@extends('layouts.app')

@section('title', 'Cartões')
@section('page-title', 'Cartões')

@section('content')
    {{-- Seletor de período --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('cartoes.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Mês</label>
                    <select name="mes" class="form-select" onchange="this.form.submit()">
                        <option value="todos" @selected($mes === 'todos')>Todos os meses do ano</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" @selected($mes === $m)>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Ano</label>
                    <select name="ano" class="form-select" onchange="this.form.submit()">
                        @foreach ($mesesDisponiveis->pluck('ano')->unique()->sortDesc() as $y)
                            <option value="{{ $y }}" @selected($ano == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Períodos com lançamentos</label>
                    <select class="form-select" onchange="if(this.value){ window.location.href = this.value; }">
                        <option value="">— Ir direto para —</option>
                        @foreach ($mesesDisponiveis as $p)
                            <option value="{{ route('cartoes.index', ['mes' => $p['mes'], 'ano' => $p['ano']]) }}" @selected($mes === $p['mes'] && $ano === $p['ano'])>
                                {{ \Carbon\Carbon::create()->month($p['mes'])->translatedFormat('F') . '/' . $p['ano'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <a href="{{ route('cartoes.index') }}" class="btn btn-outline-secondary flex-grow-1" title="Mês atual"><i class="fa-solid fa-rotate-left me-1"></i>Mês atual</a>
                </div>
            </form>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa-solid fa-credit-card"></i></div>
            <div class="stat-value">{{ $totais->count() }}</div>
            <div class="stat-label">Cartões cadastrados</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
            <div class="stat-value">R$ {{ number_format($totalGeral, 2, ',', '.') }}</div>
            <div class="stat-label">{{ $mes === 'todos' ? 'Total das faturas atuais' : 'Total das faturas de ' . \Illuminate\Support\Carbon::create($ano, $mes, 1)->translatedFormat('F/Y') }}</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-arrows-rotate"></i></div>
            <div class="stat-value">R$ {{ number_format($totalAssinaturas, 2, ',', '.') }}</div>
            <div class="stat-label">Assinaturas ativas/mês</div>
        </div>
    </div>

    <div class="row g-3">
        @forelse ($totais as $t)
            <div class="col-md-6 col-xl-4">
                <div class="card shadow-sm h-100 {{ $t['cartao']->ativo ? '' : 'opacity-50' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-0"><i class="fa-solid fa-credit-card me-2"></i>{{ $t['cartao']->nome }}</h5>
                                <span class="badge bg-{{ $t['cartao']->tipo === 'debito' ? 'secondary' : 'info' }} mt-1">{{ $t['cartao']->tipo_label }}</span>
                                @if ($t['cartao']->bandeira)<span class="badge bg-light text-dark border ms-1">{{ $t['cartao']->bandeira }}</span>@endif
                            </div>
                            <div class="btn-group">
                                <a href="{{ route('lancamentos.create', ['forma' => 'cartao', 'cartao' => $t['cartao']->id]) }}" class="btn btn-sm btn-success" title="Registrar gasto neste cartão"><i class="fa-solid fa-plus me-1"></i>Gasto</a>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit{{ $t['cartao']->id }}"><i class="fa-solid fa-pen me-1"></i>Editar</button>
                                <form method="POST" action="{{ route('cartoes.destroy', $t['cartao']) }}" onsubmit="return confirm('Excluir cartão?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash me-1"></i>Excluir</button>
                                </form>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row text-center">
                            <div class="col-6">
                                <span class="text-muted small">{{ $t['destaque_label'] }}</span>
                                <h6 class="text-danger">R$ {{ number_format($t['destaque'], 2, ',', '.') }}</h6>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small">À vista</span>
                                <h6>R$ {{ number_format($t['avista_periodo'], 2, ',', '.') }}</h6>
                            </div>
                        </div>
                        @if ($t['cartao']->limite > 0)
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Limite: R$ {{ number_format($t['cartao']->limite, 2, ',', '.') }}</span>
                                    <span class="{{ $t['utilizacao_pct'] > 80 ? 'text-danger fw-bold' : 'text-muted' }}">{{ number_format($t['utilizacao_pct'], 1, ',', '.') }}% usado</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar {{ $t['utilizacao_pct'] > 80 ? 'bg-danger' : ($t['utilizacao_pct'] > 50 ? 'bg-warning' : 'bg-success') }}"
                                         style="width: {{ min($t['utilizacao_pct'], 100) }}%"></div>
                                </div>
                            </div>
                        @endif
                        @if ($t['cartao']->dia_fechamento || $t['cartao']->dia_vencimento)
                            <div class="small text-muted mt-2">
                                Fechamento: {{ $t['cartao']->dia_fechamento ?: '—' }} / Vencimento: {{ $t['cartao']->dia_vencimento ?: '—' }}
                            </div>
                        @endif
                        @php $det = ($detalhesFatura ?? [])[$t['cartao']->id] ?? null; @endphp
                        @if ($det && $det['itens']->isNotEmpty())
                            <button class="btn btn-sm btn-outline-secondary w-100 mt-2" data-bs-toggle="collapse" data-bs-target="#fat{{ $t['cartao']->id }}">
                                <i class="fa-solid fa-receipt me-1"></i>O que tem na fatura de {{ $det['rotulo'] }} ({{ $det['itens']->count() }})
                            </button>
                            <div class="collapse mt-2" id="fat{{ $t['cartao']->id }}">
                                <ul class="list-group list-group-flush small">
                                    @foreach ($det['itens'] as $item)
                                        <li class="list-group-item d-flex justify-content-between align-items-start px-1">
                                            <div>
                                                <span class="text-muted">{{ $item->data->translatedFormat('d/m') }}</span>
                                                {{ $item->assinatura?->nome ?? $item->descricao }}
                                                @if ($item->assinatura_id)<span class="badge bg-warning text-dark ms-1">plano</span>@endif
                                                @if ($item->isParcela())<span class="badge bg-secondary ms-1">{{ $item->parcela_atual }}/{{ $item->qtd_parcelas }}</span>@endif
                                                @if ($item->ajuste)<span class="badge bg-info text-dark ms-1">ajuste</span>@endif
                                                <div class="text-muted">{{ $item->categoria?->nome ?? '' }}</div>
                                            </div>
                                            <strong class="text-danger text-nowrap">R$ {{ number_format($item->valor, 2, ',', '.') }}</strong>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    <div class="collapse" id="edit{{ $t['cartao']->id }}">
                        <form method="POST" action="{{ route('cartoes.update', $t['cartao']) }}" class="row g-2 p-3 bg-body-tertiary">
                            @csrf @method('PUT')
                            <div class="col-md-4">
                                <input type="text" name="nome" class="form-control form-control-sm" value="{{ $t['cartao']->nome }}" required>
                            </div>
                            <div class="col-md-3">
                                <select name="tipo" class="form-select form-select-sm">
                                    <option value="credito" @selected($t['cartao']->tipo === 'credito')>Crédito</option>
                                    <option value="debito" @selected($t['cartao']->tipo === 'debito')>Débito</option>
                                    <option value="credito_debito" @selected($t['cartao']->tipo === 'credito_debito')>Crédito/Débito</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="bandeira" class="form-control form-control-sm" value="{{ $t['cartao']->bandeira }}" placeholder="Bandeira">
                            </div>
                            <div class="col-md-2">
                                <input type="number" step="0.01" name="limite" class="form-control form-control-sm" value="{{ $t['cartao']->limite }}">
                            </div>
                            <div class="col-md-2">
                                <input type="number" min="1" max="31" name="dia_fechamento" class="form-control form-control-sm" value="{{ $t['cartao']->dia_fechamento }}" placeholder="Fech.">
                            </div>
                            <div class="col-md-2">
                                <input type="number" min="1" max="31" name="dia_vencimento" class="form-control form-control-sm" value="{{ $t['cartao']->dia_vencimento }}" placeholder="Venc.">
                            </div>
                            <div class="col-md-2">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo{{ $t['cartao']->id }}" @checked($t['cartao']->ativo)>
                                    <label class="form-check-label small" for="ativo{{ $t['cartao']->id }}">Ativo</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-sm btn-primary w-100"><i class="fa-solid fa-check"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">Nenhum cartão cadastrado. Cadastre para vincular os gastos ao cartão usado.</div>
            </div>
        @endforelse
    </div>

    {{-- Assinaturas --}}
    <div class="card shadow-sm mt-2">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0"><i class="fa-solid fa-arrows-rotate me-2 text-success"></i>Assinaturas <small class="text-muted">(gastos recorrentes no cartão — entram na fatura na data de cobrança)</small></h5>
            @if ($assinaturas->where('ativo', true)->count() > 0)
                <span class="badge bg-success">R$ {{ number_format($totalAssinaturas, 2, ',', '.') }}/mês ativos</span>
            @endif
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('assinaturas.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label small">Nome *</label>
                    <input type="text" name="nome" class="form-control form-control-sm" placeholder="Ex.: Netflix" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Cartão *</label>
                    <select name="cartao_id" class="form-select form-select-sm" required>
                        @foreach (auth()->user()->cartoes()->where('ativo', true)->orderBy('nome')->get() as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Categoria *</label>
                    <select name="categoria_id" class="form-select form-select-sm" required>
                        @foreach (auth()->user()->categorias()->where('tipo', 'despesa')->orderBy('nome')->get() as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Valor *</label>
                    <input type="number" step="0.01" min="0.01" name="valor" class="form-control form-control-sm" placeholder="R$" required>
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Dia</label>
                    <input type="number" min="1" max="31" name="dia_cobranca" class="form-control form-control-sm" placeholder="15">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Início</label>
                    <input type="month" name="data_inicio" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="ativo" value="1" id="assAtiva" checked>
                        <label class="form-check-label small" for="assAtiva">Ativa</label>
                    </div>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-sm btn-success w-100"><i class="fa-solid fa-plus me-1"></i>Salvar</button>
                </div>
            </form>
            <hr>
            @forelse ($assinaturas as $ass)
                <div class="d-flex justify-content-between align-items-center py-1 {{ $ass->ativo ? '' : 'opacity-50' }}">
                    <div>
                        <strong>{{ $ass->nome }}</strong>
                        <span class="text-muted small"> · {{ $ass->cartao?->nome }} · {{ $ass->categoria?->nome }} · R$ {{ number_format($ass->valor, 2, ',', '.') }} @if ($ass->dia_cobranca) · dia {{ $ass->dia_cobranca }} @endif</span>
                        @if ($ass->observacao)<div class="small text-muted">{{ $ass->observacao }}</div>@endif
                    </div>
                    <div class="text-nowrap">
                        <form method="POST" action="{{ route('assinaturas.update', $ass) }}" class="d-inline">
                            @csrf @method('PUT')
                            <input type="hidden" name="nome" value="{{ $ass->nome }}">
                            <input type="hidden" name="cartao_id" value="{{ $ass->cartao_id }}">
                            <input type="hidden" name="categoria_id" value="{{ $ass->categoria_id }}">
                            <input type="hidden" name="valor" value="{{ $ass->valor }}">
                            <input type="hidden" name="dia_cobranca" value="{{ $ass->dia_cobranca }}">
                            <input type="hidden" name="data_inicio" value="{{ $ass->data_inicio?->format('Y-m') }}">
                            <input type="hidden" name="ativo" value="{{ $ass->ativo ? 0 : 1 }}">
                            <button class="btn btn-sm {{ $ass->ativo ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $ass->ativo ? 'Desativar (para de gerar cobranças futuras)' : 'Ativar' }}">
                                <i class="fa-solid {{ $ass->ativo ? 'fa-pause' : 'fa-play' }}"></i> {{ $ass->ativo ? 'Desativar' : 'Ativar' }}
                            </button>
                        </form>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editAss{{ $ass->id }}"><i class="fa-solid fa-pen"></i></button>
                        <form method="POST" action="{{ route('assinaturas.destroy', $ass) }}" class="d-inline" onsubmit="return confirm('Excluir assinatura {{ $ass->nome }} e seus lançamentos?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                </div>
                <div class="collapse" id="editAss{{ $ass->id }}">
                    <form method="POST" action="{{ route('assinaturas.update', $ass) }}" class="row g-2 p-2 bg-body-tertiary mb-2">
                        @csrf @method('PUT')
                        <div class="col-md-2"><input type="text" name="nome" class="form-control form-control-sm" value="{{ $ass->nome }}" required></div>
                        <div class="col-md-2">
                            <select name="cartao_id" class="form-select form-select-sm">
                                @foreach (auth()->user()->cartoes()->orderBy('nome')->get() as $c)
                                    <option value="{{ $c->id }}" @selected($ass->cartao_id === $c->id)>{{ $c->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="categoria_id" class="form-select form-select-sm">
                                @foreach (auth()->user()->categorias()->where('tipo', 'despesa')->orderBy('nome')->get() as $cat)
                                    <option value="{{ $cat->id }}" @selected($ass->categoria_id === $cat->id)>{{ $cat->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1"><input type="number" step="0.01" min="0.01" name="valor" class="form-control form-control-sm" value="{{ $ass->valor }}" required></div>
                        <div class="col-md-1"><input type="number" min="1" max="31" name="dia_cobranca" class="form-control form-control-sm" value="{{ $ass->dia_cobranca }}"></div>
                        <div class="col-md-1"><input type="month" name="data_inicio" class="form-control form-control-sm" value="{{ $ass->data_inicio?->format('Y-m') }}"></div>
                        <div class="col-md-1">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="assAtiva{{ $ass->id }}" @checked($ass->ativo)>
                                <label class="form-check-label small" for="assAtiva{{ $ass->id }}">Ativa</label>
                            </div>
                        </div>
                        <div class="col-md-1"><button class="btn btn-sm btn-primary w-100"><i class="fa-solid fa-check"></i></button></div>
                    </form>
                </div>
            @empty
                <div class="alert alert-info mb-0">Nenhuma assinatura cadastrada. Assinaturas geram um lançamento mensal no cartão escolhido.</div>
            @endforelse
        </div>
    </div>

    {{-- Ajustes de fatura --}}
    <div class="card shadow-sm mt-2">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fa-solid fa-sliders me-2 text-warning"></i>Ajustes de fatura <small class="text-muted">(informe o valor cheio da fatura do banco — os planos do cartão somam sozinhos na dashboard)</small></h5>
        </div>
        <div class="card-body">
            @forelse ($faturasAjuste as $cartaoNome => $faturas)
                <form method="POST" action="{{ route('cartoes.ajustes.store') }}" class="form-ajuste-fatura row g-2 align-items-end pb-3 mb-3 border-bottom" data-limite="{{ now()->addMonths(2)->format('Y-m') }}">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label small w-100">{{ $cartaoNome }} — Fatura *
                            <span class="fatura-ajuste-atual badge bg-light text-dark float-end fw-normal"></span>
                        </label>
                        <select name="fatura" class="fatura-ajuste-select form-select form-select-sm" required>
                            @foreach ($faturas as $f)
                                <option value="{{ $f->cartao_id }}|{{ $f->chave }}" data-total="{{ number_format($f->total, 2, ',', '.') }}">
                                    {{ \Carbon\Carbon::create($f->ano, $f->mes, 1)->translatedFormat('F') }} / {{ $f->ano }} — R$ {{ number_format($f->total, 2, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Valor da fatura (R$) *</label>
                        <input type="text" name="valor" class="fatura-ajuste-valor form-control form-control-sm" placeholder="R$ 0,00" inputmode="decimal" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">&nbsp;</label>
                        <div>
                            <button class="btn btn-sm btn-warning text-dark fw-bold px-2 py-1"><i class="fa-solid fa-check me-1"></i>Ajustar fatura</button>
                        </div>
                    </div>
                </form>
            @empty
                <div class="alert alert-light text-muted mb-0"><i class="fa-solid fa-credit-card me-1"></i>Nenhuma fatura em aberto para ajustar.</div>
            @endforelse
            @if ($ajustes->isNotEmpty())
                <hr>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Cartão</th>
                                <th>Fatura</th>
                                <th>Descrição</th>
                                <th class="text-end">Valor</th>
                                <th class="text-end">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ajustes as $ajuste)
                                <tr>
                                    <td>{{ $ajuste->cartao?->nome }}</td>
                                    <td>{{ \Carbon\Carbon::create()->month((int) substr($ajuste->fatura_key, 5, 2))->translatedFormat('M') . '/' . substr($ajuste->fatura_key, 0, 4) }}</td>
                                    <td>
                                        {{ $ajuste->descricao }}
                                        @if ($ajuste->observacao)
                                            <div class="small text-muted">{{ $ajuste->observacao }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end {{ $ajuste->valor >= 0 ? 'text-danger' : 'text-success' }}">{{ $ajuste->valor >= 0 ? '+' : '' }}R$ {{ number_format($ajuste->valor, 2, ',', '.') }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('cartoes.ajustes.destroy', $ajuste) }}" class="d-inline" onsubmit="return confirm('Remover ajuste?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Projeção de parcelas --}}
    <div class="card shadow-sm mt-2">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title"><i class="fa-solid fa-calendar-check me-2 text-primary"></i>Fatura prevista (parcelas dos próximos 12 meses)</h5>
            <span class="small text-muted">Atualizado a cada acesso — parcelas lançadas afetam os meses à frente</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Cartão</th>
                        @foreach ($mesesProjecao as $p)
                            <th class="text-center {{ $p['mes'] === now()->month && $p['ano'] === now()->year ? 'table-primary' : '' }}">{{ $p['rotulo'] }}</th>
                        @endforeach
                        <th class="text-center">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($totais as $t)
                        <tr>
                            <td><strong>{{ $t['cartao']->nome }}</strong></td>
                            @php $totalCartao = 0; @endphp
                            @foreach ($mesesProjecao as $p)
                                @php $valor = $projecao[$t['cartao']->id][$p['ano'] . '-' . $p['mes']] ?? 0; $totalCartao += $valor; @endphp
                                <td class="text-end {{ $p['mes'] === now()->month && $p['ano'] === now()->year ? 'table-primary' : '' }}">
                                    {{ $valor ? 'R$ ' . number_format($valor, 2, ',', '.') : '—' }}
                                </td>
                            @endforeach
                            <td class="text-end fw-bold">R$ {{ number_format($totalCartao, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="table-secondary">
                        <td><strong>Total geral</strong></td>
                        @foreach ($mesesProjecao as $p)
                            @php $coluna = collect($totais)->sum(fn ($t) => $projecao[$t['cartao']->id][$p['ano'] . '-' . $p['mes']] ?? 0); @endphp
                            <td class="text-end fw-bold">{{ $coluna ? 'R$ ' . number_format($coluna, 2, ',', '.') : '—' }}</td>
                        @endforeach
                        <td class="text-end fw-bold">R$ {{ number_format(collect($totais)->sum(fn ($t) => collect($mesesProjecao)->sum(fn ($p) => $projecao[$t['cartao']->id][$p['ano'] . '-' . $p['mes']] ?? 0)), 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.form-ajuste-fatura').forEach(function (form) {
            const select = form.querySelector('.fatura-ajuste-select');
            const valor = form.querySelector('.fatura-ajuste-valor');
            const atual = form.querySelector('.fatura-ajuste-atual');
            if (!select) return;

            function aplicarMascara(el) {
                const digits = el.value.replace(/\D/g, '').slice(0, 12);
                const num = parseInt(digits || '0', 10);
                el.value = (num / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            }

            function atualizarValor() {
                const opt = select.selectedOptions[0];
                if (!opt || !opt.dataset.total) {
                    valor.value = '';
                    atual.textContent = '';
                    return;
                }
                valor.value = opt.dataset.total;
                aplicarMascara(valor);
                atual.textContent = 'Valor atual da fatura: R$ ' + opt.dataset.total;
            }

            select.addEventListener('change', atualizarValor);
            valor.addEventListener('input', function () { aplicarMascara(valor); });
            atualizarValor();

            form.addEventListener('submit', function (e) {
                const chave = select.value.split('|')[1] || '';
                const limite = form.dataset.limite || '';
                if (chave && limite && chave > limite) {
                    const opt = select.selectedOptions[0];
                    const nome = opt ? opt.textContent.trim() : chave;
                    if (!confirm('Atenção: "' + nome + '" é uma fatura futura (além de ' + limite + '). Confirma registrar este valor?')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
@endpush
