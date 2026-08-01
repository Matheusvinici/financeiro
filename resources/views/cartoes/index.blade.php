@extends('layouts.app')

@section('title', 'Cartões')
@section('page-title', 'Cartões')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-plus me-2"></i>Novo cartão</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('cartoes.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label small">Nome</label>
                    <input type="text" name="nome" class="form-control" placeholder="Nubank" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="credito">Crédito</option>
                        <option value="debito">Débito</option>
                        <option value="credito_debito">Crédito/Débito</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Bandeira</label>
                    <input type="text" name="bandeira" class="form-control" placeholder="Visa, Master...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Limite (R$)</label>
                    <input type="number" step="0.01" min="0" name="limite" class="form-control" value="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Fechamento</label>
                    <input type="number" min="1" max="31" name="dia_fechamento" class="form-control" placeholder="Dia">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Venc.</label>
                    <input type="number" min="1" max="31" name="dia_vencimento" class="form-control" placeholder="Dia">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100"><i class="fa-solid fa-check"></i></button>
                </div>
            </form>
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
                            <div class="col-6">
                                <span class="text-muted small">Gasto no mês</span>
                                <h6 class="text-danger">R$ {{ number_format($t['gasto_mes'], 2, ',', '.') }}</h6>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small">Limite</span>
                                <h6 class="text-primary">R$ {{ number_format($t['cartao']->limite, 2, ',', '.') }}</h6>
                            </div>
                        </div>
                        @if ($t['cartao']->dia_fechamento || $t['cartao']->dia_vencimento)
                            <div class="small text-muted">
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
@endsection
