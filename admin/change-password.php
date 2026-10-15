<?php
/**
 * change-password.php - THIN REDIRECT WRAPPER
 * Semua logika password kini di-handle oleh profile.php
 * File ini dipertahankan hanya sebagai backward-compatibility
 * untuk link lama atau mekanisme forced password change.
 */
require_once __DIR__ . '/includes/auth.php';
Auth::requireAuth();

// Jika ada parameter ?forced=1, tampilkan pesan khusus
if (isset($_GET['forced'])) {
    setFlash('info', 'Untuk keamanan, silakan perbarui password Anda di halaman Profil.');
}

// Redirect ke profile.php#security (tab ganti password)
header('Location: profile.php#security');
exit;