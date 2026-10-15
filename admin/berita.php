<?php
require_once 'includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
// ===== FIX: Pastikan semua $_GET keys selalu ada (anti warning PHP 8) =====
$_GET['action'] = $_GET['action'] ?? '';
$_GET['id']     = $_GET['id'] ?? 0;
$_GET['q']      = $_GET['q'] ?? '';
$_GET['status'] = $_GET['status'] ?? '';
$_GET['kategori'] = $_GET['kategori'] ?? '';
$_GET['date']   = $_GET['date'] ?? '';
$_GET['featured'] = $_GET['featured'] ?? '';
$_GET['sort']   = $_GET['sort'] ?? 'created_at';
$_GET['dir']    = $_GET['dir'] ?? 'DESC';
$_GET['ids']    = $_GET['ids'] ?? '';

$pageTitle = 'Kelola Berita';

Auth::requireAuth(); // Pastikan user sudah login

$pageTitle = 'Kelola Berita';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
    setFlash('error', 'Koneksi database gagal');
}

$currentAdminId = Auth::id();
// ===== SAFE GET VARIABLES (Mencegah Warning PHP 8) =====
$action = $_GET['action'] ?? '';
$id_get = (int)($_GET['id'] ?? 0);

/**
 * Helper: Invalidate cache untuk berita
 * Dipanggil setiap kali ada perubahan data berita
 */
function invalidateBeritaCache() {
    // Clear semua cache yang terkait berita
    cacheForgetPattern('berita');
    cacheForgetPattern('home_news');
    cacheForgetPattern('related_news');
    cacheForget('dashboard_stats');
    
    // Log cache invalidation
    if (APP_DEBUG) {
        error_log("Berita cache invalidated by user: " . (Auth::user()['username'] ?? 'unknown'));
    }
}

/* ===== HANDLE ACTIONS ===== */
if (isset($_GET['action']) && $db) {
    $id = (int)($_GET['id'] ?? 0);
    
    // ===== DELETE BERITA =====
    if ($_GET['action'] === 'delete' && $id) {
        try {
            // Get berita data untuk logging
            $stmt = $db->prepare("SELECT judul, gambar FROM berita WHERE id = ?");
            $stmt->execute([$id]);
            $berita = $stmt->fetch();
            
            if (!$berita) {
                setFlash('error', 'Berita tidak ditemukan');
                header('Location: berita.php');
                exit;
            }
            
            // Hapus file gambar jika ada
            if ($berita['gambar'] && file_exists(__DIR__ . '/../uploads/' . $berita['gambar'])) {
                @unlink(__DIR__ . '/../uploads/' . $berita['gambar']);
            }
            
            // Delete dari database
            $db->prepare("DELETE FROM berita WHERE id = ?")->execute([$id]);
            
            // Invalidate cache
            invalidateBeritaCache();
            
            // Log activity
            act_log('berita', 'delete', $id, "Deleted: " . $berita['judul']);
            
            setFlash('success', 'Berita "' . clean($berita['judul']) . '" berhasil dihapus');
            
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus berita: ' . $e->getMessage());
        }
        
        header('Location: berita.php');
        exit;
    }
    
    // ===== TOGGLE STATUS (PUBLISH/DRAFT) =====
    if ($_GET['action'] === 'toggle' && $id) {
        try {
            // Get current status untuk logging
            $stmt = $db->prepare("SELECT judul, status FROM berita WHERE id = ?");
            $stmt->execute([$id]);
            $berita = $stmt->fetch();
            
            if (!$berita) {
                setFlash('error', 'Berita tidak ditemukan');
                header('Location: berita.php');
                exit;
            }
            
            $newStatus = $berita['status'] === 'published' ? 'draft' : 'published';
            
            $db->prepare("UPDATE berita SET status = ?, published_at = IF(?='published',NOW(),NULL), updated_at = NOW() WHERE id = ?")
               ->execute([$newStatus, $newStatus, $id]);
            
            // Invalidate cache
            invalidateBeritaCache();
            
            // Log activity
            act_log('berita', 'toggle_status', $id, "Changed status to $newStatus: " . $berita['judul']);
            
            setFlash('success', 'Status berita berhasil diubah ke ' . ucfirst($newStatus));
            
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
        
        header('Location: berita.php');
        exit;
    }
    
    // ===== BULK ACTIONS =====
    if (in_array($_GET['action'], ['bulk-publish', 'bulk-draft', 'bulk-delete']) && !empty($_GET['ids'])) {
        try {
            $ids = array_map('intval', explode(',', $_GET['ids']));
            
            if (empty($ids)) {
                setFlash('error', 'Tidak ada berita yang dipilih');
                header('Location: berita.php');
                exit;
            }
            
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            if ($_GET['action'] === 'bulk-delete') {
                // Get data untuk logging dan delete files
                $stmt = $db->prepare("SELECT id, judul, gambar FROM berita WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $deletedBerita = $stmt->fetchAll();
                
                // Delete files
                foreach ($deletedBerita as $row) {
                    if ($row['gambar'] && file_exists(__DIR__ . '/../uploads/' . $row['gambar'])) {
                        @unlink(__DIR__ . '/../uploads/' . $row['gambar']);
                    }
                }
                
                // Delete dari database
                $db->prepare("DELETE FROM berita WHERE id IN ($placeholders)")->execute($ids);
                
                $judulList = array_slice(array_column($deletedBerita, 'judul'), 0, 3);
                $logMessage = "Bulk deleted " . count($ids) . " berita: " . implode(', ', $judulList) . (count($deletedBerita) > 3 ? '...' : '');
                
                setFlash('success', count($ids) . ' berita berhasil dihapus');
                
            } elseif ($_GET['action'] === 'bulk-publish') {
                $db->prepare("UPDATE berita SET status = 'published', published_at = IFNULL(published_at, NOW()), updated_at = NOW() WHERE id IN ($placeholders)")
                   ->execute($ids);
                
                $logMessage = "Bulk published " . count($ids) . " berita";
                setFlash('success', count($ids) . ' berita berhasil dipublikasi');
                
            } elseif ($_GET['action'] === 'bulk-draft') {
                $db->prepare("UPDATE berita SET status = 'draft', updated_at = NOW() WHERE id IN ($placeholders)")
                   ->execute($ids);
                
                $logMessage = "Bulk drafted " . count($ids) . " berita";
                setFlash('success', count($ids) . ' berita dijadikan draft');
            }
            
            // Invalidate cache untuk semua bulk actions
            invalidateBeritaCache();
            
            // Log activity
            act_log('berita', $_GET['action'], null, $logMessage);
            
        } catch (Throwable $e) {
            setFlash('error', 'Gagal melakukan bulk action: ' . $e->getMessage());
        }
        
        header('Location: berita.php');
        exit;
    }
}
    
    if ($_GET['action'] === 'toggle' && $id) {
        try {
            $db->prepare("UPDATE berita SET status = IF(status='published','draft','published'), published_at = IF(status='draft',NOW(),NULL), updated_at = NOW() WHERE id = ?")->execute([$id]);
            setFlash('success', 'Status berita berhasil diubah');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mengubah status');
        }
        header('Location: berita.php');
        exit;
    }
    
    // Bulk actions
    if (in_array($_GET['action'], ['bulk-publish', 'bulk-draft', 'bulk-delete']) && !empty($_GET['ids'])) {
        try {
            $ids = array_map('intval', explode(',', $_GET['ids']));
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            if ($_GET['action'] === 'bulk-delete') {
                $stmt = $db->prepare("SELECT gambar FROM berita WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                foreach ($stmt->fetchAll() as $row) {
                    if ($row['gambar'] && file_exists(__DIR__ . '/../uploads/' . $row['gambar'])) {
                        @unlink(__DIR__ . '/../uploads/' . $row['gambar']);
                    }
                }
                $db->prepare("DELETE FROM berita WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' berita berhasil dihapus');
            } elseif ($_GET['action'] === 'bulk-publish') {
                $db->prepare("UPDATE berita SET status = 'published', published_at = IFNULL(published_at, NOW()), updated_at = NOW() WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' berita berhasil dipublikasi');
            } elseif ($_GET['action'] === 'bulk-draft') {
                $db->prepare("UPDATE berita SET status = 'draft', updated_at = NOW() WHERE id IN ($placeholders)")->execute($ids);
                setFlash('success', count($ids) . ' berita dijadikan draft');
            }
        } catch (Throwable $e) {
            setFlash('error', 'Gagal melakukan bulk action');
        }
        header('Location: berita.php');
        exit;
    }


/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    
    // ===== CSRF VERIFICATION (Security Check) =====
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token keamanan tidak valid. Silakan refresh halaman dan coba lagi.');
        header('Location: berita.php');
        exit;
    }
    
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $judul = clean($_POST['judul']);
        $slug = !empty($_POST['slug']) ? clean($_POST['slug']) : generateSlug($judul);
        $kategori = clean($_POST['kategori']);
        $tags = clean($_POST['tags'] ?? '');
        $ringkasan = clean($_POST['ringkasan']);
        $konten = $_POST['konten'] ?? ''; // Don't clean HTML content
        $status = clean($_POST['status'] ?? 'draft');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $published_at = !empty($_POST['published_at']) ? clean($_POST['published_at']) : ($status === 'published' ? date('Y-m-d H:i:s') : null);
        
        // ===== VALIDATION =====
        if (empty($judul)) {
            setFlash('error', 'Judul berita wajib diisi');
            header('Location: berita.php');
            exit;
        }
        
        if (mb_strlen($judul) > 200) {
            setFlash('error', 'Judul maksimal 200 karakter');
            header('Location: berita.php');
            exit;
        }
        
        if (mb_strlen($ringkasan) > 500) {
            setFlash('error', 'Ringkasan maksimal 500 karakter');
            header('Location: berita.php');
            exit;
        }
        
        // ===== UPLOAD GAMBAR =====
        $gambar = $_POST['gambar_lama'] ?? null;
        
        if (!empty($_FILES['gambar']['name'])) {
            $newImage = uploadImage($_FILES['gambar'], 'uploads/berita');
            
            if ($newImage) {
                // Delete old image jika ada
                if ($gambar && file_exists(__DIR__ . '/../' . $gambar)) {
                    @unlink(__DIR__ . '/../' . $gambar);
                }
                $gambar = $newImage;
            } else {
                setFlash('error', 'Gagal upload gambar. Pastikan format dan ukuran sesuai.');
                header('Location: berita.php');
                exit;
            }
        }
        
        // ===== SAVE TO DATABASE =====
        if ($id) {
            // ===== UPDATE BERITA =====
            $stmt = $db->prepare("UPDATE berita SET judul=?, slug=?, kategori=?, tags=?, ringkasan=?, konten=?, gambar=?, status=?, is_featured=?, published_at=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$judul, $slug, $kategori, $tags, $ringkasan, $konten, $gambar, $status, $is_featured, $published_at, $id]);
            
            $action = 'update';
            $message = 'Berita "' . $judul . '" berhasil diperbarui';
            $logMessage = "Updated: $judul (ID: $id)";
            
        } else {
            // ===== CREATE NEW BERITA =====
            $stmt = $db->prepare("INSERT INTO berita (judul, slug, kategori, tags, ringkasan, konten, gambar, status, is_featured, author_id, published_at, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$judul, $slug, $kategori, $tags, $ringkasan, $konten, $gambar, $status, $is_featured, $currentAdminId, $published_at]);
            
            $id = $db->lastInsertId();
            $action = 'create';
            $message = 'Berita baru "' . $judul . '" berhasil ditambahkan';
            $logMessage = "Created: $judul (ID: $id)";
        }
        
        // ===== INVALIDATE CACHE =====
        invalidateBeritaCache();
        
        // ===== LOG ACTIVITY =====
        act_log('berita', $action, $id, $logMessage);
        
        setFlash('success', $message);
        
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan berita: ' . $e->getMessage());
        
        // Log error untuk debugging
        error_log("Berita save error: " . $e->getMessage());
    }
    
    header('Location: berita.php');
    exit;
}

/* ===== FETCH PARAMETERS ===== */
$search = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
$filterStatus = $_GET['status'] ?? '';
$filterCat = $_GET['kategori'] ?? '';
$filterDate = $_GET['date'] ?? '';
$filterFeatured = $_GET['featured'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$sortDir = $_GET['dir'] ?? 'DESC';

$where = "1=1";
$params = [];

if ($filterStatus) { $where .= " AND b.status = ?"; $params[] = $filterStatus; }
if ($filterCat) { $where .= " AND b.kategori = ?"; $params[] = $filterCat; }
if ($filterFeatured === '1') { $where .= " AND b.is_featured = 1"; }
if ($filterDate) { $where .= " AND DATE(b.created_at) = ?"; $params[] = $filterDate; }
if (isset($_GET['q']) && $_GET['q']) { 
    $where .= " AND (b.judul LIKE ? OR b.ringkasan LIKE ? OR b.kategori LIKE ? OR b.tags LIKE ?)"; 
    $params[] = $search; $params[] = $search; $params[] = $search; $params[] = $search;
}

$validSorts = ['created_at', 'published_at', 'judul', 'views', 'kategori'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'created_at';
$sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

/* ===== FETCH DATA ===== */
$beritaList = [];
$categories = [];
$stats = [
    'total' => 0,
    'published' => 0,
    'draft' => 0,
    'featured' => 0,
    'total_views' => 0,
    'today_posts' => 0,
];

if ($db) {
    try {
        $sql = "SELECT b.*, a.nama_lengkap as author_name, a.email as author_email
                FROM berita b 
                LEFT JOIN admins a ON b.author_id = a.id 
                WHERE $where 
                ORDER BY b.$sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $beritaList = $stmt->fetchAll();
        
        $categories = $db->query("SELECT DISTINCT kategori FROM berita WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM berita")->fetchColumn();
        $stats['published'] = (int)$db->query("SELECT COUNT(*) FROM berita WHERE status='published'")->fetchColumn();
        $stats['draft'] = (int)$db->query("SELECT COUNT(*) FROM berita WHERE status='draft'")->fetchColumn();
        $stats['featured'] = (int)$db->query("SELECT COUNT(*) FROM berita WHERE is_featured=1 AND status='published'")->fetchColumn();
        $stats['total_views'] = (int)$db->query("SELECT COALESCE(SUM(views), 0) FROM berita")->fetchColumn();
        $stats['today_posts'] = (int)$db->query("SELECT COUNT(*) FROM berita WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    } catch (Throwable $e) {}
}

/* ===== TOP ARTICLES (by views) ===== */
$topArticles = [];
if ($db) {
    try {
        $topArticles = $db->query("
            SELECT id, judul, views, kategori, gambar, published_at 
            FROM berita 
            WHERE status='published' 
            ORDER BY views DESC 
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== CATEGORY STATS ===== */
$categoryStats = [];
if ($db) {
    try {
        $categoryStats = $db->query("
            SELECT kategori, COUNT(*) as total, SUM(views) as total_views 
            FROM berita 
            WHERE kategori IS NOT NULL AND kategori != ''
            GROUP BY kategori 
            ORDER BY total DESC 
            LIMIT 6
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== WRITING ACTIVITY (7 days) ===== */
$writingActivity = [];
if ($db) {
    try {
        $stmt = $db->query("
            SELECT DATE(created_at) as date, COUNT(*) as total 
            FROM berita 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at) 
            ORDER BY date ASC
        ");
        foreach ($stmt->fetchAll() as $row) {
            $writingActivity[$row['date']] = (int)$row['total'];
        }
    } catch (Throwable $e) {}
}

/* ===== TOP AUTHORS ===== */
$topAuthors = [];
if ($db) {
    try {
        $topAuthors = $db->query("
            SELECT a.nama_lengkap, a.email, COUNT(*) as total_posts, SUM(b.views) as total_views 
            FROM berita b 
            LEFT JOIN admins a ON b.author_id = a.id 
            WHERE b.status='published' AND b.author_id IS NOT NULL
            GROUP BY b.author_id, a.nama_lengkap, a.email
            ORDER BY total_posts DESC 
            LIMIT 5
        ")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== EDIT MODE ===== */
$editData = null;
if ($action === 'edit' && $id_get > 0 && $db) {
    try {
        $st = $db->prepare("SELECT * FROM berita WHERE id = ?");
        $st->execute([$id_get]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

/* ===== HELPER: Word count ===== */
function countWords($text) {
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', trim($text));
    return $text === '' ? 0 : count(explode(' ', $text));
}

function readingTime($wordCount) {
    $minutes = max(1, ceil($wordCount / 200));
    return $minutes . ' min read';
}

function getAuthorInitial($name) {
    $parts = explode(' ', trim($name ?? 'A'));
    $initial = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initial .= strtoupper(substr(end($parts), 0, 1));
    }
    return $initial;
}

/* ===== GROUP BY DATE ===== */
$groupedNews = [];
foreach ($beritaList as $b) {
    $dateKey = date('Y-m-d', strtotime($b['created_at']));
    $groupedNews[$dateKey][] = $b;
}

include 'includes/header.php';
?>

<div class="page-content berita-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="berita-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-newspaper-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-newspaper"></i>
                    <span>Content Management</span>
                    <?php if ($stats['today_posts'] > 0): ?>
                    <span class="hero-alert-dot"></span>
                    <?php endif; ?>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-feather-alt"></i>
                    </span>
                    Kelola Berita & Artikel
                </h1>
                <p class="hero-subtitle">
                    Tulis, publikasi, dan kelola konten website dengan editor profesional
                    <?php if ($stats['today_posts'] > 0): ?>
                    <span class="hero-today-badge">
                        <i class="fas fa-fire"></i> <?= $stats['today_posts'] ?> artikel hari ini
                    </span>
                    <?php endif; ?>
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-newspaper"></i>
                        <span><strong><?= number_format($stats['total']) ?></strong> Total</span>
                    </div>
                    <div class="hms-item live">
                        <span class="live-dot"></span>
                        <span><strong><?= number_format($stats['published']) ?></strong> Live</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-eye"></i>
                        <span><strong><?= number_format($stats['total_views']) ?></strong> Views</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-star"></i>
                        <span><strong><?= $stats['featured'] ?></strong> Featured</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportArticles()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-ghost" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-feather-alt"></i>
                    <span>Tulis Artikel Baru</span>
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
    <div class="berita-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="berita-main-col">
            <!-- Stats Cards -->
            <div class="stats-grid berita-stats">
                <div class="stat-card stat-emerald">
                    <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Artikel</p>
                        <span class="stat-trend up"><i class="fas fa-feather-alt"></i> Content</span>
                    </div>
                </div>

                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fas fa-globe"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['published'] ?>">0</h3>
                        <p>Published</p>
                        <span class="stat-trend up live-pulse"><i class="fas fa-circle"></i> Live Now</span>
                    </div>
                </div>

                <div class="stat-card stat-orange">
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['draft'] ?>">0</h3>
                        <p>Draft</p>
                        <span class="stat-trend up"><i class="fas fa-edit"></i> Pending</span>
                    </div>
                </div>

                <div class="stat-card stat-cyan">
                    <div class="stat-icon"><i class="fas fa-eye"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total_views'] ?>">0</h3>
                        <p>Total Views</p>
                        <span class="stat-trend up"><i class="fas fa-chart-line"></i> Traffic</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card berita-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari judul, ringkasan, kategori..." value="<?= clean($_GET['q'] ?? '') ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterStatus" class="toolbar-select">
                            <option value="">📊 Semua Status</option>
                            <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>🟢 Published</option>
                            <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>📝 Draft</option>
                        </select>
                        
                        <select id="filterKategori" class="toolbar-select">
                            <option value="">🏷️ Semua Kategori</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= clean($c) ?>" <?= $filterCat === $c ? 'selected' : '' ?>><?= clean($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="date" id="filterDate" class="toolbar-select" value="<?= clean($filterDate) ?>" title="Filter tanggal">
                        
                        <label class="featured-filter">
                            <input type="checkbox" id="filterFeatured" <?= $filterFeatured === '1' ? 'checked' : '' ?>>
                            <i class="fas fa-star"></i>
                            <span>Featured</span>
                        </label>
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
                            <a href="?sort=published_at&dir=DESC" class="sort-item <?= $sortBy === 'published_at' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-globe"></i> Publish Terbaru
                            </a>
                            <a href="?sort=views&dir=DESC" class="sort-item <?= $sortBy === 'views' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-eye"></i> Views Terbanyak
                            </a>
                            <a href="?sort=judul&dir=ASC" class="sort-item <?= $sortBy === 'judul' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Judul A-Z
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
                    <span><strong id="selectedCount">0</strong> artikel dipilih</span>
                </div>
                <div class="bulk-actions">
                    <button class="btn btn-sm btn-success" onclick="bulkAction('bulk-publish')">
                        <i class="fas fa-globe"></i> Publish
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="bulkAction('bulk-draft')">
                        <i class="fas fa-file"></i> Draft
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('bulk-delete')">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        Batal
                    </button>
                </div>
            </div>

            <!-- News Grid -->
            <div class="berita-grid" id="beritaGrid" data-view="grid">
                <?php if (!empty($beritaList)): ?>
                    <?php foreach ($beritaList as $b): 
                        $thumb = function_exists('getBeritaImage') ? getBeritaImage($b['gambar'], $b['kategori'], '../') : ($b['gambar'] ? '../uploads/' . $b['gambar'] : '');
                        $wordCount = countWords($b['konten']);
                        $readTime = readingTime($wordCount);
                        $isPublished = $b['status'] === 'published';
                        $tags = array_filter(array_map('trim', explode(',', $b['tags'] ?? '')));
                        $authorInitial = getAuthorInitial($b['author_name']);
                        $authorColors = ['#10B981', '#3B82F6', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4'];
                        $authorColor = $authorColors[ord($authorInitial[0]) % count($authorColors)];
                    ?>
                    <div class="berita-card <?= !$isPublished ? 'is-draft' : '' ?> <?= $b['is_featured'] ? 'is-featured' : '' ?>" data-id="<?= $b['id'] ?>">
                        <!-- Checkbox -->
                        <label class="card-checkbox">
                            <input type="checkbox" class="berita-check" value="<?= $b['id'] ?>">
                            <span class="check-mark"></span>
                        </label>
                        
                        <?php if ($b['is_featured']): ?>
                        <div class="featured-star" title="Featured Article">
                            <i class="fas fa-star"></i>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Card Image -->
                        <div class="bc-image" onclick="previewArticle(<?= htmlspecialchars(json_encode($b, JSON_HEX_APOS)) ?>)">
                            <?php if ($thumb): ?>
                                <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= clean($b['judul']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="bc-placeholder">
                                    <i class="fas fa-newspaper"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="bc-image-overlay">
                                <span class="bc-category-badge"><?= clean($b['kategori']) ?></span>
                                <div class="bc-quick-actions" onclick="event.stopPropagation()">
                                    <button onclick="editArticle(<?= htmlspecialchars(json_encode($b, JSON_HEX_APOS)) ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="previewArticle(<?= htmlspecialchars(json_encode($b, JSON_HEX_APOS)) ?>)" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="?action=toggle&id=<?= $b['id'] ?>" title="<?= $isPublished ? 'Jadikan Draft' : 'Publish' ?>">
                                        <i class="fas fa-<?= $isPublished ? 'eye-slash' : 'globe' ?>"></i>
                                    </a>
                                    <a href="?action=delete&id=<?= $b['id'] ?>" title="Hapus" data-confirm="Yakin hapus berita ini?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <?php if (!$isPublished): ?>
                            <div class="bc-draft-badge">
                                <i class="fas fa-file-alt"></i> Draft
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Body -->
                        <div class="bc-body">
                            <div class="bc-top-row">
                                <span class="bc-read-time">
                                    <i class="fas fa-clock"></i> <?= $readTime ?>
                                </span>
                                <span class="bc-views">
                                    <i class="fas fa-eye"></i> <?= number_format($b['views'] ?? 0) ?>
                                </span>
                            </div>

                            <h3 class="bc-title"><?= clean($b['judul']) ?></h3>
                            
                            <?php if ($b['ringkasan']): ?>
                            <p class="bc-excerpt"><?= clean(mb_substr($b['ringkasan'], 0, 120)) ?><?= strlen($b['ringkasan']) > 120 ? '...' : '' ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($tags)): ?>
                            <div class="bc-tags">
                                <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                                <span class="bc-tag">#<?= clean($tag) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($tags) > 3): ?>
                                <span class="bc-tag more">+<?= count($tags) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <div class="bc-footer">
                                <div class="bc-author">
                                    <div class="bc-avatar" style="background: <?= $authorColor ?>"><?= $authorInitial ?></div>
                                    <div class="bc-author-info">
                                        <div class="bc-author-name"><?= clean($b['author_name'] ?: 'Admin') ?></div>
                                        <div class="bc-date">
                                            <?= function_exists('formatTanggal') ? formatTanggal($b['published_at'] ?? $b['created_at']) : date('d M Y', strtotime($b['published_at'] ?? $b['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <button class="bc-edit-btn" onclick="editArticle(<?= htmlspecialchars(json_encode($b, JSON_HEX_APOS)) ?>)">
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
                                <rect x="40" y="30" width="120" height="90" rx="4" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <rect x="40" y="30" width="120" height="20" rx="4" fill="#10B981" opacity="0.2"/>
                                <rect x="50" y="60" width="100" height="4" rx="2" fill="var(--gray-300)"/>
                                <rect x="50" y="70" width="80" height="4" rx="2" fill="var(--gray-300)"/>
                                <rect x="50" y="80" width="90" height="4" rx="2" fill="var(--gray-300)"/>
                                <rect x="50" y="90" width="70" height="4" rx="2" fill="var(--gray-300)"/>
                                <rect x="50" y="100" width="85" height="4" rx="2" fill="var(--gray-300)"/>
                                <circle cx="155" cy="45" r="15" fill="#10B981" opacity="0.9"/>
                                <path d="M 148 45 L 162 45 M 155 38 L 155 52" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3>Belum ada artikel</h3>
                        <p>Mulai tulis artikel pertama Anda untuk menginformasikan pengunjung</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-feather-alt"></i> Tulis Artikel Pertama
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Table View -->
            <?php if (!empty($beritaList)): ?>
            <div class="berita-table-view" id="beritaTable" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="selectAllTable"></th>
                                    <th>Thumbnail</th>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Author</th>
                                    <th>Views</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($beritaList as $b): 
                                    $thumb = function_exists('getBeritaImage') ? getBeritaImage($b['gambar'], $b['kategori'], '../') : ($b['gambar'] ? '../uploads/' . $b['gambar'] : '');
                                    $isPublished = $b['status'] === 'published';
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="berita-check" value="<?= $b['id'] ?>"></td>
                                    <td>
                                        <?php if ($thumb): ?>
                                            <img src="<?= htmlspecialchars($thumb) ?>" alt="" style="width:50px;height:50px;border-radius:8px;object-fit:cover">
                                        <?php else: ?>
                                            <div style="width:50px;height:50px;border-radius:8px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;color:var(--gray-400)"><i class="fas fa-newspaper"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= clean($b['judul']) ?></strong>
                                        <?php if ($b['is_featured']): ?> <i class="fas fa-star" style="color:#F59E0B" title="Featured"></i><?php endif; ?>
                                        <div style="font-size:0.72rem;color:var(--gray-500);margin-top:2px"><?= readingTime(countWords($b['konten'])) ?></div>
                                    </td>
                                    <td><span class="badge badge-primary"><?= clean($b['kategori']) ?></span></td>
                                    <td><?= clean($b['author_name'] ?: 'Unknown') ?></td>
                                    <td><strong><?= number_format($b['views'] ?? 0) ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?= $isPublished ? 'success' : 'warning' ?>">
                                            <i class="fas fa-<?= $isPublished ? 'globe' : 'file-alt' ?>"></i>
                                            <?= ucfirst($b['status']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:0.82rem"><?= function_exists('formatTanggal') ? formatTanggal($b['published_at'] ?? $b['created_at']) : date('d M Y', strtotime($b['published_at'] ?? $b['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-sm" onclick="previewArticle(<?= htmlspecialchars(json_encode($b, JSON_HEX_APOS)) ?>)" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-sm" onclick="editArticle(<?= htmlspecialchars(json_encode($b, JSON_HEX_APOS)) ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=toggle&id=<?= $b['id'] ?>" class="btn-icon btn-sm" title="Toggle">
                                                <i class="fas fa-<?= $isPublished ? 'eye-slash' : 'globe' ?>"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $b['id'] ?>" class="btn-icon btn-sm btn-danger" data-confirm="Hapus berita ini?" title="Hapus">
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
                <span>Menampilkan <strong><?= count($beritaList) ?></strong> artikel</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="berita-sidebar">
            <!-- Top Articles -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-fire"></i> Trending Articles</h3>
                    <span class="widget-badge">Top 5</span>
                </div>
                <div class="widget-body">
                    <?php if (!empty($topArticles)): ?>
                        <?php foreach ($topArticles as $i => $ta): 
                            $taThumb = function_exists('getBeritaImage') ? getBeritaImage($ta['gambar'], $ta['kategori'], '../') : ($ta['gambar'] ? '../uploads/' . $ta['gambar'] : '');
                        ?>
                        <div class="trending-item" onclick="previewArticle(<?= htmlspecialchars(json_encode($ta, JSON_HEX_APOS)) ?>)">
                            <div class="ti-rank">#<?= $i + 1 ?></div>
                            <?php if ($taThumb): ?>
                                <img src="<?= htmlspecialchars($taThumb) ?>" alt="" class="ti-thumb">
                            <?php else: ?>
                                <div class="ti-thumb-placeholder"><i class="fas fa-newspaper"></i></div>
                            <?php endif; ?>
                            <div class="ti-info">
                                <div class="ti-title"><?= clean(mb_substr($ta['judul'], 0, 50)) ?><?= strlen($ta['judul']) > 50 ? '...' : '' ?></div>
                                <div class="ti-meta">
                                    <i class="fas fa-eye"></i> <?= number_format($ta['views']) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-fire"></i>
                            <p>Belum ada artikel trending</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Categories -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-tags"></i> Kategori</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($categoryStats)): 
                        $maxCat = max(array_column($categoryStats, 'total'));
                        $catColors = ['#10B981', '#3B82F6', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4'];
                    ?>
                        <?php foreach ($categoryStats as $i => $cs): 
                            $pct = round(($cs['total'] / $maxCat) * 100);
                            $color = $catColors[$i % count($catColors)];
                        ?>
                        <div class="cat-stat-item">
                            <div class="csi-head">
                                <span class="csi-name">
                                    <span class="csi-dot" style="background: <?= $color ?>"></span>
                                    <?= clean($cs['kategori']) ?>
                                </span>
                                <span class="csi-count"><?= $cs['total'] ?></span>
                            </div>
                            <div class="csi-bar">
                                <div class="csi-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                            </div>
                            <div class="csi-views">
                                <i class="fas fa-eye"></i> <?= number_format($cs['total_views'] ?? 0) ?> views
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-tags"></i>
                            <p>Belum ada kategori</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Writing Activity -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-bar"></i> Aktivitas Menulis</h3>
                    <span class="widget-badge">7 Hari</span>
                </div>
                <div class="widget-body">
                    <?php if (!empty($writingActivity)): 
                        $maxActivity = max($writingActivity) ?: 1;
                        $totalWeek = array_sum($writingActivity);
                    ?>
                    <div class="writing-chart">
                        <?php 
                        $startDate = strtotime('-6 days');
                        for ($i = 0; $i < 7; $i++):
                            $date = date('Y-m-d', strtotime("+$i days", $startDate));
                            $count = $writingActivity[$date] ?? 0;
                            $height = max(4, ($count / $maxActivity) * 100);
                            $isToday = $date === date('Y-m-d');
                        ?>
                        <div class="wc-bar-group" title="<?= date('d M', strtotime($date)) ?>: <?= $count ?> artikel">
                            <div class="wc-bar-value"><?= $count ?></div>
                            <div class="wc-bar-wrapper">
                                <div class="wc-bar <?= $isToday ? 'today' : '' ?>" style="height: <?= $height ?>%"></div>
                            </div>
                            <div class="wc-bar-label"><?= date('D', strtotime($date)) ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="wc-summary">
                        <span>Total: <strong><?= $totalWeek ?> artikel</strong></span>
                        <span>Avg: <strong><?= round($totalWeek/7) ?>/hari</strong></span>
                    </div>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-chart-bar"></i>
                            <p>Belum ada data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Authors -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-users"></i> Top Authors</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($topAuthors)): 
                        $maxAuthor = max(array_column($topAuthors, 'total_posts'));
                        $colors = ['#10B981', '#3B82F6', '#F59E0B', '#8B5CF6', '#EC4899'];
                        foreach ($topAuthors as $i => $au): 
                            $pct = round(($au['total_posts'] / $maxAuthor) * 100);
                            $color = $colors[$i % count($colors)];
                            $initial = getAuthorInitial($au['nama_lengkap']);
                    ?>
                        <div class="author-item">
                            <div class="ai-avatar" style="background: <?= $color ?>"><?= $initial ?></div>
                            <div class="ai-info">
                                <div class="ai-name"><?= clean($au['nama_lengkap']) ?></div>
                                <div class="ai-bar">
                                    <div class="ai-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                                </div>
                            </div>
                            <div class="ai-stats">
                                <div class="ai-posts"><?= $au['total_posts'] ?></div>
                                <div class="ai-views"><i class="fas fa-eye"></i> <?= number_format($au['total_views'] ?? 0) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-users"></i>
                            <p>Belum ada author</p>
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
                        <i class="fas fa-feather-alt"></i>
                        <span>Tulis Artikel Baru</span>
                    </button>
                    <a href="komentar.php" class="qa-btn">
                        <i class="fas fa-comments"></i>
                        <span>Kelola Komentar</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="media.php" class="qa-btn">
                        <i class="fas fa-images"></i>
                        <span>Media Library</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                    <a href="analytics.php" class="qa-btn">
                        <i class="fas fa-chart-line"></i>
                        <span>Lihat Analytics</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Writing Tips -->
            <div class="card widget-card tips-card">
                <div class="widget-header">
                    <h3><i class="fas fa-lightbulb"></i> Writing Tips</h3>
                </div>
                <div class="widget-body">
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-heading"></i></div>
                        <div class="tip-text">Gunakan judul yang menarik & mengandung keyword</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-image"></i></div>
                        <div class="tip-text">Selalu tambahkan thumbnail berkualitas tinggi</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-align-left"></i></div>
                        <div class="tip-text">Ideal panjang artikel: 800-1500 kata untuk SEO</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-tags"></i></div>
                        <div class="tip-text">Tambahkan 3-5 tags untuk discoverability</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== ARTICLE MODAL ===== -->
<div class="modal" id="articleModal">
    <div class="modal-content modal-xl">
        <div class="modal-header modal-header-gradient berita-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-feather-alt"></i></div>
                <div>
                    <h3 id="modalTitle">Tulis Artikel Baru</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px" id="modalSubtitle">Buat konten yang engaging untuk pembaca</p>
                </div>
            </div>
            <div class="modal-header-actions">
                <span class="autosave-indicator" id="autosaveIndicator" style="display:none">
                    <i class="fas fa-check-circle"></i> Auto-saved
                </span>
                <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
        </div>
        
<form method="POST" enctype="multipart/form-data" id="articleForm" class="modal-body">
<?php if (function_exists('generateCSRF')): ?>
    <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
<?php endif; ?>
<input type="hidden" name="id" id="formId">
<input type="hidden" name="gambar_lama" id="gambarLama">
            
            <div class="editor-layout">
                <!-- LEFT: Main Editor -->
                <div class="editor-main">
                    <!-- Title -->
                    <div class="form-group title-group">
                        <input type="text" name="judul" id="judul" required 
                               placeholder="Judul artikel yang menarik..." 
                               class="title-input">
                        <div class="title-meta">
                            <span class="char-counter" id="titleCounter">0/100 karakter</span>
                            <span class="slug-preview" id="slugPreview">
                                <i class="fas fa-link"></i> <span id="slugText">slug-akan-muncul-disini</span>
                            </span>
                        </div>
                        <input type="hidden" name="slug" id="slug">
                    </div>

                    <!-- Ringkasan -->
                    <div class="form-group">
                        <label class="field-label">
                            <i class="fas fa-align-left"></i> Ringkasan / Excerpt
                        </label>
                        <textarea name="ringkasan" id="ringkasan" rows="2" required 
                                  placeholder="Tulis ringkasan singkat yang menarik pembaca..." 
                                  maxlength="200" class="excerpt-input"></textarea>
                        <span class="char-counter" id="ringkasanCounter">0/200</span>
                    </div>

                    <!-- Content Editor -->
                    <div class="form-group">
                        <label class="field-label">
                            <i class="fas fa-edit"></i> Konten Artikel
                        </label>
                        <div class="rich-editor">
                            <div class="editor-toolbar">
                                <div class="toolbar-group">
                                    <button type="button" onclick="formatText('bold')" title="Bold (Ctrl+B)"><i class="fas fa-bold"></i></button>
                                    <button type="button" onclick="formatText('italic')" title="Italic (Ctrl+I)"><i class="fas fa-italic"></i></button>
                                    <button type="button" onclick="formatText('underline')" title="Underline"><i class="fas fa-underline"></i></button>
                                    <button type="button" onclick="formatText('strikeThrough')" title="Strikethrough"><i class="fas fa-strikethrough"></i></button>
                                </div>
                                <div class="toolbar-divider"></div>
                                <div class="toolbar-group">
                                    <button type="button" onclick="insertBlock('h2')" title="Heading 2"><i class="fas fa-heading"></i></button>
                                    <button type="button" onclick="insertBlock('h3')" title="Heading 3"><i class="fas fa-heading" style="font-size:0.8em"></i></button>
                                    <button type="button" onclick="insertBlock('p')" title="Paragraph"><i class="fas fa-paragraph"></i></button>
                                    <button type="button" onclick="insertBlock('blockquote')" title="Quote"><i class="fas fa-quote-left"></i></button>
                                </div>
                                <div class="toolbar-divider"></div>
                                <div class="toolbar-group">
                                    <button type="button" onclick="insertBlock('ul')" title="Bullet List"><i class="fas fa-list-ul"></i></button>
                                    <button type="button" onclick="insertBlock('ol')" title="Numbered List"><i class="fas fa-list-ol"></i></button>
                                </div>
                                <div class="toolbar-divider"></div>
                                <div class="toolbar-group">
                                    <button type="button" onclick="insertLink()" title="Insert Link"><i class="fas fa-link"></i></button>
                                    <button type="button" onclick="insertImage()" title="Insert Image URL"><i class="fas fa-image"></i></button>
                                    <button type="button" onclick="insertCode()" title="Code Block"><i class="fas fa-code"></i></button>
                                </div>
                                <div class="toolbar-divider"></div>
                                <div class="toolbar-group">
                                    <button type="button" onclick="formatText('undo')" title="Undo"><i class="fas fa-undo"></i></button>
                                    <button type="button" onclick="formatText('redo')" title="Redo"><i class="fas fa-redo"></i></button>
                                </div>
                            </div>
                            <textarea name="konten" id="konten" rows="15" required 
                                      placeholder="Mulai menulis konten artikel Anda di sini...

Tips:
- Gunakan heading (H2, H3) untuk struktur
- Tambahkan gambar untuk visual yang menarik
- Buat paragraf yang ringkas dan mudah dibaca" 
                                      class="content-textarea"></textarea>
                        </div>
                        <div class="editor-footer">
                            <span class="word-counter" id="wordCounter">
                                <i class="fas fa-file-word"></i> <span id="wordCount">0</span> kata · <span id="readingTime">0 min read</span>
                            </span>
                            <span class="editor-tip" id="editorTip">
                                <i class="fas fa-lightbulb"></i> Tip: Artikel 800-1500 kata optimal untuk SEO
                            </span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Sidebar Options -->
                <div class="editor-sidebar">
                    <!-- Publish Status -->
                    <div class="editor-panel">
                        <h4 class="panel-title"><i class="fas fa-globe"></i> Publikasi</h4>
                        
                        <div class="status-switcher">
                            <label class="status-option">
                                <input type="radio" name="status" value="draft" checked>
                                <div class="status-box draft">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Draft</span>
                                    <small>Simpan sebagai draft</small>
                                </div>
                            </label>
                            <label class="status-option">
                                <input type="radio" name="status" value="published">
                                <div class="status-box publish">
                                    <i class="fas fa-globe"></i>
                                    <span>Publish</span>
                                    <small>Langsung publik</small>
                                </div>
                            </label>
                        </div>
                        
                        <div class="form-group" style="margin-top: 12px">
                            <label class="field-label small">
                                <i class="fas fa-calendar-alt"></i> Schedule Publish
                            </label>
                            <div class="input-icon">
                                <i class="fas fa-clock"></i>
                                <input type="datetime-local" name="published_at" id="publishedAt">
                            </div>
                            <small class="form-hint">Kosongkan untuk publish sekarang</small>
                        </div>
                        
                        <label class="featured-checkbox">
                            <input type="checkbox" name="is_featured" id="isFeatured" value="1">
                            <span class="fc-mark"></span>
                            <div class="fc-content">
                                <strong><i class="fas fa-star" style="color:#F59E0B"></i> Featured Article</strong>
                                <small>Tampilkan di highlight</small>
                            </div>
                        </label>
                    </div>

                    <!-- Category & Tags -->
                    <div class="editor-panel">
                        <h4 class="panel-title"><i class="fas fa-tags"></i> Kategori & Tags</h4>
                        
                        <div class="form-group">
                            <label class="field-label small">Kategori *</label>
                            <div class="input-icon">
                                <i class="fas fa-folder"></i>
                                <input type="text" name="kategori" id="kategori" required 
                                       placeholder="Contoh: Workshop, Seminar" list="kategoriList">
                                <datalist id="kategoriList">
                                    <?php foreach ($categories as $c): ?>
                                    <option value="<?= clean($c) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="field-label small">Tags</label>
                            <div class="tags-input-wrapper" id="tagsWrapper">
                                <div class="tags-list" id="tagsList"></div>
                                <input type="text" id="tagsInput" placeholder="Ketik tag lalu Enter...">
                            </div>
                            <input type="hidden" name="tags" id="tagsHidden">
                            <small class="form-hint">Pisahkan dengan Enter atau koma</small>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="editor-panel">
                        <h4 class="panel-title"><i class="fas fa-image"></i> Thumbnail</h4>
                        <div class="upload-area upload-compact" id="uploadArea">
                            <div class="upload-placeholder-compact">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Upload Thumbnail</span>
                                <small>1200x630px recommended</small>
                            </div>
                            <img id="previewImg" class="upload-preview-compact" style="display:none">
                            <input type="file" name="gambar" id="gambarInput" accept="image/*">
                        </div>
                    </div>

                    <!-- SEO Preview -->
                    <div class="editor-panel seo-panel">
                        <h4 class="panel-title"><i class="fab fa-google"></i> SEO Preview</h4>
                        <div class="seo-preview">
                            <div class="seo-url" id="seoUrl">example.com/berita/<span id="seoSlug">slug</span></div>
                            <div class="seo-title" id="seoTitle">Judul artikel akan muncul di sini</div>
                            <div class="seo-desc" id="seoDesc">Ringkasan artikel akan ditampilkan di sini sebagai meta description...</div>
                        </div>
                        <div class="seo-score">
                            <div class="seo-score-item good">
                                <i class="fas fa-check-circle"></i> Title
                            </div>
                            <div class="seo-score-item good">
                                <i class="fas fa-check-circle"></i> Meta
                            </div>
                            <div class="seo-score-item warning" id="seoContent">
                                <i class="fas fa-exclamation-circle"></i> Content
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer modal-footer-split">
                <div class="footer-left">
                    <button type="button" class="btn btn-ghost-secondary" onclick="saveDraft()">
                        <i class="fas fa-save"></i> Simpan Draft
                    </button>
                </div>
                <div class="footer-right">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> <span id="submitText">Simpan & Publish</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ===== PREVIEW MODAL ===== -->
<div class="modal" id="previewModal">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-gradient">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-eye"></i></div>
                <div>
                    <h3>Preview Artikel</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Tampilan artikel seperti di website</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePreview()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body preview-body" id="previewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePreview()">Tutup</button>
            <button class="btn btn-primary" id="previewEditBtn"><i class="fas fa-edit"></i> Edit Artikel</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   BERITA PAGE - JOURNALIST EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.berita-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.berita-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #064E3B 0%, #047857 40%, #10B981 100%);
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
    background: radial-gradient(circle, #34D399, transparent);
    top: -120px; right: 8%;
}

.orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, #6EE7B7, transparent);
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

.hero-newspaper-pattern {
    position: absolute;
    top: 50%;
    right: 5%;
    transform: translateY(-50%);
    font-size: 18rem;
    color: rgba(255,255,255,0.04);
    pointer-events: none;
}

.hero-newspaper-pattern::before {
    content: '\f1ea';
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
    position: relative;
}

.hero-alert-dot {
    width: 8px;
    height: 8px;
    background: #F59E0B;
    border-radius: 50%;
    animation: liveDot 1.5s infinite;
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
    color: #A7F3D0;
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
    background: linear-gradient(135deg, #fff, #ECFDF5);
    color: #047857;
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
.berita-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.berita-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.berita-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-emerald .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-green .stat-icon { background: linear-gradient(135deg, #059669, #065F46); }
.stat-orange .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }
.stat-cyan .stat-icon { background: linear-gradient(135deg, #06B6D4, #0891B2); }

.live-pulse {
    animation: livePulse 1.5s infinite;
}

@keyframes livePulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* ===== TOOLBAR ===== */
.berita-toolbar {
    padding: 16px 20px;
    margin-bottom: 20px;
}

.berita-toolbar {
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
    border-color: #10B981;
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(16,185,129,0.1);
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
    border-color: #10B981;
    box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
}

.featured-filter {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: var(--white);
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 0.85rem;
    color: var(--gray-700);
    transition: all var(--transition);
    user-select: none;
}

.featured-filter:hover {
    border-color: #F59E0B;
}

.featured-filter:has(input:checked) {
    background: rgba(245,158,11,0.1);
    border-color: #F59E0B;
    color: #B45309;
}

.featured-filter input {
    display: none;
}

.featured-filter i {
    color: #F59E0B;
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
    background: #10B981;
    color: var(--white);
    border-color: #10B981;
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
    background: rgba(16,185,129,0.1);
    color: #10B981;
    font-weight: 600;
}

.sort-item i {
    width: 16px;
    color: var(--gray-500);
}

.sort-item.active i {
    color: #10B981;
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
    color: #10B981;
    box-shadow: var(--shadow-sm);
}

/* ===== BULK BAR ===== */
.bulk-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: linear-gradient(135deg, #047857, #10B981);
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

/* ===== BERITA GRID ===== */
.berita-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.berita-grid[data-view="list"] {
    grid-template-columns: 1fr;
}

.berita-grid[data-view="list"] .berita-card {
    display: grid;
    grid-template-columns: 260px 1fr;
}

.berita-grid[data-view="list"] .bc-image {
    height: 100%;
    min-height: 200px;
}

.berita-card {
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

[data-theme="dark"] .berita-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.berita-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(16,185,129,0.3);
}

.berita-card.is-draft {
    border-color: rgba(245,158,11,0.3);
}

.berita-card.is-featured {
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

.berita-card:hover .card-checkbox,
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
    background: #10B981;
    border-color: #10B981;
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

/* Card Image */
.bc-image {
    position: relative;
    height: 180px;
    background: var(--gray-100);
    overflow: hidden;
    cursor: pointer;
}

.bc-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.berita-card:hover .bc-image img {
    transform: scale(1.08);
}

.bc-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: var(--gray-400);
    background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
}

.bc-image-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, transparent 60%);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 14px;
    opacity: 0;
    transition: opacity var(--transition);
}

.berita-card:hover .bc-image-overlay {
    opacity: 1;
}

.bc-category-badge {
    align-self: flex-start;
    padding: 5px 12px;
    background: #10B981;
    color: var(--white);
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    backdrop-filter: blur(10px);
}

.bc-quick-actions {
    display: flex;
    gap: 6px;
    align-self: flex-end;
}

.bc-quick-actions button,
.bc-quick-actions a {
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

.bc-quick-actions button:hover,
.bc-quick-actions a:hover {
    background: #10B981;
    color: var(--white);
    transform: translateY(-3px);
}

.bc-draft-badge {
    position: absolute;
    top: 14px;
    right: 14px;
    padding: 4px 12px;
    background: rgba(245,158,11,0.95);
    color: var(--white);
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    backdrop-filter: blur(10px);
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Card Body */
.bc-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.bc-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.bc-read-time,
.bc-views {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    color: var(--gray-600);
    font-weight: 600;
}

[data-theme="dark"] .bc-read-time,
[data-theme="dark"] .bc-views {
    color: var(--gray-400);
}

.bc-read-time i,
.bc-views i {
    color: #10B981;
    font-size: 0.68rem;
}

.bc-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 8px;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.8em;
}

[data-theme="dark"] .bc-title {
    color: var(--white);
}

.bc-excerpt {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 12px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

[data-theme="dark"] .bc-excerpt {
    color: var(--gray-400);
}

.bc-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 12px;
}

.bc-tag {
    padding: 2px 8px;
    background: var(--gray-100);
    color: var(--gray-700);
    border-radius: 10px;
    font-size: 0.68rem;
    font-weight: 600;
}

[data-theme="dark"] .bc-tag {
    background: rgba(255,255,255,0.08);
    color: var(--gray-300);
}

.bc-tag.more {
    background: rgba(16,185,129,0.1);
    color: #047857;
}

.bc-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 14px;
    border-top: 1px solid var(--gray-100);
    margin-top: auto;
    gap: 10px;
}

[data-theme="dark"] .bc-footer {
    border-color: rgba(255,255,255,0.08);
}

.bc-author {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    min-width: 0;
}

.bc-avatar {
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
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.bc-author-info {
    flex: 1;
    min-width: 0;
}

.bc-author-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

[data-theme="dark"] .bc-author-name {
    color: var(--gray-200);
}

.bc-date {
    font-size: 0.7rem;
    color: var(--gray-500);
}

.bc-edit-btn {
    width: 34px;
    height: 34px;
    border-radius: var(--radius-sm);
    background: #10B981;
    color: var(--white);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
    flex-shrink: 0;
}

.bc-edit-btn:hover {
    background: #047857;
    transform: translateY(-2px) rotate(5deg);
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}

/* List View Adjustments */
.berita-grid[data-view="list"] .bc-body {
    padding: 20px;
}

.berita-grid[data-view="list"] .bc-title {
    font-size: 1.2rem;
    -webkit-line-clamp: 1;
}

.berita-grid[data-view="list"] .bc-excerpt {
    -webkit-line-clamp: 3;
}

/* Table View */
.berita-table-view .data-table {
    font-size: 0.85rem;
}

.berita-table-view .data-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 700;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .berita-table-view .data-table th {
    background: var(--dark);
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

.berita-table-view .data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

.result-info {
    text-align: center;
    padding: 12px;
    color: var(--gray-600);
    font-size: 0.85rem;
}

.result-info strong {
    color: #10B981;
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
.berita-sidebar {
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
    color: #10B981;
}

.widget-badge {
    padding: 3px 10px;
    background: #10B981;
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

/* ===== TRENDING ===== */
.trending-item {
    display: grid;
    grid-template-columns: 24px 50px 1fr;
    gap: 10px;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    transition: all var(--transition-fast);
}

[data-theme="dark"] .trending-item {
    border-color: rgba(255,255,255,0.05);
}

.trending-item:last-child {
    border-bottom: none;
}

.trending-item:hover {
    transform: translateX(4px);
}

.ti-rank {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--gray-400);
    text-align: center;
    font-family: 'Space Grotesk', sans-serif;
}

.trending-item:nth-child(1) .ti-rank {
    color: #F59E0B;
    font-size: 1rem;
}

.trending-item:nth-child(2) .ti-rank {
    color: #94A3B8;
}

.trending-item:nth-child(3) .ti-rank {
    color: #CD7F32;
}

.ti-thumb {
    width: 50px;
    height: 50px;
    border-radius: var(--radius-sm);
    object-fit: cover;
}

.ti-thumb-placeholder {
    width: 50px;
    height: 50px;
    border-radius: var(--radius-sm);
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-400);
}

.ti-info {
    min-width: 0;
}

.ti-title {
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

[data-theme="dark"] .ti-title {
    color: var(--gray-200);
}

.ti-meta {
    font-size: 0.72rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 4px;
}

.ti-meta i {
    color: #10B981;
    font-size: 0.68rem;
}

/* ===== CATEGORY STATS ===== */
.cat-stat-item {
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .cat-stat-item {
    border-color: rgba(255,255,255,0.05);
}

.cat-stat-item:last-child {
    border-bottom: none;
}

.csi-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.csi-name {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

[data-theme="dark"] .csi-name {
    color: var(--gray-200);
}

.csi-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.csi-count {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .csi-count {
    color: var(--white);
}

.csi-bar {
    height: 5px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
    margin-bottom: 4px;
}

[data-theme="dark"] .csi-bar {
    background: rgba(255,255,255,0.08);
}

.csi-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

.csi-views {
    font-size: 0.7rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 4px;
}

.csi-views i {
    color: #10B981;
    font-size: 0.65rem;
}

/* ===== WRITING CHART ===== */
.writing-chart {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    height: 140px;
    padding: 0 4px;
    margin-bottom: 12px;
}

.wc-bar-group {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    min-width: 0;
}

.wc-bar-value {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--gray-700);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .wc-bar-value {
    color: var(--gray-300);
}

.wc-bar-wrapper {
    width: 100%;
    height: 100px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.wc-bar {
    width: 100%;
    max-width: 24px;
    background: linear-gradient(180deg, #34D399, #10B981);
    border-radius: 6px 6px 3px 3px;
    transition: all var(--transition-fast);
    animation: barGrow 0.8s ease-out;
}

.wc-bar:hover {
    background: linear-gradient(180deg, #6EE7B7, #34D399);
    box-shadow: 0 4px 12px rgba(16,185,129,0.4);
}

.wc-bar.today {
    background: linear-gradient(180deg, #F59E0B, #D97706);
}

.wc-bar-label {
    font-size: 0.68rem;
    color: var(--gray-600);
    font-weight: 500;
}

.wc-summary {
    padding-top: 12px;
    border-top: 1px solid var(--gray-100);
    display: flex;
    justify-content: space-between;
    font-size: 0.78rem;
    color: var(--gray-600);
}

[data-theme="dark"] .wc-summary {
    border-color: rgba(255,255,255,0.08);
    color: var(--gray-400);
}

.wc-summary strong {
    color: #10B981;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== AUTHORS ===== */
.author-item {
    display: grid;
    grid-template-columns: 36px 1fr auto;
    gap: 10px;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .author-item {
    border-color: rgba(255,255,255,0.05);
}

.author-item:last-child {
    border-bottom: none;
}

.ai-avatar {
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

.ai-info {
    min-width: 0;
}

.ai-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

[data-theme="dark"] .ai-name {
    color: var(--gray-200);
}

.ai-bar {
    height: 4px;
    background: var(--gray-100);
    border-radius: 50px;
    overflow: hidden;
}

[data-theme="dark"] .ai-bar {
    background: rgba(255,255,255,0.08);
}

.ai-bar-fill {
    height: 100%;
    border-radius: 50px;
    animation: barFill 1s ease-out;
}

.ai-stats {
    text-align: right;
}

.ai-posts {
    font-size: 1rem;
    font-weight: 700;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
    line-height: 1;
}

[data-theme="dark"] .ai-posts {
    color: var(--white);
}

.ai-views {
    font-size: 0.68rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 3px;
    margin-top: 2px;
    justify-content: flex-end;
}

.ai-views i {
    color: #10B981;
    font-size: 0.6rem;
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
    background: linear-gradient(135deg, #10B981, #047857);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}

.qa-btn.primary:hover {
    box-shadow: 0 6px 16px rgba(16,185,129,0.4);
}

.qa-btn i:first-child {
    color: #10B981;
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

/* ===== TIPS ===== */
.tips-card {
    background: linear-gradient(135deg, rgba(245,158,11,0.04), rgba(16,185,129,0.04));
}

.tip-item {
    display: flex;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
    align-items: flex-start;
}

[data-theme="dark"] .tip-item {
    border-color: rgba(255,255,255,0.05);
}

.tip-item:last-child {
    border-bottom: none;
}

.tip-icon {
    width: 28px;
    height: 28px;
    border-radius: var(--radius-sm);
    background: rgba(16,185,129,0.12);
    color: #10B981;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    flex-shrink: 0;
    margin-top: 1px;
}

.tip-text {
    font-size: 0.82rem;
    color: var(--gray-700);
    line-height: 1.5;
    flex: 1;
}

[data-theme="dark"] .tip-text {
    color: var(--gray-300);
}

/* ===== ARTICLE MODAL ===== */
.berita-modal-head {
    background: linear-gradient(135deg, #064E3B 0%, #047857 60%, #10B981 100%) !important;
}

.modal-xl {
    max-width: 1200px !important;
    width: 95vw;
}

.modal-header-actions {
    display: flex;
    align-items: center;
    gap: 14px;
}

.autosave-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: rgba(255,255,255,0.2);
    color: var(--white);
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
    animation: fadeIn 0.3s ease;
}

.autosave-indicator i {
    color: #34D399;
}

/* Editor Layout */
.editor-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
}

.editor-main {
    min-width: 0;
}

.editor-sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-height: 70vh;
    overflow-y: auto;
    padding-right: 8px;
}

.editor-sidebar::-webkit-scrollbar {
    width: 4px;
}

.editor-sidebar::-webkit-scrollbar-thumb {
    background: var(--gray-300);
    border-radius: 2px;
}

/* Title Input */
.title-group {
    margin-bottom: 20px;
}

.title-input {
    width: 100%;
    padding: 14px 0;
    border: none;
    border-bottom: 2px solid var(--gray-200);
    font-size: 1.5rem;
    font-weight: 700;
    font-family: 'Space Grotesk', sans-serif;
    color: var(--dark);
    background: transparent;
    transition: all var(--transition);
}

[data-theme="dark"] .title-input {
    color: var(--white);
    border-color: rgba(255,255,255,0.1);
}

.title-input:focus {
    outline: none;
    border-bottom-color: #10B981;
}

.title-input::placeholder {
    color: var(--gray-400);
    font-weight: 500;
}

.title-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    gap: 10px;
    flex-wrap: wrap;
}

.slug-preview {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: var(--gray-100);
    color: var(--gray-600);
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-family: 'Courier New', monospace;
}

[data-theme="dark"] .slug-preview {
    background: rgba(255,255,255,0.05);
    color: var(--gray-400);
}

.slug-preview i {
    color: #10B981;
}

/* Excerpt Input */
.excerpt-input {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: 0.9rem;
    font-family: inherit;
    resize: vertical;
    min-height: 60px;
    transition: all var(--transition);
    background: var(--white);
    color: var(--dark);
}

[data-theme="dark"] .excerpt-input {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    color: var(--white);
}

.excerpt-input:focus {
    outline: none;
    border-color: #10B981;
    box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
}

/* Rich Editor */
.rich-editor {
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    overflow: hidden;
    transition: all var(--transition);
    background: var(--white);
}

[data-theme="dark"] .rich-editor {
    background: rgba(255,255,255,0.03);
    border-color: rgba(255,255,255,0.1);
}

.rich-editor:focus-within {
    border-color: #10B981;
    box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
}

.editor-toolbar {
    display: flex;
    gap: 4px;
    padding: 8px 10px;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    flex-wrap: wrap;
    align-items: center;
}

[data-theme="dark"] .editor-toolbar {
    background: rgba(0,0,0,0.2);
    border-color: rgba(255,255,255,0.08);
}

.toolbar-group {
    display: flex;
    gap: 2px;
}

.toolbar-divider {
    width: 1px;
    height: 24px;
    background: var(--gray-200);
    margin: 0 4px;
}

[data-theme="dark"] .toolbar-divider {
    background: rgba(255,255,255,0.1);
}

.editor-toolbar button {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    border-radius: 6px;
    cursor: pointer;
    color: var(--gray-700);
    transition: all var(--transition-fast);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}

[data-theme="dark"] .editor-toolbar button {
    color: var(--gray-300);
}

.editor-toolbar button:hover {
    background: var(--white);
    color: #10B981;
    transform: translateY(-1px);
}

[data-theme="dark"] .editor-toolbar button:hover {
    background: rgba(255,255,255,0.08);
}

.content-textarea {
    width: 100%;
    padding: 16px;
    border: none;
    resize: vertical;
    min-height: 350px;
    font-family: 'Georgia', serif;
    font-size: 1rem;
    line-height: 1.7;
    background: transparent;
    color: var(--dark);
}

[data-theme="dark"] .content-textarea {
    color: var(--gray-200);
}

.content-textarea:focus {
    outline: none;
}

.content-textarea::placeholder {
    color: var(--gray-400);
    font-style: italic;
}

.editor-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-100);
    font-size: 0.78rem;
}

[data-theme="dark"] .editor-footer {
    background: rgba(0,0,0,0.2);
    border-color: rgba(255,255,255,0.08);
}

.word-counter {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--gray-700);
    font-weight: 600;
}

[data-theme="dark"] .word-counter {
    color: var(--gray-300);
}

.word-counter i {
    color: #10B981;
}

.editor-tip {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--gray-600);
    font-style: italic;
}

.editor-tip i {
    color: #F59E0B;
}

/* Editor Panels */
.editor-panel {
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--radius);
    border: 1px solid var(--gray-100);
}

[data-theme="dark"] .editor-panel {
    background: rgba(255,255,255,0.03);
    border-color: rgba(255,255,255,0.08);
}

.panel-title {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

[data-theme="dark"] .panel-title {
    color: var(--white);
}

.panel-title i {
    color: #10B981;
    font-size: 0.85rem;
}

.field-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 6px;
}

[data-theme="dark"] .field-label {
    color: var(--gray-300);
}

.field-label i {
    color: #10B981;
    font-size: 0.78rem;
}

.field-label.small {
    font-size: 0.75rem;
}

.form-group {
    margin-bottom: 14px;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-hint {
    display: block;
    font-size: 0.72rem;
    color: var(--gray-500);
    margin-top: 4px;
    font-style: italic;
}

.char-counter {
    font-size: 0.72rem;
    color: var(--gray-500);
    margin-top: 4px;
    display: block;
    text-align: right;
}

/* Status Switcher */
.status-switcher {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.status-option {
    cursor: pointer;
    position: relative;
}

.status-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.status-box {
    padding: 14px 12px;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius);
    text-align: center;
    transition: all var(--transition);
    background: var(--white);
}

[data-theme="dark"] .status-box {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
}

.status-box i {
    font-size: 1.3rem;
    margin-bottom: 6px;
    display: block;
}

.status-box span {
    display: block;
    font-size: 0.85rem;
    font-weight: 700;
    margin-bottom: 2px;
}

.status-box small {
    font-size: 0.68rem;
    color: var(--gray-500);
}

.status-box.draft {
    color: #92400E;
}

.status-box.publish {
    color: #047857;
}

.status-option input:checked + .status-box.draft {
    background: #FEF3C7;
    border-color: #F59E0B;
}

.status-option input:checked + .status-box.publish {
    background: #D1FAE5;
    border-color: #10B981;
}

[data-theme="dark"] .status-option input:checked + .status-box.draft {
    background: rgba(245,158,11,0.15);
    border-color: #F59E0B;
    color: #FCD34D;
}

[data-theme="dark"] .status-option input:checked + .status-box.publish {
    background: rgba(16,185,129,0.15);
    border-color: #10B981;
    color: #34D399;
}

/* Featured Checkbox */
.featured-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: linear-gradient(135deg, rgba(245,158,11,0.06), rgba(16,185,129,0.04));
    border-radius: var(--radius);
    cursor: pointer;
    margin-top: 12px;
    border: 1px solid transparent;
    transition: all var(--transition);
}

.featured-checkbox:hover {
    border-color: rgba(245,158,11,0.3);
}

.featured-checkbox input {
    display: none;
}

.fc-mark {
    width: 20px;
    height: 20px;
    border: 2px solid var(--gray-300);
    border-radius: 5px;
    flex-shrink: 0;
    position: relative;
    transition: all var(--transition-fast);
}

.featured-checkbox input:checked + .fc-mark {
    background: #F59E0B;
    border-color: #F59E0B;
}

.fc-mark::after {
    content: '\f005';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    color: var(--white);
    font-size: 0.65rem;
    transition: transform 0.2s;
}

.featured-checkbox input:checked + .fc-mark::after {
    transform: translate(-50%, -50%) scale(1);
}

.fc-content strong {
    display: block;
    font-size: 0.85rem;
    color: var(--dark);
    margin-bottom: 2px;
}

[data-theme="dark"] .fc-content strong {
    color: var(--white);
}

.fc-content small {
    font-size: 0.72rem;
    color: var(--gray-600);
}

/* Tags Input */
.tags-input-wrapper {
    padding: 6px 10px;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    background: var(--white);
    transition: all var(--transition);
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    min-height: 42px;
}

[data-theme="dark"] .tags-input-wrapper {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
}

.tags-input-wrapper:focus-within {
    border-color: #10B981;
    box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    background: rgba(16,185,129,0.12);
    color: #047857;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

[data-theme="dark"] .tag-chip {
    background: rgba(16,185,129,0.2);
    color: #34D399;
}

.tag-chip button {
    background: transparent;
    border: none;
    color: inherit;
    cursor: pointer;
    padding: 0;
    font-size: 0.75rem;
    opacity: 0.7;
    transition: opacity var(--transition-fast);
}

.tag-chip button:hover {
    opacity: 1;
}

#tagsInput {
    flex: 1;
    min-width: 80px;
    border: none;
    outline: none;
    background: transparent;
    font-size: 0.85rem;
    font-family: inherit;
    padding: 4px 0;
    color: var(--dark);
}

[data-theme="dark"] #tagsInput {
    color: var(--white);
}

/* Upload Compact */
.upload-compact {
    position: relative;
    border: 2px dashed var(--gray-300);
    border-radius: var(--radius);
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all var(--transition);
    background: var(--white);
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

[data-theme="dark"] .upload-compact {
    border-color: rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.03);
}

.upload-compact:hover {
    border-color: #10B981;
    background: rgba(16,185,129,0.04);
}

.upload-placeholder-compact i {
    font-size: 1.8rem;
    color: #10B981;
    margin-bottom: 6px;
    display: block;
}

.upload-placeholder-compact span {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
}

[data-theme="dark"] .upload-placeholder-compact span {
    color: var(--gray-200);
}

.upload-placeholder-compact small {
    display: block;
    font-size: 0.7rem;
    color: var(--gray-500);
    margin-top: 2px;
}

.upload-compact input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.upload-preview-compact {
    max-width: 100%;
    max-height: 180px;
    border-radius: var(--radius-sm);
    object-fit: cover;
}

/* SEO Preview */
.seo-panel {
    background: linear-gradient(135deg, rgba(59,130,246,0.04), rgba(16,185,129,0.04));
    border-color: rgba(59,130,246,0.15);
}

.seo-preview {
    padding: 14px;
    background: var(--white);
    border-radius: var(--radius);
    border: 1px solid var(--gray-100);
    margin-bottom: 12px;
}

[data-theme="dark"] .seo-preview {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.08);
}

.seo-url {
    font-size: 0.72rem;
    color: #047857;
    font-family: 'Courier New', monospace;
    margin-bottom: 4px;
    word-break: break-all;
}

.seo-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1A0DAB;
    margin-bottom: 4px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

[data-theme="dark"] .seo-title {
    color: #8AB4F8;
}

.seo-desc {
    font-size: 0.8rem;
    color: var(--gray-600);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

[data-theme="dark"] .seo-desc {
    color: var(--gray-400);
}

.seo-score {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
}

.seo-score-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 6px;
    border-radius: var(--radius-sm);
    font-size: 0.7rem;
    font-weight: 600;
}

.seo-score-item.good {
    background: rgba(16,185,129,0.1);
    color: #047857;
}

.seo-score-item.warning {
    background: rgba(245,158,11,0.1);
    color: #92400E;
}

.seo-score-item.bad {
    background: rgba(239,68,68,0.1);
    color: #B91C1C;
}

[data-theme="dark"] .seo-score-item.good {
    background: rgba(16,185,129,0.15);
    color: #34D399;
}

[data-theme="dark"] .seo-score-item.warning {
    background: rgba(245,158,11,0.15);
    color: #FCD34D;
}

[data-theme="dark"] .seo-score-item.bad {
    background: rgba(239,68,68,0.15);
    color: #FCA5A5;
}

/* Modal Footer Split */
.modal-footer-split {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.footer-left,
.footer-right {
    display: flex;
    gap: 10px;
}

.btn-ghost-secondary {
    padding: 10px 18px;
    background: transparent;
    color: var(--gray-600);
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all var(--transition);
    font-family: inherit;
}

.btn-ghost-secondary:hover {
    background: var(--gray-50);
    border-color: var(--gray-300);
}

[data-theme="dark"] .btn-ghost-secondary {
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

/* ===== PREVIEW MODAL ===== */
.preview-body {
    padding: 0 !important;
}

.preview-hero {
    position: relative;
    height: 280px;
    background: var(--gray-200);
    overflow: hidden;
}

.preview-hero img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-hero-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: var(--gray-400);
    background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
}

.preview-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7), transparent 60%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 24px;
    color: var(--white);
}

.preview-category {
    display: inline-block;
    padding: 5px 14px;
    background: #10B981;
    color: var(--white);
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 10px;
    align-self: flex-start;
}

.preview-title {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1.25;
    margin-bottom: 10px;
    font-family: 'Space Grotesk', sans-serif;
}

.preview-meta {
    display: flex;
    gap: 14px;
    font-size: 0.85rem;
    opacity: 0.9;
    flex-wrap: wrap;
}

.preview-meta span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.preview-content {
    padding: 30px;
}

.preview-excerpt {
    font-size: 1.1rem;
    color: var(--gray-700);
    line-height: 1.6;
    margin-bottom: 20px;
    padding-left: 16px;
    border-left: 4px solid #10B981;
    font-style: italic;
}

[data-theme="dark"] .preview-excerpt {
    color: var(--gray-300);
}

.preview-article {
    font-size: 1rem;
    line-height: 1.8;
    color: var(--gray-800);
    font-family: 'Georgia', serif;
}

[data-theme="dark"] .preview-article {
    color: var(--gray-300);
}

.preview-article h2 {
    font-size: 1.5rem;
    margin: 24px 0 12px;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

.preview-article h3 {
    font-size: 1.25rem;
    margin: 20px 0 10px;
    color: var(--dark);
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .preview-article h2,
[data-theme="dark"] .preview-article h3 {
    color: var(--white);
}

.preview-article p {
    margin-bottom: 14px;
}

.preview-tags {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--gray-200);
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.preview-tag {
    padding: 5px 14px;
    background: var(--gray-100);
    color: var(--gray-700);
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 600;
}

[data-theme="dark"] .preview-tag {
    background: rgba(255,255,255,0.08);
    color: var(--gray-300);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .berita-layout {
        grid-template-columns: 1fr;
    }
    
    .berita-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
    
    .editor-layout {
        grid-template-columns: 1fr;
    }
    
    .editor-sidebar {
        max-height: none;
    }
}

@media (max-width: 768px) {
    .berita-page {
        padding: 16px;
    }
    
    .berita-hero {
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
    
    .berita-toolbar {
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
    
    .berita-grid {
        grid-template-columns: 1fr;
    }
    
    .berita-grid[data-view="list"] .berita-card {
        grid-template-columns: 1fr;
    }
    
    .modal-xl {
        max-width: 100% !important;
    }
    
    .title-input {
        font-size: 1.2rem;
    }
    
    .preview-title {
        font-size: 1.4rem;
    }
    
    .modal-footer-split {
        flex-direction: column;
        align-items: stretch;
    }
    
    .footer-left,
    .footer-right {
        width: 100%;
        justify-content: center;
    }
}

@media print {
    .berita-hero,
    .berita-sidebar,
    .berita-toolbar,
    .bulk-bar,
    .bc-quick-actions,
    .bc-edit-btn,
    .card-checkbox {
        display: none !important;
    }
    
    .berita-layout {
        grid-template-columns: 1fr;
    }
    
    .berita-card {
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
        document.getElementById('articleForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('gambarLama').value = '';
        document.getElementById('modalTitle').textContent = 'Tulis Artikel Baru';
        document.getElementById('modalSubtitle').textContent = 'Buat konten yang engaging untuk pembaca';
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('tagsList').innerHTML = '';
        document.getElementById('tagsHidden').value = '';
        document.getElementById('slugText').textContent = 'slug-akan-muncul-disini';
        document.getElementById('seoSlug').textContent = 'slug';
        document.getElementById('seoTitle').textContent = 'Judul artikel akan muncul di sini';
        document.getElementById('seoDesc').textContent = 'Ringkasan artikel akan ditampilkan di sini sebagai meta description...';
        document.getElementById('titleCounter').textContent = '0/100 karakter';
        document.getElementById('ringkasanCounter').textContent = '0/200';
        document.getElementById('wordCount').textContent = '0';
        document.getElementById('readingTime').textContent = '0 min read';
        
        loadDraft();
        
        document.getElementById('articleModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('judul')?.focus(), 300);
    };

    window.closeModal = function() {
        if (hasUnsavedChanges()) {
            if (!confirm('Anda memiliki perubahan yang belum disimpan. Yakin ingin keluar?')) {
                return;
            }
        }
        document.getElementById('articleModal').classList.remove('active');
        document.body.style.overflow = '';
        clearDraft();
    };

    window.editArticle = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Artikel';
        document.getElementById('modalSubtitle').textContent = 'Perbarui konten artikel';
        document.getElementById('formId').value = data.id;
        document.getElementById('judul').value = data.judul;
        document.getElementById('kategori').value = data.kategori;
        document.getElementById('ringkasan').value = data.ringkasan;
        document.getElementById('konten').value = data.konten;
        document.getElementById('gambarLama').value = data.gambar || '';
        document.querySelector(`input[name="status"][value="${data.status}"]`).checked = true;
        document.getElementById('isFeatured').checked = data.is_featured == 1;
        
        if (data.published_at) {
            const d = new Date(data.published_at);
            const iso = d.toISOString().slice(0, 16);
            document.getElementById('publishedAt').value = iso;
        }
        
        // Set slug
        document.getElementById('slug').value = data.slug || '';
        document.getElementById('slugText').textContent = data.slug || generateSlug(data.judul);
        document.getElementById('seoSlug').textContent = data.slug || generateSlug(data.judul);
        
        // Set tags
        const tags = (data.tags || '').split(',').map(t => t.trim()).filter(t => t);
        tags.forEach(tag => addTag(tag));
        
        // Set preview image
        const previewImg = document.getElementById('previewImg');
        if (data.gambar) {
            previewImg.src = '../uploads/' + data.gambar;
            previewImg.style.display = 'block';
        } else {
            previewImg.style.display = 'none';
        }
        
        updateCounters();
        updateSEOPreview();
        
        document.getElementById('articleModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /* ===== PREVIEW MODAL ===== */
    window.previewArticle = function(data) {
        const thumb = data.gambar ? '../uploads/' + data.gambar : '';
        const tags = (data.tags || '').split(',').map(t => t.trim()).filter(t => t);
        const wordCount = countWords(data.konten || '');
        const readTime = Math.max(1, Math.ceil(wordCount / 200));
        
        const body = document.getElementById('previewBody');
        body.innerHTML = `
            <div class="preview-hero">
                ${thumb 
                    ? `<img src="${thumb}" alt="${data.judul}">`
                    : `<div class="preview-hero-placeholder"><i class="fas fa-newspaper"></i></div>`
                }
                <div class="preview-overlay">
                    <span class="preview-category">${data.kategori || 'Uncategorized'}</span>
                    <h1 class="preview-title">${data.judul}</h1>
                    <div class="preview-meta">
                        <span><i class="fas fa-user"></i> ${data.author_name || data.author || 'Admin'}</span>
                        <span><i class="fas fa-calendar"></i> ${new Date(data.published_at || data.created_at).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'})}</span>
                        <span><i class="fas fa-clock"></i> ${readTime} min read</span>
                        <span><i class="fas fa-eye"></i> ${Number(data.views || 0).toLocaleString('id-ID')} views</span>
                    </div>
                </div>
            </div>
            
            <div class="preview-content">
                ${data.ringkasan ? `<div class="preview-excerpt">${data.ringkasan}</div>` : ''}
                
                <div class="preview-article">${(data.konten || '').replace(/\n/g, '</p><p>').replace(/^/, '<p>').replace(/$/, '</p>')}</div>
                
                ${tags.length > 0 ? `
                <div class="preview-tags">
                    ${tags.map(t => `<span class="preview-tag">#${t}</span>`).join('')}
                </div>
                ` : ''}
            </div>
        `;
        
        document.getElementById('previewEditBtn').onclick = function() {
            closePreview();
            setTimeout(() => editArticle(data), 300);
        };
        
        document.getElementById('previewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closePreview = function() {
        document.getElementById('previewModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    /* ===== TAGS SYSTEM ===== */
    const tagsInput = document.getElementById('tagsInput');
    const tagsList = document.getElementById('tagsList');
    const tagsHidden = document.getElementById('tagsHidden');
    const currentTags = new Set();

    function addTag(tag) {
        tag = tag.trim();
        if (!tag || currentTags.has(tag)) return;
        if (currentTags.size >= 10) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Maksimal 10 tags', 'warning');
            return;
        }
        
        currentTags.add(tag);
        const chip = document.createElement('span');
        chip.className = 'tag-chip';
        chip.innerHTML = `#${tag} <button type="button" onclick="removeTag('${tag.replace(/'/g, "\\'")}')"><i class="fas fa-times"></i></button>`;
        tagsList.appendChild(chip);
        updateTagsHidden();
    }

    window.removeTag = function(tag) {
        currentTags.delete(tag);
        const chips = tagsList.querySelectorAll('.tag-chip');
        chips.forEach(chip => {
            if (chip.textContent.includes(tag)) chip.remove();
        });
        updateTagsHidden();
    };

    function updateTagsHidden() {
        tagsHidden.value = Array.from(currentTags).join(',');
    }

    tagsInput?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const value = this.value.replace(',', '').trim();
            if (value) {
                addTag(value);
                this.value = '';
            }
        } else if (e.key === 'Backspace' && !this.value && currentTags.size > 0) {
            const lastTag = Array.from(currentTags).pop();
            removeTag(lastTag);
        }
    });

    tagsInput?.addEventListener('blur', function() {
        const value = this.value.trim();
        if (value) {
            value.split(',').forEach(t => addTag(t));
            this.value = '';
        }
    });

    /* ===== SLUG GENERATION ===== */
    function generateSlug(text) {
        return (text || '')
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .substring(0, 80);
    }

    const judulInput = document.getElementById('judul');
    const slugInput = document.getElementById('slug');
    
    judulInput?.addEventListener('input', function() {
        const slug = generateSlug(this.value);
        slugInput.value = slug;
        document.getElementById('slugText').textContent = slug || 'slug-akan-muncul-disini';
        document.getElementById('seoSlug').textContent = slug || 'slug';
        document.getElementById('titleCounter').textContent = `${this.value.length}/100 karakter`;
        updateSEOPreview();
        scheduleAutoSave();
    });

    /* ===== COUNTERS ===== */
    const ringkasanInput = document.getElementById('ringkasan');
    const kontenInput = document.getElementById('konten');
    
    function countWords(text) {
        text = (text || '').replace(/<[^>]*>/g, '').trim();
        if (!text) return 0;
        return text.split(/\s+/).filter(w => w.length > 0).length;
    }

    function updateCounters() {
        const ringkasan = ringkasanInput?.value || '';
        const konten = kontenInput?.value || '';
        
        document.getElementById('ringkasanCounter').textContent = `${ringkasan.length}/200`;
        
        const wordCount = countWords(konten);
        const readTime = Math.max(1, Math.ceil(wordCount / 200));
        document.getElementById('wordCount').textContent = wordCount.toLocaleString('id-ID');
        document.getElementById('readingTime').textContent = `${readTime} min read`;
        
        // Update SEO content score
        const seoContent = document.getElementById('seoContent');
        if (wordCount < 300) {
            seoContent.className = 'seo-score-item bad';
            seoContent.innerHTML = '<i class="fas fa-exclamation-circle"></i> Content';
        } else if (wordCount < 800) {
            seoContent.className = 'seo-score-item warning';
            seoContent.innerHTML = '<i class="fas fa-exclamation-circle"></i> Content';
        } else {
            seoContent.className = 'seo-score-item good';
            seoContent.innerHTML = '<i class="fas fa-check-circle"></i> Content';
        }
        
        // Update tip
        const tip = document.getElementById('editorTip');
        if (wordCount >= 800 && wordCount <= 1500) {
            tip.innerHTML = '<i class="fas fa-check-circle" style="color:#10B981"></i> Panjang artikel optimal untuk SEO!';
        } else if (wordCount > 1500) {
            tip.innerHTML = '<i class="fas fa-info-circle" style="color:#3B82F6"></i> Artikel panjang, pertimbangkan subheadings';
        } else {
            tip.innerHTML = '<i class="fas fa-lightbulb"></i> Tip: Artikel 800-1500 kata optimal untuk SEO';
        }
    }

    function updateSEOPreview() {
        const judul = document.getElementById('judul').value || 'Judul artikel akan muncul di sini';
        const ringkasan = document.getElementById('ringkasan').value || 'Ringkasan artikel akan ditampilkan di sini sebagai meta description...';
        
        document.getElementById('seoTitle').textContent = judul;
        document.getElementById('seoDesc').textContent = ringkasan.substring(0, 160) + (ringkasan.length > 160 ? '...' : '');
    }

    ringkasanInput?.addEventListener('input', function() {
        updateCounters();
        updateSEOPreview();
        scheduleAutoSave();
    });

    kontenInput?.addEventListener('input', function() {
        updateCounters();
        scheduleAutoSave();
    });

    /* ===== RICH EDITOR ===== */
    window.formatText = function(cmd) {
        document.execCommand(cmd, false, null);
        kontenInput?.focus();
    };

    window.insertBlock = function(tag) {
        const ta = kontenInput;
        if (!ta) return;
        
        const start = ta.selectionStart;
        const end = ta.selectionEnd;
        const sel = ta.value.substring(start, end);
        const before = ta.value.substring(0, start);
        const after = ta.value.substring(end);
        
        let insert = '';
        if (tag === 'ul') {
            insert = sel ? sel.split('\n').map(line => `<li>${line}</li>`).join('\n') : '<li>Item 1</li>\n<li>Item 2</li>';
            insert = `<ul>\n${insert}\n</ul>`;
        } else if (tag === 'ol') {
            insert = sel ? sel.split('\n').map(line => `<li>${line}</li>`).join('\n') : '<li>Item 1</li>\n<li>Item 2</li>';
            insert = `<ol>\n${insert}\n</ol>`;
        } else if (tag === 'blockquote') {
            insert = `<blockquote>${sel || 'Quote text here...'}</blockquote>`;
        } else {
            insert = `<${tag}>${sel || 'Text here...'}</${tag}>`;
        }
        
        ta.value = before + insert + after;
        ta.focus();
        updateCounters();
    };

    window.insertLink = function() {
        const url = prompt('Masukkan URL:');
        if (!url) return;
        const text = prompt('Teks link:', url);
        if (text === null) return;
        
        const ta = kontenInput;
        const start = ta.selectionStart;
        const end = ta.selectionEnd;
        const sel = ta.value.substring(start, end) || text;
        const before = ta.value.substring(0, start);
        const after = ta.value.substring(end);
        
        ta.value = before + `<a href="${url}">${sel}</a>` + after;
        ta.focus();
    };

    window.insertImage = function() {
        const url = prompt('Masukkan URL gambar:');
        if (!url) return;
        const alt = prompt('Alt text:', 'Image');
        
        const ta = kontenInput;
        const start = ta.selectionStart;
        const before = ta.value.substring(0, start);
        const after = ta.value.substring(start);
        
        ta.value = before + `\n<img src="${url}" alt="${alt || ''}" style="max-width:100%;height:auto">\n` + after;
        ta.focus();
    };

    window.insertCode = function() {
        const ta = kontenInput;
        const start = ta.selectionStart;
        const end = ta.selectionEnd;
        const sel = ta.value.substring(start, end) || 'code here';
        const before = ta.value.substring(0, start);
        const after = ta.value.substring(end);
        
        ta.value = before + `<pre><code>${sel}</code></pre>` + after;
        ta.focus();
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

    /* ===== AUTO-SAVE DRAFT ===== */
    let autoSaveTimer = null;
    let lastSaveTime = 0;

    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(saveDraft, 30000); // 30 seconds
    }

    function saveDraft() {
        const draft = {
            judul: document.getElementById('judul').value,
            kategori: document.getElementById('kategori').value,
            ringkasan: document.getElementById('ringkasan').value,
            konten: document.getElementById('konten').value,
            tags: Array.from(currentTags),
            id: document.getElementById('formId').value,
            savedAt: Date.now()
        };
        
        if (!draft.judul && !draft.konten) return;
        
        try {
            localStorage.setItem('berita_draft', JSON.stringify(draft));
            lastSaveTime = Date.now();
            
            const indicator = document.getElementById('autosaveIndicator');
            indicator.style.display = 'inline-flex';
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 3000);
        } catch (e) {}
    }

    function loadDraft() {
        try {
            const draft = JSON.parse(localStorage.getItem('berita_draft') || 'null');
            if (!draft) return;
            
            // Don't load if editing existing article
            if (document.getElementById('formId').value) return;
            
            const age = Date.now() - (draft.savedAt || 0);
            if (age > 7 * 24 * 60 * 60 * 1000) { // 7 days
                localStorage.removeItem('berita_draft');
                return;
            }
            
            if (draft.judul || draft.konten) {
                if (confirm('Ditemukan draft yang belum dipublikasi. Muat draft?')) {
                    document.getElementById('judul').value = draft.judul || '';
                    document.getElementById('kategori').value = draft.kategori || '';
                    document.getElementById('ringkasan').value = draft.ringkasan || '';
                    document.getElementById('konten').value = draft.konten || '';
                    
                    if (draft.tags) {
                        draft.tags.forEach(tag => addTag(tag));
                    }
                    
                    updateCounters();
                    updateSEOPreview();
                    
                    if (window.IFPro?.toast) {
                        window.IFPro.toast.show('Draft berhasil dimuat', 'success');
                    }
                } else {
                    localStorage.removeItem('berita_draft');
                }
            }
        } catch (e) {}
    }

    function clearDraft() {
        localStorage.removeItem('berita_draft');
    }

    window.saveDraft = function() {
        saveDraft();
        if (window.IFPro?.toast) {
            window.IFPro.toast.show('✓ Draft tersimpan otomatis', 'success');
        }
    };

    function hasUnsavedChanges() {
        const judul = document.getElementById('judul').value;
        const konten = document.getElementById('konten').value;
        return (judul || konten) && (Date.now() - lastSaveTime > 30000);
    }

    // Save draft periodically when modal is open
    setInterval(() => {
        if (document.getElementById('articleModal').classList.contains('active')) {
            saveDraft();
        }
    }, 60000);

    // Clear draft on successful submit
    document.getElementById('articleForm')?.addEventListener('submit', clearDraft);

    /* ===== STATUS CHANGE HANDLER ===== */
    document.querySelectorAll('input[name="status"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const submitText = document.getElementById('submitText');
            if (this.value === 'published') {
                submitText.textContent = 'Simpan & Publish';
            } else {
                submitText.textContent = 'Simpan Draft';
            }
        });
    });

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const filterKategori = document.getElementById('filterKategori');
    const filterDate = document.getElementById('filterDate');
    const filterFeatured = document.getElementById('filterFeatured');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    [filterStatus, filterKategori, filterDate, filterFeatured].forEach(el => {
        el?.addEventListener('change', applyFilters);
    });

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const status = filterStatus?.value;
        const kategori = filterKategori?.value;
        const date = filterDate?.value;
        const featured = filterFeatured?.checked;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        if (kategori) url.searchParams.set('kategori', kategori); else url.searchParams.delete('kategori');
        if (date) url.searchParams.set('date', date); else url.searchParams.delete('date');
        if (featured) url.searchParams.set('featured', '1'); else url.searchParams.delete('featured');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'status', 'kategori', 'date', 'featured', 'sort', 'dir'].forEach(p => url.searchParams.delete(p));
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
            const grid = document.getElementById('beritaGrid');
            const table = document.getElementById('beritaTable');
            
            if (view === 'table') {
                grid.style.display = 'none';
                table.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                table.style.display = 'none';
                grid.dataset.view = view;
            }
            
            localStorage.setItem('berita_view', view);
        });
    });

    // Restore saved view
    const savedView = localStorage.getItem('berita_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== BULK SELECTION ===== */
    const selectedIds = new Set();
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountEl = document.getElementById('selectedCount');

    document.querySelectorAll('.berita-check').forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            if (this.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkBar();
        });
    });

    document.getElementById('selectAllTable')?.addEventListener('change', function() {
        document.querySelectorAll('#beritaTable .berita-check').forEach(c => {
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
        document.querySelectorAll('.berita-check').forEach(c => c.checked = false);
        const selectAll = document.getElementById('selectAllTable');
        if (selectAll) selectAll.checked = false;
        updateBulkBar();
    };

    window.bulkAction = function(action) {
        if (selectedIds.size === 0) return;
        
        const messages = {
            'bulk-publish': `Publish ${selectedIds.size} artikel terpilih?`,
            'bulk-draft': `Jadikan ${selectedIds.size} artikel sebagai draft?`,
            'bulk-delete': `Yakin hapus ${selectedIds.size} artikel terpilih? Aksi ini tidak dapat dibatalkan!`
        };
        
        if (!confirm(messages[action])) return;
        
        const ids = Array.from(selectedIds).join(',');
        window.location.href = `?action=${action}&ids=${ids}`;
    };

    /* ===== EXPORT CSV ===== */
    window.exportArticles = function() {
        const articles = <?= json_encode($beritaList) ?>;
        
        if (!articles || articles.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['ID', 'Judul', 'Kategori', 'Tags', 'Author', 'Status', 'Views', 'Featured', 'Published At', 'Created At'];
        const rows = articles.map(a => [
            a.id,
            `"${(a.judul || '').replace(/"/g, '""')}"`,
            `"${(a.kategori || '').replace(/"/g, '""')}"`,
            `"${(a.tags || '').replace(/"/g, '""')}"`,
            `"${(a.author_name || 'Unknown').replace(/"/g, '""')}"`,
            a.status,
            a.views || 0,
            a.is_featured ? 'Ya' : 'Tidak',
            a.published_at || '',
            a.created_at
        ]);
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `berita-export-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${articles.length} artikel berhasil di-export`, 'success');
        }, 500);
    };

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        // Ctrl+S = Save draft
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            if (document.getElementById('articleModal').classList.contains('active')) {
                e.preventDefault();
                saveDraft();
                if (window.IFPro?.toast) window.IFPro.toast.show('✓ Draft tersimpan', 'success');
            }
        }
        
        // N = New article
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
    document.getElementById('articleModal')?.addEventListener('click', function(e) {
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
<?php if ($action === 'edit' && $editData): ?>
window.addEventListener('load', () => {
    setTimeout(() => editArticle(<?= json_encode($editData, JSON_HEX_APOS) ?>), 300);
});
<?php endif; ?>

    console.log('%c📰 Berita v4.0 Loaded', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c   Features: Rich Editor, Auto-save, SEO Preview, Tags, Bulk Actions', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: N (New), / (Search), Ctrl+S (Save Draft), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>