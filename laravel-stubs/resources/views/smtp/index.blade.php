@extends('layouts.app')

@section('content')
  <div class="mt-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold">Serviço de E-mail (SMTP)</h1>
      <a href="{{ route('dashboard') }}" class="text-sm text-indigo-400 hover:text-indigo-300">← Voltar ao Dashboard</a>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl ring-1 ring-white/10">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-white/5 text-slate-300">
          <tr>
            <th class="px-4 py-2">Nome</th>
            <th class="px-4 py-2">Endereço</th>
            <th class="px-4 py-2">Porta</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($rows as $r)
            <tr class="border-t border-white/10">
              <td class="px-4 py-2">{{ $r->smtp_nome ?? '-' }}</td>
              <td class="px-4 py-2">{{ $r->smtp_endereco ?? '-' }}</td>
              <td class="px-4 py-2">{{ $r->smtp_porta ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="px-4 py-6 text-center text-slate-400">Nenhuma configuração SMTP encontrada.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection

