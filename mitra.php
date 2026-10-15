<?php
require_once 'includes/init_security.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Mitra Kerja Sama';
$pageDesc = 'Jaringan mitra industri, universitas, pemerintah, dan komunitas yang mendukung Program Studi Informatika.';

// ===== FILTER PARAMETERS =====
$filterJenis = isset($_GET['jenis']) ? clean($_GET['jenis']) : '';

// ===== FETCH DATA DENGAN HELPER =====
try {
    $filters = [];
    if ($filterJenis) {
        $filters['jenis'] = $filterJenis;
    }
    
    $mitraList = getMitra($filters);
    $mitraStats = getMitraStats();
    
} catch (Throwable $e) {
    $mitraList = [];
    $mitraStats = ['total' => 0, 'industri' => 0, 'universitas' => 0, 'pemerintah' => 0, 'komunitas' => 0];
}

// ===== JENIS CONFIG =====
$jenisConfig = [
    'industri' => ['icon' => 'fa-industry', 'color' => '#3B82F6', 'label' => 'Industri', 'bg' => 'rgba(59,130,246,0.12)'],
    'universitas' => ['icon' => 'fa-university', 'color' => '#8B5CF6', 'label' => 'Universitas', 'bg' => 'rgba(139,92,246,0.12)'],
    'pemerintah' => ['icon' => 'fa-landmark', 'color' => '#EF4444', 'label' => 'Pemerintah', 'bg' => 'rgba(239,68,68,0.12)'],
    'komunitas' => ['icon' => 'fa-users', 'color' => '#10B981', 'label' => 'Komunitas', 'bg' => 'rgba(16,185,129,0.12)'],
];

function getJenisInfo($jenis) {
    global $jenisConfig;
    return $jenisConfig[$jenis] ?? $jenisConfig['industri'];
}

// ===== STATISTICS =====
$totalMitra = $mitraStats['total'];
?>

<!-- ===== READING PROGRESS ===== -->
<div class="mt-reading-progress">
    <div class="mt-progress-fill" id="mtProgressFill"></div>
</div>

<!-- ===== HERO SECTION ===== -->
<section class="mt-hero">
    <canvas id="mtNetworkCanvas" class="mt-network-canvas"></canvas>
    <div class="mt-hero-bg">
        <div class="mt-nebula mt-nebula-1"></div>
        <div class="mt-nebula mt-nebula-2"></div>
        <div class="mt-nebula mt-nebula-3"></div>
        <div class="mt-stars-bg"></div>
        <div class="mt-connection-lines"></div>
    </div>
    
    <!-- Floating Partnership Icons -->
    <div class="mt-floating-icons">
        <span class="mt-float-icon" style="--delay: 0s; --x: 10%; --y: 20%; --rot: 10deg;">🤝</span>
        <span class="mt-float-icon" style="--delay: 1.5s; --x: 85%; --y: 25%; --rot: -15deg;">🏢</span>
        <span class="mt-float-icon" style="--delay: 0.8s; --x: 15%; --y: 70%; --rot: 20deg;">🎓</span>
        <span class="mt-float-icon" style="--delay: 2.2s; --x: 88%; --y: 65%; --rot: -10deg;">🏛️</span>
        <span class="mt-float-icon" style="--delay: 3s; --x: 50%; --y: 15%; --rot: 5deg;">👥</span>
        <span class="mt-float-icon" style="--delay: 1.2s; --x: 45%; --y: 85%; --rot: -20deg;">🌐</span>
    </div>
    
    <div class="container mt-hero-content">
        <span class="mt-hero-badge fade-up">
            <span class="mt-badge-glow"></span>
            <i class="fas fa-handshake"></i> PARTNERSHIP NETWORK
        </span>
        
        <h1 class="mt-hero-title fade-up">
            <span class="mt-title-line">Mitra</span>
            <span class="mt-title-line mt-title-network">Kerja Sama</span>
        </h1>
        
        <p class="mt-hero-subtitle fade-up">
            <span id="mtTypingText"></span><span class="mt-cursor">|</span>
        </p>
        
        <!-- Mitra Stats -->
        <div class="mt-hero-stats fade-up">
            <div class="mt-stat-pill">
                <i class="fas fa-handshake"></i>
                <div>
                    <span class="mt-stat-num" data-count="<?= $totalMitra ?>">0</span>
                    <span class="mt-stat-label">Total Mitra</span>
                </div>
            </div>
            <div class="mt-stat-pill">
                <i class="fas fa-industry"></i>
                <div>
                    <span class="mt-stat-num" data-count="<?= $mitraStats['industri'] ?>">0</span>
                    <span class="mt-stat-label">Industri</span>
                </div>
            </div>
            <div class="mt-stat-pill">
                <i class="fas fa-university"></i>
                <div>
                    <span class="mt-stat-num" data-count="<?= $mitraStats['universitas'] ?>">0</span>
                    <span class="mt-stat-label">Universitas</span>
                </div>
            </div>
            <div class="mt-stat-pill">
                <i class="fas fa-landmark"></i>
                <div>
                    <span class="mt-stat-num" data-count="<?= $mitraStats['pemerintah'] ?>">0</span>
                    <span class="mt-stat-label">Pemerintah</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-scroll-indicator">
        <span>Explore Our Partners</span>
        <div class="mt-scroll-arrow">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<section class="mt-main-section">
    <div class="container">
        
        <!-- Filter Console -->
        <div class="mt-filter-console fade-up">
            <div class="mt-console-header">
                <div class="mt-console-dots">
                    <span class="dot red"></span>
                    <span class="dot yellow"></span>
                    <span class="dot green"></span>
                </div>
                <i class="fas fa-network-wired"></i>
                <span>partner_network.exe</span>
                <span class="mt-console-status">● CONNECTING</span>
            </div>
            
            <div class="mt-console-body">
                <!-- Search Bar -->
                <div class="mt-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="mtSearchInput" placeholder="Cari mitra by nama, website, atau deskripsi..." autocomplete="off">
                    <span class="mt-search-hint">
                        <kbd>Ctrl</kbd>+<kbd>K</kbd>
                    </span>
                </div>
                
                <!-- Jenis Filter Pills -->
                <div class="mt-filter-group">
                    <label><i class="fas fa-layer-group"></i> Jenis Mitra:</label>
                    <div class="mt-filter-pills">
                        <a href="mitra.php" class="mt-pill <?= !$filterJenis ? 'active' : '' ?>">
                            <i class="fas fa-infinity"></i> Semua
                        </a>
                        <?php foreach ($jenisConfig as $key => $cfg): ?>
                            <a href="?jenis=<?= $key ?>" 
                               class="mt-pill <?= $filterJenis === $key ? 'active' : '' ?>"
                               style="--pill-color: <?= $cfg['color'] ?>;">
                                <i class="fas <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Results Info -->
                <div class="mt-filter-actions">
                    <span class="mt-results-info">
                        <i class="fas fa-building"></i>
                        <strong id="mtResultsCount"><?= count($mitraList) ?></strong> mitra ditemukan
                    </span>
                    <button class="mt-btn-reset" onclick="mtResetFilters()">
                        <i class="fas fa-redo"></i> Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Mitra Grid -->
        <?php if (!empty($mitraList)): ?>
            <div class="mt-grid" id="mtGrid">
                <?php foreach ($mitraList as $index => $m): 
                    $jenisCfg = getJenisInfo($m['jenis']);
                ?>
                <div class="mt-card fade-up" 
                     data-nama="<?= strtolower(clean($m['nama'] ?? '')) ?>"
                     data-website="<?= strtolower(clean($m['website'] ?? '')) ?>"
                     data-deskripsi="<?= strtolower(clean($m['deskripsi'] ?? '')) ?>"
                     style="animation-delay: <?= $index * 0.05 ?>s; --card-accent: <?= $jenisCfg['color'] ?>;"
                     onclick='mtOpenModal(<?= json_encode($m, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>)'>
                    
                    <!-- Card Glow -->
                    <div class="mt-card-glow"></div>
                    
                    <!-- Card Header (Logo) -->
                    <div class="mt-card-header">
                        <div class="mt-logo-wrap">
                            <?php if (!empty($m['logo'])): ?>
                                <img src="uploads/<?= htmlspecialchars($m['logo']) ?>" alt="<?= clean($m['nama']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="mt-logo-placeholder" style="background: <?= $jenisCfg['bg'] ?>; color: <?= $jenisCfg['color'] ?>;">
                                    <i class="fas <?= $jenisCfg['icon'] ?>"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Jenis Badge -->
                        <div class="mt-jenis-badge" style="background: <?= $jenisCfg['bg'] ?>; color: <?= $jenisCfg['color'] ?>;">
                            <i class="fas <?= $jenisCfg['icon'] ?>"></i>
                            <span><?= $jenisCfg['label'] ?></span>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="mt-card-body">
                        <h3 class="mt-card-name"><?= clean($m['nama'] ?? 'Tanpa Nama') ?></h3>
                        
                        <?php if (!empty($m['deskripsi'])): ?>
                        <p class="mt-card-desc"><?= clean(mb_substr($m['deskripsi'], 0, 80)) ?><?= strlen($m['deskripsi'] ?? '') > 80 ? '...' : '' ?></p>
                        <?php else: ?>
                        <p class="mt-card-desc mt-no-desc">Belum ada deskripsi.</p>
                        <?php endif; ?>
                        
                        <!-- Website Link -->
                        <?php if (!empty($m['website'])): ?>
                        <a href="<?= htmlspecialchars($m['website']) ?>" target="_blank" rel="noopener" 
                           class="mt-website-btn" style="color: <?= $jenisCfg['color'] ?>;"
                           onclick="event.stopPropagation()">
                            <i class="fas fa-external-link-alt"></i>
                            <span>Kunjungi Website</span>
                        </a>
                        <?php else: ?>
                        <span class="mt-no-website">
                            <i class="fas fa-link-slash"></i>
                            <span>Tidak ada website</span>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="mt-card-footer">
                        <span class="mt-footer-text">
                            <i class="fas fa-eye"></i>
                            Lihat Detail
                        </span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Empty Search State -->
            <div class="mt-empty-search" id="mtEmptySearch" style="display: none;">
                <div class="mt-empty-icon">
                    <i class="fas fa-search"></i>
                    <div class="mt-empty-pulse"></div>
                </div>
                <h3>Tidak Ada Hasil</h3>
                <p>Mitra yang Anda cari tidak ditemukan. Coba kata kunci lain.</p>
                <button class="mt-btn-primary" onclick="mtResetFilters()">
                    <i class="fas fa-redo"></i> Reset Pencarian
                </button>
            </div>
            
        <?php else: ?>
            <div class="mt-empty-state fade-up">
                <div class="mt-empty-icon">
                    <i class="fas fa-building"></i>
                    <div class="mt-empty-pulse"></div>
                </div>
                <h3>Belum Ada Data Mitra</h3>
                <p>Data mitra akan segera ditambahkan oleh administrator.</p>
                <?php if ($filterJenis): ?>
                <button class="mt-btn-primary" onclick="mtResetFilters()">
                    <i class="fas fa-redo"></i> Reset Filter
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Jenis Distribution Chart -->
        <?php if ($totalMitra > 0): ?>
        <div class="mt-distribution-section fade-up">
            <h2 class="mt-section-title">
                <i class="fas fa-chart-pie"></i>
                Distribusi Jenis Mitra
            </h2>
            
            <div class="mt-distribution-card">
                <div class="mt-donut-wrap">
                    <svg viewBox="0 0 36 36" class="mt-donut-ring">
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--mt-gray-200)" stroke-width="3.8"/>
                        <?php 
                        $offset = 0;
                        foreach ($jenisConfig as $key => $cfg):
                            $count = $mitraStats[$key] ?? 0;
                            if ($count > 0):
                                $pct = ($count / $totalMitra) * 100;
                                $dashArray = ($pct * 2.83) . ', ' . (100 - ($pct * 2.83));
                        ?>
                        <circle cx="18" cy="18" r="15.9" fill="none" 
                            stroke="<?= $cfg['color'] ?>" 
                            stroke-width="3.8"
                            stroke-dasharray="<?= $dashArray ?>"
                            stroke-dashoffset="-<?= $offset ?>"
                            transform="rotate(-90 18 18)"/>
                        <?php 
                                $offset += ($pct * 2.83);
                            endif;
                        endforeach; 
                        ?>
                    </svg>
                    <div class="mt-donut-center">
                        <div class="mt-donut-value"><?= $totalMitra ?></div>
                        <div class="mt-donut-label">Total</div>
                    </div>
                </div>
                
                <div class="mt-distribution-legend">
                    <?php foreach ($jenisConfig as $key => $cfg): 
                        $count = $mitraStats[$key] ?? 0;
                        $pct = $totalMitra > 0 ? round(($count / $totalMitra) * 100) : 0;
                    ?>
                    <div class="mt-legend-item">
                        <span class="mt-legend-dot" style="background: <?= $cfg['color'] ?>"></span>
                        <span class="mt-legend-name">
                            <i class="fas <?= $cfg['icon'] ?>"></i>
                            <?= $cfg['label'] ?>
                        </span>
                        <span class="mt-legend-count"><?= $count ?> (<?= $pct ?>%)</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== MODAL DETAIL ===== -->
<div class="mt-modal" id="mtModal">
    <div class="mt-modal-content">
        <button class="mt-modal-close" onclick="mtCloseModal()" aria-label="Close modal">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="mt-modal-inner">
            <!-- Modal Hero -->
            <div class="mt-modal-hero" id="mtModalHero">
                <div class="mt-modal-logo-wrap">
                    <img src="" alt="Logo Mitra" id="mtModalLogo">
                    <div class="mt-modal-logo-ring"></div>
                </div>
                <div class="mt-modal-hero-info">
                    <h2 id="mtModalNama"></h2>
                    <div class="mt-modal-jenis" id="mtModalJenis"></div>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="mt-modal-body">
                <!-- Info Grid -->
                <div class="mt-modal-info-grid">
                    <div class="mt-modal-info-item">
                        <i class="fas fa-tag"></i>
                        <div>
                            <span>Jenis Mitra</span>
                            <strong id="mtModalJenisText"></strong>
                        </div>
                    </div>
                    <div class="mt-modal-info-item">
                        <i class="fas fa-sort-numeric-up"></i>
                        <div>
                            <span>Urutan Tampil</span>
                            <strong id="mtModalUrutan"></strong>
                        </div>
                    </div>
                    <div class="mt-modal-info-item">
                        <i class="fas fa-calendar"></i>
                        <div>
                            <span>Ditambahkan</span>
                            <strong id="mtModalCreated"></strong>
                        </div>
                    </div>
                    <div class="mt-modal-info-item" id="mtModalWebsiteWrap" style="display: none;">
                        <i class="fas fa-globe"></i>
                        <div>
                            <span>Website</span>
                            <a href="#" id="mtModalWebsiteLink" target="_blank" rel="noopener" class="mt-modal-website-link">
                                <span id="mtModalWebsiteText"></span>
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Deskripsi -->
                <div class="mt-modal-desc" id="mtModalDescWrap" style="display: none;">
                    <h4>
                        <i class="fas fa-align-left"></i>
                        <span>Deskripsi Kerja Sama</span>
                    </h4>
                    <p id="mtModalDesc"></p>
                </div>
                
                <!-- Website Button -->
                <div class="mt-modal-actions" id="mtModalActions" style="display: none;">
                    <a href="#" id="mtModalWebsiteBtn" target="_blank" rel="noopener" class="mt-btn-website">
                        <i class="fas fa-globe"></i>
                        <span>Kunjungi Website Mitra</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="mtToast" class="mt-toast">
    <i class="fas fa-info-circle"></i>
    <span id="mtToastMsg">Mitra berhasil dimuat</span>
</div>

<!-- ============================================================
     STYLES (prefix: mt- = Mitra Network)
============================================================ -->
<style>
:root {
    --mt-primary: #3B82F6;
    --mt-primary-dark: #1E40AF;
    --mt-primary-light: #DBEAFE;
    --mt-indigo: #6366F1;
    --mt-indigo-dark: #4338CA;
    --mt-emerald: #10B981;
    --mt-emerald-dark: #047857;
    --mt-purple: #8B5CF6;
    --mt-red: #EF4444;
    --mt-dark: #0F172A;
    --mt-gray-50: #F8FAFC;
    --mt-gray-100: #F1F5F9;
    --mt-gray-200: #E2E8F0;
    --mt-gray-600: #475569;
    --mt-gray-700: #374151;
    --mt-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --mt-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --mt-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
    --mt-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --mt-font-display: 'Space Grotesk', 'Inter', sans-serif;
}

body.gl-dark {
    --mt-gray-50: #0B1120;
    --mt-gray-100: rgba(255,255,255,.08);
    --mt-gray-200: rgba(255,255,255,.1);
    --mt-gray-600: #94A3B8;
    --mt-gray-700: #CBD5E1;
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

/* ===== READING PROGRESS ===== */
.mt-reading-progress {
    position: fixed; top: 0; left: 0; right: 0; height: 3px;
    background: rgba(59,130,246,0.1); z-index: 9998;
}
.mt-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--mt-primary), var(--mt-indigo), var(--mt-emerald));
    box-shadow: 0 0 10px rgba(59,130,246,0.6);
    transition: width 0.1s ease-out;
}

/* ===== HERO ===== */
.mt-hero {
    background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 50%, #0F172A 100%);
    padding: 160px 20px 120px; position: relative; overflow: hidden;
    min-height: 95vh; display: flex; align-items: center; justify-content: center;
}

.mt-network-canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    z-index: 1; opacity: 0.6;
}

.mt-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }

.mt-nebula {
    position: absolute; border-radius: 50%;
    filter: blur(100px); opacity: 0.3;
    animation: mtNebulaFloat 20s infinite ease-in-out;
}
.mt-nebula-1 { width: 600px; height: 600px; background: var(--mt-primary); top: -200px; right: -100px; }
.mt-nebula-2 { width: 500px; height: 500px; background: var(--mt-indigo); bottom: -150px; left: -100px; animation-delay: -7s; }
.mt-nebula-3 { width: 400px; height: 400px; background: var(--mt-emerald); top: 40%; left: 30%; animation-delay: -12s; }
@keyframes mtNebulaFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(40px, -40px) scale(1.1); }
}

.mt-stars-bg {
    position: absolute; inset: 0;
    background-image: 
        radial-gradient(2px 2px at 20px 30px, white, transparent),
        radial-gradient(2px 2px at 60px 70px, rgba(255,255,255,0.8), transparent),
        radial-gradient(1px 1px at 50px 160px, white, transparent),
        radial-gradient(1px 1px at 130px 40px, rgba(255,255,255,0.6), transparent),
        radial-gradient(2px 2px at 90px 120px, white, transparent);
    background-size: 200px 200px;
    animation: mtStarsTwinkle 8s infinite;
}
@keyframes mtStarsTwinkle {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 0.8; }
}

/* Floating Icons */
.mt-floating-icons { position: absolute; inset: 0; z-index: 3; pointer-events: none; }
.mt-float-icon {
    position: absolute; font-size: 2.5rem;
    animation: mtFloatIcon 10s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
    opacity: 0.35; filter: drop-shadow(0 0 15px rgba(59,130,246,0.4));
    transform: rotate(var(--rot));
}
@keyframes mtFloatIcon {
    0%, 100% { transform: translateY(0) rotate(var(--rot)); opacity: 0.35; }
    50% { transform: translateY(-40px) rotate(calc(var(--rot) + 20deg)); opacity: 0.7; }
}

.mt-hero-content {
    position: relative; z-index: 4; text-align: center;
    color: #fff; max-width: 1000px; margin: 0 auto;
}

.mt-hero-badge {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 10px 24px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    font-size: .85rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 700; letter-spacing: 2px; position: relative;
    font-family: var(--mt-font-mono);
}
.mt-badge-glow {
    position: absolute; inset: 0; border-radius: 50px;
    border: 2px solid var(--mt-primary);
    animation: mtBadgeGlow 2s infinite;
}
@keyframes mtBadgeGlow { 
    0% { transform: scale(1); opacity: 0.8; box-shadow: 0 0 0 0 rgba(59,130,246,0.4); } 
    100% { transform: scale(1.15); opacity: 0; box-shadow: 0 0 30px 10px rgba(59,130,246,0); } 
}
.mt-hero-badge i { color: var(--mt-primary); }

.mt-hero-title {
    font-size: clamp(3rem, 9vw, 6.5rem); font-weight: 900;
    margin-bottom: 24px; letter-spacing: -0.03em; line-height: 0.95;
    font-family: var(--mt-font-display);
}
.mt-title-line { display: block; }
.mt-title-network {
    position: relative; display: inline-block;
    background: linear-gradient(135deg, #F59E0B 0%, #3B82F6 50%, #10B981 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 200%;
    animation: mtNetworkGradient 5s ease infinite;
}
.mt-title-network::after {
    content: ''; position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 120%; height: 120%;
    background: radial-gradient(circle, rgba(59,130,246,0.15), transparent 60%);
    z-index: -1; border-radius: 50%;
    animation: mtNetworkGlow 3s ease-in-out infinite;
}
@keyframes mtNetworkGradient { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
@keyframes mtNetworkGlow { 0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); } 50% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); } }

.mt-hero-subtitle {
    font-size: 1.2rem; opacity: .9; max-width: 700px;
    margin: 0 auto 40px; line-height: 1.7;
    display: inline-block; min-height: 1.7em;
}
.mt-cursor { animation: mtBlink 1s infinite; color: var(--mt-primary); font-weight: 300; }
@keyframes mtBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

/* Hero Stats */
.mt-hero-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 16px; max-width: 900px; margin: 0 auto;
}
.mt-stat-pill {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 16px;
    backdrop-filter: blur(12px); transition: all 0.3s;
    position: relative; overflow: hidden;
}
.mt-stat-pill:hover {
    transform: translateY(-4px);
    border-color: var(--mt-primary);
    box-shadow: 0 12px 24px rgba(59,130,246,0.2);
}
.mt-stat-pill i {
    font-size: 1.5rem; color: var(--mt-primary);
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(59,130,246,0.2); display: flex;
    align-items: center; justify-content: center;
    flex-shrink: 0;
}
.mt-stat-num {
    display: block; font-size: 1.5rem; font-weight: 900;
    color: #fff; line-height: 1; font-family: var(--mt-font-display);
}
.mt-stat-label {
    font-size: 0.7rem; color: rgba(255,255,255,.6);
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-top: 2px; display: block;
}

/* Scroll Indicator */
.mt-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%;
    transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.mt-scroll-arrow { animation: mtArrowBounce 2s infinite; }
@keyframes mtArrowBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(10px); }
}

/* ===== MAIN SECTION ===== */
.mt-main-section {
    padding: 60px 20px 100px;
    background: var(--mt-gray-50);
}
body.gl-dark .mt-main-section { background: #0B1120; }

/* ===== FILTER CONSOLE ===== */
.mt-filter-console {
    background: #fff; border-radius: 20px;
    box-shadow: var(--mt-shadow-md); overflow: hidden;
    border: 1px solid var(--mt-gray-100); margin-bottom: 40px;
}
body.gl-dark .mt-filter-console { background: #1E293B; border-color: rgba(255,255,255,.08); }

.mt-console-header {
    background: var(--mt-dark); padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
    color: #94A3B8; font-family: var(--mt-font-mono); font-size: 0.8rem;
}
.mt-console-dots { display: flex; gap: 5px; }
.mt-console-dots .dot { width: 11px; height: 11px; border-radius: 50%; }
.mt-console-dots .red { background: #FF5F56; }
.mt-console-dots .yellow { background: #FFBD2E; }
.mt-console-dots .green { background: #27C93F; }
.mt-console-header i { color: var(--mt-primary); }
.mt-console-header span { color: #E2E8F0; }
.mt-console-status {
    margin-left: auto; color: var(--mt-primary);
    font-size: 0.7rem; letter-spacing: 1px;
    animation: mtBlink 2s infinite;
}

.mt-console-body { padding: 24px; display: flex; flex-direction: column; gap: 20px; }

.mt-search-wrap {
    position: relative; display: flex; align-items: center;
}
.mt-search-wrap > i {
    position: absolute; left: 18px; top: 50%;
    transform: translateY(-50%); color: var(--mt-gray-600);
    z-index: 1; font-size: 1rem;
}
.mt-search-wrap input {
    width: 100%; padding: 16px 100px 16px 50px;
    border: 1.5px solid var(--mt-gray-200); border-radius: 14px;
    background: var(--mt-gray-50); font-family: inherit;
    font-size: 1rem; color: var(--mt-dark);
    transition: all 0.3s;
}
body.gl-dark .mt-search-wrap input {
    background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1); color: #E2E8F0;
}
.mt-search-wrap input:focus {
    outline: none; border-color: var(--mt-primary);
    box-shadow: 0 0 0 4px rgba(59,130,246,0.15);
}

.mt-search-hint {
    position: absolute; right: 18px; top: 50%;
    transform: translateY(-50%);
    display: flex; gap: 3px; align-items: center;
    color: var(--mt-gray-600); font-size: 0.75rem;
    pointer-events: none;
}
.mt-search-hint kbd {
    background: var(--mt-gray-100); padding: 3px 8px;
    border-radius: 5px; font-family: var(--mt-font-mono);
    font-size: 0.72rem; border: 1px solid var(--mt-gray-200);
}
body.gl-dark .mt-search-hint kbd {
    background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.1); color: #CBD5E1;
}

.mt-filter-group {
    padding-top: 16px; border-top: 1px solid var(--mt-gray-100);
}
body.gl-dark .mt-filter-group { border-color: rgba(255,255,255,.08); }

.mt-filter-group label {
    font-size: 0.72rem; font-weight: 700; color: var(--mt-gray-600);
    letter-spacing: 1.5px; font-family: var(--mt-font-mono);
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 12px;
}
body.gl-dark .mt-filter-group label { color: #94A3B8; }
.mt-filter-group label i { color: var(--mt-primary); }

.mt-filter-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.mt-pill {
    padding: 8px 16px; border-radius: 24px; font-size: 0.82rem;
    font-weight: 600; color: var(--mt-gray-700);
    background: var(--mt-gray-100); border: 1.5px solid transparent;
    text-decoration: none; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 6px;
}
body.gl-dark .mt-pill { background: rgba(255,255,255,.05); color: #CBD5E1; }
.mt-pill i { font-size: 0.7rem; color: var(--pill-color, var(--mt-primary)); }
.mt-pill:hover {
    transform: translateY(-2px);
    border-color: var(--pill-color, var(--mt-primary));
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.mt-pill.active {
    background: var(--pill-color, var(--mt-primary)); 
    color: #fff;
    border-color: var(--pill-color, var(--mt-primary));
    box-shadow: 0 4px 16px rgba(59,130,246,0.3);
}
.mt-pill.active i { color: #fff; }

.mt-filter-actions {
    padding-top: 16px; border-top: 1px solid var(--mt-gray-100);
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 12px;
}
body.gl-dark .mt-filter-actions { border-color: rgba(255,255,255,.08); }

.mt-results-info {
    color: var(--mt-gray-600); display: flex; align-items: center; gap: 8px;
    font-size: 0.85rem;
}
body.gl-dark .mt-results-info { color: #94A3B8; }
.mt-results-info i { color: var(--mt-primary); }
.mt-results-info strong { 
    color: var(--mt-dark); font-family: var(--mt-font-display); 
    font-size: 1.1rem; font-weight: 800;
}
body.gl-dark .mt-results-info strong { color: #F1F5F9; }

.mt-btn-reset {
    padding: 10px 20px; background: rgba(239, 68, 68, 0.1);
    color: #DC2626; border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 8px; font-family: var(--mt-font-mono);
    font-size: 0.85rem; font-weight: 600; cursor: pointer;
    transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 8px;
}
.mt-btn-reset:hover { background: #DC2626; color: #fff; transform: translateY(-2px); }

/* ===== MITRA GRID ===== */
.mt-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.mt-card {
    position: relative; background: #fff; border-radius: 20px;
    overflow: hidden; border: 1px solid var(--mt-gray-100);
    box-shadow: var(--mt-shadow-sm); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer; display: flex; flex-direction: column;
}
body.gl-dark .mt-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.mt-card-glow {
    position: absolute; top: -50%; left: 50%;
    transform: translateX(-50%);
    width: 200%; height: 200%;
    background: radial-gradient(circle, var(--card-accent, var(--mt-primary)), transparent 60%);
    opacity: 0; transition: opacity 0.5s;
    filter: blur(60px); pointer-events: none; z-index: 0;
}
.mt-card:hover .mt-card-glow { opacity: 0.15; }

.mt-card::before {
    content: ''; position: absolute; inset: 0; border-radius: 20px; padding: 2px;
    background: conic-gradient(from 0deg, transparent, var(--card-accent, var(--mt-primary)), var(--mt-indigo), transparent 40%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none; z-index: 5;
}
.mt-card:hover::before { opacity: 1; animation: mtRotateBorder 4s linear infinite; }
@keyframes mtRotateBorder { to { transform: rotate(360deg); } }

.mt-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--mt-shadow-lg), 0 0 30px rgba(59,130,246,0.2);
    border-color: var(--card-accent, var(--mt-primary));
}

/* Card Header */
.mt-card-header {
    position: relative; padding: 30px 24px 20px;
    display: flex; align-items: center; justify-content: center;
    background: var(--mt-gray-50);
}
body.gl-dark .mt-card-header { background: rgba(0,0,0,0.2); }

.mt-logo-wrap {
    width: 100px; height: 100px;
    display: flex; align-items: center; justify-content: center;
    background: #fff; border-radius: 16px;
    box-shadow: var(--mt-shadow-sm);
    overflow: hidden;
}
body.gl-dark .mt-logo-wrap { background: rgba(255,255,255,.1); }

.mt-logo-wrap img {
    width: 100%; height: 100%; object-fit: contain;
    padding: 10px;
}

.mt-logo-placeholder {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2.5rem;
}

.mt-jenis-badge {
    position: absolute; top: 16px; right: 16px;
    padding: 6px 12px; border-radius: 20px;
    font-size: 0.72rem; font-weight: 700;
    display: flex; align-items: center; gap: 6px;
    backdrop-filter: blur(10px);
}
.mt-jenis-badge i { font-size: 0.7rem; }

/* Card Body */
.mt-card-body {
    padding: 24px; flex: 1; display: flex; flex-direction: column;
    text-align: center; position: relative; z-index: 2;
}

.mt-card-name {
    font-size: 1.15rem; font-weight: 800; line-height: 1.3;
    color: var(--mt-dark); margin: 0 0 10px 0;
    font-family: var(--mt-font-display);
}
body.gl-dark .mt-card-name { color: #F1F5F9; }

.mt-card-desc {
    font-size: 0.88rem; color: var(--mt-gray-600);
    line-height: 1.6; margin-bottom: 16px;
    display: -webkit-box; -webkit-line-clamp: 3;
    -webkit-box-orient: vertical; overflow: hidden;
}
body.gl-dark .mt-card-desc { color: #94A3B8; }

.mt-no-desc {
    font-style: italic; opacity: 0.6;
}

.mt-website-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 20px; background: var(--mt-gray-50);
    border: 1.5px solid var(--mt-gray-200); border-radius: 10px;
    text-decoration: none; font-weight: 700; font-size: 0.85rem;
    transition: all 0.3s; margin-top: auto;
}
body.gl-dark .mt-website-btn {
    background: rgba(255,255,255,.05);
    border-color: rgba(255,255,255,.1);
}
.mt-website-btn:hover {
    background: var(--card-accent, var(--mt-primary));
    color: #fff !important;
    border-color: var(--card-accent, var(--mt-primary));
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59,130,246,0.3);
}

.mt-no-website {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 20px; color: var(--mt-gray-600);
    font-size: 0.82rem; font-style: italic;
    margin-top: auto;
}
body.gl-dark .mt-no-website { color: #64748B; }

/* Card Footer */
.mt-card-footer {
    padding: 14px 24px; background: var(--mt-gray-50);
    border-top: 1px solid var(--mt-gray-100);
    display: flex; justify-content: space-between; align-items: center;
    font-size: 0.82rem;
}
body.gl-dark .mt-card-footer { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.08); }

.mt-footer-text {
    color: var(--mt-gray-600); display: flex; align-items: center; gap: 6px;
    font-weight: 600;
}
body.gl-dark .mt-footer-text { color: #94A3B8; }
.mt-footer-text i { color: var(--card-accent, var(--mt-primary)); }

.mt-card-footer > i {
    color: var(--card-accent, var(--mt-primary)); transition: transform 0.3s;
}
.mt-card:hover .mt-card-footer > i { transform: translateX(4px); }

/* Empty States */
.mt-empty-search,
.mt-empty-state {
    text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px;
    border: 2px dashed var(--mt-gray-200);
    max-width: 600px; margin: 0 auto;
}
body.gl-dark .mt-empty-search,
body.gl-dark .mt-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }

.mt-empty-icon {
    position: relative; width: 100px; height: 100px;
    margin: 0 auto 24px; display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: var(--mt-gray-600);
}
.mt-empty-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--mt-primary);
    animation: mtEmptyPulse 2s infinite;
}
@keyframes mtEmptyPulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

.mt-empty-search h3,
.mt-empty-state h3 {
    font-size: 1.5rem; margin: 0 0 8px 0; color: var(--mt-dark);
    font-weight: 800; font-family: var(--mt-font-display);
}
body.gl-dark .mt-empty-search h3,
body.gl-dark .mt-empty-state h3 { color: #F1F5F9; }

.mt-empty-search p,
.mt-empty-state p { color: var(--mt-gray-600); margin-bottom: 20px; }

.mt-btn-primary {
    padding: 12px 24px; background: var(--mt-primary);
    color: #fff; border: none; border-radius: 10px;
    font-weight: 700; cursor: pointer; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 8px;
    font-family: inherit; font-size: 0.95rem;
}
.mt-btn-primary:hover { background: var(--mt-primary-dark); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(59,130,246,0.3); }

/* ===== DISTRIBUTION SECTION ===== */
.mt-distribution-section {
    margin-top: 80px;
}

.mt-section-title {
    font-size: 1.8rem; font-weight: 800; color: var(--mt-dark);
    margin-bottom: 30px; text-align: center;
    font-family: var(--mt-font-display);
    display: flex; align-items: center; justify-content: center; gap: 12px;
}
body.gl-dark .mt-section-title { color: #F1F5F9; }
.mt-section-title i { color: var(--mt-primary); }

.mt-distribution-card {
    background: #fff; border-radius: 24px;
    box-shadow: var(--mt-shadow-md);
    border: 1px solid var(--mt-gray-100);
    padding: 40px;
    display: flex; align-items: center; gap: 60px;
    max-width: 800px; margin: 0 auto;
}
body.gl-dark .mt-distribution-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.mt-donut-wrap {
    position: relative; width: 200px; height: 200px; flex-shrink: 0;
}

.mt-donut-ring {
    width: 100%; height: 100%;
    transform: rotate(-90deg);
}

.mt-donut-center {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
}

.mt-donut-value {
    font-size: 2.5rem; font-weight: 900;
    color: var(--mt-dark); font-family: var(--mt-font-display);
    line-height: 1;
}
body.gl-dark .mt-donut-value { color: #F1F5F9; }

.mt-donut-label {
    font-size: 0.8rem; color: var(--mt-gray-600);
    margin-top: 4px; text-transform: uppercase;
    letter-spacing: 0.5px;
}

.mt-distribution-legend {
    flex: 1;
    display: flex; flex-direction: column; gap: 16px;
}

.mt-legend-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; background: var(--mt-gray-50);
    border-radius: 12px; transition: all 0.3s;
}
body.gl-dark .mt-legend-item { background: rgba(255,255,255,.05); }
.mt-legend-item:hover {
    transform: translateX(4px);
    box-shadow: var(--mt-shadow-sm);
}

.mt-legend-dot {
    width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0;
}

.mt-legend-name {
    flex: 1; font-weight: 600; color: var(--mt-dark);
    display: flex; align-items: center; gap: 8px;
    font-size: 0.9rem;
}
body.gl-dark .mt-legend-name { color: #F1F5F9; }
.mt-legend-name i { color: var(--mt-gray-600); }

.mt-legend-count {
    font-weight: 800; color: var(--mt-dark);
    font-family: var(--mt-font-display);
    font-size: 1rem;
}
body.gl-dark .mt-legend-count { color: #F1F5F9; }

/* ===== MODAL ===== */
.mt-modal {
    position: fixed; inset: 0; z-index: 10000;
    background: rgba(2,6,23,0.9); backdrop-filter: blur(12px);
    display: none; align-items: center; justify-content: center;
    padding: 20px; opacity: 0; transition: opacity 0.3s;
}
.mt-modal.open { display: flex; opacity: 1; }

.mt-modal-content {
    background: #fff; border-radius: 28px; max-width: 700px;
    width: 100%; max-height: 90vh; overflow: hidden;
    box-shadow: 0 30px 60px rgba(0,0,0,0.4);
    position: relative; transform: scale(0.9) translateY(20px);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
body.gl-dark .mt-modal-content { background: #1E293B; }
.mt-modal.open .mt-modal-content { transform: scale(1) translateY(0); }

.mt-modal-close {
    position: absolute; top: 20px; right: 20px; z-index: 10;
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);
    border: none; color: var(--mt-dark); font-size: 1.1rem;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all 0.3s;
}
body.gl-dark .mt-modal-close { background: rgba(0,0,0,0.5); color: #fff; }
.mt-modal-close:hover {
    background: #EF4444; color: #fff;
    transform: rotate(90deg) scale(1.1);
}

.mt-modal-inner {
    position: relative; z-index: 1;
    max-height: 90vh; overflow-y: auto;
}
.mt-modal-inner::-webkit-scrollbar { width: 6px; }
.mt-modal-inner::-webkit-scrollbar-thumb { background: var(--mt-primary); border-radius: 3px; }

/* Modal Hero */
.mt-modal-hero {
    padding: 40px 40px 30px;
    background: linear-gradient(135deg, var(--mt-dark), #1E3A8A, var(--mt-primary-dark));
    color: #fff; display: flex; gap: 24px;
    align-items: center; position: relative;
    border-bottom: 3px solid var(--mt-primary);
}

.mt-modal-logo-wrap {
    position: relative; width: 120px; height: 120px; flex-shrink: 0;
    background: rgba(255,255,255,0.1); border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
}
.mt-modal-logo-wrap img {
    width: 100%; height: 100%; object-fit: contain;
    padding: 15px;
}
.mt-modal-logo-ring {
    position: absolute; inset: -8px; border-radius: 24px;
    border: 2px dashed var(--mt-primary);
    animation: mtRingSpin 15s linear infinite;
}
@keyframes mtRingSpin { to { transform: rotate(360deg); } }

.mt-modal-hero-info { flex: 1; }
.mt-modal-hero-info h2 {
    font-size: 1.8rem; margin: 0 0 12px 0; font-weight: 800;
    font-family: var(--mt-font-display); line-height: 1.2;
}
.mt-modal-jenis {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 14px; background: rgba(255,255,255,0.15);
    border-radius: 20px; font-size: 0.85rem;
    backdrop-filter: blur(10px);
}

/* Modal Body */
.mt-modal-body { padding: 30px 40px; }

.mt-modal-info-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 12px; margin-bottom: 24px;
}
.mt-modal-info-item {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; background: var(--mt-gray-50);
    border-radius: 12px; border: 1px solid var(--mt-gray-100);
}
body.gl-dark .mt-modal-info-item { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.08); }

.mt-modal-info-item i {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, var(--mt-primary), var(--mt-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.mt-modal-info-item div { flex: 1; }
.mt-modal-info-item span {
    font-size: 0.7rem; color: var(--mt-gray-600);
    text-transform: uppercase; letter-spacing: 0.5px;
    display: block; margin-bottom: 2px;
}
body.gl-dark .mt-modal-info-item span { color: #94A3B8; }
.mt-modal-info-item strong {
    font-size: 0.95rem; color: var(--mt-dark);
    font-family: var(--mt-font-mono); font-weight: 700;
    word-break: break-word;
}
body.gl-dark .mt-modal-info-item strong { color: #F1F5F9; }

.mt-modal-website-link {
    display: inline-flex; align-items: center; gap: 6px;
    color: var(--mt-primary); text-decoration: none;
    font-weight: 700; font-size: 0.9rem;
    transition: all 0.3s;
}
.mt-modal-website-link:hover {
    color: var(--mt-primary-dark);
    text-decoration: underline;
}
.mt-modal-website-link i { font-size: 0.7rem; }

.mt-modal-desc {
    margin-bottom: 24px;
}
.mt-modal-desc h4 {
    font-size: 1rem; font-weight: 800; color: var(--mt-dark);
    margin: 0 0 12px 0; display: flex; align-items: center; gap: 10px;
    text-transform: uppercase; letter-spacing: 1px;
    font-family: var(--mt-font-display);
}
body.gl-dark .mt-modal-desc h4 { color: #F1F5F9; }
.mt-modal-desc h4 i { color: var(--mt-primary); font-size: 1.1rem; }

.mt-modal-desc p {
    font-size: 1rem; line-height: 1.8; color: var(--mt-gray-700);
    margin: 0; padding: 20px;
    background: var(--mt-gray-50); border-radius: 12px;
    border-left: 4px solid var(--mt-primary);
}
body.gl-dark .mt-modal-desc p { background: rgba(255,255,255,.03); color: #CBD5E1; }

.mt-modal-actions {
    display: flex; justify-content: center;
    padding-top: 20px; border-top: 1px solid var(--mt-gray-100);
}
body.gl-dark .mt-modal-actions { border-color: rgba(255,255,255,.08); }

.mt-btn-website {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 14px 28px; background: linear-gradient(135deg, var(--mt-primary), var(--mt-primary-dark));
    color: #fff; border-radius: 12px; text-decoration: none;
    font-weight: 700; font-size: 1rem; transition: all 0.3s;
    box-shadow: 0 4px 16px rgba(59,130,246,0.3);
}
.mt-btn-website:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(59,130,246,0.4);
}
.mt-btn-website i:last-child { transition: transform 0.3s; }
.mt-btn-website:hover i:last-child { transform: translateX(4px); }

/* ===== TOAST ===== */
.mt-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--mt-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    z-index: 10001; opacity: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.9rem;
}
body.gl-dark .mt-toast { background: #fff; color: var(--mt-dark); }
.mt-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.mt-toast i { color: var(--mt-primary); font-size: 1.2rem; }

/* ===== ANIMATIONS ===== */
.fade-up {
    opacity: 0; transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .mt-hero-stats { grid-template-columns: repeat(2, 1fr); }
    .mt-distribution-card { flex-direction: column; text-align: center; }
    .mt-modal-info-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .mt-hero { padding: 120px 20px 80px; min-height: 85vh; }
    .mt-hero-title { font-size: 3rem; }
    .mt-hero-stats { grid-template-columns: 1fr; gap: 10px; }
    .mt-stat-pill { justify-content: center; }
    .mt-floating-icons { display: none; }
    .mt-console-body { padding: 16px; }
    .mt-search-hint { display: none; }
    .mt-search-wrap input { padding-right: 16px; }
    .mt-filter-pills { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 6px; }
    .mt-pill { white-space: nowrap; flex-shrink: 0; }
    .mt-grid { grid-template-columns: 1fr; }
    .mt-distribution-card { padding: 24px; }
    .mt-modal-hero { padding: 30px 20px; flex-direction: column; text-align: center; }
    .mt-modal-logo-wrap { width: 100px; height: 100px; margin: 0 auto; }
    .mt-modal-hero-info h2 { font-size: 1.4rem; }
    .mt-modal-body { padding: 20px; }
}
@media (max-width: 480px) {
    .mt-hero-title { font-size: 2.3rem; }
    .mt-hero-badge { font-size: 0.7rem; padding: 8px 16px; letter-spacing: 1px; }
    .mt-card { border-radius: 16px; }
    .mt-card-name { font-size: 1rem; }
    .mt-modal-content { border-radius: 20px; }
    .mt-modal-hero-info h2 { font-size: 1.2rem; }
    .mt-stat-num { font-size: 1.3rem; }
    .mt-donut-wrap { width: 150px; height: 150px; }
    .mt-donut-value { font-size: 2rem; }
}

/* Performance optimizations */
.mt-main-section {
    content-visibility: auto;
    contain-intrinsic-size: auto 2000px;
}

body.is-scrolling .mt-nebula,
body.is-scrolling .mt-stars-bg,
body.is-scrolling .mt-float-icon,
body.is-scrolling .mt-progress-fill,
body.is-scrolling .mt-badge-glow,
body.is-scrolling .mt-modal-logo-ring {
    animation-play-state: paused !important;
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    .mt-nebula,
    .mt-floating-icons {
        display: none !important;
    }
}
</style>

<!-- ============================================================
     SCRIPTS
============================================================ -->
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

    // Hero visibility detection
    let heroVisible = true;
    if ('IntersectionObserver' in window) {
        const heroSection = document.querySelector('.mt-hero');
        if (heroSection) {
            new IntersectionObserver((entries) => {
                heroVisible = entries[0]?.isIntersecting ?? true;
            }, { threshold: 0.01 }).observe(heroSection);
        }

        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                entry.target.classList.toggle('mt-offscreen', !entry.isIntersecting);
            });
        }, { rootMargin: '300px 0px', threshold: 0 });

        document.querySelectorAll('.mt-hero, .mt-main-section').forEach(section => {
            sectionObserver.observe(section);
        });
    }

    // ===== 1. Network Canvas Animation (OPTIMIZED) =====
    const canvas = document.getElementById('mtNetworkCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d', { alpha: true });
        let width, height, nodes = [], time = 0;
        let lastFrame = 0;
        const frameInterval = 1000 / 30; // 30 FPS limit

        function resize() {
            width = canvas.width = canvas.parentElement.offsetWidth;
            height = canvas.height = canvas.parentElement.offsetHeight;
            initNodes();
        }

        function initNodes() {
            nodes = [];
            const numNodes = Math.min(Math.floor((width * height) / 15000), 80);
            const colors = ['#3B82F6', '#6366F1', '#10B981', '#8B5CF6', '#F59E0B'];
            
            for (let i = 0; i < numNodes; i++) {
                nodes.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    size: Math.random() * 3 + 1,
                    vx: (Math.random() - 0.5) * 0.4,
                    vy: (Math.random() - 0.5) * 0.4,
                    color: colors[Math.floor(Math.random() * colors.length)],
                    pulse: Math.random() * Math.PI * 2
                });
            }
        }

        function drawNode(node) {
            const pulseScale = 1 + Math.sin(time * 0.02 + node.pulse) * 0.3;
            
            ctx.beginPath();
            ctx.arc(node.x, node.y, node.size * pulseScale, 0, Math.PI * 2);
            ctx.fillStyle = node.color;
            ctx.globalAlpha = 0.7;
            ctx.fill();
            ctx.globalAlpha = 1;
            
            // Glow effect
            ctx.beginPath();
            ctx.arc(node.x, node.y, node.size * pulseScale * 2.5, 0, Math.PI * 2);
            ctx.fillStyle = node.color;
            ctx.globalAlpha = 0.08;
            ctx.fill();
            ctx.globalAlpha = 1;
        }

        function drawConnections() {
            const maxDist = 160;
            const maxDistSq = maxDist * maxDist;
            
            for (let i = 0; i < nodes.length; i++) {
                for (let j = i + 1; j < nodes.length; j++) {
                    const dx = nodes[i].x - nodes[j].x;
                    const dy = nodes[i].y - nodes[j].y;
                    const distSq = dx * dx + dy * dy;
                    
                    if (distSq < maxDistSq) {
                        const distance = Math.sqrt(distSq);
                        const opacity = (1 - distance / maxDist) * 0.25;
                        
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(59, 130, 246, ${opacity})`;
                        ctx.lineWidth = 1;
                        ctx.moveTo(nodes[i].x, nodes[i].y);
                        ctx.lineTo(nodes[j].x, nodes[j].y);
                        ctx.stroke();
                    }
                }
            }
        }

        function animate(currentTime) {
            requestAnimationFrame(animate);
            
            if (!heroVisible || document.hidden) return;
            
            const elapsed = currentTime - lastFrame;
            if (elapsed < frameInterval) return;
            lastFrame = currentTime - (elapsed % frameInterval);

            ctx.clearRect(0, 0, width, height);
            
            // Update nodes
            nodes.forEach(node => {
                node.x += node.vx;
                node.y += node.vy;
                
                if (node.x < 0 || node.x > width) node.vx *= -1;
                if (node.y < 0 || node.y > height) node.vy *= -1;
            });
            
            drawConnections();
            nodes.forEach(drawNode);
            time++;
        }

        resize();
        window.addEventListener('resize', resize, { passive: true });
        requestAnimationFrame(animate);
    }

    // ===== 2. Typing Effect =====
    const textElement = document.getElementById('mtTypingText');
    if (textElement) {
        const phrases = [
            "Jaringan mitra industri, universitas, pemerintah, dan komunitas.",
            "Kolaborasi strategis untuk menciptakan lulusan yang siap kerja.",
            "Dari ruang kelas menuju industri global melalui kemitraan.",
            "Bersama-sama membangun masa depan teknologi Indonesia."
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
    const counters = document.querySelectorAll('.mt-stat-num[data-count]');
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
    const progressFill = document.getElementById('mtProgressFill');
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
    const searchInput = document.getElementById('mtSearchInput');
    const grid = document.getElementById('mtGrid');
    const emptySearch = document.getElementById('mtEmptySearch');
    const resultsCount = document.getElementById('mtResultsCount');

    if (searchInput && grid) {
        let searchDebounce;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                const query = e.target.value.toLowerCase().trim();
                const cards = grid.querySelectorAll('.mt-card');
                let visibleCount = 0;

                cards.forEach(card => {
                    const nama = card.getAttribute('data-nama') || '';
                    const website = card.getAttribute('data-website') || '';
                    const deskripsi = card.getAttribute('data-deskripsi') || '';
                    
                    if (!query || nama.includes(query) || website.includes(query) || deskripsi.includes(query)) {
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
            }, 120);
        });
    }

    window.mtResetFilters = function() {
        window.location.href = 'mitra.php';
    };

    // ===== 7. Keyboard Shortcuts =====
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) searchInput.focus();
        }
        if (e.key === 'Escape') {
            mtCloseModal();
        }
    });

    // ===== 8. Modal Functions =====
    const modal = document.getElementById('mtModal');

    window.mtOpenModal = function(data) {
        if (!modal || !data) return;

        // Get jenis config
        const jenisConfig = {
            'industri': {icon: 'fa-industry', color: '#3B82F6', label: 'Industri'},
            'universitas': {icon: 'fa-university', color: '#8B5CF6', label: 'Universitas'},
            'pemerintah': {icon: 'fa-landmark', color: '#EF4444', label: 'Pemerintah'},
            'komunitas': {icon: 'fa-users', color: '#10B981', label: 'Komunitas'}
        };
        
        const jenisCfg = jenisConfig[data.jenis] || jenisConfig['industri'];
        
        // Set hero
        const hero = document.getElementById('mtModalHero');
        const logo = document.getElementById('mtModalLogo');
        
        if (data.logo) {
            logo.src = 'uploads/' + data.logo;
            logo.style.display = 'block';
        } else {
            logo.style.display = 'none';
            const placeholder = document.createElement('div');
            placeholder.style.cssText = `
                width: 100%; height: 100%;
                display: flex; align-items: center; justify-content: center;
                font-size: 3rem; color: ${jenisCfg.color};
            `;
            placeholder.innerHTML = `<i class="fas ${jenisCfg.icon}"></i>`;
            logo.parentElement.appendChild(placeholder);
        }
        
        hero.style.background = `linear-gradient(135deg, #0F172A, #1E3A8A, ${jenisCfg.color})`;
        
        // Set basic info
        document.getElementById('mtModalNama').textContent = data.nama || '-';
        document.getElementById('mtModalJenisText').textContent = jenisCfg.label;
        document.getElementById('mtModalUrutan').textContent = data.urutan || '0';
        document.getElementById('mtModalCreated').textContent = formatTanggal(data.created_at);
        
        // Jenis badge
        document.getElementById('mtModalJenis').innerHTML = `
            <i class="fas ${jenisCfg.icon}"></i>
            <span>${jenisCfg.label}</span>
        `;
        
        // Website
        const websiteWrap = document.getElementById('mtModalWebsiteWrap');
        if (data.website) {
            document.getElementById('mtModalWebsiteText').textContent = data.website;
            document.getElementById('mtModalWebsiteLink').href = data.website;
            document.getElementById('mtModalWebsiteBtn').href = data.website;
            websiteWrap.style.display = 'flex';
            document.getElementById('mtModalActions').style.display = 'flex';
        } else {
            websiteWrap.style.display = 'none';
            document.getElementById('mtModalActions').style.display = 'none';
        }
        
        // Deskripsi
        const descWrap = document.getElementById('mtModalDescWrap');
        if (data.deskripsi) {
            document.getElementById('mtModalDesc').textContent = data.deskripsi;
            descWrap.style.display = 'block';
        } else {
            descWrap.style.display = 'none';
        }
        
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.mtCloseModal = function() {
        if (modal) {
            modal.classList.remove('open');
            document.body.style.overflow = '';
            
            // Cleanup placeholder if exists
            const logo = document.getElementById('mtModalLogo');
            const placeholder = logo.parentElement.querySelector('div');
            if (placeholder) placeholder.remove();
        }
    };

    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) mtCloseModal();
        });
    }

    // ===== 9. Toast =====
    window.mtShowToast = function(message) {
        const toast = document.getElementById('mtToast');
        if (!toast) return;
        const msgEl = document.getElementById('mtToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // ===== 10. Parallax Nebulas (THROTTLED) =====
    const heroSection = document.querySelector('.mt-hero');
    let parallaxTicking = false;
    if (heroSection && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            if (!parallaxTicking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.pageYOffset;
                    const nebulas = heroSection.querySelectorAll('.mt-nebula');
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

    // ===== 11. Format Tanggal Helper =====
    function formatTanggal(dateStr) {
        if (!dateStr) return '-';
        const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const d = new Date(dateStr);
        return `${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
    }

    setTimeout(() => {
        mtShowToast('✨ Welcome to Partnership Network!');
    }, 1500);

    console.log('%c╔══════════════════════════════════════════╗', 'color:#3B82F6;font-weight:bold');
    console.log('%c║   PARTNERSHIP NETWORK - Our Partners     ║', 'color:#3B82F6;font-weight:bold;font-size:14px');
    console.log('%c╚══════════════════════════════════════════╝', 'color:#3B82F6;font-weight:bold');
    console.log('%c🤝 ' + document.querySelectorAll('.mt-card').length + ' partners in our network (OPTIMIZED)', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>