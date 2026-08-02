<?php

namespace App\Http\Controllers;

use App\Models\Lancamento;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LancamentoController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = $user->lancamentos()->with(['categoria', 'subcategoria', 'cartao', 'origem']);

        $mes = max(1, min(12, (int) $request->input('mes', now()->month)));
        $ano = (int) $request->input('ano', now()->year);
        $query->noMes($ano, $mes);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }
        if ($request->filled('cartao_id')) {
            $query->where('cartao_id', $request->cartao_id);
        }
        if ($request->filled('busca')) {
            $query->where('descricao', 'like', '%' . $request->busca . '%');
        }

        $lancamentos = $query->orderByDesc('data')->orderByDesc('id')->paginate(30)->withQueryString();

        $colecao = $lancamentos->getCollection();
        $assinaturaIds = $colecao->whereNotNull('assinatura_id')->pluck('assinatura_id')->unique();
        $assinaturas = $user->assinaturas()->with(['categoria', 'cartao'])
            ->whereIn('id', $assinaturaIds)->get()->keyBy('id');

        if ($assinaturaIds->isNotEmpty()) {
            $agrupados = $colecao->whereNotNull('assinatura_id')
                ->groupBy('assinatura_id')
                ->map(fn ($g) => $g->sortByDesc('data')->first());
            $colecao = $colecao->whereNull('assinatura_id')
                ->concat($agrupados)
                ->sortByDesc('data')->sortByDesc('id')
                ->values();
            $lancamentos->setCollection($colecao);
        }

        $categorias = $user->categorias()->orderBy('tipo')->orderBy('nome')->get();
        $cartoes = $user->cartoes()->orderBy('nome')->get();

        return view('lancamentos.index', compact('lancamentos', 'categorias', 'cartoes', 'assinaturas'));
    }

    public function create()
    {
        $user = auth()->user();

        return view('lancamentos.form', [
            'lancamento' => new Lancamento(['data' => now(), 'tipo' => 'despesa', 'qtd_parcelas' => 1]),
            'categorias' => $user->categorias()->with('subcategorias')->orderBy('tipo')->orderBy('nome')->get(),
            'cartoes' => $user->cartoes()->where('ativo', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $user = auth()->user();

        $qtdParcelas = (int) ($data['qtd_parcelas'] ?? 1);
        $qtdParcelas = max(1, min(120, $qtdParcelas));

        if ($qtdParcelas === 1) {
            $user->lancamentos()->create($this->campos($data, 1, 1, null));

            return redirect()->route('lancamentos.index')->with('success', 'Lançamento registrado.');
        }

        $primeiro = $user->lancamentos()->create($this->campos($data, $qtdParcelas, 1, null));

        for ($i = 2; $i <= $qtdParcelas; $i++) {
            $user->lancamentos()->create($this->campos($data, $qtdParcelas, $i, $primeiro->id));
        }

        return redirect()->route('lancamentos.index')->with('success', "Lançamento criado em {$qtdParcelas} parcelas.");
    }

    public function edit(Lancamento $lancamento)
    {
        abort_unless($lancamento->user_id === auth()->id(), 403);
        $user = auth()->user();

        return view('lancamentos.form', [
            'lancamento' => $lancamento,
            'categorias' => $user->categorias()->with('subcategorias')->orderBy('tipo')->orderBy('nome')->get(),
            'cartoes' => $user->cartoes()->where('ativo', true)->get(),
        ]);
    }

    public function update(Request $request, Lancamento $lancamento)
    {
        abort_unless($lancamento->user_id === auth()->id(), 403);

        $data = $this->validar($request);
        $lancamento->update($this->campos($data, $lancamento->qtd_parcelas, $lancamento->parcela_atual, $lancamento->origem_id));

        return redirect()->route('lancamentos.index')->with('success', 'Lançamento atualizado.');
    }

    public function destroy(Lancamento $lancamento)
    {
        abort_unless($lancamento->user_id === auth()->id(), 403);

        $serie = $lancamento->isParcela()
            ? $this->lancamentosSerie($lancamento)
            : collect([$lancamento]);

        foreach ($serie as $item) {
            $item->delete();
        }

        return back()->with('success', 'Lançamento(s) removido(s).');
    }

    private function lancamentosSerie(Lancamento $lancamento)
    {
        if ($lancamento->origem_id) {
            $origem = Lancamento::find($lancamento->origem_id);
        } else {
            $origem = $lancamento;
        }

        return Lancamento::where('user_id', $lancamento->user_id)
            ->where(function ($q) use ($origem) {
                $q->where('id', $origem->id)->orWhere('origem_id', $origem->id);
            })
            ->orderBy('parcela_atual')
            ->get();
    }

    private function validar(Request $request): array
    {
        return $this->validate($request, [
            'data' => ['required', 'date'],
            'descricao' => ['required', 'string', 'max:150'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'tipo' => ['required', 'in:receita,despesa'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'subcategoria_id' => ['nullable', 'exists:subcategorias,id'],
            'forma_pagamento' => ['nullable', 'in:cartao,pix,dinheiro,boleto,outros'],
            'cartao_id' => ['nullable', 'exists:cartoes,id'],
            'recorrente' => ['nullable', 'boolean'],
            'qtd_parcelas' => ['nullable', 'integer', 'min:1', 'max:120'],
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function campos(array $data, int $qtdParcelas, int $parcelaAtual, ?int $origemId): array
    {
        $data = collect($data);

        return [
            'data' => $data['data'],
            'descricao' => $parcelaAtual > 1 ? $data['descricao'] . " ({$parcelaAtual}/{$qtdParcelas})" : $data['descricao'],
            'valor' => $data['valor'],
            'tipo' => $data['tipo'],
            'categoria_id' => $data['categoria_id'] ?: null,
            'subcategoria_id' => $data['subcategoria_id'] ?: null,
            'forma_pagamento' => $data['forma_pagamento'] ?: null,
            'cartao_id' => $data['cartao_id'] ?: null,
            'recorrente' => $data['recorrente'] ?? false,
            'qtd_parcelas' => $qtdParcelas,
            'parcela_atual' => $parcelaAtual,
            'origem_id' => $origemId,
            'observacao' => $data['observacao'] ?: null,
        ];
    }

    public function subcategorias(Request $request)
    {
        $items = \App\Models\Subcategoria::where('categoria_id', $request->categoria_id)
            ->where('user_id', auth()->id())
            ->orderBy('nome')->get(['id', 'nome']);

        return response()->json($items);
    }
}
