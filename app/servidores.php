<?php
require_once __DIR__ . '/bootstrap.php';

$db = safekup_db();

$formMode = 'create';
$formValues = [
    'servidor_id' => null,
    'servidor_nome' => '',
    'servidor_ip' => '',
    'servidor_plataforma' => '',
    'servidor_user_privilegio' => '',
    'servidor_senha_acesso' => '',
    'servidor_nome_compartilhamento' => '',
];
$formErrors = [];
$openModal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formMode = $_POST['modal_mode'] === 'edit' ? 'edit' : 'create';
    $formValues['servidor_id'] = isset($_POST['servidor_id']) ? (int)$_POST['servidor_id'] : null;
    $formValues['servidor_nome'] = trim($_POST['servidor_nome'] ?? '');
    $formValues['servidor_ip'] = trim($_POST['servidor_ip'] ?? '');
    $formValues['servidor_plataforma'] = trim($_POST['servidor_plataforma'] ?? '');
    $formValues['servidor_user_privilegio'] = trim($_POST['servidor_user_privilegio'] ?? '');
    $formValues['servidor_senha_acesso'] = $_POST['servidor_senha_acesso'] ?? '';
    $formValues['servidor_nome_compartilhamento'] = trim($_POST['servidor_nome_compartilhamento'] ?? '');

    if ($formValues['servidor_nome'] === '') {
        $formErrors['servidor_nome'] = 'Informe um nome para identificar o servidor.';
    }
    if ($formValues['servidor_ip'] === '' || !filter_var($formValues['servidor_ip'], FILTER_VALIDATE_IP)) {
        $formErrors['servidor_ip'] = 'Informe um IP válido.';
    }
    if (!in_array($formValues['servidor_plataforma'], ['Linux', 'Windows'], true)) {
        $formErrors['servidor_plataforma'] = 'Selecione a plataforma.';
    }
    if ($formValues['servidor_user_privilegio'] === '') {
        $formErrors['servidor_user_privilegio'] = 'Informe o usuário administrativo.';
    }
    if ($formMode === 'create' && $formValues['servidor_senha_acesso'] === '') {
        $formErrors['servidor_senha_acesso'] = 'Informe a senha de acesso.';
    }
    if ($formValues['servidor_nome_compartilhamento'] === '') {
        $formErrors['servidor_nome_compartilhamento'] = 'Informe o compartilhamento de destino.';
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $stmt = $db->prepare('SELECT COUNT(*) FROM servidores WHERE servidor_nome = :nome');
            $stmt->execute([':nome' => $formValues['servidor_nome']]);
            if ($stmt->fetchColumn() > 0) {
                $formErrors['servidor_nome'] = 'Já existe um servidor com esse nome.';
            }
        } else {
            if (!$formValues['servidor_id']) {
                $formErrors['servidor_id'] = 'Registro inválido.';
            } else {
                $stmt = $db->prepare('SELECT COUNT(*) FROM servidores WHERE servidor_nome = :nome AND servidor_id <> :id');
                $stmt->execute([':nome' => $formValues['servidor_nome'], ':id' => $formValues['servidor_id']]);
                if ($stmt->fetchColumn() > 0) {
                    $formErrors['servidor_nome'] = 'Já existe um servidor com esse nome.';
                }
            }
        }
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $insert = $db->prepare('INSERT INTO servidores (servidor_nome, servidor_ip, servidor_user_privilegio, servidor_senha_acesso, servidor_nome_compartilhamento, servidor_plataforma) VALUES (:nome, :ip, :usuario, :senha, :compartilhamento, :plataforma)');
            $insert->execute([
                ':nome' => $formValues['servidor_nome'],
                ':ip' => $formValues['servidor_ip'],
                ':usuario' => $formValues['servidor_user_privilegio'],
                ':senha' => base64_encode($formValues['servidor_senha_acesso']),
                ':compartilhamento' => $formValues['servidor_nome_compartilhamento'],
                ':plataforma' => $formValues['servidor_plataforma'],
            ]);
            header('Location: /app/servidores.php?status=created');
            exit;
        }

        if ($formMode === 'edit' && $formValues['servidor_id']) {
            $senhaFinal = $formValues['servidor_senha_acesso'];
            if ($senhaFinal === '') {
                $currentStmt = $db->prepare('SELECT servidor_senha_acesso FROM servidores WHERE servidor_id = :id');
                $currentStmt->execute([':id' => $formValues['servidor_id']]);
                $senhaFinal = $currentStmt->fetchColumn();
            } else {
                $senhaFinal = base64_encode($senhaFinal);
            }

            $update = $db->prepare('UPDATE servidores SET servidor_nome = :nome, servidor_ip = :ip, servidor_user_privilegio = :usuario, servidor_senha_acesso = :senha, servidor_nome_compartilhamento = :compartilhamento, servidor_plataforma = :plataforma WHERE servidor_id = :id');
            $update->execute([
                ':nome' => $formValues['servidor_nome'],
                ':ip' => $formValues['servidor_ip'],
                ':usuario' => $formValues['servidor_user_privilegio'],
                ':senha' => $senhaFinal,
                ':compartilhamento' => $formValues['servidor_nome_compartilhamento'],
                ':plataforma' => $formValues['servidor_plataforma'],
                ':id' => $formValues['servidor_id'],
            ]);
            header('Location: /app/servidores.php?status=updated');
            exit;
        }
    } else {
        $openModal = true;
    }
}

$stmt = $db->query('SELECT servidor_id, servidor_nome, servidor_ip, servidor_user_privilegio, servidor_plataforma, servidor_nome_compartilhamento, servidor_senha_acesso FROM servidores ORDER BY servidor_id DESC');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status = $_GET['status'] ?? '';
$alerts = [
    'created' => ['type' => 'success', 'message' => 'Servidor cadastrado com sucesso.'],
    'updated' => ['type' => 'success', 'message' => 'Servidor atualizado com sucesso.'],
    'notfound' => ['type' => 'danger', 'message' => 'Servidor não encontrado ou removido.'],
];
$currentAlert = $alerts[$status] ?? null;

safekup_render_header('Safekup — Servidores de Backup', 'servidores');
?>
    <section class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Servidores de Backup</h2>
                <p class="text-sm text-slate-300">
                    Hosts responsáveis por armazenar os dumps gerados pelas rotinas do Safekup.
                </p>
            </div>
            <button type="button"
               data-modal-trigger="create"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-500/90 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-400">
                <i class="fa fa-plus"></i>
                <span>Novo servidor</span>
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

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/60">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Nome</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">IP</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Usuário</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Plataforma</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Compartilhamento</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-400">
                                Nenhum servidor cadastrado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr class="hover:bg-slate-800/50">
                                <td class="px-4 py-3 font-medium"><?= safekup_escape($row['servidor_nome']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['servidor_ip']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['servidor_user_privilegio']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['servidor_plataforma']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['servidor_nome_compartilhamento']); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button"
                                           data-modal-trigger="edit"
                                           data-servidor='<?= safekup_escape(json_encode([
                                                'servidor_id' => (int)$row['servidor_id'],
                                                'servidor_nome' => $row['servidor_nome'],
                                                'servidor_ip' => $row['servidor_ip'],
                                                'servidor_user_privilegio' => $row['servidor_user_privilegio'],
                                                'servidor_plataforma' => $row['servidor_plataforma'],
                                                'servidor_nome_compartilhamento' => $row['servidor_nome_compartilhamento'],
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

    <div id="servidorModal" class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/70 px-4 py-8">
        <div class="w-full max-w-3xl overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl shadow-indigo-900/30">
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                <div>
                    <h2 id="servidorModalTitle" class="text-lg font-semibold text-white">Novo servidor</h2>
                    <p class="text-xs text-slate-400">Informe os dados do servidor responsável pelo armazenamento dos backups.</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" data-modal-close>
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <form method="post" class="grid gap-4 px-6 py-5">
                <input type="hidden" name="modal_mode" id="modal_mode" value="<?= safekup_escape($formMode); ?>">
                <input type="hidden" name="servidor_id" id="servidor_id" value="<?= safekup_escape((string)($formValues['servidor_id'] ?? '')); ?>">

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Nome do servidor</span>
                        <input name="servidor_nome" id="servidor_nome" value="<?= safekup_escape($formValues['servidor_nome']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['servidor_nome'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['servidor_nome']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">IP do servidor</span>
                        <input name="servidor_ip" id="servidor_ip" value="<?= safekup_escape($formValues['servidor_ip']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['servidor_ip'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['servidor_ip']); ?></span>
                        <?php endif; ?>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Plataforma</span>
                        <select name="servidor_plataforma" id="servidor_plataforma"
                                class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Selecione</option>
                            <option value="Linux" <?= $formValues['servidor_plataforma'] === 'Linux' ? 'selected' : ''; ?>>Linux</option>
                            <option value="Windows" <?= $formValues['servidor_plataforma'] === 'Windows' ? 'selected' : ''; ?>>Windows</option>
                        </select>
                        <?php if (isset($formErrors['servidor_plataforma'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['servidor_plataforma']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Usuário administrativo</span>
                        <input name="servidor_user_privilegio" id="servidor_user_privilegio" value="<?= safekup_escape($formValues['servidor_user_privilegio']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['servidor_user_privilegio'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['servidor_user_privilegio']); ?></span>
                        <?php endif; ?>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="flex flex-col gap-2 text-sm">
                        <div class="flex items-center justify-between text-slate-200">
                            <span>Senha</span>
                            <span class="text-xs text-slate-400" id="senhaHint">Obrigatório</span>
                        </div>
                        <input name="servidor_senha_acesso" id="servidor_senha_acesso" type="password" value=""
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['servidor_senha_acesso'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['servidor_senha_acesso']); ?></span>
                        <?php endif; ?>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="text-slate-200">Compartilhamento</span>
                        <input name="servidor_nome_compartilhamento" id="servidor_nome_compartilhamento" value="<?= safekup_escape($formValues['servidor_nome_compartilhamento']); ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        <?php if (isset($formErrors['servidor_nome_compartilhamento'])): ?>
                            <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['servidor_nome_compartilhamento']); ?></span>
                        <?php endif; ?>
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-white/10 pt-4">
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-indigo-400/60 hover:text-white" data-modal-close>
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        <i class="fa fa-save"></i>
                        <span id="servidorModalSubmit">Salvar servidor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const modal = document.getElementById('servidorModal');
            const modeField = document.getElementById('modal_mode');
            const idField = document.getElementById('servidor_id');
            const nomeField = document.getElementById('servidor_nome');
            const ipField = document.getElementById('servidor_ip');
            const plataformaField = document.getElementById('servidor_plataforma');
            const usuarioField = document.getElementById('servidor_user_privilegio');
            const senhaField = document.getElementById('servidor_senha_acesso');
            const compartilhamentoField = document.getElementById('servidor_nome_compartilhamento');
            const titulo = document.getElementById('servidorModalTitle');
            const submitLabel = document.getElementById('servidorModalSubmit');
            const senhaHint = document.getElementById('senhaHint');

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
                    titulo.textContent = 'Editar servidor de backup';
                    submitLabel.textContent = 'Salvar alterações';
                    senhaHint.textContent = 'Deixe em branco para manter a atual';
                } else {
                    titulo.textContent = 'Cadastrar servidor de backup';
                    submitLabel.textContent = 'Salvar servidor';
                    senhaHint.textContent = 'Obrigatório';
                }

                idField.value = data?.servidor_id ?? '';
                nomeField.value = data?.servidor_nome ?? '';
                ipField.value = data?.servidor_ip ?? '';
                plataformaField.value = data?.servidor_plataforma ?? '';
                usuarioField.value = data?.servidor_user_privilegio ?? '';
                senhaField.value = '';
                compartilhamentoField.value = data?.servidor_nome_compartilhamento ?? '';

                toggleModal(true);
                setTimeout(() => nomeField.focus(), 50);
            }

            document.querySelectorAll('[data-modal-trigger="create"]').forEach(btn => {
                btn.addEventListener('click', () => openModal('create'));
            });

            document.querySelectorAll('[data-modal-trigger="edit"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const dataset = btn.getAttribute('data-servidor');
                        const data = dataset ? JSON.parse(dataset) : {};
                        openModal('edit', data);
                    } catch (error) {
                        console.error('Erro ao abrir modal:', error);
                        openModal('edit');
                    }
                });
            });

            modal.querySelectorAll('[data-modal-close]').forEach(btn => {
                btn.addEventListener('click', () => toggleModal(false));
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    toggleModal(false);
                }
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('flex')) {
                    toggleModal(false);
                }
            });

            <?php if ($openModal): ?>
            document.addEventListener('DOMContentLoaded', () => {
                openModal('<?= $formMode ?>', <?= json_encode(array_intersect_key($formValues, array_flip(['servidor_id','servidor_nome','servidor_ip','servidor_plataforma','servidor_user_privilegio','servidor_nome_compartilhamento'])), JSON_UNESCAPED_UNICODE); ?>);
            });
            <?php endif; ?>
        })();
    </script>
<?php
safekup_render_footer();
