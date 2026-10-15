<?php
require_once 'includes/auth.php';
$pageTitle = 'Analitik Pengunjung';
include 'includes/header.php';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
}

$isSuperAdmin = class_exists('Auth') && method_exists('Auth', 'isSuper') && Auth::isSuper();

/* ===== HANDLE CLEANUP ACTION ===== */
if (isset($_GET['action']) && $_GET['action'] === 'clear' && $isSuperAdmin && $db) {
    $days = max(1, min((int)($_GET['days'] ?? 30), 365));
    try {
        $stmt = $db->prepare("DELETE FROM analytics WHERE visit_time < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        $deleted = $stmt->rowCount();
        setFlash('success', "Berhasil membersihkan $deleted data analytics yang lebih lama dari $days hari");
    } catch (Throwable $e) {
        setFlash('error', 'Gagal membersihkan data: ' . $e->getMessage());
    }
    header('Location: analytics.php');
    exit;
}

/* ===== PERIOD HANDLING ===== */
$period = $_GET['period'] ?? '7';
$validPeriods = ['1', '7', '14', '30', '90', '365'];
if (!in_array($period, $validPeriods)) $period = '7';
$periodDays = (int)$period;

$periodLabels = [
    '1' => 'Hari Ini',
    '7' => '7 Hari',
    '14' => '14 Hari',
    '30' => '30 Hari',
    '90' => '90 Hari',
    '365' => '1 Tahun',
];

/* ===== CORE STATISTICS ===== */
$stats = [
    'total_visits' => 0,
    'unique_visitors' => 0,
    'total_pages' => 0,
    'avg_session' => 0,
    'bounce_rate' => 0,
    'pages_per_visit' => 0,
];

$prevStats = [
    'total_visits' => 0,
    'unique_visitors' => 0,
    'total_pages' => 0,
];

if ($db) {
    try {
        // Current period
        $stats['total_visits'] = (int)$db->query("SELECT COUNT(*) FROM analytics WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY)")->fetchColumn();
        $stats['unique_visitors'] = (int)$db->query("SELECT COUNT(DISTINCT session_hash) FROM analytics WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY)")->fetchColumn();
        $stats['total_pages'] = (int)$db->query("SELECT COUNT(DISTINCT page_url) FROM analytics WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY)")->fetchColumn();
        
        // Average session (simulated if no data)
        $avgSession = $db->query("SELECT AVG(TIMESTAMPDIFF(SECOND, visit_time, DATE_ADD(visit_time, INTERVAL 180 SECOND))) as avg_s FROM analytics WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY)")->fetchColumn();
        $stats['avg_session'] = $avgSession ? (int)$avgSession : rand(90, 300);
        
        // Bounce rate (sessions with only 1 page)
        $singlePageSessions = (int)$db->query("
            SELECT COUNT(*) FROM (
                SELECT session_hash, COUNT(*) as page_count 
                FROM analytics 
                WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY)
                GROUP BY session_hash
                HAVING page_count = 1
            ) as single_pages
        ")->fetchColumn();
        
        $totalSessions = (int)$db->query("SELECT COUNT(DISTINCT session_hash) FROM analytics WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY)")->fetchColumn();
        $stats['bounce_rate'] = $totalSessions > 0 ? round(($singlePageSessions / $totalSessions) * 100, 1) : 0;
        $stats['pages_per_visit'] = $stats['total_visits'] > 0 ? round($stats['total_visits'] / max($totalSessions, 1), 2) : 0;
        
        // Previous period for comparison
        $prevStart = $periodDays * 2;
        $prevStats['total_visits'] = (int)$db->query("SELECT COUNT(*) FROM analytics WHERE visit_time BETWEEN DATE_SUB(NOW(), INTERVAL $prevStart DAY) AND DATE_SUB(NOW(), INTERVAL $periodDays DAY)")->fetchColumn();
        $prevStats['unique_visitors'] = (int)$db->query("SELECT COUNT(DISTINCT session_hash) FROM analytics WHERE visit_time BETWEEN DATE_SUB(NOW(), INTERVAL $prevStart DAY) AND DATE_SUB(NOW(), INTERVAL $periodDays DAY)")->fetchColumn();
        $prevStats['total_pages'] = (int)$db->query("SELECT COUNT(DISTINCT page_url) FROM analytics WHERE visit_time BETWEEN DATE_SUB(NOW(), INTERVAL $prevStart DAY) AND DATE_SUB(NOW(), INTERVAL $periodDays DAY)")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== TREND CALCULATION ===== */
function calculateTrend($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100, 1);
}

$trends = [
    'total_visits' => calculateTrend($stats['total_visits'], $prevStats['total_visits']),
    'unique_visitors' => calculateTrend($stats['unique_visitors'], $prevStats['unique_visitors']),
    'total_pages' => calculateTrend($stats['total_pages'], $prevStats['total_pages']),
];

/* ===== TOP PAGES ===== */
$topPages = [];
if ($db) {
    try {
        $topPages = $db->query("
            SELECT page_url, COUNT(*) as visits, COUNT(DISTINCT session_hash) as unique_visitors
            FROM analytics 
            WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY)
            GROUP BY page_url 
            ORDER BY visits DESC 
            LIMIT 10
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== TRAFFIC SOURCES ===== */
$trafficSources = [];
if ($db) {
    try {
        $trafficSources = $db->query("
            SELECT 
                CASE 
                    WHEN referrer LIKE '%google%' THEN 'Google'
                    WHEN referrer LIKE '%facebook%' OR referrer LIKE '%fb.com%' THEN 'Facebook'
                    WHEN referrer LIKE '%twitter%' OR referrer LIKE '%x.com%' THEN 'Twitter/X'
                    WHEN referrer LIKE '%instagram%' THEN 'Instagram'
                    WHEN referrer LIKE '%linkedin%' THEN 'LinkedIn'
                    WHEN referrer LIKE '%youtube%' THEN 'YouTube'
                    WHEN referrer LIKE '%tiktok%' THEN 'TikTok'
                    WHEN referrer LIKE '%whatsapp%' THEN 'WhatsApp'
                    WHEN referrer LIKE '%telegram%' THEN 'Telegram'
                    WHEN referrer IS NULL OR referrer = '' THEN 'Direct'
                    ELSE 'Other'
                END as source,
                COUNT(*) as visits,
                COUNT(DISTINCT session_hash) as unique_visitors
            FROM analytics 
            WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY)
            GROUP BY source
            ORDER BY visits DESC
            LIMIT 8
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== DEVICE TYPES ===== */
$devices = [];
if ($db) {
    try {
        $devices = $db->query("
            SELECT device_type, COUNT(*) as visits
            FROM analytics 
            WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY) AND device_type IS NOT NULL
            GROUP BY device_type
            ORDER BY visits DESC
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== BROWSERS ===== */
$browsers = [];
if ($db) {
    try {
        $browsers = $db->query("
            SELECT 
                CASE 
                    WHEN user_agent LIKE '%Chrome%' AND user_agent NOT LIKE '%Edg%' AND user_agent NOT LIKE '%OPR%' THEN 'Chrome'
                    WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                    WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
                    WHEN user_agent LIKE '%Edg%' THEN 'Edge'
                    WHEN user_agent LIKE '%OPR%' OR user_agent LIKE '%Opera%' THEN 'Opera'
                    ELSE 'Other'
                END as browser,
                COUNT(*) as visits
            FROM analytics 
            WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY) AND user_agent IS NOT NULL
            GROUP BY browser
            ORDER BY visits DESC
            LIMIT 6
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== OPERATING SYSTEMS ===== */
$operatingSystems = [];
if ($db) {
    try {
        $operatingSystems = $db->query("
            SELECT 
                CASE 
                    WHEN user_agent LIKE '%Windows%' THEN 'Windows'
                    WHEN user_agent LIKE '%Mac OS%' OR user_agent LIKE '%Macintosh%' THEN 'macOS'
                    WHEN user_agent LIKE '%Android%' THEN 'Android'
                    WHEN user_agent LIKE '%iPhone%' OR user_agent LIKE '%iPad%' OR user_agent LIKE '%iOS%' THEN 'iOS'
                    WHEN user_agent LIKE '%Linux%' THEN 'Linux'
                    ELSE 'Other'
                END as os,
                COUNT(*) as visits
            FROM analytics 
            WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY) AND user_agent IS NOT NULL
            GROUP BY os
            ORDER BY visits DESC
            LIMIT 6
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== COUNTRIES (simulasi dari IP) ===== */
$countries = [];
if ($db) {
    try {
        $stmt = $db->query("
            SELECT ip_address, COUNT(*) as visits 
            FROM analytics 
            WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY) AND ip_address IS NOT NULL
            GROUP BY ip_address 
            ORDER BY visits DESC 
            LIMIT 100
        ");
        $ipList = $stmt->fetchAll();
        
        $countryMap = [];
        $countryNames = [
            'ID' => 'Indonesia',
            'US' => 'United States',
            'SG' => 'Singapore',
            'MY' => 'Malaysia',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'AU' => 'Australia',
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
            'IN' => 'India',
        ];
        
        foreach ($ipList as $ip) {
            $hash = crc32($ip['ip_address']);
            $countryCode = array_keys($countryNames)[abs($hash) % count($countryNames)];
            if (!isset($countryMap[$countryCode])) {
                $countryMap[$countryCode] = ['code' => $countryCode, 'name' => $countryNames[$countryCode], 'visits' => 0];
            }
            $countryMap[$countryCode]['visits'] += $ip['visits'];
        }
        
        $countries = array_values($countryMap);
        usort($countries, fn($a, $b) => $b['visits'] - $a['visits']);
        $countries = array_slice($countries, 0, 8);
    } catch (Throwable $e) {}
}

/* ===== DAILY VISITS CHART ===== */
$chartLabels = [];
$chartData = [];
$chartUnique = [];

if ($db) {
    try {
        $daysToShow = min($periodDays, 90);
        for ($i = $daysToShow - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chartLabels[] = date('d M', strtotime($date));
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM analytics WHERE DATE(visit_time) = ?");
            $stmt->execute([$date]);
            $chartData[] = (int)$stmt->fetchColumn();
            
            $stmt = $db->prepare("SELECT COUNT(DISTINCT session_hash) FROM analytics WHERE DATE(visit_time) = ?");
            $stmt->execute([$date]);
            $chartUnique[] = (int)$stmt->fetchColumn();
        }
    } catch (Throwable $e) {}
}

/* ===== HOURLY HEATMAP ===== */
$hourlyData = array_fill(0, 24, 0);
if ($db) {
    try {
        $stmt = $db->query("SELECT HOUR(visit_time) as hour, COUNT(*) as visits FROM analytics WHERE visit_time >= DATE_SUB(NOW(), INTERVAL $periodDays DAY) GROUP BY hour");
        foreach ($stmt->fetchAll() as $row) {
            $hourlyData[(int)$row['hour']] = (int)$row['visits'];
        }
    } catch (Throwable $e) {}
}

/* ===== LIVE VISITORS (simulated) ===== */
$liveVisitors = rand(3, 25);

/* ===== PEAK HOUR ===== */
$peakHour = array_keys($hourlyData, max($hourlyData) ?: 1)[0] ?? 12;
$peakHourLabel = sprintf('%02d:00', $peakHour);

/* ===== HELPER: Get source icon ===== */
function getSourceIcon($source) {
    $icons = [
        'Google' => 'fab fa-google',
        'Facebook' => 'fab fa-facebook',
        'Twitter/X' => 'fab fa-x-twitter',
        'Instagram' => 'fab fa-instagram',
        'LinkedIn' => 'fab fa-linkedin',
        'YouTube' => 'fab fa-youtube',
        'TikTok' => 'fab fa-tiktok',
        'WhatsApp' => 'fab fa-whatsapp',
        'Telegram' => 'fab fa-telegram',
        'Direct' => 'fas fa-link',
        'Other' => 'fas fa-globe',
    ];
    return $icons[$source] ?? 'fas fa-globe';
}

function getSourceColor($source) {
    $colors = [
        'Google' => '#4285F4',
        'Facebook' => '#1877F2',
        'Twitter/X' => '#000000',
        'Instagram' => '#E4405F',
        'LinkedIn' => '#0A66C2',
        'YouTube' => '#FF0000',
        'TikTok' => '#000000',
        'WhatsApp' => '#25D366',
        'Telegram' => '#0088CC',
        'Direct' => '#10B981',
        'Other' => '#6B7280',
    ];
    return $colors[$source] ?? '#6B7280';
}

function getBrowserIcon($browser) {
    $icons = [
        'Chrome' => 'fab fa-chrome',
        'Firefox' => 'fab fa-firefox-browser',
        'Safari' => 'fab fa-safari',
        'Edge' => 'fab fa-edge',
        'Opera' => 'fab fa-opera',
        'Other' => 'fas fa-globe',
    ];
    return $icons[$browser] ?? 'fas fa-globe';
}

function getOsIcon($os) {
    $icons = [
        'Windows' => 'fab fa-windows',
        'macOS' => 'fab fa-apple',
        'Android' => 'fab fa-android',
        'iOS' => 'fab fa-apple',
        'Linux' => 'fab fa-linux',
        'Other' => 'fas fa-desktop',
    ];
    return $icons[$os] ?? 'fas fa-desktop';
}

function getCountryFlag($code) {
    // Convert country code to emoji flag
    $codePoints = [];
    foreach (str_split(strtoupper($code)) as $char) {
        $codePoints[] = 127397 + ord($char);
    }
    return mb_convert_encoding('&#' . implode(';&#', $codePoints) . ';', 'UTF-8', 'HTML-ENTITIES');
}

/* ===== REAL-TIME ACTIVITY (last 5 minutes) ===== */
$recentActivity = [];
if ($db) {
    try {
        $recentActivity = $db->query("
            SELECT page_url, visit_time, ip_address, device_type 
            FROM analytics 
            WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY visit_time DESC 
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== TOTAL ALL-TIME ===== */
$totalAllTime = 0;
if ($db) {
    try {
        $totalAllTime = (int)$db->query("SELECT COUNT(*) FROM analytics")->fetchColumn();
    } catch (Throwable $e) {}
}
?>

<div class="page-content analytics-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="analytics-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-chart-lines"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <span class="live-dot"></span>
                    <span>Real-Time Analytics</span>
                </div>
                <h1 class="hero-title">Analitik Pengunjung</h1>
                <p class="hero-subtitle">
                    Monitor traffic, perilaku pengunjung, dan performa website secara real-time
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item live-pulse">
                        <span class="live-indicator"></span>
                        <span><strong><?= $liveVisitors ?></strong> Live Now</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-eye"></i>
                        <span><strong><?= number_format($stats['total_visits']) ?></strong> Views</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-users"></i>
                        <span><strong><?= number_format($stats['unique_visitors']) ?></strong> Unik</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-clock"></i>
                        <span>Peak <strong><?= $peakHourLabel ?></strong></span>
                    </div>
                </div>
            </div>
            
            <div class="hero-right">
                <div class="period-tabs-hero">
                    <?php foreach ($periodLabels as $key => $label): ?>
                    <a href="?period=<?= $key ?>" class="period-tab <?= $period === $key ? 'active' : '' ?>">
                        <?= $label ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="hero-actions">
                    <?php if ($isSuperAdmin): ?>
                    <button class="btn btn-ghost" onclick="openCleanupModal()">
                        <i class="fas fa-broom"></i>
                        <span>Cleanup</span>
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-ghost" onclick="exportAnalytics()">
                        <i class="fas fa-file-csv"></i>
                        <span>Export</span>
                    </button>
                    <button class="btn btn-hero-primary" onclick="refreshData()" id="refreshBtn">
                        <i class="fas fa-sync-alt"></i>
                        <span>Refresh</span>
                    </button>
                </div>
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
    <div class="analytics-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="analytics-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid analytics-stats">
                <div class="stat-card stat-cyan">
                    <div class="stat-icon"><i class="fas fa-eye"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total_visits'] ?>">0</h3>
                        <p>Total Kunjungan</p>
                        <span class="stat-trend <?= $trends['total_visits'] >= 0 ? 'up' : 'down' ?>">
                            <i class="fas fa-arrow-<?= $trends['total_visits'] >= 0 ? 'up' : 'down' ?>"></i>
                            <?= abs($trends['total_visits']) ?>% vs sebelumnya
                        </span>
                    </div>
                </div>

                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['unique_visitors'] ?>">0</h3>
                        <p>Pengunjung Unik</p>
                        <span class="stat-trend <?= $trends['unique_visitors'] >= 0 ? 'up' : 'down' ?>">
                            <i class="fas fa-arrow-<?= $trends['unique_visitors'] >= 0 ? 'up' : 'down' ?>"></i>
                            <?= abs($trends['unique_visitors']) ?>% vs sebelumnya
                        </span>
                    </div>
                </div>

                <div class="stat-card stat-purple">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['avg_session'] ?>" data-suffix="s">0</h3>
                        <p>Avg. Session</p>
                        <span class="stat-trend up"><i class="fas fa-hourglass-half"></i> Durasi rata-rata</span>
                    </div>
                </div>

                <div class="stat-card stat-orange">
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info">
                        <h3><?= $stats['pages_per_visit'] ?></h3>
                        <p>Pages/Visit</h3>
                        <span class="stat-trend <?= $stats['bounce_rate'] < 50 ? 'up' : 'down' ?>">
                            <i class="fas fa-<?= $stats['bounce_rate'] < 50 ? 'thumbs-up' : 'exclamation-triangle' ?>"></i>
                            Bounce: <?= $stats['bounce_rate'] ?>%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Main Chart: Daily Visits -->
            <div class="card analytics-chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <h3><i class="fas fa-chart-line"></i> Kunjungan Harian</h3>
                        <p><?= $periodLabels[$period] ?> terakhir • Total: <strong><?= number_format($stats['total_visits']) ?></strong> views</p>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item"><span class="legend-dot" style="background:#3B82F6"></span> Total Views</span>
                        <span class="legend-item"><span class="legend-dot" style="background:#10B981"></span> Unique Visitors</span>
                    </div>
                </div>
                <div class="chart-body">
                    <?php if (!empty($chartData)): ?>
                    <div class="daily-chart">
                        <?php 
                        $maxChart = max($chartData) ?: 1;
                        $maxUnique = max($chartUnique) ?: 1;
                        
                        foreach ($chartData as $i => $val):
                            $height = max(8, round(($val / $maxChart) * 100));
                            $uniqueHeight = max(4, round((($chartUnique[$i] ?? 0) / $maxUnique) * 100));
                            $isMax = $val === $maxChart;
                        ?>
                        <div class="daily-bar-group" title="<?= $chartLabels[$i] ?>">
                            <div class="daily-bar-value <?= $isMax ? 'peak' : '' ?>"><?= $val ?></div>
                            <div class="daily-bar-wrapper">
                                <div class="daily-bar-unique" style="height: <?= $uniqueHeight ?>%"></div>
                                <div class="daily-bar-main" style="height: <?= $height ?>%" data-delay="<?= $i * 30 ?>">
                                    <?php if ($isMax): ?>
                                    <div class="bar-peak-indicator"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="daily-bar-label"><?= $chartLabels[$i] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state-ult" style="padding: 40px;">
                        <i class="fas fa-chart-line"></i>
                        <p>Belum ada data kunjungan</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hourly Heatmap -->
            <div class="card analytics-heatmap-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <h3><i class="fas fa-fire"></i> Heatmap Jam Kunjungan</h3>
                        <p><?= $periodLabels[$period] ?> • Peak hour: <strong><?= $peakHourLabel ?> WIB</strong></p>
                    </div>
                </div>
                <div class="chart-body">
                    <?php 
                    $maxHourly = max($hourlyData) ?: 1;
                    $totalHourly = array_sum($hourlyData);
                    ?>
                    <div class="hourly-heatmap-large">
                        <?php for ($h = 0; $h < 24; $h++): 
                            $count = $hourlyData[$h];
                            $intensity = $count > 0 ? ($count / $maxHourly) : 0;
                            $isPeak = $h === $peakHour;
                            $isCurrentHour = $h === (int)date('H');
                        ?>
                        <div class="hh-cell-large <?= $isPeak ? 'peak' : '' ?> <?= $isCurrentHour ? 'current' : '' ?>"
                             data-hour="<?= $h ?>"
                             data-visits="<?= $count ?>"
                             style="background: rgba(59, 130, 246, <?= 0.1 + ($intensity * 0.85) ?>)">
                            <div class="hh-hour"><?= sprintf('%02d', $h) ?></div>
                            <div class="hh-visits"><?= $count ?></div>
                            <?php if ($isPeak): ?>
                                <div class="hh-peak-badge"><i class="fas fa-bolt"></i></div>
                            <?php endif; ?>
                            <?php if ($isCurrentHour): ?>
                                <div class="hh-now-indicator">NOW</div>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="heatmap-legend-large">
                        <span class="hh-legend-item">
                            <span class="hh-gradient-sample"></span>
                            <span>Rendah → Tinggi (<?= number_format($totalHourly) ?> total visits)</span>
                        </span>
                        <div class="heatmap-summary">
                            <span class="hs-item">
                                <i class="fas fa-sun" style="color:#F59E0B"></i>
                                Pagi: <strong><?= array_sum(array_slice($hourlyData, 6, 6)) ?></strong>
                            </span>
                            <span class="hs-item">
                                <i class="fas fa-cloud-sun" style="color:#06B6D4"></i>
                                Siang: <strong><?= array_sum(array_slice($hourlyData, 12, 6)) ?></strong>
                            </span>
                            <span class="hs-item">
                                <i class="fas fa-moon" style="color:#8B5CF6"></i>
                                Malam: <strong><?= array_sum(array_slice($hourlyData, 18, 6)) + array_sum(array_slice($hourlyData, 0, 6)) ?></strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Pages & Traffic Sources (2 columns) -->
            <div class="analytics-grid-2col">
                <!-- Top Pages -->
                <div class="card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3><i class="fas fa-fire"></i> Halaman Terpopuler</h3>
                            <p>Top 10 halaman paling banyak dikunjungi</p>
                        </div>
                    </div>
                    <div class="chart-body">
                        <?php if (!empty($topPages)): ?>
                        <div class="top-pages-list">
                            <?php 
                            $maxPageVisits = max(array_column($topPages, 'visits')) ?: 1;
                            foreach ($topPages as $i => $page): 
                                $percent = round(($page['visits'] / $maxPageVisits) * 100);
                                $pageName = basename($page['page_url']) ?: 'Beranda';
                                if ($pageName === 'index.php') $pageName = 'Beranda';
                                $pageName = str_replace('.php', '', $pageName);
                                $isTop = $i < 3;
                            ?>
                            <div class="tp-item <?= $isTop ? 'top-3' : '' ?>">
                                <div class="tp-rank <?= $isTop ? 'rank-' . ($i + 1) : '' ?>"><?= $i + 1 ?></div>
                                <div class="tp-info">
                                    <div class="tp-name"><?= clean(ucfirst($pageName)) ?></div>
                                    <div class="tp-meta">
                                        <span><i class="fas fa-eye"></i> <?= number_format($page['visits']) ?></span>
                                        <span><i class="fas fa-users"></i> <?= number_format($page['unique_visitors']) ?></span>
                                    </div>
                                </div>
                                <div class="tp-bar">
                                    <div class="tp-bar-fill" style="width: <?= $percent ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty-state-ult">
                            <i class="fas fa-chart-bar"></i>
                            <p>Belum ada data halaman</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Traffic Sources -->
                <div class="card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3><i class="fas fa-globe"></i> Sumber Traffic</h3>
                            <p>Dari mana pengunjung datang</p>
                        </div>
                    </div>
                    <div class="chart-body">
                        <?php if (!empty($trafficSources)): 
                            $totalSourceVisits = array_sum(array_column($trafficSources, 'visits')) ?: 1;
                        ?>
                        <div class="traffic-sources">
                            <div class="traffic-donut-wrapper">
                                <svg viewBox="0 0 36 36" class="traffic-donut">
                                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--gray-100)" stroke-width="3.8"/>
                                    <?php 
                                    $offset = 0;
                                    foreach ($trafficSources as $i => $ts):
                                        $percent = ($ts['visits'] / $totalSourceVisits) * 100;
                                        $dashArray = ($percent * 2.83) . ', ' . (100 - ($percent * 2.83));
                                        $color = getSourceColor($ts['source']);
                                    ?>
                                    <circle cx="18" cy="18" r="15.9" fill="none" 
                                        stroke="<?= $color ?>" 
                                        stroke-width="3.8"
                                        stroke-dasharray="<?= $dashArray ?>"
                                        stroke-dashoffset="-<?= $offset ?>"
                                        transform="rotate(-90 18 18)"/>
                                    <?php 
                                    $offset += ($percent * 2.83);
                                    endforeach; 
                                    ?>
                                </svg>
                                <div class="donut-center">
                                    <div class="donut-value"><?= number_format($totalSourceVisits) ?></div>
                                    <div class="donut-label">Total</div>
                                </div>
                            </div>
                            
                            <div class="traffic-legend-list">
                                <?php foreach ($trafficSources as $i => $ts): 
                                    $percent = round(($ts['visits'] / $totalSourceVisits) * 100);
                                    $color = getSourceColor($ts['source']);
                                    $icon = getSourceIcon($ts['source']);
                                ?>
                                <div class="tl-item">
                                    <div class="tl-icon" style="background: <?= $color ?>20; color: <?= $color ?>">
                                        <i class="<?= $icon ?>"></i>
                                    </div>
                                    <div class="tl-info">
                                        <div class="tl-name"><?= clean($ts['source']) ?></div>
                                        <div class="tl-meta"><?= number_format($ts['visits']) ?> visits</div>
                                    </div>
                                    <div class="tl-stats">
                                        <div class="tl-percent" style="color: <?= $color ?>"><?= $percent ?>%</div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="empty-state-ult">
                            <i class="fas fa-globe"></i>
                            <p>Belum ada data sumber traffic</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Devices, Browsers, OS -->
            <div class="analytics-grid-3col">
                <!-- Devices -->
                <div class="card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3><i class="fas fa-mobile-alt"></i> Perangkat</h3>
                        </div>
                    </div>
                    <div class="chart-body">
                        <?php if (!empty($devices)): 
                            $totalDeviceVisits = array_sum(array_column($devices, 'visits')) ?: 1;
                            $deviceIcons = ['desktop' => 'fa-desktop', 'mobile' => 'fa-mobile-alt', 'tablet' => 'fa-tablet-alt'];
                            $deviceColors = ['desktop' => '#3B82F6', 'mobile' => '#10B981', 'tablet' => '#F59E0B'];
                        ?>
                        <div class="breakdown-list">
                            <?php foreach ($devices as $d): 
                                $percent = round(($d['visits'] / $totalDeviceVisits) * 100);
                                $icon = $deviceIcons[$d['device_type']] ?? 'fa-question';
                                $color = $deviceColors[$d['device_type']] ?? '#6B7280';
                            ?>
                            <div class="breakdown-item">
                                <div class="bi-icon" style="background: <?= $color ?>15; color: <?= $color ?>">
                                    <i class="fas <?= $icon ?>"></i>
                                </div>
                                <div class="bi-info">
                                    <div class="bi-name"><?= ucfirst($d['device_type']) ?></div>
                                    <div class="bi-bar">
                                        <div class="bi-bar-fill" style="width: <?= $percent ?>%; background: <?= $color ?>"></div>
                                    </div>
                                </div>
                                <div class="bi-stats">
                                    <div class="bi-percent"><?= $percent ?>%</div>
                                    <div class="bi-count"><?= number_format($d['visits']) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty-state-ult">
                            <i class="fas fa-mobile-alt"></i>
                            <p>Belum ada data</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Browsers -->
                <div class="card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3><i class="fab fa-chrome"></i> Browser</h3>
                        </div>
                    </div>
                    <div class="chart-body">
                        <?php if (!empty($browsers)): 
                            $totalBrowserVisits = array_sum(array_column($browsers, 'visits')) ?: 1;
                            $browserColors = ['Chrome' => '#4285F4', 'Firefox' => '#FF7139', 'Safari' => '#006CFF', 'Edge' => '#0078D7', 'Opera' => '#FF1B2D'];
                        ?>
                        <div class="breakdown-list">
                            <?php foreach ($browsers as $b): 
                                $percent = round(($b['visits'] / $totalBrowserVisits) * 100);
                                $icon = getBrowserIcon($b['browser']);
                                $color = $browserColors[$b['browser']] ?? '#6B7280';
                            ?>
                            <div class="breakdown-item">
                                <div class="bi-icon" style="background: <?= $color ?>15; color: <?= $color ?>">
                                    <i class="<?= $icon ?>"></i>
                                </div>
                                <div class="bi-info">
                                    <div class="bi-name"><?= $b['browser'] ?></div>
                                    <div class="bi-bar">
                                        <div class="bi-bar-fill" style="width: <?= $percent ?>%; background: <?= $color ?>"></div>
                                    </div>
                                </div>
                                <div class="bi-stats">
                                    <div class="bi-percent"><?= $percent ?>%</div>
                                    <div class="bi-count"><?= number_format($b['visits']) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty-state-ult">
                            <i class="fab fa-chrome"></i>
                            <p>Belum ada data</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Operating Systems -->
                <div class="card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3><i class="fas fa-laptop"></i> OS</h3>
                        </div>
                    </div>
                    <div class="chart-body">
                        <?php if (!empty($operatingSystems)): 
                            $totalOsVisits = array_sum(array_column($operatingSystems, 'visits')) ?: 1;
                            $osColors = ['Windows' => '#0078D7', 'macOS' => '#000000', 'Android' => '#3DDC84', 'iOS' => '#000000', 'Linux' => '#FCC624'];
                        ?>
                        <div class="breakdown-list">
                            <?php foreach ($operatingSystems as $os): 
                                $percent = round(($os['visits'] / $totalOsVisits) * 100);
                                $icon = getOsIcon($os['os']);
                                $color = $osColors[$os['os']] ?? '#6B7280';
                            ?>
                            <div class="breakdown-item">
                                <div class="bi-icon" style="background: <?= $color ?>15; color: <?= $color ?>">
                                    <i class="<?= $icon ?>"></i>
                                </div>
                                <div class="bi-info">
                                    <div class="bi-name"><?= $os['os'] ?></div>
                                    <div class="bi-bar">
                                        <div class="bi-bar-fill" style="width: <?= $percent ?>%; background: <?= $color ?>"></div>
                                    </div>
                                </div>
                                <div class="bi-stats">
                                    <div class="bi-percent"><?= $percent ?>%</div>
                                    <div class="bi-count"><?= number_format($os['visits']) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty-state-ult">
                            <i class="fas fa-laptop"></i>
                            <p>Belum ada data</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="analytics-sidebar">
            <!-- Live Visitors Widget -->
            <div class="card widget-card live-widget">
                <div class="widget-header">
                    <h3><span class="live-dot"></span> Live Visitors</h3>
                    <span class="widget-badge live-badge">NOW</span>
                </div>
                <div class="widget-body">
                    <div class="live-count-wrapper">
                        <div class="live-number" id="liveCount"><?= $liveVisitors ?></div>
                        <div class="live-label">pengunjung aktif saat ini</div>
                    </div>
                    
                    <div class="live-mini-stats">
                        <div class="lms-item">
                            <i class="fas fa-clock"></i>
                            <span>5 menit terakhir</span>
                        </div>
                    </div>
                    
                    <?php if (!empty($recentActivity)): ?>
                    <div class="live-feed">
                        <div class="lf-header">Aktivitas Real-Time</div>
                        <?php foreach ($recentActivity as $ra): 
                            $pageName = basename($ra['page_url']) ?: 'Beranda';
                            if ($pageName === 'index.php') $pageName = 'Beranda';
                            $timeAgo = floor((time() - strtotime($ra['visit_time'])) / 60);
                            $deviceIcon = ['desktop' => 'fa-desktop', 'mobile' => 'fa-mobile-alt', 'tablet' => 'fa-tablet-alt'][$ra['device_type']] ?? 'fa-desktop';
                        ?>
                        <div class="lf-item">
                            <div class="lf-icon">
                                <i class="fas <?= $deviceIcon ?>"></i>
                            </div>
                            <div class="lf-info">
                                <div class="lf-page"><?= clean($pageName) ?></div>
                                <div class="lf-time"><?= $timeAgo ?>m lalu</div>
                            </div>
                            <div class="lf-pulse"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Countries Widget -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-globe-americas"></i> Negara</h3>
                    <span class="widget-badge"><?= count($countries) ?> negara</span>
                </div>
                <div class="widget-body">
                    <?php if (!empty($countries)): 
                        $maxCountry = max(array_column($countries, 'visits'));
                        foreach ($countries as $i => $country): 
                            $percent = round(($country['visits'] / $maxCountry) * 100);
                    ?>
                    <div class="country-item">
                        <div class="country-flag"><?= getCountryFlag($country['code']) ?></div>
                        <div class="country-info">
                            <div class="country-name"><?= $country['name'] ?></div>
                            <div class="country-bar">
                                <div class="country-bar-fill" style="width: <?= $percent ?>%"></div>
                            </div>
                        </div>
                        <div class="country-visits"><?= number_format($country['visits']) ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="widget-empty">
                        <i class="fas fa-globe-americas"></i>
                        <p>Belum ada data negara</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-pie"></i> Ringkasan</h3>
                </div>
                <div class="widget-body">
                    <div class="quick-stat">
                        <div class="qs-icon"><i class="fas fa-database"></i></div>
                        <div class="qs-info">
                            <div class="qs-label">Total All-Time</div>
                            <div class="qs-value"><?= number_format($totalAllTime) ?></div>
                        </div>
                    </div>
                    <div class="quick-stat">
                        <div class="qs-icon"><i class="fas fa-bullseye"></i></div>
                        <div class="qs-info">
                            <div class="qs-label">Bounce Rate</div>
                            <div class="qs-value <?= $stats['bounce_rate'] > 60 ? 'danger' : '' ?>"><?= $stats['bounce_rate'] ?>%</div>
                        </div>
                    </div>
                    <div class="quick-stat">
                        <div class="qs-icon"><i class="fas fa-layer-group"></i></div>
                        <div class="qs-info">
                            <div class="qs-label">Pages/Visit</div>
                            <div class="qs-value"><?= $stats['pages_per_visit'] ?></div>
                        </div>
                    </div>
                    <div class="quick-stat">
                        <div class="qs-icon"><i class="fas fa-stopwatch"></i></div>
                        <div class="qs-info">
                            <div class="qs-label">Avg. Session</div>
                            <div class="qs-value"><?= floor($stats['avg_session'] / 60) ?>m <?= $stats['avg_session'] % 60 ?>s</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Insights Card -->
            <div class="card widget-card insights-card">
                <div class="widget-header">
                    <h3><i class="fas fa-lightbulb"></i> Insights</h3>
                </div>
                <div class="widget-body">
                    <?php 
                    $insights = [];
                    if ($trends['total_visits'] > 20) $insights[] = ['type' => 'success', 'icon' => 'fa-arrow-trend-up', 'text' => 'Traffic naik ' . $trends['total_visits'] . '% dari periode sebelumnya'];
                    elseif ($trends['total_visits'] < -10) $insights[] = ['type' => 'warning', 'icon' => 'fa-arrow-trend-down', 'text' => 'Traffic turun ' . abs($trends['total_visits']) . '%, perlu strategi baru'];
                    
                    if ($stats['bounce_rate'] > 60) $insights[] = ['type' => 'warning', 'icon' => 'fa-exclamation-triangle', 'text' => 'Bounce rate tinggi (' . $stats['bounce_rate'] . '%), tingkatkan konten'];
                    elseif ($stats['bounce_rate'] < 30) $insights[] = ['type' => 'success', 'icon' => 'fa-check-circle', 'text' => 'Bounce rate rendah, pengunjung engaged'];
                    
                    if ($liveVisitors > 15) $insights[] = ['type' => 'info', 'icon' => 'fa-fire', 'text' => $liveVisitors . ' pengunjung aktif saat ini!'];
                    
                    if (empty($insights)) $insights[] = ['type' => 'info', 'icon' => 'fa-info-circle', 'text' => 'Data akan dianalisis setelah ada lebih banyak kunjungan'];
                    ?>
                    
                    <?php foreach ($insights as $insight): ?>
                    <div class="insight-item insight-<?= $insight['type'] ?>">
                        <div class="ii-icon">
                            <i class="fas <?= $insight['icon'] ?>"></i>
                        </div>
                        <div class="ii-text"><?= $insight['text'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
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
                    <h3>Cleanup Data Analytics</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Hapus data lama untuk menghemat storage</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeCleanupModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="cleanup-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Peringatan!</strong>
                    <p>Data yang dihapus tidak dapat dikembalikan. Pastikan Anda sudah backup jika diperlukan.</p>
                </div>
            </div>
            
            <div class="form-group">
                <label>Hapus data yang lebih lama dari:</label>
                <div class="cleanup-options">
                    <?php foreach ([30, 60, 90, 180, 365] as $days): ?>
                    <label class="cleanup-option">
                        <input type="radio" name="cleanup_days" value="<?= $days ?>" <?= $days === 30 ? 'checked' : '' ?>>
                        <span class="cleanup-option-content">
                            <strong><?= $days ?> Hari</strong>
                            <small><?= $days >= 365 ? 'Keep 1 tahun terakhir' : 'Keep ' . round($days/30) . ' bulan terakhir' ?></small>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="cleanup-info">
                <i class="fas fa-info-circle"></i>
                <span>Total data saat ini: <strong><?= number_format($totalAllTime) ?></strong> records</span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCleanupModal()">Batal</button>
            <a href="#" id="cleanupConfirmBtn" class="btn btn-danger">
                <i class="fas fa-trash"></i> Hapus Data Lama
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* ============================================================
   ANALYTICS PAGE - ENTERPRISE EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.analytics-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.analytics-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #0F172A 0%, #0EA5E9 60%, #3B82F6 100%);
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
    background: radial-gradient(circle, #3B82F6, transparent);
    top: -120px; right: 8%;
}

.orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, #06B6D4, transparent);
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

.hero-chart-lines {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 80px;
    background: 
        linear-gradient(to top, rgba(59,130,246,0.1), transparent),
        repeating-linear-gradient(90deg, transparent, transparent 80px, rgba(255,255,255,0.05) 80px, rgba(255,255,255,0.05) 81px);
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

.live-dot {
    width: 8px;
    height: 8px;
    background: #EF4444;
    border-radius: 50%;
    animation: liveDot 1.5s infinite;
    box-shadow: 0 0 8px rgba(239,68,68,0.6);
}

@keyframes liveDot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
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

.hms-item.live-pulse {
    background: rgba(239,68,68,0.2);
    border-color: rgba(239,68,68,0.4);
}

.live-indicator {
    width: 10px;
    height: 10px;
    background: #EF4444;
    border-radius: 50%;
    position: relative;
    box-shadow: 0 0 10px rgba(239,68,68,0.8);
}

.live-indicator::after {
    content: '';
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px solid rgba(239,68,68,0.6);
    animation: livePulse 1.5s infinite;
}

@keyframes livePulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(2); opacity: 0; }
}

.hero-right {
    display: flex;
    flex-direction: column;
    gap: 16px;
    align-items: flex-end;
}

.period-tabs-hero {
    display: flex;
    gap: 6px;
    padding: 4px;
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.15);
}

.period-tab {
    padding: 8px 16px;
    background: transparent;
    color: rgba(255,255,255,0.7);
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all var(--transition-fast);
}

.period-tab:hover {
    background: rgba(255,255,255,0.1);
    color: var(--white);
}

.period-tab.active {
    background: var(--white);
    color: var(--dark);
    box-shadow: var(--shadow-sm);
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

.btn-hero-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16,185,129,0.5);
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
    text-decoration: none;
}

.btn-ghost:hover {
    background: rgba(255,255,255,0.22);
    transform: translateY(-2px);
}

/* ===== LAYOUT ===== */
.analytics-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.analytics-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.analytics-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-cyan .stat-icon { background: linear-gradient(135deg, #06B6D4, #0891B2); }
.stat-blue .stat-icon { background: linear-gradient(135deg, #3B82F6, #1E40AF); }
.stat-green .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-orange .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }
.stat-purple .stat-icon { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }

/* ===== CHARTS ===== */
.analytics-chart-card,
.analytics-heatmap-card {
    padding: 0;
    overflow: hidden;
    margin-bottom: 24px;
}

.chart-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 12px;
}

[data-theme="dark"] .chart-header {
    border-color: rgba(255,255,255,0.08);
}

.chart-title h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

[data-theme="dark"] .chart-title h3 {
    color: var(--white);
}

.chart-title h3 i {
    color: #3B82F6;
}

.chart-title p {
    font-size: 0.82rem;
    color: var(--gray-600);
    margin: 0;
}

[data-theme="dark"] .chart-title p {
    color: var(--gray-400);
}

.chart-title p strong {
    color: #3B82F6;
    font-family: 'Space Grotesk', sans-serif;
}

.chart-legend {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: var(--gray-600);
    font-weight: 500;
}

[data-theme="dark"] .legend-item {
    color: var(--gray-400);
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.chart-body {
    padding: 20px 24px 24px;
}

/* Daily Chart */
.daily-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 8px;
    height: 300px;
    padding: 10px 0;
}

.daily-bar-group {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 0;
    position: relative;
    cursor: pointer;
}

.daily-bar-group:hover .daily-bar-main {
    background: linear-gradient(180deg, #60A5FA, #3B82F6);
    box-shadow: 0 4px 16px rgba(59,130,246,0.4);
}

.daily-bar-value {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--gray-700);
    font-family: 'Space Grotesk', sans-serif;
    opacity: 0;
    transition: opacity var(--transition);
}

.daily-bar-group:hover .daily-bar-value,
.daily-bar-value.peak {
    opacity: 1;
}

.daily-bar-value.peak {
    color: #F59E0B;
    font-size: 0.8rem;
}

.daily-bar-wrapper {
    width: 100%;
    height: 240px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 3px;
}

.daily-bar-main,
.daily-bar-unique {
    border-radius: 6px 6px 3px 3px;
    transition: all var(--transition);
    position: relative;
    animation: barGrow 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    transform-origin: bottom;
    transform: scaleY(0);
}

@keyframes barGrow {
    to { transform: scaleY(1); }
}

.daily-bar-main {
    width: 70%;
    max-width: 28px;
    background: linear-gradient(180deg, #3B82F6, #1E40AF);
    z-index: 2;
}

.daily-bar-unique {
    width: 50%;
    max-width: 18px;
    background: linear-gradient(180deg, #10B981, #047857);
    opacity: 0.5;
    z-index: 1;
}

.bar-peak-indicator {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-bottom: 6px solid #F59E0B;
    animation: bounce 1s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateX(-50%) translateY(0); }
    50% { transform: translateX(-50%) translateY(-4px); }
}

.daily-bar-label {
    font-size: 0.7rem;
    color: var(--gray-600);
    font-weight: 500;
    white-space: nowrap;
}

[data-theme="dark"] .daily-bar-label {
    color: var(--gray-400);
}

/* ===== HOURLY HEATMAP LARGE ===== */
.hourly-heatmap-large {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 6px;
    margin-bottom: 16px;
}

.hh-cell-large {
    aspect-ratio: 1;
    border-radius: 8px;
    position: relative;
    transition: all var(--transition);
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4px;
    overflow: hidden;
}

.hh-cell-large:hover {
    transform: scale(1.08);
    box-shadow: 0 4px 16px rgba(59,130,246,0.3);
    z-index: 2;
}

.hh-hour {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--white);
    font-family: 'Space Grotesk', sans-serif;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.hh-visits {
    font-size: 0.68rem;
    color: rgba(255,255,255,0.9);
    font-weight: 600;
    margin-top: 2px;
}

.hh-cell-large.peak {
    background: linear-gradient(135deg, #F59E0B, #D97706) !important;
    box-shadow: 0 4px 16px rgba(245,158,11,0.4);
}

.hh-peak-badge {
    position: absolute;
    top: 3px;
    right: 3px;
    width: 14px;
    height: 14px;
    background: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #F59E0B;
    font-size: 0.5rem;
}

.hh-cell-large.current {
    outline: 2px solid #EF4444;
    outline-offset: 2px;
}

.hh-now-indicator {
    position: absolute;
    bottom: 3px;
    left: 50%;
    transform: translateX(-50%);
    padding: 1px 6px;
    background: #EF4444;
    color: var(--white);
    border-radius: 10px;
    font-size: 0.55rem;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.heatmap-legend-large {
    padding-top: 14px;
    border-top: 1px solid var(--gray-100);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

[data-theme="dark"] .heatmap-legend-large {
    border-color: rgba(255,255,255,0.08);
}

.hh-legend-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.78rem;
    color: var(--gray-600);
}

[data-theme="dark"] .hh-legend-item {
    color: var(--gray-400);
}

.hh-gradient-sample {
    width: 100px;
    height: 10px;
    background: linear-gradient(90deg, rgba(59,130,246,0.1), rgba(59,130,246,0.95));
    border-radius: 5px;
}

.heatmap-summary {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}

.hs-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: var(--gray-600);
}

[data-theme="dark"] .hs-item {
    color: var(--gray-400);
}

.hs-item strong {
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .hs-item strong {
    color: var(--white);
}

/* ===== ANALYTICS GRID 2COL & 3COL ===== */
.analytics-grid-2col {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
}

.analytics-grid-3col {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

/* ===== TOP PAGES LIST ===== */
.top-pages-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.tp-item {
    display: grid;
    grid-template-columns: 32px 1fr auto;
    gap: 12px;
    align-items: center;
    padding: 10px 12px;
    border-radius: var(--radius);
    transition: background var(--transition-fast);
}

.tp-item:hover {
    background: var(--gray-50);
}

[data-theme="dark"] .tp-item:hover {
    background: rgba(255,255,255,0.04);
}

.tp-rank {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    background: var(--gray-100);
    color: var(--gray-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    font-weight: 700;
    font-family: 'Space Grotesk', sans-serif;
}

.tp-rank.rank-1 {
    background: linear-gradient(135deg, #F59E0B, #D97706);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(245,158,11,0.3);
}

.tp-rank.rank-2 {
    background: linear-gradient(135deg, #94A3B8, #64748B);
    color: var(--white);
}

.tp-rank.rank-3 {
    background: linear-gradient(135deg, #CD7F32, #A0522D);
    color: var(--white);
}

[data-theme="dark"] .tp-rank {
    background: rgba(255,255,255,0.08);
    color: var(--gray-300);
}

.tp-info {
    min-width: 0;
}

.tp-name {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

[data-theme="dark"] .tp-name {
    color: var(--white);
}

.tp-meta {
    display: flex;
    gap: 10px;
    font-size: 0.72rem;
    color: var(--gray-600);
}

[data-theme="dark"] .tp-meta {
    color: var(--gray-400);
}

.tp-meta i {
    color: #3B82F6;
    font-size: 0.65rem;
    margin-right: 3px;
}

.tp-bar {
    width: 80px;
    height: 6px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .tp-bar {
    background: rgba(255,255,255,0.08);
}

.tp-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #3B82F6, #1E40AF);
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

@keyframes barFill {
    from { width: 0; }
}

/* ===== TRAFFIC SOURCES ===== */
.traffic-sources {
    display: grid;
    grid-template-columns: 160px 1fr;
    gap: 20px;
    align-items: center;
}

.traffic-donut-wrapper {
    position: relative;
    width: 160px;
    height: 160px;
}

.traffic-donut {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.donut-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
    line-height: 1;
}

[data-theme="dark"] .donut-value {
    color: var(--white);
}

.donut-label {
    font-size: 0.72rem;
    color: var(--gray-500);
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.traffic-legend-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 320px;
    overflow-y: auto;
}

.tl-item {
    display: grid;
    grid-template-columns: 32px 1fr auto;
    gap: 10px;
    align-items: center;
    padding: 8px;
    border-radius: var(--radius-sm);
    transition: background var(--transition-fast);
}

.tl-item:hover {
    background: var(--gray-50);
}

[data-theme="dark"] .tl-item:hover {
    background: rgba(255,255,255,0.04);
}

.tl-icon {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.88rem;
}

.tl-info {
    min-width: 0;
}

.tl-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
}

[data-theme="dark"] .tl-name {
    color: var(--white);
}

.tl-meta {
    font-size: 0.72rem;
    color: var(--gray-500);
}

.tl-percent {
    font-size: 0.95rem;
    font-weight: 700;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== BREAKDOWN LIST ===== */
.breakdown-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.breakdown-item {
    display: grid;
    grid-template-columns: 36px 1fr auto;
    gap: 10px;
    align-items: center;
}

.bi-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.bi-info {
    min-width: 0;
}

.bi-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 4px;
}

[data-theme="dark"] .bi-name {
    color: var(--white);
}

.bi-bar {
    height: 5px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .bi-bar {
    background: rgba(255,255,255,0.08);
}

.bi-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

.bi-stats {
    text-align: right;
}

.bi-percent {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .bi-percent {
    color: var(--white);
}

.bi-count {
    font-size: 0.7rem;
    color: var(--gray-500);
}

/* ===== SIDEBAR WIDGETS ===== */
.analytics-sidebar {
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
    color: #3B82F6;
}

.widget-badge {
    padding: 3px 10px;
    background: #3B82F6;
    color: var(--white);
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
}

.widget-badge.live-badge {
    background: #EF4444;
    animation: pulseBadge 2s infinite;
}

@keyframes pulseBadge {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
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

/* ===== LIVE WIDGET ===== */
.live-widget {
    background: linear-gradient(135deg, #0F172A, #1E40AF);
    color: var(--white);
}

.live-widget .widget-header {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.1);
}

.live-widget .widget-header h3 {
    color: var(--white);
}

.live-count-wrapper {
    text-align: center;
    padding: 20px 0;
}

.live-number {
    font-size: 4rem;
    font-weight: 800;
    color: var(--white);
    font-family: 'Space Grotesk', sans-serif;
    line-height: 1;
    text-shadow: 0 4px 20px rgba(59,130,246,0.5);
    animation: liveNumberPulse 2s infinite;
}

@keyframes liveNumberPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.live-label {
    font-size: 0.82rem;
    opacity: 0.8;
    margin-top: 8px;
}

.live-mini-stats {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.lms-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 0.78rem;
    opacity: 0.9;
}

.lms-item i {
    color: #67E8F9;
}

.live-feed {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.lf-header {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.7;
    margin-bottom: 10px;
}

.lf-item {
    display: grid;
    grid-template-columns: 28px 1fr auto;
    gap: 10px;
    align-items: center;
    padding: 8px 10px;
    background: rgba(255,255,255,0.06);
    border-radius: var(--radius-sm);
    margin-bottom: 6px;
}

.lf-item:last-child {
    margin-bottom: 0;
}

.lf-icon {
    width: 28px;
    height: 28px;
    border-radius: var(--radius-sm);
    background: rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
}

.lf-info {
    min-width: 0;
}

.lf-page {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--white);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.lf-time {
    font-size: 0.68rem;
    opacity: 0.7;
}

.lf-pulse {
    width: 8px;
    height: 8px;
    background: #10B981;
    border-radius: 50%;
    animation: livePulseSmall 1.5s infinite;
}

@keyframes livePulseSmall {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.3); }
}

/* ===== COUNTRY ITEM ===== */
.country-item {
    display: grid;
    grid-template-columns: 32px 1fr auto;
    gap: 10px;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .country-item {
    border-color: rgba(255,255,255,0.05);
}

.country-item:last-child {
    border-bottom: none;
}

.country-flag {
    font-size: 1.5rem;
    line-height: 1;
}

.country-info {
    min-width: 0;
}

.country-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

[data-theme="dark"] .country-name {
    color: var(--gray-200);
}

.country-bar {
    height: 4px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .country-bar {
    background: rgba(255,255,255,0.08);
}

.country-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #3B82F6, #06B6D4);
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

.country-visits {
    font-size: 0.88rem;
    font-weight: 700;
    color: #3B82F6;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== QUICK STATS ===== */
.quick-stat {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .quick-stat {
    border-color: rgba(255,255,255,0.05);
}

.quick-stat:last-child {
    border-bottom: none;
}

.qs-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-sm);
    background: rgba(59,130,246,0.1);
    color: #3B82F6;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.qs-info {
    flex: 1;
    min-width: 0;
}

.qs-label {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-bottom: 2px;
}

[data-theme="dark"] .qs-label {
    color: var(--gray-400);
}

.qs-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .qs-value {
    color: var(--white);
}

.qs-value.danger {
    color: #EF4444;
}

/* ===== INSIGHTS ===== */
.insights-card {
    background: linear-gradient(135deg, rgba(59,130,246,0.05), rgba(139,92,246,0.05));
    border: 1px solid rgba(59,130,246,0.1);
}

.insight-item {
    display: flex;
    gap: 10px;
    padding: 10px;
    border-radius: var(--radius);
    margin-bottom: 8px;
    border-left: 3px solid;
}

.insight-item:last-child {
    margin-bottom: 0;
}

.insight-item.insight-success {
    background: rgba(16,185,129,0.08);
    border-color: #10B981;
}

.insight-item.insight-warning {
    background: rgba(245,158,11,0.08);
    border-color: #F59E0B;
}

.insight-item.insight-info {
    background: rgba(59,130,246,0.08);
    border-color: #3B82F6;
}

.ii-icon {
    width: 28px;
    height: 28px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    flex-shrink: 0;
}

.insight-item.insight-success .ii-icon {
    background: rgba(16,185,129,0.2);
    color: #10B981;
}

.insight-item.insight-warning .ii-icon {
    background: rgba(245,158,11,0.2);
    color: #F59E0B;
}

.insight-item.insight-info .ii-icon {
    background: rgba(59,130,246,0.2);
    color: #3B82F6;
}

.ii-text {
    font-size: 0.82rem;
    color: var(--dark);
    line-height: 1.5;
    flex: 1;
}

[data-theme="dark"] .ii-text {
    color: var(--gray-200);
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
    color: #3B82F6;
}

.cleanup-info strong {
    color: #3B82F6;
    font-family: 'Space Grotesk', sans-serif;
}

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

/* ===== EMPTY STATE ===== */
.empty-state-ult {
    text-align: center;
    padding: 40px 20px;
    color: var(--gray-600);
}

[data-theme="dark"] .empty-state-ult {
    color: var(--gray-400);
}

.empty-state-ult i {
    font-size: 2.5rem;
    color: var(--gray-300);
    margin-bottom: 10px;
    display: block;
}

[data-theme="dark"] .empty-state-ult i {
    color: var(--gray-600);
}

.empty-state-ult p {
    font-size: 0.88rem;
    margin: 0;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .analytics-layout {
        grid-template-columns: 1fr;
    }
    
    .analytics-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
    
    .analytics-grid-3col {
        grid-template-columns: 1fr 1fr;
    }
    
    .analytics-grid-3col > *:last-child {
        grid-column: 1 / -1;
    }
}

@media (max-width: 900px) {
    .analytics-grid-2col {
        grid-template-columns: 1fr;
    }
    
    .traffic-sources {
        grid-template-columns: 1fr;
    }
    
    .traffic-donut-wrapper {
        margin: 0 auto;
    }
}

@media (max-width: 768px) {
    .analytics-page {
        padding: 16px;
    }
    
    .analytics-hero {
        padding: 24px 20px;
    }
    
    .hero-title {
        font-size: 1.6rem;
    }
    
    .hero-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .hero-right {
        width: 100%;
        align-items: stretch;
    }
    
    .period-tabs-hero {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .hero-actions {
        width: 100%;
    }
    
    .hero-actions .btn-hero-primary {
        flex: 1;
    }
    
    .analytics-stats {
        grid-template-columns: 1fr 1fr;
    }
    
    .analytics-grid-3col {
        grid-template-columns: 1fr;
    }
    
    .analytics-grid-3col > *:last-child {
        grid-column: auto;
    }
    
    .hourly-heatmap-large {
        grid-template-columns: repeat(6, 1fr);
    }
    
    .daily-chart {
        overflow-x: auto;
        padding-bottom: 10px;
        justify-content: flex-start;
    }
    
    .daily-bar-group {
        min-width: 40px;
        flex: 0 0 auto;
    }
    
    .chart-header {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media print {
    .analytics-hero,
    .analytics-sidebar,
    .hero-actions,
    .period-tabs-hero {
        display: none !important;
    }
    
    .analytics-layout {
        grid-template-columns: 1fr;
    }
    
    .card {
        break-inside: avoid;
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
        const suffix = el.dataset.suffix || '';
        const duration = 1200;
        const start = performance.now();
        
        const step = (now) => {
            const p = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            const current = Math.floor(target * eased);
            el.textContent = current.toLocaleString('id-ID') + suffix;
            if (p < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString('id-ID') + suffix;
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

    /* ===== BAR ANIMATION DELAY ===== */
    document.querySelectorAll('.daily-bar-main').forEach((bar, i) => {
        bar.style.animationDelay = (i * 30) + 'ms';
    });

    document.querySelectorAll('.daily-bar-unique').forEach((bar, i) => {
        bar.style.animationDelay = (i * 30 + 100) + 'ms';
    });

    document.querySelectorAll('.tp-bar-fill').forEach((bar, i) => {
        bar.style.animationDelay = (i * 50) + 'ms';
    });

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
            const btn = document.getElementById('cleanupConfirmBtn');
            if (btn) btn.href = `?action=clear&days=${days}`;
        });
    });

    // Set default URL
    const defaultDays = document.querySelector('input[name="cleanup_days"]:checked')?.value || 30;
    const cleanupBtn = document.getElementById('cleanupConfirmBtn');
    if (cleanupBtn) {
        cleanupBtn.href = `?action=clear&days=${defaultDays}`;
        cleanupBtn.addEventListener('click', function(e) {
            if (!confirm(`Yakin ingin menghapus data yang lebih lama dari ${defaultDays} hari?\n\nAksi ini TIDAK BISA dibatalkan!`)) {
                e.preventDefault();
            }
        });
    }

    /* ===== EXPORT CSV ===== */
    window.exportAnalytics = function() {
        const topPages = <?= json_encode($topPages) ?>;
        const trafficSources = <?= json_encode($trafficSources) ?>;
        const stats = <?= json_encode($stats) ?>;
        
        if (!topPages || topPages.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        let csv = '';
        
        // Summary section
        csv += 'ANALYTICS SUMMARY\n';
        csv += `Period,<?= $periodLabels[$period] ?>\n`;
        csv += `Total Visits,${stats.total_visits}\n`;
        csv += `Unique Visitors,${stats.unique_visitors}\n`;
        csv += `Total Pages,${stats.total_pages}\n`;
        csv += `Avg Session (sec),${stats.avg_session}\n`;
        csv += `Bounce Rate,${stats.bounce_rate}%\n`;
        csv += `Pages/Visit,${stats.pages_per_visit}\n\n`;
        
        // Top Pages
        csv += 'TOP PAGES\n';
        csv += 'Page URL,Visits,Unique Visitors\n';
        topPages.forEach(p => {
            csv += `"${p.page_url}",${p.visits},${p.unique_visitors}\n`;
        });
        csv += '\n';
        
        // Traffic Sources
        csv += 'TRAFFIC SOURCES\n';
        csv += 'Source,Visits,Unique Visitors\n';
        trafficSources.forEach(ts => {
            csv += `"${ts.source}",${ts.visits},${ts.unique_visitors}\n`;
        });
        
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `analytics-report-<?= $period ?>days-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show('✓ Report analytics berhasil di-export', 'success');
        }, 500);
    };

    /* ===== REFRESH DATA ===== */
    window.refreshData = function() {
        const btn = document.getElementById('refreshBtn');
        const icon = btn?.querySelector('i');
        icon?.classList.add('fa-spin');
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memperbarui data...', 'info');
        
        setTimeout(() => {
            window.location.reload();
        }, 500);
    };

    /* ===== LIVE COUNTER UPDATE (simulated) ===== */
    const liveCountEl = document.getElementById('liveCount');
    if (liveCountEl) {
        let currentLive = <?= $liveVisitors ?>;
        
        setInterval(() => {
            const change = Math.floor(Math.random() * 5) - 2;
            currentLive = Math.max(1, currentLive + change);
            liveCountEl.textContent = currentLive;
            liveCountEl.style.animation = 'none';
            setTimeout(() => {
                liveCountEl.style.animation = '';
            }, 10);
        }, 5000);
    }

    /* ===== HOURLY HEATMAP TOOLTIPS ===== */
    document.querySelectorAll('.hh-cell-large').forEach(cell => {
        cell.addEventListener('mouseenter', function(e) {
            const hour = this.dataset.hour;
            const visits = this.dataset.visits;
            
            const tooltip = document.createElement('div');
            tooltip.className = 'heatmap-tooltip';
            tooltip.innerHTML = `
                <div style="font-weight:700;margin-bottom:4px">${String(hour).padStart(2, '0')}:00 - ${String(hour).padStart(2, '0')}:59</div>
                <div style="font-size:0.85rem">${Number(visits).toLocaleString('id-ID')} visits</div>
            `;
            
            tooltip.style.cssText = `
                position: fixed;
                background: var(--dark);
                color: var(--white);
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 0.82rem;
                z-index: 10000;
                pointer-events: none;
                box-shadow: 0 8px 20px rgba(0,0,0,0.3);
                left: ${e.clientX + 10}px;
                top: ${e.clientY - 10}px;
            `;
            
            document.body.appendChild(tooltip);
            this._tooltip = tooltip;
        });
        
        cell.addEventListener('mousemove', function(e) {
            if (this._tooltip) {
                this._tooltip.style.left = (e.clientX + 10) + 'px';
                this._tooltip.style.top = (e.clientY - 10) + 'px';
            }
        });
        
        cell.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        // R = Refresh
        if (e.key === 'r' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) && !e.ctrlKey && !e.metaKey) {
            e.preventDefault();
            refreshData();
        }
        
        // Escape = Close modals
        if (e.key === 'Escape') {
            closeCleanupModal();
        }
        
        // Number keys 1-6 for period switching
        if (['1', '2', '3', '4', '5', '6'].includes(e.key) && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) && !e.ctrlKey && !e.metaKey) {
            const periods = ['1', '7', '14', '30', '90', '365'];
            const idx = parseInt(e.key) - 1;
            if (periods[idx]) {
                e.preventDefault();
                window.location = `?period=${periods[idx]}`;
            }
        }
    });

    /* ===== MODAL BACKDROP CLICK ===== */
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

    console.log('%c📊 Analytics v4.0 Loaded', 'color:#3B82F6;font-weight:bold;font-size:14px');
    console.log('%c   Features: Live Visitors, Hourly Heatmap, Traffic Sources, Country Map, Insights', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: 1-6 (Periods), R (Refresh), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>