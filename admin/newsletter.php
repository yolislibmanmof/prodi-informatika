<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Newsletter';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
}

/* ===== HANDLE ACTIONS ===== */
if (isset($_GET['action']) && $db) {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($_GET['action'] === 'delete' && $id) {
        try {
            $db->prepare("DELETE FROM newsletter_subscribers WHERE id = ?")->execute([$id]);
            setFlash('success', 'Subscriber berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus subscriber');
        }
        header('Location: newsletter.php');
        exit;
    }
    
    if ($_GET['action'] === 'approve' && $id) {
        try {
            $db->prepare("UPDATE newsletter_subscribers SET status = 'aktif', updated_at = NOW() WHERE id = ?")->execute([$id]);
            setFlash('success', 'Subscriber berhasil diaktifkan');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mengaktifkan subscriber');
        }
        header('Location: newsletter.php');
        exit;
    }
    
    if ($_GET['action'] === 'unsubscribe' && $id) {
        try {
            $db->prepare("UPDATE newsletter_subscribers SET status = 'unsubscribed', updated_at = NOW() WHERE id = ?")->execute([$id]);
            setFlash('success', 'Subscriber berhasil di-unsubscribe');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mengubah status');
        }
        header('Location: newsletter.php');
        exit;
    }
    
    if ($_GET['action'] === 'bulk_delete' && !empty($_POST['selected'])) {
        try {
            $ids = array_map('intval', $_POST['selected']);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $db->prepare("DELETE FROM newsletter_subscribers WHERE id IN ($placeholders)")->execute($ids);
            setFlash('success', count($ids) . ' subscriber berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus subscriber');
        }
        header('Location: newsletter.php');
        exit;
    }
}

/* ===== FETCH PARAMETERS ===== */
$filterStatus = $_GET['status'] ?? '';
$searchQuery = $_GET['q'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$sortDir = $_GET['dir'] ?? 'DESC';

$where = "1=1";
$params = [];

if ($filterStatus) { $where .= " AND status = ?"; $params[] = $filterStatus; }
if ($searchQuery) { 
    $searchParam = "%$searchQuery%";
    $where .= " AND (email LIKE ? OR nama LIKE ?)"; 
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$validSorts = ['created_at', 'email', 'nama'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'created_at';
$sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

/* ===== FETCH DATA ===== */
$subscribers = [];
$stats = [
    'total' => 0,
    'aktif' => 0,
    'pending' => 0,
    'unsubscribed' => 0,
];

if ($db) {
    try {
        $sql = "SELECT * FROM newsletter_subscribers WHERE $where ORDER BY $sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $subscribers = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM newsletter_subscribers")->fetchColumn();
        $stats['aktif'] = (int)$db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status='aktif'")->fetchColumn();
        $stats['pending'] = (int)$db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status='pending'")->fetchColumn();
        $stats['unsubscribed'] = (int)$db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status='unsubscribed'")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== 7-DAY SUBSCRIPTION TREND ===== */
$weeklyTrend = [];
if ($db) {
    try {
        $trendData = $db->query("
            SELECT DATE(created_at) as date, COUNT(*) as total 
            FROM newsletter_subscribers 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at) 
            ORDER BY date ASC
        ")->fetchAll();
        
        $startDate = strtotime('-6 days');
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("+$i days", $startDate));
            $found = array_filter($trendData, fn($d) => $d['date'] === $date);
            $weeklyTrend[] = [
                'day' => date('D', strtotime($date)),
                'total' => $found ? reset($found)['total'] : 0
            ];
        }
    } catch (Throwable $e) {}
}

function getInitials($name) {
    if (!$name) return '?';
    $parts = explode(' ', trim($name));
    $initial = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initial .= strtoupper(substr(end($parts), 0, 1));
    }
    return $initial;
}

include 'includes/header.php';
?>

<div class="page-content newsletter-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="newsletter-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-mail-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-paper-plane"></i>
                    <span>Email Marketing</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-envelope-open-text"></i>
                    </span>
                    Kelola Newsletter
                </h1>
                <p class="hero-subtitle">
                    Database subscriber email untuk broadcast informasi prodi
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-users"></i>
                        <span><strong><?= number_format($stats['total']) ?></strong> Total</span>
                    </div>
                    <div class="hms-item live">
                        <span class="live-dot"></span>
                        <span><strong><?= number_format($stats['aktif']) ?></strong> Aktif</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-clock"></i>
                        <span><strong><?= number_format($stats['pending']) ?></strong> Pending</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="openImportModal()">
                    <i class="fas fa-file-csv"></i>
                    <span>Import CSV</span>
                </button>
                <button class="btn btn-ghost" onclick="openBroadcastModal()">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send Broadcast</span>
                </button>
                <button class="btn btn-hero-primary" onclick="exportSubscribers()">
                    <i class="fas fa-download"></i>
                    <span>Export Data</span>
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
    <div class="newsletter-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="newsletter-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid newsletter-stats">
                <div class="stat-card stat-indigo">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Subscriber</p>
                        <span class="stat-trend up"><i class="fas fa-database"></i> Database</span>
                    </div>
                </div>

                <div class="stat-card stat-emerald">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['aktif'] ?>">0</h3>
                        <p>Aktif</p>
                        <span class="stat-trend up live-pulse"><i class="fas fa-circle"></i> Reachable</span>
                    </div>
                </div>

                <div class="stat-card stat-amber">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['pending'] ?>">0</h3>
                        <p>Pending</p>
                        <span class="stat-trend up"><i class="fas fa-hourglass-half"></i> Verifikasi</span>
                    </div>
                </div>

                <div class="stat-card stat-slate">
                    <div class="stat-icon"><i class="fas fa-user-slash"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['unsubscribed'] ?>">0</h3>
                        <p>Unsubscribed</p>
                        <span class="stat-trend down"><i class="fas fa-ban"></i> Opt-out</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card newsletter-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari email atau nama..." value="<?= clean($searchQuery) ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterStatus" class="toolbar-select">
                            <option value="">📊 Semua Status</option>
                            <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>🟢 Aktif</option>
                            <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>🟡 Pending</option>
                            <option value="unsubscribed" <?= $filterStatus === 'unsubscribed' ? 'selected' : '' ?>>⚪ Unsubscribed</option>
                        </div>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <div class="sort-dropdown">
                        <button class="toolbar-icon-btn" id="sortBtn" title="Urutkan">
                            <i class="fas fa-sort-amount-down"></i>
                        </button>
                        <div class="sort-menu" id="sortMenu">
                            <a href="?sort=created_at&dir=DESC" class="sort-item <?= $sortBy === 'created_at' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-history"></i> Terbaru
                            </a>
                            <a href="?sort=created_at&dir=ASC" class="sort-item <?= $sortBy === 'created_at' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-history"></i> Terlama
                            </a>
                            <a href="?sort=email&dir=ASC" class="sort-item <?= $sortBy === 'email' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Email A-Z
                            </a>
                        </div>
                    </div>
                    
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid" title="Grid View">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button class="view-btn" data-view="list" title="List View">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    
                    <button class="toolbar-icon-btn" onclick="resetFilters()" title="Reset Filter">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
            </div>

            <!-- Bulk Actions Bar -->
            <form method="POST" action="?action=bulk_delete" id="bulkForm">
            <div class="bulk-bar" id="bulkBar">
                <div class="bulk-info">
                    <i class="fas fa-check-circle"></i>
                    <span><strong id="selectedCount">0</strong> subscriber dipilih</span>
                </div>
                <div class="bulk-actions">
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus subscriber yang dipilih?')">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        Batal
                    </button>
                </div>
            </div>

            <!-- Subscribers Grid -->
            <div class="subscribers-grid" id="subscribersGrid" data-view="grid">
                <?php if (!empty($subscribers)): ?>
                    <?php foreach ($subscribers as $s): 
                        $statusColors = [
                            'aktif' => ['bg' => 'rgba(16,185,129,0.12)', 'color' => '#047857', 'icon' => 'fa-check-circle'],
                            'pending' => ['bg' => 'rgba(245,158,11,0.12)', 'color' => '#92400E', 'icon' => 'fa-clock'],
                            'unsubscribed' => ['bg' => 'rgba(107,114,128,0.12)', 'color' => '#374151', 'icon' => 'fa-user-slash']
                        ];
                        $st = $statusColors[$s['status']] ?? $statusColors['unsubscribed'];
                        $initials = getInitials($s['nama']);
                        $colors = ['#4F46E5', '#10B981', '#F59E0B', '#EC4899', '#06B6D4'];
                        $avatarColor = $colors[ord($initials[0]) % count($colors)];
                    ?>
                    <div class="subscriber-card" data-id="<?= $s['id'] ?>">
                        <!-- Checkbox -->
                        <label class="card-checkbox">
                            <input type="checkbox" name="selected[]" class="sub-check" value="<?= $s['id'] ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <!-- Status Badge -->
                        <div class="sc-status" style="background: <?= $st['bg'] ?>; color: <?= $st['color'] ?>">
                            <i class="fas <?= $st['icon'] ?>"></i>
                            <?= ucfirst($s['status']) ?>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="sc-body">
                            <div class="sc-avatar" style="background: <?= $avatarColor ?>">
                                <?= $initials ?>
                            </div>
                            <div class="sc-info">
                                <h3 class="sc-name"><?= clean($s['nama'] ?: 'Tanpa Nama') ?></h3>
                                <p class="sc-email" title="<?= clean($s['email']) ?>"><?= clean($s['email']) ?></p>
                            </div>
                        </div>
                        
                        <div class="sc-meta">
                            <div class="sc-date">
                                <i class="fas fa-calendar"></i>
                                <?= function_exists('formatTanggal') ? formatTanggal($s['created_at']) : date('d M Y', strtotime($s['created_at'])) ?>
                            </div>
                        </div>
                        
                        <div class="sc-actions">
                            <?php if ($s['status'] === 'pending'): ?>
                            <a href="?action=approve&id=<?= $s['id'] ?>" class="sc-action-btn success" title="Aktifkan">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($s['status'] !== 'unsubscribed'): ?>
                            <a href="?action=unsubscribe&id=<?= $s['id'] ?>" class="sc-action-btn warning" title="Unsubscribe" data-confirm="Yakin unsubscriber ini?">
                                <i class="fas fa-user-slash"></i>
                            </a>
                            <?php endif; ?>
                            
                            <a href="?action=delete&id=<?= $s['id'] ?>" class="sc-action-btn danger" title="Hapus" data-confirm="Yakin hapus subscriber ini?">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration">
                            <svg viewBox="0 0 200 150" width="200" height="150">
                                <ellipse cx="100" cy="130" rx="70" ry="10" fill="var(--gray-200)" opacity="0.5"/>
                                <rect x="40" y="40" width="120" height="70" rx="8" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <path d="M 40 45 L 100 80 L 160 45" fill="none" stroke="var(--gray-300)" stroke-width="2"/>
                                <circle cx="155" cy="45" r="18" fill="#4F46E5" opacity="0.9"/>
                                <path d="M 148 45 L 162 45 M 155 38 L 155 52" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Tidak ada subscriber</h3>
                        <p>Belum ada data subscriber yang terdaftar</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openImportModal()">
                                <i class="fas fa-file-csv"></i> Import CSV
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- List View (Table) -->
            <?php if (!empty($subscribers)): ?>
            <div class="subscribers-list-view" id="subscribersList" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="selectAllTable"></th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscribers as $s): 
                                    $st = $statusColors[$s['status']] ?? $statusColors['unsubscribed'];
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="selected[]" class="sub-check" value="<?= $s['id'] ?>"></td>
                                    <td>
                                        <strong><?= clean($s['nama'] ?: 'Tanpa Nama') ?></strong>
                                    </td>
                                    <td>
                                        <code style="font-size:0.82rem;padding:3px 8px;background:var(--gray-100);border-radius:4px"><?= clean($s['email']) ?></code>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>">
                                            <i class="fas <?= $st['icon'] ?>"></i> <?= ucfirst($s['status']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:0.82rem"><?= function_exists('formatTanggal') ? formatTanggal($s['created_at']) : date('d M Y', strtotime($s['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($s['status'] === 'pending'): ?>
                                            <a href="?action=approve&id=<?= $s['id'] ?>" class="btn-icon btn-sm btn-success" title="Aktifkan">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($s['status'] !== 'unsubscribed'): ?>
                                            <a href="?action=unsubscribe&id=<?= $s['id'] ?>" class="btn-icon btn-sm btn-warning" title="Unsubscribe">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?= $s['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus subscriber ini?" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="result-info">
                <span>Menampilkan <strong><?= count($subscribers) ?></strong> subscriber</span>
            </div>
            <?php endif; ?>
            </form>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="newsletter-sidebar">
            <!-- Weekly Trend -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-line"></i> Tren 7 Hari</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($weeklyTrend)): 
                        $maxTrend = max(array_column($weeklyTrend, 'total')) ?: 1;
                    ?>
                    <div class="trend-chart">
                        <?php foreach ($weeklyTrend as $t): 
                            $height = max(4, ($t['total'] / $maxTrend) * 100);
                        ?>
                        <div class="tc-bar-group">
                            <div class="tc-bar" style="height: <?= $height ?>%">
                                <?php if ($t['total'] > 0): ?>
                                <span class="tc-value"><?= $t['total'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="tc-label"><?= $t['day'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="widget-empty">
                        <i class="fas fa-chart-line"></i>
                        <p>Belum ada data tren</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribusi Status</h3>
                </div>
                <div class="widget-body">
                    <div class="status-donut">
                        <svg viewBox="0 0 36 36" class="donut-ring">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--gray-100)" stroke-width="3.8"/>
                            <?php 
                            $total = $stats['total'] ?: 1;
                            $offset = 0;
                            $segments = [
                                ['value' => $stats['aktif'], 'color' => '#10B981'],
                                ['value' => $stats['pending'], 'color' => '#F59E0B'],
                                ['value' => $stats['unsubscribed'], 'color' => '#6B7280']
                            ];
                            foreach ($segments as $seg):
                                if ($seg['value'] > 0):
                                    $pct = ($seg['value'] / $total) * 100;
                                    $dashArray = ($pct * 2.83) . ', ' . (100 - ($pct * 2.83));
                            ?>
                            <circle cx="18" cy="18" r="15.9" fill="none" 
                                stroke="<?= $seg['color'] ?>" 
                                stroke-width="3.8"
                                stroke-dasharray="<?= $dashArray ?>"
                                stroke-dashoffset="-<?= $offset ?>"
                                transform="rotate(-90 18 18)"/>
                            <?php 
                                    $offset += ($pct * 2.83);
                                endif;
                            endforeach; 
                            ?>
                        </svg>
                        <div class="donut-center">
                            <div class="donut-value"><?= $stats['total'] ?></div>
                            <div class="donut-label">Total</div>
                        </div>
                    </div>
                    <div class="status-legend">
                        <div class="sl-item">
                            <span class="sl-dot" style="background: #10B981"></span>
                            <span class="sl-name">Aktif</span>
                            <span class="sl-count"><?= $stats['aktif'] ?></span>
                        </div>
                        <div class="sl-item">
                            <span class="sl-dot" style="background: #F59E0B"></span>
                            <span class="sl-name">Pending</span>
                            <span class="sl-count"><?= $stats['pending'] ?></span>
                        </div>
                        <div class="sl-item">
                            <span class="sl-dot" style="background: #6B7280"></span>
                            <span class="sl-name">Unsubscribed</span>
                            <span class="sl-count"><?= $stats['unsubscribed'] ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="widget-body">
                    <button class="qa-btn primary" onclick="openBroadcastModal()">
                        <i class="fas fa-paper-plane"></i>
                        <span>Kirim Broadcast</span>
                    </button>
                    <button class="qa-btn" onclick="openImportModal()">
                        <i class="fas fa-file-csv"></i>
                        <span>Import CSV</span>
                    </button>
                    <a href="kontak.php" class="qa-btn">
                        <i class="fas fa-envelope"></i>
                        <span>Pesan Masuk</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Tips -->
            <div class="card widget-card tips-card">
                <div class="widget-header">
                    <h3><i class="fas fa-lightbulb"></i> Tips</h3>
                </div>
                <div class="widget-body">
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Gunakan double opt-in untuk memastikan email valid</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Hapus subscriber yang bounce untuk menjaga reputasi</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Segmentasi subscriber berdasarkan minat</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== IMPORT MODAL ===== -->
<div class="modal" id="importModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header modal-header-gradient newsletter-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-file-csv"></i></div>
                <div>
                    <h3>Import Subscriber</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Upload file CSV untuk import massal</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeImportModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="upload-area upload-large" style="min-height: 180px;">
                <div class="upload-placeholder-large">
                    <div class="upload-icon-large">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h4>Drag & drop file CSV di sini</h4>
                    <p>atau klik untuk memilih file</p>
                    <small class="upload-note">Format: email, nama (opsional)</small>
                </div>
            </div>
            <div style="margin-top: 16px; padding: 12px; background: rgba(79,70,229,0.05); border-radius: var(--radius); font-size: 0.82rem; color: var(--gray-700);">
                <i class="fas fa-info-circle" style="color: #4F46E5; margin-right: 6px;"></i>
                Contoh format CSV:<br>
                <code style="background: var(--white); padding: 2px 6px; border-radius: 4px; font-family: monospace;">email@domain.com, Nama Lengkap</code>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
            <button class="btn btn-primary"><i class="fas fa-upload"></i> Upload & Import</button>
        </div>
    </div>
</div>

<!-- ===== BROADCAST MODAL ===== -->
<div class="modal" id="broadcastModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header modal-header-gradient newsletter-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-paper-plane"></i></div>
                <div>
                    <h3>Kirim Broadcast</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Kirim email ke semua subscriber aktif</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeBroadcastModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="field-label">Subjek Email *</label>
                <div class="input-icon">
                    <i class="fas fa-heading"></i>
                    <input type="text" placeholder="Masukkan subjek email...">
                </div>
            </div>
            <div class="form-group">
                <label class="field-label">Konten Email *</label>
                <div class="textarea-wrap">
                    <textarea rows="6" placeholder="Tulis konten email di sini..."></textarea>
                </div>
            </div>
            <div style="padding: 12px; background: rgba(16,185,129,0.05); border-radius: var(--radius); font-size: 0.82rem; color: var(--gray-700); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-users" style="color: #10B981;"></i>
                Akan dikirim ke <strong><?= $stats['aktif'] ?></strong> subscriber aktif
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeBroadcastModal()">Batal</button>
            <button class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kirim Broadcast</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   NEWSLETTER PAGE - NEWSLETTER MANAGER EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.newsletter-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.newsletter-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #312E81 0%, #4F46E5 50%, #6366F1 100%);
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
    background: radial-gradient(circle, #A5B4FC, transparent);
    top: -120px; right: 8%;
}

.orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, #818CF8, transparent);
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

.hero-mail-pattern {
    position: absolute;
    top: 50%;
    right: 5%;
    transform: translateY(-50%);
    font-size: 18rem;
    color: rgba(255,255,255,0.04);
    pointer-events: none;
}

.hero-mail-pattern::before {
    content: '\f0e0';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
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
    display: flex;
    align-items: center;
    gap: 16px;
}

.title-icon-wrap {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.9;
    max-width: 600px;
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

.hms-item.live {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.3);
}

.live-dot {
    width: 8px;
    height: 8px;
    background: #34D399;
    border-radius: 50%;
    box-shadow: 0 0 8px rgba(52,211,153,0.8);
    animation: liveDot 1.5s infinite;
}

.hms-item i {
    color: #C7D2FE;
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
    padding: 14px 26px;
    background: linear-gradient(135deg, #fff, #EEF2FF);
    color: #4F46E5;
    border: none;
    border-radius: var(--radius);
    font-size: 0.92rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all var(--transition);
    font-family: inherit;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.btn-hero-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.3);
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
.newsletter-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.newsletter-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.newsletter-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-indigo .stat-icon { background: linear-gradient(135deg, #4F46E5, #3730A3); }
.stat-emerald .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-amber .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }
.stat-slate .stat-icon { background: linear-gradient(135deg, #6B7280, #374151); }

.live-pulse {
    animation: livePulse 1.5s infinite;
}

@keyframes livePulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* ===== TOOLBAR ===== */
.newsletter-toolbar {
    padding: 16px 20px;
    margin-bottom: 20px;
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
    border-color: #4F46E5;
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(79,70,229,0.1);
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
    min-width: 130px;
}

[data-theme="dark"] .toolbar-select {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    color: var(--white);
}

.toolbar-select:focus {
    outline: none;
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
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
    background: #4F46E5;
    color: var(--white);
    border-color: #4F46E5;
    transform: translateY(-2px);
}

.toolbar-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

.sort-dropdown {
    position: relative;
}

.sort-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 200px;
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-100);
    padding: 6px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px);
    transition: all var(--transition);
    z-index: 50;
}

[data-theme="dark"] .sort-menu {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.sort-dropdown.open .sort-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.sort-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: var(--radius-sm);
    color: var(--dark);
    text-decoration: none;
    font-size: 0.85rem;
    transition: all var(--transition-fast);
}

[data-theme="dark"] .sort-item {
    color: var(--gray-200);
}

.sort-item:hover {
    background: var(--gray-50);
}

.sort-item.active {
    background: rgba(79,70,229,0.1);
    color: #4F46E5;
    font-weight: 600;
}

.sort-item i {
    width: 16px;
    color: var(--gray-500);
}

.sort-item.active i {
    color: #4F46E5;
}

.view-toggle {
    display: flex;
    background: var(--gray-100);
    border-radius: var(--radius);
    padding: 3px;
    gap: 2px;
}

[data-theme="dark"] .view-toggle {
    background: rgba(255,255,255,0.08);
}

.view-btn {
    width: 36px;
    height: 34px;
    border: none;
    background: transparent;
    border-radius: var(--radius-sm);
    cursor: pointer;
    color: var(--gray-600);
    transition: all var(--transition-fast);
    display: flex;
    align-items: center;
    justify-content: center;
}

.view-btn.active {
    background: var(--white);
    color: #4F46E5;
    box-shadow: var(--shadow-sm);
}

/* ===== BULK BAR ===== */
.bulk-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: linear-gradient(135deg, #3730A3, #4F46E5);
    color: var(--white);
    border-radius: var(--radius);
    margin-bottom: 16px;
    animation: slideDown 0.3s ease;
    box-shadow: var(--shadow-md);
}

.bulk-bar.show {
    display: flex;
}

.bulk-info {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
}

.bulk-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.82rem;
}

.btn-danger {
    background: #EF4444;
    color: var(--white);
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* ===== SUBSCRIBERS GRID ===== */
.subscribers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.subscriber-card {
    position: relative;
    background: var(--white);
    border-radius: var(--radius-xl);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--gray-100);
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-direction: column;
}

[data-theme="dark"] .subscriber-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.subscriber-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(79,70,229,0.3);
}

/* Card Checkbox */
.card-checkbox {
    position: absolute;
    top: 14px;
    left: 14px;
    z-index: 15;
    cursor: pointer;
    opacity: 0;
    transition: opacity var(--transition);
}

.subscriber-card:hover .card-checkbox,
.card-checkbox:has(input:checked) {
    opacity: 1;
}

.card-checkbox input {
    position: absolute;
    opacity: 0;
}

.check-mark {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: rgba(255,255,255,0.95);
    border: 2px solid var(--gray-300);
    border-radius: 6px;
    transition: all var(--transition-fast);
    backdrop-filter: blur(10px);
}

.card-checkbox input:checked + .check-mark {
    background: #4F46E5;
    border-color: #4F46E5;
}

.check-mark::after {
    content: '\f00c';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 0.7rem;
    color: var(--white);
    transform: scale(0);
    transition: transform 0.2s;
}

.card-checkbox input:checked + .check-mark::after {
    transform: scale(1);
}

/* Status Badge */
.sc-status {
    position: absolute;
    top: 14px;
    right: 14px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    z-index: 12;
}

/* Card Body */
.sc-body {
    padding: 24px 20px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.sc-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-weight: 700;
    font-size: 1.1rem;
    font-family: 'Space Grotesk', sans-serif;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.sc-info {
    flex: 1;
    min-width: 0;
}

.sc-name {
    font-size: 1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

[data-theme="dark"] .sc-name {
    color: var(--white);
}

.sc-email {
    font-size: 0.82rem;
    color: var(--gray-600);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

[data-theme="dark"] .sc-email {
    color: var(--gray-400);
}

.sc-meta {
    padding: 0 20px 16px;
}

.sc-date {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: var(--gray-500);
}

.sc-date i {
    color: #4F46E5;
    font-size: 0.7rem;
}

.sc-actions {
    display: flex;
    gap: 6px;
    padding: 14px 20px;
    border-top: 1px solid var(--gray-100);
    background: var(--gray-50);
}

[data-theme="dark"] .sc-actions {
    border-color: rgba(255,255,255,0.08);
    background: rgba(0,0,0,0.2);
}

.sc-action-btn {
    flex: 1;
    padding: 8px;
    border-radius: var(--radius-sm);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all var(--transition-fast);
    font-size: 0.85rem;
}

.sc-action-btn.success {
    background: rgba(16,185,129,0.1);
    color: #047857;
}

.sc-action-btn.success:hover {
    background: #10B981;
    color: var(--white);
}

.sc-action-btn.warning {
    background: rgba(245,158,11,0.1);
    color: #92400E;
}

.sc-action-btn.warning:hover {
    background: #F59E0B;
    color: var(--white);
}

.sc-action-btn.danger {
    background: rgba(239,68,68,0.1);
    color: #B91C1C;
}

.sc-action-btn.danger:hover {
    background: #EF4444;
    color: var(--white);
}

/* ===== EMPTY STATE ===== */
.empty-state-ult {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: var(--white);
    border-radius: var(--radius-xl);
    border: 2px dashed var(--gray-200);
}

[data-theme="dark"] .empty-state-ult {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.1);
}

.empty-illustration {
    margin-bottom: 24px;
    display: inline-block;
    animation: floatIllustration 3s ease-in-out infinite;
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

.result-info {
    text-align: center;
    padding: 12px;
    color: var(--gray-600);
    font-size: 0.85rem;
}

.result-info strong {
    color: #4F46E5;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== LIST VIEW ===== */
.subscribers-list-view .data-table {
    font-size: 0.85rem;
}

.subscribers-list-view .data-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 700;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .subscribers-list-view .data-table th {
    background: var(--dark);
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

.subscribers-list-view .data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

/* ===== SIDEBAR WIDGETS ===== */
.newsletter-sidebar {
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
    color: #4F46E5;
}

.widget-body {
    padding: 16px 18px;
}

.widget-empty {
    text-align: center;
    padding: 24px 12px;
    color: var(--gray-600);
}

.widget-empty i {
    font-size: 2rem;
    color: var(--gray-300);
    margin-bottom: 8px;
    display: block;
}

.widget-empty p {
    font-size: 0.82rem;
    margin: 0;
}

/* ===== TREND CHART ===== */
.trend-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 8px;
    height: 140px;
    padding: 0 4px;
}

.tc-bar-group {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    height: 100%;
}

.tc-bar {
    flex: 1;
    width: 100%;
    max-width: 32px;
    background: linear-gradient(180deg, #4F46E5, #818CF8);
    border-radius: 6px 6px 3px 3px;
    position: relative;
    transition: all var(--transition);
    animation: barGrow 0.8s ease-out;
}

.tc-bar:hover {
    background: linear-gradient(180deg, #4338CA, #6366F1);
    box-shadow: 0 4px 12px rgba(79,70,229,0.3);
}

.tc-value {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.68rem;
    font-weight: 700;
    color: #4F46E5;
    font-family: 'Space Grotesk', sans-serif;
}

.tc-label {
    font-size: 0.68rem;
    color: var(--gray-500);
    font-weight: 600;
}

/* ===== STATUS DONUT ===== */
.status-donut {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 0 auto 16px;
}

.donut-ring {
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
    font-size: 1.8rem;
    font-weight: 800;
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

.status-legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.sl-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
}

.sl-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.sl-name {
    flex: 1;
    color: var(--dark);
    font-weight: 500;
}

[data-theme="dark"] .sl-name {
    color: var(--gray-200);
}

.sl-count {
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .sl-count {
    color: var(--white);
}

/* ===== QUICK ACTIONS ===== */
.qa-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: var(--radius);
    background: var(--gray-50);
    color: var(--dark);
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 500;
    transition: all var(--transition);
    margin-bottom: 8px;
    cursor: pointer;
    border: none;
    width: 100%;
    text-align: left;
    font-family: inherit;
}

[data-theme="dark"] .qa-btn {
    background: rgba(255,255,255,0.04);
    color: var(--gray-200);
}

.qa-btn:hover {
    background: var(--gray-100);
    transform: translateX(4px);
}

.qa-btn.primary {
    background: linear-gradient(135deg, #4F46E5, #3730A3);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(79,70,229,0.3);
}

.qa-btn.primary:hover {
    box-shadow: 0 6px 16px rgba(79,70,229,0.4);
}

.qa-btn i:first-child {
    color: #4F46E5;
    width: 16px;
}

.qa-btn.primary i:first-child {
    color: var(--white);
}

.qa-btn span {
    flex: 1;
}

.qa-arrow {
    color: var(--gray-400);
    font-size: 0.72rem;
    opacity: 0;
    transition: opacity var(--transition-fast);
}

.qa-btn:hover .qa-arrow {
    opacity: 1;
}

/* ===== TIPS ===== */
.tips-card {
    background: linear-gradient(135deg, rgba(79,70,229,0.04), rgba(129,140,248,0.04));
}

.tip-item {
    display: flex;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
    align-items: flex-start;
}

[data-theme="dark"] .tip-item {
    border-color: rgba(255,255,255,0.05);
}

.tip-item:last-child {
    border-bottom: none;
}

.tip-icon {
    width: 28px;
    height: 28px;
    border-radius: var(--radius-sm);
    background: rgba(16,185,129,0.12);
    color: #10B981;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    flex-shrink: 0;
    margin-top: 1px;
}

.tip-text {
    font-size: 0.82rem;
    color: var(--gray-700);
    line-height: 1.5;
    flex: 1;
}

[data-theme="dark"] .tip-text {
    color: var(--gray-300);
}

/* ===== MODALS ===== */
.newsletter-modal-head {
    background: linear-gradient(135deg, #312E81 0%, #4F46E5 60%, #6366F1 100%) !important;
}

.upload-large {
    position: relative;
    border: 2px dashed var(--gray-300);
    border-radius: var(--radius-lg);
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all var(--transition);
    background: var(--white);
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
}

[data-theme="dark"] .upload-large {
    border-color: rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.03);
}

.upload-large:hover {
    border-color: #4F46E5;
    background: rgba(79,70,229,0.04);
}

.upload-placeholder-large {
    color: var(--gray-600);
}

.upload-icon-large {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, rgba(79,70,229,0.15), rgba(99,102,241,0.1));
    color: #4F46E5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 14px;
    box-shadow: 0 8px 20px rgba(79,70,229,0.2);
}

.upload-placeholder-large h4 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 4px;
}

[data-theme="dark"] .upload-placeholder-large h4 {
    color: var(--white);
}

.upload-placeholder-large p {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 12px;
}

.upload-note {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500);
    font-style: italic;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .newsletter-layout {
        grid-template-columns: 1fr;
    }
    
    .newsletter-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .newsletter-page {
        padding: 16px;
    }
    
    .newsletter-hero {
        padding: 24px 20px;
    }
    
    .hero-title {
        font-size: 1.6rem;
    }
    
    .title-icon-wrap {
        width: 44px;
        height: 44px;
        font-size: 1.2rem;
    }
    
    .hero-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .hero-actions {
        width: 100%;
    }
    
    .hero-actions .btn-hero-primary {
        flex: 1;
    }
    
    .newsletter-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .toolbar-left,
    .toolbar-right {
        width: 100%;
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
    
    .toolbar-right {
        flex-direction: row;
        justify-content: space-between;
    }
    
    .subscribers-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .newsletter-hero,
    .newsletter-sidebar,
    .newsletter-toolbar,
    .bulk-bar,
    .sc-actions,
    .card-checkbox {
        display: none !important;
    }
    
    .newsletter-layout {
        grid-template-columns: 1fr;
    }
    
    .subscriber-card {
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
        const duration = 1200;
        const start = performance.now();
        
        const step = (now) => {
            const p = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            const current = Math.floor(target * eased);
            el.textContent = current.toLocaleString('id-ID');
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

    /* ===== MODAL FUNCTIONS ===== */
    window.openImportModal = function() {
        document.getElementById('importModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closeImportModal = function() {
        document.getElementById('importModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.openBroadcastModal = function() {
        document.getElementById('broadcastModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closeBroadcastModal = function() {
        document.getElementById('broadcastModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    filterStatus?.addEventListener('change', applyFilters);

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const status = filterStatus?.value;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'status', 'sort', 'dir'].forEach(p => url.searchParams.delete(p));
        window.location = url;
    };

    /* ===== SORT DROPDOWN ===== */
    const sortBtn = document.getElementById('sortBtn');
    const sortMenu = document.querySelector('.sort-dropdown');
    
    sortBtn?.addEventListener('click', function(e) {
        e.stopPropagation();
        sortMenu.classList.toggle('open');
    });

    document.addEventListener('click', () => {
        sortMenu?.classList.remove('open');
    });

    /* ===== VIEW TOGGLE ===== */
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const view = this.dataset.view;
            const grid = document.getElementById('subscribersGrid');
            const list = document.getElementById('subscribersList');
            
            if (view === 'list') {
                grid.style.display = 'none';
                list.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                list.style.display = 'none';
            }
            
            localStorage.setItem('newsletter_view', view);
        });
    });

    const savedView = localStorage.getItem('newsletter_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');

    document.querySelectorAll('.sub-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
        });
    });

    window.toggleSelectAll = function(checkbox) {
        document.querySelectorAll('.sub-check').forEach(c => {
            c.checked = checkbox.checked;
            const id = c.value;
            if (checkbox.checked) selectedIds.add(id);
            else selectedIds.delete(id);
        });
        updateBulkBar();
    };

    document.getElementById('selectAllTable')?.addEventListener('change', function() {
        toggleSelectAll(this);
    });

    function updateBulkBar() {
        if (selectedIds.size > 0) {
            bulkBar.classList.add('show');
            selectedCountEl.textContent = selectedIds.size;
        } else {
            bulkBar.classList.remove('show');
        }
    }

    window.clearSelection = function() {
        selectedIds.clear();
        document.querySelectorAll('.sub-check').forEach(c => c.checked = false);
        const selectAll = document.getElementById('selectAll');
        const selectAllTable = document.getElementById('selectAllTable');
        if (selectAll) selectAll.checked = false;
        if (selectAllTable) selectAllTable.checked = false;
        updateBulkBar();
    };

    /* ===== EXPORT CSV ===== */
    window.exportSubscribers = function() {
        const subscribers = <?= json_encode($subscribers) ?>;
        
        if (!subscribers || subscribers.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['Email', 'Nama', 'Status', 'Tanggal Daftar'];
        const rows = subscribers.map(s => [
            `"${(s.email || '').replace(/"/g, '""')}"`,
            `"${(s.nama || '').replace(/"/g, '""')}"`,
            s.status || '',
            s.created_at || ''
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `newsletter-subscribers-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${subscribers.length} subscriber berhasil di-export`, 'success');
        }, 500);
    };

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        // / = Focus search
        if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            searchInput?.focus();
        }
        
        // Escape = Close modals
        if (e.key === 'Escape') {
            closeImportModal();
            closeBroadcastModal();
        }
    });

    /* ===== MODAL BACKDROP CLICK ===== */
    ['importModal', 'broadcastModal'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
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

    console.log('%c📧 Newsletter v4.0 Loaded', 'color:#4F46E5;font-weight:bold;font-size:14px');
    console.log('%c   Features: Bulk Actions, Import/Export, Broadcast, Trend Chart', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>