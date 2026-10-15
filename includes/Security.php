<?php
/**
 * Security.php - LAPIS 4: PERPUSTAKAAN KEAMANAN
 */
class Security {

    /** IP peer langsung; forwarded headers hanya aman jika proxy tepercaya dikonfigurasi. */
    public static function clientIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    /** Token CSRF (ambil / buat) */
    public static function csrf() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Cetak input hidden CSRF untuk form */
    public static function csrfField() {
        echo '<input type="hidden" name="csrf_token" value="' . self::csrf() . '">';
    }

    public static function csrfVerify($token) {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
    }

    /** Rate limit berbasis session + catat ke security_logs saat jebol */
    public static function rateLimit($key, $max = 5, $window = 900) {
        $now = time();
        $_SESSION['rl'][$key] = array_filter($_SESSION['rl'][$key] ?? [], fn($t) => ($now - $t) < $window);
        if (count($_SESSION['rl'][$key]) >= $max) {
            if (function_exists('sec_log')) sec_log('rate_limit', 'warning', "kunci=$key");
            return false;
        }
        $_SESSION['rl'][$key][] = $now;
        return true;
    }

    /** Validasi upload berlapis: error -> ukuran -> ekstensi ganda -> MIME -> isi file */
    public static function validateUpload($file, $allowed = ['jpg','jpeg','png','webp','gif'], $max = 5242880) {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK)  return [false, 'Upload gagal.'];
        if ($file['size'] > $max)                                        return [false, 'File terlalu besar (maks 5MB).'];
        $name = basename($file['name']);
        if (preg_match('/\.(php|phtml|phar|pl|py|jsp|asp|sh|cgi)/i', $name)) return [false, 'Nama file mencurigakan.'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true))                             return [false, 'Ekstensi tidak diizinkan.'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $okMime = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif'];
        if (($okMime[$ext] ?? '') !== $mime)                             return [false, 'Isi file tidak cocok dengan ekstensinya.'];
        $head = file_get_contents($file['tmp_name'], false, null, 0, 512);
        if (stripos($head, '<?php') !== false || stripos($head, '<script') !== false) return [false, 'File mengandung kode berbahaya.'];
        return [true, ''];
    }

    public static function passwordHash($p)  { return password_hash($p, PASSWORD_DEFAULT); }
    public static function passwordVerify($p, $h) { return password_verify($p, $h); }

    /* ----- Remember-me model SELECTOR:VALIDATOR (anti token theft) ----- */
    public static function issueRememberToken($adminId) {
        $selector   = bin2hex(random_bytes(12));
        $validator  = bin2hex(random_bytes(32));
        $expires    = date('Y-m-d H:i:s', time() + 30*86400);
        require_once __DIR__ . '/database.php';
        $st = getDB()->prepare("INSERT INTO remember_tokens (admin_id, selector, hashed_validator, expires_at) VALUES (?,?,?,?)");
        $st->execute([$adminId, $selector, hash('sha256', $validator), $expires]);
        setcookie('ifpro_remember', $selector . ':' . $validator, [
            'expires'  => time() + 30*86400, 'path' => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true, 'samesite' => 'Lax',
        ]);
    }

    /** Coba login otomatis dari cookie; return admin_id atau null */
    public static function consumeRememberToken() {
        if (empty($_COOKIE['ifpro_remember'])) return null;
        [$selector, $validator] = array_pad(explode(':', $_COOKIE['ifpro_remember'], 2), 2, '');
        if (!$selector || !$validator) return null;
        require_once __DIR__ . '/database.php';
        $db = getDB();
        $st = $db->prepare("SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW()");
        $st->execute([$selector]);
        $row = $st->fetch();
        if (!$row) return null;
        if (!hash_equals($row['hashed_validator'], hash('sha256', $validator))) {
            // Validator salah padahal selector ada = cookie kemungkinan DICURI
            $db->prepare("DELETE FROM remember_tokens WHERE admin_id = ?")->execute([$row['admin_id']]);
            if (function_exists('sec_log')) sec_log('remember_token_theft', 'critical', "selector=$selector");
            return null;
        }
        // Rotasi validator (sekali pakai)
        $newValidator = bin2hex(random_bytes(32));
        $db->prepare("UPDATE remember_tokens SET hashed_validator = ? WHERE id = ?")->execute([hash('sha256', $newValidator), $row['id']]);
        setcookie('ifpro_remember', $selector . ':' . $newValidator, [
            'expires' => time() + 30*86400, 'path' => '/',
            'secure'  => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly'=> true, 'samesite' => 'Lax',
        ]);
        return (int)$row['admin_id'];
    }

    public static function destroyRememberToken() {
        if (empty($_COOKIE['ifpro_remember'])) return;
        $selector = explode(':', $_COOKIE['ifpro_remember'], 2)[0];
        try {
            require_once __DIR__ . '/database.php';
            getDB()->prepare("DELETE FROM remember_tokens WHERE selector = ?")->execute([$selector]);
        } catch (Throwable $e) {}
        setcookie('ifpro_remember', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}