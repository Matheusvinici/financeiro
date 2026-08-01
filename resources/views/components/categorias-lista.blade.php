@props(['categorias', 'tipo'])

<div class="table-responsive">
    <table class="table table-hover mb-0">
        <tbody>
            @forelse ($categorias as $categoria)
                <tr>
                    <td class="align-middle" style="width: 42px;">
                        <i class="fa-solid {{ $categoria->icone ?? 'fa-tag' }} fa-lg" style="color: {{ $categoria->cor ?? '#0d6efd' }}"></i>
                    </td>
                    <td class="align-middle">
                        <strong>{{ $categoria->nome }}</strong>
                        <div class="small text-muted">
                            @foreach ($categoria->subcategorias as $sub)
                                <span class="badge bg-light text-dark border me-1">
                                    {{ $sub->nome }}
                                    <form method="POST" action="{{ route('subcategorias.destroy', $sub) }}" class="d-inline" onsubmit="return confirm('Remover item?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link p-0 text-danger ms-1"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="align-middle text-end" style="width: 230px;">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#addSub{{ $categoria->id }}" title="Adicionar item">
                            <i class="fa-solid fa-plus me-1"></i>Item
                        </button>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editCat{{ $categoria->id }}" title="Editar">
                            <i class="fa-solid fa-pen me-1"></i>Editar
                        </button>
                        <form method="POST" action="{{ route('categorias.destroy', $categoria) }}" class="d-inline" onsubmit="return confirm('Excluir categoria e seus itens?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Excluir"><i class="fa-solid fa-trash me-1"></i>Excluir</button>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="p-0">
                        <div class="collapse" id="addSub{{ $categoria->id }}">
                            <form method="POST" action="{{ route('categorias.subcategorias.store', $categoria) }}" class="d-flex gap-2 p-2 bg-body-tertiary">
                                @csrf
                                <input type="text" name="nome" class="form-control form-control-sm" placeholder="Nome do item (ex.: Seguro, Energia)" required>
                                <button class="btn btn-sm btn-success flex-shrink-0"><i class="fa-solid fa-check"></i> Adicionar</button>
                            </form>
                        </div>
                        <div class="collapse" id="editCat{{ $categoria->id }}">
                            <form method="POST" action="{{ route('categorias.update', $categoria) }}" class="row g-2 p-2 bg-body-tertiary">
                                @csrf @method('PUT')
                                <div class="col-md-5">
                                    <input type="text" name="nome" class="form-control form-control-sm" value="{{ $categoria->nome }}" required>
                                </div>
                                <div class="col-md-3">
                                    <select name="tipo" class="form-select form-select-sm">
                                        <option value="despesa" @selected($categoria->tipo === 'despesa')>Despesa</option>
                                        <option value="receita" @selected($categoria->tipo === 'receita')>Receita</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="color" name="cor" class="form-control form-control-color" value="{{ $categoria->cor ?? '#0d6efd' }}">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-sm btn-primary w-100"><i class="fa-solid fa-check"></i></button>
                                </div>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted py-4">
                        Nenhuma categoria de {{ $tipo === 'receita' ? 'receita' : 'despesa' }} cadastrada.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
