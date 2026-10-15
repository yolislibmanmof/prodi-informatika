<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Konsentrasi';

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
            $check = $db->prepare("SELECT COUNT(*) FROM mata_kuliah WHERE konsentrasi_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                setFlash('error', 'Konsentrasi tidak bisa dihapus karena masih digunakan oleh mata kuliah');
            } else {
                $db->prepare("DELETE FROM konsentrasi WHERE id = ?")->execute([$id]);
                setFlash('success', 'Konsentrasi berhasil dihapus');
            }
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus konsentrasi');
        }
        header('Location: konsentrasi.php');
        exit;
    }
    
    if ($_GET['action'] === 'toggle' && $id) {
        try {
            $db->prepare("UPDATE konsentrasi SET status = IF(status='aktif','nonaktif','aktif'), updated_at = NOW() WHERE id = ?")->execute([$id]);
            setFlash('success', 'Status konsentrasi berhasil diubah');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mengubah status');
        }
        header('Location: konsentrasi.php');
        exit;
    }
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $kode = clean($_POST['kode']);
        $nama = clean($_POST['nama']);
        $deskripsi = clean($_POST['deskripsi']);
        $ikon = clean($_POST['ikon']);
        $warna = clean($_POST['warna']);
        $urutan = (int)$_POST['urutan'];
        $status = clean($_POST['status']);
        
        if ($id) {
            $stmt = $db->prepare("UPDATE konsentrasi SET kode=?, nama=?, deskripsi=?, ikon=?, warna=?, urutan=?, status=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$kode, $nama, $deskripsi, $ikon, $warna, $urutan, $status, $id]);
            setFlash('success', 'Konsentrasi berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO konsentrasi (kode, nama, deskripsi, ikon, warna, urutan, status, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$kode, $nama, $deskripsi, $ikon, $warna, $urutan, $status]);
            setFlash('success', 'Konsentrasi baru berhasil ditambahkan');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan: ' . $e->getMessage());
    }
    header('Location: konsentrasi.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$filterStatus = $_GET['status'] ?? '';
$searchQuery = $_GET['q'] ?? '';
$sortBy = $_GET['sort'] ?? 'urutan';
$sortDir = $_GET['dir'] ?? 'ASC';

$where = "1=1";
$params = [];

if ($filterStatus) { $where .= " AND k.status = ?"; $params[] = $filterStatus; }
if ($searchQuery) { 
    $searchParam = "%$searchQuery%";
    $where .= " AND (k.nama LIKE ? OR k.kode LIKE ? OR k.deskripsi LIKE ?)"; 
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$validSorts = ['urutan', 'nama', 'created_at'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'urutan';
$sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

/* ===== FETCH DATA ===== */
$konsentrasiList = [];
$stats = [
    'total' => 0,
    'aktif' => 0,
    'nonaktif' => 0,
    'total_mk' => 0,
];

if ($db) {
    try {
        $sql = "SELECT k.*, (SELECT COUNT(*) FROM mata_kuliah WHERE konsentrasi_id = k.id) as total_mk 
                FROM konsentrasi k 
                WHERE $where 
                ORDER BY k.$sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $konsentrasiList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM konsentrasi")->fetchColumn();
        $stats['aktif'] = (int)$db->query("SELECT COUNT(*) FROM konsentrasi WHERE status='aktif'")->fetchColumn();
        $stats['nonaktif'] = (int)$db->query("SELECT COUNT(*) FROM konsentrasi WHERE status='nonaktif'")->fetchColumn();
        $stats['total_mk'] = (int)$db->query("SELECT COUNT(*) FROM mata_kuliah WHERE konsentrasi_id IS NOT NULL")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== TOP KONSENTRASI BY MK ===== */
$topKonsentrasi = [];
if ($db) {
    try {
        $topKonsentrasi = $db->query("
            SELECT k.nama, k.warna, COUNT(m.id) as total_mk
            FROM konsentrasi k
            LEFT JOIN mata_kuliah m ON k.id = m.konsentrasi_id
            GROUP BY k.id, k.nama, k.warna
            ORDER BY total_mk DESC
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM konsentrasi WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content konsentrasi-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="konsentrasi-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-academic-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Academic Specialization</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-layer-group"></i>
                    </span>
                    Kelola Konsentrasi
                </h1>
                <p class="hero-subtitle">
                    Kelompok keahlian dan peminatan mahasiswa untuk kurikulum yang dinamis
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-layer-group"></i>
                        <span><strong><?= $stats['total'] ?></strong> Konsentrasi</span>
                    </div>
                    <div class="hms-item live">
                        <span class="live-dot"></span>
                        <span><strong><?= $stats['aktif'] ?></strong> Aktif</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-book"></i>
                        <span><strong><?= $stats['total_mk'] ?></strong> Mata Kuliah</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportKonsentrasi()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Konsentrasi</span>
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
    <div class="konsentrasi-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="konsentrasi-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid konsentrasi-stats">
                <div class="stat-card stat-indigo">
                    <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Konsentrasi</p>
                        <span class="stat-trend up"><i class="fas fa-graduation-cap"></i> Program</span>
                    </div>
                </div>

                <div class="stat-card stat-emerald">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['aktif'] ?>">0</h3>
                        <p>Konsentrasi Aktif</p>
                        <span class="stat-trend up live-pulse"><i class="fas fa-circle"></i> Available</span>
                    </div>
                </div>

                <div class="stat-card stat-slate">
                    <div class="stat-icon"><i class="fas fa-pause-circle"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['nonaktif'] ?>">0</h3>
                        <p>Nonaktif</p>
                        <span class="stat-trend down"><i class="fas fa-ban"></i> Unavailable</span>
                    </div>
                </div>

                <div class="stat-card stat-amber">
                    <div class="stat-icon"><i class="fas fa-book-open"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total_mk'] ?>">0</h3>
                        <p>Total Mata Kuliah</p>
                        <span class="stat-trend up"><i class="fas fa-chalkboard-teacher"></i> Courses</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card konsentrasi-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama, kode, atau deskripsi..." value="<?= clean($searchQuery) ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterStatus" class="toolbar-select">
                            <option value="">📊 Semua Status</option>
                            <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>🟢 Aktif</option>
                            <option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : '' ?>>⚪ Nonaktif</option>
                        </select>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <div class="sort-dropdown">
                        <button class="toolbar-icon-btn" id="sortBtn" title="Urutkan">
                            <i class="fas fa-sort-amount-down"></i>
                        </button>
                        <div class="sort-menu" id="sortMenu">
                            <a href="?sort=urutan&dir=ASC" class="sort-item <?= $sortBy === 'urutan' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-numeric-up"></i> Urutan ASC
                            </a>
                            <a href="?sort=urutan&dir=DESC" class="sort-item <?= $sortBy === 'urutan' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-numeric-down"></i> Urutan DESC
                            </a>
                            <a href="?sort=nama&dir=ASC" class="sort-item <?= $sortBy === 'nama' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Nama A-Z
                            </a>
                            <a href="?sort=created_at&dir=DESC" class="sort-item <?= $sortBy === 'created_at' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-history"></i> Terbaru
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

            <!-- Konsentrasi Grid -->
            <div class="konsentrasi-grid" id="konsentrasiGrid" data-view="grid">
                <?php if (!empty($konsentrasiList)): ?>
                    <?php foreach ($konsentrasiList as $k): 
                        $isActive = $k['status'] === 'aktif';
                    ?>
                    <div class="konsentrasi-card <?= !$isActive ? 'is-inactive' : '' ?>" data-id="<?= $k['id'] ?>" style="--accent-color: <?= htmlspecialchars($k['warna']) ?>">
                        <!-- Card Top Accent -->
                        <div class="kc-accent" style="background: <?= htmlspecialchars($k['warna']) ?>"></div>
                        
                        <!-- Card Body -->
                        <div class="kc-body">
                            <div class="kc-header">
                                <div class="kc-icon-wrap" style="background: <?= htmlspecialchars($k['warna']) ?>15; color: <?= htmlspecialchars($k['warna']) ?>">
                                    <i class="fas <?= htmlspecialchars($k['ikon'] ?: 'fa-graduation-cap') ?>"></i>
                                </div>
                                <div class="kc-status">
                                    <span class="kc-badge" style="background: <?= $isActive ? 'rgba(16,185,129,0.12)' : 'rgba(107,114,128,0.12)' ?>; color: <?= $isActive ? '#047857' : '#374151' ?>">
                                        <i class="fas fa-<?= $isActive ? 'check-circle' : 'pause-circle' ?>"></i>
                                        <?= ucfirst($k['status']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="kc-info">
                                <span class="kc-kode" style="background: <?= htmlspecialchars($k['warna']) ?>; color: #fff"><?= htmlspecialchars($k['kode']) ?></span>
                                <h3 class="kc-name"><?= clean($k['nama']) ?></h3>
                                <p class="kc-desc"><?= clean(mb_substr($k['deskripsi'], 0, 100)) ?><?= strlen($k['deskripsi']) > 100 ? '...' : '' ?></p>
                            </div>
                            
                            <div class="kc-stats">
                                <div class="kc-stat-item">
                                    <i class="fas fa-book" style="color: <?= htmlspecialchars($k['warna']) ?>"></i>
                                    <span><strong><?= $k['total_mk'] ?></strong> Mata Kuliah</span>
                                </div>
                                <div class="kc-stat-item">
                                    <i class="fas fa-sort-numeric-up" style="color: <?= htmlspecialchars($k['warna']) ?>"></i>
                                    <span>Urutan: <strong><?= $k['urutan'] ?></strong></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card Actions -->
                        <div class="kc-actions">
                            <button onclick="editKonsentrasi(<?= htmlspecialchars(json_encode($k, JSON_HEX_APOS)) ?>)" class="kc-action-btn" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?action=toggle&id=<?= $k['id'] ?>" class="kc-action-btn" title="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>" data-confirm="Ubah status konsentrasi ini?">
                                <i class="fas fa-<?= $isActive ? 'eye-slash' : 'eye' ?>"></i>
                            </a>
                            <a href="?action=delete&id=<?= $k['id'] ?>" class="kc-action-btn danger" title="Hapus" data-confirm="Yakin hapus konsentrasi ini? Jika ada mata kuliah yang terkait, penghapusan akan ditolak.">
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
                                <rect x="50" y="40" width="100" height="70" rx="8" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <rect x="65" y="55" width="70" height="6" rx="3" fill="var(--gray-300)"/>
                                <rect x="65" y="70" width="50" height="6" rx="3" fill="var(--gray-300)"/>
                                <rect x="65" y="85" width="60" height="6" rx="3" fill="var(--gray-300)"/>
                                <circle cx="155" cy="45" r="18" fill="#4F46E5" opacity="0.9"/>
                                <path d="M 148 45 L 162 45 M 155 38 L 155 52" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Belum ada konsentrasi</h3>
                        <p>Tambahkan kelompok keahlian untuk melengkapi struktur kurikulum</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-plus"></i> Tambah Konsentrasi Pertama
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- List View (Table) -->
            <?php if (!empty($konsentrasiList)): ?>
            <div class="konsentrasi-list-view" id="konsentrasiList" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Urutan</th>
                                    <th>Kode</th>
                                    <th>Nama Konsentrasi</th>
                                    <th>Ikon</th>
                                    <th>Warna</th>
                                    <th>Mata Kuliah</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($konsentrasiList as $k): 
                                    $isActive = $k['status'] === 'aktif';
                                ?>
                                <tr>
                                    <td><strong><?= $k['urutan'] ?></strong></td>
                                    <td><span class="badge" style="background:<?= htmlspecialchars($k['warna']) ?>;color:#fff"><?= clean($k['kode']) ?></span></td>
                                    <td>
                                        <strong><?= clean($k['nama']) ?></strong>
                                        <div style="font-size:0.75rem;color:var(--gray-500);margin-top:2px"><?= clean(mb_substr($k['deskripsi'], 0, 50)) ?></div>
                                    </td>
                                    <td><i class="fas <?= htmlspecialchars($k['ikon'] ?: 'fa-graduation-cap') ?>" style="color:<?= htmlspecialchars($k['warna']) ?>"></i></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px">
                                            <div style="width:20px;height:20px;border-radius:4px;background:<?= htmlspecialchars($k['warna']) ?>"></div>
                                            <code style="font-size:0.75rem"><?= htmlspecialchars($k['warna']) ?></code>
                                        </div>
                                    </td>
                                    <td><strong><?= $k['total_mk'] ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?= $isActive ? 'success' : 'default' ?>">
                                            <i class="fas fa-<?= $isActive ? 'check-circle' : 'pause-circle' ?>"></i> <?= ucfirst($k['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-sm" onclick="editKonsentrasi(<?= htmlspecialchars(json_encode($k, JSON_HEX_APOS)) ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=toggle&id=<?= $k['id'] ?>" class="btn-icon btn-sm" title="Toggle">
                                                <i class="fas fa-<?= $isActive ? 'eye-slash' : 'eye' ?>"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $k['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus konsentrasi ini?" title="Hapus">
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
                <span>Menampilkan <strong><?= count($konsentrasiList) ?></strong> konsentrasi</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="konsentrasi-sidebar">
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
                                ['value' => $stats['nonaktif'], 'color' => '#6B7280']
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
                            <span class="sl-dot" style="background: #6B7280"></span>
                            <span class="sl-name">Nonaktif</span>
                            <span class="sl-count"><?= $stats['nonaktif'] ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Konsentrasi by MK -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-trophy"></i> Top Konsentrasi</h3>
                    <span class="widget-badge">By MK</span>
                </div>
                <div class="widget-body">
                    <?php if (!empty($topKonsentrasi)): 
                        $maxMK = max(array_column($topKonsentrasi, 'total_mk')) ?: 1;
                    ?>
                        <?php foreach ($topKonsentrasi as $i => $tk): 
                            $pct = round(($tk['total_mk'] / $maxMK) * 100);
                        ?>
                        <div class="top-konsentrasi-item">
                            <div class="tki-rank">#<?= $i + 1 ?></div>
                            <div class="tki-icon" style="background: <?= htmlspecialchars($tk['warna']) ?>15; color: <?= htmlspecialchars($tk['warna']) ?>">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="tki-info">
                                <div class="tki-name"><?= clean($tk['nama']) ?></div>
                                <div class="tki-bar">
                                    <div class="tki-bar-fill" style="width: <?= $pct ?>%; background: <?= htmlspecialchars($tk['warna']) ?>"></div>
                                </div>
                            </div>
                            <div class="tki-count"><?= $tk['total_mk'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-trophy"></i>
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
                        <span>Tambah Konsentrasi</span>
                    </button>
                    <a href="kurikulum.php" class="qa-btn">
                        <i class="fas fa-book"></i>
                        <span>Kelola Kurikulum</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="mata-kuliah.php" class="qa-btn">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Mata Kuliah</span>
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
                        <div class="tip-text">Gunakan kode singkat yang unik dan mudah diingat</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Atur urutan tampil untuk mengontrol tampilan di website</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="tip-text">Konsentrasi yang sudah punya mata kuliah tidak bisa dihapus</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== KONSENTRASI MODAL ===== -->
<div class="modal" id="konsentrasiModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient konsentrasi-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <h3 id="modalTitle">Tambah Konsentrasi</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Kelompok keahlian mahasiswa</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" id="konsentrasiForm" class="modal-body">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" id="formId">
            
            <div class="form-sections">
                <!-- Basic Info -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-info-circle"></i> Informasi Dasar</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="field-label">Kode Singkat <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-hashtag"></i>
                                <input type="text" name="kode" id="kode" required placeholder="Contoh: DSIS" maxlength="10">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Urutan Tampil <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-sort-numeric-up"></i>
                                <input type="number" name="urutan" id="urutan" required min="0" value="0">
                            </div>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Nama Konsentrasi <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-graduation-cap"></i>
                                <input type="text" name="nama" id="nama" required placeholder="Contoh: Data Science & Intelligent System">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visual & Details -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-palette"></i> Visual & Detail</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="field-label">Ikon (Font Awesome) <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-icons"></i>
                                <input type="text" name="ikon" id="ikon" required value="fa-graduation-cap" placeholder="fa-graduation-cap">
                            </div>
                            <small class="form-hint">Gunakan class icon Font Awesome (tanpa 'fas')</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Warna Aksen <span class="required">*</span></label>
                            <div class="color-picker-group">
                                <input type="color" name="warna" id="warna" value="#10B981" class="color-input">
                                <div class="input-icon" style="flex:1">
                                    <i class="fas fa-palette"></i>
                                    <input type="text" name="warna_text" id="warna_text" value="#10B981" placeholder="#10B981">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Deskripsi</label>
                            <div class="textarea-wrap">
                                <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Penjelasan singkat tentang konsentrasi dan prospeknya..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-toggle-on"></i> Status</h4>
                    <div class="status-toggle-ult">
                        <label class="status-option">
                            <input type="radio" name="status" value="aktif" checked>
                            <div class="status-box draft">
                                <i class="fas fa-check-circle"></i>
                                <span>Aktif</span>
                                <small>Tersedia untuk dipilih mahasiswa</small>
                            </div>
                        </label>
                        <label class="status-option">
                            <input type="radio" name="status" value="nonaktif">
                            <div class="status-box publish">
                                <i class="fas fa-pause-circle"></i>
                                <span>Nonaktif</span>
                                <small>Tidak ditampilkan di website</small>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="submitText">Simpan Konsentrasi</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* ============================================================
   KONSENTRASI PAGE - ACADEMIC MANAGER EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.konsentrasi-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.konsentrasi-hero {
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

.hero-academic-pattern {
    position: absolute;
    top: 50%;
    right: 5%;
    transform: translateY(-50%);
    font-size: 18rem;
    color: rgba(255,255,255,0.04);
    pointer-events: none;
}

.hero-academic-pattern::before {
    content: '\f19d';
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
.konsentrasi-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.konsentrasi-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.konsentrasi-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-indigo .stat-icon { background: linear-gradient(135deg, #4F46E5, #3730A3); }
.stat-emerald .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-slate .stat-icon { background: linear-gradient(135deg, #6B7280, #374151); }
.stat-amber .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }

.live-pulse {
    animation: livePulse 1.5s infinite;
}

@keyframes livePulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* ===== TOOLBAR ===== */
.konsentrasi-toolbar {
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

/* ===== KONSENTRASI GRID ===== */
.konsentrasi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.konsentrasi-card {
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

[data-theme="dark"] .konsentrasi-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.konsentrasi-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: var(--accent-color, #4F46E5);
}

.konsentrasi-card.is-inactive {
    opacity: 0.7;
}

.kc-accent {
    height: 6px;
    width: 100%;
}

.kc-body {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.kc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.kc-icon-wrap {
    width: 50px;
    height: 50px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.kc-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.kc-info {
    flex: 1;
    margin-bottom: 16px;
}

.kc-kode {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 10px;
    letter-spacing: 0.5px;
}

.kc-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 8px;
    line-height: 1.3;
}

[data-theme="dark"] .kc-name {
    color: var(--white);
}

.kc-desc {
    font-size: 0.85rem;
    color: var(--gray-600);
    line-height: 1.5;
}

[data-theme="dark"] .kc-desc {
    color: var(--gray-400);
}

.kc-stats {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-top: 16px;
    border-top: 1px solid var(--gray-100);
}

[data-theme="dark"] .kc-stats {
    border-color: rgba(255,255,255,0.08);
}

.kc-stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    color: var(--gray-600);
}

[data-theme="dark"] .kc-stat-item {
    color: var(--gray-400);
}

.kc-stat-item i {
    width: 16px;
    text-align: center;
}

.kc-stat-item strong {
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .kc-stat-item strong {
    color: var(--white);
}

.kc-actions {
    display: flex;
    gap: 6px;
    padding: 14px 20px;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-100);
}

[data-theme="dark"] .kc-actions {
    background: rgba(0,0,0,0.2);
    border-color: rgba(255,255,255,0.08);
}

.kc-action-btn {
    flex: 1;
    padding: 8px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--gray-200);
    background: var(--white);
    color: var(--gray-700);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all var(--transition-fast);
    font-size: 0.85rem;
}

[data-theme="dark"] .kc-action-btn {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    color: var(--gray-300);
}

.kc-action-btn:hover {
    background: #4F46E5;
    color: var(--white);
    border-color: #4F46E5;
    transform: translateY(-2px);
}

.kc-action-btn.danger:hover {
    background: #EF4444;
    border-color: #EF4444;
}

/* ===== LIST VIEW ===== */
.konsentrasi-list-view .data-table {
    font-size: 0.85rem;
}

.konsentrasi-list-view .data-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 700;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .konsentrasi-list-view .data-table th {
    background: var(--dark);
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

.konsentrasi-list-view .data-table td {
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
    color: #4F46E5;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== SIDEBAR WIDGETS ===== */
.konsentrasi-sidebar {
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

.widget-badge {
    padding: 3px 10px;
    background: #4F46E5;
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

/* ===== TOP KONSENTRASI ===== */
.top-konsentrasi-item {
    display: grid;
    grid-template-columns: 24px 36px 1fr auto;
    gap: 10px;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .top-konsentrasi-item {
    border-color: rgba(255,255,255,0.05);
}

.top-konsentrasi-item:last-child {
    border-bottom: none;
}

.tki-rank {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--gray-400);
    text-align: center;
    font-family: 'Space Grotesk', sans-serif;
}

.top-konsentrasi-item:nth-child(1) .tki-rank { color: #F59E0B; font-size: 1rem; }
.top-konsentrasi-item:nth-child(2) .tki-rank { color: #94A3B8; }
.top-konsentrasi-item:nth-child(3) .tki-rank { color: #CD7F32; }

.tki-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.tki-info {
    min-width: 0;
}

.tki-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

[data-theme="dark"] .tki-name {
    color: var(--gray-200);
}

.tki-bar {
    height: 4px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .tki-bar {
    background: rgba(255,255,255,0.08);
}

.tki-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

.tki-count {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .tki-count {
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

/* ===== MODAL ===== */
.konsentrasi-modal-head {
    background: linear-gradient(135deg, #312E81 0%, #4F46E5 60%, #6366F1 100%) !important;
}

.color-picker-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.color-input {
    width: 50px;
    height: 42px;
    border: 2px solid var(--gray-200);
    border-radius: 8px;
    cursor: pointer;
    background: var(--white);
    padding: 2px;
}

[data-theme="dark"] .color-input {
    border-color: rgba(255,255,255,0.1);
    background: var(--dark-2);
}

.status-toggle-ult {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.status-option {
    cursor: pointer;
    position: relative;
}

.status-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.status-box {
    padding: 16px;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius);
    text-align: center;
    transition: all var(--transition);
    background: var(--white);
}

[data-theme="dark"] .status-box {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
}

.status-box i {
    font-size: 1.3rem;
    margin-bottom: 6px;
    display: block;
}

.status-box span {
    display: block;
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 2px;
}

.status-box small {
    display: block;
    font-size: 0.72rem;
    color: var(--gray-500);
    line-height: 1.3;
}

.status-option input:checked + .status-box {
    border-color: #4F46E5;
    background: rgba(79,70,229,0.05);
}

.status-option input:checked + .status-box i {
    color: #4F46E5;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .konsentrasi-layout {
        grid-template-columns: 1fr;
    }
    
    .konsentrasi-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .konsentrasi-page {
        padding: 16px;
    }
    
    .konsentrasi-hero {
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
    
    .konsentrasi-toolbar {
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
    
    .konsentrasi-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .konsentrasi-hero,
    .konsentrasi-sidebar,
    .konsentrasi-toolbar,
    .kc-actions,
    .card-checkbox {
        display: none !important;
    }
    
    .konsentrasi-layout {
        grid-template-columns: 1fr;
    }
    
    .konsentrasi-card {
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
        document.getElementById('konsentrasiForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Konsentrasi';
        document.getElementById('submitText').textContent = 'Simpan Konsentrasi';
        document.getElementById('warna').value = '#10B981';
        document.getElementById('warna_text').value = '#10B981';
        document.getElementById('konsentrasiModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('kode')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('konsentrasiModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.editKonsentrasi = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Konsentrasi';
        document.getElementById('submitText').textContent = 'Perbarui Konsentrasi';
        document.getElementById('formId').value = data.id;
        document.getElementById('kode').value = data.kode;
        document.getElementById('nama').value = data.nama;
        document.getElementById('deskripsi').value = data.deskripsi || '';
        document.getElementById('ikon').value = data.ikon || 'fa-graduation-cap';
        document.getElementById('warna').value = data.warna || '#10B981';
        document.getElementById('warna_text').value = data.warna || '#10B981';
        document.getElementById('urutan').value = data.urutan || 0;
        document.querySelector(`input[name="status"][value="${data.status}"]`).checked = true;
        
        document.getElementById('konsentrasiModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== COLOR PICKER SYNC ===== */
    const warnaInput = document.getElementById('warna');
    const warnaText = document.getElementById('warna_text');
    
    warnaInput?.addEventListener('input', function() {
        warnaText.value = this.value;
    });
    
    warnaText?.addEventListener('input', function() {
        if (/^#[0-9A-F]{6}$/i.test(this.value)) {
            warnaInput.value = this.value;
        }
    });

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
            const grid = document.getElementById('konsentrasiGrid');
            const list = document.getElementById('konsentrasiList');
            
            if (view === 'list') {
                grid.style.display = 'none';
                list.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                list.style.display = 'none';
            }
            
            localStorage.setItem('konsentrasi_view', view);
        });
    });

    const savedView = localStorage.getItem('konsentrasi_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== EXPORT CSV ===== */
    window.exportKonsentrasi = function() {
        const data = <?= json_encode($konsentrasiList) ?>;
        
        if (!data || data.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['Urutan', 'Kode', 'Nama', 'Deskripsi', 'Ikon', 'Warna', 'Mata Kuliah', 'Status'];
        const rows = data.map(d => [
            d.urutan,
            `"${(d.kode || '').replace(/"/g, '""')}"`,
            `"${(d.nama || '').replace(/"/g, '""')}"`,
            `"${(d.deskripsi || '').replace(/"/g, '""')}"`,
            d.ikon || '',
            d.warna || '',
            d.total_mk || 0,
            d.status || ''
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `konsentrasi-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${data.length} konsentrasi berhasil di-export`, 'success');
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
        }
    });

    /* ===== MODAL BACKDROP CLICK ===== */
    document.getElementById('konsentrasiModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
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
        setTimeout(() => editKonsentrasi(<?= json_encode($editData, JSON_HEX_APOS) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c🎓 Konsentrasi v4.0 Loaded', 'color:#4F46E5;font-weight:bold;font-size:14px');
    console.log('%c   Features: Dynamic Colors, Icon Preview, Top MK Widget, Export CSV', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: N (New), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>