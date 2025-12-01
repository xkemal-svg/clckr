<?php
require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/layout.php';

enforceAdminSession();

// Logout kontrolü
if (isset($_GET['logout'])) {
    logoutAdmin();
}

$pdo = DB::connect();

$error = '';
$success = '';
$editBot = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
}

// Bot ekleme / düzenleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bot_name'])) {
    $botName = trim($_POST['bot_name'] ?? '');
    $userAgent = trim($_POST['user_agent'] ?? '');
    $targetUrl = trim($_POST['target_url'] ?? '');
    $delayMs = intval($_POST['delay_ms'] ?? 5000);
    $active = isset($_POST['active']) ? (int)$_POST['active'] : 0;
    $botId = isset($_POST['bot_id']) ? (int)$_POST['bot_id'] : 0;

    if (!$botName || !$userAgent || !$targetUrl) {
        $error = "Tüm alanlar zorunludur.";
    } elseif (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
        $error = "Geçerli bir hedef URL giriniz.";
    } elseif ($delayMs < 100) {
        $error = "Delay ms en az 100 olmalıdır.";
    } elseif ($active !== 0 && $active !== 1) {
        $error = "Geçersiz aktiflik değeri.";
    } else {
        if ($botId > 0) {
            $stmt = $pdo->prepare("UPDATE cloacker_bots SET bot_name=:bot_name, user_agent=:user_agent, target_url=:target_url, delay_ms=:delay_ms, active=:active WHERE id=:id");
            $stmt->execute([
                ':bot_name' => $botName,
                ':user_agent' => $userAgent,
                ':target_url' => $targetUrl,
                ':delay_ms' => $delayMs,
                ':active' => $active,
                ':id' => $botId
            ]);
            $success = "Bot güncellendi.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO cloacker_bots (bot_name, user_agent, target_url, delay_ms, active) VALUES (:bot_name, :user_agent, :target_url, :delay_ms, :active)");
            $stmt->execute([
                ':bot_name' => $botName,
                ':user_agent' => $userAgent,
                ':target_url' => $targetUrl,
                ':delay_ms' => $delayMs,
                ':active' => $active
            ]);
            $success = "Yeni bot eklendi.";
        }
    }
}

// Bot silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_bot_id'])) {
    $delId = (int)$_POST['delete_bot_id'];
    $stmt = $pdo->prepare("DELETE FROM cloacker_bots WHERE id=:id");
    $stmt->execute([':id' => $delId]);
    $success = "Bot silindi.";
}

// Bot listesi
$stmt = $pdo->query("SELECT * FROM cloacker_bots ORDER BY created_at DESC");
$bots = $stmt->fetchAll();

// Edit form yükleme
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM cloacker_bots WHERE id=:id");
    $stmt->execute([':id' => $editId]);
    $editBot = $stmt->fetch();
}

// Örnek User Agents
$userAgents = [
    'Googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    'Bingbot' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
    'Facebook External Hit' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'Twitterbot' => 'Twitterbot/1.0',
    'Discordbot' => 'Discordbot/2.0',
];

render_admin_layout_start('Botları Yönet', 'manage_bots');
?>

<script>
function toggleUserAgent(select) {
    var uaInput = document.getElementById('user_agent_manual');
    if (select.value === '__manual') {
        uaInput.style.display = 'block';
    } else {
        uaInput.style.display = 'none';
        uaInput.value = select.value;
    }
}

window.onload = function() {
    var select = document.getElementById('user_agent_select');
    if (select) toggleUserAgent(select);
}
</script>

    <?php if ($error): ?>
        <div class="mb-4 p-3 rounded glass-card border border-red-500/30 text-red-400"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="mb-4 p-3 rounded glass-card border border-cyan-500/30 text-cyan-400"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off" class="glass-card p-6 rounded-xl shadow mb-6 border border-cyan-500/20">
        <input type="hidden" name="bot_id" value="<?= $editBot['id'] ?? 0 ?>" />
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">

        <label class="block font-semibold mt-4">Bot Adı</label>
        <input type="text" name="bot_name" class="mt-1 w-full rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white p-2" required value="<?= htmlspecialchars($editBot['bot_name'] ?? '') ?>" />

        <label class="block font-semibold mt-4">User Agent Seçimi</label>
        <select name="user_agent_select" id="user_agent_select" onchange="toggleUserAgent(this)" class="mt-1 w-full rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white p-2">
            <?php foreach ($userAgents as $name => $agent): ?>
                <option value="<?=htmlspecialchars($agent)?>" <?= (isset($editBot['user_agent']) && $editBot['user_agent'] === $agent) ? 'selected' : '' ?>><?=htmlspecialchars($name)?></option>
            <?php endforeach; ?>
            <option value="__manual" <?= (isset($editBot['user_agent']) && !in_array($editBot['user_agent'], $userAgents)) ? 'selected' : '' ?>>Manuel Gir</option>
        </select>

        <input type="text" name="user_agent" id="user_agent_manual" placeholder="User Agent girin" style="margin-top:5px; display:none;"
            class="mt-2 w-full rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white p-2"
            value="<?= htmlspecialchars($editBot['user_agent'] ?? '') ?>" required />

        <label class="block font-semibold mt-4">Hedef URL</label>
        <input type="text" name="target_url" class="mt-1 w-full rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white p-2" required value="<?= htmlspecialchars($editBot['target_url'] ?? '') ?>" />

        <label class="block font-semibold mt-4">Delay (ms)</label>
        <input type="number" name="delay_ms" class="mt-1 w-full rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white p-2" required min="100" step="100" value="<?= htmlspecialchars($editBot['delay_ms'] ?? 5000) ?>" />

        <label class="block font-semibold mt-4">Aktif mi?</label>
        <select name="active" class="mt-1 w-full rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white p-2" required>
            <option value="1" <?= (isset($editBot['active']) && $editBot['active'] == 1) ? 'selected' : '' ?>>Evet</option>
            <option value="0" <?= (!isset($editBot['active']) || $editBot['active'] == 0) ? 'selected' : '' ?>>Hayır</option>
        </select>

        <button type="submit" class="mt-6 bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-600 hover:to-teal-600 text-white font-bold py-2 px-4 rounded-lg transition shadow-md"><?= $editBot ? 'Güncelle' : 'Ekle' ?></button>
        <?php if ($editBot): ?>
            <a href="manage_bots.php" class="ml-4 text-cyan-400 hover:text-cyan-300 hover:underline">İptal</a>
        <?php endif; ?>
    </form>

    <div class="glass-card p-6 rounded-xl shadow border border-cyan-500/20">
        <h2 class="text-xl font-bold mb-4">Mevcut Botlar</h2>
        <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse">
                <thead>
                    <tr class="bg-gradient-to-r from-cyan-500 to-teal-500 text-white">
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Bot Adı</th>
                    <th class="px-4 py-2">User Agent</th>
                    <th class="px-4 py-2">Hedef URL</th>
                    <th class="px-4 py-2">Delay (ms)</th>
                    <th class="px-4 py-2">Aktif</th>
                    <th class="px-4 py-2">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bots): foreach ($bots as $bot): ?>
                    <tr class="border-b border-gray-700">
                        <td class="px-4 py-2"><?= $bot['id'] ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($bot['bot_name']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($bot['user_agent']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($bot['target_url']) ?></td>
                        <td class="px-4 py-2"><?= (int)$bot['delay_ms'] ?></td>
                        <td class="px-4 py-2"><?= $bot['active'] ? 'Evet' : 'Hayır' ?></td>
                        <td class="px-4 py-2">
                            <a href="?edit=<?= $bot['id'] ?>" class="text-cyan-400 hover:text-cyan-300 hover:underline">Düzenle</a> |
                            <form method="post" class="inline" onsubmit="return confirm('Botu silmek istediğinize emin misiniz?')">
                                <input type="hidden" name="delete_bot_id" value="<?= $bot['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
                                <button type="submit" class="text-red-400 hover:text-red-300 hover:underline">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="px-4 py-2 text-gray-400">Kayıtlı bot yok.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php render_admin_layout_end(); ?>
