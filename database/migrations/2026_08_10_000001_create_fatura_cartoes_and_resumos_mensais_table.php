<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fatura_cartoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cartao_id')->constrained('cartoes')->cascadeOnDelete();
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('ano');
            $table->string('chave', 7);
            $table->decimal('total', 12, 2)->default(0);
            $table->unsignedInteger('qtd')->default(0);
            $table->boolean('pago')->default(false);
            $table->date('vencimento')->nullable();
            $table->timestamps();

            $table->unique(['cartao_id', 'chave']);
            $table->index(['user_id', 'ano', 'mes']);
        });

        Schema::create('resumos_mensais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('ano');
            $table->decimal('receitas_total', 12, 2)->default(0);
            $table->decimal('despesas_total', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'ano', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resumos_mensais');
        Schema::dropIfExists('fatura_cartoes');
    }
};