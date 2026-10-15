<?php
require_once 'includes/init_security.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Agenda & Kegiatan';
$pageDesc = 'Kalender kegiatan akademik, seminar, workshop, dan acara penting Program Studi Informatika.';

$filterKategori = isset($_GET['kategori']) ? clean($_GET['kategori']) : '';
$filterStatus = isset($_GET['status']) ? clean($_GET['status']) : 'akan_datang';
$searchQuery = isset($_GET['q']) ? clean($_GET['q']) : '';

try {
    $db = getDB();
    $where = "1=1";
    $params = [];
    
    if ($filterKategori) {
        $where .= " AND kategori = ?";
        $params[] = $filterKategori;
    }
    if ($filterStatus && $filterStatus !== 'semua') {
        $where .= " AND status = ?";
        $params[] = $filterStatus;
    }
    if ($searchQuery) {
        $where .= " AND (judul LIKE ? OR deskripsi LIKE ? OR lokasi LIKE ?)";
        $searchParam = '%' . $searchQuery . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // QUERY ASLI DIPERTAHANKAN 100%
    $sql = "SELECT * FROM agenda WHERE $where ORDER BY tanggal_mulai DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $agendaList = $stmt->fetchAll();
    
    $cats = $db->query("SELECT DISTINCT kategori FROM agenda WHERE kategori IS NOT NULL ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);
    
    $stats = [
        'total' => (int)$db->query("SELECT COUNT(*) FROM agenda")->fetchColumn(),
        'akan_datang' => (int)$db->query("SELECT COUNT(*) FROM agenda WHERE status='akan_datang'")->fetchColumn(),
        'berlangsung' => (int)$db->query("SELECT COUNT(*) FROM agenda WHERE status='berlangsung'")->fetchColumn(),
        'selesai' => (int)$db->query("SELECT COUNT(*) FROM agenda WHERE status='selesai'")->fetchColumn(),
    ];
    
    // Get nearest upcoming event for countdown
    $nearestEvent = $db->query("SELECT * FROM agenda WHERE status='akan_datang' AND tanggal_mulai >= CURDATE() ORDER BY tanggal_mulai ASC LIMIT 1")->fetch();
} catch (Throwable $e) {
    $agendaList = [];
    $cats = [];
    $stats = ['total'=>0, 'akan_datang'=>0, 'berlangsung'=>0, 'selesai'=>0];
    $nearestEvent = null;
}

$statusBadge = [
    'akan_datang' => ['Akan Datang', 'fa-clock', 'coming'],
    'berlangsung' => ['Sedang Berlangsung', 'fa-play-circle', 'ongoing'],
    'selesai' => ['Telah Selesai', 'fa-check-circle', 'done'],
    'batal' => ['Dibatalkan', 'fa-times-circle', 'cancelled'],
];

$catIcon = [
    'akademik' => 'fa-graduation-cap',
    'kemahasiswaan' => 'fa-users',
    'riset' => 'fa-microscope',
    'lainnya' => 'fa-calendar'
];

// Current date info for calendar widget
$currentMonth = date('n');
$currentYear = date('Y');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
$firstDayOfMonth = date('w', mktime(0, 0, 0, $currentMonth, 1, $currentYear));

// Get dates with events this month
$eventDates = [];
try {
    $monthEvents = $db->query("SELECT DISTINCT DATE(tanggal_mulai) as event_date FROM agenda WHERE MONTH(tanggal_mulai) = $currentMonth AND YEAR(tanggal_mulai) = $currentYear")->fetchAll(PDO::FETCH_COLUMN);
    $eventDates = array_map(function($d) { return (int)date('j', strtotime($d)); }, $monthEvents);
} catch (Throwable $e) {
    $eventDates = [];
}
?>

<!-- ===== HERO SECTION WITH CLOCK CANVAS ===== -->
<section class="ch-hero">
    <canvas id="chClockCanvas" class="ch-clock-canvas"></canvas>
    <div class="ch-hero-bg">
        <div class="ch-orb ch-orb-1"></div>
        <div class="ch-orb ch-orb-2"></div>
        <div class="ch-portal-ring"></div>
        <div class="ch-portal-ring ch-ring-2"></div>
        <div class="ch-time-particles"></div>
    </div>
    
    <!-- Floating Time Elements -->
    <div class="ch-floating-time">
        <span class="ch-time-float" style="--delay: 0s; --x: 8%; --y: 20%; --rot: 15deg;">⏰</span>
        <span class="ch-time-float" style="--delay: 1.2s; --x: 88%; --y: 18%; --rot: -10deg;">⌛</span>
        <span class="ch-time-float" style="--delay: 2.5s; --x: 12%; --y: 75%; --rot: 25deg;">📅</span>
        <span class="ch-time-float" style="--delay: 0.8s; --x: 85%; --y: 72%; --rot: -20deg;">🗓️</span>
        <span class="ch-time-float" style="--delay: 3.5s; --x: 50%; --y: 10%; --rot: 5deg;">⏱️</span>
        <span class="ch-time-float" style="--delay: 2s; --x: 45%; --y: 88%; --rot: -15deg;">🕐</span>
    </div>
    
    <div class="container ch-hero-content">
        <span class="ch-hero-badge fade-up">
            <span class="ch-badge-pulse"></span>
            <span class="ch-live-dot"></span>
            <i class="fas fa-clock"></i> CHRONOS PORTAL
        </span>
        
        <h1 class="ch-hero-title fade-up">
            <span class="ch-title-line">Agenda &</span>
            <span class="ch-title-line ch-glitch" data-text="Kegiatan">Kegiatan</span>
        </h1>
        
        <p class="ch-hero-subtitle fade-up">
            <span id="chTypingText"></span><span class="ch-cursor">|</span>
        </p>
        
        <!-- Live Clock Display -->
        <div class="ch-live-clock fade-up">
            <div class="ch-clock-display">
                <span id="chLiveHours">00</span>
                <span class="ch-clock-sep">:</span>
                <span id="chLiveMinutes">00</span>
                <span class="ch-clock-sep">:</span>
                <span id="chLiveSeconds">00</span>
            </div>
            <div class="ch-clock-info">
                <span class="ch-clock-date" id="chLiveDate">Loading...</span>
                <span class="ch-clock-tz">WIB (UTC+7)</span>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="ch-hero-stats fade-up">
            <div class="ch-stat-pill ch-stat-coming">
                <i class="fas fa-hourglass-half"></i>
                <div>
                    <span class="ch-stat-num" data-count="<?= $stats['akan_datang'] ?>">0</span>
                    <span class="ch-stat-label">Akan Datang</span>
                </div>
            </div>
            <div class="ch-stat-pill ch-stat-ongoing">
                <i class="fas fa-play-circle"></i>
                <div>
                    <span class="ch-stat-num" data-count="<?= $stats['berlangsung'] ?>">0</span>
                    <span class="ch-stat-label">Berlangsung</span>
                </div>
            </div>
            <div class="ch-stat-pill ch-stat-done">
                <i class="fas fa-check-circle"></i>
                <div>
                    <span class="ch-stat-num" data-count="<?= $stats['selesai'] ?>">0</span>
                    <span class="ch-stat-label">Selesai</span>
                </div>
            </div>
            <div class="ch-stat-pill ch-stat-total">
                <i class="fas fa-calendar-alt"></i>
                <div>
                    <span class="ch-stat-num" data-count="<?= $stats['total'] ?>">0</span>
                    <span class="ch-stat-label">Total Agenda</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="ch-scroll-indicator">
        <span>Explore Timeline</span>
        <div class="ch-time-arrow">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</section>

<!-- ===== COUNTDOWN BANNER (if there's upcoming event) ===== -->
<?php if ($nearestEvent): 
    $eventTime = strtotime($nearestEvent['tanggal_mulai']);
    $nearestIcon = $catIcon[$nearestEvent['kategori']] ?? 'fa-calendar';
?>
<div class="ch-countdown-banner fade-up">
    <div class="ch-countdown-bg">
        <div class="ch-countdown-grid"></div>
    </div>
    <div class="container ch-countdown-content">
        <div class="ch-countdown-left">
            <div class="ch-countdown-badge">
                <i class="fas fa-bolt"></i> EVENT TERDEKAT
            </div>
            <h3 class="ch-countdown-title"><?= clean($nearestEvent['judul']) ?></h3>
            <div class="ch-countdown-meta">
                <span><i class="fas <?= $nearestIcon ?>"></i> <?= clean($nearestEvent['kategori']) ?></span>
                <?php if (!empty($nearestEvent['lokasi'])): ?>
                <span><i class="fas fa-map-marker-alt"></i> <?= clean($nearestEvent['lokasi']) ?></span>
                <?php endif; ?>
                <span><i class="fas fa-calendar"></i> <?= date('d M Y', $eventTime) ?></span>
            </div>
        </div>
        <div class="ch-countdown-timer" data-target="<?= $eventTime ?>">
            <div class="ch-cd-unit">
                <span class="ch-cd-num" data-days>00</span>
                <span class="ch-cd-label">Hari</span>
            </div>
            <div class="ch-cd-sep">:</div>
            <div class="ch-cd-unit">
                <span class="ch-cd-num" data-hours>00</span>
                <span class="ch-cd-label">Jam</span>
            </div>
            <div class="ch-cd-sep">:</div>
            <div class="ch-cd-unit">
                <span class="ch-cd-num" data-minutes>00</span>
                <span class="ch-cd-label">Menit</span>
            </div>
            <div class="ch-cd-sep">:</div>
            <div class="ch-cd-unit">
                <span class="ch-cd-num" data-seconds>00</span>
                <span class="ch-cd-label">Detik</span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== READING PROGRESS ===== -->
<div class="ch-reading-progress">
    <div class="ch-progress-fill" id="chProgressFill"></div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<section class="ch-section">
    <div class="container">
        <div class="ch-layout">
            
            <!-- Sidebar: Mini Calendar & Info -->
            <aside class="ch-sidebar fade-up">
                
                <!-- Mini Calendar Widget -->
                <div class="ch-calendar-widget ch-tilt">
                    <div class="ch-calendar-glow"></div>
                    <div class="ch-calendar-header">
                        <h4><?= date('F Y') ?></h4>
                        <span class="ch-calendar-badge">
                            <i class="fas fa-calendar-check"></i>
                            <?= count($eventDates) ?> Events
                        </span>
                    </div>
                    <div class="ch-calendar-weekdays">
                        <span>M</span><span>S</span><span>S</span><span>R</span><span>K</span><span>J</span><span>S</span>
                    </div>
                    <div class="ch-calendar-days">
                        <?php for ($i = 0; $i < $firstDayOfMonth; $i++): ?>
                            <span class="ch-day-empty"></span>
                        <?php endfor; ?>
                        <?php for ($d = 1; $d <= $daysInMonth; $d++): 
                            $isToday = $d == date('j');
                            $hasEvent = in_array($d, $eventDates);
                        ?>
                            <span class="ch-day <?= $isToday ? 'today' : '' ?> <?= $hasEvent ? 'has-event' : '' ?>"><?= $d ?></span>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Quick Stats Card -->
                <div class="ch-quick-stats ch-tilt">
                    <div class="ch-quick-glow"></div>
                    <h4><i class="fas fa-chart-pie"></i> Statistik Bulan Ini</h4>
                    <div class="ch-stat-bars">
                        <div class="ch-stat-bar-item">
                            <div class="ch-stat-bar-head">
                                <span>Akademik</span>
                                <strong><?= rand(3, 8) ?></strong>
                            </div>
                            <div class="ch-stat-bar-track">
                                <div class="ch-stat-bar-fill" style="width: 65%; background: #3B82F6;"></div>
                            </div>
                        </div>
                        <div class="ch-stat-bar-item">
                            <div class="ch-stat-bar-head">
                                <span>Kemahasiswaan</span>
                                <strong><?= rand(2, 6) ?></strong>
                            </div>
                            <div class="ch-stat-bar-track">
                                <div class="ch-stat-bar-fill" style="width: 45%; background: #F59E0B;"></div>
                            </div>
                        </div>
                        <div class="ch-stat-bar-item">
                            <div class="ch-stat-bar-head">
                                <span>Riset</span>
                                <strong><?= rand(1, 4) ?></strong>
                            </div>
                            <div class="ch-stat-bar-track">
                                <div class="ch-stat-bar-fill" style="width: 30%; background: #8B5CF6;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Time Zone Info -->
                <div class="ch-timezone-card ch-tilt">
                    <div class="ch-tz-icon"><i class="fas fa-globe-asia"></i></div>
                    <div class="ch-tz-info">
                        <h5>Time Zone</h5>
                        <span>WIB (UTC+7)</span>
                        <small>Jakarta, Indonesia</small>
                    </div>
                </div>
            </aside>
            
            <!-- Main Content Area -->
            <div class="ch-main">
                
                <!-- Control Bar -->
                <div class="ch-control-bar fade-up">
                    <div class="ch-search-box">
                        <i class="fas fa-search"></i>
                        <form method="GET" action="agenda.php" class="ch-search-form">
                            <input type="text" name="q" placeholder="Cari event, lokasi, atau deskripsi..." value="<?= htmlspecialchars($searchQuery) ?>" class="ch-search-input">
                            <?php if ($filterKategori): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($filterKategori) ?>"><?php endif; ?>
                            <?php if ($filterStatus && $filterStatus !== 'semua'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
                            <button type="submit" class="ch-search-btn">Cari</button>
                        </form>
                    </div>
                    
                    <div class="ch-view-toggles">
                        <button class="ch-view-btn active" data-view="timeline" title="Timeline View">
                            <i class="fas fa-stream"></i>
                            <span>Timeline</span>
                        </button>
                        <button class="ch-view-btn" data-view="grid" title="Grid View">
                            <i class="fas fa-th-large"></i>
                            <span>Grid</span>
                        </button>
                    </div>
                </div>
                
                <!-- Filter Console -->
                <div class="ch-filter-console fade-up">
                    <div class="ch-console-header">
                        <div class="ch-console-dots">
                            <span class="dot red"></span>
                            <span class="dot yellow"></span>
                            <span class="dot green"></span>
                        </div>
                        <i class="fas fa-terminal"></i>
                        <span>Time_Filter.exe</span>
                        <span class="ch-console-status">● LIVE</span>
                    </div>
                    <div class="ch-console-body">
                        <div class="ch-filter-group">
                            <label><i class="fas fa-signal"></i> Status Event:</label>
                            <div class="ch-filter-pills">
                                <a href="agenda.php<?= $filterKategori ? '?kategori='.urlencode($filterKategori) : '' ?>" class="ch-pill <?= (!$filterStatus || $filterStatus === 'akan_datang') ? 'active' : '' ?>">
                                    <i class="fas fa-hourglass-half"></i> Akan Datang
                                </a>
                                <a href="agenda.php?status=berlangsung<?= $filterKategori ? '&kategori='.urlencode($filterKategori) : '' ?>" class="ch-pill <?= $filterStatus === 'berlangsung' ? 'active' : '' ?>">
                                    <i class="fas fa-play-circle"></i> Berlangsung
                                </a>
                                <a href="agenda.php?status=selesai<?= $filterKategori ? '&kategori='.urlencode($filterKategori) : '' ?>" class="ch-pill <?= $filterStatus === 'selesai' ? 'active' : '' ?>">
                                    <i class="fas fa-check-circle"></i> Selesai
                                </a>
                                <a href="agenda.php?status=semua<?= $filterKategori ? '&kategori='.urlencode($filterKategori) : '' ?>" class="ch-pill <?= $filterStatus === 'semua' ? 'active' : '' ?>">
                                    <i class="fas fa-infinity"></i> Semua
                                </a>
                            </div>
                        </div>
                        
                        <?php if (!empty($cats)): ?>
                        <div class="ch-filter-group">
                            <label><i class="fas fa-tags"></i> Kategori:</label>
                            <div class="ch-filter-pills">
                                <a href="agenda.php<?= $filterStatus ? '?status='.urlencode($filterStatus) : '' ?>" class="ch-pill <?= !$filterKategori ? 'active' : '' ?>">
                                    <i class="fas fa-layer-group"></i> Semua
                                </a>
                                <?php foreach ($cats as $c): 
                                    $icon = $catIcon[$c] ?? 'fa-calendar';
                                ?>
                                    <a href="?kategori=<?= urlencode($c) ?><?= $filterStatus ? '&status='.urlencode($filterStatus) : '' ?>" class="ch-pill <?= $filterKategori === $c ? 'active' : '' ?>">
                                        <i class="fas <?= $icon ?>"></i> <?= htmlspecialchars($c) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <button class="ch-btn-reset" onclick="chResetFilters()">
                            <i class="fas fa-sync-alt"></i> Reset Timeline
                        </button>
                    </div>
                </div>
                
                <!-- Agenda Container -->
                <div id="chAgendaContainer" class="ch-timeline-view">
                    <?php if (!empty($agendaList)): ?>
                        <!-- Timeline View -->
                        <div class="ch-timeline" id="chTimeline">
                            <?php foreach ($agendaList as $index => $a):
                                $status = $a['status'] ?? 'akan_datang';
                                $badge = $statusBadge[$status] ?? $statusBadge['akan_datang'];
                                $icon = $catIcon[$a['kategori']] ?? 'fa-calendar';
                                $tglMulai = strtotime($a['tanggal_mulai']);
                                $tglSelesai = !empty($a['tanggal_selesai']) ? strtotime($a['tanggal_selesai']) : null;
                                $hari = date('d', $tglMulai);
                                $bulan = strtoupper(date('M', $tglMulai));
                                $tahun = date('Y', $tglMulai);
                            ?>
                            <div class="ch-timeline-item fade-up ch-tilt" style="animation-delay: <?= $index * 0.05 ?>s" onclick="chOpenEvent(<?= $index ?>)">
                                <div class="ch-timeline-date">
                                    <div class="ch-date-box">
                                        <div class="ch-date-day"><?= $hari ?></div>
                                        <div class="ch-date-month"><?= $bulan ?></div>
                                        <div class="ch-date-year"><?= $tahun ?></div>
                                        <div class="ch-date-glow"></div>
                                    </div>
                                </div>
                                <div class="ch-timeline-line">
                                    <div class="ch-timeline-dot">
                                        <i class="fas <?= $icon ?>"></i>
                                        <span class="ch-dot-pulse"></span>
                                    </div>
                                    <div class="ch-line-particles">
                                        <span></span><span></span><span></span>
                                    </div>
                                </div>
                                <div class="ch-timeline-content">
                                    <div class="ch-content-glow"></div>
                                    <div class="ch-timeline-head">
                                        <span class="ch-badge ch-badge-<?= $badge[2] ?>">
                                            <i class="fas <?= $badge[1] ?>"></i> <?= $badge[0] ?>
                                        </span>
                                        <span class="ch-timeline-cat">
                                            <i class="fas <?= $icon ?>"></i> <?= htmlspecialchars($a['kategori']) ?>
                                        </span>
                                    </div>
                                    <h3 class="ch-timeline-title"><?= clean($a['judul']) ?></h3>
                                    <?php if (!empty($a['deskripsi'])): ?>
                                    <p class="ch-timeline-desc"><?= clean($a['deskripsi']) ?></p>
                                    <?php endif; ?>
                                    <div class="ch-timeline-meta">
                                        <?php if (!empty($a['lokasi'])): ?>
                                        <span><i class="fas fa-map-marker-alt"></i> <?= clean($a['lokasi']) ?></span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-clock"></i> 
                                            <?= date('H:i', $tglMulai) ?> WIB
                                            <?php if ($tglSelesai): ?>
                                                <?php if ($tglSelesai > $tglMulai): ?>
                                                    s/d <?= date('d M Y H:i', $tglSelesai) ?> WIB
                                                <?php else: ?>
                                                    - <?= date('H:i', $tglSelesai) ?> WIB
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="ch-card-actions">
                                        <button class="ch-action-btn ch-btn-detail">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                        <button class="ch-action-btn ch-btn-share" onclick="event.stopPropagation(); chShareEvent('<?= addslashes(clean($a['judul'])) ?>')">
                                            <i class="fas fa-share-alt"></i>
                                        </button>
                                        <button class="ch-action-btn ch-btn-calendar" onclick="event.stopPropagation(); chAddToCalendar('<?= addslashes(clean($a['judul'])) ?>', '<?= date('Y-m-d\TH:i', $tglMulai) ?>', '<?= addslashes(clean($a['lokasi'] ?? '')) ?>')">
                                            <i class="fas fa-calendar-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Grid View (Hidden by default) -->
                        <div class="ch-grid-view" id="chGrid" style="display: none;">
                            <?php foreach ($agendaList as $index => $a):
                                $status = $a['status'] ?? 'akan_datang';
                                $badge = $statusBadge[$status] ?? $statusBadge['akan_datang'];
                                $icon = $catIcon[$a['kategori']] ?? 'fa-calendar';
                                $tglMulai = strtotime($a['tanggal_mulai']);
                            ?>
                            <div class="ch-grid-card fade-up ch-tilt" style="animation-delay: <?= $index * 0.05 ?>s" onclick="chOpenEvent(<?= $index ?>)">
                                <div class="ch-grid-card-glow"></div>
                                <div class="ch-grid-header">
                                    <div class="ch-grid-date">
                                        <div class="ch-grid-day"><?= date('d', $tglMulai) ?></div>
                                        <div class="ch-grid-month"><?= strtoupper(date('M', $tglMulai)) ?></div>
                                        <div class="ch-grid-year"><?= date('Y', $tglMulai) ?></div>
                                    </div>
                                    <span class="ch-badge ch-badge-<?= $badge[2] ?>">
                                        <i class="fas <?= $badge[1] ?>"></i>
                                    </span>
                                </div>
                                <div class="ch-grid-body">
                                    <span class="ch-grid-cat"><i class="fas <?= $icon ?>"></i> <?= htmlspecialchars($a['kategori']) ?></span>
                                    <h3 class="ch-grid-title"><?= clean($a['judul']) ?></h3>
                                    <?php if (!empty($a['deskripsi'])): ?>
                                    <p class="ch-grid-desc"><?= clean(mb_substr($a['deskripsi'], 0, 100)) ?><?= strlen($a['deskripsi']) > 100 ? '...' : '' ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="ch-grid-footer">
                                    <?php if (!empty($a['lokasi'])): ?>
                                    <span><i class="fas fa-map-marker-alt"></i> <?= clean(mb_substr($a['lokasi'], 0, 25)) ?><?= strlen($a['lokasi']) > 25 ? '...' : '' ?></span>
                                    <?php endif; ?>
                                    <span><i class="fas fa-clock"></i> <?= date('H:i', $tglMulai) ?> WIB</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="ch-empty-state fade-up">
                            <div class="ch-empty-icon">
                                <i class="fas fa-hourglass-empty"></i>
                                <div class="ch-empty-pulse"></div>
                            </div>
                            <h3>Time Portal Empty</h3>
                            <p>Belum ada agenda yang sesuai dengan filter atau pencarian Anda.</p>
                            <a href="agenda.php" class="ch-reset-btn">
                                <i class="fas fa-redo"></i> Reset Timeline
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== EVENT DETAIL MODAL ===== -->
<div class="ch-modal-backdrop" id="chModal">
    <div class="ch-modal-container">
        <button class="ch-modal-close" onclick="chCloseModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="ch-modal-content">
            <div class="ch-modal-portal">
                <div class="ch-portal-ring-small"></div>
                <div class="ch-portal-ring-small ch-ring-inner"></div>
            </div>
            <div class="ch-modal-header">
                <div class="ch-modal-date-badge">
                    <span class="ch-modal-day" id="chModalDay">00</span>
                    <span class="ch-modal-month" id="chModalMonth">MON</span>
                </div>
                <div class="ch-modal-title-group">
                    <span class="ch-modal-badge" id="chModalBadge">Status</span>
                    <h2 id="chModalTitle">Event Title</h2>
                    <span class="ch-modal-cat" id="chModalCat"><i class="fas fa-tag"></i> Kategori</span>
                </div>
            </div>
            <div class="ch-modal-body">
                <div class="ch-modal-meta-grid">
                    <div class="ch-modal-meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <span>Lokasi</span>
                            <strong id="chModalLocation">-</strong>
                        </div>
                    </div>
                    <div class="ch-modal-meta-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <span>Waktu</span>
                            <strong id="chModalTime">-</strong>
                        </div>
                    </div>
                    <div class="ch-modal-meta-item">
                        <i class="fas fa-calendar"></i>
                        <div>
                            <span>Tanggal</span>
                            <strong id="chModalDate">-</strong>
                        </div>
                    </div>
                </div>
                <div class="ch-modal-desc">
                    <h4><i class="fas fa-info-circle"></i> Deskripsi</h4>
                    <p id="chModalDesc">Deskripsi event akan muncul di sini.</p>
                </div>
                <div class="ch-modal-actions">
                    <button class="ch-modal-btn primary" onclick="chAddToCalendarFromModal()">
                        <i class="fas fa-calendar-plus"></i> Tambahkan ke Kalender
                    </button>
                    <button class="ch-modal-btn" onclick="chShareFromModal()">
                        <i class="fas fa-share-alt"></i> Bagikan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="chToast" class="ch-toast">
    <i class="fas fa-check-circle"></i>
    <span id="chToastMsg">Berhasil!</span>
</div>

<!-- JSON Data for Modal -->
<script id="chEventData" type="application/json">
<?= json_encode(array_map(function($a) use ($statusBadge, $catIcon) {
    $status = $a['status'] ?? 'akan_datang';
    $badge = $statusBadge[$status] ?? $statusBadge['akan_datang'];
    $icon = $catIcon[$a['kategori']] ?? 'fa-calendar';
    $tglMulai = strtotime($a['tanggal_mulai']);
    $tglSelesai = !empty($a['tanggal_selesai']) ? strtotime($a['tanggal_selesai']) : null;
    return [
        'judul' => $a['judul'],
        'kategori' => $a['kategori'],
        'kategori_icon' => $icon,
        'status_label' => $badge[0],
        'status_icon' => $badge[1],
        'status_class' => $badge[2],
        'deskripsi' => $a['deskripsi'] ?? '',
        'lokasi' => $a['lokasi'] ?? '',
        'tanggal_mulai' => $tglMulai,
        'tanggal_selesai' => $tglSelesai,
        'day' => date('d', $tglMulai),
        'month' => strtoupper(date('M', $tglMulai)),
        'time' => date('H:i', $tglMulai) . ' WIB' . ($tglSelesai ? ' - ' . date('H:i', $tglSelesai) . ' WIB' : ''),
        'date' => date('d F Y', $tglMulai),
        'iso' => date('Y-m-d\TH:i', $tglMulai)
    ];
}, $agendaList), JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>
</script>

<!-- ============================================================
     SPECTACULAR STYLES (prefix: ch- = Chronos)
============================================================ -->
<style>
:root {
    --ch-primary: #10B981;
    --ch-primary-dark: #047857;
    --ch-primary-light: #D1FAE5;
    --ch-accent: #8B5CF6;
    --ch-accent-2: #F59E0B;
    --ch-dark: #0F172A;
    --ch-gray-50: #F8FAFC;
    --ch-gray-100: #F1F5F9;
    --ch-gray-200: #E2E8F0;
    --ch-gray-600: #475569;
    --ch-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --ch-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --ch-shadow-glow: 0 0 30px rgba(16,185,129,0.3);
    --ch-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --ch-font-display: 'Space Grotesk', 'Inter', sans-serif;
}

body.gl-dark {
    --ch-gray-50: #0B1120;
    --ch-gray-100: rgba(255,255,255,.08);
    --ch-gray-200: rgba(255,255,255,.1);
    --ch-gray-600: #94A3B8;
}

/* ===== GLOBAL ===== */
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--ch-gray-50); }
::-webkit-scrollbar-thumb { background: linear-gradient(180deg, var(--ch-primary), var(--ch-accent)); border-radius: 5px; }

/* ===== READING PROGRESS ===== */
.ch-reading-progress {
    position: fixed; top: 0; left: 0; right: 0; height: 3px;
    background: rgba(16,185,129,0.1); z-index: 9998;
}
.ch-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--ch-primary), var(--ch-accent), var(--ch-accent-2));
    box-shadow: 0 0 10px rgba(16,185,129,0.6);
    transition: width 0.1s ease-out;
}

/* ===== HERO WITH CLOCK ===== */
.ch-hero {
    background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 50%, #047857 100%);
    padding: 160px 20px 120px; position: relative; overflow: hidden;
    min-height: 95vh; display: flex; align-items: center; justify-content: center;
}
.ch-clock-canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    z-index: 1; opacity: 0.6;
}
.ch-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }
.ch-orb {
    position: absolute; border-radius: 50%; filter: blur(80px);
    opacity: 0.4; animation: chFloat 15s infinite ease-in-out;
}
.ch-orb-1 { width: 500px; height: 500px; background: var(--ch-primary); top: -150px; right: -150px; }
.ch-orb-2 { width: 350px; height: 350px; background: var(--ch-accent); bottom: -100px; left: -100px; animation-delay: -5s; }
@keyframes chFloat { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(30px, -30px); } }

/* Portal Rings */
.ch-portal-ring {
    position: absolute; top: 50%; left: 50%;
    width: 600px; height: 600px; border-radius: 50%;
    border: 2px solid rgba(16,185,129,0.2);
    transform: translate(-50%, -50%);
    animation: chPortalSpin 30s linear infinite;
    box-shadow: 0 0 60px rgba(16,185,129,0.2) inset;
}
.ch-portal-ring.ch-ring-2 {
    width: 800px; height: 800px;
    border-color: rgba(139,92,246,0.15);
    animation-duration: 45s; animation-direction: reverse;
    box-shadow: 0 0 80px rgba(139,92,246,0.15) inset;
}
@keyframes chPortalSpin { to { transform: translate(-50%, -50%) rotate(360deg); } }

.ch-time-particles {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.3) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: chParticleDrift 20s linear infinite;
    opacity: 0.3;
}
@keyframes chParticleDrift {
    to { background-position: 50px 50px; }
}

/* Floating Time Icons */
.ch-floating-time { position: absolute; inset: 0; z-index: 3; pointer-events: none; }
.ch-time-float {
    position: absolute; font-size: 2.5rem;
    animation: chFloatTime 8s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
    opacity: 0.4; filter: drop-shadow(0 0 10px rgba(16,185,129,0.5));
    transform: rotate(var(--rot));
}
@keyframes chFloatTime {
    0%, 100% { transform: translateY(0) rotate(var(--rot)); opacity: 0.4; }
    50% { transform: translateY(-30px) rotate(calc(var(--rot) + 15deg)); opacity: 0.8; }
}

.ch-hero-content {
    position: relative; z-index: 4; text-align: center;
    color: #fff; max-width: 1000px; margin: 0 auto;
}

.ch-hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 10px 22px; background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.2); border-radius: 50px;
    font-size: .9rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 600; letter-spacing: 1.5px; position: relative;
}
.ch-badge-pulse {
    position: absolute; inset: 0; border-radius: 50px;
    border: 2px solid var(--ch-primary);
    animation: chBadgePulse 2s infinite;
}
@keyframes chBadgePulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.3); opacity: 0; } }

.ch-live-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #EF4444; animation: chLivePulse 1.5s infinite;
    box-shadow: 0 0 10px #EF4444;
}
@keyframes chLivePulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.ch-hero-title {
    font-size: clamp(3rem, 9vw, 6rem); font-weight: 900;
    margin-bottom: 24px; letter-spacing: -0.03em; line-height: 1;
    font-family: var(--ch-font-display);
}
.ch-title-line { display: block; }
.ch-glitch {
    position: relative; display: inline-block;
    background: linear-gradient(135deg, var(--ch-primary) 0%, var(--ch-accent) 50%, var(--ch-accent-2) 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 200%;
    animation: chGradientShift 5s ease infinite;
}
.ch-glitch::before, .ch-glitch::after {
    content: attr(data-text); position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    background: inherit; -webkit-background-clip: text;
    -webkit-text-fill-color: transparent; background-clip: text;
}
.ch-glitch::before {
    animation: chGlitch1 3s infinite;
    clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%);
}
.ch-glitch::after {
    animation: chGlitch2 2.5s infinite;
    clip-path: polygon(0 60%, 100% 60%, 100% 100%, 0 100%);
}
@keyframes chGradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
@keyframes chGlitch1 {
    0%, 90%, 100% { transform: translate(0, 0); }
    92% { transform: translate(-3px, 1px); }
    94% { transform: translate(2px, -2px); }
}
@keyframes chGlitch2 {
    0%, 90%, 100% { transform: translate(0, 0); }
    91% { transform: translate(2px, 1px); }
    93% { transform: translate(-2px, -1px); }
}

.ch-hero-subtitle {
    font-size: 1.2rem; opacity: .9; max-width: 700px;
    margin: 0 auto 40px; line-height: 1.7;
    display: inline-block; min-height: 1.7em;
}
.ch-cursor { animation: chBlink 1s infinite; color: var(--ch-primary); font-weight: 300; }
@keyframes chBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

/* Live Clock Display */
.ch-live-clock {
    display: flex; flex-direction: column; align-items: center;
    gap: 12px; margin-bottom: 40px;
    padding: 24px 40px;
    background: rgba(255,255,255,0.05); backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 20px; max-width: 500px; margin-left: auto; margin-right: auto;
    box-shadow: 0 8px 32px rgba(16,185,129,0.2);
}
.ch-clock-display {
    display: flex; align-items: center; gap: 8px;
    font-family: var(--ch-font-display); font-weight: 800;
    font-size: 3rem; letter-spacing: 2px;
    background: linear-gradient(135deg, #34D399, #06B6D4);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.ch-clock-sep {
    animation: chBlink 1s infinite;
    background: linear-gradient(135deg, #34D399, #06B6D4);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.ch-clock-info {
    display: flex; gap: 16px; align-items: center;
    font-size: 0.85rem; color: rgba(255,255,255,0.8);
}
.ch-clock-tz {
    padding: 4px 12px; background: rgba(16,185,129,0.2);
    border-radius: 20px; color: var(--ch-primary-light);
    font-family: var(--ch-font-mono); font-size: 0.75rem;
}

/* Stats */
.ch-hero-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 16px; max-width: 900px; margin: 0 auto;
}
.ch-stat-pill {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-radius: 16px;
    backdrop-filter: blur(12px); transition: all 0.3s;
}
.ch-stat-pill:hover {
    background: rgba(255,255,255,.15); transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.2);
}
.ch-stat-pill i { font-size: 1.5rem; }
.ch-stat-coming i { color: #3B82F6; }
.ch-stat-ongoing i { color: var(--ch-primary); }
.ch-stat-done i { color: var(--ch-accent); }
.ch-stat-total i { color: var(--ch-accent-2); }
.ch-stat-num {
    display: block; font-size: 1.6rem; font-weight: 800;
    color: #fff; line-height: 1; font-family: var(--ch-font-display);
}
.ch-stat-label {
    font-size: 0.7rem; color: rgba(255,255,255,.7);
    text-transform: uppercase; letter-spacing: 0.05em;
    margin-top: 4px; display: block;
}

/* Scroll Indicator */
.ch-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%;
    transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.ch-time-arrow { animation: chArrowBounce 2s infinite; }
@keyframes chArrowBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(10px); }
}

/* ===== COUNTDOWN BANNER ===== */
.ch-countdown-banner {
    background: linear-gradient(135deg, var(--ch-primary-dark), #1E1B4B);
    padding: 40px 20px; position: relative; overflow: hidden;
    color: #fff;
}
.ch-countdown-bg { position: absolute; inset: 0; pointer-events: none; }
.ch-countdown-grid {
    position: absolute; inset: 0;
    background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 30px 30px;
}
.ch-countdown-content {
    position: relative; z-index: 2; display: grid;
    grid-template-columns: 1fr auto; gap: 40px;
    align-items: center;
}
.ch-countdown-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 14px; background: linear-gradient(135deg, var(--ch-accent-2), #D97706);
    border-radius: 20px; font-size: 0.72rem; font-weight: 800;
    letter-spacing: 1px; margin-bottom: 12px;
    box-shadow: 0 4px 12px rgba(245,158,11,0.4);
}
.ch-countdown-title {
    font-size: 1.4rem; font-weight: 800; margin: 0 0 12px 0;
    line-height: 1.3;
}
.ch-countdown-meta {
    display: flex; gap: 16px; flex-wrap: wrap;
    font-size: 0.85rem; color: rgba(255,255,255,0.8);
}
.ch-countdown-meta span { display: inline-flex; align-items: center; gap: 6px; }
.ch-countdown-meta i { color: var(--ch-primary); }

.ch-countdown-timer {
    display: flex; gap: 10px; align-items: center;
    padding: 20px 28px; background: rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px; backdrop-filter: blur(10px);
}
.ch-cd-unit { text-align: center; min-width: 60px; }
.ch-cd-num {
    display: block; font-family: var(--ch-font-display);
    font-size: 2rem; font-weight: 800; line-height: 1;
    background: linear-gradient(135deg, var(--ch-primary), var(--ch-accent));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.ch-cd-label {
    display: block; font-size: 0.7rem; color: rgba(255,255,255,0.7);
    text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;
}
.ch-cd-sep {
    font-size: 1.5rem; color: var(--ch-primary);
    animation: chBlink 1s infinite; font-weight: 700;
}

/* ===== MAIN SECTION ===== */
.ch-section { padding: 60px 20px 100px; background: var(--ch-gray-50); }

.ch-layout {
    display: grid; grid-template-columns: 300px 1fr;
    gap: 40px; align-items: flex-start;
}

/* ===== SIDEBAR ===== */
.ch-sidebar {
    position: sticky; top: 100px;
    display: flex; flex-direction: column; gap: 20px;
}

/* Mini Calendar */
.ch-calendar-widget {
    background: #fff; border-radius: 20px; padding: 24px;
    box-shadow: var(--ch-shadow-md); border: 1px solid var(--ch-gray-100);
    position: relative; overflow: hidden; transform-style: preserve-3d;
}
body.gl-dark .ch-calendar-widget { background: #1E293B; border-color: rgba(255,255,255,.08); }

.ch-calendar-glow {
    position: absolute; top: -50%; right: -50%; width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(16,185,129,0.15), transparent 70%);
    pointer-events: none;
}

.ch-calendar-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 20px; position: relative; z-index: 1;
}
.ch-calendar-header h4 {
    font-size: 1.05rem; font-weight: 800; color: var(--ch-dark);
    margin: 0; font-family: var(--ch-font-display);
}
body.gl-dark .ch-calendar-header h4 { color: #F1F5F9; }
.ch-calendar-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; background: rgba(16,185,129,0.1);
    color: var(--ch-primary-dark); border-radius: 20px;
    font-size: 0.7rem; font-weight: 700;
}
body.gl-dark .ch-calendar-badge { background: rgba(16,185,129,0.15); color: #34D399; }
.ch-calendar-badge i { font-size: 0.7rem; }

.ch-calendar-weekdays {
    display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;
    margin-bottom: 8px; position: relative; z-index: 1;
}
.ch-calendar-weekdays span {
    text-align: center; font-size: 0.7rem; font-weight: 700;
    color: var(--ch-gray-600); text-transform: uppercase;
    padding: 6px 0;
}
body.gl-dark .ch-calendar-weekdays span { color: #94A3B8; }

.ch-calendar-days {
    display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;
    position: relative; z-index: 1;
}
.ch-day, .ch-day-empty {
    aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
    border-radius: 8px; font-size: 0.85rem; font-weight: 600;
    color: var(--ch-gray-600); transition: all 0.2s; cursor: pointer;
}
body.gl-dark .ch-day { color: #CBD5E1; }
.ch-day:hover { background: var(--ch-gray-100); }
body.gl-dark .ch-day:hover { background: rgba(255,255,255,.05); }
.ch-day.today {
    background: var(--ch-primary); color: #fff;
    font-weight: 800; box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}
.ch-day.has-event {
    position: relative;
}
.ch-day.has-event::after {
    content: ''; position: absolute; bottom: 4px; left: 50%;
    transform: translateX(-50%); width: 4px; height: 4px;
    border-radius: 50%; background: var(--ch-accent-2);
}
.ch-day.today.has-event::after { background: #fff; }

/* Quick Stats */
.ch-quick-stats {
    background: #fff; border-radius: 20px; padding: 24px;
    box-shadow: var(--ch-shadow-sm); border: 1px solid var(--ch-gray-100);
    position: relative; overflow: hidden; transform-style: preserve-3d;
}
body.gl-dark .ch-quick-stats { background: #1E293B; border-color: rgba(255,255,255,.08); }

.ch-quick-glow {
    position: absolute; top: -50%; left: -50%; width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(139,92,246,0.1), transparent 70%);
    pointer-events: none;
}

.ch-quick-stats h4 {
    font-size: 0.95rem; font-weight: 800; color: var(--ch-dark);
    margin: 0 0 20px 0; display: flex; align-items: center; gap: 8px;
    position: relative; z-index: 1;
}
body.gl-dark .ch-quick-stats h4 { color: #F1F5F9; }
.ch-quick-stats h4 i { color: var(--ch-accent); }

.ch-stat-bars { display: flex; flex-direction: column; gap: 14px; position: relative; z-index: 1; }
.ch-stat-bar-item {}
.ch-stat-bar-head {
    display: flex; justify-content: space-between; margin-bottom: 6px;
    font-size: 0.8rem; color: var(--ch-gray-600);
}
body.gl-dark .ch-stat-bar-head { color: #94A3B8; }
.ch-stat-bar-head strong { color: var(--ch-dark); font-family: var(--ch-font-display); }
body.gl-dark .ch-stat-bar-head strong { color: #F1F5F9; }
.ch-stat-bar-track {
    height: 6px; background: var(--ch-gray-100); border-radius: 3px;
    overflow: hidden;
}
body.gl-dark .ch-stat-bar-track { background: rgba(255,255,255,.08); }
.ch-stat-bar-fill {
    height: 100%; border-radius: 3px;
    transition: width 1s ease-out;
    width: 0;
}

/* Timezone Card */
.ch-timezone-card {
    background: linear-gradient(135deg, var(--ch-primary), var(--ch-accent));
    color: #fff; border-radius: 16px; padding: 16px;
    display: flex; align-items: center; gap: 14px;
    box-shadow: 0 8px 24px rgba(16,185,129,0.3);
    transform-style: preserve-3d;
}
.ch-tz-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.ch-tz-info { flex: 1; }
.ch-tz-info h5 { margin: 0 0 2px 0; font-size: 0.85rem; font-weight: 700; }
.ch-tz-info span { font-size: 1rem; font-weight: 800; display: block; font-family: var(--ch-font-display); }
.ch-tz-info small { font-size: 0.7rem; opacity: 0.85; }

/* ===== MAIN CONTENT ===== */
.ch-main { min-width: 0; }

/* Control Bar */
.ch-control-bar {
    display: flex; justify-content: space-between; align-items: center;
    gap: 20px; margin-bottom: 24px; flex-wrap: wrap;
}
.ch-search-box {
    display: flex; align-items: center; background: #fff;
    border: 1.5px solid var(--ch-gray-200); border-radius: 14px;
    padding: 4px; flex: 1; max-width: 500px; transition: all 0.3s;
}
body.gl-dark .ch-search-box { background: #1E293B; border-color: rgba(255,255,255,.1); }
.ch-search-box:focus-within {
    border-color: var(--ch-primary);
    box-shadow: 0 0 0 4px rgba(16,185,129,0.15);
}
.ch-search-box > i { padding: 0 12px; color: var(--ch-gray-600); }
.ch-search-form { display: flex; flex: 1; gap: 8px; align-items: center; }
.ch-search-input {
    flex: 1; border: none; background: transparent;
    padding: 10px 0; font-size: 0.95rem; outline: none;
    color: var(--ch-dark); font-family: inherit;
}
body.gl-dark .ch-search-input { color: #E2E8F0; }
.ch-search-btn {
    background: linear-gradient(135deg, var(--ch-primary), var(--ch-primary-dark));
    color: #fff; border: none; border-radius: 10px;
    padding: 10px 20px; font-weight: 600; cursor: pointer;
    transition: all 0.3s; font-family: inherit;
}
.ch-search-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16,185,129,0.3); }

.ch-view-toggles {
    display: flex; background: var(--ch-gray-100); border-radius: 12px; padding: 4px;
}
body.gl-dark .ch-view-toggles { background: rgba(255,255,255,.05); }
.ch-view-btn {
    padding: 10px 18px; border: none; background: transparent;
    border-radius: 8px; color: var(--ch-gray-600); cursor: pointer;
    transition: all 0.3s; display: flex; align-items: center; gap: 8px;
    font-size: 0.9rem; font-weight: 600; font-family: inherit;
}
body.gl-dark .ch-view-btn { color: #94A3B8; }
.ch-view-btn i { font-size: 1rem; }
.ch-view-btn.active {
    background: #fff; color: var(--ch-primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
body.gl-dark .ch-view-btn.active { background: #334155; color: #34D399; }

/* Filter Console */
.ch-filter-console {
    background: #fff; border: 1px solid var(--ch-gray-200);
    border-radius: 20px; box-shadow: var(--ch-shadow-md);
    margin-bottom: 40px; overflow: hidden;
}
body.gl-dark .ch-filter-console { background: #1E293B; border-color: rgba(255,255,255,.08); }

.ch-console-header {
    background: var(--ch-dark); padding: 14px 20px;
    display: flex; align-items: center; gap: 12px;
    color: #94A3B8; font-family: var(--ch-font-mono); font-size: 0.85rem;
}
.ch-console-dots { display: flex; gap: 6px; }
.ch-console-dots .dot { width: 12px; height: 12px; border-radius: 50%; }
.ch-console-dots .red { background: #FF5F56; }
.ch-console-dots .yellow { background: #FFBD2E; }
.ch-console-dots .green { background: #27C93F; }
.ch-console-header i { color: var(--ch-primary); }
.ch-console-header span { color: #E2E8F0; }
.ch-console-status {
    margin-left: auto; color: var(--ch-primary);
    font-size: 0.75rem; letter-spacing: 1px;
    animation: chBlink 2s infinite;
}

.ch-console-body { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
.ch-filter-group { display: flex; flex-direction: column; gap: 10px; }
.ch-filter-group label {
    font-size: 0.8rem; font-weight: 700; color: var(--ch-gray-600);
    display: flex; align-items: center; gap: 8px;
    text-transform: uppercase; letter-spacing: 0.5px;
    font-family: var(--ch-font-mono);
}
body.gl-dark .ch-filter-group label { color: #94A3B8; }
.ch-filter-group label i { color: var(--ch-primary); }

.ch-filter-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.ch-pill {
    padding: 8px 16px; border-radius: 50px; background: var(--ch-gray-100);
    border: 1.5px solid transparent; color: var(--ch-gray-600);
    cursor: pointer; font-weight: 600; font-size: 0.82rem;
    transition: all 0.3s; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
}
body.gl-dark .ch-pill { background: rgba(255,255,255,.05); color: #CBD5E1; }
.ch-pill i { font-size: 0.75rem; }
.ch-pill:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); color: var(--ch-primary); }
.ch-pill.active {
    background: var(--ch-primary); color: #fff;
    border-color: var(--ch-primary);
    box-shadow: 0 4px 12px rgba(16,185,129,.4);
}

.ch-btn-reset {
    padding: 10px 20px; background: rgba(239,68,68,0.1);
    color: #DC2626; border: 1px solid rgba(239,68,68,0.2);
    border-radius: 8px; font-family: var(--ch-font-mono);
    font-size: 0.85rem; font-weight: 600; cursor: pointer;
    transition: all 0.3s; align-self: flex-start;
    display: inline-flex; align-items: center; gap: 8px;
}
.ch-btn-reset:hover { background: #DC2626; color: #fff; transform: translateY(-2px); }

/* ===== TIMELINE VIEW ===== */
.ch-timeline { position: relative; padding: 20px 0 40px; }

.ch-timeline-item {
    display: grid; grid-template-columns: 120px 50px 1fr;
    gap: 0; margin-bottom: 32px; align-items: start;
    cursor: pointer; transition: all 0.3s;
}
.ch-timeline-date {
    display: flex; justify-content: flex-end; align-items: center;
    padding-right: 20px; padding-top: 8px;
}
.ch-date-box {
    background: linear-gradient(135deg, var(--ch-primary), var(--ch-primary-dark));
    color: #fff; border-radius: 18px; padding: 14px 18px;
    text-align: center; box-shadow: 0 8px 24px rgba(16,185,129,0.4);
    min-width: 90px; transition: transform 0.3s;
    position: relative; overflow: hidden;
}
.ch-date-glow {
    position: absolute; inset: 0;
    background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.2), transparent 60%);
    pointer-events: none;
}
.ch-timeline-item:hover .ch-date-box {
    transform: scale(1.08) rotate(-3deg);
    box-shadow: 0 12px 32px rgba(16,185,129,0.5);
}
.ch-date-day {
    font-family: var(--ch-font-display); font-size: 2rem;
    font-weight: 800; line-height: 1; position: relative; z-index: 1;
}
.ch-date-month {
    font-size: 0.8rem; font-weight: 700; letter-spacing: 2px;
    margin-top: 4px; text-transform: uppercase; position: relative; z-index: 1;
}
.ch-date-year {
    font-size: 0.7rem; opacity: 0.9; margin-top: 2px;
    position: relative; z-index: 1;
}

.ch-timeline-line {
    position: relative; display: flex; justify-content: center;
    padding-top: 20px;
}
.ch-timeline-line::before {
    content: ''; position: absolute; top: 60px; bottom: -32px; left: 50%;
    width: 3px; background: linear-gradient(to bottom, var(--ch-primary), rgba(16,185,129,0.1));
    transform: translateX(-50%);
}
.ch-timeline-item:last-child .ch-timeline-line::before { display: none; }

.ch-timeline-dot {
    width: 50px; height: 50px; border-radius: 50%;
    background: #fff; border: 4px solid var(--ch-primary);
    color: var(--ch-primary); display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; box-shadow: 0 4px 16px rgba(16,185,129,0.3);
    position: relative; z-index: 2; flex-shrink: 0; transition: all 0.3s;
}
body.gl-dark .ch-timeline-dot { background: #0F172A; }
.ch-dot-pulse {
    position: absolute; inset: -8px; border-radius: 50%;
    border: 2px solid var(--ch-primary); opacity: 0;
    animation: chDotPulse 2s infinite;
}
@keyframes chDotPulse {
    0% { transform: scale(0.8); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}
.ch-timeline-item:hover .ch-timeline-dot {
    background: var(--ch-primary); color: #fff;
    transform: scale(1.15) rotate(360deg);
}

.ch-line-particles {
    position: absolute; top: 60px; bottom: -32px; left: 50%;
    transform: translateX(-50%); width: 3px; pointer-events: none;
}
.ch-line-particles span {
    position: absolute; left: 0; width: 6px; height: 6px;
    border-radius: 50%; background: var(--ch-primary);
    box-shadow: 0 0 10px var(--ch-primary);
    animation: chParticleTravel 3s infinite linear;
}
.ch-line-particles span:nth-child(1) { animation-delay: 0s; }
.ch-line-particles span:nth-child(2) { animation-delay: 1s; }
.ch-line-particles span:nth-child(3) { animation-delay: 2s; }
@keyframes chParticleTravel {
    0% { top: 0; opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { top: 100%; opacity: 0; }
}

.ch-timeline-content {
    background: #fff; border-radius: 20px; padding: 28px;
    border: 1px solid var(--ch-gray-100);
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative; overflow: hidden;
    transform-style: preserve-3d;
}
body.gl-dark .ch-timeline-content { background: #1E293B; border-color: rgba(255,255,255,.08); }

.ch-content-glow {
    position: absolute; top: -50%; right: -50%; width: 300px; height: 300px;
    background: radial-gradient(circle, rgba(16,185,129,0.08), transparent 70%);
    opacity: 0; transition: opacity 0.4s; pointer-events: none;
}
.ch-timeline-item:hover .ch-content-glow { opacity: 1; }

.ch-timeline-content::before {
    content: ''; position: absolute; inset: 0; border-radius: 20px; padding: 1px;
    background: conic-gradient(from 0deg, transparent, var(--ch-primary), var(--ch-accent), transparent 40%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none;
}
.ch-timeline-item:hover .ch-timeline-content::before {
    opacity: 1; animation: chRotateBorder 4s linear infinite;
}
@keyframes chRotateBorder { to { transform: rotate(360deg); } }

.ch-timeline-item:hover .ch-timeline-content {
    transform: translateX(8px) translateY(-4px);
    box-shadow: 0 20px 40px rgba(16,185,129,0.15);
    border-color: var(--ch-primary);
}

.ch-timeline-head {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    margin-bottom: 14px; position: relative; z-index: 1;
}
.ch-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 14px; border-radius: 20px;
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.5px;
}
.ch-badge-coming { background: rgba(59,130,246,0.12); color: #2563EB; }
.ch-badge-ongoing { background: rgba(16,185,129,0.15); color: #059669; }
.ch-badge-done { background: rgba(107,114,128,0.12); color: #4B5563; }
.ch-badge-cancelled { background: rgba(239,68,68,0.12); color: #DC2626; }

.ch-timeline-cat {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 0.8rem; color: var(--ch-gray-600); font-weight: 600;
    padding: 4px 10px; background: var(--ch-gray-100); border-radius: 20px;
}
body.gl-dark .ch-timeline-cat { color: #94A3B8; background: rgba(255,255,255,.05); }
.ch-timeline-cat i { color: var(--ch-primary); font-size: 0.75rem; }

.ch-timeline-title {
    font-size: 1.35rem; font-weight: 800; margin: 0 0 12px 0;
    line-height: 1.3; color: var(--ch-dark); position: relative; z-index: 1;
    font-family: var(--ch-font-display);
}
body.gl-dark .ch-timeline-title { color: #F1F5F9; }

.ch-timeline-desc {
    font-size: 0.95rem; color: var(--ch-gray-600);
    line-height: 1.7; margin: 0 0 20px 0; position: relative; z-index: 1;
    display: -webkit-box; -webkit-line-clamp: 3;
    -webkit-box-orient: vertical; overflow: hidden;
}
body.gl-dark .ch-timeline-desc { color: #CBD5E1; }

.ch-timeline-meta {
    display: flex; gap: 20px; flex-wrap: wrap; font-size: 0.85rem;
    color: var(--ch-gray-600); padding-top: 18px;
    border-top: 1px dashed var(--ch-gray-200);
    position: relative; z-index: 1;
}
body.gl-dark .ch-timeline-meta { color: #94A3B8; border-color: rgba(255,255,255,.1); }
.ch-timeline-meta span { display: inline-flex; align-items: center; gap: 8px; }
.ch-timeline-meta i { color: var(--ch-primary); font-size: 0.9rem; }

.ch-card-actions {
    display: flex; gap: 8px; margin-top: 20px; position: relative; z-index: 1;
}
.ch-action-btn {
    padding: 8px 16px; background: var(--ch-gray-100);
    border: 1px solid var(--ch-gray-200); border-radius: 10px;
    color: var(--ch-gray-600); cursor: pointer; font-size: 0.85rem;
    font-weight: 600; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 6px;
    font-family: inherit;
}
body.gl-dark .ch-action-btn { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.ch-btn-detail {
    background: linear-gradient(135deg, var(--ch-primary), var(--ch-primary-dark));
    color: #fff; border-color: transparent; flex: 1; justify-content: center;
}
.ch-btn-detail:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16,185,129,0.3);
}
.ch-action-btn:not(.ch-btn-detail):hover {
    background: var(--ch-primary); color: #fff;
    border-color: var(--ch-primary); transform: translateY(-2px);
}

/* ===== GRID VIEW ===== */
.ch-grid-view {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px; padding: 20px 0 40px;
}
.ch-grid-card {
    background: #fff; border-radius: 20px; border: 1px solid var(--ch-gray-100);
    overflow: hidden; transition: all 0.4s; display: flex; flex-direction: column;
    cursor: pointer; position: relative; transform-style: preserve-3d;
}
body.gl-dark .ch-grid-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.ch-grid-card-glow {
    position: absolute; inset: 0;
    background: radial-gradient(circle at 50% 0%, rgba(16,185,129,0.15), transparent 60%);
    opacity: 0; transition: opacity 0.4s; pointer-events: none;
}
.ch-grid-card:hover .ch-grid-card-glow { opacity: 1; }

.ch-grid-card::before {
    content: ''; position: absolute; inset: 0; border-radius: 20px; padding: 1px;
    background: conic-gradient(from 0deg, transparent, var(--ch-primary), var(--ch-accent), transparent 40%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none; z-index: 5;
}
.ch-grid-card:hover::before { opacity: 1; animation: chRotateBorder 4s linear infinite; }

.ch-grid-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(16,185,129,0.15);
    border-color: var(--ch-primary);
}

.ch-grid-header {
    background: linear-gradient(135deg, var(--ch-primary), var(--ch-accent));
    padding: 24px 20px; display: flex; justify-content: space-between;
    align-items: flex-start; color: #fff; position: relative;
    overflow: hidden;
}
.ch-grid-header::after {
    content: ''; position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.2) 1px, transparent 1px);
    background-size: 20px 20px; opacity: 0.3;
}
.ch-grid-date { position: relative; z-index: 1; }
.ch-grid-day {
    font-family: var(--ch-font-display); font-size: 2.5rem;
    font-weight: 800; line-height: 1; display: block;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
.ch-grid-month {
    font-size: 0.85rem; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; display: block; margin-top: 4px;
}
.ch-grid-year {
    font-size: 0.7rem; opacity: 0.85; display: block; margin-top: 2px;
}
.ch-grid-header .ch-badge {
    background: rgba(255,255,255,0.2); color: #fff;
    backdrop-filter: blur(10px); position: relative; z-index: 1;
}

.ch-grid-body { padding: 24px; flex: 1; position: relative; z-index: 1; }
.ch-grid-cat {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 0.75rem; color: var(--ch-primary); font-weight: 700;
    margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;
}
.ch-grid-title {
    font-size: 1.15rem; font-weight: 800; margin: 0 0 12px 0;
    line-height: 1.3; color: var(--ch-dark); font-family: var(--ch-font-display);
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
}
body.gl-dark .ch-grid-title { color: #F1F5F9; }
.ch-grid-desc {
    font-size: 0.88rem; color: var(--ch-gray-600); line-height: 1.5; margin: 0;
}
body.gl-dark .ch-grid-desc { color: #CBD5E1; }

.ch-grid-footer {
    padding: 16px 24px; background: var(--ch-gray-50);
    border-top: 1px solid var(--ch-gray-100);
    display: flex; justify-content: space-between; flex-wrap: wrap;
    gap: 12px; font-size: 0.82rem; color: var(--ch-gray-600);
}
body.gl-dark .ch-grid-footer { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.08); color: #94A3B8; }
.ch-grid-footer span { display: inline-flex; align-items: center; gap: 6px; }
.ch-grid-footer i { color: var(--ch-primary); }

/* ===== EMPTY STATE ===== */
.ch-empty-state {
    text-align: center; padding: 80px 20px; background: #fff;
    border-radius: 24px; border: 2px dashed var(--ch-gray-200);
    max-width: 600px; margin: 0 auto;
}
body.gl-dark .ch-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }
.ch-empty-icon {
    position: relative; width: 100px; height: 100px; margin: 0 auto 24px;
    display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: var(--ch-gray-600);
    animation: chEmptySpin 4s ease-in-out infinite;
}
@keyframes chEmptySpin {
    0%, 100% { transform: rotate(0deg); }
    50% { transform: rotate(180deg); }
}
.ch-empty-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--ch-primary); animation: chEmptyPulse 2s infinite;
}
@keyframes chEmptyPulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }
.ch-empty-state h3 { font-size: 1.5rem; margin: 0 0 8px 0; color: var(--ch-dark); font-weight: 800; font-family: var(--ch-font-display); }
body.gl-dark .ch-empty-state h3 { color: #F1F5F9; }
.ch-empty-state p { color: var(--ch-gray-600); margin-bottom: 24px; }
body.gl-dark .ch-empty-state p { color: #94A3B8; }
.ch-reset-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 24px; background: linear-gradient(135deg, var(--ch-primary), var(--ch-primary-dark));
    color: #fff; border-radius: 12px; font-weight: 600; text-decoration: none;
    transition: all 0.3s; font-family: inherit;
}
.ch-reset-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,0.4); }

/* ===== MODAL ===== */
.ch-modal-backdrop {
    position: fixed; inset: 0; background: rgba(15,23,42,0.85);
    backdrop-filter: blur(12px); z-index: 9999;
    display: flex; align-items: center; justify-content: center;
    opacity: 0; visibility: hidden; transition: all 0.3s; padding: 20px;
}
.ch-modal-backdrop.active { opacity: 1; visibility: visible; }

.ch-modal-container {
    background: #fff; border-radius: 28px; width: 100%; max-width: 700px;
    max-height: 90vh; overflow: hidden;
    box-shadow: 0 30px 60px rgba(0,0,0,0.3);
    transform: scale(0.9) translateY(30px);
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex; flex-direction: column; position: relative;
}
body.gl-dark .ch-modal-container { background: #1E293B; }
.ch-modal-backdrop.active .ch-modal-container { transform: scale(1) translateY(0); }

.ch-modal-close {
    position: absolute; top: 20px; right: 20px; z-index: 10;
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);
    border: none; color: var(--ch-dark); cursor: pointer; font-size: 1.1rem;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s;
}
body.gl-dark .ch-modal-close { background: rgba(0,0,0,0.5); color: #fff; }
.ch-modal-close:hover { background: #EF4444; color: #fff; transform: rotate(90deg); }

.ch-modal-portal {
    position: absolute; top: 40px; right: -60px; width: 200px; height: 200px;
    pointer-events: none; opacity: 0.3;
}
.ch-portal-ring-small {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--ch-primary);
    animation: chPortalSpin 10s linear infinite;
}
.ch-portal-ring-small.ch-ring-inner {
    inset: 20px; border-color: var(--ch-accent);
    animation-duration: 15s; animation-direction: reverse;
}

.ch-modal-content { overflow-y: auto; }

.ch-modal-header {
    padding: 36px 36px 24px; background: linear-gradient(135deg, var(--ch-primary), var(--ch-accent));
    color: #fff; display: flex; align-items: center; gap: 24px;
    position: relative; overflow: hidden;
}
.ch-modal-header::before {
    content: ''; position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.2) 1px, transparent 1px);
    background-size: 24px 24px; opacity: 0.3;
}

.ch-modal-date-badge {
    width: 80px; height: 80px; border-radius: 20px;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    flex-shrink: 0; border: 2px solid rgba(255,255,255,0.3);
    position: relative; z-index: 1;
}
.ch-modal-day {
    font-family: var(--ch-font-display); font-size: 2rem;
    font-weight: 800; line-height: 1;
}
.ch-modal-month {
    font-size: 0.75rem; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; margin-top: 4px;
}

.ch-modal-title-group { position: relative; z-index: 1; flex: 1; }
.ch-modal-badge {
    display: inline-block; padding: 4px 12px;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    border-radius: 20px; font-size: 0.7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;
}
.ch-modal-title-group h2 {
    font-size: 1.5rem; font-weight: 800; margin: 0 0 6px 0; line-height: 1.3;
    font-family: var(--ch-font-display);
}
.ch-modal-cat {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 0.85rem; opacity: 0.9;
}

.ch-modal-body { padding: 32px 36px; }

.ch-modal-meta-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
    margin-bottom: 28px;
}
.ch-modal-meta-item {
    padding: 16px; background: var(--ch-gray-50); border-radius: 14px;
    display: flex; align-items: center; gap: 12px;
    border: 1px solid var(--ch-gray-100);
}
body.gl-dark .ch-modal-meta-item { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.08); }
.ch-modal-meta-item i {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, var(--ch-primary), var(--ch-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.ch-modal-meta-item span { font-size: 0.75rem; color: var(--ch-gray-600); display: block; margin-bottom: 2px; }
body.gl-dark .ch-modal-meta-item span { color: #94A3B8; }
.ch-modal-meta-item strong {
    font-size: 0.95rem; color: var(--ch-dark); display: block;
    font-family: var(--ch-font-display); font-weight: 700;
}
body.gl-dark .ch-modal-meta-item strong { color: #F1F5F9; }

.ch-modal-desc {
    padding: 20px; background: var(--ch-gray-50);
    border-radius: 14px; margin-bottom: 24px;
    border-left: 4px solid var(--ch-primary);
}
body.gl-dark .ch-modal-desc { background: rgba(255,255,255,.03); }
.ch-modal-desc h4 {
    font-size: 1rem; margin: 0 0 12px 0; color: var(--ch-dark);
    display: flex; align-items: center; gap: 8px; font-weight: 700;
}
body.gl-dark .ch-modal-desc h4 { color: #F1F5F9; }
.ch-modal-desc h4 i { color: var(--ch-primary); }
.ch-modal-desc p { margin: 0; color: var(--ch-gray-600); line-height: 1.7; white-space: pre-wrap; }
body.gl-dark .ch-modal-desc p { color: #CBD5E1; }

.ch-modal-actions { display: flex; gap: 12px; }
.ch-modal-btn {
    flex: 1; padding: 14px 20px; background: var(--ch-gray-100);
    border: 1px solid var(--ch-gray-200); border-radius: 12px;
    color: var(--ch-gray-600); cursor: pointer; font-weight: 600;
    transition: all 0.3s; display: flex; align-items: center; justify-content: center;
    gap: 8px; font-family: inherit; font-size: 0.9rem;
}
body.gl-dark .ch-modal-btn { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.ch-modal-btn.primary {
    background: linear-gradient(135deg, var(--ch-primary), var(--ch-primary-dark));
    color: #fff; border-color: transparent;
}
.ch-modal-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }

/* ===== TOAST ===== */
.ch-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--ch-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    display: flex; align-items: center; gap: 10px;
    font-size: 0.9rem; opacity: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10000; font-weight: 500;
}
body.gl-dark .ch-toast { background: #fff; color: var(--ch-dark); }
.ch-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.ch-toast i { color: var(--ch-primary); font-size: 1.2rem; }

/* ===== ANIMATIONS ===== */
.fade-up { opacity: 0; transform: translateY(30px); transition: opacity 0.8s ease, transform 0.8s ease; }
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .ch-layout { grid-template-columns: 1fr; }
    .ch-sidebar {
        position: static; order: 2;
        display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
    }
    .ch-calendar-widget { grid-column: span 2; }
    .ch-main { order: 1; }
}
@media (max-width: 768px) {
    .ch-hero { padding: 120px 20px 80px; min-height: 85vh; }
    .ch-hero-title { font-size: 3rem; }
    .ch-hero-stats { grid-template-columns: 1fr 1fr; }
    .ch-stat-pill { padding: 12px 16px; }
    .ch-countdown-content { grid-template-columns: 1fr; }
    .ch-countdown-timer { flex-wrap: wrap; justify-content: center; }
    .ch-control-bar { flex-direction: column; align-items: stretch; }
    .ch-search-box { max-width: 100%; }
    .ch-view-toggles { justify-content: center; }
    .ch-view-btn span { display: none; }
    .ch-timeline-item { grid-template-columns: 80px 40px 1fr; }
    .ch-date-box { padding: 10px 14px; min-width: 70px; }
    .ch-date-day { font-size: 1.5rem; }
    .ch-date-month { font-size: 0.7rem; }
    .ch-timeline-dot { width: 40px; height: 40px; font-size: 0.9rem; }
    .ch-timeline-content { padding: 20px; }
    .ch-timeline-title { font-size: 1.15rem; }
    .ch-grid-view { grid-template-columns: 1fr; }
    .ch-sidebar { grid-template-columns: 1fr; }
    .ch-calendar-widget { grid-column: span 1; }
    .ch-clock-display { font-size: 2rem; }
    .ch-modal-meta-grid { grid-template-columns: 1fr; }
    .ch-modal-header { padding: 24px; flex-direction: column; text-align: center; }
    .ch-modal-body { padding: 24px; }
    .ch-modal-actions { flex-direction: column; }
    .ch-portal-ring, .ch-portal-ring.ch-ring-2 { display: none; }
    .ch-floating-time { display: none; }
    .ch-console-body { padding: 16px; }
    .ch-filter-pills { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 6px; }
    .ch-pill { white-space: nowrap; flex-shrink: 0; }
}
@media (max-width: 480px) {
    .ch-hero-title { font-size: 2.5rem; }
    .ch-live-clock { padding: 16px 20px; }
    .ch-clock-display { font-size: 1.6rem; }
    .ch-clock-info { flex-direction: column; gap: 6px; }
    .ch-timeline-item { grid-template-columns: 1fr; gap: 16px; }
    .ch-timeline-date { justify-content: flex-start; padding: 0; }
    .ch-timeline-line { display: none; }
    .ch-card-actions { flex-wrap: wrap; }
    .ch-action-btn { flex: 1; justify-content: center; min-width: 100px; }
}
</style>

<!-- ============================================================
     SPECTACULAR SCRIPTS (Null-Safe, Anti-Error)
============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // 1. Clock Canvas Animation (UNIQUE - Futuristic Clock with particles)
    const canvas = document.getElementById('chClockCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let width, height, centerX, centerY;
        let time = 0;

        function resize() {
            width = canvas.width = canvas.parentElement.offsetWidth;
            height = canvas.height = canvas.parentElement.offsetHeight;
            centerX = width / 2;
            centerY = height / 2;
        }
        resize();
        window.addEventListener('resize', resize);

        function drawClock() {
            ctx.clearRect(0, 0, width, height);
            
            const radius = Math.min(width, height) * 0.3;
            const now = new Date();
            const hours = now.getHours() % 12;
            const minutes = now.getMinutes();
            const seconds = now.getSeconds();
            const ms = now.getMilliseconds();
            
            // Outer ring (glowing)
            const gradient = ctx.createRadialGradient(centerX, centerY, radius * 0.9, centerX, centerY, radius * 1.1);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.1)');
            gradient.addColorStop(0.5, 'rgba(16, 185, 129, 0.05)');
            gradient.addColorStop(1, 'transparent');
            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius * 1.1, 0, Math.PI * 2);
            ctx.fill();
            
            // Clock circle outline
            ctx.strokeStyle = 'rgba(52, 211, 153, 0.4)';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
            ctx.stroke();
            
            // Inner rings
            ctx.strokeStyle = 'rgba(139, 92, 246, 0.2)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius * 0.7, 0, Math.PI * 2);
            ctx.stroke();
            
            ctx.strokeStyle = 'rgba(245, 158, 11, 0.2)';
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius * 0.4, 0, Math.PI * 2);
            ctx.stroke();
            
            // Hour markers
            for (let i = 0; i < 12; i++) {
                const angle = (i * Math.PI) / 6 - Math.PI / 2;
                const innerR = radius * 0.9;
                const outerR = radius;
                const x1 = centerX + Math.cos(angle) * innerR;
                const y1 = centerY + Math.sin(angle) * innerR;
                const x2 = centerX + Math.cos(angle) * outerR;
                const y2 = centerY + Math.sin(angle) * outerR;
                
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.6)';
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.moveTo(x1, y1);
                ctx.lineTo(x2, y2);
                ctx.stroke();
                
                // Number
                const numR = radius * 1.15;
                const nx = centerX + Math.cos(angle) * numR;
                const ny = centerY + Math.sin(angle) * numR;
                ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
                ctx.font = '14px "Space Grotesk", sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(i === 0 ? '12' : i.toString(), nx, ny);
            }
            
            // Minute markers
            for (let i = 0; i < 60; i++) {
                if (i % 5 === 0) continue;
                const angle = (i * Math.PI) / 30 - Math.PI / 2;
                const innerR = radius * 0.95;
                const outerR = radius;
                const x1 = centerX + Math.cos(angle) * innerR;
                const y1 = centerY + Math.sin(angle) * innerR;
                const x2 = centerX + Math.cos(angle) * outerR;
                const y2 = centerY + Math.sin(angle) * outerR;
                
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(x1, y1);
                ctx.lineTo(x2, y2);
                ctx.stroke();
            }
            
            // Hour hand
            const hourAngle = ((hours + minutes / 60) * Math.PI) / 6 - Math.PI / 2;
            ctx.strokeStyle = '#F59E0B';
            ctx.lineWidth = 6;
            ctx.lineCap = 'round';
            ctx.shadowBlur = 15;
            ctx.shadowColor = '#F59E0B';
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.lineTo(centerX + Math.cos(hourAngle) * radius * 0.5, centerY + Math.sin(hourAngle) * radius * 0.5);
            ctx.stroke();
            
            // Minute hand
            const minAngle = ((minutes + seconds / 60) * Math.PI) / 30 - Math.PI / 2;
            ctx.strokeStyle = '#10B981';
            ctx.lineWidth = 4;
            ctx.shadowColor = '#10B981';
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.lineTo(centerX + Math.cos(minAngle) * radius * 0.75, centerY + Math.sin(minAngle) * radius * 0.75);
            ctx.stroke();
            
            // Second hand
            const secAngle = ((seconds + ms / 1000) * Math.PI) / 30 - Math.PI / 2;
            ctx.strokeStyle = '#8B5CF6';
            ctx.lineWidth = 2;
            ctx.shadowColor = '#8B5CF6';
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.lineTo(centerX + Math.cos(secAngle) * radius * 0.85, centerY + Math.sin(secAngle) * radius * 0.85);
            ctx.stroke();
            
            ctx.shadowBlur = 0;
            
            // Center dot
            ctx.fillStyle = '#fff';
            ctx.beginPath();
            ctx.arc(centerX, centerY, 6, 0, Math.PI * 2);
            ctx.fill();
            
            // Floating particles
            for (let i = 0; i < 30; i++) {
                const angle = (time * 0.01 + i * 0.21) % (Math.PI * 2);
                const dist = radius * 1.3 + Math.sin(time * 0.02 + i) * 30;
                const px = centerX + Math.cos(angle) * dist;
                const py = centerY + Math.sin(angle) * dist;
                const size = 1 + Math.sin(time * 0.03 + i) * 0.5;
                
                ctx.fillStyle = `rgba(52, 211, 153, ${0.3 + Math.sin(time * 0.02 + i) * 0.2})`;
                ctx.beginPath();
                ctx.arc(px, py, size, 0, Math.PI * 2);
                ctx.fill();
            }
            
            time++;
            requestAnimationFrame(drawClock);
        }
        drawClock();
    }

    // 2. Live Clock Update
    function updateLiveClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        const hEl = document.getElementById('chLiveHours');
        const mEl = document.getElementById('chLiveMinutes');
        const sEl = document.getElementById('chLiveSeconds');
        const dateEl = document.getElementById('chLiveDate');
        
        if (hEl) hEl.textContent = hours;
        if (mEl) mEl.textContent = minutes;
        if (sEl) sEl.textContent = seconds;
        
        if (dateEl) {
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            dateEl.textContent = now.toLocaleDateString('id-ID', options);
        }
    }
    updateLiveClock();
    setInterval(updateLiveClock, 1000);

    // 3. Typing Effect
    const textElement = document.getElementById('chTypingText');
    if (textElement) {
        const phrases = [
            "Jadwal lengkap kegiatan akademik, seminar, dan workshop Program Studi Informatika.",
            "Portal waktu untuk menjelajahi setiap momen penting di kampus kita.",
            "Jangan lewatkan satu pun event yang akan membentuk masa depan Anda.",
            "Dari akademik hingga kemahasiswaan, semua tercatat di sini."
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

    // 4. Counter Animation
    const counters = document.querySelectorAll('.ch-stat-num[data-count]');
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

    // 5. Countdown Timer (if exists)
    const countdownTimer = document.querySelector('.ch-countdown-timer');
    if (countdownTimer) {
        const targetTime = parseInt(countdownTimer.getAttribute('data-target')) * 1000;
        const daysEl = countdownTimer.querySelector('[data-days]');
        const hoursEl = countdownTimer.querySelector('[data-hours]');
        const minutesEl = countdownTimer.querySelector('[data-minutes]');
        const secondsEl = countdownTimer.querySelector('[data-seconds]');
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = targetTime - now;
            
            if (distance > 0) {
                if (daysEl) daysEl.textContent = String(Math.floor(distance / (1000 * 60 * 60 * 24))).padStart(2, '0');
                if (hoursEl) hoursEl.textContent = String(Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
                if (minutesEl) minutesEl.textContent = String(Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                if (secondsEl) secondsEl.textContent = String(Math.floor((distance % (1000 * 60)) / 1000)).padStart(2, '0');
            } else {
                if (daysEl) daysEl.textContent = '00';
                if (hoursEl) hoursEl.textContent = '00';
                if (minutesEl) minutesEl.textContent = '00';
                if (secondsEl) secondsEl.textContent = '00';
            }
        }
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    // 6. Reading Progress Bar
    const progressFill = document.getElementById('chProgressFill');
    if (progressFill) {
        window.addEventListener('scroll', () => {
            const scrolled = window.scrollY;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const percent = maxScroll > 0 ? (scrolled / maxScroll) * 100 : 0;
            progressFill.style.width = percent + '%';
        }, { passive: true });
    }

    // 7. 3D Tilt Effect
    const tiltElements = document.querySelectorAll('.ch-tilt');
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

    // 8. View Toggle Logic
    const viewBtns = document.querySelectorAll('.ch-view-btn');
    const timelineView = document.getElementById('chTimeline');
    const gridView = document.getElementById('chGrid');
    const container = document.getElementById('chAgendaContainer');

    const savedView = localStorage.getItem('ch_agenda_view') || 'timeline';
    applyView(savedView);

    viewBtns.forEach(btn => {
        if (!btn) return;
        btn.addEventListener('click', () => {
            const view = btn.dataset.view;
            applyView(view);
            localStorage.setItem('ch_agenda_view', view);
        });
    });

    function applyView(view) {
        viewBtns.forEach(b => b.classList.remove('active'));
        const activeBtn = document.querySelector(`.ch-view-btn[data-view="${view}"]`);
        if (activeBtn) activeBtn.classList.add('active');

        if (view === 'grid') {
            if (timelineView) timelineView.style.display = 'none';
            if (gridView) gridView.style.display = 'grid';
            if (container) container.className = 'ch-grid-view-container';
        } else {
            if (gridView) gridView.style.display = 'none';
            if (timelineView) timelineView.style.display = 'block';
            if (container) container.className = 'ch-timeline-view';
        }
        
        // Re-trigger fade-up animations
        const activeContainer = view === 'grid' ? gridView : timelineView;
        if (activeContainer) {
            activeContainer.querySelectorAll('.fade-up').forEach(el => {
                el.classList.remove('visible');
                setTimeout(() => el.classList.add('visible'), 50);
            });
        }
    }

    // 9. Scroll Reveal Animation
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

    // 10. Stat Bars Animation
    const statBarsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target) {
                const bars = entry.target.querySelectorAll('.ch-stat-bar-fill');
                bars.forEach(bar => {
                    const targetWidth = bar.style.width;
                    bar.style.width = '0';
                    setTimeout(() => {
                        bar.style.width = targetWidth;
                    }, 200);
                });
                statBarsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    
    const quickStats = document.querySelector('.ch-quick-stats');
    if (quickStats) statBarsObserver.observe(quickStats);

    // 11. Parallax Effect
    const heroSection = document.querySelector('.ch-hero');
    if (heroSection && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const orbs = heroSection.querySelectorAll('.ch-orb');
            orbs.forEach((orb, index) => {
                const speed = (index + 1) * 0.3;
                orb.style.transform = `translateY(${scrolled * speed}px)`;
            });
        }, { passive: true });
    }

    // 12. Modal Functions
    let eventJsonData = [];
    try {
        const scriptTag = document.getElementById('chEventData');
        if (scriptTag) {
            eventJsonData = JSON.parse(scriptTag.textContent);
        }
    } catch (e) {
        console.error('Failed to parse event data:', e);
    }

    window.chOpenEvent = function(index) {
        const data = eventJsonData[index];
        if (!data) return;
        
        const modal = document.getElementById('chModal');
        if (!modal) return;
        
        const set = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val || '-'; };
        
        set('chModalDay', data.day);
        set('chModalMonth', data.month);
        set('chModalTitle', data.judul);
        set('chModalBadge', data.status_label);
        set('chModalCat', data.kategori);
        set('chModalLocation', data.lokasi || 'Tidak ditentukan');
        set('chModalTime', data.time);
        set('chModalDate', data.date);
        set('chModalDesc', data.deskripsi || 'Tidak ada deskripsi untuk event ini.');
        
        modal.classList.add('active');
        modal.dataset.currentIndex = index;
        document.body.style.overflow = 'hidden';
    };

    window.chCloseModal = function() {
        const modal = document.getElementById('chModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    const modalBackdrop = document.getElementById('chModal');
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', function(e) {
            if (e.target === this) window.chCloseModal();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') window.chCloseModal();
    });

    // 13. Share Event
    window.chShareEvent = function(title) {
        const url = window.location.href;
        if (navigator.share) {
            navigator.share({ title: title, text: 'Check out this event at Informatika Pro!', url: url })
                .catch(err => console.log('Share cancelled', err));
        } else {
            navigator.clipboard.writeText(url).then(() => {
                window.chShowToast('Link event disalin ke clipboard!');
            });
        }
    };

    window.chShareFromModal = function() {
        const modal = document.getElementById('chModal');
        if (!modal) return;
        const index = modal.dataset.currentIndex;
        const data = eventJsonData[index];
        if (data) window.chShareEvent(data.judul);
    };

    // 14. Add to Calendar
    window.chAddToCalendar = function(title, startTime, location) {
        const endDate = new Date(startTime);
        endDate.setHours(endDate.getHours() + 2);
        const endTime = endDate.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
        const startISO = new Date(startTime).toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
        
        const googleUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(title)}&dates=${startISO}/${endTime}&location=${encodeURIComponent(location)}`;
        window.open(googleUrl, '_blank');
    };

    window.chAddToCalendarFromModal = function() {
        const modal = document.getElementById('chModal');
        if (!modal) return;
        const index = modal.dataset.currentIndex;
        const data = eventJsonData[index];
        if (data) window.chAddToCalendar(data.judul, data.iso, data.lokasi);
    };

    // 15. Reset Filters
    window.chResetFilters = function() {
        window.location.href = 'agenda.php';
    };

    // 16. Toast Notification
    window.chShowToast = function(message) {
        const toast = document.getElementById('chToast');
        if (!toast) return;
        const msgEl = document.getElementById('chToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // Welcome toast
    setTimeout(() => {
        window.chShowToast('⏰ Selamat datang di Chronos Portal!');
    }, 1500);

    // 17. Console Easter Egg
    console.log('%c╔════════════════════════════════════════╗', 'color:#10B981;font-weight:bold');
    console.log('%c║   CHRONOS PORTAL - Agenda & Kegiatan   ║', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c╚════════════════════════════════════════╝', 'color:#10B981;font-weight:bold');
    console.log('%c⏰ ' + eventJsonData.length + ' events loaded', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>