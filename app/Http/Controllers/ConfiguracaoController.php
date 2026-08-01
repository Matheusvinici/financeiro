<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConfiguracaoController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $config = [
            'renda_fixa' => $user->getConfig('renda_fixa'),
            'meta_poupanca' => $user->getConfig('meta_poupanca'),
            'percentual_alerta' => $user->getConfig('percentual_alerta', 50),
        ];

        return view('configuracoes.index', compact('config'));
    }

    public function update(Request $request)
    {
        $data = $this->validate($request, [
            'renda_fixa' => ['nullable', 'numeric', 'min:0'],
            'meta_poupanca' => ['nullable', 'numeric', 'min:0'],
            'percentual_alerta' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        $user = auth()->user();
        $user->setConfig('renda_fixa', $data['renda_fixa'] ?: null);
        $user->setConfig('meta_poupanca', $data['meta_poupanca'] ?: null);
        $user->setConfig('percentual_alerta', $data['percentual_alerta']);

        return back()->with('success', 'Configurações salvas.');
    }
}
