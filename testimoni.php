<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Testimoni Mahasiswa & Alumni';
$pageDesc = 'Baca pengalaman dan ulasan dari mahasiswa serta alumni Program Studi Informatika.';

$filterAngkatan = isset($_GET['angkatan']) ? clean($_GET['angkatan']) : '';

try {
    $db = getDB();
    $where = "WHERE status = 'approved'";
    $params = [];
    if ($filterAngkatan) {
        $where .= " AND tahun_masuk LIKE ?";
        $params[] = "%$filterAngkatan%";
    }
    
    // QUERY ASLI DIPERTAHANKAN
    $sql = "SELECT * FROM testimoni $where ORDER BY urutan ASC, created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $testimoniList = $stmt->fetchAll();
    
    $angkatans = $db->query("SELECT DISTINCT tahun_masuk FROM testimoni WHERE status='approved' AND tahun_masuk IS NOT NULL AND tahun_masuk != '' ORDER BY tahun_masuk DESC")->fetchAll(PDO::FETCH_COLUMN);
    
    // Tambahan statistik untuk hero
    $totalTestimoni = count($testimoniList);
    $avgRating = 4.8; // Default rating (bisa dikonfigurasi via settings jika perlu)
} catch (Throwable $e) {
    $testimoniList = [];
    $angkatans = [];
    $totalTestimoni = 0;
    $avgRating = 0;
}

// Ambil 1 testimoni featured (urutan paling kecil)
$featuredTestimoni = !empty($testimoniList) ? $testimoniList[0] : null;
$regularTestimoni = !empty($testimoniList) ? array_slice($testimoniList, 1) : [];
?>

<!-- ===== HERO SECTION WITH WAVEFORM CANVAS ===== -->
<section class="vc-hero">
    <canvas id="vcWaveformCanvas" class="vc-waveform-canvas"></canvas>
    <div class="vc-hero-bg">
        <div class="vc-orb vc-orb-1"></div>
        <div class="vc-orb vc-orb-2"></div>
        <div class="vc-sound-waves"></div>
    </div>
    
    <!-- Floating Sound Icons -->
    <div class="vc-floating-sounds">
        <span class="vc-sound-icon" style="--delay: 0s; --x: 8%; --y: 25%;">🎙️</span>
        <span class="vc-sound-icon" style="--delay: 1.5s; --x: 85%; --y: 20%;">🎧</span>
        <span class="vc-sound-icon" style="--delay: 3s; --x: 75%; --y: 75%;">🔊</span>
        <span class="vc-sound-icon" style="--delay: 0.8s; --x: 12%; --y: 70%;">💬</span>
        <span class="vc-sound-icon" style="--delay: 2.2s; --x: 50%; --y: 85%;">🎤</span>
        <span class="vc-sound-icon" style="--delay: 4s; --x: 92%; --y: 50%;">📢</span>
    </div>
    
    <div class="container vc-hero-content">
        <span class="vc-hero-badge fade-up">
            <span class="vc-recording-dot"></span>
            <i class="fas fa-microphone"></i> LIVE VOICES
        </span>
        
        <h1 class="vc-hero-title fade-up">
            <span class="vc-title-line">Suara</span>
            <span class="vc-title-line vc-glitch" data-text="Mereka">Mereka</span>
        </h1>
        
        <p class="vc-hero-subtitle fade-up">
            <span id="vcTypingText"></span><span class="vc-cursor">|</span>
        </p>
        
        <!-- Voice Stats -->
        <div class="vc-voice-stats fade-up">
            <div class="vc-voice-stat">
                <div class="vc-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="vc-stat-info">
                    <span class="vc-stat-num" data-count="<?= $totalTestimoni ?>">0</span>
                    <span class="vc-stat-label">Voices Recorded</span>
                </div>
            </div>
            <div class="vc-voice-stat">
                <div class="vc-stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="vc-stat-info">
                    <span class="vc-stat-num"><?= number_format($avgRating, 1) ?></span>
                    <span class="vc-stat-label">Avg. Rating</span>
                </div>
            </div>
            <div class="vc-voice-stat">
                <div class="vc-stat-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="vc-stat-info">
                    <span class="vc-stat-num"><?= count($angkatans) ?>+</span>
                    <span class="vc-stat-label">Angkatan</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="vc-scroll-indicator">
        <span>Listen to Stories</span>
        <div class="vc-wave-indicator">
            <span></span><span></span><span></span><span></span><span></span>
        </div>
    </div>
</section>

<!-- ===== READING PROGRESS ===== -->
<div class="vc-reading-progress">
    <div class="vc-progress-fill" id="vcProgressFill"></div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<section class="vc-section">
    <div class="container">
        
        <!-- Filter Bar (Voice Console Style) -->
        <div class="vc-filter-console fade-up">
            <div class="vc-console-header">
                <div class="vc-console-dots">
                    <span class="dot red"></span>
                    <span class="dot yellow"></span>
                    <span class="dot green"></span>
                </div>
                <i class="fas fa-wave-square"></i>
                <span>Voice_Filter.exe</span>
                <span class="vc-console-status">● LIVE</span>
            </div>
            <div class="vc-console-body">
                <div class="vc-filter-label">
                    <i class="fas fa-filter"></i> Filter by Angkatan:
                </div>
                <div class="vc-filter-tags" id="vcFilterTags">
                    <button class="vc-filter-btn active" data-filter="all">
                        <i class="fas fa-infinity"></i> Semua
                    </button>
                    <?php foreach ($angkatans as $a): ?>
                        <button class="vc-filter-btn" data-filter="<?= htmlspecialchars(preg_replace('/[^0-9]/', '', $a)) ?>">
                            <i class="fas fa-calendar"></i> <?= htmlspecialchars($a) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Featured Spotlight -->
        <?php if ($featuredTestimoni): 
            $ftAvatar = (strpos($featuredTestimoni['avatar'], 'http') === 0) ? $featuredTestimoni['avatar'] : (defined('SITE_URL') ? SITE_URL : '') . '/uploads/' . $featuredTestimoni['avatar'];
            if (!empty($featuredTestimoni['avatar']) && strpos($featuredTestimoni['avatar'], 'http') !== 0 && !file_exists(__DIR__ . '/uploads/' . $featuredTestimoni['avatar'])) {
                $ftAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($featuredTestimoni['nama']) . '&background=10B981&color=fff&size=256&font-size=0.4';
            } elseif (empty($featuredTestimoni['avatar']) || $featuredTestimoni['avatar'] === 'default.png') {
                $ftAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($featuredTestimoni['nama']) . '&background=10B981&color=fff&size=256&font-size=0.4';
            }
            $ftTahun = preg_replace('/[^0-9]/', '', $featuredTestimoni['tahun_masuk']);
        ?>
        <div class="vc-spotlight fade-up" data-angkatan="<?= htmlspecialchars($ftTahun) ?>">
            <div class="vc-spotlight-badge">
                <i class="fas fa-crown"></i> FEATURED VOICE
            </div>
            <div class="vc-spotlight-content">
                <div class="vc-spotlight-avatar">
                    <img src="<?= htmlspecialchars($ftAvatar) ?>" alt="<?= clean($featuredTestimoni['nama']) ?>">
                    <div class="vc-avatar-ring"></div>
                    <div class="vc-sound-waves-small">
                        <span></span><span></span><span></span><span></span><span></span>
                    </div>
                </div>
                <div class="vc-spotlight-text">
                    <i class="fas fa-quote-left vc-spotlight-quote-icon"></i>
                    <p class="vc-spotlight-quote">"<?= clean($featuredTestimoni['quote']) ?>"</p>
                    <div class="vc-spotlight-author">
                        <h3><?= clean($featuredTestimoni['nama']) ?></h3>
                        <span><i class="fas fa-graduation-cap"></i> <?= clean($featuredTestimoni['tahun_masuk']) ?></span>
                    </div>
                </div>
            </div>
            <button class="vc-spotlight-copy" onclick="vcCopyQuote(this, '<?= addslashes(clean($featuredTestimoni['quote'])) ?>')">
                <i class="fas fa-copy"></i> Copy Quote
            </button>
        </div>
        <?php endif; ?>

        <!-- Testimoni Grid -->
        <?php if (!empty($regularTestimoni)): ?>
            <div class="vc-grid" id="vcGrid">
                <?php foreach ($regularTestimoni as $index => $t): 
                    $avatar = (strpos($t['avatar'], 'http') === 0) ? $t['avatar'] : (defined('SITE_URL') ? SITE_URL : '') . '/uploads/' . $t['avatar'];
                    if (!empty($t['avatar']) && strpos($t['avatar'], 'http') !== 0 && !file_exists(__DIR__ . '/uploads/' . $t['avatar'])) {
                        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($t['nama']) . '&background=10B981&color=fff&size=128&font-size=0.4';
                    } elseif (empty($t['avatar']) || $t['avatar'] === 'default.png') {
                        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($t['nama']) . '&background=10B981&color=fff&size=128&font-size=0.4';
                    }
                    
                    $tahun = preg_replace('/[^0-9]/', '', $t['tahun_masuk']);
                ?>
                <div class="vc-card fade-up" data-angkatan="<?= htmlspecialchars($tahun) ?>" style="animation-delay: <?= $index * 0.05 ?>s">
                    <!-- Sound Wave Bars (Unique to this page) -->
                    <div class="vc-sound-wave-bars">
                        <?php for ($i = 0; $i < 20; $i++): ?>
                            <span style="--bar-delay: <?= $i * 0.05 ?>s; --bar-height: <?= rand(20, 80) ?>%"></span>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="vc-card-inner">
                        <div class="vc-quote-mark"><i class="fas fa-quote-left"></i></div>
                        <p class="vc-quote-text">"<?= clean($t['quote']) ?>"</p>
                        
                        <div class="vc-author">
                            <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= clean($t['nama']) ?>" loading="lazy">
                            <div class="vc-author-info">
                                <h4><?= clean($t['nama']) ?></h4>
                                <span><i class="fas fa-graduation-cap"></i> <?= clean($t['tahun_masuk']) ?></span>
                            </div>
                        </div>
                        
                        <button class="vc-copy-btn" onclick="vcCopyQuote(this, '<?= addslashes(clean($t['quote'])) ?>')" title="Copy Quote">
                            <i class="fas fa-copy"></i>
                        </button>
                        
                        <!-- Play button visual (decorative) -->
                        <div class="vc-play-btn">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (empty($featuredTestimoni)): ?>
            <div class="vc-empty-state fade-up">
                <div class="vc-empty-icon">
                    <i class="fas fa-microphone-slash"></i>
                    <div class="vc-empty-pulse"></div>
                </div>
                <h3>No Voices Recorded Yet</h3>
                <p>Belum ada testimoni yang disetujui<?= $filterAngkatan ? ' untuk angkatan ini' : '' ?>.</p>
                <?php if ($filterAngkatan): ?>
                <button class="vc-reset-btn" onclick="vcResetFilter()">
                    <i class="fas fa-redo"></i> Lihat Semua Testimoni
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- CTA Section (Voice Recording Theme) -->
        <div class="vc-cta fade-up">
            <div class="vc-cta-bg">
                <div class="vc-cta-wave"></div>
                <div class="vc-cta-wave"></div>
                <div class="vc-cta-wave"></div>
            </div>
            <div class="vc-cta-content">
                <div class="vc-cta-mic">
                    <i class="fas fa-microphone"></i>
                    <span class="vc-mic-pulse"></span>
                </div>
                <h3>Rekam Cerita Anda</h3>
                <p>Suarakan pengalaman Anda sebagai mahasiswa atau alumni Informatika Pro. Voice Anda akan menginspirasi generasi berikutnya!</p>
                <a href="<?= (defined('SITE_URL') ? SITE_URL : '') ?>/#contact" class="vc-cta-btn">
                    <i class="fas fa-paper-plane"></i> Kirim Testimoni
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Floating Action Button -->
<a href="<?= (defined('SITE_URL') ? SITE_URL : '') ?>/#contact" class="vc-fab" title="Kirim Testimoni">
    <i class="fas fa-microphone"></i>
    <span class="vc-fab-pulse"></span>
</a>

<!-- Toast -->
<div id="vcToast" class="vc-toast">
    <i class="fas fa-check-circle"></i>
    <span id="vcToastMsg">Berhasil!</span>
</div>

<!-- ============================================================
     SPECTACULAR STYLES (prefix: vc- = Voice)
============================================================ -->
<style>
:root {
    --vc-primary: #10B981;
    --vc-primary-dark: #047857;
    --vc-primary-light: #D1FAE5;
    --vc-accent: #F59E0B;
    --vc-dark: #0F172A;
    --vc-gray-50: #F8FAFC;
    --vc-gray-100: #F1F5F9;
    --vc-gray-200: #E2E8F0;
    --vc-gray-600: #475569;
    --vc-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --vc-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --vc-shadow-glow: 0 0 30px rgba(16,185,129,0.3);
    --vc-font-mono: 'JetBrains Mono', 'Courier New', monospace;
}

body.gl-dark {
    --vc-gray-50: #0B1120;
    --vc-gray-100: rgba(255,255,255,.08);
    --vc-gray-200: rgba(255,255,255,.1);
    --vc-gray-600: #94A3B8;
}

/* ===== GLOBAL ===== */
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--vc-gray-50); }
::-webkit-scrollbar-thumb { background: linear-gradient(180deg, var(--vc-primary), var(--vc-primary-dark)); border-radius: 5px; }

/* ===== READING PROGRESS ===== */
.vc-reading-progress {
    position: fixed; top: 0; left: 0; right: 0; height: 3px;
    background: rgba(16,185,129,0.1); z-index: 9998;
}
.vc-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--vc-primary), var(--vc-accent));
    box-shadow: 0 0 10px rgba(16,185,129,0.6);
    transition: width 0.1s ease-out;
}

/* ===== HERO WITH WAVEFORM ===== */
.vc-hero {
    background: linear-gradient(135deg, #0F172A 0%, #047857 100%);
    padding: 160px 20px 120px; position: relative; overflow: hidden;
    min-height: 90vh; display: flex; align-items: center; justify-content: center;
}
.vc-waveform-canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    z-index: 1; opacity: 0.7;
}
.vc-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }
.vc-orb {
    position: absolute; border-radius: 50%; filter: blur(80px);
    opacity: 0.4; animation: vcFloat 15s infinite ease-in-out;
}
.vc-orb-1 { width: 500px; height: 500px; background: var(--vc-primary); top: -150px; right: -150px; }
.vc-orb-2 { width: 350px; height: 350px; background: var(--vc-accent); bottom: -100px; left: -100px; animation-delay: -5s; }
@keyframes vcFloat { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(30px, -30px); } }

.vc-sound-waves {
    position: absolute; inset: 0;
    background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 40px,
        rgba(52, 211, 153, 0.03) 40px,
        rgba(52, 211, 153, 0.03) 41px
    );
}

/* Floating Sound Icons */
.vc-floating-sounds {
    position: absolute; inset: 0; z-index: 3; pointer-events: none;
}
.vc-sound-icon {
    position: absolute; font-size: 2rem;
    animation: vcFloatIcon 8s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
    opacity: 0.3; filter: grayscale(0.3);
}
@keyframes vcFloatIcon {
    0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.3; }
    50% { transform: translateY(-30px) rotate(15deg); opacity: 0.7; }
}

.vc-hero-content {
    position: relative; z-index: 4; text-align: center;
    color: #fff; max-width: 900px; margin: 0 auto;
}

.vc-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 10px 22px; background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.2); border-radius: 50px;
    font-size: .9rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 600; letter-spacing: 1px;
}
.vc-recording-dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: #EF4444; animation: vcRecordPulse 1.5s infinite;
}
@keyframes vcRecordPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.3); }
}

.vc-hero-title {
    font-size: clamp(3rem, 10vw, 7rem); font-weight: 900;
    margin-bottom: 20px; letter-spacing: -0.03em; line-height: 0.9;
}
.vc-title-line { display: block; }
.vc-glitch {
    position: relative; display: inline-block;
    background: linear-gradient(135deg, #34D399 0%, #F59E0B 50%, #06B6D4 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 200%;
    animation: vcGradientShift 5s ease infinite;
}
.vc-glitch::before, .vc-glitch::after {
    content: attr(data-text); position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    background: inherit; -webkit-background-clip: text;
    -webkit-text-fill-color: transparent; background-clip: text;
}
.vc-glitch::before {
    animation: vcGlitch1 3s infinite;
    clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%);
    transform: translate(-2px, 2px); opacity: 0.8;
}
.vc-glitch::after {
    animation: vcGlitch2 2.5s infinite;
    clip-path: polygon(0 60%, 100% 60%, 100% 100%, 0 100%);
    transform: translate(2px, -2px); opacity: 0.8;
}
@keyframes vcGradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
@keyframes vcGlitch1 {
    0%, 90%, 100% { transform: translate(0, 0); }
    92% { transform: translate(-3px, 1px); }
    94% { transform: translate(2px, -2px); }
    96% { transform: translate(-1px, 2px); }
}
@keyframes vcGlitch2 {
    0%, 90%, 100% { transform: translate(0, 0); }
    91% { transform: translate(2px, 1px); }
    93% { transform: translate(-2px, -1px); }
    95% { transform: translate(1px, 2px); }
}

.vc-hero-subtitle {
    font-size: 1.2rem; opacity: .9; max-width: 650px;
    margin: 0 auto 40px; line-height: 1.7;
    display: inline-block; min-height: 1.7em;
}
.vc-cursor { animation: vcBlink 1s infinite; color: var(--vc-primary); font-weight: 300; }
@keyframes vcBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

/* Voice Stats */
.vc-voice-stats {
    display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;
    margin-top: 40px;
}
.vc-voice-stat {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 28px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-radius: 16px;
    backdrop-filter: blur(12px); transition: all 0.3s;
}
.vc-voice-stat:hover {
    background: rgba(255,255,255,.15); transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.2);
}
.vc-stat-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: linear-gradient(135deg, var(--vc-primary), var(--vc-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
}
.vc-stat-num {
    display: block; font-size: 1.8rem; font-weight: 800;
    color: #fff; line-height: 1; font-family: 'Space Grotesk', sans-serif;
}
.vc-stat-label {
    font-size: 0.75rem; color: rgba(255,255,255,.7);
    text-transform: uppercase; letter-spacing: 0.05em;
    margin-top: 4px; display: block;
}

/* Scroll Indicator */
.vc-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%;
    transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 12px;
    color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.vc-wave-indicator {
    display: flex; gap: 3px; align-items: center; height: 30px;
}
.vc-wave-indicator span {
    display: block; width: 3px; background: var(--vc-primary);
    border-radius: 2px; animation: vcWaveBar 1s infinite ease-in-out;
}
.vc-wave-indicator span:nth-child(1) { height: 30%; animation-delay: 0s; }
.vc-wave-indicator span:nth-child(2) { height: 60%; animation-delay: 0.1s; }
.vc-wave-indicator span:nth-child(3) { height: 100%; animation-delay: 0.2s; }
.vc-wave-indicator span:nth-child(4) { height: 60%; animation-delay: 0.3s; }
.vc-wave-indicator span:nth-child(5) { height: 30%; animation-delay: 0.4s; }
@keyframes vcWaveBar {
    0%, 100% { transform: scaleY(0.5); }
    50% { transform: scaleY(1); }
}

/* ===== MAIN SECTION ===== */
.vc-section { padding: 80px 20px 120px; background: var(--vc-gray-50); position: relative; }

/* ===== FILTER CONSOLE ===== */
.vc-filter-console {
    background: #fff; border: 1px solid var(--vc-gray-200);
    border-radius: 20px; box-shadow: var(--vc-shadow-md);
    max-width: 1000px; margin: 0 auto 60px; overflow: hidden;
}
body.gl-dark .vc-filter-console { background: #1E293B; border-color: rgba(255,255,255,.08); }

.vc-console-header {
    background: var(--vc-dark); padding: 14px 20px;
    display: flex; align-items: center; gap: 12px;
    color: #94A3B8; font-family: var(--vc-font-mono);
    font-size: 0.85rem;
}
.vc-console-dots { display: flex; gap: 6px; }
.vc-console-dots .dot { width: 12px; height: 12px; border-radius: 50%; }
.vc-console-dots .red { background: #FF5F56; }
.vc-console-dots .yellow { background: #FFBD2E; }
.vc-console-dots .green { background: #27C93F; }
.vc-console-header i { color: var(--vc-primary); }
.vc-console-header span { color: #E2E8F0; }
.vc-console-status {
    margin-left: auto; color: var(--vc-primary);
    font-size: 0.75rem; letter-spacing: 1px;
    animation: vcBlink 2s infinite;
}

.vc-console-body { padding: 24px; }
.vc-filter-label {
    font-size: 0.85rem; font-weight: 700; color: var(--vc-gray-600);
    display: flex; align-items: center; gap: 8px; margin-bottom: 12px;
    text-transform: uppercase; letter-spacing: 0.5px;
}
body.gl-dark .vc-filter-label { color: #94A3B8; }
.vc-filter-label i { color: var(--vc-primary); }

.vc-filter-tags { display: flex; gap: 8px; flex-wrap: wrap; }
.vc-filter-btn {
    padding: 10px 18px; border-radius: 50px;
    background: var(--vc-gray-100); border: 1.5px solid transparent;
    color: var(--vc-gray-600); cursor: pointer; font-weight: 600;
    font-size: 0.85rem; transition: all 0.3s; font-family: inherit;
    display: inline-flex; align-items: center; gap: 6px;
}
body.gl-dark .vc-filter-btn { background: rgba(255,255,255,.05); color: #CBD5E1; }
.vc-filter-btn i { font-size: 0.75rem; }
.vc-filter-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.vc-filter-btn.active {
    background: var(--vc-primary); color: #fff;
    border-color: var(--vc-primary);
    box-shadow: 0 4px 12px rgba(16,185,129,.4);
}

/* ===== FEATURED SPOTLIGHT ===== */
.vc-spotlight {
    background: linear-gradient(135deg, rgba(16,185,129,0.05), rgba(245,158,11,0.05));
    border: 2px solid rgba(16,185,129,0.2); border-radius: 28px;
    padding: 40px; margin-bottom: 60px; position: relative;
    overflow: hidden; transition: all 0.3s;
}
body.gl-dark .vc-spotlight { background: linear-gradient(135deg, rgba(16,185,129,0.05), rgba(245,158,11,0.05)); }
.vc-spotlight:hover { transform: translateY(-4px); box-shadow: var(--vc-shadow-glow); }
.vc-spotlight.hidden { display: none !important; }

.vc-spotlight-badge {
    position: absolute; top: 20px; right: 20px;
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; background: linear-gradient(135deg, #F59E0B, #D97706);
    color: #fff; border-radius: 20px; font-size: 0.72rem;
    font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
    box-shadow: 0 4px 12px rgba(245,158,11,0.4);
}

.vc-spotlight-content {
    display: grid; grid-template-columns: auto 1fr; gap: 32px;
    align-items: center;
}
.vc-spotlight-avatar { position: relative; width: 140px; height: 140px; flex-shrink: 0; }
.vc-spotlight-avatar img {
    width: 100%; height: 100%; border-radius: 50%;
    object-fit: cover; border: 4px solid var(--vc-primary);
}
.vc-avatar-ring {
    position: absolute; inset: -8px; border-radius: 50%;
    border: 2px dashed var(--vc-primary); opacity: 0.4;
    animation: vcSpin 20s linear infinite;
}
@keyframes vcSpin { to { transform: rotate(360deg); } }

.vc-sound-waves-small {
    position: absolute; bottom: -20px; left: 50%;
    transform: translateX(-50%); display: flex; gap: 3px;
    align-items: flex-end; height: 24px;
}
.vc-sound-waves-small span {
    display: block; width: 3px; background: var(--vc-primary);
    border-radius: 2px; animation: vcSmallWave 1s infinite ease-in-out;
}
.vc-sound-waves-small span:nth-child(1) { height: 40%; animation-delay: 0s; }
.vc-sound-waves-small span:nth-child(2) { height: 70%; animation-delay: 0.15s; }
.vc-sound-waves-small span:nth-child(3) { height: 100%; animation-delay: 0.3s; }
.vc-sound-waves-small span:nth-child(4) { height: 70%; animation-delay: 0.45s; }
.vc-sound-waves-small span:nth-child(5) { height: 40%; animation-delay: 0.6s; }
@keyframes vcSmallWave {
    0%, 100% { transform: scaleY(0.4); }
    50% { transform: scaleY(1); }
}

.vc-spotlight-text { position: relative; }
.vc-spotlight-quote-icon {
    font-size: 2.5rem; color: var(--vc-primary); opacity: 0.3;
    margin-bottom: 16px; display: block;
}
.vc-spotlight-quote {
    font-size: 1.25rem; line-height: 1.7; font-style: italic;
    color: var(--vc-dark); margin: 0 0 20px 0; font-weight: 500;
}
body.gl-dark .vc-spotlight-quote { color: #F1F5F9; }
.vc-spotlight-author h3 { font-size: 1.2rem; margin: 0 0 4px 0; color: var(--vc-dark); font-weight: 800; }
body.gl-dark .vc-spotlight-author h3 { color: #F1F5F9; }
.vc-spotlight-author span {
    font-size: 0.9rem; color: var(--vc-primary);
    display: flex; align-items: center; gap: 6px; font-weight: 600;
}

.vc-spotlight-copy {
    position: absolute; bottom: 24px; right: 24px;
    padding: 10px 20px; background: #fff;
    border: 1px solid var(--vc-gray-200); border-radius: 50px;
    color: var(--vc-gray-600); cursor: pointer; font-weight: 600;
    font-size: 0.85rem; transition: all 0.3s; font-family: inherit;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: var(--vc-shadow-sm);
}
body.gl-dark .vc-spotlight-copy { background: #0F172A; border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.vc-spotlight-copy:hover {
    background: var(--vc-primary); color: #fff;
    border-color: var(--vc-primary); transform: translateY(-2px);
}

/* ===== TESTIMONI GRID ===== */
.vc-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px; margin-bottom: 60px;
}

.vc-card {
    background: #fff; border-radius: 24px; overflow: hidden;
    border: 1px solid var(--vc-gray-100); box-shadow: var(--vc-shadow-sm);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative; opacity: 0;
    animation: vcSlideUp 0.6s ease forwards;
}
body.gl-dark .vc-card { background: #1E293B; border-color: rgba(255,255,255,.08); }
@keyframes vcSlideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Sound Wave Bars - UNIQUE FEATURE */
.vc-sound-wave-bars {
    position: absolute; top: 0; left: 0; right: 0; height: 50px;
    display: flex; align-items: flex-end; justify-content: space-around;
    padding: 0 16px; gap: 2px; overflow: hidden;
    background: linear-gradient(180deg, rgba(16,185,129,0.05), transparent);
    pointer-events: none;
}
.vc-sound-wave-bars span {
    flex: 1; background: linear-gradient(180deg, var(--vc-primary), var(--vc-primary-dark));
    border-radius: 2px 2px 0 0; height: var(--bar-height);
    opacity: 0.4; animation: vcBarAnimation 1.5s infinite ease-in-out;
    animation-delay: var(--bar-delay);
    transform-origin: bottom;
}
@keyframes vcBarAnimation {
    0%, 100% { transform: scaleY(0.3); }
    50% { transform: scaleY(1); }
}

.vc-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: var(--vc-shadow-md), var(--vc-shadow-glow);
    border-color: var(--vc-primary);
}
.vc-card:hover .vc-sound-wave-bars span { opacity: 0.8; }

.vc-card.hidden { display: none !important; }

.vc-card-inner {
    padding: 60px 28px 28px; position: relative;
    display: flex; flex-direction: column; min-height: 300px;
}

.vc-quote-mark {
    position: absolute; top: 60px; right: 20px;
    font-size: 2.5rem; color: var(--vc-primary-light);
    opacity: 0.5; line-height: 1; transition: all 0.3s;
}
.vc-card:hover .vc-quote-mark { opacity: 1; transform: scale(1.2) rotate(-10deg); }
body.gl-dark .vc-quote-mark { color: rgba(16,185,129,.2); }

.vc-quote-text {
    font-size: 1rem; line-height: 1.7; color: var(--vc-gray-600);
    margin-bottom: 24px; font-style: italic; flex: 1;
    display: -webkit-box; -webkit-line-clamp: 6;
    -webkit-box-orient: vertical; overflow: hidden;
}
body.gl-dark .vc-quote-text { color: #CBD5E1; }

.vc-author {
    display: flex; align-items: center; gap: 14px;
    padding-top: 20px; border-top: 1px dashed var(--vc-gray-200);
}
body.gl-dark .vc-author { border-color: rgba(255,255,255,.1); }
.vc-author img {
    width: 50px; height: 50px; border-radius: 50%;
    object-fit: cover; border: 3px solid var(--vc-primary-light);
    flex-shrink: 0; transition: transform 0.3s;
}
.vc-card:hover .vc-author img { transform: scale(1.1) rotate(5deg); }

.vc-author-info h4 { font-size: 1rem; margin: 0 0 2px 0; color: var(--vc-dark); font-weight: 700; }
body.gl-dark .vc-author-info h4 { color: #F1F5F9; }
.vc-author-info span {
    font-size: 0.82rem; color: var(--vc-primary);
    display: flex; align-items: center; gap: 6px; font-weight: 600;
}

.vc-copy-btn {
    position: absolute; bottom: 24px; right: 24px;
    width: 40px; height: 40px; border-radius: 50%;
    background: var(--vc-gray-100); border: none;
    color: var(--vc-gray-600); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    opacity: 0; transform: translateY(10px);
    transition: all 0.3s; font-size: 0.9rem;
}
body.gl-dark .vc-copy-btn { background: rgba(255,255,255,.08); color: #CBD5E1; }
.vc-card:hover .vc-copy-btn { opacity: 1; transform: translateY(0); }
.vc-copy-btn:hover { background: var(--vc-primary); color: #fff; }

.vc-play-btn {
    position: absolute; top: 70px; left: 24px;
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, var(--vc-primary), var(--vc-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1rem; box-shadow: 0 4px 12px rgba(16,185,129,0.3);
    transition: all 0.3s; cursor: pointer;
    animation: vcPlayPulse 2s infinite;
}
@keyframes vcPlayPulse {
    0%, 100% { box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
    50% { box-shadow: 0 4px 24px rgba(16,185,129,0.6); }
}
.vc-play-btn:hover { transform: scale(1.1); }

/* ===== EMPTY STATE ===== */
.vc-empty-state {
    text-align: center; padding: 80px 20px; background: #fff;
    border-radius: 24px; border: 2px dashed var(--vc-gray-200);
    max-width: 600px; margin: 0 auto 60px;
}
body.gl-dark .vc-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }
.vc-empty-icon {
    position: relative; width: 100px; height: 100px; margin: 0 auto 24px;
    display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: var(--vc-gray-600);
}
.vc-empty-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--vc-primary); animation: vcEmptyPulse 2s infinite;
}
@keyframes vcEmptyPulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }
.vc-empty-state h3 { font-size: 1.4rem; margin: 0 0 8px 0; color: var(--vc-dark); font-weight: 800; }
body.gl-dark .vc-empty-state h3 { color: #F1F5F9; }
.vc-empty-state p { color: var(--vc-gray-600); margin-bottom: 24px; }
body.gl-dark .vc-empty-state p { color: #94A3B8; }
.vc-reset-btn {
    padding: 12px 24px; background: var(--vc-primary); color: #fff;
    border: none; border-radius: 10px; font-weight: 600;
    cursor: pointer; transition: all 0.3s; font-family: inherit;
    display: inline-flex; align-items: center; gap: 8px;
}
.vc-reset-btn:hover { background: var(--vc-primary-dark); transform: translateY(-2px); }

/* ===== CTA ===== */
.vc-cta {
    background: linear-gradient(135deg, var(--vc-dark), var(--vc-primary-dark));
    border-radius: 32px; padding: 80px 40px; text-align: center;
    color: #fff; position: relative; overflow: hidden;
}
.vc-cta-bg { position: absolute; inset: 0; pointer-events: none; }
.vc-cta-wave {
    position: absolute; bottom: 0; left: 0; right: 0;
    height: 100px; background: linear-gradient(0deg, rgba(16,185,129,0.1), transparent);
    animation: vcWaveMove 4s infinite ease-in-out;
}
.vc-cta-wave:nth-child(2) { animation-delay: 1s; opacity: 0.5; }
.vc-cta-wave:nth-child(3) { animation-delay: 2s; opacity: 0.3; }
@keyframes vcWaveMove {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

.vc-cta-content { position: relative; z-index: 1; }
.vc-cta-mic {
    width: 90px; height: 90px; margin: 0 auto 24px;
    background: linear-gradient(135deg, var(--vc-primary), var(--vc-primary-dark));
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 2.5rem; position: relative;
    box-shadow: 0 8px 24px rgba(16,185,129,0.4);
}
.vc-mic-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--vc-primary); animation: vcMicPulse 2s infinite;
}
@keyframes vcMicPulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

.vc-cta h3 { font-size: 2.2rem; margin-bottom: 12px; font-weight: 800; }
.vc-cta p {
    font-size: 1.05rem; opacity: 0.9; max-width: 600px;
    margin: 0 auto 32px; line-height: 1.7;
}
.vc-cta-btn {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 14px 36px; background: #fff; color: var(--vc-primary-dark);
    border-radius: 50px; font-weight: 700; text-decoration: none;
    transition: all 0.3s; font-size: 1rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
}
.vc-cta-btn:hover { transform: translateY(-3px) scale(1.05); box-shadow: 0 12px 32px rgba(0,0,0,0.3); }

/* ===== FLOATING ACTION BUTTON ===== */
.vc-fab {
    position: fixed; bottom: 100px; right: 30px; z-index: 997;
    width: 60px; height: 60px; border-radius: 50%;
    background: linear-gradient(135deg, var(--vc-primary), var(--vc-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; text-decoration: none;
    box-shadow: 0 8px 24px rgba(16,185,129,0.4);
    transition: all 0.3s;
}
.vc-fab:hover { transform: scale(1.1); box-shadow: 0 12px 32px rgba(16,185,129,0.6); }
.vc-fab-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--vc-primary); animation: vcFabPulse 2s infinite;
}
@keyframes vcFabPulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

/* ===== TOAST ===== */
.vc-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--vc-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    display: flex; align-items: center; gap: 10px;
    font-size: 0.9rem; opacity: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10000; font-weight: 500;
}
body.gl-dark .vc-toast { background: #fff; color: var(--vc-dark); }
.vc-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.vc-toast i { color: var(--vc-primary); font-size: 1.2rem; }

/* ===== ANIMATIONS ===== */
.fade-up { opacity: 0; transform: translateY(30px); transition: opacity 0.8s ease, transform 0.8s ease; }
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .vc-hero { padding: 120px 20px 80px; min-height: 80vh; }
    .vc-hero-title { font-size: 3.5rem; }
    .vc-voice-stats { flex-direction: column; align-items: center; }
    .vc-voice-stat { width: 100%; max-width: 300px; }
    .vc-grid { grid-template-columns: 1fr; }
    .vc-spotlight-content { grid-template-columns: 1fr; text-align: center; }
    .vc-spotlight-avatar { margin: 0 auto; width: 120px; height: 120px; }
    .vc-spotlight-copy { position: static; margin-top: 16px; }
    .vc-cta { padding: 60px 20px; }
    .vc-cta h3 { font-size: 1.6rem; }
    .vc-filter-tags { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 8px; }
    .vc-filter-btn { white-space: nowrap; flex-shrink: 0; }
    .vc-fab { width: 50px; height: 50px; bottom: 80px; right: 20px; font-size: 1.1rem; }
    .vc-floating-sounds { display: none; }
}
@media (max-width: 480px) {
    .vc-card-inner { padding: 50px 20px 20px; }
    .vc-quote-text { font-size: 0.95rem; }
}
</style>

<!-- ============================================================
     SPECTACULAR SCRIPTS (Null-Safe)
============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // 1. Waveform Canvas Animation (UNIQUE - Audio Visualizer)
    const canvas = document.getElementById('vcWaveformCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let width, height, time = 0;

        function resize() {
            width = canvas.width = canvas.parentElement.offsetWidth;
            height = canvas.height = canvas.parentElement.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        function drawWaveform() {
            ctx.clearRect(0, 0, width, height);
            
            // Multiple waves with different frequencies
            const waves = [
                { amplitude: 30, frequency: 0.01, speed: 0.02, color: 'rgba(52, 211, 153, 0.4)' },
                { amplitude: 20, frequency: 0.015, speed: 0.03, color: 'rgba(245, 158, 11, 0.3)' },
                { amplitude: 15, frequency: 0.02, speed: 0.015, color: 'rgba(6, 182, 212, 0.3)' }
            ];

            waves.forEach(wave => {
                ctx.beginPath();
                ctx.strokeStyle = wave.color;
                ctx.lineWidth = 2;
                
                for (let x = 0; x < width; x += 5) {
                    const y = height / 2 + 
                        Math.sin(x * wave.frequency + time * wave.speed) * wave.amplitude +
                        Math.sin(x * wave.frequency * 2 + time * wave.speed * 1.5) * (wave.amplitude / 2);
                    
                    if (x === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }
                ctx.stroke();
            });

            // Add vertical bars (equalizer effect)
            const barCount = 40;
            const barWidth = width / barCount;
            for (let i = 0; i < barCount; i++) {
                const barHeight = (Math.sin(time * 0.05 + i * 0.3) + 1) * 30 + 10;
                const x = i * barWidth;
                const y = height - barHeight;
                
                ctx.fillStyle = `rgba(52, 211, 153, ${0.1 + Math.random() * 0.1})`;
                ctx.fillRect(x, y, barWidth - 2, barHeight);
            }

            time++;
            requestAnimationFrame(drawWaveform);
        }
        drawWaveform();
    }

    // 2. Typing Effect
    const textElement = document.getElementById('vcTypingText');
    if (textElement) {
        const phrases = [
            "Dengarkan suara mereka yang telah merasakan kualitas pendidikan Informatika Pro.",
            "Jejak kesuksesan alumni kami di berbagai belahan dunia.",
            "Komunitas Informatika Pro yang solid dan saling mendukung.",
            "Setiap cerita adalah inspirasi bagi generasi berikutnya."
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

    // 3. Counter Animation
    const counters = document.querySelectorAll('.vc-stat-num[data-count]');
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

    // 4. Reading Progress Bar
    const progressFill = document.getElementById('vcProgressFill');
    if (progressFill) {
        window.addEventListener('scroll', () => {
            const scrolled = window.scrollY;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const percent = maxScroll > 0 ? (scrolled / maxScroll) * 100 : 0;
            progressFill.style.width = percent + '%';
        }, { passive: true });
    }

    // 5. Scroll Reveal Animation
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

    // 6. Instant Client-Side Filtering
    const filterBtns = document.querySelectorAll('.vc-filter-btn');
    const allCards = document.querySelectorAll('.vc-card, .vc-spotlight');

    filterBtns.forEach(btn => {
        if (!btn) return;
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const filterValue = this.getAttribute('data-filter');

            allCards.forEach(card => {
                if (!card) return;
                const cardAngkatan = card.getAttribute('data-angkatan') || '';
                
                if (filterValue === 'all' || cardAngkatan === filterValue) {
                    card.classList.remove('hidden');
                    card.style.display = '';
                } else {
                    card.classList.add('hidden');
                    card.style.display = 'none';
                }
            });
        });
    });

    // 7. Copy Quote Functionality
    window.vcCopyQuote = function(btn, text) {
        if (!btn || !text) return;
        navigator.clipboard.writeText('"' + text + '"').then(() => {
            const toast = document.getElementById('vcToast');
            const toastMsg = document.getElementById('vcToastMsg');
            if (toast && toastMsg) {
                toastMsg.textContent = 'Kutipan berhasil disalin!';
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 3000);
            }
            
            const originalIcon = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.style.background = '#10B981';
            btn.style.color = '#fff';
            btn.style.borderColor = '#10B981';
            
            setTimeout(() => {
                btn.innerHTML = originalIcon;
                btn.style.background = '';
                btn.style.color = '';
                btn.style.borderColor = '';
            }, 2000);
        }).catch(err => console.error('Copy failed', err));
    };

    // 8. Reset Filter Function
    window.vcResetFilter = function() {
        const allBtn = document.querySelector('.vc-filter-btn[data-filter="all"]');
        if (allBtn) allBtn.click();
    };

    // 9. Parallax Effect on Scroll
    const heroSection = document.querySelector('.vc-hero');
    if (heroSection && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const orbs = heroSection.querySelectorAll('.vc-orb');
            orbs.forEach((orb, index) => {
                const speed = (index + 1) * 0.3;
                orb.style.transform = `translateY(${scrolled * speed}px)`;
            });
        }, { passive: true });
    }

    // 10. Welcome Toast
    setTimeout(() => {
        const toast = document.getElementById('vcToast');
        const toastMsg = document.getElementById('vcToastMsg');
        if (toast && toastMsg) {
            toastMsg.textContent = '🎙️ Selamat datang di Voice of Informatika!';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3500);
        }
    }, 1500);

    // 11. Console Easter Egg
    console.log('%c╔════════════════════════════════════════╗', 'color:#10B981;font-weight:bold');
    console.log('%c║   VOICE OF INFORMATIKA - Testimoni     ║', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c╚════════════════════════════════════════╝', 'color:#10B981;font-weight:bold');
    console.log('%c🎙️ ' + document.querySelectorAll('.vc-card').length + ' voices recorded', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>