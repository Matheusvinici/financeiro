<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cartoes', function (Blueprint $table) {
            $table->unsignedSmallInteger('fatura_paga_mes')->nullable()->after('dia_vencimento');
            $table->unsignedSmallInteger('fatura_paga_ano')->nullable()->after('fatura_paga_mes');
        });
    }

    public function down(): void
    {
        Schema::table('cartoes', function (Blueprint $table) {
            $table->dropColumn(['fatura_paga_mes', 'fatura_paga_ano']);
        });
    }
};
