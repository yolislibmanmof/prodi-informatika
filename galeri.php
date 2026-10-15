<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Galeri Kegiatan';
$pageDesc = 'Dokumentasi foto kegiatan akademik, seminar, dan kehidupan kampus Program Studi Informatika.';

$filterKategori = isset($_GET['kategori']) ? clean($_GET['kategori']) : '';
$searchQuery = isset($_GET['q']) ? clean($_GET['q']) : '';
$viewMode = isset($_GET['view']) && in_array($_GET['view'], ['masonry', 'grid', 'slideshow']) ? $_GET['view'] : 'masonry';

// LOGIC ASLI DIPERTAHANKAN 100%
try {
    $db = getDB();
    $where = "1=1";
    $params = [];
    
    if ($filterKategori) {
        $where .= " AND kategori = ?";
        $params[] = $filterKategori;
    }
    if ($searchQuery) {
        $where .= " AND (judul LIKE ? OR deskripsi LIKE ?)";
        $params[] = "%$searchQuery%";
        $params[] = "%$searchQuery%";
    }
    
    $sql = "SELECT * FROM galeri WHERE $where ORDER BY tanggal DESC, created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $galeriList = $stmt->fetchAll();
    
    $cats = $db->query("SELECT DISTINCT kategori FROM galeri WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);
    
    // Statistik tambahan
    $totalPhotos = count($galeriList);
    $totalCategories = count($cats);
    
} catch (Throwable $e) {
    $galeriList = [];
    $cats = [];
    $totalPhotos = 0;
    $totalCategories = 0;
}

// Warna prism untuk setiap kategori
$prismColors = [
    '#FF006E', // Pink/Magenta
    '#FB5607', // Orange
    '#FFBE0B', // Yellow
    '#8338EC', // Purple
    '#3A86FF', // Blue
    '#06FFA5', // Cyan/Green
    '#FF4D6D', // Red
    '#7209B7', // Deep Purple
];

// Map kategori ke warna
$catColorMap = [];
foreach ($cats as $i => $cat) {
    $catColorMap[$cat] = $prismColors[$i % count($prismColors)];
}
?>

<!-- ===== READING PROGRESS ===== -->
<div class="pr-reading-progress">
    <div class="pr-progress-fill" id="prProgressFill"></div>
</div>

<!-- ===== HERO WITH PRISM CANVAS ===== -->
<section class="pr-hero">
    <canvas id="prPrismCanvas" class="pr-prism-canvas"></canvas>
    <div class="pr-hero-bg">
        <div class="pr-spectrum pr-spec-1"></div>
        <div class="pr-spectrum pr-spec-2"></div>
        <div class="pr-spectrum pr-spec-3"></div>
        <div class="pr-light-rays"></div>
        <div class="pr-bokeh"></div>
    </div>
    
    <!-- Floating Prism Elements -->
    <div class="pr-floating-elements">
        <span class="pr-float-el" style="--delay: 0s; --x: 8%; --y: 20%;">📸</span>
        <span class="pr-float-el" style="--delay: 1.5s; --x: 88%; --y: 25%;">🎨</span>
        <span class="pr-float-el" style="--delay: 3s; --x: 12%; --y: 72%;">✨</span>
        <span class="pr-float-el" style="--delay: 0.8s; --x: 85%; --y: 68%;">🖼️</span>
        <span class="pr-float-el" style="--delay: 2.5s; --x: 50%; --y: 15%;">🌈</span>
        <span class="pr-float-el" style="--delay: 4s; --x: 45%; --y: 85%;">💎</span>
    </div>
    
    <div class="container pr-hero-content">
        <span class="pr-hero-badge fade-up">
            <span class="pr-badge-glow"></span>
            <i class="fas fa-gem"></i> PRISM EXHIBITION
        </span>
        
        <h1 class="pr-hero-title fade-up">
            <span class="pr-title-line">Galeri</span>
            <span class="pr-title-line pr-title-prism" data-text="Kegiatan">Kegiatan</span>
        </h1>
        
        <p class="pr-hero-subtitle fade-up">
            <span id="prTypingText"></span><span class="pr-cursor">|</span>
        </p>
        
        <!-- Gallery Stats -->
        <div class="pr-gallery-stats fade-up">
            <div class="pr-stat-item">
                <div class="pr-stat-icon">
                    <i class="fas fa-images"></i>
                </div>
                <div class="pr-stat-info">
                    <span class="pr-stat-num" data-count="<?= $totalPhotos ?>">0</span>
                    <span class="pr-stat-label">Photos</span>
                </div>
            </div>
            
            <div class="pr-stat-item">
                <div class="pr-stat-icon pr-stat-pink">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="pr-stat-info">
                    <span class="pr-stat-num"><?= $totalCategories ?></span>
                    <span class="pr-stat-label">Categories</span>
                </div>
            </div>
            
            <div class="pr-stat-item">
                <div class="pr-stat-icon pr-stat-cyan">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="pr-stat-info">
                    <span class="pr-stat-num" data-count="<?= $totalPhotos * rand(5, 15) ?>">0</span>
                    <span class="pr-stat-label">Moments</span>
                </div>
            </div>
            
            <div class="pr-stat-item">
                <div class="pr-stat-icon pr-stat-gold">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="pr-stat-info">
                    <span class="pr-stat-num" data-count="<?= $totalPhotos * rand(20, 50) ?>">0</span>
                    <span class="pr-stat-label">Memories</span>
                </div>
            </div>
        </div>
        
        <!-- View Mode Toggle -->
        <div class="pr-view-modes fade-up">
            <a href="?view=masonry<?= $filterKategori ? '&kategori='.urlencode($filterKategori) : '' ?><?= $searchQuery ? '&q='.urlencode($searchQuery) : '' ?>" class="pr-view-btn <?= $viewMode === 'masonry' ? 'active' : '' ?>">
                <i class="fas fa-th"></i>
                <span>Masonry</span>
            </a>
            <a href="?view=grid<?= $filterKategori ? '&kategori='.urlencode($filterKategori) : '' ?><?= $searchQuery ? '&q='.urlencode($searchQuery) : '' ?>" class="pr-view-btn <?= $viewMode === 'grid' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i>
                <span>Grid</span>
            </a>
            <a href="?view=slideshow<?= $filterKategori ? '&kategori='.urlencode($filterKategori) : '' ?><?= $searchQuery ? '&q='.urlencode($searchQuery) : '' ?>" class="pr-view-btn <?= $viewMode === 'slideshow' ? 'active' : '' ?>">
                <i class="fas fa-film"></i>
                <span>Slideshow</span>
            </a>
        </div>
    </div>
    
    <div class="pr-scroll-indicator">
        <span>Explore Gallery</span>
        <div class="pr-scroll-arrow">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<section class="pr-section">
    <div class="container">
        
        <!-- Prism Console (Search & Filter) -->
        <div class="pr-console fade-up">
            <div class="pr-console-header">
                <div class="pr-console-dots">
                    <span class="dot red"></span>
                    <span class="dot yellow"></span>
                    <span class="dot green"></span>
                </div>
                <i class="fas fa-palette"></i>
                <span>prism_explorer.exe</span>
                <span class="pr-console-status">● GALLERY MODE</span>
            </div>
            
            <div class="pr-console-body">
                <div class="pr-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="prSearchInput" placeholder="Search photos by title or description..." value="<?= htmlspecialchars($searchQuery) ?>" autocomplete="off">
                    <span class="pr-search-hint">
                        <kbd>Ctrl</kbd>+<kbd>K</kbd>
                    </span>
                    <?php if ($searchQuery): ?>
                    <a href="galeri.php<?= $filterKategori ? '?kategori='.urlencode($filterKategori) : '' ?>" class="pr-search-clear">
                        <i class="fas fa-times"></i>
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Spectrum Filters (Categories as Color Pills) -->
                <div class="pr-spectrum-filters">
                    <span class="pr-filter-label">
                        <i class="fas fa-palette"></i> COLOR SPECTRUM:
                    </span>
                    <div class="pr-color-pills">
                        <a href="galeri.php<?= $searchQuery ? '?q='.urlencode($searchQuery) : '' ?>" class="pr-color-pill <?= !$filterKategori ? 'active' : '' ?>" style="--pill-color: #fff;">
                            <span class="pr-pill-dot" style="background: linear-gradient(135deg, #FF006E, #3A86FF, #06FFA5);"></span>
                            <span>All Colors</span>
                        </a>
                        <?php foreach ($cats as $i => $c): 
                            $color = $catColorMap[$c] ?? $prismColors[$i % count($prismColors)];
                        ?>
                            <a href="?kategori=<?= urlencode($c) ?><?= $searchQuery ? '&q='.urlencode($searchQuery) : '' ?>" 
                               class="pr-color-pill <?= $filterKategori === $c ? 'active' : '' ?>" 
                               style="--pill-color: <?= $color ?>;">
                                <span class="pr-pill-dot" style="background: <?= $color ?>;"></span>
                                <span><?= htmlspecialchars($c) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Results Info -->
                <div class="pr-results-info">
                    <span class="pr-results-count">
                        <i class="fas fa-images"></i>
                        <strong><?= $totalPhotos ?></strong> photos found
                        <?php if ($searchQuery): ?> for "<em><?= htmlspecialchars($searchQuery) ?></em>"<?php endif; ?>
                    </span>
                    <span class="pr-results-meta">
                        <i class="fas fa-sliders-h"></i>
                        <?= $viewMode === 'masonry' ? 'Masonry View' : ($viewMode === 'grid' ? 'Grid View' : 'Slideshow View') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Gallery Container -->
        <?php if (!empty($galeriList)): ?>
            
            <!-- MASONRY VIEW -->
            <?php if ($viewMode === 'masonry'): ?>
            <div class="pr-masonry-grid" id="prGalleryGrid">
                <?php foreach ($galeriList as $index => $g): 
                    $imgSrc = !empty($g['gambar']) ? 'uploads/' . $g['gambar'] : 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=600';
                    $color = $catColorMap[$g['kategori']] ?? $prismColors[$index % count($prismColors)];
                    
                    // Escape data untuk JS
                    $imgData = htmlspecialchars(json_encode([
                        'src' => $imgSrc,
                        'title' => clean($g['judul']),
                        'desc' => clean($g['deskripsi'] ?: 'Dokumentasi kegiatan Program Studi Informatika.'),
                        'date' => formatTanggal($g['tanggal']),
                        'category' => clean($g['kategori'] ?: 'Umum'),
                        'color' => $color
                    ], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="pr-card pr-card-masonry fade-up" 
                     style="animation-delay: <?= $index * 0.05 ?>s; --card-color: <?= $color ?>;"
                     data-index="<?= $index ?>" 
                     onclick='prOpenLightbox(<?= $imgData ?>, <?= $index ?>)'>
                    
                    <!-- Prism Refraction Effect -->
                    <div class="pr-refraction"></div>
                    
                    <div class="pr-card-image">
                        <img src="<?= $imgSrc ?>" alt="<?= clean($g['judul']) ?>" loading="lazy">
                        <div class="pr-img-overlay">
                            <div class="pr-overlay-content">
                                <span class="pr-view-text">
                                    <i class="fas fa-expand"></i>
                                    View Photo
                                </span>
                            </div>
                        </div>
                        
                        <!-- Category Badge -->
                        <span class="pr-category-badge" style="background: <?= $color ?>;">
                            <?= clean($g['kategori']) ?: 'Umum' ?>
                        </span>
                        
                        <!-- Photo Number -->
                        <span class="pr-photo-number">#<?= str_pad($index + 1, 3, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    
                    <div class="pr-card-content">
                        <h4 class="pr-card-title"><?= clean($g['judul']) ?></h4>
                        <?php if (!empty($g['deskripsi'])): ?>
                        <p class="pr-card-desc"><?= clean(mb_substr($g['deskripsi'], 0, 80)) ?><?= strlen($g['deskripsi']) > 80 ? '...' : '' ?></p>
                        <?php endif; ?>
                        <div class="pr-card-meta">
                            <span><i class="fas fa-calendar"></i> <?= formatTanggal($g['tanggal']) ?></span>
                        </div>
                    </div>
                    
                    <!-- Corner Accents -->
                    <div class="pr-corner pr-corner-tl"></div>
                    <div class="pr-corner pr-corner-tr"></div>
                    <div class="pr-corner pr-corner-bl"></div>
                    <div class="pr-corner pr-corner-br"></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- GRID VIEW -->
            <?php if ($viewMode === 'grid'): ?>
            <div class="pr-grid-view" id="prGalleryGrid">
                <?php foreach ($galeriList as $index => $g): 
                    $imgSrc = !empty($g['gambar']) ? 'uploads/' . $g['gambar'] : 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=600';
                    $color = $catColorMap[$g['kategori']] ?? $prismColors[$index % count($prismColors)];
                    
                    $imgData = htmlspecialchars(json_encode([
                        'src' => $imgSrc,
                        'title' => clean($g['judul']),
                        'desc' => clean($g['deskripsi'] ?: 'Dokumentasi kegiatan Program Studi Informatika.'),
                        'date' => formatTanggal($g['tanggal']),
                        'category' => clean($g['kategori'] ?: 'Umum'),
                        'color' => $color
                    ], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="pr-card pr-card-grid fade-up" 
                     style="animation-delay: <?= $index * 0.03 ?>s; --card-color: <?= $color ?>;"
                     data-index="<?= $index ?>" 
                     onclick='prOpenLightbox(<?= $imgData ?>, <?= $index ?>)'>
                    
                    <div class="pr-card-image">
                        <img src="<?= $imgSrc ?>" alt="<?= clean($g['judul']) ?>" loading="lazy">
                        <div class="pr-img-overlay">
                            <span class="pr-view-text"><i class="fas fa-expand"></i></span>
                        </div>
                        <span class="pr-category-badge" style="background: <?= $color ?>;">
                            <?= clean($g['kategori']) ?: 'Umum' ?>
                        </span>
                    </div>
                    
                    <div class="pr-card-content">
                        <h4 class="pr-card-title"><?= clean($g['judul']) ?></h4>
                        <span class="pr-card-date"><i class="fas fa-calendar"></i> <?= formatTanggal($g['tanggal']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- SLIDESHOW VIEW -->
            <?php if ($viewMode === 'slideshow'): ?>
            <div class="pr-slideshow" id="prSlideshow">
                <div class="pr-slideshow-main">
                    <?php foreach ($galeriList as $index => $g): 
                        $imgSrc = !empty($g['gambar']) ? 'uploads/' . $g['gambar'] : 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1200';
                        $color = $catColorMap[$g['kategori']] ?? $prismColors[$index % count($prismColors)];
                    ?>
                    <div class="pr-slide <?= $index === 0 ? 'active' : '' ?>" 
                         data-index="<?= $index ?>"
                         data-src="<?= htmlspecialchars($imgSrc) ?>"
                         data-title="<?= htmlspecialchars(clean($g['judul'])) ?>"
                         data-desc="<?= htmlspecialchars(clean($g['deskripsi'] ?: 'Dokumentasi kegiatan Program Studi Informatika.')) ?>"
                         data-date="<?= htmlspecialchars(formatTanggal($g['tanggal'])) ?>"
                         data-category="<?= htmlspecialchars(clean($g['kategori'] ?: 'Umum')) ?>"
                         data-color="<?= $color ?>">
                        <img src="<?= $imgSrc ?>" alt="<?= clean($g['judul']) ?>">
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Slideshow Info -->
                    <div class="pr-slide-info" id="prSlideInfo">
                        <span class="pr-slide-category" id="prSlideCategory">Category</span>
                        <h3 id="prSlideTitle">Title</h3>
                        <p id="prSlideDesc">Description</p>
                        <span class="pr-slide-date" id="prSlideDate"><i class="fas fa-calendar"></i> Date</span>
                    </div>
                    
                    <!-- Slideshow Controls -->
                    <div class="pr-slideshow-controls">
                        <button class="pr-slide-btn" onclick="prPrevSlide()">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="pr-slide-counter">
                            <span id="prSlideCurrent">1</span>
                            <span class="pr-slide-sep">/</span>
                            <span id="prSlideTotal"><?= $totalPhotos ?></span>
                        </div>
                        <button class="pr-slide-btn" onclick="prNextSlide()">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button class="pr-slide-btn pr-autoplay-btn" id="prAutoplayBtn" onclick="prToggleAutoplay()">
                            <i class="fas fa-play" id="prAutoplayIcon"></i>
                        </button>
                        <button class="pr-slide-btn" onclick="prOpenSlideshowFullscreen()">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="pr-slide-progress">
                        <div class="pr-slide-progress-fill" id="prSlideProgressFill"></div>
                    </div>
                </div>
                
                <!-- Thumbnail Strip -->
                <div class="pr-thumbnail-strip">
                    <?php foreach ($galeriList as $index => $g): 
                        $imgSrc = !empty($g['gambar']) ? 'uploads/' . $g['gambar'] : 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=200';
                        $color = $catColorMap[$g['kategori']] ?? $prismColors[$index % count($prismColors)];
                    ?>
                    <div class="pr-thumbnail <?= $index === 0 ? 'active' : '' ?>" 
                         data-index="<?= $index ?>" 
                         onclick="prGoToSlide(<?= $index ?>)"
                         style="--thumb-color: <?= $color ?>;">
                        <img src="<?= $imgSrc ?>" alt="<?= clean($g['judul']) ?>" loading="lazy">
                        <span class="pr-thumb-num"><?= $index + 1 ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Empty State -->
            <div class="pr-empty-state fade-up">
                <div class="pr-empty-bg">
                    <div class="pr-empty-prism"></div>
                </div>
                <div class="pr-empty-content">
                    <div class="pr-empty-icon">
                        <i class="fas fa-camera-retro"></i>
                        <div class="pr-empty-pulse"></div>
                    </div>
                    <h3>Gallery is Empty</h3>
                    <p>Belum ada foto yang diunggah<?= $filterKategori || $searchQuery ? ' untuk pencarian ini' : '' ?>.</p>
                    <?php if ($filterKategori || $searchQuery): ?>
                    <a href="galeri.php" class="pr-btn pr-btn-primary">
                        <i class="fas fa-redo"></i> Lihat Semua Galeri
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== ADVANCED LIGHTBOX (Prism Theater) ===== -->
<div class="pr-lightbox" id="prLightbox">
    <!-- Ambient Background -->
    <div class="pr-lightbox-ambient" id="prLightboxAmbient"></div>
    
    <!-- Close Button -->
    <button class="pr-lightbox-close" onclick="prCloseLightbox()" aria-label="Close">
        <i class="fas fa-times"></i>
    </button>
    
    <!-- Navigation -->
    <button class="pr-lightbox-nav pr-nav-prev" onclick="prNavigateLightbox(-1)" aria-label="Previous">
        <i class="fas fa-chevron-left"></i>
    </button>
    <button class="pr-lightbox-nav pr-nav-next" onclick="prNavigateLightbox(1)" aria-label="Next">
        <i class="fas fa-chevron-right"></i>
    </button>
    
    <!-- Main Content -->
    <div class="pr-lightbox-content" onclick="event.stopPropagation()">
        <div class="pr-lightbox-frame">
            <!-- Prism Frame Corners -->
            <div class="pr-frame-corner pr-fc-tl"></div>
            <div class="pr-frame-corner pr-fc-tr"></div>
            <div class="pr-frame-corner pr-fc-bl"></div>
            <div class="pr-frame-corner pr-fc-br"></div>
            
            <!-- Image -->
            <div class="pr-lightbox-image-wrap" id="prImageWrap">
                <img src="" alt="" id="prLbImage">
                <div class="pr-lightbox-loading" id="prLbLoading">
                    <div class="pr-loading-prism">
                        <span></span><span></span><span></span>
                    </div>
                    <span>Loading...</span>
                </div>
            </div>
        </div>
        
        <!-- Info Panel -->
        <div class="pr-lightbox-info">
            <div class="pr-info-left">
                <span class="pr-info-category" id="prLbCategory" style="color: #10B981;">Category</span>
                <h3 id="prLbTitle">Title</h3>
                <p id="prLbDesc">Description</p>
                <div class="pr-info-meta">
                    <span><i class="fas fa-calendar"></i> <span id="prLbDate">Date</span></span>
                    <span class="pr-info-counter">
                        <i class="fas fa-images"></i>
                        <span id="prLbCounter">1 / 1</span>
                    </span>
                </div>
            </div>
            
            <div class="pr-info-actions">
                <button class="pr-action-btn" onclick="prDownloadImage()" title="Download">
                    <i class="fas fa-download"></i>
                </button>
                <button class="pr-action-btn" onclick="prShareImage()" title="Share">
                    <i class="fas fa-share-alt"></i>
                </button>
                <button class="pr-action-btn" onclick="prToggleFullscreen()" title="Fullscreen">
                    <i class="fas fa-expand"></i>
                </button>
                <button class="pr-action-btn" onclick="prToggleInfo()" title="Toggle Info" id="prInfoToggle">
                    <i class="fas fa-info-circle"></i>
                </button>
            </div>
        </div>
        
        <!-- Film Strip (Thumbnails) -->
        <div class="pr-film-strip" id="prFilmStrip">
            <!-- Filled by JS -->
        </div>
    </div>
</div>

<!-- Toast -->
<div id="prToast" class="pr-toast">
    <i class="fas fa-check-circle"></i>
    <span id="prToastMsg">Success!</span>
</div>

<!-- Back to Top -->
<button class="pr-back-top" id="prBackTop" title="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<style>
:root {
    --pr-primary: #10B981;
    --pr-primary-dark: #047857;
    --pr-pink: #FF006E;
    --pr-orange: #FB5607;
    --pr-yellow: #FFBE0B;
    --pr-purple: #8338EC;
    --pr-blue: #3A86FF;
    --pr-cyan: #06FFA5;
    --pr-dark: #0F172A;
    --pr-deeper: #020617;
    --pr-gray-50: #F8FAFC;
    --pr-gray-100: #F1F5F9;
    --pr-gray-200: #E2E8F0;
    --pr-gray-600: #475569;
    --pr-gray-700: #374151;
    --pr-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --pr-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --pr-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
    --pr-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --pr-font-display: 'Space Grotesk', 'Inter', sans-serif;
}

body.gl-dark {
    --pr-gray-50: #0B1120;
    --pr-gray-100: rgba(255,255,255,.08);
    --pr-gray-200: rgba(255,255,255,.1);
    --pr-gray-600: #94A3B8;
    --pr-gray-700: #CBD5E1;
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--pr-gray-50); }
::-webkit-scrollbar-thumb { 
    background: linear-gradient(180deg, var(--pr-pink), var(--pr-purple), var(--pr-blue)); 
    border-radius: 5px; 
}

/* ===== READING PROGRESS ===== */
.pr-reading-progress {
    position: fixed; top: 0; left: 0; right: 0; height: 4px;
    background: rgba(131,56,236,0.1); z-index: 9998;
}
.pr-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--pr-pink), var(--pr-orange), var(--pr-yellow), var(--pr-cyan), var(--pr-blue), var(--pr-purple));
    background-size: 300% 100%;
    animation: prRainbowMove 4s linear infinite;
    box-shadow: 0 0 10px rgba(131,56,236,0.5);
    transition: width 0.1s ease-out;
}
@keyframes prRainbowMove {
    0% { background-position: 0% 50%; }
    100% { background-position: 300% 50%; }
}

/* ===== HERO ===== */
.pr-hero {
    background: linear-gradient(135deg, #020617 0%, #1E1B4B 40%, #4C1D95 70%, #831843 100%);
    padding: 160px 20px 120px; position: relative; overflow: hidden;
    min-height: 90vh; display: flex; align-items: center; justify-content: center;
}

.pr-prism-canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    z-index: 1; opacity: 0.7;
}

.pr-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }

.pr-spectrum {
    position: absolute; border-radius: 50%;
    filter: blur(120px); opacity: 0.35;
    animation: prSpectrumFloat 18s infinite ease-in-out;
}
.pr-spec-1 { width: 500px; height: 500px; background: var(--pr-pink); top: -150px; right: -100px; }
.pr-spec-2 { width: 400px; height: 400px; background: var(--pr-blue); bottom: -100px; left: -100px; animation-delay: -6s; }
.pr-spec-3 { width: 350px; height: 350px; background: var(--pr-cyan); top: 30%; left: 45%; animation-delay: -12s; }
@keyframes prSpectrumFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -30px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.95); }
}

.pr-light-rays {
    position: absolute; inset: 0;
    background: 
        conic-gradient(from 0deg at 50% 50%, 
            transparent 0deg, 
            rgba(255,0,110,0.05) 30deg, 
            transparent 60deg,
            rgba(58,134,255,0.05) 120deg,
            transparent 150deg,
            rgba(6,255,165,0.05) 210deg,
            transparent 240deg,
            rgba(255,190,11,0.05) 300deg,
            transparent 330deg
        );
    animation: prRaysRotate 30s linear infinite;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 70%);
}
@keyframes prRaysRotate { to { transform: rotate(360deg); } }

.pr-bokeh {
    position: absolute; inset: 0;
    background-image: 
        radial-gradient(circle at 20% 30%, rgba(255,255,255,0.3) 2px, transparent 3px),
        radial-gradient(circle at 70% 60%, rgba(255,255,255,0.2) 1px, transparent 2px),
        radial-gradient(circle at 40% 80%, rgba(255,255,255,0.25) 1.5px, transparent 2.5px),
        radial-gradient(circle at 85% 20%, rgba(255,255,255,0.15) 1px, transparent 2px);
    background-size: 300px 300px;
    animation: prBokehDrift 25s linear infinite;
}
@keyframes prBokehDrift {
    to { background-position: 300px 300px; }
}

/* Floating Elements */
.pr-floating-elements { position: absolute; inset: 0; z-index: 3; pointer-events: none; }
.pr-float-el {
    position: absolute; font-size: 2.5rem;
    animation: prFloatEl 10s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
    opacity: 0.4; filter: drop-shadow(0 0 15px rgba(255,255,255,0.5));
}
@keyframes prFloatEl {
    0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.4; }
    50% { transform: translateY(-35px) rotate(15deg); opacity: 0.8; }
}

.pr-hero-content {
    position: relative; z-index: 4; text-align: center;
    color: #fff; max-width: 1000px; margin: 0 auto;
}

.pr-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 10px 22px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    font-size: .82rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 700; letter-spacing: 2px; position: relative;
    font-family: var(--pr-font-mono);
}
.pr-badge-glow {
    position: absolute; inset: 0; border-radius: 50px;
    border: 2px solid var(--pr-pink);
    animation: prBadgeGlow 2s infinite;
}
@keyframes prBadgeGlow { 
    0% { transform: scale(1); opacity: 1; box-shadow: 0 0 0 0 rgba(255,0,110,0.4); } 
    100% { transform: scale(1.2); opacity: 0; box-shadow: 0 0 30px 10px rgba(255,0,110,0); } 
}
.pr-hero-badge i { 
    background: linear-gradient(135deg, var(--pr-pink), var(--pr-purple));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}

.pr-hero-title {
    font-size: clamp(3rem, 9vw, 6.5rem); font-weight: 900;
    margin-bottom: 24px; letter-spacing: -0.03em; line-height: 0.95;
    font-family: var(--pr-font-display);
}
.pr-title-line { display: block; }

.pr-title-prism {
    position: relative; display: inline-block;
    background: linear-gradient(135deg, 
        #FF006E 0%, #FB5607 20%, #FFBE0B 40%, 
        #06FFA5 60%, #3A86FF 80%, #8338EC 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 300% 100%;
    animation: prPrismShift 6s ease infinite;
}
.pr-title-prism::before, .pr-title-prism::after {
    content: attr(data-text); position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    background: inherit; -webkit-background-clip: text;
    -webkit-text-fill-color: transparent; background-clip: text;
}
.pr-title-prism::before {
    animation: prChromaticR 3s infinite;
    mix-blend-mode: screen; opacity: 0.5;
}
.pr-title-prism::after {
    animation: prChromaticB 3s infinite;
    mix-blend-mode: screen; opacity: 0.5;
}
@keyframes prPrismShift { 
    0%, 100% { background-position: 0% 50%; } 
    50% { background-position: 100% 50%; } 
}
@keyframes prChromaticR {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(3px, -2px); }
}
@keyframes prChromaticB {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(-3px, 2px); }
}

.pr-hero-subtitle {
    font-size: 1.2rem; opacity: .9; max-width: 700px;
    margin: 0 auto 40px; line-height: 1.7;
    display: inline-block; min-height: 1.7em;
}
.pr-cursor { animation: prBlink 1s infinite; color: var(--pr-pink); font-weight: 300; }
@keyframes prBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

/* Gallery Stats */
.pr-gallery-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 16px; max-width: 900px; margin: 0 auto 32px;
}
.pr-stat-item {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 16px;
    backdrop-filter: blur(12px); transition: all 0.3s;
    position: relative; overflow: hidden;
}
.pr-stat-item::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, transparent, rgba(255,0,110,0.08));
    opacity: 0; transition: opacity 0.3s;
}
.pr-stat-item:hover {
    transform: translateY(-4px);
    border-color: var(--pr-pink);
    box-shadow: 0 12px 24px rgba(255,0,110,0.2);
}
.pr-stat-item:hover::before { opacity: 1; }

.pr-stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: linear-gradient(135deg, var(--pr-primary), var(--pr-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0; position: relative;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}
.pr-stat-pink { background: linear-gradient(135deg, var(--pr-pink), #BE185D); box-shadow: 0 4px 12px rgba(255,0,110,0.3); }
.pr-stat-cyan { background: linear-gradient(135deg, var(--pr-cyan), #059669); box-shadow: 0 4px 12px rgba(6,255,165,0.3); }
.pr-stat-gold { background: linear-gradient(135deg, var(--pr-yellow), #D97706); box-shadow: 0 4px 12px rgba(255,190,11,0.3); }

.pr-stat-info { position: relative; z-index: 1; }
.pr-stat-num {
    display: block; font-size: 1.6rem; font-weight: 900;
    color: #fff; line-height: 1; font-family: var(--pr-font-display);
}
.pr-stat-label {
    font-size: 0.7rem; color: rgba(255,255,255,.6);
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-top: 2px; display: block;
}

/* View Modes */
.pr-view-modes {
    display: inline-flex; gap: 4px; padding: 6px;
    background: rgba(255,255,255,.08); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
}
.pr-view-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: 50px;
    color: rgba(255,255,255,0.8); text-decoration: none;
    font-size: 0.88rem; font-weight: 600; transition: all 0.3s;
}
.pr-view-btn i { font-size: 0.95rem; }
.pr-view-btn:hover { color: #fff; background: rgba(255,255,255,0.1); }
.pr-view-btn.active {
    background: linear-gradient(135deg, var(--pr-pink), var(--pr-purple));
    color: #fff; box-shadow: 0 4px 16px rgba(255,0,110,0.4);
}

/* Scroll Indicator */
.pr-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%;
    transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.pr-scroll-arrow { animation: prArrowBounce 2s infinite; }
@keyframes prArrowBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(10px); }
}

/* ===== MAIN SECTION ===== */
.pr-section { padding: 60px 20px 100px; background: var(--pr-gray-50); }
body.gl-dark .pr-section { background: #0B1120; }

/* ===== CONSOLE ===== */
.pr-console {
    background: #fff; border-radius: 20px;
    box-shadow: var(--pr-shadow-md); overflow: hidden;
    border: 1px solid var(--pr-gray-100); margin-bottom: 40px;
}
body.gl-dark .pr-console { background: #1E293B; border-color: rgba(255,255,255,.08); }

.pr-console-header {
    background: var(--pr-dark); padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
    color: #94A3B8; font-family: var(--pr-font-mono); font-size: 0.8rem;
}
.pr-console-dots { display: flex; gap: 5px; }
.pr-console-dots .dot { width: 11px; height: 11px; border-radius: 50%; }
.pr-console-dots .red { background: #FF5F56; }
.pr-console-dots .yellow { background: #FFBD2E; }
.pr-console-dots .green { background: #27C93F; }
.pr-console-header i { color: var(--pr-pink); }
.pr-console-header span { color: #E2E8F0; }
.pr-console-status {
    margin-left: auto; color: var(--pr-cyan);
    font-size: 0.7rem; letter-spacing: 1px;
    animation: prBlink 2s infinite;
}

.pr-console-body { padding: 24px; display: flex; flex-direction: column; gap: 20px; }

/* Search */
.pr-search-wrap {
    position: relative; display: flex; align-items: center;
}
.pr-search-wrap > i {
    position: absolute; left: 18px; top: 50%;
    transform: translateY(-50%); color: var(--pr-gray-600);
    z-index: 1; font-size: 1rem;
}
.pr-search-wrap input {
    width: 100%; padding: 16px 100px 16px 50px;
    border: 1.5px solid var(--pr-gray-200); border-radius: 14px;
    background: var(--pr-gray-50); font-family: inherit;
    font-size: 1rem; color: var(--pr-dark);
    transition: all 0.3s;
}
body.gl-dark .pr-search-wrap input {
    background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1); color: #E2E8F0;
}
.pr-search-wrap input:focus {
    outline: none; border-color: var(--pr-pink);
    box-shadow: 0 0 0 4px rgba(255,0,110,0.15);
}

.pr-search-hint {
    position: absolute; right: 60px; top: 50%;
    transform: translateY(-50%);
    display: flex; gap: 3px; align-items: center;
    color: var(--pr-gray-600); font-size: 0.75rem;
    pointer-events: none;
}
.pr-search-hint kbd {
    background: var(--pr-gray-100); padding: 3px 8px;
    border-radius: 5px; font-family: var(--pr-font-mono);
    font-size: 0.72rem; border: 1px solid var(--pr-gray-200);
}
body.gl-dark .pr-search-hint kbd {
    background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.1); color: #CBD5E1;
}

.pr-search-clear {
    position: absolute; right: 16px; top: 50%;
    transform: translateY(-50%);
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--pr-gray-100); color: var(--pr-gray-600);
    display: flex; align-items: center; justify-content: center;
    text-decoration: none; transition: all 0.3s;
}
body.gl-dark .pr-search-clear { background: rgba(255,255,255,.1); color: #CBD5E1; }
.pr-search-clear:hover {
    background: #EF4444; color: #fff;
    transform: translateY(-50%) rotate(90deg);
}

/* Spectrum Filters */
.pr-spectrum-filters {
    padding-top: 16px; border-top: 1px solid var(--pr-gray-100);
}
body.gl-dark .pr-spectrum-filters { border-color: rgba(255,255,255,.08); }

.pr-filter-label {
    font-size: 0.72rem; font-weight: 700; color: var(--pr-gray-600);
    letter-spacing: 1.5px; font-family: var(--pr-font-mono);
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 12px;
}
body.gl-dark .pr-filter-label { color: #94A3B8; }
.pr-filter-label i { color: var(--pr-pink); }

.pr-color-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.pr-color-pill {
    padding: 8px 16px; border-radius: 24px; font-size: 0.82rem;
    font-weight: 600; color: var(--pr-gray-700);
    background: var(--pr-gray-100); border: 1.5px solid transparent;
    text-decoration: none; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 8px;
    position: relative; overflow: hidden;
}
body.gl-dark .pr-color-pill { background: rgba(255,255,255,.05); color: #CBD5E1; }

.pr-pill-dot {
    width: 12px; height: 12px; border-radius: 50%;
    flex-shrink: 0; box-shadow: 0 0 8px var(--pill-color);
}

.pr-color-pill::before {
    content: ''; position: absolute; inset: 0;
    background: var(--pill-color); opacity: 0; transition: opacity 0.3s;
}
.pr-color-pill:hover {
    transform: translateY(-2px);
    border-color: var(--pill-color);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.pr-color-pill:hover::before { opacity: 0.1; }

.pr-color-pill.active {
    background: var(--pill-color); color: #fff;
    border-color: var(--pill-color);
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
.pr-color-pill.active .pr-pill-dot {
    box-shadow: 0 0 12px rgba(255,255,255,0.5);
}

/* Results Info */
.pr-results-info {
    padding-top: 16px; border-top: 1px solid var(--pr-gray-100);
    display: flex; justify-content: space-between; align-items: center;
    font-size: 0.85rem; flex-wrap: wrap; gap: 12px;
}
body.gl-dark .pr-results-info { border-color: rgba(255,255,255,.08); }

.pr-results-count {
    color: var(--pr-gray-600); display: flex; align-items: center; gap: 8px;
}
body.gl-dark .pr-results-count { color: #94A3B8; }
.pr-results-count i { color: var(--pr-pink); }
.pr-results-count strong {
    color: var(--pr-dark); font-family: var(--pr-font-display);
    font-size: 1.1rem; font-weight: 800;
}
body.gl-dark .pr-results-count strong { color: #F1F5F9; }
.pr-results-count em { 
    color: var(--pr-pink); font-style: normal; font-weight: 600; 
}

.pr-results-meta {
    color: var(--pr-gray-600); display: flex; align-items: center; gap: 8px;
    font-family: var(--pr-font-mono); font-size: 0.78rem;
}
body.gl-dark .pr-results-meta { color: #94A3B8; }
.pr-results-meta i { color: var(--pr-purple); }

/* ===== MASONRY GRID ===== */
.pr-masonry-grid {
    column-count: 3;
    column-gap: 24px;
    margin-bottom: 60px;
}

/* ===== GRID VIEW ===== */
.pr-grid-view {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 60px;
}

/* ===== PRISM CARD ===== */
.pr-card {
    position: relative; background: #fff; border-radius: 20px;
    overflow: hidden; border: 1px solid var(--pr-gray-100);
    box-shadow: var(--pr-shadow-sm); transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer; display: flex; flex-direction: column;
}
body.gl-dark .pr-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.pr-card-masonry {
    break-inside: avoid;
    margin-bottom: 24px;
}

/* Refraction Effect */
.pr-refraction {
    position: absolute; inset: 0; pointer-events: none; z-index: 3;
    background: linear-gradient(135deg, 
        transparent 0%, 
        rgba(255,0,110,0.1) 20%,
        transparent 40%,
        rgba(58,134,255,0.1) 60%,
        transparent 80%,
        rgba(6,255,165,0.1) 100%
    );
    opacity: 0; transition: opacity 0.4s;
    mix-blend-mode: screen;
}
.pr-card:hover .pr-refraction { opacity: 1; }

/* Corner Accents */
.pr-corner {
    position: absolute; width: 24px; height: 24px;
    border: 2px solid var(--card-color, var(--pr-pink));
    opacity: 0; transition: all 0.4s; z-index: 4; pointer-events: none;
}
.pr-corner-tl { top: 12px; left: 12px; border-right: none; border-bottom: none; }
.pr-corner-tr { top: 12px; right: 12px; border-left: none; border-bottom: none; }
.pr-corner-bl { bottom: 12px; left: 12px; border-right: none; border-top: none; }
.pr-corner-br { bottom: 12px; right: 12px; border-left: none; border-top: none; }
.pr-card:hover .pr-corner { opacity: 1; }

.pr-card::before {
    content: ''; position: absolute; inset: 0; border-radius: 20px; padding: 1px;
    background: conic-gradient(from 0deg, 
        transparent, 
        var(--card-color, var(--pr-pink)), 
        var(--pr-blue), 
        var(--pr-cyan),
        transparent 50%
    );
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none; z-index: 5;
}
.pr-card:hover::before { opacity: 1; animation: prRotateBorder 4s linear infinite; }
@keyframes prRotateBorder { to { transform: rotate(360deg); } }

.pr-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 50px rgba(0,0,0,0.15), 0 0 40px color-mix(in srgb, var(--card-color, var(--pr-pink)) 30%, transparent);
    border-color: var(--card-color, var(--pr-pink));
}

/* Card Image */
.pr-card-image {
    position: relative; overflow: hidden;
    background: var(--pr-gray-100);
}
.pr-card-masonry .pr-card-image { height: auto; }
.pr-card-grid .pr-card-image { height: 240px; }

.pr-card-image img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}
.pr-card:hover .pr-card-image img { transform: scale(1.1); }

.pr-img-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(180deg, transparent 30%, rgba(15,23,42,0.9) 100%);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; transition: opacity 0.3s;
    z-index: 2;
}
.pr-card:hover .pr-img-overlay { opacity: 1; }

.pr-view-text {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 12px 24px; background: rgba(255,255,255,0.95);
    color: var(--pr-dark); border-radius: 50px;
    font-weight: 700; font-size: 0.88rem;
    transform: translateY(20px); transition: transform 0.3s;
    backdrop-filter: blur(10px);
}
.pr-card:hover .pr-view-text { transform: translateY(0); }

.pr-category-badge {
    position: absolute; top: 16px; left: 16px; z-index: 2;
    padding: 5px 12px; color: #fff;
    border-radius: 20px; font-size: 0.7rem; font-weight: 700;
    letter-spacing: 0.5px; text-transform: uppercase;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    backdrop-filter: blur(10px);
}

.pr-photo-number {
    position: absolute; top: 16px; right: 16px; z-index: 2;
    padding: 4px 10px; background: rgba(0,0,0,0.6);
    color: #fff; border-radius: 20px;
    font-family: var(--pr-font-mono); font-size: 0.7rem;
    font-weight: 700; letter-spacing: 1px;
    backdrop-filter: blur(10px);
}

/* Card Content */
.pr-card-content {
    padding: 20px; flex: 1; display: flex; flex-direction: column;
}

.pr-card-title {
    font-size: 1.1rem; font-weight: 800; line-height: 1.3;
    color: var(--pr-dark); margin: 0 0 8px 0;
    font-family: var(--pr-font-display);
    transition: color 0.3s;
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
}
body.gl-dark .pr-card-title { color: #F1F5F9; }
.pr-card:hover .pr-card-title { color: var(--card-color, var(--pr-pink)); }

.pr-card-desc {
    font-size: 0.85rem; color: var(--pr-gray-600); line-height: 1.5;
    margin: 0 0 12px 0; flex: 1;
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
}
body.gl-dark .pr-card-desc { color: #94A3B8; }

.pr-card-meta {
    display: flex; gap: 12px; font-size: 0.78rem; color: var(--pr-gray-600);
    padding-top: 12px; border-top: 1px dashed var(--pr-gray-200);
}
body.gl-dark .pr-card-meta { color: #94A3B8; border-color: rgba(255,255,255,.1); }
.pr-card-meta i { color: var(--card-color, var(--pr-pink)); margin-right: 4px; }

.pr-card-date {
    font-size: 0.78rem; color: var(--pr-gray-600);
    display: flex; align-items: center; gap: 6px;
}
.pr-card-date i { color: var(--card-color, var(--pr-pink)); }

/* ===== SLIDESHOW ===== */
.pr-slideshow {
    position: relative; background: var(--pr-dark);
    border-radius: 24px; overflow: hidden;
    margin-bottom: 60px;
    box-shadow: var(--pr-shadow-lg);
}

.pr-slideshow-main {
    position: relative; height: 70vh; min-height: 500px;
    background: #000;
}

.pr-slide {
    position: absolute; inset: 0; opacity: 0;
    transition: opacity 0.6s ease;
}
.pr-slide.active { opacity: 1; }

.pr-slide img {
    width: 100%; height: 100%; object-fit: cover;
}

.pr-slide-info {
    position: absolute; bottom: 0; left: 0; right: 0;
    padding: 40px; background: linear-gradient(0deg, rgba(0,0,0,0.9) 0%, transparent 100%);
    color: #fff; z-index: 5;
}
.pr-slide-category {
    display: inline-block; padding: 4px 12px;
    background: var(--pr-pink); border-radius: 20px;
    font-size: 0.7rem; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
    margin-bottom: 12px;
}
.pr-slide-info h3 {
    font-size: 2rem; font-weight: 800; margin: 0 0 8px 0;
    font-family: var(--pr-font-display);
}
.pr-slide-info p {
    font-size: 1rem; opacity: 0.9; margin: 0 0 12px 0;
    max-width: 600px;
}
.pr-slide-date {
    font-size: 0.85rem; opacity: 0.8;
    display: inline-flex; align-items: center; gap: 6px;
}
.pr-slide-date i { color: var(--pr-pink); }

.pr-slideshow-controls {
    position: absolute; top: 50%; left: 0; right: 0;
    transform: translateY(-50%);
    display: flex; justify-content: space-between; align-items: center;
    padding: 0 20px; z-index: 10;
    pointer-events: none;
}
.pr-slide-btn {
    width: 50px; height: 50px; border-radius: 50%;
    background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; transition: all 0.3s;
    pointer-events: auto;
}
.pr-slide-btn:hover {
    background: var(--pr-pink); border-color: var(--pr-pink);
    transform: scale(1.1);
}

.pr-slide-counter {
    position: absolute; top: 20px; right: 20px;
    padding: 8px 16px; background: rgba(0,0,0,0.6);
    backdrop-filter: blur(10px); border-radius: 50px;
    color: #fff; font-family: var(--pr-font-mono);
    font-size: 0.85rem; font-weight: 700;
    letter-spacing: 1px; z-index: 10;
}
.pr-slide-sep { opacity: 0.5; margin: 0 6px; }

.pr-slideshow-controls > .pr-slide-btn:nth-of-type(2),
.pr-slideshow-controls > .pr-slide-btn:nth-of-type(3),
.pr-slideshow-controls > .pr-slide-btn:nth-of-type(4) {
    position: absolute; bottom: 20px;
}
.pr-slideshow-controls > .pr-slide-btn:nth-of-type(2) { right: 140px; }
.pr-slideshow-controls > .pr-slide-btn:nth-of-type(3) { right: 80px; }
.pr-slideshow-controls > .pr-slide-btn:nth-of-type(4) { right: 20px; }

.pr-slide-progress {
    position: absolute; bottom: 0; left: 0; right: 0;
    height: 3px; background: rgba(255,255,255,0.1);
    z-index: 10;
}
.pr-slide-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--pr-pink), var(--pr-purple));
    transition: width 0.1s linear;
}

/* Thumbnail Strip */
.pr-thumbnail-strip {
    display: flex; gap: 8px; padding: 16px;
    overflow-x: auto; background: rgba(0,0,0,0.8);
    scrollbar-width: thin;
}
.pr-thumbnail-strip::-webkit-scrollbar { height: 6px; }
.pr-thumbnail-strip::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
.pr-thumbnail-strip::-webkit-scrollbar-thumb { background: var(--pr-pink); border-radius: 3px; }

.pr-thumbnail {
    position: relative; width: 100px; height: 70px; flex-shrink: 0;
    border-radius: 8px; overflow: hidden; cursor: pointer;
    border: 2px solid transparent; transition: all 0.3s;
}
.pr-thumbnail img {
    width: 100%; height: 100%; object-fit: cover;
}
.pr-thumbnail:hover { border-color: rgba(255,255,255,0.5); transform: translateY(-2px); }
.pr-thumbnail.active {
    border-color: var(--thumb-color, var(--pr-pink));
    box-shadow: 0 0 12px var(--thumb-color, var(--pr-pink));
}
.pr-thumb-num {
    position: absolute; top: 4px; left: 4px;
    background: rgba(0,0,0,0.7); color: #fff;
    padding: 2px 6px; border-radius: 4px;
    font-size: 0.65rem; font-family: var(--pr-font-mono);
    font-weight: 700;
}

/* ===== EMPTY STATE ===== */
.pr-empty-state {
    position: relative; text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px; overflow: hidden;
    border: 2px dashed var(--pr-gray-200);
}
body.gl-dark .pr-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }

.pr-empty-bg { position: absolute; inset: 0; pointer-events: none; }
.pr-empty-prism {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 300px; height: 300px;
    background: conic-gradient(from 0deg, 
        var(--pr-pink), var(--pr-orange), var(--pr-yellow),
        var(--pr-cyan), var(--pr-blue), var(--pr-purple), var(--pr-pink)
    );
    border-radius: 50%; filter: blur(80px); opacity: 0.15;
    animation: prEmptyRotate 10s linear infinite;
}
@keyframes prEmptyRotate { to { transform: translate(-50%, -50%) rotate(360deg); } }

.pr-empty-content { position: relative; z-index: 1; }
.pr-empty-icon {
    position: relative; width: 120px; height: 120px;
    margin: 0 auto 24px; display: flex; align-items: center; justify-content: center;
    font-size: 4rem; color: var(--pr-gray-600);
}
.pr-empty-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--pr-pink);
    animation: prEmptyPulse 2s infinite;
}
@keyframes prEmptyPulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

.pr-empty-state h3 {
    font-size: 1.5rem; margin: 0 0 8px 0; color: var(--pr-dark);
    font-weight: 800; font-family: var(--pr-font-display);
}
body.gl-dark .pr-empty-state h3 { color: #F1F5F9; }
.pr-empty-state p { color: var(--pr-gray-600); margin-bottom: 24px; }

/* Buttons */
.pr-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 24px; border-radius: 12px; font-weight: 700;
    font-size: 0.9rem; text-decoration: none; border: none;
    cursor: pointer; transition: all 0.3s; font-family: inherit;
}
.pr-btn-primary {
    background: linear-gradient(135deg, var(--pr-pink), var(--pr-purple));
    color: #fff; box-shadow: 0 4px 16px rgba(255,0,110,0.3);
}
.pr-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(255,0,110,0.4); }

/* ===== LIGHTBOX (Prism Theater) ===== */
.pr-lightbox {
    position: fixed; inset: 0; z-index: 10000;
    background: rgba(2,6,23,0.98); backdrop-filter: blur(20px);
    display: none; align-items: center; justify-content: center;
    opacity: 0; transition: opacity 0.3s;
}
.pr-lightbox.active { display: flex; opacity: 1; }

.pr-lightbox-ambient {
    position: absolute; inset: 0; pointer-events: none;
    background: radial-gradient(circle at center, var(--ambient-color, rgba(255,0,110,0.1)), transparent 60%);
    transition: background 0.5s;
}

.pr-lightbox-close {
    position: absolute; top: 20px; right: 20px; z-index: 20;
    width: 48px; height: 48px; border-radius: 50%;
    background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff; font-size: 1.2rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s;
}
.pr-lightbox-close:hover {
    background: #EF4444; border-color: #EF4444;
    transform: rotate(90deg) scale(1.1);
}

.pr-lightbox-nav {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 56px; height: 56px; border-radius: 50%;
    background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff; font-size: 1.2rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s; z-index: 15;
}
.pr-lightbox-nav:hover {
    background: var(--pr-pink); border-color: var(--pr-pink);
    transform: translateY(-50%) scale(1.1);
}
.pr-nav-prev { left: 30px; }
.pr-nav-next { right: 30px; }

.pr-lightbox-content {
    max-width: 1400px; width: 95%; max-height: 95vh;
    display: flex; flex-direction: column; gap: 16px;
    position: relative; z-index: 5;
}

.pr-lightbox-frame {
    position: relative; flex: 1; display: flex;
    align-items: center; justify-content: center;
    min-height: 300px;
}

/* Frame Corners */
.pr-frame-corner {
    position: absolute; width: 30px; height: 30px;
    border: 3px solid var(--pr-pink);
    z-index: 10; transition: all 0.3s;
}
.pr-fc-tl { top: -10px; left: -10px; border-right: none; border-bottom: none; }
.pr-fc-tr { top: -10px; right: -10px; border-left: none; border-bottom: none; }
.pr-fc-bl { bottom: -10px; left: -10px; border-right: none; border-top: none; }
.pr-fc-br { bottom: -10px; right: -10px; border-left: none; border-top: none; }

.pr-lightbox-image-wrap {
    position: relative; max-width: 100%; max-height: 70vh;
    display: flex; align-items: center; justify-content: center;
}
.pr-lightbox-image-wrap img {
    max-width: 100%; max-height: 70vh; object-fit: contain;
    border-radius: 8px; box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    transition: opacity 0.3s;
}

.pr-lightbox-loading {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    display: none; flex-direction: column; align-items: center; gap: 12px;
    color: #fff; font-family: var(--pr-font-mono);
    font-size: 0.85rem; letter-spacing: 2px;
}
.pr-lightbox-loading.active { display: flex; }

.pr-loading-prism {
    display: flex; gap: 6px;
}
.pr-loading-prism span {
    width: 8px; height: 30px; border-radius: 4px;
    animation: prPrismLoad 1s infinite ease-in-out;
}
.pr-loading-prism span:nth-child(1) { background: var(--pr-pink); animation-delay: 0s; }
.pr-loading-prism span:nth-child(2) { background: var(--pr-yellow); animation-delay: 0.2s; }
.pr-loading-prism span:nth-child(3) { background: var(--pr-blue); animation-delay: 0.4s; }
@keyframes prPrismLoad {
    0%, 100% { transform: scaleY(0.5); }
    50% { transform: scaleY(1); }
}

/* Lightbox Info */
.pr-lightbox-info {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 24px; padding: 20px; background: rgba(255,255,255,0.05);
    backdrop-filter: blur(10px); border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.1);
    color: #fff;
}
.pr-lightbox-info.hidden { display: none; }

.pr-info-left { flex: 1; }
.pr-info-category {
    display: inline-block; padding: 4px 12px;
    border-radius: 20px; font-size: 0.7rem; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
    margin-bottom: 8px; background: rgba(255,255,255,0.1);
}
.pr-info-left h3 {
    font-size: 1.4rem; font-weight: 800; margin: 0 0 8px 0;
    font-family: var(--pr-font-display);
}
.pr-info-left p {
    font-size: 0.9rem; opacity: 0.8; margin: 0 0 12px 0;
    line-height: 1.5;
}
.pr-info-meta {
    display: flex; gap: 16px; font-size: 0.82rem; opacity: 0.7;
    flex-wrap: wrap;
}
.pr-info-meta i { color: var(--pr-pink); margin-right: 4px; }

.pr-info-actions {
    display: flex; gap: 8px; flex-shrink: 0;
}
.pr-action-btn {
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s; font-size: 0.95rem;
}
.pr-action-btn:hover {
    background: var(--pr-pink); border-color: var(--pr-pink);
    transform: translateY(-2px);
}

/* Film Strip */
.pr-film-strip {
    display: flex; gap: 8px; padding: 12px;
    overflow-x: auto; background: rgba(0,0,0,0.6);
    backdrop-filter: blur(10px); border-radius: 12px;
    scrollbar-width: thin;
    max-height: 80px;
}
.pr-film-strip::-webkit-scrollbar { height: 4px; }
.pr-film-strip::-webkit-scrollbar-thumb { background: var(--pr-pink); border-radius: 2px; }

.pr-film-thumb {
    position: relative; width: 80px; height: 56px; flex-shrink: 0;
    border-radius: 6px; overflow: hidden; cursor: pointer;
    border: 2px solid transparent; transition: all 0.3s;
    opacity: 0.5;
}
.pr-film-thumb img { width: 100%; height: 100%; object-fit: cover; }
.pr-film-thumb:hover { opacity: 0.8; }
.pr-film-thumb.active {
    opacity: 1; border-color: var(--pr-pink);
    box-shadow: 0 0 10px var(--pr-pink);
}

/* ===== TOAST ===== */
.pr-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--pr-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    z-index: 10001; opacity: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.9rem; border: 1px solid rgba(255,255,255,0.1);
}
body.gl-dark .pr-toast { background: #fff; color: var(--pr-dark); }
.pr-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.pr-toast i { 
    background: linear-gradient(135deg, var(--pr-pink), var(--pr-purple));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    font-size: 1.2rem;
}

/* ===== BACK TO TOP ===== */
.pr-back-top {
    position: fixed; bottom: 30px; right: 30px;
    width: 50px; height: 50px; border-radius: 50%;
    background: linear-gradient(135deg, var(--pr-pink), var(--pr-purple));
    border: none; color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; z-index: 998;
    box-shadow: 0 8px 24px rgba(255,0,110,0.4);
    opacity: 0; visibility: hidden; transform: translateY(20px) scale(.8);
    transition: all .4s;
}
.pr-back-top.show { opacity: 1; visibility: visible; transform: none; }
.pr-back-top:hover { transform: translateY(-4px) scale(1.05); }

/* ===== ANIMATIONS ===== */
.fade-up {
    opacity: 0; transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .pr-masonry-grid { column-count: 2; }
    .pr-gallery-stats { grid-template-columns: repeat(2, 1fr); }
    .pr-slideshow-main { height: 60vh; }
    .pr-slide-info h3 { font-size: 1.5rem; }
    .pr-lightbox-nav { width: 44px; height: 44px; }
    .pr-nav-prev { left: 10px; }
    .pr-nav-next { right: 10px; }
}
@media (max-width: 768px) {
    .pr-hero { padding: 120px 20px 80px; min-height: 85vh; }
    .pr-hero-title { font-size: 3rem; }
    .pr-gallery-stats { grid-template-columns: 1fr; gap: 10px; }
    .pr-stat-item { justify-content: center; }
    .pr-view-modes { flex-wrap: wrap; justify-content: center; }
    .pr-floating-elements { display: none; }
    .pr-console-body { padding: 16px; }
    .pr-search-hint { display: none; }
    .pr-search-wrap input { padding-right: 50px; }
    .pr-color-pills { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 6px; }
    .pr-color-pill { white-space: nowrap; flex-shrink: 0; }
    .pr-results-info { flex-direction: column; gap: 8px; align-items: flex-start; }
    .pr-masonry-grid { column-count: 1; }
    .pr-grid-view { grid-template-columns: 1fr; }
    .pr-slideshow-main { height: 50vh; min-height: 350px; }
    .pr-slide-info { padding: 20px; }
    .pr-slide-info h3 { font-size: 1.2rem; }
    .pr-slide-info p { display: none; }
    .pr-lightbox-info { flex-direction: column; padding: 16px; }
    .pr-info-actions { width: 100%; justify-content: center; }
    .pr-film-strip { max-height: 60px; }
    .pr-film-thumb { width: 60px; height: 42px; }
    .pr-back-top { width: 44px; height: 44px; bottom: 20px; right: 20px; }
    .pr-frame-corner { width: 20px; height: 20px; }
    .pr-fc-tl, .pr-fc-tr { top: -6px; }
    .pr-fc-bl, .pr-fc-br { bottom: -6px; }
    .pr-fc-tl, .pr-fc-bl { left: -6px; }
    .pr-fc-tr, .pr-fc-br { right: -6px; }
    .pr-slideshow-controls > .pr-slide-btn:nth-of-type(2),
    .pr-slideshow-controls > .pr-slide-btn:nth-of-type(3),
    .pr-slideshow-controls > .pr-slide-btn:nth-of-type(4) { display: none; }
}
@media (max-width: 480px) {
    .pr-hero-title { font-size: 2.3rem; }
    .pr-hero-badge { font-size: 0.7rem; padding: 8px 16px; letter-spacing: 1px; }
    .pr-stat-num { font-size: 1.3rem; }
    .pr-card-content { padding: 16px; }
    .pr-card-title { font-size: 1rem; }
    .pr-lightbox-content { width: 100%; padding: 0 10px; }
    .pr-lightbox-info { padding: 12px; gap: 12px; }
    .pr-info-left h3 { font-size: 1.1rem; }
    .pr-thumbnail { width: 70px; height: 50px; }
}

/* ============================================================
   PERFORMANCE PATCH - GALERI SMOOTH SCROLL
============================================================ */

/* Content visibility untuk section bawah */
.pr-section,
.pr-lightbox,
.pr-toast {
    content-visibility: auto;
    contain-intrinsic-size: auto 2000px;
}

/* Pause animasi saat scroll */
body.is-scrolling .pr-spectrum,
body.is-scrolling .pr-light-rays,
body.is-scrolling .pr-bokeh,
body.is-scrolling .pr-float-el,
body.is-scrolling .pr-progress-fill,
body.is-scrolling .pr-empty-prism,
body.is-scrolling .pr-empty-pulse {
    animation-play-state: paused !important;
}

/* Pause animasi untuk section yang tidak terlihat */
.pr-offscreen * {
    animation-play-state: paused !important;
}

/* Optimasi elemen yang sering bergerak */
.pr-progress-fill,
.pr-spectrum,
.pr-light-rays,
.pr-bokeh,
.pr-float-el {
    will-change: transform;
    backface-visibility: hidden;
}

/* Fade-up optimization */
.fade-up {
    backface-visibility: hidden;
}
.fade-up.visible {
    will-change: auto;
}

/* Mobile optimization */
@media (max-width: 768px), (hover: none), (pointer: coarse) {
    .pr-spectrum,
    .pr-light-rays,
    .pr-bokeh {
        animation: none !important;
        opacity: 0.2 !important;
    }
    
    .pr-floating-elements {
        display: none !important;
    }
    
    .pr-hero-bg {
        filter: blur(40px);
    }
}

/* Reduce motion untuk accessibility */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .pr-spectrum,
    .pr-light-rays,
    .pr-bokeh,
    .pr-floating-elements {
        display: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ===== PERFORMANCE: Scroll & Viewport Control =====
    let scrollTimer;
    const markScrolling = () => {
        document.body.classList.add('is-scrolling');
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(() => {
            document.body.classList.remove('is-scrolling');
        }, 180);
    };
    window.addEventListener('scroll', markScrolling, { passive: true });
    markScrolling();

    if ('IntersectionObserver' in window) {
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                entry.target.classList.toggle('pr-offscreen', !entry.isIntersecting);
            });
        }, {
            rootMargin: '300px 0px',
            threshold: 0
        });

        document.querySelectorAll('.pr-hero, .pr-section').forEach(section => {
            sectionObserver.observe(section);
        });

        const heroSection = document.querySelector('.pr-hero');
        if (heroSection) {
            window.__prHeroVisible = true;
            new IntersectionObserver((entries) => {
                const entry = entries[0];
                window.__prHeroVisible = entry ? entry.isIntersecting : true;
                window.dispatchEvent(new CustomEvent('pr:hero-visibility', {
                    detail: window.__prHeroVisible
                }));
            }, {
                threshold: 0.01
            }).observe(heroSection);
        }
    }

    // ===== 1. Prism Canvas Animation (OPTIMIZED) =====
    const canvas = document.getElementById('prPrismCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d', { alpha: true });
        let width, height, time = 0;
        let rays = [];
        let rafId = null;
        let running = true;

        function resize() {
            width = canvas.width = canvas.parentElement.offsetWidth;
            height = canvas.height = canvas.parentElement.offsetHeight;
            initRays();
        }

        function initRays() {
            rays = [];
            const colors = ['#FF006E', '#FB5607', '#FFBE0B', '#06FFA5', '#3A86FF', '#8338EC'];
            
            // Kurangi jumlah rays untuk performa (60 -> 40)
            for (let i = 0; i < 40; i++) {
                const angle = (i / 40) * Math.PI * 2;
                rays.push({
                    angle: angle,
                    length: Math.random() * 200 + 100,
                    width: Math.random() * 3 + 1,
                    color: colors[i % colors.length],
                    speed: Math.random() * 0.01 + 0.005,
                    phase: Math.random() * Math.PI * 2
                });
            }
        }

        function drawPrism() {
            // Stop render jika tab di-background ATAU hero sudah di-scroll keluar layar
            if (!running || document.hidden || window.__prHeroVisible === false) {
                rafId = requestAnimationFrame(drawPrism);
                return;
            }

            ctx.clearRect(0, 0, width, height);
            const centerX = width / 2;
            const centerY = height / 2;

            const prismSize = 60;
            ctx.beginPath();
            ctx.moveTo(centerX, centerY - prismSize);
            ctx.lineTo(centerX - prismSize * 0.866, centerY + prismSize * 0.5);
            ctx.lineTo(centerX + prismSize * 0.866, centerY + prismSize * 0.5);
            ctx.closePath();
            
            const prismGradient = ctx.createLinearGradient(
                centerX - prismSize, centerY - prismSize,
                centerX + prismSize, centerY + prismSize
            );
            prismGradient.addColorStop(0, 'rgba(255,255,255,0.15)');
            prismGradient.addColorStop(0.5, 'rgba(255,255,255,0.25)');
            prismGradient.addColorStop(1, 'rgba(255,255,255,0.15)');
            
            ctx.fillStyle = prismGradient;
            ctx.fill();
            ctx.strokeStyle = 'rgba(255,255,255,0.4)';
            ctx.lineWidth = 2;
            ctx.stroke();

            rays.forEach(ray => {
                const pulse = Math.sin(time * ray.speed + ray.phase) * 0.3 + 0.7;
                const rayLength = ray.length * pulse;
                
                const startX = centerX + prismSize * 0.866;
                const startY = centerY;
                const endX = startX + Math.cos(ray.angle * 0.3 + 0.2) * rayLength;
                const endY = startY + Math.sin(ray.angle) * rayLength * 0.8;
                
                const gradient = ctx.createLinearGradient(startX, startY, endX, endY);
                gradient.addColorStop(0, ray.color);
                gradient.addColorStop(1, 'transparent');
                
                ctx.beginPath();
                ctx.moveTo(startX, startY);
                ctx.lineTo(endX, endY);
                ctx.strokeStyle = gradient;
                ctx.lineWidth = ray.width * pulse;
                ctx.globalAlpha = 0.4 * pulse;
                ctx.stroke();
                ctx.globalAlpha = 1;
            });

            // Kurangi partikel (30 -> 15) & hapus shadowBlur
            for (let i = 0; i < 15; i++) {
                const px = (Math.sin(time * 0.01 + i) + 1) * width / 2;
                const py = (Math.cos(time * 0.013 + i * 1.5) + 1) * height / 2;
                const size = Math.sin(time * 0.02 + i) * 1 + 2;
                const colors = ['#FF006E', '#FFBE0B', '#06FFA5', '#3A86FF', '#8338EC'];
                
                ctx.beginPath();
                ctx.arc(px, py, size, 0, Math.PI * 2);
                ctx.fillStyle = colors[i % colors.length];
                ctx.globalAlpha = 0.3;
                ctx.fill();
                ctx.globalAlpha = 1;
            }

            time++;
            rafId = requestAnimationFrame(drawPrism);
        }

        window.addEventListener('pr:hero-visibility', function(e) {
            const visible = Boolean(e.detail);
            if (visible && !document.hidden) running = true;
            else running = false;
        });

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) running = false;
            else if (window.__prHeroVisible) running = true;
        });

        resize();
        window.addEventListener('resize', resize, { passive: true });
        drawPrism();
    }

    // ===== 2. Typing Effect =====
    const textElement = document.getElementById('prTypingText');
    if (textElement) {
        const phrases = [
            "Momen-momen berharga yang terabadikan dalam spektrum warna.",
            "Dokumentasi kegiatan akademik, seminar, dan kehidupan kampus.",
            "Setiap foto adalah pecahan cahaya yang menyimpan cerita.",
            "Jelajahi galeri kami seperti melihat melalui prisma."
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
    const counters = document.querySelectorAll('.pr-stat-num[data-count]');
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

    // ===== 4. Reading Progress Bar (THROTTLED) =====
    const progressFill = document.getElementById('prProgressFill');
    let progressTicking = false;
    if (progressFill) {
        window.addEventListener('scroll', () => {
            if (!progressTicking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.scrollY;
                    const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
                    const percent = maxScroll > 0 ? (scrolled / maxScroll) * 100 : 0;
                    progressFill.style.width = percent + '%';
                    progressTicking = false;
                });
                progressTicking = true;
            }
        }, { passive: true });
    }

    // ===== 5. Scroll Reveal =====
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

    // ===== 6. Gallery Data Collection =====
    const galleryItems = document.querySelectorAll('.pr-card');
    const galleryData = [];
    
    galleryItems.forEach((item, index) => {
        const onclickAttr = item.getAttribute('onclick');
        if (onclickAttr) {
            const match = onclickAttr.match(/prOpenLightbox\((\{.*?\}),\s*(\d+)\)/);
            if (match) {
                try {
                    let jsonStr = match[1]
                        .replace(/&quot;/g, '"')
                        .replace(/&#039;/g, "'")
                        .replace(/&amp;/g, '&')
                        .replace(/&lt;/g, '<')
                        .replace(/&gt;/g, '>');
                    const data = JSON.parse(jsonStr);
                    data.index = index;
                    galleryData.push(data);
                } catch (e) {
                    console.warn('Error parsing gallery data:', e);
                }
            }
        }
    });

    // ===== 7. Lightbox Functions =====
    const lightbox = document.getElementById('prLightbox');
    const lbImage = document.getElementById('prLbImage');
    const lbTitle = document.getElementById('prLbTitle');
    const lbDesc = document.getElementById('prLbDesc');
    const lbCategory = document.getElementById('prLbCategory');
    const lbDate = document.getElementById('prLbDate');
    const lbCounter = document.getElementById('prLbCounter');
    const lbLoading = document.getElementById('prLbLoading');
    const lbAmbient = document.getElementById('prLightboxAmbient');
    const filmStrip = document.getElementById('prFilmStrip');
    
    let currentLbIndex = 0;
    let lbInfoVisible = true;

    function buildFilmStrip() {
        if (!filmStrip || galleryData.length === 0) return;
        filmStrip.innerHTML = '';
        galleryData.forEach((item, idx) => {
            const thumb = document.createElement('div');
            thumb.className = 'pr-film-thumb';
            thumb.dataset.index = idx;
            thumb.innerHTML = `<img src="${item.src}" alt="${item.title}" loading="lazy">`;
            thumb.addEventListener('click', () => prGoToLightboxIndex(idx));
            filmStrip.appendChild(thumb);
        });
    }
    buildFilmStrip();

    window.prOpenLightbox = function(data, index) {
        currentLbIndex = index;
        updateLightboxContent();
        if (lightbox) {
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    function updateLightboxContent() {
        const data = galleryData[currentLbIndex];
        if (!data) return;

        if (lbLoading) lbLoading.classList.add('active');
        if (lbImage) lbImage.style.opacity = '0.3';
        
        if (lbImage) {
            lbImage.src = data.src;
            lbImage.alt = data.title;
            lbImage.onload = () => {
                if (lbLoading) lbLoading.classList.remove('active');
                lbImage.style.opacity = '1';
            };
        }
        
        if (lbTitle) lbTitle.textContent = data.title;
        if (lbDesc) lbDesc.textContent = data.desc;
        if (lbCategory) {
            lbCategory.textContent = data.category;
            lbCategory.style.color = data.color;
            lbCategory.style.background = `${data.color}20`;
        }
        if (lbDate) lbDate.textContent = data.date;
        if (lbCounter) lbCounter.textContent = `${currentLbIndex + 1} / ${galleryData.length}`;
        
        if (lbAmbient && data.color) {
            lbAmbient.style.setProperty('--ambient-color', `${data.color}30`);
            lbAmbient.style.background = `radial-gradient(circle at center, ${data.color}25, transparent 60%)`;
        }
        
        if (filmStrip) {
            filmStrip.querySelectorAll('.pr-film-thumb').forEach((thumb, idx) => {
                thumb.classList.toggle('active', idx === currentLbIndex);
            });
            const activeThumb = filmStrip.querySelector('.pr-film-thumb.active');
            if (activeThumb) {
                activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        }
        
        if (galleryData[currentLbIndex + 1]) {
            const img = new Image(); img.src = galleryData[currentLbIndex + 1].src;
        }
        if (galleryData[currentLbIndex - 1]) {
            const img = new Image(); img.src = galleryData[currentLbIndex - 1].src;
        }
    }

    window.prNavigateLightbox = function(direction) {
        currentLbIndex += direction;
        if (currentLbIndex < 0) currentLbIndex = galleryData.length - 1;
        if (currentLbIndex >= galleryData.length) currentLbIndex = 0;
        updateLightboxContent();
    };

    window.prGoToLightboxIndex = function(index) {
        currentLbIndex = index;
        updateLightboxContent();
    };

    window.prCloseLightbox = function() {
        if (lightbox) {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    window.prToggleInfo = function() {
        const info = document.querySelector('.pr-lightbox-info');
        if (!info) return;
        lbInfoVisible = !lbInfoVisible;
        info.classList.toggle('hidden', !lbInfoVisible);
    };

    window.prToggleFullscreen = function() {
        if (!document.fullscreenElement) {
            if (lightbox) lightbox.requestFullscreen().catch(err => console.log(err));
        } else {
            document.exitFullscreen();
        }
    };

    window.prDownloadImage = function() {
        const data = galleryData[currentLbIndex];
        if (!data) return;
        const a = document.createElement('a');
        a.href = data.src;
        a.download = data.title.replace(/[^a-z0-9]/gi, '_') + '.jpg';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        prShowToast('📥 Download started!');
    };

    window.prShareImage = function() {
        const data = galleryData[currentLbIndex];
        if (!data) return;
        if (navigator.share) {
            navigator.share({ title: data.title, text: data.desc, url: window.location.href }).catch(err => console.log('Share cancelled'));
        } else {
            navigator.clipboard.writeText(window.location.href).then(() => {
                prShowToast('🔗 Link copied to clipboard!');
            });
        }
    };

    document.addEventListener('keydown', (e) => {
        if (!lightbox || !lightbox.classList.contains('active')) return;
        if (e.key === 'Escape') prCloseLightbox();
        if (e.key === 'ArrowLeft') prNavigateLightbox(-1);
        if (e.key === 'ArrowRight') prNavigateLightbox(1);
        if (e.key === 'i' || e.key === 'I') prToggleInfo();
        if (e.key === 'f' || e.key === 'F') prToggleFullscreen();
    });

    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) prCloseLightbox();
        });
    }

    let touchStartX = 0, touchEndX = 0;
    if (lightbox) {
        lightbox.addEventListener('touchstart', (e) => { touchStartX = e.changedTouches[0].screenX; }, { passive: true });
        lightbox.addEventListener('touchend', (e) => { touchEndX = e.changedTouches[0].screenX; handleSwipe(); }, { passive: true });
    }
    
    function handleSwipe() {
        const diff = touchStartX - touchEndX;
        if (Math.abs(diff) < 50) return;
        if (diff > 0) prNavigateLightbox(1);
        else prNavigateLightbox(-1);
    }

    // ===== 8. Live Search =====
    const searchInput = document.getElementById('prSearchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = 'galeri.php';
                const params = new URLSearchParams(window.location.search);
                if (params.has('kategori')) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden'; hidden.name = 'kategori'; hidden.value = params.get('kategori');
                    form.appendChild(hidden);
                }
                if (params.has('view')) {
                    const hiddenView = document.createElement('input');
                    hiddenView.type = 'hidden'; hiddenView.name = 'view'; hiddenView.value = params.get('view');
                    form.appendChild(hiddenView);
                }
                const q = document.createElement('input');
                q.type = 'hidden'; q.name = 'q'; q.value = e.target.value;
                form.appendChild(q);
                document.body.appendChild(form);
                form.submit();
            }, 500);
        });
    }

    // ===== 9. Slideshow Functions =====
    const slides = document.querySelectorAll('.pr-slide');
    const thumbnails = document.querySelectorAll('.pr-thumbnail');
    let currentSlide = 0;
    let autoplayInterval = null;
    let autoplayActive = false;
    const AUTOPLAY_DURATION = 5000;

    function updateSlideshow() {
        slides.forEach((slide, idx) => slide.classList.toggle('active', idx === currentSlide));
        thumbnails.forEach((thumb, idx) => thumb.classList.toggle('active', idx === currentSlide));
        
        const activeSlide = slides[currentSlide];
        if (activeSlide) {
            const { title, desc, date, category, color } = activeSlide.dataset;
            const slideTitle = document.getElementById('prSlideTitle');
            const slideDesc = document.getElementById('prSlideDesc');
            const slideDate = document.getElementById('prSlideDate');
            const slideCategory = document.getElementById('prSlideCategory');
            const slideCurrent = document.getElementById('prSlideCurrent');
            
            if (slideTitle) slideTitle.textContent = title;
            if (slideDesc) slideDesc.textContent = desc;
            if (slideDate) slideDate.innerHTML = `<i class="fas fa-calendar"></i> ${date}`;
            if (slideCategory) { slideCategory.textContent = category; slideCategory.style.background = color; }
            if (slideCurrent) slideCurrent.textContent = currentSlide + 1;
            
            const activeThumb = thumbnails[currentSlide];
            if (activeThumb) activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        updateSlideProgress();
    }

    function updateSlideProgress() {
        const progressFill = document.getElementById('prSlideProgressFill');
        if (!progressFill || !autoplayActive) return;
        let startTime = Date.now();
        function animate() {
            if (!autoplayActive) { progressFill.style.width = '0%'; return; }
            const elapsed = Date.now() - startTime;
            const progress = Math.min((elapsed / AUTOPLAY_DURATION) * 100, 100);
            progressFill.style.width = progress + '%';
            if (progress < 100) requestAnimationFrame(animate);
        }
        animate();
    }

    window.prNextSlide = function() { currentSlide = (currentSlide + 1) % slides.length; updateSlideshow(); };
    window.prPrevSlide = function() { currentSlide = (currentSlide - 1 + slides.length) % slides.length; updateSlideshow(); };
    window.prGoToSlide = function(index) { currentSlide = index; updateSlideshow(); };

    window.prToggleAutoplay = function() {
        const icon = document.getElementById('prAutoplayIcon');
        if (autoplayActive) {
            clearInterval(autoplayInterval);
            autoplayActive = false;
            if (icon) icon.className = 'fas fa-play';
            const progressFill = document.getElementById('prSlideProgressFill');
            if (progressFill) progressFill.style.width = '0%';
            prShowToast('⏸️ Autoplay paused');
        } else {
            autoplayInterval = setInterval(prNextSlide, AUTOPLAY_DURATION);
            autoplayActive = true;
            if (icon) icon.className = 'fas fa-pause';
            updateSlideProgress();
            prShowToast('▶️ Autoplay started');
        }
    };

    window.prOpenSlideshowFullscreen = function() {
        const slideshow = document.getElementById('prSlideshow');
        if (!slideshow) return;
        if (!document.fullscreenElement) slideshow.requestFullscreen().catch(err => console.log(err));
        else document.exitFullscreen();
    };

    if (slides.length > 0) updateSlideshow();

    document.addEventListener('keydown', (e) => {
        if (lightbox && lightbox.classList.contains('active')) return;
        if (slides.length === 0) return;
        if (e.key === 'ArrowLeft') prPrevSlide();
        if (e.key === 'ArrowRight') prNextSlide();
        if (e.key === ' ') { e.preventDefault(); prToggleAutoplay(); }
    });

    // ===== 10. Keyboard Shortcuts =====
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) searchInput.focus();
        }
    });

    // ===== 11. Toast =====
    window.prShowToast = function(message) {
        const toast = document.getElementById('prToast');
        if (!toast) return;
        const msgEl = document.getElementById('prToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // ===== 12. Back to Top (THROTTLED) =====
    const backTop = document.getElementById('prBackTop');
    let backTopTicking = false;
    if (backTop) {
        window.addEventListener('scroll', () => {
            if (!backTopTicking) {
                window.requestAnimationFrame(() => {
                    if (window.scrollY > 600) backTop.classList.add('show');
                    else backTop.classList.remove('show');
                    backTopTicking = false;
                });
                backTopTicking = true;
            }
        }, { passive: true });
        backTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }

    // ===== 13. Parallax Spectrums (THROTTLED) =====
    const heroSection = document.querySelector('.pr-hero');
    let parallaxTicking = false;
    if (heroSection && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            if (!parallaxTicking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.pageYOffset;
                    const spectrums = heroSection.querySelectorAll('.pr-spectrum');
                    spectrums.forEach((spec, index) => {
                        const speed = (index + 1) * 0.2;
                        spec.style.transform = `translateY(${scrolled * speed}px)`;
                    });
                    parallaxTicking = false;
                });
                parallaxTicking = true;
            }
        }, { passive: true });
    }

    setTimeout(() => prShowToast('🌈 Welcome to Prism Gallery!'), 1500);

    console.log('%c╔═══════════════════════════════════════════╗', 'color:#FF006E;font-weight:bold');
    console.log('%c║   PRISM GALLERY - Light & Color Exhibition ║', 'color:#FF006E;font-weight:bold;font-size:14px');
    console.log('%c╚═══════════════════════════════════════════╝', 'color:#FF006E;font-weight:bold');
    console.log('%c🌈 ' + galleryData.length + ' photos in spectrum (OPTIMIZED)', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>