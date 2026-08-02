<div>
    <form wire:submit="save">
        {{-- Passo 1: tipo --}}
        <div class="row g-3 mb-4">
            <div class="col-12">
                <label class="form-label fw-bold">O que é isso? *</label>
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="radio" class="btn-check" name="tipo" id="tipo-despesa" value="despesa" wire:model.live="tipo">
                        <label class="btn btn-outline-danger btn-lg w-100 text-start" for="tipo-despesa">
                            <i class="fa-solid fa-arrow-trend-down me-2"></i>
                            <strong>Despesa</strong>
                            <small class="d-block opacity-75">Gastei / saiu dinheiro</small>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <input type="radio" class="btn-check" name="tipo" id="tipo-receita" value="receita" wire:model.live="tipo">
                        <label class="btn btn-outline-success btn-lg w-100 text-start" for="tipo-receita">
                            <i class="fa-solid fa-arrow-trend-up me-2"></i>
                            <strong>Receita</strong>
                            <small class="d-block opacity-75">Recebi / entrou dinheiro</small>
                        </label>
                    </div>
                </div>
                @error('tipo')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Passo 2: campos conforme o tipo (oculto até escolher) --}}
        @if ($tipo)
        <div class="row g-3" id="campos-lancamento">
            <div class="col-md-4">
                <label class="form-label">{{ $tipo === 'receita' ? 'Descrição da receita' : 'Descrição do gasto' }} *</label>
                <input type="text" wire:model="descricao" class="form-control"
                       placeholder="{{ $tipo === 'receita' ? 'Ex.: Salário, Freela, Cashback' : 'Ex.: Mercado, Combustível, Fatura' }}" required>
                @error('descricao')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label">Valor (R$) *</label>
                <input type="text" x-data x-init="$el.value = $wire.valor || ''"
                       x-on:input="const digits = $el.value.replace(/\D/g, '').slice(0, 12); const num = parseInt(digits || '0', 10); $el.value = (num / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });"
                       wire:model="valor" inputmode="decimal" class="form-control" placeholder="R$ 0,00" required>
                @error('valor')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label">Data *</label>
                <input type="date" wire:model="data" class="form-control" required>
                @error('data')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Categoria</label>
                <select wire:model.live="categoria_id" id="categoria_id" class="form-select">
                    <option value="">— Selecionar categoria —</option>
                    @foreach ($this->categorias->where('tipo', $tipo) as $c)
                        <option value="{{ $c->id }}">{{ $c->nome }}</option>
                    @endforeach
                </select>
                @error('categoria_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            @if ($tipo === 'despesa')
                @if ($this->itens->isNotEmpty())
                <div class="col-md-4" wire:key="campo-item">
                    <label class="form-label">Item</label>
                    <select wire:model="subcategoria_id" class="form-select">
                        <option value="">— Selecionar item —</option>
                        @foreach ($this->itens as $s)
                            <option value="{{ $s->id }}">{{ $s->nome }}</option>
                        @endforeach
                    </select>
                    @error('subcategoria_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                @endif

                <div class="col-md-4 {{ $this->itens->isNotEmpty() ? '' : 'col-md-6' }}">
                    <label class="form-label">Forma de pagamento</label>
                    <select wire:model.live="forma_pagamento" class="form-select">
                        <option value="">— Selecionar —</option>
                        <option value="cartao">Cartão</option>
                        <option value="pix">Pix</option>
                        <option value="dinheiro">Dinheiro</option>
                        <option value="boleto">Boleto</option>
                        <option value="outros">Outros</option>
                    </select>
                </div>

                <div class="col-md-{{ $this->itens->isNotEmpty() ? 4 : 6 }} {{ $forma_pagamento === 'cartao' ? '' : 'd-none' }}" id="cartao_div">
                    <label class="form-label">Cartão utilizado</label>
                    <select wire:model="cartao_id" class="form-select">
                        <option value="">— Nenhum —</option>
                        @foreach ($this->cartoes as $cartao)
                            <option value="{{ $cartao->id }}">{{ $cartao->nome }} ({{ $cartao->tipo_label }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Repetição --}}
            <div class="col-12 mt-2">
                <label class="form-label fw-bold">Isso se repete?</label>
                <div class="row g-2" id="opcoes_repeticao">
                    <div class="col-md-4">
                        <input type="radio" class="btn-check" name="repeticao" id="rep-unica" value="unica" wire:model.live="repeticao">
                        <label class="btn btn-outline-primary btn-lg w-100 text-start" for="rep-unica">
                            <i class="fa-solid fa-circle-dot me-1"></i>
                            <strong>Uma vez</strong>
                            <small class="d-block opacity-75">Só nesta data</small>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <input type="radio" class="btn-check" name="repeticao" id="rep-todo-mes" value="todo_mes" wire:model.live="repeticao">
                        <label class="btn btn-outline-primary btn-lg w-100 text-start" for="rep-todo-mes">
                            <i class="fa-solid fa-calendar-days me-1"></i>
                            <strong>Todo mês</strong>
                            <small class="d-block opacity-75">Repete todos os meses</small>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <input type="radio" class="btn-check" name="repeticao" id="rep-periodo" value="periodo" wire:model.live="repeticao">
                        <label class="btn btn-outline-primary btn-lg w-100 text-start" for="rep-periodo">
                            <i class="fa-solid fa-calendar-range me-1"></i>
                            <strong>Por um período</strong>
                            <small class="d-block opacity-75">Repete até uma data final</small>
                        </label>
                    </div>
                </div>
            </div>

            @if ($repeticao === 'periodo')
                <div class="col-md-4" id="div_data_fim">
                    <label class="form-label">Data final *</label>
                    <input type="date" wire:model="data_fim" class="form-control">
                    @error('data_fim')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            @endif

            <div class="col-md-12">
                <label class="form-label">Observação</label>
                <textarea wire:model="observacao" class="form-control" rows="2"></textarea>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <span wire:loading.remove wire:target="save"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</span>
                <span wire:loading wire:target="save"><span class="spinner-border spinner-border-sm me-1"></span>Salvando...</span>
            </button>
            <a href="{{ route('lancamentos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
        @endif
    </form>
</div>
