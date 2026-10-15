<?php
/**
 * admin/includes/auth.php - GATEKEEPER AREA ADMIN
 * Dipanggil oleh semua halaman admin untuk verifikasi login.
 * Terintegrasi dengan Auth.php + Security.php + Remember-me cookie.
 */

require_once __DIR__ . '/../../includes/init_security.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Coba login otomatis dari cookie "ingat saya"
Auth::bootRemember();

// Jika tidak login, lempar ke halaman login
if (!Auth::check()) {
    if (function_exists('sec_log')) sec_log('admin_access_denied', 'warning', $_SERVER['REQUEST_URI'] ?? '');
    setFlash('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
    header('Location: login.php');
    exit;
}

// Block state-changing GET requests. Admin UI now submits these actions as POST with a CSRF token.
// Read-only actions such as view/export remain GET-compatible.
$adminAction = strtolower(trim((string)($_GET['action'] ?? '')));
$mutatingAdminAction = '/(?:^|[-_])(delete|remove|destroy|toggle|status|duplicate|publish|draft|activate|deactivate|approve|reject|spam|pending|verified|accepted|rejected|revoke|unsubscribe|dibaca|dibalas|clear|reset|disable|enable|feature|hide|show|purge|flush|cleanup|mark|archive|restore|regenerate|generate|send|resend)(?:$|[-_])/';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && $adminAction !== ''
    && preg_match($mutatingAdminAction, $adminAction)) {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: text/plain; charset=utf-8');
    exit('Aksi perubahan harus dikirim melalui formulir yang aman. Kembali ke panel admin dan coba lagi.');
}

// Enforce CSRF validation centrally for every state-changing admin form.
// All admin POST forms must include the session token; invalid requests fail closed.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && (!defined('CSRF_ENABLED') || CSRF_ENABLED)
    && !verifyCSRF($_POST['csrf_token'] ?? '')) {
    if (function_exists('sec_log')) {
        sec_log('csrf_fail', 'warning', 'Admin POST rejected: ' . basename($_SERVER['SCRIPT_NAME'] ?? 'unknown'));
    }
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Permintaan ditolak karena token keamanan tidak valid. Muat ulang halaman dan coba lagi.');
}

// Sinkronisasi cache publik setelah setiap perubahan dari panel admin.
// Hook shutdown dijalankan setelah handler CRUD selesai (termasuk saat script exit/redirect),
// sehingga halaman publik tidak terus menyajikan data lama dari cache file.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    register_shutdown_function(static function (): void {
        if (function_exists('cacheFlush')) {
            cacheFlush();
        }
    });
}

// Helper lama untuk kompatibilitas kode existing
function isAdmin() {
    return Auth::check();
}

function requireAdmin() {
    Auth::requireAuth();
}

function requireSuperAdmin() {
    Auth::requireRole('superadmin');
}