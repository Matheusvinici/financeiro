@extends('layouts.app')

@section('title', 'Configurações')
@section('page-title', 'Configurações financeiras')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-gear me-2"></i>Parâmetros usados no dashboard e no simulador de parcelas</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('configuracoes.update') }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Renda fixa mensal (R$)</label>
                        <input type="number" step="0.01" min="0" name="renda_fixa" class="form-control"
                               value="{{ $config['renda_fixa'] }}" placeholder="Ex.: 6397 (se vazio, usa a média dos últimos 6 meses)">
                        <div class="form-text">Usada nos alertas de comprometimento da renda e no simulador.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Meta de poupança mensal (R$)</label>
                        <input type="number" step="0.01" min="0" name="meta_poupanca" class="form-control" value="{{ $config['meta_poupanca'] }}">
                        <div class="form-text">Quanto você quer guardar por mês.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Limite de comprometimento da renda (%)</label>
                        <input type="number" min="1" max="100" name="percentual_alerta" class="form-control" value="{{ $config['percentual_alerta'] }}" required>
                        <div class="form-text">Acima deste %, o sistema alerta que a renda está comprometida.</div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
