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
    $encryptionError = 'Não foi possível inicializar a criptografia de senhas: ' . $exception->getMessage();
}

$formMode = 'create';
$formValues = [
    'ssh_id' => null,
    'ssh_ip' => '',
    'ssh_user' => '',
    'ssh_status' => 'ATIVO',
];
$formErrors = [];
$openModal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formMode = $_POST['modal_mode'] === 'edit' ? 'edit' : 'create';
    $formValues['ssh_id'] = isset($_POST['ssh_id']) ? (int)$_POST['ssh_id'] : null;
    $formValues['ssh_ip'] = trim($_POST['ssh_ip'] ?? '');
    $formValues['ssh_user'] = trim($_POST['ssh_user'] ?? '');
    $formValues['ssh_status'] = trim($_POST['ssh_status'] ?? 'ATIVO');
    $password = $_POST['ssh_pass'] ?? '';
    $passwordConfirm = $_POST['ssh_pass_confirm'] ?? '';

    if ($formValues['ssh_ip'] === '') {
        $formErrors['ssh_ip'] = 'Informe o host ou IP.';
    }
    if ($formMode === 'create' && $formValues['ssh_user'] === '') {
        $formErrors['ssh_user'] = 'Informe o usuário.';
    }
    if (!in_array($formValues['ssh_status'], ['ATIVO', 'BLOQUEADO'], true)) {
        $formErrors['ssh_status'] = 'Status inválido.';
    }

    if ($formMode === 'create') {
        if ($password === '') {
            $formErrors['ssh_pass'] = 'Informe a senha.';
        }
        if ($password !== $passwordConfirm) {
            $formErrors['ssh_pass_confirm'] = 'As senhas não coincidem.';
        }
    } else {
        if ($password !== '' && $password !== $passwordConfirm) {
            $formErrors['ssh_pass_confirm'] = 'As senhas não coincidem.';
        }
    }

    if (!$encryptionInstance && ($password !== '' || $formMode === 'create')) {
        $formErrors['ssh_pass'] = 'Criptografia indisponível. Verifique a chave ENCRYPTION_KEY.';
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $exists = $db->prepare('SELECT COUNT(*) FROM ssh WHERE ssh_user = :usuario');
            $exists->execute([':usuario' => $formValues['ssh_user']]);
            if ($exists->fetchColumn() > 0) {
                $formErrors['ssh_user'] = 'Já existe uma conexão com esse usuário.';
            }
        } else {
            if (!$formValues['ssh_id']) {
                $formErrors['ssh_id'] = 'Registro inválido.';
            }
        }
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $insert = $db->prepare('INSERT INTO ssh (ssh_ip, ssh_user, ssh_pass, ssh_status) VALUES (:ip, :usuario, :senha, :status)');
            $insert->execute([
                ':ip' => $formValues['ssh_ip'],
                ':usuario' => $formValues['ssh_user'],
                ':senha' => $encryptionInstance ? $encryptionInstance->encrypt($password) : '',
                ':status' => $formValues['ssh_status'],
            ]);
            header('Location: /app/ssh.php?status=created');
            exit;
        }

        if ($formMode === 'edit' && $formValues['ssh_id']) {
            if ($password !== '' && $encryptionInstance) {
                $update = $db->prepare('UPDATE ssh SET ssh_ip = :ip, ssh_status = :status, ssh_pass = :senha WHERE ssh_id = :id');
                $update->execute([
                    ':ip' => $formValues['ssh_ip'],
                    ':status' => $formValues['ssh_status'],
                    ':senha' => $encryptionInstance->encrypt($password),
                    ':id' => $formValues['ssh_id'],
                ]);
            } else {
                $update = $db->prepare('UPDATE ssh SET ssh_ip = :ip, ssh_status = :status WHERE ssh_id = :id');
                $update->execute([
                    ':ip' => $formValues['ssh_ip'],
                    ':status' => $formValues['ssh_status'],
                    ':id' => $formValues['ssh_id'],
                ]);
            }
            header('Location: /app/ssh.php?status=updated');
            exit;
        }
    } else {
        $openModal = true;
    }
}

$stmt = $db->query('SELECT ssh_id, ssh_ip, ssh_user, ssh_status FROM ssh ORDER BY ssh_id');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status = $_GET['status'] ?? '';
$alerts = [
    'created' => ['type' => 'success', 'message' => 'Conexão SSH cadastrada com sucesso.'],
    'updated' => ['type' => 'success', 'message' => 'Conexão SSH atualizada com sucesso.'],
    'notfound' => ['type' => 'danger', 'message' => 'Conexão não encontrada.'],
];
$currentAlert = $alerts[$status] ?? null;

safekup_render_header('Safekup — Conexões SSH', 'ssh');
?>
    <section class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Conexões SSH</h2>
                <p class="text-sm text-slate-300">
                    Credenciais utilizadas para acessar servidores que executam rotinas de backup.
                </p>
            </div>
            <button type="button" data-modal-trigger="create"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-500/90 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-400">
                <i class="fa fa-plus"></i>
                <span>Nova conexão</span>
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

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/60">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Host/IP</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Usuário</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-400">
                                Nenhuma conexão SSH cadastrada.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr class="hover:bg-slate-800/50">
                                <td class="px-4 py-3 font-medium"><?= safekup_escape($row['ssh_ip']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['ssh_user']); ?></td>
                                <td class="px-4 py-3">
                                    <?= safekup_badge($row['ssh_status'], $row['ssh_status'] === 'ATIVO' ? 'success' : 'danger'); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button"
                                           data-modal-trigger="edit"
                                           data-ssh='<?= safekup_escape(json_encode([
                                                'ssh_id' => (int)$row['ssh_id'],
                                                'ssh_ip' => $row['ssh_ip'],
                                                'ssh_user' => $row['ssh_user'],
                                                'ssh_status' => $row['ssh_status'],
                                            ], JSON_UNESCAPED_UNICODE)); ?>'
                                           class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-100 transition hover:bg-slate-700">
                                            <i class="fa fa-pencil"></i>
                                            <span>Editar</span>
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

    <div id="sshModal" class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/70 px-4 py-8">
        <div class="w-full max-w-3xl overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl shadow-indigo-900/30">
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                <div>
                    <h2 id="sshModalTitle" class="text-lg font-semibold text-white">Nova conexão</h2>
                    <p class="text-xs text-slate-400">Cadastre acessos SSH utilizados pelas rotinas de backup.</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" data-modal-close>
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <form method="post" class="grid gap-4 px-6 py-5">
                <input type="hidden" name="modal_mode" id="ssh_modal_mode" value="<?= safekup_escape($formMode); ?>">
                <input type="hidden" name="ssh_id" id="ssh_id" value="<?= safekup_escape((string)($formValues['ssh_id'] ?? '')); ?>">

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Host/IP</span>
                        <input name="ssh_ip" id="ssh_ip" value="<?= safekup_escape($formValues['ssh_ip']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['ssh_ip'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['ssh_ip']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm" id="sshUserWrapper">
                        <span class="text-slate-200">Usuário</span>
                        <input name="ssh_user" id="ssh_user" value="<?= safekup_escape($formValues['ssh_user']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['ssh_user'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['ssh_user']); ?></span>
                        <?php endif; ?>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2" id="sshPasswordGroup">
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Senha</span>
                        <input name="ssh_pass" id="ssh_pass" type="password"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['ssh_pass'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['ssh_pass']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Confirmar senha</span>
                        <input name="ssh_pass_confirm" id="ssh_pass_confirm" type="password"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['ssh_pass_confirm'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['ssh_pass_confirm']); ?></span>
                        <?php endif; ?>
                    </label>
                </div>

                <label class="flex flex-col gap-2 text-sm">
                    <span class="text-slate-200">Status</span>
                    <select name="ssh_status" id="ssh_status"
                            class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="ATIVO" <?= $formValues['ssh_status'] === 'ATIVO' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="BLOQUEADO" <?= $formValues['ssh_status'] === 'BLOQUEADO' ? 'selected' : ''; ?>>Bloqueado</option>
                    </select>
                    <?php if (isset($formErrors['ssh_status'])): ?>
                        <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['ssh_status']); ?></span>
                    <?php endif; ?>
                </label>

                <div class="flex justify-end gap-3 border-t border-white/10 pt-4">
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-indigo-400/60 hover:text-white" data-modal-close>
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        <i class="fa fa-save"></i>
                        <span id="sshModalSubmit">Salvar conexão</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const modal = document.getElementById('sshModal');
            const modeField = document.getElementById('ssh_modal_mode');
            const idField = document.getElementById('ssh_id');
            const ipField = document.getElementById('ssh_ip');
            const userField = document.getElementById('ssh_user');
            const statusField = document.getElementById('ssh_status');
            const passField = document.getElementById('ssh_pass');
            const passConfirmField = document.getElementById('ssh_pass_confirm');
            const titulo = document.getElementById('sshModalTitle');
            const submitLabel = document.getElementById('sshModalSubmit');
            const userWrapper = document.getElementById('sshUserWrapper');
            const passwordGroup = document.getElementById('sshPasswordGroup');

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

            function openModal(mode, data) {
                modeField.value = mode;
                if (mode === 'edit') {
                    titulo.textContent = 'Editar conexão SSH';
                    submitLabel.textContent = 'Salvar alterações';
                    userWrapper.querySelector('span').textContent = 'Usuário (apenas leitura)';
                    userField.setAttribute('readonly', 'readonly');
                    userField.classList.add('bg-slate-800/40');
                    passwordGroup.querySelector('span').textContent = 'Senha (opcional)';
                } else {
                    titulo.textContent = 'Cadastrar conexão SSH';
                    submitLabel.textContent = 'Salvar conexão';
                    userWrapper.querySelector('span').textContent = 'Usuário';
                    userField.removeAttribute('readonly');
                    userField.classList.remove('bg-slate-800/40');
                    passwordGroup.querySelector('span').textContent = 'Senha';
                }

                idField.value = data?.ssh_id ?? '';
                ipField.value = data?.ssh_ip ?? '';
                userField.value = data?.ssh_user ?? '';
                statusField.value = data?.ssh_status ?? 'ATIVO';
                passField.value = '';
                passConfirmField.value = '';

                toggleModal(true);
                setTimeout(() => ipField.focus(), 50);
            }

            document.querySelectorAll('[data-modal-trigger="create"]').forEach(btn => btn.addEventListener('click', () => openModal('create')));
            document.querySelectorAll('[data-modal-trigger="edit"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const dataset = btn.getAttribute('data-ssh');
                        const data = dataset ? JSON.parse(dataset) : {};
                        openModal('edit', data);
                    } catch (error) {
                        console.error(error);
                        openModal('edit');
                    }
                });
            });

            modal.querySelectorAll('[data-modal-close]').forEach(btn => btn.addEventListener('click', () => toggleModal(false)));
            modal.addEventListener('click', (event) => { if (event.target === modal) toggleModal(false); });
            window.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal.classList.contains('flex')) toggleModal(false); });

            <?php if ($openModal): ?>
            document.addEventListener('DOMContentLoaded', () => {
                openModal('<?= $formMode ?>', <?= json_encode(array_intersect_key($formValues, array_flip(['ssh_id','ssh_ip','ssh_user','ssh_status'])), JSON_UNESCAPED_UNICODE); ?>);
            });
            <?php endif; ?>
        })();
    </script>
<?php
safekup_render_footer();
