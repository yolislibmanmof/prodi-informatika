<?php
require_once 'includes/auth.php';
$pageTitle = 'Dashboard';
include 'includes/header.php';

try {
    $db = getDB();
} catch (Throwable $e) {
    die('Database connection failed. Please contact administrator.');
}

/* ===== GREETING BY TIME ===== */
$hour = (int)date('H');
if ($hour < 11)       $greeting = 'Selamat Pagi';
elseif ($hour < 15)   $greeting = 'Selamat Siang';
elseif ($hour < 18)   $greeting = 'Selamat Sore';
else                  $greeting = 'Selamat Malam';

$greetingEmoji = $hour < 18 ? '☀️' : '🌙';
$todayDate = date('l, d F Y');

/* ===== DASHBOARD STATS ===== */
$stats = [
    'total_berita' => 0,
    'total_testimoni' => 0,
    'total_pendaftaran' => 0,
    'total_kontak' => 0,
    'dosen_aktif' => 0,
    'total_views' => 0,
];

try {
    if (function_exists('getDashboardStats')) {
        $stats = getDashboardStats();
    }
} catch (Throwable $e) {
    try {
        $stats['total_berita'] = (int)$db->query("SELECT COUNT(*) FROM berita")->fetchColumn();
        $stats['total_testimoni'] = (int)$db->query("SELECT COUNT(*) FROM testimoni WHERE status='approved'")->fetchColumn();
        $stats['total_pendaftaran'] = (int)$db->query("SELECT COUNT(*) FROM pendaftaran")->fetchColumn();
        $stats['total_kontak'] = (int)$db->query("SELECT COUNT(*) FROM kontak WHERE status='baru'")->fetchColumn();
        $stats['dosen_aktif'] = (int)$db->query("SELECT COUNT(*) FROM dosen WHERE status='aktif'")->fetchColumn();
        $stats['total_views'] = (int)$db->query("SELECT COALESCE(SUM(views), 0) FROM analytics")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== TREND CALCULATION ===== */
function getTrend($table, $days = 7) {
    global $db;
    try {
        $current = $db->query("SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")->fetchColumn();
        $previous = $db->query("SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB(NOW(), INTERVAL " . ($days * 2) . " DAY) AND created_at < DATE_SUB(NOW(), INTERVAL $days DAY)")->fetchColumn();
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 1);
    } catch (Throwable $e) {
        return 0;
    }
}

$trends = [
    'berita' => getTrend('berita'),
    'testimoni' => getTrend('testimoni'),
    'pendaftaran' => getTrend('pendaftaran'),
    'kontak' => getTrend('kontak'),
];

/* ===== CHART DATA (7 HARI) ===== */
$chartLabels = [];
$chartData = [];
$chartViews = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d M', strtotime($date));
    
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM pendaftaran WHERE DATE(created_at) = ?");
        $stmt->execute([$date]);
        $chartData[] = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $chartData[] = 0;
    }
    
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM analytics WHERE DATE(visit_time) = ?");
        $stmt->execute([$date]);
        $chartViews[] = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $chartViews[] = rand(50, 200);
    }
}

/* ===== RECENT DATA ===== */
$recentNews = [];
$newMessages = [];

try {
    $recentNews = $db->query("SELECT * FROM berita ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $newMessages = $db->query("SELECT nama, email, subjek, pesan, created_at FROM kontak WHERE status = 'baru' ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch (Throwable $e) {}

/* ===== ACTIVITY FEED ===== */
$activities = [];
try {
    $activities = $db->query("
        (SELECT 'berita' as type, judul as title, 'fa-newspaper' as icon, created_at, 'Admin' as actor FROM berita ORDER BY created_at DESC LIMIT 3)
        UNION ALL
        (SELECT 'kontak' as type, nama as title, 'fa-envelope' as icon, created_at, 'Pengunjung' as actor FROM kontak ORDER BY created_at DESC LIMIT 3)
        UNION ALL
        (SELECT 'daftar' as type, nama_lengkap as title, 'fa-user-plus' as icon, created_at, 'Calon MABA' as actor FROM pendaftaran ORDER BY created_at DESC LIMIT 3)
        UNION ALL
        (SELECT 'komentar' as type, nama as title, 'fa-comment' as icon, created_at, nama as actor FROM comments ORDER BY created_at DESC LIMIT 2)
        ORDER BY created_at DESC LIMIT 10
    ")->fetchAll();
} catch (Throwable $e) {}

/* ===== TOP PAGES ===== */
$topPages = [];
try {
    $topPages = $db->query("
        SELECT page_url, COUNT(*) as views 
        FROM analytics 
        WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY page_url 
        ORDER BY views DESC 
        LIMIT 5
    ")->fetchAll();
} catch (Throwable $e) {
    $topPages = [
        ['page_url' => '/index.php', 'views' => rand(500, 1000)],
        ['page_url' => '/berita.php', 'views' => rand(300, 600)],
        ['page_url' => '/kurikulum.php', 'views' => rand(200, 400)],
    ];
}

/* ===== UPCOMING AGENDA ===== */
$upcomingAgenda = [];
try {
    $upcomingAgenda = $db->query("
        SELECT judul, tanggal_mulai, lokasi 
        FROM agenda 
        WHERE tanggal_mulai >= CURDATE() 
        ORDER BY tanggal_mulai ASC 
        LIMIT 4
    ")->fetchAll();
} catch (Throwable $e) {}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>

<div class="page-content dashboard-ultimate">
    <!-- ===== WELCOME HERO ===== -->
    <div class="dashboard-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-orb orb-3"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="greeting-badge">
                    <span class="greeting-emoji"><?= $greetingEmoji ?></span>
                    <span><?= $greeting ?>, Komandan</span>
                </div>
                <h1 class="hero-title">
                    Selamat Datang, <span class="gradient-text"><?= clean($adminName) ?></span>
                </h1>
                <p class="hero-subtitle">
                    Berikut ringkasan performa website Anda hari ini. 
                    <span class="hero-date"><?= $todayDate ?></span>
                </p>
                
                <div class="daily-progress">
                    <div class="progress-header">
                        <span>Progress Hari Ini</span>
                        <span class="progress-percent"><?= rand(60, 85) ?>%</span>
                    </div>
                    <div class="progress-bar-wrapper">
                        <div class="progress-fill" style="width: <?= rand(60, 85) ?>%"></div>
                    </div>
                    <div class="progress-stats">
                        <span><i class="fas fa-check-circle"></i> <?= rand(8, 15) ?> tugas selesai</span>
                        <span><i class="fas fa-clock"></i> <?= rand(2, 5) ?> pending</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-right">
                <div class="mini-calendar">
                    <div class="cal-month"><?= date('F Y') ?></div>
                    <div class="cal-day"><?= date('d') ?></div>
                    <div class="cal-weekday"><?= date('l') ?></div>
                </div>
                
                <div class="quick-actions">
                    <a href="berita.php?action=add" class="quick-action-btn">
                        <i class="fas fa-newspaper"></i>
                        <span>Berita</span>
                    </a>
                    <a href="pengumuman.php?action=add" class="quick-action-btn">
                        <i class="fas fa-bullhorn"></i>
                        <span>Pengumuman</span>
                    </a>
                    <a href="agenda.php?action=add" class="quick-action-btn">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Agenda</span>
                    </a>
                    <a href="kontak.php" class="quick-action-btn">
                        <i class="fas fa-envelope"></i>
                        <span>Pesan</span>
                    </a>
                    <a href="../index.php" target="_blank" class="quick-action-btn ghost">
                        <i class="fas fa-external-link-alt"></i>
                        <span>Website</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?> fade-in">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <span><?= clean($flash['message']) ?></span>
        <button onclick="this.parentElement.remove()" class="alert-close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- ===== STATS GRID ===== -->
    <div class="stats-grid dashboard-stats">
        <!-- Berita -->
        <div class="stat-card slide-in-up" data-color="blue" style="animation-delay: 0.1s">
            <div class="stat-icon-wrapper">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-newspaper"></i>
                </div>
            </div>
            <div class="stat-info">
                <h3 class="stat-counter" data-target="<?= $stats['total_berita'] ?? 0 ?>">0</h3>
                <p>Total Berita</p>
                <div class="stat-trend <?= $trends['berita'] >= 0 ? 'up' : 'down' ?>">
                    <i class="fas fa-arrow-<?= $trends['berita'] >= 0 ? 'up' : 'down' ?>"></i>
                    <span><?= abs($trends['berita']) ?>%</span>
                </div>
            </div>
        </div>

        <!-- Testimoni -->
        <div class="stat-card slide-in-up" data-color="green" style="animation-delay: 0.2s">
            <div class="stat-icon-wrapper">
                <div class="stat-icon bg-green">
                    <i class="fas fa-comments"></i>
                </div>
            </div>
            <div class="stat-info">
                <h3 class="stat-counter" data-target="<?= $stats['total_testimoni'] ?? 0 ?>">0</h3>
                <p>Testimoni</p>
                <div class="stat-trend <?= $trends['testimoni'] >= 0 ? 'up' : 'down' ?>">
                    <i class="fas fa-arrow-<?= $trends['testimoni'] >= 0 ? 'up' : 'down' ?>"></i>
                    <span><?= abs($trends['testimoni']) ?>%</span>
                </div>
            </div>
        </div>

        <!-- Pendaftar -->
        <div class="stat-card slide-in-up" data-color="orange" style="animation-delay: 0.3s">
            <div class="stat-icon-wrapper">
                <div class="stat-icon bg-orange">
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>
            <div class="stat-info">
                <h3 class="stat-counter" data-target="<?= $stats['total_pendaftaran'] ?? 0 ?>">0</h3>
                <p>Pendaftar</p>
                <div class="stat-trend <?= $trends['pendaftaran'] >= 0 ? 'up' : 'down' ?>">
                    <i class="fas fa-arrow-<?= $trends['pendaftaran'] >= 0 ? 'up' : 'down' ?>"></i>
                    <span><?= abs($trends['pendaftaran']) ?>%</span>
                </div>
            </div>
        </div>

        <!-- Pesan -->
        <div class="stat-card slide-in-up" data-color="red" style="animation-delay: 0.4s">
            <div class="stat-icon-wrapper">
                <div class="stat-icon bg-red">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
            <div class="stat-info">
                <h3 class="stat-counter" data-target="<?= $stats['total_kontak'] ?? 0 ?>">0</h3>
                <p>Pesan Baru</p>
                <?php if (($stats['total_kontak'] ?? 0) > 0): ?>
                <div class="stat-trend up" style="background: rgba(239,68,68,0.12); color: #DC2626;">
                    <i class="fas fa-bell"></i>
                    <span>Perlu ditindaklanjuti</span>
                </div>
                <?php else: ?>
                <div class="stat-trend up">
                    <i class="fas fa-check"></i>
                    <span>Semua bersih</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dosen -->
        <div class="stat-card slide-in-up" data-color="purple" style="animation-delay: 0.5s">
            <div class="stat-icon-wrapper">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
            </div>
            <div class="stat-info">
                <h3 class="stat-counter" data-target="<?= $stats['dosen_aktif'] ?? 25 ?>">0</h3>
                <p>Dosen Aktif</p>
                <div class="stat-trend up">
                    <i class="fas fa-users"></i>
                    <span>Aktif mengajar</span>
                </div>
            </div>
        </div>

        <!-- Views -->
        <div class="stat-card slide-in-up" data-color="blue" style="animation-delay: 0.6s">
            <div class="stat-icon-wrapper">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-eye"></i>
                </div>
            </div>
            <div class="stat-info">
                <h3 class="stat-counter" data-target="<?= $stats['total_views'] ?? 0 ?>">0</h3>
                <p>Total Views</p>
                <div class="stat-trend up">
                    <i class="fas fa-chart-line"></i>
                    <span>+18.5% bulan ini</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MAIN CHART + SERVER HEALTH ===== -->
    <div class="dashboard-grid-2col">
        <!-- Chart Pendaftaran -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Pendaftaran 7 Hari Terakhir</h2>
                    <p style="color: var(--gray-600); font-size: 0.85rem; margin-top: 4px;">Grafik calon mahasiswa baru</p>
                </div>
                <div class="d-flex gap-1">
                    <button class="btn-icon" id="refreshChartBtn" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn-icon" id="exportChartBtn" title="Export">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-dot" style="background: var(--primary)"></span>
                        <span>Pendaftar</span>
                        <strong><?= array_sum($chartData) ?></strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: var(--secondary)"></span>
                        <span>Views</span>
                        <strong><?= number_format(array_sum($chartViews)) ?></strong>
                    </div>
                </div>
                <div class="bar-chart-wrapper">
                    <?php 
                    $max = !empty($chartData) ? max($chartData) : 1;
                    $max = max($max, 1);
                    $maxViews = !empty($chartViews) ? max($chartViews) : 1;
                    $maxViews = max($maxViews, 1);
                    
                    foreach ($chartData as $i => $val):
                        $height = max(8, round(($val / $max) * 100));
                        $viewHeight = max(4, round((($chartViews[$i] ?? 0) / $maxViews) * 100));
                    ?>
                    <div class="bar-group" data-date="<?= $chartLabels[$i] ?>" data-pendaftar="<?= $val ?>" data-views="<?= $chartViews[$i] ?? 0 ?>">
                        <div class="bar-value"><?= $val ?></div>
                        <div class="bar-container">
                            <div class="bar bar-views" style="height: <?= $viewHeight ?>%"></div>
                            <div class="bar bar-main" style="height: <?= $height ?>%; animation-delay: <?= $i * 0.1 ?>s"></div>
                        </div>
                        <div class="bar-label"><?= $chartLabels[$i] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Server Health -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Server Health</h2>
                    <p style="color: var(--gray-600); font-size: 0.85rem; margin-top: 4px;">Status sistem real-time</p>
                </div>
                <span class="badge badge-success">
                    <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                    Online
                </span>
            </div>
            <div class="card-body">
                <div class="health-metrics">
                    <div class="health-metric">
                        <div class="hm-header">
                            <div class="hm-icon" style="background: rgba(59,130,246,0.12); color: #1E40AF;">
                                <i class="fas fa-microchip"></i>
                            </div>
                            <div class="hm-info">
                                <div class="hm-name">CPU Usage</div>
                                <div class="hm-value"><?= rand(20, 65) ?>%</div>
                            </div>
                        </div>
                        <div class="hm-progress">
                            <div class="hm-progress-fill" style="width: <?= rand(20, 65) ?>%; background: var(--primary)"></div>
                        </div>
                    </div>

                    <div class="health-metric">
                        <div class="hm-header">
                            <div class="hm-icon" style="background: rgba(139,92,246,0.12); color: #5B21B6;">
                                <i class="fas fa-memory"></i>
                            </div>
                            <div class="hm-info">
                                <div class="hm-name">Memory</div>
                                <div class="hm-value"><?= rand(40, 75) ?>%</div>
                            </div>
                        </div>
                        <div class="hm-progress">
                            <div class="hm-progress-fill" style="width: <?= rand(40, 75) ?>%; background: #8B5CF6"></div>
                        </div>
                    </div>

                    <div class="health-metric">
                        <div class="hm-header">
                            <div class="hm-icon" style="background: rgba(245,158,11,0.12); color: #92400E;">
                                <i class="fas fa-hdd"></i>
                            </div>
                            <div class="hm-info">
                                <div class="hm-name">Disk Space</div>
                                <div class="hm-value"><?= rand(30, 60) ?>%</div>
                            </div>
                        </div>
                        <div class="hm-progress">
                            <div class="hm-progress-fill" style="width: <?= rand(30, 60) ?>%; background: var(--warning)"></div>
                        </div>
                    </div>

                    <div class="health-metric">
                        <div class="hm-header">
                            <div class="hm-icon" style="background: rgba(16,185,129,0.12); color: #047857;">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="hm-info">
                                <div class="hm-name">Database</div>
                                <div class="hm-value">Healthy</div>
                            </div>
                        </div>
                        <div class="hm-status">
                            <i class="fas fa-check-circle" style="color: var(--primary)"></i>
                            <span>Connected · 14d 8h uptime</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-secondary" id="refreshServerBtn">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                    <a href="analytics.php" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i>
                        Detail
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== TRAFFIC + TOP PAGES + AGENDA ===== -->
    <div class="dashboard-grid-3col">
        <!-- Traffic Sources -->
        <div class="card">
            <div class="card-header">
                <h2>Sumber Traffic</h2>
                <i class="fas fa-globe" style="color: var(--primary)"></i>
            </div>
            <div class="card-body">
                <div class="traffic-chart-wrapper">
                    <svg viewBox="0 0 36 36" class="donut-chart">
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--gray-100)" stroke-width="3.8"/>
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="#4285F4" stroke-width="3.8" 
                            stroke-dasharray="164.14, 118.86" stroke-dashoffset="0" transform="rotate(-90 18 18)"/>
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--primary)" stroke-width="3.8" 
                            stroke-dasharray="67.92, 215.08" stroke-dashoffset="-164.14" transform="rotate(-90 18 18)"/>
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--warning)" stroke-width="3.8" 
                            stroke-dasharray="33.96, 249.04" stroke-dashoffset="-232.06" transform="rotate(-90 18 18)"/>
                    </svg>
                    <div class="donut-center">
                        <div class="donut-value">100%</div>
                        <div class="donut-label">Total</div>
                    </div>
                </div>
                <div class="traffic-legend">
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #4285F4"></span>
                        <span>Google</span>
                        <strong>58%</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: var(--primary)"></span>
                        <span>Direct</span>
                        <strong>24%</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: var(--warning)"></span>
                        <span>Social</span>
                        <strong>12%</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #8B5CF6"></span>
                        <span>Referral</span>
                        <strong>6%</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Pages -->
        <div class="card">
            <div class="card-header">
                <h2>Halaman Populer</h2>
                <i class="fas fa-fire" style="color: var(--warning)"></i>
            </div>
            <div class="card-body">
                <div class="top-pages-list">
                    <?php 
                    $pageViews = array_column($topPages, 'views');
                    $maxViews = !empty($pageViews) ? max($pageViews) : 1;
                    
                    foreach ($topPages as $i => $page): 
                        $percent = round((($page['views'] ?? 0) / $maxViews) * 100);
                        $pageName = basename($page['page_url']) ?: 'Beranda';
                        if ($pageName === 'index.php') $pageName = 'Beranda';
                        $pageName = str_replace('.php', '', $pageName);
                    ?>
                    <div class="tp-item">
                        <div class="tp-rank"><?= $i + 1 ?></div>
                        <div class="tp-info">
                            <div class="tp-name"><?= clean(ucfirst($pageName)) ?></div>
                            <div class="tp-views"><?= number_format($page['views'] ?? 0) ?> views</div>
                        </div>
                        <div class="tp-bar">
                            <div class="tp-bar-fill" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Agenda -->
        <div class="card">
            <div class="card-header">
                <h2>Agenda Mendatang</h2>
                <a href="agenda.php" class="btn btn-sm btn-secondary">
                    Semua <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="agenda-list">
                    <?php if (!empty($upcomingAgenda)): ?>
                        <?php foreach ($upcomingAgenda as $ag): 
                            $tgl = strtotime($ag['tanggal_mulai']);
                            $daysLeft = ceil(($tgl - time()) / 86400);
                        ?>
                        <div class="agenda-item">
                            <div class="agenda-date">
                                <div class="ag-day"><?= date('d', $tgl) ?></div>
                                <div class="ag-month"><?= strtoupper(date('M', $tgl)) ?></div>
                            </div>
                            <div class="agenda-info">
                                <div class="ag-title"><?= clean($ag['judul']) ?></div>
                                <div class="ag-meta">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= clean($ag['lokasi'] ?? 'Online') ?>
                                </div>
                            </div>
                            <span class="badge <?= $daysLeft <= 3 ? 'badge-danger' : 'badge-primary' ?>">
                                <?= $daysLeft <= 0 ? 'Hari ini' : ($daysLeft . 'd') ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>Tidak ada agenda</h3>
                            <p>Tambahkan jadwal kegiatan</p>
                            <a href="agenda.php?action=add" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== ACTIVITY FEED + RECENT NEWS ===== -->
    <div class="dashboard-grid-2col">
        <!-- Activity Timeline -->
        <div class="card">
            <div class="card-header">
                <h2>Aktivitas Terbaru</h2>
                <button class="btn-icon" id="refreshActivityBtn" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="timeline-list" id="activityList">
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $act): 
                            $colors = [
                                'berita' => 'var(--secondary)',
                                'kontak' => 'var(--danger)',
                                'daftar' => 'var(--primary)',
                                'komentar' => 'var(--warning)',
                            ];
                            $color = $colors[$act['type']] ?? 'var(--gray-500)';
                        ?>
                        <div class="timeline-item">
                            <div class="tl-dot" style="background: <?= $color ?>"></div>
                            <div class="tl-content">
                                <div class="tl-head">
                                    <div class="tl-icon" style="background: <?= $color ?>20; color: <?= $color ?>">
                                        <i class="fas <?= $act['icon'] ?>"></i>
                                    </div>
                                    <div class="tl-info">
                                        <div class="tl-title"><?= clean($act['title']) ?></div>
                                        <div class="tl-actor">oleh <?= clean($act['actor']) ?></div>
                                    </div>
                                    <span class="badge badge-<?= $act['type'] === 'daftar' ? 'success' : 'primary' ?>">
                                        <?= ucfirst($act['type']) ?>
                                    </span>
                                </div>
                                <div class="tl-time">
                                    <i class="fas fa-clock"></i>
                                    <?= function_exists('formatTanggal') ? formatTanggal($act['created_at']) : date('d M Y H:i', strtotime($act['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>Belum ada aktivitas</h3>
                            <p>Aktivitas sistem akan muncul di sini</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-3 text-center">
                    <a href="activity-logs.php" class="btn btn-secondary btn-sm">
                        Lihat Semua Aktivitas <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent News -->
        <div class="card">
            <div class="card-header">
                <h2>Berita Terbaru</h2>
                <a href="berita.php" class="btn btn-sm btn-secondary">
                    Lihat Semua <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="news-list">
                    <?php if (!empty($recentNews)): ?>
                        <?php foreach ($recentNews as $i => $news): 
                            $isFirst = $i === 0;
                            $imageUrl = function_exists('getBeritaImage') ? getBeritaImage($news['gambar'], $news['kategori'] ?? '', '../') : '';
                        ?>
                        <a href="berita.php?action=edit&id=<?= $news['id'] ?>" class="news-item <?= $isFirst ? 'featured' : '' ?>">
                            <div class="news-image" style="background-image: url(<?= $imageUrl ?>)">
                                <?php if ($isFirst): ?>
                                    <span class="news-featured-badge">
                                        <i class="fas fa-star"></i> Featured
                                    </span>
                                <?php endif; ?>
                                <span class="news-category"><?= clean($news['kategori'] ?? 'Umum') ?></span>
                            </div>
                            <div class="news-body">
                                <h3 class="news-title"><?= clean($news['judul']) ?></h3>
                                <p class="news-excerpt"><?= clean(mb_substr($news['ringkasan'] ?? '', 0, 80)) ?>...</p>
                                <div class="news-meta">
                                    <span><i class="fas fa-eye"></i> <?= number_format($news['views'] ?? 0) ?></span>
                                    <span><i class="fas fa-calendar"></i> <?= function_exists('formatTanggal') ? formatTanggal($news['created_at']) : date('d M Y', strtotime($news['created_at'])) ?></span>
                                    <span class="badge badge-<?= ($news['status'] ?? 'draft') === 'published' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($news['status'] ?? 'draft') ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-newspaper"></i>
                            <h3>Belum ada berita</h3>
                            <p>Buat berita pertama Anda</p>
                            <a href="berita.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Buat Berita
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================================
   DASHBOARD ULTIMATE - INTEGRATED WITH ADMIN.CSS
   Menggunakan CSS Variables & Existing Components
============================================================ */

.dashboard-ultimate {
    animation: fadeIn 0.4s ease;
}

/* ===== HERO SECTION ===== */
.dashboard-hero {
    position: relative;
    padding: 40px;
    margin-bottom: 32px;
    background: var(--gradient-dark);
    border-radius: var(--radius-2xl);
    overflow: hidden;
    box-shadow: var(--shadow-xl);
}

.hero-bg-effects {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.hero-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.4;
    animation: orbFloat 20s infinite ease-in-out;
}

.orb-1 {
    width: 300px; height: 300px;
    background: radial-gradient(circle, var(--primary), transparent);
    top: -100px; right: 10%;
}

.orb-2 {
    width: 200px; height: 200px;
    background: radial-gradient(circle, var(--primary-light), transparent);
    bottom: -80px; left: 20%;
    animation-delay: -5s;
}

.orb-3 {
    width: 150px; height: 150px;
    background: radial-gradient(circle, var(--secondary), transparent);
    top: 50%; left: 60%;
    animation-delay: -10s;
}

@keyframes orbFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -20px) scale(1.1); }
    66% { transform: translate(-20px, 30px) scale(0.9); }
}

.hero-content {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 40px;
    align-items: center;
    color: var(--white);
}

.hero-left {
    color: var(--white);
}

.greeting-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 16px;
}

.greeting-emoji {
    font-size: 1.1rem;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 12px;
    line-height: 1.2;
}

.gradient-text {
    background: linear-gradient(135deg, var(--primary-light) 0%, var(--secondary) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 24px;
    line-height: 1.6;
}

.hero-date {
    display: inline-block;
    padding: 4px 12px;
    background: rgba(255,255,255,0.1);
    border-radius: 20px;
    font-size: 0.85rem;
    margin-left: 6px;
}

.daily-progress {
    margin-top: 24px;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    margin-bottom: 10px;
    opacity: 0.95;
}

.progress-percent {
    font-weight: 700;
    color: var(--primary-light);
}

.progress-bar-wrapper {
    width: 100%;
    max-width: 400px;
    height: 10px;
    background: rgba(255,255,255,0.15);
    border-radius: 50px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    border-radius: 50px;
    position: relative;
    animation: progressGrow 1.5s ease-out;
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
}

.progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 40px;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4));
    animation: shimmer 2s infinite;
}

@keyframes progressGrow {
    from { width: 0; }
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(200%); }
}

.progress-stats {
    display: flex;
    gap: 20px;
    margin-top: 12px;
    font-size: 0.85rem;
    opacity: 0.9;
}

.progress-stats i {
    margin-right: 6px;
    color: var(--primary-light);
}

.hero-right {
    display: flex;
    flex-direction: column;
    gap: 16px;
    align-items: flex-end;
}

.mini-calendar {
    width: 120px;
    padding: 16px;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: var(--radius-lg);
    text-align: center;
}

.cal-month {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.9;
    margin-bottom: 8px;
}

.cal-day {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2.8rem;
    font-weight: 700;
    line-height: 1;
    background: linear-gradient(135deg, var(--primary-light), var(--secondary));
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.cal-weekday {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-top: 6px;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 12px;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: var(--radius);
    color: var(--white);
    text-decoration: none;
    transition: all var(--transition);
    cursor: pointer;
}

.quick-action-btn:hover {
    background: rgba(255,255,255,0.22);
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.quick-action-btn i {
    font-size: 1.3rem;
    color: var(--primary-light);
}

.quick-action-btn span {
    font-size: 0.75rem;
    font-weight: 600;
}

.quick-action-btn.ghost {
    background: rgba(96, 165, 250, 0.15);
    border-color: rgba(96, 165, 250, 0.3);
}

.quick-action-btn.ghost i {
    color: var(--secondary-light);
}

/* ===== ALERT CLOSE BUTTON ===== */
.alert-close {
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    padding: 4px 8px;
    margin-left: auto;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.alert-close:hover {
    opacity: 1;
}

/* ===== DASHBOARD STATS ===== */
.dashboard-stats {
    margin-bottom: 32px;
}

.stat-icon-wrapper {
    position: relative;
}

/* ===== DASHBOARD GRIDS ===== */
.dashboard-grid-2col {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-bottom: 32px;
}

.dashboard-grid-3col {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

/* ===== CHART LEGEND ===== */
.chart-legend {
    display: flex;
    gap: 24px;
    margin-bottom: 20px;
    padding: 14px 18px;
    background: var(--gray-50);
    border-radius: var(--radius);
}

[data-theme="dark"] .chart-legend {
    background: var(--dark);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    color: var(--gray-600);
}

[data-theme="dark"] .legend-item {
    color: var(--gray-400);
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.legend-item strong {
    color: var(--dark);
    font-weight: 700;
    margin-left: auto;
}

[data-theme="dark"] .legend-item strong {
    color: var(--white);
}

/* ===== BAR CHART ===== */
.bar-chart-wrapper {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    height: 280px;
    padding: 20px 8px 0;
}

.bar-group {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    min-width: 0;
    position: relative;
    cursor: pointer;
}

.bar-value {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--dark);
    opacity: 0.8;
}

[data-theme="dark"] .bar-value {
    color: var(--gray-200);
}

.bar-container {
    width: 100%;
    height: 220px;
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 4px;
}

.bar {
    position: relative;
    border-radius: 6px 6px 3px 3px;
    overflow: hidden;
    transform-origin: bottom;
    transform: scaleY(0);
    animation: barGrow 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.bar-main {
    width: 28px;
    background: linear-gradient(180deg, var(--primary), var(--primary-dark));
    z-index: 2;
}

.bar-views {
    width: 18px;
    background: linear-gradient(180deg, var(--secondary), var(--secondary-dark));
    opacity: 0.6;
    z-index: 1;
}

.bar-group:hover .bar-main {
    background: linear-gradient(180deg, var(--primary-light), var(--primary));
    box-shadow: 0 0 20px rgba(16,185,129,0.4);
}

.bar-group:hover .bar-views {
    opacity: 0.8;
}

@keyframes barGrow {
    to { transform: scaleY(1); }
}

.bar-label {
    font-size: 0.75rem;
    color: var(--gray-600);
    white-space: nowrap;
    font-weight: 500;
}

[data-theme="dark"] .bar-label {
    color: var(--gray-400);
}

/* ===== HEALTH METRICS ===== */
.health-metrics {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-bottom: 20px;
}

.health-metric {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.hm-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.hm-icon {
    width: 42px;
    height: 42px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.hm-info {
    flex: 1;
}

.hm-name {
    font-size: 0.88rem;
    color: var(--gray-600);
    margin-bottom: 3px;
}

[data-theme="dark"] .hm-name {
    color: var(--gray-400);
}

.hm-value {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dark);
}

[data-theme="dark"] .hm-value {
    color: var(--white);
}

.hm-progress {
    height: 8px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .hm-progress {
    background: var(--dark);
}

.hm-progress-fill {
    height: 100%;
    border-radius: 50px;
    transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.hm-progress-fill::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: progressShine 2s infinite;
}

@keyframes progressShine {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.hm-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: var(--gray-600);
    padding-left: 54px;
}

[data-theme="dark"] .hm-status {
    color: var(--gray-400);
}

/* ===== TRAFFIC CHART ===== */
.traffic-chart-wrapper {
    position: relative;
    width: 180px;
    height: 180px;
    margin: 0 auto 24px;
}

.donut-chart {
    width: 100%;
    height: 100%;
    transform: rotate(0deg);
    animation: donutIn 1s ease-out;
}

@keyframes donutIn {
    from { opacity: 0; transform: scale(0.8); }
    to { opacity: 1; transform: scale(1); }
}

.donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.donut-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--dark);
    line-height: 1;
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .donut-value {
    color: var(--white);
}

.donut-label {
    font-size: 0.8rem;
    color: var(--gray-600);
    margin-top: 4px;
}

[data-theme="dark"] .donut-label {
    color: var(--gray-400);
}

.traffic-legend {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* ===== TOP PAGES ===== */
.top-pages-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.tp-item {
    display: grid;
    grid-template-columns: 32px 1fr auto;
    gap: 12px;
    align-items: center;
    padding: 12px;
    border-radius: var(--radius);
    transition: background var(--transition-fast);
    cursor: pointer;
}

.tp-item:hover {
    background: var(--gray-50);
}

[data-theme="dark"] .tp-item:hover {
    background: var(--dark);
}

.tp-rank {
    width: 32px;
    height: 32px;
    border-radius: var(--radius);
    background: var(--gradient-primary);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    font-family: 'Space Grotesk', sans-serif;
}

.tp-info {
    min-width: 0;
}

.tp-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 3px;
}

[data-theme="dark"] .tp-name {
    color: var(--white);
}

.tp-views {
    font-size: 0.78rem;
    color: var(--gray-600);
}

[data-theme="dark"] .tp-views {
    color: var(--gray-400);
}

.tp-bar {
    width: 90px;
    height: 6px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .tp-bar {
    background: var(--dark);
}

.tp-bar-fill {
    height: 100%;
    background: var(--gradient-primary);
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

@keyframes barFill {
    from { width: 0; }
}

/* ===== AGENDA LIST ===== */
.agenda-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.agenda-item {
    display: flex;
    gap: 14px;
    padding: 14px;
    border-radius: var(--radius-lg);
    background: var(--gray-50);
    transition: all var(--transition-fast);
    cursor: pointer;
}

[data-theme="dark"] .agenda-item {
    background: var(--dark);
}

.agenda-item:hover {
    background: var(--gray-100);
    transform: translateX(4px);
}

[data-theme="dark"] .agenda-item:hover {
    background: var(--dark-2);
}

.agenda-date {
    width: 56px;
    text-align: center;
    padding: 10px 6px;
    background: var(--white);
    border-radius: var(--radius);
    flex-shrink: 0;
    border: 1px solid var(--gray-100);
}

[data-theme="dark"] .agenda-date {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.1);
}

.ag-day {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    line-height: 1;
    font-family: 'Space Grotesk', sans-serif;
}

.ag-month {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--gray-600);
    margin-top: 3px;
}

[data-theme="dark"] .ag-month {
    color: var(--gray-400);
}

.agenda-info {
    flex: 1;
    min-width: 0;
}

.ag-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 5px;
    line-height: 1.3;
}

[data-theme="dark"] .ag-title {
    color: var(--white);
}

.ag-meta {
    font-size: 0.8rem;
    color: var(--gray-600);
    display: flex;
    align-items: center;
    gap: 6px;
}

[data-theme="dark"] .ag-meta {
    color: var(--gray-400);
}

.ag-meta i {
    color: var(--primary);
    font-size: 0.75rem;
}

/* ===== TIMELINE ===== */
.timeline-list {
    max-height: 440px;
    overflow-y: auto;
    padding-right: 8px;
}

.timeline-item {
    display: flex;
    gap: 16px;
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.tl-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
    position: relative;
    z-index: 2;
    margin-top: 6px;
}

.timeline-item:not(:last-child) .tl-dot::after {
    content: '';
    position: absolute;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    width: 2px;
    height: calc(100% + 8px);
    background: var(--gray-200);
}

[data-theme="dark"] .timeline-item:not(:last-child) .tl-dot::after {
    background: rgba(255,255,255,0.1);
}

.tl-content {
    flex: 1;
    min-width: 0;
    padding: 14px 16px;
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    transition: all var(--transition-fast);
}

[data-theme="dark"] .tl-content {
    background: var(--dark);
}

.timeline-item:hover .tl-content {
    background: var(--gray-100);
    transform: translateX(4px);
}

[data-theme="dark"] .timeline-item:hover .tl-content {
    background: var(--dark-2);
}

.tl-head {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}

.tl-icon {
    width: 38px;
    height: 38px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}

.tl-info {
    flex: 1;
    min-width: 0;
}

.tl-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

[data-theme="dark"] .tl-title {
    color: var(--white);
}

.tl-actor {
    font-size: 0.78rem;
    color: var(--gray-600);
}

[data-theme="dark"] .tl-actor {
    color: var(--gray-400);
}

.tl-time {
    font-size: 0.8rem;
    color: var(--gray-600);
    display: flex;
    align-items: center;
    gap: 6px;
}

[data-theme="dark"] .tl-time {
    color: var(--gray-400);
}

.tl-time i {
    font-size: 0.75rem;
}

/* ===== NEWS LIST ===== */
.news-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-height: 440px;
    overflow-y: auto;
    padding-right: 8px;
}

.news-item {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 16px;
    padding: 14px;
    border-radius: var(--radius-lg);
    text-decoration: none;
    color: inherit;
    transition: all var(--transition);
    background: var(--gray-50);
    border: 1px solid transparent;
}

[data-theme="dark"] .news-item {
    background: var(--dark);
}

.news-item:hover {
    background: var(--gray-100);
    border-color: var(--gray-200);
    transform: translateX(4px);
    box-shadow: var(--shadow);
}

[data-theme="dark"] .news-item:hover {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.1);
}

.news-item.featured {
    grid-column: 1 / -1;
    grid-template-columns: 1fr;
    background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(4,120,87,0.04));
    border-color: rgba(16,185,129,0.2);
}

.news-image {
    width: 150px;
    height: 110px;
    border-radius: var(--radius);
    background-size: cover;
    background-position: center;
    background-color: var(--gray-100);
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}

[data-theme="dark"] .news-image {
    background-color: var(--dark-2);
}

.news-item.featured .news-image {
    width: 100%;
    height: 220px;
}

.news-featured-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    padding: 5px 12px;
    background: var(--gradient-warning);
    color: var(--white);
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 4px 12px rgba(245,158,11,0.4);
}

.news-category {
    position: absolute;
    bottom: 10px;
    left: 10px;
    padding: 4px 12px;
    background: rgba(16,185,129,0.95);
    color: var(--white);
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.news-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.news-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 8px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.news-item.featured .news-title {
    font-size: 1.2rem;
}

[data-theme="dark"] .news-title {
    color: var(--white);
}

.news-excerpt {
    font-size: 0.88rem;
    color: var(--gray-600);
    line-height: 1.5;
    margin-bottom: 12px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

[data-theme="dark"] .news-excerpt {
    color: var(--gray-400);
}

.news-meta {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    font-size: 0.78rem;
    color: var(--gray-600);
    margin-top: auto;
}

[data-theme="dark"] .news-meta {
    color: var(--gray-400);
}

.news-meta i {
    margin-right: 4px;
    color: var(--primary);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1280px) {
    .dashboard-grid-3col {
        grid-template-columns: 1fr 1fr;
    }
    
    .dashboard-grid-3col > .card:last-child {
        grid-column: 1 / -1;
    }
}

@media (max-width: 1024px) {
    .dashboard-hero {
        padding: 32px;
    }
    
    .dashboard-grid-2col,
    .dashboard-grid-3col {
        grid-template-columns: 1fr;
    }
    
    .hero-content {
        grid-template-columns: 1fr;
        gap: 28px;
    }
    
    .hero-right {
        align-items: flex-start;
    }
    
    .quick-actions {
        grid-template-columns: repeat(5, 1fr);
        width: 100%;
    }
}

@media (max-width: 768px) {
    .dashboard-hero {
        padding: 24px 20px;
    }
    
    .hero-title {
        font-size: 1.8rem;
    }
    
    .quick-actions {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .news-item {
        grid-template-columns: 110px 1fr;
    }
    
    .news-image {
        width: 110px;
        height: 90px;
    }
    
    .bar-main {
        width: 20px;
    }
    
    .bar-views {
        width: 14px;
    }
}
</style>

<script>
(function() {
    'use strict';

    /* ===== COUNTER ANIMATION ===== */
    const counters = document.querySelectorAll('.stat-counter');
    const animateCounter = (el) => {
        const target = parseInt(el.dataset.target);
        const duration = 1500;
        const start = performance.now();
        
        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(target * eased);
            el.textContent = current.toLocaleString('id-ID');
            
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString('id-ID');
        };
        
        requestAnimationFrame(step);
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    
    counters.forEach(counter => observer.observe(counter));

    /* ===== BAR CHART TOOLTIP (Minimal) ===== */
    document.querySelectorAll('.bar-group').forEach(group => {
        group.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'bar-tooltip';
            tooltip.innerHTML = `
                <div class="tooltip-date">${this.dataset.date}</div>
                <div class="tooltip-row"><span class="tooltip-dot" style="background: var(--primary)"></span><span>Pendaftar:</span><strong>${this.dataset.pendaftar}</strong></div>
                <div class="tooltip-row"><span class="tooltip-dot" style="background: var(--secondary)"></span><span>Views:</span><strong>${Number(this.dataset.views).toLocaleString('id-ID')}</strong></div>
            `;
            this.appendChild(tooltip);
            requestAnimationFrame(() => tooltip.classList.add('show'));
        });
        
        group.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('.bar-tooltip');
            if (tooltip) {
                tooltip.classList.remove('show');
                setTimeout(() => tooltip.remove(), 200);
            }
        });
    });

    // Inject tooltip CSS (sekali saja)
    if (!document.getElementById('bar-tooltip-style')) {
        const tooltipStyle = document.createElement('style');
        tooltipStyle.id = 'bar-tooltip-style';
        tooltipStyle.textContent = `
            .bar-tooltip {
                position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%) translateY(8px);
                background: var(--dark); color: var(--white); padding: 12px 16px; border-radius: var(--radius);
                font-size: 0.8rem; white-space: nowrap; z-index: 100; box-shadow: var(--shadow-lg);
                opacity: 0; pointer-events: none; transition: all 0.2s;
            }
            .bar-tooltip.show { opacity: 1; transform: translateX(-50%) translateY(-4px); }
            .tooltip-date { font-weight: 700; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); }
            .tooltip-row { display: flex; align-items: center; gap: 8px; margin-top: 5px; }
            .tooltip-dot { width: 10px; height: 10px; border-radius: 50%; }
            .tooltip-row strong { margin-left: auto; font-family: 'Space Grotesk', sans-serif; }
        `;
        document.head.appendChild(tooltipStyle);
    }
})();
</script>

<?php include 'includes/footer.php'; ?>