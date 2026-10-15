<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Kurikulum';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
    setFlash('error', 'Koneksi database gagal');
}

/* ===== HANDLE ACTIONS ===== */
if (isset($_GET['action']) && $db) {
    $id = (int)($_GET['id'] ?? 0);
    try {
        if ($_GET['action'] === 'delete' && $id) {
            $db->prepare("DELETE FROM mata_kuliah WHERE id = ?")->execute([$id]);
            setFlash('success', 'Mata kuliah berhasil dihapus');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menghapus mata kuliah');
    }
    header('Location: kurikulum.php'); exit;
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $semester = (int)$_POST['semester'];
        $kode = strtoupper(clean($_POST['kode']));
        $nama = clean($_POST['nama']);
        $sks = (int)$_POST['sks'];
        $urutan = (int)$_POST['urutan'];
        
        if ($id) {
            $stmt = $db->prepare("UPDATE mata_kuliah SET semester=?, kode=?, nama=?, sks=?, urutan=? WHERE id=?");
            $stmt->execute([$semester, $kode, $nama, $sks, $urutan, $id]);
            setFlash('success', 'Mata kuliah berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO mata_kuliah (semester, kode, nama, sks, urutan) VALUES (?,?,?,?,?)");
            $stmt->execute([$semester, $kode, $nama, $sks, $urutan]);
            setFlash('success', 'Mata kuliah baru berhasil ditambahkan');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan data: ' . $e->getMessage());
    }
    header('Location: kurikulum.php'); exit;
}

/* ===== FETCH PARAMETERS ===== */
$search = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
$filterSemester = $_GET['semester'] ?? '';

$where = "1=1";
$params = [];
if ($filterSemester) { $where .= " AND semester = ?"; $params[] = $filterSemester; }
if (isset($_GET['q']) && $_GET['q']) { 
    $where .= " AND (nama LIKE ? OR kode LIKE ?)"; 
    $params[] = $search; $params[] = $search; 
}

/* ===== FETCH DATA ===== */
$mkList = [];
$stats = ['total_mk' => 0, 'total_sks' => 0, 'total_semester' => 0];
$sksPerSemester = [];

if ($db) {
    try {
        $sql = "SELECT * FROM mata_kuliah WHERE $where ORDER BY semester ASC, urutan ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $mkList = $stmt->fetchAll();
        
        $stats['total_mk'] = (int)$db->query("SELECT COUNT(*) FROM mata_kuliah")->fetchColumn();
        $stats['total_sks'] = (int)$db->query("SELECT COALESCE(SUM(sks), 0) FROM mata_kuliah")->fetchColumn();
        $stats['total_semester'] = (int)$db->query("SELECT COUNT(DISTINCT semester) FROM mata_kuliah")->fetchColumn();
        
        $sksPerSemester = $db->query("SELECT semester, SUM(sks) as total_sks, COUNT(*) as total_mk FROM mata_kuliah GROUP BY semester ORDER BY semester ASC")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM mata_kuliah WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content kurikulum-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="kurikulum-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-academic-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-book-open"></i>
                    <span>Curriculum Management</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-graduation-cap"></i>
                    </span>
                    Kelola Kurikulum
                </h1>
                <p class="hero-subtitle">
                    Manajemen struktur mata kuliah untuk 8 semester Program Studi Informatika
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-book"></i>
                        <span><strong><?= $stats['total_mk'] ?></strong> Total MK</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-credit-card" style="color:#10B981"></i>
                        <span><strong><?= $stats['total_sks'] ?></strong> Total SKS</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-layer-group" style="color:#F59E0B"></i>
                        <span><strong><?= $stats['total_semester'] ?: 8 ?></strong> Semester</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportKurikulum()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Mata Kuliah</span>
                </button>
            </div>
        </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?> fade-in">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <span><?= clean($flash['message']) ?></span>
        <button onclick="this.parentElement.remove()" class="alert-close"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>

    <!-- ===== MAIN LAYOUT ===== -->
    <div class="kurikulum-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="kurikulum-main-col">
            <!-- Toolbar dengan Custom Dropdown -->
            <div class="card kurikulum-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari kode atau nama mata kuliah..." value="<?= clean($_GET['q'] ?? '') ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <!-- Custom Semester Dropdown -->
                    <div class="custom-dropdown" id="semesterDropdown">
                        <button class="dropdown-trigger" id="dropdownTrigger">
                            <i class="fas fa-layer-group"></i>
                            <span id="dropdownLabel"><?= $filterSemester ? 'Semester ' . $filterSemester : '📚 Semua Semester' ?></span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <div class="dropdown-item <?= !$filterSemester ? 'active' : '' ?>" data-value="">
                                <i class="fas fa-book-open"></i>
                                <span>Semua Semester</span>
                                <span class="dropdown-count"><?= $stats['total_mk'] ?> MK</span>
                            </div>
                            <?php for ($i = 1; $i <= 8; $i++): 
                                $semesterColors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316', '#6366F1'];
                                $color = $semesterColors[$i - 1];
                            ?>
                            <div class="dropdown-item <?= $filterSemester == $i ? 'active' : '' ?>" data-value="<?= $i ?>">
                                <i class="fas fa-book" style="color: <?= $color ?>"></i>
                                <span>Semester <?= $i ?></span>
                                <span class="dropdown-count"><?= count(array_filter($mkList, fn($m) => $m['semester'] == $i)) ?> MK</span>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <button class="toolbar-icon-btn" onclick="resetFilters()" title="Reset Filter">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
            </div>

            <!-- Filter Indicator -->
            <?php if ($filterSemester): ?>
            <div class="filter-indicator">
                <i class="fas fa-filter"></i>
                <span>Menampilkan mata kuliah <strong>Semester <?= $filterSemester ?></strong></span>
                <button onclick="resetFilters()" class="clear-filter">
                    <i class="fas fa-times"></i> Hapus Filter
                </button>
            </div>
            <?php endif; ?>

            <!-- MK Cards Grid -->
            <div class="mk-grid" id="mkGrid">
                <?php if (!empty($mkList)): ?>
                    <?php foreach ($mkList as $mk): 
                        $semesterColors = [
                            1 => ['#3B82F6', '#1E40AF'], 2 => ['#10B981', '#047857'],
                            3 => ['#F59E0B', '#B45309'], 4 => ['#8B5CF6', '#5B21B6'],
                            5 => ['#EC4899', '#BE185D'], 6 => ['#06B6D4', '#0891B2'],
                            7 => ['#F97316', '#C2410C'], 8 => ['#6366F1', '#4338CA']
                        ];
                        $sColor = $semesterColors[$mk['semester']] ?? ['#6B7280', '#374151'];
                    ?>
                    <div class="mk-card" data-id="<?= $mk['id'] ?>" data-semester="<?= $mk['semester'] ?>">
                        <div class="mkc-header" style="background: linear-gradient(135deg, <?= $sColor[0] ?>22, <?= $sColor[1] ?>11)">
                            <i class="fas fa-book" style="font-size: 2.5rem; color: <?= $sColor[0] ?>; opacity: 0.3;"></i>
                            <div class="mkc-overlay">
                                <div class="mkc-quick-actions">
                                    <button onclick='editMK(<?= htmlspecialchars(json_encode($mk, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)' title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=delete&id=<?= $mk['id'] ?>" title="Hapus" data-confirm="Yakin hapus mata kuliah ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="mkc-body">
                            <div class="mkc-meta">
                                <span class="mkc-semester-badge" style="background: <?= $sColor[0] ?>15; color: <?= $sColor[0] ?>">
                                    <i class="fas fa-layer-group"></i> Semester <?= $mk['semester'] ?>
                                </span>
                                <span class="mkc-urutan">
                                    <i class="fas fa-sort-numeric-down"></i> Urutan: <?= $mk['urutan'] ?>
                                </span>
                            </div>
                            <h3 class="mkc-title"><?= clean($mk['nama']) ?></h3>
                            <div class="mkc-footer">
                                <span class="mkc-kode"><i class="fas fa-hashtag"></i> <?= clean($mk['kode']) ?></span>
                                <span class="mkc-sks"><i class="fas fa-credit-card"></i> <?= $mk['sks'] ?> SKS</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration"><i class="fas fa-book-open"></i></div>
                        <h3>Belum ada mata kuliah</h3>
                        <p>Mulai tambahkan mata kuliah untuk melengkapi struktur kurikulum program studi</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Tambah Mata Kuliah</button>
                            <button class="btn btn-secondary" onclick="resetFilters()"><i class="fas fa-redo"></i> Reset Filter</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="kurikulum-sidebar">
            <div class="card widget-card">
                <div class="widget-header"><h3><i class="fas fa-chart-bar"></i> Beban SKS per Semester</h3></div>
                <div class="widget-body">
                    <?php if (!empty($sksPerSemester)): 
                        $maxSks = max(array_column($sksPerSemester, 'total_sks'));
                        foreach ($sksPerSemester as $sps): 
                            $pct = round(($sps['total_sks'] / max($maxSks, 1)) * 100);
                            $semesterColors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316', '#6366F1'];
                            $color = $semesterColors[$sps['semester'] - 1] ?? '#6366F1';
                    ?>
                        <div class="sks-stat-item" onclick="filterBySemester(<?= $sps['semester'] ?>)" style="cursor:pointer">
                            <div class="ssi-head">
                                <span class="ssi-name"><i class="fas fa-book" style="color: <?= $color ?>"></i> Semester <?= $sps['semester'] ?></span>
                                <span class="ssi-count"><?= $sps['total_sks'] ?> SKS <small style="color:var(--gray-500);font-weight:400">(<?= $sps['total_mk'] ?> MK)</small></span>
                            </div>
                            <div class="ssi-bar"><div class="ssi-bar-fill" style="width: <?= $pct ?>%; background: linear-gradient(90deg, <?= $color ?>, <?= $color ?>dd)"></div></div>
                        </div>
                    <?php 
                        endforeach; 
                    else: ?>
                        <div class="widget-empty"><i class="fas fa-chart-bar"></i><p>Belum ada data SKS</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card widget-card">
                <div class="widget-header"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
                <div class="widget-body">
                    <button class="qa-btn primary" onclick="openModal()"><i class="fas fa-plus"></i><span>Tambah Mata Kuliah</span></button>
                    <button class="qa-btn" onclick="filterBySemester(1)"><i class="fas fa-layer-group" style="color:#3B82F6"></i><span>Lihat Semester 1</span><i class="fas fa-chevron-right qa-arrow"></i></button>
                    <button class="qa-btn" onclick="exportKurikulum()"><i class="fas fa-file-csv" style="color:#10B981"></i><span>Export ke CSV</span><i class="fas fa-chevron-right qa-arrow"></i></button>
                </div>
            </div>

            <div class="card widget-card tips-card">
                <div class="widget-header"><h3><i class="fas fa-lightbulb"></i> Tips</h3></div>
                <div class="widget-body">
                    <div class="tip-item"><div class="tip-icon"><i class="fas fa-check"></i></div><div class="tip-text">Gunakan kode MK yang unik dan konsisten (misal: IF101)</div></div>
                    <div class="tip-item"><div class="tip-icon"><i class="fas fa-check"></i></div><div class="tip-text">Atur 'Urutan Tampil' untuk mengurutkan MK dalam satu semester</div></div>
                    <div class="tip-item"><div class="tip-icon"><i class="fas fa-info"></i></div><div class="tip-text">Total SKS ideal per semester adalah 18-24 SKS</div></div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== MODAL FORM ===== -->
<div class="modal-ultimate" id="mkModal">
    <div class="modal-content-ultimate" style="max-width:600px;">
        <div class="modal-header-ultimate" style="background: linear-gradient(135deg, #6366F1, #4338CA); color: white;">
            <div class="modal-header-content">
                <div class="modal-icon" style="background: rgba(255,255,255,0.2);"><i class="fas fa-book"></i></div>
                <div>
                    <h3 style="color: white;" id="modalTitle">Tambah Mata Kuliah</h3>
                    <p style="color: rgba(255,255,255,0.8); margin:0;">Lengkapi formulir data mata kuliah di bawah</p>
                </div>
            </div>
            <button class="modal-close-ultimate" onclick="closeModal()" style="background: rgba(255,255,255,0.2); color: white;"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" id="mkForm" class="modal-body-ultimate">
            <input type="hidden" name="id" id="formId">
            <div class="form-grid-2">
                <div class="form-group-ultimate">
                    <label>Semester <span class="required">*</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-layer-group"></i>
                        <select name="semester" id="semester" required>
                            <?php for ($i = 1; $i <= 8; $i++): ?><option value="<?= $i ?>">Semester <?= $i ?></option><?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group-ultimate">
                    <label>Urutan Tampil <span class="required">*</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-sort-numeric-down"></i>
                        <input type="number" name="urutan" id="urutan" required min="1" value="1">
                    </div>
                </div>
                <div class="form-group-ultimate" style="grid-column: 1 / -1;">
                    <label>Kode Mata Kuliah <span class="required">*</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-hashtag"></i>
                        <input type="text" name="kode" id="kode" required placeholder="Contoh: IF101" style="text-transform:uppercase;">
                    </div>
                </div>
                <div class="form-group-ultimate" style="grid-column: 1 / -1;">
                    <label>Nama Mata Kuliah <span class="required">*</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-book"></i>
                        <input type="text" name="nama" id="nama" required placeholder="Contoh: Dasar Pemrograman">
                    </div>
                </div>
                <div class="form-group-ultimate" style="grid-column: 1 / -1;">
                    <label>Jumlah SKS <span class="required">*</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-credit-card"></i>
                        <select name="sks" id="sks" required>
                            <option value="1">1 SKS</option><option value="2">2 SKS</option><option value="3" selected>3 SKS</option>
                            <option value="4">4 SKS</option><option value="5">5 SKS</option><option value="6">6 SKS</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer-ultimate">
                <button type="button" class="btn-secondary-ultimate" onclick="closeModal()"><i class="fas fa-times"></i> Batal</button>
                <button type="submit" class="btn-primary-ultimate" style="background: linear-gradient(135deg, #6366F1, #4338CA); box-shadow: 0 4px 16px rgba(99,102,241,0.4);"><i class="fas fa-save"></i> Simpan Mata Kuliah</button>
            </div>
        </form>
    </div>
</div>

<style>
/* ===== KURIKULUM PAGE ULTIMATE v4.0 ===== */
.kurikulum-page { padding: var(--spacing-lg); animation: fadeIn 0.4s ease; }
.kurikulum-hero { position: relative; padding: 36px 40px; margin-bottom: 28px; background: linear-gradient(135deg, #6366F1 0%, #4F46E5 50%, #4338CA 100%); border-radius: var(--radius-2xl); overflow: hidden; box-shadow: var(--shadow-xl); }
.hero-bg-effects { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.hero-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.5; animation: orbFloat 20s infinite ease-in-out; }
.orb-1 { width: 320px; height: 320px; background: radial-gradient(circle, #A5B4FC, transparent); top: -120px; right: 8%; }
.orb-2 { width: 220px; height: 220px; background: radial-gradient(circle, #818CF8, transparent); bottom: -100px; left: 15%; animation-delay: -7s; }
.hero-grid-pattern { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 40px 40px; }
.hero-academic-pattern { position: absolute; top: 50%; right: 5%; transform: translateY(-50%); font-size: 18rem; color: rgba(255,255,255,0.03); pointer-events: none; }
.hero-academic-pattern::before { content: '\f19d'; font-family: 'Font Awesome 6 Free'; font-weight: 900; }
.hero-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; color: var(--white); }
.hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); border-radius: 50px; font-size: 0.78rem; font-weight: 600; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
.hero-title { font-size: 2.2rem; font-weight: 700; margin-bottom: 10px; line-height: 1.2; font-family: 'Space Grotesk', sans-serif; display: flex; align-items: center; gap: 16px; }
.title-icon-wrap { width: 56px; height: 56px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
.hero-subtitle { font-size: 0.95rem; opacity: 0.9; max-width: 600px; }
.hero-mini-stats { display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
.hms-item { display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; font-size: 0.85rem; }
.hms-item i { color: #E0E7FF; }
.hms-item strong { font-family: 'Space Grotesk', sans-serif; font-weight: 700; }
.hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-hero-primary { padding: 14px 26px; background: linear-gradient(135deg, #10B981, #047857); color: var(--white); border: none; border-radius: var(--radius); font-size: 0.92rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; box-shadow: 0 8px 20px rgba(16,185,129,0.4); }
.btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(16,185,129,0.5); }
.btn-ghost { padding: 12px 18px; background: rgba(255,255,255,0.12); color: var(--white); border: 1px solid rgba(255,255,255,0.2); border-radius: var(--radius); font-size: 0.9rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; backdrop-filter: blur(10px); text-decoration: none; }
.btn-ghost:hover { background: rgba(255,255,255,0.22); transform: translateY(-2px); color: var(--white); }

.kurikulum-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.kurikulum-main-col { min-width: 0; }

.kurikulum-toolbar { padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; overflow: visible !important; }
.toolbar-left { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; flex: 1; min-width: 0; overflow: visible !important; }
.toolbar-search { position: relative; flex: 1; min-width: 240px; max-width: 380px; }
.toolbar-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.9rem; pointer-events: none; }
.toolbar-search input { width: 100%; padding: 10px 44px 10px 42px; border: 1.5px solid var(--gray-200); border-radius: var(--radius); font-size: 0.88rem; font-family: inherit; background: var(--gray-50); transition: all var(--transition); }
[data-theme="dark"] .toolbar-search input { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.toolbar-search input:focus { outline: none; border-color: #6366F1; background: var(--white); box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
.search-kbd { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); padding: 2px 8px; background: var(--gray-200); color: var(--gray-600); border-radius: 4px; font-size: 0.7rem; font-weight: 600; font-family: 'Courier New', monospace; pointer-events: none; }

/* ===== DROPDOWN ANTI-GAGAL ===== */
.custom-dropdown { 
    position: relative; 
    min-width: 220px; 
    z-index: 10000; 
    overflow: visible !important; 
}
.dropdown-trigger {
    display: flex; align-items: center; gap: 10px; padding: 10px 16px;
    background: var(--white); border: 1.5px solid var(--gray-200); border-radius: var(--radius);
    font-size: 0.88rem; font-weight: 600; color: var(--dark); cursor: pointer;
    transition: all var(--transition); width: 100%; text-align: left; font-family: inherit;
}
[data-theme="dark"] .dropdown-trigger { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.dropdown-trigger:hover { border-color: #6366F1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.dropdown-trigger i:first-child { color: #6366F1; }
.dropdown-arrow { margin-left: auto; transition: transform 0.3s ease; color: var(--gray-500); font-size: 0.75rem; }
.custom-dropdown.open .dropdown-arrow { transform: rotate(180deg); }

.dropdown-menu {
    position: absolute !important;
    top: calc(100% + 8px) !important;
    left: 0 !important;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    max-height: 350px;
    overflow-y: auto !important;
    z-index: 999999 !important;
    padding: 8px;
    min-width: 100%;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    display: block !important;
}
[data-theme="dark"] .dropdown-menu { background: var(--dark-2); border-color: rgba(255,255,255,0.1); }
.dropdown-menu::-webkit-scrollbar { width: 6px; }
.dropdown-menu::-webkit-scrollbar-track { background: rgba(99,102,241,0.05); border-radius: 3px; }
.dropdown-menu::-webkit-scrollbar-thumb { background: #6366F1; border-radius: 3px; }
.custom-dropdown.open .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }

.dropdown-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: var(--radius); cursor: pointer; transition: all var(--transition-fast); font-size: 0.88rem; color: var(--dark); white-space: nowrap; }
[data-theme="dark"] .dropdown-item { color: var(--gray-200); }
.dropdown-item:hover { background: rgba(99,102,241,0.08); transform: translateX(4px); }
.dropdown-item.active { background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.1)); color: #6366F1; font-weight: 600; }
.dropdown-item i:first-child { width: 18px; font-size: 0.9rem; }
.dropdown-count { margin-left: auto; font-size: 0.72rem; color: var(--gray-500); background: var(--gray-100); padding: 2px 8px; border-radius: 10px; font-weight: 600; }
[data-theme="dark"] .dropdown-count { background: rgba(255,255,255,0.08); color: var(--gray-400); }
.dropdown-item.active .dropdown-count { background: rgba(99,102,241,0.2); color: #6366F1; }

.filter-indicator { display: flex; align-items: center; gap: 10px; padding: 12px 18px; background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(139,92,246,0.05)); border: 1px solid rgba(99,102,241,0.2); border-radius: var(--radius); margin-bottom: 20px; font-size: 0.88rem; color: #6366F1; animation: slideDown 0.3s ease; }
.filter-indicator i:first-child { font-size: 0.9rem; }
.filter-indicator strong { color: #4338CA; }
.clear-filter { margin-left: auto; background: rgba(99,102,241,0.15); border: none; padding: 6px 12px; border-radius: var(--radius-sm); color: #6366F1; font-size: 0.78rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all var(--transition-fast); }
.clear-filter:hover { background: rgba(99,102,241,0.25); transform: translateY(-1px); }

.toolbar-right { display: flex; gap: 10px; align-items: center; }
.toolbar-icon-btn { width: 40px; height: 40px; border-radius: var(--radius); border: 1.5px solid var(--gray-200); background: var(--white); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--gray-600); transition: all var(--transition); font-size: 0.9rem; }
[data-theme="dark"] .toolbar-icon-btn { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--gray-300); }
.toolbar-icon-btn:hover { background: #6366F1; color: var(--white); border-color: #6366F1; transform: translateY(-2px); }

.mk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px; }
.mk-card { position: relative; background: var(--white); border-radius: var(--radius-xl); overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; }
[data-theme="dark"] .mk-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.mk-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: rgba(99,102,241,0.3); }
.mkc-header { position: relative; height: 140px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.mkc-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent 70%); display: flex; align-items: flex-end; justify-content: flex-end; padding: 14px; opacity: 0; transition: opacity var(--transition); z-index: 2; }
.mk-card:hover .mkc-overlay { opacity: 1; }
.mkc-quick-actions { display: flex; gap: 8px; }
.mkc-quick-actions button, .mkc-quick-actions a { width: 38px; height: 38px; border-radius: var(--radius-sm); background: rgba(255,255,255,0.95); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--dark); text-decoration: none; transition: all var(--transition-fast); backdrop-filter: blur(10px); font-size: 0.9rem; }
.mkc-quick-actions button:hover, .mkc-quick-actions a:hover { background: #6366F1; color: var(--white); transform: translateY(-3px); }
.mkc-quick-actions a:last-child:hover { background: #EF4444; }
.mkc-body { padding: 20px; display: flex; flex-direction: column; flex: 1; }
.mkc-meta { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
.mkc-semester-badge, .mkc-urutan { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
.mkc-urutan { background: var(--gray-100); color: var(--gray-600); }
[data-theme="dark"] .mkc-urutan { background: rgba(255,255,255,0.08); color: var(--gray-400); }
.mkc-title { font-size: 1.1rem; font-weight: 700; color: var(--dark); margin-bottom: 16px; line-height: 1.3; flex: 1; }
[data-theme="dark"] .mkc-title { color: var(--white); }
.mkc-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--gray-100); }
[data-theme="dark"] .mkc-footer { border-color: rgba(255,255,255,0.08); }
.mkc-kode, .mkc-sks { display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 600; color: var(--gray-700); }
[data-theme="dark"] .mkc-kode, [data-theme="dark"] .mkc-sks { color: var(--gray-300); }
.mkc-sks { color: #10B981; }

.kurikulum-sidebar { display: flex; flex-direction: column; gap: 20px; position: sticky; top: 90px; }
.widget-card { overflow: hidden; background: var(--white); border-radius: var(--radius-xl); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); }
[data-theme="dark"] .widget-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.widget-header { padding: 16px 18px; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); }
[data-theme="dark"] .widget-header { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.08); }
.widget-header h3 { font-size: 0.95rem; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 8px; margin: 0; }
[data-theme="dark"] .widget-header h3 { color: var(--white); }
.widget-header h3 i { color: #6366F1; }
.widget-body { padding: 16px 18px; }
.widget-empty { text-align: center; padding: 24px 12px; color: var(--gray-600); }
.widget-empty i { font-size: 2rem; color: var(--gray-300); margin-bottom: 8px; display: block; }
.sks-stat-item { padding: 10px 0; border-bottom: 1px solid var(--gray-100); transition: all var(--transition-fast); }
[data-theme="dark"] .sks-stat-item { border-color: rgba(255,255,255,0.05); }
.sks-stat-item:last-child { border-bottom: none; }
.sks-stat-item:hover { background: rgba(99,102,241,0.04); padding-left: 8px; border-radius: var(--radius); }
.ssi-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.ssi-name { font-size: 0.82rem; font-weight: 600; color: var(--dark); display: flex; align-items: center; gap: 8px; }
[data-theme="dark"] .ssi-name { color: var(--gray-200); }
.ssi-name i { font-size: 0.78rem; }
.ssi-count { font-size: 0.85rem; font-weight: 700; color: var(--dark); font-family: 'Space Grotesk', sans-serif; }
[data-theme="dark"] .ssi-count { color: var(--white); }
.ssi-bar { height: 6px; background: var(--gray-100); border-radius: 50px; overflow: hidden; }
[data-theme="dark"] .ssi-bar { background: rgba(255,255,255,0.08); }
.ssi-bar-fill { height: 100%; border-radius: 50px; animation: barFill 1s ease-out; }

.qa-btn { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--radius); background: var(--gray-50); color: var(--dark); border: none; width: 100%; text-align: left; font-size: 0.88rem; font-weight: 500; transition: all var(--transition); margin-bottom: 8px; cursor: pointer; font-family: inherit; }
[data-theme="dark"] .qa-btn { background: rgba(255,255,255,0.04); color: var(--gray-200); }
.qa-btn:hover { background: var(--gray-100); transform: translateX(4px); }
.qa-btn.primary { background: linear-gradient(135deg, #6366F1, #4338CA); color: var(--white); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
.qa-btn.primary:hover { box-shadow: 0 6px 16px rgba(99,102,241,0.4); transform: translateY(-2px); }
.qa-btn i:first-child { width: 16px; }
.qa-arrow { margin-left: auto; color: var(--gray-400); font-size: 0.72rem; opacity: 0; transition: opacity var(--transition-fast); }
.qa-btn:hover .qa-arrow { opacity: 1; }
.tips-card { background: linear-gradient(135deg, rgba(99,102,241,0.04), rgba(139,92,246,0.04)); border: 1px solid rgba(99,102,241,0.1); }
.tip-item { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid rgba(99,102,241,0.1); align-items: flex-start; }
.tip-item:last-child { border-bottom: none; }
.tip-icon { width: 28px; height: 28px; border-radius: var(--radius-sm); background: rgba(99,102,241,0.12); color: #6366F1; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; margin-top: 1px; }
.tip-text { font-size: 0.82rem; color: var(--gray-700); line-height: 1.5; flex: 1; }
[data-theme="dark"] .tip-text { color: var(--gray-300); }

.modal-ultimate { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 20px; animation: fadeIn 0.3s; }
.modal-ultimate.open { display: flex; }
.modal-content-ultimate { background: var(--white); border-radius: var(--radius-2xl); width: 100%; max-width: 600px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; animation: modalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 30px 60px rgba(0,0,0,0.3); }
[data-theme="dark"] .modal-content-ultimate { background: var(--dark-2); }
@keyframes modalIn { from { opacity: 0; transform: scale(0.9) translateY(20px); } to { opacity: 1; transform: none; } }
.modal-header-ultimate { display: flex; justify-content: space-between; align-items: center; padding: 24px 28px; }
.modal-header-content { display: flex; align-items: center; gap: 16px; }
.modal-icon { width: 50px; height: 50px; border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 1.3rem; flex-shrink: 0; }
.modal-header-ultimate h3 { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; }
.modal-close-ultimate { width: 40px; height: 40px; border-radius: var(--radius); border: none; cursor: pointer; transition: all var(--transition-fast); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
.modal-close-ultimate:hover { background: rgba(255,255,255,0.3); transform: rotate(90deg); }
.modal-body-ultimate { flex: 1; overflow-y: auto; padding: 28px; }
.modal-footer-ultimate { display: flex; justify-content: flex-end; gap: 12px; padding: 20px 28px; border-top: 1px solid var(--gray-100); background: var(--gray-50); }
[data-theme="dark"] .modal-footer-ultimate { border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.2); }
.btn-secondary-ultimate, .btn-primary-ultimate { padding: 12px 24px; border-radius: var(--radius); font-size: 0.9rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; border: none; text-decoration: none; }
.btn-secondary-ultimate { background: var(--gray-100); color: var(--gray-700); }
[data-theme="dark"] .btn-secondary-ultimate { background: rgba(255,255,255,0.08); color: var(--gray-300); }
.btn-secondary-ultimate:hover { background: var(--gray-200); }
.btn-primary-ultimate { background: linear-gradient(135deg, #6366F1, #4338CA); color: var(--white); box-shadow: 0 4px 16px rgba(99,102,241,0.4); }
.btn-primary-ultimate:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(99,102,241,0.5); color: white; }

.empty-state-ult { grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--white); border-radius: var(--radius-xl); border: 2px dashed var(--gray-200); }
[data-theme="dark"] .empty-state-ult { background: var(--dark-2); border-color: rgba(255,255,255,0.1); }
.empty-illustration i { font-size: 3.5rem; color: var(--gray-300); margin-bottom: 16px; display: block; }
[data-theme="dark"] .empty-illustration i { color: var(--gray-600); }
.empty-state-ult h3 { font-size: 1.3rem; color: var(--dark); margin-bottom: 8px; }
[data-theme="dark"] .empty-state-ult h3 { color: var(--white); }
.empty-state-ult p { color: var(--gray-600); margin-bottom: 24px; }
[data-theme="dark"] .empty-state-ult p { color: var(--gray-400); }
.empty-actions { display: flex; gap: 12px; justify-content: center; }

@media (max-width: 1200px) { .kurikulum-layout { grid-template-columns: 1fr; } .kurikulum-sidebar { position: static; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); } }
@media (max-width: 768px) {
    .kurikulum-page { padding: 16px; } .kurikulum-hero { padding: 24px 20px; } .hero-title { font-size: 1.6rem; } .title-icon-wrap { width: 44px; height: 44px; font-size: 1.2rem; }
    .hero-content { flex-direction: column; align-items: flex-start; } .hero-actions { width: 100%; } .kurikulum-toolbar { flex-direction: column; align-items: stretch; }
    .toolbar-left, .toolbar-right { width: 100%; flex-direction: column; align-items: stretch; } .toolbar-search { max-width: 100%; } .toolbar-right { flex-direction: row; justify-content: space-between; }
    .mk-grid { grid-template-columns: 1fr; } .custom-dropdown { width: 100%; }
}
@media print { .kurikulum-hero, .kurikulum-sidebar, .kurikulum-toolbar, .mkc-quick-actions { display: none !important; } .kurikulum-layout { grid-template-columns: 1fr; } .mk-card { break-inside: avoid; } }
</style>

<script>
(function() {
    'use strict';

    // ===== DROPDOWN LOGIC (ULTRA SIMPLE & ROBUST) =====
    const dropdown = document.getElementById('semesterDropdown');
    const trigger = document.getElementById('dropdownTrigger');
    const menu = document.getElementById('dropdownMenu');
    const label = document.getElementById('dropdownLabel');
    const items = menu.querySelectorAll('.dropdown-item');

    if (dropdown && trigger && menu) {
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });

        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });

        items.forEach(item => {
            item.addEventListener('click', function() {
                const value = this.dataset.value;
                label.textContent = value ? 'Semester ' + value : '📚 Semua Semester';
                items.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                dropdown.classList.remove('open');
                applyFilters(value);
            });
        });
    }

    window.filterBySemester = function(semester) { applyFilters(semester.toString()); };

    function applyFilters(semester) {
        const url = new URL(window.location);
        const q = document.getElementById('searchInput').value.trim();
        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (semester) url.searchParams.set('semester', semester); else url.searchParams.delete('semester');
        window.location = url;
    }

    // ===== MODAL FUNCTIONS =====
    window.openModal = function() {
        document.getElementById('mkForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Mata Kuliah';
        document.getElementById('mkModal').classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('kode')?.focus(), 300);
    };
    window.closeModal = function() {
        document.getElementById('mkModal').classList.remove('open');
        document.body.style.overflow = '';
    };
    window.editMK = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Mata Kuliah';
        document.getElementById('formId').value = data.id;
        document.getElementById('semester').value = data.semester;
        document.getElementById('kode').value = data.kode;
        document.getElementById('nama').value = data.nama;
        document.getElementById('sks').value = data.sks;
        document.getElementById('urutan').value = data.urutan;
        document.getElementById('mkModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    document.getElementById('kode')?.addEventListener('input', function() { this.value = this.value.toUpperCase(); });

    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const url = new URL(window.location);
            const q = this.value.trim();
            if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
            window.location = url;
        }, 600);
    });

    window.resetFilters = function() {
        const url = new URL(window.location);
        url.searchParams.delete('q'); url.searchParams.delete('semester');
        window.location = url;
    };

    window.exportKurikulum = function() {
        const data = <?= json_encode($mkList) ?>;
        if (!data || data.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        const headers = ['ID', 'Semester', 'Urutan', 'Kode', 'Nama Mata Kuliah', 'SKS'];
        const rows = data.map(d => [d.id, d.semester, d.urutan, `"${(d.kode || '').replace(/"/g, '""')}"`, `"${(d.nama || '').replace(/"/g, '""')}"`, d.sks]);
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = `kurikulum-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click(); URL.revokeObjectURL(url);
        setTimeout(() => { if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${data.length} mata kuliah berhasil di-export`, 'success'); }, 500);
    };

    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'all 0.4s'; alert.style.opacity = '0'; alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 400);
        });
    }, 5000);

    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) { e.preventDefault(); searchInput?.focus(); }
        if (e.key === 'Escape') { const modal = document.getElementById('mkModal'); if (modal?.classList.contains('open')) closeModal(); if(dropdown) dropdown.classList.remove('open'); }
    });

    document.getElementById('mkModal')?.addEventListener('click', function(e) { if (e.target === this) closeModal(); });

    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && $editData): ?>
    window.addEventListener('load', () => { setTimeout(() => editMK(<?= json_encode($editData, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>), 300); });
    <?php endif; ?>

    console.log('%c📚 Kurikulum Page Ultimate Loaded', 'color:#6366F1;font-weight:bold;font-size:12px');
})();
</script>

<?php include 'includes/footer.php'; ?>