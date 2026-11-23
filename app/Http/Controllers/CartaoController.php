<?php

namespace App\Http\Controllers;

use App\Models\Assinatura;
use App\Models\Cartao;
use App\Models\Lancamento;
use App\Services\ConsolidacaoFinanceira;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CartaoController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $hoje = Carbon::now();

        ConsolidacaoFinanceira::sincronizarUsuario($user);
        Assinatura::sincronizar($user);

        $mes = $request->input('mes');
        $mes = $mes === 'todos' ? 'todos' : (int) ($mes ?: $hoje->month);
        $ano = (int) $request->input('ano', $hoje->year);

        $periodo = $mes === 'todos' ? 'ano' : 'mes';
        $mesAtual = $mes === 'todos' ? null : $mes;

        $cartoes = $user->cartoes()->withCount('lancamentos')->orderBy('nome')->get();

        $semAjuste = fn ($q) => $q->where(fn ($w) => $w->where('ajuste', false)->orWhereNull('ajuste'));

        $totais = $cartoes->map(function ($cartao) use ($ano, $mesAtual, $periodo, $semAjuste) {
            // Ajuste de fatura é correção, não gasto: fica só na fatura, fora do "gasto no período".
            $gastoPeriodo = (float) $cartao->lancamentos()
                ->where('tipo', 'despesa')->where('cartao_debito', false)->semAssinaturasFuturas()->tap($semAjuste)->quando($periodo, $ano, $mesAtual)->sum('valor');

            $parcelasPeriodo = (float) $cartao->lancamentos()
                ->where('tipo', 'despesa')->where('qtd_parcelas', '>', 1)
                ->where('cartao_debito', false)
                ->semAssinaturasFuturas()
                ->tap($semAjuste)
                ->quando($periodo, $ano, $mesAtual)->sum('valor');

            $avistaPeriodo = $gastoPeriodo - $parcelasPeriodo;

            $aberta = $cartao->proximaFaturaAberta();
            if ($aberta) {
                $faturaAtualLabel = Carbon::create($aberta['ano'], $aberta['mes'], 1)->translatedFormat('M/Y');
                $faturaAtualTotal = (float) $cartao->totalFatura($aberta['mes'], $aberta['ano']);
            } else {
                $faturaAtualLabel = null;
                $faturaAtualTotal = 0;
            }

            $isCredito = in_array($cartao->tipo, ['credito', 'credito_debito'], true);
            if ($isCredito && $mesAtual !== null) {
                $faturaMesTotal = (float) $cartao->totalFatura($mesAtual, $ano);
                $faturaMesLabel = Carbon::create($ano, $mesAtual, 1)->translatedFormat('M/Y');
                $destaque = $faturaMesTotal;
                $destaqueLabel = 'Fatura de ' . $faturaMesLabel;
            } elseif ($isCredito) {
                $faturaMesTotal = 0;
                $faturaMesLabel = null;
                $destaque = $faturaAtualTotal;
                $destaqueLabel = $faturaAtualLabel !== null ? 'Fatura atual (' . $faturaAtualLabel . ')' : null;
            } else {
                $faturaMesTotal = 0;
                $faturaMesLabel = null;
                $destaque = $gastoPeriodo;
                $destaqueLabel = 'Gasto no período';
            }

            return [
                'cartao' => $cartao,
                'gasto_periodo' => $gastoPeriodo,
                'parcelas_periodo' => $parcelasPeriodo,
                'avista_periodo' => $avistaPeriodo,
                'utilizacao_pct' => $cartao->limite > 0 ? round(($gastoPeriodo / $cartao->limite) * 100, 1) : 0,
                'fatura_atual_label' => $faturaAtualLabel,
                'fatura_atual_total' => $faturaAtualTotal,
                'fatura_mes_label' => $faturaMesLabel,
                'fatura_mes_total' => $faturaMesTotal,
                'destaque' => $destaque,
                'destaque_label' => $destaqueLabel,
            ];
        });

        $totalGeral = $totais->sum('destaque');

        $assinaturas = $user->assinaturas()->with(['cartao', 'categoria'])
            ->orderByDesc('ativo')->orderBy('nome')->get();
        $totalAssinaturas = (float) $assinaturas->where('ativo', true)->sum('valor');

        $ajustes = $user->lancamentos()
            ->with('cartao')
            ->where('ajuste', true)
            ->orderByDesc('data')->limit(15)->get();

        // Faturas para ajuste: somente o mês atual e os próximos 12 meses,
        // pulando as já pagas — meses passados não são mais ajustáveis.
        $faturasTotais = Cartao::faturasUsuario(
            $user,
            $hoje->copy()->subMonths(36)->startOfMonth(),
            $hoje->copy()->addMonths(36)->endOfMonth()
        )->keyBy(fn ($f) => $f->cartao_id . '|' . $f->chave);

        $faturasAjuste = collect();
        $user->cartoes()->where('ativo', true)->get()->each(function ($cartao) use (&$faturasAjuste, $faturasTotais, $hoje) {
            if (!in_array($cartao->tipo, ['credito', 'credito_debito'], true)) {
                return;
            }

            $inicio = $hoje->copy()->startOfMonth();
            $fim = $hoje->copy()->addMonths(11)->startOfMonth();

            for ($d = $inicio->copy(); $d->lte($fim); $d->addMonth()) {
                $mes = $d->month;
                $ano = $d->year;

                if ($cartao->faturaPaga($mes, $ano)) {
                    continue;
                }

                $chave = sprintf('%04d-%02d', $ano, $mes);
                $total = $faturasTotais->get($cartao->id . '|' . $chave);

                $faturasAjuste->push((object) [
                    'cartao' => $cartao,
                    'cartao_id' => $cartao->id,
                    'mes' => $mes,
                    'ano' => $ano,
                    'chave' => $chave,
                    'vencimento' => Carbon::create($ano, $mes, min(
                        (int) ($cartao->dia_vencimento ?: 1),
                        Carbon::create($ano, $mes, 1)->daysInMonth
                    )),
                    'total' => (float) ($total?->total ?? 0),
                    'qtd' => (int) ($total?->qtd ?? 0),
                    'pago' => false,
                ]);
            }
        });

        $faturasAjuste = $faturasAjuste
            ->sortBy(fn ($f) => $f->vencimento)
            ->groupBy(fn ($f) => $f->cartao->nome)
            ->sortKeys();

        // Projeção de parcelas por cartão nos próximos 12 meses
        $inicio = $hoje->copy()->startOfMonth();
        $fim = $inicio->copy()->addMonths(11)->endOfMonth();

        $parcelasFuturas = $user->lancamentos()
            ->where('tipo', 'despesa')
            ->whereNotNull('cartao_id')
            ->where('cartao_debito', false)
            ->where(fn ($q) => $q->where('ajuste', false)->orWhereNull('ajuste'))
            ->whereBetween('data', [$inicio, $fim])
            ->selectRaw("cartao_id, COALESCE(fatura_key, DATE_FORMAT(data, '%Y-%m')) as chave, SUM(valor) as total")
            ->groupBy('cartao_id', 'chave')->get();

        $mesesProjecao = [];
        for ($i = 0; $i < 12; $i++) {
            $d = $inicio->copy()->addMonths($i);
            $mesesProjecao[] = [
                'ano' => $d->year,
                'mes' => $d->month,
                'rotulo' => $d->translatedFormat('M/y'),
            ];
        }

        $projecao = [];
        foreach ($cartoes as $cartao) {
            $projecao[$cartao->id] = [];
            foreach ($mesesProjecao as $p) {
                $projecao[$cartao->id][$p['ano'] . '-' . $p['mes']] = 0;
            }
        }
        foreach ($parcelasFuturas as $p) {
            $projecao[$p->cartao_id][$p->chave] = (float) $p->total;
        }

        $mesesDisponiveis = $user->lancamentos()
            ->selectRaw('YEAR(data) as ano, MONTH(data) as mes')
            ->distinct()->get()
            ->map(fn ($m) => ['ano' => (int) $m->ano, 'mes' => (int) $m->mes])
            ->push(['ano' => $hoje->year, 'mes' => $hoje->month])
            ->unique(fn ($m) => $m['ano'] . '-' . $m['mes'])
            ->sortByDesc(fn ($m) => $m['ano'] * 12 + $m['mes'])
            ->values();

        // Itens que compõem a fatura de cada cartão no período: compras +
        // planos (assinaturas entram na fatura na data de cobrança e, passada
        // a data, aparecem aqui junto com o resto). Igual ao que a dashboard soma.
        $detalhesFatura = [];
        foreach ($cartoes as $cartao) {
            if ($mesAtual !== null) {
                $chave = sprintf('%04d-%02d', $ano, $mesAtual);
                $rotulo = Carbon::create($ano, $mesAtual, 1)->translatedFormat('F/Y');
            } else {
                $aberta = $cartao->proximaFaturaAberta();
                if (!$aberta) {
                    continue;
                }
                $chave = sprintf('%04d-%02d', $aberta['ano'], $aberta['mes']);
                $rotulo = Carbon::create($aberta['ano'], $aberta['mes'], 1)->translatedFormat('F/Y');
            }
            $itens = $cartao->lancamentos()
                ->with(['categoria', 'assinatura'])
                ->where('tipo', 'despesa')
                ->where('cartao_debito', false)
                ->get()->filter(fn ($l) => $l->faturaChave() === $chave)
                ->sortBy('data')->values();
            $detalhesFatura[$cartao->id] = ['chave' => $chave, 'rotulo' => $rotulo, 'itens' => $itens];
        }

        return view('cartoes.index', compact(
            'totais', 'totalGeral', 'mes', 'ano', 'mesesProjecao', 'projecao', 'mesesDisponiveis',
            'assinaturas', 'totalAssinaturas', 'ajustes', 'faturasAjuste', 'detalhesFatura'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'nome' => ['required', 'string', 'max:60'],
            'tipo' => ['required', 'in:credito,debito,credito_debito'],
            'bandeira' => ['nullable', 'string', 'max:40'],
            'limite' => ['nullable', 'numeric', 'min:0'],
            'dia_fechamento' => ['nullable', 'integer', 'min:1', 'max:31'],
            'dia_vencimento' => ['nullable', 'integer', 'min:1', 'max:31'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        auth()->user()->cartoes()->create($data + ['ativo' => $request->boolean('ativo', true)]);

        return back()->with('success', 'Cartão cadastrado.');
    }

    public function update(Request $request, Cartao $cartao)
    {
        abort_unless($cartao->user_id === auth()->id(), 403);

        $data = $this->validate($request, [
            'nome' => ['required', 'string', 'max:60'],
            'tipo' => ['required', 'in:credito,debito,credito_debito'],
            'bandeira' => ['nullable', 'string', 'max:40'],
            'limite' => ['nullable', 'numeric', 'min:0'],
            'dia_fechamento' => ['nullable', 'integer', 'min:1', 'max:31'],
            'dia_vencimento' => ['nullable', 'integer', 'min:1', 'max:31'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $cartao->update($data + ['ativo' => $request->boolean('ativo', true)]);

        return back()->with('success', 'Cartão atualizado.');
    }

    public function storeAjuste(Request $request)
    {
        $user = auth()->user();

        $data = $this->validate($request, [
            'fatura' => ['required', 'regex:/^\d+\|\d{4}-\d{2}$/'],
            'valor' => ['required'],
        ]);

        [$cartaoId, $faturaKey] = explode('|', $data['fatura']);
        [$ano, $mes] = array_map('intval', explode('-', $faturaKey));

        $cartao = $user->cartoes()->findOrFail($cartaoId);

        if ($cartao->faturaPaga($mes, $ano)) {
            return back()->with('error', "A fatura de {$cartao->nome} ({$mes}/{$ano}) já está marcada como paga e não pode ser ajustada.");
        }

        $novoValor = $this->converterValor($data['valor']);

        if ($novoValor === null || $novoValor < 0) {
            return back()->with('error', 'Informe um valor de fatura válido.');
        }

        // Um ajuste por fatura: apaga ajustes anteriores e recalcula sobre as
        // compras reais, senão vários ajustes se empilham e o total confunde.
        $user->lancamentos()
            ->where('cartao_id', $cartao->id)
            ->where('ajuste', true)
            ->where('fatura_key', $faturaKey)
            ->get()->each->delete();

        $comprasTotal = round((float) $user->lancamentos()
            ->where('cartao_id', $cartao->id)
            ->where('tipo', 'despesa')
            ->where('cartao_debito', false)
            ->where(fn ($q) => $q->where('ajuste', false)->orWhereNull('ajuste'))
            ->get()->filter(fn ($l) => $l->faturaChave() === $faturaKey)->sum('valor'), 2);

        $totalAtual = $comprasTotal;
        $novoValor = round($novoValor, 2);
        $diferenca = round($novoValor - $totalAtual, 2);

        if (abs($diferenca) < 0.01) {
            return back()->with('success', 'Ajustes antigos removidos: a fatura de ' . Carbon::create($ano, $mes, 1)->translatedFormat('M/Y')
                . " já estava no valor informado (R$ " . number_format($totalAtual, 2, ',', '.') . ').');
        }

        $diaVenc = (int) $cartao->dia_vencimento ?: 1;
        $dataVencimento = Carbon::create($ano, $mes, min($diaVenc, Carbon::create($ano, $mes, 1)->daysInMonth));

        $user->lancamentos()->create([
            'data' => $dataVencimento,
            'descricao' => 'Ajuste de fatura',
            'valor' => $diferenca,
            'tipo' => 'despesa',
            'forma_pagamento' => 'cartao',
            'cartao_id' => $cartao->id,
            'recorrente' => false,
            'qtd_parcelas' => 1,
            'parcela_atual' => 1,
            'pago' => false,
            'abate_saldo' => true,
            'ajuste' => true,
            'fatura_key' => $faturaKey,
            'observacao' => 'Fatura ajustada de R$ ' . number_format($totalAtual, 2, ',', '.')
                . ' para R$ ' . number_format($novoValor, 2, ',', '.'),
        ]);

        return back()->with('success', 'Ajuste registrado na fatura de ' . $dataVencimento->translatedFormat('M/Y')
            . ' para R$ ' . number_format($novoValor, 2, ',', '.') . '.');
    }

    private function converterValor($valor): ?float
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        $s = (string) $valor;
        $s = preg_replace('/[^\d.,\-]/', '', $s);
        if ($s === '' || $s === '-') {
            return null;
        }

        $negativo = str_starts_with($s, '-');
        $s = ltrim($s, '-');
        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }

        return round((float) $s, 2) * ($negativo ? -1 : 1);
    }

    public function destroyAjuste(Lancamento $lancamento)
    {
        abort_unless($lancamento->user_id === auth()->id() && $lancamento->ajuste, 403);

        $lancamento->delete();

        return back()->with('success', 'Ajuste removido.');
    }

    public function destroy(Cartao $cartao)
    {
        abort_unless($cartao->user_id === auth()->id(), 403);
        $cartao->delete();

        return back()->with('success', 'Cartão removido.');
    }
}
