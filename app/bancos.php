<?php
require_once __DIR__ . '/bootstrap.php';

$db = safekup_db();

$encryptionError = null;
$encryptionInstance = null;
try {
    require_once __DIR__ . '/../php/include/encryption.inc.php';
    if (isset($encryption) && $encryption instanceof Encryption) {
        $encryptionInstance = $encryption;
    } else {
        $encryptionInstance = new Encryption();
    }
} catch (Throwable $exception) {
    $encryptionError = 'Não foi possível inicializar a criptografia das senhas: ' . $exception->getMessage();
}

$tipos = $db->query('SELECT tipo_id, tipo_nome FROM tipo ORDER BY tipo_nome')->fetchAll(PDO::FETCH_ASSOC);
$aplicacoes = $db->query('SELECT app_id, app_nome FROM aplicacao WHERE app_id != 1 ORDER BY app_nome')->fetchAll(PDO::FETCH_ASSOC);
$servidores = $db->query('SELECT servidor_id, servidor_nome FROM servidores ORDER BY servidor_nome')->fetchAll(PDO::FETCH_ASSOC);
$sshOptions = $db->query('SELECT ssh_id, ssh_ip FROM ssh ORDER BY ssh_ip')->fetchAll(PDO::FETCH_ASSOC);
$restoreOptions = $db->query('SELECT restore_id, restore_nome FROM restores ORDER BY restore_nome')->fetchAll(PDO::FETCH_ASSOC);

$diasSemana = [
    0 => 'Domingo',
    1 => 'Segunda',
    2 => 'Terça',
    3 => 'Quarta',
    4 => 'Quinta',
    5 => 'Sexta',
    6 => 'Sábado',
];

$formMode = 'create';
$formValues = [
    'bd_id' => null,
    'bd_nome_usuario' => '',
    'bd_login' => '',
    'bd_senha' => '',
    'bd_ip' => '',
    'bd_porta' => '',
    'bd_tipo' => '',
    'bd_app' => '',
    'bd_servidor_backup' => '',
    'bd_hora_backup' => '',
    'bd_backup_ativo' => 'SIM',
    'bd_recorrencia' => '1',
    'bd_container' => '',
    'bd_id_glpi' => '',
    'bd_ssh' => '0',
    'bd_id_restore' => '',
    'bd_dias' => [],
];
$formErrors = [];
$openModal = false;

$userLogin = safekup_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formMode = $_POST['modal_mode'] === 'edit' ? 'edit' : 'create';
    $formValues['bd_id'] = isset($_POST['bd_id']) ? (int)$_POST['bd_id'] : null;
    $formValues['bd_nome_usuario'] = trim($_POST['bd_nome_usuario'] ?? '');
    $formValues['bd_login'] = trim($_POST['bd_login'] ?? '');
    $formValues['bd_ip'] = trim($_POST['bd_ip'] ?? '');
    $formValues['bd_porta'] = trim($_POST['bd_porta'] ?? '');
    $formValues['bd_tipo'] = trim($_POST['bd_tipo'] ?? '');
    $formValues['bd_app'] = trim($_POST['bd_app'] ?? '');
    $formValues['bd_servidor_backup'] = trim($_POST['bd_servidor_backup'] ?? '');
    $formValues['bd_hora_backup'] = trim($_POST['bd_hora_backup'] ?? '');
    $formValues['bd_backup_ativo'] = trim($_POST['bd_backup_ativo'] ?? '');
    $formValues['bd_recorrencia'] = trim($_POST['bd_recorrencia'] ?? '1');
    $formValues['bd_container'] = trim($_POST['bd_container'] ?? '');
    $formValues['bd_id_glpi'] = trim($_POST['bd_id_glpi'] ?? '');
    $formValues['bd_ssh'] = trim($_POST['bd_ssh'] ?? '0');
    $formValues['bd_id_restore'] = trim($_POST['bd_id_restore'] ?? '');
    $formValues['bd_dias'] = array_map('intval', $_POST['bd_dias'] ?? []);
    $password = $_POST['bd_senha'] ?? '';
    $passwordConfirm = $_POST['bd_senha_confirm'] ?? '';

    if ($formValues['bd_nome_usuario'] === '') {
        $formErrors['bd_nome_usuario'] = 'Informe o nome identificador do banco.';
    }
    if ($formValues['bd_login'] === '') {
        $formErrors['bd_login'] = 'Informe o usuário do banco.';
    }
    if (!filter_var($formValues['bd_ip'], FILTER_VALIDATE_IP)) {
        $formErrors['bd_ip'] = 'Informe um endereço IP válido.';
    }
    if ($formValues['bd_tipo'] === '') {
        $formErrors['bd_tipo'] = 'Selecione o tipo de banco.';
    }
    if ($formValues['bd_servidor_backup'] === '') {
        $formErrors['bd_servidor_backup'] = 'Selecione o servidor de backup.';
    }
    if ($formValues['bd_hora_backup'] === '') {
        $formErrors['bd_hora_backup'] = 'Informe o horário do backup.';
    }
    if (!in_array($formValues['bd_backup_ativo'], ['SIM', 'NÃO'], true)) {
        $formErrors['bd_backup_ativo'] = 'Selecione se o backup está ativo.';
    }
    if (!in_array($formValues['bd_recorrencia'], ['1', '2', '3', '4'], true)) {
        $formErrors['bd_recorrencia'] = 'Selecione a recorrência diária.';
    }
    if (empty($formValues['bd_dias'])) {
        $formErrors['bd_dias'] = 'Selecione pelo menos um dia para executar o backup.';
    }

    if ($formMode === 'create') {
        if ($password === '') {
            $formErrors['bd_senha'] = 'Informe a senha de acesso ao banco.';
        }
        if ($password !== $passwordConfirm) {
            $formErrors['bd_senha_confirm'] = 'As senhas informadas não coincidem.';
        }
    } else {
        if ($password !== '' && $password !== $passwordConfirm) {
            $formErrors['bd_senha_confirm'] = 'As senhas informadas não coincidem.';
        }
    }

    if (!$encryptionInstance && ($password !== '' || $formMode === 'create')) {
        $formErrors['bd_senha'] = 'Criptografia indisponível. Verifique a chave ENCRYPTION_KEY.';
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $exists = $db->prepare('SELECT COUNT(*) FROM db_management WHERE bd_nome_usuario = :nome');
            $exists->execute([':nome' => $formValues['bd_nome_usuario']]);
            if ($exists->fetchColumn() > 0) {
                $formErrors['bd_nome_usuario'] = 'Já existe um banco com esse nome cadastrado.';
            }
        } else {
            if (!$formValues['bd_id']) {
                $formErrors['bd_id'] = 'Registro inválido.';
            }
        }
    }

    if (empty($formErrors)) {
        $diaFlags = array_fill(0, 7, 0);
        foreach ($formValues['bd_dias'] as $dia) {
            if (isset($diaFlags[$dia])) {
                $diaFlags[$dia] = 1;
            }
        }

        $senhaFinal = null;
        if ($password !== '') {
            $senhaFinal = $encryptionInstance ? $encryptionInstance->encrypt($password) : '';
        }

        if ($formMode === 'create') {
            $insert = $db->prepare('
                INSERT INTO db_management (
                    bd_tipo, bd_app, bd_nome_usuario, bd_login, bd_senha, bd_ip, bd_porta,
                    bd_dia_0, bd_dia_1, bd_dia_2, bd_dia_3, bd_dia_4, bd_dia_5, bd_dia_6,
                    bd_hora_backup, bd_servidor_backup, bd_data_cadastro, bd_data_alteracao,
                    bd_usuario_adm, bd_backup_ativo, bd_ssh, bd_recorrencia, bd_container,
                    bd_id_restore, bd_id_glpi
                ) VALUES (
                    :tipo, :app, :nome, :login, :senha, :ip, :porta,
                    :dia0, :dia1, :dia2, :dia3, :dia4, :dia5, :dia6,
                    :hora, :servidor, :cadastro, :alteracao,
                    :usuario_adm, :ativo, :ssh, :recorrencia, :container,
                    :restore, :glpi
                )
            ');
            $insert->execute([
                ':tipo' => $formValues['bd_tipo'] !== '' ? (int)$formValues['bd_tipo'] : null,
                ':app' => $formValues['bd_app'] !== '' ? (int)$formValues['bd_app'] : null,
                ':nome' => $formValues['bd_nome_usuario'],
                ':login' => $formValues['bd_login'],
                ':senha' => $senhaFinal,
                ':ip' => $formValues['bd_ip'],
                ':porta' => $formValues['bd_porta'] !== '' ? $formValues['bd_porta'] : null,
                ':dia0' => $diaFlags[0],
                ':dia1' => $diaFlags[1],
                ':dia2' => $diaFlags[2],
                ':dia3' => $diaFlags[3],
                ':dia4' => $diaFlags[4],
                ':dia5' => $diaFlags[5],
                ':dia6' => $diaFlags[6],
                ':hora' => $formValues['bd_hora_backup'],
                ':servidor' => (int)$formValues['bd_servidor_backup'],
                ':cadastro' => date('Y-m-d H:i:s'),
                ':alteracao' => date('Y-m-d H:i:s'),
                ':usuario_adm' => $userLogin,
                ':ativo' => $formValues['bd_backup_ativo'],
                ':ssh' => $formValues['bd_ssh'] !== '' && $formValues['bd_ssh'] !== '0' ? (int)$formValues['bd_ssh'] : null,
                ':recorrencia' => (int)$formValues['bd_recorrencia'],
                ':container' => $formValues['bd_container'] !== '' ? $formValues['bd_container'] : null,
                ':restore' => $formValues['bd_id_restore'] !== '' ? (int)$formValues['bd_id_restore'] : null,
                ':glpi' => $formValues['bd_id_glpi'] !== '' ? $formValues['bd_id_glpi'] : null,
            ]);
            header('Location: /app/bancos.php?status=created');
            exit;
        }

        if ($formMode === 'edit' && $formValues['bd_id']) {
            if ($senhaFinal === null) {
                $current = $db->prepare('SELECT bd_senha FROM db_management WHERE bd_id = :id');
                $current->execute([':id' => $formValues['bd_id']]);
                $senhaFinal = $current->fetchColumn();
            }

            $update = $db->prepare('
                UPDATE db_management SET
                    bd_tipo = :tipo,
                    bd_app = :app,
                    bd_nome_usuario = :nome,
                    bd_login = :login,
                    bd_senha = :senha,
                    bd_ip = :ip,
                    bd_porta = :porta,
                    bd_dia_0 = :dia0,
                    bd_dia_1 = :dia1,
                    bd_dia_2 = :dia2,
                    bd_dia_3 = :dia3,
                    bd_dia_4 = :dia4,
                    bd_dia_5 = :dia5,
                    bd_dia_6 = :dia6,
                    bd_hora_backup = :hora,
                    bd_servidor_backup = :servidor,
                    bd_data_alteracao = :alteracao,
                    bd_usuario_adm = :usuario_adm,
                    bd_backup_ativo = :ativo,
                    bd_ssh = :ssh,
                    bd_recorrencia = :recorrencia,
                    bd_container = :container,
                    bd_id_restore = :restore,
                    bd_id_glpi = :glpi
                WHERE bd_id = :id
            ');
            $update->execute([
                ':tipo' => $formValues['bd_tipo'] !== '' ? (int)$formValues['bd_tipo'] : null,
                ':app' => $formValues['bd_app'] !== '' ? (int)$formValues['bd_app'] : null,
                ':nome' => $formValues['bd_nome_usuario'],
                ':login' => $formValues['bd_login'],
                ':senha' => $senhaFinal,
                ':ip' => $formValues['bd_ip'],
                ':porta' => $formValues['bd_porta'] !== '' ? $formValues['bd_porta'] : null,
                ':dia0' => $diaFlags[0],
                ':dia1' => $diaFlags[1],
                ':dia2' => $diaFlags[2],
                ':dia3' => $diaFlags[3],
                ':dia4' => $diaFlags[4],
                ':dia5' => $diaFlags[5],
                ':dia6' => $diaFlags[6],
                ':hora' => $formValues['bd_hora_backup'],
                ':servidor' => (int)$formValues['bd_servidor_backup'],
                ':alteracao' => date('Y-m-d H:i:s'),
                ':usuario_adm' => $userLogin,
                ':ativo' => $formValues['bd_backup_ativo'],
                ':ssh' => $formValues['bd_ssh'] !== '' && $formValues['bd_ssh'] !== '0' ? (int)$formValues['bd_ssh'] : null,
                ':recorrencia' => (int)$formValues['bd_recorrencia'],
                ':container' => $formValues['bd_container'] !== '' ? $formValues['bd_container'] : null,
                ':restore' => $formValues['bd_id_restore'] !== '' ? (int)$formValues['bd_id_restore'] : null,
                ':glpi' => $formValues['bd_id_glpi'] !== '' ? $formValues['bd_id_glpi'] : null,
                ':id' => $formValues['bd_id'],
            ]);
            header('Location: /app/bancos.php?status=updated');
            exit;
        }
    } else {
        $openModal = true;
    }
}

$sql = '
    SELECT 
        A.*, 
        B.tipo_nome, 
        C.app_nome,
        S.servidor_nome,
        R.restore_nome
    FROM db_management A
    LEFT JOIN tipo B ON A.bd_tipo = B.tipo_id
    LEFT JOIN aplicacao C ON A.bd_app = C.app_id
    LEFT JOIN servidores S ON A.bd_servidor_backup = S.servidor_id
    LEFT JOIN restores R ON A.bd_id_restore = R.restore_id
    ORDER BY A.bd_nome_usuario
';
$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status = $_GET['status'] ?? '';
$alerts = [
    'created' => ['type' => 'success', 'message' => 'Banco cadastrado com sucesso.'],
    'updated' => ['type' => 'success', 'message' => 'Banco atualizado com sucesso.'],
    'notfound' => ['type' => 'danger', 'message' => 'Banco não encontrado.'],
];
$currentAlert = $alerts[$status] ?? null;

safekup_render_header('Safekup — Bancos de Dados', 'bancos');
?>
    <section class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Bancos de Dados</h2>
                <p class="text-sm text-slate-300">
                    Inventário das bases protegidas pelo Safekup com informações de agendamento e plataforma.
                </p>
            </div>
            <button type="button" data-modal-trigger="create"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-500/90 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-400">
                <i class="fa fa-plus"></i>
                <span>Novo banco</span>
            </button>
        </div>

        <?php if ($encryptionError): ?>
            <div class="rounded-xl border border-pink-500/40 bg-pink-500/10 px-4 py-3 text-sm text-pink-200">
                <div class="flex items-start gap-2">
                    <i class="fa fa-exclamation-triangle mt-0.5"></i>
                    <span><?= safekup_escape($encryptionError); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($currentAlert): ?>
            <div class="rounded-xl border <?= $currentAlert['type'] === 'success' ? 'border-green-500/40 bg-green-500/10 text-green-200' : 'border-pink-500/40 bg-pink-500/10 text-pink-200'; ?> px-4 py-3 text-sm">
                <div class="flex items-start gap-2">
                    <i class="fa <?= $currentAlert['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mt-0.5"></i>
                    <span><?= safekup_escape($currentAlert['message']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-900/60">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Banco</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">SGBD</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Aplicação</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Servidor Backup</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Horário</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Cadastrado em</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-400">Nenhum banco cadastrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $diasAtivos = [];
                                for ($i = 0; $i <= 6; $i++) {
                                    if ((int)($row['bd_dia_' . $i] ?? 0) === 1) {
                                        $diasAtivos[] = $i;
                                    }
                                }
                                $payload = [
                                    'bd_id' => (int)$row['bd_id'],
                                    'bd_nome_usuario' => $row['bd_nome_usuario'],
                                    'bd_login' => $row['bd_login'],
                                    'bd_ip' => $row['bd_ip'],
                                    'bd_porta' => $row['bd_porta'],
                                    'bd_tipo' => $row['bd_tipo'],
                                    'bd_app' => $row['bd_app'],
                                    'bd_servidor_backup' => $row['bd_servidor_backup'],
                                    'bd_hora_backup' => $row['bd_hora_backup'],
                                    'bd_backup_ativo' => $row['bd_backup_ativo'],
                                    'bd_recorrencia' => $row['bd_recorrencia'],
                                    'bd_container' => $row['bd_container'],
                                    'bd_id_glpi' => $row['bd_id_glpi'],
                                    'bd_ssh' => $row['bd_ssh'],
                                    'bd_id_restore' => $row['bd_id_restore'],
                                    'bd_dias' => $diasAtivos,
                                ];
                            ?>
                            <tr class="hover:bg-slate-800/50">
                                <td class="px-4 py-3 font-medium">
                                    <div class="flex flex-col">
                                        <span><?= safekup_escape($row['bd_nome_usuario']); ?></span>
                                        <span class="text-xs text-slate-400 font-mono"><?= safekup_escape($row['bd_ip']); ?><?= $row['bd_porta'] ? ':' . safekup_escape($row['bd_porta']) : ''; ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><?= safekup_render_database_label($row['tipo_nome'] ?? null); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['app_nome'] ?? '-'); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['servidor_nome'] ?? '-'); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['bd_hora_backup']); ?>:00</td>
                                <td class="px-4 py-3">
                                    <?= safekup_badge(
                                        strtoupper((string)$row['bd_backup_ativo']) === 'SIM' ? 'Ativo' : 'Inativo',
                                        strtoupper((string)$row['bd_backup_ativo']) === 'SIM' ? 'success' : 'danger'
                                    ); ?>
                                </td>
                                <td class="px-4 py-3"><?= safekup_escape(safekup_format_datetime($row['bd_data_cadastro'])); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button"
                                           data-modal-trigger="edit"
                                           data-banco='<?= safekup_escape(json_encode($payload, JSON_UNESCAPED_UNICODE)); ?>'
                                           class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-100 transition hover:bg-slate-700">
                                            <i class="fa fa-pencil"></i>
                                            <span>Editar</span>
                                        </button>
                                        <button type="button"
                                            data-backup-trigger
                                            data-backup-id="<?= (int)$row['bd_id']; ?>"
                                            data-backup-name="<?= safekup_escape($row['bd_nome_usuario']); ?>"
                                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-500/20 px-3 py-1.5 text-xs font-semibold text-indigo-200 transition hover:bg-indigo-500/30">
                                            <i class="fa fa-play-circle"></i>
                                            <span>Executar dump</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div id="bancoModal" class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/70 px-4 py-8">
        <div class="w-full max-w-5xl overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl shadow-indigo-900/30">
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                <div>
                    <h2 id="bancoModalTitle" class="text-lg font-semibold text-white">Novo banco</h2>
                    <p class="text-xs text-slate-400">Configure as credenciais e agenda de execução do backup.</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" data-modal-close>
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <form method="post" class="grid gap-4 px-6 py-5">
                <input type="hidden" name="modal_mode" id="banco_modal_mode" value="<?= safekup_escape($formMode); ?>">
                <input type="hidden" name="bd_id" id="bd_id" value="<?= safekup_escape((string)($formValues['bd_id'] ?? '')); ?>">

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Nome do banco</span>
                        <input name="bd_nome_usuario" id="bd_nome_usuario" value="<?= safekup_escape($formValues['bd_nome_usuario']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['bd_nome_usuario'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['bd_nome_usuario']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Usuário do banco</span>
                        <input name="bd_login" id="bd_login" value="<?= safekup_escape($formValues['bd_login']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['bd_login'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['bd_login']); ?></span>
                        <?php endif; ?>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Endereço IP</span>
                        <input name="bd_ip" id="bd_ip" value="<?= safekup_escape($formValues['bd_ip']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['bd_ip'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['bd_ip']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Porta</span>
                        <input name="bd_porta" id="bd_porta" value="<?= safekup_escape($formValues['bd_porta']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Container (Docker)</span>
                        <input name="bd_container" id="bd_container" value="<?= safekup_escape($formValues['bd_container']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Tipo</span>
                        <select name="bd_tipo" id="bd_tipo"
                                class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Selecione</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <option value="<?= safekup_escape((string)$tipo['tipo_id']); ?>" <?= (string)$formValues['bd_tipo'] === (string)$tipo['tipo_id'] ? 'selected' : ''; ?>>
                                    <?= safekup_escape($tipo['tipo_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($formErrors['bd_tipo'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['bd_tipo']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Aplicação</span>
                        <select name="bd_app" id="bd_app"
                                class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Selecione</option>
                            <?php foreach ($aplicacoes as $app): ?>
                                <option value="<?= safekup_escape((string)$app['app_id']); ?>" <?= (string)$formValues['bd_app'] === (string)$app['app_id'] ? 'selected' : ''; ?>>
                                    <?= safekup_escape($app['app_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Servidor de backup</span>
                        <select name="bd_servidor_backup" id="bd_servidor_backup"
                                class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Selecione</option>
                            <?php foreach ($servidores as $servidor): ?>
                                <option value="<?= safekup_escape((string)$servidor['servidor_id']); ?>" <?= (string)$formValues['bd_servidor_backup'] === (string)$servidor['servidor_id'] ? 'selected' : ''; ?>>
                                    <?= safekup_escape($servidor['servidor_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($formErrors['bd_servidor_backup'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['bd_servidor_backup']); ?></span>
                        <?php endif; ?>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Horário do backup</span>
                        <select name="bd_hora_backup" id="bd_hora_backup"
                                class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Selecione</option>
                            <?php for ($h = 0; $h <= 23; $h++): $value = str_pad((string)$h, 2, '0', STR_PAD_LEFT); ?>
                                <option value="<?= $value; ?>" <?= $formValues['bd_hora_backup'] === $value ? 'selected' : ''; ?>><?= $value; ?>:00</option>
                            <?php endfor; ?>
                        </select>
                        <?php if (isset($formErrors['bd_hora_backup'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['bd_hora_backup']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Recorrência diária</span>
                        <select name="bd_recorrencia" id="bd_recorrencia"
                                class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="1" <?= $formValues['bd_recorrencia'] === '1' ? 'selected' : ''; ?>>1x</option>
                            <option value="2" <?= $formValues['bd_recorrencia'] === '2' ? 'selected' : ''; ?>>2x</option>
                            <option value="3" <?= $formValues['bd_recorrencia'] === '3' ? 'selected' : ''; ?>>3x</option>
                            <option value="4" <?= $formValues['bd_recorrencia'] === '4' ? 'selected' : ''; ?>>4x</option>
                        </select>
                        <?php if (isset($formErrors['bd_recorrencia'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['bd_recorrencia']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Status</span>
                        <select name="bd_backup_ativo" id="bd_backup_ativo"
                                class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="SIM" <?= $formValues['bd_backup_ativo'] === 'SIM' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="NÃO" <?= $formValues['bd_backup_ativo'] === 'NÃO' ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                        <?php if (isset($formErrors['bd_backup_ativo'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['bd_backup_ativo']); ?></span>
                        <?php endif; ?>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Conexão SSH</span>
                        <select name="bd_ssh" id="bd_ssh"
                                class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="0">Não utilizar</option>
                            <?php foreach ($sshOptions as $ssh): ?>
                                <option value="<?= safekup_escape((string)$ssh['ssh_id']); ?>" <?= (string)$formValues['bd_ssh'] === (string)$ssh['ssh_id'] ? 'selected' : ''; ?>>
                                    <?= safekup_escape($ssh['ssh_ip']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Servidor de restore</span>
                        <select name="bd_id_restore" id="bd_id_restore"
                                class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Selecione</option>
                            <?php foreach ($restoreOptions as $restore): ?>
                                <option value="<?= safekup_escape((string)$restore['restore_id']); ?>" <?= (string)$formValues['bd_id_restore'] === (string)$restore['restore_id'] ? 'selected' : ''; ?>>
                                    <?= safekup_escape($restore['restore_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Ticket GLPI (opcional)</span>
                        <input name="bd_id_glpi" id="bd_id_glpi" value="<?= safekup_escape($formValues['bd_id_glpi']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2 text-sm">
                        <div class="flex items-center justify-between text-slate-200">
                            <span>Senha do banco</span>
                            <span class="text-xs text-slate-400" id="senhaInfoBanco">Obrigatório</span>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input name="bd_senha" id="bd_senha" type="password"
                                   class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                            <input name="bd_senha_confirm" id="bd_senha_confirm" type="password"
                                   class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Confirmar" />
                        </div>
                        <?php if (isset($formErrors['bd_senha'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['bd_senha']); ?></span>
                        <?php endif; ?>
                        <?php if (isset($formErrors['bd_senha_confirm'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['bd_senha_confirm']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-col gap-2 text-sm">
                        <div class="flex items-center justify-between text-slate-200">
                            <span>Dias do backup</span>
                            <button type="button" id="toggleAllDays" class="text-xs text-indigo-300 hover:text-indigo-200">Alternar todos</button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-slate-200">
                            <?php foreach ($diasSemana as $diaValor => $diaLabel): ?>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="bd_dias[]" value="<?= $diaValor; ?>" class="rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500">
                                    <span><?= safekup_escape($diaLabel); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (isset($formErrors['bd_dias'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['bd_dias']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-white/10 pt-4">
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover;border-indigo-400/60 hover:text-white" data-modal-close>
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        <i class="fa fa-save"></i>
                        <span id="bancoModalSubmit">Salvar banco</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const modal = document.getElementById('bancoModal');
            const modeField = document.getElementById('banco_modal_mode');
            const idField = document.getElementById('bd_id');
            const nomeField = document.getElementById('bd_nome_usuario');
            const loginField = document.getElementById('bd_login');
            const ipField = document.getElementById('bd_ip');
            const portaField = document.getElementById('bd_porta');
            const tipoField = document.getElementById('bd_tipo');
            const appField = document.getElementById('bd_app');
            const servidorField = document.getElementById('bd_servidor_backup');
            const horaField = document.getElementById('bd_hora_backup');
            const statusField = document.getElementById('bd_backup_ativo');
            const recorrenciaField = document.getElementById('bd_recorrencia');
            const containerField = document.getElementById('bd_container');
            const glpiField = document.getElementById('bd_id_glpi');
            const sshField = document.getElementById('bd_ssh');
            const restoreField = document.getElementById('bd_id_restore');
            const passField = document.getElementById('bd_senha');
            const passConfirmField = document.getElementById('bd_senha_confirm');
            const senhaInfo = document.getElementById('senhaInfoBanco');
            const toggleAllDaysBtn = document.getElementById('toggleAllDays');
            const title = document.getElementById('bancoModalTitle');
            const submitLabel = document.getElementById('bancoModalSubmit');
            const dayCheckboxes = Array.from(document.querySelectorAll('input[name="bd_dias[]"]'));

            function toggleModal(show) {
                if (show) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                } else {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');
                }
            }

            function fillDays(dias) {
                const set = new Set((dias || []).map(Number));
                dayCheckboxes.forEach(checkbox => {
                    checkbox.checked = set.has(Number(checkbox.value));
                });
            }

            function openModal(mode, data) {
                modeField.value = mode;
                if (mode === 'edit') {
                    title.textContent = 'Editar banco de dados';
                    submitLabel.textContent = 'Salvar alterações';
                    senhaInfo.textContent = 'Opcional — deixe em branco para manter';
                } else {
                    title.textContent = 'Cadastrar banco de dados';
                    submitLabel.textContent = 'Salvar banco';
                    senhaInfo.textContent = 'Obrigatório';
                }

                idField.value = data?.bd_id ?? '';
                nomeField.value = data?.bd_nome_usuario ?? '';
                loginField.value = data?.bd_login ?? '';
                ipField.value = data?.bd_ip ?? '';
                portaField.value = data?.bd_porta ?? '';
                tipoField.value = data?.bd_tipo ?? '';
                appField.value = data?.bd_app ?? '';
                servidorField.value = data?.bd_servidor_backup ?? '';
                horaField.value = data?.bd_hora_backup ?? '';
                statusField.value = data?.bd_backup_ativo ?? 'SIM';
                recorrenciaField.value = data?.bd_recorrencia ?? '1';
                containerField.value = data?.bd_container ?? '';
                glpiField.value = data?.bd_id_glpi ?? '';
                sshField.value = data?.bd_ssh ?? '0';
                restoreField.value = data?.bd_id_restore ?? '';
                passField.value = '';
                passConfirmField.value = '';
                fillDays(data?.bd_dias ?? []);

                toggleModal(true);
                setTimeout(() => nomeField.focus(), 50);
            }

            document.querySelectorAll('[data-modal-trigger="create"]').forEach(btn => btn.addEventListener('click', () => openModal('create')));
            document.querySelectorAll('[data-modal-trigger="edit"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const dataset = btn.getAttribute('data-banco');
                        const data = dataset ? JSON.parse(dataset) : {};
                        openModal('edit', data);
                    } catch (error) {
                        console.error(error);
                        openModal('edit');
                    }
                });
            });

            function showNotification(type, message) {
                const baseClasses = 'fixed right-4 top-4 z-50 flex items-center gap-3 rounded-lg px-4 py-3 text-sm shadow-xl';
                const variants = {
                    success: 'bg-emerald-500/20 text-emerald-100 border border-emerald-500/40 backdrop-blur',
                    warning: 'bg-amber-500/20 text-amber-100 border border-amber-500/40 backdrop-blur',
                    error: 'bg-rose-500/20 text-rose-100 border border-rose-500/40 backdrop-blur',
                    info: 'bg-indigo-500/20 text-indigo-100 border border-indigo-500/40 backdrop-blur'
                };
                const notification = document.createElement('div');
                notification.className = `${baseClasses} ${variants[type] ?? variants.info}`;
                notification.innerHTML = `
                    <i class="fa ${type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-exclamation-triangle' : type === 'error' ? 'fa-times-circle' : 'fa-info-circle'}"></i>
                    <span>${message}</span>
                `;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('opacity-0', 'transition', 'duration-300');
                    setTimeout(() => notification.remove(), 320);
                }, 5000);
            }

            async function triggerManualBackup(button) {
                const bdId = button.getAttribute('data-backup-id');
                const bdName = button.getAttribute('data-backup-name') || 'banco';
                if (!bdId) {
                    showNotification('error', 'Não foi possível identificar o banco selecionado.');
                    return;
                }

                if (!confirm(`Executar o dump agora para "${bdName}"?`)) {
                    return;
                }

                const originalHtml = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fa fa-spinner fa-spin"></i><span>Executando...</span>';

                try {
                    const response = await fetch('/scripts/backups/manual_dump.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        credentials: 'same-origin',
                        body: new URLSearchParams({ bd_id: bdId })
                    });

                    const text = (await response.text()).trim().toLowerCase();

                    if (!response.ok) {
                        throw new Error(text || 'Resposta inválida do servidor.');
                    }

                    if (text === 'inativo') {
                        showNotification('warning', 'O backup deste banco está inativo. Verifique o cadastro.');
                    } else if (text === 'erro_montagem') {
                        showNotification('error', 'Erro ao montar o compartilhamento para executar o dump.');
                    } else if (text === 'erro_ssh') {
                        showNotification('error', 'Não foi possível estabelecer conexão SSH para este banco.');
                    } else if (text === 'erro') {
                        showNotification('error', 'Ocorreu um erro durante a execução do dump.');
                    } else {
                        showNotification('success', `Dump do banco "${bdName}" executado com sucesso. Aguarde o e-mail com os detalhes.`);
                    }
                } catch (error) {
                    console.error('Falha ao executar dump imediato', error);
                    showNotification('error', 'Falha ao executar o dump agora. Tente novamente mais tarde.');
                } finally {
                    button.disabled = false;
                    button.innerHTML = originalHtml;
                }
            }

            document.querySelectorAll('[data-backup-trigger]').forEach(button => {
                button.addEventListener('click', () => triggerManualBackup(button));
            });

            toggleAllDaysBtn.addEventListener('click', () => {
                const allChecked = dayCheckboxes.every(cb => cb.checked);
                dayCheckboxes.forEach(cb => { cb.checked = !allChecked; });
            });

            modal.querySelectorAll('[data-modal-close]').forEach(btn => btn.addEventListener('click', () => toggleModal(false)));
            modal.addEventListener('click', (event) => { if (event.target === modal) toggleModal(false); });
            window.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal.classList.contains('flex')) toggleModal(false); });

            <?php if ($openModal): ?>
            document.addEventListener('DOMContentLoaded', () => {
                openModal('<?= $formMode ?>', <?= json_encode(array_merge(array_intersect_key($formValues, array_flip(['bd_id','bd_nome_usuario','bd_login','bd_ip','bd_porta','bd_tipo','bd_app','bd_servidor_backup','bd_hora_backup','bd_backup_ativo','bd_recorrencia','bd_container','bd_id_glpi','bd_ssh','bd_id_restore'])), ['bd_dias' => $formValues['bd_dias']]), JSON_UNESCAPED_UNICODE); ?>);
            });
            <?php endif; ?>
        })();
    </script>
<?php
safekup_render_footer();
