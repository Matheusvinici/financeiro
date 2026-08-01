<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cartao extends Model
{
    protected $table = 'cartoes';

    protected $fillable = [
        'user_id', 'nome', 'tipo', 'bandeira', 'limite', 'dia_fechamento', 'dia_vencimento', 'ativo',
    ];

    protected $casts = [
        'limite' => 'decimal:2',
        'dia_fechamento' => 'integer',
        'dia_vencimento' => 'integer',
        'ativo' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'credito' => 'Crédito',
            'debito' => 'Débito',
            default => 'Crédito/Débito',
        };
    }
}
