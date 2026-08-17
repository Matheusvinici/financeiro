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
     */
    public static function totalAssinaturasNaFatura(User $user, string $chave): float
    {
        return (float) $user->lancamentos()
            ->whereNotNull('assinatura_id')
            ->where('fatura_key', $chave)
            ->sum('valor');
    }
}
