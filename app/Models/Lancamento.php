<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Lancamento extends Model
{
    protected $fillable = [
        'user_id', 'data', 'descricao', 'valor', 'tipo',
        'categoria_id', 'subcategoria_id', 'forma_pagamento',
        'cartao_id', 'recorrente', 'qtd_parcelas', 'parcela_atual',
        'origem_id', 'observacao', 'pago', 'abate_saldo', 'cartao_debito',
        'assinatura_id', 'ajuste', 'fatura_key',
    ];

    protected $casts = [
        'data' => 'date',
        'valor' => 'decimal:2',
        'recorrente' => 'boolean',
        'qtd_parcelas' => 'integer',
        'parcela_atual' => 'integer',
        'pago' => 'boolean',
        'abate_saldo' => 'boolean',
        'cartao_debito' => 'boolean',
        'ajuste' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assinatura(): BelongsTo
    {
        return $this->belongsTo(Assinatura::class);
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

    public function serieRelacionada(): \Illuminate\Support\Collection
    {
        if ($this->assinatura_id) {
            return collect([$this]);
        }

        if ($this->isParcela()) {
            $origem = $this->origem_id ? self::find($this->origem_id) : $this;

            return self::where('user_id', $this->user_id)
                ->where(function ($q) use ($origem) {
                    $q->where('id', $origem->id)->orWhere('origem_id', $origem->id);
                })
                ->orderBy('parcela_atual')
                ->get();
        }

        if ($this->recorrente) {
            return self::where('user_id', $this->user_id)
                ->where('descricao', $this->descricao)
                ->where('tipo', $this->tipo)
                ->whereNull('assinatura_id')
                ->where('recorrente', true)
                ->orderBy('data')
                ->get();
        }

        return collect([$this]);
    }

    public function isPendente(): bool
    {
        return $this->tipo === 'despesa' && !$this->pago;
    }

    public function isVencido(): bool
    {
        return $this->isPendente() && $this->data?->isPast();
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

    public function scopeContabilizadas(Builder $q): Builder
    {
        return $q->where(function (Builder $sub) {
            $sub->whereNull('assinatura_id')
                ->orWhereDate('data', '<=', Carbon::now());
        });
    }
}
