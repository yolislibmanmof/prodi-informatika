<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Dosen';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
}

$currentAdminId = $_SESSION['admin_id'] ?? 0;

/* ===== HANDLE ACTIONS ===== */
if (isset($_GET['action']) && $db) {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($_GET['action'] === 'delete' && $id) {
        try {
            $stmt = $db->prepare("SELECT foto FROM dosen WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['foto'] && file_exists(__DIR__ . '/../uploads/' . $row['foto'])) {
                @unlink(__DIR__ . '/../uploads/' . $row['foto']);
            }
            $db->prepare("DELETE FROM dosen WHERE id = ?")->execute([$id]);
            setFlash('success', 'Dosen berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus dosen');
        }
        header('Location: dosen.php');
        exit;
    }
    
    if ($_GET['action'] === 'toggle' && $id) {
        try {
            $db->prepare("UPDATE dosen SET status = IF(status='aktif','nonaktif','aktif'), updated_at = NOW() WHERE id = ?")->execute([$id]);
            setFlash('success', 'Status dosen berhasil diubah');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mengubah status');
        }
        header('Location: dosen.php');
        exit;
    }
    
    // Bulk actions
    if (in_array($_GET['action'], ['bulk-activate', 'bulk-deactivate', 'bulk-delete']) && !empty($_GET['ids'])) {
        try {
            $ids = array_map('intval', explode(',', $_GET['ids']));
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            if ($_GET['action'] === 'bulk-delete') {
                $stmt = $db->prepare("SELECT foto FROM dosen WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                foreach ($stmt->fetchAll() as $row) {
                    if ($row['foto'] && file_exists(__DIR__ . '/../uploads/' . $row['foto'])) {
                        @unlink(__DIR__ . '/../uploads/' . $row['foto']);
                    }
                }
                $db->prepare("DELETE FROM dosen WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' dosen berhasil dihapus');
            } elseif ($_GET['action'] === 'bulk-activate') {
                $db->prepare("UPDATE dosen SET status = 'aktif', updated_at = NOW() WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' dosen berhasil diaktifkan');
            } elseif ($_GET['action'] === 'bulk-deactivate') {
                $db->prepare("UPDATE dosen SET status = 'nonaktif', updated_at = NOW() WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' dosen dinonaktifkan');
            }
        } catch (Throwable $e) {
            setFlash('error', 'Gagal melakukan bulk action');
        }
        header('Location: dosen.php');
        exit;
    }
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $nidn = clean($_POST['nidn']);
        $nama = clean($_POST['nama']);
        $gelar_depan = clean($_POST['gelar_depan'] ?? '');
        $gelar_belakang = clean($_POST['gelar_belakang'] ?? '');
        $gelar = trim($gelar_depan . ' ' . $nama . ', ' . $gelar_belakang);
        $keahlian = clean($_POST['keahlian']);
        $email = clean($_POST['email'] ?? '');
        $telepon = clean($_POST['telepon'] ?? '');
        $pendidikan = clean($_POST['pendidikan'] ?? '');
        $jabatan_fungsional = clean($_POST['jabatan_fungsional'] ?? '');
        $sinta_id = clean($_POST['sinta_id'] ?? '');
        $google_scholar = clean($_POST['google_scholar'] ?? '');
        $profil = clean($_POST['profil'] ?? '');
        $status = clean($_POST['status'] ?? 'aktif');
        
        // Upload foto
        $foto = $_POST['foto_lama'] ?? null;
        if (!empty($_FILES['foto']['name'])) {
            $newImage = uploadImage($_FILES['foto'], 'uploads/dosen');
            if ($newImage) {
                if ($foto && file_exists(__DIR__ . '/../' . $foto)) {
                    @unlink(__DIR__ . '/../' . $foto);
                }
                $foto = $newImage;
            }
        }
        
        if ($id) {
            $stmt = $db->prepare("UPDATE dosen SET nidn=?, nama=?, gelar=?, keahlian=?, email=?, telepon=?, pendidikan=?, jabatan_fungsional=?, sinta_id=?, google_scholar=?, foto=?, profil=?, status=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$nidn, $nama, $gelar, $keahlian, $email, $telepon, $pendidikan, $jabatan_fungsional, $sinta_id, $google_scholar, $foto, $profil, $status, $id]);
            setFlash('success', 'Data dosen berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO dosen (nidn, nama, gelar, keahlian, email, telepon, pendidikan, jabatan_fungsional, sinta_id, google_scholar, foto, profil, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$nidn, $nama, $gelar, $keahlian, $email, $telepon, $pendidikan, $jabatan_fungsional, $sinta_id, $google_scholar, $foto, $profil, $status]);
            setFlash('success', 'Dosen baru berhasil ditambahkan');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan data: ' . $e->getMessage());
    }
    header('Location: dosen.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$search = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
$filterStatus = $_GET['status'] ?? '';
$filterEducation = $_GET['education'] ?? '';
$filterJabatan = $_GET['jabatan'] ?? '';
$sortBy = $_GET['sort'] ?? 'nama';
$sortDir = $_GET['dir'] ?? 'ASC';

$where = "1=1";
$params = [];

if ($filterStatus) { $where .= " AND status = ?"; $params[] = $filterStatus; }
if ($filterEducation) { $where .= " AND pendidikan = ?"; $params[] = $filterEducation; }
if ($filterJabatan) { $where .= " AND jabatan_fungsional = ?"; $params[] = $filterJabatan; }
if (isset($_GET['q']) && $_GET['q']) { 
    $where .= " AND (nama LIKE ? OR nidn LIKE ? OR keahlian LIKE ? OR email LIKE ?)"; 
    $params[] = $search; $params[] = $search; $params[] = $search; $params[] = $search;
}

$validSorts = ['nama', 'nidn', 'created_at', 'jabatan_fungsional'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'nama';
$sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

/* ===== FETCH DATA ===== */
$dosenList = [];
$stats = [
    'total' => 0,
    'aktif' => 0,
    'nonaktif' => 0,
    's3' => 0,
    's2' => 0,
    'guru_besar' => 0,
    'lektor_kepala' => 0,
];

if ($db) {
    try {
        $sql = "SELECT * FROM dosen WHERE $where ORDER BY $sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $dosenList = $stmt->fetchAll();
        
        // Stats
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM dosen")->fetchColumn();
        $stats['aktif'] = (int)$db->query("SELECT COUNT(*) FROM dosen WHERE status='aktif'")->fetchColumn();
        $stats['nonaktif'] = (int)$db->query("SELECT COUNT(*) FROM dosen WHERE status='nonaktif'")->fetchColumn();
        $stats['s3'] = (int)$db->query("SELECT COUNT(*) FROM dosen WHERE pendidikan='S3' OR pendidikan LIKE '%Doktor%'")->fetchColumn();
        $stats['s2'] = (int)$db->query("SELECT COUNT(*) FROM dosen WHERE pendidikan='S2' OR pendidikan LIKE '%Magister%'")->fetchColumn();
        $stats['guru_besar'] = (int)$db->query("SELECT COUNT(*) FROM dosen WHERE jabatan_fungsional LIKE '%Guru Besar%' OR jabatan_fungsional LIKE '%Profesor%'")->fetchColumn();
        $stats['lektor_kepala'] = (int)$db->query("SELECT COUNT(*) FROM dosen WHERE jabatan_fungsional LIKE '%Lektor Kepala%'")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== EXPERTISE DISTRIBUTION ===== */
$expertiseStats = [];
if ($db) {
    try {
        $expertiseStats = $db->query("
            SELECT keahlian, COUNT(*) as total 
            FROM dosen 
            WHERE keahlian IS NOT NULL AND keahlian != '' AND status='aktif'
            GROUP BY keahlian 
            ORDER BY total DESC 
            LIMIT 8
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== EDUCATION LEVELS ===== */
$educationStats = [];
if ($db) {
    try {
        $educationStats = $db->query("
            SELECT pendidikan, COUNT(*) as total 
            FROM dosen 
            WHERE pendidikan IS NOT NULL AND pendidikan != ''
            GROUP BY pendidikan 
            ORDER BY total DESC
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== FUNCTIONAL POSITIONS ===== */
$jabatanStats = [];
if ($db) {
    try {
        $jabatanStats = $db->query("
            SELECT jabatan_fungsional, COUNT(*) as total 
            FROM dosen 
            WHERE jabatan_fungsional IS NOT NULL AND jabatan_fungsional != ''
            GROUP BY jabatan_fungsional 
            ORDER BY 
                CASE 
                    WHEN jabatan_fungsional LIKE '%Guru Besar%' THEN 1
                    WHEN jabatan_fungsional LIKE '%Lektor Kepala%' THEN 2
                    WHEN jabatan_fungsional LIKE '%Lektor%' THEN 3
                    WHEN jabatan_fungsional LIKE '%Asisten Ahli%' THEN 4
                    ELSE 5
                END,
                total DESC
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== RECENTLY ADDED ===== */
$recentDosen = [];
if ($db) {
    try {
        $recentDosen = $db->query("
            SELECT id, nama, foto, keahlian, created_at 
            FROM dosen 
            ORDER BY created_at DESC 
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== UNIQUE VALUES FOR FILTERS ===== */
$uniqueExpertise = [];
$uniqueJabatan = [];
if ($db) {
    try {
        $uniqueExpertise = $db->query("SELECT DISTINCT keahlian FROM dosen WHERE keahlian IS NOT NULL AND keahlian != '' ORDER BY keahlian")->fetchAll(PDO::FETCH_COLUMN);
        $uniqueJabatan = $db->query("SELECT DISTINCT jabatan_fungsional FROM dosen WHERE jabatan_fungsional IS NOT NULL AND jabatan_fungsional != '' ORDER BY jabatan_fungsional")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {}
}

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM dosen WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

/* ===== HELPER FUNCTIONS ===== */
function getInitials($name) {
    $parts = explode(' ', trim($name ?? 'A'));
    $initial = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initial .= strtoupper(substr(end($parts), 0, 1));
    }
    return $initial;
}

function getEducationBadge($education) {
    $ed = strtoupper($education ?? '');
    if (strpos($ed, 'S3') !== false || strpos($ed, 'DOKTOR') !== false || strpos($ed, 'DR.') !== false) {
        return ['label' => 'S3 / Doktor', 'color' => '#8B5CF6', 'icon' => 'fa-award'];
    }
    if (strpos($ed, 'S2') !== false || strpos($ed, 'MAGISTER') !== false) {
        return ['label' => 'S2 / Magister', 'color' => '#3B82F6', 'icon' => 'fa-graduation-cap'];
    }
    if (strpos($ed, 'S1') !== false || strpos($ed, 'SARJANA') !== false) {
        return ['label' => 'S1 / Sarjana', 'color' => '#10B981', 'icon' => 'fa-user-graduate'];
    }
    return ['label' => $education ?: '-', 'color' => '#6B7280', 'icon' => 'fa-graduation-cap'];
}

function getJabatanColor($jabatan) {
    $jb = strtoupper($jabatan ?? '');
    if (strpos($jb, 'GURU BESAR') !== false || strpos($jb, 'PROFESOR') !== false) return '#DC2626';
    if (strpos($jb, 'LEKTOR KEPALA') !== false) return '#F59E0B';
    if (strpos($jb, 'LEKTOR') !== false) return '#3B82F6';
    if (strpos($jb, 'ASISTEN AHLI') !== false) return '#10B981';
    return '#6B7280';
}

function getExpertiseColor($expertise) {
    $colors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#14B8A6', '#EF4444'];
    return $colors[crc32($expertise) % count($colors)];
}

function parseExpertise($expertise) {
    if (!$expertise) return [];
    $parts = preg_split('/[,;|\/]/', $expertise);
    return array_filter(array_map('trim', $parts));
}

include 'includes/header.php';
?>

<div class="page-content dosen-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="dosen-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-academic-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Academic Faculty</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-user-graduate"></i>
                    </span>
                    Kelola Dosen & Pengajar
                </h1>
                <p class="hero-subtitle">
                    Database lengkap tenaga pengajar Program Studi Informatika dengan kualifikasi akademik
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-users"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total Dosen</span>
                    </div>
                    <div class="hms-item live">
                        <span class="live-dot"></span>
                        <span><strong><?= $stats['aktif'] ?></strong> Aktif</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-award" style="color:#F59E0B"></i>
                        <span><strong><?= $stats['guru_besar'] ?></strong> Guru Besar</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-graduation-cap" style="color:#8B5CF6"></i>
                        <span><strong><?= $stats['s3'] ?></strong> Doktor (S3)</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportDosen()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-ghost" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-user-plus"></i>
                    <span>Tambah Dosen Baru</span>
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
    <div class="dosen-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="dosen-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid dosen-stats">
                <div class="stat-card stat-indigo">
                    <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Dosen</p>
                        <span class="stat-trend up"><i class="fas fa-users"></i> Fakultas</span>
                    </div>
                </div>

                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['aktif'] ?>">0</h3>
                        <p>Aktif Mengajar</p>
                        <span class="stat-trend up live-pulse"><i class="fas fa-circle"></i> Active</span>
                    </div>
                </div>

                <div class="stat-card stat-purple">
                    <div class="stat-icon"><i class="fas fa-award"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['s3'] ?>">0</h3>
                        <p>Bergelar Doktor</p>
                        <span class="stat-trend up"><i class="fas fa-graduation-cap"></i> S3</span>
                    </div>
                </div>

                <div class="stat-card stat-amber">
                    <div class="stat-icon"><i class="fas fa-crown"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['guru_besar'] ?>">0</h3>
                        <p>Guru Besar</p>
                        <span class="stat-trend up"><i class="fas fa-star"></i> Professor</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card dosen-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama, NIDN, keahlian, email..." value="<?= clean($_GET['q'] ?? '') ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterStatus" class="toolbar-select">
                            <option value="">📊 Semua Status</option>
                            <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>🟢 Aktif</option>
                            <option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : '' ?>>⚪ Nonaktif</option>
                        </select>
                        
                        <select id="filterEducation" class="toolbar-select">
                            <option value="">🎓 Semua Pendidikan</option>
                            <option value="S3" <?= $filterEducation === 'S3' ? 'selected' : '' ?>>🏆 Doktor (S3)</option>
                            <option value="S2" <?= $filterEducation === 'S2' ? 'selected' : '' ?>>🎓 Magister (S2)</option>
                            <option value="S1" <?= $filterEducation === 'S1' ? 'selected' : '' ?>>📘 Sarjana (S1)</option>
                        </select>
                        
                        <select id="filterJabatan" class="toolbar-select">
                            <option value="">👔 Semua Jabatan</option>
                            <?php foreach ($uniqueJabatan as $jb): ?>
                            <option value="<?= clean($jb) ?>" <?= $filterJabatan === $jb ? 'selected' : '' ?>><?= clean($jb) ?></option>
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
                            <a href="?sort=nama&dir=ASC" class="sort-item <?= $sortBy === 'nama' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Nama A-Z
                            </a>
                            <a href="?sort=nama&dir=DESC" class="sort-item <?= $sortBy === 'nama' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-up"></i> Nama Z-A
                            </a>
                            <a href="?sort=nidn&dir=ASC" class="sort-item <?= $sortBy === 'nidn' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-id-card"></i> NIDN
                            </a>
                            <a href="?sort=jabatan_fungsional&dir=ASC" class="sort-item <?= $sortBy === 'jabatan_fungsional' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-user-tie"></i> Jabatan
                            </a>
                            <a href="?sort=created_at&dir=DESC" class="sort-item <?= $sortBy === 'created_at' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-history"></i> Terbaru Ditambah
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
                    <span><strong id="selectedCount">0</strong> dosen dipilih</span>
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

            <!-- Dosen Grid -->
            <div class="dosen-grid" id="dosenGrid" data-view="grid">
                <?php if (!empty($dosenList)): ?>
                    <?php foreach ($dosenList as $d): 
                        $thumb = $d['foto'] ? '../uploads/' . $d['foto'] : '';
                        $initials = getInitials($d['nama']);
                        $avatarColors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#14B8A6', '#EF4444'];
                        $avatarColor = $avatarColors[ord($initials[0]) % count($avatarColors)];
                        $expertiseList = parseExpertise($d['keahlian']);
                        $eduBadge = getEducationBadge($d['pendidikan'] ?? '');
                        $jabColor = getJabatanColor($d['jabatan_fungsional'] ?? '');
                        $isActive = $d['status'] === 'aktif';
                    ?>
                    <div class="dosen-card <?= !$isActive ? 'is-inactive' : '' ?>" data-id="<?= $d['id'] ?>">
                        <!-- Checkbox -->
                        <label class="card-checkbox">
                            <input type="checkbox" class="dosen-check" value="<?= $d['id'] ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <!-- Academic Rank Badge -->
                        <?php if ($d['jabatan_fungsional']): ?>
                        <div class="rank-badge" style="background: <?= $jabColor ?>; box-shadow: 0 4px 12px <?= $jabColor ?>40">
                            <?php if (stripos($d['jabatan_fungsional'], 'Guru Besar') !== false || stripos($d['jabatan_fungsional'], 'Profesor') !== false): ?>
                                <i class="fas fa-crown"></i>
                            <?php elseif (stripos($d['jabatan_fungsional'], 'Lektor Kepala') !== false): ?>
                                <i class="fas fa-medal"></i>
                            <?php elseif (stripos($d['jabatan_fungsional'], 'Lektor') !== false): ?>
                                <i class="fas fa-award"></i>
                            <?php else: ?>
                                <i class="fas fa-user-tie"></i>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Status Dot -->
                        <span class="status-indicator <?= $isActive ? 'active' : 'inactive' ?>"></span>
                        
                        <!-- Card Image -->
                        <div class="dc-image" onclick="previewDosen(<?= htmlspecialchars(json_encode($d, JSON_HEX_APOS)) ?>)">
                            <div class="dc-image-bg" style="background: linear-gradient(135deg, <?= $avatarColor ?>22, <?= $avatarColor ?>11)"></div>
                            <?php if ($thumb): ?>
                                <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= clean($d['nama']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="dc-avatar-placeholder" style="background: linear-gradient(135deg, <?= $avatarColor ?>, <?= $avatarColor ?>dd)">
                                    <?= $initials ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="dc-image-overlay">
                                <div class="dc-quick-actions" onclick="event.stopPropagation()">
                                    <button onclick="editDosen(<?= htmlspecialchars(json_encode($d, JSON_HEX_APOS)) ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="previewDosen(<?= htmlspecialchars(json_encode($d, JSON_HEX_APOS)) ?>)" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="?action=toggle&id=<?= $d['id'] ?>" title="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                        <i class="fas fa-<?= $isActive ? 'eye-slash' : 'eye' ?>"></i>
                                    </a>
                                    <a href="?action=delete&id=<?= $d['id'] ?>" title="Hapus" data-confirm="Yakin hapus data dosen ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="dc-body">
                            <div class="dc-top-badges">
                                <?php if (!empty($d['pendidikan'])): ?>
                                    <span class="edu-badge" style="background: <?= $eduBadge['color'] ?>15; color: <?= $eduBadge['color'] ?>">
                                        <i class="fas <?= $eduBadge['icon'] ?>"></i>
                                        <?= clean($eduBadge['label']) ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($d['jabatan_fungsional'])): ?>
                                    <span class="jabatan-badge" style="background: <?= $jabColor ?>15; color: <?= $jabColor ?>">
                                        <i class="fas fa-user-tie"></i>
                                        <?= clean($d['jabatan_fungsional']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="dc-name"><?= clean(($d['gelar'] ?? '') ?: ($d['nama'] ?? 'Dosen')) ?></h3>
                            
                            <div class="dc-nidn">
                                <i class="fas fa-id-card"></i>
                                <span><?= clean($d['nidn'] ?? '-') ?></span>
                            </div>
                            
                            <?php if (!empty($expertiseList)): ?>
                            <div class="dc-expertise">
                                <?php foreach (array_slice($expertiseList, 0, 3) as $exp): 
                                    $expColor = getExpertiseColor($exp);
                                ?>
                                <span class="expertise-tag" style="background: <?= $expColor ?>15; color: <?= $expColor ?>; border-left: 2px solid <?= $expColor ?>">
                                    <?= clean($exp) ?>
                                </span>
                                <?php endforeach; ?>
                                <?php if (count($expertiseList) > 3): ?>
                                <span class="expertise-tag more">+<?= count($expertiseList) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <div class="dc-footer">
                                <div class="dc-contact">
                                    <?php if (!empty($d['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($d['email']) ?>" class="dc-contact-item" title="<?= clean($d['email']) ?>">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($d['telepon'])): ?>
                                    <a href="tel:<?= htmlspecialchars($d['telepon']) ?>" class="dc-contact-item" title="<?= clean($d['telepon']) ?>">
                                        <i class="fas fa-phone"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($d['sinta_id'])): ?>
                                    <a href="https://sinta.kemdikbud.go.id/authors/profile/<?= urlencode($d['sinta_id']) ?>" target="_blank" class="dc-contact-item sinta" title="SINTA Profile">
                                        <span style="font-weight:700;font-size:0.68rem">SINTA</span>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($d['google_scholar'])): ?>
                                    <a href="<?= htmlspecialchars($d['google_scholar']) ?>" target="_blank" class="dc-contact-item scholar" title="Google Scholar">
                                        <i class="fab fa-google"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <button class="dc-edit-btn" onclick="editDosen(<?= htmlspecialchars(json_encode($d, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE)) ?>)">
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
                                <circle cx="100" cy="55" r="28" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <path d="M 75 95 Q 100 80 125 95 L 125 120 Q 100 130 75 120 Z" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <rect x="80" y="30" width="40" height="8" rx="2" fill="var(--primary)" opacity="0.3"/>
                                <circle cx="100" cy="50" r="12" fill="var(--gray-200)"/>
                                <circle cx="155" cy="40" r="18" fill="#3B82F6" opacity="0.9"/>
                                <line x1="148" y1="40" x2="162" y2="40" stroke="white" stroke-width="3" stroke-linecap="round"/>
                                <line x1="155" y1="33" x2="155" y2="47" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Belum ada data dosen</h3>
                        <p>Tambahkan dosen pertama untuk melengkapi data tenaga pengajar prodi</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-user-plus"></i> Tambah Dosen Pertama
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Table View -->
            <?php if (!empty($dosenList)): ?>
            <div class="dosen-table-view" id="dosenTable" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="selectAllTable"></th>
                                    <th>Foto</th>
                                    <th>Nama & Gelar</th>
                                    <th>NIDN</th>
                                    <th>Keahlian</th>
                                    <th>Pendidikan</th>
                                    <th>Jabatan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dosenList as $d): 
                                    $thumb = $d['foto'] ? '../uploads/' . $d['foto'] : '';
                                    $initials = getInitials($d['nama']);
                                    $avatarColors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'];
                                    $avatarColor = $avatarColors[ord($initials[0]) % count($avatarColors)];
                                    $eduBadge = getEducationBadge($d['pendidikan'] ?? '');
                                    $isActive = $d['status'] === 'aktif';
                                ?>
                                <tr class="<?= !$isActive ? 'inactive-row' : '' ?>">
                                    <td><input type="checkbox" class="dosen-check" value="<?= $d['id'] ?>"></td>
                                    <td>
                                        <?php if ($thumb): ?>
                                            <img src="<?= htmlspecialchars($thumb) ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover">
                                        <?php else: ?>
                                            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,<?= $avatarColor ?>,<?= $avatarColor ?>dd);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.78rem"><?= $initials ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= clean($d['gelar'] ?: $d['nama']) ?></strong>
                                        <?php if ($d['email']): ?>
                                        <div style="font-size:0.72rem;color:var(--gray-500);margin-top:2px">
                                            <i class="fas fa-envelope" style="font-size:0.65rem"></i> <?= clean($d['email']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><code style="font-size:0.78rem;padding:3px 8px;background:var(--gray-100);border-radius:4px"><?= clean($d['nidn']) ?></code></td>
                                    <td>
                                        <div style="display:flex;flex-wrap:wrap;gap:3px;max-width:200px">
                                            <?php foreach (array_slice(parseExpertise($d['keahlian']), 0, 2) as $exp): ?>
                                            <span style="font-size:0.68rem;padding:2px 8px;background:<?= getExpertiseColor($exp) ?>15;color:<?= getExpertiseColor($exp) ?>;border-radius:10px;font-weight:600"><?= clean($exp) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($d['pendidikan']): ?>
                                        <span class="badge" style="background:<?= $eduBadge['color'] ?>15;color:<?= $eduBadge['color'] ?>">
                                            <i class="fas <?= $eduBadge['icon'] ?>"></i> <?= $eduBadge['label'] ?>
                                        </span>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($d['jabatan_fungsional']): 
                                            $jc = getJabatanColor($d['jabatan_fungsional']);
                                        ?>
                                        <span style="font-size:0.75rem;font-weight:600;color:<?= $jc ?>">
                                            <?= clean($d['jabatan_fungsional']) ?>
                                        </span>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $isActive ? 'success' : 'default' ?>">
                                            <i class="fas fa-circle" style="font-size:0.45rem"></i>
                                            <?= ucfirst($d['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-sm" onclick="previewDosen(<?= htmlspecialchars(json_encode($d, JSON_HEX_APOS)) ?>)" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-sm" onclick="editDosen(<?= htmlspecialchars(json_encode($d, JSON_HEX_APOS)) ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=toggle&id=<?= $d['id'] ?>" class="btn-icon btn-sm" title="Toggle">
                                                <i class="fas fa-<?= $isActive ? 'eye-slash' : 'eye' ?>"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $d['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus dosen ini?" title="Hapus">
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
                <span>Menampilkan <strong><?= count($dosenList) ?></strong> dosen</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="dosen-sidebar">
            <!-- Expertise Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-laptop-code"></i> Bidang Keahlian</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($expertiseStats)): 
                        $maxExp = max(array_column($expertiseStats, 'total'));
                    ?>
                        <?php foreach ($expertiseStats as $exp): 
                            $pct = round(($exp['total'] / $maxExp) * 100);
                            $color = getExpertiseColor($exp['keahlian']);
                        ?>
                        <div class="expertise-stat-item">
                            <div class="esi-head">
                                <span class="esi-name">
                                    <span class="esi-dot" style="background: <?= $color ?>"></span>
                                    <?= clean($exp['keahlian']) ?>
                                </span>
                                <span class="esi-count"><?= $exp['total'] ?></span>
                            </div>
                            <div class="esi-bar">
                                <div class="esi-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-laptop-code"></i>
                            <p>Belum ada data keahlian</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Academic Ranks -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-user-tie"></i> Jabatan Fungsional</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($jabatanStats)): 
                        $maxJab = max(array_column($jabatanStats, 'total'));
                    ?>
                        <?php foreach ($jabatanStats as $jb): 
                            $pct = round(($jb['total'] / $maxJab) * 100);
                            $color = getJabatanColor($jb['jabatan_fungsional']);
                        ?>
                        <div class="jabatan-stat-item">
                            <div class="jsi-head">
                                <span class="jsi-name" style="color: <?= $color ?>">
                                    <?php if (stripos($jb['jabatan_fungsional'], 'Guru Besar') !== false): ?>
                                        <i class="fas fa-crown"></i>
                                    <?php elseif (stripos($jb['jabatan_fungsional'], 'Lektor Kepala') !== false): ?>
                                        <i class="fas fa-medal"></i>
                                    <?php else: ?>
                                        <i class="fas fa-user-tie"></i>
                                    <?php endif; ?>
                                    <?= clean($jb['jabatan_fungsional']) ?>
                                </span>
                                <span class="jsi-count"><?= $jb['total'] ?></span>
                            </div>
                            <div class="jsi-bar">
                                <div class="jsi-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-user-tie"></i>
                            <p>Belum ada data jabatan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Education Levels -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-graduation-cap"></i> Tingkat Pendidikan</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($educationStats)): 
                        $maxEdu = max(array_column($educationStats, 'total'));
                        $eduColors = ['#8B5CF6', '#3B82F6', '#10B981', '#F59E0B'];
                    ?>
                        <div class="education-chart">
                            <?php 
                            $total = array_sum(array_column($educationStats, 'total'));
                            $offset = 0;
                            ?>
                            <svg viewBox="0 0 36 36" class="edu-donut">
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--gray-100)" stroke-width="3.8"/>
                                <?php foreach ($educationStats as $i => $ed): 
                                    $pct = ($ed['total'] / $total) * 100;
                                    $dashArray = ($pct * 2.83) . ', ' . (100 - ($pct * 2.83));
                                    $color = $eduColors[$i % count($eduColors)];
                                ?>
                                <circle cx="18" cy="18" r="15.9" fill="none" 
                                    stroke="<?= $color ?>" 
                                    stroke-width="3.8"
                                    stroke-dasharray="<?= $dashArray ?>"
                                    stroke-dashoffset="-<?= $offset ?>"
                                    transform="rotate(-90 18 18)"/>
                                <?php 
                                $offset += ($pct * 2.83);
                                endforeach; ?>
                            </svg>
                            <div class="edu-donut-center">
                                <div class="edu-donut-value"><?= $total ?></div>
                                <div class="edu-donut-label">Total</div>
                            </div>
                        </div>
                        
                        <div class="education-legend">
                            <?php foreach ($educationStats as $i => $ed): 
                                $pct = round(($ed['total'] / $total) * 100);
                                $color = $eduColors[$i % count($eduColors)];
                            ?>
                            <div class="el-item">
                                <span class="el-dot" style="background: <?= $color ?>"></span>
                                <span class="el-name"><?= clean($ed['pendidikan']) ?></span>
                                <span class="el-count"><?= $ed['total'] ?> (<?= $pct ?>%)</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-graduation-cap"></i>
                            <p>Belum ada data pendidikan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recently Added -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-clock"></i> Baru Ditambahkan</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($recentDosen)): ?>
                        <?php foreach ($recentDosen as $rd): 
                            $rdInitials = getInitials($rd['nama']);
                            $rdColor = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'][ord($rdInitials[0]) % 5];
                            $rdThumb = $rd['foto'] ? '../uploads/' . $rd['foto'] : '';
                        ?>
                        <div class="recent-item" onclick="previewDosen(<?= htmlspecialchars(json_encode($rd, JSON_HEX_APOS)) ?>)">
                            <?php if ($rdThumb): ?>
                                <img src="<?= htmlspecialchars($rdThumb) ?>" class="ri-avatar" alt="">
                            <?php else: ?>
                                <div class="ri-avatar-placeholder" style="background: <?= $rdColor ?>"><?= $rdInitials ?></div>
                            <?php endif; ?>
                            <div class="ri-info">
                                <div class="ri-name"><?= clean($rd['nama']) ?></div>
                                <div class="ri-meta">
                                    <i class="fas fa-laptop-code"></i>
                                    <?= clean(mb_substr($rd['keahlian'] ?? '', 0, 30)) ?><?= strlen($rd['keahlian'] ?? '') > 30 ? '...' : '' ?>
                                </div>
                            </div>
                            <div class="ri-time">
                                <?= function_exists('formatTanggal') ? formatTanggal($rd['created_at']) : date('d M', strtotime($rd['created_at'])) ?>
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
                        <i class="fas fa-user-plus"></i>
                        <span>Tambah Dosen Baru</span>
                    </button>
                    <a href="mata-kuliah.php" class="qa-btn">
                        <i class="fas fa-book"></i>
                        <span>Kelola Mata Kuliah</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="jadwal.php" class="qa-btn">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Jadwal Mengajar</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="penelitian.php" class="qa-btn">
                        <i class="fas fa-flask"></i>
                        <span>Penelitian Dosen</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Faculty Stats Summary -->
            <div class="card widget-card summary-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-pie"></i> Ringkasan Fakultas</h3>
                </div>
                <div class="widget-body">
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="si-icon" style="background: rgba(139,92,246,0.15); color: #8B5CF6">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="si-info">
                                <div class="si-value"><?= $stats['s3'] ?></div>
                                <div class="si-label">Doktor (S3)</div>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="si-icon" style="background: rgba(59,130,246,0.15); color: #3B82F6">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="si-info">
                                <div class="si-value"><?= $stats['s2'] ?></div>
                                <div class="si-label">Magister (S2)</div>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="si-icon" style="background: rgba(220,38,38,0.15); color: #DC2626">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="si-info">
                                <div class="si-value"><?= $stats['guru_besar'] ?></div>
                                <div class="si-label">Guru Besar</div>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="si-icon" style="background: rgba(245,158,11,0.15); color: #F59E0B">
                                <i class="fas fa-medal"></i>
                            </div>
                            <div class="si-info">
                                <div class="si-value"><?= $stats['lektor_kepala'] ?></div>
                                <div class="si-label">Lektor Kepala</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== DOSEN MODAL ===== -->
<div class="modal" id="dosenModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient dosen-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <h3 id="modalTitle">Tambah Dosen Baru</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px" id="modalSubtitle">Lengkapi data akademik dosen</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="dosenForm" class="modal-body">
            <input type="hidden" name="id" id="formId">
            <input type="hidden" name="foto_lama" id="fotoLama">
            
            <div class="form-sections">
                <!-- Section: Identitas -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-id-card"></i> Identitas Dosen</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>NIDN <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="nidn" id="nidn" required placeholder="Nomor Induk Dosen Nasional">
                            </div>
                            <small class="form-hint">Format: 10 digit angka</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Status <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-toggle-on"></i>
                                <select name="status" id="status" required>
                                    <option value="aktif">🟢 Aktif</option>
                                    <option value="nonaktif">⚪ Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Data Pribadi -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-user"></i> Data Pribadi</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Gelar Depan</label>
                            <div class="input-icon">
                                <i class="fas fa-graduation-cap"></i>
                                <input type="text" name="gelar_depan" id="gelarDepan" placeholder="Contoh: Prof. Dr.">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Nama Lengkap <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" name="nama" id="nama" required placeholder="Nama lengkap tanpa gelar">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Gelar Belakang</label>
                            <div class="input-icon">
                                <i class="fas fa-graduation-cap"></i>
                                <input type="text" name="gelar_belakang" id="gelarBelakang" placeholder="Contoh: S.Kom., M.Kom.">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <div class="input-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" id="email" placeholder="email@dosen.ac.id">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <div class="input-icon">
                                <i class="fas fa-phone"></i>
                                <input type="tel" name="telepon" id="telepon" placeholder="08xxxxxxxxxx">
                            </div>
                        </div>
                    </div>
                    
                    <div class="name-preview">
                        <span class="np-label">Preview Nama Lengkap:</span>
                        <span class="np-value" id="namePreview">-</span>
                    </div>
                </div>

                <!-- Section: Akademik -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-graduation-cap"></i> Data Akademik</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Pendidikan Terakhir</label>
                            <div class="input-icon">
                                <i class="fas fa-graduation-cap"></i>
                                <select name="pendidikan" id="pendidikan">
                                    <option value="">- Pilih -</option>
                                    <option value="S3">S3 / Doktor</option>
                                    <option value="S2">S2 / Magister</option>
                                    <option value="S1">S1 / Sarjana</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Jabatan Fungsional</label>
                            <div class="input-icon">
                                <i class="fas fa-user-tie"></i>
                                <select name="jabatan_fungsional" id="jabatanFungsional">
                                    <option value="">- Pilih -</option>
                                    <option value="Guru Besar">Guru Besar / Profesor</option>
                                    <option value="Lektor Kepala">Lektor Kepala</option>
                                    <option value="Lektor">Lektor</option>
                                    <option value="Asisten Ahli">Asisten Ahli</option>
                                    <option value="Tenaga Pengajar">Tenaga Pengajar</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label>Keahlian / Bidang Studi <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-laptop-code"></i>
                                <input type="text" name="keahlian" id="keahlian" required placeholder="Contoh: AI, Machine Learning, Web Development">
                            </div>
                            <small class="form-hint">Pisahkan dengan koma untuk multiple expertise</small>
                        </div>
                    </div>
                </div>

                <!-- Section: Academic Profiles -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-link"></i> Profil Akademik Online</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>SINTA ID</label>
                            <div class="input-icon">
                                <i class="fas fa-fingerprint"></i>
                                <input type="text" name="sinta_id" id="sintaId" placeholder="Contoh: 6012345">
                            </div>
                            <small class="form-hint">ID dari sinta.kemdikbud.go.id</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Google Scholar URL</label>
                            <div class="input-icon">
                                <i class="fab fa-google"></i>
                                <input type="url" name="google_scholar" id="googleScholar" placeholder="https://scholar.google.com/citations?user=...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Profile & Photo -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-file-alt"></i> Profil & Foto</h4>
                    <div class="form-grid-2">
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label>Profil Singkat</label>
                            <div class="textarea-wrap">
                                <textarea name="profil" id="profil" rows="4" placeholder="Deskripsi singkat profil akademik, research interest, dll..." maxlength="500"></textarea>
                                <span class="char-counter"><span id="profilCount">0</span>/500</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Foto Dosen</label>
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
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Dosen
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== PREVIEW MODAL ===== -->
<div class="modal" id="previewModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient dosen-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <h3>Profil Dosen</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Detail lengkap profil dosen</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePreview()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="previewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePreview()">Tutup</button>
            <button class="btn btn-primary" id="previewEditBtn"><i class="fas fa-edit"></i> Edit Dosen</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   DOSEN PAGE - ACADEMIC ENTERPRISE EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.dosen-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.dosen-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #1E1B4B 0%, #3730A3 50%, #4F46E5 100%);
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
    background: radial-gradient(circle, #818CF8, transparent);
    top: -120px; right: 8%;
}

.orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, #A78BFA, transparent);
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
    color: rgba(255,255,255,0.03);
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
.dosen-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.dosen-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.dosen-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-indigo .stat-icon { background: linear-gradient(135deg, #4F46E5, #3730A3); }
.stat-green .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-purple .stat-icon { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }
.stat-amber .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }

.live-pulse {
    animation: livePulse 1.5s infinite;
}

@keyframes livePulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* ===== TOOLBAR ===== */
.dosen-toolbar {
    padding: 16px 20px;
    margin-bottom: 20px;
}

.dosen-toolbar {
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
    min-width: 120px;
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

/* ===== DOSEN GRID ===== */
.dosen-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.dosen-grid[data-view="list"] {
    grid-template-columns: 1fr;
}

.dosen-grid[data-view="list"] .dosen-card {
    display: grid;
    grid-template-columns: 220px 1fr;
}

.dosen-grid[data-view="list"] .dc-image {
    height: 100%;
    min-height: 220px;
}

.dosen-card {
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

[data-theme="dark"] .dosen-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.dosen-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(79,70,229,0.3);
}

.dosen-card.is-inactive {
    opacity: 0.7;
}

.dosen-card.is-inactive:hover {
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

.dosen-card:hover .card-checkbox,
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

/* Rank Badge */
.rank-badge {
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

/* Card Image */
.dc-image {
    position: relative;
    height: 200px;
    background: var(--gray-100);
    overflow: hidden;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dc-image-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
}

.dc-image img {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid var(--white);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    transition: transform 0.5s;
    position: relative;
    z-index: 1;
}

.dosen-card:hover .dc-image img {
    transform: scale(1.05);
}

.dc-avatar-placeholder {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: 700;
    color: var(--white);
    font-family: 'Space Grotesk', sans-serif;
    border: 5px solid var(--white);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    position: relative;
    z-index: 1;
}

.dc-image-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity var(--transition);
    z-index: 2;
}

.dosen-card:hover .dc-image-overlay {
    opacity: 1;
}

.dc-quick-actions {
    display: flex;
    gap: 8px;
}

.dc-quick-actions button,
.dc-quick-actions a {
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

.dc-quick-actions button:hover,
.dc-quick-actions a:hover {
    background: #4F46E5;
    color: var(--white);
    transform: translateY(-3px);
}

/* Card Body */
.dc-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    flex: 1;
    text-align: center;
}

.dc-top-badges {
    display: flex;
    gap: 6px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.edu-badge {
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.jabatan-badge {
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.dc-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 8px;
    line-height: 1.3;
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .dc-name {
    color: var(--white);
}

.dc-nidn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: var(--gray-100);
    color: var(--gray-700);
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    font-family: 'Courier New', monospace;
    margin: 0 auto 12px;
}

[data-theme="dark"] .dc-nidn {
    background: rgba(255,255,255,0.08);
    color: var(--gray-300);
}

.dc-nidn i {
    color: #4F46E5;
    font-size: 0.68rem;
}

.dc-expertise {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    justify-content: center;
    margin-bottom: 14px;
}

.expertise-tag {
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
}

.expertise-tag.more {
    background: var(--gray-100);
    color: var(--gray-600);
    border-left: none;
}

[data-theme="dark"] .expertise-tag.more {
    background: rgba(255,255,255,0.08);
    color: var(--gray-400);
}

.dc-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 14px;
    border-top: 1px solid var(--gray-100);
    margin-top: auto;
    gap: 10px;
}

[data-theme="dark"] .dc-footer {
    border-color: rgba(255,255,255,0.08);
}

.dc-contact {
    display: flex;
    gap: 5px;
    flex: 1;
    justify-content: center;
}

.dc-contact-item {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--gray-100);
    color: var(--gray-600);
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all var(--transition-fast);
    font-size: 0.82rem;
}

[data-theme="dark"] .dc-contact-item {
    background: rgba(255,255,255,0.08);
    color: var(--gray-400);
}

.dc-contact-item:hover {
    background: #4F46E5;
    color: var(--white);
    transform: translateY(-2px);
}

.dc-contact-item.sinta {
    background: #F59E0B;
    color: var(--white);
}

.dc-contact-item.scholar {
    background: #4285F4;
    color: var(--white);
}

.dc-edit-btn {
    width: 34px;
    height: 34px;
    border-radius: var(--radius-sm);
    background: #4F46E5;
    color: var(--white);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
    flex-shrink: 0;
}

.dc-edit-btn:hover {
    background: #3730A3;
    transform: translateY(-2px) rotate(5deg);
    box-shadow: 0 4px 12px rgba(79,70,229,0.3);
}

/* List View Adjustments */
.dosen-grid[data-view="list"] .dc-body {
    text-align: left;
    padding: 20px;
}

.dosen-grid[data-view="list"] .dc-top-badges {
    justify-content: flex-start;
}

.dosen-grid[data-view="list"] .dc-nidn {
    margin: 0 0 12px 0;
}

.dosen-grid[data-view="list"] .dc-expertise {
    justify-content: flex-start;
}

.dosen-grid[data-view="list"] .dc-footer {
    justify-content: flex-end;
}

/* Table View */
.dosen-table-view .data-table {
    font-size: 0.85rem;
}

.dosen-table-view .data-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 700;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .dosen-table-view .data-table th {
    background: var(--dark);
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

.dosen-table-view .data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

.dosen-table-view tr.inactive-row {
    opacity: 0.6;
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
.dosen-sidebar {
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

/* ===== EXPERTISE STATS ===== */
.expertise-stat-item {
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .expertise-stat-item {
    border-color: rgba(255,255,255,0.05);
}

.expertise-stat-item:last-child {
    border-bottom: none;
}

.esi-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.esi-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

[data-theme="dark"] .esi-name {
    color: var(--gray-200);
}

.esi-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.esi-count {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
    flex-shrink: 0;
    margin-left: 8px;
}

[data-theme="dark"] .esi-count {
    color: var(--white);
}

.esi-bar {
    height: 5px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .esi-bar {
    background: rgba(255,255,255,0.08);
}

.esi-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

/* ===== JABATAN STATS ===== */
.jabatan-stat-item {
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .jabatan-stat-item {
    border-color: rgba(255,255,255,0.05);
}

.jabatan-stat-item:last-child {
    border-bottom: none;
}

.jsi-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.jsi-name {
    font-size: 0.82rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.jsi-name i {
    font-size: 0.78rem;
}

.jsi-count {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .jsi-count {
    color: var(--white);
}

.jsi-bar {
    height: 5px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .jsi-bar {
    background: rgba(255,255,255,0.08);
}

.jsi-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

/* ===== EDUCATION DONUT ===== */
.education-chart {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 0 auto 16px;
}

.edu-donut {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.edu-donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.edu-donut-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
    line-height: 1;
}

[data-theme="dark"] .edu-donut-value {
    color: var(--white);
}

.edu-donut-label {
    font-size: 0.72rem;
    color: var(--gray-500);
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.education-legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.el-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
}

.el-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.el-name {
    flex: 1;
    color: var(--dark);
    font-weight: 500;
}

[data-theme="dark"] .el-name {
    color: var(--gray-200);
}

.el-count {
    color: var(--gray-600);
    font-weight: 600;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.78rem;
}

[data-theme="dark"] .el-count {
    color: var(--gray-400);
}

/* ===== RECENT ITEMS ===== */
.recent-item {
    display: grid;
    grid-template-columns: 40px 1fr auto;
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

.ri-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.ri-avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-weight: 700;
    font-size: 0.78rem;
    font-family: 'Space Grotesk', sans-serif;
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

.ri-meta {
    font-size: 0.72rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ri-meta i {
    color: #4F46E5;
    font-size: 0.65rem;
}

.ri-time {
    font-size: 0.68rem;
    color: var(--gray-500);
    white-space: nowrap;
}

/* ===== SUMMARY CARD ===== */
.summary-card {
    background: linear-gradient(135deg, rgba(79,70,229,0.05), rgba(139,92,246,0.05));
    border: 1px solid rgba(79,70,229,0.15);
}

.summary-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: var(--white);
    border-radius: var(--radius);
    transition: all var(--transition-fast);
}

[data-theme="dark"] .summary-item {
    background: rgba(255,255,255,0.05);
}

.summary-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.si-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.si-info {
    flex: 1;
    min-width: 0;
}

.si-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
    line-height: 1;
}

[data-theme="dark"] .si-value {
    color: var(--white);
}

.si-label {
    font-size: 0.68rem;
    color: var(--gray-600);
    margin-top: 2px;
}

[data-theme="dark"] .si-label {
    color: var(--gray-400);
}

/* ===== MODAL GRADIENT ===== */
.dosen-modal-head {
    background: linear-gradient(135deg, #1E1B4B 0%, #3730A3 60%, #4F46E5 100%) !important;
}

/* Name Preview */
.name-preview {
    margin-top: 16px;
    padding: 12px 16px;
    background: linear-gradient(135deg, rgba(79,70,229,0.08), rgba(139,92,246,0.05));
    border-left: 4px solid #4F46E5;
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
    color: #4F46E5;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== PREVIEW MODAL ===== */
.preview-profile-hero {
    padding: 30px 20px;
    text-align: center;
    position: relative;
    margin: -28px -28px 20px;
    border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
    background: linear-gradient(135deg, #4F46E5, #3730A3);
    color: var(--white);
}

.preview-avatar-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    margin: 0 auto 14px;
    border: 5px solid var(--white);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    object-fit: cover;
}

.preview-avatar-placeholder-large {
    width: 120px;
    height: 120px;
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
    margin-bottom: 4px;
    font-family: 'Space Grotesk', sans-serif;
}

.preview-jabatan {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 14px;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
    margin-bottom: 8px;
}

.preview-nidn {
    font-size: 0.85rem;
    opacity: 0.9;
    font-family: 'Courier New', monospace;
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

.preview-expertise-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.preview-expertise-tag {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
}

.preview-profile-links {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.preview-link-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: var(--gray-50);
    border-radius: var(--radius);
    text-decoration: none;
    color: var(--dark);
    font-size: 0.85rem;
    font-weight: 600;
    transition: all var(--transition-fast);
}

[data-theme="dark"] .preview-link-btn {
    background: rgba(255,255,255,0.05);
    color: var(--gray-200);
}

.preview-link-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.preview-link-btn.sinta {
    background: linear-gradient(135deg, #F59E0B, #D97706);
    color: var(--white);
}

.preview-link-btn.scholar {
    background: linear-gradient(135deg, #4285F4, #1967D2);
    color: var(--white);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .dosen-layout {
        grid-template-columns: 1fr;
    }
    
    .dosen-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .dosen-page {
        padding: 16px;
    }
    
    .dosen-hero {
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
    
    .dosen-toolbar {
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
    
    .dosen-grid {
        grid-template-columns: 1fr;
    }
    
    .dosen-grid[data-view="list"] .dosen-card {
        grid-template-columns: 1fr;
    }
    
    .form-grid-2 {
        grid-template-columns: 1fr;
    }
    
    .preview-info-grid {
        grid-template-columns: 1fr;
    }
    
    .preview-profile-links {
        grid-template-columns: 1fr;
    }
}

@media print {
    .dosen-hero,
    .dosen-sidebar,
    .dosen-toolbar,
    .bulk-bar,
    .dc-quick-actions,
    .dc-edit-btn,
    .card-checkbox {
        display: none !important;
    }
    
    .dosen-layout {
        grid-template-columns: 1fr;
    }
    
    .dosen-card {
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
        document.getElementById('dosenForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('fotoLama').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Dosen Baru';
        document.getElementById('modalSubtitle').textContent = 'Lengkapi data akademik dosen';
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('namePreview').textContent = '-';
        document.getElementById('profilCount').textContent = '0';
        
        document.getElementById('dosenModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('nidn')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('dosenModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.editDosen = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Dosen';
        document.getElementById('modalSubtitle').textContent = 'Perbarui data akademik dosen';
        document.getElementById('formId').value = data.id;
        document.getElementById('nidn').value = data.nidn;
        document.getElementById('nama').value = data.nama;
        
        // Parse gelar
        const gelarParts = (data.gelar || '').split(data.nama);
        if (gelarParts.length >= 2) {
            document.getElementById('gelarDepan').value = gelarParts[0].trim();
            document.getElementById('gelarBelakang').value = gelarParts[1].replace(/^,\s*/, '').trim();
        }
        
        document.getElementById('keahlian').value = data.keahlian;
        document.getElementById('email').value = data.email || '';
        document.getElementById('telepon').value = data.telepon || '';
        document.getElementById('pendidikan').value = data.pendidikan || '';
        document.getElementById('jabatanFungsional').value = data.jabatan_fungsional || '';
        document.getElementById('sintaId').value = data.sinta_id || '';
        document.getElementById('googleScholar').value = data.google_scholar || '';
        document.getElementById('profil').value = data.profil || '';
        document.getElementById('status').value = data.status;
        document.getElementById('fotoLama').value = data.foto || '';
        document.getElementById('profilCount').textContent = (data.profil || '').length;
        
        // Update preview
        updateNamePreview();
        
        const previewImg = document.getElementById('previewImg');
        if (data.foto) {
            previewImg.src = '../uploads/' + data.foto;
            previewImg.style.display = 'block';
        } else {
            previewImg.style.display = 'none';
        }
        
        document.getElementById('dosenModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== PREVIEW MODAL ===== */
    window.previewDosen = function(data) {
        const initials = getInitials(data.nama || 'A');
        const colors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4'];
        const color = colors[initials.charCodeAt(0) % colors.length];
        const thumb = data.foto ? '../uploads/' + data.foto : '';
        const expertiseList = (data.keahlian || '').split(/[,;|\/]/).map(t => t.trim()).filter(t => t);
        const eduBadge = getEducationBadge(data.pendidikan || '');
        const jabColor = getJabatanColor(data.jabatan_fungsional || '');
        
        function getEducationBadge(ed) {
            const edUpper = (ed || '').toUpperCase();
            if (edUpper.includes('S3') || edUpper.includes('DOKTOR')) return {label: 'S3 / Doktor', color: '#8B5CF6', icon: 'fa-award'};
            if (edUpper.includes('S2') || edUpper.includes('MAGISTER')) return {label: 'S2 / Magister', color: '#3B82F6', icon: 'fa-graduation-cap'};
            if (edUpper.includes('S1') || edUpper.includes('SARJANA')) return {label: 'S1 / Sarjana', color: '#10B981', icon: 'fa-user-graduate'};
            return {label: ed || '-', color: '#6B7280', icon: 'fa-graduation-cap'};
        }
        
        function getJabatanColor(jb) {
            const upper = (jb || '').toUpperCase();
            if (upper.includes('GURU BESAR') || upper.includes('PROFESOR')) return '#DC2626';
            if (upper.includes('LEKTOR KEPALA')) return '#F59E0B';
            if (upper.includes('LEKTOR')) return '#3B82F6';
            if (upper.includes('ASISTEN AHLI')) return '#10B981';
            return '#6B7280';
        }
        
        function getInitials(name) {
            const parts = (name || 'A').trim().split(' ');
            let initial = parts[0].charAt(0).toUpperCase();
            if (parts.length > 1) initial += parts[parts.length - 1].charAt(0).toUpperCase();
            return initial;
        }
        
        function getExpertiseColor(exp) {
            const colors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4'];
            return colors[Math.abs(exp.split('').reduce((a, c) => a + c.charCodeAt(0), 0)) % colors.length];
        }
        
        const body = document.getElementById('previewBody');
        body.innerHTML = `
            <div class="preview-profile-hero">
                ${thumb 
                    ? `<img src="${thumb}" class="preview-avatar-large" alt="${data.nama}">`
                    : `<div class="preview-avatar-placeholder-large" style="background: linear-gradient(135deg, ${color}, ${color}dd)">${initials}</div>`
                }
                <h2 class="preview-name">${data.gelar || data.nama}</h2>
                ${data.jabatan_fungsional ? `<div class="preview-jabatan"><i class="fas fa-user-tie"></i> ${data.jabatan_fungsional}</div>` : ''}
                <div class="preview-nidn"><i class="fas fa-id-card"></i> NIDN: ${data.nidn}</div>
            </div>
            
            <div class="preview-info-grid">
                <div>
                    <div class="preview-label"><i class="fas fa-graduation-cap"></i> Pendidikan</div>
                    <div class="preview-value">
                        ${data.pendidikan 
                            ? `<span style="padding:3px 10px;background:${eduBadge.color}15;color:${eduBadge.color};border-radius:20px;font-size:0.78rem;font-weight:600"><i class="fas ${eduBadge.icon}"></i> ${eduBadge.label}</span>`
                            : '-'
                        }
                    </div>
                </div>
                <div>
                    <div class="preview-label"><i class="fas fa-envelope"></i> Email</div>
                    <div class="preview-value">${data.email ? `<a href="mailto:${data.email}" style="color:#4F46E5;text-decoration:none">${data.email}</a>` : '-'}</div>
                </div>
                ${data.telepon ? `
                <div>
                    <div class="preview-label"><i class="fas fa-phone"></i> Telepon</div>
                    <div class="preview-value"><a href="tel:${data.telepon}" style="color:#4F46E5;text-decoration:none">${data.telepon}</a></div>
                </div>
                ` : ''}
                <div>
                    <div class="preview-label"><i class="fas fa-toggle-on"></i> Status</div>
                    <div class="preview-value">
                        <span class="badge badge-${data.status === 'aktif' ? 'success' : 'default'}">
                            <i class="fas fa-circle" style="font-size:0.45rem"></i> ${data.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                        </span>
                    </div>
                </div>
            </div>
            
            ${expertiseList.length > 0 ? `
            <div class="preview-section">
                <div class="preview-label"><i class="fas fa-laptop-code"></i> Bidang Keahlian</div>
                <div class="preview-expertise-list">
                    ${expertiseList.map(exp => {
                        const c = getExpertiseColor(exp);
                        return `<span class="preview-expertise-tag" style="background:${c}15;color:${c};border-left:3px solid ${c}">${exp}</span>`;
                    }).join('')}
                </div>
            </div>
            ` : ''}
            
            ${data.profil ? `
            <div class="preview-section">
                <div class="preview-label"><i class="fas fa-file-alt"></i> Profil</div>
                <div class="preview-value">${data.profil}</div>
            </div>
            ` : ''}
            
            ${data.sinta_id || data.google_scholar ? `
            <div class="preview-section">
                <div class="preview-label"><i class="fas fa-link"></i> Profil Akademik Online</div>
                <div class="preview-profile-links">
                    ${data.sinta_id ? `
                    <a href="https://sinta.kemdikbud.go.id/authors/profile/${encodeURIComponent(data.sinta_id)}" target="_blank" class="preview-link-btn sinta">
                        <i class="fas fa-fingerprint"></i>
                        <div>
                            <div style="font-weight:700">SINTA Profile</div>
                            <div style="font-size:0.72rem;opacity:0.9">ID: ${data.sinta_id}</div>
                        </div>
                    </a>
                    ` : ''}
                    ${data.google_scholar ? `
                    <a href="${data.google_scholar}" target="_blank" class="preview-link-btn scholar">
                        <i class="fab fa-google"></i>
                        <div>
                            <div style="font-weight:700">Google Scholar</div>
                            <div style="font-size:0.72rem;opacity:0.9">Publikasi</div>
                        </div>
                    </a>
                    ` : ''}
                </div>
            </div>
            ` : ''}
        `;
        
        document.getElementById('previewEditBtn').onclick = function() {
            closePreview();
            setTimeout(() => editDosen(data), 300);
        };
        
        document.getElementById('previewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closePreview = function() {
        document.getElementById('previewModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    /* ===== NAME PREVIEW ===== */
    function updateNamePreview() {
        const depan = document.getElementById('gelarDepan')?.value.trim() || '';
        const nama = document.getElementById('nama')?.value.trim() || '';
        const belakang = document.getElementById('gelarBelakang')?.value.trim() || '';
        
        let full = '';
        if (depan) full += depan + ' ';
        full += nama;
        if (belakang) full += ', ' + belakang;
        
        document.getElementById('namePreview').textContent = full || '-';
    }

    ['gelarDepan', 'nama', 'gelarBelakang'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', updateNamePreview);
    });

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

    /* ===== PROFIL COUNTER ===== */
    const profilInput = document.getElementById('profil');
    const profilCount = document.getElementById('profilCount');
    
    profilInput?.addEventListener('input', function() {
        profilCount.textContent = this.value.length;
        if (this.value.length >= 480) {
            profilCount.style.color = 'var(--warning)';
        } else {
            profilCount.style.color = '';
        }
    });

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const filterEducation = document.getElementById('filterEducation');
    const filterJabatan = document.getElementById('filterJabatan');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    [filterStatus, filterEducation, filterJabatan].forEach(el => {
        el?.addEventListener('change', applyFilters);
    });

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const status = filterStatus?.value;
        const education = filterEducation?.value;
        const jabatan = filterJabatan?.value;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        if (education) url.searchParams.set('education', education); else url.searchParams.delete('education');
        if (jabatan) url.searchParams.set('jabatan', jabatan); else url.searchParams.delete('jabatan');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'status', 'education', 'jabatan', 'sort', 'dir'].forEach(p => url.searchParams.delete(p));
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
            const grid = document.getElementById('dosenGrid');
            const table = document.getElementById('dosenTable');
            
            if (view === 'table') {
                grid.style.display = 'none';
                table.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                table.style.display = 'none';
                grid.dataset.view = view;
            }
            
            localStorage.setItem('dosen_view', view);
        });
    });

    // Restore saved view
    const savedView = localStorage.getItem('dosen_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');

    document.querySelectorAll('.dosen-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
        });
    });

    document.getElementById('selectAllTable')?.addEventListener('change', function() {
        document.querySelectorAll('#dosenTable .dosen-check').forEach(c => {
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
        document.querySelectorAll('.dosen-check').forEach(c => c.checked = false);
        const selectAll = document.getElementById('selectAllTable');
        if (selectAll) selectAll.checked = false;
        updateBulkBar();
    };

    window.bulkAction = function(action) {
        if (selectedIds.size === 0) return;
        
        const messages = {
            'bulk-delete': `Yakin hapus ${selectedIds.size} dosen terpilih? Aksi ini tidak dapat dibatalkan!`,
            'bulk-activate': `Aktifkan ${selectedIds.size} dosen terpilih?`,
            'bulk-deactivate': `Nonaktifkan ${selectedIds.size} dosen terpilih?`
        };
        
        if (!confirm(messages[action])) return;
        
        const ids = Array.from(selectedIds).join(',');
        window.location.href = `?action=${action}&ids=${ids}`;
    };

    /* ===== EXPORT CSV ===== */
    window.exportDosen = function() {
        const dosenList = <?= json_encode($dosenList) ?>;
        
        if (!dosenList || dosenList.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['ID', 'NIDN', 'Nama Lengkap', 'Gelar', 'Email', 'Telepon', 'Pendidikan', 'Jabatan Fungsional', 'Keahlian', 'SINTA ID', 'Google Scholar', 'Status', 'Created At'];
        const rows = dosenList.map(d => [
            d.id,
            d.nidn,
            `"${(d.nama || '').replace(/"/g, '""')}"`,
            `"${(d.gelar || '').replace(/"/g, '""')}"`,
            `"${(d.email || '').replace(/"/g, '""')}"`,
            `"${(d.telepon || '').replace(/"/g, '""')}"`,
            d.pendidikan || '',
            d.jabatan_fungsional || '',
            `"${(d.keahlian || '').replace(/"/g, '""')}"`,
            d.sinta_id || '',
            d.google_scholar || '',
            d.status,
            d.created_at
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `dosen-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${dosenList.length} dosen berhasil di-export`, 'success');
        }, 500);
    };

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        // N = New dosen
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
    document.getElementById('dosenModal')?.addEventListener('click', function(e) {
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
        setTimeout(() => editDosen(<?= json_encode($editData, JSON_HEX_APOS) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c🎓 Dosen v4.0 Loaded', 'color:#4F46E5;font-weight:bold;font-size:14px');
    console.log('%c   Features: Expertise Distribution, Academic Ranks, SINTA Integration, Bulk Actions', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: N (New), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>