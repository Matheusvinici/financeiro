<?php

namespace App\Http\Controllers;

use App\Models\Lancamento;
use App\Models\ResumoMensal;
use App\Services\ConsolidacaoFinanceira;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RelatorioController extends Controller
{
    public function mensal(Request $request)
    {
        $user = auth()->user();

        ConsolidacaoFinanceira::sincronizarUsuario($user);

        $ano = (int) $request->input('ano', Carbon::now()->year);
        $mes = $request->input('mes', 'todos');
        $mes = $mes === 'todos' || $mes === '' ? 'todos' : (int) $mes;
        $mesAtual = $mes === 'todos' ? null : $mes;

        $receitas = $user->lancamentos()->where('tipo', 'receita')
            ->whereYear('data', $ano)
            ->when($mesAtual, fn ($q) => $q->whereMonth('data', $mesAtual))
            ->with('categoria', 'subcategoria')->get();

        [$despesas] = $this->despesasDoPeriodo($user, $mesAtual, $ano);

        $despesasNaoAbateLista = $user->lancamentos()->where('tipo', 'despesa')
            ->where('abate_saldo', false)
            ->whereYear('data', $ano)
            ->when($mesAtual, fn ($q) => $q->whereMonth('data', $mesAtual))
            ->get();
        $despesasNaoAbate = $despesasNaoAbateLista->sum('valor');
        $totaisMesNaoAbate = [];
        foreach ($mesAtual ? [$mesAtual] : range(1, 12) as $m) {
            $totaisMesNaoAbate[$m] = $despesasNaoAbateLista->filter(fn ($l) => $l->data->month === $m)->sum('valor');
        }

        $agrupadas = $this->agruparPorCategoria($receitas, $despesas);

        $resumos = ResumoMensal::where('user_id', $user->id)
            ->where('ano', $ano)
            ->get()
            ->keyBy('mes');

        $totaisMesReceitas = [];
        $totaisMesDespesas = [];
        $totaisAno = [];
        foreach ($mesAtual ? [$mesAtual] : range(1, 12) as $m) {
            $totaisMesReceitas[$m] = (float) ($resumos[$m]->receitas_total ?? 0);
            $totaisMesDespesas[$m] = (float) ($resumos[$m]->despesas_total ?? 0);
        }
        $totaisAno['receitas'] = round(collect($totaisMesReceitas)->sum(), 2);
        $totaisAno['despesas'] = round(collect($totaisMesDespesas)->sum(), 2);
        $totaisAno['saldo'] = round($totaisAno['receitas'] - $totaisAno['despesas'], 2);

        $saldoMes = [];
        foreach ($mesAtual ? [$mesAtual] : range(1, 12) as $m) {
            $saldoMes[$m] = ($totaisMesReceitas[$m] ?? 0) - ($totaisMesDespesas[$m] ?? 0);
        }

        $cartoes = $user->cartoes()->orderBy('nome')->get();
        $gastosCartaoMes = [];
        $faturasPorCartao = \App\Models\FaturaCartao::where('user_id', $user->id)
            ->where('ano', $ano)
            ->get()
            ->groupBy('cartao_id');
        foreach ($cartoes as $cartao) {
            $gastosCartaoMes[$cartao->id] = [];
            foreach ($mesAtual ? [$mesAtual] : range(1, 12) as $m) {
                $linha = $faturasPorCartao->get($cartao->id);
                $gastosCartaoMes[$cartao->id][$m] = (float) ($linha?->firstWhere('mes', $m)->total ?? 0);
            }
        }

        return view('relatorios.mensal', compact(
            'ano', 'mes', 'mesAtual', 'agrupadas', 'totaisMesReceitas', 'totaisMesDespesas',
            'saldoMes', 'totaisAno', 'cartoes', 'gastosCartaoMes', 'despesasNaoAbate',
            'totaisMesNaoAbate'
        ));
    }

    public function exportarPdf(Request $request)
    {
        $user = auth()->user();

        ConsolidacaoFinanceira::sincronizarUsuario($user);

        $ano = (int) $request->input('ano', Carbon::now()->year);
        $mes = $request->input('mes', 'todos');
        $mes = $mes === 'todos' || $mes === '' ? 'todos' : (int) $mes;
        $mesAtual = $mes === 'todos' ? null : $mes;

        $receitas = $user->lancamentos()->where('tipo', 'receita')
            ->whereYear('data', $ano)
            ->when($mesAtual, fn ($q) => $q->whereMonth('data', $mesAtual))
            ->with('categoria', 'subcategoria')->get();

        [$despesas] = $this->despesasDoPeriodo($user, $mesAtual, $ano);

        $agrupadas = $this->agruparPorCategoria($receitas, $despesas);

        $totaisMesReceitas = [];
        $totaisMesDespesas = [];
        $resumos = ResumoMensal::where('user_id', $user->id)
            ->where('ano', $ano)
            ->get()
            ->keyBy('mes');
        foreach ($mesAtual ? [$mesAtual] : range(1, 12) as $m) {
            $totaisMesReceitas[$m] = (float) ($resumos[$m]->receitas_total ?? 0);
            $totaisMesDespesas[$m] = (float) ($resumos[$m]->despesas_total ?? 0);
        }
        $totaisAno = [
            'receitas' => round(collect($totaisMesReceitas)->sum(), 2),
            'despesas' => round(collect($totaisMesDespesas)->sum(), 2),
            'saldo' => round(collect($totaisMesReceitas)->sum() - collect($totaisMesDespesas)->sum(), 2),
        ];

        $pdf = Pdf::loadView('relatorios.pdf', compact('ano', 'mes', 'mesAtual', 'agrupadas', 'totaisMesReceitas', 'totaisMesDespesas', 'totaisAno'));

        return $pdf->download("relatorio-financeiro-{$ano}.pdf");
    }

    /**
     * Despesas que abatem do saldo: lançamentos normais (fora de cartão de
     * crédito) somados por data, uma linha por fatura de cartão e uma linha
     * por plano ativo do cartão no mês — dashboard soma fatura + planos.
     */
    private function despesasDoPeriodo($user, ?int $mesAtual, int $ano): array
    {
        $inicio = $mesAtual !== null
            ? Carbon::create($ano, $mesAtual, 1)->startOfMonth()
            : Carbon::create($ano, 1, 1)->startOfMonth();
        $fim = $mesAtual !== null
            ? Carbon::create($ano, $mesAtual, 1)->endOfMonth()
            : Carbon::create($ano, 12, 31)->endOfMonth();

        $base = $user->lancamentos()->with(['categoria', 'subcategoria'])
            ->where('tipo', 'despesa')->where('abate_saldo', true)->semAssinaturasFuturas()
            ->where(function ($q) {
                $q->whereNull('cartao_id')
                    ->orWhere('cartao_debito', true)
                    ->orWhere('forma_pagamento', '!=', 'cartao');
            })
            ->whereBetween('data', [$inicio, $fim])
            ->get();

        $faturas = collect();

        if ($mesAtual !== null) {
            foreach ($user->cartoes()->whereIn('tipo', ['credito', 'credito_debito'])->get() as $cartao) {
                $total = (float) $cartao->totalFatura($mesAtual, $ano);
                if (abs($total) < 0.01) {
                    continue;
                }
                $faturas->push((object) [
                    'data' => Carbon::create($ano, $mesAtual, min(
                        (int) ($cartao->dia_vencimento ?: 1),
                        Carbon::create($ano, $mesAtual, 1)->daysInMonth
                    )),
                    'descricao' => 'Fatura ' . $cartao->nome . ' ' . Carbon::create($ano, $mesAtual, 1)->translatedFormat('M/Y'),
                    'valor' => $total,
                    'categoria' => (object) ['nome' => 'Faturas de cartão'],
                    'subcategoria' => null,
                ]);
            }
        } else {
            \App\Models\Cartao::faturasUsuario($user, $inicio, $fim)
                ->filter(fn ($f) => $f->ano === $ano)
                ->each(function ($f) use (&$faturas) {
                    $total = (float) $f->total;
                    if (abs($total) < 0.01) {
                        return;
                    }
                    $faturas->push((object) [
                        'data' => $f->vencimento,
                        'descricao' => 'Fatura ' . $f->cartao->nome . ' ' . $f->vencimento->translatedFormat('M/Y'),
                        'valor' => $total,
                        'categoria' => (object) ['nome' => 'Faturas de cartão'],
                        'subcategoria' => null,
                    ]);
                });
        }

        $planos = $user->lancamentos()->with(['categoria', 'subcategoria'])
            ->whereNotNull('assinatura_id')
            ->when($mesAtual !== null ? 'mes' : 'ano', $ano, $mesAtual)
            ->orderBy('data')->orderBy('id')
            ->get()
            ->map(fn ($l) => (object) [
                'data' => $l->data,
                'descricao' => $l->descricao,
                'valor' => (float) $l->valor,
                'categoria' => $l->categoria,
                'subcategoria' => $l->subcategoria,
            ]);

        return [$base->concat($faturas)->concat($planos), $base, $faturas];
    }

    private function agruparPorCategoria($receitas, $despesas): array
    {
        $agrupadas = [];

        foreach ($receitas as $l) {
            $nome = $l->categoria?->nome ?? 'Sem categoria';
            $item = $l->subcategoria?->nome ?? $l->descricao;
            $agrupadas['receitas'][$nome][$item][$l->data->month] =
                (float) ($agrupadas['receitas'][$nome][$item][$l->data->month] ?? 0) + $l->valor;
        }

        foreach ($despesas as $l) {
            $nome = $l->categoria?->nome ?? 'Sem categoria';
            $item = $l->subcategoria?->nome ?? $l->descricao;
            $agrupadas['despesas'][$nome][$item][$l->data->month] =
                (float) ($agrupadas['despesas'][$nome][$item][$l->data->month] ?? 0) + $l->valor;
        }

        if (isset($agrupadas['receitas'])) {
            ksort($agrupadas['receitas']);
        }
        if (isset($agrupadas['despesas'])) {
            ksort($agrupadas['despesas']);
        }

        return $agrupadas;
    }
}
