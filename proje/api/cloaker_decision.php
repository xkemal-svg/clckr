<?php

require_once __DIR__ . '/../cloacker.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $siteId = null;
    if (isset($_GET['site_id'])) {
        $siteId = (int)$_GET['site_id'];
    } elseif (isset($_POST['site_id'])) {
        $siteId = (int)$_POST['site_id'];
    }

    $originHost = $_GET['origin'] ?? $_POST['origin'] ?? null;
    if (is_string($originHost) && $originHost !== '') {
        $originHost = normalizeDomain($originHost);
    } else {
        $originHost = null;
    }

    $visitorIp = $_GET['visitor_ip'] ?? $_POST['visitor_ip'] ?? null;
    if ($visitorIp && !filter_var($visitorIp, FILTER_VALIDATE_IP)) {
        $visitorIp = null;
    }

    $visitorUa = $_GET['visitor_ua'] ?? $_POST['visitor_ua'] ?? null;

    $decision = cloaker_decision(true, true, $siteId ?: null, null, [
        'host' => $originHost ?: ($_SERVER['HTTP_HOST'] ?? null),
        'override_ip' => $visitorIp,
        'override_user_agent' => $visitorUa,
    ]);

    echo json_encode([
        'status' => 'ok',
        'allowed' => $decision['allowed'],
        'redirect_url' => $decision['redirect_url'],
        'redirect_target' => $decision['redirect_target'],
        'meta' => [
            'ip' => $decision['ip'],
            'country' => $decision['country'],
            'os' => $decision['os'],
            'browser' => $decision['browser'],
            'is_bot' => $decision['bot'],
            'is_proxy' => $decision['proxy'],
            'signals' => $decision['signals'],
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $e) {
    http_response_code(409);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'İşlem sırasında hata oluştu.'
    ], JSON_UNESCAPED_UNICODE);
}

?>