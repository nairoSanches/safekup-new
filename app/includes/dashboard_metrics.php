<?php
declare(strict_types=1);

/**
 * Retorna os intervalos disponíveis para o dashboard.
 *
 * @return array<int, string>
 */
function safekup_dashboard_range_options(): array
{
    return [
        14 => '14 dias',
        7  => '7 dias',
        1  => 'Hoje',
    ];
}

/**
 * Intervalo padrão do dashboard.
 */
function safekup_dashboard_default_range(): int
{
    return 1;
}

/**
 * Calcula métricas do dashboard conforme o intervalo informado.
 *
 * @return array{
 *     range_days:int,
 *     period:array{title:string,label:string,description:string},
 *     totals:array{
 *         executions:int,
 *         executions_text:string,
 *         success:int,
 *         success_text:string,
 *         failure:int,
 *         failure_text:string,
 *         success_rate:?float,
 *         success_rate_text:string,
 *         failure_rate:?float,
 *         failure_rate_text:string,
 *         volume_bytes:float,
 *         volume_text:string
 *     },
 *     chart_titles:array{
 *         executions_title:string,
 *         executions_subtitle:string,
 *         status_title:string,
 *         status_subtitle:string,
 *         volume_title:string,
 *         volume_subtitle:string
 *     },
 *     chart:array{
 *         labels:array<int,string>,
 *         totals:array<int,int>,
 *         success:array<int,int>,
 *         failures:array<int,int>,
 *         volume_mb:array<int,float>
 *     },
 *     latest_dump:?array{
 *         bd_nome_usuario:string,
 *         status:string,
 *         status_badge_html:string,
 *         data_execucao:string,
 *         data_execucao_text:string
 *     },
 *     has_data:bool,
 *     error:?string,
 *     timestamp:string
 * }
 */
function safekup_dashboard_metrics(PDO $pdo, int $rangeDays): array
{
    $rangeOptions = safekup_dashboard_range_options();
    if (!array_key_exists($rangeDays, $rangeOptions)) {
        $rangeDays = safekup_dashboard_default_range();
    }

    $periodTitle = $rangeDays === 1 ? 'Atividade de hoje' : "Atividade dos últimos {$rangeDays} dias";
    $periodLabel = $rangeDays === 1 ? 'Hoje' : "Últimos {$rangeDays} dias";
    $periodDescription = '—';

    $dailyLabels = [];
    $dailyTotals = [];
    $dailySuccess = [];
    $dailyFailures = [];
    $dailyVolumeMb = [];
    $dailyVolumeBytes = [];
    $totalExecutions = 0;
    $successExecutions = 0;
    $failureExecutions = 0;
    $totalVolumeBytes = 0.0;
    $successRate = null;
    $failureRate = null;
    $latestDump = null;
    $statsError = null;
    $hasDailyData = false;

    try {
        $today = new DateTimeImmutable('today');
        $startDateObj = $rangeDays > 1
            ? $today->sub(new DateInterval('P' . ($rangeDays - 1) . 'D'))
            : $today;
        $startDateParam = $rangeDays === 1
            ? $startDateObj->format('Y-m-d')
            : $startDateObj->format('Y-m-d 00:00:00');
        $periodDescription = $rangeDays === 1
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
        $successRate = $totalExecutions > 0 ? round(($successExecutions / max($totalExecutions, 1)) * 100, 1) : null;
        $failureRate = $totalExecutions > 0 ? round(($failureExecutions / max($totalExecutions, 1)) * 100, 1) : null;
        $hasDailyData = !empty($dailyLabels);

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
        $totalVolumeBytes = 0.0;
        $successRate = null;
        $failureRate = null;
        $latestDump = null;
        $hasDailyData = false;
    }

    $successRateText = $successRate !== null ? number_format($successRate, 1, ',', '.') . '%' : '—';
    $failureRateText = $failureRate !== null ? number_format($failureRate, 1, ',', '.') . '%' : '—';
    $formattedVolume = safekup_format_size($totalVolumeBytes);

    $latestDumpData = null;
    if ($latestDump) {
        $status = (string) ($latestDump['status'] ?? '-');
        $latestDumpData = [
            'bd_nome_usuario' => (string) ($latestDump['bd_nome_usuario'] ?? '-'),
            'status' => $status,
            'status_badge_html' => safekup_badge(
                $status !== '' ? $status : '-',
                strtoupper($status) === 'OK' ? 'success' : 'danger'
            ),
            'data_execucao' => (string) ($latestDump['data_execucao'] ?? ''),
            'data_execucao_text' => safekup_format_datetime($latestDump['data_execucao'] ?? null),
        ];
    }

    $chartTitles = [
        'executions_title' => $rangeDays === 1 ? 'Execuções totais por hora' : 'Execuções totais por dia',
        'executions_subtitle' => $rangeDays === 1
            ? 'Visualize a distribuição das execuções ao longo do dia selecionado.'
            : 'Visualize a tendência diária de dumps realizados.',
        'status_title' => $rangeDays === 1 ? 'Sucesso x falha por hora' : 'Sucesso x falha por dia',
        'status_subtitle' => $rangeDays === 1
            ? 'Comparativo horário entre execuções concluídas e com problemas.'
            : 'Comparativo diário entre execuções concluídas e com problemas.',
        'volume_title' => $rangeDays === 1 ? 'Volume gerado por hora (MB)' : 'Volume gerado por dia (MB)',
        'volume_subtitle' => $rangeDays === 1
            ? 'Soma horária do tamanho dos arquivos produzidos pelos dumps.'
            : 'Soma diária do tamanho dos arquivos produzidos pelos dumps.',
    ];

    return [
        'range_days' => $rangeDays,
        'period' => [
            'title' => $periodTitle,
            'label' => $periodLabel,
            'description' => $periodDescription,
        ],
        'totals' => [
            'executions' => $totalExecutions,
            'executions_text' => number_format($totalExecutions, 0, ',', '.'),
            'success' => $successExecutions,
            'success_text' => number_format($successExecutions, 0, ',', '.'),
            'failure' => $failureExecutions,
            'failure_text' => number_format($failureExecutions, 0, ',', '.'),
            'success_rate' => $successRate,
            'success_rate_text' => $successRateText,
            'failure_rate' => $failureRate,
            'failure_rate_text' => $failureRateText,
            'volume_bytes' => $totalVolumeBytes,
            'volume_text' => $formattedVolume,
        ],
        'chart_titles' => $chartTitles,
        'chart' => [
            'labels' => $dailyLabels,
            'totals' => $dailyTotals,
            'success' => $dailySuccess,
            'failures' => $dailyFailures,
            'volume_mb' => $dailyVolumeMb,
        ],
        'latest_dump' => $latestDumpData,
        'has_data' => $hasDailyData,
        'error' => $statsError,
        'timestamp' => date('c'),
    ];
}
