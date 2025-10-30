<?php
session_start();

require_once __DIR__ . '/../php/login/verifica_sessao.php';
require_once __DIR__ . '/../php/conexao/conexao_pdo.php';

function safekup_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = conectar();
    }
    return $pdo;
}

function safekup_user(): string
{
    return $_SESSION['login'] ?? 'Usuário';
}

function safekup_escape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function safekup_menu_items(): array
{
    return [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => '/app/dashboard.php'],
        ['key' => 'restore', 'label' => 'Servidor de Restore', 'href' => '/app/restore.php'],
        ['key' => 'servidores', 'label' => 'Servidores Backup', 'href' => '/app/servidores.php'],
        ['key' => 'tipos', 'label' => 'Tipos de Bancos', 'href' => '/app/tipos.php'],
        ['key' => 'aplicacoes', 'label' => 'Aplicações', 'href' => '/app/aplicacoes.php'],
        ['key' => 'ssh', 'label' => 'SSH', 'href' => '/app/ssh.php'],
        ['key' => 'bancos', 'label' => 'Bancos', 'href' => '/app/bancos.php'],
        ['key' => 'usuarios', 'label' => 'Usuários', 'href' => '/app/usuarios.php'],
        ['key' => 'relatorios', 'label' => 'Relatórios', 'href' => '/app/relatorios.php'],
    ];
}

function safekup_render_header(string $title, string $activeKey = 'dashboard'): void
{
    $user = safekup_escape(safekup_user());
    ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= safekup_escape($title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/alertify.min.css" />
    <link rel="stylesheet" href="/assets/css/default.min.css" />
    <link rel="stylesheet" href="/assets/font-awesome/4.5.0/css/font-awesome.min.css" />
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-slate-100">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-white/10 bg-slate-900/70 backdrop-blur">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-4 px-6 py-4 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between md:gap-6">
                    <a href="/app/dashboard.php" class="flex items-center gap-3 hover:text-white">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-500/20 text-indigo-300">
                            <i class="fa fa-database text-xl" aria-hidden="true"></i>
                        </span>
                        <div>
                            <span class="block text-lg font-semibold leading-tight">Safekup</span>
                            <span class="block text-sm text-slate-400">Gerencie seus backups com segurança</span>
                        </div>
                    </a>

                    <nav class="flex flex-wrap items-center gap-3 text-sm text-slate-300">
                        <?php foreach (safekup_menu_items() as $item): ?>
                            <?php
                            $isActive = $item['key'] === $activeKey;
                            $classes = $isActive
                                ? 'bg-indigo-500/20 border-indigo-400/70 text-white'
                                : 'border-white/10 hover:border-indigo-400/60 hover:text-white';
                            ?>
                            <a href="<?= safekup_escape($item['href']); ?>"
                               class="rounded-full border px-3 py-1.5 transition <?= $classes; ?>"
                               <?= $isActive ? 'aria-current="page"' : ''; ?>>
                                <?= safekup_escape($item['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <div class="flex items-center gap-4 text-sm text-slate-300 md:justify-end">
                    <div class="text-right">
                        <p class="font-medium"><?= $user; ?></p>
                        <p class="text-xs text-slate-400">Conectado via LDAP</p>
                    </div>
                    <form method="post" action="/php/login/logout.php">
                        <button
                            class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:bg-slate-700">
                            <i class="fa fa-sign-out"></i>
                            <span>Sair</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-10 px-6 py-10">
    <?php
}

function safekup_render_footer(): void
{
    ?>
        </main>

        <footer class="border-t border-white/10 bg-slate-900/50 py-6 text-center text-xs text-slate-500">
            Safekup • Ambiente em transição para a nova experiência • <?= date('Y'); ?>
        </footer>
    </div>
</body>

</html>
    <?php
}

function safekup_format_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }
    return date('d/m/Y H:i', $timestamp);
}

function safekup_badge(string $label, string $variant = 'default'): string
{
    $variants = [
        'success' => 'bg-green-500/10 text-green-300 ring-1 ring-green-500/30',
        'danger'  => 'bg-pink-500/10 text-pink-300 ring-1 ring-pink-500/30',
        'info'    => 'bg-sky-500/10 text-sky-300 ring-1 ring-sky-500/30',
        'default' => 'bg-white/5 text-slate-200 ring-1 ring-white/10',
    ];
    $class = $variants[$variant] ?? $variants['default'];
    return sprintf(
        '<span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold %s">%s</span>',
        $class,
        safekup_escape($label)
    );
}

function safekup_perfil_label($perfilId): string
{
    $perfilId = (string) $perfilId;
    return match ($perfilId) {
        '1' => 'Administrador',
        '2' => 'USID',
        default => 'Perfil #' . $perfilId,
    };
}

function safekup_format_size($bytes): string
{
    if ($bytes === null || $bytes === '') {
        return '-';
    }
    $bytes = (float) $bytes;
    if ($bytes <= 0) {
        return '0 B';
    }
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
    $value = $bytes / (1024 ** $power);
    return number_format($value, $power === 0 ? 0 : 2, ',', '.') . ' ' . $units[$power];
}
