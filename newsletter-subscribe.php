<?php
/**
 * newsletter-subscribe.php - Handler backend form newsletter
 * Terintegrasi dengan Security.php + tabel newsletter_subscribers
 */
require_once __DIR__ . '/includes/init_security.php';

header('Content-Type: application/json; charset=utf-8');

// Helper response JSON
$respond = function($success, $message, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'timestamp' => date('c')
    ]);
    exit;
};

// ===== 1. METHOD VALIDATION =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(false, 'Metode tidak diizinkan', 405);
}

// ===== 2. CSRF VALIDATION =====
$token = $_POST['csrf_token'] ?? '';
if (!Security::csrfVerify($token)) {
    sec_log('newsletter_csrf_fail', 'warning');
    $respond(false, 'Token keamanan tidak valid. Refresh halaman dan coba lagi.', 403);
}

// ===== 3. RATE LIMITING (max 3 subscribe / IP / jam) =====
$clientIP = Security::clientIP();
if (!Security::rateLimit('newsletter_' . $clientIP, 3, 3600)) {
    sec_log('newsletter_rate_limit', 'warning', "IP=$clientIP");
    $respond(false, 'Terlalu banyak percobaan. Coba lagi nanti.', 429);
}

// ===== 4. HONEYPOT TRAP =====
if (!empty($_POST['website_url']) || !empty($_POST['company_hp'])) {
    sec_log('newsletter_bot_trap', 'warning', "IP=$clientIP");
    sleep(1);
    $respond(true, 'Terima kasih telah berlangganan!');
}

// ===== 5. VALIDASI EMAIL =====
$email = trim($_POST['email'] ?? '');
$source = $_POST['source'] ?? 'unknown';

if (empty($email)) {
    $respond(false, 'Email wajib diisi', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sec_log('newsletter_invalid_email', 'info', "email=$email");
    $respond(false, 'Format email tidak valid', 400);
}

if (strlen($email) > 150) {
    $respond(false, 'Email terlalu panjang', 400);
}

// Normalisasi email (lowercase)
$email = strtolower($email);

// ===== 6. SIMPAN KE DATABASE =====
try {
    $db = getDB();
    
    // Cek apakah email sudah terdaftar
    $stmt = $db->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['status'] === 'aktif') {
            $respond(false, 'Email ini sudah berlangganan. Silakan cek inbox Anda.', 409);
        } elseif ($existing['status'] === 'unsubscribed') {
            // Reactivate
            $db->prepare("UPDATE newsletter_subscribers SET status = 'aktif', token = ? WHERE id = ?")
               ->execute([bin2hex(random_bytes(32)), $existing['id']]);
            $respond(true, 'Selamat datang kembali! Anda kini berlangganan newsletter kami.');
        } else {
            // Pending - kirim ulang konfirmasi
            $respond(true, 'Email sudah terdaftar. Silakan cek inbox untuk konfirmasi.');
        }
    }
    
    // Insert subscriber baru
    $token = bin2hex(random_bytes(32));
    $stmt = $db->prepare("INSERT INTO newsletter_subscribers (email, token, status, created_at) VALUES (?, ?, 'pending', NOW())");
    $stmt->execute([$email, $token]);
    
    $subscriberId = $db->lastInsertId();
    
    // Catat aktivitas
    if (function_exists('act_log')) {
        act_log('newsletter', 'subscribe', $subscriberId, "Subscriber baru: $email via $source");
    }
    sec_log('newsletter_subscribe', 'info', "email=$email, source=$source");
    
    // Kirim email konfirmasi (opsional, via EmailService)
    // TODO: Aktifkan setelah EmailService diimplementasikan
    // EmailService::send('newsletter_welcome', $email, [
    //     'nama' => 'Subscriber',
    //     'link_konfirmasi' => SITE_URL . '/newsletter-confirm.php?token=' . $token
    // ]);
    
    $respond(true, 'Terima kasih! Silakan cek inbox Anda untuk konfirmasi berlangganan.');
    
} catch (Throwable $e) {
    sec_log('newsletter_db_error', 'critical', $e->getMessage());
    error_log('[Newsletter] DB error: ' . $e->getMessage());
    $respond(false, 'Terjadi kesalahan sistem. Coba lagi nanti.', 500);
}