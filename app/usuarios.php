<?php
require_once __DIR__ . '/bootstrap.php';

$stmt = safekup_db()->query('SELECT usuario_id, usuario_nome, usuario_login, usuario_status, usuario_id_app, usuario_email FROM usuario ORDER BY usuario_nome');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

safekup_render_header('Safekup — Usuários', 'usuarios');
?>
    <section class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Usuários</h2>
                <p class="text-sm text-slate-300">
                    Contas que acessam o Safekup via autenticação LDAP, com opção de bloqueio e definição de perfis.
                </p>
            </div>
            <a href="/php/usuarios/cadastro_usuario.php"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-500/90 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-400">
                <i class="fa fa-plus"></i>
                <span>Novo cadastro (legado)</span>
            </a>
        </div>

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
                            <td colspan="7" class="px-4 py-6 text-center text-slate-400">
                                Nenhum usuário cadastrado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
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
                                        <a href="/php/usuarios/alterar_usuario.php?usuario_id=<?= safekup_escape((string)$row['usuario_id']); ?>"
                                           class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-100 transition hover:bg-slate-700">
                                            <i class="fa fa-pencil"></i>
                                            <span>Editar (legado)</span>
                                        </a>
                                        <a href="/php/usuarios/usuarios.php"
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
