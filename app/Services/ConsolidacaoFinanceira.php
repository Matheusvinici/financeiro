<?php

namespace App\Services;

use App\Models\FaturaCartao;
use App\Models\ResumoMensal;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Fonte única de consolidação financeira.
 *
 * - `fatura_cartoes`: total real (ajustado) de cada fatura por cartão/mês,
 *   quantidade de lançamentos, vencimento e status pago.
 * - `resumos_mensais`: totais de receitas e despesas (lançamentos fora do
 *   cartão de crédito somados por data + total das faturas do mês).
 *
 * Dashboard, pendências, cartões e relatório mensal leem somente destas
 * tabelas, garantindo que os valores jamais divergem entre telas.
 */
class ConsolidacaoFinanceira
{
    /**
     * Recomputa a consolidação de um usuário. Se mes/ano forem informados,
     * recomputa somente aquele mês; caso contrário, renova as fixas e usa
     * uma janela do passado ao futuro (36 meses atrás até 36 à frente).
     */
    public static function sincronizarUsuario(User $user, ?int $ano = null, ?int $mes = null): void
    {
        self::migrarPagasLegado($user);

        $hoje = Carbon::now();

        if ($ano !== null && $mes !== null) {
            $inicio = Carbon::create($ano, $mes, 1)->startOfMonth();
            $fim = $inicio->copy()->endOfMonth();
        } else {
            // Conta fixa não é "por ano": renova sozinha até 36 meses à frente.
            FixasRecorrentes::sincronizarUsuario($user);
            $inicio = $hoje->copy()->subMonths(36)->startOfMonth();
            $fim = $hoje->copy()->addMonths(36)->endOfMonth();
        }

        self::sincronizarFaturas($user, $inicio, $fim);
        self::sincronizarResumos($user, $inicio, $fim);
    }

    /**
     * Recomputa as faturas (valor, quantidade e vencimento) de um lançamento
     * alterado/criado/excluído. Preserva o status pago definido pelo usuário.
     */
    public static function sincronizarLancamento($lancamento): void
    {
        if (!$lancamento || !$lancamento->user_id) {
            return;
        }

        $user = User::find($lancamento->user_id);
        if (!$user) {
            return;
        }

        $meses = [];

        if ($lancamento->data) {
            $meses[$lancamento->data->year . '|' . $lancamento->data->month]
                = [$lancamento->data->year, $lancamento->data->month];
        }

        $original = $lancamento->getOriginal('data');
        if ($original) {
            $original = Carbon::parse($original);
            $meses[$original->year . '|' . $original->month] = [$original->year, $original->month];
        }

        $faturasAfetadas = [];
        if ($lancamento->isCreditoCartao()) {
            $chave = $lancamento->faturaChave();
            [$a, $m] = array_map('intval', explode('-', $chave));
            $meses["{$a}|{$m}"] = [$a, $m];
            $faturasAfetadas[$lancamento->cartao_id . '|' . $chave] = [$lancamento->cartao_id, $chave];

            $origData = $lancamento->getOriginal('data');
            $origCartaoId = (int) ($lancamento->getOriginal('cartao_id') ?: 0);
            if ($origCartaoId && $origData) {
                $cartaoOrig = $origCartaoId === (int) $lancamento->cartao_id
                    ? null
                    : \App\Models\Cartao::find($origCartaoId);
                if ($cartaoOrig) {
                    $origChave = $lancamento->getOriginal('fatura_key')
                        ?? $cartaoOrig->faturaChave(Carbon::parse($origData));
                    [$oa, $om] = array_map('intval', explode('-', $origChave));
                    $meses["{$oa}|{$om}"] = [$oa, $om];
                    $faturasAfetadas[$origCartaoId . '|' . $origChave] = [$origCartaoId, $origChave];
                }
            }
        }

        foreach ($meses as [$a, $m]) {
            self::sincronizarUsuario($user, $a, $m);
        }

        foreach ($faturasAfetadas as [$cartaoId, $chave]) {
            [$fA, $fM] = array_map('intval', explode('-', $chave));
            $cartao = \App\Models\Cartao::find($cartaoId);
            if ($cartao) {
                $cartao->recomporFaturaRow($fM, $fA);
            }
        }
    }

    /**
     * Migra o status "pago" do array legado (faturas_pagas) para a tabela
     * consolidada. Idempotente.
     */
    private static function migrarPagasLegado(User $user): void
    {
        foreach ($user->cartoes()->whereNotNull('faturas_pagas')->get() as $cartao) {
            foreach (($cartao->faturas_pagas ?? []) as $f) {
                $row = FaturaCartao::where('cartao_id', $cartao->id)
                    ->where('mes', (int) $f['mes'])
                    ->where('ano', (int) $f['ano'])
                    ->first();

                if ($row) {
                    if (!$row->pago) {
                        $row->update(['pago' => true]);
                    }
                } else {
                    $chave = sprintf('%04d-%02d', (int) $f['ano'], (int) $f['mes']);
                    FaturaCartao::create([
                        'user_id' => $cartao->user_id,
                        'cartao_id' => $cartao->id,
                        'mes' => (int) $f['mes'],
                        'ano' => (int) $f['ano'],
                        'chave' => $chave,
                        'total' => 0,
                        'qtd' => 0,
                        'pago' => true,
                        'vencimento' => Carbon::create((int) $f['ano'], (int) $f['mes'], 1)->format('Y-m-d'),
                    ]);
                }
            }
        }
    }

    private static function sincronizarFaturas(User $user, Carbon $inicio, Carbon $fim): void
    {
        $compras = $user->lancamentos()->with('cartao')
            ->where('tipo', 'despesa')
            ->where('forma_pagamento', 'cartao')
            ->where('cartao_debito', false)
            ->whereNotNull('cartao_id')
            ->whereBetween('data', [$inicio, $fim])
            ->get()
            ->filter(fn ($l) => $l->cartao !== null);

        $grupos = $compras->groupBy(fn ($l) => $l->cartao_id . '|' . $l->faturaChave());

        foreach ($grupos as $key => $g) {
            [$cartaoId, $chave] = explode('|', $key);
            [$ano, $mes] = array_map('intval', explode('-', $chave));
            $cartao = $g->first()->cartao;
            $cartao->recomporFaturaRow($mes, $ano);
        }
    }

    private static function sincronizarResumos(User $user, Carbon $inicio, Carbon $fim): void
    {
        $receitas = $user->lancamentos()->where('tipo', 'receita')
            ->whereBetween('data', [$inicio, $fim])
            ->selectRaw("DATE_FORMAT(data, '%Y-%m') as chave, SUM(valor) as total")
            ->groupBy('chave')->get()->keyBy('chave');

        $base = $user->lancamentos()->where('tipo', 'despesa')
            ->where('abate_saldo', true)
            ->semAssinaturasFuturas()
            ->where(function ($q) {
                $q->whereNull('cartao_id')
                    ->orWhere('cartao_debito', true)
                    ->orWhere('forma_pagamento', '!=', 'cartao');
            })
            ->whereBetween('data', [$inicio, $fim])
            ->selectRaw("DATE_FORMAT(data, '%Y-%m') as chave, SUM(valor) as total")
            ->groupBy('chave')->get()->keyBy('chave');

        $faturas = FaturaCartao::where('user_id', $user->id)
            ->whereBetween('chave', [$inicio->format('Y-m'), $fim->format('Y-m')])
            ->selectRaw('chave, SUM(total) as total')
            ->groupBy('chave')->get()->keyBy('chave');

        // Dashboard soma fatura + planos do cartão: a fatura traz só compras
        // (planos ficam fora do total da fatura) e os planos ativos entram
        // no mês da cobrança. Base (fora do cartão, por data) + faturas
        // (pelo ciclo do cartão) + planos (mês da cobrança).
        $planos = $user->lancamentos()
            ->whereNotNull('assinatura_id')
            ->whereBetween('data', [$inicio, $fim])
            ->selectRaw("DATE_FORMAT(data, '%Y-%m') as chave, SUM(valor) as total")
            ->groupBy('chave')->get()->keyBy('chave');

        $resumos = [];
        for ($d = $inicio->copy()->startOfMonth(); $d->lte($fim); $d->addMonth()) {
            $chave = $d->format('Y-m');
            $resumos[] = [
                'user_id' => $user->id,
                'ano' => (int) $d->year,
                'mes' => (int) $d->month,
                'receitas_total' => round((float) ($receitas[$chave]->total ?? 0), 2),
                'despesas_total' => round(
                    (float) ($base[$chave]->total ?? 0)
                    + (float) ($faturas[$chave]->total ?? 0)
                    + (float) ($planos[$chave]->total ?? 0),
                    2
                ),
            ];
        }

        ResumoMensal::upsert($resumos, ['user_id', 'ano', 'mes'], ['receitas_total', 'despesas_total', 'updated_at']);
    }
}