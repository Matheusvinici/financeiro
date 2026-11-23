<?php

namespace App\Services;

/**
 * Regra única de "despesas do período" usada por dashboard, pendências,
 * cartões e relatório mensal, para que todos os totais batam.
 *
 * O que está no cartão entra pelo valor cheio da fatura (planos incluídos,
 * no mês em que caem na fatura): se está registrado no cartão, aparece
 * igual na dashboard — nunca a menos.
 */
class ResumoFinanceiro
{
}
