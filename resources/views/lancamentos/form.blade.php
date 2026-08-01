@extends('layouts.app')

@section('title', $lancamento->exists ? 'Editar lançamento' : 'Novo lançamento')
@section('page-title', $lancamento->exists ? 'Editar lançamento' : 'Novo lançamento')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST"
                  action="{{ $lancamento->exists ? route('lancamentos.update', $lancamento) : route('lancamentos.store') }}">
                @csrf
                @if ($lancamento->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Descrição *</label>
                        <input type="text" name="descricao" class="form-control" value="{{ old('descricao', $lancamento->descricao) }}" placeholder="Ex.: Mercado, Salário, Fatura Nubank" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Valor (R$) *</label>
                        <input type="number" step="0.01" min="0.01" name="valor" class="form-control" value="{{ old('valor', $lancamento->valor) }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data *</label>
                        <input type="date" name="data" class="form-control" value="{{ old('data', $lancamento->data?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" class="form-select" required>
                            <option value="despesa" @selected(old('tipo', $lancamento->tipo) === 'despesa')>Despesa (gasto)</option>
                            <option value="receita" @selected(old('tipo', $lancamento->tipo) === 'receita')>Receita (entrada)</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="recorrente" value="1" id="recorrente" @checked(old('recorrente', $lancamento->recorrente))>
                            <label class="form-check-label" for="recorrente">Gasto fixo (todo mês)</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Categoria</label>
                        <select name="categoria_id" id="categoria_id" class="form-select">
                            <option value="">— Sem categoria —</option>
                            @foreach ($categorias->where('tipo', 'despesa') as $c)
                                <option value="{{ $c->id }}" data-tipo="despesa" @selected(old('categoria_id', $lancamento->categoria_id) == $c->id)>{{ $c->nome }}</option>
                            @endforeach
                            @foreach ($categorias->where('tipo', 'receita') as $c)
                                <option value="{{ $c->id }}" data-tipo="receita" @selected(old('categoria_id', $lancamento->categoria_id) == $c->id)>{{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Item</label>
                        <select name="subcategoria_id" id="subcategoria_id" class="form-select">
                            <option value="">— Sem item —</option>
                            @if ($lancamento->categoria_id)
                                @foreach ($categorias->find($lancamento->categoria_id)?->subcategorias ?? [] as $s)
                                    <option value="{{ $s->id }}" @selected($lancamento->subcategoria_id == $s->id)>{{ $s->nome }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Forma de pagamento</label>
                        <select name="forma_pagamento" id="forma_pagamento" class="form-select">
                            <option value="">— Selecionar —</option>
                            <option value="cartao" @selected(old('forma_pagamento', $lancamento->forma_pagamento) === 'cartao')>Cartão</option>
                            <option value="pix" @selected(old('forma_pagamento', $lancamento->forma_pagamento) === 'pix')>Pix</option>
                            <option value="dinheiro" @selected(old('forma_pagamento', $lancamento->forma_pagamento) === 'dinheiro')>Dinheiro</option>
                            <option value="boleto" @selected(old('forma_pagamento', $lancamento->forma_pagamento) === 'boleto')>Boleto</option>
                            <option value="outros" @selected(old('forma_pagamento', $lancamento->forma_pagamento) === 'outros')>Outros</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="cartao_div">
                        <label class="form-label">Cartão utilizado</label>
                        <select name="cartao_id" id="cartao_id" class="form-select">
                            <option value="">— Nenhum —</option>
                            @foreach ($cartoes as $cartao)
                                <option value="{{ $cartao->id }}" @selected(old('cartao_id', $lancamento->cartao_id) == $cartao->id)>{{ $cartao->nome }} ({{ $cartao->tipo_label }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Parcelas</label>
                        <input type="number" name="qtd_parcelas" id="qtd_parcelas" class="form-control" value="{{ old('qtd_parcelas', $lancamento->qtd_parcelas) }}" min="1" max="120" placeholder="1 = à vista">
                        <div class="form-text">Ao informar mais de 1, as parcelas mensais serão criadas automaticamente.</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Observação</label>
                        <textarea name="observacao" class="form-control" rows="2">{{ old('observacao', $lancamento->observacao) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</button>
                    <a href="{{ route('lancamentos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const subcategoriasUrl = "{{ route('lancamentos.subcategorias') }}";
    const tipoSelecionado = {{ json_encode(old('tipo', $lancamento->tipo)) }};

    function carregarSubcategorias(categoriaId, selecionada = null) {
        const select = document.getElementById('subcategoria_id');
        select.innerHTML = '<option value="">— Sem item —</option>';
        if (!categoriaId) return;

        fetch(subcategoriasUrl + '?categoria_id=' + categoriaId)
            .then(r => r.json())
            .then(items => {
                items.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.nome;
                    if (selecionada && item.id == selecionada) opt.selected = true;
                    select.appendChild(opt);
                });
            });
    }

    function aplicarTipo(tipo) {
        document.querySelectorAll('#categoria_id option').forEach(opt => {
            if (!opt.value) return;
            opt.style.display = (tipo === 'receita' && opt.dataset.tipo === 'receita') ||
                               (tipo === 'despesa' && opt.dataset.tipo === 'despesa') ? '' : 'none';
        });
        const sel = document.getElementById('categoria_id');
        if (sel.selectedOptions[0]?.dataset?.tipo && sel.selectedOptions[0].dataset.tipo !== tipo) {
            sel.value = '';
            carregarSubcategorias('');
        }
    }

    document.getElementById('tipo')?.addEventListener('change', e => aplicarTipo(e.target.value));
    document.getElementById('categoria_id')?.addEventListener('change', e => carregarSubcategorias(e.target.value));
    document.getElementById('forma_pagamento')?.addEventListener('change', e => {
        document.getElementById('cartao_div').style.display = e.target.value === 'cartao' ? '' : 'none';
        if (e.target.value !== 'cartao') document.getElementById('cartao_id').value = '';
    });

    aplicarTipo(tipoSelecionado);
    carregarSubcategorias({{ json_encode(old('categoria_id', $lancamento->categoria_id) ?: 'null') }}, {{ json_encode(old('subcategoria_id', $lancamento->subcategoria_id) ?: 'null') }});
    if ({{ json_encode(old('forma_pagamento', $lancamento->forma_pagamento)) }} !== 'cartao') {
        document.getElementById('cartao_div').style.display = 'none';
    }
</script>
@endpush
