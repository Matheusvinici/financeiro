<?php

namespace App\Http\Controllers;

use App\Models\ContaPagar;
use Illuminate\Http\Request;

class ContaPagarController extends Controller
{
    public function index()
    {
        $statusOrdem = ['aberto' => 0, 'parcial' => 1, 'pago' => 2];

        $contas = auth()->user()->contasPagar()
            ->with('categoria')
            ->get()
            ->sortBy(fn (ContaPagar $c) => [$statusOrdem[$c->status] ?? 3, -$c->valor_restante]);

        $totalAberto = $contas->where('status', '!=', 'pago')->sum('valor_restante');

        return view('contas-pagar.index', compact('contas', 'totalAberto'));
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        auth()->user()->contasPagar()->create([
            'descricao' => $data['descricao'],
            'valor_total' => $data['valor_total'],
            'valor_pago' => 0,
            'status' => 'aberto',
            'data_vencimento' => $data['data_vencimento'] ?: null,
            'categoria_id' => $data['categoria_id'] ?: null,
            'observacao' => $data['observacao'] ?: null,
        ]);

        return back()->with('success', 'Conta a pagar registrada.');
    }

    public function update(Request $request, ContaPagar $conta)
    {
        abort_unless($conta->user_id === auth()->id(), 403);

        $data = $this->validar($request);
        $conta->update([
            'descricao' => $data['descricao'],
            'valor_total' => $data['valor_total'],
            'data_vencimento' => $data['data_vencimento'] ?: null,
            'categoria_id' => $data['categoria_id'] ?: null,
            'observacao' => $data['observacao'] ?: null,
        ]);
        $conta->refreshStatus();

        return back()->with('success', 'Conta atualizada.');
    }

    public function pagar(Request $request, ContaPagar $conta)
    {
        abort_unless($conta->user_id === auth()->id(), 403);

        $valor = (float) $request->input('valor', $conta->valor_restante);

        $conta->valor_pago = round($conta->valor_pago + $valor, 2);
        $conta->refreshStatus();
        $conta->save();

        return back()->with('success', 'Pagamento registrado na conta.');
    }

    public function destroy(ContaPagar $conta)
    {
        abort_unless($conta->user_id === auth()->id(), 403);
        $conta->delete();

        return back()->with('success', 'Conta removida.');
    }

    private function validar(Request $request): array
    {
        return $this->validate($request, [
            'descricao' => ['required', 'string', 'max:150'],
            'valor_total' => ['required', 'numeric', 'min:0.01'],
            'data_vencimento' => ['nullable', 'date'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
