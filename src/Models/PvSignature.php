<?php

namespace SalsabilEnnaiem\PvModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PvSignature extends Model
{
    protected $table = 'pv_module_signatures';

    protected $fillable = [
        'user_id',
        'path',
        'mime',
        'signed_mechanism',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
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

    public function disk(): string
    {
        return config('pv-module.storage_disk', 'local');
    }
}