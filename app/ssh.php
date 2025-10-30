<?php
require_once __DIR__ . '/bootstrap.php';

$stmt = safekup_db()->query('SELECT ssh_id, ssh_ip, ssh_user FROM ssh ORDER BY ssh_id');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <a href="/php/ssh/cadastro_ssh.php"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-500/90 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-400">
                <i class="fa fa-plus"></i>
                <span>Novo cadastro (legado)</span>
            </a>
        </div>

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/60">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Host/IP</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Usuário</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Porta</th>
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
                                <td class="px-4 py-3">22</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="/php/ssh/alterar_ssh.php?ssh_id=<?= safekup_escape((string)$row['ssh_id']); ?>"
                                           class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-100 transition hover:bg-slate-700">
                                            <i class="fa fa-pencil"></i>
                                            <span>Editar (legado)</span>
                                        </a>
                                        <a href="/php/ssh/ssh.php"
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
