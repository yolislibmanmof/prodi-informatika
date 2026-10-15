<?php
require_once 'includes/init_security.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Jejak Alumni';
$pageDesc = 'Telusuri perjalanan karir alumni Program Studi Informatika yang sukses di berbagai bidang teknologi.';

// ===== FILTER PARAMETERS =====
$filterTahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
$filterIpk = isset($_GET['ipk']) ? clean($_GET['ipk']) : '';
$filterKota = isset($_GET['kota']) ? clean($_GET['kota']) : '';

// ===== FETCH DATA DENGAN HELPER =====
try {
    $filters = [
        'status' => 'aktif',
        'tahun_lulus' => $filterTahun ?: null,
        'ipk_range' => $filterIpk ?: null,
        'kota' => $filterKota ?: null
    ];
    
    $alumniList = getAlumni($filters);
    $alumniStats = getAlumniStats();
    $yearsList = getAlumniYears();
    $kotaList = getAlumniKota();
    
    // Featured alumni (Alumni of the Month)
    $featuredAlumni = getAlumni(['is_featured' => 1, 'status' => 'aktif'], 1);
    $alumniOfMonth = !empty($featuredAlumni) ? $featuredAlumni[0] : null;
    
} catch (Throwable $e) {
    $alumniList = [];
    $alumniStats = ['total' => 0, 'working' => 0, 'featured' => 0, 'avg_ipk' => 0, 'cities' => 0];
    $yearsList = [];
    $kotaList = [];
    $alumniOfMonth = null;
}

// ===== STATISTICS =====
$totalAlumni = $alumniStats['total'];
$workingCount = $alumniStats['working'];
$featuredCount = $alumniStats['featured'];
$avgIpk = $alumniStats['avg_ipk'];
$employmentRate = $totalAlumni > 0 ? round(($workingCount / $totalAlumni) * 100) : 0;
?>

<!-- ===== READING PROGRESS ===== -->
<div class="al-reading-progress">
    <div class="al-progress-fill" id="alProgressFill"></div>
</div>

<!-- ===== HERO SECTION ===== -->
<section class="al-hero">
    <canvas id="alTimelineCanvas" class="al-timeline-canvas"></canvas>
    <div class="al-hero-bg">
        <div class="al-nebula al-nebula-1"></div>
        <div class="al-nebula al-nebula-2"></div>
        <div class="al-nebula al-nebula-3"></div>
        <div class="al-stars-bg"></div>
        <div class="al-path-lines"></div>
    </div>
    
    <!-- Floating Career Icons -->
    <div class="al-floating-icons">
        <span class="al-float-icon" style="--delay: 0s; --x: 10%; --y: 20%; --rot: 10deg;">💼</span>
        <span class="al-float-icon" style="--delay: 1.5s; --x: 85%; --y: 25%; --rot: -15deg;">🎓</span>
        <span class="al-float-icon" style="--delay: 0.8s; --x: 15%; --y: 70%; --rot: 20deg;">🚀</span>
        <span class="al-float-icon" style="--delay: 2.2s; --x: 88%; --y: 65%; --rot: -10deg;">⭐</span>
        <span class="al-float-icon" style="--delay: 3s; --x: 50%; --y: 15%; --rot: 5deg;">🌟</span>
        <span class="al-float-icon" style="--delay: 1.2s; --x: 45%; --y: 85%; --rot: -20deg;">💡</span>
    </div>
    
    <div class="container al-hero-content">
        <span class="al-hero-badge fade-up">
            <span class="al-badge-glow"></span>
            <i class="fas fa-route"></i> TRACER STUDY
        </span>
        
        <h1 class="al-hero-title fade-up">
            <span class="al-title-line">Jejak</span>
            <span class="al-title-line al-title-journey">Alumni</span>
        </h1>
        
        <p class="al-hero-subtitle fade-up">
            <span id="alTypingText"></span><span class="al-cursor">|</span>
        </p>
        
        <!-- Alumni Stats -->
        <div class="al-hero-stats fade-up">
            <div class="al-stat-pill">
                <i class="fas fa-user-graduate"></i>
                <div>
                    <span class="al-stat-num" data-count="<?= $totalAlumni ?>">0</span>
                    <span class="al-stat-label">Total Alumni</span>
                </div>
            </div>
            <div class="al-stat-pill">
                <i class="fas fa-briefcase"></i>
                <div>
                    <span class="al-stat-num" data-count="<?= $workingCount ?>">0</span>
                    <span class="al-stat-label">Sudah Bekerja</span>
                </div>
            </div>
            <div class="al-stat-pill">
                <i class="fas fa-chart-line"></i>
                <div>
                    <span class="al-stat-num"><?= $employmentRate ?>%</span>
                    <span class="al-stat-label">Employment Rate</span>
                </div>
            </div>
            <?php if ($avgIpk > 0): ?>
            <div class="al-stat-pill">
                <i class="fas fa-graduation-cap"></i>
                <div>
                    <span class="al-stat-num"><?= number_format($avgIpk, 2) ?></span>
                    <span class="al-stat-label">Average IPK</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="al-scroll-indicator">
        <span>Explore Alumni Journey</span>
        <div class="al-scroll-arrow">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</section>

<!-- ===== ALUMNI OF THE MONTH ===== -->
<?php if ($alumniOfMonth): ?>
<section class="al-featured-section">
    <div class="container">
        <div class="al-featured-card fade-up">
            <div class="al-featured-glow"></div>
            <div class="al-featured-ribbon">
                <i class="fas fa-crown"></i>
                <span>Alumni Unggulan</span>
            </div>
            
            <div class="al-featured-content">
                <div class="al-featured-avatar">
                    <?php if ($alumniOfMonth['foto']): ?>
                        <img src="uploads/<?= htmlspecialchars($alumniOfMonth['foto']) ?>" alt="<?= clean($alumniOfMonth['nama']) ?>">
                    <?php else: ?>
                        <div class="al-avatar-placeholder">
                            <?= strtoupper(substr($alumniOfMonth['nama'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="al-avatar-ring"></div>
                </div>
                
                <div class="al-featured-info">
                    <h3 class="al-featured-name"><?= clean($alumniOfMonth['nama']) ?></h3>
                    <p class="al-featured-role"><?= clean($alumniOfMonth['pekerjaan'] ?? 'Professional') ?></p>
                    
                    <?php if (!empty($alumniOfMonth['perusahaan'])): ?>
                    <div class="al-featured-company">
                        <i class="fas fa-building"></i>
                        <span><?= clean($alumniOfMonth['perusahaan']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($alumniOfMonth['quote'])): ?>
                    <div class="al-featured-quote">
                        <i class="fas fa-quote-left"></i>
                        <p><?= clean($alumniOfMonth['quote']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="al-featured-badges">
                        <span class="al-badge al-badge-year">
                            <i class="fas fa-graduation-cap"></i>
                            Angkatan <?= $alumniOfMonth['tahun_lulus'] ?>
                        </span>
                        <?php 
                        $ipkInfo = getIpkLabel($alumniOfMonth['ipk'] ?? 0);
                        if ($ipkInfo && $alumniOfMonth['ipk'] > 0): 
                        ?>
                        <span class="al-badge" style="background: <?= $ipkInfo['bg'] ?>; color: <?= $ipkInfo['color'] ?>;">
                            <i class="fas <?= $ipkInfo['icon'] ?>"></i>
                            IPK <?= number_format($alumniOfMonth['ipk'], 2) ?> • <?= $ipkInfo['label'] ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== MAIN CONTENT ===== -->
<section class="al-main-section">
    <div class="container">
        
        <!-- Filter Console -->
        <div class="al-filter-console fade-up">
            <div class="al-console-header">
                <div class="al-console-dots">
                    <span class="dot red"></span>
                    <span class="dot yellow"></span>
                    <span class="dot green"></span>
                </div>
                <i class="fas fa-compass"></i>
                <span>alumni_explorer.exe</span>
                <span class="al-console-status">● SEARCHING</span>
            </div>
            
            <div class="al-console-body">
                <!-- Search Bar -->
                <div class="al-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="alSearchInput" placeholder="Cari alumni by nama, pekerjaan, atau perusahaan..." autocomplete="off">
                    <span class="al-search-hint">
                        <kbd>Ctrl</kbd>+<kbd>K</kbd>
                    </span>
                </div>
                
                <!-- Filter Pills -->
                <?php if (!empty($yearsList)): ?>
                <div class="al-filter-group">
                    <label><i class="fas fa-calendar"></i> Tahun Lulus:</label>
                    <div class="al-filter-pills">
                        <a href="alumni.php" class="al-pill <?= !$filterTahun ? 'active' : '' ?>">Semua</a>
                        <?php foreach ($yearsList as $thn): ?>
                            <a href="?tahun=<?= $thn ?><?= $filterIpk ? '&ipk=' . $filterIpk : '' ?><?= $filterKota ? '&kota=' . urlencode($filterKota) : '' ?>" 
                               class="al-pill <?= $filterTahun == $thn ? 'active' : '' ?>"><?= $thn ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="al-filter-group">
                    <label><i class="fas fa-star"></i> IPK:</label>
                    <div class="al-filter-pills">
                        <a href="alumni.php<?= $filterTahun ? '?tahun=' . $filterTahun : '' ?><?= $filterKota ? ($filterTahun ? '&' : '?') . 'kota=' . urlencode($filterKota) : '' ?>" 
                           class="al-pill <?= !$filterIpk ? 'active' : '' ?>">Semua</a>
                        <a href="?ipk=cumlaude<?= $filterTahun ? '&tahun=' . $filterTahun : '' ?><?= $filterKota ? '&kota=' . urlencode($filterKota) : '' ?>" 
                           class="al-pill al-pill-gold <?= $filterIpk === 'cumlaude' ? 'active' : '' ?>">
                            <i class="fas fa-crown"></i> Cumlaude
                        </a>
                        <a href="?ipk=sangat_baik<?= $filterTahun ? '&tahun=' . $filterTahun : '' ?><?= $filterKota ? '&kota=' . urlencode($filterKota) : '' ?>" 
                           class="al-pill al-pill-green <?= $filterIpk === 'sangat_baik' ? 'active' : '' ?>">
                            <i class="fas fa-star"></i> Sangat Baik
                        </a>
                        <a href="?ipk=baik<?= $filterTahun ? '&tahun=' . $filterTahun : '' ?><?= $filterKota ? '&kota=' . urlencode($filterKota) : '' ?>" 
                           class="al-pill al-pill-blue <?= $filterIpk === 'baik' ? 'active' : '' ?>">
                            <i class="fas fa-check"></i> Baik
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($kotaList)): ?>
                <div class="al-filter-group">
                    <label><i class="fas fa-map-marker-alt"></i> Kota:</label>
                    <div class="al-filter-pills">
                        <a href="alumni.php<?= $filterTahun ? '?tahun=' . $filterTahun : '' ?><?= $filterIpk ? ($filterTahun ? '&' : '?') . 'ipk=' . $filterIpk : '' ?>" 
                           class="al-pill <?= !$filterKota ? 'active' : '' ?>">Semua</a>
                        <?php foreach (array_slice($kotaList, 0, 8) as $kota): ?>
                            <a href="?kota=<?= urlencode($kota) ?><?= $filterTahun ? '&tahun=' . $filterTahun : '' ?><?= $filterIpk ? '&ipk=' . $filterIpk : '' ?>" 
                               class="al-pill <?= $filterKota === $kota ? 'active' : '' ?>"><?= clean($kota) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="al-filter-actions">
                    <span class="al-results-info">
                        <i class="fas fa-users"></i>
                        <strong id="alResultsCount"><?= count($alumniList) ?></strong> alumni ditemukan
                    </span>
                    <button class="al-btn-reset" onclick="alResetFilters()">
                        <i class="fas fa-redo"></i> Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Alumni Grid -->
        <?php if (!empty($alumniList)): ?>
            <div class="al-grid" id="alGrid">
                <?php foreach ($alumniList as $index => $a): 
                    $initial = strtoupper(substr($a['nama'] ?? 'A', 0, 1));
                    $colors = ['#8B5CF6', '#EC4899', '#F59E0B', '#10B981', '#3B82F6', '#06B6D4'];
                    $color = $colors[ord($initial) % count($colors)];
                    $ipkInfo = !empty($a['ipk']) ? getIpkLabel($a['ipk']) : null;
                    $isFeatured = !empty($a['is_featured']);
                ?>
                <div class="al-card fade-up <?= $isFeatured ? 'al-featured' : '' ?>" 
                     data-name="<?= strtolower(clean($a['nama'] ?? '')) ?>"
                     data-pekerjaan="<?= strtolower(clean($a['pekerjaan'] ?? '')) ?>"
                     data-perusahaan="<?= strtolower(clean($a['perusahaan'] ?? '')) ?>"
                     style="animation-delay: <?= $index * 0.05 ?>s; --card-accent: <?= $color ?>;"
                     onclick='alOpenModal(<?= json_encode($a, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>)'>
                    
                    <!-- Card Glow -->
                    <div class="al-card-glow"></div>
                    
                    <?php if ($isFeatured): ?>
                    <div class="al-featured-badge">
                        <i class="fas fa-crown"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Card Header -->
                    <div class="al-card-header">
                        <div class="al-avatar">
                            <?php if (!empty($a['foto'])): ?>
                                <img src="uploads/<?= htmlspecialchars($a['foto']) ?>" alt="<?= clean($a['nama']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="al-avatar-placeholder" style="background: linear-gradient(135deg, <?= $color ?>, <?= $color ?>dd)">
                                    <?= $initial ?>
                                </div>
                            <?php endif; ?>
                            <span class="al-status-dot <?= $a['status'] ?? 'aktif' ?>"></span>
                        </div>
                        
                        <?php if ($ipkInfo): ?>
                        <div class="al-ipk-badge" style="background: <?= $ipkInfo['bg'] ?>; color: <?= $ipkInfo['color'] ?>;">
                            <i class="fas <?= $ipkInfo['icon'] ?>"></i>
                            <?= number_format($a['ipk'], 2) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="al-card-body">
                        <h3 class="al-card-name"><?= clean($a['nama'] ?? 'Tanpa Nama') ?></h3>
                        <p class="al-card-role"><?= clean($a['pekerjaan'] ?? 'Belum bekerja') ?></p>
                        
                        <?php if (!empty($a['perusahaan'])): ?>
                        <div class="al-card-company">
                            <i class="fas fa-building"></i>
                            <span><?= clean($a['perusahaan']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($a['kota'])): ?>
                        <div class="al-card-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= clean($a['kota']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="al-card-year">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Angkatan <?= $a['tahun_lulus'] ?? date('Y') ?></span>
                        </div>
                        
                        <?php if (!empty($a['quote'])): ?>
                        <div class="al-card-quote">
                            <i class="fas fa-quote-left"></i>
                            <p><?= clean(mb_substr($a['quote'], 0, 80)) ?><?= strlen($a['quote'] ?? '') > 80 ? '...' : '' ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Social Links -->
                        <div class="al-card-socials">
                            <?php if (!empty($a['linkedin'])): ?>
                            <a href="<?= htmlspecialchars($a['linkedin']) ?>" target="_blank" class="al-social-link al-linkedin" onclick="event.stopPropagation()">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($a['instagram'])): ?>
                            <a href="https://instagram.com/<?= htmlspecialchars($a['instagram']) ?>" target="_blank" class="al-social-link al-instagram" onclick="event.stopPropagation()">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($a['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($a['email']) ?>" class="al-social-link al-email" onclick="event.stopPropagation()">
                                <i class="fas fa-envelope"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="al-card-footer">
                        <span class="al-footer-text">
                            <i class="fas fa-eye"></i>
                            Lihat Detail
                        </span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Empty Search State -->
            <div class="al-empty-search" id="alEmptySearch" style="display: none;">
                <div class="al-empty-icon">
                    <i class="fas fa-search"></i>
                    <div class="al-empty-pulse"></div>
                </div>
                <h3>Tidak Ada Hasil</h3>
                <p>Alumni yang Anda cari tidak ditemukan. Coba kata kunci lain.</p>
                <button class="al-btn-primary" onclick="alResetFilters()">
                    <i class="fas fa-redo"></i> Reset Pencarian
                </button>
            </div>
            
        <?php else: ?>
            <div class="al-empty-state fade-up">
                <div class="al-empty-icon">
                    <i class="fas fa-user-slash"></i>
                    <div class="al-empty-pulse"></div>
                </div>
                <h3>Belum Ada Data Alumni</h3>
                <p>Data alumni akan segera ditambahkan oleh administrator.</p>
                <?php if ($filterTahun || $filterIpk || $filterKota): ?>
                <button class="al-btn-primary" onclick="alResetFilters()">
                    <i class="fas fa-redo"></i> Reset Filter
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== MODAL DETAIL ===== -->
<div class="al-modal" id="alModal">
    <div class="al-modal-content">
        <button class="al-modal-close" onclick="alCloseModal()" aria-label="Close modal">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="al-modal-inner">
            <!-- Modal Hero -->
            <div class="al-modal-hero" id="alModalHero">
                <div class="al-modal-avatar-wrap">
                    <img src="" alt="Foto Alumni" id="alModalFoto">
                    <div class="al-modal-avatar-ring"></div>
                </div>
                <div class="al-modal-hero-info">
                    <h2 id="alModalNama"></h2>
                    <p class="al-modal-role" id="alModalRole"></p>
                    <div class="al-modal-company" id="alModalCompany" style="display: none;">
                        <i class="fas fa-building"></i>
                        <span id="alModalCompanyText"></span>
                    </div>
                </div>
            </div>
            
            <!-- Modal Badges -->
            <div class="al-modal-badges" id="alModalBadges"></div>
            
            <!-- Modal Body -->
            <div class="al-modal-body">
                <!-- Info Grid -->
                <div class="al-modal-info-grid">
                    <div class="al-modal-info-item">
                        <i class="fas fa-graduation-cap"></i>
                        <div>
                            <span>Tahun Lulus</span>
                            <strong id="alModalTahun"></strong>
                        </div>
                    </div>
                    <div class="al-modal-info-item" id="alModalKotaWrap" style="display: none;">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <span>Kota</span>
                            <strong id="alModalKota"></strong>
                        </div>
                    </div>
                    <div class="al-modal-info-item" id="alModalEmailWrap" style="display: none;">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <span>Email</span>
                            <strong id="alModalEmail"></strong>
                        </div>
                    </div>
                    <div class="al-modal-info-item" id="alModalTeleponWrap" style="display: none;">
                        <i class="fas fa-phone"></i>
                        <div>
                            <span>Telepon</span>
                            <strong id="alModalTelepon"></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Quote -->
                <div class="al-modal-quote" id="alModalQuoteWrap" style="display: none;">
                    <i class="fas fa-quote-left"></i>
                    <p id="alModalQuote"></p>
                </div>
                
                <!-- Social Links -->
                <div class="al-modal-socials" id="alModalSocials"></div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="alToast" class="al-toast">
    <i class="fas fa-info-circle"></i>
    <span id="alToastMsg">Alumni berhasil dimuat</span>
</div>

<!-- ============================================================
     STYLES (prefix: al- = Alumni Journey)
============================================================ -->
<style>
:root {
    --al-primary: #8B5CF6;
    --al-primary-dark: #6D28D9;
    --al-primary-light: #DDD6FE;
    --al-pink: #EC4899;
    --al-pink-dark: #BE185D;
    --al-gold: #F59E0B;
    --al-gold-light: #FEF3C7;
    --al-green: #10B981;
    --al-blue: #3B82F6;
    --al-dark: #0F172A;
    --al-gray-50: #F8FAFC;
    --al-gray-100: #F1F5F9;
    --al-gray-200: #E2E8F0;
    --al-gray-600: #475569;
    --al-gray-700: #374151;
    --al-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --al-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --al-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
    --al-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --al-font-display: 'Space Grotesk', 'Inter', sans-serif;
}

body.gl-dark {
    --al-gray-50: #0B1120;
    --al-gray-100: rgba(255,255,255,.08);
    --al-gray-200: rgba(255,255,255,.1);
    --al-gray-600: #94A3B8;
    --al-gray-700: #CBD5E1;
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

/* ===== READING PROGRESS ===== */
.al-reading-progress {
    position: fixed; top: 0; left: 0; right: 0; height: 3px;
    background: rgba(139,92,246,0.1); z-index: 9998;
}
.al-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--al-primary), var(--al-pink), var(--al-gold));
    box-shadow: 0 0 10px rgba(139,92,246,0.6);
    transition: width 0.1s ease-out;
}

/* ===== HERO ===== */
.al-hero {
    background: linear-gradient(135deg, #1E1B4B 0%, #831843 50%, #0F172A 100%);
    padding: 160px 20px 120px; position: relative; overflow: hidden;
    min-height: 95vh; display: flex; align-items: center; justify-content: center;
}

.al-timeline-canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    z-index: 1; opacity: 0.6;
}

.al-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }

.al-nebula {
    position: absolute; border-radius: 50%;
    filter: blur(100px); opacity: 0.3;
    animation: alNebulaFloat 20s infinite ease-in-out;
}
.al-nebula-1 { width: 600px; height: 600px; background: var(--al-primary); top: -200px; right: -100px; }
.al-nebula-2 { width: 500px; height: 500px; background: var(--al-pink); bottom: -150px; left: -100px; animation-delay: -7s; }
.al-nebula-3 { width: 400px; height: 400px; background: var(--al-gold); top: 40%; left: 30%; animation-delay: -12s; }
@keyframes alNebulaFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(40px, -40px) scale(1.1); }
}

.al-stars-bg {
    position: absolute; inset: 0;
    background-image: 
        radial-gradient(2px 2px at 20px 30px, white, transparent),
        radial-gradient(2px 2px at 60px 70px, rgba(255,255,255,0.8), transparent),
        radial-gradient(1px 1px at 50px 160px, white, transparent),
        radial-gradient(1px 1px at 130px 40px, rgba(255,255,255,0.6), transparent),
        radial-gradient(2px 2px at 90px 120px, white, transparent);
    background-size: 200px 200px;
    animation: alStarsTwinkle 8s infinite;
}
@keyframes alStarsTwinkle {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 0.8; }
}

/* Floating Icons */
.al-floating-icons { position: absolute; inset: 0; z-index: 3; pointer-events: none; }
.al-float-icon {
    position: absolute; font-size: 2.5rem;
    animation: alFloatIcon 10s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
    opacity: 0.35; filter: drop-shadow(0 0 15px rgba(139,92,246,0.4));
    transform: rotate(var(--rot));
}
@keyframes alFloatIcon {
    0%, 100% { transform: translateY(0) rotate(var(--rot)); opacity: 0.35; }
    50% { transform: translateY(-40px) rotate(calc(var(--rot) + 20deg)); opacity: 0.7; }
}

.al-hero-content {
    position: relative; z-index: 4; text-align: center;
    color: #fff; max-width: 1000px; margin: 0 auto;
}

.al-hero-badge {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 10px 24px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    font-size: .85rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 700; letter-spacing: 2px; position: relative;
    font-family: var(--al-font-mono);
}
.al-badge-glow {
    position: absolute; inset: 0; border-radius: 50px;
    border: 2px solid var(--al-pink);
    animation: alBadgeGlow 2s infinite;
}
@keyframes alBadgeGlow { 
    0% { transform: scale(1); opacity: 0.8; box-shadow: 0 0 0 0 rgba(236,72,153,0.4); } 
    100% { transform: scale(1.15); opacity: 0; box-shadow: 0 0 30px 10px rgba(236,72,153,0); } 
}
.al-hero-badge i { color: var(--al-pink); }

.al-hero-title {
    font-size: clamp(3rem, 9vw, 6.5rem); font-weight: 900;
    margin-bottom: 24px; letter-spacing: -0.03em; line-height: 0.95;
    font-family: var(--al-font-display);
}
.al-title-line { display: block; }
.al-title-journey {
    position: relative; display: inline-block;
    background: linear-gradient(135deg, #F59E0B 0%, #EC4899 50%, #8B5CF6 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 200%;
    animation: alJourneyGradient 5s ease infinite;
}
.al-title-journey::after {
    content: ''; position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 120%; height: 120%;
    background: radial-gradient(circle, rgba(236,72,153,0.15), transparent 60%);
    z-index: -1; border-radius: 50%;
    animation: alJourneyGlow 3s ease-in-out infinite;
}
@keyframes alJourneyGradient { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
@keyframes alJourneyGlow { 0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); } 50% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); } }

.al-hero-subtitle {
    font-size: 1.2rem; opacity: .9; max-width: 700px;
    margin: 0 auto 40px; line-height: 1.7;
    display: inline-block; min-height: 1.7em;
}
.al-cursor { animation: alBlink 1s infinite; color: var(--al-pink); font-weight: 300; }
@keyframes alBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

/* Hero Stats */
.al-hero-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 16px; max-width: 900px; margin: 0 auto;
}
.al-stat-pill {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 16px;
    backdrop-filter: blur(12px); transition: all 0.3s;
    position: relative; overflow: hidden;
}
.al-stat-pill:hover {
    transform: translateY(-4px);
    border-color: var(--al-pink);
    box-shadow: 0 12px 24px rgba(236,72,153,0.2);
}
.al-stat-pill i {
    font-size: 1.5rem; color: var(--al-pink);
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(236,72,153,0.2); display: flex;
    align-items: center; justify-content: center;
    flex-shrink: 0;
}
.al-stat-num {
    display: block; font-size: 1.5rem; font-weight: 900;
    color: #fff; line-height: 1; font-family: var(--al-font-display);
}
.al-stat-label {
    font-size: 0.7rem; color: rgba(255,255,255,.6);
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-top: 2px; display: block;
}

/* Scroll Indicator */
.al-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%;
    transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.al-scroll-arrow { animation: alArrowBounce 2s infinite; }
@keyframes alArrowBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(10px); }
}

/* ===== FEATURED SECTION ===== */
.al-featured-section {
    padding: 60px 20px;
    background: var(--al-gray-50);
    position: relative;
}
body.gl-dark .al-featured-section { background: #0B1120; }

.al-featured-card {
    position: relative; max-width: 900px; margin: 0 auto;
    background: #fff; border-radius: 24px; overflow: hidden;
    box-shadow: var(--al-shadow-lg); border: 1px solid var(--al-gray-100);
}
body.gl-dark .al-featured-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.al-featured-glow {
    position: absolute; inset: -2px; border-radius: 24px;
    background: conic-gradient(from 0deg, var(--al-primary), var(--al-pink), var(--al-gold), transparent 50%);
    opacity: 0.3; filter: blur(20px); z-index: 0;
    animation: alFeaturedGlow 4s linear infinite;
}
@keyframes alFeaturedGlow { to { transform: rotate(360deg); } }

.al-featured-ribbon {
    position: absolute; top: 20px; right: -35px; z-index: 10;
    background: linear-gradient(135deg, #FDE047, #F59E0B);
    color: #78350F; padding: 8px 50px; font-size: 0.8rem;
    font-weight: 800; transform: rotate(45deg);
    box-shadow: 0 4px 12px rgba(245,158,11,0.4);
    display: flex; align-items: center; gap: 8px;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.al-featured-ribbon i { font-size: 0.9rem; }

.al-featured-content {
    position: relative; z-index: 1;
    display: flex; gap: 30px; padding: 40px;
    align-items: center;
}

.al-featured-avatar {
    position: relative; width: 150px; height: 150px; flex-shrink: 0;
}
.al-featured-avatar img,
.al-featured-avatar .al-avatar-placeholder {
    width: 100%; height: 100%; border-radius: 50%;
    object-fit: cover; border: 4px solid var(--al-gold);
    box-shadow: 0 8px 24px rgba(245,158,11,0.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 3rem; font-weight: 700; color: #fff;
    font-family: var(--al-font-display);
    background: linear-gradient(135deg, var(--al-primary), var(--al-pink));
}
.al-avatar-ring {
    position: absolute; inset: -10px; border-radius: 50%;
    border: 2px dashed var(--al-gold);
    animation: alRingSpin 15s linear infinite;
}
@keyframes alRingSpin { to { transform: rotate(360deg); } }

.al-featured-info { flex: 1; }
.al-featured-name {
    font-size: 1.8rem; font-weight: 800; color: var(--al-dark);
    margin: 0 0 8px 0; font-family: var(--al-font-display);
}
body.gl-dark .al-featured-name { color: #F1F5F9; }

.al-featured-role {
    font-size: 1.1rem; color: var(--al-gray-600);
    margin: 0 0 12px 0;
}
body.gl-dark .al-featured-role { color: #94A3B8; }

.al-featured-company {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 14px; background: var(--al-gray-100);
    border-radius: 20px; font-size: 0.9rem; color: var(--al-gray-700);
    margin-bottom: 16px;
}
body.gl-dark .al-featured-company { background: rgba(255,255,255,.08); color: #CBD5E1; }
.al-featured-company i { color: var(--al-primary); }

.al-featured-quote {
    position: relative; padding: 16px 20px;
    background: linear-gradient(135deg, rgba(139,92,246,0.08), rgba(236,72,153,0.05));
    border-radius: 12px; border-left: 4px solid var(--al-pink);
    margin-bottom: 16px;
}
.al-featured-quote i {
    position: absolute; top: 10px; left: 10px;
    color: var(--al-pink); opacity: 0.5; font-size: 0.8rem;
}
.al-featured-quote p {
    font-size: 0.95rem; color: var(--al-gray-700);
    line-height: 1.6; font-style: italic;
    padding-left: 20px; margin: 0;
}
body.gl-dark .al-featured-quote p { color: #CBD5E1; }

.al-featured-badges {
    display: flex; gap: 8px; flex-wrap: wrap;
}
.al-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 20px; font-size: 0.78rem;
    font-weight: 700; letter-spacing: 0.3px;
}
.al-badge-year {
    background: var(--al-gray-100); color: var(--al-gray-700);
}
body.gl-dark .al-badge-year { background: rgba(255,255,255,.08); color: #CBD5E1; }

/* ===== MAIN SECTION ===== */
.al-main-section {
    padding: 60px 20px 100px;
    background: var(--al-gray-50);
}
body.gl-dark .al-main-section { background: #0B1120; }

/* ===== FILTER CONSOLE ===== */
.al-filter-console {
    background: #fff; border-radius: 20px;
    box-shadow: var(--al-shadow-md); overflow: hidden;
    border: 1px solid var(--al-gray-100); margin-bottom: 40px;
}
body.gl-dark .al-filter-console { background: #1E293B; border-color: rgba(255,255,255,.08); }

.al-console-header {
    background: var(--al-dark); padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
    color: #94A3B8; font-family: var(--al-font-mono); font-size: 0.8rem;
}
.al-console-dots { display: flex; gap: 5px; }
.al-console-dots .dot { width: 11px; height: 11px; border-radius: 50%; }
.al-console-dots .red { background: #FF5F56; }
.al-console-dots .yellow { background: #FFBD2E; }
.al-console-dots .green { background: #27C93F; }
.al-console-header i { color: var(--al-pink); }
.al-console-header span { color: #E2E8F0; }
.al-console-status {
    margin-left: auto; color: var(--al-pink);
    font-size: 0.7rem; letter-spacing: 1px;
    animation: alBlink 2s infinite;
}

.al-console-body { padding: 24px; display: flex; flex-direction: column; gap: 20px; }

.al-search-wrap {
    position: relative; display: flex; align-items: center;
}
.al-search-wrap > i {
    position: absolute; left: 18px; top: 50%;
    transform: translateY(-50%); color: var(--al-gray-600);
    z-index: 1; font-size: 1rem;
}
.al-search-wrap input {
    width: 100%; padding: 16px 100px 16px 50px;
    border: 1.5px solid var(--al-gray-200); border-radius: 14px;
    background: var(--al-gray-50); font-family: inherit;
    font-size: 1rem; color: var(--al-dark);
    transition: all 0.3s;
}
body.gl-dark .al-search-wrap input {
    background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1); color: #E2E8F0;
}
.al-search-wrap input:focus {
    outline: none; border-color: var(--al-primary);
    box-shadow: 0 0 0 4px rgba(139,92,246,0.15);
}

.al-search-hint {
    position: absolute; right: 18px; top: 50%;
    transform: translateY(-50%);
    display: flex; gap: 3px; align-items: center;
    color: var(--al-gray-600); font-size: 0.75rem;
    pointer-events: none;
}
.al-search-hint kbd {
    background: var(--al-gray-100); padding: 3px 8px;
    border-radius: 5px; font-family: var(--al-font-mono);
    font-size: 0.72rem; border: 1px solid var(--al-gray-200);
}
body.gl-dark .al-search-hint kbd {
    background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.1); color: #CBD5E1;
}

.al-filter-group {
    padding-top: 16px; border-top: 1px solid var(--al-gray-100);
}
body.gl-dark .al-filter-group { border-color: rgba(255,255,255,.08); }

.al-filter-group label {
    font-size: 0.72rem; font-weight: 700; color: var(--al-gray-600);
    letter-spacing: 1.5px; font-family: var(--al-font-mono);
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 12px;
}
body.gl-dark .al-filter-group label { color: #94A3B8; }
.al-filter-group label i { color: var(--al-primary); }

.al-filter-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.al-pill {
    padding: 8px 16px; border-radius: 24px; font-size: 0.82rem;
    font-weight: 600; color: var(--al-gray-700);
    background: var(--al-gray-100); border: 1.5px solid transparent;
    text-decoration: none; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 6px;
}
body.gl-dark .al-pill { background: rgba(255,255,255,.05); color: #CBD5E1; }
.al-pill i { font-size: 0.7rem; }
.al-pill:hover {
    transform: translateY(-2px);
    border-color: var(--al-primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.al-pill.active {
    background: var(--al-primary); 
    color: #fff;
    border-color: var(--al-primary);
    box-shadow: 0 4px 16px rgba(139,92,246,0.3);
}
.al-pill-gold.active { background: var(--al-gold); border-color: var(--al-gold); box-shadow: 0 4px 16px rgba(245,158,11,0.3); }
.al-pill-green.active { background: var(--al-green); border-color: var(--al-green); box-shadow: 0 4px 16px rgba(16,185,129,0.3); }
.al-pill-blue.active { background: var(--al-blue); border-color: var(--al-blue); box-shadow: 0 4px 16px rgba(59,130,246,0.3); }

.al-filter-actions {
    padding-top: 16px; border-top: 1px solid var(--al-gray-100);
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 12px;
}
body.gl-dark .al-filter-actions { border-color: rgba(255,255,255,.08); }

.al-results-info {
    color: var(--al-gray-600); display: flex; align-items: center; gap: 8px;
    font-size: 0.85rem;
}
body.gl-dark .al-results-info { color: #94A3B8; }
.al-results-info i { color: var(--al-primary); }
.al-results-info strong { 
    color: var(--al-dark); font-family: var(--al-font-display); 
    font-size: 1.1rem; font-weight: 800;
}
body.gl-dark .al-results-info strong { color: #F1F5F9; }

.al-btn-reset {
    padding: 10px 20px; background: rgba(239, 68, 68, 0.1);
    color: #DC2626; border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 8px; font-family: var(--al-font-mono);
    font-size: 0.85rem; font-weight: 600; cursor: pointer;
    transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 8px;
}
.al-btn-reset:hover { background: #DC2626; color: #fff; transform: translateY(-2px); }

/* ===== ALUMNI GRID ===== */
.al-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.al-card {
    position: relative; background: #fff; border-radius: 20px;
    overflow: hidden; border: 1px solid var(--al-gray-100);
    box-shadow: var(--al-shadow-sm); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer; display: flex; flex-direction: column;
}
body.gl-dark .al-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.al-card-glow {
    position: absolute; top: -50%; left: 50%;
    transform: translateX(-50%);
    width: 200%; height: 200%;
    background: radial-gradient(circle, var(--card-accent, var(--al-primary)), transparent 60%);
    opacity: 0; transition: opacity 0.5s;
    filter: blur(60px); pointer-events: none; z-index: 0;
}
.al-card:hover .al-card-glow { opacity: 0.15; }

.al-card::before {
    content: ''; position: absolute; inset: 0; border-radius: 20px; padding: 2px;
    background: conic-gradient(from 0deg, transparent, var(--card-accent, var(--al-primary)), var(--al-pink), transparent 40%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none; z-index: 5;
}
.al-card:hover::before { opacity: 1; animation: alRotateBorder 4s linear infinite; }
@keyframes alRotateBorder { to { transform: rotate(360deg); } }

.al-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--al-shadow-lg), 0 0 30px rgba(139,92,246,0.2);
    border-color: var(--card-accent, var(--al-primary));
}

.al-card.al-featured { border-color: var(--al-gold); }

.al-featured-badge {
    position: absolute; top: 16px; right: 16px; z-index: 10;
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #FDE047, #F59E0B);
    color: #78350F; display: flex; align-items: center; justify-content: center;
    font-size: 1rem; box-shadow: 0 4px 12px rgba(245,158,11,0.4);
    animation: alPulse 2s infinite;
}
@keyframes alPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Card Header */
.al-card-header {
    position: relative; padding: 24px 24px 0;
    display: flex; justify-content: center;
}

.al-avatar {
    position: relative; width: 90px; height: 90px;
}
.al-avatar img {
    width: 100%; height: 100%; border-radius: 50%;
    object-fit: cover; border: 4px solid #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    transition: transform 0.3s;
}
.al-card:hover .al-avatar img { transform: scale(1.05); }

.al-avatar-placeholder {
    width: 100%; height: 100%; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; font-weight: 700; color: #fff;
    font-family: var(--al-font-display);
    border: 4px solid #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.al-status-dot {
    position: absolute; bottom: 4px; right: 4px;
    width: 16px; height: 16px; border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px rgba(255,255,255,0.3);
}
.al-status-dot.aktif {
    background: var(--al-green);
    box-shadow: 0 0 0 3px rgba(16,185,129,0.3);
}
.al-status-dot.nonaktif {
    background: #94A3B8;
}

.al-ipk-badge {
    position: absolute; top: 0; right: 20px;
    padding: 4px 12px; border-radius: 20px;
    font-size: 0.72rem; font-weight: 700;
    display: flex; align-items: center; gap: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.al-ipk-badge i { font-size: 0.65rem; }

/* Card Body */
.al-card-body {
    padding: 20px 24px; flex: 1; display: flex; flex-direction: column;
    text-align: center; position: relative; z-index: 2;
}

.al-card-name {
    font-size: 1.15rem; font-weight: 800; line-height: 1.3;
    color: var(--al-dark); margin: 0 0 6px 0;
    font-family: var(--al-font-display);
}
body.gl-dark .al-card-name { color: #F1F5F9; }

.al-card-role {
    font-size: 0.88rem; color: var(--al-gray-600);
    margin: 0 0 14px 0;
}
body.gl-dark .al-card-role { color: #94A3B8; }

.al-card-company,
.al-card-location {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 12px; background: var(--al-gray-50);
    border-radius: 10px; margin-bottom: 8px;
    font-size: 0.82rem; color: var(--al-gray-700);
    width: fit-content; margin-left: auto; margin-right: auto;
}
body.gl-dark .al-card-company,
body.gl-dark .al-card-location { background: rgba(255,255,255,.05); color: #CBD5E1; }

.al-card-company i,
.al-card-location i {
    color: var(--al-primary); font-size: 0.75rem;
}

.al-card-year {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; background: var(--al-gray-100);
    border-radius: 20px; font-size: 0.78rem; font-weight: 600;
    color: var(--al-gray-700); width: fit-content;
    margin: 0 auto 14px;
}
body.gl-dark .al-card-year { background: rgba(255,255,255,.08); color: #CBD5E1; }
.al-card-year i { color: var(--al-pink); }

.al-card-quote {
    position: relative; padding: 12px 16px;
    background: linear-gradient(135deg, rgba(139,92,246,0.08), rgba(236,72,153,0.05));
    border-radius: 10px; border-left: 3px solid var(--al-pink);
    margin-bottom: 14px; text-align: left;
}
.al-card-quote i {
    position: absolute; top: 8px; left: 8px;
    color: var(--al-pink); opacity: 0.5; font-size: 0.65rem;
}
.al-card-quote p {
    font-size: 0.8rem; color: var(--al-gray-700);
    line-height: 1.5; font-style: italic;
    padding-left: 16px; margin: 0;
}
body.gl-dark .al-card-quote p { color: #CBD5E1; }

.al-card-socials {
    display: flex; gap: 8px; justify-content: center;
    margin-top: auto;
}

.al-social-link {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    text-decoration: none; transition: all 0.3s;
    font-size: 0.9rem;
}

.al-linkedin {
    background: rgba(10,102,194,0.1); color: #0A66C2;
}
.al-linkedin:hover {
    background: #0A66C2; color: #fff;
    transform: translateY(-3px);
}

.al-instagram {
    background: linear-gradient(135deg, rgba(225,48,108,0.1), rgba(131,58,180,0.1));
    color: #E1306C;
}
.al-instagram:hover {
    background: linear-gradient(135deg, #E1306C, #833AB4);
    color: #fff; transform: translateY(-3px);
}

.al-email {
    background: rgba(239,68,68,0.1); color: #EF4444;
}
.al-email:hover {
    background: #EF4444; color: #fff;
    transform: translateY(-3px);
}

/* Card Footer */
.al-card-footer {
    padding: 14px 24px; background: var(--al-gray-50);
    border-top: 1px solid var(--al-gray-100);
    display: flex; justify-content: space-between; align-items: center;
    font-size: 0.82rem;
}
body.gl-dark .al-card-footer { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.08); }

.al-footer-text {
    color: var(--al-gray-600); display: flex; align-items: center; gap: 6px;
    font-weight: 600;
}
body.gl-dark .al-footer-text { color: #94A3B8; }
.al-footer-text i { color: var(--al-primary); }

.al-card-footer > i {
    color: var(--al-primary); transition: transform 0.3s;
}
.al-card:hover .al-card-footer > i { transform: translateX(4px); }

/* Empty States */
.al-empty-search,
.al-empty-state {
    text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px;
    border: 2px dashed var(--al-gray-200);
    max-width: 600px; margin: 0 auto;
}
body.gl-dark .al-empty-search,
body.gl-dark .al-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }

.al-empty-icon {
    position: relative; width: 100px; height: 100px;
    margin: 0 auto 24px; display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: var(--al-gray-600);
}
.al-empty-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--al-primary);
    animation: alEmptyPulse 2s infinite;
}
@keyframes alEmptyPulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

.al-empty-search h3,
.al-empty-state h3 {
    font-size: 1.5rem; margin: 0 0 8px 0; color: var(--al-dark);
    font-weight: 800; font-family: var(--al-font-display);
}
body.gl-dark .al-empty-search h3,
body.gl-dark .al-empty-state h3 { color: #F1F5F9; }

.al-empty-search p,
.al-empty-state p { color: var(--al-gray-600); margin-bottom: 20px; }

.al-btn-primary {
    padding: 12px 24px; background: var(--al-primary);
    color: #fff; border: none; border-radius: 10px;
    font-weight: 700; cursor: pointer; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 8px;
    font-family: inherit; font-size: 0.95rem;
}
.al-btn-primary:hover { background: var(--al-primary-dark); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(139,92,246,0.3); }

/* ===== MODAL ===== */
.al-modal {
    position: fixed; inset: 0; z-index: 10000;
    background: rgba(2,6,23,0.9); backdrop-filter: blur(12px);
    display: none; align-items: center; justify-content: center;
    padding: 20px; opacity: 0; transition: opacity 0.3s;
}
.al-modal.open { display: flex; opacity: 1; }

.al-modal-content {
    background: #fff; border-radius: 28px; max-width: 700px;
    width: 100%; max-height: 90vh; overflow: hidden;
    box-shadow: 0 30px 60px rgba(0,0,0,0.4);
    position: relative; transform: scale(0.9) translateY(20px);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
body.gl-dark .al-modal-content { background: #1E293B; }
.al-modal.open .al-modal-content { transform: scale(1) translateY(0); }

.al-modal-close {
    position: absolute; top: 20px; right: 20px; z-index: 10;
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);
    border: none; color: var(--al-dark); font-size: 1.1rem;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all 0.3s;
}
body.gl-dark .al-modal-close { background: rgba(0,0,0,0.5); color: #fff; }
.al-modal-close:hover {
    background: #EF4444; color: #fff;
    transform: rotate(90deg) scale(1.1);
}

.al-modal-inner {
    position: relative; z-index: 1;
    max-height: 90vh; overflow-y: auto;
}
.al-modal-inner::-webkit-scrollbar { width: 6px; }
.al-modal-inner::-webkit-scrollbar-thumb { background: var(--al-primary); border-radius: 3px; }

/* Modal Hero */
.al-modal-hero {
    padding: 40px 40px 30px;
    background: linear-gradient(135deg, var(--al-dark), #1E1B4B, var(--al-primary-dark));
    color: #fff; display: flex; gap: 24px;
    align-items: center; position: relative;
    border-bottom: 3px solid var(--al-pink);
}

.al-modal-avatar-wrap {
    position: relative; width: 120px; height: 120px; flex-shrink: 0;
}
.al-modal-avatar-wrap img,
.al-modal-avatar-wrap .al-avatar-placeholder {
    width: 100%; height: 100%; border-radius: 50%;
    object-fit: cover; border: 4px solid rgba(255,255,255,0.2);
    position: relative; z-index: 2;
}
.al-modal-avatar-ring {
    position: absolute; inset: -8px; border-radius: 50%;
    border: 2px dashed var(--al-pink);
    animation: alRingSpin 15s linear infinite;
}

.al-modal-hero-info { flex: 1; }
.al-modal-hero-info h2 {
    font-size: 1.8rem; margin: 0 0 8px 0; font-weight: 800;
    font-family: var(--al-font-display); line-height: 1.2;
}
.al-modal-role {
    font-size: 1rem; opacity: 0.9; margin: 0 0 12px 0;
}
.al-modal-company {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 14px; background: rgba(255,255,255,0.1);
    border-radius: 20px; font-size: 0.85rem;
    backdrop-filter: blur(10px);
}
.al-modal-company i { color: var(--al-pink); }

/* Modal Badges */
.al-modal-badges {
    display: flex; gap: 8px; padding: 16px 40px;
    background: var(--al-gray-50); border-bottom: 1px solid var(--al-gray-100);
    flex-wrap: wrap;
}
body.gl-dark .al-modal-badges { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.08); }

/* Modal Body */
.al-modal-body { padding: 30px 40px; }

.al-modal-info-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 12px; margin-bottom: 24px;
}
.al-modal-info-item {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; background: var(--al-gray-50);
    border-radius: 12px; border: 1px solid var(--al-gray-100);
}
body.gl-dark .al-modal-info-item { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.08); }

.al-modal-info-item i {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, var(--al-primary), var(--al-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.al-modal-info-item div { flex: 1; }
.al-modal-info-item span {
    font-size: 0.7rem; color: var(--al-gray-600);
    text-transform: uppercase; letter-spacing: 0.5px;
    display: block; margin-bottom: 2px;
}
body.gl-dark .al-modal-info-item span { color: #94A3B8; }
.al-modal-info-item strong {
    font-size: 0.95rem; color: var(--al-dark);
    font-family: var(--al-font-mono); font-weight: 700;
    word-break: break-word;
}
body.gl-dark .al-modal-info-item strong { color: #F1F5F9; }

.al-modal-quote {
    position: relative; padding: 20px 24px;
    background: linear-gradient(135deg, rgba(139,92,246,0.08), rgba(236,72,153,0.05));
    border-radius: 14px; border-left: 4px solid var(--al-pink);
    margin-bottom: 24px;
}
.al-modal-quote i {
    color: var(--al-pink); margin-right: 8px; opacity: 0.6;
}
.al-modal-quote p {
    font-size: 1rem; color: var(--al-gray-700);
    line-height: 1.7; font-style: italic; margin: 0;
}
body.gl-dark .al-modal-quote p { color: #CBD5E1; }

.al-modal-socials {
    display: flex; gap: 12px; justify-content: center;
    padding-top: 20px; border-top: 1px solid var(--al-gray-100);
}
body.gl-dark .al-modal-socials { border-color: rgba(255,255,255,.08); }

.al-modal-socials .al-social-link {
    width: 48px; height: 48px; font-size: 1.2rem;
}

/* ===== TOAST ===== */
.al-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--al-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    z-index: 10001; opacity: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.9rem;
}
body.gl-dark .al-toast { background: #fff; color: var(--al-dark); }
.al-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.al-toast i { color: var(--al-pink); font-size: 1.2rem; }

/* ===== ANIMATIONS ===== */
.fade-up {
    opacity: 0; transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .al-hero-stats { grid-template-columns: repeat(2, 1fr); }
    .al-featured-content { flex-direction: column; text-align: center; }
    .al-featured-avatar { margin: 0 auto; }
    .al-featured-company,
    .al-card-company,
    .al-card-location,
    .al-card-year { margin-left: auto; margin-right: auto; }
    .al-modal-info-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .al-hero { padding: 120px 20px 80px; min-height: 85vh; }
    .al-hero-title { font-size: 3rem; }
    .al-hero-stats { grid-template-columns: 1fr; gap: 10px; }
    .al-stat-pill { justify-content: center; }
    .al-floating-icons { display: none; }
    .al-console-body { padding: 16px; }
    .al-search-hint { display: none; }
    .al-search-wrap input { padding-right: 16px; }
    .al-filter-pills { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 6px; }
    .al-pill { white-space: nowrap; flex-shrink: 0; }
    .al-grid { grid-template-columns: 1fr; }
    .al-featured-card { margin: 0 10px; }
    .al-featured-content { padding: 30px 20px; }
    .al-featured-ribbon { font-size: 0.7rem; padding: 6px 40px; }
    .al-modal-hero { padding: 30px 20px; flex-direction: column; text-align: center; }
    .al-modal-avatar-wrap { width: 100px; height: 100px; margin: 0 auto; }
    .al-modal-hero-info h2 { font-size: 1.4rem; }
    .al-modal-badges { padding: 12px 20px; justify-content: center; }
    .al-modal-body { padding: 20px; }
}
@media (max-width: 480px) {
    .al-hero-title { font-size: 2.3rem; }
    .al-hero-badge { font-size: 0.7rem; padding: 8px 16px; letter-spacing: 1px; }
    .al-card { border-radius: 16px; }
    .al-card-name { font-size: 1rem; }
    .al-modal-content { border-radius: 20px; }
    .al-modal-hero-info h2 { font-size: 1.2rem; }
    .al-stat-num { font-size: 1.3rem; }
}

/* Performance optimizations */
.al-main-section {
    content-visibility: auto;
    contain-intrinsic-size: auto 2000px;
}

body.is-scrolling .al-nebula,
body.is-scrolling .al-stars-bg,
body.is-scrolling .al-float-icon,
body.is-scrolling .al-progress-fill,
body.is-scrolling .al-badge-glow,
body.is-scrolling .al-featured-glow,
body.is-scrolling .al-avatar-ring,
body.is-scrolling .al-modal-avatar-ring {
    animation-play-state: paused !important;
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    .al-nebula,
    .al-floating-icons {
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
        const heroSection = document.querySelector('.al-hero');
        if (heroSection) {
            new IntersectionObserver((entries) => {
                heroVisible = entries[0]?.isIntersecting ?? true;
            }, { threshold: 0.01 }).observe(heroSection);
        }

        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                entry.target.classList.toggle('al-offscreen', !entry.isIntersecting);
            });
        }, { rootMargin: '300px 0px', threshold: 0 });

        document.querySelectorAll('.al-hero, .al-main-section').forEach(section => {
            sectionObserver.observe(section);
        });
    }

    // ===== 1. Timeline Canvas Animation (OPTIMIZED) =====
    const canvas = document.getElementById('alTimelineCanvas');
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
            const colors = ['#8B5CF6', '#EC4899', '#F59E0B', '#10B981', '#3B82F6'];
            
            for (let i = 0; i < numNodes; i++) {
                nodes.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    size: Math.random() * 3 + 1,
                    vx: (Math.random() - 0.5) * 0.3,
                    vy: (Math.random() - 0.5) * 0.3,
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
            ctx.globalAlpha = 0.6;
            ctx.fill();
            ctx.globalAlpha = 1;
            
            // Glow effect
            ctx.beginPath();
            ctx.arc(node.x, node.y, node.size * pulseScale * 2, 0, Math.PI * 2);
            ctx.fillStyle = node.color;
            ctx.globalAlpha = 0.1;
            ctx.fill();
            ctx.globalAlpha = 1;
        }

        function drawConnections() {
            const maxDist = 150;
            const maxDistSq = maxDist * maxDist;
            
            for (let i = 0; i < nodes.length; i++) {
                for (let j = i + 1; j < nodes.length; j++) {
                    const dx = nodes[i].x - nodes[j].x;
                    const dy = nodes[i].y - nodes[j].y;
                    const distSq = dx * dx + dy * dy;
                    
                    if (distSq < maxDistSq) {
                        const distance = Math.sqrt(distSq);
                        const opacity = (1 - distance / maxDist) * 0.3;
                        
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(139, 92, 246, ${opacity})`;
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
    const textElement = document.getElementById('alTypingText');
    if (textElement) {
        const phrases = [
            "Telusuri perjalanan karir alumni yang sukses di berbagai bidang teknologi.",
            "Dari ruang kelas menuju panggung industri global.",
            "Jejak prestasi yang menginspirasi generasi inovator berikutnya.",
            "Alumni kami bekerja di perusahaan teknologi terkemuka dunia."
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
    const counters = document.querySelectorAll('.al-stat-num[data-count]');
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
    const progressFill = document.getElementById('alProgressFill');
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
    const searchInput = document.getElementById('alSearchInput');
    const grid = document.getElementById('alGrid');
    const emptySearch = document.getElementById('alEmptySearch');
    const resultsCount = document.getElementById('alResultsCount');

    if (searchInput && grid) {
        let searchDebounce;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                const query = e.target.value.toLowerCase().trim();
                const cards = grid.querySelectorAll('.al-card');
                let visibleCount = 0;

                cards.forEach(card => {
                    const name = card.getAttribute('data-name') || '';
                    const pekerjaan = card.getAttribute('data-pekerjaan') || '';
                    const perusahaan = card.getAttribute('data-perusahaan') || '';
                    
                    if (!query || name.includes(query) || pekerjaan.includes(query) || perusahaan.includes(query)) {
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

    window.alResetFilters = function() {
        window.location.href = 'alumni.php';
    };

    // ===== 7. Keyboard Shortcuts =====
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) searchInput.focus();
        }
        if (e.key === 'Escape') {
            alCloseModal();
        }
    });

    // ===== 8. Modal Functions =====
    const modal = document.getElementById('alModal');

    window.alOpenModal = function(data) {
        if (!modal || !data) return;

        // Set hero
        const hero = document.getElementById('alModalHero');
        const foto = document.getElementById('alModalFoto');
        const initial = (data.nama || 'A').charAt(0).toUpperCase();
        const colors = ['#8B5CF6', '#EC4899', '#F59E0B', '#10B981', '#3B82F6'];
        const color = colors[initial.charCodeAt(0) % colors.length];
        
        if (data.foto) {
            foto.src = 'uploads/' + data.foto;
            foto.style.display = 'block';
        } else {
            foto.style.display = 'none';
            const placeholder = document.createElement('div');
            placeholder.className = 'al-avatar-placeholder';
            placeholder.style.background = `linear-gradient(135deg, ${color}, ${color}dd)`;
            placeholder.textContent = initial;
            foto.parentElement.appendChild(placeholder);
        }
        
        hero.style.background = `linear-gradient(135deg, #0F172A, #1E1B4B, ${color})`;
        
        // Set basic info
        document.getElementById('alModalNama').textContent = data.nama || '-';
        document.getElementById('alModalRole').textContent = data.pekerjaan || 'Belum bekerja';
        document.getElementById('alModalTahun').textContent = data.tahun_lulus || '-';
        
        // Company
        const companyWrap = document.getElementById('alModalCompany');
        if (data.perusahaan) {
            document.getElementById('alModalCompanyText').textContent = data.perusahaan;
            companyWrap.style.display = 'inline-flex';
        } else {
            companyWrap.style.display = 'none';
        }
        
        // Badges
        const badgesEl = document.getElementById('alModalBadges');
        let badgesHtml = `<span class="al-badge al-badge-year"><i class="fas fa-graduation-cap"></i> Angkatan ${data.tahun_lulus || '-'}</span>`;
        
        if (data.ipk && data.ipk > 0) {
            const ipkInfo = getIpkLabel(data.ipk);
            if (ipkInfo) {
                badgesHtml += `<span class="al-badge" style="background: ${ipkInfo.bg}; color: ${ipkInfo.color};"><i class="fas ${ipkInfo.icon}"></i> IPK ${Number(data.ipk).toFixed(2)} • ${ipkInfo.label}</span>`;
            }
        }
        
        if (data.is_featured == 1) {
            badgesHtml += `<span class="al-badge" style="background: rgba(245,158,11,0.15); color: #F59E0B;"><i class="fas fa-crown"></i> Alumni Unggulan</span>`;
        }
        
        badgesEl.innerHTML = badgesHtml;
        
        // Info grid
        const kotaWrap = document.getElementById('alModalKotaWrap');
        const emailWrap = document.getElementById('alModalEmailWrap');
        const teleponWrap = document.getElementById('alModalTeleponWrap');
        
        if (data.kota) {
            document.getElementById('alModalKota').textContent = data.kota;
            kotaWrap.style.display = 'flex';
        } else {
            kotaWrap.style.display = 'none';
        }
        
        if (data.email) {
            document.getElementById('alModalEmail').textContent = data.email;
            emailWrap.style.display = 'flex';
        } else {
            emailWrap.style.display = 'none';
        }
        
        if (data.telepon) {
            document.getElementById('alModalTelepon').textContent = data.telepon;
            teleponWrap.style.display = 'flex';
        } else {
            teleponWrap.style.display = 'none';
        }
        
        // Quote
        const quoteWrap = document.getElementById('alModalQuoteWrap');
        if (data.quote) {
            document.getElementById('alModalQuote').textContent = data.quote;
            quoteWrap.style.display = 'block';
        } else {
            quoteWrap.style.display = 'none';
        }
        
        // Social links
        const socialsEl = document.getElementById('alModalSocials');
        let socialsHtml = '';
        
        if (data.linkedin) {
            socialsHtml += `<a href="${data.linkedin}" target="_blank" class="al-social-link al-linkedin"><i class="fab fa-linkedin-in"></i></a>`;
        }
        if (data.instagram) {
            socialsHtml += `<a href="https://instagram.com/${data.instagram}" target="_blank" class="al-social-link al-instagram"><i class="fab fa-instagram"></i></a>`;
        }
        if (data.email) {
            socialsHtml += `<a href="mailto:${data.email}" class="al-social-link al-email"><i class="fas fa-envelope"></i></a>`;
        }
        if (data.telepon) {
            socialsHtml += `<a href="tel:${data.telepon}" class="al-social-link al-email" style="background: rgba(16,185,129,0.1); color: #10B981;"><i class="fas fa-phone"></i></a>`;
        }
        
        socialsEl.innerHTML = socialsHtml;
        
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.alCloseModal = function() {
        if (modal) {
            modal.classList.remove('open');
            document.body.style.overflow = '';
            
            // Cleanup placeholder if exists
            const foto = document.getElementById('alModalFoto');
            const placeholder = foto.parentElement.querySelector('.al-avatar-placeholder');
            if (placeholder) placeholder.remove();
        }
    };

    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) alCloseModal();
        });
    }

    // ===== 9. IPK Label Helper (for modal) =====
    window.getIpkLabel = function(ipk) {
        if (ipk >= 3.51) return {label: 'Cumlaude', color: '#F59E0B', bg: 'rgba(245,158,11,0.12)', icon: 'fa-crown'};
        if (ipk >= 3.01) return {label: 'Sangat Baik', color: '#10B981', bg: 'rgba(16,185,129,0.12)', icon: 'fa-star'};
        if (ipk >= 2.76) return {label: 'Baik', color: '#3B82F6', bg: 'rgba(59,130,246,0.12)', icon: 'fa-check-circle'};
        if (ipk >= 2.00) return {label: 'Cukup', color: '#6B7280', bg: 'rgba(107,114,128,0.12)', icon: 'fa-circle'};
        return null;
    };

    // ===== 10. Toast =====
    window.alShowToast = function(message) {
        const toast = document.getElementById('alToast');
        if (!toast) return;
        const msgEl = document.getElementById('alToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // ===== 11. Parallax Nebulas (THROTTLED) =====
    const heroSection = document.querySelector('.al-hero');
    let parallaxTicking = false;
    if (heroSection && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            if (!parallaxTicking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.pageYOffset;
                    const nebulas = heroSection.querySelectorAll('.al-nebula');
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
        alShowToast('✨ Welcome to Alumni Journey!');
    }, 1500);

    console.log('%c╔══════════════════════════════════════════╗', 'color:#EC4899;font-weight:bold');
    console.log('%c║   ALUMNI JOURNEY - Tracer Study          ║', 'color:#EC4899;font-weight:bold;font-size:14px');
    console.log('%c╚══════════════════════════════════════════╝', 'color:#EC4899;font-weight:bold');
    console.log('%c🎓 ' + document.querySelectorAll('.al-card').length + ' alumni in our network (OPTIMIZED)', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>