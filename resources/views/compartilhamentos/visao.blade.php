@extends('layouts.app')

@section('title', 'Compartilhados comigo')
@section('page-title', 'Finanças compartilhadas comigo')

@section('content')
    @forelse ($compartilhamentos as $c)
        @php
            $categorias = $c->dono->categorias()
                ->when($c->categoria_ids, fn ($q) => $q->whereIn('id', $c->categoria_ids))
                ->get();
            $ids = $categorias->pluck('id');
            $receitas = $c->dono->lancamentos()->where('tipo', 'receita')
                ->whereYear('data', now()->year)->whereMonth('data', now()->month)
                ->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('categoria_id', $ids))
                ->sum('valor');
            $despesas = $c->dono->lancamentos()->where('tipo', 'despesa')
                ->whereYear('data', now()->year)->whereMonth('data', now()->month)
                ->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('categoria_id', $ids))
                ->sum('valor');
        @endphp
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1"><i class="fa-solid fa-user me-2"></i>{{ $c->dono->name }}</h5>
                        <div class="small text-muted">
                            Categorias visíveis:
                            @if ($categorias->isNotEmpty())
                                @foreach ($categorias as $cat)
                                    <span class="badge bg-light text-dark border">{{ $cat->nome }}</span>
                                @endforeach
                            @else
                                <span class="badge bg-success">Todas</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Este mês (só leitura)</div>
                        <div class="text-success fw-bold">+R$ {{ number_format($receitas, 2, ',', '.') }}</div>
                        <div class="text-danger fw-bold">-R$ {{ number_format($despesas, 2, ',', '.') }}</div>
                        <a href="{{ route('compartilhamentos.ver', $c) }}" class="btn btn-sm btn-outline-info mt-1"><i class="fa-solid fa-eye me-1"></i>Ver lançamentos</a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info">
            <i class="fa-solid fa-circle-info me-1"></i>
            Nenhuma pessoa compartilhou finanças com você.
        </div>
    @endforelse
@endsection
