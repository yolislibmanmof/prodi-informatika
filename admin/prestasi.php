<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Prestasi';

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
            $stmt = $db->prepare("SELECT gambar FROM prestasi WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!empty($row['gambar'])) {
                deleteUploadedFile((string)$row['gambar'], 'uploads/prestasi');
            }
            $db->prepare("DELETE FROM prestasi WHERE id = ?")->execute([$id]);
            setFlash('success', 'Prestasi berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus prestasi');
        }
        header('Location: prestasi.php');
        exit;
    }
    
    // Bulk delete
    if ($_GET['action'] === 'bulk-delete' && !empty($_POST['selected'])) {
        try {
            $ids = array_map('intval', $_POST['selected']);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            $stmt = $db->prepare("SELECT gambar FROM prestasi WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $row) {
                if (!empty($row['gambar'])) {
                    deleteUploadedFile((string)$row['gambar'], 'uploads/prestasi');
                }
            }
            $db->prepare("DELETE FROM prestasi WHERE id IN ($placeholders)")->execute($ids);
            setFlash('success', count($ids) . ' prestasi berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus prestasi');
        }
        header('Location: prestasi.php');
        exit;
    }
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $newImageUploaded = null;
        $existingImagePath = null;
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $judul = clean($_POST['judul']);
        $pencapaian = clean($_POST['pencapaian']);
        $kategori = clean($_POST['kategori']);
        $tingkat = clean($_POST['tingkat']);
        $nama_tim = clean($_POST['nama_tim']);
        $tahun = (int)$_POST['tahun'];
        $deskripsi = clean($_POST['deskripsi']);
        $urutan = (int)($_POST['urutan'] ?? 0);
        
        $existingImagePath = $id ? getExistingUploadPath($db, 'prestasi', 'gambar', $id) : null;
        $gambar = $existingImagePath;
        if (!empty($_FILES['gambar']['name'])) {
            $newImage = uploadImage($_FILES['gambar'], 'uploads/prestasi');
            if (!$newImage) {
                throw new RuntimeException('Gagal memvalidasi gambar unggahan.');
            }
            $newImageUploaded = $newImage;
            $gambar = $newImage;
        }
        
        if ($id) {
            $stmt = $db->prepare("UPDATE prestasi SET judul=?, pencapaian=?, kategori=?, tingkat=?, nama_tim=?, tahun=?, deskripsi=?, gambar=?, urutan=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$judul, $pencapaian, $kategori, $tingkat, $nama_tim, $tahun, $deskripsi, $gambar, $urutan, $id]);
            setFlash('success', 'Prestasi berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO prestasi (judul, pencapaian, kategori, tingkat, nama_tim, tahun, deskripsi, gambar, urutan, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$judul, $pencapaian, $kategori, $tingkat, $nama_tim, $tahun, $deskripsi, $gambar, $urutan]);
            setFlash('success', 'Prestasi baru berhasil ditambahkan');
        }
        if ($newImageUploaded !== null) {
            if ($existingImagePath !== null && $existingImagePath !== $gambar) {
                deleteUploadedFile((string)$existingImagePath, 'uploads/prestasi');
            }
            $newImageUploaded = null;
        }
    } catch (Throwable $e) {
        if ($newImageUploaded !== null) {
            deleteUploadedFile((string)$newImageUploaded, 'uploads/prestasi');
        }
        setFlash('error', 'Gagal menyimpan: ' . $e->getMessage());
    }
    header('Location: prestasi.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$filterTingkat = $_GET['tingkat'] ?? '';
$filterKategori = $_GET['kategori'] ?? '';
$searchQuery = $_GET['q'] ?? '';
$sortBy = $_GET['sort'] ?? 'tahun';
$sortDir = $_GET['dir'] ?? 'DESC';

$where = "1=1";
$params = [];

if ($filterTingkat) { $where .= " AND tingkat = ?"; $params[] = $filterTingkat; }
if ($filterKategori) { $where .= " AND kategori = ?"; $params[] = $filterKategori; }
if ($searchQuery) { 
    $searchParam = "%$searchQuery%";
    $where .= " AND (judul LIKE ? OR pencapaian LIKE ? OR nama_tim LIKE ?)"; 
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$validSorts = ['tahun', 'judul', 'kategori', 'created_at'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'tahun';
$sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

/* ===== FETCH DATA & STATS ===== */
$prestasiList = [];
$stats = [
    'total' => 0,
    'juara_1' => 0,
    'nasional' => 0,
    'internasional' => 0,
];

if ($db) {
    try {
        $sql = "SELECT * FROM prestasi WHERE $where ORDER BY $sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $prestasiList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM prestasi")->fetchColumn();
        $stats['juara_1'] = (int)$db->query("SELECT COUNT(*) FROM prestasi WHERE pencapaian LIKE '%Juara 1%' OR pencapaian LIKE '%Juara I%' OR pencapaian LIKE '%Gold%'")->fetchColumn();
        $stats['nasional'] = (int)$db->query("SELECT COUNT(*) FROM prestasi WHERE tingkat='nasional'")->fetchColumn();
        $stats['internasional'] = (int)$db->query("SELECT COUNT(*) FROM prestasi WHERE tingkat='internasional'")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== TINGKAT DISTRIBUTION ===== */
$tingkatStats = [];
if ($db) {
    try {
        $tingkatStats = $db->query("
            SELECT tingkat, COUNT(*) as total
            FROM prestasi 
            WHERE tingkat IS NOT NULL
            GROUP BY tingkat
            ORDER BY total DESC
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== TINGKAT CONFIG ===== */
$tingkatConfig = [
    'internasional' => ['icon' => 'fa-globe', 'color' => '#8B5CF6', 'label' => 'Internasional'],
    'nasional' => ['icon' => 'fa-flag', 'color' => '#EF4444', 'label' => 'Nasional'],
    'regional' => ['icon' => 'fa-map', 'color' => '#F59E0B', 'label' => 'Regional'],
    'internal' => ['icon' => 'fa-building', 'color' => '#3B82F6', 'label' => 'Internal'],
];

function getTingkatInfo($tingkat) {
    global $tingkatConfig;
    return $tingkatConfig[$tingkat] ?? $tingkatConfig['internal'];
}

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM prestasi WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content prestasi-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="prestasi-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-trophy-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-trophy"></i>
                    <span>Hall of Fame</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-medal"></i>
                    </span>
                    Kelola Prestasi
                </h1>
                <p class="hero-subtitle">
                    Manajemen prestasi mahasiswa dan program studi
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-trophy"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total</span>
                    </div>
                    <div class="hms-item gold">
                        <i class="fas fa-crown"></i>
                        <span><strong><?= $stats['juara_1'] ?></strong> Juara 1</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-flag"></i>
                        <span><strong><?= $stats['nasional'] ?></strong> Nasional</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportPrestasi()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Prestasi</span>
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
    <div class="prestasi-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="prestasi-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid prestasi-stats">
                <div class="stat-card stat-amber">
                    <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Prestasi</p>
                        <span class="stat-trend up"><i class="fas fa-star"></i> Achievements</span>
                    </div>
                </div>

                <div class="stat-card stat-yellow">
                    <div class="stat-icon"><i class="fas fa-crown"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['juara_1'] ?>">0</h3>
                        <p>Juara 1 / Gold</p>
                        <span class="stat-trend up"><i class="fas fa-medal"></i> Top Tier</span>
                    </div>
                </div>

                <div class="stat-card stat-rose">
                    <div class="stat-icon"><i class="fas fa-flag"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['nasional'] ?>">0</h3>
                        <p>Tingkat Nasional</p>
                        <span class="stat-trend up"><i class="fas fa-map-marker-alt"></i> National</span>
                    </div>
                </div>

                <div class="stat-card stat-violet">
                    <div class="stat-icon"><i class="fas fa-globe"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['internasional'] ?>">0</h3>
                        <p>Internasional</p>
                        <span class="stat-trend up"><i class="fas fa-globe-americas"></i> Global</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card prestasi-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari judul, pencapaian, atau nama tim..." value="<?= clean($searchQuery) ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterTingkat" class="toolbar-select">
                            <option value="">🌍 Semua Tingkat</option>
                            <option value="internasional" <?= $filterTingkat === 'internasional' ? 'selected' : '' ?>>🌐 Internasional</option>
                            <option value="nasional" <?= $filterTingkat === 'nasional' ? 'selected' : '' ?>>🇮🇩 Nasional</option>
                            <option value="regional" <?= $filterTingkat === 'regional' ? 'selected' : '' ?>>🗺️ Regional</option>
                            <option value="internal" <?= $filterTingkat === 'internal' ? 'selected' : '' ?>>🏫 Internal</option>
                        </select>
                        
                        <select id="filterKategori" class="toolbar-select">
                            <option value="">🏷️ Semua Kategori</option>
                            <option value="kompetisi" <?= $filterKategori === 'kompetisi' ? 'selected' : '' ?>>🏆 Kompetisi</option>
                            <option value="akademik" <?= $filterKategori === 'akademik' ? 'selected' : '' ?>>📚 Akademik</option>
                            <option value="nonakademik" <?= $filterKategori === 'nonakademik' ? 'selected' : '' ?>>🎨 Non-Akademik</option>
                            <option value="penelitian" <?= $filterKategori === 'penelitian' ? 'selected' : '' ?>>🔬 Penelitian</option>
                        </select>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <div class="sort-dropdown">
                        <button class="toolbar-icon-btn" id="sortBtn" title="Urutkan">
                            <i class="fas fa-sort-amount-down"></i>
                        </button>
                        <div class="sort-menu" id="sortMenu">
                            <a href="?sort=tahun&dir=DESC" class="sort-item <?= $sortBy === 'tahun' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-calendar-alt"></i> Tahun Terbaru
                            </a>
                            <a href="?sort=tahun&dir=ASC" class="sort-item <?= $sortBy === 'tahun' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-calendar-alt"></i> Tahun Terlama
                            </a>
                            <a href="?sort=judul&dir=ASC" class="sort-item <?= $sortBy === 'judul' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Judul A-Z
                            </a>
                            <a href="?sort=created_at&dir=DESC" class="sort-item <?= $sortBy === 'created_at' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-history"></i> Input Terbaru
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
                    <span><strong id="selectedCount">0</strong> prestasi dipilih</span>
                </div>
                <div class="bulk-actions">
                    <button type="submit" formaction="?action=bulk-delete" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus prestasi yang dipilih?')">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        Batal
                    </button>
                </div>
            </div>

            <!-- Prestasi Grid -->
            <div class="prestasi-grid" id="prestasiGrid" data-view="grid">
                <?php if (!empty($prestasiList)): ?>
                    <?php foreach ($prestasiList as $p): 
                        $tingkatCfg = getTingkatInfo($p['tingkat']);
                        $isJuara1 = stripos($p['pencapaian'], 'juara 1') !== false || stripos($p['pencapaian'], 'juara i') !== false || stripos($p['pencapaian'], 'gold') !== false;
                    ?>
                    <div class="prestasi-card <?= $isJuara1 ? 'is-juara-1' : '' ?>" data-id="<?= $p['id'] ?>">
                        <!-- Checkbox -->
                        <label class="card-checkbox">
                            <input type="checkbox" name="selected[]" class="prestasi-check" value="<?= $p['id'] ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <!-- Image Area -->
                        <div class="pc-image" onclick="previewPrestasi(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)">
                            <?php if ($p['gambar']): ?>
                                <img src="../uploads/<?= htmlspecialchars($p['gambar']) ?>" alt="<?= clean($p['judul']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="pc-placeholder" style="background: linear-gradient(135deg, <?= $tingkatCfg['color'] ?>, <?= $tingkatCfg['color'] ?>dd)">
                                    <i class="fas fa-trophy"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="pc-overlay">
                                <div class="pc-quick-actions" onclick="event.stopPropagation()">
                                    <button onclick="previewPrestasi(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editPrestasi(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=delete&id=<?= $p['id'] ?>" title="Hapus" data-confirm="Yakin hapus prestasi ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Badges -->
                            <div class="pc-badges">
                                <span class="pc-tingkat-badge" style="background: <?= $tingkatCfg['color'] ?>">
                                    <i class="fas <?= $tingkatCfg['icon'] ?>"></i>
                                    <?= $tingkatCfg['label'] ?>
                                </span>
                                <?php if ($isJuara1): ?>
                                <span class="pc-juara-badge">
                                    <i class="fas fa-crown"></i> Juara 1
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="pc-body">
                            <div class="pc-header">
                                <h3 class="pc-title"><?= clean($p['judul']) ?></h3>
                                <span class="pc-pencapaian" style="color: <?= $isJuara1 ? '#F59E0B' : $tingkatCfg['color'] ?>">
                                    <i class="fas fa-medal"></i> <?= clean($p['pencapaian']) ?>
                                </span>
                            </div>
                            
                            <?php if ($p['nama_tim']): ?>
                            <div class="pc-team">
                                <i class="fas fa-users"></i>
                                <span><?= clean($p['nama_tim']) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="pc-footer">
                                <div class="pc-meta">
                                    <span class="pc-kategori">
                                        <i class="fas fa-tag"></i> <?= ucfirst($p['kategori']) ?>
                                    </span>
                                    <span class="pc-tahun">
                                        <i class="fas fa-calendar"></i> <?= $p['tahun'] ?>
                                    </span>
                                </div>
                                <button class="pc-edit-btn" onclick="editPrestasi(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" style="background: <?= $tingkatCfg['color'] ?>">
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
                                <path d="M 70 50 L 130 50 L 130 90 L 70 90 Z" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <path d="M 85 50 L 85 40 L 115 40 L 115 50" fill="var(--gray-200)" stroke="var(--gray-300)" stroke-width="2"/>
                                <circle cx="100" cy="70" r="12" fill="#F59E0B" opacity="0.3"/>
                                <path d="M 96 70 L 100 74 L 104 66" stroke="#F59E0B" stroke-width="2" fill="none"/>
                                <circle cx="155" cy="45" r="18" fill="#F59E0B" opacity="0.9"/>
                                <path d="M 148 45 L 162 45 M 155 38 L 155 52" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Belum ada prestasi</h3>
                        <p>Mulai tambahkan prestasi mahasiswa untuk ditampilkan di website</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-plus"></i> Tambah Prestasi Pertama
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- List View (Table) -->
            <?php if (!empty($prestasiList)): ?>
            <div class="prestasi-list-view" id="prestasiList" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="selectAllTable"></th>
                                    <th>Gambar</th>
                                    <th>Judul & Pencapaian</th>
                                    <th>Tim / Mahasiswa</th>
                                    <th>Kategori</th>
                                    <th>Tingkat</th>
                                    <th>Tahun</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prestasiList as $p): 
                                    $tingkatCfg = getTingkatInfo($p['tingkat']);
                                    $isJuara1 = stripos($p['pencapaian'], 'juara 1') !== false || stripos($p['pencapaian'], 'juara i') !== false || stripos($p['pencapaian'], 'gold') !== false;
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="selected[]" class="prestasi-check" value="<?= $p['id'] ?>"></td>
                                    <td>
                                        <?php if ($p['gambar']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($p['gambar']) ?>" alt="" style="width:50px;height:50px;border-radius:8px;object-fit:cover;cursor:pointer" onclick="previewPrestasi(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)">
                                        <?php else: ?>
                                            <div style="width:50px;height:50px;border-radius:8px;background:linear-gradient(135deg,<?= $tingkatCfg['color'] ?>,<?= $tingkatCfg['color'] ?>dd);display:flex;align-items:center;justify-content:center;color:#fff">
                                                <i class="fas fa-trophy"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= clean($p['judul']) ?></strong>
                                        <div style="font-size:0.75rem;color:<?= $isJuara1 ? '#F59E0B' : $tingkatCfg['color'] ?>;margin-top:2px;font-weight:600">
                                            <i class="fas fa-medal"></i> <?= clean($p['pencapaian']) ?>
                                        </div>
                                    </td>
                                    <td style="font-size:0.85rem"><?= clean($p['nama_tim'] ?: '-') ?></td>
                                    <td>
                                        <span class="badge" style="background:var(--gray-100);color:var(--gray-700)">
                                            <?= ucfirst($p['kategori']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:<?= $tingkatCfg['color'] ?>15;color:<?= $tingkatCfg['color'] ?>">
                                            <i class="fas <?= $tingkatCfg['icon'] ?>"></i> <?= $tingkatCfg['label'] ?>
                                        </span>
                                    </td>
                                    <td><strong><?= $p['tahun'] ?></strong></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-sm" onclick="previewPrestasi(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-sm" onclick="editPrestasi(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS)) ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=delete&id=<?= $p['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus prestasi ini?" title="Hapus">
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
                <span>Menampilkan <strong><?= count($prestasiList) ?></strong> prestasi</span>
            </div>
            <?php endif; ?>
            </form>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="prestasi-sidebar">
            <!-- Tingkat Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribusi Tingkat</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($tingkatStats)): ?>
                    <div class="tingkat-donut">
                        <svg viewBox="0 0 36 36" class="donut-ring">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--gray-100)" stroke-width="3.8"/>
                            <?php 
                            $total = array_sum(array_column($tingkatStats, 'total')) ?: 1;
                            $offset = 0;
                            foreach ($tingkatStats as $ts):
                                $cfg = getTingkatInfo($ts['tingkat']);
                                $pct = ($ts['total'] / $total) * 100;
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
                    <div class="tingkat-legend">
                        <?php foreach ($tingkatStats as $ts): 
                            $cfg = getTingkatInfo($ts['tingkat']);
                            $pct = round(($ts['total'] / $total) * 100);
                        ?>
                        <div class="tl-item">
                            <span class="tl-dot" style="background: <?= $cfg['color'] ?>"></span>
                            <span class="tl-name">
                                <i class="fas <?= $cfg['icon'] ?>"></i>
                                <?= $cfg['label'] ?>
                            </span>
                            <span class="tl-count"><?= $ts['total'] ?> (<?= $pct ?>%)</span>
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

            <!-- Quick Actions -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="widget-body">
                    <button class="qa-btn primary" onclick="openModal()">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Prestasi</span>
                    </button>
                    <a href="alumni.php" class="qa-btn">
                        <i class="fas fa-user-graduate"></i>
                        <span>Data Alumni</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="mitra.php" class="qa-btn">
                        <i class="fas fa-handshake"></i>
                        <span>Mitra Kerja Sama</span>
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
                        <div class="tip-text">Gunakan foto dokumentasi saat menerima penghargaan</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Sebutkan nama tim atau mahasiswa dengan lengkap</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Pilih tingkat kompetisi yang sesuai untuk akurasi data</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== PRESTASI MODAL ===== -->
<div class="modal" id="prestasiModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient prestasi-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-trophy"></i></div>
                <div>
                    <h3 id="modalTitle">Tambah Prestasi</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px" id="modalSubtitle">Lengkapi data prestasi mahasiswa/prodi</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="prestasiForm" class="modal-body">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" id="formId">
            <input type="hidden" name="gambar_lama" id="gambarLama">
            
            <div class="form-sections">
                <!-- Basic Info -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-info-circle"></i> Informasi Prestasi</h4>
                    <div class="form-grid-2">
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Judul Prestasi <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-trophy"></i>
                                <input type="text" name="judul" id="judul" required placeholder="Contoh: Gemastik XV Divisi Pemrograman">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Pencapaian <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-medal"></i>
                                <input type="text" name="pencapaian" id="pencapaian" required placeholder="Contoh: Juara 1">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Tahun <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-calendar"></i>
                                <input type="number" name="tahun" id="tahun" required min="2000" max="2035" value="<?= date('Y') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-layer-group"></i> Detail Kompetisi</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="field-label">Kategori <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-tag"></i>
                                <select name="kategori" id="kategori" required>
                                    <option value="kompetisi">🏆 Kompetisi</option>
                                    <option value="akademik">📚 Akademik</option>
                                    <option value="nonakademik">🎨 Non-Akademik</option>
                                    <option value="penelitian">🔬 Penelitian</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Tingkat <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-layer-group"></i>
                                <select name="tingkat" id="tingkat" required>
                                    <option value="internasional">🌐 Internasional</option>
                                    <option value="nasional">🇮🇩 Nasional</option>
                                    <option value="regional">🗺️ Regional</option>
                                    <option value="internal">🏫 Internal</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Nama Tim / Mahasiswa</label>
                            <div class="input-icon">
                                <i class="fas fa-users"></i>
                                <input type="text" name="nama_tim" id="nama_tim" placeholder="Contoh: Tim Binary Storm (3 mahasiswa)">
                            </div>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Deskripsi</label>
                            <div class="textarea-wrap">
                                <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Deskripsi singkat tentang prestasi..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-image"></i> Dokumentasi</h4>
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
                    <i class="fas fa-save"></i> <span id="submitText">Simpan Prestasi</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== PREVIEW MODAL ===== -->
<div class="modal" id="previewModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient prestasi-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-eye"></i></div>
                <div>
                    <h3>Detail Prestasi</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Informasi lengkap pencapaian</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePreview()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="previewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePreview()">Tutup</button>
            <button class="btn btn-primary" id="previewEditBtn"><i class="fas fa-edit"></i> Edit Prestasi</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   PRESTASI PAGE - ACHIEVEMENT MANAGER EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.prestasi-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.prestasi-hero {
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

.hero-trophy-pattern {
    position: absolute;
    top: 50%;
    right: 5%;
    transform: translateY(-50%);
    font-size: 18rem;
    color: rgba(255,255,255,0.04);
    pointer-events: none;
}

.hero-trophy-pattern::before {
    content: '\f091';
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

.hms-item.gold {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.3);
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
.prestasi-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.prestasi-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.prestasi-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-amber .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }
.stat-yellow .stat-icon { background: linear-gradient(135deg, #FCD34D, #F59E0B); }
.stat-rose .stat-icon { background: linear-gradient(135deg, #F43F5E, #BE123C); }
.stat-violet .stat-icon { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }

/* ===== TOOLBAR ===== */
.prestasi-toolbar {
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
    color: #F59E0B;
    box-shadow: var(--shadow-sm);
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

/* ===== PRESTASI GRID ===== */
.prestasi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.prestasi-card {
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

[data-theme="dark"] .prestasi-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.prestasi-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(245,158,11,0.3);
}

.prestasi-card.is-juara-1 {
    border-color: #F59E0B;
    box-shadow: 0 4px 20px rgba(245,158,11,0.15);
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

.prestasi-card:hover .card-checkbox,
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

/* Card Image */
.pc-image {
    position: relative;
    height: 180px;
    background: var(--gray-100);
    overflow: hidden;
    cursor: pointer;
}

.pc-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.prestasi-card:hover .pc-image img {
    transform: scale(1.08);
}

.pc-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: var(--white);
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

.prestasi-card:hover .pc-overlay {
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

/* Badges */
.pc-badges {
    position: absolute;
    top: 12px;
    right: 12px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    z-index: 12;
}

.pc-tingkat-badge,
.pc-juara-badge {
    padding: 4px 10px;
    color: var(--white);
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.pc-juara-badge {
    background: linear-gradient(135deg, #F59E0B, #D97706);
}

/* Card Body */
.pc-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.pc-header {
    margin-bottom: 12px;
}

.pc-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 6px;
    line-height: 1.3;
}

[data-theme="dark"] .pc-title {
    color: var(--white);
}

.pc-pencapaian {
    font-size: 0.82rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.pc-team {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    color: var(--gray-600);
    margin-bottom: 12px;
    padding: 6px 10px;
    background: var(--gray-50);
    border-radius: 8px;
}

[data-theme="dark"] .pc-team {
    background: rgba(255,255,255,0.04);
    color: var(--gray-400);
}

.pc-team i {
    color: #F59E0B;
}

.pc-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 14px;
    border-top: 1px solid var(--gray-100);
    margin-top: auto;
}

[data-theme="dark"] .pc-footer {
    border-color: rgba(255,255,255,0.08);
}

.pc-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.pc-kategori,
.pc-tahun {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: var(--gray-600);
}

.pc-kategori i,
.pc-tahun i {
    color: var(--gray-500);
    font-size: 0.7rem;
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
.prestasi-list-view .data-table {
    font-size: 0.85rem;
}

.prestasi-list-view .data-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 700;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .prestasi-list-view .data-table th {
    background: var(--dark);
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

.prestasi-list-view .data-table td {
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
    color: #F59E0B;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== SIDEBAR WIDGETS ===== */
.prestasi-sidebar {
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

/* ===== TINGKAT DONUT ===== */
.tingkat-donut {
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

.tingkat-legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.tl-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
}

.tl-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.tl-name {
    flex: 1;
    color: var(--dark);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.tl-name i {
    font-size: 0.75rem;
    color: var(--gray-500);
}

[data-theme="dark"] .tl-name {
    color: var(--gray-200);
}

.tl-count {
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.78rem;
}

[data-theme="dark"] .tl-count {
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
    background: linear-gradient(135deg, #F59E0B, #D97706);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(245,158,11,0.3);
}

.qa-btn.primary:hover {
    box-shadow: 0 6px 16px rgba(245,158,11,0.4);
}

.qa-btn i:first-child {
    color: #F59E0B;
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
.prestasi-modal-head {
    background: linear-gradient(135deg, #78350F 0%, #D97706 60%, #F59E0B 100%) !important;
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
    border-color: #F59E0B;
    background: rgba(245,158,11,0.04);
}

.upload-large.dragover {
    border-color: #F59E0B;
    background: rgba(245,158,11,0.08);
    transform: scale(1.01);
}

.upload-placeholder-large {
    color: var(--gray-600);
}

.upload-icon-large {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(180,83,9,0.1));
    color: #F59E0B;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 14px;
    box-shadow: 0 8px 20px rgba(245,158,11,0.2);
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
    height: 250px;
    background: var(--gray-100);
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
    color: var(--white);
}

.preview-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent 60%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 24px;
    color: var(--white);
}

.preview-badges {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}

.preview-tingkat-badge,
.preview-juara-badge {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--white);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.preview-juara-badge {
    background: linear-gradient(135deg, #F59E0B, #D97706);
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
    background: rgba(245,158,11,0.05);
    border-left: 4px solid #F59E0B;
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
    .prestasi-layout {
        grid-template-columns: 1fr;
    }
    
    .prestasi-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .prestasi-page {
        padding: 16px;
    }
    
    .prestasi-hero {
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
    
    .prestasi-toolbar {
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
    
    .prestasi-grid {
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
    .prestasi-hero,
    .prestasi-sidebar,
    .prestasi-toolbar,
    .bulk-bar,
    .pc-quick-actions,
    .card-checkbox {
        display: none !important;
    }
    
    .prestasi-layout {
        grid-template-columns: 1fr;
    }
    
    .prestasi-card {
        break-inside: avoid;
    }
}
</style>

<script>
(function() {
    'use strict';

    const allPrestasi = <?= json_encode($prestasiList) ?>;
    const tingkatConfig = <?= json_encode($tingkatConfig) ?>;

    function getTingkatInfo(tingkat) {
        return tingkatConfig[tingkat] || tingkatConfig['internal'];
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
        document.getElementById('prestasiForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('gambarLama').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Prestasi';
        document.getElementById('modalSubtitle').textContent = 'Lengkapi data prestasi mahasiswa/prodi';
        document.getElementById('submitText').textContent = 'Simpan Prestasi';
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('uploadPlaceholder').style.display = 'block';
        document.getElementById('tahun').value = new Date().getFullYear();
        
        document.getElementById('prestasiModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('judul')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('prestasiModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.closePreview = function() {
        document.getElementById('previewModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.editPrestasi = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Prestasi';
        document.getElementById('modalSubtitle').textContent = 'Perbarui data prestasi';
        document.getElementById('submitText').textContent = 'Perbarui Prestasi';
        document.getElementById('formId').value = data.id;
        document.getElementById('judul').value = data.judul;
        document.getElementById('pencapaian').value = data.pencapaian;
        document.getElementById('tahun').value = data.tahun;
        document.getElementById('kategori').value = data.kategori;
        document.getElementById('tingkat').value = data.tingkat;
        document.getElementById('nama_tim').value = data.nama_tim || '';
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
        
        document.getElementById('prestasiModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== PREVIEW MODAL ===== */
    window.previewPrestasi = function(data) {
        const tingkatCfg = getTingkatInfo(data.tingkat);
        const isJuara1 = /juara\s*1|juara\s*i|gold/i.test(data.pencapaian);
        
        const body = document.getElementById('previewBody');
        body.innerHTML = `
            <div class="preview-hero" style="background: ${data.gambar ? 'var(--gray-100)' : `linear-gradient(135deg, ${tingkatCfg.color}, ${tingkatCfg.color}dd)`}">
                ${data.gambar 
                    ? `<img src="../uploads/${data.gambar}" alt="${data.judul}">`
                    : `<div class="preview-hero-placeholder"><i class="fas fa-trophy"></i></div>`
                }
                <div class="preview-overlay">
                    <div class="preview-badges">
                        <span class="preview-tingkat-badge" style="background: ${tingkatCfg.color}">
                            <i class="fas ${tingkatCfg.icon}"></i> ${tingkatCfg.label}
                        </span>
                        ${isJuara1 ? `<span class="preview-juara-badge"><i class="fas fa-crown"></i> Juara 1</span>` : ''}
                    </div>
                    <h1 class="preview-title">${data.judul}</h1>
                </div>
            </div>
            
            <div class="preview-info-grid">
                <div>
                    <div class="preview-label"><i class="fas fa-medal"></i> Pencapaian</div>
                    <div class="preview-value" style="color: ${isJuara1 ? '#F59E0B' : tingkatCfg.color}; font-weight:700">${data.pencapaian}</div>
                </div>
                <div>
                    <div class="preview-label"><i class="fas fa-calendar"></i> Tahun</div>
                    <div class="preview-value"><strong>${data.tahun}</strong></div>
                </div>
                ${data.nama_tim ? `
                <div style="grid-column: 1 / -1">
                    <div class="preview-label"><i class="fas fa-users"></i> Tim / Mahasiswa</div>
                    <div class="preview-value">${data.nama_tim}</div>
                </div>
                ` : ''}
                <div>
                    <div class="preview-label"><i class="fas fa-tag"></i> Kategori</div>
                    <div class="preview-value">${data.kategori ? data.kategori.charAt(0).toUpperCase() + data.kategori.slice(1) : '-'}</div>
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
            setTimeout(() => editPrestasi(data), 300);
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
    const filterTingkat = document.getElementById('filterTingkat');
    const filterKategori = document.getElementById('filterKategori');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    [filterTingkat, filterKategori].forEach(el => {
        el?.addEventListener('change', applyFilters);
    });

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const tingkat = filterTingkat?.value;
        const kategori = filterKategori?.value;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (tingkat) url.searchParams.set('tingkat', tingkat); else url.searchParams.delete('tingkat');
        if (kategori) url.searchParams.set('kategori', kategori); else url.searchParams.delete('kategori');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'tingkat', 'kategori', 'sort', 'dir'].forEach(p => url.searchParams.delete(p));
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
            const grid = document.getElementById('prestasiGrid');
            const list = document.getElementById('prestasiList');
            
            if (view === 'list') {
                grid.style.display = 'none';
                list.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                list.style.display = 'none';
            }
            
            localStorage.setItem('prestasi_view', view);
        });
    });

    const savedView = localStorage.getItem('prestasi_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');
    const selectAllCheckbox = document.getElementById('selectAllTable');

    document.querySelectorAll('.prestasi-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
            updateSelectAllState();
        });
    });

    window.toggleSelectAll = function(checkbox) {
        document.querySelectorAll('.prestasi-check').forEach(c => {
            c.checked = checkbox.checked;
            const id = c.value;
            if (checkbox.checked) selectedIds.add(id);
            else selectedIds.delete(id);
        });
        updateBulkBar();
    };

    function updateSelectAllState() {
        const allChecks = document.querySelectorAll('.prestasi-check');
        const checkedChecks = document.querySelectorAll('.prestasi-check:checked');
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
        document.querySelectorAll('.prestasi-check').forEach(c => c.checked = false);
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
        updateBulkBar();
    };

    /* ===== EXPORT CSV ===== */
    window.exportPrestasi = function() {
        if (!allPrestasi || allPrestasi.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['Judul', 'Pencapaian', 'Kategori', 'Tingkat', 'Nama Tim', 'Tahun', 'Deskripsi', 'Created At'];
        const rows = allPrestasi.map(p => [
            `"${(p.judul || '').replace(/"/g, '""')}"`,
            `"${(p.pencapaian || '').replace(/"/g, '""')}"`,
            p.kategori || '',
            p.tingkat || '',
            `"${(p.nama_tim || '').replace(/"/g, '""')}"`,
            p.tahun || '',
            `"${(p.deskripsi || '').replace(/"/g, '""')}"`,
            p.created_at || ''
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `prestasi-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${allPrestasi.length} prestasi berhasil di-export`, 'success');
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
    ['prestasiModal', 'previewModal'].forEach(id => {
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
        setTimeout(() => editPrestasi(<?= json_encode($editData, JSON_HEX_APOS) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c🏆 Prestasi v4.0 Loaded', 'color:#F59E0B;font-weight:bold;font-size:14px');
    console.log('%c   Features: Tingkat Distribution, Preview Modal, Bulk Actions, Export CSV', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: N (New), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>