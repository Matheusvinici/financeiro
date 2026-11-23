<?php

namespace App\Livewire;

use App\Models\Assinatura;
use App\Models\ContaPagar;
use App\Models\ResumoMensal;
use App\Services\ConsolidacaoFinanceira;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public $mes;

    public $ano;

    public function mount(): void
    {
        $hoje = Carbon::now();

        $mesQuery = request()->query('mes', $hoje->month);
        $this->mes = $mesQuery === 'todos' ? 'todos' : (int) $mesQuery;
        $this->ano = (int) request()->query('ano', $hoje->year);
    }

    public function updatedMes(): void
    {
        $this->dispatch('graficos-atualizados');
    }

    public function updatedAno(): void
    {
        $this->dispatch('graficos-atualizados');
    }

    public function resetPeriodo(): void
    {
        $hoje = Carbon::now();
        $this->mes = $hoje->month;
        $this->ano = $hoje->year;
        $this->dispatch('graficos-atualizados');
    }

    public function render()
    {
        $user = auth()->user();
        $hoje = Carbon::now();

        ConsolidacaoFinanceira::sincronizarUsuario($user);
        Assinatura::sincronizar($user);

        $mes = $this->mes === 'todos' ? 'todos' : (int) ($this->mes ?: $hoje->month);
        $ano = (int) $this->ano;

        $periodo = $mes === 'todos' ? 'ano' : 'mes';
        $mesAtual = $mes === 'todos' ? null : $mes;

        $resumos = ResumoMensal::where('user_id', $user->id)
            ->where('ano', $ano)
            ->get()
            ->keyBy('mes');
        $receitasMes = $mesAtual !== null
            ? (float) ($resumos[$mesAtual]->receitas_total ?? 0)
            : (float) $resumos->sum('receitas_total');
        $despesasMes = $mesAtual !== null
            ? (float) ($resumos[$mesAtual]->despesas_total ?? 0)
            : (float) $resumos->sum('despesas_total');
        $saldoMes = $receitasMes - $despesasMes;

        if ($mes === 'todos') {
            $periodoAnterior = Carbon::create($ano - 1, 1, 1);
            $periodoAnt = 'ano';
        } else {
            $periodoAnterior = Carbon::create($ano, $mes, 1)->subMonth();
            $periodoAnt = 'mes';
        }

        $receitasMesAnterior = (float) (ResumoMensal::where('user_id', $user->id)
            ->where('ano', $periodoAnterior->year)->where('mes', $periodoAnterior->month)
            ->value('receitas_total') ?? 0);
        $despesasMesAnterior = (float) (ResumoMensal::where('user_id', $user->id)
            ->where('ano', $periodoAnterior->year)->where('mes', $periodoAnterior->month)
            ->value('despesas_total') ?? 0);
        $saldoMesAnterior = $receitasMesAnterior - $despesasMesAnterior;

        $saldoAno = (float) $user->lancamentos()->whereYear('data', $ano)
            ->get()->sum(fn ($l) => $l->tipo === 'receita' ? $l->valor : ($l->abate_saldo ? -$l->valor : 0));

        $contasAberto = ContaPagar::where('user_id', $user->id)
            ->where('status', '!=', 'pago')->get();
        $totalContasAberto = $contasAberto->sum('valor_restante');

        $receitasLista = $user->lancamentos()->with('categoria')
            ->where('tipo', 'receita')->quando($periodo, $ano, $mesAtual)
            ->orderByDesc('data')->orderByDesc('id')
            ->get(['id', 'data', 'descricao', 'valor', 'categoria_id']);

        $despesasLista = $this->despesasDoPeriodo($user, $periodo, $ano, $mesAtual);

        $mesesDisponiveis = $user->lancamentos()
            ->selectRaw('YEAR(data) as ano, MONTH(data) as mes')
            ->distinct()->get()
            ->map(fn ($m) => ['ano' => (int) $m->ano, 'mes' => (int) $m->mes])
            ->push(['ano' => $hoje->year, 'mes' => $hoje->month])
            ->unique(fn ($m) => $m['ano'] . '-' . $m['mes'])
            ->sortByDesc(fn ($m) => $m['ano'] * 12 + $m['mes'])
            ->values();

        $alertas = [];

        if ($saldoMes < 0) {
            $alertas[] = [
                'tipo' => 'danger',
                'titulo' => $mes === 'todos' ? 'Ano no vermelho' : 'Mês no vermelho',
                'texto' => "As despesas de R$ " . number_format($despesasMes, 2, ',', '.') .
                    " superaram as receitas de R$ " . number_format($receitasMes, 2, ',', '.') .
                    " em R$ " . number_format(abs($saldoMes), 2, ',', '.') . " neste período.",
            ];
        } elseif ($despesasMes > $receitasMes) {
            $alertas[] = [
                'tipo' => 'warning',
                'titulo' => 'Atenção com o período',
                'texto' => "Despesas (R$ " . number_format($despesasMes, 2, ',', '.') .
                    ") estão acima das receitas (R$ " . number_format($receitasMes, 2, ',', '.') . ").",
            ];
        } elseif ($saldoMes >= 0 && $saldoMes < $saldoMesAnterior) {
            $alertas[] = [
                'tipo' => 'info',
                'titulo' => 'Saldo abaixo do período anterior',
                'texto' => "Seu saldo atual de R$ " . number_format($saldoMes, 2, ',', '.') .
                    " está abaixo dos R$ " . number_format($saldoMesAnterior, 2, ',', '.') . " do período anterior.",
            ];
        }

        if ($mes !== 'todos') {
            $crescimento = $this->categoriasQueCrescem($user, $ano, $mesAtual, $periodoAnterior->year, $periodoAnterior->month);
            if (count($crescimento) > 0) {
                $parte = collect($crescimento)->take(3)->map(function ($c) {
                    return $c['nome'] . ' (+' . number_format($c['variacao_pct'], 0) . '% / R$ ' .
                        number_format($c['diferenca'], 2, ',', '.') . ')';
                })->join(', ');
                $alertas[] = [
                    'tipo' => 'warning',
                    'titulo' => 'O que mais pesou neste mês',
                    'texto' => 'Categorias que cresceram comparado ao mês passado: ' . $parte . '.',
                ];
            }
        }

        $comprometimento = $this->comprometimentoRenda($user, $ano, $mesAtual);
        $limiteComprometimento = (float) ($user->getConfig('percentual_alerta', 50) ?? 50);
        if ($comprometimento['percentual'] > $limiteComprometimento) {
            $alertas[] = [
                'tipo' => 'danger',
                'titulo' => 'Renda muito comprometida',
                'texto' => number_format($comprometimento['percentual'], 1, ',', '.') .
                    '% da sua renda está comprometida com parcelas e contas fixas (R$ ' .
                    number_format($comprometimento['total'], 2, ',', '.') .
                    '). Limite definido: ' . number_format($limiteComprometimento, 0) . '%.',
            ];
        } else {
            $alertas[] = [
                'tipo' => 'success',
                'titulo' => 'Comprometimento saudável',
                'texto' => number_format($comprometimento['percentual'], 1, ',', '.') .
                    '% da renda comprometida com parcelas e fixas (R$ ' .
                    number_format($comprometimento['total'], 2, ',', '.') . ').',
            ];
        }

        if ($totalContasAberto > 0) {
            $alertas[] = [
                'tipo' => 'info',
                'titulo' => 'Contas a pagar em aberto',
                'texto' => 'Você tem R$ ' . number_format($totalContasAberto, 2, ',', '.') .
                    ' em dívidas/compromissos de longo prazo pendentes.',
            ];
        }

        $simulador = $this->simuladorParcela($user, $ano, $mesAtual);

        $graficos = $this->dadosGraficos($user, $ano, $mesAtual);

        $ultimos = $user->lancamentos()->with(['categoria', 'subcategoria', 'cartao'])
            ->quando($periodo, $ano, $mesAtual)
            ->orderByDesc('data')->limit(8)->get();

        return view('livewire.dashboard', compact(
            'user', 'ano', 'mes', 'hoje', 'mesAtual', 'periodo', 'mesesDisponiveis',
            'receitasMes', 'despesasMes', 'saldoMes',
            'receitasMesAnterior', 'despesasMesAnterior', 'saldoMesAnterior',
            'saldoAno', 'totalContasAberto', 'contasAberto',
            'receitasLista', 'despesasLista',
            'alertas', 'simulador', 'graficos', 'ultimos',
        ));
    }

    /**
     * Itens de despesa do período para a listagem: lançamentos normais (fora
     * de cartão de crédito) e uma linha por fatura de cartão pelo valor
     * cheio — o que está no cartão aparece igual na dashboard, sem subtrair
     * os planos. Os totais são lidos de resumos_mensais; aqui apenas a lista.
     */
    private function despesasDoPeriodo($user, string $periodo, int $ano, ?int $mes): \Illuminate\Support\Collection
    {
        $base = $user->lancamentos()->with('categoria')
            ->where('tipo', 'despesa')->where('abate_saldo', true)->semAssinaturasFuturas()
            ->where(function ($q) {
                $q->whereNull('cartao_id')
                    ->orWhere('cartao_debito', true)
                    ->orWhere('forma_pagamento', '!=', 'cartao');
            })
            ->quando($periodo, $ano, $mes)
            ->orderByDesc('data')->orderByDesc('id')
            ->get(['id', 'data', 'descricao', 'valor', 'categoria_id']);

        $faturas = \App\Models\FaturaCartao::where('user_id', $user->id)
            ->with('cartao')
            ->when($mes !== null, fn ($q) => $q->where('ano', $ano)->where('mes', $mes), fn ($q) => $q->where('ano', $ano))
            ->get()
            ->map(function ($f) {
                return (object) [
                    'data' => $f->vencimento ?? Carbon::create($f->ano, $f->mes, 1),
                    'descricao' => 'Fatura ' . $f->cartao->nome . ' ' . $f->vencimento?->translatedFormat('M/Y') ?? $f->chave,
                    'categoria' => null,
                    'valor' => (float) $f->total,
                ];
            })
            ->filter(fn ($l) => abs((float) $l->valor) >= 0.01);

        return $base->concat($faturas)
            ->sortByDesc(fn ($l) => $l->data->timestamp)
            ->values();
    }

    private function categoriasQueCrescem($user, int $ano, int $mes, int $anoAnt, int $mesAnt): array
    {
        $atual = $user->lancamentos()->where('tipo', 'despesa')
            ->whereYear('data', $ano)->whereMonth('data', $mes)
            ->selectRaw('categoria_id, SUM(valor) as total')
            ->groupBy('categoria_id')->get()->pluck('total', 'categoria_id');

        $anterior = $user->lancamentos()->where('tipo', 'despesa')
            ->whereYear('data', $anoAnt)->whereMonth('data', $mesAnt)
            ->selectRaw('categoria_id, SUM(valor) as total')
            ->groupBy('categoria_id')->get()->pluck('total', 'categoria_id');

        $resultado = [];
        foreach ($atual as $catId => $total) {
            $prev = (float) ($anterior[$catId] ?? 0);
            $diferenca = (float) $total - $prev;
            if ($diferenca <= 0 || !$catId) {
                continue;
            }
            $resultado[] = [
                'nome' => $user->categorias()->find($catId)?->nome ?? 'Sem categoria',
                'atual' => (float) $total,
                'anterior' => $prev,
                'diferenca' => $diferenca,
                'variacao_pct' => $prev > 0 ? ($diferenca / $prev) * 100 : 100,
            ];
        }
        usort($resultado, fn ($a, $b) => $b['diferenca'] <=> $a['diferenca']);

        return $resultado;
    }

    private function comprometimentoRenda($user, int $ano, ?int $mes): array
    {
        $rendaFixa = (float) ($user->getConfig('renda_fixa') ?? $this->mediaReceitas($user));
        $rendaFixa = $rendaFixa ?: 1;

        $periodo = $mes === null ? 'ano' : 'mes';

        $parcelas = (float) $user->lancamentos()->where('tipo', 'despesa')
            ->where('abate_saldo', true)->where('qtd_parcelas', '>', 1)->quando($periodo, $ano, $mes)->sum('valor');

        $fixas = (float) $user->lancamentos()->where('tipo', 'despesa')
            ->where('abate_saldo', true)->where('recorrente', true)->quando($periodo, $ano, $mes)->sum('valor');

        $total = $parcelas + $fixas;

        return [
            'total' => $total,
            'parcelas' => $parcelas,
            'fixas' => $fixas,
            'renda' => $rendaFixa,
            'percentual' => round(($total / $rendaFixa) * 100, 1),
        ];
    }

    private function simuladorParcela($user, int $ano, ?int $mes): array
    {
        $receitaMedia = (float) ($user->getConfig('renda_fixa') ?? $this->mediaReceitas($user));
        $despesaMedia = $this->mediaDespesas($user);

        $periodo = $mes === null ? 'ano' : 'mes';

        $parcelasMes = (float) $user->lancamentos()->where('tipo', 'despesa')
            ->where('abate_saldo', true)->where('qtd_parcelas', '>', 1)->quando($periodo, $ano, $mes)->sum('valor');

        $margem = $receitaMedia - $despesaMedia;
        $margemLivre = $margem - $parcelasMes;

        return [
            'receita_media' => $receitaMedia,
            'despesa_media' => $despesaMedia,
            'margem' => $margem,
            'parcelas_mes' => $parcelasMes,
            'margem_livre' => $margemLivre,
            'pode' => $margemLivre > 0,
        ];
    }

    private function mediaReceitas($user): float
    {
        $seisMeses = $user->lancamentos()->where('tipo', 'receita')
            ->where('data', '>=', Carbon::now()->subMonths(6)->startOfMonth())->sum('valor');

        return round($seisMeses / 6, 2);
    }

    private function mediaDespesas($user): float
    {
        $seisMeses = $user->lancamentos()->where('tipo', 'despesa')
            ->where('data', '>=', Carbon::now()->subMonths(6)->startOfMonth())->sum('valor');

        return round($seisMeses / 6, 2);
    }

    private function dadosGraficos($user, int $ano, ?int $mes): array
    {
        $saldo = [];
        $receitas = [];
        $despesas = [];
        $rotulos = [];

        $agora = Carbon::now();
        for ($i = 11; $i >= 0; $i--) {
            $d = $agora->copy()->subMonths($i);
            $r = (float) $user->lancamentos()->where('tipo', 'receita')->noMes($d->year, $d->month)->sum('valor');
            $de = (float) $user->lancamentos()->where('tipo', 'despesa')->noMes($d->year, $d->month)->sum('valor');
            $receitas[] = $r;
            $despesas[] = $de;
            $saldo[] = round($r - $de, 2);
            $rotulos[] = $d->translatedFormat('M/y');
        }

        $donut = $user->lancamentos()->where('tipo', 'despesa')
            ->quando($mes === null ? 'ano' : 'mes', $ano, $mes)
            ->with('categoria')
            ->get()
            ->groupBy(fn ($l) => $l->categoria?->nome ?? 'Sem categoria')
            ->map(fn ($g) => round($g->sum('valor'), 2))
            ->sortDesc()
            ->take(8);

        return compact('rotulos', 'receitas', 'despesas', 'saldo', 'donut');
    }
}
