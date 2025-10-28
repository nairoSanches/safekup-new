<?php

namespace App\Http\Controllers;

use App\Models\Aplicacao;

class AplicacoesController extends Controller
{
    public function index()
    {
        $rows = Aplicacao::orderBy('app_nome')->paginate(15);
        return view('aplicacoes.index', compact('rows'));
    }
}

