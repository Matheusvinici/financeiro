<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaPagar extends Model
{
    protected $table = 'contas_pagar';

    protected $fillable = [
        'user_id', 'descricao', 'valor_total', 'valor_pago', 'status',
        'data_vencimento', 'categoria_id', 'observacao',
        'descricao_pagamento', 'quem_pagou',
    ];

    protected $casts = [
        'valor_total' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'data_vencimento' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function getValorRestanteAttribute(): float
    {
        return round($this->valor_total - $this->valor_pago, 2);
    }

    public function refreshStatus(): void
    {
        if ($this->valor_pago <= 0) {
            $this->status = 'aberto';
        } elseif ($this->valor_pago >= $this->valor_total) {
            $this->status = 'pago';
            $this->valor_pago = $this->valor_total;
        } else {
            $this->status = 'parcial';
        }
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'aberto' => 'Em aberto',
            'parcial' => 'Parcialmente pago',
            default => 'Pago',
        };
    }
}
