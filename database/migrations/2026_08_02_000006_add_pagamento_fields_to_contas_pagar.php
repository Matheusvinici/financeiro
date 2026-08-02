<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_pagar', function (Blueprint $table) {
            $table->string('descricao_pagamento')->nullable()->after('observacao');
            $table->string('quem_pagou')->nullable()->after('descricao_pagamento');
        });
    }

    public function down(): void
    {
        Schema::table('contas_pagar', function (Blueprint $table) {
            $table->dropColumn(['descricao_pagamento', 'quem_pagou']);
        });
    }
};
