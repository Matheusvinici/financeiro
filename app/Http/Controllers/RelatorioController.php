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

        $receitas = $user->lancamentos()->where('tipo', 'receita')
            ->whereYear('data', $ano)->with('categoria', 'subcategoria')->get();
        $despesas = $user->lancamentos()->where('tipo', 'despesa')
            ->whereYear('data', $ano)->with('categoria', 'subcategoria')->get();

        $agrupadas = $this->agruparPorCategoria($receitas, $despesas);

        $totaisMesReceitas = [];
        $totaisMesDespesas = [];
        $totaisAno = [];
        for ($m = 1; $m <= 12; $m++) {
            $totaisMesReceitas[$m] = $receitas->filter(fn ($l) => $l->data->month === $m)->sum('valor');
            $totaisMesDespesas[$m] = $despesas->filter(fn ($l) => $l->data->month === $m)->sum('valor');
        }
        $totaisAno['receitas'] = $receitas->sum('valor');
        $totaisAno['despesas'] = $despesas->sum('valor');
        $totaisAno['saldo'] = $totaisAno['receitas'] - $totaisAno['despesas'];

        $saldoMes = [];
        for ($m = 1; $m <= 12; $m++) {
            $saldoMes[$m] = $totaisMesReceitas[$m] - $totaisMesDespesas[$m];
        }

        $cartoes = $user->cartoes()->orderBy('nome')->get();
        $gastosCartaoMes = [];
        foreach ($cartoes as $cartao) {
            $gastosCartaoMes[$cartao->id] = [];
            for ($m = 1; $m <= 12; $m++) {
                $gastosCartaoMes[$cartao->id][$m] = (float) $cartao->lancamentos()
                    ->where('tipo', 'despesa')->whereYear('data', $ano)->whereMonth('data', $m)
                    ->sum('valor');
            }
        }

        return view('relatorios.mensal', compact(
            'ano', 'agrupadas', 'totaisMesReceitas', 'totaisMesDespesas',
            'saldoMes', 'totaisAno', 'cartoes', 'gastosCartaoMes'
        ));
    }

    public function exportarPdf(Request $request)
    {
        $user = auth()->user();
        $ano = (int) $request->input('ano', Carbon::now()->year);

        $receitas = $user->lancamentos()->where('tipo', 'receita')
            ->whereYear('data', $ano)->with('categoria', 'subcategoria')->get();
        $despesas = $user->lancamentos()->where('tipo', 'despesa')
            ->whereYear('data', $ano)->with('categoria', 'subcategoria')->get();

        $agrupadas = $this->agruparPorCategoria($receitas, $despesas);

        $totaisMesReceitas = [];
        $totaisMesDespesas = [];
        for ($m = 1; $m <= 12; $m++) {
            $totaisMesReceitas[$m] = $receitas->filter(fn ($l) => $l->data->month === $m)->sum('valor');
            $totaisMesDespesas[$m] = $despesas->filter(fn ($l) => $l->data->month === $m)->sum('valor');
        }
        $totaisAno = [
            'receitas' => $receitas->sum('valor'),
            'despesas' => $despesas->sum('valor'),
            'saldo' => $receitas->sum('valor') - $despesas->sum('valor'),
        ];

        $pdf = Pdf::loadView('relatorios.pdf', compact('ano', 'agrupadas', 'totaisMesReceitas', 'totaisMesDespesas', 'totaisAno'));

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

        ksort($agrupadas['receitas'] ?? []);
        ksort($agrupadas['despesas'] ?? []);

        return $agrupadas;
    }
}
