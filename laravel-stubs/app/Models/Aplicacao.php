<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aplicacao extends Model
{
    protected $table = 'aplicacao';
    protected $primaryKey = 'app_id';
    public $timestamps = false;
}

