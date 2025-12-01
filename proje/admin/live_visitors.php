<?php
require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../cloacker.php';

enforceAdminSession();

// Logout kontrolü
if (isset($_GET['logout'])) {
    logoutAdmin();
}

$pdo = DB::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Bot isimlerini user agent üzerinden tespit için fonksiyon
function getBotName($ua) {
    $ua = strtolower($ua);
    if (strpos($ua, 'googlebot') !== false) return 'Googlebot';
    if (strpos($ua, 'bingbot') !== false) return 'Bingbot';
    if (strpos($ua, 'facebookexternalhit') !== false || strpos($ua, 'facebot') !== false) return 'Facebook Bot';
    if (strpos($ua, 'twitterbot') !== false) return 'Twitterbot';
    if (strpos($ua, 'discordbot') !== false) return 'Discordbot';
    if (strpos($ua, 'linkedinbot') !== false) return 'LinkedIn Bot';
    if (strpos($ua, 'applebot') !== false) return 'Applebot';
    if (strpos($ua, 'telegrambot') !== false) return 'Telegrambot';
    return 'Bot';
}

// Silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected'])) {
    requireCsrfToken();
    $ids = $_POST['delete_ids'] ?? [];
    if (is_array($ids) && count($ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM cloacker_visitors WHERE id IN ($placeholders)");
        $stmt->execute($ids);
    }
    header("Location: live_visitors.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit();
}

// Filtreleme parametreleri
$filterBot = isset($_GET['filter_bot']) ? $_GET['filter_bot'] : '';
$filterProxy = isset($_GET['filter_proxy']) ? $_GET['filter_proxy'] : '';
$filterCountry = isset($_GET['filter_country']) ? trim($_GET['filter_country']) : '';
$filterSite = isset($_GET['filter_site']) ? (int)$_GET['filter_site'] : 0;
$filterRedirect = isset($_GET['filter_redirect']) ? $_GET['filter_redirect'] : '';
$filterDateFrom = isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '';
$filterDateTo = isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '';

// WHERE koşulları oluştur
$whereConditions = [];
$params = [];

if ($filterBot !== '') {
    $whereConditions[] = "v.is_bot = :filter_bot";
    $params[':filter_bot'] = $filterBot === '1' ? 1 : 0;
}

if ($filterProxy !== '') {
    $whereConditions[] = "v.is_proxy = :filter_proxy";
    $params[':filter_proxy'] = $filterProxy === '1' ? 1 : 0;
}

if ($filterCountry !== '') {
    $whereConditions[] = "v.country = :filter_country";
    $params[':filter_country'] = strtoupper($filterCountry);
}

if ($filterSite > 0) {
    $whereConditions[] = "v.site_id = :filter_site";
    $params[':filter_site'] = $filterSite;
}

if ($filterRedirect !== '') {
    $whereConditions[] = "v.redirect_target = :filter_redirect";
    $params[':filter_redirect'] = $filterRedirect;
}

if ($filterDateFrom !== '') {
    $whereConditions[] = "DATE(v.created_at) >= :filter_date_from";
    $params[':filter_date_from'] = $filterDateFrom;
}

if ($filterDateTo !== '') {
    $whereConditions[] = "DATE(v.created_at) <= :filter_date_to";
    $params[':filter_date_to'] = $filterDateTo;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// İstatistikler
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as bots,
        SUM(CASE WHEN is_proxy = 1 THEN 1 ELSE 0 END) as proxies,
        SUM(CASE WHEN redirect_target = 'normal' THEN 1 ELSE 0 END) as normal,
        SUM(CASE WHEN redirect_target = 'fake' THEN 1 ELSE 0 END) as fake,
        AVG(fingerprint_score) as avg_fingerprint_score,
        AVG(bot_confidence) as avg_bot_confidence,
        AVG(ml_confidence) as avg_ml_confidence
    FROM cloacker_visitors v
    $whereClause
");
$statsStmt->execute($params);
$stats = $statsStmt->fetch();

// Site listesi (filtre için)
$sitesStmt = $pdo->query("SELECT id, name FROM cloacker_sites WHERE is_active = 1 ORDER BY name");
$sites = $sitesStmt->fetchAll();

// Ülke listesi (filtre için)
$countriesStmt = $pdo->query("SELECT DISTINCT country FROM cloacker_visitors WHERE country IS NOT NULL AND country != '' ORDER BY country");
$countries = $countriesStmt->fetchAll(PDO::FETCH_COLUMN);

// Sayfalama
$perPage = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

$totalRows = $pdo->prepare("SELECT COUNT(*) FROM cloacker_visitors v $whereClause");
$totalRows->execute($params);
$totalRows = (int)$totalRows->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Site bilgisi ile ziyaretçileri al
$stmt = $pdo->prepare("
    SELECT 
        v.*, 
        s.name AS site_name
    FROM cloacker_visitors v
    LEFT JOIN cloacker_sites s ON v.site_id = s.id
    $whereClause
    ORDER BY v.created_at DESC 
    LIMIT :lim OFFSET :off
");
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$visitors = $stmt->fetchAll();

render_admin_layout_start('Canlı Ziyaretçiler', 'live_visitors');
?>

<!-- İstatistikler Bölümü -->
<div class="mb-6">
    <h2 class="text-2xl font-heading font-bold text-white mb-4">📊 İstatistikler</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="glass-card rounded-xl p-4 border border-cyan-500/20">
            <p class="text-xs uppercase tracking-wider text-gray-400 font-medium mb-1">Toplam Ziyaretçi</p>
            <p class="text-3xl font-heading font-bold text-white"><?= number_format($stats['total'] ?? 0) ?></p>
        </div>
        
        <div class="glass-card rounded-xl p-4 border border-red-500/20">
            <p class="text-xs uppercase tracking-wider text-gray-400 font-medium mb-1">Bot Tespit Edilen</p>
            <p class="text-3xl font-heading font-bold text-red-400"><?= number_format($stats['bots'] ?? 0) ?></p>
            <p class="text-xs text-gray-400 mt-1">
                <?= $stats['total'] > 0 ? number_format(($stats['bots'] / $stats['total']) * 100, 1) : 0 ?>%
            </p>
        </div>
        
        <div class="glass-card rounded-xl p-4 border border-yellow-500/20">
            <p class="text-xs uppercase tracking-wider text-gray-400 font-medium mb-1">Proxy/VPN</p>
            <p class="text-3xl font-heading font-bold text-yellow-400"><?= number_format($stats['proxies'] ?? 0) ?></p>
            <p class="text-xs text-gray-400 mt-1">
                <?= $stats['total'] > 0 ? number_format(($stats['proxies'] / $stats['total']) * 100, 1) : 0 ?>%
            </p>
        </div>
        
        <div class="glass-card rounded-xl p-4 border border-green-500/20">
            <p class="text-xs uppercase tracking-wider text-gray-400 font-medium mb-1">Normal Trafik</p>
            <p class="text-3xl font-heading font-bold text-green-400"><?= number_format($stats['normal'] ?? 0) ?></p>
            <p class="text-xs text-gray-400 mt-1">
                <?= $stats['total'] > 0 ? number_format(($stats['normal'] / $stats['total']) * 100, 1) : 0 ?>%
            </p>
        </div>
    </div>
    
    <!-- Skor İstatistikleri -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="glass-card rounded-xl p-4 border border-cyan-500/20">
            <p class="text-xs uppercase tracking-wider text-gray-400 font-medium mb-1">Ortalama Fingerprint Skoru</p>
            <p class="text-2xl font-heading font-bold text-cyan-400">
                <?= $stats['avg_fingerprint_score'] ? number_format((float)$stats['avg_fingerprint_score'], 2) : '0.00' ?>
            </p>
        </div>
        
        <div class="glass-card rounded-xl p-4 border border-purple-500/20">
            <p class="text-xs uppercase tracking-wider text-gray-400 font-medium mb-1">Ortalama Bot Confidence</p>
            <p class="text-2xl font-heading font-bold text-purple-400">
                <?= $stats['avg_bot_confidence'] ? number_format((float)$stats['avg_bot_confidence'], 2) : '0.00' ?>%
            </p>
        </div>
        
        <div class="glass-card rounded-xl p-4 border border-blue-500/20">
            <p class="text-xs uppercase tracking-wider text-gray-400 font-medium mb-1">Ortalama ML Confidence</p>
            <p class="text-2xl font-heading font-bold text-blue-400">
                <?= $stats['avg_ml_confidence'] ? number_format((float)$stats['avg_ml_confidence'], 2) : '0.00' ?>%
            </p>
        </div>
    </div>
</div>

<!-- Gelişmiş Filtreleme -->
<div class="mb-6 glass-card rounded-xl p-6 border border-cyan-500/20">
    <h2 class="text-xl font-heading font-semibold text-white mb-4">🔍 Gelişmiş Filtreleme</h2>
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Bot Durumu</label>
            <select name="filter_bot" class="w-full px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                <option value="">Tümü</option>
                <option value="1" <?= $filterBot === '1' ? 'selected' : '' ?>>Bot</option>
                <option value="0" <?= $filterBot === '0' ? 'selected' : '' ?>>Gerçek Kullanıcı</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Proxy/VPN Durumu</label>
            <select name="filter_proxy" class="w-full px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                <option value="">Tümü</option>
                <option value="1" <?= $filterProxy === '1' ? 'selected' : '' ?>>Proxy/VPN Var</option>
                <option value="0" <?= $filterProxy === '0' ? 'selected' : '' ?>>Proxy/VPN Yok</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Ülke</label>
            <select name="filter_country" class="w-full px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                <option value="">Tüm Ülkeler</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= htmlspecialchars($country) ?>" <?= $filterCountry === $country ? 'selected' : '' ?>>
                        <?= htmlspecialchars($country) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Site</label>
            <select name="filter_site" class="w-full px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                <option value="0">Tüm Siteler</option>
                <?php foreach ($sites as $site): ?>
                    <option value="<?= $site['id'] ?>" <?= $filterSite == $site['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($site['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Yönlendirme</label>
            <select name="filter_redirect" class="w-full px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
                <option value="">Tümü</option>
                <option value="normal" <?= $filterRedirect === 'normal' ? 'selected' : '' ?>>Normal (Money Page)</option>
                <option value="fake" <?= $filterRedirect === 'fake' ? 'selected' : '' ?>>Fake (Safe Page)</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Başlangıç Tarihi</label>
            <input type="date" name="filter_date_from" value="<?= htmlspecialchars($filterDateFrom) ?>"
                   class="w-full px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Bitiş Tarihi</label>
            <input type="date" name="filter_date_to" value="<?= htmlspecialchars($filterDateTo) ?>"
                   class="w-full px-4 py-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white">
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-lg hover:from-cyan-600 hover:to-teal-600 transition font-medium">
                Filtrele
            </button>
        </div>
    </form>
    
    <?php if ($filterBot !== '' || $filterProxy !== '' || $filterCountry !== '' || $filterSite > 0 || $filterRedirect !== '' || $filterDateFrom !== '' || $filterDateTo !== ''): ?>
        <div class="mt-4">
            <a href="live_visitors.php" class="text-sm text-cyan-400 hover:text-cyan-300 underline">
                Filtreleri Temizle
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Ziyaretçi Listesi -->
<form method="post" onsubmit="return confirm('Seçili kayıtları silmek istediğinize emin misiniz?');">
    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
    <div class="flex justify-between items-center mb-4">
        <button type="submit" name="delete_selected" class="px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 neon-glow-red text-white rounded-lg shadow transition">Seçilileri Sil</button>
        <span class="text-sm text-gray-300">Toplam: <?=number_format($totalRows)?> kayıt</span>
    </div>

    <div class="overflow-x-auto shadow-lg rounded-xl border border-cyan-500/20 glass-card">
        <table class="w-full text-xs">
            <thead class="bg-gradient-to-r from-cyan-500 to-teal-500 text-white">
                <tr>
                    <th class="px-2 py-2"><input type="checkbox" id="select_all" class="w-3 h-3"></th>
                    <th class="px-2 py-2 text-left">ID</th>
                    <th class="px-2 py-2 text-left">IP</th>
                    <th class="px-2 py-2 text-left">Ülke</th>
                    <th class="px-2 py-2 text-left">OS</th>
                    <th class="px-2 py-2 text-left">Tarayıcı / Bot</th>
                    <th class="px-2 py-2 text-left">Site</th>
                    <th class="px-2 py-2 text-left">FP Skoru</th>
                    <th class="px-2 py-2 text-left">Bot Conf</th>
                    <th class="px-2 py-2 text-center">Proxy</th>
                    <th class="px-2 py-2 text-center">Bot</th>
                    <th class="px-2 py-2 text-center">Yönlendirme</th>
                    <th class="px-2 py-2 text-left">Zaman</th>
                    <th class="px-2 py-2 text-center">Detay</th>
                </tr>
            </thead>
            <tbody class="glass-card text-white">
                <?php foreach ($visitors as $v): ?>
                <tr class="border-b border-gray-700 hover:bg-cyan-500/5 transition">
                    <td class="px-2 py-2 text-center"><input type="checkbox" name="delete_ids[]" value="<?=$v['id']?>" class="w-3 h-3"></td>
                    <td class="px-2 py-2 text-gray-300"><?=htmlspecialchars($v['id'])?></td>
                    <td class="px-2 py-2 font-mono text-xs text-gray-300"><?=htmlspecialchars($v['ip'])?></td>
                    <td class="px-2 py-2 text-gray-300"><?=htmlspecialchars($v['country'] ?? 'UN')?></td>
                    <td class="px-2 py-2 text-gray-300 text-xs"><?=htmlspecialchars($v['os'] ?? 'unknown')?></td>
                    <td class="px-2 py-2">
                        <?php if ($v['is_bot']): ?>
                            <span class="text-red-400 font-semibold text-xs"><?=htmlspecialchars(getBotName($v['user_agent']))?></span>
                        <?php else: ?>
                            <span class="text-xs"><?=htmlspecialchars($v['browser'] ?? 'Unknown')?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-2 py-2">
                        <?php if (!empty($v['site_name'])): ?>
                            <span class="px-1.5 py-0.5 text-xs rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                                <?=htmlspecialchars($v['site_name'])?>
                            </span>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">Genel</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-2 py-2">
                        <div class="flex flex-col">
                            <span class="text-cyan-400 font-semibold text-xs"><?= $v['fingerprint_score'] ?? 0 ?></span>
                            <?php if (!empty($v['ml_confidence'])): ?>
                                <span class="text-xs text-gray-500">ML: <?= number_format((float)$v['ml_confidence'], 1) ?>%</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-2 py-2">
                        <div class="flex flex-col">
                            <span class="text-purple-400 font-semibold text-xs"><?= $v['bot_confidence'] ? number_format((float)$v['bot_confidence'], 1) : '0.0' ?>%</span>
                            <?php if (!empty($v['dynamic_threshold'])): ?>
                                <span class="text-xs text-gray-500">Eşik: <?= number_format((float)$v['dynamic_threshold'], 1) ?>%</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <?php if ($v['is_proxy']): ?>
                            <span class="px-1.5 py-0.5 text-xs rounded-full bg-yellow-500/20 text-yellow-400 border border-yellow-500/30">Evet</span>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <?php if ($v['is_bot']): ?>
                            <span class="px-1.5 py-0.5 text-xs rounded-full bg-red-500/20 text-red-400 border border-red-500/30">Evet</span>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <?php if ($v['redirect_target'] === 'normal'): ?>
                            <span class="px-1.5 py-0.5 text-xs rounded-full bg-green-500/20 text-green-400 border border-green-500/30">Normal</span>
                        <?php else: ?>
                            <span class="px-1.5 py-0.5 text-xs rounded-full bg-blue-500/20 text-blue-400 border border-blue-500/30">Fake</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-2 py-2 text-xs text-gray-400"><?=htmlspecialchars($v['created_at'])?></td>
                    <td class="px-2 py-2 text-center">
                        <button type="button" onclick="showVisitorDetails(<?= $v['id'] ?>)" 
                                class="px-2 py-1 bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-lg hover:from-cyan-600 hover:to-teal-600 transition text-xs font-medium">
                            Detay
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>

<?php if ($totalPages > 1): ?>
<div class="flex justify-center space-x-2 mt-6">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?page=<?=$p?><?= !empty($_SERVER['QUERY_STRING']) ? '&' . str_replace('page=' . $page . '&', '', str_replace('&page=' . $page, '', $_SERVER['QUERY_STRING'])) : '' ?>" 
           class="px-3 py-2 rounded-lg border transition <?= $p == $page ? 'bg-gradient-to-r from-cyan-500 to-teal-500 text-white border-transparent shadow-md' : 'glass-card border-gray-300 dark:border-gray-700 text-cyan-600 dark:text-cyan-400 hover:bg-cyan-500/5' ?>">
            <?=$p?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<!-- Detay Modal -->
<div id="visitorModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="glass-card rounded-xl border border-cyan-500/20 p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-2xl font-heading font-bold text-white">Ziyaretçi Detayları</h3>
            <button onclick="closeVisitorModal()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
        </div>
        
        <div id="visitorModalContent" class="space-y-4">
            <p class="text-gray-400">Yükleniyor...</p>
        </div>
    </div>
</div>

<script>
document.getElementById("select_all").addEventListener("change", function(){
    document.querySelectorAll('input[name="delete_ids[]"]').forEach(el => el.checked = this.checked);
});

function showVisitorDetails(visitorId) {
    const modal = document.getElementById('visitorModal');
    const content = document.getElementById('visitorModalContent');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    content.innerHTML = '<p class="text-gray-400">Yükleniyor...</p>';
    
    fetch(`api/visitor_details.php?id=${visitorId}`, {
        credentials: 'include'
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'ok') {
            renderVisitorDetails(data);
        } else {
            content.innerHTML = '<p class="text-red-400">Hata: ' + (data.message || 'Bilinmeyen hata') + '</p>';
        }
    })
    .catch(err => {
        content.innerHTML = '<p class="text-red-400">Yüklenirken hata oluştu.</p>';
        console.error(err);
    });
}

function closeVisitorModal() {
    document.getElementById('visitorModal').classList.add('hidden');
    document.getElementById('visitorModal').classList.remove('flex');
}

function renderVisitorDetails(data) {
    const v = data.visitor;
    const content = document.getElementById('visitorModalContent');
    
    let html = `
        <!-- Temel Bilgiler -->
        <div class="glass-card rounded-lg p-4 border border-cyan-500/20">
            <h4 class="text-lg font-semibold text-white mb-3">Temel Bilgiler</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-400">IP Adresi:</span>
                    <span class="text-white font-mono ml-2">${escapeHtml(v.ip)}</span>
                </div>
                <div>
                    <span class="text-gray-400">Ülke:</span>
                    <span class="text-white ml-2">${escapeHtml(v.country || 'UN')}</span>
                </div>
                <div>
                    <span class="text-gray-400">OS:</span>
                    <span class="text-white ml-2">${escapeHtml(v.os || 'unknown')}</span>
                </div>
                <div>
                    <span class="text-gray-400">Tarayıcı:</span>
                    <span class="text-white ml-2">${escapeHtml(v.browser || 'Unknown')}</span>
                </div>
                <div>
                    <span class="text-gray-400">Site:</span>
                    <span class="text-white ml-2">${escapeHtml(v.site_name || 'Genel')}</span>
                </div>
                <div>
                    <span class="text-gray-400">Zaman:</span>
                    <span class="text-white ml-2">${escapeHtml(v.created_at)}</span>
                </div>
            </div>
        </div>
        
        <!-- User-Agent -->
        <div class="glass-card rounded-lg p-4 border border-cyan-500/20">
            <h4 class="text-lg font-semibold text-white mb-3">User-Agent</h4>
            <div class="bg-gray-900/50 p-3 rounded-lg">
                <code class="text-xs text-gray-300 break-all">${escapeHtml(v.user_agent || 'N/A')}</code>
            </div>
        </div>
        
        <!-- Bot/Proxy Bilgileri -->
        <div class="glass-card rounded-lg p-4 border border-cyan-500/20">
            <h4 class="text-lg font-semibold text-white mb-3">Tespit Bilgileri</h4>
            <div class="space-y-2">
    `;
    
    if (data.bot_name) {
        html += `
            <div class="flex items-center justify-between p-2 bg-red-500/10 rounded-lg">
                <span class="text-gray-300">Bot Adı:</span>
                <span class="text-red-400 font-semibold">${escapeHtml(data.bot_name)}</span>
            </div>
        `;
    }
    
    if (data.proxy_type) {
        html += `
            <div class="flex items-center justify-between p-2 bg-yellow-500/10 rounded-lg">
                <span class="text-gray-300">Proxy/VPN Tipi:</span>
                <span class="text-yellow-400 font-semibold">${escapeHtml(data.proxy_type)}</span>
            </div>
        `;
    }
    
    html += `
                <div class="flex items-center justify-between p-2 bg-cyan-500/10 rounded-lg">
                    <span class="text-gray-300">Fingerprint Skoru:</span>
                    <span class="text-cyan-400 font-semibold">${v.fingerprint_score || 0}</span>
                </div>
                <div class="flex items-center justify-between p-2 bg-purple-500/10 rounded-lg">
                    <span class="text-gray-300">Bot Confidence:</span>
                    <span class="text-purple-400 font-semibold">${v.bot_confidence ? parseFloat(v.bot_confidence).toFixed(2) : '0.00'}%</span>
                </div>
    `;
    
    if (v.ml_confidence) {
        html += `
            <div class="flex items-center justify-between p-2 bg-blue-500/10 rounded-lg">
                <span class="text-gray-300">ML Confidence:</span>
                <span class="text-blue-400 font-semibold">${parseFloat(v.ml_confidence).toFixed(2)}%</span>
            </div>
        `;
    }
    
    html += `
            </div>
        </div>
        
        <!-- Neden Bot/Proxy/VPN -->
        ${data.reasons && data.reasons.length > 0 ? `
        <div class="glass-card rounded-lg p-4 border border-red-500/20">
            <h4 class="text-lg font-semibold text-red-400 mb-3">Tespit Nedenleri</h4>
            <ul class="space-y-1">
                ${data.reasons.map(r => `<li class="text-sm text-gray-300">• ${escapeHtml(r)}</li>`).join('')}
            </ul>
        </div>
        ` : ''}
        
        <!-- Filtreler -->
        <div class="glass-card rounded-lg p-4 border border-cyan-500/20">
            <h4 class="text-lg font-semibold text-white mb-3">Bot Filtreleri</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
    `;
    
    for (const [key, filter] of Object.entries(data.filters)) {
        const statusClass = filter.detected ? 'bg-red-500/20 text-red-400 border-red-500/30' : 'bg-gray-800/50 text-gray-400 border-gray-700';
        const enabledClass = filter.enabled ? '' : 'opacity-50';
        const detectedIcon = filter.detected ? '✓' : '✗';
        
        html += `
            <div class="p-2 rounded-lg border ${statusClass} ${enabledClass}">
                <div class="flex items-center justify-between">
                    <span class="text-sm">${escapeHtml(filter.name)}</span>
                    <div class="flex items-center gap-2">
                        ${!filter.enabled ? '<span class="text-xs text-gray-500">(Pasif)</span>' : ''}
                        <span class="text-xs font-semibold">${detectedIcon}</span>
                    </div>
                </div>
                ${filter.value ? `<div class="text-xs text-gray-500 mt-1 break-words">${escapeHtml(String(filter.value))}</div>` : ''}
            </div>
        `;
    }
    
    html += `
            </div>
        </div>
        
        <!-- Lokasyon Bilgileri -->
        <div class="glass-card rounded-lg p-4 border border-cyan-500/20">
            <h4 class="text-lg font-semibold text-white mb-3">Lokasyon Bilgileri</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400">Ülke:</span>
                    <span class="text-white">${escapeHtml(data.location.country || 'UN')}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">IP:</span>
                    <span class="text-white font-mono">${escapeHtml(data.location.ip)}</span>
                </div>
            </div>
        </div>
        
        <!-- Fingerprint Detayları -->
        <div class="glass-card rounded-lg p-4 border border-cyan-500/20">
            <h4 class="text-lg font-semibold text-white mb-3">Fingerprint Detayları</h4>
            <div class="space-y-2 text-sm">
                ${v.fingerprint_hash ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">Fingerprint Hash:</span>
                    <span class="text-white font-mono text-xs">${escapeHtml(v.fingerprint_hash.substring(0, 16))}...</span>
                </div>
                ` : ''}
                ${v.ja3_hash ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">JA3 Hash:</span>
                    <span class="text-white font-mono text-xs">${escapeHtml(v.ja3_hash.substring(0, 16))}...</span>
                </div>
                ` : ''}
                ${v.ja3s_hash ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">JA3s Hash:</span>
                    <span class="text-white font-mono text-xs">${escapeHtml(v.ja3s_hash.substring(0, 16))}...</span>
                </div>
                ` : ''}
                ${v.canvas_fingerprint ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">Canvas Fingerprint:</span>
                    <span class="text-white font-mono text-xs">${escapeHtml(String(v.canvas_fingerprint).substring(0, 16))}...</span>
                </div>
                ` : ''}
                ${v.webgl_fingerprint ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">WebGL Fingerprint:</span>
                    <span class="text-white font-mono text-xs">${escapeHtml(String(v.webgl_fingerprint).substring(0, 16))}...</span>
                </div>
                ` : ''}
                ${v.audio_fingerprint ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">Audio Fingerprint:</span>
                    <span class="text-white font-mono text-xs">${escapeHtml(String(v.audio_fingerprint).substring(0, 16))}...</span>
                </div>
                ` : ''}
                ${v.fonts_hash ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">Fonts Hash:</span>
                    <span class="text-white font-mono text-xs">${escapeHtml(String(v.fonts_hash).substring(0, 16))}...</span>
                </div>
                ` : ''}
                ${v.plugins_hash ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">Plugins Hash:</span>
                    <span class="text-white font-mono text-xs">${escapeHtml(String(v.plugins_hash).substring(0, 16))}...</span>
                </div>
                ` : ''}
                ${v.webrtc_leak ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">WebRTC Leak:</span>
                    <span class="text-red-400">Evet</span>
                </div>
                ` : ''}
                ${v.local_ip_detected ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">Local IP:</span>
                    <span class="text-white font-mono text-xs">${escapeHtml(v.local_ip_detected)}</span>
                </div>
                ` : ''}
                ${v.rdns_hostname ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">rDNS Hostname:</span>
                    <span class="text-white font-mono text-xs">${escapeHtml(v.rdns_hostname)}</span>
                </div>
                ` : ''}
                ${v.rdns_is_bot !== undefined ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">rDNS Bot:</span>
                    <span class="${v.rdns_is_bot ? 'text-red-400' : 'text-green-400'}">${v.rdns_is_bot ? 'Evet' : 'Hayır'}</span>
                </div>
                ` : ''}
                ${v.tls13_fingerprint ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">TLS 1.3 Fingerprint:</span>
                    <span class="text-white font-mono text-xs">${escapeHtml(String(v.tls13_fingerprint).substring(0, 16))}...</span>
                </div>
                ` : ''}
                ${v.threat_score !== undefined && v.threat_score !== null ? `
                <div class="flex justify-between">
                    <span class="text-gray-400">Threat Score:</span>
                    <span class="${parseFloat(v.threat_score) >= 50 ? 'text-red-400' : 'text-green-400'}">${parseFloat(v.threat_score).toFixed(2)}</span>
                </div>
                ` : ''}
            </div>
        </div>
    `;
    
    content.innerHTML = html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

// Modal dışına tıklanınca kapat
document.getElementById('visitorModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeVisitorModal();
    }
});
</script>

<?php render_admin_layout_end(); ?>
