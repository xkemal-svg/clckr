<?php
require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/dashboard_metrics.php';

enforceAdminSession(true);

header('Content-Type: application/json; charset=utf-8');

try {
    $metrics = getDashboardMetrics();
    echo json_encode([
        'status' => 'ok',
        'data' => $metrics
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Veri alınamadı'
    ], JSON_UNESCAPED_UNICODE);
}
?>














