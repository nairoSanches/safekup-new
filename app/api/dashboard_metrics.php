<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/dashboard_metrics.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = safekup_db();
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Falha ao conectar ao banco de dados.',
    ]);
    exit;
}

$rangeOptions = safekup_dashboard_range_options();
$defaultRange = safekup_dashboard_default_range();
$range = isset($_GET['range']) ? (int) $_GET['range'] : $defaultRange;
if (!array_key_exists($range, $rangeOptions)) {
    $range = $defaultRange;
}

$metrics = safekup_dashboard_metrics($pdo, $range);

echo json_encode([
    'status' => $metrics['error'] === null ? 'ok' : 'error',
    'data' => $metrics,
]);
