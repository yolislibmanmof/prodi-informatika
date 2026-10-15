<?php
require_once 'includes/auth.php';
Auth::requireRole('superadmin');
$pageTitle = 'Template Email';

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
            $db->prepare("DELETE FROM email_templates WHERE id = ?")->execute([$id]);
            setFlash('success', 'Template email berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus template');
        }
        header('Location: email-templates.php');
        exit;
    }
    
    if ($_GET['action'] === 'toggle' && $id) {
        try {
            $db->prepare("UPDATE email_templates SET status = IF(status='aktif','nonaktif','aktif'), updated_at = NOW() WHERE id = ?")->execute([$id]);
            setFlash('success', 'Status template berhasil diubah');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mengubah status');
        }
        header('Location: email-templates.php');
        exit;
    }

    // Bulk delete
    if ($_GET['action'] === 'bulk-delete' && !empty($_POST['selected'])) {
        try {
            $ids = array_map('intval', $_POST['selected']);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $db->prepare("DELETE FROM email_templates WHERE id IN ($placeholders)")->execute($ids);
            setFlash('success', count($ids) . ' template berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus template');
        }
        header('Location: email-templates.php');
        exit;
    }
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $kode = strtolower(clean($_POST['kode']));
        $nama = clean($_POST['nama']);
        $subjek = clean($_POST['subjek']);
        $body = $_POST['body']; // HTML, tidak di-clean
        $status = clean($_POST['status']);
        
        // Validasi kode unik
        $check = $db->prepare("SELECT id FROM email_templates WHERE kode = ? AND id != ?");
        $check->execute([$kode, $id ?: 0]);
        if ($check->fetch()) {
            setFlash('error', 'Kode template sudah digunakan');
            header('Location: email-templates.php' . ($id ? '?action=edit&id=' . $id : ''));
            exit;
        }
        
        if ($id) {
            $stmt = $db->prepare("UPDATE email_templates SET kode=?, nama=?, subjek=?, body=?, status=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$kode, $nama, $subjek, $body, $status, $id]);
            setFlash('success', 'Template email berhasil diperbarui');
        } else {
            $stmt = $db->prepare("INSERT INTO email_templates (kode, nama, subjek, body, status, created_at, updated_at) VALUES (?,?,?,?,?,NOW(),NOW())");
            $stmt->execute([$kode, $nama, $subjek, $body, $status]);
            setFlash('success', 'Template email baru berhasil ditambahkan');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan: ' . $e->getMessage());
    }
    header('Location: email-templates.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$filterStatus = $_GET['status'] ?? '';
$searchQuery = $_GET['q'] ?? '';
$sortBy = $_GET['sort'] ?? 'nama';
$sortDir = $_GET['dir'] ?? 'ASC';

$where = "1=1";
$params = [];
if ($filterStatus) { $where .= " AND status = ?"; $params[] = $filterStatus; }
if ($searchQuery) { 
    $searchParam = "%$searchQuery%";
    $where .= " AND (nama LIKE ? OR kode LIKE ? OR subjek LIKE ?)"; 
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$validSorts = ['nama', 'kode', 'created_at', 'updated_at'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'nama';
$sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

/* ===== FETCH DATA & STATS ===== */
$templates = [];
$stats = [
    'total' => 0,
    'aktif' => 0,
    'nonaktif' => 0,
];

if ($db) {
    try {
        $sql = "SELECT * FROM email_templates WHERE $where ORDER BY $sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $templates = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM email_templates")->fetchColumn();
        $stats['aktif'] = (int)$db->query("SELECT COUNT(*) FROM email_templates WHERE status='aktif'")->fetchColumn();
        $stats['nonaktif'] = (int)$db->query("SELECT COUNT(*) FROM email_templates WHERE status='nonaktif'")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== PLACEHOLDERS ===== */
$placeholders = [
    '{{nama}}' => 'Nama penerima',
    '{{email}}' => 'Email penerima',
    '{{nomor}}' => 'Nomor pendaftaran/tiket',
    '{{tanggal}}' => 'Tanggal kegiatan',
    '{{link_konfirmasi}}' => 'URL konfirmasi',
    '{{site_name}}' => 'Nama situs',
    '{{site_url}}' => 'URL situs',
    '{{password}}' => 'Password (jika ada)',
];

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT * FROM email_templates WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content email-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="email-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-email-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-envelope-open-text"></i>
                    <span>Email Automation</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-mail-bulk"></i>
                    </span>
                    Template Email
                </h1>
                <p class="hero-subtitle">
                    Kelola template notifikasi email otomatis dengan placeholder dinamis
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-layer-group"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total Template</span>
                    </div>
                    <div class="hms-item active">
                        <i class="fas fa-check-circle"></i>
                        <span><strong><?= $stats['aktif'] ?></strong> Aktif</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportTemplates()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Template</span>
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
    <div class="email-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="email-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid email-stats">
                <div class="stat-card stat-blue">
                    <div class="stat-icon"><i class="fas fa-envelope-open-text"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Template</p>
                        <span class="stat-trend up"><i class="fas fa-database"></i> Templates</span>
                    </div>
                </div>

                <div class="stat-card stat-emerald">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['aktif'] ?>">0</h3>
                        <p>Template Aktif</p>
                        <span class="stat-trend up"><i class="fas fa-bolt"></i> Active</span>
                    </div>
                </div>

                <div class="stat-card stat-slate">
                    <div class="stat-icon"><i class="fas fa-pause-circle"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['nonaktif'] ?>">0</h3>
                        <p>Template Nonaktif</p>
                        <span class="stat-trend up"><i class="fas fa-archive"></i> Inactive</span>
                    </div>
                </div>
            </div>

            <!-- Info Box: Placeholders -->
            <div class="card info-box-card">
                <div class="card-body" style="padding: 20px;">
                    <div style="display: flex; gap: 16px; align-items: flex-start;">
                        <div class="info-icon-wrap">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin-bottom: 8px; color: #1E40AF; font-size: 1rem;">
                                <i class="fas fa-code" style="margin-right: 6px;"></i> Placeholder Variables
                            </h4>
                            <p style="font-size: 0.85rem; color: var(--gray-700); margin-bottom: 12px;">
                                Gunakan variabel berikut dalam template. Variabel akan diganti otomatis dengan data dinamis saat email dikirim:
                            </p>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                <?php foreach ($placeholders as $var => $desc): ?>
                                <span class="placeholder-badge">
                                    <code><?= $var ?></code>
                                    <span class="placeholder-desc"><?= $desc ?></span>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card email-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama, kode, atau subjek..." value="<?= clean($searchQuery) ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterStatus" class="toolbar-select">
                            <option value="">📊 Semua Status</option>
                            <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>🟢 Aktif</option>
                            <option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : '' ?>>⚪ Nonaktif</option>
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
                            <a href="?sort=kode&dir=ASC" class="sort-item <?= $sortBy === 'kode' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-code"></i> Kode A-Z
                            </a>
                            <a href="?sort=updated_at&dir=DESC" class="sort-item <?= $sortBy === 'updated_at' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-history"></i> Terbaru Diupdate
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
            <div class="bulk-bar" id="bulkBar">
                <div class="bulk-info">
                    <i class="fas fa-check-circle"></i>
                    <span><strong id="selectedCount">0</strong> template dipilih</span>
                </div>
                <div class="bulk-actions">
                    <button type="submit" formaction="?action=bulk-delete" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus template yang dipilih?')">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        Batal
                    </button>
                </div>
            </div>

            <!-- Templates Grid -->
            <div class="templates-grid" id="templatesGrid" data-view="grid">
                <?php if (!empty($templates)): ?>
                    <?php foreach ($templates as $t): ?>
                    <div class="template-card" data-id="<?= $t['id'] ?>">
                        <!-- Checkbox -->
                        <label class="card-checkbox">
                            <input type="checkbox" name="selected[]" class="template-check" value="<?= $t['id'] ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <!-- Header Area -->
                        <div class="tc-header" style="background: linear-gradient(135deg, #3B82F6, #1E40AF)">
                            <i class="fas fa-envelope-open-text"></i>
                            
                            <div class="tc-overlay">
                                <div class="tc-quick-actions" onclick="event.stopPropagation()">
                                    <button onclick="previewTemplate(<?= htmlspecialchars(json_encode($t, JSON_HEX_APOS)) ?>)" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editTemplate(<?= htmlspecialchars(json_encode($t, JSON_HEX_APOS)) ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=toggle&id=<?= $t['id'] ?>" title="Toggle Status">
                                        <i class="fas fa-<?= $t['status'] === 'aktif' ? 'eye-slash' : 'eye' ?>"></i>
                                    </a>
                                    <a href="?action=delete&id=<?= $t['id'] ?>" title="Hapus" data-confirm="Yakin hapus template ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="tc-status-badge" style="background: <?= $t['status'] === 'aktif' ? 'rgba(16,185,129,0.9)' : 'rgba(107,114,128,0.9)' ?>">
                                <i class="fas fa-<?= $t['status'] === 'aktif' ? 'check-circle' : 'pause-circle' ?>"></i>
                                <?= ucfirst($t['status']) ?>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="tc-body">
                            <div class="tc-code-row">
                                <code class="tc-code"><?= clean($t['kode']) ?></code>
                                <span class="tc-date">
                                    <i class="fas fa-clock"></i> <?= function_exists('formatTanggal') ? formatTanggal($t['updated_at']) : date('d M Y', strtotime($t['updated_at'])) ?>
                                </span>
                            </div>
                            <h3 class="tc-title"><?= clean($t['nama']) ?></h3>
                            <p class="tc-subject">
                                <i class="fas fa-envelope" style="color: #3B82F6; margin-right: 6px;"></i>
                                <?= clean(mb_substr($t['subjek'], 0, 60)) ?><?= strlen($t['subjek']) > 60 ? '...' : '' ?>
                            </p>
                            
                            <div class="tc-footer">
                                <button class="tc-action-btn" onclick="previewTemplate(<?= htmlspecialchars(json_encode($t, JSON_HEX_APOS)) ?>)" style="background: #3B82F6">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button class="tc-action-btn" onclick="editTemplate(<?= htmlspecialchars(json_encode($t, JSON_HEX_APOS)) ?>)" style="background: #6B7280">
                                    <i class="fas fa-edit"></i> Edit
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
                                <rect x="50" y="40" width="100" height="70" rx="4" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <path d="M 50 40 L 100 75 L 150 40" fill="none" stroke="var(--gray-300)" stroke-width="2"/>
                                <rect x="65" y="55" width="70" height="6" rx="3" fill="var(--gray-300)"/>
                                <rect x="65" y="70" width="50" height="6" rx="3" fill="var(--gray-300)"/>
                                <circle cx="155" cy="45" r="18" fill="#3B82F6" opacity="0.9"/>
                                <path d="M 148 45 L 162 45 M 155 38 L 155 52" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Belum ada template email</h3>
                        <p>Buat template untuk notifikasi otomatis sistem</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-plus"></i> Tambah Template Pertama
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- List View (Table) -->
            <?php if (!empty($templates)): ?>
            <div class="templates-list-view" id="templatesList" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="selectAllTable"></th>
                                    <th>Kode</th>
                                    <th>Nama Template</th>
                                    <th>Subjek Email</th>
                                    <th>Status</th>
                                    <th>Terakhir Update</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $t): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected[]" class="template-check" value="<?= $t['id'] ?>"></td>
                                    <td><code style="background:var(--gray-100);padding:3px 8px;border-radius:4px;font-size:0.75rem;color:#3B82F6"><?= clean($t['kode']) ?></code></td>
                                    <td><strong><?= clean($t['nama']) ?></strong></td>
                                    <td style="font-size:0.85rem"><?= clean(mb_substr($t['subjek'], 0, 50)) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $t['status'] === 'aktif' ? 'success' : 'default' ?>">
                                            <i class="fas fa-<?= $t['status'] === 'aktif' ? 'check-circle' : 'pause-circle' ?>"></i> <?= ucfirst($t['status']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:0.82rem"><?= function_exists('formatTanggal') ? formatTanggal($t['updated_at']) : date('d M Y', strtotime($t['updated_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-sm" onclick="previewTemplate(<?= htmlspecialchars(json_encode($t, JSON_HEX_APOS)) ?>)" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-sm" onclick="editTemplate(<?= htmlspecialchars(json_encode($t, JSON_HEX_APOS)) ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=toggle&id=<?= $t['id'] ?>" class="btn-icon btn-sm" title="Toggle">
                                                <i class="fas fa-<?= $t['status'] === 'aktif' ? 'eye-slash' : 'eye' ?>"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $t['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus template ini?" title="Hapus">
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
                <span>Menampilkan <strong><?= count($templates) ?></strong> template</span>
            </div>
            <?php endif; ?>
            </form>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="email-sidebar">
            <!-- Quick Actions -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="widget-body">
                    <button class="qa-btn primary" onclick="openModal()">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Template</span>
                    </button>
                    <a href="users.php" class="qa-btn">
                        <i class="fas fa-users-cog"></i>
                        <span>Manajemen User</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="settings.php" class="qa-btn">
                        <i class="fas fa-cog"></i>
                        <span>Pengaturan SMTP</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Tips -->
            <div class="card widget-card tips-card">
                <div class="widget-header">
                    <h3><i class="fas fa-lightbulb"></i> Tips Template</h3>
                </div>
                <div class="widget-body">
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Gunakan kode unik lowercase dengan underscore (contoh: <code>registrasi_berhasil</code>)</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Selalu sertakan placeholder {{site_name}} untuk branding yang konsisten</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Gunakan HTML inline styling untuk kompatibilitas email client</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="tip-text">Jangan hapus template default sistem untuk menghindari error</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== TEMPLATE MODAL ===== -->
<div class="modal" id="templateModal">
    <div class="modal-content modal-xl">
        <div class="modal-header modal-header-gradient template-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-envelope-open-text"></i></div>
                <div>
                    <h3 id="modalTitle">Tambah Template Email</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px" id="modalSubtitle">Gunakan placeholder {{variabel}} untuk konten dinamis</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" id="templateForm" class="modal-body">
            <input type="hidden" name="id" id="formId">
            
            <div class="form-sections">
                <!-- Basic Info -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-info-circle"></i> Informasi Dasar</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="field-label">Kode Template <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-code"></i>
                                <input type="text" name="kode" id="kode" required pattern="[a-z_]+" placeholder="contoh: pendaftaran_diterima" style="text-transform: lowercase;">
                            </div>
                            <small class="form-hint">Unik, lowercase, hanya huruf dan underscore</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label">Nama Template <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-tag"></i>
                                <input type="text" name="nama" id="nama" required placeholder="Contoh: Pendaftaran Diterima">
                            </div>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Subjek Email <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="text" name="subjek" id="subjek" required placeholder="Contoh: Selamat! Pendaftaran {{nomor}} Anda Diterima">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Body -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-code"></i> Body Email (HTML)</h4>
                    <div class="form-group">
                        <label class="field-label">Konten HTML <span class="required">*</span></label>
                        <div class="textarea-wrap">
                            <textarea name="body" id="body" rows="12" required placeholder="<p>Halo {{nama}},</p><p>Selamat, pendaftaran Anda...</p>"></textarea>
                        </div>
                        
                        <!-- Quick Insert -->
                        <div class="quick-insert-box">
                            <div class="qi-header">
                                <i class="fas fa-bolt"></i>
                                <span>Quick Insert Placeholder</span>
                            </div>
                            <div class="qi-buttons">
                                <?php foreach (array_keys($placeholders) as $var): ?>
                                <button type="button" class="qi-btn" onclick="insertPlaceholder('<?= $var ?>')">
                                    <code><?= $var ?></code>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-toggle-on"></i> Status</h4>
                    <div class="status-toggle-ult">
                        <label class="status-option">
                            <input type="radio" name="status" value="aktif" checked>
                            <div class="status-box draft">
                                <i class="fas fa-check-circle"></i>
                                <span>Aktif</span>
                                <small>Digunakan untuk mengirim email</small>
                            </div>
                        </label>
                        <label class="status-option">
                            <input type="radio" name="status" value="nonaktif">
                            <div class="status-box publish">
                                <i class="fas fa-pause-circle"></i>
                                <span>Nonaktif</span>
                                <small>Tidak akan dikirim</small>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="submitText">Simpan Template</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== PREVIEW MODAL ===== -->
<div class="modal" id="previewModal">
    <div class="modal-content modal-xl">
        <div class="modal-header modal-header-gradient template-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-eye"></i></div>
                <div>
                    <h3>Preview Template</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Tampilan email yang akan dikirim</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePreview()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="previewBody" style="background: #f3f4f6; padding: 0;">
            <!-- Preview content will be injected here -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePreview()">Tutup</button>
            <button class="btn btn-primary" id="previewEditBtn"><i class="fas fa-edit"></i> Edit Template</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   EMAIL PAGE - EMAIL TEMPLATE MANAGER EDITION v4.0
   Integrated with admin.css design system
============================================================ */
.email-page { padding: var(--spacing-lg); animation: fadeIn 0.4s ease; }

/* ===== HERO ===== */
.email-hero {
    position: relative; padding: 36px 40px; margin-bottom: 28px;
    background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 50%, #60A5FA 100%);
    border-radius: var(--radius-2xl); overflow: hidden; box-shadow: var(--shadow-xl);
}
.hero-bg-effects { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.hero-orb {
    position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.5;
    animation: orbFloat 20s infinite ease-in-out;
}
.orb-1 { width: 320px; height: 320px; background: radial-gradient(circle, #60A5FA, transparent); top: -120px; right: 8%; }
.orb-2 { width: 220px; height: 220px; background: radial-gradient(circle, #3B82F6, transparent); bottom: -100px; left: 15%; animation-delay: -7s; }
.hero-grid-pattern {
    position: absolute; inset: 0;
    background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 40px 40px;
}
.hero-email-pattern {
    position: absolute; top: 50%; right: 5%; transform: translateY(-50%);
    font-size: 18rem; color: rgba(255,255,255,0.04); pointer-events: none;
}
.hero-email-pattern::before { content: '\f0e0'; font-family: 'Font Awesome 6 Free'; font-weight: 900; }

.hero-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; color: var(--white); }
.hero-badge {
    display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px;
    background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2); border-radius: 50px;
    font-size: 0.78rem; font-weight: 600; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px;
}
.hero-title { font-size: 2.2rem; font-weight: 700; margin-bottom: 10px; line-height: 1.2; font-family: 'Space Grotesk', sans-serif; display: flex; align-items: center; gap: 16px; }
.title-icon-wrap {
    width: 56px; height: 56px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2); border-radius: 14px;
    display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;
}
.hero-subtitle { font-size: 0.95rem; opacity: 0.9; max-width: 600px; }
.hero-mini-stats { display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
.hms-item {
    display: flex; align-items: center; gap: 8px; padding: 8px 14px;
    background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; font-size: 0.85rem;
}
.hms-item.active { background: rgba(255,255,255,0.2); border-color: rgba(255,255,255,0.3); }
.hms-item i { color: #DBEAFE; }
.hms-item strong { font-family: 'Space Grotesk', sans-serif; font-weight: 700; }

.hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-hero-primary {
    padding: 14px 26px; background: linear-gradient(135deg, #F59E0B, #D97706); color: var(--white);
    border: none; border-radius: var(--radius); font-size: 0.92rem; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition);
    font-family: inherit; box-shadow: 0 8px 20px rgba(245,158,11,0.4);
}
.btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(245,158,11,0.5); }
.btn-ghost {
    padding: 12px 18px; background: rgba(255,255,255,0.12); color: var(--white);
    border: 1px solid rgba(255,255,255,0.2); border-radius: var(--radius); font-size: 0.9rem;
    font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
    transition: all var(--transition); font-family: inherit; backdrop-filter: blur(10px);
}
.btn-ghost:hover { background: rgba(255,255,255,0.22); transform: translateY(-2px); }

/* ===== LAYOUT ===== */
.email-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.email-main-col { min-width: 0; }
.email-stats { margin-bottom: 24px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
.stat-blue .stat-icon { background: linear-gradient(135deg, #3B82F6, #1E40AF); }
.stat-emerald .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-slate .stat-icon { background: linear-gradient(135deg, #6B7280, #374151); }

/* ===== INFO BOX ===== */
.info-box-card { margin-bottom: 24px; border: 1px solid rgba(59,130,246,0.2) !important; background: linear-gradient(135deg, rgba(59,130,246,0.08), rgba(30,64,175,0.04)) !important; }
.info-icon-wrap { width: 40px; height: 40px; border-radius: 50%; background: rgba(59,130,246,0.15); color: #3B82F6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.placeholder-badge { display: inline-flex; flex-direction: column; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2); border-radius: 8px; padding: 6px 10px; transition: all 0.2s; }
.placeholder-badge:hover { background: rgba(59,130,246,0.15); transform: translateY(-2px); }
.placeholder-badge code { font-family: 'Courier New', monospace; font-size: 0.78rem; color: #1E40AF; font-weight: 700; }
.placeholder-desc { font-size: 0.68rem; color: #475569; margin-top: 2px; }

/* ===== TOOLBAR ===== */
.email-toolbar { padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
.toolbar-left { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; flex: 1; min-width: 0; }
.toolbar-search { position: relative; flex: 1; min-width: 240px; max-width: 380px; }
.toolbar-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.9rem; pointer-events: none; }
.toolbar-search input {
    width: 100%; padding: 10px 44px 10px 42px; border: 1.5px solid var(--gray-200); border-radius: var(--radius);
    font-size: 0.88rem; font-family: inherit; background: var(--gray-50); transition: all var(--transition);
}
[data-theme="dark"] .toolbar-search input { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.toolbar-search input:focus { outline: none; border-color: #3B82F6; background: var(--white); box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }
.search-kbd {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    padding: 2px 8px; background: var(--gray-200); color: var(--gray-600); border-radius: 4px;
    font-size: 0.7rem; font-weight: 600; font-family: 'Courier New', monospace; pointer-events: none;
}
.toolbar-filters { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.toolbar-select {
    padding: 9px 14px; border: 1.5px solid var(--gray-200); border-radius: var(--radius);
    font-size: 0.85rem; font-family: inherit; background: var(--white); cursor: pointer;
    transition: all var(--transition); color: var(--dark); min-width: 130px;
}
[data-theme="dark"] .toolbar-select { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.toolbar-select:focus { outline: none; border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.toolbar-right { display: flex; gap: 10px; align-items: center; }
.sort-dropdown { position: relative; }
.sort-menu {
    position: absolute; top: calc(100% + 8px); right: 0; min-width: 200px;
    background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-100); padding: 6px; opacity: 0; visibility: hidden;
    transform: translateY(-8px); transition: all var(--transition); z-index: 50;
}
[data-theme="dark"] .sort-menu { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.sort-dropdown.open .sort-menu { opacity: 1; visibility: visible; transform: translateY(0); }
.sort-item { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: var(--radius-sm); color: var(--dark); text-decoration: none; font-size: 0.85rem; transition: all var(--transition-fast); }
[data-theme="dark"] .sort-item { color: var(--gray-200); }
.sort-item:hover { background: var(--gray-50); }
.sort-item.active { background: rgba(59,130,246,0.1); color: #1E40AF; font-weight: 600; }
.sort-item i { width: 16px; color: var(--gray-500); }
.sort-item.active i { color: #3B82F6; }
.view-toggle { display: flex; background: var(--gray-100); border-radius: var(--radius); padding: 3px; gap: 2px; }
.view-btn { width: 36px; height: 34px; border: none; background: transparent; border-radius: var(--radius-sm); cursor: pointer; color: var(--gray-600); transition: all var(--transition-fast); display: flex; align-items: center; justify-content: center; }
.view-btn.active { background: var(--white); color: #3B82F6; box-shadow: var(--shadow-sm); }

/* ===== BULK BAR ===== */
.bulk-bar {
    display: none; align-items: center; justify-content: space-between; padding: 12px 20px;
    background: linear-gradient(135deg, #1E40AF, #3B82F6); color: var(--white);
    border-radius: var(--radius); margin-bottom: 16px; animation: slideDown 0.3s ease; box-shadow: var(--shadow-md);
}
.bulk-bar.show { display: flex; }
.bulk-info { display: flex; align-items: center; gap: 10px; font-size: 0.9rem; }
.bulk-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.btn-sm { padding: 6px 12px; font-size: 0.82rem; }
.btn-danger { background: #EF4444; color: var(--white); border: none; border-radius: var(--radius-sm); cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }

/* ===== TEMPLATES GRID ===== */
.templates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 20px; }
.template-card {
    position: relative; background: var(--white); border-radius: var(--radius-xl); overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); display: flex; flex-direction: column;
}
[data-theme="dark"] .template-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.template-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: rgba(59,130,246,0.3); }

.card-checkbox { position: absolute; top: 14px; left: 14px; z-index: 15; cursor: pointer; opacity: 0; transition: opacity var(--transition); }
.template-card:hover .card-checkbox, .card-checkbox:has(input:checked) { opacity: 1; }
.card-checkbox input { position: absolute; opacity: 0; }
.check-mark { display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: rgba(255,255,255,0.95); border: 2px solid var(--gray-300); border-radius: 6px; transition: all var(--transition-fast); backdrop-filter: blur(10px); }
.card-checkbox input:checked + .check-mark { background: #3B82F6; border-color: #3B82F6; }
.check-mark::after { content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900; font-size: 0.7rem; color: var(--white); transform: scale(0); transition: transform 0.2s; }
.card-checkbox input:checked + .check-mark::after { transform: scale(1); }

.tc-header { position: relative; height: 120px; display: flex; align-items: center; justify-content: center; font-size: 3.5rem; color: rgba(255,255,255,0.3); overflow: hidden; }
.tc-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity var(--transition); z-index: 2; }
.template-card:hover .tc-overlay { opacity: 1; }
.tc-quick-actions { display: flex; gap: 8px; }
.tc-quick-actions button, .tc-quick-actions a {
    width: 38px; height: 38px; border-radius: var(--radius-sm); background: rgba(255,255,255,0.95); border: none;
    cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--dark);
    text-decoration: none; transition: all var(--transition-fast); backdrop-filter: blur(10px); font-size: 0.9rem;
}
.tc-quick-actions button:hover, .tc-quick-actions a:hover { background: #3B82F6; color: var(--white); transform: translateY(-3px); }
.tc-status-badge {
    position: absolute; top: 12px; right: 12px; padding: 4px 10px; color: var(--white);
    border-radius: 20px; font-size: 0.68rem; font-weight: 700; display: inline-flex;
    align-items: center; gap: 5px; z-index: 12; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.tc-body { padding: 18px; display: flex; flex-direction: column; flex: 1; }
.tc-code-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.tc-code { background: var(--gray-100); padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; color: #3B82F6; font-family: 'Courier New', monospace; font-weight: 600; }
.tc-date { font-size: 0.72rem; color: var(--gray-500); display: flex; align-items: center; gap: 4px; }
.tc-title { font-size: 1.05rem; font-weight: 700; color: var(--dark); margin-bottom: 8px; line-height: 1.3; }
[data-theme="dark"] .tc-title { color: var(--white); }
.tc-subject { font-size: 0.82rem; color: var(--gray-600); margin-bottom: 16px; line-height: 1.4; flex: 1; }
.tc-footer { display: flex; gap: 8px; padding-top: 14px; border-top: 1px solid var(--gray-100); }
[data-theme="dark"] .tc-footer { border-color: rgba(255,255,255,0.08); }
.tc-action-btn {
    flex: 1; padding: 8px; border-radius: var(--radius-sm); color: var(--white); border: none;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
    font-size: 0.82rem; font-weight: 600; transition: all var(--transition-fast);
}
.tc-action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

/* ===== LIST VIEW ===== */
.templates-list-view .data-table { font-size: 0.85rem; }
.templates-list-view .data-table th { background: var(--gray-50); padding: 12px 16px; font-weight: 700; color: var(--gray-700); border-bottom: 2px solid var(--gray-200); text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.5px; }
[data-theme="dark"] .templates-list-view .data-table th { background: var(--dark); color: var(--gray-300); border-color: rgba(255,255,255,0.1); }
.templates-list-view .data-table td { padding: 12px 16px; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }

/* ===== EMPTY STATE ===== */
.empty-state-ult { grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--white); border-radius: var(--radius-xl); border: 2px dashed var(--gray-200); }
[data-theme="dark"] .empty-state-ult { background: var(--dark-2); border-color: rgba(255,255,255,0.1); }
.empty-illustration { margin-bottom: 24px; display: inline-block; animation: floatIllustration 3s ease-in-out infinite; }
.empty-state-ult h3 { font-size: 1.3rem; color: var(--dark); margin-bottom: 8px; }
[data-theme="dark"] .empty-state-ult h3 { color: var(--white); }
.empty-state-ult p { color: var(--gray-600); margin-bottom: 24px; }
.result-info { text-align: center; padding: 12px; color: var(--gray-600); font-size: 0.85rem; }
.result-info strong { color: #3B82F6; font-family: 'Space Grotesk', sans-serif; }

/* ===== SIDEBAR WIDGETS ===== */
.email-sidebar { display: flex; flex-direction: column; gap: 20px; position: sticky; top: 90px; }
.widget-card { overflow: hidden; }
.widget-header { padding: 16px 18px; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); }
[data-theme="dark"] .widget-header { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.08); }
.widget-header h3 { font-size: 0.95rem; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 8px; margin: 0; }
[data-theme="dark"] .widget-header h3 { color: var(--white); }
.widget-header h3 i { color: #3B82F6; }
.widget-body { padding: 16px 18px; }

.qa-btn {
    display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--radius);
    background: var(--gray-50); color: var(--dark); text-decoration: none; font-size: 0.88rem;
    font-weight: 500; transition: all var(--transition); margin-bottom: 8px; cursor: pointer;
    border: none; width: 100%; text-align: left; font-family: inherit;
}
[data-theme="dark"] .qa-btn { background: rgba(255,255,255,0.04); color: var(--gray-200); }
.qa-btn:hover { background: var(--gray-100); transform: translateX(4px); }
.qa-btn.primary { background: linear-gradient(135deg, #3B82F6, #1E40AF); color: var(--white); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
.qa-btn.primary:hover { box-shadow: 0 6px 16px rgba(59,130,246,0.4); }
.qa-btn i:first-child { color: #3B82F6; width: 16px; }
.qa-btn.primary i:first-child { color: var(--white); }
.qa-btn span { flex: 1; }
.qa-arrow { color: var(--gray-400); font-size: 0.72rem; opacity: 0; transition: opacity var(--transition-fast); }
.qa-btn:hover .qa-arrow { opacity: 1; }

.tips-card { background: linear-gradient(135deg, rgba(59,130,246,0.04), rgba(30,64,175,0.04)); }
.tip-item { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--gray-100); align-items: flex-start; }
[data-theme="dark"] .tip-item { border-color: rgba(255,255,255,0.05); }
.tip-item:last-child { border-bottom: none; }
.tip-icon { width: 28px; height: 28px; border-radius: var(--radius-sm); background: rgba(59,130,246,0.12); color: #3B82F6; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; margin-top: 1px; }
.tip-text { font-size: 0.82rem; color: var(--gray-700); line-height: 1.5; flex: 1; }
[data-theme="dark"] .tip-text { color: var(--gray-300); }
.tip-text code { background: rgba(59,130,246,0.1); color: #1E40AF; padding: 2px 6px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 0.78rem; }

/* ===== MODAL ===== */
.template-modal-head { background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 60%, #60A5FA 100%) !important; }
.modal-xl { max-width: 900px; }
.quick-insert-box { margin-top: 12px; padding: 14px; background: var(--gray-50); border-radius: var(--radius); border: 1px solid var(--gray-200); }
[data-theme="dark"] .quick-insert-box { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.1); }
.qi-header { display: flex; align-items: center; gap: 8px; font-size: 0.82rem; font-weight: 600; color: var(--gray-700); margin-bottom: 10px; }
[data-theme="dark"] .qi-header { color: var(--gray-300); }
.qi-header i { color: #F59E0B; }
.qi-buttons { display: flex; flex-wrap: wrap; gap: 6px; }
.qi-btn { padding: 4px 10px; background: var(--white); border: 1px solid var(--gray-200); border-radius: 6px; cursor: pointer; transition: all 0.2s; font-family: inherit; }
[data-theme="dark"] .qi-btn { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); }
.qi-btn:hover { background: #3B82F6; color: var(--white); border-color: #3B82F6; }
.qi-btn code { font-family: 'Courier New', monospace; font-size: 0.75rem; font-weight: 600; }

/* ===== PREVIEW MODAL ===== */
#previewBody { background: #f3f4f6; padding: 24px; min-height: 400px; }
[data-theme="dark"] #previewBody { background: #111827; }
.email-preview-container { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow-md); overflow: hidden; max-width: 600px; margin: 0 auto; }
[data-theme="dark"] .email-preview-container { background: #1F2937; }
.email-preview-header { background: #f9fafb; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
[data-theme="dark"] .email-preview-header { background: #111827; border-color: #374151; }
.email-preview-meta { margin-bottom: 8px; }
.email-preview-meta-row { display: flex; margin-bottom: 4px; font-size: 0.85rem; }
.email-preview-label { width: 60px; color: #6B7280; font-weight: 600; flex-shrink: 0; }
[data-theme="dark"] .email-preview-label { color: #9CA3AF; }
.email-preview-value { color: #111827; word-break: break-all; }
[data-theme="dark"] .email-preview-value { color: #F3F4F6; }
.email-preview-body { padding: 24px 20px; font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #374151; }
[data-theme="dark"] .email-preview-body { color: #D1D5DB; }
.email-preview-body .placeholder-highlight { background: #FEF3C7; color: #92400E; padding: 2px 6px; border-radius: 4px; font-family: 'Courier New', monospace; font-weight: 600; font-size: 13px; }
[data-theme="dark"] .email-preview-body .placeholder-highlight { background: rgba(251, 191, 36, 0.2); color: #FBBF24; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .email-layout { grid-template-columns: 1fr; }
    .email-sidebar { position: static; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
}
@media (max-width: 768px) {
    .email-page { padding: 16px; }
    .email-hero { padding: 24px 20px; }
    .hero-title { font-size: 1.6rem; }
    .title-icon-wrap { width: 44px; height: 44px; font-size: 1.2rem; }
    .hero-content { flex-direction: column; align-items: flex-start; }
    .hero-actions { width: 100%; }
    .hero-actions .btn-hero-primary { flex: 1; }
    .email-toolbar { flex-direction: column; align-items: stretch; }
    .toolbar-left, .toolbar-right { width: 100%; flex-direction: column; align-items: stretch; }
    .toolbar-search { max-width: 100%; }
    .toolbar-filters { flex-direction: column; align-items: stretch; }
    .toolbar-select { width: 100%; }
    .toolbar-right { flex-direction: row; justify-content: space-between; }
    .templates-grid { grid-template-columns: 1fr; }
    .form-grid-2 { grid-template-columns: 1fr; }
}
@media print {
    .email-hero, .email-sidebar, .email-toolbar, .bulk-bar, .tc-quick-actions, .card-checkbox { display: none !important; }
    .email-layout { grid-template-columns: 1fr; }
    .template-card { break-inside: avoid; }
}
</style>

<script>
(function() {
    'use strict';
    const allTemplates = <?= json_encode($templates) ?>;
    
    /* Helper escape HTML untuk JavaScript (pengganti clean() PHP) */
    const escapeHtml = (str) => String(str ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

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
        document.getElementById('templateForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah Template Email';
        document.getElementById('modalSubtitle').textContent = 'Gunakan placeholder {{variabel}} untuk konten dinamis';
        document.getElementById('submitText').textContent = 'Simpan Template';
        document.getElementById('templateModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('kode')?.focus(), 300);
    };
    window.closeModal = function() {
        document.getElementById('templateModal').classList.remove('active');
        document.body.style.overflow = '';
    };
    window.closePreview = function() {
        document.getElementById('previewModal').classList.remove('active');
        document.body.style.overflow = '';
    };
    window.editTemplate = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Template Email';
        document.getElementById('modalSubtitle').textContent = 'Perbarui template email';
        document.getElementById('submitText').textContent = 'Perbarui Template';
        document.getElementById('formId').value = data.id;
        document.getElementById('kode').value = data.kode;
        document.getElementById('nama').value = data.nama;
        document.getElementById('subjek').value = data.subjek;
        document.getElementById('body').value = data.body;
        document.querySelector(`input[name="status"][value="${data.status}"]`).checked = true;
        document.getElementById('templateModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== PREVIEW MODAL ===== */
    window.previewTemplate = function(data) {
        const placeholders = <?= json_encode($placeholders) ?>;
        let previewBody = data.body;
        // Highlight placeholders
        Object.keys(placeholders).forEach(placeholder => {
            const regex = new RegExp(placeholder.replace(/[{}]/g, '\\$&'), 'g');
            previewBody = previewBody.replace(regex, `<span class="placeholder-highlight">${placeholder}</span>`);
        });
        document.getElementById('previewBody').innerHTML = `
            <div class="email-preview-container">
                <div class="email-preview-header">
                    <div class="email-preview-meta">
                        <div class="email-preview-meta-row">
                            <span class="email-preview-label">Dari:</span>
                            <span class="email-preview-value">admin@${window.location.hostname}</span>
                        </div>
                        <div class="email-preview-meta-row">
                            <span class="email-preview-label">Subjek:</span>
                            <span class="email-preview-value">${escapeHtml(data.subjek)}</span>
                        </div>
                    </div>
                </div>
                <div class="email-preview-body">
                    ${previewBody}
                </div>
            </div>
        `;
        document.getElementById('previewEditBtn').onclick = function() {
            closePreview();
            setTimeout(() => editTemplate(data), 300);
        };
        document.getElementById('previewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== QUICK INSERT ===== */
    window.insertPlaceholder = function(varName) {
        const textarea = document.getElementById('body');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        textarea.value = text.substring(0, start) + varName + text.substring(end);
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = start + varName.length;
    };

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });
    filterStatus?.addEventListener('change', applyFilters);
    
    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const status = filterStatus?.value;
        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        window.location = url;
    }
    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'status', 'sort', 'dir'].forEach(p => url.searchParams.delete(p));
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
            const grid = document.getElementById('templatesGrid');
            const list = document.getElementById('templatesList');
            if (view === 'list') {
                grid.style.display = 'none';
                list.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                list.style.display = 'none';
            }
            localStorage.setItem('templates_view', view);
        });
    });
    const savedView = localStorage.getItem('templates_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');
    const selectAllCheckbox = document.getElementById('selectAllTable');
    
    document.querySelectorAll('.template-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
            updateSelectAllState();
        });
    });
    
    window.toggleSelectAll = function(checkbox) {
        document.querySelectorAll('.template-check').forEach(c => {
            c.checked = checkbox.checked;
            const id = c.value;
            if (checkbox.checked) selectedIds.add(id);
            else selectedIds.delete(id);
        });
        updateBulkBar();
    };
    
    function updateSelectAllState() {
        const allChecks = document.querySelectorAll('.template-check');
        const checkedChecks = document.querySelectorAll('.template-check:checked');
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
        document.querySelectorAll('.template-check').forEach(c => c.checked = false);
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
        updateBulkBar();
    };

    /* ===== EXPORT CSV ===== */
    window.exportTemplates = function() {
        if (!allTemplates || allTemplates.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['Kode', 'Nama', 'Subjek', 'Status', 'Terakhir Update'];
        const rows = allTemplates.map(t => [
            `"${(t.kode || '').replace(/"/g, '""')}"`,
            `"${(t.nama || '').replace(/"/g, '""')}"`,
            `"${(t.subjek || '').replace(/"/g, '""')}"`,
            t.status || '',
            t.updated_at || ''
        ]);
        
        // FIX: Pastikan \n dalam satu baris string
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `email-templates-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${allTemplates.length} template berhasil di-export`, 'success');
        }, 500);
    };

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        if (e.key === 'n' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) && !e.ctrlKey && !e.metaKey) {
            e.preventDefault();
            openModal();
        }
        if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            searchInput?.focus();
        }
        if (e.key === 'Escape') {
            closeModal();
            closePreview();
        }
    });

    /* ===== MODAL BACKDROP CLICK ===== */
    ['templateModal', 'previewModal'].forEach(id => {
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
        setTimeout(() => editTemplate(<?= json_encode($editData, JSON_HEX_APOS) ?>), 300);
    });
    <?php endif; ?>
    
    console.log('%c📧 Email Templates v4.0 Loaded', 'color:#3B82F6;font-weight:bold;font-size:14px');
    console.log('%c   Features: Live Preview, Quick Insert, Bulk Actions, Export CSV', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: N (New), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>