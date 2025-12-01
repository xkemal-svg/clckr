<?php
/**
 * A/B Testing Framework - Test Yönetimi ve Sonuçlar
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

// Yeni test oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test'])) {
    requireCsrfToken();
    
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $testType = $_POST['test_type'] ?? 'detection_strategy';
        $trafficSplit = (float)($_POST['traffic_split'] ?? 50.0);
        $variantA = json_encode([
            'bot_confidence_threshold' => (float)($_POST['variant_a_threshold'] ?? 30.0),
            'scores' => [
                'canvas' => (int)($_POST['variant_a_canvas_score'] ?? 8),
                'webgl' => (int)($_POST['variant_a_webgl_score'] ?? 7),
                'audio' => (int)($_POST['variant_a_audio_score'] ?? 6),
            ]
        ]);
        $variantB = json_encode([
            'bot_confidence_threshold' => (float)($_POST['variant_b_threshold'] ?? 30.0),
            'scores' => [
                'canvas' => (int)($_POST['variant_b_canvas_score'] ?? 8),
                'webgl' => (int)($_POST['variant_b_webgl_score'] ?? 7),
                'audio' => (int)($_POST['variant_b_audio_score'] ?? 6),
            ]
        ]);
        
        if (empty($name)) {
            $error = 'Test adı gereklidir.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO cloacker_ab_tests 
                (name, description, test_type, variant_a, variant_b, traffic_split, created_by, created_at)
                VALUES 
                (:name, :description, :test_type, :variant_a, :variant_b, :traffic_split, :created_by, NOW())
            ");
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':test_type' => $testType,
                ':variant_a' => $variantA,
                ':variant_b' => $variantB,
                ':traffic_split' => $trafficSplit,
                ':created_by' => $_SESSION['admin_id'],
            ]);
            
            $success = 'A/B test başarıyla oluşturuldu.';
        }
    } catch (Exception $e) {
        $error = 'Test oluşturulurken hata oluştu: ' . $e->getMessage();
    }
}

// Test durumunu değiştir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_test'])) {
    requireCsrfToken();
    
    $testId = (int)($_POST['test_id'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("UPDATE cloacker_ab_tests SET is_active = :active, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':active' => $isActive, ':id' => $testId]);
        $success = 'Test durumu güncellendi.';
    } catch (Exception $e) {
        $error = 'Test durumu güncellenirken hata oluştu: ' . $e->getMessage();
    }
}

// Test sil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_test'])) {
    requireCsrfToken();
    
    $testId = (int)($_POST['test_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM cloacker_ab_tests WHERE id = :id");
        $stmt->execute([':id' => $testId]);
        $success = 'Test silindi.';
    } catch (Exception $e) {
        $error = 'Test silinirken hata oluştu: ' . $e->getMessage();
    }
}

// Tüm testleri al
$allTests = $pdo->query("
    SELECT t.*, a.username as created_by_name,
           (SELECT COUNT(*) FROM cloacker_ab_test_daily_stats WHERE test_id = t.id) as total_days
    FROM cloacker_ab_tests t
    LEFT JOIN cloacker_admins a ON t.created_by = a.id
    ORDER BY t.created_at DESC
")->fetchAll();

// Son 30 günlük istatistikler
$last30Days = (new DateTimeImmutable('-30 days'))->format('Y-m-d');
$dailyStats = [];
foreach ($allTests as $test) {
    $stats = $pdo->prepare("
        SELECT test_date, variant, 
               SUM(total_visitors) as total,
               SUM(normal_visitors) as normal,
               SUM(fake_visitors) as fake,
               SUM(bot_detected) as bots,
               AVG(avg_bot_confidence) as avg_confidence
        FROM cloacker_ab_test_daily_stats
        WHERE test_id = :test_id AND test_date >= :since
        GROUP BY test_date, variant
        ORDER BY test_date DESC, variant ASC
    ");
    $stats->execute([':test_id' => $test['id'], ':since' => $last30Days]);
    $dailyStats[$test['id']] = $stats->fetchAll();
}

render_admin_layout_start('A/B Testing Framework', 'ab_testing');
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

<!-- Yeni Test Oluştur - Tek Sütun Üstte -->
<div class="mb-8">
    <div class="glass-card rounded-xl p-6 border border-cyan-500/20">
        <h3 class="text-xl font-heading font-semibold text-white mb-4">Yeni A/B Test Oluştur</h3>
            
            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Test Adı <span class="text-red-400">*</span></label>
                    <input type="text" name="name" required placeholder="Örn: Bot Threshold Optimizasyonu"
                           class="w-full px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                    <p class="text-xs text-gray-400 mt-1">Test için açıklayıcı bir isim verin. Örnek: "Bot Threshold Optimizasyonu", "Canvas Score Testi"</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Açıklama</label>
                    <textarea name="description" rows="3" placeholder="Bu test neyi ölçüyor? Hangi hipotezi test ediyorsunuz?"
                              class="w-full px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white"></textarea>
                    <p class="text-xs text-gray-400 mt-1">Testin amacını ve neyi ölçtüğünüzü açıklayın. Örnek: "30% threshold ile 25% threshold'u karşılaştırıyorum"</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Test Tipi</label>
                    <select name="test_type" class="w-full px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                        <option value="detection_strategy">Detection Strategy (Tespit Stratejisi)</option>
                        <option value="threshold">Threshold (Eşik Değeri)</option>
                        <option value="redirect_method">Redirect Method (Yönlendirme Yöntemi)</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">
                        <strong>Detection Strategy:</strong> Farklı bot tespit stratejilerini test eder (örn: Canvas vs WebGL ağırlıklı)<br>
                        <strong>Threshold:</strong> Bot confidence eşik değerlerini test eder (örn: 30% vs 25%)<br>
                        <strong>Redirect Method:</strong> Farklı yönlendirme yöntemlerini test eder
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Trafik Dağılımı (%) <span class="text-cyan-400">Örnek: 50</span></label>
                    <input type="number" name="traffic_split" value="50" min="0" max="100" step="0.1"
                           class="w-full px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                    <p class="text-xs text-gray-400 mt-1">
                        <strong>50%:</strong> Trafiğin yarısı Variant A'ya, yarısı Variant B'ye gider (eşit dağılım - önerilen)<br>
                        <strong>30%:</strong> Trafiğin %30'u Variant A'ya, %70'i Variant B'ye gider (B'yi daha fazla test etmek için)<br>
                        <strong>70%:</strong> Trafiğin %70'i Variant A'ya, %30'u Variant B'ye gider (A'yı daha fazla test etmek için)
                    </p>
                </div>
                
                <div class="border-t border-cyan-500/20 pt-4">
                    <h4 class="text-sm font-semibold text-cyan-400 mb-2">Variant A Ayarları (Kontrol Grubu)</h4>
                    <p class="text-xs text-gray-400 mb-3">Mevcut ayarlarınız veya test etmek istediğiniz ilk konfigürasyon</p>
                    <div class="space-y-2">
                        <div>
                            <input type="number" name="variant_a_threshold" value="30" step="0.1" placeholder="Bot Confidence Threshold"
                                   class="w-full px-3 py-1.5 text-sm rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                            <p class="text-xs text-gray-500 mt-1">Örnek: <span class="text-cyan-400">30.0</span> - Bot confidence bu değerin üzerindeyse bot olarak işaretlenir</p>
                        </div>
                        <div>
                            <input type="number" name="variant_a_canvas_score" value="8" placeholder="Canvas Score"
                                   class="w-full px-3 py-1.5 text-sm rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                            <p class="text-xs text-gray-500 mt-1">Örnek: <span class="text-cyan-400">8</span> - Canvas fingerprint yoksa bu skor eklenir (0-100 arası)</p>
                        </div>
                        <div>
                            <input type="number" name="variant_a_webgl_score" value="7" placeholder="WebGL Score"
                                   class="w-full px-3 py-1.5 text-sm rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                            <p class="text-xs text-gray-500 mt-1">Örnek: <span class="text-cyan-400">7</span> - WebGL fingerprint yoksa bu skor eklenir (0-100 arası)</p>
                        </div>
                        <div>
                            <input type="number" name="variant_a_audio_score" value="6" placeholder="Audio Score"
                                   class="w-full px-3 py-1.5 text-sm rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                            <p class="text-xs text-gray-500 mt-1">Örnek: <span class="text-cyan-400">6</span> - Audio fingerprint yoksa bu skor eklenir (0-100 arası)</p>
                        </div>
                    </div>
                </div>
                
                <div class="border-t border-cyan-500/20 pt-4">
                    <h4 class="text-sm font-semibold text-purple-400 mb-2">Variant B Ayarları (Test Grubu)</h4>
                    <p class="text-xs text-gray-400 mb-3">Test etmek istediğiniz yeni konfigürasyon (Variant A ile karşılaştırılacak)</p>
                    <div class="space-y-2">
                        <div>
                            <input type="number" name="variant_b_threshold" value="30" step="0.1" placeholder="Bot Confidence Threshold"
                                   class="w-full px-3 py-1.5 text-sm rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                            <p class="text-xs text-gray-500 mt-1">Örnek: <span class="text-purple-400">25.0</span> - Daha düşük eşik = daha agresif bot tespiti (daha fazla bot yakalanır ama false positive riski artar)</p>
                        </div>
                        <div>
                            <input type="number" name="variant_b_canvas_score" value="8" placeholder="Canvas Score"
                                   class="w-full px-3 py-1.5 text-sm rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                            <p class="text-xs text-gray-500 mt-1">Örnek: <span class="text-purple-400">10</span> - Daha yüksek skor = Canvas yokluğuna daha fazla önem verilir</p>
                        </div>
                        <div>
                            <input type="number" name="variant_b_webgl_score" value="7" placeholder="WebGL Score"
                                   class="w-full px-3 py-1.5 text-sm rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                            <p class="text-xs text-gray-500 mt-1">Örnek: <span class="text-purple-400">9</span> - WebGL'e daha fazla ağırlık verilir</p>
                        </div>
                        <div>
                            <input type="number" name="variant_b_audio_score" value="6" placeholder="Audio Score"
                                   class="w-full px-3 py-1.5 text-sm rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                            <p class="text-xs text-gray-500 mt-1">Örnek: <span class="text-purple-400">5</span> - Audio'ya daha az ağırlık verilir</p>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="create_test"
                        class="w-full px-4 py-2 bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-lg hover:from-cyan-600 hover:to-teal-600 transition font-medium">
                    Test Oluştur
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Test Listesi ve Sonuçlar - Tek Sütun Alt Alta -->
<div class="mb-8">
    <h2 class="text-2xl font-heading font-semibold text-white mb-4">Mevcut A/B Testleri</h2>
    
    <div class="space-y-6">
        <?php if (empty($allTests)): ?>
            <div class="glass-card rounded-xl p-8 border border-cyan-500/20 text-center">
                <p class="text-gray-400">Henüz A/B testi oluşturulmamış.</p>
            </div>
        <?php else: ?>
            <?php foreach ($allTests as $test): ?>
                <?php
                $testStats = $dailyStats[$test['id']] ?? [];
                $variantAStats = array_filter($testStats, fn($s) => $s['variant'] === 'A');
                $variantBStats = array_filter($testStats, fn($s) => $s['variant'] === 'B');
                
                $variantATotal = array_sum(array_column($variantAStats, 'total'));
                $variantBTotal = array_sum(array_column($variantBStats, 'total'));
                $variantANormal = array_sum(array_column($variantAStats, 'normal'));
                $variantBNormal = array_sum(array_column($variantBStats, 'normal'));
                $variantABots = array_sum(array_column($variantAStats, 'bots'));
                $variantBBots = array_sum(array_column($variantBStats, 'bots'));
                
                $variantANormalRate = $variantATotal > 0 ? ($variantANormal / $variantATotal) * 100 : 0;
                $variantBNormalRate = $variantBTotal > 0 ? ($variantBNormal / $variantBTotal) * 100 : 0;
                $variantABotRate = $variantATotal > 0 ? ($variantABots / $variantATotal) * 100 : 0;
                $variantBBotRate = $variantBTotal > 0 ? ($variantBBots / $variantBTotal) * 100 : 0;
                ?>
                
                <div class="glass-card rounded-xl p-6 border border-cyan-500/20">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-heading font-semibold text-white"><?= htmlspecialchars($test['name']) ?></h3>
                            <p class="text-sm text-gray-400 mt-1"><?= htmlspecialchars($test['description'] ?: 'Açıklama yok') ?></p>
                            <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                <span>Tip: <?= htmlspecialchars($test['test_type']) ?></span>
                                <span>Başlangıç: <?= date('d.m.Y H:i', strtotime($test['start_date'])) ?></span>
                                <?php if ($test['end_date']): ?>
                                    <span>Bitiş: <?= date('d.m.Y H:i', strtotime($test['end_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?= $test['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400' ?>">
                                <?= $test['is_active'] ? 'Aktif' : 'Pasif' ?>
                            </span>
                            <form method="POST" action="" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="test_id" value="<?= $test['id'] ?>">
                                <input type="hidden" name="is_active" value="<?= $test['is_active'] ? 0 : 1 ?>">
                                <button type="submit" name="toggle_test" class="px-3 py-1 text-xs rounded-lg border border-cyan-500/20 hover:bg-cyan-500/10 text-cyan-400 transition">
                                    <?= $test['is_active'] ? 'Durdur' : 'Başlat' ?>
                                </button>
                            </form>
                            <form method="POST" action="" class="inline" onsubmit="return confirm('Bu testi silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="test_id" value="<?= $test['id'] ?>">
                                <button type="submit" name="delete_test" class="px-3 py-1 text-xs rounded-lg border border-red-500/20 hover:bg-red-500/10 text-red-400 transition">
                                    Sil
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Günlük İstatistikler -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="border border-cyan-500/20 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-cyan-400 mb-3">Variant A</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Toplam:</span>
                                    <span class="text-white font-semibold"><?= number_format($variantATotal) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Normal:</span>
                                    <span class="text-green-400 font-semibold"><?= number_format($variantANormal) ?> (<?= number_format($variantANormalRate, 1) ?>%)</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Bot:</span>
                                    <span class="text-red-400 font-semibold"><?= number_format($variantABots) ?> (<?= number_format($variantABotRate, 1) ?>%)</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border border-purple-500/20 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-purple-400 mb-3">Variant B</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Toplam:</span>
                                    <span class="text-white font-semibold"><?= number_format($variantBTotal) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Normal:</span>
                                    <span class="text-green-400 font-semibold"><?= number_format($variantBNormal) ?> (<?= number_format($variantBNormalRate, 1) ?>%)</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Bot:</span>
                                    <span class="text-red-400 font-semibold"><?= number_format($variantBBots) ?> (<?= number_format($variantBBotRate, 1) ?>%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Son 7 Günlük Grafik -->
                    <?php if (!empty($testStats)): ?>
                        <div class="mt-4">
                            <h4 class="text-sm font-semibold text-white mb-2">Son 7 Günlük Trend</h4>
                            <canvas id="chart-<?= $test['id'] ?>" height="100"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- A/B Test Açıklaması - Tek Sütun En Altta -->
<div class="mb-8">
    <div class="glass-card rounded-xl p-6 border border-blue-500/30 bg-blue-900/20">
        <h3 class="text-xl font-heading font-semibold text-blue-300 mb-4">💡 A/B Test Mantığı ve Kullanım Kılavuzu</h3>
        
        <div class="space-y-4 text-sm text-gray-300">
            <div>
                <h4 class="text-lg font-semibold text-blue-400 mb-2">Nasıl Çalışır?</h4>
                <ol class="list-decimal list-inside space-y-2 ml-4">
                    <li>Test oluşturduğunuzda, gelen ziyaretçiler rastgele Variant A veya B'ye yönlendirilir (trafik dağılımına göre)</li>
                    <li>Her variant için bot tespit sonuçları ayrı ayrı kaydedilir</li>
                    <li>Sonuçları karşılaştırarak hangi konfigürasyonun daha iyi çalıştığını görebilirsiniz</li>
                    <li>Kazanan variant'ı belirleyip sistem ayarlarınızı ona göre güncelleyebilirsiniz</li>
                </ol>
            </div>
            
            <div>
                <h4 class="text-lg font-semibold text-blue-400 mb-2">Örnek Senaryo</h4>
                <div class="bg-gray-900/50 rounded-lg p-4 space-y-2">
                    <div>
                        <strong class="text-cyan-400">Variant A (Kontrol):</strong> Threshold 30%, Canvas Score 8 → Mevcut ayarlarınız
                    </div>
                    <div>
                        <strong class="text-purple-400">Variant B (Test):</strong> Threshold 25%, Canvas Score 10 → Daha agresif bot tespiti
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-700">
                        <strong class="text-yellow-400">Sonuç Analizi:</strong> Variant B daha fazla bot yakalıyor ama normal kullanıcıları da engelliyor mu? 
                        Test sonuçlarına bakarak karar verin! Eğer Variant B'nin normal ziyaretçi oranı düşükse, false positive riski yüksek demektir.
                    </div>
                </div>
            </div>
            
            <div>
                <h4 class="text-lg font-semibold text-blue-400 mb-2">Test Tipleri</h4>
                <div class="space-y-2">
                    <div class="bg-gray-900/50 rounded-lg p-3">
                        <strong class="text-cyan-400">Detection Strategy:</strong> Farklı bot tespit stratejilerini test eder. 
                        Örneğin Canvas ağırlıklı vs WebGL ağırlıklı tespit. Hangi fingerprint yöntemi daha etkili?
                    </div>
                    <div class="bg-gray-900/50 rounded-lg p-3">
                        <strong class="text-cyan-400">Threshold:</strong> Bot confidence eşik değerlerini test eder. 
                        Örneğin 30% vs 25% threshold. Daha düşük eşik daha fazla bot yakalar ama false positive riski artar.
                    </div>
                    <div class="bg-gray-900/50 rounded-lg p-3">
                        <strong class="text-cyan-400">Redirect Method:</strong> Farklı yönlendirme yöntemlerini test eder. 
                        Örneğin anında redirect vs delayed redirect. Hangi yöntem botları daha iyi filtreler?
                    </div>
                </div>
            </div>
            
            <div>
                <h4 class="text-lg font-semibold text-blue-400 mb-2">Trafik Dağılımı Stratejisi</h4>
                <div class="space-y-2">
                    <div><strong class="text-green-400">50% / 50%:</strong> Eşit dağılım - En objektif sonuçlar için önerilen. Her iki variant eşit trafik alır.</div>
                    <div><strong class="text-yellow-400">30% / 70%:</strong> Variant B'yi daha fazla test etmek için. Yeni konfigürasyonu daha fazla trafikle test edersiniz.</div>
                    <div><strong class="text-yellow-400">70% / 30%:</strong> Variant A'yı daha fazla test etmek için. Mevcut ayarlarınızı daha fazla trafikle doğrulamak istiyorsanız.</div>
                </div>
            </div>
            
            <div>
                <h4 class="text-lg font-semibold text-blue-400 mb-2">Sonuçları Yorumlama</h4>
                <div class="bg-gray-900/50 rounded-lg p-4 space-y-2">
                    <div>
                        <strong class="text-green-400">Normal Ziyaretçi Oranı:</strong> Yüksek oran = daha az false positive (iyi). 
                        Düşük oran = çok fazla normal kullanıcı engelleniyor (kötü).
                    </div>
                    <div>
                        <strong class="text-red-400">Bot Tespit Oranı:</strong> Yüksek oran = daha fazla bot yakalanıyor (iyi). 
                        Ancak normal ziyaretçi oranı da düşükse, false positive riski var.
                    </div>
                    <div>
                        <strong class="text-cyan-400">Kazanan Variant:</strong> Hem yüksek normal ziyaretçi oranı hem de yüksek bot tespit oranına sahip variant kazanır. 
                        İdeal durum: Yüksek normal oran + Yüksek bot tespit oranı.
                    </div>
                </div>
            </div>
            
            <div class="bg-yellow-900/20 border border-yellow-500/30 rounded-lg p-4">
                <h4 class="text-lg font-semibold text-yellow-400 mb-2">⚠️ Önemli Notlar</h4>
                <ul class="list-disc list-inside space-y-1 ml-4">
                    <li>Test sonuçlarını en az 7-14 gün boyunca toplayın (istatistiksel anlamlılık için)</li>
                    <li>Test sırasında diğer ayarları değiştirmeyin (test sonuçlarını bozabilir)</li>
                    <li>Kazanan variant'ı belirledikten sonra testi durdurun ve ayarları güncelleyin</li>
                    <li>Birden fazla testi aynı anda çalıştırmak istatistikleri bozabilir</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
// Grafikleri oluştur
<?php foreach ($allTests as $test): ?>
    <?php
    $testStats = $dailyStats[$test['id']] ?? [];
    $last7Days = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $last7Days[$date] = ['A' => ['total' => 0, 'normal' => 0, 'bots' => 0], 'B' => ['total' => 0, 'normal' => 0, 'bots' => 0]];
    }
    
    foreach ($testStats as $stat) {
        $date = $stat['test_date'];
        if (isset($last7Days[$date])) {
            $variant = $stat['variant'];
            $last7Days[$date][$variant] = [
                'total' => (int)$stat['total'],
                'normal' => (int)$stat['normal'],
                'bots' => (int)$stat['bots']
            ];
        }
    }
    ?>
    
    const ctx<?= $test['id'] ?> = document.getElementById('chart-<?= $test['id'] ?>');
    if (ctx<?= $test['id'] ?>) {
        new Chart(ctx<?= $test['id'] ?>, {
            type: 'line',
            data: {
                labels: [<?= implode(',', array_map(fn($d) => "'" . date('d.m', strtotime($d)) . "'", array_keys($last7Days))) ?>],
                datasets: [
                    {
                        label: 'Variant A - Normal',
                        data: [<?= implode(',', array_column(array_column($last7Days, 'A'), 'normal')) ?>],
                        borderColor: 'rgb(34, 211, 238)',
                        backgroundColor: 'rgba(34, 211, 238, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Variant B - Normal',
                        data: [<?= implode(',', array_column(array_column($last7Days, 'B'), 'normal')) ?>],
                        borderColor: 'rgb(168, 85, 247)',
                        backgroundColor: 'rgba(168, 85, 247, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Variant A - Bot',
                        data: [<?= implode(',', array_column(array_column($last7Days, 'A'), 'bots')) ?>],
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Variant B - Bot',
                        data: [<?= implode(',', array_column(array_column($last7Days, 'B'), 'bots')) ?>],
                        borderColor: 'rgb(251, 146, 60)',
                        backgroundColor: 'rgba(251, 146, 60, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#ffffff' }
                    }
                },
                scales: {
                    x: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255, 255, 255, 0.1)' } },
                    y: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255, 255, 255, 0.1)' } }
                }
            }
        });
    }
<?php endforeach; ?>
</script>

<?php render_admin_layout_end(); ?>

