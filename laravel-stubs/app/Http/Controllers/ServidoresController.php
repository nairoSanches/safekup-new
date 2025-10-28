<?php

namespace App\Http\Controllers;

use App\Models\Servidor;

class ServidoresController extends Controller
{
    public function index()
    {
        $rows = Servidor::orderBy('servidor_id', 'desc')->paginate(15);
        return view('servidores.index', compact('rows'));
    }
}

