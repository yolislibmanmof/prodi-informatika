<?php
require_once 'includes/auth.php';
$pageTitle = 'Data Pendaftaran';

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
            $db->prepare("DELETE FROM pendaftaran WHERE id = ?")->execute([$id]);
            setFlash('success', 'Data pendaftaran berhasil dihapus');
        } elseif (in_array($_GET['action'], ['pending', 'verified', 'accepted', 'rejected'])) {
            $db->prepare("UPDATE pendaftaran SET status = ? WHERE id = ?")->execute([$_GET['action'], $id]);
            setFlash('success', 'Status pendaftaran berhasil diperbarui');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal memproses permintaan');
    }
    header('Location: pendaftaran.php'); exit;
}

/* ===== FETCH PARAMETERS ===== */
$search = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
$filterStatus = $_GET['status'] ?? '';

$where = "1=1";
$params = [];
if ($filterStatus) { $where .= " AND status = ?"; $params[] = $filterStatus; }
if (isset($_GET['q']) && $_GET['q']) { 
    $where .= " AND (nama_lengkap LIKE ? OR email LIKE ? OR asal_sekolah LIKE ?)"; 
    $params[] = $search; $params[] = $search; $params[] = $search; 
}

/* ===== FETCH DATA ===== */
$pendaftaranList = [];
$stats = ['total' => 0, 'pending' => 0, 'accepted' => 0, 'rejected' => 0];
$jalurStats = [];

if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM pendaftaran WHERE $where ORDER BY created_at DESC");
        $stmt->execute($params);
        $pendaftaranList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM pendaftaran")->fetchColumn();
        $stats['pending'] = (int)$db->query("SELECT COUNT(*) FROM pendaftaran WHERE status='pending'")->fetchColumn();
        $stats['accepted'] = (int)$db->query("SELECT COUNT(*) FROM pendaftaran WHERE status='accepted'")->fetchColumn();
        $stats['rejected'] = (int)$db->query("SELECT COUNT(*) FROM pendaftaran WHERE status='rejected'")->fetchColumn();
        
        $jalurStats = $db->query("SELECT jalur_pendaftaran, COUNT(*) as total FROM pendaftaran GROUP BY jalur_pendaftaran ORDER BY total DESC")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== DETAIL MODE ===== */
$detailData = null;
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM pendaftaran WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $detailData = $st->fetch();
    } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content pendaftaran-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="pendaftaran-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-academic-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-user-graduate"></i>
                    <span>Admission Management</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-clipboard-list"></i>
                    </span>
                    Data Pendaftaran
                </h1>
                <p class="hero-subtitle">
                    Kelola dan verifikasi calon mahasiswa baru Program Studi Informatika
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-users"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total Pendaftar</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-clock" style="color:#F59E0B"></i>
                        <span><strong><?= $stats['pending'] ?></strong> Menunggu</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-check-circle" style="color:#10B981"></i>
                        <span><strong><?= $stats['accepted'] ?></strong> Diterima</span>
                    </div>
                </div>
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
    <div class="pendaftaran-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="pendaftaran-main-col">
            <!-- Toolbar -->
            <div class="card pendaftaran-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama, email, atau sekolah..." value="<?= clean($_GET['q'] ?? '') ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <!-- Custom Dropdown Premium -->
                    <div class="custom-dropdown" id="statusDropdown">
                        <button class="dropdown-trigger" id="dropdownTrigger">
                            <i class="fas fa-filter" style="color: <?= $filterStatus ? match($filterStatus) { 'pending' => '#F59E0B', 'verified' => '#3B82F6', 'accepted' => '#10B981', 'rejected' => '#EF4444', default => '#6B7280' } : '#6B7280' ?>"></i>
                            <span id="dropdownLabel"><?= $filterStatus ? match($filterStatus) { 'pending' => 'Pending', 'verified' => 'Terverifikasi', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', default => 'Semua Status' } : 'Semua Status' ?></span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <div class="dropdown-item <?= !$filterStatus ? 'active' : '' ?>" data-value="">
                                <i class="fas fa-th-large" style="color: #6B7280"></i>
                                <span>Semua Status</span>
                                <span class="dropdown-count"><?= $stats['total'] ?></span>
                            </div>
                            <div class="dropdown-item <?= $filterStatus === 'pending' ? 'active' : '' ?>" data-value="pending">
                                <i class="fas fa-clock" style="color: #F59E0B"></i>
                                <span>Pending</span>
                                <span class="dropdown-count"><?= $stats['pending'] ?></span>
                            </div>
                            <div class="dropdown-item <?= $filterStatus === 'verified' ? 'active' : '' ?>" data-value="verified">
                                <i class="fas fa-check" style="color: #3B82F6"></i>
                                <span>Terverifikasi</span>
                                <span class="dropdown-count"><?= count(array_filter($pendaftaranList, fn($p) => $p['status'] === 'verified')) ?></span>
                            </div>
                            <div class="dropdown-item <?= $filterStatus === 'accepted' ? 'active' : '' ?>" data-value="accepted">
                                <i class="fas fa-check-circle" style="color: #10B981"></i>
                                <span>Diterima</span>
                                <span class="dropdown-count"><?= $stats['accepted'] ?></span>
                            </div>
                            <div class="dropdown-item <?= $filterStatus === 'rejected' ? 'active' : '' ?>" data-value="rejected">
                                <i class="fas fa-times-circle" style="color: #EF4444"></i>
                                <span>Ditolak</span>
                                <span class="dropdown-count"><?= $stats['rejected'] ?></span>
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

            <!-- Pendaftaran Cards Grid -->
            <div class="pendaftaran-grid" id="pendaftaranGrid">
                <?php if (!empty($pendaftaranList)): ?>
                    <?php foreach ($pendaftaranList as $p): 
                        $statusConfig = [
                            'pending' => ['#F59E0B', '#D97706', 'clock', 'draft'],
                            'verified' => ['#3B82F6', '#1E40AF', 'check', 'published'],
                            'accepted' => ['#10B981', '#047857', 'check-circle', 'published'],
                            'rejected' => ['#EF4444', '#B91C1C', 'times-circle', 'draft']
                        ];
                        $cfg = $statusConfig[$p['status']] ?? $statusConfig['pending'];
                    ?>
                    <div class="pendaftaran-card" data-id="<?= $p['id'] ?>">
                        <div class="pc-header" style="background: linear-gradient(135deg, <?= $cfg[0] ?>22, <?= $cfg[1] ?>11)">
                            <i class="fas fa-user-graduate" style="font-size: 2.5rem; color: <?= $cfg[0] ?>; opacity: 0.3;"></i>
                            <div class="pc-overlay">
                                <div class="pc-quick-actions">
                                    <a href="?action=view&id=<?= $p['id'] ?>" title="Lihat Detail"><i class="fas fa-eye"></i></a>
                                    <?php if ($p['status'] !== 'accepted'): ?>
                                    <a href="?action=accepted&id=<?= $p['id'] ?>" title="Terima" data-confirm="Terima pendaftar ini?"><i class="fas fa-check"></i></a>
                                    <?php endif; ?>
                                    <?php if ($p['status'] !== 'rejected'): ?>
                                    <a href="?action=rejected&id=<?= $p['id'] ?>" title="Tolak" data-confirm="Tolak pendaftar ini?"><i class="fas fa-times"></i></a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?= $p['id'] ?>" title="Hapus" data-confirm="Yakin hapus data ini?"><i class="fas fa-trash"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="pc-body">
                            <div class="pc-meta">
                                <span><i class="fas fa-calendar"></i> <?= function_exists('formatTanggal') ? formatTanggal($p['created_at']) : date('d M Y', strtotime($p['created_at'])) ?></span>
                                <span><i class="fas fa-route"></i> <?= clean(ucfirst($p['jalur_pendaftaran'] ?? 'Reguler')) ?></span>
                            </div>
                            <h3 class="pc-title"><?= clean($p['nama_lengkap']) ?></h3>
                            <p class="pc-excerpt"><?= clean($p['asal_sekolah'] ?: 'Asal sekolah tidak diisi') ?></p>
                            <div class="pc-footer">
                                <span class="pc-badge" style="background: <?= $cfg[0] ?>15; color: <?= $cfg[0] ?>">
                                    <i class="fas fa-<?= $cfg[2] ?>"></i> <?= ucfirst($p['status']) ?>
                                </span>
                                <span class="pc-author"><i class="fas fa-envelope"></i> <?= clean($p['email']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration"><i class="fas fa-user-graduate"></i></div>
                        <h3>Belum ada pendaftar</h3>
                        <p>Data pendaftaran calon mahasiswa akan muncul di sini</p>
                        <div class="empty-actions">
                            <button class="btn btn-secondary" onclick="resetFilters()"><i class="fas fa-redo"></i> Reset Filter</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="pendaftaran-sidebar">
            <!-- Jalur Stats -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-pie"></i> Statistik Jalur</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($jalurStats)): 
                        $maxJalur = max(array_column($jalurStats, 'total'));
                        $jalurColors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'];
                    ?>
                        <?php foreach ($jalurStats as $index => $jl): 
                            $pct = round(($jl['total'] / max($maxJalur, 1)) * 100);
                            $color = $jalurColors[$index % count($jalurColors)];
                        ?>
                        <div class="jalur-stat-item">
                            <div class="jsi-head">
                                <span class="jsi-name" style="color: <?= $color ?>">
                                    <i class="fas fa-route"></i> <?= clean(ucfirst($jl['jalur_pendaftaran'])) ?>
                                </span>
                                <span class="jsi-count"><?= $jl['total'] ?></span>
                            </div>
                            <div class="jsi-bar">
                                <div class="jsi-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty"><i class="fas fa-chart-pie"></i><p>Belum ada data jalur</p></div>
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
                    <button class="qa-btn" onclick="filterByStatus('accepted')">
                        <i class="fas fa-check-circle" style="color:#10B981"></i>
                        <span>Lihat yang Diterima</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </button>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== DETAIL MODAL ===== -->
<?php if ($detailData): ?>
<div class="modal-ultimate open" id="detailModal">
    <div class="modal-content-ultimate" style="max-width:700px;">
        <div class="modal-header-ultimate" style="background: linear-gradient(135deg, #3B82F6, #1E40AF); color: white;">
            <div class="modal-header-content">
                <div class="modal-icon" style="background: rgba(255,255,255,0.2);"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <h3 style="color: white;">Detail Pendaftaran #<?= str_pad($detailData['id'], 5, '0', STR_PAD_LEFT) ?></h3>
                    <p style="color: rgba(255,255,255,0.8); margin:0;"><?= clean($detailData['nama_lengkap']) ?></p>
                </div>
            </div>
            <a href="pendaftaran.php" class="modal-close-ultimate" style="background: rgba(255,255,255,0.2); color: white;"><i class="fas fa-times"></i></a>
        </div>
        
        <div class="modal-body-ultimate">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
                <div class="form-group-ultimate">
                    <label>Nama Lengkap</label>
                    <div class="input-group-ultimate" style="background:var(--gray-50); padding:12px 14px; border-radius:10px; font-weight:500; border: 1px solid var(--gray-200);">
                        <?= clean($detailData['nama_lengkap']) ?>
                    </div>
                </div>
                <div class="form-group-ultimate">
                    <label>Email</label>
                    <div class="input-group-ultimate" style="background:var(--gray-50); padding:12px 14px; border-radius:10px; border: 1px solid var(--gray-200);">
                        <a href="mailto:<?= clean($detailData['email']) ?>" style="color:var(--primary); text-decoration:none;"><?= clean($detailData['email']) ?></a>
                    </div>
                </div>
                <div class="form-group-ultimate">
                    <label>Telepon</label>
                    <div class="input-group-ultimate" style="background:var(--gray-50); padding:12px 14px; border-radius:10px; border: 1px solid var(--gray-200);">
                        <?= clean($detailData['telepon']) ?: '-' ?>
                    </div>
                </div>
                <div class="form-group-ultimate">
                    <label>Asal Sekolah</label>
                    <div class="input-group-ultimate" style="background:var(--gray-50); padding:12px 14px; border-radius:10px; border: 1px solid var(--gray-200);">
                        <?= clean($detailData['asal_sekolah']) ?: '-' ?>
                    </div>
                </div>
                <div class="form-group-ultimate">
                    <label>Jurusan Sekolah</label>
                    <div class="input-group-ultimate" style="background:var(--gray-50); padding:12px 14px; border-radius:10px; border: 1px solid var(--gray-200);">
                        <?= clean($detailData['jurusan_sekolah']) ?: '-' ?>
                    </div>
                </div>
                <div class="form-group-ultimate">
                    <label>Nilai Rata-rata</label>
                    <div class="input-group-ultimate" style="background:var(--gray-50); padding:12px 14px; border-radius:10px; font-weight:600; color:var(--primary); border: 1px solid var(--gray-200);">
                        <?= $detailData['nilai_rata_rata'] ?: '-' ?>
                    </div>
                </div>
            </div>
            
            <div class="form-group-ultimate" style="margin-bottom:20px;">
                <label>Alamat Lengkap</label>
                <div class="textarea-group-ultimate" style="background:var(--gray-50); padding:16px; border-radius:10px; line-height:1.6; border: 1px solid var(--gray-200);">
                    <?= clean($detailData['alamat']) ?: '-' ?>
                </div>
            </div>
            
            <div class="form-group-ultimate">
                <label>Status Saat Ini</label>
                <div class="input-group-ultimate" style="background:var(--gray-50); padding:12px 14px; border-radius:10px; font-weight:600; text-transform:capitalize; border: 1px solid var(--gray-200);">
                    <i class="fas fa-<?= match($detailData['status']) { 'pending' => 'clock', 'verified' => 'check', 'accepted' => 'check-circle', 'rejected' => 'times-circle', default => 'clock' } ?>" style="margin-right:8px; color:var(--primary);"></i>
                    <?= ucfirst($detailData['status']) ?>
                </div>
            </div>
        </div>
        
        <div class="modal-footer-ultimate">
            <a href="pendaftaran.php" class="btn-secondary-ultimate">Tutup</a>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="?action=pending&id=<?= $detailData['id'] ?>" class="btn-secondary-ultimate" style="background:#FEF3C7; color:#92400E; border-color:#FCD34D;"><i class="fas fa-clock"></i> Pending</a>
                <a href="?action=verified&id=<?= $detailData['id'] ?>" class="btn-secondary-ultimate" style="background:#DBEAFE; color:#1E40AF; border-color:#93C5FD;"><i class="fas fa-check"></i> Verifikasi</a>
                <a href="?action=accepted&id=<?= $detailData['id'] ?>" class="btn-primary-ultimate"><i class="fas fa-check-circle"></i> Terima</a>
                <a href="?action=rejected&id=<?= $detailData['id'] ?>" class="btn-secondary-ultimate" style="background:#FEE2E2; color:#991B1B; border-color:#FCA5A5;"><i class="fas fa-times"></i> Tolak</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* ===== PENDAFTARAN PAGE ULTIMATE v4.0 ===== */
.pendaftaran-page { padding: var(--spacing-lg); animation: fadeIn 0.4s ease; }

/* Hero */
.pendaftaran-hero {
    position: relative; padding: 36px 40px; margin-bottom: 28px;
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 50%, #1E40AF 100%);
    border-radius: var(--radius-2xl); overflow: hidden; box-shadow: var(--shadow-xl);
}
.hero-bg-effects { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.hero-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.5; animation: orbFloat 20s infinite ease-in-out; }
.orb-1 { width: 320px; height: 320px; background: radial-gradient(circle, #93C5FD, transparent); top: -120px; right: 8%; }
.orb-2 { width: 220px; height: 220px; background: radial-gradient(circle, #60A5FA, transparent); bottom: -100px; left: 15%; animation-delay: -7s; }
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
.hms-item i { color: #DBEAFE; }
.hms-item strong { font-family: 'Space Grotesk', sans-serif; font-weight: 700; }

/* Layout */
.pendaftaran-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.pendaftaran-main-col { min-width: 0; }

/* Toolbar */
.pendaftaran-toolbar { padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; overflow: visible !important; }
.toolbar-left { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; flex: 1; min-width: 0; overflow: visible !important; }
.toolbar-search { position: relative; flex: 1; min-width: 240px; max-width: 380px; }
.toolbar-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.9rem; pointer-events: none; }
.toolbar-search input { width: 100%; padding: 10px 44px 10px 42px; border: 1.5px solid var(--gray-200); border-radius: var(--radius); font-size: 0.88rem; font-family: inherit; background: var(--gray-50); transition: all var(--transition); }
[data-theme="dark"] .toolbar-search input { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.toolbar-search input:focus { outline: none; border-color: #3B82F6; background: var(--white); box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }
.search-kbd { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); padding: 2px 8px; background: var(--gray-200); color: var(--gray-600); border-radius: 4px; font-size: 0.7rem; font-weight: 600; font-family: 'Courier New', monospace; pointer-events: none; }

/* Custom Dropdown Premium */
.custom-dropdown { 
    position: relative; 
    min-width: 200px; 
    z-index: 1000; 
    overflow: visible !important; 
}
.dropdown-trigger {
    display: flex; align-items: center; gap: 10px; padding: 10px 16px;
    background: var(--white); border: 1.5px solid var(--gray-200); border-radius: var(--radius);
    font-size: 0.88rem; font-weight: 600; color: var(--dark); cursor: pointer;
    transition: all var(--transition); width: 100%; text-align: left; font-family: inherit;
}
[data-theme="dark"] .dropdown-trigger { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.dropdown-trigger:hover { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
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
.dropdown-menu::-webkit-scrollbar-track { background: rgba(59,130,246,0.05); border-radius: 3px; }
.dropdown-menu::-webkit-scrollbar-thumb { background: #3B82F6; border-radius: 3px; }
.custom-dropdown.open .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }

.dropdown-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: var(--radius); cursor: pointer; transition: all var(--transition-fast); font-size: 0.88rem; color: var(--dark); white-space: nowrap; }
[data-theme="dark"] .dropdown-item { color: var(--gray-200); }
.dropdown-item:hover { background: rgba(59,130,246,0.08); transform: translateX(4px); }
.dropdown-item.active { background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(37,99,235,0.1)); color: #3B82F6; font-weight: 600; }
.dropdown-item i:first-child { width: 18px; font-size: 0.9rem; }
.dropdown-count { margin-left: auto; font-size: 0.72rem; color: var(--gray-500); background: var(--gray-100); padding: 2px 8px; border-radius: 10px; font-weight: 600; }
[data-theme="dark"] .dropdown-count { background: rgba(255,255,255,0.08); color: var(--gray-400); }
.dropdown-item.active .dropdown-count { background: rgba(59,130,246,0.2); color: #3B82F6; }

.toolbar-filters { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.toolbar-right { display: flex; gap: 10px; align-items: center; }
.toolbar-icon-btn { width: 40px; height: 40px; border-radius: var(--radius); border: 1.5px solid var(--gray-200); background: var(--white); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--gray-600); transition: all var(--transition); font-size: 0.9rem; }
[data-theme="dark"] .toolbar-icon-btn { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--gray-300); }
.toolbar-icon-btn:hover { background: #3B82F6; color: var(--white); border-color: #3B82F6; transform: translateY(-2px); }

/* Grid & Cards */
.pendaftaran-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 20px; }
.pendaftaran-card { position: relative; background: var(--white); border-radius: var(--radius-xl); overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; }
[data-theme="dark"] .pendaftaran-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.pendaftaran-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: rgba(59,130,246,0.3); }
.pc-header { position: relative; height: 140px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.pc-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent 70%); display: flex; align-items: flex-end; justify-content: flex-end; padding: 14px; opacity: 0; transition: opacity var(--transition); z-index: 2; }
.pendaftaran-card:hover .pc-overlay { opacity: 1; }
.pc-quick-actions { display: flex; gap: 8px; }
.pc-quick-actions a { width: 38px; height: 38px; border-radius: var(--radius-sm); background: rgba(255,255,255,0.95); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--dark); text-decoration: none; transition: all var(--transition-fast); backdrop-filter: blur(10px); font-size: 0.9rem; }
.pc-quick-actions a:hover { background: #3B82F6; color: var(--white); transform: translateY(-3px); }
.pc-quick-actions a:last-child:hover { background: #EF4444; }
.pc-body { padding: 20px; display: flex; flex-direction: column; flex: 1; }
.pc-meta { display: flex; gap: 14px; margin-bottom: 12px; flex-wrap: wrap; font-size: 0.75rem; color: var(--gray-600); }
[data-theme="dark"] .pc-meta { color: var(--gray-400); }
.pc-meta i { color: #3B82F6; margin-right: 4px; }
.pc-title { font-size: 1.1rem; font-weight: 700; color: var(--dark); margin-bottom: 8px; line-height: 1.3; flex: 1; }
[data-theme="dark"] .pc-title { color: var(--white); }
.pc-excerpt { font-size: 0.85rem; color: var(--gray-600); margin-bottom: 14px; line-height: 1.5; flex: 1; }
[data-theme="dark"] .pc-excerpt { color: var(--gray-400); }
.pc-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--gray-100); }
[data-theme="dark"] .pc-footer { border-color: rgba(255,255,255,0.08); }
.pc-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
.pc-author { font-size: 0.75rem; color: var(--gray-600); display: flex; align-items: center; gap: 4px; }
[data-theme="dark"] .pc-author { color: var(--gray-400); }

/* Sidebar Widgets */
.pendaftaran-sidebar { display: flex; flex-direction: column; gap: 20px; position: sticky; top: 90px; }
.widget-card { overflow: hidden; background: var(--white); border-radius: var(--radius-xl); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); }
[data-theme="dark"] .widget-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.widget-header { padding: 16px 18px; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); }
[data-theme="dark"] .widget-header { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.08); }
.widget-header h3 { font-size: 0.95rem; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 8px; margin: 0; }
[data-theme="dark"] .widget-header h3 { color: var(--white); }
.widget-header h3 i { color: #3B82F6; }
.widget-body { padding: 16px 18px; }
.widget-empty { text-align: center; padding: 24px 12px; color: var(--gray-600); }
.widget-empty i { font-size: 2rem; color: var(--gray-300); margin-bottom: 8px; display: block; }

.jalur-stat-item { padding: 10px 0; border-bottom: 1px solid var(--gray-100); transition: all var(--transition-fast); }
[data-theme="dark"] .jalur-stat-item { border-color: rgba(255,255,255,0.05); }
.jalur-stat-item:last-child { border-bottom: none; }
.jalur-stat-item:hover { background: rgba(59,130,246,0.04); padding-left: 8px; border-radius: var(--radius); }
.jsi-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.jsi-name { font-size: 0.82rem; font-weight: 600; color: var(--dark); display: flex; align-items: center; gap: 8px; }
[data-theme="dark"] .jsi-name { color: var(--gray-200); }
.jsi-name i { font-size: 0.78rem; }
.jsi-count { font-size: 0.85rem; font-weight: 700; color: var(--dark); font-family: 'Space Grotesk', sans-serif; }
[data-theme="dark"] .jsi-count { color: var(--white); }
.jsi-bar { height: 6px; background: var(--gray-100); border-radius: 50px; overflow: hidden; }
[data-theme="dark"] .jsi-bar { background: rgba(255,255,255,0.08); }
.jsi-bar-fill { height: 100%; border-radius: 50px; animation: barFill 1s ease-out; }

.qa-btn { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--radius); background: var(--gray-50); color: var(--dark); border: none; width: 100%; text-align: left; font-size: 0.88rem; font-weight: 500; transition: all var(--transition); margin-bottom: 8px; cursor: pointer; font-family: inherit; }
[data-theme="dark"] .qa-btn { background: rgba(255,255,255,0.04); color: var(--gray-200); }
.qa-btn:hover { background: var(--gray-100); transform: translateX(4px); }
.qa-btn i:first-child { width: 16px; }
.qa-arrow { margin-left: auto; color: var(--gray-400); font-size: 0.72rem; opacity: 0; transition: opacity var(--transition-fast); }
.qa-btn:hover .qa-arrow { opacity: 1; }

/* Modal & Misc */
.modal-ultimate { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 20px; animation: fadeIn 0.3s; }
.modal-ultimate.open { display: flex; }
.modal-content-ultimate { background: var(--white); border-radius: var(--radius-2xl); width: 100%; max-width: 700px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; animation: modalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 30px 60px rgba(0,0,0,0.3); }
[data-theme="dark"] .modal-content-ultimate { background: var(--dark-2); }
@keyframes modalIn { from { opacity: 0; transform: scale(0.9) translateY(20px); } to { opacity: 1; transform: none; } }
.modal-header-ultimate { display: flex; justify-content: space-between; align-items: center; padding: 24px 28px; }
.modal-header-content { display: flex; align-items: center; gap: 16px; }
.modal-icon { width: 50px; height: 50px; border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 1.3rem; flex-shrink: 0; }
.modal-header-ultimate h3 { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; }
.modal-close-ultimate { width: 40px; height: 40px; border-radius: var(--radius); border: none; cursor: pointer; transition: all var(--transition-fast); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; text-decoration: none; }
.modal-close-ultimate:hover { background: rgba(255,255,255,0.3); transform: rotate(90deg); }
.modal-body-ultimate { flex: 1; overflow-y: auto; padding: 28px; }
.modal-footer-ultimate { display: flex; justify-content: space-between; align-items: center; padding: 20px 28px; border-top: 1px solid var(--gray-100); background: var(--gray-50); flex-wrap: wrap; gap: 10px; }
[data-theme="dark"] .modal-footer-ultimate { border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.2); }

.form-group-ultimate { display: flex; flex-direction: column; gap: 6px; }
.form-group-ultimate label { font-size: 0.82rem; font-weight: 600; color: var(--gray-700); }
[data-theme="dark"] .form-group-ultimate label { color: var(--gray-300); }

.btn-secondary-ultimate, .btn-primary-ultimate { padding: 12px 24px; border-radius: var(--radius); font-size: 0.9rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; border: none; text-decoration: none; }
.btn-secondary-ultimate { background: var(--gray-100); color: var(--gray-700); }
[data-theme="dark"] .btn-secondary-ultimate { background: rgba(255,255,255,0.08); color: var(--gray-300); }
.btn-secondary-ultimate:hover { background: var(--gray-200); }
.btn-primary-ultimate { background: linear-gradient(135deg, #10B981, #047857); color: var(--white); box-shadow: 0 4px 16px rgba(16,185,129,0.4); }
.btn-primary-ultimate:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(16,185,129,0.5); color: white; }

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
    .pendaftaran-layout { grid-template-columns: 1fr; }
    .pendaftaran-sidebar { position: static; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
}
@media (max-width: 768px) {
    .pendaftaran-page { padding: 16px; }
    .pendaftaran-hero { padding: 24px 20px; }
    .hero-title { font-size: 1.6rem; }
    .title-icon-wrap { width: 44px; height: 44px; font-size: 1.2rem; }
    .hero-content { flex-direction: column; align-items: flex-start; }
    .pendaftaran-toolbar { flex-direction: column; align-items: stretch; }
    .toolbar-left, .toolbar-right { width: 100%; flex-direction: column; align-items: stretch; }
    .toolbar-search { max-width: 100%; }
    .toolbar-right { flex-direction: row; justify-content: space-between; }
    .pendaftaran-grid { grid-template-columns: 1fr; }
    .modal-footer-ultimate { flex-direction: column; }
    .custom-dropdown { width: 100%; }
}
</style>

<script>
(function() {
    'use strict';

    // Custom Dropdown Logic
    const dropdown = document.getElementById('statusDropdown');
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
                const text = this.querySelector('span').textContent;
                const icon = this.querySelector('i');
                
                // Update label
                label.textContent = text;
                trigger.querySelector('i:first-child').style.color = icon.style.color;
                
                // Update active state
                items.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                // Close dropdown
                dropdown.classList.remove('open');
                
                // Apply filter
                applyFilters(value);
            });
        });
    }

    // Search & Filter Logic
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 600);
        });
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                applyFilters();
            }
        });
    }

    window.filterByStatus = function(status) {
        applyFilters(status);
    };

    window.applyFilters = function(status) {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        window.location = url;
    };

    window.resetFilters = function() {
        const url = new URL(window.location);
        url.searchParams.delete('q');
        url.searchParams.delete('status');
        window.location = url;
    };

    // Modal Backdrop Click
    const detailModal = document.getElementById('detailModal');
    if (detailModal) {
        detailModal.addEventListener('click', function(e) {
            if (e.target === this) {
                window.location.href = 'pendaftaran.php';
            }
        });
    }

    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'all 0.4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 400);
        });
    }, 5000);

    console.log('%c Pendaftaran Page Ultimate Loaded', 'color:#3B82F6;font-weight:bold;font-size:12px');
})();
</script>

<?php include 'includes/footer.php'; ?>