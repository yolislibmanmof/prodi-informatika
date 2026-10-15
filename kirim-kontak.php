<?php
/**
 * kirim-kontak.php - HANDLER FORM KONTAK DENGAN KEAMANAN BERLAPIS
 * Terintegrasi dengan Security.php + tabel security_logs + notifications
 */

require_once __DIR__ . '/includes/init_security.php';
require_once __DIR__ . '/includes/functions.php';

/* ============================================================
   HELPER: JSON response atau redirect dengan flash message
============================================================ */
$respondError = function($msg, $backTo = 'index.php#contact') {
    if (!headers_sent()) {
        setFlash('error', $msg);
        header('Location: ' . $backTo);
        exit;
    }
    die($msg);
};

/* ============================================================
   1. METHOD VALIDATION - hanya POST yang diizinkan
============================================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sec_log('contact_method_blocked', 'warning', 'bukan POST');
    header('HTTP/1.1 405 Method Not Allowed');
    header('Location: index.php');
    exit;
}

/* ============================================================
   2. REFERER VALIDATION - cegah CSRF dari domain luar
============================================================ */
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$allowedHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
if ($referer && stripos($referer, $allowedHost) === false) {
    sec_log('contact_referer_blocked', 'warning', "referer=$referer");
    $respondError('Akses ditolak: sumber permintaan tidak valid');
}

/* ============================================================
   3. CSRF TOKEN VALIDATION - pakai Security class baru
============================================================ */
$token = $_POST['csrf_token'] ?? '';
if (!Security::csrfVerify($token)) {
    sec_log('csrf_fail', 'warning', 'form kontak');
    $respondError('Token keamanan tidak valid. Silakan refresh halaman dan coba lagi.');
}

/* ============================================================
   4. HONEYPOT TRAP - field tersembunyi untuk bot
============================================================ */
if (!empty($_POST['website_url']) || !empty($_POST['company_name_hp'])) {
    sec_log('bot_trap', 'warning', 'honeypot terisi di form kontak');
    sleep(2); // Delay agar bot "yakin" submit berhasil
    header('Location: terima-kasih.php?bot=1');
    exit;
}

/* ============================================================
   5. TIMING ATTACK PROTECTION - form diisi terlalu cepat = bot
============================================================ */
$formLoadedAt = (int)($_POST['form_loaded_at'] ?? 0);
$elapsed = time() - $formLoadedAt;
if ($formLoadedAt > 0 && $elapsed < 3) {
    sec_log('contact_timing_attack', 'warning', "elapsed=${elapsed}s");
    $respondError('Form dikirim terlalu cepat. Silakan coba lagi.');
}

/* ============================================================
   6. RATE LIMITING PER IP - pakai Security class (max 5 pesan / 15 menit)
============================================================ */
$clientIP = Security::clientIP();
if (!Security::rateLimit('contact_' . $clientIP, 5, 900)) {
    sec_log('contact_rate_limit', 'warning', "IP=$clientIP");
    $respondError('Terlalu banyak pengiriman. Tunggu beberapa menit sebelum mencoba lagi.');
}

/* ============================================================
   7. INPUT SANITIZATION & VALIDATION
============================================================ */
$nama    = trim($_POST['nama'] ?? '');
$email   = trim($_POST['email'] ?? '');
$telepon = trim($_POST['telepon'] ?? '');
$subjek  = trim($_POST['subjek'] ?? '');
$pesan   = trim($_POST['pesan'] ?? '');

$errors = [];

// Nama
if (empty($nama)) {
    $errors[] = 'Nama wajib diisi';
} elseif (mb_strlen($nama) < 3) {
    $errors[] = 'Nama minimal 3 karakter';
} elseif (mb_strlen($nama) > 100) {
    $errors[] = 'Nama maksimal 100 karakter';
} elseif (!preg_match('/^[\p{L}\s\'.\-]+$/u', $nama)) {
    $errors[] = 'Nama hanya boleh berisi huruf';
}

// Email
if (empty($email)) {
    $errors[] = 'Email wajib diisi';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email tidak valid';
} elseif (mb_strlen($email) > 150) {
    $errors[] = 'Email terlalu panjang';
}

// Telepon (optional tapi kalau diisi harus valid)
if (!empty($telepon)) {
    $teleponClean = preg_replace('/[^0-9+\-\s()]/', '', $telepon);
    if (mb_strlen($teleponClean) < 8 || mb_strlen($teleponClean) > 20) {
        $errors[] = 'Nomor telepon tidak valid';
    }
    $telepon = $teleponClean;
}

// Subjek (optional)
if (!empty($subjek) && mb_strlen($subjek) > 200) {
    $errors[] = 'Subjek terlalu panjang (maks 200 karakter)';
}

// Pesan
if (empty($pesan)) {
    $errors[] = 'Pesan wajib diisi';
} elseif (mb_strlen($pesan) < 10) {
    $errors[] = 'Pesan minimal 10 karakter';
} elseif (mb_strlen($pesan) > 2000) {
    $errors[] = 'Pesan maksimal 2000 karakter';
}

/* ============================================================
   8. SPAM KEYWORD DETECTION
============================================================ */
$spamKeywords = [
    'viagra', 'cialis', 'casino', 'poker', 'lottery', 'winner',
    'click here', 'buy now', '[url=', '<a href', 'http://'
];
$lowerPesan = strtolower($pesan . ' ' . $subjek);
foreach ($spamKeywords as $kw) {
    if (stripos($lowerPesan, $kw) !== false) {
        sec_log('spam_keyword_detected', 'warning', "keyword=$kw, email=$email");
        $errors[] = 'Pesan terdeteksi mengandung konten tidak sesuai';
        break;
    }
}

/* ============================================================
   9. DUPLICATE DETECTION (pesan identik dari email yang sama dalam 1 jam)
============================================================ */
try {
    $db = getDB();
    $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
    $stmtDup = $db->prepare("
        SELECT COUNT(*) FROM kontak 
        WHERE email = ? AND pesan = ? AND created_at > ?
    ");
    $stmtDup->execute([$email, $pesan, $oneHourAgo]);
    if ((int)$stmtDup->fetchColumn() > 0) {
        sec_log('contact_duplicate', 'info', "email=$email");
        $errors[] = 'Pesan identik sudah pernah dikirim. Silakan tulis pesan baru.';
    }
} catch (Throwable $e) {
    // Skip duplicate check kalau DB bermasalah
}

/* ============================================================
   JIKA ADA ERROR, BALIK KE FORM
============================================================ */
if (!empty($errors)) {
    setFlash('error', implode('. ', $errors));
    header('Location: index.php#contact');
    exit;
}

/* ============================================================
   10. CLEAN FINAL DATA - pakai Security::sanitize() yang lebih kuat
============================================================ */
$nama   = Security::sanitize($nama);
$email  = Security::sanitize($email);
$subjek = Security::sanitize($subjek);
$pesan  = Security::sanitize($pesan);

/* ============================================================
   11. SAVE TO DATABASE + GENERATE TICKET NUMBER
============================================================ */
try {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $refererSafe = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 255);

    $stmt = $db->prepare("
        INSERT INTO kontak (nama, email, telepon, subjek, pesan, ip_address, user_agent, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'baru')
    ");
    $stmt->execute([$nama, $email, $telepon, $subjek, $pesan, $clientIP, $userAgent]);
    $messageId = $db->lastInsertId();
    $ticketNumber = 'TKT-' . date('Ymd') . '-' . str_pad($messageId, 5, '0', STR_PAD_LEFT);

    // Update ticket_number di database
    $db->prepare("UPDATE kontak SET ticket_number = ? WHERE id = ?")->execute([$ticketNumber, $messageId]);

    // Catat ke activity_logs
    if (function_exists('act_log')) {
        act_log('kontak', 'create', $messageId, "Pesan baru dari $nama ($email)");
    }

    // Buat notifikasi untuk admin (muncul di lonceng notifikasi)
    $notifStmt = $db->prepare("
        INSERT INTO notifications (tipe, judul, pesan, link) 
        VALUES ('kontak_baru', ?, ?, ?)
    ");
    $notifStmt->execute([
        'Pesan Kontak Baru dari ' . $nama,
        'Subjek: ' . $subjek . ' | Email: ' . $email,
        'admin/kontak.php?id=' . $messageId
    ]);

    // Simpan info untuk halaman sukses
    $_SESSION['contact_receipt'] = [
        'ticket' => $ticketNumber,
        'name'   => $nama,
        'email'  => $email,
        'time'   => date('d F Y, H:i'),
    ];

    // (Opsional) Kirim notifikasi ke admin via email - pakai EmailService nanti
    // EmailService::sendContactNotification($ticketNumber, $nama, $email, $subjek, $pesan);

    sec_log('contact_success', 'info', "ticket=$ticketNumber");
    header('Location: terima-kasih.php');
    exit;

} catch (Throwable $e) {
    sec_log('contact_db_error', 'critical', $e->getMessage());
    setFlash('error', 'Terjadi kesalahan sistem. Silakan coba lagi beberapa saat.');
    header('Location: index.php#contact');
    exit;
}