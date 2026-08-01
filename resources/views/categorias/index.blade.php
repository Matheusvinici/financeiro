@extends('layouts.app')

@section('title', 'Categorias')
@section('page-title', 'Categorias e Itens')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-plus me-2"></i>Nova categoria</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('categorias.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small">Nome da categoria</label>
                    <input type="text" name="nome" class="form-control" placeholder="Ex.: CASA, LAZER, CARTÕES" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Tipo</label>
                    <select name="tipo" class="form-select" required>
                        <option value="despesa">Despesa (gasto)</option>
                        <option value="receita">Receita (entrada)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Cor</label>
                    <input type="color" name="cor" class="form-control form-control-color" value="#0d6efd">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ícone</label>
                    <input type="text" name="icone" class="form-control" placeholder="fa-house" value="fa-tag">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-check"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="fa-solid fa-arrow-trend-up text-success me-2"></i>Receitas</h5>
                </div>
                <div class="card-body p-0">
                    <x-categorias-lista :categorias="$receitas" tipo="receita" />
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="fa-solid fa-arrow-trend-down text-danger me-2"></i>Despesas</h5>
                </div>
                <div class="card-body p-0">
                    <x-categorias-lista :categorias="$despesas" tipo="despesa" />
                </div>
            </div>
        </div>
    </div>
@endsection
