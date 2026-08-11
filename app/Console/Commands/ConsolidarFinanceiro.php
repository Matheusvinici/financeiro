<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ConsolidacaoFinanceira;
use Illuminate\Console\Command;

class ConsolidarFinanceiro extends Command
{
    protected $signature = 'consolidar:financeiro {--user= : ID do usuário (padrão: todos)}';

    protected $description = 'Recomputa a consolidação financeira (fatura_cartoes e resumos_mensais)';

    public function handle(): int
    {
        $query = User::query();
        if ($userId = $this->option('user')) {
            $query->where('id', (int) $userId);
        }

        $query->each(function (User $user) {
            ConsolidacaoFinanceira::sincronizarUsuario($user);
            $this->info("Usuário {$user->id} consolidado.");
        });

        $this->info('Consolidação concluída.');

        return self::SUCCESS;
    }
}