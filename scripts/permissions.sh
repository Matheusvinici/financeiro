#!/bin/bash
# Script para criar permissões no Spatie Laravel Permission
# Execute: php artisan db:seed --class=PermissionSeeder
# Ou use este script para criar via tinker

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

echo "Criando permissões..."

# Lista de permissões
PERMISSIONS=(
    "barbearia.view"
    "barbearia.create"
    "barbearia.edit"
    "barbearia.delete"
    "barbeiro.view"
    "barbeiro.create"
    "barbeiro.edit"
    "barbeiro.delete"
    "servico.view"
    "servico.create"
    "servico.edit"
    "servico.delete"
    "cliente.view"
    "cliente.create"
    "cliente.edit"
    "cliente.delete"
    "agendamento.view"
    "agendamento.create"
    "agendamento.edit"
    "agendamento.delete"
    "plano.view"
    "plano.create"
    "plano.edit"
    "plano.delete"
    "relatorio.view"
    "relatorio.faturamento"
    "configuracao.edit"
    "despesa.view"
    "despesa.create"
    "despesa.edit"
    "despesa.delete"
    "caixa.view"
    "caixa.abrir"
    "caixa.fechar"
)

for perm in "${PERMISSIONS[@]}"; do
    php artisan tinker --execute="\Spatie\Permission\Models\Permission::firstOrCreate(['name' => '$perm', 'guard_name' => 'web']);"
    echo "  + $perm"
done

# Criar papel admin com todas as permissoes
echo ""
echo "Criando papel 'admin' com todas as permissões..."
php artisan tinker --execute="
\$role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
\$role->syncPermissions(\Spatie\Permission\Models\Permission::all());
"

# Atribuir papel admin ao primeiro usuario
echo ""
echo "Atribuindo papel 'admin' ao primeiro usuario..."
php artisan tinker --execute="
\$user = \App\Models\User::first();
if (\$user) { \$user->assignRole('admin'); echo 'Papel admin atribuido a ' . \$user->email; }
"

echo ""
echo "Permissões criadas com sucesso!"
