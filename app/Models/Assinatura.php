<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Assinatura extends Model
{
    protected $fillable = [
        'user_id', 'cartao_id', 'categoria_id', 'nome', 'valor',
        'dia_cobranca', 'ativo', 'data_inicio', 'observacao',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'dia_cobranca' => 'integer',
        'ativo' => 'boolean',
        'data_inicio' => 'date',
    ];

    /**
     * Gera/ajusta os lançamentos mensais de todas as assinaturas do usuário.
     * Assinaturas ativas criam lançamentos até 11 meses à frente (fatura prevista);
     * assinaturas desativadas mantêm apenas os meses já iniciados.
     */
    public static function sincronizar(User $user): void
    {
        foreach ($user->assinaturas()->with('cartao')->get() as $assinatura) {
            $assinatura->sincronizarLancamentos();
        }
    }

    public function sincronizarLancamentos(): void
    {
        $agora = Carbon::now();
        $inicio = $this->data_inicio
            ? $this->data_inicio->copy()->startOfMonth()
            : $this->created_at->copy()->startOfMonth();
        $fim = $this->ativo
            ? $agora->copy()->addMonths(11)->endOfMonth()
            : $agora->copy()->endOfMonth();

        if ($this->ativo) {
            $this->lancamentos()->where('data', '>', $fim)->delete();
            $this->lancamentos()->where('data', '>', $agora)
                ->where('valor', '!=', $this->valor)
                ->update(['valor' => $this->valor]);
        } else {
            $this->lancamentos()->where('data', '>', $agora->copy()->endOfMonth())->delete();
        }

        $existentes = $this->lancamentos()->pluck('data')->map(
            fn ($data) => $data->format('Y-m')
        )->flip();

        $dia = (int) $this->dia_cobranca ?: (int) $this->cartao?->dia_vencimento ?: 15;
        $chaveAtual = $agora->format('Y-m');

        for ($mes = $inicio->copy(); $mes->lte($fim); $mes->addMonth()) {
            $chave = $mes->format('Y-m');
            if ($existentes->has($chave)) {
                continue;
            }

            Lancamento::create([
                'user_id' => $this->user_id,
                'data' => $mes->copy()->day(min($dia, $mes->daysInMonth)),
                'descricao' => $this->nome,
                'valor' => $this->valor,
                'tipo' => 'despesa',
                'categoria_id' => $this->categoria_id,
                'forma_pagamento' => 'cartao',
                'cartao_id' => $this->cartao_id,
                'recorrente' => true,
                'qtd_parcelas' => 1,
                'parcela_atual' => 1,
                'assinatura_id' => $this->id,
                'pago' => $chave < $chaveAtual,
                'abate_saldo' => true,
                'observacao' => 'Assinatura gerada automaticamente',
            ]);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cartao(): BelongsTo
    {
        return $this->belongsTo(Cartao::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }
}
