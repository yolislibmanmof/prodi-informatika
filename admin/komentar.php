<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Komentar';

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
            $db->prepare("DELETE FROM comments WHERE id = ?")->execute([$id]);
            setFlash('success', 'Komentar berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus komentar');
        }
        header('Location: komentar.php');
        exit;
    }
    
    if ($_GET['action'] === 'approve' && $id) {
        try {
            $db->prepare("UPDATE comments SET status = 'approved', updated_at = NOW() WHERE id = ?")->execute([$id]);
            setFlash('success', 'Komentar berhasil disetujui');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menyetujui komentar');
        }
        header('Location: komentar.php');
        exit;
    }
    
    if ($_GET['action'] === 'spam' && $id) {
        try {
            $db->prepare("UPDATE comments SET status = 'spam', updated_at = NOW() WHERE id = ?")->execute([$id]);
            setFlash('success', 'Komentar ditandai sebagai spam');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menandai spam');
        }
        header('Location: komentar.php');
        exit;
    }
    
    // Bulk actions
    if (in_array($_GET['action'], ['bulk-approve', 'bulk-spam', 'bulk-delete']) && !empty($_POST['selected'])) {
        try {
            $ids = array_map('intval', $_POST['selected']);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            if ($_GET['action'] === 'bulk-delete') {
                $db->prepare("DELETE FROM comments WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' komentar berhasil dihapus');
            } elseif ($_GET['action'] === 'bulk-approve') {
                $db->prepare("UPDATE comments SET status = 'approved', updated_at = NOW() WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' komentar berhasil disetujui');
            } elseif ($_GET['action'] === 'bulk-spam') {
                $db->prepare("UPDATE comments SET status = 'spam', updated_at = NOW() WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' komentar ditandai sebagai spam');
            }
        } catch (Throwable $e) {
            setFlash('error', 'Gagal melakukan aksi massal');
        }
        header('Location: komentar.php');
        exit;
    }
}

/* ===== HANDLE REPLY ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply' && $db) {
    try {
        $parentId = (int)$_POST['parent_id'];
        $beritaId = (int)$_POST['berita_id'];
        $balasan = clean($_POST['balasan']);
        
        if ($balasan && $parentId && $beritaId) {
            $stmt = $db->prepare("INSERT INTO comments (berita_id, parent_id, nama, email, komentar, status, created_at) VALUES (?, ?, ?, ?, ?, 'approved', NOW())");
            $stmt->execute([$beritaId, $parentId, 'Admin', 'admin@informatika.ac.id', $balasan]);
            setFlash('success', 'Balasan berhasil dikirim');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal mengirim balasan');
    }
    header('Location: komentar.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$filterStatus = $_GET['status'] ?? '';
$searchQuery = $_GET['q'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$sortDir = $_GET['dir'] ?? 'DESC';

$where = "1=1";
$params = [];

if ($filterStatus) { $where .= " AND c.status = ?"; $params[] = $filterStatus; }
if ($searchQuery) { 
    $searchParam = "%$searchQuery%";
    $where .= " AND (c.nama LIKE ? OR c.email LIKE ? OR c.komentar LIKE ? OR b.judul LIKE ?)"; 
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$validSorts = ['created_at', 'nama', 'status'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'created_at';
$sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

/* ===== FETCH DATA ===== */
$commentsList = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'spam' => 0,
];

if ($db) {
    try {
        $sql = "SELECT c.*, b.judul as berita_judul 
                FROM comments c 
                LEFT JOIN berita b ON c.berita_id = b.id 
                WHERE $where 
                ORDER BY c.$sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $commentsList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
        $stats['pending'] = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn();
        $stats['approved'] = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status='approved'")->fetchColumn();
        $stats['spam'] = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status='spam'")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== RECENT ARTICLES WITH COMMENTS ===== */
$recentArticles = [];
if ($db) {
    try {
        $recentArticles = $db->query("
            SELECT b.id, b.judul, COUNT(c.id) as comment_count
            FROM berita b
            LEFT JOIN comments c ON b.id = c.berita_id
            GROUP BY b.id, b.judul
            HAVING comment_count > 0
            ORDER BY comment_count DESC
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content komentar-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="komentar-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-comment-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-comments"></i>
                    <span>Community Moderation</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-shield-alt"></i>
                    </span>
                    Kelola Komentar
                </h1>
                <p class="hero-subtitle">
                    Moderasi dan balas komentar pengunjung pada artikel website
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-comments"></i>
                        <span><strong><?= number_format($stats['total']) ?></strong> Total</span>
                    </div>
                    <div class="hms-item pending">
                        <span class="live-dot"></span>
                        <span><strong><?= number_format($stats['pending']) ?></strong> Pending</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-check-circle"></i>
                        <span><strong><?= number_format($stats['approved']) ?></strong> Approved</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportComments()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
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
    <div class="komentar-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="komentar-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid komentar-stats">
                <div class="stat-card stat-amber">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['pending'] ?>">0</h3>
                        <p>Pending Review</p>
                        <span class="stat-trend up"><i class="fas fa-hourglass-half"></i> Needs Action</span>
                    </div>
                </div>

                <div class="stat-card stat-emerald">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['approved'] ?>">0</h3>
                        <p>Approved</p>
                        <span class="stat-trend up"><i class="fas fa-check"></i> Published</span>
                    </div>
                </div>

                <div class="stat-card stat-rose">
                    <div class="stat-icon"><i class="fas fa-ban"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['spam'] ?>">0</h3>
                        <p>Spam</p>
                        <span class="stat-trend down"><i class="fas fa-shield-alt"></i> Blocked</span>
                    </div>
                </div>

                <div class="stat-card stat-indigo">
                    <div class="stat-icon"><i class="fas fa-comments"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Komentar</p>
                        <span class="stat-trend up"><i class="fas fa-chart-line"></i> All Time</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card komentar-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama, email, atau isi komentar..." value="<?= clean($searchQuery) ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterStatus" class="toolbar-select">
                            <option value="">📊 Semua Status</option>
                            <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>🟡 Pending</option>
                            <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>🟢 Approved</option>
                            <option value="spam" <?= $filterStatus === 'spam' ? 'selected' : '' ?>>🔴 Spam</option>
                        </select>
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
                            <a href="?sort=nama&dir=ASC" class="sort-item <?= $sortBy === 'nama' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Nama A-Z
                            </a>
                        </div>
                    </div>
                    
                    <button class="toolbar-icon-btn" onclick="resetFilters()" title="Reset Filter">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
            </div>

            <!-- Bulk Actions Bar -->
            <form method="POST" id="bulkForm">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="bulk-bar" id="bulkBar">
                <div class="bulk-info">
                    <i class="fas fa-check-circle"></i>
                    <span><strong id="selectedCount">0</strong> komentar dipilih</span>
                </div>
                <div class="bulk-actions">
                    <button type="submit" formaction="?action=bulk-approve" class="btn btn-sm btn-success" onclick="return confirm('Setujui komentar yang dipilih?')">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button type="submit" formaction="?action=bulk-spam" class="btn btn-sm btn-warning" onclick="return confirm('Tandai sebagai spam?')">
                        <i class="fas fa-ban"></i> Spam
                    </button>
                    <button type="submit" formaction="?action=bulk-delete" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus komentar yang dipilih?')">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        Batal
                    </button>
                </div>
            </div>

            <!-- Comments List -->
            <div class="comments-list-container">
                <?php if (!empty($commentsList)): ?>
                    <div class="comments-header-row">
                        <label class="select-all-label">
                            <input type="checkbox" id="selectAllComments" onchange="toggleSelectAll(this)">
                            <span>Pilih Semua</span>
                        </label>
                        <span class="results-count">Menampilkan <strong><?= count($commentsList) ?></strong> komentar</span>
                    </div>
                    
                    <div class="comments-list-ult">
                        <?php foreach ($commentsList as $c): 
                            $statusColors = [
                                'pending' => ['bg' => 'rgba(245,158,11,0.12)', 'color' => '#92400E', 'icon' => 'fa-clock', 'label' => 'Pending'],
                                'approved' => ['bg' => 'rgba(16,185,129,0.12)', 'color' => '#047857', 'icon' => 'fa-check-circle', 'label' => 'Approved'],
                                'spam' => ['bg' => 'rgba(239,68,68,0.12)', 'color' => '#991B1B', 'icon' => 'fa-ban', 'label' => 'Spam']
                            ];
                            $st = $statusColors[$c['status']] ?? $statusColors['pending'];
                            $initial = strtoupper(substr($c['nama'] ?? 'A', 0, 1));
                            $colors = ['#F59E0B', '#10B981', '#EF4444', '#3B82F6', '#8B5CF6', '#06B6D4'];
                            $avatarColor = $colors[ord($initial) % count($colors)];
                            $isReply = $c['parent_id'] > 0;
                        ?>
                        <div class="comment-card-ult <?= $isReply ? 'is-reply' : '' ?>" data-status="<?= $c['status'] ?>" style="border-left-color: <?= $st['color'] ?>">
                            <!-- Checkbox -->
                            <div class="cc-checkbox">
                                <input type="checkbox" name="selected[]" class="comment-check" value="<?= $c['id'] ?>">
                            </div>
                            
                            <div class="cc-main">
                                <div class="cc-header">
                                    <div class="cc-user">
                                        <div class="cc-avatar" style="background: <?= $avatarColor ?>"><?= $initial ?></div>
                                        <div class="cc-info">
                                            <div class="cc-name">
                                                <?= clean($c['nama']) ?>
                                                <?php if ($c['email'] === 'admin@informatika.ac.id'): ?>
                                                <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="cc-email"><?= clean($c['email']) ?></div>
                                        </div>
                                    </div>
                                    <span class="cc-status-badge" style="background: <?= $st['bg'] ?>; color: <?= $st['color'] ?>">
                                        <i class="fas <?= $st['icon'] ?>"></i>
                                        <?= $st['label'] ?>
                                    </span>
                                </div>
                                
                                <div class="cc-body">
                                    <?php if ($isReply): ?>
                                    <div class="cc-reply-indicator">
                                        <i class="fas fa-reply"></i> Balasan Admin
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="cc-article">
                                        <i class="fas fa-newspaper"></i> 
                                        <span>Pada artikel:</span>
                                        <strong><?= clean($c['berita_judul'] ?: 'Artikel dihapus') ?></strong>
                                    </div>
                                    
                                    <p class="cc-text"><?= nl2br(clean($c['komentar'])) ?></p>
                                    
                                    <div class="cc-meta">
                                        <span><i class="fas fa-clock"></i> <?= function_exists('formatTanggal') ? formatTanggal($c['created_at']) : date('d M Y H:i', strtotime($c['created_at'])) ?></span>
                                        <?php if ($c['ip_address']): ?>
                                        <span><i class="fas fa-globe"></i> <?= clean($c['ip_address']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="cc-actions">
                                    <?php if ($c['status'] === 'pending'): ?>
                                    <a href="?action=approve&id=<?= $c['id'] ?>" class="cc-btn success" title="Setujui">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <a href="?action=spam&id=<?= $c['id'] ?>" class="cc-btn warning" title="Tandai Spam">
                                        <i class="fas fa-ban"></i> Spam
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!$isReply): ?>
                                    <button type="button" class="cc-btn primary" onclick='openReply(<?= htmlspecialchars(json_encode($c, JSON_HEX_APOS)) ?>)' title="Balas">
                                        <i class="fas fa-reply"></i> Balas
                                    </button>
                                    <?php endif; ?>
                                    
                                    <a href="?action=delete&id=<?= $c['id'] ?>" class="cc-btn danger" data-confirm="Yakin hapus komentar ini?" title="Hapus">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration">
                            <svg viewBox="0 0 200 150" width="200" height="150">
                                <ellipse cx="100" cy="130" rx="70" ry="10" fill="var(--gray-200)" opacity="0.5"/>
                                <rect x="40" y="40" width="120" height="70" rx="8" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <circle cx="70" cy="75" r="4" fill="var(--gray-300)"/>
                                <circle cx="85" cy="75" r="4" fill="var(--gray-300)"/>
                                <circle cx="100" cy="75" r="4" fill="var(--gray-300)"/>
                                <rect x="40" y="90" width="80" height="6" rx="3" fill="var(--gray-200)"/>
                                <circle cx="155" cy="45" r="18" fill="#F59E0B" opacity="0.9"/>
                                <path d="M 148 45 L 162 45 M 155 38 L 155 52" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Belum ada komentar</h3>
                        <p>Komentar pengunjung akan muncul di sini untuk dimoderasi</p>
                        <div class="empty-actions">
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            </form>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="komentar-sidebar">
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
                                ['value' => $stats['pending'], 'color' => '#F59E0B'],
                                ['value' => $stats['approved'], 'color' => '#10B981'],
                                ['value' => $stats['spam'], 'color' => '#EF4444']
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
                            <span class="sl-dot" style="background: #F59E0B"></span>
                            <span class="sl-name">Pending</span>
                            <span class="sl-count"><?= $stats['pending'] ?></span>
                        </div>
                        <div class="sl-item">
                            <span class="sl-dot" style="background: #10B981"></span>
                            <span class="sl-name">Approved</span>
                            <span class="sl-count"><?= $stats['approved'] ?></span>
                        </div>
                        <div class="sl-item">
                            <span class="sl-dot" style="background: #EF4444"></span>
                            <span class="sl-name">Spam</span>
                            <span class="sl-count"><?= $stats['spam'] ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Articles -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-fire"></i> Artikel Populer</h3>
                    <span class="widget-badge">By Comments</span>
                </div>
                <div class="widget-body">
                    <?php if (!empty($recentArticles)): ?>
                        <?php foreach ($recentArticles as $i => $art): 
                            $maxComments = max(array_column($recentArticles, 'comment_count')) ?: 1;
                            $pct = round(($art['comment_count'] / $maxComments) * 100);
                            $colors = ['#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899'];
                            $color = $colors[$i % count($colors)];
                        ?>
                        <div class="article-item">
                            <div class="ai-rank">#<?= $i + 1 ?></div>
                            <div class="ai-info">
                                <div class="ai-title"><?= clean(mb_substr($art['judul'], 0, 40)) ?><?= strlen($art['judul']) > 40 ? '...' : '' ?></div>
                                <div class="ai-bar">
                                    <div class="ai-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                                </div>
                            </div>
                            <div class="ai-count"><?= $art['comment_count'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-fire"></i>
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
                    <a href="berita.php" class="qa-btn">
                        <i class="fas fa-newspaper"></i>
                        <span>Kelola Berita</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="analytics.php" class="qa-btn">
                        <i class="fas fa-chart-line"></i>
                        <span>Analitik Website</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Moderation Tips -->
            <div class="card widget-card tips-card">
                <div class="widget-header">
                    <h3><i class="fas fa-lightbulb"></i> Tips Moderasi</h3>
                </div>
                <div class="widget-body">
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Balas komentar dengan sopan dan profesional</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Tandai spam untuk melatih filter otomatis</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="tip-text">Hapus komentar yang mengandung SARA atau kekerasan</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== REPLY MODAL ===== -->
<div class="modal" id="replyModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header modal-header-gradient komentar-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-reply"></i></div>
                <div>
                    <h3>Balas Komentar</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px" id="replyTo">Membalas komentar dari...</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeReply()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" class="modal-body">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="parent_id" id="replyParentId">
            <input type="hidden" name="berita_id" id="replyBeritaId">
            
            <div class="form-section">
                <div class="form-group">
                    <label class="field-label">Balasan Admin <span class="required">*</span></label>
                    <div class="textarea-wrap">
                        <textarea name="balasan" id="balasan" rows="6" required placeholder="Tulis balasan Anda di sini..."></textarea>
                    </div>
                    <small class="form-hint">Balasan akan langsung disetujui dan ditampilkan di bawah komentar asli.</small>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeReply()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Kirim Balasan
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* ============================================================
   KOMENTAR PAGE - COMMENT MODERATOR EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.komentar-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.komentar-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #78350F 0%, #D97706 50%, #F59E0B 100%);
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
    background: radial-gradient(circle, #FCD34D, transparent);
    top: -120px; right: 8%;
}

.orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, #FBBF24, transparent);
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

.hero-comment-pattern {
    position: absolute;
    top: 50%;
    right: 5%;
    transform: translateY(-50%);
    font-size: 18rem;
    color: rgba(255,255,255,0.04);
    pointer-events: none;
}

.hero-comment-pattern::before {
    content: '\f086';
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

.hms-item.pending {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.3);
}

.live-dot {
    width: 8px;
    height: 8px;
    background: #FCD34D;
    border-radius: 50%;
    box-shadow: 0 0 8px rgba(252,211,77,0.8);
    animation: liveDot 1.5s infinite;
}

.hms-item i {
    color: #FEF3C7;
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
    background: linear-gradient(135deg, #10B981, #047857);
    color: var(--white);
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
    box-shadow: 0 8px 20px rgba(16,185,129,0.4);
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
.komentar-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.komentar-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.komentar-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-amber .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }
.stat-emerald .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-rose .stat-icon { background: linear-gradient(135deg, #F43F5E, #BE123C); }
.stat-indigo .stat-icon { background: linear-gradient(135deg, #4F46E5, #3730A3); }

/* ===== TOOLBAR ===== */
.komentar-toolbar {
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
    border-color: #F59E0B;
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(245,158,11,0.1);
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
    border-color: #F59E0B;
    box-shadow: 0 0 0 3px rgba(245,158,11,0.1);
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
    background: #F59E0B;
    color: var(--white);
    border-color: #F59E0B;
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
    background: rgba(245,158,11,0.1);
    color: #B45309;
    font-weight: 600;
}

.sort-item i {
    width: 16px;
    color: var(--gray-500);
}

.sort-item.active i {
    color: #F59E0B;
}

/* ===== BULK BAR ===== */
.bulk-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: linear-gradient(135deg, #B45309, #F59E0B);
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

.btn-success {
    background: #10B981;
    color: var(--white);
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-warning {
    background: #F59E0B;
    color: var(--white);
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
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

/* ===== COMMENTS LIST ===== */
.comments-list-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.comments-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: var(--white);
    border-radius: var(--radius);
    border: 1px solid var(--gray-100);
}

[data-theme="dark"] .comments-header-row {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.select-all-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--dark);
    cursor: pointer;
}

[data-theme="dark"] .select-all-label {
    color: var(--white);
}

.results-count {
    font-size: 0.85rem;
    color: var(--gray-600);
}

.results-count strong {
    color: #F59E0B;
    font-family: 'Space Grotesk', sans-serif;
}

.comments-list-ult {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.comment-card-ult {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: 20px;
    border: 1px solid var(--gray-100);
    border-left: 4px solid var(--gray-300);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    gap: 16px;
}

[data-theme="dark"] .comment-card-ult {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.comment-card-ult:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    transform: translateY(-2px);
}

.comment-card-ult.is-reply {
    background: rgba(79,70,229,0.03);
    border-left-color: #4F46E5 !important;
    margin-left: 40px;
}

[data-theme="dark"] .comment-card-ult.is-reply {
    background: rgba(79,70,229,0.08);
}

.cc-checkbox {
    padding-top: 4px;
}

.cc-checkbox input {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #F59E0B;
}

.cc-main {
    flex: 1;
    min-width: 0;
}

.cc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 10px;
}

.cc-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.cc-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1rem;
    font-family: 'Space Grotesk', sans-serif;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.cc-info {
    display: flex;
    flex-direction: column;
}

.cc-name {
    font-weight: 700;
    color: var(--dark);
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

[data-theme="dark"] .cc-name {
    color: var(--white);
}

.admin-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: rgba(79,70,229,0.1);
    color: #4F46E5;
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
}

.cc-email {
    font-size: 0.82rem;
    color: var(--gray-600);
}

[data-theme="dark"] .cc-email {
    color: var(--gray-400);
}

.cc-status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.cc-body {
    margin-bottom: 14px;
}

.cc-reply-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: rgba(79,70,229,0.1);
    color: #4F46E5;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 600;
    margin-bottom: 10px;
}

.cc-article {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: var(--gray-50);
    border-radius: 8px;
    font-size: 0.82rem;
    color: var(--gray-600);
    margin-bottom: 12px;
}

[data-theme="dark"] .cc-article {
    background: rgba(255,255,255,0.05);
    color: var(--gray-400);
}

.cc-article i {
    color: #F59E0B;
}

.cc-article strong {
    color: var(--dark);
    margin-left: 4px;
}

[data-theme="dark"] .cc-article strong {
    color: var(--white);
}

.cc-text {
    font-size: 0.95rem;
    line-height: 1.6;
    color: var(--dark);
    margin-bottom: 12px;
    white-space: pre-wrap;
}

[data-theme="dark"] .cc-text {
    color: var(--gray-200);
}

.cc-meta {
    display: flex;
    gap: 16px;
    font-size: 0.78rem;
    color: var(--gray-600);
    flex-wrap: wrap;
}

.cc-meta i {
    color: #F59E0B;
    margin-right: 4px;
}

.cc-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    padding-top: 14px;
    border-top: 1px solid var(--gray-100);
}

[data-theme="dark"] .cc-actions {
    border-color: rgba(255,255,255,0.08);
}

.cc-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    font-family: inherit;
}

.cc-btn.success {
    background: rgba(16,185,129,0.12);
    color: #047857;
}

.cc-btn.success:hover {
    background: #10B981;
    color: var(--white);
}

.cc-btn.warning {
    background: rgba(245,158,11,0.12);
    color: #92400E;
}

.cc-btn.warning:hover {
    background: #F59E0B;
    color: var(--white);
}

.cc-btn.primary {
    background: rgba(79,70,229,0.12);
    color: #4338CA;
}

.cc-btn.primary:hover {
    background: #4F46E5;
    color: var(--white);
}

.cc-btn.danger {
    background: rgba(239,68,68,0.12);
    color: #991B1B;
}

.cc-btn.danger:hover {
    background: #EF4444;
    color: var(--white);
}

/* ===== EMPTY STATE ===== */
.empty-state-ult {
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

/* ===== SIDEBAR WIDGETS ===== */
.komentar-sidebar {
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
    color: #F59E0B;
}

.widget-badge {
    padding: 3px 10px;
    background: #F59E0B;
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

/* ===== ARTICLE ITEMS ===== */
.article-item {
    display: grid;
    grid-template-columns: 24px 1fr auto;
    gap: 10px;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .article-item {
    border-color: rgba(255,255,255,0.05);
}

.article-item:last-child {
    border-bottom: none;
}

.ai-rank {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--gray-400);
    text-align: center;
    font-family: 'Space Grotesk', sans-serif;
}

.article-item:nth-child(1) .ai-rank { color: #F59E0B; font-size: 1rem; }
.article-item:nth-child(2) .ai-rank { color: #94A3B8; }
.article-item:nth-child(3) .ai-rank { color: #CD7F32; }

.ai-info {
    min-width: 0;
}

.ai-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

[data-theme="dark"] .ai-title {
    color: var(--gray-200);
}

.ai-bar {
    height: 4px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .ai-bar {
    background: rgba(255,255,255,0.08);
}

.ai-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

.ai-count {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .ai-count {
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

.qa-btn i:first-child {
    color: #F59E0B;
    width: 16px;
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
    background: linear-gradient(135deg, rgba(245,158,11,0.04), rgba(180,83,9,0.04));
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
    background: rgba(245,158,11,0.12);
    color: #F59E0B;
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

/* ===== MODAL ===== */
.komentar-modal-head {
    background: linear-gradient(135deg, #78350F 0%, #D97706 60%, #F59E0B 100%) !important;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .komentar-layout {
        grid-template-columns: 1fr;
    }
    
    .komentar-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .komentar-page {
        padding: 16px;
    }
    
    .komentar-hero {
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
    
    .komentar-toolbar {
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
    
    .comment-card-ult {
        flex-direction: column;
    }
    
    .cc-checkbox {
        position: absolute;
        top: 20px;
        right: 20px;
    }
    
    .comment-card-ult {
        position: relative;
        padding-right: 50px;
    }
    
    .comment-card-ult.is-reply {
        margin-left: 20px;
    }
}

@media print {
    .komentar-hero,
    .komentar-sidebar,
    .komentar-toolbar,
    .bulk-bar,
    .cc-actions,
    .cc-checkbox {
        display: none !important;
    }
    
    .komentar-layout {
        grid-template-columns: 1fr;
    }
    
    .comment-card-ult {
        break-inside: avoid;
    }
}
</style>

<script>
(function() {
    'use strict';

    const allComments = <?= json_encode($commentsList) ?>;

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
    window.openReply = function(data) {
        document.getElementById('replyParentId').value = data.id;
        document.getElementById('replyBeritaId').value = data.berita_id;
        document.getElementById('replyTo').textContent = 'Membalas komentar dari ' + data.nama;
        document.getElementById('balasan').value = '';
        document.getElementById('replyModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('balasan')?.focus(), 300);
    };

    window.closeReply = function() {
        document.getElementById('replyModal').classList.remove('active');
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

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');
    const selectAllCheckbox = document.getElementById('selectAllComments');

    document.querySelectorAll('.comment-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
            updateSelectAllState();
        });
    });

    window.toggleSelectAll = function(checkbox) {
        document.querySelectorAll('.comment-check').forEach(c => {
            c.checked = checkbox.checked;
            const id = c.value;
            if (checkbox.checked) selectedIds.add(id);
            else selectedIds.delete(id);
        });
        updateBulkBar();
    };

    function updateSelectAllState() {
        const allChecks = document.querySelectorAll('.comment-check');
        const checkedChecks = document.querySelectorAll('.comment-check:checked');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = allChecks.length > 0 && allChecks.length === checkedChecks.length;
            selectAllCheckbox.indeterminate = checkedChecks.length > 0 && checkedChecks.length < allChecks.length;
        }
    }

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
        document.querySelectorAll('.comment-check').forEach(c => c.checked = false);
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
        updateBulkBar();
    };

    /* ===== EXPORT CSV ===== */
    window.exportComments = function() {
        if (!allComments || allComments.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['Nama', 'Email', 'Komentar', 'Artikel', 'Status', 'IP Address', 'Tanggal'];
        const rows = allComments.map(c => [
            `"${(c.nama || '').replace(/"/g, '""')}"`,
            `"${(c.email || '').replace(/"/g, '""')}"`,
            `"${(c.komentar || '').replace(/"/g, '""').replace(/\n/g, ' ')}"`,
            `"${(c.berita_judul || '').replace(/"/g, '""')}"`,
            c.status || '',
            c.ip_address || '',
            c.created_at || ''
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `komentar-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${allComments.length} komentar berhasil di-export`, 'success');
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
            closeReply();
        }
    });

    /* ===== MODAL BACKDROP CLICK ===== */
    document.getElementById('replyModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeReply();
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

    console.log('%c💬 Komentar v4.0 Loaded', 'color:#F59E0B;font-weight:bold;font-size:14px');
    console.log('%c   Features: Bulk Actions, Reply Thread, Export CSV, Article Stats', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>