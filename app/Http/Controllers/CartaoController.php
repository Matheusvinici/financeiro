<?php

namespace App\Http\Controllers;

use App\Models\Cartao;
use Illuminate\Http\Request;

class CartaoController extends Controller
{
    public function index()
    {
        $cartoes = auth()->user()->cartoes()->withCount('lancamentos')->orderBy('nome')->get();
        $totais = $cartoes->map(function ($cartao) {
            $gastoMes = (float) $cartao->lancamentos()
                ->where('tipo', 'despesa')->whereYear('data', now()->year)->whereMonth('data', now()->month)
                ->sum('valor');

            return [
                'cartao' => $cartao,
                'gasto_mes' => $gastoMes,
            ];
        });

        return view('cartoes.index', ['totais' => $totais]);
    }

    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'nome' => ['required', 'string', 'max:60'],
            'tipo' => ['required', 'in:credito,debito,credito_debito'],
            'bandeira' => ['nullable', 'string', 'max:40'],
            'limite' => ['nullable', 'numeric', 'min:0'],
            'dia_fechamento' => ['nullable', 'integer', 'min:1', 'max:31'],
            'dia_vencimento' => ['nullable', 'integer', 'min:1', 'max:31'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        auth()->user()->cartoes()->create($data + ['ativo' => $request->boolean('ativo', true)]);

        return back()->with('success', 'Cartão cadastrado.');
    }

    public function update(Request $request, Cartao $cartao)
    {
        abort_unless($cartao->user_id === auth()->id(), 403);

        $data = $this->validate($request, [
            'nome' => ['required', 'string', 'max:60'],
            'tipo' => ['required', 'in:credito,debito,credito_debito'],
            'bandeira' => ['nullable', 'string', 'max:40'],
            'limite' => ['nullable', 'numeric', 'min:0'],
            'dia_fechamento' => ['nullable', 'integer', 'min:1', 'max:31'],
            'dia_vencimento' => ['nullable', 'integer', 'min:1', 'max:31'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $cartao->update($data + ['ativo' => $request->boolean('ativo', true)]);

        return back()->with('success', 'Cartão atualizado.');
    }

    public function destroy(Cartao $cartao)
    {
        abort_unless($cartao->user_id === auth()->id(), 403);
        $cartao->delete();

        return back()->with('success', 'Cartão removido.');
    }
}
