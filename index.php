<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

/* ==========================================================
   DATA DINAMIS DARI DATABASE (Ultimate Anti-Error)
========================================================= */
$stats = [
    'mahasiswa' => (int)getSetting('statistik_mahasiswa', 518),
    'publikasi' => (int)getSetting('statistik_publikasi', 158),
    'dosen' => 25,
    'akreditasi' => getSetting('akreditasi', 'Baik Sekali'),
    'alumni' => (int)getSetting('statistik_alumni', 1200),
    'mitra' => (int)getSetting('statistik_mitra', 45)
];

$pengumuman = [];
$testimonials = [];
$newsList = [];
$faqs = [];
$mitraList = [];
$konsentrasi = [];
$karirPaths = [];
$events = [];

try {
    $db = getDB();
    
    // 1. Cache stats dosen (TTL 1 jam = 3600 detik)
    $stats['dosen'] = cache('stats_dosen_aktif', 3600, function() use ($db) {
        $count = (int)$db->query("SELECT COUNT(*) FROM dosen WHERE status = 'aktif'")->fetchColumn();
        return $count > 0 ? $count : 25;
    });

    // 2. Cache pengumuman (TTL 10 menit = 600 detik)
    $pengumuman = cache('index_pengumuman', 600, function() use ($db) {
        return $db->query("
            SELECT judul, prioritas FROM pengumuman 
            WHERE status = 'aktif' AND (tanggal_berakhir IS NULL OR tanggal_berakhir >= CURDATE()) 
            ORDER BY FIELD(prioritas,'penting','tinggi','normal','rendah'), created_at DESC LIMIT 6
        ")->fetchAll();
    });

    // 3. Ini sudah otomatis di-cache oleh functions.php
    $testimonials = getTestimonials(6);
    $newsList = getHomeNews(6);

    // 4. Cache FAQ (TTL 1 jam)
    $faqs = cache('index_faqs', 3600, function() use ($db) {
        return $db->query("SELECT * FROM faq WHERE status='aktif' ORDER BY urutan ASC LIMIT 8")->fetchAll();
    });

    // 5. Cache Mitra (TTL 1 jam)
    $mitraList = cache('index_mitra', 3600, function() use ($db) {
        return $db->query("SELECT nama, jenis, logo FROM mitra WHERE status='aktif' ORDER BY urutan ASC LIMIT 12")->fetchAll();
    });

    // 6. Cache Konsentrasi (TTL 1 jam)
    $konsentrasi = cache('index_konsentrasi', 3600, function() use ($db) {
        return $db->query("SELECT * FROM konsentrasi WHERE status='aktif' ORDER BY urutan ASC")->fetchAll();
    });

    // 7. Cache Karir (TTL 1 jam)
    $karirPaths = cache('index_karir', 3600, function() use ($db) {
        return $db->query("SELECT jabatan, gaji_min, gaji_max, permintaan, ikon FROM karir_paths WHERE status='aktif' ORDER BY urutan ASC LIMIT 6")->fetchAll();
    });

    // 8. Cache Events (TTL 5 menit = 300 detik)
    $events = cache('index_events', 300, function() use ($db) {
        return $db->query("SELECT * FROM agenda WHERE status = 'akan_datang' AND tanggal_mulai >= CURDATE() ORDER BY tanggal_mulai ASC LIMIT 3")->fetchAll();
    });

} catch (Throwable $e) {
    // Fallback data tetap sama jika database error
    $testimonials = [
        ['nama' => 'Ivan Sholana', 'tahun_masuk' => 'Mahasiswa 2021', 'avatar' => 'https://i.pravatar.cc/150?img=12', 'quote' => 'Kurikulum berbasis proyek membuat saya siap kerja sejak hari pertama. Dosen sangat suportif!'],
        ['nama' => 'Dian Puspita', 'tahun_masuk' => 'Alumni 2021', 'avatar' => 'https://i.pravatar.cc/150?img=5', 'quote' => 'Fasilitas lab AI dan bimbingan riset adalah kunci saya diterima di perusahaan teknologi besar.'],
        ['nama' => 'Budi Santoso', 'tahun_masuk' => 'Alumni 2020', 'avatar' => 'https://i.pravatar.cc/150?img=7', 'quote' => 'Networking dengan industri yang dibangun kampus sangat membantu karir saya.'],
    ];
}

if (!function_exists('getBeritaImage')) {
    function getBeritaImage($gambar, $kategori = '') {
        if ($gambar && strpos($gambar, 'http') === 0) return $gambar;
        if ($gambar) {
            $candidates = [$gambar, 'uploads/' . $gambar, 'uploads/berita/' . $gambar];
            foreach ($candidates as $path) {
                if (file_exists(__DIR__ . '/' . $path)) return $path;
            }
        }
        $defaults = [
            'Workshop' => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=800&q=80',
            'Pengabdian' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800&q=80',
            'Proyek' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=800&q=80',
        ];
        return $defaults[$kategori] ?? 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&q=80';
    }
}

if (!function_exists('getReadingTime')) {
    function getReadingTime($text) {
        $words = str_word_count(strip_tags($text));
        $minutes = ceil($words / 200);
        return $minutes . ' menit baca';
    }
}

$techStack = [
    ['name' => 'Python', 'icon' => 'fa-python', 'color' => '#3776AB'],
    ['name' => 'JavaScript', 'icon' => 'fa-js', 'color' => '#F7DF1E'],
    ['name' => 'React', 'icon' => 'fa-react', 'color' => '#61DAFB'],
    ['name' => 'Node.js', 'icon' => 'fa-node-js', 'color' => '#339933'],
    ['name' => 'TensorFlow', 'icon' => 'fa-brain', 'color' => '#FF6F00'],
    ['name' => 'AWS', 'icon' => 'fa-aws', 'color' => '#FF9900'],
    ['name' => 'Docker', 'icon' => 'fa-docker', 'color' => '#2496ED'],
    ['name' => 'Kubernetes', 'icon' => 'fa-dharmachakra', 'color' => '#326CE5'],
    ['name' => 'Git', 'icon' => 'fa-git-alt', 'color' => '#F05032'],
    ['name' => 'Linux', 'icon' => 'fa-linux', 'color' => '#FCC624'],
    ['name' => 'MongoDB', 'icon' => 'fa-leaf', 'color' => '#47A248'],
    ['name' => 'PostgreSQL', 'icon' => 'fa-database', 'color' => '#4169E1'],
];
?>

<!-- ============================================================
   QUANTUM NEXUS - HOLOGRAPHIC HOMEPAGE
   Tema paling berbeda: Hologram + Quantum + Prismatic
============================================================ -->

<!-- 0. Holographic Scanline Overlay -->
<div class="qn-scanlines"></div>

<!-- 1. Custom Holographic Cursor -->
<div class="qn-cursor" id="qnCursor"></div>
<div class="qn-cursor-ring" id="qnCursorRing"></div>
<div class="qn-cursor-trail" id="qnCursorTrail"></div>

<!-- 2. Scroll Progress Bar (Holographic) -->
<div class="qn-scroll-progress">
    <div class="qn-scroll-fill" id="qnScrollFill"></div>
    <div class="qn-scroll-glow"></div>
</div>

<!-- 3. Skip Link -->
<a href="#main-content" class="qn-skip-link">Langsung ke konten utama</a>

<!-- 4. Back to Top (Holographic) -->
<button class="qn-back-top" id="qnBackTop" aria-label="Kembali ke atas">
    <svg class="qn-progress-ring" viewBox="0 0 36 36">
        <circle class="qn-ring-bg" cx="18" cy="18" r="15" fill="none" stroke-width="2"/>
        <circle class="qn-ring-fill" id="qnRingFill" cx="18" cy="18" r="15" fill="none" stroke-width="2"/>
    </svg>
    <i class="fas fa-arrow-up"></i>
    <span class="qn-btt-glow"></span>
</button>

<!-- 5. Cookie Consent (Holographic) -->
<div class="qn-cookie" id="qnCookie">
    <div class="qn-cookie-content">
        <div class="qn-cookie-icon">🍪</div>
        <div class="qn-cookie-text">
            <h4>Cookie Quantum Aktif</h4>
            <p>Kami menggunakan cookies untuk memberikan pengalaman holografik terbaik.</p>
        </div>
        <div class="qn-cookie-actions">
            <button class="qn-cookie-btn primary" id="qnCookieAccept">Aktifkan</button>
            <button class="qn-cookie-btn" id="qnCookieDecline">Tolak</button>
        </div>
    </div>
</div>

<!-- FLASH MESSAGE -->
<?php $flash = getFlash(); if ($flash): ?>
<div class="flash-toast flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" id="flashToast">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <span><?= clean($flash['message']) ?></span>
    <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
</div>
<script>setTimeout(() => { const t = document.getElementById('flashToast'); if (t) t.remove(); }, 6000);</script>
<?php endif; ?>

<!-- MAIN CONTENT START -->
<main id="main-content">

<!-- ===== HERO: QUANTUM TUNNEL ===== -->
<section class="qn-hero" id="home">
    <!-- Multi-layer Particle Systems -->
    <canvas id="qnQuantumCanvas" class="qn-quantum-canvas"></canvas>
    <canvas id="qnDataCanvas" class="qn-data-canvas"></canvas>
    
    <!-- Quantum Tunnel Depth Layers -->
    <div class="qn-tunnel">
        <div class="qn-tunnel-ring qn-ring-1"></div>
        <div class="qn-tunnel-ring qn-ring-2"></div>
        <div class="qn-tunnel-ring qn-ring-3"></div>
        <div class="qn-tunnel-ring qn-ring-4"></div>
        <div class="qn-tunnel-ring qn-ring-5"></div>
    </div>
    
    <!-- Holographic Grid -->
    <div class="qn-holo-grid"></div>
    
<!-- Data Streams (Optimized: 8 streams only) -->
<div class="qn-data-streams">
<?php for ($i = 0; $i < 8; $i++): ?>
<div class="qn-stream" style="--delay: <?= $i * 0.5 ?>s; --left: <?= rand(5, 95) ?>%; --duration: <?= rand(10, 25) ?>s;"><?= str_repeat('01', 15) ?></div>
<?php endfor; ?>
</div>
    
    <!-- Floating Holographic Elements -->
    <div class="qn-floating-holograms">
        <div class="qn-holo-shape qn-shape-1">
            <svg viewBox="0 0 100 100"><polygon points="50,5 95,27.5 95,72.5 50,95 5,72.5 5,27.5" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        </div>
        <div class="qn-holo-shape qn-shape-2">
            <svg viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="50" cy="50" r="25" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
        </div>
        <div class="qn-holo-shape qn-shape-3">
            <svg viewBox="0 0 100 100"><rect x="15" y="15" width="70" height="70" fill="none" stroke="currentColor" stroke-width="2" transform="rotate(45 50 50)"/></svg>
        </div>
        <div class="qn-holo-shape qn-shape-4">
            <svg viewBox="0 0 100 100"><polygon points="50,10 90,90 10,90" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        </div>
    </div>
    
    <div class="container qn-hero-content">
        <!-- Status Bar -->
        <div class="qn-status-bar fade-up">
            <span class="qn-status-item">
                <span class="qn-status-dot"></span>
                SYSTEM ONLINE
            </span>
            <span class="qn-status-divider">|</span>
            <span class="qn-status-item">
                <i class="fas fa-certificate"></i>
                AKREDITASI <?= strtoupper(clean($stats['akreditasi'])) ?>
            </span>
            <span class="qn-status-divider">|</span>
            <span class="qn-status-item">
                <i class="fas fa-signal"></i>
                QUANTUM LINK: STABLE
            </span>
        </div>
        
        <!-- Main Title dengan Holographic Effect -->
        <h1 class="qn-hero-title fade-up">
            <span class="qn-title-top">
                <span class="qn-title-bracket">[</span>
                <span class="qn-title-label">INITIALIZE</span>
                <span class="qn-title-bracket">]</span>
            </span>
            <span class="qn-title-main">
                <span class="qn-word" data-text="BENTUK">BENTUK</span>
                <span class="qn-word qn-holo-text" data-text="MASA DEPAN">MASA DEPAN</span>
            </span>
            <span class="qn-title-sub">
                <span class="qn-sub-line"></span>
                <span class="qn-sub-text" id="qnTypewriter">DIGITAL ANDA</span>
                <span class="qn-sub-cursor">_</span>
                <span class="qn-sub-line"></span>
            </span>
        </h1>
        
        <p class="qn-hero-subtitle fade-up">
            Program Studi Informatika terdepan yang mencetak 
            <span class="qn-highlight">AI Engineer</span>, 
            <span class="qn-highlight">Data Scientist</span>, dan 
            <span class="qn-highlight">Software Architect</span> 
            berkualitas tinggi untuk era transformasi digital global.
        </p>
        
        <!-- Holographic CTA Buttons -->
        <div class="qn-hero-cta fade-up">
            <a href="daftar.php" class="qn-btn qn-btn-primary qn-holo-btn">
                <span class="qn-btn-content">
                    <i class="fas fa-rocket"></i>
                    <span>DAFTAR SEKARANG</span>
                </span>
                <span class="qn-btn-glitch"></span>
                <span class="qn-btn-scan"></span>
            </a>
            <a href="#about" class="qn-btn qn-btn-ghost qn-holo-btn">
                <span class="qn-btn-content">
                    <i class="fas fa-play-circle"></i>
                    <span>JELAJAHI PROGRAM</span>
                </span>
                <span class="qn-btn-glitch"></span>
            </a>
        </div>
        
        <!-- Quantum Stats HUD -->
        <div class="qn-stats-hud fade-up">
            <div class="qn-hud-corner qn-corner-tl"></div>
            <div class="qn-hud-corner qn-corner-tr"></div>
            <div class="qn-hud-corner qn-corner-bl"></div>
            <div class="qn-hud-corner qn-corner-br"></div>
            
            <div class="qn-hud-header">
                <i class="fas fa-chart-line"></i>
                <span>LIVE METRICS // REAL-TIME</span>
                <span class="qn-hud-live">
                    <span class="qn-live-dot"></span>
                    LIVE
                </span>
            </div>
            
            <div class="qn-stats-grid">
                <div class="qn-stat-cell">
                    <div class="qn-stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="qn-stat-data">
                        <span class="qn-stat-value" data-target="<?= $stats['mahasiswa'] ?>">0</span>
                        <span class="qn-stat-unit">+</span>
                        <span class="qn-stat-label">MAHASISWA AKTIF</span>
                    </div>
                    <div class="qn-stat-bar"><div class="qn-bar-fill" style="--percent: 85%"></div></div>
                </div>
                
                <div class="qn-stat-cell">
                    <div class="qn-stat-icon"><i class="fas fa-flask"></i></div>
                    <div class="qn-stat-data">
                        <span class="qn-stat-value" data-target="<?= $stats['publikasi'] ?>">0</span>
                        <span class="qn-stat-unit">+</span>
                        <span class="qn-stat-label">PUBLIKASI RISET</span>
                    </div>
                    <div class="qn-stat-bar"><div class="qn-bar-fill" style="--percent: 72%"></div></div>
                </div>
                
                <div class="qn-stat-cell">
                    <div class="qn-stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="qn-stat-data">
                        <span class="qn-stat-value" data-target="<?= $stats['dosen'] ?>">0</span>
                        <span class="qn-stat-unit">+</span>
                        <span class="qn-stat-label">DOSEN AHLI</span>
                    </div>
                    <div class="qn-stat-bar"><div class="qn-bar-fill" style="--percent: 90%"></div></div>
                </div>
                
                <div class="qn-stat-cell">
                    <div class="qn-stat-icon"><i class="fas fa-handshake"></i></div>
                    <div class="qn-stat-data">
                        <span class="qn-stat-value" data-target="<?= $stats['mitra'] ?>">0</span>
                        <span class="qn-stat-unit">+</span>
                        <span class="qn-stat-label">MITRA INDUSTRI</span>
                    </div>
                    <div class="qn-stat-bar"><div class="qn-bar-fill" style="--percent: 68%"></div></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scroll Indicator -->
    <div class="qn-scroll-indicator">
        <div class="qn-scroll-line"></div>
        <span>SCROLL TO EXPLORE</span>
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<!-- ===== TECH STACK HOLOGRAPHIC MARQUEE ===== -->
<section class="qn-tech-section">
    <div class="qn-tech-header">
        <span class="qn-tech-label">
            <i class="fas fa-microchip"></i>
            TECH_STACK.init()
        </span>
        <span class="qn-tech-status">// 12 modules loaded</span>
    </div>
    <div class="qn-tech-marquee">
        <div class="qn-marquee-track">
            <?php foreach (array_merge($techStack, $techStack) as $tech): ?>
            <div class="qn-tech-chip" style="--chip-color: <?= $tech['color'] ?>">
                <i class="fab <?= $tech['icon'] ?>"></i>
                <span><?= $tech['name'] ?></span>
                <span class="qn-chip-glow"></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== PENGUMUMAN RUNNING TEXT ===== -->
<?php if (!empty($pengumuman)): ?>
<section class="qn-broadcast-strip">
    <div class="qn-broadcast-header">
        <span class="qn-broadcast-dot"></span>
        <span>LIVE BROADCAST</span>
    </div>
    <div class="qn-broadcast-marquee">
        <div class="qn-broadcast-track">
            <?php for ($loop = 0; $loop < 2; $loop++): ?>
                <?php foreach ($pengumuman as $p): ?>
                <span class="qn-broadcast-item">
                    <i class="fas fa-bullhorn"></i>
                    <strong><?= clean($p['judul']) ?></strong>
                    <span class="qn-broadcast-sep">◆</span>
                </span>
                <?php endforeach; ?>
            <?php endfor; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== VIDEO SHOWCASE: HOLOGRAPHIC DISPLAY ===== -->
<section class="qn-video-section" id="video">
    <div class="container">
        <div class="qn-section-header">
            <span class="qn-section-tag">
                <span class="qn-tag-bracket">[</span>
                VIRTUAL_TOUR
                <span class="qn-tag-bracket">]</span>
            </span>
            <h2 class="qn-section-title">
                <span class="qn-title-word">Jelajahi Kampus</span>
                <span class="qn-title-word qn-holo-text">Secara Virtual</span>
            </h2>
            <p class="qn-section-subtitle">Saksikan fasilitas modern dan suasana belajar yang inspiratif melalui holographic display</p>
        </div>
        
        <div class="qn-video-showcase fade-up">
            <div class="qn-video-container">
                <div class="qn-holo-frame">
                    <div class="qn-frame-corner qn-fc-tl"></div>
                    <div class="qn-frame-corner qn-fc-tr"></div>
                    <div class="qn-frame-corner qn-fc-bl"></div>
                    <div class="qn-frame-corner qn-fc-br"></div>
                    
                    <div class="qn-video-wrapper" id="videoWrapper">
                        <video id="campusVideo" poster="https://images.unsplash.com/photo-1562774053-701939374585?w=1200&q=80" preload="none">
                            <source src="https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4" type="video/mp4">
                        </video>
                        <div class="qn-video-overlay" id="videoOverlay">
                            <button class="qn-play-btn" id="videoPlayBtn">
                                <i class="fas fa-play"></i>
                                <span class="qn-play-ring"></span>
                                <span class="qn-play-ring"></span>
                                <span class="qn-play-ring"></span>
                            </button>
                            <div class="qn-video-info">
                                <span class="qn-video-tag">HOLOGRAM DISPLAY</span>
                                <h3>Virtual Campus Tour 2026</h3>
                                <p>Lihat fasilitas lab AI, co-working space, dan lingkungan belajar kami</p>
                            </div>
                        </div>
                        <div class="qn-video-controls" id="videoControls" style="display:none;">
                            <button id="videoPauseBtn"><i class="fas fa-pause"></i></button>
                            <div class="qn-video-progress">
                                <div class="qn-video-progress-bar" id="videoProgressBar"></div>
                            </div>
                            <span id="videoTime">0:00 / 0:00</span>
                            <button id="videoFullscreenBtn"><i class="fas fa-expand"></i></button>
                        </div>
                    </div>
                    
                    <!-- Holographic Scan Effect -->
                    <div class="qn-holo-scan"></div>
                </div>
            </div>
            
            <div class="qn-video-features">
                <?php 
                $features = [
                    ['icon' => 'fa-microchip', 'title' => 'Lab AI & Machine Learning', 'desc' => 'Dilengkapi GPU cluster untuk training model deep learning'],
                    ['icon' => 'fa-network-wired', 'title' => 'Lab Cyber Security', 'desc' => 'Simulasi serangan dan pertahanan siber real-world'],
                    ['icon' => 'fa-laptop-code', 'title' => 'Co-Working Space', 'desc' => 'Ruang kolaborasi modern untuk proyek industri'],
                ];
                foreach ($features as $i => $f): 
                ?>
                <div class="qn-feature-card fade-up" style="animation-delay: <?= $i * 0.1 ?>s">
                    <div class="qn-feature-num">0<?= $i + 1 ?></div>
                    <div class="qn-feature-icon"><i class="fas <?= $f['icon'] ?>"></i></div>
                    <div class="qn-feature-content">
                        <h4><?= $f['title'] ?></h4>
                        <p><?= $f['desc'] ?></p>
                    </div>
                    <div class="qn-feature-line"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ===== ABOUT: QUANTUM PROFILE ===== -->
<section class="qn-about" id="about">
    <div class="container">
        <div class="qn-about-grid">
            <div class="qn-about-visual fade-up">
                <div class="qn-hologram-display">
                    <div class="qn-holo-projector"></div>
                    <div class="qn-holo-beam"></div>
                    <div class="qn-holo-image">
                        <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&q=80" alt="Kolaborasi Mahasiswa" loading="lazy" width="800" height="600">
                        <div class="qn-holo-overlay"></div>
                    </div>
                    <div class="qn-holo-scanlines"></div>
                    
                    <!-- Floating Data Cards -->
                    <div class="qn-float-card qn-fc-1">
                        <i class="fas fa-award"></i>
                        <div>
                            <h4>Top 10</h4>
                            <p>Peringkat Nasional</p>
                        </div>
                    </div>
                    <div class="qn-float-card qn-fc-2">
                        <i class="fas fa-trophy"></i>
                        <div>
                            <h4>200+</h4>
                            <p>Prestasi</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="qn-about-content fade-up">
                <span class="qn-section-tag">
                    <span class="qn-tag-bracket">[</span>
                    TENTANG_KAMI
                    <span class="qn-tag-bracket">]</span>
                </span>
                <h2 class="qn-section-title">
                    <span class="qn-title-word">Kampus Informatika</span>
                    <span class="qn-title-word qn-holo-text">Terkemuka</span>
                </h2>
                <p class="qn-about-lead">
                    Kami memadukan teori komputasi yang kuat dengan praktik industri modern. 
                    Kurikulum kami dirancang bersama mitra teknologi global untuk memastikan 
                    lulusan tidak hanya siap kerja, tetapi siap memimpin inovasi.
                </p>
                
                <ul class="qn-features-list">
                    <li class="fade-up">
                        <div class="qn-feature-marker">01</div>
                        <div class="qn-feature-icon"><i class="fas fa-brain"></i></div>
                        <div class="qn-feature-text">
                            <h4>Kurikulum Berbasis AI & Industri</h4>
                            <p>Integrasi Machine Learning dan proyek nyata sejak semester awal.</p>
                        </div>
                    </li>
                    <li class="fade-up">
                        <div class="qn-feature-marker">02</div>
                        <div class="qn-feature-icon"><i class="fas fa-handshake"></i></div>
                        <div class="qn-feature-text">
                            <h4>Partnership Global</h4>
                            <p>Kerja sama dengan GoTo, Telkom, AWS, dan universitas mitra luar negeri.</p>
                        </div>
                    </li>
                    <li class="fade-up">
                        <div class="qn-feature-marker">03</div>
                        <div class="qn-feature-icon"><i class="fas fa-laptop-code"></i></div>
                        <div class="qn-feature-text">
                            <h4>Fasilitas Laboratorium Modern</h4>
                            <p>Lab AI, Cyber Security, dan Co-working Space berstandar industri.</p>
                        </div>
                    </li>
                </ul>
                
                <a href="tentang.php" class="qn-btn qn-btn-primary qn-holo-btn">
                    <span class="qn-btn-content">
                        <span>SELENGKAPNYA</span>
                        <i class="fas fa-arrow-right"></i>
                    </span>
                    <span class="qn-btn-glitch"></span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ===== EVENT COUNTDOWN: TEMPORAL GATE ===== -->
<?php if (!empty($events)): ?>
<section class="qn-events">
    <div class="container">
        <div class="qn-section-header">
            <span class="qn-section-tag">
                <span class="qn-tag-bracket">[</span>
                TEMPORAL_GATE
                <span class="qn-tag-bracket">]</span>
            </span>
            <h2 class="qn-section-title">
                <span class="qn-title-word">Event Mendatang</span>
                <span class="qn-title-word qn-holo-text">Jangan Lewatkan</span>
            </h2>
        </div>
        
        <div class="qn-events-grid">
            <?php foreach ($events as $index => $event): 
                $eventTime = strtotime($event['tanggal_mulai']);
            ?>
            <div class="qn-event-card fade-up" data-event-time="<?= $eventTime ?>" style="animation-delay: <?= $index * 0.1 ?>s">
                <div class="qn-event-hologram">
                    <div class="qn-event-scan"></div>
                    <div class="qn-event-header">
                        <span class="qn-event-category"><?= clean($event['kategori']) ?></span>
                        <span class="qn-event-id">#EVT-<?= str_pad($event['id'] ?? ($index + 1), 3, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    
                    <h3 class="qn-event-title"><?= clean($event['judul']) ?></h3>
                    <p class="qn-event-desc"><?= clean(mb_substr($event['deskripsi'] ?? '', 0, 90)) ?>...</p>
                    
                    <!-- Quantum Countdown -->
                    <div class="qn-countdown">
                        <div class="qn-cd-unit">
                            <span class="qn-cd-value" data-days>00</span>
                            <span class="qn-cd-label">HARI</span>
                        </div>
                        <span class="qn-cd-sep">:</span>
                        <div class="qn-cd-unit">
                            <span class="qn-cd-value" data-hours>00</span>
                            <span class="qn-cd-label">JAM</span>
                        </div>
                        <span class="qn-cd-sep">:</span>
                        <div class="qn-cd-unit">
                            <span class="qn-cd-value" data-minutes>00</span>
                            <span class="qn-cd-label">MENIT</span>
                        </div>
                        <span class="qn-cd-sep">:</span>
                        <div class="qn-cd-unit">
                            <span class="qn-cd-value" data-seconds>00</span>
                            <span class="qn-cd-label">DETIK</span>
                        </div>
                    </div>
                    
                    <div class="qn-event-meta">
                        <span><i class="fas fa-map-marker-alt"></i> <?= clean($event['lokasi'] ?? 'TBA') ?></span>
                        <span><i class="fas fa-calendar"></i> <?= date('d M Y', $eventTime) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== KONSENTRASI: QUANTUM PATHS ===== -->
<section class="qn-expertise" id="expertise">
    <div class="container">
        <div class="qn-section-header">
            <span class="qn-section-tag">
                <span class="qn-tag-bracket">[</span>
                QUANTUM_PATHS
                <span class="qn-tag-bracket">]</span>
            </span>
            <h2 class="qn-section-title">
                <span class="qn-title-word">Pilih Jalur</span>
                <span class="qn-title-word qn-holo-text">Spesialisasimu</span>
            </h2>
            <p class="qn-section-subtitle">Tiga konsentrasi unggulan yang disesuaikan dengan kebutuhan industri masa depan</p>
        </div>

        <div class="qn-expertise-grid">
            <?php if (!empty($konsentrasi)): ?>
                <?php foreach ($konsentrasi as $i => $k): ?>
                <div class="qn-expertise-card fade-up" style="--card-color: <?= clean($k['warna']) ?>; animation-delay: <?= $i * 0.1 ?>s">
                    <?php if ($k['urutan'] == 1): ?><div class="qn-featured-tag">⚡ UNGGULAN</div><?php endif; ?>
                    
                    <div class="qn-card-hologram">
                        <div class="qn-card-number">0<?= $i + 1 ?></div>
                        <div class="qn-card-icon">
                            <i class="fas <?= clean($k['ikon']) ?>"></i>
                            <span class="qn-icon-ring"></span>
                        </div>
                        <h3><?= clean($k['nama']) ?></h3>
                        <p><?= clean($k['deskripsi']) ?></p>
                        
                        <ul class="qn-card-features">
                            <li><i class="fas fa-check"></i> Kurikulum Terkini</li>
                            <li><i class="fas fa-check"></i> Dosen Praktisi</li>
                            <li><i class="fas fa-check"></i> Sertifikasi Industri</li>
                        </ul>
                        
                        <a href="kurikulum.php" class="qn-card-link">
                            <span>LIHAT KURIKULUM</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        
                        <div class="qn-card-scan"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php
                $fallback = [
                    ['icon' => 'fa-brain', 'title' => 'Data Science & Intelligent System', 'desc' => 'Menguasai analisis data, machine learning, dan business intelligence.', 'color' => '#8B5CF6'],
                    ['icon' => 'fa-network-wired', 'title' => 'Communication & Info Technology', 'desc' => 'Spesialisasi jaringan, IoT, dan telekomunikasi modern.', 'color' => '#3B82F6', 'featured' => true],
                    ['icon' => 'fa-code', 'title' => 'Software Engineering & Algorithm', 'desc' => 'Pengembangan software berkualitas dan algoritma inovatif.', 'color' => '#10B981'],
                ];
                foreach ($fallback as $i => $k):
                ?>
                <div class="qn-expertise-card fade-up" style="--card-color: <?= $k['color'] ?>; animation-delay: <?= $i * 0.1 ?>s">
                    <?php if (isset($k['featured'])): ?><div class="qn-featured-tag">⚡ POPULER</div><?php endif; ?>
                    <div class="qn-card-hologram">
                        <div class="qn-card-number">0<?= $i + 1 ?></div>
                        <div class="qn-card-icon">
                            <i class="fas <?= $k['icon'] ?>"></i>
                            <span class="qn-icon-ring"></span>
                        </div>
                        <h3><?= $k['title'] ?></h3>
                        <p><?= $k['desc'] ?></p>
                        <a href="#" class="qn-card-link">
                            <span>PELAJARI LEBIH LANJUT</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <div class="qn-card-scan"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== CODE SHOWCASE: TERMINAL INTERFACE ===== -->
<section class="qn-code-section">
    <div class="container">
        <div class="qn-code-showcase fade-up">
            <div class="qn-terminal">
                <div class="qn-terminal-header">
                    <div class="qn-terminal-dots">
                        <span></span><span></span><span></span>
                    </div>
                    <span class="qn-terminal-title">quantum_model.py — python</span>
                    <span class="qn-terminal-status">● ACTIVE</span>
                </div>
                <div class="qn-terminal-body">
                    <pre><code><span class="qn-code-kw">import</span> tensorflow <span class="qn-code-kw">as</span> tf
<span class="qn-code-kw">from</span> transformers <span class="qn-code-kw">import</span> AutoModel

<span class="qn-code-cmt"># Inisialisasi model AI untuk NLP</span>
<span class="qn-code-kw">class</span> <span class="qn-code-cls">InformatikaNLP</span>:
    <span class="qn-code-kw">def</span> <span class="qn-code-fn">__init__</span>(<span class="qn-code-var">self</span>):
        <span class="qn-code-var">self</span>.model = AutoModel.from_pretrained(<span class="qn-code-str">'bert-base'</span>)
        <span class="qn-code-var">self</span>.tokenizer = tf.keras.layers.TextVectorization()
    
    <span class="qn-code-kw">def</span> <span class="qn-code-fn">predict</span>(<span class="qn-code-var">self</span>, text):
        <span class="qn-code-cmt"># Proses input dan kembalikan prediksi</span>
        <span class="qn-code-kw">return</span> <span class="qn-code-var">self</span>.model(text)

<span class="qn-code-cmt"># Buat instance dan jalankan</span>
nlp = InformatikaNLP()
result = nlp.predict(<span class="qn-code-str">"Hello, Informatika!"</span>)
<span class="qn-code-fn">print</span>(f<span class="qn-code-str">"Result: {result}"</span>)
<span class="qn-code-cursor">▋</span></code></pre>
                </div>
            </div>
            
            <div class="qn-code-info">
                <span class="qn-section-tag">
                    <span class="qn-tag-bracket">[</span>
                    HANDS_ON_CODING
                    <span class="qn-tag-bracket">]</span>
                </span>
                <h2>
                    <span class="qn-title-word">Belajar Langsung dengan</span>
                    <span class="qn-title-word qn-holo-text">Real Projects</span>
                </h2>
                <p>Mahasiswa kami tidak hanya belajar teori, tetapi langsung mengerjakan proyek nyata dengan teknologi terkini seperti AI, Machine Learning, dan Cloud Computing.</p>
                <ul class="qn-code-features">
                    <li><i class="fas fa-terminal"></i> Project-based Learning</li>
                    <li><i class="fas fa-users"></i> Industry Mentors</li>
                    <li><i class="fas fa-cloud"></i> Cloud Resources</li>
                    <li><i class="fab fa-github"></i> Open Source Contribution</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ===== PROSPEK KARIR: DATA ANALYTICS ===== -->
<section class="qn-karir" id="karir">
    <div class="container">
        <div class="qn-section-header">
            <span class="qn-section-tag">
                <span class="qn-tag-bracket">[</span>
                CAREER_DATA
                <span class="qn-tag-bracket">]</span>
            </span>
            <h2 class="qn-section-title">
                <span class="qn-title-word">Lulusan Kami</span>
                <span class="qn-title-word qn-holo-text">Diincar Industri</span>
            </h2>
            <p class="qn-section-subtitle">Berbagai peran strategis dengan kompensasi kompetitif menanti Anda</p>
        </div>

        <div class="qn-karir-grid">
            <?php if (!empty($karirPaths)): ?>
                <?php foreach ($karirPaths as $index => $kp): 
                    $demandClass = $kp['permintaan'] === 'sangat_tinggi' ? 'high' : 'medium';
                    $demandText = $kp['permintaan'] === 'sangat_tinggi' ? 'SANGAT TINGGI' : 'TINGGI';
                    $avgSalary = ($kp['gaji_min'] + $kp['gaji_max']) / 2;
                    $salaryPercent = min(100, ($avgSalary / 15000000) * 100);
                ?>
                <div class="qn-karir-card fade-up" style="animation-delay: <?= $index * 0.1 ?>s">
                    <div class="qn-karir-header">
                        <div class="qn-karir-icon">
                            <i class="fas <?= clean($kp['ikon']) ?>"></i>
                        </div>
                        <div class="qn-karir-badge <?= $demandClass ?>"><?= $demandText ?></div>
                    </div>
                    <h3><?= clean($kp['jabatan']) ?></h3>
                    <div class="qn-karir-salary">
                        <span class="qn-salary-label">ESTIMASI GAJI</span>
                        <span class="qn-salary-value">Rp <?= number_format($kp['gaji_min'], 0, ',', '.') ?> - <?= number_format($kp['gaji_max'], 0, ',', '.') ?></span>
                    </div>
                    <div class="qn-karir-bar">
                        <div class="qn-bar-bg"></div>
                        <div class="qn-bar-fill" style="--percent: <?= $salaryPercent ?>%"></div>
                        <span class="qn-bar-marker" style="left: <?= $salaryPercent ?>%"></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== TESTIMONIALS: HOLOGRAPHIC CAROUSEL ===== -->
<section class="qn-testimonials" id="testimonials">
    <div class="container">
        <div class="qn-section-header">
            <span class="qn-section-tag">
                <span class="qn-tag-bracket">[</span>
                VOICE_LOGS
                <span class="qn-tag-bracket">]</span>
            </span>
            <h2 class="qn-section-title">
                <span class="qn-title-word">Kata</span>
                <span class="qn-title-word qn-holo-text">Mereka</span>
            </h2>
            <p class="qn-section-subtitle">Pengalaman nyata dari mahasiswa dan alumni kami</p>
        </div>

        <div class="qn-testimonial-carousel fade-up">
            <div class="qn-carousel-stage">
                <div class="qn-carousel-track" id="testimonialTrack">
                    <?php foreach ($testimonials as $i => $t): ?>
                    <div class="qn-testimonial-card">
                        <div class="qn-testi-hologram">
                            <div class="qn-quote-mark">
                                <i class="fas fa-quote-right"></i>
                            </div>
                            <p class="qn-quote-text">"<?= clean($t['quote']) ?>"</p>
                            
                            <div class="qn-testi-author">
                                <div class="qn-author-avatar">
                                    <img src="<?= clean($t['avatar']) ?>" alt="<?= clean($t['nama']) ?>" loading="lazy" width="60" height="60">
                                    <span class="qn-avatar-ring"></span>
                                </div>
                                <div class="qn-author-info">
                                    <h4><?= clean($t['nama']) ?></h4>
                                    <span><?= clean($t['tahun_masuk']) ?></span>
                                </div>
                                <div class="qn-author-id">#AL-<?= str_pad($i + 1, 3, '0', STR_PAD_LEFT) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="qn-carousel-controls">
                <button class="qn-carousel-btn prev" id="prevBtn"><i class="fas fa-chevron-left"></i></button>
                <div class="qn-carousel-dots" id="sliderDots"></div>
                <button class="qn-carousel-btn next" id="nextBtn"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</section>

<!-- ===== NEWS: DATA FEED ===== -->
<section class="qn-news" id="news">
    <div class="container">
        <div class="qn-section-header">
            <span class="qn-section-tag">
                <span class="qn-tag-bracket">[</span>
                DATA_FEED
                <span class="qn-tag-bracket">]</span>
            </span>
            <h2 class="qn-section-title">
                <span class="qn-title-word">Aktivitas</span>
                <span class="qn-title-word qn-holo-text">Kampus</span>
            </h2>
            <p class="qn-section-subtitle">Update terkini seputar pengajaran, penelitian, dan pengabdian</p>
        </div>

        <div class="qn-news-grid">
            <?php if (!empty($newsList)): ?>
                <?php foreach ($newsList as $index => $n): ?>
                <article class="qn-news-card fade-up <?= $index === 0 ? 'featured' : '' ?>" style="animation-delay: <?= $index * 0.1 ?>s">
                    <div class="qn-news-hologram">
                        <div class="qn-news-image">
                            <img src="<?= getBeritaImage($n['gambar'], $n['kategori']) ?>" alt="<?= clean($n['judul']) ?>" loading="lazy" width="600" height="400">
                            <span class="qn-news-category"><?= clean($n['kategori']) ?></span>
                            <span class="qn-news-id">#NWS-<?= str_pad($n['id'] ?? ($index + 1), 3, '0', STR_PAD_LEFT) ?></span>
                            <div class="qn-news-scan"></div>
                        </div>
                        <div class="qn-news-content">
                            <div class="qn-news-meta">
                                <span><i class="far fa-calendar"></i> <?= formatTanggal($n['published_at'] ?? $n['created_at']) ?></span>
                                <span class="qn-news-time">
                                    <i class="far fa-clock"></i> <?= getReadingTime($n['ringkasan']) ?>
                                </span>
                            </div>
                            <h3><?= clean($n['judul']) ?></h3>
                            <p><?= clean(mb_substr($n['ringkasan'], 0, 120)) ?>...</p>
                            <a href="berita-detail.php?slug=<?= clean($n['slug']) ?>" class="qn-news-link">
                                <span>BACA SELENGKAPNYA</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="qn-news-empty fade-up">
                    <i class="far fa-newspaper"></i>
                    <p>Belum ada berita yang dipublikasikan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== MITRA: PARTNER NETWORK ===== -->
<?php if (!empty($mitraList)): ?>
<section class="qn-mitra">
    <div class="container">
        <div class="qn-section-header">
            <span class="qn-section-tag">
                <span class="qn-tag-bracket">[</span>
                PARTNER_NETWORK
                <span class="qn-tag-bracket">]</span>
            </span>
            <h2 class="qn-section-title">
                <span class="qn-title-word">Didukung Oleh</span>
                <span class="qn-title-word qn-holo-text">Perusahaan Terbaik</span>
            </h2>
        </div>
        <div class="qn-mitra-grid fade-up">
            <?php foreach ($mitraList as $m): ?>
            <div class="qn-mitra-node">
                <div class="qn-node-ring"></div>
                <?php if (!empty($m['logo'])): ?>
                    <img src="<?= clean($m['logo']) ?>" alt="<?= clean($m['nama']) ?>" loading="lazy" width="160" height="80">
                <?php else: ?>
                    <i class="fas fa-building"></i>
                <?php endif; ?>
                <span><?= clean($m['nama']) ?></span>
                <span class="qn-node-status">● CONNECTED</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== FAQ: KNOWLEDGE BASE ===== -->
<?php if (!empty($faqs)): ?>
<section class="qn-faq" id="faq">
    <div class="container">
        <div class="qn-section-header">
            <span class="qn-section-tag">
                <span class="qn-tag-bracket">[</span>
                KNOWLEDGE_BASE
                <span class="qn-tag-bracket">]</span>
            </span>
            <h2 class="qn-section-title">
                <span class="qn-title-word">Pertanyaan</span>
                <span class="qn-title-word qn-holo-text">Umum</span>
            </h2>
            <p class="qn-section-subtitle">Jawaban untuk pertanyaan yang sering diajukan calon mahasiswa</p>
        </div>
        <div class="qn-faq-container">
            <div class="qn-faq-search-wrap fade-up">
                <i class="fas fa-search"></i>
                <input type="text" class="qn-faq-search" id="faqSearch" placeholder="Cari di knowledge base...">
                <span class="qn-search-status">AI SEARCH ACTIVE</span>
            </div>
            <div class="qn-faq-list">
                <?php foreach ($faqs as $i => $f): ?>
                <div class="qn-faq-item fade-up" data-question="<?= strtolower(clean($f['pertanyaan'])) ?>" style="animation-delay: <?= $i * 0.05 ?>s">
                    <div class="qn-faq-q">
                        <span class="qn-faq-id">Q<?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="qn-faq-text"><?= clean($f['pertanyaan']) ?></span>
                        <span class="qn-faq-toggle">
                            <span></span><span></span>
                        </span>
                    </div>
                    <div class="qn-faq-a">
                        <div class="qn-faq-answer">
                            <span class="qn-answer-label">AI RESPONSE</span>
                            <p><?= clean($f['jawaban']) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== CTA + CONTACT: QUANTUM LINK ===== -->
<section class="qn-cta" id="contact">
    <div class="container">
        <div class="qn-cta-card fade-up">
            <!-- Quantum Background -->
            <div class="qn-cta-bg">
                <div class="qn-cta-grid"></div>
                <div class="qn-cta-orb qn-orb-1"></div>
                <div class="qn-cta-orb qn-orb-2"></div>
            </div>
            
            <div class="qn-cta-content">
                <span class="qn-section-tag light">
                    <span class="qn-tag-bracket">[</span>
                    QUANTUM_LINK
                    <span class="qn-tag-bracket">]</span>
                </span>
                <h2>Siap Memulai Perjalanan Digitalmu?</h2>
                <p>Bergabunglah dengan ratusan mahasiswa yang telah memilih Informatika sebagai langkah awal karier impian mereka.</p>
                
                <div class="qn-contact-grid">
                    <div class="qn-contact-item">
                        <div class="qn-contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <span class="qn-contact-label">LOCATION</span>
                            <span class="qn-contact-value"><?= clean(getSetting('contact_address', getSetting('alamat', 'Jl. Informatika No. 1, Jakarta'))) ?></span>
                        </div>
                    </div>
                    <div class="qn-contact-item">
                        <div class="qn-contact-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <span class="qn-contact-label">EMAIL</span>
                            <span class="qn-contact-value"><?= clean(getSetting('contact_email', getSetting('email', 'info@informatikapro.ac.id'))) ?></span>
                        </div>
                    </div>
                    <div class="qn-contact-item">
                        <div class="qn-contact-icon"><i class="fab fa-whatsapp"></i></div>
                        <div>
                            <span class="qn-contact-label">WHATSAPP</span>
                            <span class="qn-contact-value">+<?= clean(getSetting('contact_whatsapp', getSetting('whatsapp', '6281234567890'))) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <form class="qn-contact-form" action="kirim-kontak.php" method="POST" id="hpContactForm" novalidate>
                <div class="qn-form-header">
                    <i class="fas fa-paper-plane"></i>
                    <h3>KIRIM PESAN</h3>
                    <span class="qn-form-status">● SECURE CHANNEL</span>
                </div>
                
                <div class="qn-form-group">
                    <label>NAMA LENGKAP</label>
                    <input type="text" name="nama" placeholder="John Doe" required data-validate="name">
                </div>
                <div class="qn-form-group">
                    <label>EMAIL AKTIF</label>
                    <input type="email" name="email" placeholder="john@example.com" required data-validate="email">
                </div>
                <div class="qn-form-group">
                    <label>SUBJEK PESAN</label>
                    <input type="text" name="subjek" placeholder="Pertanyaan tentang..." required data-validate="subject">
                </div>
                <div class="qn-form-group">
                    <label>PESAN</label>
                    <textarea name="pesan" rows="4" placeholder="Tulis pesan Anda di sini..." required data-validate="message"></textarea>
                </div>
                <button type="submit" class="qn-btn qn-btn-white qn-holo-btn" style="width: 100%; justify-content: center;" id="submitBtn">
                    <span class="qn-btn-content">
                        <i class="fas fa-paper-plane"></i>
                        <span>KIRIM PESAN</span>
                    </span>
                    <span class="qn-btn-glitch"></span>
                </button>
            </form>
        </div>
    </div>
</section>

</main>

<!-- ============================================================
   QUANTUM NEXUS STYLES (HOLOGRAPHIC THEME)
============================================================ -->
<style>
/* ===== GLOBAL VARIABLES ===== */
:root {
    --qn-primary: #10B981;
    --qn-primary-dark: #047857;
    --qn-primary-light: #34D399;
    --qn-cyan: #06B6D4;
    --qn-purple: #8B5CF6;
    --qn-pink: #EC4899;
    --qn-gold: #F59E0B;
    --qn-dark: #0F172A;
    --qn-darker: #020617;
    --qn-gray-50: #F8FAFC;
    --qn-gray-100: #F1F5F9;
    --qn-gray-200: #E2E8F0;
    --qn-gray-600: #475569;
    --qn-gray-700: #374151;
    --qn-holo-gradient: linear-gradient(135deg, #10B981 0%, #06B6D4 25%, #8B5CF6 50%, #EC4899 75%, #F59E0B 100%);
    --qn-holo-2: linear-gradient(90deg, #10B981, #06B6D4, #8B5CF6, #EC4899, #F59E0B, #10B981);
    --qn-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --qn-font-display: 'Space Grotesk', 'Inter', sans-serif;
}

* { box-sizing: border-box; }

/* ===== SCANLINES OVERLAY ===== */
.qn-scanlines {
    position: fixed; inset: 0; pointer-events: none; z-index: 9990;
    background: repeating-linear-gradient(
        0deg,
        rgba(16, 185, 129, 0.02) 0px,
        rgba(16, 185, 129, 0.02) 1px,
        transparent 1px,
        transparent 3px
    );
    opacity: 0.4;
    mix-blend-mode: overlay;
}

/* ===== HOLOGRAPHIC CURSOR ===== */
.qn-cursor, .qn-cursor-ring, .qn-cursor-trail {
    position: fixed; top: 0; left: 0;
    pointer-events: none; z-index: 9999;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: opacity 0.3s;
}
.qn-cursor {
    width: 8px; height: 8px;
    background: var(--qn-holo-gradient);
    background-size: 200% 200%;
    animation: qnHoloShift 3s ease infinite;
    box-shadow: 0 0 15px rgba(16,185,129,0.8);
}
.qn-cursor-ring {
    width: 40px; height: 40px;
    border: 2px solid transparent;
    background: var(--qn-holo-2) border-box;
    background-size: 300% 100%;
    animation: qnHoloShift 3s linear infinite, qnRingSpin 8s linear infinite;
    -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    transition: width 0.3s, height 0.3s;
}
.qn-cursor-trail {
    width: 60px; height: 60px;
    background: radial-gradient(circle, rgba(16,185,129,0.15), transparent 70%);
    filter: blur(10px);
}
.qn-cursor-ring.hover { width: 70px; height: 70px; }
@media (max-width: 1024px), (hover: none) {
    .qn-cursor, .qn-cursor-ring, .qn-cursor-trail { display: none; }
}
@keyframes qnRingSpin { to { transform: translate(-50%, -50%) rotate(360deg); } }
@keyframes qnHoloShift {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

/* ===== HOLOGRAPHIC SCROLL PROGRESS ===== */
.qn-scroll-progress {
    position: fixed; top: 0; left: 0; right: 0;
    height: 4px; z-index: 9998;
    background: rgba(16,185,129,0.1);
    overflow: hidden;
}
.qn-scroll-fill {
    height: 100%; width: 0;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    animation: qnHoloShift 3s linear infinite;
    transition: width 0.1s ease-out;
    box-shadow: 0 0 15px rgba(16,185,129,0.6);
}
.qn-scroll-glow {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(16,185,129,0.3), transparent);
    animation: qnScrollGlow 3s ease-in-out infinite;
}
@keyframes qnScrollGlow {
    0%, 100% { transform: translateX(-100%); }
    50% { transform: translateX(100%); }
}

/* ===== SKIP LINK ===== */
.qn-skip-link {
    position: absolute; left: -9999px; top: auto;
    width: 1px; height: 1px; overflow: hidden;
}
.qn-skip-link:focus {
    position: fixed; top: 10px; left: 10px;
    width: auto; height: auto; padding: 12px 20px;
    background: var(--qn-holo-gradient);
    color: #fff; border-radius: 8px;
    z-index: 10001; text-decoration: none;
    font-weight: 700; font-family: var(--qn-font-mono);
}

/* ===== BACK TO TOP (Holographic) ===== */
.qn-back-top {
    position: fixed; bottom: 30px; right: 24px; z-index: 997;
    width: 54px; height: 54px; border-radius: 50%;
    background: var(--qn-dark);
    border: 2px solid transparent;
    background-image: linear-gradient(var(--qn-dark), var(--qn-dark)), var(--qn-holo-2);
    background-origin: border-box;
    background-clip: padding-box, border-box;
    color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; opacity: 0; visibility: hidden;
    transform: translateY(20px) scale(0.8);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 8px 24px rgba(16,185,129,0.3);
}
.qn-back-top.show { opacity: 1; visibility: visible; transform: none; }
.qn-back-top:hover { transform: translateY(-4px) scale(1.05); }
.qn-btt-glow {
    position: absolute; inset: -4px; border-radius: 50%;
    background: var(--qn-holo-2); background-size: 300% 100%;
    filter: blur(10px); opacity: 0; z-index: -1;
    animation: qnHoloShift 3s linear infinite;
    transition: opacity 0.3s;
}
.qn-back-top:hover .qn-btt-glow { opacity: 0.6; }
.qn-progress-ring { position: absolute; width: 100%; height: 100%; transform: rotate(-90deg); }
.qn-ring-bg { stroke: rgba(255,255,255,0.1); }
.qn-ring-fill { stroke: url(#holoGrad); stroke: #10B981; stroke-linecap: round; stroke-dasharray: 94.25; stroke-dashoffset: 94.25; transition: stroke-dashoffset 0.3s; }

/* ===== COOKIE BANNER (Holographic) ===== */
.qn-cookie {
    position: fixed; bottom: 20px; left: 20px; right: 20px;
    max-width: 520px; margin: 0 auto; z-index: 9997;
    opacity: 0; visibility: hidden; transform: translateY(40px);
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.qn-cookie.show { opacity: 1; visibility: visible; transform: none; }
.qn-cookie-content {
    background: var(--qn-dark); color: #fff;
    border-radius: 20px; padding: 20px 24px;
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    border: 1px solid transparent;
    background-image: linear-gradient(var(--qn-dark), var(--qn-dark)), var(--qn-holo-2);
    background-origin: border-box;
    background-clip: padding-box, border-box;
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
}
.qn-cookie-icon {
    font-size: 2.5rem;
    animation: qnCookieBounce 2s infinite;
    filter: drop-shadow(0 0 10px rgba(245,158,11,0.6));
}
@keyframes qnCookieBounce {
    0%, 100% { transform: rotate(0) scale(1); }
    25% { transform: rotate(-15deg) scale(1.1); }
    75% { transform: rotate(15deg) scale(1.1); }
}
.qn-cookie-text { flex: 1; min-width: 200px; }
.qn-cookie-text h4 { font-size: 0.95rem; margin: 0 0 4px; font-family: var(--qn-font-mono); letter-spacing: 1px; }
.qn-cookie-text p { font-size: 0.82rem; color: rgba(255,255,255,0.7); margin: 0; line-height: 1.5; }
.qn-cookie-actions { display: flex; gap: 8px; }
.qn-cookie-btn {
    padding: 8px 16px; border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.2);
    background: transparent; color: #fff;
    cursor: pointer; font-size: 0.85rem;
    font-weight: 600; transition: all 0.25s;
    font-family: var(--qn-font-mono);
}
.qn-cookie-btn.primary {
    background: var(--qn-holo-gradient);
    background-size: 200% 200%;
    animation: qnHoloShift 3s ease infinite;
    border-color: transparent;
}
.qn-cookie-btn.primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,0.4); }
.qn-cookie-btn:not(.primary):hover { background: rgba(255,255,255,0.1); }

/* ===== HERO: QUANTUM TUNNEL ===== */
.qn-hero {
    min-height: 100vh; position: relative; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    padding: 140px 20px 100px;
    background: radial-gradient(ellipse at center, #0F172A 0%, #020617 100%);
}
.qn-quantum-canvas, .qn-data-canvas {
    position: absolute; inset: 0; z-index: 1;
    width: 100%; height: 100%; pointer-events: none;
}
.qn-data-canvas { opacity: 0.4; }

/* Tunnel Rings */
.qn-tunnel {
    position: absolute; inset: 0; z-index: 1;
    display: flex; align-items: center; justify-content: center;
    pointer-events: none;
}
.qn-tunnel-ring {
    position: absolute; border-radius: 50%;
    border: 1px solid rgba(16,185,129,0.3);
    animation: qnTunnelPulse 6s ease-in-out infinite;
}
.qn-ring-1 { width: 120%; height: 120%; border-color: rgba(16,185,129,0.4); animation-delay: 0s; }
.qn-ring-2 { width: 100%; height: 100%; border-color: rgba(6,182,212,0.35); animation-delay: 1s; }
.qn-ring-3 { width: 80%; height: 80%; border-color: rgba(139,92,246,0.3); animation-delay: 2s; }
.qn-ring-4 { width: 60%; height: 60%; border-color: rgba(236,72,153,0.25); animation-delay: 3s; }
.qn-ring-5 { width: 40%; height: 40%; border-color: rgba(245,158,11,0.2); animation-delay: 4s; }
@keyframes qnTunnelPulse {
    0%, 100% { transform: scale(1) rotate(0); opacity: 0.5; }
    50% { transform: scale(1.05) rotate(180deg); opacity: 1; }
}

/* Holo Grid */
.qn-holo-grid {
    position: absolute; inset: 0; z-index: 1;
    background-image:
        linear-gradient(rgba(16,185,129,0.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(16,185,129,0.08) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 70%);
    animation: qnGridPulse 4s ease-in-out infinite;
}
@keyframes qnGridPulse {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 0.6; }
}

/* Data Streams */
.qn-data-streams {
    position: absolute; inset: 0; z-index: 1;
    overflow: hidden; pointer-events: none;
    opacity: 0.15;
}
.qn-stream {
    position: absolute; top: -100px;
    font-family: var(--qn-font-mono);
    font-size: 12px; color: var(--qn-primary);
    writing-mode: vertical-rl;
    text-orientation: mixed;
    white-space: nowrap;
    animation: qnStreamFall var(--duration) linear infinite;
    animation-delay: var(--delay);
    left: var(--left);
    text-shadow: 0 0 10px var(--qn-primary);
}
@keyframes qnStreamFall {
    0% { transform: translateY(-100%); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(100vh); opacity: 0; }
}

/* Floating Holographic Shapes */
.qn-floating-holograms {
    position: absolute; inset: 0; z-index: 2;
    pointer-events: none;
}
.qn-holo-shape {
    position: absolute; color: rgba(16,185,129,0.3);
    animation: qnFloatShape 20s ease-in-out infinite;
    filter: drop-shadow(0 0 20px currentColor);
}
.qn-holo-shape svg { width: 100%; height: 100%; }
.qn-shape-1 { width: 150px; height: 150px; top: 10%; left: 8%; animation-delay: 0s; }
.qn-shape-2 { width: 120px; height: 120px; top: 15%; right: 10%; color: rgba(6,182,212,0.3); animation-delay: -5s; }
.qn-shape-3 { width: 100px; height: 100px; bottom: 20%; left: 12%; color: rgba(139,92,246,0.3); animation-delay: -10s; }
.qn-shape-4 { width: 130px; height: 130px; bottom: 15%; right: 8%; color: rgba(236,72,153,0.3); animation-delay: -15s; }
@keyframes qnFloatShape {
    0%, 100% { transform: translate(0, 0) rotate(0); }
    25% { transform: translate(20px, -30px) rotate(90deg); }
    50% { transform: translate(-20px, -50px) rotate(180deg); }
    75% { transform: translate(30px, -20px) rotate(270deg); }
}

/* Hero Content */
.qn-hero-content {
    position: relative; z-index: 3;
    text-align: center; color: #fff;
    max-width: 1100px; margin: 0 auto;
}

/* Status Bar */
.qn-status-bar {
    display: inline-flex; align-items: center; gap: 16px;
    padding: 10px 20px;
    background: rgba(15,23,42,0.6);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(16,185,129,0.3);
    border-radius: 50px;
    font-family: var(--qn-font-mono);
    font-size: 0.72rem; font-weight: 600;
    letter-spacing: 1.5px; color: rgba(255,255,255,0.8);
    margin-bottom: 32px;
    box-shadow: 0 0 20px rgba(16,185,129,0.2);
}
.qn-status-item {
    display: flex; align-items: center; gap: 8px;
}
.qn-status-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--qn-primary);
    box-shadow: 0 0 10px var(--qn-primary);
    animation: qnStatusPulse 1.5s infinite;
}
@keyframes qnStatusPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.3); }
}
.qn-status-divider { color: rgba(255,255,255,0.3); }

/* Hero Title - Holographic */
.qn-hero-title {
    font-size: clamp(2.5rem, 7vw, 5.5rem);
    font-weight: 900; line-height: 1.05;
    margin-bottom: 24px; letter-spacing: -0.03em;
    font-family: var(--qn-font-display);
}
.qn-title-top {
    display: flex; align-items: center; justify-content: center;
    gap: 12px; margin-bottom: 16px;
    font-family: var(--qn-font-mono);
    font-size: clamp(0.75rem, 1.5vw, 0.9rem);
    color: var(--qn-primary);
    letter-spacing: 4px; font-weight: 600;
}
.qn-title-bracket {
    color: var(--qn-primary);
    opacity: 0.7;
}
.qn-title-main {
    display: block; margin-bottom: 20px;
}
.qn-word {
    display: inline-block; position: relative;
}
.qn-holo-text {
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: qnHoloShift 4s linear infinite;
    position: relative;
    filter: drop-shadow(0 0 20px rgba(16,185,129,0.4));
}
.qn-holo-text::before, .qn-holo-text::after {
    content: attr(data-text);
    position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    background: inherit; background-size: inherit;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.qn-holo-text::before {
    animation: qnGlitchR 3s infinite;
    opacity: 0.6; mix-blend-mode: screen;
}
.qn-holo-text::after {
    animation: qnGlitchL 3s infinite;
    opacity: 0.6; mix-blend-mode: screen;
}
@keyframes qnGlitchR {
    0%, 90%, 100% { transform: translate(0); }
    92% { transform: translate(3px, -1px); }
    94% { transform: translate(-2px, 1px); }
}
@keyframes qnGlitchL {
    0%, 90%, 100% { transform: translate(0); }
    91% { transform: translate(-3px, 1px); }
    93% { transform: translate(2px, -1px); }
}

.qn-title-sub {
    display: flex; align-items: center; justify-content: center;
    gap: 16px; margin-top: 8px;
}
.qn-sub-line {
    height: 2px; width: 60px;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    animation: qnHoloShift 3s linear infinite;
}
.qn-sub-text {
    font-size: clamp(1.2rem, 3vw, 2rem);
    font-family: var(--qn-font-mono);
    color: #fff; letter-spacing: 3px;
    font-weight: 700;
}
.qn-sub-cursor {
    color: var(--qn-primary);
    animation: qnBlink 1s infinite;
    font-family: var(--qn-font-mono);
}
@keyframes qnBlink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0; }
}

/* Hero Subtitle */
.qn-hero-subtitle {
    font-size: 1.15rem; opacity: 0.85;
    max-width: 720px; margin: 0 auto 40px;
    line-height: 1.7;
}
.qn-highlight {
    color: var(--qn-primary-light);
    font-weight: 700;
    position: relative;
    padding: 0 4px;
    background: rgba(16,185,129,0.1);
    border-radius: 4px;
}

/* CTA Buttons - Holographic */
.qn-hero-cta {
    display: flex; gap: 16px; justify-content: center;
    flex-wrap: wrap; margin-bottom: 48px;
}
.qn-btn {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 14px 32px; border-radius: 12px;
    font-weight: 700; font-size: 0.9rem;
    text-decoration: none; border: none;
    cursor: pointer; transition: all 0.3s;
    font-family: var(--qn-font-mono);
    letter-spacing: 1.5px;
    position: relative; overflow: hidden;
}
.qn-btn-content {
    display: flex; align-items: center; gap: 10px;
    position: relative; z-index: 2;
}
.qn-btn-primary {
    background: var(--qn-holo-gradient);
    background-size: 200% 200%;
    color: #fff;
    animation: qnHoloShift 3s ease infinite;
    box-shadow: 0 8px 24px rgba(16,185,129,0.3);
}
.qn-btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(16,185,129,0.5);
}
.qn-btn-ghost {
    background: transparent; color: #fff;
    border: 2px solid rgba(255,255,255,0.3);
    backdrop-filter: blur(10px);
}
.qn-btn-ghost:hover {
    border-color: var(--qn-primary);
    background: rgba(16,185,129,0.1);
    color: var(--qn-primary-light);
}
.qn-btn-white {
    background: #fff; color: var(--qn-dark);
}
.qn-btn-white:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }
.qn-btn-glitch {
    position: absolute; inset: 0;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    opacity: 0; transition: opacity 0.3s;
    z-index: 1;
}
.qn-holo-btn:hover .qn-btn-glitch { opacity: 0.2; }
.qn-btn-scan {
    position: absolute; top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    z-index: 3;
}
.qn-holo-btn:hover .qn-btn-scan {
    animation: qnBtnScan 0.8s ease;
}
@keyframes qnBtnScan {
    to { left: 100%; }
}

/* Stats HUD - Holographic */
.qn-stats-hud {
    position: relative;
    background: rgba(15,23,42,0.5);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(16,185,129,0.3);
    border-radius: 20px;
    padding: 24px 28px;
    max-width: 900px; margin: 0 auto;
    box-shadow: 0 0 40px rgba(16,185,129,0.15);
}
.qn-hud-corner {
    position: absolute; width: 20px; height: 20px;
    border: 2px solid var(--qn-primary);
    pointer-events: none;
}
.qn-corner-tl { top: -2px; left: -2px; border-right: none; border-bottom: none; }
.qn-corner-tr { top: -2px; right: -2px; border-left: none; border-bottom: none; }
.qn-corner-bl { bottom: -2px; left: -2px; border-right: none; border-top: none; }
.qn-corner-br { bottom: -2px; right: -2px; border-left: none; border-top: none; }
.qn-hud-header {
    display: flex; align-items: center; gap: 10px;
    padding-bottom: 16px; margin-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    font-family: var(--qn-font-mono);
    font-size: 0.75rem; color: rgba(255,255,255,0.7);
    letter-spacing: 2px; font-weight: 600;
}
.qn-hud-header i { color: var(--qn-primary); }
.qn-hud-live {
    margin-left: auto;
    display: flex; align-items: center; gap: 6px;
    color: var(--qn-primary);
    font-size: 0.7rem;
}
.qn-live-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--qn-primary);
    box-shadow: 0 0 8px var(--qn-primary);
    animation: qnStatusPulse 1.5s infinite;
}

.qn-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
.qn-stat-cell {
    display: flex; flex-direction: column; gap: 8px;
    padding: 12px; background: rgba(255,255,255,0.03);
    border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);
    transition: all 0.3s;
}
.qn-stat-cell:hover {
    background: rgba(16,185,129,0.08);
    border-color: rgba(16,185,129,0.3);
    transform: translateY(-2px);
}
.qn-stat-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(16,185,129,0.15);
    color: var(--qn-primary);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
}
.qn-stat-data {
    display: flex; align-items: baseline; gap: 4px;
    flex-wrap: wrap;
}
.qn-stat-value {
    font-family: var(--qn-font-display);
    font-size: 1.8rem; font-weight: 900;
    color: #fff; line-height: 1;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: qnHoloShift 4s linear infinite;
}
.qn-stat-unit {
    color: var(--qn-primary); font-weight: 700;
    font-size: 1rem;
}
.qn-stat-label {
    display: block; width: 100%;
    font-family: var(--qn-font-mono);
    font-size: 0.65rem; color: rgba(255,255,255,0.6);
    letter-spacing: 1px; margin-top: 4px;
    text-transform: uppercase;
}
.qn-stat-bar {
    height: 3px; background: rgba(255,255,255,0.1);
    border-radius: 2px; overflow: hidden;
}
.qn-bar-fill {
    height: 100%; width: var(--percent);
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    animation: qnHoloShift 3s linear infinite;
    border-radius: 2px;
    box-shadow: 0 0 8px rgba(16,185,129,0.5);
}

/* Scroll Indicator */
.qn-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%;
    transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 12px;
    color: rgba(255,255,255,0.7);
    font-family: var(--qn-font-mono);
    font-size: 0.7rem; letter-spacing: 3px;
    font-weight: 600;
}
.qn-scroll-line {
    width: 2px; height: 40px;
    background: linear-gradient(to bottom, transparent, var(--qn-primary), transparent);
    animation: qnScrollLine 2s ease-in-out infinite;
}
@keyframes qnScrollLine {
    0%, 100% { transform: scaleY(0.5); opacity: 0.5; }
    50% { transform: scaleY(1); opacity: 1; }
}
.qn-scroll-indicator i {
    color: var(--qn-primary);
    animation: qnBounceDown 2s infinite;
}
@keyframes qnBounceDown {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(8px); }
}

/* ===== TECH SECTION ===== */
.qn-tech-section {
    background: var(--qn-darker);
    padding: 32px 0; position: relative;
    overflow: hidden;
    border-top: 1px solid rgba(16,185,129,0.2);
    border-bottom: 1px solid rgba(16,185,129,0.2);
}
.qn-tech-header {
    display: flex; justify-content: space-between;
    align-items: center; padding: 0 20px 20px;
    max-width: 1200px; margin: 0 auto;
    font-family: var(--qn-font-mono);
    font-size: 0.78rem;
}
.qn-tech-label {
    color: var(--qn-primary);
    display: flex; align-items: center; gap: 8px;
    letter-spacing: 2px; font-weight: 700;
}
.qn-tech-status { color: rgba(255,255,255,0.5); }

.qn-tech-marquee { overflow: hidden; }
.qn-marquee-track {
    display: flex; gap: 16px;
    animation: qnTechMarquee 40s linear infinite;
    white-space: nowrap; width: fit-content;
}
@keyframes qnTechMarquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.qn-tech-chip {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 50px;
    color: #fff; font-family: var(--qn-font-mono);
    font-weight: 600; font-size: 0.88rem;
    transition: all 0.3s; position: relative;
    overflow: hidden;
}
.qn-tech-chip:hover {
    border-color: var(--chip-color);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px color-mix(in srgb, var(--chip-color) 30%, transparent);
}
.qn-tech-chip i {
    font-size: 1.3rem; color: var(--chip-color);
    filter: drop-shadow(0 0 8px var(--chip-color));
}
.qn-chip-glow {
    position: absolute; inset: 0;
    background: radial-gradient(circle at center, var(--chip-color), transparent 70%);
    opacity: 0; transition: opacity 0.3s;
}
.qn-tech-chip:hover .qn-chip-glow { opacity: 0.15; }

/* ===== BROADCAST STRIP ===== */
.qn-broadcast-strip {
    background: linear-gradient(90deg, var(--qn-dark), #047857, var(--qn-dark));
    padding: 14px 0; position: relative;
    overflow: hidden; z-index: 5;
    border-bottom: 1px solid rgba(16,185,129,0.3);
}
.qn-broadcast-header {
    position: absolute; left: 20px; top: 50%;
    transform: translateY(-50%);
    display: flex; align-items: center; gap: 8px;
    font-family: var(--qn-font-mono);
    font-size: 0.7rem; color: #fff;
    letter-spacing: 2px; font-weight: 700;
    padding: 4px 12px; background: rgba(239,68,68,0.9);
    border-radius: 4px; z-index: 3;
}
.qn-broadcast-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #fff;
    animation: qnStatusPulse 1s infinite;
}
.qn-broadcast-marquee { overflow: hidden; margin-left: 160px; }
.qn-broadcast-track {
    display: inline-flex; white-space: nowrap;
    animation: qnBroadcastMarquee 35s linear infinite;
}
@keyframes qnBroadcastMarquee {
    from { transform: translateX(0); }
    to { transform: translateX(-50%); }
}
.qn-broadcast-item {
    display: inline-flex; align-items: center; gap: 10px;
    color: #fff; font-size: 0.88rem;
    margin-right: 60px;
    font-family: var(--qn-font-mono);
}
.qn-broadcast-item i { color: var(--qn-gold); font-size: 0.9rem; }
.qn-broadcast-item strong { font-weight: 700; }
.qn-broadcast-sep { color: rgba(255,255,255,0.4); }

/* ===== SECTION HEADERS ===== */
.qn-section-header {
    text-align: center; margin-bottom: 60px;
}
.qn-section-tag {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 16px;
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.3);
    color: var(--qn-primary);
    border-radius: 50px;
    font-family: var(--qn-font-mono);
    font-size: 0.72rem; font-weight: 700;
    letter-spacing: 2px; margin-bottom: 16px;
}
.qn-section-tag.light {
    background: rgba(255,255,255,0.15);
    border-color: rgba(255,255,255,0.3);
    color: #fff;
}
.qn-tag-bracket { opacity: 0.5; }
.qn-section-title {
    font-size: clamp(2rem, 4.5vw, 3rem);
    font-weight: 900; margin-bottom: 16px;
    color: var(--qn-dark); letter-spacing: -0.02em;
    font-family: var(--qn-font-display);
}
.qn-title-word { display: inline-block; }
.qn-section-subtitle {
    font-size: 1.05rem; color: var(--qn-gray-600);
    max-width: 620px; margin: 0 auto;
    line-height: 1.7;
}

/* ===== VIDEO SECTION ===== */
.qn-video-section {
    padding: 100px 0; background: var(--qn-gray-50);
}
.qn-video-showcase {
    display: grid; grid-template-columns: 1.5fr 1fr;
    gap: 40px; align-items: center;
}
.qn-holo-frame {
    position: relative; padding: 20px;
    background: rgba(16,185,129,0.05);
    border: 1px solid rgba(16,185,129,0.2);
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
}
.qn-frame-corner {
    position: absolute; width: 30px; height: 30px;
    border: 2px solid var(--qn-primary);
}
.qn-fc-tl { top: -2px; left: -2px; border-right: none; border-bottom: none; border-top-left-radius: 24px; }
.qn-fc-tr { top: -2px; right: -2px; border-left: none; border-bottom: none; border-top-right-radius: 24px; }
.qn-fc-bl { bottom: -2px; left: -2px; border-right: none; border-top: none; border-bottom-left-radius: 24px; }
.qn-fc-br { bottom: -2px; right: -2px; border-left: none; border-top: none; border-bottom-right-radius: 24px; }

.qn-video-wrapper {
    position: relative; border-radius: 16px;
    overflow: hidden; background: #000;
}
.qn-video-wrapper video { width: 100%; display: block; }
.qn-video-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(15,23,42,0.85), rgba(4,120,87,0.6));
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 24px; transition: opacity 0.3s;
}
.qn-video-overlay.hidden { opacity: 0; pointer-events: none; }
.qn-play-btn {
    width: 80px; height: 80px; border-radius: 50%;
    background: var(--qn-holo-gradient);
    background-size: 200% 200%;
    animation: qnHoloShift 3s ease infinite;
    border: none; color: #fff; font-size: 1.8rem;
    cursor: pointer; position: relative;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 10px 30px rgba(16,185,129,0.5);
    transition: transform 0.3s;
}
.qn-play-btn:hover { transform: scale(1.1); }
.qn-play-ring {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.5);
    animation: qnPlayRing 2s infinite;
}
.qn-play-ring:nth-child(2) { animation-delay: 0.5s; }
.qn-play-ring:nth-child(3) { animation-delay: 1s; }
@keyframes qnPlayRing {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.8); opacity: 0; }
}
.qn-video-info { text-align: center; color: #fff; }
.qn-video-tag {
    display: inline-block; padding: 4px 12px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    font-family: var(--qn-font-mono);
    font-size: 0.7rem; letter-spacing: 2px;
    margin-bottom: 12px; font-weight: 700;
}
.qn-video-info h3 { font-size: 1.5rem; font-weight: 800; margin: 0 0 8px; font-family: var(--qn-font-display); }
.qn-video-info p { font-size: 0.95rem; opacity: 0.9; margin: 0; }

.qn-video-controls {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
    padding: 16px 20px; display: flex;
    align-items: center; gap: 12px; color: #fff;
}
.qn-video-controls button {
    background: none; border: none; color: #fff;
    font-size: 1.1rem; cursor: pointer;
    transition: color 0.2s;
}
.qn-video-controls button:hover { color: var(--qn-primary); }
.qn-video-progress {
    flex: 1; height: 4px;
    background: rgba(255,255,255,0.2);
    border-radius: 2px; cursor: pointer;
    overflow: hidden;
}
.qn-video-progress-bar {
    height: 100%;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    animation: qnHoloShift 3s linear infinite;
    border-radius: 2px; width: 0%;
}
.qn-video-controls span {
    font-family: var(--qn-font-mono);
    font-size: 0.8rem;
}

.qn-holo-scan {
    position: absolute; top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--qn-primary), transparent);
    box-shadow: 0 0 10px var(--qn-primary);
    animation: qnHoloScan 3s ease-in-out infinite;
    pointer-events: none; z-index: 2;
}
@keyframes qnHoloScan {
    0% { top: 0; opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { top: 100%; opacity: 0; }
}

.qn-video-features {
    display: flex; flex-direction: column; gap: 16px;
}
.qn-feature-card {
    display: grid; grid-template-columns: auto 1fr;
    gap: 16px; padding: 20px;
    background: #fff; border-radius: 16px;
    border: 1px solid var(--qn-gray-200);
    transition: all 0.3s; position: relative;
    overflow: hidden;
}
.qn-feature-card::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 4px; background: var(--qn-holo-2);
    background-size: 100% 300%;
    animation: qnHoloShift 3s linear infinite;
}
.qn-feature-card:hover {
    transform: translateX(8px);
    box-shadow: 0 10px 30px rgba(16,185,129,0.15);
    border-color: var(--qn-primary);
}
.qn-feature-num {
    font-family: var(--qn-font-display);
    font-size: 1.8rem; font-weight: 900;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: qnHoloShift 4s linear infinite;
    line-height: 1;
}
.qn-feature-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: var(--qn-holo-gradient);
    background-size: 200% 200%;
    animation: qnHoloShift 3s ease infinite;
    color: #fff; display: flex;
    align-items: center; justify-content: center;
    font-size: 1.1rem;
}
.qn-feature-content h4 {
    font-size: 1rem; font-weight: 700;
    margin: 0 0 6px; color: var(--qn-dark);
}
.qn-feature-content p {
    font-size: 0.85rem; color: var(--qn-gray-600);
    line-height: 1.5; margin: 0;
}

/* ===== ABOUT SECTION ===== */
.qn-about { padding: 100px 0; background: #fff; }
.qn-about-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 60px; align-items: center;
}

/* Hologram Display */
.qn-hologram-display {
    position: relative; padding: 40px 20px 80px;
    display: flex; align-items: center; justify-content: center;
    min-height: 500px;
}
.qn-holo-projector {
    position: absolute; bottom: 0; left: 50%;
    transform: translateX(-50%);
    width: 200px; height: 20px;
    background: linear-gradient(180deg, var(--qn-dark), #1E293B);
    border-radius: 10px 10px 0 0;
    box-shadow: 0 0 30px rgba(16,185,129,0.4);
}
.qn-holo-projector::before {
    content: ''; position: absolute; top: 5px; left: 50%;
    transform: translateX(-50%);
    width: 30px; height: 10px;
    background: var(--qn-primary);
    border-radius: 5px;
    box-shadow: 0 0 20px var(--qn-primary);
}
.qn-holo-beam {
    position: absolute; bottom: 20px; left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 200px solid transparent;
    border-right: 200px solid transparent;
    border-bottom: 400px solid rgba(16,185,129,0.08);
    filter: blur(10px);
    animation: qnBeamPulse 3s ease-in-out infinite;
}
@keyframes qnBeamPulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; }
}
.qn-holo-image {
    position: relative; width: 100%; max-width: 450px;
    border-radius: 20px; overflow: hidden;
    box-shadow: 0 20px 60px rgba(16,185,129,0.25);
    border: 2px solid rgba(16,185,129,0.3);
    animation: qnHoloFloat 4s ease-in-out infinite;
}
@keyframes qnHoloFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}
.qn-holo-image img { width: 100%; display: block; }
.qn-holo-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(16,185,129,0.1), transparent, rgba(6,182,212,0.1));
    mix-blend-mode: overlay;
}
.qn-holo-scanlines {
    position: absolute; inset: 0; pointer-events: none;
    background: repeating-linear-gradient(
        0deg,
        rgba(16,185,129,0.1) 0px,
        rgba(16,185,129,0.1) 1px,
        transparent 1px,
        transparent 4px
    );
    opacity: 0.6;
    border-radius: 20px;
}

/* Floating Cards */
.qn-float-card {
    position: absolute;
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px;
    background: #fff; border-radius: 14px;
    border: 1px solid transparent;
    background-image: linear-gradient(#fff, #fff), var(--qn-holo-2);
    background-origin: border-box;
    background-clip: padding-box, border-box;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    animation: qnFloatCard 5s ease-in-out infinite;
}
.qn-float-card i {
    font-size: 1.5rem;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: qnHoloShift 3s linear infinite;
}
.qn-float-card h4 {
    font-size: 1rem; font-weight: 800;
    margin: 0; color: var(--qn-dark);
    font-family: var(--qn-font-display);
}
.qn-float-card p {
    font-size: 0.75rem; color: var(--qn-gray-600);
    margin: 0;
}
.qn-fc-1 { top: 10%; left: -5%; animation-delay: 0s; }
.qn-fc-2 { bottom: 20%; right: -5%; animation-delay: -2.5s; }
@keyframes qnFloatCard {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}

/* About Content */
.qn-about-lead {
    font-size: 1.05rem; color: var(--qn-gray-600);
    line-height: 1.7; margin-bottom: 32px;
}
.qn-features-list {
    list-style: none; padding: 0; margin: 0 0 32px;
    display: flex; flex-direction: column; gap: 20px;
}
.qn-features-list li {
    display: grid; grid-template-columns: auto auto 1fr;
    gap: 16px; align-items: flex-start;
}
.qn-feature-marker {
    font-family: var(--qn-font-display);
    font-size: 1.2rem; font-weight: 900;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: qnHoloShift 4s linear infinite;
    min-width: 30px;
}
.qn-feature-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: var(--qn-holo-gradient);
    background-size: 200% 200%;
    animation: qnHoloShift 3s ease infinite;
    color: #fff; display: flex;
    align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.qn-feature-text h4 {
    font-size: 1rem; font-weight: 700;
    margin: 0 0 4px; color: var(--qn-dark);
}
.qn-feature-text p {
    font-size: 0.88rem; color: var(--qn-gray-600);
    margin: 0; line-height: 1.5;
}

/* ===== EVENTS SECTION ===== */
.qn-events {
    padding: 100px 0;
    background: linear-gradient(135deg, var(--qn-dark) 0%, #047857 100%);
    color: #fff; position: relative; overflow: hidden;
}
.qn-events::before {
    content: ''; position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 50px 50px;
    opacity: 0.5;
}
.qn-events .qn-section-title { color: #fff; }
.qn-events .qn-section-subtitle { color: rgba(255,255,255,0.8); }
.qn-events .qn-section-tag {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
    color: var(--qn-primary-light);
}

.qn-events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px; position: relative; z-index: 1;
}
.qn-event-card {
    position: relative;
}
.qn-event-hologram {
    padding: 24px; border-radius: 20px;
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,0.15);
    position: relative; overflow: hidden;
    transition: all 0.3s;
}
.qn-event-hologram::before {
    content: ''; position: absolute; inset: 0;
    border-radius: 20px; padding: 1px;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    animation: qnHoloShift 4s linear infinite;
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.3s;
}
.qn-event-card:hover .qn-event-hologram::before { opacity: 1; }
.qn-event-card:hover .qn-event-hologram {
    transform: translateY(-8px);
    background: rgba(255,255,255,0.08);
}

.qn-event-scan {
    position: absolute; top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--qn-primary), transparent);
    animation: qnEventScan 4s ease-in-out infinite;
}
@keyframes qnEventScan {
    0%, 100% { top: 0; opacity: 0; }
    50% { top: 100%; opacity: 1; }
}

.qn-event-header {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 16px;
}
.qn-event-category {
    padding: 4px 12px;
    background: rgba(16,185,129,0.2);
    color: var(--qn-primary-light);
    border-radius: 20px;
    font-family: var(--qn-font-mono);
    font-size: 0.7rem; font-weight: 700;
    letter-spacing: 1px;
}
.qn-event-id {
    font-family: var(--qn-font-mono);
    font-size: 0.72rem; color: rgba(255,255,255,0.5);
    letter-spacing: 1px;
}

.qn-event-title {
    font-size: 1.2rem; font-weight: 800;
    margin: 0 0 10px; line-height: 1.3;
    font-family: var(--qn-font-display);
}
.qn-event-desc {
    font-size: 0.88rem; opacity: 0.85;
    margin: 0 0 16px; line-height: 1.5;
}

/* Quantum Countdown */
.qn-countdown {
    display: flex; align-items: center; justify-content: space-between;
    gap: 6px; padding: 16px;
    background: rgba(0,0,0,0.3);
    border-radius: 12px; margin-bottom: 16px;
    border: 1px solid rgba(16,185,129,0.2);
}
.qn-cd-unit { text-align: center; flex: 1; }
.qn-cd-value {
    display: block;
    font-family: var(--qn-font-display);
    font-size: 1.6rem; font-weight: 900;
    line-height: 1;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: qnHoloShift 4s linear infinite;
    margin-bottom: 4px;
}
.qn-cd-label {
    font-family: var(--qn-font-mono);
    font-size: 0.62rem; opacity: 0.6;
    letter-spacing: 1.5px;
}
.qn-cd-sep {
    color: var(--qn-primary);
    font-weight: 700; font-size: 1.2rem;
    animation: qnBlink 1s infinite;
}

.qn-event-meta {
    display: flex; gap: 16px; flex-wrap: wrap;
    font-size: 0.8rem; opacity: 0.75;
}
.qn-event-meta span {
    display: inline-flex; align-items: center; gap: 6px;
}
.qn-event-meta i { color: var(--qn-primary); }

/* ===== EXPERTISE SECTION ===== */
.qn-expertise { padding: 100px 0; background: var(--qn-gray-50); }
.qn-expertise-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
}
.qn-expertise-card {
    position: relative;
}
.qn-card-hologram {
    position: relative; padding: 32px;
    background: #fff; border-radius: 24px;
    border: 1px solid var(--qn-gray-200);
    transition: all 0.4s; overflow: hidden;
    height: 100%;
    display: flex; flex-direction: column;
}
.qn-card-hologram::before {
    content: ''; position: absolute; top: 0; left: 0;
    width: 100%; height: 4px;
    background: var(--card-color, var(--qn-primary));
    box-shadow: 0 0 20px var(--card-color, var(--qn-primary));
}
.qn-card-hologram::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, var(--card-color, var(--qn-primary)), transparent);
    opacity: 0; transition: opacity 0.3s;
    border-radius: 24px;
}
.qn-expertise-card:hover .qn-card-hologram {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px color-mix(in srgb, var(--card-color) 20%, transparent);
    border-color: var(--card-color);
}
.qn-expertise-card:hover .qn-card-hologram::after { opacity: 0.05; }

.qn-featured-tag {
    position: absolute; top: 16px; right: 16px;
    padding: 4px 12px; border-radius: 20px;
    background: var(--qn-holo-gradient);
    background-size: 200% 200%;
    animation: qnHoloShift 3s ease infinite;
    color: #fff; font-family: var(--qn-font-mono);
    font-size: 0.68rem; font-weight: 800;
    letter-spacing: 1px; z-index: 2;
    box-shadow: 0 4px 12px rgba(245,158,11,0.3);
}
.qn-card-number {
    position: absolute; top: 20px; left: 24px;
    font-family: var(--qn-font-display);
    font-size: 3rem; font-weight: 900;
    line-height: 1; opacity: 0.1;
    color: var(--card-color, var(--qn-primary));
}
.qn-card-icon {
    width: 72px; height: 72px; border-radius: 18px;
    background: color-mix(in srgb, var(--card-color, var(--qn-primary)) 15%, transparent);
    color: var(--card-color, var(--qn-primary));
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 12px 0 20px;
    position: relative;
}
.qn-icon-ring {
    position: absolute; inset: -4px; border-radius: 22px;
    border: 2px dashed var(--card-color, var(--qn-primary));
    opacity: 0; transition: opacity 0.3s;
    animation: qnRingSpin 10s linear infinite;
}
.qn-expertise-card:hover .qn-icon-ring { opacity: 0.5; }

.qn-card-hologram h3 {
    font-size: 1.25rem; font-weight: 800;
    margin: 0 0 12px; color: var(--qn-dark);
    font-family: var(--qn-font-display);
    position: relative; z-index: 1;
}
.qn-card-hologram p {
    font-size: 0.92rem; color: var(--qn-gray-600);
    line-height: 1.6; margin: 0 0 20px;
    position: relative; z-index: 1;
    flex: 1;
}
.qn-card-features {
    list-style: none; padding: 0; margin: 0 0 20px;
    display: flex; flex-direction: column; gap: 8px;
    position: relative; z-index: 1;
}
.qn-card-features li {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.85rem; color: var(--qn-gray-600);
    font-weight: 500;
}
.qn-card-features i { color: var(--card-color, var(--qn-primary)); }
.qn-card-link {
    display: inline-flex; align-items: center; gap: 10px;
    color: var(--card-color, var(--qn-primary));
    font-weight: 700; text-decoration: none;
    font-family: var(--qn-font-mono);
    font-size: 0.82rem; letter-spacing: 1px;
    transition: gap 0.3s; position: relative; z-index: 1;
}
.qn-card-link:hover { gap: 16px; }

.qn-card-scan {
    position: absolute; top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--card-color, var(--qn-primary)), transparent);
    opacity: 0;
    animation: qnCardScan 4s ease-in-out infinite;
}
.qn-expertise-card:hover .qn-card-scan { opacity: 1; }
@keyframes qnCardScan {
    0%, 100% { top: 0; opacity: 0; }
    50% { top: 100%; opacity: 1; }
}

/* ===== CODE SECTION ===== */
.qn-code-section { padding: 100px 0; background: #fff; }
.qn-code-showcase {
    display: grid; grid-template-columns: 1.2fr 1fr;
    gap: 60px; align-items: center;
}
.qn-terminal {
    background: var(--qn-darker);
    border-radius: 16px; overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    border: 1px solid rgba(16,185,129,0.2);
    position: relative;
}
.qn-terminal::before {
    content: ''; position: absolute; inset: 0;
    background: repeating-linear-gradient(
        0deg,
        rgba(16,185,129,0.02) 0px,
        rgba(16,185,129,0.02) 1px,
        transparent 1px,
        transparent 3px
    );
    pointer-events: none;
}
.qn-terminal-header {
    background: #1E293B; padding: 12px 16px;
    display: flex; align-items: center; gap: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.qn-terminal-dots { display: flex; gap: 8px; }
.qn-terminal-dots span {
    width: 12px; height: 12px; border-radius: 50%;
}
.qn-terminal-dots span:nth-child(1) { background: #FF5F56; }
.qn-terminal-dots span:nth-child(2) { background: #FFBD2E; }
.qn-terminal-dots span:nth-child(3) { background: #27C93F; }
.qn-terminal-title {
    color: #94A3B8; font-family: var(--qn-font-mono);
    font-size: 0.82rem; flex: 1;
}
.qn-terminal-status {
    color: var(--qn-primary);
    font-family: var(--qn-font-mono);
    font-size: 0.72rem; letter-spacing: 1px;
    animation: qnBlink 2s infinite;
}

.qn-terminal-body {
    padding: 24px; overflow-x: auto;
    max-height: 500px;
}
.qn-terminal-body pre {
    margin: 0; font-family: var(--qn-font-mono);
    font-size: 0.88rem; line-height: 1.7;
}
.qn-terminal-body code { color: #E2E8F0; }
.qn-code-kw { color: #C084FC; }
.qn-code-var { color: #60A5FA; }
.qn-code-fn { color: #34D399; }
.qn-code-str { color: #FCD34D; }
.qn-code-cmt { color: #6B7280; font-style: italic; }
.qn-code-cls { color: #F472B6; }
.qn-code-cursor {
    display: inline-block;
    color: var(--qn-primary);
    animation: qnBlink 1s infinite;
}

.qn-code-info h2 {
    font-size: 2rem; font-weight: 800;
    margin-bottom: 16px; color: var(--qn-dark);
    font-family: var(--qn-font-display);
}
.qn-code-info p {
    font-size: 1rem; color: var(--qn-gray-600);
    line-height: 1.7; margin-bottom: 24px;
}
.qn-code-features {
    list-style: none; padding: 0; margin: 0;
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
}
.qn-code-features li {
    display: flex; align-items: center; gap: 10px;
    font-size: 0.9rem; color: var(--qn-gray-700);
    font-weight: 600;
}
.qn-code-features i {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(16,185,129,0.1);
    color: var(--qn-primary);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
}

/* ===== KARIR SECTION ===== */
.qn-karir { padding: 100px 0; background: var(--qn-gray-50); }
.qn-karir-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
}
.qn-karir-card {
    background: #fff; border-radius: 20px;
    padding: 28px; border: 1px solid var(--qn-gray-200);
    transition: all 0.3s; position: relative;
    overflow: hidden;
}
.qn-karir-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(16,185,129,0.15);
    border-color: var(--qn-primary);
}
.qn-karir-header {
    display: flex; justify-content: space-between;
    align-items: flex-start; margin-bottom: 16px;
}
.qn-karir-icon {
    width: 56px; height: 56px; border-radius: 14px;
    background: var(--qn-holo-gradient);
    background-size: 200% 200%;
    animation: qnHoloShift 3s ease infinite;
    color: #fff; display: flex;
    align-items: center; justify-content: center;
    font-size: 1.4rem;
    box-shadow: 0 8px 20px rgba(16,185,129,0.3);
}
.qn-karir-badge {
    padding: 4px 10px; border-radius: 20px;
    font-family: var(--qn-font-mono);
    font-size: 0.68rem; font-weight: 800;
    letter-spacing: 1px;
}
.qn-karir-badge.high {
    background: rgba(239,68,68,0.1);
    color: #DC2626; border: 1px solid rgba(239,68,68,0.3);
}
.qn-karir-badge.medium {
    background: rgba(245,158,11,0.1);
    color: #D97706; border: 1px solid rgba(245,158,11,0.3);
}
.qn-karir-card h3 {
    font-size: 1.15rem; font-weight: 800;
    margin: 0 0 16px; color: var(--qn-dark);
    font-family: var(--qn-font-display);
}
.qn-karir-salary { margin-bottom: 16px; }
.qn-salary-label {
    font-family: var(--qn-font-mono);
    font-size: 0.7rem; color: var(--qn-gray-600);
    display: block; margin-bottom: 4px;
    letter-spacing: 1px;
}
.qn-salary-value {
    font-family: var(--qn-font-display);
    font-size: 1.1rem; font-weight: 800;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: qnHoloShift 4s linear infinite;
}
.qn-karir-bar {
    position: relative; width: 100%; height: 10px;
    border-radius: 5px; overflow: visible;
}
.qn-bar-bg {
    position: absolute; inset: 0;
    background: var(--qn-gray-100);
    border-radius: 5px;
}
.qn-karir-bar .qn-bar-fill {
    position: absolute; top: 0; left: 0; bottom: 0;
    width: var(--percent);
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    animation: qnHoloShift 3s linear infinite;
    border-radius: 5px;
    box-shadow: 0 0 12px rgba(16,185,129,0.5);
}
.qn-bar-marker {
    position: absolute; top: -4px;
    width: 4px; height: 18px;
    background: var(--qn-primary);
    border-radius: 2px;
    transform: translateX(-50%);
    box-shadow: 0 0 8px var(--qn-primary);
}

/* ===== TESTIMONIALS ===== */
.qn-testimonials { padding: 100px 0; background: #fff; }
.qn-testimonial-carousel { position: relative; overflow: hidden; }
.qn-carousel-stage { overflow: hidden; border-radius: 24px; }
.qn-carousel-track {
    display: flex;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.qn-testimonial-card {
    min-width: 100%; padding: 12px;
}
.qn-testi-hologram {
    padding: 40px;
    background: var(--qn-gray-50);
    border-radius: 20px;
    border: 1px solid var(--qn-gray-200);
    text-align: center;
    position: relative; overflow: hidden;
}
.qn-testi-hologram::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 4px;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    animation: qnHoloShift 3s linear infinite;
}
.qn-quote-mark {
    font-size: 3rem;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: qnHoloShift 4s linear infinite;
    margin-bottom: 20px;
}
.qn-quote-text {
    font-size: 1.15rem; line-height: 1.7;
    color: var(--qn-gray-700); margin-bottom: 30px;
    font-style: italic; max-width: 700px;
    margin-left: auto; margin-right: auto;
}
.qn-testi-author {
    display: flex; align-items: center; justify-content: center;
    gap: 16px; flex-wrap: wrap;
}
.qn-author-avatar {
    position: relative; width: 60px; height: 60px;
    border-radius: 50%;
}
.qn-author-avatar img {
    width: 100%; height: 100%; border-radius: 50%;
    object-fit: cover; position: relative; z-index: 1;
}
.qn-avatar-ring {
    position: absolute; inset: -4px; border-radius: 50%;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    animation: qnHoloShift 3s linear infinite, qnRingSpin 8s linear infinite;
    padding: 2px;
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
}
.qn-author-info h4 {
    font-size: 1rem; font-weight: 800;
    margin: 0; color: var(--qn-dark);
    font-family: var(--qn-font-display);
}
.qn-author-info span {
    font-size: 0.85rem; color: var(--qn-gray-600);
}
.qn-author-id {
    font-family: var(--qn-font-mono);
    font-size: 0.72rem; color: var(--qn-gray-600);
    letter-spacing: 1px;
    padding: 4px 10px; background: var(--qn-gray-100);
    border-radius: 20px;
}

.qn-carousel-controls {
    display: flex; align-items: center; justify-content: center;
    gap: 20px; margin-top: 32px;
}
.qn-carousel-btn {
    width: 48px; height: 48px; border-radius: 50%;
    background: #fff; border: 2px solid var(--qn-gray-200);
    color: var(--qn-gray-700); cursor: pointer;
    transition: all 0.3s;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
}
.qn-carousel-btn:hover {
    background: var(--qn-holo-gradient);
    background-size: 200% 200%;
    animation: qnHoloShift 3s ease infinite;
    color: #fff; border-color: transparent;
    transform: scale(1.1);
}
.qn-carousel-dots { display: flex; gap: 8px; }
.qn-carousel-dots .dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: var(--qn-gray-200);
    cursor: pointer; transition: all 0.3s;
}
.qn-carousel-dots .dot.active {
    width: 30px; border-radius: 5px;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    animation: qnHoloShift 3s linear infinite;
}

/* ===== NEWS SECTION ===== */
.qn-news { padding: 100px 0; background: var(--qn-gray-50); }
.qn-news-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
}
.qn-news-card.featured { grid-column: span 2; }
.qn-news-hologram {
    background: #fff; border-radius: 20px;
    overflow: hidden; border: 1px solid var(--qn-gray-200);
    transition: all 0.3s; height: 100%;
    display: flex; flex-direction: column;
}
.qn-news-card:hover .qn-news-hologram {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(16,185,129,0.15);
    border-color: var(--qn-primary);
}
.qn-news-image {
    position: relative; height: 220px;
    overflow: hidden;
}
.qn-news-card.featured .qn-news-image { height: 280px; }
.qn-news-image img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.6s;
}
.qn-news-card:hover .qn-news-image img { transform: scale(1.08); }
.qn-news-category {
    position: absolute; top: 16px; left: 16px;
    padding: 4px 12px;
    background: var(--qn-holo-gradient);
    background-size: 200% 200%;
    animation: qnHoloShift 3s ease infinite;
    color: #fff; border-radius: 20px;
    font-family: var(--qn-font-mono);
    font-size: 0.7rem; font-weight: 700;
    letter-spacing: 1px;
}
.qn-news-id {
    position: absolute; top: 16px; right: 16px;
    padding: 4px 10px;
    background: rgba(0,0,0,0.7); backdrop-filter: blur(10px);
    color: #fff; border-radius: 20px;
    font-family: var(--qn-font-mono);
    font-size: 0.68rem; letter-spacing: 1px;
}
.qn-news-scan {
    position: absolute; top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--qn-primary), transparent);
    animation: qnHoloScan 3s ease-in-out infinite;
}

.qn-news-content {
    padding: 24px; flex: 1;
    display: flex; flex-direction: column;
}
.qn-news-meta {
    display: flex; align-items: center; gap: 12px;
    font-size: 0.8rem; color: var(--qn-gray-600);
    margin-bottom: 12px; flex-wrap: wrap;
}
.qn-news-meta i { color: var(--qn-primary); margin-right: 4px; }
.qn-news-time { margin-left: auto; }
.qn-news-card h3 {
    font-size: 1.15rem; font-weight: 800;
    margin: 0 0 12px; color: var(--qn-dark);
    line-height: 1.4;
    font-family: var(--qn-font-display);
}
.qn-news-card p {
    font-size: 0.92rem; color: var(--qn-gray-600);
    line-height: 1.6; margin: 0 0 16px;
    flex: 1;
}
.qn-news-link {
    display: inline-flex; align-items: center; gap: 8px;
    color: var(--qn-primary); font-weight: 700;
    text-decoration: none;
    font-family: var(--qn-font-mono);
    font-size: 0.82rem; letter-spacing: 1px;
    transition: gap 0.3s;
}
.qn-news-link:hover { gap: 14px; }

.qn-news-empty {
    grid-column: 1 / -1;
    text-align: center; padding: 60px;
    background: #fff; border-radius: 20px;
}
.qn-news-empty i {
    font-size: 3rem;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: qnHoloShift 4s linear infinite;
    margin-bottom: 16px;
}

/* ===== MITRA SECTION ===== */
.qn-mitra { padding: 80px 0; background: #fff; }
.qn-mitra-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 20px;
}
.qn-mitra-node {
    background: var(--qn-gray-50);
    padding: 24px 16px; border-radius: 16px;
    text-align: center;
    border: 1px solid var(--qn-gray-200);
    transition: all 0.3s; position: relative;
    overflow: hidden;
}
.qn-mitra-node:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px rgba(16,185,129,0.15);
    border-color: var(--qn-primary);
}
.qn-node-ring {
    position: absolute; inset: 0;
    border: 2px dashed rgba(16,185,129,0.2);
    border-radius: 16px;
    opacity: 0; transition: opacity 0.3s;
    animation: qnRingSpin 15s linear infinite;
}
.qn-mitra-node:hover .qn-node-ring { opacity: 1; }
.qn-mitra-node img {
    max-height: 40px; margin-bottom: 12px;
    position: relative; z-index: 1;
}
.qn-mitra-node i {
    font-size: 2rem; margin-bottom: 12px;
    background: var(--qn-holo-2);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: qnHoloShift 4s linear infinite;
}
.qn-mitra-node span:first-of-type {
    display: block; font-size: 0.85rem;
    font-weight: 700; color: var(--qn-dark);
    margin-bottom: 6px; position: relative; z-index: 1;
}
.qn-node-status {
    display: inline-block;
    font-family: var(--qn-font-mono);
    font-size: 0.62rem;
    color: var(--qn-primary);
    letter-spacing: 1px;
    padding: 2px 8px;
    background: rgba(16,185,129,0.1);
    border-radius: 10px;
    position: relative; z-index: 1;
}

/* ===== FAQ SECTION ===== */
.qn-faq { padding: 100px 0; background: var(--qn-gray-50); }
.qn-faq-container { max-width: 800px; margin: 0 auto; }
.qn-faq-search-wrap {
    position: relative; margin-bottom: 24px;
}
.qn-faq-search-wrap > i {
    position: absolute; left: 18px; top: 50%;
    transform: translateY(-50%);
    color: var(--qn-gray-600);
}
.qn-faq-search {
    width: 100%; padding: 16px 180px 16px 48px;
    border: 2px solid var(--qn-gray-200);
    border-radius: 14px; font-size: 0.95rem;
    background: #fff; transition: all 0.3s;
    font-family: inherit;
}
.qn-faq-search:focus {
    outline: none; border-color: var(--qn-primary);
    box-shadow: 0 0 0 4px rgba(16,185,129,0.15);
}
.qn-search-status {
    position: absolute; right: 16px; top: 50%;
    transform: translateY(-50%);
    padding: 4px 10px;
    background: rgba(16,185,129,0.1);
    color: var(--qn-primary);
    border-radius: 20px;
    font-family: var(--qn-font-mono);
    font-size: 0.68rem; font-weight: 700;
    letter-spacing: 1px;
}

.qn-faq-list {
    display: flex; flex-direction: column; gap: 12px;
}
.qn-faq-item {
    background: #fff; border-radius: 14px;
    border: 1px solid var(--qn-gray-200);
    overflow: hidden; transition: all 0.3s;
    position: relative;
}
.qn-faq-item::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 4px;
    background: var(--qn-holo-2);
    background-size: 100% 300%;
    animation: qnHoloShift 3s linear infinite;
    opacity: 0; transition: opacity 0.3s;
}
.qn-faq-item:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
.qn-faq-item.active {
    border-color: var(--qn-primary);
    box-shadow: 0 12px 30px rgba(16,185,129,0.15);
}
.qn-faq-item.active::before { opacity: 1; }
.qn-faq-item.hidden { display: none; }

.qn-faq-q {
    padding: 20px 24px; cursor: pointer;
    display: grid; grid-template-columns: auto 1fr auto;
    gap: 14px; align-items: center;
    font-weight: 700; color: var(--qn-dark);
    transition: color 0.3s;
}
.qn-faq-item.active .qn-faq-q { color: var(--qn-primary); }
.qn-faq-id {
    font-family: var(--qn-font-mono);
    font-size: 0.75rem; color: var(--qn-primary);
    padding: 3px 8px;
    background: rgba(16,185,129,0.1);
    border-radius: 6px;
    letter-spacing: 1px;
}
.qn-faq-text {
    font-size: 0.95rem; line-height: 1.4;
}
.qn-faq-toggle {
    width: 24px; height: 24px;
    position: relative; flex-shrink: 0;
}
.qn-faq-toggle span {
    position: absolute; left: 50%; top: 50%;
    width: 14px; height: 2px;
    background: var(--qn-gray-600);
    transition: all 0.3s;
}
.qn-faq-toggle span:first-child { transform: translate(-50%, -50%); }
.qn-faq-toggle span:last-child { transform: translate(-50%, -50%) rotate(90deg); }
.qn-faq-item.active .qn-faq-toggle span:first-child { background: var(--qn-primary); }
.qn-faq-item.active .qn-faq-toggle span:last-child { transform: translate(-50%, -50%) rotate(0); background: var(--qn-primary); }

.qn-faq-a {
    max-height: 0; overflow: hidden;
    transition: max-height 0.4s;
}
.qn-faq-item.active .qn-faq-a { max-height: 400px; }
.qn-faq-answer {
    padding: 0 24px 20px;
    border-top: 1px dashed var(--qn-gray-200);
    padding-top: 16px;
}
.qn-answer-label {
    display: inline-block;
    padding: 3px 10px;
    background: var(--qn-holo-gradient);
    background-size: 200% 200%;
    animation: qnHoloShift 3s ease infinite;
    color: #fff; border-radius: 20px;
    font-family: var(--qn-font-mono);
    font-size: 0.68rem; font-weight: 700;
    letter-spacing: 1px; margin-bottom: 10px;
}
.qn-faq-answer p {
    font-size: 0.9rem; color: var(--qn-gray-600);
    line-height: 1.7; margin: 0;
}

/* ===== CTA SECTION ===== */
.qn-cta { padding: 100px 0; background: #fff; }
.qn-cta-card {
    background: linear-gradient(135deg, var(--qn-dark) 0%, #047857 100%);
    border-radius: 32px; padding: 60px;
    color: #fff;
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 60px; align-items: center;
    position: relative; overflow: hidden;
    box-shadow: 0 30px 70px rgba(0,0,0,0.3);
}
.qn-cta-bg { position: absolute; inset: 0; pointer-events: none; }
.qn-cta-grid {
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
    background-size: 50px 50px;
    opacity: 0.5;
}
.qn-cta-orb {
    position: absolute; border-radius: 50%;
    filter: blur(80px);
}
.qn-orb-1 {
    width: 400px; height: 400px;
    background: rgba(16,185,129,0.3);
    top: -100px; right: -100px;
    animation: qnOrbFloat 10s ease-in-out infinite;
}
.qn-orb-2 {
    width: 300px; height: 300px;
    background: rgba(139,92,246,0.3);
    bottom: -100px; left: -100px;
    animation: qnOrbFloat 12s ease-in-out infinite reverse;
}
@keyframes qnOrbFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(40px, -30px) scale(1.1); }
}

.qn-cta-content { position: relative; z-index: 1; }
.qn-cta-content h2 {
    font-size: 2rem; font-weight: 900;
    margin: 0 0 16px; line-height: 1.2;
    font-family: var(--qn-font-display);
}
.qn-cta-content > p {
    font-size: 1rem; opacity: 0.9;
    margin-bottom: 32px; line-height: 1.6;
}
.qn-contact-grid {
    display: flex; flex-direction: column; gap: 16px;
}
.qn-contact-item {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 18px;
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 12px;
    transition: all 0.3s;
}
.qn-contact-item:hover {
    background: rgba(255,255,255,0.12);
    transform: translateX(6px);
}
.qn-contact-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: var(--qn-holo-gradient);
    background-size: 200% 200%;
    animation: qnHoloShift 3s ease infinite;
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.qn-contact-label {
    display: block;
    font-family: var(--qn-font-mono);
    font-size: 0.68rem; color: rgba(255,255,255,0.6);
    letter-spacing: 1.5px; margin-bottom: 2px;
}
.qn-contact-value {
    display: block; font-size: 0.9rem;
    font-weight: 600; color: #fff;
}

/* Contact Form */
.qn-contact-form {
    position: relative; z-index: 1;
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 24px; padding: 32px;
}
.qn-form-header {
    display: flex; align-items: center; gap: 12px;
    padding-bottom: 20px; margin-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.qn-form-header i {
    font-size: 1.2rem; color: var(--qn-primary);
}
.qn-form-header h3 {
    font-size: 1.1rem; margin: 0;
    font-family: var(--qn-font-mono);
    letter-spacing: 2px; font-weight: 800;
}
.qn-form-status {
    margin-left: auto;
    font-family: var(--qn-font-mono);
    font-size: 0.68rem;
    color: var(--qn-primary);
    letter-spacing: 1px;
    animation: qnBlink 2s infinite;
}
.qn-form-group { margin-bottom: 16px; }
.qn-form-group label {
    display: block;
    font-family: var(--qn-font-mono);
    font-size: 0.7rem; color: rgba(255,255,255,0.7);
    letter-spacing: 1.5px; margin-bottom: 6px;
}
.qn-contact-form input, .qn-contact-form textarea {
    width: 100%; padding: 13px 16px;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 10px;
    background: rgba(255,255,255,0.95);
    color: var(--qn-dark);
    font-family: inherit; font-size: 0.9rem;
    outline: none; transition: all 0.3s;
}
.qn-contact-form input:focus, .qn-contact-form textarea:focus {
    border-color: var(--qn-primary);
    box-shadow: 0 0 0 4px rgba(16,185,129,0.2);
}
.qn-contact-form textarea { resize: vertical; min-height: 100px; }

/* ===== ANIMATIONS ===== */
.fade-up {
    opacity: 0; transform: translateY(40px);
    transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-up.visible { opacity: 1; transform: none; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .qn-video-showcase, .qn-about-grid,
    .qn-code-showcase, .qn-cta-card {
        grid-template-columns: 1fr;
    }
    .qn-news-card.featured { grid-column: span 1; }
    .qn-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .qn-code-features { grid-template-columns: 1fr; }
    .qn-cta-card { padding: 40px 28px; gap: 40px; }
    .qn-hero { padding: 120px 20px 80px; }
}
@media (max-width: 768px) {
    .qn-stats-grid { grid-template-columns: 1fr; }
    .qn-status-bar { flex-direction: column; gap: 8px; padding: 14px; }
    .qn-status-divider { display: none; }
    .qn-hero-title { font-size: 2.5rem; }
    .qn-sub-text { font-size: 1rem; letter-spacing: 2px; }
    .qn-sub-line { width: 30px; }
    .qn-hero-cta { flex-direction: column; align-items: stretch; }
    .qn-btn { justify-content: center; }
    .qn-floating-holograms { display: none; }
    .qn-back-top { bottom: 24px; right: 16px; width: 48px; height: 48px; }
    .qn-cookie { left: 10px; right: 10px; bottom: 10px; }
    .qn-cookie-content { padding: 16px; }
    .qn-broadcast-header { position: static; transform: none; margin-bottom: 10px; display: inline-flex; }
    .qn-broadcast-marquee { margin-left: 0; }
    .qn-title-top { flex-direction: column; gap: 6px; }
    .qn-video-features { gap: 12px; }
    .qn-float-card { display: none; }
    .qn-hologram-display { padding: 20px 10px 60px; min-height: 400px; }
    .qn-contact-form { padding: 24px; }
}
@media (max-width: 480px) {
    .qn-countdown { flex-wrap: wrap; padding: 12px; }
    .qn-cd-unit { min-width: 60px; }
    .qn-cd-value { font-size: 1.3rem; }
    .qn-event-meta { flex-direction: column; gap: 8px; }
    .qn-carousel-controls { gap: 10px; }
    .qn-carousel-btn { width: 40px; height: 40px; }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    .qn-scanlines, .qn-data-streams { display: none; }
}

/* ============================================================
   🌙 DARK MODE OVERRIDE (PATCH - gl-dark support)
   Memastikan halaman index kompatibel dengan global dark mode
============================================================ */

/* === BASE DARK MODE === */
body.gl-dark {
    background: #0F172A !important;
    color: #E2E8F0 !important;
}

/* === SECTION BACKGROUNDS === */
body.gl-dark .qn-video-section,
body.gl-dark .qn-about,
body.gl-dark .qn-expertise,
body.gl-dark .qn-code-section,
body.gl-dark .qn-karir,
body.gl-dark .qn-testimonials,
body.gl-dark .qn-news,
body.gl-dark .qn-mitra,
body.gl-dark .qn-faq,
body.gl-dark .qn-cta {
    background: #0F172A !important;
}

/* === CARDS & CONTAINERS === */
body.gl-dark .qn-video-feature,
body.gl-dark .qn-card-hologram,
body.gl-dark .qn-karir-card,
body.gl-dark .qn-news-hologram,
body.gl-dark .qn-mitra-node,
body.gl-dark .qn-faq-item,
body.gl-dark .qn-float-card,
body.gl-dark .qn-testi-hologram {
    background: #1E293B !important;
    border-color: rgba(255,255,255,0.08) !important;
}

/* === HOVER STATES (DARK) === */
body.gl-dark .qn-expertise-card:hover .qn-card-hologram,
body.gl-dark .qn-karir-card:hover,
body.gl-dark .qn-news-card:hover .qn-news-hologram,
body.gl-dark .qn-mitra-node:hover,
body.gl-dark .qn-faq-item:hover {
    background: #1E293B !important;
}

/* === TEXT COLORS (DARK) === */
body.gl-dark .qn-section-title .qn-title-word:first-child,
body.gl-dark .qn-card-hologram h3,
body.gl-dark .qn-karir-card h3,
body.gl-dark .qn-news-card h3,
body.gl-dark .qn-video-feature h4,
body.gl-dark .qn-faq-q,
body.gl-dark .qn-features-list h4,
body.gl-dark .qn-feature-content h4,
body.gl-dark .qn-code-info h2,
body.gl-dark .qn-feature-text h4,
body.gl-dark .qn-float-card h4,
body.gl-dark .qn-quote-text,
body.gl-dark .qn-author-info h4,
body.gl-dark .qn-mitra-node span:first-of-type {
    color: #F1F5F9 !important;
}

body.gl-dark .qn-section-subtitle,
body.gl-dark .qn-card-hologram p,
body.gl-dark .qn-karir-salary .qn-salary-label,
body.gl-dark .qn-news-card p,
body.gl-dark .qn-video-feature p,
body.gl-dark .qn-faq-a p,
body.gl-dark .qn-feature-text p,
body.gl-dark .qn-about-lead,
body.gl-dark .qn-card-features li,
body.gl-dark .qn-code-features li,
body.gl-dark .qn-quote-text,
body.gl-dark .qn-author-info span,
body.gl-dark .qn-event-desc,
body.gl-dark .qn-news-meta,
body.gl-dark .qn-faq-text,
body.gl-dark .qn-feature-content p {
    color: #94A3B8 !important;
}

/* === FORM ELEMENTS (DARK) === */
body.gl-dark .qn-faq-search {
    background: #1E293B !important;
    border-color: rgba(255,255,255,0.1) !important;
    color: #E2E8F0 !important;
}

body.gl-dark .qn-faq-search::placeholder {
    color: #64748B !important;
}

/* === PROGRESS BARS & UI ELEMENTS === */
body.gl-dark .qn-bar-bg {
    background: rgba(255,255,255,0.1) !important;
}

body.gl-dark .qn-stat-bar {
    background: rgba(255,255,255,0.08) !important;
}

/* === COOKIE BANNER (DARK) === */
body.gl-dark .qn-cookie-content {
    background: #1E293B !important;
    border-color: #334155 !important;
}

/* === CAROUSEL CONTROLS (DARK) === */
body.gl-dark .qn-carousel-btn {
    background: #1E293B !important;
    border-color: rgba(255,255,255,0.1) !important;
    color: #94A3B8 !important;
}

/* === EMPTY STATE (DARK) === */
body.gl-dark .qn-news-empty {
    background: #1E293B !important;
}

/* === FAQ ANSWER BORDER (DARK) === */
body.gl-dark .qn-faq-answer {
    border-top-color: rgba(255,255,255,0.1) !important;
}

/* === SEARCH STATUS BADGE (DARK) === */
body.gl-dark .qn-search-status {
    background: rgba(16,185,129,0.2) !important;
}

/* === FAQ ID BADGE (DARK) === */
body.gl-dark .qn-faq-id {
    background: rgba(16,185,129,0.2) !important;
}

/* === FAQ TOGGLE BARS (DARK) === */
body.gl-dark .qn-faq-toggle span {
    background: #94A3B8 !important;
}

/* === NEWS META (DARK) === */
body.gl-dark .qn-news-meta {
    color: #94A3B8 !important;
}

/* === MITRA NODE (DARK) === */
body.gl-dark .qn-mitra-node {
    background: #1E293B !important;
    border-color: rgba(255,255,255,0.08) !important;
}

/* === VIDEO FRAME (DARK) === */
body.gl-dark .qn-holo-frame {
    background: rgba(16,185,129,0.08) !important;
    border-color: rgba(16,185,129,0.3) !important;
}

/* === CONTACT FORM INPUTS (DARK) === */
body.gl-dark .qn-contact-form input,
body.gl-dark .qn-contact-form textarea {
    background: #1E293B !important;
    border-color: rgba(255,255,255,0.15) !important;
    color: #F1F5F9 !important;
}

body.gl-dark .qn-contact-form input::placeholder,
body.gl-dark .qn-contact-form textarea::placeholder {
    color: #64748B !important;
}

/* === CAROUSEL DOTS (DARK) === */
body.gl-dark .qn-carousel-dots .dot {
    background: rgba(255,255,255,0.2) !important;
}

/* === TECH SECTION (DARK) === */
body.gl-dark .qn-tech-section {
    background: #020617 !important;
    border-color: rgba(16,185,129,0.3) !important;
}

/* === FEATURE CARDS (DARK) === */
body.gl-dark .qn-feature-card {
    background: #1E293B !important;
    border-color: rgba(255,255,255,0.08) !important;
}

/* === TERMINAL (ALREADY DARK - NO CHANGE) === */
/* Terminal sudah gelap, tidak perlu diubah */

/* === QUOTE MARK (DARK) === */
body.gl-dark .qn-quote-mark {
    color: #10B981 !important;
    -webkit-text-fill-color: #10B981 !important;
}

/* === AUTHOR ID (DARK) === */
body.gl-dark .qn-author-id {
    background: rgba(255,255,255,0.1) !important;
    color: #94A3B8 !important;
}

/* === CTA CARD TEXT (DARK) === */
body.gl-dark .qn-cta-card {
    background: linear-gradient(135deg, #020617 0%, #047857 100%) !important;
}

html { scroll-behavior: smooth; }
::selection { background: rgba(16,185,129,0.3); color: var(--qn-dark); }

/* ============================================================
   PERFORMANCE PATCH - SMOOTH SCROLL
   Tujuan: mengurangi beban render tanpa menghapus fitur
============================================================ */

/* Anchor offset kalau ada navbar fixed */
main section {
  scroll-margin-top: 90px;
}

/* Browser tidak perlu render penuh section yang jauh dari viewport */
.qn-video-section,
.qn-about,
.qn-events,
.qn-expertise,
.qn-code-section,
.qn-karir,
.qn-testimonials,
.qn-news,
.qn-mitra,
.qn-faq,
.qn-cta {
  content-visibility: auto;
  contain-intrinsic-size: auto 900px;
}

/* Section tipis/marquee lebih aman diberi containment */
.qn-tech-section,
.qn-broadcast-strip {
  contain: layout paint style;
}

/* Elemen dekoratif tidak boleh menangkap pointer */
.qn-scanlines,
.qn-holo-grid,
.qn-data-streams,
.qn-floating-holograms,
.qn-tunnel,
.qn-scroll-progress,
.qn-scroll-glow,
.qn-holo-scan,
.qn-scroll-indicator {
  pointer-events: none;
}

/* Scanline full-screen sering jadi penyebab utama scroll berat */
.qn-scanlines {
  opacity: .16;
  mix-blend-mode: normal;
}

/* Blur trail cursor cukup mahal */
.qn-cursor-trail {
  filter: none;
}

/* Optimasi elemen yang sering bergerak */
.qn-marquee-track,
.qn-broadcast-track,
.qn-carousel-track {
  will-change: transform;
  backface-visibility: hidden;
}

/* Fade-up pakai transform/opacity, bukan top/left */
.fade-up {
  backface-visibility: hidden;
}

.fade-up.visible {
  will-change: auto;
}

/* Saat user sedang scroll, pause animasi dekoratif yang berat */
body.is-scrolling .qn-scanlines,
body.is-scrolling .qn-data-streams,
body.is-scrolling .qn-stream,
body.is-scrolling .qn-tunnel-ring,
body.is-scrolling .qn-holo-shape,
body.is-scrolling .qn-holo-scan,
body.is-scrolling .qn-marquee-track,
body.is-scrolling .qn-broadcast-track,
body.is-scrolling .qn-scroll-glow,
body.is-scrolling .qn-event-scan,
body.is-scrolling .qn-card-scan,
body.is-scrolling .qn-news-scan {
  animation-play-state: paused !important;
}

/* Pause animasi CSS untuk section yang sedang tidak terlihat */
.qn-offscreen * {
  animation-play-state: paused !important;
}

/* Mobile / touch device: kurangi efek yang paling mahal */
@media (max-width: 1024px), (hover: none), (pointer: coarse) {
  .qn-scanlines,
  .qn-cursor,
  .qn-cursor-ring,
  .qn-cursor-trail {
    display: none !important;
  }

  .qn-status-bar,
  .qn-stats-hud,
  .qn-event-hologram,
  .qn-contact-form,
  .qn-contact-item,
  .qn-video-overlay {
    backdrop-filter: none !important;
  }

  .qn-holo-frame,
  .qn-news-hologram,
  .qn-card-hologram,
  .qn-karir-card,
  .qn-feature-card,
  .qn-testi-hologram {
    box-shadow: 0 10px 24px rgba(0, 0, 0, .12) !important;
  }
}
</style>

<!-- ============================================================
   QUANTUM NEXUS SCRIPTS (OPTIMIZED v1.1 - MAX SMOOTHNESS)
============================================================ -->
<script>
(function() {
    'use strict';

/* ===== 0. UTILITIES: Frame Limiter & Debounce (OPTIMIZED) ===== */
// Membatasi render canvas & MENGHENTIKAN TOTAL saat hero tidak terlihat
window.__qnHeroVisible = true; // Status default

function createFrameLimiter(callback, fps = 30) {
    let lastTime = 0;
    let rafId = null;
    let running = false;
    const interval = 1000 / fps;

    function loop(currentTime) {
        if (!running) return;
        
        rafId = requestAnimationFrame(loop);
        
        // Stop render jika tab di-background ATAU hero sudah di-scroll keluar layar
        if (document.hidden || window.__qnHeroVisible === false) {
            return; 
        }
        
        const elapsed = currentTime - lastTime;
        if (elapsed < interval) return;
        
        lastTime = currentTime - (elapsed % interval);
        callback(currentTime);
    }

    function start() {
        if (!running) {
            running = true;
            rafId = requestAnimationFrame(loop);
        }
    }

    function stop() {
        running = false;
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
    }

    // Listener untuk event visibility dari IntersectionObserver
    window.addEventListener('qn:hero-visibility', function(e) {
        window.__qnHeroVisible = Boolean(e.detail);
        if (window.__qnHeroVisible && !document.hidden) {
            start();
        } else {
            stop();
        }
    });

    // Listener untuk tab switch (berhenti jika user pindah tab)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stop();
        } else if (window.__qnHeroVisible) {
            start();
        }
    });

    start(); // Jalankan saat inisialisasi

    // Return fungsi kosong karena loop sudah dijalankan oleh start() di atas.
    // Ini mencegah double-loop karena pemanggilan aslinya: requestAnimationFrame(createFrameLimiter(...))
    return function() {}; 
}

    // Debounce untuk resize dan search
    let resizeTimeout, searchTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => window.dispatchEvent(new Event('optimized-resize')), 150);
    }, { passive: true });

    /* ===== 1. Quantum Particle Canvas ===== */
    const canvas = document.getElementById('qnQuantumCanvas');
    if (canvas) {
        // { alpha: true } memberi hint ke browser untuk optimasi blending
        const ctx = canvas.getContext('2d', { alpha: true }); 
        let width, height, particles = [];
        let mouseX = -1000, mouseY = -1000; // Mulai di luar layar
        
        function resize() {
            width = canvas.width = canvas.offsetWidth;
            height = canvas.height = canvas.offsetHeight;
        }
        resize();
        window.addEventListener('optimized-resize', resize);
        
        canvas.parentElement.addEventListener('mousemove', e => {
            const rect = canvas.getBoundingClientRect();
            mouseX = e.clientX - rect.left;
            mouseY = e.clientY - rect.top;
        }, { passive: true });

        canvas.parentElement.addEventListener('mouseleave', () => {
            mouseX = -1000; mouseY = -1000;
        }, { passive: true });

        class QuantumParticle {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.size = Math.random() * 2 + 0.5; // Sedikit lebih kecil untuk performa
                this.speedX = (Math.random() - 0.5) * 0.4; // Gerakan lebih lambat & smooth
                this.speedY = (Math.random() - 0.5) * 0.4;
                const colors = ['#10B981', '#06B6D4', '#8B5CF6', '#EC4899', '#F59E0B'];
                this.color = colors[Math.floor(Math.random() * colors.length)];
                this.phase = Math.random() * Math.PI * 2;
            }
            update() {
                if (mouseX !== -1000) {
                    const dx = mouseX - this.x;
                    const dy = mouseY - this.y;
                    const distSq = dx * dx + dy * dy; // Optimasi: hindari Math.sqrt di sini
                    if (distSq < 22500) { // 150 * 150
                        const dist = Math.sqrt(distSq);
                        this.x += (dx / dist) * 0.6;
                        this.y += (dy / dist) * 0.6;
                    }
                }
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.x < 0 || this.x > width) this.speedX *= -1;
                if (this.y < 0 || this.y > height) this.speedY *= -1;
                this.phase += 0.015;
            }
            draw() {
                const pulse = Math.sin(this.phase) * 0.3 + 0.7;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size * pulse, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.globalAlpha = 0.5;
                ctx.fill();
                ctx.globalAlpha = 1;
            }
        }

        // Kurangi partikel sedikit untuk gain performa besar di device mid-tier
        const numParticles = window.innerWidth < 768 ? 30 : 50; 
        for (let i = 0; i < numParticles; i++) particles.push(new QuantumParticle());

        function connect() {
            for (let a = 0; a < particles.length; a++) {
                for (let b = a + 1; b < particles.length; b++) {
                    const dx = particles[a].x - particles[b].x;
                    const dy = particles[a].y - particles[b].y;
                    const distSq = dx * dx + dy * dy;
                    
                    if (distSq < 16900) { // 130 * 130 (Optimasi jarak kuadrat)
                        const distance = Math.sqrt(distSq);
                        const opacity = (1 - distance / 130) * 0.25;
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(16, 185, 129, ${opacity})`;
                        ctx.lineWidth = 0.6;
                        ctx.moveTo(particles[a].x, particles[a].y);
                        ctx.lineTo(particles[b].x, particles[b].y);
                        ctx.stroke();
                    }
                }
            }
        }

        // Jalankan dengan limiter 30 FPS
        requestAnimationFrame(createFrameLimiter(() => {
            ctx.clearRect(0, 0, width, height);
            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
                particles[i].draw();
            }
            connect();
        }, 30));
    }

    /* ===== 2. Data Canvas (Matrix Rain Style) ===== */
    const dataCanvas = document.getElementById('qnDataCanvas');
    if (dataCanvas) {
        const ctx = dataCanvas.getContext('2d', { alpha: true });
        let width, height, columns, drops;
        const chars = '01{}[]()=+-*&^%$#@!ABCDEF';
        const fontSize = 14;
        
        function resize() {
            width = dataCanvas.width = dataCanvas.offsetWidth;
            height = dataCanvas.height = dataCanvas.offsetHeight;
            columns = Math.floor(width / fontSize);
            drops = Array(columns).fill(1);
        }
        resize();
        window.addEventListener('optimized-resize', resize);
        
        const colors = ['#10B981', '#06B6D4', '#8B5CF6'];
        
        // 24 FPS sudah sangat cukup untuk efek matrix (terlihat cinematic & ringan)
        requestAnimationFrame(createFrameLimiter(() => {
            ctx.fillStyle = 'rgba(2, 6, 23, 0.08)';
            ctx.fillRect(0, 0, width, height);
            ctx.font = fontSize + 'px monospace';
            
            for (let i = 0; i < columns; i++) {
                const text = chars[Math.floor(Math.random() * chars.length)];
                const x = i * fontSize;
                const y = drops[i] * fontSize;
                
                ctx.fillStyle = colors[Math.floor(Math.random() * colors.length)];
                ctx.fillText(text, x, y);
                
                if (y > height && Math.random() > 0.975) drops[i] = 0;
                drops[i]++;
            }
        }, 24));
    }

    /* ===== 3. Holographic Cursor (GPU Accelerated) ===== */
    if (matchMedia('(hover: hover)').matches && !window.matchMedia('(pointer: coarse)').matches) {
        const cursor = document.getElementById('qnCursor');
        const ring = document.getElementById('qnCursorRing');
        const trail = document.getElementById('qnCursorTrail');
        let mx = 0, my = 0, rx = 0, ry = 0, tx = 0, ty = 0;
        let isAnimating = false;

        document.addEventListener('mousemove', e => {
            mx = e.clientX; my = e.clientY;
            if (cursor) cursor.style.transform = `translate(${mx}px, ${my}px)`; // Pakai transform, bukan left/top
            
            if (!isAnimating) {
                isAnimating = true;
                requestAnimationFrame(animateCursor);
            }
        }, { passive: true });

        function animateCursor() {
            isAnimating = false;
            if (ring) {
                rx += (mx - rx) * 0.15;
                ry += (my - ry) * 0.15;
                ring.style.transform = `translate(${rx}px, ${ry}px)`;
            }
            if (trail) {
                tx += (mx - tx) * 0.08;
                ty += (my - ty) * 0.08;
                trail.style.transform = `translate(${tx}px, ${ty}px)`;
            }
            requestAnimationFrame(animateCursor);
        }
        animateCursor();
        
        const hoverTargets = document.querySelectorAll('a, button, input, textarea, .qn-expertise-card, .qn-news-card, .qn-event-card, .qn-testimonial-card, .qn-mitra-node, .qn-karir-card, .qn-faq-q');
        hoverTargets.forEach(el => {
            el.addEventListener('mouseenter', () => ring && ring.classList.add('hover'), { passive: true });
            el.addEventListener('mouseleave', () => ring && ring.classList.remove('hover'), { passive: true });
        });
    }

    /* ===== 4. Scroll Progress & Back to Top (Throttled) ===== */
    let scrollTicking = false;
    const scrollFill = document.getElementById('qnScrollFill');
    const backTop = document.getElementById('qnBackTop');
    const ringFill = document.getElementById('qnRingFill');

    window.addEventListener('scroll', () => {
        if (!scrollTicking) {
            window.requestAnimationFrame(() => {
                const scrolled = window.scrollY;
                const max = document.documentElement.scrollHeight - window.innerHeight;
                const percent = max > 0 ? (scrolled / max) * 100 : 0;
                
                if (scrollFill) scrollFill.style.width = percent + '%';
                if (backTop) scrolled > 400 ? backTop.classList.add('show') : backTop.classList.remove('show');
                if (ringFill) ringFill.style.strokeDashoffset = 94.25 - ((scrolled / max) * 94.25);
                
                scrollTicking = false;
            });
            scrollTicking = true;
        }
    }, { passive: true });

    if (backTop) backTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }), { passive: true });

    /* ===== 5. Cookie Consent ===== */
    const cookie = document.getElementById('qnCookie');
    if (cookie && !localStorage.getItem('qn_cookie_consent')) {
        setTimeout(() => cookie.classList.add('show'), 2500);
    }
    document.getElementById('qnCookieAccept')?.addEventListener('click', () => {
        localStorage.setItem('qn_cookie_consent', 'accepted');
        cookie.classList.remove('show');
    }, { passive: true });
    document.getElementById('qnCookieDecline')?.addEventListener('click', () => {
        localStorage.setItem('qn_cookie_consent', 'declined');
        cookie.classList.remove('show');
    }, { passive: true });

    /* ===== 6. Video Player ===== */
    const video = document.getElementById('campusVideo');
    if (video) {
        const overlay = document.getElementById('videoOverlay');
        const controls = document.getElementById('videoControls');
        const playBtn = document.getElementById('videoPlayBtn');
        const pauseBtn = document.getElementById('videoPauseBtn');
        const progressBar = document.getElementById('videoProgressBar');
        const timeDisplay = document.getElementById('videoTime');
        const fullscreenBtn = document.getElementById('videoFullscreenBtn');

        playBtn?.addEventListener('click', () => {
            video.play();
            overlay.classList.add('hidden');
            controls.style.display = 'flex';
        }, { passive: true });
        
        pauseBtn?.addEventListener('click', () => {
            if (video.paused) { video.play(); pauseBtn.innerHTML = '<i class="fas fa-pause"></i>'; } 
            else { video.pause(); pauseBtn.innerHTML = '<i class="fas fa-play"></i>'; }
        }, { passive: true });
        
        video.addEventListener('timeupdate', () => {
            if (progressBar) progressBar.style.width = (video.currentTime / video.duration * 100) + '%';
            if (timeDisplay) {
                const ft = t => `${Math.floor(t/60)}:${String(Math.floor(t%60)).padStart(2,'0')}`;
                timeDisplay.textContent = `${ft(video.currentTime)} / ${ft(video.duration)}`;
            }
        }, { passive: true });
        
        fullscreenBtn?.addEventListener('click', () => {
            if (video.requestFullscreen) video.requestFullscreen();
        }, { passive: true });
    }

    /* ===== 7. Unified Countdown Timer (Single Interval) ===== */
    const countdownCards = document.querySelectorAll('.qn-event-card');
    if (countdownCards.length > 0) {
        function updateCountdowns() {
            const now = Date.now();
            countdownCards.forEach(card => {
                const distance = (parseInt(card.dataset.eventTime, 10) * 1000) - now;
                if (distance > 0) {
                    const d = card.querySelector('[data-days]');
                    const h = card.querySelector('[data-hours]');
                    const m = card.querySelector('[data-minutes]');
                    const s = card.querySelector('[data-seconds]');
                    if (d) d.textContent = String(Math.floor(distance / 86400000)).padStart(2, '0');
                    if (h) h.textContent = String(Math.floor((distance % 86400000) / 3600000)).padStart(2, '0');
                    if (m) m.textContent = String(Math.floor((distance % 3600000) / 60000)).padStart(2, '0');
                    if (s) s.textContent = String(Math.floor((distance % 60000) / 1000)).padStart(2, '0');
                }
            });
        }
        updateCountdowns();
        setInterval(updateCountdowns, 1000);
    }

    /* ===== 8. Testimonial Slider ===== */
    const track = document.getElementById('testimonialTrack');
    if (track) {
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const dotsContainer = document.getElementById('sliderDots');
        const slides = track.children;
        let current = 0, autoPlay;

        for (let i = 0; i < slides.length; i++) {
            const dot = document.createElement('div');
            dot.className = 'dot' + (i === 0 ? ' active' : '');
            dot.addEventListener('click', () => goTo(i), { passive: true });
            dotsContainer.appendChild(dot);
        }
        const dots = dotsContainer.children;

        function update() {
            track.style.transform = `translateX(-${current * 100}%)`;
            Array.from(dots).forEach((d, i) => d.classList.toggle('active', i === current));
        }
        function goTo(i) {
            current = i;
            if (current >= slides.length) current = 0;
            if (current < 0) current = slides.length - 1;
            update();
            resetAuto();
        }
        nextBtn?.addEventListener('click', () => goTo(current + 1), { passive: true });
        prevBtn?.addEventListener('click', () => goTo(current - 1), { passive: true });
        
        function startAuto() { autoPlay = setInterval(() => goTo(current + 1), 6000); } // Naikkan ke 6s agar lebih nyaman dibaca
        function resetAuto() { clearInterval(autoPlay); startAuto(); }
        
        track.parentElement.addEventListener('mouseenter', () => clearInterval(autoPlay), { passive: true });
        track.parentElement.addEventListener('mouseleave', startAuto, { passive: true });
        startAuto();
    }

    /* ===== 9. FAQ Accordion & Search (Debounced) ===== */
    const faqItems = document.querySelectorAll('.qn-faq-item');
    faqItems.forEach(item => {
        item.querySelector('.qn-faq-q').addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            faqItems.forEach(i => i.classList.remove('active')); // Hapus querySelectorAll di dalam loop
            if (!isActive) item.classList.add('active');
        }, { passive: true });
    });

    const faqSearch = document.getElementById('faqSearch');
    if (faqSearch) {
        faqSearch.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const query = e.target.value.toLowerCase();
                faqItems.forEach(item => {
                    const q = item.getAttribute('data-question') || '';
                    const isHidden = query.length > 0 && !q.includes(query);
                    item.classList.toggle('hidden', isHidden);
                    if (isHidden) item.classList.remove('active');
                });
            }, 150); // Debounce 150ms mencegah lag saat mengetik
        }, { passive: true });
    }

    /* ===== 10. Form Validation ===== */
    const form = document.getElementById('hpContactForm');
    if (form) {
        const validators = {
            name: v => v.trim().length >= 3,
            email: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),
            subject: v => v.trim().length >= 3,
            message: v => v.trim().length >= 10
        };
        form.addEventListener('submit', e => {
            let valid = true, firstInvalid = null;
            form.querySelectorAll('[data-validate][required]').forEach(field => {
                if (!validators[field.dataset.validate](field.value)) {
                    valid = false;
                    field.style.borderColor = '#EF4444';
                    if (!firstInvalid) firstInvalid = field;
                } else {
                    field.style.borderColor = '';
                }
            });
            if (!valid) {
                e.preventDefault();
                if (firstInvalid) firstInvalid.focus();
            }
        });
    }

    /* ===== 11. Typewriter Effect ===== */
    const typewriterEl = document.getElementById('qnTypewriter');
    if (typewriterEl) {
        const words = ['DIGITAL ANDA', 'TEKNOLOGI', 'MASA DEPAN', 'KARIR IMPIAN', 'INOVASI'];
        let wordIdx = 0, charIdx = 0, isDeleting = false;
        const type = () => {
            const word = words[wordIdx];
            if (isDeleting) {
                typewriterEl.textContent = word.substring(0, charIdx - 1);
                charIdx--;
            } else {
                typewriterEl.textContent = word.substring(0, charIdx + 1);
                charIdx++;
            }
            let speed = isDeleting ? 50 : 100;
            if (!isDeleting && charIdx === word.length) { speed = 2000; isDeleting = true; }
            else if (isDeleting && charIdx === 0) {
                isDeleting = false;
                wordIdx = (wordIdx + 1) % words.length;
                speed = 500;
            }
            setTimeout(type, speed);
        };
        setTimeout(type, 1000);
    }

    /* ===== 12. Stats Counter ===== */
    const counters = document.querySelectorAll('.qn-stat-value');
    if (counters.length > 0) {
        const counterObs = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = +el.dataset.target;
                    const duration = 2000;
                    const startTime = performance.now();
                    const easeOutQuart = t => 1 - Math.pow(1 - t, 4);
                    
                    const update = (now) => {
                        const progress = Math.min((now - startTime) / duration, 1);
                        el.textContent = Math.floor(target * easeOutQuart(progress)).toLocaleString('id-ID');
                        if (progress < 1) requestAnimationFrame(update);
                        else el.textContent = target.toLocaleString('id-ID');
                    };
                    requestAnimationFrame(update);
                    counterObs.unobserve(el);
                }
            });
        }, { threshold: 0.5, rootMargin: '0px 0px -50px 0px' });
        counters.forEach(c => counterObs.observe(c));
    }

    /* ===== 13. Scroll Reveal ===== */
    const revealObs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('.fade-up').forEach(el => revealObs.observe(el));

    /* ===== 14. Smooth Scroll ===== */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', e => {
            const href = anchor.getAttribute('href');
            if (href.length > 1 && href !== '#') {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });
                }
            }
        }, { passive: true });
    });

    /* ===== 15. Console Easter Egg ===== */
    console.log('%c╔══════════════════════════════════════════════╗', 'color:#10B981;font-weight:bold');
    console.log('%c║   QUANTUM NEXUS - Holographic Interface v1.1  ║', 'color:#10B981;font-weight:bold;font-size:13px');
    console.log('%c║   🚀 Optimized for Maximum Smoothness (60fps) ║', 'color:#06B6D4;font-weight:bold;font-size:11px');
    console.log('%c╚══════════════════════════════════════════════╝', 'color:#10B981;font-weight:bold');
    console.log('%c💚 Crafted with love for Komandan', 'color:#64748b;font-size:11px');

/* ============================================================
   PERFORMANCE PATCH - SCROLL & VIEWPORT CONTROL
============================================================ */
let qnScrollTimer;

const qnMarkScrolling = () => {
  document.body.classList.add('is-scrolling');

  clearTimeout(qnScrollTimer);
  qnScrollTimer = setTimeout(() => {
    document.body.classList.remove('is-scrolling');
  }, 180);
};

window.addEventListener('scroll', qnMarkScrolling, { passive: true });
qnMarkScrolling();

if ('IntersectionObserver' in window) {
  /*
    Pause animasi CSS untuk section yang jauh dari viewport.
    rootMargin 300px agar animasi sudah aktif sebelum section masuk layar.
  */
  const qnSectionObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      entry.target.classList.toggle('qn-offscreen', !entry.isIntersecting);
    });
  }, {
    rootMargin: '300px 0px',
    threshold: 0
  });

  document.querySelectorAll('main section').forEach(section => {
    qnSectionObserver.observe(section);
  });

  /*
    Kontrol visibilitas hero.
    Akan dipakai untuk menghentikan canvas hero saat user sudah scroll jauh.
  */
  const heroSection = document.querySelector('.qn-hero');

  if (heroSection) {
    new IntersectionObserver((entries) => {
      const entry = entries[0];
      const heroVisible = entry ? entry.isIntersecting : true;

      window.__qnHeroVisible = heroVisible;

      window.dispatchEvent(new CustomEvent('qn:hero-visibility', {
        detail: heroVisible
      }));
    }, {
      threshold: 0.01
    }).observe(heroSection);
  }
}

})();
</script>
<?php require_once 'includes/footer.php'; ?>