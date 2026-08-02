<?php

namespace App\Http\Controllers;

use App\Models\Assinatura;
use Illuminate\Http\Request;

class AssinaturaController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validar($request);

        $assinatura = auth()->user()->assinaturas()->create([
            'cartao_id' => $data['cartao_id'],
            'categoria_id' => $data['categoria_id'],
            'nome' => $data['nome'],
            'valor' => $data['valor'],
            'dia_cobranca' => $data['dia_cobranca'] ?? null,
            'ativo' => $request->boolean('ativo', true),
            'data_inicio' => $data['data_inicio'] ?? null,
            'observacao' => $data['observacao'] ?? null,
        ]);

        $assinatura->sincronizarLancamentos();

        return back()->with('success', "Assinatura {$assinatura->nome} cadastrada.");
    }

    public function update(Request $request, Assinatura $assinatura)
    {
        abort_unless($assinatura->user_id === auth()->id(), 403);

        $data = $this->validar($request);

        $assinatura->update([
            'cartao_id' => $data['cartao_id'],
            'categoria_id' => $data['categoria_id'],
            'nome' => $data['nome'],
            'valor' => $data['valor'],
            'dia_cobranca' => $data['dia_cobranca'] ?? null,
            'ativo' => $request->boolean('ativo', true),
            'data_inicio' => $data['data_inicio'] ?? null,
            'observacao' => $data['observacao'] ?? null,
        ]);

        $assinatura->sincronizarLancamentos();

        return back()->with('success', 'Assinatura atualizada.');
    }

    public function alterarValor(Request $request, Assinatura $assinatura)
    {
        abort_unless($assinatura->user_id === auth()->id(), 403);

        $data = $this->validate($request, [
            'novo_valor' => ['required', 'numeric', 'min:0.01'],
        ]);

        $assinatura->update(['valor' => $data['novo_valor']]);
        $assinatura->sincronizarLancamentos();

        return back()->with('success', 'Valor da assinatura atualizado. Lançamentos futuros ajustados.');
    }

    public function toggle(Assinatura $assinatura)
    {
        abort_unless($assinatura->user_id === auth()->id(), 403);

        $assinatura->update(['ativo' => !$assinatura->ativo]);
        $assinatura->sincronizarLancamentos();

        return back()->with('success', 'Assinatura ' . ($assinatura->ativo ? 'ativada' : 'desativada') . '.');
    }

    public function destroy(Assinatura $assinatura)
    {
        abort_unless($assinatura->user_id === auth()->id(), 403);

        $assinatura->lancamentos()->delete();
        $assinatura->delete();

        return back()->with('success', 'Assinatura removida.');
    }

    private function validar(Request $request): array
    {
        return $this->validate($request, [
            'cartao_id' => ['required', 'exists:cartoes,id'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'nome' => ['required', 'string', 'max:80'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'dia_cobranca' => ['nullable', 'integer', 'min:1', 'max:31'],
            'data_inicio' => ['nullable', 'date'],
            'observacao' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
