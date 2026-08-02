@extends('layouts.app')

@section('title', 'Compartilhar')
@section('page-title', 'Compartilhamento de acesso')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title"><i class="fa-solid fa-user-plus me-2 text-primary"></i>Cadastrar usuário e vincular papel</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('compartilhamentos.store') }}">
                @csrf
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small">Nome completo *</label>
                        <input type="text" name="name" class="form-control" placeholder="Ex.: Maria Silva" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">E-mail *</label>
                        <input type="email" name="email" class="form-control" placeholder="pessoa@email.com" value="{{ old('email') }}" required>
                        <div class="form-text">Se já existir, o usuário é apenas vinculado.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Senha * (se for novo usuário)</label>
                        <input type="password" name="password" class="form-control" placeholder="Mín. 8 caracteres" required>
                    </div>
                    @error('email')<div class="col-12 text-danger small">{{ $message }}</div>@enderror
                    @error('password')<div class="col-12 text-danger small">{{ $message }}</div>@enderror
                </div>

                <label class="form-label small">O que essa pessoa pode ver? (deixe em branco = todas as categorias)</label>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach ($categorias as $c)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="categoria_ids[]" value="{{ $c->id }}" id="cat{{ $c->id }}"
                                   @checked(in_array($c->id, old('categoria_ids', [])))>
                            <label class="form-check-label" for="cat{{ $c->id }}">
                                <i class="fa-solid {{ $c->icone ?? 'fa-tag' }}" style="color: {{ $c->cor ?? '#0d6efd' }}"></i>
                                {{ $c->nome }}
                            </label>
                        </div>
                    @endforeach
                </div>

                <button class="btn btn-primary"><i class="fa-solid fa-share me-1"></i>Cadastrar e compartilhar</button>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="fa-solid fa-share-from-square me-2 text-primary"></i>Compartilhamentos que eu fiz</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr><th>Pessoa</th><th>Papel / Pode ver</th><th class="text-end">Ações</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($enviados as $e)
                                    @php
                                        $role = \Spatie\Permission\Models\Role::where('name', "convidado-{$e->dono_user_id}-{$e->convidado_user_id}")->first();
                                    @endphp
                                    <tr>
                                        <td><i class="fa-solid fa-user me-1"></i>{{ $e->convidado->name }}<div class="small text-muted">{{ $e->convidado->email }}</div></td>
                                        <td>
                                            @if ($role)
                                                <span class="badge bg-primary mb-1">{{ $role->name }}</span><br>
                                            @endif
                                            @if ($e->categoria_ids)
                                                @foreach ($e->categoria_ids as $cid)
                                                    @php $cat = $categorias->firstWhere('id', $cid); @endphp
                                                    @if ($cat)<span class="badge bg-light text-dark border">{{ $cat->nome }}</span>@endif
                                                @endforeach
                                            @else
                                                <span class="badge bg-success">Todas as categorias</span>
                                            @endif
                                            <span class="badge bg-secondary">só leitura</span>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('compartilhamentos.destroy', $e) }}" onsubmit="return confirm('Remover compartilhamento?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash me-1"></i>Remover</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Você ainda não compartilhou nada.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="fa-solid fa-eye me-2 text-primary"></i>Finanças compartilhadas comigo</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr><th>Pessoa</th><th>Posso ver</th><th class="text-end">Ações</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($recebidos as $r)
                                    <tr>
                                        <td><i class="fa-solid fa-user me-1"></i>{{ $r->dono->name }}<div class="small text-muted">{{ $r->dono->email }}</div></td>
                                        <td>
                                            @if ($r->categoria_ids)
                                                @foreach ($r->categoria_ids as $cid)
                                                    @php $cat = $r->dono->categorias()->find($cid); @endphp
                                                    @if ($cat)<span class="badge bg-light text-dark border">{{ $cat->nome }}</span>@endif
                                                @endforeach
                                            @else
                                                <span class="badge bg-success">Todas as categorias</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('compartilhamentos.ver', $r) }}" class="btn btn-sm btn-outline-info"><i class="fa-solid fa-eye me-1"></i>Ver</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Ninguém compartilhou finanças com você ainda.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
