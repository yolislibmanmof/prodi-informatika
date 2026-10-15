<?php
/**
 * init_security.php - LAPIS 2: BOOTSTRAP KEAMANAN GLOBAL (Enhanced)
 * WAJIB dipanggil paling awal oleh header.php & admin/includes/auth.php
 * 
 * Features:
 * - Environment-based configuration
 * - HTTPS enforcement
 * - Session timeout
 * - Enhanced session security
 * - Security logging
 */

if (defined('SEC_INIT')) return;
define('SEC_INIT', true);

// Load configuration pertama (sebelum yang lain)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/functions.php';

/* ============================================================
   1. HTTPS ENFORCEMENT (Application Level)
   ============================================================ */
function enforceHTTPS() {
    // Skip untuk localhost/development
    $devHosts = ['localhost', '127.0.0.1', '::1', '192.168.', '10.'];
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    
    // Check apakah ini development environment
    $isDev = APP_ENV === 'development';
    
    if ($isDev) {
        return; // Development mode, skip HTTPS enforcement
    }
    
    // Check apakah request dari local network
    foreach ($devHosts as $devHost) {
        if (strpos($currentHost, $devHost) !== false) {
            return; // Local development, skip
        }
    }
    
    // Check apakah HTTPS aktif
    $isHTTPS = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
               || $_SERVER['SERVER_PORT'] == 443
               || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    if (!$isHTTPS) {
        // Redirect ke HTTPS
        $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirectURL);
        exit('Redirecting to secure connection...');
    }
}

// Panggil HTTPS enforcement
enforceHTTPS();

/* ============================================================
   2. ERROR REPORTING CONFIGURATION
   ============================================================ */
$dev = (APP_ENV === 'development');

// Set error reporting berdasarkan environment
if ($dev) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

/* ============================================================
   3. FOLDER STRUCTURE VALIDATION
   ============================================================ */
$requiredDirs = ['logs', 'cache', 'uploads'];
foreach ($requiredDirs as $d) {
    $dirPath = __DIR__ . '/../' . $d;
    if (!is_dir($dirPath)) {
        if (!@mkdir($dirPath, 0755, true)) {
            error_log("Failed to create directory: $dirPath");
        }
    }
}

/* ============================================================
   4. SESSION CONFIGURATION (Enhanced)
   ============================================================ */

// Session timeout dari config (dalam menit, convert ke detik)
$sessionLifetime = SESSION_LIFETIME * 60;

// Set session configuration SEBELUM session_start
ini_set('session.gc_maxlifetime', $sessionLifetime);
ini_set('session.cookie_lifetime', 0); // Session cookie (bukan persistent)
ini_set('session.use_strict_mode', 1); // Reject uninitialized session IDs
ini_set('session.use_only_cookies', 1); // Hanya pakai cookies (no URL session IDs)
ini_set('session.cookie_httponly', 1); // Anti XSS
ini_set('session.cookie_samesite', 'Lax'); // Anti CSRF

// Session name dari config
session_name(SESSION_NAME);

// Set cookie parameters
session_set_cookie_params([
    'lifetime' => 0, // Session only
    'path'     => '/',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ============================================================
   5. SESSION TIMEOUT CHECK
   ============================================================ */
if (isset($_SESSION['last_activity'])) {
    $timeSinceLastActivity = time() - $_SESSION['last_activity'];
    
    if ($timeSinceLastActivity > $sessionLifetime) {
        // Session expired
        $expiredUser = $_SESSION['admin_username'] ?? 'Unknown';
        
        // Log session timeout
        if (function_exists('sec_log')) {
            sec_log('session_timeout', 'info', "User: $expiredUser, Idle: " . round($timeSinceLastActivity / 60) . " menit");
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Start new session untuk flash message
        session_start();
        setFlash('warning', 'Sesi Anda telah berakhir karena tidak aktif selama ' . SESSION_LIFETIME . ' menit. Silakan login kembali.');
        
        // Redirect ke login page jika di admin area
        $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($currentScript, '/admin/') !== false) {
            header('Location: ' . SITE_URL . '/admin/login.php');
            exit;
        }
        
        // Untuk public pages, just continue dengan empty session
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();

/* ============================================================
   6. SESSION FINGERPRINTING (Anti Session Hijacking)
   ============================================================ */
// Buat fingerprint dari IP + User Agent
$clientIP = $dev ? 'dev' : ($_SERVER['REMOTE_ADDR'] ?? '');
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$fingerprint = substr(md5($clientIP . '|' . $userAgent), 0, 16);

// Check fingerprint
if (empty($_SESSION['fp'])) {
    // First request, set fingerprint
    $_SESSION['fp'] = $fingerprint;
} elseif ($_SESSION['fp'] !== $fingerprint) {
    // Fingerprint berubah = possible session hijacking
    $oldUser = $_SESSION['admin_username'] ?? 'Unknown';
    
    // Log suspicious activity
    error_log("SECURITY ALERT: Session fingerprint mismatch! User: $oldUser, IP: $clientIP");
    
    if (function_exists('sec_log')) {
        sec_log('session_hijack_attempt', 'critical', "Old user: $oldUser, New IP: $clientIP");
    }
    
    // Destroy session
    session_unset();
    session_destroy();
    
    // Start new session
    session_start();
    $_SESSION['fp'] = $fingerprint;
    
    setFlash('error', 'Sesi Anda telah berakhir karena terdeteksi aktivitas mencurigakan. Silakan login kembali.');
    
    // Redirect ke login jika di admin area
    $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($currentScript, '/admin/') !== false) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

/* ============================================================
   7. SECURITY HEADERS (Fallback jika mod_headers Apache mati)
   ============================================================ */
$headersSent = headers_sent();

if (!$headersSent) {
    // Check apakah mod_headers aktif
    $modHeadersActive = function_exists('apache_get_modules') && 
                        in_array('mod_headers', apache_get_modules(), true);
    
    if (!$modHeadersActive) {
        // Set security headers manual
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; img-src 'self' data: https:; media-src 'self' https://commondatastorage.googleapis.com https://*.googleapis.com; connect-src 'self'; frame-src 'self' https://www.google.com; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'");
        header('X-XSS-Protection: 1; mode=block');
    }
    
    // Remove dangerous headers
    header_remove('X-Powered-By');
    header_remove('Server');
}

/* ============================================================
   8. SECURITY LOGGING HELPER (LAPIS 5)
   ============================================================ */
if (!function_exists('sec_log')) {
    /**
     * Log security events ke database
     * 
     * @param string $event Event type (login_failed, csrf_fail, dll)
     * @param string $severity Severity level (info, warning, critical)
     * @param string $details Additional details
     * @param string|null $username Username terkait (optional)
     */
    function sec_log($event, $severity = 'info', $details = '', $username = null) {
        try {
            $db = getDB();
            
            $st = $db->prepare("INSERT INTO security_logs
                (event_type, username, ip_address, user_agent, severity, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())");
            
            $st->execute([
                $event,
                $username,
                Security::clientIP(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                $severity,
                substr($details, 0, 1000)
            ]);
        } catch (Throwable $e) {
            // Logging gagal tidak boleh crash aplikasi
            // Tapi log ke file
            error_log("Security logging failed: " . $e->getMessage());
        }
    }
}

/* ============================================================
   9. ACTIVITY LOGGING HELPER
   ============================================================ */
if (!function_exists('act_log')) {
    /**
     * Log admin activity ke database
     * 
     * @param string $module Module name (auth, berita, user, dll)
     * @param string $action Action performed (login, create, update, delete)
     * @param int|null $targetId ID of affected record (optional)
     * @param string $description Human-readable description
     */
    function act_log($module, $action, $targetId = null, $description = '') {
        try {
            $db = getDB();
            
            $st = $db->prepare("INSERT INTO activity_logs
                (admin_id, module, action, target_id, description, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $st->execute([
                $_SESSION['admin_id'] ?? null,
                $module,
                $action,
                $targetId,
                substr($description, 0, 500),
                Security::clientIP(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]);
        } catch (Throwable $e) {
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }
}

/* ============================================================
   10. ADDITIONAL SECURITY CHECKS
   ============================================================ */

// Prevent clickjacking dengan X-Frame-Options
if (!$headersSent && !defined('ALLOW_IFRAME')) {
    // Bisa di-override di halaman tertentu dengan define('ALLOW_IFRAME', true);
    header('X-Frame-Options: SAMEORIGIN');
}

// Prevent MIME type sniffing
if (!$headersSent) {
    header('X-Content-Type-Options: nosniff');
}

// Log successful security initialization (sekali saja per session)
if (!isset($_SESSION['sec_init_logged'])) {
    $_SESSION['sec_init_logged'] = true;
    // Uncomment untuk debug:
    // error_log("Security initialization completed successfully");
}