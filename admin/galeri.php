<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Galeri';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
    setFlash('error', 'Koneksi database gagal');
}

/* ===== HANDLE ACTIONS ===== */
if (isset($_GET['action']) && isset($_GET['id']) && $db) {
    $id = (int)$_GET['id'];
    try {
        if ($_GET['action'] === 'delete') {
            $stmt = $db->prepare("SELECT gambar FROM galeri WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['gambar'] && file_exists(__DIR__ . '/../uploads/' . $row['gambar'])) {
                unlink(__DIR__ . '/../uploads/' . $row['gambar']);
            }
            $db->prepare("DELETE FROM galeri WHERE id = ?")->execute([$id]);
            setFlash('success', 'Foto berhasil dihapus');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal memproses permintaan');
    }
    header('Location: galeri.php'); exit;
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $judul = clean($_POST['judul']);
        $deskripsi = clean($_POST['deskripsi']);
        $kategori = clean($_POST['kategori']);
        $tanggal = $_POST['tanggal'] ?: date('Y-m-d');
        
        $gambar = $_POST['gambar_lama'] ?? null;
        if (!empty($_FILES['gambar']['name'])) {
            $newImage = uploadImage($_FILES['gambar'], 'uploads/galeri');
            if ($newImage) {
                if ($gambar && file_exists(__DIR__ . '/../uploads/' . $gambar)) {
                    unlink(__DIR__ . '/../uploads/' . $gambar);
                }
                $gambar = $newImage;
            }
        }
        
        if ($id) {
            $stmt = $db->prepare("UPDATE galeri SET judul=?, deskripsi=?, gambar=?, kategori=?, tanggal=? WHERE id=?");
            $stmt->execute([$judul, $deskripsi, $gambar, $kategori, $tanggal, $id]);
            setFlash('success', 'Foto berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO galeri (judul, deskripsi, gambar, kategori, tanggal, created_at) VALUES (?,?,?,?,?,NOW())");
            $stmt->execute([$judul, $deskripsi, $gambar, $kategori, $tanggal]);
            setFlash('success', 'Foto baru berhasil ditambahkan');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan data: ' . $e->getMessage());
    }
    header('Location: galeri.php'); exit;
}

/* ===== FETCH PARAMETERS ===== */
$search = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
$filterCat = $_GET['kategori'] ?? '';

$where = "1=1";
$params = [];
if ($filterCat) { $where .= " AND kategori = ?"; $params[] = $filterCat; }
if (isset($_GET['q']) && $_GET['q']) { 
    $where .= " AND (judul LIKE ? OR deskripsi LIKE ?)"; 
    $params[] = $search; $params[] = $search; 
}

/* ===== FETCH DATA ===== */
$galeriList = [];
$stats = ['total' => 0, 'categories' => 0];
$catStats = [];

if ($db) {
    try {
        $sql = "SELECT * FROM galeri WHERE $where ORDER BY tanggal DESC, created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $galeriList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM galeri")->fetchColumn();
        
        $cats = $db->query("SELECT DISTINCT kategori FROM galeri WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);
        $stats['categories'] = count($cats);
        
        $catStats = $db->query("SELECT kategori, COUNT(*) as total FROM galeri WHERE kategori IS NOT NULL AND kategori != '' GROUP BY kategori ORDER BY total DESC LIMIT 5")->fetchAll();
    } catch (Throwable $e) {
        $cats = [];
    }
}

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM galeri WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content galeri-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="galeri-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-academic-pattern"><i class="fas fa-camera-retro"></i></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-images"></i>
                    <span>Visual Documentation</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-photo-video"></i>
                    </span>
                    Kelola Galeri Foto
                </h1>
                <p class="hero-subtitle">
                    Dokumentasi kegiatan, fasilitas, dan momen berharga Program Studi Informatika
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-images"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total Foto</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-folder-open" style="color:#F59E0B"></i>
                        <span><strong><?= $stats['categories'] ?></strong> Kategori</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Foto</span>
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
    <div class="galeri-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="galeri-main-col">
            <!-- Toolbar -->
            <div class="card galeri-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari judul atau deskripsi..." value="<?= clean($_GET['q'] ?? '') ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <!-- Custom Kategori Dropdown -->
                    <div class="custom-dropdown" id="catDropdown">
                        <button class="dropdown-trigger" id="catTrigger">
                            <i class="fas fa-folder" style="color: #6B7280"></i>
                            <span id="catLabel"><?= $filterCat ? clean($filterCat) : 'Semua Kategori' ?></span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu" id="catMenu">
                            <div class="dropdown-item <?= !$filterCat ? 'active' : '' ?>" data-value="">
                                <i class="fas fa-th-large" style="color: #6B7280"></i>
                                <span>Semua Kategori</span>
                                <span class="dropdown-count"><?= $stats['total'] ?></span>
                            </div>
                            <?php foreach ($cats as $c): ?>
                            <div class="dropdown-item <?= $filterCat === $c ? 'active' : '' ?>" data-value="<?= clean($c) ?>">
                                <i class="fas fa-folder" style="color: #8B5CF6"></i>
                                <span><?= clean($c) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div each>
                </div>
                
                <div class="toolbar-right">
                    <button class="toolbar-icon-btn" onclick="resetFilters()" title="Reset Filter">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
            </div>

            <!-- Gallery Cards Grid -->
            <div class="galeri-grid" id="galeriGrid">
                <?php if (!empty($galeriList)): ?>
                    <?php foreach ($galeriList as $g): 
                        $thumb = $g['gambar'] ? '../uploads/' . $g['gambar'] : 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=400&q=80';
                    ?>
                    <div class="galeri-card" data-id="<?= $g['id'] ?>">
                        <div class="gc-image-wrapper">
                            <img src="<?= clean($thumb) ?>" alt="<?= clean($g['judul']) ?>" loading="lazy">
                            <div class="gc-overlay">
                                <div class="gc-category-badge"><?= clean($g['kategori'] ?: 'Umum') ?></div>
                                <div class="gc-quick-actions">
                                    <button onclick='editGaleri(<?= htmlspecialchars(json_encode($g, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)' title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=delete&id=<?= $g['id'] ?>" title="Hapus" data-confirm="Yakin hapus foto ini? Tindakan ini tidak dapat dibatalkan.">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="gc-body">
                            <h3 class="gc-title"><?= clean($g['judul']) ?></h3>
                            <p class="gc-excerpt"><?= clean(mb_substr($g['deskripsi'] ?: 'Tidak ada deskripsi', 0, 90)) ?><?= strlen($g['deskripsi'] ?: '') > 90 ? '...' : '' ?></p>
                            <div class="gc-footer">
                                <span class="gc-date">
                                    <i class="fas fa-calendar-alt"></i> 
                                    <?= function_exists('formatTanggal') ? formatTanggal($g['tanggal']) : date('d M Y', strtotime($g['tanggal'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration"><i class="fas fa-images"></i></div>
                        <h3>Belum ada foto</h3>
                        <p>Unggah foto pertama untuk memperkaya galeri website</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Unggah Foto Pertama</button>
                            <button class="btn btn-secondary" onclick="resetFilters()"><i class="fas fa-redo"></i> Reset Filter</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="galeri-sidebar">
            <!-- Category Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-bar"></i> Kategori Populer</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($catStats)): 
                        $maxCat = max(array_column($catStats, 'total'));
                        $catColors = ['#8B5CF6', '#EC4899', '#3B82F6', '#10B981', '#F59E0B'];
                    ?>
                        <?php foreach ($catStats as $index => $cs): 
                            $pct = round(($cs['total'] / max($maxCat, 1)) * 100);
                            $color = $catColors[$index % count($catColors)];
                        ?>
                        <div class="cat-stat-item" onclick="filterByCat('<?= clean($cs['kategori']) ?>')" style="cursor:pointer">
                            <div class="csi-head">
                                <span class="csi-name" style="color: <?= $color ?>">
                                    <i class="fas fa-folder"></i> <?= clean($cs['kategori']) ?>
                                </span>
                                <span class="csi-count"><?= $cs['total'] ?></span>
                            </div>
                            <div class="csi-bar">
                                <div class="csi-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty"><i class="fas fa-chart-bar"></i><p>Belum ada data kategori</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tips Widget -->
            <div class="card widget-card tips-card">
                <div class="widget-header">
                    <h3><i class="fas fa-lightbulb"></i> Tips Upload</h3>
                </div>
                <div class="widget-body">
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-image"></i></div>
                        <div class="tip-text">Gunakan gambar dengan resolusi minimal <strong>800x600px</strong> agar terlihat tajam.</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-file-image"></i></div>
                        <div class="tip-text">Format yang didukung: <strong>JPG, PNG, WEBP</strong> dengan ukuran maksimal 5MB.</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-tag"></i></div>
                        <div class="tip-text">Isi <strong>Kategori</strong> dengan konsisten (misal: "Wisuda", "Seminar") untuk memudahkan filter.</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== MODAL FORM ===== -->
<div class="modal-ultimate" id="galeriModal">
    <div class="modal-content-ultimate" style="max-width:650px;">
        <div class="modal-header-ultimate" style="background: linear-gradient(135deg, #EC4899, #8B5CF6); color: white;">
            <div class="modal-header-content">
                <div class="modal-icon" style="background: rgba(255,255,255,0.2);"><i class="fas fa-camera"></i></div>
                <div>
                    <h3 style="color: white;" id="modalTitle">Tambah Foto</h3>
                    <p style="color: rgba(255,255,255,0.8); margin:0;">Lengkapi detail dokumentasi</p>
                </div>
            </div>
            <button class="modal-close-ultimate" onclick="closeModal()" style="background: rgba(255,255,255,0.2); color: white;"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="galeriForm" class="modal-body-ultimate">
            <input type="hidden" name="id" id="formId">
            <input type="hidden" name="gambar_lama" id="gambarLama">
            
            <div class="form-grid-2">
                <div class="form-group-ultimate" style="grid-column: 1 / -1;">
                    <label>Judul Foto <span class="required">*</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-heading"></i>
                        <input type="text" name="judul" id="judul" required placeholder="Contoh: Wisuda Periode I Tahun 2026">
                    </div>
                </div>
                
                <div class="form-group-ultimate">
                    <label>Kategori</label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-tag"></i>
                        <input type="text" name="kategori" id="kategori" placeholder="Contoh: Wisuda" list="kategoriList">
                        <datalist id="kategoriList">
                            <?php foreach ($cats as $c): ?><option value="<?= clean($c) ?>"><?php endforeach; ?>
                        </datalist>
                    </div>
                </div>

                <div class="form-group-ultimate">
                    <label>Tanggal Kegiatan</label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" name="tanggal" id="tanggal" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="form-group-ultimate" style="grid-column: 1 / -1;">
                    <label>Deskripsi</label>
                    <div class="textarea-group-ultimate">
                        <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Deskripsi singkat mengenai foto..."></textarea>
                        <span class="char-counter"><span id="deskripsiCount">0</span>/300</span>
                    </div>
                </div>

                <div class="form-group-ultimate" style="grid-column: 1 / -1;">
                    <label>File Gambar <span class="required">*</span></label>
                    <div class="upload-area-ultimate" id="uploadArea">
                        <div class="upload-placeholder-ultimate" id="uploadPlaceholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Klik atau drag gambar ke sini</span>
                            <small>JPG, PNG, WEBP (Max 5MB)</small>
                        </div>
                        <img id="previewImg" class="upload-preview-ultimate" style="display:none">
                        <input type="file" name="gambar" id="gambarInput" accept="image/*">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer-ultimate">
                <button type="button" class="btn-secondary-ultimate" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn-primary-ultimate" style="background: linear-gradient(135deg, #EC4899, #8B5CF6); box-shadow: 0 4px 16px rgba(236,72,153,0.4);">
                    <i class="fas fa-save"></i> Simpan Foto
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* ===== GALERI PAGE ULTIMATE v4.0 ===== */
.galeri-page { padding: var(--spacing-lg); animation: fadeIn 0.4s ease; }

/* Hero */
.galeri-hero {
    position: relative; padding: 36px 40px; margin-bottom: 28px;
    background: linear-gradient(135deg, #EC4899 0%, #8B5CF6 50%, #6366F1 100%);
    border-radius: var(--radius-2xl); overflow: hidden; box-shadow: var(--shadow-xl);
}
.hero-bg-effects { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.hero-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.5; animation: orbFloat 20s infinite ease-in-out; }
.orb-1 { width: 320px; height: 320px; background: radial-gradient(circle, #F9A8D4, transparent); top: -120px; right: 8%; }
.orb-2 { width: 220px; height: 220px; background: radial-gradient(circle, #C4B5FD, transparent); bottom: -100px; left: 15%; animation-delay: -7s; }
.hero-grid-pattern { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 40px 40px; }
.hero-academic-pattern { position: absolute; top: 50%; right: 5%; transform: translateY(-50%); font-size: 18rem; color: rgba(255,255,255,0.03); pointer-events: none; display: flex; align-items: center; justify-content: center; }
.hero-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; color: var(--white); }
.hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); border-radius: 50px; font-size: 0.78rem; font-weight: 600; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
.hero-title { font-size: 2.2rem; font-weight: 700; margin-bottom: 10px; line-height: 1.2; font-family: 'Space Grotesk', sans-serif; display: flex; align-items: center; gap: 16px; }
.title-icon-wrap { width: 56px; height: 56px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
.hero-subtitle { font-size: 0.95rem; opacity: 0.9; max-width: 600px; }
.hero-mini-stats { display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
.hms-item { display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; font-size: 0.85rem; }
.hms-item i { color: #FCE7F3; }
.hms-item strong { font-family: 'Space Grotesk', sans-serif; font-weight: 700; }
.hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-hero-primary { padding: 14px 26px; background: linear-gradient(135deg, #10B981, #047857); color: var(--white); border: none; border-radius: var(--radius); font-size: 0.92rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; box-shadow: 0 8px 20px rgba(16,185,129,0.4); }
.btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(16,185,129,0.5); }

/* Layout */
.galeri-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.galeri-main-col { min-width: 0; }

/* Toolbar */
.galeri-toolbar { padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; overflow: visible !important; }
.toolbar-left { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; flex: 1; min-width: 0; overflow: visible !important; }
.toolbar-search { position: relative; flex: 1; min-width: 240px; max-width: 380px; }
.toolbar-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.9rem; pointer-events: none; }
.toolbar-search input { width: 100%; padding: 10px 44px 10px 42px; border: 1.5px solid var(--gray-200); border-radius: var(--radius); font-size: 0.88rem; font-family: inherit; background: var(--gray-50); transition: all var(--transition); }
[data-theme="dark"] .toolbar-search input { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.toolbar-search input:focus { outline: none; border-color: #EC4899; background: var(--white); box-shadow: 0 0 0 4px rgba(236,72,153,0.1); }
.search-kbd { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); padding: 2px 8px; background: var(--gray-200); color: var(--gray-600); border-radius: 4px; font-size: 0.7rem; font-weight: 600; font-family: 'Courier New', monospace; pointer-events: none; }

/* Custom Dropdown */
.custom-dropdown { position: relative; min-width: 200px; z-index: 1000; overflow: visible !important; }
.dropdown-trigger {
    display: flex; align-items: center; gap: 10px; padding: 10px 16px;
    background: var(--white); border: 1.5px solid var(--gray-200); border-radius: var(--radius);
    font-size: 0.88rem; font-weight: 600; color: var(--dark); cursor: pointer;
    transition: all var(--transition); width: 100%; text-align: left; font-family: inherit;
}
[data-theme="dark"] .dropdown-trigger { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.dropdown-trigger:hover { border-color: #EC4899; box-shadow: 0 0 0 3px rgba(236,72,153,0.1); }
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
    max-height: 300px;
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
.dropdown-menu::-webkit-scrollbar-track { background: rgba(236,72,153,0.05); border-radius: 3px; }
.dropdown-menu::-webkit-scrollbar-thumb { background: #EC4899; border-radius: 3px; }
.custom-dropdown.open .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }

.dropdown-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: var(--radius); cursor: pointer; transition: all var(--transition-fast); font-size: 0.88rem; color: var(--dark); white-space: nowrap; }
[data-theme="dark"] .dropdown-item { color: var(--gray-200); }
.dropdown-item:hover { background: rgba(236,72,153,0.08); transform: translateX(4px); }
.dropdown-item.active { background: linear-gradient(135deg, rgba(236,72,153,0.15), rgba(139,92,246,0.1)); color: #EC4899; font-weight: 600; }
.dropdown-item i:first-child { width: 18px; font-size: 0.9rem; }
.dropdown-count { margin-left: auto; font-size: 0.72rem; color: var(--gray-500); background: var(--gray-100); padding: 2px 8px; border-radius: 10px; font-weight: 600; }
[data-theme="dark"] .dropdown-count { background: rgba(255,255,255,0.08); color: var(--gray-400); }
.dropdown-item.active .dropdown-count { background: rgba(236,72,153,0.2); color: #EC4899; }

.toolbar-right { display: flex; gap: 10px; align-items: center; }
.toolbar-icon-btn { width: 40px; height: 40px; border-radius: var(--radius); border: 1.5px solid var(--gray-200); background: var(--white); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--gray-600); transition: all var(--transition); font-size: 0.9rem; }
[data-theme="dark"] .toolbar-icon-btn { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--gray-300); }
.toolbar-icon-btn:hover { background: #EC4899; color: var(--white); border-color: #EC4899; transform: translateY(-2px); }

/* Gallery Grid & Cards */
.galeri-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px; }
.galeri-card { position: relative; background: var(--white); border-radius: var(--radius-xl); overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; }
[data-theme="dark"] .galeri-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.galeri-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: rgba(236,72,153,0.3); }

.gc-image-wrapper { position: relative; height: 200px; overflow: hidden; background: var(--gray-100); }
.gc-image-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
.galeri-card:hover .gc-image-wrapper img { transform: scale(1.08); }

.gc-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.2) 50%, transparent 100%);
    display: flex; flex-direction: column; justify-content: space-between; padding: 16px;
    opacity: 0; transition: opacity 0.3s ease; z-index: 2;
}
.galeri-card:hover .gc-overlay { opacity: 1; }

.gc-category-badge {
    align-self: flex-start; padding: 5px 12px;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3); border-radius: 20px;
    font-size: 0.72rem; font-weight: 600; color: white;
}

.gc-quick-actions { display: flex; gap: 8px; align-self: flex-end; }
.gc-quick-actions button, .gc-quick-actions a {
    width: 38px; height: 38px; border-radius: var(--radius-sm);
    background: rgba(255,255,255,0.95); border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: var(--dark); text-decoration: none; transition: all var(--transition-fast);
    backdrop-filter: blur(10px); font-size: 0.9rem;
}
.gc-quick-actions button:hover, .gc-quick-actions a:hover { background: #EC4899; color: var(--white); transform: translateY(-3px); }
.gc-quick-actions a:last-child:hover { background: #EF4444; }

.gc-body { padding: 20px; display: flex; flex-direction: column; flex: 1; }
.gc-title { font-size: 1.05rem; font-weight: 700; color: var(--dark); margin-bottom: 8px; line-height: 1.3; }
[data-theme="dark"] .gc-title { color: var(--white); }
.gc-excerpt { font-size: 0.85rem; color: var(--gray-600); margin-bottom: 16px; line-height: 1.5; flex: 1; }
[data-theme="dark"] .gc-excerpt { color: var(--gray-400); }
.gc-footer { display: flex; justify-content: flex-start; padding-top: 12px; border-top: 1px solid var(--gray-100); }
[data-theme="dark"] .gc-footer { border-color: rgba(255,255,255,0.08); }
.gc-date { font-size: 0.75rem; color: var(--gray-600); display: flex; align-items: center; gap: 6px; }
[data-theme="dark"] .gc-date { color: var(--gray-400); }
.gc-date i { color: #EC4899; }

/* Sidebar */
.galeri-sidebar { display: flex; flex-direction: column; gap: 20px; position: sticky; top: 90px; }
.widget-card { overflow: hidden; background: var(--white); border-radius: var(--radius-xl); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); }
[data-theme="dark"] .widget-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.widget-header { padding: 16px 18px; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); }
[data-theme="dark"] .widget-header { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.08); }
.widget-header h3 { font-size: 0.95rem; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 8px; margin: 0; }
[data-theme="dark"] .widget-header h3 { color: var(--white); }
.widget-header h3 i { color: #EC4899; }
.widget-body { padding: 16px 18px; }
.widget-empty { text-align: center; padding: 24px 12px; color: var(--gray-600); }
.widget-empty i { font-size: 2rem; color: var(--gray-300); margin-bottom: 8px; display: block; }

.cat-stat-item { padding: 10px 0; border-bottom: 1px solid var(--gray-100); transition: all var(--transition-fast); }
[data-theme="dark"] .cat-stat-item { border-color: rgba(255,255,255,0.05); }
.cat-stat-item:last-child { border-bottom: none; }
.cat-stat-item:hover { background: rgba(236,72,153,0.04); padding-left: 8px; border-radius: var(--radius); }
.csi-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.csi-name { font-size: 0.82rem; font-weight: 600; color: var(--dark); display: flex; align-items: center; gap: 8px; }
[data-theme="dark"] .csi-name { color: var(--gray-200); }
.csi-name i { font-size: 0.78rem; }
.csi-count { font-size: 0.85rem; font-weight: 700; color: var(--dark); font-family: 'Space Grotesk', sans-serif; }
[data-theme="dark"] .csi-count { color: var(--white); }
.csi-bar { height: 6px; background: var(--gray-100); border-radius: 50px; overflow: hidden; }
[data-theme="dark"] .csi-bar { background: rgba(255,255,255,0.08); }
.csi-bar-fill { height: 100%; border-radius: 50px; animation: barFill 1s ease-out; }

.tips-card { background: linear-gradient(135deg, rgba(236,72,153,0.04), rgba(139,92,246,0.04)); border: 1px solid rgba(236,72,153,0.1); }
.tip-item { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid rgba(236,72,153,0.1); align-items: flex-start; }
.tip-item:last-child { border-bottom: none; }
.tip-icon { width: 28px; height: 28px; border-radius: var(--radius-sm); background: rgba(236,72,153,0.12); color: #EC4899; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; margin-top: 1px; }
.tip-text { font-size: 0.82rem; color: var(--gray-700); line-height: 1.5; flex: 1; }
[data-theme="dark"] .tip-text { color: var(--gray-300); }

/* Modal & Upload */
.modal-ultimate { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 20px; animation: fadeIn 0.3s; }
.modal-ultimate.open { display: flex; }
.modal-content-ultimate { background: var(--white); border-radius: var(--radius-2xl); width: 100%; max-width: 650px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; animation: modalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 30px 60px rgba(0,0,0,0.3); }
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

.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group-ultimate { display: flex; flex-direction: column; gap: 6px; }
.form-group-ultimate label { font-size: 0.88rem; font-weight: 600; color: var(--dark); }
[data-theme="dark"] .form-group-ultimate label { color: var(--gray-200); }
.input-group-ultimate, .textarea-group-ultimate { position: relative; }
.input-group-ultimate i, .textarea-group-ultimate i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 1rem; z-index: 1; pointer-events: none; }
.input-group-ultimate input, .input-group-ultimate select, .textarea-group-ultimate textarea { width: 100%; padding: 12px 16px 12px 46px; border: 1.5px solid var(--gray-200); border-radius: var(--radius); font-size: 0.95rem; font-family: inherit; background: var(--gray-50); transition: all var(--transition); color: var(--dark); }
[data-theme="dark"] .input-group-ultimate input, [data-theme="dark"] .input-group-ultimate select, [data-theme="dark"] .textarea-group-ultimate textarea { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.input-group-ultimate input:focus, .input-group-ultimate select:focus, .textarea-group-ultimate textarea:focus { outline: none; border-color: #EC4899; background: var(--white); box-shadow: 0 0 0 4px rgba(236,72,153,0.1); }
.textarea-group-ultimate textarea { padding-left: 16px; min-height: 100px; resize: vertical; }
.char-counter { position: absolute; bottom: 10px; right: 16px; font-size: 0.75rem; color: var(--gray-500); background: var(--white); padding: 2px 8px; border-radius: 4px; }
[data-theme="dark"] .char-counter { background: var(--dark); color: var(--gray-400); }

.upload-area-ultimate {
    position: relative; border: 2px dashed var(--gray-300); border-radius: var(--radius-lg);
    padding: 30px 20px; text-align: center; cursor: pointer; transition: all 0.3s ease;
    background: var(--gray-50); min-height: 160px; display: flex; flex-direction: column;
    align-items: center; justify-content: center; overflow: hidden;
}
[data-theme="dark"] .upload-area-ultimate { border-color: rgba(255,255,255,0.2); background: rgba(255,255,255,0.02); }
.upload-area-ultimate:hover, .upload-area-ultimate.dragover { border-color: #EC4899; background: rgba(236,72,153,0.05); transform: scale(1.01); }
.upload-placeholder-ultimate { color: var(--gray-600); pointer-events: none; }
.upload-placeholder-ultimate i { font-size: 2.5rem; color: #EC4899; margin-bottom: 10px; display: block; }
.upload-placeholder-ultimate span { display: block; font-size: 0.95rem; font-weight: 600; margin-bottom: 4px; color: var(--dark); }
[data-theme="dark"] .upload-placeholder-ultimate span { color: var(--white); }
.upload-placeholder-ultimate small { font-size: 0.75rem; color: var(--gray-500); }
.upload-area-ultimate input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; z-index: 2; }
.upload-preview-ultimate { max-width: 100%; max-height: 180px; border-radius: var(--radius); object-fit: cover; box-shadow: var(--shadow-md); z-index: 1; }

.btn-secondary-ultimate, .btn-primary-ultimate { padding: 12px 24px; border-radius: var(--radius); font-size: 0.9rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; border: none; text-decoration: none; }
.btn-secondary-ultimate { background: var(--gray-100); color: var(--gray-700); }
[data-theme="dark"] .btn-secondary-ultimate { background: rgba(255,255,255,0.08); color: var(--gray-300); }
.btn-secondary-ultimate:hover { background: var(--gray-200); }
.btn-primary-ultimate { background: linear-gradient(135deg, #EC4899, #8B5CF6); color: var(--white); box-shadow: 0 4px 16px rgba(236,72,153,0.4); }
.btn-primary-ultimate:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(236,72,153,0.5); color: white; }

.empty-state-ult { grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--white); border-radius: var(--radius-xl); border: 2px dashed var(--gray-200); }
[data-theme="dark"] .empty-state-ult { background: var(--dark-2); border-color: rgba(255,255,255,0.1); }
.empty-illustration i { font-size: 3.5rem; color: var(--gray-300); margin-bottom: 16px; display: block; }
[data-theme="dark"] .empty-illustration i { color: var(--gray-600); }
.empty-state-ult h3 { font-size: 1.3rem; color: var(--dark); margin-bottom: 8px; }
[data-theme="dark"] .empty-state-ult h3 { color: var(--white); }
.empty-state-ult p { color: var(--gray-600); margin-bottom: 24px; }
[data-theme="dark"] .empty-state-ult p { color: var(--gray-400); }
.empty-actions { display: flex; gap: 12px; justify-content: center; }

@media (max-width: 1200px) {
    .galeri-layout { grid-template-columns: 1fr; }
    .galeri-sidebar { position: static; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
}
@media (max-width: 768px) {
    .galeri-page { padding: 16px; }
    .galeri-hero { padding: 24px 20px; }
    .hero-title { font-size: 1.6rem; }
    .title-icon-wrap { width: 44px; height: 44px; font-size: 1.2rem; }
    .hero-content { flex-direction: column; align-items: flex-start; }
    .hero-actions { width: 100%; }
    .galeri-toolbar { flex-direction: column; align-items: stretch; }
    .toolbar-left, .toolbar-right { width: 100%; flex-direction: column; align-items: stretch; }
    .toolbar-search { max-width: 100%; }
    .toolbar-right { flex-direction: row; justify-content: space-between; }
    .galeri-grid { grid-template-columns: 1fr; }
    .custom-dropdown { width: 100%; }
    .form-grid-2 { grid-template-columns: 1fr; }
}
</style>

<script>
(function() {
    'use strict';

    // Custom Dropdown Logic
    const dropdown = document.getElementById('catDropdown');
    const trigger = document.getElementById('catTrigger');
    const menu = document.getElementById('catMenu');
    const label = document.getElementById('catLabel');
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
                const text = this.querySelector('span').textContent;
                const icon = this.querySelector('i');
                
                label.textContent = text;
                trigger.querySelector('i:first-child').style.color = icon.style.color;
                
                items.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                dropdown.classList.remove('open');
                applyFilters(value);
            });
        });
    }

    window.filterByCat = function(cat) {
        applyFilters(cat);
    };

    window.applyFilters = function(cat) {
        const url = new URL(window.location);
        const q = document.getElementById('searchInput').value.trim();
        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (cat) url.searchParams.set('kategori', cat); else url.searchParams.delete('kategori');
        window.location = url;
    };

    window.resetFilters = function() {
        const url = new URL(window.location);
        url.searchParams.delete('q');
        url.searchParams.delete('kategori');
        window.location = url;
    };

    // Modal Functions
    window.openModal = function() {
        document.getElementById('galeriForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('gambarLama').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Foto';
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('uploadPlaceholder').style.display = 'flex';
        document.getElementById('deskripsiCount').textContent = '0';
        document.getElementById('galeriModal').classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('judul')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('galeriModal').classList.remove('open');
        document.body.style.overflow = '';
    };

    window.editGaleri = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Foto';
        document.getElementById('formId').value = data.id;
        document.getElementById('judul').value = data.judul;
        document.getElementById('kategori').value = data.kategori || '';
        document.getElementById('deskripsi').value = data.deskripsi || '';
        document.getElementById('tanggal').value = data.tanggal || '<?= date('Y-m-d') ?>';
        document.getElementById('gambarLama').value = data.gambar || '';
        document.getElementById('deskripsiCount').textContent = (data.deskripsi || '').length;
        
        const preview = document.getElementById('previewImg');
        const placeholder = document.getElementById('uploadPlaceholder');
        if (data.gambar) {
            preview.src = '../uploads/' + data.gambar;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
        }
        
        document.getElementById('galeriModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    // Upload Area Logic
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
            reader.onload = function(ev) {
                previewImg.src = ev.target.result;
                previewImg.style.display = 'block';
                uploadPlaceholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    ['dragenter', 'dragover'].forEach(evt => {
        uploadArea?.addEventListener(evt, function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(evt => {
        uploadArea?.addEventListener(evt, function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
    });

    uploadArea?.addEventListener('drop', function(e) {
        const file = e.dataTransfer.files[0];
        if (file) {
            gambarInput.files = e.dataTransfer.files;
            gambarInput.dispatchEvent(new Event('change'));
        }
    });

    // Deskripsi Counter
    document.getElementById('deskripsi')?.addEventListener('input', function() {
        document.getElementById('deskripsiCount').textContent = this.value.length;
    });

    // Search Debounce
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const url = new URL(window.location);
                const q = this.value.trim();
                if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
                window.location = url;
            }, 600);
        });
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                const url = new URL(window.location);
                const q = this.value.trim();
                if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
                window.location = url;
            }
        });
    }

    // Modal Backdrop Click
    document.getElementById('galeriModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'all 0.4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 400);
        });
    }, 5000);

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            searchInput?.focus();
        }
        if (e.key === 'Escape') {
            const modal = document.getElementById('galeriModal');
            if (modal?.classList.contains('open')) closeModal();
            if (dropdown) dropdown.classList.remove('open');
        }
    });

    // Auto Open Edit Modal
    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && $editData): ?>
    window.addEventListener('load', () => {
        setTimeout(() => editGaleri(<?= json_encode($editData, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c📸 Galeri Page Ultimate Loaded', 'color:#EC4899;font-weight:bold;font-size:12px');
})();
</script>

<?php include 'includes/footer.php'; ?>