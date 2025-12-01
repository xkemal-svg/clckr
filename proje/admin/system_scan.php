<?php
/**
 * Sistem Tarama ve Hata Kontrolü
 * Tüm sistemi tarar ve hataları tespit eder
 */

require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../cloacker.php';

enforceAdminSession();

$pdo = DB::connect();
$scanResults = [];
$errors = [];
$warnings = [];
$info = [];

// Tarama başlat
if (isset($_GET['run_scan'])) {
    // 1. Veritabanı yapısı kontrolü
    $scanResults[] = [
        'category' => 'Veritabanı',
        'name' => 'Veritabanı Bağlantısı',
        'status' => 'checking'
    ];
    try {
        $pdo->query("SELECT 1");
        $scanResults[count($scanResults) - 1]['status'] = 'ok';
        $scanResults[count($scanResults) - 1]['message'] = 'Veritabanı bağlantısı başarılı';
    } catch (Exception $e) {
        $scanResults[count($scanResults) - 1]['status'] = 'error';
        $scanResults[count($scanResults) - 1]['message'] = 'Bağlantı hatası: ' . $e->getMessage();
        $errors[] = $scanResults[count($scanResults) - 1]['message'];
    }
    
    // 2. Tablolar kontrolü
    $requiredTables = [
        'cloacker_visitors',
        'cloacker_settings',
        'cloacker_sites',
        'cloacker_admins',
        'cloacker_bot_detections',
        'cloacker_rdns_cache',
        'cloacker_fingerprint_history',
        'cloacker_behavioral_data',
        'cloacker_api_keys',
        'cloacker_ja3_blacklist',
        'cloacker_rate_limits',
        'cloacker_allowed_countries',
    ];
    
    foreach ($requiredTables as $table) {
        $scanResults[] = [
            'category' => 'Veritabanı',
            'name' => "Tablo: $table",
            'status' => 'checking'
        ];
        try {
            $pdo->query("SELECT 1 FROM $table LIMIT 1");
            $scanResults[count($scanResults) - 1]['status'] = 'ok';
            $scanResults[count($scanResults) - 1]['message'] = 'Tablo mevcut';
        } catch (Exception $e) {
            $scanResults[count($scanResults) - 1]['status'] = 'error';
            $scanResults[count($scanResults) - 1]['message'] = 'Tablo bulunamadı';
            $errors[] = "Tablo eksik: $table";
        }
    }
    
    // 3. Sütunlar kontrolü
    $requiredColumns = [
        'cloacker_visitors' => [
            'rdns_hostname',
            'rdns_is_bot',
            'fingerprint_similarity',
            'behavioral_bot_score',
            'canvas_fingerprint',
            'webgl_fingerprint',
            'audio_fingerprint',
            'fonts_hash',
            'plugins_hash',
            'ml_confidence',
            'fingerprint_hash',
            'ja3_hash',
        ],
        'cloacker_settings' => [
            'enable_rdns_check',
            'enable_fingerprint_similarity',
            'enable_behavioral_analysis',
            'fingerprint_similarity_threshold_high',
            'fingerprint_similarity_threshold_low',
            'behavioral_bot_threshold',
            'rdns_cache_ttl_hours',
        ],
    ];
    
    foreach ($requiredColumns as $table => $columns) {
        foreach ($columns as $column) {
            $scanResults[] = [
                'category' => 'Veritabanı',
                'name' => "Sütun: $table.$column",
                'status' => 'checking'
            ];
            try {
                $pdo->query("SELECT $column FROM $table LIMIT 1");
                $scanResults[count($scanResults) - 1]['status'] = 'ok';
                $scanResults[count($scanResults) - 1]['message'] = 'Sütun mevcut';
            } catch (Exception $e) {
                $scanResults[count($scanResults) - 1]['status'] = 'warning';
                $scanResults[count($scanResults) - 1]['message'] = 'Sütun bulunamadı (migration gerekli)';
                $warnings[] = "Sütun eksik: $table.$column";
            }
        }
    }
    
    // 4. Fonksiyonlar kontrolü
    $requiredFunctions = [
        'checkReverseDNS',
        'isBotHostname',
        'verifyGooglebot',
        'fingerprintToVector',
        'cosineSimilarity',
        'calculateFingerprintSimilarity',
        'checkFingerprintHistory',
        'updateFingerprintHistory',
    ];
    
    foreach ($requiredFunctions as $func) {
        $scanResults[] = [
            'category' => 'PHP Fonksiyonları',
            'name' => "Fonksiyon: $func",
            'status' => 'checking'
        ];
        if (function_exists($func)) {
            $scanResults[count($scanResults) - 1]['status'] = 'ok';
            $scanResults[count($scanResults) - 1]['message'] = 'Fonksiyon mevcut';
        } else {
            $scanResults[count($scanResults) - 1]['status'] = 'error';
            $scanResults[count($scanResults) - 1]['message'] = 'Fonksiyon bulunamadı';
            $errors[] = "Fonksiyon eksik: $func";
        }
    }
    
    // 5. Dosya kontrolü
    $requiredFiles = [
        'cloacker.php',
        'embed/cloacker.js',
        'api/cloaker_decision.php',
        'admin/settings.php',
        'admin/live_visitors.php',
        'admin/api/visitor_details.php',
    ];
    
    foreach ($requiredFiles as $file) {
        $scanResults[] = [
            'category' => 'Dosyalar',
            'name' => "Dosya: $file",
            'status' => 'checking'
        ];
        $fullPath = __DIR__ . '/../' . $file;
        if (file_exists($fullPath)) {
            $scanResults[count($scanResults) - 1]['status'] = 'ok';
            $scanResults[count($scanResults) - 1]['message'] = 'Dosya mevcut';
        } else {
            $scanResults[count($scanResults) - 1]['status'] = 'error';
            $scanResults[count($scanResults) - 1]['message'] = 'Dosya bulunamadı';
            $errors[] = "Dosya eksik: $file";
        }
    }
    
    // 6. Settings kontrolü
    $scanResults[] = [
        'category' => 'Ayarlar',
        'name' => 'Settings Kaydı',
        'status' => 'checking'
    ];
    try {
        $settings = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch();
        if ($settings) {
            $scanResults[count($scanResults) - 1]['status'] = 'ok';
            $scanResults[count($scanResults) - 1]['message'] = 'Settings kaydı mevcut';
            
            // Yeni ayarların varlığını kontrol et
            $newSettings = [
                'enable_rdns_check',
                'enable_fingerprint_similarity',
                'enable_behavioral_analysis',
            ];
            
            foreach ($newSettings as $setting) {
                if (!isset($settings[$setting])) {
                    $warnings[] = "Ayar eksik: $setting (migration gerekli)";
                }
            }
        } else {
            $scanResults[count($scanResults) - 1]['status'] = 'error';
            $scanResults[count($scanResults) - 1]['message'] = 'Settings kaydı bulunamadı';
            $errors[] = 'Settings kaydı eksik';
        }
    } catch (Exception $e) {
        $scanResults[count($scanResults) - 1]['status'] = 'error';
        $scanResults[count($scanResults) - 1]['message'] = 'Settings okuma hatası: ' . $e->getMessage();
        $errors[] = $scanResults[count($scanResults) - 1]['message'];
    }
    
    // 7. Fonksiyon testleri
    $scanResults[] = [
        'category' => 'Test',
        'name' => 'Cloaker Decision Test',
        'status' => 'checking'
    ];
    try {
        $decision = cloaker_decision(false, false, null, null, [
            'override_ip' => '8.8.8.8',
            'override_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'client_fingerprints' => [
                'canvas' => 'test123',
                'webgl' => 'test456',
                'audio' => 'test789',
            ],
            'skip_proxy_check' => true,
        ]);
        $scanResults[count($scanResults) - 1]['status'] = 'ok';
        $scanResults[count($scanResults) - 1]['message'] = 'Cloaker decision çalışıyor';
    } catch (Exception $e) {
        $scanResults[count($scanResults) - 1]['status'] = 'error';
        $scanResults[count($scanResults) - 1]['message'] = 'Hata: ' . $e->getMessage();
        $errors[] = 'Cloaker decision hatası: ' . $e->getMessage();
    }
    
    // 8. rDNS test ve leak kontrolü
    $scanResults[] = [
        'category' => 'Test',
        'name' => 'rDNS Kontrol Test',
        'status' => 'checking'
    ];
    try {
        // rDNS cache tablosunu kontrol et
        try {
            $pdo->query("SELECT 1 FROM cloacker_rdns_cache LIMIT 1");
            $rdnsTableExists = true;
        } catch (Exception $e) {
            $rdnsTableExists = false;
            $warnings[] = 'rDNS cache tablosu eksik (migration gerekli)';
        }
        
        // rDNS fonksiyonu kontrolü
        if (function_exists('gethostbyaddr')) {
            $testIP = '8.8.8.8';
            $hostname = @gethostbyaddr($testIP);
            if ($hostname && $hostname !== $testIP) {
                $scanResults[count($scanResults) - 1]['status'] = 'ok';
                $scanResults[count($scanResults) - 1]['message'] = 'rDNS kontrolü çalışıyor (Hostname: ' . substr($hostname, 0, 50) . ')';
            } else {
                $scanResults[count($scanResults) - 1]['status'] = 'warning';
                $scanResults[count($scanResults) - 1]['message'] = 'rDNS kontrolü çalışıyor ancak test IP için hostname bulunamadı (normal olabilir)';
            }
        } else {
            $scanResults[count($scanResults) - 1]['status'] = 'error';
            $scanResults[count($scanResults) - 1]['message'] = 'gethostbyaddr fonksiyonu mevcut değil';
            $errors[] = 'gethostbyaddr fonksiyonu eksik';
        }
    } catch (Exception $e) {
        $scanResults[count($scanResults) - 1]['status'] = 'error';
        $scanResults[count($scanResults) - 1]['message'] = 'Hata: ' . $e->getMessage();
        $errors[] = 'rDNS test hatası: ' . $e->getMessage();
    }
    
    // 8.1. rDNS Leak kontrolü - Botlara bilgi sızıntısı var mı?
    $scanResults[] = [
        'category' => 'Güvenlik',
        'name' => 'rDNS Leak Kontrolü',
        'status' => 'checking'
    ];
    try {
        // rDNS sonuçlarının loglarda veya API'de açıkça gösterilip gösterilmediğini kontrol et
        $leakPoints = [];
        
        // 1. API response kontrolü
        $apiFile = __DIR__ . '/../api/cloaker_decision.php';
        if (file_exists($apiFile)) {
            $apiContent = file_get_contents($apiFile);
            // rDNS bilgilerinin API response'unda olup olmadığını kontrol et
            if (preg_match('/rdns.*hostname|hostname.*rdns/i', $apiContent)) {
                // Eğer rdns_hostname response'da varsa ama sanitize edilmemişse leak olabilir
                if (strpos($apiContent, 'sanitize') === false && strpos($apiContent, 'hidden') === false) {
                    $leakPoints[] = 'API response\'da rDNS bilgisi açıkça gösteriliyor olabilir';
                }
            }
        }
        
        // 2. Log dosyalarında hassas bilgi kontrolü
        $logDir = __DIR__ . '/../logs/';
        if (is_dir($logDir)) {
            $logFiles = glob($logDir . '*.log');
            foreach ($logFiles as $logFile) {
                $content = @file_get_contents($logFile);
                if ($content && preg_match('/rdns.*hostname.*googlebot|googlebot.*rdns/i', $content)) {
                    // Log dosyalarında gerçek bot hostname'leri varsa bu bir leak olabilir
                    $leakPoints[] = 'Log dosyalarında bot hostname bilgileri bulundu: ' . basename($logFile);
                }
            }
        }
        
        if (empty($leakPoints)) {
            $scanResults[count($scanResults) - 1]['status'] = 'ok';
            $scanResults[count($scanResults) - 1]['message'] = 'rDNS bilgileri güvenli şekilde saklanıyor, leak tespit edilmedi';
        } else {
            $scanResults[count($scanResults) - 1]['status'] = 'warning';
            $scanResults[count($scanResults) - 1]['message'] = 'Potansiyel leak noktaları: ' . implode(', ', $leakPoints);
            $warnings[] = 'rDNS bilgileri botlara sızabilir: ' . implode('; ', $leakPoints);
        }
    } catch (Exception $e) {
        $scanResults[count($scanResults) - 1]['status'] = 'error';
        $scanResults[count($scanResults) - 1]['message'] = 'Leak kontrolü hatası: ' . $e->getMessage();
        $errors[] = 'rDNS leak kontrolü hatası: ' . $e->getMessage();
    }
    
    // 9. JavaScript dosyası kontrolü
    $scanResults[] = [
        'category' => 'JavaScript',
        'name' => 'Behavioral Analysis Kodu',
        'status' => 'checking'
    ];
    try {
        $jsContent = file_get_contents(__DIR__ . '/../embed/cloacker.js');
        if (strpos($jsContent, 'calculateBehavioralFeatures') !== false && 
            strpos($jsContent, 'behavioralData') !== false) {
            $scanResults[count($scanResults) - 1]['status'] = 'ok';
            $scanResults[count($scanResults) - 1]['message'] = 'Behavioral analysis kodu mevcut';
        } else {
            $scanResults[count($scanResults) - 1]['status'] = 'warning';
            $scanResults[count($scanResults) - 1]['message'] = 'Behavioral analysis kodu bulunamadı';
            $warnings[] = 'Behavioral analysis JavaScript kodu eksik';
        }
    } catch (Exception $e) {
        $scanResults[count($scanResults) - 1]['status'] = 'error';
        $scanResults[count($scanResults) - 1]['message'] = 'Dosya okunamadı: ' . $e->getMessage();
        $errors[] = 'JavaScript dosyası okunamadı';
    }
}

render_admin_layout_start('Sistem Tarama', 'system_scan');
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-white mb-2">🔍 Sistem Tarama</h1>
    <p class="text-gray-400">Tüm sistemi tarar ve hataları tespit eder</p>
</div>

<div class="mb-6">
    <a href="?run_scan=1" class="inline-block bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-6 py-2 rounded-lg">
        Taramayı Başlat
    </a>
</div>

<?php if (!empty($scanResults)): ?>
    <?php
    $okCount = count(array_filter($scanResults, fn($r) => $r['status'] === 'ok'));
    $errorCount = count(array_filter($scanResults, fn($r) => $r['status'] === 'error'));
    $warningCount = count(array_filter($scanResults, fn($r) => $r['status'] === 'warning'));
    $totalCount = count($scanResults);
    ?>
    
    <div class="mb-6 p-4 rounded-lg <?= $errorCount > 0 ? 'bg-red-900/30 border border-red-500/30' : 'bg-green-900/30 border border-green-500/30' ?>">
        <h3 class="font-semibold mb-2">Özet</h3>
        <div class="grid grid-cols-4 gap-4 text-sm">
            <div>
                <span class="text-gray-400">Toplam Kontrol:</span>
                <span class="font-bold ml-2"><?= $totalCount ?></span>
            </div>
            <div>
                <span class="text-green-400">✓ Başarılı:</span>
                <span class="font-bold ml-2"><?= $okCount ?></span>
            </div>
            <div>
                <span class="text-yellow-400">⚠ Uyarı:</span>
                <span class="font-bold ml-2"><?= $warningCount ?></span>
            </div>
            <div>
                <span class="text-red-400">✗ Hata:</span>
                <span class="font-bold ml-2"><?= $errorCount ?></span>
            </div>
        </div>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 rounded-lg bg-red-900/30 border border-red-500/30">
            <h3 class="font-semibold text-red-400 mb-2">Kritik Hatalar</h3>
            <ul class="list-disc list-inside space-y-1 text-sm">
                <?php foreach ($errors as $error): ?>
                    <li class="text-red-300"><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($warnings)): ?>
        <div class="mb-6 p-4 rounded-lg bg-yellow-900/30 border border-yellow-500/30">
            <h3 class="font-semibold text-yellow-400 mb-2">Uyarılar</h3>
            <ul class="list-disc list-inside space-y-1 text-sm">
                <?php foreach ($warnings as $warning): ?>
                    <li class="text-yellow-300"><?= htmlspecialchars($warning) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="space-y-4">
        <?php
        $currentCategory = '';
        foreach ($scanResults as $result):
            if ($currentCategory !== $result['category']):
                $currentCategory = $result['category'];
        ?>
            <h3 class="text-xl font-semibold text-white mt-6 mb-3"><?= htmlspecialchars($currentCategory) ?></h3>
        <?php endif; ?>
        
        <div class="p-3 rounded-lg border <?php
            if ($result['status'] === 'ok') echo 'bg-green-900/20 border-green-500/30';
            elseif ($result['status'] === 'error') echo 'bg-red-900/20 border-red-500/30';
            else echo 'bg-yellow-900/20 border-yellow-500/30';
        ?>">
            <div class="flex items-center gap-2">
                <span class="font-semibold">
                    <?php if ($result['status'] === 'ok'): ?>
                        ✓
                    <?php elseif ($result['status'] === 'error'): ?>
                        ✗
                    <?php else: ?>
                        ⚠
                    <?php endif; ?>
                    <?= htmlspecialchars($result['name']) ?>
                </span>
            </div>
            <div class="text-sm text-gray-400 mt-1"><?= htmlspecialchars($result['message']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="text-gray-400">Taramayı başlatmak için yukarıdaki butona tıklayın.</p>
<?php endif; ?>

<?php render_admin_layout_end(); ?>

