<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Kelompok Keahlian & Konsentrasi';
$pageDesc = 'Jelajahi bidang keahlian dan jalur spesialisasi Program Studi Informatika.';

$konsentrasiList = [];
$mkMap = [];

try {
    $db = getDB();
    $konsentrasiList = cache('public_konsentrasi_all', 600, function() use ($db) {
        try {
            return $db->query("SELECT k.*, (SELECT COUNT(*) FROM mata_kuliah mk WHERE mk.konsentrasi_id = k.id) AS total_mk FROM konsentrasi k WHERE k.status = 'aktif' ORDER BY k.urutan ASC, k.nama ASC")->fetchAll();
        } catch (Throwable $e) {
            return $db->query("SELECT * FROM konsentrasi WHERE status = 'aktif' ORDER BY urutan ASC, nama ASC")->fetchAll();
        }
    });
    try {
        $rows = $db->query("SELECT konsentrasi_id, nama FROM mata_kuliah WHERE konsentrasi_id IS NOT NULL ORDER BY semester ASC, nama ASC")->fetchAll();
        foreach ($rows as $r) { $mkMap[(int)$r['konsentrasi_id']][] = $r['nama']; }
    } catch (Throwable $e) { $mkMap = []; }
} catch (Throwable $e) {
    $konsentrasiList = [];
}

$totalKonsentrasi = count($konsentrasiList);
$totalMK = 0;
foreach ($konsentrasiList as $k) { $totalMK += (int)($k['total_mk'] ?? 0); }
$avgMK = $totalKonsentrasi > 0 ? round($totalMK / $totalKonsentrasi) : 0;
?>

<!-- ===== READING PROGRESS ===== -->
<div class="kx-reading-progress">
    <div class="kx-progress-fill" id="kxProgressFill"></div>
</div>

<!-- ===== HERO: EXPERTISE MATRIX ===== -->
<section class="kx-hero">
    <canvas id="kxMatrixCanvas" class="kx-matrix-canvas"></canvas>
    <div class="kx-hero-bg">
        <div class="kx-grid-pattern"></div>
        <div class="kx-scanline"></div>
        <div class="kx-orb kx-orb-1"></div>
        <div class="kx-orb kx-orb-2"></div>
    </div>

    <div class="kx-floating-elements">
        <span class="kx-float-el" style="--delay: 0s;   --x: 8%;  --y: 22%;">🎓</span>
        <span class="kx-float-el" style="--delay: 1.4s; --x: 88%; --y: 26%;">🧠</span>
        <span class="kx-float-el" style="--delay: 2.6s; --x: 12%; --y: 72%;">💻</span>
        <span class="kx-float-el" style="--delay: 0.9s; --x: 85%; --y: 68%;">🔬</span>
        <span class="kx-float-el" style="--delay: 3.2s; --x: 50%; --y: 14%;">📡</span>
        <span class="kx-float-el" style="--delay: 2s;   --x: 45%; --y: 85%;">🛠️</span>
    </div>

    <div class="container kx-hero-content">
        <span class="kx-hero-badge fade-up">
            <span class="kx-badge-pulse"></span>
            <i class="fas fa-layer-group"></i> ACADEMIC SPECIALIZATION
        </span>

        <h1 class="kx-hero-title fade-up">
            <span class="kx-title-line">Kelompok</span>
            <span class="kx-title-line kx-title-matrix" data-text="Keahlian">Keahlian</span>
        </h1>

        <p class="kx-hero-subtitle fade-up">
            <span id="kxTypingText"></span><span class="kx-cursor">|</span>
        </p>

        <div class="kx-hero-stats fade-up">
            <div class="kx-hstat">
                <div class="kx-hstat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="kx-hstat-info">
                    <span class="kx-hstat-num" data-count="<?= $totalKonsentrasi ?>">0</span>
                    <span class="kx-hstat-label">Konsentrasi</span>
                </div>
            </div>
            <div class="kx-hstat">
                <div class="kx-hstat-icon kx-hstat-violet"><i class="fas fa-book"></i></div>
                <div class="kx-hstat-info">
                    <span class="kx-hstat-num" data-count="<?= $totalMK ?>">0</span>
                    <span class="kx-hstat-label">Mata Kuliah</span>
                </div>
            </div>
            <div class="kx-hstat">
                <div class="kx-hstat-icon kx-hstat-pink"><i class="fas fa-chart-line"></i></div>
                <div class="kx-hstat-info">
                    <span class="kx-hstat-num" data-count="<?= $avgMK ?>">0</span>
                    <span class="kx-hstat-label">Rata-rata MK/Jalur</span>
                </div>
            </div>
            <div class="kx-hstat">
                <div class="kx-hstat-icon kx-hstat-amber"><i class="fas fa-calendar-alt"></i></div>
                <div class="kx-hstat-info">
                    <span class="kx-hstat-num" data-count="8">0</span>
                    <span class="kx-hstat-label">Semester</span>
                </div>
            </div>
        </div>
    </div>

    <div class="kx-scroll-indicator">
        <span>Explore Specializations</span>
        <div class="kx-scroll-arrow"><i class="fas fa-chevron-down"></i></div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<section class="kx-section">
    <div class="container">

        <!-- Expertise Console -->
        <div class="kx-console fade-up">
            <div class="kx-console-header">
                <div class="kx-console-dots">
                    <span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span>
                </div>
                <i class="fas fa-diagram-project"></i>
                <span>expertise_matrix.exe</span>
                <span class="kx-console-status">● MAPPING</span>
            </div>

            <div class="kx-console-body">
                <div class="kx-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="kxSearchInput" placeholder="Cari konsentrasi, kode, atau kata kunci..." autocomplete="off">
                    <span class="kx-search-hint"><kbd>Ctrl</kbd>+<kbd>K</kbd></span>
                </div>
                <div class="kx-results-info">
                    <span class="kx-results-count">
                        <i class="fas fa-layer-group"></i>
                        <strong id="kxResultsCount"><?= $totalKonsentrasi ?></strong> jalur spesialisasi ditemukan
                    </span>
                    <span class="kx-results-meta">
                        <i class="fas fa-graduation-cap"></i> Kurikulum Berbasis Kompetensi
                    </span>
                </div>
            </div>
        </div>

        <!-- Konsentrasi Grid -->
        <?php if (!empty($konsentrasiList)): ?>
        <div class="kx-grid" id="kxGrid">
            <?php foreach ($konsentrasiList as $index => $k):
                $warna = clean($k['warna'] ?? '') ?: '#6366F1';
                $ikon = clean($k['ikon'] ?? '') ?: 'fa-graduation-cap';
                $mkList = $mkMap[(int)($k['id'] ?? 0)] ?? [];
                $modalData = htmlspecialchars(json_encode([
                    'kode'      => clean($k['kode'] ?? '-'),
                    'nama'      => clean($k['nama'] ?? 'Tanpa Nama'),
                    'deskripsi' => clean($k['deskripsi'] ?? '') ?: 'Belum ada deskripsi untuk konsentrasi ini.',
                    'ikon'      => $ikon,
                    'warna'     => $warna,
                    'total_mk'  => (int)($k['total_mk'] ?? count($mkList)),
                    'urutan'    => (int)($k['urutan'] ?? 0),
                    'mk'        => array_slice($mkList, 0, 12),
                    'mk_more'   => max(0, count($mkList) - 12),
                ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                $searchAttr = strtolower(clean(($k['nama'] ?? '') . ' ' . ($k['kode'] ?? '') . ' ' . ($k['deskripsi'] ?? '')));
            ?>
            <article class="kx-card fade-up" style="--kx-color: <?= $warna ?>; animation-delay: <?= $index * 0.07 ?>s;" data-search="<?= htmlspecialchars($searchAttr) ?>">
                <div class="kx-card-accent"></div>
                <?php if ((int)($k['urutan'] ?? 0) === 1): ?><span class="kx-featured-tag">⚡ UNGGULAN</span><?php endif; ?>

                <div class="kx-card-icon">
                    <i class="fas <?= $ikon ?>"></i>
                    <span class="kx-icon-ring"></span>
                </div>
                <span class="kx-kode-badge"><?= clean($k['kode'] ?? '-') ?></span>
                <h3 class="kx-card-title"><?= clean($k['nama'] ?? 'Tanpa Nama') ?></h3>
                <p class="kx-card-desc"><?= clean(mb_substr($k['deskripsi'] ?? '', 0, 110)) ?><?= strlen($k['deskripsi'] ?? '') > 110 ? '...' : '' ?></p>

                <div class="kx-card-stats">
                    <span class="kx-info-chip"><i class="fas fa-book"></i> <?= (int)($k['total_mk'] ?? 0) ?> Mata Kuliah</span>
                    <span class="kx-info-chip"><i class="fas fa-route"></i> Jalur #<?= (int)($k['urutan'] ?? 0) ?></span>
                </div>

                <button class="kx-detail-btn" onclick='kxOpenModal(<?= $modalData ?>)'>
                    <i class="fas fa-eye"></i> Lihat Detail
                </button>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="kx-empty-search" id="kxEmptySearch" style="display: none;">
            <div class="kx-empty-icon"><i class="fas fa-search-minus"></i><div class="kx-empty-pulse"></div></div>
            <h3>Konsentrasi Tidak Ditemukan</h3>
            <p>Tidak ada jalur spesialisasi yang cocok dengan kata kunci pencarian Anda.</p>
            <button class="kx-btn-reset" onclick="kxResetSearch()"><i class="fas fa-redo"></i> Reset Pencarian</button>
        </div>
        <?php else: ?>
        <div class="kx-empty-state fade-up">
            <div class="kx-empty-icon"><i class="fas fa-layer-group"></i><div class="kx-empty-pulse"></div></div>
            <h3>Belum Ada Konsentrasi Aktif</h3>
            <p>Data kelompok keahlian akan segera ditampilkan oleh administrator.</p>
        </div>
        <?php endif; ?>

        <!-- Learning Path -->
        <div class="kx-path fade-up">
            <div class="kx-path-header">
                <span class="kx-section-label">LEARNING_PATH</span>
                <h2>Perjalanan <span class="kx-accent">Belajar</span> Anda</h2>
                <p>Empat tahap kurikulum yang membawa Anda dari fondasi hingga spesialisasi penuh</p>
            </div>
            <div class="kx-path-grid">
                <div class="kx-path-step">
                    <span class="kx-step-num">01</span>
                    <div class="kx-step-icon"><i class="fas fa-seedling"></i></div>
                    <h4>Semester 1-2 · Fondasi</h4>
                    <p>Matematika diskrit, algoritma pemrograman, dan dasar komputasi.</p>
                </div>
                <div class="kx-path-step">
                    <span class="kx-step-num">02</span>
                    <div class="kx-step-icon"><i class="fas fa-microchip"></i></div>
                    <h4>Semester 3-4 · Inti</h4>
                    <p>Struktur data, basis data, jaringan, dan rekayasa perangkat lunak.</p>
                </div>
                <div class="kx-path-step">
                    <span class="kx-step-num">03</span>
                    <div class="kx-step-icon"><i class="fas fa-layer-group"></i></div>
                    <h4>Semester 5-6 · Spesialisasi</h4>
                    <p>Memilih konsentrasi dan mendalami mata kuliah keahlian pilihan.</p>
                </div>
                <div class="kx-path-step">
                    <span class="kx-step-num">04</span>
                    <div class="kx-step-icon"><i class="fas fa-rocket"></i></div>
                    <h4>Semester 7-8 · Riset & Industri</h4>
                    <p>Magang industri, proyek nyata, dan tugas akhir berbasis riset.</p>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="kx-cta fade-up">
            <div class="kx-cta-bg"><div class="kx-cta-grid"></div><div class="kx-cta-orb"></div></div>
            <div class="kx-cta-content">
                <div class="kx-cta-icon"><i class="fas fa-graduation-cap"></i></div>
                <h2>Sudah Menemukan Jalur Spesialisasi Anda?</h2>
                <p>Lihat struktur kurikulum lengkap setiap konsentrasi atau mulai pendaftaran sekarang juga.</p>
                <div class="kx-cta-actions">
                    <a href="kurikulum.php" class="kx-btn kx-btn-primary"><i class="fas fa-book"></i> Lihat Kurikulum</a>
                    <a href="daftar.php" class="kx-btn kx-btn-ghost"><i class="fas fa-rocket"></i> Daftar Sekarang</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== MODAL DETAIL ===== -->
<div class="kx-modal" id="kxModal">
    <div class="kx-modal-content">
        <button class="kx-modal-close" onclick="kxCloseModal()" aria-label="Tutup"><i class="fas fa-times"></i></button>
        <div class="kx-modal-head" id="kxModalHead">
            <div class="kx-modal-icon" id="kxModalIcon"><i class="fas fa-graduation-cap"></i></div>
            <div class="kx-modal-headinfo">
                <span class="kx-modal-kode" id="kxModalKode"></span>
                <h2 id="kxModalNama"></h2>
            </div>
        </div>
        <div class="kx-modal-body">
            <div class="kx-modal-chips">
                <span class="kx-info-chip"><i class="fas fa-book"></i> <span id="kxModalMk"></span></span>
                <span class="kx-info-chip"><i class="fas fa-route"></i> Jalur #<span id="kxModalUrutan"></span></span>
            </div>
            <div class="kx-modal-desc-box">
                <div class="kx-desc-label"><i class="fas fa-align-left"></i> DESKRIPSI</div>
                <p id="kxModalDeskripsi"></p>
            </div>
            <div class="kx-modal-mk-section" id="kxModalMkSection">
                <div class="kx-desc-label"><i class="fas fa-book-open"></i> MATA KULIAH PADA JALUR INI</div>
                <div class="kx-mk-chips" id="kxModalMkChips"></div>
            </div>
            <div class="kx-modal-actions">
                <a href="kurikulum.php" class="kx-btn kx-btn-primary"><i class="fas fa-book"></i> Lihat Kurikulum</a>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="kxToast" class="kx-toast">
    <i class="fas fa-check-circle"></i>
    <span id="kxToastMsg">Berhasil!</span>
</div>

<!-- Back to Top -->
<button class="kx-back-top" id="kxBackTop" title="Kembali ke atas">
    <i class="fas fa-arrow-up"></i>
</button>

<style>
:root {
    --kx-primary: #6366F1;
    --kx-indigo: #4F46E5;
    --kx-deep: #1E1B4B;
    --kx-violet: #8B5CF6;
    --kx-pink: #EC4899;
    --kx-amber: #F59E0B;
    --kx-dark: #0F172A;
    --kx-gray-50: #F8FAFC;
    --kx-gray-100: #F1F5F9;
    --kx-gray-200: #E2E8F0;
    --kx-gray-600: #475569;
    --kx-gray-700: #374151;
    --kx-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --kx-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --kx-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
    --kx-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --kx-font-display: 'Space Grotesk', 'Inter', sans-serif;
}
body.gl-dark {
    --kx-gray-50: #0B1120;
    --kx-gray-100: rgba(255,255,255,.08);
    --kx-gray-200: rgba(255,255,255,.1);
    --kx-gray-600: #94A3B8;
    --kx-gray-700: #CBD5E1;
}
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--kx-gray-50); }
::-webkit-scrollbar-thumb { background: linear-gradient(180deg, var(--kx-indigo), var(--kx-violet)); border-radius: 5px; }

/* ===== READING PROGRESS ===== */
.kx-reading-progress { position: fixed; top: 0; left: 0; right: 0; height: 4px; background: rgba(99,102,241,0.1); z-index: 9998; }
.kx-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--kx-indigo), var(--kx-violet), var(--kx-pink));
    background-size: 200% 100%;
    animation: kxGradientMove 3s ease infinite;
    box-shadow: 0 0 10px rgba(99,102,241,0.6);
    transition: width 0.1s ease-out;
}
@keyframes kxGradientMove { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

/* ===== HERO ===== */
.kx-hero {
    background: linear-gradient(135deg, #1E1B4B 0%, #312E81 50%, #4F46E5 100%);
    padding: 160px 20px 120px; position: relative; overflow: hidden;
    min-height: 92vh; display: flex; align-items: center; justify-content: center;
}
.kx-matrix-canvas { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 1; opacity: 0.7; }
.kx-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }
.kx-grid-pattern {
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(165,180,252,0.07) 1px, transparent 1px),
        linear-gradient(90deg, rgba(165,180,252,0.07) 1px, transparent 1px);
    background-size: 50px 50px;
    mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
}
.kx-scanline {
    position: absolute; left: 0; right: 0; height: 2px; top: 0;
    background: linear-gradient(90deg, transparent, var(--kx-violet), transparent);
    box-shadow: 0 0 12px var(--kx-violet);
    animation: kxScan 6s ease-in-out infinite; opacity: 0.6;
}
@keyframes kxScan { 0% { top: 0; opacity: 0; } 10% { opacity: .6; } 90% { opacity: .6; } 100% { top: 100%; opacity: 0; } }
.kx-orb { position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.3; animation: kxOrbFloat 18s infinite ease-in-out; }
.kx-orb-1 { width: 500px; height: 500px; background: var(--kx-violet); top: -150px; right: -100px; }
.kx-orb-2 { width: 380px; height: 380px; background: var(--kx-pink); bottom: -120px; left: -80px; animation-delay: -8s; }
@keyframes kxOrbFloat { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(35px,-35px) scale(1.08); } }

.kx-floating-elements { position: absolute; inset: 0; z-index: 3; pointer-events: none; }
.kx-float-el {
    position: absolute; font-size: 2.4rem;
    animation: kxFloatEl 10s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
    opacity: 0.35; filter: drop-shadow(0 0 15px rgba(139,92,246,0.5));
}
@keyframes kxFloatEl { 0%,100% { transform: translateY(0) rotate(0); opacity: .35; } 50% { transform: translateY(-35px) rotate(12deg); opacity: .75; } }

.kx-hero-content { position: relative; z-index: 4; text-align: center; color: #fff; max-width: 1000px; margin: 0 auto; }
.kx-hero-badge {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 10px 24px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    font-size: .85rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 700; letter-spacing: 2px; position: relative;
    font-family: var(--kx-font-mono);
}
.kx-badge-pulse { position: absolute; inset: 0; border-radius: 50px; border: 2px solid var(--kx-violet); animation: kxBadgePulse 2s infinite; }
@keyframes kxBadgePulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.25); opacity: 0; } }
.kx-hero-badge i { color: #A5B4FC; }

.kx-hero-title { font-size: clamp(3rem, 9vw, 6.5rem); font-weight: 900; margin-bottom: 24px; letter-spacing: -0.03em; line-height: 0.95; font-family: var(--kx-font-display); }
.kx-title-line { display: block; }
.kx-title-matrix {
    position: relative; display: inline-block;
    background: linear-gradient(135deg, #A5B4FC 0%, #8B5CF6 40%, #EC4899 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 100%;
    animation: kxGradientShift 5s ease infinite;
}
@keyframes kxGradientShift { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

.kx-hero-subtitle { font-size: 1.2rem; opacity: .9; max-width: 700px; margin: 0 auto 40px; line-height: 1.7; display: inline-block; min-height: 1.7em; }
.kx-cursor { animation: kxBlink 1s infinite; color: #A5B4FC; font-weight: 300; }
@keyframes kxBlink { 0%,50% { opacity: 1; } 51%,100% { opacity: 0; } }

.kx-hero-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; max-width: 900px; margin: 0 auto; }
.kx-hstat {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 16px;
    backdrop-filter: blur(12px); transition: all 0.3s;
}
.kx-hstat:hover { transform: translateY(-4px); border-color: var(--kx-violet); background: rgba(255,255,255,.1); }
.kx-hstat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: linear-gradient(135deg, var(--kx-indigo), var(--kx-primary));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0; box-shadow: 0 4px 12px rgba(79,70,229,0.4);
}
.kx-hstat-violet { background: linear-gradient(135deg, #8B5CF6, #6D28D9); box-shadow: 0 4px 12px rgba(139,92,246,0.4); }
.kx-hstat-pink { background: linear-gradient(135deg, #EC4899, #BE185D); box-shadow: 0 4px 12px rgba(236,72,153,0.4); }
.kx-hstat-amber { background: linear-gradient(135deg, #F59E0B, #B45309); box-shadow: 0 4px 12px rgba(245,158,11,0.4); }
.kx-hstat-num { display: block; font-size: 1.6rem; font-weight: 900; color: #fff; line-height: 1; font-family: var(--kx-font-display); }
.kx-hstat-label { font-size: 0.7rem; color: rgba(255,255,255,.6); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; display: block; }

.kx-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    color: rgba(255,255,255,.6); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase;
}
.kx-scroll-arrow { animation: kxArrowBounce 2s infinite; }
@keyframes kxArrowBounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(10px); } }

/* ===== MAIN SECTION ===== */
.kx-section { padding: 60px 20px 100px; background: var(--kx-gray-50); }
body.gl-dark .kx-section { background: #0B1120; }

/* Console */
.kx-console { background: #fff; border-radius: 20px; box-shadow: var(--kx-shadow-md); overflow: hidden; border: 1px solid var(--kx-gray-100); margin-bottom: 40px; }
body.gl-dark .kx-console { background: #1E293B; border-color: rgba(255,255,255,.08); }
.kx-console-header { background: var(--kx-dark); padding: 12px 20px; display: flex; align-items: center; gap: 10px; color: #94A3B8; font-family: var(--kx-font-mono); font-size: 0.8rem; }
.kx-console-dots { display: flex; gap: 5px; }
.kx-console-dots .dot { width: 11px; height: 11px; border-radius: 50%; }
.kx-console-dots .red { background: #FF5F56; }
.kx-console-dots .yellow { background: #FFBD2E; }
.kx-console-dots .green { background: #27C93F; }
.kx-console-header i { color: var(--kx-primary); }
.kx-console-header span { color: #E2E8F0; }
.kx-console-status { margin-left: auto; color: var(--kx-primary); font-size: 0.7rem; letter-spacing: 1px; animation: kxBlink 2s infinite; }
.kx-console-body { padding: 24px; display: flex; flex-direction: column; gap: 20px; }

.kx-search-wrap { position: relative; display: flex; align-items: center; }
.kx-search-wrap > i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--kx-gray-600); z-index: 1; font-size: 1rem; }
.kx-search-wrap input {
    width: 100%; padding: 16px 100px 16px 50px;
    border: 1.5px solid var(--kx-gray-200); border-radius: 14px;
    background: var(--kx-gray-50); font-family: inherit; font-size: 1rem; color: var(--kx-dark); transition: all 0.3s;
}
body.gl-dark .kx-search-wrap input { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1); color: #E2E8F0; }
.kx-search-wrap input:focus { outline: none; border-color: var(--kx-indigo); box-shadow: 0 0 0 4px rgba(79,70,229,0.15); }
.kx-search-hint { position: absolute; right: 18px; top: 50%; transform: translateY(-50%); display: flex; gap: 3px; align-items: center; color: var(--kx-gray-600); font-size: 0.75rem; pointer-events: none; }
.kx-search-hint kbd { background: var(--kx-gray-100); padding: 3px 8px; border-radius: 5px; font-family: var(--kx-font-mono); font-size: 0.72rem; border: 1px solid var(--kx-gray-200); }
body.gl-dark .kx-search-hint kbd { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.1); color: #CBD5E1; }

.kx-results-info { padding-top: 16px; border-top: 1px solid var(--kx-gray-100); display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; flex-wrap: wrap; gap: 12px; }
body.gl-dark .kx-results-info { border-color: rgba(255,255,255,.08); }
.kx-results-count { color: var(--kx-gray-600); display: flex; align-items: center; gap: 8px; }
body.gl-dark .kx-results-count { color: #94A3B8; }
.kx-results-count i { color: var(--kx-indigo); }
.kx-results-count strong { color: var(--kx-dark); font-family: var(--kx-font-display); font-size: 1.1rem; font-weight: 800; }
body.gl-dark .kx-results-count strong { color: #F1F5F9; }
.kx-results-meta { color: var(--kx-gray-600); display: flex; align-items: center; gap: 8px; font-family: var(--kx-font-mono); font-size: 0.78rem; }
body.gl-dark .kx-results-meta { color: #94A3B8; }
.kx-results-meta i { color: var(--kx-violet); }

/* ===== GRID & CARDS ===== */
.kx-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 28px; margin-bottom: 60px; }
.kx-card {
    position: relative; background: #fff; border-radius: 20px; overflow: hidden;
    border: 1px solid var(--kx-gray-100); box-shadow: var(--kx-shadow-sm);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex; flex-direction: column; align-items: center; text-align: center; padding: 0 24px 24px;
}
body.gl-dark .kx-card { background: #1E293B; border-color: rgba(255,255,255,.08); }
.kx-card:hover { transform: translateY(-8px); box-shadow: var(--kx-shadow-lg); border-color: var(--kx-color, var(--kx-indigo)); }
.kx-card-accent { position: absolute; top: 0; left: 0; right: 0; height: 5px; background: var(--kx-color, var(--kx-indigo)); box-shadow: 0 0 16px var(--kx-color, var(--kx-indigo)); }
.kx-featured-tag {
    position: absolute; top: 16px; right: 16px;
    padding: 4px 12px; border-radius: 20px;
    background: linear-gradient(135deg, var(--kx-amber), #D97706);
    color: #fff; font-family: var(--kx-font-mono); font-size: 0.66rem; font-weight: 800; letter-spacing: 1px;
    box-shadow: 0 4px 12px rgba(245,158,11,0.4);
}
.kx-card-icon {
    width: 76px; height: 76px; border-radius: 20px; margin: 36px 0 16px;
    background: color-mix(in srgb, var(--kx-color, var(--kx-indigo)) 14%, transparent);
    color: var(--kx-color, var(--kx-indigo));
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; position: relative; transition: transform 0.4s;
}
.kx-card:hover .kx-card-icon { transform: scale(1.08) rotate(-5deg); }
.kx-icon-ring { position: absolute; inset: -6px; border-radius: 24px; border: 2px dashed var(--kx-color, var(--kx-indigo)); opacity: 0; transition: opacity 0.3s; animation: kxRingSpin 12s linear infinite; }
.kx-card:hover .kx-icon-ring { opacity: 0.5; }
@keyframes kxRingSpin { to { transform: rotate(360deg); } }
.kx-kode-badge {
    padding: 4px 14px; border-radius: 20px; font-family: var(--kx-font-mono);
    font-size: 0.7rem; font-weight: 800; letter-spacing: 1px;
    background: var(--kx-color, var(--kx-indigo)); color: #fff; margin-bottom: 12px;
}
.kx-card-title { font-size: 1.2rem; font-weight: 800; line-height: 1.3; color: var(--kx-dark); margin: 0 0 10px 0; font-family: var(--kx-font-display); transition: color 0.3s; }
body.gl-dark .kx-card-title { color: #F1F5F9; }
.kx-card:hover .kx-card-title { color: var(--kx-color, var(--kx-indigo)); }
.kx-card-desc { font-size: 0.86rem; color: var(--kx-gray-600); line-height: 1.6; margin: 0 0 16px 0; flex: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
body.gl-dark .kx-card-desc { color: #94A3B8; }
.kx-card-stats { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; margin-bottom: 16px; }
.kx-info-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: var(--kx-gray-50); border: 1px solid var(--kx-gray-200); border-radius: 20px; font-size: 0.72rem; color: var(--kx-gray-600); font-family: var(--kx-font-mono); }
body.gl-dark .kx-info-chip { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #94A3B8; }
.kx-info-chip i { color: var(--kx-color, var(--kx-indigo)); font-size: 0.68rem; }
.kx-detail-btn {
    display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px;
    background: color-mix(in srgb, var(--kx-color, var(--kx-indigo)) 12%, transparent);
    color: var(--kx-color, var(--kx-indigo)); border: none; border-radius: 10px;
    font-weight: 700; font-size: 0.82rem; cursor: pointer; transition: all 0.3s; font-family: var(--kx-font-mono);
}
.kx-detail-btn:hover { background: var(--kx-color, var(--kx-indigo)); color: #fff; transform: translateY(-2px); }

/* Empty states */
.kx-empty-search, .kx-empty-state { text-align: center; padding: 70px 20px; background: #fff; border-radius: 20px; border: 2px dashed var(--kx-gray-200); margin-bottom: 60px; }
body.gl-dark .kx-empty-search, body.gl-dark .kx-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }
.kx-empty-icon { position: relative; width: 100px; height: 100px; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--kx-gray-600); }
.kx-empty-pulse { position: absolute; inset: 0; border-radius: 50%; border: 2px solid var(--kx-indigo); animation: kxEmptyPulse 2s infinite; }
@keyframes kxEmptyPulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }
.kx-empty-search h3, .kx-empty-state h3 { font-size: 1.4rem; margin: 0 0 8px 0; color: var(--kx-dark); font-weight: 800; font-family: var(--kx-font-display); }
body.gl-dark .kx-empty-search h3, body.gl-dark .kx-empty-state h3 { color: #F1F5F9; }
.kx-empty-search p, .kx-empty-state p { color: var(--kx-gray-600); margin-bottom: 20px; }
.kx-btn-reset { padding: 10px 20px; background: var(--kx-indigo); color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; font-size: 0.9rem; }
.kx-btn-reset:hover { background: var(--kx-primary); transform: translateY(-2px); }

/* ===== LEARNING PATH ===== */
.kx-path { background: #fff; border-radius: 24px; padding: 40px; border: 1px solid var(--kx-gray-100); box-shadow: var(--kx-shadow-sm); margin-bottom: 60px; }
body.gl-dark .kx-path { background: #1E293B; border-color: rgba(255,255,255,.08); }
.kx-path-header { text-align: center; margin-bottom: 36px; }
.kx-section-label { display: inline-block; padding: 6px 16px; background: linear-gradient(135deg, var(--kx-indigo), var(--kx-violet)); color: #fff; border-radius: 20px; font-family: var(--kx-font-mono); font-size: 0.7rem; font-weight: 800; letter-spacing: 2px; margin-bottom: 16px; }
.kx-path-header h2 { font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 900; margin: 0 0 12px 0; color: var(--kx-dark); font-family: var(--kx-font-display); }
body.gl-dark .kx-path-header h2 { color: #F1F5F9; }
.kx-accent { background: linear-gradient(135deg, var(--kx-indigo), var(--kx-pink)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.kx-path-header p { font-size: 1rem; color: var(--kx-gray-600); max-width: 600px; margin: 0 auto; }
body.gl-dark .kx-path-header p { color: #94A3B8; }
.kx-path-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
.kx-path-step { position: relative; padding: 24px 20px; background: var(--kx-gray-50); border-radius: 16px; border: 1px solid var(--kx-gray-100); transition: all 0.3s; }
body.gl-dark .kx-path-step { background: rgba(255,255,255,.03); border-color: rgba(255,255,255,.08); }
.kx-path-step:hover { transform: translateY(-6px); border-color: var(--kx-indigo); box-shadow: var(--kx-shadow-md); }
.kx-step-num { position: absolute; top: 14px; right: 18px; font-family: var(--kx-font-display); font-size: 2.2rem; font-weight: 900; opacity: 0.12; color: var(--kx-indigo); }
.kx-step-icon { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, var(--kx-indigo), var(--kx-violet)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 16px; box-shadow: 0 6px 16px rgba(79,70,229,0.35); }
.kx-path-step h4 { font-size: 0.98rem; font-weight: 800; margin: 0 0 8px 0; color: var(--kx-dark); font-family: var(--kx-font-display); }
body.gl-dark .kx-path-step h4 { color: #F1F5F9; }
.kx-path-step p { font-size: 0.84rem; color: var(--kx-gray-600); line-height: 1.6; margin: 0; }
body.gl-dark .kx-path-step p { color: #94A3B8; }

/* ===== CTA ===== */
.kx-cta { position: relative; border-radius: 28px; overflow: hidden; padding: 60px 40px; text-align: center; color: #fff; background: linear-gradient(135deg, #1E1B4B 0%, #312E81 50%, #4F46E5 100%); box-shadow: var(--kx-shadow-lg); }
.kx-cta-bg { position: absolute; inset: 0; pointer-events: none; }
.kx-cta-grid { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 40px 40px; }
.kx-cta-orb { position: absolute; top: -100px; right: -100px; width: 320px; height: 320px; border-radius: 50%; background: rgba(139,92,246,0.35); filter: blur(60px); }
.kx-cta-content { position: relative; z-index: 1; max-width: 640px; margin: 0 auto; }
.kx-cta-icon { width: 80px; height: 80px; margin: 0 auto 20px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; animation: kxCtaBounce 3s infinite ease-in-out; }
@keyframes kxCtaBounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
.kx-cta h2 { font-size: clamp(1.6rem, 4vw, 2.4rem); margin: 0 0 14px 0; font-weight: 900; font-family: var(--kx-font-display); }
.kx-cta p { font-size: 1.05rem; opacity: 0.9; margin: 0 0 30px 0; line-height: 1.6; }
.kx-cta-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
.kx-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.3s; font-family: inherit; }
.kx-btn-primary { background: #fff; color: var(--kx-indigo); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
.kx-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.3); }
.kx-btn-ghost { background: transparent; color: #fff; border: 2px solid rgba(255,255,255,0.3); }
.kx-btn-ghost:hover { background: rgba(255,255,255,0.1); border-color: #fff; transform: translateY(-3px); }

/* ===== MODAL ===== */
.kx-modal { position: fixed; inset: 0; z-index: 10000; background: rgba(30,27,75,0.85); backdrop-filter: blur(12px); display: none; align-items: center; justify-content: center; padding: 20px; opacity: 0; transition: opacity 0.3s; }
.kx-modal.open { display: flex; opacity: 1; }
.kx-modal-content { background: #fff; border-radius: 24px; max-width: 640px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative; transform: scale(0.92) translateY(20px); transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 30px 60px rgba(0,0,0,0.4); }
body.gl-dark .kx-modal-content { background: #1E293B; }
.kx-modal.open .kx-modal-content { transform: scale(1) translateY(0); }
.kx-modal-close { position: absolute; top: 16px; right: 16px; z-index: 10; width: 42px; height: 42px; border-radius: 50%; background: rgba(255,255,255,0.9); border: none; color: var(--kx-dark); font-size: 1.05rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s; }
.kx-modal-close:hover { background: #EF4444; color: #fff; transform: rotate(90deg); }
.kx-modal-head { display: flex; align-items: center; gap: 18px; padding: 32px 28px 24px; border-bottom: 1px solid var(--kx-gray-100); }
body.gl-dark .kx-modal-head { border-color: rgba(255,255,255,.08); }
.kx-modal-icon { width: 70px; height: 70px; border-radius: 18px; background: var(--kx-indigo); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; flex-shrink: 0; box-shadow: 0 8px 20px rgba(79,70,229,0.35); }
.kx-modal-kode { display: inline-block; padding: 3px 12px; border-radius: 20px; font-family: var(--kx-font-mono); font-size: 0.7rem; font-weight: 800; letter-spacing: 1px; margin-bottom: 6px; }
.kx-modal-headinfo h2 { font-size: 1.5rem; font-weight: 900; margin: 0; color: var(--kx-dark); font-family: var(--kx-font-display); line-height: 1.25; }
body.gl-dark .kx-modal-headinfo h2 { color: #F1F5F9; }
.kx-modal-body { padding: 24px 28px 28px; }
.kx-modal-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
.kx-modal-desc-box { padding: 18px; background: var(--kx-gray-50); border-left: 4px solid var(--kx-indigo); border-radius: 12px; margin-bottom: 18px; }
body.gl-dark .kx-modal-desc-box { background: rgba(255,255,255,.03); }
.kx-desc-label { font-size: 0.7rem; font-weight: 800; letter-spacing: 1.5px; color: var(--kx-indigo); font-family: var(--kx-font-mono); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.kx-modal-desc-box p { margin: 0; font-size: 0.92rem; line-height: 1.8; color: var(--kx-gray-700); }
body.gl-dark .kx-modal-desc-box p { color: #CBD5E1; }
.kx-modal-mk-section { margin-bottom: 20px; }
.kx-mk-chips { display: flex; gap: 8px; flex-wrap: wrap; }
.kx-mk-chip { padding: 6px 12px; background: rgba(99,102,241,0.1); color: var(--kx-indigo); border-radius: 20px; font-size: 0.76rem; font-weight: 600; }
body.gl-dark .kx-mk-chip { background: rgba(99,102,241,0.18); color: #A5B4FC; }
.kx-mk-chip.more { background: var(--kx-gray-100); color: var(--kx-gray-600); font-family: var(--kx-font-mono); }
.kx-modal-actions { display: flex; justify-content: flex-end; }

/* ===== TOAST & BACK TO TOP ===== */
.kx-toast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px); background: var(--kx-dark); color: #fff; padding: 14px 24px; border-radius: 50px; display: flex; align-items: center; gap: 10px; box-shadow: 0 10px 30px rgba(0,0,0,.3); z-index: 10001; opacity: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); font-size: 0.9rem; }
body.gl-dark .kx-toast { background: #fff; color: var(--kx-dark); }
.kx-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.kx-toast i { color: var(--kx-primary); font-size: 1.2rem; }
.kx-back-top { position: fixed; bottom: 30px; right: 30px; width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, var(--kx-indigo), var(--kx-violet)); border: none; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; z-index: 998; box-shadow: 0 8px 24px rgba(79,70,229,0.4); opacity: 0; visibility: hidden; transform: translateY(20px) scale(.8); transition: all .4s; }
.kx-back-top.show { opacity: 1; visibility: visible; transform: none; }
.kx-back-top:hover { transform: translateY(-4px) scale(1.05); }

/* ===== ANIMATIONS ===== */
.fade-up { opacity: 0; transform: translateY(30px); transition: opacity 0.8s ease, transform 0.8s ease; }
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .kx-hero-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .kx-hero { padding: 120px 20px 80px; min-height: 85vh; }
    .kx-hero-title { font-size: 3rem; }
    .kx-hero-stats { grid-template-columns: 1fr 1fr; gap: 10px; }
    .kx-hstat { justify-content: center; }
    .kx-floating-elements { display: none; }
    .kx-console-body { padding: 16px; }
    .kx-search-hint { display: none; }
    .kx-search-wrap input { padding-right: 20px; }
    .kx-results-info { flex-direction: column; align-items: flex-start; gap: 8px; }
    .kx-grid { grid-template-columns: 1fr; }
    .kx-path { padding: 28px 20px; }
    .kx-cta { padding: 40px 20px; }
    .kx-cta-actions { flex-direction: column; }
    .kx-cta-actions .kx-btn { width: 100%; justify-content: center; }
    .kx-back-top { width: 44px; height: 44px; bottom: 20px; right: 20px; }
}
@media (max-width: 480px) {
    .kx-hero-title { font-size: 2.3rem; }
    .kx-hero-badge { font-size: 0.7rem; padding: 8px 16px; letter-spacing: 1px; }
    .kx-hstat-num { font-size: 1.3rem; }
    .kx-card { padding: 0 18px 18px; }
}

/* ============================================================
   PERFORMANCE PATCH - BUILT-IN (SMOOTH SCROLL SINCE DAY ONE)
============================================================ */
main section, .kx-section { scroll-margin-top: 90px; }
.kx-section { content-visibility: auto; contain-intrinsic-size: auto 2000px; }
.kx-hero-bg, .kx-floating-elements, .kx-reading-progress, .kx-grid-pattern { pointer-events: none; }
.kx-matrix-canvas, .kx-orb, .kx-scanline, .kx-float-el, .kx-progress-fill { will-change: transform; backface-visibility: hidden; }
.fade-up { backface-visibility: hidden; }
.fade-up.visible { will-change: auto; }

body.is-scrolling .kx-orb,
body.is-scrolling .kx-scanline,
body.is-scrolling .kx-float-el,
body.is-scrolling .kx-progress-fill,
body.is-scrolling .kx-badge-pulse { animation-play-state: paused !important; }
.kx-offscreen * { animation-play-state: paused !important; }

@media (max-width: 768px), (hover: none), (pointer: coarse) {
    .kx-orb { filter: blur(60px); opacity: 0.2 !important; animation: none !important; }
    .kx-scanline { display: none !important; }
    .kx-floating-elements { display: none !important; }
    .kx-hstat, .kx-hero-badge, .kx-cta-icon { backdrop-filter: none !important; }
}
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
    .kx-scanline, .kx-floating-elements { display: none !important; }
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
            entries.forEach(entry => entry.target.classList.toggle('kx-offscreen', !entry.isIntersecting));
        }, { rootMargin: '300px 0px', threshold: 0 });
        document.querySelectorAll('.kx-hero, .kx-section').forEach(s => sectionObserver.observe(s));

        const heroSection = document.querySelector('.kx-hero');
        if (heroSection) {
            window.__kxHeroVisible = true;
            new IntersectionObserver((entries) => {
                const entry = entries[0];
                window.__kxHeroVisible = entry ? entry.isIntersecting : true;
                window.dispatchEvent(new CustomEvent('kx:hero-visibility', { detail: window.__kxHeroVisible }));
            }, { threshold: 0.01 }).observe(heroSection);
        }
    }

    // ===== 1. Matrix Canvas (OPTIMIZED: 30 FPS, no shadowBlur, stop offscreen) =====
    const canvas = document.getElementById('kxMatrixCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d', { alpha: true });
        let width, height, nodes = [], time = 0, running = true, lastFrame = 0;
        const frameInterval = 1000 / 30;

        function resize() {
            width = canvas.width = canvas.parentElement.offsetWidth;
            height = canvas.height = canvas.parentElement.offsetHeight;
            initNodes();
        }
        function initNodes() {
            nodes = [];
            const num = Math.min(Math.floor((width * height) / 18000), 55);
            for (let i = 0; i < num; i++) {
                nodes.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    vx: (Math.random() - 0.5) * 0.4,
                    vy: (Math.random() - 0.5) * 0.4,
                    size: Math.random() * 2.5 + 1.5,
                    phase: Math.random() * Math.PI * 2
                });
            }
        }
        function drawNode(n) {
            const pulse = Math.sin(time * 0.03 + n.phase) * 0.3 + 0.7;
            const s = n.size * pulse;
            ctx.beginPath();
            ctx.moveTo(n.x, n.y - s);
            ctx.lineTo(n.x + s, n.y);
            ctx.lineTo(n.x, n.y + s);
            ctx.lineTo(n.x - s, n.y);
            ctx.closePath();
            ctx.strokeStyle = 'rgba(165, 180, 252, 0.7)';
            ctx.lineWidth = 1;
            ctx.stroke();
        }
        function drawLinks() {
            const maxDistSq = 130 * 130;
            for (let i = 0; i < nodes.length; i++) {
                for (let j = i + 1; j < nodes.length; j++) {
                    const dx = nodes[i].x - nodes[j].x;
                    const dy = nodes[i].y - nodes[j].y;
                    const distSq = dx * dx + dy * dy;
                    if (distSq < maxDistSq) {
                        const opacity = (1 - Math.sqrt(distSq) / 130) * 0.25;
                        ctx.beginPath();
                        ctx.strokeStyle = 'rgba(139, 92, 246, ' + opacity + ')';
                        ctx.lineWidth = 0.5;
                        ctx.moveTo(nodes[i].x, nodes[i].y);
                        ctx.lineTo(nodes[j].x, nodes[j].y);
                        ctx.stroke();
                    }
                }
            }
        }
        function updateNodes() {
            nodes.forEach(n => {
                n.x += n.vx; n.y += n.vy;
                if (n.x < 0 || n.x > width) n.vx *= -1;
                if (n.y < 0 || n.y > height) n.vy *= -1;
            });
        }
        function animate(currentTime) {
            requestAnimationFrame(animate);
            if (!running || document.hidden || window.__kxHeroVisible === false) return;
            const elapsed = currentTime - lastFrame;
            if (elapsed < frameInterval) return;
            lastFrame = currentTime - (elapsed % frameInterval);

            ctx.clearRect(0, 0, width, height);
            drawLinks();
            nodes.forEach(drawNode);
            updateNodes();
            time++;
        }
        window.addEventListener('kx:hero-visibility', (e) => { running = Boolean(e.detail); });
        document.addEventListener('visibilitychange', () => {
            running = !document.hidden && window.__kxHeroVisible !== false;
        });
        resize();
        window.addEventListener('resize', resize, { passive: true });
        requestAnimationFrame(animate);
    }

    // ===== 2. Typing Effect =====
    const textElement = document.getElementById('kxTypingText');
    if (textElement) {
        const phrases = [
            "Pilih jalur spesialisasi sesuai passion dan tujuan karirmu.",
            "Kurikulum setiap konsentrasi dirancang bersama industri.",
            "Dari data science hingga software engineering, semua ada di sini.",
            "Setiap jalur dilengkapi proyek nyata dan sertifikasi profesional."
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
    const counters = document.querySelectorAll('.kx-hstat-num[data-count]');
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
    const progressFill = document.getElementById('kxProgressFill');
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
            if (entry.isIntersecting && entry.target) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('.fade-up').forEach(el => { if (el) observer.observe(el); });

    // ===== 6. Live Search (DEBOUNCED) =====
    const searchInput = document.getElementById('kxSearchInput');
    const grid = document.getElementById('kxGrid');
    const emptySearch = document.getElementById('kxEmptySearch');
    const resultsCount = document.getElementById('kxResultsCount');

    if (searchInput && grid) {
        let searchDebounce;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                const query = e.target.value.toLowerCase().trim();
                const cards = grid.querySelectorAll('.kx-card');
                let visibleCount = 0;
                cards.forEach(card => {
                    const hay = card.getAttribute('data-search') || '';
                    if (!query || hay.includes(query)) { card.style.display = ''; visibleCount++; }
                    else { card.style.display = 'none'; }
                });
                if (resultsCount) resultsCount.textContent = visibleCount;
                if (emptySearch) emptySearch.style.display = (query && visibleCount === 0) ? 'block' : 'none';
            }, 120);
        });
    }
    window.kxResetSearch = function() {
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        }
    };

    // ===== 7. Modal Detail =====
    const modal = document.getElementById('kxModal');
    window.kxOpenModal = function(data) {
        if (!modal || !data) return;
        const warna = data.warna || '#6366F1';

        const head = document.getElementById('kxModalHead');
        head.style.background = 'linear-gradient(135deg, ' + warna + '26, transparent)';

        const icon = document.getElementById('kxModalIcon');
        icon.style.background = warna;
        icon.innerHTML = '<i class="fas ' + data.ikon + '"></i>';

        const kode = document.getElementById('kxModalKode');
        kode.textContent = data.kode;
        kode.style.background = warna + '22';
        kode.style.color = warna;

        document.getElementById('kxModalNama').textContent = data.nama;
        document.getElementById('kxModalDeskripsi').textContent = data.deskripsi;
        document.getElementById('kxModalMk').textContent = data.total_mk + ' Mata Kuliah';
        document.getElementById('kxModalUrutan').textContent = data.urutan;

        const mkSection = document.getElementById('kxModalMkSection');
        const mkChips = document.getElementById('kxModalMkChips');
        if (data.mk && data.mk.length > 0) {
            mkSection.style.display = 'block';
            let html = data.mk.map(m => '<span class="kx-mk-chip">' + m + '</span>').join('');
            if (data.mk_more > 0) html += '<span class="kx-mk-chip more">+' + data.mk_more + ' lainnya</span>';
            mkChips.innerHTML = html;
        } else {
            mkSection.style.display = 'none';
            mkChips.innerHTML = '';
        }

        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    };
    window.kxCloseModal = function() {
        if (modal) { modal.classList.remove('open'); document.body.style.overflow = ''; }
    };
    if (modal) {
        modal.addEventListener('click', (e) => { if (e.target === modal) kxCloseModal(); });
    }

    // ===== 8. Keyboard Shortcuts =====
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) searchInput.focus();
        }
        if (e.key === 'Escape') kxCloseModal();
    });

    // ===== 9. Toast =====
    window.kxShowToast = function(message) {
        const toast = document.getElementById('kxToast');
        if (!toast) return;
        const msgEl = document.getElementById('kxToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // ===== 10. Back to Top (THROTTLED) =====
    const backTop = document.getElementById('kxBackTop');
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

    // Welcome toast
    setTimeout(() => kxShowToast('🎓 Selamat menjelajahi jalur spesialisasi!'), 1500);

    // Console Easter Egg
    console.log('%c╔══════════════════════════════════════════╗', 'color:#6366F1;font-weight:bold');
    console.log('%c║   EXPERTISE MATRIX - Specialization Map  ║', 'color:#6366F1;font-weight:bold;font-size:14px');
    console.log('%c╚══════════════════════════════════════════╝', 'color:#6366F1;font-weight:bold');
    console.log('%c🎓 ' + document.querySelectorAll('.kx-card').length + ' specialization paths mapped (OPTIMIZED)', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>