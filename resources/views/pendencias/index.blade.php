@extends('layouts.app')

@section('title', 'Pendências do mês')
@section('page-title', 'Pendências do mês')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Mês</label>
                    <select class="form-select" onchange="this.form.submit()" form="filtro-pendencias" name="mes">
                        <option value="todos" @selected($mes === 'todos')>Todos os meses do ano</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" @selected($mes === $m)>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ano</label>
                    <select class="form-select" onchange="this.form.submit()" form="filtro-pendencias" name="ano">
                        @foreach ($mesesDisponiveis->pluck('ano')->unique()->sortDesc() as $y)
                            <option value="{{ $y }}" @selected($ano === $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Períodos com lançamentos</label>
                    <select class="form-select" onchange="if(this.value){ window.location.href = this.value; }">
                        <option value="">— Ir direto para —</option>
                        @foreach ($mesesDisponiveis as $p)
                            <option value="{{ route('pendencias.index', ['mes' => $p['mes'], 'ano' => $p['ano']]) }}" @selected($mes === $p['mes'] && $ano === $p['ano'])>
                                {{ \Carbon\Carbon::create()->month($p['mes'])->translatedFormat('F') . '/' . $p['ano'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('pendencias.index', ['mes' => $hoje->month, 'ano' => $hoje->year]) }}" class="btn btn-outline-secondary w-100"><i class="fa-solid fa-calendar-day me-1"></i>Mês atual</a>
                </div>
            </div>
            <form id="filtro-pendencias" method="GET" action="{{ route('pendencias.index') }}" class="d-none"></form>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card red">
            <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-value">R$ {{ number_format($totalPendente, 2, ',', '.') }}</div>
            <div class="stat-label">Total a pagar no mês</div>
            <div class="stat-sub">{{ $pendencias->count() }} conta(s) pendente(s)</div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-value">R$ {{ number_format($totalUrgente, 2, ',', '.') }}</div>
            <div class="stat-label">Vencendo nos próximos 3 dias</div>
            <div class="stat-sub">Inclui contas vencidas</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
            <div class="stat-value">R$ {{ number_format($totalAbate, 2, ',', '.') }}</div>
            <div class="stat-label">Sai do meu dinheiro</div>
            <div class="stat-sub">Contas e faturas que abatem do saldo</div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fa-solid fa-arrows-rotate"></i></div>
            <div class="stat-value">R$ {{ number_format($totalNaoAbate, 2, ',', '.') }}</div>
            <div class="stat-label">Não abatem do saldo</div>
            <div class="stat-sub">Pagas por terceiros</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-value">R$ {{ number_format($totalPago, 2, ',', '.') }}</div>
            <div class="stat-label">Total pago no mês</div>
            <div class="stat-sub">Contas e faturas quitadas no período</div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-bell me-2"></i>O que falta pagar <span class="text-muted small">(ordenado pela data mais próxima)</span></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th class="text-end">Valor</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendentes as $l)
                            @if ($l->is_fatura ?? false)
                                @php
                                    $venceHoje = $l->data->copy()->startOfDay()->eq($hoje->copy()->startOfDay());
                                    $vencida = $l->data->copy()->startOfDay()->lt($hoje->copy()->startOfDay());
                                    $proxima = $l->data->copy()->startOfDay()->between($hoje->copy()->startOfDay()->addDay(), $hoje->copy()->addDays(3)->startOfDay());
                                    $linha = $vencida || $venceHoje ? 'table-danger-subtle' : ($proxima ? 'table-warning-subtle' : 'table-light');
                                @endphp
                                <tr class="{{ $linha }}">
                                    <td class="text-nowrap">
                                        <strong>{{ $l->data->translatedFormat('d/m') }}</strong>
                                        <span class="d-block small text-muted">{{ $l->data->translatedFormat('D') }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $l->fatura_cartao->nome }}</strong>
                                        <span class="badge bg-primary ms-1" title="Fatura do cartão de {{ $l->fatura_cartao->tipo_label }}">fatura {{ $l->fatura_cartao->tipo_label }}</span>
                                        <span class="d-block small text-muted">{{ $l->fatura_qtd }} compra(s) no cartão</span>
                                    </td>
                                    <td>—</td>
                                    <td class="text-end fw-bold">R$ {{ number_format($l->fatura_total, 2, ',', '.') }}</td>
                                    <td>
                                        @if ($vencida || $venceHoje)
                                            <span class="badge bg-danger">Fatura vencida</span>
                                        @elseif ($proxima)
                                            <span class="badge bg-warning text-dark">Fatura vence em breve</span>
                                        @else
                                            <span class="badge bg-primary">Fatura a pagar</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <form method="POST" action="{{ route('pendencias.fatura', $l->fatura_cartao) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="mes" value="{{ $l->fatura_mes }}">
                                            <input type="hidden" name="ano" value="{{ $l->fatura_ano }}">
                                            <button class="btn btn-sm btn-success" title="Marcar fatura como paga"><i class="fa-solid fa-check me-1"></i>Pagar</button>
                                        </form>
                                        <a href="{{ route('cartoes.index') }}" class="btn btn-sm btn-outline-primary" title="Ver cartões"><i class="fa-solid fa-credit-card"></i></a>
                                    </td>
                                </tr>
                            @else
                                @php
                                    $venceHoje = $l->data && $l->data->copy()->startOfDay()->eq($hoje->copy()->startOfDay());
                                    $vencida = $l->data && $l->data->copy()->startOfDay()->lt($hoje->copy()->startOfDay());
                                    $proxima = $l->data && $l->data->copy()->startOfDay()->between($hoje->copy()->startOfDay()->addDay(), $hoje->copy()->addDays(3)->startOfDay());
                                    $linha = $vencida || $venceHoje ? 'table-danger-subtle' : ($proxima ? 'table-warning-subtle' : 'table-light');
                                @endphp
                                <tr class="{{ $linha }}">
                                    <td class="text-nowrap">
                                        <strong>{{ $l->data?->translatedFormat('d/m') }}</strong>
                                        <span class="d-block small text-muted">{{ $l->data?->translatedFormat('D') }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $l->descricao }}</strong>
                                        @if ($l->isParcela())<span class="badge bg-secondary ms-1">parcela {{ $l->parcela_atual }}/{{ $l->qtd_parcelas }}</span>@endif
                                        @if (!$l->abate_saldo)<span class="badge bg-info text-dark ms-1" title="Pago por você mas não sai do seu saldo">não abate</span>@endif
                                        @if ($l->observacao)<div class="small text-muted">{{ $l->observacao }}</div>@endif
                                    </td>
                                    <td>{{ $l->categoria?->nome ?? '—' }}</td>
                                    <td class="text-end fw-bold">R$ {{ number_format($l->valor, 2, ',', '.') }}</td>
                                    <td>
                                        @if ($vencida || $venceHoje)
                                            <span class="badge bg-danger">Venceu hoje / vencida</span>
                                        @elseif ($proxima)
                                            <span class="badge bg-warning text-dark">Vence em breve</span>
                                        @else
                                            <span class="badge bg-secondary">A vencer</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <form method="POST" action="{{ route('pendencias.pagar', $l) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" title="Marcar como pago"><i class="fa-solid fa-check me-1"></i>Pagar</button>
                                        </form>
                                        <form method="POST" action="{{ route('pendencias.abate', $l) }}" class="d-inline" title="{{ $l->abate_saldo ? 'Marcar como não abate (pago por terceiros)' : 'Marcar como abate do saldo' }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-info"><i class="fa-solid fa-arrows-rotate"></i></button>
                                        </form>
                                        <a href="{{ route('lancamentos.edit', $l) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                        <form method="POST" action="{{ route('lancamentos.destroy', $l) }}" class="d-inline" onsubmit="return confirm('Excluir este lançamento?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-circle-check fa-2x mb-2 d-block text-success"></i>
                                    Nada pendente {{ $mesAtual ? 'neste mês' : 'neste ano' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($faturasCartao->isNotEmpty())
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-credit-card me-2 text-primary"></i>Faturas de cartão <span class="text-muted small">(o que você gastou no período)</span></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Vencimento</th>
                            <th>Cartão</th>
                            <th class="text-end">Total do período</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($faturasCartao->sortBy(fn ($f) => $f['vencimento']) as $f)
                            @php $cartao = $f['cartao']; @endphp
                            <tr class="{{ $f['pago'] ? 'table-success-subtle' : 'table-light' }}">
                                <td class="text-nowrap">
                                    <strong>{{ $f['vencimento']->translatedFormat('d/m') }}</strong>
                                    <span class="d-block small text-muted">{{ $f['vencimento']->translatedFormat('M/Y') }}</span>
                                </td>
                                <td>
                                    <strong>{{ $cartao->nome }}</strong>
                                    <span class="badge bg-{{ $cartao->tipo === 'debito' ? 'secondary' : 'info' }}">{{ $cartao->tipo_label }}</span>
                                    <span class="d-block small text-muted">{{ $f['qtd'] }} compra(s) nesta fatura</span>
                                </td>
                                <td class="text-end fw-bold">R$ {{ number_format($f['total'], 2, ',', '.') }}</td>
                                <td>
                                    @if ($f['pago'])
                                        <span class="badge bg-success">Fatura paga</span>
                                    @else
                                        <span class="badge bg-warning text-dark">A pagar</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if (!$f['pago'])
                                        <form method="POST" action="{{ route('pendencias.fatura', $cartao) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="mes" value="{{ $f['fatura_mes'] }}">
                                            <input type="hidden" name="ano" value="{{ $f['fatura_ano'] }}">
                                            <button class="btn btn-sm btn-success" title="Marcar fatura como paga"><i class="fa-solid fa-check me-1"></i>Pagar fatura</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('cartoes.index') }}" class="btn btn-sm btn-outline-primary" title="Ver cartões"><i class="fa-solid fa-credit-card"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if ($pagas->isNotEmpty())
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-circle-check me-2 text-success"></i>Pagas neste período</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <tbody>
                        @foreach ($pagas as $l)
                            <tr class="table-success-subtle">
                                <td class="text-nowrap"><strong>{{ $l->data?->translatedFormat('d/m') }}</strong></td>
                                <td>
                                    <strong>{{ $l->descricao }}</strong>
                                    @if (!$l->abate_saldo)<span class="badge bg-info text-dark ms-1">não abate</span>@endif
                                </td>
                                <td>{{ $l->categoria?->nome ?? '—' }}</td>
                                <td class="text-end">R$ {{ number_format($l->valor, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap">
                                    <form method="POST" action="{{ route('pendencias.desfazer', $l) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary" title="Voltar para pendências"><i class="fa-solid fa-rotate-left me-1"></i>Desfazer</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
@endsection
