<?php
/**
 * admin/logout.php - LOGOUT AMAN DENGAN INVALIDASI COOKIE & SESSION
 */

require_once __DIR__ . '/../includes/init_security.php';
require_once __DIR__ . '/../includes/Auth.php';

// Logout changes authentication state; only accept a CSRF-protected POST.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Silakan gunakan tombol Keluar dari panel admin.');
}
if (!Security::csrfVerify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Permintaan logout ditolak karena token keamanan tidak valid.');
}

// Pastikan user memang login sebelum logout
if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

// Log aktivitas logout
if (function_exists('act_log')) {
    act_log('auth', 'logout', Auth::id(), 'User logout manual');
}
if (function_exists('sec_log')) {
    sec_log('logout_success', 'info', '', Auth::user()['username'] ?? '');
}

// Hapus cookie remember-me dari database + browser
Security::destroyRememberToken();

// Bersihkan seluruh session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect ke halaman login
header('Location: login.php?logout=1');
exit;