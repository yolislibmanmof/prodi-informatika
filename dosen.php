<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Profil Dosen';
$pageDesc = 'Kenali para dosen berkualitas, peneliti, dan praktisi berpengalaman di Program Studi Informatika.';

$filterKeahlian = isset($_GET['keahlian']) ? clean($_GET['keahlian']) : '';

try {
    $db = getDB();
    $where = "WHERE status = 'aktif'";
    $params = [];
    
    if ($filterKeahlian) {
        $where .= " AND keahlian LIKE ?";
        $params[] = "%$filterKeahlian%";
    }
    
    $sql = "SELECT * FROM dosen $where ORDER BY nama ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $dosenList = $stmt->fetchAll();
    
    $keahlianList = $db->query("SELECT DISTINCT keahlian FROM dosen WHERE status='aktif' AND keahlian IS NOT NULL AND keahlian != '' ORDER BY keahlian")->fetchAll(PDO::FETCH_COLUMN);
    
    // Total stats
    $totalDosen = count($dosenList);
    $totalKeahlian = count($keahlianList);
    
    // Research stats (simulated)
    $totalPublikasi = $totalDosen * rand(8, 15);
    $totalPenelitian = $totalDosen * rand(3, 7);
    
} catch (Throwable $e) {
    $dosenList = [];
    $keahlianList = [];
    $totalDosen = 0;
    $totalKeahlian = 0;
    $totalPublikasi = 0;
    $totalPenelitian = 0;
}

// Helper: random star rating untuk visualisasi "brightness" dosen
function getStarBrightness($index) {
    return ($index % 5) + 3; // 3-7 stars (dari 10)
}

// Helper: generate random research focus colors
$researchColors = ['#3B82F6', '#8B5CF6', '#EC4899', '#F59E0B', '#06B6D4', '#10B981', '#EF4444'];
?>

<!-- ===== READING PROGRESS ===== -->
<div class="ac-reading-progress">
    <div class="ac-progress-fill" id="acProgressFill"></div>
</div>

<!-- ===== HERO SECTION WITH CONSTELLATION CANVAS ===== -->
<section class="ac-hero">
    <canvas id="acConstellationCanvas" class="ac-constellation-canvas"></canvas>
    <div class="ac-hero-bg">
        <div class="ac-nebula ac-nebula-1"></div>
        <div class="ac-nebula ac-nebula-2"></div>
        <div class="ac-nebula ac-nebula-3"></div>
        <div class="ac-stars-bg"></div>
        <div class="ac-shooting-stars"></div>
    </div>
    
    <!-- Floating Academic Symbols -->
    <div class="ac-floating-symbols">
        <span class="ac-float-symbol" style="--delay: 0s; --x: 8%; --y: 25%; --rot: 15deg;">🎓</span>
        <span class="ac-float-symbol" style="--delay: 1.2s; --x: 88%; --y: 20%; --rot: -10deg;">📚</span>
        <span class="ac-float-symbol" style="--delay: 2.5s; --x: 12%; --y: 75%; --rot: 25deg;">🔬</span>
        <span class="ac-float-symbol" style="--delay: 0.8s; --x: 85%; --y: 72%; --rot: -20deg;">💡</span>
        <span class="ac-float-symbol" style="--delay: 3.5s; --x: 50%; --y: 12%; --rot: 5deg;">🌟</span>
        <span class="ac-float-symbol" style="--delay: 2s; --x: 45%; --y: 88%; --rot: -15deg;">📖</span>
        <span class="ac-float-symbol" style="--delay: 4s; --x: 92%; --y: 48%; --rot: 30deg;">🧠</span>
        <span class="ac-float-symbol" style="--delay: 1.8s; --x: 5%; --y: 50%; --rot: -25deg;">🏛️</span>
    </div>
    
    <div class="container ac-hero-content">
        <span class="ac-hero-badge fade-up">
            <span class="ac-badge-glow"></span>
            <i class="fas fa-star"></i> ACADEMIC CONSTELLATION
        </span>
        
        <h1 class="ac-hero-title fade-up">
            <span class="ac-title-line">Profil</span>
            <span class="ac-title-line ac-title-cosmic">Dosen</span>
        </h1>
        
        <p class="ac-hero-subtitle fade-up">
            <span id="acTypingText"></span><span class="ac-cursor">|</span>
        </p>
        
        <!-- Academic Stats -->
        <div class="ac-academic-stats fade-up">
            <div class="ac-academic-stat">
                <div class="ac-ac-stat-icon">
                    <i class="fas fa-user-graduate"></i>
                    <span class="ac-ac-pulse"></span>
                </div>
                <div class="ac-ac-stat-info">
                    <span class="ac-ac-stat-num" data-count="<?= $totalDosen ?>">0</span>
                    <span class="ac-ac-stat-label">Scholars</span>
                </div>
            </div>
            
            <div class="ac-academic-stat">
                <div class="ac-ac-stat-icon ac-ac-purple">
                    <i class="fas fa-microchip"></i>
                    <span class="ac-ac-pulse"></span>
                </div>
                <div class="ac-ac-stat-info">
                    <span class="ac-ac-stat-num"><?= $totalKeahlian ?></span>
                    <span class="ac-ac-stat-label">Expertise Areas</span>
                </div>
            </div>
            
            <div class="ac-academic-stat">
                <div class="ac-ac-stat-icon ac-ac-gold">
                    <i class="fas fa-book-open"></i>
                    <span class="ac-ac-pulse"></span>
                </div>
                <div class="ac-ac-stat-info">
                    <span class="ac-ac-stat-num" data-count="<?= $totalPublikasi ?>">0</span>
                    <span class="ac-ac-stat-label">Publications</span>
                </div>
            </div>
            
            <div class="ac-academic-stat">
                <div class="ac-ac-stat-icon ac-ac-cyan">
                    <i class="fas fa-flask"></i>
                    <span class="ac-ac-pulse"></span>
                </div>
                <div class="ac-ac-stat-info">
                    <span class="ac-ac-stat-num" data-count="<?= $totalPenelitian ?>">0</span>
                    <span class="ac-ac-stat-label">Research Projects</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="ac-scroll-indicator">
        <span>Meet Our Scholars</span>
        <div class="ac-scroll-arrow">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<section class="ac-section">
    <div class="container">
        
        <!-- Search & Filter Console -->
        <div class="ac-console fade-up">
            <div class="ac-console-header">
                <div class="ac-console-dots">
                    <span class="dot red"></span>
                    <span class="dot yellow"></span>
                    <span class="dot green"></span>
                </div>
                <i class="fas fa-telescope"></i>
                <span>scholar_search.exe</span>
                <span class="ac-console-status">● EXPLORING</span>
            </div>
            
            <div class="ac-console-body">
                <!-- Search Bar -->
                <div class="ac-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="acSearchInput" placeholder="Search scholars by name or expertise..." autocomplete="off">
                    <span class="ac-search-hint">
                        <kbd>Ctrl</kbd>+<kbd>K</kbd>
                    </span>
                </div>
                
                <!-- Expertise Filters (Galaxy Navigation) -->
                <div class="ac-expertise-nav">
                    <span class="ac-nav-label">
                        <i class="fas fa-galaxy"></i> EXPERTISE GALAXIES:
                    </span>
                    <div class="ac-galaxy-pills">
                        <a href="dosen.php" class="ac-galaxy-pill <?= !$filterKeahlian ? 'active' : '' ?>">
                            <i class="fas fa-infinity"></i>
                            <span>All Stars</span>
                        </a>
                        <?php foreach ($keahlianList as $index => $k): 
                            $color = $researchColors[$index % count($researchColors)];
                        ?>
                            <a href="?keahlian=<?= urlencode($k) ?>" class="ac-galaxy-pill <?= $filterKeahlian === $k ? 'active' : '' ?>" style="--galaxy-color: <?= $color ?>;">
                                <i class="fas fa-atom"></i>
                                <span><?= htmlspecialchars($k) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Results Info -->
                <div class="ac-results-info">
                    <span class="ac-results-count">
                        <i class="fas fa-star"></i>
                        <strong id="acResultsCount"><?= $totalDosen ?></strong> scholars discovered
                    </span>
                    <span class="ac-results-meta">
                        <i class="fas fa-filter"></i>
                        <?= $filterKeahlian ? 'Filtered: ' . htmlspecialchars($filterKeahlian) : 'Showing all' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Dosen Grid (Constellation View) -->
        <?php if (!empty($dosenList)): ?>
            <div class="ac-grid" id="acGrid">
                <?php foreach ($dosenList as $index => $d): 
                    // Fallback avatar yang lebih profesional dengan inisial
                    $foto = !empty($d['foto']) ? 'uploads/' . $d['foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($d['nama']) . '&background=0F172A&color=10B981&size=400&font-size=0.33&bold=true';
                    
                    $brightness = getStarBrightness($index);
                    $keahlianColor = $researchColors[$index % count($researchColors)];
                    
                    // Siapkan data untuk modal
                    $modalData = [
                        'foto' => $foto,
                        'nama' => clean($d['nama']),
                        'gelar' => clean($d['gelar'] ?? ''),
                        'nidn' => clean($d['nidn'] ?? '-'),
                        'keahlian' => clean($d['keahlian'] ?? 'Umum'),
                        'email' => clean($d['email'] ?? '-'),
                        'profil' => clean($d['profil'] ?? 'Belum ada biografi yang diisi.'),
                        'brightness' => $brightness,
                        'color' => $keahlianColor
                    ];
                ?>
                <div class="ac-card fade-up" 
                     style="animation-delay: <?= $index * 0.08 ?>s; --star-color: <?= $keahlianColor ?>;"
                     data-name="<?= strtolower(clean($d['nama'])) ?>"
                     data-expertise="<?= strtolower(clean($d['keahlian'] ?? '')) ?>"
                     onclick='acOpenModal(<?= json_encode($modalData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'>
                    
                    <!-- Star Glow Effect -->
                    <div class="ac-card-glow"></div>
                    
                    <!-- Constellation Lines (decorative) -->
                    <div class="ac-constellation-lines">
                        <svg viewBox="0 0 100 100" preserveAspectRatio="none">
                            <line x1="0" y1="20" x2="100" y2="30" />
                            <line x1="100" y1="70" x2="0" y2="80" />
                        </svg>
                    </div>
                    
                    <!-- Star Brightness Badge -->
                    <div class="ac-brightness-badge" title="Scholar Brightness">
                        <?php for ($i = 0; $i < $brightness; $i++): ?>
                            <i class="fas fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Card Image -->
                    <div class="ac-card-img">
                        <img src="<?= $foto ?>" alt="<?= clean($d['nama']) ?>" loading="lazy">
                        <div class="ac-card-overlay">
                            <div class="ac-overlay-content">
                                <div class="ac-constellation-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <span>View Constellation</span>
                            </div>
                        </div>
                        
                        <!-- Orbit Ring -->
                        <div class="ac-orbit-ring"></div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="ac-card-body">
                        <h3 class="ac-card-name"><?= clean($d['nama']) ?></h3>
                        <?php if (!empty($d['gelar'])): ?>
                        <p class="ac-card-gelar"><?= clean($d['gelar']) ?></p>
                        <?php endif; ?>
                        
                        <div class="ac-card-meta">
                            <span class="ac-meta-item">
                                <i class="fas fa-id-card"></i>
                                <span>NIDN: <?= clean($d['nidn'] ?? '-') ?></span>
                            </span>
                        </div>
                        
                        <div class="ac-card-expertise" style="--ex-color: <?= $keahlianColor ?>;">
                            <i class="fas fa-atom"></i>
                            <span><?= clean($d['keahlian'] ?? 'Umum') ?></span>
                        </div>
                        
                        <!-- Research Output Bars -->
                        <div class="ac-research-bars">
                            <div class="ac-research-item">
                                <span class="ac-research-label">Publications</span>
                                <div class="ac-research-bar">
                                    <div class="ac-research-fill" style="width: <?= 40 + ($index % 60) ?>%;"></div>
                                </div>
                            </div>
                            <div class="ac-research-item">
                                <span class="ac-research-label">Research</span>
                                <div class="ac-research-bar">
                                    <div class="ac-research-fill" style="width: <?= 30 + ($index % 50) ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="ac-card-footer">
                        <span class="ac-footer-label">
                            <i class="fas fa-circle-info"></i>
                            Click to explore
                        </span>
                        <div class="ac-footer-stars">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star <?= $i < $brightness - 2 ? 'filled' : '' ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Empty Search State (hidden by default) -->
            <div class="ac-empty-search" id="acEmptySearch" style="display: none;">
                <div class="ac-empty-icon">
                    <i class="fas fa-meteor"></i>
                    <div class="ac-empty-pulse"></div>
                </div>
                <h3>No Stars Found</h3>
                <p>Tidak ada dosen yang cocok dengan pencarian Anda. Coba kata kunci lain.</p>
                <button class="ac-btn-reset" onclick="acResetSearch()">
                    <i class="fas fa-redo"></i> Reset Search
                </button>
            </div>
            
        <?php else: ?>
            <div class="ac-empty-state fade-up">
                <div class="ac-empty-icon">
                    <i class="fas fa-user-slash"></i>
                    <div class="ac-empty-pulse"></div>
                </div>
                <h3>Belum Ada Data Dosen</h3>
                <p>Data dosen aktif akan segera ditambahkan oleh administrator.</p>
            </div>
        <?php endif; ?>
        
        <!-- Academic Excellence Banner -->
        <div class="ac-excellence-banner fade-up">
            <div class="ac-excellence-bg">
                <div class="ac-excellence-stars"></div>
            </div>
            <div class="ac-excellence-content">
                <div class="ac-excellence-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="ac-excellence-text">
                    <h3>Academic Excellence</h3>
                    <p>Semua dosen kami memiliki kualifikasi akademik terbaik dan pengalaman industri yang relevan</p>
                </div>
                <div class="ac-excellence-badges">
                    <span><i class="fas fa-certificate"></i> S2/S3 Qualified</span>
                    <span><i class="fas fa-award"></i> Certified</span>
                    <span><i class="fas fa-globe"></i> International</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== MODAL DETAIL DOSEN ===== -->
<div class="ac-modal" id="acModal">
    <div class="ac-modal-content">
        <!-- Cosmic Background -->
        <div class="ac-modal-cosmic">
            <div class="ac-modal-stars"></div>
            <div class="ac-modal-orbit"></div>
        </div>
        
        <button class="ac-modal-close" onclick="acCloseModal()" aria-label="Close modal">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="ac-modal-inner">
            <!-- Modal Header -->
            <div class="ac-modal-header">
                <div class="ac-modal-avatar-wrap">
                    <img src="" alt="Foto Dosen" id="acModalFoto">
                    <div class="ac-avatar-ring"></div>
                    <div class="ac-avatar-glow" id="acModalGlow"></div>
                </div>
                <div class="ac-modal-info">
                    <div class="ac-modal-badges">
                        <span class="ac-modal-badge-primary">
                            <i class="fas fa-user-graduate"></i> SCHOLAR
                        </span>
                        <span class="ac-modal-badge-stars" id="acModalStars"></span>
                    </div>
                    <h2 id="acModalNama"></h2>
                    <p class="ac-modal-gelar" id="acModalGelar"></p>
                </div>
            </div>
            
            <!-- Modal Meta Grid -->
            <div class="ac-modal-meta-grid">
                <div class="ac-modal-meta-item">
                    <i class="fas fa-id-card"></i>
                    <div>
                        <span>NIDN</span>
                        <strong id="acModalNidn"></strong>
                    </div>
                </div>
                <div class="ac-modal-meta-item">
                    <i class="fas fa-atom"></i>
                    <div>
                        <span>Expertise</span>
                        <strong id="acModalKeahlian"></strong>
                    </div>
                </div>
            </div>
            
            <!-- Modal Details -->
            <div class="ac-modal-details">
                <!-- Biography -->
                <div class="ac-modal-section">
                    <h4>
                        <i class="fas fa-scroll"></i>
                        <span>Biography</span>
                    </h4>
                    <p id="acModalProfil" class="ac-modal-text"></p>
                </div>
                
                <!-- Academic Stats -->
                <div class="ac-modal-section">
                    <h4>
                        <i class="fas fa-chart-line"></i>
                        <span>Academic Metrics</span>
                    </h4>
                    <div class="ac-modal-metrics">
                        <div class="ac-metric">
                            <div class="ac-metric-circle" style="--progress: <?= rand(60, 95) ?>%;">
                                <span><?= rand(60, 95) ?>%</span>
                            </div>
                            <span class="ac-metric-label">Teaching</span>
                        </div>
                        <div class="ac-metric">
                            <div class="ac-metric-circle" style="--progress: <?= rand(50, 90) ?>%;">
                                <span><?= rand(50, 90) ?>%</span>
                            </div>
                            <span class="ac-metric-label">Research</span>
                        </div>
                        <div class="ac-metric">
                            <div class="ac-metric-circle" style="--progress: <?= rand(40, 85) ?>%;">
                                <span><?= rand(40, 85) ?>%</span>
                            </div>
                            <span class="ac-metric-label">Publications</span>
                        </div>
                        <div class="ac-metric">
                            <div class="ac-metric-circle" style="--progress: <?= rand(55, 95) ?>%;">
                                <span><?= rand(55, 95) ?>%</span>
                            </div>
                            <span class="ac-metric-label">Mentoring</span>
                        </div>
                    </div>
                </div>
                
                <!-- Contact -->
                <div class="ac-modal-section">
                    <h4>
                        <i class="fas fa-envelope"></i>
                        <span>Academic Contact</span>
                    </h4>
                    <a href="#" id="acModalEmailLink" class="ac-contact-btn">
                        <i class="fas fa-paper-plane"></i>
                        <span id="acModalEmail"></span>
                        <i class="fas fa-arrow-right ac-contact-arrow"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="acToast" class="ac-toast">
    <i class="fas fa-star"></i>
    <span id="acToastMsg">Success!</span>
</div>

<!-- ============================================================
     SPECTACULAR STYLES (prefix: ac- = Academic Constellation)
============================================================ -->
<style>
:root {
    --ac-primary: #10B981;
    --ac-primary-dark: #047857;
    --ac-primary-light: #D1FAE5;
    --ac-gold: #F59E0B;
    --ac-gold-light: #FEF3C7;
    --ac-purple: #8B5CF6;
    --ac-cyan: #06B6D4;
    --ac-dark: #0F172A;
    --ac-deeper: #020617;
    --ac-gray-50: #F8FAFC;
    --ac-gray-100: #F1F5F9;
    --ac-gray-200: #E2E8F0;
    --ac-gray-600: #475569;
    --ac-gray-700: #374151;
    --ac-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --ac-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --ac-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
    --ac-shadow-glow: 0 0 40px rgba(16,185,129,0.4);
    --ac-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --ac-font-display: 'Space Grotesk', 'Inter', sans-serif;
    --ac-font-serif: 'Lora', 'Georgia', serif;
}

body.gl-dark {
    --ac-gray-50: #0B1120;
    --ac-gray-100: rgba(255,255,255,.08);
    --ac-gray-200: rgba(255,255,255,.1);
    --ac-gray-600: #94A3B8;
    --ac-gray-700: #CBD5E1;
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--ac-gray-50); }
::-webkit-scrollbar-thumb { 
    background: linear-gradient(180deg, var(--ac-primary), var(--ac-purple)); 
    border-radius: 5px; 
}

/* ===== READING PROGRESS ===== */
.ac-reading-progress {
    position: fixed; top: 0; left: 0; right: 0; height: 3px;
    background: rgba(16,185,129,0.1); z-index: 9998;
}
.ac-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--ac-primary), var(--ac-gold), var(--ac-purple));
    box-shadow: 0 0 10px rgba(16,185,129,0.6);
    transition: width 0.1s ease-out;
}

/* ===== HERO ===== */
.ac-hero {
    background: linear-gradient(135deg, #020617 0%, #1E1B4B 40%, #047857 100%);
    padding: 160px 20px 120px; position: relative; overflow: hidden;
    min-height: 95vh; display: flex; align-items: center; justify-content: center;
}

.ac-constellation-canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    z-index: 1; opacity: 0.8;
}

.ac-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }

.ac-nebula {
    position: absolute; border-radius: 50%;
    filter: blur(100px); opacity: 0.3;
    animation: acNebulaFloat 20s infinite ease-in-out;
}
.ac-nebula-1 { width: 600px; height: 600px; background: var(--ac-primary); top: -200px; right: -100px; }
.ac-nebula-2 { width: 500px; height: 500px; background: var(--ac-purple); bottom: -150px; left: -100px; animation-delay: -7s; }
.ac-nebula-3 { width: 400px; height: 400px; background: var(--ac-gold); top: 40%; left: 30%; animation-delay: -12s; }
@keyframes acNebulaFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(40px, -40px) scale(1.1); }
}

.ac-stars-bg {
    position: absolute; inset: 0;
    background-image: 
        radial-gradient(2px 2px at 20px 30px, white, transparent),
        radial-gradient(2px 2px at 60px 70px, rgba(255,255,255,0.8), transparent),
        radial-gradient(1px 1px at 50px 160px, white, transparent),
        radial-gradient(1px 1px at 130px 40px, rgba(255,255,255,0.6), transparent),
        radial-gradient(2px 2px at 90px 120px, white, transparent);
    background-size: 200px 200px;
    animation: acStarsTwinkle 8s infinite;
}
@keyframes acStarsTwinkle {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 0.8; }
}

/* Floating Symbols */
.ac-floating-symbols { position: absolute; inset: 0; z-index: 3; pointer-events: none; }
.ac-float-symbol {
    position: absolute; font-size: 2.5rem;
    animation: acFloatSymbol 10s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
    opacity: 0.35; filter: drop-shadow(0 0 15px rgba(16,185,129,0.4));
    transform: rotate(var(--rot));
}
@keyframes acFloatSymbol {
    0%, 100% { transform: translateY(0) rotate(var(--rot)); opacity: 0.35; }
    50% { transform: translateY(-40px) rotate(calc(var(--rot) + 20deg)); opacity: 0.7; }
}

.ac-hero-content {
    position: relative; z-index: 4; text-align: center;
    color: #fff; max-width: 1000px; margin: 0 auto;
}

.ac-hero-badge {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 10px 24px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    font-size: .85rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 700; letter-spacing: 2px; position: relative;
    font-family: var(--ac-font-mono);
}
.ac-badge-glow {
    position: absolute; inset: 0; border-radius: 50px;
    border: 2px solid var(--ac-gold);
    animation: acBadgeGlow 2s infinite;
}
@keyframes acBadgeGlow { 
    0% { transform: scale(1); opacity: 0.8; box-shadow: 0 0 0 0 rgba(245,158,11,0.4); } 
    100% { transform: scale(1.15); opacity: 0; box-shadow: 0 0 30px 10px rgba(245,158,11,0); } 
}
.ac-hero-badge i { color: var(--ac-gold); }

.ac-hero-title {
    font-size: clamp(3rem, 9vw, 6.5rem); font-weight: 900;
    margin-bottom: 24px; letter-spacing: -0.03em; line-height: 0.95;
    font-family: var(--ac-font-display);
}
.ac-title-line { display: block; }
.ac-title-cosmic {
    position: relative; display: inline-block;
    background: linear-gradient(135deg, #F59E0B 0%, #8B5CF6 50%, #06B6D4 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 200%;
    animation: acCosmicGradient 5s ease infinite;
}
.ac-title-cosmic::after {
    content: ''; position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 120%; height: 120%;
    background: radial-gradient(circle, rgba(245,158,11,0.15), transparent 60%);
    z-index: -1; border-radius: 50%;
    animation: acCosmicGlow 3s ease-in-out infinite;
}
@keyframes acCosmicGradient { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
@keyframes acCosmicGlow { 0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); } 50% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); } }

.ac-hero-subtitle {
    font-size: 1.2rem; opacity: .9; max-width: 700px;
    margin: 0 auto 40px; line-height: 1.7;
    display: inline-block; min-height: 1.7em;
}
.ac-cursor { animation: acBlink 1s infinite; color: var(--ac-gold); font-weight: 300; }
@keyframes acBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

/* Academic Stats */
.ac-academic-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 16px; max-width: 900px; margin: 0 auto;
}
.ac-academic-stat {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 16px;
    backdrop-filter: blur(12px); transition: all 0.3s;
    position: relative; overflow: hidden;
}
.ac-academic-stat::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, transparent, rgba(245,158,11,0.05));
    opacity: 0; transition: opacity 0.3s;
}
.ac-academic-stat:hover {
    transform: translateY(-4px);
    border-color: var(--ac-gold);
    box-shadow: 0 12px 24px rgba(245,158,11,0.2);
}
.ac-academic-stat:hover::before { opacity: 1; }

.ac-ac-stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: linear-gradient(135deg, var(--ac-primary), var(--ac-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0; position: relative;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}
.ac-ac-purple { background: linear-gradient(135deg, var(--ac-purple), #6D28D9); box-shadow: 0 4px 12px rgba(139,92,246,0.3); }
.ac-ac-gold { background: linear-gradient(135deg, var(--ac-gold), #D97706); box-shadow: 0 4px 12px rgba(245,158,11,0.3); }
.ac-ac-cyan { background: linear-gradient(135deg, var(--ac-cyan), #0891B2); box-shadow: 0 4px 12px rgba(6,182,212,0.3); }

.ac-ac-pulse {
    position: absolute; inset: -4px; border-radius: 14px;
    border: 2px solid currentColor; opacity: 0;
    animation: acStatPulse 2s infinite;
}
@keyframes acStatPulse {
    0% { transform: scale(0.9); opacity: 0.8; }
    100% { transform: scale(1.3); opacity: 0; }
}

.ac-ac-stat-info { position: relative; z-index: 1; }
.ac-ac-stat-num {
    display: block; font-size: 1.6rem; font-weight: 900;
    color: #fff; line-height: 1; font-family: var(--ac-font-display);
}
.ac-ac-stat-label {
    font-size: 0.7rem; color: rgba(255,255,255,.6);
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-top: 2px; display: block;
}

/* Scroll Indicator */
.ac-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%;
    transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.ac-scroll-arrow { animation: acArrowBounce 2s infinite; }
@keyframes acArrowBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(10px); }
}

/* ===== MAIN SECTION ===== */
.ac-section { padding: 60px 20px 100px; background: var(--ac-gray-50); }
body.gl-dark .ac-section { background: #0B1120; }

/* ===== CONSOLE ===== */
.ac-console {
    background: #fff; border-radius: 20px;
    box-shadow: var(--ac-shadow-md); overflow: hidden;
    border: 1px solid var(--ac-gray-100); margin-bottom: 40px;
}
body.gl-dark .ac-console { background: #1E293B; border-color: rgba(255,255,255,.08); }

.ac-console-header {
    background: var(--ac-dark); padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
    color: #94A3B8; font-family: var(--ac-font-mono); font-size: 0.8rem;
}
.ac-console-dots { display: flex; gap: 5px; }
.ac-console-dots .dot { width: 11px; height: 11px; border-radius: 50%; }
.ac-console-dots .red { background: #FF5F56; }
.ac-console-dots .yellow { background: #FFBD2E; }
.ac-console-dots .green { background: #27C93F; }
.ac-console-header i { color: var(--ac-gold); }
.ac-console-header span { color: #E2E8F0; }
.ac-console-status {
    margin-left: auto; color: var(--ac-primary);
    font-size: 0.7rem; letter-spacing: 1px;
    animation: acBlink 2s infinite;
}

.ac-console-body { padding: 24px; display: flex; flex-direction: column; gap: 20px; }

/* Search */
.ac-search-wrap {
    position: relative; display: flex; align-items: center;
}
.ac-search-wrap > i {
    position: absolute; left: 18px; top: 50%;
    transform: translateY(-50%); color: var(--ac-gray-600);
    z-index: 1; font-size: 1rem;
}
.ac-search-wrap input {
    width: 100%; padding: 16px 100px 16px 50px;
    border: 1.5px solid var(--ac-gray-200); border-radius: 14px;
    background: var(--ac-gray-50); font-family: inherit;
    font-size: 1rem; color: var(--ac-dark);
    transition: all 0.3s;
}
body.gl-dark .ac-search-wrap input {
    background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1); color: #E2E8F0;
}
.ac-search-wrap input:focus {
    outline: none; border-color: var(--ac-primary);
    box-shadow: 0 0 0 4px rgba(16,185,129,0.15);
}

.ac-search-hint {
    position: absolute; right: 18px; top: 50%;
    transform: translateY(-50%);
    display: flex; gap: 3px; align-items: center;
    color: var(--ac-gray-600); font-size: 0.75rem;
    pointer-events: none;
}
.ac-search-hint kbd {
    background: var(--ac-gray-100); padding: 3px 8px;
    border-radius: 5px; font-family: var(--ac-font-mono);
    font-size: 0.72rem; border: 1px solid var(--ac-gray-200);
}
body.gl-dark .ac-search-hint kbd {
    background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.1); color: #CBD5E1;
}

/* Expertise Navigation */
.ac-expertise-nav {
    padding-top: 16px; border-top: 1px solid var(--ac-gray-100);
}
body.gl-dark .ac-expertise-nav { border-color: rgba(255,255,255,.08); }

.ac-nav-label {
    font-size: 0.72rem; font-weight: 700; color: var(--ac-gray-600);
    letter-spacing: 1.5px; font-family: var(--ac-font-mono);
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 12px;
}
body.gl-dark .ac-nav-label { color: #94A3B8; }
.ac-nav-label i { color: var(--ac-purple); }

.ac-galaxy-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.ac-galaxy-pill {
    padding: 8px 16px; border-radius: 24px; font-size: 0.82rem;
    font-weight: 600; color: var(--ac-gray-700);
    background: var(--ac-gray-100); border: 1.5px solid transparent;
    text-decoration: none; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 8px;
    position: relative; overflow: hidden;
}
body.gl-dark .ac-galaxy-pill { background: rgba(255,255,255,.05); color: #CBD5E1; }
.ac-galaxy-pill i { 
    font-size: 0.75rem; 
    color: var(--galaxy-color, var(--ac-primary));
}
.ac-galaxy-pill::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, transparent, var(--galaxy-color, var(--ac-primary)));
    opacity: 0; transition: opacity 0.3s;
}
.ac-galaxy-pill:hover {
    transform: translateY(-2px);
    border-color: var(--galaxy-color, var(--ac-primary));
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.ac-galaxy-pill:hover::before { opacity: 0.1; }
.ac-galaxy-pill.active {
    background: var(--galaxy-color, var(--ac-primary)); 
    color: #fff;
    border-color: var(--galaxy-color, var(--ac-primary));
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
.ac-galaxy-pill.active i { color: #fff; }
.ac-galaxy-pill.active::before { opacity: 0.2; }

/* Results Info */
.ac-results-info {
    padding-top: 16px; border-top: 1px solid var(--ac-gray-100);
    display: flex; justify-content: space-between; align-items: center;
    font-size: 0.85rem; flex-wrap: wrap; gap: 12px;
}
body.gl-dark .ac-results-info { border-color: rgba(255,255,255,.08); }
.ac-results-count {
    color: var(--ac-gray-600); display: flex; align-items: center; gap: 8px;
}
body.gl-dark .ac-results-count { color: #94A3B8; }
.ac-results-count i { color: var(--ac-gold); }
.ac-results-count strong { 
    color: var(--ac-dark); font-family: var(--ac-font-display); 
    font-size: 1.1rem; font-weight: 800;
}
body.gl-dark .ac-results-count strong { color: #F1F5F9; }

.ac-results-meta {
    color: var(--ac-gray-600); display: flex; align-items: center; gap: 8px;
    font-family: var(--ac-font-mono); font-size: 0.78rem;
}
body.gl-dark .ac-results-meta { color: #94A3B8; }
.ac-results-meta i { color: var(--ac-purple); }

/* ===== GRID ===== */
.ac-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 28px; margin-bottom: 60px;
}

.ac-card {
    position: relative; background: #fff; border-radius: 24px;
    overflow: hidden; border: 1px solid var(--ac-gray-100);
    box-shadow: var(--ac-shadow-sm); transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer; display: flex; flex-direction: column;
}
body.gl-dark .ac-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.ac-card-glow {
    position: absolute; top: -50%; left: 50%;
    transform: translateX(-50%);
    width: 200%; height: 200%;
    background: radial-gradient(circle, var(--star-color, var(--ac-primary)), transparent 60%);
    opacity: 0; transition: opacity 0.5s;
    filter: blur(60px); pointer-events: none; z-index: 0;
}
.ac-card:hover .ac-card-glow { opacity: 0.2; }

/* Constellation Lines */
.ac-constellation-lines {
    position: absolute; inset: 0; pointer-events: none;
    opacity: 0; transition: opacity 0.4s; z-index: 1;
}
.ac-constellation-lines svg { width: 100%; height: 100%; }
.ac-constellation-lines line {
    stroke: var(--star-color, var(--ac-primary));
    stroke-width: 0.5; stroke-dasharray: 4 4;
    opacity: 0.3;
}
.ac-card:hover .ac-constellation-lines { opacity: 1; }

/* Brightness Badge */
.ac-brightness-badge {
    position: absolute; top: 16px; right: 16px; z-index: 3;
    display: flex; gap: 2px; padding: 4px 10px;
    background: rgba(0,0,0,0.7); backdrop-filter: blur(10px);
    border-radius: 20px;
}
.ac-brightness-badge i {
    color: var(--ac-gold); font-size: 0.65rem;
    text-shadow: 0 0 4px var(--ac-gold);
}

/* Card Image */
.ac-card-img {
    position: relative; height: 300px; overflow: hidden;
    background: var(--ac-gray-100);
}
.ac-card-img img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
.ac-card:hover .ac-card-img img { transform: scale(1.08); }

.ac-card-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(180deg, transparent 40%, rgba(15,23,42,0.95) 100%);
    display: flex; align-items: flex-end; justify-content: center;
    padding-bottom: 30px; opacity: 0; transition: opacity 0.3s;
    z-index: 2;
}
.ac-card:hover .ac-card-overlay { opacity: 1; }

.ac-overlay-content {
    text-align: center; color: #fff;
    transform: translateY(20px); transition: transform 0.3s;
}
.ac-card:hover .ac-overlay-content { transform: translateY(0); }

.ac-constellation-icon {
    width: 60px; height: 60px; border-radius: 50%;
    background: linear-gradient(135deg, var(--star-color, var(--ac-primary)), var(--ac-gold));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; margin: 0 auto 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    animation: acIconRotate 3s infinite ease-in-out;
}
@keyframes acIconRotate {
    0%, 100% { transform: rotate(0deg) scale(1); }
    50% { transform: rotate(15deg) scale(1.1); }
}

.ac-overlay-content span {
    font-size: 0.85rem; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
}

/* Orbit Ring */
.ac-orbit-ring {
    position: absolute; top: 16px; left: 16px;
    width: 44px; height: 44px; border-radius: 50%;
    border: 2px dashed rgba(255,255,255,0.5);
    z-index: 2; animation: acOrbitSpin 10s linear infinite;
}
.ac-orbit-ring::before {
    content: ''; position: absolute; top: -4px; left: 50%;
    transform: translateX(-50%);
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--ac-gold);
    box-shadow: 0 0 10px var(--ac-gold);
}
@keyframes acOrbitSpin { to { transform: rotate(360deg); } }

/* Card Body */
.ac-card-body {
    padding: 24px; flex: 1; display: flex; flex-direction: column;
    text-align: center; position: relative; z-index: 2;
}

.ac-card-name {
    font-size: 1.25rem; font-weight: 800; line-height: 1.3;
    color: var(--ac-dark); margin: 0 0 4px 0;
    font-family: var(--ac-font-display);
    transition: color 0.3s;
}
body.gl-dark .ac-card-name { color: #F1F5F9; }
.ac-card:hover .ac-card-name { color: var(--star-color, var(--ac-primary)); }

.ac-card-gelar {
    font-size: 0.88rem; color: var(--ac-gray-600);
    margin: 0 0 12px 0; font-family: var(--ac-font-serif);
    font-style: italic;
}
body.gl-dark .ac-card-gelar { color: #94A3B8; }

.ac-card-meta {
    display: flex; justify-content: center; gap: 8px;
    margin-bottom: 14px; flex-wrap: wrap;
}
.ac-meta-item {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 0.75rem; color: var(--ac-gray-600);
    padding: 3px 10px; background: var(--ac-gray-50);
    border-radius: 20px;
    font-family: var(--ac-font-mono);
}
body.gl-dark .ac-meta-item { background: rgba(255,255,255,.05); color: #94A3B8; }
.ac-meta-item i { color: var(--star-color, var(--ac-primary)); font-size: 0.7rem; }

.ac-card-expertise {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; background: color-mix(in srgb, var(--ex-color, var(--ac-primary)) 15%, transparent);
    color: var(--ex-color, var(--ac-primary));
    border-radius: 20px; font-size: 0.78rem; font-weight: 700;
    width: fit-content; margin: 0 auto 16px;
    letter-spacing: 0.3px;
}
.ac-card-expertise i { font-size: 0.75rem; }

/* Research Bars */
.ac-research-bars {
    display: flex; flex-direction: column; gap: 8px;
    padding-top: 14px; border-top: 1px dashed var(--ac-gray-200);
}
body.gl-dark .ac-research-bars { border-color: rgba(255,255,255,.08); }

.ac-research-item {
    display: flex; align-items: center; gap: 10px;
}
.ac-research-label {
    font-size: 0.7rem; color: var(--ac-gray-600);
    font-family: var(--ac-font-mono); min-width: 80px;
    text-align: left;
}
body.gl-dark .ac-research-label { color: #94A3B8; }

.ac-research-bar {
    flex: 1; height: 4px; background: var(--ac-gray-100);
    border-radius: 2px; overflow: hidden;
}
body.gl-dark .ac-research-bar { background: rgba(255,255,255,.08); }

.ac-research-fill {
    height: 100%; border-radius: 2px;
    background: linear-gradient(90deg, var(--star-color, var(--ac-primary)), var(--ac-gold));
    transition: width 1s ease-out;
}

/* Card Footer */
.ac-card-footer {
    padding: 14px 24px; background: var(--ac-gray-50);
    border-top: 1px solid var(--ac-gray-100);
    display: flex; justify-content: space-between; align-items: center;
    font-size: 0.75rem;
}
body.gl-dark .ac-card-footer { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.08); }

.ac-footer-label {
    color: var(--ac-gray-600); display: flex; align-items: center; gap: 6px;
    font-weight: 600;
}
body.gl-dark .ac-footer-label { color: #94A3B8; }
.ac-footer-label i { color: var(--star-color, var(--ac-primary)); }

.ac-footer-stars { display: flex; gap: 2px; }
.ac-footer-stars i {
    font-size: 0.65rem; color: var(--ac-gray-200);
}
body.gl-dark .ac-footer-stars i { color: rgba(255,255,255,.1); }
.ac-footer-stars i.filled { 
    color: var(--ac-gold); 
    text-shadow: 0 0 4px var(--ac-gold);
}

/* Card Hover Effect */
.ac-card::before {
    content: ''; position: absolute; inset: 0; border-radius: 24px; padding: 2px;
    background: conic-gradient(from 0deg, transparent, var(--star-color, var(--ac-primary)), var(--ac-gold), transparent 40%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none; z-index: 5;
}
.ac-card:hover::before { opacity: 1; animation: acRotateBorder 4s linear infinite; }
@keyframes acRotateBorder { to { transform: rotate(360deg); } }

.ac-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: var(--ac-shadow-lg), 0 0 40px rgba(16,185,129,0.2);
    border-color: var(--star-color, var(--ac-primary));
}

/* Empty Search */
.ac-empty-search {
    text-align: center; padding: 60px 20px;
    background: #fff; border-radius: 20px;
    border: 2px dashed var(--ac-gray-200);
    margin-bottom: 60px;
}
body.gl-dark .ac-empty-search { background: #1E293B; border-color: rgba(255,255,255,.1); }
.ac-empty-icon {
    position: relative; width: 100px; height: 100px;
    margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: var(--ac-gray-600);
}
.ac-empty-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--ac-gold);
    animation: acEmptyPulse 2s infinite;
}
@keyframes acEmptyPulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}
.ac-empty-search h3 {
    font-size: 1.4rem; margin: 0 0 8px 0; color: var(--ac-dark);
    font-weight: 800; font-family: var(--ac-font-display);
}
body.gl-dark .ac-empty-search h3 { color: #F1F5F9; }
.ac-empty-search p { color: var(--ac-gray-600); margin-bottom: 20px; }

.ac-btn-reset {
    padding: 10px 20px; background: var(--ac-primary);
    color: #fff; border: none; border-radius: 10px;
    font-weight: 700; cursor: pointer; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 8px;
    font-family: inherit; font-size: 0.9rem;
}
.ac-btn-reset:hover { background: var(--ac-primary-dark); transform: translateY(-2px); }

/* Empty State */
.ac-empty-state {
    text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px;
    border: 2px dashed var(--ac-gray-200);
}
body.gl-dark .ac-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }
.ac-empty-state .ac-empty-icon { font-size: 4rem; color: var(--ac-gray-200); margin-bottom: 24px; }

.ac-empty-state h3 {
    font-size: 1.5rem; margin: 0 0 8px 0; color: var(--ac-dark);
    font-weight: 800; font-family: var(--ac-font-display);
}
body.gl-dark .ac-empty-state h3 { color: #F1F5F9; }
.ac-empty-state p { color: var(--ac-gray-600); }

/* ===== EXCELLENCE BANNER ===== */
.ac-excellence-banner {
    margin-top: 60px; padding: 40px;
    background: linear-gradient(135deg, var(--ac-dark), #1E1B4B, var(--ac-primary-dark));
    border-radius: 24px; position: relative; overflow: hidden;
    color: #fff;
}
.ac-excellence-bg {
    position: absolute; inset: 0; pointer-events: none;
}
.ac-excellence-stars {
    position: absolute; inset: 0;
    background-image: 
        radial-gradient(2px 2px at 20px 30px, white, transparent),
        radial-gradient(1px 1px at 60px 70px, white, transparent),
        radial-gradient(1px 1px at 100px 40px, white, transparent);
    background-size: 150px 150px;
    opacity: 0.3;
}

.ac-excellence-content {
    position: relative; z-index: 1;
    display: grid; grid-template-columns: auto 1fr auto;
    gap: 30px; align-items: center;
}
.ac-excellence-icon {
    width: 80px; height: 80px; border-radius: 50%;
    background: linear-gradient(135deg, var(--ac-gold), #D97706);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 2rem; flex-shrink: 0;
    box-shadow: 0 8px 24px rgba(245,158,11,0.4);
    animation: acTrophyBounce 3s infinite ease-in-out;
}
@keyframes acTrophyBounce {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(-5deg); }
}

.ac-excellence-text h3 {
    font-size: 1.8rem; margin: 0 0 6px 0; font-weight: 800;
    font-family: var(--ac-font-display);
}
.ac-excellence-text p {
    margin: 0; opacity: 0.85; font-size: 1rem; line-height: 1.5;
}

.ac-excellence-badges {
    display: flex; flex-direction: column; gap: 8px;
}
.ac-excellence-badges span {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 14px; background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px); border-radius: 20px;
    font-size: 0.78rem; font-weight: 700; letter-spacing: 0.5px;
}
.ac-excellence-badges i { color: var(--ac-gold); }

/* ===== MODAL ===== */
.ac-modal {
    position: fixed; inset: 0; z-index: 10000;
    background: rgba(2,6,23,0.9); backdrop-filter: blur(12px);
    display: none; align-items: center; justify-content: center;
    padding: 20px; opacity: 0; transition: opacity 0.3s;
}
.ac-modal.open { display: flex; opacity: 1; }

.ac-modal-content {
    background: #fff; border-radius: 28px; max-width: 700px;
    width: 100%; max-height: 90vh; overflow: hidden;
    box-shadow: 0 30px 60px rgba(0,0,0,0.4);
    position: relative; transform: scale(0.9) translateY(20px);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
body.gl-dark .ac-modal-content { background: #1E293B; }
.ac-modal.open .ac-modal-content { transform: scale(1) translateY(0); }

/* Cosmic Background in Modal */
.ac-modal-cosmic {
    position: absolute; inset: 0; pointer-events: none; overflow: hidden;
}
.ac-modal-stars {
    position: absolute; inset: 0;
    background-image: 
        radial-gradient(2px 2px at 20px 30px, rgba(16,185,129,0.6), transparent),
        radial-gradient(1px 1px at 60px 70px, rgba(245,158,11,0.6), transparent),
        radial-gradient(1px 1px at 100px 40px, rgba(139,92,246,0.6), transparent);
    background-size: 200px 200px;
    opacity: 0.3;
}
.ac-modal-orbit {
    position: absolute; top: -100px; right: -100px;
    width: 300px; height: 300px; border-radius: 50%;
    border: 1px dashed rgba(16,185,129,0.2);
    animation: acOrbitSpin 20s linear infinite;
}

.ac-modal-close {
    position: absolute; top: 20px; right: 20px; z-index: 10;
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);
    border: none; color: var(--ac-dark); font-size: 1.1rem;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all 0.3s;
}
body.gl-dark .ac-modal-close { background: rgba(0,0,0,0.5); color: #fff; }
.ac-modal-close:hover {
    background: #EF4444; color: #fff;
    transform: rotate(90deg) scale(1.1);
}

.ac-modal-inner {
    position: relative; z-index: 1;
    max-height: 90vh; overflow-y: auto;
}
.ac-modal-inner::-webkit-scrollbar { width: 6px; }
.ac-modal-inner::-webkit-scrollbar-thumb { background: var(--ac-primary); border-radius: 3px; }

/* Modal Header */
.ac-modal-header {
    padding: 40px 40px 30px;
    background: linear-gradient(135deg, var(--ac-dark), #1E1B4B);
    color: #fff; display: flex; gap: 24px;
    align-items: center; position: relative;
    border-bottom: 2px solid var(--ac-primary);
}
.ac-modal-header::after {
    content: ''; position: absolute; bottom: -2px; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, var(--ac-primary), var(--ac-gold), var(--ac-purple));
}

.ac-modal-avatar-wrap {
    position: relative; width: 130px; height: 130px; flex-shrink: 0;
}
.ac-modal-avatar-wrap img {
    width: 100%; height: 100%; border-radius: 50%;
    object-fit: cover; border: 4px solid rgba(255,255,255,0.2);
    position: relative; z-index: 2;
}
.ac-avatar-ring {
    position: absolute; inset: -8px; border-radius: 50%;
    border: 2px dashed var(--ac-gold);
    animation: acOrbitSpin 15s linear infinite;
}
.ac-avatar-glow {
    position: absolute; inset: -20px; border-radius: 50%;
    background: radial-gradient(circle, var(--ac-primary), transparent 70%);
    opacity: 0.5; filter: blur(20px); z-index: 0;
    animation: acGlowPulse 3s infinite ease-in-out;
}
@keyframes acGlowPulse {
    0%, 100% { opacity: 0.3; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.1); }
}

.ac-modal-info { flex: 1; min-width: 0; }
.ac-modal-badges {
    display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;
}
.ac-modal-badge-primary {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; background: linear-gradient(135deg, var(--ac-primary), var(--ac-primary-dark));
    color: #fff; border-radius: 20px;
    font-size: 0.7rem; font-weight: 800; letter-spacing: 1px;
    font-family: var(--ac-font-mono);
}
.ac-modal-badge-stars {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 4px 12px; background: rgba(245,158,11,0.2);
    color: var(--ac-gold); border-radius: 20px;
    font-size: 0.7rem; font-weight: 800;
}
.ac-modal-badge-stars i { font-size: 0.65rem; }

.ac-modal-info h2 {
    font-size: 1.8rem; margin: 0 0 4px 0; font-weight: 800;
    font-family: var(--ac-font-display); line-height: 1.2;
}
.ac-modal-gelar {
    font-size: 1rem; opacity: 0.85; margin: 0;
    font-family: var(--ac-font-serif); font-style: italic;
}

/* Modal Meta Grid */
.ac-modal-meta-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
    padding: 20px 40px; background: var(--ac-gray-50);
    border-bottom: 1px solid var(--ac-gray-100);
}
body.gl-dark .ac-modal-meta-grid { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.08); }

.ac-modal-meta-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; background: #fff;
    border-radius: 12px; border: 1px solid var(--ac-gray-100);
}
body.gl-dark .ac-modal-meta-item { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.08); }

.ac-modal-meta-item i {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, var(--ac-primary), var(--ac-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.ac-modal-meta-item div { flex: 1; }
.ac-modal-meta-item span {
    font-size: 0.7rem; color: var(--ac-gray-600);
    text-transform: uppercase; letter-spacing: 0.5px;
    display: block; margin-bottom: 2px;
}
body.gl-dark .ac-modal-meta-item span { color: #94A3B8; }
.ac-modal-meta-item strong {
    font-size: 0.95rem; color: var(--ac-dark);
    font-family: var(--ac-font-mono); font-weight: 700;
    word-break: break-word;
}
body.gl-dark .ac-modal-meta-item strong { color: #F1F5F9; }

/* Modal Details */
.ac-modal-details { padding: 30px 40px; }

.ac-modal-section { margin-bottom: 28px; }
.ac-modal-section:last-child { margin-bottom: 0; }

.ac-modal-section h4 {
    font-size: 1rem; font-weight: 800; color: var(--ac-dark);
    margin: 0 0 16px 0; display: flex; align-items: center; gap: 10px;
    text-transform: uppercase; letter-spacing: 1px;
    font-family: var(--ac-font-display);
}
body.gl-dark .ac-modal-section h4 { color: #F1F5F9; }
.ac-modal-section h4 i {
    color: var(--ac-primary); font-size: 1.1rem;
}

.ac-modal-text {
    font-size: 0.95rem; line-height: 1.8; color: var(--ac-gray-700);
    margin: 0; padding: 20px;
    background: var(--ac-gray-50); border-radius: 12px;
    border-left: 4px solid var(--ac-primary);
}
body.gl-dark .ac-modal-text { background: rgba(255,255,255,.03); color: #CBD5E1; }

/* Academic Metrics */
.ac-modal-metrics {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
.ac-metric {
    text-align: center; padding: 16px 8px;
    background: var(--ac-gray-50); border-radius: 14px;
    border: 1px solid var(--ac-gray-100);
}
body.gl-dark .ac-metric { background: rgba(255,255,255,.03); border-color: rgba(255,255,255,.08); }

.ac-metric-circle {
    --progress: 0%;
    width: 70px; height: 70px; border-radius: 50%;
    margin: 0 auto 10px; position: relative;
    background: conic-gradient(var(--ac-primary) calc(var(--progress) * 1%), var(--ac-gray-100) 0);
    display: flex; align-items: center; justify-content: center;
}
body.gl-dark .ac-metric-circle { background: conic-gradient(var(--ac-primary) calc(var(--progress) * 1%), rgba(255,255,255,.1) 0); }
.ac-metric-circle::before {
    content: ''; position: absolute; inset: 6px;
    border-radius: 50%; background: #fff;
}
body.gl-dark .ac-metric-circle::before { background: #1E293B; }
.ac-metric-circle span {
    position: relative; font-family: var(--ac-font-display);
    font-size: 1.1rem; font-weight: 800; color: var(--ac-dark);
}
body.gl-dark .ac-metric-circle span { color: #F1F5F9; }

.ac-metric-label {
    font-size: 0.7rem; color: var(--ac-gray-600);
    text-transform: uppercase; letter-spacing: 0.5px;
    font-weight: 600;
}
body.gl-dark .ac-metric-label { color: #94A3B8; }

/* Contact Button */
.ac-contact-btn {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 20px; background: linear-gradient(135deg, var(--ac-primary), var(--ac-primary-dark));
    color: #fff; border-radius: 12px;
    text-decoration: none; font-weight: 700; font-size: 0.95rem;
    transition: all 0.3s;
    box-shadow: 0 4px 16px rgba(16,185,129,0.3);
}
.ac-contact-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16,185,129,0.4);
}
.ac-contact-btn i:first-child { font-size: 1.1rem; }
.ac-contact-btn span { flex: 1; word-break: break-all; }
.ac-contact-arrow { transition: transform 0.3s; }
.ac-contact-btn:hover .ac-contact-arrow { transform: translateX(4px); }

/* ===== TOAST ===== */
.ac-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--ac-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    z-index: 10001; opacity: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.9rem;
}
body.gl-dark .ac-toast { background: #fff; color: var(--ac-dark); }
.ac-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.ac-toast i { color: var(--ac-gold); font-size: 1.2rem; }

/* ===== ANIMATIONS ===== */
.fade-up {
    opacity: 0; transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .ac-academic-stats { grid-template-columns: repeat(2, 1fr); }
    .ac-excellence-content { grid-template-columns: 1fr; text-align: center; }
    .ac-excellence-icon { margin: 0 auto; }
    .ac-excellence-badges { flex-direction: row; justify-content: center; flex-wrap: wrap; }
    .ac-modal-meta-grid { grid-template-columns: 1fr; }
    .ac-modal-metrics { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .ac-hero { padding: 120px 20px 80px; min-height: 85vh; }
    .ac-hero-title { font-size: 3rem; }
    .ac-academic-stats { grid-template-columns: 1fr; gap: 10px; }
    .ac-academic-stat { justify-content: center; }
    .ac-floating-symbols { display: none; }
    .ac-console-body { padding: 16px; }
    .ac-search-hint { display: none; }
    .ac-search-wrap input { padding-right: 16px; }
    .ac-galaxy-pills { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 6px; }
    .ac-galaxy-pill { white-space: nowrap; flex-shrink: 0; }
    .ac-results-info { flex-direction: column; gap: 8px; align-items: flex-start; }
    .ac-grid { grid-template-columns: 1fr; }
    .ac-card-img { height: 260px; }
    .ac-modal-header { padding: 30px 20px; flex-direction: column; text-align: center; }
    .ac-modal-avatar-wrap { width: 110px; height: 110px; margin: 0 auto; }
    .ac-modal-info h2 { font-size: 1.4rem; }
    .ac-modal-meta-grid { padding: 16px 20px; }
    .ac-modal-details { padding: 20px; }
    .ac-modal-metrics { grid-template-columns: 1fr 1fr; gap: 10px; }
    .ac-metric { padding: 12px 6px; }
    .ac-metric-circle { width: 60px; height: 60px; }
    .ac-metric-circle span { font-size: 0.95rem; }
    .ac-excellence-banner { padding: 30px 20px; }
    .ac-excellence-text h3 { font-size: 1.4rem; }
}
@media (max-width: 480px) {
    .ac-hero-title { font-size: 2.3rem; }
    .ac-hero-badge { font-size: 0.7rem; padding: 8px 16px; letter-spacing: 1px; }
    .ac-card { border-radius: 18px; }
    .ac-card-name { font-size: 1.1rem; }
    .ac-modal-content { border-radius: 20px; }
    .ac-modal-info h2 { font-size: 1.2rem; }
    .ac-metric-circle { width: 50px; height: 50px; }
    .ac-metric-circle span { font-size: 0.85rem; }
    .ac-modal-metrics { grid-template-columns: 1fr 1fr; }
    .ac-ac-stat-num { font-size: 1.3rem; }
}

/* ============================================================
   PERFORMANCE PATCH - ACADEMIC CONSTELLATION SMOOTH SCROLL
============================================================ */

/* Content visibility untuk section bawah */
.ac-section {
    content-visibility: auto;
    contain-intrinsic-size: auto 2000px;
}

/* Pause animasi saat scroll */
body.is-scrolling .ac-nebula,
body.is-scrolling .ac-stars-bg,
body.is-scrolling .ac-shooting-stars,
body.is-scrolling .ac-float-symbol,
body.is-scrolling .ac-progress-fill,
body.is-scrolling .ac-badge-glow,
body.is-scrolling .ac-ac-pulse,
body.is-scrolling .ac-orbit-ring,
body.is-scrolling .ac-excellence-icon,
body.is-scrolling .ac-avatar-ring,
body.is-scrolling .ac-avatar-glow {
    animation-play-state: paused !important;
}

/* Pause animasi untuk section yang tidak terlihat */
.ac-offscreen * {
    animation-play-state: paused !important;
}

/* Optimasi elemen yang sering bergerak */
.ac-nebula,
.ac-float-symbol,
.ac-progress-fill {
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
    .ac-nebula,
    .ac-shooting-stars {
        animation: none !important;
        opacity: 0.15 !important;
    }
    
    .ac-floating-symbols {
        display: none !important;
    }
    
    .ac-hero-bg {
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
    
    .ac-nebula,
    .ac-shooting-stars,
    .ac-floating-symbols {
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
                entry.target.classList.toggle('ac-offscreen', !entry.isIntersecting);
            });
        }, {
            rootMargin: '300px 0px',
            threshold: 0
        });

        document.querySelectorAll('.ac-hero, .ac-section').forEach(section => {
            sectionObserver.observe(section);
        });

        const heroSection = document.querySelector('.ac-hero');
        if (heroSection) {
            window.__acHeroVisible = true;
            new IntersectionObserver((entries) => {
                const entry = entries[0];
                window.__acHeroVisible = entry ? entry.isIntersecting : true;
                window.dispatchEvent(new CustomEvent('ac:hero-visibility', {
                    detail: window.__acHeroVisible
                }));
            }, {
                threshold: 0.01
            }).observe(heroSection);
        }
    }

    // ===== 1. Constellation Canvas Animation (OPTIMIZED) =====
    const canvas = document.getElementById('acConstellationCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d', { alpha: true });
        let width, height, stars = [], time = 0;
        let running = true;
        let lastFrame = 0;
        const frameInterval = 1000 / 30; // Limit 30 FPS

        function resize() {
            width = canvas.width = canvas.parentElement.offsetWidth;
            height = canvas.height = canvas.parentElement.offsetHeight;
            initStars();
        }

        function initStars() {
            stars = [];
            // Batasi jumlah bintang maksimal 120 untuk performa
            const numStars = Math.min(Math.floor((width * height) / 10000), 120);
            for (let i = 0; i < numStars; i++) {
                stars.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    size: Math.random() * 1.5 + 0.5,
                    opacity: Math.random() * 0.7 + 0.2,
                    twinkleSpeed: Math.random() * 0.02 + 0.01,
                    twinklePhase: Math.random() * Math.PI * 2,
                    color: ['#10B981', '#F59E0B', '#8B5CF6', '#06B6D4', '#FFFFFF'][Math.floor(Math.random() * 5)]
                });
            }
        }

        function drawStar(star) {
            const twinkle = Math.sin(time * star.twinkleSpeed + star.twinklePhase) * 0.5 + 0.5;
            const opacity = star.opacity * twinkle;
            
            ctx.beginPath();
            ctx.arc(star.x, star.y, star.size, 0, Math.PI * 2);
            ctx.fillStyle = star.color;
            ctx.globalAlpha = opacity;
            // HAPUS shadowBlur - penyebab utama lag!
            ctx.fill();
            ctx.globalAlpha = 1;
        }

        function drawConstellations() {
            const maxDist = 120;
            const maxDistSq = maxDist * maxDist;
            for (let i = 0; i < stars.length; i++) {
                for (let j = i + 1; j < stars.length; j++) {
                    const dx = stars[i].x - stars[j].x;
                    const dy = stars[i].y - stars[j].y;
                    const distSq = dx * dx + dy * dy; // Hindari sqrt untuk pasangan jauh
                    if (distSq < maxDistSq) {
                        const distance = Math.sqrt(distSq);
                        const opacity = (1 - distance / maxDist) * 0.2;
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(16, 185, 129, ${opacity})`;
                        ctx.lineWidth = 0.5;
                        ctx.moveTo(stars[i].x, stars[i].y);
                        ctx.lineTo(stars[j].x, stars[j].y);
                        ctx.stroke();
                    }
                }
            }
        }

        let shootingStars = [];
        function addShootingStar() {
            // Kurangi probabilitas shooting star
            if (Math.random() < 0.003 && shootingStars.length < 2) {
                shootingStars.push({
                    x: Math.random() * width,
                    y: Math.random() * height * 0.5,
                    length: Math.random() * 70 + 35,
                    speed: Math.random() * 7 + 5,
                    angle: Math.PI / 4 + Math.random() * 0.3,
                    life: 1
                });
            }
        }

        function drawShootingStars() {
            shootingStars = shootingStars.filter(s => s.life > 0);
            shootingStars.forEach(s => {
                const endX = s.x + Math.cos(s.angle) * s.length;
                const endY = s.y + Math.sin(s.angle) * s.length;
                
                const gradient = ctx.createLinearGradient(s.x, s.y, endX, endY);
                gradient.addColorStop(0, `rgba(255,255,255,${s.life})`);
                gradient.addColorStop(1, 'rgba(255,255,255,0)');
                
                ctx.beginPath();
                ctx.strokeStyle = gradient;
                ctx.lineWidth = 1.5;
                ctx.moveTo(s.x, s.y);
                ctx.lineTo(endX, endY);
                ctx.stroke();
                
                s.x += Math.cos(s.angle) * s.speed;
                s.y += Math.sin(s.angle) * s.speed;
                s.life -= 0.02;
            });
        }

        function animate(currentTime) {
            requestAnimationFrame(animate);
            // Stop render jika tab hidden atau hero tidak terlihat
            if (!running || document.hidden || window.__acHeroVisible === false) {
                return;
            }
            // Frame limiter 30 FPS
            const elapsed = currentTime - lastFrame;
            if (elapsed < frameInterval) return;
            lastFrame = currentTime - (elapsed % frameInterval);

            ctx.clearRect(0, 0, width, height);
            drawConstellations();
            stars.forEach(drawStar);
            addShootingStar();
            drawShootingStars();
            time++;
        }

        window.addEventListener('ac:hero-visibility', function(e) {
            running = Boolean(e.detail);
        });

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                running = false;
            } else if (window.__acHeroVisible) {
                running = true;
            }
        });

        resize();
        window.addEventListener('resize', resize, { passive: true });
        requestAnimationFrame(animate);
    }

    // ===== 2. Typing Effect =====
    const textElement = document.getElementById('acTypingText');
    if (textElement) {
        const phrases = [
            "Dibimbing oleh para ahli dan praktisi berpengalaman di bidangnya.",
            "Kenali dosen berkualitas yang siap membentuk Anda menjadi inovator teknologi.",
            "Setiap dosen adalah bintang dalam konstelasi akademik Informatika Pro.",
            "Dari peneliti hingga praktisi industri - semua ada di sini."
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
    const counters = document.querySelectorAll('.ac-ac-stat-num[data-count]');
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
                    el.textContent = current;
                    if (progress < 1) requestAnimationFrame(update);
                    else el.textContent = target;
                }
                requestAnimationFrame(update);
                counterObs.unobserve(el);
            }
        });
    }, { threshold: 0.5 });
    counters.forEach(c => counterObs.observe(c));

    // ===== 4. Reading Progress Bar (THROTTLED) =====
    const progressFill = document.getElementById('acProgressFill');
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

    // ===== 6. Live Search (DEBOUNCED) =====
    const searchInput = document.getElementById('acSearchInput');
    const grid = document.getElementById('acGrid');
    const emptySearch = document.getElementById('acEmptySearch');
    const resultsCount = document.getElementById('acResultsCount');

    if (searchInput && grid) {
        let searchDebounce;
        searchInput.addEventListener('input', (e) => {
            const value = e.target.value;
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                const query = value.toLowerCase().trim();
                const cards = grid.querySelectorAll('.ac-card');
                let visibleCount = 0;

                cards.forEach(card => {
                    const name = card.getAttribute('data-name') || '';
                    const expertise = card.getAttribute('data-expertise') || '';
                    
                    if (!query || name.includes(query) || expertise.includes(query)) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (resultsCount) resultsCount.textContent = visibleCount;
                
                if (emptySearch) {
                    if (query && visibleCount === 0) {
                        emptySearch.style.display = 'block';
                        grid.style.display = 'none';
                    } else {
                        emptySearch.style.display = 'none';
                        grid.style.display = '';
                    }
                }
            }, 120); // Debounce 120ms
        });
    }

    window.acResetSearch = function() {
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        }
    };

    // ===== 7. Keyboard Shortcut =====
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) searchInput.focus();
        }
        if (e.key === 'Escape') {
            acCloseModal();
        }
    });

    // ===== 8. Modal Functions =====
    const modal = document.getElementById('acModal');

    window.acOpenModal = function(data) {
        if (!modal || !data) return;

        const set = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val || '-'; };
        const setSrc = (id, val) => { const el = document.getElementById(id); if(el) el.src = val; };
        const setHtml = (id, val) => { const el = document.getElementById(id); if(el) el.innerHTML = val; };

        setSrc('acModalFoto', data.foto);
        set('acModalNama', data.nama);
        set('acModalGelar', data.gelar);
        set('acModalNidn', data.nidn);
        set('acModalKeahlian', data.keahlian);
        set('acModalProfil', data.profil);
        set('acModalEmail', data.email);

        const starsHtml = Array(data.brightness || 5).fill('<i class="fas fa-star"></i>').join('');
        setHtml('acModalStars', starsHtml);

        const emailLink = document.getElementById('acModalEmailLink');
        if (emailLink) {
            emailLink.href = data.email && data.email !== '-' ? `mailto:${data.email}` : '#';
        }

        const glow = document.getElementById('acModalGlow');
        if (glow && data.color) {
            glow.style.background = `radial-gradient(circle, ${data.color}, transparent 70%)`;
        }

        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.acCloseModal = function() {
        if (modal) {
            modal.classList.remove('open');
            document.body.style.overflow = '';
        }
    };

    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) acCloseModal();
        });
    }

    // ===== 9. Toast =====
    window.acShowToast = function(message) {
        const toast = document.getElementById('acToast');
        if (!toast) return;
        const msgEl = document.getElementById('acToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // ===== 10. Parallax Nebulas (THROTTLED) =====
    const heroSection = document.querySelector('.ac-hero');
    let parallaxTicking = false;
    if (heroSection && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            if (!parallaxTicking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.pageYOffset;
                    const nebulas = heroSection.querySelectorAll('.ac-nebula');
                    nebulas.forEach((neb, index) => {
                        const speed = (index + 1) * 0.2;
                        neb.style.transform = `translateY(${scrolled * speed}px)`;
                    });
                    parallaxTicking = false;
                });
                parallaxTicking = true;
            }
        }, { passive: true });
    }

    setTimeout(() => {
        acShowToast('✨ Welcome to Academic Constellation!');
    }, 1500);

    console.log('%c╔══════════════════════════════════════════╗', 'color:#F59E0B;font-weight:bold');
    console.log('%c║   ACADEMIC CONSTELLATION - Our Scholars  ║', 'color:#F59E0B;font-weight:bold;font-size:14px');
    console.log('%c╚══════════════════════════════════════════╝', 'color:#F59E0B;font-weight:bold');
    console.log('%c🌟 ' + document.querySelectorAll('.ac-card').length + ' scholars in our constellation (OPTIMIZED)', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>