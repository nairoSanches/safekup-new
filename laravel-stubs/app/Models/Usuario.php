<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'usuario_id';
    public $timestamps = false; // ajuste conforme seu schema

    protected $fillable = [
        'usuario_nome',
        'usuario_login',
        'usuario_email',
        // 'usuario_senha', // se necessário
    ];
}

