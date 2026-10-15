<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Prospek Karir';

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
            $db->prepare("DELETE FROM karir_paths WHERE id = ?")->execute([$id]);
            setFlash('success', 'Prospek karir berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus prospek karir');
        }
        header('Location: prospek.php');
        exit;
    }
    
    // Bulk delete
    if ($_GET['action'] === 'bulk-delete' && !empty($_POST['selected'])) {
        try {
            $ids = array_map('intval', $_POST['selected']);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $db->prepare("DELETE FROM karir_paths WHERE id IN ($placeholders)")->execute($ids);
            setFlash('success', count($ids) . ' prospek karir berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus prospek karir');
        }
        header('Location: prospek.php');
        exit;
    }
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $jabatan = clean($_POST['jabatan']);
        $ikon = clean($_POST['ikon']);
        $deskripsi = clean($_POST['deskripsi']);
        $gaji_min = (int)($_POST['gaji_min'] ?? 0);
        $gaji_max = (int)($_POST['gaji_max'] ?? 0);
        $permintaan = clean($_POST['permintaan']);
        $urutan = (int)($_POST['urutan'] ?? 0);
        
        if ($id) {
            $stmt = $db->prepare("UPDATE karir_paths SET jabatan=?, ikon=?, deskripsi=?, gaji_min=?, gaji_max=?, permintaan=?, urutan=? WHERE id=?");
            $stmt->execute([$jabatan, $ikon, $deskripsi, $gaji_min, $gaji_max, $permintaan, $urutan, $id]);
            setFlash('success', 'Prospek karir berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO karir_paths (jabatan, ikon, deskripsi, gaji_min, gaji_max, permintaan, urutan, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$jabatan, $ikon, $deskripsi, $gaji_min, $gaji_max, $permintaan, $urutan]);
            setFlash('success', 'Prospek karir baru berhasil ditambahkan');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan: ' . $e->getMessage());
    }
    header('Location: prospek.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$searchQuery = $_GET['q'] ?? '';
$sortBy = $_GET['sort'] ?? 'urutan';
$sortDir = $_GET['dir'] ?? 'ASC';

$where = "1=1";
$params = [];
if ($searchQuery) { 
    $searchParam = "%$searchQuery%";
    $where .= " AND (jabatan LIKE ? OR deskripsi LIKE ?)"; 
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$validSorts = ['urutan', 'jabatan', 'gaji_max', 'created_at'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'urutan';
$sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

/* ===== FETCH DATA & STATS ===== */
$prospekList = [];
$stats = [
    'total' => 0,
    'tinggi' => 0,
    'sedang' => 0,
    'rendah' => 0,
];

if ($db) {
    try {
        $sql = "SELECT * FROM karir_paths WHERE $where ORDER BY $sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $prospekList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM karir_paths")->fetchColumn();
        $stats['tinggi'] = (int)$db->query("SELECT COUNT(*) FROM karir_paths WHERE permintaan IN ('tinggi', 'sangat_tinggi')")->fetchColumn();
        $stats['sedang'] = (int)$db->query("SELECT COUNT(*) FROM karir_paths WHERE permintaan = 'sedang'")->fetchColumn();
        $stats['rendah'] = (int)$db->query("SELECT COUNT(*) FROM karir_paths WHERE permintaan = 'rendah'")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== PERMINTAAN CONFIG ===== */
$permintaanConfig = [
    'sangat_tinggi' => ['icon' => 'fa-fire', 'color' => '#EF4444', 'label' => 'Sangat Tinggi'],
    'tinggi' => ['icon' => 'fa-chart-line', 'color' => '#10B981', 'label' => 'Tinggi'],
    'sedang' => ['icon' => 'fa-chart-bar', 'color' => '#F59E0B', 'label' => 'Sedang'],
    'rendah' => ['icon' => 'fa-chart-pie', 'color' => '#6B7280', 'label' => 'Rendah'],
];

function getPermintaanInfo($permintaan) {
    global $permintaanConfig;
    return $permintaanConfig[$permintaan] ?? $permintaanConfig['sedang'];
}

function formatRupiah($angka) {
    if ($angka >= 1000000) {
        return 'Rp ' . number_format($angka / 1000000, 1) . ' Jt';
    }
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM karir_paths WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content prospek-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="prospek-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-career-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-briefcase"></i>
                    <span>Career Opportunities</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-chart-line"></i>
                    </span>
                    Kelola Prospek Karir
                </h1>
                <p class="hero-subtitle">
                    Manajemen prospek pekerjaan dan peluang karir lulusan
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-briefcase"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total Prospek</span>
                    </div>
                    <div class="hms-item hot">
                        <i class="fas fa-fire"></i>
                        <span><strong><?= $stats['tinggi'] ?></strong> High Demand</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportProspek()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Prospek</span>
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
    <div class="prospek-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="prospek-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid prospek-stats">
                <div class="stat-card stat-emerald">
                    <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Prospek</p>
                        <span class="stat-trend up"><i class="fas fa-layer-group"></i> Careers</span>
                    </div>
                </div>

                <div class="stat-card stat-rose">
                    <div class="stat-icon"><i class="fas fa-fire"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['tinggi'] ?>">0</h3>
                        <p>High Demand</p>
                        <span class="stat-trend up"><i class="fas fa-chart-line"></i> Trending</span>
                    </div>
                </div>

                <div class="stat-card stat-amber">
                    <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['sedang'] ?>">0</h3>
                        <p>Medium Demand</p>
                        <span class="stat-trend up"><i class="fas fa-balance-scale"></i> Stable</span>
                    </div>
                </div>

                <div class="stat-card stat-slate">
                    <div class="stat-icon"><i class="fas fa-chart-pie"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['rendah'] ?>">0</h3>
                        <p>Low Demand</p>
                        <span class="stat-trend up"><i class="fas fa-info-circle"></i> Niche</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card prospek-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari jabatan atau deskripsi..." value="<?= clean($searchQuery) ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <div class="sort-dropdown">
                        <button class="toolbar-icon-btn" id="sortBtn" title="Urutkan">
                            <i class="fas fa-sort-amount-down"></i>
                        </button>
                        <div class="sort-menu" id="sortMenu">
                            <a href="?sort=urutan&dir=ASC" class="sort-item <?= $sortBy === 'urutan' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-numeric-up"></i> Urutan
                            </a>
                            <a href="?sort=jabatan&dir=ASC" class="sort-item <?= $sortBy === 'jabatan' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Jabatan A-Z
                            </a>
                            <a href="?sort=gaji_max&dir=DESC" class="sort-item <?= $sortBy === 'gaji_max' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-money-bill-wave"></i> Gaji Tertinggi
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
            <form method="POST" id="bulkForm">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="bulk-bar" id="bulkBar">
                <div class="bulk-info">
                    <i class="fas fa-check-circle"></i>
                    <span><strong id="selectedCount">0</strong> prospek dipilih</span>
                </div>
                <div class="bulk-actions">
                    <button type="submit" formaction="?action=bulk-delete" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus prospek yang dipilih?')">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        Batal
                    </button>
                </div>
            </div>

            <!-- Prospek Grid -->
            <div class="prospek-grid" id="prospekGrid" data-view="grid">
                <?php if (!empty($prospekList)): ?>
                    <?php foreach ($prospekList as $p): 
                        $permintaanCfg = getPermintaanInfo($p['permintaan']);
                    ?>
                    <div class="prospek-card" data-id="<?= $p['id'] ?>">
                        <!-- Checkbox -->
                        <label class="card-checkbox">
                            <input type="checkbox" name="selected[]" class="prospek-check" value="<?= $p['id'] ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <!-- Icon Area -->
                        <div class="pc-image" onclick="previewProspek(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" style="background: linear-gradient(135deg, <?= $permintaanCfg['color'] ?>, <?= $permintaanCfg['color'] ?>dd)">
                            <i class="fas <?= $p['ikon'] ?>"></i>
                            
                            <div class="pc-overlay">
                                <div class="pc-quick-actions" onclick="event.stopPropagation()">
                                    <button onclick="previewProspek(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editProspek(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=delete&id=<?= $p['id'] ?>" title="Hapus" data-confirm="Yakin hapus prospek ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Demand Badge -->
                            <div class="pc-demand-badge" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                                <i class="fas <?= $permintaanCfg['icon'] ?>"></i>
                                <?= $permintaanCfg['label'] ?>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="pc-body">
                            <h3 class="pc-title"><?= clean($p['jabatan']) ?></h3>
                            <p class="pc-desc"><?= clean(mb_substr($p['deskripsi'], 0, 80)) ?><?= strlen($p['deskripsi']) > 80 ? '...' : '' ?></p>
                            
                            <div class="pc-footer">
                                <?php if ($p['gaji_min'] > 0 || $p['gaji_max'] > 0): ?>
                                <div class="pc-salary">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span><?= formatRupiah($p['gaji_min']) ?> - <?= formatRupiah($p['gaji_max']) ?></span>
                                </div>
                                <?php else: ?>
                                <div class="pc-salary" style="color: var(--gray-400)">
                                    <i class="fas fa-minus-circle"></i>
                                    <span>Data gaji belum diisi</span>
                                </div>
                                <?php endif; ?>
                                
                                <button class="pc-edit-btn" onclick="editProspek(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" style="background: <?= $permintaanCfg['color'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration">
                            <svg viewBox="0 0 200 150" width="200" height="150">
                                <ellipse cx="100" cy="130" rx="70" ry="10" fill="var(--gray-200)" opacity="0.5"/>
                                <rect x="60" y="50" width="80" height="60" rx="4" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <rect x="70" y="60" width="60" height="6" rx="3" fill="var(--gray-300)"/>
                                <rect x="70" y="75" width="40" height="6" rx="3" fill="var(--gray-300)"/>
                                <rect x="70" y="90" width="50" height="6" rx="3" fill="var(--gray-300)"/>
                                <circle cx="155" cy="45" r="18" fill="#10B981" opacity="0.9"/>
                                <path d="M 148 45 L 162 45 M 155 38 L 155 52" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Belum ada prospek karir</h3>
                        <p>Tambahkan prospek pekerjaan untuk ditampilkan di halaman prospek karir</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-plus"></i> Tambah Prospek Pertama
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- List View (Table) -->
            <?php if (!empty($prospekList)): ?>
            <div class="prospek-list-view" id="prospekList" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="selectAllTable"></th>
                                    <th>Urutan</th>
                                    <th>Jabatan</th>
                                    <th>Permintaan</th>
                                    <th>Estimasi Gaji</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prospekList as $p): 
                                    $permintaanCfg = getPermintaanInfo($p['permintaan']);
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="selected[]" class="prospek-check" value="<?= $p['id'] ?>"></td>
                                    <td><strong><?= $p['urutan'] ?></strong></td>
                                    <td>
                                        <strong><?= clean($p['jabatan']) ?></strong>
                                        <div style="font-size:0.75rem;color:var(--gray-500);margin-top:2px"><?= clean(mb_substr($p['deskripsi'], 0, 50)) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:<?= $permintaanCfg['color'] ?>15;color:<?= $permintaanCfg['color'] ?>">
                                            <i class="fas <?= $permintaanCfg['icon'] ?>"></i> <?= $permintaanCfg['label'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($p['gaji_min'] > 0 || $p['gaji_max'] > 0): ?>
                                            <strong style="color:var(--dark)"><?= formatRupiah($p['gaji_min']) ?> - <?= formatRupiah($p['gaji_max']) ?></strong>
                                        <?php else: ?>
                                            <span style="color:var(--gray-400)">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-sm" onclick="previewProspek(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-sm" onclick="editProspek(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=delete&id=<?= $p['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus prospek ini?" title="Hapus">
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
                <span>Menampilkan <strong><?= count($prospekList) ?></strong> prospek karir</span>
            </div>
            <?php endif; ?>
            </form>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="prospek-sidebar">
            <!-- Demand Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribusi Permintaan</h3>
                </div>
                <div class="widget-body">
                    <?php 
                    $totalForChart = $stats['tinggi'] + $stats['sedang'] + $stats['rendah'];
                    if ($totalForChart > 0): 
                    ?>
                    <div class="demand-donut">
                        <svg viewBox="0 0 36 36" class="donut-ring">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--gray-100)" stroke-width="3.8"/>
                            <?php 
                            $offset = 0;
                            $segments = [
                                ['value' => $stats['tinggi'], 'color' => '#EF4444', 'label' => 'High'],
                                ['value' => $stats['sedang'], 'color' => '#F59E0B', 'label' => 'Medium'],
                                ['value' => $stats['rendah'], 'color' => '#6B7280', 'label' => 'Low']
                            ];
                            foreach ($segments as $seg):
                                if ($seg['value'] > 0):
                                    $pct = ($seg['value'] / $totalForChart) * 100;
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
                    <div class="demand-legend">
                        <?php foreach ($segments as $seg): 
                            if ($seg['value'] > 0):
                                $pct = round(($seg['value'] / $totalForChart) * 100);
                        ?>
                        <div class="dl-item">
                            <span class="dl-dot" style="background: <?= $seg['color'] ?>"></span>
                            <span class="dl-name"><?= $seg['label'] ?></span>
                            <span class="dl-count"><?= $seg['value'] ?> (<?= $pct ?>%)</span>
                        </div>
                        <?php 
                            endif;
                        endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="widget-empty">
                        <i class="fas fa-chart-pie"></i>
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
                        <span>Tambah Prospek</span>
                    </button>
                    <a href="alumni.php" class="qa-btn">
                        <i class="fas fa-user-graduate"></i>
                        <span>Data Alumni</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="mitra.php" class="qa-btn">
                        <i class="fas fa-handshake"></i>
                        <span>Mitra Industri</span>
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
                        <div class="tip-text">Gunakan ikon Font Awesome yang relevan dengan jabatan</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Isi rentang gaji untuk memberikan gambaran yang jelas</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Atur urutan untuk mengontrol tampilan di website</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== PROSPEK MODAL ===== -->
<div class="modal" id="prospekModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient prospek-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-briefcase"></i></div>
                <div>
                    <h3 id="modalTitle">Tambah Prospek Karir</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px" id="modalSubtitle">Lengkapi data prospek pekerjaan</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" id="prospekForm" class="modal-body">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" id="formId">
            
            <div class="form-sections">
                <!-- Basic Info -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-info-circle"></i> Informasi Jabatan</h4>
                    <div class="form-grid-2">
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Nama Jabatan <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-briefcase"></i>
                                <input type="text" name="jabatan" id="jabatan" required placeholder="Contoh: AI Engineer, Data Scientist">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Ikon (Font Awesome) <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-icons"></i>
                                <input type="text" name="ikon" id="ikon" required placeholder="fa-robot" value="fa-briefcase">
                            </div>
                            <small class="form-hint">
                                Lihat <a href="https://fontawesome.com/icons" target="_blank" rel="noopener" style="color:#10B981">daftar ikon</a> (gunakan tanpa prefix 'fa-')
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Urutan Tampil</label>
                            <div class="input-icon">
                                <i class="fas fa-sort-numeric-up"></i>
                                <input type="number" name="urutan" id="urutan" min="0" value="0">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-align-left"></i> Detail & Market</h4>
                    <div class="form-grid-2">
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Deskripsi Pekerjaan <span class="required">*</span></label>
                            <div class="textarea-wrap">
                                <textarea name="deskripsi" id="deskripsi" rows="3" required placeholder="Jelaskan peran, tanggung jawab, dan keahlian yang dibutuhkan..."></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Gaji Minimum (Rp/bulan)</label>
                            <div class="input-icon">
                                <i class="fas fa-money-bill-wave"></i>
                                <input type="number" name="gaji_min" id="gaji_min" min="0" step="100000" placeholder="8000000">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Gaji Maximum (Rp/bulan)</label>
                            <div class="input-icon">
                                <i class="fas fa-money-bill-wave"></i>
                                <input type="number" name="gaji_max" id="gaji_max" min="0" step="100000" placeholder="20000000">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Tingkat Permintaan Pasar <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-chart-line"></i>
                                <select name="permintaan" id="permintaan" required>
                                    <option value="sangat_tinggi">🔥 Sangat Tinggi</option>
                                    <option value="tinggi" selected>📈 Tinggi</option>
                                    <option value="sedang">📊 Sedang</option>
                                    <option value="rendah">📉 Rendah</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="submitText">Simpan Prospek</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== PREVIEW MODAL ===== -->
<div class="modal" id="previewModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient prospek-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-eye"></i></div>
                <div>
                    <h3>Detail Prospek Karir</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Informasi lengkap peluang kerja</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePreview()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="previewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePreview()">Tutup</button>
            <button class="btn btn-primary" id="previewEditBtn"><i class="fas fa-edit"></i> Edit Prospek</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   PROSPEK PAGE - CAREER PROSPECTS MANAGER EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.prospek-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.prospek-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #064E3B 0%, #059669 50%, #10B981 100%);
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
    background: radial-gradient(circle, #34D399, transparent);
    top: -120px; right: 8%;
}

.orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, #10B981, transparent);
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

.hero-career-pattern {
    position: absolute;
    top: 50%;
    right: 5%;
    transform: translateY(-50%);
    font-size: 18rem;
    color: rgba(255,255,255,0.04);
    pointer-events: none;
}

.hero-career-pattern::before {
    content: '\f0b1';
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

.hms-item.hot {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.3);
}

.hms-item i {
    color: #A7F3D0;
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
    background: linear-gradient(135deg, #F59E0B, #D97706);
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
    box-shadow: 0 8px 20px rgba(245,158,11,0.4);
}

.btn-hero-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(245,158,11,0.5);
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
.prospek-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.prospek-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.prospek-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-emerald .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-rose .stat-icon { background: linear-gradient(135deg, #F43F5E, #BE123C); }
.stat-amber .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }
.stat-slate .stat-icon { background: linear-gradient(135deg, #6B7280, #374151); }

/* ===== TOOLBAR ===== */
.prospek-toolbar {
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
    border-color: #10B981;
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(16,185,129,0.1);
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
    background: rgba(16,185,129,0.1);
    color: #047857;
    font-weight: 600;
}

.sort-item i {
    width: 16px;
    color: var(--gray-500);
}

.sort-item.active i {
    color: #10B981;
}

.view-toggle {
    display: flex;
    background: var(--gray-100);
    border-radius: var(--radius);
    padding: 3px;
    gap: 2px;
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
    color: #10B981;
    box-shadow: var(--shadow-sm);
}

/* ===== BULK BAR ===== */
.bulk-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: linear-gradient(135deg, #047857, #10B981);
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

/* ===== PROSPEK GRID ===== */
.prospek-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.prospek-card {
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

[data-theme="dark"] .prospek-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.prospek-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(16,185,129,0.3);
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

.prospek-card:hover .card-checkbox,
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
    background: #10B981;
    border-color: #10B981;
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

/* Card Image */
.pc-image {
    position: relative;
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: var(--white);
    cursor: pointer;
    overflow: hidden;
}

.pc-image i {
    transition: transform 0.5s;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
}

.prospek-card:hover .pc-image i {
    transform: scale(1.1) rotate(-5deg);
}

.pc-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity var(--transition);
    z-index: 2;
}

.prospek-card:hover .pc-overlay {
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
    background: #10B981;
    color: var(--white);
    transform: translateY(-3px);
}

.pc-demand-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 10px;
    color: var(--white);
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    z-index: 12;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

/* Card Body */
.pc-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.pc-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 8px;
    line-height: 1.3;
}

[data-theme="dark"] .pc-title {
    color: var(--white);
}

.pc-desc {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 16px;
    line-height: 1.5;
    flex: 1;
}

[data-theme="dark"] .pc-desc {
    color: var(--gray-400);
}

.pc-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 14px;
    border-top: 1px solid var(--gray-100);
}

[data-theme="dark"] .pc-footer {
    border-color: rgba(255,255,255,0.08);
}

.pc-salary {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #047857;
}

.pc-salary i {
    color: #10B981;
}

.pc-edit-btn {
    width: 34px;
    height: 34px;
    border-radius: var(--radius-sm);
    color: var(--white);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
    flex-shrink: 0;
}

.pc-edit-btn:hover {
    transform: translateY(-2px) rotate(5deg);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* ===== LIST VIEW ===== */
.prospek-list-view .data-table {
    font-size: 0.85rem;
}

.prospek-list-view .data-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 700;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .prospek-list-view .data-table th {
    background: var(--dark);
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

.prospek-list-view .data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
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
    color: #10B981;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== SIDEBAR WIDGETS ===== */
.prospek-sidebar {
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
    color: #10B981;
}

.widget-badge {
    padding: 3px 10px;
    background: #10B981;
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

/* ===== DEMAND DONUT ===== */
.demand-donut {
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

.demand-legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.dl-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
}

.dl-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.dl-name {
    flex: 1;
    color: var(--dark);
    font-weight: 500;
}

[data-theme="dark"] .dl-name {
    color: var(--gray-200);
}

.dl-count {
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.78rem;
}

[data-theme="dark"] .dl-count {
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
    background: linear-gradient(135deg, #10B981, #047857);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}

.qa-btn.primary:hover {
    box-shadow: 0 6px 16px rgba(16,185,129,0.4);
}

.qa-btn i:first-child {
    color: #10B981;
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
    background: linear-gradient(135deg, rgba(16,185,129,0.04), rgba(4,120,87,0.04));
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

/* ===== MODAL ===== */
.prospek-modal-head {
    background: linear-gradient(135deg, #064E3B 0%, #059669 60%, #10B981 100%) !important;
}

/* ===== PREVIEW MODAL ===== */
.preview-hero {
    position: relative;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 5rem;
    color: var(--white);
    margin: -28px -28px 20px;
    border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
}

.preview-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.6), transparent 60%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 24px;
    color: var(--white);
}

.preview-demand-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    margin-bottom: 10px;
    align-self: flex-start;
    color: var(--white);
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
}

.preview-title {
    font-size: 1.6rem;
    font-weight: 700;
    line-height: 1.25;
    font-family: 'Space Grotesk', sans-serif;
}

.preview-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 14px;
    background: var(--gray-50);
    border-radius: var(--radius);
    margin-bottom: 16px;
}

[data-theme="dark"] .preview-info-grid {
    background: rgba(255,255,255,0.04);
}

.preview-section {
    margin-bottom: 18px;
}

.preview-label {
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

.preview-value {
    font-size: 0.92rem;
    color: var(--dark);
    line-height: 1.6;
}

[data-theme="dark"] .preview-value {
    color: var(--gray-200);
}

.preview-desc-box {
    padding: 14px;
    background: rgba(16,185,129,0.05);
    border-left: 4px solid #10B981;
    border-radius: var(--radius-sm);
    font-size: 0.88rem;
    color: var(--gray-700);
    line-height: 1.6;
}

[data-theme="dark"] .preview-desc-box {
    color: var(--gray-300);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .prospek-layout {
        grid-template-columns: 1fr;
    }
    
    .prospek-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .prospek-page {
        padding: 16px;
    }
    
    .prospek-hero {
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
    
    .prospek-toolbar {
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
    
    .toolbar-right {
        flex-direction: row;
        justify-content: space-between;
    }
    
    .prospek-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid-2 {
        grid-template-columns: 1fr;
    }
    
    .preview-info-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .prospek-hero,
    .prospek-sidebar,
    .prospek-toolbar,
    .bulk-bar,
    .pc-quick-actions,
    .card-checkbox {
        display: none !important;
    }
    
    .prospek-layout {
        grid-template-columns: 1fr;
    }
    
    .prospek-card {
        break-inside: avoid;
    }
}
</style>

<script>
(function() {
    'use strict';

    const allProspek = <?= json_encode($prospekList) ?>;
    const permintaanConfig = <?= json_encode($permintaanConfig) ?>;

    function getPermintaanInfo(permintaan) {
        return permintaanConfig[permintaan] || permintaanConfig['sedang'];
    }

    function formatRupiah(angka) {
        if (angka >= 1000000) {
            return 'Rp ' + (angka / 1000000).toFixed(1) + ' Jt';
        }
        return 'Rp ' + angka.toLocaleString('id-ID');
    }

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
        document.getElementById('prospekForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Prospek Karir';
        document.getElementById('modalSubtitle').textContent = 'Lengkapi data prospek pekerjaan';
        document.getElementById('submitText').textContent = 'Simpan Prospek';
        document.getElementById('ikon').value = 'fa-briefcase';
        document.getElementById('urutan').value = '0';
        
        document.getElementById('prospekModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('jabatan')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('prospekModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.closePreview = function() {
        document.getElementById('previewModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.editProspek = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Prospek Karir';
        document.getElementById('modalSubtitle').textContent = 'Perbarui data prospek';
        document.getElementById('submitText').textContent = 'Perbarui Prospek';
        document.getElementById('formId').value = data.id;
        document.getElementById('jabatan').value = data.jabatan;
        document.getElementById('ikon').value = data.ikon;
        document.getElementById('deskripsi').value = data.deskripsi;
        document.getElementById('gaji_min').value = data.gaji_min || '';
        document.getElementById('gaji_max').value = data.gaji_max || '';
        document.getElementById('permintaan').value = data.permintaan;
        document.getElementById('urutan').value = data.urutan || 0;
        
        document.getElementById('prospekModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== PREVIEW MODAL ===== */
    window.previewProspek = function(data) {
        const cfg = getPermintaanInfo(data.permintaan);
        const hasSalary = data.gaji_min > 0 || data.gaji_max > 0;
        
        const body = document.getElementById('previewBody');
        body.innerHTML = `
            <div class="preview-hero" style="background: linear-gradient(135deg, ${cfg.color}, ${cfg.color}dd)">
                <i class="fas ${data.ikon}"></i>
                <div class="preview-overlay">
                    <span class="preview-demand-badge">
                        <i class="fas ${cfg.icon}"></i> Permintaan ${cfg.label}
                    </span>
                    <h1 class="preview-title">${data.jabatan}</h1>
                </div>
            </div>
            
            <div class="preview-info-grid">
                <div>
                    <div class="preview-label"><i class="fas fa-money-bill-wave"></i> Estimasi Gaji</div>
                    <div class="preview-value" style="color: #047857; font-weight:700">
                        ${hasSalary ? formatRupiah(data.gaji_min) + ' - ' + formatRupiah(data.gaji_max) : 'Belum tersedia'}
                    </div>
                </div>
                <div>
                    <div class="preview-label"><i class="fas fa-sort-numeric-up"></i> Urutan Tampil</div>
                    <div class="preview-value"><strong>${data.urutan || 0}</strong></div>
                </div>
            </div>
            
            <div class="preview-section">
                <div class="preview-label"><i class="fas fa-align-left"></i> Deskripsi Pekerjaan</div>
                <div class="preview-desc-box">${data.deskripsi || 'Tidak ada deskripsi'}</div>
            </div>
        `;
        
        document.getElementById('previewEditBtn').onclick = function() {
            closePreview();
            setTimeout(() => editProspek(data), 300);
        };
        
        document.getElementById('previewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'sort', 'dir'].forEach(p => url.searchParams.delete(p));
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
            const grid = document.getElementById('prospekGrid');
            const list = document.getElementById('prospekList');
            
            if (view === 'list') {
                grid.style.display = 'none';
                list.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                list.style.display = 'none';
            }
            
            localStorage.setItem('prospek_view', view);
        });
    });

    const savedView = localStorage.getItem('prospek_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');
    const selectAllCheckbox = document.getElementById('selectAllTable');

    document.querySelectorAll('.prospek-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
            updateSelectAllState();
        });
    });

    window.toggleSelectAll = function(checkbox) {
        document.querySelectorAll('.prospek-check').forEach(c => {
            c.checked = checkbox.checked;
            const id = c.value;
            if (checkbox.checked) selectedIds.add(id);
            else selectedIds.delete(id);
        });
        updateBulkBar();
    };

    function updateSelectAllState() {
        const allChecks = document.querySelectorAll('.prospek-check');
        const checkedChecks = document.querySelectorAll('.prospek-check:checked');
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
        document.querySelectorAll('.prospek-check').forEach(c => c.checked = false);
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
        updateBulkBar();
    };

    /* ===== EXPORT CSV ===== */
    window.exportProspek = function() {
        if (!allProspek || allProspek.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['Urutan', 'Jabatan', 'Permintaan', 'Gaji Min', 'Gaji Max', 'Deskripsi', 'Created At'];
        const rows = allProspek.map(p => [
            p.urutan || 0,
            `"${(p.jabatan || '').replace(/"/g, '""')}"`,
            p.permintaan || '',
            p.gaji_min || 0,
            p.gaji_max || 0,
            `"${(p.deskripsi || '').replace(/"/g, '""')}"`,
            p.created_at || ''
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `prospek-karir-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${allProspek.length} prospek berhasil di-export`, 'success');
        }, 500);
    };

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        // N = New
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
    ['prospekModal', 'previewModal'].forEach(id => {
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

    /* ===== AUTO OPEN EDIT MODAL ===== */
    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && $editData): ?>
    window.addEventListener('load', () => {
        setTimeout(() => editProspek(<?= json_encode($editData, JSON_HEX_APOS) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c💼 Prospek Karir v4.0 Loaded', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c   Features: Demand Distribution, Preview Modal, Bulk Actions, Export CSV', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: N (New), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>