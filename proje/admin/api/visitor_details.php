<?php
/**
 * Ziyaretçi Detayları API
 */

require_once __DIR__ . '/../includes/admin_guard.php';
require_once __DIR__ . '/../../cloacker.php';

enforceAdminSession();

header('Content-Type: application/json; charset=utf-8');

$visitorId = (int)($_GET['id'] ?? 0);

if ($visitorId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz ziyaretçi ID']);
    exit;
}

try {
    $pdo = DB::connect();
    
    // Ziyaretçi bilgilerini al
    $stmt = $pdo->prepare("
        SELECT 
            v.*,
            s.name AS site_name
        FROM cloacker_visitors v
        LEFT JOIN cloacker_sites s ON v.site_id = s.id
        WHERE v.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $visitorId]);
    $visitor = $stmt->fetch();
    
    if (!$visitor) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Ziyaretçi bulunamadı']);
        exit;
    }
    
    // Detection details'i cloacker_bot_detections tablosundan al
    $detectionDetails = [];
    try {
        $detStmt = $pdo->prepare("
            SELECT detection_type, score, details 
            FROM cloacker_bot_detections 
            WHERE visitor_id = :visitor_id 
            ORDER BY created_at ASC
        ");
        $detStmt->execute([':visitor_id' => $visitorId]);
        $detections = $detStmt->fetchAll();
        
        foreach ($detections as $det) {
            $detectionDetails[] = [
                'type' => $det['detection_type'],
                'score' => (int)$det['score'],
                'signal' => $det['detection_type'], // Signal olarak da kullanılabilir
                'details' => !empty($det['details']) ? json_decode($det['details'], true) : []
            ];
        }
    } catch (Exception $e) {
        // Tablo yoksa veya hata varsa boş array kullan
        error_log("Detection details okuma hatası: " . $e->getMessage());
    }
    
    // Eğer detection_details sütunu varsa ondan da oku (geriye dönük uyumluluk)
    if (empty($detectionDetails) && !empty($visitor['detection_details'])) {
        $detectionDetails = json_decode($visitor['detection_details'], true) ?: [];
    }
    
    // Ayarları al (hangi filtrelerin aktif olduğunu görmek için)
    $settingsStmt = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1");
    $settings = $settingsStmt->fetch() ?: [];
    
    // Bot adını belirle
    $botName = null;
    if ($visitor['is_bot']) {
        $ua = strtolower($visitor['user_agent'] ?? '');
        if (strpos($ua, 'googlebot') !== false) $botName = 'Googlebot';
        elseif (strpos($ua, 'bingbot') !== false) $botName = 'Bingbot';
        elseif (strpos($ua, 'facebookexternalhit') !== false || strpos($ua, 'facebot') !== false) $botName = 'Facebook Bot';
        elseif (strpos($ua, 'twitterbot') !== false) $botName = 'Twitterbot';
        elseif (strpos($ua, 'discordbot') !== false) $botName = 'Discordbot';
        elseif (strpos($ua, 'linkedinbot') !== false) $botName = 'LinkedIn Bot';
        elseif (strpos($ua, 'applebot') !== false) $botName = 'Applebot';
        elseif (strpos($ua, 'telegrambot') !== false) $botName = 'Telegrambot';
        else $botName = 'Bot';
    }
    
    // Proxy/VPN bilgisi
    $proxyType = null;
    if ($visitor['is_proxy']) {
        // IP2Location API'den proxy tipini al (cache'den)
        $cacheKey = 'ip2location_' . md5($visitor['ip']);
        if (isset($_SESSION[$cacheKey])) {
            $proxyType = 'Proxy/VPN';
        } else {
            $proxyType = 'Proxy/VPN';
        }
    }
    
    // Lokasyon bilgisi (IP'den)
    $location = [
        'country' => $visitor['country'] ?? 'UN',
        'ip' => $visitor['ip'] ?? '',
    ];
    
    // Aktif filtreler ve tespit durumları
    $filters = [
        'ja3_check' => [
            'name' => 'JA3/JA3s Kontrolü',
            'enabled' => (bool)($settings['enable_ja3_check'] ?? 1),
            'detected' => false, // Blacklist kontrolü yapılacak
            'value' => null, // Formatlanacak
        ],
        'canvas_check' => [
            'name' => 'Canvas Fingerprint',
            'enabled' => (bool)($settings['enable_canvas_check'] ?? 1),
            'detected' => empty($visitor['canvas_fingerprint']), // Canvas YOKSA bot (detected = true)
            'value' => $visitor['canvas_fingerprint'] ?? null,
        ],
        'webgl_check' => [
            'name' => 'WebGL Fingerprint',
            'enabled' => (bool)($settings['enable_webgl_check'] ?? 1),
            'detected' => empty($visitor['webgl_fingerprint']), // WebGL YOKSA bot (detected = true)
            'value' => $visitor['webgl_fingerprint'] ?? null,
        ],
        'audio_check' => [
            'name' => 'Audio Fingerprint',
            'enabled' => (bool)($settings['enable_audio_check'] ?? 1),
            'detected' => empty($visitor['audio_fingerprint']), // Audio YOKSA bot (detected = true)
            'value' => $visitor['audio_fingerprint'] ?? null,
        ],
        'webrtc_check' => [
            'name' => 'WebRTC Leak',
            'enabled' => (bool)($settings['enable_webrtc_check'] ?? 1),
            'detected' => (bool)($visitor['webrtc_leak'] ?? 0),
            'value' => null, // Formatlanacak
        ],
        'fonts_check' => [
            'name' => 'Fonts Detection',
            'enabled' => (bool)($settings['enable_fonts_check'] ?? 1),
            'detected' => empty($visitor['fonts_hash']), // Fonts YOKSA bot (detected = true)
            'value' => $visitor['fonts_hash'] ?? null,
        ],
        'plugins_check' => [
            'name' => 'Plugins Detection',
            'enabled' => (bool)($settings['enable_plugins_check'] ?? 1),
            'detected' => empty($visitor['plugins_hash']), // Plugins YOKSA bot (detected = true)
            'value' => $visitor['plugins_hash'] ?? null,
        ],
        'headless_check' => [
            'name' => 'Headless Detection',
            'enabled' => (bool)($settings['enable_headless_check'] ?? 1),
            'detected' => false, // Detaylardan kontrol edilecek
            'value' => null, // Formatlanacak
        ],
        'challenge_check' => [
            'name' => 'JS Challenge',
            'enabled' => (bool)($settings['enable_challenge_check'] ?? 1),
            'detected' => false, // Detaylardan kontrol edilecek
            'value' => null, // Formatlanacak
        ],
        'rate_limit' => [
            'name' => 'Rate Limiting',
            'enabled' => (bool)($settings['enable_rate_limit'] ?? 1),
            'detected' => false,
            'value' => null, // Formatlanacak
        ],
        'residential_proxy' => [
            'name' => 'Residential Proxy',
            'enabled' => (bool)($settings['enable_residential_proxy_check'] ?? 1),
            'detected' => (bool)($visitor['is_proxy'] ?? 0),
        ],
        'cloudflare_bot' => [
            'name' => 'Cloudflare Bot',
            'enabled' => (bool)($settings['enable_cloudflare_bot_check'] ?? 1),
            'detected' => false,
        ],
        'duplicate_check' => [
            'name' => 'Duplicate Check',
            'enabled' => (bool)($settings['enable_duplicate_check'] ?? 1),
            'detected' => false,
            'value' => null, // Formatlanacak
        ],
        'rdns_check' => [
            'name' => 'rDNS Kontrolü',
            'enabled' => (bool)($settings['enable_rdns_check'] ?? 1),
            'detected' => (bool)($visitor['rdns_is_bot'] ?? 0),
            'value' => $visitor['rdns_hostname'] ?? null,
        ],
        'fingerprint_similarity' => [
            'name' => 'Fingerprint Similarity',
            'enabled' => (bool)($settings['enable_fingerprint_similarity'] ?? 1),
            'detected' => false, // Similarity skoruna göre belirlenecek
            'value' => null, // Formatlanacak
        ],
        'behavioral_analysis' => [
            'name' => 'Behavioral Analysis',
            'enabled' => (bool)($settings['enable_behavioral_analysis'] ?? 1),
            'detected' => false, // Behavioral bot score'a göre belirlenecek
            'value' => null, // Formatlanacak
        ],
        'asn_check' => [
            'name' => 'ASN & Datacenter Kontrolü',
            'enabled' => (bool)($settings['enable_asn_check'] ?? 1),
            'detected' => false, // ASN bilgisine göre belirlenecek
            'value' => $visitor['asn'] ?? null,
        ],
        'ip_age_check' => [
            'name' => 'IP Yaşı & Fraud Skoru',
            'enabled' => (bool)($settings['enable_ip_age_check'] ?? 1),
            'detected' => false, // IP yaşı ve fraud skoruna göre belirlenecek
            'value' => null, // IP yaşı bilgisi henüz kaydedilmiyor
        ],
        'delayed_redirect' => [
            'name' => 'Delayed Redirect',
            'enabled' => (bool)($settings['enable_delayed_redirect'] ?? 0),
            'detected' => false, // Delayed redirect kullanıldı mı?
            'value' => null, // Formatlanacak
        ],
        'proxy_check' => [
            'name' => 'Proxy/VPN Kontrolü (IPHub)',
            'enabled' => (bool)($settings['enable_proxy_check'] ?? 1),
            'detected' => (bool)($visitor['is_proxy'] ?? 0),
            'value' => $visitor['is_proxy'] ? 'Proxy/VPN Tespit Edildi' : null,
        ],
        'tls13_fingerprinting' => [
            'name' => 'TLS 1.3 Fingerprinting',
            'enabled' => (bool)($settings['enable_tls13_fingerprinting'] ?? 1),
            'detected' => false, // TLS 1.3 fingerprint kontrolü yapılacak
            'value' => $visitor['tls13_fingerprint'] ?? null,
        ],
        'threat_intelligence' => [
            'name' => 'Real-time Threat Intelligence',
            'enabled' => (bool)($settings['enable_threat_intelligence'] ?? 1),
            'detected' => false, // Threat intelligence kontrolü yapılacak
            'value' => $visitor['threat_score'] ?? null,
        ],
    ];
    
    // Fingerprint similarity kontrolü
    if (isset($visitor['fingerprint_similarity']) && $visitor['fingerprint_similarity'] !== null) {
        $similarity = (float)$visitor['fingerprint_similarity'];
        $thresholdLow = (float)($settings['fingerprint_similarity_threshold_low'] ?? 0.85);
        // Düşük similarity = şüpheli (detected = true)
        $filters['fingerprint_similarity']['detected'] = $similarity < $thresholdLow;
        $filters['fingerprint_similarity']['value'] = number_format($similarity * 100, 2) . '%';
    } else {
        $filters['fingerprint_similarity']['value'] = 'Hesaplanmadı';
    }
    
    // Behavioral analysis kontrolü
    if (isset($visitor['behavioral_bot_score']) && $visitor['behavioral_bot_score'] !== null) {
        $behavioralScore = (float)$visitor['behavioral_bot_score'];
        $behavioralThreshold = (float)($settings['behavioral_bot_threshold'] ?? 70.0);
        // Yüksek behavioral score = bot (detected = true)
        $filters['behavioral_analysis']['detected'] = $behavioralScore >= $behavioralThreshold;
        $filters['behavioral_analysis']['value'] = number_format($behavioralScore, 2) . '%';
    } else {
        $filters['behavioral_analysis']['value'] = 'Hesaplanmadı';
    }
    
    // ASN kontrolü
    if (($settings['enable_asn_check'] ?? 1)) {
        // Önce veritabanından kontrol et
        if (!empty($visitor['asn'])) {
            $filters['asn_check']['value'] = $visitor['asn'] . ($visitor['asn_name'] ? ' (' . $visitor['asn_name'] . ')' : '');
            // Datacenter ASN kontrolü yapılabilir (örnek: AS13335 = Cloudflare, AS15169 = Google)
            $datacenterASNs = ['13335', '15169', '16509', '20940', '32934', '15133', '8075']; // Örnek datacenter ASN'leri
            if (in_array($visitor['asn'], $datacenterASNs)) {
                $filters['asn_check']['detected'] = true;
            }
            if (!$filters['asn_check']['detected'] && !empty($visitor['is_datacenter'])) {
                $filters['asn_check']['detected'] = true;
            }
        } else {
            // Veritabanında yoksa gerçek zamanlı kontrol yap
            require_once __DIR__ . '/../../cloacker.php';
            $ipInfo = getIPInfo($visitor['ip']);
            if ($ipInfo && !empty($ipInfo['asn'])) {
                $filters['asn_check']['value'] = $ipInfo['asn'] . ($ipInfo['asn_name'] ? ' (' . $ipInfo['asn_name'] . ')' : '');
                // Datacenter ASN kontrolü
                $datacenterASNs = ['13335', '15169', '16509', '20940', '32934', '15133', '8075'];
                // ASN'den sadece numarayı al (AS13335 -> 13335)
                $asnNumber = preg_replace('/^AS/i', '', $ipInfo['asn']);
                if (in_array($asnNumber, $datacenterASNs)) {
                    $filters['asn_check']['detected'] = true;
                }
            } else {
                $filters['asn_check']['value'] = 'ASN bilgisi alınamadı';
            }
        }
        if (!$filters['asn_check']['detected'] && !empty($visitor['is_datacenter'])) {
            $filters['asn_check']['detected'] = true;
            if (empty($filters['asn_check']['value']) && !empty($visitor['asn'])) {
                $filters['asn_check']['value'] = $visitor['asn'] . ($visitor['asn_name'] ? ' (' . $visitor['asn_name'] . ')' : '');
            }
        }
    }
    
    // IP Age kontrolü
    if ($settings['enable_ip_age_check'] ?? 1) {
        // Önce veritabanından kontrol et
        if (!empty($visitor['ip_age_days']) || !empty($visitor['fraud_score'])) {
            $ipAgeDays = $visitor['ip_age_days'] ?? null;
            $fraudScore = $visitor['fraud_score'] ?? null;
            
            $valueParts = [];
            if ($ipAgeDays !== null) {
                $valueParts[] = 'IP Yaşı: ' . $ipAgeDays . ' gün';
            }
            if ($fraudScore !== null) {
                $valueParts[] = 'Fraud Skoru: ' . $fraudScore;
                // Fraud score 75'ten yüksekse şüpheli
                if ($fraudScore >= 75) {
                    $filters['ip_age_check']['detected'] = true;
                }
            }
            $filters['ip_age_check']['value'] = !empty($valueParts) ? implode(', ', $valueParts) : null;
        } else {
            // Veritabanında yoksa gerçek zamanlı kontrol yap
            require_once __DIR__ . '/../../cloacker.php';
            $ipAgeData = getIPAgeAndFraudScore($visitor['ip']);
            if ($ipAgeData) {
                $valueParts = [];
                if (isset($ipAgeData['ip_age_days']) && $ipAgeData['ip_age_days'] !== null) {
                    $valueParts[] = 'IP Yaşı: ' . $ipAgeData['ip_age_days'] . ' gün';
                    // Çok yeni IP'ler (30 günden az) şüpheli olabilir
                    if ($ipAgeData['ip_age_days'] < 30) {
                        $filters['ip_age_check']['detected'] = true;
                    }
                }
                if (isset($ipAgeData['fraud_score']) && $ipAgeData['fraud_score'] !== null) {
                    $fraudScore = (int)$ipAgeData['fraud_score'];
                    $valueParts[] = 'Fraud Skoru: ' . $fraudScore;
                    // Fraud score 75'ten yüksekse şüpheli
                    if ($fraudScore >= 75) {
                        $filters['ip_age_check']['detected'] = true;
                    }
                }
                if (isset($ipAgeData['is_datacenter']) && $ipAgeData['is_datacenter']) {
                    $valueParts[] = 'Datacenter IP';
                    $filters['ip_age_check']['detected'] = true;
                }
                $filters['ip_age_check']['value'] = !empty($valueParts) ? implode(', ', $valueParts) : 'Bilgi alınamadı';
            } else {
                $filters['ip_age_check']['value'] = 'Kontrol edilemedi (API hatası veya limit)';
            }
        }
    }
    
    // Detection details'den headless, challenge, cloudflare bot ve diğer bilgilerini çıkar
    foreach ($detectionDetails as $det) {
        $detType = strtolower($det['type'] ?? '');
        $detSignal = strtolower($det['signal'] ?? '');
        
        // Type bazlı kontrol
        if ($detType === 'headless_detection' || 
            strpos($detType, 'headless') !== false ||
            strpos($detSignal, 'headless') !== false ||
            strpos($detSignal, 'navigator.webdriver') !== false ||
            strpos($detSignal, 'missing_chrome_runtime') !== false ||
            strpos($detSignal, 'missing_permissions_api') !== false ||
            strpos($detSignal, 'iframe_webdriver') !== false) {
            $filters['headless_check']['detected'] = true;
            if (empty($filters['headless_check']['value'])) {
                $filters['headless_check']['value'] = 'Headless browser tespit edildi';
            }
        }
        
        if ($detType === 'client_challenge_failed' || 
            strpos($detType, 'challenge') !== false ||
            strpos($detSignal, 'challenge') !== false ||
            strpos($detSignal, 'client_challenge') !== false) {
            $filters['challenge_check']['detected'] = true;
            if (empty($filters['challenge_check']['value'])) {
                $filters['challenge_check']['value'] = 'JS Challenge başarısız';
            }
        }
        
        if ($detType === 'rate_limit_exceeded' || 
            strpos($detType, 'rate_limit') !== false ||
            strpos($detSignal, 'rate_limit') !== false) {
            $filters['rate_limit']['detected'] = true;
            if (empty($filters['rate_limit']['value'])) {
                $filters['rate_limit']['value'] = 'Rate limit aşıldı';
            }
        }
        
        if ($detType === 'duplicate_visitor' || 
            strpos($detType, 'duplicate') !== false ||
            strpos($detSignal, 'duplicate') !== false) {
            $filters['duplicate_check']['detected'] = true;
            if (empty($filters['duplicate_check']['value'])) {
                $filters['duplicate_check']['value'] = 'Tekrarlayan ziyaretçi';
            }
        }
        
        if ($detType === 'cloudflare_bot' || 
            strpos($detType, 'cloudflare') !== false ||
            strpos($detSignal, 'cloudflare') !== false) {
            $filters['cloudflare_bot']['detected'] = true;
        }
        
        // Details içinden de kontrol et
        if (isset($det['details']) && is_array($det['details'])) {
            foreach ($det['details'] as $key => $value) {
                $keyLower = strtolower($key);
                if (strpos($keyLower, 'headless') !== false || 
                    strpos($keyLower, 'webdriver') !== false) {
                    $filters['headless_check']['detected'] = true;
                }
                if (strpos($keyLower, 'challenge') !== false) {
                    $filters['challenge_check']['detected'] = true;
                }
            }
        }
    }
    
    // JA3 hash blacklist kontrolü
    if (($settings['enable_ja3_check'] ?? 1)) {
        $ja3Hash = $visitor['ja3_hash'] ?? null;
        $ja3sHash = $visitor['ja3s_hash'] ?? null;
        
        if (!empty($ja3Hash) || !empty($ja3sHash)) {
            $ja3Value = [];
            if (!empty($ja3Hash)) {
                $ja3Value[] = 'JA3: ' . substr($ja3Hash, 0, 16) . '...';
            }
            if (!empty($ja3sHash)) {
                $ja3Value[] = 'JA3s: ' . substr($ja3sHash, 0, 16) . '...';
            }
            $filters['ja3_check']['value'] = implode(', ', $ja3Value);
            
            if (!empty($ja3Hash)) {
                try {
                    $ja3Stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM cloacker_ja3_blacklist 
                        WHERE ja3_hash = :ja3 
                        AND is_active = 1
                    ");
                    $ja3Stmt->execute([':ja3' => $ja3Hash]);
                    $ja3Count = (int)$ja3Stmt->fetchColumn();
                    if ($ja3Count > 0) {
                        $filters['ja3_check']['detected'] = true;
                        $filters['ja3_check']['value'] .= ' (Blacklist\'te)';
                    }
                } catch (Exception $e) {
                    // Tablo yoksa veya hata varsa sadece hash varlığını kontrol et
                    // Detected false kalır
                }
            }
        } else {
            $filters['ja3_check']['value'] = 'JA3/JA3s hash yok';
        }
    }
    
    // TLS 1.3 Fingerprinting kontrolü
    if (($settings['enable_tls13_fingerprinting'] ?? 1)) {
        // TLS 1.3 fingerprint verisi varsa kontrol et
        if (!empty($visitor['tls13_fingerprint'])) {
            // TLS 1.3 fingerprint blacklist kontrolü yapılabilir
            // Şimdilik sadece fingerprint varlığını kontrol ediyoruz
            $filters['tls13_fingerprinting']['detected'] = false; // İleride blacklist kontrolü eklenecek
        } else {
            // TLS 1.3 fingerprint yoksa şüpheli (bot olabilir)
            $filters['tls13_fingerprinting']['detected'] = true;
        }
    }
    
    // Real-time Threat Intelligence kontrolü
    if (($settings['enable_threat_intelligence'] ?? 1)) {
        // Önce veritabanından kontrol et
        if (!empty($visitor['threat_score'])) {
            $threatScore = (float)$visitor['threat_score'];
            // Threat score 50'den yüksekse tehdit olarak işaretle
            $filters['threat_intelligence']['detected'] = $threatScore >= 50;
            $filters['threat_intelligence']['value'] = 'Threat Score: ' . number_format($threatScore, 2) . '%';
        } else {
            // Veritabanında yoksa gerçek zamanlı kontrol yap
            require_once __DIR__ . '/../../cloacker.php';
            $threatData = checkThreatIntelligence($visitor['ip']);
            if ($threatData) {
                $threatScore = (float)$threatData['threat_score'];
                $filters['threat_intelligence']['detected'] = $threatScore >= 50;
                $filters['threat_intelligence']['value'] = 'Threat Score: ' . number_format($threatScore, 2) . '%';
                if ($threatData['total_reports'] > 0) {
                    $filters['threat_intelligence']['value'] .= ' (Raporlar: ' . $threatData['total_reports'] . ')';
                }
            } else {
                $filters['threat_intelligence']['value'] = 'Kontrol edilemedi (API hatası veya limit)';
            }
        }
    }
    
    // Client challenge solved kontrolü
    if (isset($visitor['client_challenge_solved']) && !$visitor['client_challenge_solved']) {
        $filters['challenge_check']['detected'] = true;
        if (empty($filters['challenge_check']['value'])) {
            $filters['challenge_check']['value'] = 'Client challenge çözülemedi';
        }
    }
    
    // WebRTC Leak value formatla
    if (($settings['enable_webrtc_check'] ?? 1)) {
        if ($visitor['webrtc_leak'] ?? false) {
            $localIPs = $visitor['local_ip_detected'] ?? null;
            if ($localIPs) {
                $filters['webrtc_check']['value'] = 'Local IP sızıntısı: ' . $localIPs;
            } else {
                $filters['webrtc_check']['value'] = 'WebRTC leak tespit edildi';
            }
        } else {
            $filters['webrtc_check']['value'] = 'WebRTC leak yok';
        }
    }
    
    // Headless, Challenge, Rate Limit, Duplicate için value yoksa default değer
    if (empty($filters['headless_check']['value']) && !$filters['headless_check']['detected']) {
        $filters['headless_check']['value'] = 'Headless tespit edilmedi';
    }
    if (empty($filters['challenge_check']['value']) && !$filters['challenge_check']['detected']) {
        $filters['challenge_check']['value'] = 'JS Challenge başarılı';
    }
    if (empty($filters['rate_limit']['value']) && !$filters['rate_limit']['detected']) {
        $filters['rate_limit']['value'] = 'Rate limit normal';
    }
    if (empty($filters['duplicate_check']['value']) && !$filters['duplicate_check']['detected']) {
        $filters['duplicate_check']['value'] = 'Tekrarlayan ziyaret yok';
    }
    
    // Delayed Redirect kontrolü
    if (($settings['enable_delayed_redirect'] ?? 0)) {
        $minDelay = (int)($settings['delayed_redirect_min_seconds'] ?? 7);
        $maxDelay = (int)($settings['delayed_redirect_max_seconds'] ?? max($minDelay, 15));
        $filters['delayed_redirect']['value'] = sprintf(
            'Aktif (%d-%d sn arasında gecikme)',
            $minDelay,
            $maxDelay >= $minDelay ? $maxDelay : $minDelay
        );
    } else {
        $filters['delayed_redirect']['value'] = 'Pasif';
    }
    
    // WebGL fingerprint hash kontrolü (eğer obje olarak kaydedilmişse)
    if (!empty($visitor['webgl_fingerprint']) && ($settings['enable_webgl_check'] ?? 1)) {
        // WebGL fingerprint hash olarak kaydedilmiş olmalı
        // Eğer JSON string ise parse et
        $webglValue = $visitor['webgl_fingerprint'];
        if (is_string($webglValue) && (strpos($webglValue, '{') === 0 || strpos($webglValue, '[') === 0)) {
            $webglParsed = json_decode($webglValue, true);
            if ($webglParsed !== null) {
                // Obje olarak parse edildiyse hash'e çevir
                $filters['webgl_check']['value'] = hash('sha256', json_encode($webglParsed));
            }
        }
    }
    
    // Neden bot/proxy/vpn olduğu
    $reasons = [];
    if ($visitor['is_bot']) {
        if ($visitor['fingerprint_score'] > 0) {
            $reasons[] = 'Fingerprint skoru: ' . $visitor['fingerprint_score'];
        }
        if ($visitor['bot_confidence'] > 0) {
            $reasons[] = 'Bot confidence: %' . number_format($visitor['bot_confidence'], 2);
        }
        if (!empty($visitor['ja3_hash'])) {
            $reasons[] = 'JA3 hash blacklist\'te';
        }
        if ($visitor['webrtc_leak']) {
            $reasons[] = 'WebRTC leak tespit edildi';
        }
        if (empty($visitor['canvas_fingerprint'])) {
            $reasons[] = 'Canvas fingerprint eksik';
        }
    }
    if ($visitor['is_proxy']) {
        $reasons[] = 'Proxy/VPN tespit edildi';
        if ($visitor['webrtc_leak']) {
            $reasons[] = 'WebRTC ile local IP sızıntısı';
        }
    }
    
    echo json_encode([
        'status' => 'ok',
        'visitor' => [
            'id' => $visitor['id'],
            'ip' => $visitor['ip'],
            'user_agent' => $visitor['user_agent'],
            'country' => $visitor['country'],
            'os' => $visitor['os'],
            'browser' => $visitor['browser'],
            'referer' => $visitor['referer'],
            'is_bot' => (bool)$visitor['is_bot'],
            'is_proxy' => (bool)$visitor['is_proxy'],
            'redirect_target' => $visitor['redirect_target'],
            'is_fake_url' => (bool)$visitor['is_fake_url'],
            'fingerprint_score' => $visitor['fingerprint_score'],
            'bot_confidence' => $visitor['bot_confidence'],
            'ml_confidence' => $visitor['ml_confidence'],
            'created_at' => $visitor['created_at'],
            'site_name' => $visitor['site_name'],
            'rdns_hostname' => $visitor['rdns_hostname'] ?? null,
            'rdns_is_bot' => (bool)($visitor['rdns_is_bot'] ?? 0),
            'fingerprint_similarity' => $visitor['fingerprint_similarity'] ?? null,
            'behavioral_bot_score' => $visitor['behavioral_bot_score'] ?? null,
            'asn' => $visitor['asn'] ?? null,
            'asn_name' => $visitor['asn_name'] ?? null,
            'fingerprint_hash' => $visitor['fingerprint_hash'] ?? null,
            'ja3_hash' => $visitor['ja3_hash'] ?? null,
            'ja3s_hash' => $visitor['ja3s_hash'] ?? null,
            'canvas_fingerprint' => $visitor['canvas_fingerprint'] ?? null,
            'webgl_fingerprint' => $visitor['webgl_fingerprint'] ?? null,
            'audio_fingerprint' => $visitor['audio_fingerprint'] ?? null,
            'fonts_hash' => $visitor['fonts_hash'] ?? null,
            'plugins_hash' => $visitor['plugins_hash'] ?? null,
            'webrtc_leak' => (bool)($visitor['webrtc_leak'] ?? 0),
            'local_ip_detected' => $visitor['local_ip_detected'] ?? null,
            'tls13_fingerprint' => $visitor['tls13_fingerprint'] ?? null,
            'threat_score' => $visitor['threat_score'] ?? null,
        ],
        'bot_name' => $botName,
        'proxy_type' => $proxyType,
        'location' => $location,
        'filters' => $filters,
        'reasons' => $reasons,
        'detections' => $detectionDetails,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Veri alınırken hata oluştu: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

