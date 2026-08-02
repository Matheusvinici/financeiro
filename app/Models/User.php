<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function categorias()
    {
        return $this->hasMany(Categoria::class);
    }

    public function cartoes()
    {
        return $this->hasMany(Cartao::class);
    }

    public function lancamentos()
    {
        return $this->hasMany(Lancamento::class);
    }

    public function contasPagar()
    {
        return $this->hasMany(ContaPagar::class);
    }

    public function compartilhamentosEnviados()
    {
        return $this->hasMany(Compartilhamento::class, 'dono_user_id');
    }

    public function compartilhamentosRecebidos()
    {
        return $this->hasMany(Compartilhamento::class, 'convidado_user_id');
    }

    public function configuracoes()
    {
        return $this->hasMany(Configuracao::class);
    }

    public function getConfig(string $chave, $padrao = null)
    {
        $cfg = $this->configuracoes()->where('chave', $chave)->first();

        return $cfg ? $cfg->valor : $padrao;
    }

    public function setConfig(string $chave, $valor): void
    {
        $this->configuracoes()->updateOrCreate(
            ['chave' => $chave],
            ['valor' => $valor]
        );
    }
}
