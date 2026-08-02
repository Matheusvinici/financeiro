<div>
    @php
        $rotuloPeriodo = $mes === 'todos' || $mes === 'Todos'
            ? 'ano ' . $ano
            : \Carbon\Carbon::now()->month((int) $mes)->translatedFormat('F/Y');
    @endphp

    {{-- Seletor de período (atualiza sozinho) --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Mês</label>
                    <select wire:model.live="mes" class="form-select">
                        <option value="todos">Todos os meses do ano</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}">{{ \Carbon\Carbon::now()->month($m)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ano</label>
                    <select wire:model.live="ano" class="form-select">
                        @foreach ($mesesDisponiveis->pluck('ano')->unique()->sortDesc() as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Períodos com lançamentos</label>
                    <select class="form-select" onchange="if(this.value){ window.location.href = this.value; }">
                        <option value="">— Ir direto para —</option>
                        @foreach ($mesesDisponiveis as $p)
                            <option value="{{ route('dashboard', ['mes' => $p['mes'], 'ano' => $p['ano']]) }}" @selected((int) $mes === $p['mes'] && (int) $ano === $p['ano'])>
                                {{ \Carbon\Carbon::now()->month($p['mes'])->translatedFormat('F') . '/' . $p['ano'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="button" wire:click="resetPeriodo" class="btn btn-outline-secondary flex-grow-1" title="Mês atual"><i class="fa-solid fa-rotate-left me-1"></i>Mês atual</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="stat-value">
                <a href="#" class="stat-link" data-bs-toggle="modal" data-bs-target="#modalReceitas" title="Ver quais receitas compõem este total">R$ {{ number_format($receitasMes, 2, ',', '.') }}</a>
            </div>
            <div class="stat-label">Receitas de {{ $rotuloPeriodo }}</div>
            <div class="stat-sub"><a href="{{ route('lancamentos.index', ['tipo' => 'receita']) }}">Ver lançamentos <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
        </div>

        <div class="stat-card red">
            <div class="stat-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
            <div class="stat-value">
                <a href="#" class="stat-link" data-bs-toggle="modal" data-bs-target="#modalDespesas" title="Ver quais despesas compõem este total">R$ {{ number_format($despesasMes, 2, ',', '.') }}</a>
            </div>
            <div class="stat-label">Despesas de {{ $rotuloPeriodo }}</div>
            @if ($assinaturasPrevistas > 0)
                <div class="stat-sub">Inclui R$ {{ number_format($assinaturasPrevistas, 2, ',', '.') }} em assinaturas previstas</div>
            @else
                <div class="stat-sub"><a href="{{ route('lancamentos.index', ['tipo' => 'despesa']) }}">Ver lançamentos <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
            @endif
        </div>

        <div class="stat-card {{ $saldoMes >= 0 ? 'blue' : 'purple' }}">
            <div class="stat-icon"><i class="fa-solid fa-scale-balanced"></i></div>
            <div class="stat-value">R$ {{ number_format($saldoMes, 2, ',', '.') }}</div>
            <div class="stat-label">{{ $mes === 'todos' ? 'Saldo do ano' : 'Saldo do mês' }}</div>
            <div class="stat-sub">
                {{ $saldoMesAnterior >= 0 ? '+' : '' }}R$ {{ number_format($saldoMesAnterior, 2, ',', '.') }} no período anterior
            </div>
        </div>

        <div class="stat-card yellow">
            <div class="stat-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <div class="stat-value">
                <a href="#" class="stat-link" data-bs-toggle="modal" data-bs-target="#modalPendencias" title="Ver quais pendências compõem este total">R$ {{ number_format($totalContasAberto, 2, ',', '.') }}</a>
            </div>
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
                    <h5 class="card-title"><i class="fa-solid fa-chart-pie me-2 text-primary"></i>Despesas por categoria ({{ $rotuloPeriodo }})</h5>
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
                                        <p>Nenhum lançamento neste período.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script type="application/json" id="graficos-data">
        @json($graficos)
    </script>
    <div class="modal fade" id="modalReceitas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-arrow-trend-up text-success me-2"></i>Receitas de {{ $rotuloPeriodo }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th class="text-end">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($receitasLista as $l)
                                <tr>
                                    <td class="text-nowrap">{{ $l->data->translatedFormat('d/m/Y') }}</td>
                                    <td>{{ $l->descricao }}</td>
                                    <td>{{ $l->categoria?->nome ?? '—' }}</td>
                                    <td class="text-end text-success">+ R$ {{ number_format($l->valor, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Nenhuma receita neste período.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <th colspan="3">Total de receitas</th>
                                <th class="text-end">R$ {{ number_format($receitasMes, 2, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDespesas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-arrow-trend-down text-danger me-2"></i>Despesas de {{ $rotuloPeriodo }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th class="text-end">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($despesasLista as $l)
                                <tr>
                                    <td class="text-nowrap">{{ $l->data->translatedFormat('d/m/Y') }}</td>
                                    <td>{{ $l->descricao }}</td>
                                    <td>{{ $l->categoria?->nome ?? '—' }}</td>
                                    <td class="text-end text-danger">- R$ {{ number_format($l->valor, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Nenhuma despesa neste período.</td></tr>
                            @endforelse
                            @if ($assinaturasPrevistas > 0)
                                <tr>
                                    <td class="text-nowrap">{{ $hoje->translatedFormat('d/m/Y') }}</td>
                                    <td>Assinaturas previstas (ainda sem lançamento no mês)</td>
                                    <td>—</td>
                                    <td class="text-end text-danger">- R$ {{ number_format($assinaturasPrevistas, 2, ',', '.') }}</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <th colspan="3">Total de despesas</th>
                                <th class="text-end">R$ {{ number_format($despesasMes, 2, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPendencias" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-hand-holding-dollar text-warning me-2"></i>Pendências em aberto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Vencimento</th>
                                <th>Descrição</th>
                                <th>Status</th>
                                <th class="text-end">Valor restante</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contasAberto as $c)
                                <tr>
                                    <td class="text-nowrap">{{ $c->data_vencimento?->translatedFormat('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $c->descricao }}</td>
                                    <td><span class="badge {{ $c->status === 'parcial' ? 'bg-warning text-dark' : 'bg-danger' }}">{{ $c->status_label }}</span></td>
                                    <td class="text-end text-danger">R$ {{ number_format($c->valor_restante, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Nenhuma pendência em aberto.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <th colspan="3">Total pendente</th>
                                <th class="text-end">R$ {{ number_format($totalContasAberto, 2, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.mfCharts = {};

    function initGraficos() {
        Object.values(window.mfCharts).forEach(c => { try { c.destroy(); } catch (e) {} });
        window.mfCharts = {};

        const el = document.getElementById('graficos-data');
        if (!el) return;
        const graficos = JSON.parse(el.textContent);

        const temaBody = document.body.getAttribute('data-bs-theme');
        const corTexto = temaBody === 'dark' ? '#9ca3af' : '#6c7293';
        Chart.defaults.color = corTexto;

        const canvasEvo = document.getElementById('chartEvolucao');
        if (canvasEvo) {
            window.mfCharts.evolucao = new Chart(canvasEvo, {
                type: 'line',
                data: {
                    labels: graficos.rotulos,
                    datasets: [
                        { label: 'Receitas', data: graficos.receitas, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.1)', fill: true, tension: .3 },
                        { label: 'Despesas', data: graficos.despesas, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.1)', fill: true, tension: .3 },
                        { label: 'Saldo', data: graficos.saldo, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.15)', fill: true, tension: .3 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        }

        const canvasDonut = document.getElementById('chartDonut');
        if (canvasDonut) {
            window.mfCharts.donut = new Chart(canvasDonut, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(graficos.donut),
                    datasets: [{
                        data: Object.values(graficos.donut),
                        backgroundColor: ['#3b82f6', '#ef4444', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6', '#fd7e14', '#14b8a6'],
                        borderWidth: 2,
                        borderColor: temaBody === 'dark' ? '#1a1d29' : '#ffffff'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
            });
        }
    }

    document.addEventListener('livewire:init', () => {
        initGraficos();
        Livewire.on('graficos-atualizados', () => initGraficos());
    });
</script>
