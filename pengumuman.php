<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Pengumuman Resmi';
$pageDesc = 'Informasi resmi dan pengumuman penting dari Program Studi Informatika.';

// LOGIC ASLI DIPERTAHANKAN 100%
try {
    $db = getDB();
    $sql = "SELECT * FROM pengumuman 
            WHERE status = 'aktif' 
              AND (tanggal_berakhir IS NULL OR tanggal_berakhir >= CURDATE())
            ORDER BY FIELD(prioritas, 'penting', 'tinggi', 'normal', 'rendah'), created_at DESC";
    $pengumumanList = $db->query($sql)->fetchAll();
    
    // Statistik tambahan
    $totalPengumuman = count($pengumumanList);
    $pentingCount = count(array_filter($pengumumanList, fn($p) => $p['prioritas'] === 'penting'));
    $tinggiCount = count(array_filter($pengumumanList, fn($p) => $p['prioritas'] === 'tinggi'));
    $normalCount = count(array_filter($pengumumanList, fn($p) => $p['prioritas'] === 'normal'));
    
} catch (Throwable $e) {
    $pengumumanList = [];
    $totalPengumuman = 0;
    $pentingCount = 0;
    $tinggiCount = 0;
    $normalCount = 0;
}

// Priority Signal Config
$signalConfig = [
    'penting' => ['color' => '#EF4444', 'label' => 'CRITICAL', 'frequency' => 'URGENT', 'bars' => 5],
    'tinggi' => ['color' => '#F59E0B', 'label' => 'HIGH', 'frequency' => 'PRIORITY', 'bars' => 4],
    'normal' => ['color' => '#3B82F6', 'label' => 'NORMAL', 'frequency' => 'STANDARD', 'bars' => 3],
    'rendah' => ['color' => '#6B7280', 'label' => 'LOW', 'frequency' => 'ROUTINE', 'bars' => 2],
];
?>

<!-- ===== READING PROGRESS ===== -->
<div class="bc-reading-progress">
    <div class="bc-progress-fill" id="bcProgressFill"></div>
    <div class="bc-progress-ping">
        <span></span>
    </div>
</div>

<!-- ===== HERO WITH RADAR CANVAS ===== -->
<section class="bc-hero">
    <canvas id="bcRadarCanvas" class="bc-radar-canvas"></canvas>
    <div class="bc-hero-bg">
        <div class="bc-beacon-tower"></div>
        <div class="bc-signal-rings">
            <span class="bc-ring"></span>
            <span class="bc-ring bc-ring-2"></span>
            <span class="bc-ring bc-ring-3"></span>
            <span class="bc-ring bc-ring-4"></span>
        </div>
        <div class="bc-grid-overlay"></div>
        <div class="bc-scan-line"></div>
    </div>
    
    <!-- Floating Dispatch Elements -->
    <div class="bc-floating-elements">
        <span class="bc-float-el" style="--delay: 0s; --x: 8%; --y: 20%;">📡</span>
        <span class="bc-float-el" style="--delay: 1.5s; --x: 88%; --y: 25%;">🔔</span>
        <span class="bc-float-el" style="--delay: 3s; --x: 12%; --y: 72%;">⚡</span>
        <span class="bc-float-el" style="--delay: 0.8s; --x: 85%; --y: 68%;">📢</span>
        <span class="bc-float-el" style="--delay: 2.5s; --x: 50%; --y: 12%;">🚨</span>
        <span class="bc-float-el" style="--delay: 4s; --x: 45%; --y: 85%;">📋</span>
    </div>
    
    <div class="container bc-hero-content">
        <span class="bc-hero-badge fade-up">
            <span class="bc-badge-pulse"></span>
            <span class="bc-signal-status">
                <span class="bc-status-dot"></span>
                SIGNAL ACTIVE
            </span>
            <i class="fas fa-broadcast-tower"></i> BEACON DISPATCH
        </span>
        
        <h1 class="bc-hero-title fade-up">
            <span class="bc-title-line">Papan</span>
            <span class="bc-title-line bc-title-signal" data-text="Pengumuman">Pengumuman</span>
        </h1>
        
        <p class="bc-hero-subtitle fade-up">
            <span id="bcTypingText"></span><span class="bc-cursor">|</span>
        </p>
        
        <!-- Signal Stats -->
        <div class="bc-signal-stats fade-up">
            <div class="bc-signal-stat">
                <div class="bc-stat-icon bc-stat-critical">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="bc-stat-pulse"></span>
                </div>
                <div class="bc-stat-info">
                    <span class="bc-stat-num" data-count="<?= $pentingCount ?>">0</span>
                    <span class="bc-stat-label">Critical</span>
                </div>
                <div class="bc-signal-bars bc-bars-5">
                    <span></span><span></span><span></span><span></span><span></span>
                </div>
            </div>
            
            <div class="bc-signal-stat">
                <div class="bc-stat-icon bc-stat-high">
                    <i class="fas fa-arrow-up"></i>
                    <span class="bc-stat-pulse"></span>
                </div>
                <div class="bc-stat-info">
                    <span class="bc-stat-num" data-count="<?= $tinggiCount ?>">0</span>
                    <span class="bc-stat-label">High Priority</span>
                </div>
                <div class="bc-signal-bars bc-bars-4">
                    <span></span><span></span><span></span><span></span>
                </div>
            </div>
            
            <div class="bc-signal-stat">
                <div class="bc-stat-icon bc-stat-normal">
                    <i class="fas fa-info-circle"></i>
                    <span class="bc-stat-pulse"></span>
                </div>
                <div class="bc-stat-info">
                    <span class="bc-stat-num" data-count="<?= $normalCount ?>">0</span>
                    <span class="bc-stat-label">Normal</span>
                </div>
                <div class="bc-signal-bars bc-bars-3">
                    <span></span><span></span><span></span>
                </div>
            </div>
            
            <div class="bc-signal-stat">
                <div class="bc-stat-icon bc-stat-total">
                    <i class="fas fa-satellite-dish"></i>
                    <span class="bc-stat-pulse"></span>
                </div>
                <div class="bc-stat-info">
                    <span class="bc-stat-num" data-count="<?= $totalPengumuman ?>">0</span>
                    <span class="bc-stat-label">Total Signals</span>
                </div>
                <div class="bc-signal-bars bc-bars-full">
                    <span></span><span></span><span></span><span></span><span></span>
                </div>
            </div>
        </div>
        
        <!-- Live Transmission Status -->
        <div class="bc-transmission-status fade-up">
            <span class="bc-tx-label">
                <span class="bc-tx-dot"></span>
                LIVE TRANSMISSION
            </span>
            <div class="bc-tx-wave">
                <?php for ($i = 0; $i < 40; $i++): ?>
                    <span style="--wave-delay: <?= $i * 0.05 ?>s; --wave-height: <?= rand(10, 100) ?>%"></span>
                <?php endfor; ?>
            </div>
            <span class="bc-tx-time" id="bcLiveTime">00:00:00</span>
        </div>
    </div>
    
    <div class="bc-scroll-indicator">
        <span>Receive Signals</span>
        <div class="bc-scroll-arrow">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<section class="bc-section">
    <div class="container">
        <div class="bc-layout">
            
            <!-- LEFT: Dispatch Console Sidebar -->
            <aside class="bc-sidebar fade-up">
                
                <!-- Beacon Status Card -->
                <div class="bc-beacon-status bc-tilt">
                    <div class="bc-beacon-glow"></div>
                    <div class="bc-beacon-visual">
                        <div class="bc-tower-icon">
                            <i class="fas fa-broadcast-tower"></i>
                            <span class="bc-tower-wave"></span>
                            <span class="bc-tower-wave bc-wave-2"></span>
                            <span class="bc-tower-wave bc-wave-3"></span>
                        </div>
                    </div>
                    <h3>Beacon Status</h3>
                    <div class="bc-beacon-info">
                        <div class="bc-info-row">
                            <span>Status</span>
                            <strong class="bc-status-online">
                                <span class="bc-online-dot"></span> ONLINE
                            </strong>
                        </div>
                        <div class="bc-info-row">
                            <span>Signal Strength</span>
                            <strong>EXCELLENT</strong>
                        </div>
                        <div class="bc-info-row">
                            <span>Last Broadcast</span>
                            <strong id="bcLastBroadcast">Just now</strong>
                        </div>
                        <div class="bc-info-row">
                            <span>Frequency</span>
                            <strong>24/7 MHz</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Priority Filter Card -->
                <div class="bc-priority-filter">
                    <h4><i class="fas fa-signal"></i> Signal Priority</h4>
                    <div class="bc-priority-list">
                        <button class="bc-priority-btn active" data-filter="all">
                            <span class="bc-priority-dot bc-dot-all"></span>
                            <span>All Signals</span>
                            <span class="bc-priority-count"><?= $totalPengumuman ?></span>
                        </button>
                        <button class="bc-priority-btn" data-filter="penting">
                            <span class="bc-priority-dot bc-dot-critical"></span>
                            <span>Critical</span>
                            <span class="bc-priority-count bc-count-critical"><?= $pentingCount ?></span>
                        </button>
                        <button class="bc-priority-btn" data-filter="tinggi">
                            <span class="bc-priority-dot bc-dot-high"></span>
                            <span>High Priority</span>
                            <span class="bc-priority-count bc-count-high"><?= $tinggiCount ?></span>
                        </button>
                        <button class="bc-priority-btn" data-filter="normal">
                            <span class="bc-priority-dot bc-dot-normal"></span>
                            <span>Normal</span>
                            <span class="bc-priority-count bc-count-normal"><?= $normalCount ?></span>
                        </button>
                    </div>
                </div>
                
                <!-- Signal Log Widget -->
                <div class="bc-signal-log">
                    <h4><i class="fas fa-history"></i> Recent Activity</h4>
                    <div class="bc-log-entries">
                        <div class="bc-log-entry">
                            <span class="bc-log-dot bc-dot-online"></span>
                            <div>
                                <strong>System Online</strong>
                                <small>Beacon activated</small>
                            </div>
                        </div>
                        <div class="bc-log-entry">
                            <span class="bc-log-dot bc-dot-broadcast"></span>
                            <div>
                                <strong><?= $totalPengumuman ?> Signals</strong>
                                <small>Active broadcasts</small>
                            </div>
                        </div>
                        <div class="bc-log-entry">
                            <span class="bc-log-dot bc-dot-scan"></span>
                            <div>
                                <strong>Radar Scanning</strong>
                                <small>Continuous monitoring</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Emergency Contact -->
                <div class="bc-emergency-card">
                    <div class="bc-emergency-bg">
                        <i class="fas fa-satellite-dish"></i>
                    </div>
                    <div class="bc-emergency-content">
                        <i class="fas fa-headset"></i>
                        <h4>Emergency Channel</h4>
                        <p>Butuh bantuan segera? Hubungi kami langsung</p>
                        <a href="tel:+6281234567890" class="bc-emergency-btn">
                            <i class="fas fa-phone"></i> Call Now
                        </a>
                    </div>
                </div>
            </aside>
            
            <!-- RIGHT: Main Content -->
            <div class="bc-main">
                
                <!-- Dispatch Console -->
                <div class="bc-console fade-up">
                    <div class="bc-console-header">
                        <div class="bc-console-dots">
                            <span class="dot red"></span>
                            <span class="dot yellow"></span>
                            <span class="dot green"></span>
                        </div>
                        <i class="fas fa-terminal"></i>
                        <span>signal_receiver.exe</span>
                        <span class="bc-console-status">● RECEIVING</span>
                    </div>
                    
                    <div class="bc-console-body">
                        <div class="bc-search-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" id="bcSearchInput" placeholder="Search signals by title or content..." autocomplete="off">
                            <span class="bc-search-hint">
                                <kbd>Ctrl</kbd>+<kbd>K</kbd>
                            </span>
                            <button class="bc-search-clear" id="bcSearchClear" style="display: none;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="bc-console-meta">
                            <span class="bc-meta-info">
                                <i class="fas fa-satellite-dish"></i>
                                <strong id="bcResultsCount"><?= $totalPengumuman ?></strong> signals received
                            </span>
                            <span class="bc-meta-time">
                                <i class="fas fa-clock"></i>
                                Last scan: <span id="bcLastScan">Just now</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Announcement List (Signal Cards) -->
                <?php if (!empty($pengumumanList)): ?>
                    <div class="bc-signals" id="bcSignals">
                        <?php foreach ($pengumumanList as $index => $p): 
                            $priority = $p['prioritas'] ?? 'normal';
                            $config = $signalConfig[$priority] ?? $signalConfig['normal'];
                            $icon = match($priority) {
                                'penting' => 'fa-exclamation-triangle',
                                'tinggi' => 'fa-arrow-up',
                                'normal' => 'fa-info-circle',
                                default => 'fa-tag'
                            };
                            $timeAgo = bcTimeAgo($p['created_at']);
                        ?>
                        <div class="bc-signal-card fade-up" 
                             style="animation-delay: <?= $index * 0.06 ?>s; --signal-color: <?= $config['color'] ?>;"
                             data-priority="<?= $priority ?>" 
                             data-title="<?= strtolower(clean($p['judul'])) ?>" 
                             data-content="<?= strtolower(clean($p['konten'])) ?>">
                            
                            <!-- Signal Pulse Effect -->
                            <div class="bc-signal-pulse"></div>
                            
                            <!-- Priority Side Bar -->
                            <div class="bc-signal-bar"></div>
                            
                            <!-- Transmission Header -->
                            <div class="bc-signal-header">
                                <div class="bc-signal-meta-left">
                                    <div class="bc-signal-icon" style="background: <?= $config['color'] ?>20; color: <?= $config['color'] ?>;">
                                        <i class="fas <?= $icon ?>"></i>
                                        <?php if ($priority === 'penting'): ?>
                                        <span class="bc-icon-alert"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="bc-signal-info">
                                        <span class="bc-signal-priority" style="color: <?= $config['color'] ?>;">
                                            <i class="fas fa-signal"></i>
                                            <?= $config['label'] ?> SIGNAL
                                        </span>
                                        <span class="bc-signal-frequency">
                                            FREQ: <?= $config['frequency'] ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="bc-signal-meta-right">
                                    <span class="bc-signal-id">
                                        #TX-<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?>
                                    </span>
                                    <span class="bc-signal-time">
                                        <i class="far fa-clock"></i>
                                        <?= $timeAgo ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Signal Body -->
                            <div class="bc-signal-body">
                                <div class="bc-signal-date-badge">
                                    <span class="bc-date-day"><?= date('d', strtotime($p['created_at'])) ?></span>
                                    <span class="bc-date-month"><?= strtoupper(date('M', strtotime($p['created_at']))) ?></span>
                                </div>
                                
                                <div class="bc-signal-content">
                                    <h3 class="bc-signal-title"><?= clean($p['judul']) ?></h3>
                                    <p class="bc-signal-desc"><?= nl2br(clean($p['konten'])) ?></p>
                                </div>
                            </div>
                            
                            <!-- Signal Footer -->
                            <div class="bc-signal-footer">
                                <div class="bc-signal-strength">
                                    <span class="bc-strength-label">Signal Strength:</span>
                                    <div class="bc-strength-bars">
                                        <?php for ($i = 0; $i < $config['bars']; $i++): ?>
                                            <span style="background: <?= $config['color'] ?>;"></span>
                                        <?php endfor; ?>
                                        <?php for ($i = $config['bars']; $i < 5; $i++): ?>
                                            <span class="bc-bar-inactive"></span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($p['tanggal_berakhir'])): ?>
                                <span class="bc-signal-expiry">
                                    <i class="fas fa-hourglass-half"></i>
                                    Expires: <?= formatTanggal($p['tanggal_berakhir']) ?>
                                </span>
                                <?php else: ?>
                                <span class="bc-signal-permanent">
                                    <i class="fas fa-infinity"></i>
                                    Permanent Signal
                                </span>
                                <?php endif; ?>
                                
                                <button class="bc-signal-action" onclick="bcShareSignal(this, '<?= addslashes(clean($p['judul'])) ?>')">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                            </div>
                            
                            <!-- Corner Brackets -->
                            <div class="bc-bracket bc-bracket-tl"></div>
                            <div class="bc-bracket bc-bracket-tr"></div>
                            <div class="bc-bracket bc-bracket-bl"></div>
                            <div class="bc-bracket bc-bracket-br"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- No Results State -->
                    <div class="bc-no-results" id="bcNoResults" style="display: none;">
                        <div class="bc-no-results-icon">
                            <i class="fas fa-satellite-dish"></i>
                            <div class="bc-no-pulse"></div>
                            <div class="bc-no-pulse bc-pulse-2"></div>
                        </div>
                        <h3>No Signal Detected</h3>
                        <p>Tidak ada pengumuman yang cocok dengan filter Anda. Coba kata kunci lain atau reset filter.</p>
                        <button class="bc-btn bc-btn-primary" onclick="bcResetFilters()">
                            <i class="fas fa-redo"></i> Retune Receiver
                        </button>
                    </div>
                    
                <?php else: ?>
                    <div class="bc-empty-state fade-up">
                        <div class="bc-empty-bg">
                            <div class="bc-empty-radar"></div>
                        </div>
                        <div class="bc-empty-content">
                            <div class="bc-empty-icon">
                                <i class="fas fa-broadcast-tower"></i>
                                <div class="bc-empty-wave"></div>
                            </div>
                            <h3>No Active Signals</h3>
                            <p>Saat ini tidak ada pengumuman aktif. Beacon kami akan segera memancarkan sinyal baru.</p>
                            <div class="bc-empty-status">
                                <span class="bc-empty-dot"></span>
                                <span>Beacon is monitoring...</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Toast -->
<div id="bcToast" class="bc-toast">
    <i class="fas fa-check-circle"></i>
    <span id="bcToastMsg">Success!</span>
</div>

<!-- Back to Top -->
<button class="bc-back-top" id="bcBackTop" title="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<?php
// Helper function untuk time ago
function bcTimeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}
?>

<style>
:root {
    --bc-primary: #10B981;
    --bc-primary-dark: #047857;
    --bc-primary-light: #D1FAE5;
    --bc-critical: #EF4444;
    --bc-high: #F59E0B;
    --bc-normal: #3B82F6;
    --bc-low: #6B7280;
    --bc-cyan: #06B6D4;
    --bc-dark: #0F172A;
    --bc-deeper: #020617;
    --bc-gray-50: #F8FAFC;
    --bc-gray-100: #F1F5F9;
    --bc-gray-200: #E2E8F0;
    --bc-gray-600: #475569;
    --bc-gray-700: #374151;
    --bc-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --bc-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --bc-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
    --bc-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --bc-font-display: 'Space Grotesk', 'Inter', sans-serif;
}

body.gl-dark {
    --bc-gray-50: #0B1120;
    --bc-gray-100: rgba(255,255,255,.08);
    --bc-gray-200: rgba(255,255,255,.1);
    --bc-gray-600: #94A3B8;
    --bc-gray-700: #CBD5E1;
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--bc-gray-50); }
::-webkit-scrollbar-thumb { 
    background: linear-gradient(180deg, var(--bc-critical), var(--bc-high), var(--bc-primary)); 
    border-radius: 5px; 
}

/* ===== READING PROGRESS ===== */
.bc-reading-progress {
    position: fixed; top: 0; left: 0; right: 0; height: 4px;
    background: rgba(239,68,68,0.1); z-index: 9998;
}
.bc-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--bc-critical), var(--bc-high), var(--bc-primary));
    box-shadow: 0 0 10px rgba(239,68,68,0.5);
    transition: width 0.1s ease-out;
}
.bc-progress-ping {
    position: absolute; top: -4px; right: 0;
    width: 12px; height: 12px; border-radius: 50%;
    background: var(--bc-critical);
    box-shadow: 0 0 10px var(--bc-critical);
    animation: bcPingPulse 1.5s infinite;
}
.bc-progress-ping span {
    position: absolute; inset: -4px; border-radius: 50%;
    border: 2px solid var(--bc-critical);
    animation: bcPingWave 1.5s infinite;
}
@keyframes bcPingPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}
@keyframes bcPingWave {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(2); opacity: 0; }
}

/* ===== HERO ===== */
.bc-hero {
    background: linear-gradient(135deg, #020617 0%, #0F172A 30%, #1E1B4B 70%, #047857 100%);
    padding: 160px 20px 120px; position: relative; overflow: hidden;
    min-height: 90vh; display: flex; align-items: center; justify-content: center;
}

.bc-radar-canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    z-index: 1; opacity: 0.7;
}

.bc-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }

/* Beacon Tower */
.bc-beacon-tower {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 20px; height: 20px; border-radius: 50%;
    background: radial-gradient(circle, #fff, var(--bc-primary));
    box-shadow: 0 0 40px var(--bc-primary), 0 0 80px var(--bc-primary);
    z-index: 3;
}
.bc-beacon-tower::before {
    content: ''; position: absolute; inset: -10px;
    border-radius: 50%; border: 2px solid rgba(255,255,255,0.3);
    animation: bcBeaconCore 2s infinite;
}
@keyframes bcBeaconCore {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.5); opacity: 0.5; }
}

/* Signal Rings */
.bc-signal-rings {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 100%; height: 100%;
}
.bc-ring {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    border-radius: 50%;
    border: 1px solid rgba(16,185,129,0.3);
    animation: bcRingExpand 4s infinite;
}
.bc-ring-2 { animation-delay: 1s; }
.bc-ring-3 { animation-delay: 2s; }
.bc-ring-4 { animation-delay: 3s; }
@keyframes bcRingExpand {
    0% { width: 20px; height: 20px; opacity: 1; }
    100% { width: 800px; height: 800px; opacity: 0; }
}

.bc-grid-overlay {
    position: absolute; inset: 0;
    background-image: 
        linear-gradient(rgba(16,185,129,0.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(16,185,129,0.08) 1px, transparent 1px);
    background-size: 50px 50px;
    mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
}

.bc-scan-line {
    position: absolute; top: 50%; left: 50%;
    width: 50%; height: 2px;
    background: linear-gradient(90deg, var(--bc-primary), transparent);
    transform-origin: left center;
    animation: bcScanRotate 6s linear infinite;
    box-shadow: 0 0 20px var(--bc-primary);
}
@keyframes bcScanRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Floating Elements */
.bc-floating-elements { position: absolute; inset: 0; z-index: 3; pointer-events: none; }
.bc-float-el {
    position: absolute; font-size: 2.5rem;
    animation: bcFloatEl 10s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
    opacity: 0.35; filter: drop-shadow(0 0 15px rgba(239,68,68,0.5));
}
@keyframes bcFloatEl {
    0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.35; }
    50% { transform: translateY(-35px) rotate(15deg); opacity: 0.7; }
}

.bc-hero-content {
    position: relative; z-index: 4; text-align: center;
    color: #fff; max-width: 1000px; margin: 0 auto;
}

.bc-hero-badge {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 10px 22px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    font-size: .82rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 700; letter-spacing: 2px; position: relative;
    font-family: var(--bc-font-mono);
}
.bc-badge-pulse {
    position: absolute; inset: 0; border-radius: 50px;
    border: 2px solid var(--bc-critical);
    animation: bcBadgePulse 2s infinite;
}
@keyframes bcBadgePulse { 
    0% { transform: scale(1); opacity: 1; } 
    100% { transform: scale(1.15); opacity: 0; } 
}

.bc-signal-status {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 10px; background: var(--bc-critical);
    border-radius: 4px; font-size: 0.7rem; font-weight: 800;
    letter-spacing: 1px;
}
.bc-status-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #fff; animation: bcStatusPulse 1s infinite;
}
@keyframes bcStatusPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.bc-hero-title {
    font-size: clamp(3rem, 9vw, 6.5rem); font-weight: 900;
    margin-bottom: 24px; letter-spacing: -0.03em; line-height: 0.95;
    font-family: var(--bc-font-display);
}
.bc-title-line { display: block; }

.bc-title-signal {
    position: relative; display: inline-block;
    background: linear-gradient(135deg, #EF4444 0%, #F59E0B 40%, #10B981 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 100%;
    animation: bcSignalShift 4s ease infinite;
}
.bc-title-signal::before, .bc-title-signal::after {
    content: attr(data-text); position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    background: inherit; -webkit-background-clip: text;
    -webkit-text-fill-color: transparent; background-clip: text;
}
.bc-title-signal::before {
    animation: bcGlitch1 3s infinite;
    clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%);
    opacity: 0.7;
}
.bc-title-signal::after {
    animation: bcGlitch2 2.5s infinite;
    clip-path: polygon(0 60%, 100% 60%, 100% 100%, 0 100%);
    opacity: 0.7;
}
@keyframes bcSignalShift { 
    0%, 100% { background-position: 0% 50%; } 
    50% { background-position: 100% 50%; } 
}
@keyframes bcGlitch1 {
    0%, 90%, 100% { transform: translate(0, 0); }
    92% { transform: translate(-3px, 1px); }
    94% { transform: translate(2px, -2px); }
}
@keyframes bcGlitch2 {
    0%, 90%, 100% { transform: translate(0, 0); }
    91% { transform: translate(2px, 1px); }
    93% { transform: translate(-2px, -1px); }
}

.bc-hero-subtitle {
    font-size: 1.2rem; opacity: .9; max-width: 700px;
    margin: 0 auto 40px; line-height: 1.7;
    display: inline-block; min-height: 1.7em;
}
.bc-cursor { animation: bcBlink 1s infinite; color: var(--bc-critical); font-weight: 300; }
@keyframes bcBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

/* Signal Stats */
.bc-signal-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 14px; max-width: 950px; margin: 0 auto 32px;
}
.bc-signal-stat {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 14px;
    backdrop-filter: blur(12px); transition: all 0.3s;
    position: relative; overflow: hidden;
}
.bc-signal-stat::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px; background: var(--stat-color, var(--bc-primary));
}
.bc-signal-stat:nth-child(1) { --stat-color: var(--bc-critical); }
.bc-signal-stat:nth-child(2) { --stat-color: var(--bc-high); }
.bc-signal-stat:nth-child(3) { --stat-color: var(--bc-normal); }
.bc-signal-stat:nth-child(4) { --stat-color: var(--bc-primary); }

.bc-signal-stat:hover {
    transform: translateY(-3px);
    border-color: var(--stat-color, var(--bc-primary));
    box-shadow: 0 12px 24px rgba(0,0,0,0.2);
}

.bc-stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, var(--bc-primary), var(--bc-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0; position: relative;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}
.bc-stat-critical { background: linear-gradient(135deg, var(--bc-critical), #DC2626); box-shadow: 0 4px 12px rgba(239,68,68,0.4); }
.bc-stat-high { background: linear-gradient(135deg, var(--bc-high), #D97706); box-shadow: 0 4px 12px rgba(245,158,11,0.4); }
.bc-stat-normal { background: linear-gradient(135deg, var(--bc-normal), #1D4ED8); box-shadow: 0 4px 12px rgba(59,130,246,0.4); }
.bc-stat-total { background: linear-gradient(135deg, var(--bc-primary), var(--bc-primary-dark)); }

.bc-stat-pulse {
    position: absolute; inset: -4px; border-radius: 12px;
    border: 2px solid currentColor; opacity: 0;
    animation: bcStatPulse 2s infinite;
}
@keyframes bcStatPulse {
    0% { transform: scale(0.9); opacity: 0.8; }
    100% { transform: scale(1.3); opacity: 0; }
}

.bc-stat-info { flex: 1; min-width: 0; }
.bc-stat-num {
    display: block; font-size: 1.4rem; font-weight: 900;
    color: #fff; line-height: 1; font-family: var(--bc-font-display);
}
.bc-stat-label {
    font-size: 0.68rem; color: rgba(255,255,255,.6);
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-top: 2px; display: block;
}

/* Signal Bars */
.bc-signal-bars {
    display: flex; gap: 2px; align-items: flex-end; height: 20px;
}
.bc-signal-bars span {
    width: 3px; border-radius: 1px;
    background: var(--stat-color, var(--bc-primary));
    animation: bcBarPulse 1.5s infinite ease-in-out;
}
.bc-signal-bars span:nth-child(1) { height: 20%; animation-delay: 0s; }
.bc-signal-bars span:nth-child(2) { height: 40%; animation-delay: 0.1s; }
.bc-signal-bars span:nth-child(3) { height: 60%; animation-delay: 0.2s; }
.bc-signal-bars span:nth-child(4) { height: 80%; animation-delay: 0.3s; }
.bc-signal-bars span:nth-child(5) { height: 100%; animation-delay: 0.4s; }
@keyframes bcBarPulse {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 1; }
}

.bc-bars-3 span:nth-child(4), .bc-bars-3 span:nth-child(5) { display: none; }
.bc-bars-4 span:nth-child(5) { display: none; }

/* Transmission Status */
.bc-transmission-status {
    display: flex; align-items: center; gap: 16px;
    max-width: 700px; margin: 0 auto;
    padding: 14px 20px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 50px;
    backdrop-filter: blur(12px);
}
.bc-tx-label {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.72rem; font-weight: 700; color: rgba(255,255,255,.9);
    letter-spacing: 1.5px; font-family: var(--bc-font-mono);
    white-space: nowrap;
}
.bc-tx-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--bc-critical);
    box-shadow: 0 0 10px var(--bc-critical);
    animation: bcStatusPulse 1s infinite;
}

.bc-tx-wave {
    flex: 1; display: flex; gap: 2px; align-items: center; height: 24px;
}
.bc-tx-wave span {
    flex: 1; background: linear-gradient(180deg, var(--bc-critical), var(--bc-high));
    border-radius: 2px; height: var(--wave-height);
    animation: bcWaveBar 1.2s infinite ease-in-out;
    animation-delay: var(--wave-delay);
    transform-origin: bottom;
}
@keyframes bcWaveBar {
    0%, 100% { transform: scaleY(0.3); }
    50% { transform: scaleY(1); }
}

.bc-tx-time {
    font-family: var(--bc-font-mono); font-weight: 700;
    font-size: 0.9rem; color: var(--bc-primary);
    white-space: nowrap;
}

/* Scroll Indicator */
.bc-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%;
    transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.bc-scroll-arrow { animation: bcArrowBounce 2s infinite; }
@keyframes bcArrowBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(10px); }
}

/* ===== MAIN SECTION ===== */
.bc-section { padding: 60px 20px 100px; background: var(--bc-gray-50); }
body.gl-dark .bc-section { background: #0B1120; }

.bc-layout {
    display: grid; grid-template-columns: 300px 1fr;
    gap: 32px; align-items: flex-start;
}

/* ===== SIDEBAR ===== */
.bc-sidebar {
    position: sticky; top: 100px;
    display: flex; flex-direction: column; gap: 20px;
}

/* Beacon Status */
.bc-beacon-status {
    background: linear-gradient(135deg, var(--bc-dark), #1E1B4B);
    color: #fff; border-radius: 20px; padding: 24px;
    text-align: center; position: relative; overflow: hidden;
    box-shadow: var(--bc-shadow-lg);
    transform-style: preserve-3d;
}
.bc-beacon-glow {
    position: absolute; top: -50%; left: 50%;
    transform: translateX(-50%);
    width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(16,185,129,0.2), transparent 60%);
    filter: blur(40px); pointer-events: none;
}

.bc-beacon-visual {
    margin-bottom: 16px; position: relative; z-index: 1;
}
.bc-tower-icon {
    width: 80px; height: 80px; border-radius: 50%;
    background: linear-gradient(135deg, var(--bc-primary), var(--bc-cyan));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 0 auto;
    position: relative;
    box-shadow: 0 8px 24px rgba(16,185,129,0.4);
}
.bc-tower-wave {
    position: absolute; inset: -8px; border-radius: 50%;
    border: 2px solid var(--bc-primary);
    animation: bcTowerWave 2s infinite;
}
.bc-wave-2 { animation-delay: 0.5s; }
.bc-wave-3 { animation-delay: 1s; }
@keyframes bcTowerWave {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

.bc-beacon-status h3 {
    font-size: 1.1rem; margin: 0 0 16px 0; font-weight: 800;
    font-family: var(--bc-font-display); position: relative; z-index: 1;
    letter-spacing: 1px;
}

.bc-beacon-info {
    display: flex; flex-direction: column; gap: 10px;
    position: relative; z-index: 1;
}
.bc-info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 12px; background: rgba(255,255,255,0.05);
    border-radius: 8px; font-size: 0.8rem;
}
.bc-info-row span { color: rgba(255,255,255,0.6); }
.bc-info-row strong {
    color: #fff; font-family: var(--bc-font-mono);
    font-size: 0.78rem;
}
.bc-status-online {
    display: inline-flex; align-items: center; gap: 6px;
    color: var(--bc-primary) !important;
}
.bc-online-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--bc-primary);
    box-shadow: 0 0 8px var(--bc-primary);
    animation: bcStatusPulse 1.5s infinite;
}

/* Priority Filter */
.bc-priority-filter {
    background: #fff; border-radius: 20px; padding: 20px;
    box-shadow: var(--bc-shadow-sm); border: 1px solid var(--bc-gray-100);
}
body.gl-dark .bc-priority-filter { background: #1E293B; border-color: rgba(255,255,255,.08); }

.bc-priority-filter h4 {
    font-size: 0.85rem; font-weight: 800; color: var(--bc-dark);
    margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;
    text-transform: uppercase; letter-spacing: 1px;
}
body.gl-dark .bc-priority-filter h4 { color: #F1F5F9; }
.bc-priority-filter h4 i { color: var(--bc-critical); }

.bc-priority-list { display: flex; flex-direction: column; gap: 6px; }
.bc-priority-btn {
    width: 100%; display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; background: var(--bc-gray-50);
    border: 1px solid transparent; border-radius: 10px;
    cursor: pointer; font-family: inherit; font-size: 0.85rem;
    color: var(--bc-gray-700); transition: all 0.3s;
}
body.gl-dark .bc-priority-btn { background: rgba(255,255,255,.03); color: #CBD5E1; }
.bc-priority-btn:hover {
    border-color: var(--bc-primary); background: var(--bc-primary-light);
    color: var(--bc-primary-dark); transform: translateX(4px);
}
body.gl-dark .bc-priority-btn:hover { background: rgba(16,185,129,0.1); color: #34D399; }
.bc-priority-btn.active {
    background: var(--bc-primary); color: #fff;
    border-color: var(--bc-primary);
}

.bc-priority-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
}
.bc-dot-all { background: linear-gradient(135deg, var(--bc-critical), var(--bc-high), var(--bc-normal)); }
.bc-dot-critical { background: var(--bc-critical); box-shadow: 0 0 8px var(--bc-critical); }
.bc-dot-high { background: var(--bc-high); box-shadow: 0 0 8px var(--bc-high); }
.bc-dot-normal { background: var(--bc-normal); box-shadow: 0 0 8px var(--bc-normal); }

.bc-priority-btn span:first-of-type { flex: 1; text-align: left; font-weight: 600; }

.bc-priority-count {
    padding: 2px 8px; background: rgba(16,185,129,0.15);
    color: var(--bc-primary); border-radius: 10px;
    font-size: 0.7rem; font-weight: 700;
    font-family: var(--bc-font-mono);
}
.bc-priority-btn.active .bc-priority-count {
    background: rgba(255,255,255,0.2); color: #fff;
}
.bc-count-critical { background: rgba(239,68,68,0.15); color: var(--bc-critical); }
.bc-count-high { background: rgba(245,158,11,0.15); color: var(--bc-high); }
.bc-count-normal { background: rgba(59,130,246,0.15); color: var(--bc-normal); }

/* Signal Log */
.bc-signal-log {
    background: #fff; border-radius: 20px; padding: 20px;
    box-shadow: var(--bc-shadow-sm); border: 1px solid var(--bc-gray-100);
}
body.gl-dark .bc-signal-log { background: #1E293B; border-color: rgba(255,255,255,.08); }

.bc-signal-log h4 {
    font-size: 0.85rem; font-weight: 800; color: var(--bc-dark);
    margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;
    text-transform: uppercase; letter-spacing: 1px;
}
body.gl-dark .bc-signal-log h4 { color: #F1F5F9; }
.bc-signal-log h4 i { color: var(--bc-high); }

.bc-log-entries { display: flex; flex-direction: column; gap: 10px; }
.bc-log-entry {
    display: flex; align-items: center; gap: 10px;
    padding: 10px; background: var(--bc-gray-50);
    border-radius: 8px; border-left: 3px solid var(--bc-primary);
}
body.gl-dark .bc-log-entry { background: rgba(255,255,255,.03); }
.bc-log-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.bc-dot-online { background: var(--bc-primary); box-shadow: 0 0 6px var(--bc-primary); }
.bc-dot-broadcast { background: var(--bc-high); box-shadow: 0 0 6px var(--bc-high); }
.bc-dot-scan { background: var(--bc-cyan); box-shadow: 0 0 6px var(--bc-cyan); }

.bc-log-entry strong {
    display: block; font-size: 0.82rem; color: var(--bc-dark);
    margin-bottom: 2px;
}
body.gl-dark .bc-log-entry strong { color: #F1F5F9; }
.bc-log-entry small {
    font-size: 0.7rem; color: var(--bc-gray-600);
}

/* Emergency Card */
.bc-emergency-card {
    background: linear-gradient(135deg, var(--bc-critical), #DC2626);
    color: #fff; border-radius: 20px; padding: 24px;
    text-align: center; position: relative; overflow: hidden;
}
.bc-emergency-bg {
    position: absolute; top: -30%; right: -30%;
    font-size: 8rem; opacity: 0.1;
    transform: rotate(-15deg); pointer-events: none;
}
.bc-emergency-content { position: relative; z-index: 1; }
.bc-emergency-content > i {
    font-size: 2rem; margin-bottom: 12px; display: block;
}
.bc-emergency-card h4 {
    font-size: 1.1rem; margin: 0 0 8px 0; font-weight: 800;
}
.bc-emergency-card p {
    font-size: 0.85rem; opacity: 0.9; margin-bottom: 16px;
}
.bc-emergency-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 20px; background: #fff; color: var(--bc-critical);
    border-radius: 50px; text-decoration: none; font-weight: 700;
    font-size: 0.88rem; transition: all 0.3s; width: 100%;
}
.bc-emergency-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

/* ===== MAIN CONTENT ===== */
.bc-main { min-width: 0; }

/* Console */
.bc-console {
    background: #fff; border-radius: 20px;
    box-shadow: var(--bc-shadow-md); overflow: hidden;
    border: 1px solid var(--bc-gray-100); margin-bottom: 28px;
}
body.gl-dark .bc-console { background: #1E293B; border-color: rgba(255,255,255,.08); }

.bc-console-header {
    background: var(--bc-dark); padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
    color: #94A3B8; font-family: var(--bc-font-mono); font-size: 0.8rem;
}
.bc-console-dots { display: flex; gap: 5px; }
.bc-console-dots .dot { width: 11px; height: 11px; border-radius: 50%; }
.bc-console-dots .red { background: #FF5F56; }
.bc-console-dots .yellow { background: #FFBD2E; }
.bc-console-dots .green { background: #27C93F; }
.bc-console-header i { color: var(--bc-critical); }
.bc-console-header span { color: #E2E8F0; }
.bc-console-status {
    margin-left: auto; color: var(--bc-primary);
    font-size: 0.7rem; letter-spacing: 1px;
    animation: bcBlink 2s infinite;
}

.bc-console-body { padding: 20px; }

.bc-search-wrap {
    position: relative; display: flex; align-items: center;
    margin-bottom: 14px;
}
.bc-search-wrap > i {
    position: absolute; left: 18px; top: 50%;
    transform: translateY(-50%); color: var(--bc-gray-600);
    z-index: 1; font-size: 1rem;
}
.bc-search-wrap input {
    width: 100%; padding: 14px 100px 14px 50px;
    border: 1.5px solid var(--bc-gray-200); border-radius: 12px;
    background: var(--bc-gray-50); font-family: inherit;
    font-size: 0.95rem; color: var(--bc-dark);
    transition: all 0.3s;
}
body.gl-dark .bc-search-wrap input {
    background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1); color: #E2E8F0;
}
.bc-search-wrap input:focus {
    outline: none; border-color: var(--bc-critical);
    box-shadow: 0 0 0 4px rgba(239,68,68,0.15);
}

.bc-search-hint {
    position: absolute; right: 50px; top: 50%;
    transform: translateY(-50%);
    display: flex; gap: 3px; align-items: center;
    color: var(--bc-gray-600); font-size: 0.72rem;
    pointer-events: none;
}
.bc-search-hint kbd {
    background: var(--bc-gray-100); padding: 2px 6px;
    border-radius: 4px; font-family: var(--bc-font-mono);
    font-size: 0.68rem; border: 1px solid var(--bc-gray-200);
}
body.gl-dark .bc-search-hint kbd {
    background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.1); color: #CBD5E1;
}

.bc-search-clear {
    position: absolute; right: 14px; top: 50%;
    transform: translateY(-50%);
    width: 30px; height: 30px; border-radius: 50%;
    background: var(--bc-gray-100); border: none;
    color: var(--bc-gray-600); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s; font-size: 0.85rem;
}
body.gl-dark .bc-search-clear { background: rgba(255,255,255,.1); color: #CBD5E1; }
.bc-search-clear:hover {
    background: var(--bc-critical); color: #fff;
    transform: translateY(-50%) rotate(90deg);
}

.bc-console-meta {
    display: flex; justify-content: space-between; align-items: center;
    font-size: 0.82rem; flex-wrap: wrap; gap: 10px;
}
.bc-meta-info {
    color: var(--bc-gray-600); display: flex; align-items: center; gap: 8px;
}
body.gl-dark .bc-meta-info { color: #94A3B8; }
.bc-meta-info i { color: var(--bc-critical); }
.bc-meta-info strong {
    color: var(--bc-dark); font-family: var(--bc-font-display);
    font-size: 1rem; font-weight: 800;
}
body.gl-dark .bc-meta-info strong { color: #F1F5F9; }

.bc-meta-time {
    color: var(--bc-gray-600); display: flex; align-items: center; gap: 8px;
    font-family: var(--bc-font-mono); font-size: 0.75rem;
}
body.gl-dark .bc-meta-time { color: #94A3B8; }
.bc-meta-time i { color: var(--bc-high); }

/* ===== SIGNAL CARDS ===== */
.bc-signals {
    display: flex; flex-direction: column; gap: 20px;
}

.bc-signal-card {
    position: relative; background: #fff; border-radius: 16px;
    padding: 24px; padding-left: 32px;
    border: 1px solid var(--bc-gray-100);
    box-shadow: var(--bc-shadow-sm); overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
body.gl-dark .bc-signal-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

/* Signal Pulse Effect */
.bc-signal-pulse {
    position: absolute; top: 20px; left: 12px;
    width: 12px; height: 12px; border-radius: 50%;
    background: var(--signal-color, var(--bc-primary));
    box-shadow: 0 0 10px var(--signal-color, var(--bc-primary));
}
.bc-signal-pulse::before {
    content: ''; position: absolute; inset: -4px;
    border-radius: 50%; border: 2px solid var(--signal-color, var(--bc-primary));
    animation: bcCardPulse 2s infinite;
}
@keyframes bcCardPulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(2); opacity: 0; }
}

/* Priority Side Bar */
.bc-signal-bar {
    position: absolute; left: 0; top: 0; bottom: 0;
    width: 4px; background: var(--signal-color, var(--bc-primary));
    transition: width 0.3s;
}
.bc-signal-card:hover .bc-signal-bar { width: 6px; }

/* Corner Brackets */
.bc-bracket {
    position: absolute; width: 20px; height: 20px;
    border: 2px solid var(--signal-color, var(--bc-primary));
    opacity: 0; transition: opacity 0.3s; pointer-events: none;
}
.bc-bracket-tl { top: 8px; left: 28px; border-right: none; border-bottom: none; }
.bc-bracket-tr { top: 8px; right: 8px; border-left: none; border-bottom: none; }
.bc-bracket-bl { bottom: 8px; left: 28px; border-right: none; border-top: none; }
.bc-bracket-br { bottom: 8px; right: 8px; border-left: none; border-top: none; }
.bc-signal-card:hover .bc-bracket { opacity: 1; }

.bc-signal-card::before {
    content: ''; position: absolute; inset: 0; border-radius: 16px; padding: 1px;
    background: conic-gradient(from 0deg, 
        transparent, 
        var(--signal-color, var(--bc-primary)),
        transparent 40%
    );
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none;
}
.bc-signal-card:hover::before { opacity: 1; animation: bcRotateBorder 4s linear infinite; }
@keyframes bcRotateBorder { to { transform: rotate(360deg); } }

.bc-signal-card:hover {
    transform: translateX(6px);
    box-shadow: var(--bc-shadow-md), 0 0 30px color-mix(in srgb, var(--signal-color, var(--bc-primary)) 20%, transparent);
    border-color: var(--signal-color, var(--bc-primary));
}

/* Signal Header */
.bc-signal-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 16px; margin-bottom: 16px; flex-wrap: wrap;
}

.bc-signal-meta-left {
    display: flex; align-items: center; gap: 12px;
}

.bc-signal-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0; position: relative;
}
.bc-icon-alert {
    position: absolute; inset: -4px; border-radius: 14px;
    border: 2px solid var(--bc-critical);
    animation: bcAlertPulse 1s infinite;
}
@keyframes bcAlertPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.5; }
}

.bc-signal-info { display: flex; flex-direction: column; gap: 2px; }
.bc-signal-priority {
    font-size: 0.7rem; font-weight: 800; letter-spacing: 1.5px;
    font-family: var(--bc-font-mono); display: flex; align-items: center; gap: 6px;
}
.bc-signal-priority i { font-size: 0.7rem; }
.bc-signal-frequency {
    font-size: 0.68rem; color: var(--bc-gray-600);
    font-family: var(--bc-font-mono); letter-spacing: 0.5px;
}
body.gl-dark .bc-signal-frequency { color: #94A3B8; }

.bc-signal-meta-right {
    display: flex; flex-direction: column; align-items: flex-end; gap: 4px;
}
.bc-signal-id {
    font-size: 0.7rem; font-weight: 700; color: var(--bc-gray-600);
    font-family: var(--bc-font-mono); letter-spacing: 1px;
    padding: 3px 10px; background: var(--bc-gray-100); border-radius: 4px;
}
body.gl-dark .bc-signal-id { background: rgba(255,255,255,.08); color: #94A3B8; }

.bc-signal-time {
    font-size: 0.75rem; color: var(--bc-gray-600);
    display: flex; align-items: center; gap: 5px;
}
body.gl-dark .bc-signal-time { color: #94A3B8; }
.bc-signal-time i { color: var(--signal-color, var(--bc-primary)); font-size: 0.75rem; }

/* Signal Body */
.bc-signal-body {
    display: flex; gap: 20px; margin-bottom: 16px;
}

.bc-signal-date-badge {
    width: 70px; height: 70px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--signal-color, var(--bc-primary)), color-mix(in srgb, var(--signal-color, var(--bc-primary)) 70%, #000));
    color: #fff; border-radius: 14px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    box-shadow: 0 4px 12px color-mix(in srgb, var(--signal-color, var(--bc-primary)) 30%, transparent);
}
.bc-date-day {
    font-size: 1.6rem; font-weight: 900; line-height: 1;
    font-family: var(--bc-font-display);
}
.bc-date-month {
    font-size: 0.65rem; font-weight: 700; letter-spacing: 1.5px;
    margin-top: 4px; text-transform: uppercase;
}

.bc-signal-content { flex: 1; min-width: 0; }
.bc-signal-title {
    font-size: 1.2rem; font-weight: 800; line-height: 1.3;
    color: var(--bc-dark); margin: 0 0 10px 0;
    font-family: var(--bc-font-display);
    transition: color 0.3s;
}
body.gl-dark .bc-signal-title { color: #F1F5F9; }
.bc-signal-card:hover .bc-signal-title { color: var(--signal-color, var(--bc-primary)); }

.bc-signal-desc {
    font-size: 0.92rem; color: var(--bc-gray-600); line-height: 1.7;
    margin: 0; white-space: pre-line;
}
body.gl-dark .bc-signal-desc { color: #94A3B8; }

/* Signal Footer */
.bc-signal-footer {
    display: flex; align-items: center; gap: 16px;
    padding-top: 16px; border-top: 1px dashed var(--bc-gray-200);
    flex-wrap: wrap;
}
body.gl-dark .bc-signal-footer { border-color: rgba(255,255,255,.08); }

.bc-signal-strength {
    display: flex; align-items: center; gap: 8px;
}
.bc-strength-label {
    font-size: 0.72rem; color: var(--bc-gray-600); font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.5px;
}
body.gl-dark .bc-strength-label { color: #94A3B8; }

.bc-strength-bars {
    display: flex; gap: 3px; align-items: flex-end; height: 16px;
}
.bc-strength-bars span {
    width: 4px; border-radius: 1px; height: 100%;
    animation: bcStrengthPulse 1.5s infinite ease-in-out;
}
.bc-strength-bars span:nth-child(1) { height: 25%; animation-delay: 0s; }
.bc-strength-bars span:nth-child(2) { height: 50%; animation-delay: 0.1s; }
.bc-strength-bars span:nth-child(3) { height: 75%; animation-delay: 0.2s; }
.bc-strength-bars span:nth-child(4) { height: 90%; animation-delay: 0.3s; }
.bc-strength-bars span:nth-child(5) { height: 100%; animation-delay: 0.4s; }
.bc-bar-inactive { background: var(--bc-gray-200) !important; opacity: 0.4; }
body.gl-dark .bc-bar-inactive { background: rgba(255,255,255,.1) !important; }
@keyframes bcStrengthPulse {
    0%, 100% { opacity: 0.7; }
    50% { opacity: 1; }
}

.bc-signal-expiry, .bc-signal-permanent {
    font-size: 0.78rem; color: var(--bc-gray-600);
    display: flex; align-items: center; gap: 6px;
    padding: 4px 10px; background: var(--bc-gray-100);
    border-radius: 20px;
}
body.gl-dark .bc-signal-expiry, body.gl-dark .bc-signal-permanent { 
    background: rgba(255,255,255,.05); color: #94A3B8; 
}
.bc-signal-expiry i { color: var(--bc-high); font-size: 0.75rem; }
.bc-signal-permanent i { color: var(--bc-primary); font-size: 0.75rem; }

.bc-signal-action {
    margin-left: auto; width: 36px; height: 36px;
    border-radius: 50%; background: var(--bc-gray-100);
    border: 1px solid var(--bc-gray-200); color: var(--bc-gray-600);
    cursor: pointer; transition: all 0.3s;
    display: flex; align-items: center; justify-content: center;
}
body.gl-dark .bc-signal-action { 
    background: rgba(255,255,255,.05); 
    border-color: rgba(255,255,255,.1); 
    color: #CBD5E1; 
}
.bc-signal-action:hover {
    background: var(--signal-color, var(--bc-primary));
    color: #fff; border-color: var(--signal-color, var(--bc-primary));
    transform: rotate(90deg) scale(1.1);
}

/* ===== NO RESULTS ===== */
.bc-no-results {
    text-align: center; padding: 60px 20px;
    background: #fff; border-radius: 20px;
    border: 2px dashed var(--bc-gray-200);
}
body.gl-dark .bc-no-results { background: #1E293B; border-color: rgba(255,255,255,.1); }

.bc-no-results-icon {
    position: relative; width: 100px; height: 100px;
    margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: var(--bc-gray-600);
}
.bc-no-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--bc-critical);
    animation: bcNoPulse 2s infinite;
}
.bc-pulse-2 { animation-delay: 1s; }
@keyframes bcNoPulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

.bc-no-results h3 {
    font-size: 1.4rem; margin: 0 0 8px 0; color: var(--bc-dark);
    font-weight: 800; font-family: var(--bc-font-display);
}
body.gl-dark .bc-no-results h3 { color: #F1F5F9; }
.bc-no-results p { color: var(--bc-gray-600); margin-bottom: 20px; }
body.gl-dark .bc-no-results p { color: #94A3B8; }

/* Buttons */
.bc-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 24px; border-radius: 12px; font-weight: 700;
    font-size: 0.9rem; text-decoration: none; border: none;
    cursor: pointer; transition: all 0.3s; font-family: inherit;
}
.bc-btn-primary {
    background: linear-gradient(135deg, var(--bc-critical), #DC2626);
    color: #fff; box-shadow: 0 4px 16px rgba(239,68,68,0.3);
}
.bc-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(239,68,68,0.4); }

/* ===== EMPTY STATE ===== */
.bc-empty-state {
    position: relative; text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px; overflow: hidden;
    border: 2px dashed var(--bc-gray-200);
}
body.gl-dark .bc-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }

.bc-empty-bg { position: absolute; inset: 0; pointer-events: none; }
.bc-empty-radar {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 400px; height: 400px; border-radius: 50%;
    background: conic-gradient(from 0deg, 
        transparent 0deg, 
        rgba(16,185,129,0.1) 30deg, 
        transparent 60deg
    );
    animation: bcEmptyRadar 4s linear infinite;
}
@keyframes bcEmptyRadar { to { transform: translate(-50%, -50%) rotate(360deg); } }

.bc-empty-content { position: relative; z-index: 1; }
.bc-empty-icon {
    position: relative; width: 120px; height: 120px;
    margin: 0 auto 24px; display: flex; align-items: center; justify-content: center;
    font-size: 4rem; color: var(--bc-gray-600);
}
.bc-empty-wave {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--bc-primary);
    animation: bcEmptyWave 2s infinite;
}
@keyframes bcEmptyWave {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

.bc-empty-state h3 {
    font-size: 1.5rem; margin: 0 0 8px 0; color: var(--bc-dark);
    font-weight: 800; font-family: var(--bc-font-display);
}
body.gl-dark .bc-empty-state h3 { color: #F1F5F9; }
.bc-empty-state p { color: var(--bc-gray-600); margin-bottom: 20px; max-width: 400px; margin-left: auto; margin-right: auto; }

.bc-empty-status {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 16px; background: var(--bc-gray-100);
    border-radius: 50px; font-size: 0.82rem; color: var(--bc-gray-600);
    font-weight: 600;
}
body.gl-dark .bc-empty-status { background: rgba(255,255,255,.05); color: #94A3B8; }
.bc-empty-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--bc-primary);
    box-shadow: 0 0 8px var(--bc-primary);
    animation: bcStatusPulse 1.5s infinite;
}

/* ===== TOAST ===== */
.bc-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--bc-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    z-index: 10001; opacity: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.9rem; border: 1px solid rgba(255,255,255,0.1);
}
body.gl-dark .bc-toast { background: #fff; color: var(--bc-dark); }
.bc-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.bc-toast i { 
    color: var(--bc-primary);
    font-size: 1.2rem;
}

/* ===== BACK TO TOP ===== */
.bc-back-top {
    position: fixed; bottom: 30px; right: 30px;
    width: 50px; height: 50px; border-radius: 50%;
    background: linear-gradient(135deg, var(--bc-critical), var(--bc-high));
    border: none; color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; z-index: 998;
    box-shadow: 0 8px 24px rgba(239,68,68,0.4);
    opacity: 0; visibility: hidden; transform: translateY(20px) scale(.8);
    transition: all .4s;
}
.bc-back-top.show { opacity: 1; visibility: visible; transform: none; }
.bc-back-top:hover { transform: translateY(-4px) scale(1.05); }

/* ===== ANIMATIONS ===== */
.fade-up {
    opacity: 0; transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .bc-layout { grid-template-columns: 1fr; }
    .bc-sidebar {
        position: static; order: 2;
        display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
    }
    .bc-beacon-status { grid-column: span 2; }
    .bc-emergency-card { grid-column: span 2; }
    .bc-main { order: 1; }
    .bc-signal-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .bc-hero { padding: 120px 20px 80px; min-height: 85vh; }
    .bc-hero-title { font-size: 3rem; }
    .bc-signal-stats { grid-template-columns: 1fr; gap: 10px; }
    .bc-signal-stat { justify-content: center; }
    .bc-transmission-status { flex-direction: column; border-radius: 16px; padding: 14px; }
    .bc-tx-wave { width: 100%; }
    .bc-floating-elements { display: none; }
    .bc-console-body { padding: 16px; }
    .bc-search-hint { display: none; }
    .bc-search-wrap input { padding-right: 50px; }
    .bc-console-meta { flex-direction: column; gap: 8px; align-items: flex-start; }
    .bc-signal-body { flex-direction: column; gap: 12px; }
    .bc-signal-date-badge { width: 60px; height: 60px; }
    .bc-date-day { font-size: 1.3rem; }
    .bc-signal-footer { flex-direction: column; align-items: flex-start; gap: 10px; }
    .bc-signal-action { margin-left: 0; align-self: flex-end; }
    .bc-sidebar { grid-template-columns: 1fr; }
    .bc-beacon-status, .bc-emergency-card { grid-column: span 1; }
    .bc-back-top { width: 44px; height: 44px; bottom: 20px; right: 20px; }
}
@media (max-width: 480px) {
    .bc-hero-title { font-size: 2.3rem; }
    .bc-hero-badge { font-size: 0.7rem; padding: 8px 16px; letter-spacing: 1px; flex-wrap: wrap; justify-content: center; }
    .bc-signal-card { padding: 18px; padding-left: 26px; }
    .bc-signal-title { font-size: 1.05rem; }
    .bc-stat-num { font-size: 1.2rem; }
    .bc-bracket { display: none; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ===== 1. Radar Canvas Animation =====
    const canvas = document.getElementById('bcRadarCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let width, height, time = 0;
        let blips = [];

        function resize() {
            width = canvas.width = canvas.parentElement.offsetWidth;
            height = canvas.height = canvas.parentElement.offsetHeight;
            initBlips();
        }

        function initBlips() {
            blips = [];
            const centerX = width / 2;
            const centerY = height / 2;
            const maxRadius = Math.min(width, height) * 0.4;
            
            for (let i = 0; i < 15; i++) {
                const angle = Math.random() * Math.PI * 2;
                const distance = Math.random() * maxRadius;
                blips.push({
                    x: centerX + Math.cos(angle) * distance,
                    y: centerY + Math.sin(angle) * distance,
                    size: Math.random() * 3 + 2,
                    opacity: 0,
                    maxOpacity: Math.random() * 0.6 + 0.4,
                    color: ['#10B981', '#EF4444', '#F59E0B', '#3B82F6'][Math.floor(Math.random() * 4)],
                    phase: Math.random() * Math.PI * 2,
                    angle: angle,
                    distance: distance
                });
            }
        }

        function drawRadar() {
            ctx.clearRect(0, 0, width, height);
            const centerX = width / 2;
            const centerY = height / 2;
            const maxRadius = Math.min(width, height) * 0.4;

            // Draw concentric circles
            for (let i = 1; i <= 4; i++) {
                const radius = (maxRadius / 4) * i;
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                ctx.strokeStyle = `rgba(16, 185, 129, ${0.15 - i * 0.02})`;
                ctx.lineWidth = 1;
                ctx.stroke();
            }

            // Draw cross lines
            ctx.beginPath();
            ctx.moveTo(centerX - maxRadius, centerY);
            ctx.lineTo(centerX + maxRadius, centerY);
            ctx.moveTo(centerX, centerY - maxRadius);
            ctx.lineTo(centerX, centerY + maxRadius);
            ctx.strokeStyle = 'rgba(16, 185, 129, 0.1)';
            ctx.lineWidth = 1;
            ctx.stroke();

            // Draw radar sweep
            const sweepAngle = (time * 0.02) % (Math.PI * 2);
            
            // Sweep gradient
            const gradient = ctx.createConicGradient(sweepAngle - Math.PI / 2, centerX, centerY);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
            gradient.addColorStop(0.1, 'rgba(16, 185, 129, 0.1)');
            gradient.addColorStop(0.2, 'transparent');
            gradient.addColorStop(1, 'transparent');
            
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, maxRadius, sweepAngle - 0.5, sweepAngle);
            ctx.closePath();
            ctx.fillStyle = gradient;
            ctx.fill();

            // Draw sweep line
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.lineTo(
                centerX + Math.cos(sweepAngle) * maxRadius,
                centerY + Math.sin(sweepAngle) * maxRadius
            );
            ctx.strokeStyle = 'rgba(16, 185, 129, 0.8)';
            ctx.lineWidth = 2;
            ctx.shadowBlur = 15;
            ctx.shadowColor = '#10B981';
            ctx.stroke();
            ctx.shadowBlur = 0;

            // Draw blips (detected signals)
            blips.forEach(blip => {
                // Calculate if sweep just passed this blip
                const blipAngle = Math.atan2(blip.y - centerY, blip.x - centerX);
                let angleDiff = sweepAngle - blipAngle;
                if (angleDiff < 0) angleDiff += Math.PI * 2;
                
                // Blip fades after sweep passes
                if (angleDiff < 0.5) {
                    blip.opacity = blip.maxOpacity;
                } else {
                    blip.opacity = Math.max(0, blip.opacity - 0.01);
                }

                if (blip.opacity > 0) {
                    ctx.beginPath();
                    ctx.arc(blip.x, blip.y, blip.size, 0, Math.PI * 2);
                    ctx.fillStyle = blip.color;
                    ctx.globalAlpha = blip.opacity;
                    ctx.shadowBlur = 10;
                    ctx.shadowColor = blip.color;
                    ctx.fill();
                    ctx.globalAlpha = 1;
                    ctx.shadowBlur = 0;

                    // Ping ring
                    if (angleDiff < 0.3) {
                        ctx.beginPath();
                        ctx.arc(blip.x, blip.y, blip.size + (angleDiff * 30), 0, Math.PI * 2);
                        ctx.strokeStyle = blip.color;
                        ctx.globalAlpha = blip.opacity * 0.5;
                        ctx.lineWidth = 1;
                        ctx.stroke();
                        ctx.globalAlpha = 1;
                    }
                }
            });

            // Center point
            ctx.beginPath();
            ctx.arc(centerX, centerY, 4, 0, Math.PI * 2);
            ctx.fillStyle = '#10B981';
            ctx.shadowBlur = 15;
            ctx.shadowColor = '#10B981';
            ctx.fill();
            ctx.shadowBlur = 0;

            time++;
            requestAnimationFrame(drawRadar);
        }

        resize();
        window.addEventListener('resize', resize);
        drawRadar();
    }

    // ===== 2. Typing Effect =====
    const textElement = document.getElementById('bcTypingText');
    if (textElement) {
        const phrases = [
            "Sinyal pengumuman resmi dari Program Studi Informatika.",
            "Informasi penting tentang akademik, pendaftaran, dan kegiatan kampus.",
            "Pastikan Anda tidak melewatkan satupun transmisi penting.",
            "Beacon kami memancarkan informasi 24/7 untuk Anda."
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

    // ===== 3. Counter Animation =====
    const counters = document.querySelectorAll('.bc-stat-num[data-count]');
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

    // ===== 4. Reading Progress Bar =====
    const progressFill = document.getElementById('bcProgressFill');
    if (progressFill) {
        window.addEventListener('scroll', () => {
            const scrolled = window.scrollY;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const percent = maxScroll > 0 ? (scrolled / maxScroll) * 100 : 0;
            progressFill.style.width = percent + '%';
        }, { passive: true });
    }

    // ===== 5. Live Clock =====
    function updateClock() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        
        const liveTime = document.getElementById('bcLiveTime');
        const lastBroadcast = document.getElementById('bcLastBroadcast');
        const lastScan = document.getElementById('bcLastScan');
        
        if (liveTime) liveTime.textContent = `${h}:${m}:${s}`;
        if (lastBroadcast) lastBroadcast.textContent = `${h}:${m}`;
        if (lastScan) lastScan.textContent = `${h}:${m}:${s}`;
    }
    updateClock();
    setInterval(updateClock, 1000);

    // ===== 6. 3D Tilt Effect =====
    const tiltElements = document.querySelectorAll('.bc-tilt');
    tiltElements.forEach(el => {
        if (!el) return;
        el.addEventListener('mousemove', (e) => {
            const rect = el.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = ((y - centerY) / centerY) * -3;
            const rotateY = ((x - centerX) / centerX) * 3;
            el.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });
        el.addEventListener('mouseleave', () => {
            el.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        });
    });

    // ===== 7. Scroll Reveal =====
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

    // ===== 8. Filter & Search Logic =====
    const searchInput = document.getElementById('bcSearchInput');
    const searchClear = document.getElementById('bcSearchClear');
    const filterBtns = document.querySelectorAll('.bc-priority-btn');
    const signals = document.querySelectorAll('.bc-signal-card');
    const noResults = document.getElementById('bcNoResults');
    const signalsContainer = document.getElementById('bcSignals');
    const resultsCount = document.getElementById('bcResultsCount');

    let currentFilter = 'all';
    let currentSearch = '';

    function applyFilters() {
        let visibleCount = 0;

        signals.forEach(signal => {
            const priority = signal.getAttribute('data-priority') || '';
            const title = signal.getAttribute('data-title') || '';
            const content = signal.getAttribute('data-content') || '';

            const matchesFilter = currentFilter === 'all' || priority === currentFilter;
            const matchesSearch = !currentSearch || title.includes(currentSearch) || content.includes(currentSearch);

            if (matchesFilter && matchesSearch) {
                signal.style.display = '';
                visibleCount++;
            } else {
                signal.style.display = 'none';
            }
        });

        if (resultsCount) resultsCount.textContent = visibleCount;

        if (noResults && signalsContainer) {
            if (visibleCount === 0 && (currentSearch || currentFilter !== 'all')) {
                noResults.style.display = 'block';
                signalsContainer.style.display = 'none';
            } else {
                noResults.style.display = 'none';
                signalsContainer.style.display = '';
            }
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value.toLowerCase().trim();
            if (searchClear) searchClear.style.display = currentSearch ? 'flex' : 'none';
            applyFilters();
        });
    }

    if (searchClear) {
        searchClear.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
                currentSearch = '';
                searchClear.style.display = 'none';
                searchInput.focus();
                applyFilters();
            }
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.getAttribute('data-filter') || 'all';
            applyFilters();
        });
    });

    window.bcResetFilters = function() {
        if (searchInput) {
            searchInput.value = '';
            currentSearch = '';
            if (searchClear) searchClear.style.display = 'none';
        }
        filterBtns.forEach(b => b.classList.remove('active'));
        const allBtn = document.querySelector('.bc-priority-btn[data-filter="all"]');
        if (allBtn) allBtn.classList.add('active');
        currentFilter = 'all';
        applyFilters();
        bcShowToast('📡 Receiver retuned!');
    };

    // ===== 9. Keyboard Shortcuts =====
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) searchInput.focus();
        }
        if (e.key === 'Escape') {
            bcResetFilters();
        }
    });

    // ===== 10. Share Signal =====
    window.bcShareSignal = function(btn, title) {
        const url = window.location.href;
        if (navigator.share) {
            navigator.share({
                title: title,
                text: 'Pengumuman resmi dari Informatika Pro',
                url: url
            }).catch(err => console.log('Share cancelled'));
        } else {
            navigator.clipboard.writeText(url).then(() => {
                bcShowToast('🔗 Link copied to clipboard!');
            });
        }
    };

    // ===== 11. Toast =====
    window.bcShowToast = function(message) {
        const toast = document.getElementById('bcToast');
        if (!toast) return;
        const msgEl = document.getElementById('bcToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // ===== 12. Back to Top =====
    const backTop = document.getElementById('bcBackTop');
    if (backTop) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 600) backTop.classList.add('show');
            else backTop.classList.remove('show');
        }, { passive: true });
        backTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ===== 13. Parallax Effect =====
    const heroSection = document.querySelector('.bc-hero');
    if (heroSection && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const tower = heroSection.querySelector('.bc-beacon-tower');
            if (tower) {
                tower.style.transform = `translate(-50%, calc(-50% + ${scrolled * 0.3}px))`;
            }
        }, { passive: true });
    }

    // Welcome toast
    setTimeout(() => {
        bcShowToast('📡 Beacon online - receiving signals...');
    }, 1500);

    // Console Easter Egg
    console.log('%c╔══════════════════════════════════════════╗', 'color:#EF4444;font-weight:bold');
    console.log('%c║   BEACON - Official Signal Dispatch      ║', 'color:#EF4444;font-weight:bold;font-size:14px');
    console.log('%c╚══════════════════════════════════════════╝', 'color:#EF4444;font-weight:bold');
    console.log('%c📡 ' + document.querySelectorAll('.bc-signal-card').length + ' signals received', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>