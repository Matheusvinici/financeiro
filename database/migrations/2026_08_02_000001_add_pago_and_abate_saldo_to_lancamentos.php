<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->boolean('pago')->default(true)->after('observacao');
            $table->boolean('abate_saldo')->default(true)->after('pago');
        });
    }

    public function down(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->dropColumn(['pago', 'abate_saldo']);
        });
    }
};
