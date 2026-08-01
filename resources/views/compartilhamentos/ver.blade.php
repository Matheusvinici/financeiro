@extends('layouts.app')

@section('title', 'Visão de ' . $dono->name)
@section('page-title', 'Lançamentos de ' . $dono->name . ' (só leitura)')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('compartilhamentos.ver', $compartilhamento) }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Mês</label>
                    <select name="mes" class="form-select">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" @selected($mes == $m)>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ano</label>
                    <select name="ano" class="form-select">
                        @foreach (range(now()->year, now()->year - 7) as $y)
                            <option value="{{ $y }}" @selected($ano == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <button class="btn btn-primary me-2"><i class="fa-solid fa-magnifying-glass me-1"></i>Filtrar</button>
                    <a href="{{ route('compartilhamentos.visao') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="stat-value">R$ {{ number_format($receitas, 2, ',', '.') }}</div>
            <div class="stat-label">Receitas no período</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
            <div class="stat-value">R$ {{ number_format($despesas, 2, ',', '.') }}</div>
            <div class="stat-label">Despesas no período</div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title"><i class="fa-solid fa-wallet me-2 text-primary"></i>Lançamentos visíveis</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lancamentos as $l)
                            <tr>
                                <td>{{ $l->data->translatedFormat('d/m/Y') }}</td>
                                <td>{{ $l->descricao }}
                                    @if ($l->isParcela())<span class="badge bg-secondary">parcela {{ $l->parcela_atual }}/{{ $l->qtd_parcelas }}</span>@endif
                                </td>
                                <td>{{ $l->categoria?->nome }}@if ($l->subcategoria)<span class="text-muted">/ {{ $l->subcategoria->nome }}</span>@endif</td>
                                <td class="text-end fw-bold {{ $l->tipo === 'receita' ? 'text-success' : 'text-danger' }}">
                                    {{ $l->tipo === 'receita' ? '+' : '-' }}R$ {{ number_format($l->valor, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Nenhum lançamento no período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
