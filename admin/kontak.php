<?php
require_once 'includes/auth.php';
$pageTitle = 'Pesan Kontak';

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
            $db->prepare("DELETE FROM kontak WHERE id = ?")->execute([$id]);
            setFlash('success', 'Pesan berhasil dihapus');
            header('Location: kontak.php'); exit;
        }
        if (in_array($_GET['action'], ['dibaca', 'dibalas']) && $id) {
            $db->prepare("UPDATE kontak SET status = ? WHERE id = ?")->execute([$_GET['action'], $id]);
            setFlash('success', 'Status pesan berhasil diperbarui');
            header('Location: kontak.php'); exit;
        }
        
        // Bulk actions
        if (in_array($_GET['action'], ['bulk-dibaca', 'bulk-dibalas', 'bulk-delete']) && !empty($_GET['ids'])) {
            $ids = array_map('intval', explode(',', $_GET['ids']));
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            if ($_GET['action'] === 'bulk-delete') {
                $db->prepare("DELETE FROM kontak WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' pesan berhasil dihapus');
            } elseif ($_GET['action'] === 'bulk-dibaca') {
                $db->prepare("UPDATE kontak SET status = 'dibaca' WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' pesan ditandai telah dibaca');
            } elseif ($_GET['action'] === 'bulk-dibalas') {
                $db->prepare("UPDATE kontak SET status = 'dibalas' WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' pesan ditandai telah dibalas');
            }
            header('Location: kontak.php'); exit;
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal memproses permintaan');
        header('Location: kontak.php'); exit;
    }
}

/* ===== FETCH PARAMETERS ===== */
$search = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
$filterStatus = $_GET['status'] ?? '';

$where = "1=1";
$params = [];
if ($filterStatus) { $where .= " AND status = ?"; $params[] = $filterStatus; }
if (isset($_GET['q']) && $_GET['q']) { 
    $where .= " AND (nama LIKE ? OR email LIKE ? OR subjek LIKE ?)"; 
    $params[] = $search; $params[] = $search; $params[] = $search; 
}

/* ===== FETCH DATA ===== */
$kontakList = [];
$stats = ['total' => 0, 'baru' => 0, 'dibaca' => 0, 'dibalas' => 0];

if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM kontak WHERE $where ORDER BY created_at DESC");
        $stmt->execute($params);
        $kontakList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM kontak")->fetchColumn();
        $stats['baru'] = (int)$db->query("SELECT COUNT(*) FROM kontak WHERE status='baru'")->fetchColumn();
        $stats['dibaca'] = (int)$db->query("SELECT COUNT(*) FROM kontak WHERE status='dibaca'")->fetchColumn();
        $stats['dibalas'] = (int)$db->query("SELECT COUNT(*) FROM kontak WHERE status='dibalas'")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== STATUS DISTRIBUTION ===== */
$statusStats = [];
if ($db) {
    try {
        $statusStats = $db->query("SELECT status, COUNT(*) as total FROM kontak GROUP BY status ORDER BY FIELD(status, 'baru', 'dibaca', 'dibalas')")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== RECENT MESSAGES ===== */
$recentMessages = [];
if ($db) {
    try {
        $recentMessages = $db->query("SELECT id, nama, subjek, created_at FROM kontak WHERE status='baru' ORDER BY created_at DESC LIMIT 5")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== DETAIL VIEW ===== */
$detailData = null;
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM kontak WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $detailData = $st->fetch();
        
        // Auto update status to 'dibaca' if it's 'baru'
        if ($detailData && $detailData['status'] === 'baru') {
            $db->prepare("UPDATE kontak SET status = 'dibaca' WHERE id = ?")->execute([(int)$_GET['id']]);
            $detailData['status'] = 'dibaca'; // Update local object
        }
    } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content kontak-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="kontak-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-mail-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-inbox"></i>
                    <span>Inbox Management</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-envelope-open-text"></i>
                    </span>
                    Pesan Kontak
                </h1>
                <p class="hero-subtitle">
                    Kelola dan respon pesan yang masuk dari pengunjung website
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-envelope"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total Pesan</span>
                    </div>
                    <div class="hms-item live">
                        <span class="live-dot"></span>
                        <span><strong><?= $stats['baru'] ?></strong> Pesan Baru</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-eye" style="color:#3B82F6"></i>
                        <span><strong><?= $stats['dibaca'] ?></strong> Dibaca</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-check-circle" style="color:#10B981"></i>
                        <span><strong><?= $stats['dibalas'] ?></strong> Dibalas</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportKontak()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-ghost" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
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
    <div class="kontak-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="kontak-main-col">
            <!-- Toolbar -->
            <div class="card kontak-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama, email, atau subjek..." value="<?= clean($_GET['q'] ?? '') ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterStatus" class="toolbar-select">
                            <option value="">📊 Semua Status</option>
                            <option value="baru" <?= $filterStatus === 'baru' ? 'selected' : '' ?>>🔴 Baru</option>
                            <option value="dibaca" <?= $filterStatus === 'dibaca' ? 'selected' : '' ?>>👁️ Dibaca</option>
                            <option value="dibalas" <?= $filterStatus === 'dibalas' ? 'selected' : '' ?>>✅ Dibalas</option>
                        </select>
                    </div>
                </div>
                
                <div class="toolbar-right">
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
                    <span><strong id="selectedCount">0</strong> pesan dipilih</span>
                </div>
                <div class="bulk-actions">
                    <button class="btn btn-sm btn-info" onclick="bulkAction('bulk-dibaca')">
                        <i class="fas fa-eye"></i> Tandai Dibaca
                    </button>
                    <button class="btn btn-sm btn-success" onclick="bulkAction('bulk-dibalas')">
                        <i class="fas fa-check"></i> Tandai Dibalas
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('bulk-delete')">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        Batal
                    </button>
                </div>
            </div>

            <!-- Pesan Cards Grid -->
            <div class="kontak-grid" id="kontakGrid" data-view="grid">
                <?php if (!empty($kontakList)): ?>
                    <?php foreach ($kontakList as $k): 
                        $statusColors = [
                            'baru' => ['#F59E0B', '#D97706', 'fa-envelope'],
                            'dibaca' => ['#3B82F6', '#1E40AF', 'fa-eye'],
                            'dibalas' => ['#10B981', '#047857', 'fa-check-circle']
                        ];
                        $sColor = $statusColors[$k['status']] ?? $statusColors['baru'];
                        $badgeClass = $k['status'] === 'baru' ? 'draft' : 'published';
                    ?>
                    <div class="kontak-card <?= $k['status'] === 'baru' ? 'is-new' : '' ?>" data-id="<?= $k['id'] ?>">
                        <!-- Checkbox -->
                        <label class="card-checkbox">
                            <input type="checkbox" class="kontak-check" value="<?= $k['id'] ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <!-- Card Header -->
                        <div class="kc-header" onclick="viewDetail(<?= $k['id'] ?>)" style="background: linear-gradient(135deg, <?= $sColor[0] ?>22, <?= $sColor[1] ?>11)">
                            <i class="fas <?= $sColor[2] ?>" style="font-size: 2.5rem; color: <?= $sColor[0] ?>; opacity: 0.3;"></i>
                            <div class="kc-overlay">
                                <div class="kc-quick-actions" onclick="event.stopPropagation()">
                                    <a href="?action=view&id=<?= $k['id'] ?>" title="Lihat Detail"><i class="fas fa-eye"></i></a>
                                    <?php if ($k['status'] !== 'dibalas'): ?>
                                    <a href="?action=dibalas&id=<?= $k['id'] ?>" title="Tandai Dibalas"><i class="fas fa-check"></i></a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?= $k['id'] ?>" title="Hapus" data-confirm="Yakin hapus pesan ini?"><i class="fas fa-trash"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="kc-body">
                            <div class="kc-meta">
                                <span><i class="fas fa-calendar"></i> <?= function_exists('formatTanggal') ? formatTanggal($k['created_at']) : date('d M Y', strtotime($k['created_at'])) ?></span>
                            </div>
                            <h3 class="kc-title"><?= clean($k['nama']) ?></h3>
                            <p class="kc-subject" style="font-weight:600; color:var(--dark); margin-bottom:8px;"><?= clean($k['subjek']) ?></p>
                            <p class="kc-excerpt"><?= clean(mb_substr($k['pesan'], 0, 80)) ?><?= strlen($k['pesan']) > 80 ? '...' : '' ?></p>
                            
                            <div class="kc-footer">
                                <span class="kc-badge badge-<?= $badgeClass ?>">
                                    <i class="fas fa-<?= $sColor[2] ?>"></i>
                                    <?= ucfirst($k['status']) ?>
                                </span>
                                <span class="kc-author"><i class="fas fa-envelope" style="margin-right:4px;"></i> <?= clean($k['email']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <h3>Kotak masuk bersih</h3>
                        <p>Tidak ada pesan yang sesuai dengan filter Anda, atau memang belum ada pesan masuk.</p>
                        <div class="empty-actions">
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Table View -->
            <?php if (!empty($kontakList)): ?>
            <div class="kontak-table-view" id="kontakTable" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="selectAllTable"></th>
                                    <th>Status</th>
                                    <th>Pengirim</th>
                                    <th>Subjek</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kontakList as $k): 
                                    $sColor = $statusColors[$k['status']] ?? $statusColors['baru'];
                                    $badgeClass = $k['status'] === 'baru' ? 'draft' : 'published';
                                ?>
                                <tr class="<?= $k['status'] === 'baru' ? 'new-row' : '' ?>">
                                    <td><input type="checkbox" class="kontak-check" value="<?= $k['id'] ?>"></td>
                                    <td>
                                        <span class="badge badge-<?= $badgeClass ?>">
                                            <i class="fas fa-<?= $sColor[2] ?>"></i> <?= ucfirst($k['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= clean($k['nama']) ?></strong>
                                        <div style="font-size:0.75rem;color:var(--gray-500)"><?= clean($k['email']) ?></div>
                                    </td>
                                    <td><?= clean($k['subjek']) ?></td>
                                    <td><?= function_exists('formatTanggal') ? formatTanggal($k['created_at']) : date('d M Y', strtotime($k['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?action=view&id=<?= $k['id'] ?>" class="btn-icon btn-sm" title="Lihat"><i class="fas fa-eye"></i></a>
                                            <?php if ($k['status'] !== 'dibalas'): ?>
                                            <a href="?action=dibalas&id=<?= $k['id'] ?>" class="btn-icon btn-sm" title="Dibalas"><i class="fas fa-check"></i></a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?= $k['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus pesan ini?" title="Hapus"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="kontak-sidebar">
            <!-- Status Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribusi Status</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($statusStats)): 
                        $maxStat = max(array_column($statusStats, 'total'));
                        $statColors = ['baru' => '#F59E0B', 'dibaca' => '#3B82F6', 'dibalas' => '#10B981'];
                    ?>
                        <?php foreach ($statusStats as $ss): 
                            $pct = round(($ss['total'] / max($maxStat, 1)) * 100);
                            $color = $statColors[$ss['status']] ?? '#6B7280';
                        ?>
                        <div class="status-stat-item">
                            <div class="ssi-head">
                                <span class="ssi-name" style="color: <?= $color ?>">
                                    <i class="fas fa-<?= $statusColors[$ss['status']][2] ?>"></i>
                                    <?= ucfirst($ss['status']) ?>
                                </span>
                                <span class="ssi-count"><?= $ss['total'] ?></span>
                            </div>
                            <div class="ssi-bar">
                                <div class="ssi-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-chart-pie"></i>
                            <p>Belum ada data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Messages -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-clock"></i> Pesan Baru</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($recentMessages)): ?>
                        <?php foreach ($recentMessages as $rm): ?>
                        <div class="recent-item" onclick="viewDetail(<?= $rm['id'] ?>)">
                            <div class="ri-icon" style="background: rgba(245,158,11,0.12); color: #F59E0B">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="ri-info">
                                <div class="ri-name"><?= clean($rm['nama']) ?></div>
                                <div class="ri-meta"><?= clean(mb_substr($rm['subjek'], 0, 25)) ?>...</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-check-circle"></i>
                            <p>Semua pesan sudah dibaca</p>
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
                    <button class="qa-btn" onclick="document.getElementById('filterStatus').value='baru'; applyFilters();">
                        <i class="fas fa-envelope" style="color:#F59E0B"></i>
                        <span>Lihat Pesan Baru</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </button>
                    <button class="qa-btn" onclick="document.getElementById('filterStatus').value='dibalas'; applyFilters();">
                        <i class="fas fa-check-circle" style="color:#10B981"></i>
                        <span>Lihat Yang Dibalas</span>
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
                <div class="modal-icon" style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <div>
                    <h3 style="color: white;">Detail Pesan</h3>
                    <p style="color: rgba(255,255,255,0.8); margin:0;">Dari: <?= clean($detailData['nama']) ?></p>
                </div>
            </div>
            <a href="kontak.php" class="modal-close-ultimate" style="background: rgba(255,255,255,0.2); color: white;"><i class="fas fa-times"></i></a>
        </div>
        
        <div class="modal-body-ultimate">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:24px; padding:16px; background:var(--gray-50); border-radius:var(--radius);">
                <div>
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--gray-500);margin-bottom:4px">Email Pengirim</div>
                    <a href="mailto:<?= clean($detailData['email']) ?>" style="color:var(--primary);text-decoration:none;font-weight:600"><?= clean($detailData['email']) ?></a>
                </div>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--gray-500);margin-bottom:4px">Telepon</div>
                    <div style="font-weight:600"><?= clean($detailData['telepon']) ?: '-' ?></div>
                </div>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--gray-500);margin-bottom:4px">Tanggal Dikirim</div>
                    <div style="font-weight:600"><?= function_exists('formatTanggal') ? formatTanggal($detailData['created_at']) : date('d M Y H:i', strtotime($detailData['created_at'])) ?></div>
                </div>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--gray-500);margin-bottom:4px">Status Saat Ini</div>
                    <span class="badge badge-<?= $detailData['status'] === 'baru' ? 'draft' : 'published' ?>">
                        <?= ucfirst($detailData['status']) ?>
                    </span>
                </div>
            </div>
            
            <div class="form-group-ultimate">
                <label>Subjek</label>
                <div style="background:var(--gray-50); padding:14px; border-radius:var(--radius); font-weight:600; color:var(--dark); border:1px solid var(--gray-200);">
                    <?= clean($detailData['subjek']) ?>
                </div>
            </div>
            
            <div class="form-group-ultimate">
                <label>Isi Pesan</label>
                <div style="background:var(--white); padding:20px; border-radius:var(--radius); line-height:1.8; color:var(--dark); border:1px solid var(--gray-200); white-space:pre-wrap;">
                    <?= clean($detailData['pesan']) ?>
                </div>
            </div>
        </div>
        
        <div class="modal-footer-ultimate">
            <a href="kontak.php" class="btn-secondary-ultimate">Tutup</a>
            <?php if ($detailData['status'] !== 'dibalas'): ?>
            <a href="?action=dibalas&id=<?= $detailData['id'] ?>" class="btn-primary-ultimate">
                <i class="fas fa-check"></i> Tandai Telah Dibalas
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* ===== KONTAK PAGE ULTIMATE v4.0 ===== */
.kontak-page { padding: var(--spacing-lg); animation: fadeIn 0.4s ease; }

/* Hero */
.kontak-hero {
    position: relative; padding: 36px 40px; margin-bottom: 28px;
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 50%, #1E40AF 100%);
    border-radius: var(--radius-2xl); overflow: hidden; box-shadow: var(--shadow-xl);
}
.hero-bg-effects { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.hero-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.5; animation: orbFloat 20s infinite ease-in-out; }
.orb-1 { width: 320px; height: 320px; background: radial-gradient(circle, #93C5FD, transparent); top: -120px; right: 8%; }
.orb-2 { width: 220px; height: 220px; background: radial-gradient(circle, #60A5FA, transparent); bottom: -100px; left: 15%; animation-delay: -7s; }
.hero-grid-pattern { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 40px 40px; }
.hero-mail-pattern { position: absolute; top: 50%; right: 5%; transform: translateY(-50%); font-size: 18rem; color: rgba(255,255,255,0.03); pointer-events: none; }
.hero-mail-pattern::before { content: '\f0e0'; font-family: 'Font Awesome 6 Free'; font-weight: 900; }

.hero-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; color: var(--white); }
.hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); border-radius: 50px; font-size: 0.78rem; font-weight: 600; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
.hero-title { font-size: 2.2rem; font-weight: 700; margin-bottom: 10px; line-height: 1.2; font-family: 'Space Grotesk', sans-serif; display: flex; align-items: center; gap: 16px; }
.title-icon-wrap { width: 56px; height: 56px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
.hero-subtitle { font-size: 0.95rem; opacity: 0.9; max-width: 600px; }
.hero-mini-stats { display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
.hms-item { display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; font-size: 0.85rem; }
.hms-item.live { background: rgba(255,255,255,0.2); border-color: rgba(255,255,255,0.3); }
.live-dot { width: 8px; height: 8px; background: #34D399; border-radius: 50%; box-shadow: 0 0 8px rgba(52,211,153,0.8); animation: liveDot 1.5s infinite; }
.hms-item i { color: #BFDBFE; }
.hms-item strong { font-family: 'Space Grotesk', sans-serif; font-weight: 700; }
.hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-ghost { padding: 12px 18px; background: rgba(255,255,255,0.12); color: var(--white); border: 1px solid rgba(255,255,255,0.2); border-radius: var(--radius); font-size: 0.9rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; backdrop-filter: blur(10px); text-decoration: none; }
.btn-ghost:hover { background: rgba(255,255,255,0.22); transform: translateY(-2px); color: var(--white); }

/* Layout */
.kontak-layout { display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }
.kontak-main-col { min-width: 0; }

/* Toolbar & Bulk */
.kontak-toolbar { padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
.toolbar-left { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; flex: 1; min-width: 0; }
.toolbar-search { position: relative; flex: 1; min-width: 240px; max-width: 380px; }
.toolbar-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.9rem; pointer-events: none; }
.toolbar-search input { width: 100%; padding: 10px 44px 10px 42px; border: 1.5px solid var(--gray-200); border-radius: var(--radius); font-size: 0.88rem; font-family: inherit; background: var(--gray-50); transition: all var(--transition); }
[data-theme="dark"] .toolbar-search input { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.toolbar-search input:focus { outline: none; border-color: #3B82F6; background: var(--white); box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }
.search-kbd { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); padding: 2px 8px; background: var(--gray-200); color: var(--gray-600); border-radius: 4px; font-size: 0.7rem; font-weight: 600; font-family: 'Courier New', monospace; pointer-events: none; }
.toolbar-filters { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.toolbar-select { padding: 9px 14px; border: 1.5px solid var(--gray-200); border-radius: var(--radius); font-size: 0.85rem; font-family: inherit; background: var(--white); cursor: pointer; transition: all var(--transition); color: var(--dark); min-width: 130px; }
[data-theme="dark"] .toolbar-select { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.toolbar-select:focus { outline: none; border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.toolbar-right { display: flex; gap: 10px; align-items: center; }
.toolbar-icon-btn { width: 40px; height: 40px; border-radius: var(--radius); border: 1.5px solid var(--gray-200); background: var(--white); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--gray-600); transition: all var(--transition); font-size: 0.9rem; }
[data-theme="dark"] .toolbar-icon-btn { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--gray-300); }
.toolbar-icon-btn:hover { background: #3B82F6; color: var(--white); border-color: #3B82F6; transform: translateY(-2px); }
.view-toggle { display: flex; background: var(--gray-100); border-radius: var(--radius); padding: 3px; gap: 2px; }
[data-theme="dark"] .view-toggle { background: rgba(255,255,255,0.08); }
.view-btn { width: 36px; height: 34px; border: none; background: transparent; border-radius: var(--radius-sm); cursor: pointer; color: var(--gray-600); transition: all var(--transition-fast); display: flex; align-items: center; justify-content: center; }
.view-btn.active { background: var(--white); color: #3B82F6; box-shadow: var(--shadow-sm); }

.bulk-bar { display: none; align-items: center; justify-content: space-between; padding: 12px 20px; background: linear-gradient(135deg, #2563EB, #3B82F6); color: var(--white); border-radius: var(--radius); margin-bottom: 16px; animation: slideDown 0.3s ease; box-shadow: var(--shadow-md); }
.bulk-bar.show { display: flex; }
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
.bulk-info { display: flex; align-items: center; gap: 10px; font-size: 0.9rem; }
.bulk-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.btn-sm { padding: 6px 12px; font-size: 0.82rem; border: none; border-radius: var(--radius-sm); cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; color: white; }
.btn-info { background: #3B82F6; } .btn-success { background: #10B981; } .btn-danger { background: #EF4444; } .btn-secondary { background: rgba(255,255,255,0.2); color: white; }

/* Grid & Cards */
.kontak-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 20px; }
.kontak-grid[data-view="list"] { grid-template-columns: 1fr; }
.kontak-grid[data-view="list"] .kontak-card { display: grid; grid-template-columns: 180px 1fr; }
.kontak-grid[data-view="list"] .kc-header { height: 100%; min-height: 180px; }

.kontak-card { position: relative; background: var(--white); border-radius: var(--radius-xl); overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; }
[data-theme="dark"] .kontak-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.kontak-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: rgba(59,130,246,0.3); }
.kontak-card.is-new { border-left: 4px solid #F59E0B; }

.card-checkbox { position: absolute; top: 14px; left: 14px; z-index: 15; cursor: pointer; opacity: 0; transition: opacity var(--transition); }
.kontak-card:hover .card-checkbox, .card-checkbox:has(input:checked) { opacity: 1; }
.card-checkbox input { position: absolute; opacity: 0; }
.check-mark { display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: rgba(255,255,255,0.95); border: 2px solid var(--gray-300); border-radius: 6px; transition: all var(--transition-fast); backdrop-filter: blur(10px); }
.card-checkbox input:checked + .check-mark { background: #3B82F6; border-color: #3B82F6; }
.check-mark::after { content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900; font-size: 0.7rem; color: var(--white); transform: scale(0); transition: transform 0.2s; }
.card-checkbox input:checked + .check-mark::after { transform: scale(1); }

.kc-header { position: relative; height: 140px; display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; }
.kc-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent 70%); display: flex; align-items: flex-end; justify-content: flex-end; padding: 14px; opacity: 0; transition: opacity var(--transition); z-index: 2; }
.kontak-card:hover .kc-overlay { opacity: 1; }
.kc-quick-actions { display: flex; gap: 8px; }
.kc-quick-actions a { width: 38px; height: 38px; border-radius: var(--radius-sm); background: rgba(255,255,255,0.95); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--dark); text-decoration: none; transition: all var(--transition-fast); backdrop-filter: blur(10px); font-size: 0.9rem; }
.kc-quick-actions a:hover { background: #3B82F6; color: var(--white); transform: translateY(-3px); }
.kc-quick-actions a:last-child:hover { background: #EF4444; }

.kc-body { padding: 20px; display: flex; flex-direction: column; flex: 1; }
.kc-meta { display: flex; gap: 14px; font-size: 0.75rem; color: var(--gray-600); margin-bottom: 10px; }
[data-theme="dark"] .kc-meta { color: var(--gray-400); }
.kc-meta i { color: #3B82F6; margin-right: 4px; }
.kc-title { font-size: 1.1rem; font-weight: 700; color: var(--dark); margin-bottom: 8px; line-height: 1.3; }
[data-theme="dark"] .kc-title { color: var(--white); }
.kc-subject { font-size: 0.9rem; }
.kc-excerpt { font-size: 0.85rem; color: var(--gray-600); margin-bottom: 14px; line-height: 1.5; flex: 1; }
[data-theme="dark"] .kc-excerpt { color: var(--gray-400); }
.kc-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--gray-100); }
[data-theme="dark"] .kc-footer { border-color: rgba(255,255,255,0.08); }
.kc-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
.kc-badge.badge-published { background: rgba(16,185,129,0.12); color: #047857; }
.kc-badge.badge-draft { background: rgba(245,158,11,0.12); color: #B45309; }
.kc-author { font-size: 0.75rem; color: var(--gray-600); display: flex; align-items: center; }

/* Table View */
.kontak-table-view .data-table { font-size: 0.85rem; }
.kontak-table-view .data-table th { background: var(--gray-50); padding: 12px 16px; font-weight: 700; color: var(--gray-700); border-bottom: 2px solid var(--gray-200); text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.5px; }
[data-theme="dark"] .kontak-table-view .data-table th { background: var(--dark); color: var(--gray-300); border-color: rgba(255,255,255,0.1); }
.kontak-table-view .data-table td { padding: 12px 16px; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
.kontak-table-view tr.new-row { background: rgba(245,158,11,0.03); }
[data-theme="dark"] .kontak-table-view tr.new-row { background: rgba(245,158,11,0.05); }

/* Sidebar Widgets */
.kontak-sidebar { display: flex; flex-direction: column; gap: 20px; position: sticky; top: 90px; }
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

.status-stat-item { padding: 10px 0; border-bottom: 1px solid var(--gray-100); }
[data-theme="dark"] .status-stat-item { border-color: rgba(255,255,255,0.05); }
.status-stat-item:last-child { border-bottom: none; }
.ssi-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.ssi-name { font-size: 0.82rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.ssi-name i { font-size: 0.78rem; }
.ssi-count { font-size: 0.85rem; font-weight: 700; color: var(--dark); font-family: 'Space Grotesk', sans-serif; }
[data-theme="dark"] .ssi-count { color: var(--white); }
.ssi-bar { height: 5px; background: var(--gray-100); border-radius: 50px; overflow: hidden; }
[data-theme="dark"] .ssi-bar { background: rgba(255,255,255,0.08); }
.ssi-bar-fill { height: 100%; border-radius: 50px; animation: barFill 1s ease-out; }

.recent-item { display: grid; grid-template-columns: 40px 1fr; gap: 10px; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--gray-100); cursor: pointer; transition: all var(--transition-fast); }
[data-theme="dark"] .recent-item { border-color: rgba(255,255,255,0.05); }
.recent-item:last-child { border-bottom: none; }
.recent-item:hover { transform: translateX(4px); }
.ri-icon { width: 40px; height: 40px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.ri-info { min-width: 0; }
.ri-name { font-size: 0.85rem; font-weight: 600; color: var(--dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
[data-theme="dark"] .ri-name { color: var(--gray-200); }
.ri-meta { font-size: 0.72rem; color: var(--gray-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.qa-btn { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--radius); background: var(--gray-50); color: var(--dark); border: none; width: 100%; text-align: left; font-size: 0.88rem; font-weight: 500; transition: all var(--transition); margin-bottom: 8px; cursor: pointer; font-family: inherit; }
[data-theme="dark"] .qa-btn { background: rgba(255,255,255,0.04); color: var(--gray-200); }
.qa-btn:hover { background: var(--gray-100); transform: translateX(4px); }
.qa-btn i:first-child { width: 16px; }
.qa-arrow { margin-left: auto; color: var(--gray-400); font-size: 0.72rem; opacity: 0; transition: opacity var(--transition-fast); }
.qa-btn:hover .qa-arrow { opacity: 1; }

/* Modal Ultimate */
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
.modal-footer-ultimate { display: flex; justify-content: flex-end; gap: 12px; padding: 20px 28px; border-top: 1px solid var(--gray-100); background: var(--gray-50); }
[data-theme="dark"] .modal-footer-ultimate { border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.2); }
.btn-secondary-ultimate, .btn-primary-ultimate { padding: 12px 24px; border-radius: var(--radius); font-size: 0.9rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; border: none; text-decoration: none; }
.btn-secondary-ultimate { background: var(--gray-100); color: var(--gray-700); }
[data-theme="dark"] .btn-secondary-ultimate { background: rgba(255,255,255,0.08); color: var(--gray-300); }
.btn-secondary-ultimate:hover { background: var(--gray-200); }
.btn-primary-ultimate { background: linear-gradient(135deg, #3B82F6, #2563EB); color: var(--white); box-shadow: 0 4px 16px rgba(59,130,246,0.4); }
.btn-primary-ultimate:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(59,130,246,0.5); color: white; }

/* Empty State */
.empty-state-ult { grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--white); border-radius: var(--radius-xl); border: 2px dashed var(--gray-200); }
[data-theme="dark"] .empty-state-ult { background: var(--dark-2); border-color: rgba(255,255,255,0.1); }
.empty-illustration i { font-size: 3.5rem; color: var(--gray-300); margin-bottom: 16px; display: block; }
[data-theme="dark"] .empty-illustration i { color: var(--gray-600); }
.empty-state-ult h3 { font-size: 1.3rem; color: var(--dark); margin-bottom: 8px; }
[data-theme="dark"] .empty-state-ult h3 { color: var(--white); }
.empty-state-ult p { color: var(--gray-600); margin-bottom: 24px; }
[data-theme="dark"] .empty-state-ult p { color: var(--gray-400); }
.empty-actions { display: flex; gap: 12px; justify-content: center; }

/* Responsive */
@media (max-width: 1200px) {
    .kontak-layout { grid-template-columns: 1fr; }
    .kontak-sidebar { position: static; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
}
@media (max-width: 768px) {
    .kontak-page { padding: 16px; }
    .kontak-hero { padding: 24px 20px; }
    .hero-title { font-size: 1.6rem; }
    .title-icon-wrap { width: 44px; height: 44px; font-size: 1.2rem; }
    .hero-content { flex-direction: column; align-items: flex-start; }
    .hero-actions { width: 100%; }
    .kontak-toolbar { flex-direction: column; align-items: stretch; }
    .toolbar-left, .toolbar-right { width: 100%; flex-direction: column; align-items: stretch; }
    .toolbar-search { max-width: 100%; }
    .toolbar-filters { flex-direction: column; align-items: stretch; }
    .toolbar-select { width: 100%; }
    .toolbar-right { flex-direction: row; justify-content: space-between; }
    .kontak-grid { grid-template-columns: 1fr; }
    .kontak-grid[data-view="list"] .kontak-card { grid-template-columns: 1fr; }
}
@media print {
    .kontak-hero, .kontak-sidebar, .kontak-toolbar, .bulk-bar, .card-checkbox, .kc-quick-actions { display: none !important; }
    .kontak-layout { grid-template-columns: 1fr; }
    .kontak-card { break-inside: avoid; }
}
</style>

<script>
(function() {
    'use strict';

    // View Toggle
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const view = this.dataset.view;
            const grid = document.getElementById('kontakGrid');
            const table = document.getElementById('kontakTable');
            if (view === 'table') { grid.style.display = 'none'; table.style.display = 'block'; } 
            else { grid.style.display = 'grid'; table.style.display = 'none'; grid.dataset.view = view; }
            localStorage.setItem('kontak_view', view);
        });
    });
    const savedView = localStorage.getItem('kontak_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    // Filters
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 600);
    });
    filterStatus?.addEventListener('change', applyFilters);

    window.applyFilters = function() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const s = filterStatus?.value;
        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (s) url.searchParams.set('status', s); else url.searchParams.delete('status');
        window.location = url;
    };

    window.resetFilters = function() {
        const url = new URL(window.location);
        url.searchParams.delete('q'); url.searchParams.delete('status');
        window.location = url;
    };

    // Bulk Selection
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');

    document.querySelectorAll('.kontak-check').forEach(check => {
        check.addEventListener('change', function() {
            if (this.checked) selectedIds.add(this.value);
            else selectedIds.delete(this.value);
            updateBulkBar();
        });
    });

    document.getElementById('selectAllTable')?.addEventListener('change', function() {
        document.querySelectorAll('#kontakTable .kontak-check').forEach(c => {
            c.checked = this.checked;
            if (this.checked) selectedIds.add(c.value);
            else selectedIds.delete(c.value);
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
        document.querySelectorAll('.kontak-check').forEach(c => c.checked = false);
        const selectAll = document.getElementById('selectAllTable');
        if (selectAll) selectAll.checked = false;
        updateBulkBar();
    };

    window.bulkAction = function(action) {
        if (selectedIds.size === 0) return;
        const messages = {
            'bulk-delete': `Yakin hapus ${selectedIds.size} pesan terpilih?`,
            'bulk-dibaca': `Tandai ${selectedIds.size} pesan telah dibaca?`,
            'bulk-dibalas': `Tandai ${selectedIds.size} pesan telah dibalas?`
        };
        if (!confirm(messages[action])) return;
        window.location.href = `?action=${action}&ids=${Array.from(selectedIds).join(',')}`;
    };

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
            const modal = document.getElementById('detailModal');
            if (modal?.classList.contains('open')) {
                window.location.href = 'kontak.php';
            }
        }
    });

    // Export CSV
    window.exportKontak = function() {
        const data = <?= json_encode($kontakList) ?>;
        if (!data || data.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['ID', 'Nama', 'Email', 'Telepon', 'Subjek', 'Pesan', 'Status', 'Tanggal'];
        const rows = data.map(d => [
            d.id,
            `"${(d.nama || '').replace(/"/g, '""')}"`,
            `"${(d.email || '').replace(/"/g, '""')}"`,
            `"${(d.telepon || '').replace(/"/g, '""')}"`,
            `"${(d.subjek || '').replace(/"/g, '""')}"`,
            `"${(d.pesan || '').replace(/"/g, '""')}"`,
            d.status,
            d.created_at
        ]);
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `kontak-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${data.length} pesan berhasil di-export`, 'success');
        }, 500);
    };

    console.log('%c📩 Kontak Page Ultimate Loaded', 'color:#3B82F6;font-weight:bold;font-size:12px');
})();
</script>

<?php include 'includes/footer.php'; ?>