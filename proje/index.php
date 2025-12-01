<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../cloacker.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header("Location: dashboard.php");
    exit();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 300)) {
    session_unset();
    session_destroy();
}

$_SESSION['last_activity'] = time();

function verify_admin_login($username, $password) {
    $pdo = DB::connect();
    $stmt = $pdo->prepare("SELECT * FROM cloacker_admins WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        return $admin;
    }
    return null;
}

function log_admin_login($admin_id, $ip, $user_agent, $success) {
    $pdo = DB::connect();
    $stmt = $pdo->prepare("INSERT INTO cloacker_admin_logins (admin_id, login_time, ip, user_agent, success) VALUES (:aid, NOW(), :ip, :ua, :succ)");
    $stmt->execute([
        ':aid' => $admin_id > 0 ? $admin_id : null,
        ':ip' => $ip,
        ':ua' => $user_agent,
        ':succ' => $success ? 1 : 0
    ]);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = getClientIP();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $admin = verify_admin_login($username, $password);

    if ($admin) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        $pdo = DB::connect();
        $stmt = $pdo->prepare("UPDATE cloacker_admins SET last_login = NOW() WHERE id = :id");
        $stmt->execute(['id' => $admin['id']]);

        log_admin_login($admin['id'], $ip, $ua, true);

        header("Location: dashboard.php");
        exit();
    } else {
        log_admin_login(null, $ip, $ua, false);
        $error = "Kullanıcı adı veya şifre hatalı.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Panel Giriş</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="max-w-md w-full bg-white p-8 rounded shadow">
    <h2 class="text-2xl font-semibold mb-6 text-center">Admin Panel Girişi</h2>
    <?php if($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off" class="space-y-6">
        <div>
            <label for="username" class="block mb-2 font-medium">Kullanıcı Adı</label>
            <input type="text" id="username" name="username" required autofocus class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
        <div>
            <label for="password" class="block mb-2 font-medium">Şifre</label>
            <input type="password" id="password" name="password" required class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Giriş Yap</button>
    </form>
    <p class="text-center mt-4">
        <a href="forgot_password.php" class="text-blue-600 hover:underline">Şifremi Unuttum?</a>
    </p>
</div>
</body>
</html>