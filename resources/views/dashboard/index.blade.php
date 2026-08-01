@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Financeiro')

@section('content')
    {{-- Stats Cards --}}
    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="stat-value">R$ {{ number_format($receitasMes, 2, ',', '.') }}</div>
            <div class="stat-label">Receitas de {{ $hoje->translatedFormat('F') }}</div>
            <div class="stat-sub"><a href="{{ route('lancamentos.index', ['tipo' => 'receita']) }}">Ver lançamentos <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
        </div>

        <div class="stat-card red">
            <div class="stat-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
            <div class="stat-value">R$ {{ number_format($despesasMes, 2, ',', '.') }}</div>
            <div class="stat-label">Despesas de {{ $hoje->translatedFormat('F') }}</div>
            <div class="stat-sub"><a href="{{ route('lancamentos.index', ['tipo' => 'despesa']) }}">Ver lançamentos <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
        </div>

        <div class="stat-card {{ $saldoMes >= 0 ? 'blue' : 'purple' }}">
            <div class="stat-icon"><i class="fa-solid fa-scale-balanced"></i></div>
            <div class="stat-value">R$ {{ number_format($saldoMes, 2, ',', '.') }}</div>
            <div class="stat-label">Saldo do mês</div>
            <div class="stat-sub">
                {{ $saldoMesAnterior >= 0 ? '+' : '' }}R$ {{ number_format($saldoMesAnterior, 2, ',', '.') }} no mês passado
            </div>
        </div>

        <div class="stat-card yellow">
            <div class="stat-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <div class="stat-value">R$ {{ number_format($totalContasAberto, 2, ',', '.') }}</div>
            <div class="stat-label">Contas a pagar em aberto</div>
            <div class="stat-sub"><a href="{{ route('contas-pagar.index') }}">Gerenciar <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
        </div>

        <div class="stat-card {{ $simulador['margem_livre'] >= 0 ? 'purple' : 'red' }}">
            <div class="stat-icon"><i class="fa-solid fa-calculator"></i></div>
            <div class="stat-value">R$ {{ number_format($simulador['margem_livre'], 2, ',', '.') }}</div>
            <div class="stat-label">Margem livre mensal</div>
            <div class="stat-sub">Receita média R$ {{ number_format($simulador['receita_media'], 2, ',', '.') }}</div>
        </div>
    </div>

    {{-- Alertas --}}
    @if (count($alertas))
        <div class="row g-3 mb-4">
            @foreach ($alertas as $alerta)
                <div class="col-md-6 col-xl-4">
                    <div class="alert alert-{{ $alerta['tipo'] }} h-100 mb-0">
                        <strong><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $alerta['titulo'] }}</strong>
                        <div class="small mt-1">{{ $alerta['texto'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Simulador de nova parcela --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title"><i class="fa-solid fa-calculator me-2 text-primary"></i>Posso criar uma nova parcela?</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small">Receita média (6 meses)</span>
                    <h6 class="mb-0 mt-1">R$ {{ number_format($simulador['receita_media'], 2, ',', '.') }}</h6>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small">Despesa média (6 meses)</span>
                    <h6 class="mb-0 mt-1">R$ {{ number_format($simulador['despesa_media'], 2, ',', '.') }}</h6>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small">Parcelas pagas neste mês</span>
                    <h6 class="mb-0 mt-1">R$ {{ number_format($simulador['parcelas_mes'], 2, ',', '.') }}</h6>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small">Margem livre mensal</span>
                    <h6 class="mb-0 mt-1 {{ $simulador['margem_livre'] >= 0 ? 'text-success' : 'text-danger' }}">
                        R$ {{ number_format($simulador['margem_livre'], 2, ',', '.') }}
                    </h6>
                </div>
            </div>
            <hr>
            @if ($simulador['pode'])
                <p class="mb-0">
                    <i class="fa-solid fa-circle-check text-success me-1"></i>
                    <strong>Sim!</strong> Sua margem livre permite assumir uma parcela de até
                    <strong>R$ {{ number_format($simulador['margem_livre'], 2, ',', '.') }} por mês</strong>.
                    Ex.: uma parcela de R$ {{ number_format(min(round($simulador['margem_livre'] / 2, 2), $simulador['margem_livre']), 2, ',', '.') }}
                    deixaria uma folga de R$ {{ number_format($simulador['margem_livre'] / 2, 2, ',', '.') }}.
                </p>
            @else
                <p class="mb-0 text-danger">
                    <i class="fa-solid fa-circle-xmark me-1"></i>
                    <strong>Cuidado!</strong> Sua margem livre está negativa (R$ {{ number_format($simulador['margem_livre'], 2, ',', '.') }}).
                    Evite criar novas parcelas até equilibrar receitas e despesas.
                </p>
            @endif
        </div>
    </div>

    {{-- Gráficos --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="fa-solid fa-chart-line me-2 text-primary"></i>Evolução dos últimos 12 meses</h5>
                </div>
                <div class="card-body">
                    <div class="chart-box"><canvas id="chartEvolucao"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="fa-solid fa-chart-pie me-2 text-primary"></i>Despesas por categoria ({{ $hoje->translatedFormat('F') }})</h5>
                </div>
                <div class="card-body">
                    <div class="chart-box"><canvas id="chartDonut"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Últimos lançamentos --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Últimos lançamentos</h5>
            <a href="{{ route('lancamentos.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Novo lançamento</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Forma</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ultimos as $l)
                            <tr>
                                <td class="text-nowrap">{{ $l->data->translatedFormat('d/m/Y') }}</td>
                                <td>
                                    {{ $l->descricao }}
                                    @if ($l->isParcela())<span class="badge bg-secondary ms-1">{{ $l->parcela_atual }}/{{ $l->qtd_parcelas }}</span>@endif
                                    @if ($l->recorrente)<span class="badge bg-info ms-1">fixo</span>@endif
                                </td>
                                <td>{{ $l->categoria?->nome }}@if ($l->subcategoria)<span class="text-muted"> / {{ $l->subcategoria->nome }}</span>@endif</td>
                                <td>
                                    @if ($l->cartao)<span class="badge bg-dark">{{ $l->cartao->nome }}</span>
                                    @else <span class="text-muted">{{ $l->forma_pagamento ? $l->forma_label : '—' }}</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold {{ $l->tipo === 'receita' ? 'text-success' : 'text-danger' }}">
                                    {{ $l->tipo === 'receita' ? '+' : '-' }}R$ {{ number_format($l->valor, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-folder-open"></i>
                                        <p>Nenhum lançamento ainda. Importe sua planilha ou cadastre o primeiro!</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const rotulos = @json($graficos['rotulos']);
    const receitas = @json($graficos['receitas']);
    const despesas = @json($graficos['despesas']);
    const saldo = @json($graficos['saldo']);
    const temaBody = document.body.getAttribute('data-bs-theme');
    const corTexto = temaBody === 'dark' ? '#9ca3af' : '#6c7293';

    Chart.defaults.color = corTexto;

    new Chart(document.getElementById('chartEvolucao'), {
        type: 'line',
        data: {
            labels: rotulos,
            datasets: [
                {
                    label: 'Receitas',
                    data: receitas,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,.1)',
                    fill: true,
                    tension: .3
                },
                {
                    label: 'Despesas',
                    data: despesas,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,.1)',
                    fill: true,
                    tension: .3
                },
                {
                    label: 'Saldo',
                    data: saldo,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,.15)',
                    fill: true,
                    tension: .3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    const donut = @json($graficos['donut']);
    new Chart(document.getElementById('chartDonut'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(donut),
            datasets: [{
                data: Object.values(donut),
                backgroundColor: ['#3b82f6', '#ef4444', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6', '#fd7e14', '#14b8a6'],
                borderWidth: 2,
                borderColor: temaBody === 'dark' ? '#1a1d29' : '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
        }
    });
</script>
@endpush
