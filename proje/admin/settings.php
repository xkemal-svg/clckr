<?php
/**
 * Genel Sistem Ayarları - Bot Filtreleri ve Skor Ayarları
 */

require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../cloacker.php';

enforceAdminSession();

// Logout kontrolü
if (isset($_GET['logout'])) {
    logoutAdmin();
}

$pdo = DB::connect();
$error = '';
$success = '';

// Ayarları yükle
$stmt = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1");
$settings = $stmt->fetch() ?: [];

// Ayarları güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    requireCsrfToken();
    
    try {
        // Bot filtre aktif/pasif ayarları
        $enableJA3 = isset($_POST['enable_ja3_check']) ? 1 : 0;
        $enableCanvas = isset($_POST['enable_canvas_check']) ? 1 : 0;
        $enableWebGL = isset($_POST['enable_webgl_check']) ? 1 : 0;
        $enableAudio = isset($_POST['enable_audio_check']) ? 1 : 0;
        $enableWebRTC = isset($_POST['enable_webrtc_check']) ? 1 : 0;
        $enableFonts = isset($_POST['enable_fonts_check']) ? 1 : 0;
        $enablePlugins = isset($_POST['enable_plugins_check']) ? 1 : 0;
        $enableHeadless = isset($_POST['enable_headless_check']) ? 1 : 0;
        $enableChallenge = isset($_POST['enable_challenge_check']) ? 1 : 0;
        $enableRateLimit = isset($_POST['enable_rate_limit']) ? 1 : 0;
        $enableResidentialProxy = isset($_POST['enable_residential_proxy_check']) ? 1 : 0;
        $enableCloudflareBot = isset($_POST['enable_cloudflare_bot_check']) ? 1 : 0;
        $enableDuplicate = isset($_POST['enable_duplicate_check']) ? 1 : 0;
        $enableRDNS = isset($_POST['enable_rdns_check']) ? 1 : 0;
        $enableFingerprintSimilarity = isset($_POST['enable_fingerprint_similarity']) ? 1 : 0;
        $enableASN = isset($_POST['enable_asn_check']) ? 1 : 0;
        $enableBehavioralAnalysis = isset($_POST['enable_behavioral_analysis']) ? 1 : 0;
        $enableIPAge = isset($_POST['enable_ip_age_check']) ? 1 : 0;
        $enableDelayedRedirect = isset($_POST['enable_delayed_redirect']) ? 1 : 0;
        $enableProxyCheck = isset($_POST['enable_proxy_check']) ? 1 : 0;
        $enableTLS13Fingerprinting = isset($_POST['enable_tls13_fingerprinting']) ? 1 : 0;
        $enableThreatIntelligence = isset($_POST['enable_threat_intelligence']) ? 1 : 0;
        
        // Skor ayarları
        $canvasScore = (int)($_POST['canvas_score'] ?? 8);
        $webglScore = (int)($_POST['webgl_score'] ?? 7);
        $audioScore = (int)($_POST['audio_score'] ?? 6);
        $webrtcScore = (int)($_POST['webrtc_score'] ?? 10);
        $headlessScore = (int)($_POST['headless_score'] ?? 12);
        $fontsScore = (int)($_POST['fonts_score'] ?? 4);
        $pluginsScore = (int)($_POST['plugins_score'] ?? 3);
        $challengeScore = (int)($_POST['challenge_score'] ?? 15);
        $speechSynthesisScore = (int)($_POST['speech_synthesis_score'] ?? 3);
        $ja3Score = (int)($_POST['ja3_score'] ?? 20);
        $rdnsScore = (int)($_POST['rdns_score'] ?? 20);
        $rdnsCacheTTL = (int)($_POST['rdns_cache_ttl_hours'] ?? 24);
        $challengeFailAction = $_POST['challenge_fail_action'] ?? 'add_score'; // 'add_score' veya 'mark_bot'
        
        // Fingerprint Similarity ayarları
        $fingerprintSimilarityThresholdHigh = (float)($_POST['fingerprint_similarity_threshold_high'] ?? 0.98);
        $fingerprintSimilarityThresholdLow = (float)($_POST['fingerprint_similarity_threshold_low'] ?? 0.85);
        
        // Behavioral Analysis ayarları
        $behavioralBotThreshold = (float)($_POST['behavioral_bot_threshold'] ?? 70.0);
        
        // Delayed Redirect ayarları
        $delayedRedirectMin = (int)($_POST['delayed_redirect_min_seconds'] ?? 7);
        $delayedRedirectMax = (int)($_POST['delayed_redirect_max_seconds'] ?? 15);
        
        // Rate limit ayarları
        $rateLimitMaxRequests = (int)($_POST['rate_limit_max_requests'] ?? 10);
        $rateLimitWindowSeconds = (int)($_POST['rate_limit_window_seconds'] ?? 60);
        
        // Bot confidence threshold
        $botConfidenceThreshold = (float)($_POST['bot_confidence_threshold'] ?? 30.0);
        
        // Dinamik eşik ayarları
        $mlEnabled = isset($_POST['ml_enabled']) ? 1 : 0;
        $dynamicThresholdEnabled = isset($_POST['dynamic_threshold_enabled']) ? 1 : 0;
        $minThreshold = (float)($_POST['min_threshold'] ?? 20.0);
        $maxThreshold = (float)($_POST['max_threshold'] ?? 50.0);
        
        // Sütunların varlığını kontrol et
        $hasNewColumns = false;
        $hasRDNSColumns = false;
        try {
            $pdo->query("SELECT enable_ja3_check FROM cloacker_settings LIMIT 1");
            $hasNewColumns = true;
        } catch (PDOException $e) {
            // Yeni sütunlar yok
        }
        
        // rDNS sütunlarının varlığını kontrol et ve yoksa ekle
        $hasRDNSColumns = false;
        if ($hasNewColumns) {
            try {
                $pdo->query("SELECT enable_rdns_check FROM cloacker_settings LIMIT 1");
                $hasRDNSColumns = true;
            } catch (PDOException $e) {
                // rDNS sütunları yok, ekle
                try {
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN enable_rdns_check tinyint(1) DEFAULT 1");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN rdns_score int(11) DEFAULT 20");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN rdns_cache_ttl_hours int(11) DEFAULT 24");
                    $hasRDNSColumns = true;
                } catch (PDOException $e2) {
                    // Sütunlar eklenemedi, devam et
                    $hasRDNSColumns = false;
                }
            }
        }
        
        if ($hasNewColumns) {
            // challenge_fail_action sütununun varlığını kontrol et
            $hasChallengeFailAction = false;
            try {
                $pdo->query("SELECT challenge_fail_action FROM cloacker_settings LIMIT 1");
                $hasChallengeFailAction = true;
            } catch (PDOException $e) {
                // Sütun yok, ekle
                try {
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN challenge_fail_action VARCHAR(20) DEFAULT 'add_score'");
                    $hasChallengeFailAction = true;
                } catch (PDOException $e2) {
                    // Sütun eklenemedi, devam et
                }
            }
            
            $updateFields = "
                enable_ja3_check = :ja3,
                enable_canvas_check = :canvas,
                enable_webgl_check = :webgl,
                enable_audio_check = :audio,
                enable_webrtc_check = :webrtc,
                enable_fonts_check = :fonts,
                enable_plugins_check = :plugins,
                enable_headless_check = :headless,
                enable_challenge_check = :challenge,
                enable_rate_limit = :rate_limit,
                enable_residential_proxy_check = :residential,
                enable_cloudflare_bot_check = :cloudflare,
                enable_duplicate_check = :duplicate,
                enable_rdns_check = :rdns,
                enable_fingerprint_similarity = :fingerprint_similarity,
                enable_asn_check = :asn_check,
                enable_behavioral_analysis = :behavioral_analysis,
                enable_ip_age_check = :ip_age_check,
                enable_delayed_redirect = :delayed_redirect,
                enable_proxy_check = :proxy_check,
                enable_tls13_fingerprinting = :tls13_fingerprinting,
                enable_threat_intelligence = :threat_intelligence,
                canvas_score = :canvas_score,
                webgl_score = :webgl_score,
                audio_score = :audio_score,
                webrtc_score = :webrtc_score,
                headless_score = :headless_score,
                fonts_score = :fonts_score,
                plugins_score = :plugins_score,
                challenge_score = :challenge_score,
                speech_synthesis_score = :speech_synthesis_score,
                ja3_score = :ja3_score,
                rdns_score = :rdns_score,
                rdns_cache_ttl_hours = :rdns_cache_ttl,
                fingerprint_similarity_threshold_high = :fp_sim_high,
                fingerprint_similarity_threshold_low = :fp_sim_low,
                behavioral_bot_threshold = :behavioral_threshold,
                delayed_redirect_min_seconds = :delayed_min,
                delayed_redirect_max_seconds = :delayed_max,
                rate_limit_max_requests = :rate_max,
                rate_limit_window_seconds = :rate_window,
                bot_confidence_threshold = :bot_threshold,
                ml_enabled = :ml_enabled,
                dynamic_threshold_enabled = :dynamic_enabled,
                min_threshold = :min_threshold,
                max_threshold = :max_threshold
            ";
            
            $executeParams = [
                ':ja3' => $enableJA3,
                ':canvas' => $enableCanvas,
                ':webgl' => $enableWebGL,
                ':audio' => $enableAudio,
                ':webrtc' => $enableWebRTC,
                ':fonts' => $enableFonts,
                ':plugins' => $enablePlugins,
                ':headless' => $enableHeadless,
                ':challenge' => $enableChallenge,
                ':rate_limit' => $enableRateLimit,
                ':residential' => $enableResidentialProxy,
                ':cloudflare' => $enableCloudflareBot,
                ':duplicate' => $enableDuplicate,
                ':rdns' => $enableRDNS,
                ':fingerprint_similarity' => $enableFingerprintSimilarity,
                ':asn_check' => $enableASN,
                ':behavioral_analysis' => $enableBehavioralAnalysis,
                ':ip_age_check' => $enableIPAge,
                ':delayed_redirect' => $enableDelayedRedirect,
                ':proxy_check' => $enableProxyCheck,
                ':tls13_fingerprinting' => $enableTLS13Fingerprinting,
                ':threat_intelligence' => $enableThreatIntelligence,
                ':fp_sim_high' => $fingerprintSimilarityThresholdHigh,
                ':fp_sim_low' => $fingerprintSimilarityThresholdLow,
                ':behavioral_threshold' => $behavioralBotThreshold,
                ':delayed_min' => $delayedRedirectMin,
                ':delayed_max' => $delayedRedirectMax,
                ':canvas_score' => $canvasScore,
                ':webgl_score' => $webglScore,
                ':audio_score' => $audioScore,
                ':webrtc_score' => $webrtcScore,
                ':headless_score' => $headlessScore,
                ':fonts_score' => $fontsScore,
                ':plugins_score' => $pluginsScore,
                ':challenge_score' => $challengeScore,
                ':speech_synthesis_score' => $speechSynthesisScore,
                ':ja3_score' => $ja3Score,
                ':rdns_score' => $rdnsScore,
                ':rdns_cache_ttl' => $rdnsCacheTTL,
                ':rate_max' => $rateLimitMaxRequests,
                ':rate_window' => $rateLimitWindowSeconds,
                ':bot_threshold' => $botConfidenceThreshold,
                ':ml_enabled' => $mlEnabled,
                ':dynamic_enabled' => $dynamicThresholdEnabled,
                ':min_threshold' => $minThreshold,
                ':max_threshold' => $maxThreshold,
            ];
            
            if ($hasChallengeFailAction) {
                $updateFields .= ", challenge_fail_action = :challenge_fail_action";
                $executeParams[':challenge_fail_action'] = $challengeFailAction;
            }
            
            // Yeni sütunların varlığını kontrol et ve ekle
            $hasAdvancedColumns = false;
            try {
                $pdo->query("SELECT enable_fingerprint_similarity FROM cloacker_settings LIMIT 1");
                $hasAdvancedColumns = true;
            } catch (PDOException $e) {
                // Yeni sütunlar yok, ekle
                try {
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN enable_fingerprint_similarity tinyint(1) DEFAULT 1");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN enable_asn_check tinyint(1) DEFAULT 1");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN enable_behavioral_analysis tinyint(1) DEFAULT 1");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN enable_ip_age_check tinyint(1) DEFAULT 1");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN enable_delayed_redirect tinyint(1) DEFAULT 0");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN enable_proxy_check tinyint(1) DEFAULT 1");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN enable_tls13_fingerprinting tinyint(1) DEFAULT 1");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN enable_threat_intelligence tinyint(1) DEFAULT 1");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN fingerprint_similarity_threshold_high decimal(5,4) DEFAULT 0.98");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN fingerprint_similarity_threshold_low decimal(5,4) DEFAULT 0.85");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN behavioral_bot_threshold decimal(5,2) DEFAULT 70.00");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN delayed_redirect_min_seconds int(11) DEFAULT 7");
                    $pdo->exec("ALTER TABLE cloacker_settings ADD COLUMN delayed_redirect_max_seconds int(11) DEFAULT 15");
                    $hasAdvancedColumns = true;
                } catch (PDOException $e2) {
                    // Sütunlar eklenemedi, devam et
                }
            }
            
            // hasRDNSColumns kontrolü - eğer sütunlar yoksa ekle ama updateFields'e ekleme (zaten var)
            // hasAdvancedColumns kontrolü - eğer sütunlar yoksa ekle ama updateFields'e ekleme (zaten var)
            // Not: updateFields'de zaten bu sütunlar var, bu yüzden tekrar eklemiyoruz
            
            $stmt = $pdo->prepare("
                UPDATE cloacker_settings SET
                    $updateFields,
                    updated_at = NOW()
                WHERE id = 1
            ");
            $stmt->execute($executeParams);
        } else {
            // Eski format - sadece bot_confidence_threshold
            $stmt = $pdo->prepare("
                UPDATE cloacker_settings SET
                    bot_confidence_threshold = :bot_threshold,
                    updated_at = NOW()
                WHERE id = 1
            ");
            $stmt->execute([
                ':bot_threshold' => $botConfidenceThreshold,
            ]);
        }
        
        $success = "Bot filtre ayarları güncellendi.";
        
        // Ayarları yeniden yükle
        $stmt = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1");
        $settings = $stmt->fetch() ?: [];
    } catch (Exception $e) {
        $error = "Ayarlar güncellenirken hata oluştu: " . $e->getMessage();
    }
}

render_admin_layout_start('Bot Filtre Ayarları', 'settings');
?>

<?php if ($error): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
        <p class="text-red-800 dark:text-red-200"><?= htmlspecialchars($error) ?></p>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
        <p class="text-green-800 dark:text-green-200"><?= htmlspecialchars($success) ?></p>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    
    <div class="space-y-6">
        <!-- Bot Confidence Threshold -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Genel Bot Tespit Ayarları</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Bot Confidence Eşiği (%)
                    </label>
                    <input type="number" name="bot_confidence_threshold" 
                           value="<?= htmlspecialchars($settings['bot_confidence_threshold'] ?? 30.0) ?>" 
                           step="0.1" min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Bu değerin üzerindeki confidence skoruna sahip ziyaretçiler bot olarak işaretlenir.
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="ml_enabled" value="1" 
                                   <?= ($settings['ml_enabled'] ?? 1) ? 'checked' : '' ?>
                                   class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ML Tabanlı Skorlama</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="dynamic_threshold_enabled" value="1" 
                                   <?= ($settings['dynamic_threshold_enabled'] ?? 1) ? 'checked' : '' ?>
                                   class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Dinamik Eşik</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Minimum Eşik (%)
                        </label>
                        <input type="number" name="min_threshold" 
                               value="<?= htmlspecialchars($settings['min_threshold'] ?? 20.0) ?>" 
                               step="0.1" min="0" max="100"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Maksimum Eşik (%)
                        </label>
                        <input type="number" name="max_threshold" 
                               value="<?= htmlspecialchars($settings['max_threshold'] ?? 50.0) ?>" 
                               step="0.1" min="0" max="100"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bot Filtreleri -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Bot Filtreleri (Aktif/Pasif)</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_ja3_check" value="1" 
                           <?= ($settings['enable_ja3_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">JA3/JA3s Kontrolü</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_canvas_check" value="1" 
                           <?= ($settings['enable_canvas_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Canvas Fingerprint</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_webgl_check" value="1" 
                           <?= ($settings['enable_webgl_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">WebGL Fingerprint</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_audio_check" value="1" 
                           <?= ($settings['enable_audio_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Audio Fingerprint</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_webrtc_check" value="1" 
                           <?= ($settings['enable_webrtc_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">WebRTC Leak</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_fonts_check" value="1" 
                           <?= ($settings['enable_fonts_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Fonts Detection</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_plugins_check" value="1" 
                           <?= ($settings['enable_plugins_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Plugins Detection</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_headless_check" value="1" 
                           <?= ($settings['enable_headless_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Headless Detection</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_challenge_check" value="1" 
                           <?= ($settings['enable_challenge_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">JS Challenge</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_rate_limit" value="1" 
                           <?= ($settings['enable_rate_limit'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Rate Limiting</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_residential_proxy_check" value="1" 
                           <?= ($settings['enable_residential_proxy_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Residential Proxy</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_cloudflare_bot_check" value="1" 
                           <?= ($settings['enable_cloudflare_bot_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cloudflare Bot</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_duplicate_check" value="1" 
                           <?= ($settings['enable_duplicate_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Duplicate Check</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_rdns_check" value="1" 
                           <?= ($settings['enable_rdns_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">rDNS Kontrolü</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_fingerprint_similarity" value="1" 
                           <?= ($settings['enable_fingerprint_similarity'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Fingerprint Similarity (Cosine)</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_asn_check" value="1" 
                           <?= ($settings['enable_asn_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ASN & Datacenter Kontrolü</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_behavioral_analysis" value="1" 
                           <?= ($settings['enable_behavioral_analysis'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Behavioral Analysis</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_ip_age_check" value="1" 
                           <?= ($settings['enable_ip_age_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">IP Yaşı & Fraud Skoru</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_delayed_redirect" value="1" 
                           <?= ($settings['enable_delayed_redirect'] ?? 0) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Delayed Redirect</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_proxy_check" value="1" 
                           <?= ($settings['enable_proxy_check'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Proxy/VPN Kontrolü (IPHub)</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_tls13_fingerprinting" value="1" 
                           <?= ($settings['enable_tls13_fingerprinting'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">TLS 1.3 Fingerprinting</span>
                </label>
                
                <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" name="enable_threat_intelligence" value="1" 
                           <?= ($settings['enable_threat_intelligence'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-cyan-600 border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Real-time Threat Intelligence</span>
                </label>
            </div>
        </div>
        
        <!-- Skor Ayarları -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Filtre Skorları</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Her filtre için bot tespit skorunu ayarlayın. Yüksek skor = daha agresif tespit.
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Canvas Score
                    </label>
                    <input type="number" name="canvas_score" 
                           value="<?= htmlspecialchars($settings['canvas_score'] ?? 8) ?>" 
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        WebGL Score
                    </label>
                    <input type="number" name="webgl_score" 
                           value="<?= htmlspecialchars($settings['webgl_score'] ?? 7) ?>" 
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Audio Score
                    </label>
                    <input type="number" name="audio_score" 
                           value="<?= htmlspecialchars($settings['audio_score'] ?? 6) ?>" 
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        WebRTC Score
                    </label>
                    <input type="number" name="webrtc_score" 
                           value="<?= htmlspecialchars($settings['webrtc_score'] ?? 10) ?>" 
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Headless Score
                    </label>
                    <input type="number" name="headless_score" 
                           value="<?= htmlspecialchars($settings['headless_score'] ?? 12) ?>" 
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Fonts Score
                    </label>
                    <input type="number" name="fonts_score" 
                           value="<?= htmlspecialchars($settings['fonts_score'] ?? 4) ?>" 
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Plugins Score
                    </label>
                    <input type="number" name="plugins_score" 
                           value="<?= htmlspecialchars($settings['plugins_score'] ?? 3) ?>" 
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Challenge Score
                    </label>
                    <input type="number" name="challenge_score" 
                           value="<?= htmlspecialchars($settings['challenge_score'] ?? 15) ?>" 
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Speech Synthesis Score
                    </label>
                    <input type="number" name="speech_synthesis_score" 
                           value="<?= htmlspecialchars($settings['speech_synthesis_score'] ?? 3) ?>" 
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        JA3/JA3s Score
                    </label>
                    <input type="number" name="ja3_score" 
                           value="<?= htmlspecialchars($settings['ja3_score'] ?? 20) ?>" 
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        JA3 blacklist'te bulunan ziyaretçiler için eklenecek skor
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        rDNS Score
                    </label>
                    <input type="number" name="rdns_score" 
                           value="<?= htmlspecialchars($settings['rdns_score'] ?? 20) ?>" 
                           min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        rDNS kontrolünde bot hostname tespit edildiğinde eklenecek skor
                    </p>
                </div>
            </div>
            
            <!-- Challenge Fail Action -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Challenge Başarısız Olduğunda Yapılacak İşlem
                </label>
                <select name="challenge_fail_action" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <option value="add_score" <?= ($settings['challenge_fail_action'] ?? 'add_score') === 'add_score' ? 'selected' : '' ?>>
                        Sadece Skor Ekle (Önerilen - Daha Esnek)
                    </option>
                    <option value="mark_bot" <?= ($settings['challenge_fail_action'] ?? 'add_score') === 'mark_bot' ? 'selected' : '' ?>>
                        Direkt Bot Olarak İşaretle (Agresif)
                    </option>
                </select>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <strong>Sadece Skor Ekle:</strong> Challenge başarısız olduğunda sadece bot confidence skoruna ekleme yapar. Diğer faktörlerle birlikte değerlendirilir.<br>
                    <strong>Direkt Bot Olarak İşaretle:</strong> Challenge başarısız olduğunda ziyaretçiyi direkt bot olarak işaretler (eski davranış).
                </p>
            </div>
        </div>
        
        <!-- Fingerprint Similarity Ayarları -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Fingerprint Similarity (Cosine Similarity) Ayarları</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Yüksek Benzerlik Eşiği (0.0-1.0)
                    </label>
                    <input type="number" name="fingerprint_similarity_threshold_high" 
                           value="<?= htmlspecialchars($settings['fingerprint_similarity_threshold_high'] ?? 0.98) ?>" 
                           step="0.01" min="0" max="1"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Örnek: <span class="text-cyan-400">0.98</span> - Bu değerin üzerindeki similarity = güvenilir ziyaretçi (whitelist)
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Düşük Benzerlik Eşiği (0.0-1.0)
                    </label>
                    <input type="number" name="fingerprint_similarity_threshold_low" 
                           value="<?= htmlspecialchars($settings['fingerprint_similarity_threshold_low'] ?? 0.85) ?>" 
                           step="0.01" min="0" max="1"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Örnek: <span class="text-cyan-400">0.85</span> - Bu değerin altındaki similarity = şüpheli (review gerekli)
                    </p>
                </div>
            </div>
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-xs text-blue-800 dark:text-blue-200">
                    <strong>Cosine Similarity Nedir?</strong><br>
                    Fingerprint'lerin benzerliğini ölçer. Yüksek similarity (0.98+) = aynı cihaz/tarayıcı (güvenilir). 
                    Düşük similarity (0.85-) = farklı fingerprint (şüpheli). Bu sayede bot taklitlerini tespit edebilirsiniz.
                </p>
            </div>
        </div>
        
        <!-- Behavioral Analysis Ayarları -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Behavioral Analysis Ayarları</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Behavioral Bot Threshold (%)
                    </label>
                    <input type="number" name="behavioral_bot_threshold" 
                           value="<?= htmlspecialchars($settings['behavioral_bot_threshold'] ?? 70.0) ?>" 
                           step="0.1" min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Örnek: <span class="text-cyan-400">70.0</span> - Bu değerin üzerindeki behavioral skor = bot olarak işaretlenir
                    </p>
                </div>
            </div>
            <div class="mt-4 p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                <p class="text-xs text-purple-800 dark:text-purple-200">
                    <strong>Behavioral Analysis Nedir?</strong><br>
                    Kullanıcı davranışlarını analiz eder (mouse hareketleri, scroll pattern, click timing vb.). 
                    Botlar genelde insan benzeri davranış gösteremez. Bu analiz ile bot tespiti yapılır.
                </p>
            </div>
        </div>
        
        <!-- Delayed Redirect Ayarları -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Delayed Redirect Ayarları</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Minimum Gecikme (Saniye)
                    </label>
                    <input type="number" name="delayed_redirect_min_seconds" 
                           value="<?= htmlspecialchars($settings['delayed_redirect_min_seconds'] ?? 7) ?>" 
                           min="1" max="60"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Örnek: <span class="text-cyan-400">7</span> - Minimum bekleme süresi (saniye)
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Maksimum Gecikme (Saniye)
                    </label>
                    <input type="number" name="delayed_redirect_max_seconds" 
                           value="<?= htmlspecialchars($settings['delayed_redirect_max_seconds'] ?? 15) ?>" 
                           min="1" max="60"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Örnek: <span class="text-cyan-400">15</span> - Maksimum bekleme süresi (saniye)
                    </p>
                </div>
            </div>
            <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <p class="text-xs text-yellow-800 dark:text-yellow-200">
                    <strong>Delayed Redirect Nedir?</strong><br>
                    Botları tespit etmek için yönlendirmeyi geciktirir. Botlar genelde JavaScript çalıştıramaz veya 
                    bekleyemez. Bu sayede bot trafiği filtrelenir. Normal kullanıcılar için kısa bir bekleme süresi ekler.
                </p>
            </div>
        </div>
        
        <!-- rDNS Ayarları -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">rDNS (Reverse DNS) Ayarları</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        rDNS Cache TTL (Saat)
                    </label>
                    <input type="number" name="rdns_cache_ttl_hours" 
                           value="<?= htmlspecialchars($settings['rdns_cache_ttl_hours'] ?? 24) ?>" 
                           min="1" max="168"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        rDNS sonuçlarının cache'de ne kadar süre tutulacağı (1-168 saat arası). Önerilen: 24 saat
                    </p>
                </div>
            </div>
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-xs text-blue-800 dark:text-blue-200">
                    <strong>rDNS Kontrolü Nedir?</strong><br>
                    Reverse DNS kontrolü, IP adresinin gerçekten bir bot servisinden gelip gelmediğini doğrular. 
                    Örneğin Googlebot'un IP'si googlebot.com ile biten bir hostname'e sahip olmalıdır. 
                    Bu kontrol, sahte bot trafiğini tespit etmek için çok etkilidir.
                </p>
            </div>
        </div>
        
        <!-- TLS 1.3 Fingerprinting Ayarları -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">TLS 1.3 Fingerprinting Ayarları</h3>
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-xs text-blue-800 dark:text-blue-200">
                    <strong>TLS 1.3 Fingerprinting Nedir?</strong><br>
                    TLS 1.3 handshake sırasında toplanan fingerprint verileri ile bot tespiti yapılır. 
                    JA3/JA3s hash'lerinden farklı olarak, TLS 1.3'ün özel özelliklerini (cipher suites, extensions, signature algorithms) analiz eder.
                    Bu sayede gelişmiş bot taklitlerini ve automation framework'lerini tespit edebilirsiniz.
                </p>
            </div>
        </div>
        
        <!-- Real-time Threat Intelligence Ayarları -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Real-time Threat Intelligence Ayarları</h3>
            <div class="mt-4 p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                <p class="text-xs text-purple-800 dark:text-purple-200">
                    <strong>Real-time Threat Intelligence Nedir?</strong><br>
                    IP adreslerini gerçek zamanlı olarak threat intelligence veritabanları ile kontrol eder (AbuseIPDB, VirusTotal vb.).
                    Kötü amaçlı aktivite geçmişi olan IP'leri, botnet üyelerini ve bilinen saldırı kaynaklarını tespit eder.
                    Bu sayede proaktif güvenlik sağlanır ve bilinen tehditler otomatik olarak engellenir.
                </p>
            </div>
        </div>
        
        <!-- Rate Limiting Ayarları -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Rate Limiting Ayarları</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Maksimum İstek Sayısı
                    </label>
                    <input type="number" name="rate_limit_max_requests" 
                           value="<?= htmlspecialchars($settings['rate_limit_max_requests'] ?? 10) ?>" 
                           min="1" max="1000"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Belirtilen süre içinde izin verilen maksimum istek sayısı
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Zaman Penceresi (Saniye)
                    </label>
                    <input type="number" name="rate_limit_window_seconds" 
                           value="<?= htmlspecialchars($settings['rate_limit_window_seconds'] ?? 60) ?>" 
                           min="1" max="3600"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Rate limit kontrolü için zaman penceresi
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Kaydet Butonu -->
        <div class="flex justify-end">
            <button type="submit" name="save_settings" 
                    class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-lg hover:from-cyan-600 hover:to-teal-600 transition font-medium">
                Ayarları Kaydet
            </button>
        </div>
    </div>
</form>

<?php render_admin_layout_end(); ?>
