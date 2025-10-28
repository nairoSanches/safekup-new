# Migração para Laravel (sem alterar o banco)

Este guia prepara um app Laravel usando o banco atual do Safekup e mantém o login por LDAP. Inclui exemplos de rotas, controller e model mapeando a tabela existente `usuarios`.

## 1) Criar o projeto Laravel

Execute na raiz do repositório:

```
composer create-project laravel/laravel safekup-laravel
```

OU (Docker) montar um container PHP/Composer e criar o projeto dentro dele.

## 2) Configurar `.env` (apontando para o banco atual)

No diretório `safekup-laravel/` copie `.env.example` para `.env` e ajuste:

```
APP_NAME=Safekup
APP_URL=http://localhost:8081

DB_CONNECTION=mysql
DB_HOST=SEU_HOST
DB_PORT=3306
DB_DATABASE=SEU_DATABASE
DB_USERNAME=SEU_USUARIO
DB_PASSWORD=SEU_SEGREDO

# LDAP atual
LDAP_HOST=ldap://seu.ldap
LDAP_PORT=389
LDAP_DN=DC=exemplo,DC=gov,DC=br
```

Gere a APP_KEY:

```
php artisan key:generate
```

Se estiver no Windows e sem Composer, você pode criar o projeto depois e copiar os stubs com:

```
./scripts/copy-laravel-stubs.ps1 -Target safekup-laravel
```

## 3) Model mapeando tabela `usuarios`

Crie `app/Models/Usuario.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'usuario_id';
    public $timestamps = false; // ajuste se tiver colunas created_at/updated_at

    protected $fillable = [
        'usuario_nome',
        'usuario_login',
        'usuario_email',
        // 'usuario_senha'  // se usar hash local
    ];
}
```

## 4) Controller de Login com LDAP

Crie `app/Http/Controllers/Auth/LoginController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'senha' => 'required|string',
        ]);

        $ldapHost = env('LDAP_HOST');
        $ldapPort = (int) env('LDAP_PORT', 389);
        $baseDn   = env('LDAP_DN'); // opcional no bind simples

        $user = $request->input('login');
        $pass = $request->input('senha');

        $conn = @ldap_connect($ldapHost, $ldapPort);
        if (!$conn) {
            return back()->withErrors(['login' => 'Falha na conexão com o LDAP.']);
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        if (!@ldap_bind($conn, $user, $pass)) {
            // opcional: fallback de cache/sessão
            return back()->withErrors(['login' => 'Usuário ou senha inválidos.']);
        }

        Session::put('login', $user);
        @ldap_unbind($conn);
        return redirect()->route('dashboard');
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('login');
    }
}
```

## 5) Rotas

Edite `routes/web.php`:

```php
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('web')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
```

## 6) Views (Tailwind)

Crie `resources/views/auth/login.blade.php` com um formulário simples (usa Tailwind CDN no layout base) e ids `login`/`senha`.

Exemplo:

```blade
<x-layout>
  <div class="max-w-md mx-auto mt-24 bg-white/5 p-6 rounded-xl">
    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm">Usuário</label>
        <input name="login" class="w-full mt-1 rounded bg-slate-800 border border-slate-700 p-2" />
      </div>
      <div>
        <label class="block text-sm">Senha</label>
        <input type="password" name="senha" class="w-full mt-1 rounded bg-slate-800 border border-slate-700 p-2" />
      </div>
      <button class="w-full bg-indigo-600 hover:bg-indigo-500 rounded p-2 text-white">Entrar</button>
    </form>
  </div>
</x-layout>
```

Crie `resources/views/dashboard.blade.php` com um placeholder.

## 7) Protegendo as rotas

Um middleware simples `SessionAuth` foi incluído nos stubs:

- Arquivo: `app/Http/Middleware/SessionAuth.php`
- Ele verifica `Session::has('login')` e redireciona para `/login` caso não haja sessão.

Registre o alias no `app/Http/Kernel.php` do seu projeto Laravel:

```php
protected $routeMiddleware = [
    // ...
    'session.auth' => \App\Http\Middleware\SessionAuth::class,
];
```

As rotas do dashboard já usam `->middleware('session.auth')` nos stubs.

## 8) Gradualidade

- Migre telas do PHP atual para Blade aos poucos, reaproveitando queries via Eloquent/DB Facade sem alterar tabelas.
- Mantenha o PHP legado rodando em paralelo até finalizar a migração.

## 9) Observações de segurança

- Não exibir `display_errors` em produção.
- Se guardar senha local, use `password_hash`/`password_verify` e prepared statements.
- Tokens/segredos apenas no `.env`.

## 10) Docker Compose (opcional)

Você pode subir um serviço Apache/PHP apontando para a pasta do Laravel:

```
docker compose up -d safekup-laravel
```

O serviço `safekup-laravel` foi adicionado em `docker-compose.yml`, agora com build a partir de `docker/laravel.Dockerfile`, que:

- Habilita `mod_rewrite`
- Ajusta `DocumentRoot` para `/public`
- Instala `pdo_mysql`

Suba apenas o serviço Laravel:

```
docker compose up -d safekup-laravel
```
