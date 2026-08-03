<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cartoes', function (Blueprint $table) {
            $table->json('faturas_pagas')->nullable()->after('dia_vencimento');
        });

        // Migrar dados existentes
        $cartoes = DB::table('cartoes')
            ->whereNotNull('fatura_paga_mes')
            ->whereNotNull('fatura_paga_ano')
            ->get();

        foreach ($cartoes as $cartao) {
            $faturasPagas = [['mes' => $cartao->fatura_paga_mes, 'ano' => $cartao->fatura_paga_ano]];
            DB::table('cartoes')
                ->where('id', $cartao->id)
                ->update(['faturas_pagas' => json_encode($faturasPagas)]);
        }

        Schema::table('cartoes', function (Blueprint $table) {
            $table->dropColumn(['fatura_paga_mes', 'fatura_paga_ano']);
        });
    }

    public function down(): void
    {
        Schema::table('cartoes', function (Blueprint $table) {
            $table->integer('fatura_paga_mes')->nullable()->after('dia_vencimento');
            $table->integer('fatura_paga_ano')->nullable()->after('fatura_paga_mes');
        });

        // Migrar dados de volta (pega a última fatura paga)
        $cartoes = DB::table('cartoes')
            ->whereNotNull('faturas_pagas')
            ->get();

        foreach ($cartoes as $cartao) {
            $faturas = json_decode($cartao->faturas_pagas, true);
            if (!empty($faturas)) {
                $ultima = end($faturas);
                DB::table('cartoes')
                    ->where('id', $cartao->id)
                    ->update([
                        'fatura_paga_mes' => $ultima['mes'],
                        'fatura_paga_ano' => $ultima['ano'],
                    ]);
            }
        }

        Schema::table('cartoes', function (Blueprint $table) {
            $table->dropColumn('faturas_pagas');
        });
    }
};
