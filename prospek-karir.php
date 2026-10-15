<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Prospek Karir Lulusan';
$pageDesc = 'Berbagai peluang karir menjanjikan di bidang teknologi untuk lulusan Program Studi Informatika.';
?>

<!-- ===== READING PROGRESS (Fuel Gauge) ===== -->
<div class="co-fuel-gauge">
    <div class="co-fuel-fill" id="coFuelFill"></div>
    <div class="co-fuel-rocket">🚀</div>
</div>

<!-- ===== HERO: LAUNCH SEQUENCE ===== -->
<section class="co-hero">
    <canvas id="coStarCanvas" class="co-star-canvas"></canvas>
    <div class="co-hero-bg">
        <div class="co-nebula co-neb-1"></div>
        <div class="co-nebula co-neb-2"></div>
        <div class="co-nebula co-neb-3"></div>
        <div class="co-orbit-paths"></div>
        <div class="co-shooting-stars"></div>
    </div>
    
    <!-- Floating Space Elements -->
    <div class="co-floating-elements">
        <span class="co-float-el" style="--delay: 0s; --x: 8%; --y: 20%;">🚀</span>
        <span class="co-float-el" style="--delay: 1.5s; --x: 88%; --y: 25%;">🌟</span>
        <span class="co-float-el" style="--delay: 3s; --x: 12%; --y: 72%;">🛸</span>
        <span class="co-float-el" style="--delay: 0.8s; --x: 85%; --y: 68%;">🌍</span>
        <span class="co-float-el" style="--delay: 2.5s; --x: 50%; --y: 15%;">🛰️</span>
        <span class="co-float-el" style="--delay: 4s; --x: 45%; --y: 85%;">👨‍🚀</span>
        <span class="co-float-el" style="--delay: 1.8s; --x: 92%; --y: 48%;">🌙</span>
        <span class="co-float-el" style="--delay: 3.5s; --x: 5%; --y: 50%;">⭐</span>
    </div>
    
    <div class="container co-hero-content">
        <!-- Mission Badge -->
        <span class="co-mission-badge fade-up">
            <span class="co-badge-glow"></span>
            <span class="co-mission-id">MISSION: ODYSSEY-2026</span>
            <i class="fas fa-rocket"></i>
        </span>
        
        <h1 class="co-hero-title fade-up">
            <span class="co-title-line">Prospek</span>
            <span class="co-title-line co-title-odyssey" data-text="Karir">Karir</span>
            <span class="co-title-sub">Lulusan</span>
        </h1>
        
        <p class="co-hero-subtitle fade-up">
            <span id="coTypingText"></span><span class="co-cursor">|</span>
        </p>
        
        <!-- Launch Countdown -->
        <div class="co-launch-sequence fade-up">
            <div class="co-launch-panel">
                <div class="co-launch-header">
                    <span class="co-launch-dot"></span>
                    <span>LAUNCH SEQUENCE</span>
                </div>
                <div class="co-countdown">
                    <div class="co-cd-unit">
                        <span id="coCdDays">00</span>
                        <small>DAYS</small>
                    </div>
                    <span class="co-cd-sep">:</span>
                    <div class="co-cd-unit">
                        <span id="coCdHours">00</span>
                        <small>HRS</small>
                    </div>
                    <span class="co-cd-sep">:</span>
                    <div class="co-cd-unit">
                        <span id="coCdMins">00</span>
                        <small>MIN</small>
                    </div>
                    <span class="co-cd-sep">:</span>
                    <div class="co-cd-unit">
                        <span id="coCdSecs">00</span>
                        <small>SEC</small>
                    </div>
                </div>
                <div class="co-launch-status">
                    <span class="co-status-indicator">
                        <span class="co-status-dot"></span>
                        SYSTEMS NOMINAL
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Mission Stats -->
        <div class="co-mission-stats fade-up">
            <div class="co-mstat">
                <div class="co-mstat-ring" style="--percent: 92%;">
                    <svg viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45"></circle>
                        <circle cx="50" cy="50" r="45" class="co-ring-fill"></circle>
                    </svg>
                    <span class="co-mstat-value">92%</span>
                </div>
                <span class="co-mstat-label">Employment Rate</span>
            </div>
            
            <div class="co-mstat">
                <div class="co-mstat-icon">
                    <i class="fas fa-building"></i>
                    <span class="co-icon-pulse"></span>
                </div>
                <div class="co-mstat-info">
                    <span class="co-mstat-num" data-count="150">0</span>
                    <span class="co-mstat-label">Partner Companies</span>
                </div>
            </div>
            
            <div class="co-mstat">
                <div class="co-mstat-icon co-mstat-blue">
                    <i class="fas fa-globe"></i>
                    <span class="co-icon-pulse"></span>
                </div>
                <div class="co-mstat-info">
                    <span class="co-mstat-num" data-count="15">0</span>
                    <span class="co-mstat-label">Countries Reached</span>
                </div>
            </div>
            
            <div class="co-mstat">
                <div class="co-mstat-icon co-mstat-gold">
                    <i class="fas fa-trophy"></i>
                    <span class="co-icon-pulse"></span>
                </div>
                <div class="co-mstat-info">
                    <span class="co-mstat-num" data-count="500">0</span>
                    <span class="co-mstat-label">Success Stories</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="co-scroll-indicator">
        <span>Begin Mission</span>
        <div class="co-scroll-arrow">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<section class="co-section">
    <div class="container">
        
        <!-- Mission Control Console -->
        <div class="co-console fade-up">
            <div class="co-console-header">
                <div class="co-console-dots">
                    <span class="dot red"></span>
                    <span class="dot yellow"></span>
                    <span class="dot green"></span>
                </div>
                <i class="fas fa-satellite-dish"></i>
                <span>mission_control.exe</span>
                <span class="co-console-status">● LIVE MISSION</span>
            </div>
            
            <div class="co-console-body">
                <div class="co-mission-info">
                    <span class="co-info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <strong>Target:</strong> Global Tech Industry
                    </span>
                    <span class="co-info-item">
                        <i class="fas fa-calendar"></i>
                        <strong>Launch Window:</strong> 2026-2030
                    </span>
                    <span class="co-info-item">
                        <i class="fas fa-users"></i>
                        <strong>Crew:</strong> <?= rand(200, 500) ?> Cadets Ready
                    </span>
                </div>
            </div>
        </div>

        <!-- Career Orbit Section -->
        <div class="co-orbit-header fade-up">
            <span class="co-section-label">CAREER ORBITS</span>
            <h2>Pilihan Karir yang <span class="accent">Menjanjikan</span></h2>
            <p>Setiap jalur karir adalah orbit unik yang menunggu untuk dijelajahi oleh lulusan kami</p>
        </div>

        <!-- Career Cards (Orbital Cards) -->
        <div class="co-orbit-grid">
            <?php
            $careers = [
                ['icon' => 'fa-brain', 'title' => 'AI / Machine Learning Engineer', 'desc' => 'Merancang dan mengembangkan model kecerdasan buatan untuk menyelesaikan masalah kompleks.', 'badge' => 'High Demand', 'salary' => 'Rp 15-30 Jt', 'altitude' => 95, 'trajectory' => 'Exponential Growth'],
                ['icon' => 'fa-code', 'title' => 'Software Engineer', 'desc' => 'Membangun aplikasi web, mobile, dan desktop yang skalabel dan berkualitas tinggi.', 'badge' => 'Top Salary', 'salary' => 'Rp 12-25 Jt', 'altitude' => 88, 'trajectory' => 'Stable High'],
                ['icon' => 'fa-shield-alt', 'title' => 'Cybersecurity Analyst', 'desc' => 'Melindungi aset digital perusahaan dari ancaman serangan siber dan kerentanan sistem.', 'badge' => 'Critical', 'salary' => 'Rp 14-28 Jt', 'altitude' => 92, 'trajectory' => 'Rising Fast'],
                ['icon' => 'fa-database', 'title' => 'Data Scientist', 'desc' => 'Menganalisis big data untuk menghasilkan insight bisnis yang berharga dan prediktif.', 'badge' => 'High Demand', 'salary' => 'Rp 16-32 Jt', 'altitude' => 97, 'trajectory' => 'Exponential Growth'],
                ['icon' => 'fa-cloud', 'title' => 'Cloud Architect', 'desc' => 'Merancang infrastruktur cloud yang aman, efisien, dan hemat biaya untuk enterprise.', 'badge' => 'Top Salary', 'salary' => 'Rp 18-35 Jt', 'altitude' => 90, 'trajectory' => 'High Altitude'],
                ['icon' => 'fa-mobile-alt', 'title' => 'Mobile App Developer', 'desc' => 'Menciptakan pengalaman pengguna yang mulus di platform iOS dan Android.', 'badge' => 'Popular', 'salary' => 'Rp 10-22 Jt', 'altitude' => 82, 'trajectory' => 'Steady Orbit'],
                ['icon' => 'fa-project-diagram', 'title' => 'IT Project Manager', 'desc' => 'Memimpin tim teknis dan memastikan proyek teknologi selesai tepat waktu dan sesuai budget.', 'badge' => 'Leadership', 'salary' => 'Rp 20-40 Jt', 'altitude' => 85, 'trajectory' => 'Command Path'],
                ['icon' => 'fa-gamepad', 'title' => 'Game Developer', 'desc' => 'Mengembangkan mekanisme, grafis, dan logika permainan video yang imersif.', 'badge' => 'Creative', 'salary' => 'Rp 12-25 Jt', 'altitude' => 78, 'trajectory' => 'Creative Orbit']
            ];
            $orbitColors = ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316'];
            
            foreach ($careers as $index => $c):
                $color = $orbitColors[$index % count($orbitColors)];
                $badgeClass = match($c['badge']) {
                    'High Demand' => 'co-badge-demand',
                    'Top Salary' => 'co-badge-salary',
                    'Critical' => 'co-badge-critical',
                    'Popular' => 'co-badge-popular',
                    'Leadership' => 'co-badge-leadership',
                    'Creative' => 'co-badge-creative',
                    default => 'co-badge-default'
                };
            ?>
            <div class="co-orbit-card fade-up" style="animation-delay: <?= $index * 0.08 ?>s; --orbit-color: <?= $color ?>;">
                <!-- Orbit Ring -->
                <div class="co-card-orbit">
                    <span class="co-orbit-dot"></span>
                </div>
                
                <!-- Mission Patch Badge -->
                <div class="co-mission-patch <?= $badgeClass ?>">
                    <i class="fas fa-certificate"></i>
                    <span><?= $c['badge'] ?></span>
                </div>
                
                <!-- Card Header -->
                <div class="co-card-header">
                    <div class="co-card-icon">
                        <i class="fas <?= $c['icon'] ?>"></i>
                        <div class="co-icon-glow"></div>
                    </div>
                    <div class="co-card-id">
                        ORBIT-<?= str_pad($index + 1, 3, '0', STR_PAD_LEFT) ?>
                    </div>
                </div>
                
                <!-- Card Body -->
                <div class="co-card-body">
                    <h3 class="co-card-title"><?= $c['title'] ?></h3>
                    <p class="co-card-desc"><?= $c['desc'] ?></p>
                    
                    <!-- Trajectory Info -->
                    <div class="co-trajectory">
                        <i class="fas fa-route"></i>
                        <span><?= $c['trajectory'] ?></span>
                    </div>
                </div>
                
                <!-- Altitude Meter -->
                <div class="co-altitude-meter">
                    <span class="co-alt-label">Career Altitude</span>
                    <div class="co-alt-track">
                        <div class="co-alt-fill" style="width: <?= $c['altitude'] ?>%;"></div>
                        <span class="co-alt-value"><?= $c['altitude'] ?>K</span>
                    </div>
                </div>
                
                <!-- Card Footer -->
                <div class="co-card-footer">
                    <div class="co-salary-display">
                        <i class="fas fa-coins"></i>
                        <div>
                            <span class="co-salary-label">Monthly Salary</span>
                            <strong class="co-salary-value"><?= $c['salary'] ?></strong>
                        </div>
                    </div>
                    <button class="co-card-action" onclick="coShowToast('🚀 Mission briefing for <?= addslashes($c['title']) ?> coming soon!')">
                        <i class="fas fa-rocket"></i>
                    </button>
                </div>
                
                <!-- Corner Accents -->
                <div class="co-corner co-corner-tl"></div>
                <div class="co-corner co-corner-tr"></div>
                <div class="co-corner co-corner-bl"></div>
                <div class="co-corner co-corner-br"></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Why Hire Us (Mission Advantages) -->
        <div class="co-advantages fade-up">
            <div class="co-adv-header">
                <span class="co-section-label">MISSION ADVANTAGES</span>
                <h2>Mengapa Lulusan Kami <span class="accent">Dicari?</span></h2>
                <p>Program pelatihan kami dirancang seperti misi luar angkasa - mempersiapkan Anda untuk tantangan terbesar</p>
            </div>
            
            <div class="co-adv-grid">
                <div class="co-adv-card">
                    <div class="co-adv-icon">
                        <i class="fas fa-laptop-code"></i>
                        <span class="co-adv-pulse"></span>
                    </div>
                    <div class="co-adv-content">
                        <h4>Kurikulum Industri</h4>
                        <p>Mata kuliah disesuaikan dengan kebutuhan industri terkini dan diperbarui setiap 2 tahun</p>
                        <div class="co-adv-tag">
                            <i class="fas fa-check"></i> Industry-Aligned
                        </div>
                    </div>
                </div>
                
                <div class="co-adv-card">
                    <div class="co-adv-icon co-adv-cyan">
                        <i class="fas fa-hands-helping"></i>
                        <span class="co-adv-pulse"></span>
                    </div>
                    <div class="co-adv-content">
                        <h4>Magang MSIB</h4>
                        <p>Program magang di perusahaan teknologi terkemuka melalui Kampus Merdeka</p>
                        <div class="co-adv-tag">
                            <i class="fas fa-check"></i> Real Experience
                        </div>
                    </div>
                </div>
                
                <div class="co-adv-card">
                    <div class="co-adv-icon co-adv-gold">
                        <i class="fas fa-certificate"></i>
                        <span class="co-adv-pulse"></span>
                    </div>
                    <div class="co-adv-content">
                        <h4>Sertifikasi Profesional</h4>
                        <p>Lulusan mendapatkan sertifikasi AWS, Google Cloud, atau Cisco yang diakui global</p>
                        <div class="co-adv-tag">
                            <i class="fas fa-check"></i> Globally Recognized
                        </div>
                    </div>
                </div>
                
                <div class="co-adv-card">
                    <div class="co-adv-icon co-adv-pink">
                        <i class="fas fa-users"></i>
                        <span class="co-adv-pulse"></span>
                    </div>
                    <div class="co-adv-content">
                        <h4>Alumni Network</h4>
                        <p>Jaringan alumni kuat di berbagai perusahaan teknologi multinasional</p>
                        <div class="co-adv-tag">
                            <i class="fas fa-check"></i> Strong Network
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Partner Companies (Space Stations) -->
        <div class="co-stations fade-up">
            <div class="co-stations-header">
                <span class="co-section-label">ORBITAL STATIONS</span>
                <h2>Dipercaya oleh <span class="accent">Raksasa Teknologi</span></h2>
                <p>Stasiun-stasiun orbital tempat alumni kami bertugas dan berkontribusi</p>
            </div>
            
            <div class="co-stations-marquee">
                <div class="co-stations-track">
                    <?php
                    $partners = ['Google', 'Tokopedia', 'Gojek', 'Telkom Indonesia', 'Bank BCA', 'Traveloka', 'Microsoft', 'IBM', 'Amazon', 'Shopee', 'Bukalapak', 'Dana'];
                    $allPartners = array_merge($partners, $partners);
                    foreach ($allPartners as $i => $partner):
                        $stationColor = $orbitColors[$i % count($orbitColors)];
                    ?>
                    <div class="co-station" style="--station-color: <?= $stationColor ?>;">
                        <div class="co-station-ring"></div>
                        <div class="co-station-core">
                            <span class="co-station-letter"><?= strtoupper(substr($partner, 0, 2)) ?></span>
                        </div>
                        <span class="co-station-name"><?= $partner ?></span>
                        <span class="co-station-status">
                            <span class="co-status-dot-mini"></span>
                            ACTIVE
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Alumni Testimonials (Astronaut Logs) -->
        <div class="co-astronauts fade-up">
            <div class="co-astronauts-header">
                <span class="co-section-label">ASTRONAUT LOGS</span>
                <h2>Kata <span class="accent">Alumni</span> Kami</h2>
                <p>Transmisi langsung dari alumni yang telah sukses menjelajahi galaksi industri</p>
            </div>
            
            <div class="co-astronaut-grid">
                <?php
                $testimonials = [
                    ['name' => 'Andi Pratama', 'role' => 'Software Engineer di Google', 'year' => '2020', 'quote' => 'Kurikulum yang praktis dan dosen yang berpengalaman membuat saya siap menghadapi tantangan di Silicon Valley.', 'mission' => 'SILICON-EXPEDITION'],
                    ['name' => 'Siti Rahayu', 'role' => 'Data Scientist di Tokopedia', 'year' => '2021', 'quote' => 'Program magang MSIB membuka jalan saya untuk langsung diterima di perusahaan unicorn Indonesia.', 'mission' => 'UNICORN-QUEST'],
                    ['name' => 'Budi Santoso', 'role' => 'Cloud Architect di AWS', 'year' => '2019', 'quote' => 'Sertifikasi AWS yang saya dapatkan saat kuliah menjadi modal utama karir internasional saya.', 'mission' => 'CLOUD-ASCENT']
                ];
                foreach ($testimonials as $i => $t):
                    $astroColor = $orbitColors[$i % count($orbitColors)];
                ?>
                <div class="co-astronaut-card" style="--astro-color: <?= $astroColor ?>;">
                    <!-- Transmission Effect -->
                    <div class="co-transmission-lines"></div>
                    
                    <!-- Astronaut Avatar -->
                    <div class="co-astro-avatar">
                        <div class="co-helmet">
                            <span class="co-helmet-initial"><?= strtoupper(substr($t['name'], 0, 1)) ?></span>
                            <div class="co-helmet-reflection"></div>
                        </div>
                        <div class="co-helmet-ring"></div>
                    </div>
                    
                    <!-- Mission Info -->
                    <div class="co-mission-tag">
                        <i class="fas fa-satellite"></i>
                        <span><?= $t['mission'] ?></span>
                    </div>
                    
                    <!-- Quote -->
                    <div class="co-transmission-content">
                        <div class="co-quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="co-astro-quote"><?= $t['quote'] ?></p>
                    </div>
                    
                    <!-- Astronaut Info -->
                    <div class="co-astro-info">
                        <strong><?= $t['name'] ?></strong>
                        <span><?= $t['role'] ?></span>
                        <span class="co-astro-year">
                            <i class="fas fa-rocket"></i>
                            Class of <?= $t['year'] ?>
                        </span>
                    </div>
                    
                    <!-- Signal Strength -->
                    <div class="co-signal-strength">
                        <span></span><span></span><span></span><span></span><span></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CTA: Join the Mission -->
        <div class="co-cta fade-up">
            <div class="co-cta-bg">
                <div class="co-cta-planet"></div>
                <div class="co-cta-stars"></div>
            </div>
            <div class="co-cta-content">
                <div class="co-cta-icon">
                    <i class="fas fa-rocket"></i>
                    <span class="co-cta-flame"></span>
                </div>
                <span class="co-cta-label">JOIN THE MISSION</span>
                <h2>Siap Meluncurkan <br>Karir Impian Anda?</h2>
                <p>Bergabunglah dengan ratusan lulusan sukses yang telah membuktikan kualitas Program Studi Informatika di kancah global</p>
                
                <div class="co-cta-buttons">
                    <a href="daftar.php" class="co-cta-btn co-cta-primary">
                        <i class="fas fa-rocket"></i>
                        <span>Launch My Career</span>
                        <span class="co-btn-glow"></span>
                    </a>
                    <a href="kurikulum.php" class="co-cta-btn co-cta-secondary">
                        <i class="fas fa-book-open"></i>
                        <span>View Flight Manual</span>
                    </a>
                </div>
                
                <div class="co-cta-footer">
                    <span class="co-cta-stat">
                        <i class="fas fa-users"></i>
                        <strong>500+</strong> Cadets Enrolled
                    </span>
                    <span class="co-cta-divider">|</span>
                    <span class="co-cta-stat">
                        <i class="fas fa-star"></i>
                        <strong>4.9/5</strong> Satisfaction
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Toast -->
<div id="coToast" class="co-toast">
    <i class="fas fa-rocket"></i>
    <span id="coToastMsg">Success!</span>
</div>

<!-- Back to Top -->
<button class="co-back-top" id="coBackTop" title="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<style>
:root {
    --co-primary: #10B981;
    --co-primary-dark: #047857;
    --co-cyan: #06B6D4;
    --co-pink: #EC4899;
    --co-purple: #8B5CF6;
    --co-gold: #F59E0B;
    --co-red: #EF4444;
    --co-blue: #3B82F6;
    --co-orange: #F97316;
    --co-dark: #0F172A;
    --co-deeper: #020617;
    --co-gray-50: #F8FAFC;
    --co-gray-100: #F1F5F9;
    --co-gray-200: #E2E8F0;
    --co-gray-600: #475569;
    --co-gray-700: #374151;
    --co-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --co-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --co-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
    --co-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --co-font-display: 'Space Grotesk', 'Inter', sans-serif;
}

body.gl-dark {
    --co-gray-50: #0B1120;
    --co-gray-100: rgba(255,255,255,.08);
    --co-gray-200: rgba(255,255,255,.1);
    --co-gray-600: #94A3B8;
    --co-gray-700: #CBD5E1;
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--co-gray-50); }
::-webkit-scrollbar-thumb { 
    background: linear-gradient(180deg, var(--co-primary), var(--co-purple)); 
    border-radius: 5px; 
}

/* ===== FUEL GAUGE (Progress Bar) ===== */
.co-fuel-gauge {
    position: fixed; top: 0; left: 0; right: 0; height: 6px;
    background: rgba(15,23,42,0.1); z-index: 9998;
}
.co-fuel-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, #EF4444 0%, #F59E0B 30%, #10B981 60%, #3B82F6 100%);
    box-shadow: 0 0 15px rgba(16,185,129,0.6);
    transition: width 0.1s ease-out;
    position: relative;
}
.co-fuel-rocket {
    position: absolute; top: -12px; right: -20px;
    font-size: 1.5rem;
    filter: drop-shadow(0 0 8px rgba(245,158,11,0.6));
    transform: rotate(90deg);
    animation: coRocketShake 0.5s infinite;
}
@keyframes coRocketShake {
    0%, 100% { transform: rotate(90deg) translateX(0); }
    50% { transform: rotate(90deg) translateX(2px); }
}

/* ===== HERO ===== */
.co-hero {
    background: linear-gradient(135deg, #020617 0%, #0F172A 30%, #1E1B4B 70%, #047857 100%);
    padding: 160px 20px 120px; position: relative; overflow: hidden;
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
}

.co-star-canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    z-index: 1; opacity: 0.8;
}

.co-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }

.co-nebula {
    position: absolute; border-radius: 50%;
    filter: blur(120px); opacity: 0.3;
    animation: coNebulaFloat 20s infinite ease-in-out;
}
.co-neb-1 { width: 600px; height: 600px; background: var(--co-purple); top: -200px; right: -150px; }
.co-neb-2 { width: 450px; height: 450px; background: var(--co-primary); bottom: -150px; left: -100px; animation-delay: -7s; }
.co-neb-3 { width: 350px; height: 350px; background: var(--co-cyan); top: 30%; left: 40%; animation-delay: -14s; }
@keyframes coNebulaFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -30px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.95); }
}

.co-orbit-paths {
    position: absolute; inset: 0;
    background: 
        radial-gradient(ellipse at 20% 30%, transparent 40%, rgba(16,185,129,0.03) 41%, transparent 42%),
        radial-gradient(ellipse at 80% 70%, transparent 30%, rgba(139,92,246,0.03) 31%, transparent 32%),
        radial-gradient(ellipse at 50% 50%, transparent 50%, rgba(6,182,212,0.03) 51%, transparent 52%);
}

.co-shooting-stars {
    position: absolute; inset: 0;
    background-image: 
        radial-gradient(2px 2px at 20% 30%, white, transparent),
        radial-gradient(1px 1px at 60% 70%, white, transparent),
        radial-gradient(1px 1px at 40% 80%, white, transparent),
        radial-gradient(2px 2px at 85% 20%, white, transparent),
        radial-gradient(1px 1px at 15% 60%, white, transparent);
    background-size: 300px 300px;
    animation: coStarsDrift 50s linear infinite;
}
@keyframes coStarsDrift {
    to { background-position: 300px 300px; }
}

/* Floating Elements */
.co-floating-elements { position: absolute; inset: 0; z-index: 3; pointer-events: none; }
.co-float-el {
    position: absolute; font-size: 2.5rem;
    animation: coFloatEl 10s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
    opacity: 0.35; filter: drop-shadow(0 0 15px rgba(255,255,255,0.4));
}
@keyframes coFloatEl {
    0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.35; }
    50% { transform: translateY(-40px) rotate(15deg); opacity: 0.7; }
}

.co-hero-content {
    position: relative; z-index: 4; text-align: center;
    color: #fff; max-width: 1100px; margin: 0 auto;
}

/* Mission Badge */
.co-mission-badge {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 10px 22px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.2); border-radius: 50px;
    font-size: .82rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 700; letter-spacing: 2px; position: relative;
    font-family: var(--co-font-mono);
}
.co-badge-glow {
    position: absolute; inset: 0; border-radius: 50px;
    border: 2px solid var(--co-primary);
    animation: coBadgeGlow 2s infinite;
}
@keyframes coBadgeGlow { 
    0% { transform: scale(1); opacity: 1; } 
    100% { transform: scale(1.2); opacity: 0; } 
}
.co-mission-id {
    color: var(--co-gold);
}
.co-mission-badge i { color: var(--co-primary); font-size: 1rem; }

.co-hero-title {
    font-size: clamp(3rem, 9vw, 7rem); font-weight: 900;
    margin-bottom: 24px; letter-spacing: -0.03em; line-height: 0.95;
    font-family: var(--co-font-display);
}
.co-title-line { display: block; }
.co-title-sub {
    display: block; font-size: 0.5em; font-weight: 500;
    color: rgba(255,255,255,0.7); letter-spacing: 0.1em;
    margin-top: 10px;
}

.co-title-odyssey {
    position: relative; display: inline-block;
    background: linear-gradient(135deg, #10B981 0%, #06B6D4 40%, #8B5CF6 70%, #F59E0B 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 100%;
    animation: coOdysseyShift 5s ease infinite;
}
.co-title-odyssey::before, .co-title-odyssey::after {
    content: attr(data-text); position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    background: inherit; -webkit-background-clip: text;
    -webkit-text-fill-color: transparent; background-clip: text;
}
.co-title-odyssey::before {
    animation: coChromaticR 3s infinite;
    mix-blend-mode: screen; opacity: 0.4;
}
.co-title-odyssey::after {
    animation: coChromaticB 3s infinite;
    mix-blend-mode: screen; opacity: 0.4;
}
@keyframes coOdysseyShift { 
    0%, 100% { background-position: 0% 50%; } 
    50% { background-position: 100% 50%; } 
}
@keyframes coChromaticR {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(3px, -2px); }
}
@keyframes coChromaticB {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(-3px, 2px); }
}

.co-hero-subtitle {
    font-size: 1.2rem; opacity: .9; max-width: 700px;
    margin: 0 auto 40px; line-height: 1.7;
    display: inline-block; min-height: 1.7em;
}
.co-cursor { animation: coBlink 1s infinite; color: var(--co-primary); font-weight: 300; }
@keyframes coBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

/* Launch Sequence Panel */
.co-launch-sequence {
    display: flex; justify-content: center; margin-bottom: 40px;
}
.co-launch-panel {
    background: rgba(15,23,42,0.6); backdrop-filter: blur(20px);
    border: 1px solid rgba(16,185,129,0.3); border-radius: 20px;
    padding: 24px 32px; min-width: 450px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.1);
}
.co-launch-header {
    display: flex; align-items: center; gap: 10px;
    font-family: var(--co-font-mono); font-size: 0.75rem;
    color: var(--co-primary); letter-spacing: 2px; font-weight: 700;
    margin-bottom: 16px; justify-content: center;
}
.co-launch-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--co-red);
    box-shadow: 0 0 10px var(--co-red);
    animation: coStatusPulse 1s infinite;
}
@keyframes coStatusPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.co-countdown {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    margin-bottom: 16px;
}
.co-cd-unit {
    display: flex; flex-direction: column; align-items: center;
    padding: 8px 16px; background: rgba(0,0,0,0.4);
    border-radius: 10px; border: 1px solid rgba(16,185,129,0.2);
    min-width: 65px;
}
.co-cd-unit span {
    font-family: var(--co-font-display); font-size: 1.8rem;
    font-weight: 900; color: #fff; line-height: 1;
    text-shadow: 0 0 10px var(--co-primary);
}
.co-cd-unit small {
    font-family: var(--co-font-mono); font-size: 0.6rem;
    color: rgba(255,255,255,0.5); letter-spacing: 1px;
    margin-top: 4px;
}
.co-cd-sep {
    font-size: 1.5rem; color: var(--co-primary); font-weight: 700;
    animation: coBlink 1s infinite;
}

.co-launch-status {
    text-align: center;
}
.co-status-indicator {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 14px; background: rgba(16,185,129,0.15);
    border-radius: 20px; font-family: var(--co-font-mono);
    font-size: 0.72rem; color: var(--co-primary);
    letter-spacing: 1px; font-weight: 700;
}
.co-status-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--co-primary);
    box-shadow: 0 0 8px var(--co-primary);
    animation: coStatusPulse 1.5s infinite;
}

/* Mission Stats */
.co-mission-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 16px; max-width: 900px; margin: 0 auto;
}
.co-mstat {
    background: rgba(255,255,255,.06); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.1); border-radius: 16px;
    padding: 20px 16px; transition: all 0.3s;
    display: flex; flex-direction: column; align-items: center; gap: 12px;
    text-align: center;
}
.co-mstat:hover {
    transform: translateY(-4px);
    border-color: var(--co-primary);
    background: rgba(255,255,255,.1);
}

/* Ring Stat (First one) */
.co-mstat-ring {
    position: relative; width: 80px; height: 80px;
}
.co-mstat-ring svg {
    width: 100%; height: 100%; transform: rotate(-90deg);
}
.co-mstat-ring circle {
    fill: none; stroke-width: 6;
}
.co-mstat-ring circle:first-child { stroke: rgba(255,255,255,0.1); }
.co-mstat-ring .co-ring-fill {
    stroke: var(--co-primary); stroke-linecap: round;
    stroke-dasharray: 283; 
    stroke-dashoffset: calc(283 - (283 * var(--percent)) / 100);
    transition: stroke-dashoffset 2s ease-out;
    filter: drop-shadow(0 0 6px var(--co-primary));
}
.co-mstat-value {
    position: absolute; inset: 0; display: flex;
    align-items: center; justify-content: center;
    font-family: var(--co-font-display); font-size: 1.3rem;
    font-weight: 900; color: #fff;
}

.co-mstat-icon {
    width: 50px; height: 50px; border-radius: 14px;
    background: linear-gradient(135deg, var(--co-primary), var(--co-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; position: relative;
    box-shadow: 0 4px 16px rgba(16,185,129,0.4);
}
.co-mstat-blue { background: linear-gradient(135deg, var(--co-blue), #1D4ED8); box-shadow: 0 4px 16px rgba(59,130,246,0.4); }
.co-mstat-gold { background: linear-gradient(135deg, var(--co-gold), #D97706); box-shadow: 0 4px 16px rgba(245,158,11,0.4); }

.co-icon-pulse {
    position: absolute; inset: -4px; border-radius: 16px;
    border: 2px solid currentColor; opacity: 0;
    animation: coIconPulse 2s infinite;
}
@keyframes coIconPulse {
    0% { transform: scale(0.9); opacity: 0.8; }
    100% { transform: scale(1.3); opacity: 0; }
}

.co-mstat-info { text-align: center; }
.co-mstat-num {
    display: block; font-size: 1.8rem; font-weight: 900;
    color: #fff; line-height: 1; font-family: var(--co-font-display);
}
.co-mstat-label {
    font-size: 0.7rem; color: rgba(255,255,255,.6);
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-top: 4px; display: block;
}

/* Scroll Indicator */
.co-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%;
    transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.co-scroll-arrow { animation: coArrowBounce 2s infinite; }
@keyframes coArrowBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(10px); }
}

/* ===== MAIN SECTION ===== */
.co-section { padding: 80px 20px 100px; background: var(--co-gray-50); }
body.gl-dark .co-section { background: #0B1120; }

/* Console */
.co-console {
    background: #fff; border-radius: 20px;
    box-shadow: var(--co-shadow-md); overflow: hidden;
    border: 1px solid var(--co-gray-100); margin-bottom: 60px;
}
body.gl-dark .co-console { background: #1E293B; border-color: rgba(255,255,255,.08); }

.co-console-header {
    background: var(--co-dark); padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
    color: #94A3B8; font-family: var(--co-font-mono); font-size: 0.8rem;
}
.co-console-dots { display: flex; gap: 5px; }
.co-console-dots .dot { width: 11px; height: 11px; border-radius: 50%; }
.co-console-dots .red { background: #FF5F56; }
.co-console-dots .yellow { background: #FFBD2E; }
.co-console-dots .green { background: #27C93F; }
.co-console-header i { color: var(--co-primary); }
.co-console-header span { color: #E2E8F0; }
.co-console-status {
    margin-left: auto; color: var(--co-primary);
    font-size: 0.7rem; letter-spacing: 1px;
    animation: coBlink 2s infinite;
}

.co-console-body { padding: 20px 24px; }

.co-mission-info {
    display: flex; justify-content: space-around; flex-wrap: wrap; gap: 16px;
}
.co-info-item {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.88rem; color: var(--co-gray-600);
}
body.gl-dark .co-info-item { color: #94A3B8; }
.co-info-item i { color: var(--co-primary); font-size: 1rem; }
.co-info-item strong { color: var(--co-dark); margin-right: 4px; }
body.gl-dark .co-info-item strong { color: #F1F5F9; }

/* Section Headers */
.co-orbit-header, .co-adv-header, .co-stations-header, .co-astronauts-header {
    text-align: center; margin-bottom: 50px;
}
.co-section-label {
    display: inline-block; padding: 6px 16px;
    background: linear-gradient(135deg, var(--co-primary), var(--co-cyan));
    color: #fff; border-radius: 20px;
    font-family: var(--co-font-mono); font-size: 0.7rem;
    font-weight: 800; letter-spacing: 2px; margin-bottom: 16px;
}
.co-orbit-header h2, .co-adv-header h2, .co-stations-header h2, .co-astronauts-header h2 {
    font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 900;
    margin: 0 0 12px 0; color: var(--co-dark);
    font-family: var(--co-font-display);
}
body.gl-dark .co-orbit-header h2, body.gl-dark .co-adv-header h2,
body.gl-dark .co-stations-header h2, body.gl-dark .co-astronauts-header h2 { color: #F1F5F9; }
.accent { 
    background: linear-gradient(135deg, var(--co-primary), var(--co-purple));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.co-orbit-header p, .co-adv-header p, .co-stations-header p, .co-astronauts-header p {
    font-size: 1.05rem; color: var(--co-gray-600);
    max-width: 600px; margin: 0 auto;
}
body.gl-dark .co-orbit-header p, body.gl-dark .co-adv-header p,
body.gl-dark .co-stations-header p, body.gl-dark .co-astronauts-header p { color: #94A3B8; }

/* ===== ORBIT GRID (Career Cards) ===== */
.co-orbit-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 28px; margin-bottom: 80px;
}

.co-orbit-card {
    position: relative; background: #fff; border-radius: 24px;
    padding: 28px; border: 1px solid var(--co-gray-100);
    box-shadow: var(--co-shadow-sm); overflow: hidden;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex; flex-direction: column;
}
body.gl-dark .co-orbit-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

/* Orbit Ring Animation */
.co-card-orbit {
    position: absolute; top: -40px; right: -40px;
    width: 120px; height: 120px; border-radius: 50%;
    border: 2px dashed var(--orbit-color, var(--co-primary));
    opacity: 0.3; animation: coOrbitSpin 20s linear infinite;
}
.co-orbit-dot {
    position: absolute; top: 0; left: 50%;
    transform: translateX(-50%) translateY(-50%);
    width: 12px; height: 12px; border-radius: 50%;
    background: var(--orbit-color, var(--co-primary));
    box-shadow: 0 0 15px var(--orbit-color, var(--co-primary));
}
@keyframes coOrbitSpin { to { transform: rotate(360deg); } }

/* Mission Patch */
.co-mission-patch {
    position: absolute; top: 16px; right: 16px; z-index: 3;
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 20px;
    font-size: 0.68rem; font-weight: 800;
    letter-spacing: 1px; text-transform: uppercase;
    font-family: var(--co-font-mono);
}
.co-mission-patch i { font-size: 0.7rem; }

.co-badge-demand { background: rgba(239,68,68,0.15); color: var(--co-red); border: 1px solid rgba(239,68,68,0.3); }
.co-badge-salary { background: rgba(245,158,11,0.15); color: var(--co-gold); border: 1px solid rgba(245,158,11,0.3); }
.co-badge-critical { background: rgba(236,72,153,0.15); color: var(--co-pink); border: 1px solid rgba(236,72,153,0.3); }
.co-badge-popular { background: rgba(16,185,129,0.15); color: var(--co-primary); border: 1px solid rgba(16,185,129,0.3); }
.co-badge-leadership { background: rgba(139,92,246,0.15); color: var(--co-purple); border: 1px solid rgba(139,92,246,0.3); }
.co-badge-creative { background: rgba(6,182,212,0.15); color: var(--co-cyan); border: 1px solid rgba(6,182,212,0.3); }

/* Card Header */
.co-card-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 20px;
}
.co-card-icon {
    width: 64px; height: 64px; border-radius: 18px;
    background: linear-gradient(135deg, var(--orbit-color, var(--co-primary)), color-mix(in srgb, var(--orbit-color, var(--co-primary)) 70%, #000));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; position: relative;
    box-shadow: 0 8px 20px color-mix(in srgb, var(--orbit-color, var(--co-primary)) 35%, transparent);
    transition: all 0.4s;
}
.co-icon-glow {
    position: absolute; inset: -6px; border-radius: 22px;
    background: var(--orbit-color, var(--co-primary));
    opacity: 0.2; filter: blur(10px);
    transition: opacity 0.3s;
}
.co-orbit-card:hover .co-icon-glow { opacity: 0.4; }
.co-orbit-card:hover .co-card-icon { transform: scale(1.08) rotate(-5deg); }

.co-card-id {
    font-family: var(--co-font-mono); font-size: 0.72rem;
    color: var(--co-gray-600); padding: 4px 10px;
    background: var(--co-gray-100); border-radius: 6px;
    font-weight: 700; letter-spacing: 1px;
}
body.gl-dark .co-card-id { background: rgba(255,255,255,.08); color: #94A3B8; }

/* Card Body */
.co-card-body { flex: 1; margin-bottom: 20px; }
.co-card-title {
    font-size: 1.2rem; font-weight: 800; line-height: 1.3;
    color: var(--co-dark); margin: 0 0 10px 0;
    font-family: var(--co-font-display);
    transition: color 0.3s;
}
body.gl-dark .co-card-title { color: #F1F5F9; }
.co-orbit-card:hover .co-card-title { color: var(--orbit-color, var(--co-primary)); }

.co-card-desc {
    font-size: 0.88rem; color: var(--co-gray-600); line-height: 1.6;
    margin: 0 0 14px 0;
}
body.gl-dark .co-card-desc { color: #94A3B8; }

.co-trajectory {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 5px 12px; background: color-mix(in srgb, var(--orbit-color, var(--co-primary)) 12%, transparent);
    color: var(--orbit-color, var(--co-primary));
    border-radius: 20px; font-size: 0.75rem; font-weight: 700;
    font-family: var(--co-font-mono); letter-spacing: 0.5px;
}
.co-trajectory i { font-size: 0.7rem; }

/* Altitude Meter */
.co-altitude-meter {
    margin-bottom: 20px; padding: 14px;
    background: var(--co-gray-50); border-radius: 12px;
}
body.gl-dark .co-altitude-meter { background: rgba(255,255,255,.03); }
.co-alt-label {
    display: flex; justify-content: space-between;
    font-size: 0.72rem; color: var(--co-gray-600);
    font-weight: 600; margin-bottom: 8px;
    text-transform: uppercase; letter-spacing: 0.5px;
}
body.gl-dark .co-alt-label { color: #94A3B8; }

.co-alt-track {
    height: 8px; background: var(--co-gray-200);
    border-radius: 4px; overflow: hidden; position: relative;
}
body.gl-dark .co-alt-track { background: rgba(255,255,255,.08); }
.co-alt-fill {
    height: 100%; border-radius: 4px;
    background: linear-gradient(90deg, var(--orbit-color, var(--co-primary)), color-mix(in srgb, var(--orbit-color, var(--co-primary)) 70%, #fff));
    position: relative; transition: width 1s ease-out;
}
.co-alt-fill::after {
    content: ''; position: absolute; right: 0; top: -4px;
    width: 4px; height: 16px; background: var(--orbit-color, var(--co-primary));
    border-radius: 2px; box-shadow: 0 0 8px var(--orbit-color, var(--co-primary));
}
.co-alt-value {
    position: absolute; right: 8px; top: -20px;
    font-family: var(--co-font-mono); font-size: 0.7rem;
    font-weight: 800; color: var(--orbit-color, var(--co-primary));
}

/* Card Footer */
.co-card-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding-top: 16px; border-top: 1px dashed var(--co-gray-200);
}
body.gl-dark .co-card-footer { border-color: rgba(255,255,255,.1); }

.co-salary-display {
    display: flex; align-items: center; gap: 10px;
}
.co-salary-display > i {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(245,158,11,0.15); color: var(--co-gold);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
}
.co-salary-label {
    display: block; font-size: 0.68rem; color: var(--co-gray-600);
    text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;
}
body.gl-dark .co-salary-label { color: #94A3B8; }
.co-salary-value {
    font-family: var(--co-font-display); font-size: 0.95rem;
    font-weight: 800; color: var(--co-dark);
}
body.gl-dark .co-salary-value { color: #F1F5F9; }

.co-card-action {
    width: 44px; height: 44px; border-radius: 12px;
    background: linear-gradient(135deg, var(--orbit-color, var(--co-primary)), color-mix(in srgb, var(--orbit-color, var(--co-primary)) 70%, #000));
    border: none; color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; transition: all 0.3s;
    box-shadow: 0 4px 12px color-mix(in srgb, var(--orbit-color, var(--co-primary)) 30%, transparent);
}
.co-card-action:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 20px color-mix(in srgb, var(--orbit-color, var(--co-primary)) 40%, transparent);
}

/* Corner Accents */
.co-corner {
    position: absolute; width: 20px; height: 20px;
    border: 2px solid var(--orbit-color, var(--co-primary));
    opacity: 0; transition: opacity 0.3s; pointer-events: none;
}
.co-corner-tl { top: 10px; left: 10px; border-right: none; border-bottom: none; }
.co-corner-tr { top: 10px; right: 10px; border-left: none; border-bottom: none; }
.co-corner-bl { bottom: 10px; left: 10px; border-right: none; border-top: none; }
.co-corner-br { bottom: 10px; right: 10px; border-left: none; border-top: none; }
.co-orbit-card:hover .co-corner { opacity: 1; }

.co-orbit-card::before {
    content: ''; position: absolute; inset: 0; border-radius: 24px; padding: 1px;
    background: conic-gradient(from 0deg, 
        transparent, 
        var(--orbit-color, var(--co-primary)),
        transparent 40%
    );
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none;
}
.co-orbit-card:hover::before { opacity: 1; animation: coRotateBorder 4s linear infinite; }
@keyframes coRotateBorder { to { transform: rotate(360deg); } }

.co-orbit-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: var(--co-shadow-lg), 0 0 40px color-mix(in srgb, var(--orbit-color, var(--co-primary)) 25%, transparent);
    border-color: var(--orbit-color, var(--co-primary));
}

/* ===== ADVANTAGES GRID ===== */
.co-advantages { margin-bottom: 80px; }

.co-adv-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
}

.co-adv-card {
    background: #fff; border-radius: 20px; padding: 28px;
    border: 1px solid var(--co-gray-100);
    box-shadow: var(--co-shadow-sm); transition: all 0.4s;
    display: flex; gap: 20px; align-items: flex-start;
}
body.gl-dark .co-adv-card { background: #1E293B; border-color: rgba(255,255,255,.08); }
.co-adv-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--co-shadow-md);
    border-color: var(--co-primary);
}

.co-adv-icon {
    width: 56px; height: 56px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--co-primary), var(--co-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; position: relative;
    box-shadow: 0 4px 16px rgba(16,185,129,0.3);
}
.co-adv-cyan { background: linear-gradient(135deg, var(--co-cyan), #0891B2); box-shadow: 0 4px 16px rgba(6,182,212,0.3); }
.co-adv-gold { background: linear-gradient(135deg, var(--co-gold), #D97706); box-shadow: 0 4px 16px rgba(245,158,11,0.3); }
.co-adv-pink { background: linear-gradient(135deg, var(--co-pink), #BE185D); box-shadow: 0 4px 16px rgba(236,72,153,0.3); }

.co-adv-pulse {
    position: absolute; inset: -4px; border-radius: 16px;
    border: 2px solid currentColor; opacity: 0;
    animation: coIconPulse 2s infinite;
}

.co-adv-content { flex: 1; }
.co-adv-content h4 {
    font-size: 1.1rem; font-weight: 800; margin: 0 0 8px 0;
    color: var(--co-dark); font-family: var(--co-font-display);
}
body.gl-dark .co-adv-content h4 { color: #F1F5F9; }
.co-adv-content p {
    font-size: 0.88rem; color: var(--co-gray-600);
    line-height: 1.6; margin: 0 0 12px 0;
}
body.gl-dark .co-adv-content p { color: #94A3B8; }

.co-adv-tag {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; background: var(--co-primary-light);
    color: var(--co-primary-dark); border-radius: 20px;
    font-size: 0.72rem; font-weight: 700;
    letter-spacing: 0.5px;
}
body.gl-dark .co-adv-tag { background: rgba(16,185,129,0.15); color: #34D399; }
.co-adv-tag i { font-size: 0.65rem; }

/* ===== SPACE STATIONS (Partners) ===== */
.co-stations { margin-bottom: 80px; }

.co-stations-marquee {
    overflow: hidden; position: relative;
    padding: 20px 0;
}
.co-stations-marquee::before, .co-stations-marquee::after {
    content: ''; position: absolute; top: 0; width: 150px; height: 100%; z-index: 2;
    pointer-events: none;
}
.co-stations-marquee::before { left: 0; background: linear-gradient(to right, var(--co-gray-50), transparent); }
.co-stations-marquee::after { right: 0; background: linear-gradient(to left, var(--co-gray-50), transparent); }
body.gl-dark .co-stations-marquee::before { background: linear-gradient(to right, #0B1120, transparent); }
body.gl-dark .co-stations-marquee::after { background: linear-gradient(to left, #0B1120, transparent); }

.co-stations-track {
    display: flex; gap: 32px;
    animation: coStationsMarquee 40s linear infinite;
    width: fit-content;
}
.co-stations-track:hover { animation-play-state: paused; }
@keyframes coStationsMarquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

.co-station {
    position: relative; flex-shrink: 0;
    width: 140px; height: 160px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 10px; padding: 20px;
    background: #fff; border-radius: 20px;
    border: 1px solid var(--co-gray-100);
    box-shadow: var(--co-shadow-sm);
    transition: all 0.3s;
    cursor: pointer;
}
body.gl-dark .co-station { background: #1E293B; border-color: rgba(255,255,255,.08); }

.co-station:hover {
    transform: translateY(-8px) scale(1.05);
    border-color: var(--station-color, var(--co-primary));
    box-shadow: 0 12px 30px color-mix(in srgb, var(--station-color, var(--co-primary)) 25%, transparent);
}

.co-station-ring {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 80px; height: 80px; border-radius: 50%;
    border: 2px dashed var(--station-color, var(--co-primary));
    opacity: 0; transition: opacity 0.3s;
    animation: coOrbitSpin 10s linear infinite;
}
.co-station:hover .co-station-ring { opacity: 0.5; }

.co-station-core {
    width: 60px; height: 60px; border-radius: 50%;
    background: linear-gradient(135deg, var(--station-color, var(--co-primary)), color-mix(in srgb, var(--station-color, var(--co-primary)) 70%, #000));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-family: var(--co-font-display); font-weight: 900; font-size: 1.2rem;
    box-shadow: 0 6px 16px color-mix(in srgb, var(--station-color, var(--co-primary)) 35%, transparent);
    position: relative; z-index: 1;
    transition: transform 0.3s;
}
.co-station:hover .co-station-core { transform: scale(1.1) rotate(360deg); }

.co-station-name {
    font-size: 0.85rem; font-weight: 700; color: var(--co-dark);
    text-align: center; position: relative; z-index: 1;
}
body.gl-dark .co-station-name { color: #F1F5F9; }

.co-station-status {
    display: flex; align-items: center; gap: 5px;
    font-family: var(--co-font-mono); font-size: 0.65rem;
    color: var(--co-primary); letter-spacing: 1px; font-weight: 700;
    position: relative; z-index: 1;
}
.co-status-dot-mini {
    width: 5px; height: 5px; border-radius: 50%;
    background: var(--co-primary);
    box-shadow: 0 0 6px var(--co-primary);
    animation: coStatusPulse 1.5s infinite;
}

/* ===== ASTRONAUTS (Testimonials) ===== */
.co-astronauts { margin-bottom: 80px; }

.co-astronaut-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 28px;
}

.co-astronaut-card {
    position: relative; background: #fff; border-radius: 24px;
    padding: 32px 28px; border: 1px solid var(--co-gray-100);
    box-shadow: var(--co-shadow-sm); overflow: hidden;
    transition: all 0.4s; text-align: center;
}
body.gl-dark .co-astronaut-card { background: #1E293B; border-color: rgba(255,255,255,.08); }
.co-astronaut-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--co-shadow-lg);
    border-color: var(--astro-color, var(--co-primary));
}

/* Transmission Lines */
.co-transmission-lines {
    position: absolute; inset: 0; pointer-events: none;
    background: repeating-linear-gradient(
        0deg,
        transparent,
        transparent 2px,
        rgba(16,185,129,0.03) 2px,
        rgba(16,185,129,0.03) 3px
    );
    opacity: 0; transition: opacity 0.3s;
}
.co-astronaut-card:hover .co-transmission-lines { opacity: 1; }

/* Astronaut Avatar */
.co-astro-avatar {
    position: relative; width: 100px; height: 100px;
    margin: 0 auto 20px;
}
.co-helmet {
    width: 100%; height: 100%; border-radius: 50%;
    background: linear-gradient(135deg, var(--astro-color, var(--co-primary)), color-mix(in srgb, var(--astro-color, var(--co-primary)) 70%, #000));
    display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden;
    box-shadow: 0 8px 24px color-mix(in srgb, var(--astro-color, var(--co-primary)) 35%, transparent);
}
.co-helmet-initial {
    font-family: var(--co-font-display); font-size: 2.5rem;
    font-weight: 900; color: #fff; z-index: 2;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.co-helmet-reflection {
    position: absolute; top: 15%; left: 15%;
    width: 30%; height: 30%; border-radius: 50%;
    background: rgba(255,255,255,0.3);
    filter: blur(8px);
}
.co-helmet-ring {
    position: absolute; inset: -8px; border-radius: 50%;
    border: 2px dashed var(--astro-color, var(--co-primary));
    animation: coOrbitSpin 15s linear infinite;
}

/* Mission Tag */
.co-mission-tag {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 5px 14px; background: var(--co-gray-100);
    border-radius: 20px; font-family: var(--co-font-mono);
    font-size: 0.7rem; font-weight: 700;
    color: var(--co-gray-700); letter-spacing: 1px;
    margin-bottom: 16px;
}
body.gl-dark .co-mission-tag { background: rgba(255,255,255,.08); color: #CBD5E1; }
.co-mission-tag i { color: var(--astro-color, var(--co-primary)); }

/* Transmission Content */
.co-transmission-content {
    position: relative; margin-bottom: 20px;
}
.co-quote-icon {
    width: 40px; height: 40px; border-radius: 50%;
    background: var(--co-gray-100);
    color: var(--astro-color, var(--co-primary));
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; margin: 0 auto 12px;
}
body.gl-dark .co-quote-icon { background: rgba(255,255,255,.08); }

.co-astro-quote {
    font-size: 0.95rem; color: var(--co-gray-700);
    line-height: 1.7; font-style: italic; margin: 0;
}
body.gl-dark .co-astro-quote { color: #CBD5E1; }

/* Astronaut Info */
.co-astro-info {
    padding-top: 16px; border-top: 1px dashed var(--co-gray-200);
    margin-bottom: 12px;
}
body.gl-dark .co-astro-info { border-color: rgba(255,255,255,.1); }
.co-astro-info strong {
    display: block; font-size: 1.05rem; color: var(--co-dark);
    font-family: var(--co-font-display); font-weight: 800;
    margin-bottom: 4px;
}
body.gl-dark .co-astro-info strong { color: #F1F5F9; }
.co-astro-info span {
    display: block; font-size: 0.82rem; color: var(--co-gray-600);
}
body.gl-dark .co-astro-info span { color: #94A3B8; }
.co-astro-year {
    display: inline-flex; align-items: center; gap: 6px;
    margin-top: 8px; padding: 3px 10px;
    background: color-mix(in srgb, var(--astro-color, var(--co-primary)) 12%, transparent);
    color: var(--astro-color, var(--co-primary));
    border-radius: 20px; font-size: 0.72rem; font-weight: 700;
    font-family: var(--co-font-mono); letter-spacing: 0.5px;
}
.co-astro-year i { font-size: 0.7rem; }

/* Signal Strength */
.co-signal-strength {
    display: flex; gap: 3px; align-items: flex-end; justify-content: center;
    height: 16px;
}
.co-signal-strength span {
    width: 4px; border-radius: 1px;
    background: var(--astro-color, var(--co-primary));
    animation: coSignalPulse 1.5s infinite ease-in-out;
}
.co-signal-strength span:nth-child(1) { height: 25%; animation-delay: 0s; }
.co-signal-strength span:nth-child(2) { height: 50%; animation-delay: 0.1s; }
.co-signal-strength span:nth-child(3) { height: 75%; animation-delay: 0.2s; }
.co-signal-strength span:nth-child(4) { height: 90%; animation-delay: 0.3s; }
.co-signal-strength span:nth-child(5) { height: 100%; animation-delay: 0.4s; }
@keyframes coSignalPulse {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 1; }
}

/* ===== CTA SECTION ===== */
.co-cta {
    position: relative; border-radius: 32px; overflow: hidden;
    padding: 80px 40px; text-align: center; color: #fff;
    background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 50%, #047857 100%);
    box-shadow: var(--co-shadow-lg);
}

.co-cta-bg { position: absolute; inset: 0; pointer-events: none; }
.co-cta-planet {
    position: absolute; top: -100px; right: -100px;
    width: 400px; height: 400px; border-radius: 50%;
    background: radial-gradient(circle at 30% 30%, var(--co-primary), var(--co-primary-dark));
    opacity: 0.2; filter: blur(60px);
}
.co-cta-stars {
    position: absolute; inset: 0;
    background-image: 
        radial-gradient(2px 2px at 20% 30%, white, transparent),
        radial-gradient(1px 1px at 60% 70%, white, transparent),
        radial-gradient(1px 1px at 40% 80%, white, transparent);
    background-size: 200px 200px;
    opacity: 0.3;
}

.co-cta-content { position: relative; z-index: 1; max-width: 700px; margin: 0 auto; }

.co-cta-icon {
    position: relative; width: 100px; height: 100px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, var(--co-primary), var(--co-cyan));
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 2.5rem; color: #fff;
    box-shadow: 0 10px 30px rgba(16,185,129,0.4);
    animation: coCtaBounce 3s infinite ease-in-out;
}
@keyframes coCtaBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}
.co-cta-flame {
    position: absolute; bottom: -20px; left: 50%;
    transform: translateX(-50%);
    width: 30px; height: 40px;
    background: linear-gradient(to top, #EF4444, #F59E0B, transparent);
    border-radius: 50% 50% 30% 30%;
    filter: blur(4px);
    animation: coFlameFlicker 0.3s infinite alternate;
}
@keyframes coFlameFlicker {
    from { height: 40px; opacity: 0.8; }
    to { height: 50px; opacity: 1; }
}

.co-cta-label {
    display: inline-block; padding: 6px 16px;
    background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);
    border-radius: 20px; font-family: var(--co-font-mono);
    font-size: 0.75rem; font-weight: 800; letter-spacing: 2px;
    margin-bottom: 16px; border: 1px solid rgba(255,255,255,0.2);
}

.co-cta h2 {
    font-size: clamp(1.8rem, 5vw, 3rem); font-weight: 900;
    margin: 0 0 16px 0; font-family: var(--co-font-display);
    line-height: 1.2;
}
.co-cta p {
    font-size: 1.1rem; opacity: 0.9; margin: 0 0 32px 0;
    line-height: 1.6;
}

.co-cta-buttons {
    display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;
    margin-bottom: 32px;
}
.co-cta-btn {
    position: relative; display: inline-flex; align-items: center; gap: 10px;
    padding: 16px 32px; border-radius: 50px; font-weight: 700;
    font-size: 1rem; text-decoration: none; transition: all 0.3s;
    overflow: hidden; font-family: var(--co-font-display);
}
.co-cta-primary {
    background: #fff; color: var(--co-primary-dark);
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
}
.co-cta-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.3);
}
.co-btn-glow {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 0; height: 0; border-radius: 50%;
    background: var(--co-primary); opacity: 0;
    transition: all 0.5s;
}
.co-cta-primary:hover .co-btn-glow {
    width: 300px; height: 300px; opacity: 0.15;
}

.co-cta-secondary {
    background: transparent; color: #fff;
    border: 2px solid rgba(255,255,255,0.3);
}
.co-cta-secondary:hover {
    background: rgba(255,255,255,0.1); border-color: #fff;
    transform: translateY(-3px);
}

.co-cta-footer {
    display: flex; gap: 24px; justify-content: center; align-items: center;
    flex-wrap: wrap;
}
.co-cta-stat {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.9rem; color: rgba(255,255,255,0.8);
}
.co-cta-stat i { color: var(--co-gold); }
.co-cta-stat strong {
    color: #fff; font-family: var(--co-font-display); font-size: 1.1rem;
}
.co-cta-divider { color: rgba(255,255,255,0.3); }

/* ===== TOAST ===== */
.co-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--co-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    z-index: 10001; opacity: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.9rem; border: 1px solid rgba(16,185,129,0.3);
}
body.gl-dark .co-toast { background: #fff; color: var(--co-dark); }
.co-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.co-toast i { color: var(--co-primary); font-size: 1.2rem; }

/* ===== BACK TO TOP ===== */
.co-back-top {
    position: fixed; bottom: 30px; right: 30px;
    width: 50px; height: 50px; border-radius: 50%;
    background: linear-gradient(135deg, var(--co-primary), var(--co-cyan));
    border: none; color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; z-index: 998;
    box-shadow: 0 8px 24px rgba(16,185,129,0.4);
    opacity: 0; visibility: hidden; transform: translateY(20px) scale(.8);
    transition: all .4s;
}
.co-back-top.show { opacity: 1; visibility: visible; transform: none; }
.co-back-top:hover { transform: translateY(-4px) scale(1.05); }

/* ===== ANIMATIONS ===== */
.fade-up {
    opacity: 0; transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .co-mission-stats { grid-template-columns: repeat(2, 1fr); }
    .co-orbit-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
}
@media (max-width: 768px) {
    .co-hero { padding: 120px 20px 80px; min-height: 90vh; }
    .co-hero-title { font-size: 3rem; }
    .co-mission-stats { grid-template-columns: 1fr 1fr; gap: 10px; }
    .co-launch-panel { min-width: auto; width: 100%; padding: 20px; }
    .co-countdown { flex-wrap: wrap; }
    .co-cd-unit { min-width: 55px; padding: 6px 12px; }
    .co-cd-unit span { font-size: 1.4rem; }
    .co-floating-elements { display: none; }
    .co-orbit-grid { grid-template-columns: 1fr; }
    .co-adv-grid { grid-template-columns: 1fr; }
    .co-adv-card { flex-direction: column; text-align: center; }
    .co-astronaut-grid { grid-template-columns: 1fr; }
    .co-mission-info { flex-direction: column; align-items: flex-start; gap: 10px; }
    .co-cta { padding: 50px 24px; }
    .co-cta-buttons { flex-direction: column; }
    .co-cta-btn { width: 100%; justify-content: center; }
    .co-back-top { width: 44px; height: 44px; bottom: 20px; right: 20px; }
}
@media (max-width: 480px) {
    .co-hero-title { font-size: 2.3rem; }
    .co-mission-badge { font-size: 0.7rem; padding: 8px 14px; flex-wrap: wrap; justify-content: center; }
    .co-cd-unit { min-width: 50px; }
    .co-cd-unit span { font-size: 1.2rem; }
    .co-orbit-card { padding: 22px; }
    .co-card-title { font-size: 1.05rem; }
}

/* ============================================================
   PERFORMANCE PATCH - CAREER ODYSSEY SMOOTH SCROLL
============================================================ */

/* Content visibility untuk section bawah */
.co-section {
    content-visibility: auto;
    contain-intrinsic-size: auto 3000px;
}

/* Pause animasi saat scroll */
body.is-scrolling .co-nebula,
body.is-scrolling .co-shooting-stars,
body.is-scrolling .co-float-el,
body.is-scrolling .co-orbit-ring,
body.is-scrolling .co-orbit-dot,
body.is-scrolling .co-badge-glow,
body.is-scrolling .co-icon-pulse,
body.is-scrolling .co-station-ring,
body.is-scrolling .co-signal-strength span,
body.is-scrolling .co-status-dot,
body.is-scrolling .co-status-dot-mini,
body.is-scrolling .co-cta-flame {
    animation-play-state: paused !important;
}

/* Pause animasi untuk section yang tidak terlihat */
.co-offscreen * {
    animation-play-state: paused !important;
}

/* Optimasi elemen yang sering bergerak */
.co-nebula,
.co-shooting-stars,
.co-float-el,
.co-stations-track {
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
    .co-nebula,
    .co-shooting-stars,
    .co-floating-elements {
        animation: none !important;
        opacity: 0.15 !important;
    }
    
    .co-hero-bg {
        filter: blur(30px);
    }
    
    .co-launch-panel,
    .co-mstat,
    .co-orbit-card,
    .co-adv-card,
    .co-station,
    .co-astronaut-card {
        backdrop-filter: none !important;
    }
}

/* Reduce motion untuk accessibility */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .co-nebula,
    .co-shooting-stars,
    .co-floating-elements {
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
                entry.target.classList.toggle('co-offscreen', !entry.isIntersecting);
            });
        }, {
            rootMargin: '300px 0px',
            threshold: 0
        });

        document.querySelectorAll('.co-hero, .co-section').forEach(section => {
            sectionObserver.observe(section);
        });

        const heroSection = document.querySelector('.co-hero');
        if (heroSection) {
            window.__coHeroVisible = true;
            new IntersectionObserver((entries) => {
                const entry = entries[0];
                window.__coHeroVisible = entry ? entry.isIntersecting : true;
                window.dispatchEvent(new CustomEvent('co:hero-visibility', {
                    detail: window.__coHeroVisible
                }));
            }, {
                threshold: 0.01
            }).observe(heroSection);
        }
    }

    // ===== 1. Star Field Canvas (OPTIMIZED) =====
    const canvas = document.getElementById('coStarCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d', { alpha: true });
        let width, height, stars = [], time = 0;
        let rafId = null;
        let running = true;

        function resize() {
            width = canvas.width = canvas.parentElement.offsetWidth;
            height = canvas.height = canvas.parentElement.offsetHeight;
            initStars();
        }

        function initStars() {
            stars = [];
            // Dibatasi maksimal 150 bintang untuk performa
            const numStars = Math.min(Math.floor((width * height) / 12000), 150);
            for (let i = 0; i < numStars; i++) {
                stars.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    size: Math.random() * 1.2 + 0.3,
                    opacity: Math.random() * 0.6 + 0.2,
                    twinkleSpeed: Math.random() * 0.02 + 0.01,
                    phase: Math.random() * Math.PI * 2,
                    color: ['#FFFFFF', '#10B981', '#06B6D4', '#8B5CF6'][Math.floor(Math.random() * 4)]
                });
            }
        }

        function drawStar(star) {
            const twinkle = Math.sin(time * star.twinkleSpeed + star.phase) * 0.5 + 0.5;
            ctx.beginPath();
            ctx.arc(star.x, star.y, star.size, 0, Math.PI * 2);
            ctx.fillStyle = star.color;
            ctx.globalAlpha = star.opacity * twinkle;
            // shadowBlur dihapus karena sangat membebani GPU
            ctx.fill();
            ctx.globalAlpha = 1;
        }

        let shootingStars = [];
        function addShootingStar() {
            if (Math.random() < 0.004 && shootingStars.length < 2) {
                shootingStars.push({
                    x: Math.random() * width,
                    y: Math.random() * height * 0.5,
                    length: Math.random() * 80 + 40,
                    speed: Math.random() * 8 + 6,
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
                s.life -= 0.025;
            });
        }

        function animate() {
            // Berhenti render jika tab di-background atau hero tidak terlihat
            if (!running || document.hidden || window.__coHeroVisible === false) {
                rafId = requestAnimationFrame(animate);
                return;
            }

            ctx.clearRect(0, 0, width, height);
            stars.forEach(drawStar);
            addShootingStar();
            drawShootingStars();
            time++;
            rafId = requestAnimationFrame(animate);
        }

        window.addEventListener('co:hero-visibility', function(e) {
            const visible = Boolean(e.detail);
            if (visible && !document.hidden) running = true;
            else running = false;
        });

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) running = false;
            else if (window.__coHeroVisible) running = true;
        });

        resize();
        window.addEventListener('resize', resize, { passive: true });
        animate();
    }

    // ===== 2. Typing Effect =====
    const textElement = document.getElementById('coTypingText');
    if (textElement) {
        const phrases = [
            "Lulusan kami tidak hanya siap kerja, tetapi siap memimpin inovasi di berbagai sektor.",
            "Dari startup hingga perusahaan multinasional - alumni kami ada di mana-mana.",
            "Setiap karir adalah misi luar angkasa yang menunggu untuk diluncurkan.",
            "Bergabunglah dengan 500+ alumni sukses di galaksi industri teknologi."
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

    // ===== 3. Countdown Timer =====
    function updateCountdown() {
        const now = new Date();
        const target = new Date(now.getFullYear(), 11, 31, 23, 59, 59);
        const diff = target - now;
        
        if (diff > 0) {
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const secs = Math.floor((diff % (1000 * 60)) / 1000);
            
            const setEl = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = String(val).padStart(2, '0');
            };
            setEl('coCdDays', days);
            setEl('coCdHours', hours);
            setEl('coCdMins', mins);
            setEl('coCdSecs', secs);
        }
    }
    updateCountdown();
    setInterval(updateCountdown, 1000);

    // ===== 4. Counter Animation =====
    const counters = document.querySelectorAll('.co-mstat-num[data-count]');
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

    // ===== 5. Fuel Gauge (THROTTLED) =====
    const fuelFill = document.getElementById('coFuelFill');
    let fuelTicking = false;
    if (fuelFill) {
        window.addEventListener('scroll', () => {
            if (!fuelTicking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.scrollY;
                    const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
                    const percent = maxScroll > 0 ? (scrolled / maxScroll) * 100 : 0;
                    fuelFill.style.width = percent + '%';
                    fuelTicking = false;
                });
                fuelTicking = true;
            }
        }, { passive: true });
    }

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

    // ===== 7. Toast =====
    window.coShowToast = function(message) {
        const toast = document.getElementById('coToast');
        if (!toast) return;
        const msgEl = document.getElementById('coToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // ===== 8. Back to Top (THROTTLED) =====
    const backTop = document.getElementById('coBackTop');
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
        backTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ===== 9. Parallax Nebulas (THROTTLED) =====
    const heroSection = document.querySelector('.co-hero');
    let parallaxTicking = false;
    if (heroSection && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            if (!parallaxTicking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.pageYOffset;
                    const nebulas = heroSection.querySelectorAll('.co-nebula');
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
        coShowToast('🚀 Welcome aboard Career Odyssey!');
    }, 1500);

    console.log('%c╔════════════════════════════════════════════╗', 'color:#10B981;font-weight:bold');
    console.log('%c║   CAREER ODYSSEY - Launch Your Future      ║', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c╚════════════════════════════════════════════╝', 'color:#10B981;font-weight:bold');
    console.log('%c🚀 ' + document.querySelectorAll('.co-orbit-card').length + ' career orbits available (OPTIMIZED)', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>