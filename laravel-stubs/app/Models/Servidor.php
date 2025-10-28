<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servidor extends Model
{
    protected $table = 'servidores';
    protected $primaryKey = 'servidor_id';
    public $timestamps = false;
}

