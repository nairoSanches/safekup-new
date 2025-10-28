@extends('layouts.app')

@section('content')
  <div class="mt-6">
    <h1 class="text-2xl font-semibold">Dashboard</h1>
    <p class="mt-2 text-slate-300">Visão geral dos seus ativos e backups.</p>

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <!-- Cards -->
      <div class="rounded-xl bg-white/5 ring-1 ring-white/10 p-4">
        <p class="text-slate-400 text-sm">Usuários</p>
        <p class="mt-1 text-3xl font-semibold">{{ $metrics['usuarios'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl bg-white/5 ring-1 ring-white/10 p-4">
        <p class="text-slate-400 text-sm">Servidores</p>
        <p class="mt-1 text-3xl font-semibold">{{ $metrics['servidores'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl bg-white/5 ring-1 ring-white/10 p-4">
        <p class="text-slate-400 text-sm">Aplicações</p>
        <p class="mt-1 text-3xl font-semibold">{{ $metrics['aplicacoes'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl bg-white/5 ring-1 ring-white/10 p-4">
        <p class="text-slate-400 text-sm">Tipos DB</p>
        <p class="mt-1 text-3xl font-semibold">{{ $metrics['tipos'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl bg-white/5 ring-1 ring-white/10 p-4">
        <p class="text-slate-400 text-sm">SMTP</p>
        <p class="mt-1 text-3xl font-semibold">{{ $metrics['smtp'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl bg-white/5 ring-1 ring-white/10 p-4">
        <p class="text-slate-400 text-sm">Documentos</p>
        <p class="mt-1 text-3xl font-semibold">{{ $metrics['documentos'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl bg-white/5 ring-1 ring-white/10 p-4">
        <p class="text-slate-400 text-sm">Computadores</p>
        <p class="mt-1 text-3xl font-semibold">{{ $metrics['computadores'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl bg-white/5 ring-1 ring-white/10 p-4">
        <p class="text-slate-400 text-sm">Sistemas Operacionais</p>
        <p class="mt-1 text-3xl font-semibold">{{ $metrics['sistemas_operacionais'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl bg-white/5 ring-1 ring-white/10 p-4">
        <p class="text-slate-400 text-sm">Setores</p>
        <p class="mt-1 text-3xl font-semibold">{{ $metrics['setores'] ?? 0 }}</p>
      </div>
    </div>

    <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="rounded-xl bg-green-500/10 ring-1 ring-green-500/30 p-4">
        <p class="text-slate-300 text-sm">Backups com sucesso</p>
        <p class="mt-1 text-3xl font-semibold text-green-400">{{ $metrics['backups_sucesso'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl bg-pink-500/10 ring-1 ring-pink-500/30 p-4">
        <p class="text-slate-300 text-sm">Backups com falha</p>
        <p class="mt-1 text-3xl font-semibold text-pink-400">{{ $metrics['backups_falha'] ?? 0 }}</p>
      </div>
    </div>

    <div class="mt-10">
      <a href="{{ route('smtp.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-white/5 ring-1 ring-white/10 px-4 py-2 text-sm hover:bg-white/10">
        <i class="fa fa-envelope"></i>
        <span>Ver configurações de SMTP</span>
      </a>
      <a href="{{ route('backups.index') }}" class="ml-3 inline-flex items-center gap-2 rounded-lg bg-white/5 ring-1 ring-white/10 px-4 py-2 text-sm hover:bg-white/10">
        <i class="fa fa-history"></i>
        <span>Ver backups recentes</span>
      </a>
      <a href="{{ route('servidores.index') }}" class="ml-3 inline-flex items-center gap-2 rounded-lg bg-white/5 ring-1 ring-white/10 px-4 py-2 text-sm hover:bg-white/10">
        <i class="fa fa-server"></i>
        <span>Servidores</span>
      </a>
      <a href="{{ route('aplicacoes.index') }}" class="ml-3 inline-flex items-center gap-2 rounded-lg bg-white/5 ring-1 ring-white/10 px-4 py-2 text-sm hover:bg-white/10">
        <i class="fa fa-cubes"></i>
        <span>Aplicações</span>
      </a>
      <a href="{{ route('tipos.index') }}" class="ml-3 inline-flex items-center gap-2 rounded-lg bg-white/5 ring-1 ring-white/10 px-4 py-2 text-sm hover:bg-white/10">
        <i class="fa fa-database"></i>
        <span>Tipos de Banco</span>
      </a>
    </div>

    <div class="mt-10">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Últimos Backups</h2>
        <a href="{{ route('backups.index') }}" class="text-sm text-indigo-400 hover:text-indigo-300">Ver todos</a>
      </div>
      <div class="mt-4 overflow-x-auto rounded-xl ring-1 ring-white/10">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-white/5 text-slate-300">
            <tr>
              <th class="px-4 py-2">ID</th>
              <th class="px-4 py-2">Status</th>
              <th class="px-4 py-2">Data</th>
              <th class="px-4 py-2">BD</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($ultimosBackups as $r)
              <tr class="border-t border-white/10">
                <td class="px-4 py-2">{{ $r->backup_id ?? '-' }}</td>
                <td class="px-4 py-2">
                  <span class="inline-flex items-center rounded px-2 py-0.5 text-xs {{ ($r->backup_status ?? '') === 'SUCESSO' ? 'bg-green-500/10 text-green-300 ring-1 ring-green-500/30' : 'bg-pink-500/10 text-pink-300 ring-1 ring-pink-500/30' }}">
                    {{ $r->backup_status ?? '-' }}
                  </span>
                </td>
                <td class="px-4 py-2">{{ $r->backup_data ?? ($r->created_at ?? '-') }}</td>
                <td class="px-4 py-2">{{ $r->bd_id ?? '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="px-4 py-6 text-center text-slate-400">Nenhum registro.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
