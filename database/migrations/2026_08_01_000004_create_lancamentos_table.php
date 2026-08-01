<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('data');
            $table->string('descricao');
            $table->decimal('valor', 12, 2);
            $table->enum('tipo', ['receita', 'despesa'])->default('despesa');
            $table->foreignId('categoria_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subcategoria_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('forma_pagamento', ['cartao', 'pix', 'dinheiro', 'boleto', 'outros'])->nullable();
            $table->foreignId('cartao_id')->nullable()->constrained('cartoes')->nullOnDelete();
            $table->boolean('recorrente')->default(false);
            $table->tinyInteger('qtd_parcelas')->default(1);
            $table->tinyInteger('parcela_atual')->default(1);
            $table->foreignId('origem_id')->nullable()->constrained('lancamentos')->nullOnDelete();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos');
    }
};
