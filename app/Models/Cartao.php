<?php

namespace App\Models;

use App\Models\FaturaCartao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
        $row = $this->faturaRow($mes, $ano);
        if ($row) {
            return (bool) $row->pago;
        }

        $pagas = $this->faturas_pagas ?? [];
        return collect($pagas)->contains(fn ($f) => (int) $f['mes'] === $mes && (int) $f['ano'] === $ano);
    }

    public function marcarFaturaPaga(int $mes, int $ano): void
    {
        $this->recomporFaturaRow($mes, $ano)->update(['pago' => true]);

        $pagas = $this->faturas_pagas ?? [];
        if (!collect($pagas)->contains(fn ($f) => (int) $f['mes'] === $mes && (int) $f['ano'] === $ano)) {
            $pagas[] = ['mes' => $mes, 'ano' => $ano];
            $this->update(['faturas_pagas' => $pagas]);
        }
    }

    public function desmarcarFaturaPaga(int $mes, int $ano): void
    {
        $row = $this->faturaRow($mes, $ano);
        if ($row) {
            $row->update(['pago' => false]);
        }

        $pagas = collect($this->faturas_pagas ?? []);
        $pagas = $pagas->filter(fn ($f) => !((int) $f['mes'] === $mes && (int) $f['ano'] === $ano))->values();
        $this->update(['faturas_pagas' => $pagas->toArray()]);
    }

    private function faturaRow(int $mes, int $ano): ?FaturaCartao
    {
        return FaturaCartao::where('cartao_id', $this->id)
            ->where('mes', $mes)->where('ano', $ano)
            ->first();
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

    /**
     * Faturas de todos os cartões do usuário na janela informada, lidas da
     * tabela consolidada (total ajustado, quantidade e status pago).
     * Fonte única usada por ajustes de fatura, pendências e relatórios.
     */
    public static function faturasUsuario(User $user, Carbon $inicio, Carbon $fim): Collection
    {
        return FaturaCartao::with('cartao')
            ->where('user_id', $user->id)
            ->whereBetween('chave', [$inicio->format('Y-m'), $fim->format('Y-m')])
            ->orderBy('chave')
            ->get()
            ->filter(fn ($r) => $r->cartao !== null)
            ->map(fn ($r) => (object) [
                'cartao' => $r->cartao,
                'cartao_id' => $r->cartao_id,
                'mes' => (int) $r->mes,
                'ano' => (int) $r->ano,
                'chave' => $r->chave,
                'vencimento' => $r->vencimento ?? Carbon::create($r->ano, $r->mes, 1),
                'total' => (float) $r->total,
                'qtd' => (int) $r->qtd,
                'pago' => (bool) $r->pago,
            ])
            ->values();
    }

    /**
     * Total atual de uma fatura (compras + ajustes que entram nela),
     * lido da tabela consolidada.
     */
    public function totalFatura(int $mes, int $ano): float
    {
        $row = $this->faturaRow($mes, $ano);
        if ($row) {
            return (float) $row->total;
        }

        return (float) $this->recomporFaturaRow($mes, $ano)->total;
    }

    /**
     * Recalcula e atualiza a linha consolidada da fatura (valor exato,
     * independente da janela), preservando o status pago.
     *
     * Planos (assinaturas) ficam FORA da fatura: eles são somados à parte
     * na dashboard/relatório ("fatura + planos do cartão"), então não podem
     * estar dentro do total da fatura.
     */
    public function recomporFaturaRow(int $mes, int $ano): FaturaCartao
    {
        $chave = sprintf('%04d-%02d', $ano, $mes);

        $compras = $this->lancamentos()
            ->where('tipo', 'despesa')
            ->where('cartao_debito', false)
            ->whereNull('assinatura_id')
            ->get()
            ->filter(fn ($l) => $l->faturaChave() === $chave);

        $vencimento = Carbon::create($ano, $mes, min(
            (int) ($this->dia_vencimento ?: 1),
            Carbon::create($ano, $mes, 1)->daysInMonth
        ));

        return FaturaCartao::updateOrCreate(
            ['cartao_id' => $this->id, 'chave' => $chave],
            [
                'user_id' => $this->user_id,
                'mes' => $mes,
                'ano' => $ano,
                'total' => round($compras->sum('valor'), 2),
                'qtd' => $compras->count(),
                'vencimento' => $vencimento->format('Y-m-d'),
            ]
        );
    }

    private function calcularTotalFatura(int $mes, int $ano): float
    {
        $chave = sprintf('%04d-%02d', $ano, $mes);
        $inicio = Carbon::create($ano, $mes, 1)->subMonths(36)->startOfMonth();
        $fim = Carbon::create($ano, $mes, 1)->addMonths(36)->endOfMonth();

        return (float) $this->lancamentos()
            ->where('tipo', 'despesa')
            ->where('cartao_debito', false)
            ->whereNull('assinatura_id')
            ->whereBetween('data', [$inicio, $fim])
            ->get()
            ->filter(fn ($l) => $l->faturaChave() === $chave)
            ->sum('valor');
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
