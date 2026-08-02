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
            <div class="stat-label">Total gasto no período</div>
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
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit{{ $t['cartao']->id }}"><i class="fa-solid fa-pen me-1"></i>Editar</button>
                                <form method="POST" action="{{ route('cartoes.destroy', $t['cartao']) }}" onsubmit="return confirm('Excluir cartão?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash me-1"></i>Excluir</button>
                                </form>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row text-center">
                            <div class="col-4">
                                <span class="text-muted small">Gasto no período</span>
                                <h6 class="text-danger">R$ {{ number_format($t['gasto_periodo'], 2, ',', '.') }}</h6>
                            </div>
                            <div class="col-4">
                                <span class="text-muted small">À vista</span>
                                <h6>R$ {{ number_format($t['avista_periodo'], 2, ',', '.') }}</h6>
                            </div>
                            <div class="col-4">
                                <span class="text-muted small">Parcelas</span>
                                <h6 class="text-primary">R$ {{ number_format($t['parcelas_periodo'], 2, ',', '.') }}</h6>
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
