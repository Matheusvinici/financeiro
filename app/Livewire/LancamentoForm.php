<?php

namespace App\Livewire;

use App\Models\Lancamento;
use App\Models\Subcategoria;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LancamentoForm extends Component
{
    public ?int $lancamentoId = null;

    public string $tipo = '';

    public string $descricao = '';

    public string $valor = '';

    public string $data = '';

    public ?int $categoria_id = null;

    public ?int $subcategoria_id = null;

    public ?string $forma_pagamento = null;

    public ?int $cartao_id = null;

    public string $repeticao = 'unica';

    public ?string $data_fim = null;

    public string $observacao = '';

    public function mount(?Lancamento $lancamento = null): void
    {
        $this->lancamentoId = $lancamento?->id;
        $this->data = $lancamento?->data?->format('Y-m-d') ?? now()->format('Y-m-d');

        if (!$lancamento) {
            return;
        }

        $this->tipo = $lancamento->tipo;
        $this->descricao = $lancamento->descricao;
        $this->valor = number_format((float) $lancamento->valor, 2, ',', '.');
        $this->categoria_id = $lancamento->categoria_id;
        $this->subcategoria_id = $lancamento->subcategoria_id;
        $this->forma_pagamento = $lancamento->forma_pagamento;
        $this->cartao_id = $lancamento->cartao_id;
        $this->observacao = $lancamento->observacao ?? '';

        if ($lancamento->recorrente) {
            $this->repeticao = 'todo_mes';
        } elseif ($lancamento->isParcela()) {
            $this->repeticao = 'periodo';
            $this->data_fim = $lancamento->data?->copy()->addMonths($lancamento->qtd_parcelas - 1)->format('Y-m-d');
        }
    }

    public function title(): string
    {
        return $this->lancamentoId ? 'Editar lançamento' : 'Novo lançamento';
    }

    #[Computed]
    public function categorias()
    {
        return auth()->user()->categorias()->orderBy('tipo')->orderBy('nome')->get();
    }

    #[Computed]
    public function cartoes()
    {
        return auth()->user()->cartoes()->where('ativo', true)->orderBy('nome')->get();
    }

    #[Computed]
    public function itens()
    {
        if (!$this->categoria_id) {
            return collect();
        }

        return Subcategoria::where('categoria_id', $this->categoria_id)
            ->where('user_id', auth()->id())
            ->orderBy('nome')->get();
    }

    public function updatedTipo(): void
    {
        $this->categoria_id = null;
        $this->subcategoria_id = null;
        $this->forma_pagamento = null;
        $this->cartao_id = null;
    }

    public function updatedCategoriaId(): void
    {
        $this->subcategoria_id = null;
    }

    public function updatedValor($valor): void
    {
        $this->valor = $this->converterValor($valor);
    }

    private function converterValor($valor): ?float
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        $s = (string) $valor;
        $s = preg_replace('/[^\d.,\-]/', '', $s);
        if ($s === '' || $s === '-') {
            return null;
        }

        $negativo = str_starts_with($s, '-');
        $s = ltrim($s, '-');
        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }

        return round((float) $s, 2) * ($negativo ? -1 : 1);
    }

    public function save()
    {
        $this->validate([
            'descricao' => ['required', 'string', 'max:150'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data' => ['required', 'date'],
            'tipo' => ['required', 'in:receita,despesa'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'subcategoria_id' => ['nullable', 'exists:subcategorias,id'],
            'forma_pagamento' => ['nullable', 'in:cartao,pix,dinheiro,boleto,outros'],
            'cartao_id' => ['nullable', 'exists:cartoes,id'],
            'repeticao' => ['required', 'in:unica,todo_mes,periodo'],
            'data_fim' => ['nullable', 'required_if:repeticao,periodo', 'date', 'after_or_equal:data'],
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);

        $valor = $this->converterValor($this->valor);
        $this->valor = $valor !== null ? number_format($valor, 2, ',', '.') : '';

        $user = auth()->user();
        $subcategoriaId = $this->subcategoria_id;

        $recorrente = $this->repeticao === 'todo_mes';

        if ($this->repeticao === 'periodo' && $this->data_fim) {
            $qtd = max(1, Carbon::parse($this->data)->startOfMonth()
                ->diffInMonths(Carbon::parse($this->data_fim)->startOfMonth()) + 1);
        } else {
            $qtd = 1;
        }

        if ($this->lancamentoId) {
            $lancamento = Lancamento::findOrFail($this->lancamentoId);
            abort_unless($lancamento->user_id === $user->id, 403);

            $lancamento->update([
                'data' => $this->data,
                'descricao' => $this->descricao,
                'valor' => $valor,
                'tipo' => $this->tipo,
                'categoria_id' => $this->categoria_id ?: null,
                'subcategoria_id' => $subcategoriaId ?: null,
                'forma_pagamento' => $this->forma_pagamento ?: null,
                'cartao_id' => $this->forma_pagamento === 'cartao' ? $this->cartao_id : null,
                'recorrente' => $recorrente,
                'qtd_parcelas' => 1,
                'parcela_atual' => 1,
                'observacao' => $this->observacao ?: null,
            ]);

            session()->flash('success', 'Lançamento atualizado.');

            return redirect()->route('lancamentos.index');
        }

        if ($qtd === 1) {
            $user->lancamentos()->create([
                'data' => $this->data,
                'descricao' => $this->descricao,
                'valor' => $valor,
                'tipo' => $this->tipo,
                'categoria_id' => $this->categoria_id ?: null,
                'subcategoria_id' => $subcategoriaId ?: null,
                'forma_pagamento' => $this->forma_pagamento ?: null,
                'cartao_id' => $this->forma_pagamento === 'cartao' ? $this->cartao_id : null,
                'recorrente' => $recorrente,
                'qtd_parcelas' => 1,
                'parcela_atual' => 1,
                'origem_id' => null,
                'observacao' => $this->observacao ?: null,
            ]);
        } else {
            $primeiro = $user->lancamentos()->create([
                'data' => $this->data,
                'descricao' => $this->descricao,
                'valor' => $valor,
                'tipo' => $this->tipo,
                'categoria_id' => $this->categoria_id ?: null,
                'subcategoria_id' => $subcategoriaId ?: null,
                'forma_pagamento' => $this->forma_pagamento ?: null,
                'cartao_id' => $this->forma_pagamento === 'cartao' ? $this->cartao_id : null,
                'recorrente' => $recorrente,
                'qtd_parcelas' => $qtd,
                'parcela_atual' => 1,
                'origem_id' => null,
                'observacao' => $this->observacao ?: null,
            ]);

            for ($i = 2; $i <= $qtd; $i++) {
                $user->lancamentos()->create([
                    'data' => Carbon::parse($this->data)->addMonths($i - 1)->format('Y-m-d'),
                    'descricao' => $this->descricao . " ({$i}/{$qtd})",
                    'valor' => $valor,
                    'tipo' => $this->tipo,
                    'categoria_id' => $this->categoria_id ?: null,
                    'subcategoria_id' => $subcategoriaId ?: null,
                    'forma_pagamento' => $this->forma_pagamento ?: null,
                    'cartao_id' => $this->forma_pagamento === 'cartao' ? $this->cartao_id : null,
                    'recorrente' => $recorrente,
                    'qtd_parcelas' => $qtd,
                    'parcela_atual' => $i,
                    'origem_id' => $primeiro->id,
                    'observacao' => $this->observacao ?: null,
                ]);
            }
        }

        session()->flash('success', $qtd > 1 ? "Lançamento criado para {$qtd} meses." : 'Lançamento registrado.');

        return redirect()->route('lancamentos.index');
    }

    public function render()
    {
        return view('livewire.lancamento-form');
    }
}
