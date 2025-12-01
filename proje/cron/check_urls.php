<?php
/**
 * URL Kontrolü ve Telegram Bildirimi
 * 
 * Bu dosyayı cron job olarak çalıştırın:
 * */5 * * * * php /path/to/cron/check_urls.php
 * (Her 5 dakikada bir)
 */

require_once __DIR__ . '/../cloacker.php';

function sendTelegramMessage($botToken, $chatId, $message) {
    if (empty($botToken) || empty($chatId)) {
        return false;
    }
    
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

function checkUrl($url) {
    if (empty($url)) {
        return false;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // 200-399 arası başarılı sayılır
    return ($httpCode >= 200 && $httpCode < 400) && empty($error);
}

try {
    $pdo = DB::connect();
    
    // Tüm aktif siteleri al
    $stmt = $pdo->query("SELECT id, name, normal_url, fake_url, settings FROM cloacker_sites WHERE is_active = 1");
    $sites = $stmt->fetchAll();
    
    foreach ($sites as $site) {
        $settings = !empty($site['settings']) ? json_decode($site['settings'], true) : [];
        $botToken = $settings['telegram_bot_token'] ?? '';
        $chatId = $settings['telegram_chat_id'] ?? '';
        
        $normalUrl = $site['normal_url'];
        $fakeUrl = $site['fake_url'];
        
        // Normal URL kontrolü
        $normalOk = checkUrl($normalUrl);
        if (!$normalOk) {
            $message = "⚠️ <b>URL Kontrol Uyarısı</b>\n\n";
            $message .= "Site: <b>{$site['name']}</b>\n";
            $message .= "Normal URL çalışmıyor!\n";
            $message .= "URL: <code>{$normalUrl}</code>\n";
            $message .= "Zaman: " . date('Y-m-d H:i:s');
            
            if (!empty($botToken) && !empty($chatId)) {
                sendTelegramMessage($botToken, $chatId, $message);
            }
            
            error_log("Cloaker: Normal URL çalışmıyor - Site: {$site['name']}, URL: {$normalUrl}");
        }
        
        // Fake URL kontrolü
        $fakeOk = checkUrl($fakeUrl);
        if (!$fakeOk) {
            $message = "⚠️ <b>URL Kontrol Uyarısı</b>\n\n";
            $message .= "Site: <b>{$site['name']}</b>\n";
            $message .= "Fake URL çalışmıyor!\n";
            $message .= "URL: <code>{$fakeUrl}</code>\n";
            $message .= "Zaman: " . date('Y-m-d H:i:s');
            
            if (!empty($botToken) && !empty($chatId)) {
                sendTelegramMessage($botToken, $chatId, $message);
            }
            
            error_log("Cloaker: Fake URL çalışmıyor - Site: {$site['name']}, URL: {$fakeUrl}");
        }
        
        // Son kontrol zamanını kaydet
        $pdo->prepare("UPDATE cloacker_sites SET updated_at = NOW() WHERE id = :id")->execute([':id' => $site['id']]);
    }
    
    echo "URL kontrolü tamamlandı: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    error_log("URL kontrol hatası: " . $e->getMessage());
    echo "Hata: " . $e->getMessage() . "\n";
}
















