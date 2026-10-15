<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Fasilitas & Infrastruktur';
$pageDesc = 'Jelajahi laboratorium, ruangan, dan peralatan pendukung modern Program Studi Informatika.';

$filterJenis = isset($_GET['jenis']) ? clean($_GET['jenis']) : '';

/* ===== JENIS CONFIG (SINKRON DENGAN ADMIN) ===== */
$jenisConfig = [
    'laboratorium' => ['icon' => 'fa-flask',     'color' => '#06B6D4', 'label' => 'Laboratorium'],
    'ruangan'      => ['icon' => 'fa-door-open', 'color' => '#8B5CF6', 'label' => 'Ruangan'],
    'peralatan'    => ['icon' => 'fa-tools',     'color' => '#F59E0B', 'label' => 'Peralatan'],
    'lainnya'      => ['icon' => 'fa-box',       'color' => '#6B7280', 'label' => 'Lainnya'],
];

if (!function_exists('getFasilitasImage')) {
    function getFasilitasImage($gambar) {
        if ($gambar && strpos($gambar, 'http') === 0) return $gambar;
        if ($gambar) {
            $candidates = [$gambar, 'uploads/' . $gambar, 'uploads/fasilitas/' . $gambar];
            foreach ($candidates as $path) {
                if (file_exists(__DIR__ . '/' . $path)) return $path;
            }
        }
        return '';
    }
}

$allList = [];
$fasilitasList = [];
$stats = ['total' => 0, 'laboratorium' => 0, 'ruangan' => 0, 'peralatan' => 0, 'lainnya' => 0, 'kapasitas' => 0];
$dist = [];
$topLocations = [];

try {
    $db = getDB();
    $allList = cache('public_fasilitas_all', 600, function() use ($db) {
        try {
            return $db->query("SELECT * FROM fasilitas WHERE status IN ('aktif','maintenance') ORDER BY urutan ASC, nama ASC")->fetchAll();
        } catch (Throwable $e) {
            return $db->query("SELECT * FROM fasilitas WHERE status IN ('aktif','maintenance') ORDER BY nama ASC")->fetchAll();
        }
    });
} catch (Throwable $e) {
    $allList = [];
}

/* Stats, distribusi & top lokasi dihitung dari satu sumber data */
foreach ($allList as $f) {
    $j = $f['jenis'] ?? 'lainnya';
    if (!isset($jenisConfig[$j])) $j = 'lainnya';
    $stats['total']++;
    $stats[$j]++;
    $kap = (int)($f['kapasitas'] ?? 0);
    $stats['kapasitas'] += $kap;
    if (!isset($dist[$j])) $dist[$j] = ['total' => 0, 'kapasitas' => 0];
    $dist[$j]['total']++;
    $dist[$j]['kapasitas'] += $kap;
    $loc = trim($f['lokasi'] ?? '');
    if ($loc !== '') $topLocations[$loc] = ($topLocations[$loc] ?? 0) + 1;
}
arsort($topLocations);
$topLocations = array_slice($topLocations, 0, 6, true);

$fasilitasList = $filterJenis
    ? array_values(array_filter($allList, fn($f) => ($f['jenis'] ?? 'lainnya') === $filterJenis))
    : $allList;
?>

<!-- ===== READING PROGRESS ===== -->
<div class="fs-reading-progress">
    <div class="fs-progress-fill" id="fsProgressFill"></div>
</div>

<!-- ===== HERO: FACILITY BLUEPRINT ===== -->
<section class="fs-hero">
    <canvas id="fsBlueprintCanvas" class="fs-blueprint-canvas"></canvas>
    <div class="fs-hero-bg">
        <div class="fs-blueprint-grid"></div>
        <div class="fs-scanline"></div>
        <div class="fs-orb fs-orb-1"></div>
        <div class="fs-orb fs-orb-2"></div>
    </div>

    <div class="fs-floating-elements">
        <span class="fs-float-el" style="--delay: 0s;   --x: 8%;  --y: 22%;">🏢</span>
        <span class="fs-float-el" style="--delay: 1.4s; --x: 88%; --y: 26%;">🧪</span>
        <span class="fs-float-el" style="--delay: 2.6s; --x: 12%; --y: 72%;">🖥️</span>
        <span class="fs-float-el" style="--delay: 0.9s; --x: 85%; --y: 68%;">📚</span>
        <span class="fs-float-el" style="--delay: 3.2s; --x: 50%; --y: 14%;">🔧</span>
        <span class="fs-float-el" style="--delay: 2s;   --x: 45%; --y: 85%;">💺</span>
    </div>

    <div class="container fs-hero-content">
        <span class="fs-hero-badge fade-up">
            <span class="fs-badge-pulse"></span>
            <i class="fas fa-building"></i> CAMPUS INFRASTRUCTURE
        </span>

        <h1 class="fs-hero-title fade-up">
            <span class="fs-title-line">Fasilitas</span>
            <span class="fs-title-line fs-title-blueprint" data-text="Kampus Modern">Kampus Modern</span>
        </h1>

        <p class="fs-hero-subtitle fade-up">
            <span id="fsTypingText"></span><span class="fs-cursor">|</span>
        </p>

        <div class="fs-hero-stats fade-up">
            <div class="fs-hstat">
                <div class="fs-hstat-icon"><i class="fas fa-building"></i></div>
                <div class="fs-hstat-info">
                    <span class="fs-hstat-num" data-count="<?= $stats['total'] ?>">0</span>
                    <span class="fs-hstat-label">Fasilitas</span>
                </div>
            </div>
            <div class="fs-hstat">
                <div class="fs-hstat-icon fs-hstat-cyan"><i class="fas fa-flask"></i></div>
                <div class="fs-hstat-info">
                    <span class="fs-hstat-num" data-count="<?= $stats['laboratorium'] ?>">0</span>
                    <span class="fs-hstat-label">Laboratorium</span>
                </div>
            </div>
            <div class="fs-hstat">
                <div class="fs-hstat-icon fs-hstat-violet"><i class="fas fa-door-open"></i></div>
                <div class="fs-hstat-info">
                    <span class="fs-hstat-num" data-count="<?= $stats['ruangan'] ?>">0</span>
                    <span class="fs-hstat-label">Ruangan</span>
                </div>
            </div>
            <div class="fs-hstat">
                <div class="fs-hstat-icon fs-hstat-amber"><i class="fas fa-users"></i></div>
                <div class="fs-hstat-info">
                    <span class="fs-hstat-num" data-count="<?= $stats['kapasitas'] ?>">0</span>
                    <span class="fs-hstat-label">Kapasitas</span>
                </div>
            </div>
        </div>
    </div>

    <div class="fs-scroll-indicator">
        <span>Explore Infrastructure</span>
        <div class="fs-scroll-arrow"><i class="fas fa-chevron-down"></i></div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<section class="fs-section">
    <div class="container">

        <!-- Facility Explorer Console -->
        <div class="fs-console fade-up">
            <div class="fs-console-header">
                <div class="fs-console-dots">
                    <span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span>
                </div>
                <i class="fas fa-compass-drafting"></i>
                <span>facility_explorer.exe</span>
                <span class="fs-console-status">● SCANNING</span>
            </div>

            <div class="fs-console-body">
                <div class="fs-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="fsSearchInput" placeholder="Cari fasilitas, lokasi, atau kata kunci..." autocomplete="off">
                    <span class="fs-search-hint"><kbd>Ctrl</kbd>+<kbd>K</kbd></span>
                </div>

                <div class="fs-jenis-nav">
                    <span class="fs-nav-label"><i class="fas fa-layer-group"></i> KATEGORI:</span>
                    <div class="fs-jenis-pills">
                        <a href="fasilitas.php" class="fs-jenis-pill <?= !$filterJenis ? 'active' : '' ?>" style="--pill-color: #06B6D4;">
                            <i class="fas fa-infinity"></i> <span>Semua</span>
                        </a>
                        <?php foreach ($jenisConfig as $key => $cfg): ?>
                        <a href="?jenis=<?= $key ?>" class="fs-jenis-pill <?= $filterJenis === $key ? 'active' : '' ?>" style="--pill-color: <?= $cfg['color'] ?>;">
                            <i class="fas <?= $cfg['icon'] ?>"></i> <span><?= $cfg['label'] ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="fs-results-info">
                    <span class="fs-results-count">
                        <i class="fas fa-building"></i>
                        <strong id="fsResultsCount"><?= count($fasilitasList) ?></strong> fasilitas ditemukan
                    </span>
                    <span class="fs-results-meta">
                        <i class="fas fa-filter"></i>
                        <?= $filterJenis ? 'Filter: ' . ($jenisConfig[$filterJenis]['label'] ?? $filterJenis) : 'Menampilkan semua' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Facility Grid -->
        <?php if (!empty($fasilitasList)): ?>
        <div class="fs-grid" id="fsGrid">
            <?php foreach ($fasilitasList as $index => $f):
                $j = $f['jenis'] ?? 'lainnya';
                $cfg = $jenisConfig[$j] ?? $jenisConfig['lainnya'];
                $img = getFasilitasImage($f['gambar'] ?? '');
                $kap = (int)($f['kapasitas'] ?? 0);
                $status = $f['status'] ?? 'aktif';
                $ikon = clean($f['ikon'] ?? '') ?: $cfg['icon'];
                $modalData = htmlspecialchars(json_encode([
                    'nama'       => clean($f['nama'] ?? 'Tanpa Nama'),
                    'jenisLabel' => $cfg['label'],
                    'color'      => $cfg['color'],
                    'icon'       => $ikon,
                    'lokasi'     => clean($f['lokasi'] ?? '') ?: '-',
                    'kapasitas'  => $kap,
                    'status'     => $status,
                    'deskripsi'  => clean($f['deskripsi'] ?? '') ?: 'Belum ada deskripsi untuk fasilitas ini.',
                    'gambar'     => $img,
                    'tanggal'    => function_exists('formatTanggal') ? formatTanggal($f['created_at'] ?? date('Y-m-d H:i:s')) : date('d M Y'),
                ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                $searchAttr = strtolower(clean(($f['nama'] ?? '') . ' ' . ($f['lokasi'] ?? '') . ' ' . ($cfg['label']) . ' ' . ($f['deskripsi'] ?? '')));
            ?>
            <article class="fs-card fade-up" style="animation-delay: <?= $index * 0.06 ?>s; --fs-color: <?= $cfg['color'] ?>;" data-search="<?= htmlspecialchars($searchAttr) ?>">
                <div class="fs-card-media">
                    <?php if ($img): ?>
                        <img src="<?= $img ?>" alt="<?= clean($f['nama']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="fs-card-placeholder"><i class="fas <?= $ikon ?>"></i></div>
                    <?php endif; ?>
                    <div class="fs-card-scan"></div>
                    <span class="fs-type-badge" style="background: <?= $cfg['color'] ?>;"><i class="fas <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?></span>
                    <?php if ($status !== 'aktif'): ?>
                    <span class="fs-status-badge"><i class="fas fa-tools"></i> <?= ucfirst($status) ?></span>
                    <?php endif; ?>
                </div>

                <div class="fs-card-body">
                    <h3 class="fs-card-title"><?= clean($f['nama'] ?? 'Tanpa Nama') ?></h3>
                    <?php if (!empty($f['deskripsi'])): ?>
                    <p class="fs-card-desc"><?= clean(mb_substr($f['deskripsi'], 0, 90)) ?><?= strlen($f['deskripsi']) > 90 ? '...' : '' ?></p>
                    <?php endif; ?>
                    <div class="fs-card-info">
                        <?php if (!empty($f['lokasi'])): ?><span class="fs-info-chip"><i class="fas fa-map-marker-alt"></i> <?= clean($f['lokasi']) ?></span><?php endif; ?>
                        <?php if ($kap > 0): ?><span class="fs-info-chip"><i class="fas fa-users"></i> <?= $kap ?> orang</span><?php endif; ?>
                    </div>
                </div>

                <div class="fs-card-footer">
                    <button class="fs-detail-btn" onclick='fsOpenModal(<?= $modalData ?>)'>
                        <i class="fas fa-eye"></i> Lihat Detail
                    </button>
                    <span class="fs-card-id">#FAS-<?= str_pad($index + 1, 3, '0', STR_PAD_LEFT) ?></span>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="fs-empty-search" id="fsEmptySearch" style="display: none;">
            <div class="fs-empty-icon"><i class="fas fa-search-minus"></i><div class="fs-empty-pulse"></div></div>
            <h3>Fasilitas Tidak Ditemukan</h3>
            <p>Tidak ada fasilitas yang cocok dengan kata kunci pencarian Anda.</p>
            <button class="fs-btn-reset" onclick="fsResetSearch()"><i class="fas fa-redo"></i> Reset Pencarian</button>
        </div>
        <?php else: ?>
        <div class="fs-empty-state fade-up">
            <div class="fs-empty-icon"><i class="fas fa-building"></i><div class="fs-empty-pulse"></div></div>
            <h3>Belum Ada Data Fasilitas</h3>
            <p>Data fasilitas aktif akan segera ditampilkan oleh administrator.</p>
        </div>
        <?php endif; ?>

        <!-- Infrastructure Map -->
        <?php if (!empty($dist)): ?>
        <div class="fs-map fade-up">
            <div class="fs-map-header">
                <span class="fs-section-label">INFRASTRUCTURE MAP</span>
                <h2>Sebaran <span class="fs-accent">Fasilitas</span> Kami</h2>
                <p>Distribusi infrastruktur pendukung kegiatan akademik berdasarkan kategori</p>
            </div>

            <div class="fs-map-grid">
                <?php $maxDist = max(array_column($dist, 'total')); foreach ($dist as $key => $d):
                    $cfg = $jenisConfig[$key] ?? $jenisConfig['lainnya'];
                    $pct = round(($d['total'] / $maxDist) * 100);
                ?>
                <div class="fs-map-item">
                    <div class="fs-map-head">
                        <span class="fs-map-name" style="color: <?= $cfg['color'] ?>;">
                            <i class="fas <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?>
                        </span>
                        <span class="fs-map-count"><?= $d['total'] ?> unit · <?= number_format($d['kapasitas']) ?> kursi</span>
                    </div>
                    <div class="fs-map-bar">
                        <div class="fs-map-fill" style="width: <?= $pct ?>%; background: linear-gradient(90deg, <?= $cfg['color'] ?>, <?= $cfg['color'] ?>99);"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($topLocations)): ?>
            <div class="fs-locations">
                <span class="fs-loc-label"><i class="fas fa-map-marker-alt"></i> LOKASI POPULER:</span>
                <div class="fs-loc-chips">
                    <?php foreach ($topLocations as $loc => $count): ?>
                    <span class="fs-loc-chip"><?= clean($loc) ?> <strong><?= $count ?></strong></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="fs-cta fade-up">
            <div class="fs-cta-bg"><div class="fs-cta-grid"></div><div class="fs-cta-orb"></div></div>
            <div class="fs-cta-content">
                <div class="fs-cta-icon"><i class="fas fa-headset"></i></div>
                <h2>Butuh Informasi Jadwal atau Peminjaman Ruangan?</h2>
                <p>Tim administrasi prodi siap membantu keperluan akademik dan kegiatan mahasiswa terkait penggunaan fasilitas kampus.</p>
                <div class="fs-cta-actions">
                    <a href="index.php#contact" class="fs-btn fs-btn-primary"><i class="fas fa-paper-plane"></i> Hubungi Kami</a>
                    <a href="galeri.php" class="fs-btn fs-btn-ghost"><i class="fas fa-images"></i> Lihat Dokumentasi</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== MODAL DETAIL ===== -->
<div class="fs-modal" id="fsModal">
    <div class="fs-modal-content">
        <button class="fs-modal-close" onclick="fsCloseModal()" aria-label="Tutup"><i class="fas fa-times"></i></button>
        <div class="fs-modal-media" id="fsModalMedia">
            <img src="" alt="" id="fsModalImg" style="display:none;">
            <div class="fs-modal-placeholder" id="fsModalPlaceholder"><i class="fas fa-flask" id="fsModalIcon"></i></div>
            <span class="fs-type-badge" id="fsModalBadge"></span>
            <span class="fs-status-badge" id="fsModalStatus" style="display:none;"></span>
        </div>
        <div class="fs-modal-body">
            <h2 id="fsModalNama"></h2>
            <div class="fs-modal-chips">
                <span class="fs-info-chip"><i class="fas fa-map-marker-alt"></i> <span id="fsModalLokasi"></span></span>
                <span class="fs-info-chip"><i class="fas fa-users"></i> <span id="fsModalKapasitas"></span></span>
                <span class="fs-info-chip"><i class="fas fa-calendar"></i> <span id="fsModalTanggal"></span></span>
            </div>
            <div class="fs-modal-desc-box">
                <div class="fs-desc-label"><i class="fas fa-align-left"></i> DESKRIPSI</div>
                <p id="fsModalDeskripsi"></p>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="fsToast" class="fs-toast">
    <i class="fas fa-check-circle"></i>
    <span id="fsToastMsg">Berhasil!</span>
</div>

<!-- Back to Top -->
<button class="fs-back-top" id="fsBackTop" title="Kembali ke atas">
    <i class="fas fa-arrow-up"></i>
</button>

<style>
:root {
    --fs-primary: #06B6D4;
    --fs-primary-dark: #0891B2;
    --fs-deep: #082F49;
    --fs-deeper: #0C4A6E;
    --fs-violet: #8B5CF6;
    --fs-amber: #F59E0B;
    --fs-dark: #0F172A;
    --fs-gray-50: #F8FAFC;
    --fs-gray-100: #F1F5F9;
    --fs-gray-200: #E2E8F0;
    --fs-gray-600: #475569;
    --fs-gray-700: #374151;
    --fs-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --fs-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --fs-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
    --fs-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --fs-font-display: 'Space Grotesk', 'Inter', sans-serif;
}
body.gl-dark {
    --fs-gray-50: #0B1120;
    --fs-gray-100: rgba(255,255,255,.08);
    --fs-gray-200: rgba(255,255,255,.1);
    --fs-gray-600: #94A3B8;
    --fs-gray-700: #CBD5E1;
}
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--fs-gray-50); }
::-webkit-scrollbar-thumb { background: linear-gradient(180deg, var(--fs-primary), var(--fs-violet)); border-radius: 5px; }

/* ===== READING PROGRESS ===== */
.fs-reading-progress { position: fixed; top: 0; left: 0; right: 0; height: 4px; background: rgba(6,182,212,0.1); z-index: 9998; }
.fs-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--fs-primary), var(--fs-violet), var(--fs-amber));
    background-size: 200% 100%;
    animation: fsGradientMove 3s ease infinite;
    box-shadow: 0 0 10px rgba(6,182,212,0.6);
    transition: width 0.1s ease-out;
}
@keyframes fsGradientMove { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

/* ===== HERO ===== */
.fs-hero {
    background: linear-gradient(135deg, #082F49 0%, #0C4A6E 50%, #164E63 100%);
    padding: 160px 20px 120px; position: relative; overflow: hidden;
    min-height: 92vh; display: flex; align-items: center; justify-content: center;
}
.fs-blueprint-canvas { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 1; opacity: 0.7; }
.fs-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }
.fs-blueprint-grid {
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(6,182,212,0.07) 1px, transparent 1px),
        linear-gradient(90deg, rgba(6,182,212,0.07) 1px, transparent 1px);
    background-size: 50px 50px;
    mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
}
.fs-scanline {
    position: absolute; left: 0; right: 0; height: 2px; top: 0;
    background: linear-gradient(90deg, transparent, var(--fs-primary), transparent);
    box-shadow: 0 0 12px var(--fs-primary);
    animation: fsScan 6s ease-in-out infinite;
    opacity: 0.6;
}
@keyframes fsScan { 0% { top: 0; opacity: 0; } 10% { opacity: .6; } 90% { opacity: .6; } 100% { top: 100%; opacity: 0; } }
.fs-orb { position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.3; animation: fsOrbFloat 18s infinite ease-in-out; }
.fs-orb-1 { width: 500px; height: 500px; background: var(--fs-primary); top: -150px; right: -100px; }
.fs-orb-2 { width: 380px; height: 380px; background: var(--fs-violet); bottom: -120px; left: -80px; animation-delay: -8s; }
@keyframes fsOrbFloat { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(35px,-35px) scale(1.08); } }

.fs-floating-elements { position: absolute; inset: 0; z-index: 3; pointer-events: none; }
.fs-float-el {
    position: absolute; font-size: 2.4rem;
    animation: fsFloatEl 10s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
    opacity: 0.35; filter: drop-shadow(0 0 15px rgba(6,182,212,0.5));
}
@keyframes fsFloatEl { 0%,100% { transform: translateY(0) rotate(0); opacity: .35; } 50% { transform: translateY(-35px) rotate(12deg); opacity: .75; } }

.fs-hero-content { position: relative; z-index: 4; text-align: center; color: #fff; max-width: 1000px; margin: 0 auto; }
.fs-hero-badge {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 10px 24px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    font-size: .85rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 700; letter-spacing: 2px; position: relative;
    font-family: var(--fs-font-mono);
}
.fs-badge-pulse { position: absolute; inset: 0; border-radius: 50px; border: 2px solid var(--fs-primary); animation: fsBadgePulse 2s infinite; }
@keyframes fsBadgePulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.25); opacity: 0; } }
.fs-hero-badge i { color: var(--fs-primary); }

.fs-hero-title { font-size: clamp(3rem, 9vw, 6.5rem); font-weight: 900; margin-bottom: 24px; letter-spacing: -0.03em; line-height: 0.95; font-family: var(--fs-font-display); }
.fs-title-line { display: block; }
.fs-title-blueprint {
    position: relative; display: inline-block;
    background: linear-gradient(135deg, #22D3EE 0%, #8B5CF6 50%, #F59E0B 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 100%;
    animation: fsGradientShift 5s ease infinite;
}
@keyframes fsGradientShift { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

.fs-hero-subtitle { font-size: 1.2rem; opacity: .9; max-width: 700px; margin: 0 auto 40px; line-height: 1.7; display: inline-block; min-height: 1.7em; }
.fs-cursor { animation: fsBlink 1s infinite; color: var(--fs-primary); font-weight: 300; }
@keyframes fsBlink { 0%,50% { opacity: 1; } 51%,100% { opacity: 0; } }

.fs-hero-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; max-width: 900px; margin: 0 auto; }
.fs-hstat {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 16px;
    backdrop-filter: blur(12px); transition: all 0.3s;
}
.fs-hstat:hover { transform: translateY(-4px); border-color: var(--fs-primary); background: rgba(255,255,255,.1); }
.fs-hstat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: linear-gradient(135deg, var(--fs-primary), var(--fs-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(6,182,212,0.3);
}
.fs-hstat-cyan { background: linear-gradient(135deg, #22D3EE, #06B6D4); }
.fs-hstat-violet { background: linear-gradient(135deg, #8B5CF6, #6D28D9); box-shadow: 0 4px 12px rgba(139,92,246,0.3); }
.fs-hstat-amber { background: linear-gradient(135deg, #F59E0B, #B45309); box-shadow: 0 4px 12px rgba(245,158,11,0.3); }
.fs-hstat-num { display: block; font-size: 1.6rem; font-weight: 900; color: #fff; line-height: 1; font-family: var(--fs-font-display); }
.fs-hstat-label { font-size: 0.7rem; color: rgba(255,255,255,.6); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; display: block; }

.fs-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    color: rgba(255,255,255,.6); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase;
}
.fs-scroll-arrow { animation: fsArrowBounce 2s infinite; }
@keyframes fsArrowBounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(10px); } }

/* ===== MAIN SECTION ===== */
.fs-section { padding: 60px 20px 100px; background: var(--fs-gray-50); }
body.gl-dark .fs-section { background: #0B1120; }

/* Console */
.fs-console { background: #fff; border-radius: 20px; box-shadow: var(--fs-shadow-md); overflow: hidden; border: 1px solid var(--fs-gray-100); margin-bottom: 40px; }
body.gl-dark .fs-console { background: #1E293B; border-color: rgba(255,255,255,.08); }
.fs-console-header { background: var(--fs-dark); padding: 12px 20px; display: flex; align-items: center; gap: 10px; color: #94A3B8; font-family: var(--fs-font-mono); font-size: 0.8rem; }
.fs-console-dots { display: flex; gap: 5px; }
.fs-console-dots .dot { width: 11px; height: 11px; border-radius: 50%; }
.fs-console-dots .red { background: #FF5F56; }
.fs-console-dots .yellow { background: #FFBD2E; }
.fs-console-dots .green { background: #27C93F; }
.fs-console-header i { color: var(--fs-primary); }
.fs-console-header span { color: #E2E8F0; }
.fs-console-status { margin-left: auto; color: var(--fs-primary); font-size: 0.7rem; letter-spacing: 1px; animation: fsBlink 2s infinite; }
.fs-console-body { padding: 24px; display: flex; flex-direction: column; gap: 20px; }

.fs-search-wrap { position: relative; display: flex; align-items: center; }
.fs-search-wrap > i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--fs-gray-600); z-index: 1; font-size: 1rem; }
.fs-search-wrap input {
    width: 100%; padding: 16px 100px 16px 50px;
    border: 1.5px solid var(--fs-gray-200); border-radius: 14px;
    background: var(--fs-gray-50); font-family: inherit; font-size: 1rem; color: var(--fs-dark); transition: all 0.3s;
}
body.gl-dark .fs-search-wrap input { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1); color: #E2E8F0; }
.fs-search-wrap input:focus { outline: none; border-color: var(--fs-primary); box-shadow: 0 0 0 4px rgba(6,182,212,0.15); }
.fs-search-hint { position: absolute; right: 18px; top: 50%; transform: translateY(-50%); display: flex; gap: 3px; align-items: center; color: var(--fs-gray-600); font-size: 0.75rem; pointer-events: none; }
.fs-search-hint kbd { background: var(--fs-gray-100); padding: 3px 8px; border-radius: 5px; font-family: var(--fs-font-mono); font-size: 0.72rem; border: 1px solid var(--fs-gray-200); }
body.gl-dark .fs-search-hint kbd { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.1); color: #CBD5E1; }

.fs-jenis-nav { padding-top: 16px; border-top: 1px solid var(--fs-gray-100); }
body.gl-dark .fs-jenis-nav { border-color: rgba(255,255,255,.08); }
.fs-nav-label { font-size: 0.72rem; font-weight: 700; color: var(--fs-gray-600); letter-spacing: 1.5px; font-family: var(--fs-font-mono); display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
body.gl-dark .fs-nav-label { color: #94A3B8; }
.fs-nav-label i { color: var(--fs-primary); }
.fs-jenis-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.fs-jenis-pill {
    padding: 8px 16px; border-radius: 24px; font-size: 0.82rem; font-weight: 600;
    color: var(--fs-gray-700); background: var(--fs-gray-100); border: 1.5px solid transparent;
    text-decoration: none; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px;
}
body.gl-dark .fs-jenis-pill { background: rgba(255,255,255,.05); color: #CBD5E1; }
.fs-jenis-pill i { color: var(--pill-color, var(--fs-primary)); font-size: 0.78rem; }
.fs-jenis-pill:hover { transform: translateY(-2px); border-color: var(--pill-color); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.fs-jenis-pill.active { background: var(--pill-color); color: #fff; border-color: var(--pill-color); box-shadow: 0 4px 16px rgba(0,0,0,0.2); }
.fs-jenis-pill.active i { color: #fff; }

.fs-results-info { padding-top: 16px; border-top: 1px solid var(--fs-gray-100); display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; flex-wrap: wrap; gap: 12px; }
body.gl-dark .fs-results-info { border-color: rgba(255,255,255,.08); }
.fs-results-count { color: var(--fs-gray-600); display: flex; align-items: center; gap: 8px; }
body.gl-dark .fs-results-count { color: #94A3B8; }
.fs-results-count i { color: var(--fs-primary); }
.fs-results-count strong { color: var(--fs-dark); font-family: var(--fs-font-display); font-size: 1.1rem; font-weight: 800; }
body.gl-dark .fs-results-count strong { color: #F1F5F9; }
.fs-results-meta { color: var(--fs-gray-600); display: flex; align-items: center; gap: 8px; font-family: var(--fs-font-mono); font-size: 0.78rem; }
body.gl-dark .fs-results-meta { color: #94A3B8; }
.fs-results-meta i { color: var(--fs-violet); }

/* ===== GRID & CARDS ===== */
.fs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 28px; margin-bottom: 60px; }
.fs-card {
    position: relative; background: #fff; border-radius: 20px; overflow: hidden;
    border: 1px solid var(--fs-gray-100); box-shadow: var(--fs-shadow-sm);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column;
}
body.gl-dark .fs-card { background: #1E293B; border-color: rgba(255,255,255,.08); }
.fs-card:hover { transform: translateY(-8px); box-shadow: var(--fs-shadow-lg); border-color: var(--fs-color, var(--fs-primary)); }

.fs-card-media { position: relative; height: 200px; overflow: hidden; background: var(--fs-gray-100); }
.fs-card-media img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s cubic-bezier(0.4,0,0.2,1); }
.fs-card:hover .fs-card-media img { transform: scale(1.08); }
.fs-card-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: var(--fs-color, var(--fs-primary)); background: linear-gradient(135deg, color-mix(in srgb, var(--fs-color, var(--fs-primary)) 12%, transparent), color-mix(in srgb, var(--fs-color, var(--fs-primary)) 4%, transparent)); }
.fs-card-scan { position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, var(--fs-color, var(--fs-primary)), transparent); opacity: 0; animation: fsCardScan 4s ease-in-out infinite; }
.fs-card:hover .fs-card-scan { opacity: 1; }
@keyframes fsCardScan { 0%,100% { top: 0; opacity: 0; } 50% { top: 100%; opacity: 1; } }

.fs-type-badge { position: absolute; top: 14px; left: 14px; z-index: 3; padding: 5px 12px; color: #fff; border-radius: 20px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
.fs-status-badge { position: absolute; top: 14px; right: 14px; z-index: 3; padding: 5px 12px; background: rgba(245,158,11,0.95); color: #fff; border-radius: 20px; font-size: 0.68rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }

.fs-card-body { padding: 22px; flex: 1; display: flex; flex-direction: column; }
.fs-card-title { font-size: 1.15rem; font-weight: 800; line-height: 1.3; color: var(--fs-dark); margin: 0 0 8px 0; font-family: var(--fs-font-display); transition: color 0.3s; }
body.gl-dark .fs-card-title { color: #F1F5F9; }
.fs-card:hover .fs-card-title { color: var(--fs-color, var(--fs-primary)); }
.fs-card-desc { font-size: 0.85rem; color: var(--fs-gray-600); line-height: 1.6; margin: 0 0 14px 0; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
body.gl-dark .fs-card-desc { color: #94A3B8; }
.fs-card-info { display: flex; gap: 8px; flex-wrap: wrap; }
.fs-info-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: var(--fs-gray-50); border: 1px solid var(--fs-gray-200); border-radius: 20px; font-size: 0.72rem; color: var(--fs-gray-600); font-family: var(--fs-font-mono); }
body.gl-dark .fs-info-chip { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #94A3B8; }
.fs-info-chip i { color: var(--fs-color, var(--fs-primary)); font-size: 0.68rem; }

.fs-card-footer { padding: 14px 22px; background: var(--fs-gray-50); border-top: 1px solid var(--fs-gray-100); display: flex; justify-content: space-between; align-items: center; }
body.gl-dark .fs-card-footer { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.08); }
.fs-detail-btn {
    display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px;
    background: color-mix(in srgb, var(--fs-color, var(--fs-primary)) 12%, transparent);
    color: var(--fs-color, var(--fs-primary)); border: none; border-radius: 10px;
    font-weight: 700; font-size: 0.8rem; cursor: pointer; transition: all 0.3s; font-family: var(--fs-font-mono);
}
.fs-detail-btn:hover { background: var(--fs-color, var(--fs-primary)); color: #fff; transform: translateY(-2px); }
.fs-card-id { font-family: var(--fs-font-mono); font-size: 0.68rem; color: var(--fs-gray-600); letter-spacing: 1px; }
body.gl-dark .fs-card-id { color: #94A3B8; }

/* Empty states */
.fs-empty-search, .fs-empty-state { text-align: center; padding: 70px 20px; background: #fff; border-radius: 20px; border: 2px dashed var(--fs-gray-200); margin-bottom: 60px; }
body.gl-dark .fs-empty-search, body.gl-dark .fs-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }
.fs-empty-icon { position: relative; width: 100px; height: 100px; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--fs-gray-600); }
.fs-empty-pulse { position: absolute; inset: 0; border-radius: 50%; border: 2px solid var(--fs-primary); animation: fsEmptyPulse 2s infinite; }
@keyframes fsEmptyPulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }
.fs-empty-search h3, .fs-empty-state h3 { font-size: 1.4rem; margin: 0 0 8px 0; color: var(--fs-dark); font-weight: 800; font-family: var(--fs-font-display); }
body.gl-dark .fs-empty-search h3, body.gl-dark .fs-empty-state h3 { color: #F1F5F9; }
.fs-empty-search p, .fs-empty-state p { color: var(--fs-gray-600); margin-bottom: 20px; }
.fs-btn-reset { padding: 10px 20px; background: var(--fs-primary); color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; font-size: 0.9rem; }
.fs-btn-reset:hover { background: var(--fs-primary-dark); transform: translateY(-2px); }

/* ===== INFRASTRUCTURE MAP ===== */
.fs-map { background: #fff; border-radius: 24px; padding: 40px; border: 1px solid var(--fs-gray-100); box-shadow: var(--fs-shadow-sm); margin-bottom: 60px; }
body.gl-dark .fs-map { background: #1E293B; border-color: rgba(255,255,255,.08); }
.fs-map-header { text-align: center; margin-bottom: 36px; }
.fs-section-label { display: inline-block; padding: 6px 16px; background: linear-gradient(135deg, var(--fs-primary), var(--fs-violet)); color: #fff; border-radius: 20px; font-family: var(--fs-font-mono); font-size: 0.7rem; font-weight: 800; letter-spacing: 2px; margin-bottom: 16px; }
.fs-map-header h2 { font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 900; margin: 0 0 12px 0; color: var(--fs-dark); font-family: var(--fs-font-display); }
body.gl-dark .fs-map-header h2 { color: #F1F5F9; }
.fs-accent { background: linear-gradient(135deg, var(--fs-primary), var(--fs-violet)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.fs-map-header p { font-size: 1rem; color: var(--fs-gray-600); max-width: 600px; margin: 0 auto; }
body.gl-dark .fs-map-header p { color: #94A3B8; }
.fs-map-grid { display: flex; flex-direction: column; gap: 20px; margin-bottom: 28px; }
.fs-map-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px; }
.fs-map-name { font-weight: 800; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; font-family: var(--fs-font-display); }
.fs-map-count { font-family: var(--fs-font-mono); font-size: 0.75rem; color: var(--fs-gray-600); }
body.gl-dark .fs-map-count { color: #94A3B8; }
.fs-map-bar { height: 10px; background: var(--fs-gray-100); border-radius: 6px; overflow: hidden; }
body.gl-dark .fs-map-bar { background: rgba(255,255,255,.08); }
.fs-map-fill { height: 100%; border-radius: 6px; transition: width 1s ease-out; box-shadow: 0 0 10px rgba(6,182,212,0.3); }
.fs-locations { padding-top: 24px; border-top: 1px dashed var(--fs-gray-200); display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
body.gl-dark .fs-locations { border-color: rgba(255,255,255,.1); }
.fs-loc-label { font-size: 0.72rem; font-weight: 700; color: var(--fs-gray-600); letter-spacing: 1.5px; font-family: var(--fs-font-mono); display: inline-flex; align-items: center; gap: 8px; }
body.gl-dark .fs-loc-label { color: #94A3B8; }
.fs-loc-label i { color: var(--fs-primary); }
.fs-loc-chips { display: flex; gap: 8px; flex-wrap: wrap; }
.fs-loc-chip { padding: 6px 14px; background: var(--fs-gray-50); border: 1px solid var(--fs-gray-200); border-radius: 20px; font-size: 0.78rem; color: var(--fs-gray-700); font-weight: 600; }
body.gl-dark .fs-loc-chip { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.fs-loc-chip strong { color: var(--fs-primary); font-family: var(--fs-font-mono); margin-left: 4px; }

/* ===== CTA ===== */
.fs-cta { position: relative; border-radius: 28px; overflow: hidden; padding: 60px 40px; text-align: center; color: #fff; background: linear-gradient(135deg, #082F49 0%, #0C4A6E 50%, #0891B2 100%); box-shadow: var(--fs-shadow-lg); }
.fs-cta-bg { position: absolute; inset: 0; pointer-events: none; }
.fs-cta-grid { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 40px 40px; }
.fs-cta-orb { position: absolute; top: -100px; right: -100px; width: 320px; height: 320px; border-radius: 50%; background: rgba(6,182,212,0.3); filter: blur(60px); }
.fs-cta-content { position: relative; z-index: 1; max-width: 640px; margin: 0 auto; }
.fs-cta-icon { width: 80px; height: 80px; margin: 0 auto 20px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; animation: fsCtaBounce 3s infinite ease-in-out; }
@keyframes fsCtaBounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
.fs-cta h2 { font-size: clamp(1.6rem, 4vw, 2.4rem); margin: 0 0 14px 0; font-weight: 900; font-family: var(--fs-font-display); }
.fs-cta p { font-size: 1.05rem; opacity: 0.9; margin: 0 0 30px 0; line-height: 1.6; }
.fs-cta-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
.fs-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.3s; font-family: inherit; }
.fs-btn-primary { background: #fff; color: var(--fs-primary-dark); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
.fs-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.3); }
.fs-btn-ghost { background: transparent; color: #fff; border: 2px solid rgba(255,255,255,0.3); }
.fs-btn-ghost:hover { background: rgba(255,255,255,0.1); border-color: #fff; transform: translateY(-3px); }

/* ===== MODAL ===== */
.fs-modal { position: fixed; inset: 0; z-index: 10000; background: rgba(8,47,73,0.85); backdrop-filter: blur(12px); display: none; align-items: center; justify-content: center; padding: 20px; opacity: 0; transition: opacity 0.3s; }
.fs-modal.open { display: flex; opacity: 1; }
.fs-modal-content { background: #fff; border-radius: 24px; max-width: 640px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative; transform: scale(0.92) translateY(20px); transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 30px 60px rgba(0,0,0,0.4); }
body.gl-dark .fs-modal-content { background: #1E293B; }
.fs-modal.open .fs-modal-content { transform: scale(1) translateY(0); }
.fs-modal-close { position: absolute; top: 16px; right: 16px; z-index: 10; width: 42px; height: 42px; border-radius: 50%; background: rgba(255,255,255,0.9); border: none; color: var(--fs-dark); font-size: 1.05rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s; }
.fs-modal-close:hover { background: #EF4444; color: #fff; transform: rotate(90deg); }
.fs-modal-media { position: relative; height: 260px; background: var(--fs-gray-100); overflow: hidden; }
.fs-modal-media img { width: 100%; height: 100%; object-fit: cover; }
.fs-modal-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 5rem; }
.fs-modal-body { padding: 28px; }
.fs-modal-body h2 { font-size: 1.6rem; font-weight: 900; margin: 0 0 16px 0; color: var(--fs-dark); font-family: var(--fs-font-display); }
body.gl-dark .fs-modal-body h2 { color: #F1F5F9; }
.fs-modal-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
.fs-modal-desc-box { padding: 18px; background: var(--fs-gray-50); border-left: 4px solid var(--fs-primary); border-radius: 12px; }
body.gl-dark .fs-modal-desc-box { background: rgba(255,255,255,.03); }
.fs-desc-label { font-size: 0.7rem; font-weight: 800; letter-spacing: 1.5px; color: var(--fs-primary); font-family: var(--fs-font-mono); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.fs-modal-desc-box p { margin: 0; font-size: 0.92rem; line-height: 1.8; color: var(--fs-gray-700); }
body.gl-dark .fs-modal-desc-box p { color: #CBD5E1; }

/* ===== TOAST & BACK TO TOP ===== */
.fs-toast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px); background: var(--fs-dark); color: #fff; padding: 14px 24px; border-radius: 50px; display: flex; align-items: center; gap: 10px; box-shadow: 0 10px 30px rgba(0,0,0,.3); z-index: 10001; opacity: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); font-size: 0.9rem; }
body.gl-dark .fs-toast { background: #fff; color: var(--fs-dark); }
.fs-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.fs-toast i { color: var(--fs-primary); font-size: 1.2rem; }
.fs-back-top { position: fixed; bottom: 30px; right: 30px; width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, var(--fs-primary), var(--fs-violet)); border: none; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; z-index: 998; box-shadow: 0 8px 24px rgba(6,182,212,0.4); opacity: 0; visibility: hidden; transform: translateY(20px) scale(.8); transition: all .4s; }
.fs-back-top.show { opacity: 1; visibility: visible; transform: none; }
.fs-back-top:hover { transform: translateY(-4px) scale(1.05); }

/* ===== ANIMATIONS ===== */
.fade-up { opacity: 0; transform: translateY(30px); transition: opacity 0.8s ease, transform 0.8s ease; }
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .fs-hero-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .fs-hero { padding: 120px 20px 80px; min-height: 85vh; }
    .fs-hero-title { font-size: 3rem; }
    .fs-hero-stats { grid-template-columns: 1fr 1fr; gap: 10px; }
    .fs-hstat { justify-content: center; }
    .fs-floating-elements { display: none; }
    .fs-console-body { padding: 16px; }
    .fs-search-hint { display: none; }
    .fs-search-wrap input { padding-right: 20px; }
    .fs-jenis-pills { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 6px; }
    .fs-jenis-pill { white-space: nowrap; flex-shrink: 0; }
    .fs-results-info { flex-direction: column; align-items: flex-start; gap: 8px; }
    .fs-grid { grid-template-columns: 1fr; }
    .fs-map { padding: 28px 20px; }
    .fs-cta { padding: 40px 20px; }
    .fs-cta-actions { flex-direction: column; }
    .fs-cta-actions .fs-btn { width: 100%; justify-content: center; }
    .fs-back-top { width: 44px; height: 44px; bottom: 20px; right: 20px; }
}
@media (max-width: 480px) {
    .fs-hero-title { font-size: 2.3rem; }
    .fs-hero-badge { font-size: 0.7rem; padding: 8px 16px; letter-spacing: 1px; }
    .fs-hstat-num { font-size: 1.3rem; }
    .fs-card-body { padding: 18px; }
}

/* ============================================================
   PERFORMANCE PATCH - BUILT-IN (SMOOTH SCROLL SINCE DAY ONE)
============================================================ */
main section, .fs-section { scroll-margin-top: 90px; }
.fs-section { content-visibility: auto; contain-intrinsic-size: auto 2000px; }
.fs-hero-bg, .fs-floating-elements, .fs-reading-progress, .fs-card-scan { pointer-events: none; }
.fs-blueprint-canvas, .fs-orb, .fs-scanline, .fs-float-el, .fs-progress-fill { will-change: transform; backface-visibility: hidden; }
.fade-up { backface-visibility: hidden; }
.fade-up.visible { will-change: auto; }

body.is-scrolling .fs-orb,
body.is-scrolling .fs-scanline,
body.is-scrolling .fs-float-el,
body.is-scrolling .fs-progress-fill,
body.is-scrolling .fs-badge-pulse,
body.is-scrolling .fs-card-scan { animation-play-state: paused !important; }
.fs-offscreen * { animation-play-state: paused !important; }

@media (max-width: 768px), (hover: none), (pointer: coarse) {
    .fs-orb { filter: blur(60px); opacity: 0.2 !important; animation: none !important; }
    .fs-scanline { display: none !important; }
    .fs-floating-elements { display: none !important; }
    .fs-hstat, .fs-hero-badge, .fs-cta-icon { backdrop-filter: none !important; }
}
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
    .fs-scanline, .fs-floating-elements { display: none !important; }
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
            entries.forEach(entry => entry.target.classList.toggle('fs-offscreen', !entry.isIntersecting));
        }, { rootMargin: '300px 0px', threshold: 0 });
        document.querySelectorAll('.fs-hero, .fs-section').forEach(s => sectionObserver.observe(s));

        const heroSection = document.querySelector('.fs-hero');
        if (heroSection) {
            window.__fsHeroVisible = true;
            new IntersectionObserver((entries) => {
                const entry = entries[0];
                window.__fsHeroVisible = entry ? entry.isIntersecting : true;
                window.dispatchEvent(new CustomEvent('fs:hero-visibility', { detail: window.__fsHeroVisible }));
            }, { threshold: 0.01 }).observe(heroSection);
        }
    }

    // ===== 1. Blueprint Canvas (OPTIMIZED: 30 FPS, no shadowBlur, stop offscreen) =====
    const canvas = document.getElementById('fsBlueprintCanvas');
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
                    size: Math.random() * 2 + 1.5,
                    phase: Math.random() * Math.PI * 2
                });
            }
        }
        function drawNode(n) {
            const pulse = Math.sin(time * 0.03 + n.phase) * 0.3 + 0.7;
            const s = n.size * pulse;
            ctx.beginPath();
            ctx.moveTo(n.x - s, n.y); ctx.lineTo(n.x + s, n.y);
            ctx.moveTo(n.x, n.y - s); ctx.lineTo(n.x, n.y + s);
            ctx.strokeStyle = 'rgba(34, 211, 238, 0.7)';
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
                        ctx.strokeStyle = 'rgba(6, 182, 212, ' + opacity + ')';
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
            if (!running || document.hidden || window.__fsHeroVisible === false) return;
            const elapsed = currentTime - lastFrame;
            if (elapsed < frameInterval) return;
            lastFrame = currentTime - (elapsed % frameInterval);

            ctx.clearRect(0, 0, width, height);
            drawLinks();
            nodes.forEach(drawNode);
            updateNodes();
            time++;
        }
        window.addEventListener('fs:hero-visibility', (e) => { running = Boolean(e.detail); });
        document.addEventListener('visibilitychange', () => {
            running = !document.hidden && window.__fsHeroVisible !== false;
        });
        resize();
        window.addEventListener('resize', resize, { passive: true });
        requestAnimationFrame(animate);
    }

    // ===== 2. Typing Effect =====
    const textElement = document.getElementById('fsTypingText');
    if (textElement) {
        const phrases = [
            "Laboratorium berstandar industri untuk praktikum dan riset.",
            "Ruangan belajar modern yang nyaman dan berteknologi terkini.",
            "Peralatan lengkap pendukung kegiatan akademik dan kemahasiswaan.",
            "Jelajahi infrastruktur Program Studi Informatika secara menyeluruh."
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
    const counters = document.querySelectorAll('.fs-hstat-num[data-count]');
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
    const progressFill = document.getElementById('fsProgressFill');
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
    const searchInput = document.getElementById('fsSearchInput');
    const grid = document.getElementById('fsGrid');
    const emptySearch = document.getElementById('fsEmptySearch');
    const resultsCount = document.getElementById('fsResultsCount');

    if (searchInput && grid) {
        let searchDebounce;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                const query = e.target.value.toLowerCase().trim();
                const cards = grid.querySelectorAll('.fs-card');
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
    window.fsResetSearch = function() {
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        }
    };

    // ===== 7. Modal Detail =====
    const modal = document.getElementById('fsModal');
    window.fsOpenModal = function(data) {
        if (!modal || !data) return;
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

        const img = document.getElementById('fsModalImg');
        const placeholder = document.getElementById('fsModalPlaceholder');
        const icon = document.getElementById('fsModalIcon');
        if (data.gambar) {
            img.src = data.gambar; img.alt = data.nama; img.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            img.style.display = 'none';
            placeholder.style.display = 'flex';
            placeholder.style.color = data.color;
            placeholder.style.background = data.color + '15';
            icon.className = 'fas ' + data.icon;
        }

        const badge = document.getElementById('fsModalBadge');
        badge.style.background = data.color;
        badge.innerHTML = '<i class="fas ' + data.icon + '"></i> ' + data.jenisLabel;

        const statusBadge = document.getElementById('fsModalStatus');
        if (data.status && data.status !== 'aktif') {
            statusBadge.style.display = 'inline-flex';
            statusBadge.innerHTML = '<i class="fas fa-tools"></i> ' + data.status.charAt(0).toUpperCase() + data.status.slice(1);
        } else {
            statusBadge.style.display = 'none';
        }

        set('fsModalNama', data.nama);
        set('fsModalLokasi', data.lokasi);
        set('fsModalKapasitas', data.kapasitas > 0 ? data.kapasitas + ' orang' : 'Tidak ditentukan');
        set('fsModalTanggal', data.tanggal);
        set('fsModalDeskripsi', data.deskripsi);

        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    };
    window.fsCloseModal = function() {
        if (modal) { modal.classList.remove('open'); document.body.style.overflow = ''; }
    };
    if (modal) {
        modal.addEventListener('click', (e) => { if (e.target === modal) fsCloseModal(); });
    }

    // ===== 8. Keyboard Shortcuts =====
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) searchInput.focus();
        }
        if (e.key === 'Escape') fsCloseModal();
    });

    // ===== 9. Toast =====
    window.fsShowToast = function(message) {
        const toast = document.getElementById('fsToast');
        if (!toast) return;
        const msgEl = document.getElementById('fsToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // ===== 10. Back to Top (THROTTLED) =====
    const backTop = document.getElementById('fsBackTop');
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
    setTimeout(() => fsShowToast('🏢 Selamat menjelajahi fasilitas kampus!'), 1500);

    // Console Easter Egg
    console.log('%c╔══════════════════════════════════════════╗', 'color:#06B6D4;font-weight:bold');
    console.log('%c║   FACILITY BLUEPRINT - Infrastructure    ║', 'color:#06B6D4;font-weight:bold;font-size:14px');
    console.log('%c╚══════════════════════════════════════════╝', 'color:#06B6D4;font-weight:bold');
    console.log('%c🏢 ' + document.querySelectorAll('.fs-card').length + ' facilities mapped (OPTIMIZED)', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>