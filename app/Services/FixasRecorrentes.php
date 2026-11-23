<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Mantém as contas fixas (recorrente=true) vivas para frente.
 *
 * Conta fixa não é "por ano": uma vez criada, ela se renova sozinha até
 * 36 meses à frente de hoje. Assim um fixo criado em 2026 aparece em
 * 2027, 2028... sem precisar recriar nada a cada ano.
 *
 * Chamado dentro da consolidação (sincronização completa) e na listagem
 * de lançamentos, então qualquer tela estende o horizonte sozinha.
 * Idempotente: meses que já têm a linha são pulados.
 */
class FixasRecorrentes
{
    public static function sincronizarUsuario(User $user, int $mesesFuturo = 36, int $maxCriar = 1000): int
    {
        $hoje = Carbon::now();
        $inicioMesAtual = $hoje->copy()->startOfMonth();
        $fim = $hoje->copy()->addMonths($mesesFuturo)->endOfMonth();

        $grupos = $user->lancamentos()
            ->where('recorrente', true)
            ->whereNull('assinatura_id')
            ->selectRaw('descricao, tipo, categoria_id, subcategoria_id, MAX(data) as max_data, COUNT(*) as total')
            ->groupBy('descricao', 'tipo', 'categoria_id', 'subcategoria_id')
            ->get();

        $criados = 0;

        foreach ($grupos as $g) {
            if ($criados >= $maxCriar) {
                break;
            }

            $max = Carbon::parse($g->max_data)->startOfMonth();

            // Série encerrada no passado: não ressuscita.
            if ($max->lt($inicioMesAtual)) {
                continue;
            }

            // Já cobre o horizonte: nada a fazer.
            if ($max->gte($fim->copy()->startOfMonth())) {
                continue;
            }

            $modelo = $user->lancamentos()
                ->where('recorrente', true)
                ->whereNull('assinatura_id')
                ->where('descricao', $g->descricao)
                ->where('tipo', $g->tipo)
                ->when($g->categoria_id === null, fn ($q) => $q->whereNull('categoria_id'), fn ($q) => $q->where('categoria_id', $g->categoria_id))
                ->when($g->subcategoria_id === null, fn ($q) => $q->whereNull('subcategoria_id'), fn ($q) => $q->where('subcategoria_id', $g->subcategoria_id))
                ->whereYear('data', $max->year)
                ->whereMonth('data', $max->month)
                ->orderBy('id')
                ->get();

            if ($modelo->isEmpty()) {
                continue;
            }

            for ($m = $max->copy()->addMonth(); $m->lte($fim) && $criados < $maxCriar; $m->addMonth()) {
                foreach ($modelo as $t) {
                    $dia = min((int) $t->data->day, (int) $m->daysInMonth);
                    $novaData = $m->copy()->day($dia);

                    $existe = $user->lancamentos()
                        ->where('descricao', $t->descricao)
                        ->where('tipo', $t->tipo)
                        ->when($t->categoria_id === null, fn ($q) => $q->whereNull('categoria_id'), fn ($q) => $q->where('categoria_id', $t->categoria_id))
                        ->when($t->subcategoria_id === null, fn ($q) => $q->whereNull('subcategoria_id'), fn ($q) => $q->where('subcategoria_id', $t->subcategoria_id))
                        ->whereDate('data', $novaData->format('Y-m-d'))
                        ->where('valor', $t->valor)
                        ->exists();

                    if ($existe) {
                        continue;
                    }

                    $user->lancamentos()->create([
                        'data' => $novaData->format('Y-m-d'),
                        'descricao' => $t->descricao,
                        'valor' => $t->valor,
                        'tipo' => $t->tipo,
                        'categoria_id' => $t->categoria_id,
                        'subcategoria_id' => $t->subcategoria_id,
                        'forma_pagamento' => $t->forma_pagamento,
                        'cartao_id' => $t->cartao_id,
                        'recorrente' => true,
                        'qtd_parcelas' => 1,
                        'parcela_atual' => 1,
                        'origem_id' => null,
                        'observacao' => $t->observacao,
                        'pago' => $novaData->copy()->startOfDay()->lte($hoje->copy()->startOfDay()) ? (bool) $t->pago : false,
                        'abate_saldo' => (bool) $t->abate_saldo,
                        'cartao_debito' => (bool) $t->cartao_debito,
                    ]);

                    $criados++;
                }
            }
        }

        return $criados;
    }
}
