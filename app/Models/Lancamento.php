<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lancamento extends Model
{
    protected $fillable = [
        'user_id', 'data', 'descricao', 'valor', 'tipo',
        'categoria_id', 'subcategoria_id', 'forma_pagamento',
        'cartao_id', 'recorrente', 'qtd_parcelas', 'parcela_atual',
        'origem_id', 'observacao',
    ];

    protected $casts = [
        'data' => 'date',
        'valor' => 'decimal:2',
        'recorrente' => 'boolean',
        'qtd_parcelas' => 'integer',
        'parcela_atual' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Subcategoria::class);
    }

    public function cartao(): BelongsTo
    {
        return $this->belongsTo(Cartao::class);
    }

    public function origem(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class, 'origem_id');
    }

    public function isParcela(): bool
    {
        return $this->qtd_parcelas > 1;
    }

    public function getFormaLabelAttribute(): string
    {
        return match ($this->forma_pagamento) {
            'cartao' => 'Cartão',
            'pix' => 'Pix',
            'dinheiro' => 'Dinheiro',
            'boleto' => 'Boleto',
            default => 'Outros',
        };
    }

    public function scopeNoMes(Builder $q, int $ano, int $mes): Builder
    {
        return $q->whereYear('data', $ano)->whereMonth('data', $mes);
    }

    public function scopeQuando(Builder $q, string $periodo, int $ano, ?int $mes = null): Builder
    {
        $q->whereYear('data', $ano);

        if ($periodo === 'mes') {
            $q->whereMonth('data', $mes);
        }

        return $q;
    }
}
