<?php
require_once __DIR__ . '/bootstrap.php';

$dumpStmt = safekup_db()->query("
    SELECT id, bd_nome_usuario, bd_ip, data_execucao, status, tamanho_arquivo, tempo_decorrido
    FROM historico_dumps
    ORDER BY data_execucao DESC
    LIMIT 8
");
$dumpRows = $dumpStmt->fetchAll(PDO::FETCH_ASSOC);

$restoreStmt = safekup_db()->query("
    SELECT id, bd_nome_usuario, bd_ip, data_execucao, status, tamanho_arquivo, tempo_decorrido
    FROM historico_restores
    ORDER BY data_execucao DESC
    LIMIT 8
");
$restoreRows = $restoreStmt->fetchAll(PDO::FETCH_ASSOC);

safekup_render_header('Safekup — Relatórios', 'relatorios');
?>
    <section class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Relatórios</h2>
                <p class="text-sm text-slate-300">
                    Acompanhe os resultados recentes de dumps e testes de restore. Para análises completas, acesse as telas legadas.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="/php/relatorios/dumps_realizados.php"
                   class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-indigo-400/60 hover:text-white">
                    <i class="fa fa-database"></i>
                    <span>Relatório legado de dumps</span>
                </a>
                <a href="/php/relatorios/restores_realizados.php"
                   class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-indigo-400/60 hover:text-white">
                    <i class="fa fa-undo"></i>
                    <span>Relatório legado de restores</span>
                </a>
            </div>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <div class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/60 p-6 shadow-xl shadow-indigo-900/20">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Últimos dumps</h3>
                <a href="/php/relatorios/dumps_realizados.php" class="text-xs font-semibold text-indigo-300 hover:text-indigo-200">Ver completo</a>
            </div>
            <div class="overflow-hidden rounded-xl border border-white/5">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/5 text-slate-300">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Banco</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Execução</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Tempo</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Tamanho</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-200">
                        <?php if (empty($dumpRows)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-400">
                                    Nenhum dump registrado ainda.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dumpRows as $row): ?>
                                <tr class="hover:bg-slate-800/50">
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-medium"><?= safekup_escape($row['bd_nome_usuario']); ?></span>
                                            <span class="text-xs text-slate-400 font-mono"><?= safekup_escape($row['bd_ip']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3"><?= safekup_escape(safekup_format_datetime($row['data_execucao'])); ?></td>
                                    <?php
                                        $statusRaw = (string) ($row['status'] ?? '');
                                        $isOk = in_array(strtoupper($statusRaw), ['SUCESSO', 'OK'], true);
                                        $statusLabel = $isOk ? 'OK' : ($statusRaw !== '' ? $statusRaw : 'Indefinido');
                                    ?>
                                    <td class="px-4 py-3">
                                        <?= safekup_badge(
                                            $statusLabel,
                                            $isOk
                                                ? 'success'
                                                : 'danger'
                                        ); ?>
                                    </td>
                                    <td class="px-4 py-3"><?= safekup_escape($row['tempo_decorrido'] ?? '-'); ?></td>
                                    <td class="px-4 py-3"><?= safekup_escape(safekup_format_size($row['tamanho_arquivo'] ?? null)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/60 p-6 shadow-xl shadow-indigo-900/20">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Últimos restores</h3>
                <a href="/php/relatorios/restores_realizados.php" class="text-xs font-semibold text-indigo-300 hover:text-indigo-200">Ver completo</a>
            </div>
            <div class="overflow-hidden rounded-xl border border-white/5">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/5 text-slate-300">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Banco</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Execução</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Tempo</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Tamanho</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-200">
                        <?php if (empty($restoreRows)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-400">
                                    Nenhum restore registrado ainda.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($restoreRows as $row): ?>
                                <tr class="hover:bg-slate-800/50">
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-medium"><?= safekup_escape($row['bd_nome_usuario']); ?></span>
                                            <span class="text-xs text-slate-400 font-mono"><?= safekup_escape($row['bd_ip']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3"><?= safekup_escape(safekup_format_datetime($row['data_execucao'])); ?></td>
                                    <?php
                                        $restoreStatusRaw = (string) ($row['status'] ?? '');
                                        $restoreOk = in_array(strtoupper($restoreStatusRaw), ['SUCESSO', 'OK'], true);
                                        $restoreLabel = $restoreOk ? 'OK' : ($restoreStatusRaw !== '' ? $restoreStatusRaw : 'Indefinido');
                                    ?>
                                    <td class="px-4 py-3">
                                        <?= safekup_badge(
                                            $restoreLabel,
                                            $restoreOk
                                                ? 'success'
                                                : 'danger'
                                        ); ?>
                                    </td>
                                    <td class="px-4 py-3"><?= safekup_escape($row['tempo_decorrido'] ?? '-'); ?></td>
                                    <td class="px-4 py-3"><?= safekup_escape(safekup_format_size($row['tamanho_arquivo'] ?? null)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php
safekup_render_footer();
