<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->enum('tipo', ['credito', 'debito', 'credito_debito'])->default('credito');
            $table->string('bandeira')->nullable();
            $table->decimal('limite', 12, 2)->default(0);
            $table->tinyInteger('dia_fechamento')->nullable();
            $table->tinyInteger('dia_vencimento')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartoes');
    }
};
