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
 *     databases:array{
 *         total:int,
 *         total_text:string,
 *         active:int,
 *         active_text:string,
 *         inactive:int,
 *         inactive_text:string,
 *         active_badges_html:string
 *     },
 *     recent_periods:array<int, array{
 *         interval_text:string,
 *         summary_text:string,
 *         success_count:int,
 *         failure_count:int,
 *         total_count:int,
 *         variant:string
 *     }>,
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
 *         has_error:bool,
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
    $latestDumpData = null;
    $recentPeriods = [];
    $statsError = null;
    $hasDailyData = false;
    $databaseSummary = [
        'total' => 0,
        'total_text' => '0',
        'active' => 0,
        'active_text' => '0',
        'inactive' => 0,
        'inactive_text' => '0',
        'active_badges_html' => '<span class="rounded-lg border border-white/10 bg-slate-800/70 px-3 py-1 text-xs text-slate-300">Nenhum banco cadastrado</span>',
    ];
    $calendarWeekdayLabels = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
    $calendarSummary = [
        'start_date' => '',
        'end_date' => '',
        'month_label' => '',
        'weekday_labels' => $calendarWeekdayLabels,
        'weeks' => [],
        'active_total' => 0,
        'has_active' => false,
    ];

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
                SUM(COALESCE(h.tamanho_arquivo, 0)) AS total_size_bytes,
                MIN(h.data_execucao) AS first_execucao,
                MAX(h.data_execucao) AS last_execucao
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
        $latestRow = $latestDumpStmt ? $latestDumpStmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($latestRow) {
            $rawStatus = (string) ($latestRow['status'] ?? '');
            $normalizedStatus = strtoupper(trim($rawStatus));
            $hasError = $normalizedStatus !== '' && !in_array($normalizedStatus, ['OK', 'SUCESSO'], true);
            $badgeVariant = $normalizedStatus === '' ? 'default' : ($hasError ? 'danger' : 'success');
            $statusLabel = $rawStatus !== '' ? $rawStatus : '-';

            $latestDumpData = [
                'bd_nome_usuario' => (string) ($latestRow['bd_nome_usuario'] ?? '-'),
                'status' => $statusLabel,
                'has_error' => $hasError,
                'status_badge_html' => safekup_badge(
                    $statusLabel,
                    $badgeVariant
                ),
                'data_execucao' => (string) ($latestRow['data_execucao'] ?? ''),
                'data_execucao_text' => safekup_format_datetime($latestRow['data_execucao'] ?? null),
            ];
        }

        $databaseCountsStmt = $pdo->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN UPPER(TRIM(COALESCE(db.bd_backup_ativo, ''))) = 'SIM' THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN UPPER(TRIM(COALESCE(db.bd_backup_ativo, ''))) IN ('NAO', 'NÃO') THEN 1 ELSE 0 END) AS inactive_count
            FROM db_management db
        ");
        $databaseCounts = $databaseCountsStmt ? $databaseCountsStmt->fetch(PDO::FETCH_ASSOC) : null;

        $totalDatabases = (int) ($databaseCounts['total'] ?? 0);
        $activeDatabases = (int) ($databaseCounts['active_count'] ?? 0);
        $inactiveDatabases = (int) ($databaseCounts['inactive_count'] ?? 0);

        $activeByTypeStmt = $pdo->query("
            SELECT
                COALESCE(NULLIF(TRIM(t.tipo_nome), ''), 'Sem tipo') AS tipo_nome,
                COUNT(*) AS total
            FROM db_management db
            LEFT JOIN tipo t ON db.bd_tipo = t.tipo_id
            WHERE UPPER(TRIM(COALESCE(db.bd_backup_ativo, ''))) = 'SIM'
            GROUP BY tipo_nome
            ORDER BY total DESC, tipo_nome ASC
        ");
        $activeByTypeRows = $activeByTypeStmt ? $activeByTypeStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $badgePieces = [];
        foreach ($activeByTypeRows as $activeRow) {
            $typeName = (string) ($activeRow['tipo_nome'] ?? 'Sem tipo');
            $countText = number_format((int) ($activeRow['total'] ?? 0), 0, ',', '.');
            $badgePieces[] = sprintf(
                '<span class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-slate-800/80 px-3 py-1 text-xs font-semibold text-slate-200">%s<span class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[0.7rem] font-bold text-emerald-200">%s</span></span>',
                safekup_render_database_label($typeName),
                $countText
            );
        }

        $activeBadgesHtml = $badgePieces
            ? implode('', $badgePieces)
            : '<span class="rounded-lg border border-white/10 bg-slate-800/70 px-3 py-1 text-xs text-slate-300">Nenhum banco ativo</span>';

        $databaseSummary = [
            'total' => $totalDatabases,
            'total_text' => number_format($totalDatabases, 0, ',', '.'),
            'active' => $activeDatabases,
            'active_text' => number_format($activeDatabases, 0, ',', '.'),
            'inactive' => $inactiveDatabases,
            'inactive_text' => number_format($inactiveDatabases, 0, ',', '.'),
            'active_badges_html' => $activeBadgesHtml,
        ];

        $recentPeriods = [];
        $periodSlice = array_slice($dailyStats, -6);
        $periodSlice = array_reverse($periodSlice);
        foreach ($periodSlice as $periodRow) {
            $firstExec = $periodRow['first_execucao'] ?? null;
            $lastExec = $periodRow['last_execucao'] ?? null;
            $startDate = $firstExec ? new DateTimeImmutable($firstExec) : null;
            $endDate = $lastExec ? new DateTimeImmutable($lastExec) : $startDate;

            $intervalText = '—';
            if ($startDate && $endDate) {
                $sameDay = $startDate->format('Y-m-d') === $endDate->format('Y-m-d');
                if ($sameDay) {
                    if ($rangeDays === 1) {
                        $intervalText = sprintf(
                            '%s %s até %s',
                            $startDate->format('d/m'),
                            $startDate->format('H:i'),
                            $endDate->format('H:i')
                        );
                    } else {
                        $intervalText = sprintf(
                            '%s %s até %s',
                            $startDate->format('d/m'),
                            $startDate->format('H:i'),
                            $endDate->format('H:i')
                        );
                    }
                } else {
                    $intervalText = sprintf(
                        '%s até %s',
                        $startDate->format('d/m H:i'),
                        $endDate->format('d/m H:i')
                    );
                }
            } elseif ($startDate) {
                $intervalText = $rangeDays === 1
                    ? $startDate->format('d/m H:i')
                    : $startDate->format('d/m H:i');
            }

            $successCount = (int) ($periodRow['success_count'] ?? 0);
            $failureCount = (int) ($periodRow['failure_count'] ?? 0);
            $totalCount = (int) ($periodRow['total'] ?? ($successCount + $failureCount));

            if ($failureCount === 0 && $successCount > 0) {
                $summaryText = 'Todos OK';
                $variant = 'success';
            } elseif ($failureCount === 0 && $successCount === 0) {
                $summaryText = 'Sem execuções registradas';
                $variant = 'default';
            } else {
                $parts = [];
                if ($failureCount > 0) {
                    $parts[] = sprintf('%d falha%s', $failureCount, $failureCount === 1 ? '' : 's');
                }
                if ($successCount > 0) {
                    $parts[] = sprintf('%d OK', $successCount);
                }
                if (empty($parts) && $totalCount > 0) {
                    $parts[] = sprintf('%d execuções', $totalCount);
                }
                $summaryText = implode(' · ', $parts);
                $variant = $failureCount > 0 ? 'danger' : 'success';
            }

            $recentPeriods[] = [
                'interval_text' => $intervalText,
                'summary_text' => $summaryText,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'total_count' => $totalCount,
                'variant' => $variant,
            ];
        }

        $makeCalendarKey = static function (?int $id, ?string $name): string {
            if ($id !== null) {
                return 'id:' . $id;
            }
            $normalized = strtolower(trim((string) ($name ?? '')));
            if ($normalized !== '') {
                return 'name:' . md5($normalized);
            }
            return 'unknown';
        };

        $activeDbStmt = $pdo->query("
            SELECT bd_id, bd_nome_usuario
            FROM db_management
            WHERE UPPER(TRIM(COALESCE(bd_backup_ativo, ''))) = 'SIM'
        ");
        $activeDbRows = $activeDbStmt ? $activeDbStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $activeDatabases = [];
        foreach ($activeDbRows as $row) {
            $dbId = isset($row['bd_id']) ? (int) $row['bd_id'] : null;
            $dbName = trim((string) ($row['bd_nome_usuario'] ?? ''));
            $key = $makeCalendarKey($dbId, $dbName);
            if (!isset($activeDatabases[$key])) {
                $activeDatabases[$key] = [
                    'id' => $dbId,
                    'name' => $dbName !== '' ? $dbName : 'Sem nome',
                ];
            }
        }
        $activeDbCount = count($activeDatabases);

        $calendarMonthStart = $today->modify('first day of this month')->setTime(0, 0, 0);
        $calendarViewStart = $calendarMonthStart;
        while ((int) $calendarViewStart->format('N') !== 1) {
            $calendarViewStart = $calendarViewStart->sub(new DateInterval('P1D'));
        }

        $calendarMonthEnd = $today->modify('last day of this month')->setTime(0, 0, 0);
        $calendarViewEnd = $calendarMonthEnd;
        while ((int) $calendarViewEnd->format('N') !== 7) {
            $calendarViewEnd = $calendarViewEnd->add(new DateInterval('P1D'));
        }

        $calendarQueryStart = $calendarViewStart->format('Y-m-d 00:00:00');
        $calendarQueryEndObj = $calendarViewEnd->add(new DateInterval('P1D'));
        $calendarQueryEnd = $calendarQueryEndObj->format('Y-m-d 00:00:00');

        $calendarStmt = $pdo->prepare("
            SELECT h.bd_id, h.bd_nome_usuario, h.data_execucao, h.status
            FROM historico_dumps h
            WHERE h.data_execucao >= :startDate
              AND h.data_execucao < :endDate
            ORDER BY h.data_execucao ASC
        ");
        $calendarStmt->execute([
            'startDate' => $calendarQueryStart,
            'endDate' => $calendarQueryEnd,
        ]);
        $calendarRows = $calendarStmt->fetchAll(PDO::FETCH_ASSOC);

        $calendarSuccessTokens = ['OK', 'SUCESS'];
        $calendarFailureTokens = ['ERRO', 'FALHA', 'FAIL', 'CANCEL', 'NOK'];
        $calendarMap = [];
        foreach ($calendarRows as $row) {
            $execDateRaw = $row['data_execucao'] ?? null;
            if (!$execDateRaw) {
                continue;
            }
            try {
                $execDate = new DateTimeImmutable($execDateRaw);
            } catch (Throwable $calendarException) {
                continue;
            }

            $dateKey = $execDate->format('Y-m-d');
            $dbId = isset($row['bd_id']) ? (int) $row['bd_id'] : null;
            $dbName = trim((string) ($row['bd_nome_usuario'] ?? ''));
            $dbKey = $makeCalendarKey($dbId, $dbName);

            if (!isset($calendarMap[$dateKey])) {
                $calendarMap[$dateKey] = [];
            }
            if (!isset($calendarMap[$dateKey][$dbKey])) {
                $calendarMap[$dateKey][$dbKey] = [
                    'executions' => 0,
                    'success_count' => 0,
                    'failure_count' => 0,
                    'unknown_count' => 0,
                    'has_success' => false,
                    'has_failure' => false,
                    'has_unknown' => false,
                    'db_name' => $dbName !== '' ? $dbName : 'Sem nome',
                ];
            }

            $entry = &$calendarMap[$dateKey][$dbKey];
            $entry['executions']++;

            $normalizedStatus = strtoupper(trim((string) ($row['status'] ?? '')));
            $hasSuccess = false;
            foreach ($calendarSuccessTokens as $token) {
                if ($token !== '' && strpos($normalizedStatus, $token) !== false) {
                    $hasSuccess = true;
                    break;
                }
            }
            $hasFailure = false;
            if (!$hasSuccess) {
                foreach ($calendarFailureTokens as $token) {
                    if ($token !== '' && strpos($normalizedStatus, $token) !== false) {
                        $hasFailure = true;
                        break;
                    }
                }
            }

            if ($hasSuccess) {
                $entry['has_success'] = true;
                $entry['success_count']++;
            }
            if ($hasFailure) {
                $entry['has_failure'] = true;
                $entry['failure_count']++;
            }
            if (!$hasSuccess && !$hasFailure) {
                $entry['has_unknown'] = true;
                $entry['unknown_count']++;
            }

            unset($entry);
        }

        $monthNames = [
            1 => 'janeiro',
            2 => 'fevereiro',
            3 => 'março',
            4 => 'abril',
            5 => 'maio',
            6 => 'junho',
            7 => 'julho',
            8 => 'agosto',
            9 => 'setembro',
            10 => 'outubro',
            11 => 'novembro',
            12 => 'dezembro',
        ];
        $monthName = $monthNames[(int) $calendarMonthStart->format('n')] ?? $calendarMonthStart->format('F');
        $monthLabel = ucfirst($monthName) . ' ' . $calendarMonthStart->format('Y');

        $statusTexts = [
            'success' => 'Todos os backups concluídos',
            'warning' => 'Execuções pendentes',
            'danger' => 'Erros detectados',
            'future' => 'Dia futuro',
            'empty' => 'Sem execuções registradas',
        ];

        $calendarWeeks = [];
        $cursor = $calendarViewStart;
        $weekBuffer = [];
        while ($cursor <= $calendarViewEnd) {
            $dateKey = $cursor->format('Y-m-d');
            $isToday = $cursor->format('Y-m-d') === $today->format('Y-m-d');
            $isFuture = $cursor > $today;
            $isPast = $cursor < $today;
            $isCurrentMonth = $cursor->format('m') === $calendarMonthStart->format('m');

            $dayEntries = $calendarMap[$dateKey] ?? [];
            $dayExecutions = 0;
            $dayHasFailure = false;
            $daySuccessExecutions = 0;
            $dayFailureExecutions = 0;
            $dayUnknownExecutions = 0;
            foreach ($dayEntries as $entry) {
                $dayExecutions += (int) ($entry['executions'] ?? 0);
                if (!empty($entry['has_failure'])) {
                    $dayHasFailure = true;
                }
                $daySuccessExecutions += (int) ($entry['success_count'] ?? 0);
                $dayFailureExecutions += (int) ($entry['failure_count'] ?? 0);
                $dayUnknownExecutions += (int) ($entry['unknown_count'] ?? 0);
            }

            $activeSuccess = [];
            $activeFailure = [];
            $activePending = [];
            foreach ($activeDatabases as $dbKey => $dbInfo) {
                $entry = $dayEntries[$dbKey] ?? null;
                if ($entry) {
                    if (!empty($entry['has_failure'])) {
                        $activeFailure[] = $dbInfo['name'];
                    } elseif (!empty($entry['has_success'])) {
                        $activeSuccess[] = $dbInfo['name'];
                    } else {
                        if (!$isFuture) {
                            $activePending[] = $dbInfo['name'];
                        }
                    }
                } else {
                    if (!$isFuture) {
                        $activePending[] = $dbInfo['name'];
                    }
                }
            }

            $failureNames = $activeFailure;
            foreach ($dayEntries as $dbKey => $entry) {
                if (!empty($entry['has_failure']) && !isset($activeDatabases[$dbKey])) {
                    $failureNames[] = $entry['db_name'];
                }
            }
            $failureNames = array_values(array_unique($failureNames));

            $variant = 'empty';
            if ($isFuture) {
                $variant = 'future';
            } elseif ($dayHasFailure || !empty($failureNames)) {
                $variant = 'danger';
            } elseif ($activeDbCount === 0) {
                $variant = $dayExecutions > 0 ? 'success' : 'empty';
            } elseif (!empty($activePending)) {
                $variant = 'warning';
            } else {
                $variant = $dayExecutions > 0 ? 'success' : 'warning';
            }

            $statusText = $statusTexts[$variant] ?? 'Sem dados';
            if ($isToday && !$isFuture) {
                $variant = 'warning';
                $statusText = 'Dia em andamento';
            }

            $summaryParts = [];
            if ($dayExecutions > 0 || $daySuccessExecutions > 0) {
                $summaryParts[] = sprintf(
                    '%d/%d execuções/sucesso',
                    $dayExecutions,
                    $daySuccessExecutions
                );
            }
            if ($dayFailureExecutions > 0) {
                $summaryParts[] = sprintf(
                    '%d com erro',
                    $dayFailureExecutions
                );
            }
            if (!$isFuture && !empty($activePending)) {
                $summaryParts[] = sprintf(
                    '%d pendente%s',
                    count($activePending),
                    count($activePending) === 1 ? '' : 's'
                );
            }
            $summaryParts = array_values(array_filter($summaryParts));
            if (empty($summaryParts)) {
                $summaryParts[] = $dayExecutions > 0
                    ? sprintf(
                        '%d execução%s registrada%s',
                        $dayExecutions,
                        $dayExecutions === 1 ? '' : 'es',
                        $dayExecutions === 1 ? '' : 's'
                    )
                    : 'Sem execuções registradas';
            }
            $summaryText = implode(' · ', $summaryParts);
            if ($isFuture) {
                $summaryText = 'Execuções futuras';
            }

            $detailExecutionLine = '';
            if ($dayExecutions > 0 || $daySuccessExecutions > 0 || $dayFailureExecutions > 0 || $dayUnknownExecutions > 0) {
                $detailExecutionLine = sprintf(
                    'Execuções: %d total · %d sucesso · %d erro',
                    $dayExecutions,
                    $daySuccessExecutions,
                    $dayFailureExecutions
                );
                if ($dayUnknownExecutions > 0) {
                    $detailExecutionLine .= sprintf(
                        ' · %d outra%s',
                        $dayUnknownExecutions,
                        $dayUnknownExecutions === 1 ? '' : 's'
                    );
                }
            }

            $weekBuffer[] = [
                'date' => $dateKey,
                'label' => $cursor->format('j'),
                'weekday' => (int) $cursor->format('N'),
                'weekday_label' => $calendarWeekdayLabels[((int) $cursor->format('N')) - 1] ?? $cursor->format('D'),
                'is_today' => $isToday,
                'is_past' => $isPast,
                'is_future' => $isFuture,
                'is_current_month' => $isCurrentMonth,
                'variant' => $variant,
                'status_text' => $statusText,
                'summary' => $summaryText,
                'counts' => [
                    'success' => $daySuccessExecutions,
                    'failure' => $dayFailureExecutions,
                    'pending' => count($activePending),
                    'executions' => $dayExecutions,
                    'unknown' => $dayUnknownExecutions,
                    'active_total' => $activeDbCount,
                ],
                'details' => [
                    'success' => array_values($activeSuccess),
                    'failure' => $failureNames,
                    'pending' => array_values($activePending),
                    'executions_line' => $detailExecutionLine,
                ],
            ];

            if (count($weekBuffer) === 7) {
                $calendarWeeks[] = $weekBuffer;
                $weekBuffer = [];
            }

            $cursor = $cursor->add(new DateInterval('P1D'));
        }

        if (!empty($weekBuffer)) {
            $calendarWeeks[] = $weekBuffer;
        }

        $calendarSummary = [
            'start_date' => $calendarViewStart->format('Y-m-d'),
            'end_date' => $calendarViewEnd->format('Y-m-d'),
            'month_label' => $monthLabel,
            'weekday_labels' => $calendarWeekdayLabels,
            'weeks' => $calendarWeeks,
            'active_total' => $activeDbCount,
            'has_active' => $activeDbCount > 0,
        ];
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
        $latestDumpData = null;
        $recentPeriods = [];
        $hasDailyData = false;
        $databaseSummary = [
            'total' => 0,
            'total_text' => '0',
            'active' => 0,
            'active_text' => '0',
            'inactive' => 0,
            'inactive_text' => '0',
            'active_badges_html' => '<span class="rounded-lg border border-white/10 bg-slate-800/70 px-3 py-1 text-xs text-slate-300">Nenhum banco cadastrado</span>',
        ];
        $calendarSummary = [
            'start_date' => '',
            'end_date' => '',
            'month_label' => '',
            'weekday_labels' => $calendarWeekdayLabels,
            'weeks' => [],
            'active_total' => 0,
            'has_active' => false,
        ];
    }

    $successRateText = $successRate !== null ? number_format($successRate, 1, ',', '.') . '%' : '—';
    $failureRateText = $failureRate !== null ? number_format($failureRate, 1, ',', '.') . '%' : '—';
    $formattedVolume = safekup_format_size($totalVolumeBytes);

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
        'databases' => $databaseSummary,
        'recent_periods' => $recentPeriods,
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
        'calendar' => $calendarSummary,
        'timestamp' => date('c'),
    ];
}
