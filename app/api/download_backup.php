<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

try {
    $pdo = safekup_db();
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Falha ao conectar ao banco de dados.';
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo 'Requisição inválida.';
    exit;
}

$stmt = $pdo->prepare('
    SELECT
        h.arquivo_backup,
        h.data_execucao,
        h.bd_nome_usuario,
        h.bd_ip,
        t.tipo_nome
    FROM historico_dumps h
    LEFT JOIN db_management dbm ON h.bd_id = dbm.bd_id
    LEFT JOIN tipo t ON dbm.bd_tipo = t.tipo_id
    WHERE h.id = :id
    LIMIT 1
');
$stmt->execute([':id' => $id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    http_response_code(404);
    echo 'Registro não encontrado.';
    exit;
}

$baseDir = '/DBbackup';
$baseDirReal = realpath($baseDir) ?: $baseDir;

$filePath = trim((string) ($record['arquivo_backup'] ?? ''));

if ($filePath === '') {
    $dbIp = trim((string) ($record['bd_ip'] ?? ''));
    $tipoNome = trim((string) ($record['tipo_nome'] ?? ''));
    $dbName = trim((string) ($record['bd_nome_usuario'] ?? ''));
    $dataExecucao = $record['data_execucao'] ?? null;

    if ($dbIp !== '' && $tipoNome !== '' && $dbName !== '' && $dataExecucao) {
        try {
            $date = new DateTimeImmutable($dataExecucao);
            $timestamp = $date->format('Y-m-d_H-i-s');
            $dir = sprintf('%s/%s/%s/%s', $baseDir, $dbIp, $tipoNome, $dbName);
            $pattern = $dir . '/backup_' . $timestamp . '.*';
            $matches = glob($pattern);
            if (!empty($matches)) {
                $filePath = $matches[0];
            }
        } catch (Throwable $exception) {
            // Ignora falhas na tentativa de construir o caminho
        }
    }
}

if (!is_readable($filePath) || !is_file($filePath)) {
    http_response_code(404);
    echo 'Arquivo de backup indisponível.';
    exit;
}

$realPath = realpath($filePath);
if ($realPath === false || strpos($realPath, rtrim($baseDirReal, '/') . '/') !== 0) {
    http_response_code(403);
    echo 'Acesso negado ao arquivo solicitado.';
    exit;
}

$fileName = basename($filePath);
$fileName = str_replace(['"', '\\'], '', $fileName);
$fileSize = filesize($filePath);

set_time_limit(0);
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . (string) $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

flush();
readfile($filePath);
exit;
