<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Cartao extends Model
{
    protected $table = 'cartoes';

    protected $fillable = [
        'user_id', 'nome', 'tipo', 'bandeira', 'limite', 'dia_fechamento', 'dia_vencimento', 'ativo',
        'fatura_paga_mes', 'fatura_paga_ano',
    ];

    protected $casts = [
        'limite' => 'decimal:2',
        'dia_fechamento' => 'integer',
        'dia_vencimento' => 'integer',
        'fatura_paga_mes' => 'integer',
        'fatura_paga_ano' => 'integer',
        'ativo' => 'boolean',
    ];

    public function faturaPaga(int $mes, int $ano): bool
    {
        return (int) $this->fatura_paga_mes === $mes && (int) $this->fatura_paga_ano === $ano;
    }

    /**
     * Retorna "YYYY-MM" do vencimento da fatura em que esta compra entra,
     * considerando o ciclo fechamento -> vencimento do cartão.
     */
    public function faturaChave(Carbon $data): string
    {
        $ano = $data->year;
        $mes = $data->month;

        $fechamento = (int) $this->dia_fechamento;
        if ($fechamento > 0 && $data->day >= $fechamento) {
            $mes++;
            if ($mes > 12) {
                $mes = 1;
                $ano++;
            }
        }

        $vencimento = (int) $this->dia_vencimento;
        if ($vencimento > 0 && $vencimento < $fechamento) {
            $mes++;
            if ($mes > 12) {
                $mes = 1;
                $ano++;
            }
        }

        return sprintf('%04d-%02d', $ano, $mes);
    }

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
