<?php

namespace App\Http\Controllers;

use App\Models\Cartao;
use App\Models\Lancamento;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PendenciaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $hoje = Carbon::now();

        $mes = $request->input('mes');
        $mes = $mes === 'todos' ? 'todos' : (int) ($mes ?: $hoje->month);
        $ano = (int) $request->input('ano', $hoje->year);

        $mesAtual = $mes === 'todos' ? null : $mes;

        $query = $user->lancamentos()
            ->with(['categoria', 'subcategoria', 'cartao'])
            ->where('tipo', 'despesa')
            ->where(fn ($q) => $q->whereNull('cartao_id')->orWhere('forma_pagamento', '!=', 'cartao')->orWhere('cartao_debito', true));

        if ($mesAtual) {
            $query->noMes($ano, $mesAtual);
        } else {
            $query->whereYear('data', $ano);
        }

        $pendencias = $query->where('pago', false)
            ->orderBy('data')->orderBy('id')
            ->get();

        $pagas = $user->lancamentos()
            ->with(['categoria'])
            ->where('tipo', 'despesa')
            ->where('pago', true)
            ->where(fn ($q) => $q->whereNull('cartao_id')->orWhere('forma_pagamento', '!=', 'cartao')->orWhere('cartao_debito', true))
            ->when($mesAtual, fn ($q) => $q->noMes($ano, $mesAtual), fn ($q) => $q->whereYear('data', $ano))
            ->orderByDesc('data')
            ->limit(50)
            ->get();

        // Compras do cartão (crédito) — janela ampla para agrupar faturas por vencimento
        $inicioJanela = $hoje->copy()->subMonths(24)->startOfMonth();
        $fimJanela = $hoje->copy()->addMonths(11)->endOfMonth();

        $comprasCartao = $user->lancamentos()
            ->with('cartao')
            ->where('tipo', 'despesa')
            ->where('forma_pagamento', 'cartao')
            ->where('cartao_debito', false)
            ->whereNotNull('cartao_id')
            ->whereBetween('data', [$inicioJanela, $fimJanela])
            ->get()
            ->filter(fn ($l) => $l->cartao !== null);

        // Agrupar faturas pela chave da fatura (fatura_key ou ciclo do cartão)
        $faturasTodas = $comprasCartao
            ->groupBy(fn ($l) => $l->cartao_id . '|' . $l->faturaChave())
            ->map(function ($g, $key) {
                [$cId, $chave] = explode('|', $key);
                [$fAno, $fMes] = array_map('intval', explode('-', $chave));

                $vencimento = Carbon::create($fAno, $fMes, min(
                    (int) ($g->first()->cartao->dia_vencimento ?: 1),
                    Carbon::create($fAno, $fMes, 1)->daysInMonth
                ));

                return [
                    'cartao' => $g->first()->cartao,
                    'fatura_ano' => $fAno,
                    'fatura_mes' => $fMes,
                    'vencimento' => $vencimento,
                    'total' => round($g->sum('valor'), 2),
                    'pago' => $g->first()->cartao->faturaPaga($fMes, $fAno),
                    'qtd' => $g->count(),
                ];
            })
            ->values();

        // Faturas em aberto: somente as que vencem dentro do período selecionado
        $fimPeriodo = $mesAtual === null
            ? Carbon::create($ano, 12, 31)
            : Carbon::create($ano, $mesAtual, 1)->endOfMonth();

        $inicioPeriodo = $mesAtual === null
            ? Carbon::create($ano, 1, 1)
            : Carbon::create($ano, $mesAtual, 1)->startOfDay();

        // Só é atrasada uma conta que já venceu de verdade; ao abrir um mês futuro
        // (ex.: setembro com hoje em agosto), contas ainda não vencidas não podem
        // virar "dívida".
        $limiteAtrasadas = $hoje->copy()->startOfDay()->lt($inicioPeriodo)
            ? $hoje->copy()->startOfDay()
            : $inicioPeriodo;

        $faturasCartao = $mesAtual === null
            ? $faturasTodas->filter(fn ($f) => $f['fatura_ano'] === $ano)
            : $faturasTodas->filter(
                fn ($f) => $f['vencimento']->copy()->startOfDay()->lte($fimPeriodo->copy()->endOfDay())
            )->values();

        // Faturas pendentes do período em aberto (as de antes do período entram em "atrasadas")
        $faturaPendentes = $faturasCartao->filter(
            fn ($f) => !$f['pago'] && $f['vencimento']->copy()->startOfDay()->gte($inicioPeriodo->copy()->startOfDay())
        )
            ->map(fn ($f) => (object) [
                'is_fatura' => true,
                'fatura_cartao' => $f['cartao'],
                'fatura_total' => $f['total'],
                'fatura_qtd' => $f['qtd'],
                'fatura_mes' => $f['fatura_mes'],
                'fatura_ano' => $f['fatura_ano'],
                'data' => $f['vencimento'],
                'atrasada' => false,
            ]);

        // Faturas de meses ANTERIORES que continuam sem pagamento (atrasadas)
        $faturasAtrasadas = $faturasTodas->filter(function ($f) use ($inicioPeriodo, $limiteAtrasadas) {
            return !$f['pago']
                && $f['vencimento']->copy()->startOfDay()->lt($limiteAtrasadas)
                && $f['vencimento']->copy()->startOfDay()->gte($inicioPeriodo->copy()->subMonths(24));
        })->map(function ($f) use ($hoje) {
            return (object) [
                'is_fatura' => true,
                'fatura_cartao' => $f['cartao'],
                'fatura_total' => $f['total'],
                'fatura_qtd' => $f['qtd'],
                'fatura_mes' => $f['fatura_mes'],
                'fatura_ano' => $f['fatura_ano'],
                'data' => $f['vencimento'],
                'atrasada' => true,
                'dias_atraso' => (int) $f['vencimento']->copy()->startOfDay()->diffInDays($hoje->copy()->startOfDay()),
            ];
        })->values();

        // Despesas individuais de meses ANTERIORES que continuam pendentes (atrasadas)
        $despesasAtrasadas = collect();
        if ($mesAtual !== null) {
            $despesasAtrasadas = $user->lancamentos()
                ->with(['categoria', 'subcategoria'])
                ->where('tipo', 'despesa')
                ->where('pago', false)
                ->where(fn ($q) => $q->whereNull('cartao_id')->orWhere('forma_pagamento', '!=', 'cartao')->orWhere('cartao_debito', true))
                ->where('data', '<', $limiteAtrasadas)
                ->orderBy('data')
                ->get()
                ->map(function ($l) use ($hoje) {
                    $l->atrasada = true;
                    $l->dias_atraso = (int) $l->data->copy()->startOfDay()->diffInDays($hoje->copy()->startOfDay());
                    return $l;
                });
        }

        $pendentes = $pendencias->concat($faturaPendentes)->concat($faturasAtrasadas)->concat($despesasAtrasadas)
            ->sortBy(fn ($i) => $i->data ? $i->data->toDateString() : '9999-12-31')
            ->values();

        $totalPendente = $pendencias->sum('valor') + $faturaPendentes->sum('fatura_total') + $faturasAtrasadas->sum('fatura_total') + $despesasAtrasadas->sum('valor');
        $totalNaoAbate = $pendencias->where('abate_saldo', false)->sum('valor') + $despesasAtrasadas->where('abate_saldo', false)->sum('valor');
        $totalAbate = $totalPendente - $totalNaoAbate;
        $totalUrgente = $pendencias->filter(fn ($l) => $l->data && $l->data->copy()->startOfDay()->lte($hoje->copy()->addDays(3)))->sum('valor')
            + $faturaPendentes->filter(fn ($f) => $f->data->copy()->startOfDay()->lte($hoje->copy()->addDays(3)))->sum('fatura_total')
            + $faturasAtrasadas->filter(fn ($f) => $f->data->copy()->startOfDay()->lte($hoje->copy()->addDays(3)))->sum('fatura_total')
            + $despesasAtrasadas->sum('valor');

        $totalPago = (float) $user->lancamentos()
            ->where('tipo', 'despesa')
            ->where('pago', true)
            ->where(fn ($q) => $q->whereNull('cartao_id')->orWhere('forma_pagamento', '!=', 'cartao')->orWhere('cartao_debito', true))
            ->when($mesAtual, fn ($q) => $q->noMes($ano, $mesAtual), fn ($q) => $q->whereYear('data', $ano))
            ->sum('valor');

        $totalPago += $faturasCartao->where('pago', true)->sum('total');

        $mesesDisponiveis = $user->lancamentos()
            ->selectRaw('YEAR(data) as ano, MONTH(data) as mes')
            ->distinct()->get()
            ->map(fn ($m) => ['ano' => (int) $m->ano, 'mes' => (int) $m->mes])
            ->push(['ano' => $hoje->year, 'mes' => $hoje->month])
            ->unique(fn ($m) => $m['ano'] . '-' . $m['mes'])
            ->sortByDesc(fn ($m) => $m['ano'] * 12 + $m['mes'])
            ->values();

        return view('pendencias.index', compact(
            'pendencias', 'pagas', 'faturasCartao', 'pendentes', 'mes', 'ano', 'mesAtual', 'hoje',
            'totalPendente', 'totalNaoAbate', 'totalAbate', 'totalUrgente', 'totalPago', 'mesesDisponiveis'
        ));
    }

    public function pagarFatura(Request $request, Cartao $cartao)
    {
        abort_unless($cartao->user_id === auth()->id(), 403);

        $mes = (int) $request->input('mes', now()->month);
        $ano = (int) $request->input('ano', now()->year);

        $cartao->marcarFaturaPaga($mes, $ano);

        return back()->with('success', "Fatura de {$cartao->nome} ({$mes}/{$ano}) marcada como paga.");
    }

    public function pagar(Lancamento $lancamento)
    {
        abort_unless($lancamento->user_id === auth()->id(), 403);

        $lancamento->update(['pago' => true]);

        return back()->with('success', 'Pagamento registrado.');
    }

    public function desfazerPagamento(Lancamento $lancamento)
    {
        abort_unless($lancamento->user_id === auth()->id(), 403);

        $lancamento->update(['pago' => false]);

        return back()->with('success', 'Lançamento voltou para as pendências.');
    }

    public function alternarAbate(Lancamento $lancamento)
    {
        abort_unless($lancamento->user_id === auth()->id(), 403);

        $novo = !$lancamento->abate_saldo;
        $serie = $lancamento->serieRelacionada();

        foreach ($serie as $item) {
            $item->update(['abate_saldo' => $novo]);
        }

        $qtd = $serie->count();

        return back()->with('success', $qtd > 1
            ? ($novo ? "Conta passa a abater do saldo em todos os {$qtd} meses." : "Conta passa a não abater do saldo em nenhum dos {$qtd} meses.")
            : ($novo ? 'Agora abate do saldo.' : 'Agora não abate do saldo.'));
    }
}
