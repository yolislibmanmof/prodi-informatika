<?php
require_once 'includes/auth.php';
Auth::requireRole('superadmin');
$pageTitle = 'Log Keamanan';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
    setFlash('error', 'Koneksi database gagal');
}

/* ===== HANDLE ACTIONS ===== */
if (isset($_GET['action']) && $_GET['action'] === 'clear' && $db) {
    try {
        $days = (int)($_GET['days'] ?? 30);
        $db->prepare("DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)")->execute([$days]);
        setFlash('success', "Log keamanan lebih dari $days hari berhasil dibersihkan");
    } catch (Throwable $e) {
        setFlash('error', 'Gagal membersihkan log');
    }
    header('Location: security-logs.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$filterEvent = $_GET['event'] ?? '';
$filterSeverity = $_GET['severity'] ?? '';
$searchIP = $_GET['ip'] ?? '';
$limit = min((int)($_GET['limit'] ?? 50), 200);

$where = "1=1";
$params = [];

if ($filterEvent) { $where .= " AND event_type = ?"; $params[] = $filterEvent; }
if ($filterSeverity) { $where .= " AND severity = ?"; $params[] = $filterSeverity; }
if ($searchIP) { $where .= " AND ip_address LIKE ?"; $params[] = "%$searchIP%"; }

/* ===== FETCH DATA ===== */
$logs = [];
$stats = ['total' => 0, 'today' => 0, 'critical' => 0, 'warnings' => 0];
$events = [];
$severityStats = [];
$topIPs = [];

if ($db) {
    try {
        $sql = "SELECT * FROM security_logs WHERE $where ORDER BY created_at DESC LIMIT ?";
        $stmt = $db->prepare($sql);
        $params[] = $limit;
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM security_logs")->fetchColumn();
        $stats['today'] = (int)$db->query("SELECT COUNT(*) FROM security_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $stats['critical'] = (int)$db->query("SELECT COUNT(*) FROM security_logs WHERE severity = 'critical' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $stats['warnings'] = (int)$db->query("SELECT COUNT(*) FROM security_logs WHERE severity = 'warning' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        
        $events = $db->query("SELECT DISTINCT event_type FROM security_logs ORDER BY event_type")->fetchAll(PDO::FETCH_COLUMN);
        $severityStats = $db->query("SELECT severity, COUNT(*) as total FROM security_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY severity ORDER BY total DESC")->fetchAll();
        $topIPs = $db->query("SELECT ip_address, COUNT(*) as total FROM security_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY ip_address ORDER BY total DESC LIMIT 5")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== CONFIGURATION ===== */
$severityColors = [
    'info' => ['#3B82F6', '#1E40AF', 'fa-info-circle'],
    'warning' => ['#F59E0B', '#D97706', 'fa-exclamation-triangle'],
    'critical' => ['#EF4444', '#B91C1C', 'fa-exclamation-circle'],
];

$eventIcons = [
    'login_success' => 'fa-sign-in-alt',
    'login_failed' => 'fa-times-circle',
    'login_blocked_locked' => 'fa-lock',
    'account_locked' => 'fa-user-lock',
    'logout_success' => 'fa-sign-out-alt',
    'unauthorized_access_attempt' => 'fa-user-shield',
    'insufficient_role' => 'fa-user-slash',
    'csrf_fail' => 'fa-shield-alt',
    'bot_trap' => 'fa-robot',
    'rate_limit' => 'fa-tachometer-alt',
    'upload_blocked' => 'fa-file-upload',
    'remember_token_theft' => 'fa-cookie-bite',
];

include 'includes/header.php';
?>

<div class="page-content security-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="security-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-academic-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security Monitoring</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-lock"></i>
                    </span>
                    Log Keamanan
                </h1>
                <p class="hero-subtitle">
                    Monitoring dan analisis kejadian keamanan sistem secara real-time
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-shield-alt"></i>
                        <span><strong><?= number_format($stats['total']) ?></strong> Total Events</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-calendar-day" style="color:#10B981"></i>
                        <span><strong><?= $stats['today'] ?></strong> Hari Ini</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-exclamation-circle" style="color:#EF4444"></i>
                        <span><strong><?= $stats['critical'] ?></strong> Critical (24h)</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <a href="?action=clear&days=7" class="btn btn-ghost" onclick="return confirm('Hapus log lebih dari 7 hari?')">
                    <i class="fas fa-broom"></i>
                    <span>Cleanup 7 Hari</span>
                </a>
                <a href="?action=clear&days=30" class="btn btn-hero-primary" style="background: linear-gradient(135deg, #EF4444, #B91C1C); box-shadow: 0 8px 20px rgba(239,68,68,0.4);" onclick="return confirm('Hapus log lebih dari 30 hari?')">
                    <i class="fas fa-broom"></i>
                    <span>Cleanup 30 Hari</span>
                </a>
            </div>
        </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?> fade-in">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <span><?= clean($flash['message']) ?></span>
        <button onclick="this.parentElement.remove()" class="alert-close"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>

    <!-- Critical Alert -->
    <?php if ($stats['critical'] > 0): ?>
    <div class="critical-alert">
        <div class="critical-alert-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="critical-alert-content">
            <strong>Perhatian! Ada <?= $stats['critical'] ?> Kejadian Kritis</strong>
            <span>Ditemukan <?= $stats['critical'] ?> log dengan severity critical dalam 24 jam terakhir. Silakan review segera.</span>
        </div>
        <button onclick="this.parentElement.remove()" class="critical-alert-close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- ===== MAIN LAYOUT ===== -->
    <div class="security-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="security-main-col">
            <!-- Toolbar -->
            <div class="card security-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchIP" placeholder="Cari IP address..." value="<?= clean($searchIP) ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <!-- Event Dropdown -->
                    <div class="custom-dropdown" id="eventDropdown">
                        <button class="dropdown-trigger" id="eventTrigger">
                            <i class="fas fa-bell" style="color: #6B7280"></i>
                            <span id="eventLabel"><?= $filterEvent ? str_replace('_', ' ', ucfirst($filterEvent)) : 'Semua Event' ?></span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu" id="eventMenu">
                            <div class="dropdown-item <?= !$filterEvent ? 'active' : '' ?>" data-value="">
                                <i class="fas fa-th-large" style="color: #6B7280"></i>
                                <span>Semua Event</span>
                                <span class="dropdown-count"><?= $stats['total'] ?></span>
                            </div>
                            <?php foreach ($events as $e): 
                                $icon = $eventIcons[$e] ?? 'fa-bell';
                            ?>
                            <div class="dropdown-item <?= $filterEvent === $e ? 'active' : '' ?>" data-value="<?= clean($e) ?>">
                                <i class="fas <?= $icon ?>" style="color: #6B7280"></i>
                                <span><?= str_replace('_', ' ', ucfirst(clean($e))) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Severity Dropdown -->
                    <div class="custom-dropdown" id="severityDropdown">
                        <button class="dropdown-trigger" id="severityTrigger">
                            <i class="fas fa-flag" style="color: <?= $filterSeverity ? ($severityColors[$filterSeverity][0] ?? '#6B7280') : '#6B7280' ?>"></i>
                            <span id="severityLabel"><?= $filterSeverity ? ucfirst($filterSeverity) : 'Semua Severity' ?></span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu" id="severityMenu">
                            <div class="dropdown-item <?= !$filterSeverity ? 'active' : '' ?>" data-value="">
                                <i class="fas fa-th-large" style="color: #6B7280"></i>
                                <span>Semua Severity</span>
                            </div>
                            <div class="dropdown-item <?= $filterSeverity === 'info' ? 'active' : '' ?>" data-value="info">
                                <i class="fas fa-info-circle" style="color: #3B82F6"></i>
                                <span>Info</span>
                            </div>
                            <div class="dropdown-item <?= $filterSeverity === 'warning' ? 'active' : '' ?>" data-value="warning">
                                <i class="fas fa-exclamation-triangle" style="color: #F59E0B"></i>
                                <span>Warning</span>
                            </div>
                            <div class="dropdown-item <?= $filterSeverity === 'critical' ? 'active' : '' ?>" data-value="critical">
                                <i class="fas fa-exclamation-circle" style="color: #EF4444"></i>
                                <span>Critical</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Limit Dropdown -->
                    <div class="custom-dropdown" id="limitDropdown">
                        <button class="dropdown-trigger" id="limitTrigger">
                            <i class="fas fa-list-ol" style="color: #6B7280"></i>
                            <span id="limitLabel"><?= $limit ?> Terbaru</span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu" id="limitMenu">
                            <div class="dropdown-item <?= $limit === 50 ? 'active' : '' ?>" data-value="50">
                                <i class="fas fa-list" style="color: #6B7280"></i>
                                <span>50 Terbaru</span>
                            </div>
                            <div class="dropdown-item <?= $limit === 100 ? 'active' : '' ?>" data-value="100">
                                <i class="fas fa-list" style="color: #6B7280"></i>
                                <span>100 Terbaru</span>
                            </div>
                            <div class="dropdown-item <?= $limit === 200 ? 'active' : '' ?>" data-value="200">
                                <i class="fas fa-list" style="color: #6B7280"></i>
                                <span>200 Terbaru</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <button class="toolbar-icon-btn" onclick="resetFilters()" title="Reset Filter">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="card security-table-card">
                <div class="table-header">
                    <h3><i class="fas fa-list-alt"></i> Daftar Log Keamanan</h3>
                    <span class="table-count"><?= count($logs) ?> log ditampilkan</span>
                </div>
                
                <?php if (!empty($logs)): ?>
                <div class="table-responsive">
                    <table class="security-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-clock"></i> Waktu</th>
                                <th><i class="fas fa-bell"></i> Event</th>
                                <th><i class="fas fa-user"></i> Username</th>
                                <th><i class="fas fa-network-wired"></i> IP Address</th>
                                <th><i class="fas fa-flag"></i> Severity</th>
                                <th><i class="fas fa-info-circle"></i> Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): 
                                $sev = $severityColors[$log['severity']] ?? $severityColors['info'];
                                $icon = $eventIcons[$log['event_type']] ?? 'fa-bell';
                            ?>
                            <tr class="<?= $log['severity'] === 'critical' ? 'row-critical' : '' ?>">
                                <td class="col-time">
                                    <i class="fas fa-clock"></i>
                                    <?= date('d M Y H:i', strtotime($log['created_at'])) ?>
                                </td>
                                <td class="col-event">
                                    <span class="event-badge">
                                        <i class="fas <?= $icon ?>"></i>
                                        <?= str_replace('_', ' ', ucfirst($log['event_type'])) ?>
                                    </span>
                                </td>
                                <td class="col-user">
                                    <?= $log['username'] ? '<i class="fas fa-user"></i> ' . clean($log['username']) : '<span class="text-muted">-</span>' ?>
                                </td>
                                <td class="col-ip">
                                    <code class="ip-code"><?= clean($log['ip_address']) ?></code>
                                </td>
                                <td class="col-severity">
                                    <span class="severity-badge severity-<?= $log['severity'] ?>">
                                        <i class="fas <?= $sev[2] ?>"></i>
                                        <?= ucfirst($log['severity']) ?>
                                    </span>
                                </td>
                                <td class="col-details" title="<?= clean($log['details']) ?>">
                                    <?= clean($log['details'] ?: '-') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state-ult">
                    <div class="empty-illustration"><i class="fas fa-shield-alt"></i></div>
                    <h3>Tidak ada log keamanan</h3>
                    <p>Belum ada kejadian keamanan yang tercatat dalam rentang waktu ini</p>
                    <div class="empty-actions">
                        <button class="btn btn-secondary" onclick="resetFilters()"><i class="fas fa-redo"></i> Reset Filter</button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="security-sidebar">
            <!-- Severity Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribusi Severity (24h)</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($severityStats)): 
                        $maxSev = max(array_column($severityStats, 'total'));
                    ?>
                        <?php foreach ($severityStats as $ss): 
                            $pct = round(($ss['total'] / max($maxSev, 1)) * 100);
                            $sevCfg = $severityColors[$ss['severity']] ?? $severityColors['info'];
                        ?>
                        <div class="severity-stat-item">
                            <div class="ssi-head">
                                <span class="ssi-name" style="color: <?= $sevCfg[0] ?>">
                                    <i class="fas <?= $sevCfg[2] ?>"></i> <?= ucfirst($ss['severity']) ?>
                                </span>
                                <span class="ssi-count"><?= $ss['total'] ?></span>
                            </div>
                            <div class="ssi-bar">
                                <div class="ssi-bar-fill" style="width: <?= $pct ?>%; background: <?= $sevCfg[0] ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty"><i class="fas fa-chart-pie"></i><p>Belum ada data severity</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top IP Addresses -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-network-wired"></i> Top IP (7 Hari)</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($topIPs)): ?>
                        <?php foreach ($topIPs as $index => $ip): 
                            $ipColors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'];
                            $color = $ipColors[$index % count($ipColors)];
                        ?>
                        <div class="ip-stat-item">
                            <div class="ipi-head">
                                <span class="ipi-rank" style="background: <?= $color ?>">#<?= $index + 1 ?></span>
                                <code class="ipi-address"><?= clean($ip['ip_address']) ?></code>
                            </div>
                            <div class="ipi-count"><?= $ip['total'] ?> events</div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty"><i class="fas fa-network-wired"></i><p>Belum ada data IP</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="widget-body">
                    <button class="qa-btn" onclick="filterBySeverity('critical')">
                        <i class="fas fa-exclamation-circle" style="color:#EF4444"></i>
                        <span>Lihat Critical</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </button>
                    <button class="qa-btn" onclick="filterBySeverity('warning')">
                        <i class="fas fa-exclamation-triangle" style="color:#F59E0B"></i>
                        <span>Lihat Warning</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </button>
                    <a href="?action=clear&days=30" class="qa-btn" onclick="return confirm('Hapus log lebih dari 30 hari?')" style="text-decoration:none;">
                        <i class="fas fa-broom" style="color:#6B7280"></i>
                        <span>Cleanup 30 Hari</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
/* ===== SECURITY PAGE ULTIMATE v4.0 ===== */
.security-page { padding: var(--spacing-lg); animation: fadeIn 0.4s ease; }

/* Hero */
.security-hero {
    position: relative; padding: 36px 40px; margin-bottom: 28px;
    background: linear-gradient(135deg, #1E293B 0%, #0F172A 50%, #020617 100%);
    border-radius: var(--radius-2xl); overflow: hidden; box-shadow: var(--shadow-xl);
    border: 1px solid rgba(239,68,68,0.2);
}
.hero-bg-effects { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.hero-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.5; animation: orbFloat 20s infinite ease-in-out; }
.orb-1 { width: 320px; height: 320px; background: radial-gradient(circle, rgba(239,68,68,0.3), transparent); top: -120px; right: 8%; }
.orb-2 { width: 220px; height: 220px; background: radial-gradient(circle, rgba(245,158,11,0.2), transparent); bottom: -100px; left: 15%; animation-delay: -7s; }
.hero-grid-pattern { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px); background-size: 40px 40px; }
.hero-academic-pattern { position: absolute; top: 50%; right: 5%; transform: translateY(-50%); font-size: 18rem; color: rgba(239,68,68,0.05); pointer-events: none; }
.hero-academic-pattern::before { content: '\f3ed'; font-family: 'Font Awesome 6 Free'; font-weight: 900; }
.hero-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; color: var(--white); }
.hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(239,68,68,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(239,68,68,0.3); border-radius: 50px; font-size: 0.78rem; font-weight: 600; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #FCA5A5; }
.hero-title { font-size: 2.2rem; font-weight: 700; margin-bottom: 10px; line-height: 1.2; font-family: 'Space Grotesk', sans-serif; display: flex; align-items: center; gap: 16px; }
.title-icon-wrap { width: 56px; height: 56px; background: rgba(239,68,68,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(239,68,68,0.3); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; color: #FCA5A5; }
.hero-subtitle { font-size: 0.95rem; opacity: 0.9; max-width: 600px; color: #CBD5E1; }
.hero-mini-stats { display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
.hms-item { display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; font-size: 0.85rem; color: #E2E8F0; }
.hms-item i { color: #94A3B8; }
.hms-item strong { font-family: 'Space Grotesk', sans-serif; font-weight: 700; color: var(--white); }
.hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-hero-primary { padding: 14px 26px; background: linear-gradient(135deg, #EF4444, #B91C1C); color: var(--white); border: none; border-radius: var(--radius); font-size: 0.92rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; box-shadow: 0 8px 20px rgba(239,68,68,0.4); text-decoration: none; }
.btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(239,68,68,0.5); color: var(--white); }
.btn-ghost { padding: 12px 18px; background: rgba(255,255,255,0.08); color: var(--white); border: 1px solid rgba(255,255,255,0.15); border-radius: var(--radius); font-size: 0.9rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; backdrop-filter: blur(10px); text-decoration: none; }
.btn-ghost:hover { background: rgba(255,255,255,0.15); transform: translateY(-2px); color: var(--white); }

/* Critical Alert */
.critical-alert {
    display: flex; align-items: center; gap: 16px; padding: 16px 20px;
    background: linear-gradient(135deg, rgba(239,68,68,0.1), rgba(185,28,28,0.05));
    border: 1px solid rgba(239,68,68,0.3); border-radius: var(--radius-xl);
    margin-bottom: 20px; animation: slideDown 0.3s ease;
}
.critical-alert-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: linear-gradient(135deg, #EF4444, #B91C1C);
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 1.3rem; flex-shrink: 0;
    animation: pulse 2s infinite;
}
@keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); } 50% { box-shadow: 0 0 0 10px rgba(239,68,68,0); } }
.critical-alert-content { flex: 1; }
.critical-alert-content strong { display: block; color: #EF4444; font-size: 0.95rem; margin-bottom: 2px; }
.critical-alert-content span { font-size: 0.85rem; color: var(--gray-600); }
[data-theme="dark"] .critical-alert-content span { color: var(--gray-400); }
.critical-alert-close { width: 36px; height: 36px; border-radius: 8px; background: rgba(239,68,68,0.1); border: none; cursor: pointer; color: #EF4444; display: flex; align-items: center; justify-content: center; transition: all var(--transition-fast); }
.critical-alert-close:hover { background: rgba(239,68,68,0.2); }

/* Layout */
.security-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.security-main-col { min-width: 0; }

/* Toolbar */
.security-toolbar { padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; overflow: visible !important; }
.toolbar-left { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; flex: 1; min-width: 0; overflow: visible !important; }
.toolbar-search { position: relative; flex: 1; min-width: 200px; max-width: 280px; }
.toolbar-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.9rem; pointer-events: none; }
.toolbar-search input { width: 100%; padding: 10px 44px 10px 42px; border: 1.5px solid var(--gray-200); border-radius: var(--radius); font-size: 0.88rem; font-family: inherit; background: var(--gray-50); transition: all var(--transition); }
[data-theme="dark"] .toolbar-search input { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.toolbar-search input:focus { outline: none; border-color: #EF4444; background: var(--white); box-shadow: 0 0 0 4px rgba(239,68,68,0.1); }
.search-kbd { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); padding: 2px 8px; background: var(--gray-200); color: var(--gray-600); border-radius: 4px; font-size: 0.7rem; font-weight: 600; font-family: 'Courier New', monospace; pointer-events: none; }

/* Custom Dropdown */
.custom-dropdown { position: relative; min-width: 160px; z-index: 1000; overflow: visible !important; }
.dropdown-trigger {
    display: flex; align-items: center; gap: 10px; padding: 10px 14px;
    background: var(--white); border: 1.5px solid var(--gray-200); border-radius: var(--radius);
    font-size: 0.85rem; font-weight: 600; color: var(--dark); cursor: pointer;
    transition: all var(--transition); width: 100%; text-align: left; font-family: inherit;
}
[data-theme="dark"] .dropdown-trigger { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.dropdown-trigger:hover { border-color: #EF4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.1); }
.dropdown-arrow { margin-left: auto; transition: transform 0.3s ease; color: var(--gray-500); font-size: 0.75rem; }
.custom-dropdown.open .dropdown-arrow { transform: rotate(180deg); }

.dropdown-menu {
    position: absolute !important;
    top: calc(100% + 8px) !important;
    left: 0 !important;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    max-height: 300px;
    overflow-y: auto !important;
    z-index: 999999 !important;
    padding: 8px;
    min-width: 100%;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    display: block !important;
}
[data-theme="dark"] .dropdown-menu { background: var(--dark-2); border-color: rgba(255,255,255,0.1); }
.dropdown-menu::-webkit-scrollbar { width: 6px; }
.dropdown-menu::-webkit-scrollbar-track { background: rgba(239,68,68,0.05); border-radius: 3px; }
.dropdown-menu::-webkit-scrollbar-thumb { background: #EF4444; border-radius: 3px; }
.custom-dropdown.open .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }

.dropdown-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: var(--radius); cursor: pointer; transition: all var(--transition-fast); font-size: 0.85rem; color: var(--dark); white-space: nowrap; }
[data-theme="dark"] .dropdown-item { color: var(--gray-200); }
.dropdown-item:hover { background: rgba(239,68,68,0.08); transform: translateX(4px); }
.dropdown-item.active { background: linear-gradient(135deg, rgba(239,68,68,0.15), rgba(185,28,28,0.1)); color: #EF4444; font-weight: 600; }
.dropdown-item i:first-child { width: 18px; font-size: 0.9rem; }
.dropdown-count { margin-left: auto; font-size: 0.72rem; color: var(--gray-500); background: var(--gray-100); padding: 2px 8px; border-radius: 10px; font-weight: 600; }
[data-theme="dark"] .dropdown-count { background: rgba(255,255,255,0.08); color: var(--gray-400); }
.dropdown-item.active .dropdown-count { background: rgba(239,68,68,0.2); color: #EF4444; }

.toolbar-right { display: flex; gap: 10px; align-items: center; }
.toolbar-icon-btn { width: 40px; height: 40px; border-radius: var(--radius); border: 1.5px solid var(--gray-200); background: var(--white); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--gray-600); transition: all var(--transition); font-size: 0.9rem; }
[data-theme="dark"] .toolbar-icon-btn { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--gray-300); }
.toolbar-icon-btn:hover { background: #EF4444; color: var(--white); border-color: #EF4444; transform: translateY(-2px); }

/* Table Card */
.security-table-card { overflow: hidden; }
.table-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); }
[data-theme="dark"] .table-header { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.08); }
.table-header h3 { font-size: 0.95rem; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 8px; margin: 0; }
[data-theme="dark"] .table-header h3 { color: var(--white); }
.table-header h3 i { color: #EF4444; }
.table-count { font-size: 0.82rem; color: var(--gray-600); background: var(--gray-100); padding: 4px 10px; border-radius: 20px; font-weight: 600; }
[data-theme="dark"] .table-count { background: rgba(255,255,255,0.08); color: var(--gray-400); }

.table-responsive { overflow-x: auto; }
.security-table { width: 100%; border-collapse: collapse; }
.security-table thead th { padding: 12px 16px; text-align: left; font-size: 0.78rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; letter-spacing: 0.5px; background: var(--gray-50); border-bottom: 1px solid var(--gray-100); white-space: nowrap; }
[data-theme="dark"] .security-table thead th { background: rgba(0,0,0,0.2); color: var(--gray-400); border-color: rgba(255,255,255,0.08); }
.security-table thead th i { margin-right: 6px; color: #EF4444; }
.security-table tbody tr { border-bottom: 1px solid var(--gray-100); transition: all var(--transition-fast); }
[data-theme="dark"] .security-table tbody tr { border-color: rgba(255,255,255,0.05); }
.security-table tbody tr:hover { background: rgba(239,68,68,0.02); }
.security-table tbody tr.row-critical { background: rgba(239,68,68,0.03); }
.security-table tbody tr.row-critical:hover { background: rgba(239,68,68,0.06); }
.security-table td { padding: 12px 16px; font-size: 0.85rem; color: var(--dark); vertical-align: middle; }
[data-theme="dark"] .security-table td { color: var(--gray-200); }

.col-time { white-space: nowrap; color: var(--gray-600); font-size: 0.82rem; }
[data-theme="dark"] .col-time { color: var(--gray-400); }
.col-time i { color: var(--gray-400); margin-right: 4px; }
.event-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: var(--gray-100); border-radius: 20px; font-size: 0.82rem; font-weight: 600; color: var(--dark); }
[data-theme="dark"] .event-badge { background: rgba(255,255,255,0.08); color: var(--gray-200); }
.event-badge i { color: #EF4444; }
.col-user { font-size: 0.85rem; }
.col-user i { color: var(--gray-400); margin-right: 4px; }
.text-muted { color: var(--gray-400); }
.ip-code { background: var(--gray-100); padding: 4px 8px; border-radius: 6px; font-size: 0.82rem; font-family: 'Courier New', monospace; color: var(--dark); }
[data-theme="dark"] .ip-code { background: rgba(255,255,255,0.08); color: var(--gray-200); }
.severity-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
.severity-info { background: rgba(59,130,246,0.12); color: #1E40AF; }
.severity-warning { background: rgba(245,158,11,0.15); color: #92400E; }
.severity-critical { background: rgba(239,68,68,0.15); color: #991B1B; }
.col-details { max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--gray-600); font-size: 0.82rem; }
[data-theme="dark"] .col-details { color: var(--gray-400); }

/* Sidebar */
.security-sidebar { display: flex; flex-direction: column; gap: 20px; position: sticky; top: 90px; }
.widget-card { overflow: hidden; background: var(--white); border-radius: var(--radius-xl); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); }
[data-theme="dark"] .widget-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.widget-header { padding: 16px 18px; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); }
[data-theme="dark"] .widget-header { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.08); }
.widget-header h3 { font-size: 0.95rem; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 8px; margin: 0; }
[data-theme="dark"] .widget-header h3 { color: var(--white); }
.widget-header h3 i { color: #EF4444; }
.widget-body { padding: 16px 18px; }
.widget-empty { text-align: center; padding: 24px 12px; color: var(--gray-600); }
.widget-empty i { font-size: 2rem; color: var(--gray-300); margin-bottom: 8px; display: block; }

.severity-stat-item { padding: 10px 0; border-bottom: 1px solid var(--gray-100); transition: all var(--transition-fast); }
[data-theme="dark"] .severity-stat-item { border-color: rgba(255,255,255,0.05); }
.severity-stat-item:last-child { border-bottom: none; }
.severity-stat-item:hover { background: rgba(239,68,68,0.04); padding-left: 8px; border-radius: var(--radius); }
.ssi-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.ssi-name { font-size: 0.82rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.ssi-name i { font-size: 0.78rem; }
.ssi-count { font-size: 0.85rem; font-weight: 700; color: var(--dark); font-family: 'Space Grotesk', sans-serif; }
[data-theme="dark"] .ssi-count { color: var(--white); }
.ssi-bar { height: 6px; background: var(--gray-100); border-radius: 50px; overflow: hidden; }
[data-theme="dark"] .ssi-bar { background: rgba(255,255,255,0.08); }
.ssi-bar-fill { height: 100%; border-radius: 50px; animation: barFill 1s ease-out; }

.ip-stat-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--gray-100); }
[data-theme="dark"] .ip-stat-item { border-color: rgba(255,255,255,0.05); }
.ip-stat-item:last-child { border-bottom: none; }
.ipi-head { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
.ipi-rank { width: 24px; height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.72rem; font-weight: 700; flex-shrink: 0; }
.ipi-address { font-size: 0.82rem; font-family: 'Courier New', monospace; color: var(--dark); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
[data-theme="dark"] .ipi-address { color: var(--gray-200); }
.ipi-count { font-size: 0.78rem; color: var(--gray-600); font-weight: 600; white-space: nowrap; margin-left: 8px; }
[data-theme="dark"] .ipi-count { color: var(--gray-400); }

.qa-btn { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--radius); background: var(--gray-50); color: var(--dark); border: none; width: 100%; text-align: left; font-size: 0.88rem; font-weight: 500; transition: all var(--transition); margin-bottom: 8px; cursor: pointer; font-family: inherit; text-decoration: none; }
[data-theme="dark"] .qa-btn { background: rgba(255,255,255,0.04); color: var(--gray-200); }
.qa-btn:hover { background: var(--gray-100); transform: translateX(4px); }
.qa-btn i:first-child { width: 16px; }
.qa-arrow { margin-left: auto; color: var(--gray-400); font-size: 0.72rem; opacity: 0; transition: opacity var(--transition-fast); }
.qa-btn:hover .qa-arrow { opacity: 1; }

/* Empty State */
.empty-state-ult { text-align: center; padding: 60px 20px; background: var(--white); border-radius: var(--radius-xl); border: 2px dashed var(--gray-200); }
[data-theme="dark"] .empty-state-ult { background: var(--dark-2); border-color: rgba(255,255,255,0.1); }
.empty-illustration i { font-size: 3.5rem; color: var(--gray-300); margin-bottom: 16px; display: block; }
[data-theme="dark"] .empty-illustration i { color: var(--gray-600); }
.empty-state-ult h3 { font-size: 1.3rem; color: var(--dark); margin-bottom: 8px; }
[data-theme="dark"] .empty-state-ult h3 { color: var(--white); }
.empty-state-ult p { color: var(--gray-600); margin-bottom: 24px; }
[data-theme="dark"] .empty-state-ult p { color: var(--gray-400); }
.empty-actions { display: flex; gap: 12px; justify-content: center; }

/* Animations */
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes barFill { from { width: 0; } }

/* Responsive */
@media (max-width: 1200px) {
    .security-layout { grid-template-columns: 1fr; }
    .security-sidebar { position: static; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
}
@media (max-width: 768px) {
    .security-page { padding: 16px; }
    .security-hero { padding: 24px 20px; }
    .hero-title { font-size: 1.6rem; }
    .title-icon-wrap { width: 44px; height: 44px; font-size: 1.2rem; }
    .hero-content { flex-direction: column; align-items: flex-start; }
    .hero-actions { width: 100%; }
    .security-toolbar { flex-direction: column; align-items: stretch; }
    .toolbar-left, .toolbar-right { width: 100%; flex-direction: column; align-items: stretch; }
    .toolbar-search { max-width: 100%; }
    .toolbar-right { flex-direction: row; justify-content: space-between; }
    .custom-dropdown { width: 100%; }
    .critical-alert { flex-direction: column; align-items: flex-start; }
}
</style>

<script>
(function() {
    'use strict';

    // Custom Dropdown Factory
    function createDropdown(dropdownId, triggerId, menuId, labelId, callback) {
        const dropdown = document.getElementById(dropdownId);
        const trigger = document.getElementById(triggerId);
        const menu = document.getElementById(menuId);
        const label = document.getElementById(labelId);
        const items = menu.querySelectorAll('.dropdown-item');

        if (!dropdown || !trigger || !menu) return;

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            // Close other dropdowns
            document.querySelectorAll('.custom-dropdown.open').forEach(d => {
                if (d !== dropdown) d.classList.remove('open');
            });
            dropdown.classList.toggle('open');
        });

        items.forEach(item => {
            item.addEventListener('click', function() {
                const value = this.dataset.value;
                const text = this.querySelector('span').textContent;
                
                if (label) label.textContent = text;
                
                // Update icon color based on active item
                const icon = this.querySelector('i');
                if (icon && trigger) {
                    trigger.querySelector('i:first-child').style.color = icon.style.color || '#6B7280';
                }
                
                items.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                dropdown.classList.remove('open');
                
                if (callback) callback(value);
            });
        });

        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    }

    // Create all dropdowns
    createDropdown('eventDropdown', 'eventTrigger', 'eventMenu', 'eventLabel', function(value) {
        applyFilters({ event: value });
    });

    createDropdown('severityDropdown', 'severityTrigger', 'severityMenu', 'severityLabel', function(value) {
        applyFilters({ severity: value });
    });

    createDropdown('limitDropdown', 'limitTrigger', 'limitMenu', 'limitLabel', function(value) {
        applyFilters({ limit: value });
    });

    // Search & Filter Logic
    const searchIP = document.getElementById('searchIP');
    let searchTimeout;

    if (searchIP) {
        searchIP.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => applyFilters({ ip: this.value.trim() }), 600);
        });
        searchIP.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                applyFilters({ ip: this.value.trim() });
            }
        });
    }

    window.filterBySeverity = function(severity) {
        applyFilters({ severity: severity });
    };

    window.applyFilters = function(updates) {
        const url = new URL(window.location);
        
        if (updates) {
            Object.keys(updates).forEach(key => {
                if (updates[key]) {
                    url.searchParams.set(key, updates[key]);
                } else {
                    url.searchParams.delete(key);
                }
            });
        }
        
        window.location = url;
    };

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['event', 'severity', 'ip', 'limit'].forEach(p => url.searchParams.delete(p));
        window.location = url;
    };

    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'all 0.4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 400);
        });
    }, 5000);

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            searchIP?.focus();
        }
    });

    console.log('%c🔒 Security Logs Page Ultimate Loaded', 'color:#EF4444;font-weight:bold;font-size:12px');
})();
</script>

<?php include 'includes/footer.php'; ?>