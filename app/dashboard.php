<?php
require_once __DIR__ . '/bootstrap.php';

safekup_render_header('Safekup — Painel', 'dashboard');
?>
    <section class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <h2 class="text-xl font-semibold">Visão geral</h2>
        <p class="mt-2 text-slate-300">
            Bem-vindo ao novo painel do Safekup. Aqui você terá uma visão condensada dos seus ambientes,
            agendamentos e status de backup conforme migrarmos as funcionalidades para a nova interface.
        </p>
    </section>

    <section class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 shadow-xl shadow-indigo-900/20">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Menu principal</h2>
                <p class="text-sm text-slate-300">Escolha uma área para continuar usando os módulos já disponíveis.</p>
            </div>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <?php
            $cards = [
                ['key' => 'restore', 'icon' => 'fa-refresh', 'title' => 'Servidor de Restore', 'desc' => 'Gerencie restaurações e monitore status de máquinas de recuperação.'],
                ['key' => 'servidores', 'icon' => 'fa-server', 'title' => 'Servidores Backup', 'desc' => 'Cadastre hosts de backup e acompanhe as credenciais utilizadas.'],
                ['key' => 'tipos', 'icon' => 'fa-th', 'title' => 'Tipos de Banco', 'desc' => 'Configure os tipos suportados e mantenha os templates atualizados.'],
                ['key' => 'aplicacoes', 'icon' => 'fa-cogs', 'title' => 'Aplicações', 'desc' => 'Relacione aplicações aos bancos e mantenha a documentação.'],
                ['key' => 'ssh', 'icon' => 'fa-link', 'title' => 'SSH', 'desc' => 'Gerencie chaves e conexões seguras reutilizadas pelos processos.'],
                ['key' => 'bancos', 'icon' => 'fa-database', 'title' => 'Bancos de Dados', 'desc' => 'Cadastre instâncias, agendas e parâmetros de dump para cada base.'],
                ['key' => 'usuarios', 'icon' => 'fa-users', 'title' => 'Usuários', 'desc' => 'Consulte perfis cadastrados e bloqueie acessos quando necessário.'],
                ['key' => 'relatorios', 'icon' => 'fa-bar-chart', 'title' => 'Relatórios', 'desc' => 'Visualize dumps realizados, falhas e restaurações executadas.'],
            ];
            $menuIndex = [];
            foreach (safekup_menu_items() as $item) {
                $menuIndex[$item['key']] = $item['href'];
            }
            foreach ($cards as $card):
                $href = $menuIndex[$card['key']] ?? '#';
            ?>
                <a href="<?= safekup_escape($href); ?>"
                    class="group flex flex-col gap-3 rounded-2xl border border-white/10 bg-slate-900/80 p-5 transition hover:-translate-y-1 hover:border-indigo-400/60 hover:bg-slate-900 hover:shadow-2xl hover:shadow-indigo-900/40">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-500/15 text-indigo-300">
                        <i class="fa <?= safekup_escape($card['icon']); ?>"></i>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-white"><?= safekup_escape($card['title']); ?></h3>
                        <p class="text-sm text-slate-300"><?= safekup_escape($card['desc']); ?></p>
                    </div>
                    <span class="mt-auto text-xs font-semibold uppercase tracking-wide text-indigo-300 group-hover:text-indigo-200">Acessar</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="grid gap-6 md:grid-cols-3">
        <article
            class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 shadow-lg shadow-indigo-900/10 transition hover:-translate-y-1 hover:border-indigo-400/60 hover:shadow-indigo-900/30">
            <div class="flex items-center justify-between text-sm text-indigo-300">
                <span class="uppercase tracking-wide">Status</span>
                <i class="fa fa-shield"></i>
            </div>
            <h3 class="mt-4 text-2xl font-semibold">Backups ativos</h3>
            <p class="mt-3 text-sm text-slate-300">
                Em breve você acompanhará aqui os resultados mais recentes, falhas e próximas execuções.
            </p>
        </article>

        <article
            class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 shadow-lg shadow-indigo-900/10 transition hover:-translate-y-1 hover:border-indigo-400/60 hover:shadow-indigo-900/30">
            <div class="flex items-center justify-between text-sm text-indigo-300">
                <span class="uppercase tracking-wide">Configurações</span>
                <i class="fa fa-server"></i>
            </div>
            <h3 class="mt-4 text-2xl font-semibold">Infraestrutura</h3>
            <p class="mt-3 text-sm text-slate-300">
                Continue administrando servidores, bancos e integrações pelos módulos modernizados.
            </p>
        </article>

        <article
            class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 shadow-lg shadow-indigo-900/10 transition hover:-translate-y-1 hover:border-indigo-400/60 hover:shadow-indigo-900/30">
            <div class="flex items-center justify-between text-sm text-indigo-300">
                <span class="uppercase tracking-wide">Roadmap</span>
                <i class="fa fa-road"></i>
            </div>
            <h3 class="mt-4 text-2xl font-semibold">Transição em andamento</h3>
            <p class="mt-3 text-sm text-slate-300">
                Novas telas seguem sendo liberadas gradualmente. Compartilhe feedbacks para priorizarmos o que é mais importante.
            </p>
        </article>
    </section>
<?php
safekup_render_footer();
