<?php

namespace App\Http\Controllers;

use App\Models\Compartilhamento;
use App\Models\User;
use Illuminate\Http\Request;

class CompartilhamentoController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $enviados = $user->compartilhamentosEnviados()->with('convidado')->get();
        $recebidos = $user->compartilhamentosRecebidos()->with('dono')->get();
        $categorias = $user->categorias()->orderBy('tipo')->orderBy('nome')->get();

        return view('compartilhamentos.index', compact('enviados', 'recebidos', 'categorias'));
    }

    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'email' => ['required', 'email', 'exists:users,email'],
            'categoria_ids' => ['nullable', 'array'],
            'categoria_ids.*' => ['integer', 'exists:categorias,id'],
        ]);

        $user = auth()->user();
        if ($request->email === $user->email) {
            return back()->withErrors(['email' => 'Você não pode compartilhar com você mesmo.']);
        }

        $convidado = User::where('email', $request->email)->first();

        $ids = collect($data['categoria_ids'] ?? [])
            ->filter(fn ($id) => $user->categorias()->whereKey($id)->exists())
            ->values();

        $user->compartilhamentosEnviados()->updateOrCreate(
            ['convidado_user_id' => $convidado->id],
            [
                'categoria_ids' => $ids->isEmpty() ? null : $ids->all(),
                'so_leitura' => true,
            ]
        );

        return back()->with('success', 'Compartilhamento atualizado.');
    }

    public function destroy(Compartilhamento $compartilhamento)
    {
        abort_unless($compartilhamento->dono_user_id === auth()->id(), 403);
        $compartilhamento->delete();

        return back()->with('success', 'Compartilhamento removido.');
    }

    public function visao()
    {
        $user = auth()->user();
        $compartilhamentos = $user->compartilhamentosRecebidos()->with('dono')->get();

        return view('compartilhamentos.visao', compact('compartilhamentos'));
    }

    public function ver(Request $request, Compartilhamento $compartilhamento)
    {
        abort_unless($compartilhamento->convidado_user_id === auth()->id(), 403);

        $dono = $compartilhamento->dono;
        $mes = (int) $request->input('mes', now()->month);
        $ano = (int) $request->input('ano', now()->year);

        $query = $dono->lancamentos()->with(['categoria', 'subcategoria', 'cartao'])
            ->whereYear('data', $ano)->whereMonth('data', $mes);

        if ($compartilhamento->categoria_ids) {
            $query->whereIn('categoria_id', $compartilhamento->categoria_ids);
        }

        $lancamentos = $query->orderByDesc('data')->orderByDesc('id')->get();

        $receitas = $lancamentos->where('tipo', 'receita')->sum('valor');
        $despesas = $lancamentos->where('tipo', 'despesa')->sum('valor');

        $categorias = $dono->categorias()
            ->when($compartilhamento->categoria_ids, fn ($q) => $q->whereIn('id', $compartilhamento->categoria_ids))
            ->with('subcategorias')->get();

        return view('compartilhamentos.ver', compact('compartilhamento', 'dono', 'lancamentos', 'receitas', 'despesas', 'mes', 'ano', 'categorias'));
    }
}
