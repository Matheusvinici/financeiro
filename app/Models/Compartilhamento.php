<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compartilhamento extends Model
{
    protected $fillable = [
        'dono_user_id', 'convidado_user_id', 'categoria_ids', 'so_leitura',
    ];

    protected $casts = [
        'categoria_ids' => 'array',
        'so_leitura' => 'boolean',
    ];

    public function dono(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dono_user_id');
    }

    public function convidado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'convidado_user_id');
    }
}
