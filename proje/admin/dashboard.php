<?php
require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/dashboard_metrics.php';
require_once __DIR__ . '/includes/layout.php';

enforceAdminSession();

if (isset($_GET['logout'])) {
    logoutAdmin();
}

$metrics = getDashboardMetrics();
$defaultDeviceDistribution = [
    'mobile' => ['percentage' => 0, 'count' => 0],
    'desktop' => ['percentage' => 0, 'count' => 0],
    'tablet' => ['percentage' => 0, 'count' => 0],
];
$deviceDistribution = array_replace_recursive(
    $defaultDeviceDistribution,
    $metrics['deviceDistribution'] ?? []
);
$topCountriesTotal = max(1, $metrics['topCountriesTotal']);

render_admin_layout_start('Yönetim Paneli', 'dashboard');
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>

<div class="flex flex-wrap items-center justify-between gap-4 mb-8">
    <div>
        <h2 class="text-3xl font-heading font-bold text-white mb-2">Genel Bakış</h2>
        <p class="text-sm text-gray-400">Veriler 15 saniyede bir otomatik yenilenir.</p>
    </div>
    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg glass-card border border-cyan-500/30 text-sm neon-glow-cyan">
        <div class="w-2 h-2 rounded-full bg-cyan-400 pulse-glow"></div>
        <span class="text-cyan-300 font-medium">Sistem Aktif</span>
    </div>
</div>

<?php
// 24 saatlik istatistikler
$last24h = (new DateTimeImmutable('-24 hours'))->format('Y-m-d H:i:s');
$pdo = DB::connect();

$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN redirect_target = 'normal' THEN 1 ELSE 0 END) AS normal_count,
        SUM(CASE WHEN redirect_target = 'fake' THEN 1 ELSE 0 END) AS fake_count,
        SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) AS bot_count,
        SUM(CASE WHEN is_proxy = 1 THEN 1 ELSE 0 END) AS proxy_count
    FROM cloacker_visitors
    WHERE created_at >= :since
");
$stmt->execute([':since' => $last24h]);
$stats24h = $stmt->fetch() ?: ['total' => 0, 'normal_count' => 0, 'fake_count' => 0, 'bot_count' => 0, 'proxy_count' => 0];
?>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-10">
    <!-- Toplam Tıklama -->
    <article class="glass-card rounded-xl p-6 border border-cyan-500/20 hover:border-cyan-500/40 hover:shadow-lg hover:shadow-cyan-500/20 transition-all duration-300 neon-glow-cyan group cursor-pointer transform hover:scale-105">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs uppercase tracking-wider text-gray-400 font-medium">Toplam Tıklama</p>
            <svg class="w-6 h-6 text-cyan-400 opacity-50 group-hover:opacity-100 group-hover:rotate-12 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
            </svg>
        </div>
        <p class="text-4xl font-heading font-bold text-white mb-1" id="metric-total"><?=number_format($stats24h['total'])?></p>
        <span class="text-xs text-gray-400">Son 24 saat</span>
    </article>
    
    <!-- Normal Ziyaretçi -->
    <article class="glass-card rounded-xl p-6 border border-green-500/20 hover:border-green-500/40 hover:shadow-lg hover:shadow-green-500/20 transition-all duration-300 group cursor-pointer transform hover:scale-105">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs uppercase tracking-wider text-gray-400 font-medium">Normal Ziyaretçi</p>
            <svg class="w-6 h-6 text-green-400 opacity-50 group-hover:opacity-100 group-hover:rotate-12 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <p class="text-4xl font-heading font-bold text-green-400 mb-1" id="metric-normal"><?=number_format($stats24h['normal_count'])?></p>
        <span class="text-xs text-gray-400">Normal Sayfa</span>
    </article>
    
    <!-- Bot -->
    <article class="glass-card rounded-xl p-6 border border-red-500/20 hover:border-red-500/40 hover:shadow-lg hover:shadow-red-500/20 transition-all duration-300 neon-glow-red group cursor-pointer transform hover:scale-105">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs uppercase tracking-wider text-gray-400 font-medium">Bot</p>
            <svg class="w-6 h-6 text-red-400 opacity-50 group-hover:opacity-100 group-hover:rotate-12 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <p class="text-4xl font-heading font-bold text-red-400 mb-1" id="metric-bot"><?=number_format($stats24h['bot_count'])?></p>
        <span class="text-xs text-gray-400">Tespit edilen botlar</span>
    </article>
    
    <!-- Proxy/VPN -->
    <article class="glass-card rounded-xl p-6 border border-yellow-500/20 hover:border-yellow-500/40 hover:shadow-lg hover:shadow-yellow-500/20 transition-all duration-300 group cursor-pointer transform hover:scale-105">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs uppercase tracking-wider text-gray-400 font-medium">Proxy/VPN</p>
            <svg class="w-6 h-6 text-yellow-400 opacity-50 group-hover:opacity-100 group-hover:rotate-12 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>
        <p class="text-4xl font-heading font-bold text-yellow-400 mb-1" id="metric-proxy"><?=number_format($stats24h['proxy_count'])?></p>
        <span class="text-xs text-gray-400">Tespit edilen proxy/VPN</span>
    </article>
</div>

<!-- Trafik Analizi - Tek Sütun -->
<div class="mb-10">
    <section class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-heading font-semibold text-white">Trafik Analizi</h2>
                <p class="text-sm text-gray-400">İzinli vs. engellenen ziyaretler</p>
            </div>
            <div class="flex gap-2">
                <button class="px-3 py-1 rounded-lg bg-cyan-500/20 border border-cyan-500/30 text-cyan-300 text-xs font-medium">Canlı</button>
                <button class="px-3 py-1 rounded-lg glass-card border border-gray-600/30 text-gray-400 text-xs font-medium">24 Saat</button>
            </div>
        </div>
        <div class="h-80 overflow-hidden">
            <canvas id="trafficChart" style="max-height: 320px;"></canvas>
        </div>
    </section>
</div>

<!-- Cihaz Dağılımı ve Ülke Dağılımı - İki Sütun -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
    <section class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <h2 class="text-xl font-heading font-semibold text-white mb-4">Cihaz Dağılımı (24s)</h2>
        <div class="space-y-4" id="device-distribution">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <div class="flex-1">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-white font-medium">Mobile</span>
                        <span class="text-cyan-400" id="device-mobile"><?=$deviceDistribution['mobile']['percentage']?>% (<?=number_format($deviceDistribution['mobile']['count'])?>)</span>
                    </div>
                    <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-cyan-500 to-cyan-400 h-2" id="device-mobile-bar" style="width: <?=$deviceDistribution['mobile']['percentage']?>%;"></div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <div class="flex-1">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-white font-medium">Desktop</span>
                        <span class="text-blue-400" id="device-desktop"><?=$deviceDistribution['desktop']['percentage']?>% (<?=number_format($deviceDistribution['desktop']['count'])?>)</span>
                    </div>
                    <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-400 h-2" id="device-desktop-bar" style="width: <?=$deviceDistribution['desktop']['percentage']?>%;"></div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                </svg>
                <div class="flex-1">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-white font-medium">Tablet</span>
                        <span class="text-gray-400" id="device-tablet"><?=$deviceDistribution['tablet']['percentage']?>% (<?=number_format($deviceDistribution['tablet']['count'])?>)</span>
                    </div>
                    <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-gray-500 h-2" id="device-tablet-bar" style="width: <?=$deviceDistribution['tablet']['percentage']?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-heading font-semibold text-white">Ülke Dağılımı (24s)</h2>
        </div>
        <div class="space-y-4" id="country-list">
            <?php if ($metrics['topCountries']): ?>
                <?php foreach ($metrics['topCountries'] as $country): 
                    $percentage = $topCountriesTotal > 0 ? round(($country['total'] / $topCountriesTotal) * 100, 1) : 0;
                ?>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-semibold text-white"><?=htmlspecialchars($country['country'] ?: 'UN')?></span>
                            <span class="text-cyan-400"><?=$percentage?>%</span>
                        </div>
                        <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-cyan-500 to-cyan-400 h-2 transition-all duration-500" style="width: <?=$percentage?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-sm text-gray-400">Henüz veri yok.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- Sistem Durumu - Tek Sütun -->
<div class="mb-10">
    <section class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <h2 class="text-xl font-heading font-semibold text-white mb-4">Sistem Durumu</h2>
        <div class="flex items-center gap-2 text-green-400">
            <div class="w-2 h-2 rounded-full bg-green-400 pulse-glow"></div>
            <span class="text-sm font-medium">• Tüm servisler aktif</span>
        </div>
    </section>
</div>

<!-- A/B Test İstatistikleri - Tek Sütun -->
<?php
// Aktif A/B testlerini al
try {
    $abTests = $pdo->query("
        SELECT t.*, 
               (SELECT COUNT(*) FROM cloacker_ab_test_daily_stats WHERE test_id = t.id) as total_days
        FROM cloacker_ab_tests t
        WHERE t.is_active = 1
        ORDER BY t.created_at DESC
        LIMIT 5
    ")->fetchAll();
} catch (Exception $e) {
    // Tablo yoksa boş array döndür
    $abTests = [];
}

$last30Days = (new DateTimeImmutable('-30 days'))->format('Y-m-d');
?>
<div class="mb-10">
    <section class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-heading font-semibold text-white">🧪 A/B Test İstatistikleri</h2>
            <a href="ab_testing.php" class="text-sm text-cyan-400 hover:text-cyan-300 transition">Tümünü Gör →</a>
        </div>
        
        <?php if (!empty($abTests)): ?>
        <div class="space-y-4">
            <?php foreach ($abTests as $test): 
                try {
                    $stats = $pdo->prepare("
                        SELECT variant, 
                               SUM(total_visitors) as total,
                               SUM(normal_visitors) as normal,
                               SUM(fake_visitors) as fake,
                               SUM(bot_detected) as bots
                        FROM cloacker_ab_test_daily_stats
                        WHERE test_id = :test_id AND test_date >= :since
                        GROUP BY variant
                    ");
                    $stats->execute([':test_id' => $test['id'], ':since' => $last30Days]);
                    $testStats = $stats->fetchAll();
                } catch (Exception $e) {
                    $testStats = [];
                }
                
                $variantA = null;
                $variantB = null;
                foreach ($testStats as $stat) {
                    if ($stat['variant'] === 'A') {
                        $variantA = $stat;
                    } elseif ($stat['variant'] === 'B') {
                        $variantB = $stat;
                    }
                }
                
                $variantATotal = (int)($variantA['total'] ?? 0);
                $variantBTotal = (int)($variantB['total'] ?? 0);
                $variantANormal = (int)($variantA['normal'] ?? 0);
                $variantBNormal = (int)($variantB['normal'] ?? 0);
                $variantABots = (int)($variantA['bots'] ?? 0);
                $variantBBots = (int)($variantB['bots'] ?? 0);
                
                $variantANormalRate = $variantATotal > 0 ? round(($variantANormal / $variantATotal) * 100, 1) : 0;
                $variantBNormalRate = $variantBTotal > 0 ? round(($variantBNormal / $variantBTotal) * 100, 1) : 0;
            ?>
            <div class="border border-cyan-500/20 rounded-lg p-4 bg-cyan-500/5 hover:bg-cyan-500/10 transition">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-base font-semibold text-white"><?= htmlspecialchars($test['name']) ?></h3>
                        <?php if (!empty($test['description'])): ?>
                            <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($test['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400 border border-green-500/30">
                        Aktif
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-3">
                    <div class="border-l-2 border-cyan-400 pl-3">
                        <div class="text-cyan-400 font-semibold mb-2 text-sm">Variant A (Kontrol)</div>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Toplam Ziyaret:</span>
                                <span class="text-white font-semibold"><?= number_format($variantATotal) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Normal Sayfa:</span>
                                <span class="text-green-400 font-semibold"><?= number_format($variantANormal) ?> <span class="text-gray-500">(<?= $variantANormalRate ?>%)</span></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Bot Tespit:</span>
                                <span class="text-red-400 font-semibold"><?= number_format($variantABots) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="border-l-2 border-purple-400 pl-3">
                        <div class="text-purple-400 font-semibold mb-2 text-sm">Variant B (Test)</div>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Toplam Ziyaret:</span>
                                <span class="text-white font-semibold"><?= number_format($variantBTotal) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Normal Sayfa:</span>
                                <span class="text-green-400 font-semibold"><?= number_format($variantBNormal) ?> <span class="text-gray-500">(<?= $variantBNormalRate ?>%)</span></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Bot Tespit:</span>
                                <span class="text-red-400 font-semibold"><?= number_format($variantBBots) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($variantATotal > 0 && $variantBTotal > 0): 
                    $winner = $variantBNormalRate > $variantANormalRate ? 'B' : ($variantBNormalRate < $variantANormalRate ? 'A' : 'Berabere');
                    $difference = abs($variantBNormalRate - $variantANormalRate);
                    $winnerColor = $winner === 'A' ? 'cyan' : ($winner === 'B' ? 'purple' : 'gray');
                ?>
                <div class="mt-3 pt-3 border-t border-cyan-500/20 bg-gray-900/30 rounded p-2">
                    <div class="flex items-center justify-between text-xs">
                        <div>
                            <span class="text-gray-400">Kazanan Variant:</span>
                            <span class="font-bold text-<?= $winnerColor ?>-400 ml-2">Variant <?= $winner ?></span>
                        </div>
                        <div>
                            <span class="text-gray-400">Fark:</span>
                            <span class="font-semibold text-<?= $winnerColor ?>-400 ml-2"><?= $difference ?>%</span>
                        </div>
                    </div>
                </div>
                <?php elseif ($variantATotal == 0 && $variantBTotal == 0): ?>
                <div class="mt-3 pt-3 border-t border-cyan-500/20">
                    <div class="text-xs text-gray-500 text-center">Henüz veri toplanmadı. Test aktif olduğunda istatistikler burada görünecek.</div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-8">
            <div class="text-6xl mb-4">🧪</div>
            <p class="text-gray-400 mb-2">Aktif A/B testi bulunmuyor</p>
            <a href="ab_testing.php" class="inline-block mt-4 px-4 py-2 bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-lg hover:from-cyan-600 hover:to-teal-600 transition font-medium text-sm">
                Yeni Test Oluştur
            </a>
        </div>
        <?php endif; ?>
    </section>
</div>

<!-- Son Aktiviteler - Tek Sütun -->
<div class="mb-10">
    <section class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-heading font-semibold text-white">Son Aktiviteler</h2>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg glass-card border border-cyan-500/30 text-sm neon-glow-cyan">
                <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span class="text-cyan-300 font-medium">Canlı izleme modu açık</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gradient-to-r from-cyan-500 to-teal-500 text-white">
                    <tr>
                        <th class="px-2 py-2 text-left">IP ADRESİ</th>
                        <th class="px-2 py-2 text-left">LOKASYON</th>
                        <th class="px-2 py-2 text-left">CİHAZ / OS</th>
                        <th class="px-2 py-2 text-center">YÖNLENDİRME</th>
                        <th class="px-2 py-2 text-center">DURUM</th>
                        <th class="px-2 py-2 text-left">ZAMAN</th>
                    </tr>
                </thead>
                <tbody id="recent-visitors" class="divide-y divide-gray-800">
                    <?php foreach ($metrics['recentVisitors'] as $visitor): ?>
                        <tr class="border-b border-gray-700 hover:bg-cyan-500/5 transition">
                            <td class="px-2 py-2 font-mono text-xs text-gray-300"><?=htmlspecialchars($visitor['ip'])?></td>
                            <td class="px-2 py-2 text-center">
                                <span class="px-2 py-1 text-xs rounded-full bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 font-medium">
                                    <?=htmlspecialchars($visitor['country'] ?: 'UN')?>
                                </span>
                            </td>
                            <td class="px-2 py-2 text-center">
                                <?php if ($visitor['is_bot']): ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-500/20 text-red-400 border border-red-500/30 font-medium">
                                        Bot / <?=htmlspecialchars($visitor['browser'] ?? 'Unknown')?>
                                    </span>
                                <?php else: ?>
                                    <?php
                                    $osDisplay = '';
                                    if ($visitor['os']) {
                                        $osMap = ['ios' => 'iOS', 'android' => 'Android', 'windows' => 'Windows', 'macos' => 'macOS', 'linux' => 'Linux'];
                                        $osKey = strtolower($visitor['os']);
                                        $osDisplay = $osMap[$osKey] ?? ucfirst($visitor['os']);
                                    }
                                    $deviceType = ($osDisplay === 'iOS' || $osDisplay === 'Android') ? 'Mobile' : 'Desktop';
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-purple-500/20 text-purple-400 border border-purple-500/30 font-medium">
                                        <?=$deviceType?> / <?=$osDisplay ?: 'Desktop'?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-2 py-2 text-center">
                                <?php if ($visitor['redirect_target'] === 'normal'): ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-500/20 text-green-400 border border-green-500/30 font-medium">Normal Sayfa</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-500/20 text-red-400 border border-red-500/30 font-medium">Fake Sayfa</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-2 py-2 text-center">
                                <?php if ($visitor['is_bot']): ?>
                                    <span class="px-1.5 py-0.5 text-xs rounded-full bg-red-500/20 text-red-400 border border-red-500/30">Bot</span>
                                <?php elseif ($visitor['is_proxy'] ?? false): ?>
                                    <span class="px-1.5 py-0.5 text-xs rounded-full bg-yellow-500/20 text-yellow-400 border border-yellow-500/30">Proxy</span>
                                <?php else: ?>
                                    <span class="px-1.5 py-0.5 text-xs rounded-full bg-green-500/20 text-green-400 border border-green-500/30">Normal</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-2 py-2 text-xs text-gray-400">
                                <?php
                                // Tarih ve saat formatında göster
                                $createdTime = strtotime($visitor['created_at']);
                                echo date('d.m.Y H:i:s', $createdTime);
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- IP Whois Sorgulama - Tek Sütun -->
<div class="mb-10">
    <section class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <h2 class="text-xl font-heading font-semibold text-white mb-4">🌍 IP Sorgulama</h2>
        <form id="ip-lookup-form" class="flex gap-3 mb-4">
            <input type="text" id="ip-input" placeholder="IP adresi girin..."
                   class="flex-1 px-4 py-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white placeholder-gray-500 focus:border-cyan-500/50 focus:outline-none transition"
                   required>
            <button class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700 text-white rounded-lg transition font-medium neon-glow-cyan">Sorgula</button>
        </form>
        <div id="ip-lookup-result" class="space-y-4 text-sm">
            <p class="text-gray-400">Sonuçlar burada görüntülenecek.</p>
        </div>
    </section>
</div>

<!-- Eski bölümler - kaldırılacak -->
<div class="space-y-6 hidden">
        <section class="glass-card rounded-xl border border-cyan-500/20 p-6">
            <h2 class="text-xl font-heading font-semibold text-white mb-4">Cihaz Dağılımı</h2>
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <div class="flex-1">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-white font-medium">Mobile</span>
                            <span class="text-cyan-400">65%</span>
                        </div>
                        <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-cyan-500 to-cyan-400 h-2" style="width: 65%;"></div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <div class="flex-1">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-white font-medium">Desktop</span>
                            <span class="text-blue-400">30%</span>
                        </div>
                        <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-400 h-2" style="width: 30%;"></div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                    </svg>
                    <div class="flex-1">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-white font-medium">Tablet</span>
                            <span class="text-gray-400">5%</span>
                        </div>
                        <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
                            <div class="bg-gray-500 h-2" style="width: 5%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
const initialDashboardData = <?=json_encode([
    'trafficTrend' => $metrics['trafficTrend'] ?? [],
    'botTrend' => $metrics['botTrend'] ?? [],
    'proxyTrend' => $metrics['proxyTrend'] ?? [],
    'detectionBreakdown' => $metrics['detectionBreakdown'] ?? [],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;

const chartRefs = { traffic: null };
const numberFormatter = new Intl.NumberFormat('tr-TR');

function initCharts(data) {
    if (!window.Chart) {
        console.error('Chart.js yüklenemedi');
        return;
    }
    const trafficCtx = document.getElementById('trafficChart');
    if (!trafficCtx) {
        console.error('trafficChart canvas elementi bulunamadı');
        return;
    }
    
    // Eğer daha önce chart oluşturulmuşsa, yok et
    if (chartRefs.traffic) {
        chartRefs.traffic.destroy();
    }
    
    const trendLabels = (data.trafficTrend || []).map(item => item.label || item.date || '');
    const allowedData = (data.trafficTrend || []).map(item => item.allowed || 0);
    const blockedData = (data.trafficTrend || []).map(item => item.blocked || 0);
    const botData = (data.botTrend && data.botTrend.length > 0) ? data.botTrend.map(item => item.bots || 0) : trendLabels.map(() => 0);
    const proxyData = (data.proxyTrend && data.proxyTrend.length > 0) ? data.proxyTrend.map(item => item.proxies || 0) : trendLabels.map(() => 0);
    
    // Veri kontrolü
    if (trendLabels.length === 0 || (allowedData.every(v => v === 0) && blockedData.every(v => v === 0) && botData.every(v => v === 0) && proxyData.every(v => v === 0))) {
        console.warn('Grafik için veri yok veya tüm değerler sıfır');
    }
    
    try {
        chartRefs.traffic = new Chart(trafficCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [
                    {
                        label: 'Toplam Tıklama',
                        data: trendLabels.map((_, i) => (allowedData[i] || 0) + (blockedData[i] || 0)),
                        borderColor: '#0CD6F5',
                        backgroundColor: 'rgba(12, 214, 245, 0.15)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2.5,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    },
                    {
                        label: 'Normal Ziyaretçi',
                        data: allowedData,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.15)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2.5,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    },
                    {
                        label: 'Bot',
                        data: botData,
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.15)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2.5,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    },
                    {
                        label: 'Proxy/VPN',
                        data: proxyData,
                        borderColor: '#F59E0B',
                        backgroundColor: 'rgba(245, 158, 11, 0.15)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2.5,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        display: true,
                        labels: {
                            color: '#9CA3AF',
                            font: { family: 'Inter', size: 12 }
                        }
                    } 
                },
                scales: {
                    x: { 
                        grid: { 
                            display: true,
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: { color: '#9CA3AF' }
                    },
                    y: { 
                        grid: { 
                            color: 'rgba(255, 255, 255, 0.05)' 
                        },
                        beginAtZero: true,
                        ticks: { color: '#9CA3AF' }
                    }
                }
            }
        });
        console.log('Grafik başarıyla oluşturuldu');
    } catch (error) {
        console.error('Grafik oluşturma hatası:', error);
    }
}

function updateCharts(data) {
    if (!data || !data.trafficTrend) {
        console.warn('Grafik güncelleme için veri yok');
        return;
    }
    
    if (!chartRefs.traffic) {
        // Chart henüz oluşturulmamışsa oluştur
        initCharts(data);
        return;
    }
    
    try {
        const trendLabels = (data.trafficTrend || []).map(item => item.label || item.date || '');
        const allowedData = (data.trafficTrend || []).map(item => item.allowed || 0);
        const blockedData = (data.trafficTrend || []).map(item => item.blocked || 0);
        const botData = (data.botTrend && data.botTrend.length > 0) ? data.botTrend.map(item => item.bots || 0) : trendLabels.map(() => 0);
        const proxyData = (data.proxyTrend && data.proxyTrend.length > 0) ? data.proxyTrend.map(item => item.proxies || 0) : trendLabels.map(() => 0);
        
        chartRefs.traffic.data.labels = trendLabels;
        chartRefs.traffic.data.datasets[0].data = trendLabels.map((_, i) => (allowedData[i] || 0) + (blockedData[i] || 0));
        chartRefs.traffic.data.datasets[1].data = allowedData;
        chartRefs.traffic.data.datasets[2].data = botData;
        chartRefs.traffic.data.datasets[3].data = proxyData;
        chartRefs.traffic.update('none'); // Animasyon olmadan güncelle
    } catch (error) {
        console.error('Grafik güncelleme hatası:', error);
    }
}

// Chart'ı başlat
if (initialDashboardData && initialDashboardData.trafficTrend) {
    initCharts(initialDashboardData);
} else {
    console.warn('İlk grafik verisi yüklenemedi, API\'den bekleniyor...');
    // İlk yüklemede veri yoksa, API'den gelen verilerle oluştur
    setTimeout(() => {
        fetchDashboardStats();
    }, 500);
}

const fetchDashboardStats = async () => {
    try {
        const response = await fetch('dashboard_stats.php', { credentials: 'include' });
        const result = await response.json();
        
        if (result.status === 'ok' && result.data) {
            const d = result.data;
            
            // Yeni kart yapısına göre güncelle
            const breakdown = d.detectionBreakdown || {};
            const total24h = (breakdown.allowed || 0) + (breakdown.fake || 0);
            
            document.getElementById('metric-total').textContent = numberFormatter.format(total24h);
            document.getElementById('metric-normal').textContent = numberFormatter.format(breakdown.allowed || 0);
            document.getElementById('metric-bot').textContent = numberFormatter.format(breakdown.bots || 0);
            document.getElementById('metric-proxy').textContent = numberFormatter.format(breakdown.proxies || 0);
            
            // Cihaz dağılımını güncelle
            if (d.deviceDistribution) {
                const dev = d.deviceDistribution;
                document.getElementById('device-mobile').textContent = `${dev.mobile.percentage}% (${numberFormatter.format(dev.mobile.count)})`;
                document.getElementById('device-mobile-bar').style.width = `${dev.mobile.percentage}%`;
                document.getElementById('device-desktop').textContent = `${dev.desktop.percentage}% (${numberFormatter.format(dev.desktop.count)})`;
                document.getElementById('device-desktop-bar').style.width = `${dev.desktop.percentage}%`;
                document.getElementById('device-tablet').textContent = `${dev.tablet.percentage}% (${numberFormatter.format(dev.tablet.count)})`;
                document.getElementById('device-tablet-bar').style.width = `${dev.tablet.percentage}%`;
            }
            
            updateCharts(d);

            const perfWrapper = document.getElementById('site-performance');
            if (perfWrapper && Array.isArray(d.sitePerformance)) {
                perfWrapper.innerHTML = d.sitePerformance.length
                    ? d.sitePerformance.map(site => `
                        <div class="flex items-center justify-between p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/40">
                            <div>
                                <p class="font-semibold">${escapeHtml(site.site_name || 'Genel Trafik')}</p>
                                <span class="text-xs text-gray-500 dark:text-gray-400">${numberFormatter.format(site.total)} ziyaret</span>
                            </div>
                            <div class="text-right text-xs">
                                <p class="text-emerald-500">İzinli: ${numberFormatter.format(site.allowed_total || 0)}</p>
                                <p class="text-rose-500">Fake: ${numberFormatter.format(site.fake_total || 0)}</p>
                            </div>
                        </div>
                    `).join('')
                    : '<p class="text-sm text-gray-500">Henüz veri yok.</p>';
            }
            
            // Son aktiviteler tablosunu güncelle
            const visitorsTbody = document.getElementById('recent-visitors');
            if (visitorsTbody && d.recentVisitors && Array.isArray(d.recentVisitors)) {
                if (d.recentVisitors.length === 0) {
                    visitorsTbody.innerHTML = '<tr><td colspan="6" class="px-2 py-4 text-center text-gray-400 text-xs">Henüz ziyaretçi yok</td></tr>';
                } else {
                    visitorsTbody.innerHTML = d.recentVisitors.map(v => {
                        // OS formatı
                        let osDisplay = '';
                        if (v.os) {
                            const osMap = {'ios': 'iOS', 'android': 'Android', 'windows': 'Windows', 'macos': 'macOS', 'linux': 'Linux'};
                            const osKey = v.os.toLowerCase();
                            osDisplay = osMap[osKey] || v.os.charAt(0).toUpperCase() + v.os.slice(1);
                        }
                        const deviceType = (osDisplay === 'iOS' || osDisplay === 'Android') ? 'Mobile' : 'Desktop';
                        
                        // Tarih ve saat formatına çevir
                        let timeDisplay = '';
                        try {
                            const date = new Date(v.created_at);
                            if (!isNaN(date.getTime())) {
                                const day = String(date.getDate()).padStart(2, '0');
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const year = date.getFullYear();
                                const hours = String(date.getHours()).padStart(2, '0');
                                const minutes = String(date.getMinutes()).padStart(2, '0');
                                const seconds = String(date.getSeconds()).padStart(2, '0');
                                timeDisplay = `${day}.${month}.${year} ${hours}:${minutes}:${seconds}`;
                            } else {
                                timeDisplay = escapeHtml(v.created_at || 'N/A');
                            }
                        } catch (e) {
                            timeDisplay = escapeHtml(v.created_at || 'N/A');
                        }
                        
                        return `
                            <tr class="border-b border-gray-700 hover:bg-cyan-500/5 transition">
                                <td class="px-2 py-2 font-mono text-xs text-gray-300">${escapeHtml(v.ip || 'N/A')}</td>
                                <td class="px-2 py-2 text-center">
                                    <span class="px-2 py-1 text-xs rounded-full bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 font-medium">
                                        ${escapeHtml(v.country || 'UN')}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    ${v.is_bot 
                                        ? '<span class="px-2 py-1 text-xs rounded-full bg-red-500/20 text-red-400 border border-red-500/30 font-medium">Bot / ' + escapeHtml(v.browser || 'Unknown') + '</span>'
                                        : '<span class="px-2 py-1 text-xs rounded-full bg-purple-500/20 text-purple-400 border border-purple-500/30 font-medium">' + deviceType + ' / ' + (osDisplay || 'Desktop') + '</span>'}
                                </td>
                                <td class="px-2 py-2 text-center">
                                    ${v.redirect_target === 'normal' 
                                        ? '<span class="px-2 py-1 text-xs rounded-full bg-green-500/20 text-green-400 border border-green-500/30 font-medium">Normal Sayfa</span>'
                                        : '<span class="px-2 py-1 text-xs rounded-full bg-red-500/20 text-red-400 border border-red-500/30 font-medium">Fake Sayfa</span>'}
                                </td>
                                <td class="px-2 py-2 text-center">
                                    ${v.is_bot 
                                        ? '<span class="px-1.5 py-0.5 text-xs rounded-full bg-red-500/20 text-red-400 border border-red-500/30">Bot</span>'
                                        : (v.is_proxy 
                                            ? '<span class="px-1.5 py-0.5 text-xs rounded-full bg-yellow-500/20 text-yellow-400 border border-yellow-500/30">Proxy</span>'
                                            : '<span class="px-1.5 py-0.5 text-xs rounded-full bg-green-500/20 text-green-400 border border-green-500/30">Normal</span>')}
                                </td>
                                <td class="px-2 py-2 text-xs text-gray-400">${timeDisplay}</td>
                            </tr>
                        `;
                    }).join('');
                }
            } else if (visitorsTbody && (!d.recentVisitors || !Array.isArray(d.recentVisitors))) {
                console.warn('recentVisitors verisi bulunamadı veya geçersiz format');
            }
            
            const loginsUl = document.getElementById('recent-logins');
            if (d.recentLogins && Array.isArray(d.recentLogins)) {
                loginsUl.innerHTML = d.recentLogins.map(l => `
                    <li class="flex justify-between items-start border-b border-gray-100 dark:border-gray-800 pb-2 last:border-0">
                        <div>
                            <p class="font-semibold">${escapeHtml(l.username || 'Bilinmeyen')}</p>
                            <p class="text-xs text-gray-500">${escapeHtml(l.ip)}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400">${escapeHtml(l.login_time)}</p>
                            <span class="text-xs font-semibold ${l.success ? 'text-emerald-500' : 'text-rose-500'}">${l.success ? 'Başarılı' : 'Hatalı'}</span>
                        </div>
                    </li>
                `).join('');
            }
            
            const countryList = document.getElementById('country-list');
            if (d.topCountries && Array.isArray(d.topCountries) && d.topCountries.length > 0) {
                const total = d.topCountriesTotal || 1;
                countryList.innerHTML = d.topCountries.map(c => {
                    const pct = total > 0 ? ((c.total / total) * 100).toFixed(1) : 0;
                    return `
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>${escapeHtml(c.country || 'UN')}</span>
                                <span>${pct}%</span>
                            </div>
                            <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-gradient-to-r from-cyan-500 to-cyan-400 h-2 transition-all duration-500" style="width: ${pct}%;"></div>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        }
    } catch (error) {
        console.error('Dashboard güncelleme hatası:', error);
    }
};

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

fetchDashboardStats();
setInterval(fetchDashboardStats, 15000);

const ipForm = document.getElementById('ip-lookup-form');
const ipInput = document.getElementById('ip-input');
const resultBox = document.getElementById('ip-lookup-result');

function renderIpResult(data) {
    if (!data || data.status !== 'ok') {
        resultBox.innerHTML = '<p class="text-red-400">Sorgu başarısız oldu.</p>';
        return;
    }

    const whois = data.whois || {};
    const abuse = data.abuse || {};

    resultBox.innerHTML = `
        <div class="glass-card rounded-lg p-4 border border-cyan-500/20">
            <h3 class="font-semibold mb-2 text-white">Whois</h3>
            <ul class="space-y-2 text-xs text-gray-300">
                <li><strong class="text-cyan-400">IP:</strong> ${escapeHtml(whois.query || '-')}</li>
                <li><strong class="text-cyan-400">Ülke:</strong> ${escapeHtml(whois.country || '-')} (${escapeHtml(whois.countryCode || '-')})</li>
                <li><strong class="text-cyan-400">Şehir:</strong> ${escapeHtml(whois.city || '-')}</li>
                <li><strong class="text-cyan-400">ISP:</strong> ${escapeHtml(whois.isp || '-')}</li>
            </ul>
        </div>
        <div class="glass-card rounded-lg p-4 border border-cyan-500/20">
            <h3 class="font-semibold mb-2 text-white">AbuseIPDB</h3>
            ${abuse.ipAddress ? `
            <ul class="space-y-2 text-xs text-gray-300">
                <li><strong class="text-cyan-400">Risk Skoru:</strong> ${abuse.abuseConfidenceScore}/100</li>
                <li><strong class="text-cyan-400">Son Rapor:</strong> ${escapeHtml(abuse.lastReportedAt || 'Yok')}</li>
                <li><strong class="text-cyan-400">Ülke:</strong> ${escapeHtml(abuse.countryCode || '-')}</li>
            </ul>` : '<p class="text-xs text-gray-400">Kayıt bulunamadı.</p>'}
        </div>
    `;
}

if (ipForm) {
    ipForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const ipValue = ipInput.value.trim();
        if (!ipValue) return;
        resultBox.innerHTML = '<p class="text-xs text-gray-400">Sorgulanıyor...</p>';
        try {
            const response = await fetch(`ip_lookup.php?ip=${encodeURIComponent(ipValue)}`, { credentials: 'include' });
            const payload = await response.json();
            renderIpResult(payload);
        } catch (error) {
            resultBox.innerHTML = '<p class="text-red-400 text-xs">Sunucuya ulaşılamadı.</p>';
            console.error(error);
        }
    });
}
</script>

<?php render_admin_layout_end(); ?>
