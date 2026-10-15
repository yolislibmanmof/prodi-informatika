<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Tentang Program Studi';
$pageDesc = 'Sejarah, visi, misi, dan nilai-nilai Program Studi Informatika.';

$stats = [
    'mahasiswa'  => (int)getSetting('statistik_mahasiswa', 518),
    'alumni'     => (int)getSetting('statistik_alumni', 1200),
    'mitra'      => (int)getSetting('statistik_mitra', 45),
    'akreditasi' => getSetting('akreditasi', 'Baik Sekali'),
    'tahun'      => (int)getSetting('tahun_berdiri', 2015),
    'dosen'      => 25,
];

$visi = getSetting('visi', 'Menjadi program studi Informatika unggul berkelas internasional yang mencetak inovator teknologi berkarakter, adaptif, dan berdaya saing global pada tahun 2030.');
$misiRaw = getSetting('misi', "Menyelenggarakan pendidikan berbasis proyek dan kebutuhan industri.\nMengembangkan riset terapan di bidang AI, data science, dan keamanan siber.\nMembangun kemitraan global dengan industri dan universitas terkemuka.\nMembentuk lulusan berintegritas, adaptif, dan berjiwa wirausaha.");
$misiList = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $misiRaw))));

try {
    $db = getDB();
    $stats['dosen'] = cache('stats_dosen_aktif', 3600, function() use ($db) {
        $c = (int)$db->query("SELECT COUNT(*) FROM dosen WHERE status = 'aktif'")->fetchColumn();
        return $c > 0 ? $c : 25;
    });
} catch (Throwable $e) { $stats['dosen'] = 25; }

$milestones = [
    [$stats['tahun'],      'Fondasi Program Studi', 'Prodi Informatika resmi berdiri dengan kurikulum berbasis komputasi fundamental dan pemrograman.'],
    [$stats['tahun'] + 4,  'Akreditasi & Pengakuan', 'Meraih akreditasi nasional pertama dan membangun laboratorium pemrograman terpadu.'],
    [$stats['tahun'] + 8,  'Era Laboratorium AI', 'Pembentukan lab AI, data science, dan cyber security beserta kelompok keahlian dosen.'],
    [$stats['tahun'] + 10, 'Kemitraan Global', 'Penandatanganan kerja sama industri multinasional dan program pertukaran pelajar.'],
    [2026,                 'Quantum Campus', 'Transformasi digital penuh: layanan akademik terintegrasi dan kampus berbasis data.'],
];

$values = [
    ['icon' => 'fa-lightbulb',     'color' => '#F59E0B', 'title' => 'Inovasi',    'desc' => 'Mendorong eksperimen dan pemikiran kreatif dalam setiap solusi teknologi.'],
    ['icon' => 'fa-shield-alt',    'color' => '#10B981', 'title' => 'Integritas', 'desc' => 'Menjunjung tinggi etika akademik dan kejujuran ilmiah.'],
    ['icon' => 'fa-hands-helping', 'color' => '#06B6D4', 'title' => 'Kolaborasi', 'desc' => 'Tumbuh bersama melalui kerja sama lintas disiplin dan industri.'],
    ['icon' => 'fa-bullseye',      'color' => '#8B5CF6', 'title' => 'Dampak',     'desc' => 'Setiap riset dan lulusan harus memberi manfaat nyata bagi masyarakat.'],
];
?>

<!-- ===== READING PROGRESS ===== -->
<div class="tg-reading-progress"><div class="tg-progress-fill" id="tgProgressFill"></div></div>

<!-- ===== HERO: GENESIS ARCHIVE ===== -->
<section class="tg-hero">
    <canvas id="tgWaveCanvas" class="tg-wave-canvas"></canvas>
    <div class="tg-hero-bg">
        <div class="tg-grid-pattern"></div>
        <div class="tg-orb tg-orb-1"></div>
        <div class="tg-orb tg-orb-2"></div>
    </div>

    <div class="container tg-hero-content">
        <span class="tg-hero-badge fade-up">
            <span class="tg-badge-pulse"></span>
            <i class="fas fa-landmark"></i> GENESIS ARCHIVE
        </span>
        <h1 class="tg-hero-title fade-up">
            <span class="tg-title-line">Tentang</span>
            <span class="tg-title-line tg-title-heritage">Informatika Pro</span>
        </h1>
        <p class="tg-hero-subtitle fade-up">
            <span id="tgTypingText"></span><span class="tg-cursor">|</span>
        </p>

        <div class="tg-hero-stats fade-up">
            <div class="tg-hstat">
                <div class="tg-hstat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="tg-hstat-info">
                    <span class="tg-hstat-num"><?= $stats['tahun'] ?></span>
                    <span class="tg-hstat-label">Tahun Berdiri</span>
                </div>
            </div>
            <div class="tg-hstat">
                <div class="tg-hstat-icon tg-hstat-gold"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="tg-hstat-info">
                    <span class="tg-hstat-num" data-count="<?= $stats['dosen'] ?>">0</span>
                    <span class="tg-hstat-label">Dosen Ahli</span>
                </div>
            </div>
            <div class="tg-hstat">
                <div class="tg-hstat-icon tg-hstat-cyan"><i class="fas fa-user-graduate"></i></div>
                <div class="tg-hstat-info">
                    <span class="tg-hstat-num" data-count="<?= $stats['mahasiswa'] ?>">0</span>
                    <span class="tg-hstat-label">Mahasiswa Aktif</span>
                </div>
            </div>
            <div class="tg-hstat">
                <div class="tg-hstat-icon tg-hstat-violet"><i class="fas fa-certificate"></i></div>
                <div class="tg-hstat-info">
                    <span class="tg-hstat-num tg-text-plain"><?= clean($stats['akreditasi']) ?></span>
                    <span class="tg-hstat-label">Akreditasi</span>
                </div>
            </div>
        </div>
    </div>

    <div class="tg-scroll-indicator">
        <span>Our Story</span>
        <div class="tg-scroll-arrow"><i class="fas fa-chevron-down"></i></div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<section class="tg-section">
    <div class="container">

        <!-- Visi & Misi -->
        <div class="tg-vm-grid">
            <div class="tg-vm-card tg-vm-visi fade-up">
                <div class="tg-vm-icon"><i class="fas fa-eye"></i></div>
                <span class="tg-section-label">OUR VISION</span>
                <h2>Visi</h2>
                <p><?= clean($visi) ?></p>
            </div>
            <div class="tg-vm-card tg-vm-misi fade-up">
                <div class="tg-vm-icon tg-vm-icon-gold"><i class="fas fa-bullseye"></i></div>
                <span class="tg-section-label gold">OUR MISSION</span>
                <h2>Misi</h2>
                <ul class="tg-misi-list">
                    <?php foreach ($misiList as $i => $m): ?>
                    <li>
                        <span class="tg-misi-num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                        <span><?= clean($m) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Timeline Sejarah -->
        <div class="tg-timeline-wrap fade-up">
            <div class="tg-center-header">
                <span class="tg-section-label">HERITAGE PATH</span>
                <h2>Perjalanan <span class="tg-accent">Kami</span></h2>
                <p>Dari fondasi kecil hingga menjadi ekosistem digital yang tumbuh pesat</p>
            </div>
            <div class="tg-timeline">
                <?php foreach ($milestones as $i => $ms): ?>
                <div class="tg-tl-item <?= $i % 2 === 0 ? 'left' : 'right' ?> fade-up">
                    <div class="tg-tl-dot"></div>
                    <div class="tg-tl-card">
                        <span class="tg-tl-year"><?= $ms[0] ?></span>
                        <h4><?= clean($ms[1]) ?></h4>
                        <p><?= clean($ms[2]) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Values -->
        <div class="tg-values fade-up">
            <div class="tg-center-header">
                <span class="tg-section-label">CORE VALUES</span>
                <h2>Nilai yang <span class="tg-accent">Kami Pegang</span></h2>
            </div>
            <div class="tg-values-grid">
                <?php foreach ($values as $v): ?>
                <div class="tg-value-card fade-up" style="--tg-color: <?= $v['color'] ?>;">
                    <div class="tg-value-icon"><i class="fas <?= $v['icon'] ?>"></i></div>
                    <h4><?= clean($v['title']) ?></h4>
                    <p><?= clean($v['desc']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CTA -->
        <div class="tg-cta fade-up">
            <div class="tg-cta-bg"><div class="tg-cta-grid"></div><div class="tg-cta-orb"></div></div>
            <div class="tg-cta-content">
                <div class="tg-cta-icon"><i class="fas fa-user-tie"></i></div>
                <h2>Kenali Lebih Dekat dari Pemimpin Prodi</h2>
                <p>Sambutan dan arahan Ketua Program Studi untuk seluruh civitas akademika dan calon mahasiswa.</p>
                <div class="tg-cta-actions">
                    <a href="sambutan-kaprodi.php" class="tg-btn tg-btn-primary"><i class="fas fa-envelope-open-text"></i> Baca Sambutan Kaprodi</a>
                    <a href="daftar.php" class="tg-btn tg-btn-ghost"><i class="fas fa-rocket"></i> Bergabung Bersama Kami</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Toast -->
<div id="tgToast" class="tg-toast"><i class="fas fa-check-circle"></i><span id="tgToastMsg">Berhasil!</span></div>

<!-- Back to Top -->
<button class="tg-back-top" id="tgBackTop" title="Kembali ke atas"><i class="fas fa-arrow-up"></i></button>

<style>
:root {
    --tg-primary: #0D9488;
    --tg-primary-dark: #0F766E;
    --tg-deep: #042F2E;
    --tg-gold: #F59E0B;
    --tg-cyan: #06B6D4;
    --tg-violet: #8B5CF6;
    --tg-dark: #0F172A;
    --tg-gray-50: #F8FAFC;
    --tg-gray-100: #F1F5F9;
    --tg-gray-200: #E2E8F0;
    --tg-gray-600: #475569;
    --tg-gray-700: #374151;
    --tg-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --tg-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --tg-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
    --tg-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --tg-font-display: 'Space Grotesk', 'Inter', sans-serif;
}
body.gl-dark {
    --tg-gray-50: #0B1120;
    --tg-gray-100: rgba(255,255,255,.08);
    --tg-gray-200: rgba(255,255,255,.1);
    --tg-gray-600: #94A3B8;
    --tg-gray-700: #CBD5E1;
}
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--tg-gray-50); }
::-webkit-scrollbar-thumb { background: linear-gradient(180deg, var(--tg-primary), var(--tg-gold)); border-radius: 5px; }

/* Reading progress */
.tg-reading-progress { position: fixed; top: 0; left: 0; right: 0; height: 4px; background: rgba(13,148,136,0.1); z-index: 9998; }
.tg-progress-fill { height: 100%; width: 0; background: linear-gradient(90deg, var(--tg-primary), var(--tg-gold), var(--tg-cyan)); background-size: 200% 100%; animation: tgGradientMove 3s ease infinite; box-shadow: 0 0 10px rgba(13,148,136,0.6); transition: width 0.1s ease-out; }
@keyframes tgGradientMove { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

/* Hero */
.tg-hero { background: linear-gradient(135deg, #022C22 0%, #065F46 50%, #0D9488 100%); padding: 160px 20px 120px; position: relative; overflow: hidden; min-height: 92vh; display: flex; align-items: center; justify-content: center; }
.tg-wave-canvas { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 1; opacity: 0.6; }
.tg-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }
.tg-grid-pattern { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 50px 50px; mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%); }
.tg-orb { position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.3; animation: tgOrbFloat 18s infinite ease-in-out; }
.tg-orb-1 { width: 500px; height: 500px; background: var(--tg-gold); top: -150px; right: -100px; }
.tg-orb-2 { width: 380px; height: 380px; background: var(--tg-cyan); bottom: -120px; left: -80px; animation-delay: -8s; }
@keyframes tgOrbFloat { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(35px,-35px) scale(1.08); } }

.tg-hero-content { position: relative; z-index: 4; text-align: center; color: #fff; max-width: 1000px; margin: 0 auto; }
.tg-hero-badge { display: inline-flex; align-items: center; gap: 12px; padding: 10px 24px; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); border-radius: 50px; font-size: .85rem; margin-bottom: 24px; backdrop-filter: blur(10px); font-weight: 700; letter-spacing: 2px; position: relative; font-family: var(--tg-font-mono); }
.tg-badge-pulse { position: absolute; inset: 0; border-radius: 50px; border: 2px solid var(--tg-gold); animation: tgBadgePulse 2s infinite; }
@keyframes tgBadgePulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.25); opacity: 0; } }
.tg-hero-badge i { color: var(--tg-gold); }
.tg-hero-title { font-size: clamp(3rem, 9vw, 6.5rem); font-weight: 900; margin-bottom: 24px; letter-spacing: -0.03em; line-height: 0.95; font-family: var(--tg-font-display); }
.tg-title-line { display: block; }
.tg-title-heritage { background: linear-gradient(135deg, #FDE68A 0%, #F59E0B 40%, #5EEAD4 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; background-size: 200% 100%; animation: tgGradientShift 5s ease infinite; }
@keyframes tgGradientShift { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
.tg-hero-subtitle { font-size: 1.2rem; opacity: .9; max-width: 700px; margin: 0 auto 40px; line-height: 1.7; display: inline-block; min-height: 1.7em; }
.tg-cursor { animation: tgBlink 1s infinite; color: var(--tg-gold); font-weight: 300; }
@keyframes tgBlink { 0%,50% { opacity: 1; } 51%,100% { opacity: 0; } }

.tg-hero-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; max-width: 900px; margin: 0 auto; }
.tg-hstat { display: flex; align-items: center; gap: 14px; padding: 16px 20px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); border-radius: 16px; backdrop-filter: blur(12px); transition: all 0.3s; }
.tg-hstat:hover { transform: translateY(-4px); border-color: var(--tg-gold); background: rgba(255,255,255,.1); }
.tg-hstat-icon { width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, var(--tg-primary), var(--tg-primary-dark)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; box-shadow: 0 4px 12px rgba(13,148,136,0.4); }
.tg-hstat-gold { background: linear-gradient(135deg, #F59E0B, #B45309); box-shadow: 0 4px 12px rgba(245,158,11,0.4); }
.tg-hstat-cyan { background: linear-gradient(135deg, #06B6D4, #0891B2); box-shadow: 0 4px 12px rgba(6,182,212,0.4); }
.tg-hstat-violet { background: linear-gradient(135deg, #8B5CF6, #6D28D9); box-shadow: 0 4px 12px rgba(139,92,246,0.4); }
.tg-hstat-num { display: block; font-size: 1.6rem; font-weight: 900; color: #fff; line-height: 1; font-family: var(--tg-font-display); }
.tg-text-plain { font-size: 1.3rem; }
.tg-hstat-label { font-size: 0.7rem; color: rgba(255,255,255,.6); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; display: block; }

.tg-scroll-indicator { position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); z-index: 4; display: flex; flex-direction: column; align-items: center; gap: 8px; color: rgba(255,255,255,.6); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; }
.tg-scroll-arrow { animation: tgArrowBounce 2s infinite; }
@keyframes tgArrowBounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(10px); } }

/* Main section */
.tg-section { padding: 80px 20px 100px; background: var(--tg-gray-50); }
body.gl-dark .tg-section { background: #0B1120; }
.tg-section-label { display: inline-block; padding: 6px 16px; background: linear-gradient(135deg, var(--tg-primary), var(--tg-cyan)); color: #fff; border-radius: 20px; font-family: var(--tg-font-mono); font-size: 0.7rem; font-weight: 800; letter-spacing: 2px; margin-bottom: 16px; }
.tg-section-label.gold { background: linear-gradient(135deg, var(--tg-gold), #D97706); }
.tg-accent { background: linear-gradient(135deg, var(--tg-primary), var(--tg-gold)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.tg-center-header { text-align: center; margin-bottom: 44px; }
.tg-center-header h2 { font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 900; margin: 0 0 12px 0; color: var(--tg-dark); font-family: var(--tg-font-display); }
body.gl-dark .tg-center-header h2 { color: #F1F5F9; }
.tg-center-header p { font-size: 1rem; color: var(--tg-gray-600); max-width: 600px; margin: 0 auto; }
body.gl-dark .tg-center-header p { color: #94A3B8; }

/* Visi Misi */
.tg-vm-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 28px; margin-bottom: 90px; }
.tg-vm-card { background: #fff; border-radius: 24px; padding: 36px; border: 1px solid var(--tg-gray-100); box-shadow: var(--tg-shadow-sm); position: relative; overflow: hidden; transition: all 0.4s; }
body.gl-dark .tg-vm-card { background: #1E293B; border-color: rgba(255,255,255,.08); }
.tg-vm-card:hover { transform: translateY(-6px); box-shadow: var(--tg-shadow-lg); }
.tg-vm-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, var(--tg-primary), var(--tg-cyan)); }
.tg-vm-misi::before { background: linear-gradient(90deg, var(--tg-gold), #D97706); }
.tg-vm-icon { width: 60px; height: 60px; border-radius: 16px; background: linear-gradient(135deg, var(--tg-primary), var(--tg-primary-dark)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 18px; box-shadow: 0 8px 20px rgba(13,148,136,0.35); }
.tg-vm-icon-gold { background: linear-gradient(135deg, var(--tg-gold), #B45309); box-shadow: 0 8px 20px rgba(245,158,11,0.35); }
.tg-vm-card h2 { font-size: 1.7rem; font-weight: 900; margin: 0 0 14px 0; color: var(--tg-dark); font-family: var(--tg-font-display); }
body.gl-dark .tg-vm-card h2 { color: #F1F5F9; }
.tg-vm-card p { font-size: 1rem; line-height: 1.8; color: var(--tg-gray-700); margin: 0; }
body.gl-dark .tg-vm-card p { color: #CBD5E1; }
.tg-misi-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 14px; }
.tg-misi-list li { display: flex; gap: 14px; align-items: flex-start; font-size: 0.95rem; line-height: 1.6; color: var(--tg-gray-700); }
body.gl-dark .tg-misi-list li { color: #CBD5E1; }
.tg-misi-num { font-family: var(--tg-font-mono); font-weight: 800; color: var(--tg-gold); font-size: 0.85rem; padding-top: 2px; flex-shrink: 0; }

/* Timeline */
.tg-timeline-wrap { margin-bottom: 90px; }
.tg-timeline { position: relative; max-width: 800px; margin: 0 auto; padding: 20px 0; }
.tg-timeline::before { content: ''; position: absolute; left: 50%; top: 0; bottom: 0; width: 3px; transform: translateX(-50%); background: linear-gradient(180deg, var(--tg-primary), var(--tg-gold), var(--tg-cyan)); border-radius: 3px; opacity: 0.4; }
.tg-tl-item { position: relative; width: 50%; padding: 0 40px 50px 0; }
.tg-tl-item.right { margin-left: 50%; padding: 0 0 50px 40px; }
.tg-tl-dot { position: absolute; top: 8px; right: -9px; width: 18px; height: 18px; border-radius: 50%; background: var(--tg-primary); border: 4px solid var(--tg-gray-50); box-shadow: 0 0 0 4px rgba(13,148,136,0.2); z-index: 2; }
body.gl-dark .tg-tl-dot { border-color: #0B1120; }
.tg-tl-item.right .tg-tl-dot { right: auto; left: -9px; }
.tg-tl-card { background: #fff; border-radius: 18px; padding: 22px; border: 1px solid var(--tg-gray-100); box-shadow: var(--tg-shadow-sm); transition: all 0.3s; }
body.gl-dark .tg-tl-card { background: #1E293B; border-color: rgba(255,255,255,.08); }
.tg-tl-card:hover { transform: translateY(-4px); box-shadow: var(--tg-shadow-md); border-color: var(--tg-primary); }
.tg-tl-year { display: inline-block; padding: 3px 12px; background: rgba(13,148,136,0.12); color: var(--tg-primary-dark); border-radius: 20px; font-family: var(--tg-font-mono); font-size: 0.78rem; font-weight: 800; margin-bottom: 10px; }
body.gl-dark .tg-tl-year { background: rgba(13,148,136,0.2); color: #5EEAD4; }
.tg-tl-card h4 { font-size: 1.05rem; font-weight: 800; margin: 0 0 8px 0; color: var(--tg-dark); font-family: var(--tg-font-display); }
body.gl-dark .tg-tl-card h4 { color: #F1F5F9; }
.tg-tl-card p { font-size: 0.86rem; line-height: 1.6; color: var(--tg-gray-600); margin: 0; }
body.gl-dark .tg-tl-card p { color: #94A3B8; }

/* Values */
.tg-values { margin-bottom: 90px; }
.tg-values-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 22px; }
.tg-value-card { background: #fff; border-radius: 20px; padding: 28px 24px; text-align: center; border: 1px solid var(--tg-gray-100); box-shadow: var(--tg-shadow-sm); transition: all 0.4s; position: relative; overflow: hidden; }
body.gl-dark .tg-value-card { background: #1E293B; border-color: rgba(255,255,255,.08); }
.tg-value-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--tg-color, var(--tg-primary)); box-shadow: 0 0 16px var(--tg-color, var(--tg-primary)); }
.tg-value-card:hover { transform: translateY(-8px); box-shadow: var(--tg-shadow-lg); border-color: var(--tg-color); }
.tg-value-icon { width: 62px; height: 62px; margin: 0 auto 16px; border-radius: 18px; background: color-mix(in srgb, var(--tg-color, var(--tg-primary)) 14%, transparent); color: var(--tg-color, var(--tg-primary)); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: transform 0.4s; }
.tg-value-card:hover .tg-value-icon { transform: scale(1.1) rotate(-6deg); }
.tg-value-card h4 { font-size: 1.1rem; font-weight: 800; margin: 0 0 8px 0; color: var(--tg-dark); font-family: var(--tg-font-display); }
body.gl-dark .tg-value-card h4 { color: #F1F5F9; }
.tg-value-card p { font-size: 0.85rem; line-height: 1.6; color: var(--tg-gray-600); margin: 0; }
body.gl-dark .tg-value-card p { color: #94A3B8; }

/* CTA */
.tg-cta { position: relative; border-radius: 28px; overflow: hidden; padding: 60px 40px; text-align: center; color: #fff; background: linear-gradient(135deg, #022C22 0%, #065F46 50%, #0D9488 100%); box-shadow: var(--tg-shadow-lg); }
.tg-cta-bg { position: absolute; inset: 0; pointer-events: none; }
.tg-cta-grid { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 40px 40px; }
.tg-cta-orb { position: absolute; top: -100px; right: -100px; width: 320px; height: 320px; border-radius: 50%; background: rgba(245,158,11,0.3); filter: blur(60px); }
.tg-cta-content { position: relative; z-index: 1; max-width: 640px; margin: 0 auto; }
.tg-cta-icon { width: 80px; height: 80px; margin: 0 auto 20px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; animation: tgCtaBounce 3s infinite ease-in-out; }
@keyframes tgCtaBounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
.tg-cta h2 { font-size: clamp(1.6rem, 4vw, 2.4rem); margin: 0 0 14px 0; font-weight: 900; font-family: var(--tg-font-display); }
.tg-cta p { font-size: 1.05rem; opacity: 0.9; margin: 0 0 30px 0; line-height: 1.6; }
.tg-cta-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
.tg-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.3s; font-family: inherit; }
.tg-btn-primary { background: #fff; color: var(--tg-primary-dark); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
.tg-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.3); }
.tg-btn-ghost { background: transparent; color: #fff; border: 2px solid rgba(255,255,255,0.3); }
.tg-btn-ghost:hover { background: rgba(255,255,255,0.1); border-color: #fff; transform: translateY(-3px); }

/* Toast & back top */
.tg-toast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px); background: var(--tg-dark); color: #fff; padding: 14px 24px; border-radius: 50px; display: flex; align-items: center; gap: 10px; box-shadow: 0 10px 30px rgba(0,0,0,.3); z-index: 10001; opacity: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); font-size: 0.9rem; }
body.gl-dark .tg-toast { background: #fff; color: var(--tg-dark); }
.tg-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.tg-toast i { color: var(--tg-primary); font-size: 1.2rem; }
.tg-back-top { position: fixed; bottom: 30px; right: 30px; width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, var(--tg-primary), var(--tg-gold)); border: none; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; z-index: 998; box-shadow: 0 8px 24px rgba(13,148,136,0.4); opacity: 0; visibility: hidden; transform: translateY(20px) scale(.8); transition: all .4s; }
.tg-back-top.show { opacity: 1; visibility: visible; transform: none; }
.tg-back-top:hover { transform: translateY(-4px) scale(1.05); }

/* Animations & responsive */
.fade-up { opacity: 0; transform: translateY(30px); transition: opacity 0.8s ease, transform 0.8s ease; }
.fade-up.visible { opacity: 1; transform: translateY(0); }
@media (max-width: 1024px) { .tg-vm-grid { grid-template-columns: 1fr; } .tg-hero-stats { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .tg-hero { padding: 120px 20px 80px; min-height: 85vh; }
    .tg-hero-title { font-size: 3rem; }
    .tg-hero-stats { grid-template-columns: 1fr 1fr; gap: 10px; }
    .tg-hstat { justify-content: center; }
    .tg-timeline::before { left: 12px; }
    .tg-tl-item, .tg-tl-item.right { width: 100%; margin-left: 0; padding: 0 0 40px 40px; }
    .tg-tl-dot, .tg-tl-item.right .tg-tl-dot { left: 3px; right: auto; }
    .tg-cta { padding: 40px 20px; }
    .tg-cta-actions { flex-direction: column; }
    .tg-cta-actions .tg-btn { width: 100%; justify-content: center; }
    .tg-back-top { width: 44px; height: 44px; bottom: 20px; right: 20px; }
}
@media (max-width: 480px) { .tg-hero-title { font-size: 2.3rem; } .tg-hero-badge { font-size: 0.7rem; padding: 8px 16px; letter-spacing: 1px; } .tg-hstat-num { font-size: 1.3rem; } }

/* ============================================================
   PERFORMANCE PATCH - BUILT-IN (SMOOTH SCROLL SINCE DAY ONE)
============================================================ */
main section, .tg-section { scroll-margin-top: 90px; }
.tg-section { content-visibility: auto; contain-intrinsic-size: auto 2200px; }
.tg-hero-bg, .tg-reading-progress, .tg-grid-pattern { pointer-events: none; }
.tg-wave-canvas, .tg-orb, .tg-progress-fill { will-change: transform; backface-visibility: hidden; }
.fade-up { backface-visibility: hidden; }
.fade-up.visible { will-change: auto; }
body.is-scrolling .tg-orb,
body.is-scrolling .tg-progress-fill,
body.is-scrolling .tg-badge-pulse { animation-play-state: paused !important; }
.tg-offscreen * { animation-play-state: paused !important; }
@media (max-width: 768px), (hover: none), (pointer: coarse) {
    .tg-orb { filter: blur(60px); opacity: 0.2 !important; animation: none !important; }
    .tg-hstat, .tg-hero-badge, .tg-cta-icon { backdrop-filter: none !important; }
}
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ===== 0. PERFORMANCE: Scroll & Viewport Control =====
    let scrollTimer;
    const markScrolling = () => {
        document.body.classList.add('is-scrolling');
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(() => document.body.classList.remove('is-scrolling'), 180);
    };
    window.addEventListener('scroll', markScrolling, { passive: true });
    markScrolling();

    if ('IntersectionObserver' in window) {
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => entry.target.classList.toggle('tg-offscreen', !entry.isIntersecting));
        }, { rootMargin: '300px 0px', threshold: 0 });
        document.querySelectorAll('.tg-hero, .tg-section').forEach(s => sectionObserver.observe(s));

        const heroSection = document.querySelector('.tg-hero');
        if (heroSection) {
            window.__tgHeroVisible = true;
            new IntersectionObserver((entries) => {
                const entry = entries[0];
                window.__tgHeroVisible = entry ? entry.isIntersecting : true;
                window.dispatchEvent(new CustomEvent('tg:hero-visibility', { detail: window.__tgHeroVisible }));
            }, { threshold: 0.01 }).observe(heroSection);
        }
    }

    // ===== 1. Aurora Waves Canvas (30 FPS, stop offscreen) =====
    const canvas = document.getElementById('tgWaveCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d', { alpha: true });
        let width, height, time = 0, running = true, lastFrame = 0;
        const frameInterval = 1000 / 30;
        const waveColors = ['rgba(94,234,212,0.35)', 'rgba(245,158,11,0.28)', 'rgba(6,182,212,0.25)'];

        function resize() {
            width = canvas.width = canvas.parentElement.offsetWidth;
            height = canvas.height = canvas.parentElement.offsetHeight;
        }
        function drawWaves() {
            ctx.clearRect(0, 0, width, height);
            for (let w = 0; w < 3; w++) {
                ctx.beginPath();
                for (let x = 0; x <= width; x += 10) {
                    const y = height / 2 + Math.sin(x * 0.006 + time * 0.02 + w * 2.1) * (40 + w * 26) + (w - 1) * 60;
                    x === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
                }
                ctx.strokeStyle = waveColors[w];
                ctx.lineWidth = 1.5;
                ctx.stroke();
            }
        }
        function animate(currentTime) {
            requestAnimationFrame(animate);
            if (!running || document.hidden || window.__tgHeroVisible === false) return;
            const elapsed = currentTime - lastFrame;
            if (elapsed < frameInterval) return;
            lastFrame = currentTime - (elapsed % frameInterval);
            drawWaves();
            time++;
        }
        window.addEventListener('tg:hero-visibility', (e) => { running = Boolean(e.detail); });
        document.addEventListener('visibilitychange', () => { running = !document.hidden && window.__tgHeroVisible !== false; });
        resize();
        window.addEventListener('resize', resize, { passive: true });
        requestAnimationFrame(animate);
    }

    // ===== 2. Typing Effect =====
    const textElement = document.getElementById('tgTypingText');
    if (textElement) {
        const phrases = [
            "Satu dekade lebih membentuk inovator teknologi berkarakter.",
            "Kurikulum hidup yang tumbuh bersama kebutuhan industri.",
            "Riset terapan, kemitraan global, dan dampak nyata bagi masyarakat.",
            "Rumah bagi para pemimpi yang membangun masa depan digital."
        ];
        let phraseIndex = 0, charIndex = 0, isDeleting = false;
        function type() {
            const currentPhrase = phrases[phraseIndex];
            if (isDeleting) { textElement.textContent = currentPhrase.substring(0, charIndex - 1); charIndex--; }
            else { textElement.textContent = currentPhrase.substring(0, charIndex + 1); charIndex++; }
            let typeSpeed = isDeleting ? 30 : 50;
            if (!isDeleting && charIndex === currentPhrase.length) { typeSpeed = 2500; isDeleting = true; }
            else if (isDeleting && charIndex === 0) { isDeleting = false; phraseIndex = (phraseIndex + 1) % phrases.length; typeSpeed = 500; }
            setTimeout(type, typeSpeed);
        }
        type();
    }

    // ===== 3. Counter Animation =====
    const counters = document.querySelectorAll('.tg-hstat-num[data-count]');
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
                    el.textContent = Math.floor(target * easeOutQuart(progress)).toLocaleString('id-ID');
                    if (progress < 1) requestAnimationFrame(update);
                    else el.textContent = target.toLocaleString('id-ID');
                }
                requestAnimationFrame(update);
                counterObs.unobserve(el);
            }
        });
    }, { threshold: 0.5 });
    counters.forEach(c => counterObs.observe(c));

    // ===== 4. Reading Progress (THROTTLED) =====
    const progressFill = document.getElementById('tgProgressFill');
    let progressTicking = false;
    if (progressFill) {
        window.addEventListener('scroll', () => {
            if (!progressTicking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.scrollY;
                    const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
                    progressFill.style.width = (maxScroll > 0 ? (scrolled / maxScroll) * 100 : 0) + '%';
                    progressTicking = false;
                });
                progressTicking = true;
            }
        }, { passive: true });
    }

    // ===== 5. Scroll Reveal =====
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target) { entry.target.classList.add('visible'); observer.unobserve(entry.target); }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('.fade-up').forEach(el => { if (el) observer.observe(el); });

    // ===== 6. Toast =====
    window.tgShowToast = function(message) {
        const toast = document.getElementById('tgToast');
        if (!toast) return;
        const msgEl = document.getElementById('tgToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // ===== 7. Back to Top (THROTTLED) =====
    const backTop = document.getElementById('tgBackTop');
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
        backTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }

    setTimeout(() => tgShowToast('🏛️ Selamat datang di cerita kami!'), 1500);

    console.log('%c╔══════════════════════════════════════════╗', 'color:#0D9488;font-weight:bold');
    console.log('%c║   GENESIS ARCHIVE - About The Program    ║', 'color:#0D9488;font-weight:bold;font-size:14px');
    console.log('%c╚══════════════════════════════════════════╝', 'color:#0D9488;font-weight:bold');
    console.log('%c🏛️ Heritage since ' + <?= $stats['tahun'] ?> + ' (OPTIMIZED)', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>