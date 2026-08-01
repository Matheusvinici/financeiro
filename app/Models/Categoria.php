<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $fillable = [
        'user_id', 'nome', 'tipo', 'cor', 'icone', 'ordem',
    ];

    protected $casts = [
        'ordem' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subcategorias(): HasMany
    {
        return $this->hasMany(Subcategoria::class)->orderBy('ordem')->orderBy('nome');
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }

    public function isReceita(): bool
    {
        return $this->tipo === 'receita';
    }

    public function isDespesa(): bool
    {
        return $this->tipo === 'despesa';
    }
}
