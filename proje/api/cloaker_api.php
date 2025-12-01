<?php
require_once __DIR__ . '/../cloacker.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: X-API-Key, X-Visitor-IP, X-Visitor-UA, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$rawInput = file_get_contents('php://input');
$jsonBody = json_decode($rawInput, true);

function sendJsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

try {
    $apiKey = $_SERVER['HTTP_X_API_KEY']
        ?? ($_GET['api_key'] ?? ($_POST['api_key'] ?? ($jsonBody['api_key'] ?? null)));

    if (empty($apiKey)) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'API anahtarı gerekli. X-API-Key headerı veya api_key parametresi gönderin.'
        ], 401);
    }

    $pdo = DB::connect();
    $stmt = $pdo->prepare("SELECT id, site_id, is_active FROM cloacker_api_keys WHERE api_key = :key LIMIT 1");
    $stmt->execute([':key' => $apiKey]);
    $apiKeyRow = $stmt->fetch();

    if (!$apiKeyRow) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'API anahtarı doğrulanamadı.'
        ], 403);
    }

    if (empty($apiKeyRow['is_active'])) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'API anahtarı pasif. Lütfen yönetim panelinden aktifleştirin.'
        ], 403);
    }

    $keySiteId = (int)($apiKeyRow['site_id'] ?? 0);
    if ($keySiteId <= 0) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'API anahtarına bağlı aktif bir site bulunamadı.'
        ], 400);
    }

    $siteId = $keySiteId;

    $siteProbe = resolveSiteConfiguration($siteId, null);
    if (($siteProbe['site_id'] ?? null) !== $siteId) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Site pasif veya silinmiş görünüyor. Lütfen yönetim panelinden kontrol edin.'
        ], 409);
    }

$visitorIpHeader = $_SERVER['HTTP_X_VISITOR_IP']
    ?? ($_GET['visitor_ip'] ?? ($_POST['visitor_ip'] ?? ($jsonBody['visitor_ip'] ?? ($jsonBody['ip'] ?? null))));
$visitorIp = null;
if ($visitorIpHeader && filter_var($visitorIpHeader, FILTER_VALIDATE_IP)) {
    // Sadece public IP'leri override olarak kabul et; aksi halde sistem gerçek IP'yi kendisi çözer.
    if (filter_var($visitorIpHeader, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $visitorIp = $visitorIpHeader;
    }
}

    $visitorUA = $_SERVER['HTTP_X_VISITOR_UA']
        ?? ($_GET['visitor_ua'] ?? ($_POST['visitor_ua'] ?? ($jsonBody['visitor_ua'] ?? ($jsonBody['user_agent'] ?? null))));

    $decision = cloaker_decision(true, false, $siteId, $apiKey, [
        'override_ip' => $visitorIp,
        'override_user_agent' => $visitorUA,
        'api_key_context' => [
            'id' => (int)$apiKeyRow['id'],
            'site_id' => $siteId
        ],
        'request_source' => 'api',
    ]);

    if (($decision['site_id'] ?? null) !== $siteId) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Site ayarlarına erişilemedi. Lütfen domain ve site durumunu kontrol edin.'
        ], 409);
    }

    sendJsonResponse([
        'status' => 'ok',
        'allowed' => $decision['allowed'],
        'redirect_url' => $decision['redirect_url'],
        'redirect_target' => $decision['redirect_target'],
        'detection' => [
            'is_bot' => $decision['bot'],
            'is_proxy' => $decision['proxy'],
            'bot_confidence' => $decision['bot_confidence'],
            'fingerprint_score' => $decision['fingerprint_score'],
            'signals' => $decision['signals']
        ],
        'visitor' => [
            'ip' => $decision['ip'],
            'country' => $decision['country'],
            'os' => $decision['os'],
            'browser' => $decision['browser']
        ]
    ]);

} catch (Throwable $e) {
    error_log("Cloaker API hatası: " . $e->getMessage());
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Sunucu hatası oluştu.'
    ], 500);
}
