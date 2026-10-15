<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Fasilitas';

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
            $stmt = $db->prepare("SELECT gambar FROM fasilitas WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['gambar'] && file_exists(__DIR__ . '/../uploads/' . $row['gambar'])) {
                @unlink(__DIR__ . '/../uploads/' . $row['gambar']);
            }
            $db->prepare("DELETE FROM fasilitas WHERE id = ?")->execute([$id]);
            setFlash('success', 'Fasilitas berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus fasilitas');
        }
        header('Location: fasilitas.php');
        exit;
    }
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $nama = clean($_POST['nama']);
        $jenis = clean($_POST['jenis']);
        $lokasi = clean($_POST['lokasi']);
        $ikon = clean($_POST['ikon']);
        $kapasitas = (int)($_POST['kapasitas'] ?? 0);
        $status = clean($_POST['status'] ?? 'aktif');
        $deskripsi = clean($_POST['deskripsi']);
        
        $gambar = $_POST['gambar_lama'] ?? null;
        if (!empty($_FILES['gambar']['name'])) {
            $newImage = uploadImage($_FILES['gambar'], 'uploads/fasilitas');
            if ($newImage) {
                if ($gambar && file_exists(__DIR__ . '/../' . $gambar)) {
                    @unlink(__DIR__ . '/../' . $gambar);
                }
                $gambar = $newImage;
            }
        }
        
        if ($id) {
            $stmt = $db->prepare("UPDATE fasilitas SET nama=?, jenis=?, lokasi=?, ikon=?, kapasitas=?, status=?, deskripsi=?, gambar=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$nama, $jenis, $lokasi, $ikon, $kapasitas, $status, $deskripsi, $gambar, $id]);
            setFlash('success', 'Fasilitas berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO fasilitas (nama, jenis, lokasi, ikon, kapasitas, status, deskripsi, gambar, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$nama, $jenis, $lokasi, $ikon, $kapasitas, $status, $deskripsi, $gambar]);
            setFlash('success', 'Fasilitas baru berhasil ditambahkan');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan: ' . $e->getMessage());
    }
    header('Location: fasilitas.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$filterJenis = $_GET['jenis'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$searchQuery = $_GET['q'] ?? '';
$sortBy = $_GET['sort'] ?? 'urutan';
$sortDir = $_GET['dir'] ?? 'ASC';

$where = "1=1";
$params = [];

if ($filterJenis) { $where .= " AND jenis = ?"; $params[] = $filterJenis; }
if ($filterStatus) { $where .= " AND status = ?"; $params[] = $filterStatus; }
if ($searchQuery) { 
    $searchParam = "%$searchQuery%";
    $where .= " AND (nama LIKE ? OR lokasi LIKE ? OR deskripsi LIKE ?)"; 
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$validSorts = ['urutan', 'nama', 'created_at', 'jenis'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'urutan';
$sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

/* ===== FETCH DATA ===== */
$fasilitasList = [];
$stats = [
    'total' => 0,
    'laboratorium' => 0,
    'ruangan' => 0,
    'peralatan' => 0,
    'total_kapasitas' => 0,
];

if ($db) {
    try {
        $sql = "SELECT * FROM fasilitas WHERE $where ORDER BY $sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $fasilitasList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM fasilitas")->fetchColumn();
        $stats['laboratorium'] = (int)$db->query("SELECT COUNT(*) FROM fasilitas WHERE jenis='laboratorium'")->fetchColumn();
        $stats['ruangan'] = (int)$db->query("SELECT COUNT(*) FROM fasilitas WHERE jenis='ruangan'")->fetchColumn();
        $stats['peralatan'] = (int)$db->query("SELECT COUNT(*) FROM fasilitas WHERE jenis='peralatan'")->fetchColumn();
        $stats['total_kapasitas'] = (int)$db->query("SELECT COALESCE(SUM(kapasitas), 0) FROM fasilitas")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== JENIS DISTRIBUTION ===== */
$jenisStats = [];
if ($db) {
    try {
        $jenisStats = $db->query("
            SELECT jenis, COUNT(*) as total, COALESCE(SUM(kapasitas), 0) as total_kapasitas
            FROM fasilitas 
            WHERE jenis IS NOT NULL
            GROUP BY jenis
            ORDER BY total DESC
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== TOP LOCATIONS ===== */
$topLocations = [];
if ($db) {
    try {
        $topLocations = $db->query("
            SELECT lokasi, COUNT(*) as total
            FROM fasilitas 
            WHERE lokasi IS NOT NULL AND lokasi != ''
            GROUP BY lokasi
            ORDER BY total DESC
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== JENIS CONFIG ===== */
$jenisConfig = [
    'laboratorium' => ['icon' => 'fa-flask', 'color' => '#06B6D4', 'label' => 'Laboratorium'],
    'ruangan' => ['icon' => 'fa-door-open', 'color' => '#8B5CF6', 'label' => 'Ruangan'],
    'peralatan' => ['icon' => 'fa-tools', 'color' => '#F59E0B', 'label' => 'Peralatan'],
    'lainnya' => ['icon' => 'fa-box', 'color' => '#6B7280', 'label' => 'Lainnya'],
];

function getJenisInfo($jenis) {
    global $jenisConfig;
    return $jenisConfig[$jenis] ?? $jenisConfig['lainnya'];
}

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM fasilitas WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content fasilitas-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="fasilitas-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-facility-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-building"></i>
                    <span>Campus Infrastructure</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-flask"></i>
                    </span>
                    Kelola Fasilitas
                </h1>
                <p class="hero-subtitle">
                    Laboratorium, ruangan, dan fasilitas pendukung program studi
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-building"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-flask"></i>
                        <span><strong><?= $stats['laboratorium'] ?></strong> Lab</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-door-open"></i>
                        <span><strong><?= $stats['ruangan'] ?></strong> Ruangan</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-users"></i>
                        <span><strong><?= number_format($stats['total_kapasitas']) ?></strong> Kapasitas</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportFasilitas()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Fasilitas</span>
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
    <div class="fasilitas-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="fasilitas-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid fasilitas-stats">
                <div class="stat-card stat-teal">
                    <div class="stat-icon"><i class="fas fa-building"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Fasilitas</p>
                        <span class="stat-trend up"><i class="fas fa-layer-group"></i> Infrastructure</span>
                    </div>
                </div>

                <div class="stat-card stat-cyan">
                    <div class="stat-icon"><i class="fas fa-flask"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['laboratorium'] ?>">0</h3>
                        <p>Laboratorium</p>
                        <span class="stat-trend up"><i class="fas fa-microscope"></i> Research</span>
                    </div>
                </div>

                <div class="stat-card stat-violet">
                    <div class="stat-icon"><i class="fas fa-door-open"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['ruangan'] ?>">0</h3>
                        <p>Ruangan</p>
                        <span class="stat-trend up"><i class="fas fa-chalkboard"></i> Classrooms</span>
                    </div>
                </div>

                <div class="stat-card stat-amber">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total_kapasitas'] ?>">0</h3>
                        <p>Total Kapasitas</p>
                        <span class="stat-trend up"><i class="fas fa-chair"></i> Seats</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card fasilitas-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama, lokasi, atau deskripsi..." value="<?= clean($searchQuery) ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterJenis" class="toolbar-select">
                            <option value="">🏢 Semua Jenis</option>
                            <option value="laboratorium" <?= $filterJenis === 'laboratorium' ? 'selected' : '' ?>>🧪 Laboratorium</option>
                            <option value="ruangan" <?= $filterJenis === 'ruangan' ? 'selected' : '' ?>>🚪 Ruangan</option>
                            <option value="peralatan" <?= $filterJenis === 'peralatan' ? 'selected' : '' ?>>🔧 Peralatan</option>
                            <option value="lainnya" <?= $filterJenis === 'lainnya' ? 'selected' : '' ?>>📦 Lainnya</option>
                        </select>
                        
                        <select id="filterStatus" class="toolbar-select">
                            <option value="">📊 Semua Status</option>
                            <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>🟢 Aktif</option>
                            <option value="maintenance" <?= $filterStatus === 'maintenance' ? 'selected' : '' ?>>🔧 Maintenance</option>
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
                                <i class="fas fa-sort-numeric-up"></i> Urutan
                            </a>
                            <a href="?sort=nama&dir=ASC" class="sort-item <?= $sortBy === 'nama' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Nama A-Z
                            </a>
                            <a href="?sort=jenis&dir=ASC" class="sort-item <?= $sortBy === 'jenis' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-tag"></i> Jenis
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

            <!-- Fasilitas Grid -->
            <div class="fasilitas-grid" id="fasilitasGrid" data-view="grid">
                <?php if (!empty($fasilitasList)): ?>
                    <?php foreach ($fasilitasList as $f): 
                        $jenisCfg = getJenisInfo($f['jenis'] ?? 'lainnya');
                        $isActive = ($f['status'] ?? 'aktif') === 'aktif';
                    ?>
                    <div class="fasilitas-card <?= !$isActive ? 'is-inactive' : '' ?>" data-id="<?= $f['id'] ?? 0 ?>">
                        <!-- Image/Icon Area -->
                        <div class="fc-image" onclick="previewFasilitas(<?= htmlspecialchars(json_encode($f, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                            <?php if (!empty($f['gambar'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($f['gambar']) ?>" alt="<?= clean($f['nama'] ?? 'Fasilitas') ?>" loading="lazy">
                            <?php else: ?>
                                <div class="fc-placeholder" style="background: <?= $jenisCfg['color'] ?>15">
                                    <i class="fas <?= $f['ikon'] ?? $jenisCfg['icon'] ?>" style="color: <?= $jenisCfg['color'] ?>"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="fc-overlay">
                                <div class="fc-quick-actions" onclick="event.stopPropagation()">
                                    <button onclick="previewFasilitas(<?= htmlspecialchars(json_encode($f, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editFasilitas(<?= htmlspecialchars(json_encode($f, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=delete&id=<?= $f['id'] ?? 0 ?>" title="Hapus" data-confirm="Yakin hapus fasilitas ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Type Badge -->
                            <div class="fc-type-badge" style="background: <?= $jenisCfg['color'] ?>">
                                <i class="fas <?= $jenisCfg['icon'] ?>"></i>
                                <?= $jenisCfg['label'] ?>
                            </div>
                            
                            <!-- Status Indicator -->
                            <?php if (!$isActive): ?>
                            <div class="fc-status-badge maintenance">
                                <i class="fas fa-tools"></i>
                                <?= ucfirst($f['status'] ?? 'maintenance') ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Body -->
                        <div class="fc-body">
                            <h3 class="fc-title"><?= clean($f['nama'] ?? 'Tanpa Nama') ?></h3>
                            
                            <?php if (!empty($f['deskripsi'])): ?>
                            <p class="fc-desc"><?= clean(mb_substr($f['deskripsi'], 0, 80)) ?><?= strlen($f['deskripsi']) > 80 ? '...' : '' ?></p>
                            <?php endif; ?>
                            
                            <div class="fc-info">
                                <?php if (!empty($f['lokasi'])): ?>
                                <div class="fc-info-item">
                                    <i class="fas fa-map-marker-alt" style="color: <?= $jenisCfg['color'] ?>"></i>
                                    <span><?= clean($f['lokasi']) ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($f['kapasitas']) && (int)$f['kapasitas'] > 0): ?>
                                <div class="fc-info-item">
                                    <i class="fas fa-users" style="color: <?= $jenisCfg['color'] ?>"></i>
                                    <span><?= (int)$f['kapasitas'] ?> orang</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="fc-footer">
                                <span class="fc-date">
                                    <i class="fas fa-calendar"></i>
                                    <?= function_exists('formatTanggal') ? formatTanggal($f['created_at'] ?? date('Y-m-d H:i:s')) : date('d M Y', strtotime($f['created_at'] ?? 'now')) ?>
                                </span>
                                <button class="fc-edit-btn" onclick="editFasilitas(<?= htmlspecialchars(json_encode($f, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" style="background: <?= $jenisCfg['color'] ?>">
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
                                <rect x="40" y="50" width="120" height="70" rx="4" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <rect x="40" y="50" width="120" height="15" fill="#06B6D4" opacity="0.2"/>
                                <rect x="55" y="75" width="30" height="30" rx="2" fill="var(--gray-200)" stroke="var(--gray-300)" stroke-width="1"/>
                                <rect x="95" y="75" width="30" height="30" rx="2" fill="var(--gray-200)" stroke="var(--gray-300)" stroke-width="1"/>
                                <circle cx="70" cy="90" r="6" fill="#06B6D4" opacity="0.5"/>
                                <circle cx="110" cy="90" r="6" fill="#06B6D4" opacity="0.5"/>
                                <circle cx="155" cy="45" r="18" fill="#06B6D4" opacity="0.9"/>
                                <line x1="148" y1="45" x2="162" y2="45" stroke="white" stroke-width="3" stroke-linecap="round"/>
                                <line x1="155" y1="38" x2="155" y2="52" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Belum ada fasilitas</h3>
                        <p>Tambahkan laboratorium dan fasilitas pendukung prodi</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-plus"></i> Tambah Fasilitas Pertama
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- List View (Table) -->
            <?php if (!empty($fasilitasList)): ?>
            <div class="fasilitas-list-view" id="fasilitasList" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Gambar</th>
                                    <th>Nama</th>
                                    <th>Jenis</th>
                                    <th>Lokasi</th>
                                    <th>Kapasitas</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fasilitasList as $f): 
                                    $jenisCfg = getJenisInfo($f['jenis'] ?? 'lainnya');
                                    $isActive = ($f['status'] ?? 'aktif') === 'aktif';
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($f['gambar'])): ?>
                                            <img src="../uploads/<?= htmlspecialchars($f['gambar']) ?>" alt="" style="width:50px;height:50px;border-radius:8px;object-fit:cover;cursor:pointer" onclick="previewFasilitas(<?= htmlspecialchars(json_encode($f, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                                        <?php else: ?>
                                            <div style="width:50px;height:50px;border-radius:8px;background:<?= $jenisCfg['color'] ?>15;display:flex;align-items:center;justify-content:center;color:<?= $jenisCfg['color'] ?>">
                                                <i class="fas <?= $f['ikon'] ?? $jenisCfg['icon'] ?>" style="font-size:1.2rem"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= clean($f['nama'] ?? 'Tanpa Nama') ?></strong>
                                        <?php if (!empty($f['deskripsi'])): ?>
                                        <div style="font-size:0.72rem;color:var(--gray-500);margin-top:2px"><?= clean(mb_substr($f['deskripsi'], 0, 40)) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:<?= $jenisCfg['color'] ?>15;color:<?= $jenisCfg['color'] ?>">
                                            <i class="fas <?= $jenisCfg['icon'] ?>"></i> <?= $jenisCfg['label'] ?>
                                        </span>
                                    </td>
                                    <td style="font-size:0.85rem"><?= clean($f['lokasi'] ?? '-') ?></td>
                                    <td><strong><?= (int)($f['kapasitas'] ?? 0) ?: '-' ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?= $isActive ? 'success' : 'warning' ?>">
                                            <i class="fas fa-<?= $isActive ? 'check-circle' : 'tools' ?>"></i>
                                            <?= ucfirst($f['status'] ?? 'aktif') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-sm" onclick="previewFasilitas(<?= htmlspecialchars(json_encode($f, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-sm" onclick="editFasilitas(<?= htmlspecialchars(json_encode($f, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=delete&id=<?= $f['id'] ?? 0 ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus fasilitas ini?" title="Hapus">
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
                <span>Menampilkan <strong><?= count($fasilitasList) ?></strong> fasilitas</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="fasilitas-sidebar">
            <!-- Jenis Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribusi Jenis</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($jenisStats)): ?>
                    <div class="jenis-donut">
                        <svg viewBox="0 0 36 36" class="donut-ring">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--gray-100)" stroke-width="3.8"/>
                            <?php 
                            $total = array_sum(array_column($jenisStats, 'total')) ?: 1;
                            $offset = 0;
                            foreach ($jenisStats as $js):
                                $cfg = getJenisInfo($js['jenis']);
                                $pct = ($js['total'] / $total) * 100;
                                $dashArray = ($pct * 2.83) . ', ' . (100 - ($pct * 2.83));
                            ?>
                            <circle cx="18" cy="18" r="15.9" fill="none" 
                                stroke="<?= $cfg['color'] ?>" 
                                stroke-width="3.8"
                                stroke-dasharray="<?= $dashArray ?>"
                                stroke-dashoffset="-<?= $offset ?>"
                                transform="rotate(-90 18 18)"/>
                            <?php 
                                $offset += ($pct * 2.83);
                            endforeach; 
                            ?>
                        </svg>
                        <div class="donut-center">
                            <div class="donut-value"><?= $stats['total'] ?></div>
                            <div class="donut-label">Total</div>
                        </div>
                    </div>
                    <div class="jenis-legend">
                        <?php foreach ($jenisStats as $js): 
                            $cfg = getJenisInfo($js['jenis']);
                            $pct = round(($js['total'] / $total) * 100);
                        ?>
                        <div class="jl-item">
                            <span class="jl-dot" style="background: <?= $cfg['color'] ?>"></span>
                            <span class="jl-name">
                                <i class="fas <?= $cfg['icon'] ?>"></i>
                                <?= $cfg['label'] ?>
                            </span>
                            <span class="jl-count"><?= $js['total'] ?> (<?= $pct ?>%)</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="widget-empty">
                        <i class="fas fa-chart-pie"></i>
                        <p>Belum ada data</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Locations -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Top Lokasi</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($topLocations)): 
                        $maxLoc = max(array_column($topLocations, 'total'));
                    ?>
                        <?php foreach ($topLocations as $i => $loc): 
                            $pct = round(($loc['total'] / $maxLoc) * 100);
                            $locColors = ['#06B6D4', '#8B5CF6', '#F59E0B', '#10B981', '#EC4899'];
                            $color = $locColors[$i % count($locColors)];
                        ?>
                        <div class="location-item">
                            <div class="li-head">
                                <span class="li-name">
                                    <i class="fas fa-map-marker-alt" style="color: <?= $color ?>"></i>
                                    <?= clean($loc['lokasi']) ?>
                                </span>
                                <span class="li-count"><?= $loc['total'] ?></span>
                            </div>
                            <div class="li-bar">
                                <div class="li-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>Belum ada data lokasi</p>
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
                        <span>Tambah Fasilitas</span>
                    </button>
                    <a href="jadwal.php" class="qa-btn">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Jadwal Penggunaan</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="dosen.php" class="qa-btn">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Kelola Dosen</span>
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
                        <div class="tip-text">Tambahkan foto berkualitas untuk setiap fasilitas</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Isi kapasitas untuk membantu penjadwalan</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Update status saat fasilitas dalam maintenance</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-info"></i></div>
                        <div class="tip-text">Gunakan ikon Font Awesome yang relevan</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== FASILITAS MODAL ===== -->
<div class="modal" id="fasilitasModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient fasilitas-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-flask"></i></div>
                <div>
                    <h3 id="modalTitle">Tambah Fasilitas</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px" id="modalSubtitle">Lengkapi data fasilitas prodi</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="fasilitasForm" class="modal-body">
            <input type="hidden" name="id" id="formId">
            <input type="hidden" name="gambar_lama" id="gambarLama">
            
            <div class="form-sections">
                <!-- Basic Info -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-info-circle"></i> Informasi Dasar</h4>
                    <div class="form-grid-2">
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Nama Fasilitas <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-building"></i>
                                <input type="text" name="nama" id="nama" required placeholder="Contoh: Laboratorium Pemrograman">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Jenis <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-tag"></i>
                                <select name="jenis" id="jenis" required>
                                    <option value="laboratorium">🧪 Laboratorium</option>
                                    <option value="ruangan">🚪 Ruangan</option>
                                    <option value="peralatan">🔧 Peralatan</option>
                                    <option value="lainnya">📦 Lainnya</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Status <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-toggle-on"></i>
                                <select name="status" id="status" required>
                                    <option value="aktif">🟢 Aktif</option>
                                    <option value="maintenance">🔧 Maintenance</option>
                                    <option value="nonaktif">⚪ Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location & Details -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-map-marker-alt"></i> Lokasi & Detail</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="field-label">Lokasi</label>
                            <div class="input-icon">
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" name="lokasi" id="lokasi" placeholder="Contoh: Gedung A Lt. 2">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Kapasitas</label>
                            <div class="input-icon">
                                <i class="fas fa-users"></i>
                                <input type="number" name="kapasitas" id="kapasitas" min="0" placeholder="0" value="0">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Ikon (Font Awesome)</label>
                            <div class="input-icon">
                                <i class="fas fa-icons"></i>
                                <input type="text" name="ikon" id="ikon" value="fa-flask" placeholder="fa-flask">
                            </div>
                            <small class="form-hint">Gunakan class icon tanpa prefix 'fas'</small>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Deskripsi</label>
                            <div class="textarea-wrap">
                                <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Deskripsi singkat tentang fasilitas, peralatan yang tersedia, dll..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-image"></i> Gambar Fasilitas</h4>
                    <div class="upload-area upload-large" id="uploadArea">
                        <div class="upload-placeholder-large" id="uploadPlaceholder">
                            <div class="upload-icon-large">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h4>Drag & drop gambar di sini</h4>
                            <p>atau klik untuk memilih file</p>
                            <small class="upload-note">JPG, PNG, WEBP (Max 5MB)</small>
                        </div>
                        <img id="previewImg" class="upload-preview" style="display:none; max-height: 200px; border-radius: var(--radius);">
                        <input type="file" name="gambar" id="gambarInput" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="submitText">Simpan Fasilitas</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== PREVIEW MODAL ===== -->
<div class="modal" id="previewModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient fasilitas-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-eye"></i></div>
                <div>
                    <h3>Detail Fasilitas</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Informasi lengkap fasilitas</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePreview()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="previewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePreview()">Tutup</button>
            <button class="btn btn-primary" id="previewEditBtn"><i class="fas fa-edit"></i> Edit</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   FASILITAS PAGE - FACILITIES MANAGER EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.fasilitas-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.fasilitas-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #164E63 0%, #0891B2 50%, #06B6D4 100%);
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
    background: radial-gradient(circle, #67E8F9, transparent);
    top: -120px; right: 8%;
}

.orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, #22D3EE, transparent);
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

.hero-facility-pattern {
    position: absolute;
    top: 50%;
    right: 5%;
    transform: translateY(-50%);
    font-size: 18rem;
    color: rgba(255,255,255,0.04);
    pointer-events: none;
}

.hero-facility-pattern::before {
    content: '\f0c3';
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

.hms-item i {
    color: #A5F3FC;
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
.fasilitas-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.fasilitas-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.fasilitas-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-teal .stat-icon { background: linear-gradient(135deg, #06B6D4, #0891B2); }
.stat-cyan .stat-icon { background: linear-gradient(135deg, #22D3EE, #06B6D4); }
.stat-violet .stat-icon { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }
.stat-amber .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }

/* ===== TOOLBAR ===== */
.fasilitas-toolbar {
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
    border-color: #06B6D4;
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(6,182,212,0.1);
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
    background: rgba(6,182,212,0.1);
    color: #0891B2;
    font-weight: 600;
}

.sort-item i {
    width: 16px;
    color: var(--gray-500);
}

.sort-item.active i {
    color: #06B6D4;
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
    color: #06B6D4;
    box-shadow: var(--shadow-sm);
}

/* ===== FASILITAS GRID ===== */
.fasilitas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.fasilitas-card {
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

[data-theme="dark"] .fasilitas-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.fasilitas-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(6,182,212,0.3);
}

.fasilitas-card.is-inactive {
    opacity: 0.7;
}

/* Card Image */
.fc-image {
    position: relative;
    height: 180px;
    background: var(--gray-100);
    overflow: hidden;
    cursor: pointer;
}

.fc-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.fasilitas-card:hover .fc-image img {
    transform: scale(1.08);
}

.fc-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
}

.fc-overlay {
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

.fasilitas-card:hover .fc-overlay {
    opacity: 1;
}

.fc-quick-actions {
    display: flex;
    gap: 8px;
}

.fc-quick-actions button,
.fc-quick-actions a {
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

.fc-quick-actions button:hover,
.fc-quick-actions a:hover {
    background: #06B6D4;
    color: var(--white);
    transform: translateY(-3px);
}

/* Type Badge */
.fc-type-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 4px 12px;
    color: var(--white);
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    z-index: 12;
    backdrop-filter: blur(10px);
}

.fc-status-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    z-index: 12;
}

.fc-status-badge.maintenance {
    background: rgba(245,158,11,0.95);
    color: var(--white);
}

/* Card Body */
.fc-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.fc-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 6px;
    line-height: 1.3;
}

[data-theme="dark"] .fc-title {
    color: var(--white);
}

.fc-desc {
    font-size: 0.82rem;
    color: var(--gray-600);
    margin-bottom: 12px;
    line-height: 1.5;
}

[data-theme="dark"] .fc-desc {
    color: var(--gray-400);
}

.fc-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 12px;
    flex: 1;
}

.fc-info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.78rem;
    color: var(--gray-600);
}

[data-theme="dark"] .fc-info-item {
    color: var(--gray-400);
}

.fc-info-item i {
    width: 14px;
    text-align: center;
}

.fc-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid var(--gray-100);
}

[data-theme="dark"] .fc-footer {
    border-color: rgba(255,255,255,0.08);
}

.fc-date {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    color: var(--gray-500);
}

.fc-date i {
    color: #06B6D4;
    font-size: 0.68rem;
}

.fc-edit-btn {
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

.fc-edit-btn:hover {
    transform: translateY(-2px) rotate(5deg);
    box-shadow: 0 4px 12px rgba(6,182,212,0.3);
}

/* ===== LIST VIEW ===== */
.fasilitas-list-view .data-table {
    font-size: 0.85rem;
}

.fasilitas-list-view .data-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 700;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .fasilitas-list-view .data-table th {
    background: var(--dark);
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

.fasilitas-list-view .data-table td {
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
    color: #06B6D4;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== SIDEBAR WIDGETS ===== */
.fasilitas-sidebar {
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

/* ===== JENIS DONUT ===== */
.jenis-donut {
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

.jenis-legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.jl-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
}

.jl-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.jl-name {
    flex: 1;
    color: var(--dark);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.jl-name i {
    font-size: 0.75rem;
    color: var(--gray-500);
}

[data-theme="dark"] .jl-name {
    color: var(--gray-200);
}

.jl-count {
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.78rem;
}

[data-theme="dark"] .jl-count {
    color: var(--white);
}

/* ===== LOCATION ITEMS ===== */
.location-item {
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .location-item {
    border-color: rgba(255,255,255,0.05);
}

.location-item:last-child {
    border-bottom: none;
}

.li-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.li-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 70%;
}

[data-theme="dark"] .li-name {
    color: var(--gray-200);
}

.li-name i {
    font-size: 0.78rem;
}

.li-count {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .li-count {
    color: var(--white);
}

.li-bar {
    height: 5px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .li-bar {
    background: rgba(255,255,255,0.08);
}

.li-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
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
    background: linear-gradient(135deg, #06B6D4, #0891B2);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(6,182,212,0.3);
}

.qa-btn.primary:hover {
    box-shadow: 0 6px 16px rgba(6,182,212,0.4);
}

.qa-btn i:first-child {
    color: #06B6D4;
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
    background: linear-gradient(135deg, rgba(6,182,212,0.04), rgba(8,145,178,0.04));
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
    background: rgba(6,182,212,0.12);
    color: #06B6D4;
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
.fasilitas-modal-head {
    background: linear-gradient(135deg, #164E63 0%, #0891B2 60%, #06B6D4 100%) !important;
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
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

[data-theme="dark"] .upload-large {
    border-color: rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.03);
}

.upload-large:hover {
    border-color: #06B6D4;
    background: rgba(6,182,212,0.04);
}

.upload-large.dragover {
    border-color: #06B6D4;
    background: rgba(6,182,212,0.08);
    transform: scale(1.01);
}

.upload-placeholder-large {
    color: var(--gray-600);
}

.upload-icon-large {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, rgba(6,182,212,0.15), rgba(8,145,178,0.1));
    color: #06B6D4;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 14px;
    box-shadow: 0 8px 20px rgba(6,182,212,0.2);
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

.upload-preview {
    max-width: 100%;
    object-fit: contain;
}

/* ===== PREVIEW MODAL ===== */
.preview-hero {
    position: relative;
    height: 280px;
    background: var(--gray-200);
    overflow: hidden;
    margin: -28px -28px 20px;
    border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
}

.preview-hero img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-hero-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 5rem;
}

.preview-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7), transparent 60%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 24px;
    color: var(--white);
}

.preview-type-badge {
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
}

.preview-title {
    font-size: 1.6rem;
    font-weight: 700;
    line-height: 1.25;
    margin-bottom: 8px;
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
    background: rgba(6,182,212,0.05);
    border-left: 4px solid #06B6D4;
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
    .fasilitas-layout {
        grid-template-columns: 1fr;
    }
    
    .fasilitas-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .fasilitas-page {
        padding: 16px;
    }
    
    .fasilitas-hero {
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
    
    .fasilitas-toolbar {
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
    
    .fasilitas-grid {
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
    .fasilitas-hero,
    .fasilitas-sidebar,
    .fasilitas-toolbar,
    .fc-quick-actions,
    .fc-edit-btn {
        display: none !important;
    }
    
    .fasilitas-layout {
        grid-template-columns: 1fr;
    }
    
    .fasilitas-card {
        break-inside: avoid;
    }
}
</style>

<script>
(function() {
    'use strict';

    const allFasilitas = <?= json_encode($fasilitasList) ?>;

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

    const jenisConfig = <?= json_encode($jenisConfig) ?>;

    function getJenisInfo(jenis) {
        return jenisConfig[jenis] || jenisConfig['lainnya'];
    }

    /* ===== MODAL FUNCTIONS ===== */
    window.openModal = function() {
        document.getElementById('fasilitasForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('gambarLama').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Fasilitas';
        document.getElementById('modalSubtitle').textContent = 'Lengkapi data fasilitas prodi';
        document.getElementById('submitText').textContent = 'Simpan Fasilitas';
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('uploadPlaceholder').style.display = 'block';
        document.getElementById('ikon').value = 'fa-flask';
        
        document.getElementById('fasilitasModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('nama')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('fasilitasModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.closePreview = function() {
        document.getElementById('previewModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.editFasilitas = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Fasilitas';
        document.getElementById('modalSubtitle').textContent = 'Perbarui data fasilitas';
        document.getElementById('submitText').textContent = 'Perbarui Fasilitas';
        document.getElementById('formId').value = data.id;
        document.getElementById('nama').value = data.nama;
        document.getElementById('jenis').value = data.jenis;
        document.getElementById('lokasi').value = data.lokasi || '';
        document.getElementById('kapasitas').value = data.kapasitas || 0;
        document.getElementById('status').value = data.status || 'aktif';
        document.getElementById('ikon').value = data.ikon || 'fa-flask';
        document.getElementById('deskripsi').value = data.deskripsi || '';
        document.getElementById('gambarLama').value = data.gambar || '';
        
        const previewImg = document.getElementById('previewImg');
        if (data.gambar) {
            previewImg.src = '../uploads/' + data.gambar;
            previewImg.style.display = 'block';
            document.getElementById('uploadPlaceholder').style.display = 'none';
        } else {
            previewImg.style.display = 'none';
            document.getElementById('uploadPlaceholder').style.display = 'block';
        }
        
        document.getElementById('fasilitasModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== PREVIEW MODAL ===== */
    window.previewFasilitas = function(data) {
        const jenisCfg = getJenisInfo(data.jenis);
        const isActive = (data.status || 'aktif') === 'aktif';
        
        const body = document.getElementById('previewBody');
        body.innerHTML = `
            <div class="preview-hero" style="background: ${jenisCfg.color}15">
                ${data.gambar 
                    ? `<img src="../uploads/${data.gambar}" alt="${data.nama}">`
                    : `<div class="preview-hero-placeholder" style="color: ${jenisCfg.color}"><i class="fas ${data.ikon || jenisCfg.icon}"></i></div>`
                }
                <div class="preview-overlay">
                    <span class="preview-type-badge" style="background: ${jenisCfg.color}">
                        <i class="fas ${jenisCfg.icon}"></i> ${jenisCfg.label}
                    </span>
                    <h1 class="preview-title">${data.nama}</h1>
                    ${!isActive ? `<div style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;background:rgba(245,158,11,0.9);border-radius:20px;font-size:0.78rem;font-weight:600"><i class="fas fa-tools"></i> ${data.status}</div>` : ''}
                </div>
            </div>
            
            <div class="preview-info-grid">
                ${data.lokasi ? `
                <div>
                    <div class="preview-label"><i class="fas fa-map-marker-alt"></i> Lokasi</div>
                    <div class="preview-value">${data.lokasi}</div>
                </div>
                ` : ''}
                ${data.kapasitas > 0 ? `
                <div>
                    <div class="preview-label"><i class="fas fa-users"></i> Kapasitas</div>
                    <div class="preview-value"><strong style="color:${jenisCfg.color}">${data.kapasitas}</strong> orang</div>
                </div>
                ` : ''}
                <div>
                    <div class="preview-label"><i class="fas fa-toggle-on"></i> Status</div>
                    <div class="preview-value">
                        <span class="badge badge-${isActive ? 'success' : 'warning'}">
                            <i class="fas fa-${isActive ? 'check-circle' : 'tools'}"></i> ${data.status || 'Aktif'}
                        </span>
                    </div>
                </div>
                <div>
                    <div class="preview-label"><i class="fas fa-calendar"></i> Ditambahkan</div>
                    <div class="preview-value">${new Date(data.created_at).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'})}</div>
                </div>
            </div>
            
            ${data.deskripsi ? `
            <div class="preview-section">
                <div class="preview-label"><i class="fas fa-align-left"></i> Deskripsi</div>
                <div class="preview-desc-box">${data.deskripsi}</div>
            </div>
            ` : ''}
        `;
        
        document.getElementById('previewEditBtn').onclick = function() {
            closePreview();
            setTimeout(() => editFasilitas(data), 300);
        };
        
        document.getElementById('previewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== FILE UPLOAD HANDLING ===== */
    const uploadArea = document.getElementById('uploadArea');
    const gambarInput = document.getElementById('gambarInput');
    const previewImg = document.getElementById('previewImg');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');

    gambarInput?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                if (window.IFPro?.toast) window.IFPro.toast.show('Ukuran file maksimal 5MB', 'error');
                gambarInput.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (ev) => {
                previewImg.src = ev.target.result;
                previewImg.style.display = 'block';
                uploadPlaceholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    ['dragenter', 'dragover'].forEach(evt => {
        uploadArea?.addEventListener(evt, e => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(evt => {
        uploadArea?.addEventListener(evt, e => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
    });

    uploadArea?.addEventListener('drop', e => {
        if (e.dataTransfer.files[0]) {
            gambarInput.files = e.dataTransfer.files;
            gambarInput.dispatchEvent(new Event('change'));
        }
    });

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterJenis = document.getElementById('filterJenis');
    const filterStatus = document.getElementById('filterStatus');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    [filterJenis, filterStatus].forEach(el => {
        el?.addEventListener('change', applyFilters);
    });

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const jenis = filterJenis?.value;
        const status = filterStatus?.value;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (jenis) url.searchParams.set('jenis', jenis); else url.searchParams.delete('jenis');
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'jenis', 'status', 'sort', 'dir'].forEach(p => url.searchParams.delete(p));
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
            const grid = document.getElementById('fasilitasGrid');
            const list = document.getElementById('fasilitasList');
            
            if (view === 'list') {
                grid.style.display = 'none';
                list.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                list.style.display = 'none';
            }
            
            localStorage.setItem('fasilitas_view', view);
        });
    });

    const savedView = localStorage.getItem('fasilitas_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== EXPORT CSV ===== */
    window.exportFasilitas = function() {
        if (!allFasilitas || allFasilitas.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['Nama', 'Jenis', 'Lokasi', 'Kapasitas', 'Status', 'Ikon', 'Deskripsi', 'Created At'];
        const rows = allFasilitas.map(f => [
            `"${(f.nama || '').replace(/"/g, '""')}"`,
            f.jenis || '',
            `"${(f.lokasi || '').replace(/"/g, '""')}"`,
            f.kapasitas || 0,
            f.status || 'aktif',
            f.ikon || '',
            `"${(f.deskripsi || '').replace(/"/g, '""')}"`,
            f.created_at || ''
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `fasilitas-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${allFasilitas.length} fasilitas berhasil di-export`, 'success');
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
    ['fasilitasModal', 'previewModal'].forEach(id => {
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
        setTimeout(() => editFasilitas(<?= json_encode($editData, JSON_HEX_APOS) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c🏢 Fasilitas v4.0 Loaded', 'color:#06B6D4;font-weight:bold;font-size:14px');
    console.log('%c   Features: Jenis Distribution, Location Stats, Preview Modal, Export CSV', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: N (New), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>