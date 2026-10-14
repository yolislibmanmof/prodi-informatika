<?php
/**
 * config.php - Central Configuration
 * Loads environment variables from .env file
 * Backward compatible dengan existing constants
 */

// ===== LOAD ENVIRONMENT VARIABLES FROM .ENV =====
$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($envLines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes jika ada
            $value = trim($value, '"\'');
            
            // Set sebagai environment variable
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// ===== HELPER FUNCTION UNTUK GET CONFIG =====
if (!function_exists('env')) {
    /**
     * Get environment variable dengan default value
     * Usage: env('DB_HOST', 'localhost')
     * 
     * @param string $key Nama variable
     * @param mixed $default Default value jika tidak ada
     * @return mixed
     */
    function env($key, $default = null) {
        $value = $_ENV[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert boolean strings
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        
        return $value;
    }
}

// ===== APPLICATION CONFIG =====
// Production is the safe default; development must be enabled explicitly.
define('APP_ENV', env('APP_ENV', 'production')); // development | production
define('APP_DEBUG', env('APP_DEBUG', false));
define('APP_VERSION', '1.0.0');

// ===== BACKWARD COMPATIBLE CONSTANTS =====
define('SITE_NAME', env('SITE_NAME', 'Informatika Pro'));
define('SITE_URL', env('SITE_URL', APP_ENV === 'development' ? 'http://localhost/prodi-informatika' : ''));

// ===== DATABASE CONFIG =====
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'prodi-informatika'));
define('DB_USER', env('DB_USER', APP_ENV === 'development' ? 'root' : ''));
define('DB_PASS', env('DB_PASS', ''));
define('DB_PORT', env('DB_PORT', 3306));
define('DB_CHARSET', 'utf8mb4');

// Fail closed when the production configuration has not been provided.
if (APP_ENV === 'production' && (SITE_URL === '' || DB_USER === '' || DB_PASS === '')) {
    error_log('Production configuration is incomplete. Check SITE_URL, DB_USER and DB_PASS.');
    http_response_code(500);
    exit('Konfigurasi aplikasi belum lengkap. Periksa variabel lingkungan pada server.');
}

// ===== SESSION CONFIG =====
define('SESSION_LIFETIME', env('SESSION_LIFETIME', 120)); // dalam menit
define('SESSION_NAME', env('SESSION_NAME', 'IFPROSESS'));

// ===== SECURITY CONFIG =====
define('CSRF_ENABLED', env('CSRF_ENABLED', true));
define('RATE_LIMIT_ENABLED', env('RATE_LIMIT_ENABLED', true));
define('MAX_LOGIN_ATTEMPTS', env('MAX_LOGIN_ATTEMPTS', 5));
define('LOCKOUT_MINUTES', env('LOCKOUT_MINUTES', 15));

// ===== FILE UPLOAD CONFIG =====
define('UPLOAD_MAX_SIZE', env('UPLOAD_MAX_SIZE', 5242880)); // 5MB dalam bytes
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// ===== CACHE CONFIG =====
define('CACHE_ENABLED', env('CACHE_ENABLED', true));
define('CACHE_DRIVER', env('CACHE_DRIVER', 'file')); // file | redis
define('CACHE_TTL', env('CACHE_TTL', 3600)); // 1 hour default

// ===== EMAIL CONFIG (untuk future use) =====
define('MAIL_HOST', env('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', env('MAIL_PORT', 587));
define('MAIL_USERNAME', env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', ''));
define('MAIL_FROM_EMAIL', env('MAIL_FROM_EMAIL', 'noreply@informatika.ac.id'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', SITE_NAME));

// ===== TIMEZONE =====
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

// ===== ERROR REPORTING =====
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Log errors to file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');