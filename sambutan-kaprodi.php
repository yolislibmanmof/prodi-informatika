<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Sambutan Ketua Program Studi';
$pageDesc = 'Sambutan, visi, dan misi dari Ketua Program Studi Informatika untuk calon mahasiswa dan masyarakat.';

// Fetch dynamic Kaprodi data from settings - LOGIC ASLI DIPERTAHANKAN
$kaprodiNama = getSetting('kaprodi_nama', 'Rizky Fenaldo Maulana, S.Kom., M.Kom.');
$kaprodiGelar = getSetting('kaprodi_gelar', 'S.Kom., M.Kom.');
$kaprodiSambutan = getSetting('kaprodi_sambutan', 'Selamat datang di Program Studi Informatika. Kami berkomitmen untuk menyediakan lingkungan belajar yang inovatif, kolaboratif, dan berorientasi pada masa depan. Dengan kurikulum yang terus diperbarui mengikuti perkembangan teknologi seperti Artificial Intelligence, Cybersecurity, dan Cloud Computing, kami mempersiapkan Anda bukan hanya untuk mendapatkan pekerjaan, tetapi untuk menjadi creator dan leader di dunia teknologi. Saya mengajak Anda untuk bergabung, belajar, dan tumbuh bersama kami dalam mewujudkan visi menjadi program studi informatika terkemuka yang menghasilkan lulusan berdaya saing global.');
$kaprodiFoto = getSetting('kaprodi_foto', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=600&h=700&fit=crop');

// Extract first name
$firstName = explode(' ', $kaprodiNama)[0];

// Data tambahan (bisa dikonfigurasi via settings jika perlu)
$kaprodiStats = [
    'pengalaman' => getSetting('kaprodi_pengalaman', '15'),
    'publikasi' => getSetting('kaprodi_publikasi', '25'),
    'penelitian' => getSetting('kaprodi_penelitian', '18'),
    'pengabdian' => getSetting('kaprodi_pengabdian', '12'),
];

$timelineItems = [
    ['year' => '2024', 'title' => 'Ketua Program Studi', 'desc' => 'Program Studi Informatika'],
    ['year' => '2020', 'title' => 'Sekretaris Prodi', 'desc' => 'Program Studi Informatika'],
    ['year' => '2015', 'title' => 'Dosen Tetap', 'desc' => 'Program Studi Informatika'],
    ['year' => '2012', 'title' => 'Magister Ilmu Komputer', 'desc' => 'Universitas Terkemuka'],
];

$researchAreas = [
    ['icon' => 'fa-brain', 'name' => 'Artificial Intelligence', 'count' => 12],
    ['icon' => 'fa-shield-alt', 'name' => 'Cybersecurity', 'count' => 8],
    ['icon' => 'fa-cloud', 'name' => 'Cloud Computing', 'count' => 5],
    ['icon' => 'fa-database', 'name' => 'Data Science', 'count' => 7],
];
?>

<!-- ===== HERO SECTION ENHANCED ===== -->
<section class="kap-hero">
    <canvas id="kapNetworkCanvas" class="kap-network-canvas"></canvas>
    <div class="kap-hero-bg">
        <div class="kap-orb kap-orb-1"></div>
        <div class="kap-orb kap-orb-2"></div>
        <div class="kap-orb kap-orb-3"></div>
        <div class="kap-grid-pattern"></div>
    </div>
    
    <!-- Floating Tech Elements -->
    <div class="kap-floating-elements">
        <span class="kap-float-item" style="--delay: 0s; --x: 10%; --y: 20%;">01</span>
        <span class="kap-float-item" style="--delay: 2s; --x: 85%; --y: 15%;">{}</span>
        <span class="kap-float-item" style="--delay: 4s; --x: 75%; --y: 70%;">&lt;/&gt;</span>
        <span class="kap-float-item" style="--delay: 1s; --x: 15%; --y: 75%;">#</span>
        <span class="kap-float-item" style="--delay: 3s; --x: 50%; --y: 10%;">fn()</span>
        <span class="kap-float-item" style="--delay: 5s; --x: 90%; --y: 50%;">AI</span>
        <span class="kap-float-item" style="--delay: 2.5s; --x: 5%; --y: 50%;">0x</span>
    </div>
    
    <div class="container kap-hero-content">
        <span class="kap-badge fade-up">
            <span class="kap-badge-pulse"></span>
            <i class="fas fa-user-tie"></i> Leadership Message
        </span>
        
        <h1 class="kap-hero-title fade-up">
            <span class="kap-title-line">Sambutan</span>
            <span class="kap-title-line kap-gradient-text">Kaprodi</span>
        </h1>
        
        <p class="kap-hero-subtitle fade-up">
            <span id="kapTypingText"></span><span class="kap-cursor">|</span>
        </p>
        
        <!-- Hero Stats -->
        <div class="kap-hero-stats fade-up">
            <div class="kap-stat-pill">
                <i class="fas fa-briefcase"></i>
                <div>
                    <span class="kap-stat-num" data-count="<?= $kaprodiStats['pengalaman'] ?>">0</span>
                    <span class="kap-stat-label">Tahun Pengalaman</span>
                </div>
            </div>
            <div class="kap-stat-pill">
                <i class="fas fa-book-open"></i>
                <div>
                    <span class="kap-stat-num" data-count="<?= $kaprodiStats['publikasi'] ?>">0</span>
                    <span class="kap-stat-label">Publikasi Ilmiah</span>
                </div>
            </div>
            <div class="kap-stat-pill">
                <i class="fas fa-flask"></i>
                <div>
                    <span class="kap-stat-num" data-count="<?= $kaprodiStats['penelitian'] ?>">0</span>
                    <span class="kap-stat-label">Proyek Riset</span>
                </div>
            </div>
            <div class="kap-stat-pill">
                <i class="fas fa-hands-helping"></i>
                <div>
                    <span class="kap-stat-num" data-count="<?= $kaprodiStats['pengabdian'] ?>">0</span>
                    <span class="kap-stat-label">Pengabdian</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="kap-scroll-indicator">
        <span>Scroll Down</span>
        <div class="kap-mouse"><div class="kap-wheel"></div></div>
    </div>
</section>

<!-- ===== READING PROGRESS BAR ===== -->
<div class="kap-reading-progress">
    <div class="kap-progress-fill" id="kapProgressFill"></div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<section class="kap-section" id="kapSection">
    <div class="container">
        <div class="kap-layout">
            
            <!-- Sticky Sidebar -->
            <aside class="kap-sidebar fade-up">
                
                <!-- Profile Card -->
                <div class="kap-profile-card kap-tilt">
                    <div class="kap-profile-glow"></div>
                    <div class="kap-image-frame">
                        <img src="<?= htmlspecialchars($kaprodiFoto) ?>" alt="<?= htmlspecialchars($kaprodiNama) ?>" loading="lazy">
                        <div class="kap-verified-badge" title="Verified Profile">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="kap-image-ring"></div>
                    </div>
                    <h3 class="kap-profile-name"><?= htmlspecialchars($kaprodiNama) ?></h3>
                    <p class="kap-profile-title">Ketua Program Studi Informatika</p>
                    
                    <!-- Social Links -->
                    <div class="kap-social-links">
                        <a href="#" class="kap-social-link" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="kap-social-link" title="Google Scholar"><i class="fas fa-graduation-cap"></i></a>
                        <a href="#" class="kap-social-link" title="ResearchGate"><i class="fab fa-researchgate"></i></a>
                        <a href="#" class="kap-social-link" title="ORCID"><i class="fab fa-orcid"></i></a>
                        <a href="#" class="kap-social-link" title="GitHub"><i class="fab fa-github"></i></a>
                    </div>
                    
                    <!-- Mini Stats -->
                    <div class="kap-mini-stats">
                        <div class="kap-mini-stat">
                            <i class="fas fa-book"></i>
                            <span><?= $kaprodiStats['publikasi'] ?> Publikasi</span>
                        </div>
                        <div class="kap-mini-stat">
                            <i class="fas fa-flask"></i>
                            <span><?= $kaprodiStats['penelitian'] ?> Riset</span>
                        </div>
                    </div>
                </div>

                <!-- Contact Card -->
                <div class="kap-contact-card kap-tilt">
                    <div class="kap-contact-orb"></div>
                    <div class="kap-contact-icon"><i class="fas fa-envelope"></i></div>
                    <h4>Hubungi Kaprodi</h4>
                    <p>Untuk kerjasama akademik atau pertanyaan strategis</p>
                    <a href="mailto:kaprodi@informatikapro.ac.id" class="kap-contact-btn">
                        <i class="fas fa-paper-plane"></i> Kirim Email
                    </a>
                    
                    <!-- QR Code -->
                    <div class="kap-qr-section">
                        <div class="kap-qr-code">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=mailto:kaprodi@informatikapro.ac.id&bgcolor=ffffff" alt="QR Code">
                        </div>
                        <span>Scan untuk kontak cepat</span>
                    </div>
                </div>
                
                <!-- Text Controls -->
                <div class="kap-controls-card">
                    <button class="kap-control-btn" id="kapTtsBtn" title="Bacakan Sambutan">
                        <i class="fas fa-volume-up"></i>
                        <span>Bacakan</span>
                    </button>
                    <button class="kap-control-btn" onclick="window.print()" title="Cetak Halaman">
                        <i class="fas fa-print"></i>
                        <span>Cetak</span>
                    </button>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="kap-content fade-up">
                
                <!-- Welcome Banner -->
                <div class="kap-welcome-banner kap-tilt">
                    <div class="kap-welcome-glow"></div>
                    <div class="kap-welcome-icon">
                        <i class="fas fa-hand-sparkles"></i>
                    </div>
                    <div class="kap-welcome-text">
                        <h2>Selamat Datang di Informatika Pro!</h2>
                        <p>Pesan resmi dari pimpinan Program Studi Informatika untuk Anda.</p>
                    </div>
                </div>

                <!-- Quote Carousel -->
                <div class="kap-quote-carousel" id="kapQuoteCarousel">
                    <div class="kap-quote-slide active" data-index="0">
                        <i class="fas fa-quote-left"></i>
                        <p>"Di era transformasi digital, Informatika bukan lagi sekadar tentang coding. Ini tentang menciptakan solusi yang berdampak nyata bagi masyarakat dan industri."</p>
                        <span class="kap-quote-author">— <?= htmlspecialchars($firstName) ?></span>
                    </div>
                    <div class="kap-quote-slide" data-index="1">
                        <i class="fas fa-quote-left"></i>
                        <p>"Pendidikan adalah kunci untuk membuka pintu masa depan. Kami berkomitmen memberikan pendidikan terbaik untuk setiap mahasiswa."</p>
                        <span class="kap-quote-author">— <?= htmlspecialchars($firstName) ?></span>
                    </div>
                    <div class="kap-quote-slide" data-index="2">
                        <i class="fas fa-quote-left"></i>
                        <p>"Inovasi lahir dari kolaborasi. Mari bersama-sama membangun ekosistem teknologi yang inklusif dan berkelanjutan."</p>
                        <span class="kap-quote-author">— <?= htmlspecialchars($firstName) ?></span>
                    </div>
                    
                    <div class="kap-quote-controls">
                        <button class="kap-quote-nav-btn" onclick="kapPrevQuote()"><i class="fas fa-chevron-left"></i></button>
                        <div class="kap-quote-dots">
                            <button class="kap-quote-dot active" data-index="0"></button>
                            <button class="kap-quote-dot" data-index="1"></button>
                            <button class="kap-quote-dot" data-index="2"></button>
                        </div>
                        <button class="kap-quote-nav-btn" onclick="kapNextQuote()"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                
                <!-- Sambutan Text -->
                <div class="kap-text" id="kapSambutanText">
                    <?= nl2br(htmlspecialchars($kaprodiSambutan)) ?>
                </div>

                <!-- Signature Section -->
                <div class="kap-signature-section">
                    <div class="kap-signature-anim">
                        <svg viewBox="0 0 200 60" class="kap-signature-svg">
                            <path class="kap-signature-path" d="M10,40 Q30,10 60,35 T110,35 T160,35 T190,35" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="kap-signature-info">
                        <span class="kap-sign-name"><?= htmlspecialchars($firstName) ?></span>
                        <span class="kap-sign-title">Ketua Program Studi Informatika</span>
                        <div class="kap-sign-line"></div>
                    </div>
                </div>

                <!-- Visi Misi Grid -->
                <div class="kap-vm-section">
                    <h3 class="kap-section-title">
                        <span class="kap-title-icon"><i class="fas fa-bullseye"></i></span>
                        Visi & Misi Kami
                    </h3>
                    <div class="kap-vm-grid">
                        <div class="kap-vm-card kap-tilt">
                            <div class="kap-vm-glow"></div>
                            <span class="kap-vm-number">01</span>
                            <div class="kap-vm-icon"><i class="fas fa-eye"></i></div>
                            <h4>Visi Kami</h4>
                            <p>Menjadi program studi informatika unggul yang menghasilkan inovator teknologi beretika dan berdaya saing global pada tahun 2030.</p>
                            <div class="kap-vm-progress">
                                <div class="kap-vm-progress-bar" data-progress="85">
                                    <span>85% Tercapai</span>
                                </div>
                            </div>
                            <div class="kap-vm-tag">Vision 2030</div>
                        </div>
                        <div class="kap-vm-card kap-tilt">
                            <div class="kap-vm-glow"></div>
                            <span class="kap-vm-number">02</span>
                            <div class="kap-vm-icon"><i class="fas fa-rocket"></i></div>
                            <h4>Misi Kami</h4>
                            <p>Menyelenggarakan pendidikan berkualitas, penelitian inovatif, dan pengabdian masyarakat berbasis teknologi yang berkelanjutan.</p>
                            <div class="kap-vm-progress">
                                <div class="kap-vm-progress-bar" data-progress="72">
                                    <span>72% Tercapai</span>
                                </div>
                            </div>
                            <div class="kap-vm-tag">Mission Driven</div>
                        </div>
                    </div>
                </div>
                
                <!-- Strategic Pillars -->
                <div class="kap-pillars-section">
                    <h3 class="kap-section-title">
                        <span class="kap-title-icon"><i class="fas fa-layer-group"></i></span>
                        Pilar Strategis
                    </h3>
                    <div class="kap-pillars-grid">
                        <div class="kap-pillar-item kap-tilt">
                            <div class="kap-pillar-icon"><i class="fas fa-graduation-cap"></i></div>
                            <h5>Pendidikan</h5>
                            <p>Kurikulum berbasis industri dengan standar global ACM/IEEE</p>
                        </div>
                        <div class="kap-pillar-item kap-tilt">
                            <div class="kap-pillar-icon"><i class="fas fa-microscope"></i></div>
                            <h5>Penelitian</h5>
                            <p>Riset inovatif di bidang AI, Cybersecurity, dan Cloud</p>
                        </div>
                        <div class="kap-pillar-item kap-tilt">
                            <div class="kap-pillar-icon"><i class="fas fa-handshake"></i></div>
                            <h5>Kerjasama</h5>
                            <p>Partnership dengan industri dan universitas global</p>
                        </div>
                        <div class="kap-pillar-item kap-tilt">
                            <div class="kap-pillar-icon"><i class="fas fa-users"></i></div>
                            <h5>Pengabdian</h5>
                            <p>Kontribusi nyata untuk masyarakat melalui teknologi</p>
                        </div>
                    </div>
                </div>

                <!-- Research Areas -->
                <div class="kap-research-section">
                    <h3 class="kap-section-title">
                        <span class="kap-title-icon"><i class="fas fa-flask"></i></span>
                        Bidang Riset Unggulan
                    </h3>
                    <div class="kap-research-grid">
                        <?php foreach ($researchAreas as $area): ?>
                        <div class="kap-research-item kap-tilt">
                            <div class="kap-research-icon"><i class="fas <?= $area['icon'] ?>"></i></div>
                            <h5><?= $area['name'] ?></h5>
                            <span class="kap-research-count"><?= $area['count'] ?> Publikasi</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="kap-timeline-section">
                    <h3 class="kap-section-title">
                        <span class="kap-title-icon"><i class="fas fa-history"></i></span>
                        Perjalanan Karir
                    </h3>
                    <div class="kap-timeline">
                        <?php foreach ($timelineItems as $index => $item): ?>
                        <div class="kap-timeline-item <?= $index === 0 ? 'kap-current' : '' ?>" style="animation-delay: <?= $index * 0.1 ?>s">
                            <div class="kap-timeline-dot"></div>
                            <div class="kap-timeline-content">
                                <span class="kap-timeline-year"><?= $item['year'] ?></span>
                                <h5><?= $item['title'] ?></h5>
                                <p><?= $item['desc'] ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tech Stack Pills -->
                <div class="kap-tech-section">
                    <h3 class="kap-section-title">
                        <span class="kap-title-icon"><i class="fas fa-microchip"></i></span>
                        Fokus Teknologi
                    </h3>
                    <div class="kap-pills-container">
                        <span class="kap-pill"><i class="fas fa-brain"></i> AI & ML</span>
                        <span class="kap-pill"><i class="fas fa-shield-alt"></i> Cybersecurity</span>
                        <span class="kap-pill"><i class="fas fa-cloud"></i> Cloud Computing</span>
                        <span class="kap-pill"><i class="fas fa-database"></i> Data Science</span>
                        <span class="kap-pill"><i class="fas fa-mobile-alt"></i> Mobile Dev</span>
                        <span class="kap-pill"><i class="fas fa-code"></i> Software Eng</span>
                        <span class="kap-pill"><i class="fas fa-network-wired"></i> IoT</span>
                        <span class="kap-pill"><i class="fas fa-vr-cardboard"></i> AR/VR</span>
                    </div>
                </div>

                <!-- Share Section -->
                <div class="kap-share-section">
                    <h4><i class="fas fa-share-alt"></i> Bagikan Sambutan</h4>
                    <div class="kap-share-buttons">
                        <a href="https://facebook.com/sharer/sharer.php?u=" target="_blank" class="kap-share-btn" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://twitter.com/intent/tweet?url=&text=" target="_blank" class="kap-share-btn" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="https://linkedin.com/sharing/share-offsite/?url=" target="_blank" class="kap-share-btn" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="https://wa.me/?text=" target="_blank" class="kap-share-btn" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        <button class="kap-share-btn kap-copy-link" id="kapCopyLink" title="Copy Link">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== RELATED LINKS ===== -->
<section class="kap-related-section">
    <div class="container">
        <h2 class="kap-related-title">Jelajahi Lebih Lanjut</h2>
        <p class="kap-related-subtitle">Temukan informasi penting lainnya tentang Program Studi Informatika</p>
        <div class="kap-related-grid">
            <a href="kurikulum.php" class="kap-related-card kap-tilt">
                <div class="kap-related-icon"><i class="fas fa-book-open"></i></div>
                <h4>Kurikulum</h4>
                <p>Lihat struktur kurikulum 8 semester yang terintegrasi industri</p>
                <span class="kap-related-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="dosen.php" class="kap-related-card kap-tilt">
                <div class="kap-related-icon"><i class="fas fa-users"></i></div>
                <h4>Dosen & Staff</h4>
                <p>Tim pengajar berkualitas dengan pengalaman industri</p>
                <span class="kap-related-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="prestasi.php" class="kap-related-card kap-tilt">
                <div class="kap-related-icon"><i class="fas fa-trophy"></i></div>
                <h4>Prestasi</h4>
                <p>Pencapaian mahasiswa di tingkat nasional & internasional</p>
                <span class="kap-related-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
        </div>
    </div>
</section>

<!-- Toast Notification -->
<div id="kapToast" class="kap-toast">
    <i class="fas fa-check-circle"></i>
    <span id="kapToastMsg">Berhasil!</span>
</div>

<!-- ============================================================
     SPECTACULAR STYLES (prefix: kap-)
============================================================ -->
<style>
:root {
    --kap-primary: #10B981;
    --kap-primary-dark: #047857;
    --kap-primary-light: #D1FAE5;
    --kap-accent: #06B6D4;
    --kap-dark: #0F172A;
    --kap-gray-50: #F8FAFC;
    --kap-gray-100: #F1F5F9;
    --kap-gray-200: #E2E8F0;
    --kap-gray-600: #475569;
    --kap-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --kap-shadow-lg: 0 20px 50px rgba(15,23,42,.1);
    --kap-shadow-glow: 0 0 30px rgba(16,185,129,0.3);
    --kap-font-mono: 'JetBrains Mono', 'Courier New', monospace;
}

body.gl-dark {
    --kap-gray-50: #0B1120;
    --kap-gray-100: rgba(255,255,255,.08);
    --kap-gray-200: rgba(255,255,255,.1);
    --kap-gray-600: #94A3B8;
}

/* ===== GLOBAL ===== */
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--kap-gray-50); }
::-webkit-scrollbar-thumb { background: linear-gradient(180deg, var(--kap-primary), var(--kap-primary-dark)); border-radius: 5px; }

/* ===== READING PROGRESS ===== */
.kap-reading-progress {
    position: fixed; top: 0; left: 0; right: 0; height: 3px;
    background: rgba(16,185,129,0.1); z-index: 9998;
}
.kap-progress-fill {
    height: 100%; width: 0; background: linear-gradient(90deg, var(--kap-primary), var(--kap-accent));
    box-shadow: 0 0 10px rgba(16,185,129,0.6); transition: width 0.1s ease-out;
}

/* ===== HERO ===== */
.kap-hero {
    background: linear-gradient(135deg, #0F172A 0%, #047857 100%);
    padding: 160px 20px 100px; position: relative; overflow: hidden;
    min-height: 90vh; display: flex; align-items: center; justify-content: center;
}
.kap-network-canvas { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 1; opacity: 0.5; }
.kap-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }
.kap-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.4; animation: kapFloat 15s infinite ease-in-out; }
.kap-orb-1 { width: 500px; height: 500px; background: var(--kap-primary); top: -150px; right: -150px; }
.kap-orb-2 { width: 350px; height: 350px; background: #34D399; bottom: -100px; left: -100px; animation-delay: -5s; }
.kap-orb-3 { width: 250px; height: 250px; background: var(--kap-accent); top: 40%; right: 20%; animation-delay: -10s; }
.kap-grid-pattern {
    position: absolute; inset: 0;
    background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    mask-image: radial-gradient(ellipse at center, black 40%, transparent 70%);
}
@keyframes kapFloat { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(30px, -30px); } }

/* Floating Tech Elements */
.kap-floating-elements {
    position: absolute; inset: 0; z-index: 3; pointer-events: none;
}
.kap-float-item {
    position: absolute; font-family: var(--kap-font-mono);
    font-size: 1.2rem; color: rgba(52, 211, 153, 0.4);
    font-weight: 700; animation: kapFloatItem 8s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
}
@keyframes kapFloatItem {
    0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.4; }
    50% { transform: translateY(-30px) rotate(10deg); opacity: 0.8; }
}

.kap-hero-content { position: relative; z-index: 4; text-align: center; color: #fff; max-width: 900px; margin: 0 auto; }

.kap-badge {
    display: inline-flex; align-items: center; gap: 10px; padding: 10px 22px;
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
    border-radius: 50px; font-size: .9rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    position: relative; font-weight: 600; letter-spacing: 0.5px;
}
.kap-badge-pulse {
    position: absolute; inset: 0; border-radius: 50px; border: 2px solid var(--kap-primary);
    animation: kapBadgePulse 2s infinite;
}
@keyframes kapBadgePulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.3); opacity: 0; } }

.kap-hero-title {
    font-size: clamp(3rem, 8vw, 6rem); font-weight: 900;
    margin-bottom: 20px; letter-spacing: -0.03em; line-height: 1;
}
.kap-title-line { display: block; }
.kap-gradient-text {
    background: linear-gradient(135deg, #34D399 0%, #06B6D4 50%, #F59E0B 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 200%;
    animation: kapGradientShift 5s ease infinite;
}
@keyframes kapGradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

.kap-hero-subtitle {
    font-size: 1.2rem; opacity: .9; max-width: 650px; margin: 0 auto 40px;
    line-height: 1.7; display: inline-block; min-height: 1.7em;
}
.kap-cursor { animation: kapBlink 1s infinite; color: var(--kap-primary); font-weight: 300; }
@keyframes kapBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

/* Hero Stats */
.kap-hero-stats { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; }
.kap-stat-pill {
    display: flex; align-items: center; gap: 12px; padding: 14px 24px;
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
    border-radius: 16px; backdrop-filter: blur(12px); transition: all 0.3s;
}
.kap-stat-pill:hover { background: rgba(255,255,255,.15); transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.2); }
.kap-stat-pill i { font-size: 1.5rem; color: #34D399; }
.kap-stat-num { display: block; font-size: 1.5rem; font-weight: 800; color: #fff; line-height: 1; font-family: 'Space Grotesk', sans-serif; }
.kap-stat-label { font-size: 0.75rem; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; display: block; }

.kap-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
    z-index: 4; display: flex; flex-direction: column; align-items: center;
    gap: 8px; color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.kap-mouse { width: 24px; height: 40px; border: 2px solid rgba(255,255,255,.3); border-radius: 12px; position: relative; }
.kap-wheel {
    width: 4px; height: 8px; background: var(--kap-primary); border-radius: 2px;
    position: absolute; top: 6px; left: 50%; transform: translateX(-50%);
    animation: kapScroll 2s infinite;
}
@keyframes kapScroll { 0% { opacity: 1; transform: translateX(-50%) translateY(0); } 100% { opacity: 0; transform: translateX(-50%) translateY(12px); } }

/* ===== MAIN SECTION ===== */
.kap-section { padding: 80px 20px 100px; background: var(--kap-gray-50); position: relative; }

.kap-layout { display: grid; grid-template-columns: 380px 1fr; gap: 50px; align-items: flex-start; }

/* ===== SIDEBAR ===== */
.kap-sidebar { position: sticky; top: 100px; display: flex; flex-direction: column; gap: 20px; }

/* Profile Card */
.kap-profile-card {
    background: #fff; border-radius: 24px; padding: 28px;
    box-shadow: var(--kap-shadow-lg); text-align: center;
    position: relative; overflow: hidden; transform-style: preserve-3d;
    border: 1px solid var(--kap-gray-100);
}
body.gl-dark .kap-profile-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.kap-profile-glow {
    position: absolute; top: -50%; right: -50%; width: 300px; height: 300px;
    background: radial-gradient(circle, rgba(16,185,129,0.15), transparent 70%);
    pointer-events: none;
}

.kap-image-frame {
    position: relative; width: 160px; height: 160px; margin: 0 auto 20px;
    border-radius: 50%; overflow: hidden; border: 4px solid #fff;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
body.gl-dark .kap-image-frame { border-color: #0F172A; }
.kap-image-frame img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
.kap-image-frame:hover img { transform: scale(1.1); }

.kap-verified-badge {
    position: absolute; bottom: 5px; right: 5px;
    width: 32px; height: 32px; border-radius: 50%;
    background: linear-gradient(135deg, #10B981, #047857);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; border: 3px solid #fff;
    box-shadow: 0 4px 12px rgba(16,185,129,0.4);
}

.kap-image-ring {
    position: absolute; inset: -6px; border-radius: 50%;
    border: 2px dashed var(--kap-primary); opacity: 0.3;
    animation: kapSpin 20s linear infinite;
}
@keyframes kapSpin { to { transform: rotate(360deg); } }

.kap-profile-name {
    font-size: 1.15rem; font-weight: 800; color: var(--kap-dark);
    margin: 0 0 4px 0; line-height: 1.3;
}
body.gl-dark .kap-profile-name { color: #F1F5F9; }
.kap-profile-title {
    font-size: 0.85rem; color: var(--kap-primary);
    font-weight: 600; margin: 0 0 16px 0;
}

/* Social Links */
.kap-social-links {
    display: flex; justify-content: center; gap: 8px;
    margin-bottom: 20px;
}
.kap-social-link {
    width: 36px; height: 36px; border-radius: 10px;
    background: var(--kap-gray-100); color: var(--kap-gray-600);
    display: flex; align-items: center; justify-content: center;
    text-decoration: none; transition: all 0.3s; font-size: 0.9rem;
}
body.gl-dark .kap-social-link { background: rgba(255,255,255,.08); color: #CBD5E1; }
.kap-social-link:hover {
    background: var(--kap-primary); color: #fff;
    transform: translateY(-3px); box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}

/* Mini Stats */
.kap-mini-stats {
    display: flex; gap: 8px; justify-content: center;
    padding-top: 16px; border-top: 1px dashed var(--kap-gray-200);
}
body.gl-dark .kap-mini-stats { border-color: rgba(255,255,255,.1); }
.kap-mini-stat {
    display: flex; align-items: center; gap: 6px;
    padding: 6px 12px; background: var(--kap-gray-50);
    border-radius: 20px; font-size: 0.75rem; color: var(--kap-gray-600);
    font-weight: 600;
}
body.gl-dark .kap-mini-stat { background: rgba(255,255,255,.05); color: #CBD5E1; }
.kap-mini-stat i { color: var(--kap-primary); font-size: 0.8rem; }

/* Contact Card */
.kap-contact-card {
    background: linear-gradient(135deg, var(--kap-primary), var(--kap-primary-dark));
    color: #fff; padding: 28px; border-radius: 20px; text-align: center;
    position: relative; overflow: hidden;
}
.kap-contact-orb {
    position: absolute; top: -60%; right: -40%; width: 200px; height: 200px;
    background: rgba(255,255,255,0.1); border-radius: 50%;
    filter: blur(40px);
}
.kap-contact-icon {
    width: 56px; height: 56px; background: rgba(255,255,255,0.2);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px; font-size: 1.5rem; position: relative; z-index: 1;
}
.kap-contact-card h4 {
    font-size: 1.1rem; margin-bottom: 8px; font-weight: 700;
    position: relative; z-index: 1;
}
.kap-contact-card p {
    font-size: .85rem; opacity: .9; margin-bottom: 20px;
    line-height: 1.5; position: relative; z-index: 1;
}
.kap-contact-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 12px; background: #fff; color: var(--kap-primary-dark);
    border-radius: 12px; text-decoration: none; font-weight: 700; font-size: .9rem;
    transition: all .3s; position: relative; z-index: 1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.kap-contact-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.2); }

/* QR Section */
.kap-qr-section {
    margin-top: 20px; padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.2);
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    position: relative; z-index: 1;
}
.kap-qr-code {
    width: 80px; height: 80px; background: #fff; padding: 6px;
    border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.kap-qr-code img { width: 100%; height: 100%; }
.kap-qr-section span { font-size: 0.7rem; opacity: 0.8; }

/* Controls Card */
.kap-controls-card {
    display: flex; gap: 8px;
}
.kap-control-btn {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 16px; background: #fff; border: 1px solid var(--kap-gray-200);
    border-radius: 12px; color: var(--kap-gray-600);
    cursor: pointer; font-size: 0.85rem; font-weight: 600;
    transition: all 0.3s; font-family: inherit;
}
body.gl-dark .kap-control-btn { background: #1E293B; border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.kap-control-btn:hover {
    background: var(--kap-primary); color: #fff; border-color: var(--kap-primary);
    transform: translateY(-2px);
}
.kap-control-btn i { font-size: 1rem; }

/* ===== MAIN CONTENT ===== */
.kap-content { padding-top: 10px; }

/* Welcome Banner */
.kap-welcome-banner {
    background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(6,182,212,0.05));
    border: 1px solid rgba(16,185,129,0.2);
    border-radius: 20px; padding: 24px; margin-bottom: 32px;
    display: flex; align-items: center; gap: 20px;
    position: relative; overflow: hidden; transform-style: preserve-3d;
}
body.gl-dark .kap-welcome-banner { background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(6,182,212,0.05)); }

.kap-welcome-glow {
    position: absolute; top: -50%; left: -50%; width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(16,185,129,0.2), transparent 70%);
    filter: blur(40px);
}

.kap-welcome-icon {
    width: 60px; height: 60px; border-radius: 16px;
    background: linear-gradient(135deg, var(--kap-primary), var(--kap-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; flex-shrink: 0; position: relative; z-index: 1;
    box-shadow: 0 8px 20px rgba(16,185,129,0.3);
}
.kap-welcome-text { position: relative; z-index: 1; }
.kap-welcome-text h2 { font-size: 1.3rem; margin: 0 0 4px 0; color: var(--kap-dark); font-weight: 800; }
body.gl-dark .kap-welcome-text h2 { color: #F1F5F9; }
.kap-welcome-text p { font-size: 0.9rem; color: var(--kap-gray-600); margin: 0; }
body.gl-dark .kap-welcome-text p { color: #94A3B8; }

/* Quote Carousel */
.kap-quote-carousel {
    position: relative; margin-bottom: 40px;
    background: #fff; border-radius: 20px; padding: 32px;
    border: 1px solid var(--kap-gray-100);
    box-shadow: var(--kap-shadow-sm); overflow: hidden;
}
body.gl-dark .kap-quote-carousel { background: #1E293B; border-color: rgba(255,255,255,.08); }

.kap-quote-slide {
    display: none; position: relative;
    animation: kapFadeIn 0.5s ease;
}
.kap-quote-slide.active { display: block; }
@keyframes kapFadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

.kap-quote-slide > i {
    font-size: 3rem; color: var(--kap-primary); opacity: 0.2;
    position: absolute; top: -10px; left: -5px;
}
.kap-quote-slide p {
    font-size: 1.2rem; font-style: italic; color: var(--kap-dark);
    margin: 0 0 16px 0; line-height: 1.7; font-weight: 500;
    padding-left: 40px;
}
body.gl-dark .kap-quote-slide p { color: #E2E8F0; }
.kap-quote-author {
    display: block; font-size: 0.9rem; color: var(--kap-primary);
    font-weight: 700; padding-left: 40px;
}

.kap-quote-controls {
    display: flex; align-items: center; justify-content: center;
    gap: 16px; margin-top: 20px;
}
.kap-quote-nav-btn {
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--kap-gray-100); border: none;
    color: var(--kap-gray-600); cursor: pointer; font-size: 0.85rem;
    transition: all 0.3s; display: flex; align-items: center; justify-content: center;
}
body.gl-dark .kap-quote-nav-btn { background: rgba(255,255,255,.08); color: #CBD5E1; }
.kap-quote-nav-btn:hover {
    background: var(--kap-primary); color: #fff;
    transform: scale(1.1);
}
.kap-quote-dots { display: flex; gap: 8px; }
.kap-quote-dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: var(--kap-gray-200); border: none;
    cursor: pointer; transition: all 0.3s; padding: 0;
}
body.gl-dark .kap-quote-dot { background: rgba(255,255,255,.1); }
.kap-quote-dot.active { background: var(--kap-primary); width: 28px; border-radius: 5px; }

/* Sambutan Text */
.kap-text {
    font-size: 1.05rem; line-height: 1.9; color: var(--kap-gray-600);
    margin-bottom: 32px; padding: 0 4px;
}
body.gl-dark .kap-text { color: #CBD5E1; }
.kap-text p { margin-bottom: 20px; }
.kap-text strong { color: var(--kap-primary-dark); font-weight: 700; }
body.gl-dark .kap-text strong { color: #34D399; }

/* Signature Section */
.kap-signature-section {
    margin: 48px 0; padding: 32px 0;
    border-top: 1px solid var(--kap-gray-200);
    border-bottom: 1px solid var(--kap-gray-200);
    display: flex; align-items: center; gap: 32px;
}
body.gl-dark .kap-signature-section { border-color: rgba(255,255,255,.1); }

.kap-signature-anim {
    width: 180px; flex-shrink: 0;
}
.kap-signature-svg {
    width: 100%; height: auto;
}
.kap-signature-path {
    stroke-dasharray: 300;
    stroke-dashoffset: 300;
    animation: kapSignAnim 3s ease-out forwards;
    animation-delay: 0.5s;
}
@keyframes kapSignAnim {
    to { stroke-dashoffset: 0; }
}

.kap-signature-info { display: flex; flex-direction: column; gap: 4px; }
.kap-sign-name {
    font-family: 'Brush Script MT', 'Segoe Script', cursive;
    font-size: 2.2rem; color: var(--kap-primary); line-height: 1;
}
.kap-sign-title { font-size: .9rem; color: var(--kap-gray-600); font-weight: 500; }
body.gl-dark .kap-sign-title { color: #94A3B8; }
.kap-sign-line {
    width: 60px; height: 3px; background: linear-gradient(90deg, var(--kap-primary), transparent);
    margin-top: 8px; border-radius: 2px;
}

/* Section Titles */
.kap-section-title {
    font-size: 1.5rem; font-weight: 800; color: var(--kap-dark);
    margin-bottom: 28px; display: flex; align-items: center; gap: 12px;
}
body.gl-dark .kap-section-title { color: #F1F5F9; }
.kap-title-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, var(--kap-primary), var(--kap-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
}

/* Visi Misi Grid */
.kap-vm-section { margin-bottom: 48px; }
.kap-vm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.kap-vm-card {
    background: #fff; padding: 32px; border-radius: 20px;
    border: 1px solid var(--kap-gray-100); transition: all .3s;
    position: relative; overflow: hidden; transform-style: preserve-3d;
}
body.gl-dark .kap-vm-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.kap-vm-card::before {
    content: ''; position: absolute; inset: 0; border-radius: 20px; padding: 1px;
    background: conic-gradient(from 0deg, transparent, var(--kap-primary), transparent 30%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none;
}
.kap-vm-card:hover::before { opacity: 1; animation: kapRotateBorder 4s linear infinite; }
@keyframes kapRotateBorder { to { transform: rotate(360deg); } }

.kap-vm-card:hover { transform: translateY(-6px); box-shadow: var(--kap-shadow-lg); border-color: var(--kap-primary); }

.kap-vm-glow {
    position: absolute; top: -50%; right: -50%; width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(16,185,129,0.1), transparent 70%);
    opacity: 0; transition: opacity 0.4s; pointer-events: none;
}
.kap-vm-card:hover .kap-vm-glow { opacity: 1; }

.kap-vm-number {
    position: absolute; top: 20px; right: 24px;
    font-family: 'Space Grotesk', sans-serif; font-size: 3rem;
    font-weight: 900; color: var(--kap-primary); opacity: 0.1;
    line-height: 1;
}

.kap-vm-icon {
    width: 56px; height: 56px; background: var(--kap-primary-light);
    color: var(--kap-primary-dark); border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; margin-bottom: 20px; position: relative; z-index: 1;
}
body.gl-dark .kap-vm-icon { background: rgba(16,185,129,0.15); color: #34D399; }
.kap-vm-card h4 { font-size: 1.2rem; margin-bottom: 12px; color: var(--kap-dark); position: relative; z-index: 1; font-weight: 800; }
body.gl-dark .kap-vm-card h4 { color: #F1F5F9; }
.kap-vm-card p { font-size: .95rem; color: var(--kap-gray-600); margin: 0 0 20px 0; line-height: 1.6; position: relative; z-index: 1; }
body.gl-dark .kap-vm-card p { color: #94A3B8; }

/* Progress Bar */
.kap-vm-progress {
    margin-bottom: 16px; position: relative; z-index: 1;
}
.kap-vm-progress-bar {
    height: 8px; background: var(--kap-gray-100);
    border-radius: 4px; overflow: hidden; position: relative;
}
body.gl-dark .kap-vm-progress-bar { background: rgba(255,255,255,.1); }
.kap-vm-progress-bar::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(90deg, var(--kap-primary), var(--kap-accent));
    width: 0; transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 4px;
}
.kap-vm-progress-bar.animated::before { width: var(--progress-width); }
.kap-vm-progress-bar span {
    position: absolute; top: -24px; right: 0;
    font-size: 0.75rem; font-weight: 700; color: var(--kap-primary);
    font-family: var(--kap-font-mono);
}

.kap-vm-tag {
    display: inline-block; padding: 6px 12px;
    background: rgba(16,185,129,0.1); color: var(--kap-primary);
    border-radius: 6px; font-size: 0.7rem; font-weight: 700;
    font-family: var(--kap-font-mono); position: relative; z-index: 1;
    text-transform: uppercase; letter-spacing: 0.5px;
}

/* Strategic Pillars */
.kap-pillars-section { margin-bottom: 48px; }
.kap-pillars-grid {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
}
.kap-pillar-item {
    background: #fff; padding: 24px; border-radius: 16px;
    border: 1px solid var(--kap-gray-100); text-align: center;
    transition: all 0.3s; transform-style: preserve-3d;
}
body.gl-dark .kap-pillar-item { background: #1E293B; border-color: rgba(255,255,255,.08); }
.kap-pillar-item:hover {
    transform: translateY(-6px);
    box-shadow: var(--kap-shadow-lg);
    border-color: var(--kap-primary);
}
.kap-pillar-icon {
    width: 56px; height: 56px; margin: 0 auto 16px;
    background: linear-gradient(135deg, var(--kap-primary-light), rgba(209,250,229,0.3));
    color: var(--kap-primary-dark); border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
}
body.gl-dark .kap-pillar-icon { background: rgba(16,185,129,0.15); color: #34D399; }
.kap-pillar-item h5 { font-size: 1rem; margin: 0 0 8px 0; color: var(--kap-dark); font-weight: 700; }
body.gl-dark .kap-pillar-item h5 { color: #F1F5F9; }
.kap-pillar-item p { font-size: 0.85rem; color: var(--kap-gray-600); margin: 0; line-height: 1.5; }
body.gl-dark .kap-pillar-item p { color: #94A3B8; }

/* Research Areas */
.kap-research-section { margin-bottom: 48px; }
.kap-research-grid {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
}
.kap-research-item {
    background: #fff; padding: 20px; border-radius: 14px;
    border: 1px solid var(--kap-gray-100); text-align: center;
    transition: all 0.3s; transform-style: preserve-3d;
}
body.gl-dark .kap-research-item { background: #1E293B; border-color: rgba(255,255,255,.08); }
.kap-research-item:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: var(--kap-shadow-md);
    border-color: var(--kap-primary);
}
.kap-research-icon {
    width: 48px; height: 48px; margin: 0 auto 12px;
    background: var(--kap-primary); color: #fff;
    border-radius: 12px; display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
}
.kap-research-item h5 { font-size: 0.9rem; margin: 0 0 6px 0; color: var(--kap-dark); font-weight: 700; }
body.gl-dark .kap-research-item h5 { color: #F1F5F9; }
.kap-research-count {
    font-size: 0.75rem; color: var(--kap-primary);
    font-weight: 600; font-family: var(--kap-font-mono);
}

/* Timeline */
.kap-timeline-section { margin-bottom: 48px; }
.kap-timeline { position: relative; padding-left: 30px; }
.kap-timeline::before {
    content: ''; position: absolute; left: 8px; top: 0; bottom: 0;
    width: 2px; background: linear-gradient(180deg, var(--kap-primary), var(--kap-gray-200));
}
body.gl-dark .kap-timeline::before { background: linear-gradient(180deg, var(--kap-primary), rgba(255,255,255,.1)); }

.kap-timeline-item {
    position: relative; padding: 0 0 32px 24px;
    opacity: 0; animation: kapTimelineSlide 0.6s ease forwards;
}
@keyframes kapTimelineSlide {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

.kap-timeline-dot {
    position: absolute; left: -27px; top: 4px;
    width: 18px; height: 18px; border-radius: 50%;
    background: #fff; border: 3px solid var(--kap-gray-200);
    transition: all 0.3s;
}
body.gl-dark .kap-timeline-dot { background: #1E293B; border-color: rgba(255,255,255,.1); }
.kap-timeline-item.kap-current .kap-timeline-dot {
    background: var(--kap-primary); border-color: var(--kap-primary);
    box-shadow: 0 0 0 6px rgba(16,185,129,0.2);
}

.kap-timeline-content {
    background: #fff; padding: 20px; border-radius: 14px;
    border: 1px solid var(--kap-gray-100); transition: all 0.3s;
}
body.gl-dark .kap-timeline-content { background: #1E293B; border-color: rgba(255,255,255,.08); }
.kap-timeline-item:hover .kap-timeline-content {
    transform: translateX(8px);
    box-shadow: var(--kap-shadow-sm);
    border-color: var(--kap-primary);
}

.kap-timeline-year {
    display: inline-block; padding: 4px 12px;
    background: var(--kap-primary-light); color: var(--kap-primary-dark);
    border-radius: 6px; font-size: 0.8rem; font-weight: 700;
    font-family: var(--kap-font-mono); margin-bottom: 8px;
}
body.gl-dark .kap-timeline-year { background: rgba(16,185,129,0.15); color: #34D399; }
.kap-timeline-content h5 { font-size: 1rem; margin: 0 0 4px 0; color: var(--kap-dark); font-weight: 700; }
body.gl-dark .kap-timeline-content h5 { color: #F1F5F9; }
.kap-timeline-content p { font-size: 0.85rem; color: var(--kap-gray-600); margin: 0; }
body.gl-dark .kap-timeline-content p { color: #94A3B8; }

/* Tech Pills */
.kap-tech-section { margin-bottom: 48px; }
.kap-pills-container { display: flex; flex-wrap: wrap; gap: 10px; }
.kap-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 18px; background: #fff; border: 1px solid var(--kap-gray-200);
    border-radius: 50px; font-size: 0.85rem; color: var(--kap-gray-600);
    transition: all 0.3s; cursor: default; font-weight: 500;
}
body.gl-dark .kap-pill { background: #1E293B; border-color: rgba(255,255,255,0.1); color: #CBD5E1; }
.kap-pill i { color: var(--kap-primary); font-size: 0.9rem; }
.kap-pill:hover {
    background: var(--kap-primary); color: #fff; border-color: var(--kap-primary);
    transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}
.kap-pill:hover i { color: #fff; }

/* Share Section */
.kap-share-section {
    background: linear-gradient(135deg, rgba(16,185,129,0.05), rgba(6,182,212,0.05));
    border: 1px solid rgba(16,185,129,0.15);
    border-radius: 20px; padding: 24px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 16px;
}
.kap-share-section h4 {
    font-size: 1rem; color: var(--kap-dark); margin: 0;
    display: flex; align-items: center; gap: 10px; font-weight: 700;
}
body.gl-dark .kap-share-section h4 { color: #F1F5F9; }
.kap-share-section h4 i { color: var(--kap-primary); }

.kap-share-buttons { display: flex; gap: 8px; }
.kap-share-btn {
    width: 40px; height: 40px; border-radius: 10px;
    background: #fff; border: 1px solid var(--kap-gray-200);
    color: var(--kap-gray-600); display: flex; align-items: center; justify-content: center;
    text-decoration: none; transition: all 0.3s; font-size: 1rem;
    cursor: pointer;
}
body.gl-dark .kap-share-btn { background: #1E293B; border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.kap-share-btn:hover {
    background: var(--kap-primary); color: #fff; border-color: var(--kap-primary);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 16px rgba(16,185,129,0.3);
}

/* ===== RELATED SECTION ===== */
.kap-related-section {
    padding: 80px 20px; background: var(--kap-dark);
    color: #fff; text-align: center;
}
.kap-related-title {
    font-size: 2rem; font-weight: 800; margin-bottom: 12px;
}
.kap-related-subtitle {
    font-size: 1rem; color: rgba(255,255,255,0.7); margin-bottom: 48px;
    max-width: 500px; margin-left: auto; margin-right: auto;
}
.kap-related-grid {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 24px; max-width: 1000px; margin: 0 auto;
}
.kap-related-card {
    background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 20px; padding: 32px; text-align: center;
    text-decoration: none; color: #fff; transition: all 0.3s;
    position: relative; overflow: hidden; transform-style: preserve-3d;
}
.kap-related-card:hover {
    background: rgba(16,185,129,0.1); border-color: var(--kap-primary);
    transform: translateY(-8px);
}
.kap-related-icon {
    width: 64px; height: 64px; margin: 0 auto 20px;
    background: linear-gradient(135deg, var(--kap-primary), var(--kap-primary-dark));
    color: #fff; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem;
}
.kap-related-card h4 { font-size: 1.2rem; margin: 0 0 12px 0; font-weight: 700; }
.kap-related-card p { font-size: 0.9rem; color: rgba(255,255,255,0.7); margin: 0 0 20px 0; line-height: 1.5; }
.kap-related-arrow {
    display: inline-flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; border-radius: 50%;
    background: rgba(255,255,255,0.1); color: var(--kap-primary);
    transition: all 0.3s;
}
.kap-related-card:hover .kap-related-arrow {
    background: var(--kap-primary); color: #fff;
    transform: translateX(5px);
}

/* ===== TOAST ===== */
.kap-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--kap-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    display: flex; align-items: center; gap: 10px;
    font-size: 0.9rem; opacity: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10000; font-weight: 500;
}
body.gl-dark .kap-toast { background: #fff; color: var(--kap-dark); }
.kap-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.kap-toast i { color: var(--kap-primary); font-size: 1.2rem; }

/* ===== ANIMATIONS ===== */
.fade-up { opacity: 0; transform: translateY(30px); transition: opacity 0.8s ease, transform 0.8s ease; }
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 968px) {
    .kap-layout { grid-template-columns: 1fr; }
    .kap-sidebar { position: static; max-width: 400px; margin: 0 auto; }
    .kap-vm-grid { grid-template-columns: 1fr; }
    .kap-pillars-grid { grid-template-columns: 1fr 1fr; }
    .kap-research-grid { grid-template-columns: 1fr 1fr; }
    .kap-related-grid { grid-template-columns: 1fr; }
    .kap-floating-elements { display: none; }
}
@media (max-width: 640px) {
    .kap-hero { padding: 120px 20px 60px; min-height: 70vh; }
    .kap-hero-stats { flex-direction: column; align-items: center; }
    .kap-stat-pill { width: 100%; max-width: 300px; }
    .kap-signature-section { flex-direction: column; text-align: center; }
    .kap-signature-anim { width: 150px; }
    .kap-pillars-grid { grid-template-columns: 1fr; }
    .kap-research-grid { grid-template-columns: 1fr; }
    .kap-share-section { flex-direction: column; text-align: center; }
    .kap-quote-slide p { padding-left: 0; font-size: 1rem; }
    .kap-quote-slide > i { display: none; }
    .kap-quote-author { padding-left: 0; }
}

/* ===== PRINT ===== */
@media print {
    .kap-hero, .kap-reading-progress, .kap-sidebar, .kap-share-section, .kap-related-section,
    .navbar, .footer, .wa-float, .kap-quote-controls, .kap-controls-card { display: none !important; }
    .kap-layout { display: block; }
    .kap-content { padding: 0; }
    body { background: #fff !important; color: #000 !important; }
}
</style>

<!-- ============================================================
     BULLETPROOF SCRIPTS
============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // 1. Network Canvas Animation
    const canvas = document.getElementById('kapNetworkCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let width, height, particles = [];
        const particleCount = window.innerWidth < 768 ? 25 : 50;

        function resize() {
            width = canvas.width = canvas.parentElement.offsetWidth;
            height = canvas.height = canvas.parentElement.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        class Node {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.vx = (Math.random() - 0.5) * 0.5;
                this.vy = (Math.random() - 0.5) * 0.5;
                this.size = Math.random() * 2 + 1;
            }
            update() {
                this.x += this.vx; this.y += this.vy;
                if (this.x < 0 || this.x > width) this.vx *= -1;
                if (this.y < 0 || this.y > height) this.vy *= -1;
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(52, 211, 153, 0.6)';
                ctx.shadowBlur = 10;
                ctx.shadowColor = 'rgba(52, 211, 153, 0.5)';
                ctx.fill();
                ctx.shadowBlur = 0;
            }
        }
        for (let i = 0; i < particleCount; i++) particles.push(new Node());

        function connectNodes() {
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    if (distance < 130) {
                        const opacity = (1 - distance / 130) * 0.25;
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(52, 211, 153, ${opacity})`;
                        ctx.lineWidth = 1;
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.stroke();
                    }
                }
            }
        }
        function animate() {
            ctx.clearRect(0, 0, width, height);
            particles.forEach(p => { p.update(); p.draw(); });
            connectNodes();
            requestAnimationFrame(animate);
        }
        animate();
    }

    // 2. Typing Effect
    const textElement = document.getElementById('kapTypingText');
    if (textElement) {
        const phrases = [
            "Pimpinan yang berdedikasi untuk membentuk generasi inovator teknologi masa depan.",
            "Membangun ekosistem pendidikan informatika yang berdaya saing global.",
            "Menginspirasi mahasiswa untuk menjadi creator dan leader di dunia teknologi.",
            "Bergabunglah dengan kami dalam perjalanan menuju keunggulan akademik."
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
    const counters = document.querySelectorAll('.kap-stat-num[data-count]');
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
    const progressFill = document.getElementById('kapProgressFill');
    if (progressFill) {
        window.addEventListener('scroll', () => {
            const scrolled = window.scrollY;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const percent = (scrolled / maxScroll) * 100;
            progressFill.style.width = percent + '%';
        }, { passive: true });
    }

    // 5. 3D Tilt Effect
    const tiltElements = document.querySelectorAll('.kap-tilt');
    tiltElements.forEach(el => {
        if (!el) return;
        el.addEventListener('mousemove', (e) => {
            const rect = el.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = ((y - centerY) / centerY) * -4;
            const rotateY = ((x - centerX) / centerX) * 4;
            el.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });
        el.addEventListener('mouseleave', () => {
            el.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        });
    });

    // 6. Quote Carousel
    let currentQuote = 0;
    const quoteSlides = document.querySelectorAll('.kap-quote-slide');
    const quoteDots = document.querySelectorAll('.kap-quote-dot');

    function showQuote(index) {
        quoteSlides.forEach(s => s.classList.remove('active'));
        quoteDots.forEach(d => d.classList.remove('active'));
        
        if (quoteSlides[index]) quoteSlides[index].classList.add('active');
        if (quoteDots[index]) quoteDots[index].classList.add('active');
        currentQuote = index;
    }

    window.kapNextQuote = function() {
        const next = (currentQuote + 1) % quoteSlides.length;
        showQuote(next);
    };

    window.kapPrevQuote = function() {
        const prev = (currentQuote - 1 + quoteSlides.length) % quoteSlides.length;
        showQuote(prev);
    };

    quoteDots.forEach((dot, i) => {
        dot.addEventListener('click', () => showQuote(i));
    });

    // Auto-play carousel
    let quoteInterval = setInterval(window.kapNextQuote, 6000);
    const carousel = document.getElementById('kapQuoteCarousel');
    if (carousel) {
        carousel.addEventListener('mouseenter', () => clearInterval(quoteInterval));
        carousel.addEventListener('mouseleave', () => {
            quoteInterval = setInterval(window.kapNextQuote, 6000);
        });
    }

    // 7. VM Progress Bars Animation
    const progressObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target) {
                const bars = entry.target.querySelectorAll('.kap-vm-progress-bar');
                bars.forEach(bar => {
                    const progress = bar.getAttribute('data-progress');
                    bar.style.setProperty('--progress-width', progress + '%');
                    setTimeout(() => bar.classList.add('animated'), 200);
                });
                progressObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    
    const vmSection = document.querySelector('.kap-vm-section');
    if (vmSection) progressObserver.observe(vmSection);

    // 8. Scroll Reveal Animation
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

    // 9. Parallax Effect
    const heroSection = document.querySelector('.kap-hero');
    if (heroSection && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const orbs = heroSection.querySelectorAll('.kap-orb');
            orbs.forEach((orb, index) => {
                const speed = (index + 1) * 0.3;
                orb.style.transform = `translateY(${scrolled * speed}px)`;
            });
        }, { passive: true });
    }

    // 10. Text-to-Speech
    const ttsBtn = document.getElementById('kapTtsBtn');
    let isSpeaking = false;
    
    if (ttsBtn) {
        ttsBtn.addEventListener('click', function() {
            const text = document.getElementById('kapSambutanText');
            if (!text) return;
            
            if (isSpeaking) {
                window.speechSynthesis.cancel();
                isSpeaking = false;
                this.innerHTML = '<i class="fas fa-volume-up"></i><span>Bacakan</span>';
                return;
            }
            
            const utterance = new SpeechSynthesisUtterance(text.textContent);
            utterance.lang = 'id-ID';
            utterance.rate = 0.9;
            
            utterance.onend = function() {
                isSpeaking = false;
                ttsBtn.innerHTML = '<i class="fas fa-volume-up"></i><span>Bacakan</span>';
            };
            
            window.speechSynthesis.speak(utterance);
            isSpeaking = true;
            this.innerHTML = '<i class="fas fa-stop"></i><span>Berhenti</span>';
        });
    }

    // 11. Copy Link
    const copyLinkBtn = document.getElementById('kapCopyLink');
    if (copyLinkBtn) {
        copyLinkBtn.addEventListener('click', function() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                window.kapShowToast('Link berhasil disalin!');
                this.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-link"></i>';
                }, 2000);
            });
        });
    }

    // 12. Toast Notification
    window.kapShowToast = function(message) {
        const toast = document.getElementById('kapToast');
        if (!toast) return;
        const msgEl = document.getElementById('kapToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // Welcome toast
    setTimeout(() => {
        window.kapShowToast('👋 Selamat datang di halaman Sambutan Kaprodi');
    }, 1500);

    // 13. Console Easter Egg
    console.log('%c╔══════════════════════════════════════════╗', 'color:#10B981;font-weight:bold');
    console.log('%c║   SAMBUTAN KAPRODI - Informatika Pro     ║', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c╚══════════════════════════════════════════╝', 'color:#10B981;font-weight:bold');
    console.log('%c💼 Leadership Message | Vision 2030', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>