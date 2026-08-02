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

                @if ($lancamento->exists && ($lancamento->recorrente || $lancamento->isParcela()) && !$lancamento->assinatura_id)
                    <div class="alert alert-info py-2">
                        <i class="fa-solid fa-link me-1"></i>
                        Esta é uma conta {{ $lancamento->isParcela() ? 'parcelada' : 'fixa' }}: descrição, categoria, forma de pagamento e observação valem para <strong>todos os meses</strong>.
                        Data e valor continuam por mês (use o botão "Valor" da lista para alterar de um mês em diante).
                    </div>
                @endif

                {{-- Passo 1: tipo --}}
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-bold">O que é isso? *</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="tipo-option tipo-despesa {{ old('tipo', $lancamento->tipo) === 'despesa' ? 'ativo' : '' }}">
                                    <input type="radio" name="tipo" value="despesa" class="d-none" @checked(old('tipo', $lancamento->tipo) === 'despesa')>
                                    <i class="fa-solid fa-arrow-trend-down me-2"></i>
                                    <div>
                                        <strong>Despesa</strong>
                                        <small class="d-block text-muted">Gastei / saiu dinheiro</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="tipo-option tipo-receita {{ old('tipo', $lancamento->tipo) === 'receita' ? 'ativo' : '' }}">
                                    <input type="radio" name="tipo" value="receita" class="d-none" @checked(old('tipo', $lancamento->tipo) === 'receita')>
                                    <i class="fa-solid fa-arrow-trend-up me-2"></i>
                                    <div>
                                        <strong>Receita</strong>
                                        <small class="d-block text-muted">Recebi / entrou dinheiro</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        @error('tipo')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Passo 2: campos conforme o tipo --}}
                <div id="campos-lancamento" class="row g-3 d-none">
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
                    <div class="col-md-4">
                        <label class="form-label">Categoria</label>
                        <select name="categoria_id" id="categoria_id" class="form-select">
                            <option value="">— Selecionar categoria —</option>
                            @foreach ($categorias as $c)
                                <option value="{{ $c->id }}" data-tipo="{{ $c->tipo }}" @selected(old('categoria_id', $lancamento->categoria_id) == $c->id)>{{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Item</label>
                        <input type="text" name="item" id="item_nome" class="form-control" list="itens_lista"
                               value="{{ old('item', $lancamento->subcategoria?->nome) }}" placeholder="Digite ou escolha um item">
                        <datalist id="itens_lista"></datalist>
                        <input type="hidden" name="subcategoria_id" id="subcategoria_id" value="{{ old('subcategoria_id', $lancamento->subcategoria_id) }}">
                        <div class="form-text">Se o item não existir, será cadastrado automaticamente.</div>
                    </div>

                    <div class="col-md-4" id="div_forma_pagamento">
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

                    <div class="col-md-4 d-none" id="cartao_div">
                        <label class="form-label">Cartão utilizado</label>
                        <select name="cartao_id" id="cartao_id" class="form-select">
                            <option value="">— Nenhum —</option>
                            @foreach ($cartoes as $cartao)
                                <option value="{{ $cartao->id }}" @selected(old('cartao_id', $lancamento->cartao_id) == $cartao->id)>{{ $cartao->nome }} ({{ $cartao->tipo_label }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Repetição --}}
                    <div class="col-12 mt-2">
                        <label class="form-label fw-bold">Isso se repete?</label>
                        <div class="row g-2" id="opcoes_repeticao">
                            <div class="col-md-4">
                                <label class="rep-option">
                                    <input type="radio" name="repeticao" value="unica" class="d-none" checked>
                                    <i class="fa-solid fa-circle-dot me-1"></i>
                                    <strong>Uma vez</strong>
                                    <small class="d-block text-muted">Só nesta data</small>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="rep-option">
                                    <input type="radio" name="repeticao" value="todo_mes" class="d-none">
                                    <i class="fa-solid fa-calendar-days me-1"></i>
                                    <strong>Todo mês</strong>
                                    <small class="d-block text-muted">Repete todos os meses</small>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="rep-option">
                                    <input type="radio" name="repeticao" value="periodo" class="d-none">
                                    <i class="fa-solid fa-calendar-range me-1"></i>
                                    <strong>Até uma data</strong>
                                    <small class="d-block text-muted">Repete até a data final</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 d-none" id="div_data_fim">
                        <label class="form-label">Data final *</label>
                        <input type="date" name="data_fim" id="data_fim" class="form-control">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Observação</label>
                        <textarea name="observacao" class="form-control" rows="2">{{ old('observacao', $lancamento->observacao) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2" id="botoes">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</button>
                    <a href="{{ route('lancamentos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<style>
    .tipo-option {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border: 2px solid var(--border, #e3e6ef);
        border-radius: 12px;
        cursor: pointer;
        transition: all .15s ease;
        background: var(--card-bg, #fff);
        width: 100%;
        font-size: 1.05rem;
    }
    .tipo-option:hover { border-color: #adb5bd; }
    .tipo-option.ativo { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .tipo-despesa.ativo { border-color: #ef4444; background: rgba(239,68,68,.06); color: #dc2626; }
    .tipo-receita.ativo { border-color: #10b981; background: rgba(16,185,129,.06); color: #059669; }
    .rep-option {
        display: flex;
        flex-direction: column;
        padding: .85rem 1rem;
        border: 2px solid var(--border, #e3e6ef);
        border-radius: 12px;
        cursor: pointer;
        transition: all .15s ease;
        background: var(--card-bg, #fff);
        height: 100%;
    }
    .rep-option:hover { border-color: #adb5bd; }
    .rep-option:has(input:checked) { border-color: #3b82f6; background: rgba(59,130,246,.06); }
</style>
<script>
    const subcategoriasUrl = "{{ route('lancamentos.subcategorias') }}";
    const tipoInicial = @json(old('tipo', $lancamento->tipo));
    const categoriaInicial = @json(old('categoria_id', $lancamento->categoria_id) ?: null);
    const itemInicial = @json(old('item', $lancamento->subcategoria?->nome) ?: null);

    function atualizarTipo(tipo) {
        document.querySelectorAll('.tipo-option').forEach(el => {
            el.classList.toggle('ativo', el.querySelector('input').value === tipo);
        });
        document.getElementById('campos-lancamento').classList.toggle('d-none', !tipo);

        const sel = document.getElementById('categoria_id');
        const primeiraCategoria = [...sel.options].find(o => o.value && o.dataset.tipo === tipo);
        if (primeiraCategoria) {
            sel.value = primeiraCategoria.value;
            carregarItens(primeiraCategoria.value);
        } else {
            sel.value = '';
            carregarItens('');
        }
        limparCamposNaoComuns();
    }

    function carregarItens(categoriaId) {
        const input = document.getElementById('item_nome');
        const dl = document.getElementById('itens_lista');
        dl.innerHTML = '';
        input.value = '';
        document.getElementById('subcategoria_id').value = '';

        if (!categoriaId) return;

        fetch(subcategoriasUrl + '?categoria_id=' + categoriaId)
            .then(r => r.json())
            .then(items => {
                items.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.nome;
                    opt.dataset.id = item.id;
                    dl.appendChild(opt);
                });
            });
    }

    function limparCamposNaoComuns() {
        document.getElementById('item_nome').value = '';
        document.getElementById('subcategoria_id').value = '';
        document.getElementById('data_fim').value = '';
        document.querySelector('input[name="repeticao"][value="unica"]').checked = true;
        document.getElementById('div_data_fim').classList.add('d-none');
        document.querySelectorAll('#opcoes_repeticao .rep-option').forEach(el => {
            el.classList.toggle('ativo', el.querySelector('input').checked);
        });
    }

    document.getElementById('categoria_id').addEventListener('change', e => carregarItens(e.target.value));

    document.getElementById('item_nome').addEventListener('input', e => {
        const valor = e.target.value.trim();
        const dl = document.getElementById('itens_lista');
        const opt = [...dl.options].find(o => o.value === valor);
        document.getElementById('subcategoria_id').value = opt ? opt.dataset.id : '';
    });

    document.getElementById('forma_pagamento').addEventListener('change', e => {
        const div = document.getElementById('cartao_div');
        if (e.target.value === 'cartao') {
            div.classList.remove('d-none');
        } else {
            div.classList.add('d-none');
            document.getElementById('cartao_id').value = '';
        }
    });

    document.querySelectorAll('input[name="repeticao"]').forEach(r => {
        r.addEventListener('change', e => {
            document.getElementById('div_data_fim').classList.toggle('d-none', e.target.value !== 'periodo');
            document.querySelectorAll('.rep-option').forEach(el => {
                el.classList.toggle('ativo', el.querySelector('input').checked);
            });
        });
    });

    document.querySelectorAll('input[name="tipo"]').forEach(r => {
        r.addEventListener('change', e => atualizarTipo(e.target.value));
    });

    if (tipoInicial) {
        atualizarTipo(tipoInicial);
        if (categoriaInicial) {
            const sel = document.getElementById('categoria_id');
            sel.value = categoriaInicial;
            carregarItens(categoriaInicial);
            if (itemInicial) document.getElementById('item_nome').value = itemInicial;
        }
    }
</script>
@endpush
