@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Financeiro')

@section('content')
    {{-- Alertas --}}
    <div class="row g-3 mb-4">
        @foreach ($alertas as $alerta)
            <div class="col-md-6 col-xl-4">
                <div class="alert alert-{{ $alerta['tipo'] }} shadow-sm mb-0 h-100">
                    <h6 class="alert-heading mb-1"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $alerta['titulo'] }}</h6>
                    <span class="small">{{ $alerta['texto'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Cartões resumo --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>R$ {{ number_format($receitasMes, 2, ',', '.') }}</h3>
                    <p>Receitas de {{ $hoje->translatedFormat('F') }}</p>
                </div>
                <div class="icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
                <a href="{{ route('lancamentos.index', ['tipo' => 'receita']) }}" class="small-box-footer">Ver lançamentos <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>R$ {{ number_format($despesasMes, 2, ',', '.') }}</h3>
                    <p>Despesas de {{ $hoje->translatedFormat('F') }}</p>
                </div>
                <div class="icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
                <a href="{{ route('lancamentos.index', ['tipo' => 'despesa']) }}" class="small-box-footer">Ver lançamentos <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="small-box {{ $saldoMes >= 0 ? 'bg-primary' : 'bg-dark' }}">
                <div class="inner">
                    <h3>R$ {{ number_format($saldoMes, 2, ',', '.') }}</h3>
                    <p>Saldo do mês ({{ $saldoMesAnterior >= 0 ? '+' : '' }}R$ {{ number_format($saldoMesAnterior, 2, ',', '.') }} no mês passado)</p>
                </div>
                <div class="icon"><i class="fa-solid fa-scale-balanced"></i></div>
                <a href="{{ route('relatorios.mensal') }}" class="small-box-footer">Ver relatório <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>R$ {{ number_format($totalContasAberto, 2, ',', '.') }}</h3>
                    <p>Contas a pagar em aberto</p>
                </div>
                <div class="icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <a href="{{ route('contas-pagar.index') }}" class="small-box-footer">Gerenciar <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    {{-- Simulador de nova parcela --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-gradient-primary text-white">
            <h5 class="mb-0"><i class="fa-solid fa-calculator me-2"></i>Posso criar uma nova parcela?</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small">Receita média (6 meses)</span>
                    <h6 class="mb-0">R$ {{ number_format($simulador['receita_media'], 2, ',', '.') }}</h6>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small">Despesa média (6 meses)</span>
                    <h6 class="mb-0">R$ {{ number_format($simulador['despesa_media'], 2, ',', '.') }}</h6>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small">Parcelas pagas neste mês</span>
                    <h6 class="mb-0">R$ {{ number_format($simulador['parcelas_mes'], 2, ',', '.') }}</h6>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small">Margem livre mensal</span>
                    <h6 class="mb-0 {{ $simulador['margem_livre'] >= 0 ? 'text-success' : 'text-danger' }}">
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
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fa-solid fa-chart-line me-2"></i>Evolução dos últimos 12 meses</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartEvolucao" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fa-solid fa-chart-pie me-2"></i>Despesas por categoria ({{ $hoje->translatedFormat('F') }})</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartDonut" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Últimos lançamentos --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-clock-rotate-left me-2"></i>Últimos lançamentos</h5>
            <a href="{{ route('lancamentos.create') }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus me-1"></i>Novo lançamento</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
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
                                <td>{{ $l->data->translatedFormat('d/m/Y') }}</td>
                                <td>{{ $l->descricao }}
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
                            <tr><td colspan="5" class="text-center text-muted py-4">Nenhum lançamento ainda. Importe sua planilha ou cadastre o primeiro!</td></tr>
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

    new Chart(document.getElementById('chartEvolucao'), {
        type: 'line',
        data: {
            labels: rotulos,
            datasets: [
                {
                    label: 'Receitas',
                    data: receitas,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25,135,84,.1)',
                    fill: true,
                    tension: .3
                },
                {
                    label: 'Despesas',
                    data: despesas,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220,53,69,.1)',
                    fill: true,
                    tension: .3
                },
                {
                    label: 'Saldo',
                    data: saldo,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,.15)',
                    fill: true,
                    tension: .3
                }
            ]
        },
        options: {
            responsive: true,
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
                backgroundColor: ['#0d6efd', '#dc3545', '#ffc107', '#198754', '#0dcaf0', '#6610f2', '#fd7e14', '#20c997'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
        }
    });
</script>
@endpush
