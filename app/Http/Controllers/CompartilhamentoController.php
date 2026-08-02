<?php

namespace App\Http\Controllers;

use App\Models\Compartilhamento;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CompartilhamentoController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $enviados = $user->compartilhamentosEnviados()->with('convidado')->get();
        $recebidos = $user->compartilhamentosRecebidos()->with('dono')->get();
        $categorias = $user->categorias()->orderBy('tipo')->orderBy('nome')->get();

        return view('compartilhamentos.index', compact('enviados', 'recebidos', 'categorias'));
    }

    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'categoria_ids' => ['nullable', 'array'],
            'categoria_ids.*' => ['integer', 'exists:categorias,id'],
        ]);

        $user = auth()->user();
        $email = strtolower($data['email']);

        if ($email === $user->email) {
            return back()->withErrors(['email' => 'Você não pode compartilhar com você mesmo.']);
        }

        $existe = User::where('email', $email)->exists();
        $convidado = $existe ? User::where('email', $email)->first() : User::create([
            'name' => $data['name'],
            'email' => $email,
            'password' => Hash::make($data['password']),
        ]);

        $categorias = $user->categorias()
            ->when(! empty($data['categoria_ids']), fn ($q) => $q->whereIn('id', $data['categoria_ids']))
            ->pluck('id');

        $ids = $categorias->values()->all();

        $compartilhamento = $user->compartilhamentosEnviados()->updateOrCreate(
            ['convidado_user_id' => $convidado->id],
            [
                'categoria_ids' => $ids ?: null,
                'so_leitura' => true,
            ]
        );

        $this->sincronizarPapel($user, $convidado, $compartilhamento, $ids);

        $mensagem = $existe
            ? 'Usuário vinculado e papel atualizado com sucesso.'
            : 'Usuário cadastrado e papel vinculado com sucesso.';

        return back()->with('success', 'Compartilhamento atualizado. ' . $mensagem);
    }

    public function destroy(Compartilhamento $compartilhamento)
    {
        abort_unless($compartilhamento->dono_user_id === auth()->id(), 403);

        $this->removerPapel($compartilhamento);

        $compartilhamento->delete();

        return back()->with('success', 'Compartilhamento removido.');
    }

    public function visao()
    {
        $user = auth()->user();
        $compartilhamentos = $user->compartilhamentosRecebidos()->with('dono')->get();

        return view('compartilhamentos.visao', compact('compartilhamentos'));
    }

    public function ver(Request $request, Compartilhamento $compartilhamento)
    {
        abort_unless($compartilhamento->convidado_user_id === auth()->id(), 403);

        $dono = $compartilhamento->dono;
        $mes = (int) $request->input('mes', now()->month);
        $ano = (int) $request->input('ano', now()->year);

        $ids = $this->idsVisiveis($compartilhamento);

        $query = $dono->lancamentos()->with(['categoria', 'subcategoria', 'cartao'])
            ->whereYear('data', $ano)->whereMonth('data', $mes);

        if ($ids) {
            $query->whereIn('categoria_id', $ids);
        }

        $lancamentos = $query->orderByDesc('data')->orderByDesc('id')->get();

        $receitas = $lancamentos->where('tipo', 'receita')->sum('valor');
        $despesas = $lancamentos->where('tipo', 'despesa')->sum('valor');

        $categorias = $dono->categorias()
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->with('subcategorias')->get();

        return view('compartilhamentos.ver', compact('compartilhamento', 'dono', 'lancamentos', 'receitas', 'despesas', 'mes', 'ano', 'categorias'));
    }

    private function nomePapel(int $donoId, int $convidadoId): string
    {
        return "convidado-{$donoId}-{$convidadoId}";
    }

    private function sincronizarPapel(User $dono, User $convidado, Compartilhamento $compartilhamento, array $categoriaIds): void
    {
        $role = Role::firstOrCreate(['name' => $this->nomePapel($dono->id, $convidado->id), 'guard_name' => 'web']);

        $permissions = $categoriaIds
            ? Permission::whereIn('name', array_map(fn ($id) => PermissionSeeder::PERM_VER_CATEGORIA . $id, $categoriaIds))->get()
            : $dono->categorias()->get()->map(fn ($c) => Permission::firstOrCreate([
                'name' => PermissionSeeder::PERM_VER_CATEGORIA . $c->id,
                'guard_name' => 'web',
            ]));

        $role->syncPermissions($permissions);
        $convidado->assignRole($role);

        $compartilhamento->update(['categoria_ids' => $categoriaIds ?: null]);
    }

    private function removerPapel(Compartilhamento $compartilhamento): void
    {
        $role = Role::where('name', $this->nomePapel($compartilhamento->dono_user_id, $compartilhamento->convidado_user_id))->first();

        if ($role) {
            $compartilhamento->convidado->removeRole($role);
            $role->delete();
        }
    }

    public function idsVisiveis(Compartilhamento $compartilhamento): array
    {
        $role = Role::where('name', $this->nomePapel($compartilhamento->dono_user_id, $compartilhamento->convidado_user_id))->first();

        if (! $role) {
            return $compartilhamento->categoria_ids ?? [];
        }

        return $role->permissions()
            ->where('name', 'like', PermissionSeeder::PERM_VER_CATEGORIA . '%')
            ->get()
            ->map(fn ($p) => (int) str_replace(PermissionSeeder::PERM_VER_CATEGORIA, '', $p->name))
            ->filter()
            ->values()
            ->all();
    }
}
