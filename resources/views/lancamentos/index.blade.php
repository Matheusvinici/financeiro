@extends('layouts.app')

@section('title', 'Lançamentos')
@section('page-title', 'Lançamentos')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('lancamentos.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Mês</label>
                    <select name="mes" class="form-select">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" @selected(request('mes', now()->month) == $m)>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ano</label>
                    <select name="ano" class="form-select">
                        @foreach (range(now()->year, now()->year - 7) as $y)
                            <option value="{{ $y }}" @selected(request('ano', now()->year) == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="receita" @selected(request('tipo') === 'receita')>Receitas</option>
                        <option value="despesa" @selected(request('tipo') === 'despesa')>Despesas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Categoria</label>
                    <select name="categoria_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($categorias as $c)
                            <option value="{{ $c->id }}" @selected(request('categoria_id') == $c->id)>{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Cartão</label>
                    <select name="cartao_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($cartoes as $cartao)
                            <option value="{{ $cartao->id }}" @selected(request('cartao_id') == $cartao->id)>{{ $cartao->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Busca</label>
                    <input type="text" name="busca" class="form-control" value="{{ request('busca') }}" placeholder="Buscar...">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button class="btn btn-primary flex-grow-1" title="Filtrar"><i class="fa-solid fa-magnifying-glass me-1"></i>Filtrar</button>
                    <a href="{{ route('lancamentos.index') }}" class="btn btn-outline-secondary" title="Limpar"><i class="fa-solid fa-rotate-left"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-wallet me-2"></i>Lançamentos</h5>
            <a href="{{ route('lancamentos.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Novo lançamento</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Forma de pagamento</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lancamentos as $l)
                            <tr>
                                <td class="text-nowrap">{{ $l->data->translatedFormat('d/m/Y') }}</td>
                                <td>
                                    {{ $l->descricao }}
                                    @if ($l->isParcela())<span class="badge bg-secondary ms-1">parcela {{ $l->parcela_atual }}/{{ $l->qtd_parcelas }}</span>@endif
                                    @if ($l->recorrente)<span class="badge bg-info ms-1">fixo</span>@endif
                                    @if ($l->tipo === 'despesa' && !$l->pago && $l->forma_pagamento !== 'cartao')<span class="badge bg-danger ms-1">a pagar</span>@endif
                                    @if (!$l->abate_saldo)<span class="badge bg-info text-dark ms-1">não abate</span>@endif
                                    @if ($l->observacao)<div class="small text-muted">{{ $l->observacao }}</div>@endif
                                </td>
                                <td>
                                    <i class="fa-solid {{ $l->categoria?->icone ?? 'fa-tag' }} me-1" style="color: {{ $l->categoria?->cor ?? '#6c757d' }}"></i>
                                    {{ $l->categoria?->nome ?? '—' }}
                                    @if ($l->subcategoria)<span class="text-muted">/ {{ $l->subcategoria->nome }}</span>@endif
                                </td>
                                <td>
                                    @if ($l->cartao)<span class="badge bg-dark">{{ $l->cartao->nome }}</span>
                                    @else <span class="text-muted">{{ $l->forma_pagamento ? $l->forma_label : '—' }}</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold {{ $l->tipo === 'receita' ? 'text-success' : 'text-danger' }}">
                                    {{ $l->tipo === 'receita' ? '+' : '-' }}R$ {{ number_format($l->valor, 2, ',', '.') }}
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('lancamentos.edit', $l) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>Editar</a>
                                    <form method="POST" action="{{ route('lancamentos.destroy', $l) }}" class="d-inline" onsubmit="return confirm('Excluir este lançamento{{ $l->isParcela() ? ' e toda a série de parcelas' : '' }}?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash me-1"></i>Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-folder-open"></i>
                                        <p>Nenhum lançamento encontrado.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($lancamentos->hasPages())
                <div class="card-footer">{{ $lancamentos->links() }}</div>
            @endif
        </div>
    </div>
@endsection
