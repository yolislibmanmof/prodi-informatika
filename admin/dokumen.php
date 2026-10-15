<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Dokumen';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
}

$currentAdminId = $_SESSION['admin_id'] ?? 0;
$uploadDir = __DIR__ . '/../uploads/dokumen/';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

/* ===== FILE TYPE CONFIG ===== */
$fileTypes = [
    'pdf' => ['icon' => 'fa-file-pdf', 'color' => '#DC2626', 'label' => 'PDF Document', 'bg' => 'rgba(220,38,38,0.12)'],
    'docx' => ['icon' => 'fa-file-word', 'color' => '#2563EB', 'label' => 'Word Document', 'bg' => 'rgba(37,99,235,0.12)'],
    'doc' => ['icon' => 'fa-file-word', 'color' => '#2563EB', 'label' => 'Word Document', 'bg' => 'rgba(37,99,235,0.12)'],
    'xlsx' => ['icon' => 'fa-file-excel', 'color' => '#16A34A', 'label' => 'Excel Spreadsheet', 'bg' => 'rgba(22,163,74,0.12)'],
    'xls' => ['icon' => 'fa-file-excel', 'color' => '#16A34A', 'label' => 'Excel Spreadsheet', 'bg' => 'rgba(22,163,74,0.12)'],
    'pptx' => ['icon' => 'fa-file-powerpoint', 'color' => '#EA580C', 'label' => 'PowerPoint', 'bg' => 'rgba(234,88,12,0.12)'],
    'ppt' => ['icon' => 'fa-file-powerpoint', 'color' => '#EA580C', 'label' => 'PowerPoint', 'bg' => 'rgba(234,88,12,0.12)'],
    'zip' => ['icon' => 'fa-file-archive', 'color' => '#7C3AED', 'label' => 'Archive', 'bg' => 'rgba(124,58,237,0.12)'],
    'rar' => ['icon' => 'fa-file-archive', 'color' => '#7C3AED', 'label' => 'Archive', 'bg' => 'rgba(124,58,237,0.12)'],
];

$categories = [
    'kurikulum' => ['label' => 'Kurikulum', 'icon' => 'fa-book', 'color' => '#8B5CF6'],
    'akademik' => ['label' => 'Akademik', 'icon' => 'fa-graduation-cap', 'color' => '#3B82F6'],
    'kemahasiswaan' => ['label' => 'Kemahasiswaan', 'icon' => 'fa-users', 'color' => '#10B981'],
    'sop' => ['label' => 'SOP', 'icon' => 'fa-clipboard-check', 'color' => '#F59E0B'],
    'penelitian' => ['label' => 'Penelitian', 'icon' => 'fa-flask', 'color' => '#EC4899'],
    'lainnya' => ['label' => 'Lainnya', 'icon' => 'fa-folder', 'color' => '#6B7280'],
];

function formatSize($bytes) {
    if (!$bytes) return '0 B';
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024*1024) return round($bytes/1024, 1) . ' KB';
    if ($bytes < 1024*1024*1024) return round($bytes/(1024*1024), 2) . ' MB';
    return round($bytes/(1024*1024*1024), 2) . ' GB';
}

/* ===== HANDLE ACTIONS ===== */
if (isset($_GET['action']) && $db) {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($_GET['action'] === 'delete' && $id) {
        try {
            $stmt = $db->prepare("SELECT filename FROM dokumen WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['filename'] && file_exists($uploadDir . $row['filename'])) {
                @unlink($uploadDir . $row['filename']);
            }
            $db->prepare("DELETE FROM dokumen WHERE id = ?")->execute([$id]);
            setFlash('success', 'Dokumen berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus dokumen');
        }
        header('Location: dokumen.php');
        exit;
    }
    
    // Bulk actions
    if ($_GET['action'] === 'bulk-delete' && !empty($_GET['ids'])) {
        try {
            $ids = array_map('intval', explode(',', $_GET['ids']));
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            $stmt = $db->prepare("SELECT filename FROM dokumen WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $row) {
                if ($row['filename'] && file_exists($uploadDir . $row['filename'])) {
                    @unlink($uploadDir . $row['filename']);
                }
            }
            $db->prepare("DELETE FROM dokumen WHERE id IN ($placeholders)")->execute($ids);
            setFlash('success', count($ids) . ' dokumen berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus dokumen');
        }
        header('Location: dokumen.php');
        exit;
    }
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $judul = clean($_POST['judul']);
        $kategori = clean($_POST['kategori']);
        $deskripsi = clean($_POST['deskripsi'] ?? '');
        $filename_lama = $_POST['filename_lama'] ?? null;
        
        $filename = $filename_lama;
        $tipe_file = $filename_lama ? pathinfo($filename_lama, PATHINFO_EXTENSION) : '';
        $ukuran = 0;
        
        // Get existing file size if not uploading new
        if ($id && !$filename) {
            $st = $db->prepare("SELECT ukuran, tipe_file FROM dokumen WHERE id = ?");
            $st->execute([$id]);
            $existing = $st->fetch();
            if ($existing) {
                $ukuran = $existing['ukuran'];
                $tipe_file = $existing['tipe_file'];
            }
        }

        if (!empty($_FILES['file_dokumen']['name'])) {
            $allowed = ['pdf', 'docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'zip', 'rar'];
            $ext = strtolower(pathinfo($_FILES['file_dokumen']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                throw new Exception('Format file tidak diizinkan');
            }
            
            if ($_FILES['file_dokumen']['size'] > 20 * 1024 * 1024) {
                throw new Exception('Ukuran file maksimal 20MB');
            }
            
            $tipe_file = $ext;
            $ukuran = $_FILES['file_dokumen']['size'];
            $newName = uniqid('doc_') . '.' . $ext;
            $target = $uploadDir . $newName;
            
            if (move_uploaded_file($_FILES['file_dokumen']['tmp_name'], $target)) {
                if ($filename_lama && file_exists($uploadDir . $filename_lama)) {
                    @unlink($uploadDir . $filename_lama);
                }
                $filename = $newName;
            } else {
                throw new Exception('Gagal mengunggah file');
            }
        } elseif (!$id && empty($filename)) {
            throw new Exception('File dokumen wajib diunggah untuk data baru');
        }

        if ($id) {
            $stmt = $db->prepare("UPDATE dokumen SET judul=?, kategori=?, filename=?, tipe_file=?, ukuran=?, deskripsi=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$judul, $kategori, $filename, $tipe_file, $ukuran, $deskripsi, $id]);
            setFlash('success', 'Dokumen berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO dokumen (judul, kategori, filename, tipe_file, ukuran, deskripsi, created_at) VALUES (?,?,?,?,?,?,NOW())");
            $stmt->execute([$judul, $kategori, $filename, $tipe_file, $ukuran, $deskripsi]);
            setFlash('success', 'Dokumen baru berhasil diunggah');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan: ' . $e->getMessage());
    }
    header('Location: dokumen.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$search = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
$filterKategori = $_GET['kategori'] ?? '';
$filterType = $_GET['type'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$sortDir = $_GET['dir'] ?? 'DESC';

$where = "1=1";
$params = [];

if ($filterKategori) { $where .= " AND kategori = ?"; $params[] = $filterKategori; }
if ($filterType) { $where .= " AND tipe_file = ?"; $params[] = $filterType; }
if (isset($_GET['q']) && $_GET['q']) { 
    $where .= " AND (judul LIKE ? OR deskripsi LIKE ? OR filename LIKE ?)"; 
    $params[] = $search; $params[] = $search; $params[] = $search;
}

$validSorts = ['created_at', 'judul', 'ukuran', 'downloads', 'tipe_file'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'created_at';
$sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

/* ===== FETCH DATA ===== */
$dokumenList = [];
$stats = [
    'total' => 0,
    'total_downloads' => 0,
    'total_size' => 0,
    'categories' => 0,
    'file_types' => 0,
];

if ($db) {
    try {
        $sql = "SELECT * FROM dokumen WHERE $where ORDER BY $sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $dokumenList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM dokumen")->fetchColumn();
        $stats['total_downloads'] = (int)$db->query("SELECT COALESCE(SUM(downloads), 0) FROM dokumen")->fetchColumn();
        $stats['total_size'] = (int)$db->query("SELECT COALESCE(SUM(ukuran), 0) FROM dokumen")->fetchColumn();
        $stats['categories'] = (int)$db->query("SELECT COUNT(DISTINCT kategori) FROM dokumen WHERE kategori IS NOT NULL")->fetchColumn();
        $stats['file_types'] = (int)$db->query("SELECT COUNT(DISTINCT tipe_file) FROM dokumen WHERE tipe_file IS NOT NULL")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== FILE TYPE STATS ===== */
$typeStats = [];
if ($db) {
    try {
        $typeStats = $db->query("
            SELECT tipe_file, COUNT(*) as total, SUM(ukuran) as total_size, SUM(downloads) as total_downloads
            FROM dokumen 
            WHERE tipe_file IS NOT NULL
            GROUP BY tipe_file 
            ORDER BY total DESC
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== CATEGORY STATS ===== */
$categoryStats = [];
if ($db) {
    try {
        $categoryStats = $db->query("
            SELECT kategori, COUNT(*) as total, SUM(downloads) as total_downloads
            FROM dokumen 
            WHERE kategori IS NOT NULL
            GROUP BY kategori 
            ORDER BY total DESC
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== TOP DOWNLOADED ===== */
$topDownloaded = [];
if ($db) {
    try {
        $topDownloaded = $db->query("
            SELECT id, judul, kategori, tipe_file, downloads, filename, ukuran
            FROM dokumen 
            WHERE downloads > 0
            ORDER BY downloads DESC 
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== RECENT UPLOADS ===== */
$recentUploads = [];
if ($db) {
    try {
        $recentUploads = $db->query("
            SELECT id, judul, kategori, tipe_file, created_at, filename
            FROM dokumen 
            ORDER BY created_at DESC 
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== UNIQUE VALUES FOR FILTERS ===== */
$uniqueTypes = [];
if ($db) {
    try {
        $uniqueTypes = $db->query("SELECT DISTINCT tipe_file FROM dokumen WHERE tipe_file IS NOT NULL ORDER BY tipe_file")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {}
}

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM dokumen WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

/* ===== STORAGE QUOTA (simulated) ===== */
$maxStorage = 5 * 1024 * 1024 * 1024; // 5GB
$storagePercent = $maxStorage > 0 ? min(100, round(($stats['total_size'] / $maxStorage) * 100, 1)) : 0;
$storageColor = $storagePercent > 90 ? '#EF4444' : ($storagePercent > 70 ? '#F59E0B' : '#10B981');

include 'includes/header.php';
?>

<div class="page-content dokumen-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="dokumen-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-folder-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-folder-open"></i>
                    <span>File Repository</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-archive"></i>
                    </span>
                    Kelola Dokumen
                </h1>
                <p class="hero-subtitle">
                    Repository file unduhan akademik prodi - kurikulum, SOP, dan dokumen resmi
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-file-alt"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total Files</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-download"></i>
                        <span><strong><?= number_format($stats['total_downloads']) ?></strong> Downloads</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-database"></i>
                        <span><strong><?= formatSize($stats['total_size']) ?></strong> Storage</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-tags"></i>
                        <span><strong><?= $stats['categories'] ?></strong> Kategori</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportDokumen()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-ghost" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Unggah Dokumen</span>
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
    <div class="dokumen-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="dokumen-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid dokumen-stats">
                <div class="stat-card stat-amber">
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Dokumen</p>
                        <span class="stat-trend up"><i class="fas fa-folder"></i> Repository</span>
                    </div>
                </div>

                <div class="stat-card stat-blue">
                    <div class="stat-icon"><i class="fas fa-download"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total_downloads'] ?>">0</h3>
                        <p>Total Downloads</p>
                        <span class="stat-trend up"><i class="fas fa-chart-line"></i> Traffic</span>
                    </div>
                </div>

                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fas fa-database"></i></div>
                    <div class="stat-info">
                        <h3><?= formatSize($stats['total_size']) ?></h3>
                        <p>Storage Used</p>
                        <span class="stat-trend up" style="color: <?= $storageColor ?>">
                            <i class="fas fa-hdd"></i> <?= $storagePercent ?>% used
                        </span>
                    </div>
                </div>

                <div class="stat-card stat-purple">
                    <div class="stat-icon"><i class="fas fa-tags"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['categories'] ?>">0</h3>
                        <p>Kategori</p>
                        <span class="stat-trend up"><i class="fas fa-layer-group"></i> Organized</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card dokumen-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari judul, deskripsi, nama file..." value="<?= clean($_GET['q'] ?? '') ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterKategori" class="toolbar-select">
                            <option value="">📂 Semua Kategori</option>
                            <?php foreach ($categories as $key => $cat): ?>
                            <option value="<?= $key ?>" <?= $filterKategori === $key ? 'selected' : '' ?>>
                                <?= $cat['label'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select id="filterType" class="toolbar-select">
                            <option value="">📄 Semua Tipe</option>
                            <?php foreach ($uniqueTypes as $type): 
                                $cfg = $fileTypes[$type] ?? null;
                            ?>
                            <option value="<?= clean($type) ?>" <?= $filterType === $type ? 'selected' : '' ?>>
                                <?= strtoupper($type) ?> <?= $cfg ? '(' . $cfg['label'] . ')' : '' ?>
                            </option>
                            <?php endforeach; ?>
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
                                <i class="fas fa-sort-alpha-down"></i> Nama A-Z
                            </a>
                            <a href="?sort=ukuran&dir=DESC" class="sort-item <?= $sortBy === 'ukuran' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-weight-hanging"></i> Ukuran Terbesar
                            </a>
                            <a href="?sort=downloads&dir=DESC" class="sort-item <?= $sortBy === 'downloads' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-download"></i> Downloads Terbanyak
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
                    <span><strong id="selectedCount">0</strong> dokumen dipilih</span>
                </div>
                <div class="bulk-actions">
                    <button class="btn btn-sm btn-primary" onclick="bulkDownload()">
                        <i class="fas fa-download"></i> Download All
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('bulk-delete')">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        Batal
                    </button>
                </div>
            </div>

            <!-- Documents Grid -->
            <div class="dokumen-grid" id="dokumenGrid" data-view="grid">
                <?php if (!empty($dokumenList)): ?>
                    <?php foreach ($dokumenList as $d): 
                        $typeCfg = $fileTypes[$d['tipe_file']] ?? ['icon' => 'fa-file', 'color' => '#6B7280', 'label' => 'File', 'bg' => 'rgba(107,114,128,0.12)'];
                        $catCfg = $categories[$d['kategori']] ?? ['label' => ucfirst($d['kategori'] ?? 'Lainnya'), 'icon' => 'fa-folder', 'color' => '#6B7280'];
                    ?>
                    <div class="dokumen-card" data-id="<?= $d['id'] ?>">
                        <!-- Checkbox -->
                        <label class="card-checkbox">
                            <input type="checkbox" class="dokumen-check" value="<?= $d['id'] ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <!-- File Icon Hero -->
                        <div class="doc-hero" style="background: <?= $typeCfg['bg'] ?>" onclick="previewDokumen(<?= htmlspecialchars(json_encode($d, JSON_HEX_APOS)) ?>)">
                            <div class="doc-icon" style="color: <?= $typeCfg['color'] ?>">
                                <i class="fas <?= $typeCfg['icon'] ?>"></i>
                            </div>
                            <div class="doc-type-badge" style="background: <?= $typeCfg['color'] ?>">
                                <?= strtoupper($d['tipe_file']) ?>
                            </div>
                            
                            <div class="doc-overlay">
                                <div class="doc-quick-actions" onclick="event.stopPropagation()">
                                    <a href="../uploads/dokumen/<?= htmlspecialchars($d['filename']) ?>" download title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button onclick="previewDokumen(<?= htmlspecialchars(json_encode($d, JSON_HEX_APOS)) ?>)" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editDokumen(<?= htmlspecialchars(json_encode($d, JSON_HEX_APOS)) ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=delete&id=<?= $d['id'] ?>" title="Hapus" data-confirm="Yakin hapus dokumen ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="doc-body">
                            <div class="doc-category" style="background: <?= $catCfg['color'] ?>15; color: <?= $catCfg['color'] ?>">
                                <i class="fas <?= $catCfg['icon'] ?>"></i>
                                <?= $catCfg['label'] ?>
                            </div>

                            <h3 class="doc-title"><?= clean($d['judul']) ?></h3>
                            
                            <?php if ($d['deskripsi']): ?>
                            <p class="doc-description"><?= clean(mb_substr($d['deskripsi'], 0, 100)) ?><?= strlen($d['deskripsi']) > 100 ? '...' : '' ?></p>
                            <?php endif; ?>
                            
                            <div class="doc-stats">
                                <div class="doc-stat">
                                    <i class="fas fa-download"></i>
                                    <span><?= number_format($d['downloads'] ?? 0) ?></span>
                                </div>
                                <div class="doc-stat">
                                    <i class="fas fa-weight-hanging"></i>
                                    <span><?= formatSize($d['ukuran']) ?></span>
                                </div>
                                <div class="doc-stat">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= function_exists('formatTanggal') ? formatTanggal($d['created_at']) : date('d M Y', strtotime($d['created_at'])) ?></span>
                                </div>
                            </div>

                            <div class="doc-footer">
                                <span class="doc-filename" title="<?= clean($d['filename']) ?>">
                                    <i class="fas fa-paperclip"></i>
                                    <?= clean(mb_substr($d['filename'], 0, 25)) ?><?= strlen($d['filename']) > 25 ? '...' : '' ?>
                                </span>
                                <a href="../uploads/dokumen/<?= htmlspecialchars($d['filename']) ?>" download class="doc-download-btn">
                                    <i class="fas fa-download"></i>
                                    <span>Download</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration">
                            <svg viewBox="0 0 200 150" width="200" height="150">
                                <ellipse cx="100" cy="130" rx="70" ry="10" fill="var(--gray-200)" opacity="0.5"/>
                                <path d="M 40 45 L 85 45 L 95 55 L 160 55 L 160 115 L 40 115 Z" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <path d="M 40 45 L 85 45 L 95 55" fill="none" stroke="var(--gray-300)" stroke-width="2"/>
                                <rect x="60" y="70" width="80" height="4" rx="2" fill="var(--gray-300)"/>
                                <rect x="60" y="80" width="60" height="4" rx="2" fill="var(--gray-300)"/>
                                <rect x="60" y="90" width="70" height="4" rx="2" fill="var(--gray-300)"/>
                                <circle cx="155" cy="45" r="18" fill="#F59E0B" opacity="0.9"/>
                                <line x1="148" y1="45" x2="162" y2="45" stroke="white" stroke-width="3" stroke-linecap="round"/>
                                <line x1="155" y1="38" x2="155" y2="52" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Belum ada dokumen</h3>
                        <p>Unggah file akademik untuk diakses mahasiswa dan publik</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-cloud-upload-alt"></i> Unggah Dokumen Pertama
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Table View -->
            <?php if (!empty($dokumenList)): ?>
            <div class="dokumen-table-view" id="dokumenTable" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="selectAllTable"></th>
                                    <th>File</th>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Tipe</th>
                                    <th>Ukuran</th>
                                    <th>Downloads</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dokumenList as $d): 
                                    $typeCfg = $fileTypes[$d['tipe_file']] ?? ['icon' => 'fa-file', 'color' => '#6B7280', 'label' => 'File'];
                                    $catCfg = $categories[$d['kategori']] ?? ['label' => ucfirst($d['kategori'] ?? 'Lainnya'), 'icon' => 'fa-folder', 'color' => '#6B7280'];
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="dokumen-check" value="<?= $d['id'] ?>"></td>
                                    <td>
                                        <div style="width:40px;height:40px;border-radius:8px;background:<?= $typeCfg['bg'] ?>;display:flex;align-items:center;justify-content:center;color:<?= $typeCfg['color'] ?>">
                                            <i class="fas <?= $typeCfg['icon'] ?>" style="font-size:1.2rem"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= clean($d['judul']) ?></strong>
                                        <div style="font-size:0.72rem;color:var(--gray-500);margin-top:2px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= clean($d['filename']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:<?= $catCfg['color'] ?>15;color:<?= $catCfg['color'] ?>">
                                            <i class="fas <?= $catCfg['icon'] ?>"></i> <?= $catCfg['label'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code style="padding:3px 8px;background:<?= $typeCfg['bg'] ?>;color:<?= $typeCfg['color'] ?>;border-radius:4px;font-size:0.75rem;font-weight:600">
                                            <?= strtoupper($d['tipe_file']) ?>
                                        </code>
                                    </td>
                                    <td><?= formatSize($d['ukuran']) ?></td>
                                    <td><strong style="color:#F59E0B"><i class="fas fa-download" style="font-size:0.7rem"></i> <?= number_format($d['downloads'] ?? 0) ?></strong></td>
                                    <td style="font-size:0.82rem"><?= function_exists('formatTanggal') ? formatTanggal($d['created_at']) : date('d M Y', strtotime($d['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="../uploads/dokumen/<?= htmlspecialchars($d['filename']) ?>" download class="btn-icon btn-sm btn-primary" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button class="btn-icon btn-sm" onclick="previewDokumen(<?= htmlspecialchars(json_encode($d, JSON_HEX_APOS)) ?>)" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-sm" onclick="editDokumen(<?= htmlspecialchars(json_encode($d, JSON_HEX_APOS)) ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=delete&id=<?= $d['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus dokumen ini?" title="Hapus">
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
                <span>Menampilkan <strong><?= count($dokumenList) ?></strong> dokumen</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="dokumen-sidebar">
            <!-- Storage Usage -->
            <div class="card widget-card storage-widget">
                <div class="widget-header">
                    <h3><i class="fas fa-hdd"></i> Storage Usage</h3>
                </div>
                <div class="widget-body">
                    <div class="storage-visual">
                        <div class="storage-circle">
                            <svg viewBox="0 0 36 36" class="storage-ring">
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--gray-100)" stroke-width="3"/>
                                <circle cx="18" cy="18" r="15.9" fill="none" 
                                    stroke="<?= $storageColor ?>" 
                                    stroke-width="3"
                                    stroke-dasharray="<?= $storagePercent ?>, 100"
                                    stroke-linecap="round"
                                    transform="rotate(-90 18 18)"/>
                            </svg>
                            <div class="storage-center">
                                <div class="storage-percent" style="color: <?= $storageColor ?>"><?= $storagePercent ?>%</div>
                                <div class="storage-label">Used</div>
                            </div>
                        </div>
                        <div class="storage-info">
                            <div class="si-row">
                                <span class="si-label">Used:</span>
                                <strong><?= formatSize($stats['total_size']) ?></strong>
                            </div>
                            <div class="si-row">
                                <span class="si-label">Available:</span>
                                <strong><?= formatSize(max(0, $maxStorage - $stats['total_size'])) ?></strong>
                            </div>
                            <div class="si-row">
                                <span class="si-label">Total:</span>
                                <strong><?= formatSize($maxStorage) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File Types Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-file-alt"></i> Tipe File</h3>
                    <span class="widget-badge"><?= count($typeStats) ?> tipe</span>
                </div>
                <div class="widget-body">
                    <?php if (!empty($typeStats)): 
                        $maxType = max(array_column($typeStats, 'total'));
                    ?>
                        <?php foreach ($typeStats as $ts): 
                            $pct = round(($ts['total'] / $maxType) * 100);
                            $cfg = $fileTypes[$ts['tipe_file']] ?? ['icon' => 'fa-file', 'color' => '#6B7280', 'label' => strtoupper($ts['tipe_file'])];
                        ?>
                        <div class="type-stat-item">
                            <div class="tsi-head">
                                <span class="tsi-name" style="color: <?= $cfg['color'] ?>">
                                    <i class="fas <?= $cfg['icon'] ?>"></i>
                                    <?= strtoupper($ts['tipe_file']) ?>
                                </span>
                                <span class="tsi-count"><?= $ts['total'] ?></span>
                            </div>
                            <div class="tsi-bar">
                                <div class="tsi-bar-fill" style="width: <?= $pct ?>%; background: <?= $cfg['color'] ?>"></div>
                            </div>
                            <div class="tsi-meta">
                                <span><i class="fas fa-weight-hanging"></i> <?= formatSize($ts['total_size']) ?></span>
                                <span><i class="fas fa-download"></i> <?= number_format($ts['total_downloads'] ?? 0) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-file-alt"></i>
                            <p>Belum ada data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Categories -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-folder-open"></i> Kategori</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($categoryStats)): 
                        $maxCat = max(array_column($categoryStats, 'total'));
                    ?>
                        <?php foreach ($categoryStats as $cs): 
                            $pct = round(($cs['total'] / $maxCat) * 100);
                            $catCfg = $categories[$cs['kategori']] ?? ['label' => ucfirst($cs['kategori']), 'icon' => 'fa-folder', 'color' => '#6B7280'];
                        ?>
                        <a href="?kategori=<?= urlencode($cs['kategori']) ?>" class="cat-stat-item" style="text-decoration:none;color:inherit">
                            <div class="csi-head">
                                <span class="csi-name" style="color: <?= $catCfg['color'] ?>">
                                    <i class="fas <?= $catCfg['icon'] ?>"></i>
                                    <?= $catCfg['label'] ?>
                                </span>
                                <span class="csi-count"><?= $cs['total'] ?></span>
                            </div>
                            <div class="csi-bar">
                                <div class="csi-bar-fill" style="width: <?= $pct ?>%; background: <?= $catCfg['color'] ?>"></div>
                            </div>
                            <div class="csi-downloads">
                                <i class="fas fa-download"></i> <?= number_format($cs['total_downloads'] ?? 0) ?> downloads
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-folder-open"></i>
                            <p>Belum ada kategori</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Downloaded -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-fire"></i> Most Downloaded</h3>
                    <span class="widget-badge">Top 5</span>
                </div>
                <div class="widget-body">
                    <?php if (!empty($topDownloaded)): ?>
                        <?php foreach ($topDownloaded as $i => $td): 
                            $tdTypeCfg = $fileTypes[$td['tipe_file']] ?? ['icon' => 'fa-file', 'color' => '#6B7280', 'bg' => 'rgba(107,114,128,0.12)'];
                        ?>
                        <div class="top-item" onclick="previewDokumen(<?= htmlspecialchars(json_encode($td, JSON_HEX_APOS)) ?>)">
                            <div class="ti-rank">#<?= $i + 1 ?></div>
                            <div class="ti-icon" style="background: <?= $tdTypeCfg['bg'] ?>; color: <?= $tdTypeCfg['color'] ?>">
                                <i class="fas <?= $tdTypeCfg['icon'] ?>"></i>
                            </div>
                            <div class="ti-info">
                                <div class="ti-title"><?= clean(mb_substr($td['judul'], 0, 40)) ?><?= strlen($td['judul']) > 40 ? '...' : '' ?></div>
                                <div class="ti-meta">
                                    <i class="fas fa-download"></i> <?= number_format($td['downloads']) ?> · <?= formatSize($td['ukuran']) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-fire"></i>
                            <p>Belum ada download</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Uploads -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-clock"></i> Baru Diunggah</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($recentUploads)): ?>
                        <?php foreach ($recentUploads as $ru): 
                            $ruTypeCfg = $fileTypes[$ru['tipe_file']] ?? ['icon' => 'fa-file', 'color' => '#6B7280', 'bg' => 'rgba(107,114,128,0.12)'];
                        ?>
                        <div class="recent-item" onclick="previewDokumen(<?= htmlspecialchars(json_encode($ru, JSON_HEX_APOS)) ?>)">
                            <div class="ri-icon" style="background: <?= $ruTypeCfg['bg'] ?>; color: <?= $ruTypeCfg['color'] ?>">
                                <i class="fas <?= $ruTypeCfg['icon'] ?>"></i>
                            </div>
                            <div class="ri-info">
                                <div class="ri-title"><?= clean(mb_substr($ru['judul'], 0, 40)) ?><?= strlen($ru['judul']) > 40 ? '...' : '' ?></div>
                                <div class="ri-meta">
                                    <i class="fas fa-calendar"></i>
                                    <?= function_exists('formatTanggal') ? formatTanggal($ru['created_at']) : date('d M Y', strtotime($ru['created_at'])) ?>
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
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Unggah Dokumen</span>
                    </button>
                    <button class="qa-btn" onclick="bulkDownloadAll()">
                        <i class="fas fa-download"></i>
                        <span>Download All</span>
                    </button>
                    <a href="berita.php" class="qa-btn">
                        <i class="fas fa-newspaper"></i>
                        <span>Kelola Berita</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="media.php" class="qa-btn">
                        <i class="fas fa-images"></i>
                        <span>Media Library</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Allowed Formats -->
            <div class="card widget-card formats-card">
                <div class="widget-header">
                    <h3><i class="fas fa-info-circle"></i> Format yang Didukung</h3>
                </div>
                <div class="widget-body">
                    <div class="formats-grid">
                        <?php foreach (array_slice($fileTypes, 0, 6) as $ext => $cfg): ?>
                        <div class="format-item" style="background: <?= $cfg['bg'] ?>; color: <?= $cfg['color'] ?>">
                            <i class="fas <?= $cfg['icon'] ?>"></i>
                            <span><?= strtoupper($ext) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="formats-note">
                        <i class="fas fa-info-circle"></i>
                        Maksimal ukuran file: <strong>20 MB</strong>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== DOKUMEN MODAL ===== -->
<div class="modal" id="dokumenModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient dokumen-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                <div>
                    <h3 id="modalTitle">Unggah Dokumen</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px" id="modalSubtitle">Unggah file akademik untuk repository</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="dokumenForm" class="modal-body">
            <input type="hidden" name="id" id="formId">
            <input type="hidden" name="filename_lama" id="filenameLama">
            
            <div class="form-sections">
                <!-- Upload Area -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-upload"></i> File Dokumen</h4>
                    <div class="upload-area upload-large" id="uploadArea">
                        <div class="upload-placeholder-large" id="uploadPlaceholder">
                            <div class="upload-icon-large">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h4>Drag & drop file di sini</h4>
                            <p>atau klik untuk memilih file</p>
                            <div class="upload-formats">
                                <span class="uf-item pdf"><i class="fas fa-file-pdf"></i> PDF</span>
                                <span class="uf-item docx"><i class="fas fa-file-word"></i> DOCX</span>
                                <span class="uf-item xlsx"><i class="fas fa-file-excel"></i> XLSX</span>
                                <span class="uf-item pptx"><i class="fas fa-file-powerpoint"></i> PPTX</span>
                                <span class="uf-item zip"><i class="fas fa-file-archive"></i> ZIP</span>
                            </div>
                            <small class="upload-note">Maksimal ukuran: 20 MB</small>
                        </div>
                        <div class="upload-preview-box" id="uploadPreview" style="display:none">
                            <div class="upb-icon" id="previewIcon">
                                <i class="fas fa-file"></i>
                            </div>
                            <div class="upb-info">
                                <div class="upb-name" id="previewName">filename.pdf</div>
                                <div class="upb-meta">
                                    <span id="previewSize">0 KB</span>
                                    <span>•</span>
                                    <span id="previewType">PDF</span>
                                </div>
                            </div>
                            <button type="button" class="upb-remove" onclick="removeFile()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <input type="file" name="file_dokumen" id="fileDokumen" accept=".pdf,.docx,.doc,.xlsx,.xls,.pptx,.ppt,.zip,.rar">
                    </div>
                    
                    <div id="currentFileDisplay" style="display:none; margin-top: 12px; padding: 12px; background: var(--gray-50); border-radius: var(--radius); border-left: 3px solid #F59E0B;">
                        <div style="display:flex;align-items:center;gap:10px">
                            <i class="fas fa-paperclip" style="color:#F59E0B"></i>
                            <div style="flex:1">
                                <div style="font-size:0.78rem;color:var(--gray-600);margin-bottom:2px">File saat ini:</div>
                                <strong id="currentFileName" style="font-size:0.88rem;color:var(--dark)">filename.pdf</strong>
                            </div>
                            <span class="badge badge-warning" style="font-size:0.7rem">
                                <i class="fas fa-info-circle"></i> Upload file baru untuk mengganti
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Document Info -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-info-circle"></i> Informasi Dokumen</h4>
                    <div class="form-grid-2">
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Judul Dokumen <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-heading"></i>
                                <input type="text" name="judul" id="judul" required placeholder="Contoh: Kurikulum 2024 Program Studi Informatika">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Kategori <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-folder"></i>
                                <select name="kategori" id="kategori" required>
                                    <?php foreach ($categories as $key => $cat): ?>
                                    <option value="<?= $key ?>"><?= $cat['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="field-label">Deskripsi Singkat</label>
                        <div class="textarea-wrap">
                            <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Keterangan isi dokumen, tujuan, dan informasi relevan lainnya..." maxlength="500"></textarea>
                            <span class="char-counter"><span id="deskripsiCount">0</span>/500</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="submitText">Simpan Dokumen</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== PREVIEW MODAL ===== -->
<div class="modal" id="previewModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient dokumen-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-eye"></i></div>
                <div>
                    <h3>Detail Dokumen</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Informasi lengkap dokumen</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePreview()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="previewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePreview()">Tutup</button>
            <button class="btn btn-primary" id="previewDownloadBtn"><i class="fas fa-download"></i> Download</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   DOKUMEN PAGE - FILE MANAGER EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.dokumen-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.dokumen-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #78350F 0%, #B45309 50%, #F59E0B 100%);
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
    background: radial-gradient(circle, #F59E0B, transparent);
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

.hero-folder-pattern {
    position: absolute;
    top: 50%;
    right: 5%;
    transform: translateY(-50%);
    font-size: 18rem;
    color: rgba(255,255,255,0.04);
    pointer-events: none;
}

.hero-folder-pattern::before {
    content: '\f07b';
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
.dokumen-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.dokumen-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.dokumen-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-amber .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }
.stat-blue .stat-icon { background: linear-gradient(135deg, #3B82F6, #1E40AF); }
.stat-green .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-purple .stat-icon { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }

/* ===== TOOLBAR ===== */
.dokumen-toolbar {
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

.btn-primary {
    background: #10B981;
    color: var(--white);
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
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

/* ===== DOKUMEN GRID ===== */
.dokumen-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.dokumen-grid[data-view="list"] {
    grid-template-columns: 1fr;
}

.dokumen-grid[data-view="list"] .dokumen-card {
    display: grid;
    grid-template-columns: 140px 1fr;
}

.dokumen-grid[data-view="list"] .doc-hero {
    height: 100%;
    min-height: 160px;
}

.dokumen-card {
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

[data-theme="dark"] .dokumen-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.dokumen-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(245,158,11,0.3);
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

.dokumen-card:hover .card-checkbox,
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

/* Document Hero */
.doc-hero {
    position: relative;
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    overflow: hidden;
}

.doc-icon {
    font-size: 4rem;
    transition: transform 0.5s;
    z-index: 1;
}

.dokumen-card:hover .doc-icon {
    transform: scale(1.1) rotate(-5deg);
}

.doc-type-badge {
    position: absolute;
    top: 12px;
    right: 54px;
    padding: 4px 10px;
    color: var(--white);
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.doc-overlay {
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

.dokumen-card:hover .doc-overlay {
    opacity: 1;
}

.doc-quick-actions {
    display: flex;
    gap: 8px;
}

.doc-quick-actions button,
.doc-quick-actions a {
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

.doc-quick-actions button:hover,
.doc-quick-actions a:hover {
    background: #F59E0B;
    color: var(--white);
    transform: translateY(-3px);
}

/* Document Body */
.doc-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.doc-category {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    margin-bottom: 10px;
    align-self: flex-start;
}

.doc-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 8px;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.7em;
}

[data-theme="dark"] .doc-title {
    color: var(--white);
}

.doc-description {
    font-size: 0.82rem;
    color: var(--gray-600);
    margin-bottom: 12px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

[data-theme="dark"] .doc-description {
    color: var(--gray-400);
}

.doc-stats {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 12px;
    padding: 8px 0;
    border-top: 1px solid var(--gray-100);
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .doc-stats {
    border-color: rgba(255,255,255,0.08);
}

.doc-stat {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    color: var(--gray-600);
    font-weight: 600;
}

[data-theme="dark"] .doc-stat {
    color: var(--gray-400);
}

.doc-stat i {
    font-size: 0.68rem;
    color: #F59E0B;
}

.doc-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: auto;
}

.doc-filename {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.7rem;
    color: var(--gray-500);
    font-family: 'Courier New', monospace;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 140px;
}

.doc-filename i {
    color: var(--gray-400);
    font-size: 0.65rem;
}

.doc-download-btn {
    padding: 6px 12px;
    background: #F59E0B;
    color: var(--white);
    border-radius: var(--radius-sm);
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all var(--transition-fast);
    flex-shrink: 0;
}

.doc-download-btn:hover {
    background: #B45309;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245,158,11,0.3);
}

/* List View Adjustments */
.dokumen-grid[data-view="list"] .doc-body {
    padding: 16px 20px;
}

.dokumen-grid[data-view="list"] .doc-hero {
    border-radius: var(--radius) 0 0 var(--radius);
}

.dokumen-grid[data-view="list"] .doc-icon {
    font-size: 3rem;
}

/* Table View */
.dokumen-table-view .data-table {
    font-size: 0.85rem;
}

.dokumen-table-view .data-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 700;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .dokumen-table-view .data-table th {
    background: var(--dark);
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

.dokumen-table-view .data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

[data-theme="dark"] .dokumen-table-view .data-table td {
    border-color: rgba(255,255,255,0.05);
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

/* ===== SIDEBAR WIDGETS ===== */
.dokumen-sidebar {
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

/* ===== STORAGE WIDGET ===== */
.storage-widget {
    background: linear-gradient(135deg, rgba(245,158,11,0.05), rgba(180,83,9,0.05));
    border: 1px solid rgba(245,158,11,0.15);
}

.storage-visual {
    display: grid;
    grid-template-columns: 110px 1fr;
    gap: 16px;
    align-items: center;
}

.storage-circle {
    position: relative;
    width: 110px;
    height: 110px;
}

.storage-ring {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.storage-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.storage-percent {
    font-size: 1.8rem;
    font-weight: 800;
    font-family: 'Space Grotesk', sans-serif;
    line-height: 1;
}

.storage-label {
    font-size: 0.7rem;
    color: var(--gray-500);
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.storage-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.si-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 10px;
    background: var(--white);
    border-radius: var(--radius-sm);
    font-size: 0.78rem;
}

[data-theme="dark"] .si-row {
    background: rgba(255,255,255,0.05);
}

.si-label {
    color: var(--gray-600);
}

[data-theme="dark"] .si-label {
    color: var(--gray-400);
}

.si-row strong {
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .si-row strong {
    color: var(--white);
}

/* ===== TYPE STATS ===== */
.type-stat-item {
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .type-stat-item {
    border-color: rgba(255,255,255,0.05);
}

.type-stat-item:last-child {
    border-bottom: none;
}

.tsi-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.tsi-name {
    font-size: 0.88rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tsi-count {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .tsi-count {
    color: var(--white);
}

.tsi-bar {
    height: 5px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
    margin-bottom: 4px;
}

[data-theme="dark"] .tsi-bar {
    background: rgba(255,255,255,0.08);
}

.tsi-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

.tsi-meta {
    display: flex;
    gap: 12px;
    font-size: 0.68rem;
    color: var(--gray-500);
}

.tsi-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.tsi-meta i {
    font-size: 0.6rem;
}

/* ===== CATEGORY STATS ===== */
.cat-stat-item {
    display: block;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
    transition: all var(--transition-fast);
}

[data-theme="dark"] .cat-stat-item {
    border-color: rgba(255,255,255,0.05);
}

.cat-stat-item:last-child {
    border-bottom: none;
}

.cat-stat-item:hover {
    transform: translateX(4px);
}

.csi-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.csi-name {
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.csi-count {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .csi-count {
    color: var(--white);
}

.csi-bar {
    height: 4px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
    margin-bottom: 4px;
}

[data-theme="dark"] .csi-bar {
    background: rgba(255,255,255,0.08);
}

.csi-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

.csi-downloads {
    font-size: 0.68rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 4px;
}

.csi-downloads i {
    color: #F59E0B;
    font-size: 0.6rem;
}

/* ===== TOP ITEMS ===== */
.top-item {
    display: grid;
    grid-template-columns: 24px 40px 1fr;
    gap: 10px;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    transition: all var(--transition-fast);
}

[data-theme="dark"] .top-item {
    border-color: rgba(255,255,255,0.05);
}

.top-item:last-child {
    border-bottom: none;
}

.top-item:hover {
    transform: translateX(4px);
}

.ti-rank {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--gray-400);
    text-align: center;
    font-family: 'Space Grotesk', sans-serif;
}

.top-item:nth-child(1) .ti-rank {
    color: #F59E0B;
    font-size: 1rem;
}

.top-item:nth-child(2) .ti-rank {
    color: #94A3B8;
}

.top-item:nth-child(3) .ti-rank {
    color: #CD7F32;
}

.ti-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.ti-info {
    min-width: 0;
}

.ti-title {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

[data-theme="dark"] .ti-title {
    color: var(--gray-200);
}

.ti-meta {
    font-size: 0.68rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 5px;
}

.ti-meta i {
    color: #F59E0B;
    font-size: 0.6rem;
}

/* ===== RECENT ITEMS ===== */
.recent-item {
    display: grid;
    grid-template-columns: 36px 1fr;
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
    width: 36px;
    height: 36px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.ri-info {
    min-width: 0;
}

.ri-title {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

[data-theme="dark"] .ri-title {
    color: var(--gray-200);
}

.ri-meta {
    font-size: 0.68rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 4px;
}

.ri-meta i {
    color: #F59E0B;
    font-size: 0.6rem;
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
    background: linear-gradient(135deg, #F59E0B, #B45309);
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

/* ===== FORMATS CARD ===== */
.formats-card {
    background: linear-gradient(135deg, rgba(245,158,11,0.04), rgba(180,83,9,0.04));
}

.formats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}

.format-item {
    padding: 10px;
    border-radius: var(--radius-sm);
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    font-size: 0.72rem;
    font-weight: 700;
    transition: all var(--transition-fast);
}

.format-item:hover {
    transform: translateY(-2px);
}

.format-item i {
    font-size: 1.2rem;
}

.formats-note {
    padding: 10px;
    background: var(--white);
    border-radius: var(--radius-sm);
    font-size: 0.78rem;
    color: var(--gray-700);
    display: flex;
    align-items: center;
    gap: 8px;
}

[data-theme="dark"] .formats-note {
    background: rgba(255,255,255,0.05);
    color: var(--gray-300);
}

.formats-note i {
    color: #F59E0B;
}

.formats-note strong {
    color: #F59E0B;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== MODAL ===== */
.dokumen-modal-head {
    background: linear-gradient(135deg, #78350F 0%, #B45309 60%, #F59E0B 100%) !important;
}

/* Upload Large */
.upload-large {
    position: relative;
    border: 2px dashed var(--gray-300);
    border-radius: var(--radius-lg);
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all var(--transition);
    background: var(--white);
    min-height: 240px;
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
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(180,83,9,0.1));
    color: #F59E0B;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 16px;
    box-shadow: 0 8px 20px rgba(245,158,11,0.2);
}

.upload-placeholder-large h4 {
    font-size: 1.05rem;
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
    margin-bottom: 16px;
}

.upload-formats {
    display: flex;
    gap: 6px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.uf-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
}

.uf-item.pdf { background: rgba(220,38,38,0.12); color: #DC2626; }
.uf-item.docx { background: rgba(37,99,235,0.12); color: #2563EB; }
.uf-item.xlsx { background: rgba(22,163,74,0.12); color: #16A34A; }
.uf-item.pptx { background: rgba(234,88,12,0.12); color: #EA580C; }
.uf-item.zip { background: rgba(124,58,237,0.12); color: #7C3AED; }

.upload-note {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500);
    font-style: italic;
}

.upload-large input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

/* Upload Preview Box */
.upload-preview-box {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    border: 2px solid var(--gray-200);
}

[data-theme="dark"] .upload-preview-box {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
}

.upb-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    flex-shrink: 0;
    background: rgba(245,158,11,0.12);
    color: #F59E0B;
}

.upb-info {
    flex: 1;
    min-width: 0;
}

.upb-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 4px;
    word-break: break-all;
    font-family: 'Courier New', monospace;
}

[data-theme="dark"] .upb-name {
    color: var(--white);
}

.upb-meta {
    display: flex;
    gap: 8px;
    font-size: 0.78rem;
    color: var(--gray-600);
}

.upb-meta span:not(:first-child):not(:last-child) {
    color: var(--gray-400);
}

.upb-remove {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(239,68,68,0.1);
    color: #EF4444;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
    flex-shrink: 0;
}

.upb-remove:hover {
    background: #EF4444;
    color: var(--white);
    transform: rotate(90deg);
}

/* ===== PREVIEW MODAL ===== */
.preview-file-hero {
    padding: 40px 20px;
    text-align: center;
    margin: -28px -28px 20px;
    border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
    position: relative;
    overflow: hidden;
}

.preview-file-icon {
    font-size: 5rem;
    margin-bottom: 14px;
    filter: drop-shadow(0 8px 20px rgba(0,0,0,0.2));
}

.preview-file-title {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 8px;
    font-family: 'Space Grotesk', sans-serif;
}

.preview-file-meta {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    font-size: 0.85rem;
    opacity: 0.9;
}

.preview-file-meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
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

.preview-filename {
    padding: 10px 14px;
    background: var(--dark);
    color: #FCD34D;
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: 0.82rem;
    word-break: break-all;
}

[data-theme="dark"] .preview-filename {
    background: #0F172A;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .dokumen-layout {
        grid-template-columns: 1fr;
    }
    
    .dokumen-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .dokumen-page {
        padding: 16px;
    }
    
    .dokumen-hero {
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
    
    .dokumen-toolbar {
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
    
    .dokumen-grid {
        grid-template-columns: 1fr;
    }
    
    .dokumen-grid[data-view="list"] .dokumen-card {
        grid-template-columns: 1fr;
    }
    
    .form-grid-2 {
        grid-template-columns: 1fr;
    }
    
    .preview-info-grid {
        grid-template-columns: 1fr;
    }
    
    .storage-visual {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .storage-circle {
        margin: 0 auto;
    }
}

@media print {
    .dokumen-hero,
    .dokumen-sidebar,
    .dokumen-toolbar,
    .bulk-bar,
    .doc-quick-actions,
    .doc-download-btn,
    .card-checkbox {
        display: none !important;
    }
    
    .dokumen-layout {
        grid-template-columns: 1fr;
    }
    
    .dokumen-card {
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

    /* ===== FILE TYPE CONFIG ===== */
    const fileTypes = <?= json_encode($fileTypes) ?>;
    const categories = <?= json_encode($categories) ?>;

    function formatSize(bytes) {
        if (!bytes) return '0 B';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
        if (bytes < 1024*1024*1024) return (bytes/(1024*1024)).toFixed(2) + ' MB';
        return (bytes/(1024*1024*1024)).toFixed(2) + ' GB';
    }

    function getFileTypeInfo(ext) {
        return fileTypes[ext] || {icon: 'fa-file', color: '#6B7280', label: 'File', bg: 'rgba(107,114,128,0.12)'};
    }

    function getCategoryInfo(key) {
        return categories[key] || {label: key || 'Lainnya', icon: 'fa-folder', color: '#6B7280'};
    }

    /* ===== MODAL FUNCTIONS ===== */
    window.openModal = function() {
        document.getElementById('dokumenForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('filenameLama').value = '';
        document.getElementById('modalTitle').textContent = 'Unggah Dokumen';
        document.getElementById('modalSubtitle').textContent = 'Unggah file akademik untuk repository';
        document.getElementById('submitText').textContent = 'Simpan Dokumen';
        document.getElementById('currentFileDisplay').style.display = 'none';
        document.getElementById('uploadPlaceholder').style.display = 'flex';
        document.getElementById('uploadPreview').style.display = 'none';
        document.getElementById('fileDokumen').required = true;
        document.getElementById('deskripsiCount').textContent = '0';
        
        document.getElementById('dokumenModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('judul')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('dokumenModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.editDokumen = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Dokumen';
        document.getElementById('modalSubtitle').textContent = 'Perbarui informasi dokumen';
        document.getElementById('submitText').textContent = 'Perbarui Dokumen';
        document.getElementById('formId').value = data.id;
        document.getElementById('judul').value = data.judul;
        document.getElementById('kategori').value = data.kategori;
        document.getElementById('deskripsi').value = data.deskripsi || '';
        document.getElementById('filenameLama').value = data.filename || '';
        document.getElementById('fileDokumen').required = false;
        document.getElementById('deskripsiCount').textContent = (data.deskripsi || '').length;
        
        document.getElementById('uploadPlaceholder').style.display = 'flex';
        document.getElementById('uploadPreview').style.display = 'none';
        
        if (data.filename) {
            document.getElementById('currentFileDisplay').style.display = 'block';
            document.getElementById('currentFileName').textContent = data.filename + ' (' + formatSize(data.ukuran) + ')';
        } else {
            document.getElementById('currentFileDisplay').style.display = 'none';
        }
        
        document.getElementById('dokumenModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== PREVIEW MODAL ===== */
    window.previewDokumen = function(data) {
        const typeCfg = getFileTypeInfo(data.tipe_file);
        const catCfg = getCategoryInfo(data.kategori);
        
        const body = document.getElementById('previewBody');
        body.innerHTML = `
            <div class="preview-file-hero" style="background: ${typeCfg.bg}; color: ${typeCfg.color}">
                <div class="preview-file-icon">
                    <i class="fas ${typeCfg.icon}"></i>
                </div>
                <h2 class="preview-file-title" style="color: var(--dark)">${data.judul}</h2>
                <div class="preview-file-meta" style="color: var(--gray-600)">
                    <span style="padding:4px 12px;background:${catCfg.color}15;color:${catCfg.color};border-radius:20px;font-weight:600">
                        <i class="fas ${catCfg.icon}"></i> ${catCfg.label}
                    </span>
                    <span style="padding:4px 12px;background:${typeCfg.color}15;color:${typeCfg.color};border-radius:20px;font-weight:600">
                        <i class="fas ${typeCfg.icon}"></i> ${typeCfg.label}
                    </span>
                </div>
            </div>
            
            <div class="preview-info-grid">
                <div>
                    <div class="preview-label"><i class="fas fa-weight-hanging"></i> Ukuran File</div>
                    <div class="preview-value"><strong>${formatSize(data.ukuran)}</strong></div>
                </div>
                <div>
                    <div class="preview-label"><i class="fas fa-download"></i> Total Downloads</div>
                    <div class="preview-value"><strong style="color:#F59E0B">${Number(data.downloads || 0).toLocaleString('id-ID')}x</strong></div>
                </div>
                <div>
                    <div class="preview-label"><i class="fas fa-calendar"></i> Tanggal Upload</div>
                    <div class="preview-value">${new Date(data.created_at).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'})}</div>
                </div>
                <div>
                    <div class="preview-label"><i class="fas fa-clock"></i> Terakhir Update</div>
                    <div class="preview-value">${data.updated_at ? new Date(data.updated_at).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'}) : '-'}</div>
                </div>
            </div>
            
            <div class="preview-section">
                <div class="preview-label"><i class="fas fa-paperclip"></i> Nama File</div>
                <div class="preview-filename">${data.filename}</div>
            </div>
            
            ${data.deskripsi ? `
            <div class="preview-section">
                <div class="preview-label"><i class="fas fa-align-left"></i> Deskripsi</div>
                <div class="preview-value">${data.deskripsi}</div>
            </div>
            ` : ''}
            
            <div style="padding:14px;background:rgba(245,158,11,0.05);border-left:4px solid #F59E0B;border-radius:var(--radius-sm);display:flex;align-items:center;gap:10px">
                <i class="fas fa-info-circle" style="color:#F59E0B;font-size:1.2rem"></i>
                <div style="font-size:0.82rem;color:var(--gray-700);line-height:1.5">
                    Dokumen ini dapat diunduh oleh siapa saja dengan akses ke website. Pastikan konten sesuai untuk publik.
                </div>
            </div>
        `;
        
        document.getElementById('previewDownloadBtn').onclick = function() {
            window.location.href = '../uploads/dokumen/' + data.filename;
        };
        
        document.getElementById('previewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closePreview = function() {
        document.getElementById('previewModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    /* ===== FILE UPLOAD HANDLING ===== */
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileDokumen');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const uploadPreview = document.getElementById('uploadPreview');
    
    function showFilePreview(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        const typeCfg = getFileTypeInfo(ext);
        
        document.getElementById('previewIcon').innerHTML = `<i class="fas ${typeCfg.icon}"></i>`;
        document.getElementById('previewIcon').style.background = typeCfg.bg;
        document.getElementById('previewIcon').style.color = typeCfg.color;
        document.getElementById('previewName').textContent = file.name;
        document.getElementById('previewSize').textContent = formatSize(file.size);
        document.getElementById('previewType').textContent = typeCfg.label;
        
        uploadPlaceholder.style.display = 'none';
        uploadPreview.style.display = 'flex';
    }

    window.removeFile = function() {
        fileInput.value = '';
        uploadPlaceholder.style.display = 'flex';
        uploadPreview.style.display = 'none';
    };

    fileInput?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 20 * 1024 * 1024) {
                if (window.IFPro?.toast) window.IFPro.toast.show('Ukuran file maksimal 20MB', 'error');
                fileInput.value = '';
                return;
            }
            
            const ext = file.name.split('.').pop().toLowerCase();
            const allowed = ['pdf', 'docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'zip', 'rar'];
            if (!allowed.includes(ext)) {
                if (window.IFPro?.toast) window.IFPro.toast.show('Format file tidak didukung', 'error');
                fileInput.value = '';
                return;
            }
            
            showFilePreview(file);
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
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    /* ===== DESKRIPSI COUNTER ===== */
    const deskripsiInput = document.getElementById('deskripsi');
    const deskripsiCount = document.getElementById('deskripsiCount');
    
    deskripsiInput?.addEventListener('input', function() {
        deskripsiCount.textContent = this.value.length;
        if (this.value.length >= 480) {
            deskripsiCount.style.color = 'var(--warning)';
        } else {
            deskripsiCount.style.color = '';
        }
    });

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterKategori = document.getElementById('filterKategori');
    const filterType = document.getElementById('filterType');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    [filterKategori, filterType].forEach(el => {
        el?.addEventListener('change', applyFilters);
    });

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const kategori = filterKategori?.value;
        const type = filterType?.value;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (kategori) url.searchParams.set('kategori', kategori); else url.searchParams.delete('kategori');
        if (type) url.searchParams.set('type', type); else url.searchParams.delete('type');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'kategori', 'type', 'sort', 'dir'].forEach(p => url.searchParams.delete(p));
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
            const grid = document.getElementById('dokumenGrid');
            const table = document.getElementById('dokumenTable');
            
            if (view === 'table') {
                grid.style.display = 'none';
                table.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                table.style.display = 'none';
                grid.dataset.view = view;
            }
            
            localStorage.setItem('dokumen_view', view);
        });
    });

    const savedView = localStorage.getItem('dokumen_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');
    const allDocs = <?= json_encode($dokumenList) ?>;

    document.querySelectorAll('.dokumen-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
        });
    });

    document.getElementById('selectAllTable')?.addEventListener('change', function() {
        document.querySelectorAll('#dokumenTable .dokumen-check').forEach(c => {
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
        document.querySelectorAll('.dokumen-check').forEach(c => c.checked = false);
        const selectAll = document.getElementById('selectAllTable');
        if (selectAll) selectAll.checked = false;
        updateBulkBar();
    };

    window.bulkAction = function(action) {
        if (selectedIds.size === 0) return;
        
        const messages = {
            'bulk-delete': `Yakin hapus ${selectedIds.size} dokumen terpilih? File akan dihapus permanen!`
        };
        
        if (!confirm(messages[action])) return;
        
        const ids = Array.from(selectedIds).join(',');
        window.location.href = `?action=${action}&ids=${ids}`;
    };

    window.bulkDownload = function() {
        if (selectedIds.size === 0) return;
        
        if (window.IFPro?.toast) window.IFPro.toast.show(`Memulai download ${selectedIds.size} file...`, 'info');
        
        const ids = Array.from(selectedIds);
        let delay = 0;
        
        ids.forEach(id => {
            const doc = allDocs.find(d => d.id == id);
            if (doc) {
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = '../uploads/dokumen/' + doc.filename;
                    link.download = doc.filename;
                    link.click();
                }, delay);
                delay += 500;
            }
        });
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${ids.length} file sedang didownload`, 'success');
        }, delay + 500);
    };

    window.bulkDownloadAll = function() {
        if (!allDocs || allDocs.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada dokumen untuk didownload', 'warning');
            return;
        }
        
        if (!confirm(`Download semua ${allDocs.length} dokumen? Ini mungkin memakan waktu lama.`)) return;
        
        if (window.IFPro?.toast) window.IFPro.toast.show(`Memulai download ${allDocs.length} file...`, 'info');
        
        let delay = 0;
        allDocs.forEach(doc => {
            setTimeout(() => {
                const link = document.createElement('a');
                link.href = '../uploads/dokumen/' + doc.filename;
                link.download = doc.filename;
                link.click();
            }, delay);
            delay += 300;
        });
    };

    /* ===== EXPORT CSV ===== */
    window.exportDokumen = function() {
        const docs = <?= json_encode($dokumenList) ?>;
        
        if (!docs || docs.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['ID', 'Judul', 'Kategori', 'Tipe File', 'Filename', 'Ukuran (bytes)', 'Downloads', 'Deskripsi', 'Created At', 'Updated At'];
        const rows = docs.map(d => [
            d.id,
            `"${(d.judul || '').replace(/"/g, '""')}"`,
            d.kategori || '',
            d.tipe_file || '',
            `"${(d.filename || '').replace(/"/g, '""')}"`,
            d.ukuran || 0,
            d.downloads || 0,
            `"${(d.deskripsi || '').replace(/"/g, '""')}"`,
            d.created_at || '',
            d.updated_at || ''
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `dokumen-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${docs.length} dokumen berhasil di-export`, 'success');
        }, 500);
    };

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        // U = Upload
        if (e.key === 'u' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) && !e.ctrlKey && !e.metaKey) {
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
    document.getElementById('dokumenModal')?.addEventListener('click', function(e) {
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
        setTimeout(() => editDokumen(<?= json_encode($editData, JSON_HEX_APOS) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c📁 Dokumen v4.0 Loaded', 'color:#F59E0B;font-weight:bold;font-size:14px');
    console.log('%c   Features: File Manager, Storage Widget, Bulk Download, 3 View Modes', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: U (Upload), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>