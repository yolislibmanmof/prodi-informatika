<?php
// ===== SIDEBAR CONFIGURATION =====
$db = $db ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);

$menu = [
    'main' => [
        'label' => 'Utama',
        'items' => [
            'dashboard.php' => ['Dashboard', 'fas fa-tachometer-alt', 'Overview & statistik'],
        ]
    ],
    'content' => [
        'label' => 'Konten',
        'items' => [
            'berita.php' => ['Berita', 'fas fa-newspaper', 'Kelola artikel'],
            'pengumuman.php' => ['Pengumuman', 'fas fa-bullhorn', 'Info penting'],
            'galeri.php' => ['Galeri', 'fas fa-images', 'Foto & video'],
            'faq.php' => ['FAQ', 'fas fa-question-circle', 'Pertanyaan umum'],
            'komentar.php' => ['Komentar', 'fas fa-comments', 'Moderasi komentar'],
            'media.php' => ['Media Library', 'fas fa-photo-video', 'File manager'],
        ]
    ],
    'akademik' => [
        'label' => 'Akademik',
        'items' => [
            'kurikulum.php' => ['Kurikulum', 'fas fa-book', 'Struktur kurikulum'],
            'konsentrasi.php' => ['Konsentrasi', 'fas fa-layer-group', 'Peminatan'],
            'dosen.php' => ['Dosen', 'fas fa-chalkboard-teacher', 'Data pengajar'],
            'fasilitas.php' => ['Fasilitas', 'fas fa-flask', 'Lab & sarana'],
            'dokumen.php' => ['Dokumen', 'fas fa-file-download', 'File akademik'],
            'agenda.php' => ['Agenda', 'fas fa-calendar-alt', 'Jadwal kegiatan'],
        ]
    ],
    'prestasi' => [
        'label' => 'Prestasi & Karir',
        'items' => [
            'prestasi.php' => ['Prestasi', 'fas fa-trophy', 'Pencapaian'],
            'prospek.php' => ['Prospek Karir', 'fas fa-briefcase', 'Peluang kerja'],
            'alumni.php' => ['Alumni', 'fas fa-user-graduate', 'Jejaring alumni'],
            'mitra.php' => ['Mitra', 'fas fa-handshake', 'Kerjasama'],
        ]
    ],
    'users' => [
        'label' => 'Pengguna',
        'items' => [
            'pendaftaran.php' => ['Pendaftaran', 'fas fa-user-plus', 'Calon maba'],
            'kontak.php' => ['Pesan Kontak', 'fas fa-envelope', 'Inbox pesan'],
            'testimoni.php' => ['Testimoni', 'fas fa-quote-right', 'Review'],
            'newsletter.php' => ['Newsletter', 'fas fa-paper-plane', 'Subscribers'],
        ]
    ],
    'monitoring' => [
        'label' => 'Monitoring',
        'items' => [
            'analytics.php' => ['Analitik', 'fas fa-chart-line', 'Traffic & stats'],
            'activity-logs.php' => ['Log Aktivitas', 'fas fa-history', 'History sistem'],
            'security-logs.php' => ['Log Keamanan', 'fas fa-shield-alt', 'Audit trail'],
        ]
    ],
    'settings' => [
        'label' => 'Sistem',
        'items' => [
            'users.php' => ['Manajemen User', 'fas fa-users-cog', 'Admin accounts'],
            'settings.php' => ['Pengaturan', 'fas fa-cog', 'Konfigurasi'],
            'email-templates.php' => ['Template Email', 'fas fa-envelope-open-text', 'Email design'],
            'api-keys.php' => ['API Keys', 'fas fa-key', 'Integrasi'],
        ]
    ]
];

// ===== BADGE DATA (Anti-Error) =====
$badgeData = ['kontak.php' => 0, 'pendaftaran.php' => 0, 'komentar.php' => 0, 'newsletter.php' => 0];
if ($db) {
    try { $badgeData['kontak.php'] = (int)$db->query("SELECT COUNT(*) FROM kontak WHERE status='baru'")->fetchColumn(); } catch (Throwable $e) {}
    try { $badgeData['pendaftaran.php'] = (int)$db->query("SELECT COUNT(*) FROM pendaftaran WHERE status='pending'")->fetchColumn(); } catch (Throwable $e) {}
    try { $badgeData['komentar.php'] = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn(); } catch (Throwable $e) {}
    try { $badgeData['newsletter.php'] = (int)$db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status='pending'")->fetchColumn(); } catch (Throwable $e) {}
}

// ===== USER STATS =====
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$adminId = (int)($_SESSION['admin_id'] ?? 0);

$userStats = ['berita' => 0, 'agenda' => 0];
if ($db) {
    try { $userStats['berita'] = (int)$db->query("SELECT COUNT(*) FROM berita WHERE author_id = {$adminId}")->fetchColumn(); } catch (Throwable $e) {}
    try { $userStats['agenda'] = (int)$db->query("SELECT COUNT(*) FROM agenda WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(); } catch (Throwable $e) {}
}

$quickActions = [
    ['url' => 'berita.php?action=add', 'icon' => 'fas fa-newspaper', 'label' => 'Berita', 'color' => '#3B82F6'],
    ['url' => 'agenda.php?action=add', 'icon' => 'fas fa-calendar-plus', 'label' => 'Agenda', 'color' => '#8B5CF6'],
    ['url' => 'pengumuman.php?action=add', 'icon' => 'fas fa-bullhorn', 'label' => 'Pengumuman', 'color' => '#F59E0B'],
    ['url' => 'dokumen.php?action=add', 'icon' => 'fas fa-file-upload', 'label' => 'Dokumen', 'color' => '#10B981'],
];
?>

<aside class="sidebar ultimate-sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
    <!-- ===== SIDEBAR HEADER ===== -->
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo" aria-label="Dashboard">
            <div class="logo-icon-wrap">
                <i class="fas fa-code"></i>
                <div class="logo-glow"></div>
            </div>
            <span>Informatika</span>
        </a>
        <button class="sidebar-close-mobile" id="sidebarCloseMobile" aria-label="Close sidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- ===== USER CARD (Glassmorphism) ===== -->
    <div class="sidebar-user-card">
        <div class="suc-avatar">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($adminName) ?>&background=10B981&color=fff&bold=true&size=80" alt="<?= clean($adminName) ?>">
            <span class="suc-status"></span>
        </div>
        <div class="suc-info">
            <div class="suc-name"><?= clean($adminName) ?></div>
            <div class="suc-role"><?= clean(ucfirst($adminRole)) ?></div>
        </div>
    </div>

    <!-- ===== QUICK ACTIONS ===== -->
    <div class="sidebar-quick-actions">
        <div class="sqa-grid">
            <?php foreach ($quickActions as $qa): ?>
            <a href="<?= clean($qa['url']) ?>" class="sqa-item" style="--qa-color: <?= clean($qa['color']) ?>" title="<?= clean($qa['label']) ?>">
                <i class="<?= clean($qa['icon']) ?>"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ===== NAVIGATION ===== -->
    <nav class="sidebar-nav" aria-label="Sidebar navigation">
        <?php foreach ($menu as $groupKey => $group): ?>
        <div class="sidebar-group">
            <div class="sidebar-group-label"><?= clean($group['label']) ?></div>
            
            <?php foreach ($group['items'] as $file => $data): 
                $active = ($currentPage === $file) ? 'active' : '';
                $badge = $badgeData[$file] ?? 0;
            ?>
            <a href="<?= clean($file) ?>" class="sidebar-item <?= $active ?>" data-tooltip="<?= clean($data[2] ?? $data[0]) ?>">
                <i class="<?= clean($data[1]) ?>"></i>
                <span><?= clean($data[0]) ?></span>
                <?php if ($badge > 0): ?>
                    <span class="sidebar-badge pulse-badge"><?= $badge > 99 ? '99+' : $badge ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </nav>

    <!-- ===== SIDEBAR FOOTER ===== -->
    <div class="sidebar-footer">
        <div class="sidebar-server-status">
            <div class="sss-indicator">
                <span class="sss-dot"></span>
                <span class="sss-text">Server Online</span>
            </div>
            <span class="sss-version">v4.0</span>
        </div>

        <a href="logout.php" class="sidebar-item logout" title="Keluar dari sistem" aria-label="Logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Keluar</span>
        </a>
    </div>

    <!-- ===== COLLAPSE BUTTON ===== -->
    <button class="sidebar-collapse-btn" id="collapseSidebar" title="Ciutkan Sidebar" aria-label="Toggle sidebar collapse">
        <i class="fas fa-chevron-left"></i>
    </button>
</aside>

<!-- ===== MOBILE OVERLAY ===== -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
/* ============================================================
   SIDEBAR ULTIMATE MULTIMATE EDITION v4.0
   Glassmorphism, Smooth Transitions, Theme-Aware
============================================================ */

.ultimate-sidebar {
    background: var(--bg-secondary) !important;
    color: var(--text-primary) !important;
    border-right: 1px solid var(--border-color) !important;
    transition: all var(--transition) !important;
}

/* Logo Glow Effect */
.logo-icon-wrap {
    position: relative;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border-radius: var(--radius);
    color: var(--white);
    font-size: 1.1rem;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.logo-glow {
    position: absolute;
    inset: -4px;
    border-radius: var(--radius-lg);
    background: var(--primary);
    opacity: 0.3;
    filter: blur(8px);
    animation: logoPulse 3s infinite ease-in-out;
}
@keyframes logoPulse {
    0%, 100% { transform: scale(1); opacity: 0.3; }
    50% { transform: scale(1.2); opacity: 0.1; }
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--text-primary) !important;
    text-decoration: none;
    font-weight: 700;
    font-size: 1.1rem;
    font-family: 'Space Grotesk', sans-serif;
}

/* User Card Glassmorphism */
.sidebar-user-card {
    display: flex; align-items: center; gap: 12px; padding: 12px 16px; margin: 0 12px 16px;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(59, 130, 246, 0.05));
    backdrop-filter: blur(10px);
    border: 1px solid rgba(16, 185, 129, 0.15);
    border-radius: var(--radius-lg); 
    transition: all var(--transition);
}
.sidebar-user-card:hover { 
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(59, 130, 246, 0.08));
    border-color: var(--primary); 
    transform: translateY(-2px); 
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.1);
}
.suc-avatar { position: relative; flex-shrink: 0; }
.suc-avatar img { width: 36px; height: 36px; border-radius: 50%; border: 2px solid var(--border-color); }
.suc-status { position: absolute; bottom: 0; right: 0; width: 10px; height: 10px; background: var(--primary); border: 2px solid var(--bg-secondary); border-radius: 50%; }
.suc-info { flex: 1; min-width: 0; }
.suc-name { font-size: 0.85rem; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.suc-role { font-size: 0.7rem; color: var(--text-secondary); text-transform: capitalize; }

/* Quick Actions */
.sidebar-quick-actions { padding: 0 12px 16px; }
.sqa-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.sqa-item {
    aspect-ratio: 1; border-radius: var(--radius); background: var(--bg-tertiary); border: 1px solid var(--border-color);
    display: flex; align-items: center; justify-content: center; color: var(--text-secondary); text-decoration: none; font-size: 1rem; transition: all var(--transition);
}
.sqa-item:hover { background: var(--qa-color); color: var(--white); border-color: transparent; transform: translateY(-3px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }

/* Navigation Items */
.sidebar-group-label {
    color: var(--text-tertiary) !important;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 0 16px 8px;
}

.sidebar-item {
    color: var(--text-secondary) !important;
    display: flex; align-items: center; gap: 12px; padding: 10px 16px; margin: 2px 8px;
    border-radius: var(--radius); text-decoration: none; font-size: 0.88rem;
    transition: all var(--transition); position: relative; overflow: hidden;
}

.sidebar-item:hover {
    background: var(--bg-tertiary) !important;
    color: var(--text-primary) !important;
    transform: translateX(4px);
}

/* Active State Ultimate */
.sidebar-item.active {
    background: linear-gradient(90deg, rgba(16, 185, 129, 0.12) 0%, transparent 100%) !important;
    color: var(--primary) !important;
    font-weight: 600;
    border-left: 3px solid var(--primary);
    margin-left: 5px; /* Offset for border */
}
.sidebar-item.active i {
    color: var(--primary) !important;
    filter: drop-shadow(0 0 6px rgba(16, 185, 129, 0.4));
}

/* Badge Pulse */
.sidebar-badge {
    margin-left: auto; background: var(--danger); color: var(--white);
    font-size: 0.65rem; font-weight: 700; min-width: 18px; height: 18px;
    padding: 0 5px; border-radius: 9px; display: flex; align-items: center; justify-content: center;
}
.pulse-badge {
    animation: badgePulse 2s infinite;
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
}
@keyframes badgePulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

/* Logout */
.sidebar-item.logout {
    color: var(--danger) !important; margin-top: 8px;
}
.sidebar-item.logout:hover {
    background: rgba(239, 68, 68, 0.1) !important;
    color: var(--danger) !important;
}

/* Server Status */
.sidebar-server-status {
    display: flex; align-items: center; justify-content: space-between; padding: 10px 12px;
    background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: var(--radius); margin-bottom: 12px;
}
.sss-indicator { display: flex; align-items: center; gap: 8px; }
.sss-dot { width: 8px; height: 8px; background: var(--primary); border-radius: 50%; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2); animation: statusPulse 2s infinite; }
@keyframes statusPulse { 0%, 100% { box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2); } 50% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0.05); } }
.sss-text { font-size: 0.72rem; color: var(--text-secondary); }
.sss-version { font-size: 0.65rem; color: var(--text-tertiary); font-family: monospace; }

/* Collapse Button */
.sidebar-collapse-btn {
    position: absolute; top: 28px; right: -14px; width: 28px; height: 28px; border-radius: 50%;
    background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);
    cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;
    transition: all var(--transition); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); z-index: 101;
}
.sidebar-collapse-btn:hover { background: var(--primary); color: var(--white); border-color: var(--primary); transform: scale(1.15); }
.sidebar.collapsed .sidebar-collapse-btn i { transform: rotate(180deg); }

/* Collapsed State Adjustments */
.sidebar.collapsed .sidebar-user-card, .sidebar.collapsed .sidebar-quick-actions, .sidebar.collapsed .sidebar-group-label,
.sidebar.collapsed .sidebar-item span, .sidebar.collapsed .sidebar-badge, .sidebar.collapsed .sidebar-server-status, .sidebar.collapsed .sidebar-item.logout span { display: none; }
.sidebar.collapsed .sidebar-logo span { display: none; }
.sidebar.collapsed .sidebar-header, .sidebar.collapsed .sidebar-user-card, .sidebar.collapsed .sidebar-item { justify-content: center; padding-left: 0; padding-right: 0; }
.sidebar.collapsed .sidebar-item { margin: 2px 4px; }
.sidebar.collapsed .sidebar-item i { margin: 0; }

/* Tooltip for Collapsed State */
.sidebar.collapsed .sidebar-item:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    left: calc(100% + 16px);
    top: 50%;
    transform: translateY(-50%);
    background: var(--bg-secondary);
    color: var(--text-primary);
    padding: 6px 12px;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 1000;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-color);
    pointer-events: none;
    opacity: 0;
    animation: tooltipFade 0.2s forwards;
}
@keyframes tooltipFade { to { opacity: 1; } }

/* Mobile Overlay & Close Button */
.sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 99; opacity: 0; transition: opacity var(--transition); }
.sidebar-overlay.active { display: block; opacity: 1; }
.sidebar-close-mobile { display: none; }

@media (max-width: 1024px) {
    .sidebar-close-mobile {
        display: flex !important; width: 36px; height: 36px; border-radius: var(--radius);
        background: var(--bg-tertiary); border: 1px solid var(--border-color); color: var(--text-primary);
        cursor: pointer; align-items: center; justify-content: center; transition: all var(--transition);
    }
    .sidebar-close-mobile:hover { background: var(--danger); color: var(--white); border-color: var(--danger); }
}
</style>

<script>
(function() {
    'use strict';
    const sidebar = document.getElementById('sidebar');
    const closeMobileBtn = document.getElementById('sidebarCloseMobile');
    const overlay = document.getElementById('sidebarOverlay');

    // 1. Perbaiki Tombol Silang (Close Mobile)
    if (closeMobileBtn && sidebar && overlay) {
        closeMobileBtn.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // 2. Perbaiki Klik di Luar Sidebar (Overlay)
    if (overlay && sidebar) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            this.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
})();
</script>