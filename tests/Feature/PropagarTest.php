<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Livewire\LancamentoForm;
use App\Models\Categoria;
use App\Models\Lancamento;
use App\Models\User;
use Livewire\Livewire;

class PropagarTest extends TestCase
{
    public function test_editar_fixa_aplica_em_todos_ou_so_mes()
    {
        $user = User::factory()->create();
        $cat = $user->categorias()->create(['nome' => 'TESTE CAT', 'tipo' => 'despesa']);

        $ids = [];
        foreach (['2026-07-15', '2026-08-15', '2026-09-15'] as $d) {
            $ids[] = $user->lancamentos()->create([
                'data' => $d, 'descricao' => 'TESTE FIXA', 'valor' => 100,
                'tipo' => 'despesa', 'categoria_id' => $cat->id,
                'forma_pagamento' => 'pix', 'recorrente' => true,
                'qtd_parcelas' => 1, 'parcela_atual' => 1,
                'abate_saldo' => true, 'pago' => true,
            ])->id;
        }

        $this->actingAs($user);

        Livewire::test(LancamentoForm::class, ['lancamento' => $ids[1]])
            ->set('propagar', 'mes')
            ->set('observacao', 'obs mes')
            ->call('save');

        $this->assertSame('obs mes', Lancamento::find($ids[1])->observacao);
        $this->assertNull(Lancamento::find($ids[0])->observacao);
        $this->assertNull(Lancamento::find($ids[2])->observacao);

        Livewire::test(LancamentoForm::class, ['lancamento' => $ids[1]])
            ->set('propagar', 'todos')
            ->set('descricao', 'TESTE TODOS')
            ->set('categoria_id', null)
            ->set('abate_saldo', false)
            ->call('save');

        foreach ($ids as $id) {
            $this->assertSame('TESTE TODOS', Lancamento::find($id)->descricao);
            $this->assertFalse((bool) Lancamento::find($id)->abate_saldo);
        }

        Lancamento::whereIn('id', $ids)->delete();
        $cat->delete();
        $user->delete();
    }
}
