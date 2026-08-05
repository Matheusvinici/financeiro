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
        'faturas_pagas',
    ];

    protected $casts = [
        'limite' => 'decimal:2',
        'dia_fechamento' => 'integer',
        'dia_vencimento' => 'integer',
        'faturas_pagas' => 'array',
        'ativo' => 'boolean',
    ];

    public function faturaPaga(int $mes, int $ano): bool
    {
        $pagas = $this->faturas_pagas ?? [];
        return collect($pagas)->contains(fn ($f) => (int) $f['mes'] === $mes && (int) $f['ano'] === $ano);
    }

    public function marcarFaturaPaga(int $mes, int $ano): void
    {
        $pagas = $this->faturas_pagas ?? [];
        if (!$this->faturaPaga($mes, $ano)) {
            $pagas[] = ['mes' => $mes, 'ano' => $ano];
            $this->update(['faturas_pagas' => $pagas]);
        }
    }

    public function desmarcarFaturaPaga(int $mes, int $ano): void
    {
        $pagas = collect($this->faturas_pagas ?? []);
        $pagas = $pagas->filter(fn ($f) => !((int) $f['mes'] === $mes && (int) $f['ano'] === $ano))->values();
        $this->update(['faturas_pagas' => $pagas->toArray()]);
    }

    /**
     * Retorna a primeira fatura não paga (mes/ano) ou null se todas estiverem pagas.
     */
    public function primeiraFaturaNaoPaga(): ?array
    {
        $pagas = collect($this->faturas_pagas ?? []);
        $hoje = now();

        for ($i = 0; $i < 24; $i++) {
            $mes = $hoje->copy()->subMonths($i)->month;
            $ano = $hoje->copy()->subMonths($i)->year;
            if (!$pagas->contains(fn ($f) => (int) $f['mes'] === $mes && (int) $f['ano'] === $ano)) {
                return ['mes' => $mes, 'ano' => $ano];
            }
        }

        return null;
    }

    /**
     * Próxima fatura ainda não paga, buscando do mês atual para frente.
     * É a fatura que está em aberto / sendo construída.
     */
    public function proximaFaturaAberta(): ?array
    {
        $pagas = collect($this->faturas_pagas ?? []);
        $hoje = now();

        for ($i = 0; $i < 24; $i++) {
            $mes = $hoje->copy()->addMonths($i)->month;
            $ano = $hoje->copy()->addMonths($i)->year;
            if (!$pagas->contains(fn ($f) => (int) $f['mes'] === $mes && (int) $f['ano'] === $ano)) {
                return ['mes' => $mes, 'ano' => $ano];
            }
        }

        return null;
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
