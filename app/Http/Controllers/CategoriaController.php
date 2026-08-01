<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        $receitas = auth()->user()->categorias()->where('tipo', 'receita')->with('subcategorias')->orderBy('ordem')->get();
        $despesas = auth()->user()->categorias()->where('tipo', 'despesa')->with('subcategorias')->orderBy('ordem')->get();

        return view('categorias.index', compact('receitas', 'despesas'));
    }

    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'nome' => ['required', 'string', 'max:80'],
            'tipo' => ['required', 'in:receita,despesa'],
            'cor' => ['nullable', 'string', 'max:7'],
            'icone' => ['nullable', 'string', 'max:50'],
        ]);

        auth()->user()->categorias()->create($data);

        return back()->with('success', 'Categoria criada.');
    }

    public function update(Request $request, Categoria $categoria)
    {
        abort_unless($categoria->user_id === auth()->id(), 403);

        $data = $this->validate($request, [
            'nome' => ['required', 'string', 'max:80'],
            'tipo' => ['required', 'in:receita,despesa'],
            'cor' => ['nullable', 'string', 'max:7'],
            'icone' => ['nullable', 'string', 'max:50'],
        ]);

        $categoria->update($data);

        return back()->with('success', 'Categoria atualizada.');
    }

    public function destroy(Categoria $categoria)
    {
        abort_unless($categoria->user_id === auth()->id(), 403);
        $categoria->delete();

        return back()->with('success', 'Categoria removida.');
    }

    public function storeSubcategoria(Request $request, Categoria $categoria)
    {
        abort_unless($categoria->user_id === auth()->id(), 403);

        $data = $this->validate($request, [
            'nome' => ['required', 'string', 'max:80'],
        ]);

        $categoria->subcategorias()->create([
            'user_id' => auth()->id(),
            'nome' => $data['nome'],
        ]);

        return back()->with('success', 'Item adicionado à categoria.');
    }

    public function updateSubcategoria(Request $request, Subcategoria $subcategoria)
    {
        abort_unless($subcategoria->user_id === auth()->id(), 403);

        $subcategoria->update($this->validate($request, [
            'nome' => ['required', 'string', 'max:80'],
        ]));

        return back()->with('success', 'Item atualizado.');
    }

    public function destroySubcategoria(Subcategoria $subcategoria)
    {
        abort_unless($subcategoria->user_id === auth()->id(), 403);
        $subcategoria->delete();

        return back()->with('success', 'Item removido.');
    }
}
