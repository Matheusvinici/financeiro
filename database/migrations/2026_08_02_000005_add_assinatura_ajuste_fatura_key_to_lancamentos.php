<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->foreignId('assinatura_id')->nullable()->constrained('assinaturas')->nullOnDelete();
            $table->boolean('ajuste')->default(false);
            $table->string('fatura_key', 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assinatura_id');
            $table->dropColumn(['ajuste', 'fatura_key']);
        });
    }
};
