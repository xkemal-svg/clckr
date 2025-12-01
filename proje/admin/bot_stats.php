<?php
require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/layout.php';

enforceAdminSession();

$pdo = DB::connect();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
}

// Tekli silme
if (isset($_POST['delete_single_id']) && is_numeric($_POST['delete_single_id'])) {
    $deleteId = (int)$_POST['delete_single_id'];
    $stmt = $pdo->prepare("DELETE FROM cloacker_bot_stats WHERE id = :id");
    $stmt->execute([':id' => $deleteId]);
    $success = "Kayıt silindi.";
}

// Tümünü sil
if (isset($_POST['delete_all'])) {
    $pdo->exec("TRUNCATE TABLE cloacker_bot_stats");
    $success = "Tüm kayıtlar silindi.";
}

// Çoklu silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected'])) {
    $ids = $_POST['delete_ids'] ?? [];
    if (is_array($ids) && count($ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM cloacker_bot_stats WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $success = count($ids) . " kayıt silindi.";
    }
}

// Sayfalama
$perPage = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$totalRows = $pdo->query("SELECT COUNT(*) FROM cloacker_bot_stats")->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $pdo->prepare("SELECT bs.id, bs.bot_id, bs.visit_time, bs.redirect_type, bs.response_code, b.bot_name 
    FROM cloacker_bot_stats bs 
    LEFT JOIN cloacker_bots b ON bs.bot_id = b.id 
    ORDER BY bs.visit_time DESC 
    LIMIT :lim OFFSET :off");
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$stats = $stmt->fetchAll();

render_admin_layout_start('Bot İstatistikleri', 'bot_stats');
?>

<script>
// Onay kutuları
function confirmDeleteSingle() { return confirm('Bu kaydı silmek istediğinize emin misiniz?'); }
function confirmDeleteSelected() { return confirm('Seçili kayıtlar silinecek, emin misiniz?'); }
function confirmDeleteAll() { return confirm('Tüm kayıtlar silinecek, emin misiniz?'); }
function toggleSelectAll(source) {
    document.querySelectorAll('input[name="delete_ids[]"]').forEach(cb => cb.checked = source.checked);
}
</script>

    <?php if ($error): ?>
        <div class="mb-4 p-3 rounded glass-card border border-red-500/30 text-red-400"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="mb-4 p-3 rounded glass-card border border-cyan-500/30 text-cyan-400"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" onsubmit="return confirmDeleteSelected();" class="mb-6">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
        <div class="flex gap-4 mb-4">
            <button type="submit" name="delete_selected" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 neon-glow-red text-white font-bold py-2 px-4 rounded-lg transition">Seçilenleri Sil</button>
            <button type="submit" name="delete_all" onclick="return confirmDeleteAll();" class="glass-card border border-gray-600/30 hover:border-gray-500/50 text-white font-bold py-2 px-4 rounded-lg transition">Tümünü Sil</button>
        </div>

        <div class="overflow-x-auto glass-card rounded-xl shadow border border-cyan-500/20">
        <table class="min-w-full border-collapse">
            <thead class="bg-gradient-to-r from-cyan-500 to-teal-500 text-white">
                <tr>
                    <th class="px-4 py-2"><input type="checkbox" onclick="toggleSelectAll(this)"></th>
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Bot Adı</th>
                    <th class="px-4 py-2">Ziyaret Zamanı</th>
                    <th class="px-4 py-2">Yönlendirme Tipi</th>
                    <th class="px-4 py-2">HTTP Durum Kodu</th>
                    <th class="px-4 py-2">İşlem</th>
                </tr>
            </thead>
            <tbody class="glass-card text-white">
                <?php if ($stats): foreach ($stats as $row): ?>
                <tr class="border-b border-gray-700 hover:bg-cyan-500/5">
                    <td class="px-4 py-2"><input type="checkbox" name="delete_ids[]" value="<?=intval($row['id'])?>"></td>
                    <td class="px-4 py-2"><?=htmlspecialchars($row['id'])?></td>
                    <td class="px-4 py-2"><?=htmlspecialchars($row['bot_name'] ?? 'Bilinmiyor')?></td>
                    <td class="px-4 py-2"><?=htmlspecialchars($row['visit_time'])?></td>
                    <td class="px-4 py-2"><?=htmlspecialchars(ucfirst($row['redirect_type']))?></td>
                    <td class="px-4 py-2"><?=htmlspecialchars($row['response_code'])?></td>
                    <td class="px-4 py-2">
                        <button type="submit" name="delete_single_id" value="<?=intval($row['id'])?>" class="text-red-400 hover:text-red-300 hover:underline" onclick="return confirmDeleteSingle();">Sil</button>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="7" class="px-4 py-2 text-gray-400">Kayıt bulunamadı.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </form>

    <?php if ($totalPages > 1): ?>
        <div class="flex gap-2 mt-4">
            <?php for ($p=1; $p <= $totalPages; $p++): ?>
                <a href="?page=<?=$p?>" class="px-3 py-1 border rounded-lg transition <?php if($p==$page) echo 'bg-gradient-to-r from-cyan-500 to-teal-500 text-white border-transparent'; else echo 'border-gray-300 dark:border-gray-700 text-cyan-600 dark:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-gray-800'; ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

<?php render_admin_layout_end(); ?>
