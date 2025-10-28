<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Smtp extends Model
{
    protected $table = 'smtp';
    protected $primaryKey = 'smtp_id';
    public $timestamps = false;
}

