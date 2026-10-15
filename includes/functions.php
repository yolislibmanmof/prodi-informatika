<?php
/**
 * INFORMATIKA PRO - Global Functions Helper (Enhanced)
 * Ultimate Edition: Secure, Optimized, Cached, and Comprehensive
 * 
 * Features:
 * - File-based caching untuk performance
 * - Enhanced security validation
 * - Better error handling
 * - Additional helper functions
 */

// Pastikan database.php sudah di-load
if (!function_exists('getDB')) {
    require_once __DIR__ . '/database.php';
}

/* ============================================================
   1. SESSION & FLASH MESSAGES
============================================================ */

/**
 * Set flash message untuk ditampilkan sekali
 * @param string $type success|error|warning|info
 * @param string $message Pesan yang akan ditampilkan
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get dan clear flash message
 * @return array|null ['type' => string, 'message' => string] atau null
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/* ============================================================
   2. FILE-BASED CACHING SYSTEM
============================================================ */

/**
 * Get cache path
 * @param string $key Cache key
 * @return string Full path ke cache file
 */
function getCachePath($key) {
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    return $cacheDir . '/' . md5($key) . '.cache';
}

/**
 * Get data dari cache atau execute callback jika expired/tidak ada
 * 
 * @param string $key Cache key (unique identifier)
 * @param int $ttl Time to live dalam detik (default: 3600 = 1 jam)
 * @param callable $callback Function yang return data jika cache miss
 * @return mixed Cached data atau result dari callback
 * 
 * Usage:
 * $data = cache('home_news', 300, function() {
 *     return expensive_database_query();
 * });
 */
function cache($key, $ttl, $callback) {
    // Check apakah caching enabled
    if (!defined('CACHE_ENABLED') || !CACHE_ENABLED) {
        return $callback();
    }
    
    $cacheFile = getCachePath($key);
    
    // Check apakah cache exists dan belum expired
    if (file_exists($cacheFile)) {
        $cacheData = @file_get_contents($cacheFile);
        if ($cacheData !== false) {
            $data = @unserialize($cacheData);
            
            // Check apakah data valid dan belum expired
            if ($data !== false && isset($data['expires']) && $data['expires'] > time()) {
                return $data['value'];
            }
        }
    }
    
    // Cache miss atau expired, execute callback
    $value = $callback();
    
    // Save ke cache
    $cacheData = [
        'value' => $value,
        'expires' => time() + $ttl,
        'created' => time()
    ];
    
    @file_put_contents($cacheFile, serialize($cacheData), LOCK_EX);
    
    return $value;
}

/**
 * Hapus cache by key
 * @param string $key Cache key
 */
function cacheForget($key) {
    $cacheFile = getCachePath($key);
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }
}

/**
 * Hapus cache dengan pattern (semua cache yang key-nya mengandung string)
 * @param string $pattern Pattern untuk match (contoh: 'berita_')
 */
function cacheForgetPattern($pattern) {
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir)) return;
    
    $files = glob($cacheDir . '/*.cache');
    $patternHash = md5($pattern);
    
    foreach ($files as $file) {
        // Simple pattern matching (check apakah filename mengandung pattern hash)
        if (strpos(basename($file), $patternHash) !== false) {
            @unlink($file);
        }
    }
}

/**
 * Clear semua cache
 */
function cacheFlush() {
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir)) return;
    
    $files = glob($cacheDir . '/*.cache');
    foreach ($files as $file) {
        @unlink($file);
    }
}

/* ============================================================
   3. SECURITY & SANITIZATION
============================================================ */

/**
 * Sanitize data untuk output (anti XSS)
 * @param mixed $data Data yang akan di-sanitize
 * @return mixed Sanitized data
 */
function clean($data) {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate URL-friendly slug
 * @param string $string String yang akan di-convert
 * @return string Slug
 */
function generateSlug($string) {
    // Convert ke lowercase
    $slug = strtolower($string);
    
    // Replace spasi dan karakter special dengan dash
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    
    return trim($slug, '-');
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token yang akan diverifikasi
 * @return bool True jika valid
 */
function verifyCSRF($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Timing attack safe comparison
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate email address
 * @param string $email Email yang akan divalidasi
 * @return bool True jika valid
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indonesian format)
 * @param string $phone Nomor telepon
 * @return bool True jika valid
 */
function validatePhone($phone) {
    // Remove semua karakter kecuali angka, +, -, spasi, ()
    $cleaned = preg_replace('/[^0-9+\-\s()]/', '', $phone);
    
    // Minimal 8 digit, maksimal 15 digit
    $digits = preg_replace('/[^0-9]/', '', $cleaned);
    return strlen($digits) >= 8 && strlen($digits) <= 15;
}

/**
 * Validate URL
 * @param string $url URL yang akan divalidasi
 * @return bool True jika valid
 */
function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Escape untuk SQL LIKE query
 * @param string $string String yang akan di-escape
 * @return string Escaped string
 */
function escapeLike($string) {
    return addcslashes($string, '%_');
}

/* ============================================================
   4. DATE & TIME FORMATTING
============================================================ */

/**
 * Format tanggal ke format Indonesia
 * @param string $date Tanggal (Y-m-d atau timestamp)
 * @return string Format: "01 Januari 2024"
 */
function formatTanggal($date) {
    if (empty($date)) return '-';
    
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    if ($timestamp === false) return '-';
    
    return date('d', $timestamp) . ' ' . 
           $bulan[(int)date('n', $timestamp)] . ' ' . 
           date('Y', $timestamp);
}

/**
 * Format tanggal dan waktu
 * @param string $datetime DateTime string
 * @return string Format: "01 Januari 2024, 14:30"
 */
function formatDateTime($datetime) {
    if (empty($datetime)) return '-';
    
    $timestamp = strtotime($datetime);
    if ($timestamp === false) return '-';
    
    return formatTanggal($datetime) . ', ' . date('H:i', $timestamp);
}

/**
 * Format waktu relatif (time ago)
 * @param string $datetime DateTime string
 * @return string Format: "5 menit lalu", "2 jam lalu", dll
 */
function timeAgo($datetime) {
    if (empty($datetime)) return '-';
    
    $timestamp = strtotime($datetime);
    if ($timestamp === false) return '-';
    
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    if ($diff < 2592000) return floor($diff / 604800) . ' minggu lalu';
    if ($diff < 31536000) return floor($diff / 2592000) . ' bulan lalu';
    
    return formatTanggal($datetime);
}

/* ============================================================
   5. DATABASE HELPERS (PUBLIC & ADMIN) - WITH CACHING
============================================================ */

/**
 * Get berita untuk homepage (dengan caching)
 * @param int $limit Jumlah berita yang diambil
 * @return array Array of berita
 */
function getHomeNews($limit = 3) {
    return cache('home_news_' . $limit, 300, function() use ($limit) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM berita WHERE status = 'published' ORDER BY published_at DESC LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    });
}

/**
 * Get testimonials (dengan caching)
 * @param int|null $limit Jumlah testimoni (null = semua)
 * @return array Array of testimonials
 */
function getTestimonials($limit = null) {
    $cacheKey = 'testimonials_' . ($limit ?? 'all');
    
    return cache($cacheKey, 600, function() use ($limit) {
        $db = getDB();
        $sql = "SELECT * FROM testimoni WHERE status = 'approved' ORDER BY urutan ASC, created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        return $db->query($sql)->fetchAll();
    });
}

/**
 * Get berita by slug (dengan caching)
 * @param string $slug Slug berita
 * @return array|false Berita data atau false
 */
function getBeritaBySlug($slug) {
    return cache('berita_slug_' . md5($slug), 3600, function() use ($slug) {
        $db = getDB();
        $stmt = $db->prepare("SELECT b.*, a.nama_lengkap as author_name 
                              FROM berita b 
                              LEFT JOIN admins a ON b.author_id = a.id 
                              WHERE b.slug = ? AND b.status = 'published'");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    });
}

/**
 * Update view count untuk berita
 * @param int $id ID berita
 */
function updateViews($id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE berita SET views = views + 1 WHERE id = ?");
    $stmt->execute([(int)$id]);
    
    // Invalidate cache untuk berita ini
    // (kita tidak cache individual berita untuk simplicity)
}

/**
 * Get related news (dengan caching)
 * @param int $id ID berita saat ini
 * @param string $kategori Kategori berita
 * @param int $limit Jumlah related news
 * @return array Array of related berita
 */
function getRelatedNews($id, $kategori, $limit = 3) {
    $cacheKey = 'related_news_' . $id . '_' . md5($kategori) . '_' . $limit;
    
    return cache($cacheKey, 300, function() use ($id, $kategori, $limit) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM berita 
                              WHERE id != ? AND kategori = ? AND status = 'published' 
                              ORDER BY published_at DESC LIMIT ?");
        $stmt->bindValue(1, (int)$id, PDO::PARAM_INT);
        $stmt->bindValue(2, $kategori);
        $stmt->bindValue(3, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    });
}

/**
 * Get dashboard statistics (dengan caching)
 * @return array Statistics data
 */
function getDashboardStats() {
    return cache('dashboard_stats', 60, function() {
        $db = getDB();
        return [
            'total_berita' => (int)$db->query("SELECT COUNT(*) FROM berita")->fetchColumn(),
            'total_testimoni' => (int)$db->query("SELECT COUNT(*) FROM testimoni")->fetchColumn(),
            'total_pendaftaran' => (int)$db->query("SELECT COUNT(*) FROM pendaftaran")->fetchColumn(),
            'total_kontak' => (int)$db->query("SELECT COUNT(*) FROM kontak WHERE status = 'baru'")->fetchColumn(),
            'total_views' => (int)$db->query("SELECT COALESCE(SUM(views), 0) FROM berita")->fetchColumn(),
        ];
    });
}

/* ============================================================
   6. FILE UPLOAD HANDLER (Enhanced dengan Security Class)
============================================================ */

/**
 * Upload image dengan validasi berlapis
 * @param array $file $_FILES array
 * @param string $folder Target folder (default: 'uploads')
 * @return string|null Relative path ke file atau null jika gagal
 */
function uploadImage($file, $folder = 'uploads') {
    // Cek apakah file benar-benar terupload tanpa error
    if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $uploadDir = __DIR__ . '/../' . $folder . '/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("Failed to create upload directory: $uploadDir");
            return null;
        }
    }
    
    // Validasi ekstensi
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed, true)) {
        error_log("Upload failed: Invalid extension - $ext");
        return null;
    }
    
    // Validasi ukuran (max 5MB)
    $maxSize = defined('UPLOAD_MAX_SIZE') ? UPLOAD_MAX_SIZE : 5242880;
    if ($file['size'] > $maxSize) {
        error_log("Upload failed: File too large - " . $file['size'] . " bytes");
        return null;
    }
    
    // Validasi MIME type (untuk image)
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        
        if (isset($allowedMimes[$ext]) && $allowedMimes[$ext] !== $mime) {
            error_log("Upload failed: MIME type mismatch - expected {$allowedMimes[$ext]}, got $mime");
            return null;
        }
    }
    
    // Generate unique filename
    $filename = uniqid('img_', true) . '_' . time() . '.' . $ext;
    $target = $uploadDir . $filename;
    
    // Move file
    if (move_uploaded_file($file['tmp_name'], $target)) {
        @chmod($target, 0644);
        
        // ===== PHASE 2: AUTO OPTIMIZE IMAGE =====
        $optFile = __DIR__ . '/ImageOptimizer.php';
        if (file_exists($optFile)) {
            require_once $optFile;
            $optResult = ImageOptimizer::optimize($target);
            
            if ($optResult && ($optResult['success'] ?? false) && defined('APP_DEBUG') && APP_DEBUG) {
                error_log(sprintf(
                    "Image optimized: %s (%s → %s, hemat %s%%)",
                    $filename,
                    formatFileSize($optResult['original_size'] ?? 0),
                    formatFileSize($optResult['optimized_size'] ?? 0),
                    $optResult['savings_percent'] ?? 0
                ));
            }
        }
        
        return $folder . '/' . $filename;
    }
    
    error_log("Upload failed: move_uploaded_file failed");
    return null;
}

/**
 * Delete uploaded file
 * @param string $path Relative path ke file
 * @return bool True jika berhasil
 */
function deleteUploadedFile($path) {
    if (empty($path)) return false;
    
    $fullPath = __DIR__ . '/../' . $path;
    
    if (file_exists($fullPath)) {
        return @unlink($fullPath);
    }
    
    return false;
}

/* ============================================================
   7. GLOBAL SETTINGS HELPER (Enhanced dengan Caching)
============================================================ */

/**
 * Get single setting value (dengan caching)
 * @param string $key Setting key
 * @param string $default Default value jika tidak ada
 * @return string Setting value
 */
function getSetting($key, $default = '') {
    $settings = getAllSettings();
    return $settings[$key] ?? $default;
}

/**
 * Get all settings (dengan caching)
 * @return array Array of settings [key => value]
 */
function getAllSettings() {
    return cache('all_settings', 300, function() {
        $db = getDB();
        $stmt = $db->query("SELECT key_name, value FROM pengaturan");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    });
}

/**
 * Update setting value
 * @param string $key Setting key
 * @param string $value Setting value
 * @return bool True jika berhasil
 */
function updateSetting($key, $value) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO pengaturan (key_name, value) 
                              VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE value = ?");
        $result = $stmt->execute([$key, $value, $value]);
        
        // Invalidate cache
        cacheForget('all_settings');
        
        return $result;
    } catch (Exception $e) {
        error_log("Failed to update setting: " . $e->getMessage());
        return false;
    }
}

/* ============================================================
   8. GLOBAL IMAGE FALLBACK HELPER
============================================================ */

if (!function_exists('getBeritaImage')) {
    /**
     * Get image URL dengan fallback handling
     * @param string $gambar Image filename atau URL
     * @param string $kategori Kategori berita (untuk default image)
     * @param string $prefix Prefix untuk path (optional)
     * @return string Image URL
     */
    function getBeritaImage($gambar, $kategori = '', $prefix = '') {
        $defaults = [
            'Workshop'   => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=600',
            'Pengabdian' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=600',
            'Proyek'     => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=600',
        ];
        
        $fallback = $defaults[$kategori] ?? 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600';

        // Jika tidak ada gambar, return fallback
        if (!$gambar) return $fallback;
        
        // Jika sudah URL lengkap, return as-is
        if (stripos($gambar, 'http') === 0) return $gambar;

        // Cari file fisik dengan beberapa kemungkinan path
        $candidates = [
            $gambar,
            'uploads/' . $gambar,
            'uploads/berita/' . $gambar
        ];
        
        foreach ($candidates as $path) {
            $fullPath = dirname(__DIR__) . '/' . $path;
            if (file_exists($fullPath)) {
                return $prefix . $path;
            }
        }
        
        // File tidak ada, return fallback
        return $fallback;
    }
}

/* ============================================================
   9. ADDITIONAL HELPER FUNCTIONS
============================================================ */

/**
 * Get asset URL dengan versioning otomatis (cache busting)
 * File berubah → ?v= berubah → browser download versi baru
 * @param string $path Contoh: 'css/style.css' atau 'js/script.js'
 * @return string URL lengkap dengan version query
 */
function asset($path) {
    static $versions = [];
    $path = ltrim($path, '/');
    
    if (!isset($versions[$path])) {
        $fullPath = dirname(__DIR__) . '/' . $path;
        $versions[$path] = file_exists($fullPath) ? filemtime($fullPath) : time();
    }
    
    return SITE_URL . '/' . $path . '?v=' . $versions[$path];
}

/**
 * Format file size ke human-readable format
 * @param int $bytes Size dalam bytes
 * @param int $precision Decimal precision
 * @return string Format: "1.5 MB", "256 KB", dll
 */
function formatFileSize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Truncate string dengan ellipsis
 * @param string $string String yang akan di-truncate
 * @param int $length Maximum length
 * @param string $ellipsis Ellipsis string
 * @return string Truncated string
 */
function truncate($string, $length = 100, $ellipsis = '...') {
    if (mb_strlen($string) <= $length) {
        return $string;
    }
    
    return mb_substr($string, 0, $length) . $ellipsis;
}

/**
 * Get client IP address (support proxy/CDN)
 * @return string IP address
 */
function getClientIP() {
    $ipKeys = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}

/**
 * Get current URL
 * @param bool $withQuery Include query string
 * @return string Current URL
 */
function currentURL($withQuery = true) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    if (!$withQuery) {
        $url = strtok($url, '?');
    }
    
    return $url;
}

/**
 * Redirect ke URL lain
 * @param string $url Target URL
 * @param int $statusCode HTTP status code
 */
function redirect($url, $statusCode = 302) {
    http_response_code($statusCode);
    header('Location: ' . $url);
    exit;
}

/**
 * Debug helper (hanya aktif di development)
 * @param mixed $data Data yang akan di-debug
 * @param bool $die Stop execution setelah debug
 */
function dd($data, $die = true) {
    if (!defined('APP_DEBUG') || !APP_DEBUG) {
        return;
    }
    
    echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:20px;margin:20px 0;border-radius:8px;font-family:Consolas,monospace;font-size:14px;overflow:auto;">';
    var_dump($data);
    echo '</pre>';
    
    if ($die) {
        exit;
    }
}