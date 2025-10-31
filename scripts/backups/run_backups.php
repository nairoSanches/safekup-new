<?php
declare(strict_types=1);

require_once __DIR__ . '/send_backup_summary.php';

$legacyScript = __DIR__ . '/../../php/backup/backups_cron.php';

if (!file_exists($legacyScript)) {
    fwrite(STDERR, sprintf('[%s] Script legacy não encontrado em %s%s', date('Y-m-d H:i:s'), $legacyScript, PHP_EOL));
    exit(1);
}

require $legacyScript;

try {
    $result = safekup_send_backup_summary(
        date('Y-m-d'),
        static function (string $message): void {
            echo '[' . date('Y-m-d H:i:s') . "] RESUMO: {$message}" . PHP_EOL;
        },
        static function (string $message): void {
            fwrite(STDERR, '[' . date('Y-m-d H:i:s') . "] RESUMO ERRO: {$message}" . PHP_EOL);
        }
    );
    echo '[' . date('Y-m-d H:i:s') . sprintf('] RESUMO: status=%s enviados=%d', $result['status'], $result['sent_count']) . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] RESUMO ERRO: ' . $exception->getMessage() . PHP_EOL);
}
