<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name', 'Safekup') }}</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-900 text-slate-100">
  <div class="max-w-7xl mx-auto px-4 py-8">
    {{ $slot ?? '' }}
    @yield('content')
  </div>
</body>
</html>

