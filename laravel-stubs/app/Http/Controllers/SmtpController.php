<?php

namespace App\Http\Controllers;

use App\Models\Smtp;

class SmtpController extends Controller
{
    public function index()
    {
        $rows = Smtp::select('smtp_id', 'smtp_nome', 'smtp_endereco', 'smtp_porta')->orderBy('smtp_nome')->get();
        return view('smtp.index', compact('rows'));
    }
}

