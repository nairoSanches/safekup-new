<?php

namespace App\Http\Controllers;

use App\Models\Tipo;

class TiposController extends Controller
{
    public function index()
    {
        $rows = Tipo::orderBy('tipo_nome')->paginate(15);
        return view('tipos.index', compact('rows'));
    }
}

