<?php
require_once __DIR__ . '/includes/admin_guard.php';
require_once __DIR__ . '/includes/layout.php';

enforceAdminSession();

$pdo = DB::connect();

$error = '';
$success = '';
$passError = '';
$passSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
}

// Yeni admin ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$username || !$email || !$password) {
        $error = "Kullanıcı adı, e-posta ve şifre zorunludur.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Geçerli bir e-posta giriniz.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cloacker_admins WHERE username=:u OR email=:e");
        $stmt->execute([':u'=>$username, ':e'=>$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Kullanıcı adı veya e-posta zaten kayıtlı.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // full_name ve phone sütunları varsa ekle
            try {
                $pdo->query("SELECT full_name FROM cloacker_admins LIMIT 1");
                $stmt = $pdo->prepare("INSERT INTO cloacker_admins (username, password_hash, email, full_name, phone) VALUES (:u, :p, :e, :fn, :ph)");
                $stmt->execute([':u'=>$username, ':p'=>$hash, ':e'=>$email, ':fn'=>$fullName ?: null, ':ph'=>$phone ?: null]);
            } catch (PDOException $e) {
                // Eski format (geriye dönük uyumluluk)
                $stmt = $pdo->prepare("INSERT INTO cloacker_admins (username, password_hash, email) VALUES (:u, :p, :e)");
                $stmt->execute([':u'=>$username, ':p'=>$hash, ':e'=>$email]);
            }
            $success = "Yeni admin eklendi.";
        }
    }
}

// Admin silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    $del = (int)$_POST['delete_admin_id'];
    if ($del !== $_SESSION['admin_id']) {
        $stmt = $pdo->prepare("DELETE FROM cloacker_admins WHERE id=:id");
        $stmt->execute([':id'=>$del]);
        $success = "Admin silindi.";
    } else {
        $error = "Kendinizi silemezsiniz.";
    }
}

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $newPassConfirm = $_POST['new_password_confirm'] ?? '';

    if (!$currentPass || !$newPass || !$newPassConfirm) {
        $passError = "Tüm şifre alanları doldurulmalıdır.";
    } elseif ($newPass !== $newPassConfirm) {
        $passError = "Yeni şifreler eşleşmiyor.";
    } elseif (strlen($newPass) < 6) {
        $passError = "Yeni şifre en az 6 karakter olmalıdır.";
    } else {
        $stmt = $pdo->prepare("SELECT password_hash FROM cloacker_admins WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($currentPass, $admin['password_hash'])) {
            $passError = "Mevcut şifre yanlış.";
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE cloacker_admins SET password_hash = :hash WHERE id = :id");
            $stmt->execute([':hash' => $newHash, ':id' => $_SESSION['admin_id']]);
            $passSuccess = "Şifreniz başarıyla değiştirildi.";
        }
    }
}

// Mevcut adminler
$stmt = $pdo->query("SELECT id, username, email, last_login FROM cloacker_admins ORDER BY id ASC");
$admins = $stmt->fetchAll();

render_admin_layout_start('Adminleri Yönet', 'manage_admins');
?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        <!-- Sol: Yeni admin ekle -->
        <div>
            <?php if($error): ?>
                <div class="mb-4 p-3 rounded glass-card border border-red-500/30 text-red-400"><?=htmlspecialchars($error)?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="mb-4 p-3 rounded glass-card border border-cyan-500/30 text-cyan-400"><?=htmlspecialchars($success)?></div>
            <?php endif; ?>

            <form method="post" class="glass-card p-6 rounded-xl border border-cyan-500/20">
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
                <h2 class="text-xl font-heading font-semibold text-white mb-4">Yeni Admin Ekle</h2>

                <label class="block text-sm font-medium mb-1">Kullanıcı Adı</label>
                <input type="text" name="username" required class="w-full p-2 rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white mb-3">

                <label class="block text-sm font-medium mb-1">E-posta</label>
                <input type="email" name="email" required class="w-full p-2 rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white mb-3">

                <label class="block text-sm font-medium mb-1">Ad Soyad (Opsiyonel)</label>
                <input type="text" name="full_name" class="w-full p-2 rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white mb-3">

                <label class="block text-sm font-medium mb-1">Telefon (Opsiyonel)</label>
                <input type="text" name="phone" class="w-full p-2 rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white mb-3">

                <label class="block text-sm font-medium mb-1">Şifre</label>
                <input type="password" name="password" required minlength="6" class="w-full p-2 rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white mb-4">

                <button type="submit" name="add_admin" class="w-full bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700 neon-glow-cyan text-white py-2 rounded-lg">Ekle</button>
            </form>
        </div>

        <!-- Sağ: Şifre değiştir -->
        <div>
            <form method="post" class="glass-card p-6 rounded-xl border border-cyan-500/20">
                <h2 class="text-xl font-heading font-semibold text-white mb-4">Şifre Değiştir</h2>

                <?php if($passError): ?>
                    <div class="mb-3 p-2 rounded glass-card border border-red-500/30 text-red-400"><?=htmlspecialchars($passError)?></div>
                <?php endif; ?>
                <?php if($passSuccess): ?>
                    <div class="mb-3 p-2 rounded glass-card border border-cyan-500/30 text-cyan-400"><?=htmlspecialchars($passSuccess)?></div>
                <?php endif; ?>

                <input type="hidden" name="change_password" value="1" />
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">

                <label class="block text-sm font-medium mb-1">Mevcut Şifre</label>
                <input type="password" name="current_password" required class="w-full p-2 rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white mb-3">

                <label class="block text-sm font-medium mb-1">Yeni Şifre</label>
                <input type="password" name="new_password" required minlength="6" class="w-full p-2 rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white mb-3">

                <label class="block text-sm font-medium mb-1">Yeni Şifre (Tekrar)</label>
                <input type="password" name="new_password_confirm" required minlength="6" class="w-full p-2 rounded glass-card border border-cyan-500/20 bg-gray-900/50 text-white mb-4">

                <button type="submit" class="w-full bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700 neon-glow-cyan text-white py-2 rounded-lg">Şifreyi Değiştir</button>
            </form>
        </div>
    </div>

    <!-- Admin listesi: Tam genişlik -->
    <div class="glass-card p-6 rounded-xl border border-cyan-500/20 overflow-x-auto">
        <h2 class="text-xl font-heading font-semibold text-white mb-4">Kayıtlı Adminler</h2>
        <table class="min-w-full table-auto">
            <thead>
                <tr class="text-left text-sm uppercase text-gray-400">
                    <th class="px-3 py-2">ID</th>
                    <th class="px-3 py-2">Kullanıcı Adı</th>
                    <th class="px-3 py-2">E-posta</th>
                    <th class="px-3 py-2">Son Giriş</th>
                    <th class="px-3 py-2">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($admins as $a): ?>
                    <tr class="border-t border-gray-700">
                        <td class="px-3 py-3"><?=intval($a['id'])?></td>
                        <td class="px-3 py-3"><?=htmlspecialchars($a['username'])?></td>
                        <td class="px-3 py-3"><?=htmlspecialchars($a['email'])?></td>
                        <td class="px-3 py-3"><?=htmlspecialchars($a['last_login'] ?? 'Hiç')?></td>
                        <td class="px-3 py-3">
                            <?php if ($a['id'] != $_SESSION['admin_id']): ?>
                                <form method="post" class="inline" onsubmit="return confirm('Bu admini silmek istediğinize emin misiniz?')">
                                    <input type="hidden" name="delete_admin" value="1">
                                    <input type="hidden" name="delete_admin_id" value="<?=intval($a['id'])?>">
                                    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
                                    <button type="submit" class="text-red-400 hover:text-red-300 hover:underline">Sil</button>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php render_admin_layout_end(); ?>
