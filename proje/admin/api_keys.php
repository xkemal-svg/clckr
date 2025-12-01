<?php
require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/layout.php';

enforceAdminSession();

$pdo = DB::connect();
$error = '';
$success = '';

// API Key oluşturma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_key'])) {
    requireCsrfToken();
    
    $siteId = (int)($_POST['site_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    
    if ($siteId <= 0) {
        $error = "Geçerli bir site seçiniz.";
    } else {
        // Site kontrolü
        $stmt = $pdo->prepare("SELECT id FROM cloacker_sites WHERE id = :id");
        $stmt->execute([':id' => $siteId]);
        if (!$stmt->fetch()) {
            $error = "Seçilen site bulunamadı.";
        } else {
            // Benzersiz API key oluştur
            $apiKey = bin2hex(random_bytes(32));
            $apiSecret = bin2hex(random_bytes(32));
            
            $stmt = $pdo->prepare("
                INSERT INTO cloacker_api_keys (site_id, api_key, api_secret, name, created_by, created_at)
                VALUES (:site_id, :api_key, :api_secret, :name, :created_by, NOW())
            ");
            $stmt->execute([
                ':site_id' => $siteId,
                ':api_key' => $apiKey,
                ':api_secret' => $apiSecret,
                ':name' => $name ?: null,
                ':created_by' => $_SESSION['admin_id']
            ]);
            
            // Site bilgilerini al
            $siteStmt = $pdo->prepare("SELECT name, domain, normal_url, fake_url FROM cloacker_sites WHERE id = :id");
            $siteStmt->execute([':id' => $siteId]);
            $siteInfo = $siteStmt->fetch();
            
            $success = "API anahtarı başarıyla oluşturuldu!";
            $_SESSION['new_api_key'] = $apiKey;
            $_SESSION['new_api_site'] = $siteInfo;
        }
    }
}

// API Key silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_key'])) {
    requireCsrfToken();
    
    $keyId = (int)($_POST['key_id'] ?? 0);
    if ($keyId > 0) {
        $stmt = $pdo->prepare("DELETE FROM cloacker_api_keys WHERE id = :id");
        $stmt->execute([':id' => $keyId]);
        $success = "API anahtarı silindi.";
    }
}

// API Key aktif/pasif
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_key'])) {
    requireCsrfToken();
    
    $keyId = (int)($_POST['key_id'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 0);
    
    if ($keyId > 0) {
        $stmt = $pdo->prepare("UPDATE cloacker_api_keys SET is_active = :active WHERE id = :id");
        $stmt->execute([':active' => $isActive, ':id' => $keyId]);
        $success = "API anahtarı durumu güncellendi.";
    }
}

// Tüm API keyleri listele
$stmt = $pdo->query("
    SELECT 
        k.id, k.api_key, k.name, k.is_active, k.last_used, k.created_at,
        s.name AS site_name, s.domain,
        a.username AS created_by_username
    FROM cloacker_api_keys k
    LEFT JOIN cloacker_sites s ON k.site_id = s.id
    LEFT JOIN cloacker_admins a ON k.created_by = a.id
    ORDER BY k.created_at DESC
");
$apiKeys = $stmt->fetchAll();

// Siteler listesi (yeni key oluşturma için)
$stmt = $pdo->query("SELECT id, name, domain FROM cloacker_sites WHERE is_active = 1 ORDER BY name");
$sites = $stmt->fetchAll();

render_admin_layout_start('API Anahtarları', 'api_keys');
?>

<?php if($error): ?>
    <div class="mb-4 p-4 rounded-lg glass-card border border-red-500/30 text-red-400"><?=htmlspecialchars($error)?></div>
<?php endif; ?>
<?php if($success): ?>
    <div class="mb-4 p-4 rounded-lg glass-card border border-cyan-500/30">
        <div class="flex items-center justify-between mb-3">
            <p class="text-cyan-400 font-semibold"><?=$success?></p>
        </div>
        <?php if(isset($_SESSION['new_api_key'])): 
            $newKey = $_SESSION['new_api_key'];
            $newSite = $_SESSION['new_api_site'] ?? [];
            $apiUrl = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/api/cloaker_api.php';
            unset($_SESSION['new_api_key'], $_SESSION['new_api_site']);
        ?>
            <div class="glass-card p-4 rounded-lg border border-cyan-500/30">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2 text-gray-300">API Anahtarı:</label>
                    <div class="flex gap-2">
                        <input type="text" id="api-key-display" value="<?=htmlspecialchars($newKey)?>" readonly 
                               class="flex-1 px-3 py-2 glass-card border border-cyan-500/20 rounded-lg font-mono text-sm text-cyan-300">
                        <button onclick="copyToClipboard('api-key-display', 'api-key-btn')" id="api-key-btn"
                                class="copy-btn inline-flex items-center gap-2 px-3 py-2 text-sm font-medium glass-card border border-cyan-500/20 rounded-lg text-cyan-400 hover:border-cyan-500/40 hover:text-cyan-300 transition">
                            <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24">
                                <path d="M9 9h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/>
                                <path d="M7 5h8a2 2 0 0 1 2 2v1"/>
                            </svg>
                            <span>Kopyala</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">⚠️ Bu anahtar sadece bir kez gösterilir, güvenli bir yerde saklayın!</p>
                </div>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-300">PHP Kodu (Kısa):</label>
                            <div class="flex gap-2">
                                <textarea id="php-code" readonly rows="12" class="flex-1 px-3 py-2 glass-card border border-cyan-500/20 rounded-lg font-mono text-xs text-gray-300"><?php
$code = '<?php
$apiKey = \'' . htmlspecialchars($newKey) . '\';
$apiUrl = \'' . htmlspecialchars($apiUrl) . '\';

function getIP() {
    foreach([\'HTTP_CF_CONNECTING_IP\',\'HTTP_X_REAL_IP\',\'HTTP_X_FORWARDED_FOR\'] as $h) {
        if(!empty($_SERVER[$h])) {
            $ip = trim(explode(\',\',$_SERVER[$h])[0]);
            if(filter_var($ip,FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return $_SERVER[\'REMOTE_ADDR\']??null;
}

$ch = curl_init($apiUrl);
curl_setopt_array($ch,[
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_POST=>true,
    CURLOPT_HTTPHEADER=>[
        \'X-API-Key:\'.$apiKey,
        \'X-Visitor-IP:\'.getIP(),
        \'X-Visitor-UA:\'.($_SERVER[\'HTTP_USER_AGENT\']??\'\')
    ]
]);

$data = json_decode(curl_exec($ch),true);
if($data[\'status\']===\'ok\') {
    header(\'Location:\'.$data[\'redirect_url\']);
    exit;
}
?>';
echo htmlspecialchars($code);
?></textarea>
                                <button onclick="copyToClipboard('php-code', 'php-btn')" id="php-btn"
                                        class="copy-btn inline-flex items-center gap-2 px-3 py-2 text-sm font-medium glass-card border border-cyan-500/20 rounded-lg text-cyan-400 hover:border-cyan-500/40 hover:text-cyan-300 transition">
                                    <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path d="M9 9h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/>
                                        <path d="M7 5h8a2 2 0 0 1 2 2v1"/>
                                    </svg>
                                    <span>Kopyala</span>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-300">JavaScript Kodu (Kısa):</label>
                            <div class="flex gap-2">
                                <textarea id="js-code" readonly rows="12" class="flex-1 px-3 py-2 glass-card border border-cyan-500/20 rounded-lg font-mono text-xs text-gray-300"><?php
$jsCode = '<script>
const API_KEY=\'' . htmlspecialchars($newKey) . '\';
const API_URL=\'' . htmlspecialchars($apiUrl) . '\';
fetch(API_URL,{
    method:\'POST\',
    headers:{\'X-API-Key\':API_KEY}
}).then(r=>r.json()).then(d=>{
    if(d.status===\'ok\')window.location.href=d.redirect_url;
});
</script>';
echo htmlspecialchars($jsCode);
?></textarea>
                                <button onclick="copyToClipboard('js-code', 'js-btn')" id="js-btn"
                                        class="copy-btn inline-flex items-center gap-2 px-3 py-2 text-sm font-medium glass-card border border-cyan-500/20 rounded-lg text-cyan-400 hover:border-cyan-500/40 hover:text-cyan-300 transition">
                                    <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path d="M9 9h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/>
                                        <path d="M7 5h8a2 2 0 0 1 2 2v1"/>
                                    </svg>
                                    <span>Kopyala</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2 text-gray-300">HTML Sayfasına Ekle (Tek Satır):</label>
                        <div class="flex gap-2">
                            <textarea id="html-code" readonly rows="3" class="flex-1 px-3 py-2 glass-card border border-cyan-500/20 rounded-lg font-mono text-xs text-gray-300"><?php
$htmlCode = '<script>const API_KEY=\'' . htmlspecialchars($newKey) . '\';const API_URL=\'' . htmlspecialchars($apiUrl) . '\';fetch(API_URL,{method:\'POST\',headers:{\'X-API-Key\':API_KEY}}).then(r=>r.json()).then(d=>{if(d.status===\'ok\')window.location.href=d.redirect_url;});</script>';
echo htmlspecialchars($htmlCode);
?></textarea>
                            <button onclick="copyToClipboard('html-code', 'html-btn')" id="html-btn"
                                    class="copy-btn inline-flex items-center gap-2 px-3 py-2 text-sm font-medium glass-card border border-cyan-500/20 rounded-lg text-cyan-400 hover:border-cyan-500/40 hover:text-cyan-300 transition">
                                <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path d="M9 9h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/>
                                    <path d="M7 5h8a2 2 0 0 1 2 2v1"/>
                                </svg>
                                <span>Kopyala</span>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Bu kodu HTML sayfanızın &lt;/head&gt; veya &lt;/body&gt; etiketinden önce ekleyin</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="space-y-6 mb-8">
    <!-- Yeni API Key Oluştur -->
    <div class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <h3 class="text-xl font-heading font-semibold text-white mb-4">Yeni API Anahtarı</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
            <input type="hidden" name="create_key" value="1">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Site</label>
                    <select name="site_id" required class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white focus:border-cyan-500/50 focus:outline-none transition">
                        <option value="">Seçiniz...</option>
                        <?php foreach ($sites as $site): ?>
                            <option value="<?=$site['id']?>"><?=htmlspecialchars($site['name'])?> (<?=htmlspecialchars($site['domain'])?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">İsim (Opsiyonel)</label>
                    <input type="text" name="name" placeholder="Örn: Production API Key"
                           class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white placeholder-gray-500 focus:border-cyan-500/50 focus:outline-none transition">
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700 text-white py-3 rounded-lg transition font-medium neon-glow-cyan">
                    API Anahtarı Oluştur
                </button>
            </div>
        </form>
    </div>
    
    <!-- API Key Listesi -->
    <div class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <h3 class="text-xl font-heading font-semibold text-white mb-4">Mevcut API Anahtarları</h3>
        
        <?php if (empty($apiKeys)): ?>
            <p class="text-gray-400">Henüz API anahtarı oluşturulmamış.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-400 border-b border-gray-700">
                        <tr>
                            <th class="py-3 font-medium">API Key</th>
                            <th class="py-3 font-medium">Site</th>
                            <th class="py-3 font-medium">İsim</th>
                            <th class="py-3 font-medium">Durum</th>
                            <th class="py-3 font-medium">Son Kullanım</th>
                            <th class="py-3 font-medium">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        <?php foreach ($apiKeys as $key): ?>
                            <tr class="hover:bg-cyan-500/5 transition">
                                <td class="py-3 font-mono text-xs">
                                    <div class="flex items-center gap-2">
                                        <code class="glass-card px-3 py-1 rounded-lg border border-cyan-500/20 text-cyan-300"><?=htmlspecialchars(substr($key['api_key'], 0, 16))?>...</code>
                                        <button onclick="copyApiKey('<?=htmlspecialchars($key['api_key'])?>', this)" 
                                                class="copy-btn inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium glass-card border border-cyan-500/20 text-cyan-400 hover:border-cyan-500/40 hover:text-cyan-300 rounded-lg transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path d="M9 9h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/>
                                                <path d="M7 5h8a2 2 0 0 1 2 2v1"/>
                                            </svg>
                                            <span>Kopyala</span>
                                        </button>
                                    </div>
                                </td>
                                <td class="py-3 text-gray-300"><?=htmlspecialchars($key['site_name'] ?? 'N/A')?></td>
                                <td class="py-3 text-gray-300"><?=htmlspecialchars($key['name'] ?? '-')?></td>
                                <td class="py-3">
                                    <?php if ($key['is_active']): ?>
                                        <span class="px-3 py-1 text-xs rounded-lg bg-gradient-to-r from-cyan-500/20 to-cyan-400/20 border border-cyan-500/30 text-cyan-300 font-medium">Aktif</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 text-xs rounded-lg bg-gradient-to-r from-red-500/20 to-red-400/20 border border-red-500/30 text-red-300 font-medium">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-xs text-gray-400"><?=htmlspecialchars($key['last_used'] ?? 'Hiç')?></td>
                                <td class="py-3">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
                                        <input type="hidden" name="key_id" value="<?=$key['id']?>">
                                        <input type="hidden" name="is_active" value="<?=$key['is_active'] ? 0 : 1?>">
                                        <input type="hidden" name="toggle_key" value="1">
                                        <button type="submit" class="text-xs px-3 py-1 rounded-lg glass-card border border-gray-600/30 text-gray-400 hover:border-cyan-500/30 hover:text-cyan-400 transition">
                                            <?=$key['is_active'] ? 'Pasifleştir' : 'Aktifleştir'?>
                                        </button>
                                    </form>
                                    <form method="post" class="inline ml-2" onsubmit="return confirm('Bu API anahtarını silmek istediğinizden emin misiniz?');">
                                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
                                        <input type="hidden" name="key_id" value="<?=$key['id']?>">
                                        <input type="hidden" name="delete_key" value="1">
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

<!-- API Kullanım Dokümantasyonu -->
<div class="glass-card rounded-xl border border-cyan-500/20 p-6">
    <h3 class="text-xl font-heading font-semibold text-white mb-4">📚 API Kullanım Kılavuzu</h3>
    
    <div class="space-y-6 text-sm">
        <!-- Endpoint -->
        <div>
            <h4 class="font-semibold mb-2 text-cyan-400">1. Endpoint:</h4>
            <code class="glass-card px-3 py-2 rounded-lg block text-xs text-cyan-300 border border-cyan-500/20">POST https://yourdomain.com/api/cloaker_api.php</code>
        </div>
        
        <!-- Authentication -->
        <div>
            <h4 class="font-semibold mb-2 text-cyan-400">2. Authentication:</h4>
            <p class="mb-2 text-gray-400">API key'i 3 şekilde gönderebilirsiniz:</p>
            <div class="space-y-2">
                <code class="glass-card px-3 py-2 rounded-lg block text-xs text-cyan-300 border border-cyan-500/20">Header: X-API-Key: YOUR_API_KEY</code>
                <code class="glass-card px-3 py-2 rounded-lg block text-xs text-cyan-300 border border-cyan-500/20">GET: ?api_key=YOUR_API_KEY</code>
                <code class="glass-card px-3 py-2 rounded-lg block text-xs text-cyan-300 border border-cyan-500/20">POST Body: {"api_key": "YOUR_API_KEY"}</code>
            </div>
        </div>
        
        <!-- JavaScript Örneği -->
        <div>
            <h4 class="font-semibold mb-2 text-cyan-400">3. JavaScript Örneği (Basit):</h4>
            <pre class="glass-card p-3 rounded-lg overflow-x-auto text-xs border border-cyan-500/20"><code class="text-gray-300">const API_KEY = 'YOUR_API_KEY_HERE';
const API_URL = 'https://yourdomain.com/api/cloaker_api.php';

fetch(API_URL, {
    method: 'POST',
    headers: {
        'X-API-Key': API_KEY,
        'Content-Type': 'application/json'
    }
})
.then(res => res.json())
.then(data => {
    if (data.status === 'ok') {
        window.location.href = data.redirect_url;
    }
})
.catch(err => console.error('Error:', err));</code></pre>
        </div>
        
        <!-- PHP Örneği -->
        <div>
            <h4 class="font-semibold mb-2 text-cyan-400">4. PHP Örneği:</h4>
            <pre class="glass-card p-3 rounded-lg overflow-x-auto text-xs border border-cyan-500/20"><code class="text-gray-300">$apiKey = 'YOUR_API_KEY_HERE';
$apiUrl = 'https://yourdomain.com/api/cloaker_api.php';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$data = json_decode($response, true);

if ($data['status'] === 'ok') {
    header('Location: ' . $data['redirect_url']);
    exit;
}</code></pre>
        </div>
        
        <!-- Response Formatı -->
        <div>
            <h4 class="font-semibold mb-2 text-cyan-400">5. Response Formatı:</h4>
            <pre class="glass-card p-3 rounded-lg overflow-x-auto text-xs border border-cyan-500/20"><code class="text-gray-300">{
    "status": "ok",
    "allowed": true,
    "redirect_url": "https://normal-site.com",
    "detection": {
        "is_bot": false,
        "is_proxy": false,
        "bot_confidence": 15.5,
        "fingerprint_score": 2
    },
    "visitor": {
        "ip": "192.168.1.1",
        "country": "TR",
        "os": "windows",
        "browser": "Chrome"
    }
}</code></pre>
        </div>
        
        <!-- Hızlı Başlangıç -->
        <div class="glass-card p-4 rounded-lg border border-cyan-500/30">
            <h4 class="font-semibold mb-2 text-cyan-400">🚀 Hızlı Başlangıç:</h4>
            <ol class="list-decimal list-inside space-y-1 text-gray-300">
                <li>Yukarıdan bir API key oluşturun</li>
                <li>API key'i kopyalayın</li>
                <li>HTML sayfanıza JavaScript kodunu ekleyin</li>
                <li>API_KEY değişkenine kendi key'inizi yazın</li>
                <li>API_URL'i kendi domain'inizle değiştirin</li>
                <li>Test edin!</li>
            </ol>
        </div>
        
        <!-- Detaylı Dokümantasyon -->
        <div class="mt-4">
            <a href="../API_KULLANIM_KILAVUZU.md" target="_blank" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-cyan-500 to-cyan-600 text-white rounded-lg hover:from-cyan-600 hover:to-cyan-700 transition font-medium neon-glow-cyan">
                📖 Detaylı Kullanım Kılavuzunu Görüntüle
            </a>
        </div>
    </div>
</div>

<script>
const successContent = `
    <span class="inline-flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
            <path d="M5 13l4 4L19 7"></path>
        </svg>
        <span>Kopyalandı</span>
    </span>
`;

function flashCopySuccess(button) {
    if (!button) {
        return;
    }
    if (!button.dataset.originalHtml) {
        button.dataset.originalHtml = button.innerHTML;
    }
    if (!button.dataset.originalClass) {
        button.dataset.originalClass = button.className;
    }

    button.innerHTML = successContent;
    button.className = button.dataset.originalClass + ' bg-emerald-500 border-emerald-500 text-white hover:bg-emerald-600 dark:bg-emerald-600 dark:text-white';

    clearTimeout(button._copyResetTimer);
    button._copyResetTimer = setTimeout(() => {
        button.innerHTML = button.dataset.originalHtml;
        button.className = button.dataset.originalClass;
    }, 2000);
}

function legacyCopy(text) {
    return new Promise((resolve, reject) => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        textarea.setAttribute('readonly', '');
        document.body.appendChild(textarea);
        textarea.select();
        textarea.setSelectionRange(0, 99999);

        const succeeded = document.execCommand('copy');
        document.body.removeChild(textarea);
        succeeded ? resolve() : reject(new Error('execCommand failed'));
    });
}

async function writeToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(text);
    }
    return legacyCopy(text);
}

async function copyToClipboard(elementId, buttonId) {
    const element = document.getElementById(elementId);
    const button = document.getElementById(buttonId);
    if (!element || !button) {
        return;
    }
    const value = element.value ?? '';

    try {
        await writeToClipboard(value);
        flashCopySuccess(button);
    } catch (err) {
        console.error('Kopyalama hatası:', err);
        alert('Kopyalama başarısız. Lütfen manuel olarak kopyalayın.');
    }
}

async function copyApiKey(apiKey, button) {
    try {
        await writeToClipboard(apiKey);
        flashCopySuccess(button);
    } catch (err) {
        console.error('Kopyalama hatası:', err);
        alert('Kopyalama başarısız. Lütfen manuel olarak kopyalayın.');
    }
}
</script>

<?php render_admin_layout_end(); ?>

