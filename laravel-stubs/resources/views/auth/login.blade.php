@extends('layouts.app')

@section('content')
  <div class="max-w-md mx-auto mt-24 bg-white/5 p-6 rounded-xl ring-1 ring-white/10">
    <h1 class="text-xl font-semibold mb-4">Safekup — Login</h1>
    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm">Usuário</label>
        <input name="login" class="w-full mt-1 rounded bg-slate-800 border border-slate-700 p-2" autocomplete="username">
        @error('login')
          <p class="text-pink-400 text-sm mt-1">{{ $message }}</p>
        @enderror
      </div>
      <div>
        <label class="block text-sm">Senha</label>
        <input type="password" name="senha" class="w-full mt-1 rounded bg-slate-800 border border-slate-700 p-2" autocomplete="current-password">
        @error('senha')
          <p class="text-pink-400 text-sm mt-1">{{ $message }}</p>
        @enderror
      </div>
      <button class="w-full bg-indigo-600 hover:bg-indigo-500 rounded p-2 text-white">Entrar</button>
    </form>
  </div>
@endsection

