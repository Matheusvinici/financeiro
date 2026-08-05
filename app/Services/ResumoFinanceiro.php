<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Regra única de "despesas do período" usada por dashboard, pendências,
 * cartões e relatório mensal, para que todos os totais batam.
 *
 * Total do mês = despesas com data no período (inclusive compras no cartão),
 * que abatem do saldo, excluindo assinaturas cujo dia de cobrança ainda não
 * chegou (elas ainda não entraram no cartão e aparecem separadas).
 */
class ResumoFinanceiro
{
    /**
     * Total de despesas do período que abatem do saldo, contadas pela data
     * do lançamento. Assinaturas com cobrança futura ficam de fora.
     */
    public static function despesasPeriodo(User $user, string $periodo, int $ano, ?int $mes = null): float
    {
        return (float) $user->lancamentos()
            ->where('tipo', 'despesa')
            ->where('abate_saldo', true)
            ->semAssinaturasFuturas()
            ->quando($periodo, $ano, $mes)
            ->sum('valor');
    }

    /**
     * Lançamentos de assinatura do período cujo dia de cobrança ainda não
     * chegou (ainda não entraram no cartão).
     */
    public static function assinaturasEntramCartao(User $user, string $periodo, int $ano, ?int $mes = null): Collection
    {
        return $user->lancamentos()
            ->with(['cartao', 'categoria'])
            ->where('tipo', 'despesa')
            ->soAssinaturasFuturas()
            ->quando($periodo, $ano, $mes)
            ->orderBy('data')->orderBy('id')
            ->get();
    }

    /**
     * Total das assinaturas do período que ainda não entraram no cartão.
     */
    public static function totalAssinaturasEntram(User $user, string $periodo, int $ano, ?int $mes = null): float
    {
        return (float) self::assinaturasEntramCartao($user, $periodo, $ano, $mes)->sum('valor');
    }
}
