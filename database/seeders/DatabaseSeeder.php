<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'matheus2vandrade@gmail.com'],
            [
                'name' => 'Matheus Andrade',
                'password' => Hash::make('Carpediem1996#'),
            ]
        );

        $admin->setConfig('percentual_alerta', 50);

        $this->criarCategoriaBase($admin, 'RECEITAS', 'receita', '#198754', 'fa-money-bill-wave', ['Salário', 'Décimo', 'Férias', 'Bolsa', 'Outras']);
        $this->criarCategoriaBase($admin, 'CASA', 'despesa', '#ffc107', 'fa-house', ['Energia', 'Internet', 'Feira']);
        $this->criarCategoriaBase($admin, 'CARTÕES', 'despesa', '#dc3545', 'fa-credit-card', []);
        $this->criarCategoriaBase($admin, 'LAZER', 'despesa', '#6f42c1', 'fa-futbol', ['Lazer', 'Viagem']);
        $this->criarCategoriaBase($admin, 'SAÚDE', 'despesa', '#20c997', 'fa-heart-pulse', ['Plano']);
        $this->criarCategoriaBase($admin, 'CARRO', 'despesa', '#0d6efd', 'fa-car', ['Seguro', 'Consorcio', 'Gasolina']);
    }

    private function criarCategoriaBase(User $user, string $nome, string $tipo, string $cor, string $icone, array $itens): void
    {
        $categoria = Categoria::firstOrCreate(
            ['user_id' => $user->id, 'nome' => $nome],
            ['tipo' => $tipo, 'cor' => $cor, 'icone' => $icone, 'ordem' => 0]
        );

        foreach ($itens as $item) {
            Subcategoria::firstOrCreate(
                ['categoria_id' => $categoria->id, 'nome' => $item],
                ['user_id' => $user->id, 'ordem' => 0]
            );
        }
    }
}
