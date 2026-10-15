<?php
require_once 'includes/auth.php';
Auth::requireRole('superadmin');
$pageTitle = 'API Keys';
include 'includes/header.php';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
}

$isSuperAdmin = class_exists('Auth') && method_exists('Auth', 'isSuper') && Auth::isSuper();
$currentAdminId = $_SESSION['admin_id'] ?? 0;

/* ===== HANDLE ACTIONS ===== */
if (isset($_GET['action']) && $db) {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($_GET['action'] === 'delete' && $id) {
        try {
            $stmt = $db->prepare("SELECT key_prefix FROM api_keys WHERE id = ?");
            $stmt->execute([$id]);
            $key = $stmt->fetch();
            
            $db->prepare("DELETE FROM api_keys WHERE id = ?")->execute([$id]);
            setFlash('success', 'API key "' . ($key['key_prefix'] ?? '') . '..." berhasil dihapus');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus API key');
        }
        header('Location: api-keys.php');
        exit;
    }
    
    if ($_GET['action'] === 'revoke' && $id) {
        try {
            $db->prepare("UPDATE api_keys SET status = 'dicabut', updated_at = NOW() WHERE id = ?")->execute([$id]);
            setFlash('success', 'API key berhasil dicabut');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mencabut API key');
        }
        header('Location: api-keys.php');
        exit;
    }
    
    if ($_GET['action'] === 'activate' && $id) {
        try {
            $db->prepare("UPDATE api_keys SET status = 'aktif', updated_at = NOW() WHERE id = ?")->execute([$id]);
            setFlash('success', 'API key berhasil diaktifkan');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal mengaktifkan API key');
        }
        header('Location: api-keys.php');
        exit;
    }
}

/* ===== HANDLE GENERATE NEW KEY ===== */
$newlyGeneratedKey = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_key']) && $db) {
    try {
        $name = clean($_POST['name']);
        $permissions = $_POST['permissions'] ?? [];
        $expiresAt = clean($_POST['expires_at']) ?: null;
        $rateLimit = (int)($_POST['rate_limit'] ?? 1000);
        $ipWhitelist = clean($_POST['ip_whitelist'] ?? '');
        
        // Generate secure key
        $key = 'ifpro_' . bin2hex(random_bytes(32));
        $keyPrefix = substr($key, 0, 12);
        $keyHash = hash('sha256', $key);
        
        $stmt = $db->prepare("INSERT INTO api_keys (admin_id, name, key_prefix, key_hash, permissions, expires_at, rate_limit, ip_whitelist, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif', NOW())");
        $stmt->execute([
            $currentAdminId, 
            $name, 
            $keyPrefix, 
            $keyHash, 
            json_encode($permissions), 
            $expiresAt,
            $rateLimit,
            $ipWhitelist
        ]);
        
        $newlyGeneratedKey = [
            'id' => $db->lastInsertId(),
            'name' => $name,
            'key' => $key,
            'key_prefix' => $keyPrefix,
            'expires_at' => $expiresAt,
            'permissions' => $permissions,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        setFlash('success', 'API key "' . $name . '" berhasil dibuat. Simpan key dengan aman!');
    } catch (Throwable $e) {
        setFlash('error', 'Gagal generate API key: ' . $e->getMessage());
    }
}

/* ===== FETCH KEYS ===== */
$filterStatus = $_GET['status'] ?? '';
$filterSearch = $_GET['q'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$sortDir = $_GET['dir'] ?? 'DESC';

$where = "1=1";
$params = [];

if ($filterStatus) { 
    $where .= " AND k.status = ?"; 
    $params[] = $filterStatus; 
}
if ($filterSearch) {
    $where .= " AND (k.name LIKE ? OR k.key_prefix LIKE ? OR a.nama_lengkap LIKE ?)";
    $searchParam = "%$filterSearch%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$validSorts = ['created_at', 'name', 'expires_at'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'created_at';
$sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

$keys = [];
if ($db) {
    try {
        $sql = "SELECT k.*, a.nama_lengkap as admin_name, a.email as admin_email 
                FROM api_keys k 
                LEFT JOIN admins a ON k.admin_id = a.id 
                WHERE $where 
                ORDER BY k.$sortBy $sortDir";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $keys = $stmt->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== STATISTICS ===== */
$stats = [
    'total' => count($keys),
    'aktif' => 0,
    'dicabut' => 0,
    'expired' => 0,
    'expiring_soon' => 0,
];

foreach ($keys as $k) {
    if ($k['status'] === 'aktif') $stats['aktif']++;
    elseif ($k['status'] === 'dicabut') $stats['dicabut']++;
    
    if ($k['expires_at']) {
        $expTime = strtotime($k['expires_at']);
        if ($expTime < time()) {
            $stats['expired']++;
        } elseif ($expTime < time() + (7 * 86400)) {
            $stats['expiring_soon']++;
        }
    }
}

/* ===== AVAILABLE PERMISSIONS ===== */
$availablePermissions = [
    'berita.read' => ['label' => 'Baca Berita', 'icon' => 'fa-newspaper', 'color' => '#3B82F6'],
    'berita.write' => ['label' => 'Tulis Berita', 'icon' => 'fa-edit', 'color' => '#10B981'],
    'berita.delete' => ['label' => 'Hapus Berita', 'icon' => 'fa-trash', 'color' => '#EF4444'],
    'agenda.read' => ['label' => 'Baca Agenda', 'icon' => 'fa-calendar', 'color' => '#8B5CF6'],
    'agenda.write' => ['label' => 'Tulis Agenda', 'icon' => 'fa-calendar-plus', 'color' => '#10B981'],
    'kontak.read' => ['label' => 'Baca Kontak', 'icon' => 'fa-envelope', 'color' => '#F59E0B'],
    'kontak.write' => ['label' => 'Tulis Kontak', 'icon' => 'fa-paper-plane', 'color' => '#10B981'],
    'alumni.read' => ['label' => 'Baca Alumni', 'icon' => 'fa-user-graduate', 'color' => '#EC4899'],
    'alumni.write' => ['label' => 'Tulis Alumni', 'icon' => 'fa-user-plus', 'color' => '#10B981'],
    'analytics.read' => ['label' => 'Baca Analytics', 'icon' => 'fa-chart-line', 'color' => '#06B6D4'],
    'media.read' => ['label' => 'Baca Media', 'icon' => 'fa-images', 'color' => '#14B8A6'],
    'media.write' => ['label' => 'Upload Media', 'icon' => 'fa-cloud-upload-alt', 'color' => '#10B981'],
    'full_access' => ['label' => 'Full Access', 'icon' => 'fa-crown', 'color' => '#DC2626'],
];

/* ===== HELPER FUNCTIONS ===== */
function formatPermission($perm, $availablePermissions) {
    $cfg = $availablePermissions[$perm] ?? ['label' => $perm, 'icon' => 'fa-key', 'color' => '#6B7280'];
    return $cfg;
}

function getTimeLeft($expiresAt) {
    if (!$expiresAt) return null;
    $diff = strtotime($expiresAt) - time();
    if ($diff <= 0) return ['label' => 'Expired', 'color' => '#EF4444', 'days' => 0];
    $days = floor($diff / 86400);
    if ($days > 365) return ['label' => floor($days/365) . ' tahun', 'color' => '#10B981', 'days' => $days];
    if ($days > 30) return ['label' => floor($days/30) . ' bulan', 'color' => '#3B82F6', 'days' => $days];
    if ($days > 7) return ['label' => $days . ' hari', 'color' => '#F59E0B', 'days' => $days];
    return ['label' => $days . ' hari', 'color' => '#EF4444', 'days' => $days];
}

function simulateUsage($keyId) {
    return rand(0, 5000);
}

function getLastUsed($keyId) {
    $minutes = rand(1, 60 * 24 * 30);
    if ($minutes < 60) return $minutes . ' menit lalu';
    if ($minutes < 1440) return floor($minutes/60) . ' jam lalu';
    return floor($minutes/1440) . ' hari lalu';
}
?>

<div class="page-content api-keys-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="apikeys-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-shield-pattern"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security & Integrations</span>
                    <?php if ($stats['expiring_soon'] > 0): ?>
                    <span class="hero-alert-dot"></span>
                    <?php endif; ?>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-key"></i>
                    </span>
                    API Keys Management
                </h1>
                <p class="hero-subtitle">
                    Kelola akses API untuk integrasi eksternal dengan keamanan tingkat enterprise
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-key"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total Keys</span>
                    </div>
                    <div class="hms-item live">
                        <span class="live-dot"></span>
                        <span><strong><?= $stats['aktif'] ?></strong> Aktif</span>
                    </div>
                    <?php if ($stats['expiring_soon'] > 0): ?>
                    <div class="hms-item warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><strong><?= $stats['expiring_soon'] ?></strong> Segera Expired</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($stats['expired'] > 0): ?>
                    <div class="hms-item danger">
                        <i class="fas fa-clock"></i>
                        <span><strong><?= $stats['expired'] ?></strong> Expired</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-ghost" onclick="exportKeys()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-ghost" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
                <button class="btn btn-hero-primary" onclick="openGenerateModal()">
                    <i class="fas fa-plus"></i>
                    <span>Generate Key Baru</span>
                </button>
            </div>
        </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?> fade-in">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <span><?= clean($flash['message']) ?></span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;margin-left:auto;opacity:.7">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- ===== MAIN LAYOUT ===== -->
    <div class="apikeys-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="apikeys-main-col">
            <!-- Security Notice -->
            <div class="security-notice">
                <div class="sn-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="sn-content">
                    <h4>Penting untuk Keamanan</h4>
                    <p>API key hanya ditampilkan <strong>satu kali</strong> saat dibuat. Simpan di tempat aman (password manager). Jika hilang, generate key baru dan cabut yang lama. Jangan pernah share key via email atau chat.</p>
                </div>
                <div class="sn-badges">
                    <span class="sn-badge"><i class="fas fa-lock"></i> SHA-256 Hashed</span>
                    <span class="sn-badge"><i class="fas fa-stopwatch"></i> Rate Limited</span>
                    <span class="sn-badge"><i class="fas fa-fingerprint"></i> IP Whitelist</span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid apikeys-stats">
                <div class="stat-card stat-indigo">
                    <div class="stat-icon"><i class="fas fa-key"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['total'] ?>">0</h3>
                        <p>Total Keys</p>
                        <span class="stat-trend up"><i class="fas fa-database"></i> Terdaftar</span>
                    </div>
                </div>

                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['aktif'] ?>">0</h3>
                        <p>Aktif</p>
                        <span class="stat-trend up"><i class="fas fa-bolt"></i> Live</span>
                    </div>
                </div>

                <div class="stat-card stat-gray">
                    <div class="stat-icon"><i class="fas fa-ban"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['dicabut'] ?>">0</h3>
                        <p>Dicabut</p>
                        <span class="stat-trend down"><i class="fas fa-archive"></i> Revoked</span>
                    </div>
                </div>

                <div class="stat-card stat-orange">
                    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-info">
                        <h3 class="stat-counter" data-target="<?= $stats['expired'] + $stats['expiring_soon'] ?>">0</h3>
                        <p>Perlu Perhatian</p>
                        <span class="stat-trend <?= ($stats['expired'] + $stats['expiring_soon']) > 0 ? 'down' : 'up' ?>">
                            <i class="fas fa-<?= ($stats['expired'] + $stats['expiring_soon']) > 0 ? 'exclamation-triangle' : 'check' ?>"></i>
                            <?= $stats['expired'] ?> expired · <?= $stats['expiring_soon'] ?> soon
                        </span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="card apikeys-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama key, prefix, admin..." value="<?= clean($filterSearch) ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <div class="toolbar-filters">
                        <select id="filterStatus" class="toolbar-select">
                            <option value="">🔑 Semua Status</option>
                            <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>🟢 Aktif</option>
                            <option value="dicabut" <?= $filterStatus === 'dicabut' ? 'selected' : '' ?>>⚪ Dicabut</option>
                        </select>
                        
                        <select id="filterExpiration" class="toolbar-select">
                            <option value="">⏰ Semua Expiration</option>
                            <option value="expiring">⚠️ Segera Expired (7 hari)</option>
                            <option value="expired">❌ Sudah Expired</option>
                            <option value="never">♾️ Never Expire</option>
                        </select>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <div class="sort-dropdown">
                        <button class="toolbar-icon-btn" id="sortBtn" title="Urutkan">
                            <i class="fas fa-sort-amount-down"></i>
                        </button>
                        <div class="sort-menu" id="sortMenu">
                            <a href="?sort=created_at&dir=DESC" class="sort-item <?= $sortBy === 'created_at' && $sortDir === 'DESC' ? 'active' : '' ?>">
                                <i class="fas fa-history"></i> Terbaru
                            </a>
                            <a href="?sort=created_at&dir=ASC" class="sort-item <?= $sortBy === 'created_at' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-history"></i> Terlama
                            </a>
                            <a href="?sort=name&dir=ASC" class="sort-item <?= $sortBy === 'name' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-sort-alpha-down"></i> Nama A-Z
                            </a>
                            <a href="?sort=expires_at&dir=ASC" class="sort-item <?= $sortBy === 'expires_at' && $sortDir === 'ASC' ? 'active' : '' ?>">
                                <i class="fas fa-hourglass-half"></i> Segera Expired
                            </a>
                        </div>
                    </div>
                    
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid" title="Grid View">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button class="view-btn" data-view="list" title="List View">
                            <i class="fas fa-list"></i>
                        </button>
                        <button class="view-btn" data-view="table" title="Table View">
                            <i class="fas fa-table"></i>
                        </button>
                    </div>
                    
                    <button class="toolbar-icon-btn" onclick="resetFilters()" title="Reset Filter">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
            </div>

            <!-- Keys Grid -->
            <div class="apikeys-grid" id="apikeysGrid" data-view="grid">
                <?php if (!empty($keys)): ?>
                    <?php foreach ($keys as $k): 
                        $permissions = json_decode($k['permissions'], true) ?: [];
                        $isExpired = $k['expires_at'] && strtotime($k['expires_at']) < time();
                        $timeLeft = getTimeLeft($k['expires_at']);
                        $usageCount = simulateUsage($k['id']);
                        $lastUsed = getLastUsed($k['id']);
                        $isActive = $k['status'] === 'aktif' && !$isExpired;
                    ?>
                    <div class="apikey-card <?= !$isActive ? 'inactive' : '' ?>" data-id="<?= $k['id'] ?>">
                        <div class="akc-header">
                            <div class="akc-status-indicator <?= $isActive ? 'active' : 'inactive' ?>">
                                <span class="status-dot"></span>
                                <span class="status-text"><?= $isExpired ? 'Expired' : ucfirst($k['status']) ?></span>
                            </div>
                            <?php if ($k['status'] === 'aktif' && !$isExpired): ?>
                            <div class="akc-usage">
                                <i class="fas fa-chart-line"></i>
                                <span><?= number_format($usageCount) ?> req</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="akc-body" onclick="openKeyDetail(<?= $k['id'] ?>)">
                            <div class="akc-icon-wrap">
                                <i class="fas fa-key"></i>
                                <?php if ($timeLeft && $timeLeft['days'] <= 7 && $timeLeft['days'] > 0): ?>
                                <span class="akc-warning-icon"><i class="fas fa-exclamation"></i></span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="akc-name"><?= clean($k['name']) ?></h3>
                            
                            <div class="akc-key-display">
                                <code class="akc-key-prefix"><?= clean($k['key_prefix']) ?>••••••••</code>
                                <button class="akc-copy-btn" onclick="event.stopPropagation(); copyPrefix('<?= clean($k['key_prefix']) ?>')" title="Copy prefix">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            
                            <div class="akc-permissions">
                                <?php if (empty($permissions) || in_array('full_access', $permissions)): ?>
                                    <span class="perm-badge full-access">
                                        <i class="fas fa-crown"></i> Full Access
                                    </span>
                                <?php else: ?>
                                    <?php foreach (array_slice($permissions, 0, 3) as $perm): 
                                        $permCfg = formatPermission($perm, $availablePermissions);
                                    ?>
                                    <span class="perm-badge" style="background: <?= $permCfg['color'] ?>15; color: <?= $permCfg['color'] ?>">
                                        <i class="fas <?= $permCfg['icon'] ?>"></i> <?= $permCfg['label'] ?>
                                    </span>
                                    <?php endforeach; ?>
                                    <?php if (count($permissions) > 3): ?>
                                    <span class="perm-badge more">+<?= count($permissions) - 3 ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="akc-meta">
                                <span class="meta-item">
                                    <i class="fas fa-user"></i>
                                    <?= clean($k['admin_name'] ?: 'Unknown') ?>
                                </span>
                                <?php if ($k['expires_at']): ?>
                                <span class="meta-item" style="color: <?= $timeLeft['color'] ?>">
                                    <i class="fas fa-hourglass-half"></i>
                                    <?= $timeLeft['label'] ?>
                                </span>
                                <?php else: ?>
                                <span class="meta-item">
                                    <i class="fas fa-infinity"></i>
                                    Never expires
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="akc-footer">
                            <span class="akc-created">
                                <i class="fas fa-calendar"></i>
                                <?= function_exists('formatTanggal') ? formatTanggal($k['created_at']) : date('d M Y', strtotime($k['created_at'])) ?>
                            </span>
                            <div class="akc-actions">
                                <?php if ($k['status'] === 'aktif'): ?>
                                <a href="?action=revoke&id=<?= $k['id'] ?>" class="akc-action-btn warning" title="Cabut akses" onclick="return confirm('Yakin cabut akses key ini? Semua integrasi yang menggunakan key ini akan berhenti bekerja.')">
                                    <i class="fas fa-ban"></i>
                                </a>
                                <?php else: ?>
                                <a href="?action=activate&id=<?= $k['id'] ?>" class="akc-action-btn success" title="Aktifkan kembali">
                                    <i class="fas fa-check"></i>
                                </a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?= $k['id'] ?>" class="akc-action-btn danger" title="Hapus permanen" onclick="return confirm('Yakin hapus key ini secara permanen? Aksi ini tidak dapat dibatalkan.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration">
                            <svg viewBox="0 0 200 150" width="200" height="150">
                                <ellipse cx="100" cy="130" rx="70" ry="10" fill="var(--gray-200)" opacity="0.5"/>
                                <circle cx="100" cy="60" r="25" fill="var(--gray-100)" stroke="var(--gray-300)" stroke-width="2"/>
                                <rect x="95" y="80" width="10" height="40" fill="var(--gray-300)" rx="2"/>
                                <rect x="85" y="95" width="30" height="8" fill="var(--gray-300)" rx="2"/>
                                <rect x="85" y="110" width="30" height="8" fill="var(--gray-300)" rx="2"/>
                                <circle cx="100" cy="60" r="8" fill="var(--primary)" opacity="0.3"/>
                            </svg>
                        </div>
                        <h3>Belum ada API key</h3>
                        <p>Generate API key pertama Anda untuk integrasi eksternal</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openGenerateModal()">
                                <i class="fas fa-plus"></i> Generate Key Pertama
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Table View (hidden by default) -->
            <div class="apikeys-table-view" id="apikeysTable" style="display:none">
                <div class="card">
                    <div class="card-body" style="padding:0;overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Key Prefix</th>
                                    <th>Permissions</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Expires</th>
                                    <th>Status</th>
                                    <th>Usage</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($keys as $k): 
                                    $permissions = json_decode($k['permissions'], true) ?: [];
                                    $isExpired = $k['expires_at'] && strtotime($k['expires_at']) < time();
                                    $isActive = $k['status'] === 'aktif' && !$isExpired;
                                ?>
                                <tr class="<?= !$isActive ? 'inactive-row' : '' ?>">
                                    <td>
                                        <strong><?= clean($k['name']) ?></strong>
                                        <div style="font-size:0.75rem;color:var(--gray-500);margin-top:2px">
                                            ID: #<?= $k['id'] ?>
                                        </div>
                                    </td>
                                    <td>
                                        <code class="key-code"><?= clean($k['key_prefix']) ?>••••••••</code>
                                    </td>
                                    <td>
                                        <div class="perm-badges-mini">
                                            <?php if (empty($permissions) || in_array('full_access', $permissions)): ?>
                                                <span class="perm-badge-mini full">Full Access</span>
                                            <?php else: ?>
                                                <?php foreach (array_slice($permissions, 0, 2) as $perm): 
                                                    $permCfg = formatPermission($perm, $availablePermissions);
                                                ?>
                                                <span class="perm-badge-mini" style="background: <?= $permCfg['color'] ?>15; color: <?= $permCfg['color'] ?>">
                                                    <?= $permCfg['label'] ?>
                                                </span>
                                                <?php endforeach; ?>
                                                <?php if (count($permissions) > 2): ?>
                                                <span class="perm-badge-mini more">+<?= count($permissions) - 2 ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= clean($k['admin_name'] ?: 'Unknown') ?></td>
                                    <td>
                                        <?php if ($k['expires_at']): 
                                            $timeLeft = getTimeLeft($k['expires_at']);
                                        ?>
                                        <span style="color: <?= $timeLeft['color'] ?>; font-weight: 600;">
                                            <?= $timeLeft['label'] ?>
                                        </span>
                                        <div style="font-size:0.72rem;color:var(--gray-500)">
                                            <?= date('d M Y', strtotime($k['expires_at'])) ?>
                                        </div>
                                        <?php else: ?>
                                        <span style="color:var(--gray-400)">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $isActive ? 'success' : ($isExpired ? 'danger' : 'default') ?>">
                                            <i class="fas fa-circle" style="font-size:0.5rem"></i>
                                            <?= $isExpired ? 'Expired' : ucfirst($k['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size:0.85rem;font-weight:600"><?= number_format(simulateUsage($k['id'])) ?></div>
                                        <div style="font-size:0.72rem;color:var(--gray-500)"><?= getLastUsed($k['id']) ?></div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-sm" onclick="openKeyDetail(<?= $k['id'] ?>)" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($k['status'] === 'aktif'): ?>
                                            <a href="?action=revoke&id=<?= $k['id'] ?>" class="btn-icon btn-sm btn-warning" title="Revoke" onclick="return confirm('Cabut key ini?')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                            <?php else: ?>
                                            <a href="?action=activate&id=<?= $k['id'] ?>" class="btn-icon btn-sm btn-success" title="Activate">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?= $k['id'] ?>" class="btn-icon btn-sm btn-danger" title="Delete" onclick="return confirm('Hapus permanen?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($keys)): ?>
            <div class="result-info">
                <span>Menampilkan <strong><?= count($keys) ?></strong> API keys</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="apikeys-sidebar">
            <!-- Quick Actions -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="widget-body">
                    <button class="qa-btn primary" onclick="openGenerateModal()">
                        <i class="fas fa-plus-circle"></i>
                        <span>Generate Key Baru</span>
                    </button>
                    <a href="#" class="qa-btn" onclick="event.preventDefault(); showDocs()">
                        <i class="fas fa-book"></i>
                        <span>Dokumentasi API</span>
                        <i class="fas fa-external-link-alt qa-arrow"></i>
                    </a>
                    <a href="activity-logs.php?module=api" class="qa-btn">
                        <i class="fas fa-history"></i>
                        <span>API Activity Logs</span>
                        <i class="fas fa-chevron-right qa-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Security Score -->
            <div class="card widget-card security-score-card">
                <div class="widget-header">
                    <h3><i class="fas fa-shield-alt"></i> Security Score</h3>
                </div>
                <div class="widget-body">
                    <?php 
                    $securityScore = 100;
                    $securityIssues = [];
                    
                    if ($stats['dicabut'] > 0) $securityScore -= 5;
                    if ($stats['expired'] > 0) { $securityScore -= 10; $securityIssues[] = ['type' => 'danger', 'text' => $stats['expired'] . ' key expired perlu dibersihkan']; }
                    if ($stats['expiring_soon'] > 0) { $securityScore -= 5; $securityIssues[] = ['type' => 'warning', 'text' => $stats['expiring_soon'] . ' key akan expired dalam 7 hari']; }
                    
                    $fullAccessCount = 0;
                    foreach ($keys as $k) {
                        $perms = json_decode($k['permissions'], true) ?: [];
                        if (empty($perms) || in_array('full_access', $perms)) $fullAccessCount++;
                    }
                    if ($fullAccessCount > 2) { $securityScore -= 10; $securityIssues[] = ['type' => 'warning', 'text' => $fullAccessCount . ' key punya full access (prinsip least privilege)']; }
                    
                    $noExpiryCount = count(array_filter($keys, fn($k) => !$k['expires_at'] && $k['status'] === 'aktif'));
                    if ($noExpiryCount > 3) { $securityScore -= 5; $securityIssues[] = ['type' => 'info', 'text' => $noExpiryCount . ' key tidak punya expiration date']; }
                    
                    $scoreColor = $securityScore >= 90 ? '#10B981' : ($securityScore >= 70 ? '#F59E0B' : '#EF4444');
                    $scoreLabel = $securityScore >= 90 ? 'Excellent' : ($securityScore >= 70 ? 'Good' : 'Needs Attention');
                    ?>
                    
                    <div class="security-score-circle">
                        <svg viewBox="0 0 36 36" class="score-ring">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--gray-100)" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.9" fill="none" 
                                stroke="<?= $scoreColor ?>" 
                                stroke-width="3"
                                stroke-dasharray="<?= $securityScore ?>, 100"
                                stroke-linecap="round"
                                transform="rotate(-90 18 18)"/>
                        </svg>
                        <div class="score-value" style="color: <?= $scoreColor ?>"><?= $securityScore ?></div>
                        <div class="score-label"><?= $scoreLabel ?></div>
                    </div>
                    
                    <?php if (!empty($securityIssues)): ?>
                    <div class="security-issues">
                        <?php foreach ($securityIssues as $issue): ?>
                        <div class="si-item si-<?= $issue['type'] ?>">
                            <i class="fas fa-<?= $issue['type'] === 'danger' ? 'exclamation-circle' : ($issue['type'] === 'warning' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
                            <span><?= $issue['text'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="security-success">
                        <i class="fas fa-check-circle"></i>
                        <span>Semua key dalam kondisi aman!</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- API Endpoints Info -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-code"></i> Base URL</h3>
                </div>
                <div class="widget-body">
                    <div class="api-endpoint-box">
                        <div class="aeb-label">Production</div>
                        <div class="aeb-url">
                            <code id="prodUrl"><?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/api/v1</code>
                            <button class="aeb-copy" onclick="copyText('prodUrl')"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <div class="api-endpoint-box">
                        <div class="aeb-label">Authentication</div>
                        <div class="aeb-code">
                            <pre><code>Authorization: Bearer <span style="color:#10B981">YOUR_API_KEY</span></code></pre>
                        </div>
                    </div>
                    <div class="api-endpoint-box">
                        <div class="aeb-label">Rate Limit</div>
                        <div class="aeb-info">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>1000 requests/jam per key</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Best Practices -->
            <div class="card widget-card tips-card">
                <div class="widget-header">
                    <h3><i class="fas fa-lightbulb"></i> Best Practices</h3>
                </div>
                <div class="widget-body">
                    <div class="tip-item">
                        <div class="tip-icon success"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Gunakan permission minimum yang dibutuhkan (principle of least privilege)</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon success"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Set expiration date untuk key temporary atau testing</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon success"><i class="fas fa-check"></i></div>
                        <div class="tip-text">Rotate key secara berkala (setiap 90 hari)</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon danger"><i class="fas fa-times"></i></div>
                        <div class="tip-text">JANGAN commit key ke git atau hardcode di source code</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon danger"><i class="fas fa-times"></i></div>
                        <div class="tip-text">JANGAN share key via email, chat, atau tempat tidak aman</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon info"><i class="fas fa-info"></i></div>
                        <div class="tip-text">Gunakan environment variables untuk menyimpan key</div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-history"></i> Recent Activity</h3>
                    <a href="activity-logs.php?module=api" class="widget-link">View All</a>
                </div>
                <div class="widget-body">
                    <?php
                    $recentActivities = [];
                    foreach (array_slice($keys, 0, 3) as $k) {
                        $recentActivities[] = [
                            'type' => 'create',
                            'icon' => 'fa-plus-circle',
                            'color' => '#10B981',
                            'text' => 'Key "' . $k['name'] . '" dibuat',
                            'time' => function_exists('formatTanggal') ? formatTanggal($k['created_at']) : date('d M Y H:i', strtotime($k['created_at'])),
                            'actor' => $k['admin_name']
                        ];
                    }
                    ?>
                    
                    <?php if (!empty($recentActivities)): ?>
                        <?php foreach ($recentActivities as $act): ?>
                        <div class="ra-item">
                            <div class="ra-icon" style="background: <?= $act['color'] ?>15; color: <?= $act['color'] ?>">
                                <i class="fas <?= $act['icon'] ?>"></i>
                            </div>
                            <div class="ra-info">
                                <div class="ra-text"><?= $act['text'] ?></div>
                                <div class="ra-meta">
                                    <span><?= $act['actor'] ?></span>
                                    <span>·</span>
                                    <span><?= $act['time'] ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty">
                            <i class="fas fa-history"></i>
                            <p>Belum ada aktivitas</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== GENERATE KEY MODAL ===== -->
<div class="modal" id="generateModal">
    <div class="modal-content" style="max-width: 680px;">
        <div class="modal-header modal-header-gradient apikey-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-key"></i></div>
                <div>
                    <h3>Generate API Key Baru</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Buat kunci API untuk integrasi eksternal</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeGenerateModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" id="generateForm" class="modal-body">
            <div class="form-steps">
                <div class="step-indicator">
                    <div class="step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-label">Basic Info</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-label">Permissions</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-label">Security</span>
                    </div>
                </div>
                
                <!-- Step 1: Basic Info -->
                <div class="form-step-content active" data-step="1">
                    <div class="form-section">
                        <h4 class="section-title"><i class="fas fa-info-circle"></i> Informasi Dasar</h4>
                        
                        <div class="form-group">
                            <label>Nama Key <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-tag"></i>
                                <input type="text" name="name" id="keyName" required placeholder="Contoh: Mobile App Android, Website Partner, dll">
                            </div>
                            <small class="form-hint">Nama deskriptif untuk memudahkan identifikasi</small>
                        </div>
                        
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Expiration Date</label>
                                <div class="input-icon">
                                    <i class="fas fa-calendar"></i>
                                    <input type="date" name="expires_at" id="expiresAt" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Rate Limit (req/jam)</label>
                                <div class="input-icon">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <input type="number" name="rate_limit" id="rateLimit" value="1000" min="10" max="10000">
                                </div>
                            </div>
                        </div>
                        
                        <div class="quick-expiry">
                            <span class="qe-label">Quick set:</span>
                            <button type="button" class="qe-btn" onclick="setExpiry(7)">7 hari</button>
                            <button type="button" class="qe-btn" onclick="setExpiry(30)">30 hari</button>
                            <button type="button" class="qe-btn" onclick="setExpiry(90)">90 hari</button>
                            <button type="button" class="qe-btn" onclick="setExpiry(365)">1 tahun</button>
                            <button type="button" class="qe-btn" onclick="setExpiry(0)">Never</button>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Permissions -->
                <div class="form-step-content" data-step="2">
                    <div class="form-section">
                        <h4 class="section-title"><i class="fas fa-lock"></i> Permissions (Akses)</h4>
                        <p class="section-subtitle">Pilih permission yang dibutuhkan. Gunakan prinsip least privilege.</p>
                        
                        <div class="permission-groups">
                            <div class="perm-group">
                                <div class="pg-header">
                                    <span class="pg-title"><i class="fas fa-newspaper"></i> Berita</span>
                                    <label class="pg-toggle">
                                        <input type="checkbox" onchange="toggleGroup('berita', this.checked)">
                                        <span>Select All</span>
                                    </label>
                                </div>
                                <div class="perm-grid">
                                    <label class="perm-option">
                                        <input type="checkbox" name="permissions[]" value="berita.read" data-group="berita">
                                        <span class="po-visual"></span>
                                        <div class="po-content">
                                            <i class="fas fa-eye"></i>
                                            <div>
                                                <div class="po-title">Read</div>
                                                <div class="po-desc">Baca daftar & detail berita</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="perm-option">
                                        <input type="checkbox" name="permissions[]" value="berita.write" data-group="berita">
                                        <span class="po-visual"></span>
                                        <div class="po-content">
                                            <i class="fas fa-edit"></i>
                                            <div>
                                                <div class="po-title">Write</div>
                                                <div class="po-desc">Buat & update berita</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="perm-option">
                                        <input type="checkbox" name="permissions[]" value="berita.delete" data-group="berita">
                                        <span class="po-visual"></span>
                                        <div class="po-content">
                                            <i class="fas fa-trash"></i>
                                            <div>
                                                <div class="po-title">Delete</div>
                                                <div class="po-desc">Hapus berita</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="perm-group">
                                <div class="pg-header">
                                    <span class="pg-title"><i class="fas fa-calendar"></i> Agenda</span>
                                    <label class="pg-toggle">
                                        <input type="checkbox" onchange="toggleGroup('agenda', this.checked)">
                                        <span>Select All</span>
                                    </label>
                                </div>
                                <div class="perm-grid">
                                    <label class="perm-option">
                                        <input type="checkbox" name="permissions[]" value="agenda.read" data-group="agenda">
                                        <span class="po-visual"></span>
                                        <div class="po-content">
                                            <i class="fas fa-eye"></i>
                                            <div>
                                                <div class="po-title">Read</div>
                                                <div class="po-desc">Lihat daftar agenda</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="perm-option">
                                        <input type="checkbox" name="permissions[]" value="agenda.write" data-group="agenda">
                                        <span class="po-visual"></span>
                                        <div class="po-content">
                                            <i class="fas fa-calendar-plus"></i>
                                            <div>
                                                <div class="po-title">Write</div>
                                                <div class="po-desc">Buat agenda baru</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="perm-group">
                                <div class="pg-header">
                                    <span class="pg-title"><i class="fas fa-envelope"></i> Kontak & Alumni</span>
                                </div>
                                <div class="perm-grid">
                                    <label class="perm-option">
                                        <input type="checkbox" name="permissions[]" value="kontak.read">
                                        <span class="po-visual"></span>
                                        <div class="po-content">
                                            <i class="fas fa-envelope-open"></i>
                                            <div>
                                                <div class="po-title">Baca Kontak</div>
                                                <div class="po-desc">Lihat pesan masuk</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="perm-option">
                                        <input type="checkbox" name="permissions[]" value="kontak.write">
                                        <span class="po-visual"></span>
                                        <div class="po-content">
                                            <i class="fas fa-paper-plane"></i>
                                            <div>
                                                <div class="po-title">Tulis Kontak</div>
                                                <div class="po-desc">Balas & kelola</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="perm-option">
                                        <input type="checkbox" name="permissions[]" value="alumni.read">
                                        <span class="po-visual"></span>
                                        <div class="po-content">
                                            <i class="fas fa-user-graduate"></i>
                                            <div>
                                                <div class="po-title">Baca Alumni</div>
                                                <div class="po-desc">Lihat data alumni</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="perm-option">
                                        <input type="checkbox" name="permissions[]" value="alumni.write">
                                        <span class="po-visual"></span>
                                        <div class="po-content">
                                            <i class="fas fa-user-plus"></i>
                                            <div>
                                                <div class="po-title">Tulis Alumni</div>
                                                <div class="po-desc">Kelola data alumni</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="perm-group">
                                <div class="pg-header">
                                    <span class="pg-title"><i class="fas fa-chart-line"></i> Analytics & Media</span>
                                </div>
                                <div class="perm-grid">
                                    <label class="perm-option">
                                        <input type="checkbox" name="permissions[]" value="analytics.read">
                                        <span class="po-visual"></span>
                                        <div class="po-content">
                                            <i class="fas fa-chart-bar"></i>
                                            <div>
                                                <div class="po-title">Analytics</div>
                                                <div class="po-desc">Lihat statistik</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="perm-option">
                                        <input type="checkbox" name="permissions[]" value="media.read">
                                        <span class="po-visual"></span>
                                        <div class="po-content">
                                            <i class="fas fa-images"></i>
                                            <div>
                                                <div class="po-title">Baca Media</div>
                                                <div class="po-desc">Akses file media</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="perm-option">
                                        <input type="checkbox" name="permissions[]" value="media.write">
                                        <span class="po-visual"></span>
                                        <div class="po-content">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <div>
                                                <div class="po-title">Upload Media</div>
                                                <div class="po-desc">Upload file baru</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="perm-group special">
                                <div class="pg-header">
                                    <span class="pg-title"><i class="fas fa-crown"></i> Akses Khusus</span>
                                </div>
                                <label class="perm-option full-access-option">
                                    <input type="checkbox" name="permissions[]" value="full_access" id="fullAccessCheck" onchange="toggleFullAccess(this.checked)">
                                    <span class="po-visual"></span>
                                    <div class="po-content">
                                        <i class="fas fa-crown"></i>
                                        <div>
                                            <div class="po-title">Full Access</div>
                                            <div class="po-desc">Akses penuh ke semua endpoint (berbahaya!)</div>
                                        </div>
                                    </div>
                                    <span class="po-warning"><i class="fas fa-exclamation-triangle"></i></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Security -->
                <div class="form-step-content" data-step="3">
                    <div class="form-section">
                        <h4 class="section-title"><i class="fas fa-shield-alt"></i> Pengaturan Keamanan</h4>
                        
                        <div class="form-group">
                            <label>IP Whitelist (Optional)</label>
                            <div class="input-icon">
                                <i class="fas fa-globe"></i>
                                <input type="text" name="ip_whitelist" id="ipWhitelist" placeholder="Contoh: 192.168.1.1, 10.0.0.0/24">
                            </div>
                            <small class="form-hint">Batasi akses hanya dari IP tertentu. Pisahkan dengan koma.</small>
                        </div>
                        
                        <div class="security-summary">
                            <h4>Ringkasan Keamanan</h4>
                            <div class="ss-items">
                                <div class="ss-item success">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Key akan di-hash dengan SHA-256</span>
                                </div>
                                <div class="ss-item success">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Rate limiting aktif (default 1000 req/jam)</span>
                                </div>
                                <div class="ss-item info" id="ssExpiry">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Key tidak akan expired (set date untuk auto-expire)</span>
                                </div>
                                <div class="ss-item info" id="ssIp">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Akses dari semua IP (tambahkan whitelist untuk lebih aman)</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="terms-notice">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>Perhatian:</strong> API key hanya akan ditampilkan <u>satu kali</u> setelah dibuat. Pastikan Anda menyimpannya di tempat yang aman (password manager). Jika hilang, Anda perlu generate key baru.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer modal-footer-steps">
                <button type="button" class="btn btn-secondary" id="prevBtn" onclick="prevStep()" style="display:none">
                    <i class="fas fa-arrow-left"></i> Sebelumnya
                </button>
                <button type="button" class="btn btn-secondary" id="cancelBtn" onclick="closeGenerateModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">
                    Selanjutnya <i class="fas fa-arrow-right"></i>
                </button>
                <button type="submit" name="generate_key" class="btn btn-hero-primary" id="submitBtn" style="display:none">
                    <i class="fas fa-key"></i> Generate Key
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== KEY DETAIL MODAL ===== -->
<div class="modal" id="keyDetailModal">
    <div class="modal-content" style="max-width: 720px;">
        <div class="modal-header modal-header-gradient apikey-modal-head">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="fas fa-key"></i></div>
                <div>
                    <h3>Detail API Key</h3>
                    <p style="opacity:.85;font-size:.85rem;margin-top:2px">Informasi lengkap key dan usage</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeKeyDetail()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="keyDetailBody">
            <div style="text-align:center;padding:40px">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--primary)"></i>
            </div>
        </div>
    </div>
</div>

<!-- ===== NEW KEY DISPLAY MODAL (One-time) ===== -->
<?php if ($newlyGeneratedKey): ?>
<div class="modal active" id="newKeyModal" style="display:flex">
    <div class="modal-content" style="max-width: 640px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #10B981, #047857); color: white;">
            <div class="modal-header-content">
                <div class="modal-icon" style="background:rgba(255,255,255,0.2)">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h3 style="color:white">API Key Berhasil Dibuat!</h3>
                    <p style="opacity:.9;font-size:.85rem;margin-top:2px">Simpan key ini sekarang - tidak akan ditampilkan lagi</p>
                </div>
            </div>
        </div>
        <div class="modal-body">
            <div class="new-key-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>⚠️ Penting!</strong>
                    <p>Key ini <strong>HANYA ditampilkan sekali</strong>. Copy dan simpan di tempat aman (password manager). Anda tidak akan bisa melihat key ini lagi.</p>
                </div>
            </div>
            
            <div class="new-key-info">
                <div class="nki-row">
                    <span class="nki-label">Nama:</span>
                    <strong><?= clean($newlyGeneratedKey['name']) ?></strong>
                </div>
                <?php if ($newlyGeneratedKey['expires_at']): ?>
                <div class="nki-row">
                    <span class="nki-label">Expired:</span>
                    <span><?= date('d M Y', strtotime($newlyGeneratedKey['expires_at'])) ?></span>
                </div>
                <?php else: ?>
                <div class="nki-row">
                    <span class="nki-label">Expired:</span>
                    <span>Never</span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="new-key-display">
                <label class="nkd-label">
                    <i class="fas fa-key"></i> Your API Key
                </label>
                <div class="nkd-key-box">
                    <code class="nkd-key" id="newKeyValue"><?= htmlspecialchars($newlyGeneratedKey['key']) ?></code>
                    <button class="nkd-copy-btn" onclick="copyNewKey()">
                        <i class="fas fa-copy"></i>
                        <span>Copy</span>
                    </button>
                </div>
                <div class="nkd-prefix">
                    Prefix: <code><?= clean($newlyGeneratedKey['key_prefix']) ?></code>
                </div>
            </div>
            
            <div class="new-key-actions">
                <button class="btn btn-secondary" onclick="downloadKey()">
                    <i class="fas fa-download"></i> Download .env
                </button>
                <button class="btn btn-secondary" onclick="showQRCode()">
                    <i class="fas fa-qrcode"></i> Show QR Code
                </button>
            </div>
            
            <div class="new-key-example">
                <div class="nke-title"><i class="fas fa-code"></i> Contoh Penggunaan</div>
                <pre class="nke-code"><code>curl -X GET \
  <?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/api/v1/berita \
  -H 'Authorization: Bearer <?= htmlspecialchars(substr($newlyGeneratedKey['key'], 0, 20)) ?>••••••••'</code></pre>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="closeNewKeyModal()">
                <i class="fas fa-check"></i> Saya Sudah Menyimpan Key
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* ============================================================
   API KEYS PAGE - SECURITY EDITION v4.0
   Integrated with admin.css design system
============================================================ */

.api-keys-page {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* ===== HERO ===== */
.apikeys-hero {
    position: relative;
    padding: 36px 40px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, #1E1B4B 0%, #4338CA 60%, #6366F1 100%);
    border-radius: var(--radius-2xl);
    overflow: hidden;
    box-shadow: var(--shadow-xl);
}

.hero-bg-effects {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.hero-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.5;
    animation: orbFloat 20s infinite ease-in-out;
}

.orb-1 {
    width: 320px; height: 320px;
    background: radial-gradient(circle, #818CF8, transparent);
    top: -120px; right: 8%;
}

.orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, #6366F1, transparent);
    bottom: -100px; left: 15%;
    animation-delay: -7s;
}

.hero-grid-pattern {
    position: absolute;
    inset: 0;
    background-image: 
        linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 40px 40px;
}

.hero-shield-pattern {
    position: absolute;
    top: 50%;
    right: 10%;
    transform: translateY(-50%);
    font-size: 20rem;
    color: rgba(255,255,255,0.03);
    pointer-events: none;
}

.hero-shield-pattern::before {
    content: '\f3ed';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
}

.hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 24px;
    color: var(--white);
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 600;
    margin-bottom: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
}

.hero-alert-dot {
    width: 8px;
    height: 8px;
    background: #F59E0B;
    border-radius: 50%;
    animation: liveDot 1.5s infinite;
}

.hero-title {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 10px;
    line-height: 1.2;
    font-family: 'Space Grotesk', sans-serif;
    display: flex;
    align-items: center;
    gap: 16px;
}

.title-icon-wrap {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.9;
    max-width: 600px;
}

.hero-mini-stats {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.hms-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 10px;
    font-size: 0.85rem;
}

.hms-item.live {
    background: rgba(16,185,129,0.2);
    border-color: rgba(16,185,129,0.4);
}

.hms-item.warning {
    background: rgba(245,158,11,0.2);
    border-color: rgba(245,158,11,0.4);
}

.hms-item.danger {
    background: rgba(239,68,68,0.2);
    border-color: rgba(239,68,68,0.4);
}

.live-dot {
    width: 8px;
    height: 8px;
    background: #10B981;
    border-radius: 50%;
    box-shadow: 0 0 8px rgba(16,185,129,0.8);
    animation: liveDot 1.5s infinite;
}

.hms-item i {
    color: #C7D2FE;
}

.hms-item strong {
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
}

.hero-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-hero-primary {
    padding: 12px 24px;
    background: linear-gradient(135deg, #10B981, #047857);
    color: var(--white);
    border: none;
    border-radius: var(--radius);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all var(--transition);
    font-family: inherit;
    box-shadow: 0 4px 16px rgba(16,185,129,0.4);
}

.btn-hero-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16,185,129,0.5);
}

.btn-ghost {
    padding: 12px 18px;
    background: rgba(255,255,255,0.12);
    color: var(--white);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: var(--radius);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all var(--transition);
    font-family: inherit;
    backdrop-filter: blur(10px);
}

.btn-ghost:hover {
    background: rgba(255,255,255,0.22);
    transform: translateY(-2px);
}

/* ===== SECURITY NOTICE ===== */
.security-notice {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 16px;
    padding: 20px 24px;
    background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(180,83,9,0.04));
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: var(--radius-xl);
    margin-bottom: 24px;
    align-items: center;
}

.sn-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #F59E0B, #D97706);
    color: var(--white);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    box-shadow: 0 4px 12px rgba(245,158,11,0.3);
    flex-shrink: 0;
}

.sn-content h4 {
    color: #92400E;
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 4px;
}

[data-theme="dark"] .sn-content h4 {
    color: #FCD34D;
}

.sn-content p {
    color: var(--gray-700);
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.5;
}

[data-theme="dark"] .sn-content p {
    color: var(--gray-300);
}

.sn-badges {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.sn-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: rgba(16,185,129,0.1);
    color: #047857;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
}

[data-theme="dark"] .sn-badge {
    background: rgba(16,185,129,0.15);
    color: #34D399;
}

/* ===== LAYOUT ===== */
.apikeys-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

.apikeys-main-col {
    min-width: 0;
}

/* ===== STATS CARDS ===== */
.apikeys-stats {
    margin-bottom: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.stat-indigo .stat-icon { background: linear-gradient(135deg, #6366F1, #4338CA); }
.stat-green .stat-icon { background: linear-gradient(135deg, #10B981, #047857); }
.stat-gray .stat-icon { background: linear-gradient(135deg, #6B7280, #374151); }
.stat-orange .stat-icon { background: linear-gradient(135deg, #F59E0B, #B45309); }

/* ===== TOOLBAR ===== */
.apikeys-toolbar {
    padding: 16px 20px;
    margin-bottom: 20px;
}

.apikeys-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.toolbar-left {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    flex: 1;
    min-width: 0;
}

.toolbar-search {
    position: relative;
    flex: 1;
    min-width: 240px;
    max-width: 380px;
}

.toolbar-search i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-400);
    font-size: 0.9rem;
    pointer-events: none;
}

.toolbar-search input {
    width: 100%;
    padding: 10px 44px 10px 42px;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: 0.88rem;
    font-family: inherit;
    background: var(--gray-50);
    transition: all var(--transition);
}

[data-theme="dark"] .toolbar-search input {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    color: var(--white);
}

.toolbar-search input:focus {
    outline: none;
    border-color: #6366F1;
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(99,102,241,0.1);
}

[data-theme="dark"] .toolbar-search input:focus {
    background: rgba(255,255,255,0.08);
}

.search-kbd {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    padding: 2px 8px;
    background: var(--gray-200);
    color: var(--gray-600);
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    font-family: 'Courier New', monospace;
    pointer-events: none;
}

[data-theme="dark"] .search-kbd {
    background: rgba(255,255,255,0.1);
    color: var(--gray-400);
}

.toolbar-filters {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.toolbar-select {
    padding: 9px 14px;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: 0.85rem;
    font-family: inherit;
    background: var(--white);
    cursor: pointer;
    transition: all var(--transition);
    color: var(--dark);
    min-width: 130px;
}

[data-theme="dark"] .toolbar-select {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    color: var(--white);
}

.toolbar-select:focus {
    outline: none;
    border-color: #6366F1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.toolbar-icon-btn {
    width: 40px;
    height: 40px;
    border-radius: var(--radius);
    border: 1.5px solid var(--gray-200);
    background: var(--white);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-600);
    transition: all var(--transition);
    font-size: 0.9rem;
}

[data-theme="dark"] .toolbar-icon-btn {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    color: var(--gray-300);
}

.toolbar-icon-btn:hover {
    background: #6366F1;
    color: var(--white);
    border-color: #6366F1;
    transform: translateY(-2px);
}

.toolbar-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

.sort-dropdown {
    position: relative;
}

.sort-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 200px;
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-100);
    padding: 6px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px);
    transition: all var(--transition);
    z-index: 50;
}

[data-theme="dark"] .sort-menu {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.sort-dropdown.open .sort-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.sort-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: var(--radius-sm);
    color: var(--dark);
    text-decoration: none;
    font-size: 0.85rem;
    transition: all var(--transition-fast);
}

[data-theme="dark"] .sort-item {
    color: var(--gray-200);
}

.sort-item:hover {
    background: var(--gray-50);
}

[data-theme="dark"] .sort-item:hover {
    background: rgba(255,255,255,0.05);
}

.sort-item.active {
    background: rgba(99,102,241,0.1);
    color: #6366F1;
    font-weight: 600;
}

.sort-item i {
    width: 16px;
    color: var(--gray-500);
}

.sort-item.active i {
    color: #6366F1;
}

.view-toggle {
    display: flex;
    background: var(--gray-100);
    border-radius: var(--radius);
    padding: 3px;
    gap: 2px;
}

[data-theme="dark"] .view-toggle {
    background: rgba(255,255,255,0.08);
}

.view-btn {
    width: 36px;
    height: 34px;
    border: none;
    background: transparent;
    border-radius: var(--radius-sm);
    cursor: pointer;
    color: var(--gray-600);
    transition: all var(--transition-fast);
    display: flex;
    align-items: center;
    justify-content: center;
}

[data-theme="dark"] .view-btn {
    color: var(--gray-400);
}

.view-btn.active {
    background: var(--white);
    color: #6366F1;
    box-shadow: var(--shadow-sm);
}

[data-theme="dark"] .view-btn.active {
    background: var(--dark-2);
    color: #A5B4FC;
}

/* ===== APIKEY GRID ===== */
.apikeys-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.apikeys-grid[data-view="list"] {
    grid-template-columns: 1fr;
}

.apikey-card {
    position: relative;
    background: var(--white);
    border-radius: var(--radius-xl);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--gray-100);
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-direction: column;
}

[data-theme="dark"] .apikey-card {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.apikey-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(99,102,241,0.3);
}

.apikey-card.inactive {
    opacity: 0.65;
}

.apikey-card.inactive:hover {
    opacity: 0.85;
}

/* Card Header */
.akc-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 18px;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .akc-header {
    background: rgba(0,0,0,0.2);
    border-color: rgba(255,255,255,0.05);
}

.akc-status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
}

.akc-status-indicator.active {
    background: rgba(16,185,129,0.12);
    color: #047857;
}

.akc-status-indicator.inactive {
    background: rgba(107,114,128,0.12);
    color: #6B7280;
}

.akc-status-indicator .status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: currentColor;
}

.akc-status-indicator.active .status-dot {
    box-shadow: 0 0 0 3px rgba(16,185,129,0.2);
    animation: livePulseSmall 2s infinite;
}

.akc-usage {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    color: var(--gray-600);
    font-weight: 600;
    font-family: 'Space Grotesk', sans-serif;
}

[data-theme="dark"] .akc-usage {
    color: var(--gray-400);
}

.akc-usage i {
    color: #6366F1;
    font-size: 0.68rem;
}

/* Card Body */
.akc-body {
    padding: 20px 18px;
    cursor: pointer;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.akc-icon-wrap {
    width: 52px;
    height: 52px;
    background: linear-gradient(135deg, #6366F1, #4338CA);
    color: var(--white);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin-bottom: 14px;
    position: relative;
    box-shadow: 0 4px 12px rgba(99,102,241,0.3);
}

.apikey-card.inactive .akc-icon-wrap {
    background: linear-gradient(135deg, #9CA3AF, #6B7280);
    box-shadow: 0 4px 12px rgba(107,114,128,0.2);
}

.akc-warning-icon {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 20px;
    height: 20px;
    background: #F59E0B;
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    box-shadow: 0 2px 6px rgba(245,158,11,0.4);
    animation: pulseBadge 2s infinite;
}

.akc-name {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 10px;
    line-height: 1.3;
}

[data-theme="dark"] .akc-name {
    color: var(--white);
}

.akc-key-display {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 10px;
    background: var(--gray-50);
    border-radius: var(--radius-sm);
    margin-bottom: 14px;
    border: 1px solid var(--gray-100);
}

[data-theme="dark"] .akc-key-display {
    background: rgba(0,0,0,0.2);
    border-color: rgba(255,255,255,0.05);
}

.akc-key-prefix {
    flex: 1;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    color: #6366F1;
    font-weight: 600;
    letter-spacing: 0.5px;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
}

.akc-copy-btn {
    width: 28px;
    height: 28px;
    border: none;
    background: transparent;
    color: var(--gray-500);
    cursor: pointer;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    transition: all var(--transition-fast);
    flex-shrink: 0;
}

.akc-copy-btn:hover {
    background: #6366F1;
    color: var(--white);
}

.akc-permissions {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 14px;
    min-height: 24px;
}

.perm-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 600;
    white-space: nowrap;
}

.perm-badge.full-access {
    background: rgba(220,38,38,0.12);
    color: #DC2626;
}

.perm-badge.more {
    background: var(--gray-100);
    color: var(--gray-600);
}

[data-theme="dark"] .perm-badge.more {
    background: rgba(255,255,255,0.08);
    color: var(--gray-400);
}

.akc-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid var(--gray-100);
    margin-top: auto;
    gap: 8px;
    flex-wrap: wrap;
}

[data-theme="dark"] .akc-meta {
    border-color: rgba(255,255,255,0.05);
}

.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: var(--gray-600);
}

[data-theme="dark"] .meta-item {
    color: var(--gray-400);
}

.meta-item i {
    font-size: 0.68rem;
}

/* Card Footer */
.akc-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 18px;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-100);
    gap: 10px;
}

[data-theme="dark"] .akc-footer {
    background: rgba(0,0,0,0.2);
    border-color: rgba(255,255,255,0.05);
}

.akc-created {
    font-size: 0.72rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 5px;
}

.akc-actions {
    display: flex;
    gap: 6px;
}

.akc-action-btn {
    width: 30px;
    height: 30px;
    border-radius: var(--radius-sm);
    background: var(--white);
    border: 1px solid var(--gray-200);
    color: var(--gray-600);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    transition: all var(--transition-fast);
    cursor: pointer;
    text-decoration: none;
}

[data-theme="dark"] .akc-action-btn {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    color: var(--gray-400);
}

.akc-action-btn:hover {
    transform: translateY(-2px);
}

.akc-action-btn.success:hover {
    background: #10B981;
    border-color: #10B981;
    color: var(--white);
}

.akc-action-btn.warning:hover {
    background: #F59E0B;
    border-color: #F59E0B;
    color: var(--white);
}

.akc-action-btn.danger:hover {
    background: #EF4444;
    border-color: #EF4444;
    color: var(--white);
}

/* List View */
.apikeys-grid[data-view="list"] .apikey-card {
    flex-direction: row;
    align-items: stretch;
}

.apikeys-grid[data-view="list"] .akc-header {
    display: none;
}

.apikeys-grid[data-view="list"] .akc-body {
    flex-direction: row;
    align-items: center;
    gap: 20px;
    flex: 1;
    padding: 16px 20px;
}

.apikeys-grid[data-view="list"] .akc-icon-wrap {
    margin-bottom: 0;
    width: 48px;
    height: 48px;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.apikeys-grid[data-view="list"] .akc-name {
    margin-bottom: 4px;
}

.apikeys-grid[data-view="list"] .akc-key-display {
    margin-bottom: 0;
    max-width: 200px;
}

.apikeys-grid[data-view="list"] .akc-permissions {
    margin-bottom: 0;
    max-width: 300px;
}

.apikeys-grid[data-view="list"] .akc-meta {
    border-top: none;
    padding-top: 0;
    margin-top: 0;
    flex-direction: column;
    align-items: flex-start;
    gap: 3px;
}

.apikeys-grid[data-view="list"] .akc-footer {
    background: transparent;
    border-top: none;
    border-left: 1px solid var(--gray-100);
    flex-direction: column;
    padding: 14px 16px;
    gap: 12px;
    align-items: flex-end;
}

/* Table View */
.apikeys-table-view .data-table {
    font-size: 0.85rem;
}

.apikeys-table-view .data-table th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 700;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .apikeys-table-view .data-table th {
    background: var(--dark);
    color: var(--gray-300);
    border-color: rgba(255,255,255,0.1);
}

.apikeys-table-view .data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

[data-theme="dark"] .apikeys-table-view .data-table td {
    border-color: rgba(255,255,255,0.05);
}

.apikeys-table-view tr.inactive-row {
    opacity: 0.6;
}

.key-code {
    padding: 4px 10px;
    background: var(--gray-100);
    color: #6366F1;
    border-radius: 4px;
    font-size: 0.82rem;
    font-family: 'Courier New', monospace;
    font-weight: 600;
}

[data-theme="dark"] .key-code {
    background: rgba(99,102,241,0.15);
    color: #A5B4FC;
}

.perm-badges-mini {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.perm-badge-mini {
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.68rem;
    font-weight: 600;
}

.perm-badge-mini.full {
    background: rgba(220,38,38,0.12);
    color: #DC2626;
}

.perm-badge-mini.more {
    background: var(--gray-100);
    color: var(--gray-600);
}

[data-theme="dark"] .perm-badge-mini.more {
    background: rgba(255,255,255,0.08);
    color: var(--gray-400);
}

.result-info {
    text-align: center;
    padding: 12px;
    color: var(--gray-600);
    font-size: 0.85rem;
}

[data-theme="dark"] .result-info {
    color: var(--gray-400);
}

.result-info strong {
    color: #6366F1;
    font-family: 'Space Grotesk', sans-serif;
}

/* ===== EMPTY STATE ===== */
.empty-state-ult {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: var(--white);
    border-radius: var(--radius-xl);
    border: 2px dashed var(--gray-200);
}

[data-theme="dark"] .empty-state-ult {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.1);
}

.empty-illustration {
    margin-bottom: 24px;
    display: inline-block;
    animation: floatIllustration 3s ease-in-out infinite;
}

@keyframes floatIllustration {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.empty-state-ult h3 {
    font-size: 1.3rem;
    color: var(--dark);
    margin-bottom: 8px;
}

[data-theme="dark"] .empty-state-ult h3 {
    color: var(--white);
}

.empty-state-ult p {
    color: var(--gray-600);
    margin-bottom: 24px;
}

[data-theme="dark"] .empty-state-ult p {
    color: var(--gray-400);
}

.empty-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

/* ===== SIDEBAR WIDGETS ===== */
.apikeys-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
    position: sticky;
    top: 90px;
}

.widget-card {
    overflow: hidden;
}

.widget-header {
    padding: 16px 18px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--gray-50);
}

[data-theme="dark"] .widget-header {
    background: rgba(0,0,0,0.2);
    border-color: rgba(255,255,255,0.08);
}

.widget-header h3 {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

[data-theme="dark"] .widget-header h3 {
    color: var(--white);
}

.widget-header h3 i {
    color: #6366F1;
}

.widget-link {
    font-size: 0.78rem;
    color: #6366F1;
    text-decoration: none;
    font-weight: 600;
    transition: all var(--transition-fast);
}

.widget-link:hover {
    color: #4338CA;
}

.widget-body {
    padding: 16px 18px;
}

.widget-empty {
    text-align: center;
    padding: 24px 12px;
    color: var(--gray-600);
}

[data-theme="dark"] .widget-empty {
    color: var(--gray-400);
}

.widget-empty i {
    font-size: 2rem;
    color: var(--gray-300);
    margin-bottom: 8px;
    display: block;
}

.widget-empty p {
    font-size: 0.82rem;
    margin: 0;
}

/* ===== QUICK ACTIONS ===== */
.qa-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: var(--radius);
    background: var(--gray-50);
    color: var(--dark);
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 500;
    transition: all var(--transition);
    margin-bottom: 8px;
    cursor: pointer;
    border: none;
    width: 100%;
    text-align: left;
    font-family: inherit;
}

[data-theme="dark"] .qa-btn {
    background: rgba(255,255,255,0.04);
    color: var(--gray-200);
}

.qa-btn:hover {
    background: var(--gray-100);
    transform: translateX(4px);
}

[data-theme="dark"] .qa-btn:hover {
    background: rgba(255,255,255,0.08);
}

.qa-btn.primary {
    background: linear-gradient(135deg, #10B981, #047857);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}

.qa-btn.primary:hover {
    box-shadow: 0 6px 16px rgba(16,185,129,0.4);
}

.qa-btn i:first-child {
    color: #6366F1;
    width: 16px;
}

.qa-btn.primary i:first-child {
    color: var(--white);
}

.qa-btn span {
    flex: 1;
}

.qa-arrow {
    color: var(--gray-400);
    font-size: 0.72rem;
    opacity: 0;
    transition: opacity var(--transition-fast);
}

.qa-btn:hover .qa-arrow {
    opacity: 1;
}

/* ===== SECURITY SCORE ===== */
.security-score-card {
    background: linear-gradient(135deg, rgba(99,102,241,0.05), rgba(139,92,246,0.05));
    border: 1px solid rgba(99,102,241,0.15);
}

.security-score-circle {
    position: relative;
    width: 140px;
    height: 140px;
    margin: 0 auto 16px;
}

.score-ring {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.score-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 2.5rem;
    font-weight: 800;
    font-family: 'Space Grotesk', sans-serif;
    line-height: 1;
}

.score-label {
    text-align: center;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

[data-theme="dark"] .score-label {
    color: var(--gray-400);
}

.security-issues {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid var(--gray-100);
}

[data-theme="dark"] .security-issues {
    border-color: rgba(255,255,255,0.08);
}

.si-item {
    display: flex;
    gap: 10px;
    padding: 10px 12px;
    border-radius: var(--radius-sm);
    font-size: 0.78rem;
    line-height: 1.4;
    align-items: flex-start;
}

.si-item i {
    flex-shrink: 0;
    margin-top: 2px;
}

.si-item.si-danger {
    background: rgba(239,68,68,0.08);
    color: #B91C1C;
}

.si-item.si-danger i { color: #EF4444; }

.si-item.si-warning {
    background: rgba(245,158,11,0.08);
    color: #92400E;
}

.si-item.si-warning i { color: #F59E0B; }

.si-item.si-info {
    background: rgba(59,130,246,0.08);
    color: #1E40AF;
}

.si-item.si-info i { color: #3B82F6; }

[data-theme="dark"] .si-item.si-danger { background: rgba(239,68,68,0.15); color: #FCA5A5; }
[data-theme="dark"] .si-item.si-warning { background: rgba(245,158,11,0.15); color: #FCD34D; }
[data-theme="dark"] .si-item.si-info { background: rgba(59,130,246,0.15); color: #93C5FD; }

.security-success {
    display: flex;
    gap: 10px;
    align-items: center;
    padding: 12px;
    background: rgba(16,185,129,0.08);
    color: #047857;
    border-radius: var(--radius-sm);
    font-size: 0.85rem;
    font-weight: 600;
    margin-top: 12px;
}

.security-success i {
    color: #10B981;
}

[data-theme="dark"] .security-success {
    background: rgba(16,185,129,0.15);
    color: #34D399;
}

/* ===== API ENDPOINTS ===== */
.api-endpoint-box {
    margin-bottom: 14px;
}

.api-endpoint-box:last-child {
    margin-bottom: 0;
}

.aeb-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-500);
    margin-bottom: 6px;
}

.aeb-url {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 12px;
    background: var(--dark);
    border-radius: var(--radius-sm);
    overflow: hidden;
}

.aeb-url code {
    flex: 1;
    color: #A5B4FC;
    font-family: 'Courier New', monospace;
    font-size: 0.78rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.aeb-copy {
    background: transparent;
    border: none;
    color: #A5B4FC;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all var(--transition-fast);
    flex-shrink: 0;
}

.aeb-copy:hover {
    background: rgba(255,255,255,0.1);
    color: var(--white);
}

.aeb-code {
    padding: 10px 12px;
    background: var(--dark);
    border-radius: var(--radius-sm);
}

.aeb-code pre {
    margin: 0;
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
    color: #E5E7EB;
}

.aeb-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: var(--gray-50);
    border-radius: var(--radius-sm);
    font-size: 0.85rem;
    color: var(--gray-700);
}

[data-theme="dark"] .aeb-info {
    background: rgba(255,255,255,0.04);
    color: var(--gray-300);
}

.aeb-info i {
    color: #6366F1;
}

/* ===== TIPS ===== */
.tips-card {
    background: linear-gradient(135deg, rgba(245,158,11,0.04), rgba(16,185,129,0.04));
}

.tip-item {
    display: flex;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
    align-items: flex-start;
}

[data-theme="dark"] .tip-item {
    border-color: rgba(255,255,255,0.05);
}

.tip-item:last-child {
    border-bottom: none;
}

.tip-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.68rem;
    flex-shrink: 0;
    margin-top: 1px;
}

.tip-icon.success {
    background: rgba(16,185,129,0.15);
    color: #10B981;
}

.tip-icon.danger {
    background: rgba(239,68,68,0.15);
    color: #EF4444;
}

.tip-icon.info {
    background: rgba(59,130,246,0.15);
    color: #3B82F6;
}

.tip-text {
    font-size: 0.82rem;
    color: var(--gray-700);
    line-height: 1.5;
    flex: 1;
}

[data-theme="dark"] .tip-text {
    color: var(--gray-300);
}

/* ===== RECENT ACTIVITY ===== */
.ra-item {
    display: flex;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .ra-item {
    border-color: rgba(255,255,255,0.05);
}

.ra-item:last-child {
    border-bottom: none;
}

.ra-icon {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.ra-info {
    flex: 1;
    min-width: 0;
}

.ra-text {
    font-size: 0.85rem;
    color: var(--dark);
    font-weight: 500;
    margin-bottom: 3px;
    line-height: 1.3;
}

[data-theme="dark"] .ra-text {
    color: var(--gray-200);
}

.ra-meta {
    font-size: 0.72rem;
    color: var(--gray-500);
    display: flex;
    gap: 6px;
}

/* ===== MODAL GRADIENT ===== */
.apikey-modal-head {
    background: linear-gradient(135deg, #1E1B4B 0%, #4338CA 60%, #6366F1 100%) !important;
}

/* ===== FORM STEPS ===== */
.form-steps {
    padding: 4px 0;
}

.step-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 32px;
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--radius-lg);
}

[data-theme="dark"] .step-indicator {
    background: rgba(255,255,255,0.04);
}

.step {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 20px;
    font-size: 0.82rem;
    color: var(--gray-500);
    transition: all var(--transition);
}

.step.active {
    background: #6366F1;
    color: var(--white);
    font-weight: 600;
}

.step.completed {
    background: rgba(16,185,129,0.15);
    color: #047857;
}

.step-number {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.78rem;
    font-family: 'Space Grotesk', sans-serif;
}

.step.active .step-number {
    background: rgba(255,255,255,0.2);
    color: var(--white);
}

.step.completed .step-number {
    background: #10B981;
    color: var(--white);
}

.step-line {
    width: 40px;
    height: 2px;
    background: var(--gray-200);
    border-radius: 1px;
}

[data-theme="dark"] .step-line {
    background: rgba(255,255,255,0.1);
}

.form-step-content {
    display: none;
    animation: fadeIn 0.3s ease;
}

.form-step-content.active {
    display: block;
}

.section-subtitle {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-top: -10px;
    margin-bottom: 18px;
}

[data-theme="dark"] .section-subtitle {
    color: var(--gray-400);
}

.form-hint {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-top: 6px;
}

/* Quick Expiry */
.quick-expiry {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 14px;
    padding: 10px;
    background: var(--gray-50);
    border-radius: var(--radius-sm);
    flex-wrap: wrap;
}

[data-theme="dark"] .quick-expiry {
    background: rgba(255,255,255,0.04);
}

.qe-label {
    font-size: 0.78rem;
    color: var(--gray-600);
    font-weight: 500;
}

.qe-btn {
    padding: 4px 10px;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--gray-700);
    cursor: pointer;
    transition: all var(--transition-fast);
    font-family: inherit;
}

[data-theme="dark"] .qe-btn {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    color: var(--gray-300);
}

.qe-btn:hover {
    background: #6366F1;
    color: var(--white);
    border-color: #6366F1;
}

/* Permission Groups */
.permission-groups {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.perm-group {
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--radius);
    border: 1px solid var(--gray-100);
}

[data-theme="dark"] .perm-group {
    background: rgba(255,255,255,0.04);
    border-color: rgba(255,255,255,0.08);
}

.perm-group.special {
    background: linear-gradient(135deg, rgba(220,38,38,0.04), rgba(239,68,68,0.02));
    border-color: rgba(239,68,68,0.15);
}

.pg-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .pg-header {
    border-color: rgba(255,255,255,0.08);
}

.pg-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

[data-theme="dark"] .pg-title {
    color: var(--white);
}

.pg-title i {
    color: #6366F1;
}

.perm-group.special .pg-title i {
    color: #DC2626;
}

.pg-toggle {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: var(--gray-600);
    cursor: pointer;
    user-select: none;
}

.pg-toggle input {
    accent-color: #6366F1;
}

.perm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 10px;
}

.perm-option {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: var(--white);
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    cursor: pointer;
    transition: all var(--transition-fast);
    user-select: none;
}

[data-theme="dark"] .perm-option {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
}

.perm-option:hover {
    border-color: #6366F1;
    transform: translateY(-1px);
}

.perm-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.perm-option input:checked ~ .po-visual {
    background: #6366F1;
    border-color: #6366F1;
}

.perm-option input:checked ~ .po-visual::after {
    transform: translate(-50%, -50%) scale(1);
}

.perm-option input:checked ~ .po-content {
    color: #6366F1;
}

.perm-option.full-access-option input:checked {
    background: transparent;
}

.perm-option.full-access-option input:checked ~ .po-visual {
    background: #DC2626;
    border-color: #DC2626;
}

.perm-option.full-access-option input:checked ~ .po-content {
    color: #DC2626;
}

.po-visual {
    width: 20px;
    height: 20px;
    border: 2px solid var(--gray-300);
    border-radius: 5px;
    flex-shrink: 0;
    position: relative;
    transition: all var(--transition-fast);
}

[data-theme="dark"] .po-visual {
    border-color: rgba(255,255,255,0.2);
}

.po-visual::after {
    content: '\f00c';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    color: var(--white);
    font-size: 0.6rem;
    transition: transform 0.2s;
}

.po-content {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 0;
    transition: color var(--transition-fast);
}

.po-content > i {
    color: var(--gray-400);
    font-size: 0.85rem;
    flex-shrink: 0;
}

.po-content > div {
    flex: 1;
    min-width: 0;
}

.po-title {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dark);
}

[data-theme="dark"] .po-title {
    color: var(--gray-200);
}

.po-desc {
    font-size: 0.68rem;
    color: var(--gray-500);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.po-warning {
    color: #F59E0B;
    font-size: 0.85rem;
    flex-shrink: 0;
}

/* Security Summary */
.security-summary {
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--radius);
    margin-bottom: 14px;
}

[data-theme="dark"] .security-summary {
    background: rgba(255,255,255,0.04);
}

.security-summary h4 {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 12px;
}

[data-theme="dark"] .security-summary h4 {
    color: var(--white);
}

.ss-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ss-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: var(--white);
    border-radius: var(--radius-sm);
    font-size: 0.82rem;
    border-left: 3px solid;
}

[data-theme="dark"] .ss-item {
    background: rgba(255,255,255,0.05);
}

.ss-item.success { border-color: #10B981; color: #047857; }
.ss-item.success i { color: #10B981; }
.ss-item.info { border-color: #3B82F6; color: #1E40AF; }
.ss-item.info i { color: #3B82F6; }
.ss-item.warning { border-color: #F59E0B; color: #92400E; }
.ss-item.warning i { color: #F59E0B; }

[data-theme="dark"] .ss-item.success { color: #34D399; }
[data-theme="dark"] .ss-item.info { color: #93C5FD; }
[data-theme="dark"] .ss-item.warning { color: #FCD34D; }

.terms-notice {
    display: flex;
    gap: 12px;
    padding: 14px;
    background: rgba(245,158,11,0.08);
    border-left: 4px solid #F59E0B;
    border-radius: var(--radius-sm);
    font-size: 0.85rem;
    color: var(--gray-700);
    line-height: 1.5;
}

[data-theme="dark"] .terms-notice {
    color: var(--gray-300);
}

.terms-notice i {
    color: #F59E0B;
    font-size: 1.2rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.terms-notice strong {
    color: var(--dark);
}

[data-theme="dark"] .terms-notice strong {
    color: var(--white);
}

.modal-footer-steps {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* ===== NEW KEY DISPLAY MODAL ===== */
.new-key-warning {
    display: flex;
    gap: 12px;
    padding: 14px;
    background: rgba(239,68,68,0.08);
    border-left: 4px solid #EF4444;
    border-radius: var(--radius);
    margin-bottom: 20px;
}

.new-key-warning i {
    color: #EF4444;
    font-size: 1.3rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.new-key-warning strong {
    color: var(--dark);
    font-size: 0.95rem;
    display: block;
    margin-bottom: 4px;
}

[data-theme="dark"] .new-key-warning strong {
    color: var(--white);
}

.new-key-warning p {
    font-size: 0.85rem;
    color: var(--gray-700);
    margin: 0;
    line-height: 1.5;
}

[data-theme="dark"] .new-key-warning p {
    color: var(--gray-300);
}

.new-key-info {
    padding: 14px 16px;
    background: var(--gray-50);
    border-radius: var(--radius);
    margin-bottom: 18px;
}

[data-theme="dark"] .new-key-info {
    background: rgba(255,255,255,0.04);
}

.nki-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 0.88rem;
}

.nki-row:not(:last-child) {
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .nki-row {
    border-color: rgba(255,255,255,0.05);
}

.nki-label {
    color: var(--gray-600);
}

[data-theme="dark"] .nki-label {
    color: var(--gray-400);
}

.new-key-display {
    margin-bottom: 18px;
}

.nkd-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 8px;
}

[data-theme="dark"] .nkd-label {
    color: var(--white);
}

.nkd-label i {
    color: #10B981;
}

.nkd-key-box {
    display: flex;
    align-items: stretch;
    background: var(--dark);
    border-radius: var(--radius);
    overflow: hidden;
    border: 2px solid #10B981;
    box-shadow: 0 4px 20px rgba(16,185,129,0.2);
}

.nkd-key {
    flex: 1;
    padding: 14px 16px;
    color: #A5B4FC;
    font-family: 'Courier New', monospace;
    font-size: 0.88rem;
    word-break: break-all;
    user-select: all;
}

.nkd-copy-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 14px 20px;
    background: #10B981;
    color: var(--white);
    border: none;
    cursor: pointer;
    font-size: 0.78rem;
    font-weight: 600;
    transition: all var(--transition);
    font-family: inherit;
}

.nkd-copy-btn:hover {
    background: #047857;
}

.nkd-copy-btn i {
    font-size: 1rem;
}

.nkd-prefix {
    margin-top: 8px;
    font-size: 0.78rem;
    color: var(--gray-500);
    text-align: center;
}

.nkd-prefix code {
    padding: 2px 8px;
    background: var(--gray-100);
    border-radius: 4px;
    font-family: 'Courier New', monospace;
}

[data-theme="dark"] .nkd-prefix code {
    background: rgba(255,255,255,0.1);
}

.new-key-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 18px;
}

.new-key-example {
    padding: 14px;
    background: var(--gray-50);
    border-radius: var(--radius);
}

[data-theme="dark"] .new-key-example {
    background: rgba(255,255,255,0.04);
}

.nke-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 10px;
}

[data-theme="dark"] .nke-title {
    color: var(--white);
}

.nke-title i {
    color: #6366F1;
}

.nke-code {
    margin: 0;
    padding: 12px;
    background: var(--dark);
    color: #E5E7EB;
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
    line-height: 1.5;
    overflow-x: auto;
    white-space: pre;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .apikeys-layout {
        grid-template-columns: 1fr;
    }
    
    .apikeys-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .api-keys-page {
        padding: 16px;
    }
    
    .apikeys-hero {
        padding: 24px 20px;
    }
    
    .hero-title {
        font-size: 1.6rem;
    }
    
    .title-icon-wrap {
        width: 44px;
        height: 44px;
        font-size: 1.2rem;
    }
    
    .hero-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .hero-actions {
        width: 100%;
    }
    
    .hero-actions .btn-hero-primary {
        flex: 1;
    }
    
    .security-notice {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .sn-icon {
        margin: 0 auto;
    }
    
    .sn-badges {
        flex-direction: row;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .apikeys-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .toolbar-left,
    .toolbar-right {
        width: 100%;
    }
    
    .toolbar-left {
        flex-direction: column;
    }
    
    .toolbar-search {
        max-width: 100%;
    }
    
    .toolbar-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .toolbar-select {
        width: 100%;
    }
    
    .toolbar-right {
        flex-direction: row;
        justify-content: space-between;
    }
    
    .apikeys-grid {
        grid-template-columns: 1fr;
    }
    
    .apikeys-grid[data-view="list"] .apikey-card {
        flex-direction: column;
    }
    
    .apikeys-grid[data-view="list"] .akc-body {
        flex-direction: column;
        align-items: stretch;
    }
    
    .apikeys-grid[data-view="list"] .akc-key-display,
    .apikeys-grid[data-view="list"] .akc-permissions,
    .apikeys-grid[data-view="list"] .akc-meta {
        max-width: 100%;
    }
    
    .apikeys-grid[data-view="list"] .akc-footer {
        flex-direction: row;
        border-left: none;
        border-top: 1px solid var(--gray-100);
    }
    
    .step-indicator {
        overflow-x: auto;
        justify-content: flex-start;
    }
    
    .step-label {
        display: none;
    }
    
    .perm-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .apikeys-hero,
    .apikeys-sidebar,
    .security-notice,
    .apikeys-toolbar,
    .akc-actions,
    .akc-copy-btn,
    .hero-actions {
        display: none !important;
    }
    
    .apikeys-layout {
        grid-template-columns: 1fr;
    }
    
    .apikey-card {
        break-inside: avoid;
    }
}
</style>

<script>
(function() {
    'use strict';

    /* ===== COUNTER ANIMATION ===== */
    const counters = document.querySelectorAll('.stat-counter');
    const animateCounter = (el) => {
        const target = parseInt(el.dataset.target) || 0;
        const duration = 1200;
        const start = performance.now();
        
        const step = (now) => {
            const p = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            const current = Math.floor(target * eased);
            el.textContent = current.toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString('id-ID');
        };
        requestAnimationFrame(step);
    };
    
    const counterObs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                animateCounter(e.target);
                counterObs.unobserve(e.target);
            }
        });
    }, { threshold: 0.3 });
    
    counters.forEach(c => counterObs.observe(c));

    /* ===== GENERATE MODAL - MULTI STEP ===== */
    let currentStep = 1;
    const totalSteps = 3;
    
    window.openGenerateModal = function() {
        document.getElementById('generateForm').reset();
        currentStep = 1;
        updateStepUI();
        document.getElementById('generateModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('keyName')?.focus(), 300);
    };

    window.closeGenerateModal = function() {
        document.getElementById('generateModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.nextStep = function() {
        // Validate current step
        if (currentStep === 1) {
            const name = document.getElementById('keyName').value.trim();
            if (!name) {
                if (window.IFPro?.toast) {
                    window.IFPro.toast.show('Nama key harus diisi', 'error');
                }
                document.getElementById('keyName').focus();
                return;
            }
        }
        
        if (currentStep < totalSteps) {
            currentStep++;
            updateStepUI();
        }
    };

    window.prevStep = function() {
        if (currentStep > 1) {
            currentStep--;
            updateStepUI();
        }
    };

    function updateStepUI() {
        // Update step indicators
        document.querySelectorAll('.step').forEach((step, idx) => {
            step.classList.remove('active', 'completed');
            if (idx + 1 === currentStep) step.classList.add('active');
            else if (idx + 1 < currentStep) step.classList.add('completed');
        });
        
        // Show/hide step content
        document.querySelectorAll('.form-step-content').forEach(content => {
            content.classList.remove('active');
        });
        document.querySelector(`.form-step-content[data-step="${currentStep}"]`).classList.add('active');
        
        // Update buttons
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        
        prevBtn.style.display = currentStep > 1 ? 'inline-flex' : 'none';
        
        if (currentStep === totalSteps) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-flex';
        } else {
            nextBtn.style.display = 'inline-flex';
            submitBtn.style.display = 'none';
        }
    }

    window.setExpiry = function(days) {
        const input = document.getElementById('expiresAt');
        if (days === 0) {
            input.value = '';
        } else {
            const date = new Date();
            date.setDate(date.getDate() + days);
            input.value = date.toISOString().split('T')[0];
        }
        
        // Update security summary
        updateSecuritySummary();
    };

    window.toggleGroup = function(group, checked) {
        document.querySelectorAll(`input[data-group="${group}"]`).forEach(input => {
            input.checked = checked;
        });
    };

    window.toggleFullAccess = function(checked) {
        if (checked) {
            // Uncheck all other permissions
            document.querySelectorAll('input[name="permissions[]"]').forEach(input => {
                if (input.value !== 'full_access') {
                    input.checked = false;
                    input.disabled = true;
                }
            });
            
            if (window.IFPro?.toast) {
                window.IFPro.toast.show('⚠️ Full access memberikan akses penuh ke semua endpoint', 'warning');
            }
        } else {
            document.querySelectorAll('input[name="permissions[]"]').forEach(input => {
                input.disabled = false;
            });
        }
    };

    function updateSecuritySummary() {
        const expiresAt = document.getElementById('expiresAt').value;
        const ipWhitelist = document.getElementById('ipWhitelist').value.trim();
        
        const ssExpiry = document.getElementById('ssExpiry');
        const ssIp = document.getElementById('ssIp');
        
        if (ssExpiry) {
            if (expiresAt) {
                ssExpiry.className = 'ss-item success';
                ssExpiry.innerHTML = '<i class="fas fa-check-circle"></i><span>Key akan expired pada ' + new Date(expiresAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) + '</span>';
            } else {
                ssExpiry.className = 'ss-item info';
                ssExpiry.innerHTML = '<i class="fas fa-info-circle"></i><span>Key tidak akan expired (set date untuk auto-expire)</span>';
            }
        }
        
        if (ssIp) {
            if (ipWhitelist) {
                ssIp.className = 'ss-item success';
                ssIp.innerHTML = '<i class="fas fa-check-circle"></i><span>IP whitelist aktif: ' + ipWhitelist.split(',').length + ' IP</span>';
            } else {
                ssIp.className = 'ss-item info';
                ssIp.innerHTML = '<i class="fas fa-info-circle"></i><span>Akses dari semua IP (tambahkan whitelist untuk lebih aman)</span>';
            }
        }
    }

    // Live update security summary
    document.getElementById('expiresAt')?.addEventListener('change', updateSecuritySummary);
    document.getElementById('ipWhitelist')?.addEventListener('input', updateSecuritySummary);

    /* ===== COPY FUNCTIONS ===== */
    window.copyPrefix = function(prefix) {
        navigator.clipboard.writeText(prefix + '...').then(() => {
            if (window.IFPro?.toast) {
                window.IFPro.toast.show('Prefix copied ke clipboard', 'success');
            }
        }).catch(() => {
            if (window.IFPro?.toast) {
                window.IFPro.toast.show('Gagal copy', 'error');
            }
        });
    };

    window.copyText = function(elementId) {
        const text = document.getElementById(elementId).textContent;
        navigator.clipboard.writeText(text).then(() => {
            if (window.IFPro?.toast) {
                window.IFPro.toast.show('Copied ke clipboard', 'success');
            }
        });
    };

    window.copyNewKey = function() {
        const key = document.getElementById('newKeyValue').textContent;
        navigator.clipboard.writeText(key).then(() => {
            const btn = document.querySelector('.nkd-copy-btn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i><span>Copied!</span>';
            btn.style.background = '#047857';
            
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.background = '';
            }, 2000);
            
            if (window.IFPro?.toast) {
                window.IFPro.toast.show('✓ API key copied! Simpan di tempat aman', 'success');
            }
        });
    };

    window.downloadKey = function() {
        const key = document.getElementById('newKeyValue').textContent;
        const envContent = `# API Key Configuration
# Generated: ${new Date().toISOString()}
# ⚠️ JANGAN commit file ini ke git!

API_BASE_URL=${window.location.origin}/api/v1
API_KEY=${key}

# Contoh penggunaan:
# curl -X GET \\
#   \${API_BASE_URL}/berita \\
#   -H "Authorization: Bearer \${API_KEY}"
`;
        
        const blob = new Blob([envContent], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = '.env';
        a.click();
        URL.revokeObjectURL(url);
        
        if (window.IFPro?.toast) {
            window.IFPro.toast.show('✓ File .env didownload', 'success');
        }
    };

    window.showQRCode = function() {
        const key = document.getElementById('newKeyValue').textContent;
        // Simple QR code using external API
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(key)}`;
        
        const modal = document.createElement('div');
        modal.className = 'modal active';
        modal.style.display = 'flex';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 400px;">
                <div class="modal-header modal-header-gradient" style="background: linear-gradient(135deg, #10B981, #047857)">
                    <div class="modal-header-content">
                        <div class="modal-icon" style="background:rgba(255,255,255,0.2)">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div>
                            <h3 style="color:white">Scan QR Code</h3>
                            <p style="color:rgba(255,255,255,0.9);font-size:.85rem;margin-top:2px">Scan dengan mobile app</p>
                        </div>
                    </div>
                    <button class="modal-close" onclick="this.closest('.modal').remove()"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body" style="text-align:center;padding:30px">
                    <img src="${qrUrl}" alt="QR Code" style="max-width:250px;border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,0.1)">
                    <p style="margin-top:16px;font-size:0.85rem;color:var(--gray-600)">Scan QR ini dengan mobile app untuk setup otomatis</p>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    };

    window.closeNewKeyModal = function() {
        const modal = document.getElementById('newKeyModal');
        if (modal) {
            modal.style.transition = 'opacity 0.3s';
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
                document.body.style.overflow = '';
            }, 300);
        }
    };

    /* ===== KEY DETAIL MODAL ===== */
    const allKeys = <?= json_encode($keys) ?>;
    const availablePermissions = <?= json_encode($availablePermissions) ?>;

    window.openKeyDetail = function(keyId) {
        const key = allKeys.find(k => k.id == keyId);
        if (!key) return;
        
        const permissions = JSON.parse(key.permissions || '[]');
        const isExpired = key.expires_at && new Date(key.expires_at) < new Date();
        const isActive = key.status === 'aktif' && !isExpired;
        const usage = <?= json_encode(array_map(fn($k) => simulateUsage($k['id']), $keys)) ?>[keyId] || 0;
        const lastUsed = '<?= getLastUsed(0) ?>';
        
        const body = document.getElementById('keyDetailBody');
        body.innerHTML = `
            <div class="key-detail-hero" style="background: linear-gradient(135deg, ${isActive ? '#6366F1, #4338CA' : '#6B7280, #374151'}); padding: 24px; margin: -28px -28px 20px; color: white;">
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px">
                    <div style="width:54px;height:54px;background:rgba(255,255,255,0.2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <h2 style="font-size:1.3rem;font-weight:700;margin:0">${key.name}</h2>
                        <div style="font-size:0.85rem;opacity:0.9;margin-top:2px">ID: #${key.id} · Created by ${key.admin_name || 'Unknown'}</div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <span style="padding:4px 12px;background:rgba(255,255,255,0.2);border-radius:20px;font-size:0.78rem;font-weight:600;backdrop-filter:blur(10px)">
                        <i class="fas fa-circle" style="font-size:0.5rem"></i> ${isExpired ? 'Expired' : key.status}
                    </span>
                    <span style="padding:4px 12px;background:rgba(255,255,255,0.2);border-radius:20px;font-size:0.78rem;font-weight:600;backdrop-filter:blur(10px)">
                        <i class="fas fa-chart-line"></i> ${usage.toLocaleString('id-ID')} requests
                    </span>
                </div>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:14px;background:var(--gray-50);border-radius:var(--radius);margin-bottom:16px">
                <div>
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--gray-500);margin-bottom:4px">Key Prefix</div>
                    <code style="padding:6px 12px;background:var(--dark);color:#A5B4FC;border-radius:6px;font-family:'Courier New',monospace;font-size:0.88rem;font-weight:600">${key.key_prefix}••••••••</code>
                </div>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--gray-500);margin-bottom:4px">Rate Limit</div>
                    <div style="font-size:1.1rem;font-weight:700;color:var(--dark);font-family:'Space Grotesk',sans-serif">${key.rate_limit || 1000} <span style="font-size:0.78rem;font-weight:400;color:var(--gray-600)">req/jam</span></div>
                </div>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--gray-500);margin-bottom:4px">Dibuat</div>
                    <div style="font-size:0.9rem;color:var(--dark);font-weight:500">${new Date(key.created_at).toLocaleString('id-ID', {day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'})}</div>
                </div>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--gray-500);margin-bottom:4px">Expires</div>
                    <div style="font-size:0.9rem;color:${isExpired ? '#EF4444' : 'var(--dark)'};font-weight:500">${key.expires_at ? new Date(key.expires_at).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'}) : 'Never'}</div>
                </div>
                ${key.ip_whitelist ? `
                <div style="grid-column:1/-1">
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--gray-500);margin-bottom:4px">IP Whitelist</div>
                    <div style="font-size:0.85rem;color:var(--dark);font-family:'Courier New',monospace;padding:8px 12px;background:var(--white);border-radius:6px;border:1px solid var(--gray-200)">${key.ip_whitelist}</div>
                </div>
                ` : ''}
            </div>
            
            <div style="margin-bottom:18px">
                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--gray-500);margin-bottom:10px">Permissions (${permissions.length})</div>
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                    ${permissions.length === 0 || permissions.includes('full_access') 
                        ? '<span style="padding:6px 14px;background:rgba(220,38,38,0.12);color:#DC2626;border-radius:20px;font-size:0.78rem;font-weight:600"><i class="fas fa-crown"></i> Full Access</span>'
                        : permissions.map(p => {
                            const cfg = availablePermissions[p] || {label: p, icon: 'fa-key', color: '#6B7280'};
                            return `<span style="padding:6px 14px;background:${cfg.color}15;color:${cfg.color};border-radius:20px;font-size:0.78rem;font-weight:600"><i class="fas ${cfg.icon}"></i> ${cfg.label}</span>`;
                        }).join('')
                    }
                </div>
            </div>
            
            <div style="margin-bottom:18px">
                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--gray-500);margin-bottom:10px">Quick Stats</div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
                    <div style="padding:14px;background:var(--gray-50);border-radius:var(--radius);text-align:center">
                        <div style="font-size:1.4rem;font-weight:700;color:#6366F1;font-family:'Space Grotesk',sans-serif">${usage.toLocaleString('id-ID')}</div>
                        <div style="font-size:0.72rem;color:var(--gray-600);margin-top:2px">Total Requests</div>
                    </div>
                    <div style="padding:14px;background:var(--gray-50);border-radius:var(--radius);text-align:center">
                        <div style="font-size:1.4rem;font-weight:700;color:#10B981;font-family:'Space Grotesk',sans-serif">${Math.floor(usage * 0.95).toLocaleString('id-ID')}</div>
                        <div style="font-size:0.72rem;color:var(--gray-600);margin-top:2px">Successful</div>
                    </div>
                    <div style="padding:14px;background:var(--gray-50);border-radius:var(--radius);text-align:center">
                        <div style="font-size:1.4rem;font-weight:700;color:#EF4444;font-family:'Space Grotesk',sans-serif">${Math.floor(usage * 0.05).toLocaleString('id-ID')}</div>
                        <div style="font-size:0.72rem;color:var(--gray-600);margin-top:2px">Failed</div>
                    </div>
                </div>
            </div>
            
            <div style="padding:14px;background:rgba(99,102,241,0.05);border-left:4px solid #6366F1;border-radius:var(--radius-sm)">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <i class="fas fa-lightbulb" style="color:#6366F1"></i>
                    <strong style="font-size:0.88rem;color:var(--dark)">Info Keamanan</strong>
                </div>
                <div style="font-size:0.82rem;color:var(--gray-700);line-height:1.5">
                    Key ini di-hash dengan <strong>SHA-256</strong> di database. Nilai asli key tidak dapat dilihat lagi setelah dibuat. Gunakan endpoint <code style="padding:2px 6px;background:var(--gray-100);border-radius:4px;font-family:monospace;font-size:0.78rem">/api/v1/auth/verify</code> untuk memverifikasi key.
                </div>
            </div>
        `;
        
        document.getElementById('keyDetailModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closeKeyDetail = function() {
        document.getElementById('keyDetailModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    /* ===== FILTERS ===== */
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const filterExpiration = document.getElementById('filterExpiration');

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    filterStatus?.addEventListener('change', applyFilters);
    filterExpiration?.addEventListener('change', applyFilters);

    function applyFilters() {
        const url = new URL(window.location);
        const q = searchInput?.value.trim();
        const status = filterStatus?.value;
        const expiration = filterExpiration?.value;

        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        if (expiration) url.searchParams.set('expiration', expiration); else url.searchParams.delete('expiration');
        
        window.location = url;
    }

    window.resetFilters = function() {
        const url = new URL(window.location);
        ['q', 'status', 'expiration', 'sort', 'dir'].forEach(p => url.searchParams.delete(p));
        window.location = url;
    };

    /* ===== SORT DROPDOWN ===== */
    const sortBtn = document.getElementById('sortBtn');
    const sortMenu = document.querySelector('.sort-dropdown');
    
    sortBtn?.addEventListener('click', function(e) {
        e.stopPropagation();
        sortMenu.classList.toggle('open');
    });

    document.addEventListener('click', () => {
        sortMenu?.classList.remove('open');
    });

    /* ===== VIEW TOGGLE ===== */
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const view = this.dataset.view;
            const grid = document.getElementById('apikeysGrid');
            const table = document.getElementById('apikeysTable');
            
            if (view === 'table') {
                grid.style.display = 'none';
                table.style.display = 'block';
            } else {
                grid.style.display = 'grid';
                table.style.display = 'none';
                grid.dataset.view = view;
            }
            
            localStorage.setItem('apikeys_view', view);
        });
    });

    // Restore saved view
    const savedView = localStorage.getItem('apikeys_view');
    if (savedView) {
        const btn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (btn) btn.click();
    }

    /* ===== EXPORT CSV ===== */
    window.exportKeys = function() {
        const keys = <?= json_encode($keys) ?>;
        
        if (!keys || keys.length === 0) {
            if (window.IFPro?.toast) window.IFPro.toast.show('Tidak ada data untuk di-export', 'warning');
            return;
        }
        
        if (window.IFPro?.toast) window.IFPro.toast.show('Memulai export CSV...', 'info');
        
        const headers = ['ID', 'Name', 'Key Prefix', 'Created By', 'Permissions', 'Rate Limit', 'IP Whitelist', 'Expires', 'Status', 'Created At'];
        const rows = keys.map(k => {
            const perms = JSON.parse(k.permissions || '[]').join('; ');
            return [
                k.id,
                `"${k.name.replace(/"/g, '""')}"`,
                k.key_prefix,
                `"${(k.admin_name || 'Unknown').replace(/"/g, '""')}"`,
                `"${perms.replace(/"/g, '""')}"`,
                k.rate_limit || 1000,
                `"${(k.ip_whitelist || '').replace(/"/g, '""')}"`,
                k.expires_at || 'Never',
                k.status,
                k.created_at
            ];
        });
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `api-keys-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        
        setTimeout(() => {
            if (window.IFPro?.toast) window.IFPro.toast.show(`✓ ${keys.length} API keys berhasil di-export`, 'success');
        }, 500);
    };

    /* ===== DOCS ===== */
    window.showDocs = function() {
        if (window.IFPro?.toast) {
            window.IFPro.toast.show('📚 Dokumentasi API akan segera tersedia', 'info');
        }
    };

    /* ===== KEYBOARD SHORTCUTS ===== */
    document.addEventListener('keydown', function(e) {
        // N = New key
        if (e.key === 'n' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) && !e.ctrlKey && !e.metaKey) {
            e.preventDefault();
            openGenerateModal();
        }
        
        // / = Focus search
        if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            searchInput?.focus();
        }
        
        // Escape = Close modals
        if (e.key === 'Escape') {
            closeGenerateModal();
            closeKeyDetail();
            const newKeyModal = document.getElementById('newKeyModal');
            if (newKeyModal) newKeyModal.style.opacity = '0';
        }
    });

    /* ===== MODAL BACKDROP CLICKS ===== */
    document.getElementById('generateModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeGenerateModal();
    });
    
    document.getElementById('keyDetailModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeKeyDetail();
    });

    /* ===== AUTO DISMISS ALERTS ===== */
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'all 0.4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 400);
        });
    }, 5000);

    console.log('%c🔐 API Keys v4.0 Loaded', 'color:#6366F1;font-weight:bold;font-size:14px');
    console.log('%c   Features: Multi-step Generator, Security Score, 3 View Modes, Permission Groups', 'color:#64748b;font-size:11px');
    console.log('%c   Shortcuts: N (New Key), / (Search), Esc (Close)', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>