@extends('layouts.app')

@section('title', 'Lançamentos')
@section('page-title', 'Lançamentos')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('lancamentos.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Mês</label>
                    <select name="mes" class="form-select">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" @selected(request('mes', now()->month) == $m)>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ano</label>
                    <select name="ano" class="form-select">
                        @foreach (range(now()->year + 5, now()->year - 7) as $y)
                            <option value="{{ $y }}" @selected(request('ano', now()->year) == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="receita" @selected(request('tipo') === 'receita')>Receitas</option>
                        <option value="despesa" @selected(request('tipo') === 'despesa')>Despesas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Tipo de conta</label>
                    <div class="btn-group w-100" role="group" aria-label="Tipo de conta">
                        <input type="radio" class="btn-check" name="conta" id="conta_todas" value="" @checked(!request('conta')) onchange="this.form.submit()">
                        <label class="btn btn-outline-secondary btn-sm" for="conta_todas">Todas</label>
                        <input type="radio" class="btn-check" name="conta" id="conta_fixa" value="fixa" @checked(request('conta') === 'fixa') onchange="this.form.submit()">
                        <label class="btn btn-outline-primary btn-sm" for="conta_fixa">Fixa</label>
                        <input type="radio" class="btn-check" name="conta" id="conta_periodo" value="periodo" @checked(request('conta') === 'periodo') onchange="this.form.submit()">
                        <label class="btn btn-outline-warning btn-sm" for="conta_periodo">Período</label>
                        <input type="radio" class="btn-check" name="conta" id="conta_variavel" value="variavel" @checked(request('conta') === 'variavel') onchange="this.form.submit()">
                        <label class="btn btn-outline-info btn-sm" for="conta_variavel">Variáveis</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Categoria</label>
                    <select name="categoria_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($categorias as $c)
                            <option value="{{ $c->id }}" @selected(request('categoria_id') == $c->id)>{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Cartão</label>
                    <select name="cartao_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($cartoes as $cartao)
                            <option value="{{ $cartao->id }}" @selected(request('cartao_id') == $cartao->id)>{{ $cartao->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Buscar conta</label>
                    <input type="search" name="busca" class="form-control form-control-lg" value="{{ request('busca') }}" placeholder="Digite o nome... (conta, categoria, item ou cartão)" autocomplete="off">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button class="btn btn-primary flex-grow-1" title="Filtrar"><i class="fa-solid fa-magnifying-glass me-1"></i>Filtrar</button>
                    <a href="{{ route('lancamentos.index', ['visao' => $visao ?? 'lista']) }}" class="btn btn-outline-secondary" title="Limpar"><i class="fa-solid fa-rotate-left"></i></a>
                </div>
            </form>
            <div class="btn-group mt-3" role="group" aria-label="Modo de visualização">
                <a href="{{ route('lancamentos.index', array_merge(request()->except('page'), ['visao' => 'lista'])) }}" class="btn btn-sm {{ ($visao ?? 'lista') === 'lista' ? 'btn-primary' : 'btn-outline-primary' }}"><i class="fa-solid fa-list me-1"></i>Lista</a>
                <a href="{{ route('lancamentos.index', array_merge(request()->except('page'), ['visao' => 'agenda'])) }}" class="btn btn-sm {{ ($visao ?? 'lista') === 'agenda' ? 'btn-primary' : 'btn-outline-primary' }}"><i class="fa-solid fa-calendar-days me-1"></i>Agenda</a>
            </div>
        </div>
    </div>

    @if (($visao ?? 'lista') === 'agenda')
        @php
            $ref = \Carbon\Carbon::create($ano ?? request('ano', now()->year), $mes ?? request('mes', now()->month), 1);
            $ant = $ref->copy()->subMonth();
            $prox = $ref->copy()->addMonth();
            $baseQ = request()->except(['mes', 'ano', 'page']);
            $inicioBranco = $ref->dayOfWeekIso - 1;
            $diasMes = $ref->daysInMonth;
            $nomeMes = $ref->translatedFormat('F/Y');
        @endphp
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('lancamentos.index', array_merge($baseQ, ['mes' => $ant->month, 'ano' => $ant->year, 'visao' => 'agenda'])) }}" class="btn btn-outline-secondary btn-sm" title="Mês anterior"><i class="fa-solid fa-chevron-left"></i></a>
                    <h5 class="mb-0 text-capitalize"><i class="fa-solid fa-calendar-days me-2"></i>{{ $nomeMes }}</h5>
                    <a href="{{ route('lancamentos.index', array_merge($baseQ, ['mes' => $prox->month, 'ano' => $prox->year, 'visao' => 'agenda'])) }}" class="btn btn-outline-secondary btn-sm" title="Próximo mês"><i class="fa-solid fa-chevron-right"></i></a>
                    <a href="{{ route('lancamentos.index', array_merge($baseQ, ['mes' => now()->month, 'ano' => now()->year, 'visao' => 'agenda'])) }}" class="btn btn-outline-secondary btn-sm">Hoje</a>
                </div>
                <div class="d-flex gap-2 small">
                    <span class="badge bg-success fs-6">+ R$ {{ number_format($totalReceitas ?? 0, 2, ',', '.') }}</span>
                    <span class="badge bg-danger fs-6">− R$ {{ number_format($totalDespesas ?? 0, 2, ',', '.') }}</span>
                    <span class="badge {{ (($totalReceitas ?? 0) - ($totalDespesas ?? 0)) >= 0 ? 'bg-primary' : 'bg-warning text-dark' }} fs-6">Saldo R$ {{ number_format(($totalReceitas ?? 0) - ($totalDespesas ?? 0), 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
        <div class="agenda-grid">
            @foreach (['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB', 'DOM'] as $dow)
                <div class="agenda-dow">{{ $dow }}</div>
            @endforeach
            @for ($b = 0; $b < $inicioBranco; $b++)
                <div class="agenda-dia vazio"></div>
            @endfor
            @for ($dia = 1; $dia <= $diasMes; $dia++)
                @php
                    $dataDia = $ref->copy()->day($dia);
                    $chave = $dataDia->format('Y-m-d');
                    $itens = ($porDia ?? collect())->get($chave, collect());
                    $ehHoje = $chave === now()->format('Y-m-d');
                @endphp
                <div class="agenda-dia {{ $ehHoje ? 'hoje' : '' }}">
                    <div class="agenda-num">{{ $dia }}</div>
                    <div class="agenda-itens">
                        @foreach ($itens as $l)
                            @php $assinatura = $l->assinatura_id ? (($assinaturas ?? collect())[$l->assinatura_id] ?? null) : null; @endphp
                            <div class="agenda-chip {{ $l->tipo === 'receita' ? 'rec' : 'des' }}" title="{{ $l->descricao }} — {{ ($assinatura?->categoria ?? $l->categoria)?->nome ?? 'Sem categoria' }} — R$ {{ number_format($l->valor, 2, ',', '.') }}">
                                <span class="agenda-desc">{{ $assinatura ? $assinatura->nome : $l->descricao }}</span>
                                @if ($l->isParcela())<span class="agenda-tag">{{ $l->parcela_atual }}/{{ $l->qtd_parcelas }}</span>@endif
                                @if ($l->recorrente && !$assinatura)<span class="agenda-tag fixo">fixo</span>@endif
                                @if ($assinatura)<span class="agenda-tag fixo">mensal</span>@endif
                                <span class="agenda-valor">{{ $l->tipo === 'receita' ? '+' : '−' }}{{ number_format($l->valor, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endfor
        </div>
        <div class="text-center mt-3">
            <a href="{{ route('lancamentos.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Novo lançamento</a>
        </div>
        @push('scripts')
        <style>
            .agenda-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
            .agenda-dow { text-align: center; font-weight: 700; font-size: .75rem; color: #6c757d; padding: 4px; }
            .agenda-dia { background: var(--bs-body-bg, #fff); border: 1px solid var(--bs-border-color, #dee2e6); border-radius: 10px; min-height: 110px; padding: 4px; display: flex; flex-direction: column; }
            .agenda-dia.vazio { background: transparent; border: none; }
            .agenda-dia.hoje { border: 2px solid #0d6efd; }
            .agenda-num { font-weight: 700; font-size: .85rem; margin-bottom: 2px; }
            .agenda-dia.hoje .agenda-num { color: #0d6efd; }
            .agenda-itens { display: flex; flex-direction: column; gap: 3px; overflow-y: auto; max-height: 220px; }
            .agenda-chip { font-size: .72rem; line-height: 1.25; border-radius: 6px; padding: 2px 5px; background: #f1f3f5; border-left: 3px solid #6c757d; display: flex; flex-wrap: wrap; gap: 2px 4px; align-items: baseline; }
            .agenda-chip.rec { border-left-color: #198754; background: rgba(25,135,84,.1); }
            .agenda-chip.des { border-left-color: #dc3545; background: rgba(220,53,69,.08); }
            .agenda-desc { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }
            .agenda-valor { margin-left: auto; font-weight: 700; }
            .agenda-chip.rec .agenda-valor { color: #198754; }
            .agenda-chip.des .agenda-valor { color: #dc3545; }
            .agenda-tag { font-size: .62rem; background: #6c757d; color: #fff; border-radius: 4px; padding: 0 4px; }
            .agenda-tag.fixo { background: #0dcaf0; color: #000; }
            @@media (max-width: 768px) { .agenda-grid { grid-template-columns: repeat(2, 1fr); } .agenda-dia.vazio { display: none; } }
        </style>
        @endpush
    @else
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-wallet me-2"></i>Lançamentos</h5>
            <a href="{{ route('lancamentos.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Novo lançamento</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Forma de pagamento</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lancamentos as $l)
                            @php $assinatura = $l->assinatura_id ? ($assinaturas[$l->assinatura_id] ?? null) : null; @endphp
                            <tr @if ($assinatura) class="table-warning" @endif>
                                <td class="text-nowrap">
                                    @if ($assinatura)<span class="badge bg-warning text-dark me-1">mensal</span>@endif
                                    {{ $l->data->translatedFormat('d/m/Y') }}
                                </td>
                                <td>
                                    @if ($assinatura)
                                        <i class="fa-solid fa-arrows-rotate me-1 text-warning"></i>{{ $assinatura->nome }}
                                        <span class="badge bg-warning text-dark ms-1">assinatura</span>
                                        @if ($assinatura->ativo)<span class="badge bg-success ms-1">ativa</span>@else<span class="badge bg-secondary ms-1">desativada</span>@endif
                                        @if ($l->tipo === 'despesa' && !$l->pago && ($l->forma_pagamento !== 'cartao' || $l->cartao_debito))<span class="badge bg-danger ms-1">a pagar</span>@endif
                                        @if ($assinatura->observacao)<div class="small text-muted">{{ $assinatura->observacao }}</div>@endif
                                    @else
                                        {{ $l->descricao }}
                                        @if ($l->isParcela())<span class="badge bg-secondary ms-1">parcela {{ $l->parcela_atual }}/{{ $l->qtd_parcelas }}</span>@endif
                                        @if ($l->recorrente)<span class="badge bg-info ms-1">fixo</span>@endif
                                        @if ($l->tipo === 'despesa' && !$l->pago && ($l->forma_pagamento !== 'cartao' || $l->cartao_debito))<span class="badge bg-danger ms-1">a pagar</span>@endif
                                        @if (!$l->abate_saldo)<span class="badge bg-info text-dark ms-1">não abate</span>@endif
                                        @if ($l->observacao)<div class="small text-muted">{{ $l->observacao }}</div>@endif
                                    @endif
                                </td>
                                <td>
                                    <i class="fa-solid {{ ($assinatura?->categoria ?? $l->categoria)?->icone ?? 'fa-tag' }} me-1" style="color: {{ ($assinatura?->categoria ?? $l->categoria)?->cor ?? '#6c757d' }}"></i>
                                    {{ ($assinatura?->categoria ?? $l->categoria)?->nome ?? '—' }}
                                </td>
                                <td>
                                    @if ($assinatura?->cartao ?? $l->cartao)<span class="badge bg-dark">{{ ($assinatura?->cartao ?? $l->cartao)->nome }}</span>
                                    @else <span class="text-muted">{{ $l->forma_pagamento ? $l->forma_label : '—' }}</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold {{ $l->tipo === 'receita' ? 'text-success' : 'text-danger' }}">
                                    {{ $l->tipo === 'receita' ? '+' : '-' }}R$ {{ number_format($l->valor, 2, ',', '.') }}
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($assinatura)
                                        <form method="POST" action="{{ route('assinaturas.toggle', $assinatura) }}" class="d-inline">
                                            @csrf
                                            @if ($assinatura->ativo)
                                                <button class="btn btn-sm btn-outline-secondary" title="Desativar assinatura"><i class="fa-solid fa-pause me-1"></i>Desativar</button>
                                            @else
                                                <button class="btn btn-sm btn-outline-success" title="Ativar assinatura"><i class="fa-solid fa-play me-1"></i>Ativar</button>
                                            @endif
                                        </form>
                                        <button class="btn btn-sm btn-outline-primary" title="Alterar valor da assinatura"
                                            data-bs-toggle="modal" data-bs-target="#modalValor"
                                            data-url="{{ route('assinaturas.valor', $assinatura) }}"
                                            data-nome="{{ $assinatura->nome }}"
                                            data-valor="{{ $assinatura->valor }}"
                                            data-sem-mes="1"><i class="fa-solid fa-circle-dollar me-1"></i>Valor</button>
                                    @elseif ($l->isParcela() || $l->recorrente)
                                        <button class="btn btn-sm btn-outline-primary" title="Alterar valor (preserva os anteriores)"
                                            data-bs-toggle="modal" data-bs-target="#modalValor"
                                            data-url="{{ route('lancamentos.alterarValor', $l) }}"
                                            data-nome="{{ $l->descricao }}"
                                            data-valor="{{ $l->valor }}"><i class="fa-solid fa-circle-dollar me-1"></i>Valor</button>
                                        <form method="POST" action="{{ route('lancamentos.pausar', $l) }}" class="d-inline" onsubmit="return confirm('{{ $l->isParcela() ? 'Pausar estas parcelas' : 'Desativar esta conta fixa' }}? Os lançamentos futuros serão removidos e o histórico preservado.')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary" title="{{ $l->isParcela() ? 'Pausar parcelas futuras' : 'Desativar conta fixa' }}"><i class="fa-solid fa-pause me-1"></i>{{ $l->isParcela() ? 'Pausar' : 'Desativar' }}</button>
                                        </form>
                                        <a href="{{ route('lancamentos.edit', $l) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>Editar</a>
                                        <form method="POST" action="{{ route('lancamentos.destroy', $l) }}" class="d-inline" onsubmit="return confirm('Excluir este lançamento{{ $l->isParcela() ? ' e toda a série de parcelas' : '' }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash me-1"></i>Excluir</button>
                                        </form>
                                    @else
                                        <a href="{{ route('lancamentos.edit', $l) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>Editar</a>
                                        <form method="POST" action="{{ route('lancamentos.destroy', $l) }}" class="d-inline" onsubmit="return confirm('Excluir este lançamento{{ $l->isParcela() ? ' e toda a série de parcelas' : '' }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash me-1"></i>Excluir</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-folder-open"></i>
                                        <p>Nenhum lançamento encontrado.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($lancamentos->hasPages())
                <div class="card-footer">{{ $lancamentos->links() }}</div>
            @endif
        </div>
    </div>
    @endif

    <div class="modal fade" id="modalValor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="formValor">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalValorTitulo">Alterar valor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Novo valor (R$)</label>
                            <input type="number" step="0.01" min="0.01" name="novo_valor" id="modalValorValor" class="form-control" required>
                        </div>
                        <div class="mb-3" id="modalValorAplicacao">
                            <label class="form-label">Aplicar em</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="aplicacao" id="apl_todos" value="todos" checked>
                                <label class="form-check-label" for="apl_todos">Todos os meses da conta</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="aplicacao" id="apl_mes" value="mes">
                                <label class="form-check-label" for="apl_mes">Somente neste mês</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="aplicacao" id="apl_apartir" value="apartir">
                                <label class="form-check-label" for="apl_apartir">A partir de</label>
                            </div>
                            <div id="modalValorMesWrap" class="mt-2 d-none">
                                <input type="month" name="mes_inicio" id="modalValorMes" class="form-control" value="{{ now()->format('Y-m') }}">
                                <div class="form-text">Os lançamentos anteriores a esta data são preservados.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-bs-target="#modalValor"]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const f = document.getElementById('formValor');
                    f.action = btn.dataset.url;
                    document.getElementById('modalValorTitulo').textContent = 'Alterar valor — ' + btn.dataset.nome;
                    document.getElementById('modalValorValor').value = btn.dataset.valor;
                    const aplicacao = document.getElementById('modalValorAplicacao');
                    aplicacao.style.display = btn.dataset.semMes ? 'none' : '';
                    if (btn.dataset.semMes) {
                        document.getElementById('apl_todos').checked = true;
                    }
                    const mesWrap = document.getElementById('modalValorMesWrap');
                    mesWrap.classList.add('d-none');
                    document.getElementById('apl_todos').checked = true;
                    document.getElementById('modalValorMes').value = '{{ now()->format('Y-m') }}';
                });
            });

            document.querySelectorAll('input[name="aplicacao"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    const mesWrap = document.getElementById('modalValorMesWrap');
                    if (radio.value === 'apartir' && radio.checked) {
                        mesWrap.classList.remove('d-none');
                    } else {
                        mesWrap.classList.add('d-none');
                    }
                });
            });
        </script>
    @endpush
@endsection
