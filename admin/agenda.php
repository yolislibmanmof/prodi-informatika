<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Agenda';
include 'includes/header.php';
$db = getDB();

/* ===== STATISTICS (Anti-Error) ===== */
$totalAgenda = 0; $upcomingCount = 0; $ongoingCount = 0; $completedCount = 0; $todayCount = 0;
try {
    $totalAgenda = (int)$db->query("SELECT COUNT(*) FROM agenda")->fetchColumn();
    $upcomingCount = (int)$db->query("SELECT COUNT(*) FROM agenda WHERE status='akan_datang' AND tanggal_mulai >= NOW()")->fetchColumn();
    $ongoingCount = (int)$db->query("SELECT COUNT(*) FROM agenda WHERE status='berlangsung'")->fetchColumn();
    $completedCount = (int)$db->query("SELECT COUNT(*) FROM agenda WHERE status='selesai'")->fetchColumn();
    $todayCount = (int)$db->query("SELECT COUNT(*) FROM agenda WHERE DATE(tanggal_mulai) = CURDATE()")->fetchColumn();
} catch (Throwable $e) {}

/* ===== HANDLE ACTIONS ===== */
if (isset($_GET['action'])) {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($_GET['action'] === 'delete' && $id) {
        try {
            $stmt = $db->prepare("SELECT gambar FROM agenda WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['gambar'] && file_exists(__DIR__ . '/../uploads/' . $row['gambar'])) {
                unlink(__DIR__ . '/../uploads/' . $row['gambar']);
            }
            $db->prepare("DELETE FROM agenda WHERE id = ?")->execute([$id]);
            setFlash('success', 'Agenda berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus agenda');
        }
        header('Location: agenda.php');
        exit;
    }
    
    if ($_GET['action'] === 'duplicate' && $id) {
        try {
            $stmt = $db->prepare("SELECT * FROM agenda WHERE id = ?");
            $stmt->execute([$id]);
            $agenda = $stmt->fetch();
            if ($agenda) {
                unset($agenda['id']);
                $agenda['judul'] = $agenda['judul'] . ' (Copy)';
                $agenda['status'] = 'akan_datang';
                $agenda['created_at'] = date('Y-m-d H:i:s');
                $columns = array_keys($agenda);
                $placeholders = str_repeat('?,', count($columns) - 1) . '?';
                $db->prepare("INSERT INTO agenda (" . implode(',', $columns) . ") VALUES ($placeholders)")->execute(array_values($agenda));
                setFlash('success', 'Agenda berhasil diduplikasi');
            }
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menduplikasi agenda');
        }
        header('Location: agenda.php');
        exit;
    }
    
    // Bulk delete
    if ($_GET['action'] === 'bulk-delete' && !empty($_GET['ids'])) {
        try {
            $ids = array_map('intval', explode(',', $_GET['ids']));
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            $stmt = $db->prepare("SELECT gambar FROM agenda WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $row) {
                if ($row['gambar'] && file_exists(__DIR__ . '/../uploads/' . $row['gambar'])) {
                    unlink(__DIR__ . '/../uploads/' . $row['gambar']);
                }
            }
            
            $db->prepare("DELETE FROM agenda WHERE id IN ($placeholders)")->execute($ids);
            setFlash('success', count($ids) . ' agenda berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus agenda');
        }
        header('Location: agenda.php');
        exit;
    }
    
    // Quick status change
    if ($_GET['action'] === 'status' && $id && isset($_GET['status'])) {
        try {
            $validStatuses = ['akan_datang', 'berlangsung', 'selesai', 'batal'];
            $newStatus = $_GET['status'];
            if (in_array($newStatus, $validStatuses)) {
                $db->prepare("UPDATE agenda SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
                setFlash('success', 'Status agenda diperbarui');
            }
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mengubah status');
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'agenda.php'));
        exit;
    }
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $judul = clean($_POST['judul']);
        $deskripsi = clean($_POST['deskripsi']);
        $lokasi = clean($_POST['lokasi']);
        $kategori = clean($_POST['kategori']);
        $tanggal_mulai = clean($_POST['tanggal_mulai']);
        $tanggal_selesai = clean($_POST['tanggal_selesai']) ?: null;
        $status = clean($_POST['status']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        $gambar = $_POST['gambar_lama'] ?? null;
        if (!empty($_FILES['gambar']['name'])) {
            $newImage = uploadImage($_FILES['gambar'], 'uploads/agenda');
            if ($newImage) {
                if ($gambar && file_exists(__DIR__ . '/../' . $gambar)) unlink(__DIR__ . '/../' . $gambar);
                $gambar = $newImage;
            }
        }
        
        if ($id) {
            $stmt = $db->prepare("UPDATE agenda SET judul=?, deskripsi=?, lokasi=?, kategori=?, tanggal_mulai=?, tanggal_selesai=?, status=?, gambar=?, is_featured=? WHERE id=?");
            $stmt->execute([$judul, $deskripsi, $lokasi, $kategori, $tanggal_mulai, $tanggal_selesai, $status, $gambar, $is_featured, $id]);
            setFlash('success', 'Agenda berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO agenda (judul, deskripsi, lokasi, kategori, tanggal_mulai, tanggal_selesai, status, gambar, is_featured, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$judul, $deskripsi, $lokasi, $kategori, $tanggal_mulai, $tanggal_selesai, $status, $gambar, $is_featured]);
            setFlash('success', 'Agenda baru berhasil ditambahkan');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan agenda: ' . $e->getMessage());
    }
    header('Location: agenda.php');
    exit;
}

/* ===== FETCH & FILTER ===== */
$search = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
$filterStatus = $_GET['status'] ?? '';
$filterKategori = $_GET['kategori'] ?? '';
$filterDate = $_GET['date'] ?? '';
$sortBy = $_GET['sort'] ?? 'tanggal_mulai';
$sortDir = $_GET['dir'] ?? 'DESC';

$where = "1=1";
$params = [];
if ($filterStatus) { $where .= " AND status = ?"; $params[] = $filterStatus; }
if ($filterKategori) { $where .= " AND kategori = ?"; $params[] = $filterKategori; }
if ($filterDate) { $where .= " AND DATE(tanggal_mulai) = ?"; $params[] = $filterDate; }
if (isset($_GET['q']) && $_GET['q']) { 
    $where .= " AND (judul LIKE ? OR deskripsi LIKE ? OR lokasi LIKE ?)"; 
    $params[] = $search; $params[] = $search; $params[] = $search; 
}

$validSorts = ['tanggal_mulai', 'judul', 'created_at', 'kategori'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'tanggal_mulai';
$sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

try {
    $sql = "SELECT * FROM agenda WHERE $where ORDER BY $sortBy $sortDir";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $agendaList = $stmt->fetchAll();
} catch (Throwable $e) {
    $agendaList = [];
}

$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    try {
        $st = $db->prepare("SELECT * FROM agenda WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

/* ===== MINI CALENDAR DATA ===== */
$calendarDates = [];
try {
    $stmt = $db->query("SELECT DATE(tanggal_mulai) as tgl, COUNT(*) as total, status FROM agenda WHERE MONTH(tanggal_mulai) = MONTH(CURDATE()) AND YEAR(tanggal_mulai) = YEAR(CURDATE()) GROUP BY DATE(tanggal_mulai), status");
    foreach ($stmt->fetchAll() as $row) {
        $calendarDates[$row['tgl']][] = $row;
    }
} catch (Throwable $e) {}

$statusConfig = [
    'akan_datang' => ['label' => 'Akan Datang', 'color' => '#3B82F6', 'icon' => 'fa-calendar-plus', 'bg' => 'rgba(59,130,246,0.1)', 'badge' => 'primary'],
    'berlangsung' => ['label' => 'Berlangsung', 'color' => '#10B981', 'icon' => 'fa-circle', 'bg' => 'rgba(16,185,129,0.1)', 'badge' => 'success'],
    'selesai' => ['label' => 'Selesai', 'color' => '#6B7280', 'icon' => 'fa-check-circle', 'bg' => 'rgba(107,114,128,0.1)', 'badge' => 'default'],
    'batal' => ['label' => 'Dibatalkan', 'color' => '#EF4444', 'icon' => 'fa-times-circle', 'bg' => 'rgba(239,68,68,0.1)', 'badge' => 'danger']
];

$kategoriConfig = [
    'akademik' => ['label' => 'Akademik', 'icon' => 'fa-graduation-cap', 'color' => '#8B5CF6'],
    'kemahasiswaan' => ['label' => 'Kemahasiswaan', 'icon' => 'fa-users', 'color' => '#F59E0B'],
    'riset' => ['label' => 'Riset', 'icon' => 'fa-flask', 'color' => '#06B6D4'],
    'seminar' => ['label' => 'Seminar', 'icon' => 'fa-microphone', 'color' => '#EC4899'],
    'workshop' => ['label' => 'Workshop', 'icon' => 'fa-tools', 'color' => '#14B8A6'],
    'lainnya' => ['label' => 'Lainnya', 'icon' => 'fa-calendar', 'color' => '#6B7280']
];

$todayStr = date('Y-m-d');
?>

<div class="page-content agenda-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="agenda-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Manajemen Agenda</span>
                </div>
                <h1 class="hero-title">Kelola Agenda Kegiatan</h1>
                <p class="hero-subtitle">
                    Jadwal kegiatan akademik, kemahasiswaan, dan event prodi
                    <?php if ($todayCount > 0): ?>
                    <span class="hero-today-badge">
                        <i class="fas fa-fire"></i> <?= $todayCount ?> agenda hari ini
                    </span>
                    <?php endif; ?>
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-calendar-check"></i>
                        <span><strong><?= $totalAgenda ?></strong> Total</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-circle" style="color:#10B981"></i>
                        <span><strong><?= $ongoingCount ?></strong> Live</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-clock"></i>
                        <span><strong><?= $upcomingCount ?></strong> Upcoming</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportToCSV()" title="Export ke CSV">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-ghost" onclick="window.print()" title="Print">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Agenda</span>
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

    <!-- ===== MAIN LAYOUT: CONTENT + SIDEBAR ===== -->
    <div class="agenda-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="agenda-main-col">
            <!-- ===== STATS CARDS (4) ===== -->
            <div class="stats-grid agenda-stats">
                <div class="stat-card stat-blue">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $totalAgenda ?>">0</h3>
                        <p>Total Agenda</p>
                        <span class="stat-trend up"><i class="fas fa-arrow-up"></i> Aktif</span>
                    </div>
                    <div class="stat-sparkline">
                        <svg viewBox="0 0 100 30" preserveAspectRatio="none">
                            <polyline points="0,22 15,18 30,20 45,12 60,15 75,8 90,10 100,5" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                </div>

                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fas fa-broadcast-tower"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $ongoingCount ?>">0</h3>
                        <p>Berlangsung</p>
                        <span class="stat-trend up live-pulse"><i class="fas fa-circle"></i> Live Now</span>
                    </div>
                    <div class="stat-sparkline">
                        <svg viewBox="0 0 100 30" preserveAspectRatio="none">
                            <polyline points="0,15 15,12 30,18 45,10 60,14 75,8 90,12 100,6" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                </div>

                <div class="stat-card stat-orange">
                    <div class="stat-icon"><i class="fas fa-calendar-plus"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $upcomingCount ?>">0</h3>
                        <p>Akan Datang</p>
                        <span class="stat-trend up"><i class="fas fa-clock"></i> Upcoming</span>
                    </div>
                    <div class="stat-sparkline">
                        <svg viewBox="0 0 100 30" preserveAspectRatio="none">
                            <polyline points="0,20 15,22 30,15 45,18 60,10 75,14 90,8 100,12" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                </div>

                <div class="stat-card stat-purple">
                    <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $completedCount ?>">0</h3>
                        <p>Selesai</p>
                        <span class="stat-trend up"><i class="fas fa-archive"></i> Archived</span>
                    </div>
                    <div class="stat-sparkline">
                        <svg viewBox="0 0 100 30" preserveAspectRatio="none">
                            <polyline points="0,25 15,20 30,22 45,15 60,18 75,10 90,12 100,6" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- ===== TOOLBAR ===== -->
            <div class="card agenda-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari judul, lokasi, deskripsi..." value="<?= clean($_GET['q'] ?? '') ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterStatus" class="toolbar-select">
                            <option value="">📊 Semua Status</option>
                            <option value="akan_datang" <?= $filterStatus === 'akan_datang' ? 'selected' : '' ?>>🔵 Akan Datang</option>
                            <option value="berlangsung" <?= $filterStatus === 'berlangsung' ? 'selected' : '' ?>>🟢 Berlangsung</option>
                            <option value="selesai" <?= $filterStatus === 'selesai' ? 'selected' : '' ?>>⚪ Selesai</option>
                            <option value="batal" <?= $filterStatus === 'batal' ? 'selected' : '' ?>>🔴 Dibatalkan</option>
                        </select>
                        
                        <select id="filterKategori" class="toolbar-select">
                            <option value="">🏷️ Semua Kategori</option>
                            <?php foreach ($kategoriConfig as $key => $kat): ?>
                            <option value="<?= $key ?>" <?= $filterKategori === $key ? 'selected' : '' ?>><?= $kat['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="date" id="filterDate" class="toolbar-select" value="<?= clean($filterDate) ?>" title="Filter tanggal">
                        
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
                            <a href="?sort=tanggal_mulai&dir=DESC" class="sort-item <?= $sortBy === 'tanggal_mulai' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-calendar"></i> Tanggal Terbaru
                            </a>
                            <a href="?sort=tanggal_mulai&dir=ASC" class="sort-item <?= $sortBy === 'tanggal_mulai' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-calendar"></i> Tanggal Terlama
                            </a>
                            <a href="?sort=judul&dir=ASC" class="sort-item <?= $sortBy === 'judul' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Judul A-Z
                            </a>
                            <a href="?sort=judul&dir=DESC" class="sort-item <?= $sortBy === 'judul' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-up"></i> Judul Z-A
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
                        <button class="view-btn" data-view="timeline" title="Timeline View">
                            <i class="fas fa-stream"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===== BULK ACTIONS BAR ===== -->
            <div class="bulk-bar" id="bulkBar">
                <div class="bulk-info">
                    <i class="fas fa-check-circle"></i>
                    <span><strong id="selectedCount">0</strong> agenda dipilih</span>
                </div>
                <div class="bulk-actions">
                    <button class="btn btn-sm btn-danger" onclick="bulkDelete()">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </div>

            <!-- ===== AGENDA GRID ===== -->
            <div class="agenda-grid" id="agendaGrid" data-view="grid">
                <?php if (!empty($agendaList)): ?>
                    <?php foreach ($agendaList as $a): 
                        $status = $statusConfig[$a['status']] ?? $statusConfig['akan_datang'];
                        $kategori = $kategoriConfig[$a['kategori']] ?? $kategoriConfig['lainnya'];
                        $tgl = strtotime($a['tanggal_mulai']);
                        $tglSelesai = $a['tanggal_selesai'] ? strtotime($a['tanggal_selesai']) : null;
                        $daysLeft = ceil(($tgl - time()) / 86400);
                        $isToday = date('Y-m-d', $tgl) === $todayStr;
                        $isPast = $tgl < time() && $a['status'] !== 'berlangsung';
                    ?>
                    <div class="agenda-card <?= $isPast ? 'is-past' : '' ?> <?= $isToday ? 'is-today' : '' ?>" data-id="<?= $a['id'] ?>">
                        <!-- Checkbox for bulk -->
                        <label class="card-checkbox">
                            <input type="checkbox" class="agenda-check" value="<?= $a['id'] ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <?php if ($isToday): ?>
                        <div class="today-ribbon">
                            <i class="fas fa-fire"></i> Hari Ini
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($a['is_featured'] ?? false): ?>
                        <div class="featured-star" title="Featured">
                            <i class="fas fa-star"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="ac-image" onclick="previewAgenda(<?= htmlspecialchars(json_encode($a, JSON_HEX_APOS)) ?>)">
                            <?php if ($a['gambar']): ?>
                                <img src="../uploads/<?= htmlspecialchars($a['gambar']) ?>" alt="<?= clean($a['judul']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="ac-placeholder" style="background: linear-gradient(135deg, <?= $kategori['color'] ?>22, <?= $kategori['color'] ?>11); color: <?= $kategori['color'] ?>">
                                    <i class="fas <?= $kategori['icon'] ?>"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="ac-image-overlay">
                                <div class="ac-quick-actions" onclick="event.stopPropagation()">
                                    <button onclick="editAgenda(<?= htmlspecialchars(json_encode($a, JSON_HEX_APOS)) ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="previewAgenda(<?= htmlspecialchars(json_encode($a, JSON_HEX_APOS)) ?>)" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="?action=duplicate&id=<?= $a['id'] ?>" title="Duplikasi">
                                        <i class="fas fa-copy"></i>
                                    </a>
                                    <a href="?action=delete&id=<?= $a['id'] ?>" title="Hapus" data-confirm="Yakin hapus agenda ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="ac-status-badge" style="background: <?= $status['bg'] ?>; color: <?= $status['color'] ?>">
                                <i class="fas <?= $status['icon'] ?>"></i>
                                <span><?= $status['label'] ?></span>
                            </div>
                        </div>

                        <div class="ac-body">
                            <div class="ac-top-row">
                                <span class="ac-kategori" style="background: <?= $kategori['color'] ?>15; color: <?= $kategori['color'] ?>">
                                    <i class="fas <?= $kategori['icon'] ?>"></i>
                                    <?= $kategori['label'] ?>
                                </span>
                                <?php if ($daysLeft > 0 && $daysLeft <= 7 && $a['status'] === 'akan_datang'): ?>
                                <span class="ac-countdown">
                                    <i class="fas fa-hourglass-half"></i>
                                    <span data-countdown="<?= date('c', $tgl) ?>"><?= $daysLeft ?> hari</span>
                                </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="ac-title"><?= clean($a['judul']) ?></h3>
                            
                            <div class="ac-datetime">
                                <div class="adt-item">
                                    <i class="fas fa-calendar-day"></i>
                                    <span><?= date('D, d M Y', $tgl) ?></span>
                                </div>
                                <div class="adt-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?= date('H:i', $tgl) ?><?= $tglSelesai ? ' - ' . date('H:i', $tglSelesai) : '' ?> WIB</span>
                                </div>
                            </div>

                            <div class="ac-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= clean($a['lokasi'] ?: 'Online') ?></span>
                            </div>

                            <div class="ac-footer">
                                <div class="ac-status-changer">
                                    <label>Status:</label>
                                    <select onchange="quickStatusChange(<?= $a['id'] ?>, this.value)" class="status-select" style="color: <?= $status['color'] ?>">
                                        <?php foreach ($statusConfig as $key => $st): ?>
                                        <option value="<?= $key ?>" <?= $a['status'] === $key ? 'selected' : '' ?>><?= $st['label'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button class="ac-edit-btn" onclick="editAgenda(<?= htmlspecialchars(json_encode($a, JSON_HEX_APOS)) ?>)">
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
                                <rect x="50" y="30" width="100" height="80" rx="8" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <rect x="50" y="30" width="100" height="20" rx="8" fill="var(--primary)" opacity="0.2"/>
                                <circle cx="65" cy="40" r="3" fill="var(--primary)"/>
                                <circle cx="78" cy="40" r="3" fill="var(--primary)"/>
                                <line x1="65" y1="65" x2="135" y2="65" stroke="var(--gray-300)" stroke-width="2" stroke-linecap="round"/>
                                <line x1="65" y1="78" x2="120" y2="78" stroke="var(--gray-300)" stroke-width="2" stroke-linecap="round"/>
                                <line x1="65" y1="91" x2="128" y2="91" stroke="var(--gray-300)" stroke-width="2" stroke-linecap="round"/>
                                <circle cx="150" cy="95" r="20" fill="var(--primary)" opacity="0.9"/>
                                <line x1="150" y1="88" x2="150" y2="102" stroke="white" stroke-width="3" stroke-linecap="round"/>
                                <line x1="143" y1="95" x2="157" y2="95" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Tidak ada agenda ditemukan</h3>
                        <p>Mulai tambahkan jadwal kegiatan prodi Anda</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-plus"></i> Tambah Agenda Pertama
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Result count -->
            <?php if (!empty($agendaList)): ?>
            <div class="result-info">
                <span>Menampilkan <strong><?= count($agendaList) ?></strong> agenda</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="agenda-sidebar">
            <!-- Mini Calendar -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-calendar"></i> Kalender</h3>
                    <span class="widget-badge"><?= date('F Y') ?></span>
                </div>
                <div class="widget-body">
                    <div class="mini-cal">
                        <div class="mc-weekdays">
                            <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
                        </div>
                        <div class="mc-days" id="mcDays">
                            <?php
                            $firstDay = strtotime(date('Y-m-01'));
                            $daysInMonth = (int)date('t');
                            $startWeekday = (int)date('w', $firstDay);
                            $today = (int)date('j');
                            
                            for ($i = 0; $i < $startWeekday; $i++) echo '<span class="mc-day empty"></span>';
                            
                            for ($d = 1; $d <= $daysInMonth; $d++) {
                                $dateStr = date('Y-m-') . str_pad($d, 2, '0', STR_PAD_LEFT);
                                $hasEvent = isset($calendarDates[$dateStr]);
                                $isToday = $d === $today;
                                $classes = 'mc-day';
                                if ($isToday) $classes .= ' today';
                                if ($hasEvent) $classes .= ' has-event';
                                $totalEvents = 0;
                                if ($hasEvent) {
                                    foreach ($calendarDates[$dateStr] as $ev) $totalEvents += (int)$ev['total'];
                                }
                                echo '<span class="' . $classes . '" data-date="' . $dateStr . '" title="' . $totalEvents . ' agenda">' . $d;
                                if ($hasEvent && $totalEvents > 0) {
                                    echo '<span class="mc-dot">' . ($totalEvents > 9 ? '9+' : $totalEvents) . '</span>';
                                }
                                echo '</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="mc-legend">
                        <span><span class="legend-dot today"></span> Hari ini</span>
                        <span><span class="legend-dot event"></span> Ada agenda</span>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-bolt"></i> Segera Datang</h3>
                    <a href="?sort=tanggal_mulai&dir=ASC" class="widget-link">Lihat Semua</a>
                </div>
                <div class="widget-body">
                    <?php 
                    $upcomingList = array_filter($agendaList, fn($a) => strtotime($a['tanggal_mulai']) >= time() && $a['status'] === 'akan_datang');
                    $upcomingList = array_slice($upcomingList, 0, 4);
                    ?>
                    <?php if (!empty($upcomingList)): ?>
                        <?php foreach ($upcomingList as $ua): 
                            $utgl = strtotime($ua['tanggal_mulai']);
                            $ukat = $kategoriConfig[$ua['kategori']] ?? $kategoriConfig['lainnya'];
                            $udays = ceil(($utgl - time()) / 86400);
                        ?>
                        <div class="upcoming-item" onclick="previewAgenda(<?= htmlspecialchars(json_encode($ua, JSON_HEX_APOS)) ?>)">
                            <div class="ui-date" style="border-color: <?= $ukat['color'] ?>">
                                <div class="ui-day"><?= date('d', $utgl) ?></div>
                                <div class="ui-month"><?= strtoupper(date('M', $utgl)) ?></div>
                            </div>
                            <div class="ui-info">
                                <div class="ui-title"><?= clean($ua['judul']) ?></div>
                                <div class="ui-meta">
                                    <i class="fas fa-clock"></i> <?= date('H:i', $utgl) ?> WIB
                                </div>
                            </div>
                            <span class="ui-days <?= $udays <= 3 ? 'urgent' : '' ?>"><?= $udays <= 0 ? 'Now' : $udays . 'd' ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-calendar-check"></i>
                            <p>Tidak ada agenda mendatang</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Category Stats -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-tags"></i> Kategori</h3>
                </div>
                <div class="widget-body">
                    <?php 
                    $catCounts = [];
                    foreach ($agendaList as $a) {
                        $cat = $a['kategori'] ?? 'lainnya';
                        $catCounts[$cat] = ($catCounts[$cat] ?? 0) + 1;
                    }
                    arsort($catCounts);
                    $maxCat = max($catCounts) ?: 1;
                    ?>
                    <?php if (!empty($catCounts)): ?>
                        <?php foreach ($catCounts as $catKey => $count): 
                            $cat = $kategoriConfig[$catKey] ?? $kategoriConfig['lainnya'];
                            $pct = round(($count / $maxCat) * 100);
                        ?>
                        <div class="cat-item">
                            <div class="cat-head">
                                <span class="cat-name">
                                    <i class="fas <?= $cat['icon'] ?>" style="color: <?= $cat['color'] ?>"></i>
                                    <?= $cat['label'] ?>
                                </span>
                                <span class="cat-count"><?= $count ?></span>
                            </div>
                            <div class="cat-bar">
                                <div class="cat-bar-fill" style="width: <?= $pct ?>%; background: <?= $cat['color'] ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-tags"></i>
                            <p>Belum ada data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== MODAL FORM ===== -->
<div class="modal" id="agendaModal">
    <div class="modal-content" style="max-width: 760px;">
        <div class="modal-header modal-header-gradient">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div>
                    <h3 id="modalTitle">Tambah Agenda Baru</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Lengkapi detail kegiatan di bawah ini</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="agendaForm" class="modal-body">
            <input type="hidden" name="id" id="formId">
            <input type="hidden" name="gambar_lama" id="gambarLama">
            
            <div class="form-sections">
                <!-- Section: Informasi Dasar -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-info-circle"></i> Informasi Dasar
                    </h4>
                    <div class="form-grid-2">
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label>Judul Kegiatan <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-heading"></i>
                                <input type="text" name="judul" id="judul" required placeholder="Contoh: Seminar Nasional AI 2026">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Kategori <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-tag"></i>
                                <select name="kategori" id="kategori" required>
                                    <?php foreach ($kategoriConfig as $key => $kat): ?>
                                    <option value="<?= $key ?>"><?= $kat['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Status <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-info-circle"></i>
                                <select name="status" id="status" required>
                                    <?php foreach ($statusConfig as $key => $stat): ?>
                                    <option value="<?= $key ?>"><?= $stat['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Jadwal -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-clock"></i> Jadwal Kegiatan
                    </h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Tanggal & Jam Mulai <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-calendar-plus"></i>
                                <input type="datetime-local" name="tanggal_mulai" id="tanggal_mulai" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Tanggal & Jam Selesai</label>
                            <div class="input-icon">
                                <i class="fas fa-calendar-check"></i>
                                <input type="datetime-local" name="tanggal_selesai" id="tanggal_selesai">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Detail -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-file-alt"></i> Detail Kegiatan
                    </h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Lokasi</label>
                            <div class="input-icon">
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" name="lokasi" id="lokasi" placeholder="Contoh: Ruang Aula / Zoom Meeting">
                            </div>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label>Deskripsi</label>
                            <div class="textarea-wrap">
                                <textarea name="deskripsi" id="deskripsi" rows="4" placeholder="Deskripsi lengkap kegiatan..." maxlength="500"></textarea>
                                <span class="char-counter"><span id="descCount">0</span>/500</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Media -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-image"></i> Media & Pengaturan
                    </h4>
                    <div class="form-grid-2">
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label>Gambar Poster/Banner</label>
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-placeholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Klik atau drag gambar ke sini</span>
                                    <small>Format: JPG, PNG, WEBP (Max 5MB)</small>
                                </div>
                                <img id="previewImg" class="upload-preview" style="display:none">
                                <input type="file" name="gambar" id="gambarInput" accept="image/*">
                            </div>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="checkbox-card">
                                <input type="checkbox" name="is_featured" id="is_featured" value="1">
                                <span class="checkbox-custom"></span>
                                <span class="checkbox-label">
                                    <strong><i class="fas fa-star" style="color:#F59E0B"></i> Tandai sebagai Featured</strong>
                                    <small>Agenda akan muncul di halaman utama website</small>
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
                    <i class="fas fa-save"></i> Simpan Agenda
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== PREVIEW MODAL ===== -->
<div class="modal" id="previewModal">
    <div class="modal-content" style="max-width: 640px;">
        <div class="modal-header modal-header-gradient">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-eye"></i></div>
                <div>
                    <h3>Preview Agenda</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Detail lengkap kegiatan</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePreview()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="previewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePreview()">Tutup</button>
            <button class="btn btn-primary" id="previewEditBtn"><i class="fas fa-edit"></i> Edit Agenda</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   AGENDA PAGE - KEREN EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.agenda-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.agenda-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #0F172A 0%, #1E40AF 60%, #3B82F6 100%);
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
    background: radial-gradient(circle, #3B82F6, transparent);
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
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.hero-today-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: linear-gradient(135deg, #F59E0B, #D97706);
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(245,158,11,0.4);
    animation: pulseBadge 2s infinite;
}

.hero-mini-stats {
    display: flex;
    gap: 20px;
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
    color: #60A5FA;
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

/* ===== MAIN LAYOUT ===== */
.agenda-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
    align-items: start;
}

.agenda-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.agenda-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-card {
    position: relative;
    overflow: hidden;
}

.stat-sparkline {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 35px;
    opacity: 0.3;
    pointer-events: none;
}

.stat-sparkline svg {
    width: 100%;
    height: 100%;
}

.stat-blue .stat-sparkline { color: #3B82F6; }
.stat-green .stat-sparkline { color: #10B981; }
.stat-orange .stat-sparkline { color: #F59E0B; }
.stat-purple .stat-sparkline { color: #8B5CF6; }

.stat-blue .stat-icon { background: linear-gradient(135deg, #3B82F6, #1E40AF); }
.stat-green .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-orange .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }
.stat-purple .stat-icon { background: linear-gradient(135deg, #8B5CF6, #5B21B6); }

.live-pulse {
    animation: livePulse 1.5s infinite;
}

@keyframes livePulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.live-pulse i {
    font-size: 0.5rem;
    animation: livePulse 1s infinite;
}

/* ===== TOOLBAR ===== */
.agenda-toolbar {
    padding: 16px 20px;
    margin-bottom: 20px;
}

.agenda-toolbar .card-body,
.agenda-toolbar {
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
    border-color: var(--primary);
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(16,185,129,0.1);
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
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
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
    background: var(--primary);
    color: var(--white);
    border-color: var(--primary);
    transform: translateY(-2px);
}

.toolbar-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

/* Sort Dropdown */
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
    background: rgba(16,185,129,0.1);
    color: var(--primary);
    font-weight: 600;
}

.sort-item i {
    width: 16px;
    color: var(--gray-500);
}

.sort-item.active i {
    color: var(--primary);
}

/* View Toggle */
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
    color: var(--primary);
    box-shadow: var(--shadow-sm);
}

[data-theme="dark"] .view-btn.active {
    background: var(--dark-2);
    color: var(--primary-light);
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
}

/* ===== AGENDA GRID ===== */
.agenda-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.agenda-grid[data-view="list"] {
    grid-template-columns: 1fr;
}

.agenda-grid[data-view="list"] .agenda-card {
    display: grid;
    grid-template-columns: 240px 1fr;
}

.agenda-grid[data-view="list"] .ac-image {
    height: 100%;
    min-height: 180px;
}

/* Timeline View */
.agenda-grid[data-view="timeline"] {
    grid-template-columns: 1fr;
    position: relative;
    padding-left: 40px;
}

.agenda-grid[data-view="timeline"]::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 20px;
    bottom: 20px;
    width: 3px;
    background: linear-gradient(to bottom, var(--primary), var(--secondary));
    border-radius: 3px;
}

.agenda-grid[data-view="timeline"] .agenda-card {
    position: relative;
    margin-bottom: 8px;
}

.agenda-grid[data-view="timeline"] .agenda-card::before {
    content: '';
    position: absolute;
    left: -33px;
    top: 28px;
    width: 16px;
    height: 16px;
    background: var(--white);
    border: 4px solid var(--primary);
    border-radius: 50%;
    z-index: 2;
    box-shadow: 0 0 0 4px rgba(16,185,129,0.15);
}

[data-theme="dark"] .agenda-grid[data-view="timeline"] .agenda-card::before {
    background: var(--dark-2);
}

.agenda-card {
    position: relative;
    background: var(--white);
    border-radius: var(--radius-xl);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--gray-100);
    box-shadow: var(--shadow-sm);
}

[data-theme="dark"] .agenda-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.agenda-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(16,185,129,0.3);
}

.agenda-card.is-past {
    opacity: 0.7;
}

.agenda-card.is-today {
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

.agenda-card:hover .card-checkbox,
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
    background: var(--primary);
    border-color: var(--primary);
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

/* Today Ribbon */
.today-ribbon {
    position: absolute;
    top: 14px;
    left: 50px;
    z-index: 12;
    padding: 4px 12px;
    background: linear-gradient(135deg, #F59E0B, #D97706);
    color: var(--white);
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 4px 12px rgba(245,158,11,0.4);
    animation: pulseBadge 2s infinite;
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
}

/* Card Image */
.ac-image {
    position: relative;
    height: 170px;
    background: var(--gray-100);
    overflow: hidden;
    cursor: pointer;
}

.ac-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.agenda-card:hover .ac-image img {
    transform: scale(1.08);
}

.ac-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
}

.ac-image-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent 70%);
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    padding: 14px;
    opacity: 0;
    transition: opacity var(--transition);
}

.agenda-card:hover .ac-image-overlay {
    opacity: 1;
}

.ac-quick-actions {
    display: flex;
    gap: 6px;
}

.ac-quick-actions button,
.ac-quick-actions a {
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

.ac-quick-actions button:hover,
.ac-quick-actions a:hover {
    background: var(--primary);
    color: var(--white);
    transform: translateY(-3px);
}

.ac-status-badge {
    position: absolute;
    bottom: 12px;
    left: 12px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

/* Card Body */
.ac-body {
    padding: 18px;
}

.ac-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.ac-kategori {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.ac-countdown {
    padding: 4px 10px;
    background: rgba(239,68,68,0.1);
    color: #EF4444;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    animation: pulseBadge 2s infinite;
}

.ac-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 12px;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.8em;
}

[data-theme="dark"] .ac-title {
    color: var(--white);
}

.ac-datetime {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 12px;
}

.adt-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    color: var(--gray-600);
}

[data-theme="dark"] .adt-item {
    color: var(--gray-400);
}

.adt-item i {
    color: var(--primary);
    width: 14px;
    font-size: 0.78rem;
}

.ac-location {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: var(--gray-50);
    border-radius: var(--radius-sm);
    margin-bottom: 14px;
    font-size: 0.82rem;
    color: var(--gray-700);
}

[data-theme="dark"] .ac-location {
    background: rgba(255,255,255,0.04);
    color: var(--gray-300);
}

.ac-location i {
    color: var(--primary);
    font-size: 0.78rem;
}

.ac-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 14px;
    border-top: 1px solid var(--gray-100);
    gap: 10px;
}

[data-theme="dark"] .ac-footer {
    border-color: rgba(255,255,255,0.08);
}

.ac-status-changer {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: var(--gray-600);
    flex: 1;
}

[data-theme="dark"] .ac-status-changer {
    color: var(--gray-400);
}

.status-select {
    padding: 4px 8px;
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    font-family: inherit;
    background: transparent;
    cursor: pointer;
    transition: all var(--transition-fast);
}

[data-theme="dark"] .status-select {
    border-color: rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.05);
}

.status-select:focus {
    outline: none;
    border-color: var(--primary);
}

.ac-edit-btn {
    width: 34px;
    height: 34px;
    border-radius: var(--radius-sm);
    background: var(--primary);
    color: var(--white);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
    flex-shrink: 0;
}

.ac-edit-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px) rotate(5deg);
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
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
    color: var(--primary);
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== SIDEBAR WIDGETS ===== */
.agenda-sidebar {
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
    color: var(--primary);
}

.widget-badge {
    padding: 3px 10px;
    background: var(--primary);
    color: var(--white);
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
}

.widget-link {
    font-size: 0.78rem;
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    transition: all var(--transition-fast);
}

.widget-link:hover {
    color: var(--primary-dark);
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

/* Mini Calendar */
.mini-cal {
    margin-bottom: 12px;
}

.mc-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    margin-bottom: 6px;
}

.mc-weekdays span {
    text-align: center;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--gray-500);
    text-transform: uppercase;
    padding: 4px 0;
}

.mc-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 3px;
}

.mc-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--dark);
    border-radius: 8px;
    cursor: pointer;
    transition: all var(--transition-fast);
    position: relative;
}

[data-theme="dark"] .mc-day {
    color: var(--gray-200);
}

.mc-day.empty {
    cursor: default;
}

.mc-day:not(.empty):hover {
    background: var(--gray-100);
    transform: scale(1.1);
}

[data-theme="dark"] .mc-day:not(.empty):hover {
    background: rgba(255,255,255,0.08);
}

.mc-day.today {
    background: var(--gradient-primary);
    color: var(--white);
    font-weight: 700;
    box-shadow: 0 4px 10px rgba(16,185,129,0.3);
}

.mc-day.has-event {
    background: rgba(59,130,246,0.1);
    color: var(--secondary);
    font-weight: 600;
}

.mc-day.today.has-event {
    background: var(--gradient-primary);
    color: var(--white);
}

.mc-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    min-width: 14px;
    height: 14px;
    padding: 0 3px;
    background: var(--secondary);
    color: var(--white);
    border-radius: 7px;
    font-size: 0.58rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mc-day.today .mc-dot {
    background: var(--white);
    color: var(--primary);
}

.mc-legend {
    display: flex;
    gap: 14px;
    justify-content: center;
    padding-top: 12px;
    border-top: 1px solid var(--gray-100);
    font-size: 0.72rem;
    color: var(--gray-600);
}

[data-theme="dark"] .mc-legend {
    border-color: rgba(255,255,255,0.08);
    color: var(--gray-400);
}

.mc-legend span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.legend-dot.today {
    background: var(--gradient-primary);
}

.legend-dot.event {
    background: var(--secondary);
}

/* Upcoming Items */
.upcoming-item {
    display: flex;
    gap: 12px;
    padding: 10px;
    border-radius: var(--radius);
    cursor: pointer;
    transition: all var(--transition-fast);
    margin-bottom: 6px;
    align-items: center;
}

.upcoming-item:hover {
    background: var(--gray-50);
    transform: translateX(4px);
}

[data-theme="dark"] .upcoming-item:hover {
    background: rgba(255,255,255,0.04);
}

.ui-date {
    width: 48px;
    text-align: center;
    padding: 6px 4px;
    background: var(--white);
    border-radius: var(--radius-sm);
    border-left: 3px solid var(--primary);
    flex-shrink: 0;
    box-shadow: var(--shadow-sm);
}

[data-theme="dark"] .ui-date {
    background: var(--dark);
}

.ui-day {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    line-height: 1;
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .ui-day {
    color: var(--white);
}

.ui-month {
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--gray-600);
    margin-top: 2px;
}

[data-theme="dark"] .ui-month {
    color: var(--gray-400);
}

.ui-info {
    flex: 1;
    min-width: 0;
}

.ui-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 3px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

[data-theme="dark"] .ui-title {
    color: var(--white);
}

.ui-meta {
    font-size: 0.72rem;
    color: var(--gray-600);
    display: flex;
    align-items: center;
    gap: 4px;
}

[data-theme="dark"] .ui-meta {
    color: var(--gray-400);
}

.ui-meta i {
    color: var(--primary);
}

.ui-days {
    padding: 3px 8px;
    background: rgba(59,130,246,0.12);
    color: #1E40AF;
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
    flex-shrink: 0;
}

.ui-days.urgent {
    background: rgba(239,68,68,0.12);
    color: #B91C1C;
    animation: pulseBadge 2s infinite;
}

/* Category Stats */
.cat-item {
    margin-bottom: 14px;
}

.cat-item:last-child {
    margin-bottom: 0;
}

.cat-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.cat-name {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
}

[data-theme="dark"] .cat-name {
    color: var(--gray-200);
}

.cat-count {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--gray-600);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .cat-count {
    color: var(--gray-400);
}

.cat-bar {
    height: 6px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .cat-bar {
    background: rgba(255,255,255,0.08);
}

.cat-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

@keyframes barFill {
    from { width: 0; }
}

/* ===== MODAL ENHANCEMENTS ===== */
.modal-header-gradient {
    background: linear-gradient(135deg, #0F172A 0%, #1E40AF 100%);
    color: var(--white);
    border-bottom: none;
}

.modal-header-gradient h3 {
    color: var(--white);
}

.modal-header-content {
    display: flex;
    gap: 14px;
    align-items: center;
}

.modal-icon {
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.15);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    backdrop-filter: blur(10px);
    flex-shrink: 0;
}

.modal-header-gradient .modal-close {
    background: rgba(255,255,255,0.15);
    color: var(--white);
}

.modal-header-gradient .modal-close:hover {
    background: rgba(239,68,68,0.9);
}

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
    color: var(--primary);
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
}

/* Upload Area */
.upload-area {
    position: relative;
    border: 2px dashed var(--gray-300);
    border-radius: var(--radius);
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: all var(--transition);
    background: var(--gray-50);
    overflow: hidden;
}

[data-theme="dark"] .upload-area {
    border-color: rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.03);
}

.upload-area:hover,
.upload-area.dragover {
    border-color: var(--primary);
    background: rgba(16,185,129,0.05);
}

.upload-placeholder i {
    font-size: 2.2rem;
    color: var(--gray-400);
    margin-bottom: 10px;
}

.upload-placeholder span {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 4px;
}

[data-theme="dark"] .upload-placeholder span {
    color: var(--gray-200);
}

.upload-placeholder small {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.upload-area input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.upload-preview {
    max-width: 100%;
    max-height: 200px;
    border-radius: var(--radius-sm);
    object-fit: cover;
}

/* Checkbox Card */
.checkbox-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    padding: 14px;
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
    width: 22px;
    height: 22px;
    border: 2px solid var(--gray-300);
    border-radius: 6px;
    flex-shrink: 0;
    position: relative;
    transition: all var(--transition);
}

.checkbox-card input:checked + .checkbox-custom {
    background: var(--primary);
    border-color: var(--primary);
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
    font-size: 0.9rem;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 6px;
}

[data-theme="dark"] .checkbox-label strong {
    color: var(--white);
}

.checkbox-label small {
    font-size: 0.78rem;
    color: var(--gray-600);
}

[data-theme="dark"] .checkbox-label small {
    color: var(--gray-400);
}

/* ===== PREVIEW MODAL ===== */
.preview-hero {
    height: 200px;
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    margin: -28px -28px 20px;
}

.preview-hero img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-hero-placeholder {
    font-size: 4rem;
    color: var(--gray-400);
}

.preview-badges {
    position: absolute;
    top: 14px;
    left: 14px;
    display: flex;
    gap: 8px;
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

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .agenda-layout {
        grid-template-columns: 1fr;
    }
    
    .agenda-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .agenda-page {
        padding: 16px;
    }
    
    .agenda-hero {
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
    
    .agenda-toolbar {
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
    
    .agenda-grid {
        grid-template-columns: 1fr;
    }
    
    .agenda-grid[data-view="list"] .agenda-card {
        grid-template-columns: 1fr;
    }
    
    .agenda-grid[data-view="timeline"] {
        padding-left: 30px;
    }
    
    .form-grid-2 {
        grid-template-columns: 1fr;
    }
    
    .preview-info-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .agenda-hero,
    .agenda-toolbar,
    .agenda-sidebar,
    .bulk-bar,
    .card-checkbox,
    .ac-quick-actions,
    .ac-edit-btn,
    .ac-status-changer {
        display: none !important;
    }
    
    .agenda-layout {
        grid-template-columns: 1fr;
    }
    
    .agenda-card {
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

    /* ===== MODAL FUNCTIONS ===== */
    window.openModal = function() {
        document.getElementById('agendaForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('gambarLama').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Agenda Baru';
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('descCount').textContent = '0';
        document.getElementById('agendaModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('judul')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('agendaModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.editAgenda = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Agenda';
        document.getElementById('formId').value = data.id;
        document.getElementById('judul').value = data.judul;
        document.getElementById('kategori').value = data.kategori;
        document.getElementById('status').value = data.status;
        document.getElementById('tanggal_mulai').value = data.tanggal_mulai ? data.tanggal_mulai.replace(' ', 'T') : '';
        document.getElementById('tanggal_selesai').value = data.tanggal_selesai ? data.tanggal_selesai.replace(' ', 'T') : '';
        document.getElementById('lokasi').value = data.lokasi || '';
        document.getElementById('deskripsi').value = data.deskripsi || '';
        document.getElementById('gambarLama').value = data.gambar || '';
        document.getElementById('is_featured').checked = data.is_featured == 1;
        document.getElementById('descCount').textContent = (data.deskripsi || '').length;
        
        const previewImg = document.getElementById('previewImg');
        if (data.gambar) {
            previewImg.src = '../uploads/' + data.gambar;
            previewImg.style.display = 'block';
        } else {
            previewImg.style.display = 'none';
        }
        
        document.getElementById('agendaModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== PREVIEW MODAL ===== */
    window.previewAgenda = function(data) {
        const statusConfig = <?= json_encode($statusConfig) ?>;
        const kategoriConfig = <?= json_encode($kategoriConfig) ?>;
        const status = statusConfig[data.status] || statusConfig.akan_datang;
        const kategori = kategoriConfig[data.kategori] || kategoriConfig.lainnya;
        
        const tglMulai = data.tanggal_mulai ? new Date(data.tanggal_mulai.replace(' ', 'T')) : null;
        const tglSelesai = data.tanggal_selesai ? new Date(data.tanggal_selesai.replace(' ', 'T')) : null;
        
        const formatTanggal = (d) => d ? d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-';
        const formatJam = (d) => d ? d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-';
        
        const body = document.getElementById('previewBody');
        body.innerHTML = `
            <div class="preview-hero">
                ${data.gambar 
                    ? `<img src="../uploads/${data.gambar}" alt="${data.judul}">`
                    : `<div class="preview-hero-placeholder" style="color: ${kategori.color}"><i class="fas ${kategori.icon}"></i></div>`
                }
                <div class="preview-badges">
                    <span class="badge badge-${status.badge}"><i class="fas ${status.icon}"></i> ${status.label}</span>
                    <span class="badge" style="background: ${kategori.color}20; color: ${kategori.color}"><i class="fas ${kategori.icon}"></i> ${kategori.label}</span>
                    ${data.is_featured == 1 ? '<span class="badge badge-warning"><i class="fas fa-star"></i> Featured</span>' : ''}
                </div>
            </div>
            
            <h2 style="font-size: 1.4rem; margin-bottom: 16px; color: var(--dark)">${data.judul}</h2>
            
            <div class="preview-info-grid">
                <div>
                    <div class="preview-label"><i class="fas fa-calendar-day"></i> Tanggal Mulai</div>
                    <div class="preview-value">${formatTanggal(tglMulai)}</div>
                    <div style="font-size: 0.82rem; color: var(--gray-600); margin-top: 2px">${formatJam(tglMulai)}</div>
                </div>
                <div>
                    <div class="preview-label"><i class="fas fa-calendar-check"></i> Tanggal Selesai</div>
                    <div class="preview-value">${formatTanggal(tglSelesai)}</div>
                    <div style="font-size: 0.82rem; color: var(--gray-600); margin-top: 2px">${formatJam(tglSelesai)}</div>
                </div>
                <div style="grid-column: 1 / -1">
                    <div class="preview-label"><i class="fas fa-map-marker-alt"></i> Lokasi</div>
                    <div class="preview-value">${data.lokasi || 'Online'}</div>
                </div>
            </div>
            
            ${data.deskripsi ? `
            <div class="preview-section">
                <div class="preview-label"><i class="fas fa-file-alt"></i> Deskripsi</div>
                <div class="preview-value">${data.deskripsi}</div>
            </div>
            ` : ''}
        `;
        
        document.getElementById('previewEditBtn').onclick = function() {
            closePreview();
            setTimeout(() => editAgenda(data), 300);
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
    const gambarInput = document.getElementById('gambarInput');
    const previewImg = document.getElementById('previewImg');

    gambarInput?.addEventListener('change', e => {
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

    /* ===== CHARACTER COUNTER ===== */
    const deskripsi = document.getElementById('deskripsi');
    const descCount = document.getElementById('descCount');
    
    deskripsi?.addEventListener('input', function() {
        descCount.textContent = this.value.length;
        if (this.value.length >= 480) {
            descCount.style.color = 'var(--warning)';
        } else {
            descCount.style.color = '';
        }
    });

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const filterKategori = document.getElementById('filterKategori');
    const filterDate = document.getElementById('filterDate');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    filterStatus?.addEventListener('change', applyFilters);
    filterKategori?.addEventListener('change', applyFilters);
    filterDate?.addEventListener('change', applyFilters);

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const status = filterStatus?.value;
        const kategori = filterKategori?.value;
        const date = filterDate?.value;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        if (kategori) url.searchParams.set('kategori', kategori); else url.searchParams.delete('kategori');
        if (date) url.searchParams.set('date', date); else url.searchParams.delete('date');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        url.searchParams.delete('q');
        url.searchParams.delete('status');
        url.searchParams.delete('kategori');
        url.searchParams.delete('date');
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
            const grid = document.getElementById('agendaGrid');
            grid.dataset.view = view;
            
            localStorage.setItem('agenda_view', view);
        });
    });

    // Restore saved view
    const savedView = localStorage.getItem('agenda_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');

    document.querySelectorAll('.agenda-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
        });
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
        document.querySelectorAll('.agenda-check').forEach(c => c.checked = false);
        updateBulkBar();
    };

    window.bulkDelete = function() {
        if (selectedIds.size === 0) return;
        if (!confirm(`Yakin hapus ${selectedIds.size} agenda terpilih?`)) return;
        
        const ids = Array.from(selectedIds).join(',');
        window.location.href = `?action=bulk-delete&ids=${ids}`;
    };

    /* ===== QUICK STATUS CHANGE ===== */
    window.quickStatusChange = function(id, newStatus) {
        if (window.IFPro?.toast) {
            window.IFPro.toast.show('Mengubah status...', 'info');
        }
        window.location.href = `?action=status&id=${id}&status=${newStatus}`;
    };

    /* ===== EXPORT CSV (Real Implementation) ===== */
    window.exportToCSV = function() {
        const cards = document.querySelectorAll('.agenda-card');
        if (cards.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        // Get data from PHP (embedded)
        const data = <?= json_encode(array_map(function($a) use ($statusConfig, $kategoriConfig) {
            return [
                'id' => $a['id'],
                'judul' => $a['judul'],
                'kategori' => $kategoriConfig[$a['kategori']]['label'] ?? $a['kategori'],
                'status' => $statusConfig[$a['status']]['label'] ?? $a['status'],
                'tanggal_mulai' => $a['tanggal_mulai'],
                'tanggal_selesai' => $a['tanggal_selesai'] ?? '-',
                'lokasi' => $a['lokasi'] ?? '-',
                'featured' => ($a['is_featured'] ?? 0) ? 'Ya' : 'Tidak'
            ];
        }, $agendaList)) ?>;
        
        const headers = ['ID', 'Judul', 'Kategori', 'Status', 'Tanggal Mulai', 'Tanggal Selesai', 'Lokasi', 'Featured'];
        const rows = data.map(d => [d.id, `"${d.judul.replace(/"/g, '""')}"`, d.kategori, d.status, d.tanggal_mulai, d.tanggal_selesai, `"${(d.lokasi || '').replace(/"/g, '""')}"`, d.featured]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `agenda-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${data.length} agenda berhasil di-export`, 'success');
        }, 500);
    };

    /* ===== CALENDAR DATE CLICK ===== */
    document.querySelectorAll('.mc-day.has-event, .mc-day.today').forEach(day => {
        day.addEventListener('click', function() {
            const date = this.dataset.date;
            if (date) {
                const url = new URL(window.location);
                url.searchParams.set('date', date);
                window.location = url;
            }
        });
    });

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        // N = New agenda
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
    document.getElementById('agendaModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    
    document.getElementById('previewModal')?.addEventListener('click', function(e) {
        if (e.target === this) closePreview();
    });

    /* ===== COUNTDOWN UPDATER ===== */
    function updateCountdowns() {
        document.querySelectorAll('[data-countdown]').forEach(el => {
            const target = new Date(el.dataset.countdown);
            const now = new Date();
            const diff = target - now;
            
            if (diff <= 0) {
                el.textContent = 'Dimulai!';
                return;
            }
            
            const days = Math.floor(diff / 86400000);
            const hours = Math.floor((diff % 86400000) / 3600000);
            
            if (days > 0) {
                el.textContent = `${days}h ${hours}j`;
            } else {
                const mins = Math.floor((diff % 3600000) / 60000);
                el.textContent = `${hours}j ${mins}m`;
            }
        });
    }
    
    updateCountdowns();
    setInterval(updateCountdowns, 60000);

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
        setTimeout(() => editAgenda(<?= json_encode($editData, JSON_HEX_APOS) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c📅 Agenda Ultimate v4.0 Loaded', 'color:#3B82F6;font-weight:bold;font-size:14px');
    console.log('%c   Features: Calendar, Timeline, Bulk Actions, Quick Preview, CSV Export', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: N (New), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>