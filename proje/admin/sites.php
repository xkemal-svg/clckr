<?php
require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/layout.php';

enforceAdminSession();

$pdo = DB::connect();
$error = '';
$success = '';

// Site oluşturma/güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_site'])) {
    requireCsrfToken();
    
    $siteId = (int)($_POST['site_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $domainInput = trim($_POST['domain'] ?? '');
    $domain = normalizeDomain($domainInput);
    $normalUrl = trim($_POST['normal_url'] ?? '');
    $fakeUrl = trim($_POST['fake_url'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $botConfidenceThreshold = (float)($_POST['bot_confidence_threshold'] ?? 30.0);
    $allowedCountries = trim($_POST['allowed_countries'] ?? '');
    // OS ve Browser boşsa, tümü izinli demektir
    $allowedOS = isset($_POST['allowed_os']) && is_array($_POST['allowed_os']) && count($_POST['allowed_os']) > 0 
        ? implode(',', $_POST['allowed_os']) : '';
    $allowedBrowsers = isset($_POST['allowed_browsers']) && is_array($_POST['allowed_browsers']) && count($_POST['allowed_browsers']) > 0
        ? implode(',', $_POST['allowed_browsers']) : '';
    $telegramBotToken = trim($_POST['telegram_bot_token'] ?? '');
    $telegramChatId = trim($_POST['telegram_chat_id'] ?? '');
    
    if (empty($name) || empty($domainInput) || empty($domain) || empty($normalUrl) || empty($fakeUrl)) {
        $error = "Tüm zorunlu alanlar doldurulmalıdır.";
    } elseif (!filter_var($normalUrl, FILTER_VALIDATE_URL) || !filter_var($fakeUrl, FILTER_VALIDATE_URL)) {
        $error = "Geçerli URL'ler giriniz.";
    } elseif (!$domain) {
        $error = "Geçerli bir domain giriniz.";
    } else {
        $settings = json_encode([
            'bot_confidence_threshold' => $botConfidenceThreshold,
            'allowed_countries' => strtoupper($allowedCountries),
            'allowed_os' => strtolower($allowedOS),
            'allowed_browsers' => strtolower($allowedBrowsers),
            'telegram_bot_token' => $telegramBotToken,
            'telegram_chat_id' => $telegramChatId
        ]);
        
        if ($siteId > 0) {
            // Güncelle
            $stmt = $pdo->prepare("
                UPDATE cloacker_sites 
                SET name = :name, domain = :domain, normal_url = :normal_url, fake_url = :fake_url, 
                    is_active = :active, settings = :settings, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $siteId,
                ':name' => $name,
                ':domain' => $domain,
                ':normal_url' => $normalUrl,
                ':fake_url' => $fakeUrl,
                ':active' => $isActive,
                ':settings' => $settings
            ]);
            $success = "Site güncellendi.";
        } else {
            // Yeni site
            $stmt = $pdo->prepare("
                INSERT INTO cloacker_sites (name, domain, normal_url, fake_url, is_active, settings, created_by, created_at)
                VALUES (:name, :domain, :normal_url, :fake_url, :active, :settings, :created_by, NOW())
            ");
            $stmt->execute([
                ':name' => $name,
                ':domain' => $domain,
                ':normal_url' => $normalUrl,
                ':fake_url' => $fakeUrl,
                ':active' => $isActive,
                ':settings' => $settings,
                ':created_by' => $_SESSION['admin_id']
            ]);
            $success = "Site oluşturuldu.";
        }
    }
}

// Site silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_site'])) {
    requireCsrfToken();
    
    $siteId = (int)($_POST['site_id'] ?? 0);
    if ($siteId > 0) {
        $stmt = $pdo->prepare("DELETE FROM cloacker_sites WHERE id = :id");
        $stmt->execute([':id' => $siteId]);
        $success = "Site silindi.";
    }
}

// Düzenleme için site bilgisi
$editSite = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM cloacker_sites WHERE id = :id");
    $stmt->execute([':id' => $editId]);
    $editSite = $stmt->fetch();
    if ($editSite) {
        $editSite['settings'] = !empty($editSite['settings'])
            ? (json_decode($editSite['settings'], true) ?: [])
            : [];
    }
}

$formValues = [
    'name' => $_POST['name'] ?? ($editSite['name'] ?? ''),
    'domain' => $_POST['domain'] ?? ($editSite['domain'] ?? ''),
    'normal_url' => $_POST['normal_url'] ?? ($editSite['normal_url'] ?? ''),
    'fake_url' => $_POST['fake_url'] ?? ($editSite['fake_url'] ?? ''),
    'allowed_countries' => $_POST['allowed_countries'] ?? ($editSite['settings']['allowed_countries'] ?? ''),
    'bot_confidence_threshold' => $_POST['bot_confidence_threshold'] ?? ($editSite['settings']['bot_confidence_threshold'] ?? 30.0),
    'telegram_bot_token' => $_POST['telegram_bot_token'] ?? ($editSite['settings']['telegram_bot_token'] ?? ''),
    'telegram_chat_id' => $_POST['telegram_chat_id'] ?? ($editSite['settings']['telegram_chat_id'] ?? ''),
    'is_active' => isset($_POST['is_active']) ? 1 : ($editSite['is_active'] ?? 1),
];

$osList = ['android'=>'Android', 'ios'=>'iOS', 'windows'=>'Windows', 'linux'=>'Linux', 'macos'=>'macOS'];
$browserList = ['chrome'=>'Chrome', 'firefox'=>'Firefox', 'safari'=>'Safari', 'edge'=>'Edge', 'opera'=>'Opera'];

$selectedOS = isset($_POST['allowed_os']) && is_array($_POST['allowed_os'])
    ? array_values(array_intersect(array_keys($osList), array_map('strtolower', $_POST['allowed_os'])))
    : (
        isset($editSite['settings']['allowed_os'])
            ? array_filter(array_map('trim', explode(',', strtolower((string)$editSite['settings']['allowed_os']))))
            : array_keys($osList)
    );

$selectedBrowsers = isset($_POST['allowed_browsers']) && is_array($_POST['allowed_browsers'])
    ? array_values(array_intersect(array_keys($browserList), array_map('strtolower', $_POST['allowed_browsers'])))
    : (
        isset($editSite['settings']['allowed_browsers'])
            ? array_filter(array_map('trim', explode(',', strtolower((string)$editSite['settings']['allowed_browsers']))))
            : array_keys($browserList)
    );

// Tüm siteler
$stmt = $pdo->query("
    SELECT s.*, a.username AS created_by_username,
           (SELECT COUNT(*) FROM cloacker_api_keys WHERE site_id = s.id) AS api_key_count
    FROM cloacker_sites s
    LEFT JOIN cloacker_admins a ON s.created_by = a.id
    ORDER BY s.created_at DESC
");
$sites = $stmt->fetchAll();

render_admin_layout_start('Siteler', 'sites');
?>

<?php if($error): ?>
    <div class="mb-4 p-4 rounded-lg glass-card border border-red-500/30 text-red-400"><?=htmlspecialchars($error)?></div>
<?php endif; ?>
<?php if($success): ?>
    <div class="mb-4 p-4 rounded-lg glass-card border border-cyan-500/30 text-cyan-400"><?=htmlspecialchars($success)?></div>
<?php endif; ?>

<div class="space-y-6 mb-8">
    <!-- Site Formu -->
    <div class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <h3 class="text-xl font-heading font-semibold text-white mb-4">
            <?=$editSite ? 'Site Düzenle' : 'Yeni Site Ekle'?>
        </h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
            <input type="hidden" name="site_id" value="<?=$editSite['id'] ?? 0?>">
            <input type="hidden" name="save_site" value="1">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Site Adı</label>
                    <input type="text" name="name" value="<?=htmlspecialchars($formValues['name'])?>" required
                           class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white placeholder-gray-500 focus:border-cyan-500/50 focus:outline-none transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Domain</label>
                    <input type="text" name="domain" value="<?=htmlspecialchars($formValues['domain'])?>" 
                           placeholder="example.com" required
                           class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white placeholder-gray-500 focus:border-cyan-500/50 focus:outline-none transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Normal URL (İzinli)</label>
                    <input type="url" name="normal_url" value="<?=htmlspecialchars($formValues['normal_url'])?>" required
                           class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white placeholder-gray-500 focus:border-cyan-500/50 focus:outline-none transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Fake URL (Bot/VPN)</label>
                    <input type="url" name="fake_url" value="<?=htmlspecialchars($formValues['fake_url'])?>" required
                           class="w-full p-3 rounded-lg glass-card border border-red-500/20 bg-gray-900/50 text-white placeholder-gray-500 focus:border-red-500/50 focus:outline-none transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">İzin Verilen Ülkeler (ISO2, virgülle ayırın)</label>
                    <input type="text" name="allowed_countries" 
                           value="<?=htmlspecialchars($formValues['allowed_countries'])?>" 
                           placeholder="TR,US,GB,DE"
                           class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white placeholder-gray-500 focus:border-cyan-500/50 focus:outline-none transition">
                    <p class="text-xs text-gray-400 mt-1">Örnek: TR,US,GB (boş bırakırsanız tüm ülkeler izinli)</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">İzin Verilen İşletim Sistemleri</label>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <?php foreach ($osList as $key => $label): ?>
                            <label class="flex items-center gap-2 p-3 rounded-lg glass-card border border-cyan-500/10 hover:border-cyan-500/30 cursor-pointer transition">
                                <input type="checkbox" name="allowed_os[]" value="<?=$key?>"
                                       <?= in_array($key, $selectedOS) ? 'checked' : '' ?> class="w-4 h-4 accent-cyan-500">
                                <span class="text-sm text-gray-300"><?=$label?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">İzin Verilen Tarayıcılar</label>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <?php foreach ($browserList as $key => $label): ?>
                            <label class="flex items-center gap-2 p-3 rounded-lg glass-card border border-cyan-500/10 hover:border-cyan-500/30 cursor-pointer transition">
                                <input type="checkbox" name="allowed_browsers[]" value="<?=$key?>"
                                       <?= in_array($key, $selectedBrowsers) ? 'checked' : '' ?> class="w-4 h-4 accent-cyan-500">
                                <span class="text-sm text-gray-300"><?=$label?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Bot Güven Eşiği (%)</label>
                    <input type="number" name="bot_confidence_threshold" 
                           value="<?=htmlspecialchars($formValues['bot_confidence_threshold'])?>" 
                           min="0" max="100" step="0.1"
                           class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white focus:border-cyan-500/50 focus:outline-none transition">
                    <p class="text-xs text-gray-400 mt-1">Bu değerin üzerindeki bot güven skorları bot olarak işaretlenir.</p>
                </div>
                
                <div class="border-t border-gray-700 pt-4">
                    <h4 class="text-sm font-semibold mb-2 text-gray-300">Telegram Bildirim Ayarları (Opsiyonel)</h4>
                    <div class="space-y-2">
                        <div>
                            <label class="block text-xs font-medium mb-1 text-gray-400">Telegram Bot Token</label>
                            <input type="text" name="telegram_bot_token" 
                                   value="<?=htmlspecialchars($formValues['telegram_bot_token'])?>" 
                                   placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                                   class="w-full p-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white placeholder-gray-500 focus:border-cyan-500/50 focus:outline-none transition text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1 text-gray-400">Telegram Chat ID</label>
                            <input type="text" name="telegram_chat_id" 
                                   value="<?=htmlspecialchars($formValues['telegram_chat_id'])?>" 
                                   placeholder="123456789"
                                   class="w-full p-2 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white placeholder-gray-500 focus:border-cyan-500/50 focus:outline-none transition text-xs">
                        </div>
                        <p class="text-xs text-gray-400">Link çalışmazsa Telegram'a bildirim gönderilir</p>
                    </div>
                </div>
                
                <div>
                    <label class="flex items-center gap-2 text-gray-300">
                        <input type="checkbox" name="is_active" value="1" 
                               <?=$formValues['is_active'] ? 'checked' : ''?> class="w-4 h-4 accent-cyan-500">
                        <span class="text-sm">Aktif</span>
                    </label>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700 text-white py-3 rounded-lg transition font-medium neon-glow-cyan">
                    <?=$editSite ? 'Güncelle' : 'Oluştur'?>
                </button>
                
                <?php if($editSite): ?>
                    <a href="sites.php" class="block text-center text-sm text-cyan-400 hover:text-cyan-300 transition">
                        İptal
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Site Listesi -->
    <div class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <h3 class="text-xl font-heading font-semibold text-white mb-4">Mevcut Siteler</h3>
        
        <?php if (empty($sites)): ?>
            <p class="text-gray-400">Henüz site eklenmemiş.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-400 border-b border-gray-700">
                        <tr>
                            <th class="py-3 font-medium">Ad</th>
                            <th class="py-3 font-medium">Domain</th>
                            <th class="py-3 font-medium">Durum</th>
                            <th class="py-3 font-medium">API Keys</th>
                            <th class="py-3 font-medium">Oluşturulma</th>
                            <th class="py-3 font-medium">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        <?php foreach ($sites as $site): ?>
                            <tr class="hover:bg-cyan-500/5 transition">
                                <td class="py-3 font-semibold text-white"><?=htmlspecialchars($site['name'])?></td>
                                <td class="py-3 text-gray-300"><?=htmlspecialchars($site['domain'])?></td>
                                <td class="py-3">
                                    <?php if ($site['is_active']): ?>
                                        <span class="px-3 py-1 text-xs rounded-lg bg-gradient-to-r from-cyan-500/20 to-cyan-400/20 border border-cyan-500/30 text-cyan-300 font-medium">Aktif</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 text-xs rounded-lg bg-gradient-to-r from-red-500/20 to-red-400/20 border border-red-500/30 text-red-300 font-medium">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-gray-300"><?=$site['api_key_count']?></td>
                                <td class="py-3 text-xs text-gray-400"><?=htmlspecialchars($site['created_at'])?></td>
                                <td class="py-3">
                                    <a href="?edit=<?=$site['id']?>" class="text-xs px-3 py-1 rounded-lg glass-card border border-cyan-500/20 text-cyan-400 hover:border-cyan-500/40 hover:text-cyan-300 transition">
                                        Düzenle
                                    </a>
                                    <form method="post" class="inline ml-2" onsubmit="return confirm('Bu siteyi silmek istediğinizden emin misiniz? Tüm API anahtarları da silinecek.');">
                                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
                                        <input type="hidden" name="site_id" value="<?=$site['id']?>">
                                        <input type="hidden" name="delete_site" value="1">
                                        <button type="submit" class="text-xs px-3 py-1 rounded-lg glass-card border border-red-500/20 text-red-400 hover:border-red-500/40 hover:text-red-300 transition">
                                            Sil
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php render_admin_layout_end(); ?>

