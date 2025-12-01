<?php

if (!function_exists('getDashboardMetrics')) {
    function getDashboardMetrics(): array {
        $pdo = DB::connect();
        $now = new DateTimeImmutable('now');
        $todayStart = $now->setTime(0, 0)->format('Y-m-d H:i:s');
        $last24h = $now->modify('-24 hours')->format('Y-m-d H:i:s');
        $trendStart = $now->modify('-6 days')->setTime(0, 0)->format('Y-m-d H:i:s');

        $totalVisitors = (int)$pdo->query("SELECT COUNT(*) FROM cloacker_visitors")->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cloacker_visitors WHERE created_at >= :start");
        $stmt->execute([':start' => $todayStart]);
        $todayVisitors = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cloacker_visitors WHERE is_bot = 1 AND created_at >= :since");
        $stmt->execute([':since' => $last24h]);
        $blockedBots = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cloacker_visitors WHERE is_proxy = 1 AND created_at >= :since");
        $stmt->execute([':since' => $last24h]);
        $proxyHits = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN redirect_target = 'normal' THEN 1 ELSE 0 END) AS normal_count,
                SUM(CASE WHEN redirect_target = 'fake' THEN 1 ELSE 0 END) AS fake_count
            FROM cloacker_visitors
            WHERE created_at >= :since
        ");
        $stmt->execute([':since' => $last24h]);
        $distribution = $stmt->fetch() ?: ['normal_count' => 0, 'fake_count' => 0];
        $normalCount = (int)($distribution['normal_count'] ?? 0);
        $fakeCount = (int)($distribution['fake_count'] ?? 0);
        $totalRecent = max(1, $normalCount + $fakeCount);
        $allowedRate = round(($normalCount / $totalRecent) * 100, 1);

        $activeSites = (int)$pdo->query("SELECT COUNT(*) FROM cloacker_sites WHERE is_active = 1")->fetchColumn();

        $trendStmt = $pdo->prepare("
            SELECT DATE(created_at) AS day_key,
                   SUM(CASE WHEN redirect_target = 'normal' THEN 1 ELSE 0 END) AS allowed_total,
                   SUM(CASE WHEN redirect_target = 'fake' THEN 1 ELSE 0 END) AS fake_total,
                   SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) AS bots_total,
                   SUM(CASE WHEN is_proxy = 1 THEN 1 ELSE 0 END) AS proxies_total
            FROM cloacker_visitors
            WHERE created_at >= :start
            GROUP BY day_key
            ORDER BY day_key ASC
        ");
        $trendStmt->execute([':start' => $trendStart]);
        $trendRows = $trendStmt->fetchAll();
        $trendMap = [];
        foreach ($trendRows as $row) {
            $trendMap[$row['day_key']] = $row;
        }
        $trafficTrend = [];
        $botTrend = [];
        $proxyTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->modify("-{$i} days");
            $key = $day->format('Y-m-d');
            $row = $trendMap[$key] ?? ['allowed_total' => 0, 'fake_total' => 0, 'bots_total' => 0, 'proxies_total' => 0];
            $trafficTrend[] = [
                'date' => $key,
                'label' => $day->format('d.m'),
                'allowed' => (int)($row['allowed_total'] ?? 0),
                'blocked' => (int)($row['fake_total'] ?? 0),
            ];
            $botTrend[] = [
                'date' => $key,
                'label' => $day->format('d.m'),
                'bots' => (int)($row['bots_total'] ?? 0),
            ];
            $proxyTrend[] = [
                'date' => $key,
                'label' => $day->format('d.m'),
                'proxies' => (int)($row['proxies_total'] ?? 0),
            ];
        }

        $stmt = $pdo->prepare("
            SELECT 
                v.id, v.ip, v.country, v.os, v.browser, v.redirect_target, 
                v.is_bot, v.is_proxy, v.created_at, v.site_id, v.referer,
                s.name AS site_name
            FROM cloacker_visitors v
            LEFT JOIN cloacker_sites s ON v.site_id = s.id
            ORDER BY v.created_at DESC 
            LIMIT 15
        ");
        $stmt->execute();
        $recentVisitors = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT a.username, l.ip, l.login_time, l.success 
            FROM cloacker_admin_logins l
            LEFT JOIN cloacker_admins a ON l.admin_id = a.id
            ORDER BY l.login_time DESC LIMIT 5
        ");
        $stmt->execute();
        $recentLogins = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT country, COUNT(*) AS total 
            FROM cloacker_visitors 
            WHERE created_at >= :since 
            GROUP BY country 
            ORDER BY total DESC 
            LIMIT 5
        ");
        $stmt->execute([':since' => $last24h]);
        $topCountries = $stmt->fetchAll();
        $topCountriesTotal = array_sum(array_map('intval', array_column($topCountries, 'total')));

        $sitePerfStmt = $pdo->prepare("
            SELECT 
                COALESCE(s.name, 'Genel Trafik') AS site_name,
                COUNT(*) AS total,
                SUM(CASE WHEN v.redirect_target = 'normal' THEN 1 ELSE 0 END) AS allowed_total,
                SUM(CASE WHEN v.redirect_target = 'fake' THEN 1 ELSE 0 END) AS fake_total
            FROM cloacker_visitors v
            LEFT JOIN cloacker_sites s ON v.site_id = s.id
            WHERE v.created_at >= :since
            GROUP BY v.site_id, s.name
            ORDER BY total DESC
            LIMIT 4
        ");
        $sitePerfStmt->execute([':since' => $last24h]);
        $sitePerformance = $sitePerfStmt->fetchAll();

        // Cihaz dağılımı hesapla (OS bazlı)
        $deviceStmt = $pdo->prepare("
            SELECT 
                os,
                COUNT(*) AS total
            FROM cloacker_visitors 
            WHERE created_at >= :since 
            AND os IS NOT NULL 
            AND os != ''
            GROUP BY os
        ");
        $deviceStmt->execute([':since' => $last24h]);
        $deviceData = $deviceStmt->fetchAll();
        
        $mobileOS = ['ios', 'android'];
        $tabletOS = ['ipad']; // iPad genelde iOS olarak gelir ama kontrol edelim
        $mobileCount = 0;
        $desktopCount = 0;
        $tabletCount = 0;
        
        foreach ($deviceData as $row) {
            $os = strtolower($row['os'] ?? '');
            $count = (int)($row['total'] ?? 0);
            
            if (in_array($os, $mobileOS) || strpos($os, 'android') !== false || strpos($os, 'ios') !== false || strpos($os, 'iphone') !== false) {
                // iPad kontrolü
                if (strpos($os, 'ipad') !== false) {
                    $tabletCount += $count;
                } else {
                    $mobileCount += $count;
                }
            } else {
                $desktopCount += $count;
            }
        }
        
        $totalDevices = $mobileCount + $desktopCount + $tabletCount;
        $deviceDistribution = [
            'mobile' => [
                'count' => $mobileCount,
                'percentage' => $totalDevices > 0 ? round(($mobileCount / $totalDevices) * 100, 1) : 0
            ],
            'desktop' => [
                'count' => $desktopCount,
                'percentage' => $totalDevices > 0 ? round(($desktopCount / $totalDevices) * 100, 1) : 0
            ],
            'tablet' => [
                'count' => $tabletCount,
                'percentage' => $totalDevices > 0 ? round(($tabletCount / $totalDevices) * 100, 1) : 0
            ]
        ];

        // Gelişmiş fingerprint metrikleri (sütunlar yoksa varsayılan değerler)
        $advancedMetricsData = [
            'canvas_detection_rate' => 0,
            'webrtc_leaks' => 0,
            'ja3_detection_rate' => 0,
            'ml_detection_rate' => 0,
            'avg_ml_confidence' => null,
        ];
        
        try {
            // Yeni sütunların varlığını kontrol et
            $pdo->query("SELECT canvas_fingerprint FROM cloacker_visitors LIMIT 1");
            
            // Sütunlar varsa metrikleri hesapla
            $advancedStmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN canvas_fingerprint IS NOT NULL THEN 1 ELSE 0 END) as with_canvas,
                    SUM(CASE WHEN webrtc_leak = 1 THEN 1 ELSE 0 END) as webrtc_leaks,
                    SUM(CASE WHEN ja3_hash IS NOT NULL THEN 1 ELSE 0 END) as with_ja3,
                    SUM(CASE WHEN ml_confidence IS NOT NULL THEN 1 ELSE 0 END) as with_ml,
                    AVG(CASE WHEN ml_confidence IS NOT NULL THEN ml_confidence ELSE NULL END) as avg_ml_confidence
                FROM cloacker_visitors
                WHERE created_at >= :since
            ");
            $advancedStmt->execute([':since' => $last24h]);
            $advancedMetrics = $advancedStmt->fetch();
            
            if ($advancedMetrics) {
                $advancedMetricsData = [
                    'canvas_detection_rate' => $advancedMetrics['total'] > 0 ? round(($advancedMetrics['with_canvas'] / $advancedMetrics['total']) * 100, 1) : 0,
                    'webrtc_leaks' => (int)($advancedMetrics['webrtc_leaks'] ?? 0),
                    'ja3_detection_rate' => $advancedMetrics['total'] > 0 ? round(($advancedMetrics['with_ja3'] / $advancedMetrics['total']) * 100, 1) : 0,
                    'ml_detection_rate' => $advancedMetrics['total'] > 0 ? round(($advancedMetrics['with_ml'] / $advancedMetrics['total']) * 100, 1) : 0,
                    'avg_ml_confidence' => $advancedMetrics['avg_ml_confidence'] ? round((float)$advancedMetrics['avg_ml_confidence'], 2) : null,
                ];
            }
        } catch (Exception $e) {
            // Sütunlar henüz yok, varsayılan değerler kullanılacak
            error_log("Gelişmiş metrikler hesaplanamadı (sütunlar henüz eklenmemiş olabilir): " . $e->getMessage());
        }

        $systemChecks = [
            [
                'label' => 'IPHub API Anahtarı',
                'ok' => (bool)config('api_keys.iphub'),
                'hint' => 'VPN/Proxy tespiti için gerekli'
            ],
            [
                'label' => 'AbuseIPDB API Anahtarı',
                'ok' => (bool)config('api_keys.abuseipdb'),
                'hint' => 'IP risk skoru sağlayıcı'
            ],
            [
                'label' => 'HTTPS',
                'ok' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'hint' => 'Panel trafiği için zorunlu öneri'
            ],
            [
                'label' => 'Oturum Timeout',
                'ok' => (int)config('security.session_timeout', 900) >= 300,
                'hint' => 'Minimum 5 dakika önerilir'
            ],
        ];

        // 24 saatlik toplam
        $total24h = $normalCount + $fakeCount;
        
        return [
            'totalVisitors' => $totalVisitors,
            'todayVisitors' => $todayVisitors,
            'blockedBots' => $blockedBots,
            'proxyHits' => $proxyHits,
            'fakeTraffic' => $fakeCount,
            'normalTraffic' => $normalCount,
            'total24h' => $total24h,
            'activeSites' => $activeSites,
            'allowedRate' => $allowedRate,
            'recentVisitors' => $recentVisitors,
            'recentLogins' => $recentLogins,
            'topCountries' => $topCountries,
            'topCountriesTotal' => $topCountriesTotal,
            'trafficTrend' => $trafficTrend,
            'botTrend' => $botTrend,
            'proxyTrend' => $proxyTrend,
            'sitePerformance' => $sitePerformance,
            'detectionBreakdown' => [
                'allowed' => $normalCount,
                'fake' => $fakeCount,
                'bots' => $blockedBots,
                'proxies' => $proxyHits,
                'total' => $total24h,
            ],
            'systemChecks' => $systemChecks,
            'deviceDistribution' => $deviceDistribution,
            'advancedMetrics' => $advancedMetricsData,
        ];
    }
}
?>

