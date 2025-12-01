<?php
require_once __DIR__ . '/../error_logger.php';
require_once __DIR__ . '/../cloacker.php'; // Includes DB and logVisitor function

set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo = DB::connect();

function normalizeUrl($url) {
    $parts = parse_url($url);
    $host = strtolower($parts['host'] ?? '');
    $path = rtrim($parts['path'] ?? '/', '/');
    return $host . $path;
}

function isUrlEqual($url1, $url2) {
    return normalizeUrl($url1) === normalizeUrl($url2);
}

$stmtSettings = $pdo->prepare("SELECT normal_url, fake_url FROM cloacker_settings WHERE id=1");
$stmtSettings->execute();
$settings = $stmtSettings->fetch();

if (!$settings) {
    echo "Ayarlar bulunamadı.\n";
    exit(1);
}

while (true) {
    $stmt = $pdo->prepare("SELECT * FROM cloacker_bots WHERE active=1 ORDER BY id ASC");
    $stmt->execute();
    $bots = $stmt->fetchAll();

    if (!$bots) {
        echo "[" . date('Y-m-d H:i:s') . "] Aktif bot bulunamadı. 30 saniye bekleniyor...\n";
        sleep(30);
        continue;
    }

    foreach ($bots as $bot) {
        $targetUrl = $settings['fake_url'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $bot['user_agent']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        curl_close($ch);

        $redirectType = 'error';

        if ($httpCode >= 200 && $httpCode < 400) {
            if (isUrlEqual($finalUrl, $settings['normal_url'])) {
                $redirectType = 'normal';
            } elseif (isUrlEqual($finalUrl, $settings['fake_url'])) {
                $redirectType = 'fake';
            } else {
                $redirectType = 'error';
            }
        }

        // Insert bot stats
        $stmtInsertStat = $pdo->prepare("INSERT INTO cloacker_bot_stats (bot_id, visit_time, redirect_type, response_code) VALUES (:bot_id, NOW(), :redirect_type, :response_code)");
        $stmtInsertStat->execute([
            ':bot_id' => $bot['id'],
            ':redirect_type' => $redirectType,
            ':response_code' => $httpCode
        ]);

        // Log bot as visitor in cloacker_visitors table
        $botIp = '127.0.0.1'; // Or set a fixed IP or fetch real IP if applicable
        $botUserAgent = $bot['user_agent'];
        $botCountry = 'UN'; // Unknown or set as needed
        $botOS = 'bot';
        $botBrowser = 'bot';
        $botReferer = '';
        $botIsProxy = false;
        $botIsBot = true;
        $botRedirectTarget = $redirectType;
        $botIsFakeUrl = ($redirectType === 'fake') ? true : false;

        logVisitor([
            'ip' => $botIp,
            'user_agent' => $botUserAgent,
            'country' => $botCountry,
            'os' => $botOS,
            'browser' => $botBrowser,
            'referer' => $botReferer,
            'proxy_vpn' => $botIsProxy,
            'bot' => $botIsBot,
            'redirect_target' => $botRedirectTarget,
            'is_fake_url' => $botIsFakeUrl,
        ]);

        echo "[" . date('Y-m-d H:i:s') . "] Bot '{$bot['bot_name']}' hedef URL'ye istek yaptı. HTTP: $httpCode, Yönlendirme: $redirectType\n";

        // Delay süre kadar bekle (minimum 100 ms)
        usleep(max(100, (int)$bot['delay_ms']) * 1000);
    }
}