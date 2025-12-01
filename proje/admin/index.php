<?php
require_once '../cloacker.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header("Location: dashboard.php");
    exit();
}

function verify_admin_login($username, $password) {
    $pdo = DB::connect();
    $stmt = $pdo->prepare("SELECT * FROM cloacker_admins WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();
    return ($admin && password_verify($password, $admin['password_hash'])) ? $admin : null;
}

function log_admin_login($admin_id, $ip, $user_agent, $success) {
    $pdo = DB::connect();
    $stmt = $pdo->prepare("
        INSERT INTO cloacker_admin_logins (admin_id, login_time, ip, user_agent, success)
        VALUES (:aid, NOW(), :ip, :ua, :succ)
    ");
    $stmt->execute([
        ':aid' => $admin_id > 0 ? $admin_id : null,
        ':ip' => $ip,
        ':ua' => $user_agent,
        ':succ' => $success ? 1 : 0
    ]);
}

$error = '';
$lockout = false;
$ip = getClientIP();
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$maxAttempts = (int)config('security.max_login_attempts', 5);
$lockoutMinutes = (int)config('security.login_lockout_minutes', 15);

function is_login_locked_out(string $ip, int $maxAttempts, int $lockoutMinutes): bool {
    if ($maxAttempts <= 0 || $lockoutMinutes <= 0) {
        return false;
    }
    $pdo = DB::connect();
    $cutoff = (new DateTimeImmutable("-{$lockoutMinutes} minutes"))->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cloacker_admin_logins WHERE ip = :ip AND success = 0 AND login_time >= :cutoff");
    $stmt->execute([':ip' => $ip, ':cutoff' => $cutoff]);
    return ((int)$stmt->fetchColumn()) >= $maxAttempts;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    if (is_login_locked_out($ip, $maxAttempts, $lockoutMinutes)) {
        $lockout = true;
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $admin = verify_admin_login($username, $password);

        if ($admin) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_fingerprint'] = hash('sha256', $ip . $ua);
            $_SESSION['last_activity'] = time();

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
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Panel Giriş</title>

<script src="https://cdn.tailwindcss.com"></script>

<style>
    /* fade + scale animasyonu */
    .login-card {
        opacity: 0;
        transform: scale(0.92);
        animation: fadeIn .45s ease-out forwards;
    }
    @keyframes fadeIn {
        to { opacity: 1; transform: scale(1); }
    }
</style>

</head>

<!-- DİĞER SAYFALARDAKİ GİBİ DARK MODE BODY ÜZERİNDEN KONTROL EDİLİYOR -->
<body class="dark bg-gray-900 min-h-screen flex items-center justify-center transition-colors">

<div class="login-card w-full max-w-md bg-gray-800 border border-gray-700 p-8 rounded-2xl shadow-2xl">

    <h2 class="text-3xl font-bold text-center mb-6 text-gray-100">
        Admin Panel Giriş
    </h2>

    <?php if($lockout): ?>
        <div class="bg-red-900 text-red-200 p-3 rounded mb-4">
            Çok fazla hatalı deneme tespit edildi. Lütfen <?=$lockoutMinutes?> dakika sonra tekrar deneyin.
        </div>
    <?php elseif($error): ?>
        <div class="bg-red-900 text-red-200 p-3 rounded mb-4">
            <?=htmlspecialchars($error)?>
        </div>
    <?php endif; ?>

    <form method="post" autocomplete="off" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrf_token())?>">

        <div>
            <label class="block mb-1 text-gray-300">Kullanıcı Adı</label>
            <input type="text" name="username" required autofocus
                class="w-full bg-gray-900 border border-gray-700 text-gray-100 rounded px-3 py-2
                       focus:ring-2 focus:ring-blue-500 outline-none transition" />
        </div>

        <div>
            <label class="block mb-1 text-gray-300">Şifre</label>
            <input type="password" name="password" required
                class="w-full bg-gray-900 border border-gray-700 text-gray-100 rounded px-3 py-2
                       focus:ring-2 focus:ring-blue-500 outline-none transition" />
        </div>

        <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition">
            Giriş Yap
        </button>

    </form>

    <p class="text-center mt-4">
        <a href="forgot_password.php" class="text-blue-400 hover:underline">
            Şifremi Unuttum?
        </a>
    </p>

</div>

</body>
</html>
