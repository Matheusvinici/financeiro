@extends('layouts.app')

@section('title', 'Relatório Mensal')
@section('page-title', 'Relatório Mensal')

@section('content')
    @php $listaMeses = $mesAtual ? [$mesAtual] : range(1, 12); $colspan = count($listaMeses) + 2; @endphp
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('relatorios.mensal') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Ano</label>
                    <select name="ano" class="form-select">
                        @foreach (range(now()->year, now()->year - 7) as $y)
                            <option value="{{ $y }}" @selected($ano == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Mês</label>
                    <select name="mes" class="form-select">
                        <option value="todos" @selected($mes === 'todos')>Todos os meses</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" @selected($mes === $m)>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i>Gerar</button>
                    <a href="{{ route('relatorios.pdf', ['ano' => $ano, 'mes' => $mes]) }}" class="btn btn-outline-danger" target="_blank"><i class="fa-solid fa-file-pdf me-1"></i>Exportar PDF</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Balanço --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <h5 class="mb-0"><i class="fa-solid fa-scale-balanced me-2"></i>Balanço Financeiro {{ $ano }}{{ $mesAtual ? ' · ' . \Carbon\Carbon::create()->month($mesAtual)->translatedFormat('F') : '' }}</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-primary">
                    <tr>
                        <th class="text-center">Mês</th>
                        @foreach ($listaMeses as $m)
                            <th class="text-center">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('M') }}</th>
                        @endforeach
                        <th class="text-center">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th>Receitas</th>
                        @foreach ($listaMeses as $m)
                            <td class="text-end text-success">{{ number_format($totaisMesReceitas[$m] ?? 0, 2, ',', '.') }}</td>
                        @endforeach
                        <td class="text-end fw-bold text-success">{{ number_format($totaisAno['receitas'], 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Despesas</th>
                        @foreach ($listaMeses as $m)
                            <td class="text-end text-danger">{{ number_format($totaisMesDespesas[$m] ?? 0, 2, ',', '.') }}</td>
                        @endforeach
                        <td class="text-end fw-bold text-danger">{{ number_format($totaisAno['despesas'], 2, ',', '.') }}</td>
                    </tr>
                    <tr class="table-secondary">
                        <th>Saldo</th>
                        @foreach ($listaMeses as $m)
                            <td class="text-end fw-bold {{ ($saldoMes[$m] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($saldoMes[$m] ?? 0, 2, ',', '.') }}
                            </td>
                        @endforeach
                        <td class="text-end fw-bold {{ $totaisAno['saldo'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($totaisAno['saldo'], 2, ',', '.') }}
                        </td>
                    </tr>
                    @if ($despesasNaoAbate > 0)
                        <tr>
                            <th class="text-muted small">Despesas que não abatem do saldo</th>
                            @foreach ($listaMeses as $m)
                                <td class="text-end text-muted small">{{ $totaisMesNaoAbate[$m] ? number_format($totaisMesNaoAbate[$m], 2, ',', '.') : '' }}</td>
                            @endforeach
                            <td class="text-end fw-bold small text-muted">{{ number_format($despesasNaoAbate, 2, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Matriz por categoria --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-table me-2"></i>Detalhamento por categoria e item</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Categoria / Item</th>
                        @foreach ($listaMeses as $m)
                            <th class="text-center">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('M') }}</th>
                        @endforeach
                        <th class="text-center">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['receitas' => 'Receitas', 'despesas' => 'Despesas'] as $tipo => $titulo)
                        <tr class="{{ $tipo === 'receitas' ? 'table-success' : 'table-danger' }}">
                            <td colspan="{{ $colspan }}"><strong>{{ $titulo }}</strong></td>
                        </tr>
                        @forelse ($agrupadas[$tipo] ?? [] as $categoriaNome => $itens)
                            <tr class="bg-light">
                                <td><strong><i class="fa-solid fa-layer-group me-1"></i>{{ $categoriaNome }}</strong></td>
                                @foreach ($listaMeses as $m)
                                    <td class="text-end small">{{ number_format(collect($itens)->sum(fn ($i) => $i[$m] ?? 0), 2, ',', '.') }}</td>
                                @endforeach
                                <td class="text-end small fw-bold">{{ number_format(collect($itens)->sum(fn ($i) => collect($i)->sum()), 2, ',', '.') }}</td>
                            </tr>
                            @foreach ($itens as $itemNome => $valores)
                                <tr>
                                    <td class="ps-4 text-muted">{{ $itemNome }}</td>
                                    @foreach ($listaMeses as $m)
                                        <td class="text-end">{{ ($valores[$m] ?? 0) ? number_format($valores[$m], 2, ',', '.') : '' }}</td>
                                    @endforeach
                                    <td class="text-end fw-bold">{{ number_format(collect($valores)->sum(), 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="{{ $colspan }}" class="text-center text-muted">Nenhum lançamento de {{ $titulo }} em {{ $mesAtual ? \Carbon\Carbon::create()->month($mesAtual)->translatedFormat('F') . ' de ' : '' }}{{ $ano }}.</td></tr>
                        @endforelse
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Gastos por cartão --}}
    @if ($cartoes->isNotEmpty())
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa-solid fa-credit-card me-2"></i>Gastos por cartão</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Cartão</th>
                            @foreach ($listaMeses as $m)
                                <th class="text-center">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('M') }}</th>
                            @endforeach
                            <th class="text-center">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cartoes as $cartao)
                            <tr>
                                <td><strong>{{ $cartao->nome }}</strong></td>
                                @foreach ($listaMeses as $m)
                                    <td class="text-end">{{ ($gastosCartaoMes[$cartao->id][$m] ?? 0) ? number_format($gastosCartaoMes[$cartao->id][$m], 2, ',', '.') : '' }}</td>
                                @endforeach
                                <td class="text-end fw-bold">{{ number_format(collect($gastosCartaoMes[$cartao->id])->sum(), 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
