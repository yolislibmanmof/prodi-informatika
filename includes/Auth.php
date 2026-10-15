<?php
/**
 * Auth.php - Central authentication, lockout, role checks and remember-me support.
 * Requires the project's shared security bootstrap and database helpers.
 */
require_once __DIR__ . '/init_security.php';
require_once __DIR__ . '/database.php';

class Auth
{
    private const MAX_FAIL = 5;
    private const LOCK_MIN = 15;

    public static function check(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['admin_id'] : null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id'       => (int) $_SESSION['admin_id'],
            'username' => (string) ($_SESSION['admin_username'] ?? ''),
            'name'     => (string) ($_SESSION['admin_name'] ?? ''),
            'role'     => (string) ($_SESSION['admin_role'] ?? ''),
        ];
    }

    public static function isSuper(): bool
    {
        return ($_SESSION['admin_role'] ?? '') === 'superadmin';
    }

    public static function requireAuth(): void
    {
        if (self::check()) {
            return;
        }

        if (function_exists('sec_log')) {
            sec_log('unauthorized_access', 'warning', $_SERVER['REQUEST_URI'] ?? '');
        }
        if (function_exists('setFlash')) {
            setFlash('error', 'Silakan login terlebih dahulu.');
        }
        header('Location: login.php');
        exit;
    }

    public static function requireRole(string $role): void
    {
        self::requireAuth();
        if (($_SESSION['admin_role'] ?? '') === $role) {
            return;
        }

        if (function_exists('sec_log')) {
            sec_log('insufficient_role', 'warning', 'role_required=' . $role);
        }
        if (function_exists('setFlash')) {
            setFlash('error', 'Hak akses tidak mencukupi.');
        }
        header('Location: dashboard.php');
        exit;
    }

    /** Authenticate an administrator using the stored password hash. */
    public static function attempt(string $username, string $password, bool $remember = false): array
    {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            if (function_exists('sec_log')) {
                sec_log('login_failed', 'warning', 'Unknown username', $username);
            }
            return [false, 'Username atau password salah.'];
        }

        $lockedUntil = $admin['locked_until'] ?? null;
        if ($lockedUntil && strtotime((string) $lockedUntil) > time()) {
            if (function_exists('sec_log')) {
                sec_log('login_blocked_locked', 'warning', '', $username);
            }
            return [false, 'Username atau password salah.'];
        }

        if (($admin['status'] ?? 'aktif') !== 'aktif') {
            if (function_exists('sec_log')) {
                sec_log('login_blocked_inactive', 'warning', '', $username);
            }
            return [false, 'Username atau password salah.'];
        }

        $storedHash = (string) ($admin['password'] ?? '');
        if ($storedHash !== '' && Security::passwordVerify($password, $storedHash)) {
            if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
                $rehash = $db->prepare('UPDATE admins SET password = ? WHERE id = ?');
                $rehash->execute([Security::passwordHash($password), (int) $admin['id']]);
            }

            self::establishSession($admin);

            // Remove the previous browser token first; issue just one fresh token if requested.
            try {
                Security::destroyRememberToken();
                if ($remember) {
                    Security::issueRememberToken((int) $admin['id']);
                }
            } catch (Throwable $e) {
                error_log('Remember-me token update failed: ' . $e->getMessage());
                if (function_exists('sec_log')) {
                    sec_log('remember_me_issue_failed', 'warning', '', $username);
                }
            }

            if (function_exists('sec_log')) {
                sec_log('login_success', 'info', '', $username);
            }
            if (function_exists('act_log')) {
                act_log('auth', 'login', (int) $admin['id']);
            }
            return [true, ''];
        }

        // Persist failed attempts and account lockout for an existing active account.
        $failedAttempts = (int) ($admin['failed_attempts'] ?? 0);
        if ($lockedUntil && strtotime((string) $lockedUntil) <= time()) {
            $failedAttempts = 0;
        }
        $failedAttempts++;
        $newLockUntil = $failedAttempts >= self::MAX_FAIL
            ? date('Y-m-d H:i:s', time() + self::LOCK_MIN * 60)
            : null;

        $update = $db->prepare('UPDATE admins SET failed_attempts = ?, locked_until = ? WHERE id = ?');
        $update->execute([$failedAttempts, $newLockUntil, (int) $admin['id']]);

        if (function_exists('sec_log')) {
            sec_log('login_failed', 'warning', 'Failed password attempt', $username);
        }
        return [false, 'Username atau password salah.'];
    }

    /** Try a persistent login token and rotate its validator before creating a session. */
    public static function bootRemember(): void
    {
        if (self::check() || empty($_COOKIE['ifpro_remember'])) {
            return;
        }

        try {
            $adminId = Security::consumeRememberToken();
            if (!$adminId) {
                Security::destroyRememberToken();
                return;
            }

            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM admins WHERE id = ? AND status = 'aktif' LIMIT 1");
            $stmt->execute([(int) $adminId]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$admin) {
                Security::destroyRememberToken();
                return;
            }

            // consumeRememberToken() has already rotated the token, so do not delete it.
            self::establishSession($admin);
        } catch (Throwable $e) {
            error_log('Remember-me bootstrap failed: ' . $e->getMessage());
        }
    }

    /** Establish a fresh, fingerprinted PHP session after the database update succeeds. */
    private static function establishSession(array $admin): void
    {
        $db = getDB();
        $stmt = $db->prepare(
            'UPDATE admins SET last_login = NOW(), last_login_ip = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?'
        );
        $stmt->execute([Security::clientIP(), (int) $admin['id']]);

        if (session_status() === PHP_SESSION_ACTIVE && !session_regenerate_id(true)) {
            throw new RuntimeException('Unable to regenerate the authenticated session ID.');
        }

        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = (string) $admin['username'];
        $_SESSION['admin_name'] = (string) ($admin['nama_lengkap'] ?? '');
        $_SESSION['admin_role'] = (string) ($admin['role'] ?? 'admin');
        $_SESSION['last_activity'] = time();

        $fingerprintIP = (defined('APP_ENV') && APP_ENV === 'development')
            ? 'dev'
            : ($_SERVER['REMOTE_ADDR'] ?? '');
        $_SESSION['fp'] = substr(md5($fingerprintIP . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 16);

        // Force forms to obtain a new CSRF token after authentication changes.
        unset($_SESSION['csrf_token']);
    }

    public static function logout(): void
    {
        if (function_exists('act_log')) {
            act_log('auth', 'logout');
        }
        Security::destroyRememberToken();
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
