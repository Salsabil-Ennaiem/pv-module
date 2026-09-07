<?php

namespace SalsabilEnnaiem\PvModule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SalsabilEnnaiem\PvModule\Contracts\ApprovalRules;

class Pv extends Model
{
    use HasFactory;

    protected $table = 'pv_module_pvs';

    protected $fillable = [
        'titre',
        'contenu',
        'statut',
        'template_data',
        'date_generation',
        'date_validation',
        'signature_deadline',
        'receivers',
        'versions',
        'type',
        'source_type',
        'source_id',
        'created_by',
        'updated_by',
    ];

    public const STATUT_BROUILLON = 'brouillon';
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDE = 'valide';
    public const STATUT_REJETE = 'rejete';

    protected function casts(): array
    {
        return [
            'contenu' => 'array',
            'template_data' => 'array',
            'receivers' => 'array',
            'versions' => 'array',
            'date_generation' => 'datetime',
            'date_validation' => 'datetime',
            'signature_deadline' => 'datetime',
        ];
    }

    protected $userModel;

    protected function userModel(): string
    {
        return config('pv-module.user_model', \App\Models\User::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'created_by');
    }

    public function validations(): HasMany
    {
        return $this->hasMany(PvValidation::class, 'pv_id');
    }

    public function validationsEnAttente(): HasMany
    {
        return $this->validations()->where('statut', PvValidation::STATUT_EN_ATTENTE);
    }

    public function validationsValidees(): HasMany
    {
        return $this->validations()->where('statut', PvValidation::STATUT_VALIDE);
    }

    public function validationsRejetees(): HasMany
    {
        return $this->validations()->where('statut', PvValidation::STATUT_REJETE);
    }

    public function estCompletementValide(): bool
    {
        return app(ApprovalRules::class)->isApproved($this);
    }

    public function aDesRejets(): bool
    {
        return $this->validationsRejetees()->exists();
    }

    public function getValidationsRestantesAttribute(): int
    {
        return $this->validationsEnAttente()->count();
    }

    public function deadlineIsPast(): bool
    {
        return $this->signature_deadline !== null && $this->signature_deadline->isPast();
    }

    public function recordVersion(?string $statut = null): void
    {
        $versions = $this->versions ?? [];

        $versions[] = [
            'version' => count($versions) + 1,
            'titre' => $this->titre,
            'contenu' => $this->contenu,
            'template_data' => $this->template_data,
            'receivers' => $this->receivers,
            'signature_deadline' => $this->signature_deadline?->toIso8601String(),
            'statut' => $statut ?? $this->statut,
            'saved_by' => $this->created_by,
            'saved_at' => now()->toIso8601String(),
        ];

        $this->versions = $versions;
    }

    public function scopeStatut($query, string $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeBySource($query, ?string $sourceType, ?int $sourceId)
    {
        return $query
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId);
    }
}