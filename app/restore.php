<?php
require_once __DIR__ . '/bootstrap.php';

$db = safekup_db();

$formMode = 'create';
$formValues = [
    'restore_id' => null,
    'restore_nome' => '',
    'restore_ip' => '',
];
$formErrors = [];
$openModal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formMode = $_POST['modal_mode'] === 'edit' ? 'edit' : 'create';
    $formValues['restore_id'] = isset($_POST['restore_id']) ? (int)$_POST['restore_id'] : null;
    $formValues['restore_nome'] = trim($_POST['restore_nome'] ?? '');
    $formValues['restore_ip'] = trim($_POST['restore_ip'] ?? '');

    if ($formValues['restore_nome'] === '') {
        $formErrors['restore_nome'] = 'Informe um nome para identificação.';
    }
    if ($formValues['restore_ip'] === '' || !filter_var($formValues['restore_ip'], FILTER_VALIDATE_IP)) {
        $formErrors['restore_ip'] = 'Informe um IP válido.';
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $exists = $db->prepare('SELECT COUNT(*) FROM restores WHERE restore_nome = :nome');
            $exists->execute([':nome' => $formValues['restore_nome']]);
            if ($exists->fetchColumn() > 0) {
                $formErrors['restore_nome'] = 'Já existe um servidor com esse nome.';
            }
        } else {
            if (!$formValues['restore_id']) {
                $formErrors['restore_id'] = 'Registro inválido.';
            } else {
                $exists = $db->prepare('SELECT COUNT(*) FROM restores WHERE restore_nome = :nome AND restore_id <> :id');
                $exists->execute([':nome' => $formValues['restore_nome'], ':id' => $formValues['restore_id']]);
                if ($exists->fetchColumn() > 0) {
                    $formErrors['restore_nome'] = 'Já existe um servidor com esse nome.';
                }
            }
        }
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $insert = $db->prepare('INSERT INTO restores (restore_nome, restore_ip) VALUES (:nome, :ip)');
            $insert->execute([
                ':nome' => $formValues['restore_nome'],
                ':ip' => $formValues['restore_ip'],
            ]);
            header('Location: /app/restore.php?status=created');
            exit;
        }

        if ($formMode === 'edit' && $formValues['restore_id']) {
            $update = $db->prepare('UPDATE restores SET restore_nome = :nome, restore_ip = :ip WHERE restore_id = :id');
            $update->execute([
                ':nome' => $formValues['restore_nome'],
                ':ip' => $formValues['restore_ip'],
                ':id' => $formValues['restore_id'],
            ]);
            header('Location: /app/restore.php?status=updated');
            exit;
        }
    } else {
        $openModal = true;
    }
}

$stmt = $db->query('SELECT restore_id, restore_nome, restore_ip FROM restores ORDER BY restore_id DESC');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status = $_GET['status'] ?? '';
$alerts = [
    'created' => ['type' => 'success', 'message' => 'Servidor de restore cadastrado com sucesso.'],
    'updated' => ['type' => 'success', 'message' => 'Servidor de restore atualizado com sucesso.'],
    'notfound' => ['type' => 'danger', 'message' => 'Servidor não encontrado.'],
];
$currentAlert = $alerts[$status] ?? null;

safekup_render_header('Safekup — Servidor de Restore', 'restore');
?>
    <section class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Servidores de Restore</h2>
                <p class="text-sm text-slate-300">
                    Cadastros responsáveis por restaurar bases de dados exportadas pelo Safekup.
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
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-slate-400">
                                Nenhum servidor de restore cadastrado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr class="hover:bg-slate-800/50">
                                <td class="px-4 py-3 font-medium"><?= safekup_escape($row['restore_nome']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['restore_ip']); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button"
                                           data-modal-trigger="edit"
                                           data-restore='<?= safekup_escape(json_encode([
                                                'restore_id' => (int)$row['restore_id'],
                                                'restore_nome' => $row['restore_nome'],
                                                'restore_ip' => $row['restore_ip'],
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

    <div id="restoreModal" class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/70 px-4 py-8">
        <div class="w-full max-w-xl overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl shadow-indigo-900/30">
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                <div>
                    <h2 id="restoreModalTitle" class="text-lg font-semibold text-white">Novo servidor</h2>
                    <p class="text-xs text-slate-400">Cadastre os servidores utilizados para restaurar dumps.</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" data-modal-close>
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <form method="post" class="grid gap-4 px-6 py-5">
                <input type="hidden" name="modal_mode" id="restore_modal_mode" value="<?= safekup_escape($formMode); ?>">
                <input type="hidden" name="restore_id" id="restore_id" value="<?= safekup_escape((string)($formValues['restore_id'] ?? '')); ?>">

                <label class="flex flex-col gap-2 text-sm">
                    <span class="text-slate-200">Nome do servidor</span>
                    <input name="restore_nome" id="restore_nome" value="<?= safekup_escape($formValues['restore_nome']); ?>"
                           class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    <?php if (isset($formErrors['restore_nome'])): ?>
                        <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['restore_nome']); ?></span>
                    <?php endif; ?>
                </label>

                <label class="flex flex-col gap-2 text-sm">
                    <span class="text-slate-200">IP do servidor</span>
                    <input name="restore_ip" id="restore_ip" value="<?= safekup_escape($formValues['restore_ip']); ?>"
                           class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    <?php if (isset($formErrors['restore_ip'])): ?>
                        <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['restore_ip']); ?></span>
                    <?php endif; ?>
                </label>

                <div class="flex justify-end gap-3 border-t border-white/10 pt-4">
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-indigo-400/60 hover:text-white" data-modal-close>
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        <i class="fa fa-save"></i>
                        <span id="restoreModalSubmit">Salvar servidor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const modal = document.getElementById('restoreModal');
            const modeField = document.getElementById('restore_modal_mode');
            const idField = document.getElementById('restore_id');
            const nomeField = document.getElementById('restore_nome');
            const ipField = document.getElementById('restore_ip');
            const title = document.getElementById('restoreModalTitle');
            const submitLabel = document.getElementById('restoreModalSubmit');

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
                    title.textContent = 'Editar servidor de restore';
                    submitLabel.textContent = 'Salvar alterações';
                } else {
                    title.textContent = 'Cadastrar servidor de restore';
                    submitLabel.textContent = 'Salvar servidor';
                }

                idField.value = data?.restore_id ?? '';
                nomeField.value = data?.restore_nome ?? '';
                ipField.value = data?.restore_ip ?? '';

                toggleModal(true);
                setTimeout(() => nomeField.focus(), 50);
            }

            document.querySelectorAll('[data-modal-trigger="create"]').forEach(btn => {
                btn.addEventListener('click', () => openModal('create'));
            });

            document.querySelectorAll('[data-modal-trigger="edit"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const dataset = btn.getAttribute('data-restore');
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
                openModal('<?= $formMode ?>', <?= json_encode(array_intersect_key($formValues, array_flip(['restore_id','restore_nome','restore_ip'])), JSON_UNESCAPED_UNICODE); ?>);
            });
            <?php endif; ?>
        })();
    </script>
<?php
safekup_render_footer();
