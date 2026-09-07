<?php

namespace SalsabilEnnaiem\PvModule\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use SalsabilEnnaiem\PvModule\Contracts\CanManagePv;
use SalsabilEnnaiem\PvModule\Exceptions\PvModuleException;
use SalsabilEnnaiem\PvModule\Http\Requests\StorePvRequest;
use SalsabilEnnaiem\PvModule\Http\Requests\UpdatePvRequest;
use SalsabilEnnaiem\PvModule\Models\Pv;
use SalsabilEnnaiem\PvModule\Models\PvTemplate;
use SalsabilEnnaiem\PvModule\Services\PdfService;
use SalsabilEnnaiem\PvModule\Services\PvService;
use SalsabilEnnaiem\PvModule\Services\SignatureService;

class PvController extends Controller
{
    public function __construct(
        private PvService $pvService,
        private PdfService $pdfService,
        private CanManagePv $rules,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = $this->visibleQuery($user)
            ->withCount('validations', 'validationsEnAttente', 'validationsValidees', 'validationsRejetees');

        if ($term = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($term) {
                $q->where('titre', 'like', "%{$term}%")
                  ->orWhere('contenu', 'like', "%{$term}%");
            });
        }

        if ($statut = $request->input('statut')) {
            $query->where('statut', $statut);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $query->orderBy(
            $request->input('sort') === 'titre'
                ? 'titre'
                : 'created_at',
            ($request->input('sort') === 'titre' || $request->input('sort') === 'oldest') ? 'asc' : 'desc'
        );

        $pvs = $query->paginate(15)->withQueryString();

        $filters = $request->only(['q', 'statut', 'from', 'to', 'sort']);
        $filters = array_filter($filters, fn ($v) => $v !== '' && $v !== null);

        $statutLabels = [
            Pv::STATUT_BROUILLON => __('Brouillon'),
            Pv::STATUT_EN_ATTENTE => __('En attente'),
            Pv::STATUT_VALIDE => __('Validé'),
            Pv::STATUT_REJETE => __('Rejeté'),
        ];

        return view('pv-module::pv.index', [
            'pvs' => $pvs,
            'statuts' => $statutLabels,
            'filters' => $filters,
            'comptesParStatut' => [
                'tout' => $this->visibleQuery($user)->count(),
                Pv::STATUT_BROUILLON => $this->visibleQuery($user)->where('statut', Pv::STATUT_BROUILLON)->count(),
                Pv::STATUT_EN_ATTENTE => $this->visibleQuery($user)->where('statut', Pv::STATUT_EN_ATTENTE)->count(),
                Pv::STATUT_VALIDE => $this->visibleQuery($user)->where('statut', Pv::STATUT_VALIDE)->count(),
                Pv::STATUT_REJETE => $this->visibleQuery($user)->where('statut', Pv::STATUT_REJETE)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $template = PvTemplate::getActive($user->getKey(), 'pv');

        return view('pv-module::pv.create', [
            'template' => $template,
            'types' => (array) config('pv-module.types', ['pv']),
            'users' => $this->allUsers(),
            'hasSignature' => app(SignatureService::class)->hasSignature($user),
        ]);
    }

    public function store(StorePvRequest $request): RedirectResponse
    {
        $actor = $request->user();

        if (!$this->rules->canCreate($actor)) {
            abort(Response::HTTP_FORBIDDEN, __("Vous n'êtes pas autorisé à créer un PV."));
        }

        try {
            $pv = $this->pvService->store($request->validated(), $actor);
        } catch (PvModuleException $e) {
            return redirect()->back()
                ->withErrors([$e->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('pv-module.show', $pv)
            ->with('success', __('PV créé en brouillon.'));
    }

    public function storeAndSend(StorePvRequest $request): RedirectResponse
    {
        $actor = $request->user();

        if (!$this->rules->canCreate($actor)) {
            abort(Response::HTTP_FORBIDDEN, __("Vous n'êtes pas autorisé à créer un PV."));
        }

        try {
            $pv = $this->pvService->storeAndSend($request->validated(), $actor);
        } catch (PvModuleException $e) {
            return redirect()->back()
                ->withErrors([$e->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('pv-module.show', $pv)
            ->with('success', __('PV créé et envoyé aux participants pour signature.'));
    }

    public function show(Request $request, Pv $pv): View
    {
        $this->authorizeDownload($request, $pv);

        $pv->load(['validations.user', 'createur']);

        return view('pv-module::pv.show', [
            'pv' => $pv,
            'payload' => $this->pdfService->pvPayload($pv),
        ]);
    }

    public function edit(Request $request, Pv $pv): View
    {
        if (!$this->rules->canUpdate($request->user(), $pv)) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }

        $template = PvTemplate::getActive($request->user()->getKey(), 'pv');

        return view('pv-module::pv.edit', [
            'pv' => $pv->load('createur'),
            'template' => $template,
            'types' => (array) config('pv-module.types', ['pv']),
            'users' => $this->allUsers(),
        ]);
    }

    public function preview(Request $request, Pv $pv): View
    {
        $this->authorizeDownload($request, $pv);

        return view('pv-module::pv.preview', [
            'payload' => $this->pdfService->pvPayload($pv),
        ]);
    }

    public function pdf(Request $request, Pv $pv): Response
    {
        $this->authorizeDownload($request, $pv);

        $pdf = $this->pdfService->generatePv($pv);

        $filename = 'pv-'.$pv->id.'-'.now()->format('Ymd-His').'.pdf';

        return response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function versions(Request $request, Pv $pv): View
    {
        $this->authorizeDownload($request, $pv);

        return view('pv-module::pv.versions', [
            'pv' => $pv->load('createur'),
        ]);
    }

    public function send(Request $request, Pv $pv): RedirectResponse
    {
        $actor = $request->user();

        if (!$this->rules->canSend($actor, $pv)) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }

        try {
            $this->pvService->send(
                $pv,
                $request->input('receivers', []),
                $request->input('signature_deadline'),
                (bool) $request->input('update_deadline', false),
            );
        } catch (PvModuleException $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }

        return redirect()
            ->route('pv-module.show', $pv)
            ->with('success', __('PV envoyé aux participants.'));
    }

    public function validate(Request $request, Pv $pv): RedirectResponse
    {
        return $this->respondByValidation($request, $pv, function () use ($request, $pv) {
            $this->assertCanValidate($request, $pv);

            return $this->pvService->validate(
                $pv,
                $request->user(),
                $request->input('commentaire'),
            );
        }, __('Votre validation a été enregistrée.'));
    }

    public function sign(Request $request, Pv $pv): RedirectResponse
    {
        return $this->respondByValidation($request, $pv, function () use ($request, $pv) {
            $this->assertCanSign($request, $pv);

            return $this->pvService->sign($pv, $request->user());
        }, __('Votre signature a été enregistrée.'));
    }

    public function reject(Request $request, Pv $pv): RedirectResponse
    {
        $request->validate(['commentaire' => ['required', 'string', 'max:1000']]);

        return $this->respondByValidation($request, $pv, function () use ($request, $pv) {
            $this->assertCanValidate($request, $pv);

            return $this->pvService->reject(
                $pv,
                $request->user(),
                $request->input('commentaire'),
            );
        }, __('Le PV a été rejeté.'));
    }

    public function update(UpdatePvRequest $request, Pv $pv): RedirectResponse
    {
        $actor = $request->user();

        if (!$this->rules->canUpdate($actor, $pv)) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }

        try {
            $this->pvService->update($pv, $request->validated(), $actor);
        } catch (PvModuleException $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }

        return redirect()
            ->route('pv-module.show', $pv)
            ->with('success', __('PV mis à jour.'));
    }

    public function destroy(Request $request, Pv $pv): RedirectResponse
    {
        $actor = $request->user();

        if (!$this->rules->canDelete($actor, $pv)) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }

        $pv->validations()->delete();
        $pv->delete();

        return redirect()
            ->route('pv-module.index')
            ->with('success', __('PV supprimé.'));
    }

    public function notifications(Request $request): View
    {
        $user = $request->user();

        $query = $user?->notifications();

        $items = $query?->latest()->paginate(20)->withQueryString();

        return view('pv-module::notifications.index', [
            'notifications' => $items,
            'unreadCount' => $user?->unreadNotifications()->count() ?? 0,
        ]);
    }

    public function readNotification(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();
        $item = $user?->notifications()->findOrFail($notification);
        $item?->markAsRead();

        $url = (string) data_get($item?->data ?? [], 'url');
        $url = $this->safeNotificationUrl($url);

        return redirect($url ?: route('pv-module.index'));
    }

    protected function safeNotificationUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }

        if (!str_starts_with($url, '/')) {
            $host = parse_url($url, PHP_URL_HOST);

            if ($host === null || $host === '') {
                return '';
            }

            $appHost = (string) request()->getHost();

            return strtolower($host) === strtolower($appHost) ? $url : '';
        }

        return $url;
    }

    public function readAllNotifications(Request $request): RedirectResponse
    {
        $request->user()?->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->back()->with('success', __('Notifications marquées comme lues.'));
    }

    protected function respondByValidation(Request $request, Pv $pv, callable $action, string $success): RedirectResponse
    {
        try {
            $pv = $action();
        } catch (PvModuleException $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }

        return redirect()
            ->route('pv-module.show', $pv)
            ->with('success', $success);
    }

    protected function assertCanValidate(Request $request, Pv $pv): void
    {
        if (!$this->rules->canValidate($request->user(), $pv)) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }
    }

    protected function assertCanSign(Request $request, Pv $pv): void
    {
        if (!$this->rules->canSign($request->user(), $pv)) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }
    }

    protected function authorizeDownload(Request $request, Pv $pv): void
    {
        if (!$this->rules->canDownload($request->user(), $pv)) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }
    }

    protected function allUsers(): \Illuminate\Support\Collection
    {
        $model = (string) config('pv-module.user_model', \App\Models\User::class);

        return $model::query()
            ->get()
            ->sortBy(fn ($user) => mb_strtolower((string) $user->name))
            ->values();
    }

    protected function visibleQuery(mixed $user): \Illuminate\Database\Eloquent\Builder
    {
        return Pv::query()
            ->where(fn ($q) => $q->where('created_by', $user->getKey())
                ->orWhereHas('validations', fn ($v) => $v->where('user_id', $user->getKey())));
    }
}