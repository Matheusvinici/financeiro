<?php

namespace App\Http\Controllers;

use App\Models\ContaPagar;
use App\Models\Lancamento;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $ano = (int) $request->input('ano', Carbon::now()->year);
        $hoje = Carbon::now();
        $mesAtual = $hoje->month;

        $receitasMes = (float) $user->lancamentos()
            ->where('tipo', 'receita')->noMes($hoje->year, $mesAtual)->sum('valor');
        $despesasMes = (float) $user->lancamentos()
            ->where('tipo', 'despesa')->noMes($hoje->year, $mesAtual)->sum('valor');
        $saldoMes = $receitasMes - $despesasMes;

        $mesAnterior = $hoje->copy()->subMonth();
        $receitasMesAnterior = (float) $user->lancamentos()
            ->where('tipo', 'receita')->noMes($mesAnterior->year, $mesAnterior->month)->sum('valor');
        $despesasMesAnterior = (float) $user->lancamentos()
            ->where('tipo', 'despesa')->noMes($mesAnterior->year, $mesAnterior->month)->sum('valor');
        $saldoMesAnterior = $receitasMesAnterior - $despesasMesAnterior;

        $saldoAno = (float) $user->lancamentos()->whereYear('data', $hoje->year)
            ->get()->sum(fn ($l) => $l->tipo === 'receita' ? $l->valor : -$l->valor);

        $contasAberto = ContaPagar::where('user_id', $user->id)
            ->where('status', '!=', 'pago')->get();
        $totalContasAberto = $contasAberto->sum('valor_restante');

        // ----- Alertas -----
        $alertas = [];

        if ($saldoMes < 0) {
            $alertas[] = [
                'tipo' => 'danger',
                'titulo' => 'Mês no vermelho',
                'texto' => "As despesas de R$ " . number_format($despesasMes, 2, ',', '.') .
                    " superaram as receitas de R$ " . number_format($receitasMes, 2, ',', '.') .
                    " em R$ " . number_format(abs($saldoMes), 2, ',', '.') . " neste mês.",
            ];
        } elseif ($despesasMes > $receitasMes) {
            $alertas[] = [
                'tipo' => 'warning',
                'titulo' => 'Atenção com o mês',
                'texto' => "Despesas (R$ " . number_format($despesasMes, 2, ',', '.') .
                    ") estão acima das receitas (R$ " . number_format($receitasMes, 2, ',', '.') . ").",
            ];
        } elseif ($saldoMes >= 0 && $saldoMes < $saldoMesAnterior) {
            $alertas[] = [
                'tipo' => 'info',
                'titulo' => 'Saldo abaixo do mês anterior',
                'texto' => "Seu saldo atual de R$ " . number_format($saldoMes, 2, ',', '.') .
                    " está abaixo dos R$ " . number_format($saldoMesAnterior, 2, ',', '.') . " do mês passado.",
            ];
        }

        // Categorias que mais cresceram (prejudicando o saldo)
        $crescimento = $this->categoriasQueCrescem($user, $hoje->year, $mesAtual, $mesAnterior->year, $mesAnterior->month);
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

        // Comprometimento da renda
        $comprometimento = $this->comprometimentoRenda($user, $hoje->year, $mesAtual);
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

        // ----- Simulador de nova parcela -----
        $simulador = $this->simuladorParcela($user, $hoje->year, $mesAtual);

        // ----- Dados dos gráficos -----
        $graficos = $this->dadosGraficos($user, $hoje->year);

        // ----- Últimos lançamentos -----
        $ultimos = $user->lancamentos()->with(['categoria', 'subcategoria', 'cartao'])
            ->orderByDesc('data')->limit(8)->get();

        return view('dashboard.index', compact(
            'user', 'ano', 'hoje', 'mesAtual',
            'receitasMes', 'despesasMes', 'saldoMes',
            'receitasMesAnterior', 'despesasMesAnterior', 'saldoMesAnterior',
            'saldoAno', 'totalContasAberto', 'alertas', 'simulador', 'graficos', 'ultimos'
        ));
    }

    private function categoriasQueCrescem($user, int $ano, int $mes, int $anoAnt, int $mesAnt): array    {
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

    private function comprometimentoRenda($user, int $ano, int $mes): array
    {
        $rendaFixa = (float) ($user->getConfig('renda_fixa') ?? $this->mediaReceitas($user));
        $rendaFixa = $rendaFixa ?: 1;

        $parcelas = (float) $user->lancamentos()->where('tipo', 'despesa')
            ->where('qtd_parcelas', '>', 1)->whereYear('data', $ano)->whereMonth('data', $mes)
            ->sum('valor');

        $fixas = (float) $user->lancamentos()->where('tipo', 'despesa')
            ->where('recorrente', true)->whereYear('data', $ano)->whereMonth('data', $mes)
            ->sum('valor');

        $total = $parcelas + $fixas;

        return [
            'total' => $total,
            'parcelas' => $parcelas,
            'fixas' => $fixas,
            'renda' => $rendaFixa,
            'percentual' => round(($total / $rendaFixa) * 100, 1),
        ];
    }

    private function simuladorParcela($user, int $ano, int $mes): array
    {
        $receitaMedia = (float) ($user->getConfig('renda_fixa') ?? $this->mediaReceitas($user));
        $despesaMedia = $this->mediaDespesas($user);

        $parcelasMes = (float) $user->lancamentos()->where('tipo', 'despesa')
            ->where('qtd_parcelas', '>', 1)->whereYear('data', $ano)->whereMonth('data', $mes)
            ->sum('valor');

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

    private function dadosGraficos($user, int $ano): array
    {
        $meses = [];
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

        // Despesas por categoria no mês atual
        $donut = $user->lancamentos()->where('tipo', 'despesa')
            ->noMes($agora->year, $agora->month)
            ->with('categoria')
            ->get()
            ->groupBy(fn ($l) => $l->categoria?->nome ?? 'Sem categoria')
            ->map(fn ($g) => round($g->sum('valor'), 2))
            ->sortDesc()
            ->take(8);

        return compact('rotulos', 'receitas', 'despesas', 'saldo', 'donut');
    }
}
