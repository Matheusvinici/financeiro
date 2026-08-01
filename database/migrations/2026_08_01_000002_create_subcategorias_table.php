<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcategorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->integer('ordem')->default(0);
            $table->timestamps();

            $table->unique(['categoria_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategorias');
    }
};
