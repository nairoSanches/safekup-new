<?php
require_once __DIR__ . '/bootstrap.php';

$pdo = safekup_db();

$rangeOptions = [
    14 => '14 dias',
    7  => '7 dias',
    1  => 'Hoje',
];
$defaultRange = 1;
$selectedRange = isset($_GET['range']) ? (int) $_GET['range'] : $defaultRange;
if (!array_key_exists($selectedRange, $rangeOptions)) {
    $selectedRange = $defaultRange;
}
$rangeDays = $selectedRange;

$dailyStats = [];
$dailyLabels = [];
$dailyTotals = [];
$dailySuccess = [];
$dailyFailures = [];
$dailyVolumeMb = [];
$dailyVolumeBytes = [];
$totalExecutions = 0;
$successExecutions = 0;
$failureExecutions = 0;
$totalVolumeBytes = 0;
$successRate = null;
$failureRate = null;
$latestDump = null;
$statsError = null;
$periodRangeDescription = '';

try {
    $today = new DateTimeImmutable('today');
    $startDateObj = $rangeDays > 1
        ? $today->sub(new DateInterval('P' . ($rangeDays - 1) . 'D'))
        : $today;
    $startDateParam = $rangeDays === 1
        ? $startDateObj->format('Y-m-d')
        : $startDateObj->format('Y-m-d 00:00:00');
    $periodRangeDescription = $rangeDays === 1
        ? $today->format('d/m')
        : sprintf('%s — %s', $startDateObj->format('d/m'), $today->format('d/m'));

    if ($rangeDays === 1) {
        $bucketSelect = "DATE_FORMAT(h.data_execucao, '%Y-%m-%d %H:00:00') AS bucket_ts";
        $groupByExpression = "DATE_FORMAT(h.data_execucao, '%Y-%m-%d %H')";
        $whereClause = "DATE(h.data_execucao) = :startDate";
    } else {
        $bucketSelect = "DATE(h.data_execucao) AS bucket_ts";
        $groupByExpression = "DATE(h.data_execucao)";
        $whereClause = "h.data_execucao >= :startDate";
    }

    $dailyStatsStmt = $pdo->prepare("
        SELECT
            {$bucketSelect},
            COUNT(*) AS total,
            SUM(CASE WHEN h.status = 'OK' THEN 1 ELSE 0 END) AS success_count,
            SUM(CASE WHEN h.status = 'OK' THEN 0 ELSE 1 END) AS failure_count,
            SUM(COALESCE(h.tamanho_arquivo, 0)) AS total_size_bytes
        FROM historico_dumps h
        WHERE {$whereClause}
        GROUP BY {$groupByExpression}
        ORDER BY {$groupByExpression}
    ");
    $dailyStatsStmt->execute(['startDate' => $startDateParam]);
    $dailyStats = $dailyStatsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dailyStats as $row) {
        $bucketDate = new DateTimeImmutable($row['bucket_ts']);
        $dailyLabels[] = $rangeDays === 1 ? $bucketDate->format('H\h') : $bucketDate->format('d/m');
        $dailyTotals[] = (int) $row['total'];
        $dailySuccess[] = (int) $row['success_count'];
        $dailyFailures[] = (int) $row['failure_count'];
        $dailyVolumeBytes[] = (float) $row['total_size_bytes'];
        $dailyVolumeMb[] = round(((float) $row['total_size_bytes']) / 1048576, 2);
    }

    $totalExecutions = array_sum($dailyTotals);
    $successExecutions = array_sum($dailySuccess);
    $failureExecutions = array_sum($dailyFailures);
    $totalVolumeBytes = array_sum($dailyVolumeBytes);
    $successRate = $totalExecutions > 0 ? round(($successExecutions / $totalExecutions) * 100, 1) : null;
    $failureRate = $totalExecutions > 0 ? round(($failureExecutions / $totalExecutions) * 100, 1) : null;

    $latestDumpStmt = $pdo->query("
        SELECT h.data_execucao, h.status, h.bd_nome_usuario
        FROM historico_dumps h
        ORDER BY h.data_execucao DESC
        LIMIT 1
    ");
    $latestDump = $latestDumpStmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $exception) {
    error_log('Erro ao carregar métricas do dashboard: ' . $exception->getMessage());
    $statsError = 'Não foi possível carregar as métricas dos dumps recentes.';
    $dailyLabels = [];
    $dailyTotals = [];
    $dailySuccess = [];
    $dailyFailures = [];
    $dailyVolumeMb = [];
    $dailyVolumeBytes = [];
    $totalExecutions = 0;
    $successExecutions = 0;
    $failureExecutions = 0;
    $totalVolumeBytes = 0;
    $successRate = null;
    $failureRate = null;
    $latestDump = null;
    $periodRangeDescription = '—';
}

$successRateText = $successRate !== null ? number_format($successRate, 1, ',', '.') . '%' : '—';
$failureRateText = $failureRate !== null ? number_format($failureRate, 1, ',', '.') . '%' : '—';
$formattedVolume = safekup_format_size($totalVolumeBytes);
$hasDailyData = !empty($dailyLabels);
$periodLabel = $rangeDays === 1 ? 'Hoje' : "Últimos {$rangeDays} dias";
$periodTitle = $rangeDays === 1 ? 'Atividade de hoje' : "Atividade dos últimos {$rangeDays} dias";
$executionsChartTitle = $rangeDays === 1 ? 'Execuções totais por hora' : 'Execuções totais por dia';
$executionsChartSubtitle = $rangeDays === 1
    ? 'Visualize a distribuição das execuções ao longo do dia selecionado.'
    : 'Visualize a tendência diária de dumps realizados.';
$statusChartTitle = $rangeDays === 1 ? 'Sucesso x falha por hora' : 'Sucesso x falha por dia';
$statusChartSubtitle = $rangeDays === 1
    ? 'Comparativo horário entre execuções concluídas e com problemas.'
    : 'Comparativo diário entre execuções concluídas e com problemas.';
$volumeChartTitle = $rangeDays === 1 ? 'Volume gerado por hora (MB)' : 'Volume gerado por dia (MB)';
$volumeChartSubtitle = $rangeDays === 1
    ? 'Soma horária do tamanho dos arquivos produzidos pelos dumps.'
    : 'Soma diária do tamanho dos arquivos produzidos pelos dumps.';

safekup_render_header('Safekup — Painel', 'dashboard');
?>
    <section class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <h2 class="text-xl font-semibold">Visão geral</h2>
        <p class="mt-2 text-slate-300">
            Bem-vindo ao novo painel do Safekup. Aqui você terá uma visão condensada dos seus ambientes,
            agendamentos e status de backup conforme migrarmos as funcionalidades para a nova interface.
        </p>
    </section>

    <section class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/30">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <h2 class="text-xl font-semibold"><?= safekup_escape($periodTitle); ?></h2>
                <p class="text-sm text-slate-300">
                    Consolidação diária das execuções registradas no <em>historico_dumps</em>, incluindo volume gerado
                    e percentual de sucesso.
                </p>
                <p class="text-xs uppercase tracking-wide text-slate-500">
                    Período: <?= safekup_escape($periodRangeDescription); ?>
                </p>
            </div>
            <form method="get" class="flex flex-wrap gap-2">
                <?php foreach ($rangeOptions as $value => $label): ?>
                    <?php $isActive = $value === $rangeDays; ?>
                    <button type="submit"
                            name="range"
                            value="<?= (int) $value; ?>"
                            class="rounded-full border px-4 py-2 text-sm font-semibold transition <?= $isActive
                                ? 'border-indigo-400/70 bg-indigo-500/30 text-white shadow-lg shadow-indigo-900/30'
                                : 'border-white/10 bg-slate-900/60 text-slate-300 hover:border-indigo-300/60 hover:text-white'; ?>">
                        <?= safekup_escape($label); ?>
                    </button>
                <?php endforeach; ?>
                <?php if (!empty($_GET) && count($_GET) > 1): ?>
                    <?php foreach ($_GET as $key => $value): ?>
                        <?php if ($key !== 'range'): ?>
                            <input type="hidden" name="<?= safekup_escape($key); ?>" value="<?= safekup_escape((string) $value); ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </form>
            <?php if ($latestDump): ?>
                <div class="flex flex-col gap-1 rounded-2xl border border-white/10 bg-slate-900/80 px-5 py-4 text-sm text-slate-200">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Última execução</span>
                    <span class="text-lg font-semibold text-white">
                        <?= safekup_escape($latestDump['bd_nome_usuario'] ?? ''); ?>
                    </span>
                    <span class="text-slate-300">
                        <?= safekup_format_datetime($latestDump['data_execucao'] ?? null); ?>
                    </span>
                    <span>
                        <?= safekup_badge(
                            ($latestDump['status'] ?? '-') ?: '-',
                            ($latestDump['status'] ?? '') === 'OK' ? 'success' : 'danger'
                        ); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($statsError !== null): ?>
            <div class="mt-6 rounded-xl border border-pink-500/40 bg-pink-500/10 p-4 text-sm text-pink-100">
                <?= safekup_escape($statsError); ?>
            </div>
        <?php else: ?>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-white/10 bg-slate-900/75 p-5 shadow-inner shadow-indigo-900/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Execuções</p>
                    <p class="mt-2 text-3xl font-bold text-white">
                        <?= number_format($totalExecutions, 0, ',', '.'); ?>
                    </p>
                    <p class="mt-1 text-xs text-slate-400"><?= safekup_escape($periodLabel); ?></p>
                    <p class="mt-3 text-sm text-slate-300">Taxa de sucesso: <?= $successRateText; ?></p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-900/75 p-5 shadow-inner shadow-green-900/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-green-300">Sucesso</p>
                    <p class="mt-2 text-3xl font-bold text-green-100">
                        <?= number_format($successExecutions, 0, ',', '.'); ?>
                    </p>
                    <p class="mt-1 text-xs text-green-300/80">Participação: <?= $successRateText; ?></p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-900/75 p-5 shadow-inner shadow-pink-900/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-pink-300">Falhas</p>
                    <p class="mt-2 text-3xl font-bold text-pink-100">
                        <?= number_format($failureExecutions, 0, ',', '.'); ?>
                    </p>
                    <p class="mt-1 text-xs text-pink-300/80">Participação: <?= $failureRateText ?? '—'; ?></p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-900/75 p-5 shadow-inner shadow-sky-900/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-300">Volume gerado</p>
                    <p class="mt-2 text-3xl font-bold text-sky-100">
                        <?= safekup_escape($formattedVolume); ?>
                    </p>
                    <p class="mt-1 text-xs text-slate-400">Soma de arquivos exportados</p>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($statsError === null): ?>
        <section class="grid gap-6 xl:grid-cols-2">
            <article
                class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-xl shadow-indigo-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white"><?= safekup_escape($executionsChartTitle); ?></h3>
                        <p class="text-sm text-slate-300"><?= safekup_escape($executionsChartSubtitle); ?></p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-indigo-300"><?= safekup_escape($periodLabel); ?></span>
                </div>
                <div class="mt-6">
                    <?php if ($hasDailyData): ?>
                        <canvas id="dailyExecutionsChart" height="220"></canvas>
                    <?php else: ?>
                        <p class="text-sm text-slate-400">Nenhum registro encontrado no período informado.</p>
                    <?php endif; ?>
                </div>
            </article>

            <article
                class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-xl shadow-indigo-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white"><?= safekup_escape($statusChartTitle); ?></h3>
                        <p class="text-sm text-slate-300"><?= safekup_escape($statusChartSubtitle); ?></p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-indigo-300"><?= safekup_escape($periodLabel); ?></span>
                </div>
                <div class="mt-6">
                    <?php if ($hasDailyData): ?>
                        <canvas id="dailyStatusChart" height="220"></canvas>
                    <?php else: ?>
                        <p class="text-sm text-slate-400">Nenhum registro encontrado no período informado.</p>
                    <?php endif; ?>
                </div>
            </article>

            <article
                class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-xl shadow-indigo-900/20 xl:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white"><?= safekup_escape($volumeChartTitle); ?></h3>
                        <p class="text-sm text-slate-300"><?= safekup_escape($volumeChartSubtitle); ?></p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-indigo-300"><?= safekup_escape($periodLabel); ?></span>
                </div>
                <div class="mt-6">
                    <?php if ($hasDailyData): ?>
                        <canvas id="dailyVolumeChart" height="220"></canvas>
                    <?php else: ?>
                        <p class="text-sm text-slate-400">Nenhum registro encontrado no período informado.</p>
                    <?php endif; ?>
                </div>
            </article>
        </section>
    <?php endif; ?>

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
<?php if ($statsError === null && $hasDailyData): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        const dailyLabels = <?= json_encode($dailyLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const dailyTotals = <?= json_encode($dailyTotals, JSON_NUMERIC_CHECK); ?>;
        const dailySuccess = <?= json_encode($dailySuccess, JSON_NUMERIC_CHECK); ?>;
        const dailyFailures = <?= json_encode($dailyFailures, JSON_NUMERIC_CHECK); ?>;
        const dailyVolume = <?= json_encode($dailyVolumeMb, JSON_NUMERIC_CHECK); ?>;

        const chartColors = {
            totals: 'rgba(129, 140, 248, 0.85)',
            totalsFill: 'rgba(129, 140, 248, 0.18)',
            success: 'rgba(74, 222, 128, 0.85)',
            successFill: 'rgba(74, 222, 128, 0.22)',
            failure: 'rgba(248, 113, 113, 0.85)',
            failureFill: 'rgba(248, 113, 113, 0.22)',
            volume: 'rgba(56, 189, 248, 0.85)',
            volumeFill: 'rgba(56, 189, 248, 0.18)',
        };

        Chart.defaults.color = '#cbd5f5';
        Chart.defaults.borderColor = 'rgba(148, 163, 184, 0.2)';
        Chart.defaults.font.family = 'Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';

        const commonScales = {
            x: {
                ticks: { color: '#cbd5f5' },
                grid: { color: 'rgba(148, 163, 184, 0.12)' }
            },
            y: {
                beginAtZero: true,
                ticks: { color: '#cbd5f5' },
                grid: { color: 'rgba(148, 163, 184, 0.12)' }
            }
        };

        const totalsCtx = document.getElementById('dailyExecutionsChart');
        if (totalsCtx) {
            new Chart(totalsCtx, {
                type: 'line',
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: 'Execuções',
                        data: dailyTotals,
                        borderColor: chartColors.totals,
                        backgroundColor: chartColors.totalsFill,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: chartColors.totals
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: commonScales,
                    plugins: {
                        legend: { labels: { color: '#e2e8f0' } },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.92)',
                            callbacks: {
                                label: context => ` ${context.parsed.y ?? 0} execuções`
                            }
                        }
                    }
                }
            });
        }

        const statusCtx = document.getElementById('dailyStatusChart');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'bar',
                data: {
                    labels: dailyLabels,
                    datasets: [
                        {
                            label: 'Sucesso',
                            data: dailySuccess,
                            backgroundColor: chartColors.success,
                            stack: 'status'
                        },
                        {
                            label: 'Falha',
                            data: dailyFailures,
                            backgroundColor: chartColors.failure,
                            stack: 'status'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { ...commonScales.x, stacked: true },
                        y: { ...commonScales.y, stacked: true }
                    },
                    plugins: {
                        legend: { labels: { color: '#e2e8f0' } },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.92)',
                            callbacks: {
                                label: context => ` ${context.dataset.label}: ${context.parsed.y ?? 0}`
                            }
                        }
                    }
                }
            });
        }

        const volumeCtx = document.getElementById('dailyVolumeChart');
        if (volumeCtx) {
            new Chart(volumeCtx, {
                type: 'line',
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: 'Volume (MB)',
                        data: dailyVolume,
                        borderColor: chartColors.volume,
                        backgroundColor: chartColors.volumeFill,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: chartColors.volume
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: commonScales,
                    plugins: {
                        legend: { labels: { color: '#e2e8f0' } },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.92)',
                            callbacks: {
                                label: context => {
                                    const value = context.parsed.y ?? 0;
                                    return ` ${value.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} MB`;
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
<?php endif; ?>
<?php
safekup_render_footer();
