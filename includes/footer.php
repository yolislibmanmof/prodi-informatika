<?php
$currentPage = basename($_SERVER['PHP_SELF']);
// Skip global back-to-top jika halaman sudah punya versi custom
$hasCustomBackTop = in_array($currentPage, ['index.php', 'berita-detail.php']);

// Ambil data statistik dinamis dari tabel pengaturan (fallback aman)
try {
    $statMahasiswa = getSetting('statistik_mahasiswa', '518+');
    $statPublikasi = getSetting('statistik_publikasi', '158+');
    $statDosen = getSetting('statistik_dosen', '25+');
    $statAkreditasi = getSetting('akreditasi', 'A');
    
    // Ambil link sosial dinamis
    $sosialIg = getSetting('social_instagram', getSetting('instagram', '#'));
    $sosialYt = getSetting('youtube', '#');
    $sosialLn = getSetting('social_linkedin', getSetting('linkedin', '#'));
    
    // Data kontak dinamis
    $kontakEmail = getSetting('contact_email', getSetting('email', 'info@informatikapro.ac.id'));
    $kontakTelp = getSetting('contact_phone', getSetting('telepon', '(021) 1234-5678'));
    $kontakAlamat = getSetting('contact_address', getSetting('alamat', 'Jl. Informatika No. 1, Jakarta'));
    $kontakJam = getSetting('jam_layanan', 'Senin - Jumat, 08.00 - 16.00 WIB');
    $kontakWa = getSetting('contact_whatsapp', getSetting('whatsapp', '6281234567890'));
} catch (Throwable $e) {
    $statMahasiswa = '518+'; $statPublikasi = '158+'; $statDosen = '25+'; $statAkreditasi = 'A';
    $sosialIg = $sosialYt = $sosialLn = '#';
    $kontakEmail = 'info@informatikapro.ac.id';
    $kontakTelp = '(021) 1234-5678';
    $kontakAlamat = 'Jl. Informatika No. 1, Jakarta';
    $kontakJam = 'Senin - Jumat, 08.00 - 16.00 WIB';
    $kontakWa = '6281234567890';
}
?>

</main>

<!-- ===== FLOATING ACTION HUB (KIRI) ===== -->
<div class="gl-fab-hub" id="glFabHub">
    
    <!-- Quick Contact Panel (Expandable) -->
    <div class="gl-fab-panel" id="glFabPanel">
        <div class="gl-fab-panel-header">
            <span class="gl-fab-panel-title">Quick Contact</span>
            <button class="gl-fab-panel-close" id="glFabPanelClose" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="gl-fab-panel-body">
            <a href="mailto:<?= htmlspecialchars($kontakEmail) ?>" class="gl-fab-quick-item">
                <div class="gl-fab-quick-icon" style="--qi-color: #3B82F6;">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="gl-fab-quick-info">
                    <strong>Email Kami</strong>
                    <span><?= htmlspecialchars($kontakEmail) ?></span>
                </div>
                <i class="fas fa-arrow-right gl-fab-quick-arrow"></i>
            </a>
            <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $kontakTelp)) ?>" class="gl-fab-quick-item">
                <div class="gl-fab-quick-icon" style="--qi-color: #8B5CF6;">
                    <i class="fas fa-phone"></i>
                </div>
                <div class="gl-fab-quick-info">
                    <strong>Telepon Langsung</strong>
                    <span><?= htmlspecialchars($kontakTelp) ?></span>
                </div>
                <i class="fas fa-arrow-right gl-fab-quick-arrow"></i>
            </a>
            <div class="gl-fab-quick-hours">
                <i class="fas fa-clock"></i>
                <span><?= nl2br(htmlspecialchars($kontakJam)) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Quick Contact Toggle Button -->
    <button class="gl-fab-secondary" id="glFabSecondary" aria-label="Lihat kontak lainnya" title="Kontak Lainnya">
        <span class="gl-fab-secondary-icon">
            <i class="fas fa-address-book"></i>
        </span>
        <span class="gl-fab-tooltip">Kontak Lainnya</span>
    </button>
    
    <!-- PRIMARY: WhatsApp Float (KIRI BAWAH) -->
    <a href="https://wa.me/<?= htmlspecialchars($kontakWa) ?>?text=<?= urlencode('Halo Admin Informatika Pro, saya ingin bertanya tentang...') ?>"
       class="gl-wa-float" 
       target="_blank" 
       rel="noopener" 
       aria-label="Chat WhatsApp"
       id="glWaFloat">
        
        <!-- Pulse Rings -->
        <span class="gl-wa-pulse gl-wa-pulse-1"></span>
        <span class="gl-wa-pulse gl-wa-pulse-2"></span>
        <span class="gl-wa-pulse gl-wa-pulse-3"></span>
        
        <!-- Online Badge -->
        <span class="gl-wa-online-badge">
            <span class="gl-wa-online-dot"></span>
            <span>Online</span>
        </span>
        
        <!-- Main Icon -->
        <span class="gl-wa-icon-wrap">
            <i class="fab fa-whatsapp"></i>
            <span class="gl-wa-notification">1</span>
        </span>
        
        <!-- Expandable Label -->
        <span class="gl-wa-label">
            <span class="gl-wa-label-text">Chat Admin</span>
            <span class="gl-wa-label-sub">Balas &lt; 5 menit</span>
        </span>
        
        <!-- Tooltip -->
        <span class="gl-wa-tooltip">
            <span class="gl-wa-tooltip-arrow"></span>
            Chat langsung dengan admin
        </span>
    </a>
</div>

<!-- Global Back to Top (KANAN - hanya jika tidak ada custom) -->
<?php if (!$hasCustomBackTop): ?>
<button class="gl-back-top" id="glBackTop" aria-label="Kembali ke atas" title="Kembali ke atas">
    <svg class="gl-ring" viewBox="0 0 36 36">
        <circle cx="18" cy="18" r="15" fill="none" stroke-width="2" class="gl-ring-bg"/>
        <circle cx="18" cy="18" r="15" fill="none" stroke-width="2" class="gl-ring-fill" id="glRingFill"/>
    </svg>
    <i class="fas fa-arrow-up"></i>
    <span class="gl-btt-tooltip">Kembali ke Atas</span>
</button>
<?php endif; ?>

<!-- Social Proof Strip -->
<section class="gl-proof-strip">
    <div class="container">
        <div class="gl-proof-inner">
            <div class="gl-proof-title">
                <i class="fas fa-award"></i>
                <span>Dipercaya oleh mahasiswa &amp; industri</span>
            </div>
            <div class="gl-proof-stats">
                <div class="gl-proof-item">
                    <div class="gl-proof-num"><?= htmlspecialchars($statMahasiswa) ?></div>
                    <div class="gl-proof-label">Mahasiswa &amp; Alumni</div>
                </div>
                <div class="gl-proof-divider"></div>
                <div class="gl-proof-item">
                    <div class="gl-proof-num"><?= htmlspecialchars($statPublikasi) ?></div>
                    <div class="gl-proof-label">Publikasi Riset</div>
                </div>
                <div class="gl-proof-divider"></div>
                <div class="gl-proof-item">
                    <div class="gl-proof-num"><?= htmlspecialchars($statDosen) ?></div>
                    <div class="gl-proof-label">Dosen Ahli</div>
                </div>
                <div class="gl-proof-divider"></div>
                <div class="gl-proof-item">
                    <div class="gl-proof-num"><?= htmlspecialchars($statAkreditasi) ?></div>
                    <div class="gl-proof-label">Akreditasi</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Footer -->
<footer class="footer gl-footer" role="contentinfo">
    <div class="container">

        <!-- Accreditation Bar -->
        <div class="gl-accreditation">
            <div class="gl-accr-badge">
                <div class="gl-accr-icon"><i class="fas fa-certificate"></i></div>
                <div>
                    <div class="gl-accr-title">Terakreditasi Baik Sekali</div>
                    <div class="gl-accr-sub">LAM INFOKOM · No. 011/SK/LAM-INFOKOM/Ak.PNB/S/V/2024</div>
                </div>
            </div>
            <div class="gl-accr-partners">
                <span>Didukung oleh:</span>
                <div class="gl-partner-logos">
                    <span>🏛️ Kemendikbud</span>
                    <span>💼 BRIN</span>
                    <span>🌐 Google Dev</span>
                    <span>☁️ AWS Educate</span>
                </div>
            </div>
        </div>

        <!-- Footer Grid -->
        <div class="footer-grid gl-footer-grid">
            <!-- Brand -->
            <div class="footer-col gl-col-brand">
                <a href="<?= SITE_URL ?>/" class="logo gl-footer-logo">
                    <div class="gl-logo-wrap">
                        <i class="fas fa-code"></i>
                    </div>
                    <span>Informatika<span class="accent">Pro</span></span>
                </a>
                <p>Program Studi Informatika terdepan yang mencetak lulusan berkualitas untuk era transformasi digital melalui kurikulum berbasis industri.</p>
                <div class="social-links gl-social">
                    <a href="<?= htmlspecialchars($sosialIg) ?>" aria-label="Instagram" title="Instagram" <?= $sosialIg === '#' ? '' : 'target="_blank" rel="noopener"' ?>><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Facebook" title="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Twitter" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="<?= htmlspecialchars($sosialLn) ?>" aria-label="LinkedIn" title="LinkedIn" <?= $sosialLn === '#' ? '' : 'target="_blank" rel="noopener"' ?>><i class="fab fa-linkedin"></i></a>
                    <a href="<?= htmlspecialchars($sosialYt) ?>" aria-label="YouTube" title="YouTube" <?= $sosialYt === '#' ? '' : 'target="_blank" rel="noopener"' ?>><i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="GitHub" title="GitHub"><i class="fab fa-github"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-col">
                <h4>Tautan Cepat</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/#home"><i class="fas fa-chevron-right mini"></i> Beranda</a></li>
                    <li><a href="<?= SITE_URL ?>/kurikulum.php"><i class="fas fa-chevron-right mini"></i> Kurikulum</a></li>
                    <li><a href="<?= SITE_URL ?>/agenda.php"><i class="fas fa-chevron-right mini"></i> Agenda Kegiatan</a></li>
                    <li><a href="<?= SITE_URL ?>/dokumen.php"><i class="fas fa-chevron-right mini"></i> Dokumen &amp; Unduhan</a></li>
                    <li><a href="<?= SITE_URL ?>/prestasi.php"><i class="fas fa-chevron-right mini"></i> Prestasi</a></li>
                    <li><a href="<?= SITE_URL ?>/berita.php"><i class="fas fa-chevron-right mini"></i> Berita Terbaru</a></li>
                    <li><a href="<?= SITE_URL ?>/daftar.php"><i class="fas fa-chevron-right mini"></i> Pendaftaran</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="footer-col">
                <h4>Hubungi Kami</h4>
                <ul class="contact-info gl-contact-list">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= nl2br(htmlspecialchars($kontakAlamat)) ?></span>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $kontakTelp)) ?>"><?= htmlspecialchars($kontakTelp) ?></a>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?= htmlspecialchars($kontakEmail) ?>"><?= htmlspecialchars($kontakEmail) ?></a>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <span><?= nl2br(htmlspecialchars($kontakJam)) ?></span>
                    </li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div class="footer-col gl-col-newsletter">
                <h4>Newsletter</h4>
                <p>Dapatkan update berita, event, dan info beasiswa langsung di inbox Anda.</p>
                <form class="newsletter-form gl-newsletter" id="glNewsletter" action="<?= SITE_URL ?>/newsletter-subscribe.php" method="POST" novalidate>
                    <?php if (class_exists('Security')) Security::csrfField(); ?>
                    <input type="hidden" name="source" value="footer">
                    <div class="gl-newsletter-input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="glNewsEmail" name="email" placeholder="Email Anda" required autocomplete="email">
                    </div>
                    <button type="submit" id="glNewsBtn" aria-label="Berlangganan">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                <div class="gl-newsletter-note" id="glNewsNote">
                    <i class="fas fa-shield-alt"></i>
                    <span>Kami menghargai privasi Anda. Tidak ada spam.</span>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom gl-footer-bottom">
            <div class="gl-copy">
                &copy; <?= date('Y') ?> <strong><?= SITE_NAME ?></strong>. All rights reserved.
                Made with <i class="fas fa-heart" style="color:#10B981"></i> di Indonesia
            </div>
            <div class="gl-legal">
                <a href="#">Kebijakan Privasi</a>
                <span class="gl-legal-sep">·</span>
                <a href="#">Syarat &amp; Ketentuan</a>
                <span class="gl-legal-sep">·</span>
                <a href="<?= SITE_URL ?>/sitemap.xml">Sitemap</a>
                <span class="gl-legal-sep">·</span>
                <a href="<?= SITE_URL ?>/admin/login.php"><i class="fas fa-lock"></i> Admin</a>
            </div>
        </div>
    </div>
</footer>

<!-- Global Scripts -->
<script src="<?= SITE_URL ?>/js/script.js"></script>
<script src="<?= SITE_URL ?>/js/lazy-load.js" defer></script>

<style>
/* ============================================================
   GLOBAL FOOTER ENHANCEMENTS (prefix: gl-)
============================================================ */

/* ===== Back to Top Global (KANAN) ===== */
.gl-back-top {
    position: fixed; bottom: 30px; right: 24px; z-index: 997;
    width: 54px; height: 54px; border-radius: 50%;
    background: linear-gradient(135deg, #10B981, #047857);
    border: none; color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; opacity: 0; visibility: hidden;
    transform: translateY(20px) scale(.8);
    transition: all .4s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 8px 24px rgba(16,185,129,.4);
}
.gl-back-top.show { opacity: 1; visibility: visible; transform: none; }
.gl-back-top:hover { transform: translateY(-4px) scale(1.05); box-shadow: 0 12px 28px rgba(16,185,129,.5); }
.gl-ring { position: absolute; width: 100%; height: 100%; transform: rotate(-90deg); }
.gl-ring-bg { stroke: rgba(255,255,255,.2); }
.gl-ring-fill { stroke: #fff; stroke-linecap: round; stroke-dasharray: 94.25; stroke-dashoffset: 94.25; transition: stroke-dashoffset .3s; }

.gl-btt-tooltip {
    position: absolute; right: calc(100% + 12px); top: 50%;
    transform: translateY(-50%);
    background: rgba(15,23,42,0.95); color: #fff;
    padding: 6px 12px; border-radius: 8px;
    font-size: .75rem; font-weight: 600;
    white-space: nowrap;
    opacity: 0; pointer-events: none;
    transition: opacity .3s, transform .3s;
    backdrop-filter: blur(10px);
}
.gl-btt-tooltip::after {
    content: ''; position: absolute; top: 50%; right: -4px;
    transform: translateY(-50%) rotate(45deg);
    width: 8px; height: 8px;
    background: rgba(15,23,42,0.95);
}
.gl-back-top:hover .gl-btt-tooltip {
    opacity: 1; transform: translateY(-50%) translateX(-4px);
}

/* ===== FLOATING ACTION HUB (KIRI) ===== */
.gl-fab-hub {
    position: fixed; bottom: 30px; left: 24px; z-index: 998;
    display: flex; flex-direction: column-reverse; align-items: flex-start;
    gap: 12px;
}

/* ===== PRIMARY: WhatsApp Float (KIRI BAWAH) ===== */
.gl-wa-float {
    position: relative;
    display: flex; align-items: center;
    height: 60px;
    padding: 0 20px 0 0;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    border-radius: 60px;
    text-decoration: none;
    box-shadow: 0 8px 24px rgba(37, 211, 102, 0.4),
                0 4px 12px rgba(37, 211, 102, 0.2);
    transition: all .4s cubic-bezier(.4,0,.2,1);
    overflow: visible;
    isolation: isolate;
}

.gl-wa-float:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(37, 211, 102, 0.5),
                0 6px 16px rgba(37, 211, 102, 0.3);
}

/* Pulse Rings - Animasi berdenyut */
.gl-wa-pulse {
    position: absolute;
    left: 8px; top: 50%;
    width: 44px; height: 44px;
    border-radius: 50%;
    border: 2px solid #25D366;
    transform: translateY(-50%);
    opacity: 0;
    pointer-events: none;
    z-index: -1;
}
.gl-wa-pulse-1 { animation: glWaPulse 2.4s cubic-bezier(0.4, 0, 0.2, 1) infinite; }
.gl-wa-pulse-2 { animation: glWaPulse 2.4s cubic-bezier(0.4, 0, 0.2, 1) infinite 0.8s; }
.gl-wa-pulse-3 { animation: glWaPulse 2.4s cubic-bezier(0.4, 0, 0.2, 1) infinite 1.6s; }

@keyframes glWaPulse {
    0% {
        transform: translateY(-50%) scale(1);
        opacity: 0.8;
    }
    100% {
        transform: translateY(-50%) scale(2.2);
        opacity: 0;
    }
}

/* Icon Wrap */
.gl-wa-icon-wrap {
    position: relative;
    width: 60px; height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #25D366, #128C7E);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: inset 0 0 0 2px rgba(255,255,255,0.2);
}
.gl-wa-icon-wrap i {
    font-size: 1.8rem;
    color: #fff;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    transition: transform .3s;
}
.gl-wa-float:hover .gl-wa-icon-wrap i {
    transform: scale(1.15) rotate(-8deg);
}

/* Notification Badge */
.gl-wa-notification {
    position: absolute;
    top: -4px; right: -4px;
    min-width: 20px; height: 20px;
    padding: 0 6px;
    background: #EF4444;
    color: #fff;
    border-radius: 10px;
    font-size: .7rem;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid #fff;
    box-shadow: 0 2px 6px rgba(239,68,68,0.4);
    animation: glNotifBounce 2s ease-in-out infinite;
}
@keyframes glNotifBounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Online Badge */
.gl-wa-online-badge {
    position: absolute;
    top: -10px; left: 50%;
    transform: translateX(-50%);
    background: #fff;
    color: #10B981;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .65rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    display: flex; align-items: center; gap: 5px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: all .3s;
}
.gl-wa-online-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #10B981;
    box-shadow: 0 0 0 0 rgba(16,185,129,0.7);
    animation: glOnlinePulse 2s infinite;
}
@keyframes glOnlinePulse {
    0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.7); }
    70% { box-shadow: 0 0 0 6px rgba(16,185,129,0); }
    100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
}
.gl-wa-float:hover .gl-wa-online-badge {
    opacity: 1;
    top: -28px;
}

/* Expandable Label */
.gl-wa-label {
    display: flex; flex-direction: column;
    padding-left: 0;
    max-width: 0;
    overflow: hidden;
    opacity: 0;
    transition: all .4s cubic-bezier(.4,0,.2,1);
    white-space: nowrap;
}
.gl-wa-label-text {
    font-size: .9rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
}
.gl-wa-label-sub {
    font-size: .7rem;
    color: rgba(255,255,255,0.85);
    font-weight: 500;
    line-height: 1.2;
}
.gl-wa-float:hover .gl-wa-label {
    max-width: 160px;
    padding-left: 12px;
    padding-right: 8px;
    opacity: 1;
}

/* Tooltip */
.gl-wa-tooltip {
    position: absolute;
    bottom: calc(100% + 12px);
    left: 30px;
    transform: translateX(-50%);
    background: rgba(15,23,42,0.95);
    color: #fff;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: .75rem;
    font-weight: 600;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: all .3s;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.gl-wa-tooltip-arrow {
    position: absolute;
    bottom: -4px; left: 50%;
    transform: translateX(-50%) rotate(45deg);
    width: 8px; height: 8px;
    background: rgba(15,23,42,0.95);
}
.gl-wa-float:hover .gl-wa-tooltip {
    opacity: 1;
    bottom: calc(100% + 16px);
}

/* ===== SECONDARY BUTTON (Contact Hub) ===== */
.gl-fab-secondary {
    position: relative;
    width: 48px; height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10B981, #047857);
    border: none;
    color: #fff;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 6px 18px rgba(16,185,129,0.4);
    transition: all .3s cubic-bezier(.4,0,.2,1);
    font-size: 1.1rem;
}
.gl-fab-secondary:hover {
    transform: translateY(-3px) rotate(-8deg);
    box-shadow: 0 10px 24px rgba(16,185,129,0.5);
}
.gl-fab-secondary.active {
    transform: rotate(180deg);
    background: linear-gradient(135deg, #EF4444, #DC2626);
}

.gl-fab-secondary-icon {
    display: flex; align-items: center; justify-content: center;
    transition: transform .3s;
}

.gl-fab-tooltip {
    position: absolute;
    bottom: calc(100% + 10px);
    left: 50%;
    transform: translateX(-50%);
    background: rgba(15,23,42,0.95);
    color: #fff;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: .75rem;
    font-weight: 600;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity .3s;
    backdrop-filter: blur(10px);
}
.gl-fab-tooltip::after {
    content: ''; position: absolute;
    top: 100%; left: 50%;
    transform: translateX(-50%) rotate(45deg);
    width: 8px; height: 8px;
    background: rgba(15,23,42,0.95);
    margin-top: -4px;
}
.gl-fab-secondary:hover .gl-fab-tooltip {
    opacity: 1;
}

/* ===== QUICK CONTACT PANEL ===== */
.gl-fab-panel {
    position: absolute;
    bottom: calc(100% + 60px);
    left: 0;
    width: 320px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.15),
                0 8px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px) scale(0.95);
    transition: all .35s cubic-bezier(.4,0,.2,1);
    z-index: 10;
}
.gl-fab-panel.open {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}
body.gl-dark .gl-fab-panel {
    background: #1E293B;
    box-shadow: 0 20px 50px rgba(0,0,0,0.4);
}

.gl-fab-panel-header {
    padding: 16px 20px;
    background: linear-gradient(135deg, #10B981, #047857);
    color: #fff;
    display: flex; justify-content: space-between; align-items: center;
}
.gl-fab-panel-title {
    font-size: .9rem;
    font-weight: 800;
    letter-spacing: 0.5px;
}
.gl-fab-panel-close {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    border: none;
    color: #fff;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .3s;
    font-size: .8rem;
}
.gl-fab-panel-close:hover {
    background: rgba(255,255,255,0.3);
    transform: rotate(90deg);
}

.gl-fab-panel-body {
    padding: 12px;
    display: flex; flex-direction: column; gap: 6px;
}

.gl-fab-quick-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px;
    border-radius: 12px;
    text-decoration: none;
    transition: all .3s;
    border: 1px solid transparent;
}
.gl-fab-quick-item:hover {
    background: var(--gray-50, #F8FAFC);
    border-color: var(--gray-200, #E2E8F0);
    transform: translateX(4px);
}
body.gl-dark .gl-fab-quick-item:hover {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
}

.gl-fab-quick-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: color-mix(in srgb, var(--qi-color, #10B981) 12%, transparent);
    color: var(--qi-color, #10B981);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.gl-fab-quick-info {
    flex: 1;
    min-width: 0;
}
.gl-fab-quick-info strong {
    display: block;
    font-size: .85rem;
    font-weight: 700;
    color: var(--black-primary, #0F172A);
    margin-bottom: 2px;
}
body.gl-dark .gl-fab-quick-info strong { color: #F1F5F9; }
.gl-fab-quick-info span {
    display: block;
    font-size: .72rem;
    color: var(--gray-600, #475569);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
body.gl-dark .gl-fab-quick-info span { color: #94A3B8; }

.gl-fab-quick-arrow {
    color: var(--gray-400, #94A3B8);
    font-size: .75rem;
    transition: all .3s;
    flex-shrink: 0;
}
.gl-fab-quick-item:hover .gl-fab-quick-arrow {
    color: var(--qi-color, #10B981);
    transform: translateX(4px);
}

.gl-fab-quick-hours {
    padding: 10px 12px;
    margin-top: 4px;
    background: var(--gray-50, #F8FAFC);
    border-radius: 10px;
    display: flex; align-items: center; gap: 10px;
    font-size: .72rem;
    color: var(--gray-600, #475569);
    line-height: 1.4;
}
body.gl-dark .gl-fab-quick-hours {
    background: rgba(255,255,255,0.03);
    color: #94A3B8;
}
.gl-fab-quick-hours i {
    color: var(--primary, #10B981);
    font-size: .85rem;
    flex-shrink: 0;
}

/* ===== Social Proof Strip ===== */
.gl-proof-strip {
    background: linear-gradient(135deg, #10B981 0%, #047857 100%);
    padding: 40px 0; color: #fff; position: relative; overflow: hidden;
}
.gl-proof-strip::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(circle at 20% 50%, rgba(255,255,255,.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(255,255,255,.08) 0%, transparent 50%);
}
.gl-proof-inner {
    position: relative; z-index: 1;
    display: flex; flex-direction: column; align-items: center; gap: 24px;
}
.gl-proof-title {
    display: flex; align-items: center; gap: 10px;
    font-size: .95rem; font-weight: 500;
}
.gl-proof-title i { font-size: 1.2rem; color: #FEF3C7; }
.gl-proof-stats {
    display: flex; align-items: center; gap: 32px; flex-wrap: wrap; justify-content: center;
}
.gl-proof-item { text-align: center; }
.gl-proof-num {
    font-family: 'Space Grotesk', sans-serif; font-size: 2.2rem; font-weight: 700;
    line-height: 1; margin-bottom: 4px;
}
.gl-proof-label { font-size: .82rem; opacity: .9; }
.gl-proof-divider { width: 1px; height: 40px; background: rgba(255,255,255,.25); }

/* ===== Accreditation Bar ===== */
.gl-accreditation {
    display: flex; justify-content: space-between; align-items: center;
    padding: 24px; margin-bottom: 40px;
    background: linear-gradient(135deg, rgba(16,185,129,.08), rgba(4,120,87,.03));
    border: 1px solid rgba(16,185,129,.2); border-radius: 20px;
    flex-wrap: wrap; gap: 20px;
}
.gl-accr-badge { display: flex; align-items: center; gap: 14px; }
.gl-accr-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: linear-gradient(135deg, #10B981, #047857);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
    box-shadow: 0 8px 20px rgba(16,185,129,.3);
}
.gl-accr-title { font-weight: 700; font-size: 1rem; color: var(--black-primary); }
body.gl-dark .gl-accr-title { color: #fff; }
.gl-accr-sub { font-size: .78rem; color: var(--gray-600); margin-top: 2px; }
body.gl-dark .gl-accr-sub { color: #94A3B8; }

.gl-accr-partners { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.gl-accr-partners > span { font-size: .82rem; color: var(--gray-600); font-weight: 500; }
body.gl-dark .gl-accr-partners > span { color: #94A3B8; }
.gl-partner-logos { display: flex; gap: 12px; flex-wrap: wrap; }
.gl-partner-logos span {
    padding: 6px 12px; background: rgba(255,255,255,.8);
    border-radius: 8px; font-size: .78rem; font-weight: 600;
    color: var(--black-primary); border: 1px solid rgba(0,0,0,.06);
}
body.gl-dark .gl-partner-logos span { background: rgba(255,255,255,.08); color: #E2E8F0; border-color: rgba(255,255,255,.1); }

/* ===== Footer Enhancements ===== */
.gl-footer-logo {
    display: flex; align-items: center; gap: 12px; margin-bottom: 16px;
    font-family: 'Space Grotesk', sans-serif; font-size: 1.3rem; font-weight: 700;
    text-decoration: none; color: inherit;
}
.gl-footer-logo .gl-logo-wrap {
    width: 40px; height: 40px; font-size: 1rem;
    box-shadow: 0 4px 12px rgba(16,185,129,.3);
}
.gl-col-brand p { font-size: .9rem; line-height: 1.6; margin-bottom: 20px; }

.gl-social { display: flex; gap: 10px; }
.gl-social a {
    width: 40px; height: 40px; border-radius: 12px;
    background: rgba(255,255,255,.08); display: flex;
    align-items: center; justify-content: center;
    transition: all .3s; color: #fff;
}
.gl-social a:hover {
    background: var(--green-primary); transform: translateY(-3px) rotate(-8deg);
    box-shadow: 0 8px 16px rgba(16,185,129,.35);
}

.gl-contact-list a { color: rgba(255,255,255,.7); text-decoration: none; transition: color .2s; }
.gl-contact-list a:hover { color: var(--green-primary); }
.gl-contact-list i { color: var(--green-primary); margin-top: 4px; flex-shrink: 0; width: 16px; }
.gl-contact-list li { display: flex; gap: 12px; align-items: flex-start; }
.gl-contact-list li .fas.fa-chevron-right.mini { display: none; }

/* Footer list chevrons */
.footer-col ul:not(.contact-info) a {
    display: flex; align-items: center; gap: 8px;
    transition: all .25s;
}
.footer-col ul:not(.contact-info) a:hover {
    color: var(--green-primary); padding-left: 4px;
}
.footer-col .mini { font-size: .55rem; color: var(--green-primary); opacity: 0; transition: opacity .2s; }
.footer-col ul a:hover .mini { opacity: 1; }

/* ===== Newsletter ===== */
.gl-col-newsletter p { font-size: .88rem; line-height: 1.5; margin-bottom: 14px; }
.gl-newsletter {
    display: flex; gap: 8px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 50px;
    padding: 4px; transition: border-color .3s;
}
.gl-newsletter:focus-within { border-color: var(--green-primary); }
.gl-newsletter-input-wrap {
    flex: 1; display: flex; align-items: center; gap: 10px;
    padding: 0 16px;
}
.gl-newsletter-input-wrap i { color: rgba(255,255,255,.5); font-size: .85rem; }
.gl-newsletter input {
    flex: 1; background: transparent; border: none; outline: none;
    padding: 10px 0; color: #fff; font-family: inherit; font-size: .88rem;
}
.gl-newsletter input::placeholder { color: rgba(255,255,255,.4); }
.gl-newsletter button {
    width: 40px; height: 40px; border-radius: 50%;
    background: var(--green-primary); border: none;
    color: #fff; cursor: pointer; transition: all .3s;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.gl-newsletter button:hover { background: #34D399; transform: scale(1.1); }
.gl-newsletter button:disabled { opacity: .7; cursor: wait; }
.gl-newsletter button.success { background: #059669; }
.gl-newsletter button.success i::before { content: "\f00c"; }

.gl-newsletter-note {
    display: flex; align-items: center; gap: 8px;
    margin-top: 12px; font-size: .75rem; color: rgba(255,255,255,.5);
}
.gl-newsletter-note i { color: var(--green-primary); }

/* ===== Footer Bottom ===== */
.gl-footer-bottom {
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 12px; padding-top: 30px;
}
.gl-copy { font-size: .85rem; color: rgba(255,255,255,.6); }
.gl-copy strong { color: #fff; }
.gl-legal { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.gl-legal a {
    color: rgba(255,255,255,.6); text-decoration: none;
    font-size: .82rem; transition: color .2s;
}
.gl-legal a:hover { color: var(--green-primary); }
.gl-legal-sep { color: rgba(255,255,255,.3); }

/* ===== Responsive ===== */
@media (max-width: 768px) {
    .gl-fab-hub {
        bottom: 20px; left: 16px;
        gap: 10px;
    }
    .gl-wa-float {
        height: 56px;
    }
    .gl-wa-icon-wrap {
        width: 56px; height: 56px;
    }
    .gl-wa-icon-wrap i {
        font-size: 1.6rem;
    }
    .gl-wa-pulse {
        left: 6px;
        width: 40px; height: 40px;
    }
    .gl-wa-float:hover .gl-wa-label {
        max-width: 130px;
    }
    .gl-wa-float .gl-wa-label-sub {
        display: none;
    }
    .gl-fab-secondary {
        width: 44px; height: 44px;
        font-size: 1rem;
    }
    .gl-fab-panel {
        width: calc(100vw - 32px);
        max-width: 320px;
        bottom: calc(100% + 50px);
    }
    .gl-back-top {
        bottom: 20px; right: 16px;
        width: 48px; height: 48px;
    }
    .gl-btt-tooltip { display: none; }
    .gl-proof-stats { gap: 20px; }
    .gl-proof-num { font-size: 1.6rem; }
    .gl-proof-divider { height: 30px; }
    .gl-accreditation { flex-direction: column; align-items: flex-start; }
    .gl-footer-bottom { flex-direction: column; text-align: center; }
}

@media (max-width: 480px) {
    .gl-wa-float:hover .gl-wa-label {
        max-width: 110px;
    }
    .gl-wa-label-text {
        font-size: .82rem;
    }
    .gl-wa-tooltip {
        font-size: .7rem;
        padding: 6px 10px;
    }
}

/* ===== Accessibility ===== */
.gl-wa-float:focus-visible,
.gl-fab-secondary:focus-visible,
.gl-back-top:focus-visible {
    outline: 3px solid #10B981;
    outline-offset: 4px;
}

/* ===== LAZY LOAD SHIMMER EFFECT ===== */
img[loading="lazy"] {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    min-height: 200px; /* Sesuaikan dengan tinggi kartu berita Anda */
    border-radius: 8px;
}

img[loading="lazy"].loaded,
img[loading="lazy"]:not([data-src]) {
    animation: none;
    background: none;
    min-height: auto;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
</style>

<script>
(function() {
    'use strict';

    /* ===== 1. Global Back to Top (conditional) ===== */
    const glBackTop = document.getElementById('glBackTop');
    const glRingFill = document.getElementById('glRingFill');

    if (glBackTop && glRingFill) {
        const circumference = 94.25;
        window.addEventListener('scroll', () => {
            const y = window.scrollY;
            const max = document.documentElement.scrollHeight - window.innerHeight;
            const percent = max > 0 ? y / max : 0;

            if (y > 500) glBackTop.classList.add('show');
            else glBackTop.classList.remove('show');

            glRingFill.style.strokeDashoffset = circumference - (percent * circumference);
        }, { passive: true });

        glBackTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ===== 2. Quick Contact Panel Toggle ===== */
    const fabSecondary = document.getElementById('glFabSecondary');
    const fabPanel = document.getElementById('glFabPanel');
    const fabPanelClose = document.getElementById('glFabPanelClose');

    if (fabSecondary && fabPanel) {
        const togglePanel = (forceState) => {
            const isOpen = fabPanel.classList.contains('open');
            const shouldOpen = forceState !== undefined ? forceState : !isOpen;
            
            fabPanel.classList.toggle('open', shouldOpen);
            fabSecondary.classList.toggle('active', shouldOpen);
            
            // Update icon
            const icon = fabSecondary.querySelector('i');
            if (icon) {
                icon.className = shouldOpen ? 'fas fa-times' : 'fas fa-address-book';
            }
        };

        fabSecondary.addEventListener('click', () => togglePanel());
        
        if (fabPanelClose) {
            fabPanelClose.addEventListener('click', () => togglePanel(false));
        }

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (fabPanel.classList.contains('open') && 
                !fabPanel.contains(e.target) && 
                !fabSecondary.contains(e.target)) {
                togglePanel(false);
            }
        });

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && fabPanel.classList.contains('open')) {
                togglePanel(false);
                fabSecondary.focus();
            }
        });
    }

    /* ===== 3. WhatsApp Float - Auto-hide on scroll down, show on scroll up ===== */
    const waFloat = document.getElementById('glWaFloat');
    if (waFloat) {
        let lastScrollY = window.scrollY;
        let scrollTimeout;

        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            
            const currentScrollY = window.scrollY;
            const scrollDiff = currentScrollY - lastScrollY;
            
            // Jika scroll ke bawah lebih dari 10px, sembunyikan WA (opsional)
            if (scrollDiff > 10 && currentScrollY > 300) {
                waFloat.style.transform = 'translateX(-120%)';
                waFloat.style.opacity = '0';
            } else if (scrollDiff < -10 || currentScrollY < 300) {
                waFloat.style.transform = 'translateX(0)';
                waFloat.style.opacity = '1';
            }
            
            lastScrollY = currentScrollY;
            
            scrollTimeout = setTimeout(() => {
                waFloat.style.transform = 'translateX(0)';
                waFloat.style.opacity = '1';
            }, 150);
        }, { passive: true });

        // Track click untuk analytics (optional)
        waFloat.addEventListener('click', () => {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'click_whatsapp', {
                    'event_category': 'engagement',
                    'event_label': 'footer_wa_float'
                });
            }
        });
    }

    /* ===== 4. Newsletter Handler (Submit ke Backend via Fetch) ===== */
    const nlForm = document.getElementById('glNewsletter');
    const nlInput = document.getElementById('glNewsEmail');
    const nlBtn = document.getElementById('glNewsBtn');
    const nlNote = document.getElementById('glNewsNote');

    const setNote = (icon, color, text) => {
        if (!nlNote) return;
        nlNote.innerHTML = `<i class="fas ${icon}" style="color:${color}"></i><span style="color:${color}">${text}</span>`;
    };

    if (nlForm) {
        nlForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = nlInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailRegex.test(email)) {
                nlInput.style.boxShadow = '0 0 0 2px rgba(239,68,68,.4)';
                nlInput.focus();
                setNote('fa-exclamation-circle', '#FCA5A5', 'Format email tidak valid');
                setTimeout(() => {
                    nlInput.style.boxShadow = '';
                    setNote('fa-shield-alt', 'rgba(255,255,255,.5)', 'Kami menghargai privasi Anda. Tidak ada spam.');
                }, 3000);
                return;
            }

            nlBtn.disabled = true;
            const originalIcon = nlBtn.innerHTML;
            nlBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const formData = new FormData(nlForm);
                const response = await fetch(nlForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });

                const data = await response.json();

                if (data.success) {
                    nlBtn.classList.add('success');
                    nlBtn.innerHTML = '<i class="fas fa-check"></i>';
                    nlInput.value = '';
                    setNote('fa-check-circle', '#34D399', data.message || 'Terima kasih! Silakan cek inbox untuk konfirmasi.');
                } else {
                    nlBtn.innerHTML = originalIcon;
                    setNote('fa-exclamation-circle', '#FCA5A5', data.message || 'Gagal berlangganan. Coba lagi.');
                }
            } catch (err) {
                console.error('Newsletter error:', err);
                nlBtn.innerHTML = originalIcon;
                setNote('fa-exclamation-circle', '#FCA5A5', 'Terjadi kesalahan jaringan. Coba lagi nanti.');
            } finally {
                setTimeout(() => {
                    if (!nlBtn.classList.contains('success')) {
                        nlBtn.disabled = false;
                    } else {
                        nlBtn.classList.remove('success');
                        nlBtn.disabled = false;
                    }
                    nlBtn.innerHTML = originalIcon;
                    if (!nlNote.querySelector('.fa-check-circle')) {
                        setNote('fa-shield-alt', 'rgba(255,255,255,.5)', 'Kami menghargai privasi Anda. Tidak ada spam.');
                    }
                }, 4000);
            }
        });
    }

    /* ===== 5. Footer Reveal Animation ===== */
    const footerObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                footerObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.gl-footer, .gl-proof-strip, .gl-accreditation').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity .8s, transform .8s';
        footerObserver.observe(el);
    });

    /* ===== 6. Current Year Auto-Update ===== */
    const yearEl = document.querySelector('.gl-copy');
    if (yearEl && !yearEl.textContent.includes(new Date().getFullYear())) {
        yearEl.innerHTML = yearEl.innerHTML.replace(/\d{4}/, new Date().getFullYear());
    }

    /* ===== 7. Keyboard Accessibility ===== */
    document.querySelectorAll('.gl-social a, .gl-fab-quick-item').forEach(el => {
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                el.click();
            }
        });
    });

    /* ===== 8. Online Status Simulation ===== */
    const onlineDot = document.querySelector('.gl-wa-online-dot');
    if (onlineDot) {
        // Simulasi "Online" pada jam kerja
        const checkOnlineStatus = () => {
            const now = new Date();
            const hour = now.getHours();
            const day = now.getDay();
            const isWorkHours = day >= 1 && day <= 5 && hour >= 8 && hour < 16;
            
            const badge = document.querySelector('.gl-wa-online-badge');
            if (badge) {
                if (isWorkHours) {
                    badge.querySelector('span:last-child').textContent = 'Online';
                    onlineDot.style.background = '#10B981';
                } else {
                    badge.querySelector('span:last-child').textContent = 'Away';
                    onlineDot.style.background = '#F59E0B';
                }
            }
        };
        checkOnlineStatus();
        setInterval(checkOnlineStatus, 60000); // Update setiap menit
    }

    /* ===== 9. Notification Badge Auto-dismiss ===== */
    const notifBadge = document.querySelector('.gl-wa-notification');
    if (notifBadge) {
        // Hilangkan notifikasi setelah user click atau setelah 10 detik
        setTimeout(() => {
            notifBadge.style.opacity = '0';
            setTimeout(() => notifBadge.remove(), 300);
        }, 10000);
    }

})();
</script>

<?php
// Deteksi halaman saat ini dengan aman
$currentPageFooter = basename($_SERVER['PHP_SELF']);

// HANYA tampilkan loader ini jika BUKAN index.php
if ($currentPageFooter !== 'index.php' && $currentPageFooter !== ''): 
?>

<!-- ===== QUANTUM HOLOGRAPHIC LOADER (SMART) ===== -->
<div id="quantumLoader" class="quantum-loader-overlay">
    <div class="quantum-core">
        <div class="quantum-ring"></div>
        <div class="quantum-ring"></div>
        <div class="quantum-core-dot"></div>
    </div>
    <div class="quantum-text">
        <span id="qLoaderName">MEMUAT</span>
        <span class="q-glitch" data-text="...">...</span>
    </div>
    <div class="quantum-bar">
        <div class="quantum-bar-fill"></div>
    </div>
</div>

<style>
/* Desain Premium Quantum Loader */
.quantum-loader-overlay {
    position: fixed; inset: 0; z-index: 99999;
    background: rgba(2, 6, 23, 0.96);
    backdrop-filter: blur(16px);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    opacity: 0; visibility: hidden; pointer-events: none;
    transition: opacity 0.4s ease, visibility 0.4s ease;
}
/* Class active akan menimpa opacity/visibility di atas */
.quantum-loader-overlay.active {
    opacity: 1; visibility: visible; pointer-events: auto;
}
.quantum-core { width: 80px; height: 80px; position: relative; margin-bottom: 32px; }
.quantum-ring {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid transparent; border-top-color: #10B981; border-right-color: #06B6D4;
    animation: qSpin 1.2s linear infinite;
}
.quantum-ring:nth-child(2) {
    inset: 8px; border-top-color: #8B5CF6; border-left-color: #EC4899;
    animation-duration: 1.8s; animation-direction: reverse;
}
.quantum-core-dot {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    width: 12px; height: 12px; background: #10B981; border-radius: 50%;
    box-shadow: 0 0 20px #10B981, 0 0 40px #06B6D4;
    animation: qPulse 1.5s ease-in-out infinite;
}
.quantum-text {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.1rem; font-weight: 800; color: #fff;
    letter-spacing: 4px; text-transform: uppercase; margin-bottom: 24px; position: relative;
}
.q-glitch {
    position: relative; display: inline-block; color: #10B981;
}
.q-glitch::before, .q-glitch::after {
    content: attr(data-text); position: absolute; top: 0; left: 0;
    width: 100%; height: 100%; background: rgba(2, 6, 23, 0.96);
}
.q-glitch::before {
    left: 2px; text-shadow: -1px 0 #EC4899;
    animation: qGlitch1 2s infinite linear alternate-reverse;
}
.q-glitch::after {
    left: -2px; text-shadow: -1px 0 #06B6D4;
    animation: qGlitch2 3s infinite linear alternate-reverse;
}
.quantum-bar {
    width: 200px; height: 3px; background: rgba(255,255,255,0.1);
    border-radius: 2px; overflow: hidden;
}
.quantum-bar-fill {
    height: 100%; width: 30%;
    background: linear-gradient(90deg, #10B981, #06B6D4, #8B5CF6);
    background-size: 200% 100%;
    animation: qShimmer 1.5s ease-in-out infinite;
}
@keyframes qSpin { to { transform: rotate(360deg); } }
@keyframes qPulse { 0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 1; } 50% { transform: translate(-50%, -50%) scale(1.5); opacity: 0.7; } }
@keyframes qGlitch1 { 0% { clip-path: inset(20% 0 80% 0); } 20% { clip-path: inset(60% 0 10% 0); } 40% { clip-path: inset(40% 0 50% 0); } 60% { clip-path: inset(80% 0 5% 0); } 80% { clip-path: inset(10% 0 70% 0); } 100% { clip-path: inset(30% 0 20% 0); } }
@keyframes qGlitch2 { 0% { clip-path: inset(10% 0 60% 0); } 20% { clip-path: inset(80% 0 5% 0); } 40% { clip-path: inset(30% 0 20% 0); } 60% { clip-path: inset(10% 0 80% 0); } 80% { clip-path: inset(50% 0 30% 0); } 100% { clip-path: inset(70% 0 10% 0); } }
@keyframes qShimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(400%); } }
</style>

<script>
(function() {
    'use strict';
    const loader = document.getElementById('quantumLoader');
    const loaderName = document.getElementById('qLoaderName');
    if (!loader || !loaderName) return;

    // ===== 1. Smart State Management =====
    const checkInternalNav = () => {
        return sessionStorage.getItem('glInternalNav') === 'true';
    };

    const setInternalNav = (menuName) => {
        sessionStorage.setItem('glInternalNav', 'true');
        if (menuName) {
            sessionStorage.setItem('glMenuName', menuName);
        }
    };

    const clearInternalNav = () => {
        sessionStorage.removeItem('glInternalNav');
        sessionStorage.removeItem('glMenuName');
    };

    // ===== 2. Extract Menu Name from Clicked Link =====
    function getMenuName(link) {
        // Try dropdown item first (most common)
        const dropdownItem = link.closest('.nb-dropdown-item');
        if (dropdownItem) {
            const titleEl = dropdownItem.querySelector('.nb-di-title');
            if (titleEl) return titleEl.textContent.trim();
        }
        
        // Try main nav link
        const navLink = link.closest('.nb-link');
        if (navLink) {
            const textEl = navLink.querySelector('.nb-link-text');
            if (textEl) return textEl.textContent.trim();
        }
        
        // Try footer link
        const footerLink = link.closest('.footer-col a, .gl-social a');
        if (footerLink) {
            const text = footerLink.textContent.trim();
            if (text && text.length < 30) return text;
        }
        
        // Fallback: extract from URL
        const href = link.getAttribute('href');
        if (href) {
            const filename = href.split('/').pop().split('?')[0].split('#')[0];
            if (filename && filename !== '' && filename !== 'index.php') {
                return filename.replace('.php', '').replace(/-/g, ' ').replace(/_/g, ' ');
            }
        }
        
        return 'HALAMAN';
    }

    // ===== 3. Intercept ALL Link Clicks (Navbar + Footer + Content) =====
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        
        // Skip: external, anchors, mailto, tel, javascript, download, new tab
        if (!href || 
            href.startsWith('#') || 
            href.startsWith('mailto:') || 
            href.startsWith('tel:') || 
            href.startsWith('javascript:') || 
            link.getAttribute('target') === '_blank' || 
            link.hasAttribute('download')) {
            return;
        }

        try {
            const url = new URL(href, window.location.origin);
            
            // Only track internal links (same domain)
            if (url.origin === window.location.origin) {
                const menuName = getMenuName(link);
                setInternalNav(menuName);
            }
        } catch (err) {
            // Invalid URL, skip
        }
    });

    // ===== 4. Intercept Form Submissions =====
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form && form.method && form.method.toLowerCase() === 'get') {
            // GET forms navigate away, set flag
            setInternalNav('HASIL PENCARIAN');
        }
        // POST forms usually stay on same page or redirect, no flag needed
    });

    // ===== 5. Activate Loader on Page Load (if internal nav) =====
    if (checkInternalNav()) {
        const menuName = sessionStorage.getItem('glMenuName') || 'HALAMAN';
        loaderName.textContent = menuName.toUpperCase();
        loader.classList.add('active');
        clearInternalNav(); // Reset after use
    }

// ===== 6. Hide Loader + Reveal Body =====
const revealBody = () => {
    document.documentElement.classList.remove('gl-preload');
};

window.addEventListener('load', () => {
    setTimeout(() => {
        loader.classList.remove('active');
        setTimeout(revealBody, 50); // Slight delay for smooth transition
    }, 400);
});

// ===== 7. Fallback: Browser Back Button (bfcache) =====
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        loader.classList.remove('active');
        clearInternalNav();
        revealBody();
    }
});

// ===== 8. Fallback: Hide after 3 seconds (safety net) =====
setTimeout(() => {
    loader.classList.remove('active');
    revealBody();
}, 3000);

// ===== 9. Extra Safety: Jika tidak ada flag tapi body hidden, force reveal =====
if (!checkInternalNav()) {
    // Jika bukan internal nav tapi body terlanjur hidden, langsung reveal
    setTimeout(() => {
        if (document.documentElement.classList.contains('gl-preload')) {
            revealBody();
        }
    }, 100);
}

})();
</script>

<?php endif; ?>