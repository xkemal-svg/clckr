<?php
/**
 * IP2Location API Test Sayfası
 */

require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../cloacker.php';

enforceAdminSession();

// Logout kontrolü
if (isset($_GET['logout'])) {
    logoutAdmin();
}

$testResult = null;
$testIP = $_GET['test_ip'] ?? '212.253.187.0';
$apiKey = config('api_keys.ip2location');

render_admin_layout_start('IP2Location API Test', 'test_ip2location');
?>

<div class="mb-6">
    <h2 class="text-2xl font-heading font-bold text-white mb-4">🔍 IP2Location API Test</h2>
    <p class="text-sm text-gray-400 mb-4">
        IP2Location API bağlantısını test edin ve proxy/residential proxy tespitini kontrol edin.
    </p>
</div>

<!-- API Key Bilgisi -->
<div class="glass-card rounded-xl p-6 border border-cyan-500/20 mb-6">
    <h3 class="text-lg font-semibold text-white mb-4">API Yapılandırması</h3>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <span class="text-gray-300">API Key Durumu:</span>
            <?php if (!empty($apiKey)): ?>
                <span class="px-3 py-1 text-xs rounded-full bg-green-500/20 text-green-400 border border-green-500/30">
                    ✅ API Key Tanımlı
                </span>
            <?php else: ?>
                <span class="px-3 py-1 text-xs rounded-full bg-red-500/20 text-red-400 border border-red-500/30">
                    ❌ API Key Bulunamadı
                </span>
            <?php endif; ?>
        </div>
        <?php if (!empty($apiKey)): ?>
            <div class="flex items-center justify-between">
                <span class="text-gray-300">API Key (İlk 20 karakter):</span>
                <span class="font-mono text-xs text-gray-400"><?= htmlspecialchars(substr($apiKey, 0, 20)) ?>...</span>
            </div>
        <?php endif; ?>
        <div class="flex items-center justify-between">
            <span class="text-gray-300">API Endpoint:</span>
            <span class="font-mono text-xs text-gray-400">https://api.ip2location.io/</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-gray-300">API Key Gönderimi:</span>
            <span class="font-mono text-xs text-gray-400">Header: X-API-KEY</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-gray-300">Query Format:</span>
            <span class="font-mono text-xs text-gray-400">?ip=IP_ADDRESS</span>
        </div>
    </div>
</div>

<!-- Test Formu -->
<div class="glass-card rounded-xl p-6 border border-cyan-500/20 mb-6">
    <h3 class="text-lg font-semibold text-white mb-4">IP Adresi Test Et</h3>
    <form method="GET" action="" class="flex gap-3">
        <input type="text" name="test_ip" value="<?= htmlspecialchars($testIP) ?>" 
               placeholder="Test edilecek IP adresi (örn: 212.253.187.0)"
               class="flex-1 px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white placeholder-gray-500 focus:border-cyan-500/50 focus:outline-none transition">
        <button type="submit" 
                class="px-6 py-2 bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-lg hover:from-cyan-600 hover:to-teal-600 transition font-medium">
            Test Et
        </button>
    </form>
</div>

<?php
// API Testi
if (!empty($testIP) && !empty($apiKey) && filter_var($testIP, FILTER_VALIDATE_IP)) {
    try {
        $startTime = microtime(true);
        
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET',
                'header' => "X-API-KEY: {$apiKey}\r\n"
            ]
        ]);
        
        $url = "https://api.ip2location.io/?ip={$testIP}";
        $response = @file_get_contents($url, false, $ctx);
        
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2); // ms
        
        if ($response) {
            $data = json_decode($response, true);
            
            // Hata kontrolü (yeni API formatı)
            if (isset($data['error'])) {
                $errorMsg = is_array($data['error']) 
                    ? ($data['error']['error_message'] ?? $data['error']['message'] ?? 'Bilinmeyen hata')
                    : (is_string($data['error']) ? $data['error'] : 'Bilinmeyen hata');
                
                $testResult = [
                    'success' => false,
                    'error' => $errorMsg,
                    'error_code' => is_array($data['error']) ? ($data['error']['error_code'] ?? $data['error']['code'] ?? null) : null,
                    'response_time' => $responseTime,
                    'raw_response' => $response
                ];
            } else {
                $testResult = [
                    'success' => true,
                    'data' => $data,
                    'response_time' => $responseTime,
                    'raw_response' => $response
                ];
            }
        } else {
            $testResult = [
                'success' => false,
                'error' => 'API\'ye bağlanılamadı. Lütfen internet bağlantınızı ve API key\'inizi kontrol edin.',
                'response_time' => $responseTime
            ];
        }
    } catch (Exception $e) {
        $testResult = [
            'success' => false,
            'error' => 'Hata: ' . $e->getMessage()
        ];
    }
} elseif (!empty($testIP) && !filter_var($testIP, FILTER_VALIDATE_IP)) {
    $testResult = [
        'success' => false,
        'error' => 'Geçersiz IP adresi formatı.'
    ];
}
?>

<?php if ($testResult): ?>
    <div class="glass-card rounded-xl p-6 border <?= $testResult['success'] ? 'border-green-500/20' : 'border-red-500/20' ?>">
        <h3 class="text-lg font-semibold text-white mb-4">
            <?= $testResult['success'] ? '✅ Test Başarılı' : '❌ Test Başarısız' ?>
        </h3>
        
        <?php if ($testResult['success']): ?>
            <?php $d = $testResult['data']; ?>
            
            <!-- Response Time -->
            <div class="mb-4 p-3 bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                <span class="text-sm text-gray-300">Yanıt Süresi:</span>
                <span class="text-cyan-400 font-semibold ml-2"><?= $testResult['response_time'] ?> ms</span>
            </div>
            
            <!-- Temel Bilgiler -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="p-4 bg-gray-900/50 rounded-lg border border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-300 mb-2">IP Adresi</h4>
                    <p class="text-white font-mono"><?= htmlspecialchars($d['ip'] ?? 'N/A') ?></p>
                </div>
                
                <div class="p-4 bg-gray-900/50 rounded-lg border border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-300 mb-2">Ülke</h4>
                    <p class="text-white"><?= htmlspecialchars($d['country_name'] ?? 'N/A') ?> (<?= htmlspecialchars($d['country_code'] ?? 'N/A') ?>)</p>
                </div>
                
                <div class="p-4 bg-gray-900/50 rounded-lg border border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-300 mb-2">Şehir</h4>
                    <p class="text-white"><?= htmlspecialchars($d['city_name'] ?? 'N/A') ?></p>
                </div>
                
                <div class="p-4 bg-gray-900/50 rounded-lg border border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-300 mb-2">ISP</h4>
                    <p class="text-white"><?= htmlspecialchars($d['isp'] ?? 'N/A') ?></p>
                </div>
            </div>
            
            <!-- Proxy Bilgileri -->
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-white mb-3">Proxy Tespit Bilgileri</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-lg border <?= isset($d['is_proxy']) && $d['is_proxy'] ? 'bg-red-500/10 border-red-500/30' : 'bg-green-500/10 border-green-500/30' ?>">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-300">Proxy Durumu:</span>
                            <span class="px-2 py-1 text-xs rounded-full <?= isset($d['is_proxy']) && $d['is_proxy'] ? 'bg-red-500/20 text-red-400' : 'bg-green-500/20 text-green-400' ?>">
                                <?= isset($d['is_proxy']) && $d['is_proxy'] ? 'Evet' : 'Hayır' ?>
                            </span>
                        </div>
                        <?php if (isset($d['proxy_type'])): ?>
                            <p class="text-xs text-gray-400">Tip: <?= htmlspecialchars($d['proxy_type']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-4 rounded-lg border <?= isset($d['is_residential_proxy']) && $d['is_residential_proxy'] ? 'bg-red-500/10 border-red-500/30' : 'bg-green-500/10 border-green-500/30' ?>">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-300">Residential Proxy:</span>
                            <span class="px-2 py-1 text-xs rounded-full <?= isset($d['is_residential_proxy']) && $d['is_residential_proxy'] ? 'bg-red-500/20 text-red-400' : 'bg-green-500/20 text-green-400' ?>">
                                <?= isset($d['is_residential_proxy']) && $d['is_residential_proxy'] ? 'Evet' : 'Hayır' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-4 rounded-lg border border-gray-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-300">Usage Type:</span>
                            <span class="text-xs text-gray-400"><?= htmlspecialchars($d['usage_type'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detaylı Bilgiler -->
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-white mb-3">Detaylı Bilgiler</h4>
                <div class="bg-gray-900/50 rounded-lg p-4 border border-gray-700">
                    <pre class="text-xs text-gray-300 overflow-x-auto"><?= htmlspecialchars(json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Hata Mesajı -->
            <div class="p-4 bg-red-500/10 rounded-lg border border-red-500/30">
                <p class="text-red-400 font-semibold mb-2">Hata:</p>
                <p class="text-gray-300"><?= htmlspecialchars($testResult['error'] ?? 'Bilinmeyen hata') ?></p>
                <?php if (isset($testResult['error_code'])): ?>
                    <p class="text-xs text-gray-400 mt-2">Hata Kodu: <?= htmlspecialchars($testResult['error_code']) ?></p>
                <?php endif; ?>
                <?php if (isset($testResult['response_time'])): ?>
                    <p class="text-xs text-gray-400 mt-1">Yanıt Süresi: <?= $testResult['response_time'] ?> ms</p>
                <?php endif; ?>
            </div>
            
            <?php if (isset($testResult['raw_response'])): ?>
                <div class="mt-4">
                    <h4 class="text-sm font-semibold text-white mb-2">Ham Yanıt:</h4>
                    <div class="bg-gray-900/50 rounded-lg p-4 border border-gray-700">
                        <pre class="text-xs text-gray-300 overflow-x-auto"><?= htmlspecialchars($testResult['raw_response']) ?></pre>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Örnek IP Adresleri -->
<div class="glass-card rounded-xl p-6 border border-cyan-500/20 mt-6">
    <h3 class="text-lg font-semibold text-white mb-4">Örnek Test IP Adresleri</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        <a href="?test_ip=212.253.187.0" class="p-3 bg-gray-900/50 rounded-lg border border-gray-700 hover:border-cyan-500/50 transition text-center">
            <p class="text-xs text-gray-400 mb-1">Örnek Test IP</p>
            <p class="text-white font-mono text-sm">212.253.187.0</p>
        </a>
        <a href="?test_ip=8.8.8.8" class="p-3 bg-gray-900/50 rounded-lg border border-gray-700 hover:border-cyan-500/50 transition text-center">
            <p class="text-xs text-gray-400 mb-1">Google DNS</p>
            <p class="text-white font-mono text-sm">8.8.8.8</p>
        </a>
        <a href="?test_ip=1.1.1.1" class="p-3 bg-gray-900/50 rounded-lg border border-gray-700 hover:border-cyan-500/50 transition text-center">
            <p class="text-xs text-gray-400 mb-1">Cloudflare DNS</p>
            <p class="text-white font-mono text-sm">1.1.1.1</p>
        </a>
        <a href="?test_ip=208.67.222.222" class="p-3 bg-gray-900/50 rounded-lg border border-gray-700 hover:border-cyan-500/50 transition text-center">
            <p class="text-xs text-gray-400 mb-1">OpenDNS</p>
            <p class="text-white font-mono text-sm">208.67.222.222</p>
        </a>
        <a href="?test_ip=<?= $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1' ?>" class="p-3 bg-gray-900/50 rounded-lg border border-gray-700 hover:border-cyan-500/50 transition text-center">
            <p class="text-xs text-gray-400 mb-1">Kendi IP'niz</p>
            <p class="text-white font-mono text-sm"><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?></p>
        </a>
    </div>
</div>

<?php render_admin_layout_end(); ?>

