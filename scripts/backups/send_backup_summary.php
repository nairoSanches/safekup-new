<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../php/conexao/conexao_pdo.php';

if (!function_exists('safekup_backup_summary_log_info')) {
    function safekup_backup_summary_log_info(?callable $logger, string $message): void
    {
        if ($logger !== null) {
            $logger($message);
            return;
        }
        if (php_sapi_name() === 'cli') {
            fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . "] $message\n");
        }
    }
}

if (!function_exists('safekup_backup_summary_log_error')) {
    function safekup_backup_summary_log_error(?callable $logger, string $message): void
    {
        if ($logger !== null) {
            $logger($message);
            return;
        }
        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, '[' . date('Y-m-d H:i:s') . "] $message\n");
        }
    }
}

if (!function_exists('safekup_backup_summary_format_bytes')) {
    function safekup_backup_summary_format_bytes(?float $bytes): string
    {
        if ($bytes === null || $bytes <= 0) {
            return '-';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) min(floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);
        return number_format($value, $power === 0 ? 0 : 2, ',', '.') . ' ' . $units[$power];
    }
}

if (!function_exists('safekup_backup_summary_format_datetime')) {
    function safekup_backup_summary_format_datetime(?string $value): string
    {
        if ($value === null) {
            return '-';
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $value;
        }
        return date('d/m/Y H:i', $timestamp);
    }
}

if (!function_exists('safekup_backup_summary_build_html')) {
    function safekup_backup_summary_build_html(string $targetDate, array $rows, array $totals): string
    {
        $formattedDate = DateTime::createFromFormat('Y-m-d', $targetDate)?->format('d/m/Y') ?? $targetDate;
        ob_start();
        ?>
        <p style="font-family: Arial, sans-serif; font-size: 14px; color: #0f172a;">
            Olá,<br><br>
            Segue o resumo das execuções de backup registradas em <strong><?= htmlspecialchars($formattedDate); ?></strong>.
        </p>

        <table style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 13px;">
            <tbody>
                <tr>
                    <td style="padding: 6px 10px; border: 1px solid #cbd5f5;">Total de backups</td>
                    <td style="padding: 6px 10px; border: 1px solid #cbd5f5; font-weight: bold;"><?= (int) $totals['total']; ?></td>
                </tr>
                <tr>
                    <td style="padding: 6px 10px; border: 1px solid #cbd5f5;">Com sucesso (OK)</td>
                    <td style="padding: 6px 10px; border: 1px solid #cbd5f5; font-weight: bold; color: #16a34a;"><?= (int) $totals['success']; ?></td>
                </tr>
                <tr>
                    <td style="padding: 6px 10px; border: 1px solid #cbd5f5;">Com falha</td>
                    <td style="padding: 6px 10px; border: 1px solid #cbd5f5; font-weight: bold; color: #f87171;"><?= (int) $totals['failure']; ?></td>
                </tr>
            </tbody>
        </table>

        <p style="font-family: Arial, sans-serif; font-size: 13px; color: #475569; margin-top: 16px;">
            Detalhes por banco:
        </p>

        <table style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 12px;">
            <thead>
                <tr style="background:#0f172a; color:#e2e8f0;">
                    <th style="padding:8px 10px; border:1px solid #1e293b; text-align:left;">Banco</th>
                    <th style="padding:8px 10px; border:1px solid #1e293b; text-align:left;">IP</th>
                    <th style="padding:8px 10px; border:1px solid #1e293b; text-align:left;">Status</th>
                    <th style="padding:8px 10px; border:1px solid #1e293b; text-align:left;">Executado em</th>
                    <th style="padding:8px 10px; border:1px solid #1e293b; text-align:left;">Tempo</th>
                    <th style="padding:8px 10px; border:1px solid #1e293b; text-align:left;">Tamanho</th>
                    <th style="padding:8px 10px; border:1px solid #1e293b; text-align:left;">Detalhe</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                    $status = strtoupper((string) ($row['status'] ?? '—'));
                    $statusColor = $status === 'OK' ? '#16a34a' : '#f97316';
                    $descricao = trim((string) ($row['descricao'] ?? ''));
                ?>
                <tr>
                    <td style="padding:8px 10px; border:1px solid #1e293b; font-weight:bold; color:#0f172a;">
                        <?= htmlspecialchars((string) ($row['bd_nome_usuario'] ?? '—')); ?>
                    </td>
                    <td style="padding:8px 10px; border:1px solid #1e293b; color:#334155;">
                        <?= htmlspecialchars((string) ($row['bd_ip'] ?? '—')); ?>
                    </td>
                    <td style="padding:8px 10px; border:1px solid #1e293b; font-weight:bold; color: <?= $statusColor; ?>;">
                        <?= htmlspecialchars($status); ?>
                    </td>
                    <td style="padding:8px 10px; border:1px solid #1e293b; color:#334155;">
                        <?= htmlspecialchars(safekup_backup_summary_format_datetime($row['data_execucao'] ?? null)); ?>
                    </td>
                    <td style="padding:8px 10px; border:1px solid #1e293b; color:#334155;">
                        <?= htmlspecialchars((string) ($row['tempo_decorrido'] ?? '-')); ?>
                    </td>
                    <td style="padding:8px 10px; border:1px solid #1e293b; color:#334155;">
                        <?= htmlspecialchars(safekup_backup_summary_format_bytes(isset($row['tamanho_arquivo']) ? (float) $row['tamanho_arquivo'] : null)); ?>
                    </td>
                    <td style="padding:8px 10px; border:1px solid #1e293b; color:#334155;">
                        <?= $descricao !== '' ? htmlspecialchars($descricao) : '—'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p style="font-family: Arial, sans-serif; font-size: 12px; color: #475569; margin-top: 18px;">
            Dúvidas ou inconsistências? Entre em contato com o time responsável pelo Safekup.
        </p>

        <p style="font-family: Arial, sans-serif; font-size: 12px; color: #475569;">
            — Equipe Safekup
        </p>
        <?php
        return (string) ob_get_clean();
    }
}

if (!function_exists('safekup_backup_summary_build_text')) {
    function safekup_backup_summary_build_text(string $targetDate, array $rows, array $totals): string
    {
        $lines = [];
        $formattedDate = DateTime::createFromFormat('Y-m-d', $targetDate)?->format('d/m/Y') ?? $targetDate;
        $lines[] = "Resumo dos backups - {$formattedDate}";
        $lines[] = str_repeat('=', 60);
        $lines[] = "Total: {$totals['total']} | OK: {$totals['success']} | Falhas: {$totals['failure']}";
        $lines[] = '';
        foreach ($rows as $row) {
            $lines[] = sprintf(
                '- %s (%s) | %s | %s | tempo: %s | tamanho: %s%s',
                $row['bd_nome_usuario'] ?? '—',
                $row['bd_ip'] ?? '—',
                strtoupper($row['status'] ?? '—'),
                safekup_backup_summary_format_datetime($row['data_execucao'] ?? null),
                $row['tempo_decorrido'] ?? '-',
                safekup_backup_summary_format_bytes(isset($row['tamanho_arquivo']) ? (float) $row['tamanho_arquivo'] : null),
                isset($row['descricao']) && trim((string) $row['descricao']) !== '' ? ' | detalhe: ' . trim((string) $row['descricao']) : ''
            );
        }
        $lines[] = '';
        $lines[] = '— Equipe Safekup';

        return implode(PHP_EOL, $lines);
    }
}

if (!function_exists('safekup_backup_summary_send_email')) {
    function safekup_backup_summary_send_email(array $smtpConfig, array $recipients, string $subject, string $htmlBody, string $textBody): void
    {
        $mailer = new PHPMailer(true);

        $mailer->isSMTP();
        $mailer->Host = $smtpConfig['smtp_endereco'];
        $mailer->Port = (int) $smtpConfig['smtp_porta'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $smtpConfig['smtp_email_admin'];
        $mailer->Password = base64_decode((string) $smtpConfig['smtp_senha']);
        $mailer->SMTPSecure = $mailer->Port === 465
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom(
            $smtpConfig['smtp_email_admin'],
            $smtpConfig['smtp_nome'] !== '' ? $smtpConfig['smtp_nome'] : 'Safekup'
        );

        foreach ($recipients as $recipient) {
            $name = trim((string) ($recipient['nome'] ?? ''));
            $firstName = $name !== '' ? preg_split('/\s+/', $name)[0] ?? $name : '';
            $mailer->addAddress($recipient['email'], $firstName);
        }

        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $htmlBody;
        $mailer->AltBody = $textBody;

        $mailer->send();
    }
}

if (!function_exists('safekup_send_backup_summary')) {
    /**
     * Envia o resumo de backups para a data informada (ou última disponível).
     *
     * @param string|null   $targetDate   Data alvo (YYYY-MM-DD) ou null para última data registrada.
     * @param callable|null $infoLogger   Função para log informativo (string $msg): void.
     * @param callable|null $errorLogger  Função para log de erro (string $msg): void.
     *
     * @return array{status:string,target_date:?string,totals:array,sent_count:int,errors:string[]}
     */
    function safekup_send_backup_summary(?string $targetDate = null, ?callable $infoLogger = null, ?callable $errorLogger = null): array
    {
        $errors = [];
        $sentCount = 0;
        $totals = ['total' => 0, 'success' => 0, 'failure' => 0];

        try {
            $pdo = conectar();
        } catch (Throwable $e) {
            $message = 'Falha ao conectar ao banco: ' . $e->getMessage();
            safekup_backup_summary_log_error($errorLogger, $message);
            return [
                'status' => 'error',
                'target_date' => $targetDate,
                'totals' => $totals,
                'sent_count' => $sentCount,
                'errors' => [$message],
            ];
        }

        // Resolve target date
        if ($targetDate !== null) {
            $date = DateTime::createFromFormat('Y-m-d', $targetDate);
            if (!$date || $date->format('Y-m-d') !== $targetDate) {
                $message = "Data informada inválida: {$targetDate}. Use o formato YYYY-MM-DD.";
                safekup_backup_summary_log_error($errorLogger, $message);
                return [
                    'status' => 'error',
                    'target_date' => $targetDate,
                    'totals' => $totals,
                    'sent_count' => $sentCount,
                    'errors' => [$message],
                ];
            }
        } else {
            $stmt = $pdo->query('SELECT DATE(MAX(data_execucao)) FROM historico_dumps');
            $latest = $stmt->fetchColumn();
            if ($latest === null) {
                safekup_backup_summary_log_info($infoLogger, 'Nenhum registro encontrado em historico_dumps. Nada a enviar.');
                return [
                    'status' => 'skipped_no_data',
                    'target_date' => null,
                    'totals' => $totals,
                    'sent_count' => 0,
                    'errors' => [],
                ];
            }
            $targetDate = (string) $latest;
        }

        safekup_backup_summary_log_info($infoLogger, "Gerando relatório para {$targetDate}.");

        // Locate active recipients
        $recipientStmt = $pdo->query("
            SELECT d.destinatario_id,
                   d.nome,
                   d.email,
                   d.smtp_id,
                   s.smtp_nome,
                   s.smtp_email_admin,
                   s.smtp_porta,
                   s.smtp_endereco,
                   s.smtp_senha
            FROM smtp_destinatarios d
            JOIN smtp s ON s.smtp_id = d.smtp_id
            WHERE d.ativo = 1
        ");
        $recipientRows = $recipientStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($recipientRows)) {
            safekup_backup_summary_log_info($infoLogger, 'Nenhum destinatário ativo localizado. Abortando envio.');
            return [
                'status' => 'skipped_no_recipients',
                'target_date' => $targetDate,
                'totals' => $totals,
                'sent_count' => 0,
                'errors' => [],
            ];
        }

        // Group recipients by SMTP
        $smtpGroups = [];
        foreach ($recipientRows as $row) {
            $smtpId = (int) $row['smtp_id'];
            if (!isset($smtpGroups[$smtpId])) {
                $smtpGroups[$smtpId] = [
                    'config' => [
                        'smtp_nome'        => (string) ($row['smtp_nome'] ?? ''),
                        'smtp_email_admin' => (string) $row['smtp_email_admin'],
                        'smtp_porta'       => (int) $row['smtp_porta'],
                        'smtp_endereco'    => (string) $row['smtp_endereco'],
                        'smtp_senha'       => (string) $row['smtp_senha'],
                    ],
                    'recipients' => [],
                ];
            }
            $smtpGroups[$smtpId]['recipients'][] = [
                'destinatario_id' => (int) $row['destinatario_id'],
                'nome'            => (string) $row['nome'],
                'email'           => (string) $row['email'],
            ];
        }

        // Query summary data (latest entry per base for the day)
        $summarySql = "
            SELECT h.bd_nome_usuario,
                   h.bd_ip,
                   h.status,
                   h.data_execucao,
                   h.tempo_decorrido,
                   h.tamanho_arquivo,
                   h.descricao
            FROM historico_dumps h
            JOIN (
                SELECT bd_nome_usuario, MAX(data_execucao) AS max_exec
                FROM historico_dumps
                WHERE DATE(data_execucao) = :data
                GROUP BY bd_nome_usuario
            ) latest ON latest.bd_nome_usuario = h.bd_nome_usuario AND latest.max_exec = h.data_execucao
            WHERE DATE(h.data_execucao) = :data
            ORDER BY h.bd_nome_usuario
        ";

        $summaryStmt = $pdo->prepare($summarySql);
        $summaryStmt->execute(['data' => $targetDate]);
        $summaryRows = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($summaryRows)) {
            safekup_backup_summary_log_info($infoLogger, "Nenhum backup encontrado para {$targetDate}. Nada será enviado.");
            return [
                'status' => 'skipped_no_data',
                'target_date' => $targetDate,
                'totals' => $totals,
                'sent_count' => 0,
                'errors' => [],
            ];
        }

        $totals = [
            'total'   => count($summaryRows),
            'success' => count(array_filter($summaryRows, static fn($row) => strtoupper((string) ($row['status'] ?? '')) === 'OK')),
            'failure' => count(array_filter($summaryRows, static fn($row) => strtoupper((string) ($row['status'] ?? '')) !== 'OK')),
        ];

        $htmlBody = safekup_backup_summary_build_html($targetDate, $summaryRows, $totals);
        $textBody = safekup_backup_summary_build_text($targetDate, $summaryRows, $totals);
        $subject = sprintf(
            'Safekup - Resumo dos backups %s (%d/%d OK)',
            $targetDate,
            $totals['success'],
            $totals['total']
        );

        foreach ($smtpGroups as $smtpId => $group) {
            try {
                safekup_backup_summary_log_info(
                    $infoLogger,
                    sprintf(
                        'Enviando relatório usando servidor SMTP #%d para %d destinatário(s).',
                        $smtpId,
                        count($group['recipients'])
                    )
                );
                safekup_backup_summary_send_email($group['config'], $group['recipients'], $subject, $htmlBody, $textBody);
                $sentCount += count($group['recipients']);
            } catch (Exception $mailerException) {
                $message = "Falha ao enviar usando SMTP #{$smtpId}: " . $mailerException->getMessage();
                $errors[] = $message;
                safekup_backup_summary_log_error($errorLogger, $message);
            } catch (Throwable $genericException) {
                $message = "Erro inesperado no envio SMTP #{$smtpId}: " . $genericException->getMessage();
                $errors[] = $message;
                safekup_backup_summary_log_error($errorLogger, $message);
            }
        }

        $status = 'sent';
        if (!empty($errors) && $sentCount === 0) {
            $status = 'error';
        } elseif (!empty($errors)) {
            $status = 'partial';
        }

        safekup_backup_summary_log_info(
            $infoLogger,
            sprintf('Processo concluído. E-mails entregues: %d.', $sentCount)
        );

        return [
            'status' => $status,
            'target_date' => $targetDate,
            'totals' => $totals,
            'sent_count' => $sentCount,
            'errors' => $errors,
        ];
    }
}

if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    $argumentDate = null;
    foreach (array_slice($argv, 1) as $argument) {
        if (strpos($argument, '--date=') === 0) {
            $argumentDate = substr($argument, 7);
            break;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $argument)) {
            $argumentDate = $argument;
            break;
        }
    }

    $result = safekup_send_backup_summary($argumentDate);
    exit($result['status'] === 'error' ? 1 : 0);
}
