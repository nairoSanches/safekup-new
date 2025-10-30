<?php
require_once __DIR__ . '/bootstrap.php';

$sql = "
    SELECT 
        A.bd_id,
        A.bd_nome_usuario,
        A.bd_hora_backup,
        A.bd_ip,
        A.bd_data_cadastro,
        A.bd_backup_ativo,
        A.bd_container,
        B.tipo_nome,
        C.app_nome
    FROM db_management A
    JOIN tipo B ON A.bd_tipo = B.tipo_id
    LEFT JOIN aplicacao C ON A.bd_app = C.app_id
    ORDER BY A.bd_nome_usuario
";
$stmt = safekup_db()->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <a href="/php/database-management/cadastro_bd.php"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-500/90 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-400">
                <i class="fa fa-plus"></i>
                <span>Novo cadastro (legado)</span>
            </a>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-900/60">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Banco</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">SGBD</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Aplicação</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">IP</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Horário</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Ativo</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Cadastrado em</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-400">
                                Nenhum banco de dados cadastrado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr class="hover:bg-slate-800/50">
                                <td class="px-4 py-3 font-medium">
                                    <div class="flex items-center gap-3">
                                        <?= safekup_escape($row['bd_nome_usuario']); ?>
                                        <?php if (!empty($row['bd_container'])): ?>
                                            <?= safekup_badge('Container', 'info'); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><?= safekup_escape($row['tipo_nome']); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape($row['app_nome'] ?? '-'); ?></td>
                                <td class="px-4 py-3 font-mono text-sm"><?= safekup_escape($row['bd_ip']); ?></td>
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
                                        <a href="/php/database-management/altera_bd.php?bd_id=<?= safekup_escape((string)$row['bd_id']); ?>"
                                           class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-100 transition hover:bg-slate-700">
                                            <i class="fa fa-pencil"></i>
                                            <span>Editar (legado)</span>
                                        </a>
                                        <a href="/php/database-management/db_management.php"
                                           class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:border-indigo-400/60 hover:text-white">
                                            <i class="fa fa-external-link"></i>
                                            <span>Ver tela antiga</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php
safekup_render_footer();
