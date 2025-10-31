<?php
require_once __DIR__ . '/bootstrap.php';

$db = safekup_db();

$formMode = 'create';
$formValues = [
    'tipo_id' => null,
    'tipo_nome' => '',
    'tipo_plataforma' => '',
];
$formErrors = [];
$openModal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formMode = $_POST['modal_mode'] === 'edit' ? 'edit' : 'create';
    $formValues['tipo_id'] = isset($_POST['tipo_id']) ? (int)$_POST['tipo_id'] : null;
    $formValues['tipo_nome'] = trim($_POST['tipo_nome'] ?? '');
    $formValues['tipo_plataforma'] = trim($_POST['tipo_plataforma'] ?? '');

    if ($formValues['tipo_nome'] === '') {
        $formErrors['tipo_nome'] = 'Informe o nome do tipo.';
    }
    if (!in_array($formValues['tipo_plataforma'], ['Linux', 'Windows'], true)) {
        $formErrors['tipo_plataforma'] = 'Selecione a plataforma.';
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $exists = $db->prepare('SELECT COUNT(*) FROM tipo WHERE tipo_nome = :nome');
            $exists->execute([':nome' => $formValues['tipo_nome']]);
            if ($exists->fetchColumn() > 0) {
                $formErrors['tipo_nome'] = 'Já existe um tipo com esse nome.';
            }
        } else {
            if (!$formValues['tipo_id']) {
                $formErrors['tipo_id'] = 'Registro inválido.';
            } else {
                $exists = $db->prepare('SELECT COUNT(*) FROM tipo WHERE tipo_nome = :nome AND tipo_id <> :id');
                $exists->execute([':nome' => $formValues['tipo_nome'], ':id' => $formValues['tipo_id']]);
                if ($exists->fetchColumn() > 0) {
                    $formErrors['tipo_nome'] = 'Já existe um tipo com esse nome.';
                }
            }
        }
    }

    if (empty($formErrors)) {
        if ($formMode === 'create') {
            $insert = $db->prepare('INSERT INTO tipo (tipo_nome, tipo_plataforma) VALUES (:nome, :plataforma)');
            $insert->execute([
                ':nome' => $formValues['tipo_nome'],
                ':plataforma' => $formValues['tipo_plataforma'],
            ]);
            header('Location: /app/tipos.php?status=created');
            exit;
        }

        if ($formMode === 'edit' && $formValues['tipo_id']) {
            $update = $db->prepare('UPDATE tipo SET tipo_nome = :nome, tipo_plataforma = :plataforma WHERE tipo_id = :id');
            $update->execute([
                ':nome' => $formValues['tipo_nome'],
                ':plataforma' => $formValues['tipo_plataforma'],
                ':id' => $formValues['tipo_id'],
            ]);
            header('Location: /app/tipos.php?status=updated');
            exit;
        }
    } else {
        $openModal = true;
    }
}

$stmt = $db->query('SELECT tipo_id, tipo_nome, tipo_plataforma FROM tipo ORDER BY tipo_nome');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status = $_GET['status'] ?? '';
$alerts = [
    'created' => ['type' => 'success', 'message' => 'Tipo cadastrado com sucesso.'],
    'updated' => ['type' => 'success', 'message' => 'Tipo atualizado com sucesso.'],
    'notfound' => ['type' => 'danger', 'message' => 'Tipo não encontrado.'],
];
$currentAlert = $alerts[$status] ?? null;

safekup_render_header('Safekup — Tipos de Banco', 'tipos');
?>
    <section class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Tipos de Banco de Dados</h2>
                <p class="text-sm text-slate-300">
                    Catálogo de SGBDs suportados e suas plataformas.
                </p>
            </div>
            <button type="button" data-modal-trigger="create"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-500/90 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-400">
                <i class="fa fa-plus"></i>
                <span>Novo tipo</span>
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
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">ID</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Tipo</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Plataforma</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-400">
                                Nenhum tipo cadastrado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr class="hover:bg-slate-800/50">
                                <td class="px-4 py-3 font-medium">#<?= safekup_escape((string)$row['tipo_id']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['tipo_nome']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['tipo_plataforma']); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button"
                                           data-modal-trigger="edit"
                                           data-tipo='<?= safekup_escape(json_encode([
                                                'tipo_id' => (int)$row['tipo_id'],
                                                'tipo_nome' => $row['tipo_nome'],
                                                'tipo_plataforma' => $row['tipo_plataforma'],
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

    <div id="tipoModal" class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/70 px-4 py-8">
        <div class="w-full max-w-xl overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl shadow-indigo-900/30">
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                <div>
                    <h2 id="tipoModalTitle" class="text-lg font-semibold text-white">Novo tipo</h2>
                    <p class="text-xs text-slate-400">Cadastre os tipos de banco suportados pelo Safekup.</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" data-modal-close>
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <form method="post" class="grid gap-4 px-6 py-5">
                <input type="hidden" name="modal_mode" id="tipo_modal_mode" value="<?= safekup_escape($formMode); ?>">
                <input type="hidden" name="tipo_id" id="tipo_id" value="<?= safekup_escape((string)($formValues['tipo_id'] ?? '')); ?>">

                <label class="flex flex-col gap-2 text-sm">
                    <span class="text-slate-200">Nome do tipo</span>
                    <input name="tipo_nome" id="tipo_nome" value="<?= safekup_escape($formValues['tipo_nome']); ?>"
                           class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    <?php if (isset($formErrors['tipo_nome'])): ?>
                        <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['tipo_nome']); ?></span>
                    <?php endif; ?>
                </label>

                <label class="flex flex-col gap-2 text-sm">
                    <span class="text-slate-200">Plataforma</span>
                    <select name="tipo_plataforma" id="tipo_plataforma"
                            class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Selecione</option>
                        <option value="Linux" <?= $formValues['tipo_plataforma'] === 'Linux' ? 'selected' : ''; ?>>Linux</option>
                        <option value="Windows" <?= $formValues['tipo_plataforma'] === 'Windows' ? 'selected' : ''; ?>>Windows</option>
                    </select>
                    <?php if (isset($formErrors['tipo_plataforma'])): ?>
                        <span class="text-xs text-pink-400"><?= safekup_escape($formErrors['tipo_plataforma']); ?></span>
                    <?php endif; ?>
                </label>

                <div class="flex justify-end gap-3 border-t border-white/10 pt-4">
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-indigo-400/60 hover:text-white" data-modal-close>
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        <i class="fa fa-save"></i>
                        <span id="tipoModalSubmit">Salvar tipo</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const modal = document.getElementById('tipoModal');
            const modeField = document.getElementById('tipo_modal_mode');
            const idField = document.getElementById('tipo_id');
            const nomeField = document.getElementById('tipo_nome');
            const plataformaField = document.getElementById('tipo_plataforma');
            const titulo = document.getElementById('tipoModalTitle');
            const submitLabel = document.getElementById('tipoModalSubmit');

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
                    titulo.textContent = 'Editar tipo de banco';
                    submitLabel.textContent = 'Salvar alterações';
                } else {
                    titulo.textContent = 'Cadastrar tipo de banco';
                    submitLabel.textContent = 'Salvar tipo';
                }

                idField.value = data?.tipo_id ?? '';
                nomeField.value = data?.tipo_nome ?? '';
                plataformaField.value = data?.tipo_plataforma ?? '';

                toggleModal(true);
                setTimeout(() => nomeField.focus(), 50);
            }

            document.querySelectorAll('[data-modal-trigger="create"]').forEach(btn => {
                btn.addEventListener('click', () => openModal('create'));
            });

            document.querySelectorAll('[data-modal-trigger="edit"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const dataset = btn.getAttribute('data-tipo');
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
                openModal('<?= $formMode ?>', <?= json_encode(array_intersect_key($formValues, array_flip(['tipo_id','tipo_nome','tipo_plataforma'])), JSON_UNESCAPED_UNICODE); ?>);
            });
            <?php endif; ?>
        })();
    </script>
<?php
safekup_render_footer();
