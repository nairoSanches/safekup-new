<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/dashboard_metrics.php';

$pdo = safekup_db();

$rangeOptions = safekup_dashboard_range_options();
$defaultRange = safekup_dashboard_default_range();
$selectedRange = isset($_GET['range']) ? (int) $_GET['range'] : $defaultRange;

$metrics = safekup_dashboard_metrics($pdo, $selectedRange);
$rangeDays = $metrics['range_days'];
$period = $metrics['period'];
$totals = $metrics['totals'];
$chartTitles = $metrics['chart_titles'];
$latestDump = $metrics['latest_dump'];
$hasDailyData = (bool) $metrics['has_data'];
$statsError = $metrics['error'];

$periodTitle = $period['title'];
$periodLabel = $period['label'];
$periodRangeDescription = $period['description'];
$successRateText = $totals['success_rate_text'];
$failureRateText = $totals['failure_rate_text'];
$formattedVolume = $totals['volume_text'];

$initialMetricsJson = json_encode($metrics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

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
                <h2 class="text-xl font-semibold" data-dashboard="period-title"><?= safekup_escape($periodTitle); ?></h2>
                <p class="text-sm text-slate-300">
                    Consolidação diária das execuções registradas no <em>historico_dumps</em>, incluindo volume gerado
                    e percentual de sucesso.
                </p>
                <p class="text-xs uppercase tracking-wide text-slate-500">
                    Período: <span data-dashboard="period-description"><?= safekup_escape($periodRangeDescription); ?></span>
                </p>
            </div>
            <form method="get" class="flex flex-wrap gap-2" data-dashboard="range-form">
                <?php foreach ($rangeOptions as $value => $label): ?>
                    <?php $isActive = $value === $rangeDays; ?>
                    <button type="submit"
                            name="range"
                            value="<?= (int) $value; ?>"
                            data-dashboard-range-button
                            data-active-classes="border-indigo-400/70 bg-indigo-500/30 text-white shadow-lg shadow-indigo-900/30"
                            data-inactive-classes="border-white/10 bg-slate-900/60 text-slate-300 hover:border-indigo-300/60 hover:text-white"
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
            <div class="flex flex-col gap-1 rounded-2xl border border-white/10 bg-slate-900/80 px-5 py-4 text-sm text-slate-200 <?= $latestDump ? '' : 'hidden'; ?>"
                 data-dashboard="latest-card">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Última execução</span>
                    <span class="text-lg font-semibold text-white" data-dashboard="latest-name">
                        <?= safekup_escape($latestDump['bd_nome_usuario'] ?? ''); ?>
                    </span>
                    <span class="text-slate-300" data-dashboard="latest-datetime">
                        <?= safekup_format_datetime($latestDump['data_execucao'] ?? null); ?>
                    </span>
                    <span data-dashboard="latest-status">
                        <?= $latestDump
                            ? safekup_badge(
                                ($latestDump['status'] ?? '-') ?: '-',
                                ($latestDump['status'] ?? '') === 'OK' ? 'success' : 'danger'
                            )
                            : safekup_badge('-', 'default'); ?>
                    </span>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-pink-500/40 bg-pink-500/10 p-4 text-sm text-pink-100 <?= $statsError ? '' : 'hidden'; ?>"
             data-dashboard="error">
            <span data-dashboard="error-text"><?= safekup_escape($statsError ?? ''); ?></span>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4 <?= $statsError ? 'hidden' : ''; ?>"
             data-dashboard="stats-container">
                <div class="rounded-2xl border border-white/10 bg-slate-900/75 p-5 shadow-inner shadow-indigo-900/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Execuções</p>
                    <p class="mt-2 text-3xl font-bold text-white">
                        <span data-dashboard="total-executions"><?= safekup_escape($totals['executions_text']); ?></span>
                    </p>
                    <p class="mt-1 text-xs text-slate-400" data-dashboard="period-label"><?= safekup_escape($periodLabel); ?></p>
                    <p class="mt-3 text-sm text-slate-300">Taxa de sucesso: <span data-dashboard="success-rate"><?= safekup_escape($successRateText); ?></span></p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-900/75 p-5 shadow-inner shadow-green-900/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-green-300">Sucesso</p>
                    <p class="mt-2 text-3xl font-bold text-green-100">
                        <span data-dashboard="total-success"><?= safekup_escape($totals['success_text']); ?></span>
                    </p>
                    <p class="mt-1 text-xs text-green-300/80">Participação: <span data-dashboard="success-rate"><?= safekup_escape($successRateText); ?></span></p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-900/75 p-5 shadow-inner shadow-pink-900/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-pink-300">Falhas</p>
                    <p class="mt-2 text-3xl font-bold text-pink-100">
                        <span data-dashboard="total-failure"><?= safekup_escape($totals['failure_text']); ?></span>
                    </p>
                    <p class="mt-1 text-xs text-pink-300/80">Participação: <span data-dashboard="failure-rate"><?= safekup_escape($failureRateText); ?></span></p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-900/75 p-5 shadow-inner shadow-sky-900/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-300">Volume gerado</p>
                    <p class="mt-2 text-3xl font-bold text-sky-100">
                        <span data-dashboard="volume-text"><?= safekup_escape($formattedVolume); ?></span>
                    </p>
                    <p class="mt-1 text-xs text-slate-400">Soma de arquivos exportados</p>
                </div>
        </div>
    </section>

        <section class="grid gap-6 xl:grid-cols-2 <?= $statsError ? 'hidden' : ''; ?>" data-dashboard="charts-section">
            <article
                class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-xl shadow-indigo-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white" data-dashboard="chart-executions-title"><?= safekup_escape($chartTitles['executions_title']); ?></h3>
                        <p class="text-sm text-slate-300" data-dashboard="chart-executions-subtitle"><?= safekup_escape($chartTitles['executions_subtitle']); ?></p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-indigo-300" data-dashboard="period-label"><?= safekup_escape($periodLabel); ?></span>
                </div>
                <div class="mt-6">
                    <canvas id="dailyExecutionsChart" height="220" class="<?= $hasDailyData ? '' : 'hidden'; ?>" data-dashboard="chart-executions-canvas"></canvas>
                    <p class="text-sm text-slate-400 <?= $hasDailyData ? 'hidden' : ''; ?>" data-dashboard="chart-executions-empty">Nenhum registro encontrado no período informado.</p>
                </div>
            </article>

            <article
                class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-xl shadow-indigo-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white" data-dashboard="chart-status-title"><?= safekup_escape($chartTitles['status_title']); ?></h3>
                        <p class="text-sm text-slate-300" data-dashboard="chart-status-subtitle"><?= safekup_escape($chartTitles['status_subtitle']); ?></p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-indigo-300" data-dashboard="period-label"><?= safekup_escape($periodLabel); ?></span>
                </div>
                <div class="mt-6">
                    <canvas id="dailyStatusChart" height="220" class="<?= $hasDailyData ? '' : 'hidden'; ?>" data-dashboard="chart-status-canvas"></canvas>
                    <p class="text-sm text-slate-400 <?= $hasDailyData ? 'hidden' : ''; ?>" data-dashboard="chart-status-empty">Nenhum registro encontrado no período informado.</p>
                </div>
            </article>

            <article
                class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-xl shadow-indigo-900/20 xl:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white" data-dashboard="chart-volume-title"><?= safekup_escape($chartTitles['volume_title']); ?></h3>
                        <p class="text-sm text-slate-300" data-dashboard="chart-volume-subtitle"><?= safekup_escape($chartTitles['volume_subtitle']); ?></p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-indigo-300" data-dashboard="period-label"><?= safekup_escape($periodLabel); ?></span>
                </div>
                <div class="mt-6">
                    <canvas id="dailyVolumeChart" height="220" class="<?= $hasDailyData ? '' : 'hidden'; ?>" data-dashboard="chart-volume-canvas"></canvas>
                    <p class="text-sm text-slate-400 <?= $hasDailyData ? 'hidden' : ''; ?>" data-dashboard="chart-volume-empty">Nenhum registro encontrado no período informado.</p>
                </div>
            </article>
        </section>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            const state = {
                range: <?= (int) $rangeDays; ?>,
                data: <?= $initialMetricsJson ?: '{}'; ?>,
                refreshMs: 60000,
                timer: null,
                charts: {
                    executions: null,
                    status: null,
                    volume: null
                },
                endpoints: {
                    metrics: '/app/api/dashboard_metrics.php'
                }
            };

            const defaultStatusBadge = <?= json_encode(safekup_badge('-', 'default')); ?>;

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

            const selectors = {
                rangeForm: '[data-dashboard="range-form"]',
                rangeButtons: '[data-dashboard-range-button]',
                periodLabel: '[data-dashboard="period-label"]',
            };

            function elements(selector) {
                return Array.from(document.querySelectorAll(selector));
            }

            function setText(key, value) {
                elements(`[data-dashboard="${key}"]`).forEach((el) => {
                    if (value === null || typeof value === 'undefined' || value === '') {
                        el.textContent = '—';
                    } else {
                        el.textContent = value;
                    }
                });
            }

            function setHtml(key, value) {
                elements(`[data-dashboard="${key}"]`).forEach((el) => {
                    el.innerHTML = value;
                });
            }

            function toggleVisibility(selector, visible) {
                elements(selector).forEach((el) => {
                    el.classList.toggle('hidden', !visible);
                });
            }

            function updateRangeButtons() {
                const buttons = elements(selectors.rangeButtons);
                buttons.forEach((button) => {
                    const isActive = Number(button.value) === Number(state.range);
                    const activeClasses = (button.dataset.activeClasses || '').split(' ').filter(Boolean);
                    const inactiveClasses = (button.dataset.inactiveClasses || '').split(' ').filter(Boolean);

                    button.classList.remove(...activeClasses, ...inactiveClasses);
                    if (isActive) {
                        if (activeClasses.length) {
                            button.classList.add(...activeClasses);
                        }
                    } else if (inactiveClasses.length) {
                        button.classList.add(...inactiveClasses);
                    }
                });
            }

            function updateCharts(data) {
                const hasData = Boolean(data.has_data);

                toggleVisibility('[data-dashboard="chart-executions-canvas"]', hasData);
                toggleVisibility('[data-dashboard="chart-status-canvas"]', hasData);
                toggleVisibility('[data-dashboard="chart-volume-canvas"]', hasData);
                toggleVisibility('[data-dashboard="chart-executions-empty"]', !hasData);
                toggleVisibility('[data-dashboard="chart-status-empty"]', !hasData);
                toggleVisibility('[data-dashboard="chart-volume-empty"]', !hasData);

                if (!hasData) {
                    ['executions', 'status', 'volume'].forEach((key) => {
                        if (state.charts[key]) {
                            const chart = state.charts[key];
                            chart.data.labels = [];
                            chart.data.datasets.forEach((dataset) => {
                                dataset.data = [];
                            });
                            chart.update();
                        }
                    });
                    return;
                }

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

                const labels = data.chart.labels || [];
                const totalsData = data.chart.totals || [];
                const successData = data.chart.success || [];
                const failureData = data.chart.failures || [];
                const volumeData = data.chart.volume_mb || [];

                if (!state.charts.executions) {
                    const ctx = document.getElementById('dailyExecutionsChart');
                    if (ctx) {
                        state.charts.executions = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels,
                                datasets: [{
                                    label: 'Execuções',
                                    data: totalsData,
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
                                            label: (context) => ` ${context.parsed.y ?? 0} execuções`
                                        }
                                    }
                                }
                            }
                        });
                    }
                } else {
                    const chart = state.charts.executions;
                    chart.data.labels = labels;
                    chart.data.datasets[0].data = totalsData;
                    chart.update();
                }

                if (!state.charts.status) {
                    const ctx = document.getElementById('dailyStatusChart');
                    if (ctx) {
                        state.charts.status = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels,
                                datasets: [
                                    {
                                        label: 'Sucesso',
                                        data: successData,
                                        backgroundColor: chartColors.success,
                                        stack: 'status'
                                    },
                                    {
                                        label: 'Falha',
                                        data: failureData,
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
                                            label: (context) => ` ${context.dataset.label}: ${context.parsed.y ?? 0}`
                                        }
                                    }
                                }
                            }
                        });
                    }
                } else {
                    const chart = state.charts.status;
                    chart.data.labels = labels;
                    chart.data.datasets[0].data = successData;
                    chart.data.datasets[1].data = failureData;
                    chart.update();
                }

                if (!state.charts.volume) {
                    const ctx = document.getElementById('dailyVolumeChart');
                    if (ctx) {
                        state.charts.volume = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels,
                                datasets: [{
                                    label: 'Volume (MB)',
                                    data: volumeData,
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
                                            label: (context) => {
                                                const value = context.parsed.y ?? 0;
                                                return ` ${value.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} MB`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                } else {
                    const chart = state.charts.volume;
                    chart.data.labels = labels;
                    chart.data.datasets[0].data = volumeData;
                    chart.update();
                }
            }

            function applyData(data) {
                state.data = data;

                const period = data.period || {};
                const totals = data.totals || {};
                const chartTitles = data.chart_titles || {};
                const latest = data.latest_dump || null;

                setText('period-title', period.title || '');
                setText('period-description', period.description || '');
                setText('period-label', period.label || '');

                setText('total-executions', totals.executions_text || '0');
                setText('total-success', totals.success_text || '0');
                setText('total-failure', totals.failure_text || '0');
                setText('success-rate', totals.success_rate_text || '—');
                setText('failure-rate', totals.failure_rate_text || '—');
                setText('volume-text', totals.volume_text || '—');

                toggleVisibility('[data-dashboard="latest-card"]', Boolean(latest));
                if (latest) {
                    setText('latest-name', latest.bd_nome_usuario || '-');
                    setText('latest-datetime', latest.data_execucao_text || '-');
                    setHtml('latest-status', latest.status_badge_html || defaultStatusBadge);
                }

                setText('error-text', data.error ?? '');
                toggleVisibility('[data-dashboard="error"]', Boolean(data.error));
                toggleVisibility('[data-dashboard="stats-container"]', !data.error);
                toggleVisibility('[data-dashboard="charts-section"]', !data.error);

                setText('chart-executions-title', chartTitles.executions_title || '');
                setText('chart-executions-subtitle', chartTitles.executions_subtitle || '');
                setText('chart-status-title', chartTitles.status_title || '');
                setText('chart-status-subtitle', chartTitles.status_subtitle || '');
                setText('chart-volume-title', chartTitles.volume_title || '');
                setText('chart-volume-subtitle', chartTitles.volume_subtitle || '');

                updateRangeButtons();
                updateCharts(data);
            }

            function fetchData() {
                const url = new URL(state.endpoints.metrics, window.location.origin);
                url.searchParams.set('range', String(state.range));

                return fetch(url.toString(), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        return response.json();
                    })
                    .then((payload) => {
                        if (payload && payload.data) {
                            applyData(payload.data);
                        } else {
                            throw new Error('Resposta inválida do servidor.');
                        }
                    })
                    .catch((error) => {
                        console.error('Falha ao atualizar indicadores', error);
                        setText('error-text', 'Não foi possível atualizar os indicadores automaticamente.');
                        toggleVisibility('[data-dashboard="error"]', true);
                    });
            }

            function updateUrl() {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('range', String(state.range));
                window.history.replaceState({}, '', currentUrl.toString());
            }

            function scheduleRefresh() {
                if (state.timer) {
                    clearInterval(state.timer);
                }
                state.timer = setInterval(() => {
                    fetchData().catch(() => {});
                }, state.refreshMs);
            }

            function initRangeControls() {
                const form = document.querySelector(selectors.rangeForm);
                if (form) {
                    form.addEventListener('submit', (event) => event.preventDefault());
                }
                elements(selectors.rangeButtons).forEach((button) => {
                    button.addEventListener('click', (event) => {
                        event.preventDefault();
                        const newRange = Number(button.value);
                        if (Number.isNaN(newRange) || newRange === state.range) {
                            return;
                        }
                        state.range = newRange;
                        updateRangeButtons();
                        updateUrl();
                        fetchData().then(() => {
                            scheduleRefresh();
                        });
                    });
                });
            }

            function init() {
                if (state.data) {
                    applyData(state.data);
                }
                initRangeControls();
                updateUrl();
                scheduleRefresh();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
<?php
safekup_render_footer();
