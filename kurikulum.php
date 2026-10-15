<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Struktur Kurikulum';
$pageDesc = 'Peta perjalanan akademik 8 semester Program Studi Informatika yang terintegrasi dengan kebutuhan industri.';

// Fetch dynamic curriculum data - LOGIKA ASLI DIPERTAHANKAN
try {
    $db = getDB();
    $stmt = $db->query("SELECT semester, kode, nama, sks FROM mata_kuliah ORDER BY semester ASC, urutan ASC");
    $coursesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $courses = [];
    $semesterSks = [];
    foreach ($coursesRaw as $row) {
        $courses[$row['semester']][] = $row;
        $semesterSks[$row['semester']] = ($semesterSks[$row['semester']] ?? 0) + $row['sks'];
    }
    
    $totalSks = array_sum($semesterSks) ?: 144;
} catch (Throwable $e) {
    $courses = [];
    $totalSks = 144;
}

// Fallback data jika database kosong
if (empty($courses)) {
    $courses = [
        1 => [['kode'=>'IF101','nama'=>'Dasar Pemrograman','sks'=>3], ['kode'=>'IF102','nama'=>'Matematika Diskrit','sks'=>3], ['kode'=>'IF103','nama'=>'Pengantar Informatika','sks'=>3]],
        2 => [['kode'=>'IF201','nama'=>'Algoritma & Struktur Data','sks'=>3], ['kode'=>'IF202','nama'=>'Basis Data','sks'=>3]],
        3 => [['kode'=>'IF301','nama'=>'Pemrograman Web','sks'=>3], ['kode'=>'IF302','nama'=>'Jaringan Komputer','sks'=>3]],
        4 => [['kode'=>'IF401','nama'=>'Kecerdasan Buatan','sks'=>3], ['kode'=>'IF402','nama'=>'Machine Learning','sks'=>3]],
        5 => [['kode'=>'IF501','nama'=>'Deep Learning','sks'=>3], ['kode'=>'IF502','nama'=>'Cloud Computing','sks'=>3]],
        6 => [['kode'=>'IF601','nama'=>'Magang / MSIB','sks'=>20]],
        7 => [['kode'=>'IF701','nama'=>'Skripsi / Tugas Akhir','sks'=>6]],
        8 => [['kode'=>'IF801','nama'=>'Skripsi (Lanjutan)','sks'=>6]]
    ];
    foreach ($courses as $sem => $list) {
        $semesterSks[$sem] = array_sum(array_column($list, 'sks'));
    }
}

// Helper: icon berdasarkan semester untuk variasi visual
$semesterIcons = [
    1 => 'fa-rocket', 2 => 'fa-code', 3 => 'fa-network-wired', 4 => 'fa-brain',
    5 => 'fa-cloud', 6 => 'fa-briefcase', 7 => 'fa-pen-fancy', 8 => 'fa-graduation-cap'
];
$semesterColors = [
    1 => '#10B981', 2 => '#3B82F6', 3 => '#8B5CF6', 4 => '#F59E0B',
    5 => '#06B6D4', 6 => '#EC4899', 7 => '#EF4444', 8 => '#14B8A6'
];
?>

<!-- ===== HERO SECTION WITH CANVAS ===== -->
<section class="kur-hero">
    <canvas id="kurNetworkCanvas" class="kur-network-canvas"></canvas>
    <div class="kur-hero-bg">
        <div class="kur-orb kur-orb-1"></div>
        <div class="kur-orb kur-orb-2"></div>
        <div class="kur-grid-pattern"></div>
    </div>
    
    <div class="container kur-hero-content">
        <span class="kur-hero-badge fade-up">
            <span class="kur-badge-pulse"></span>
            <i class="fas fa-book-open"></i> Kurikulum 2024
        </span>
        
        <h1 class="kur-hero-title fade-up">
            Struktur <span class="kur-text-gradient">Kurikulum</span>
        </h1>
        
        <p class="kur-hero-subtitle fade-up">
            <span id="kurTypingText"></span><span class="kur-cursor">|</span>
        </p>
        
        <div class="kur-hero-stats fade-up">
            <div class="kur-stat-pill">
                <i class="fas fa-book"></i>
                <div>
                    <span class="kur-stat-num" data-count="<?= $totalSks ?>">0</span>
                    <span class="kur-stat-label">Total SKS</span>
                </div>
            </div>
            <div class="kur-stat-pill">
                <i class="fas fa-calendar-alt"></i>
                <div>
                    <span class="kur-stat-num">8</span>
                    <span class="kur-stat-label">Semester</span>
                </div>
            </div>
            <div class="kur-stat-pill">
                <i class="fas fa-briefcase"></i>
                <div>
                    <span class="kur-stat-num">1-2</span>
                    <span class="kur-stat-label">Magang MSIB</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="kur-scroll-indicator">
        <span>Explore Curriculum</span>
        <div class="kur-mouse"><div class="kur-wheel"></div></div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<section class="kur-main-section">
    <div class="container">
        <div class="kur-layout">
            
            <!-- Sticky Sidebar -->
            <aside class="kur-sidebar fade-up">
                
                <!-- Program Summary Card -->
                <div class="kur-info-card kur-tilt">
                    <div class="kur-card-glow"></div>
                    <h3><i class="fas fa-info-circle"></i> Ringkasan Program</h3>
                    <ul class="kur-stats">
                        <li><span>Total SKS</span><strong><?= $totalSks ?> SKS</strong></li>
                        <li><span>Durasi</span><strong>8 Semester (4 Tahun)</strong></li>
                        <li><span>Magang</span><strong>1-2 Semester (MSIB)</strong></li>
                        <li><span>Skripsi</span><strong>6 SKS</strong></li>
                        <li><span>Level KKNI</span><strong>Level 6</strong></li>
                    </ul>
                    <a href="#" class="kur-btn-download" onclick="alert('Fitur download PDF kurikulum lengkap akan segera tersedia!'); return false;">
                        <i class="fas fa-file-pdf"></i> Download Kurikulum (PDF)
                    </a>
                </div>

                <!-- Progress Card -->
                <div class="kur-info-card kur-progress-card">
                    <h4><i class="fas fa-chart-line"></i> Progress SKS</h4>
                    <div class="kur-progress-visual">
                        <?php for ($i = 1; $i <= 8; $i++): 
                            $sks = $semesterSks[$i] ?? 0;
                            $percent = ($sks / 30) * 100; // max ~30 SKS per semester
                        ?>
                        <div class="kur-progress-bar-wrap">
                            <span class="kur-progress-label">S<?= $i ?></span>
                            <div class="kur-progress-track">
                                <div class="kur-progress-fill" 
                                     style="width: <?= min(100, $percent) ?>%; background: <?= $semesterColors[$i] ?>;"
                                     data-width="<?= min(100, $percent) ?>"></div>
                            </div>
                            <span class="kur-progress-value"><?= $sks ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Did You Know Card -->
                <div class="kur-info-card kur-accent kur-tilt">
                    <div class="kur-accent-icon"><i class="fas fa-lightbulb"></i></div>
                    <h4>Tahukah Anda?</h4>
                    <p>Kurikulum kami telah disesuaikan dengan standar <strong>KKNI Level 6</strong> dan referensi <strong>ACM/IEEE Computing Curricula</strong> yang diperbarui setiap 2 tahun.</p>
                </div>

                <!-- Quick Links -->
                <div class="kur-quick-links">
                    <a href="#panel-1" class="kur-quick-link" onclick="jumpToSemester(1)">
                        <i class="fas fa-rocket"></i> Semester Awal
                    </a>
                    <a href="#panel-6" class="kur-quick-link" onclick="jumpToSemester(6)">
                        <i class="fas fa-briefcase"></i> Magang MSIB
                    </a>
                    <a href="#panel-7" class="kur-quick-link" onclick="jumpToSemester(7)">
                        <i class="fas fa-pen-fancy"></i> Tugas Akhir
                    </a>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="kur-content fade-up">
                
                <!-- Search Bar -->
                <div class="kur-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="kurSearchInput" placeholder="Cari mata kuliah (contoh: 'Web', 'AI', 'Data', 'Cloud')...">
                    <span class="kur-search-clear" id="kurSearchClear"><i class="fas fa-times"></i></span>
                    <span class="kur-search-hint">
                        <kbd>Ctrl</kbd> + <kbd>K</kbd>
                    </span>
                </div>

                <!-- Tab Navigation (Sticky) -->
                <div class="kur-tab-nav-sticky" id="kurTabSticky">
                    <div class="kur-tab-nav" id="kurTabNav">
                        <?php for ($i = 1; $i <= 8; $i++): 
                            $sks = $semesterSks[$i] ?? 0;
                            $icon = $semesterIcons[$i] ?? 'fa-book';
                            $color = $semesterColors[$i] ?? '#10B981';
                        ?>
                        <button class="kur-tab-btn <?= $i === 1 ? 'active' : '' ?>" 
                                data-target="<?= $i ?>"
                                style="--tab-color: <?= $color ?>;">
                            <i class="fas <?= $icon ?>"></i>
                            <span>Semester</span>
                            <strong><?= $i ?></strong>
                            <small><?= $sks ?> SKS</small>
                        </button>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Tab Panels -->
                <div class="kur-tabs" id="kurTabs">
                    <?php foreach ($courses as $num => $mkList): 
                        $icon = $semesterIcons[$num] ?? 'fa-book';
                        $color = $semesterColors[$num] ?? '#10B981';
                    ?>
                    <div class="kur-tab-panel <?= $num === 1 ? 'active' : '' ?>" data-tab="<?= $num ?>" id="panel-<?= $num ?>">
                        <div class="kur-tab-header">
                            <div class="kur-tab-icon" style="background: <?= $color ?>20; color: <?= $color ?>;">
                                <i class="fas <?= $icon ?>"></i>
                            </div>
                            <div class="kur-tab-info">
                                <h2>Semester <?= $num ?></h2>
                                <p><?= count($mkList) ?> Mata Kuliah • Total <?= $semesterSks[$num] ?? 0 ?> SKS</p>
                            </div>
                            <div class="kur-tab-progress">
                                <div class="kur-circular-progress" style="--progress: <?= ($semesterSks[$num] ?? 0) / 30 * 100 ?>; --progress-color: <?= $color ?>;">
                                    <span><?= $semesterSks[$num] ?? 0 ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="kur-mk-grid">
                            <?php foreach ($mkList as $index => $matkul): 
                                $sks = (int)$matkul['sks'];
                            ?>
                            <div class="kur-mk-card kur-tilt" 
                                 style="animation-delay: <?= $index * 0.05 ?>s; --mk-color: <?= $color ?>;"
                                 data-name="<?= strtolower(clean($matkul['nama'])) ?>" 
                                 data-code="<?= strtolower(clean($matkul['kode'])) ?>"
                                 onclick="kurShowMKDetail('<?= addslashes(clean($matkul['kode'])) ?>', '<?= addslashes(clean($matkul['nama'])) ?>', <?= $sks ?>, <?= $num ?>)">
                                <div class="kur-mk-card-glow"></div>
                                <div class="kur-mk-header">
                                    <span class="kur-mk-code"><?= clean($matkul['kode']) ?></span>
                                    <span class="kur-mk-sks-badge"><?= $sks ?> SKS</span>
                                </div>
                                <h3 class="kur-mk-name"><?= clean($matkul['nama']) ?></h3>
                                <div class="kur-mk-footer">
                                    <div class="kur-mk-dots">
                                        <?php for ($d = 0; $d < min($sks, 6); $d++): ?>
                                            <span class="kur-mk-dot"></span>
                                        <?php endfor; ?>
                                    </div>
                                    <button class="kur-mk-detail-btn">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- No Results State -->
                <div class="kur-no-results" id="kurNoResults" style="display: none;">
                    <div class="kur-no-results-icon">
                        <i class="fas fa-search"></i>
                        <div class="kur-no-results-pulse"></div>
                    </div>
                    <h3>Mata Kuliah Tidak Ditemukan</h3>
                    <p>Coba gunakan kata kunci lain seperti "Pemrograman", "Jaringan", "Data", atau "Cloud".</p>
                    <button class="kur-btn-reset" onclick="document.getElementById('kurSearchClear').click()">
                        <i class="fas fa-redo"></i> Reset Pencarian
                    </button>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- ===== MK DETAIL MODAL ===== -->
<div class="kur-modal-backdrop" id="kurModal">
    <div class="kur-modal-container">
        <button class="kur-modal-close" onclick="kurCloseModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="kur-modal-content">
            <div class="kur-modal-header">
                <div class="kur-modal-icon" id="kurModalIcon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="kur-modal-title-group">
                    <span class="kur-modal-code" id="kurModalCode">IF101</span>
                    <h3 id="kurModalName">Nama Mata Kuliah</h3>
                </div>
            </div>
            
            <div class="kur-modal-body">
                <div class="kur-modal-stats">
                    <div class="kur-modal-stat">
                        <i class="fas fa-graduation-cap"></i>
                        <div>
                            <span>Semester</span>
                            <strong id="kurModalSemester">1</strong>
                        </div>
                    </div>
                    <div class="kur-modal-stat">
                        <i class="fas fa-clock"></i>
                        <div>
                            <span>Beban SKS</span>
                            <strong id="kurModalSks">3 SKS</strong>
                        </div>
                    </div>
                </div>
                
                <div class="kur-modal-info">
                    <h4><i class="fas fa-info-circle"></i> Deskripsi</h4>
                    <p>Mata kuliah ini dirancang untuk memberikan pemahaman mendalam tentang konsep fundamental dan aplikasi praktis di bidang terkait. Mahasiswa akan mempelajari teori, praktik, dan studi kasus yang relevan dengan kebutuhan industri saat ini.</p>
                </div>
                
                <div class="kur-modal-info">
                    <h4><i class="fas fa-bullseye"></i> Capaian Pembelajaran</h4>
                    <ul class="kur-modal-outcomes">
                        <li><i class="fas fa-check-circle"></i> Memahami konsep fundamental</li>
                        <li><i class="fas fa-check-circle"></i> Mampu mengimplementasikan solusi</li>
                        <li><i class="fas fa-check-circle"></i> Menganalisis kasus nyata industri</li>
                        <li><i class="fas fa-check-circle"></i> Berkolaborasi dalam proyek tim</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="kurToast" class="kur-toast">
    <i class="fas fa-check-circle"></i>
    <span id="kurToastMsg">Berhasil!</span>
</div>

<!-- ============================================================
     SPECTACULAR STYLES (prefix: kur-)
============================================================ -->
<style>
:root {
    --kur-primary: #10B981;
    --kur-primary-dark: #047857;
    --kur-primary-light: #D1FAE5;
    --kur-dark: #0F172A;
    --kur-gray-50: #F8FAFC;
    --kur-gray-100: #F1F5F9;
    --kur-gray-200: #E2E8F0;
    --kur-gray-600: #475569;
    --kur-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --kur-shadow-lg: 0 20px 40px rgba(15,23,42,.1);
    --kur-shadow-glow: 0 0 30px rgba(16,185,129,0.25);
    --kur-font-mono: 'JetBrains Mono', 'Courier New', monospace;
}

body.gl-dark {
    --kur-dark: #F1F5F9;
    --kur-gray-50: #0B1120;
    --kur-gray-100: rgba(255,255,255,.08);
    --kur-gray-200: rgba(255,255,255,.1);
    --kur-gray-600: #94A3B8;
}

/* ===== GLOBAL ===== */
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--kur-gray-50); }
::-webkit-scrollbar-thumb { background: linear-gradient(180deg, var(--kur-primary), var(--kur-primary-dark)); border-radius: 5px; }

/* ===== HERO ===== */
.kur-hero {
    background: linear-gradient(135deg, #0F172A 0%, #047857 100%);
    padding: 160px 20px 100px; position: relative; overflow: hidden;
    min-height: 85vh; display: flex; align-items: center; justify-content: center;
}
.kur-network-canvas { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 1; opacity: 0.5; }
.kur-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }
.kur-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.4; animation: kurFloat 15s infinite ease-in-out; }
.kur-orb-1 { width: 500px; height: 500px; background: var(--kur-primary); top: -150px; right: -150px; }
.kur-orb-2 { width: 350px; height: 350px; background: #34D399; bottom: -100px; left: -100px; animation-delay: -5s; }
.kur-grid-pattern {
    position: absolute; inset: 0;
    background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    mask-image: radial-gradient(ellipse at center, black 40%, transparent 70%);
}
@keyframes kurFloat { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(30px, -30px); } }

.kur-hero-content { position: relative; z-index: 3; text-align: center; color: #fff; max-width: 900px; margin: 0 auto; }

.kur-hero-badge {
    display: inline-flex; align-items: center; gap: 10px; padding: 10px 22px;
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
    border-radius: 50px; font-size: .9rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    position: relative; font-weight: 600; letter-spacing: 0.5px;
}
.kur-badge-pulse {
    position: absolute; inset: 0; border-radius: 50px; border: 2px solid var(--kur-primary);
    animation: kurBadgePulse 2s infinite;
}
@keyframes kurBadgePulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.3); opacity: 0; } }

.kur-hero-title {
    font-size: clamp(2.5rem, 6vw, 4.5rem); font-weight: 800;
    margin-bottom: 20px; letter-spacing: -0.03em; line-height: 1.1;
}
.kur-text-gradient {
    background: linear-gradient(135deg, #34D399 0%, #06B6D4 50%, #F59E0B 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 200%;
    animation: kurGradientShift 5s ease infinite;
}
@keyframes kurGradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

.kur-hero-subtitle {
    font-size: 1.2rem; opacity: .9; max-width: 650px; margin: 0 auto 40px;
    line-height: 1.7; display: inline-block; min-height: 1.7em;
}
.kur-cursor { animation: kurBlink 1s infinite; color: var(--kur-primary); font-weight: 300; }
@keyframes kurBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

.kur-hero-stats { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
.kur-stat-pill {
    display: flex; align-items: center; gap: 14px; padding: 16px 28px;
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
    border-radius: 16px; backdrop-filter: blur(12px); transition: all 0.3s;
}
.kur-stat-pill:hover { background: rgba(255,255,255,.15); transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.2); }
.kur-stat-pill i { font-size: 1.8rem; color: #34D399; }
.kur-stat-num { display: block; font-size: 1.8rem; font-weight: 800; color: #fff; line-height: 1; font-family: 'Space Grotesk', sans-serif; }
.kur-stat-label { font-size: 0.8rem; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; display: block; }

.kur-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
    z-index: 3; display: flex; flex-direction: column; align-items: center;
    gap: 8px; color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.kur-mouse { width: 24px; height: 40px; border: 2px solid rgba(255,255,255,.3); border-radius: 12px; position: relative; }
.kur-wheel {
    width: 4px; height: 8px; background: var(--kur-primary); border-radius: 2px;
    position: absolute; top: 6px; left: 50%; transform: translateX(-50%);
    animation: kurScroll 2s infinite;
}
@keyframes kurScroll { 0% { opacity: 1; transform: translateX(-50%) translateY(0); } 100% { opacity: 0; transform: translateX(-50%) translateY(12px); } }

/* ===== MAIN SECTION ===== */
.kur-main-section { padding: 80px 20px 120px; background: var(--kur-gray-50); position: relative; }

.kur-layout { display: grid; grid-template-columns: 340px 1fr; gap: 40px; align-items: flex-start; }

/* ===== SIDEBAR ===== */
.kur-sidebar { position: sticky; top: 100px; display: flex; flex-direction: column; gap: 20px; }

.kur-info-card {
    background: #fff; border-radius: 20px; padding: 24px;
    box-shadow: var(--kur-shadow-sm); border: 1px solid var(--kur-gray-100);
    position: relative; overflow: hidden; transform-style: preserve-3d;
}
body.gl-dark .kur-info-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.kur-card-glow {
    position: absolute; top: -50%; right: -50%; width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(16,185,129,0.1), transparent 70%);
    opacity: 0; transition: opacity 0.4s; pointer-events: none;
}
.kur-info-card:hover .kur-card-glow { opacity: 1; }

.kur-info-card h3 {
    font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
    color: var(--kur-primary); padding-bottom: 12px; border-bottom: 1px solid var(--kur-gray-100);
}
body.gl-dark .kur-info-card h3 { border-color: rgba(255,255,255,.08); }

.kur-stats { list-style: none; padding: 0; margin: 0 0 20px; }
.kur-stats li {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0; border-bottom: 1px dashed var(--kur-gray-100);
    font-size: .9rem; color: var(--kur-gray-600);
}
body.gl-dark .kur-stats li { border-color: rgba(255,255,255,.08); }
.kur-stats li:last-child { border-bottom: none; }
.kur-stats li strong { color: var(--kur-dark); font-weight: 700; }
body.gl-dark .kur-stats li strong { color: #F1F5F9; }

.kur-btn-download {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 12px; background: linear-gradient(135deg, var(--kur-primary), var(--kur-primary-dark));
    color: #fff; border-radius: 12px; text-decoration: none;
    font-weight: 600; transition: all .3s; font-size: 0.95rem;
    box-shadow: 0 4px 12px rgba(16,185,129,.25);
}
.kur-btn-download:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,.4); }

/* Progress Card */
.kur-progress-card { padding: 20px; }
.kur-progress-card h4 {
    font-size: 1rem; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
    color: var(--kur-dark);
}
body.gl-dark .kur-progress-card h4 { color: #F1F5F9; }

.kur-progress-visual { display: flex; flex-direction: column; gap: 8px; }
.kur-progress-bar-wrap { display: flex; align-items: center; gap: 8px; }
.kur-progress-label {
    font-family: var(--kur-font-mono); font-size: 0.75rem; font-weight: 700;
    color: var(--kur-gray-600); min-width: 24px;
}
body.gl-dark .kur-progress-label { color: #94A3B8; }
.kur-progress-track {
    flex: 1; height: 6px; background: var(--kur-gray-100);
    border-radius: 3px; overflow: hidden;
}
body.gl-dark .kur-progress-track { background: rgba(255,255,255,.08); }
.kur-progress-fill {
    height: 100%; border-radius: 3px;
    transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
    width: 0;
}
.kur-progress-fill.animated { width: var(--target-width, 0%); }
.kur-progress-value {
    font-family: var(--kur-font-mono); font-size: 0.75rem;
    font-weight: 700; color: var(--kur-dark); min-width: 20px; text-align: right;
}
body.gl-dark .kur-progress-value { color: #F1F5F9; }

/* Accent Card */
.kur-accent {
    background: linear-gradient(135deg, rgba(16,185,129,.1), rgba(4,120,87,.05));
    border-color: rgba(16,185,129,.2);
}
body.gl-dark .kur-accent { background: linear-gradient(135deg, rgba(52,211,153,.1), rgba(4,120,87,.05)); }
.kur-accent-icon {
    position: absolute; top: -20px; right: -20px;
    width: 80px; height: 80px; background: var(--kur-primary);
    color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 2rem; opacity: 0.1; transform: rotate(15deg);
}
.kur-accent h4 {
    font-size: 1rem; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
    color: var(--kur-primary-dark);
}
body.gl-dark .kur-accent h4 { color: #34D399; }
.kur-accent p { font-size: .85rem; color: var(--kur-gray-600); margin: 0; line-height: 1.6; }
body.gl-dark .kur-accent p { color: #94A3B8; }
.kur-accent strong { color: var(--kur-primary-dark); font-weight: 700; }
body.gl-dark .kur-accent strong { color: #34D399; }

/* Quick Links */
.kur-quick-links { display: flex; flex-direction: column; gap: 8px; }
.kur-quick-link {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; background: #fff; border: 1px solid var(--kur-gray-100);
    border-radius: 12px; text-decoration: none; color: var(--kur-gray-600);
    transition: all 0.3s; font-size: 0.9rem; font-weight: 500;
}
body.gl-dark .kur-quick-link { background: #1E293B; border-color: rgba(255,255,255,.08); color: #CBD5E1; }
.kur-quick-link:hover {
    background: var(--kur-primary); color: #fff; border-color: var(--kur-primary);
    transform: translateX(4px); box-shadow: 0 4px 12px rgba(16,185,129,.3);
}
.kur-quick-link i { color: var(--kur-primary); transition: color 0.3s; }
.kur-quick-link:hover i { color: #fff; }

/* ===== MAIN CONTENT ===== */
.kur-content {
    background: #fff; border-radius: 24px; padding: 32px;
    box-shadow: var(--kur-shadow-sm); border: 1px solid var(--kur-gray-100);
    min-height: 600px;
}
body.gl-dark .kur-content { background: #1E293B; border-color: rgba(255,255,255,.08); }

/* Search */
.kur-search-wrap {
    position: relative; margin-bottom: 28px; display: flex; align-items: center;
}
.kur-search-wrap i {
    position: absolute; left: 18px; top: 50%; transform: translateY(-50%);
    color: var(--kur-gray-600); font-size: 1rem; z-index: 2;
}
.kur-search-wrap input {
    width: 100%; padding: 16px 120px 16px 48px; border: 1.5px solid var(--kur-gray-200);
    border-radius: 14px; font-size: 1rem; font-family: inherit; transition: all .3s;
    background: var(--kur-gray-50);
}
body.gl-dark .kur-search-wrap input { background: rgba(255,255,255,.03); border-color: rgba(255,255,255,.1); color: #fff; }
.kur-search-wrap input:focus {
    outline: none; border-color: var(--kur-primary);
    box-shadow: 0 0 0 4px rgba(16,185,129,.15); background: #fff;
}
body.gl-dark .kur-search-wrap input:focus { background: rgba(0,0,0,0.2); }

.kur-search-clear {
    position: absolute; right: 120px; top: 50%; transform: translateY(-50%);
    color: var(--kur-gray-600); cursor: pointer; display: none;
    transition: color .3s; padding: 4px; z-index: 2;
}
.kur-search-clear:hover { color: #EF4444; }

.kur-search-hint {
    position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
    display: flex; gap: 4px; align-items: center; color: var(--kur-gray-600);
    font-size: 0.8rem;
}
.kur-search-hint kbd {
    background: var(--kur-gray-100); padding: 2px 8px; border-radius: 4px;
    font-family: var(--kur-font-mono); font-size: 0.75rem;
    border: 1px solid var(--kur-gray-200);
}
body.gl-dark .kur-search-hint kbd { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.1); color: #CBD5E1; }

/* Tab Navigation */
.kur-tab-nav-sticky {
    position: sticky; top: 80px; z-index: 10;
    background: #fff; padding: 16px 0; margin: 0 -32px 24px;
    padding-left: 32px; padding-right: 32px;
    border-bottom: 1px solid var(--kur-gray-100);
    backdrop-filter: blur(10px);
}
body.gl-dark .kur-tab-nav-sticky { background: rgba(30,41,59,0.95); border-color: rgba(255,255,255,.08); }

.kur-tab-nav {
    display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px;
    scrollbar-width: thin; scrollbar-color: var(--kur-gray-200) transparent;
}
.kur-tab-nav::-webkit-scrollbar { height: 4px; }
.kur-tab-nav::-webkit-scrollbar-thumb { background: var(--kur-gray-200); border-radius: 2px; }

.kur-tab-btn {
    flex: 0 0 auto; min-width: 90px; padding: 12px 16px;
    background: var(--kur-gray-50); border: 2px solid transparent;
    border-radius: 14px; cursor: pointer; text-align: center;
    transition: all .3s; color: var(--kur-gray-600); font-family: inherit;
    display: flex; flex-direction: column; align-items: center; gap: 2px;
    position: relative;
}
body.gl-dark .kur-tab-btn { background: rgba(255,255,255,.03); color: #94A3B8; }
.kur-tab-btn i { font-size: 1.2rem; margin-bottom: 2px; color: var(--tab-color); }
.kur-tab-btn span { font-size: .65rem; text-transform: uppercase; letter-spacing: .5px; }
.kur-tab-btn strong { font-size: 1.2rem; font-family: 'Space Grotesk', sans-serif; color: var(--kur-dark); }
body.gl-dark .kur-tab-btn strong { color: #F1F5F9; }
.kur-tab-btn small { font-size: .7rem; opacity: .7; font-family: var(--kur-font-mono); }
.kur-tab-btn.active, .kur-tab-btn:hover {
    background: var(--tab-color); color: #fff; border-color: var(--tab-color);
    transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
.kur-tab-btn.active strong, .kur-tab-btn:hover strong,
.kur-tab-btn.active i, .kur-tab-btn:hover i,
.kur-tab-btn.active span, .kur-tab-btn:hover span,
.kur-tab-btn.active small, .kur-tab-btn:hover small { color: #fff; }

/* Tab Panels */
.kur-tab-panel { display: none; animation: kurFadeIn .4s ease; }
.kur-tab-panel.active { display: block; }
@keyframes kurFadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

.kur-tab-header {
    display: flex; align-items: center; gap: 20px;
    margin-bottom: 32px; padding-bottom: 20px;
    border-bottom: 2px dashed var(--kur-gray-100);
    position: relative;
}
body.gl-dark .kur-tab-header { border-color: rgba(255,255,255,.08); }

.kur-tab-icon {
    width: 60px; height: 60px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.kur-tab-info { flex: 1; }
.kur-tab-info h2 { font-size: 1.6rem; margin: 0 0 4px 0; color: var(--kur-dark); font-weight: 800; }
body.gl-dark .kur-tab-info h2 { color: #F1F5F9; }
.kur-tab-info p { font-size: .95rem; color: var(--kur-gray-600); margin: 0; }
body.gl-dark .kur-tab-info p { color: #94A3B8; }

.kur-circular-progress {
    --size: 70px;
    --progress: 0;
    --progress-color: var(--kur-primary);
    width: var(--size); height: var(--size);
    border-radius: 50%;
    background: conic-gradient(var(--progress-color) calc(var(--progress) * 1%), var(--kur-gray-100) 0);
    display: flex; align-items: center; justify-content: center;
    position: relative;
}
body.gl-dark .kur-circular-progress { background: conic-gradient(var(--progress-color) calc(var(--progress) * 1%), rgba(255,255,255,.1) 0); }
.kur-circular-progress::before {
    content: ''; position: absolute; inset: 6px; border-radius: 50%;
    background: #fff;
}
body.gl-dark .kur-circular-progress::before { background: #1E293B; }
.kur-circular-progress span {
    position: relative; font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem; font-weight: 800; color: var(--kur-dark);
}
body.gl-dark .kur-circular-progress span { color: #F1F5F9; }

/* MK Grid */
.kur-mk-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.kur-mk-card {
    background: #fff; border-radius: 18px; padding: 22px;
    border: 1px solid var(--kur-gray-100);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    transform-style: preserve-3d; cursor: pointer;
    position: relative; overflow: hidden;
    box-shadow: var(--kur-shadow-sm);
    animation: kurSlideIn 0.4s ease forwards;
    opacity: 0;
}
body.gl-dark .kur-mk-card { background: #0F172A; border-color: rgba(255,255,255,.08); }

@keyframes kurSlideIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.kur-mk-card::before {
    content: ''; position: absolute; inset: 0; border-radius: 18px; padding: 2px;
    background: conic-gradient(from 0deg, transparent, var(--mk-color), transparent 30%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none;
}
.kur-mk-card:hover::before { opacity: 1; animation: kurRotateBorder 3s linear infinite; }
@keyframes kurRotateBorder { to { transform: rotate(360deg); } }

.kur-mk-card-glow {
    position: absolute; top: -50%; right: -50%; width: 200px; height: 200px;
    background: radial-gradient(circle, var(--mk-color), transparent 70%);
    opacity: 0; transition: opacity 0.4s; pointer-events: none;
    filter: blur(30px);
}
.kur-mk-card:hover .kur-mk-card-glow { opacity: 0.15; }

.kur-mk-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 32px rgba(0,0,0,0.1);
    border-color: var(--mk-color);
}

.kur-mk-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.kur-mk-code {
    font-family: var(--kur-font-mono); font-size: .8rem; font-weight: 700;
    color: var(--mk-color); background: var(--mk-color);
    background: linear-gradient(135deg, var(--mk-color), var(--mk-color));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
    padding: 4px 10px; border-radius: 6px;
    border: 1px dashed var(--mk-color);
}
.kur-mk-sks-badge {
    background: var(--mk-color); color: #fff;
    padding: 4px 10px; border-radius: 20px;
    font-size: 0.72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.3px;
}

.kur-mk-name {
    font-size: 1.05rem; font-weight: 700; line-height: 1.4;
    color: var(--kur-dark); margin: 0 0 16px 0;
    min-height: 2.8em;
}
body.gl-dark .kur-mk-name { color: #F1F5F9; }

.kur-mk-footer {
    display: flex; justify-content: space-between; align-items: center;
    padding-top: 12px; border-top: 1px dashed var(--kur-gray-100);
}
body.gl-dark .kur-mk-footer { border-color: rgba(255,255,255,.08); }

.kur-mk-dots { display: flex; gap: 4px; }
.kur-mk-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--mk-color); opacity: 0.6;
}
.kur-mk-dot:nth-child(1) { opacity: 1; }
.kur-mk-dot:nth-child(2) { opacity: 0.8; }
.kur-mk-dot:nth-child(3) { opacity: 0.6; }
.kur-mk-dot:nth-child(4) { opacity: 0.4; }
.kur-mk-dot:nth-child(5) { opacity: 0.3; }
.kur-mk-dot:nth-child(6) { opacity: 0.2; }

.kur-mk-detail-btn {
    background: var(--kur-gray-50); border: 1px solid var(--kur-gray-200);
    width: 32px; height: 32px; border-radius: 50%;
    color: var(--mk-color); cursor: pointer; font-size: 0.9rem;
    transition: all 0.3s; display: flex; align-items: center; justify-content: center;
}
body.gl-dark .kur-mk-detail-btn { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); }
.kur-mk-detail-btn:hover {
    background: var(--mk-color); color: #fff;
    transform: rotate(90deg); border-color: var(--mk-color);
}

/* ===== NO RESULTS ===== */
.kur-no-results {
    text-align: center; padding: 80px 20px; background: var(--kur-gray-50);
    border-radius: 20px; border: 2px dashed var(--kur-gray-200); margin-top: 24px;
}
body.gl-dark .kur-no-results { background: rgba(255,255,255,.03); border-color: rgba(255,255,255,.1); }
.kur-no-results-icon {
    position: relative; width: 100px; height: 100px; margin: 0 auto 24px;
    display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: var(--kur-gray-600);
}
.kur-no-results-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--kur-gray-600); animation: kurNoResPulse 2s infinite;
}
@keyframes kurNoResPulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }
.kur-no-results h3 { font-size: 1.4rem; margin-bottom: 8px; color: var(--kur-dark); font-weight: 800; }
body.gl-dark .kur-no-results h3 { color: #F1F5F9; }
.kur-no-results p { color: var(--kur-gray-600); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto; }
body.gl-dark .kur-no-results p { color: #94A3B8; }

.kur-btn-reset {
    padding: 12px 24px; background: var(--kur-primary); color: #fff;
    border: none; border-radius: 12px; font-weight: 600;
    cursor: pointer; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 8px;
    font-family: inherit; font-size: 0.95rem;
}
.kur-btn-reset:hover { background: var(--kur-primary-dark); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,0.3); }

/* ===== MODAL ===== */
.kur-modal-backdrop {
    position: fixed; inset: 0; background: rgba(15,23,42,0.85);
    backdrop-filter: blur(12px); z-index: 9999;
    display: flex; align-items: center; justify-content: center;
    opacity: 0; visibility: hidden; transition: all 0.3s; padding: 20px;
}
.kur-modal-backdrop.active { opacity: 1; visibility: visible; }

.kur-modal-container {
    background: #fff; border-radius: 24px; width: 100%; max-width: 600px;
    max-height: 90vh; overflow: hidden;
    box-shadow: 0 30px 60px rgba(0,0,0,0.3);
    transform: scale(0.9) translateY(30px);
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex; flex-direction: column; position: relative;
}
body.gl-dark .kur-modal-container { background: #1E293B; }
.kur-modal-backdrop.active .kur-modal-container { transform: scale(1) translateY(0); }

.kur-modal-close {
    position: absolute; top: 20px; right: 20px; z-index: 10;
    width: 40px; height: 40px; border-radius: 50%;
    background: var(--kur-gray-100); border: none;
    color: var(--kur-dark); cursor: pointer; font-size: 1.1rem;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s;
}
body.gl-dark .kur-modal-close { background: rgba(255,255,255,.1); color: #fff; }
.kur-modal-close:hover { background: #EF4444; color: #fff; transform: rotate(90deg); }

.kur-modal-content { overflow-y: auto; }

.kur-modal-header {
    padding: 32px; background: linear-gradient(135deg, var(--kur-primary), var(--kur-primary-dark));
    color: #fff; display: flex; align-items: center; gap: 20px;
}
.kur-modal-icon {
    width: 70px; height: 70px; border-radius: 20px;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; flex-shrink: 0;
}
.kur-modal-code {
    display: inline-block; font-family: var(--kur-font-mono);
    font-size: 0.85rem; font-weight: 700; letter-spacing: 0.5px;
    background: rgba(255,255,255,0.2); padding: 4px 10px;
    border-radius: 6px; margin-bottom: 6px;
}
.kur-modal-title-group h3 { font-size: 1.5rem; margin: 0; font-weight: 800; line-height: 1.3; }

.kur-modal-body { padding: 32px; }

.kur-modal-stats {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
    margin-bottom: 28px;
}
.kur-modal-stat {
    display: flex; align-items: center; gap: 14px;
    padding: 16px; background: var(--kur-gray-50);
    border-radius: 14px; border: 1px solid var(--kur-gray-100);
}
body.gl-dark .kur-modal-stat { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.08); }
.kur-modal-stat i { font-size: 1.5rem; color: var(--kur-primary); }
.kur-modal-stat span { font-size: 0.8rem; color: var(--kur-gray-600); display: block; margin-bottom: 2px; }
body.gl-dark .kur-modal-stat span { color: #94A3B8; }
.kur-modal-stat strong { font-size: 1.1rem; color: var(--kur-dark); font-family: 'Space Grotesk', sans-serif; }
body.gl-dark .kur-modal-stat strong { color: #F1F5F9; }

.kur-modal-info { margin-bottom: 24px; }
.kur-modal-info h4 {
    font-size: 1rem; color: var(--kur-dark); margin-bottom: 12px;
    display: flex; align-items: center; gap: 8px; font-weight: 700;
}
body.gl-dark .kur-modal-info h4 { color: #F1F5F9; }
.kur-modal-info h4 i { color: var(--kur-primary); }
.kur-modal-info p { color: var(--kur-gray-600); line-height: 1.7; margin: 0; }
body.gl-dark .kur-modal-info p { color: #94A3B8; }

.kur-modal-outcomes {
    list-style: none; padding: 0; margin: 0;
    display: grid; grid-template-columns: 1fr; gap: 10px;
}
.kur-modal-outcomes li {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 10px 14px; background: var(--kur-gray-50);
    border-radius: 10px; font-size: 0.9rem; color: var(--kur-gray-600);
}
body.gl-dark .kur-modal-outcomes li { background: rgba(255,255,255,.05); color: #CBD5E1; }
.kur-modal-outcomes i { color: var(--kur-primary); margin-top: 2px; flex-shrink: 0; }

/* ===== TOAST ===== */
.kur-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--kur-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    display: flex; align-items: center; gap: 10px;
    font-size: 0.9rem; opacity: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10000; font-weight: 500;
}
body.gl-dark .kur-toast { background: #fff; color: var(--kur-dark); }
.kur-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.kur-toast i { color: var(--kur-primary); font-size: 1.2rem; }

/* ===== ANIMATIONS ===== */
.fade-up { opacity: 0; transform: translateY(30px); transition: opacity 0.8s ease, transform 0.8s ease; }
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 968px) {
    .kur-layout { grid-template-columns: 1fr; }
    .kur-sidebar { position: static; order: 2; }
    .kur-content { order: 1; }
    .kur-tab-nav-sticky { top: 70px; }
    .kur-tab-nav-sticky { margin-left: -24px; margin-right: -24px; padding-left: 24px; padding-right: 24px; }
}
@media (max-width: 480px) {
    .kur-hero { padding: 120px 20px 60px; }
    .kur-hero-stats { flex-direction: column; align-items: center; }
    .kur-stat-pill { width: 100%; max-width: 300px; }
    .kur-content { padding: 20px; }
    .kur-tab-header { flex-direction: column; align-items: flex-start; }
    .kur-circular-progress { margin-top: 12px; }
    .kur-mk-grid { grid-template-columns: 1fr; }
    .kur-search-hint { display: none; }
    .kur-search-wrap input { padding-right: 48px; }
    .kur-search-clear { right: 16px; }
    .kur-modal-header { padding: 24px; }
    .kur-modal-body { padding: 24px; }
    .kur-modal-stats { grid-template-columns: 1fr; }
}

/* ===== PRINT ===== */
@media print {
    .kur-hero, .kur-sidebar, .kur-search-wrap, .kur-tab-nav-sticky, .navbar, .footer, .wa-float { display: none !important; }
    .kur-layout { display: block; }
    .kur-content { box-shadow: none; border: none; padding: 0; }
    .kur-tab-panel { display: block !important; page-break-inside: avoid; margin-bottom: 30px; }
    .kur-mk-card { break-inside: avoid; border: 1px solid #ccc; }
    body { background: #fff !important; color: #000 !important; }
}
</style>

<!-- ============================================================
     SPECTACULAR SCRIPTS (Null-Safe)
============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // 1. Network Canvas Animation
    const canvas = document.getElementById('kurNetworkCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let width, height, particles = [];
        const particleCount = window.innerWidth < 768 ? 25 : 60;

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
                this.size = Math.random() * 2.5 + 1;
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
    const textElement = document.getElementById('kurTypingText');
    if (textElement) {
        const phrases = [
            "<?= $totalSks ?> SKS yang dirancang untuk membentuk profesional teknologi yang kompeten.",
            "Kurikulum terintegrasi dengan kebutuhan industri digital global.",
            "Peta perjalanan akademik 8 semester menuju karir impian Anda."
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
    const counters = document.querySelectorAll('.kur-stat-num[data-count]');
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

    // 4. Progress Bars Animation
    const progressObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target) {
                const fills = entry.target.querySelectorAll('.kur-progress-fill');
                fills.forEach(fill => {
                    const targetWidth = fill.getAttribute('data-width');
                    setTimeout(() => {
                        fill.style.width = targetWidth + '%';
                    }, 200);
                });
                progressObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    const progressCard = document.querySelector('.kur-progress-card');
    if (progressCard) progressObserver.observe(progressCard);

    // 5. 3D Tilt Effect
    const tiltElements = document.querySelectorAll('.kur-tilt');
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

    // 6. Tab Switching
    const tabs = document.querySelectorAll('.kur-tab-btn');
    const panels = document.querySelectorAll('.kur-tab-panel');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.target;
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            panels.forEach(p => {
                p.classList.remove('active');
                if (p.dataset.tab === target) {
                    p.classList.add('active');
                    // Re-trigger animations
                    const items = p.querySelectorAll('.kur-mk-card');
                    items.forEach((item, idx) => {
                        item.style.animation = 'none';
                        void item.offsetWidth;
                        item.style.animation = `kurSlideIn 0.4s ease forwards ${idx * 0.05}s`;
                    });
                }
            });
            
            // Smooth scroll to tabs
            const sticky = document.getElementById('kurTabSticky');
            if (sticky && window.innerWidth > 968) {
                window.scrollTo({
                    top: sticky.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });

    // 7. Live Search Filter
    const searchInput = document.getElementById('kurSearchInput');
    const searchClear = document.getElementById('kurSearchClear');
    const noResults = document.getElementById('kurNoResults');
    const tabSticky = document.getElementById('kurTabSticky');

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            if (searchClear) searchClear.style.display = query ? 'block' : 'none';
            
            let totalVisible = 0;

            panels.forEach(panel => {
                const items = panel.querySelectorAll('.kur-mk-card');
                let panelVisibleCount = 0;

                items.forEach(item => {
                    const name = item.getAttribute('data-name') || '';
                    const code = item.getAttribute('data-code') || '';
                    
                    if (name.includes(query) || code.includes(query)) {
                        item.style.display = 'block';
                        panelVisibleCount++;
                        totalVisible++;
                    } else {
                        item.style.display = 'none';
                    }
                });

                if (query !== '') {
                    panel.style.display = panelVisibleCount > 0 ? 'block' : 'none';
                    panel.classList.remove('active');
                } else {
                    panel.style.display = '';
                    const activeTab = document.querySelector('.kur-tab-btn.active');
                    if (activeTab && panel.dataset.tab === activeTab.dataset.target) {
                        panel.classList.add('active');
                    }
                }
            });

            if (noResults) {
                noResults.style.display = (totalVisible === 0 && query !== '') ? 'block' : 'none';
            }
            if (tabSticky) {
                tabSticky.style.display = query !== '' ? 'none' : 'block';
            }
        });

        if (searchClear) {
            searchClear.addEventListener('click', () => {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
                const firstTab = document.querySelector('.kur-tab-btn');
                if (firstTab) firstTab.click();
            });
        }
    }

    // Ctrl+K shortcut
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) searchInput.focus();
        }
        if (e.key === 'Escape') {
            window.kurCloseModal && window.kurCloseModal();
        }
    });

    // 8. Scroll Animation
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

    // 9. Parallax Orbs
    const hero = document.querySelector('.kur-hero');
    if (hero && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const orbs = hero.querySelectorAll('.kur-orb');
            orbs.forEach((orb, index) => {
                const speed = (index + 1) * 0.3;
                orb.style.transform = `translateY(${scrolled * speed}px)`;
            });
        }, { passive: true });
    }

    // 10. Toast Notification
    window.kurShowToast = function(message) {
        const toast = document.getElementById('kurToast');
        if (!toast) return;
        const msgEl = document.getElementById('kurToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // Welcome toast
    setTimeout(() => {
        window.kurShowToast('📚 Kurikulum berhasil dimuat!');
    }, 1500);

    // 11. Console Easter Egg
    console.log('%c╔═══════════════════════════════════════╗', 'color:#10B981;font-weight:bold');
    console.log('%c║   KURIKULUM - Informatika Pro         ║', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c╚═══════════════════════════════════════╝', 'color:#10B981;font-weight:bold');
});

// 12. Modal Functions
window.kurShowMKDetail = function(code, name, sks, semester) {
    const modal = document.getElementById('kurModal');
    if (!modal) return;
    
    const set = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val || '-'; };
    set('kurModalCode', code);
    set('kurModalName', name);
    set('kurModalSks', sks + ' SKS');
    set('kurModalSemester', 'Semester ' + semester);
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
};

window.kurCloseModal = function() {
    const modal = document.getElementById('kurModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
};

const modalBackdrop = document.getElementById('kurModal');
if (modalBackdrop) {
    modalBackdrop.addEventListener('click', function(e) {
        if (e.target === this) window.kurCloseModal();
    });
}

// 13. Jump to Semester
window.jumpToSemester = function(num) {
    const tab = document.querySelector(`.kur-tab-btn[data-target="${num}"]`);
    if (tab) tab.click();
};
</script>

<?php require_once 'includes/footer.php'; ?>