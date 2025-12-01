<?php
/**
 * Cloaker API - PHP Entegrasyon Örneği
 * 
 * Bu dosyayı sitenizin ana sayfasına ekleyebilirsiniz
 */

// ⚠️ BURAYA KENDİ API KEY'İNİZİ GİRİN
define('CLOAKER_API_KEY', 'YOUR_API_KEY_HERE');
define('CLOAKER_API_URL', 'https://yourdomain.com/api/cloaker_api.php');

/**
 * Cloaker API'ye istek gönder
 */
function checkVisitorWithCloaker() {
    $ch = curl_init(CLOAKER_API_URL);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'X-API-Key: ' . CLOAKER_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log('Cloaker API cURL Error: ' . $error);
        return null;
    }
    
    if ($httpCode !== 200) {
        error_log('Cloaker API HTTP Error: ' . $httpCode);
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || $data['status'] !== 'ok') {
        error_log('Cloaker API Response Error: ' . ($data['message'] ?? 'Unknown'));
        return null;
    }
    
    return $data;
}

// Ziyaretçiyi kontrol et
$cloakerData = checkVisitorWithCloaker();

if ($cloakerData) {
    // Bot/VPN tespit edildi mi?
    if (!$cloakerData['allowed']) {
        // Fake sayfaya yönlendir
        header('Location: ' . $cloakerData['redirect_url']);
        exit;
    }
    
    // Normal ziyaretçi - sayfayı göster
    // İsterseniz normal sayfaya da yönlendirebilirsiniz:
    // header('Location: ' . $cloakerData['redirect_url']);
    // exit;
} else {
    // API hatası - varsayılan davranış
    // Normal sayfayı göster veya hata sayfası göster
}

// Normal sayfa içeriği buradan devam eder
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Normal Sayfa</title>
</head>
<body>
    <h1>Hoş Geldiniz!</h1>
    <p>Bu normal ziyaretçiler için gösterilen sayfadır.</p>
    
    <?php if ($cloakerData): ?>
        <div style="background: #e3f2fd; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3>Ziyaretçi Bilgileri (Debug):</h3>
            <p><strong>IP:</strong> <?= htmlspecialchars($cloakerData['visitor']['ip']) ?></p>
            <p><strong>Ülke:</strong> <?= htmlspecialchars($cloakerData['visitor']['country']) ?></p>
            <p><strong>OS:</strong> <?= htmlspecialchars($cloakerData['visitor']['os']) ?></p>
            <p><strong>Tarayıcı:</strong> <?= htmlspecialchars($cloakerData['visitor']['browser']) ?></p>
            <p><strong>Bot Güven Skoru:</strong> <?= $cloakerData['detection']['bot_confidence'] ?>%</p>
        </div>
    <?php endif; ?>
</body>
</html>
















