<?php

require_once __DIR__ . '/../../cloacker.php';

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function logoutAdmin(bool $timeout = false): void {
    session_unset();
    session_destroy();
    header("Location: index.php" . ($timeout ? '?timeout=1' : ''));
    exit();
}

function respondAdminAuthFailure(bool $jsonResponse, string $message, bool $timeout = false): void {
    if ($jsonResponse) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'fail', 'message' => $message]);
        exit();
    }
    logoutAdmin($timeout);
}

function enforceAdminSession(bool $jsonResponse = false): void {
    if (!isAdminLoggedIn()) {
        respondAdminAuthFailure($jsonResponse, 'Giriş yapılmamış');
    }

    if (!empty($_SESSION['admin_fingerprint'])) {
        $currentFingerprint = hash('sha256', getClientIP() . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (!hash_equals($_SESSION['admin_fingerprint'], $currentFingerprint)) {
            respondAdminAuthFailure($jsonResponse, 'Oturum doğrulanamadı.');
        }
    }

    $timeout = (int)config('security.session_timeout', 900);
    if ($timeout > 0 && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        respondAdminAuthFailure($jsonResponse, 'Oturum süresi doldu.', true);
    }

    $_SESSION['last_activity'] = time();
}


