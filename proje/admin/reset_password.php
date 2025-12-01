<?php
require_once '../cloacker.php';

use \PDO;

$error = '';
$success = '';
$showForm = false;
$token = $_GET['token'] ?? '';

if (!$token) {
    $error = "Geçersiz veya eksik token.";
} else {
    $pdo = DB::connect();
    $stmt = $pdo->prepare("SELECT pr.admin_id, pr.expires_at, a.username 
        FROM cloacker_password_resets pr 
        JOIN cloacker_admins a ON pr.admin_id = a.id 
        WHERE pr.token = :token");
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $error = "Geçersiz token.";
    } elseif (strtotime($row['expires_at']) < time()) {
        $error = "Token süresi dolmuş.";
    } else {
        $showForm = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            if (strlen($password) < 6) {
                $error = "Şifre en az 6 karakter olmalıdır.";
            } elseif ($password !== $password_confirm) {
                $error = "Şifreler uyuşmuyor.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE cloacker_admins SET password_hash = :hash WHERE id = :id");
                $stmt->execute([':hash' => $hash, ':id' => $row['admin_id']]);

                // Token'ı sil
                $stmt = $pdo->prepare("DELETE FROM cloacker_password_resets WHERE admin_id = :id");
                $stmt->execute([':id' => $row['admin_id']]);

                $success = "Şifreniz başarıyla güncellendi. <a href='index.php' class='text-blue-600 underline'>Giriş yapabilirsiniz.</a>";
                $showForm = false;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Şifre Sıfırlama</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { darkMode: 'class' };
</script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex items-center justify-center">

<div class="max-w-md w-full bg-white dark:bg-gray-800 p-8 rounded-xl shadow">
    <h2 class="text-2xl font-semibold mb-6 text-center">Şifre Sıfırlama</h2>

    <?php if ($error): ?>
        <div class="bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-300 p-3 rounded mb-4"><?=htmlspecialchars($error)?></div>
    <?php elseif ($success): ?>
        <div class="bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-300 p-3 rounded mb-4"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($showForm): ?>
    <form method="post" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
        <label class="block font-medium">Yeni Şifre</label>
        <input type="password" name="password" required class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100" />
        
        <label class="block font-medium">Yeni Şifre (Tekrar)</label>
        <input type="password" name="password_confirm" required class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100" />
        
        <button type="submit" class="w-full bg-blue-600 dark:bg-blue-700 text-white py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-800 transition">Şifreyi Güncelle</button>
    </form>
    <?php endif; ?>
</div>

</body>
</html>
