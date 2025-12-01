<?php
/**
 * Bot Simulasyonu Sayfası
 * Gerçek platform botlarını simüle ederek sistemi test eder
 */

require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../cloacker.php';

enforceAdminSession();

$pdo = DB::connect();
$error = '';
$success = '';
$testResults = [];

// Log dosyası yolu
$logFile = __DIR__ . '/../logs/bot_simulation.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

// Log okuma fonksiyonu
function readBotSimulationLogs($logFile) {
    if (!file_exists($logFile)) {
        return [];
    }
    $content = file_get_contents($logFile);
    if (empty($content)) {
        return [];
    }
    $lines = explode("\n", trim($content));
    $logs = [];
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        $data = json_decode($line, true);
        if ($data) {
            $logs[] = $data;
        }
    }
    // En yeni önce
    return array_reverse($logs);
}

// Log yazma fonksiyonu
function writeBotSimulationLog($logFile, $data) {
    $logEntry = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Log silme
if (isset($_GET['clear_logs'])) {
    if (file_exists($logFile)) {
        @unlink($logFile);
        $success = "Tüm loglar silindi";
    } else {
        $error = "Log dosyası bulunamadı";
    }
}

// Logları oku
$logs = readBotSimulationLogs($logFile);

// Gerçek platform botları
$platformBots = [
    'google' => [
        'name' => 'Googlebot',
        'user_agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'ip' => '66.249.66.1', // Googlebot IP örneği
        'description' => 'Google arama botu - rDNS ile doğrulanmalı',
        'expected_result' => 'Fake (Bot tespit edilmeli)'
    ],
    'google_ads' => [
        'name' => 'Google Ads Bot',
        'user_agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'ip' => '66.249.66.1',
        'description' => 'Google Ads reklam onay botu',
        'expected_result' => 'Fake (Bot tespit edilmeli)'
    ],
    'facebook' => [
        'name' => 'Facebook Bot',
        'user_agent' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
        'ip' => '31.13.24.0', // Facebook IP örneği
        'description' => 'Facebook link preview botu',
        'expected_result' => 'Fake (Bot tespit edilmeli)'
    ],
    'tiktok' => [
        'name' => 'TikTok Bot',
        'user_agent' => 'Mozilla/5.0 (compatible; TikTokBot/1.0; +https://www.tiktok.com/help/article/verify-tiktok-bot)',
        'ip' => '103.27.148.0', // TikTok IP örneği
        'description' => 'TikTok link preview botu',
        'expected_result' => 'Fake (Bot tespit edilmeli)'
    ],
    'twitter' => [
        'name' => 'Twitter Bot',
        'user_agent' => 'Twitterbot/1.0',
        'ip' => '199.16.156.0', // Twitter IP örneği
        'description' => 'Twitter link preview botu',
        'expected_result' => 'Fake (Bot tespit edilmeli)'
    ],
    'linkedin' => [
        'name' => 'LinkedIn Bot',
        'user_agent' => 'LinkedInBot/1.0 (compatible; Mozilla/5.0; +http://www.linkedin.com)',
        'ip' => '108.174.10.0', // LinkedIn IP örneği
        'description' => 'LinkedIn link preview botu',
        'expected_result' => 'Fake (Bot tespit edilmeli)'
    ],
    'bing' => [
        'name' => 'Bingbot',
        'user_agent' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        'ip' => '157.55.39.0', // Bing IP örneği
        'description' => 'Bing arama botu',
        'expected_result' => 'Fake (Bot tespit edilmeli)'
    ],
    'pinterest' => [
        'name' => 'Pinterest Bot',
        'user_agent' => 'Pinterest/0.2 (+http://www.pinterest.com/bot.html)',
        'ip' => '54.236.1.0', // Pinterest IP örneği
        'description' => 'Pinterest link preview botu',
        'expected_result' => 'Fake (Bot tespit edilmeli)'
    ],
    'instagram' => [
        'name' => 'Instagram Bot',
        'user_agent' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
        'ip' => '31.13.24.0',
        'description' => 'Instagram link preview botu (Facebook ile aynı)',
        'expected_result' => 'Fake (Bot tespit edilmeli)'
    ],
    'whatsapp' => [
        'name' => 'WhatsApp Bot',
        'user_agent' => 'WhatsApp/2.0',
        'ip' => '31.13.24.0',
        'description' => 'WhatsApp link preview botu',
        'expected_result' => 'Fake (Bot tespit edilmeli)'
    ],
];

// Hedef URL ayarları
$targetUrl = isset($_GET['target_url']) ? trim($_GET['target_url']) : '';
if (empty($targetUrl) && isset($_POST['target_url'])) {
    $targetUrl = trim($_POST['target_url']);
}

// Test çalıştırma
if (isset($_GET['run_test']) && isset($_GET['bot'])) {
    $botKey = $_GET['bot'];
    if (!isset($platformBots[$botKey])) {
        $error = 'Geçersiz bot seçimi';
    } else {
        $bot = $platformBots[$botKey];
        
        try {
            // Hedef URL kontrolü
            $testUrl = !empty($targetUrl) ? $targetUrl : ($_SERVER['HTTP_HOST'] ?? 'localhost');
            if (!filter_var($testUrl, FILTER_VALIDATE_URL) && !preg_match('/^[a-zA-Z0-9.-]+$/', $testUrl)) {
                // URL değilse, HTTP ekle
                if (!preg_match('#^https?://#', $testUrl)) {
                    $testUrl = 'http://' . $testUrl;
                }
            }
            
            // Cloaker decision'ı test et
            // NOT: Botların proxy olarak görünmemesi için özel IP kullanıyoruz
            // Gerçek bot IP'leri kullanıldığında proxy tespiti yapılabilir
            // Bu yüzden test için localhost IP kullanıyoruz veya proxy kontrolünü bypass ediyoruz
            
            $decision = cloaker_decision(true, false, null, null, [
                'override_ip' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1', // Sunucu IP'si kullan (proxy olarak görünmesin)
                'override_user_agent' => $bot['user_agent'],
                'client_fingerprints' => [
                    // Botlar genelde fingerprint göndermez
                    'canvas' => null,
                    'webgl' => null,
                    'audio' => null,
                    'challenge' => null, // Challenge çözemez
                ],
                'skip_proxy_check' => true, // Test için proxy kontrolünü atla
                'target_url' => $testUrl, // Hedef URL'yi geçir
            ]);
            
            $testResult = [
                'bot' => $bot,
                'decision' => $decision,
                'success' => true,
                'message' => 'Test tamamlandı',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $testResults[$botKey] = $testResult;
            
            // Log'a kaydet
            writeBotSimulationLog($logFile, [
                'type' => 'single_test',
                'bot_key' => $botKey,
                'bot_name' => $bot['name'],
                'user_agent' => $bot['user_agent'],
                'target_url' => $testUrl,
                'ip' => $decision['ip'] ?? null,
                'bot_detected' => $decision['bot'] ?? false,
                'redirect_target' => $decision['redirect_target'] ?? null,
                'redirect_url' => $decision['redirect_url'] ?? null,
                'is_fake_url' => $decision['is_fake_url'] ?? false,
                'rdns_hostname' => $decision['rdns_hostname'] ?? null,
                'rdns_is_bot' => $decision['rdns_is_bot'] ?? false,
                'fingerprint_similarity' => $decision['fingerprint_similarity'] ?? null,
                'behavioral_bot_score' => $decision['behavioral_bot_score'] ?? null,
                'fingerprint_score' => $decision['fingerprint_score'] ?? null,
                'bot_confidence' => $decision['bot_confidence'] ?? null,
                'ml_confidence' => $decision['ml_confidence'] ?? null,
                'is_proxy' => $decision['proxy'] ?? false,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            $success = "Bot testi tamamlandı: {$bot['name']}";
        } catch (Exception $e) {
            $testResults[$botKey] = [
                'bot' => $bot,
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $error = "Test hatası: " . $e->getMessage();
        }
    }
}

// Tüm botları test et
if (isset($_GET['run_all_tests'])) {
    // Hedef URL kontrolü
    $testUrl = !empty($targetUrl) ? $targetUrl : ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!filter_var($testUrl, FILTER_VALIDATE_URL) && !preg_match('/^[a-zA-Z0-9.-]+$/', $testUrl)) {
        if (!preg_match('#^https?://#', $testUrl)) {
            $testUrl = 'http://' . $testUrl;
        }
    }
    
    foreach ($platformBots as $key => $bot) {
        try {
            $decision = cloaker_decision(true, false, null, null, [
                'override_ip' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
                'override_user_agent' => $bot['user_agent'],
                'client_fingerprints' => [
                    'canvas' => null,
                    'webgl' => null,
                    'audio' => null,
                    'challenge' => null,
                ],
                'skip_proxy_check' => true,
                'target_url' => $testUrl,
            ]);
            
            $testResult = [
                'bot' => $bot,
                'decision' => $decision,
                'success' => true,
                'message' => 'Test tamamlandı',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $testResults[$key] = $testResult;
            
            // Log'a kaydet
            writeBotSimulationLog($logFile, [
                'type' => 'batch_test',
                'bot_key' => $key,
                'bot_name' => $bot['name'],
                'user_agent' => $bot['user_agent'],
                'target_url' => $testUrl,
                'ip' => $decision['ip'] ?? null,
                'bot_detected' => $decision['bot'] ?? false,
                'redirect_target' => $decision['redirect_target'] ?? null,
                'redirect_url' => $decision['redirect_url'] ?? null,
                'is_fake_url' => $decision['is_fake_url'] ?? false,
                'rdns_hostname' => $decision['rdns_hostname'] ?? null,
                'rdns_is_bot' => $decision['rdns_is_bot'] ?? false,
                'fingerprint_similarity' => $decision['fingerprint_similarity'] ?? null,
                'behavioral_bot_score' => $decision['behavioral_bot_score'] ?? null,
                'fingerprint_score' => $decision['fingerprint_score'] ?? null,
                'bot_confidence' => $decision['bot_confidence'] ?? null,
                'ml_confidence' => $decision['ml_confidence'] ?? null,
                'is_proxy' => $decision['proxy'] ?? false,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            $testResults[$key] = [
                'bot' => $bot,
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    $success = "Tüm bot testleri tamamlandı";
}

render_admin_layout_start('Bot Simulasyonu', 'bot_simulation');
?>

<?php if ($error): ?>
    <div class="mb-4 p-3 rounded glass-card border border-red-500/30 text-red-400"><?=htmlspecialchars($error)?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="mb-4 p-3 rounded glass-card border border-cyan-500/30 text-cyan-400"><?=htmlspecialchars($success)?></div>
<?php endif; ?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-white mb-2">🤖 Bot Simulasyonu</h1>
    <p class="text-gray-400">Gerçek platform botlarını simüle ederek sistemin bot tespit yeteneğini test edin</p>
</div>

<!-- Hedef URL Girişi -->
<div class="mb-6 glass-card rounded-xl border border-cyan-500/20 p-6">
    <h2 class="text-xl font-semibold text-white mb-4">🎯 Hedef URL</h2>
    <form method="GET" action="" class="flex gap-4 items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-300 mb-2">
                Test Edilecek URL (Botlar bu URL'yi ziyaret edecek)
            </label>
            <input type="text" name="target_url" 
                   value="<?= htmlspecialchars($targetUrl) ?>" 
                   placeholder="https://example.com veya example.com"
                   class="w-full px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white placeholder-gray-500">
            <p class="text-xs text-gray-400 mt-1">
                Cloaker sisteminizi test etmek için bir URL girin. Botlar bu URL'yi ziyaret edecek ve sisteminiz bot tespiti yapacak.
            </p>
        </div>
        <div>
            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-lg hover:from-cyan-600 hover:to-teal-600 transition font-medium">
                URL'yi Kaydet
            </button>
        </div>
    </form>
    <?php if (!empty($targetUrl)): ?>
        <div class="mt-4 p-3 bg-cyan-500/10 border border-cyan-500/30 rounded-lg">
            <p class="text-sm text-cyan-300">
                <strong>Hedef URL:</strong> <span class="font-mono"><?= htmlspecialchars($targetUrl) ?></span>
            </p>
        </div>
    <?php endif; ?>
</div>

<div class="mb-6">
    <a href="?run_all_tests=1<?= !empty($targetUrl) ? '&target_url=' . urlencode($targetUrl) : '' ?>" class="inline-block bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-6 py-2 rounded-lg mr-2">
        Tüm Botları Test Et
    </a>
    <span class="text-gray-400 text-sm">Tüm platform botlarını tek seferde test eder</span>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($platformBots as $key => $bot): ?>
        <div class="glass-card rounded-xl border border-cyan-500/20 p-4">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h3 class="text-lg font-semibold text-white"><?= htmlspecialchars($bot['name']) ?></h3>
                    <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($bot['description']) ?></p>
                </div>
                <a href="?run_test=1&bot=<?= $key ?><?= !empty($targetUrl) ? '&target_url=' . urlencode($targetUrl) : '' ?>" class="bg-cyan-500 hover:bg-cyan-600 text-white px-3 py-1 rounded text-sm">
                    Test Et
                </a>
            </div>
            
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-gray-500">User-Agent:</span>
                    <div class="text-gray-300 font-mono text-xs mt-1 break-all"><?= htmlspecialchars($bot['user_agent']) ?></div>
                </div>
                <div>
                    <span class="text-gray-500">Beklenen:</span>
                    <span class="text-yellow-400"><?= htmlspecialchars($bot['expected_result']) ?></span>
                </div>
            </div>
            
            <?php if (isset($testResults[$key])): ?>
                <?php $result = $testResults[$key]; ?>
                <div class="mt-4 p-3 rounded-lg <?= $result['success'] ? 'bg-green-900/30 border border-green-500/30' : 'bg-red-900/30 border border-red-500/30' ?>">
                    <div class="text-sm">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-400">Durum:</span>
                            <span class="<?= $result['success'] ? 'text-green-400' : 'text-red-400' ?>">
                                <?= $result['success'] ? '✓ Başarılı' : '✗ Hata' ?>
                            </span>
                        </div>
                        <?php if ($result['success'] && isset($result['decision'])): ?>
                            <div class="space-y-1 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Bot Tespit:</span>
                                    <span class="<?= $result['decision']['bot'] ? 'text-red-400' : 'text-green-400' ?>">
                                        <?= $result['decision']['bot'] ? '✓ Tespit Edildi' : '✗ Tespit Edilmedi' ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Yönlendirme:</span>
                                    <?php 
                                    $isFake = ($result['decision']['redirect_target'] === 'fake' || ($result['decision']['is_fake_url'] ?? false));
                                    ?>
                                    <span class="<?= $isFake ? 'text-red-400' : 'text-green-400' ?>">
                                        <?= $isFake ? 'Fake Sayfa' : 'Normal Sayfa' ?>
                                    </span>
                                </div>
                                <?php if (isset($result['decision']['is_fake_url'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Gerçek Yönlendirme:</span>
                                    <span class="<?= $result['decision']['is_fake_url'] ? 'text-red-400 font-bold' : 'text-green-400 font-bold' ?>">
                                        <?= $result['decision']['is_fake_url'] ? '✅ Fake URL\'ye Yönlendirildi' : '✅ Normal URL\'ye Yönlendirildi' ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <?php if (isset($result['decision']['redirect_url'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Yönlendirme URL:</span>
                                    <span class="text-cyan-400 text-xs font-mono break-all">
                                        <?= htmlspecialchars($result['decision']['redirect_url']) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Fingerprint Skoru:</span>
                                    <span class="text-cyan-400 font-semibold">
                                        <?= $result['decision']['fingerprint_score'] ?? 0 ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Bot Confidence:</span>
                                    <span class="text-yellow-400">
                                        <?= $result['decision']['bot_confidence'] ? number_format($result['decision']['bot_confidence'], 2) . '%' : 'N/A' ?>
                                    </span>
                                </div>
                                <?php if (isset($result['decision']['ml_confidence']) && $result['decision']['ml_confidence'] !== null): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">ML Confidence:</span>
                                    <span class="text-blue-400">
                                        <?= number_format((float)$result['decision']['ml_confidence'], 2) ?>%
                                    </span>
                                </div>
                                <?php endif; ?>
                                <?php if (isset($result['decision']['rdns_hostname']) && $result['decision']['rdns_hostname']): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">rDNS:</span>
                                        <span class="text-cyan-400 text-xs font-mono">
                                            <?= htmlspecialchars($result['decision']['rdns_hostname']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Fingerprint Similarity:</span>
                                    <?php if (isset($result['decision']['fingerprint_similarity']) && $result['decision']['fingerprint_similarity'] !== null): ?>
                                        <span class="text-purple-400">
                                            <?= number_format((float)$result['decision']['fingerprint_similarity'] * 100, 2) ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-500 text-xs">Hesaplanıyor...</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($result['decision']['behavioral_bot_score']) && $result['decision']['behavioral_bot_score'] !== null): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Behavioral Score:</span>
                                        <span class="text-orange-400">
                                            <?= number_format($result['decision']['behavioral_bot_score'], 2) ?>%
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Proxy/VPN:</span>
                                    <span class="<?= ($result['decision']['proxy'] ?? false) ? 'text-yellow-400' : 'text-green-400' ?>">
                                        <?= ($result['decision']['proxy'] ?? false) ? 'Tespit Edildi' : 'Tespit Edilmedi' ?>
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-red-400 text-xs"><?= htmlspecialchars($result['message']) ?></div>
                        <?php endif; ?>
                        <div class="text-xs text-gray-500 mt-2"><?= htmlspecialchars($result['timestamp']) ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-8 glass-card rounded-xl border border-cyan-500/20 p-6">
    <h2 class="text-xl font-semibold mb-4 text-white">📊 Test Sonuçları Özeti</h2>
    
    <?php if (empty($testResults)): ?>
        <p class="text-gray-400">Henüz test çalıştırılmadı. Yukarıdaki botlardan birini seçerek test edin.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-700">
                        <th class="text-left p-2 text-gray-400">Bot</th>
                        <th class="text-left p-2 text-gray-400">Durum</th>
                        <th class="text-left p-2 text-gray-400">Bot Tespit</th>
                        <th class="text-left p-2 text-gray-400">Yönlendirme</th>
                        <th class="text-left p-2 text-gray-400">FP Skoru</th>
                        <th class="text-left p-2 text-gray-400">Bot Confidence</th>
                        <th class="text-left p-2 text-gray-400">ML Confidence</th>
                        <th class="text-left p-2 text-gray-400">rDNS</th>
                        <th class="text-left p-2 text-gray-400">Fingerprint Similarity</th>
                        <th class="text-left p-2 text-gray-400">Behavioral Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testResults as $key => $result): ?>
                        <tr class="border-b border-gray-800">
                            <td class="p-2 text-white"><?= htmlspecialchars($result['bot']['name']) ?></td>
                            <td class="p-2">
                                <span class="<?= $result['success'] ? 'text-green-400' : 'text-red-400' ?>">
                                    <?= $result['success'] ? '✓' : '✗' ?>
                                </span>
                            </td>
                            <?php if ($result['success'] && isset($result['decision'])): ?>
                                <td class="p-2">
                                    <span class="<?= $result['decision']['bot'] ? 'text-red-400' : 'text-green-400' ?>">
                                        <?= $result['decision']['bot'] ? 'Evet' : 'Hayır' ?>
                                    </span>
                                </td>
                                <td class="p-2">
                                    <?php 
                                    $isFake = ($result['decision']['redirect_target'] === 'fake' || ($result['decision']['is_fake_url'] ?? false));
                                    ?>
                                    <span class="<?= $isFake ? 'text-red-400' : 'text-green-400' ?>">
                                        <?= $isFake ? 'Fake' : 'Normal' ?>
                                    </span>
                                    <?php if (isset($result['decision']['is_fake_url'])): ?>
                                        <br><span class="text-xs <?= $result['decision']['is_fake_url'] ? 'text-red-300' : 'text-green-300' ?>">
                                            (<?= $result['decision']['is_fake_url'] ? 'Fake URL' : 'Normal URL' ?>)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-2">
                                    <div class="flex flex-col">
                                        <span class="text-cyan-400 font-semibold text-xs"><?= $result['decision']['fingerprint_score'] ?? 0 ?></span>
                                    </div>
                                </td>
                                <td class="p-2 text-yellow-400">
                                    <?= $result['decision']['bot_confidence'] ? number_format($result['decision']['bot_confidence'], 2) . '%' : 'N/A' ?>
                                </td>
                                <td class="p-2 text-blue-400">
                                    <?php 
                                    $mlConf = $result['decision']['ml_confidence'] ?? null;
                                    if ($mlConf !== null) {
                                        echo number_format((float)$mlConf, 2) . '%';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td class="p-2 text-cyan-400 text-xs font-mono">
                                    <?= !empty($result['decision']['rdns_hostname']) ? htmlspecialchars($result['decision']['rdns_hostname']) : 'N/A' ?>
                                </td>
                                <td class="p-2 text-purple-400">
                                    <?php 
                                    $similarity = $result['decision']['fingerprint_similarity'] ?? null;
                                    if ($similarity !== null) {
                                        echo number_format((float)$similarity * 100, 2) . '%';
                                    } else {
                                        echo '<span class="text-gray-500 text-xs">Hesaplanıyor...</span>';
                                    }
                                    ?>
                                </td>
                                <td class="p-2 text-orange-400">
                                    <?= isset($result['decision']['behavioral_bot_score']) && $result['decision']['behavioral_bot_score'] !== null ? number_format($result['decision']['behavioral_bot_score'], 2) . '%' : 'N/A' ?>
                                </td>
                            <?php else: ?>
                                <td colspan="6" class="p-2 text-red-400"><?= htmlspecialchars($result['message']) ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Test Logları Bölümü -->
<section class="glass-card rounded-2xl border border-cyan-500/20 shadow p-6 mt-8">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">📋 Test Logları</h2>
        <div class="flex gap-2">
            <a href="?clear_logs=1" 
               onclick="return confirm('Tüm logları silmek istediğinize emin misiniz?')" 
               class="inline-block bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-2 rounded-lg text-sm">
                🗑️ Tümünü Sil
            </a>
            <a href="?" class="inline-block bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700 text-white px-4 py-2 rounded-lg text-sm">
                🔄 Yenile
            </a>
        </div>
    </div>
    
    <?php if (empty($logs)): ?>
        <p class="text-gray-400 text-sm">Henüz log kaydı yok. Test çalıştırdığınızda loglar burada görünecek.</p>
    <?php else: ?>
        <div class="mb-4 text-sm text-gray-400">
            Toplam <span class="font-bold text-white"><?= count($logs) ?></span> log kaydı
        </div>
        
        <div class="space-y-3 max-h-[600px] overflow-y-auto">
            <?php foreach ($logs as $log): ?>
                <div class="p-4 rounded-lg border <?php
                    if ($log['bot_detected'] ?? false) {
                        echo 'bg-red-900/20 border-red-500/30';
                    } else {
                        echo 'bg-green-900/20 border-green-500/30';
                    }
                ?>">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="font-semibold text-white"><?= htmlspecialchars($log['bot_name'] ?? 'Bilinmeyen Bot') ?></span>
                            <span class="text-xs text-gray-400 ml-2"><?= htmlspecialchars($log['timestamp'] ?? '') ?></span>
                        </div>
                        <div class="flex gap-2">
                            <?php if ($log['bot_detected'] ?? false): ?>
                                <span class="px-2 py-1 rounded text-xs bg-red-500/30 text-red-300">Bot Tespit Edildi</span>
                            <?php else: ?>
                                <span class="px-2 py-1 rounded text-xs bg-green-500/30 text-green-300">Bot Tespit Edilmedi</span>
                            <?php endif; ?>
                            <?php if ($log['is_fake_url'] ?? false): ?>
                                <span class="px-2 py-1 rounded text-xs bg-purple-500/30 text-purple-300">Fake URL</span>
                            <?php else: ?>
                                <span class="px-2 py-1 rounded text-xs bg-blue-500/30 text-blue-300">Normal URL</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 mt-3 text-sm">
                        <div>
                            <span class="text-gray-400">User-Agent:</span>
                            <span class="text-white text-xs font-mono ml-2"><?= htmlspecialchars(substr($log['user_agent'] ?? 'N/A', 0, 60)) ?><?= strlen($log['user_agent'] ?? '') > 60 ? '...' : '' ?></span>
                        </div>
                        <div>
                            <span class="text-gray-400">IP:</span>
                            <span class="text-white ml-2"><?= htmlspecialchars($log['ip'] ?? 'N/A') ?></span>
                        </div>
                        <?php if (!empty($log['rdns_hostname'])): ?>
                            <div>
                                <span class="text-gray-400">rDNS:</span>
                                <span class="text-cyan-400 text-xs font-mono ml-2"><?= htmlspecialchars($log['rdns_hostname']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($log['fingerprint_similarity']) && $log['fingerprint_similarity'] !== null): ?>
                            <div>
                                <span class="text-gray-400">Fingerprint Similarity:</span>
                                <span class="text-purple-400 ml-2"><?= number_format($log['fingerprint_similarity'] * 100, 2) ?>%</span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($log['behavioral_bot_score']) && $log['behavioral_bot_score'] !== null): ?>
                            <div>
                                <span class="text-gray-400">Behavioral Score:</span>
                                <span class="text-orange-400 ml-2"><?= number_format($log['behavioral_bot_score'], 2) ?>%</span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($log['fingerprint_score'])): ?>
                            <div>
                                <span class="text-gray-400">Fingerprint Score:</span>
                                <span class="text-yellow-400 ml-2"><?= $log['fingerprint_score'] ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($log['bot_confidence']) && $log['bot_confidence'] !== null): ?>
                            <div>
                                <span class="text-gray-400">Bot Confidence:</span>
                                <span class="text-red-400 ml-2"><?= number_format($log['bot_confidence'], 2) ?>%</span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($log['ml_confidence']) && $log['ml_confidence'] !== null): ?>
                            <div>
                                <span class="text-gray-400">ML Confidence:</span>
                                <span class="text-blue-400 ml-2"><?= number_format($log['ml_confidence'], 2) ?>%</span>
                            </div>
                        <?php endif; ?>
                        <div>
                            <span class="text-gray-400">Proxy/VPN:</span>
                            <span class="<?= ($log['is_proxy'] ?? false) ? 'text-yellow-400' : 'text-green-400' ?> ml-2">
                                <?= ($log['is_proxy'] ?? false) ? 'Tespit Edildi' : 'Tespit Edilmedi (Bypass Aktif)' ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-400">Redirect Target:</span>
                            <span class="text-white ml-2"><?= htmlspecialchars($log['redirect_target'] ?? 'N/A') ?></span>
                        </div>
                        <?php if (!empty($log['redirect_url'])): ?>
                        <div>
                            <span class="text-gray-400">Redirect URL:</span>
                            <span class="text-cyan-400 text-xs font-mono ml-2 break-all"><?= htmlspecialchars($log['redirect_url']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($log['target_url'])): ?>
                        <div>
                            <span class="text-gray-400">Hedef URL:</span>
                            <span class="text-yellow-400 text-xs font-mono ml-2 break-all"><?= htmlspecialchars($log['target_url']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div>
                            <span class="text-gray-400">Test Tipi:</span>
                            <span class="text-white ml-2"><?= $log['type'] === 'single_test' ? 'Tekil Test' : 'Toplu Test' ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php render_admin_layout_end(); ?>

