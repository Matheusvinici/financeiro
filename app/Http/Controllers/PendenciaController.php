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

        $comprasCartao = $user->lancamentos()
            ->with('cartao')
            ->where('tipo', 'despesa')
            ->where('forma_pagamento', 'cartao')
            ->where('cartao_debito', false)
            ->whereNotNull('cartao_id')
            ->when($mesAtual, fn ($q) => $q->noMes($ano, $mesAtual), fn ($q) => $q->whereYear('data', $ano))
            ->get()
            ->filter(fn ($l) => $l->cartao !== null);

        $faturasCartao = $comprasCartao
            ->groupBy(fn ($l) => $l->cartao_id . '|' . ($l->fatura_key ?: $l->data->format('Y-m')))
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

        $faturaPendentes = $mesAtual === null
            ? collect()
            : $faturasCartao->filter(fn ($f) => !$f['pago'])
                ->map(fn ($f) => (object) [
                    'is_fatura' => true,
                    'fatura_cartao' => $f['cartao'],
                    'fatura_total' => $f['total'],
                    'fatura_qtd' => $f['qtd'],
                    'fatura_mes' => $f['fatura_mes'],
                    'fatura_ano' => $f['fatura_ano'],
                    'data' => $f['vencimento'],
                ]);

        $pendentes = $pendencias->concat($faturaPendentes)
            ->sortBy(fn ($i) => $i->data ? $i->data->toDateString() : '9999-12-31')
            ->values();

        $totalPendente = $pendencias->sum('valor') + $faturaPendentes->sum('fatura_total');
        $totalNaoAbate = $pendencias->where('abate_saldo', false)->sum('valor');
        $totalUrgente = $pendencias->filter(fn ($l) => $l->data && $l->data->copy()->startOfDay()->lte($hoje->copy()->addDays(3)))->sum('valor')
            + $faturaPendentes->filter(fn ($f) => $f->data->copy()->startOfDay()->lte($hoje->copy()->addDays(3)))->sum('fatura_total');

        $totalPago = (float) $user->lancamentos()
            ->where('tipo', 'despesa')
            ->where('pago', true)
            ->where(fn ($q) => $q->whereNull('cartao_id')->orWhere('forma_pagamento', '!=', 'cartao')->orWhere('cartao_debito', true))
            ->when($mesAtual, fn ($q) => $q->noMes($ano, $mesAtual), fn ($q) => $q->whereYear('data', $ano))
            ->sum('valor');

        if ($mesAtual !== null) {
            $totalPago += $faturasCartao->where('pago', true)->sum('total');
        }

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
            'totalPendente', 'totalNaoAbate', 'totalUrgente', 'totalPago', 'mesesDisponiveis'
        ));
    }

    public function pagarFatura(Request $request, Cartao $cartao)
    {
        abort_unless($cartao->user_id === auth()->id(), 403);

        $mes = (int) $request->input('mes', now()->month);
        $ano = (int) $request->input('ano', now()->year);

        $cartao->update([
            'fatura_paga_mes' => $mes,
            'fatura_paga_ano' => $ano,
        ]);

        return back()->with('success', "Fatura de {$cartao->nome} marcada como paga.");
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
