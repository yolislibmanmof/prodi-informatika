<?php
requireAdmin();

// ===== SAFE DATABASE CONNECTION =====
try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
}

// ===== NOTIFICATION COUNTS (Anti-Error) =====
$notifKontak = 0;
$notifPendaftar = 0;
$notifKomentar = 0;
$notifNewsletter = 0;

if ($db) {
    try { $notifKontak = (int)$db->query("SELECT COUNT(*) FROM kontak WHERE status = 'baru'")->fetchColumn(); } catch (Throwable $e) {}
    try { $notifPendaftar = (int)$db->query("SELECT COUNT(*) FROM pendaftaran WHERE status = 'pending'")->fetchColumn(); } catch (Throwable $e) {}
    try { $notifKomentar = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(); } catch (Throwable $e) {}
    try { $notifNewsletter = (int)$db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'pending'")->fetchColumn(); } catch (Throwable $e) {}
}

$totalNotif = $notifKontak + $notifPendaftar + $notifKomentar + $notifNewsletter;

// ===== RECENT NOTIFICATIONS =====
$notifications = [];
if ($db) {
    try {
        $notifications = $db->query("
            SELECT * FROM notifications 
            WHERE is_read = 0 
            ORDER BY created_at DESC 
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}

    if (empty($notifications)) {
        try {
            $notifications = $db->query("
                (SELECT 'kontak_baru' as tipe, nama as judul, CONCAT('Kontak baru dari ', nama) as pesan, 'fa-envelope' as icon, 'kontak.php' as link, created_at FROM kontak WHERE status='baru' ORDER BY created_at DESC LIMIT 3)
                UNION ALL
                (SELECT 'pendaftaran_baru' as tipe, nama_lengkap as judul, CONCAT('Pendaftar baru jalur ', jalur_pendaftaran) as pesan, 'fa-user-plus' as icon, 'pendaftaran.php' as link, created_at FROM pendaftaran WHERE status='pending' ORDER BY created_at DESC LIMIT 2)
                ORDER BY created_at DESC LIMIT 5
            ")->fetchAll();
        } catch (Throwable $e) {
            $notifications = [];
        }
    }
}

// ===== BREADCRUMB AUTO-DETECT =====
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitles = [
    'dashboard' => 'Dashboard',
    'berita' => 'Berita',
    'pengumuman' => 'Pengumuman',
    'galeri' => 'Galeri',
    'faq' => 'FAQ',
    'komentar' => 'Komentar',
    'media' => 'Media Library',
    'kurikulum' => 'Kurikulum',
    'konsentrasi' => 'Konsentrasi',
    'dosen' => 'Dosen',
    'fasilitas' => 'Fasilitas',
    'dokumen' => 'Dokumen',
    'agenda' => 'Agenda',
    'prestasi' => 'Prestasi',
    'prospek' => 'Prospek Karir',
    'alumni' => 'Alumni',
    'mitra' => 'Mitra',
    'pendaftaran' => 'Pendaftaran',
    'kontak' => 'Pesan Kontak',
    'testimoni' => 'Testimoni',
    'newsletter' => 'Newsletter',
    'analytics' => 'Analitik',
    'activity-logs' => 'Log Aktivitas',
    'security-logs' => 'Log Keamanan',
    'users' => 'Manajemen User',
    'settings' => 'Pengaturan',
    'email-templates' => 'Template Email',
    'api-keys' => 'API Keys',
    'profile' => 'Profil Saya',
    'change-password' => 'Ubah Password',
];
$breadcrumbTitle = $pageTitles[$currentPage] ?? ucfirst(str_replace('-', ' ', $currentPage));

// ===== ADMIN INFO =====
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$adminUsername = $_SESSION['admin_username'] ?? 'admin';

// ===== QUICK STATS FOR PROFILE =====
$profileStats = ['posts' => 0, 'tasks' => 0, 'days' => 0];
if ($db) {
    try {
        $profileStats['posts'] = (int)$db->query("SELECT COUNT(*) FROM berita WHERE author_id = " . (int)($_SESSION['admin_id'] ?? 0))->fetchColumn();
        $profileStats['tasks'] = (int)$db->query("SELECT COUNT(*) FROM pendaftaran WHERE status = 'pending'")->fetchColumn();
        $profileStats['days'] = isset($_SESSION['login_time']) ? max(1, ceil((time() - $_SESSION['login_time']) / 86400)) : 1;
    } catch (Throwable $e) {}
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle ?? $breadcrumbTitle) ?> — Admin Informatika Pro</title>
    <meta name="description" content="Admin Panel Program Studi Informatika - Ultimate Edition">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Base Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <style>
    /* ============================================================
       HEADER ULTIMATE - INTEGRATED WITH ADMIN.CSS v3.0
       Menggunakan CSS Variables & Existing Components
    ============================================================ */

    /* ===== HEADER MAIN ===== */
    .admin-header {
        position: sticky;
        top: 0;
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--spacing-sm) var(--spacing-lg);
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, 0.06));
        box-shadow: var(--shadow-sm);
        gap: var(--spacing);
    }

    [data-theme="dark"] .admin-header {
        background: rgba(30, 41, 59, 0.92);
    }

    /* ===== HEADER LEFT ===== */
    .header-left {
        display: flex;
        align-items: center;
        gap: var(--spacing);
        flex: 1;
        min-width: 0;
    }

    .sidebar-toggle {
        width: 42px;
        height: 42px;
        border-radius: var(--radius);
        background: var(--gray-100);
        border: 1px solid transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--dark);
        font-size: 1.1rem;
        transition: all var(--transition);
        flex-shrink: 0;
    }

    [data-theme="dark"] .sidebar-toggle {
        background: rgba(255, 255, 255, 0.08);
        color: var(--white);
    }

    .sidebar-toggle:hover {
        background: var(--primary);
        color: var(--white);
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    /* ===== BREADCRUMB ===== */
    .header-breadcrumb {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .hb-home {
        width: 36px;
        height: 36px;
        border-radius: var(--radius);
        background: var(--gray-100);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gray-600);
        text-decoration: none;
        font-size: 0.9rem;
        transition: all var(--transition-fast);
        flex-shrink: 0;
    }

    [data-theme="dark"] .hb-home {
        background: rgba(255, 255, 255, 0.08);
        color: var(--gray-400);
    }

    .hb-home:hover {
        background: var(--primary);
        color: var(--white);
        transform: translateY(-2px);
    }

    .hb-separator {
        color: var(--gray-400);
        font-size: 0.75rem;
    }

    .hb-current {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .hb-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--dark);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    [data-theme="dark"] .hb-title {
        color: var(--white);
    }

    .hb-badge {
        padding: 3px 10px;
        background: var(--gradient-primary);
        color: var(--white);
        border-radius: 20px;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        white-space: nowrap;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    /* ===== SEARCH ===== */
    .header-search {
        position: relative;
        flex: 1;
        max-width: 420px;
    }

    .hs-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .hs-icon {
        position: absolute;
        left: 14px;
        color: var(--gray-400);
        font-size: 0.9rem;
        z-index: 1;
        pointer-events: none;
    }

    .hs-input {
        width: 100%;
        padding: 10px 65px 10px 42px;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius);
        font-size: 0.88rem;
        font-family: inherit;
        background: var(--gray-50);
        transition: all var(--transition);
    }

    [data-theme="dark"] .hs-input {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
        color: var(--white);
    }

    .hs-input:focus {
        outline: none;
        border-color: var(--primary);
        background: var(--white);
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    }

    [data-theme="dark"] .hs-input:focus {
        background: rgba(255, 255, 255, 0.08);
    }

    .hs-shortcut {
        position: absolute;
        right: 12px;
        display: flex;
        gap: 4px;
        pointer-events: none;
    }

    .hs-kbd {
        padding: 2px 6px;
        background: var(--gray-200);
        color: var(--gray-600);
        border-radius: 4px;
        font-size: 0.68rem;
        font-weight: 600;
        font-family: 'Courier New', monospace;
        border: 1px solid var(--gray-300);
    }

    [data-theme="dark"] .hs-kbd {
        background: rgba(255, 255, 255, 0.1);
        color: var(--gray-400);
        border-color: rgba(255, 255, 255, 0.15);
    }

    /* ===== HEADER RIGHT ===== */
    .header-right {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .header-icon-btn {
        position: relative;
        width: 42px;
        height: 42px;
        border-radius: var(--radius);
        background: var(--gray-100);
        border: 1px solid transparent;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gray-700);
        font-size: 1.05rem;
        transition: all var(--transition);
    }

    [data-theme="dark"] .header-icon-btn {
        background: rgba(255, 255, 255, 0.08);
        color: var(--gray-300);
    }

    .header-icon-btn:hover {
        background: var(--primary);
        color: var(--white);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .header-icon-btn .hib-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: var(--gradient-danger);
        color: var(--white);
        font-size: 0.62rem;
        font-weight: 700;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        padding: 0 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--white);
        animation: badgePulse 2s infinite;
    }

    [data-theme="dark"] .header-icon-btn .hib-badge {
        border-color: var(--dark-2);
    }

    @keyframes badgePulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    /* ===== DROPDOWN ===== */
    .dropdown {
        position: relative;
    }

    .dropdown-menu {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        min-width: 340px;
        background: var(--white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xl);
        border: 1px solid var(--gray-100);
        padding: 0;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all var(--transition);
        z-index: 100;
        overflow: hidden;
    }

    [data-theme="dark"] .dropdown-menu {
        background: var(--dark-2);
        border-color: rgba(255, 255, 255, 0.08);
    }

    .dropdown.open .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    /* ===== NOTIFICATIONS DROPDOWN ===== */
    .notif-dropdown .dropdown-menu {
        min-width: 380px;
        max-height: 520px;
        display: flex;
        flex-direction: column;
    }

    .dm-header {
        padding: 18px 20px;
        border-bottom: 1px solid var(--gray-100);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--gray-50);
    }

    [data-theme="dark"] .dm-header {
        border-color: rgba(255, 255, 255, 0.08);
        background: rgba(0, 0, 0, 0.2);
    }

    .dmh-left h4 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    [data-theme="dark"] .dmh-left h4 {
        color: var(--white);
    }

    .dmh-left h4 i {
        color: var(--primary);
    }

    .dmh-left p {
        font-size: 0.78rem;
        color: var(--gray-600);
        margin: 0;
    }

    [data-theme="dark"] .dmh-left p {
        color: var(--gray-400);
    }

    .dmh-actions {
        display: flex;
        gap: 6px;
    }

    .dm-action-btn {
        width: 32px;
        height: 32px;
        border-radius: var(--radius-sm);
        background: transparent;
        border: 1px solid var(--gray-200);
        cursor: pointer;
        color: var(--gray-600);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        transition: all var(--transition-fast);
    }

    [data-theme="dark"] .dm-action-btn {
        border-color: rgba(255, 255, 255, 0.1);
        color: var(--gray-400);
    }

    .dm-action-btn:hover {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
    }

    /* Notif Tabs */
    .dm-tabs {
        display: flex;
        padding: 8px 12px;
        gap: 4px;
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-100);
    }

    [data-theme="dark"] .dm-tabs {
        background: rgba(0, 0, 0, 0.2);
        border-color: rgba(255, 255, 255, 0.08);
    }

    .dm-tab {
        flex: 1;
        padding: 8px 10px;
        border: none;
        background: transparent;
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--gray-600);
        transition: all var(--transition-fast);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    [data-theme="dark"] .dm-tab {
        color: var(--gray-400);
    }

    .dm-tab:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    [data-theme="dark"] .dm-tab:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .dm-tab.active {
        background: var(--white);
        color: var(--primary);
        box-shadow: var(--shadow-sm);
    }

    [data-theme="dark"] .dm-tab.active {
        background: rgba(255, 255, 255, 0.08);
        color: var(--primary-light);
    }

    .dm-tab-count {
        padding: 2px 7px;
        background: var(--gray-200);
        color: var(--gray-700);
        border-radius: 10px;
        font-size: 0.65rem;
        font-weight: 700;
    }

    [data-theme="dark"] .dm-tab-count {
        background: rgba(255, 255, 255, 0.1);
        color: var(--gray-300);
    }

    .dm-tab.active .dm-tab-count {
        background: rgba(16, 185, 129, 0.15);
        color: var(--primary);
    }

    /* Notif List */
    .dm-list {
        flex: 1;
        overflow-y: auto;
        max-height: 360px;
        padding: 8px;
    }

    .dm-list::-webkit-scrollbar {
        width: 5px;
    }

    .dm-list::-webkit-scrollbar-thumb {
        background: var(--gray-200);
        border-radius: 3px;
    }

    [data-theme="dark"] .dm-list::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
    }

    .notif-item {
        display: flex;
        gap: 12px;
        padding: 12px;
        border-radius: var(--radius);
        cursor: pointer;
        transition: all var(--transition-fast);
        text-decoration: none;
        color: inherit;
        margin-bottom: 4px;
        border: 1px solid transparent;
        position: relative;
    }

    .notif-item:hover {
        background: var(--gray-50);
        border-color: var(--gray-100);
        transform: translateX(2px);
    }

    [data-theme="dark"] .notif-item:hover {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.08);
    }

    .notif-item.unread {
        background: rgba(16, 185, 129, 0.04);
        padding-left: 22px;
    }

    .notif-item.unread::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 18px;
        width: 7px;
        height: 7px;
        background: var(--primary);
        border-radius: 50%;
        box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
    }

    .ni-icon {
        width: 42px;
        height: 42px;
        border-radius: var(--radius);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .ni-icon.kontak_baru { background: rgba(59, 130, 246, 0.12); color: #1E40AF; }
    .ni-icon.pendaftaran_baru { background: rgba(16, 185, 129, 0.12); color: #047857; }
    .ni-icon.komentar { background: rgba(251, 191, 36, 0.12); color: #92400E; }
    .ni-icon.newsletter { background: rgba(139, 92, 246, 0.12); color: #5B21B6; }
    .ni-icon.default { background: var(--gray-100); color: var(--gray-700); }

    [data-theme="dark"] .ni-icon.default {
        background: rgba(255, 255, 255, 0.08);
        color: var(--gray-300);
    }

    .ni-body {
        flex: 1;
        min-width: 0;
    }

    .ni-title {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    [data-theme="dark"] .ni-title {
        color: var(--white);
    }

    .ni-message {
        font-size: 0.78rem;
        color: var(--gray-600);
        margin-bottom: 6px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.4;
    }

    [data-theme="dark"] .ni-message {
        color: var(--gray-400);
    }

    .ni-time {
        font-size: 0.72rem;
        color: var(--gray-500);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .ni-time i {
        font-size: 0.68rem;
    }

    /* Empty State */
    .dm-empty {
        padding: 40px 20px;
        text-align: center;
    }

    .dm-empty i {
        font-size: 2.5rem;
        color: var(--gray-300);
        margin-bottom: 12px;
        display: block;
    }

    [data-theme="dark"] .dm-empty i {
        color: var(--gray-600);
    }

    .dm-empty h4 {
        font-size: 0.95rem;
        color: var(--dark);
        margin-bottom: 4px;
    }

    [data-theme="dark"] .dm-empty h4 {
        color: var(--white);
    }

    .dm-empty p {
        font-size: 0.82rem;
        color: var(--gray-600);
    }

    [data-theme="dark"] .dm-empty p {
        color: var(--gray-400);
    }

    /* Dropdown Footer */
    .dm-footer {
        padding: 14px 20px;
        border-top: 1px solid var(--gray-100);
        background: var(--gray-50);
        text-align: center;
    }

    [data-theme="dark"] .dm-footer {
        border-color: rgba(255, 255, 255, 0.08);
        background: rgba(0, 0, 0, 0.2);
    }

    .dm-footer-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--primary);
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: all var(--transition-fast);
    }

    .dm-footer-link:hover {
        gap: 12px;
        color: var(--primary-dark);
    }

    /* ===== PROFILE DROPDOWN ===== */
    .admin-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 5px 12px 5px 5px;
        border-radius: 50px;
        cursor: pointer;
        transition: all var(--transition);
        background: var(--gray-100);
        border: 1px solid transparent;
    }

    [data-theme="dark"] .admin-profile {
        background: rgba(255, 255, 255, 0.08);
    }

    .admin-profile:hover {
        background: var(--gray-200);
        border-color: var(--gray-300);
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    [data-theme="dark"] .admin-profile:hover {
        background: rgba(255, 255, 255, 0.12);
    }

    .ap-avatar {
        position: relative;
        flex-shrink: 0;
    }

    .ap-avatar img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--white);
        box-shadow: var(--shadow-sm);
    }

    [data-theme="dark"] .ap-avatar img {
        border-color: var(--dark-2);
    }

    .ap-status {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 12px;
        height: 12px;
        background: var(--primary);
        border: 2px solid var(--white);
        border-radius: 50%;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
    }

    [data-theme="dark"] .ap-status {
        border-color: var(--dark-2);
    }

    .ap-info {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }

    .ap-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--dark);
        white-space: nowrap;
    }

    [data-theme="dark"] .ap-name {
        color: var(--white);
    }

    .ap-role {
        font-size: 0.7rem;
        color: var(--gray-600);
        text-transform: capitalize;
    }

    [data-theme="dark"] .ap-role {
        color: var(--gray-400);
    }

    .ap-arrow {
        font-size: 0.68rem;
        color: var(--gray-500);
        transition: transform var(--transition);
    }

    .dropdown.open .ap-arrow {
        transform: rotate(180deg);
    }

    /* Profile Dropdown Menu */
    .profile-dropdown .dropdown-menu {
        min-width: 300px;
    }

    .pd-header {
        padding: 18px;
        background: var(--gradient-primary);
        color: var(--white);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .pd-header img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: 3px solid rgba(255, 255, 255, 0.3);
    }

    .pdh-info h4 {
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .pdh-info p {
        font-size: 0.75rem;
        opacity: 0.88;
        margin: 0;
    }

    .pd-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        padding: 14px 18px;
        border-bottom: 1px solid var(--gray-100);
        background: var(--gray-50);
    }

    [data-theme="dark"] .pd-stats {
        border-color: rgba(255, 255, 255, 0.08);
        background: rgba(0, 0, 0, 0.2);
    }

    .pd-stat {
        text-align: center;
    }

    .pd-stat-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary);
        font-family: 'Space Grotesk', sans-serif;
    }

    .pd-stat-label {
        font-size: 0.68rem;
        color: var(--gray-600);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 2px;
    }

    [data-theme="dark"] .pd-stat-label {
        color: var(--gray-400);
    }

    .pd-menu {
        padding: 8px;
    }

    .pd-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 14px;
        border-radius: var(--radius);
        cursor: pointer;
        transition: all var(--transition-fast);
        text-decoration: none;
        color: var(--dark);
        font-size: 0.88rem;
        font-weight: 500;
        margin-bottom: 2px;
    }

    [data-theme="dark"] .pd-item {
        color: var(--gray-200);
    }

    .pd-item:hover {
        background: var(--gray-50);
        transform: translateX(3px);
    }

    [data-theme="dark"] .pd-item:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .pd-item i:first-child {
        width: 20px;
        color: var(--gray-600);
        font-size: 0.95rem;
    }

    [data-theme="dark"] .pd-item i:first-child {
        color: var(--gray-400);
    }

    .pd-item span {
        flex: 1;
    }

    .pd-item .pd-arrow {
        font-size: 0.7rem;
        color: var(--gray-400);
        opacity: 0;
        transition: all var(--transition-fast);
    }

    .pd-item:hover .pd-arrow {
        opacity: 1;
        transform: translateX(2px);
    }

    .pd-divider {
        height: 1px;
        background: var(--gray-100);
        margin: 6px 8px;
    }

    [data-theme="dark"] .pd-divider {
        background: rgba(255, 255, 255, 0.08);
    }

    .pd-item.danger {
        color: var(--danger);
    }

    .pd-item.danger i:first-child {
        color: var(--danger);
    }

    .pd-item.danger:hover {
        background: rgba(239, 68, 68, 0.08);
    }

    /* ===== COMMAND PALETTE ===== */
    .command-palette {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(8px);
        z-index: 10000;
        align-items: flex-start;
        justify-content: center;
        padding-top: 15vh;
        animation: fadeIn 0.2s;
    }

    .command-palette.open {
        display: flex;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .cp-container {
        width: 100%;
        max-width: 620px;
        background: var(--white);
        border-radius: var(--radius-2xl);
        overflow: hidden;
        box-shadow: var(--shadow-xl);
        animation: cpSlideDown 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    [data-theme="dark"] .cp-container {
        background: var(--dark-2);
    }

    @keyframes cpSlideDown {
        from { opacity: 0; transform: translateY(-20px) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .cp-search {
        display: flex;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid var(--gray-100);
        gap: 14px;
    }

    [data-theme="dark"] .cp-search {
        border-color: rgba(255, 255, 255, 0.08);
    }

    .cp-search i {
        font-size: 1.3rem;
        color: var(--primary);
    }

    .cp-search input {
        flex: 1;
        border: none;
        outline: none;
        font-size: 1.1rem;
        font-family: inherit;
        background: transparent;
        color: var(--dark);
    }

    [data-theme="dark"] .cp-search input {
        color: var(--white);
    }

    .cp-search input::placeholder {
        color: var(--gray-400);
    }

    .cp-close-hint {
        padding: 3px 8px;
        background: var(--gray-100);
        color: var(--gray-600);
        border-radius: var(--radius-sm);
        font-size: 0.72rem;
        font-weight: 600;
        font-family: 'Courier New', monospace;
    }

    [data-theme="dark"] .cp-close-hint {
        background: rgba(255, 255, 255, 0.1);
        color: var(--gray-400);
    }

    .cp-results {
        max-height: 420px;
        overflow-y: auto;
        padding: 12px;
    }

    .cp-group-label {
        padding: 8px 12px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--gray-500);
    }

    .cp-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 14px;
        border-radius: var(--radius);
        cursor: pointer;
        transition: all var(--transition-fast);
        margin-bottom: 4px;
    }

    .cp-item:hover,
    .cp-item.selected {
        background: var(--gray-50);
    }

    [data-theme="dark"] .cp-item:hover,
    [data-theme="dark"] .cp-item.selected {
        background: rgba(255, 255, 255, 0.05);
    }

    .cp-item-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius);
        background: var(--gray-100);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gray-700);
        font-size: 1rem;
        flex-shrink: 0;
        transition: all var(--transition-fast);
    }

    [data-theme="dark"] .cp-item-icon {
        background: rgba(255, 255, 255, 0.08);
        color: var(--gray-300);
    }

    .cp-item.selected .cp-item-icon {
        background: var(--gradient-primary);
        color: var(--white);
    }

    .cp-item-text {
        flex: 1;
        min-width: 0;
    }

    .cp-item-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 2px;
    }

    [data-theme="dark"] .cp-item-title {
        color: var(--white);
    }

    .cp-item-desc {
        font-size: 0.78rem;
        color: var(--gray-600);
    }

    [data-theme="dark"] .cp-item-desc {
        color: var(--gray-400);
    }

    .cp-item-shortcut {
        font-size: 0.72rem;
        color: var(--gray-500);
        font-family: 'Courier New', monospace;
        padding: 2px 8px;
        background: var(--gray-100);
        border-radius: 4px;
    }

    [data-theme="dark"] .cp-item-shortcut {
        background: rgba(255, 255, 255, 0.08);
        color: var(--gray-400);
    }

    .cp-footer {
        padding: 12px 20px;
        background: var(--gray-50);
        border-top: 1px solid var(--gray-100);
        display: flex;
        gap: 18px;
        justify-content: center;
    }

    [data-theme="dark"] .cp-footer {
        background: rgba(0, 0, 0, 0.2);
        border-color: rgba(255, 255, 255, 0.08);
    }

    .cp-hint {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        color: var(--gray-600);
    }

    [data-theme="dark"] .cp-hint {
        color: var(--gray-400);
    }

    .cp-hint kbd {
        padding: 2px 7px;
        background: var(--gray-200);
        color: var(--gray-700);
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        font-weight: 600;
        font-size: 0.7rem;
    }

    [data-theme="dark"] .cp-hint kbd {
        background: rgba(255, 255, 255, 0.1);
        color: var(--gray-300);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .admin-header {
            padding: 12px 20px;
        }
        
        .header-search {
            max-width: 260px;
        }
        
        .hs-shortcut {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .admin-header {
            padding: 10px 16px;
            gap: 10px;
        }
        
        .header-search {
            display: none;
        }
        
        .ap-info {
            display: none;
        }
        
        .ap-arrow {
            display: none;
        }
        
        .admin-profile {
            padding: 4px;
        }
        
        .dropdown-menu {
            right: -10px;
        }
        
        .notif-dropdown .dropdown-menu {
            min-width: 320px;
            right: -60px;
        }
        
        .header-breadcrumb .hb-badge {
            display: none;
        }
        
        .cp-container {
            margin: 0 16px;
        }
    }

    @media (max-width: 480px) {
        .hb-home {
            display: none;
        }
        
        .hb-separator {
            display: none;
        }
        
        .notif-dropdown .dropdown-menu {
            right: -100px;
            min-width: 300px;
        }
    }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    
    <main class="admin-main">
        <!-- ===== HEADER ULTIMATE ===== -->
        <header class="admin-header">
            <!-- LEFT SIDE -->
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar (Ctrl+B)" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Breadcrumb -->
                <div class="header-breadcrumb">
                    <a href="dashboard.php" class="hb-home" title="Dashboard" aria-label="Go to dashboard">
                        <i class="fas fa-home"></i>
                    </a>
                    <i class="fas fa-chevron-right hb-separator"></i>
                    <div class="hb-current">
                        <span class="hb-title"><?= clean($breadcrumbTitle) ?></span>
                        <?php if ($currentPage === 'dashboard'): ?>
                        <span class="hb-badge">Home</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- SEARCH -->
            <div class="header-search">
                <div class="hs-wrapper">
                    <i class="fas fa-search hs-icon"></i>
                    <input type="text" class="hs-input" id="globalSearch" placeholder="Cari menu, data, atau aksi..." autocomplete="off" aria-label="Global search">
                    <div class="hs-shortcut">
                        <span class="hs-kbd">Ctrl</span>
                        <span class="hs-kbd">K</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="header-right">
                <!-- View Site -->
                <button class="header-icon-btn" id="viewSiteBtn" title="Lihat Website" aria-label="View website">
                    <i class="fas fa-external-link-alt"></i>
                </button>

                <!-- Theme Toggle -->
                <button class="header-icon-btn theme-toggle" id="themeToggle" title="Toggle Dark Mode" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>
                
                <!-- Notifications -->
                <div class="dropdown notif-dropdown" id="notifDropdown">
                    <button class="header-icon-btn" title="Notifikasi" aria-label="Notifications" aria-haspopup="true">
                        <i class="fas fa-bell"></i>
                        <?php if ($totalNotif > 0): ?>
                            <span class="hib-badge" aria-label="<?= $totalNotif ?> notifikasi"><?= $totalNotif > 99 ? '99+' : $totalNotif ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu" role="menu">
                        <div class="dm-header">
                            <div class="dmh-left">
                                <h4><i class="fas fa-bell"></i> Notifikasi</h4>
                                <p><?= $totalNotif ?> notifikasi belum dibaca</p>
                            </div>
                            <div class="dmh-actions">
                                <button class="dm-action-btn" id="markAllRead" title="Tandai semua dibaca" aria-label="Mark all as read">
                                    <i class="fas fa-check-double"></i>
                                </button>
                                <button class="dm-action-btn" id="notifSettings" title="Pengaturan" aria-label="Notification settings">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Tabs -->
                        <div class="dm-tabs" role="tablist">
                            <button class="dm-tab active" data-filter="all" role="tab" aria-selected="true">
                                <i class="fas fa-inbox"></i>
                                <span>Semua</span>
                                <span class="dm-tab-count"><?= $totalNotif ?></span>
                            </button>
                            <button class="dm-tab" data-filter="kontak" role="tab">
                                <i class="fas fa-envelope"></i>
                                <span>Pesan</span>
                                <span class="dm-tab-count"><?= $notifKontak ?></span>
                            </button>
                            <button class="dm-tab" data-filter="pendaftaran" role="tab">
                                <i class="fas fa-user-plus"></i>
                                <span>Daftar</span>
                                <span class="dm-tab-count"><?= $notifPendaftar ?></span>
                            </button>
                        </div>

                        <!-- List -->
                        <div class="dm-list" id="notifList">
                            <?php if (empty($notifications)): ?>
                            <div class="dm-empty">
                                <i class="fas fa-bell-slash"></i>
                                <h4>Tidak ada notifikasi</h4>
                                <p>Semua sudah beres, Komandan! 🎉</p>
                            </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $n): 
                                    $iconClass = $n['icon'] ?? 'fa-bell';
                                    $tipeClass = $n['tipe'] ?? 'default';
                                    $link = $n['link'] ?? '#';
                                    $judul = $n['judul'] ?? $n['title'] ?? 'Notifikasi';
                                    $pesan = $n['pesan'] ?? $n['message'] ?? '';
                                    $created = $n['created_at'] ?? date('Y-m-d H:i:s');
                                ?>
                                <a href="<?= htmlspecialchars($link) ?>" class="notif-item unread" data-type="<?= htmlspecialchars($tipeClass) ?>" role="menuitem">
                                    <div class="ni-icon <?= htmlspecialchars($tipeClass) ?>">
                                        <i class="fas <?= htmlspecialchars($iconClass) ?>"></i>
                                    </div>
                                    <div class="ni-body">
                                        <div class="ni-title"><?= clean($judul) ?></div>
                                        <div class="ni-message"><?= clean($pesan) ?></div>
                                        <div class="ni-time">
                                            <i class="fas fa-clock"></i>
                                            <span data-time="<?= htmlspecialchars($created) ?>"><?= function_exists('formatTanggal') ? formatTanggal($created) : date('d M Y H:i', strtotime($created)) ?></span>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="dm-footer">
                            <a href="kontak.php" class="dm-footer-link">
                                Lihat Semua Notifikasi
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Profile -->
                <div class="dropdown profile-dropdown" id="profileDropdown">
                    <div class="admin-profile" role="button" aria-haspopup="true" aria-expanded="false">
                        <div class="ap-avatar">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($adminName) ?>&background=10B981&color=fff&bold=true&size=80" alt="<?= clean($adminName) ?>">
                            <span class="ap-status"></span>
                        </div>
                        <div class="ap-info">
                            <span class="ap-name"><?= clean($adminName) ?></span>
                            <span class="ap-role"><?= ucfirst($adminRole) ?></span>
                        </div>
                        <i class="fas fa-chevron-down ap-arrow"></i>
                    </div>
                    <div class="dropdown-menu" role="menu">
                        <div class="pd-header">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($adminName) ?>&background=fff&color=10B981&bold=true&size=96" alt="<?= clean($adminName) ?>">
                            <div class="pdh-info">
                                <h4><?= clean($adminName) ?></h4>
                                <p><?= clean($adminUsername) ?>@informatikapro.ac.id</p>
                            </div>
                        </div>
                        
                        <div class="pd-stats">
                            <div class="pd-stat">
                                <div class="pd-stat-value"><?= $profileStats['posts'] ?></div>
                                <div class="pd-stat-label">Posts</div>
                            </div>
                            <div class="pd-stat">
                                <div class="pd-stat-value"><?= $profileStats['tasks'] ?></div>
                                <div class="pd-stat-label">Tasks</div>
                            </div>
                            <div class="pd-stat">
                                <div class="pd-stat-value"><?= $profileStats['days'] ?></div>
                                <div class="pd-stat-label">Days</div>
                            </div>
                        </div>
                        
                        <div class="pd-menu">
                            <a href="profile.php" class="pd-item" role="menuitem">
                                <i class="fas fa-user"></i>
                                <span>Profil Saya</span>
                                <i class="fas fa-chevron-right pd-arrow"></i>
                            </a>
                            <a href="settings.php" class="pd-item" role="menuitem">
                                <i class="fas fa-cog"></i>
                                <span>Pengaturan</span>
                                <i class="fas fa-chevron-right pd-arrow"></i>
                            </a>
                            <a href="change-password.php" class="pd-item" role="menuitem">
                                <i class="fas fa-key"></i>
                                <span>Ubah Password</span>
                                <i class="fas fa-chevron-right pd-arrow"></i>
                            </a>
                            <a href="activity-logs.php" class="pd-item" role="menuitem">
                                <i class="fas fa-history"></i>
                                <span>Aktivitas Saya</span>
                                <i class="fas fa-chevron-right pd-arrow"></i>
                            </a>
                            <div class="pd-divider"></div>
                            <a href="#" class="pd-item" id="helpLink" role="menuitem">
                                <i class="fas fa-question-circle"></i>
                                <span>Bantuan</span>
                                <i class="fas fa-chevron-right pd-arrow"></i>
                            </a>
                            <div class="pd-divider"></div>
                            <a href="logout.php" class="pd-item danger" role="menuitem">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Keluar</span>
                                <i class="fas fa-chevron-right pd-arrow"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- ===== COMMAND PALETTE ===== -->
        <div class="command-palette" id="commandPalette" role="dialog" aria-label="Command palette">
            <div class="cp-container">
                <div class="cp-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="cpSearchInput" placeholder="Ketik untuk mencari menu, aksi, atau data..." autocomplete="off" aria-label="Command search">
                    <span class="cp-close-hint">ESC</span>
                </div>
                <div class="cp-results" id="cpResults">
                    <div class="cp-group-label">Aksi Cepat</div>
                    <div class="cp-item" data-url="berita.php?action=add">
                        <div class="cp-item-icon"><i class="fas fa-newspaper"></i></div>
                        <div class="cp-item-text">
                            <div class="cp-item-title">Buat Berita Baru</div>
                            <div class="cp-item-desc">Tulis artikel atau pengumuman</div>
                        </div>
                        <span class="cp-item-shortcut">N</span>
                    </div>
                    <div class="cp-item" data-url="agenda.php?action=add">
                        <div class="cp-item-icon"><i class="fas fa-calendar-plus"></i></div>
                        <div class="cp-item-text">
                            <div class="cp-item-title">Tambah Agenda</div>
                            <div class="cp-item-desc">Jadwal kegiatan baru</div>
                        </div>
                    </div>
                    <div class="cp-item" data-url="pendaftaran.php">
                        <div class="cp-item-icon"><i class="fas fa-user-plus"></i></div>
                        <div class="cp-item-text">
                            <div class="cp-item-title">Lihat Pendaftar</div>
                            <div class="cp-item-desc">Calon mahasiswa baru</div>
                        </div>
                    </div>
                    <div class="cp-item" data-url="kontak.php">
                        <div class="cp-item-icon"><i class="fas fa-envelope"></i></div>
                        <div class="cp-item-text">
                            <div class="cp-item-title">Pesan Masuk</div>
                            <div class="cp-item-desc">Kelola inbox pesan</div>
                        </div>
                    </div>
                    
                    <div class="cp-group-label">Navigasi</div>
                    <div class="cp-item" data-url="dashboard.php">
                        <div class="cp-item-icon"><i class="fas fa-tachometer-alt"></i></div>
                        <div class="cp-item-text">
                            <div class="cp-item-title">Dashboard</div>
                            <div class="cp-item-desc">Halaman utama admin</div>
                        </div>
                    </div>
                    <div class="cp-item" data-url="analytics.php">
                        <div class="cp-item-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="cp-item-text">
                            <div class="cp-item-title">Analitik</div>
                            <div class="cp-item-desc">Statistik website</div>
                        </div>
                    </div>
                    <div class="cp-item" data-url="settings.php">
                        <div class="cp-item-icon"><i class="fas fa-cog"></i></div>
                        <div class="cp-item-text">
                            <div class="cp-item-title">Pengaturan</div>
                            <div class="cp-item-desc">Konfigurasi sistem</div>
                        </div>
                    </div>
                    <div class="cp-item" data-url="activity-logs.php">
                        <div class="cp-item-icon"><i class="fas fa-history"></i></div>
                        <div class="cp-item-text">
                            <div class="cp-item-title">Log Aktivitas</div>
                            <div class="cp-item-desc">History sistem</div>
                        </div>
                    </div>
                </div>
                <div class="cp-footer">
                    <div class="cp-hint">
                        <kbd>↑↓</kbd> Navigasi
                    </div>
                    <div class="cp-hint">
                        <kbd>Enter</kbd> Pilih
                    </div>
                    <div class="cp-hint">
                        <kbd>Esc</kbd> Tutup
                    </div>
                </div>
            </div>
        </div>