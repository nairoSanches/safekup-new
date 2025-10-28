<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class BackupsController extends Controller
{
    public function index()
    {
        // Ajuste as colunas/ordenação conforme seu schema real
        $rows = DB::table('backups_realizados')
            ->orderBy('backup_id', 'desc')
            ->paginate(15);

        return view('backups.index', compact('rows'));
    }
}

