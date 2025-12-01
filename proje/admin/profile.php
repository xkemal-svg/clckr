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
$hasFullName = false;
$hasPhone = false;

// Profil bilgilerini al
try {
    // Önce sütunların var olup olmadığını kontrol et
    $hasFullName = false;
    $hasPhone = false;
    try {
        $pdo->query("SELECT full_name FROM cloacker_admins LIMIT 1");
        $hasFullName = true;
    } catch (PDOException $e) {}
    
    try {
        $pdo->query("SELECT phone FROM cloacker_admins LIMIT 1");
        $hasPhone = true;
    } catch (PDOException $e) {}
    
    if ($hasFullName && $hasPhone) {
        $stmt = $pdo->prepare("SELECT id, username, email, full_name, phone, created_at, last_login FROM cloacker_admins WHERE id = :id");
    } else {
        $stmt = $pdo->prepare("SELECT id, username, email, created_at, last_login FROM cloacker_admins WHERE id = :id");
    }
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        header("Location: dashboard.php");
        exit();
    }
    
    // Eksik sütunları null olarak ekle
    if (!isset($admin['full_name'])) {
        $admin['full_name'] = null;
    }
    if (!isset($admin['phone'])) {
        $admin['phone'] = null;
    }
    
    // Tarih formatlarını düzenle
    if (!empty($admin['created_at'])) {
        $admin['created_at'] = date('d.m.Y H:i', strtotime($admin['created_at']));
    } else {
        $admin['created_at'] = 'Bilinmiyor';
    }
    
    if (!empty($admin['last_login'])) {
        $admin['last_login'] = date('d.m.Y H:i', strtotime($admin['last_login']));
    } else {
        $admin['last_login'] = 'Hiç';
    }
} catch (PDOException $e) {
    error_log("Profile veri çekme hatası: " . $e->getMessage());
    header("Location: dashboard.php");
    exit();
}

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    requireCsrfToken();
    
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Geçerli bir e-posta adresi giriniz.";
    } else {
        // Email kontrolü (başka admin kullanıyor mu?)
        $stmt = $pdo->prepare("SELECT id FROM cloacker_admins WHERE email = :email AND id != :id");
        $stmt->execute([':email' => $email, ':id' => $_SESSION['admin_id']]);
        if ($stmt->fetch()) {
            $error = "Bu e-posta adresi başka bir admin tarafından kullanılıyor.";
        } else {
            // Önce sütunların var olup olmadığını kontrol et
            $hasFullName = false;
            $hasPhone = false;
            try {
                $pdo->query("SELECT full_name FROM cloacker_admins LIMIT 1");
                $hasFullName = true;
            } catch (PDOException $e) {}
            
            try {
                $pdo->query("SELECT phone FROM cloacker_admins LIMIT 1");
                $hasPhone = true;
            } catch (PDOException $e) {}
            
            if ($hasFullName && $hasPhone) {
                $stmt = $pdo->prepare("UPDATE cloacker_admins SET full_name = :full_name, email = :email, phone = :phone WHERE id = :id");
                $stmt->execute([
                    ':full_name' => $fullName ?: null,
                    ':email' => $email,
                    ':phone' => $phone ?: null,
                    ':id' => $_SESSION['admin_id']
                ]);
            } else {
                // Sadece email güncelle
                $stmt = $pdo->prepare("UPDATE cloacker_admins SET email = :email WHERE id = :id");
                $stmt->execute([
                    ':email' => $email,
                    ':id' => $_SESSION['admin_id']
                ]);
            }
            $success = "Profil bilgileriniz güncellendi.";
            // Yeniden yükle
            if ($hasFullName && $hasPhone) {
                $stmt = $pdo->prepare("SELECT id, username, email, full_name, phone, created_at, last_login FROM cloacker_admins WHERE id = :id");
            } else {
                $stmt = $pdo->prepare("SELECT id, username, email, created_at, last_login FROM cloacker_admins WHERE id = :id");
            }
            $stmt->execute([':id' => $_SESSION['admin_id']]);
            $admin = $stmt->fetch();
            
            // Eksik sütunları null olarak ekle
            if (!isset($admin['full_name'])) {
                $admin['full_name'] = null;
            }
            if (!isset($admin['phone'])) {
                $admin['phone'] = null;
            }
            
            // Tarih formatlarını düzenle
            if (!empty($admin['created_at'])) {
                $admin['created_at'] = date('d.m.Y H:i', strtotime($admin['created_at']));
            } else {
                $admin['created_at'] = 'Bilinmiyor';
            }
            
            if (!empty($admin['last_login'])) {
                $admin['last_login'] = date('d.m.Y H:i', strtotime($admin['last_login']));
            } else {
                $admin['last_login'] = 'Hiç';
            }
        }
    }
}

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    requireCsrfToken();
    
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $newPassConfirm = $_POST['new_password_confirm'] ?? '';
    
    if (empty($currentPass) || empty($newPass) || empty($newPassConfirm)) {
        $error = "Tüm şifre alanları doldurulmalıdır.";
    } elseif ($newPass !== $newPassConfirm) {
        $error = "Yeni şifreler eşleşmiyor.";
    } elseif (strlen($newPass) < 8) {
        $error = "Yeni şifre en az 8 karakter olmalıdır.";
    } else {
        $stmt = $pdo->prepare("SELECT password_hash FROM cloacker_admins WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['admin_id']]);
        $row = $stmt->fetch();
        
        if (!$row || !password_verify($currentPass, $row['password_hash'])) {
            $error = "Mevcut şifre yanlış.";
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE cloacker_admins SET password_hash = :hash WHERE id = :id");
            $stmt->execute([':hash' => $newHash, ':id' => $_SESSION['admin_id']]);
            $success = "Şifreniz başarıyla değiştirildi.";
        }
    }
}

render_admin_layout_start('Profilim', 'profile');
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Profil Bilgileri -->
    <div class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <h3 class="text-xl font-heading font-semibold text-white mb-4">Kişisel Bilgiler</h3>
        
        <?php if($error): ?>
            <div class="mb-4 p-4 rounded-lg glass-card border border-red-500/30 text-red-400"><?=htmlspecialchars($error)?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="mb-4 p-4 rounded-lg glass-card border border-cyan-500/30 text-cyan-400"><?=htmlspecialchars($success)?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
            <input type="hidden" name="update_profile" value="1">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Kullanıcı Adı</label>
                    <input type="text" value="<?=htmlspecialchars($admin['username'])?>" disabled
                           class="w-full p-3 rounded-lg glass-card border border-gray-600/30 bg-gray-900/50 text-gray-500">
                    <p class="text-xs text-gray-400 mt-1">Kullanıcı adı değiştirilemez</p>
                </div>
                
                <?php
                // full_name sütunu var mı kontrol et
                $hasFullName = false;
                try {
                    $pdo->query("SELECT full_name FROM cloacker_admins LIMIT 1");
                    $hasFullName = true;
                } catch (PDOException $e) {}
                ?>
                
                <?php if ($hasFullName): ?>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Ad Soyad</label>
                    <input type="text" name="full_name" value="<?=htmlspecialchars($admin['full_name'] ?? '')?>"
                           class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white focus:border-cyan-500/50 focus:outline-none transition">
                </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">E-posta</label>
                    <input type="email" name="email" value="<?=htmlspecialchars($admin['email'] ?? '')?>" required
                           class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white focus:border-cyan-500/50 focus:outline-none transition">
                </div>
                
                <?php
                // phone sütunu var mı kontrol et
                $hasPhone = false;
                try {
                    $pdo->query("SELECT phone FROM cloacker_admins LIMIT 1");
                    $hasPhone = true;
                } catch (PDOException $e) {}
                ?>
                
                <?php if ($hasPhone): ?>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Telefon</label>
                    <input type="text" name="phone" value="<?=htmlspecialchars($admin['phone'] ?? '')?>"
                           class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white focus:border-cyan-500/50 focus:outline-none transition">
                </div>
                <?php endif; ?>
                
                <button type="submit" class="w-full bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700 text-white py-3 rounded-lg transition font-medium neon-glow-cyan">
                    Bilgileri Güncelle
                </button>
            </div>
        </form>
    </div>
    
    <!-- Şifre Değiştirme -->
    <div class="glass-card rounded-xl border border-cyan-500/20 p-6">
        <h3 class="text-xl font-heading font-semibold text-white mb-4">Şifre Değiştir</h3>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
            <input type="hidden" name="change_password" value="1">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Mevcut Şifre</label>
                    <input type="password" name="current_password" required
                           class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white focus:border-cyan-500/50 focus:outline-none transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Yeni Şifre</label>
                    <input type="password" name="new_password" required minlength="8"
                           class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white focus:border-cyan-500/50 focus:outline-none transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Yeni Şifre (Tekrar)</label>
                    <input type="password" name="new_password_confirm" required minlength="8"
                           class="w-full p-3 rounded-lg glass-card border border-cyan-500/20 bg-gray-900/50 text-white focus:border-cyan-500/50 focus:outline-none transition">
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700 text-white py-3 rounded-lg transition font-medium neon-glow-cyan">
                    Şifreyi Değiştir
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hesap Bilgileri -->
<div class="mt-6 glass-card rounded-xl border border-cyan-500/20 p-6">
    <h3 class="text-xl font-heading font-semibold text-white mb-4">Hesap Bilgileri</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div class="p-4 rounded-lg glass-card border border-cyan-500/10">
            <p class="text-gray-400 mb-2">Hesap Oluşturulma</p>
            <p class="font-semibold text-white text-lg"><?=htmlspecialchars($admin['created_at'] ?? 'Bilinmiyor')?></p>
        </div>
        <div class="p-4 rounded-lg glass-card border border-cyan-500/10">
            <p class="text-gray-400 mb-2">Son Giriş</p>
            <p class="font-semibold text-white text-lg"><?=htmlspecialchars($admin['last_login'] ?? 'Hiç')?></p>
        </div>
    </div>
</div>

<?php render_admin_layout_end(); ?>

