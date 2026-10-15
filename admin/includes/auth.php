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