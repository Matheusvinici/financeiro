@extends('layouts.app')

@section('title', 'Contas a Pagar')
@section('page-title', 'Contas a Pagar (compromissos de longo prazo)')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-plus me-2"></i>Nova conta a pagar</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('contas-pagar.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small">Descrição *</label>
                    <input type="text" name="descricao" class="form-control" placeholder="Ex.: Financiamento Pedra Linda, Dívida Sogra" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Valor total (R$) *</label>
                    <input type="number" step="0.01" min="0.01" name="valor_total" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Vencimento (opcional)</label>
                    <input type="date" name="data_vencimento" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Categoria</label>
                    <select name="categoria_id" class="form-select">
                        <option value="">— Nenhuma —</option>
                        @foreach (auth()->user()->categorias()->where('tipo', 'despesa')->get() as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100"><i class="fa-solid fa-check me-1"></i>Salvar</button>
                </div>
                <div class="col-md-8">
                    <textarea name="observacao" class="form-control" rows="1" placeholder="Observação (opcional)"></textarea>
                </div>
            </form>
        </div>
    </div>

    @if ($totalAberto > 0)
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            Total pendente: <strong>R$ {{ number_format($totalAberto, 2, ',', '.') }}</strong>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-list me-2"></i>Minhas contas</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Vencimento</th>
                            <th class="text-end">Valor total</th>
                            <th class="text-end">Pago</th>
                            <th class="text-end">Restante</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contas as $conta)
                            <tr class="{{ $conta->status === 'pago' ? 'opacity-50' : '' }}">
                                <td>
                                    <strong>{{ $conta->descricao }}</strong>
                                    @if ($conta->observacao)<div class="small text-muted">{{ $conta->observacao }}</div>@endif
                                    @if ($conta->quem_pagou || $conta->descricao_pagamento)
                                        <div class="small text-success">
                                            <i class="fa-solid fa-circle-check me-1"></i>
                                            Pago{{ $conta->quem_pagou ? ' por ' . $conta->quem_pagou : '' }}{{ $conta->descricao_pagamento ? ' — ' . $conta->descricao_pagamento : '' }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $conta->categoria?->nome ?? '—' }}</td>
                                <td>{{ $conta->data_vencimento?->translatedFormat('d/m/Y') ?? 'Sem data' }}</td>
                                <td class="text-end">R$ {{ number_format($conta->valor_total, 2, ',', '.') }}</td>
                                <td class="text-end text-success">R$ {{ number_format($conta->valor_pago, 2, ',', '.') }}</td>
                                <td class="text-end fw-bold">R$ {{ number_format($conta->valor_restante, 2, ',', '.') }}</td>
                                <td>
                                    @if ($conta->status === 'pago')
                                        <span class="badge bg-success">Pago</span>
                                    @elseif ($conta->status === 'parcial')
                                        <span class="badge bg-warning text-dark">Parcial</span>
                                    @else
                                        <span class="badge bg-danger">Em aberto</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($conta->status !== 'pago')
                                        <button class="btn btn-sm btn-success" data-bs-toggle="collapse" data-bs-target="#pagar{{ $conta->id }}" title="Registrar pagamento"><i class="fa-solid fa-money-bill-wave me-1"></i>Pagar</button>
                                    @endif
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit{{ $conta->id }}"><i class="fa-solid fa-pen me-1"></i>Editar</button>
                                    <form method="POST" action="{{ route('contas-pagar.destroy', $conta) }}" class="d-inline" onsubmit="return confirm('Excluir conta?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash me-1"></i>Excluir</button>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="8" class="p-0">
                                    <div class="collapse" id="pagar{{ $conta->id }}">
                                        <form method="POST" action="{{ route('contas-pagar.pagar', $conta) }}" class="row g-2 p-3 bg-body-tertiary">
                                            @csrf
                                            <div class="col-md-2">
                                                <label class="form-label small">Valor a pagar (R$) *</label>
                                                <input type="number" step="0.01" min="0.01" name="valor" class="form-control form-control-sm" value="{{ $conta->valor_restante }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Descrição do pagamento</label>
                                                <input type="text" name="descricao_pagamento" class="form-control form-control-sm" placeholder="Ex.: transferência Pix, depósito, dinheiro">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Quem pagou</label>
                                                <input type="text" name="quem_pagou" class="form-control form-control-sm" placeholder="Ex.: Matheus, Mari">
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button class="btn btn-sm btn-success w-100"><i class="fa-solid fa-money-bill-wave me-1"></i>Confirmar</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="8" class="p-0">
                                    <div class="collapse" id="edit{{ $conta->id }}">
                                        <form method="POST" action="{{ route('contas-pagar.update', $conta) }}" class="row g-2 p-3 bg-body-tertiary">
                                            @csrf @method('PUT')
                                            <div class="col-md-3">
                                                <input type="text" name="descricao" class="form-control form-control-sm" value="{{ $conta->descricao }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" step="0.01" name="valor_total" class="form-control form-control-sm" value="{{ $conta->valor_total }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="date" name="data_vencimento" class="form-control form-control-sm" value="{{ $conta->data_vencimento?->format('Y-m-d') }}">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="observacao" class="form-control form-control-sm" value="{{ $conta->observacao }}" placeholder="Obs.">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="descricao_pagamento" class="form-control form-control-sm" value="{{ $conta->descricao_pagamento }}" placeholder="Desc. pagamento">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="quem_pagou" class="form-control form-control-sm" value="{{ $conta->quem_pagou }}" placeholder="Quem pagou">
                                            </div>
                                            <div class="col-md-2">
                                                <button class="btn btn-sm btn-primary w-100"><i class="fa-solid fa-check"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5">Nenhuma conta cadastrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
