<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public const PERM_VER_CATEGORIA = 'ver categoria ';

    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);

        foreach (Categoria::all() as $categoria) {
            Permission::firstOrCreate(['name' => self::PERM_VER_CATEGORIA . $categoria->id, 'guard_name' => 'web']);
        }

        $adminRole->syncPermissions(Permission::all());

        $admin = User::where('email', 'matheus2vandrade@gmail.com')->first();
        if ($admin) {
            $admin->assignRole('Administrador');
        }
    }

    public static function sincronizarCategoria(Categoria $categoria): void
    {
        $permission = Permission::firstOrCreate([
            'name' => self::PERM_VER_CATEGORIA . $categoria->id,
            'guard_name' => 'web',
        ]);

        Role::where('name', 'Administrador')->first()?->givePermissionTo($permission);
    }

    public static function removerCategoria(Categoria $categoria): void
    {
        Permission::where('name', self::PERM_VER_CATEGORIA . $categoria->id)->delete();
    }
}
