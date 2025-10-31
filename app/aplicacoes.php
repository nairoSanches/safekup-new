<?php
require_once __DIR__ . '/bootstrap.php';

$db = safekup_db();

$formMode = 'create';
$formValues = [
    'app_id' => null,
    'app_nome' => '',
    'app_descricao' => '',
];
$formErrors = [];
$openModal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formMode = $_POST['modal_mode'] === 'edit' ? 'edit' : 'create';
    $formValues['app_id'] = isset($_POST['app_id']) ? (int)$_POST['app_id'] : null;
    $formValues['app_nome'] = trim($_POST['app_nome'] ?? '');
    $formValues['app_descricao'] = trim($_POST['app_descricao'] ?? '');

    if ($formValues['app_nome'] === '') {
        $formErrors['app_nome'] = 'Informe o nome da aplicação.';
    }
    if ($formValues['app_descricao'] === '') {
        $formErrors['app_descricao'] = 'Informe uma descrição.';
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $exists = $db->prepare('SELECT COUNT(*) FROM aplicacao WHERE app_nome = :nome');
            $exists->execute([':nome' => $formValues['app_nome']]);
            if ($exists->fetchColumn() > 0) {
                $formErrors['app_nome'] = 'Já existe uma aplicação com esse nome.';
            }
        } else {
            if (!$formValues['app_id']) {
                $formErrors['app_id'] = 'Registro inválido.';
            } else {
                $exists = $db->prepare('SELECT COUNT(*) FROM aplicacao WHERE app_nome = :nome AND app_id <> :id');
                $exists->execute([':nome' => $formValues['app_nome'], ':id' => $formValues['app_id']]);
                if ($exists->fetchColumn() > 0) {
                    $formErrors['app_nome'] = 'Já existe uma aplicação com esse nome.';
                }
            }
        }
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $insert = $db->prepare('INSERT INTO aplicacao (app_nome, app_descricao) VALUES (:nome, :descricao)');
            $insert->execute([
                ':nome' => $formValues['app_nome'],
                ':descricao' => $formValues['app_descricao'],
            ]);
            header('Location: /app/aplicacoes.php?status=created');
            exit;
        }

        if ($formMode === 'edit' && $formValues['app_id']) {
            $update = $db->prepare('UPDATE aplicacao SET app_nome = :nome, app_descricao = :descricao WHERE app_id = :id');
            $update->execute([
                ':nome' => $formValues['app_nome'],
                ':descricao' => $formValues['app_descricao'],
                ':id' => $formValues['app_id'],
            ]);
            header('Location: /app/aplicacoes.php?status=updated');
            exit;
        }
    } else {
        $openModal = true;
    }
}

$stmt = $db->query('SELECT app_id, app_nome, app_descricao FROM aplicacao WHERE app_id != 1 ORDER BY app_nome');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status = $_GET['status'] ?? '';
$alerts = [
    'created' => ['type' => 'success', 'message' => 'Aplicação cadastrada com sucesso.'],
    'updated' => ['type' => 'success', 'message' => 'Aplicação atualizada com sucesso.'],
    'notfound' => ['type' => 'danger', 'message' => 'Aplicação não encontrada.'],
];
$currentAlert = $alerts[$status] ?? null;

safekup_render_header('Safekup — Aplicações', 'aplicacoes');
?>
    <section class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Aplicações</h2>
                <p class="text-sm text-slate-300">
                    Relacione bancos de dados às aplicações que os utilizam.
                </p>
            </div>
            <button type="button" data-modal-trigger="create"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-500/90 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-400">
                <i class="fa fa-plus"></i>
                <span>Nova aplicação</span>
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
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Código</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Nome</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Descrição</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-400">
                                Nenhuma aplicação cadastrada.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr class="hover:bg-slate-800/50">
                                <td class="px-4 py-3 font-medium">#<?= safekup_escape((string)$row['app_id']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['app_nome']); ?></td>
                                <td class="px-4 py-3 text-slate-300"><?= safekup_escape($row['app_descricao']); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button"
                                           data-modal-trigger="edit"
                                           data-aplicacao='<?= safekup_escape(json_encode([
                                                'app_id' => (int)$row['app_id'],
                                                'app_nome' => $row['app_nome'],
                                                'app_descricao' => $row['app_descricao'],
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

    <div id="appModal" class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/70 px-4 py-8">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl shadow-indigo-900/30">
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                <div>
                    <h2 id="appModalTitle" class="text-lg font-semibold text-white">Nova aplicação</h2>
                    <p class="text-xs text-slate-400">Cadastre as aplicações que utilizam os bancos protegidos.</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" data-modal-close>
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <form method="post" class="grid gap-4 px-6 py-5">
                <input type="hidden" name="modal_mode" id="app_modal_mode" value="<?= safekup_escape($formMode); ?>">
                <input type="hidden" name="app_id" id="app_id" value="<?= safekup_escape((string)($formValues['app_id'] ?? '')); ?>">

                <label class="flex flex-col gap-2 text-sm">
                    <span class="text-slate-200">Nome da aplicação</span>
                    <input name="app_nome" id="app_nome" value="<?= safekup_escape($formValues['app_nome']); ?>"
                           class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    <?php if (isset($formErrors['app_nome'])): ?>
                        <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['app_nome']); ?></span>
                    <?php endif; ?>
                </label>

                <label class="flex flex-col gap-2 text-sm">
                    <span class="text-slate-200">Descrição</span>
                    <textarea name="app_descricao" id="app_descricao" rows="4"
                              class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"><?= safekup_escape($formValues['app_descricao']); ?></textarea>
                    <?php if (isset($formErrors['app_descricao'])): ?>
                        <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['app_descricao']); ?></span>
                    <?php endif; ?>
                </label>

                <div class="flex justify-end gap-3 border-t border-white/10 pt-4">
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-indigo-400/60 hover:text-white" data-modal-close>
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        <i class="fa fa-save"></i>
                        <span id="appModalSubmit">Salvar aplicação</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const modal = document.getElementById('appModal');
            const modeField = document.getElementById('app_modal_mode');
            const idField = document.getElementById('app_id');
            const nomeField = document.getElementById('app_nome');
            const descricaoField = document.getElementById('app_descricao');
            const titulo = document.getElementById('appModalTitle');
            const submitLabel = document.getElementById('appModalSubmit');

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
                    titulo.textContent = 'Editar aplicação';
                    submitLabel.textContent = 'Salvar alterações';
                } else {
                    titulo.textContent = 'Cadastrar aplicação';
                    submitLabel.textContent = 'Salvar aplicação';
                }

                idField.value = data?.app_id ?? '';
                nomeField.value = data?.app_nome ?? '';
                descricaoField.value = data?.app_descricao ?? '';

                toggleModal(true);
                setTimeout(() => nomeField.focus(), 50);
            }

            document.querySelectorAll('[data-modal-trigger="create"]').forEach(btn => {
                btn.addEventListener('click', () => openModal('create'));
            });

            document.querySelectorAll('[data-modal-trigger="edit"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const dataset = btn.getAttribute('data-aplicacao');
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
                openModal('<?= $formMode ?>', <?= json_encode(array_intersect_key($formValues, array_flip(['app_id','app_nome','app_descricao'])), JSON_UNESCAPED_UNICODE); ?>);
            });
            <?php endif; ?>
        })();
    </script>
<?php
safekup_render_footer();
