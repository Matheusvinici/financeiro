<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumoMensal extends Model
{
    protected $table = 'resumos_mensais';

    protected $fillable = [
        'user_id', 'mes', 'ano', 'receitas_total', 'despesas_total',
    ];

    protected $casts = [
        'receitas_total' => 'decimal:2',
        'despesas_total' => 'decimal:2',
        'mes' => 'integer',
        'ano' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function saldo(): float
    {
        return (float) $this->receitas_total - (float) $this->despesas_total;
    }
}