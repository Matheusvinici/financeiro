<?php

namespace App\Http\Controllers;

use App\Models\Assinatura;
use App\Models\Cartao;
use App\Models\Lancamento;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CartaoController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $hoje = Carbon::now();

        Assinatura::sincronizar($user);

        $mes = $request->input('mes');
        $mes = $mes === 'todos' ? 'todos' : (int) ($mes ?: $hoje->month);
        $ano = (int) $request->input('ano', $hoje->year);

        $periodo = $mes === 'todos' ? 'ano' : 'mes';
        $mesAtual = $mes === 'todos' ? null : $mes;

        $cartoes = $user->cartoes()->withCount('lancamentos')->orderBy('nome')->get();

        $totais = $cartoes->map(function ($cartao) use ($ano, $mesAtual, $periodo) {
            $gastoPeriodo = (float) $cartao->lancamentos()
                ->where('tipo', 'despesa')->where('cartao_debito', false)->semAssinaturasFuturas()->quando($periodo, $ano, $mesAtual)->sum('valor');

            $parcelasPeriodo = (float) $cartao->lancamentos()
                ->where('tipo', 'despesa')->where('qtd_parcelas', '>', 1)
                ->where('cartao_debito', false)
                ->semAssinaturasFuturas()
                ->quando($periodo, $ano, $mesAtual)->sum('valor');

            $avistaPeriodo = $gastoPeriodo - $parcelasPeriodo;

            return [
                'cartao' => $cartao,
                'gasto_periodo' => $gastoPeriodo,
                'parcelas_periodo' => $parcelasPeriodo,
                'avista_periodo' => $avistaPeriodo,
                'utilizacao_pct' => $cartao->limite > 0 ? round(($gastoPeriodo / $cartao->limite) * 100, 1) : 0,
            ];
        });

        $totalGeral = $totais->sum('gasto_periodo');

        $assinaturas = $user->assinaturas()->with(['cartao', 'categoria'])
            ->orderByDesc('ativo')->orderBy('nome')->get();
        $totalAssinaturas = (float) $assinaturas->where('ativo', true)->sum('valor');

        $ajustes = $user->lancamentos()
            ->with('cartao')
            ->where('ajuste', true)
            ->orderByDesc('data')->limit(15)->get();

        // Projeção de parcelas por cartão nos próximos 12 meses
        $inicio = $hoje->copy()->startOfMonth();
        $fim = $inicio->copy()->addMonths(11)->endOfMonth();

        $parcelasFuturas = $user->lancamentos()
            ->where('tipo', 'despesa')
            ->whereNotNull('cartao_id')
            ->where('cartao_debito', false)
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

        return view('cartoes.index', compact(
            'totais', 'totalGeral', 'mes', 'ano', 'mesesProjecao', 'projecao', 'mesesDisponiveis',
            'assinaturas', 'totalAssinaturas', 'ajustes'
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
            'cartao_id' => ['required', 'exists:cartoes,id'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
            'ano' => ['required', 'integer', 'min:2000', 'max:2100'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'operacao' => ['required', 'in:adicionar,reduzir'],
            'motivo' => ['nullable', 'string', 'max:150'],
        ]);

        $cartao = $user->cartoes()->findOrFail($data['cartao_id']);

        $faturaKey = sprintf('%04d-%02d', $data['ano'], $data['mes']);
        $diaVenc = (int) $cartao->dia_vencimento ?: 1;
        $dataVencimento = Carbon::create($data['ano'], $data['mes'], min($diaVenc, Carbon::create($data['ano'], $data['mes'], 1)->daysInMonth));

        $user->lancamentos()->create([
            'data' => $dataVencimento,
            'descricao' => ($data['motivo'] ?? null) ? "Ajuste de fatura — {$data['motivo']}" : 'Ajuste de fatura',
            'valor' => $data['operacao'] === 'reduzir' ? -$data['valor'] : $data['valor'],
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
            'observacao' => $data['motivo'] ?? null,
        ]);

        return back()->with('success', 'Ajuste registrado na fatura de ' . $dataVencimento->translatedFormat('M/Y') . '.');
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
