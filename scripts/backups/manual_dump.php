<?php
declare(strict_types=1);

/**
 * Executa o shell script responsável por disparar o dump imediato de um banco.
 *
 * Parâmetros (JSON / POST):
 *  - bd_id (int) — identificador do banco na tabela db_management.
 *
 * Retorno (JSON):
 *  {
 *      "status": "ok"|"error",
 *      "message": "string"
 *  }
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método não permitido. Use POST.'
    ]);
    exit;
}

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $input = $decoded;
        }
    }
}

$bdId = isset($input['bd_id']) ? (int) $input['bd_id'] : 0;
if ($bdId <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Identificador do banco inválido.'
    ]);
    exit;
}

$scriptPath = realpath(__DIR__ . '/manual_dump.sh');
if ($scriptPath === false || !is_file($scriptPath) || !is_executable($scriptPath)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Script de dump manual não encontrado ou sem permissão de execução.'
    ]);
    exit;
}

$descriptorSpec = [
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open(
    escapeshellcmd($scriptPath) . ' ' . escapeshellarg((string) $bdId),
    $descriptorSpec,
    $pipes,
    $_SERVER['DOCUMENT_ROOT'] ?? __DIR__
);

if (!is_resource($process)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Não foi possível iniciar o processo de dump.'
    ]);
    exit;
}

$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);

$exitCode = proc_close($process);

if ($exitCode === 0) {
    echo json_encode([
        'status' => 'ok',
        'message' => trim($stdout) !== '' ? trim($stdout) : 'Dump executado com sucesso.'
    ]);
} else {
    http_response_code(500);
    $message = trim($stderr) !== '' ? trim($stderr) : 'Falha ao executar dump.';
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
}
