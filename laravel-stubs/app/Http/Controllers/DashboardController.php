<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Coletas de métricas rápidas (ajuste os nomes conforme seu schema real)
        $metrics = [
            'usuarios'            => DB::table('usuarios')->count(),
            'servidores'          => DB::table('servidores')->count(),
            'aplicacoes'          => DB::table('aplicacao')->count(),
            'tipos'               => DB::table('tipo')->count(),
            'smtp'                => DB::table('smtp')->count(),
            'documentos'          => DB::table('documentos')->count(),
            'computadores'        => DB::table('computadores')->count(),
            'sistemas_operacionais'=> DB::table('sistemas_operacionais')->count(),
            'setores'             => DB::table('setores')->count(),
            'backups_sucesso'     => DB::table('backups_realizados')->where('backup_status', 'SUCESSO')->count(),
            'backups_falha'       => DB::table('backups_realizados')->where('backup_status', 'FALHA')->count(),
        ];
        
        // Últimos backups (ajuste a coluna de ordenação conforme seu schema)
        $ultimosBackups = DB::table('backups_realizados')
            ->orderBy('backup_id', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard', compact('metrics', 'ultimosBackups'));
    }
}
