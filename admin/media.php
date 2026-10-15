<?php
require_once 'includes/auth.php';
$pageTitle = 'Media Library';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
}

$currentAdminId = $_SESSION['admin_id'] ?? 0;
$uploadDir = __DIR__ . '/../uploads/media/';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

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
            $stmt = $db->prepare("SELECT path FROM media WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!empty($row['path'])) {
                deleteUploadedFile((string)$row['path']);
            }
            $db->prepare("DELETE FROM media WHERE id = ?")->execute([$id]);
            setFlash('success', 'File media berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus file');
        }
        header('Location: media.php');
        exit;
    }
    
    // Bulk delete
    if ($_GET['action'] === 'bulk-delete' && !empty($_GET['ids'])) {
        try {
            $ids = array_map('intval', explode(',', $_GET['ids']));
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            $stmt = $db->prepare("SELECT path FROM media WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $row) {
                if (!empty($row['path'])) {
                    deleteUploadedFile((string)$row['path']);
                }
            }
            $db->prepare("DELETE FROM media WHERE id IN ($placeholders)")->execute($ids);
            setFlash('success', count($ids) . ' file berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus file');
        }
        header('Location: media.php');
        exit;
    }
}

/* ===== HANDLE UPLOAD ===== */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_FILES['media_files']) && $db) {
    // SVG is excluded because active SVG content can execute script on this origin.
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxSize = 5 * 1024 * 1024;
    $uploaded = 0;
    $errors = [];
    $files = $_FILES['media_files'];

    foreach (($files['tmp_name'] ?? []) as $key => $tmpName) {
        $uploadError = (int)($files['error'][$key] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK || !is_string($tmpName) || $tmpName === '' || !is_uploaded_file($tmpName)) {
            $errors[] = 'File #' . ((int)$key + 1) . ': upload tidak valid';
            continue;
        }

        $originalName = basename((string)($files['name'][$key] ?? ''));
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $size = (int)($files['size'][$key] ?? 0);
        if (!in_array($ext, $allowedExtensions, true)) {
            $errors[] = $originalName . ': format gambar tidak diizinkan';
            continue;
        }
        if ($size <= 0 || $size > $maxSize) {
            $errors[] = $originalName . ': ukuran harus 1 byte–5 MB';
            continue;
        }

        $mimeType = UploadValidation::validatedRasterImageMime($tmpName, $ext, $size, $maxSize, 40000000);
        if ($mimeType === null) {
            $errors[] = $originalName . ': isi file bukan gambar valid';
            continue;
        }

        try {
            $newName = 'media_' . bin2hex(random_bytes(16)) . '.' . $ext;
        } catch (Throwable $e) {
            $errors[] = $originalName . ': pembuatan nama file aman gagal';
            continue;
        }

        $target = $uploadDir . $newName;
        if (!move_uploaded_file($tmpName, $target)) {
            $errors[] = $originalName . ': gagal menyimpan file';
            continue;
        }
        @chmod($target, 0644);

        try {
            $stmt = $db->prepare("INSERT INTO media (filename, path, mime_type, ukuran, alt_text, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                mb_substr($originalName, 0, 255),
                'media/' . $newName,
                $mimeType,
                $size,
                mb_substr(trim(strip_tags((string)($_POST['alt_text'] ?? ''))), 0, 255),
                $currentAdminId
            ]);
            $uploaded++;
        } catch (Throwable $e) {
            @unlink($target);
            error_log('Media metadata save failed: ' . $e->getMessage());
            $errors[] = $originalName . ': gagal menyimpan metadata';
        }
    }

    if ($uploaded > 0) {
        setFlash('success', "$uploaded file berhasil diunggah");
    }
    if (!empty($errors)) {
        setFlash('error', implode(', ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? '...' : ''));
    }
    header('Location: media.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$filterType = $_GET['type'] ?? '';
$searchQuery = $_GET['q'] ?? '';
$filterUploader = $_GET['uploader'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$sortDir = $_GET['dir'] ?? 'DESC';

$where = "1=1";
$params = [];

if ($filterType) { 
    $where .= " AND mime_type LIKE ?"; 
    $params[] = "$filterType%";
}
if ($filterUploader) {
    $where .= " AND m.uploaded_by = ?";
    $params[] = (int)$filterUploader;
}
if ($searchQuery) { 
    $where .= " AND (filename LIKE ? OR alt_text LIKE ?)"; 
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$validSorts = ['created_at', 'filename', 'ukuran', 'mime_type'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'created_at';
$sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

/* ===== FETCH DATA ===== */
$mediaList = [];
$stats = [
    'total' => 0,
    'images' => 0,
    'total_size' => 0,
    'uploaders' => 0,
];

if ($db) {
    try {
        $sql = "SELECT m.*, a.nama_lengkap as uploader_name 
                FROM media m 
                LEFT JOIN admins a ON m.uploaded_by = a.id 
                WHERE $where 
                ORDER BY m.$sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $mediaList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM media")->fetchColumn();
        $stats['images'] = (int)$db->query("SELECT COUNT(*) FROM media WHERE mime_type LIKE 'image%'")->fetchColumn();
        $stats['total_size'] = (int)$db->query("SELECT COALESCE(SUM(ukuran), 0) FROM media")->fetchColumn();
        $stats['uploaders'] = (int)$db->query("SELECT COUNT(DISTINCT uploaded_by) FROM media WHERE uploaded_by IS NOT NULL")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== FILE TYPE STATS ===== */
$typeStats = [];
if ($db) {
    try {
        $typeStats = $db->query("
            SELECT mime_type, COUNT(*) as total, SUM(ukuran) as total_size
            FROM media 
            WHERE mime_type IS NOT NULL
            GROUP BY mime_type
            ORDER BY total DESC
            LIMIT 6
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== TOP UPLOADERS ===== */
$topUploaders = [];
if ($db) {
    try {
        $topUploaders = $db->query("
            SELECT a.nama_lengkap, a.email, COUNT(*) as total, SUM(m.ukuran) as total_size
            FROM media m 
            LEFT JOIN admins a ON m.uploaded_by = a.id 
            WHERE m.uploaded_by IS NOT NULL
            GROUP BY m.uploaded_by, a.nama_lengkap, a.email
            ORDER BY total DESC 
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== RECENT UPLOADS ===== */
$recentUploads = [];
if ($db) {
    try {
        $recentUploads = $db->query("
            SELECT id, filename, path, mime_type, created_at
            FROM media 
            ORDER BY created_at DESC 
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== UNIQUE UPLOADERS FOR FILTER ===== */
$uniqueUploaders = [];
if ($db) {
    try {
        $uniqueUploaders = $db->query("
            SELECT DISTINCT a.id, a.nama_lengkap 
            FROM media m 
            JOIN admins a ON m.uploaded_by = a.id 
            ORDER BY a.nama_lengkap
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== STORAGE CALCULATION ===== */
$maxStorage = 2 * 1024 * 1024 * 1024; // 2GB
$storagePercent = $maxStorage > 0 ? min(100, round(($stats['total_size'] / $maxStorage) * 100, 1)) : 0;
$storageColor = $storagePercent > 90 ? '#EF4444' : ($storagePercent > 70 ? '#F59E0B' : '#EC4899');

/* ===== FILE TYPE CONFIG ===== */
$fileTypeConfig = [
    'image/jpeg' => ['icon' => 'fa-file-image', 'color' => '#EC4899', 'label' => 'JPEG'],
    'image/png' => ['icon' => 'fa-file-image', 'color' => '#8B5CF6', 'label' => 'PNG'],
    'image/gif' => ['icon' => 'fa-file-image', 'color' => '#F59E0B', 'label' => 'GIF'],
    'image/webp' => ['icon' => 'fa-file-image', 'color' => '#06B6D4', 'label' => 'WebP'],
    'image/svg+xml' => ['icon' => 'fa-file-code', 'color' => '#10B981', 'label' => 'SVG'],
];

function getFileTypeInfo($mimeType) {
    global $fileTypeConfig;
    return $fileTypeConfig[$mimeType] ?? ['icon' => 'fa-file-image', 'color' => '#6B7280', 'label' => 'Image'];
}

function getInitials($name) {
    $parts = explode(' ', trim($name ?? 'A'));
    $initial = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initial .= strtoupper(substr(end($parts), 0, 1));
    }
    return $initial;
}

include 'includes/header.php';
?>

<div class="page-content media-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="media-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-image-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-palette"></i>
                    <span>Digital Asset Management</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-images"></i>
                    </span>
                    Media Library
                </h1>
                <p class="hero-subtitle">
                    Pustaka terpusat untuk gambar, video, dan aset digital website
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-images"></i>
                        <span><strong><?= $stats['total'] ?></strong> Files</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-image"></i>
                        <span><strong><?= $stats['images'] ?></strong> Images</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-database"></i>
                        <span><strong><?= formatSize($stats['total_size']) ?></strong> Storage</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-users"></i>
                        <span><strong><?= $stats['uploaders'] ?></strong> Uploaders</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportMedia()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-ghost" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openUploadModal()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Upload Media</span>
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
    <div class="media-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="media-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid media-stats">
                <div class="stat-card stat-pink">
                    <div class="stat-icon"><i class="fas fa-images"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Files</p>
                        <span class="stat-trend up"><i class="fas fa-folder"></i> Library</span>
                    </div>
                </div>

                <div class="stat-card stat-purple">
                    <div class="stat-icon"><i class="fas fa-image"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['images'] ?>">0</h3>
                        <p>Gambar</p>
                        <span class="stat-trend up"><i class="fas fa-photo-video"></i> Visual</span>
                    </div>
                </div>

                <div class="stat-card stat-cyan">
                    <div class="stat-icon"><i class="fas fa-hdd"></i></div>
                    <div class="stat-info">
                        <h3><?= formatSize($stats['total_size']) ?></h3>
                        <p>Storage Used</p>
                        <span class="stat-trend up" style="color: <?= $storageColor ?>">
                            <i class="fas fa-database"></i> <?= $storagePercent ?>%
                        </span>
                    </div>
                </div>

                <div class="stat-card stat-rose">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['uploaders'] ?>">0</h3>
                        <p>Uploaders</p>
                        <span class="stat-trend up"><i class="fas fa-user-check"></i> Active</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card media-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama file atau alt text..." value="<?= clean($searchQuery) ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterType" class="toolbar-select">
                            <option value="">🖼️ Semua Tipe</option>
                            <option value="image" <?= $filterType === 'image' ? 'selected' : '' ?>>📷 Gambar</option>
                            <option value="video" <?= $filterType === 'video' ? 'selected' : '' ?>>🎬 Video</option>
                            <option value="application" <?= $filterType === 'application' ? 'selected' : '' ?>>📄 Dokumen</option>
                        </select>
                        
                        <select id="filterUploader" class="toolbar-select">
                            <option value="">👤 Semua Uploader</option>
                            <?php foreach ($uniqueUploaders as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $filterUploader == $u['id'] ? 'selected' : '' ?>>
                                <?= clean($u['nama_lengkap']) ?>
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
                            <a href="?sort=filename&dir=ASC" class="sort-item <?= $sortBy === 'filename' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Nama A-Z
                            </a>
                            <a href="?sort=ukuran&dir=DESC" class="sort-item <?= $sortBy === 'ukuran' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-weight-hanging"></i> Ukuran Terbesar
                            </a>
                            <a href="?sort=ukuran&dir=ASC" class="sort-item <?= $sortBy === 'ukuran' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-weight-hanging"></i> Ukuran Terkecil
                            </a>
                        </div>
                    </div>
                    
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid" title="Grid View">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button class="view-btn" data-view="masonry" title="Masonry View">
                            <i class="fas fa-th"></i>
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
            <div class="bulk-bar" id="bulkBar">
                <div class="bulk-info">
                    <i class="fas fa-check-circle"></i>
                    <span><strong id="selectedCount">0</strong> file dipilih</span>
                </div>
                <div class="bulk-actions">
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('bulk-delete')">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        Batal
                    </button>
                </div>
            </div>

            <!-- Media Grid -->
            <div class="media-grid" id="mediaGrid" data-view="grid">
                <?php if (!empty($mediaList)): ?>
                    <?php foreach ($mediaList as $m): 
                        $isImage = strpos($m['mime_type'], 'image') !== false;
                        $typeCfg = getFileTypeInfo($m['mime_type']);
                        $fileUrl = '../uploads/' . $m['path'];
                    ?>
                    <div class="media-card" data-id="<?= $m['id'] ?>">
                        <!-- Checkbox -->
                        <label class="card-checkbox">
                            <input type="checkbox" class="media-check" value="<?= $m['id'] ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <!-- Type Badge -->
                        <div class="mc-type-badge" style="background: <?= $typeCfg['color'] ?>; top: 14px; right: 54px;">
                            <?= strtoupper($typeCfg['label']) ?>
                        </div>
                        
                        <!-- Image/Icon Area -->
                        <div class="mc-image" onclick="viewMedia(<?= htmlspecialchars(json_encode($m, JSON_HEX_APOS)) ?>)">
                            <?php if ($isImage): ?>
                                <img src="<?= htmlspecialchars($fileUrl) ?>" alt="<?= clean($m['alt_text'] ?: $m['filename']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="mc-placeholder">
                                    <i class="fas <?= $typeCfg['icon'] ?>" style="color: <?= $typeCfg['color'] ?>"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mc-overlay">
                                <div class="mc-quick-actions" onclick="event.stopPropagation()">
                                    <a href="<?= htmlspecialchars($fileUrl) ?>" download title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button onclick="copyUrl('<?= htmlspecialchars($fileUrl) ?>')" title="Copy URL">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button onclick="viewMedia(<?= htmlspecialchars(json_encode($m, JSON_HEX_APOS)) ?>)" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="?action=delete&id=<?= $m['id'] ?>" title="Hapus" data-confirm="Yakin hapus file ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="mc-body">
                            <h3 class="mc-title" title="<?= clean($m['filename']) ?>"><?= clean($m['filename']) ?></h3>
                            
                            <?php if ($m['alt_text']): ?>
                            <p class="mc-alt" title="<?= clean($m['alt_text']) ?>"><?= clean(mb_substr($m['alt_text'], 0, 50)) ?><?= strlen($m['alt_text']) > 50 ? '...' : '' ?></p>
                            <?php endif; ?>
                            
                            <div class="mc-meta">
                                <div class="mc-stat">
                                    <i class="fas fa-weight-hanging"></i>
                                    <span><?= formatSize($m['ukuran']) ?></span>
                                </div>
                                <div class="mc-stat">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= function_exists('formatTanggal') ? formatTanggal($m['created_at']) : date('d M Y', strtotime($m['created_at'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="mc-footer">
                                <div class="mc-uploader">
                                    <div class="mc-avatar">
                                        <?php 
                                        $initial = getInitials($m['uploader_name']);
                                        $colors = ['#EC4899', '#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#06B6D4'];
                                        $avatarColor = $colors[ord($initial[0]) % count($colors)];
                                        ?>
                                        <span style="background: <?= $avatarColor ?>"><?= $initial ?></span>
                                    </div>
                                    <span class="mc-uploader-name"><?= clean($m['uploader_name'] ?: 'Admin') ?></span>
                                </div>
                                <button class="mc-copy-btn" onclick="copyUrl('<?= htmlspecialchars($fileUrl) ?>')" title="Copy URL">
                                    <i class="fas fa-copy"></i>
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
                                <rect x="40" y="40" width="120" height="80" rx="8" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <circle cx="70" cy="70" r="10" fill="#FCD34D"/>
                                <path d="M 40 110 L 80 80 L 120 100 L 160 75 L 160 120 L 40 120 Z" fill="#EC4899" opacity="0.3"/>
                                <circle cx="155" cy="45" r="18" fill="#EC4899" opacity="0.9"/>
                                <line x1="148" y1="45" x2="162" y2="45" stroke="white" stroke-width="3" stroke-linecap="round"/>
                                <line x1="155" y1="38" x2="155" y2="52" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Media Library Kosong</h3>
                        <p>Upload gambar atau file media untuk digunakan di konten website</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openUploadModal()">
                                <i class="fas fa-cloud-upload-alt"></i> Upload Media Pertama
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- List View (hidden by default) -->
            <?php if (!empty($mediaList)): ?>
            <div class="media-list-view" id="mediaList" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="selectAllTable"></th>
                                    <th>Preview</th>
                                    <th>Filename</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Uploader</th>
                                    <th>Date</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mediaList as $m): 
                                    $isImage = strpos($m['mime_type'], 'image') !== false;
                                    $typeCfg = getFileTypeInfo($m['mime_type']);
                                    $fileUrl = '../uploads/' . $m['path'];
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="media-check" value="<?= $m['id'] ?>"></td>
                                    <td>
                                        <?php if ($isImage): ?>
                                            <img src="<?= htmlspecialchars($fileUrl) ?>" alt="" style="width:50px;height:50px;border-radius:8px;object-fit:cover;cursor:pointer" onclick="viewMedia(<?= htmlspecialchars(json_encode($m, JSON_HEX_APOS)) ?>)">
                                        <?php else: ?>
                                            <div style="width:50px;height:50px;border-radius:8px;background:<?= $typeCfg['color'] ?>15;display:flex;align-items:center;justify-content:center">
                                                <i class="fas <?= $typeCfg['icon'] ?>" style="color:<?= $typeCfg['color'] ?>"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= clean($m['filename']) ?></strong>
                                        <?php if ($m['alt_text']): ?>
                                        <div style="font-size:0.72rem;color:var(--gray-500);margin-top:2px"><?= clean(mb_substr($m['alt_text'], 0, 40)) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:<?= $typeCfg['color'] ?>15;color:<?= $typeCfg['color'] ?>">
                                            <i class="fas <?= $typeCfg['icon'] ?>"></i> <?= $typeCfg['label'] ?>
                                        </span>
                                    </td>
                                    <td><?= formatSize($m['ukuran']) ?></td>
                                    <td><?= clean($m['uploader_name'] ?: 'Unknown') ?></td>
                                    <td style="font-size:0.82rem"><?= function_exists('formatTanggal') ? formatTanggal($m['created_at']) : date('d M Y', strtotime($m['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?= htmlspecialchars($fileUrl) ?>" download class="btn-icon btn-sm" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button class="btn-icon btn-sm" onclick="copyUrl('<?= htmlspecialchars($fileUrl) ?>')" title="Copy URL">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <button class="btn-icon btn-sm" onclick="viewMedia(<?= htmlspecialchars(json_encode($m, JSON_HEX_APOS)) ?>)" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="?action=delete&id=<?= $m['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus file ini?" title="Hapus">
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
                <span>Menampilkan <strong><?= count($mediaList) ?></strong> file</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="media-sidebar">
            <!-- Storage Usage -->
            <div class="card widget-card storage-widget">
                <div class="widget-header">
                    <h3><i class="fas fa-hdd"></i> Storage</h3>
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

            <!-- File Types -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-layer-group"></i> Tipe File</h3>
                    <span class="widget-badge"><?= count($typeStats) ?></span>
                </div>
                <div class="widget-body">
                    <?php if (!empty($typeStats)): 
                        $maxType = max(array_column($typeStats, 'total'));
                    ?>
                        <?php foreach ($typeStats as $ts): 
                            $pct = round(($ts['total'] / $maxType) * 100);
                            $cfg = getFileTypeInfo($ts['mime_type']);
                        ?>
                        <div class="type-stat-item">
                            <div class="tsi-head">
                                <span class="tsi-name" style="color: <?= $cfg['color'] ?>">
                                    <i class="fas <?= $cfg['icon'] ?>"></i>
                                    <?= $cfg['label'] ?>
                                </span>
                                <span class="tsi-count"><?= $ts['total'] ?></span>
                            </div>
                            <div class="tsi-bar">
                                <div class="tsi-bar-fill" style="width: <?= $pct ?>%; background: <?= $cfg['color'] ?>"></div>
                            </div>
                            <div class="tsi-meta">
                                <span><i class="fas fa-weight-hanging"></i> <?= formatSize($ts['total_size']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-layer-group"></i>
                            <p>Belum ada data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Uploaders -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-crown"></i> Top Uploaders</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($topUploaders)): 
                        $maxUploader = max(array_column($topUploaders, 'total'));
                        $colors = ['#EC4899', '#8B5CF6', '#3B82F6', '#10B981', '#F59E0B'];
                        foreach ($topUploaders as $i => $u): 
                            $pct = round(($u['total'] / $maxUploader) * 100);
                            $color = $colors[$i % count($colors)];
                            $initial = getInitials($u['nama_lengkap']);
                    ?>
                        <div class="uploader-item">
                            <div class="ui-avatar" style="background: <?= $color ?>"><?= $initial ?></div>
                            <div class="ui-info">
                                <div class="ui-name"><?= clean($u['nama_lengkap']) ?></div>
                                <div class="ui-bar">
                                    <div class="ui-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                                </div>
                            </div>
                            <div class="ui-stats">
                                <div class="ui-count"><?= $u['total'] ?></div>
                                <div class="ui-size"><i class="fas fa-weight-hanging"></i> <?= formatSize($u['total_size']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-users"></i>
                            <p>Belum ada uploader</p>
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
                        <div class="recent-grid">
                        <?php foreach ($recentUploads as $ru): 
                            $isImage = strpos($ru['mime_type'], 'image') !== false;
                            $typeCfg = getFileTypeInfo($ru['mime_type']);
                        ?>
                        <div class="recent-thumb" onclick="viewMedia(<?= htmlspecialchars(json_encode($ru, JSON_HEX_APOS)) ?>)">
                            <?php if ($isImage): ?>
                                <img src="../uploads/<?= htmlspecialchars($ru['path']) ?>" alt="<?= clean($ru['filename']) ?>">
                            <?php else: ?>
                                <div class="rt-placeholder" style="background: <?= $typeCfg['color'] ?>15; color: <?= $typeCfg['color'] ?>">
                                    <i class="fas <?= $typeCfg['icon'] ?>"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-clock"></i>
                            <p>Belum ada upload</p>
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
                    <button class="qa-btn primary" onclick="openUploadModal()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Upload Media</span>
                    </button>
                    <a href="berita.php" class="qa-btn">
                        <i class="fas fa-newspaper"></i>
                        <span>Kelola Berita</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="dokumen.php" class="qa-btn">
                        <i class="fas fa-folder-open"></i>
                        <span>Dokumen</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="galeri.php" class="qa-btn">
                        <i class="fas fa-photo-video"></i>
                        <span>Galeri Foto</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Supported Formats -->
            <div class="card widget-card formats-card">
                <div class="widget-header">
                    <h3><i class="fas fa-info-circle"></i> Format Didukung</h3>
                </div>
                <div class="widget-body">
                    <div class="formats-grid">
                        <div class="format-item jpg"><i class="fas fa-file-image"></i><span>JPG</span></div>
                        <div class="format-item png"><i class="fas fa-file-image"></i><span>PNG</span></div>
                        <div class="format-item gif"><i class="fas fa-file-image"></i><span>GIF</span></div>
                        <div class="format-item webp"><i class="fas fa-file-image"></i><span>WEBP</span></div>
                        <div class="format-item svg"><i class="fas fa-file-code"></i><span>SVG</span></div>
                    </div>
                    <div class="formats-note">
                        <i class="fas fa-info-circle"></i>
                        Maksimal ukuran: <strong>5 MB/file</strong>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== UPLOAD MODAL ===== -->
<div class="modal" id="uploadModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient media-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                <div>
                    <h3>Upload Media</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Unggah gambar untuk digunakan di konten website</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeUploadModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm" class="modal-body">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-sections">
                <!-- Alt Text -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-tag"></i> Alt Text (Opsional)</h4>
                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fas fa-tag"></i>
                            <input type="text" name="alt_text" id="altText" placeholder="Deskripsi gambar untuk SEO dan accessibility...">
                        </div>
                        <small class="form-hint">Alt text membantu SEO dan accessibility untuk pengguna screen reader</small>
                    </div>
                </div>

                <!-- Upload Area -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-images"></i> Pilih File <small style="color:var(--gray-500);font-weight:400">(Bisa multiple)</small></h4>
                    <div class="upload-area upload-large" id="uploadArea">
                        <div class="upload-placeholder-large" id="uploadPlaceholder">
                            <div class="upload-icon-large">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h4>Drag & drop file di sini</h4>
                            <p>atau klik untuk memilih file</p>
                            <div class="upload-formats">
                                <span class="uf-item jpg"><i class="fas fa-file-image"></i> JPG</span>
                                <span class="uf-item png"><i class="fas fa-file-image"></i> PNG</span>
                                <span class="uf-item gif"><i class="fas fa-file-image"></i> GIF</span>
                                <span class="uf-item webp"><i class="fas fa-file-image"></i> WebP</span>
                                <span class="uf-item svg"><i class="fas fa-file-code"></i> SVG</span>
                            </div>
                            <small class="upload-note">Maksimal ukuran: 5 MB per file</small>
                        </div>
                        <input type="file" name="media_files[]" id="mediaFiles" multiple accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer">
                    </div>
                    
                    <!-- Preview Grid -->
                    <div class="preview-grid" id="previewGrid" style="display:none; margin-top: 16px;">
                        <div class="pg-header">
                            <span id="fileCount">0 file dipilih</span>
                            <span id="totalSize">0 KB</span>
                            <button type="button" class="pg-clear" onclick="clearFiles()">
                                <i class="fas fa-times"></i> Clear All
                            </button>
                        </div>
                        <div class="pg-items" id="previewItems"></div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary" id="uploadBtn">
                    <i class="fas fa-upload"></i> <span>Upload Files</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== VIEW MODAL ===== -->
<div class="modal" id="viewModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient media-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-eye"></i></div>
                <div>
                    <h3 id="viewTitle">Detail Media</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px" id="viewSubtitle"></p>
                </div>
            </div>
            <button class="modal-close" onclick="closeViewModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="viewBody"></div>
    </div>
</div>

<!-- ===== COPY URL MODAL ===== -->
<div class="modal" id="copyUrlModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header modal-header-gradient media-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-link"></i></div>
                <div>
                    <h3>Copy URL</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Gunakan URL ini untuk embed di konten</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeCopyUrlModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="url-box">
                <div class="url-label">
                    <i class="fas fa-link"></i> Full URL
                </div>
                <div class="url-input-group">
                    <input type="text" id="copyUrlInput" readonly class="url-input">
                    <button class="url-copy-btn" onclick="doCopy()">
                        <i class="fas fa-copy"></i>
                        <span id="copyBtnText">Copy</span>
                    </button>
                </div>
            </div>
            
            <div class="url-box">
                <div class="url-label">
                    <i class="fas fa-code"></i> HTML Embed
                </div>
                <div class="url-input-group">
                    <input type="text" id="embedInput" readonly class="url-input" style="font-family:'Courier New',monospace">
                    <button class="url-copy-btn" onclick="doCopyEmbed()">
                        <i class="fas fa-copy"></i>
                        <span id="embedBtnText">Copy</span>
                    </button>
                </div>
            </div>
            
            <div class="url-box">
                <div class="url-label">
                    <i class="fas fa-markdown"></i> Markdown
                </div>
                <div class="url-input-group">
                    <input type="text" id="markdownInput" readonly class="url-input" style="font-family:'Courier New',monospace">
                    <button class="url-copy-btn" onclick="doCopyMarkdown()">
                        <i class="fas fa-copy"></i>
                        <span id="markdownBtnText">Copy</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================================
   MEDIA PAGE - MEDIA LIBRARY EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.media-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.media-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #831843 0%, #BE185D 50%, #EC4899 100%);
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
    background: radial-gradient(circle, #F9A8D4, transparent);
    top: -120px; right: 8%;
}

.orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, #F472B6, transparent);
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

.hero-image-pattern {
    position: absolute;
    top: 50%;
    right: 5%;
    transform: translateY(-50%);
    font-size: 18rem;
    color: rgba(255,255,255,0.04);
    pointer-events: none;
}

.hero-image-pattern::before {
    content: '\f03e';
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
    color: #FBCFE8;
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
    background: linear-gradient(135deg, #fff, #FDF2F8);
    color: #BE185D;
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
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.btn-hero-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.3);
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
.media-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.media-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.media-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-pink .stat-icon { background: linear-gradient(135deg, #EC4899, #BE185D); }
.stat-purple .stat-icon { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }
.stat-cyan .stat-icon { background: linear-gradient(135deg, #06B6D4, #0891B2); }
.stat-rose .stat-icon { background: linear-gradient(135deg, #F43F5E, #BE123C); }

/* ===== TOOLBAR ===== */
.media-toolbar {
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
    border-color: #EC4899;
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(236,72,153,0.1);
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
    border-color: #EC4899;
    box-shadow: 0 0 0 3px rgba(236,72,153,0.1);
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
    background: #EC4899;
    color: var(--white);
    border-color: #EC4899;
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
    background: rgba(236,72,153,0.1);
    color: #BE185D;
    font-weight: 600;
}

.sort-item i {
    width: 16px;
    color: var(--gray-500);
}

.sort-item.active i {
    color: #EC4899;
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
    color: #EC4899;
    box-shadow: var(--shadow-sm);
}

/* ===== BULK BAR ===== */
.bulk-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: linear-gradient(135deg, #BE185D, #EC4899);
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

/* ===== MEDIA GRID ===== */
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.media-grid[data-view="masonry"] {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    grid-auto-rows: 10px;
}

.media-grid[data-view="masonry"] .media-card {
    grid-row-end: span 25;
}

.media-card {
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

[data-theme="dark"] .media-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.media-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(236,72,153,0.3);
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

.media-card:hover .card-checkbox,
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
    background: #EC4899;
    border-color: #EC4899;
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

/* Type Badge */
.mc-type-badge {
    position: absolute;
    padding: 3px 10px;
    color: var(--white);
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    z-index: 12;
}

/* Image Area */
.mc-image {
    position: relative;
    height: 180px;
    background: var(--gray-100);
    overflow: hidden;
    cursor: pointer;
}

.mc-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.media-card:hover .mc-image img {
    transform: scale(1.08);
}

.mc-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    background: var(--gray-50);
}

.mc-overlay {
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

.media-card:hover .mc-overlay {
    opacity: 1;
}

.mc-quick-actions {
    display: flex;
    gap: 8px;
}

.mc-quick-actions button,
.mc-quick-actions a {
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

.mc-quick-actions button:hover,
.mc-quick-actions a:hover {
    background: #EC4899;
    color: var(--white);
    transform: translateY(-3px);
}

/* Card Body */
.mc-body {
    padding: 16px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.mc-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 6px;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

[data-theme="dark"] .mc-title {
    color: var(--white);
}

.mc-alt {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-bottom: 10px;
    line-height: 1.4;
    font-style: italic;
}

[data-theme="dark"] .mc-alt {
    color: var(--gray-400);
}

.mc-meta {
    display: flex;
    gap: 12px;
    padding: 8px 0;
    border-top: 1px solid var(--gray-100);
    border-bottom: 1px solid var(--gray-100);
    margin-bottom: 10px;
}

[data-theme="dark"] .mc-meta {
    border-color: rgba(255,255,255,0.08);
}

.mc-stat {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    color: var(--gray-600);
    font-weight: 600;
}

[data-theme="dark"] .mc-stat {
    color: var(--gray-400);
}

.mc-stat i {
    font-size: 0.68rem;
    color: #EC4899;
}

.mc-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-top: auto;
}

.mc-uploader {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 0;
}

.mc-avatar span {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-weight: 700;
    font-size: 0.7rem;
    font-family: 'Space Grotesk', sans-serif;
    flex-shrink: 0;
}

.mc-uploader-name {
    font-size: 0.75rem;
    color: var(--gray-600);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

[data-theme="dark"] .mc-uploader-name {
    color: var(--gray-400);
}

.mc-copy-btn {
    width: 30px;
    height: 30px;
    border-radius: var(--radius-sm);
    background: rgba(236,72,153,0.1);
    color: #EC4899;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
    flex-shrink: 0;
    font-size: 0.78rem;
}

.mc-copy-btn:hover {
    background: #EC4899;
    color: var(--white);
    transform: translateY(-2px);
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

.result-info {
    text-align: center;
    padding: 12px;
    color: var(--gray-600);
    font-size: 0.85rem;
}

.result-info strong {
    color: #EC4899;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== MEDIA LIST VIEW ===== */
.media-list-view .data-table {
    font-size: 0.85rem;
}

.media-list-view .data-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 700;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .media-list-view .data-table th {
    background: var(--dark);
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

.media-list-view .data-table td {
    padding: 10px 16px;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

/* ===== SIDEBAR WIDGETS ===== */
.media-sidebar {
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
    color: #EC4899;
}

.widget-badge {
    padding: 3px 10px;
    background: #EC4899;
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
    background: linear-gradient(135deg, rgba(236,72,153,0.05), rgba(190,24,93,0.05));
    border: 1px solid rgba(236,72,153,0.15);
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

.si-row strong {
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
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
    font-size: 0.85rem;
    font-weight: 600;
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

/* ===== UPLOADER ITEMS ===== */
.uploader-item {
    display: grid;
    grid-template-columns: 36px 1fr auto;
    gap: 10px;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .uploader-item {
    border-color: rgba(255,255,255,0.05);
}

.uploader-item:last-child {
    border-bottom: none;
}

.ui-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-weight: 700;
    font-size: 0.78rem;
    font-family: 'Space Grotesk', sans-serif;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.ui-info {
    min-width: 0;
}

.ui-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

[data-theme="dark"] .ui-name {
    color: var(--gray-200);
}

.ui-bar {
    height: 4px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .ui-bar {
    background: rgba(255,255,255,0.08);
}

.ui-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

.ui-stats {
    text-align: right;
}

.ui-count {
    font-size: 1rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
    line-height: 1;
}

[data-theme="dark"] .ui-count {
    color: var(--white);
}

.ui-size {
    font-size: 0.68rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 3px;
    margin-top: 2px;
    justify-content: flex-end;
}

.ui-size i {
    color: #EC4899;
    font-size: 0.6rem;
}

/* ===== RECENT THUMBS ===== */
.recent-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 6px;
}

.recent-thumb {
    aspect-ratio: 1;
    border-radius: var(--radius-sm);
    overflow: hidden;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.recent-thumb:hover {
    transform: scale(1.08);
    box-shadow: var(--shadow-md);
}

.recent-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.rt-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
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
    background: linear-gradient(135deg, #EC4899, #BE185D);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(236,72,153,0.3);
}

.qa-btn.primary:hover {
    box-shadow: 0 6px 16px rgba(236,72,153,0.4);
}

.qa-btn i:first-child {
    color: #EC4899;
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
    background: linear-gradient(135deg, rgba(236,72,153,0.04), rgba(190,24,93,0.04));
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

.format-item.jpg { background: rgba(236,72,153,0.12); color: #EC4899; }
.format-item.png { background: rgba(139,92,246,0.12); color: #8B5CF6; }
.format-item.gif { background: rgba(245,158,11,0.12); color: #F59E0B; }
.format-item.webp { background: rgba(6,182,212,0.12); color: #06B6D4; }
.format-item.svg { background: rgba(16,185,129,0.12); color: #10B981; }

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
    color: #EC4899;
}

.formats-note strong {
    color: #EC4899;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== MODAL ===== */
.media-modal-head {
    background: linear-gradient(135deg, #831843 0%, #BE185D 60%, #EC4899 100%) !important;
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
    border-color: #EC4899;
    background: rgba(236,72,153,0.04);
}

.upload-large.dragover {
    border-color: #EC4899;
    background: rgba(236,72,153,0.08);
    transform: scale(1.01);
}

.upload-placeholder-large {
    color: var(--gray-600);
}

.upload-icon-large {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, rgba(236,72,153,0.15), rgba(190,24,93,0.1));
    color: #EC4899;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 16px;
    box-shadow: 0 8px 20px rgba(236,72,153,0.2);
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

.uf-item.jpg { background: rgba(236,72,153,0.12); color: #EC4899; }
.uf-item.png { background: rgba(139,92,246,0.12); color: #8B5CF6; }
.uf-item.gif { background: rgba(245,158,11,0.12); color: #F59E0B; }
.uf-item.webp { background: rgba(6,182,212,0.12); color: #06B6D4; }
.uf-item.svg { background: rgba(16,185,129,0.12); color: #10B981; }

.upload-note {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500);
    font-style: italic;
}

/* ===== PREVIEW GRID ===== */
.preview-grid {
    background: var(--gray-50);
    border-radius: var(--radius);
    padding: 14px;
}

[data-theme="dark"] .preview-grid {
    background: rgba(255,255,255,0.04);
}

.pg-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--gray-200);
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
}

[data-theme="dark"] .pg-header {
    border-color: rgba(255,255,255,0.08);
    color: var(--white);
}

.pg-header span:nth-child(2) {
    margin-left: auto;
    color: var(--gray-500);
    font-weight: 500;
    font-size: 0.78rem;
}

.pg-clear {
    background: transparent;
    border: 1px solid var(--gray-300);
    color: var(--gray-600);
    padding: 4px 10px;
    border-radius: var(--radius-sm);
    font-size: 0.72rem;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-fast);
    font-family: inherit;
}

.pg-clear:hover {
    background: #EF4444;
    color: var(--white);
    border-color: #EF4444;
}

.pg-items {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    max-height: 300px;
    overflow-y: auto;
}

.pg-item {
    aspect-ratio: 1;
    border-radius: var(--radius-sm);
    overflow: hidden;
    position: relative;
    background: var(--gray-200);
}

.pg-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pg-item-name {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 4px 6px;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    color: var(--white);
    font-size: 0.68rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ===== VIEW MODAL ===== */
.view-image-hero {
    max-height: 400px;
    width: 100%;
    object-fit: contain;
    background: var(--gray-100);
    border-radius: var(--radius);
    margin-bottom: 20px;
}

.view-file-hero {
    padding: 60px 20px;
    background: var(--gray-50);
    border-radius: var(--radius);
    text-align: center;
    margin-bottom: 20px;
}

.view-file-hero i {
    font-size: 5rem;
}

.view-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 14px;
    background: var(--gray-50);
    border-radius: var(--radius);
    margin-bottom: 16px;
}

[data-theme="dark"] .view-info-grid {
    background: rgba(255,255,255,0.04);
}

.view-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-500);
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.view-value {
    font-size: 0.92rem;
    color: var(--dark);
    font-weight: 500;
    word-break: break-all;
}

[data-theme="dark"] .view-value {
    color: var(--gray-200);
}

.view-actions {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
    margin-top: 16px;
}

.view-action-btn {
    padding: 12px 14px;
    border-radius: var(--radius);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all var(--transition);
    cursor: pointer;
    border: none;
    font-family: inherit;
}

.view-action-btn.primary {
    background: linear-gradient(135deg, #EC4899, #BE185D);
    color: var(--white);
}

.view-action-btn.secondary {
    background: var(--gray-100);
    color: var(--dark);
    border: 1px solid var(--gray-200);
}

[data-theme="dark"] .view-action-btn.secondary {
    background: rgba(255,255,255,0.05);
    color: var(--gray-200);
    border-color: rgba(255,255,255,0.1);
}

.view-action-btn.danger {
    background: rgba(239,68,68,0.1);
    color: #EF4444;
    border: 1px solid rgba(239,68,68,0.2);
}

.view-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.view-alt-box {
    padding: 14px;
    background: rgba(236,72,153,0.05);
    border-left: 4px solid #EC4899;
    border-radius: var(--radius-sm);
    margin-top: 16px;
    font-size: 0.88rem;
    color: var(--gray-700);
    line-height: 1.5;
    font-style: italic;
}

[data-theme="dark"] .view-alt-box {
    color: var(--gray-300);
}

.view-alt-box i {
    color: #EC4899;
    margin-right: 6px;
}

/* ===== COPY URL MODAL ===== */
.url-box {
    margin-bottom: 16px;
}

.url-box:last-child {
    margin-bottom: 0;
}

.url-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--gray-700);
    margin-bottom: 8px;
}

[data-theme="dark"] .url-label {
    color: var(--gray-300);
}

.url-label i {
    color: #EC4899;
}

.url-input-group {
    display: flex;
    gap: 6px;
    background: var(--gray-50);
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    padding: 4px;
}

[data-theme="dark"] .url-input-group {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
}

.url-input {
    flex: 1;
    padding: 8px 12px;
    background: transparent;
    border: none;
    font-size: 0.82rem;
    font-family: inherit;
    color: var(--dark);
    min-width: 0;
}

[data-theme="dark"] .url-input {
    color: var(--white);
}

.url-input:focus {
    outline: none;
}

.url-copy-btn {
    padding: 8px 14px;
    background: #EC4899;
    color: var(--white);
    border: none;
    border-radius: var(--radius-sm);
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all var(--transition-fast);
    font-family: inherit;
    flex-shrink: 0;
}

.url-copy-btn:hover {
    background: #BE185D;
}

.url-copy-btn.copied {
    background: #10B981;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .media-layout {
        grid-template-columns: 1fr;
    }
    
    .media-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .media-page {
        padding: 16px;
    }
    
    .media-hero {
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
    
    .media-toolbar {
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
    
    .media-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    }
    
    .view-info-grid {
        grid-template-columns: 1fr;
    }
    
    .view-actions {
        grid-template-columns: 1fr;
    }
    
    .storage-visual {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .storage-circle {
        margin: 0 auto;
    }
    
    .recent-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media print {
    .media-hero,
    .media-sidebar,
    .media-toolbar,
    .bulk-bar,
    .mc-quick-actions,
    .mc-copy-btn,
    .card-checkbox {
        display: none !important;
    }
    
    .media-layout {
        grid-template-columns: 1fr;
    }
    
    .media-card {
        break-inside: avoid;
    }
}
</style>

<script>
(function() {
    'use strict';

    const allMedia = <?= json_encode($mediaList) ?>;

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

    function formatSize(bytes) {
        if (!bytes) return '0 B';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
        if (bytes < 1024*1024*1024) return (bytes/(1024*1024)).toFixed(2) + ' MB';
        return (bytes/(1024*1024*1024)).toFixed(2) + ' GB';
    }

    /* ===== UPLOAD MODAL ===== */
    window.openUploadModal = function() {
        document.getElementById('uploadForm').reset();
        document.getElementById('previewGrid').style.display = 'none';
        document.getElementById('previewItems').innerHTML = '';
        document.getElementById('fileCount').textContent = '0 file dipilih';
        document.getElementById('totalSize').textContent = '0 KB';
        document.getElementById('uploadModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closeUploadModal = function() {
        document.getElementById('uploadModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.closeViewModal = function() {
        document.getElementById('viewModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.closeCopyUrlModal = function() {
        document.getElementById('copyUrlModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    /* ===== VIEW MEDIA ===== */
    window.viewMedia = function(data) {
        const isImage = (data.mime_type || '').includes('image');
        const fileUrl = '../uploads/' + data.path;
        const fullUrl = window.location.origin + '/' + fileUrl.replace(/^\.\.\//, '');
        
        document.getElementById('viewTitle').textContent = data.filename;
        document.getElementById('viewSubtitle').textContent = `Uploaded by ${data.uploader_name || 'Admin'}`;
        
        const body = document.getElementById('viewBody');
        body.innerHTML = `
            ${isImage 
                ? `<img src="${fileUrl}" alt="${data.alt_text || data.filename}" class="view-image-hero">`
                : `<div class="view-file-hero"><i class="fas fa-file" style="color:var(--gray-400)"></i></div>`
            }
            
            <div class="view-info-grid">
                <div>
                    <div class="view-label"><i class="fas fa-file"></i> Filename</div>
                    <div class="view-value" style="font-family:'Courier New',monospace;font-size:0.82rem">${data.filename}</div>
                </div>
                <div>
                    <div class="view-label"><i class="fas fa-layer-group"></i> MIME Type</div>
                    <div class="view-value">${data.mime_type}</div>
                </div>
                <div>
                    <div class="view-label"><i class="fas fa-weight-hanging"></i> Size</div>
                    <div class="view-value"><strong style="color:#EC4899">${formatSize(data.ukuran)}</strong></div>
                </div>
                <div>
                    <div class="view-label"><i class="fas fa-calendar"></i> Uploaded</div>
                    <div class="view-value">${new Date(data.created_at).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'})}</div>
                </div>
                ${data.alt_text ? `
                <div style="grid-column:1/-1">
                    <div class="view-label"><i class="fas fa-tag"></i> Alt Text</div>
                    <div class="view-value">${data.alt_text}</div>
                </div>
                ` : ''}
            </div>
            
            ${data.alt_text ? `
            <div class="view-alt-box">
                <i class="fas fa-quote-left"></i> ${data.alt_text}
            </div>
            ` : ''}
            
            <div class="view-actions">
                <a href="${fileUrl}" download class="view-action-btn primary">
                    <i class="fas fa-download"></i> Download
                </a>
                <button class="view-action-btn secondary" onclick="openCopyUrlModal('${fullUrl}', '${data.filename}', ${isImage})">
                    <i class="fas fa-copy"></i> Copy URL
                </button>
                <a href="?action=delete&id=${data.id}" class="view-action-btn danger" onclick="return confirm('Yakin hapus file ini?')">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
        `;
        
        document.getElementById('viewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== COPY URL ===== */
    let currentCopyUrl = '';
    let currentFilename = '';
    let currentIsImage = false;

    window.copyUrl = function(url) {
        const fullUrl = window.location.origin + '/' + url.replace(/^\.\.\//, '');
        navigator.clipboard.writeText(fullUrl).then(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show('✓ URL copied to clipboard', 'success');
        });
    };

    window.openCopyUrlModal = function(url, filename, isImage) {
        currentCopyUrl = url;
        currentFilename = filename;
        currentIsImage = isImage;
        
        document.getElementById('copyUrlInput').value = url;
        
        const ext = filename.split('.').pop().toLowerCase();
        const isImageExt = ['jpg','jpeg','png','gif','webp','svg'].includes(ext);
        const embedCode = isImageExt 
            ? `<img src="${url}" alt="${filename}">`
            : `<a href="${url}" download>${filename}</a>`;
        const markdownCode = isImageExt 
            ? `![${filename}](${url})`
            : `[${filename}](${url})`;
        
        document.getElementById('embedInput').value = embedCode;
        document.getElementById('markdownInput').value = markdownCode;
        
        document.getElementById('copyUrlModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    function copyWithFeedback(inputId, btnId) {
        const input = document.getElementById(inputId);
        input.select();
        navigator.clipboard.writeText(input.value).then(() => {
            const btn = document.getElementById(btnId);
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            btn.classList.add('copied');
            setTimeout(() => {
                btn.innerHTML = original;
                btn.classList.remove('copied');
            }, 2000);
            if (window.IFPro?.toast) window.IFPro.toast.show('✓ Copied to clipboard', 'success');
        });
    }

    window.doCopy = function() { copyWithFeedback('copyUrlInput', 'copyBtnText'); };
    window.doCopyEmbed = function() { copyWithFeedback('embedInput', 'embedBtnText'); };
    window.doCopyMarkdown = function() { copyWithFeedback('markdownInput', 'markdownBtnText'); };

    /* ===== FILE UPLOAD HANDLING ===== */
    const uploadArea = document.getElementById('uploadArea');
    const mediaFiles = document.getElementById('mediaFiles');
    const previewGrid = document.getElementById('previewGrid');
    const previewItems = document.getElementById('previewItems');
    const fileCount = document.getElementById('fileCount');
    const totalSize = document.getElementById('totalSize');

    function updatePreview(files) {
        previewItems.innerHTML = '';
        let totalBytes = 0;
        
        Array.from(files).forEach((file, i) => {
            totalBytes += file.size;
            const item = document.createElement('div');
            item.className = 'pg-item';
            
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    item.innerHTML = `
                        <img src="${e.target.result}" alt="${file.name}">
                        <div class="pg-item-name">${file.name}</div>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                item.innerHTML = `
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--gray-400)">
                        <i class="fas fa-file" style="font-size:2rem"></i>
                    </div>
                    <div class="pg-item-name">${file.name}</div>
                `;
            }
            previewItems.appendChild(item);
        });
        
        fileCount.textContent = `${files.length} file dipilih`;
        totalSize.textContent = formatSize(totalBytes);
        previewGrid.style.display = 'block';
    }

    window.clearFiles = function() {
        mediaFiles.value = '';
        previewGrid.style.display = 'none';
        previewItems.innerHTML = '';
    };

    mediaFiles?.addEventListener('change', e => {
        if (e.target.files.length > 0) {
            updatePreview(e.target.files);
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
        if (e.dataTransfer.files.length) {
            mediaFiles.files = e.dataTransfer.files;
            mediaFiles.dispatchEvent(new Event('change'));
        }
    });

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterType = document.getElementById('filterType');
    const filterUploader = document.getElementById('filterUploader');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    [filterType, filterUploader].forEach(el => {
        el?.addEventListener('change', applyFilters);
    });

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const type = filterType?.value;
        const uploader = filterUploader?.value;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (type) url.searchParams.set('type', type); else url.searchParams.delete('type');
        if (uploader) url.searchParams.set('uploader', uploader); else url.searchParams.delete('uploader');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'type', 'uploader', 'sort', 'dir'].forEach(p => url.searchParams.delete(p));
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
            const grid = document.getElementById('mediaGrid');
            const list = document.getElementById('mediaList');
            
            if (view === 'list') {
                grid.style.display = 'none';
                list.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                list.style.display = 'none';
                grid.dataset.view = view;
            }
            
            localStorage.setItem('media_view', view);
        });
    });

    const savedView = localStorage.getItem('media_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');

    document.querySelectorAll('.media-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
        });
    });

    document.getElementById('selectAllTable')?.addEventListener('change', function() {
        document.querySelectorAll('#mediaList .media-check').forEach(c => {
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
        document.querySelectorAll('.media-check').forEach(c => c.checked = false);
        const selectAll = document.getElementById('selectAllTable');
        if (selectAll) selectAll.checked = false;
        updateBulkBar();
    };

    window.bulkAction = function(action) {
        if (selectedIds.size === 0) return;
        
        if (!confirm(`Yakin hapus ${selectedIds.size} file terpilih? File akan dihapus permanen!`)) return;
        
        const ids = Array.from(selectedIds).join(',');
        window.adminSecureAction(`?action=${action}&ids=${ids}`);
    };

    /* ===== EXPORT CSV ===== */
    window.exportMedia = function() {
        if (!allMedia || allMedia.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['ID', 'Filename', 'Path', 'MIME Type', 'Size (bytes)', 'Alt Text', 'Uploader', 'Created At'];
        const rows = allMedia.map(m => [
            m.id,
            `"${(m.filename || '').replace(/"/g, '""')}"`,
            m.path || '',
            m.mime_type || '',
            m.ukuran || 0,
            `"${(m.alt_text || '').replace(/"/g, '""')}"`,
            `"${(m.uploader_name || 'Unknown').replace(/"/g, '""')}"`,
            m.created_at || ''
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `media-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${allMedia.length} file berhasil di-export`, 'success');
        }, 500);
    };

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        // U = Upload
        if (e.key === 'u' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) && !e.ctrlKey && !e.metaKey) {
            e.preventDefault();
            openUploadModal();
        }
        
        // / = Focus search
        if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            searchInput?.focus();
        }
        
        // Escape = Close modals
        if (e.key === 'Escape') {
            closeUploadModal();
            closeViewModal();
            closeCopyUrlModal();
        }
    });

    /* ===== MODAL BACKDROP CLICKS ===== */
    ['uploadModal', 'viewModal', 'copyUrlModal'].forEach(id => {
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

    console.log('%c🖼️ Media Library v4.0 Loaded', 'color:#EC4899;font-weight:bold;font-size:14px');
    console.log('%c   Features: Drag & Drop, Bulk Delete, Copy URL (HTML/Markdown), 3 View Modes', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: U (Upload), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>