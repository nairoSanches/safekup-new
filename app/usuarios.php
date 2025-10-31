<?php
require_once __DIR__ . '/bootstrap.php';

$db = safekup_db();

$perfis = [
    '1' => 'Administrador',
    '2' => 'USID',
];
$statusOptions = ['ATIVO', 'BLOQUEADO'];

$formMode = 'create';
$formValues = [
    'usuario_id' => null,
    'usuario_nome' => '',
    'usuario_login' => '',
    'usuario_id_app' => '1',
    'usuario_status' => 'ATIVO',
    'usuario_email' => '',
];
$formErrors = [];
$openModal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formMode = $_POST['modal_mode'] === 'edit' ? 'edit' : 'create';
    $formValues['usuario_id'] = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;
    $formValues['usuario_nome'] = trim($_POST['usuario_nome'] ?? '');
    $formValues['usuario_login'] = trim($_POST['usuario_login'] ?? '');
    $formValues['usuario_id_app'] = $_POST['usuario_id_app'] ?? '1';
    $formValues['usuario_status'] = $_POST['usuario_status'] ?? 'ATIVO';
    $formValues['usuario_email'] = trim($_POST['usuario_email'] ?? '');
    $password = $_POST['usuario_senha'] ?? '';
    $passwordConfirm = $_POST['usuario_senha_confirm'] ?? '';

    if ($formValues['usuario_nome'] === '') {
        $formErrors['usuario_nome'] = 'Informe o nome do usuário.';
    }
    if ($formMode === 'create' && $formValues['usuario_login'] === '') {
        $formErrors['usuario_login'] = 'Informe o login.';
    }
    if (!array_key_exists($formValues['usuario_id_app'], $perfis)) {
        $formErrors['usuario_id_app'] = 'Selecione o perfil.';
    }
    if (!in_array($formValues['usuario_status'], $statusOptions, true)) {
        $formErrors['usuario_status'] = 'Selecione o status.';
    }
    if ($formValues['usuario_email'] === '' || !filter_var($formValues['usuario_email'], FILTER_VALIDATE_EMAIL)) {
        $formErrors['usuario_email'] = 'Informe um e-mail válido.';
    }

    if ($formMode === 'create') {
        if ($password === '') {
            $formErrors['usuario_senha'] = 'Informe uma senha inicial.';
        }
        if ($password !== $passwordConfirm) {
            $formErrors['usuario_senha_confirm'] = 'As senhas não coincidem.';
        }
    }

    if ($formMode === 'edit') {
        if (!$formValues['usuario_id']) {
            $formErrors['usuario_id'] = 'Registro inválido.';
        }
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $exists = $db->prepare('SELECT COUNT(*) FROM usuario WHERE usuario_login = :login');
            $exists->execute([':login' => $formValues['usuario_login']]);
            if ($exists->fetchColumn() > 0) {
                $formErrors['usuario_login'] = 'Já existe um usuário com esse login.';
            }
        }
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $insert = $db->prepare('
                INSERT INTO usuario (
                    usuario_nome, usuario_login, usuario_senha, usuario_status,
                    usuario_id_app, usuario_email, usuario_tentativas_invalidas, usuario_data_bloqueio
                ) VALUES (
                    UPPER(:nome), :login, :senha, :status,
                    :perfil, :email, 0, "1900-01-01 00:00:00"
                )
            ');
            $insert->execute([
                ':nome' => $formValues['usuario_nome'],
                ':login' => $formValues['usuario_login'],
                ':senha' => md5($password),
                ':status' => $formValues['usuario_status'],
                ':perfil' => (int)$formValues['usuario_id_app'],
                ':email' => $formValues['usuario_email'],
            ]);
            header('Location: /app/usuarios.php?status=created');
            exit;
        }

        if ($formMode === 'edit' && $formValues['usuario_id']) {
            $update = $db->prepare('
                UPDATE usuario SET
                    usuario_nome = UPPER(:nome),
                    usuario_status = :status,
                    usuario_id_app = :perfil,
                    usuario_email = :email,
                    usuario_tentativas_invalidas = 0,
                    usuario_data_bloqueio = "1900-01-01 00:00:00"
                WHERE usuario_id = :id
            ');
            $update->execute([
                ':nome' => $formValues['usuario_nome'],
                ':status' => $formValues['usuario_status'],
                ':perfil' => (int)$formValues['usuario_id_app'],
                ':email' => $formValues['usuario_email'],
                ':id' => $formValues['usuario_id'],
            ]);
            header('Location: /app/usuarios.php?status=updated');
            exit;
        }
    } else {
        $openModal = true;
    }
}

$stmt = $db->query('SELECT usuario_id, usuario_nome, usuario_login, usuario_status, usuario_id_app, usuario_email FROM usuario ORDER BY usuario_nome');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status = $_GET['status'] ?? '';
$alerts = [
    'created' => ['type' => 'success', 'message' => 'Usuário cadastrado com sucesso.'],
    'updated' => ['type' => 'success', 'message' => 'Usuário atualizado com sucesso.'],
    'notfound' => ['type' => 'danger', 'message' => 'Usuário não encontrado.'],
];
$currentAlert = $alerts[$status] ?? null;

safekup_render_header('Safekup — Usuários', 'usuarios');
?>
    <section class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Usuários</h2>
                <p class="text-sm text-slate-300">Gerencie os perfis que acessam o Safekup.</p>
            </div>
            <button type="button" data-modal-trigger="create"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-500/90 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-400">
                <i class="fa fa-plus"></i>
                <span>Novo usuário</span>
            </button>
        </div>

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
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">ID</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Nome</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Login</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Perfil</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Email</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-400">Nenhum usuário cadastrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $payload = [
                                    'usuario_id' => (int)$row['usuario_id'],
                                    'usuario_nome' => $row['usuario_nome'],
                                    'usuario_login' => $row['usuario_login'],
                                    'usuario_id_app' => $row['usuario_id_app'],
                                    'usuario_status' => $row['usuario_status'],
                                    'usuario_email' => $row['usuario_email'],
                                ];
                            ?>
                            <tr class="hover:bg-slate-800/50">
                                <td class="px-4 py-3 font-medium">#<?= safekup_escape((string)$row['usuario_id']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['usuario_nome']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['usuario_login']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape(safekup_perfil_label($row['usuario_id_app'])); ?></td>
                                <td class="px-4 py-3">
                                    <?= safekup_badge(
                                        strtoupper((string)$row['usuario_status']) === 'ATIVO' ? 'Ativo' : 'Bloqueado',
                                        strtoupper((string)$row['usuario_status']) === 'ATIVO' ? 'success' : 'danger'
                                    ); ?>
                                </td>
                                <td class="px-4 py-3"><?= safekup_escape($row['usuario_email']); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button"
                                           data-modal-trigger="edit"
                                           data-usuario='<?= safekup_escape(json_encode($payload, JSON_UNESCAPED_UNICODE)); ?>'
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

    <div id="usuarioModal" class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/70 px-4 py-8">
        <div class="w-full max-w-3xl overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl shadow-indigo-900/30">
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                <div>
                    <h2 id="usuarioModalTitle" class="text-lg font-semibold text-white">Novo usuário</h2>
                    <p class="text-xs text-slate-400">Cadastre perfis de acesso para o Safekup.</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" data-modal-close>
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <form method="post" class="grid gap-4 px-6 py-5">
                <input type="hidden" name="modal_mode" id="usuario_modal_mode" value="<?= safekup_escape($formMode); ?>">
                <input type="hidden" name="usuario_id" id="usuario_id" value="<?= safekup_escape((string)($formValues['usuario_id'] ?? '')); ?>">

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Nome</span>
                        <input name="usuario_nome" id="usuario_nome" value="<?= safekup_escape($formValues['usuario_nome']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['usuario_nome'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['usuario_nome']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm" id="loginWrapper">
                        <span class="text-slate-200">Login</span>
                        <input name="usuario_login" id="usuario_login" value="<?= safekup_escape($formValues['usuario_login']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['usuario_login'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['usuario_login']); ?></span>
                        <?php endif; ?>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Perfil</span>
                        <select name="usuario_id_app" id="usuario_id_app"
                                class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <?php foreach ($perfis as $perfilId => $perfilLabel): ?>
                                <option value="<?= safekup_escape($perfilId); ?>" <?= (string)$formValues['usuario_id_app'] === (string)$perfilId ? 'selected' : ''; ?>>
                                    <?= safekup_escape($perfilLabel); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($formErrors['usuario_id_app'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['usuario_id_app']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Status</span>
                        <select name="usuario_status" id="usuario_status"
                                class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <?php foreach ($statusOptions as $option): ?>
                                <option value="<?= $option; ?>" <?= $formValues['usuario_status'] === $option ? 'selected' : ''; ?>><?= $option; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($formErrors['usuario_status'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['usuario_status']); ?></span>
                        <?php endif; ?>
                    </label>
                </div>

                <label class="flex flex-col gap-2 text-sm">
                    <span class="text-slate-200">Email</span>
                    <input name="usuario_email" id="usuario_email" type="email" value="<?= safekup_escape($formValues['usuario_email']); ?>"
                           class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    <?php if (isset($formErrors['usuario_email'])): ?>
                        <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['usuario_email']); ?></span>
                    <?php endif; ?>
                </label>

                <div class="grid gap-4 sm:grid-cols-2" id="senhaWrapper">
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Senha</span>
                        <input name="usuario_senha" id="usuario_senha" type="password"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['usuario_senha'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['usuario_senha']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Confirmar senha</span>
                        <input name="usuario_senha_confirm" id="usuario_senha_confirm" type="password"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['usuario_senha_confirm'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['usuario_senha_confirm']); ?></span>
                        <?php endif; ?>
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-white/10 pt-4">
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover;border-indigo-400/60 hover:text-white" data-modal-close>
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        <i class="fa fa-save"></i>
                        <span id="usuarioModalSubmit">Salvar usuário</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const modal = document.getElementById('usuarioModal');
            const modeField = document.getElementById('usuario_modal_mode');
            const idField = document.getElementById('usuario_id');
            const nomeField = document.getElementById('usuario_nome');
            const loginField = document.getElementById('usuario_login');
            const perfilField = document.getElementById('usuario_id_app');
            const statusField = document.getElementById('usuario_status');
            const emailField = document.getElementById('usuario_email');
            const senhaField = document.getElementById('usuario_senha');
            const senhaConfirmField = document.getElementById('usuario_senha_confirm');
            const titulo = document.getElementById('usuarioModalTitle');
            const submitLabel = document.getElementById('usuarioModalSubmit');
            const loginWrapper = document.getElementById('loginWrapper');
            const senhaWrapper = document.getElementById('senhaWrapper');

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
                    titulo.textContent = 'Editar usuário';
                    submitLabel.textContent = 'Salvar alterações';
                    loginField.setAttribute('readonly', 'readonly');
                    loginField.classList.add('bg-slate-800/40');
                    senhaWrapper.classList.add('hidden');
                } else {
                    titulo.textContent = 'Cadastrar usuário';
                    submitLabel.textContent = 'Salvar usuário';
                    loginField.removeAttribute('readonly');
                    loginField.classList.remove('bg-slate-800/40');
                    senhaWrapper.classList.remove('hidden');
                }

                idField.value = data?.usuario_id ?? '';
                nomeField.value = data?.usuario_nome ?? '';
                loginField.value = data?.usuario_login ?? '';
                perfilField.value = data?.usuario_id_app ?? '1';
                statusField.value = data?.usuario_status ?? 'ATIVO';
                emailField.value = data?.usuario_email ?? '';
                senhaField.value = '';
                senhaConfirmField.value = '';

                toggleModal(true);
                setTimeout(() => nomeField.focus(), 50);
            }

            document.querySelectorAll('[data-modal-trigger="create"]').forEach(btn => btn.addEventListener('click', () => openModal('create')));
            document.querySelectorAll('[data-modal-trigger="edit"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const dataset = btn.getAttribute('data-usuario');
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
                openModal('<?= $formMode ?>', <?= json_encode(array_intersect_key($formValues, array_flip(['usuario_id','usuario_nome','usuario_login','usuario_id_app','usuario_status','usuario_email'])), JSON_UNESCAPED_UNICODE); ?>);
            });
            <?php endif; ?>
        })();
    </script>
<?php
safekup_render_footer();
