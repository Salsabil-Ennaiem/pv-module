<?php

namespace SalsabilEnnaiem\PvModule\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use SalsabilEnnaiem\PvModule\Services\SignatureService;

class SignatureController extends Controller
{
    public function __construct(
        private SignatureService $signatures,
    ) {
    }

    public function index(Request $request): View
    {
        return view('pv-module::signature.index', [
            'signature' => $this->signatures->getSignature($request->user()),
            'maxSizeKb' => (int) config('pv-module.max_signature_size_kb', 2048),
        ]);
    }

    public function upload(Request $request): RedirectResponse|JsonResponse
    {
        $errors = $this->signatures->validateSignatureFile($request->file('signature'));

        if (!empty($errors)) {
            return $this->response([
                'success' => false,
                'errors' => $errors,
            ], __("Votre signature n'a pas pu être enregistrée."), error: true);
        }

        try {
            $this->signatures->storeFromFile($request->file('signature'), $request->user());
        } catch (\InvalidArgumentException $e) {
            return $this->response([
                'success' => false,
                'errors' => [$e->getMessage()],
            ], __("Votre signature n'a pas pu être enregistrée."), error: true);
        }

        return $this->response(
            ['success' => true, 'message' => __('Signature enregistrée.')],
            __('Signature enregistrée avec succès.')
        );
    }

    public function save(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'signature_data' => ['required', 'string'],
        ]);

        try {
            $this->signatures->storeFromBase64($request->input('signature_data'), $request->user());
        } catch (\InvalidArgumentException $e) {
            return $this->response([
                'success' => false,
                'errors' => [$e->getMessage()],
            ], __("Votre signature n'a pas pu être enregistrée."), error: true);
        }

        return $this->response(
            ['success' => true, 'message' => 'Signature enregistrée.'],
            'Signature enregistrée avec succès.'
        );
    }

    public function delete(Request $request): RedirectResponse|JsonResponse
    {
        $this->signatures->deleteSignature($request->user());

        return $this->response(
            ['success' => true, 'message' => __('Signature supprimée.')],
            __('Signature supprimée.')
        );
    }

    public function image(Request $request): Response
    {
        $user = $request->user();
        $signature = $this->signatures->getSignature($user);

        if ($signature === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $disk = Storage::disk($signature->disk());

        if (!$disk->exists($signature->path)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $disk->response($signature->path, 'signature', [
            'Content-Type' => $signature->mime ?? 'image/png',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function info(Request $request): JsonResponse
    {
        $user = $request->user();
        $signature = $this->signatures->getSignature($user);
        $exists = $signature !== null
            && Storage::disk($signature->disk())->exists($signature->path);

        return response()->json([
            'has_signature' => $this->signatures->hasSignature($user),
            'file_exists' => $exists,
            'mime' => $signature?->mime,
            'updated_at' => $signature?->updated_at?->toIso8601String(),
            'url' => $exists ? route('pv-module.signature.image') : null,
            'base64' => $exists ? $this->signatures->getSignatureBase64($user) : null,
        ]);
    }

    protected function response(array $json, string $flash, bool $error = false): RedirectResponse|JsonResponse
    {
        if (request()->expectsJson()) {
            return response()->json($json, $error ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
        }

        return redirect()
            ->route('pv-module.signature.index')
            ->with($error ? 'error' : 'success', $flash);
    }
}