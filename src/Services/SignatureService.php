<?php

namespace SalsabilEnnaiem\PvModule\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SalsabilEnnaiem\PvModule\Models\PvSignature;

class SignatureService
{
    public function disk(): string
    {
        return (string) config('pv-module.storage_disk', 'local');
    }

    public function storeFromFile($file, $user): PvSignature
    {
        $this->assertValidImage($file->getRealPath());

        $path = $file->store('signatures', $this->disk());

        return $this->upsert($user, $path, $file->getMimeType());
    }

    public function storeFromBase64(string $base64Data, $user): PvSignature
    {
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        $decoded = base64_decode($imageData, true);

        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64 image data');
        }

        $tmp = @tempnam(sys_get_temp_dir(), 'pvm');
        if ($tmp === false) {
            throw new \RuntimeException('Impossible de créer un fichier temporaire.');
        }

        try {
            @file_put_contents($tmp, $decoded);
            $this->assertValidImage($tmp);
        } finally {
            @unlink($tmp);
        }

        $filename = 'signature_'.$user->getKey().'_'.Str::random(10).'.png';
        $path = 'signatures/'.$filename;

        Storage::disk($this->disk())->put($path, $decoded);

        return $this->upsert($user, $path, 'image/png');
    }

    protected function assertValidImage(string $path): void
    {
        $info = @getimagesize($path);

        if ($info === false) {
            throw new \InvalidArgumentException('Le fichier n\'est pas une image valide.');
        }

        $allowed = (array) config('pv-module.allowed_signature_mimes', ['image/jpeg', 'image/png', 'image/gif']);
        $mime = $info['mime'] ?? null;

        if ($mime === null || !in_array($mime, $allowed, true)) {
            throw new \InvalidArgumentException('Le format de l\'image n\'est pas autorisé.');
        }
    }

    protected function upsert($user, string $path, ?string $mime): PvSignature
    {
        return PvSignature::updateOrCreate(
            ['user_id' => $user->getKey()],
            ['path' => $path, 'mime' => $mime]
        );
    }

    public function getSignature($user): ?PvSignature
    {
        return PvSignature::where('user_id', $user->getKey())->first();
    }

    public function hasSignature($user): bool
    {
        $signature = $this->getSignature($user);

        return $signature !== null && Storage::disk($signature->disk())->exists($signature->path);
    }

    public function getSignatureUrl($user): ?string
    {
        $signature = $this->getSignature($user);

        return $signature ? Storage::disk($signature->disk())->url($signature->path) : null;
    }

    public function getSignatureBase64($user): ?string
    {
        $signature = $this->getSignature($user);
        if ($signature === null) {
            return null;
        }

        $path = Storage::disk($signature->disk())->path($signature->path);
        if (!file_exists($path)) {
            return null;
        }

        $mime = $signature->mime ?? mime_content_type($path);
        $data = base64_encode(file_get_contents($path));

        return "data:{$mime};base64,{$data}";
    }

    public function deleteSignature($user): bool
    {
        $signature = $this->getSignature($user);
        if ($signature === null) {
            return false;
        }

        Storage::disk($signature->disk())->delete($signature->path);
        $signature->delete();

        return true;
    }

    public function validateSignatureFile($file): array
    {
        $maxSize = (int) config('pv-module.max_signature_size_kb', 2048);
        $allowedMimes = (array) config('pv-module.allowed_signature_mimes', ['image/jpeg', 'image/png', 'image/gif']);

        $errors = [];

        if (!$file) {
            $errors[] = 'Le fichier signature est requis.';

            return $errors;
        }

        if ($file->getSize() > $maxSize * 1024) {
            $errors[] = "Le fichier ne doit pas dépasser {$maxSize}KB.";
        }

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'Le format du fichier doit être JPEG, PNG ou GIF.';
        }

        return $errors;
    }
}