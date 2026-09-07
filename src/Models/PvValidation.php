<?php

namespace SalsabilEnnaiem\PvModule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PvValidation extends Model
{
    use HasFactory;

    protected $table = 'pv_module_pv_validations';

    protected $fillable = [
        'pv_id',
        'user_id',
        'version',
        'statut',
        'commentaire',
        'date_reponse',
    ];

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDE = 'valide';
    public const STATUT_REJETE = 'rejete';

    protected function casts(): array
    {
        return [
            'date_reponse' => 'datetime',
            'version' => 'integer',
        ];
    }

    protected function userModel(): string
    {
        return config('pv-module.user_model', \App\Models\User::class);
    }

    public function pv(): BelongsTo
    {
        return $this->belongsTo(Pv::class, 'pv_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'user_id');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', self::STATUT_EN_ATTENTE);
    }

    public function scopeValide($query)
    {
        return $query->where('statut', self::STATUT_VALIDE);
    }

    public function scopeRejete($query)
    {
        return $query->where('statut', self::STATUT_REJETE);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function valider(?string $commentaire = null): void
    {
        $this->statut = self::STATUT_VALIDE;
        $this->commentaire = $commentaire;
        $this->date_reponse = now();
        $this->version = count($this->pv->versions ?? []) ?: 1;
        $this->save();

        if ($this->pv->estCompletementValide()) {
            $this->pv->statut = Pv::STATUT_VALIDE;
            $this->pv->date_validation = now();
            $this->pv->recordVersion(Pv::STATUT_VALIDE);
            $this->pv->save();
        }
    }

    public function rejeter(?string $commentaire = null): void
    {
        $this->statut = self::STATUT_REJETE;
        $this->commentaire = $commentaire;
        $this->date_reponse = now();
        $this->version = count($this->pv->versions ?? []) ?: 1;
        $this->save();

        $this->pv->statut = Pv::STATUT_REJETE;
        $this->pv->recordVersion(Pv::STATUT_REJETE);
        $this->pv->save();
    }
}