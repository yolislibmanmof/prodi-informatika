<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Pengumuman';

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
            $db->prepare("DELETE FROM pengumuman WHERE id = ?")->execute([$id]);
            setFlash('success', 'Pengumuman berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus pengumuman');
        }
        header('Location: pengumuman.php');
        exit;
    }
    
    if ($_GET['action'] === 'toggle' && $id) {
        try {
            $db->prepare("UPDATE pengumuman SET status = IF(status='aktif','nonaktif','aktif'), updated_at = NOW() WHERE id = ?")->execute([$id]);
            setFlash('success', 'Status pengumuman berhasil diubah');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mengubah status');
        }
        header('Location: pengumuman.php');
        exit;
    }
    
    // Bulk actions
    if (in_array($_GET['action'], ['bulk-activate', 'bulk-deactivate', 'bulk-delete']) && !empty($_GET['ids'])) {
        try {
            $ids = array_map('intval', explode(',', $_GET['ids']));
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            if ($_GET['action'] === 'bulk-delete') {
                $db->prepare("DELETE FROM pengumuman WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' pengumuman berhasil dihapus');
            } elseif ($_GET['action'] === 'bulk-activate') {
                $db->prepare("UPDATE pengumuman SET status = 'aktif', updated_at = NOW() WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' pengumuman berhasil diaktifkan');
            } elseif ($_GET['action'] === 'bulk-deactivate') {
                $db->prepare("UPDATE pengumuman SET status = 'nonaktif', updated_at = NOW() WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' pengumuman dinonaktifkan');
            }
        } catch (Throwable $e) {
            setFlash('error', 'Gagal melakukan bulk action');
        }
        header('Location: pengumuman.php');
        exit;
    }
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $judul = clean($_POST['judul']);
        $konten = clean($_POST['konten']);
        $prioritas = clean($_POST['prioritas']);
        $status = clean($_POST['status']);
        $tanggal_mulai = !empty($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : null;
        $tanggal_berakhir = !empty($_POST['tanggal_berakhir']) ? $_POST['tanggal_berakhir'] : null;
        
        if ($id) {
            $stmt = $db->prepare("UPDATE pengumuman SET judul=?, konten=?, prioritas=?, status=?, tanggal_mulai=?, tanggal_berakhir=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$judul, $konten, $prioritas, $status, $tanggal_mulai, $tanggal_berakhir, $id]);
            setFlash('success', 'Pengumuman berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO pengumuman (judul, konten, prioritas, status, tanggal_mulai, tanggal_berakhir, created_at) VALUES (?,?,?,?,?,?,NOW())");
            $stmt->execute([$judul, $konten, $prioritas, $status, $tanggal_mulai, $tanggal_berakhir]);
            setFlash('success', 'Pengumuman baru berhasil ditambahkan');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan data: ' . $e->getMessage());
    }
    header('Location: pengumuman.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$search = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
$filterStatus = $_GET['status'] ?? '';
$filterPrioritas = $_GET['prioritas'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$sortDir = $_GET['dir'] ?? 'DESC';

$where = "1=1";
$params = [];

if ($filterStatus) { $where .= " AND status = ?"; $params[] = $filterStatus; }
if ($filterPrioritas) { $where .= " AND prioritas = ?"; $params[] = $filterPrioritas; }
if (isset($_GET['q']) && $_GET['q']) { 
    $where .= " AND (judul LIKE ? OR konten LIKE ?)"; 
    $params[] = $search; $params[] = $search;
}

$validSorts = ['judul', 'prioritas', 'created_at', 'tanggal_mulai'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'created_at';
$sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

/* ===== FETCH DATA ===== */
$pengumumanList = [];
$stats = [
    'total' => 0,
    'aktif' => 0,
    'nonaktif' => 0,
    'penting' => 0,
    'tinggi' => 0,
    'expiring_soon' => 0,
];

if ($db) {
    try {
        $sql = "SELECT * FROM pengumuman WHERE $where ORDER BY FIELD(prioritas,'penting','tinggi','normal','rendah'), $sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $pengumumanList = $stmt->fetchAll();
        
        // Stats
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM pengumuman")->fetchColumn();
        $stats['aktif'] = (int)$db->query("SELECT COUNT(*) FROM pengumuman WHERE status='aktif'")->fetchColumn();
        $stats['nonaktif'] = (int)$db->query("SELECT COUNT(*) FROM pengumuman WHERE status='nonaktif'")->fetchColumn();
        $stats['penting'] = (int)$db->query("SELECT COUNT(*) FROM pengumuman WHERE prioritas='penting' AND status='aktif'")->fetchColumn();
        $stats['tinggi'] = (int)$db->query("SELECT COUNT(*) FROM pengumuman WHERE prioritas='tinggi' AND status='aktif'")->fetchColumn();
        $stats['expiring_soon'] = (int)$db->query("SELECT COUNT(*) FROM pengumuman WHERE status='aktif' AND tanggal_berakhir BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== PRIORITY DISTRIBUTION ===== */
$priorityStats = [];
if ($db) {
    try {
        $priorityStats = $db->query("
            SELECT prioritas, COUNT(*) as total 
            FROM pengumuman 
            WHERE status='aktif'
            GROUP BY prioritas 
            ORDER BY FIELD(prioritas,'penting','tinggi','normal','rendah')
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== RECENT PENGUMUMAN ===== */
$recentPengumuman = [];
if ($db) {
    try {
        $recentPengumuman = $db->query("
            SELECT id, judul, prioritas, created_at 
            FROM pengumuman 
            WHERE status='aktif'
            ORDER BY created_at DESC 
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM pengumuman WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

/* ===== HELPER FUNCTIONS ===== */
function getPriorityConfig($prioritas) {
    $configs = [
        'penting' => ['label' => 'Penting', 'color' => '#EF4444', 'icon' => 'fa-exclamation-triangle', 'bg' => 'rgba(239,68,68,0.12)'],
        'tinggi' => ['label' => 'Tinggi', 'color' => '#F59E0B', 'icon' => 'fa-arrow-up', 'bg' => 'rgba(245,158,11,0.12)'],
        'normal' => ['label' => 'Normal', 'color' => '#3B82F6', 'icon' => 'fa-info-circle', 'bg' => 'rgba(59,130,246,0.12)'],
        'rendah' => ['label' => 'Rendah', 'color' => '#6B7280', 'icon' => 'fa-minus-circle', 'bg' => 'rgba(107,114,128,0.12)'],
    ];
    return $configs[$prioritas] ?? $configs['normal'];
}

include 'includes/header.php';
?>

<div class="page-content pengumuman-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="pengumuman-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-broadcast-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcement Management</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-broadcast-tower"></i>
                    </span>
                    Kelola Pengumuman
                </h1>
                <p class="hero-subtitle">
                    Informasi penting untuk mahasiswa, dosen, dan pengunjung website
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-newspaper"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total Pengumuman</span>
                    </div>
                    <div class="hms-item live">
                        <span class="live-dot"></span>
                        <span><strong><?= $stats['aktif'] ?></strong> Aktif</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-exclamation-triangle" style="color:#F59E0B"></i>
                        <span><strong><?= $stats['penting'] ?></strong> Penting</span>
                    </div>
                    <?php if ($stats['expiring_soon'] > 0): ?>
                    <div class="hms-item warning">
                        <i class="fas fa-clock" style="color:#EF4444"></i>
                        <span><strong><?= $stats['expiring_soon'] ?></strong> Segera Berakhir</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportPengumuman()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-ghost" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Pengumuman</span>
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
    <div class="pengumuman-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="pengumuman-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid pengumuman-stats">
                <div class="stat-card stat-blue">
                    <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Pengumuman</p>
                        <span class="stat-trend up"><i class="fas fa-newspaper"></i> Semua</span>
                    </div>
                </div>

                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['aktif'] ?>">0</h3>
                        <p>Sedang Aktif</p>
                        <span class="stat-trend up live-pulse"><i class="fas fa-circle"></i> Live</span>
                    </div>
                </div>

                <div class="stat-card stat-red">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['penting'] ?>">0</h3>
                        <p>Prioritas Penting</p>
                        <span class="stat-trend up"><i class="fas fa-flag"></i> Urgent</span>
                    </div>
                </div>

                <div class="stat-card stat-amber">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['expiring_soon'] ?>">0</h3>
                        <p>Segera Berakhir</p>
                        <span class="stat-trend up"><i class="fas fa-hourglass-half"></i> 7 Hari</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card pengumuman-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari judul atau konten pengumuman..." value="<?= clean($_GET['q'] ?? '') ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterStatus" class="toolbar-select">
                            <option value="">📊 Semua Status</option>
                            <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>🟢 Aktif</option>
                            <option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : '' ?>>⚪ Nonaktif</option>
                        </select>
                        
                        <select id="filterPrioritas" class="toolbar-select">
                            <option value="">🎯 Semua Prioritas</option>
                            <option value="penting" <?= $filterPrioritas === 'penting' ? 'selected' : '' ?>>🚨 Penting</option>
                            <option value="tinggi" <?= $filterPrioritas === 'tinggi' ? 'selected' : '' ?>>🔼 Tinggi</option>
                            <option value="normal" <?= $filterPrioritas === 'normal' ? 'selected' : '' ?>>➖ Normal</option>
                            <option value="rendah" <?= $filterPrioritas === 'rendah' ? 'selected' : '' ?>>🔽 Rendah</option>
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
                            <a href="?sort=judul&dir=ASC" class="sort-item <?= $sortBy === 'judul' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Judul A-Z
                            </a>
                            <a href="?sort=prioritas&dir=ASC" class="sort-item <?= $sortBy === 'prioritas' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-flag"></i> Prioritas
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
                        <button class="view-btn" data-view="table" title="Table View">
                            <i class="fas fa-table"></i>
                        </button>
                    </div>
                    
                    <button class="toolbar-icon-btn" onclick="resetFilters()" title="Reset Filter">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
            </div>

            <!-- Bulk Actions Bar -->
            <div class="bulk-bar" id="bulkBar">
                <div class="bulk-info">
                    <i class="fas fa-check-circle"></i>
                    <span><strong id="selectedCount">0</strong> pengumuman dipilih</span>
                </div>
                <div class="bulk-actions">
                    <button class="btn btn-sm btn-success" onclick="bulkAction('bulk-activate')">
                        <i class="fas fa-check"></i> Aktifkan
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="bulkAction('bulk-deactivate')">
                        <i class="fas fa-times"></i> Nonaktifkan
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('bulk-delete')">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        Batal
                    </button>
                </div>
            </div>

            <!-- Pengumuman Grid -->
            <div class="pengumuman-grid" id="pengumumanGrid" data-view="grid">
                <?php if (!empty($pengumumanList)): ?>
                    <?php foreach ($pengumumanList as $p): 
                        $prioConfig = getPriorityConfig($p['prioritas'] ?? 'normal');
                        $isActive = ($p['status'] ?? 'nonaktif') === 'aktif';
                    ?>
                    <div class="pengumuman-card <?= !$isActive ? 'is-inactive' : '' ?>" data-id="<?= $p['id'] ?>">
                        <!-- Checkbox -->
                        <label class="card-checkbox">
                            <input type="checkbox" class="pengumuman-check" value="<?= $p['id'] ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <!-- Priority Badge -->
                        <div class="priority-badge" style="background: <?= $prioConfig['color'] ?>; box-shadow: 0 4px 12px <?= $prioConfig['color'] ?>40">
                            <i class="fas <?= $prioConfig['icon'] ?>"></i>
                        </div>
                        
                        <!-- Status Dot -->
                        <span class="status-indicator <?= $isActive ? 'active' : 'inactive' ?>"></span>
                        
                        <!-- Card Header with Gradient -->
                        <div class="pc-header" onclick="previewPengumuman(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" style="background: linear-gradient(135deg, <?= $prioConfig['color'] ?>22, <?= $prioConfig['color'] ?>11)">
                            <div class="pc-icon" style="color: <?= $prioConfig['color'] ?>">
                                <i class="fas <?= $prioConfig['icon'] ?>"></i>
                            </div>
                            <div class="pc-overlay">
                                <div class="pc-quick-actions" onclick="event.stopPropagation()">
                                    <button onclick="editPengumuman(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="previewPengumuman(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="?action=toggle&id=<?= $p['id'] ?>" title="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                        <i class="fas fa-<?= $isActive ? 'eye-slash' : 'eye' ?>"></i>
                                    </a>
                                    <a href="?action=delete&id=<?= $p['id'] ?>" title="Hapus" data-confirm="Yakin hapus pengumuman ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="pc-body">
                            <div class="pc-priority-label" style="color: <?= $prioConfig['color'] ?>">
                                <i class="fas <?= $prioConfig['icon'] ?>"></i>
                                <?= $prioConfig['label'] ?>
                            </div>

                            <h3 class="pc-title"><?= clean($p['judul'] ?? 'Tanpa Judul') ?></h3>
                            
                            <p class="pc-excerpt"><?= clean(mb_substr($p['konten'] ?? '', 0, 100)) ?><?= strlen($p['konten'] ?? '') > 100 ? '...' : '' ?></p>
                            
                            <div class="pc-meta">
                                <span><i class="fas fa-calendar"></i> <?= function_exists('formatTanggal') ? formatTanggal($p['created_at'] ?? date('Y-m-d')) : date('d M Y', strtotime($p['created_at'] ?? 'now')) ?></span>
                                <?php if (!empty($p['tanggal_berakhir'])): ?>
                                <span><i class="fas fa-clock"></i> s/d <?= function_exists('formatTanggal') ? formatTanggal($p['tanggal_berakhir']) : date('d M Y', strtotime($p['tanggal_berakhir'])) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="pc-footer">
                                <span class="pc-badge badge-<?= $isActive ? 'success' : 'default' ?>">
                                    <i class="fas fa-<?= $isActive ? 'check-circle' : 'file' ?>"></i>
                                    <?= ucfirst($p['status'] ?? 'nonaktif') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration">
                            <svg viewBox="0 0 200 150" width="200" height="150">
                                <ellipse cx="100" cy="130" rx="70" ry="10" fill="var(--gray-200)" opacity="0.5"/>
                                <rect x="60" y="40" width="80" height="70" rx="4" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <rect x="70" y="50" width="60" height="8" rx="2" fill="var(--primary)" opacity="0.3"/>
                                <rect x="70" y="65" width="60" height="6" rx="2" fill="var(--gray-200)"/>
                                <rect x="70" y="78" width="40" height="6" rx="2" fill="var(--gray-200)"/>
                                <circle cx="155" cy="40" r="18" fill="#F59E0B" opacity="0.9"/>
                                <line x1="148" y1="40" x2="162" y2="40" stroke="white" stroke-width="3" stroke-linecap="round"/>
                                <line x1="155" y1="33" x2="155" y2="47" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Belum ada pengumuman</h3>
                        <p>Buat pengumuman pertama untuk menginformasikan pengunjung website</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-plus"></i> Tambah Pengumuman
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Table View -->
            <?php if (!empty($pengumumanList)): ?>
            <div class="pengumuman-table-view" id="pengumumanTable" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="selectAllTable"></th>
                                    <th>Prioritas</th>
                                    <th>Judul</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th>Berakhir</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pengumumanList as $p): 
                                    $prioConfig = getPriorityConfig($p['prioritas'] ?? 'normal');
                                    $isActive = ($p['status'] ?? 'nonaktif') === 'aktif';
                                ?>
                                <tr class="<?= !$isActive ? 'inactive-row' : '' ?>">
                                    <td><input type="checkbox" class="pengumuman-check" value="<?= $p['id'] ?>"></td>
                                    <td>
                                        <span class="badge" style="background:<?= $prioConfig['bg'] ?>;color:<?= $prioConfig['color'] ?>">
                                            <i class="fas <?= $prioConfig['icon'] ?>"></i> <?= $prioConfig['label'] ?>
                                        </span>
                                    </td>
                                    <td><strong><?= clean($p['judul']) ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?= $isActive ? 'success' : 'default' ?>">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= function_exists('formatTanggal') ? formatTanggal($p['created_at']) : date('d M Y', strtotime($p['created_at'])) ?></td>
                                    <td><?= !empty($p['tanggal_berakhir']) ? (function_exists('formatTanggal') ? formatTanggal($p['tanggal_berakhir']) : date('d M Y', strtotime($p['tanggal_berakhir']))) : '-' ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-sm" onclick="previewPengumuman(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-sm" onclick="editPengumuman(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=toggle&id=<?= $p['id'] ?>" class="btn-icon btn-sm" title="Toggle">
                                                <i class="fas fa-<?= $isActive ? 'eye-slash' : 'eye' ?>"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $p['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus pengumuman ini?" title="Hapus">
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
                <span>Menampilkan <strong><?= count($pengumumanList) ?></strong> pengumuman</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="pengumuman-sidebar">
            <!-- Priority Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribusi Prioritas</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($priorityStats)): 
                        $maxPrio = max(array_column($priorityStats, 'total'));
                    ?>
                        <?php foreach ($priorityStats as $ps): 
                            $config = getPriorityConfig($ps['prioritas']);
                            $pct = round(($ps['total'] / max($maxPrio, 1)) * 100);
                        ?>
                        <div class="priority-stat-item">
                            <div class="psi-head">
                                <span class="psi-name" style="color: <?= $config['color'] ?>">
                                    <i class="fas <?= $config['icon'] ?>"></i>
                                    <?= $config['label'] ?>
                                </span>
                                <span class="psi-count"><?= $ps['total'] ?></span>
                            </div>
                            <div class="psi-bar">
                                <div class="psi-bar-fill" style="width: <?= $pct ?>%; background: <?= $config['color'] ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-chart-pie"></i>
                            <p>Belum ada data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Pengumuman -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-clock"></i> Terbaru Ditambahkan</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($recentPengumuman)): ?>
                        <?php foreach ($recentPengumuman as $rp): 
                            $config = getPriorityConfig($rp['prioritas']);
                        ?>
                        <div class="recent-item" onclick="previewPengumuman(<?= htmlspecialchars(json_encode($rp, JSON_HEX_APOS)) ?>)">
                            <div class="ri-icon" style="background: <?= $config['bg'] ?>; color: <?= $config['color'] ?>">
                                <i class="fas <?= $config['icon'] ?>"></i>
                            </div>
                            <div class="ri-info">
                                <div class="ri-name"><?= clean($rp['judul']) ?></div>
                                <div class="ri-time">
                                    <?= function_exists('formatTanggal') ? formatTanggal($rp['created_at']) : date('d M', strtotime($rp['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-clock"></i>
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
                    <button class="qa-btn primary" onclick="openModal()">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Pengumuman</span>
                    </button>
                    <button class="qa-btn" onclick="document.getElementById('filterStatus').value='aktif'; applyFilters();">
                        <i class="fas fa-check-circle"></i>
                        <span>Lihat Yang Aktif</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </button>
                    <button class="qa-btn" onclick="document.getElementById('filterPrioritas').value='penting'; applyFilters();">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Prioritas Penting</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </button>
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
                        <div class="tip-text">Gunakan prioritas "Penting" untuk informasi mendesak</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Setel tanggal berakhir agar pengumuman otomatis归档</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-info"></i></div>
                        <div class="tip-text">Gunakan Ctrl+S untuk menyimpan dengan cepat</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== PENGUMUMAN MODAL ===== -->
<div class="modal" id="pengumumanModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient pengumuman-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-bullhorn"></i></div>
                <div>
                    <h3 id="modalTitle">Tambah Pengumuman</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px" id="modalSubtitle">Buat pengumuman baru untuk website</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" id="pengumumanForm" class="modal-body">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" id="formId">
            
            <div class="form-sections">
                <!-- Section: Informasi Dasar -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-info-circle"></i> Informasi Dasar</h4>
                    <div class="form-grid-2">
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label>Judul Pengumuman <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-heading"></i>
                                <input type="text" name="judul" id="judul" required placeholder="Masukkan judul pengumuman">
                            </div>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label>Konten Pengumuman <span class="required">*</span></label>
                            <div class="textarea-wrap">
                                <textarea name="konten" id="konten" rows="6" required placeholder="Tulis konten pengumuman di sini..."></textarea>
                                <span class="char-counter"><span id="kontenCount">0</span>/1000</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Pengaturan -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-cog"></i> Pengaturan</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Prioritas <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-flag"></i>
                                <select name="prioritas" id="prioritas" required>
                                    <option value="rendah"> Rendah</option>
                                    <option value="normal" selected>➖ Normal</option>
                                    <option value="tinggi"> Tinggi</option>
                                    <option value="penting">🚨 Penting</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Status <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-toggle-on"></i>
                                <select name="status" id="status" required>
                                    <option value="aktif" selected>🟢 Aktif</option>
                                    <option value="nonaktif">⚪ Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Tanggal Mulai (Opsional)</label>
                            <div class="input-icon">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="date" name="tanggal_mulai" id="tanggal_mulai">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Tanggal Berakhir (Opsional)</label>
                            <div class="input-icon">
                                <i class="fas fa-calendar-times"></i>
                                <input type="date" name="tanggal_berakhir" id="tanggal_berakhir">
                            </div>
                            <small class="form-hint">Kosongkan jika tidak ada batas waktu</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Pengumuman
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== PREVIEW MODAL ===== -->
<div class="modal" id="previewModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient pengumuman-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-eye"></i></div>
                <div>
                    <h3>Preview Pengumuman</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Detail lengkap pengumuman</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePreview()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="previewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePreview()">Tutup</button>
            <button class="btn btn-primary" id="previewEditBtn"><i class="fas fa-edit"></i> Edit Pengumuman</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   PENGUMUMAN PAGE - BROADCAST CENTER EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.pengumuman-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.pengumuman-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 50%, #B45309 100%);
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

.hero-broadcast-pattern {
    position: absolute;
    top: 50%;
    right: 5%;
    transform: translateY(-50%);
    font-size: 18rem;
    color: rgba(255,255,255,0.03);
    pointer-events: none;
}

.hero-broadcast-pattern::before {
    content: '\f8e6';
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

.hms-item.warning {
    background: rgba(239,68,68,0.2);
    border-color: rgba(239,68,68,0.3);
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
    color: #FDE68A;
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

.btn-hero-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(16,185,129,0.5);
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
.pengumuman-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.pengumuman-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.pengumuman-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-blue .stat-icon { background: linear-gradient(135deg, #3B82F6, #1E40AF); }
.stat-green .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-red .stat-icon { background: linear-gradient(135deg, #EF4444, #B91C1C); }
.stat-amber .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }

.live-pulse {
    animation: livePulse 1.5s infinite;
}

/* ===== TOOLBAR ===== */
.pengumuman-toolbar {
    padding: 16px 20px;
    margin-bottom: 20px;
}

.pengumuman-toolbar {
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
    color: #F59E0B;
    box-shadow: var(--shadow-sm);
}

/* ===== BULK BAR ===== */
.bulk-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: linear-gradient(135deg, #D97706, #F59E0B);
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

/* ===== PENGUMUMAN GRID ===== */
.pengumuman-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.pengumuman-grid[data-view="list"] {
    grid-template-columns: 1fr;
}

.pengumuman-grid[data-view="list"] .pengumuman-card {
    display: grid;
    grid-template-columns: 180px 1fr;
}

.pengumuman-grid[data-view="list"] .pc-header {
    height: 100%;
    min-height: 180px;
}

.pengumuman-card {
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

[data-theme="dark"] .pengumuman-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.pengumuman-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(245,158,11,0.3);
}

.pengumuman-card.is-inactive {
    opacity: 0.7;
}

.pengumuman-card.is-inactive:hover {
    opacity: 0.9;
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

.pengumuman-card:hover .card-checkbox,
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
    background: #F59E0B;
    border-color: #F59E0B;
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

/* Priority Badge */
.priority-badge {
    position: absolute;
    top: 14px;
    right: 54px;
    z-index: 12;
    width: 32px;
    height: 32px;
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    animation: pulseBadge 2s infinite;
}

.status-indicator {
    position: absolute;
    top: 14px;
    right: 14px;
    z-index: 12;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid var(--white);
    box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
}

.status-indicator.active {
    background: #10B981;
    box-shadow: 0 0 0 3px rgba(16,185,129,0.3);
}

.status-indicator.inactive {
    background: #94A3B8;
}

/* Card Header */
.pc-header {
    position: relative;
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    cursor: pointer;
}

.pc-icon {
    font-size: 4rem;
    z-index: 1;
}

.pc-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent 70%);
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    padding: 14px;
    opacity: 0;
    transition: opacity var(--transition);
    z-index: 2;
}

.pengumuman-card:hover .pc-overlay {
    opacity: 1;
}

.pc-quick-actions {
    display: flex;
    gap: 8px;
}

.pc-quick-actions button,
.pc-quick-actions a {
    width: 38px;
    height: 38px;
    border-radius: var(--radius-sm);
    background: rgba(255,255,255,0.95);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--dark);
    text-decoration: none;
    transition: all var(--transition-fast);
    backdrop-filter: blur(10px);
    font-size: 0.9rem;
}

.pc-quick-actions button:hover,
.pc-quick-actions a:hover {
    background: #F59E0B;
    color: var(--white);
    transform: translateY(-3px);
}

/* Card Body */
.pc-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.pc-priority-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 10px;
    align-self: flex-start;
}

.pc-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 10px;
    line-height: 1.3;
}

[data-theme="dark"] .pc-title {
    color: var(--white);
}

.pc-excerpt {
    font-size: 0.88rem;
    color: var(--gray-600);
    margin-bottom: 14px;
    line-height: 1.6;
    flex: 1;
}

[data-theme="dark"] .pc-excerpt {
    color: var(--gray-400);
}

.pc-meta {
    display: flex;
    gap: 14px;
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-bottom: 12px;
    flex-wrap: wrap;
}

[data-theme="dark"] .pc-meta {
    color: var(--gray-400);
}

.pc-meta i {
    color: #F59E0B;
    margin-right: 4px;
}

.pc-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid var(--gray-100);
}

[data-theme="dark"] .pc-footer {
    border-color: rgba(255,255,255,0.08);
}

.pc-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}

.pc-badge.badge-success {
    background: rgba(16,185,129,0.12);
    color: #047857;
}

.pc-badge.badge-default {
    background: rgba(107,114,128,0.12);
    color: #4B5563;
}

/* ===== SIDEBAR WIDGETS ===== */
.pengumuman-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
    position: sticky;
    top: 90px;
}

/* Priority Stats */
.priority-stat-item {
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .priority-stat-item {
    border-color: rgba(255,255,255,0.05);
}

.priority-stat-item:last-child {
    border-bottom: none;
}

.psi-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.psi-name {
    font-size: 0.82rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.psi-name i {
    font-size: 0.78rem;
}

.psi-count {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .psi-count {
    color: var(--white);
}

.psi-bar {
    height: 5px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .psi-bar {
    background: rgba(255,255,255,0.08);
}

.psi-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

/* Recent Items */
.recent-item {
    display: grid;
    grid-template-columns: 40px 1fr;
    gap: 10px;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    transition: all var(--transition-fast);
}

[data-theme="dark"] .recent-item {
    border-color: rgba(255,255,255,0.05);
}

.recent-item:last-child {
    border-bottom: none;
}

.recent-item:hover {
    transform: translateX(4px);
}

.ri-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.ri-info {
    min-width: 0;
}

.ri-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

[data-theme="dark"] .ri-name {
    color: var(--gray-200);
}

.ri-time {
    font-size: 0.72rem;
    color: var(--gray-500);
}

/* ===== MODAL GRADIENT ===== */
.pengumuman-modal-head {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 60%, #B45309 100%) !important;
}

/* Name Preview */
.name-preview {
    margin-top: 16px;
    padding: 12px 16px;
    background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(217,119,6,0.05));
    border-left: 4px solid #F59E0B;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.np-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--gray-700);
}

[data-theme="dark"] .np-label {
    color: var(--gray-300);
}

.np-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: #B45309;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== PREVIEW MODAL ===== */
.preview-pengumuman-hero {
    padding: 30px 20px;
    text-align: center;
    position: relative;
    margin: -28px -28px 20px;
    border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
}

.preview-priority-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    backdrop-filter: blur(10px);
    margin-bottom: 12px;
    color: var(--white);
}

.preview-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--white);
    margin-bottom: 8px;
    line-height: 1.3;
}

.preview-meta {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
    color: rgba(255,255,255,0.9);
    font-size: 0.85rem;
}

.preview-content {
    padding: 20px;
    background: var(--gray-50);
    border-radius: var(--radius);
    margin-bottom: 16px;
    line-height: 1.8;
    color: var(--dark);
}

[data-theme="dark"] .preview-content {
    background: rgba(255,255,255,0.04);
    color: var(--gray-200);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .pengumuman-layout {
        grid-template-columns: 1fr;
    }
    
    .pengumuman-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .pengumuman-page {
        padding: 16px;
    }
    
    .pengumuman-hero {
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
    
    .pengumuman-toolbar {
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
    
    .pengumuman-grid {
        grid-template-columns: 1fr;
    }
    
    .pengumuman-grid[data-view="list"] .pengumuman-card {
        grid-template-columns: 1fr;
    }
    
    .form-grid-2 {
        grid-template-columns: 1fr;
    }
}

@media print {
    .pengumuman-hero,
    .pengumuman-sidebar,
    .pengumuman-toolbar,
    .bulk-bar,
    .card-checkbox,
    .pc-quick-actions {
        display: none !important;
    }
    
    .pengumuman-layout {
        grid-template-columns: 1fr;
    }
    
    .pengumuman-card {
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
    window.openModal = function() {
        document.getElementById('pengumumanForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Pengumuman';
        document.getElementById('modalSubtitle').textContent = 'Buat pengumuman baru untuk website';
        document.getElementById('kontenCount').textContent = '0';
        
        document.getElementById('pengumumanModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('judul')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('pengumumanModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.editPengumuman = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Pengumuman';
        document.getElementById('modalSubtitle').textContent = 'Perbarui pengumuman';
        document.getElementById('formId').value = data.id;
        document.getElementById('judul').value = data.judul || '';
        document.getElementById('konten').value = data.konten || '';
        document.getElementById('prioritas').value = data.prioritas || 'normal';
        document.getElementById('status').value = data.status || 'aktif';
        document.getElementById('tanggal_mulai').value = data.tanggal_mulai || '';
        document.getElementById('tanggal_berakhir').value = data.tanggal_berakhir || '';
        document.getElementById('kontenCount').textContent = (data.konten || '').length;
        
        document.getElementById('pengumumanModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== PREVIEW MODAL ===== */
    window.previewPengumuman = function(data) {
        const prioConfig = {
            penting: {label: 'Penting', color: '#EF4444', icon: 'fa-exclamation-triangle'},
            tinggi: {label: 'Tinggi', color: '#F59E0B', icon: 'fa-arrow-up'},
            normal: {label: 'Normal', color: '#3B82F6', icon: 'fa-info-circle'},
            rendah: {label: 'Rendah', color: '#6B7280', icon: 'fa-minus-circle'}
        };
        const config = prioConfig[data.prioritas] || prioConfig.normal;
        
        const body = document.getElementById('previewBody');
        body.innerHTML = `
            <div class="preview-pengumuman-hero" style="background: linear-gradient(135deg, ${config.color}, ${config.color}dd)">
                <div class="preview-priority-badge">
                    <i class="fas ${config.icon}"></i>
                    Prioritas ${config.label}
                </div>
                <h2 class="preview-title">${data.judul || 'Tanpa Judul'}</h2>
                <div class="preview-meta">
                    <span><i class="fas fa-calendar"></i> ${new Date(data.created_at || new Date()).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'})}</span>
                    ${data.tanggal_berakhir ? `<span><i class="fas fa-clock"></i> s/d ${new Date(data.tanggal_berakhir).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'})}</span>` : ''}
                </div>
            </div>
            
            <div class="preview-content">
                ${data.konten || 'Tidak ada konten'}
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:14px;background:var(--gray-50);border-radius:var(--radius)">
                <div>
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--gray-500);margin-bottom:6px">Status</div>
                    <div style="font-size:0.95rem;color:var(--dark);font-weight:600">
                        <span class="badge badge-${(data.status || 'nonaktif') === 'aktif' ? 'success' : 'default'}">
                            <i class="fas fa-${(data.status || 'nonaktif') === 'aktif' ? 'check-circle' : 'file'}"></i>
                            ${(data.status || 'nonaktif') === 'aktif' ? 'Aktif' : 'Nonaktif'}
                        </span>
                    </div>
                </div>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--gray-500);margin-bottom:6px">Prioritas</div>
                    <div style="font-size:0.95rem;color:${config.color};font-weight:600">
                        <i class="fas ${config.icon}"></i> ${config.label}
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('previewEditBtn').onclick = function() {
            closePreview();
            setTimeout(() => editPengumuman(data), 300);
        };
        
        document.getElementById('previewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closePreview = function() {
        document.getElementById('previewModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    /* ===== KONTEN COUNTER ===== */
    const kontenInput = document.getElementById('konten');
    const kontenCount = document.getElementById('kontenCount');
    
    kontenInput?.addEventListener('input', function() {
        const count = this.value.length;
        kontenCount.textContent = count;
        if (count >= 950) {
            kontenCount.style.color = 'var(--warning)';
        } else {
            kontenCount.style.color = '';
        }
    });

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const filterPrioritas = document.getElementById('filterPrioritas');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    [filterStatus, filterPrioritas].forEach(el => {
        el?.addEventListener('change', applyFilters);
    });

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const status = filterStatus?.value;
        const prioritas = filterPrioritas?.value;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        if (prioritas) url.searchParams.set('prioritas', prioritas); else url.searchParams.delete('prioritas');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'status', 'prioritas', 'sort', 'dir'].forEach(p => url.searchParams.delete(p));
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
            const grid = document.getElementById('pengumumanGrid');
            const table = document.getElementById('pengumumanTable');
            
            if (view === 'table') {
                grid.style.display = 'none';
                table.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                table.style.display = 'none';
                grid.dataset.view = view;
            }
            
            localStorage.setItem('pengumuman_view', view);
        });
    });

    const savedView = localStorage.getItem('pengumuman_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');

    document.querySelectorAll('.pengumuman-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
        });
    });

    document.getElementById('selectAllTable')?.addEventListener('change', function() {
        document.querySelectorAll('#pengumumanTable .pengumuman-check').forEach(c => {
            c.checked = this.checked;
            const id = c.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
        });
        updateBulkBar();
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
        document.querySelectorAll('.pengumuman-check').forEach(c => c.checked = false);
        const selectAll = document.getElementById('selectAllTable');
        if (selectAll) selectAll.checked = false;
        updateBulkBar();
    };

    window.bulkAction = function(action) {
        if (selectedIds.size === 0) return;
        
        const messages = {
            'bulk-delete': `Yakin hapus ${selectedIds.size} pengumuman terpilih?`,
            'bulk-activate': `Aktifkan ${selectedIds.size} pengumuman terpilih?`,
            'bulk-deactivate': `Nonaktifkan ${selectedIds.size} pengumuman terpilih?`
        };
        
        if (!confirm(messages[action])) return;
        
        const ids = Array.from(selectedIds).join(',');
        window.adminSecureAction(`?action=${action}&ids=${ids}`);
    };

    /* ===== EXPORT CSV ===== */
    window.exportPengumuman = function() {
        const pengumumanList = <?= json_encode($pengumumanList) ?>;
        
        if (!pengumumanList || pengumumanList.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['ID', 'Judul', 'Prioritas', 'Status', 'Tanggal Mulai', 'Tanggal Berakhir', 'Created At'];
        const rows = pengumumanList.map(p => [
            p.id,
            `"${(p.judul || '').replace(/"/g, '""')}"`,
            p.prioritas || '',
            p.status || '',
            p.tanggal_mulai || '',
            p.tanggal_berakhir || '',
            p.created_at || ''
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `pengumuman-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${pengumumanList.length} pengumuman berhasil di-export`, 'success');
        }, 500);
    };

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        // N = New pengumuman
        if (e.key === 'n' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) && !e.ctrlKey && !e.metaKey) {
            e.preventDefault();
            openModal();
        }
        
        // / = Focus search
        if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            searchInput?.focus();
        }
        
        // Escape = Close modals
        if (e.key === 'Escape') {
            closeModal();
            closePreview();
        }
    });

    /* ===== MODAL BACKDROP CLICK ===== */
    document.getElementById('pengumumanModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    
    document.getElementById('previewModal')?.addEventListener('click', function(e) {
        if (e.target === this) closePreview();
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

    /* ===== AUTO OPEN EDIT MODAL ===== */
    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && $editData): ?>
    window.addEventListener('load', () => {
        setTimeout(() => editPengumuman(<?= json_encode($editData, JSON_HEX_APOS) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c Pengumuman Ultimate v4.0 Loaded', 'color:#F59E0B;font-weight:bold;font-size:14px');
    console.log('%c   Features: Priority Distribution, Bulk Actions, Export CSV', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: N (New), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>