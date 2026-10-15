<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Mitra';

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
            $stmt = $db->prepare("SELECT logo FROM mitra WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!empty($row['logo'])) {
                deleteUploadedFile((string)$row['logo'], 'uploads/mitra');
            }
            $db->prepare("DELETE FROM mitra WHERE id = ?")->execute([$id]);
            setFlash('success', 'Mitra berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus mitra');
        }
        header('Location: mitra.php');
        exit;
    }
    
    // Bulk delete
    if ($_GET['action'] === 'bulk-delete' && !empty($_POST['selected'])) {
        try {
            $ids = array_map('intval', $_POST['selected']);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            $stmt = $db->prepare("SELECT logo FROM mitra WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $row) {
                if (!empty($row['logo'])) {
                    deleteUploadedFile((string)$row['logo'], 'uploads/mitra');
                }
            }
            $db->prepare("DELETE FROM mitra WHERE id IN ($placeholders)")->execute($ids);
            setFlash('success', count($ids) . ' mitra berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus mitra');
        }
        header('Location: mitra.php');
        exit;
    }
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $newImageUploaded = null;
        $existingImagePath = null;
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $nama = clean($_POST['nama']);
        $jenis = clean($_POST['jenis']);
        $website = clean($_POST['website']);
        $deskripsi = clean($_POST['deskripsi']);
        $urutan = (int)($_POST['urutan'] ?? 0);
        
        $existingImagePath = $id ? getExistingUploadPath($db, 'mitra', 'logo', $id) : null;
        $logo = $existingImagePath;
        if (!empty($_FILES['logo']['name'])) {
            $newImage = uploadImage($_FILES['logo'], 'uploads/mitra');
            if (!$newImage) {
                throw new RuntimeException('Gagal memvalidasi gambar unggahan.');
            }
            $newImageUploaded = $newImage;
            $logo = $newImage;
        }
        
        if ($id) {
            $stmt = $db->prepare("UPDATE mitra SET nama=?, jenis=?, website=?, deskripsi=?, logo=?, urutan=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$nama, $jenis, $website, $deskripsi, $logo, $urutan, $id]);
            setFlash('success', 'Mitra berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO mitra (nama, jenis, website, deskripsi, logo, urutan, created_at) VALUES (?,?,?,?,?,?,NOW())");
            $stmt->execute([$nama, $jenis, $website, $deskripsi, $logo, $urutan]);
            setFlash('success', 'Mitra baru berhasil ditambahkan');
        }
        if ($newImageUploaded !== null) {
            if ($existingImagePath !== null && $existingImagePath !== $logo) {
                deleteUploadedFile((string)$existingImagePath, 'uploads/mitra');
            }
            $newImageUploaded = null;
        }
    } catch (Throwable $e) {
        if ($newImageUploaded !== null) {
            deleteUploadedFile((string)$newImageUploaded, 'uploads/mitra');
        }
        setFlash('error', 'Gagal menyimpan: ' . $e->getMessage());
    }
    header('Location: mitra.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$filterJenis = $_GET['jenis'] ?? '';
$searchQuery = $_GET['q'] ?? '';
$sortBy = $_GET['sort'] ?? 'urutan';
$sortDir = $_GET['dir'] ?? 'ASC';

$where = "1=1";
$params = [];

if ($filterJenis) { $where .= " AND jenis = ?"; $params[] = $filterJenis; }
if ($searchQuery) { 
    $searchParam = "%$searchQuery%";
    $where .= " AND (nama LIKE ? OR website LIKE ? OR deskripsi LIKE ?)"; 
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$validSorts = ['urutan', 'nama', 'jenis', 'created_at'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'urutan';
$sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

/* ===== FETCH DATA ===== */
$mitraList = [];
$stats = [
    'total' => 0,
    'industri' => 0,
    'universitas' => 0,
    'pemerintah' => 0,
    'komunitas' => 0,
];

if ($db) {
    try {
        $sql = "SELECT * FROM mitra WHERE $where ORDER BY $sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $mitraList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM mitra")->fetchColumn();
        $stats['industri'] = (int)$db->query("SELECT COUNT(*) FROM mitra WHERE jenis='industri'")->fetchColumn();
        $stats['universitas'] = (int)$db->query("SELECT COUNT(*) FROM mitra WHERE jenis='universitas'")->fetchColumn();
        $stats['pemerintah'] = (int)$db->query("SELECT COUNT(*) FROM mitra WHERE jenis='pemerintah'")->fetchColumn();
        $stats['komunitas'] = (int)$db->query("SELECT COUNT(*) FROM mitra WHERE jenis='komunitas'")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== JENIS DISTRIBUTION ===== */
$jenisStats = [];
if ($db) {
    try {
        $jenisStats = $db->query("
            SELECT jenis, COUNT(*) as total
            FROM mitra 
            WHERE jenis IS NOT NULL
            GROUP BY jenis
            ORDER BY total DESC
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== JENIS CONFIG ===== */
$jenisConfig = [
    'industri' => ['icon' => 'fa-industry', 'color' => '#3B82F6', 'label' => 'Industri'],
    'universitas' => ['icon' => 'fa-university', 'color' => '#8B5CF6', 'label' => 'Universitas'],
    'pemerintah' => ['icon' => 'fa-landmark', 'color' => '#EF4444', 'label' => 'Pemerintah'],
    'komunitas' => ['icon' => 'fa-users', 'color' => '#10B981', 'label' => 'Komunitas'],
];

function getJenisInfo($jenis) {
    global $jenisConfig;
    return $jenisConfig[$jenis] ?? $jenisConfig['industri'];
}

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM mitra WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content mitra-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="mitra-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-partner-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-handshake"></i>
                    <span>Strategic Partnerships</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-building"></i>
                    </span>
                    Kelola Mitra
                </h1>
                <p class="hero-subtitle">
                    Manajemen mitra industri, universitas, pemerintah, dan komunitas
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-handshake"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total Mitra</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-industry"></i>
                        <span><strong><?= $stats['industri'] ?></strong> Industri</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-university"></i>
                        <span><strong><?= $stats['universitas'] ?></strong> Universitas</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportMitra()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Mitra</span>
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
    <div class="mitra-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="mitra-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid mitra-stats">
                <div class="stat-card stat-blue">
                    <div class="stat-icon"><i class="fas fa-handshake"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Mitra</p>
                        <span class="stat-trend up"><i class="fas fa-layer-group"></i> Partners</span>
                    </div>
                </div>

                <div class="stat-card stat-indigo">
                    <div class="stat-icon"><i class="fas fa-industry"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['industri'] ?>">0</h3>
                        <p>Industri</p>
                        <span class="stat-trend up"><i class="fas fa-briefcase"></i> Industry</span>
                    </div>
                </div>

                <div class="stat-card stat-violet">
                    <div class="stat-icon"><i class="fas fa-university"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['universitas'] ?>">0</h3>
                        <p>Universitas</p>
                        <span class="stat-trend up"><i class="fas fa-graduation-cap"></i> Academic</span>
                    </div>
                </div>

                <div class="stat-card stat-rose">
                    <div class="stat-icon"><i class="fas fa-landmark"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['pemerintah'] ?>">0</h3>
                        <p>Pemerintah</p>
                        <span class="stat-trend up"><i class="fas fa-landmark"></i> Government</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card mitra-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama, website, atau deskripsi..." value="<?= clean($searchQuery) ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterJenis" class="toolbar-select">
                            <option value="">🤝 Semua Jenis</option>
                            <option value="industri" <?= $filterJenis === 'industri' ? 'selected' : '' ?>>🏭 Industri</option>
                            <option value="universitas" <?= $filterJenis === 'universitas' ? 'selected' : '' ?>>🎓 Universitas</option>
                            <option value="pemerintah" <?= $filterJenis === 'pemerintah' ? 'selected' : '' ?>>🏛️ Pemerintah</option>
                            <option value="komunitas" <?= $filterJenis === 'komunitas' ? 'selected' : '' ?>>👥 Komunitas</option>
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

            <!-- Bulk Actions Bar -->
            <form method="POST" id="bulkForm">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="bulk-bar" id="bulkBar">
                <div class="bulk-info">
                    <i class="fas fa-check-circle"></i>
                    <span><strong id="selectedCount">0</strong> mitra dipilih</span>
                </div>
                <div class="bulk-actions">
                    <button type="submit" formaction="?action=bulk-delete" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus mitra yang dipilih?')">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        Batal
                    </button>
                </div>
            </div>

            <!-- Mitra Grid -->
            <div class="mitra-grid" id="mitraGrid" data-view="grid">
                <?php if (!empty($mitraList)): ?>
                    <?php foreach ($mitraList as $m): 
                        $jenisCfg = getJenisInfo($m['jenis']);
                    ?>
                    <div class="mitra-card" data-id="<?= $m['id'] ?>">
                        <!-- Checkbox -->
                        <label class="card-checkbox">
                            <input type="checkbox" name="selected[]" class="mitra-check" value="<?= $m['id'] ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <!-- Image/Logo Area -->
                        <div class="mc-image" onclick="previewMitra(<?= htmlspecialchars(json_encode($m, JSON_HEX_APOS)) ?>)">
                            <?php if ($m['logo']): ?>
                                <img src="../uploads/<?= htmlspecialchars($m['logo']) ?>" alt="<?= clean($m['nama']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="mc-placeholder" style="background: <?= $jenisCfg['color'] ?>15">
                                    <i class="fas <?= $jenisCfg['icon'] ?>" style="color: <?= $jenisCfg['color'] ?>"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mc-overlay">
                                <div class="mc-quick-actions" onclick="event.stopPropagation()">
                                    <button onclick="previewMitra(<?= htmlspecialchars(json_encode($m, JSON_HEX_APOS)) ?>)" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editMitra(<?= htmlspecialchars(json_encode($m, JSON_HEX_APOS)) ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=delete&id=<?= $m['id'] ?>" title="Hapus" data-confirm="Yakin hapus mitra ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Jenis Badge -->
                            <div class="mc-jenis-badge" style="background: <?= $jenisCfg['color'] ?>">
                                <i class="fas <?= $jenisCfg['icon'] ?>"></i>
                                <?= $jenisCfg['label'] ?>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="mc-body">
                            <h3 class="mc-title"><?= clean($m['nama']) ?></h3>
                            
                            <?php if ($m['deskripsi']): ?>
                            <p class="mc-desc"><?= clean(mb_substr($m['deskripsi'], 0, 80)) ?><?= strlen($m['deskripsi']) > 80 ? '...' : '' ?></p>
                            <?php endif; ?>
                            
                            <div class="mc-footer">
                                <?php if ($m['website']): ?>
                                <a href="<?= htmlspecialchars($m['website']) ?>" target="_blank" rel="noopener" class="mc-website-btn" style="color: <?= $jenisCfg['color'] ?>">
                                    <i class="fas fa-external-link-alt"></i> Kunjungi Website
                                </a>
                                <?php else: ?>
                                <span class="mc-no-website"><i class="fas fa-link-slash"></i> Tidak ada website</span>
                                <?php endif; ?>
                                
                                <div class="mc-urutan">
                                    <i class="fas fa-sort-numeric-up"></i>
                                    <span>Urutan: <strong><?= $m['urutan'] ?></strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration">
                            <svg viewBox="0 0 200 150" width="200" height="150">
                                <ellipse cx="100" cy="130" rx="70" ry="10" fill="var(--gray-200)" opacity="0.5"/>
                                <rect x="40" y="50" width="120" height="60" rx="4" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <circle cx="70" cy="80" r="15" fill="#3B82F6" opacity="0.3"/>
                                <circle cx="130" cy="80" r="15" fill="#8B5CF6" opacity="0.3"/>
                                <path d="M 85 80 L 115 80" stroke="var(--gray-300)" stroke-width="3" stroke-dasharray="4 4"/>
                                <circle cx="155" cy="45" r="18" fill="#3B82F6" opacity="0.9"/>
                                <path d="M 148 45 L 162 45 M 155 38 L 155 52" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Belum ada mitra</h3>
                        <p>Tambahkan mitra kerja sama prodi untuk ditampilkan di website</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-plus"></i> Tambah Mitra Pertama
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- List View (Table) -->
            <?php if (!empty($mitraList)): ?>
            <div class="mitra-list-view" id="mitraList" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="selectAllTable"></th>
                                    <th>Logo</th>
                                    <th>Nama Mitra</th>
                                    <th>Jenis</th>
                                    <th>Website</th>
                                    <th>Urutan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mitraList as $m): 
                                    $jenisCfg = getJenisInfo($m['jenis']);
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="selected[]" class="mitra-check" value="<?= $m['id'] ?>"></td>
                                    <td>
                                        <?php if ($m['logo']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($m['logo']) ?>" alt="" style="width:50px;height:50px;border-radius:8px;object-fit:contain;padding:4px;background:var(--white);cursor:pointer" onclick="previewMitra(<?= htmlspecialchars(json_encode($m, JSON_HEX_APOS)) ?>)">
                                        <?php else: ?>
                                            <div style="width:50px;height:50px;border-radius:8px;background:<?= $jenisCfg['color'] ?>15;display:flex;align-items:center;justify-content:center;color:<?= $jenisCfg['color'] ?>">
                                                <i class="fas <?= $jenisCfg['icon'] ?>" style="font-size:1.2rem"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= clean($m['nama']) ?></strong>
                                        <?php if ($m['deskripsi']): ?>
                                        <div style="font-size:0.72rem;color:var(--gray-500);margin-top:2px"><?= clean(mb_substr($m['deskripsi'], 0, 40)) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:<?= $jenisCfg['color'] ?>15;color:<?= $jenisCfg['color'] ?>">
                                            <i class="fas <?= $jenisCfg['icon'] ?>"></i> <?= $jenisCfg['label'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($m['website']): ?>
                                        <a href="<?= htmlspecialchars($m['website']) ?>" target="_blank" rel="noopener" style="color:var(--primary);font-size:0.85rem;text-decoration:none">
                                            <i class="fas fa-external-link-alt"></i> Link
                                        </a>
                                        <?php else: ?>
                                        <span style="color:var(--gray-400);font-size:0.85rem">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= $m['urutan'] ?></strong></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-sm" onclick="previewMitra(<?= htmlspecialchars(json_encode($m, JSON_HEX_APOS)) ?>)" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-sm" onclick="editMitra(<?= htmlspecialchars(json_encode($m, JSON_HEX_APOS)) ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=delete&id=<?= $m['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus mitra ini?" title="Hapus">
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
                <span>Menampilkan <strong><?= count($mitraList) ?></strong> mitra</span>
            </div>
            <?php endif; ?>
            </form>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="mitra-sidebar">
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

            <!-- Quick Actions -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="widget-body">
                    <button class="qa-btn primary" onclick="openModal()">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Mitra</span>
                    </button>
                    <a href="prestasi.php" class="qa-btn">
                        <i class="fas fa-trophy"></i>
                        <span>Kelola Prestasi</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="alumni.php" class="qa-btn">
                        <i class="fas fa-user-graduate"></i>
                        <span>Data Alumni</span>
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
                        <div class="tip-text">Gunakan logo dengan background transparan (PNG/SVG)</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Atur urutan tampil untuk mengontrol tampilan di website</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Lengkapi link website untuk memudahkan verifikasi</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== MITRA MODAL ===== -->
<div class="modal" id="mitraModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient mitra-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-handshake"></i></div>
                <div>
                    <h3 id="modalTitle">Tambah Mitra</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px" id="modalSubtitle">Lengkapi data mitra kerja sama</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="mitraForm" class="modal-body">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" id="formId">
            <input type="hidden" name="logo_lama" id="logoLama">
            
            <div class="form-sections">
                <!-- Basic Info -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-info-circle"></i> Informasi Dasar</h4>
                    <div class="form-grid-2">
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Nama Mitra <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-building"></i>
                                <input type="text" name="nama" id="nama" required placeholder="Contoh: PT Telkom Indonesia">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Jenis Mitra <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-tag"></i>
                                <select name="jenis" id="jenis" required>
                                    <option value="industri">🏭 Industri</option>
                                    <option value="universitas">🎓 Universitas</option>
                                    <option value="pemerintah">🏛️ Pemerintah</option>
                                    <option value="komunitas">👥 Komunitas</option>
                                </select>
                            </div>
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
                    <h4 class="section-title"><i class="fas fa-link"></i> Detail & Website</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="field-label">Website</label>
                            <div class="input-icon">
                                <i class="fas fa-globe"></i>
                                <input type="url" name="website" id="website" placeholder="https://example.com">
                            </div>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Deskripsi Kerja Sama</label>
                            <div class="textarea-wrap">
                                <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Jelaskan bentuk kerja sama atau deskripsi singkat mitra..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logo Upload -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-image"></i> Logo Mitra</h4>
                    <div class="upload-area upload-large" id="uploadArea">
                        <div class="upload-placeholder-large" id="uploadPlaceholder">
                            <div class="upload-icon-large">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h4>Drag & drop logo di sini</h4>
                            <p>atau klik untuk memilih file</p>
                            <small class="upload-note">PNG, SVG, JPG (Max 2MB, background transparan disarankan)</small>
                        </div>
                        <img id="previewImg" class="upload-preview" style="display:none; max-height: 150px; object-fit: contain;">
                        <input type="file" name="logo" id="logoInput" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="submitText">Simpan Mitra</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== PREVIEW MODAL ===== -->
<div class="modal" id="previewModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient mitra-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-eye"></i></div>
                <div>
                    <h3>Detail Mitra</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Informasi lengkap mitra kerja sama</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePreview()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="previewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePreview()">Tutup</button>
            <button class="btn btn-primary" id="previewEditBtn"><i class="fas fa-edit"></i> Edit Mitra</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   MITRA PAGE - PARTNER MANAGER EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.mitra-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.mitra-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 50%, #60A5FA 100%);
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
    background: radial-gradient(circle, #93C5FD, transparent);
    top: -120px; right: 8%;
}

.orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, #60A5FA, transparent);
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

.hero-partner-pattern {
    position: absolute;
    top: 50%;
    right: 5%;
    transform: translateY(-50%);
    font-size: 18rem;
    color: rgba(255,255,255,0.04);
    pointer-events: none;
}

.hero-partner-pattern::before {
    content: '\f2b5';
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
    color: #DBEAFE;
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
.mitra-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.mitra-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.mitra-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-blue .stat-icon { background: linear-gradient(135deg, #3B82F6, #1E40AF); }
.stat-indigo .stat-icon { background: linear-gradient(135deg, #6366F1, #4338CA); }
.stat-violet .stat-icon { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }
.stat-rose .stat-icon { background: linear-gradient(135deg, #F43F5E, #BE123C); }

/* ===== TOOLBAR ===== */
.mitra-toolbar {
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
    border-color: #3B82F6;
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
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
    border-color: #3B82F6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
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
    background: #3B82F6;
    color: var(--white);
    border-color: #3B82F6;
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
    background: rgba(59,130,246,0.1);
    color: #1E40AF;
    font-weight: 600;
}

.sort-item i {
    width: 16px;
    color: var(--gray-500);
}

.sort-item.active i {
    color: #3B82F6;
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
    color: #3B82F6;
    box-shadow: var(--shadow-sm);
}

/* ===== BULK BAR ===== */
.bulk-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: linear-gradient(135deg, #1E40AF, #3B82F6);
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

/* ===== MITRA GRID ===== */
.mitra-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.mitra-card {
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

[data-theme="dark"] .mitra-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.mitra-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(59,130,246,0.3);
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

.mitra-card:hover .card-checkbox,
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
    background: #3B82F6;
    border-color: #3B82F6;
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
.mc-image {
    position: relative;
    height: 160px;
    background: var(--gray-50);
    overflow: hidden;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mc-image img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
    padding: 20px;
    transition: transform 0.5s;
}

.mitra-card:hover .mc-image img {
    transform: scale(1.05);
}

.mc-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
}

.mc-overlay {
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

.mitra-card:hover .mc-overlay {
    opacity: 1;
}

.mc-quick-actions {
    display: flex;
    gap: 8px;
}

.mc-quick-actions button,
.mc-quick-actions a {
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

.mc-quick-actions button:hover,
.mc-quick-actions a:hover {
    background: #3B82F6;
    color: var(--white);
    transform: translateY(-3px);
}

/* Jenis Badge */
.mc-jenis-badge {
    position: absolute;
    top: 12px;
    right: 12px;
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

/* Card Body */
.mc-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.mc-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 8px;
    line-height: 1.3;
}

[data-theme="dark"] .mc-title {
    color: var(--white);
}

.mc-desc {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 16px;
    line-height: 1.5;
    flex: 1;
}

[data-theme="dark"] .mc-desc {
    color: var(--gray-400);
}

.mc-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 14px;
    border-top: 1px solid var(--gray-100);
}

[data-theme="dark"] .mc-footer {
    border-color: rgba(255,255,255,0.08);
}

.mc-website-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.mc-website-btn:hover {
    text-decoration: underline;
}

.mc-no-website {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: var(--gray-400);
}

.mc-urutan {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: var(--gray-500);
}

.mc-urutan i {
    color: var(--gray-400);
}

/* ===== LIST VIEW ===== */
.mitra-list-view .data-table {
    font-size: 0.85rem;
}

.mitra-list-view .data-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 700;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .mitra-list-view .data-table th {
    background: var(--dark);
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

.mitra-list-view .data-table td {
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
    color: #3B82F6;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== SIDEBAR WIDGETS ===== */
.mitra-sidebar {
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
    background: linear-gradient(135deg, #3B82F6, #1E40AF);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(59,130,246,0.3);
}

.qa-btn.primary:hover {
    box-shadow: 0 6px 16px rgba(59,130,246,0.4);
}

.qa-btn i:first-child {
    color: #3B82F6;
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
    background: linear-gradient(135deg, rgba(59,130,246,0.04), rgba(30,64,175,0.04));
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
    background: rgba(59,130,246,0.12);
    color: #3B82F6;
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
.mitra-modal-head {
    background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 60%, #60A5FA 100%) !important;
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
    border-color: #3B82F6;
    background: rgba(59,130,246,0.04);
}

.upload-large.dragover {
    border-color: #3B82F6;
    background: rgba(59,130,246,0.08);
    transform: scale(1.01);
}

.upload-placeholder-large {
    color: var(--gray-600);
}

.upload-icon-large {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(30,64,175,0.1));
    color: #3B82F6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 14px;
    box-shadow: 0 8px 20px rgba(59,130,246,0.2);
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
    height: 200px;
    background: var(--gray-100);
    overflow: hidden;
    margin: -28px -28px 20px;
    border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-hero img {
    max-height: 150px;
    max-width: 80%;
    object-fit: contain;
}

.preview-hero-placeholder {
    font-size: 5rem;
}

.preview-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.5), transparent 60%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 24px;
    color: var(--white);
}

.preview-jenis-badge {
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
    background: rgba(59,130,246,0.05);
    border-left: 4px solid #3B82F6;
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
    .mitra-layout {
        grid-template-columns: 1fr;
    }
    
    .mitra-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .mitra-page {
        padding: 16px;
    }
    
    .mitra-hero {
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
    
    .mitra-toolbar {
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
    
    .mitra-grid {
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
    .mitra-hero,
    .mitra-sidebar,
    .mitra-toolbar,
    .bulk-bar,
    .mc-quick-actions,
    .card-checkbox {
        display: none !important;
    }
    
    .mitra-layout {
        grid-template-columns: 1fr;
    }
    
    .mitra-card {
        break-inside: avoid;
    }
}
</style>

<script>
(function() {
    'use strict';

    const allMitra = <?= json_encode($mitraList) ?>;
    const jenisConfig = <?= json_encode($jenisConfig) ?>;

    function getJenisInfo(jenis) {
        return jenisConfig[jenis] || jenisConfig['industri'];
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
        document.getElementById('mitraForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('logoLama').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Mitra';
        document.getElementById('modalSubtitle').textContent = 'Lengkapi data mitra kerja sama';
        document.getElementById('submitText').textContent = 'Simpan Mitra';
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('uploadPlaceholder').style.display = 'block';
        
        document.getElementById('mitraModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('nama')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('mitraModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.closePreview = function() {
        document.getElementById('previewModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.editMitra = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Mitra';
        document.getElementById('modalSubtitle').textContent = 'Perbarui data mitra';
        document.getElementById('submitText').textContent = 'Perbarui Mitra';
        document.getElementById('formId').value = data.id;
        document.getElementById('nama').value = data.nama;
        document.getElementById('jenis').value = data.jenis;
        document.getElementById('website').value = data.website || '';
        document.getElementById('deskripsi').value = data.deskripsi || '';
        document.getElementById('urutan').value = data.urutan || 0;
        document.getElementById('logoLama').value = data.logo || '';
        
        const previewImg = document.getElementById('previewImg');
        if (data.logo) {
            previewImg.src = '../uploads/' + data.logo;
            previewImg.style.display = 'block';
            document.getElementById('uploadPlaceholder').style.display = 'none';
        } else {
            previewImg.style.display = 'none';
            document.getElementById('uploadPlaceholder').style.display = 'block';
        }
        
        document.getElementById('mitraModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== PREVIEW MODAL ===== */
    window.previewMitra = function(data) {
        const jenisCfg = getJenisInfo(data.jenis);
        
        const body = document.getElementById('previewBody');
        body.innerHTML = `
            <div class="preview-hero" style="background: ${jenisCfg.color}15">
                ${data.logo 
                    ? `<img src="../uploads/${data.logo}" alt="${data.nama}">`
                    : `<div class="preview-hero-placeholder" style="color: ${jenisCfg.color}"><i class="fas ${jenisCfg.icon}"></i></div>`
                }
                <div class="preview-overlay">
                    <span class="preview-jenis-badge" style="background: ${jenisCfg.color}">
                        <i class="fas ${jenisCfg.icon}"></i> ${jenisCfg.label}
                    </span>
                    <h1 class="preview-title">${data.nama}</h1>
                </div>
            </div>
            
            <div class="preview-info-grid">
                <div>
                    <div class="preview-label"><i class="fas fa-globe"></i> Website</div>
                    <div class="preview-value">
                        ${data.website 
                            ? `<a href="${data.website}" target="_blank" rel="noopener" style="color:#3B82F6;text-decoration:none;font-weight:600">${data.website} <i class="fas fa-external-link-alt" style="font-size:0.75rem"></i></a>` 
                            : '<span style="color:var(--gray-400)">Tidak tersedia</span>'}
                    </div>
                </div>
                <div>
                    <div class="preview-label"><i class="fas fa-sort-numeric-up"></i> Urutan Tampil</div>
                    <div class="preview-value"><strong style="color:${jenisCfg.color}">${data.urutan || 0}</strong></div>
                </div>
                <div>
                    <div class="preview-label"><i class="fas fa-calendar"></i> Ditambahkan</div>
                    <div class="preview-value">${new Date(data.created_at).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'})}</div>
                </div>
            </div>
            
            ${data.deskripsi ? `
            <div class="preview-section">
                <div class="preview-label"><i class="fas fa-align-left"></i> Deskripsi Kerja Sama</div>
                <div class="preview-desc-box">${data.deskripsi}</div>
            </div>
            ` : ''}
        `;
        
        document.getElementById('previewEditBtn').onclick = function() {
            closePreview();
            setTimeout(() => editMitra(data), 300);
        };
        
        document.getElementById('previewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== FILE UPLOAD HANDLING ===== */
    const uploadArea = document.getElementById('uploadArea');
    const logoInput = document.getElementById('logoInput');
    const previewImg = document.getElementById('previewImg');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');

    logoInput?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                if (window.IFPro?.toast) window.IFPro.toast.show('Ukuran file maksimal 2MB', 'error');
                logoInput.value = '';
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
            logoInput.files = e.dataTransfer.files;
            logoInput.dispatchEvent(new Event('change'));
        }
    });

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterJenis = document.getElementById('filterJenis');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    filterJenis?.addEventListener('change', applyFilters);

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const jenis = filterJenis?.value;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (jenis) url.searchParams.set('jenis', jenis); else url.searchParams.delete('jenis');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'jenis', 'sort', 'dir'].forEach(p => url.searchParams.delete(p));
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
            const grid = document.getElementById('mitraGrid');
            const list = document.getElementById('mitraList');
            
            if (view === 'list') {
                grid.style.display = 'none';
                list.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                list.style.display = 'none';
            }
            
            localStorage.setItem('mitra_view', view);
        });
    });

    const savedView = localStorage.getItem('mitra_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');
    const selectAllCheckbox = document.getElementById('selectAllTable');

    document.querySelectorAll('.mitra-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
            updateSelectAllState();
        });
    });

    window.toggleSelectAll = function(checkbox) {
        document.querySelectorAll('.mitra-check').forEach(c => {
            c.checked = checkbox.checked;
            const id = c.value;
            if (checkbox.checked) selectedIds.add(id);
            else selectedIds.delete(id);
        });
        updateBulkBar();
    };

    function updateSelectAllState() {
        const allChecks = document.querySelectorAll('.mitra-check');
        const checkedChecks = document.querySelectorAll('.mitra-check:checked');
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
        document.querySelectorAll('.mitra-check').forEach(c => c.checked = false);
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
        updateBulkBar();
    };

    /* ===== EXPORT CSV ===== */
    window.exportMitra = function() {
        if (!allMitra || allMitra.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['Nama', 'Jenis', 'Website', 'Deskripsi', 'Urutan', 'Created At'];
        const rows = allMitra.map(m => [
            `"${(m.nama || '').replace(/"/g, '""')}"`,
            m.jenis || '',
            `"${(m.website || '').replace(/"/g, '""')}"`,
            `"${(m.deskripsi || '').replace(/"/g, '""')}"`,
            m.urutan || 0,
            m.created_at || ''
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `mitra-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${allMitra.length} mitra berhasil di-export`, 'success');
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
    ['mitraModal', 'previewModal'].forEach(id => {
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
        setTimeout(() => editMitra(<?= json_encode($editData, JSON_HEX_APOS) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c🤝 Mitra v4.0 Loaded', 'color:#3B82F6;font-weight:bold;font-size:14px');
    console.log('%c   Features: Jenis Distribution, Preview Modal, Bulk Actions, Export CSV', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: N (New), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>