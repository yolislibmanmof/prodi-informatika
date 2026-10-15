<?php
require_once 'includes/init_security.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Dokumen & Unduhan';
$pageDesc = 'Pusat unduhan dokumen akademik: kurikulum, panduan, SOP, dan template resmi Program Studi Informatika.';

// Pagination & Sorting setup - LOGIC ASLI DIPERTAHANKAN
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$sortBy = isset($_GET['sort']) && in_array($_GET['sort'], ['terbaru', 'terpopuler', 'terbesar']) ? $_GET['sort'] : 'terbaru';

$filterKategori = isset($_GET['kategori']) ? clean($_GET['kategori']) : '';
$searchQuery = isset($_GET['q']) ? clean($_GET['q']) : '';

try {
    $db = getDB();
    $where = "status = 'aktif'";
    $params = [];
    
    if ($filterKategori) {
        $where .= " AND kategori = ?";
        $params[] = $filterKategori;
    }
    if ($searchQuery) {
        $where .= " AND (judul LIKE ? OR deskripsi LIKE ?)";
        $searchParam = "%$searchQuery%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $countStmt = $db->prepare("SELECT COUNT(*) FROM dokumen WHERE $where");
    $countStmt->execute($params);
    $totalDocs = (int)$countStmt->fetchColumn();
    $totalPages = ceil($totalDocs / $limit);

    $orderBy = match($sortBy) {
        'terpopuler' => 'downloads DESC, created_at DESC',
        'terbesar' => 'ukuran DESC, created_at DESC',
        default => 'created_at DESC'
    };

    $sql = "SELECT * FROM dokumen WHERE $where ORDER BY $orderBy LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $dokumenList = $stmt->fetchAll();
    
    $cats = $db->query("SELECT DISTINCT kategori FROM dokumen WHERE kategori IS NOT NULL AND status='aktif' ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);

    // Hero Stats
    $totalAllDocs = (int)$db->query("SELECT COUNT(*) FROM dokumen WHERE status='aktif'")->fetchColumn();
    $totalAllDownloads = (int)$db->query("SELECT COALESCE(SUM(downloads), 0) FROM dokumen WHERE status='aktif'")->fetchColumn();
    
    // Storage calculation (estimate)
    $totalSizeBytes = (int)$db->query("SELECT COALESCE(SUM(ukuran), 0) FROM dokumen WHERE status='aktif'")->fetchColumn();
    
    // File type distribution
    $fileTypeDist = $db->query("SELECT tipe_file, COUNT(*) as cnt FROM dokumen WHERE status='aktif' GROUP BY tipe_file ORDER BY cnt DESC LIMIT 5")->fetchAll(PDO::FETCH_KEY_PAIR);

} catch (Throwable $e) {
    $dokumenList = [];
    $cats = [];
    $totalPages = 0;
    $totalAllDocs = 0;
    $totalAllDownloads = 0;
    $totalSizeBytes = 0;
    $fileTypeDist = [];
}

function formatSize($bytes) {
    if (!$bytes) return 'Unknown';
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024*1024) return round($bytes/1024, 1) . ' KB';
    return round($bytes/(1024*1024), 2) . ' MB';
}

$fileIcon = [
    'pdf' => ['fa-file-pdf', '#DC2626'],
    'docx' => ['fa-file-word', '#2563EB'],
    'doc' => ['fa-file-word', '#2563EB'],
    'xlsx' => ['fa-file-excel', '#16A34A'],
    'xls' => ['fa-file-excel', '#16A34A'],
    'pptx' => ['fa-file-powerpoint', '#EA580C'],
    'ppt' => ['fa-file-powerpoint', '#EA580C'],
    'zip' => ['fa-file-zipper', '#7C3AED'],
    'rar' => ['fa-file-zipper', '#7C3AED'],
    'jpg' => ['fa-file-image', '#DB2777'],
    'jpeg' => ['fa-file-image', '#DB2777'],
    'png' => ['fa-file-image', '#DB2777'],
];

$catLabel = [
    'kurikulum' => ['Kurikulum', 'fa-book-open'],
    'akademik' => ['Akademik', 'fa-graduation-cap'],
    'kemahasiswaan' => ['Kemahasiswaan', 'fa-users'],
    'sop' => ['SOP & Panduan', 'fa-clipboard-check'],
    'lainnya' => ['Lainnya', 'fa-folder-open']
];

// Format size for display
$storageFormatted = formatSize($totalSizeBytes);
$maxStorage = 5 * 1024 * 1024 * 1024; // 5GB max (simulated)
$storagePercent = min(100, ($totalSizeBytes / $maxStorage) * 100);
?>

<!-- ===== READING PROGRESS BAR ===== -->
<div class="dv-reading-progress">
    <div class="dv-progress-fill" id="dvProgressFill"></div>
</div>

<!-- ===== HERO SECTION WITH HEXAGON CANVAS ===== -->
<section class="dv-hero">
    <canvas id="dvHexCanvas" class="dv-hex-canvas"></canvas>
    <div class="dv-hero-bg">
        <div class="dv-orb dv-orb-1"></div>
        <div class="dv-orb dv-orb-2"></div>
        <div class="dv-orb dv-orb-3"></div>
        <div class="dv-hex-pattern"></div>
        <div class="dv-grid-lines"></div>
    </div>
    
    <!-- Floating Security Icons -->
    <div class="dv-floating-icons">
        <span class="dv-float-icon" style="--delay: 0s; --x: 8%; --y: 20%; --rot: 15deg;">🔒</span>
        <span class="dv-float-icon" style="--delay: 1.5s; --x: 88%; --y: 18%; --rot: -10deg;">🛡️</span>
        <span class="dv-float-icon" style="--delay: 3s; --x: 12%; --y: 75%; --rot: 25deg;">🔐</span>
        <span class="dv-float-icon" style="--delay: 0.8s; --x: 85%; --y: 72%; --rot: -20deg;">📁</span>
        <span class="dv-float-icon" style="--delay: 2.5s; --x: 50%; --y: 10%; --rot: 5deg;">☁️</span>
        <span class="dv-float-icon" style="--delay: 4s; --x: 45%; --y: 88%; --rot: -15deg;">💾</span>
        <span class="dv-float-icon" style="--delay: 1.2s; --x: 92%; --y: 45%; --rot: 30deg;">🗂️</span>
        <span class="dv-float-icon" style="--delay: 3.5s; --x: 5%; --y: 48%; --rot: -25deg;">📊</span>
    </div>
    
    <div class="container dv-hero-content">
        <span class="dv-hero-badge fade-up">
            <span class="dv-shield-pulse"></span>
            <span class="dv-secure-badge">
                <i class="fas fa-shield-halved"></i> SECURE
            </span>
            <i class="fas fa-vault"></i> DIGITAL VAULT
        </span>
        
        <h1 class="dv-hero-title fade-up">
            <span class="dv-title-line">Dokumen &</span>
            <span class="dv-title-line dv-gradient-text">Unduhan</span>
        </h1>
        
        <p class="dv-hero-subtitle fade-up">
            <span id="dvTypingText"></span><span class="dv-cursor">|</span>
        </p>
        
        <!-- Hero Stats Grid -->
        <div class="dv-hero-stats fade-up">
            <div class="dv-stat-card">
                <div class="dv-stat-icon-wrap">
                    <i class="fas fa-file-lines"></i>
                    <span class="dv-stat-pulse"></span>
                </div>
                <div class="dv-stat-info">
                    <span class="dv-stat-num" data-count="<?= $totalAllDocs ?>">0</span>
                    <span class="dv-stat-label">Total Documents</span>
                </div>
                <div class="dv-stat-bar">
                    <div class="dv-stat-fill" style="width: 100%;"></div>
                </div>
            </div>
            
            <div class="dv-stat-card">
                <div class="dv-stat-icon-wrap dv-stat-accent">
                    <i class="fas fa-cloud-arrow-down"></i>
                    <span class="dv-stat-pulse"></span>
                </div>
                <div class="dv-stat-info">
                    <span class="dv-stat-num" data-count="<?= $totalAllDownloads ?>">0</span>
                    <span class="dv-stat-label">Total Downloads</span>
                </div>
                <div class="dv-stat-bar">
                    <div class="dv-stat-fill dv-fill-accent" style="width: 100%;"></div>
                </div>
            </div>
            
            <div class="dv-stat-card">
                <div class="dv-stat-icon-wrap dv-stat-blue">
                    <i class="fas fa-database"></i>
                    <span class="dv-stat-pulse"></span>
                </div>
                <div class="dv-stat-info">
                    <span class="dv-stat-num"><?= $storageFormatted ?></span>
                    <span class="dv-stat-label">Storage Used</span>
                </div>
                <div class="dv-stat-bar">
                    <div class="dv-stat-fill dv-fill-blue" style="width: <?= $storagePercent ?>%;"></div>
                </div>
            </div>
            
            <div class="dv-stat-card">
                <div class="dv-stat-icon-wrap dv-stat-purple">
                    <i class="fas fa-shield-check"></i>
                    <span class="dv-stat-pulse"></span>
                </div>
                <div class="dv-stat-info">
                    <span class="dv-stat-num">256-bit</span>
                    <span class="dv-stat-label">Encryption</span>
                </div>
                <div class="dv-stat-bar">
                    <div class="dv-stat-fill dv-fill-purple" style="width: 100%;"></div>
                </div>
            </div>
        </div>
        
        <!-- File Type Distribution Mini Chart -->
        <?php if (!empty($fileTypeDist)): ?>
        <div class="dv-file-dist fade-up">
            <span class="dv-dist-label">
                <i class="fas fa-chart-pie"></i> FILE TYPES:
            </span>
            <div class="dv-dist-bars">
                <?php 
                $maxDist = max($fileTypeDist);
                foreach ($fileTypeDist as $type => $count): 
                    $icon = $fileIcon[strtolower($type)] ?? ['fa-file', '#6B7280'];
                    $percent = ($count / $maxDist) * 100;
                ?>
                <div class="dv-dist-item">
                    <span class="dv-dist-type" style="color: <?= $icon[1] ?>;">
                        <i class="fas <?= $icon[0] ?>"></i> <?= strtoupper($type) ?>
                    </span>
                    <div class="dv-dist-bar">
                        <div class="dv-dist-fill" style="width: <?= $percent ?>%; background: <?= $icon[1] ?>;"></div>
                    </div>
                    <span class="dv-dist-count"><?= $count ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="dv-scroll-indicator">
        <span>Explore Vault</span>
        <div class="dv-arrow-bounce">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<section class="dv-section">
    <div class="container dv-layout">
        
        <!-- LEFT: Sidebar (Vault Info) -->
        <aside class="dv-sidebar fade-up">
            
            <!-- Vault Status Card -->
            <div class="dv-vault-status dv-tilt">
                <div class="dv-vault-glow"></div>
                <div class="dv-vault-header">
                    <div class="dv-vault-lock">
                        <i class="fas fa-lock"></i>
                        <span class="dv-lock-pulse"></span>
                    </div>
                    <div>
                        <h4>VAULT STATUS</h4>
                        <span class="dv-status-online">
                            <span class="dv-status-dot"></span> SECURE & ACTIVE
                        </span>
                    </div>
                </div>
                <div class="dv-vault-stats">
                    <div class="dv-vault-stat">
                        <i class="fas fa-server"></i>
                        <div>
                            <span>Uptime</span>
                            <strong>99.9%</strong>
                        </div>
                    </div>
                    <div class="dv-vault-stat">
                        <i class="fas fa-clock"></i>
                        <div>
                            <span>Last Sync</span>
                            <strong>2 min ago</strong>
                        </div>
                    </div>
                    <div class="dv-vault-stat">
                        <i class="fas fa-shield-virus"></i>
                        <div>
                            <span>Malware Scan</span>
                            <strong class="dv-safe">CLEAN</strong>
                        </div>
                    </div>
                </div>
                <div class="dv-storage-meter">
                    <div class="dv-storage-head">
                        <span><i class="fas fa-hdd"></i> Storage</span>
                        <strong><?= $storageFormatted ?> / 5 GB</strong>
                    </div>
                    <div class="dv-storage-track">
                        <div class="dv-storage-fill" style="width: <?= $storagePercent ?>%;"></div>
                    </div>
                    <small><?= number_format(100 - $storagePercent, 1) ?>% available</small>
                </div>
            </div>
            
            <!-- Quick Access Card -->
            <div class="dv-quick-card">
                <h4><i class="fas fa-bolt"></i> Quick Access</h4>
                <div class="dv-quick-list">
                    <a href="?kategori=kurikulum" class="dv-quick-item <?= $filterKategori === 'kurikulum' ? 'active' : '' ?>">
                        <i class="fas fa-book-open"></i>
                        <span>Kurikulum</span>
                        <span class="dv-quick-count"><?= array_count_values(array_column($dokumenList, 'kategori'))['kurikulum'] ?? 0 ?></span>
                    </a>
                    <a href="?kategori=sop" class="dv-quick-item <?= $filterKategori === 'sop' ? 'active' : '' ?>">
                        <i class="fas fa-clipboard-check"></i>
                        <span>SOP & Panduan</span>
                        <span class="dv-quick-count"><?= array_count_values(array_column($dokumenList, 'kategori'))['sop'] ?? 0 ?></span>
                    </a>
                    <a href="?kategori=akademik" class="dv-quick-item <?= $filterKategori === 'akademik' ? 'active' : '' ?>">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Akademik</span>
                        <span class="dv-quick-count"><?= array_count_values(array_column($dokumenList, 'kategori'))['akademik'] ?? 0 ?></span>
                    </a>
                    <a href="?kategori=kemahasiswaan" class="dv-quick-item <?= $filterKategori === 'kemahasiswaan' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        <span>Kemahasiswaan</span>
                        <span class="dv-quick-count"><?= array_count_values(array_column($dokumenList, 'kategori'))['kemahasiswaan'] ?? 0 ?></span>
                    </a>
                </div>
            </div>
            
            <!-- Recent Downloads -->
            <div class="dv-recent-card">
                <h4><i class="fas fa-history"></i> Recent Activity</h4>
                <div class="dv-recent-list" id="dvRecentList">
                    <div class="dv-recent-empty">
                        <i class="fas fa-inbox"></i>
                        <span>No recent downloads</span>
                    </div>
                </div>
            </div>
            
            <!-- Security Info -->
            <div class="dv-security-card dv-tilt">
                <div class="dv-security-bg">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div class="dv-security-content">
                    <i class="fas fa-user-shield"></i>
                    <h4>Secure Downloads</h4>
                    <p>Semua file telah melalui proses scanning malware & virus. 100% aman untuk diunduh.</p>
                    <div class="dv-security-badges">
                        <span><i class="fas fa-check-circle"></i> Verified</span>
                        <span><i class="fas fa-virus-slash"></i> Clean</span>
                        <span><i class="fas fa-lock"></i> Encrypted</span>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- RIGHT: Main Content Area -->
        <div class="dv-main">
            
            <!-- Console Control Bar -->
            <div class="dv-console-bar fade-up">
                <div class="dv-console-header">
                    <div class="dv-console-dots">
                        <span class="dot red"></span>
                        <span class="dot yellow"></span>
                        <span class="dot green"></span>
                    </div>
                    <i class="fas fa-terminal"></i>
                    <span>vault_search.exe</span>
                    <span class="dv-console-status">● ENCRYPTED</span>
                </div>
                
                <form method="GET" action="dokumen.php" class="dv-console-form" id="dvConsoleForm">
                    <div class="dv-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" id="dvSearchInput" placeholder="Search documents by title or description..." value="<?= htmlspecialchars($searchQuery) ?>" autocomplete="off">
                        <?php if ($filterKategori): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($filterKategori) ?>"><?php endif; ?>
                        <?php if ($sortBy !== 'terbaru'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sortBy) ?>"><?php endif; ?>
                        <span class="dv-search-hint">
                            <kbd>Ctrl</kbd>+<kbd>K</kbd>
                        </span>
                    </div>
                    
                    <div class="dv-controls-right">
                        <select name="sort" class="dv-select-vault" onchange="this.form.submit()">
                            <option value="terbaru" <?= $sortBy === 'terbaru' ? 'selected' : '' ?>>🕒 Terbaru</option>
                            <option value="terpopuler" <?= $sortBy === 'terpopuler' ? 'selected' : '' ?>>🔥 Terpopuler</option>
                            <option value="terbesar" <?= $sortBy === 'terbesar' ? 'selected' : '' ?>>💾 Terbesar</option>
                        </select>
                        
                        <button type="submit" class="dv-btn dv-btn-scan">
                            <i class="fas fa-radar"></i> Scan
                        </button>
                        
                        <?php if ($searchQuery || $filterKategori || $sortBy !== 'terbaru'): ?>
                        <a href="dokumen.php" class="dv-btn dv-btn-reset">
                            <i class="fas fa-redo"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Category Filter Pills -->
                <div class="dv-category-pills">
                    <span class="dv-cat-label">
                        <i class="fas fa-filter"></i> CATEGORIES:
                    </span>
                    <a href="dokumen.php<?= $searchQuery ? '?q='.urlencode($searchQuery) : '' ?><?= $sortBy !== 'terbaru' ? '&sort='.$sortBy : '' ?>" class="dv-pill <?= !$filterKategori ? 'active' : '' ?>">
                        <i class="fas fa-layer-group"></i> All Files
                    </a>
                    <?php foreach ($cats as $c): 
                        $lbl = $catLabel[$c] ?? [ucfirst($c), 'fa-folder'];
                    ?>
                        <a href="?kategori=<?= urlencode($c) ?><?= $searchQuery ? '&q='.urlencode($searchQuery) : '' ?><?= $sortBy !== 'terbaru' ? '&sort='.$sortBy : '' ?>" class="dv-pill <?= $filterKategori === $c ? 'active' : '' ?>">
                            <i class="fas <?= $lbl[1] ?>"></i> <?= htmlspecialchars($lbl[0]) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <!-- Results Info -->
                <div class="dv-results-info">
                    <span class="dv-results-count">
                        <i class="fas fa-folder-open"></i>
                        <strong><?= $totalDocs ?></strong> documents found
                        <?php if ($searchQuery): ?> for "<em><?= htmlspecialchars($searchQuery) ?></em>"<?php endif; ?>
                    </span>
                    <span class="dv-results-time">
                        <i class="fas fa-stopwatch"></i>
                        Scan completed in <?= rand(50, 200) ?>ms
                    </span>
                </div>
            </div>

            <!-- Dokumen Grid -->
            <?php if (!empty($dokumenList)): ?>
                <div class="dv-grid">
                    <?php foreach ($dokumenList as $index => $d):
                        $tipe = strtolower($d['tipe_file'] ?? 'pdf');
                        $icon = $fileIcon[$tipe] ?? ['fa-file', '#6B7280'];
                        $catLbl = $catLabel[$d['kategori']] ?? [ucfirst($d['kategori']), 'fa-folder'];
                        $downloadUrl = (defined('SITE_URL') ? SITE_URL : '') . '/uploads/dokumen/' . urlencode($d['filename']);
                        $isOfficial = in_array($d['kategori'], ['kurikulum', 'sop', 'akademik']);
                        $popularity = min(100, ($d['downloads'] / 50) * 100); // normalized
                    ?>
                    <div class="dv-card fade-up dv-tilt" style="animation-delay: <?= $index * 0.05 ?>s; --file-color: <?= $icon[1] ?>;">
                        <div class="dv-card-glow"></div>
                        
                        <!-- Scanner Lines (appears on hover) -->
                        <div class="dv-scanner-lines">
                            <span></span><span></span><span></span>
                        </div>
                        
                        <?php if ($isOfficial): ?>
                        <div class="dv-official-badge" title="Verified Official Document">
                            <i class="fas fa-certificate"></i>
                            <span>OFFICIAL</span>
                            <span class="dv-verify-pulse"></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Card Header -->
                        <div class="dv-card-header">
                            <div class="dv-file-icon-wrap">
                                <div class="dv-file-icon" style="background: <?= $icon[1] ?>15; color: <?= $icon[1] ?>;">
                                    <i class="fas <?= $icon[0] ?>"></i>
                                </div>
                                <div class="dv-file-radar">
                                    <span></span><span></span><span></span>
                                </div>
                            </div>
                            
                            <div class="dv-file-meta-top">
                                <span class="dv-file-type" style="background: <?= $icon[1] ?>; color: #fff;">
                                    <?= strtoupper($tipe) ?>
                                </span>
                                <span class="dv-cat-tag">
                                    <i class="fas <?= $catLbl[1] ?>"></i>
                                    <?= htmlspecialchars($catLbl[0]) ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="dv-card-body">
                            <h3 class="dv-card-title"><?= clean($d['judul']) ?></h3>
                            <?php if ($d['deskripsi']): ?>
                            <p class="dv-card-desc"><?= clean(mb_substr($d['deskripsi'], 0, 95)) ?><?= strlen($d['deskripsi']) > 95 ? '...' : '' ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Card Stats -->
                        <div class="dv-card-stats">
                            <div class="dv-stat-mini">
                                <i class="fas fa-weight-hanging"></i>
                                <span><?= $d['ukuran'] ? formatSize($d['ukuran']) : 'N/A' ?></span>
                            </div>
                            <div class="dv-stat-mini">
                                <i class="fas fa-download"></i>
                                <span><?= number_format($d['downloads']) ?></span>
                            </div>
                            <div class="dv-stat-mini dv-stat-pop">
                                <i class="fas fa-fire"></i>
                                <span><?= round($popularity) ?>%</span>
                            </div>
                        </div>
                        
                        <!-- Popularity Bar -->
                        <div class="dv-popularity-bar">
                            <div class="dv-pop-fill" style="width: <?= $popularity ?>%;"></div>
                        </div>
                        
                        <!-- Card Actions -->
                        <div class="dv-card-actions">
                            <a href="<?= htmlspecialchars($downloadUrl) ?>" class="dv-download-btn" download data-id="<?= $d['id'] ?>" data-name="<?= clean($d['judul']) ?>" data-type="<?= strtoupper($tipe) ?>">
                                <i class="fas fa-cloud-arrow-down"></i>
                                <span>Secure Download</span>
                                <span class="dv-download-progress"></span>
                            </a>
                            <button class="dv-action-btn dv-btn-preview" onclick="dvPreview('<?= addslashes(clean($d['judul'])) ?>', '<?= strtoupper($tipe) ?>', '<?= $d['ukuran'] ? formatSize($d['ukuran']) : 'N/A' ?>', <?= $d['downloads'] ?>, '<?= htmlspecialchars($d['deskripsi'] ?? '') ?>')" title="Quick Preview">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="dv-action-btn dv-btn-share" onclick="dvShare('<?= addslashes(clean($d['judul'])) ?>')" title="Share">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                        
                        <!-- Security Footer -->
                        <div class="dv-security-footer">
                            <span><i class="fas fa-shield-check"></i> Verified</span>
                            <span><i class="fas fa-virus-slash"></i> Scanned</span>
                            <span><i class="fas fa-lock"></i> Encrypted</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Smart Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="dv-pagination fade-up">
                    <div class="dv-page-info">
                        <span class="dv-page-label">PAGE</span>
                        <span class="dv-page-current"><?= $page ?></span>
                        <span class="dv-page-sep">OF</span>
                        <span class="dv-page-total"><?= $totalPages ?></span>
                    </div>
                    
                    <div class="dv-page-controls">
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($page > 1): 
                            $prevQs = http_build_query(array_merge($_GET, ['page' => $page - 1]));
                        ?>
                            <a href="?<?= $prevQs ?>" class="dv-page-btn">
                                <i class="fas fa-chevron-left"></i> PREV
                            </a>
                        <?php else: ?>
                            <span class="dv-page-btn dv-page-disabled">
                                <i class="fas fa-chevron-left"></i> PREV
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($startPage > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="dv-page-btn">1</a>
                            <?php if ($startPage > 2): ?><span class="dv-page-ellipsis">•••</span><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="dv-page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?><span class="dv-page-ellipsis">•••</span><?php endif; ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="dv-page-btn"><?= $totalPages ?></a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): 
                            $nextQs = http_build_query(array_merge($_GET, ['page' => $page + 1]));
                        ?>
                            <a href="?<?= $nextQs ?>" class="dv-page-btn">
                                NEXT <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="dv-page-btn dv-page-disabled">
                                NEXT <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty State - Access Denied -->
                <div class="dv-empty-state fade-up">
                    <div class="dv-empty-static"></div>
                    <div class="dv-empty-content">
                        <div class="dv-empty-icon">
                            <i class="fas fa-folder-open"></i>
                            <div class="dv-empty-pulse"></div>
                            <div class="dv-empty-pulse dv-pulse-2"></div>
                        </div>
                        <div class="dv-empty-code">ACCESS_404</div>
                        <h3>No Documents Found</h3>
                        <p>Tidak ada dokumen yang sesuai dengan pencarian atau filter Anda. Coba kata kunci lain atau hapus filter.</p>
                        <div class="dv-empty-actions">
                            <a href="dokumen.php" class="dv-btn dv-btn-primary">
                                <i class="fas fa-arrows-rotate"></i> Reset All Filters
                            </a>
                            <button class="dv-btn dv-btn-ghost" onclick="document.getElementById('dvSearchInput').focus()">
                                <i class="fas fa-search"></i> New Search
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== PREVIEW MODAL ===== -->
<div class="dv-modal" id="dvPreviewModal">
    <div class="dv-modal-content">
        <button class="dv-modal-close" onclick="dvClosePreview()"><i class="fas fa-times"></i></button>
        <div class="dv-modal-header">
            <div class="dv-modal-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="dv-modal-title-group">
                <span class="dv-modal-badge">QUICK PREVIEW</span>
                <h3 id="dvPreviewTitle">Document Title</h3>
            </div>
        </div>
        <div class="dv-modal-body">
            <div class="dv-preview-stats">
                <div class="dv-preview-stat">
                    <i class="fas fa-file"></i>
                    <div>
                        <span>Type</span>
                        <strong id="dvPreviewType">PDF</strong>
                    </div>
                </div>
                <div class="dv-preview-stat">
                    <i class="fas fa-weight-hanging"></i>
                    <div>
                        <span>Size</span>
                        <strong id="dvPreviewSize">0 MB</strong>
                    </div>
                </div>
                <div class="dv-preview-stat">
                    <i class="fas fa-download"></i>
                    <div>
                        <span>Downloads</span>
                        <strong id="dvPreviewDownloads">0</strong>
                    </div>
                </div>
            </div>
            <div class="dv-preview-desc">
                <h4><i class="fas fa-info-circle"></i> Description</h4>
                <p id="dvPreviewDesc">No description available.</p>
            </div>
        </div>
    </div>
</div>

<!-- ===== TOAST CONTAINER ===== -->
<div class="dv-toast-container" id="dvToastContainer"></div>

<!-- ===== BACK TO TOP ===== -->
<button class="dv-back-to-top" id="dvBackToTop" title="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<style>
:root {
    --dv-primary: #10B981;
    --dv-primary-dark: #047857;
    --dv-primary-light: #D1FAE5;
    --dv-accent: #06B6D4;
    --dv-accent-2: #8B5CF6;
    --dv-accent-3: #F59E0B;
    --dv-dark: #0F172A;
    --dv-gray-50: #F8FAFC;
    --dv-gray-100: #F1F5F9;
    --dv-gray-200: #E2E8F0;
    --dv-gray-600: #475569;
    --dv-gray-700: #374151;
    --dv-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --dv-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --dv-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
    --dv-shadow-glow: 0 0 30px rgba(16,185,129,0.3);
    --dv-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --dv-font-display: 'Space Grotesk', 'Inter', sans-serif;
}

body.gl-dark {
    --dv-gray-50: #0B1120;
    --dv-gray-100: rgba(255,255,255,.08);
    --dv-gray-200: rgba(255,255,255,.1);
    --dv-gray-600: #94A3B8;
    --dv-gray-700: #CBD5E1;
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--dv-gray-50); }
::-webkit-scrollbar-thumb { 
    background: linear-gradient(180deg, var(--dv-primary), var(--dv-accent)); 
    border-radius: 5px; 
}

/* ===== READING PROGRESS ===== */
.dv-reading-progress {
    position: fixed; top: 0; left: 0; right: 0; height: 3px;
    background: rgba(16,185,129,0.1); z-index: 9998;
}
.dv-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--dv-primary), var(--dv-accent), var(--dv-accent-2));
    box-shadow: 0 0 10px rgba(16,185,129,0.6);
    transition: width 0.1s ease-out;
}

/* ===== HERO ===== */
.dv-hero {
    background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 50%, #047857 100%);
    padding: 160px 20px 120px; position: relative; overflow: hidden;
    min-height: 95vh; display: flex; align-items: center; justify-content: center;
}

.dv-hex-canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    z-index: 1; opacity: 0.4;
}

.dv-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }

.dv-orb {
    position: absolute; border-radius: 50%; filter: blur(80px);
    opacity: 0.4; animation: dvFloat 15s infinite ease-in-out;
}
.dv-orb-1 { width: 500px; height: 500px; background: var(--dv-primary); top: -150px; right: -150px; }
.dv-orb-2 { width: 350px; height: 350px; background: var(--dv-accent-2); bottom: -100px; left: -100px; animation-delay: -5s; }
.dv-orb-3 { width: 250px; height: 250px; background: var(--dv-accent); top: 40%; left: 40%; animation-delay: -10s; }
@keyframes dvFloat { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(30px, -30px); } }

.dv-hex-pattern {
    position: absolute; inset: 0;
    background-image: 
        radial-gradient(circle at 25% 25%, rgba(16,185,129,0.08) 2px, transparent 2px),
        radial-gradient(circle at 75% 75%, rgba(6,182,212,0.08) 2px, transparent 2px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse at center, black 40%, transparent 70%);
}

.dv-grid-lines {
    position: absolute; inset: 0;
    background-image: 
        linear-gradient(rgba(16,185,129,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(16,185,129,0.05) 1px, transparent 1px);
    background-size: 80px 80px;
    mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
}

/* Floating Icons */
.dv-floating-icons { position: absolute; inset: 0; z-index: 3; pointer-events: none; }
.dv-float-icon {
    position: absolute; font-size: 2.5rem;
    animation: dvFloatIcon 8s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
    opacity: 0.4; filter: drop-shadow(0 0 10px rgba(16,185,129,0.5));
    transform: rotate(var(--rot));
}
@keyframes dvFloatIcon {
    0%, 100% { transform: translateY(0) rotate(var(--rot)); opacity: 0.4; }
    50% { transform: translateY(-30px) rotate(calc(var(--rot) + 15deg)); opacity: 0.8; }
}

.dv-hero-content {
    position: relative; z-index: 4; text-align: center;
    color: #fff; max-width: 1100px; margin: 0 auto; width: 100%;
}

.dv-hero-badge {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 10px 24px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    font-size: .85rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 700; letter-spacing: 2px; position: relative;
    font-family: var(--dv-font-mono);
}
.dv-shield-pulse {
    position: absolute; inset: 0; border-radius: 50px;
    border: 2px solid var(--dv-primary);
    animation: dvShieldPulse 2s infinite;
}
@keyframes dvShieldPulse { 
    0% { transform: scale(1); opacity: 1; } 
    100% { transform: scale(1.3); opacity: 0; } 
}
.dv-secure-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 10px; background: var(--dv-primary);
    border-radius: 4px; font-size: 0.7rem; font-weight: 800;
    letter-spacing: 1px;
}

.dv-hero-title {
    font-size: clamp(3rem, 9vw, 6rem); font-weight: 900;
    margin-bottom: 24px; letter-spacing: -0.03em; line-height: 0.95;
    font-family: var(--dv-font-display);
}
.dv-title-line { display: block; }
.dv-gradient-text {
    background: linear-gradient(135deg, #34D399 0%, #06B6D4 50%, #8B5CF6 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 200%;
    animation: dvGradientShift 5s ease infinite;
}
@keyframes dvGradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

.dv-hero-subtitle {
    font-size: 1.15rem; opacity: .9; max-width: 700px;
    margin: 0 auto 40px; line-height: 1.7;
    display: inline-block; min-height: 1.7em;
}
.dv-cursor { animation: dvBlink 1s infinite; color: var(--dv-primary); font-weight: 300; }
@keyframes dvBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

/* Hero Stats Grid */
.dv-hero-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 16px; max-width: 1000px; margin: 0 auto 40px;
}
.dv-stat-card {
    background: rgba(255,255,255,.06); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.1); border-radius: 16px;
    padding: 20px; transition: all 0.3s; position: relative;
    overflow: hidden;
}
.dv-stat-card::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, transparent, rgba(16,185,129,0.1));
    opacity: 0; transition: opacity 0.3s;
}
.dv-stat-card:hover {
    transform: translateY(-4px);
    border-color: var(--dv-primary);
    box-shadow: 0 12px 24px rgba(16,185,129,0.2);
}
.dv-stat-card:hover::before { opacity: 1; }

.dv-stat-icon-wrap {
    width: 48px; height: 48px; border-radius: 12px;
    background: linear-gradient(135deg, var(--dv-primary), var(--dv-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; margin-bottom: 14px; position: relative;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}
.dv-stat-accent { background: linear-gradient(135deg, var(--dv-accent), #0891B2); box-shadow: 0 4px 12px rgba(6,182,212,0.3); }
.dv-stat-blue { background: linear-gradient(135deg, #3B82F6, #1D4ED8); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
.dv-stat-purple { background: linear-gradient(135deg, var(--dv-accent-2), #6D28D9); box-shadow: 0 4px 12px rgba(139,92,246,0.3); }

.dv-stat-pulse {
    position: absolute; inset: -4px; border-radius: 14px;
    border: 2px solid currentColor; opacity: 0;
    animation: dvStatPulse 2s infinite;
}
@keyframes dvStatPulse {
    0% { transform: scale(0.9); opacity: 0.8; }
    100% { transform: scale(1.3); opacity: 0; }
}

.dv-stat-info { margin-bottom: 12px; position: relative; z-index: 1; }
.dv-stat-num {
    display: block; font-size: 1.8rem; font-weight: 900;
    color: #fff; line-height: 1; font-family: var(--dv-font-display);
}
.dv-stat-label {
    font-size: 0.72rem; color: rgba(255,255,255,.6);
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-top: 4px; display: block;
}

.dv-stat-bar {
    height: 4px; background: rgba(255,255,255,.1);
    border-radius: 2px; overflow: hidden; position: relative; z-index: 1;
}
.dv-stat-fill {
    height: 100%; border-radius: 2px;
    background: var(--dv-primary);
    transition: width 1s ease-out;
}
.dv-fill-accent { background: var(--dv-accent); }
.dv-fill-blue { background: #3B82F6; }
.dv-fill-purple { background: var(--dv-accent-2); }

/* File Distribution */
.dv-file-dist {
    background: rgba(255,255,255,.06); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.1); border-radius: 16px;
    padding: 20px 28px; max-width: 800px; margin: 0 auto;
}
.dv-dist-label {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.75rem; font-weight: 700; color: rgba(255,255,255,.7);
    letter-spacing: 1.5px; margin-bottom: 14px;
    font-family: var(--dv-font-mono);
}
.dv-dist-label i { color: var(--dv-primary); }

.dv-dist-bars { display: flex; flex-direction: column; gap: 8px; }
.dv-dist-item {
    display: grid; grid-template-columns: 80px 1fr 40px;
    gap: 12px; align-items: center;
}
.dv-dist-type {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.78rem; font-weight: 700;
    font-family: var(--dv-font-mono);
}
.dv-dist-type i { font-size: 0.9rem; }

.dv-dist-bar {
    height: 8px; background: rgba(255,255,255,.08);
    border-radius: 4px; overflow: hidden;
}
.dv-dist-fill {
    height: 100%; border-radius: 4px;
    transition: width 1s ease-out;
}
.dv-dist-count {
    font-family: var(--dv-font-mono); font-weight: 700;
    color: rgba(255,255,255,.9); font-size: 0.85rem;
    text-align: right;
}

/* Scroll Indicator */
.dv-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%;
    transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.dv-arrow-bounce { animation: dvArrowBounce 2s infinite; }
@keyframes dvArrowBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(10px); }
}

/* ===== MAIN SECTION ===== */
.dv-section { padding: 60px 20px 100px; background: var(--dv-gray-50); }

.dv-layout {
    display: grid; grid-template-columns: 320px 1fr;
    gap: 40px; align-items: flex-start;
}

/* ===== SIDEBAR ===== */
.dv-sidebar {
    position: sticky; top: 100px;
    display: flex; flex-direction: column; gap: 20px;
}

/* Vault Status */
.dv-vault-status {
    background: #fff; border-radius: 20px; padding: 24px;
    box-shadow: var(--dv-shadow-md); border: 1px solid var(--dv-gray-100);
    position: relative; overflow: hidden; transform-style: preserve-3d;
}
body.gl-dark .dv-vault-status { background: #1E293B; border-color: rgba(255,255,255,.08); }

.dv-vault-glow {
    position: absolute; top: -50%; right: -50%; width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(16,185,129,0.15), transparent 70%);
    pointer-events: none;
}

.dv-vault-header {
    display: flex; align-items: center; gap: 14px;
    padding-bottom: 16px; border-bottom: 1px dashed var(--dv-gray-200);
    margin-bottom: 16px; position: relative; z-index: 1;
}
body.gl-dark .dv-vault-header { border-color: rgba(255,255,255,.1); }

.dv-vault-lock {
    width: 48px; height: 48px; border-radius: 12px;
    background: linear-gradient(135deg, var(--dv-primary), var(--dv-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; position: relative;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}
.dv-lock-pulse {
    position: absolute; inset: -4px; border-radius: 14px;
    border: 2px solid var(--dv-primary);
    animation: dvLockPulse 2s infinite;
}
@keyframes dvLockPulse {
    0% { transform: scale(0.9); opacity: 0.8; }
    100% { transform: scale(1.2); opacity: 0; }
}

.dv-vault-header h4 {
    font-size: 0.8rem; font-weight: 800; color: var(--dv-dark);
    margin: 0 0 4px 0; letter-spacing: 1.5px;
    font-family: var(--dv-font-mono);
}
body.gl-dark .dv-vault-header h4 { color: #F1F5F9; }

.dv-status-online {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 0.72rem; font-weight: 700; color: var(--dv-primary);
    letter-spacing: 0.5px;
}
.dv-status-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--dv-primary);
    box-shadow: 0 0 8px var(--dv-primary);
    animation: dvStatusPulse 1.5s infinite;
}
@keyframes dvStatusPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.dv-vault-stats {
    display: flex; flex-direction: column; gap: 12px;
    margin-bottom: 16px; position: relative; z-index: 1;
}
.dv-vault-stat {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 12px; background: var(--dv-gray-50);
    border-radius: 10px; transition: all 0.3s;
}
body.gl-dark .dv-vault-stat { background: rgba(255,255,255,.03); }
.dv-vault-stat:hover { transform: translateX(4px); background: var(--dv-primary-light); }
body.gl-dark .dv-vault-stat:hover { background: rgba(16,185,129,0.1); }

.dv-vault-stat i {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(16,185,129,0.1); color: var(--dv-primary);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; flex-shrink: 0;
}
.dv-vault-stat span {
    font-size: 0.72rem; color: var(--dv-gray-600);
    display: block; margin-bottom: 2px;
}
.dv-vault-stat strong {
    font-size: 0.85rem; color: var(--dv-dark); font-weight: 700;
    font-family: var(--dv-font-mono);
}
body.gl-dark .dv-vault-stat strong { color: #F1F5F9; }
.dv-safe { color: var(--dv-primary) !important; }

.dv-storage-meter {
    padding-top: 16px; border-top: 1px dashed var(--dv-gray-200);
    position: relative; z-index: 1;
}
body.gl-dark .dv-storage-meter { border-color: rgba(255,255,255,.1); }
.dv-storage-head {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 8px; font-size: 0.8rem;
}
.dv-storage-head span {
    color: var(--dv-gray-600); display: flex; align-items: center; gap: 6px;
}
.dv-storage-head span i { color: var(--dv-primary); }
.dv-storage-head strong {
    color: var(--dv-dark); font-family: var(--dv-font-mono);
}
body.gl-dark .dv-storage-head strong { color: #F1F5F9; }

.dv-storage-track {
    height: 8px; background: var(--dv-gray-100);
    border-radius: 4px; overflow: hidden; margin-bottom: 6px;
}
body.gl-dark .dv-storage-track { background: rgba(255,255,255,.08); }
.dv-storage-fill {
    height: 100%; background: linear-gradient(90deg, var(--dv-primary), var(--dv-accent));
    border-radius: 4px; transition: width 1s ease-out;
}
.dv-storage-meter small {
    font-size: 0.7rem; color: var(--dv-gray-600);
    font-family: var(--dv-font-mono);
}

/* Quick Access */
.dv-quick-card {
    background: #fff; border-radius: 20px; padding: 24px;
    box-shadow: var(--dv-shadow-sm); border: 1px solid var(--dv-gray-100);
}
body.gl-dark .dv-quick-card { background: #1E293B; border-color: rgba(255,255,255,.08); }
.dv-quick-card h4 {
    font-size: 0.85rem; font-weight: 800; color: var(--dv-dark);
    margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;
    text-transform: uppercase; letter-spacing: 1px;
}
body.gl-dark .dv-quick-card h4 { color: #F1F5F9; }
.dv-quick-card h4 i { color: var(--dv-accent-3); }

.dv-quick-list { display: flex; flex-direction: column; gap: 6px; }
.dv-quick-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; background: var(--dv-gray-50);
    border-radius: 10px; text-decoration: none;
    color: var(--dv-gray-700); font-size: 0.85rem;
    transition: all 0.3s; border: 1px solid transparent;
}
body.gl-dark .dv-quick-item { background: rgba(255,255,255,.03); color: #CBD5E1; }
.dv-quick-item:hover {
    border-color: var(--dv-primary); background: var(--dv-primary-light);
    color: var(--dv-primary-dark); transform: translateX(4px);
}
body.gl-dark .dv-quick-item:hover { background: rgba(16,185,129,0.1); color: #34D399; }
.dv-quick-item.active {
    background: var(--dv-primary); color: #fff;
    border-color: var(--dv-primary);
}
.dv-quick-item i { font-size: 0.9rem; color: var(--dv-primary); width: 16px; }
.dv-quick-item.active i { color: #fff; }
.dv-quick-item span:first-of-type { flex: 1; font-weight: 600; }

.dv-quick-count {
    padding: 2px 8px; background: rgba(16,185,129,0.15);
    color: var(--dv-primary); border-radius: 10px;
    font-size: 0.7rem; font-weight: 700;
    font-family: var(--dv-font-mono);
}
.dv-quick-item.active .dv-quick-count {
    background: rgba(255,255,255,0.2); color: #fff;
}

/* Recent Card */
.dv-recent-card {
    background: #fff; border-radius: 20px; padding: 24px;
    box-shadow: var(--dv-shadow-sm); border: 1px solid var(--dv-gray-100);
}
body.gl-dark .dv-recent-card { background: #1E293B; border-color: rgba(255,255,255,.08); }
.dv-recent-card h4 {
    font-size: 0.85rem; font-weight: 800; color: var(--dv-dark);
    margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;
    text-transform: uppercase; letter-spacing: 1px;
}
body.gl-dark .dv-recent-card h4 { color: #F1F5F9; }
.dv-recent-card h4 i { color: var(--dv-accent-2); }

.dv-recent-list { display: flex; flex-direction: column; gap: 8px; }
.dv-recent-empty {
    text-align: center; padding: 20px; color: var(--dv-gray-600);
    font-size: 0.82rem; font-style: italic;
}
.dv-recent-empty i {
    display: block; font-size: 1.5rem; margin-bottom: 8px;
    color: var(--dv-gray-200);
}

.dv-recent-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 10px; background: var(--dv-gray-50);
    border-radius: 8px; font-size: 0.82rem;
    border-left: 3px solid var(--dv-primary);
}
body.gl-dark .dv-recent-item { background: rgba(255,255,255,.03); }
.dv-recent-item i { color: var(--dv-primary); font-size: 0.9rem; }
.dv-recent-item span { flex: 1; color: var(--dv-dark); font-weight: 600; }
body.gl-dark .dv-recent-item span { color: #F1F5F9; }
.dv-recent-item small { color: var(--dv-gray-600); font-size: 0.7rem; }

/* Security Card */
.dv-security-card {
    background: linear-gradient(135deg, var(--dv-primary), var(--dv-primary-dark));
    color: #fff; border-radius: 20px; padding: 24px;
    position: relative; overflow: hidden;
    box-shadow: 0 8px 24px rgba(16,185,129,0.3);
    transform-style: preserve-3d;
}
.dv-security-bg {
    position: absolute; top: -30%; right: -30%;
    font-size: 10rem; opacity: 0.08;
    transform: rotate(-15deg); pointer-events: none;
}
.dv-security-content { position: relative; z-index: 1; }
.dv-security-content > i {
    font-size: 2rem; margin-bottom: 12px; display: block;
}
.dv-security-card h4 {
    font-size: 1.1rem; margin: 0 0 8px 0; font-weight: 800;
}
.dv-security-card p {
    font-size: 0.85rem; opacity: 0.9; margin: 0 0 16px 0;
    line-height: 1.5;
}
.dv-security-badges {
    display: flex; gap: 6px; flex-wrap: wrap;
}
.dv-security-badges span {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px); border-radius: 20px;
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px;
}
.dv-security-badges i { font-size: 0.7rem; }

/* ===== MAIN CONTENT ===== */
.dv-main { min-width: 0; }

/* Console Bar */
.dv-console-bar {
    background: #fff; border-radius: 20px;
    box-shadow: var(--dv-shadow-md); overflow: hidden;
    border: 1px solid var(--dv-gray-100); margin-bottom: 32px;
}
body.gl-dark .dv-console-bar { background: #1E293B; border-color: rgba(255,255,255,.08); }

.dv-console-header {
    background: var(--dv-dark); padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
    color: #94A3B8; font-family: var(--dv-font-mono); font-size: 0.8rem;
}
.dv-console-dots { display: flex; gap: 5px; }
.dv-console-dots .dot { width: 11px; height: 11px; border-radius: 50%; }
.dv-console-dots .red { background: #FF5F56; }
.dv-console-dots .yellow { background: #FFBD2E; }
.dv-console-dots .green { background: #27C93F; }
.dv-console-header i { color: var(--dv-primary); }
.dv-console-header span { color: #E2E8F0; }
.dv-console-status {
    margin-left: auto; color: var(--dv-primary);
    font-size: 0.7rem; letter-spacing: 1px;
    animation: dvBlink 2s infinite;
}

.dv-console-form {
    padding: 20px; display: flex; gap: 12px;
    flex-wrap: wrap; align-items: center;
}

.dv-search-wrap {
    flex: 1; min-width: 280px; position: relative;
    display: flex; align-items: center;
}
.dv-search-wrap > i {
    position: absolute; left: 16px; top: 50%;
    transform: translateY(-50%); color: var(--dv-gray-600);
    z-index: 1;
}
.dv-search-wrap input {
    width: 100%; padding: 12px 100px 12px 44px;
    border: 1.5px solid var(--dv-gray-200); border-radius: 12px;
    background: var(--dv-gray-50); font-family: inherit;
    font-size: 0.95rem; color: var(--dv-dark);
    transition: all 0.3s;
}
body.gl-dark .dv-search-wrap input {
    background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1); color: #E2E8F0;
}
.dv-search-wrap input:focus {
    outline: none; border-color: var(--dv-primary);
    box-shadow: 0 0 0 4px rgba(16,185,129,0.15);
}

.dv-search-hint {
    position: absolute; right: 12px; top: 50%;
    transform: translateY(-50%);
    display: flex; gap: 3px; align-items: center;
    color: var(--dv-gray-600); font-size: 0.75rem;
    pointer-events: none;
}
.dv-search-hint kbd {
    background: var(--dv-gray-100); padding: 2px 6px;
    border-radius: 4px; font-family: var(--dv-font-mono);
    font-size: 0.7rem; border: 1px solid var(--dv-gray-200);
}
body.gl-dark .dv-search-hint kbd {
    background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.1); color: #CBD5E1;
}

.dv-controls-right { display: flex; gap: 8px; align-items: center; }

.dv-select-vault {
    padding: 12px 38px 12px 16px; border: 1.5px solid var(--dv-gray-200);
    border-radius: 12px; background: #fff; cursor: pointer;
    font-family: inherit; font-size: 0.9rem;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748B' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
}
body.gl-dark .dv-select-vault {
    background-color: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1); color: #E2E8F0;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394A3B8' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
}

.dv-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 20px; border-radius: 12px; font-weight: 700;
    font-size: 0.9rem; text-decoration: none; border: none;
    cursor: pointer; transition: all 0.3s; font-family: inherit;
    letter-spacing: 0.3px;
}
.dv-btn-scan {
    background: linear-gradient(135deg, var(--dv-accent), #0891B2);
    color: #fff;
}
.dv-btn-scan:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(6,182,212,0.4); }
.dv-btn-reset {
    background: rgba(239,68,68,0.1); color: #DC2626;
    width: 44px; padding: 0;
}
.dv-btn-reset:hover { background: #DC2626; color: #fff; }
.dv-btn-primary {
    background: linear-gradient(135deg, var(--dv-primary), var(--dv-primary-dark));
    color: #fff; box-shadow: 0 4px 16px rgba(16,185,129,0.3);
}
.dv-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(16,185,129,0.4); }
.dv-btn-ghost {
    background: transparent; color: var(--dv-gray-700);
    border: 1.5px solid var(--dv-gray-200);
}
body.gl-dark .dv-btn-ghost { color: #E2E8F0; border-color: rgba(255,255,255,.2); }
.dv-btn-ghost:hover { border-color: var(--dv-primary); color: var(--dv-primary); }

/* Category Pills */
.dv-category-pills {
    padding: 16px 20px; border-top: 1px solid var(--dv-gray-100);
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
body.gl-dark .dv-category-pills { border-color: rgba(255,255,255,.08); }
.dv-cat-label {
    font-size: 0.72rem; font-weight: 700; color: var(--dv-gray-600);
    letter-spacing: 1.5px; font-family: var(--dv-font-mono);
    display: flex; align-items: center; gap: 6px;
}
body.gl-dark .dv-cat-label { color: #94A3B8; }
.dv-cat-label i { color: var(--dv-primary); }

.dv-pill {
    padding: 6px 14px; border-radius: 20px; font-size: 0.8rem;
    font-weight: 600; color: var(--dv-gray-600);
    background: var(--dv-gray-100); border: 1.5px solid transparent;
    text-decoration: none; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 6px;
}
body.gl-dark .dv-pill { background: rgba(255,255,255,.05); color: #CBD5E1; }
.dv-pill i { font-size: 0.7rem; }
.dv-pill:hover {
    color: var(--dv-primary); background: rgba(16,185,129,0.1);
    transform: translateY(-2px);
}
.dv-pill.active {
    background: var(--dv-primary); color: #fff;
    border-color: var(--dv-primary);
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}

/* Results Info */
.dv-results-info {
    padding: 12px 20px; background: var(--dv-gray-50);
    border-top: 1px solid var(--dv-gray-100);
    display: flex; justify-content: space-between; align-items: center;
    font-size: 0.82rem; flex-wrap: wrap; gap: 12px;
}
body.gl-dark .dv-results-info { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.08); }
.dv-results-count {
    color: var(--dv-gray-600); display: flex; align-items: center; gap: 8px;
}
body.gl-dark .dv-results-count { color: #94A3B8; }
.dv-results-count i { color: var(--dv-primary); }
.dv-results-count strong { color: var(--dv-dark); font-family: var(--dv-font-display); }
body.gl-dark .dv-results-count strong { color: #F1F5F9; }
.dv-results-count em { color: var(--dv-primary); font-style: normal; font-weight: 600; }

.dv-results-time {
    color: var(--dv-gray-600); display: flex; align-items: center; gap: 6px;
    font-family: var(--dv-font-mono);
}
body.gl-dark .dv-results-time { color: #94A3B8; }
.dv-results-time i { color: var(--dv-accent-3); }

/* ===== DOCUMENT GRID ===== */
.dv-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px; margin-bottom: 48px;
}

.dv-card {
    position: relative; background: #fff; border-radius: 20px;
    padding: 24px; border: 1px solid var(--dv-gray-100);
    box-shadow: var(--dv-shadow-sm); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex; flex-direction: column; gap: 16px; overflow: hidden;
    transform-style: preserve-3d;
}
body.gl-dark .dv-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.dv-card-glow {
    position: absolute; inset: 0; pointer-events: none;
    background: radial-gradient(circle at 50% 0%, var(--file-color, var(--dv-primary)), transparent 60%);
    opacity: 0; transition: opacity 0.4s;
    filter: blur(40px);
}
.dv-card:hover .dv-card-glow { opacity: 0.15; }

/* Scanner Lines */
.dv-scanner-lines {
    position: absolute; inset: 0; pointer-events: none;
    opacity: 0; transition: opacity 0.3s;
}
.dv-scanner-lines span {
    position: absolute; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, var(--file-color, var(--dv-primary)), transparent);
    box-shadow: 0 0 10px var(--file-color, var(--dv-primary));
    animation: dvScanLine 2s infinite;
}
.dv-scanner-lines span:nth-child(1) { animation-delay: 0s; }
.dv-scanner-lines span:nth-child(2) { animation-delay: 0.6s; }
.dv-scanner-lines span:nth-child(3) { animation-delay: 1.2s; }
@keyframes dvScanLine {
    0% { top: 0; opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { top: 100%; opacity: 0; }
}
.dv-card:hover .dv-scanner-lines { opacity: 0.6; }

.dv-card::before {
    content: ''; position: absolute; top: 0; left: 0;
    width: 4px; height: 0; background: var(--file-color, var(--dv-primary));
    transition: height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.dv-card:hover::before { height: 100%; }

.dv-card::after {
    content: ''; position: absolute; inset: 0; border-radius: 20px; padding: 1px;
    background: conic-gradient(from 0deg, transparent, var(--file-color, var(--dv-primary)), transparent 40%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none;
}
.dv-card:hover::after { opacity: 1; animation: dvRotateBorder 4s linear infinite; }
@keyframes dvRotateBorder { to { transform: rotate(360deg); } }

.dv-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border-color: var(--file-color, var(--dv-primary));
}
body.gl-dark .dv-card:hover { box-shadow: 0 20px 40px rgba(0,0,0,0.4); }

/* Official Badge */
.dv-official-badge {
    position: absolute; top: 16px; right: 16px; z-index: 3;
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; background: linear-gradient(135deg, var(--dv-primary), var(--dv-primary-dark));
    color: #fff; border-radius: 20px; font-size: 0.68rem;
    font-weight: 800; letter-spacing: 1px;
    box-shadow: 0 4px 12px rgba(16,185,129,0.4);
}
.dv-verify-pulse {
    position: absolute; inset: 0; border-radius: 20px;
    border: 2px solid #fff; animation: dvVerifyPulse 2s infinite;
}
@keyframes dvVerifyPulse {
    0% { transform: scale(1); opacity: 0.6; }
    100% { transform: scale(1.3); opacity: 0; }
}

/* Card Header */
.dv-card-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 12px; position: relative; z-index: 2;
}

.dv-file-icon-wrap {
    position: relative; width: 60px; height: 60px; flex-shrink: 0;
}
.dv-file-icon {
    width: 100%; height: 100%; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; transition: transform 0.3s;
    position: relative; z-index: 1;
}
.dv-card:hover .dv-file-icon { transform: scale(1.1) rotate(-5deg); }

.dv-file-radar {
    position: absolute; inset: -8px; border-radius: 50%;
    pointer-events: none; opacity: 0;
    transition: opacity 0.3s;
}
.dv-card:hover .dv-file-radar { opacity: 1; }
.dv-file-radar span {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--file-color, var(--dv-primary));
    animation: dvRadar 2s infinite;
}
.dv-file-radar span:nth-child(2) { animation-delay: 0.5s; }
.dv-file-radar span:nth-child(3) { animation-delay: 1s; }
@keyframes dvRadar {
    0% { transform: scale(0.8); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

.dv-file-meta-top {
    display: flex; flex-direction: column; align-items: flex-end; gap: 6px;
}
.dv-file-type {
    padding: 4px 10px; border-radius: 6px;
    font-size: 0.68rem; font-weight: 800; letter-spacing: 1px;
    font-family: var(--dv-font-mono);
}
.dv-cat-tag {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 0.7rem; font-weight: 600; color: var(--dv-gray-600);
    text-transform: uppercase; letter-spacing: 0.5px;
}
body.gl-dark .dv-cat-tag { color: #94A3B8; }
.dv-cat-tag i { color: var(--file-color, var(--dv-primary)); font-size: 0.7rem; }

/* Card Body */
.dv-card-body { position: relative; z-index: 2; flex: 1; }
.dv-card-title {
    font-size: 1.1rem; font-weight: 800; margin: 0 0 8px 0;
    line-height: 1.4; color: var(--dv-dark); transition: color 0.3s;
    font-family: var(--dv-font-display);
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
}
body.gl-dark .dv-card-title { color: #F1F5F9; }
.dv-card:hover .dv-card-title { color: var(--file-color, var(--dv-primary)); }

.dv-card-desc {
    font-size: 0.85rem; color: var(--dv-gray-600); line-height: 1.5;
    margin: 0;
}
body.gl-dark .dv-card-desc { color: #94A3B8; }

/* Card Stats */
.dv-card-stats {
    display: flex; gap: 8px; flex-wrap: wrap;
    padding-top: 14px; border-top: 1px dashed var(--dv-gray-200);
    position: relative; z-index: 2;
}
body.gl-dark .dv-card-stats { border-color: rgba(255,255,255,.1); }

.dv-stat-mini {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; background: var(--dv-gray-50);
    border-radius: 6px; font-size: 0.72rem; font-weight: 600;
    color: var(--dv-gray-600);
    font-family: var(--dv-font-mono);
}
body.gl-dark .dv-stat-mini { background: rgba(255,255,255,.05); color: #CBD5E1; }
.dv-stat-mini i { color: var(--dv-gray-600); font-size: 0.7rem; }
.dv-stat-pop i { color: var(--dv-accent-3); }

/* Popularity Bar */
.dv-popularity-bar {
    height: 4px; background: var(--dv-gray-100);
    border-radius: 2px; overflow: hidden; position: relative; z-index: 2;
}
body.gl-dark .dv-popularity-bar { background: rgba(255,255,255,.08); }
.dv-pop-fill {
    height: 100%; border-radius: 2px;
    background: linear-gradient(90deg, var(--dv-primary), var(--dv-accent-3));
    transition: width 0.8s ease-out;
}

/* Card Actions */
.dv-card-actions {
    display: flex; gap: 8px; align-items: stretch;
    position: relative; z-index: 2;
}
.dv-download-btn {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 16px; background: linear-gradient(135deg, var(--dv-primary), var(--dv-primary-dark));
    color: #fff; border-radius: 12px; text-decoration: none;
    font-weight: 700; font-size: 0.85rem; transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(16,185,129,0.25);
    position: relative; overflow: hidden;
}
.dv-download-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16,185,129,0.4);
}
.dv-download-btn.downloading {
    background: #6B7280; pointer-events: none;
}
.dv-download-progress {
    position: absolute; left: 0; top: 0; bottom: 0;
    width: 0; background: rgba(255,255,255,0.3);
    transition: width 0.3s;
}
.dv-download-btn.downloading .dv-download-progress {
    width: 100%; transition: width 1.5s;
}

.dv-action-btn {
    width: 42px; height: 42px; border-radius: 10px;
    background: var(--dv-gray-100); border: 1px solid var(--dv-gray-200);
    color: var(--dv-gray-600); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; transition: all 0.3s; flex-shrink: 0;
}
body.gl-dark .dv-action-btn { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.dv-action-btn:hover {
    background: var(--file-color, var(--dv-primary));
    color: #fff; border-color: var(--file-color, var(--dv-primary));
    transform: translateY(-2px) rotate(10deg);
}

/* Security Footer */
.dv-security-footer {
    display: flex; justify-content: space-around;
    padding-top: 12px; border-top: 1px dashed var(--dv-gray-200);
    position: relative; z-index: 2;
}
body.gl-dark .dv-security-footer { border-color: rgba(255,255,255,.1); }
.dv-security-footer span {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.68rem; color: var(--dv-gray-600);
    font-weight: 600; letter-spacing: 0.3px;
}
body.gl-dark .dv-security-footer span { color: #94A3B8; }
.dv-security-footer i { color: var(--dv-primary); font-size: 0.7rem; }

/* ===== PAGINATION ===== */
.dv-pagination {
    display: flex; justify-content: space-between; align-items: center;
    gap: 20px; margin-top: 48px; flex-wrap: wrap;
    padding: 24px; background: #fff; border-radius: 20px;
    border: 1px solid var(--dv-gray-100); box-shadow: var(--dv-shadow-sm);
}
body.gl-dark .dv-pagination { background: #1E293B; border-color: rgba(255,255,255,.08); }

.dv-page-info {
    display: flex; align-items: center; gap: 8px;
    font-family: var(--dv-font-mono); font-size: 0.9rem;
}
.dv-page-label {
    padding: 4px 10px; background: var(--dv-primary); color: #fff;
    border-radius: 4px; font-weight: 800; font-size: 0.7rem;
    letter-spacing: 1px;
}
.dv-page-current {
    font-size: 1.5rem; font-weight: 900; color: var(--dv-dark);
    font-family: var(--dv-font-display);
}
body.gl-dark .dv-page-current { color: #F1F5F9; }
.dv-page-sep { color: var(--dv-gray-600); opacity: 0.5; font-size: 0.85rem; }
.dv-page-total { color: var(--dv-gray-600); font-weight: 600; }

.dv-page-controls { display: flex; gap: 6px; flex-wrap: wrap; }
.dv-page-btn {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    min-width: 42px; height: 42px; padding: 0 14px;
    border-radius: 10px; background: var(--dv-gray-50);
    border: 1px solid var(--dv-gray-200); color: var(--dv-gray-600);
    text-decoration: none; font-weight: 700; font-size: 0.85rem;
    font-family: var(--dv-font-mono); transition: all 0.3s;
}
body.gl-dark .dv-page-btn { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.dv-page-btn:hover:not(.dv-page-disabled) {
    border-color: var(--dv-primary); color: var(--dv-primary);
    transform: translateY(-2px);
}
.dv-page-btn.active {
    background: linear-gradient(135deg, var(--dv-primary), var(--dv-primary-dark));
    color: #fff; border-color: transparent;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}
.dv-page-disabled { opacity: 0.4; cursor: not-allowed; }
.dv-page-ellipsis {
    color: var(--dv-gray-600); padding: 0 8px; font-weight: 700;
    letter-spacing: 2px;
}

/* ===== EMPTY STATE ===== */
.dv-empty-state {
    position: relative; text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px; overflow: hidden;
    border: 2px dashed var(--dv-gray-200);
}
body.gl-dark .dv-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }

.dv-empty-static {
    position: absolute; inset: 0; opacity: 0.05;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}

.dv-empty-content { position: relative; z-index: 1; }
.dv-empty-icon {
    position: relative; width: 120px; height: 120px;
    margin: 0 auto 24px; display: flex; align-items: center; justify-content: center;
    font-size: 3.5rem; color: var(--dv-gray-600);
    animation: dvEmptySpin 4s ease-in-out infinite;
}
@keyframes dvEmptySpin {
    0%, 100% { transform: rotate(0deg); }
    50% { transform: rotate(180deg); }
}
.dv-empty-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--dv-primary);
    animation: dvEmptyPulse 2s infinite;
}
.dv-pulse-2 { animation-delay: 1s; }
@keyframes dvEmptyPulse { 
    0% { transform: scale(1); opacity: 1; } 
    100% { transform: scale(1.5); opacity: 0; } 
}

.dv-empty-code {
    font-family: var(--dv-font-mono); font-size: 0.85rem;
    color: var(--dv-accent-3); font-weight: 800; letter-spacing: 2px;
    margin-bottom: 8px;
}
.dv-empty-state h3 {
    font-size: 1.5rem; margin: 0 0 8px 0; color: var(--dv-dark);
    font-weight: 800; font-family: var(--dv-font-display);
}
body.gl-dark .dv-empty-state h3 { color: #F1F5F9; }
.dv-empty-state p { color: var(--dv-gray-600); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto; }

.dv-empty-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

/* ===== PREVIEW MODAL ===== */
.dv-modal {
    position: fixed; inset: 0; z-index: 10000;
    background: rgba(15,23,42,0.85); backdrop-filter: blur(12px);
    display: none; align-items: center; justify-content: center;
    padding: 20px; opacity: 0; transition: opacity 0.3s;
}
.dv-modal.open { display: flex; opacity: 1; }
.dv-modal-content {
    background: #fff; border-radius: 24px; max-width: 550px;
    width: 100%; max-height: 90vh; overflow: hidden;
    box-shadow: 0 30px 60px rgba(0,0,0,0.3);
    position: relative; transform: scale(0.9) translateY(20px);
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
body.gl-dark .dv-modal-content { background: #1E293B; }
.dv-modal.open .dv-modal-content { transform: scale(1) translateY(0); }

.dv-modal-close {
    position: absolute; top: 16px; right: 16px; z-index: 10;
    width: 40px; height: 40px; border-radius: 50%;
    background: var(--dv-gray-100); border: none;
    color: var(--dv-dark); font-size: 1rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s;
}
body.gl-dark .dv-modal-close { background: rgba(255,255,255,.1); color: #fff; }
.dv-modal-close:hover { background: #EF4444; color: #fff; transform: rotate(90deg); }

.dv-modal-header {
    padding: 28px 28px 20px; background: linear-gradient(135deg, var(--dv-primary), var(--dv-primary-dark));
    color: #fff; display: flex; align-items: center; gap: 16px;
}
.dv-modal-icon {
    width: 60px; height: 60px; border-radius: 16px;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; flex-shrink: 0;
}
.dv-modal-title-group { flex: 1; }
.dv-modal-badge {
    display: inline-block; padding: 3px 10px;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    border-radius: 20px; font-size: 0.65rem; font-weight: 700;
    letter-spacing: 1px; margin-bottom: 6px;
}
.dv-modal-title-group h3 {
    font-size: 1.25rem; margin: 0; font-weight: 800; line-height: 1.3;
    font-family: var(--dv-font-display);
}

.dv-modal-body { padding: 28px; }

.dv-preview-stats {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
    margin-bottom: 24px;
}
.dv-preview-stat {
    padding: 14px; background: var(--dv-gray-50);
    border-radius: 12px; display: flex; align-items: center; gap: 10px;
    border: 1px solid var(--dv-gray-100);
}
body.gl-dark .dv-preview-stat { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.08); }
.dv-preview-stat i {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, var(--dv-primary), var(--dv-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem; flex-shrink: 0;
}
.dv-preview-stat span {
    font-size: 0.7rem; color: var(--dv-gray-600); display: block; margin-bottom: 2px;
}
body.gl-dark .dv-preview-stat span { color: #94A3B8; }
.dv-preview-stat strong {
    font-size: 0.95rem; color: var(--dv-dark); font-weight: 700;
    font-family: var(--dv-font-mono);
}
body.gl-dark .dv-preview-stat strong { color: #F1F5F9; }

.dv-preview-desc {
    padding: 20px; background: var(--dv-gray-50);
    border-radius: 14px; border-left: 4px solid var(--dv-primary);
}
body.gl-dark .dv-preview-desc { background: rgba(255,255,255,.03); }
.dv-preview-desc h4 {
    font-size: 0.95rem; margin: 0 0 10px 0; color: var(--dv-dark);
    display: flex; align-items: center; gap: 8px; font-weight: 700;
}
body.gl-dark .dv-preview-desc h4 { color: #F1F5F9; }
.dv-preview-desc h4 i { color: var(--dv-primary); }
.dv-preview-desc p {
    margin: 0; color: var(--dv-gray-600); line-height: 1.7; font-size: 0.9rem;
}
body.gl-dark .dv-preview-desc p { color: #CBD5E1; }

/* ===== TOAST ===== */
.dv-toast-container {
    position: fixed; bottom: 24px; right: 24px; z-index: 10001;
    display: flex; flex-direction: column; gap: 12px;
}
.dv-toast {
    background: #1E293B; color: #fff; padding: 16px 20px;
    border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    display: flex; align-items: center; gap: 12px; min-width: 320px;
    animation: dvSlideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border-left: 4px solid var(--dv-primary);
}
body.gl-dark .dv-toast { background: #fff; color: var(--dv-dark); }
@keyframes dvSlideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
.dv-toast i { font-size: 1.3rem; color: var(--dv-primary); }
.dv-toast-content { flex: 1; }
.dv-toast-title { font-weight: 700; font-size: 0.9rem; margin-bottom: 2px; }
.dv-toast-msg { font-size: 0.8rem; opacity: 0.8; }

/* ===== BACK TO TOP ===== */
.dv-back-to-top {
    position: fixed; bottom: 30px; right: 30px;
    width: 50px; height: 50px; border-radius: 50%;
    background: linear-gradient(135deg, var(--dv-primary), var(--dv-primary-dark));
    border: none; color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; z-index: 998;
    box-shadow: 0 8px 24px rgba(16,185,129,0.4);
    opacity: 0; visibility: hidden; transform: translateY(20px) scale(.8);
    transition: all .4s;
}
.dv-back-to-top.show { opacity: 1; visibility: visible; transform: none; }
.dv-back-to-top:hover { transform: translateY(-4px) scale(1.05); }

/* ===== ANIMATIONS ===== */
.fade-up {
    opacity: 0; transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .dv-layout { grid-template-columns: 1fr; }
    .dv-sidebar {
        position: static; order: 2;
        display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
    }
    .dv-vault-status { grid-column: span 2; }
    .dv-main { order: 1; }
    .dv-hero-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .dv-hero { padding: 120px 20px 80px; min-height: 85vh; }
    .dv-hero-title { font-size: 3rem; }
    .dv-hero-stats { grid-template-columns: 1fr; gap: 12px; }
    .dv-stat-card { padding: 16px; }
    .dv-stat-num { font-size: 1.4rem; }
    .dv-file-dist { padding: 16px; }
    .dv-dist-item { grid-template-columns: 60px 1fr 30px; gap: 8px; }
    .dv-dist-type { font-size: 0.7rem; }
    .dv-floating-icons { display: none; }
    .dv-console-form { flex-direction: column; align-items: stretch; }
    .dv-controls-right { justify-content: space-between; width: 100%; }
    .dv-search-hint { display: none; }
    .dv-search-wrap input { padding-right: 16px; }
    .dv-category-pills { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 6px; }
    .dv-pill { white-space: nowrap; flex-shrink: 0; }
    .dv-grid { grid-template-columns: 1fr; }
    .dv-sidebar { grid-template-columns: 1fr; }
    .dv-vault-status { grid-column: span 1; }
    .dv-pagination { flex-direction: column; gap: 16px; padding: 20px; }
    .dv-page-controls { justify-content: center; width: 100%; overflow-x: auto; }
    .dv-results-info { flex-direction: column; align-items: flex-start; gap: 6px; }
    .dv-preview-stats { grid-template-columns: 1fr; }
    .dv-modal-header { flex-direction: column; text-align: center; padding: 24px 20px; }
    .dv-modal-body { padding: 20px; }
    .dv-back-to-top { width: 44px; height: 44px; bottom: 20px; right: 20px; }
}
@media (max-width: 480px) {
    .dv-hero-title { font-size: 2.3rem; }
    .dv-hero-badge { font-size: 0.7rem; padding: 8px 16px; letter-spacing: 1px; }
    .dv-card { padding: 18px; }
    .dv-card-title { font-size: 1rem; }
    .dv-card-actions { flex-direction: column; }
    .dv-download-btn { width: 100%; }
    .dv-action-btn { width: 100%; }
    .dv-empty-actions { flex-direction: column; }
    .dv-empty-actions .dv-btn { width: 100%; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ===== 1. Hexagon Canvas Animation =====
    const canvas = document.getElementById('dvHexCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let width, height, time = 0;
        let hexagons = [];

        function resize() {
            width = canvas.width = canvas.parentElement.offsetWidth;
            height = canvas.height = canvas.parentElement.offsetHeight;
            initHexagons();
        }

        function initHexagons() {
            hexagons = [];
            const hexSize = 40;
            const hexHeight = hexSize * Math.sqrt(3);
            const cols = Math.ceil(width / (hexSize * 1.5)) + 2;
            const rows = Math.ceil(height / hexHeight) + 2;
            
            for (let row = 0; row < rows; row++) {
                for (let col = 0; col < cols; col++) {
                    const x = col * hexSize * 1.5;
                    const y = row * hexHeight + (col % 2 === 0 ? 0 : hexHeight / 2);
                    hexagons.push({
                        x, y, size: hexSize,
                        opacity: Math.random() * 0.3 + 0.1,
                        phase: Math.random() * Math.PI * 2
                    });
                }
            }
        }

        function drawHexagon(x, y, size, opacity) {
            ctx.beginPath();
            for (let i = 0; i < 6; i++) {
                const angle = (Math.PI / 3) * i - Math.PI / 6;
                const px = x + size * Math.cos(angle);
                const py = y + size * Math.sin(angle);
                if (i === 0) ctx.moveTo(px, py);
                else ctx.lineTo(px, py);
            }
            ctx.closePath();
            ctx.strokeStyle = `rgba(52, 211, 153, ${opacity})`;
            ctx.lineWidth = 1;
            ctx.stroke();
        }

        function animate() {
            ctx.clearRect(0, 0, width, height);
            
            hexagons.forEach(hex => {
                const pulse = Math.sin(time * 0.02 + hex.phase) * 0.15 + 0.85;
                drawHexagon(hex.x, hex.y, hex.size * pulse, hex.opacity * pulse);
            });

            // Draw connecting lines between nearby hexagons
            for (let i = 0; i < hexagons.length; i++) {
                for (let j = i + 1; j < hexagons.length; j++) {
                    const dx = hexagons[i].x - hexagons[j].x;
                    const dy = hexagons[i].y - hexagons[j].y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    if (distance < 80) {
                        const opacity = (1 - distance / 80) * 0.1;
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(6, 182, 212, ${opacity})`;
                        ctx.lineWidth = 0.5;
                        ctx.moveTo(hexagons[i].x, hexagons[i].y);
                        ctx.lineTo(hexagons[j].x, hexagons[j].y);
                        ctx.stroke();
                    }
                }
            }

            time++;
            requestAnimationFrame(animate);
        }

        resize();
        window.addEventListener('resize', resize);
        animate();
    }

    // ===== 2. Typing Effect =====
    const textElement = document.getElementById('dvTypingText');
    if (textElement) {
        const phrases = [
            "Koleksi lengkap dokumen akademik resmi Program Studi Informatika.",
            "Kurikulum, SOP, panduan, dan template yang dapat diunduh dengan aman.",
            "Repository terenkripsi dengan proteksi malware & virus scanning.",
            "Akses 24/7 ke semua dokumen penting dari mana saja."
        ];
        let phraseIndex = 0, charIndex = 0, isDeleting = false;
        
        function type() {
            const currentPhrase = phrases[phraseIndex];
            if (isDeleting) {
                textElement.textContent = currentPhrase.substring(0, charIndex - 1);
                charIndex--;
            } else {
                textElement.textContent = currentPhrase.substring(0, charIndex + 1);
                charIndex++;
            }
            
            let typeSpeed = isDeleting ? 30 : 50;
            if (!isDeleting && charIndex === currentPhrase.length) {
                typeSpeed = 2500;
                isDeleting = true;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                phraseIndex = (phraseIndex + 1) % phrases.length;
                typeSpeed = 500;
            }
            setTimeout(type, typeSpeed);
        }
        type();
    }

    // ===== 3. Counter Animation =====
    const counters = document.querySelectorAll('.dv-stat-num[data-count]');
    const counterObs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target) {
                const el = entry.target;
                const target = parseInt(el.getAttribute('data-count')) || 0;
                const duration = 2000;
                const startTime = performance.now();
                const easeOutQuart = t => 1 - Math.pow(1 - t, 4);
                
                function update(now) {
                    const progress = Math.min((now - startTime) / duration, 1);
                    const current = Math.floor(target * easeOutQuart(progress));
                    el.textContent = current.toLocaleString('id-ID');
                    if (progress < 1) requestAnimationFrame(update);
                    else el.textContent = target.toLocaleString('id-ID');
                }
                requestAnimationFrame(update);
                counterObs.unobserve(el);
            }
        });
    }, { threshold: 0.5 });
    counters.forEach(c => counterObs.observe(c));

    // ===== 4. Reading Progress Bar =====
    const progressFill = document.getElementById('dvProgressFill');
    if (progressFill) {
        window.addEventListener('scroll', () => {
            const scrolled = window.scrollY;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const percent = maxScroll > 0 ? (scrolled / maxScroll) * 100 : 0;
            progressFill.style.width = percent + '%';
        }, { passive: true });
    }

    // ===== 5. 3D Tilt Effect =====
    const tiltElements = document.querySelectorAll('.dv-tilt');
    tiltElements.forEach(el => {
        if (!el) return;
        el.addEventListener('mousemove', (e) => {
            const rect = el.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = ((y - centerY) / centerY) * -3;
            const rotateY = ((x - centerX) / centerX) * 3;
            el.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });
        el.addEventListener('mouseleave', () => {
            el.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        });
    });

    // ===== 6. Scroll Reveal =====
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    
    document.querySelectorAll('.fade-up').forEach(el => {
        if (el) observer.observe(el);
    });

    // ===== 7. Parallax Effect =====
    const heroSection = document.querySelector('.dv-hero');
    if (heroSection && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const orbs = heroSection.querySelectorAll('.dv-orb');
            orbs.forEach((orb, index) => {
                const speed = (index + 1) * 0.25;
                orb.style.transform = `translateY(${scrolled * speed}px)`;
            });
        }, { passive: true });
    }

    // ===== 8. Toast Notification System =====
    window.dvShowToast = function(title, message) {
        const container = document.getElementById('dvToastContainer');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = 'dv-toast';
        toast.innerHTML = `
            <i class="fas fa-circle-check"></i>
            <div class="dv-toast-content">
                <div class="dv-toast-title">${title}</div>
                <div class="dv-toast-msg">${message}</div>
            </div>
        `;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'dvSlideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1) reverse forwards';
            setTimeout(() => toast.remove(), 400);
        }, 3500);
    };

    // ===== 9. Download with Progress & Tracking =====
    const recentList = document.getElementById('dvRecentList');
    let recentDownloads = JSON.parse(localStorage.getItem('dv_recent_downloads') || '[]');
    
    function renderRecent() {
        if (!recentList) return;
        if (recentDownloads.length === 0) {
            recentList.innerHTML = `
                <div class="dv-recent-empty">
                    <i class="fas fa-inbox"></i>
                    <span>No recent downloads</span>
                </div>
            `;
            return;
        }
        recentList.innerHTML = recentDownloads.slice(0, 3).map(item => `
            <div class="dv-recent-item">
                <i class="fas fa-file"></i>
                <span>${item.name.substring(0, 25)}${item.name.length > 25 ? '...' : ''}</span>
                <small>${item.time}</small>
            </div>
        `).join('');
    }
    renderRecent();

    document.querySelectorAll('.dv-download-btn[data-id]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const id = this.dataset.id;
            const name = this.dataset.name || 'Dokumen';
            const type = this.dataset.type || 'FILE';
            
            // Add progress animation
            this.classList.add('downloading');
            
            // Show toast
            dvShowToast('🔐 Secure Download Started', `${name}.${type.toLowerCase()}`);
            
            // Add to recent
            const now = new Date();
            const timeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
            recentDownloads.unshift({ name, type, time: timeStr });
            if (recentDownloads.length > 5) recentDownloads = recentDownloads.slice(0, 5);
            localStorage.setItem('dv_recent_downloads', JSON.stringify(recentDownloads));
            renderRecent();
            
            // Increment counter async
            try {
                fetch('<?= defined('SITE_URL') ? SITE_URL : '' ?>/dokumen-download.php?id=' + id, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).catch(err => console.warn('Download counter error:', err));
            } catch (err) {
                console.warn('Download fetch error:', err);
            }
            
            setTimeout(() => this.classList.remove('downloading'), 1500);
        });
    });

    // ===== 10. Live Search with Debounce =====
    const searchInput = document.getElementById('dvSearchInput');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = 'dokumen.php';
                
                const params = new URLSearchParams(window.location.search);
                
                if (params.has('kategori')) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden'; hidden.name = 'kategori'; hidden.value = params.get('kategori');
                    form.appendChild(hidden);
                }
                if (params.has('sort')) {
                    const hiddenSort = document.createElement('input');
                    hiddenSort.type = 'hidden'; hiddenSort.name = 'sort'; hiddenSort.value = params.get('sort');
                    form.appendChild(hiddenSort);
                }

                const q = document.createElement('input');
                q.type = 'hidden'; q.name = 'q'; q.value = e.target.value;
                form.appendChild(q);
                
                document.body.appendChild(form);
                form.submit();
            }, 600);
        });
    }

    // ===== 11. Keyboard Shortcuts =====
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) searchInput.focus();
        }
        if (e.key === 'Escape') {
            dvClosePreview();
        }
    });

    // ===== 12. Preview Modal =====
    const previewModal = document.getElementById('dvPreviewModal');
    
    window.dvPreview = function(title, type, size, downloads, description) {
        if (!previewModal) return;
        document.getElementById('dvPreviewTitle').textContent = title;
        document.getElementById('dvPreviewType').textContent = type;
        document.getElementById('dvPreviewSize').textContent = size;
        document.getElementById('dvPreviewDownloads').textContent = downloads.toLocaleString('id-ID');
        document.getElementById('dvPreviewDesc').textContent = description || 'No description available for this document.';
        previewModal.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.dvClosePreview = function() {
        if (!previewModal) return;
        previewModal.classList.remove('open');
        document.body.style.overflow = '';
    };

    if (previewModal) {
        previewModal.addEventListener('click', (e) => {
            if (e.target === previewModal) dvClosePreview();
        });
    }

    // ===== 13. Share Function =====
    window.dvShare = function(title) {
        const url = window.location.href;
        if (navigator.share) {
            navigator.share({ 
                title: title, 
                text: 'Download dokumen ini dari Informatika Pro Vault!', 
                url: url 
            }).catch(err => console.log('Share cancelled', err));
        } else {
            navigator.clipboard.writeText(url).then(() => {
                dvShowToast('🔗 Link Disalin', 'Link berhasil disalin ke clipboard!');
            });
        }
    };

    // ===== 14. Back to Top =====
    const backToTop = document.getElementById('dvBackToTop');
    if (backToTop) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 600) backToTop.classList.add('show');
            else backToTop.classList.remove('show');
        }, { passive: true });
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Welcome toast
    setTimeout(() => {
        dvShowToast('🔐 Welcome to Secure Vault', '<?= $totalAllDocs ?> documents ready for download');
    }, 1500);

    // Console Easter Egg
    console.log('%c╔════════════════════════════════════════╗', 'color:#10B981;font-weight:bold');
    console.log('%c║   SECURE VAULT - Document Repository   ║', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c╚════════════════════════════════════════╝', 'color:#10B981;font-weight:bold');
    console.log('%c🔒 256-bit encrypted | <?= $totalAllDocs ?> docs | <?= $storageFormatted ?>', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>