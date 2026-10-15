<?php
require_once 'includes/auth.php';
$pageTitle = 'Kelola Testimoni';

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
            $stmt = $db->prepare("SELECT avatar FROM testimoni WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!empty($row['avatar'])) {
                deleteUploadedFile((string)$row['avatar'], 'uploads/testimoni');
            }
            $db->prepare("DELETE FROM testimoni WHERE id = ?")->execute([$id]);
            setFlash('success', 'Testimoni berhasil dihapus');
        } elseif ($_GET['action'] === 'toggle') {
            $db->prepare("UPDATE testimoni SET status = IF(status='approved','pending','approved') WHERE id = ?")->execute([$id]);
            setFlash('success', 'Status testimoni berhasil diubah');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal memproses permintaan');
    }
    header('Location: testimoni.php'); exit;
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $nama = clean($_POST['nama']);
        $tahun_masuk = clean($_POST['tahun_masuk']);
        $quote = clean($_POST['quote']);
        $urutan = (int)($_POST['urutan'] ?: 0);
        $status = clean($_POST['status']);
        
        $newImageUploaded = null;
        $existingImagePath = $id ? getExistingUploadPath($db, 'testimoni', 'avatar', $id) : null;
        $avatar = $existingImagePath ?: 'https://i.pravatar.cc/150?img=12';
        if (!empty($_FILES['avatar']['name'])) {
            $newImage = uploadImage($_FILES['avatar'], 'uploads/testimoni');
            if (!$newImage) {
                throw new RuntimeException('Gagal memvalidasi avatar testimoni.');
            }
            $newImageUploaded = $newImage;
            $avatar = $newImage;
        }
        
        if ($id) {
            $stmt = $db->prepare("UPDATE testimoni SET nama=?, tahun_masuk=?, avatar=?, quote=?, urutan=?, status=? WHERE id=?");
            $stmt->execute([$nama, $tahun_masuk, $avatar, $quote, $urutan, $status, $id]);
            setFlash('success', 'Testimoni berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO testimoni (nama, tahun_masuk, avatar, quote, urutan, status, created_at) VALUES (?,?,?,?,?,?,NOW())");
            $stmt->execute([$nama, $tahun_masuk, $avatar, $quote, $urutan, $status]);
            setFlash('success', 'Testimoni baru berhasil ditambahkan');
        }
        if ($newImageUploaded !== null) {
            if ($existingImagePath !== null && $existingImagePath !== $avatar) {
                deleteUploadedFile((string)$existingImagePath, 'uploads/testimoni');
            }
            $newImageUploaded = null;
        }
    } catch (Throwable $e) {
        if ($newImageUploaded !== null) {
            deleteUploadedFile((string)$newImageUploaded, 'uploads/testimoni');
        }
        setFlash('error', 'Gagal menyimpan data: ' . $e->getMessage());
    }
    header('Location: testimoni.php'); exit;
}

/* ===== FETCH PARAMETERS ===== */
$filterStatus = $_GET['status'] ?? '';
$where = "1=1"; 
$params = [];
if ($filterStatus) { $where .= " AND status = ?"; $params[] = $filterStatus; }

/* ===== FETCH DATA ===== */
$testimoniList = [];
$stats = ['total' => 0, 'approved' => 0, 'pending' => 0];
$statusStats = [];

if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM testimoni WHERE $where ORDER BY urutan ASC, created_at DESC");
        $stmt->execute($params);
        $testimoniList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM testimoni")->fetchColumn();
        $stats['approved'] = (int)$db->query("SELECT COUNT(*) FROM testimoni WHERE status='approved'")->fetchColumn();
        $stats['pending'] = (int)$db->query("SELECT COUNT(*) FROM testimoni WHERE status='pending'")->fetchColumn();
        
        $statusStats = $db->query("SELECT status, COUNT(*) as total FROM testimoni GROUP BY status")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM testimoni WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content testimoni-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="testimoni-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-academic-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-quote-right"></i>
                    <span>Student & Alumni Reviews</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-comments"></i>
                    </span>
                    Kelola Testimoni
                </h1>
                <p class="hero-subtitle">
                    Ulasan dan pengalaman berharga dari mahasiswa serta alumni Program Studi Informatika
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-comments"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total Testimoni</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-check-circle" style="color:#10B981"></i>
                        <span><strong><?= $stats['approved'] ?></strong> Disetujui</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-clock" style="color:#F59E0B"></i>
                        <span><strong><?= $stats['pending'] ?></strong> Pending</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Testimoni</span>
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
    <div class="testimoni-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="testimoni-main-col">
            <!-- Toolbar -->
            <div class="card testimoni-toolbar">
                <div class="toolbar-left">
                    <!-- Custom Status Dropdown -->
                    <div class="custom-dropdown" id="statusDropdown">
                        <button class="dropdown-trigger" id="statusTrigger">
                            <i class="fas fa-filter" style="color: <?= $filterStatus === 'approved' ? '#10B981' : ($filterStatus === 'pending' ? '#F59E0B' : '#6B7280') ?>"></i>
                            <span id="statusLabel"><?= $filterStatus === 'approved' ? 'Disetujui' : ($filterStatus === 'pending' ? 'Pending' : 'Semua Status') ?></span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu" id="statusMenu">
                            <div class="dropdown-item <?= !$filterStatus ? 'active' : '' ?>" data-value="">
                                <i class="fas fa-th-large" style="color: #6B7280"></i>
                                <span>Semua Status</span>
                                <span class="dropdown-count"><?= $stats['total'] ?></span>
                            </div>
                            <div class="dropdown-item <?= $filterStatus === 'approved' ? 'active' : '' ?>" data-value="approved">
                                <i class="fas fa-check-circle" style="color: #10B981"></i>
                                <span>Disetujui</span>
                                <span class="dropdown-count"><?= $stats['approved'] ?></span>
                            </div>
                            <div class="dropdown-item <?= $filterStatus === 'pending' ? 'active' : '' ?>" data-value="pending">
                                <i class="fas fa-clock" style="color: #F59E0B"></i>
                                <span>Pending</span>
                                <span class="dropdown-count"><?= $stats['pending'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <button class="toolbar-icon-btn" onclick="resetFilters()" title="Reset Filter">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
            </div>

            <!-- Testimoni Cards Grid -->
            <div class="testimoni-grid" id="testimoniGrid">
                <?php if (!empty($testimoniList)): ?>
                    <?php foreach ($testimoniList as $t): 
                        $thumb = (strpos($t['avatar'], 'http') === 0) ? $t['avatar'] : '../' . $t['avatar'];
                        $isApproved = $t['status'] === 'approved';
                    ?>
                    <div class="testimoni-card" data-id="<?= $t['id'] ?>">
                        <div class="tc-header" style="background: linear-gradient(135deg, <?= $isApproved ? '#10B98122, #04785711' : '#F59E0B22, #D9770611' ?>)">
                            <i class="fas fa-quote-right" style="font-size: 3rem; color: <?= $isApproved ? '#10B981' : '#F59E0B' ?>; opacity: 0.2; position: absolute; top: 10px; right: 15px;"></i>
                            <img src="<?= clean($thumb) ?>" alt="<?= clean($t['nama']) ?>" class="tc-avatar">
                            <div class="tc-overlay">
                                <div class="tc-quick-actions">
                                    <button onclick='editTestimoni(<?= htmlspecialchars(json_encode($t, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)' title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=toggle&id=<?= $t['id'] ?>" title="<?= $isApproved ? 'Pending' : 'Approve' ?>">
                                        <i class="fas fa-<?= $isApproved ? 'eye-slash' : 'eye' ?>"></i>
                                    </a>
                                    <a href="?action=delete&id=<?= $t['id'] ?>" title="Hapus" data-confirm="Yakin hapus testimoni ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="tc-body">
                            <div class="tc-meta">
                                <span class="tc-year"><i class="fas fa-graduation-cap"></i> <?= clean($t['tahun_masuk']) ?></span>
                                <span class="tc-order"><i class="fas fa-sort-numeric-down"></i> Urutan: <?= $t['urutan'] ?></span>
                            </div>
                            <h3 class="tc-name"><?= clean($t['nama']) ?></h3>
                            <p class="tc-quote">"<?= clean(mb_substr($t['quote'], 0, 120)) ?><?= strlen($t['quote']) > 120 ? '...' : '' ?>"</p>
                            <div class="tc-footer">
                                <span class="tc-badge" style="background: <?= $isApproved ? 'rgba(16,185,129,0.12)' : 'rgba(245,158,11,0.15)' ?>; color: <?= $isApproved ? '#047857' : '#92400E' ?>">
                                    <i class="fas fa-<?= $isApproved ? 'check-circle' : 'clock' ?>"></i>
                                    <?= ucfirst($t['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration"><i class="fas fa-comments"></i></div>
                        <h3>Belum ada testimoni</h3>
                        <p>Mulai tambahkan ulasan dari mahasiswa atau alumni</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Buat Testimoni Pertama</button>
                            <button class="btn btn-secondary" onclick="resetFilters()"><i class="fas fa-redo"></i> Reset Filter</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="testimoni-sidebar">
            <!-- Status Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribusi Status</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($statusStats)): 
                        $maxStat = max(array_column($statusStats, 'total'));
                        $statusColors = ['approved' => '#10B981', 'pending' => '#F59E0B'];
                    ?>
                        <?php foreach ($statusStats as $ss): 
                            $pct = round(($ss['total'] / max($maxStat, 1)) * 100);
                            $color = $statusColors[$ss['status']] ?? '#6B7280';
                        ?>
                        <div class="status-stat-item" onclick="filterByStatus('<?= clean($ss['status']) ?>')" style="cursor:pointer">
                            <div class="ssi-head">
                                <span class="ssi-name" style="color: <?= $color ?>">
                                    <i class="fas fa-<?= $ss['status'] === 'approved' ? 'check-circle' : 'clock' ?>"></i> 
                                    <?= ucfirst(clean($ss['status'])) ?>
                                </span>
                                <span class="ssi-count"><?= $ss['total'] ?></span>
                            </div>
                            <div class="ssi-bar">
                                <div class="ssi-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty"><i class="fas fa-chart-pie"></i><p>Belum ada data status</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="widget-body">
                    <button class="qa-btn" onclick="filterByStatus('pending')">
                        <i class="fas fa-clock" style="color:#F59E0B"></i>
                        <span>Lihat yang Pending</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </button>
                    <button class="qa-btn" onclick="filterByStatus('approved')">
                        <i class="fas fa-check-circle" style="color:#10B981"></i>
                        <span>Lihat yang Disetujui</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </button>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== MODAL FORM ===== -->
<div class="modal-ultimate" id="testimoniModal">
    <div class="modal-content-ultimate" style="max-width:650px;">
        <div class="modal-header-ultimate" style="background: linear-gradient(135deg, #8B5CF6, #6D28D9); color: white;">
            <div class="modal-header-content">
                <div class="modal-icon" style="background: rgba(255,255,255,0.2);"><i class="fas fa-comments"></i></div>
                <div>
                    <h3 style="color: white;" id="modalTitle">Tambah Testimoni</h3>
                    <p style="color: rgba(255,255,255,0.8); margin:0;">Lengkapi formulir ulasan di bawah</p>
                </div>
            </div>
            <button class="modal-close-ultimate" onclick="closeModal()" style="background: rgba(255,255,255,0.2); color: white;"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="testimoniForm" class="modal-body-ultimate">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" id="formId">
            <input type="hidden" name="avatar_lama" id="avatarLama">
            
            <div class="form-grid-2">
                <div class="form-group-ultimate">
                    <label>Nama Lengkap <span class="required">*</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-user"></i>
                        <input type="text" name="nama" id="nama" required placeholder="Contoh: Budi Santoso">
                    </div>
                </div>
                <div class="form-group-ultimate">
                    <label>Tahun Masuk / Angkatan <span class="required">*</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-graduation-cap"></i>
                        <input type="text" name="tahun_masuk" id="tahun_masuk" required placeholder="Contoh: Mahasiswa 2021">
                    </div>
                </div>
                
                <div class="form-group-ultimate" style="grid-column: 1 / -1;">
                    <label>Kutipan / Ulasan <span class="required">*</span></label>
                    <div class="textarea-group-ultimate">
                        <textarea name="quote" id="quote" rows="4" required placeholder="Tulis pengalaman atau ulasan..."></textarea>
                        <span class="char-counter"><span id="quoteCount">0</span>/500</span>
                    </div>
                </div>
                
                <div class="form-group-ultimate">
                    <label>Foto Profil (Opsional)</label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-image"></i>
                        <input type="file" name="avatar" id="avatarInput" accept="image/*" onchange="previewAvatar(this)">
                    </div>
                    <img id="avatarPreview" src="" style="display:none; width:60px; height:60px; border-radius:50%; object-fit:cover; margin-top:10px; border:2px solid var(--gray-200);">
                </div>
                
                <div class="form-group-ultimate">
                    <label>Urutan Tampil</label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-sort-numeric-down"></i>
                        <input type="number" name="urutan" id="urutan" value="0" min="0">
                    </div>
                </div>

                <div class="form-group-ultimate" style="grid-column: 1 / -1;">
                    <label>Status <span class="required">*</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-toggle-on"></i>
                        <select name="status" id="status" required>
                            <option value="approved" selected>Disetujui</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer-ultimate">
                <button type="button" class="btn-secondary-ultimate" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn-primary-ultimate" style="background: linear-gradient(135deg, #8B5CF6, #6D28D9); box-shadow: 0 4px 16px rgba(139,92,246,0.4);">
                    <i class="fas fa-save"></i> Simpan Testimoni
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* ===== TESTIMONI PAGE ULTIMATE v4.0 ===== */
.testimoni-page { padding: var(--spacing-lg); animation: fadeIn 0.4s ease; }

/* Hero */
.testimoni-hero {
    position: relative; padding: 36px 40px; margin-bottom: 28px;
    background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 50%, #4C1D95 100%);
    border-radius: var(--radius-2xl); overflow: hidden; box-shadow: var(--shadow-xl);
}
.hero-bg-effects { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.hero-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.5; animation: orbFloat 20s infinite ease-in-out; }
.orb-1 { width: 320px; height: 320px; background: radial-gradient(circle, #C4B5FD, transparent); top: -120px; right: 8%; }
.orb-2 { width: 220px; height: 220px; background: radial-gradient(circle, #A78BFA, transparent); bottom: -100px; left: 15%; animation-delay: -7s; }
.hero-grid-pattern { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 40px 40px; }
.hero-academic-pattern { position: absolute; top: 50%; right: 5%; transform: translateY(-50%); font-size: 18rem; color: rgba(255,255,255,0.03); pointer-events: none; }
.hero-academic-pattern::before { content: '\f10e'; font-family: 'Font Awesome 6 Free'; font-weight: 900; }
.hero-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; color: var(--white); }
.hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); border-radius: 50px; font-size: 0.78rem; font-weight: 600; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
.hero-title { font-size: 2.2rem; font-weight: 700; margin-bottom: 10px; line-height: 1.2; font-family: 'Space Grotesk', sans-serif; display: flex; align-items: center; gap: 16px; }
.title-icon-wrap { width: 56px; height: 56px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
.hero-subtitle { font-size: 0.95rem; opacity: 0.9; max-width: 600px; }
.hero-mini-stats { display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
.hms-item { display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; font-size: 0.85rem; }
.hms-item i { color: #E9D5FF; }
.hms-item strong { font-family: 'Space Grotesk', sans-serif; font-weight: 700; }
.hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-hero-primary { padding: 14px 26px; background: linear-gradient(135deg, #10B981, #047857); color: var(--white); border: none; border-radius: var(--radius); font-size: 0.92rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; box-shadow: 0 8px 20px rgba(16,185,129,0.4); }
.btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(16,185,129,0.5); }

/* Layout */
.testimoni-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.testimoni-main-col { min-width: 0; }

/* Toolbar */
.testimoni-toolbar { padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; overflow: visible !important; }
.toolbar-left { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; flex: 1; min-width: 0; overflow: visible !important; }
.toolbar-right { display: flex; gap: 10px; align-items: center; }
.toolbar-icon-btn { width: 40px; height: 40px; border-radius: var(--radius); border: 1.5px solid var(--gray-200); background: var(--white); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--gray-600); transition: all var(--transition); font-size: 0.9rem; }
[data-theme="dark"] .toolbar-icon-btn { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--gray-300); }
.toolbar-icon-btn:hover { background: #8B5CF6; color: var(--white); border-color: #8B5CF6; transform: translateY(-2px); }

/* Custom Dropdown */
.custom-dropdown { position: relative; min-width: 200px; z-index: 1000; overflow: visible !important; }
.dropdown-trigger {
    display: flex; align-items: center; gap: 10px; padding: 10px 16px;
    background: var(--white); border: 1.5px solid var(--gray-200); border-radius: var(--radius);
    font-size: 0.88rem; font-weight: 600; color: var(--dark); cursor: pointer;
    transition: all var(--transition); width: 100%; text-align: left; font-family: inherit;
}
[data-theme="dark"] .dropdown-trigger { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.dropdown-trigger:hover { border-color: #8B5CF6; box-shadow: 0 0 0 3px rgba(139,92,246,0.1); }
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
.dropdown-menu::-webkit-scrollbar-track { background: rgba(139,92,246,0.05); border-radius: 3px; }
.dropdown-menu::-webkit-scrollbar-thumb { background: #8B5CF6; border-radius: 3px; }
.custom-dropdown.open .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }

.dropdown-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: var(--radius); cursor: pointer; transition: all var(--transition-fast); font-size: 0.88rem; color: var(--dark); white-space: nowrap; }
[data-theme="dark"] .dropdown-item { color: var(--gray-200); }
.dropdown-item:hover { background: rgba(139,92,246,0.08); transform: translateX(4px); }
.dropdown-item.active { background: linear-gradient(135deg, rgba(139,92,246,0.15), rgba(109,40,217,0.1)); color: #8B5CF6; font-weight: 600; }
.dropdown-item i:first-child { width: 18px; font-size: 0.9rem; }
.dropdown-count { margin-left: auto; font-size: 0.72rem; color: var(--gray-500); background: var(--gray-100); padding: 2px 8px; border-radius: 10px; font-weight: 600; }
[data-theme="dark"] .dropdown-count { background: rgba(255,255,255,0.08); color: var(--gray-400); }
.dropdown-item.active .dropdown-count { background: rgba(139,92,246,0.2); color: #8B5CF6; }

/* Grid & Cards */
.testimoni-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 20px; }
.testimoni-card { position: relative; background: var(--white); border-radius: var(--radius-xl); overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; }
[data-theme="dark"] .testimoni-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.testimoni-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: rgba(139,92,246,0.3); }
.tc-header { position: relative; height: 140px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.tc-avatar { width: 90px; height: 90px; border-radius: 50%; border: 4px solid rgba(255,255,255,0.8); object-fit: cover; transition: transform 0.3s; position: relative; z-index: 1; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.testimoni-card:hover .tc-avatar { transform: scale(1.05); }
.tc-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent 70%); display: flex; align-items: flex-end; justify-content: flex-end; padding: 14px; opacity: 0; transition: opacity var(--transition); z-index: 2; }
.testimoni-card:hover .tc-overlay { opacity: 1; }
.tc-quick-actions { display: flex; gap: 8px; }
.tc-quick-actions button, .tc-quick-actions a { width: 38px; height: 38px; border-radius: var(--radius-sm); background: rgba(255,255,255,0.95); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--dark); text-decoration: none; transition: all var(--transition-fast); backdrop-filter: blur(10px); font-size: 0.9rem; }
.tc-quick-actions button:hover, .tc-quick-actions a:hover { background: #8B5CF6; color: var(--white); transform: translateY(-3px); }
.tc-quick-actions a:last-child:hover { background: #EF4444; }
.tc-body { padding: 20px; display: flex; flex-direction: column; flex: 1; }
.tc-meta { display: flex; gap: 14px; margin-bottom: 12px; flex-wrap: wrap; font-size: 0.75rem; color: var(--gray-600); }
[data-theme="dark"] .tc-meta { color: var(--gray-400); }
.tc-meta i { color: #8B5CF6; margin-right: 4px; }
.tc-name { font-size: 1.1rem; font-weight: 700; color: var(--dark); margin-bottom: 8px; line-height: 1.3; }
[data-theme="dark"] .tc-name { color: var(--white); }
.tc-quote { font-size: 0.9rem; color: var(--gray-600); margin-bottom: 14px; line-height: 1.6; font-style: italic; flex: 1; }
[data-theme="dark"] .tc-quote { color: var(--gray-400); }
.tc-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--gray-100); }
[data-theme="dark"] .tc-footer { border-color: rgba(255,255,255,0.08); }
.tc-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }

/* Sidebar Widgets */
.testimoni-sidebar { display: flex; flex-direction: column; gap: 20px; position: sticky; top: 90px; }
.widget-card { overflow: hidden; background: var(--white); border-radius: var(--radius-xl); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); }
[data-theme="dark"] .widget-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.widget-header { padding: 16px 18px; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); }
[data-theme="dark"] .widget-header { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.08); }
.widget-header h3 { font-size: 0.95rem; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 8px; margin: 0; }
[data-theme="dark"] .widget-header h3 { color: var(--white); }
.widget-header h3 i { color: #8B5CF6; }
.widget-body { padding: 16px 18px; }
.widget-empty { text-align: center; padding: 24px 12px; color: var(--gray-600); }
.widget-empty i { font-size: 2rem; color: var(--gray-300); margin-bottom: 8px; display: block; }

.status-stat-item { padding: 10px 0; border-bottom: 1px solid var(--gray-100); transition: all var(--transition-fast); }
[data-theme="dark"] .status-stat-item { border-color: rgba(255,255,255,0.05); }
.status-stat-item:last-child { border-bottom: none; }
.status-stat-item:hover { background: rgba(139,92,246,0.04); padding-left: 8px; border-radius: var(--radius); }
.ssi-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.ssi-name { font-size: 0.82rem; font-weight: 600; color: var(--dark); display: flex; align-items: center; gap: 8px; }
[data-theme="dark"] .ssi-name { color: var(--gray-200); }
.ssi-name i { font-size: 0.78rem; }
.ssi-count { font-size: 0.85rem; font-weight: 700; color: var(--dark); font-family: 'Space Grotesk', sans-serif; }
[data-theme="dark"] .ssi-count { color: var(--white); }
.ssi-bar { height: 6px; background: var(--gray-100); border-radius: 50px; overflow: hidden; }
[data-theme="dark"] .ssi-bar { background: rgba(255,255,255,0.08); }
.ssi-bar-fill { height: 100%; border-radius: 50px; animation: barFill 1s ease-out; }

.qa-btn { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--radius); background: var(--gray-50); color: var(--dark); border: none; width: 100%; text-align: left; font-size: 0.88rem; font-weight: 500; transition: all var(--transition); margin-bottom: 8px; cursor: pointer; font-family: inherit; text-decoration: none; }
[data-theme="dark"] .qa-btn { background: rgba(255,255,255,0.04); color: var(--gray-200); }
.qa-btn:hover { background: var(--gray-100); transform: translateX(4px); }
.qa-btn i:first-child { width: 16px; }
.qa-arrow { margin-left: auto; color: var(--gray-400); font-size: 0.72rem; opacity: 0; transition: opacity var(--transition-fast); }
.qa-btn:hover .qa-arrow { opacity: 1; }

/* Modal & Misc */
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
.input-group-ultimate input:focus, .input-group-ultimate select:focus, .textarea-group-ultimate textarea:focus { outline: none; border-color: #8B5CF6; background: var(--white); box-shadow: 0 0 0 4px rgba(139,92,246,0.1); }
.textarea-group-ultimate textarea { padding-left: 16px; min-height: 100px; resize: vertical; }
.char-counter { position: absolute; bottom: 10px; right: 16px; font-size: 0.75rem; color: var(--gray-500); background: var(--white); padding: 2px 8px; border-radius: 4px; }
[data-theme="dark"] .char-counter { background: var(--dark); color: var(--gray-400); }

.btn-secondary-ultimate, .btn-primary-ultimate { padding: 12px 24px; border-radius: var(--radius); font-size: 0.9rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; border: none; text-decoration: none; }
.btn-secondary-ultimate { background: var(--gray-100); color: var(--gray-700); }
[data-theme="dark"] .btn-secondary-ultimate { background: rgba(255,255,255,0.08); color: var(--gray-300); }
.btn-secondary-ultimate:hover { background: var(--gray-200); }
.btn-primary-ultimate { background: linear-gradient(135deg, #8B5CF6, #6D28D9); color: var(--white); box-shadow: 0 4px 16px rgba(139,92,246,0.4); }
.btn-primary-ultimate:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(139,92,246,0.5); color: white; }

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
    .testimoni-layout { grid-template-columns: 1fr; }
    .testimoni-sidebar { position: static; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
}
@media (max-width: 768px) {
    .testimoni-page { padding: 16px; }
    .testimoni-hero { padding: 24px 20px; }
    .hero-title { font-size: 1.6rem; }
    .title-icon-wrap { width: 44px; height: 44px; font-size: 1.2rem; }
    .hero-content { flex-direction: column; align-items: flex-start; }
    .hero-actions { width: 100%; }
    .testimoni-toolbar { flex-direction: column; align-items: stretch; }
    .toolbar-left, .toolbar-right { width: 100%; flex-direction: column; align-items: stretch; }
    .toolbar-right { flex-direction: row; justify-content: space-between; }
    .testimoni-grid { grid-template-columns: 1fr; }
    .custom-dropdown { width: 100%; }
    .form-grid-2 { grid-template-columns: 1fr; }
}
</style>

<script>
(function() {
    'use strict';

    // Custom Dropdown Logic
    const dropdown = document.getElementById('statusDropdown');
    const trigger = document.getElementById('statusTrigger');
    const menu = document.getElementById('statusMenu');
    const label = document.getElementById('statusLabel');
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

    window.filterByStatus = function(status) {
        applyFilters(status);
    };

    window.applyFilters = function(status) {
        const url = new URL(window.location);
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        window.location = url;
    };

    window.resetFilters = function() {
        const url = new URL(window.location);
        url.searchParams.delete('status');
        window.location = url;
    };

    // Modal Functions
    window.openModal = function() {
        document.getElementById('testimoniForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('avatarLama').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Testimoni';
        document.getElementById('avatarPreview').style.display = 'none';
        document.getElementById('quoteCount').textContent = '0';
        document.getElementById('testimoniModal').classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('nama')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('testimoniModal').classList.remove('open');
        document.body.style.overflow = '';
    };

    window.editTestimoni = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Testimoni';
        document.getElementById('formId').value = data.id;
        document.getElementById('nama').value = data.nama;
        document.getElementById('tahun_masuk').value = data.tahun_masuk;
        document.getElementById('quote').value = data.quote;
        document.getElementById('urutan').value = data.urutan || 0;
        document.getElementById('status').value = data.status;
        document.getElementById('avatarLama').value = data.avatar || '';
        document.getElementById('quoteCount').textContent = (data.quote || '').length;
        
        const preview = document.getElementById('avatarPreview');
        if (data.avatar) {
            preview.src = data.avatar.startsWith('http') ? data.avatar : '../' + data.avatar;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
        
        document.getElementById('testimoniModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    // Avatar Preview
    window.previewAvatar = function(input) {
        const preview = document.getElementById('avatarPreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
        }
    };

    // Quote Counter
    document.getElementById('quote')?.addEventListener('input', function() {
        document.getElementById('quoteCount').textContent = this.value.length;
    });

    // Modal Backdrop Click
    document.getElementById('testimoniModal')?.addEventListener('click', function(e) {
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

    // Auto Open Edit Modal
    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && $editData): ?>
    window.addEventListener('load', () => {
        setTimeout(() => editTestimoni(<?= json_encode($editData, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c💬 Testimoni Page Ultimate Loaded', 'color:#8B5CF6;font-weight:bold;font-size:12px');
})();
</script>

<?php include 'includes/footer.php'; ?>