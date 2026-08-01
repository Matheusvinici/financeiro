<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('descricao');
            $table->decimal('valor_total', 12, 2);
            $table->decimal('valor_pago', 12, 2)->default(0);
            $table->enum('status', ['aberto', 'parcial', 'pago'])->default('aberto');
            $table->date('data_vencimento')->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained()->nullOnDelete();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_pagar');
    }
};
