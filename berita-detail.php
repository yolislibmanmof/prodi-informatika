<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$slug = clean($_GET['slug'] ?? '');
$berita = getBeritaBySlug($slug);

if (!$berita) {
    header('Location: index.php');
    exit;
}

updateViews($berita['id']);

/* ===== Data tambahan - LOGIC ASLI DIPERTAHANKAN ===== */
$related = getRelatedNews($berita['id'], $berita['kategori'], 3);

// Estimasi waktu baca (rata-rata 200 kata/menit)
$wordCount = str_word_count(strip_tags($berita['konten']));
$readingTime = max(1, ceil($wordCount / 200));

// Prev & Next article
$db = getDB();
$prev = $db->prepare("SELECT judul, slug FROM berita WHERE id < ? AND status='published' ORDER BY id DESC LIMIT 1");
$prev->execute([$berita['id']]);
$prevArticle = $prev->fetch();

$next = $db->prepare("SELECT judul, slug FROM berita WHERE id > ? AND status='published' ORDER BY id ASC LIMIT 1");
$next->execute([$berita['id']]);
$nextArticle = $next->fetch();

// Author info
$authorInfo = null;
if (!empty($berita['author_id'])) {
    $st = $db->prepare("SELECT nama_lengkap, email, foto FROM admins WHERE id = ?");
    $st->execute([$berita['author_id']]);
    $authorInfo = $st->fetch();
}

// Featured image dengan fallback
$featuredImg = function_exists('getBeritaImage')
    ? getBeritaImage($berita['gambar'], $berita['kategori'])
    : ($berita['gambar'] ? 'uploads/' . $berita['gambar'] : 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=1200');

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// Citation data
$citationYear = date('Y', strtotime($berita['published_at']));
$citationAuthor = $authorInfo['nama_lengkap'] ?? 'Admin Informatika Pro';
$citationTitle = $berita['judul'];
?>

<!-- ===== READING PROGRESS BAR ===== -->
<div class="dj-progress-bar" id="djProgressBar">
    <div class="dj-progress-fill" id="djProgressFill"></div>
    <div class="dj-progress-info">
        <span id="djProgressPercent">0%</span>
        <span class="dj-progress-sep">•</span>
        <span id="djProgressTime">0 min read</span>
    </div>
</div>

<!-- ===== FLOATING READING STATS ===== -->
<div class="dj-reading-stats" id="djReadingStats">
    <div class="dj-stat-item">
        <i class="fas fa-book-reader"></i>
        <span id="djWordsRead">0</span>
        <small>words</small>
    </div>
    <div class="dj-stat-item">
        <i class="fas fa-clock"></i>
        <span id="djTimeSpent">0:00</span>
        <small>time</small>
    </div>
    <div class="dj-stat-item dj-stat-wpm">
        <i class="fas fa-tachometer-alt"></i>
        <span id="djWPM">0</span>
        <small>wpm</small>
    </div>
</div>

<!-- ===== ARTICLE WRAPPER ===== -->
<div class="dj-wrapper">

    <!-- ===== HERO ===== -->
    <header class="dj-hero">
        <div class="container">
            <!-- Journal Header -->
            <div class="dj-journal-header fade-up">
                <div class="dj-journal-badge">
                    <span class="dj-badge-vol">Vol. <?= date('Y', strtotime($berita['published_at'])) ?></span>
                    <span class="dj-badge-sep">|</span>
                    <span class="dj-badge-issue">Issue <?= date('n', strtotime($berita['published_at'])) ?></span>
                    <span class="dj-badge-sep">|</span>
                    <span class="dj-badge-cat"><?= clean($berita['kategori']) ?></span>
                </div>
                <div class="dj-journal-meta-top">
                    <span><i class="fas fa-fingerprint"></i> DOI: 10.informatika.<?= $berita['id'] ?>.<?= $citationYear ?></span>
                    <span><i class="fas fa-qrcode"></i> ISSN: 2024-<?= str_pad($berita['id'], 4, '0', STR_PAD_LEFT) ?></span>
                </div>
            </div>

            <!-- Breadcrumb -->
            <nav class="dj-breadcrumb fade-up">
                <a href="index.php"><i class="fas fa-home"></i> Beranda</a>
                <i class="fas fa-chevron-right"></i>
                <a href="berita.php">Arsip Berita</a>
                <i class="fas fa-chevron-right"></i>
                <span><?= clean($berita['kategori']) ?></span>
            </nav>

            <!-- Title -->
            <h1 class="dj-title fade-up"><?= clean($berita['judul']) ?></h1>

            <!-- Author & Meta -->
            <div class="dj-author-line fade-up">
                <div class="dj-author-avatar">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($authorInfo['nama_lengkap'] ?? 'Admin') ?>&background=10B981&color=fff&size=80&bold=true" alt="Author">
                    <span class="dj-author-verified" title="Verified Author"><i class="fas fa-check"></i></span>
                </div>
                <div class="dj-author-info">
                    <strong><?= clean($authorInfo['nama_lengkap'] ?? 'Admin Informatika Pro') ?></strong>
                    <span>Program Studi Informatika</span>
                </div>
                <div class="dj-meta-pills">
                    <span class="dj-pill"><i class="far fa-calendar"></i> <?= formatTanggal($berita['published_at']) ?></span>
                    <span class="dj-pill"><i class="far fa-clock"></i> <?= $readingTime ?> min</span>
                    <span class="dj-pill"><i class="fas fa-file-word"></i> <?= number_format($wordCount) ?> words</span>
                    <span class="dj-pill"><i class="far fa-eye"></i> <?= number_format($berita['views']) ?></span>
                </div>
            </div>

            <!-- Action Toolbar -->
            <div class="dj-toolbar fade-up">
                <div class="dj-tool-group">
                    <button class="dj-tool-btn" id="djFontDown" title="Perkecil font">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="dj-tool-label">Aa</span>
                    <button class="dj-tool-btn" id="djFontUp" title="Perbesar font">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                
                <div class="dj-tool-divider"></div>
                
                <button class="dj-tool-btn" id="djFocusMode" title="Mode Fokus">
                    <i class="fas fa-compress-alt"></i>
                    <span>Focus</span>
                </button>
                
                <button class="dj-tool-btn" id="djDarkMode" title="Mode gelap">
                    <i class="fas fa-moon"></i>
                </button>
                
                <button class="dj-tool-btn" id="djPrint" title="Cetak artikel">
                    <i class="fas fa-print"></i>
                </button>
                
                <button class="dj-tool-btn" id="djBookmark" title="Simpan artikel">
                    <i class="far fa-bookmark"></i>
                </button>
                
                <div class="dj-tool-divider"></div>
                
                <button class="dj-tool-btn dj-tool-citation" id="djCitationBtn" title="Generate Citation">
                    <i class="fas fa-quote-right"></i>
                    <span>Cite</span>
                </button>
            </div>
        </div>

        <!-- Featured Image -->
        <?php if ($featuredImg): ?>
        <div class="dj-featured-image fade-up">
            <img src="<?= $featuredImg ?>" alt="<?= clean($berita['judul']) ?>" id="djMainImage">
            <div class="dj-image-overlay">
                <div class="dj-image-caption">
                    <i class="fas fa-expand"></i>
                    <span>Klik untuk memperbesar</span>
                </div>
                <div class="dj-image-credit">
                    <i class="fas fa-camera"></i>
                    <span>Informatika Pro Documentation</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </header>

    <!-- ===== ARTICLE LAYOUT ===== -->
    <div class="container dj-layout">

        <!-- LEFT: Sticky TOC & Tools -->
        <aside class="dj-sidebar" id="djSidebar">
            <!-- Reading Progress Circle -->
            <div class="dj-progress-circle-wrap">
                <svg class="dj-progress-circle" viewBox="0 0 100 100">
                    <circle class="dj-circle-bg" cx="50" cy="50" r="45"></circle>
                    <circle class="dj-circle-fill" id="djCircleFill" cx="50" cy="50" r="45"></circle>
                </svg>
                <div class="dj-circle-text">
                    <span id="djCirclePercent">0%</span>
                    <small>read</small>
                </div>
            </div>

            <!-- TOC -->
            <div class="dj-toc-card">
                <div class="dj-toc-header">
                    <i class="fas fa-list-ul"></i>
                    <h3>Contents</h3>
                </div>
                <nav class="dj-toc-nav" id="djTocNav">
                    <p class="dj-toc-empty">Auto-generated from headings</p>
                </nav>
            </div>

            <!-- Tools Card -->
            <div class="dj-tools-card">
                <h4><i class="fas fa-tools"></i> Reading Tools</h4>
                
                <button class="dj-sidebar-btn" id="djHighlightBtn">
                    <i class="fas fa-highlighter" style="color: #F59E0B;"></i>
                    <span>Highlight Mode</span>
                    <span class="dj-btn-badge" id="djHighlightCount">0</span>
                </button>
                
                <button class="dj-sidebar-btn" id="djNotesBtn">
                    <i class="fas fa-sticky-note" style="color: #8B5CF6;"></i>
                    <span>My Notes</span>
                    <span class="dj-btn-badge" id="djNotesCount">0</span>
                </button>
                
                <button class="dj-sidebar-btn" id="djReadAloud">
                    <i class="fas fa-volume-up" style="color: #10B981;"></i>
                    <span>Read Aloud</span>
                </button>
            </div>

            <!-- Share Mini -->
            <div class="dj-share-mini">
                <span class="dj-share-label">Share</span>
                <div class="dj-share-icons">
                    <a href="https://wa.me/?text=<?= urlencode($berita['judul'] . ' - ' . $currentUrl) ?>" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($currentUrl) ?>" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode($berita['judul']) ?>&url=<?= urlencode($currentUrl) ?>" target="_blank" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($currentUrl) ?>" target="_blank" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <button class="dj-copy-link" data-url="<?= $currentUrl ?>" title="Copy Link"><i class="fas fa-link"></i></button>
                </div>
            </div>
        </aside>

        <!-- MAIN: Article Content -->
        <article class="dj-content" id="djContent">

            <!-- Abstract/Lead -->
            <?php if (!empty($berita['ringkasan'])): ?>
            <div class="dj-abstract fade-up">
                <div class="dj-abstract-label">
                    <i class="fas fa-file-alt"></i>
                    <span>ABSTRACT</span>
                </div>
                <p class="dj-abstract-text"><?= clean($berita['ringkasan']) ?></p>
                <div class="dj-abstract-meta">
                    <span><strong>Keywords:</strong> <?= clean($berita['kategori']) ?>, Informatika, Teknologi, Pendidikan</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Content Body -->
            <div class="dj-body fade-up" id="djBody">
                <?= $berita['konten'] ?>
            </div>

            <!-- References/Tags -->
            <div class="dj-references fade-up">
                <h4><i class="fas fa-tags"></i> References & Tags</h4>
                <div class="dj-tags">
                    <?php
                    $tags = array_filter([
                        $berita['kategori'],
                        'Informatika',
                        'Teknologi',
                        'Pendidikan',
                        'Kampus',
                        'Digital',
                        'Innovation'
                    ]);
                    foreach ($tags as $tag):
                    ?>
                    <span class="dj-tag">#<?= clean($tag) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Author Card -->
            <div class="dj-author-card fade-up">
                <div class="dj-author-card-header">
                    <span class="dj-author-label">About the Author</span>
                </div>
                <div class="dj-author-card-body">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($authorInfo['nama_lengkap'] ?? 'Admin') ?>&background=10B981&color=fff&size=160&bold=true" alt="Author">
                    <div class="dj-author-details">
                        <h3><?= clean($authorInfo['nama_lengkap'] ?? 'Admin Informatika Pro') ?></h3>
                        <p class="dj-author-role">Researcher & Content Creator</p>
                        <p class="dj-author-bio">Kontributor aktif di Program Studi Informatika yang berfokus pada penulisan artikel seputar perkembangan teknologi, penelitian akademis, dan kegiatan kampus.</p>
                        <?php if (!empty($authorInfo['email'])): ?>
                        <a href="mailto:<?= clean($authorInfo['email']) ?>" class="dj-author-contact">
                            <i class="fas fa-envelope"></i>
                            <span><?= clean($authorInfo['email']) ?></span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Citation Modal Trigger -->
            <div class="dj-citation-section fade-up">
                <h3><i class="fas fa-quote-right"></i> Cite this Article</h3>
                <p>Gunakan artikel ini sebagai referensi dalam karya ilmiah Anda</p>
                <div class="dj-citation-buttons">
                    <button class="dj-cite-btn" data-format="apa">APA</button>
                    <button class="dj-cite-btn" data-format="mla">MLA</button>
                    <button class="dj-cite-btn" data-format="chicago">Chicago</button>
                    <button class="dj-cite-btn" data-format="bibtex">BibTeX</button>
                </div>
            </div>

            <!-- Share Section -->
            <div class="dj-share-section fade-up">
                <div class="dj-share-content">
                    <h3>💚 Share this Knowledge</h3>
                    <p>Bantu sebarkan ilmu ini kepada rekan-rekan Anda</p>
                    <div class="dj-share-grid">
                        <a href="https://wa.me/?text=<?= urlencode($berita['judul'] . ' - ' . $currentUrl) ?>" target="_blank" class="dj-share-btn whatsapp">
                            <i class="fab fa-whatsapp"></i>
                            <span>WhatsApp</span>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($currentUrl) ?>" target="_blank" class="dj-share-btn facebook">
                            <i class="fab fa-facebook-f"></i>
                            <span>Facebook</span>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?= urlencode($berita['judul']) ?>&url=<?= urlencode($currentUrl) ?>" target="_blank" class="dj-share-btn twitter">
                            <i class="fab fa-twitter"></i>
                            <span>Twitter</span>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($currentUrl) ?>" target="_blank" class="dj-share-btn linkedin">
                            <i class="fab fa-linkedin-in"></i>
                            <span>LinkedIn</span>
                        </a>
                        <a href="mailto:?subject=<?= urlencode($berita['judul']) ?>&body=<?= urlencode('Baca artikel menarik ini: ' . $currentUrl) ?>" class="dj-share-btn email">
                            <i class="fas fa-envelope"></i>
                            <span>Email</span>
                        </a>
                        <button class="dj-share-btn copy" data-url="<?= $currentUrl ?>">
                            <i class="fas fa-link"></i>
                            <span>Copy Link</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Prev/Next Navigation -->
            <div class="dj-nav-articles fade-up">
                <?php if ($prevArticle): ?>
                <a href="berita-detail.php?slug=<?= clean($prevArticle['slug']) ?>" class="dj-nav-article prev">
                    <div class="dj-nav-label">
                        <i class="fas fa-arrow-left"></i>
                        <span>Previous Article</span>
                    </div>
                    <div class="dj-nav-title"><?= clean($prevArticle['judul']) ?></div>
                </a>
                <?php else: ?>
                <div class="dj-nav-spacer"></div>
                <?php endif; ?>

                <?php if ($nextArticle): ?>
                <a href="berita-detail.php?slug=<?= clean($nextArticle['slug']) ?>" class="dj-nav-article next">
                    <div class="dj-nav-label">
                        <span>Next Article</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="dj-nav-title"><?= clean($nextArticle['judul']) ?></div>
                </a>
                <?php endif; ?>
            </div>

        </article>
    </div>

    <!-- ===== RELATED NEWS ===== -->
    <?php if (!empty($related)): ?>
    <section class="dj-related">
        <div class="container">
            <div class="dj-related-header fade-up">
                <div class="dj-related-title">
                    <span class="dj-related-label">Related Articles</span>
                    <h2>Baca <span class="accent">Selanjutnya</span></h2>
                </div>
                <a href="berita.php" class="dj-view-all">
                    <span>View Archive</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="dj-related-grid">
                <?php foreach ($related as $r):
                    $imgSrc = function_exists('getBeritaImage')
                        ? getBeritaImage($r['gambar'], $r['kategori'])
                        : ($r['gambar'] ? 'uploads/' . $r['gambar'] : 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=600');
                    $relWordCount = str_word_count(strip_tags($r['ringkasan'] ?? ''));
                    $relReadTime = max(1, ceil($relWordCount / 200));
                ?>
                <article class="dj-related-card fade-up">
                    <a href="berita-detail.php?slug=<?= clean($r['slug']) ?>" class="dj-related-link"></a>
                    <div class="dj-related-image">
                        <img src="<?= $imgSrc ?>" alt="<?= clean($r['judul']) ?>">
                        <span class="dj-related-category"><?= clean($r['kategori']) ?></span>
                        <span class="dj-related-readtime">
                            <i class="far fa-clock"></i> <?= $relReadTime ?> min
                        </span>
                    </div>
                    <div class="dj-related-content">
                        <div class="dj-related-meta">
                            <span><i class="far fa-calendar"></i> <?= formatTanggal($r['published_at'] ?? $r['created_at']) ?></span>
                            <span><i class="far fa-eye"></i> <?= number_format($r['views']) ?></span>
                        </div>
                        <h3><?= clean($r['judul']) ?></h3>
                        <p><?= clean(mb_substr($r['ringkasan'] ?? '', 0, 120)) ?>...</p>
                        <span class="dj-related-more">
                            Read Article <i class="fas fa-arrow-right"></i>
                        </span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>

<!-- ===== CITATION MODAL ===== -->
<div class="dj-modal" id="djCitationModal">
    <div class="dj-modal-content">
        <button class="dj-modal-close" id="djCitationClose"><i class="fas fa-times"></i></button>
        <div class="dj-modal-header">
            <h3><i class="fas fa-quote-right"></i> Citation Generator</h3>
            <p>Pilih format sitasi yang Anda butuhkan</p>
        </div>
        <div class="dj-modal-body">
            <div class="dj-citation-format" id="djCitationContent">
                <!-- Filled by JS -->
            </div>
            <button class="dj-copy-citation" id="djCopyCitation">
                <i class="fas fa-copy"></i>
                <span>Copy to Clipboard</span>
            </button>
        </div>
    </div>
</div>

<!-- ===== NOTES MODAL ===== -->
<div class="dj-modal" id="djNotesModal">
    <div class="dj-modal-content dj-modal-large">
        <button class="dj-modal-close" id="djNotesClose"><i class="fas fa-times"></i></button>
        <div class="dj-modal-header">
            <h3><i class="fas fa-sticky-note"></i> My Reading Notes</h3>
            <p>Catatan pribadi Anda untuk artikel ini</p>
        </div>
        <div class="dj-modal-body">
            <textarea class="dj-notes-textarea" id="djNotesTextarea" placeholder="Tulis catatan Anda di sini..."></textarea>
            <div class="dj-notes-actions">
                <button class="dj-btn-secondary" id="djClearNotes">
                    <i class="fas fa-trash"></i> Clear
                </button>
                <button class="dj-btn-primary" id="djSaveNotes">
                    <i class="fas fa-save"></i> Save Notes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== LIGHTBOX ===== -->
<div class="dj-lightbox" id="djLightbox">
    <button class="dj-lightbox-close" id="djLightboxClose"><i class="fas fa-times"></i></button>
    <img src="" alt="" id="djLightboxImg">
</div>

<!-- ===== TOAST ===== -->
<div class="dj-toast" id="djToast">
    <i class="fas fa-check-circle"></i>
    <span id="djToastMsg">Success!</span>
</div>

<!-- ===== BACK TO TOP ===== -->
<button class="dj-back-to-top" id="djBackToTop" title="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- ===== DATA FOR JS ===== -->
<script id="djArticleData" type="application/json">
<?= json_encode([
    'title' => $citationTitle,
    'author' => $citationAuthor,
    'year' => $citationYear,
    'url' => $currentUrl,
    'category' => $berita['kategori'],
    'slug' => $berita['slug'],
    'published' => $berita['published_at'],
    'wordCount' => $wordCount
], JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>
</script>

<style>
:root {
    --dj-content-width: 720px;
    --dj-sidebar-width: 280px;
    --dj-font-scale: 1;
    --dj-primary: #10B981;
    --dj-primary-dark: #047857;
    --dj-primary-light: #D1FAE5;
    --dj-accent: #F59E0B;
    --dj-accent-2: #8B5CF6;
    --dj-dark: #0F172A;
    --dj-gray-50: #F8FAFC;
    --dj-gray-100: #F1F5F9;
    --dj-gray-200: #E2E8F0;
    --dj-gray-600: #475569;
    --dj-gray-700: #374151;
    --dj-shadow-sm: 0 2px 8px rgba(15,23,42,.06);
    --dj-shadow-md: 0 8px 24px rgba(15,23,42,.1);
    --dj-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
    --dj-font-serif: 'Lora', 'Georgia', serif;
    --dj-font-sans: 'Inter', system-ui, sans-serif;
    --dj-font-mono: 'JetBrains Mono', 'Courier New', monospace;
}

body.dj-dark-mode {
    --dj-dark: #F1F5F9;
    --dj-gray-50: #0B1120;
    --dj-gray-100: rgba(255,255,255,.08);
    --dj-gray-200: rgba(255,255,255,.1);
    --dj-gray-600: #94A3B8;
    --dj-gray-700: #CBD5E1;
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--dj-gray-50); }
::-webkit-scrollbar-thumb { 
    background: linear-gradient(180deg, var(--dj-primary), var(--dj-accent-2)); 
    border-radius: 5px; 
}

/* ===== PROGRESS BAR ===== */
.dj-progress-bar {
    position: fixed; top: 0; left: 0; right: 0; 
    height: 4px; background: rgba(16, 185, 129, 0.1); 
    z-index: 999; transform: translateY(-100%); 
    transition: transform .3s;
}
.dj-progress-bar.active { transform: none; }
.dj-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--dj-primary), var(--dj-accent-2), var(--dj-accent));
    background-size: 200% 100%;
    animation: djGradientMove 3s ease infinite;
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
    transition: width .1s ease-out;
}
@keyframes djGradientMove {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}
.dj-progress-info {
    position: absolute; top: 8px; right: 20px;
    background: rgba(15,23,42,0.9); backdrop-filter: blur(10px);
    color: #fff; padding: 6px 14px; border-radius: 50px;
    font-size: 0.75rem; font-family: var(--dj-font-mono);
    display: flex; align-items: center; gap: 8px;
    opacity: 0; transition: opacity 0.3s;
}
.dj-progress-bar.active .dj-progress-info { opacity: 1; }
.dj-progress-sep { opacity: 0.5; }

/* ===== READING STATS FLOATING ===== */
.dj-reading-stats {
    position: fixed; left: 20px; top: 50%;
    transform: translateY(-50%);
    display: flex; flex-direction: column; gap: 12px;
    z-index: 900; opacity: 0; transition: opacity 0.3s;
}
body.dj-scrolled .dj-reading-stats { opacity: 1; }
.dj-stat-item {
    background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);
    border: 1px solid var(--dj-gray-200); border-radius: 12px;
    padding: 10px 14px; display: flex; align-items: center; gap: 10px;
    box-shadow: var(--dj-shadow-sm); transition: all 0.3s;
    min-width: 110px;
}
body.dj-dark-mode .dj-stat-item { 
    background: rgba(30,41,59,0.95); 
    border-color: rgba(255,255,255,.1); 
}
.dj-stat-item:hover { transform: translateX(4px); }
.dj-stat-item i { 
    color: var(--dj-primary); font-size: 1rem;
    width: 24px; text-align: center;
}
.dj-stat-item span { 
    font-family: var(--dj-font-mono); font-weight: 700;
    font-size: 1rem; color: var(--dj-dark);
}
body.dj-dark-mode .dj-stat-item span { color: #F1F5F9; }
.dj-stat-item small { 
    font-size: 0.65rem; color: var(--dj-gray-600);
    text-transform: uppercase; letter-spacing: 0.5px;
    display: block; margin-top: -2px;
}
.dj-stat-wpm i { color: var(--dj-accent); }

/* ===== HERO ===== */
.dj-hero { 
    padding: 80px 20px 40px; text-align: center;
    position: relative;
}

.dj-journal-header { margin-bottom: 32px; }
.dj-journal-badge {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 8px 20px; background: var(--dj-primary-light);
    color: var(--dj-primary-dark); border-radius: 50px;
    font-family: var(--dj-font-mono); font-size: 0.8rem;
    font-weight: 700; letter-spacing: 1px; margin-bottom: 12px;
}
body.dj-dark-mode .dj-journal-badge { 
    background: rgba(16,185,129,0.15); color: #34D399; 
}
.dj-badge-sep { opacity: 0.3; }

.dj-journal-meta-top {
    display: flex; justify-content: center; gap: 24px;
    font-size: 0.78rem; color: var(--dj-gray-600);
    font-family: var(--dj-font-mono);
}
.dj-journal-meta-top span {
    display: inline-flex; align-items: center; gap: 6px;
}
.dj-journal-meta-top i { color: var(--dj-primary); font-size: 0.7rem; }

.dj-breadcrumb {
    display: flex; align-items: center; justify-content: center; 
    gap: 10px; font-size: .85rem; margin-bottom: 32px; 
    flex-wrap: wrap;
}
.dj-breadcrumb a { 
    color: var(--dj-gray-600); transition: color .2s; 
    text-decoration: none;
}
.dj-breadcrumb a:hover { color: var(--dj-primary); }
.dj-breadcrumb i { font-size: .6rem; color: var(--dj-gray-200); }
.dj-breadcrumb span:last-child { 
    color: var(--dj-primary); font-weight: 600; 
}

.dj-title {
    font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 800;
    line-height: 1.2; margin-bottom: 32px; letter-spacing: -0.03em;
    max-width: 900px; margin-left: auto; margin-right: auto;
    font-family: var(--dj-font-serif); color: var(--dj-dark);
}
body.dj-dark-mode .dj-title { color: #F1F5F9; }

/* Author Line */
.dj-author-line {
    display: flex; align-items: center; justify-content: center;
    gap: 20px; flex-wrap: wrap; margin-bottom: 32px;
    padding: 20px; background: var(--dj-gray-50);
    border-radius: 20px; max-width: 800px; margin-left: auto; margin-right: auto;
}
body.dj-dark-mode .dj-author-line { background: rgba(255,255,255,.03); }

.dj-author-avatar { 
    position: relative; width: 56px; height: 56px; flex-shrink: 0;
}
.dj-author-avatar img { 
    width: 100%; height: 100%; border-radius: 50%; 
    border: 3px solid var(--dj-primary-light); 
}
.dj-author-verified {
    position: absolute; bottom: -2px; right: -2px;
    width: 20px; height: 20px; border-radius: 50%;
    background: var(--dj-primary); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.6rem; border: 2px solid #fff;
}

.dj-author-info { text-align: left; }
.dj-author-info strong { 
    display: block; font-size: 1rem; color: var(--dj-dark); 
    font-family: var(--dj-font-sans);
}
body.dj-dark-mode .dj-author-info strong { color: #F1F5F9; }
.dj-author-info span { 
    font-size: 0.82rem; color: var(--dj-gray-600); 
}

.dj-meta-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.dj-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 12px; background: #fff; border: 1px solid var(--dj-gray-200);
    border-radius: 50px; font-size: 0.78rem; color: var(--dj-gray-700);
    font-weight: 500;
}
body.dj-dark-mode .dj-pill { 
    background: rgba(255,255,255,.05); 
    border-color: rgba(255,255,255,.1); 
    color: #CBD5E1; 
}
.dj-pill i { color: var(--dj-primary); font-size: 0.75rem; }

/* Toolbar */
.dj-toolbar {
    display: inline-flex; gap: 8px; padding: 8px 12px;
    background: #fff; border: 1px solid var(--dj-gray-200);
    border-radius: 50px; box-shadow: var(--dj-shadow-sm);
    align-items: center; flex-wrap: wrap; justify-content: center;
}
body.dj-dark-mode .dj-toolbar { 
    background: #1E293B; border-color: rgba(255,255,255,.1); 
}

.dj-tool-group { 
    display: flex; align-items: center; gap: 4px; 
}
.dj-tool-label {
    font-family: var(--dj-font-serif); font-weight: 700;
    font-size: 0.9rem; color: var(--dj-dark); padding: 0 8px;
}
body.dj-dark-mode .dj-tool-label { color: #F1F5F9; }

.dj-tool-divider { 
    width: 1px; height: 24px; 
    background: var(--dj-gray-200); margin: 0 4px;
}

.dj-tool-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 12px; border: none; background: transparent;
    border-radius: 50px; cursor: pointer; color: var(--dj-gray-700);
    font-size: 0.85rem; font-weight: 600; transition: all .25s;
    font-family: inherit;
}
.dj-tool-btn i { font-size: 0.9rem; }
.dj-tool-btn:hover { 
    background: var(--dj-primary-light); color: var(--dj-primary-dark); 
}
.dj-tool-btn.active { 
    background: var(--dj-primary); color: #fff; 
}
.dj-tool-citation {
    background: linear-gradient(135deg, var(--dj-primary), var(--dj-primary-dark));
    color: #fff !important;
}
.dj-tool-citation:hover {
    background: linear-gradient(135deg, var(--dj-primary-dark), var(--dj-primary));
    transform: translateY(-1px);
}

/* Featured Image */
.dj-featured-image {
    margin-top: 48px; border-radius: 24px; overflow: hidden;
    box-shadow: var(--dj-shadow-lg); max-width: 1100px; 
    margin-left: auto; margin-right: auto;
    position: relative; cursor: zoom-in;
}
.dj-featured-image img { 
    width: 100%; display: block; transition: transform .6s; 
}
.dj-featured-image:hover img { transform: scale(1.03); }

.dj-image-overlay {
    position: absolute; inset: 0; 
    background: linear-gradient(180deg, transparent 60%, rgba(0,0,0,0.6) 100%);
    display: flex; justify-content: space-between; align-items: flex-end;
    padding: 24px; opacity: 0; transition: opacity 0.3s;
}
.dj-featured-image:hover .dj-image-overlay { opacity: 1; }

.dj-image-caption, .dj-image-credit {
    background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);
    color: var(--dj-dark); padding: 10px 16px; border-radius: 50px;
    font-size: 0.8rem; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
}
.dj-image-caption i, .dj-image-credit i { color: var(--dj-primary); }

/* ===== LAYOUT ===== */
.dj-layout {
    display: grid; grid-template-columns: var(--dj-sidebar-width) 1fr;
    gap: 60px; padding: 60px 20px; max-width: 1200px; 
    margin: 0 auto; align-items: flex-start;
}

/* ===== SIDEBAR ===== */
.dj-sidebar {
    position: sticky; top: 100px;
    display: flex; flex-direction: column; gap: 20px;
}

/* Progress Circle */
.dj-progress-circle-wrap {
    position: relative; width: 100px; height: 100px;
    margin: 0 auto 10px;
}
.dj-progress-circle { width: 100%; height: 100%; transform: rotate(-90deg); }
.dj-circle-bg { 
    fill: none; stroke: var(--dj-gray-200); stroke-width: 6; 
}
body.dj-dark-mode .dj-circle-bg { stroke: rgba(255,255,255,.1); }
.dj-circle-fill {
    fill: none; stroke: url(#djGradient); stroke-width: 6;
    stroke-linecap: round; stroke-dasharray: 283; stroke-dashoffset: 283;
    transition: stroke-dashoffset 0.3s ease-out;
}
.dj-progress-circle {
    background: conic-gradient(var(--dj-primary) 0deg, var(--dj-accent-2) 360deg);
}
.dj-progress-circle::before {
    content: ''; position: absolute; inset: 6px; 
    border-radius: 50%; background: #fff;
}
body.dj-dark-mode .dj-progress-circle::before { background: #1E293B; }

.dj-progress-circle {
    position: relative; background: none;
}
.dj-progress-circle svg { width: 100%; height: 100%; }
.dj-progress-circle circle {
    fill: none; stroke-width: 6;
}
.dj-progress-circle .dj-circle-bg { stroke: var(--dj-gray-200); }
.dj-progress-circle .dj-circle-fill {
    stroke: var(--dj-primary); stroke-linecap: round;
    stroke-dasharray: 283; stroke-dashoffset: 283;
    transition: stroke-dashoffset 0.3s ease-out;
}

.dj-circle-text {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.dj-circle-text span {
    font-family: var(--dj-font-mono); font-size: 1.3rem;
    font-weight: 800; color: var(--dj-dark);
}
body.dj-dark-mode .dj-circle-text span { color: #F1F5F9; }
.dj-circle-text small {
    font-size: 0.7rem; color: var(--dj-gray-600);
    text-transform: uppercase; letter-spacing: 0.5px;
}

/* TOC Card */
.dj-toc-card {
    background: #fff; border: 1px solid var(--dj-gray-200);
    border-radius: 16px; padding: 20px; box-shadow: var(--dj-shadow-sm);
}
body.dj-dark-mode .dj-toc-card { 
    background: #1E293B; border-color: rgba(255,255,255,.08); 
}
.dj-toc-header {
    display: flex; align-items: center; gap: 10px;
    padding-bottom: 14px; border-bottom: 1px solid var(--dj-gray-100); 
    margin-bottom: 14px;
}
.dj-toc-header i { color: var(--dj-primary); font-size: 1rem; }
.dj-toc-header h3 { 
    font-size: 0.9rem; margin: 0; font-weight: 700;
    letter-spacing: 0.5px; text-transform: uppercase;
}

.dj-toc-nav { max-height: 350px; overflow-y: auto; }
.dj-toc-nav::-webkit-scrollbar { width: 3px; }
.dj-toc-nav::-webkit-scrollbar-thumb { 
    background: var(--dj-gray-200); border-radius: 2px; 
}
.dj-toc-empty { 
    font-size: .82rem; color: var(--dj-gray-600); 
    font-style: italic; 
}

.dj-toc-link {
    display: block; padding: 8px 12px; margin-bottom: 2px;
    font-size: .82rem; color: var(--dj-gray-700); 
    border-left: 2px solid transparent;
    text-decoration: none; transition: all .2s; line-height: 1.4;
    border-radius: 0 6px 6px 0;
}
body.dj-dark-mode .dj-toc-link { color: #CBD5E1; }
.dj-toc-link:hover { 
    color: var(--dj-primary-dark); background: var(--dj-primary-light); 
    border-left-color: var(--dj-primary); 
}
.dj-toc-link.active { 
    color: var(--dj-primary-dark); background: var(--dj-primary-light); 
    border-left-color: var(--dj-primary); font-weight: 700; 
}
.dj-toc-link.h3 { padding-left: 24px; font-size: .78rem; }

/* Tools Card */
.dj-tools-card {
    background: #fff; border: 1px solid var(--dj-gray-200);
    border-radius: 16px; padding: 20px; box-shadow: var(--dj-shadow-sm);
}
body.dj-dark-mode .dj-tools-card { 
    background: #1E293B; border-color: rgba(255,255,255,.08); 
}
.dj-tools-card h4 {
    font-size: 0.85rem; margin: 0 0 14px 0; font-weight: 700;
    display: flex; align-items: center; gap: 8px;
    text-transform: uppercase; letter-spacing: 0.5px;
    color: var(--dj-dark);
}
body.dj-dark-mode .dj-tools-card h4 { color: #F1F5F9; }
.dj-tools-card h4 i { color: var(--dj-primary); }

.dj-sidebar-btn {
    width: 100%; display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; background: var(--dj-gray-50);
    border: 1px solid var(--dj-gray-200); border-radius: 10px;
    cursor: pointer; font-family: inherit; font-size: 0.85rem;
    color: var(--dj-gray-700); transition: all 0.3s; margin-bottom: 8px;
    position: relative;
}
body.dj-dark-mode .dj-sidebar-btn { 
    background: rgba(255,255,255,.03); 
    border-color: rgba(255,255,255,.08);
    color: #CBD5E1;
}
.dj-sidebar-btn:hover {
    border-color: var(--dj-primary); transform: translateX(4px);
    background: var(--dj-primary-light);
}
body.dj-dark-mode .dj-sidebar-btn:hover {
    background: rgba(16,185,129,0.1);
}
.dj-sidebar-btn:last-child { margin-bottom: 0; }
.dj-sidebar-btn span:first-of-type { flex: 1; text-align: left; font-weight: 600; }

.dj-btn-badge {
    background: var(--dj-primary); color: #fff;
    padding: 2px 8px; border-radius: 10px;
    font-size: 0.7rem; font-weight: 700;
    font-family: var(--dj-font-mono);
}

/* Share Mini */
.dj-share-mini {
    background: #fff; border: 1px solid var(--dj-gray-200);
    border-radius: 16px; padding: 16px;
}
body.dj-dark-mode .dj-share-mini { 
    background: #1E293B; border-color: rgba(255,255,255,.08); 
}
.dj-share-label {
    font-size: 0.75rem; color: var(--dj-gray-600);
    text-transform: uppercase; letter-spacing: 1px; font-weight: 700;
    margin-bottom: 10px; display: block;
}
.dj-share-icons { 
    display: flex; gap: 6px; flex-wrap: wrap; 
}
.dj-share-icons a, .dj-copy-link {
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--dj-gray-100); color: var(--dj-gray-700);
    display: flex; align-items: center; justify-content: center;
    text-decoration: none; border: none; cursor: pointer;
    transition: all .25s; font-size: .85rem;
}
body.dj-dark-mode .dj-share-icons a,
body.dj-dark-mode .dj-copy-link { 
    background: rgba(255,255,255,.05); color: #CBD5E1; 
}
.dj-share-icons a:hover, .dj-copy-link:hover { 
    background: var(--dj-primary); color: #fff; 
    transform: translateY(-2px); 
}

/* ===== CONTENT ===== */
.dj-content { max-width: var(--dj-content-width); min-width: 0; }

/* Abstract */
.dj-abstract {
    background: linear-gradient(135deg, var(--dj-primary-light), rgba(209,250,229,.3));
    border: 1px solid rgba(16,185,129,0.2);
    border-radius: 16px; padding: 28px; margin-bottom: 40px;
    position: relative;
}
body.dj-dark-mode .dj-abstract {
    background: rgba(16,185,129,0.08);
    border-color: rgba(16,185,129,0.2);
}
.dj-abstract-label {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 4px 12px; background: var(--dj-primary);
    color: #fff; border-radius: 20px; font-size: 0.72rem;
    font-weight: 700; letter-spacing: 1.5px; margin-bottom: 16px;
    font-family: var(--dj-font-mono);
}
.dj-abstract-text {
    font-family: var(--dj-font-serif); font-size: 1.1rem;
    line-height: 1.8; color: var(--dj-dark); margin: 0 0 16px 0;
    font-style: italic;
}
body.dj-dark-mode .dj-abstract-text { color: #F1F5F9; }
.dj-abstract-meta {
    padding-top: 12px; border-top: 1px dashed rgba(16,185,129,0.3);
    font-size: 0.85rem; color: var(--dj-gray-600);
}

/* Body Content */
.dj-body {
    font-size: calc(1.05rem * var(--dj-font-scale)); 
    line-height: 1.9; color: var(--dj-dark);
    font-family: var(--dj-font-serif);
}
body.dj-dark-mode .dj-body { color: #E2E8F0; }
.dj-body p { margin-bottom: 1.5em; }

.dj-body h2, .dj-body h3, .dj-body h4 {
    margin-top: 2.5em; margin-bottom: 1em; font-weight: 800; 
    line-height: 1.3; scroll-margin-top: 100px;
    font-family: var(--dj-font-sans);
    position: relative;
}
.dj-body h2 { 
    font-size: 1.8em; color: var(--dj-dark); 
    padding-bottom: 12px;
    border-bottom: 2px solid var(--dj-primary-light);
}
body.dj-dark-mode .dj-body h2 { 
    color: #F1F5F9; 
    border-color: rgba(16,185,129,0.2);
}
.dj-body h2::before {
    content: ''; position: absolute; bottom: -2px; left: 0;
    width: 60px; height: 2px; background: var(--dj-primary);
}
.dj-body h3 { font-size: 1.4em; color: var(--dj-dark); }
body.dj-dark-mode .dj-body h3 { color: #F1F5F9; }
.dj-body h4 { font-size: 1.15em; }

.dj-body a { 
    color: var(--dj-primary); text-decoration: underline; 
    text-decoration-color: rgba(16,185,129,.3); 
    text-underline-offset: 3px; font-weight: 600;
}
.dj-body a:hover { text-decoration-color: var(--dj-primary); }

.dj-body ul, .dj-body ol { 
    margin-bottom: 1.5em; padding-left: 1.5em; 
}
.dj-body li { margin-bottom: .5em; }
.dj-body li::marker { color: var(--dj-primary); font-weight: 700; }

.dj-body blockquote {
    border-left: 4px solid var(--dj-primary);
    padding: 20px 28px; margin: 2em 0;
    background: var(--dj-gray-50); border-radius: 0 16px 16px 0;
    font-style: italic; color: var(--dj-gray-700);
    font-size: 1.1em;
    position: relative;
}
body.dj-dark-mode .dj-body blockquote { 
    background: rgba(255,255,255,.03); 
    color: #CBD5E1;
}
.dj-body blockquote::before {
    content: '"'; position: absolute; top: -20px; left: 20px;
    font-size: 4rem; color: var(--dj-primary); opacity: 0.2;
    font-family: Georgia, serif; line-height: 1;
}

.dj-body img {
    max-width: 100%; height: auto; border-radius: 12px;
    margin: 2em 0; box-shadow: var(--dj-shadow-md);
    cursor: zoom-in; transition: transform .3s;
}
.dj-body img:hover { transform: scale(1.02); }

.dj-body code {
    background: var(--dj-gray-100); padding: 3px 10px; 
    border-radius: 6px;
    font-family: var(--dj-font-mono); font-size: .88em;
    color: var(--dj-primary-dark);
    border: 1px solid var(--dj-gray-200);
}
body.dj-dark-mode .dj-body code {
    background: rgba(16,185,129,0.1); 
    color: #34D399;
    border-color: rgba(16,185,129,0.2);
}
.dj-body pre {
    background: #0F172A; color: #E2E8F0; padding: 24px;
    border-radius: 12px; overflow-x: auto; margin: 2em 0;
    border-left: 4px solid var(--dj-primary);
    font-family: var(--dj-font-mono); font-size: 0.9em;
    line-height: 1.6;
}
.dj-body pre code { 
    background: transparent; color: inherit; padding: 0; 
    border: none;
}

.dj-body table { 
    width: 100%; border-collapse: collapse; margin: 2em 0;
    border-radius: 12px; overflow: hidden;
    box-shadow: var(--dj-shadow-sm);
}
.dj-body th, .dj-body td { 
    padding: 14px 18px; border: 1px solid var(--dj-gray-200); 
    text-align: left; 
}
.dj-body th { 
    background: var(--dj-primary); color: #fff; 
    font-weight: 700; text-transform: uppercase;
    font-size: 0.85rem; letter-spacing: 0.5px;
}
.dj-body tr:nth-child(even) td { background: var(--dj-gray-50); }
body.dj-dark-mode .dj-body tr:nth-child(even) td { 
    background: rgba(255,255,255,.02); 
}

/* Highlight */
.dj-highlight {
    background: linear-gradient(180deg, transparent 60%, #FDE68A 60%);
    padding: 0 2px; border-radius: 2px;
}

/* References */
.dj-references {
    margin-top: 48px; padding-top: 32px;
    border-top: 2px solid var(--dj-gray-200);
}
body.dj-dark-mode .dj-references { border-color: rgba(255,255,255,.1); }
.dj-references h4 {
    font-size: 1rem; margin: 0 0 16px 0; font-weight: 700;
    display: flex; align-items: center; gap: 10px;
    text-transform: uppercase; letter-spacing: 1px;
    color: var(--dj-dark);
}
body.dj-dark-mode .dj-references h4 { color: #F1F5F9; }
.dj-references h4 i { color: var(--dj-primary); }

.dj-tags { display: flex; gap: 8px; flex-wrap: wrap; }
.dj-tag {
    padding: 6px 14px; background: var(--dj-gray-100); 
    color: var(--dj-gray-700);
    border-radius: 50px; font-size: .8rem; font-weight: 600;
    font-family: var(--dj-font-mono);
    transition: all .2s; cursor: default;
    border: 1px solid var(--dj-gray-200);
}
body.dj-dark-mode .dj-tag { 
    background: rgba(255,255,255,.05); 
    color: #CBD5E1;
    border-color: rgba(255,255,255,.1);
}
.dj-tag:hover { 
    background: var(--dj-primary-light); color: var(--dj-primary-dark); 
    border-color: var(--dj-primary);
    transform: translateY(-2px);
}

/* Author Card */
.dj-author-card {
    margin-top: 48px; background: #fff;
    border: 1px solid var(--dj-gray-200);
    border-radius: 20px; overflow: hidden;
    box-shadow: var(--dj-shadow-sm);
}
body.dj-dark-mode .dj-author-card { 
    background: #1E293B; 
    border-color: rgba(255,255,255,.08);
}
.dj-author-card-header {
    background: linear-gradient(135deg, var(--dj-primary), var(--dj-primary-dark));
    padding: 12px 24px;
}
.dj-author-label {
    font-size: 0.72rem; color: rgba(255,255,255,0.9);
    text-transform: uppercase; letter-spacing: 2px; font-weight: 700;
    font-family: var(--dj-font-mono);
}

.dj-author-card-body {
    display: flex; gap: 24px; padding: 32px;
}
.dj-author-card-body > img { 
    width: 100px; height: 100px; border-radius: 50%; 
    flex-shrink: 0; border: 4px solid var(--dj-primary-light);
    box-shadow: var(--dj-shadow-md);
}
.dj-author-details h3 { 
    font-size: 1.3rem; margin: 0 0 4px 0; color: var(--dj-dark);
    font-family: var(--dj-font-sans);
}
body.dj-dark-mode .dj-author-details h3 { color: #F1F5F9; }
.dj-author-role {
    font-size: 0.85rem; color: var(--dj-primary); font-weight: 600;
    margin-bottom: 12px; display: block;
}
.dj-author-bio {
    font-size: 0.9rem; color: var(--dj-gray-600);
    line-height: 1.6; margin-bottom: 16px;
}
body.dj-dark-mode .dj-author-bio { color: #94A3B8; }

.dj-author-contact {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 16px; background: var(--dj-primary-light);
    color: var(--dj-primary-dark); border-radius: 50px;
    text-decoration: none; font-size: 0.85rem; font-weight: 600;
    transition: all 0.3s;
}
body.dj-dark-mode .dj-author-contact {
    background: rgba(16,185,129,0.15); color: #34D399;
}
.dj-author-contact:hover {
    background: var(--dj-primary); color: #fff;
    transform: translateY(-2px);
}

/* Citation Section */
.dj-citation-section {
    margin-top: 48px; padding: 32px; background: var(--dj-gray-50);
    border-radius: 20px; border: 1px solid var(--dj-gray-200);
}
body.dj-dark-mode .dj-citation-section { 
    background: rgba(255,255,255,.03);
    border-color: rgba(255,255,255,.08);
}
.dj-citation-section h3 {
    font-size: 1.3rem; margin: 0 0 8px 0; color: var(--dj-dark);
    display: flex; align-items: center; gap: 10px;
}
body.dj-dark-mode .dj-citation-section h3 { color: #F1F5F9; }
.dj-citation-section h3 i { color: var(--dj-primary); }
.dj-citation-section > p {
    color: var(--dj-gray-600); margin-bottom: 20px; font-size: 0.95rem;
}

.dj-citation-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
.dj-cite-btn {
    padding: 10px 20px; background: #fff;
    border: 1.5px solid var(--dj-gray-200); border-radius: 10px;
    color: var(--dj-gray-700); font-weight: 700; font-size: 0.85rem;
    cursor: pointer; transition: all 0.3s;
    font-family: var(--dj-font-mono); letter-spacing: 0.5px;
}
body.dj-dark-mode .dj-cite-btn {
    background: #1E293B; border-color: rgba(255,255,255,.1);
    color: #CBD5E1;
}
.dj-cite-btn:hover {
    border-color: var(--dj-primary); color: var(--dj-primary);
    transform: translateY(-2px);
}

/* Share Section */
.dj-share-section {
    margin-top: 48px; background: linear-gradient(135deg, var(--dj-primary), var(--dj-primary-dark));
    border-radius: 24px; padding: 48px 32px; text-align: center;
    color: #fff; position: relative; overflow: hidden;
}
.dj-share-section::before {
    content: ''; position: absolute; top: -50%; right: -20%;
    width: 400px; height: 400px; border-radius: 50%;
    background: rgba(255,255,255,0.1);
}
.dj-share-content { position: relative; z-index: 1; }
.dj-share-content h3 {
    font-size: 1.8rem; margin-bottom: 8px; font-weight: 800;
}
.dj-share-content > p {
    opacity: 0.9; margin-bottom: 28px; font-size: 1rem;
}

.dj-share-grid {
    display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;
}
.dj-share-btn {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 12px 20px; border-radius: 50px; text-decoration: none;
    color: #fff; font-size: .88rem; font-weight: 600; border: none;
    cursor: pointer; transition: all .3s; font-family: inherit;
    box-shadow: 0 4px 16px rgba(0,0,0,.2);
    backdrop-filter: blur(10px);
}
.dj-share-btn:hover { 
    transform: translateY(-3px); 
    box-shadow: 0 8px 24px rgba(0,0,0,.3); 
}
.dj-share-btn.whatsapp { background: rgba(37,211,102,0.9); }
.dj-share-btn.facebook { background: rgba(24,119,242,0.9); }
.dj-share-btn.twitter { background: rgba(29,161,242,0.9); }
.dj-share-btn.linkedin { background: rgba(10,102,194,0.9); }
.dj-share-btn.email { background: rgba(234,67,53,0.9); }
.dj-share-btn.copy { background: rgba(255,255,255,0.2); }

/* Prev/Next */
.dj-nav-articles {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
    margin-top: 48px;
}
.dj-nav-spacer { display: block; }
.dj-nav-article {
    padding: 24px; background: #fff;
    border: 1px solid var(--dj-gray-200); border-radius: 16px;
    text-decoration: none; transition: all .3s;
    display: flex; flex-direction: column; gap: 10px;
}
body.dj-dark-mode .dj-nav-article { 
    background: #1E293B; 
    border-color: rgba(255,255,255,.08);
}
.dj-nav-article:hover { 
    border-color: var(--dj-primary); transform: translateY(-4px); 
    box-shadow: var(--dj-shadow-md); 
}
.dj-nav-article.next { text-align: right; }
.dj-nav-label { 
    display: flex; align-items: center; gap: 8px; 
    font-size: .75rem; color: var(--dj-gray-600); 
    text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;
    font-family: var(--dj-font-mono);
}
.dj-nav-article.next .dj-nav-label { justify-content: flex-end; }
.dj-nav-label i { color: var(--dj-primary); }
.dj-nav-title { 
    color: var(--dj-dark); font-weight: 700; line-height: 1.4; 
    display: -webkit-box; -webkit-line-clamp: 2; 
    -webkit-box-orient: vertical; overflow: hidden;
    font-size: 1rem;
}
body.dj-dark-mode .dj-nav-title { color: #F1F5F9; }

/* ===== RELATED ===== */
.dj-related { 
    background: var(--dj-gray-50); padding: 80px 0; 
    margin-top: 60px;
}
body.dj-dark-mode .dj-related { background: #0B1120; }

.dj-related-header { 
    display: flex; justify-content: space-between; 
    align-items: flex-end; flex-wrap: wrap; gap: 20px; 
    margin-bottom: 40px; 
}
.dj-related-label {
    font-size: 0.75rem; color: var(--dj-primary);
    text-transform: uppercase; letter-spacing: 2px; font-weight: 700;
    font-family: var(--dj-font-mono); display: block;
    margin-bottom: 6px;
}
.dj-related-title h2 {
    font-size: 2rem; margin: 0; font-weight: 800; color: var(--dj-dark);
}
body.dj-dark-mode .dj-related-title h2 { color: #F1F5F9; }
.accent { color: var(--dj-primary); }

.dj-view-all {
    display: inline-flex; align-items: center; gap: 8px;
    color: var(--dj-primary); font-weight: 700; text-decoration: none;
    transition: gap .3s; padding: 10px 20px;
    border: 1.5px solid var(--dj-primary); border-radius: 50px;
    font-size: 0.9rem;
}
.dj-view-all:hover { gap: 14px; background: var(--dj-primary); color: #fff; }

.dj-related-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 28px;
}

.dj-related-card {
    position: relative; background: #fff; border-radius: 20px;
    overflow: hidden; border: 1px solid var(--dj-gray-200);
    transition: all 0.4s; display: flex; flex-direction: column;
}
body.dj-dark-mode .dj-related-card { 
    background: #1E293B; border-color: rgba(255,255,255,.08); 
}
.dj-related-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--dj-shadow-lg);
    border-color: var(--dj-primary);
}
.dj-related-link { position: absolute; inset: 0; z-index: 1; }

.dj-related-image {
    position: relative; height: 220px; overflow: hidden;
    background: var(--dj-gray-100);
}
.dj-related-image img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.6s;
}
.dj-related-card:hover .dj-related-image img { transform: scale(1.08); }

.dj-related-category {
    position: absolute; top: 16px; left: 16px; z-index: 2;
    padding: 4px 12px; background: var(--dj-primary);
    color: #fff; border-radius: 6px; font-size: 0.72rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
}
.dj-related-readtime {
    position: absolute; top: 16px; right: 16px; z-index: 2;
    padding: 4px 10px; background: rgba(0,0,0,0.7);
    backdrop-filter: blur(10px); color: #fff;
    border-radius: 20px; font-size: 0.72rem; font-weight: 600;
    display: flex; align-items: center; gap: 4px;
}

.dj-related-content {
    padding: 24px; flex: 1; display: flex; flex-direction: column;
}
.dj-related-meta {
    display: flex; gap: 12px; font-size: 0.78rem;
    color: var(--dj-gray-600); margin-bottom: 12px;
}
.dj-related-meta i { color: var(--dj-primary); margin-right: 4px; }

.dj-related-content h3 {
    font-size: 1.15rem; font-weight: 800; line-height: 1.3;
    color: var(--dj-dark); margin: 0 0 12px 0;
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
}
body.dj-dark-mode .dj-related-content h3 { color: #F1F5F9; }
.dj-related-content p {
    font-size: 0.88rem; color: var(--dj-gray-600);
    line-height: 1.6; margin-bottom: 16px; flex: 1;
}
body.dj-dark-mode .dj-related-content p { color: #94A3B8; }

.dj-related-more {
    display: inline-flex; align-items: center; gap: 8px;
    color: var(--dj-primary); font-weight: 700; font-size: 0.88rem;
    transition: gap 0.3s;
}
.dj-related-card:hover .dj-related-more { gap: 14px; }

/* ===== MODALS ===== */
.dj-modal {
    position: fixed; inset: 0; z-index: 10000;
    background: rgba(15,23,42,0.85); backdrop-filter: blur(10px);
    display: none; align-items: center; justify-content: center;
    padding: 20px; opacity: 0; transition: opacity 0.3s;
}
.dj-modal.open { display: flex; opacity: 1; }
.dj-modal-content {
    background: #fff; border-radius: 20px; max-width: 600px;
    width: 100%; max-height: 90vh; overflow: hidden;
    box-shadow: 0 30px 60px rgba(0,0,0,0.3);
    position: relative; transform: scale(0.9);
    transition: transform 0.3s;
}
body.dj-dark-mode .dj-modal-content { background: #1E293B; }
.dj-modal.open .dj-modal-content { transform: scale(1); }
.dj-modal-large { max-width: 700px; }

.dj-modal-close {
    position: absolute; top: 16px; right: 16px; z-index: 10;
    width: 40px; height: 40px; border-radius: 50%;
    background: var(--dj-gray-100); border: none;
    color: var(--dj-dark); font-size: 1rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s;
}
body.dj-dark-mode .dj-modal-close { 
    background: rgba(255,255,255,.1); color: #fff; 
}
.dj-modal-close:hover { 
    background: #EF4444; color: #fff; transform: rotate(90deg); 
}

.dj-modal-header {
    padding: 28px 28px 20px;
    border-bottom: 1px solid var(--dj-gray-200);
}
body.dj-dark-mode .dj-modal-header { border-color: rgba(255,255,255,.08); }
.dj-modal-header h3 {
    font-size: 1.3rem; margin: 0 0 6px 0; font-weight: 800;
    display: flex; align-items: center; gap: 10px;
    color: var(--dj-dark);
}
body.dj-dark-mode .dj-modal-header h3 { color: #F1F5F9; }
.dj-modal-header h3 i { color: var(--dj-primary); }
.dj-modal-header p {
    margin: 0; color: var(--dj-gray-600); font-size: 0.9rem;
}

.dj-modal-body { padding: 24px 28px; }

.dj-citation-format {
    background: var(--dj-gray-50); padding: 20px;
    border-radius: 12px; border-left: 4px solid var(--dj-primary);
    font-family: var(--dj-font-mono); font-size: 0.88rem;
    line-height: 1.7; color: var(--dj-dark);
    margin-bottom: 16px; word-break: break-word;
}
body.dj-dark-mode .dj-citation-format {
    background: rgba(0,0,0,0.2); color: #E2E8F0;
}

.dj-copy-citation {
    width: 100%; padding: 12px 20px; background: var(--dj-primary);
    color: #fff; border: none; border-radius: 10px;
    font-weight: 700; cursor: pointer; transition: all 0.3s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    font-family: inherit; font-size: 0.95rem;
}
.dj-copy-citation:hover {
    background: var(--dj-primary-dark); transform: translateY(-2px);
}

.dj-notes-textarea {
    width: 100%; min-height: 200px; padding: 16px;
    border: 1.5px solid var(--dj-gray-200); border-radius: 12px;
    font-family: inherit; font-size: 0.95rem; line-height: 1.6;
    resize: vertical; background: var(--dj-gray-50);
    color: var(--dj-dark);
}
body.dj-dark-mode .dj-notes-textarea {
    background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1);
    color: #E2E8F0;
}
.dj-notes-textarea:focus {
    outline: none; border-color: var(--dj-primary);
    box-shadow: 0 0 0 4px rgba(16,185,129,0.15);
}

.dj-notes-actions {
    display: flex; gap: 10px; margin-top: 16px; justify-content: flex-end;
}
.dj-btn-primary, .dj-btn-secondary {
    padding: 10px 20px; border-radius: 10px;
    font-weight: 700; cursor: pointer; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 8px;
    font-family: inherit; font-size: 0.9rem; border: none;
}
.dj-btn-primary {
    background: var(--dj-primary); color: #fff;
}
.dj-btn-primary:hover {
    background: var(--dj-primary-dark); transform: translateY(-2px);
}
.dj-btn-secondary {
    background: var(--dj-gray-100); color: var(--dj-gray-700);
    border: 1.5px solid var(--dj-gray-200);
}
body.dj-dark-mode .dj-btn-secondary {
    background: rgba(255,255,255,.05); color: #CBD5E1;
    border-color: rgba(255,255,255,.1);
}
.dj-btn-secondary:hover {
    background: var(--dj-gray-200);
}

/* ===== LIGHTBOX ===== */
.dj-lightbox {
    position: fixed; inset: 0; z-index: 10001;
    background: rgba(0,0,0,.95); backdrop-filter: blur(10px);
    display: none; align-items: center; justify-content: center;
    padding: 40px; opacity: 0; transition: opacity .3s;
}
.dj-lightbox.open { display: flex; opacity: 1; }
.dj-lightbox img { 
    max-width: 90vw; max-height: 90vh; border-radius: 12px; 
    box-shadow: 0 20px 60px rgba(0,0,0,.5); 
}
.dj-lightbox-close {
    position: absolute; top: 20px; right: 20px;
    width: 48px; height: 48px; border-radius: 50%;
    background: rgba(255,255,255,.1); border: none;
    color: #fff; font-size: 1.2rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .25s;
}
.dj-lightbox-close:hover { 
    background: #fff; color: #000; transform: rotate(90deg); 
}

/* ===== TOAST ===== */
.dj-toast {
    position: fixed; bottom: 30px; left: 50%; 
    transform: translateX(-50%) translateY(100px);
    background: var(--dj-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,.25);
    z-index: 10002; opacity: 0; transition: all .4s cubic-bezier(.4,0,.2,1);
    font-size: .9rem;
}
body.dj-dark-mode .dj-toast { background: #fff; color: var(--dj-dark); }
.dj-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.dj-toast i { color: #10B981; }

/* ===== BACK TO TOP ===== */
.dj-back-to-top {
    position: fixed; bottom: 30px; right: 30px;
    width: 50px; height: 50px; border-radius: 50%;
    background: linear-gradient(135deg, var(--dj-primary), var(--dj-primary-dark));
    border: none; color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; z-index: 998;
    box-shadow: 0 8px 24px rgba(16,185,129,.4);
    opacity: 0; visibility: hidden; transform: translateY(20px) scale(.8);
    transition: all .4s;
}
.dj-back-to-top.show { opacity: 1; visibility: visible; transform: none; }
.dj-back-to-top:hover { 
    transform: translateY(-4px) scale(1.05); 
}

/* ===== FOCUS MODE ===== */
body.dj-focus-mode .navbar,
body.dj-focus-mode .footer,
body.dj-focus-mode .wa-float,
body.dj-focus-mode .dj-sidebar,
body.dj-focus-mode .dj-related,
body.dj-focus-mode .dj-reading-stats,
body.dj-focus-mode .dj-share-section,
body.dj-focus-mode .dj-citation-section,
body.dj-focus-mode .dj-author-card,
body.dj-focus-mode .dj-references,
body.dj-focus-mode .dj-nav-articles {
    display: none !important;
}
body.dj-focus-mode .dj-layout {
    grid-template-columns: 1fr;
}
body.dj-focus-mode .dj-content {
    max-width: 700px; margin: 0 auto;
}

/* ===== ANIMATIONS ===== */
.fade-up {
    opacity: 0; transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1100px) {
    .dj-layout { grid-template-columns: 1fr; }
    .dj-sidebar { 
        position: static; order: 2;
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .dj-content { order: 1; max-width: 100%; }
    .dj-progress-circle-wrap { display: none; }
    .dj-reading-stats { display: none; }
}
@media (max-width: 768px) {
    .dj-hero { padding: 60px 20px 30px; }
    .dj-title { font-size: 2rem; }
    .dj-author-line { flex-direction: column; padding: 16px; }
    .dj-author-info { text-align: center; }
    .dj-meta-pills { justify-content: center; }
    .dj-toolbar { padding: 6px; }
    .dj-tool-btn { padding: 6px 10px; font-size: 0.78rem; }
    .dj-tool-btn span { display: none; }
    .dj-tool-divider { display: none; }
    .dj-sidebar { grid-template-columns: 1fr; }
    .dj-nav-articles { grid-template-columns: 1fr; }
    .dj-related-grid { grid-template-columns: 1fr; }
    .dj-share-grid { flex-direction: column; }
    .dj-share-btn { justify-content: center; }
    .dj-author-card-body { flex-direction: column; text-align: center; }
    .dj-author-card-body > img { margin: 0 auto; }
    .dj-citation-buttons { justify-content: center; }
    .dj-journal-meta-top { flex-direction: column; gap: 8px; }
    .dj-progress-info { display: none; }
}
@media (max-width: 480px) {
    .dj-hero { padding: 40px 16px 20px; }
    .dj-title { font-size: 1.6rem; }
    .dj-body { font-size: calc(0.95rem * var(--dj-font-scale)); }
    .dj-layout { padding: 30px 16px; }
    .dj-related { padding: 60px 16px; }
}

/* ===== PRINT ===== */
@media print {
    .dj-progress-bar, .dj-toolbar, .dj-sidebar, .dj-share-section,
    .dj-nav-articles, .dj-related, .dj-back-to-top, .dj-lightbox,
    .dj-toast, .navbar, .footer, .wa-float, .dj-reading-stats,
    .dj-citation-section, .dj-author-card { display: none !important; }
    .dj-layout { grid-template-columns: 1fr; padding: 0; }
    body { background: #fff !important; color: #000 !important; }
    .dj-body { font-size: 11pt; }
    .dj-title { color: #000 !important; }
    .dj-body h2, .dj-body h3 { color: #000 !important; page-break-after: avoid; }
    .dj-body img { page-break-inside: avoid; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Parse article data
    let articleData = {};
    try {
        const dataScript = document.getElementById('djArticleData');
        if (dataScript) {
            articleData = JSON.parse(dataScript.textContent);
        }
    } catch (e) {
        console.error('Failed to parse article data:', e);
    }

    // ===== 1. Reading Progress Bar =====
    const progressBar = document.getElementById('djProgressBar');
    const progressFill = document.getElementById('djProgressFill');
    const progressPercent = document.getElementById('djProgressPercent');
    const progressTime = document.getElementById('djProgressTime');
    const circleFill = document.getElementById('djCircleFill');
    const circlePercent = document.getElementById('djCirclePercent');
    const article = document.querySelector('.dj-content');

    const totalWords = articleData.wordCount || 0;
    const readingStartTime = Date.now();
    let wordsRead = 0;

    function updateProgress() {
        if (!article) return;
        const rect = article.getBoundingClientRect();
        const windowHeight = window.innerHeight;
        const articleTop = rect.top + window.scrollY;
        const articleHeight = rect.height;
        
        const scrolled = window.scrollY - articleTop + windowHeight * 0.3;
        const percent = Math.max(0, Math.min(100, (scrolled / articleHeight) * 100));
        
        progressFill.style.width = percent + '%';
        if (progressPercent) progressPercent.textContent = Math.round(percent) + '%';
        if (circlePercent) circlePercent.textContent = Math.round(percent) + '%';
        
        // Update circle
        if (circleFill) {
            const circumference = 283; // 2 * PI * 45
            const offset = circumference - (percent / 100) * circumference;
            circleFill.style.strokeDashoffset = offset;
        }
        
        // Estimate words read
        wordsRead = Math.round((percent / 100) * totalWords);
        const wordsEl = document.getElementById('djWordsRead');
        if (wordsEl) wordsEl.textContent = wordsRead.toLocaleString('id-ID');
        
        // Time spent
        const timeSpentMs = Date.now() - readingStartTime;
        const timeSpentMin = Math.floor(timeSpentMs / 60000);
        const timeSpentSec = Math.floor((timeSpentMs % 60000) / 1000);
        const timeEl = document.getElementById('djTimeSpent');
        if (timeEl) timeEl.textContent = `${timeSpentMin}:${String(timeSpentSec).padStart(2, '0')}`;
        
        // WPM calculation
        if (timeSpentMin > 0) {
            const wpm = Math.round(wordsRead / timeSpentMin);
            const wpmEl = document.getElementById('djWPM');
            if (wpmEl) wpmEl.textContent = wpm;
        }
        
        // Remaining time
        if (progressTime) {
            const wordsLeft = totalWords - wordsRead;
            const avgWpm = 200;
            const minutesLeft = Math.ceil(wordsLeft / avgWpm);
            progressTime.textContent = minutesLeft > 0 ? `${minutesLeft} min left` : 'Done!';
        }
        
        if (window.scrollY > 200) {
            progressBar.classList.add('active');
            document.body.classList.add('dj-scrolled');
        } else {
            progressBar.classList.remove('active');
            document.body.classList.remove('dj-scrolled');
        }
    }
    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();

    // ===== 2. Auto-generate TOC =====
    const contentBody = document.getElementById('djBody');
    const tocNav = document.getElementById('djTocNav');
    if (contentBody && tocNav) {
        const headings = contentBody.querySelectorAll('h2, h3');
        if (headings.length >= 2) {
            tocNav.innerHTML = '';
            let counter = 0;
            headings.forEach(h => {
                counter++;
                const id = 'dj-heading-' + counter;
                h.id = id;
                const link = document.createElement('a');
                link.href = '#' + id;
                link.className = 'dj-toc-link ' + h.tagName.toLowerCase();
                link.textContent = h.textContent.trim();
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    document.getElementById(id).scrollIntoView({ 
                        behavior: 'smooth', block: 'start' 
                    });
                });
                tocNav.appendChild(link);
            });

            // Highlight active TOC
            const tocLinks = tocNav.querySelectorAll('.dj-toc-link');
            const headingObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        tocLinks.forEach(l => {
                            l.classList.toggle('active', l.getAttribute('href') === '#' + id);
                        });
                    }
                });
            }, { rootMargin: '-20% 0px -70% 0px' });
            headings.forEach(h => headingObserver.observe(h));
        }
    }

    // ===== 3. Font Size Control =====
    let fontScale = parseFloat(localStorage.getItem('dj_font_scale') || '1');
    applyFontScale();

    document.getElementById('djFontDown')?.addEventListener('click', () => {
        fontScale = Math.max(0.8, fontScale - 0.1);
        applyFontScale();
        djShowToast('📏 Font size: ' + Math.round(fontScale * 100) + '%');
    });
    document.getElementById('djFontUp')?.addEventListener('click', () => {
        fontScale = Math.min(1.5, fontScale + 0.1);
        applyFontScale();
        djShowToast('📏 Font size: ' + Math.round(fontScale * 100) + '%');
    });

    function applyFontScale() {
        document.documentElement.style.setProperty('--dj-font-scale', fontScale);
        localStorage.setItem('dj_font_scale', fontScale);
    }

    // ===== 4. Focus Mode =====
    const focusBtn = document.getElementById('djFocusMode');
    if (focusBtn) {
        focusBtn.addEventListener('click', () => {
            document.body.classList.toggle('dj-focus-mode');
            const active = document.body.classList.contains('dj-focus-mode');
            focusBtn.classList.toggle('active', active);
            djShowToast(active ? '🎯 Focus mode enabled' : 'Focus mode disabled');
        });
    }

    // ===== 5. Dark Mode =====
    const darkBtn = document.getElementById('djDarkMode');
    const isDark = localStorage.getItem('dj_dark_mode') === 'true';
    if (isDark) {
        document.body.classList.add('dj-dark-mode');
        if (darkBtn) {
            darkBtn.classList.add('active');
            const icon = darkBtn.querySelector('i');
            if (icon) icon.className = 'fas fa-sun';
        }
    }
    if (darkBtn) {
        darkBtn.addEventListener('click', () => {
            document.body.classList.toggle('dj-dark-mode');
            const active = document.body.classList.contains('dj-dark-mode');
            darkBtn.classList.toggle('active', active);
            const icon = darkBtn.querySelector('i');
            if (icon) icon.className = active ? 'fas fa-sun' : 'fas fa-moon';
            localStorage.setItem('dj_dark_mode', active);
            djShowToast(active ? '🌙 Dark mode enabled' : '☀️ Light mode');
        });
    }

    // ===== 6. Print =====
    document.getElementById('djPrint')?.addEventListener('click', () => {
        window.print();
    });

    // ===== 7. Bookmark =====
    const bookmarkBtn = document.getElementById('djBookmark');
    const currentSlug = articleData.slug || '';
    let bookmarks = JSON.parse(localStorage.getItem('dj_bookmarks') || '[]');
    if (bookmarks.includes(currentSlug) && bookmarkBtn) {
        bookmarkBtn.classList.add('active');
        const icon = bookmarkBtn.querySelector('i');
        if (icon) icon.className = 'fas fa-bookmark';
    }

    if (bookmarkBtn) {
        bookmarkBtn.addEventListener('click', () => {
            const icon = bookmarkBtn.querySelector('i');
            if (bookmarks.includes(currentSlug)) {
                bookmarks = bookmarks.filter(s => s !== currentSlug);
                bookmarkBtn.classList.remove('active');
                if (icon) icon.className = 'far fa-bookmark';
                djShowToast('🗑️ Removed from bookmarks');
            } else {
                bookmarks.push(currentSlug);
                bookmarkBtn.classList.add('active');
                if (icon) icon.className = 'fas fa-bookmark';
                djShowToast('🔖 Saved to bookmarks');
            }
            localStorage.setItem('dj_bookmarks', JSON.stringify(bookmarks));
        });
    }

    // ===== 8. Copy Link =====
    document.querySelectorAll('.dj-copy-link, .dj-share-btn.copy').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const url = btn.dataset.url;
            navigator.clipboard.writeText(url).then(() => {
                djShowToast('🔗 Link copied to clipboard!');
            }).catch(() => {
                const ta = document.createElement('textarea');
                ta.value = url;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                djShowToast('🔗 Link copied!');
            });
        });
    });

    // ===== 9. Toast =====
    const toast = document.getElementById('djToast');
    const toastMsg = document.getElementById('djToastMsg');
    let toastTimer;

    window.djShowToast = function(msg) {
        if (!toast || !toastMsg) return;
        toastMsg.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // ===== 10. Lightbox =====
    const lightbox = document.getElementById('djLightbox');
    const lightboxImg = document.getElementById('djLightboxImg');
    const lightboxClose = document.getElementById('djLightboxClose');

    function openLightbox(src, alt) {
        if (!lightbox || !lightboxImg) return;
        lightboxImg.src = src;
        lightboxImg.alt = alt || '';
        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        if (!lightbox) return;
        lightbox.classList.remove('open');
        document.body.style.overflow = '';
    }

    document.getElementById('djMainImage')?.addEventListener('click', function() {
        openLightbox(this.src, this.alt);
    });

    contentBody?.querySelectorAll('img').forEach(img => {
        img.addEventListener('click', () => openLightbox(img.src, img.alt));
    });

    lightboxClose?.addEventListener('click', closeLightbox);
    lightbox?.addEventListener('click', (e) => { 
        if (e.target === lightbox) closeLightbox(); 
    });
    document.addEventListener('keydown', (e) => { 
        if (e.key === 'Escape') {
            closeLightbox();
            closeCitationModal();
            closeNotesModal();
        }
    });

    // ===== 11. Back to Top =====
    const backToTop = document.getElementById('djBackToTop');
    window.addEventListener('scroll', () => {
        if (!backToTop) return;
        if (window.scrollY > 600) backToTop.classList.add('show');
        else backToTop.classList.remove('show');
    }, { passive: true });
    backToTop?.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // ===== 12. Citation Modal =====
    const citationModal = document.getElementById('djCitationModal');
    const citationBtn = document.getElementById('djCitationBtn');
    const citationClose = document.getElementById('djCitationClose');
    const citationContent = document.getElementById('djCitationContent');
    const copyCitation = document.getElementById('djCopyCitation');
    let currentCitation = '';

    function generateCitation(format) {
        const { title, author, year, url, category } = articleData;
        const dateStr = articleData.published ? new Date(articleData.published).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : year;
        
        switch(format) {
            case 'apa':
                return `${author} (${year}). ${title}. <em>Informatika Pro Journal</em>, ${category}. Retrieved from ${url}`;
            case 'mla':
                return `${author}. "${title}." <em>Informatika Pro Journal</em>, ${dateStr}, ${url}.`;
            case 'chicago':
                return `${author}. "${title}." Informatika Pro Journal, ${dateStr}. ${url}.`;
            case 'bibtex':
                return `@article{${author.split(' ')[0].toLowerCase()}${year},\n  title={${title}},\n  author={${author}},\n  journal={Informatika Pro Journal},\n  year={${year}},\n  url={${url}}\n}`;
            default:
                return '';
        }
    }

    function showCitation(format) {
        if (!citationContent) return;
        currentCitation = generateCitation(format);
        citationContent.innerHTML = `<strong style="color: var(--dj-primary); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px;">${format} Format</strong><br><br>${currentCitation}`;
    }

    function openCitationModal() {
        if (!citationModal) return;
        showCitation('apa');
        citationModal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeCitationModal() {
        if (!citationModal) return;
        citationModal.classList.remove('open');
        document.body.style.overflow = '';
    }

    citationBtn?.addEventListener('click', openCitationModal);
    citationClose?.addEventListener('click', closeCitationModal);
    citationModal?.addEventListener('click', (e) => {
        if (e.target === citationModal) closeCitationModal();
    });

    document.querySelectorAll('.dj-cite-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            showCitation(btn.dataset.format);
            if (!citationModal.classList.contains('open')) {
                openCitationModal();
            }
        });
    });

    copyCitation?.addEventListener('click', () => {
        navigator.clipboard.writeText(currentCitation).then(() => {
            djShowToast('📋 Citation copied!');
        });
    });

    // Toolbar citation button
    document.getElementById('djCitationBtn')?.addEventListener('click', openCitationModal);

    // ===== 13. Notes Modal =====
    const notesModal = document.getElementById('djNotesModal');
    const notesBtn = document.getElementById('djNotesBtn');
    const notesClose = document.getElementById('djNotesClose');
    const notesTextarea = document.getElementById('djNotesTextarea');
    const saveNotes = document.getElementById('djSaveNotes');
    const clearNotes = document.getElementById('djClearNotes');
    const notesCount = document.getElementById('djNotesCount');

    const notesKey = 'dj_notes_' + currentSlug;
    let notes = localStorage.getItem(notesKey) || '';
    if (notesTextarea) notesTextarea.value = notes;
    if (notesCount) {
        const count = notes.length > 0 ? 1 : 0;
        notesCount.textContent = count;
    }

    function openNotesModal() {
        if (!notesModal) return;
        notesModal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeNotesModal() {
        if (!notesModal) return;
        notesModal.classList.remove('open');
        document.body.style.overflow = '';
    }

    notesBtn?.addEventListener('click', openNotesModal);
    notesClose?.addEventListener('click', closeNotesModal);
    notesModal?.addEventListener('click', (e) => {
        if (e.target === notesModal) closeNotesModal();
    });

    saveNotes?.addEventListener('click', () => {
        if (!notesTextarea) return;
        const text = notesTextarea.value;
        localStorage.setItem(notesKey, text);
        if (notesCount) notesCount.textContent = text.length > 0 ? 1 : 0;
        djShowToast('💾 Notes saved!');
        closeNotesModal();
    });

    clearNotes?.addEventListener('click', () => {
        if (!notesTextarea) return;
        if (confirm('Clear all notes?')) {
            notesTextarea.value = '';
            localStorage.removeItem(notesKey);
            if (notesCount) notesCount.textContent = 0;
            djShowToast('🗑️ Notes cleared');
        }
    });

    // ===== 14. Highlight Mode =====
    const highlightBtn = document.getElementById('djHighlightBtn');
    const highlightCount = document.getElementById('djHighlightCount');
    let highlightMode = false;
    let highlights = JSON.parse(localStorage.getItem('dj_highlights_' + currentSlug) || '[]');
    
    // Restore highlights
    if (contentBody) {
        highlights.forEach(hl => {
            // Simple restoration - in production would need more sophisticated approach
        });
        if (highlightCount) highlightCount.textContent = highlights.length;
    }

    if (highlightBtn) {
        highlightBtn.addEventListener('click', () => {
            highlightMode = !highlightMode;
            highlightBtn.classList.toggle('active', highlightMode);
            document.body.style.cursor = highlightMode ? 'text' : '';
            djShowToast(highlightMode ? '🖍️ Highlight mode ON - Select text' : 'Highlight mode OFF');
        });
    }

    // Handle text selection for highlighting
    document.addEventListener('mouseup', () => {
        if (!highlightMode) return;
        const selection = window.getSelection();
        if (!selection || selection.isCollapsed || !contentBody) return;
        
        const range = selection.getRangeAt(0);
        if (!contentBody.contains(range.commonAncestorContainer)) return;
        
        const span = document.createElement('span');
        span.className = 'dj-highlight';
        try {
            range.surroundContents(span);
            highlights.push(span.textContent);
            localStorage.setItem('dj_highlights_' + currentSlug, JSON.stringify(highlights));
            if (highlightCount) highlightCount.textContent = highlights.length;
            djShowToast('✨ Text highlighted!');
        } catch (e) {
            djShowToast('⚠️ Cannot highlight selection');
        }
        selection.removeAllRanges();
    });

    // ===== 15. Read Aloud =====
    const readAloudBtn = document.getElementById('djReadAloud');
    let isSpeaking = false;

    if (readAloudBtn) {
        readAloudBtn.addEventListener('click', () => {
            if (!contentBody) return;
            
            if (isSpeaking) {
                window.speechSynthesis.cancel();
                isSpeaking = false;
                readAloudBtn.classList.remove('active');
                djShowToast('🔇 Reading stopped');
                return;
            }

            const text = contentBody.innerText;
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 0.95;
            
            utterance.onend = () => {
                isSpeaking = false;
                readAloudBtn.classList.remove('active');
                djShowToast('✅ Reading complete');
            };
            
            window.speechSynthesis.speak(utterance);
            isSpeaking = true;
            readAloudBtn.classList.add('active');
            djShowToast('🔊 Reading aloud...');
        });
    }

    // ===== 16. Fade-up Animation =====
    const fadeObs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                fadeObs.unobserve(e.target);
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.dj-wrapper .fade-up').forEach(el => {
        if (el) fadeObs.observe(el);
    });

    // Welcome toast
    setTimeout(() => {
        djShowToast('📖 Welcome! Enjoy reading');
    }, 1500);

    // Console Easter Egg
    console.log('%c╔═══════════════════════════════════════╗', 'color:#10B981;font-weight:bold');
    console.log('%c║   DIGITAL JOURNAL - Reading Mode      ║', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c╚═══════════════════════════════════════╝', 'color:#10B981;font-weight:bold');
    console.log('%c📖 ' + totalWords + ' words | ' + articleData.title, 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>