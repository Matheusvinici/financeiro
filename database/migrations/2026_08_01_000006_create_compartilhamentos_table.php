<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compartilhamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dono_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('convidado_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('categoria_ids')->nullable();
            $table->boolean('so_leitura')->default(true);
            $table->timestamps();

            $table->unique(['dono_user_id', 'convidado_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compartilhamentos');
    }
};
