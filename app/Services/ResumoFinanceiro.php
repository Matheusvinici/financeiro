<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Regra única de "despesas do período" usada por dashboard, pendências,
 * cartões e relatório mensal, para que todos os totais batam.
 *
 * As assinaturas são contabilizadas no mês da cobrança (data do lançamento),
 * mesmo antes do dia da cobrança chegar — elas só saem quando a assinatura
 * é desativada. Nas faturas do cartão elas entram normalmente no ciclo de
 * fechamento/vencimento (tela de cartões).
 */
class ResumoFinanceiro
{
    /**
     * Lançamentos de assinatura do período, contados pela data de cobrança.
     */
    public static function assinaturasDoPeriodo(User $user, string $periodo, int $ano, ?int $mes = null): Collection
    {
        return $user->lancamentos()
            ->with(['categoria', 'subcategoria'])
            ->whereNotNull('assinatura_id')
            ->quando($periodo, $ano, $mes)
            ->orderBy('data')->orderBy('id')
            ->get();
    }

    /**
     * Total de assinaturas que estão dentro da fatura consolidada (chave
     * "YYYY-MM") de um cartão. Usado para tirar as assinaturas das faturas
     * quando o total é contado pelo mês de cobrança (evita contar duas vezes).
     *
     * O filtro por cartão é obrigatório ao abater de UMA fatura: sem ele o
     * mesmo valor era descontado da fatura de todos os cartões (ex.: uma
     * assinatura do Nubank gerava "-R$ 562" na fatura do Inter e o modal de
     * despesas deixava de bater com os cartões e com o resumo).
     */
    public static function totalAssinaturasNaFatura(User $user, string $chave, ?int $cartaoId = null): float
    {
        return (float) $user->lancamentos()
            ->whereNotNull('assinatura_id')
            ->when($cartaoId !== null, fn ($q) => $q->where('cartao_id', $cartaoId))
            ->where('fatura_key', $chave)
            ->sum('valor');
    }
}
