@extends('layouts.app')

@section('content')
  <div class="mt-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold">Backups Recentes</h1>
      <a href="{{ route('dashboard') }}" class="text-sm text-indigo-400 hover:text-indigo-300">← Voltar ao Dashboard</a>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl ring-1 ring-white/10">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-white/5 text-slate-300">
          <tr>
            <th class="px-4 py-2">ID</th>
            <th class="px-4 py-2">Status</th>
            <th class="px-4 py-2">Data</th>
            <th class="px-4 py-2">BD</th>
            <th class="px-4 py-2">Mensagem</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($rows as $r)
            <tr class="border-t border-white/10">
              <td class="px-4 py-2">{{ $r->backup_id ?? '-' }}</td>
              <td class="px-4 py-2">
                <span class="inline-flex items-center rounded px-2 py-0.5 text-xs
                  {{ ($r->backup_status ?? '') === 'SUCESSO' ? 'bg-green-500/10 text-green-300 ring-1 ring-green-500/30' : 'bg-pink-500/10 text-pink-300 ring-1 ring-pink-500/30' }}">
                  {{ $r->backup_status ?? '-' }}
                </span>
              </td>
              <td class="px-4 py-2">{{ $r->backup_data ?? ($r->created_at ?? '-') }}</td>
              <td class="px-4 py-2">{{ $r->bd_id ?? '-' }}</td>
              <td class="px-4 py-2">{{ $r->mensagem ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-4 py-6 text-center text-slate-400">Nenhum backup encontrado.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
  </div>
@endsection

