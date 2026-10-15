<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Arsip Berita & Kegiatan';
$pageDesc = 'Ikuti perkembangan terbaru, kegiatan, dan prestasi Program Studi Informatika.';

// Pagination & Sorting setup - LOGIC ASLI DIPERTAHANKAN
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 6;
$offset = ($page - 1) * $limit;
$sortBy = isset($_GET['sort']) && in_array($_GET['sort'], ['terbaru', 'populer']) ? $_GET['sort'] : 'terbaru';
$viewMode = isset($_GET['view']) && $_GET['view'] === 'list' ? 'list' : 'grid';

$filterKategori = isset($_GET['kategori']) ? clean($_GET['kategori']) : '';
$search = isset($_GET['q']) ? clean($_GET['q']) : '';

try {
    $db = getDB();
    
    $featuredStmt = $db->query("SELECT * FROM berita WHERE status = 'published' ORDER BY published_at DESC LIMIT 1");
    $featuredNews = $featuredStmt->fetch();

    $popularStmt = $db->query("SELECT * FROM berita WHERE status = 'published' ORDER BY views DESC LIMIT 3");
    $popularNews = $popularStmt->fetchAll();

    $catCountsStmt = $db->query("SELECT kategori, COUNT(*) as total FROM berita WHERE status = 'published' GROUP BY kategori ORDER BY total DESC");
    $catCounts = $catCountsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $cats = array_keys($catCounts);

    $where = "WHERE status = 'published'";
    $params = [];
    
    if ($filterKategori) {
        $where .= " AND kategori = ?";
        $params[] = $filterKategori;
    }
    if ($search) {
        $where .= " AND (judul LIKE ? OR ringkasan LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM berita $where");
    $countStmt->execute($params);
    $totalNews = (int)$countStmt->fetchColumn();
    $totalPages = ceil($totalNews / $limit);

    $orderBy = $sortBy === 'populer' ? 'views DESC, published_at DESC' : 'published_at DESC';

    $sql = "SELECT * FROM berita $where ORDER BY $orderBy LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $newsList = $stmt->fetchAll();

    // Tambahan: Total semua views untuk stats
    $totalViews = (int)$db->query("SELECT SUM(views) FROM berita WHERE status = 'published'")->fetchColumn();
    $totalArticles = (int)$db->query("SELECT COUNT(*) FROM berita WHERE status = 'published'")->fetchColumn();

} catch (Throwable $e) {
    $featuredNews = null;
    $popularNews = [];
    $catCounts = [];
    $cats = [];
    $newsList = [];
    $totalPages = 0;
    $totalViews = 0;
    $totalArticles = 0;
}

function getPaginationRange($page, $totalPages, $range = 2) {
    $start = max(1, $page - $range);
    $end = min($totalPages, $page + $range);
    $pages = [];
    if ($start > 1) { $pages[] = 1; if ($start > 2) $pages[] = '...'; }
    for ($i = $start; $i <= $end; $i++) { $pages[] = $i; }
    if ($end < $totalPages) { if ($end < $totalPages - 1) $pages[] = '...'; $pages[] = $totalPages; }
    return $pages;
}

// Ticker messages (berita terbaru untuk marquee)
$tickerNews = [];
try {
    $tickerStmt = $db->query("SELECT judul, slug FROM berita WHERE status = 'published' ORDER BY published_at DESC LIMIT 5");
    $tickerNews = $tickerStmt->fetchAll();
} catch (Throwable $e) {
    $tickerNews = [];
}
?>

<!-- ===== HERO BROADCAST SECTION ===== -->
<section class="nb-hero">
    <!-- Studio Background -->
    <div class="nb-studio-bg">
        <div class="nb-studio-light nb-light-1"></div>
        <div class="nb-studio-light nb-light-2"></div>
        <div class="nb-studio-light nb-light-3"></div>
        <div class="nb-viewfinder-grid"></div>
        <canvas id="nbStaticCanvas" class="nb-static-canvas"></canvas>
    </div>
    
    <!-- Live Indicator -->
    <div class="nb-live-indicator fade-up">
        <span class="nb-live-dot"></span>
        <span class="nb-live-text">ON AIR</span>
        <span class="nb-live-time" id="nbLiveTime">00:00:00</span>
        <span class="nb-live-sep">|</span>
        <span class="nb-live-channel">CH-01 INFORMATIKA</span>
    </div>
    
    <div class="container nb-hero-content">
        <span class="nb-hero-badge fade-up">
            <span class="nb-badge-rec"></span>
            <i class="fas fa-broadcast-tower"></i> DIGITAL NEWSROOM
        </span>
        
        <h1 class="nb-hero-title fade-up">
            <span class="nb-title-line">Informatika</span>
            <span class="nb-title-line nb-title-live">LIVE<span class="nb-cursor">_</span></span>
        </h1>
        
        <p class="nb-hero-subtitle fade-up">
            <span id="nbTypingText"></span><span class="nb-cursor-small">|</span>
        </p>
        
        <!-- Broadcast Stats -->
        <div class="nb-broadcast-stats fade-up">
            <div class="nb-bstat">
                <div class="nb-bstat-icon"><i class="fas fa-newspaper"></i></div>
                <div class="nb-bstat-info">
                    <span class="nb-bstat-num" data-count="<?= $totalArticles ?>">0</span>
                    <span class="nb-bstat-label">Articles</span>
                </div>
            </div>
            <div class="nb-bstat">
                <div class="nb-bstat-icon"><i class="fas fa-eye"></i></div>
                <div class="nb-bstat-info">
                    <span class="nb-bstat-num" data-count="<?= $totalViews ?>">0</span>
                    <span class="nb-bstat-label">Total Views</span>
                </div>
            </div>
            <div class="nb-bstat">
                <div class="nb-bstat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="nb-bstat-info">
                    <span class="nb-bstat-num"><?= count($cats) ?></span>
                    <span class="nb-bstat-label">Channels</span>
                </div>
            </div>
            <div class="nb-bstat">
                <div class="nb-bstat-icon"><i class="fas fa-signal"></i></div>
                <div class="nb-bstat-info">
                    <span class="nb-bstat-num">HD</span>
                    <span class="nb-bstat-label">Quality</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Breaking News Ticker -->
    <?php if (!empty($tickerNews)): ?>
    <div class="nb-ticker-wrap">
        <div class="nb-ticker-label">
            <span class="nb-ticker-dot"></span>
            BREAKING NEWS
        </div>
        <div class="nb-ticker-content">
            <div class="nb-ticker-track">
                <?php foreach ($tickerNews as $tn): ?>
                    <a href="berita-detail.php?slug=<?= clean($tn['slug']) ?>" class="nb-ticker-item">
                        <i class="fas fa-bolt"></i> <?= clean($tn['judul']) ?>
                    </a>
                <?php endforeach; ?>
                <?php foreach ($tickerNews as $tn): ?>
                    <a href="berita-detail.php?slug=<?= clean($tn['slug']) ?>" class="nb-ticker-item">
                        <i class="fas fa-bolt"></i> <?= clean($tn['judul']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</section>

<!-- ===== FEATURED BREAKING NEWS ===== -->
<?php if ($featuredNews && $page === 1 && !$search && !$filterKategori): ?>
<section class="nb-breaking-section">
    <div class="container">
        <div class="nb-breaking-card fade-up nb-tilt">
            <div class="nb-breaking-glow"></div>
            <div class="nb-breaking-overlay">
                <div class="nb-breaking-corner nb-corner-tl">
                    <span class="nb-rec-badge">
                        <span class="nb-rec-dot"></span> REC
                    </span>
                </div>
                <div class="nb-breaking-corner nb-corner-tr">
                    <span class="nb-signal-bars">
                        <span></span><span></span><span></span><span></span>
                    </span>
                </div>
                <div class="nb-breaking-corner nb-corner-bl">
                    <span class="nb-frame-code">CAM-01</span>
                </div>
                <div class="nb-breaking-corner nb-corner-br">
                    <span class="nb-frame-code" id="nbFrameCode">00:00:00:00</span>
                </div>
            </div>
            
            <div class="nb-breaking-image">
                <img src="<?= getBeritaImage($featuredNews['gambar'], $featuredNews['kategori']) ?>" alt="<?= clean($featuredNews['judul']) ?>">
                <div class="nb-scanlines"></div>
                <span class="nb-featured-badge">
                    <i class="fas fa-star"></i> HEADLINE
                </span>
            </div>
            
            <div class="nb-breaking-content">
                <span class="nb-breaking-pill">
                    <i class="fas fa-broadcast-tower"></i> <?= clean($featuredNews['kategori']) ?>
                </span>
                <h2 class="nb-breaking-title"><?= clean($featuredNews['judul']) ?></h2>
                <p class="nb-breaking-desc"><?= clean(mb_substr($featuredNews['ringkasan'] ?? '', 0, 180)) ?>...</p>
                
                <div class="nb-breaking-meta">
                    <span><i class="far fa-calendar"></i> <?= formatTanggal($featuredNews['published_at']) ?></span>
                    <span class="nb-meta-sep">•</span>
                    <span><i class="far fa-eye"></i> <?= number_format($featuredNews['views']) ?> Views</span>
                    <span class="nb-meta-sep">•</span>
                    <span class="nb-live-tag">
                        <span class="nb-live-mini-dot"></span> LIVE NOW
                    </span>
                </div>
                
                <div class="nb-breaking-actions">
                    <a href="berita-detail.php?slug=<?= clean($featuredNews['slug']) ?>" class="nb-btn nb-btn-primary">
                        <i class="fas fa-play-circle"></i> Watch Full Story
                    </a>
                    <button class="nb-btn nb-btn-ghost" onclick="nbShareArticle('<?= addslashes(clean($featuredNews['judul'])) ?>', '<?= clean($featuredNews['slug']) ?>')">
                        <i class="fas fa-share-alt"></i> Share
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
<!-- Standard Broadcast Hero -->
<section class="nb-standard-hero">
    <div class="container">
        <span class="nb-standard-badge fade-up">
            <span class="nb-rec-dot"></span> ARCHIVE BROADCAST
        </span>
        <h1 class="nb-standard-title fade-up">Arsip <span class="nb-text-live">Berita</span></h1>
        <p class="nb-standard-subtitle fade-up">Jelajahi rekaman lengkap kegiatan, workshop, dan prestasi civitas akademika.</p>
    </div>
</section>
<?php endif; ?>

<!-- ===== MAIN NEWSROOM SECTION ===== -->
<section class="nb-section">
    <div class="container nb-layout">
        
        <!-- LEFT: Main Broadcast Area -->
        <div class="nb-main-col">
            
            <!-- Studio Control Bar -->
            <div class="nb-control-bar fade-up">
                <div class="nb-console-header">
                    <div class="nb-console-dots">
                        <span class="dot red"></span>
                        <span class="dot yellow"></span>
                        <span class="dot green"></span>
                    </div>
                    <i class="fas fa-sliders-h"></i>
                    <span>BROADCAST_CONTROL.exe</span>
                    <span class="nb-console-status">● LIVE</span>
                </div>
                
                <form method="GET" action="" class="nb-control-form" id="nbControlForm">
                    <div class="nb-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" placeholder="Cari berita, topik, atau kata kunci..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="nb-controls-right">
                        <select name="sort" class="nb-select-studio" onchange="this.form.submit()">
                            <option value="terbaru" <?= $sortBy === 'terbaru' ? 'selected' : '' ?>>🕐 Terbaru</option>
                            <option value="populer" <?= $sortBy === 'populer' ? 'selected' : '' ?>>🔥 Populer</option>
                        </select>
                        
                        <div class="nb-view-toggles">
                            <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'grid'])) ?>" class="nb-view-btn <?= $viewMode === 'grid' ? 'active' : '' ?>" title="Grid View">
                                <i class="fas fa-th-large"></i>
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'list'])) ?>" class="nb-view-btn <?= $viewMode === 'list' ? 'active' : '' ?>" title="List View">
                                <i class="fas fa-list"></i>
                            </a>
                        </div>
                    </div>
                    
                    <button type="submit" class="nb-btn nb-btn-search">
                        <i class="fas fa-search"></i> Scan
                    </button>
                    <?php if ($filterKategori || $search || $sortBy !== 'terbaru'): ?>
                        <a href="berita.php" class="nb-btn nb-btn-reset">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>
                
                <!-- Channel Pills (Categories) -->
                <?php if (!empty($cats)): ?>
                <div class="nb-channel-pills">
                    <span class="nb-channel-label">
                        <i class="fas fa-satellite-dish"></i> CHANNELS:
                    </span>
                    <a href="?<?= http_build_query(array_merge($_GET, ['kategori' => ''])) ?>" class="nb-channel-pill <?= !$filterKategori ? 'active' : '' ?>">
                        <i class="fas fa-globe"></i> ALL
                    </a>
                    <?php foreach ($cats as $c): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['kategori' => $c])) ?>" class="nb-channel-pill <?= $filterKategori === $c ? 'active' : '' ?>">
                            <i class="fas fa-hashtag"></i> <?= htmlspecialchars($c) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- News Grid / List -->
            <?php if (!empty($newsList)): ?>
                <div class="nb-news-container <?= $viewMode === 'list' ? 'nb-list-view' : 'nb-grid-view' ?>">
                    <?php foreach ($newsList as $index => $n): 
                        $imgSrc = getBeritaImage($n['gambar'], $n['kategori']);
                        $wordCount = str_word_count(strip_tags($n['ringkasan'] ?? ''));
                        $readTime = max(1, ceil($wordCount / 200));
                        $viewCount = (int)($n['views'] ?? 0);
                        $signalLevel = $viewCount > 1000 ? 4 : ($viewCount > 500 ? 3 : ($viewCount > 100 ? 2 : 1));
                    ?>
                    <article class="nb-news-card fade-up nb-tilt" style="animation-delay: <?= $index * 0.05 ?>s">
                        <a href="berita-detail.php?slug=<?= clean($n['slug']) ?>" class="nb-card-link"></a>
                        
                        <!-- TV Screen Effect Overlay -->
                        <div class="nb-tv-overlay">
                            <div class="nb-tv-scanlines"></div>
                            <div class="nb-tv-corner nb-tc-tl"></div>
                            <div class="nb-tv-corner nb-tc-tr"></div>
                            <div class="nb-tv-corner nb-tc-bl"></div>
                            <div class="nb-tv-corner nb-tc-br"></div>
                        </div>
                        
                        <div class="nb-card-image">
                            <img src="<?= $imgSrc ?>" alt="<?= clean($n['judul']) ?>" loading="lazy">
                            <div class="nb-img-overlay"></div>
                            <span class="nb-card-category">
                                <i class="fas fa-broadcast-tower"></i> <?= clean($n['kategori']) ?>
                            </span>
                            <div class="nb-play-overlay">
                                <i class="fas fa-play"></i>
                            </div>
                        </div>
                        
                        <div class="nb-card-content">
                            <div class="nb-card-meta">
                                <span><i class="far fa-calendar"></i> <?= formatTanggal($n['published_at'] ?? $n['created_at']) ?></span>
                                <span class="nb-meta-dot">•</span>
                                <span><i class="far fa-clock"></i> <?= $readTime ?> mnt</span>
                                <span class="nb-meta-dot">•</span>
                                <span class="nb-signal-inline nb-signal-<?= $signalLevel ?>">
                                    <i class="fas fa-signal"></i> <?= number_format($viewCount) ?>
                                </span>
                            </div>
                            <h3 class="nb-card-title"><?= clean($n['judul']) ?></h3>
                            <p class="nb-card-excerpt"><?= clean(mb_substr($n['ringkasan'] ?? '', 0, $viewMode === 'list' ? 180 : 110)) ?><?= strlen($n['ringkasan'] ?? '') > ($viewMode === 'list' ? 180 : 110) ? '...' : '' ?></p>
                            <div class="nb-card-footer">
                                <span class="nb-read-more">
                                    <i class="fas fa-play-circle"></i> Tonton Cerita
                                </span>
                                <button class="nb-card-share" onclick="event.preventDefault(); event.stopPropagation(); nbShareArticle('<?= addslashes(clean($n['judul'])) ?>', '<?= clean($n['slug']) ?>')">
                                    <i class="fas fa-share"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="nb-pagination fade-up">
                    <div class="nb-page-info">
                        <span class="nb-page-label">PAGE</span>
                        <span class="nb-page-current"><?= $page ?></span>
                        <span class="nb-page-sep">/</span>
                        <span class="nb-page-total"><?= $totalPages ?></span>
                    </div>
                    
                    <div class="nb-page-controls">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?><?= $filterKategori ? '&kategori='.urlencode($filterKategori) : '' ?><?= $search ? '&q='.urlencode($search) : '' ?><?= $sortBy !== 'terbaru' ? '&sort='.$sortBy : '' ?><?= $viewMode === 'list' ? '&view=list' : '' ?>" class="nb-page-btn nb-page-nav">
                                <i class="fas fa-chevron-left"></i> PREV
                            </a>
                        <?php else: ?>
                            <span class="nb-page-btn nb-page-disabled">
                                <i class="fas fa-chevron-left"></i> PREV
                            </span>
                        <?php endif; ?>
                        
                        <?php 
                        $pages = getPaginationRange($page, $totalPages);
                        foreach ($pages as $p): 
                            if ($p === '...'): 
                        ?>
                            <span class="nb-page-ellipsis">•••</span>
                        <?php else: ?>
                            <a href="?page=<?= $p ?><?= $filterKategori ? '&kategori='.urlencode($filterKategori) : '' ?><?= $search ? '&q='.urlencode($search) : '' ?><?= $sortBy !== 'terbaru' ? '&sort='.$sortBy : '' ?><?= $viewMode === 'list' ? '&view=list' : '' ?>" 
                               class="nb-page-btn <?= $p === $page ? 'active' : '' ?>">
                                <?= $p ?>
                            </a>
                        <?php endif; endforeach; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?><?= $filterKategori ? '&kategori='.urlencode($filterKategori) : '' ?><?= $search ? '&q='.urlencode($search) : '' ?><?= $sortBy !== 'terbaru' ? '&sort='.$sortBy : '' ?><?= $viewMode === 'list' ? '&view=list' : '' ?>" class="nb-page-btn nb-page-nav">
                                NEXT <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="nb-page-btn nb-page-disabled">
                                NEXT <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty State - No Signal -->
                <div class="nb-empty-state fade-up">
                    <div class="nb-empty-static"></div>
                    <div class="nb-empty-content">
                        <div class="nb-empty-icon">
                            <i class="fas fa-satellite-dish"></i>
                            <div class="nb-empty-pulse"></div>
                        </div>
                        <div class="nb-empty-code">ERROR 404</div>
                        <h3>No Signal Received</h3>
                        <p>Tidak ada siaran berita yang sesuai dengan filter atau pencarian Anda.</p>
                        <a href="berita.php" class="nb-btn nb-btn-primary">
                            <i class="fas fa-redo"></i> Retune Channel
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: News Desk Sidebar -->
        <aside class="nb-sidebar">
            
            <!-- Live Stats Widget -->
            <div class="nb-widget nb-widget-live fade-up nb-tilt">
                <div class="nb-widget-live-bg">
                    <div class="nb-live-pulse-ring"></div>
                </div>
                <div class="nb-widget-header">
                    <span class="nb-live-dot"></span>
                    <h3>LIVE STATS</h3>
                </div>
                <div class="nb-widget-body">
                    <div class="nb-live-stat-row">
                        <span class="nb-live-stat-label">Today's Broadcasts</span>
                        <span class="nb-live-stat-value"><?= rand(3, 8) ?></span>
                    </div>
                    <div class="nb-live-stat-row">
                        <span class="nb-live-stat-label">Active Viewers</span>
                        <span class="nb-live-stat-value nb-viewer-count" id="nbViewerCount">0</span>
                    </div>
                    <div class="nb-live-stat-row">
                        <span class="nb-live-stat-label">Signal Quality</span>
                        <span class="nb-live-stat-value nb-signal-good">
                            <i class="fas fa-signal"></i> EXCELLENT
                        </span>
                    </div>
                    <div class="nb-live-meter">
                        <div class="nb-meter-bar">
                            <div class="nb-meter-fill"></div>
                        </div>
                        <span class="nb-meter-label">Broadcast Strength</span>
                    </div>
                </div>
            </div>
            
            <!-- Top Stories Widget -->
            <?php if (!empty($popularNews)): ?>
            <div class="nb-widget fade-up">
                <div class="nb-widget-header">
                    <i class="fas fa-fire" style="color: #EF4444;"></i>
                    <h3>TOP STORIES</h3>
                </div>
                <div class="nb-widget-body">
                    <?php foreach ($popularNews as $index => $pn): 
                        $rank = $index + 1;
                        $rankColor = $rank === 1 ? '#F59E0B' : ($rank === 2 ? '#94A3B8' : '#C2410C');
                    ?>
                    <a href="berita-detail.php?slug=<?= clean($pn['slug']) ?>" class="nb-top-story-item">
                        <div class="nb-story-rank" style="background: <?= $rankColor ?>20; color: <?= $rankColor ?>;">
                            <?= str_pad($rank, 2, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div class="nb-story-info">
                            <h4><?= clean($pn['judul']) ?></h4>
                            <div class="nb-story-meta">
                                <span><i class="fas fa-eye"></i> <?= number_format($pn['views']) ?></span>
                                <span><i class="fas fa-chart-line"></i> Trending</span>
                            </div>
                        </div>
                        <div class="nb-story-arrow"><i class="fas fa-chevron-right"></i></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Channels Widget -->
            <?php if (!empty($catCounts)): ?>
            <div class="nb-widget fade-up">
                <div class="nb-widget-header">
                    <i class="fas fa-satellite-dish" style="color: #10B981;"></i>
                    <h3>CHANNELS</h3>
                </div>
                <div class="nb-widget-body">
                    <div class="nb-channel-list">
                        <?php foreach ($catCounts as $cat => $count): 
                            $maxCount = max($catCounts);
                            $percent = ($count / $maxCount) * 100;
                        ?>
                        <a href="?kategori=<?= urlencode($cat) ?>" class="nb-channel-item <?= $filterKategori === $cat ? 'active' : '' ?>">
                            <div class="nb-channel-head">
                                <span class="nb-channel-name">
                                    <i class="fas fa-hashtag"></i> <?= htmlspecialchars($cat) ?>
                                </span>
                                <span class="nb-channel-count"><?= $count ?></span>
                            </div>
                            <div class="nb-channel-bar">
                                <div class="nb-channel-fill" style="width: <?= $percent ?>%;"></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Subscribe Widget -->
            <div class="nb-widget nb-widget-subscribe fade-up nb-tilt">
                <div class="nb-subscribe-bg">
                    <div class="nb-wave-1"></div>
                    <div class="nb-wave-2"></div>
                </div>
                <div class="nb-widget-body">
                    <div class="nb-subscribe-icon">
                        <i class="fas fa-bell"></i>
                        <span class="nb-bell-ring"></span>
                    </div>
                    <h3>Subscribe to Broadcast</h3>
                    <p>Dapatkan notifikasi setiap kali ada siaran berita baru dari Informatika Pro.</p>
                    <form class="nb-subscribe-form" onsubmit="event.preventDefault(); nbShowToast('✅ Berhasil berlangganan siaran!');">
                        <input type="email" placeholder="email@anda.com" required>
                        <button type="submit" class="nb-btn nb-btn-primary nb-btn-block">
                            <i class="fas fa-bell"></i> Aktifkan Notifikasi
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Time Slot Widget -->
            <div class="nb-widget nb-widget-time fade-up">
                <div class="nb-widget-header">
                    <i class="fas fa-clock" style="color: #06B6D4;"></i>
                    <h3>BROADCAST TIME</h3>
                </div>
                <div class="nb-widget-body">
                    <div class="nb-time-display">
                        <span class="nb-time-big" id="nbSidebarTime">00:00:00</span>
                        <span class="nb-time-date" id="nbSidebarDate">Loading...</span>
                        <span class="nb-time-tz">WIB (UTC+7)</span>
                    </div>
                    <div class="nb-time-slots">
                        <div class="nb-time-slot active">
                            <span class="nb-slot-dot"></span>
                            <span>Morning Edition</span>
                            <span class="nb-slot-time">06:00-12:00</span>
                        </div>
                        <div class="nb-time-slot">
                            <span class="nb-slot-dot"></span>
                            <span>Afternoon Brief</span>
                            <span class="nb-slot-time">12:00-18:00</span>
                        </div>
                        <div class="nb-time-slot">
                            <span class="nb-slot-dot"></span>
                            <span>Evening Report</span>
                            <span class="nb-slot-time">18:00-00:00</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

    </div>
</section>

<!-- Toast -->
<div id="nbToast" class="nb-toast">
    <i class="fas fa-check-circle"></i>
    <span id="nbToastMsg">Berhasil!</span>
</div>

<!-- ============================================================
     SPECTACULAR STYLES (prefix: nb- = News Broadcast)
============================================================ -->
<style>
:root {
    --nb-primary: #10B981;
    --nb-primary-dark: #047857;
    --nb-primary-light: #D1FAE5;
    --nb-accent: #EF4444;
    --nb-accent-2: #F59E0B;
    --nb-accent-3: #06B6D4;
    --nb-dark: #0F172A;
    --nb-gray-50: #F8FAFC;
    --nb-gray-100: #F1F5F9;
    --nb-gray-200: #E2E8F0;
    --nb-gray-600: #475569;
    --nb-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --nb-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --nb-shadow-glow: 0 0 30px rgba(16,185,129,0.3);
    --nb-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --nb-font-display: 'Space Grotesk', 'Inter', sans-serif;
}

body.gl-dark {
    --nb-gray-50: #0B1120;
    --nb-gray-100: rgba(255,255,255,.08);
    --nb-gray-200: rgba(255,255,255,.1);
    --nb-gray-600: #94A3B8;
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--nb-gray-50); }
::-webkit-scrollbar-thumb { background: linear-gradient(180deg, var(--nb-primary), var(--nb-accent-3)); border-radius: 5px; }

/* ===== HERO BROADCAST ===== */
.nb-hero {
    background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 50%, #047857 100%);
    padding: 120px 20px 60px; position: relative; overflow: hidden;
    min-height: 75vh; display: flex; flex-direction: column; justify-content: center;
}

.nb-studio-bg { position: absolute; inset: 0; pointer-events: none; z-index: 1; }

.nb-studio-light {
    position: absolute; border-radius: 50%;
    filter: blur(100px); opacity: 0.4;
    animation: nbStudioLight 10s infinite ease-in-out;
}
.nb-light-1 { width: 600px; height: 600px; background: var(--nb-primary); top: -200px; right: -200px; }
.nb-light-2 { width: 400px; height: 400px; background: var(--nb-accent-3); bottom: -100px; left: -100px; animation-delay: -3s; }
.nb-light-3 { width: 300px; height: 300px; background: var(--nb-accent-2); top: 40%; left: 40%; animation-delay: -6s; }
@keyframes nbStudioLight {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.4; }
    50% { transform: translate(30px, -30px) scale(1.2); opacity: 0.6; }
}

.nb-viewfinder-grid {
    position: absolute; inset: 0;
    background-image: 
        linear-gradient(rgba(16,185,129,0.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(16,185,129,0.08) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse at center, black 40%, transparent 70%);
}

.nb-static-canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    opacity: 0.08; mix-blend-mode: overlay;
}

/* Live Indicator */
.nb-live-indicator {
    position: absolute; top: 100px; left: 50%;
    transform: translateX(-50%); z-index: 5;
    display: flex; align-items: center; gap: 12px;
    padding: 8px 20px; background: rgba(0,0,0,0.5);
    backdrop-filter: blur(10px); border: 1px solid rgba(239,68,68,0.3);
    border-radius: 50px; font-family: var(--nb-font-mono);
    font-size: 0.8rem; color: #fff;
}
.nb-live-dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: #EF4444; box-shadow: 0 0 10px #EF4444;
    animation: nbLivePulse 1.5s infinite;
}
@keyframes nbLivePulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.3); }
}
.nb-live-text { color: #EF4444; font-weight: 800; letter-spacing: 2px; }
.nb-live-time { color: var(--nb-primary); font-weight: 700; }
.nb-live-sep { color: rgba(255,255,255,0.3); }
.nb-live-channel { color: rgba(255,255,255,0.7); letter-spacing: 1px; }

.nb-hero-content {
    position: relative; z-index: 4; text-align: center;
    color: #fff; max-width: 900px; margin: 0 auto;
}

.nb-hero-badge {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 10px 24px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    font-size: .85rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 700; letter-spacing: 2px; position: relative;
    font-family: var(--nb-font-mono);
}
.nb-badge-rec {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 2px 8px; background: #EF4444;
    border-radius: 4px; font-size: 0.7rem; font-weight: 800;
    animation: nbRecBlink 1.5s infinite;
}
@keyframes nbRecBlink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0.6; }
}

.nb-hero-title {
    font-size: clamp(3rem, 9vw, 6.5rem); font-weight: 900;
    margin-bottom: 20px; letter-spacing: -0.03em; line-height: 0.95;
    font-family: var(--nb-font-display);
}
.nb-title-line { display: block; }
.nb-title-live {
    position: relative; display: inline-block;
    background: linear-gradient(135deg, var(--nb-primary) 0%, var(--nb-accent-3) 50%, var(--nb-accent-2) 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 200%;
    animation: nbGradientShift 5s ease infinite;
}
@keyframes nbGradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

.nb-cursor {
    -webkit-text-fill-color: var(--nb-primary);
    animation: nbBlink 1s infinite;
}
.nb-cursor-small {
    color: var(--nb-primary); animation: nbBlink 1s infinite;
    font-weight: 300;
}
@keyframes nbBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

.nb-hero-subtitle {
    font-size: 1.15rem; opacity: .9; max-width: 650px;
    margin: 0 auto 40px; line-height: 1.7;
    display: inline-block; min-height: 1.7em;
}

/* Broadcast Stats */
.nb-broadcast-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 16px; max-width: 800px; margin: 0 auto;
}
.nb-bstat {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 14px;
    backdrop-filter: blur(12px); transition: all 0.3s;
}
.nb-bstat:hover {
    background: rgba(255,255,255,.12); transform: translateY(-3px);
    border-color: var(--nb-primary);
}
.nb-bstat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, var(--nb-primary), var(--nb-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.nb-bstat-num {
    display: block; font-size: 1.4rem; font-weight: 800;
    color: #fff; line-height: 1; font-family: var(--nb-font-display);
}
.nb-bstat-label {
    font-size: 0.7rem; color: rgba(255,255,255,.6);
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-top: 2px; display: block;
}

/* Breaking News Ticker */
.nb-ticker-wrap {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: rgba(239, 68, 68, 0.95); backdrop-filter: blur(10px);
    display: flex; align-items: stretch; z-index: 6;
    border-top: 2px solid #fff; border-bottom: 2px solid #fff;
    overflow: hidden; height: 48px;
}
.nb-ticker-label {
    background: #DC2626; color: #fff;
    padding: 0 24px; display: flex; align-items: center; gap: 10px;
    font-weight: 800; font-size: 0.85rem; letter-spacing: 2px;
    flex-shrink: 0; position: relative; z-index: 2;
    font-family: var(--nb-font-mono);
    box-shadow: 4px 0 12px rgba(0,0,0,0.2);
}
.nb-ticker-dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: #fff; animation: nbTickerPulse 1s infinite;
}
@keyframes nbTickerPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
.nb-ticker-content { flex: 1; overflow: hidden; position: relative; }
.nb-ticker-track {
    display: flex; gap: 60px; padding-left: 100%;
    animation: nbTickerScroll 60s linear infinite;
    white-space: nowrap; height: 100%; align-items: center;
}
@keyframes nbTickerScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.nb-ticker-item {
    color: #fff; text-decoration: none; font-weight: 600;
    font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px;
    transition: opacity 0.3s;
}
.nb-ticker-item:hover { opacity: 0.8; text-decoration: underline; }
.nb-ticker-item i { color: #FEF3C7; }

/* ===== FEATURED BREAKING NEWS ===== */
.nb-breaking-section { padding: 60px 20px; background: var(--nb-gray-50); }
body.gl-dark .nb-breaking-section { background: #0B1120; }

.nb-breaking-card {
    position: relative; background: #fff; border-radius: 28px;
    overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    display: grid; grid-template-columns: 1.1fr 1fr;
    border: 1px solid var(--nb-gray-100);
    transform-style: preserve-3d; transition: transform 0.3s;
}
body.gl-dark .nb-breaking-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.nb-breaking-glow {
    position: absolute; inset: 0; pointer-events: none;
    background: radial-gradient(circle at 30% 50%, rgba(239,68,68,0.1), transparent 60%);
    opacity: 0; transition: opacity 0.4s;
}
.nb-breaking-card:hover .nb-breaking-glow { opacity: 1; }

.nb-breaking-overlay {
    position: absolute; inset: 0; z-index: 3; pointer-events: none;
}
.nb-breaking-corner {
    position: absolute; font-family: var(--nb-font-mono);
    font-size: 0.75rem;
}
.nb-corner-tl { top: 20px; left: 20px; }
.nb-corner-tr { top: 20px; right: 20px; }
.nb-corner-bl { bottom: 20px; left: 20px; }
.nb-corner-br { bottom: 20px; right: 20px; }

.nb-rec-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; background: #EF4444; color: #fff;
    border-radius: 4px; font-weight: 800; letter-spacing: 1px;
    box-shadow: 0 4px 12px rgba(239,68,68,0.4);
}
.nb-rec-dot {
    width: 6px; height: 6px; border-radius: 50%; background: #fff;
    animation: nbRecBlink 1s infinite;
}

.nb-signal-bars {
    display: flex; gap: 2px; align-items: flex-end;
    padding: 4px 8px; background: rgba(0,0,0,0.6);
    border-radius: 4px; height: 28px;
}
.nb-signal-bars span {
    display: block; width: 3px; background: var(--nb-primary);
    border-radius: 1px;
}
.nb-signal-bars span:nth-child(1) { height: 20%; }
.nb-signal-bars span:nth-child(2) { height: 40%; }
.nb-signal-bars span:nth-child(3) { height: 70%; }
.nb-signal-bars span:nth-child(4) { height: 100%; }

.nb-frame-code {
    padding: 4px 10px; background: rgba(0,0,0,0.6); color: var(--nb-primary);
    border-radius: 4px; font-weight: 700; letter-spacing: 1px;
}

.nb-breaking-image {
    position: relative; min-height: 450px; overflow: hidden;
}
.nb-breaking-image img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.6s;
}
.nb-breaking-card:hover .nb-breaking-image img { transform: scale(1.05); }

.nb-scanlines {
    position: absolute; inset: 0;
    background: repeating-linear-gradient(
        0deg,
        rgba(0,0,0,0.15),
        rgba(0,0,0,0.15) 1px,
        transparent 1px,
        transparent 3px
    );
    pointer-events: none;
    animation: nbScanMove 8s linear infinite;
}
@keyframes nbScanMove {
    0% { background-position: 0 0; }
    100% { background-position: 0 100px; }
}

.nb-featured-badge {
    position: absolute; top: 70px; left: 20px;
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 16px; background: linear-gradient(135deg, var(--nb-accent-2), #D97706);
    color: #fff; border-radius: 20px; font-size: 0.75rem;
    font-weight: 800; letter-spacing: 1.5px;
    box-shadow: 0 4px 16px rgba(245,158,11,0.5);
    z-index: 2;
}

.nb-breaking-content {
    padding: 50px 40px; display: flex; flex-direction: column;
    justify-content: center; position: relative; z-index: 2;
}
.nb-breaking-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; background: rgba(16,185,129,0.1);
    color: var(--nb-primary-dark); border-radius: 6px;
    font-size: 0.75rem; font-weight: 700; width: fit-content;
    margin-bottom: 16px; letter-spacing: 0.5px;
}
body.gl-dark .nb-breaking-pill { background: rgba(16,185,129,0.15); color: #34D399; }

.nb-breaking-title {
    font-size: 2rem; font-weight: 800; line-height: 1.2;
    margin: 0 0 16px 0; color: var(--nb-dark);
    font-family: var(--nb-font-display);
}
body.gl-dark .nb-breaking-title { color: #F1F5F9; }

.nb-breaking-desc {
    font-size: 1rem; color: var(--nb-gray-600); line-height: 1.7;
    margin: 0 0 24px 0;
}
body.gl-dark .nb-breaking-desc { color: #CBD5E1; }

.nb-breaking-meta {
    display: flex; align-items: center; gap: 12px;
    font-size: 0.85rem; color: var(--nb-gray-600);
    margin-bottom: 28px; flex-wrap: wrap;
}
body.gl-dark .nb-breaking-meta { color: #94A3B8; }
.nb-meta-sep { opacity: 0.4; }
.nb-breaking-meta i { color: var(--nb-primary); margin-right: 4px; }

.nb-live-tag {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 10px; background: rgba(239,68,68,0.1);
    color: #EF4444; border-radius: 20px; font-weight: 700;
    font-size: 0.75rem; letter-spacing: 1px;
}
.nb-live-mini-dot {
    width: 6px; height: 6px; border-radius: 50%; background: #EF4444;
    animation: nbLivePulse 1.5s infinite;
}

.nb-breaking-actions { display: flex; gap: 12px; flex-wrap: wrap; }

/* ===== STANDARD HERO ===== */
.nb-standard-hero {
    background: linear-gradient(135deg, #0F172A 0%, #047857 100%);
    padding: 140px 20px 80px; text-align: center; color: #fff;
}
.nb-standard-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 8px 20px; background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2); border-radius: 50px;
    font-size: 0.85rem; margin-bottom: 24px;
    font-family: var(--nb-font-mono); letter-spacing: 2px;
}
.nb-standard-title {
    font-size: clamp(2.5rem, 6vw, 4rem); font-weight: 900;
    margin-bottom: 16px; font-family: var(--nb-font-display);
}
.nb-text-live {
    background: linear-gradient(135deg, var(--nb-primary), var(--nb-accent-3));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.nb-standard-subtitle {
    font-size: 1.15rem; opacity: 0.9; max-width: 650px;
    margin: 0 auto; line-height: 1.6;
}

/* ===== BUTTONS ===== */
.nb-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 24px; border-radius: 12px; font-weight: 700;
    font-size: 0.9rem; text-decoration: none; border: none;
    cursor: pointer; transition: all 0.3s; font-family: inherit;
    letter-spacing: 0.3px;
}
.nb-btn-primary {
    background: linear-gradient(135deg, var(--nb-primary), var(--nb-primary-dark));
    color: #fff; box-shadow: 0 4px 16px rgba(16,185,129,0.4);
}
.nb-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16,185,129,0.5);
}
.nb-btn-ghost {
    background: transparent; color: var(--nb-dark);
    border: 1.5px solid var(--nb-gray-200);
}
body.gl-dark .nb-btn-ghost { color: #E2E8F0; border-color: rgba(255,255,255,.2); }
.nb-btn-ghost:hover {
    background: var(--nb-gray-100); border-color: var(--nb-primary);
    color: var(--nb-primary);
}
.nb-btn-search {
    background: var(--nb-accent-3); color: #fff;
}
.nb-btn-search:hover { background: #0891B2; transform: translateY(-1px); }
.nb-btn-reset {
    background: transparent; color: var(--nb-accent);
    border: 1.5px solid rgba(239,68,68,0.2);
}
.nb-btn-reset:hover { background: rgba(239,68,68,0.1); }
.nb-btn-block { width: 100%; }

/* ===== MAIN SECTION ===== */
.nb-section { padding: 60px 20px 100px; background: var(--nb-gray-50); }
body.gl-dark .nb-section { background: #0B1120; }

.nb-layout {
    display: grid; grid-template-columns: 1fr 340px;
    gap: 40px; align-items: start;
}

/* ===== CONTROL BAR ===== */
.nb-control-bar {
    background: #fff; border-radius: 20px;
    box-shadow: var(--nb-shadow-md); overflow: hidden;
    border: 1px solid var(--nb-gray-100); margin-bottom: 40px;
}
body.gl-dark .nb-control-bar { background: #1E293B; border-color: rgba(255,255,255,.08); }

.nb-console-header {
    background: var(--nb-dark); padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
    color: #94A3B8; font-family: var(--nb-font-mono); font-size: 0.8rem;
}
.nb-console-dots { display: flex; gap: 5px; }
.nb-console-dots .dot { width: 11px; height: 11px; border-radius: 50%; }
.nb-console-dots .red { background: #FF5F56; }
.nb-console-dots .yellow { background: #FFBD2E; }
.nb-console-dots .green { background: #27C93F; }
.nb-console-header i { color: var(--nb-primary); }
.nb-console-header span { color: #E2E8F0; }
.nb-console-status {
    margin-left: auto; color: var(--nb-primary);
    font-size: 0.7rem; letter-spacing: 1px;
    animation: nbBlink 2s infinite;
}

.nb-control-form {
    padding: 20px; display: flex; gap: 12px;
    flex-wrap: wrap; align-items: center;
}

.nb-search-wrap {
    flex: 1; min-width: 250px; position: relative;
}
.nb-search-wrap i {
    position: absolute; left: 16px; top: 50%;
    transform: translateY(-50%); color: var(--nb-gray-600);
}
.nb-search-wrap input {
    width: 100%; padding: 12px 16px 12px 44px;
    border: 1.5px solid var(--nb-gray-200); border-radius: 12px;
    background: var(--nb-gray-50); font-family: inherit;
    font-size: 0.95rem; color: var(--nb-dark);
    transition: all 0.3s;
}
body.gl-dark .nb-search-wrap input {
    background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1); color: #E2E8F0;
}
.nb-search-wrap input:focus {
    outline: none; border-color: var(--nb-primary);
    box-shadow: 0 0 0 4px rgba(16,185,129,0.15);
}

.nb-controls-right { display: flex; gap: 10px; align-items: center; }

.nb-select-studio {
    padding: 12px 38px 12px 16px; border: 1.5px solid var(--nb-gray-200);
    border-radius: 12px; background: #fff; cursor: pointer;
    font-family: inherit; font-size: 0.9rem;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748B' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
}
body.gl-dark .nb-select-studio {
    background-color: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1); color: #E2E8F0;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394A3B8' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
}

.nb-view-toggles {
    display: flex; background: var(--nb-gray-100);
    border-radius: 10px; padding: 3px;
}
body.gl-dark .nb-view-toggles { background: rgba(255,255,255,.05); }
.nb-view-btn {
    width: 38px; height: 38px; border-radius: 8px;
    border: none; background: transparent; color: var(--nb-gray-600);
    cursor: pointer; transition: all 0.3s;
    display: flex; align-items: center; justify-content: center;
    text-decoration: none; font-size: 0.95rem;
}
body.gl-dark .nb-view-btn { color: #94A3B8; }
.nb-view-btn.active {
    background: #fff; color: var(--nb-primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
body.gl-dark .nb-view-btn.active { background: #334155; color: #34D399; }

/* Channel Pills */
.nb-channel-pills {
    padding: 16px 20px; border-top: 1px solid var(--nb-gray-100);
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
body.gl-dark .nb-channel-pills { border-color: rgba(255,255,255,.08); }
.nb-channel-label {
    font-size: 0.75rem; font-weight: 700; color: var(--nb-gray-600);
    letter-spacing: 1.5px; font-family: var(--nb-font-mono);
    display: flex; align-items: center; gap: 6px;
}
body.gl-dark .nb-channel-label { color: #94A3B8; }
.nb-channel-label i { color: var(--nb-primary); }

.nb-channel-pill {
    padding: 6px 14px; border-radius: 20px; font-size: 0.8rem;
    font-weight: 600; color: var(--nb-gray-600);
    background: var(--nb-gray-100); border: 1.5px solid transparent;
    text-decoration: none; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 6px;
}
body.gl-dark .nb-channel-pill { background: rgba(255,255,255,.05); color: #CBD5E1; }
.nb-channel-pill i { font-size: 0.7rem; }
.nb-channel-pill:hover {
    color: var(--nb-primary); background: rgba(16,185,129,0.1);
    transform: translateY(-2px);
}
.nb-channel-pill.active {
    background: var(--nb-primary); color: #fff;
    border-color: var(--nb-primary);
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}

/* ===== NEWS CONTAINER ===== */
.nb-news-container { display: grid; gap: 28px; }
.nb-grid-view { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
.nb-list-view { grid-template-columns: 1fr; }

.nb-news-card {
    position: relative; background: #fff; border-radius: 20px;
    overflow: hidden; border: 1px solid var(--nb-gray-100);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex; flex-direction: column;
    transform-style: preserve-3d;
}
body.gl-dark .nb-news-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.nb-news-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(16,185,129,0.15);
    border-color: var(--nb-primary);
}

.nb-list-view .nb-news-card { flex-direction: row; }
.nb-list-view .nb-card-image { width: 300px; flex-shrink: 0; height: auto; min-height: 240px; }
.nb-list-view .nb-card-content { padding: 28px; flex: 1; }

/* TV Screen Overlay */
.nb-tv-overlay {
    position: absolute; inset: 0; pointer-events: none; z-index: 2;
}
.nb-tv-scanlines {
    position: absolute; inset: 0;
    background: repeating-linear-gradient(
        0deg,
        rgba(0,0,0,0.03),
        rgba(0,0,0,0.03) 1px,
        transparent 1px,
        transparent 3px
    );
    opacity: 0.5;
}
.nb-tv-corner {
    position: absolute; width: 20px; height: 20px;
    border: 2px solid var(--nb-primary); opacity: 0;
    transition: opacity 0.3s;
}
.nb-tc-tl { top: 12px; left: 12px; border-right: none; border-bottom: none; }
.nb-tc-tr { top: 12px; right: 12px; border-left: none; border-bottom: none; }
.nb-tc-bl { bottom: 12px; left: 12px; border-right: none; border-top: none; }
.nb-tc-br { bottom: 12px; right: 12px; border-left: none; border-top: none; }
.nb-news-card:hover .nb-tv-corner { opacity: 1; }

.nb-card-link { position: absolute; inset: 0; z-index: 1; }

.nb-card-image {
    position: relative; height: 220px; overflow: hidden;
    background: var(--nb-gray-100);
}
.nb-card-image img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
.nb-news-card:hover .nb-card-image img { transform: scale(1.08); }

.nb-img-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(180deg, transparent 50%, rgba(0,0,0,0.4) 100%);
    pointer-events: none;
}

.nb-card-category {
    position: absolute; top: 16px; left: 16px; z-index: 2;
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; background: rgba(16,185,129,0.95);
    backdrop-filter: blur(10px); color: #fff;
    border-radius: 6px; font-size: 0.72rem; font-weight: 700;
    letter-spacing: 0.5px; text-transform: uppercase;
    box-shadow: 0 4px 12px rgba(16,185,129,0.4);
}

.nb-play-overlay {
    position: absolute; inset: 0; z-index: 2;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,0.4); opacity: 0; transition: opacity 0.3s;
}
.nb-news-card:hover .nb-play-overlay { opacity: 1; }
.nb-play-overlay i {
    width: 64px; height: 64px; border-radius: 50%;
    background: rgba(255,255,255,0.95); color: var(--nb-primary);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; padding-left: 4px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    transition: transform 0.3s;
}
.nb-play-overlay:hover i { transform: scale(1.1); }

.nb-card-content {
    padding: 24px; flex: 1; display: flex; flex-direction: column;
    position: relative; z-index: 2;
}

.nb-card-meta {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    font-size: 0.78rem; color: var(--nb-gray-600); margin-bottom: 12px;
    font-family: var(--nb-font-mono);
}
body.gl-dark .nb-card-meta { color: #94A3B8; }
.nb-meta-dot { opacity: 0.3; }
.nb-card-meta i { color: var(--nb-primary); margin-right: 3px; }

.nb-signal-inline {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 4px;
    font-weight: 700; font-size: 0.72rem;
}
.nb-signal-1 { background: rgba(107,114,128,0.1); color: #6B7280; }
.nb-signal-2 { background: rgba(59,130,246,0.1); color: #2563EB; }
.nb-signal-3 { background: rgba(16,185,129,0.1); color: #059669; }
.nb-signal-4 { background: rgba(245,158,11,0.1); color: #D97706; }

.nb-card-title {
    font-size: 1.15rem; font-weight: 800; line-height: 1.3;
    color: var(--nb-dark); margin: 0 0 12px 0;
    font-family: var(--nb-font-display);
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
    transition: color 0.3s;
}
body.gl-dark .nb-card-title { color: #F1F5F9; }
.nb-news-card:hover .nb-card-title { color: var(--nb-primary); }

.nb-card-excerpt {
    font-size: 0.88rem; color: var(--nb-gray-600); line-height: 1.6;
    margin: 0 0 18px 0; flex: 1;
}
body.gl-dark .nb-card-excerpt { color: #94A3B8; }

.nb-card-footer {
    display: flex; justify-content: space-between; align-items: center;
    padding-top: 16px; border-top: 1px dashed var(--nb-gray-200);
}
body.gl-dark .nb-card-footer { border-color: rgba(255,255,255,.1); }

.nb-read-more {
    display: inline-flex; align-items: center; gap: 8px;
    color: var(--nb-primary); font-weight: 700; font-size: 0.88rem;
    letter-spacing: 0.3px; transition: gap 0.3s;
}
.nb-news-card:hover .nb-read-more { gap: 12px; }

.nb-card-share {
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--nb-gray-100); border: none;
    color: var(--nb-gray-600); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s; font-size: 0.85rem;
    position: relative; z-index: 3;
}
body.gl-dark .nb-card-share { background: rgba(255,255,255,.08); color: #CBD5E1; }
.nb-card-share:hover {
    background: var(--nb-primary); color: #fff;
    transform: rotate(90deg);
}

/* ===== PAGINATION ===== */
.nb-pagination {
    display: flex; justify-content: space-between; align-items: center;
    gap: 20px; margin-top: 48px; flex-wrap: wrap;
    padding: 24px; background: #fff; border-radius: 20px;
    border: 1px solid var(--nb-gray-100);
    box-shadow: var(--nb-shadow-sm);
}
body.gl-dark .nb-pagination { background: #1E293B; border-color: rgba(255,255,255,.08); }

.nb-page-info {
    display: flex; align-items: center; gap: 8px;
    font-family: var(--nb-font-mono); font-size: 0.9rem;
}
.nb-page-label {
    padding: 4px 10px; background: var(--nb-primary); color: #fff;
    border-radius: 4px; font-weight: 800; font-size: 0.7rem;
    letter-spacing: 1px;
}
.nb-page-current {
    font-size: 1.5rem; font-weight: 800; color: var(--nb-dark);
    font-family: var(--nb-font-display);
}
body.gl-dark .nb-page-current { color: #F1F5F9; }
.nb-page-sep { color: var(--nb-gray-600); opacity: 0.5; }
.nb-page-total { color: var(--nb-gray-600); font-weight: 600; }

.nb-page-controls { display: flex; gap: 6px; flex-wrap: wrap; }
.nb-page-btn {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    min-width: 42px; height: 42px; padding: 0 14px;
    border-radius: 10px; background: var(--nb-gray-50);
    border: 1px solid var(--nb-gray-200); color: var(--nb-gray-600);
    text-decoration: none; font-weight: 700; font-size: 0.85rem;
    font-family: var(--nb-font-mono); transition: all 0.3s;
}
body.gl-dark .nb-page-btn { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.nb-page-btn:hover:not(.nb-page-disabled) {
    border-color: var(--nb-primary); color: var(--nb-primary);
    transform: translateY(-2px);
}
.nb-page-btn.active {
    background: linear-gradient(135deg, var(--nb-primary), var(--nb-primary-dark));
    color: #fff; border-color: transparent;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}
.nb-page-disabled { opacity: 0.4; cursor: not-allowed; }
.nb-page-ellipsis {
    color: var(--nb-gray-600); padding: 0 8px; font-weight: 700;
    letter-spacing: 2px;
}

/* ===== EMPTY STATE ===== */
.nb-empty-state {
    position: relative; text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px; overflow: hidden;
    border: 2px dashed var(--nb-gray-200); grid-column: 1 / -1;
}
body.gl-dark .nb-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }

.nb-empty-static {
    position: absolute; inset: 0; opacity: 0.05;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}

.nb-empty-content { position: relative; z-index: 1; }
.nb-empty-icon {
    position: relative; width: 100px; height: 100px;
    margin: 0 auto 24px; display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: var(--nb-gray-600);
    animation: nbEmptySpin 4s ease-in-out infinite;
}
@keyframes nbEmptySpin {
    0%, 100% { transform: rotate(0deg); }
    50% { transform: rotate(180deg); }
}
.nb-empty-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--nb-primary); animation: nbEmptyPulse 2s infinite;
}
@keyframes nbEmptyPulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }

.nb-empty-code {
    font-family: var(--nb-font-mono); font-size: 0.85rem;
    color: var(--nb-accent); font-weight: 800; letter-spacing: 2px;
    margin-bottom: 8px;
}
.nb-empty-state h3 {
    font-size: 1.5rem; margin: 0 0 8px 0; color: var(--nb-dark);
    font-weight: 800; font-family: var(--nb-font-display);
}
body.gl-dark .nb-empty-state h3 { color: #F1F5F9; }
.nb-empty-state p { color: var(--nb-gray-600); margin-bottom: 24px; }

/* ===== SIDEBAR ===== */
.nb-sidebar {
    position: sticky; top: 100px;
    display: flex; flex-direction: column; gap: 20px;
}

.nb-widget {
    background: #fff; border-radius: 20px;
    border: 1px solid var(--nb-gray-100); overflow: hidden;
    box-shadow: var(--nb-shadow-sm); position: relative;
    transform-style: preserve-3d;
}
body.gl-dark .nb-widget { background: #1E293B; border-color: rgba(255,255,255,.08); }

.nb-widget-header {
    padding: 18px 24px; background: var(--nb-gray-50);
    border-bottom: 1px solid var(--nb-gray-100);
    display: flex; align-items: center; gap: 10px;
}
body.gl-dark .nb-widget-header { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.08); }
.nb-widget-header h3 {
    font-size: 0.85rem; font-weight: 800; color: var(--nb-dark);
    margin: 0; letter-spacing: 2px; font-family: var(--nb-font-mono);
}
body.gl-dark .nb-widget-header h3 { color: #F1F5F9; }

.nb-widget-body { padding: 20px 24px; }

/* Live Stats Widget */
.nb-widget-live {
    background: linear-gradient(135deg, var(--nb-dark), #1E1B4B);
    color: #fff; border: none; overflow: hidden;
}
.nb-widget-live-bg {
    position: absolute; inset: 0; pointer-events: none;
}
.nb-live-pulse-ring {
    position: absolute; top: 50%; left: 50%;
    width: 200px; height: 200px; border-radius: 50%;
    border: 2px solid var(--nb-primary);
    transform: translate(-50%, -50%);
    animation: nbPulseRing 3s infinite;
}
@keyframes nbPulseRing {
    0% { transform: translate(-50%, -50%) scale(0.8); opacity: 1; }
    100% { transform: translate(-50%, -50%) scale(1.5); opacity: 0; }
}
.nb-widget-live .nb-widget-header {
    background: transparent; border-color: rgba(255,255,255,.1);
    position: relative; z-index: 1;
}
.nb-widget-live .nb-widget-header h3 { color: #fff; }

.nb-live-stat-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.08);
    font-size: 0.85rem; position: relative; z-index: 1;
}
.nb-live-stat-row:last-of-type { border-bottom: none; }
.nb-live-stat-label { color: rgba(255,255,255,0.7); }
.nb-live-stat-value {
    font-weight: 800; color: #fff; font-family: var(--nb-font-display);
}
.nb-signal-good { color: var(--nb-primary); display: flex; align-items: center; gap: 6px; }

.nb-live-meter {
    margin-top: 16px; padding-top: 16px;
    border-top: 1px solid rgba(255,255,255,.08);
    position: relative; z-index: 1;
}
.nb-meter-bar {
    height: 8px; background: rgba(255,255,255,.1);
    border-radius: 4px; overflow: hidden; margin-bottom: 6px;
}
.nb-meter-fill {
    height: 100%; width: 85%;
    background: linear-gradient(90deg, var(--nb-primary), var(--nb-accent-3));
    border-radius: 4px; animation: nbMeterPulse 2s infinite ease-in-out;
}
@keyframes nbMeterPulse {
    0%, 100% { width: 85%; }
    50% { width: 95%; }
}
.nb-meter-label {
    font-size: 0.7rem; color: rgba(255,255,255,0.5);
    letter-spacing: 1px; text-transform: uppercase;
    font-family: var(--nb-font-mono);
}

/* Top Stories */
.nb-top-story-item {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 0; border-bottom: 1px solid var(--nb-gray-100);
    text-decoration: none; transition: all 0.3s;
}
body.gl-dark .nb-top-story-item { border-color: rgba(255,255,255,.08); }
.nb-top-story-item:last-child { border-bottom: none; }
.nb-top-story-item:hover { transform: translateX(4px); }

.nb-story-rank {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 1rem; flex-shrink: 0;
    font-family: var(--nb-font-display);
}
.nb-story-info { flex: 1; min-width: 0; }
.nb-story-info h4 {
    font-size: 0.88rem; font-weight: 700; color: var(--nb-dark);
    margin: 0 0 6px 0; line-height: 1.3;
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
}
body.gl-dark .nb-story-info h4 { color: #F1F5F9; }
.nb-story-meta {
    display: flex; gap: 10px; font-size: 0.72rem;
    color: var(--nb-gray-600);
}
body.gl-dark .nb-story-meta { color: #94A3B8; }
.nb-story-meta i { color: var(--nb-primary); margin-right: 3px; }
.nb-story-arrow {
    color: var(--nb-gray-600); font-size: 0.8rem;
    transition: all 0.3s;
}
.nb-top-story-item:hover .nb-story-arrow {
    color: var(--nb-primary); transform: translateX(4px);
}

/* Channels Widget */
.nb-channel-list { display: flex; flex-direction: column; gap: 12px; }
.nb-channel-item {
    display: block; text-decoration: none;
    padding: 12px 14px; border-radius: 10px;
    background: var(--nb-gray-50);
    transition: all 0.3s; border: 1px solid transparent;
}
body.gl-dark .nb-channel-item { background: rgba(255,255,255,.03); }
.nb-channel-item:hover {
    background: rgba(16,185,129,0.08);
    border-color: rgba(16,185,129,0.2);
    transform: translateX(4px);
}
.nb-channel-item.active {
    background: linear-gradient(135deg, var(--nb-primary), var(--nb-primary-dark));
    color: #fff;
}

.nb-channel-head {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 8px;
}
.nb-channel-name {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.88rem; font-weight: 700; color: var(--nb-dark);
}
body.gl-dark .nb-channel-name { color: #F1F5F9; }
.nb-channel-item.active .nb-channel-name { color: #fff; }
.nb-channel-name i { color: var(--nb-primary); font-size: 0.8rem; }
.nb-channel-item.active .nb-channel-name i { color: #fff; }

.nb-channel-count {
    font-size: 0.75rem; font-weight: 800;
    padding: 2px 8px; border-radius: 4px;
    background: rgba(16,185,129,0.1); color: var(--nb-primary);
    font-family: var(--nb-font-mono);
}
.nb-channel-item.active .nb-channel-count {
    background: rgba(255,255,255,0.2); color: #fff;
}

.nb-channel-bar {
    height: 4px; background: var(--nb-gray-200);
    border-radius: 2px; overflow: hidden;
}
body.gl-dark .nb-channel-bar { background: rgba(255,255,255,.1); }
.nb-channel-item.active .nb-channel-bar { background: rgba(255,255,255,.2); }

.nb-channel-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--nb-primary), var(--nb-accent-3));
    border-radius: 2px; transition: width 0.6s ease-out;
}
.nb-channel-item.active .nb-channel-fill { background: #fff; }

/* Subscribe Widget */
.nb-widget-subscribe {
    background: linear-gradient(135deg, var(--nb-primary), var(--nb-primary-dark));
    color: #fff; border: none; overflow: hidden;
}
.nb-subscribe-bg {
    position: absolute; inset: 0; pointer-events: none;
}
.nb-wave-1, .nb-wave-2 {
    position: absolute; bottom: -50%; left: -10%;
    width: 120%; height: 200%; border-radius: 45%;
    background: rgba(255,255,255,0.08);
    animation: nbWaveRotate 15s linear infinite;
}
.nb-wave-2 {
    animation-duration: 20s; animation-direction: reverse;
    background: rgba(255,255,255,0.05);
}
@keyframes nbWaveRotate { to { transform: rotate(360deg); } }

.nb-widget-subscribe .nb-widget-body {
    position: relative; z-index: 1; text-align: center;
}
.nb-subscribe-icon {
    width: 64px; height: 64px; margin: 0 auto 16px;
    background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; position: relative;
}
.nb-bell-ring {
    position: absolute; inset: -8px; border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.3);
    animation: nbBellRing 2s infinite;
}
@keyframes nbBellRing {
    0% { transform: scale(0.9); opacity: 1; }
    100% { transform: scale(1.3); opacity: 0; }
}

.nb-widget-subscribe h3 {
    font-size: 1.15rem; font-weight: 800; margin: 0 0 8px 0;
    color: #fff;
}
.nb-widget-subscribe p {
    font-size: 0.88rem; opacity: 0.9; margin-bottom: 20px;
    line-height: 1.6;
}
.nb-subscribe-form input {
    width: 100%; padding: 12px 16px; border: none;
    border-radius: 10px; background: rgba(255,255,255,0.95);
    font-family: inherit; font-size: 0.9rem; margin-bottom: 12px;
    color: var(--nb-dark);
}
.nb-subscribe-form input:focus { outline: 2px solid #fff; }
.nb-widget-subscribe .nb-btn-primary {
    background: #fff; color: var(--nb-primary-dark);
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
.nb-widget-subscribe .nb-btn-primary:hover {
    background: #F0FDF4; transform: translateY(-2px);
}

/* Time Widget */
.nb-widget-time .nb-widget-body { padding: 24px; }
.nb-time-display {
    text-align: center; padding: 20px;
    background: var(--nb-gray-50); border-radius: 14px;
    margin-bottom: 20px; border: 1px solid var(--nb-gray-100);
}
body.gl-dark .nb-time-display { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.08); }
.nb-time-big {
    display: block; font-size: 2rem; font-weight: 900;
    color: var(--nb-dark); font-family: var(--nb-font-display);
    letter-spacing: 2px; margin-bottom: 4px;
    background: linear-gradient(135deg, var(--nb-primary), var(--nb-accent-3));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
body.gl-dark .nb-time-big { color: #F1F5F9; }
.nb-time-date {
    display: block; font-size: 0.8rem; color: var(--nb-gray-600);
    margin-bottom: 6px;
}
body.gl-dark .nb-time-date { color: #94A3B8; }
.nb-time-tz {
    display: inline-block; padding: 3px 10px;
    background: var(--nb-primary); color: #fff;
    border-radius: 4px; font-size: 0.7rem; font-weight: 700;
    letter-spacing: 1px; font-family: var(--nb-font-mono);
}

.nb-time-slots { display: flex; flex-direction: column; gap: 8px; }
.nb-time-slot {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; background: var(--nb-gray-50);
    border-radius: 8px; font-size: 0.82rem;
    transition: all 0.3s; border-left: 3px solid transparent;
}
body.gl-dark .nb-time-slot { background: rgba(255,255,255,.03); }
.nb-time-slot.active {
    background: rgba(16,185,129,0.08);
    border-left-color: var(--nb-primary);
}
.nb-slot-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--nb-gray-600); flex-shrink: 0;
}
.nb-time-slot.active .nb-slot-dot {
    background: var(--nb-primary);
    animation: nbLivePulse 1.5s infinite;
    box-shadow: 0 0 8px var(--nb-primary);
}
.nb-time-slot span:nth-child(2) { flex: 1; font-weight: 600; color: var(--nb-dark); }
body.gl-dark .nb-time-slot span:nth-child(2) { color: #F1F5F9; }
.nb-slot-time {
    font-family: var(--nb-font-mono); font-size: 0.72rem;
    color: var(--nb-gray-600); font-weight: 700;
}
body.gl-dark .nb-slot-time { color: #94A3B8; }

/* ===== TOAST ===== */
.nb-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--nb-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    display: flex; align-items: center; gap: 10px;
    font-size: 0.9rem; opacity: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10000; font-weight: 500;
}
body.gl-dark .nb-toast { background: #fff; color: var(--nb-dark); }
.nb-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.nb-toast i { color: var(--nb-primary); font-size: 1.2rem; }

/* ===== ANIMATIONS ===== */
.fade-up {
    opacity: 0; transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .nb-layout { grid-template-columns: 1fr; }
    .nb-sidebar {
        position: static; order: 2;
        display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
    }
    .nb-main-col { order: 1; }
    .nb-breaking-card { grid-template-columns: 1fr; }
    .nb-breaking-image { min-height: 300px; }
    .nb-broadcast-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .nb-hero { padding: 100px 20px 80px; min-height: 65vh; }
    .nb-live-indicator {
        position: relative; top: 0; transform: none;
        margin: 0 auto 20px; font-size: 0.7rem;
    }
    .nb-hero-title { font-size: 3rem; }
    .nb-broadcast-stats { grid-template-columns: 1fr 1fr; gap: 10px; }
    .nb-bstat { padding: 10px 14px; }
    .nb-bstat-icon { width: 32px; height: 32px; font-size: 0.9rem; }
    .nb-bstat-num { font-size: 1.1rem; }
    .nb-ticker-wrap { height: 40px; }
    .nb-ticker-label { padding: 0 16px; font-size: 0.7rem; }
    .nb-breaking-content { padding: 30px 24px; }
    .nb-breaking-title { font-size: 1.5rem; }
    .nb-control-form { flex-direction: column; align-items: stretch; }
    .nb-controls-right { justify-content: space-between; }
    .nb-list-view .nb-news-card { flex-direction: column; }
    .nb-list-view .nb-card-image { width: 100%; min-height: 200px; }
    .nb-sidebar { grid-template-columns: 1fr; }
    .nb-channel-pills { justify-content: flex-start; overflow-x: auto; flex-wrap: nowrap; padding-bottom: 6px; }
    .nb-channel-pill { white-space: nowrap; flex-shrink: 0; }
    .nb-pagination { flex-direction: column; gap: 16px; padding: 20px; }
    .nb-page-controls { justify-content: center; width: 100%; overflow-x: auto; }
    .nb-breaking-corner { font-size: 0.6rem; }
    .nb-corner-tl, .nb-corner-tr, .nb-corner-bl, .nb-corner-br { padding: 0; }
    .nb-rec-badge { font-size: 0.65rem; padding: 3px 8px; }
    .nb-frame-code { font-size: 0.65rem; padding: 3px 8px; }
}
@media (max-width: 480px) {
    .nb-hero-title { font-size: 2.5rem; }
    .nb-broadcast-stats { grid-template-columns: 1fr; }
    .nb-bstat { justify-content: center; }
    .nb-breaking-actions { flex-direction: column; }
    .nb-breaking-actions .nb-btn { width: 100%; }
    .nb-news-container.nb-grid-view { grid-template-columns: 1fr; }
}

/* ============================================================
   PERFORMANCE PATCH - NEWSROOM BROADCAST SMOOTH SCROLL
============================================================ */

/* Anchor offset untuk navbar sticky */
main section, .nb-breaking-section, .nb-standard-hero {
    scroll-margin-top: 90px;
}

/* Render hemat untuk section yang jauh dari viewport */
.nb-breaking-section,
.nb-standard-hero,
.nb-section {
    content-visibility: auto;
    contain-intrinsic-size: auto 1500px;
}

/* CRT Canvas Optimization: Pixelated agar bisa dirender di resolusi rendah */
.nb-static-canvas {
    image-rendering: pixelated;
    image-rendering: crisp-edges;
    backface-visibility: hidden;
}

/* Pause animasi berat saat user sedang scroll */
body.is-scrolling .nb-studio-light,
body.is-scrolling .nb-ticker-track,
body.is-scrolling .nb-scanlines,
body.is-scrolling .nb-wave-1,
body.is-scrolling .nb-wave-2,
body.is-scrolling .nb-live-pulse-ring,
body.is-scrolling .nb-meter-fill {
    animation-play-state: paused !important;
}

/* Pause animasi CSS untuk section yang tidak terlihat */
.nb-offscreen * {
    animation-play-state: paused !important;
}

/* Optimasi elemen yang sering bergerak */
.nb-ticker-track,
.fade-up {
    will-change: transform;
    backface-visibility: hidden;
}
.fade-up.visible { will-change: auto; }

/* Elemen dekoratif tidak boleh menangkap klik */
.nb-studio-bg, .nb-tv-overlay, .nb-img-overlay {
    pointer-events: none;
}

/* Mobile / Touch: Matikan efek paling mahal */
@media (max-width: 768px), (hover: none), (pointer: coarse) {
    .nb-static-canvas { display: none !important; } /* Matikan total canvas noise di HP */
    .nb-studio-light { filter: blur(60px); opacity: 0.2 !important; animation: none !important; }
    .nb-breaking-card, .nb-widget, .nb-news-card, .nb-control-bar {
        backdrop-filter: none !important;
    }
    .nb-tilt { transform: none !important; } /* Matikan 3D tilt di HP */
}

/* Accessibility: Reduce motion */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
    .nb-static-canvas, .nb-ticker-track { display: none !important; }
}
</style>

<!-- ============================================================
     SPECTACULAR SCRIPTS (Null-Safe, Anti-Error)
============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ===== 0. PERFORMANCE: Scroll & Viewport Control =====
    let scrollTimer;
    const markScrolling = () => {
        document.body.classList.add('is-scrolling');
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(() => document.body.classList.remove('is-scrolling'), 180);
    };
    window.addEventListener('scroll', markScrolling, { passive: true });
    markScrolling();

    if ('IntersectionObserver' in window) {
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                entry.target.classList.toggle('nb-offscreen', !entry.isIntersecting);
            });
        }, { rootMargin: '200px 0px', threshold: 0 });
        document.querySelectorAll('.nb-hero, .nb-breaking-section, .nb-standard-hero, .nb-section').forEach(s => sectionObserver.observe(s));

        // Observer khusus untuk menghentikan Canvas saat tidak terlihat
        const heroSection = document.querySelector('.nb-hero');
        if (heroSection) {
            window.__nbHeroVisible = true;
            new IntersectionObserver((entries) => {
                window.__nbHeroVisible = entries[0].isIntersecting;
                window.dispatchEvent(new CustomEvent('nb:hero-visibility', { detail: window.__nbHeroVisible }));
            }, { threshold: 0.01 }).observe(heroSection);
        }
    }

    // ===== 1. Static Canvas (TV Noise) - EXTREMELY OPTIMIZED =====
    const canvas = document.getElementById('nbStaticCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d', { alpha: false });
        let width, height, running = true, lastTime = 0;
        const fps = 15; // 15 FPS sudah sangat cukup untuk efek TV Static
        const interval = 1000 / fps;

        function resize() {
            // RAHASIA PERFORMA: Render di resolusi 25% lalu biarkan CSS yang membesarkan (pixelated)
            // Ini memangkas beban CPU hingga 95%!
            const scale = window.innerWidth < 768 ? 0.15 : 0.25; 
            width = canvas.width = canvas.parentElement.offsetWidth * scale;
            height = canvas.height = canvas.parentElement.offsetHeight * scale;
        }
        
        resize();
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(resize, 200);
        }, { passive: true });

        window.addEventListener('nb:hero-visibility', (e) => { running = Boolean(e.detail); });
        document.addEventListener('visibilitychange', () => { if (document.hidden) running = false; else if (window.__nbHeroVisible) running = true; });

        function drawStatic(currentTime) {
            requestAnimationFrame(drawStatic);
            if (!running || document.hidden) return;
            
            const elapsed = currentTime - lastTime;
            if (elapsed < interval) return;
            lastTime = currentTime;

            const imageData = ctx.createImageData(width, height);
            const data = imageData.data;
            const len = data.length;
            
            // Loop optimized: menggunakan while loop mundur dan bitwise random alternative (jika perlu), tapi Math.random di sini sudah aman karena resolusi kecil
            for (let i = 0; i < len; i += 4) {
                const val = Math.random() * 255;
                data[i] = val;     // R
                data[i + 1] = val; // G
                data[i + 2] = val; // B
                data[i + 3] = 255; // A
            }
            ctx.putImageData(imageData, 0, 0);
        }
        requestAnimationFrame(drawStatic);
    }

    // ===== 2. Live Clock & Date (Cached DOM Elements) =====
    const liveTimeEl = document.getElementById('nbLiveTime');
    const sidebarTimeEl = document.getElementById('nbSidebarTime');
    const frameCodeEl = document.getElementById('nbFrameCode');
    const sidebarDateEl = document.getElementById('nbSidebarDate');
    const timeSlots = document.querySelectorAll('.nb-time-slot');
    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };

    function updateTime() {
        if (document.hidden) return; // Stop jika tab di-background
        
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        const ms = String(Math.floor(now.getMilliseconds() / 10)).padStart(2, '0');
        const timeStr = `${h}:${m}:${s}`;

        if (liveTimeEl) liveTimeEl.textContent = timeStr;
        if (sidebarTimeEl) sidebarTimeEl.textContent = timeStr;
        if (frameCodeEl) frameCodeEl.textContent = `${timeStr}:${ms}`;
        
        if (sidebarDateEl && s === '00') { // Update tanggal hanya setiap pergantian menit
            sidebarDateEl.textContent = now.toLocaleDateString('id-ID', dateOptions);
        }

        if (timeSlots.length === 3 && m === '00' && s === '00') { // Update slot setiap jam
            const hour = now.getHours();
            timeSlots.forEach(sl => sl.classList.remove('active'));
            if (hour >= 6 && hour < 12) timeSlots[0].classList.add('active');
            else if (hour >= 12 && hour < 18) timeSlots[1].classList.add('active');
            else timeSlots[2].classList.add('active');
        }
    }
    updateTime();
    setInterval(updateTime, 1000);

    // ===== 3. Typing Effect =====
    const textElement = document.getElementById('nbTypingText');
    if (textElement) {
        const phrases = [
            "Siaran langsung berita, workshop, dan prestasi civitas akademika Informatika.",
            "Update real-time dari studio digital Program Studi Informatika Pro.",
            "Jelajahi arsip lengkap kegiatan kami sepanjang tahun.",
            "Dari akademik hingga kemahasiswaan, semua tersiarkan di sini."
        ];
        let phraseIndex = 0, charIndex = 0, isDeleting = false;
        function type() {
            const currentPhrase = phrases[phraseIndex];
            if (isDeleting) { textElement.textContent = currentPhrase.substring(0, charIndex - 1); charIndex--; } 
            else { textElement.textContent = currentPhrase.substring(0, charIndex + 1); charIndex++; }
            let typeSpeed = isDeleting ? 30 : 50;
            if (!isDeleting && charIndex === currentPhrase.length) { typeSpeed = 2500; isDeleting = true; } 
            else if (isDeleting && charIndex === 0) { isDeleting = false; phraseIndex = (phraseIndex + 1) % phrases.length; typeSpeed = 500; }
            setTimeout(type, typeSpeed);
        }
        type();
    }

    // ===== 4. Counter Animation =====
    const counters = document.querySelectorAll('.nb-bstat-num[data-count]');
    if (counters.length) {
        const counterObs = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = parseInt(el.getAttribute('data-count')) || 0;
                    const duration = 2000;
                    const startTime = performance.now();
                    const easeOutQuart = t => 1 - Math.pow(1 - t, 4);
                    function update(now) {
                        const progress = Math.min((now - startTime) / duration, 1);
                        el.textContent = Math.floor(target * easeOutQuart(progress)).toLocaleString('id-ID');
                        if (progress < 1) requestAnimationFrame(update);
                        else el.textContent = target.toLocaleString('id-ID');
                    }
                    requestAnimationFrame(update);
                    counterObs.unobserve(el);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(c => counterObs.observe(c));
    }

    // ===== 5. Simulated Viewer Count =====
    const viewerEl = document.getElementById('nbViewerCount');
    if (viewerEl) {
        let baseViewers = 127;
        function updateViewers() {
            if (document.hidden) return;
            baseViewers = Math.max(80, Math.min(250, baseViewers + (Math.floor(Math.random() * 20) - 10)));
            viewerEl.textContent = baseViewers;
        }
        updateViewers();
        setInterval(updateViewers, 3000);
    }

    // ===== 6. 3D Tilt Effect (THROTTLED) =====
    const tiltElements = document.querySelectorAll('.nb-tilt');
    if (window.matchMedia('(hover: hover)').matches) {
        tiltElements.forEach(el => {
            let tiltTicking = false;
            let lastX = 0, lastY = 0;
            el.addEventListener('mousemove', (e) => {
                lastX = e.clientX; lastY = e.clientY;
                if (!tiltTicking) {
                    tiltTicking = true;
                    requestAnimationFrame(() => {
                        const rect = el.getBoundingClientRect();
                        const x = lastX - rect.left;
                        const y = lastY - rect.top;
                        const rotateX = ((y - rect.height / 2) / (rect.height / 2)) * -3;
                        const rotateY = ((x - rect.width / 2) / (rect.width / 2)) * 3;
                        el.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                        tiltTicking = false;
                    });
                }
            }, { passive: true });
            el.addEventListener('mouseleave', () => { el.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)'; }, { passive: true });
        });
    }

    // ===== 7. Scroll Reveal Animation =====
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const delay = entry.target.style.animationDelay || '0s';
                setTimeout(() => entry.target.classList.add('visible'), parseFloat(delay) * 1000);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('.fade-up').forEach(el => { if (el) observer.observe(el); });

    // ===== 8. Share Article =====
    window.nbShareArticle = function(title, slug) {
        const url = window.location.origin + '/berita-detail.php?slug=' + slug;
        if (navigator.share) {
            navigator.share({ title: title, text: 'Baca berita ini di Informatika Pro!', url: url }).catch(() => {});
        } else {
            navigator.clipboard.writeText(url).then(() => nbShowToast('🔗 Link berita disalin ke clipboard!'));
        }
    };

    // ===== 9. Toast Notification =====
    window.nbShowToast = function(message) {
        const toast = document.getElementById('nbToast');
        if (!toast) return;
        const msgEl = document.getElementById('nbToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // ===== 10. Parallax Effect on Hero (THROTTLED) =====
    const heroSection = document.querySelector('.nb-hero');
    let parallaxTicking = false;
    if (heroSection && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            if (!parallaxTicking) {
                requestAnimationFrame(() => {
                    const scrolled = window.pageYOffset;
                    const lights = heroSection.querySelectorAll('.nb-studio-light');
                    lights.forEach((light, index) => {
                        light.style.transform = `translateY(${scrolled * ((index + 1) * 0.2)}px)`;
                    });
                    parallaxTicking = false;
                });
                parallaxTicking = true;
            }
        }, { passive: true });
    }

    // Welcome toast
    setTimeout(() => nbShowToast('📡 Selamat datang di Informatika LIVE Newsroom!'), 1500);

    // Console Easter Egg
    console.log('%c╔══════════════════════════════════════════╗', 'color:#10B981;font-weight:bold');
    console.log('%c║   INFORMATIKA LIVE - Digital Newsroom    ║', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c╚══════════════════════════════════════════╝', 'color:#10B981;font-weight:bold');
    console.log('%c📡 ON AIR - Broadcasting ' + document.querySelectorAll('.nb-news-card').length + ' stories (OPTIMIZED)', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>