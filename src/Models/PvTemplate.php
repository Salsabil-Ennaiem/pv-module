<?php

namespace SalsabilEnnaiem\PvModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PvTemplate extends Model
{
    protected $table = 'pv_module_pdf_templates';

    protected $fillable = [
        'user_id',
        'type',
        'config',
        'orientation',
        'is_default',
        'is_custom',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_default' => 'boolean',
            'is_custom' => 'boolean',
        ];
    }

    protected function userModel(): string
    {
        return config('pv-module.user_model', \App\Models\User::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'user_id');
    }

    public static function forUser(int $userId, string $type): ?self
    {
        return static::where('user_id', $userId)
                     ->where('type', $type)
                     ->first();
    }

    public static function getDefault(string $type): ?self
    {
        return static::whereNull('user_id')
                     ->where('type', $type)
                     ->where('is_default', true)
                     ->first();
    }

    public static function getActive(int $userId, string $type): ?self
    {
        return static::forUser($userId, $type)
            ?? static::getDefault($type);
    }

    public static function hasCustom(int $userId, string $type): bool
    {
        $t = static::forUser($userId, $type);

        return $t !== null && $t->is_custom === true;
    }

    public static function saveForUser(
        int $userId,
        string $type,
        array $config,
        string $orientation = 'portrait'
    ): self {
        return static::updateOrCreate(
            ['user_id' => $userId, 'type' => $type],
            [
                'config' => $config,
                'orientation' => $orientation,
                'is_custom' => true,
                'is_default' => false,
            ]
        );
    }

    public static function resetForUser(int $userId, string $type): void
    {
        static::where('user_id', $userId)
              ->where('type', $type)
              ->delete();
    }

    public function getColumns(): array
    {
        return $this->config['columns'] ?? [];
    }

    public function getSections(): array
    {
        return $this->config['sections'] ?? [];
    }

    public function getSectionsOrdered(): array
    {
        $sections = $this->getSections();

        $header = collect($sections)->firstWhere('id', 'header');
        $signature = collect($sections)->firstWhere('id', 'signature');
        $middle = collect($sections)
            ->reject(fn ($s) => in_array($s['id'] ?? null, ['header', 'signature']))
            ->values()
            ->all();

        return array_values(array_filter(array_merge(
            $header ? [$header] : [],
            $middle,
            $signature ? [$signature] : [],
        )));
    }

    public function getMargins(): array
    {
        return $this->config['margins'] ?? [
            'top' => 20,
            'bottom' => 20,
            'left' => 20,
            'right' => 20,
        ];
    }
}