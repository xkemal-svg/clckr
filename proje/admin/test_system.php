<?php
/**
 * Sistem Test Scripti
 * Cloacker sisteminin tüm bileşenlerini test eder
 */

require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/../cloacker.php';

enforceAdminSession();

header('Content-Type: text/html; charset=utf-8');

$tests = [];
$errors = [];

// Test 1: Database bağlantısı
$tests[] = [
    'name' => 'Database Bağlantısı',
    'status' => 'pending'
];
try {
    $pdo = DB::connect();
    $pdo->query("SELECT 1");
    $tests[count($tests) - 1]['status'] = 'ok';
    $tests[count($tests) - 1]['message'] = 'Database bağlantısı başarılı';
} catch (Exception $e) {
    $tests[count($tests) - 1]['status'] = 'error';
    $tests[count($tests) - 1]['message'] = 'Database bağlantı hatası: ' . $e->getMessage();
    $errors[] = $tests[count($tests) - 1]['message'];
}

// Test 2: Gerekli tablolar
$requiredTables = [
    'cloacker_visitors',
    'cloacker_settings',
    'cloacker_sites',
    'cloacker_admins',
    'cloacker_bot_detections'
];

foreach ($requiredTables as $table) {
    $tests[] = [
        'name' => "Tablo: $table",
        'status' => 'pending'
    ];
    try {
        $pdo->query("SELECT 1 FROM $table LIMIT 1");
        $tests[count($tests) - 1]['status'] = 'ok';
        $tests[count($tests) - 1]['message'] = "Tablo mevcut";
    } catch (Exception $e) {
        $tests[count($tests) - 1]['status'] = 'error';
        $tests[count($tests) - 1]['message'] = "Tablo bulunamadı: " . $e->getMessage();
        $errors[] = $tests[count($tests) - 1]['message'];
    }
}

// Test 3: Gerekli sütunlar (cloacker_visitors)
$requiredColumns = [
    'canvas_fingerprint',
    'webgl_fingerprint',
    'audio_fingerprint',
    'fonts_hash',
    'plugins_hash',
    'ml_confidence',
    'fingerprint_hash',
    'ja3_hash'
];

foreach ($requiredColumns as $column) {
    $tests[] = [
        'name' => "Sütun: cloacker_visitors.$column",
        'status' => 'pending'
    ];
    try {
        $pdo->query("SELECT $column FROM cloacker_visitors LIMIT 1");
        $tests[count($tests) - 1]['status'] = 'ok';
        $tests[count($tests) - 1]['message'] = "Sütun mevcut";
    } catch (Exception $e) {
        $tests[count($tests) - 1]['status'] = 'warning';
        $tests[count($tests) - 1]['message'] = "Sütun bulunamadı (migration gerekli olabilir)";
    }
}

// Test 4: Settings kontrolü
$tests[] = [
    'name' => 'Settings Tablosu',
    'status' => 'pending'
];
try {
    $settings = $pdo->query("SELECT * FROM cloacker_settings WHERE id = 1 LIMIT 1")->fetch();
    if ($settings) {
        $tests[count($tests) - 1]['status'] = 'ok';
        $tests[count($tests) - 1]['message'] = 'Settings mevcut';
    } else {
        $tests[count($tests) - 1]['status'] = 'error';
        $tests[count($tests) - 1]['message'] = 'Settings kaydı bulunamadı';
        $errors[] = $tests[count($tests) - 1]['message'];
    }
} catch (Exception $e) {
    $tests[count($tests) - 1]['status'] = 'error';
    $tests[count($tests) - 1]['message'] = 'Settings okuma hatası: ' . $e->getMessage();
    $errors[] = $tests[count($tests) - 1]['message'];
}

// Test 5: Fonksiyon testleri
$tests[] = [
    'name' => 'Fonksiyon: getCountry',
    'status' => 'pending'
];
try {
    $result = getCountry('8.8.8.8');
    $tests[count($tests) - 1]['status'] = 'ok';
    $tests[count($tests) - 1]['message'] = "Sonuç: $result";
} catch (Exception $e) {
    $tests[count($tests) - 1]['status'] = 'error';
    $tests[count($tests) - 1]['message'] = 'Hata: ' . $e->getMessage();
    $errors[] = $tests[count($tests) - 1]['message'];
}

$tests[] = [
    'name' => 'Fonksiyon: getOS',
    'status' => 'pending'
];
try {
    $result = getOS('Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    $tests[count($tests) - 1]['status'] = 'ok';
    $tests[count($tests) - 1]['message'] = "Sonuç: $result";
} catch (Exception $e) {
    $tests[count($tests) - 1]['status'] = 'error';
    $tests[count($tests) - 1]['message'] = 'Hata: ' . $e->getMessage();
    $errors[] = $tests[count($tests) - 1]['message'];
}

$tests[] = [
    'name' => 'Fonksiyon: getBrowser',
    'status' => 'pending'
];
try {
    $result = getBrowser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    $tests[count($tests) - 1]['status'] = 'ok';
    $tests[count($tests) - 1]['message'] = "Sonuç: $result";
} catch (Exception $e) {
    $tests[count($tests) - 1]['status'] = 'error';
    $tests[count($tests) - 1]['message'] = 'Hata: ' . $e->getMessage();
    $errors[] = $tests[count($tests) - 1]['message'];
}

// Test 6: Cloaker decision testi
$tests[] = [
    'name' => 'Cloaker Decision (Test)',
    'status' => 'pending'
];
try {
    $decision = cloaker_decision(false, false, null, null, [
        'override_ip' => '8.8.8.8',
        'override_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'client_fingerprints' => [
            'canvas' => 'test123',
            'webgl' => 'test456',
            'audio' => 'test789',
            'challenge' => 'test|123'
        ]
    ]);
    $tests[count($tests) - 1]['status'] = 'ok';
    $tests[count($tests) - 1]['message'] = 'Decision başarılı - Bot: ' . ($decision['bot'] ? 'Evet' : 'Hayır');
} catch (Exception $e) {
    $tests[count($tests) - 1]['status'] = 'error';
    $tests[count($tests) - 1]['message'] = 'Hata: ' . $e->getMessage();
    $errors[] = $tests[count($tests) - 1]['message'];
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Testi</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #06b6d4;
            margin-bottom: 2rem;
        }
        .test-item {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .test-item.ok {
            border-color: #10b981;
            background: #064e3b;
        }
        .test-item.error {
            border-color: #ef4444;
            background: #7f1d1d;
        }
        .test-item.warning {
            border-color: #f59e0b;
            background: #78350f;
        }
        .test-name {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .test-message {
            color: #cbd5e1;
            font-size: 0.9rem;
        }
        .summary {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .summary.ok {
            border-color: #10b981;
        }
        .summary.error {
            border-color: #ef4444;
        }
    </style>
</head>
<body>
    <h1>🔍 Cloacker Sistem Testi</h1>
    
    <?php
    $okCount = count(array_filter($tests, fn($t) => $t['status'] === 'ok'));
    $errorCount = count(array_filter($tests, fn($t) => $t['status'] === 'error'));
    $warningCount = count(array_filter($tests, fn($t) => $t['status'] === 'warning'));
    $totalCount = count($tests);
    ?>
    
    <div class="summary <?= $errorCount > 0 ? 'error' : 'ok' ?>">
        <h2>Özet</h2>
        <p>Toplam Test: <?= $totalCount ?></p>
        <p style="color: #10b981;">✓ Başarılı: <?= $okCount ?></p>
        <p style="color: #f59e0b;">⚠ Uyarı: <?= $warningCount ?></p>
        <p style="color: #ef4444;">✗ Hata: <?= $errorCount ?></p>
    </div>
    
    <h2>Test Sonuçları</h2>
    <?php foreach ($tests as $test): ?>
        <div class="test-item <?= $test['status'] ?>">
            <div class="test-name">
                <?php if ($test['status'] === 'ok'): ?>
                    ✓
                <?php elseif ($test['status'] === 'error'): ?>
                    ✗
                <?php else: ?>
                    ⚠
                <?php endif; ?>
                <?= htmlspecialchars($test['name']) ?>
            </div>
            <div class="test-message"><?= htmlspecialchars($test['message']) ?></div>
        </div>
    <?php endforeach; ?>
    
    <?php if (count($errors) > 0): ?>
        <div class="summary error" style="margin-top: 2rem;">
            <h2>Kritik Hatalar</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</body>
</html>


