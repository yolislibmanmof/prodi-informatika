<?php
/**
 * Auth.php - LAPIS 3: AUTENTIKASI, LOCKOUT & ROLE GATE
 */
require_once __DIR__ . '/Security.php';

/* ===== DEFENSIVE BOOTSTRAP (FIX: fatal setFlash / sesi kosong) ===== */
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}
if (!function_exists('setFlash') && is_file(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
}

class Auth {
    const MAX_FAIL   = 5;    // percobaan gagal maksimal
    const LOCK_MIN   = 15;   // menit kunci akun

    public static function check() { return !empty($_SESSION['admin_id']); }
    public static function id()    { return $_SESSION['admin_id'] ?? null; }
    public static function user()  {
        return self::check() ? [
            'id' => $_SESSION['admin_id'], 'username' => $_SESSION['admin_username'],
            'name' => $_SESSION['admin_name'], 'role' => $_SESSION['admin_role'],
        ] : null;
    }
    public static function isSuper() { return ($_SESSION['admin_role'] ?? '') === 'superadmin'; }

    public static function requireAuth() {
        if (!self::check()) {
            if (function_exists('sec_log')) sec_log('unauthorized_access', 'warning', $_SERVER['REQUEST_URI'] ?? '');
            if (function_exists('setFlash')) setFlash('error', 'Silakan login terlebih dahulu.');
            header('Location: login.php'); exit;
        }
    }

    public static function requireRole($role) {
        self::requireAuth();
        if (($_SESSION['admin_role'] ?? '') !== $role) {
            if (function_exists('sec_log')) sec_log('insufficient_role', 'warning', "butuh=$role");
            if (function_exists('setFlash')) setFlash('error', 'Hak akses tidak mencukupi.');
            header('Location: dashboard.php'); exit;
        }
    }

    /** Login otomatis dari cookie remember-me */
    public static function bootRemember() {
        if (self::check()) return;
        $adminId = Security::consumeRememberToken();
        if (!$adminId) return;
        $db = getDB();
        $st = $db->prepare("SELECT * FROM admins WHERE id = ? AND status = 'aktif'");
        $st->execute([$adminId]);
        $admin = $st->fetch();
        if ($admin) self::establishSession($admin, false);
    }

    /** Proses login username+password dengan lockout */
    public static function attempt($username, $password, $remember = false) {
        $db = getDB();
        $st = $db->prepare("SELECT * FROM admins WHERE username = ?");
        $st->execute([$username]);
        $admin = $st->fetch();

        // Akun terkunci?
        if ($admin && $admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
        $lockedUntil = $admin['locked_until'] ?? null;
        if ($admin && $lockedUntil && strtotime($lockedUntil) > time()) {
            if (function_exists('sec_log')) sec_log('login_blocked_locked', 'warning', '', $username);
            return [false, 'Akun terkunci sementara. Coba lagi ' . date('H.i', strtotime($admin['locked_until'])) . '.'];
            return [false, 'Akun terkunci sementara. Coba lagi ' . date('H.i', strtotime($lockedUntil)) . '.'];
        }
        // Akun nonaktif?
        if ($admin && ($admin['status'] ?? 'aktif') !== 'aktif') {
@@ -79,6 +80,12 @@
                $db->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([Security::passwordHash($password), $admin['id']]);
            }
            self::establishSession($admin, $remember);

            // Jika pengguna tidak memilih remember-me, cabut token lama pada browser ini.
            if (!$remember) {
                Security::destroyRememberToken();
            }

            return [true, ''];
        }

@@ -104,18 +111,41 @@
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name']     = $admin['nama_lengkap'];
        $_SESSION['admin_role']     = $admin['role'];
        $_SESSION['fp']             = substr(md5(($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 16);

        // Samakan fingerprint dengan init_security.php agar sesi tidak dianggap berubah
        // hanya karena mode development menggunakan nilai IP "dev".
        $fingerprintIP = (defined('APP_ENV') && APP_ENV === 'development')
            ? 'dev'
            : ($_SERVER['REMOTE_ADDR'] ?? '');
        $_SESSION['fp'] = substr(md5($fingerprintIP . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 16);

        // Jangan gunakan token CSRF pra-login setelah sesi autentikasi berganti.
        unset($_SESSION['csrf_token']);

        $db = getDB();
        $db->prepare("UPDATE admins SET last_login = NOW(), last_login_ip = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?")
           ->execute([Security::clientIP(), $admin['id']]);
        if ($remember) Security::issueRememberToken($admin['id']);
        if ($remember) {
            // Rotasi token remember-me lama sebelum menerbitkan token yang baru.
            Security::destroyRememberToken();
            try {
                Security::issueRememberToken($admin['id']);
            } catch (Throwable $e) {
                // Kegagalan remember-me tidak boleh membatalkan login yang sudah valid.
                error_log('Unable to issue remember-me token: ' . $e->getMessage());
                if (function_exists('sec_log')) {
                    sec_log('remember_me_issue_failed', 'warning', '', $admin['username']);
                }
            }
        }

        if (function_exists('sec_log')) sec_log('login_success', 'info', '', $admin['username']);
        if (function_exists('act_log')) act_log('auth', 'login', $admin['id']);
    }

    public static function logout() {
        if (function_exists('act_log')) act_log('auth', 'logout');
        Security::destroyRememberToken();
        $_SESSION = [];
        session_destroy();
    }