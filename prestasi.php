<?php
require_once 'includes/init_security.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Prestasi Mahasiswa';
$pageDesc = 'Daftar prestasi dan pencapaian mahasiswa Program Studi Informatika di tingkat nasional dan internasional.';

$filterTingkat = isset($_GET['tingkat']) ? clean($_GET['tingkat']) : '';
$filterTahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;

try {
    $db = getDB();
    $where = "status = 'aktif'";
    $params = [];
    
    if ($filterTingkat) {
        $where .= " AND tingkat = ?";
        $params[] = $filterTingkat;
    }
    if ($filterTahun > 0) {
        $where .= " AND tahun = ?";
        $params[] = $filterTahun;
    }
    
    // QUERY ASLI DIPERTAHANKAN - DIJAMIN BERHASIL
    $sql = "SELECT * FROM prestasi WHERE $where ORDER BY tahun DESC, urutan ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $prestasiList = $stmt->fetchAll();
    
    $tahunList = $db->query("SELECT DISTINCT tahun FROM prestasi WHERE tahun IS NOT NULL AND status='aktif' ORDER BY tahun DESC")->fetchAll(PDO::FETCH_COLUMN);
    
    $stats = [
        'total' => (int)$db->query("SELECT COUNT(*) FROM prestasi WHERE status='aktif'")->fetchColumn(),
        'nasional' => (int)$db->query("SELECT COUNT(*) FROM prestasi WHERE tingkat='nasional' AND status='aktif'")->fetchColumn(),
        'internasional' => (int)$db->query("SELECT COUNT(*) FROM prestasi WHERE tingkat='internasional' AND status='aktif'")->fetchColumn(),
    ];
} catch (Throwable $e) {
    $prestasiList = [];
    $tahunList = [];
    $stats = ['total'=>0,'nasional'=>0,'internasional'=>0];
}

// Helper styling (DIPERTAHANKAN)
$tingkatStyle = [
    'internal' => ['Internal', 'fa-building', '#6B7280', '#F3F4F6'],
    'regional' => ['Regional', 'fa-map', '#0284C7', '#E0F2FE'],
    'nasional' => ['Nasional', 'fa-flag', '#DC2626', '#FEE2E2'],
    'internasional' => ['Internasional', 'fa-globe', '#7C3AED', '#EDE9FE'],
];

$kategoriStyle = [
    'akademik' => ['Akademik', 'fa-book', '#2563EB'],
    'nonakademik' => ['Non-Akademik', 'fa-palette', '#EC4899'],
    'kompetisi' => ['Kompetisi', 'fa-trophy', '#F59E0B'],
    'penelitian' => ['Penelitian', 'fa-flask', '#10B981'],
];
?>

<!-- ===== HERO SECTION WITH CANVAS ===== -->
<section class="pr-hero">
    <canvas id="prNetworkCanvas" class="pr-network-canvas"></canvas>
    <div class="pr-hero-bg">
        <div class="pr-orb pr-orb-1"></div>
        <div class="pr-orb pr-orb-2"></div>
        <div class="pr-grid-pattern"></div>
    </div>
    
    <div class="container pr-hero-content">
        <span class="pr-hero-badge fade-up">
            <span class="pr-badge-pulse"></span>
            <i class="fas fa-trophy"></i> Hall of Fame
        </span>
        
        <h1 class="pr-hero-title fade-up">
            Kebanggaan <span class="pr-text-gradient">Informatika Pro</span>
        </h1>
        
        <p class="pr-hero-subtitle fade-up" id="prTypingText"></p>
        <span class="pr-cursor">|</span>
        
        <div class="pr-hero-stats fade-up">
            <div class="pr-stat-pill">
                <i class="fas fa-trophy"></i>
                <div>
                    <span class="pr-stat-num" data-count="<?= $stats['total'] ?>">0</span>
                    <span class="pr-stat-label">Total Prestasi</span>
                </div>
            </div>
            <div class="pr-stat-pill">
                <i class="fas fa-flag"></i>
                <div>
                    <span class="pr-stat-num" data-count="<?= $stats['nasional'] ?>">0</span>
                    <span class="pr-stat-label">Tingkat Nasional</span>
                </div>
            </div>
            <div class="pr-stat-pill">
                <i class="fas fa-globe"></i>
                <div>
                    <span class="pr-stat-num" data-count="<?= $stats['internasional'] ?>">0</span>
                    <span class="pr-stat-label">Internasional</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="pr-scroll-indicator">
        <span>Explore Achievements</span>
        <div class="pr-mouse"><div class="pr-wheel"></div></div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<section class="pr-main-section">
    <div class="container">
        
        <!-- Filter Console -->
        <div class="pr-filter-console fade-up">
            <div class="pr-console-header">
                <div class="pr-console-dots">
                    <span class="dot red"></span>
                    <span class="dot yellow"></span>
                    <span class="dot green"></span>
                </div>
                <i class="fas fa-terminal"></i>
                <span>Filter_Achievements.exe</span>
            </div>
            
            <div class="pr-console-body">
                <div class="pr-filter-group">
                    <label><i class="fas fa-layer-group"></i> Tingkat Prestasi:</label>
                    <div class="pr-filter-pills">
                        <button class="pr-pill <?= !$filterTingkat ? 'active' : '' ?>" data-filter-tingkat="all">Semua</button>
                        <?php foreach ($tingkatStyle as $key => $t): ?>
                            <button class="pr-pill <?= $filterTingkat === $key ? 'active' : '' ?>" 
                                    data-filter-tingkat="<?= $key ?>"
                                    style="--pill-color: <?= $t[2] ?>;">
                                <i class="fas <?= $t[1] ?>"></i> <?= $t[0] ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if (!empty($tahunList)): ?>
                <div class="pr-filter-group">
                    <label><i class="fas fa-calendar"></i> Tahun:</label>
                    <div class="pr-filter-pills">
                        <button class="pr-pill <?= !$filterTahun ? 'active' : '' ?>" data-filter-tahun="all">Semua</button>
                        <?php foreach ($tahunList as $thn): ?>
                            <button class="pr-pill <?= $filterTahun == $thn ? 'active' : '' ?>" data-filter-tahun="<?= $thn ?>"><?= $thn ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <button class="pr-btn-reset" onclick="prResetFilters()">
                    <i class="fas fa-sync-alt"></i> Reset Query
                </button>
            </div>
        </div>

        <!-- Prestasi Grid -->
        <?php if (!empty($prestasiList)): ?>
            <div class="pr-grid" id="prGrid">
                <?php foreach ($prestasiList as $index => $p):
                    $tingkat = $tingkatStyle[$p['tingkat']] ?? $tingkatStyle['internal'];
                    $kategori = $kategoriStyle[$p['kategori']] ?? $kategoriStyle['kompetisi'];
                    $hasAchievement = !empty($p['pencapaian']);
                ?>
                <div class="pr-card pr-tilt fade-up <?= $hasAchievement ? 'pr-featured' : '' ?>" 
                     data-tingkat="<?= $p['tingkat'] ?>" 
                     data-tahun="<?= $p['tahun'] ?>"
                     data-index="<?= $index ?>"
                     style="animation-delay: <?= $index * 0.05 ?>s; --card-accent: <?= $kategori[2] ?>;"
                     onclick="prOpenModal(<?= $index ?>)">
                    
                    <div class="pr-card-glow"></div>
                    
                    <?php if ($hasAchievement): ?>
                    <div class="pr-achievement-ribbon">
                        <i class="fas fa-medal"></i>
                        <span><?= clean($p['pencapaian']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($p['gambar']): ?>
                    <div class="pr-card-img">
                        <img src="uploads/<?= htmlspecialchars($p['gambar']) ?>" alt="<?= clean($p['judul']) ?>" loading="lazy">
                        <div class="pr-img-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="pr-card-img pr-placeholder" style="background: linear-gradient(135deg, <?= $kategori[2] ?>, <?= $kategori[2] ?>dd)">
                        <i class="fas <?= $kategori[1] ?>"></i>
                        <span class="pr-placeholder-year"><?= $p['tahun'] ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="pr-card-body">
                        <div class="pr-card-badges">
                            <span class="pr-badge pr-badge-level" style="background: <?= $tingkat[3] ?>; color: <?= $tingkat[2] ?>;">
                                <i class="fas <?= $tingkat[1] ?>"></i> <?= $tingkat[0] ?>
                            </span>
                            <span class="pr-badge pr-badge-cat" style="background: <?= $kategori[2] ?>15; color: <?= $kategori[2] ?>;">
                                <i class="fas <?= $kategori[1] ?>"></i> <?= $kategori[0] ?>
                            </span>
                        </div>
                        
                        <h3 class="pr-card-title"><?= clean($p['judul']) ?></h3>
                        
                        <?php if ($p['nama_tim']): ?>
                        <div class="pr-card-team">
                            <i class="fas fa-users"></i> <?= clean($p['nama_tim']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($p['deskripsi']): ?>
                        <p class="pr-card-desc"><?= clean($p['deskripsi']) ?></p>
                        <?php endif; ?>
                        
                        <div class="pr-card-footer">
                            <span class="pr-card-year">
                                <i class="fas fa-calendar-alt"></i> <?= $p['tahun'] ?>
                            </span>
                            <button class="pr-card-more">
                                Detail <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="pr-empty-state fade-up">
                <div class="pr-empty-icon">
                    <i class="fas fa-trophy"></i>
                    <div class="pr-empty-pulse"></div>
                </div>
                <h3>Tidak Ada Prestasi Ditemukan</h3>
                <p>Belum ada prestasi yang sesuai dengan filter yang dipilih.</p>
                <?php if ($filterTingkat || $filterTahun): ?>
                <button class="pr-btn-primary" onclick="prResetFilters()">
                    <i class="fas fa-redo"></i> Reset Filter
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== DETAIL MODAL ===== -->
<div class="pr-modal-backdrop" id="prModal">
    <div class="pr-modal-container">
        <button class="pr-modal-close" onclick="prCloseModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="pr-modal-content">
            <div class="pr-modal-img" id="prModalImg"></div>
            <div class="pr-modal-body">
                <div class="pr-modal-badges" id="prModalBadges"></div>
                <h2 class="pr-modal-title" id="prModalTitle"></h2>
                <div class="pr-modal-achievement" id="prModalAchievement" style="display:none;">
                    <i class="fas fa-medal"></i>
                    <span id="prModalAchievementText"></span>
                </div>
                <div class="pr-modal-team" id="prModalTeam" style="display:none;">
                    <i class="fas fa-users"></i>
                    <span id="prModalTeamText"></span>
                </div>
                <div class="pr-modal-desc" id="prModalDesc"></div>
                <div class="pr-modal-meta">
                    <span><i class="fas fa-calendar-alt"></i> Tahun: <strong id="prModalYear"></strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="prToast" class="pr-toast">
    <i class="fas fa-info-circle"></i>
    <span id="prToastMsg">Prestasi berhasil dimuat</span>
</div>

<style>
/* ===== ROOT VARIABLES ===== */
:root {
    --pr-primary: #10B981;
    --pr-primary-dark: #047857;
    --pr-primary-light: #D1FAE5;
    --pr-dark: #0F172A;
    --pr-gray-50: #F8FAFC;
    --pr-gray-100: #F1F5F9;
    --pr-gray-200: #E2E8F0;
    --pr-gray-600: #475569;
    --pr-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --pr-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --pr-shadow-glow: 0 0 30px rgba(16,185,129,0.25);
    --pr-font-mono: 'Courier New', Courier, monospace;
}

/* ===== HERO ===== */
.pr-hero {
    background: linear-gradient(135deg, var(--pr-dark) 0%, var(--pr-primary-dark) 100%);
    padding: 160px 20px 100px; position: relative; overflow: hidden;
    min-height: 80vh; display: flex; align-items: center; justify-content: center;
}
.pr-network-canvas { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 1; opacity: 0.5; }
.pr-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }
.pr-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.4; animation: prFloat 15s infinite ease-in-out; }
.pr-orb-1 { width: 500px; height: 500px; background: var(--pr-primary); top: -150px; right: -150px; }
.pr-orb-2 { width: 350px; height: 350px; background: #34D399; bottom: -100px; left: -100px; animation-delay: -5s; }
.pr-grid-pattern {
    position: absolute; inset: 0;
    background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    mask-image: radial-gradient(ellipse at center, black 40%, transparent 70%);
}
@keyframes prFloat { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(30px, -30px); } }

.pr-hero-content { position: relative; z-index: 3; text-align: center; color: #fff; max-width: 900px; margin: 0 auto; }

.pr-hero-badge {
    display: inline-flex; align-items: center; gap: 10px; padding: 10px 22px;
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
    border-radius: 50px; font-size: .9rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    position: relative; font-weight: 600; letter-spacing: 0.5px;
}
.pr-badge-pulse {
    position: absolute; inset: 0; border-radius: 50px; border: 2px solid var(--pr-primary);
    animation: prBadgePulse 2s infinite;
}
@keyframes prBadgePulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.3); opacity: 0; } }

.pr-hero-title {
    font-size: clamp(2.5rem, 6vw, 4.5rem); font-weight: 800;
    margin-bottom: 20px; letter-spacing: -0.03em; line-height: 1.1;
}
.pr-text-gradient {
    background: linear-gradient(135deg, #F59E0B 0%, #34D399 50%, #06B6D4 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 200%;
    animation: prGradientShift 5s ease infinite;
}
@keyframes prGradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

.pr-hero-subtitle {
    font-size: 1.2rem; opacity: .9; max-width: 650px; margin: 0 auto 40px;
    line-height: 1.7; display: inline-block; min-height: 1.7em;
}
.pr-cursor { animation: prBlink 1s infinite; color: var(--pr-primary); font-weight: 300; }
@keyframes prBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

.pr-hero-stats { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
.pr-stat-pill {
    display: flex; align-items: center; gap: 14px; padding: 16px 28px;
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
    border-radius: 16px; backdrop-filter: blur(12px);
    transition: all 0.3s;
}
.pr-stat-pill:hover { background: rgba(255,255,255,.15); transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.2); }
.pr-stat-pill i { font-size: 1.8rem; color: #34D399; }
.pr-stat-num { display: block; font-size: 1.8rem; font-weight: 800; color: #fff; line-height: 1; font-family: 'Space Grotesk', sans-serif; }
.pr-stat-label { font-size: 0.8rem; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; display: block; }

.pr-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
    z-index: 3; display: flex; flex-direction: column; align-items: center;
    gap: 8px; color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.pr-mouse { width: 24px; height: 40px; border: 2px solid rgba(255,255,255,.3); border-radius: 12px; position: relative; }
.pr-wheel {
    width: 4px; height: 8px; background: var(--pr-primary); border-radius: 2px;
    position: absolute; top: 6px; left: 50%; transform: translateX(-50%);
    animation: prScroll 2s infinite;
}
@keyframes prScroll { 0% { opacity: 1; transform: translateX(-50%) translateY(0); } 100% { opacity: 0; transform: translateX(-50%) translateY(12px); } }

/* ===== MAIN SECTION ===== */
.pr-main-section { padding: 80px 20px 120px; background: var(--pr-gray-50); position: relative; }
body.gl-dark .pr-main-section { background: #0B1120; }

/* ===== FILTER CONSOLE ===== */
.pr-filter-console {
    background: #fff; border: 1px solid var(--pr-gray-200);
    border-radius: 20px; box-shadow: var(--pr-shadow-md);
    max-width: 1000px; margin: 0 auto 60px; overflow: hidden;
}
body.gl-dark .pr-filter-console { background: #1E293B; border-color: rgba(255,255,255,.08); }

.pr-console-header {
    background: var(--pr-dark); padding: 14px 20px;
    display: flex; align-items: center; gap: 12px;
    color: #94A3B8; font-family: var(--pr-font-mono); font-size: 0.85rem;
}
.pr-console-dots { display: flex; gap: 6px; }
.pr-console-dots .dot { width: 12px; height: 12px; border-radius: 50%; }
.pr-console-dots .red { background: #FF5F56; }
.pr-console-dots .yellow { background: #FFBD2E; }
.pr-console-dots .green { background: #27C93F; }
.pr-console-header i { color: var(--pr-primary); }
.pr-console-header span { color: #E2E8F0; }

.pr-console-body { padding: 28px; display: flex; flex-direction: column; gap: 20px; }
.pr-filter-group { display: flex; flex-direction: column; gap: 10px; }
.pr-filter-group label {
    font-size: 0.82rem; font-weight: 700; color: var(--pr-gray-600);
    display: flex; align-items: center; gap: 8px;
    text-transform: uppercase; letter-spacing: 0.5px;
    font-family: var(--pr-font-mono);
}
body.gl-dark .pr-filter-group label { color: #94A3B8; }
.pr-filter-group label i { color: var(--pr-primary); }

.pr-filter-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.pr-pill {
    padding: 8px 18px; border-radius: 50px; background: var(--pr-gray-100);
    border: 1.5px solid transparent; color: var(--pr-gray-600);
    cursor: pointer; font-weight: 600; font-size: 0.85rem;
    transition: all 0.3s; font-family: inherit;
    display: inline-flex; align-items: center; gap: 6px;
}
body.gl-dark .pr-pill { background: rgba(255,255,255,.05); color: #CBD5E1; }
.pr-pill:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.pr-pill.active {
    background: var(--pill-color, var(--pr-primary));
    color: #fff; border-color: var(--pill-color, var(--pr-primary));
    box-shadow: 0 4px 12px rgba(16,185,129,.3);
}
.pr-pill i { font-size: 0.75rem; }

.pr-btn-reset {
    padding: 10px 20px; background: rgba(239, 68, 68, 0.1);
    color: #DC2626; border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 8px; font-family: var(--pr-font-mono);
    font-size: 0.85rem; font-weight: 600; cursor: pointer;
    transition: all 0.3s; align-self: flex-start;
    display: inline-flex; align-items: center; gap: 8px;
}
.pr-btn-reset:hover { background: #DC2626; color: #fff; transform: translateY(-2px); }

/* ===== PRESTASI GRID ===== */
.pr-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 28px;
}
.pr-card {
    position: relative; background: #fff; border-radius: 24px;
    overflow: hidden; border: 1px solid var(--pr-gray-200);
    box-shadow: var(--pr-shadow-sm); cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    transform-style: preserve-3d; display: flex; flex-direction: column;
}
body.gl-dark .pr-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.pr-card::before {
    content: ''; position: absolute; inset: 0; border-radius: 24px; padding: 2px;
    background: conic-gradient(from 0deg, transparent 0%, var(--card-accent, var(--pr-primary)) 10%, transparent 30%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none; z-index: 5;
}
.pr-card:hover::before { opacity: 1; animation: prRotateBorder 3s linear infinite; }
@keyframes prRotateBorder { to { transform: rotate(360deg); } }

.pr-card:hover { transform: translateY(-10px); box-shadow: 0 24px 48px rgba(16,185,129,0.2); }
.pr-card.pr-featured { grid-column: span 1; }

.pr-card-glow {
    position: absolute; inset: -2px; border-radius: 24px;
    background: radial-gradient(circle at 50% 0%, var(--card-accent, var(--pr-primary)), transparent 60%);
    opacity: 0; transition: opacity 0.4s; pointer-events: none; z-index: 0;
    filter: blur(20px);
}
.pr-card:hover .pr-card-glow { opacity: 0.3; }

.pr-achievement-ribbon {
    position: absolute; top: 20px; left: -35px; z-index: 10;
    background: linear-gradient(135deg, #FDE047, #F59E0B);
    color: #78350F; padding: 6px 40px; font-size: 0.75rem;
    font-weight: 800; transform: rotate(-45deg);
    box-shadow: 0 4px 12px rgba(245,158,11,0.4);
    display: flex; align-items: center; gap: 6px;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.pr-achievement-ribbon i { font-size: 0.8rem; }

.pr-card-img { position: relative; width: 100%; height: 220px; overflow: hidden; background: var(--pr-gray-100); }
.pr-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s; }
.pr-card:hover .pr-card-img img { transform: scale(1.08); }
.pr-img-overlay {
    position: absolute; inset: 0; background: rgba(15,23,42,0.6);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; transition: opacity 0.3s; color: #fff; font-size: 2rem;
}
.pr-card:hover .pr-img-overlay { opacity: 1; }

.pr-placeholder {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: #fff; position: relative;
}
.pr-placeholder i { font-size: 4rem; opacity: 0.5; }
.pr-placeholder-year {
    position: absolute; bottom: 12px; right: 16px;
    font-family: 'Space Grotesk', sans-serif; font-size: 1.5rem;
    font-weight: 800; opacity: 0.8;
}

.pr-card-body { padding: 24px; flex: 1; display: flex; flex-direction: column; gap: 12px; position: relative; z-index: 2; }

.pr-card-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.pr-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 20px; font-size: 0.72rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;
}

.pr-card-title {
    font-size: 1.2rem; font-weight: 800; line-height: 1.3;
    color: var(--pr-dark); margin: 4px 0;
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
}
body.gl-dark .pr-card-title { color: #F1F5F9; }

.pr-card-team {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.85rem; color: var(--pr-gray-600);
    padding: 8px 12px; background: var(--pr-gray-50);
    border-radius: 10px; width: fit-content;
    border-left: 3px solid var(--card-accent, var(--pr-primary));
}
body.gl-dark .pr-card-team { background: rgba(255,255,255,.05); color: #CBD5E1; }
.pr-card-team i { color: var(--card-accent, var(--pr-primary)); }

.pr-card-desc {
    font-size: 0.9rem; color: var(--pr-gray-600); line-height: 1.6;
    display: -webkit-box; -webkit-line-clamp: 3;
    -webkit-box-orient: vertical; overflow: hidden;
}
body.gl-dark .pr-card-desc { color: #CBD5E1; }

.pr-card-footer {
    margin-top: auto; padding-top: 16px;
    border-top: 1px dashed var(--pr-gray-200);
    display: flex; justify-content: space-between; align-items: center;
}
body.gl-dark .pr-card-footer { border-color: rgba(255,255,255,.08); }

.pr-card-year {
    font-size: 0.85rem; color: var(--pr-gray-600); font-weight: 600;
    display: inline-flex; align-items: center; gap: 6px;
    font-family: var(--pr-font-mono);
}
body.gl-dark .pr-card-year { color: #94A3B8; }
.pr-card-year i { color: var(--card-accent, var(--pr-primary)); }

.pr-card-more {
    background: none; border: none; cursor: pointer;
    color: var(--card-accent, var(--pr-primary)); font-weight: 700;
    font-size: 0.85rem; display: inline-flex; align-items: center;
    gap: 6px; transition: gap 0.3s; font-family: inherit;
}
.pr-card-more:hover { gap: 10px; }

/* ===== EMPTY STATE ===== */
.pr-empty-state {
    text-align: center; padding: 80px 20px; background: #fff;
    border-radius: 24px; border: 2px dashed var(--pr-gray-200);
    max-width: 600px; margin: 0 auto;
}
body.gl-dark .pr-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }
.pr-empty-icon {
    position: relative; width: 100px; height: 100px; margin: 0 auto 24px;
    display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: var(--pr-primary);
}
.pr-empty-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--pr-primary); animation: prEmptyPulse 2s infinite;
}
@keyframes prEmptyPulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }
.pr-empty-state h3 { font-size: 1.5rem; margin-bottom: 12px; color: var(--pr-dark); font-weight: 800; }
body.gl-dark .pr-empty-state h3 { color: #F1F5F9; }
.pr-empty-state p { color: var(--pr-gray-600); margin-bottom: 24px; }
body.gl-dark .pr-empty-state p { color: #94A3B8; }
.pr-btn-primary {
    padding: 12px 24px; background: var(--pr-primary); color: #fff;
    border: none; border-radius: 10px; font-weight: 700;
    cursor: pointer; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 8px;
    font-family: inherit; font-size: 0.95rem;
}
.pr-btn-primary:hover { background: var(--pr-primary-dark); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,0.3); }

/* ===== MODAL ===== */
.pr-modal-backdrop {
    position: fixed; inset: 0; background: rgba(15,23,42,0.85);
    backdrop-filter: blur(12px); z-index: 9999;
    display: flex; align-items: center; justify-content: center;
    opacity: 0; visibility: hidden; transition: all 0.3s; padding: 20px;
}
.pr-modal-backdrop.active { opacity: 1; visibility: visible; }

.pr-modal-container {
    background: #fff; border-radius: 24px; width: 100%; max-width: 800px;
    max-height: 90vh; overflow: hidden;
    box-shadow: 0 30px 60px rgba(0,0,0,0.3);
    transform: scale(0.9) translateY(30px);
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex; flex-direction: column; position: relative;
}
body.gl-dark .pr-modal-container { background: #1E293B; }
.pr-modal-backdrop.active .pr-modal-container { transform: scale(1) translateY(0); }

.pr-modal-close {
    position: absolute; top: 20px; right: 20px; z-index: 10;
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,0.9); border: none;
    color: var(--pr-dark); cursor: pointer; font-size: 1.2rem;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s; backdrop-filter: blur(10px);
}
.pr-modal-close:hover { background: #EF4444; color: #fff; transform: rotate(90deg); }

.pr-modal-img {
    width: 100%; height: 300px; background: var(--pr-gray-100);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; position: relative;
}
.pr-modal-img img { width: 100%; height: 100%; object-fit: cover; }
.pr-modal-img.pr-modal-placeholder {
    background: linear-gradient(135deg, var(--pr-primary), var(--pr-primary-dark));
    color: #fff; font-size: 5rem;
}

.pr-modal-body { padding: 32px; overflow-y: auto; flex: 1; }
.pr-modal-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.pr-modal-title { font-size: 1.8rem; font-weight: 800; color: var(--pr-dark); margin-bottom: 16px; line-height: 1.3; }
body.gl-dark .pr-modal-title { color: #F1F5F9; }

.pr-modal-achievement {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 10px 20px; background: linear-gradient(135deg, #FDE047, #F59E0B);
    color: #78350F; border-radius: 50px; font-weight: 800;
    margin-bottom: 16px; box-shadow: 0 4px 12px rgba(245,158,11,0.3);
}
.pr-modal-achievement i { font-size: 1.2rem; }

.pr-modal-team {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px; background: var(--pr-gray-50);
    border-radius: 12px; margin-bottom: 20px;
    border-left: 4px solid var(--pr-primary);
    font-size: 0.95rem; color: var(--pr-gray-600);
}
body.gl-dark .pr-modal-team { background: rgba(255,255,255,.05); color: #CBD5E1; }
.pr-modal-team i { color: var(--pr-primary); font-size: 1.1rem; }

.pr-modal-desc {
    font-size: 1rem; line-height: 1.8; color: var(--pr-gray-600);
    margin-bottom: 24px; white-space: pre-wrap;
}
body.gl-dark .pr-modal-desc { color: #CBD5E1; }

.pr-modal-meta {
    padding-top: 20px; border-top: 1px solid var(--pr-gray-200);
    color: var(--pr-gray-600); font-size: 0.9rem;
}
body.gl-dark .pr-modal-meta { border-color: rgba(255,255,255,.08); color: #94A3B8; }
.pr-modal-meta i { color: var(--pr-primary); margin-right: 6px; }
.pr-modal-meta strong { color: var(--pr-dark); font-family: var(--pr-font-mono); }
body.gl-dark .pr-modal-meta strong { color: #F1F5F9; }

/* ===== TOAST ===== */
.pr-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--pr-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    display: flex; align-items: center; gap: 10px;
    font-size: 0.9rem; opacity: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10000; font-weight: 500;
}
body.gl-dark .pr-toast { background: #fff; color: var(--pr-dark); }
.pr-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.pr-toast i { color: var(--pr-primary); font-size: 1.2rem; }

/* ===== ANIMATIONS ===== */
.fade-up { opacity: 0; transform: translateY(40px); transition: opacity 0.8s ease, transform 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .pr-hero { padding: 120px 20px 60px; min-height: 70vh; }
    .pr-hero-stats { flex-direction: column; align-items: center; }
    .pr-stat-pill { width: 100%; max-width: 320px; justify-content: center; }
    .pr-grid { grid-template-columns: 1fr; }
    .pr-console-body { padding: 20px; }
    .pr-filter-pills { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 8px; }
    .pr-pill { white-space: nowrap; flex-shrink: 0; }
    .pr-modal-container { max-height: 95vh; }
    .pr-modal-img { height: 200px; }
    .pr-modal-body { padding: 24px 20px; }
    .pr-modal-title { font-size: 1.4rem; }
    .pr-card.pr-featured { grid-column: span 1; }
}
@media (max-width: 480px) {
    .pr-hero-title { font-size: 2rem; }
    .pr-card-img { height: 180px; }
}
</style>

<script>
// Data prestasi untuk modal (server-rendered)
const prPrestasiData = <?= json_encode(array_map(function($p) use ($tingkatStyle, $kategoriStyle) {
    $tingkat = $tingkatStyle[$p['tingkat']] ?? $tingkatStyle['internal'];
    $kategori = $kategoriStyle[$p['kategori']] ?? $kategoriStyle['kompetisi'];
    return [
        'judul' => $p['judul'],
        'pencapaian' => $p['pencapaian'] ?? '',
        'kategori_label' => $kategori[0],
        'kategori_icon' => $kategori[1],
        'kategori_color' => $kategori[2],
        'tingkat_label' => $tingkat[0],
        'tingkat_icon' => $tingkat[1],
        'tingkat_color' => $tingkat[2],
        'tingkat_bg' => $tingkat[3],
        'nama_tim' => $p['nama_tim'] ?? '',
        'deskripsi' => $p['deskripsi'] ?? '',
        'tahun' => $p['tahun'],
        'gambar' => $p['gambar'] ? 'uploads/' . $p['gambar'] : ''
    ];
}, $prestasiList), JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>;

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // 1. Network Canvas Animation
    const canvas = document.getElementById('prNetworkCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let width, height, particles = [];
        const particleCount = window.innerWidth < 768 ? 30 : 70;

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
                this.vx = (Math.random() - 0.5) * 0.6;
                this.vy = (Math.random() - 0.5) * 0.6;
                this.size = Math.random() * 2.5 + 1;
                this.hue = Math.random() * 60 + 120; // Green-yellow range
            }
            update() {
                this.x += this.vx; this.y += this.vy;
                if (this.x < 0 || this.x > width) this.vx *= -1;
                if (this.y < 0 || this.y > height) this.vy *= -1;
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = `hsla(${this.hue}, 70%, 60%, 0.7)`;
                ctx.shadowBlur = 12;
                ctx.shadowColor = `hsla(${this.hue}, 70%, 60%, 0.5)`;
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
                    if (distance < 140) {
                        const opacity = (1 - distance / 140) * 0.3;
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
    const textElement = document.getElementById('prTypingText');
    if (textElement) {
        const phrases = [
            "Pencapaian mahasiswa dan dosen di berbagai kompetisi nasional & internasional.",
            "Jejak prestasi yang menginspirasi generasi inovator teknologi.",
            "Dari ruang kelas menuju panggung juara global."
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
    const counters = document.querySelectorAll('.pr-stat-num[data-count]');
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

    // 4. 3D Tilt Effect
    const cards = document.querySelectorAll('.pr-tilt');
    cards.forEach(card => {
        if (!card) return;
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = ((y - centerY) / centerY) * -4;
            const rotateY = ((x - centerX) / centerX) * 4;
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-10px)`;
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0)';
        });
    });

    // 5. Instant Client-Side Filtering
    const pills = document.querySelectorAll('.pr-pill');
    const gridCards = document.querySelectorAll('.pr-card');
    const emptyState = document.querySelector('.pr-empty-state');
    
    let currentTingkat = '<?= addslashes($filterTingkat) ?>' || 'all';
    let currentTahun = '<?= $filterTahun ?>' || 'all';

    function applyFilters() {
        let visibleCount = 0;
        
        gridCards.forEach(card => {
            if (!card) return;
            const cardTingkat = card.getAttribute('data-tingkat') || '';
            const cardTahun = card.getAttribute('data-tahun') || '';
            
            const matchTingkat = (currentTingkat === 'all' || cardTingkat === currentTingkat);
            const matchTahun = (currentTahun === 'all' || cardTahun === currentTahun);
            
            if (matchTingkat && matchTahun) {
                card.classList.remove('pr-hidden');
                card.style.display = '';
                visibleCount++;
            } else {
                card.classList.add('pr-hidden');
                card.style.display = 'none';
            }
        });
        
        if (emptyState) {
            if (visibleCount === 0) {
                emptyState.style.display = 'block';
                emptyState.classList.add('visible');
            } else {
                emptyState.style.display = 'none';
            }
        }
        
        // Update URL tanpa reload
        const url = new URL(window.location);
        if (currentTingkat !== 'all') url.searchParams.set('tingkat', currentTingkat);
        else url.searchParams.delete('tingkat');
        if (currentTahun !== 'all') url.searchParams.set('tahun', currentTahun);
        else url.searchParams.delete('tahun');
        window.history.replaceState({}, '', url);
    }

    pills.forEach(pill => {
        pill.addEventListener('click', function() {
            const tingkat = this.getAttribute('data-filter-tingkat');
            const tahun = this.getAttribute('data-filter-tahun');
            
            if (tingkat) {
                currentTingkat = tingkat;
                document.querySelectorAll('[data-filter-tingkat]').forEach(p => p.classList.remove('active'));
            }
            if (tahun) {
                currentTahun = tahun;
                document.querySelectorAll('[data-filter-tahun]').forEach(p => p.classList.remove('active'));
            }
            this.classList.add('active');
            
            applyFilters();
        });
    });

    window.prResetFilters = function() {
        currentTingkat = 'all';
        currentTahun = 'all';
        pills.forEach(p => p.classList.remove('active'));
        const allPills = document.querySelectorAll('.pr-pill[data-filter-tingkat="all"], .pr-pill[data-filter-tahun="all"]');
        allPills.forEach(p => p.classList.add('active'));
        applyFilters();
    };

    // 6. Modal Functions
    window.prOpenModal = function(index) {
        const data = prPrestasiData[index];
        if (!data) return;
        
        const modal = document.getElementById('prModal');
        if (!modal) return;
        
        // Set image
        const imgContainer = document.getElementById('prModalImg');
        if (data.gambar) {
            imgContainer.innerHTML = `<img src="${data.gambar}" alt="${data.judul}">`;
            imgContainer.className = 'pr-modal-img';
        } else {
            imgContainer.innerHTML = `<i class="fas ${data.kategori_icon}"></i>`;
            imgContainer.className = 'pr-modal-img pr-modal-placeholder';
            imgContainer.style.background = `linear-gradient(135deg, ${data.kategori_color}, ${data.kategori_color}dd)`;
        }
        
        // Set badges
        const badges = document.getElementById('prModalBadges');
        badges.innerHTML = `
            <span class="pr-badge" style="background: ${data.tingkat_bg}; color: ${data.tingkat_color};">
                <i class="fas ${data.tingkat_icon}"></i> ${data.tingkat_label}
            </span>
            <span class="pr-badge" style="background: ${data.kategori_color}15; color: ${data.kategori_color};">
                <i class="fas ${data.kategori_icon}"></i> ${data.kategori_label}
            </span>
        `;
        
        // Set title
        document.getElementById('prModalTitle').textContent = data.judul;
        
        // Achievement
        const achEl = document.getElementById('prModalAchievement');
        if (data.pencapaian) {
            document.getElementById('prModalAchievementText').textContent = data.pencapaian;
            achEl.style.display = 'inline-flex';
        } else {
            achEl.style.display = 'none';
        }
        
        // Team
        const teamEl = document.getElementById('prModalTeam');
        if (data.nama_tim) {
            document.getElementById('prModalTeamText').textContent = data.nama_tim;
            teamEl.style.display = 'flex';
        } else {
            teamEl.style.display = 'none';
        }
        
        // Description
        document.getElementById('prModalDesc').textContent = data.deskripsi || 'Tidak ada deskripsi lengkap untuk prestasi ini.';
        
        // Year
        document.getElementById('prModalYear').textContent = data.tahun;
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.prCloseModal = function() {
        const modal = document.getElementById('prModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    const modalBackdrop = document.getElementById('prModal');
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', function(e) {
            if (e.target === this) window.prCloseModal();
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') window.prCloseModal();
    });

    // 7. Scroll Reveal
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.05, rootMargin: '0px 0px -30px 0px' });
    
    document.querySelectorAll('.fade-up').forEach(el => {
        if (el) observer.observe(el);
    });

    // 8. Parallax Orbs
    const hero = document.querySelector('.pr-hero');
    if (hero && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const orbs = hero.querySelectorAll('.pr-orb');
            orbs.forEach((orb, index) => {
                const speed = (index + 1) * 0.3;
                orb.style.transform = `translateY(${scrolled * speed}px)`;
            });
        }, { passive: true });
    }

    // 9. Toast Notification
    window.prShowToast = function(message) {
        const toast = document.getElementById('prToast');
        if (!toast) return;
        const msgEl = document.getElementById('prToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // Show welcome toast
    setTimeout(() => {
        if (prPrestasiData.length > 0) {
            window.prShowToast(`✨ ${prPrestasiData.length} prestasi berhasil dimuat`);
        }
    }, 1500);

    // 10. Console Easter Egg
    console.log('%c╔════════════════════════════════════════╗', 'color:#F59E0B;font-weight:bold');
    console.log('%c║   PRESTASI - Informatika Pro           ║', 'color:#F59E0B;font-weight:bold;font-size:14px');
    console.log('%c╚════════════════════════════════════════╝', 'color:#F59E0B;font-weight:bold');
    console.log('%c🏆 ' + prPrestasiData.length + ' Prestasi Terdaftar', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>