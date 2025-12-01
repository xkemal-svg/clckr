<?php
/**
 * API Kod Durumu Kontrolü
 * 
 * Bu endpoint, siteye API kodunun eklenip eklenmediğini kontrol eder
 * ve site aktif olana kadar bekler
 */

require_once __DIR__ . '/../cloacker.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: X-API-Key, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function sendJsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

try {
    // API Key kontrolü
    $apiKey = null;
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        $apiKey = $_SERVER['HTTP_X_API_KEY'];
    } elseif (isset($_GET['api_key'])) {
        $apiKey = $_GET['api_key'];
    } elseif (isset($_POST['api_key'])) {
        $apiKey = $_POST['api_key'];
    }
    
    if (empty($apiKey)) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'API anahtarı gerekli.'
        ], 401);
    }
    
    // API key'den site bilgisini al
    $pdo = DB::connect();
    $stmt = $pdo->prepare("SELECT id, site_id, is_active FROM cloacker_api_keys WHERE api_key = :key");
    $stmt->execute([':key' => $apiKey]);
    $keyData = $stmt->fetch();
    
    if (!$keyData) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Geçersiz API anahtarı.'
        ], 401);
    }
    
    $siteId = (int)$keyData['site_id'];
    
    // Site durumunu kontrol et
    $stmt = $pdo->prepare("SELECT id, name, is_active FROM cloacker_sites WHERE id = :id");
    $stmt->execute([':id' => $siteId]);
    $site = $stmt->fetch();
    
    if (!$site) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Site bulunamadı.'
        ], 404);
    }
    
    sendJsonResponse([
        'status' => 'ok',
        'site' => [
            'id' => $site['id'],
            'name' => $site['name'],
            'is_active' => (bool)$site['is_active']
        ],
        'api_key' => [
            'is_active' => (bool)$keyData['is_active']
        ],
        'ready' => (bool)$site['is_active'] && (bool)$keyData['is_active']
    ]);
    
} catch (Throwable $e) {
    error_log("Check code status error: " . $e->getMessage());
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Sunucu hatası oluştu.'
    ], 500);
}
















