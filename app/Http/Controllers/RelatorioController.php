<?php

namespace App\Http\Controllers;

use App\Models\Lancamento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RelatorioController extends Controller
{
    public function mensal(Request $request)
    {
        $user = auth()->user();
        $ano = (int) $request->input('ano', Carbon::now()->year);
        $mes = $request->input('mes', 'todos');
        $mes = $mes === 'todos' || $mes === '' ? 'todos' : (int) $mes;
        $mesAtual = $mes === 'todos' ? null : $mes;

        $receitas = $user->lancamentos()->where('tipo', 'receita')
            ->whereYear('data', $ano)
            ->when($mesAtual, fn ($q) => $q->whereMonth('data', $mesAtual))
            ->with('categoria', 'subcategoria')->get();
        $despesas = $user->lancamentos()->where('tipo', 'despesa')
            ->where('abate_saldo', true)
            ->whereYear('data', $ano)
            ->when($mesAtual, fn ($q) => $q->whereMonth('data', $mesAtual))
            ->with('categoria', 'subcategoria')->get();
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

        $totaisMesReceitas = [];
        $totaisMesDespesas = [];
        $totaisAno = [];
        foreach ($mesAtual ? [$mesAtual] : range(1, 12) as $m) {
            $totaisMesReceitas[$m] = $receitas->filter(fn ($l) => $l->data->month === $m)->sum('valor');
            $totaisMesDespesas[$m] = $despesas->filter(fn ($l) => $l->data->month === $m)->sum('valor');
        }
        $totaisAno['receitas'] = $receitas->sum('valor');
        $totaisAno['despesas'] = $despesas->sum('valor');
        $totaisAno['saldo'] = $totaisAno['receitas'] - $totaisAno['despesas'];

        $saldoMes = [];
        foreach ($mesAtual ? [$mesAtual] : range(1, 12) as $m) {
            $saldoMes[$m] = ($totaisMesReceitas[$m] ?? 0) - ($totaisMesDespesas[$m] ?? 0);
        }

        $cartoes = $user->cartoes()->orderBy('nome')->get();
        $gastosCartaoMes = [];
        foreach ($cartoes as $cartao) {
            $gastosCartaoMes[$cartao->id] = [];
            foreach ($mesAtual ? [$mesAtual] : range(1, 12) as $m) {
                $gastosCartaoMes[$cartao->id][$m] = (float) $cartao->lancamentos()
                    ->where('tipo', 'despesa')
                    ->where('cartao_debito', false)
                    ->whereYear('data', $ano)->whereMonth('data', $m)
                    ->sum('valor');
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
        $ano = (int) $request->input('ano', Carbon::now()->year);
        $mes = $request->input('mes', 'todos');
        $mes = $mes === 'todos' || $mes === '' ? 'todos' : (int) $mes;
        $mesAtual = $mes === 'todos' ? null : $mes;

        $receitas = $user->lancamentos()->where('tipo', 'receita')
            ->whereYear('data', $ano)
            ->when($mesAtual, fn ($q) => $q->whereMonth('data', $mesAtual))
            ->with('categoria', 'subcategoria')->get();
        $despesas = $user->lancamentos()->where('tipo', 'despesa')
            ->where('abate_saldo', true)
            ->whereYear('data', $ano)
            ->when($mesAtual, fn ($q) => $q->whereMonth('data', $mesAtual))
            ->with('categoria', 'subcategoria')->get();

        $agrupadas = $this->agruparPorCategoria($receitas, $despesas);

        $totaisMesReceitas = [];
        $totaisMesDespesas = [];
        foreach ($mesAtual ? [$mesAtual] : range(1, 12) as $m) {
            $totaisMesReceitas[$m] = $receitas->filter(fn ($l) => $l->data->month === $m)->sum('valor');
            $totaisMesDespesas[$m] = $despesas->filter(fn ($l) => $l->data->month === $m)->sum('valor');
        }
        $totaisAno = [
            'receitas' => $receitas->sum('valor'),
            'despesas' => $despesas->sum('valor'),
            'saldo' => $receitas->sum('valor') - $despesas->sum('valor'),
        ];

        $pdf = Pdf::loadView('relatorios.pdf', compact('ano', 'mes', 'mesAtual', 'agrupadas', 'totaisMesReceitas', 'totaisMesDespesas', 'totaisAno'));

        return $pdf->download("relatorio-financeiro-{$ano}.pdf");
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
