<?php
require_once 'includes/auth.php';
$pageTitle = 'Log Aktivitas';
include 'includes/header.php';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
}

$isSuperAdmin = class_exists('Auth') && method_exists('Auth', 'isSuper') && Auth::isSuper();
$currentAdminId = $_SESSION['admin_id'] ?? 0;

/* ===== HANDLE ACTIONS ===== */
if (isset($_GET['action']) && $_GET['action'] === 'clear' && $isSuperAdmin && $db) {
    $days = (int)($_GET['days'] ?? 30);
    $days = max(1, min($days, 365)); // Safe range 1-365
    
    try {
        $stmt = $db->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        $deleted = $stmt->rowCount();
        setFlash('success', "Berhasil menghapus $deleted log aktivitas yang lebih lama dari $days hari");
    } catch (Throwable $e) {
        setFlash('error', 'Gagal membersihkan log: ' . $e->getMessage());
    }
    header('Location: activity-logs.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$filterModule = $_GET['module'] ?? '';
$filterAction = $_GET['action_type'] ?? '';
$filterAdmin = $_GET['admin'] ?? '';
$filterDate = $_GET['date'] ?? '';
$searchQuery = $_GET['q'] ?? '';
$limit = min((int)($_GET['limit'] ?? 50), 500);
$limit = max(10, $limit);

/* ===== BUILD QUERY ===== */
$where = "1=1";
$params = [];

if ($filterModule) { $where .= " AND al.module = ?"; $params[] = $filterModule; }
if ($filterAction) { $where .= " AND al.action = ?"; $params[] = $filterAction; }
if ($filterAdmin) { $where .= " AND al.admin_id = ?"; $params[] = (int)$filterAdmin; }
if ($filterDate) { $where .= " AND DATE(al.created_at) = ?"; $params[] = $filterDate; }
if ($searchQuery) { 
    $where .= " AND (al.description LIKE ? OR a.nama_lengkap LIKE ? OR al.module LIKE ? OR al.ip_address LIKE ?)"; 
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$logs = [];
if ($db) {
    try {
        $sql = "SELECT al.*, a.nama_lengkap as admin_name, a.username as admin_username, a.email as admin_email
                FROM activity_logs al 
                LEFT JOIN admins a ON al.admin_id = a.id 
                WHERE $where 
                ORDER BY al.created_at DESC 
                LIMIT ?";
        $stmt = $db->prepare($sql);
        $params[] = $limit;
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== STATISTICS ===== */
$stats = [
    'total' => 0,
    'today' => 0,
    'this_week' => 0,
    'active_admins' => 0,
    'modules' => 0,
    'suspicious' => 0,
];

if ($db) {
    try {
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
        $stats['today'] = (int)$db->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $stats['this_week'] = (int)$db->query("SELECT COUNT(*) FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $stats['active_admins'] = (int)$db->query("SELECT COUNT(DISTINCT admin_id) FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $stats['modules'] = (int)$db->query("SELECT COUNT(DISTINCT module) FROM activity_logs WHERE module IS NOT NULL AND module != ''")->fetchColumn();
        $stats['suspicious'] = (int)$db->query("SELECT COUNT(*) FROM activity_logs WHERE action IN ('login_failed', 'unauthorized_access') AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== FILTER OPTIONS ===== */
$modules = $actions = $admins = [];
if ($db) {
    try {
        $modules = $db->query("SELECT DISTINCT module FROM activity_logs WHERE module IS NOT NULL AND module != '' ORDER BY module")->fetchAll(PDO::FETCH_COLUMN);
        $actions = $db->query("SELECT DISTINCT action FROM activity_logs WHERE action IS NOT NULL ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
        $admins = $db->query("SELECT id, nama_lengkap FROM admins ORDER BY nama_lengkap")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== ACTIVITY CHART DATA (7 days) ===== */
$chartData = [];
if ($db) {
    try {
        $stmt = $db->query("
            SELECT DATE(created_at) as date, COUNT(*) as total 
            FROM activity_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at) 
            ORDER BY date ASC
        ");
        foreach ($stmt->fetchAll() as $row) {
            $chartData[$row['date']] = (int)$row['total'];
        }
    } catch (Throwable $e) {}
}

/* ===== HOURLY ACTIVITY (today) ===== */
$hourlyData = array_fill(0, 24, 0);
if ($db) {
    try {
        $stmt = $db->query("
            SELECT HOUR(created_at) as hour, COUNT(*) as total 
            FROM activity_logs 
            WHERE DATE(created_at) = CURDATE()
            GROUP BY HOUR(created_at)
        ");
        foreach ($stmt->fetchAll() as $row) {
            $hourlyData[(int)$row['hour']] = (int)$row['total'];
        }
    } catch (Throwable $e) {}
}

/* ===== TOP ADMINS ===== */
$topAdmins = [];
if ($db) {
    try {
        $topAdmins = $db->query("
            SELECT a.nama_lengkap, a.email, COUNT(*) as total 
            FROM activity_logs al 
            LEFT JOIN admins a ON al.admin_id = a.id 
            WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND al.admin_id IS NOT NULL
            GROUP BY al.admin_id, a.nama_lengkap, a.email
            ORDER BY total DESC 
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== RECENT MODULES ===== */
$recentModules = [];
if ($db) {
    try {
        $recentModules = $db->query("
            SELECT module, COUNT(*) as total 
            FROM activity_logs 
            WHERE module IS NOT NULL AND module != '' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY module 
            ORDER BY total DESC 
            LIMIT 6
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== ACTION CONFIG ===== */
$actionConfig = [
    'create' => ['label' => 'Create', 'color' => '#10B981', 'icon' => 'fa-plus-circle', 'bg' => 'rgba(16,185,129,0.12)'],
    'update' => ['label' => 'Update', 'color' => '#3B82F6', 'icon' => 'fa-edit', 'bg' => 'rgba(59,130,246,0.12)'],
    'delete' => ['label' => 'Delete', 'color' => '#EF4444', 'icon' => 'fa-trash-alt', 'bg' => 'rgba(239,68,68,0.12)'],
    'login' => ['label' => 'Login', 'color' => '#8B5CF6', 'icon' => 'fa-sign-in-alt', 'bg' => 'rgba(139,92,246,0.12)'],
    'logout' => ['label' => 'Logout', 'color' => '#6B7280', 'icon' => 'fa-sign-out-alt', 'bg' => 'rgba(107,114,128,0.12)'],
    'login_failed' => ['label' => 'Login Failed', 'color' => '#DC2626', 'icon' => 'fa-exclamation-triangle', 'bg' => 'rgba(220,38,38,0.12)'],
    'export' => ['label' => 'Export', 'color' => '#F59E0B', 'icon' => 'fa-file-export', 'bg' => 'rgba(245,158,11,0.12)'],
    'upload' => ['label' => 'Upload', 'color' => '#06B6D4', 'icon' => 'fa-cloud-upload-alt', 'bg' => 'rgba(6,182,212,0.12)'],
    'download' => ['label' => 'Download', 'color' => '#0EA5E9', 'icon' => 'fa-download', 'bg' => 'rgba(14,165,233,0.12)'],
    'subscribe' => ['label' => 'Subscribe', 'color' => '#14B8A6', 'icon' => 'fa-envelope', 'bg' => 'rgba(20,184,166,0.12)'],
    'unauthorized_access' => ['label' => 'Unauthorized', 'color' => '#DC2626', 'icon' => 'fa-ban', 'bg' => 'rgba(220,38,38,0.12)'],
];

/* ===== MODULE ICONS ===== */
$moduleIcons = [
    'auth' => 'fa-shield-alt',
    'berita' => 'fa-newspaper',
    'agenda' => 'fa-calendar-alt',
    'dosen' => 'fa-chalkboard-teacher',
    'alumni' => 'fa-user-graduate',
    'galeri' => 'fa-images',
    'kontak' => 'fa-envelope',
    'komentar' => 'fa-comments',
    'pendaftaran' => 'fa-user-plus',
    'users' => 'fa-users-cog',
    'settings' => 'fa-cog',
    'media' => 'fa-photo-video',
    'testimoni' => 'fa-quote-right',
    'newsletter' => 'fa-paper-plane',
    'faq' => 'fa-question-circle',
    'dokumen' => 'fa-file-alt',
    'kurikulum' => 'fa-book',
    'prestasi' => 'fa-trophy',
    'mitra' => 'fa-handshake',
    'prospek' => 'fa-briefcase',
    'konsentrasi' => 'fa-layer-group',
    'fasilitas' => 'fa-flask',
];

/* ===== GROUP LOGS BY DATE ===== */
$groupedLogs = [];
foreach ($logs as $log) {
    $dateKey = date('Y-m-d', strtotime($log['created_at']));
    $groupedLogs[$dateKey][] = $log;
}

/* ===== PEAK ACTIVITY HOUR ===== */
$peakHour = array_keys($hourlyData, max($hourlyData))[0] ?? 0;
$peakHourLabel = sprintf('%02d:00', $peakHour);

/* ===== AVERAGE DAILY ACTIVITY ===== */
$avgDaily = $stats['this_week'] > 0 ? round($stats['this_week'] / 7) : 0;

/* ===== HELPER: Get IP Location (simulasi) ===== */
function getIpLocation($ip) {
    if (!$ip || $ip === '127.0.0.1' || $ip === '::1') return 'Localhost';
    // Simulasi - di production pakai GeoIP API
    $locations = ['Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Yogyakarta', 'Semarang', 'Makassar', 'Palembang'];
    return $locations[crc32($ip) % count($locations)];
}
?>

<div class="page-content activity-logs-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="logs-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-matrix"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Audit Trail & Security</span>
                </div>
                <h1 class="hero-title">Log Aktivitas Sistem</h1>
                <p class="hero-subtitle">
                    Monitor semua aksi admin, deteksi anomali, dan audit keamanan real-time
                    <?php if ($stats['suspicious'] > 0): ?>
                    <span class="hero-alert-badge">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <?= $stats['suspicious'] ?> aktivitas mencurigakan
                    </span>
                    <?php endif; ?>
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-history"></i>
                        <span><strong><?= number_format($stats['total']) ?></strong> Total Log</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-calendar-day"></i>
                        <span><strong><?= $stats['today'] ?></strong> Hari Ini</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-users"></i>
                        <span><strong><?= $stats['active_admins'] ?></strong> Admin Aktif</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-clock"></i>
                        <span>Peak <strong><?= $peakHourLabel ?></strong></span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <?php if ($isSuperAdmin): ?>
                <button class="btn btn-ghost" onclick="openCleanupModal()" title="Cleanup old logs">
                    <i class="fas fa-broom"></i>
                    <span>Cleanup</span>
                </button>
                <?php endif; ?>
                <button class="btn btn-ghost" onclick="exportLogs()" title="Export ke CSV">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-ghost" id="refreshBtn" title="Refresh (30s auto)">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh</span>
                </button>
                <button class="btn btn-hero-primary" onclick="toggleAutoRefresh()" id="autoRefreshBtn">
                    <i class="fas fa-radar" id="autoRefreshIcon"></i>
                    <span id="autoRefreshText">Auto-Refresh: OFF</span>
                </button>
            </div>
        </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?> fade-in">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <span><?= clean($flash['message']) ?></span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;margin-left:auto;opacity:.7">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- ===== MAIN LAYOUT ===== -->
    <div class="logs-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="logs-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid logs-stats">
                <div class="stat-card stat-cyan">
                    <div class="stat-icon"><i class="fas fa-history"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Log</p>
                        <span class="stat-trend up"><i class="fas fa-database"></i> Database</span>
                    </div>
                </div>

                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['today'] ?>">0</h3>
                        <p>Aktivitas Hari Ini</p>
                        <span class="stat-trend up"><i class="fas fa-bolt"></i> Live</span>
                    </div>
                </div>

                <div class="stat-card stat-blue">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['active_admins'] ?>">0</h3>
                        <p>Admin Aktif (7d)</p>
                        <span class="stat-trend up"><i class="fas fa-user-check"></i> Active</span>
                    </div>
                </div>

                <div class="stat-card <?= $stats['suspicious'] > 0 ? 'stat-red' : 'stat-purple' ?>">
                    <div class="stat-icon">
                        <i class="fas fa-<?= $stats['suspicious'] > 0 ? 'exclamation-triangle' : 'shield-alt' ?>"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['suspicious'] ?>">0</h3>
                        <p><?= $stats['suspicious'] > 0 ? 'Aktivitas Mencurigakan' : 'Aman' ?></p>
                        <span class="stat-trend <?= $stats['suspicious'] > 0 ? 'down' : 'up' ?>">
                            <i class="fas fa-<?= $stats['suspicious'] > 0 ? 'exclamation-circle' : 'check-circle' ?>"></i>
                            <?= $stats['suspicious'] > 0 ? 'Perhatian' : 'Secure' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Toolbar / Filter Bar -->
            <div class="card logs-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari deskripsi, admin, module, IP..." value="<?= clean($searchQuery) ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterModule" class="toolbar-select">
                            <option value="">📦 Semua Modul</option>
                            <?php foreach ($modules as $m): ?>
                            <option value="<?= clean($m) ?>" <?= $filterModule === $m ? 'selected' : '' ?>>
                                <?= ucfirst(clean($m)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select id="filterAction" class="toolbar-select">
                            <option value="">⚡ Semua Aksi</option>
                            <?php foreach ($actions as $a): 
                                $cfg = $actionConfig[$a] ?? null;
                            ?>
                            <option value="<?= clean($a) ?>" <?= $filterAction === $a ? 'selected' : '' ?>>
                                <?= $cfg ? $cfg['label'] : ucfirst(clean($a)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select id="filterAdmin" class="toolbar-select">
                            <option value="">👤 Semua Admin</option>
                            <?php foreach ($admins as $ad): ?>
                            <option value="<?= $ad['id'] ?>" <?= $filterAdmin == $ad['id'] ? 'selected' : '' ?>>
                                <?= clean($ad['nama_lengkap']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="date" id="filterDate" class="toolbar-select" value="<?= clean($filterDate) ?>" title="Filter tanggal">
                        
                        <select id="filterLimit" class="toolbar-select" style="min-width: 110px;">
                            <option value="25" <?= $limit === 25 ? 'selected' : '' ?>>25 Terbaru</option>
                            <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50 Terbaru</option>
                            <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100 Terbaru</option>
                            <option value="200" <?= $limit === 200 ? 'selected' : '' ?>>200 Terbaru</option>
                            <option value="500" <?= $limit === 500 ? 'selected' : '' ?>>500 Terbaru</option>
                        </select>
                        
                        <button class="toolbar-icon-btn" onclick="resetFilters()" title="Reset Filter">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Timeline Log -->
            <div class="card logs-timeline-card">
                <div class="timeline-header">
                    <div>
                        <h3><i class="fas fa-stream"></i> Timeline Aktivitas</h3>
                        <p><?= count($logs) ?> aktivitas ditampilkan dari <?= number_format($stats['total']) ?> total</p>
                    </div>
                    <div class="timeline-legend">
                        <span class="legend-item"><span class="legend-dot" style="background:#10B981"></span> Create</span>
                        <span class="legend-item"><span class="legend-dot" style="background:#3B82F6"></span> Update</span>
                        <span class="legend-item"><span class="legend-dot" style="background:#EF4444"></span> Delete</span>
                        <span class="legend-item"><span class="legend-dot" style="background:#8B5CF6"></span> Login</span>
                    </div>
                </div>

                <?php if (!empty($groupedLogs)): ?>
                <div class="timeline-list" id="timelineList">
                    <?php foreach ($groupedLogs as $dateKey => $dateLogs): 
                        $isToday = $dateKey === date('Y-m-d');
                        $isYesterday = $dateKey === date('Y-m-d', strtotime('-1 day'));
                        $dateLabel = $isToday ? 'Hari Ini' : ($isYesterday ? 'Kemarin' : date('l, d F Y', strtotime($dateKey)));
                    ?>
                    <div class="timeline-date-group">
                        <div class="tdg-header">
                            <span class="tdg-label">
                                <?php if ($isToday): ?>
                                    <i class="fas fa-sun" style="color:#F59E0B"></i>
                                <?php elseif ($isYesterday): ?>
                                    <i class="fas fa-cloud-moon"></i>
                                <?php else: ?>
                                    <i class="fas fa-calendar"></i>
                                <?php endif; ?>
                                <?= $dateLabel ?>
                            </span>
                            <span class="tdg-count"><?= count($dateLogs) ?> aktivitas</span>
                        </div>
                        
                        <?php foreach ($dateLogs as $log): 
                            $action = $log['action'] ?? 'unknown';
                            $cfg = $actionConfig[$action] ?? ['label' => ucfirst($action), 'color' => '#6B7280', 'icon' => 'fa-circle', 'bg' => 'rgba(107,114,128,0.12)'];
                            $module = $log['module'] ?? '';
                            $moduleIcon = $moduleIcons[$module] ?? 'fa-cube';
                            $isSuspicious = in_array($action, ['login_failed', 'unauthorized_access']);
                        ?>
                        <div class="log-item <?= $isSuspicious ? 'suspicious' : '' ?>" 
                             data-id="<?= $log['id'] ?>"
                             data-log='<?= htmlspecialchars(json_encode($log, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'
                             onclick="openLogDetail(this)">
                            <div class="li-timeline">
                                <div class="li-dot" style="background: <?= $cfg['color'] ?>; box-shadow: 0 0 0 4px <?= $cfg['bg'] ?>;">
                                    <?php if ($isSuspicious): ?>
                                    <div class="li-pulse"></div>
                                    <?php endif; ?>
                                </div>
                                <div class="li-line"></div>
                            </div>
                            
                            <div class="li-content">
                                <div class="li-header">
                                    <div class="li-icon" style="background: <?= $cfg['bg'] ?>; color: <?= $cfg['color'] ?>">
                                        <i class="fas <?= $cfg['icon'] ?>"></i>
                                    </div>
                                    <div class="li-info">
                                        <div class="li-title">
                                            <?php if ($searchQuery): ?>
                                                <?= preg_replace('/(' . preg_quote($searchQuery, '/') . ')/i', '<mark>$1</mark>', clean($log['description'] ?: ucfirst($action))) ?>
                                            <?php else: ?>
                                                <?= clean($log['description'] ?: ucfirst($action)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="li-meta">
                                            <span class="li-actor">
                                                <i class="fas fa-user"></i>
                                                <strong><?= clean($log['admin_name'] ?: $log['admin_username'] ?: 'System') ?></strong>
                                            </span>
                                            <?php if ($module): ?>
                                            <span class="li-module">
                                                <i class="fas <?= $moduleIcon ?>"></i>
                                                <?= ucfirst(clean($module)) ?>
                                            </span>
                                            <?php endif; ?>
                                            <span class="li-action-badge" style="background: <?= $cfg['bg'] ?>; color: <?= $cfg['color'] ?>">
                                                <?= $cfg['label'] ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="li-time">
                                        <i class="fas fa-clock"></i>
                                        <span><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
                                    </div>
                                </div>
                                
                                <div class="li-footer">
                                    <?php if ($log['ip_address']): ?>
                                    <span class="li-ip">
                                        <i class="fas fa-globe"></i>
                                        <?= clean($log['ip_address']) ?>
                                        <small>(<?= getIpLocation($log['ip_address']) ?>)</small>
                                    </span>
                                    <?php endif; ?>
                                    <button class="li-detail-btn" onclick="event.stopPropagation(); openLogDetail(this.closest('.log-item'))">
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="timeline-footer">
                    <p><i class="fas fa-info-circle"></i> Menampilkan <?= count($logs) ?> log terbaru. Gunakan filter untuk mempersempit pencarian.</p>
                </div>
                <?php else: ?>
                <div class="empty-state-ult">
                    <div class="empty-illustration">
                        <svg viewBox="0 0 200 150" width="200" height="150">
                            <ellipse cx="100" cy="130" rx="70" ry="10" fill="var(--gray-200)" opacity="0.5"/>
                            <rect x="50" y="30" width="100" height="90" rx="8" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                            <line x1="65" y1="50" x2="135" y2="50" stroke="var(--gray-300)" stroke-width="2" stroke-linecap="round"/>
                            <line x1="65" y1="65" x2="120" y2="65" stroke="var(--gray-300)" stroke-width="2" stroke-linecap="round"/>
                            <line x1="65" y1="80" x2="128" y2="80" stroke="var(--gray-300)" stroke-width="2" stroke-linecap="round"/>
                            <line x1="65" y1="95" x2="115" y2="95" stroke="var(--gray-300)" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="150" cy="45" r="18" fill="#06B6D4" opacity="0.9"/>
                            <line x1="143" y1="45" x2="157" y2="45" stroke="white" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3>Tidak ada log aktivitas</h3>
                    <p>Belum ada aktivitas yang tercatat atau filter tidak menghasilkan data</p>
                    <div class="empty-actions">
                        <button class="btn btn-primary" onclick="resetFilters()">
                            <i class="fas fa-redo"></i> Reset Filter
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="logs-sidebar">
            <!-- Activity Chart (7 Days) -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-line"></i> 7 Hari Terakhir</h3>
                    <span class="widget-badge"><?= $stats['this_week'] ?> total</span>
                </div>
                <div class="widget-body">
                    <?php if (!empty($chartData)): ?>
                    <div class="activity-chart">
                        <?php 
                        $maxChart = max($chartData) ?: 1;
                        $startDate = strtotime('-6 days');
                        for ($i = 0; $i < 7; $i++):
                            $date = date('Y-m-d', strtotime("+$i days", $startDate));
                            $count = $chartData[$date] ?? 0;
                            $height = max(4, ($count / $maxChart) * 100);
                            $isToday = $date === date('Y-m-d');
                        ?>
                        <div class="ac-bar-group" title="<?= date('d M', strtotime($date)) ?>: <?= $count ?> aktivitas">
                            <div class="ac-bar-value"><?= $count ?></div>
                            <div class="ac-bar-wrapper">
                                <div class="ac-bar <?= $isToday ? 'today' : '' ?>" style="height: <?= $height ?>%"></div>
                            </div>
                            <div class="ac-bar-label"><?= date('D', strtotime($date)) ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="chart-footer">
                        <span>Avg: <strong><?= $avgDaily ?>/hari</strong></span>
                    </div>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-chart-line"></i>
                            <p>Belum ada data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hourly Heatmap (Today) -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-fire"></i> Aktivitas Per Jam</h3>
                    <span class="widget-badge">Hari Ini</span>
                </div>
                <div class="widget-body">
                    <div class="hourly-heatmap">
                        <?php 
                        $maxHourly = max($hourlyData) ?: 1;
                        for ($h = 0; $h < 24; $h++):
                            $count = $hourlyData[$h];
                            $intensity = $count > 0 ? ($count / $maxHourly) : 0;
                            $isCurrentHour = $h === (int)date('H');
                        ?>
                        <div class="hh-cell <?= $isCurrentHour ? 'current' : '' ?>" 
                             style="background: rgba(6, 182, 212, <?= 0.1 + ($intensity * 0.8) ?>)"
                             title="<?= sprintf('%02d:00', $h) ?> - <?= $count ?> aktivitas">
                            <?php if ($isCurrentHour): ?>
                                <span class="hh-now"></span>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="heatmap-legend">
                        <span class="hh-legend-item"><span class="hh-dot" style="background:rgba(6,182,212,0.1)"></span> Rendah</span>
                        <span class="hh-legend-item"><span class="hh-dot" style="background:rgba(6,182,212,0.5)"></span> Sedang</span>
                        <span class="hh-legend-item"><span class="hh-dot" style="background:rgba(6,182,212,0.9)"></span> Tinggi</span>
                    </div>
                    <div class="peak-info">
                        <i class="fas fa-bolt" style="color:#F59E0B"></i>
                        Peak: <strong><?= $peakHourLabel ?> WIB</strong> (<?= max($hourlyData) ?> aktivitas)
                    </div>
                </div>
            </div>

            <!-- Top Admins -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-crown"></i> Top Admins</h3>
                    <span class="widget-badge">7 hari</span>
                </div>
                <div class="widget-body">
                    <?php if (!empty($topAdmins)): ?>
                        <?php 
                        $maxAdmin = max(array_column($topAdmins, 'total'));
                        foreach ($topAdmins as $i => $ta): 
                            $pct = round(($ta['total'] / $maxAdmin) * 100);
                            $colors = ['#06B6D4', '#0EA5E9', '#3B82F6', '#8B5CF6', '#EC4899'];
                            $color = $colors[$i % count($colors)];
                            $initial = strtoupper(substr($ta['nama_lengkap'] ?? 'A', 0, 1));
                        ?>
                        <div class="admin-item">
                            <div class="admin-rank">#<?= $i + 1 ?></div>
                            <div class="admin-avatar" style="background: <?= $color ?>"><?= $initial ?></div>
                            <div class="admin-info">
                                <div class="admin-name"><?= clean($ta['nama_lengkap'] ?? 'Unknown') ?></div>
                                <div class="admin-bar">
                                    <div class="admin-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                                </div>
                            </div>
                            <div class="admin-count"><?= $ta['total'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-users"></i>
                            <p>Belum ada data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Module Activity -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-cubes"></i> Modul Aktif</h3>
                    <span class="widget-badge"><?= $stats['modules'] ?> modul</span>
                </div>
                <div class="widget-body">
                    <?php if (!empty($recentModules)): ?>
                        <?php 
                        $maxModule = max(array_column($recentModules, 'total'));
                        foreach ($recentModules as $rm): 
                            $pct = round(($rm['total'] / $maxModule) * 100);
                            $mIcon = $moduleIcons[$rm['module']] ?? 'fa-cube';
                        ?>
                        <div class="module-item">
                            <div class="module-head">
                                <span class="module-label">
                                    <i class="fas <?= $mIcon ?>"></i>
                                    <?= ucfirst(clean($rm['module'])) ?>
                                </span>
                                <span class="module-count"><?= $rm['total'] ?></span>
                            </div>
                            <div class="module-bar">
                                <div class="module-bar-fill" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-cubes"></i>
                            <p>Belum ada data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="widget-body">
                    <a href="security-logs.php" class="qa-link">
                        <i class="fas fa-shield-alt"></i>
                        <span>Security Logs</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="users.php" class="qa-link">
                        <i class="fas fa-users-cog"></i>
                        <span>Kelola Admin</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="settings.php" class="qa-link">
                        <i class="fas fa-cog"></i>
                        <span>Pengaturan Sistem</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== LOG DETAIL MODAL ===== -->
<div class="modal" id="logDetailModal">
    <div class="modal-content" style="max-width: 720px;">
        <div class="modal-header modal-header-gradient logs-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-file-alt"></i></div>
                <div>
                    <h3>Detail Log Aktivitas</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Informasi lengkap aktivitas</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeLogDetail()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="logDetailBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeLogDetail()">Tutup</button>
        </div>
    </div>
</div>

<!-- ===== CLEANUP MODAL ===== -->
<?php if ($isSuperAdmin): ?>
<div class="modal" id="cleanupModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header modal-header-gradient" style="background: linear-gradient(135deg, #DC2626, #991B1B)">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-broom"></i></div>
                <div>
                    <h3>Cleanup Log Aktivitas</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Hapus log lama untuk menghemat space</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeCleanupModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="cleanup-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Peringatan!</strong>
                    <p>Aksi ini tidak dapat dibatalkan. Pastikan Anda sudah backup data jika diperlukan.</p>
                </div>
            </div>
            
            <div class="form-group">
                <label>Hapus log yang lebih lama dari:</label>
                <div class="cleanup-options">
                    <label class="cleanup-option">
                        <input type="radio" name="cleanup_days" value="7">
                        <span class="cleanup-option-content">
                            <strong>7 Hari</strong>
                            <small>Keep 1 minggu terakhir</small>
                        </span>
                    </label>
                    <label class="cleanup-option">
                        <input type="radio" name="cleanup_days" value="14">
                        <span class="cleanup-option-content">
                            <strong>14 Hari</strong>
                            <small>Keep 2 minggu terakhir</small>
                        </span>
                    </label>
                    <label class="cleanup-option">
                        <input type="radio" name="cleanup_days" value="30" checked>
                        <span class="cleanup-option-content">
                            <strong>30 Hari</strong>
                            <small>Keep 1 bulan terakhir (Recommended)</small>
                        </span>
                    </label>
                    <label class="cleanup-option">
                        <input type="radio" name="cleanup_days" value="90">
                        <span class="cleanup-option-content">
                            <strong>90 Hari</strong>
                            <small>Keep 3 bulan terakhir</small>
                        </span>
                    </label>
                    <label class="cleanup-option">
                        <input type="radio" name="cleanup_days" value="180">
                        <span class="cleanup-option-content">
                            <strong>180 Hari</strong>
                            <small>Keep 6 bulan terakhir</small>
                        </span>
                    </label>
                </div>
            </div>
            
            <div class="cleanup-info">
                <i class="fas fa-info-circle"></i>
                <span>Total log saat ini: <strong><?= number_format($stats['total']) ?></strong></span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCleanupModal()">Batal</button>
            <a href="#" id="cleanupConfirmBtn" class="btn btn-danger">
                <i class="fas fa-trash"></i> Hapus Log Lama
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* ============================================================
   ACTIVITY LOGS PAGE - AUDIT TRAIL EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.activity-logs-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.logs-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #0F172A 0%, #0EA5E9 60%, #06B6D4 100%);
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
    opacity: 0.5;
    animation: orbFloat 20s infinite ease-in-out;
}

.orb-1 {
    width: 320px; height: 320px;
    background: radial-gradient(circle, #06B6D4, transparent);
    top: -120px; right: 8%;
}

.orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, #0EA5E9, transparent);
    bottom: -100px; left: 15%;
    animation-delay: -7s;
}

.hero-grid-pattern {
    position: absolute;
    inset: 0;
    background-image: 
        linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 40px 40px;
}

.hero-matrix {
    position: absolute;
    inset: 0;
    background-image: 
        repeating-linear-gradient(0deg, transparent, transparent 39px, rgba(6,182,212,0.05) 39px, rgba(6,182,212,0.05) 40px);
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 24px;
    color: var(--white);
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 600;
    margin-bottom: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.hero-title {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 10px;
    line-height: 1.2;
    font-family: 'Space Grotesk', sans-serif;
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.hero-alert-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: linear-gradient(135deg, #EF4444, #DC2626);
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(239,68,68,0.4);
    animation: pulseBadge 2s infinite;
}

.hero-mini-stats {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.hms-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 10px;
    font-size: 0.85rem;
}

.hms-item i {
    color: #67E8F9;
}

.hms-item strong {
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
}

.hero-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-hero-primary {
    padding: 12px 24px;
    background: linear-gradient(135deg, #10B981, #047857);
    color: var(--white);
    border: none;
    border-radius: var(--radius);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all var(--transition);
    font-family: inherit;
    box-shadow: 0 4px 16px rgba(16,185,129,0.4);
}

.btn-hero-primary.active {
    background: linear-gradient(135deg, #F59E0B, #D97706);
    box-shadow: 0 4px 16px rgba(245,158,11,0.4);
}

.btn-hero-primary:hover {
    transform: translateY(-2px);
}

.btn-ghost {
    padding: 12px 18px;
    background: rgba(255,255,255,0.12);
    color: var(--white);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: var(--radius);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all var(--transition);
    font-family: inherit;
    backdrop-filter: blur(10px);
}

.btn-ghost:hover {
    background: rgba(255,255,255,0.22);
    transform: translateY(-2px);
}

/* ===== LAYOUT ===== */
.logs-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.logs-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.logs-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-cyan .stat-icon { background: linear-gradient(135deg, #06B6D4, #0891B2); }
.stat-blue .stat-icon { background: linear-gradient(135deg, #0EA5E9, #0369A1); }
.stat-green .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-red .stat-icon { background: linear-gradient(135deg, #EF4444, #DC2626); }
.stat-purple .stat-icon { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }

/* ===== TOOLBAR ===== */
.logs-toolbar {
    padding: 16px 20px;
    margin-bottom: 20px;
}

.logs-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.toolbar-left {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    flex: 1;
    min-width: 0;
    width: 100%;
}

.toolbar-search {
    position: relative;
    flex: 1;
    min-width: 240px;
    max-width: 380px;
}

.toolbar-search i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-400);
    font-size: 0.9rem;
    pointer-events: none;
}

.toolbar-search input {
    width: 100%;
    padding: 10px 44px 10px 42px;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: 0.88rem;
    font-family: inherit;
    background: var(--gray-50);
    transition: all var(--transition);
}

[data-theme="dark"] .toolbar-search input {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    color: var(--white);
}

.toolbar-search input:focus {
    outline: none;
    border-color: #06B6D4;
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(6,182,212,0.1);
}

[data-theme="dark"] .toolbar-search input:focus {
    background: rgba(255,255,255,0.08);
}

.search-kbd {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    padding: 2px 8px;
    background: var(--gray-200);
    color: var(--gray-600);
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    font-family: 'Courier New', monospace;
    pointer-events: none;
}

[data-theme="dark"] .search-kbd {
    background: rgba(255,255,255,0.1);
    color: var(--gray-400);
}

.toolbar-filters {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
    flex: 1;
}

.toolbar-select {
    padding: 9px 14px;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: 0.85rem;
    font-family: inherit;
    background: var(--white);
    cursor: pointer;
    transition: all var(--transition);
    color: var(--dark);
    min-width: 120px;
}

[data-theme="dark"] .toolbar-select {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    color: var(--white);
}

.toolbar-select:focus {
    outline: none;
    border-color: #06B6D4;
    box-shadow: 0 0 0 3px rgba(6,182,212,0.1);
}

.toolbar-icon-btn {
    width: 40px;
    height: 40px;
    border-radius: var(--radius);
    border: 1.5px solid var(--gray-200);
    background: var(--white);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-600);
    transition: all var(--transition);
    font-size: 0.9rem;
}

[data-theme="dark"] .toolbar-icon-btn {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    color: var(--gray-300);
}

.toolbar-icon-btn:hover {
    background: #06B6D4;
    color: var(--white);
    border-color: #06B6D4;
    transform: translateY(-2px);
}

/* ===== TIMELINE CARD ===== */
.logs-timeline-card {
    padding: 0;
    overflow: hidden;
}

.timeline-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 12px;
    background: var(--gray-50);
}

[data-theme="dark"] .timeline-header {
    background: rgba(0,0,0,0.2);
    border-color: rgba(255,255,255,0.08);
}

.timeline-header h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

[data-theme="dark"] .timeline-header h3 {
    color: var(--white);
}

.timeline-header h3 i {
    color: #06B6D4;
}

.timeline-header p {
    font-size: 0.82rem;
    color: var(--gray-600);
    margin: 0;
}

[data-theme="dark"] .timeline-header p {
    color: var(--gray-400);
}

.timeline-legend {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: var(--gray-600);
}

[data-theme="dark"] .legend-item {
    color: var(--gray-400);
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.timeline-list {
    padding: 8px 16px 24px;
    max-height: 800px;
    overflow-y: auto;
}

/* Date Group */
.timeline-date-group {
    margin-bottom: 24px;
}

.timeline-date-group:last-child {
    margin-bottom: 0;
}

.tdg-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    background: linear-gradient(90deg, rgba(6,182,212,0.08), transparent);
    border-left: 3px solid #06B6D4;
    border-radius: 6px;
    margin-bottom: 12px;
}

.tdg-label {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

[data-theme="dark"] .tdg-label {
    color: var(--white);
}

.tdg-count {
    font-size: 0.75rem;
    padding: 3px 10px;
    background: #06B6D4;
    color: var(--white);
    border-radius: 20px;
    font-weight: 600;
}

/* Log Item */
.log-item {
    display: flex;
    gap: 14px;
    padding: 14px 16px;
    margin-bottom: 8px;
    border-radius: var(--radius);
    cursor: pointer;
    transition: all var(--transition-fast);
    position: relative;
    border: 1px solid transparent;
}

.log-item:hover {
    background: var(--gray-50);
    border-color: var(--gray-200);
    transform: translateX(4px);
}

[data-theme="dark"] .log-item:hover {
    background: rgba(255,255,255,0.04);
    border-color: rgba(255,255,255,0.1);
}

.log-item.suspicious {
    background: rgba(239,68,68,0.04);
    border-color: rgba(239,68,68,0.2);
}

.log-item.suspicious:hover {
    background: rgba(239,68,68,0.08);
    border-color: rgba(239,68,68,0.3);
}

.li-timeline {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    position: relative;
}

.li-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    flex-shrink: 0;
    position: relative;
    z-index: 2;
}

.li-pulse {
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px solid #EF4444;
    animation: suspiciousPulse 1.5s infinite;
}

@keyframes suspiciousPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.5); opacity: 0; }
}

.li-line {
    width: 2px;
    flex: 1;
    background: var(--gray-200);
    margin-top: 8px;
}

[data-theme="dark"] .li-line {
    background: rgba(255,255,255,0.08);
}

.li-content {
    flex: 1;
    min-width: 0;
}

.li-header {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 8px;
}

.li-icon {
    width: 38px;
    height: 38px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}

.li-info {
    flex: 1;
    min-width: 0;
}

.li-title {
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 4px;
    line-height: 1.4;
    word-break: break-word;
}

[data-theme="dark"] .li-title {
    color: var(--white);
}

.li-title mark {
    background: rgba(245,158,11,0.3);
    color: inherit;
    padding: 0 2px;
    border-radius: 2px;
}

.li-meta {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    font-size: 0.78rem;
    color: var(--gray-600);
}

[data-theme="dark"] .li-meta {
    color: var(--gray-400);
}

.li-actor, .li-module {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.li-actor i, .li-module i {
    color: var(--gray-400);
    font-size: 0.7rem;
}

.li-actor strong {
    color: var(--dark);
    font-weight: 600;
}

[data-theme="dark"] .li-actor strong {
    color: var(--white);
}

.li-action-badge {
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
}

.li-time {
    font-size: 0.78rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 5px;
    flex-shrink: 0;
    font-family: 'Courier New', monospace;
    font-weight: 600;
}

.li-time i {
    font-size: 0.7rem;
}

.li-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding-left: 50px;
    flex-wrap: wrap;
}

.li-ip {
    font-size: 0.75rem;
    color: var(--gray-600);
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-family: 'Courier New', monospace;
}

[data-theme="dark"] .li-ip {
    color: var(--gray-400);
}

.li-ip i {
    color: #06B6D4;
    font-size: 0.7rem;
}

.li-ip small {
    color: var(--gray-500);
    font-family: inherit;
    font-style: italic;
}

.li-detail-btn {
    padding: 4px 10px;
    background: transparent;
    color: #06B6D4;
    border: 1px solid #06B6D4;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all var(--transition-fast);
    font-family: inherit;
}

.li-detail-btn:hover {
    background: #06B6D4;
    color: var(--white);
}

.timeline-footer {
    padding: 14px 24px;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-100);
    text-align: center;
}

[data-theme="dark"] .timeline-footer {
    background: rgba(0,0,0,0.2);
    border-color: rgba(255,255,255,0.08);
}

.timeline-footer p {
    font-size: 0.78rem;
    color: var(--gray-600);
    margin: 0;
}

[data-theme="dark"] .timeline-footer p {
    color: var(--gray-400);
}

.timeline-footer i {
    color: #06B6D4;
    margin-right: 6px;
}

/* ===== EMPTY STATE ===== */
.empty-state-ult {
    padding: 60px 20px;
    text-align: center;
}

.empty-illustration {
    margin-bottom: 24px;
    display: inline-block;
    animation: floatIllustration 3s ease-in-out infinite;
}

@keyframes floatIllustration {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.empty-state-ult h3 {
    font-size: 1.3rem;
    color: var(--dark);
    margin-bottom: 8px;
}

[data-theme="dark"] .empty-state-ult h3 {
    color: var(--white);
}

.empty-state-ult p {
    color: var(--gray-600);
    margin-bottom: 24px;
}

[data-theme="dark"] .empty-state-ult p {
    color: var(--gray-400);
}

/* ===== SIDEBAR WIDGETS ===== */
.logs-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
    position: sticky;
    top: 90px;
}

.widget-card {
    overflow: hidden;
}

.widget-header {
    padding: 16px 18px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--gray-50);
}

[data-theme="dark"] .widget-header {
    background: rgba(0,0,0,0.2);
    border-color: rgba(255,255,255,0.08);
}

.widget-header h3 {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

[data-theme="dark"] .widget-header h3 {
    color: var(--white);
}

.widget-header h3 i {
    color: #06B6D4;
}

.widget-badge {
    padding: 3px 10px;
    background: #06B6D4;
    color: var(--white);
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
}

.widget-body {
    padding: 16px 18px;
}

.widget-empty {
    text-align: center;
    padding: 24px 12px;
    color: var(--gray-600);
}

[data-theme="dark"] .widget-empty {
    color: var(--gray-400);
}

.widget-empty i {
    font-size: 2rem;
    color: var(--gray-300);
    margin-bottom: 8px;
    display: block;
}

[data-theme="dark"] .widget-empty i {
    color: var(--gray-600);
}

.widget-empty p {
    font-size: 0.82rem;
    margin: 0;
}

/* ===== ACTIVITY CHART ===== */
.activity-chart {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    height: 140px;
    padding: 0 4px;
}

.ac-bar-group {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    min-width: 0;
}

.ac-bar-value {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--gray-700);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .ac-bar-value {
    color: var(--gray-300);
}

.ac-bar-wrapper {
    width: 100%;
    height: 100px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.ac-bar {
    width: 100%;
    max-width: 28px;
    background: linear-gradient(180deg, #06B6D4, #0891B2);
    border-radius: 6px 6px 3px 3px;
    transition: all var(--transition-fast);
    position: relative;
    animation: barGrow 0.8s ease-out;
}

.ac-bar:hover {
    background: linear-gradient(180deg, #22D3EE, #06B6D4);
    box-shadow: 0 4px 12px rgba(6,182,212,0.4);
}

.ac-bar.today {
    background: linear-gradient(180deg, #F59E0B, #D97706);
}

.ac-bar.today:hover {
    box-shadow: 0 4px 12px rgba(245,158,11,0.4);
}

@keyframes barGrow {
    from { height: 0 !important; }
}

.ac-bar-label {
    font-size: 0.68rem;
    color: var(--gray-600);
    font-weight: 500;
}

[data-theme="dark"] .ac-bar-label {
    color: var(--gray-400);
}

.chart-footer {
    padding-top: 12px;
    margin-top: 12px;
    border-top: 1px solid var(--gray-100);
    text-align: center;
    font-size: 0.78rem;
    color: var(--gray-600);
}

[data-theme="dark"] .chart-footer {
    border-color: rgba(255,255,255,0.08);
    color: var(--gray-400);
}

.chart-footer strong {
    color: #06B6D4;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== HOURLY HEATMAP ===== */
.hourly-heatmap {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 3px;
    margin-bottom: 12px;
}

.hh-cell {
    aspect-ratio: 1;
    border-radius: 4px;
    position: relative;
    transition: all var(--transition-fast);
    cursor: pointer;
}

.hh-cell:hover {
    transform: scale(1.2);
    box-shadow: 0 0 0 2px var(--white);
}

[data-theme="dark"] .hh-cell:hover {
    box-shadow: 0 0 0 2px var(--dark-2);
}

.hh-cell.current {
    outline: 2px solid #F59E0B;
    outline-offset: 1px;
}

.hh-now {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 4px;
    height: 4px;
    background: #F59E0B;
    border-radius: 50%;
    animation: nowPulse 1.5s infinite;
}

@keyframes nowPulse {
    0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
    50% { transform: translate(-50%, -50%) scale(1.5); opacity: 0.5; }
}

.heatmap-legend {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 12px;
    font-size: 0.7rem;
    color: var(--gray-600);
}

[data-theme="dark"] .heatmap-legend {
    color: var(--gray-400);
}

.hh-legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.hh-dot {
    width: 10px;
    height: 10px;
    border-radius: 2px;
}

.peak-info {
    padding: 10px 12px;
    background: var(--gray-50);
    border-radius: var(--radius-sm);
    font-size: 0.78rem;
    color: var(--gray-700);
    display: flex;
    align-items: center;
    gap: 8px;
}

[data-theme="dark"] .peak-info {
    background: rgba(255,255,255,0.04);
    color: var(--gray-300);
}

.peak-info strong {
    color: #06B6D4;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== TOP ADMINS ===== */
.admin-item {
    display: grid;
    grid-template-columns: 24px 32px 1fr auto;
    gap: 10px;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .admin-item {
    border-color: rgba(255,255,255,0.05);
}

.admin-item:last-child {
    border-bottom: none;
}

.admin-rank {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--gray-500);
    text-align: center;
}

.admin-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-weight: 700;
    font-size: 0.82rem;
    font-family: 'Space Grotesk', sans-serif;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.admin-info {
    min-width: 0;
}

.admin-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

[data-theme="dark"] .admin-name {
    color: var(--gray-200);
}

.admin-bar {
    height: 4px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .admin-bar {
    background: rgba(255,255,255,0.08);
}

.admin-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

.admin-count {
    font-size: 0.88rem;
    font-weight: 700;
    color: #06B6D4;
    font-family: 'Space Grotesk', sans-serif;
    min-width: 30px;
    text-align: right;
}

/* ===== MODULES ===== */
.module-item {
    margin-bottom: 12px;
}

.module-item:last-child {
    margin-bottom: 0;
}

.module-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.module-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 6px;
}

[data-theme="dark"] .module-label {
    color: var(--gray-200);
}

.module-label i {
    color: #06B6D4;
    font-size: 0.75rem;
}

.module-count {
    font-size: 0.75rem;
    font-weight: 700;
    color: #06B6D4;
    padding: 2px 8px;
    background: rgba(6,182,212,0.1);
    border-radius: 10px;
    font-family: 'Space Grotesk', sans-serif;
}

.module-bar {
    height: 5px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .module-bar {
    background: rgba(255,255,255,0.08);
}

.module-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #06B6D4, #0EA5E9);
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

/* ===== QUICK ACTIONS ===== */
.qa-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: var(--radius-sm);
    color: var(--dark);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all var(--transition-fast);
    margin-bottom: 6px;
}

[data-theme="dark"] .qa-link {
    color: var(--gray-200);
}

.qa-link:hover {
    background: var(--gray-50);
    transform: translateX(4px);
}

[data-theme="dark"] .qa-link:hover {
    background: rgba(255,255,255,0.05);
}

.qa-link i:first-child {
    color: #06B6D4;
    width: 16px;
}

.qa-link span {
    flex: 1;
}

.qa-link i:last-child {
    color: var(--gray-400);
    font-size: 0.7rem;
    opacity: 0;
    transition: opacity var(--transition-fast);
}

.qa-link:hover i:last-child {
    opacity: 1;
}

/* ===== LOG DETAIL MODAL ===== */
.logs-modal-head {
    background: linear-gradient(135deg, #0F172A 0%, #0EA5E9 60%, #06B6D4 100%) !important;
}

.log-detail-hero {
    padding: 24px;
    background: linear-gradient(135deg, var(--gray-50), transparent);
    border-bottom: 1px solid var(--gray-100);
    margin: -28px -28px 20px;
}

[data-theme="dark"] .log-detail-hero {
    background: linear-gradient(135deg, rgba(0,0,0,0.2), transparent);
    border-color: rgba(255,255,255,0.08);
}

.log-detail-icon {
    width: 54px;
    height: 54px;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 14px;
}

.log-detail-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 8px;
    line-height: 1.4;
}

[data-theme="dark"] .log-detail-title {
    color: var(--white);
}

.log-detail-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.log-detail-section {
    margin-bottom: 18px;
}

.log-detail-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-500);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.log-detail-value {
    font-size: 0.92rem;
    color: var(--dark);
    line-height: 1.6;
}

[data-theme="dark"] .log-detail-value {
    color: var(--gray-200);
}

.log-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 14px;
    background: var(--gray-50);
    border-radius: var(--radius);
    margin-bottom: 16px;
}

[data-theme="dark"] .log-detail-grid {
    background: rgba(255,255,255,0.04);
}

.log-detail-code {
    padding: 12px 14px;
    background: var(--dark);
    color: #67E8F9;
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: 0.78rem;
    line-height: 1.5;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-all;
}

[data-theme="dark"] .log-detail-code {
    background: #0F172A;
}

/* ===== CLEANUP MODAL ===== */
.cleanup-warning {
    display: flex;
    gap: 12px;
    padding: 14px;
    background: rgba(245,158,11,0.1);
    border-left: 4px solid #F59E0B;
    border-radius: var(--radius);
    margin-bottom: 20px;
}

.cleanup-warning i {
    color: #F59E0B;
    font-size: 1.3rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.cleanup-warning strong {
    color: var(--dark);
    font-size: 0.95rem;
    display: block;
    margin-bottom: 4px;
}

[data-theme="dark"] .cleanup-warning strong {
    color: var(--white);
}

.cleanup-warning p {
    font-size: 0.82rem;
    color: var(--gray-600);
    margin: 0;
}

[data-theme="dark"] .cleanup-warning p {
    color: var(--gray-400);
}

.cleanup-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.cleanup-option {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    background: var(--gray-50);
    border-radius: var(--radius);
    cursor: pointer;
    transition: all var(--transition);
    border: 2px solid transparent;
}

[data-theme="dark"] .cleanup-option {
    background: rgba(255,255,255,0.04);
}

.cleanup-option:hover {
    background: var(--gray-100);
}

[data-theme="dark"] .cleanup-option:hover {
    background: rgba(255,255,255,0.08);
}

.cleanup-option input {
    margin-top: 4px;
    accent-color: #DC2626;
}

.cleanup-option:has(input:checked) {
    border-color: #DC2626;
    background: rgba(220,38,38,0.05);
}

.cleanup-option-content strong {
    display: block;
    font-size: 0.95rem;
    color: var(--dark);
    margin-bottom: 2px;
}

[data-theme="dark"] .cleanup-option-content strong {
    color: var(--white);
}

.cleanup-option-content small {
    font-size: 0.78rem;
    color: var(--gray-600);
}

[data-theme="dark"] .cleanup-option-content small {
    color: var(--gray-400);
}

.cleanup-info {
    padding: 12px 14px;
    background: var(--gray-50);
    border-radius: var(--radius-sm);
    margin-top: 16px;
    font-size: 0.85rem;
    color: var(--gray-700);
    display: flex;
    align-items: center;
    gap: 8px;
}

[data-theme="dark"] .cleanup-info {
    background: rgba(255,255,255,0.04);
    color: var(--gray-300);
}

.cleanup-info i {
    color: #06B6D4;
}

.cleanup-info strong {
    color: #06B6D4;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== BUTTONS ===== */
.btn-danger {
    padding: 10px 20px;
    background: linear-gradient(135deg, #EF4444, #DC2626);
    color: var(--white);
    border: none;
    border-radius: var(--radius);
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all var(--transition);
    font-family: inherit;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(239,68,68,0.3);
}

.btn-danger:hover {
    background: linear-gradient(135deg, #DC2626, #991B1B);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239,68,68,0.4);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .logs-layout {
        grid-template-columns: 1fr;
    }
    
    .logs-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .activity-logs-page {
        padding: 16px;
    }
    
    .logs-hero {
        padding: 24px 20px;
    }
    
    .hero-title {
        font-size: 1.6rem;
    }
    
    .hero-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .hero-actions {
        width: 100%;
    }
    
    .logs-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .toolbar-left {
        flex-direction: column;
        align-items: stretch;
    }
    
    .toolbar-search {
        max-width: 100%;
    }
    
    .toolbar-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .toolbar-select {
        width: 100%;
    }
    
    .timeline-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .timeline-legend {
        width: 100%;
    }
    
    .li-header {
        flex-direction: column;
    }
    
    .li-time {
        align-self: flex-start;
        padding-left: 50px;
    }
    
    .li-footer {
        padding-left: 0;
    }
    
    .log-detail-grid {
        grid-template-columns: 1fr;
    }
    
    .hourly-heatmap {
        grid-template-columns: repeat(8, 1fr);
    }
}

@media print {
    .logs-hero,
    .logs-toolbar,
    .logs-sidebar,
    .li-detail-btn,
    .timeline-footer {
        display: none !important;
    }
    
    .logs-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
(function() {
    'use strict';

    /* ===== COUNTER ANIMATION ===== */
    const counters = document.querySelectorAll('.stat-counter');
    const animateCounter = (el) => {
        const target = parseInt(el.dataset.target) || 0;
        const duration = 1200;
        const start = performance.now();
        
        const step = (now) => {
            const p = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.floor(target * eased).toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString('id-ID');
        };
        requestAnimationFrame(step);
    };
    
    const counterObs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                animateCounter(e.target);
                counterObs.unobserve(e.target);
            }
        });
    }, { threshold: 0.3 });
    
    counters.forEach(c => counterObs.observe(c));

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterModule = document.getElementById('filterModule');
    const filterAction = document.getElementById('filterAction');
    const filterAdmin = document.getElementById('filterAdmin');
    const filterDate = document.getElementById('filterDate');
    const filterLimit = document.getElementById('filterLimit');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    searchInput?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(searchTimeout);
            applyFilters();
        }
    });

    [filterModule, filterAction, filterAdmin, filterDate, filterLimit].forEach(el => {
        el?.addEventListener('change', applyFilters);
    });

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const module = filterModule?.value;
        const action = filterAction?.value;
        const admin = filterAdmin?.value;
        const date = filterDate?.value;
        const limit = filterLimit?.value;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (module) url.searchParams.set('module', module); else url.searchParams.delete('module');
        if (action) url.searchParams.set('action_type', action); else url.searchParams.delete('action_type');
        if (admin) url.searchParams.set('admin', admin); else url.searchParams.delete('admin');
        if (date) url.searchParams.set('date', date); else url.searchParams.delete('date');
        if (limit) url.searchParams.set('limit', limit); else url.searchParams.delete('limit');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'module', 'action_type', 'admin', 'date', 'limit'].forEach(p => url.searchParams.delete(p));
        window.location = url;
    };

    /* ===== LOG DETAIL MODAL ===== */
    const actionConfig = <?= json_encode($actionConfig) ?>;
    const moduleIcons = <?= json_encode($moduleIcons) ?>;

    window.openLogDetail = function(element) {
        const logData = JSON.parse(element.dataset.log);
        const action = logData.action || 'unknown';
        const cfg = actionConfig[action] || {label: action, color: '#6B7280', icon: 'fa-circle', bg: 'rgba(107,114,128,0.12)'};
        const module = logData.module || '';
        const mIcon = moduleIcons[module] || 'fa-cube';
        const isSuspicious = ['login_failed', 'unauthorized_access'].includes(action);
        
        const body = document.getElementById('logDetailBody');
        body.innerHTML = `
            <div class="log-detail-hero">
                <div class="log-detail-icon" style="background: ${cfg.bg}; color: ${cfg.color}">
                    <i class="fas ${cfg.icon}"></i>
                </div>
                <h3 class="log-detail-title">${logData.description || cfg.label}</h3>
                <div class="log-detail-badges">
                    <span class="badge" style="background: ${cfg.bg}; color: ${cfg.color}">
                        <i class="fas ${cfg.icon}"></i> ${cfg.label}
                    </span>
                    ${module ? `<span class="badge badge-primary"><i class="fas ${mIcon}"></i> ${module}</span>` : ''}
                    ${isSuspicious ? '<span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Suspicious</span>' : ''}
                    <span class="badge badge-${logData.admin_id ? 'success' : 'default'}">
                        <i class="fas fa-${logData.admin_id ? 'user' : 'robot'}"></i> ${logData.admin_id ? 'Admin' : 'System'}
                    </span>
                </div>
            </div>
            
            <div class="log-detail-grid">
                <div>
                    <div class="log-detail-label"><i class="fas fa-user"></i> Actor</div>
                    <div class="log-detail-value">${logData.admin_name || logData.admin_username || 'System'}</div>
                    ${logData.admin_email ? `<div style="font-size:0.78rem;color:var(--gray-500);margin-top:2px">${logData.admin_email}</div>` : ''}
                </div>
                <div>
                    <div class="log-detail-label"><i class="fas fa-clock"></i> Waktu</div>
                    <div class="log-detail-value">${new Date(logData.created_at).toLocaleString('id-ID', {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    })}</div>
                </div>
                ${logData.ip_address ? `
                <div>
                    <div class="log-detail-label"><i class="fas fa-globe"></i> IP Address</div>
                    <div class="log-detail-value" style="font-family:'Courier New',monospace">${logData.ip_address}</div>
                </div>
                ` : ''}
                ${logData.user_agent ? `
                <div>
                    <div class="log-detail-label"><i class="fas fa-desktop"></i> Device</div>
                    <div class="log-detail-value" style="font-size:0.78rem">${parseUserAgent(logData.user_agent)}</div>
                </div>
                ` : ''}
            </div>
            
            ${logData.url ? `
            <div class="log-detail-section">
                <div class="log-detail-label"><i class="fas fa-link"></i> URL</div>
                <div class="log-detail-value" style="font-family:'Courier New',monospace;font-size:0.82rem;word-break:break-all">${logData.url}</div>
            </div>
            ` : ''}
            
            ${logData.details || logData.data ? `
            <div class="log-detail-section">
                <div class="log-detail-label"><i class="fas fa-code"></i> Detail Data</div>
                <pre class="log-detail-code">${formatJSON(logData.details || logData.data)}</pre>
            </div>
            ` : ''}
        `;
        
        document.getElementById('logDetailModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closeLogDetail = function() {
        document.getElementById('logDetailModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    function parseUserAgent(ua) {
        if (!ua) return 'Unknown';
        if (ua.includes('Chrome') && !ua.includes('Edg')) return '🌐 Google Chrome';
        if (ua.includes('Firefox')) return '🦊 Mozilla Firefox';
        if (ua.includes('Safari') && !ua.includes('Chrome')) return '🧭 Safari';
        if (ua.includes('Edg')) return '🔷 Microsoft Edge';
        if (ua.includes('Mobile')) return '📱 Mobile Browser';
        return '🖥️ Web Browser';
    }

    function formatJSON(data) {
        if (!data) return '';
        try {
            if (typeof data === 'string') {
                const parsed = JSON.parse(data);
                return JSON.stringify(parsed, null, 2);
            }
            return JSON.stringify(data, null, 2);
        } catch (e) {
            return data;
        }
    }

    /* ===== CLEANUP MODAL ===== */
    window.openCleanupModal = function() {
        document.getElementById('cleanupModal')?.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closeCleanupModal = function() {
        document.getElementById('cleanupModal')?.classList.remove('active');
        document.body.style.overflow = '';
    };

    // Update cleanup confirm URL when option changes
    document.querySelectorAll('input[name="cleanup_days"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const days = this.value;
            document.getElementById('cleanupConfirmBtn').href = `?action=clear&days=${days}`;
        });
    });

    // Set default URL
    const defaultDays = document.querySelector('input[name="cleanup_days"]:checked')?.value || 30;
    const cleanupBtn = document.getElementById('cleanupConfirmBtn');
    if (cleanupBtn) {
        cleanupBtn.href = `?action=clear&days=${defaultDays}`;
        cleanupBtn.addEventListener('click', function(e) {
            if (!confirm(`Yakin ingin menghapus log yang lebih lama dari ${defaultDays} hari?\n\nAksi ini TIDAK BISA dibatalkan!`)) {
                e.preventDefault();
            }
        });
    }

    /* ===== EXPORT CSV ===== */
    window.exportLogs = function() {
        const logs = <?= json_encode($logs) ?>;
        
        if (!logs || logs.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['ID', 'Waktu', 'Admin', 'Module', 'Action', 'Description', 'IP Address', 'URL'];
        const rows = logs.map(log => [
            log.id,
            log.created_at,
            `"${(log.admin_name || log.admin_username || 'System').replace(/"/g, '""')}"`,
            log.module || '',
            log.action,
            `"${(log.description || '').replace(/"/g, '""')}"`,
            log.ip_address || '',
            `"${(log.url || '').replace(/"/g, '""')}"`
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `activity-logs-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${logs.length} log berhasil di-export`, 'success');
        }, 500);
    };

    /* ===== AUTO REFRESH ===== */
    let autoRefreshInterval = null;
    let isAutoRefresh = localStorage.getItem('logs_auto_refresh') === 'true';

    function startAutoRefresh() {
        autoRefreshInterval = setInterval(() => {
            const refreshBtn = document.getElementById('refreshBtn');
            const icon = refreshBtn?.querySelector('i');
            icon?.classList.add('fa-spin');
            
            setTimeout(() => {
                window.location.reload();
            }, 800);
        }, 30000); // 30 seconds
        
        document.getElementById('autoRefreshBtn')?.classList.add('active');
        document.getElementById('autoRefreshText').textContent = 'Auto-Refresh: ON (30s)';
    }

    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
        document.getElementById('autoRefreshBtn')?.classList.remove('active');
        document.getElementById('autoRefreshText').textContent = 'Auto-Refresh: OFF';
    }

    window.toggleAutoRefresh = function() {
        isAutoRefresh = !isAutoRefresh;
        localStorage.setItem('logs_auto_refresh', isAutoRefresh);
        
        if (isAutoRefresh) {
            startAutoRefresh();
            if (window.IFPro?.toast) window.IFPro.toast.show('Auto-refresh aktif (30 detik)', 'success');
        } else {
            stopAutoRefresh();
            if (window.IFPro?.toast) window.IFPro.toast.show('Auto-refresh dimatikan', 'info');
        }
    };

    // Restore auto-refresh state
    if (isAutoRefresh) {
        startAutoRefresh();
    }

    /* ===== MANUAL REFRESH ===== */
    document.getElementById('refreshBtn')?.addEventListener('click', function() {
        const icon = this.querySelector('i');
        icon.classList.add('fa-spin');
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memperbarui data...', 'info');
        
        setTimeout(() => {
            window.location.reload();
        }, 500);
    });

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        // / = Focus search
        if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            searchInput?.focus();
        }
        
        // R = Refresh
        if (e.key === 'r' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) && !e.ctrlKey && !e.metaKey) {
            e.preventDefault();
            document.getElementById('refreshBtn')?.click();
        }
        
        // Escape = Close modals
        if (e.key === 'Escape') {
            closeLogDetail();
            closeCleanupModal();
        }
    });

    /* ===== MODAL BACKDROP CLICK ===== */
    document.getElementById('logDetailModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeLogDetail();
    });
    
    document.getElementById('cleanupModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeCleanupModal();
    });

    /* ===== AUTO DISMISS ALERTS ===== */
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'all 0.4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 400);
        });
    }, 5000);

    console.log('%c📋 Activity Logs v4.0 Loaded', 'color:#06B6D4;font-weight:bold;font-size:14px');
    console.log('%c   Features: Audit Trail, Heatmap, 7-Day Chart, Detail Modal, CSV Export', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: / (Search), R (Refresh), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>