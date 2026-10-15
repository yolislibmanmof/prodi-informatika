<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$isHome = $currentPage === 'index.php' || $currentPage === '';

// Helper untuk highlight menu parent saat berada di halaman child
$activeProfil = in_array($currentPage, ['sambutan-kaprodi.php', 'kurikulum.php', 'prospek-karir.php', 'dosen.php', 'fasilitas.php']);
$activeAkademik = in_array($currentPage, ['faq.php', 'dokumen.php', 'agenda.php', 'konsentrasi.php']);
$activeInformasi = in_array($currentPage, ['berita.php', 'berita-detail.php', 'pengumuman.php', 'galeri.php', 'testimoni.php', 'prestasi.php']);
?>

<!-- ============================================================
     AURORA COMMAND CENTER NAVBAR - FINAL VERSION
     Sinkron dengan Admin Panel v4.0
============================================================ -->
<nav class="nb-navbar" id="navbar" role="navigation" aria-label="Menu utama">
    <!-- Aurora Background -->
    <div class="nb-aurora"></div>
    
    <!-- Scan Line Effect -->
    <div class="nb-scanline"></div>

    <div class="container nb-container">

        <!-- Logo -->
        <a href="<?= SITE_URL ?>/" class="nb-logo" aria-label="Beranda Informatika Pro">
            <div class="nb-logo-icon">
                <i class="fas fa-code"></i>
                <span class="nb-logo-ring"></span>
                <span class="nb-logo-ring nb-ring-2"></span>
                <span class="nb-logo-particles" id="nbLogoParticles"></span>
            </div>
            <div class="nb-logo-text">
                <span class="nb-logo-main">Informatika<span class="nb-accent">Pro</span></span>
                <span class="nb-logo-tagline">QUANTUM CAMPUS</span>
            </div>
        </a>

        <!-- Main Menu -->
        <ul class="nb-menu" id="navMenu" role="menubar">
            
            <!-- Home -->
            <li role="none" class="nb-item">
                <a href="<?= SITE_URL ?>/" class="nb-link <?= $isHome ? 'nb-active' : '' ?>" role="menuitem" data-section="home">
                    <span class="nb-link-icon"><i class="fas fa-home"></i></span>
                    <span class="nb-link-text">Beranda</span>
                    <span class="nb-link-glow"></span>
                </a>
            </li>
            
            <!-- Dropdown: Profil -->
            <li role="none" class="nb-item nb-dropdown <?= $activeProfil ? 'nb-parent-active' : '' ?>">
                <a href="#" class="nb-link nb-dropdown-trigger" role="menuitem" aria-haspopup="true" aria-expanded="false">
                    <span class="nb-link-icon"><i class="fas fa-building"></i></span>
                    <span class="nb-link-text">Profil</span>
                    <i class="fas fa-chevron-down nb-chevron"></i>
                    <span class="nb-link-glow"></span>
                </a>
                <div class="nb-dropdown-panel">
                    <div class="nb-dropdown-header">
                        <i class="fas fa-building"></i>
                        <span>Profil Program Studi</span>
                    </div>
                    <ul class="nb-dropdown-menu" role="menu">
                        <li role="none">
                            <a href="<?= SITE_URL ?>/sambutan-kaprodi.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-user-tie"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Sambutan Kaprodi</span>
                                    <span class="nb-di-desc">Visi & misi program studi</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                        <li role="none">
                            <a href="<?= SITE_URL ?>/kurikulum.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-book-open"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Struktur Kurikulum</span>
                                    <span class="nb-di-desc">Mata kuliah & SKS</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                        <li role="none">
                            <a href="<?= SITE_URL ?>/prospek-karir.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-briefcase"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Prospek Karir</span>
                                    <span class="nb-di-desc">Peluang kerja lulusan</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                        <li role="none">
                            <a href="<?= SITE_URL ?>/dosen.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-chalkboard-teacher"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Profil Dosen</span>
                                    <span class="nb-di-desc">Tim pengajar ahli</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                        <li role="none">
                            <a href="<?= SITE_URL ?>/fasilitas.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-flask"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Fasilitas & Lab</span>
                                    <span class="nb-di-desc">Sarana pembelajaran</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Dropdown: Akademik -->
            <li role="none" class="nb-item nb-dropdown <?= $activeAkademik ? 'nb-parent-active' : '' ?>">
                <a href="<?= SITE_URL ?>/konsentrasi.php" class="nb-link nb-dropdown-trigger" role="menuitem" aria-haspopup="true" aria-expanded="false">
                    <span class="nb-link-icon"><i class="fas fa-graduation-cap"></i></span>
                    <span class="nb-link-text">Akademik</span>
                    <i class="fas fa-chevron-down nb-chevron"></i>
                    <span class="nb-link-glow"></span>
                </a>
                <div class="nb-dropdown-panel">
                    <div class="nb-dropdown-header">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Akademik & Layanan</span>
                    </div>
                    <ul class="nb-dropdown-menu" role="menu">
                        <li role="none">
                            <a href="<?= SITE_URL ?>/konsentrasi.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-laptop-code"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Kelompok Keahlian</span>
                                    <span class="nb-di-desc">Konsentrasi & spesialisasi</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                        <li role="none">
                            <a href="<?= SITE_URL ?>/agenda.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-calendar-alt"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Agenda & Kegiatan</span>
                                    <span class="nb-di-desc">Event & jadwal penting</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                        <li role="none">
                            <a href="<?= SITE_URL ?>/dokumen.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-file-download"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Dokumen & Unduhan</span>
                                    <span class="nb-di-desc">Formulir & panduan</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                        <li role="none">
                            <a href="<?= SITE_URL ?>/faq.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-question-circle"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">FAQ</span>
                                    <span class="nb-di-desc">Pertanyaan umum</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Dropdown: Informasi -->
            <li role="none" class="nb-item nb-dropdown <?= $activeInformasi ? 'nb-parent-active' : '' ?>">
                <a href="#" class="nb-link nb-dropdown-trigger" role="menuitem" aria-haspopup="true" aria-expanded="false">
                    <span class="nb-link-icon"><i class="fas fa-broadcast-tower"></i></span>
                    <span class="nb-link-text">Informasi</span>
                    <i class="fas fa-chevron-down nb-chevron"></i>
                    <span class="nb-link-glow"></span>
                </a>
                <div class="nb-dropdown-panel">
                    <div class="nb-dropdown-header">
                        <i class="fas fa-broadcast-tower"></i>
                        <span>Pusat Informasi</span>
                    </div>
                    <ul class="nb-dropdown-menu" role="menu">
                        <li role="none">
                            <a href="<?= SITE_URL ?>/berita.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-newspaper"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Berita</span>
                                    <span class="nb-di-desc">Update terkini kampus</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                        <li role="none">
                            <a href="<?= SITE_URL ?>/pengumuman.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-bullhorn"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Pengumuman</span>
                                    <span class="nb-di-desc">Info resmi & penting</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                        <li role="none">
                            <a href="<?= SITE_URL ?>/prestasi.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-trophy"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Prestasi</span>
                                    <span class="nb-di-desc">Pencapaian mahasiswa</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                        <li role="none">
                            <a href="<?= SITE_URL ?>/galeri.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-images"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Galeri</span>
                                    <span class="nb-di-desc">Dokumentasi kegiatan</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                        <li role="none">
                            <a href="<?= SITE_URL ?>/testimoni.php" class="nb-dropdown-item" role="menuitem">
                                <span class="nb-di-icon"><i class="fas fa-comments"></i></span>
                                <span class="nb-di-content">
                                    <span class="nb-di-title">Testimoni</span>
                                    <span class="nb-di-desc">Cerita alumni & mahasiswa</span>
                                </span>
                                <i class="fas fa-arrow-right nb-di-arrow"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Right Actions -->
            <li role="none" class="nb-actions">
                <!-- Search Trigger -->
                <button class="nb-search-trigger" id="nbSearchTrigger" aria-label="Cari (Ctrl+K)" title="Cari (Ctrl+K)">
                    <i class="fas fa-search"></i>
                    <span class="nb-search-kbd">⌘K</span>
                    <span class="nb-search-pulse"></span>
                </button>

                <!-- Theme Toggle -->
                <button class="nb-theme-toggle" id="nbThemeToggle" aria-label="Ganti tema terang/gelap" title="Ganti Tema">
                    <div class="nb-theme-orbit">
                        <span class="nb-theme-star"></span>
                        <span class="nb-theme-star nb-star-2"></span>
                        <span class="nb-theme-star nb-star-3"></span>
                    </div>
                    <i class="fas fa-moon nb-theme-icon" id="nbThemeIcon"></i>
                </button>

                <!-- Admin Panel Button - SINKRONISASI -->
                <a href="<?= SITE_URL ?>/admin/login.php" class="nb-admin-btn" role="menuitem" title="Panel Admin">
                    <span class="nb-admin-content">
                        <i class="fas fa-lock"></i>
                        <span>Admin</span>
                    </span>
                </a>

                <!-- CTA Button -->
                <a href="<?= SITE_URL ?>/daftar.php" class="nb-cta" role="menuitem">
                    <span class="nb-cta-content">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Daftar</span>
                    </span>
                    <span class="nb-cta-shine"></span>
                    <span class="nb-cta-pulse"></span>
                </a>
            </li>
        </ul>

        <!-- Mobile Toggle -->
        <button class="nb-mobile-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="navMenu">
            <span class="nb-toggle-line nb-line-1"></span>
            <span class="nb-toggle-line nb-line-2"></span>
            <span class="nb-toggle-line nb-line-3"></span>
            <span class="nb-toggle-text">MENU</span>
        </button>
    </div>

    <!-- ===== COMMAND PALETTE SEARCH MODAL ===== -->
    <div class="nb-search-overlay" id="nbSearchOverlay" role="dialog" aria-label="Pencarian" aria-modal="true">
        <div class="nb-search-modal">
            <!-- Search Header -->
            <div class="nb-search-header">
                <div class="nb-search-icon-wrap">
                    <i class="fas fa-terminal"></i>
                </div>
                <input type="text" id="nbSearchInput" class="nb-search-input" 
                       placeholder="Ketik perintah atau cari sesuatu..." 
                       autocomplete="off" spellcheck="false">
                <kbd class="nb-search-esc">ESC</kbd>
            </div>
            
            <!-- Search Body -->
            <div class="nb-search-body" id="nbSearchResults">
                <!-- Default State -->
                <div class="nb-search-default">
                    <div class="nb-search-hint">
                        <i class="fas fa-lightbulb"></i>
                        <span>Coba: <b>beasiswa</b>, <b>kurikulum</b>, <b>AI engineer</b>, atau <b>pendaftaran</b></span>
                    </div>
                    
                    <div class="nb-search-section">
                        <div class="nb-search-section-title">
                            <i class="fas fa-bolt"></i> Akses Cepat
                        </div>
                        <div class="nb-search-grid">
                            <a href="<?= SITE_URL ?>/kurikulum.php" class="nb-search-item">
                                <span class="nb-si-icon" style="--si-color: #3B82F6;"><i class="fas fa-book-open"></i></span>
                                <span class="nb-si-info">
                                    <span class="nb-si-title">Kurikulum</span>
                                    <span class="nb-si-desc">Struktur mata kuliah</span>
                                </span>
                                <kbd class="nb-si-kbd">1</kbd>
                            </a>
                            <a href="<?= SITE_URL ?>/prospek-karir.php" class="nb-search-item">
                                <span class="nb-si-icon" style="--si-color: #10B981;"><i class="fas fa-briefcase"></i></span>
                                <span class="nb-si-info">
                                    <span class="nb-si-title">Prospek Karir</span>
                                    <span class="nb-si-desc">Peluang kerja</span>
                                </span>
                                <kbd class="nb-si-kbd">2</kbd>
                            </a>
                            <a href="<?= SITE_URL ?>/agenda.php" class="nb-search-item">
                                <span class="nb-si-icon" style="--si-color: #F59E0B;"><i class="fas fa-calendar-alt"></i></span>
                                <span class="nb-si-info">
                                    <span class="nb-si-title">Agenda</span>
                                    <span class="nb-si-desc">Event & kegiatan</span>
                                </span>
                                <kbd class="nb-si-kbd">3</kbd>
                            </a>
                            <a href="<?= SITE_URL ?>/dokumen.php" class="nb-search-item">
                                <span class="nb-si-icon" style="--si-color: #8B5CF6;"><i class="fas fa-file-download"></i></span>
                                <span class="nb-si-info">
                                    <span class="nb-si-title">Dokumen</span>
                                    <span class="nb-si-desc">Unduhan & formulir</span>
                                </span>
                                <kbd class="nb-si-kbd">4</kbd>
                            </a>
                            <a href="<?= SITE_URL ?>/prestasi.php" class="nb-search-item">
                                <span class="nb-si-icon" style="--si-color: #EC4899;"><i class="fas fa-trophy"></i></span>
                                <span class="nb-si-info">
                                    <span class="nb-si-title">Prestasi</span>
                                    <span class="nb-si-desc">Pencapaian mahasiswa</span>
                                </span>
                                <kbd class="nb-si-kbd">5</kbd>
                            </a>
                            <a href="<?= SITE_URL ?>/berita.php" class="nb-search-item">
                                <span class="nb-si-icon" style="--si-color: #06B6D4;"><i class="fas fa-newspaper"></i></span>
                                <span class="nb-si-info">
                                    <span class="nb-si-title">Berita</span>
                                    <span class="nb-si-desc">Update kampus</span>
                                </span>
                                <kbd class="nb-si-kbd">6</kbd>
                            </a>
                            <a href="<?= SITE_URL ?>/daftar.php" class="nb-search-item">
                                <span class="nb-si-icon" style="--si-color: #10B981;"><i class="fas fa-user-plus"></i></span>
                                <span class="nb-si-info">
                                    <span class="nb-si-title">Pendaftaran</span>
                                    <span class="nb-si-desc">Mahasiswa baru</span>
                                </span>
                                <kbd class="nb-si-kbd">7</kbd>
                            </a>
                            <a href="<?= SITE_URL ?>/admin/login.php" class="nb-search-item">
                                <span class="nb-si-icon" style="--si-color: #6B7280;"><i class="fas fa-lock"></i></span>
                                <span class="nb-si-info">
                                    <span class="nb-si-title">Admin Panel</span>
                                    <span class="nb-si-desc">Login administrator</span>
                                </span>
                                <kbd class="nb-si-kbd">8</kbd>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Search Footer -->
            <div class="nb-search-footer">
                <span class="nb-sf-item"><kbd>↑↓</kbd> Navigasi</span>
                <span class="nb-sf-item"><kbd>↵</kbd> Buka</span>
                <span class="nb-sf-item"><kbd>esc</kbd> Tutup</span>
                <span class="nb-sf-item nb-sf-right"><kbd>1-8</kbd> Quick Access</span>
            </div>
        </div>
    </div>
</nav>

<!-- ===== BREADCRUMB (non-home) ===== -->
<?php if (!$isHome && !in_array($currentPage, ['kirim-kontak.php', 'terima-kasih.php'])): ?>
<nav class="nb-breadcrumb" aria-label="Breadcrumb">
    <div class="container">
        <ol class="nb-breadcrumb-list">
            <li class="nb-bc-item">
                <a href="<?= SITE_URL ?>/"><i class="fas fa-home"></i> Beranda</a>
            </li>
            <li class="nb-bc-sep"><i class="fas fa-chevron-right"></i></li>
            <li class="nb-bc-item nb-bc-current" aria-current="page">
                <?php
                $names = [
                    'berita.php' => 'Arsip Berita',
                    'berita-detail.php' => 'Detail Berita',
                    'pengumuman.php' => 'Pengumuman',
                    'galeri.php' => 'Galeri Kegiatan',
                    'faq.php' => 'Pertanyaan Umum (FAQ)',
                    'testimoni.php' => 'Testimoni',
                    'dosen.php' => 'Profil Dosen',
                    'kurikulum.php' => 'Struktur Kurikulum',
                    'prospek-karir.php' => 'Prospek Karir',
                    'sambutan-kaprodi.php' => 'Sambutan Kaprodi',
                    'daftar.php' => 'Pendaftaran Mahasiswa Baru',
                    'agenda.php' => 'Agenda & Kegiatan',
                    'dokumen.php' => 'Dokumen & Unduhan',
                    'prestasi.php' => 'Prestasi Mahasiswa',
                    'fasilitas.php' => 'Fasilitas & Laboratorium',
                    'konsentrasi.php' => 'Kelompok Keahlian',
                ];
                echo $names[$currentPage] ?? ucwords(str_replace(['.php', '-', '_'], [' ', ' ', ' '], $currentPage));
                ?>
            </li>
        </ol>
    </div>
</nav>
<?php endif; ?>

<!-- Main Content Anchor -->
<main id="main-content">

<!-- ============================================================
     AURORA COMMAND CENTER STYLES - FINAL
============================================================ -->
<style>
/* ===== NAVBAR BASE ===== */
.nb-navbar {
    position: fixed; top: 0; left: 0; right: 0;
    z-index: 9999; height: 72px;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid rgba(16,185,129,0.1);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
body.gl-dark .nb-navbar {
    background: rgba(15,23,42,0.92);
    border-bottom-color: rgba(16,185,129,0.15);
}
.nb-navbar.nb-scrolled {
    height: 64px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.08);
}
body.gl-dark .nb-navbar.nb-scrolled {
    box-shadow: 0 4px 30px rgba(0,0,0,0.3);
}

/* Aurora Background */
.nb-aurora {
    position: absolute; inset: 0; overflow: hidden;
    pointer-events: none; opacity: 0;
    transition: opacity 0.6s;
}
.nb-navbar.nb-scrolled .nb-aurora { opacity: 1; }
.nb-aurora::before, .nb-aurora::after {
    content: ''; position: absolute;
    width: 300px; height: 200px;
    border-radius: 50%; filter: blur(60px);
    opacity: 0.08;
}
.nb-aurora::before {
    background: linear-gradient(135deg, #10B981, #06B6D4);
    top: -60%; left: 10%;
    animation: nbAurora1 8s ease-in-out infinite;
}
.nb-aurora::after {
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    top: -60%; right: 10%;
    animation: nbAurora2 10s ease-in-out infinite;
}
@keyframes nbAurora1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(50px, 20px) scale(1.2); }
}
@keyframes nbAurora2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(-40px, 15px) scale(1.1); }
}

/* Scanline */
.nb-scanline {
    position: absolute; bottom: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #10B981, #06B6D4, #8B5CF6, transparent);
    background-size: 200% 100%;
    animation: nbScanline 4s linear infinite;
    opacity: 0; transition: opacity 0.4s;
}
.nb-navbar.nb-scrolled .nb-scanline { opacity: 0.6; }
@keyframes nbScanline {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

/* Container */
.nb-container {
    max-width: 1280px; margin: 0 auto;
    padding: 0 24px; height: 100%;
    display: flex; align-items: center;
    justify-content: space-between; gap: 20px;
}

/* ===== LOGO ===== */
.nb-logo {
    display: flex; align-items: center; gap: 12px;
    text-decoration: none; flex-shrink: 0;
    transition: transform 0.3s;
}
.nb-logo:hover { transform: translateY(-1px); }
.nb-logo-icon {
    position: relative; width: 44px; height: 44px;
    background: linear-gradient(135deg, #10B981, #047857);
    border-radius: 12px; display: flex;
    align-items: center; justify-content: center;
    color: #fff; font-size: 1.15rem;
    box-shadow: 0 6px 20px rgba(16,185,129,0.35);
    transition: all 0.3s;
}
.nb-logo:hover .nb-logo-icon {
    transform: rotate(-5deg) scale(1.05);
    box-shadow: 0 8px 28px rgba(16,185,129,0.5);
}
.nb-logo-ring {
    position: absolute; inset: -3px;
    border-radius: 14px;
    border: 2px solid rgba(16,185,129,0.5);
    opacity: 0; animation: nbLogoRing 2.5s ease-out infinite;
}
.nb-ring-2 { animation-delay: 1.25s; }
@keyframes nbLogoRing {
    0% { transform: scale(1); opacity: 0.6; }
    100% { transform: scale(1.5); opacity: 0; }
}
.nb-logo-particles { position: absolute; inset: 0; pointer-events: none; }
.nb-logo-text { display: flex; flex-direction: column; }
.nb-logo-main {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.3rem; font-weight: 800;
    color: #0F172A; letter-spacing: -0.02em;
    line-height: 1.1;
}
body.gl-dark .nb-logo-main { color: #F1F5F9; }
.nb-accent { color: #10B981; }
.nb-logo-tagline {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.58rem; letter-spacing: 2.5px;
    color: #10B981; font-weight: 700;
    text-transform: uppercase; opacity: 0.8;
}

/* ===== NAV MENU ===== */
.nb-menu {
    display: flex; align-items: center; gap: 4px;
    list-style: none; margin: 0; padding: 0;
    flex: 1; justify-content: center;
}

/* Nav Links */
.nb-item { position: relative; }
.nb-link {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px; border-radius: 10px;
    text-decoration: none; color: #475569;
    font-size: 0.88rem; font-weight: 600;
    transition: all 0.25s; position: relative;
    overflow: hidden;
}
body.gl-dark .nb-link { color: #94A3B8; }
.nb-link:hover { color: #10B981; background: rgba(16,185,129,0.06); }
body.gl-dark .nb-link:hover { color: #34D399; background: rgba(16,185,129,0.08); }
.nb-link.nb-active { color: #10B981; background: rgba(16,185,129,0.08); }
body.gl-dark .nb-link.nb-active { color: #34D399; background: rgba(16,185,129,0.1); }

.nb-link-icon { font-size: 0.9rem; opacity: 0.7; transition: opacity 0.2s; }
.nb-link:hover .nb-link-icon { opacity: 1; }
.nb-link-glow {
    position: absolute; bottom: 2px; left: 50%;
    width: 0; height: 2px; border-radius: 2px;
    background: linear-gradient(90deg, #10B981, #06B6D4);
    transform: translateX(-50%);
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 0 8px rgba(16,185,129,0.5);
}
.nb-link:hover .nb-link-glow,
.nb-link.nb-active .nb-link-glow { width: 60%; }

/* Parent active indicator */
.nb-parent-active > .nb-link { color: #10B981; }
body.gl-dark .nb-parent-active > .nb-link { color: #34D399; }

/* Chevron */
.nb-chevron {
    font-size: 0.6rem; transition: transform 0.3s;
    opacity: 0.6;
}
.nb-dropdown:hover .nb-chevron { transform: rotate(180deg); }

/* ===== DROPDOWN PANEL ===== */
.nb-dropdown-panel {
    position: absolute; top: calc(100% + 12px);
    left: 50%; transform: translateX(-50%) translateY(8px);
    min-width: 320px; background: #fff;
    border-radius: 16px; padding: 8px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.12),
                0 0 0 1px rgba(16,185,129,0.08);
    opacity: 0; visibility: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
}
body.gl-dark .nb-dropdown-panel {
    background: #1E293B;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4),
                0 0 0 1px rgba(16,185,129,0.15);
}
/* Bridge to prevent hover gap */
.nb-dropdown-panel::before {
    content: ''; position: absolute;
    top: -12px; left: 0; right: 0; height: 12px;
}
.nb-dropdown:hover .nb-dropdown-panel {
    opacity: 1; visibility: visible;
    transform: translateX(-50%) translateY(0);
}
/* Arrow pointer */
.nb-dropdown-panel::after {
    content: ''; position: absolute;
    top: -6px; left: 50%; transform: translateX(-50%) rotate(45deg);
    width: 12px; height: 12px;
    background: #fff; border-radius: 2px;
    box-shadow: -2px -2px 4px rgba(0,0,0,0.04);
}
body.gl-dark .nb-dropdown-panel::after { background: #1E293B; }

.nb-dropdown-header {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px 12px;
    border-bottom: 1px solid #F1F5F9;
    margin-bottom: 6px;
    font-size: 0.78rem; font-weight: 700;
    color: #10B981; letter-spacing: 0.5px;
    text-transform: uppercase;
}
body.gl-dark .nb-dropdown-header { border-color: rgba(255,255,255,0.06); }
.nb-dropdown-header i { font-size: 0.9rem; }

.nb-dropdown-menu { list-style: none; padding: 0; margin: 0; }
.nb-dropdown-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 10px;
    text-decoration: none; transition: all 0.2s;
    margin-bottom: 2px;
}
.nb-dropdown-item:hover {
    background: rgba(16,185,129,0.06);
    transform: translateX(4px);
}
body.gl-dark .nb-dropdown-item:hover { background: rgba(16,185,129,0.1); }

.nb-di-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(16,185,129,0.1);
    color: #10B981; display: flex;
    align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0;
    transition: all 0.2s;
}
.nb-dropdown-item:hover .nb-di-icon {
    background: #10B981; color: #fff;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}
.nb-di-content { flex: 1; min-width: 0; }
.nb-di-title {
    display: block; font-size: 0.88rem;
    font-weight: 600; color: #0F172A;
    line-height: 1.3;
}
body.gl-dark .nb-di-title { color: #F1F5F9; }
.nb-di-desc {
    display: block; font-size: 0.72rem;
    color: #94A3B8; margin-top: 1px;
}
.nb-di-arrow {
    font-size: 0.7rem; color: #10B981;
    opacity: 0; transform: translateX(-4px);
    transition: all 0.2s;
}
.nb-dropdown-item:hover .nb-di-arrow {
    opacity: 1; transform: translateX(0);
}

/* ===== RIGHT ACTIONS ===== */
.nb-actions {
    display: flex; align-items: center; gap: 10px;
    list-style: none;
}

/* Admin Button - BARU */
.nb-admin-btn {
    position: relative; display: inline-flex;
    align-items: center; gap: 8px;
    padding: 10px 18px;
    background: rgba(107, 114, 128, 0.1);
    border: 1px solid rgba(107, 114, 128, 0.2);
    color: #6B7280; border-radius: 12px;
    font-weight: 600; font-size: 0.88rem;
    text-decoration: none; overflow: hidden;
    transition: all 0.3s;
}
.nb-admin-btn:hover {
    background: rgba(107, 114, 128, 0.15);
    border-color: #6B7280;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.15);
}
body.gl-dark .nb-admin-btn {
    background: rgba(107, 114, 128, 0.08);
    border-color: rgba(107, 114, 128, 0.3);
    color: #9CA3AF;
}
.nb-admin-content {
    display: flex; align-items: center; gap: 8px;
    position: relative; z-index: 1;
}

/* Search Trigger */
.nb-search-trigger {
    position: relative; display: flex; align-items: center; gap: 8px;
    padding: 9px 14px; background: rgba(16,185,129,0.06);
    border: 1px solid rgba(16,185,129,0.2);
    border-radius: 10px; cursor: pointer;
    color: #10B981; font-size: 0.88rem;
    font-family: inherit; transition: all 0.25s;
}
body.gl-dark .nb-search-trigger {
    background: rgba(16,185,129,0.08);
    border-color: rgba(16,185,129,0.25);
}
.nb-search-trigger:hover {
    background: rgba(16,185,129,0.12);
    border-color: #10B981;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16,185,129,0.2);
}
.nb-search-kbd {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.65rem; font-weight: 700;
    padding: 2px 6px; background: rgba(16,185,129,0.1);
    border-radius: 4px; color: #10B981;
    border: 1px solid rgba(16,185,129,0.2);
}
.nb-search-pulse {
    position: absolute; top: -2px; right: -2px;
    width: 8px; height: 8px; border-radius: 50%;
    background: #10B981;
    animation: nbPulse 2s infinite;
}
@keyframes nbPulse {
    0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.5); }
    70% { box-shadow: 0 0 0 6px rgba(16,185,129,0); }
    100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
}

/* Theme Toggle */
.nb-theme-toggle {
    position: relative; width: 42px; height: 42px;
    border-radius: 12px; border: 1px solid rgba(16,185,129,0.2);
    background: rgba(16,185,129,0.06);
    cursor: pointer; display: flex;
    align-items: center; justify-content: center;
    color: #10B981; font-size: 1rem;
    transition: all 0.3s; overflow: hidden;
}
body.gl-dark .nb-theme-toggle {
    background: rgba(245,158,11,0.08);
    border-color: rgba(245,158,11,0.25);
    color: #F59E0B;
}
.nb-theme-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16,185,129,0.2);
}
.nb-theme-icon { transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1); z-index: 1; }
.nb-theme-toggle:hover .nb-theme-icon { transform: rotate(30deg) scale(1.1); }
.nb-theme-toggle.nb-animating .nb-theme-icon {
    animation: nbThemeSpin 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
@keyframes nbThemeSpin {
    0% { transform: rotate(0) scale(1); }
    50% { transform: rotate(180deg) scale(0.5); }
    100% { transform: rotate(360deg) scale(1); }
}
.nb-theme-orbit {
    position: absolute; inset: 0;
    animation: nbOrbit 6s linear infinite;
    pointer-events: none;
}
.nb-theme-star {
    position: absolute; width: 3px; height: 3px;
    border-radius: 50%; background: #10B981;
    top: 4px; left: 50%; opacity: 0.6;
}
.nb-star-2 { top: auto; bottom: 6px; left: 8px; width: 2px; height: 2px; }
.nb-star-3 { top: 12px; left: auto; right: 6px; width: 2px; height: 2px; }
body.gl-dark .nb-theme-star { background: #F59E0B; }
@keyframes nbOrbit { to { transform: rotate(360deg); } }

/* CTA Button */
.nb-cta {
    position: relative; display: inline-flex;
    align-items: center; gap: 8px;
    padding: 10px 22px;
    background: linear-gradient(135deg, #10B981, #047857);
    color: #fff; border-radius: 12px;
    font-weight: 700; font-size: 0.88rem;
    text-decoration: none; overflow: hidden;
    box-shadow: 0 6px 20px rgba(16,185,129,0.3);
    transition: all 0.3s;
}
.nb-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(16,185,129,0.45);
}
.nb-cta-content {
    display: flex; align-items: center; gap: 8px;
    position: relative; z-index: 1;
}
.nb-cta-shine {
    position: absolute; top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
    transition: left 0.6s;
}
.nb-cta:hover .nb-cta-shine { left: 100%; }
.nb-cta-pulse {
    position: absolute; inset: 0; border-radius: 12px;
    border: 2px solid #10B981;
    animation: nbCtaPulse 2s infinite;
}
@keyframes nbCtaPulse {
    0% { transform: scale(1); opacity: 0.6; }
    100% { transform: scale(1.15); opacity: 0; }
}

/* ===== MOBILE TOGGLE ===== */
.nb-mobile-toggle {
    display: none; flex-direction: column;
    align-items: center; gap: 5px;
    padding: 8px 12px; background: none;
    border: none; cursor: pointer;
    position: relative;
}
.nb-toggle-line {
    width: 24px; height: 2px;
    background: #0F172A; border-radius: 2px;
    transition: all 0.3s;
}
body.gl-dark .nb-toggle-line { background: #F1F5F9; }
.nb-toggle-text {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.55rem; letter-spacing: 2px;
    color: #10B981; font-weight: 700;
    margin-top: 2px;
}
.nb-mobile-toggle.nb-active .nb-line-1 { transform: rotate(45deg) translate(5px, 5px); }
.nb-mobile-toggle.nb-active .nb-line-2 { opacity: 0; transform: scaleX(0); }
.nb-mobile-toggle.nb-active .nb-line-3 { transform: rotate(-45deg) translate(5px, -5px); }

/* ===== SEARCH MODAL ===== */
.nb-search-overlay {
    position: fixed; inset: 0; z-index: 10001;
    background: rgba(2,6,23,0.75);
    backdrop-filter: blur(12px);
    display: flex; align-items: flex-start;
    justify-content: center; padding: 100px 20px 20px;
    opacity: 0; visibility: hidden;
    transition: all 0.3s;
}
.nb-search-overlay.nb-open { opacity: 1; visibility: visible; }
.nb-search-modal {
    width: 100%; max-width: 680px;
    background: #fff; border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(0,0,0,0.3),
                0 0 0 1px rgba(16,185,129,0.1);
    transform: translateY(-20px) scale(0.96);
    transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
body.gl-dark .nb-search-modal {
    background: #1E293B;
    box-shadow: 0 30px 80px rgba(0,0,0,0.5),
                0 0 0 1px rgba(16,185,129,0.15);
}
.nb-search-overlay.nb-open .nb-search-modal { transform: none; }

.nb-search-header {
    display: flex; align-items: center; gap: 14px;
    padding: 18px 22px;
    border-bottom: 1px solid #F1F5F9;
}
body.gl-dark .nb-search-header { border-color: rgba(255,255,255,0.06); }
.nb-search-icon-wrap {
    width: 38px; height: 38px; border-radius: 10px;
    background: rgba(16,185,129,0.1);
    color: #10B981; display: flex;
    align-items: center; justify-content: center;
    font-size: 1rem;
}
.nb-search-input {
    flex: 1; border: none; outline: none;
    background: transparent; font-size: 1.05rem;
    font-family: inherit; color: #0F172A;
}
body.gl-dark .nb-search-input { color: #F1F5F9; }
.nb-search-input::placeholder { color: #94A3B8; }
.nb-search-esc {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.7rem; padding: 4px 8px;
    background: #F1F5F9; border-radius: 6px;
    color: #64748B; font-weight: 600;
}
body.gl-dark .nb-search-esc { background: rgba(255,255,255,0.06); color: #94A3B8; }

.nb-search-body { padding: 16px 22px; max-height: 420px; overflow-y: auto; }
.nb-search-hint {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px;
    background: rgba(16,185,129,0.06);
    border: 1px solid rgba(16,185,129,0.1);
    border-radius: 10px; font-size: 0.85rem;
    color: #475569; margin-bottom: 20px;
}
body.gl-dark .nb-search-hint {
    background: rgba(16,185,129,0.08);
    border-color: rgba(16,185,129,0.15);
    color: #94A3B8;
}
.nb-search-hint i { color: #F59E0B; }
.nb-search-hint b { color: #10B981; }

.nb-search-section-title {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.72rem; font-weight: 700;
    letter-spacing: 1.5px; text-transform: uppercase;
    color: #94A3B8; margin-bottom: 12px;
}
.nb-search-section-title i { color: #F59E0B; font-size: 0.8rem; }

.nb-search-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
.nb-search-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; background: #F8FAFC;
    border-radius: 12px; text-decoration: none;
    transition: all 0.2s; border: 1px solid transparent;
}
body.gl-dark .nb-search-item { background: rgba(255,255,255,0.04); }
.nb-search-item:hover {
    background: rgba(16,185,129,0.06);
    border-color: rgba(16,185,129,0.2);
    transform: translateX(4px);
}
.nb-si-icon {
    width: 34px; height: 34px; border-radius: 8px;
    background: color-mix(in srgb, var(--si-color) 12%, transparent);
    color: var(--si-color);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; flex-shrink: 0;
}
.nb-si-info { flex: 1; min-width: 0; }
.nb-si-title { display: block; font-size: 0.85rem; font-weight: 600; color: #0F172A; }
body.gl-dark .nb-si-title { color: #F1F5F9; }
.nb-si-desc { display: block; font-size: 0.7rem; color: #94A3B8; margin-top: 1px; }
.nb-si-kbd {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.62rem; padding: 2px 6px;
    background: rgba(16,185,129,0.08);
    border-radius: 4px; color: #10B981;
    font-weight: 700;
}

.nb-search-footer {
    display: flex; gap: 16px; padding: 12px 22px;
    border-top: 1px solid #F1F5F9;
    font-size: 0.75rem; color: #94A3B8;
}
body.gl-dark .nb-search-footer { border-color: rgba(255,255,255,0.06); }
.nb-sf-item { display: flex; align-items: center; gap: 4px; }
.nb-sf-right { margin-left: auto; }
.nb-search-footer kbd {
    font-family: inherit; padding: 2px 5px;
    background: #F1F5F9; border-radius: 3px;
    font-size: 0.68rem;
}
body.gl-dark .nb-search-footer kbd { background: rgba(255,255,255,0.06); }

/* ===== BREADCRUMB ===== */
.nb-breadcrumb {
    background: #F8FAFC; padding: 16px 0;
    border-bottom: 1px solid #E2E8F0;
    margin-top: 72px;
}
body.gl-dark .nb-breadcrumb {
    background: rgba(255,255,255,0.02);
    border-color: rgba(255,255,255,0.05);
}
.nb-breadcrumb-list {
    list-style: none; padding: 0; margin: 0;
    display: flex; align-items: center; gap: 10px;
    font-size: 0.85rem; flex-wrap: wrap;
}
.nb-bc-item a {
    display: flex; align-items: center; gap: 6px;
    color: #64748B; text-decoration: none;
    transition: color 0.2s; font-weight: 500;
}
.nb-bc-item a:hover { color: #10B981; }
.nb-bc-item a i { font-size: 0.8rem; }
.nb-bc-sep { color: #CBD5E1; font-size: 0.6rem; }
.nb-bc-current { color: #10B981; font-weight: 700; }

/* ===== RESPONSIVE ===== */
@media (max-width: 968px) {
    .nb-menu {
        position: fixed; top: 72px; left: 0; right: 0;
        bottom: 0; background: #fff;
        flex-direction: column; align-items: stretch;
        padding: 20px; gap: 4px;
        transform: translateX(100%);
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-y: auto; z-index: 9998;
    }
    body.gl-dark .nb-menu { background: #0F172A; }
    .nb-menu.nb-mobile-open { transform: translateX(0); }
    
    .nb-mobile-toggle { display: flex; }
    .nb-actions { flex-direction: column; width: 100%; margin-top: 16px; gap: 12px; }
    .nb-search-trigger { width: 100%; justify-content: center; }
    .nb-theme-toggle { display: none; }
    .nb-cta { width: 100%; justify-content: center; }
    .nb-admin-btn { width: 100%; justify-content: center; }
    
    .nb-link { padding: 14px 16px; font-size: 1rem; }
    .nb-link-icon { display: none; }
    
    .nb-dropdown-panel {
        position: static; transform: none;
        min-width: 100%; box-shadow: none;
        background: transparent; padding: 0 0 0 16px;
        opacity: 1; visibility: visible;
        display: none; border-radius: 0;
    }
    .nb-dropdown-panel::before, .nb-dropdown-panel::after { display: none; }
    .nb-dropdown.nb-mobile-active .nb-dropdown-panel { display: block; }
    .nb-dropdown-header { display: none; }
    
    .nb-search-grid { grid-template-columns: 1fr; }
    .nb-breadcrumb { margin-top: 72px; }
    .nb-search-overlay { padding: 80px 12px 12px; }
}
@media (min-width: 969px) {
    .nb-mobile-toggle { display: none; }
}
</style>

<!-- ============================================================
     AURORA COMMAND CENTER SCRIPTS - FINAL
============================================================ -->
<script>
(function() {
    'use strict';

    /* ===== 1. Navbar Scroll Effect ===== */
    const navbar = document.getElementById('navbar');
    let lastScroll = 0;
    
    window.addEventListener('scroll', () => {
        const y = window.scrollY;
        if (navbar) {
            navbar.classList.toggle('nb-scrolled', y > 50);
        }
        lastScroll = y;
    }, { passive: true });

    /* ===== 2. Theme Toggle ===== */
    const themeToggle = document.getElementById('nbThemeToggle');
    const themeIcon = document.getElementById('nbThemeIcon');
    
    function applyTheme(dark, animate) {
        document.documentElement.classList.toggle('gl-dark', dark);
        document.body.classList.toggle('gl-dark', dark);
        if (themeIcon) themeIcon.className = dark ? 'fas fa-sun nb-theme-icon' : 'fas fa-moon nb-theme-icon';
        if (themeToggle) themeToggle.classList.toggle('nb-dark', dark);
        
        if (animate && themeToggle) {
            themeToggle.classList.add('nb-animating');
            setTimeout(() => themeToggle.classList.remove('nb-animating'), 600);
        }
        
        // Save to all storage keys for compatibility
        const val = dark ? 'dark' : 'light';
        localStorage.setItem('gl_theme', val);
        localStorage.setItem('hp_theme', val);
        document.cookie = `gl_theme=${val};path=/;max-age=31536000`;
        document.cookie = `hp_theme=${val};path=/;max-age=31536000`;
    }
    
    // Initialize theme from storage
    const savedTheme = localStorage.getItem('gl_theme') || localStorage.getItem('hp_theme');
    if (savedTheme === 'dark') applyTheme(true, false);
    
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isDark = document.documentElement.classList.contains('gl-dark');
            applyTheme(!isDark, true);
        });
    }

    /* ===== 3. Mobile Menu Toggle ===== */
    const mobileToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', () => {
            const isOpen = navMenu.classList.toggle('nb-mobile-open');
            mobileToggle.classList.toggle('nb-active');
            mobileToggle.setAttribute('aria-expanded', isOpen);
            document.body.style.overflow = isOpen ? 'hidden' : '';
        });
        
        // Close on link click (non-dropdown)
        navMenu.querySelectorAll('.nb-link:not(.nb-dropdown-trigger)').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('nb-mobile-open');
                mobileToggle.classList.remove('nb-active');
                document.body.style.overflow = '';
            });
        });
        
        // Mobile dropdown toggle
        document.querySelectorAll('.nb-dropdown-trigger').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                if (window.innerWidth <= 968) {
                    e.preventDefault();
                    const parent = trigger.closest('.nb-dropdown');
                    parent.classList.toggle('nb-mobile-active');
                    trigger.setAttribute('aria-expanded', parent.classList.contains('nb-mobile-active'));
                }
            });
        });
    }

    /* ===== 4. Search Modal (Command Palette) ===== */
    const searchOverlay = document.getElementById('nbSearchOverlay');
    const searchTrigger = document.getElementById('nbSearchTrigger');
    const searchInput = document.getElementById('nbSearchInput');
    
    function openSearch() {
        if (!searchOverlay) return;
        searchOverlay.classList.add('nb-open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => searchInput?.focus(), 150);
    }
    
    function closeSearch() {
        if (!searchOverlay) return;
        searchOverlay.classList.remove('nb-open');
        document.body.style.overflow = '';
        if (searchInput) searchInput.value = '';
    }
    
    if (searchTrigger) searchTrigger.addEventListener('click', openSearch);
    if (searchOverlay) {
        searchOverlay.addEventListener('click', (e) => {
            if (e.target === searchOverlay) closeSearch();
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        // Ctrl/Cmd + K
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            searchOverlay?.classList.contains('nb-open') ? closeSearch() : openSearch();
        }
        // Escape
        if (e.key === 'Escape' && searchOverlay?.classList.contains('nb-open')) {
            closeSearch();
        }
        // Number shortcuts (1-8) when search is open
        if (searchOverlay?.classList.contains('nb-open') && /^[1-8]$/.test(e.key) && !e.ctrlKey && !e.metaKey) {
            const items = searchOverlay.querySelectorAll('.nb-search-item');
            const idx = parseInt(e.key) - 1;
            if (items[idx]) { e.preventDefault(); items[idx].click(); }
        }
    });
    
    // Close search on quick item click
    document.querySelectorAll('.nb-search-item').forEach(item => {
        item.addEventListener('click', closeSearch);
    });

    /* ===== 5. Active Section Tracking (Homepage) ===== */
    if (window.location.pathname.endsWith('index.php') || window.location.pathname === '/' || window.location.pathname.endsWith('/')) {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nb-link');
        
        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY + 150;
            sections.forEach(sec => {
                const top = sec.offsetTop;
                const height = sec.offsetHeight;
                const id = sec.getAttribute('id');
                
                if (scrollY >= top && scrollY < top + height) {
                    navLinks.forEach(link => {
                        link.classList.remove('nb-active');
                        const href = link.getAttribute('href');
                        if (href && (href === `#${id}` || href.endsWith(`#${id}`))) {
                            link.classList.add('nb-active');
                        }
                    });
                }
            });
        }, { passive: true });
    }

    /* ===== 6. Logo Particle Burst ===== */
    const logoIcon = document.querySelector('.nb-logo-icon');
    if (logoIcon) {
        logoIcon.addEventListener('mouseenter', () => {
            const container = logoIcon.querySelector('.nb-logo-particles');
            if (!container) return;
            for (let i = 0; i < 6; i++) {
                const particle = document.createElement('span');
                const angle = (Math.PI * 2 / 6) * i;
                const distance = 25 + Math.random() * 15;
                particle.style.cssText = `
                    position: absolute; width: 4px; height: 4px;
                    background: #10B981; border-radius: 50%;
                    left: 50%; top: 50%;
                    transform: translate(-50%, -50%);
                    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
                    opacity: 1;
                `;
                container.appendChild(particle);
                requestAnimationFrame(() => {
                    particle.style.transform = `translate(${Math.cos(angle) * distance}px, ${Math.sin(angle) * distance}px)`;
                    particle.style.opacity = '0';
                });
                setTimeout(() => particle.remove(), 600);
            }
        });
    }

})();
</script>