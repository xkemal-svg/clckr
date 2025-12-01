<?php
require_once '../cloacker.php';

use \PDO;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Geçerli bir e-posta giriniz.";
    } else {
        sleep(1); // timing attack mitigation
        $pdo = DB::connect();
        $stmt = $pdo->prepare("SELECT id, username FROM cloacker_admins WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            // Token oluştur
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Önce varsa eski tokenları sil
            $stmt = $pdo->prepare("DELETE FROM cloacker_password_resets WHERE admin_id = :admin_id");
            $stmt->execute([':admin_id' => $admin['id']]);

            // Yeni token ekle
            $stmt = $pdo->prepare("INSERT INTO cloacker_password_resets (admin_id, token, expires_at) VALUES (:admin_id, :token, :expires)");
            $stmt->execute([
                ':admin_id' => $admin['id'],
                ':token' => $token,
                ':expires' => $expires
            ]);

            // Şifre sıfırlama linki oluştur
            $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/reset_password.php?token=$token";

            // E-posta gönder (basit mail fonksiyonu)
            $subject = "Şifre Sıfırlama Talebi";
            $message = "Merhaba {$admin['username']},\n\nŞifrenizi sıfırlamak için aşağıdaki linke 1 saat içinde tıklayın:\n\n$resetLink\n\nEğer bu isteği siz yapmadıysanız dikkate almayın.";
            $headers = "From: no-reply@yourdomain.com\r\n";

            if (mail($email, $subject, $message, $headers)) {
                $success = "Şifre sıfırlama linki e-posta adresinize gönderildi.";
            } else {
                $error = "E-posta gönderilemedi, lütfen daha sonra tekrar deneyiniz.";
            }
        } else {
            $error = "Bu e-posta adresi kayıtlı değil.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Şifremi Unuttum</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { darkMode: "class" };
</script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex items-center justify-center">

<div class="max-w-md w-full bg-white dark:bg-gray-800 p-8 rounded-xl shadow-lg">
    <h2 class="text-2xl font-semibold mb-6 text-center text-gray-900 dark:text-gray-100">Şifremi Unuttum</h2>

    <?php if ($error): ?>
        <div class="bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-200 p-3 rounded mb-4"><?=htmlspecialchars($error)?></div>
    <?php elseif ($success): ?>
        <div class="bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200 p-3 rounded mb-4"><?=htmlspecialchars($success)?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
        <label class="block font-medium text-gray-900 dark:text-gray-100">E-posta Adresi</label>
        <input type="email" name="email" required
               class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        <button type="submit" class="w-full bg-blue-600 dark:bg-blue-700 text-white py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600 transition">Şifre Sıfırlama Linki Gönder</button>
    </form>

    <p class="mt-4 text-center text-gray-900 dark:text-gray-100">
        <a href="index.php" class="text-blue-600 dark:text-blue-400 hover:underline">Giriş sayfasına dön</a>
    </p>
</div>

</body>
</html>
