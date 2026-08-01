<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Configuracao extends Model
{
    protected $table = 'configuracoes';

    protected $fillable = [
        'user_id', 'chave', 'valor',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
