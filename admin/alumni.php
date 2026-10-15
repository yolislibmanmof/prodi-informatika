<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Alumni';
include 'includes/header.php';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
}

/* ===== STATISTICS (Anti-Error) ===== */
$totalAlumni = 0;
$aktifCount = 0;
$nonaktifCount = 0;
$featuredCount = 0;
$workingCount = 0;
$avgIpk = 0;
$totalCities = 0;

if ($db) {
    try {
        $totalAlumni = (int)$db->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
        $aktifCount = (int)$db->query("SELECT COUNT(*) FROM alumni WHERE status='aktif'")->fetchColumn();
        $nonaktifCount = (int)$db->query("SELECT COUNT(*) FROM alumni WHERE status='nonaktif'")->fetchColumn();
        $featuredCount = (int)$db->query("SELECT COUNT(*) FROM alumni WHERE is_featured=1 AND status='aktif'")->fetchColumn();
        $workingCount = (int)$db->query("SELECT COUNT(*) FROM alumni WHERE status='aktif' AND perusahaan != '' AND perusahaan IS NOT NULL")->fetchColumn();
        $avgIpk = (float)$db->query("SELECT AVG(ipk) FROM alumni WHERE ipk IS NOT NULL AND ipk > 0")->fetchColumn();
        $totalCities = (int)$db->query("SELECT COUNT(DISTINCT kota) FROM alumni WHERE kota != '' AND kota IS NOT NULL")->fetchColumn();
    } catch (Throwable $e) {}
}

// Top tahun lulus
$topYears = [];
if ($db) {
    try {
        $topYears = $db->query("
            SELECT tahun_lulus, COUNT(*) as total 
            FROM alumni 
            WHERE status='aktif' 
            GROUP BY tahun_lulus 
            ORDER BY tahun_lulus DESC 
            LIMIT 6
        ")->fetchAll();
    } catch (Throwable $e) {}
}

// Top perusahaan
$topCompanies = [];
if ($db) {
    try {
        $topCompanies = $db->query("
            SELECT perusahaan, COUNT(*) as total 
            FROM alumni 
            WHERE status='aktif' AND perusahaan != '' AND perusahaan IS NOT NULL
            GROUP BY perusahaan 
            ORDER BY total DESC 
            LIMIT 6
        ")->fetchAll();
    } catch (Throwable $e) {}
}

// Top kota (untuk map widget)
$topCities = [];
if ($db) {
    try {
        $topCities = $db->query("
            SELECT kota, COUNT(*) as total 
            FROM alumni 
            WHERE status='aktif' AND kota != '' AND kota IS NOT NULL
            GROUP BY kota 
            ORDER BY total DESC 
            LIMIT 8
        ")->fetchAll();
    } catch (Throwable $e) {}
}

// IPK Distribution
$ipkDistribution = [
    'cumlaude' => 0,  // 3.51 - 4.00
    'sangat_baik' => 0, // 3.01 - 3.50
    'baik' => 0,       // 2.76 - 3.00
    'cukup' => 0       // 2.00 - 2.75
];
if ($db) {
    try {
        $ipkDistribution['cumlaude'] = (int)$db->query("SELECT COUNT(*) FROM alumni WHERE ipk >= 3.51 AND ipk <= 4.00")->fetchColumn();
        $ipkDistribution['sangat_baik'] = (int)$db->query("SELECT COUNT(*) FROM alumni WHERE ipk >= 3.01 AND ipk < 3.51")->fetchColumn();
        $ipkDistribution['baik'] = (int)$db->query("SELECT COUNT(*) FROM alumni WHERE ipk >= 2.76 AND ipk < 3.01")->fetchColumn();
        $ipkDistribution['cukup'] = (int)$db->query("SELECT COUNT(*) FROM alumni WHERE ipk >= 2.00 AND ipk < 2.76")->fetchColumn();
    } catch (Throwable $e) {}
}

// Alumni of the Month (featured dengan IPK tertinggi)
$alumniOfMonth = null;
if ($db) {
    try {
        $alumniOfMonth = $db->query("
            SELECT * FROM alumni 
            WHERE is_featured=1 AND status='aktif' 
            ORDER BY ipk DESC, tahun_lulus DESC 
            LIMIT 1
        ")->fetch();
    } catch (Throwable $e) {}
}

/* ===== HANDLE ACTIONS ===== */
if (isset($_GET['action']) && $db) {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($_GET['action'] === 'delete' && $id) {
        try {
            $stmt = $db->prepare("SELECT foto FROM alumni WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['foto'] && file_exists(__DIR__ . '/../uploads/' . $row['foto'])) {
                unlink(__DIR__ . '/../uploads/' . $row['foto']);
            }
            $db->prepare("DELETE FROM alumni WHERE id = ?")->execute([$id]);
            setFlash('success', 'Data alumni berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus alumni');
        }
        header('Location: alumni.php');
        exit;
    }
    
    if ($_GET['action'] === 'toggle' && $id) {
        try {
            $db->prepare("UPDATE alumni SET status = IF(status='aktif','nonaktif','aktif') WHERE id = ?")->execute([$id]);
            setFlash('success', 'Status alumni berhasil diubah');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mengubah status');
        }
        header('Location: alumni.php');
        exit;
    }
    
    if ($_GET['action'] === 'bulk-delete' && !empty($_GET['ids'])) {
        try {
            $ids = array_map('intval', explode(',', $_GET['ids']));
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            $stmt = $db->prepare("SELECT foto FROM alumni WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $row) {
                if ($row['foto'] && file_exists(__DIR__ . '/../uploads/' . $row['foto'])) {
                    unlink(__DIR__ . '/../uploads/' . $row['foto']);
                }
            }
            
            $db->prepare("DELETE FROM alumni WHERE id IN ($placeholders)")->execute($ids);
            setFlash('success', count($ids) . ' alumni berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus alumni');
        }
        header('Location: alumni.php');
        exit;
    }
    
    // Bulk activate/deactivate
    if (($_GET['action'] === 'bulk-activate' || $_GET['action'] === 'bulk-deactivate') && !empty($_GET['ids'])) {
        try {
            $ids = array_map('intval', explode(',', $_GET['ids']));
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $newStatus = $_GET['action'] === 'bulk-activate' ? 'aktif' : 'nonaktif';
            
            $db->prepare("UPDATE alumni SET status = ? WHERE id IN ($placeholders)")->execute(array_merge([$newStatus], $ids));
            setFlash('success', count($ids) . ' alumni berhasil ' . ($_GET['action'] === 'bulk-activate' ? 'diaktifkan' : 'dinonaktifkan'));
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mengubah status');
        }
        header('Location: alumni.php');
        exit;
    }
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $nama = clean($_POST['nama']);
        $email = clean($_POST['email'] ?? '');
        $telepon = clean($_POST['telepon'] ?? '');
        $tahun_lulus = (int)$_POST['tahun_lulus'];
        $ipk = !empty($_POST['ipk']) ? (float)$_POST['ipk'] : null;
        $pekerjaan = clean($_POST['pekerjaan']);
        $perusahaan = clean($_POST['perusahaan']);
        $kota = clean($_POST['kota'] ?? '');
        $linkedin = clean($_POST['linkedin'] ?? '');
        $instagram = clean($_POST['instagram'] ?? '');
        $quote = clean($_POST['quote'] ?? '');
        $status = clean($_POST['status'] ?? 'aktif');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        $foto = $_POST['foto_lama'] ?? null;
        if (!empty($_FILES['foto']['name'])) {
            $newImage = uploadImage($_FILES['foto'], 'uploads/alumni');
            if ($newImage) {
                if ($foto && file_exists(__DIR__ . '/../' . $foto)) unlink(__DIR__ . '/../' . $foto);
                $foto = $newImage;
            }
        }
        
        if ($id) {
            $stmt = $db->prepare("UPDATE alumni SET nama=?, email=?, telepon=?, tahun_lulus=?, ipk=?, pekerjaan=?, perusahaan=?, kota=?, linkedin=?, instagram=?, quote=?, status=?, is_featured=?, foto=? WHERE id=?");
            $stmt->execute([$nama, $email, $telepon, $tahun_lulus, $ipk, $pekerjaan, $perusahaan, $kota, $linkedin, $instagram, $quote, $status, $is_featured, $foto, $id]);
            setFlash('success', 'Data alumni berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO alumni (nama, email, telepon, tahun_lulus, ipk, pekerjaan, perusahaan, kota, linkedin, instagram, quote, status, is_featured, foto, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$nama, $email, $telepon, $tahun_lulus, $ipk, $pekerjaan, $perusahaan, $kota, $linkedin, $instagram, $quote, $status, $is_featured, $foto]);
            setFlash('success', 'Alumni baru berhasil ditambahkan');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan data: ' . $e->getMessage());
    }
    header('Location: alumni.php');
    exit;
}

/* ===== FETCH & FILTER ===== */
$search = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
$filterStatus = $_GET['status'] ?? '';
$filterYear = $_GET['year'] ?? '';
$filterIpk = $_GET['ipk'] ?? '';
$sortBy = $_GET['sort'] ?? 'tahun_lulus';
$sortDir = $_GET['dir'] ?? 'DESC';

$where = "1=1";
$params = [];
if ($filterStatus) { $where .= " AND status = ?"; $params[] = $filterStatus; }
if ($filterYear) { $where .= " AND tahun_lulus = ?"; $params[] = (int)$filterYear; }
if ($filterIpk === 'cumlaude') { $where .= " AND ipk >= 3.51"; }
elseif ($filterIpk === 'sangat_baik') { $where .= " AND ipk >= 3.01 AND ipk < 3.51"; }
elseif ($filterIpk === 'baik') { $where .= " AND ipk >= 2.76 AND ipk < 3.01"; }
if (isset($_GET['q']) && $_GET['q']) { 
    $where .= " AND (nama LIKE ? OR pekerjaan LIKE ? OR perusahaan LIKE ? OR kota LIKE ?)"; 
    $params[] = $search; $params[] = $search; $params[] = $search; $params[] = $search;
}

$validSorts = ['tahun_lulus', 'nama', 'created_at', 'pekerjaan', 'ipk'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'tahun_lulus';
$sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

$alumniList = [];
if ($db) {
    try {
        $sql = "SELECT * FROM alumni WHERE $where ORDER BY $sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $alumniList = $stmt->fetchAll();
    } catch (Throwable $e) {}
}

$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM alumni WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

// Unique years for filter
$years = [];
if ($db) {
    try {
        $years = $db->query("SELECT DISTINCT tahun_lulus FROM alumni ORDER BY tahun_lulus DESC")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {}
}

// Helper: IPK label
function getIpkLabel($ipk) {
    if ($ipk >= 3.51) return ['label' => 'Cumlaude', 'color' => '#F59E0B', 'bg' => 'rgba(245,158,11,0.12)', 'icon' => 'fa-crown'];
    if ($ipk >= 3.01) return ['label' => 'Sangat Baik', 'color' => '#10B981', 'bg' => 'rgba(16,185,129,0.12)', 'icon' => 'fa-star'];
    if ($ipk >= 2.76) return ['label' => 'Baik', 'color' => '#3B82F6', 'bg' => 'rgba(59,130,246,0.12)', 'icon' => 'fa-check-circle'];
    return ['label' => 'Cukup', 'color' => '#6B7280', 'bg' => 'rgba(107,114,128,0.12)', 'icon' => 'fa-circle'];
}

$employmentRate = $totalAlumni > 0 ? round(($workingCount / $totalAlumni) * 100) : 0;
?>

<div class="page-content alumni-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="alumni-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-user-graduate"></i>
                    <span>Tracer Study & Alumni Network</span>
                </div>
                <h1 class="hero-title">Kelola Database Alumni</h1>
                <p class="hero-subtitle">
                    Jejaring alumni prodi, tracer study, dan koneksi industri
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-users"></i>
                        <span><strong><?= number_format($totalAlumni) ?></strong> Total Alumni</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-briefcase"></i>
                        <span><strong><?= $employmentRate ?>%</strong> Bekerja</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><strong><?= $totalCities ?></strong> Kota</span>
                    </div>
                    <?php if ($avgIpk > 0): ?>
                    <div class="hms-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Avg IPK <strong><?= number_format($avgIpk, 2) ?></strong></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportToCSV()" title="Export ke CSV">
                    <i class="fas fa-file-csv"></i>
                    <span>Export CSV</span>
                </button>
                <button class="btn btn-ghost" onclick="window.print()" title="Print">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Alumni</span>
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
    <div class="alumni-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="alumni-main-col">
            <!-- ===== STATS CARDS ===== -->
            <div class="stats-grid alumni-stats">
                <div class="stat-card stat-blue">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $totalAlumni ?>">0</h3>
                        <p>Total Alumni</p>
                        <span class="stat-trend up"><i class="fas fa-users"></i> Terdaftar</span>
                    </div>
                </div>

                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $aktifCount ?>">0</h3>
                        <p>Alumni Aktif</p>
                        <span class="stat-trend up">
                            <i class="fas fa-chart-line"></i> 
                            <?= $totalAlumni > 0 ? round(($aktifCount / $totalAlumni) * 100) : 0 ?>%
                        </span>
                    </div>
                    <div class="stat-progress-ring">
                        <svg viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="15" fill="none" stroke="currentColor" stroke-width="3" opacity="0.15"/>
                            <circle cx="18" cy="18" r="15" fill="none" stroke="currentColor" stroke-width="3" 
                                stroke-dasharray="<?= $totalAlumni > 0 ? ($aktifCount / $totalAlumni) * 94.25 : 0 ?>, 94.25" 
                                stroke-linecap="round" transform="rotate(-90 18 18)"/>
                        </svg>
                    </div>
                </div>

                <div class="stat-card stat-orange">
                    <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $workingCount ?>">0</h3>
                        <p>Sudah Bekerja</p>
                        <span class="stat-trend up"><i class="fas fa-building"></i> <?= $employmentRate ?>%</span>
                    </div>
                </div>

                <div class="stat-card stat-purple">
                    <div class="stat-icon"><i class="fas fa-crown"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $featuredCount ?>">0</h3>
                        <p>Alumni Unggulan</p>
                        <span class="stat-trend up"><i class="fas fa-star"></i> Featured</span>
                    </div>
                </div>
            </div>

            <!-- ===== TOOLBAR ===== -->
            <div class="card alumni-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama, pekerjaan, perusahaan, kota..." value="<?= clean($_GET['q'] ?? '') ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterStatus" class="toolbar-select">
                            <option value="">📊 Semua Status</option>
                            <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>🟢 Aktif</option>
                            <option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : '' ?>>⚪ Nonaktif</option>
                        </select>
                        
                        <select id="filterYear" class="toolbar-select">
                            <option value="">📅 Semua Tahun</option>
                            <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select id="filterIpk" class="toolbar-select">
                            <option value="">🎓 Semua IPK</option>
                            <option value="cumlaude" <?= $filterIpk === 'cumlaude' ? 'selected' : '' ?>>🏆 Cumlaude (3.51-4.00)</option>
                            <option value="sangat_baik" <?= $filterIpk === 'sangat_baik' ? 'selected' : '' ?>>⭐ Sangat Baik (3.01-3.50)</option>
                            <option value="baik" <?= $filterIpk === 'baik' ? 'selected' : '' ?>>✓ Baik (2.76-3.00)</option>
                        </select>
                        
                        <button class="toolbar-icon-btn" onclick="resetFilters()" title="Reset Filter">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <div class="sort-dropdown">
                        <button class="toolbar-icon-btn" id="sortBtn" title="Urutkan">
                            <i class="fas fa-sort-amount-down"></i>
                        </button>
                        <div class="sort-menu" id="sortMenu">
                            <a href="?sort=tahun_lulus&dir=DESC" class="sort-item <?= $sortBy === 'tahun_lulus' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-calendar"></i> Tahun Terbaru
                            </a>
                            <a href="?sort=tahun_lulus&dir=ASC" class="sort-item <?= $sortBy === 'tahun_lulus' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-calendar"></i> Tahun Terlama
                            </a>
                            <a href="?sort=nama&dir=ASC" class="sort-item <?= $sortBy === 'nama' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Nama A-Z
                            </a>
                            <a href="?sort=ipk&dir=DESC" class="sort-item <?= $sortBy === 'ipk' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-graduation-cap"></i> IPK Tertinggi
                            </a>
                            <a href="?sort=created_at&dir=DESC" class="sort-item <?= $sortBy === 'created_at' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-history"></i> Baru Ditambah
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
                </div>
            </div>

            <!-- ===== BULK ACTIONS BAR ===== -->
            <div class="bulk-bar" id="bulkBar">
                <div class="bulk-info">
                    <i class="fas fa-check-circle"></i>
                    <span><strong id="selectedCount">0</strong> alumni dipilih</span>
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

            <!-- ===== ALUMNI GRID ===== -->
            <div class="alumni-grid" id="alumniGrid" data-view="grid">
                <?php if (!empty($alumniList)): ?>
                    <?php foreach ($alumniList as $a): 
                        $initial = strtoupper(substr($a['nama'] ?? 'A', 0, 1));
                        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#14B8A6'];
                        $color = $colors[ord($initial) % count($colors)];
                        $ipkInfo = !empty($a['ipk']) ? getIpkLabel($a['ipk']) : null;
                    ?>
                    <div class="alumni-card <?= !empty($a['is_featured']) ? 'is-featured' : '' ?> <?= ($a['status'] ?? 'aktif') === 'nonaktif' ? 'is-inactive' : '' ?>" data-id="<?= $a['id'] ?? 0 ?>">
                        <!-- Checkbox -->
                        <label class="card-checkbox">
                            <input type="checkbox" class="alumni-check" value="<?= $a['id'] ?? 0 ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <?php if (!empty($a['is_featured'])): ?>
                        <div class="featured-star" title="Alumni Unggulan">
                            <i class="fas fa-crown"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="alumni-card-header" onclick="previewAlumni(<?= htmlspecialchars(json_encode($a, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                            <div class="alumni-avatar">
                                <?php if (!empty($a['foto'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($a['foto']) ?>" alt="<?= clean($a['nama'] ?? 'Alumni') ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="avatar-placeholder" style="background: linear-gradient(135deg, <?= $color ?>, <?= $color ?>dd)">
                                        <?= $initial ?>
                                    </div>
                                <?php endif; ?>
                                <span class="status-dot <?= $a['status'] ?? 'aktif' ?>"></span>
                            </div>
                            
                            <div class="alumni-card-header-bg" style="background: linear-gradient(135deg, <?= $color ?>22, <?= $color ?>11)"></div>
                            
                            <div class="alumni-card-overlay">
                                <div class="quick-actions" onclick="event.stopPropagation()">
                                    <button onclick="editAlumni(<?= htmlspecialchars(json_encode($a, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="previewAlumni(<?= htmlspecialchars(json_encode($a, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="?action=toggle&id=<?= $a['id'] ?? 0 ?>" title="<?= ($a['status'] ?? 'aktif') === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                        <i class="fas fa-<?= ($a['status'] ?? 'aktif') === 'aktif' ? 'eye-slash' : 'eye' ?>"></i>
                                    </a>
                                    <a href="?action=delete&id=<?= $a['id'] ?? 0 ?>" title="Hapus" data-confirm="Yakin hapus data alumni ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="alumni-card-body">
                            <div class="alumni-card-top-row">
                                <?php if ($ipkInfo): ?>
                                <span class="ipk-badge" style="background: <?= $ipkInfo['bg'] ?>; color: <?= $ipkInfo['color'] ?>">
                                    <i class="fas <?= $ipkInfo['icon'] ?>"></i>
                                    IPK <?= number_format($a['ipk'], 2) ?>
                                </span>
                                <?php endif; ?>
                                <span class="alumni-year">
                                    <i class="fas fa-graduation-cap"></i>
                                    <?= $a['tahun_lulus'] ?? date('Y') ?>
                                </span>
                            </div>

                            <h3 class="alumni-name"><?= clean($a['nama'] ?? 'Tanpa Nama') ?></h3>
                            <p class="alumni-role"><?= clean($a['pekerjaan'] ?? 'Belum bekerja') ?></p>
                            
                            <?php if (!empty($a['perusahaan'])): ?>
                            <div class="alumni-company">
                                <i class="fas fa-building"></i>
                                <span><?= clean($a['perusahaan']) ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($a['kota'])): ?>
                            <div class="alumni-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= clean($a['kota']) ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($a['quote'])): ?>
                            <div class="alumni-quote">
                                <i class="fas fa-quote-left"></i>
                                <p><?= clean(mb_substr($a['quote'], 0, 70)) ?><?= strlen($a['quote']) > 70 ? '...' : '' ?></p>
                            </div>
                            <?php endif; ?>

                            <div class="alumni-socials">
                                <?php if (!empty($a['linkedin'])): ?>
                                <a href="<?= htmlspecialchars($a['linkedin']) ?>" target="_blank" class="social-link linkedin" title="LinkedIn">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($a['instagram'])): ?>
                                <a href="https://instagram.com/<?= htmlspecialchars($a['instagram']) ?>" target="_blank" class="social-link instagram" title="Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($a['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($a['email']) ?>" class="social-link email" title="Email">
                                    <i class="fas fa-envelope"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($a['telepon'])): ?>
                                <a href="tel:<?= htmlspecialchars($a['telepon']) ?>" class="social-link phone" title="Telepon">
                                    <i class="fas fa-phone"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="alumni-card-footer">
                            <button class="edit-btn" onclick="editAlumni(<?= htmlspecialchars(json_encode($a, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <span class="alumni-meta">
                                <i class="fas fa-clock"></i>
                                <?= function_exists('formatTanggal') ? formatTanggal($a['created_at'] ?? date('Y-m-d H:i:s')) : date('d M Y', strtotime($a['created_at'] ?? 'now')) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration">
                            <svg viewBox="0 0 200 150" width="200" height="150">
                                <ellipse cx="100" cy="130" rx="70" ry="10" fill="var(--gray-200)" opacity="0.5"/>
                                <circle cx="100" cy="60" r="35" fill="var(--primary)" opacity="0.15"/>
                                <circle cx="100" cy="50" r="20" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <path d="M 65 90 Q 100 110 135 90 L 135 120 Q 100 135 65 120 Z" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <circle cx="150" cy="40" r="15" fill="var(--primary)" opacity="0.9"/>
                                <line x1="150" y1="33" x2="150" y2="47" stroke="white" stroke-width="3" stroke-linecap="round"/>
                                <line x1="143" y1="40" x2="157" y2="40" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Tidak ada alumni ditemukan</h3>
                        <p>Mulai tambahkan data alumni untuk tracer study</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-plus"></i> Tambah Alumni Pertama
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Table View -->
            <?php if (!empty($alumniList)): ?>
            <div class="alumni-table-view" id="alumniTable" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="selectAllTable"></th>
                                    <th>Foto</th>
                                    <th>Nama</th>
                                    <th>Pekerjaan</th>
                                    <th>Perusahaan</th>
                                    <th>Tahun</th>
                                    <th>IPK</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alumniList as $a): 
                                    $initial = strtoupper(substr($a['nama'] ?? 'A', 0, 1));
                                    $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'];
                                    $color = $colors[ord($initial) % count($colors)];
                                    $ipkInfo = $a['ipk'] ? getIpkLabel($a['ipk']) : null;
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="alumni-check" value="<?= $a['id'] ?>"></td>
                                    <td>
                                        <?php if ($a['foto']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($a['foto']) ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover">
                                        <?php else: ?>
                                            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>dd);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700"><?= $initial ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= clean($a['nama']) ?></strong><?php if ($a['is_featured']): ?> <i class="fas fa-crown" style="color:#F59E0B" title="Featured"></i><?php endif; ?></td>
                                    <td><?= clean($a['pekerjaan'] ?: '-') ?></td>
                                    <td><?= clean($a['perusahaan'] ?: '-') ?></td>
                                    <td><?= $a['tahun_lulus'] ?></td>
                                    <td>
                                        <?php if ($ipkInfo): ?>
                                        <span class="badge" style="background:<?= $ipkInfo['bg'] ?>;color:<?= $ipkInfo['color'] ?>">
                                            <i class="fas <?= $ipkInfo['icon'] ?>"></i> <?= number_format($a['ipk'], 2) ?>
                                        </span>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $a['status'] === 'aktif' ? 'success' : 'default' ?>">
                                            <?= ucfirst($a['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-sm" onclick="previewAlumni(<?= htmlspecialchars(json_encode($a, JSON_HEX_APOS)) ?>)" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-sm" onclick="editAlumni(<?= htmlspecialchars(json_encode($a, JSON_HEX_APOS)) ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=delete&id=<?= $a['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus alumni ini?" title="Hapus">
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
                <span>Menampilkan <strong><?= count($alumniList) ?></strong> alumni</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="alumni-sidebar">
            <!-- Alumni of the Month -->
            <?php if ($alumniOfMonth): 
                $aomColor = ['#8B5CF6', '#EC4899', '#3B82F6'][ord(substr($alumniOfMonth['nama'], 0, 1)) % 3];
                $aomIpk = $alumniOfMonth['ipk'] ? getIpkLabel($alumniOfMonth['ipk']) : null;
            ?>
            <div class="card widget-card alumni-of-month">
                <div class="aom-header">
                    <i class="fas fa-crown"></i>
                    <span>Alumni Unggulan</span>
                </div>
                <div class="aom-body" onclick="previewAlumni(<?= htmlspecialchars(json_encode($alumniOfMonth, JSON_HEX_APOS)) ?>)">
                    <div class="aom-avatar">
                        <?php if ($alumniOfMonth['foto']): ?>
                            <img src="../uploads/<?= htmlspecialchars($alumniOfMonth['foto']) ?>" alt="">
                        <?php else: ?>
                            <div class="aom-placeholder" style="background: linear-gradient(135deg, <?= $aomColor ?>, <?= $aomColor ?>dd)">
                                <?= strtoupper(substr($alumniOfMonth['nama'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="aom-crown"><i class="fas fa-crown"></i></div>
                    </div>
                    <h4 class="aom-name"><?= clean($alumniOfMonth['nama']) ?></h4>
                    <p class="aom-role"><?= clean($alumniOfMonth['pekerjaan']) ?></p>
                    <p class="aom-company"><i class="fas fa-building"></i> <?= clean($alumniOfMonth['perusahaan']) ?></p>
                    <?php if ($aomIpk): ?>
                    <div class="aom-ipk" style="background: <?= $aomIpk['bg'] ?>; color: <?= $aomIpk['color'] ?>">
                        <i class="fas <?= $aomIpk['icon'] ?>"></i>
                        IPK <?= number_format($alumniOfMonth['ipk'], 2) ?> • <?= $aomIpk['label'] ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- IPK Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-graduation-cap"></i> Distribusi IPK</h3>
                    <?php if ($avgIpk > 0): ?>
                    <span class="widget-badge">Avg: <?= number_format($avgIpk, 2) ?></span>
                    <?php endif; ?>
                </div>
                <div class="widget-body">
                    <?php 
                    $totalWithIpk = array_sum($ipkDistribution);
                    $maxIpk = max($ipkDistribution) ?: 1;
                    $ipkConfig = [
                        'cumlaude' => ['label' => 'Cumlaude', 'range' => '3.51-4.00', 'color' => '#F59E0B', 'icon' => 'fa-crown'],
                        'sangat_baik' => ['label' => 'Sangat Baik', 'range' => '3.01-3.50', 'color' => '#10B981', 'icon' => 'fa-star'],
                        'baik' => ['label' => 'Baik', 'range' => '2.76-3.00', 'color' => '#3B82F6', 'icon' => 'fa-check-circle'],
                        'cukup' => ['label' => 'Cukup', 'range' => '2.00-2.75', 'color' => '#6B7280', 'icon' => 'fa-circle'],
                    ];
                    ?>
                    <?php foreach ($ipkConfig as $key => $cfg): 
                        $count = $ipkDistribution[$key];
                        $pct = $totalWithIpk > 0 ? round(($count / $totalWithIpk) * 100) : 0;
                        $barPct = round(($count / $maxIpk) * 100);
                    ?>
                    <div class="ipk-item">
                        <div class="ipk-head">
                            <span class="ipk-label" style="color: <?= $cfg['color'] ?>">
                                <i class="fas <?= $cfg['icon'] ?>"></i>
                                <?= $cfg['label'] ?>
                                <small><?= $cfg['range'] ?></small>
                            </span>
                            <span class="ipk-stats">
                                <strong><?= $count ?></strong>
                                <span class="ipk-pct"><?= $pct ?>%</span>
                            </span>
                        </div>
                        <div class="ipk-bar">
                            <div class="ipk-bar-fill" style="width: <?= $barPct ?>%; background: <?= $cfg['color'] ?>"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Companies -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-building"></i> Top Perusahaan</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($topCompanies)): ?>
                        <?php 
                        $maxCompany = max(array_column($topCompanies, 'total'));
                        foreach ($topCompanies as $i => $tc): 
                            $pct = round(($tc['total'] / $maxCompany) * 100);
                            $companyInitial = strtoupper(substr($tc['perusahaan'], 0, 2));
                            $companyColors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4'];
                            $companyColor = $companyColors[$i % count($companyColors)];
                        ?>
                        <div class="company-item">
                            <div class="company-logo" style="background: <?= $companyColor ?>15; color: <?= $companyColor ?>">
                                <?= $companyInitial ?>
                            </div>
                            <div class="company-info">
                                <div class="company-name"><?= clean($tc['perusahaan']) ?></div>
                                <div class="company-bar">
                                    <div class="company-bar-fill" style="width: <?= $pct ?>%; background: <?= $companyColor ?>"></div>
                                </div>
                            </div>
                            <div class="company-count"><?= $tc['total'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-building"></i>
                            <p>Belum ada data perusahaan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cities Map (simulasi) -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-map-marked-alt"></i> Sebaran Kota</h3>
                    <span class="widget-badge"><?= $totalCities ?> kota</span>
                </div>
                <div class="widget-body">
                    <?php if (!empty($topCities)): 
                        $maxCity = max(array_column($topCities, 'total'));
                    ?>
                        <div class="cities-cloud">
                            <?php foreach ($topCities as $i => $city): 
                                $size = 0.75 + (($city['total'] / $maxCity) * 0.75);
                                $opacity = 0.6 + (($city['total'] / $maxCity) * 0.4);
                            ?>
                            <span class="city-tag" style="font-size: <?= $size ?>rem; opacity: <?= $opacity ?>" title="<?= $city['total'] ?> alumni">
                                <?= clean($city['kota']) ?>
                                <sup><?= $city['total'] ?></sup>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>Belum ada data kota</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Graduation Years -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-calendar-alt"></i> Tahun Lulus</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($topYears)): 
                        $maxYear = max(array_column($topYears, 'total'));
                    ?>
                        <?php foreach ($topYears as $ty): 
                            $pct = round(($ty['total'] / $maxYear) * 100);
                        ?>
                        <div class="year-item">
                            <div class="year-head">
                                <span class="year-label"><?= $ty['tahun_lulus'] ?></span>
                                <span class="year-count"><?= $ty['total'] ?></span>
                            </div>
                            <div class="year-bar">
                                <div class="year-bar-fill" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-calendar"></i>
                            <p>Belum ada data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== MODAL FORM ===== -->
<div class="modal" id="alumniModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header modal-header-gradient alumni-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <h3 id="modalTitle">Tambah Alumni Baru</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Lengkapi data alumni di bawah ini</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="alumniForm" class="modal-body">
            <input type="hidden" name="id" id="formId">
            <input type="hidden" name="foto_lama" id="fotoLama">
            
            <div class="form-sections">
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-user"></i> Data Pribadi</h4>
                    <div class="form-grid-2">
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label>Nama Lengkap <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" name="nama" id="nama" required placeholder="Nama lengkap alumni">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <div class="input-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" id="email" placeholder="email@domain.com">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <div class="input-icon">
                                <i class="fas fa-phone"></i>
                                <input type="tel" name="telepon" id="telepon" placeholder="08xxxxxxxxxx">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Kota Domisili</label>
                            <div class="input-icon">
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" name="kota" id="kota" placeholder="Kota tempat tinggal">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-graduation-cap"></i> Data Akademik</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Tahun Lulus <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-calendar"></i>
                                <input type="number" name="tahun_lulus" id="tahun_lulus" required min="2000" max="2035" value="<?= date('Y') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>IPK (0.00 - 4.00)</label>
                            <div class="input-icon">
                                <i class="fas fa-award"></i>
                                <input type="number" name="ipk" id="ipk" step="0.01" min="0" max="4" placeholder="3.50">
                            </div>
                            <small class="ipk-hint" id="ipkHint" style="color:var(--gray-500);font-size:.72rem;margin-top:4px;display:block"></small>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-briefcase"></i> Data Karir</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Pekerjaan / Jabatan <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-briefcase"></i>
                                <input type="text" name="pekerjaan" id="pekerjaan" required placeholder="Contoh: Software Engineer">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Perusahaan / Instansi <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-building"></i>
                                <input type="text" name="perusahaan" id="perusahaan" required placeholder="Contoh: GoTo Group">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>LinkedIn URL</label>
                            <div class="input-icon">
                                <i class="fab fa-linkedin"></i>
                                <input type="url" name="linkedin" id="linkedin" placeholder="https://linkedin.com/in/...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Instagram</label>
                            <div class="input-icon">
                                <i class="fab fa-instagram"></i>
                                <input type="text" name="instagram" id="instagram" placeholder="username">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-quote-right"></i> Testimoni</h4>
                    <div class="form-group">
                        <label>Quote / Kesan Pesan</label>
                        <div class="textarea-wrap">
                            <textarea name="quote" id="quote" rows="3" placeholder="Ceritakan pengalaman kuliah dan karir Anda..." maxlength="300"></textarea>
                            <span class="char-counter"><span id="quoteCount">0</span>/300</span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-cog"></i> Media & Pengaturan</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Foto Profil</label>
                            <div class="upload-area upload-avatar" id="uploadArea">
                                <div class="upload-placeholder">
                                    <i class="fas fa-camera"></i>
                                    <span>Upload Foto</span>
                                    <small>Max 5MB</small>
                                </div>
                                <img id="previewImg" class="upload-preview" style="display:none">
                                <input type="file" name="foto" id="fotoInput" accept="image/*">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <div class="input-icon">
                                <i class="fas fa-info-circle"></i>
                                <select name="status" id="status">
                                    <option value="aktif">🟢 Aktif</option>
                                    <option value="nonaktif">⚪ Nonaktif</option>
                                </select>
                            </div>
                            
                            <label class="checkbox-card" style="margin-top:12px">
                                <input type="checkbox" name="is_featured" id="is_featured" value="1">
                                <span class="checkbox-custom"></span>
                                <span class="checkbox-label">
                                    <strong><i class="fas fa-crown" style="color:#F59E0B"></i> Alumni Unggulan</strong>
                                    <small>Tampil di halaman utama</small>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Alumni
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== PREVIEW MODAL ===== -->
<div class="modal" id="previewModal">
    <div class="modal-content" style="max-width: 640px;">
        <div class="modal-header modal-header-gradient alumni-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <h3>Preview Alumni</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Detail lengkap profil alumni</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePreview()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="previewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePreview()">Tutup</button>
            <button class="btn btn-primary" id="previewEditBtn"><i class="fas fa-edit"></i> Edit Alumni</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   ALUMNI PAGE - KEREN EDITION v4.0
   Integrated with admin.css
============================================================ */

.alumni-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.alumni-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #0F172A 0%, #8B5CF6 60%, #EC4899 100%);
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
    background: radial-gradient(circle, #EC4899, transparent);
    top: -120px; right: 8%;
}

.orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, #8B5CF6, transparent);
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
    color: #F9A8D4;
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
}

.btn-ghost:hover {
    background: rgba(255,255,255,0.22);
    transform: translateY(-2px);
}

/* ===== LAYOUT ===== */
.alumni-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
    align-items: start;
}

.alumni-main-col {
    min-width: 0;
}

/* ===== STATS ===== */
.alumni-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-card {
    position: relative;
    overflow: hidden;
}

.stat-blue .stat-icon { background: linear-gradient(135deg, #3B82F6, #1E40AF); }
.stat-green .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-orange .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }
.stat-purple .stat-icon { background: linear-gradient(135deg, #8B5CF6, #5B21B6); }

.stat-progress-ring {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    color: #10B981;
}

.stat-progress-ring svg {
    width: 100%;
    height: 100%;
}

/* ===== TOOLBAR ===== */
.alumni-toolbar {
    padding: 16px 20px;
    margin-bottom: 20px;
}

.alumni-toolbar {
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
    border-color: #8B5CF6;
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(139,92,246,0.1);
}

[data-theme="dark"] .toolbar-search input:focus {
    background: rgba(255,255,255,0.08);
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
    border-color: #8B5CF6;
    box-shadow: 0 0 0 3px rgba(139,92,246,0.1);
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
    background: #8B5CF6;
    color: var(--white);
    border-color: #8B5CF6;
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

[data-theme="dark"] .sort-item:hover {
    background: rgba(255,255,255,0.05);
}

.sort-item.active {
    background: rgba(139,92,246,0.1);
    color: #8B5CF6;
    font-weight: 600;
}

.sort-item i {
    width: 16px;
    color: var(--gray-500);
}

.sort-item.active i {
    color: #8B5CF6;
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

[data-theme="dark"] .view-btn {
    color: var(--gray-400);
}

.view-btn.active {
    background: var(--white);
    color: #8B5CF6;
    box-shadow: var(--shadow-sm);
}

[data-theme="dark"] .view-btn.active {
    background: var(--dark-2);
    color: #A78BFA;
}

/* ===== BULK BAR ===== */
.bulk-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: linear-gradient(135deg, #5B21B6, #8B5CF6);
    color: var(--white);
    border-radius: var(--radius);
    margin-bottom: 16px;
    animation: slideDown 0.3s ease;
    box-shadow: var(--shadow-md);
}

.bulk-bar.show {
    display: flex;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
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

/* ===== ALUMNI GRID ===== */
.alumni-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.alumni-grid[data-view="list"] {
    grid-template-columns: 1fr;
}

.alumni-grid[data-view="list"] .alumni-card {
    display: grid;
    grid-template-columns: 160px 1fr auto;
}

.alumni-grid[data-view="list"] .alumni-card-header {
    height: 100%;
    min-height: 200px;
}

.alumni-grid[data-view="list"] .alumni-card-footer {
    display: none;
}

.alumni-card {
    position: relative;
    background: var(--white);
    border-radius: var(--radius-xl);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--gray-100);
    box-shadow: var(--shadow-sm);
}

[data-theme="dark"] .alumni-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.alumni-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(139,92,246,0.3);
}

.alumni-card.is-featured {
    border-color: #F59E0B;
    box-shadow: 0 4px 20px rgba(245,158,11,0.15);
}

.alumni-card.is-inactive {
    opacity: 0.75;
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

.alumni-card:hover .card-checkbox,
.card-checkbox:has(input:checked) {
    opacity: 1;
}

.card-checkbox input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
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
    background: #8B5CF6;
    border-color: #8B5CF6;
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

/* Featured Star */
.featured-star {
    position: absolute;
    top: 14px;
    right: 54px;
    z-index: 12;
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, #F59E0B, #D97706);
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    box-shadow: 0 4px 12px rgba(245,158,11,0.4);
    animation: pulseBadge 2s infinite;
}

/* Card Header */
.alumni-card-header {
    position: relative;
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    cursor: pointer;
}

.alumni-card-header-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
}

.alumni-avatar {
    position: relative;
    z-index: 2;
}

.alumni-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    transition: transform 0.3s;
}

.alumni-card:hover .alumni-avatar img {
    transform: scale(1.08);
}

.avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    font-family: 'Space Grotesk', sans-serif;
    border: 4px solid #fff;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.status-dot {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px rgba(255,255,255,0.3);
}

.status-dot.aktif {
    background: #10B981;
    box-shadow: 0 0 0 3px rgba(16,185,129,0.3);
}

.status-dot.nonaktif {
    background: #94A3B8;
}

.alumni-card-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent 70%);
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    padding: 14px;
    opacity: 0;
    transition: opacity var(--transition);
    z-index: 3;
}

.alumni-card:hover .alumni-card-overlay {
    opacity: 1;
}

.quick-actions {
    display: flex;
    gap: 6px;
}

.quick-actions button,
.quick-actions a {
    width: 34px;
    height: 34px;
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
    font-size: 0.85rem;
}

.quick-actions button:hover,
.quick-actions a:hover {
    background: #8B5CF6;
    color: var(--white);
    transform: translateY(-3px);
}

/* Card Body */
.alumni-card-body {
    padding: 18px;
}

.alumni-card-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.ipk-badge {
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.alumni-year {
    padding: 3px 10px;
    background: var(--gray-100);
    color: var(--gray-700);
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

[data-theme="dark"] .alumni-year {
    background: rgba(255,255,255,0.08);
    color: var(--gray-300);
}

.alumni-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 4px;
    line-height: 1.3;
}

[data-theme="dark"] .alumni-name {
    color: var(--white);
}

.alumni-role {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 12px;
}

[data-theme="dark"] .alumni-role {
    color: var(--gray-400);
}

.alumni-company,
.alumni-location {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: var(--gray-50);
    border-radius: var(--radius-sm);
    margin-bottom: 10px;
    font-size: 0.82rem;
    color: var(--gray-700);
}

[data-theme="dark"] .alumni-company,
[data-theme="dark"] .alumni-location {
    background: rgba(255,255,255,0.04);
    color: var(--gray-300);
}

.alumni-company i,
.alumni-location i {
    color: #8B5CF6;
    font-size: 0.78rem;
    width: 14px;
}

.alumni-quote {
    position: relative;
    padding: 12px;
    background: linear-gradient(135deg, rgba(139,92,246,0.08), rgba(236,72,153,0.05));
    border-radius: var(--radius);
    margin-bottom: 12px;
    border-left: 3px solid #8B5CF6;
}

.alumni-quote i {
    position: absolute;
    top: 6px;
    left: 8px;
    font-size: 0.65rem;
    color: #8B5CF6;
    opacity: 0.5;
}

.alumni-quote p {
    font-size: 0.8rem;
    color: var(--gray-700);
    line-height: 1.5;
    font-style: italic;
    padding-left: 14px;
    margin: 0;
}

[data-theme="dark"] .alumni-quote p {
    color: var(--gray-300);
}

.alumni-socials {
    display: flex;
    gap: 8px;
    margin-bottom: 4px;
}

.social-link {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all var(--transition);
    font-size: 0.82rem;
}

.social-link.linkedin {
    background: rgba(10,102,194,0.1);
    color: #0A66C2;
}

.social-link.linkedin:hover {
    background: #0A66C2;
    color: var(--white);
    transform: translateY(-3px);
}

.social-link.instagram {
    background: linear-gradient(135deg, rgba(225,48,108,0.1), rgba(131,58,180,0.1));
    color: #E1306C;
}

.social-link.instagram:hover {
    background: linear-gradient(135deg, #E1306C, #833AB4);
    color: var(--white);
    transform: translateY(-3px);
}

.social-link.email {
    background: rgba(239,68,68,0.1);
    color: #EF4444;
}

.social-link.email:hover {
    background: #EF4444;
    color: var(--white);
    transform: translateY(-3px);
}

.social-link.phone {
    background: rgba(16,185,129,0.1);
    color: #10B981;
}

.social-link.phone:hover {
    background: #10B981;
    color: var(--white);
    transform: translateY(-3px);
}

/* Card Footer */
.alumni-card-footer {
    padding: 14px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid var(--gray-100);
    gap: 10px;
}

[data-theme="dark"] .alumni-card-footer {
    border-color: rgba(255,255,255,0.08);
}

.edit-btn {
    padding: 6px 14px;
    background: #8B5CF6;
    color: var(--white);
    border: none;
    border-radius: var(--radius-sm);
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all var(--transition-fast);
    font-family: inherit;
}

.edit-btn:hover {
    background: #7C3AED;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139,92,246,0.3);
}

.alumni-meta {
    font-size: 0.72rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 5px;
}

.result-info {
    text-align: center;
    padding: 12px;
    color: var(--gray-600);
    font-size: 0.85rem;
}

[data-theme="dark"] .result-info {
    color: var(--gray-400);
}

.result-info strong {
    color: #8B5CF6;
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

@keyframes floatIllustration {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
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

[data-theme="dark"] .empty-state-ult p {
    color: var(--gray-400);
}

.empty-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

/* ===== SIDEBAR WIDGETS ===== */
.alumni-sidebar {
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
}

[data-theme="dark"] .widget-header h3 {
    color: var(--white);
}

.widget-header h3 i {
    color: #8B5CF6;
}

.widget-badge {
    padding: 3px 10px;
    background: #8B5CF6;
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

/* ===== ALUMNI OF MONTH ===== */
.alumni-of-month {
    overflow: hidden;
    position: relative;
}

.alumni-of-month::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #F59E0B, #EC4899, #8B5CF6);
}

.aom-header {
    padding: 14px 18px;
    background: linear-gradient(135deg, #F59E0B, #D97706);
    color: var(--white);
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.aom-body {
    padding: 20px 18px;
    text-align: center;
    cursor: pointer;
    transition: background var(--transition-fast);
}

.aom-body:hover {
    background: var(--gray-50);
}

[data-theme="dark"] .aom-body:hover {
    background: rgba(255,255,255,0.04);
}

.aom-avatar {
    position: relative;
    width: 90px;
    height: 90px;
    margin: 0 auto 14px;
}

.aom-avatar img,
.aom-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #F59E0B;
    box-shadow: 0 8px 20px rgba(245,158,11,0.3);
}

.aom-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    color: var(--white);
    font-family: 'Space Grotesk', sans-serif;
}

.aom-crown {
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #F59E0B, #D97706);
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    box-shadow: 0 4px 12px rgba(245,158,11,0.5);
    border: 3px solid var(--white);
    animation: crownBounce 2s ease-in-out infinite;
}

@keyframes crownBounce {
    0%, 100% { transform: translateX(-50%) translateY(0) rotate(0); }
    50% { transform: translateX(-50%) translateY(-4px) rotate(-5deg); }
}

.aom-name {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 4px;
}

[data-theme="dark"] .aom-name {
    color: var(--white);
}

.aom-role {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 6px;
}

[data-theme="dark"] .aom-role {
    color: var(--gray-400);
}

.aom-company {
    font-size: 0.78rem;
    color: var(--gray-600);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-bottom: 10px;
}

[data-theme="dark"] .aom-company {
    color: var(--gray-400);
}

.aom-company i {
    color: #8B5CF6;
}

.aom-ipk {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
}

/* ===== IPK DISTRIBUTION ===== */
.ipk-item {
    margin-bottom: 14px;
}

.ipk-item:last-child {
    margin-bottom: 0;
}

.ipk-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.ipk-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
}

.ipk-label small {
    color: var(--gray-500);
    font-weight: 400;
    margin-left: 4px;
    font-size: 0.68rem;
}

[data-theme="dark"] .ipk-label small {
    color: var(--gray-500);
}

.ipk-stats {
    display: flex;
    align-items: baseline;
    gap: 4px;
}

.ipk-stats strong {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .ipk-stats strong {
    color: var(--white);
}

.ipk-pct {
    font-size: 0.7rem;
    color: var(--gray-500);
    font-weight: 600;
}

.ipk-bar {
    height: 6px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .ipk-bar {
    background: rgba(255,255,255,0.08);
}

.ipk-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

@keyframes barFill {
    from { width: 0; }
}

/* ===== COMPANIES ===== */
.company-item {
    display: grid;
    grid-template-columns: 38px 1fr auto;
    gap: 10px;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .company-item {
    border-color: rgba(255,255,255,0.05);
}

.company-item:last-child {
    border-bottom: none;
}

.company-logo {
    width: 38px;
    height: 38px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.75rem;
    font-family: 'Space Grotesk', sans-serif;
}

.company-info {
    min-width: 0;
}

.company-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

[data-theme="dark"] .company-name {
    color: var(--gray-200);
}

.company-bar {
    height: 4px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .company-bar {
    background: rgba(255,255,255,0.08);
}

.company-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

.company-count {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
    min-width: 30px;
    text-align: right;
}

[data-theme="dark"] .company-count {
    color: var(--white);
}

/* ===== CITIES CLOUD ===== */
.cities-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 8px 0;
}

.city-tag {
    display: inline-block;
    padding: 4px 10px;
    background: rgba(139,92,246,0.1);
    color: #8B5CF6;
    border-radius: 20px;
    font-weight: 600;
    transition: all var(--transition-fast);
    cursor: default;
}

.city-tag sup {
    font-size: 0.65em;
    opacity: 0.7;
    margin-left: 2px;
}

.city-tag:hover {
    background: #8B5CF6;
    color: var(--white);
    transform: scale(1.05);
}

/* ===== YEARS ===== */
.year-item {
    margin-bottom: 12px;
}

.year-item:last-child {
    margin-bottom: 0;
}

.year-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.year-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .year-label {
    color: var(--gray-200);
}

.year-count {
    font-size: 0.78rem;
    font-weight: 700;
    color: #8B5CF6;
    padding: 2px 8px;
    background: rgba(139,92,246,0.1);
    border-radius: 10px;
}

.year-bar {
    height: 5px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .year-bar {
    background: rgba(255,255,255,0.08);
}

.year-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #8B5CF6, #EC4899);
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

/* ===== MODAL GRADIENT ===== */
.modal-header-gradient.alumni-modal-head {
    background: linear-gradient(135deg, #0F172A 0%, #8B5CF6 60%, #EC4899 100%) !important;
}

.modal-header-gradient.alumni-modal-head .modal-icon {
    background: rgba(255,255,255,0.2);
}

/* Form sections - reuse admin.css */
.form-sections {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.form-section {
    padding-bottom: 20px;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .form-section {
    border-color: rgba(255,255,255,0.08);
}

.form-section:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

[data-theme="dark"] .section-title {
    color: var(--white);
}

.section-title i {
    color: #8B5CF6;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.required {
    color: var(--danger);
}

.input-icon {
    position: relative;
}

.input-icon > i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-400);
    font-size: 0.9rem;
    z-index: 1;
    pointer-events: none;
}

.input-icon input,
.input-icon select {
    padding-left: 42px;
}

.textarea-wrap {
    position: relative;
}

.textarea-wrap textarea {
    min-height: 100px;
    resize: vertical;
}

.char-counter {
    position: absolute;
    bottom: 10px;
    right: 14px;
    font-size: 0.72rem;
    color: var(--gray-500);
    background: var(--white);
    padding: 2px 8px;
    border-radius: 4px;
}

[data-theme="dark"] .char-counter {
    background: var(--dark);
    color: var(--gray-400);
}

/* Upload Area Avatar */
.upload-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 140px;
}

.upload-avatar .upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.upload-avatar .upload-placeholder i {
    font-size: 1.8rem;
    margin-bottom: 0;
}

.upload-avatar .upload-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--gray-200);
}

/* Checkbox Card */
.checkbox-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    padding: 12px;
    background: var(--gray-50);
    border-radius: var(--radius);
    transition: all var(--transition);
    border: 1px solid transparent;
}

[data-theme="dark"] .checkbox-card {
    background: rgba(255,255,255,0.04);
}

.checkbox-card:hover {
    background: var(--gray-100);
    border-color: var(--gray-200);
}

[data-theme="dark"] .checkbox-card:hover {
    background: rgba(255,255,255,0.08);
}

.checkbox-card input {
    position: absolute;
    opacity: 0;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid var(--gray-300);
    border-radius: 6px;
    flex-shrink: 0;
    position: relative;
    transition: all var(--transition);
    margin-top: 2px;
}

.checkbox-card input:checked + .checkbox-custom {
    background: #8B5CF6;
    border-color: #8B5CF6;
}

.checkbox-custom::after {
    content: '\f00c';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    color: var(--white);
    font-size: 0.7rem;
    transition: transform 0.2s;
}

.checkbox-card input:checked + .checkbox-custom::after {
    transform: translate(-50%, -50%) scale(1);
}

.checkbox-label {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.checkbox-label strong {
    font-size: 0.85rem;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 6px;
}

[data-theme="dark"] .checkbox-label strong {
    color: var(--white);
}

.checkbox-label small {
    font-size: 0.72rem;
    color: var(--gray-600);
}

[data-theme="dark"] .checkbox-label small {
    color: var(--gray-400);
}

/* ===== PREVIEW MODAL ===== */
.preview-hero {
    padding: 30px 20px;
    text-align: center;
    position: relative;
    margin: -28px -28px 20px;
    border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
}

.preview-avatar {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    margin: 0 auto 14px;
    border: 5px solid var(--white);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    object-fit: cover;
}

.preview-avatar-placeholder {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    margin: 0 auto 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: 700;
    color: var(--white);
    font-family: 'Space Grotesk', sans-serif;
    border: 5px solid var(--white);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.preview-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--white);
    margin-bottom: 4px;
}

.preview-role {
    color: rgba(255,255,255,0.9);
    font-size: 0.95rem;
    margin-bottom: 6px;
}

.preview-company {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 14px;
    background: rgba(255,255,255,0.2);
    color: var(--white);
    border-radius: 20px;
    font-size: 0.82rem;
    backdrop-filter: blur(10px);
    margin-bottom: 10px;
}

.preview-badges {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
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
    font-size: 0.95rem;
    color: var(--dark);
    line-height: 1.6;
}

[data-theme="dark"] .preview-value {
    color: var(--gray-200);
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

.preview-quote {
    position: relative;
    padding: 16px;
    background: linear-gradient(135deg, rgba(139,92,246,0.08), rgba(236,72,153,0.05));
    border-radius: var(--radius);
    border-left: 4px solid #8B5CF6;
    margin-bottom: 16px;
    font-style: italic;
    color: var(--gray-700);
    line-height: 1.6;
}

[data-theme="dark"] .preview-quote {
    color: var(--gray-300);
}

.preview-quote i {
    color: #8B5CF6;
    margin-right: 6px;
    opacity: 0.6;
}

.preview-socials {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 16px;
}

.preview-social-link {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all var(--transition);
    font-size: 1rem;
}

/* ===== TABLE VIEW ===== */
.alumni-table-view .data-table {
    font-size: 0.85rem;
}

.alumni-table-view .data-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 700;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .alumni-table-view .data-table th {
    background: var(--dark);
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

.alumni-table-view .data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

[data-theme="dark"] .alumni-table-view .data-table td {
    border-color: rgba(255,255,255,0.05);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .alumni-layout {
        grid-template-columns: 1fr;
    }
    
    .alumni-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .alumni-page {
        padding: 16px;
    }
    
    .alumni-hero {
        padding: 24px 20px;
    }
    
    .hero-title {
        font-size: 1.6rem;
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
    
    .alumni-toolbar {
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
    
    .alumni-grid {
        grid-template-columns: 1fr;
    }
    
    .alumni-grid[data-view="list"] .alumni-card {
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
    .alumni-hero,
    .alumni-toolbar,
    .alumni-sidebar,
    .bulk-bar,
    .card-checkbox,
    .quick-actions,
    .edit-btn,
    .alumni-card-footer {
        display: none !important;
    }
    
    .alumni-layout {
        grid-template-columns: 1fr;
    }
    
    .alumni-card {
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
            el.textContent = Math.floor(target * eased).toLocaleString('id-ID');
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

    /* ===== IPK HINT ===== */
    const ipkInput = document.getElementById('ipk');
    const ipkHint = document.getElementById('ipkHint');
    
    ipkInput?.addEventListener('input', function() {
        const val = parseFloat(this.value);
        if (isNaN(val)) {
            ipkHint.textContent = '';
            return;
        }
        if (val >= 3.51) {
            ipkHint.innerHTML = '🏆 <strong style="color:#F59E0B">Cumlaude</strong>';
        } else if (val >= 3.01) {
            ipkHint.innerHTML = '⭐ <strong style="color:#10B981">Sangat Memuaskan</strong>';
        } else if (val >= 2.76) {
            ipkHint.innerHTML = '✓ <strong style="color:#3B82F6">Memuaskan</strong>';
        } else if (val >= 2.00) {
            ipkHint.innerHTML = '• <strong style="color:#6B7280">Cukup</strong>';
        } else {
            ipkHint.innerHTML = '';
        }
    });

    /* ===== MODAL FUNCTIONS ===== */
    window.openModal = function() {
        document.getElementById('alumniForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('fotoLama').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Alumni Baru';
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('quoteCount').textContent = '0';
        document.getElementById('ipkHint').textContent = '';
        document.getElementById('alumniModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('nama')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('alumniModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.editAlumni = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Alumni';
        document.getElementById('formId').value = data.id;
        document.getElementById('nama').value = data.nama;
        document.getElementById('email').value = data.email || '';
        document.getElementById('telepon').value = data.telepon || '';
        document.getElementById('kota').value = data.kota || '';
        document.getElementById('tahun_lulus').value = data.tahun_lulus;
        document.getElementById('ipk').value = data.ipk || '';
        document.getElementById('pekerjaan').value = data.pekerjaan;
        document.getElementById('perusahaan').value = data.perusahaan;
        document.getElementById('linkedin').value = data.linkedin || '';
        document.getElementById('instagram').value = data.instagram || '';
        document.getElementById('quote').value = data.quote || '';
        document.getElementById('status').value = data.status;
        document.getElementById('is_featured').checked = data.is_featured == 1;
        document.getElementById('fotoLama').value = data.foto || '';
        document.getElementById('quoteCount').textContent = (data.quote || '').length;
        
        // Trigger IPK hint
        if (data.ipk) {
            ipkInput.dispatchEvent(new Event('input'));
        }
        
        const previewImg = document.getElementById('previewImg');
        if (data.foto) {
            previewImg.src = '../uploads/' + data.foto;
            previewImg.style.display = 'block';
        } else {
            previewImg.style.display = 'none';
        }
        
        document.getElementById('alumniModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== PREVIEW MODAL ===== */
    window.previewAlumni = function(data) {
        const initial = (data.nama || 'A').charAt(0).toUpperCase();
        const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#14B8A6'];
        const color = colors[initial.charCodeAt(0) % colors.length];
        
        const getIpkLabel = (ipk) => {
            if (ipk >= 3.51) return {label: 'Cumlaude', color: '#F59E0B', bg: 'rgba(245,158,11,0.12)', icon: 'fa-crown'};
            if (ipk >= 3.01) return {label: 'Sangat Baik', color: '#10B981', bg: 'rgba(16,185,129,0.12)', icon: 'fa-star'};
            if (ipk >= 2.76) return {label: 'Baik', color: '#3B82F6', bg: 'rgba(59,130,246,0.12)', icon: 'fa-check-circle'};
            return {label: 'Cukup', color: '#6B7280', bg: 'rgba(107,114,128,0.12)', icon: 'fa-circle'};
        };
        
        const ipkInfo = data.ipk ? getIpkLabel(data.ipk) : null;
        
        const body = document.getElementById('previewBody');
        body.innerHTML = `
            <div class="preview-hero" style="background: linear-gradient(135deg, ${color}, ${color}dd)">
                ${data.foto 
                    ? `<img src="../uploads/${data.foto}" class="preview-avatar" alt="${data.nama}">`
                    : `<div class="preview-avatar-placeholder" style="background: linear-gradient(135deg, ${color}, ${color}dd)">${initial}</div>`
                }
                <h2 class="preview-name">${data.nama}</h2>
                <p class="preview-role">${data.pekerjaan || 'Belum bekerja'}</p>
                ${data.perusahaan ? `<div class="preview-company"><i class="fas fa-building"></i> ${data.perusahaan}</div>` : ''}
                <div class="preview-badges">
                    <span class="badge" style="background:rgba(255,255,255,0.2);color:#fff;backdrop-filter:blur(10px)">
                        <i class="fas fa-graduation-cap"></i> Angkatan ${data.tahun_lulus}
                    </span>
                    ${ipkInfo ? `<span class="badge" style="background:${ipkInfo.bg};color:${ipkInfo.color}">
                        <i class="fas ${ipkInfo.icon}"></i> IPK ${Number(data.ipk).toFixed(2)} • ${ipkInfo.label}
                    </span>` : ''}
                    <span class="badge badge-${data.status === 'aktif' ? 'success' : 'default'}">
                        <i class="fas fa-circle" style="font-size:0.5rem"></i> ${data.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                    </span>
                    ${data.is_featured == 1 ? '<span class="badge badge-warning"><i class="fas fa-crown"></i> Featured</span>' : ''}
                </div>
            </div>
            
            <div class="preview-info-grid">
                ${data.kota ? `
                <div>
                    <div class="preview-label"><i class="fas fa-map-marker-alt"></i> Kota Domisili</div>
                    <div class="preview-value">${data.kota}</div>
                </div>
                ` : ''}
                ${data.telepon ? `
                <div>
                    <div class="preview-label"><i class="fas fa-phone"></i> Telepon</div>
                    <div class="preview-value"><a href="tel:${data.telepon}" style="color:inherit;text-decoration:none">${data.telepon}</a></div>
                </div>
                ` : ''}
                ${data.email ? `
                <div style="grid-column: 1 / -1">
                    <div class="preview-label"><i class="fas fa-envelope"></i> Email</div>
                    <div class="preview-value"><a href="mailto:${data.email}" style="color:var(--primary);text-decoration:none">${data.email}</a></div>
                </div>
                ` : ''}
            </div>
            
            ${data.quote ? `
            <div class="preview-quote">
                <i class="fas fa-quote-left"></i>
                ${data.quote}
            </div>
            ` : ''}
            
            <div class="preview-socials">
                ${data.linkedin ? `<a href="${data.linkedin}" target="_blank" class="preview-social-link social-link linkedin"><i class="fab fa-linkedin-in"></i></a>` : ''}
                ${data.instagram ? `<a href="https://instagram.com/${data.instagram}" target="_blank" class="preview-social-link social-link instagram"><i class="fab fa-instagram"></i></a>` : ''}
                ${data.email ? `<a href="mailto:${data.email}" class="preview-social-link social-link email"><i class="fas fa-envelope"></i></a>` : ''}
                ${data.telepon ? `<a href="tel:${data.telepon}" class="preview-social-link social-link phone"><i class="fas fa-phone"></i></a>` : ''}
            </div>
        `;
        
        document.getElementById('previewEditBtn').onclick = function() {
            closePreview();
            setTimeout(() => editAlumni(data), 300);
        };
        
        document.getElementById('previewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closePreview = function() {
        document.getElementById('previewModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    /* ===== UPLOAD PREVIEW ===== */
    const uploadArea = document.getElementById('uploadArea');
    const fotoInput = document.getElementById('fotoInput');
    const previewImg = document.getElementById('previewImg');

    fotoInput?.addEventListener('change', e => {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                if (window.IFPro?.toast) window.IFPro.toast.show('Ukuran file maksimal 5MB', 'error');
                fotoInput.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (ev) => {
                previewImg.src = ev.target.result;
                previewImg.style.display = 'block';
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
            fotoInput.files = e.dataTransfer.files;
            fotoInput.dispatchEvent(new Event('change'));
        }
    });

    /* ===== CHARACTER COUNTER ===== */
    const quote = document.getElementById('quote');
    const quoteCount = document.getElementById('quoteCount');
    
    quote?.addEventListener('input', function() {
        quoteCount.textContent = this.value.length;
        if (this.value.length >= 280) {
            quoteCount.style.color = 'var(--warning)';
        } else {
            quoteCount.style.color = '';
        }
    });

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const filterYear = document.getElementById('filterYear');
    const filterIpk = document.getElementById('filterIpk');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    filterStatus?.addEventListener('change', applyFilters);
    filterYear?.addEventListener('change', applyFilters);
    filterIpk?.addEventListener('change', applyFilters);

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const status = filterStatus?.value;
        const year = filterYear?.value;
        const ipk = filterIpk?.value;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        if (year) url.searchParams.set('year', year); else url.searchParams.delete('year');
        if (ipk) url.searchParams.set('ipk', ipk); else url.searchParams.delete('ipk');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        url.searchParams.delete('q');
        url.searchParams.delete('status');
        url.searchParams.delete('year');
        url.searchParams.delete('ipk');
        url.searchParams.delete('sort');
        url.searchParams.delete('dir');
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
            const grid = document.getElementById('alumniGrid');
            const table = document.getElementById('alumniTable');
            
            if (view === 'table') {
                grid.style.display = 'none';
                table.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                table.style.display = 'none';
                grid.dataset.view = view;
            }
            
            localStorage.setItem('alumni_view', view);
        });
    });

    // Restore saved view
    const savedView = localStorage.getItem('alumni_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');

    document.querySelectorAll('.alumni-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
        });
    });

    // Select all in table view
    document.getElementById('selectAllTable')?.addEventListener('change', function() {
        document.querySelectorAll('#alumniTable .alumni-check').forEach(c => {
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
        document.querySelectorAll('.alumni-check').forEach(c => c.checked = false);
        const selectAll = document.getElementById('selectAllTable');
        if (selectAll) selectAll.checked = false;
        updateBulkBar();
    };

    window.bulkAction = function(action) {
        if (selectedIds.size === 0) return;
        
        const messages = {
            'bulk-delete': `Yakin hapus ${selectedIds.size} alumni terpilih?`,
            'bulk-activate': `Aktifkan ${selectedIds.size} alumni terpilih?`,
            'bulk-deactivate': `Nonaktifkan ${selectedIds.size} alumni terpilih?`
        };
        
        if (!confirm(messages[action])) return;
        
        const ids = Array.from(selectedIds).join(',');
        window.location.href = `?action=${action}&ids=${ids}`;
    };

    /* ===== EXPORT CSV (Real Implementation) ===== */
    window.exportToCSV = function() {
        const data = <?= json_encode(array_map(function($a) {
            return [
                'id' => $a['id'],
                'nama' => $a['nama'],
                'email' => $a['email'] ?? '',
                'telepon' => $a['telepon'] ?? '',
                'tahun_lulus' => $a['tahun_lulus'],
                'ipk' => $a['ipk'] ?? '',
                'pekerjaan' => $a['pekerjaan'],
                'perusahaan' => $a['perusahaan'],
                'kota' => $a['kota'] ?? '',
                'linkedin' => $a['linkedin'] ?? '',
                'instagram' => $a['instagram'] ?? '',
                'status' => $a['status'],
                'featured' => ($a['is_featured'] ?? 0) ? 'Ya' : 'Tidak'
            ];
        }, $alumniList)) ?>;
        
        if (data.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['ID', 'Nama', 'Email', 'Telepon', 'Tahun Lulus', 'IPK', 'Pekerjaan', 'Perusahaan', 'Kota', 'LinkedIn', 'Instagram', 'Status', 'Featured'];
        const rows = data.map(d => [
            d.id,
            `"${d.nama.replace(/"/g, '""')}"`,
            `"${d.email.replace(/"/g, '""')}"`,
            `"${d.telepon.replace(/"/g, '""')}"`,
            d.tahun_lulus,
            d.ipk || '',
            `"${d.pekerjaan.replace(/"/g, '""')}"`,
            `"${d.perusahaan.replace(/"/g, '""')}"`,
            `"${d.kota.replace(/"/g, '""')}"`,
            `"${d.linkedin.replace(/"/g, '""')}"`,
            `"${d.instagram.replace(/"/g, '""')}"`,
            d.status,
            d.featured
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `alumni-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${data.length} alumni berhasil di-export`, 'success');
        }, 500);
    };

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        // N = New alumni
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
    document.getElementById('alumniModal')?.addEventListener('click', function(e) {
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
        setTimeout(() => editAlumni(<?= json_encode($editData, JSON_HEX_APOS) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c🎓 Alumni Ultimate v4.0 Loaded', 'color:#8B5CF6;font-weight:bold;font-size:14px');
    console.log('%c   Features: IPK Distribution, Companies, Cities Map, Alumni of Month', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: N (New), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>