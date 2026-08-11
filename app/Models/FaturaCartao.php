<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class FaturaCartao extends Model
{
    protected $table = 'fatura_cartoes';

    protected $fillable = [
        'user_id', 'cartao_id', 'mes', 'ano', 'chave', 'total', 'qtd', 'pago', 'vencimento',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'qtd' => 'integer',
        'pago' => 'boolean',
        'vencimento' => 'date',
        'mes' => 'integer',
        'ano' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cartao(): BelongsTo
    {
        return $this->belongsTo(Cartao::class);
    }
}