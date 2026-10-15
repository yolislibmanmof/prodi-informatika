<?php
require_once 'includes/auth.php';
$pageTitle = 'Manajemen User';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
    setFlash('error', 'Koneksi database gagal');
}

/* ===== HANDLE ACTIONS ===== */
if (isset($_GET['action']) && isset($_GET['id']) && $db) {
    $id = (int)$_GET['id'];
    try {
        if ($_GET['action'] === 'delete') {
            if ($id == $_SESSION['admin_id']) {
                setFlash('error', 'Anda tidak dapat menghapus akun Anda sendiri');
            } else {
                $db->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]);
                setFlash('success', 'User berhasil dihapus');
            }
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal memproses permintaan');
    }
    header('Location: users.php'); exit;
}

/* ===== HANDLE FORM SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
        $nama_lengkap = clean($_POST['nama_lengkap']);
        $username = clean($_POST['username']);
        $role = clean($_POST['role']);
        $password = $_POST['password'];
        
        $check = $db->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
        $check->execute([$username, $id ?: 0]);
        if ($check->fetch()) {
            setFlash('error', 'Username sudah digunakan');
            header('Location: users.php'); exit;
        }
        
        if ($id) {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE admins SET nama_lengkap=?, username=?, password=?, role=? WHERE id=?");
                $stmt->execute([$nama_lengkap, $username, $hash, $role, $id]);
            } else {
                $stmt = $db->prepare("UPDATE admins SET nama_lengkap=?, username=?, role=? WHERE id=?");
                $stmt->execute([$nama_lengkap, $username, $role, $id]);
            }
            setFlash('success', 'User berhasil diperbarui');
        } else {
            if (empty($password)) {
                setFlash('error', 'Password wajib diisi untuk user baru');
                header('Location: users.php'); exit;
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO admins (nama_lengkap, username, password, role, created_at) VALUES (?,?,?,?,NOW())");
            $stmt->execute([$nama_lengkap, $username, $hash, $role]);
            setFlash('success', 'User baru berhasil ditambahkan');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan data: ' . $e->getMessage());
    }
    header('Location: users.php'); exit;
}

/* ===== FETCH PARAMETERS ===== */
$search = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
$filterRole = $_GET['role'] ?? '';

$where = "1=1";
$params = [];
if ($filterRole) { $where .= " AND role = ?"; $params[] = $filterRole; }
if (isset($_GET['q']) && $_GET['q']) { 
    $where .= " AND (nama_lengkap LIKE ? OR username LIKE ?)"; 
    $params[] = $search; $params[] = $search; 
}

/* ===== FETCH DATA ===== */
$usersList = [];
$stats = ['total' => 0, 'superadmin' => 0, 'admin' => 0];
$roleStats = [];

if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM admins WHERE $where ORDER BY created_at DESC");
        $stmt->execute($params);
        $usersList = $stmt->fetchAll();
        
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        $stats['superadmin'] = (int)$db->query("SELECT COUNT(*) FROM admins WHERE role='superadmin'")->fetchColumn();
        $stats['admin'] = (int)$db->query("SELECT COUNT(*) FROM admins WHERE role='admin'")->fetchColumn();
        
        $roleStats = $db->query("SELECT role, COUNT(*) as total FROM admins GROUP BY role")->fetchAll();
    } catch (Throwable $e) {}
}

/* ===== EDIT MODE ===== */
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $db) {
    try {
        $st = $db->prepare("SELECT id, nama_lengkap, username, role FROM admins WHERE id = ?");
        $st->execute([(int)$_GET['id']]);
        $editData = $st->fetch();
    } catch (Throwable $e) {}
}

// Helper for initials
function getInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr(end($parts), 0, 1));
    }
    return $initials;
}

include 'includes/header.php';
?>

<div class="page-content users-page">
    <!-- ===== HERO HEADER ===== -->
    <div class="users-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
            <div class="hero-academic-pattern"><i class="fas fa-user-shield"></i></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Access Control Management</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-icon-wrap">
                        <i class="fas fa-users-cog"></i>
                    </span>
                    Manajemen User
                </h1>
                <p class="hero-subtitle">
                    Kelola hak akses, peran, dan keamanan akun administrator sistem
                </p>
                
                <div class="hero-mini-stats">
                    <div class="hms-item">
                        <i class="fas fa-users"></i>
                        <span><strong><?= $stats['total'] ?></strong> Total User</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-user-shield" style="color:#8B5CF6"></i>
                        <span><strong><?= $stats['superadmin'] ?></strong> Superadmin</span>
                    </div>
                    <div class="hms-item">
                        <i class="fas fa-user-cog" style="color:#10B981"></i>
                        <span><strong><?= $stats['admin'] ?></strong> Admin</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-actions">
                <button class="btn btn-hero-primary" onclick="openModal()">
                    <i class="fas fa-user-plus"></i>
                    <span>Tambah User</span>
                </button>
            </div>
        </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?> fade-in">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <span><?= clean($flash['message']) ?></span>
        <button onclick="this.parentElement.remove()" class="alert-close"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>

    <!-- ===== MAIN LAYOUT ===== -->
    <div class="users-layout">
        <!-- LEFT: MAIN CONTENT -->
        <div class="users-main-col">
            <!-- Toolbar -->
            <div class="card users-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama atau username..." value="<?= clean($_GET['q'] ?? '') ?>">
                        <kbd class="search-kbd">/</kbd>
                    </div>
                    
                    <!-- Custom Role Dropdown -->
                    <div class="custom-dropdown" id="roleDropdown">
                        <button class="dropdown-trigger" id="roleTrigger">
                            <i class="fas fa-filter" style="color: <?= $filterRole === 'superadmin' ? '#8B5CF6' : ($filterRole === 'admin' ? '#10B981' : '#6B7280') ?>"></i>
                            <span id="roleLabel"><?= $filterRole === 'superadmin' ? 'Superadmin' : ($filterRole === 'admin' ? 'Admin' : 'Semua Role') ?></span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu" id="roleMenu">
                            <div class="dropdown-item <?= !$filterRole ? 'active' : '' ?>" data-value="">
                                <i class="fas fa-th-large" style="color: #6B7280"></i>
                                <span>Semua Role</span>
                                <span class="dropdown-count"><?= $stats['total'] ?></span>
                            </div>
                            <div class="dropdown-item <?= $filterRole === 'superadmin' ? 'active' : '' ?>" data-value="superadmin">
                                <i class="fas fa-user-shield" style="color: #8B5CF6"></i>
                                <span>Superadmin</span>
                                <span class="dropdown-count"><?= $stats['superadmin'] ?></span>
                            </div>
                            <div class="dropdown-item <?= $filterRole === 'admin' ? 'active' : '' ?>" data-value="admin">
                                <i class="fas fa-user-cog" style="color: #10B981"></i>
                                <span>Admin</span>
                                <span class="dropdown-count"><?= $stats['admin'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <button class="toolbar-icon-btn" onclick="resetFilters()" title="Reset Filter">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
            </div>

            <!-- User Cards Grid -->
            <div class="users-grid" id="usersGrid">
                <?php if (!empty($usersList)): ?>
                    <?php foreach ($usersList as $u): 
                        $isSuper = $u['role'] === 'superadmin';
                        $gradient = $isSuper ? '#8B5CF6, #5B21B6' : '#10B981, #047857';
                        $avatarGradient = $isSuper ? '#C4B5FD, #8B5CF6' : '#6EE7B7, #10B981';
                        $initials = getInitials($u['nama_lengkap']);
                        $isYou = ($u['id'] == $_SESSION['admin_id']);
                    ?>
                    <div class="user-card" data-id="<?= $u['id'] ?>">
                        <div class="uc-header" style="background: linear-gradient(135deg, <?= $gradient ?>);">
                            <div class="uc-role-badge">
                                <i class="fas fa-<?= $isSuper ? 'user-shield' : 'user-cog' ?>"></i>
                                <?= ucfirst($u['role']) ?>
                            </div>
                            <?php if ($isYou): ?>
                            <div class="uc-you-badge"><i class="fas fa-star"></i> Anda</div>
                            <?php endif; ?>
                        </div>
                        <div class="uc-body">
                            <div class="uc-avatar-wrapper">
                                <div class="uc-avatar" style="background: linear-gradient(135deg, <?= $avatarGradient ?>);">
                                    <?= $initials ?>
                                </div>
                                <span class="uc-status <?= $u['last_login'] && (time() - strtotime($u['last_login']) < 86400) ? 'online' : 'offline' ?>" 
                                      title="<?= $u['last_login'] ? 'Last login: ' . date('d M Y, H:i', strtotime($u['last_login'])) : 'Belum pernah login' ?>"></span>
                            </div>
                            
                            <h3 class="uc-name"><?= clean($u['nama_lengkap']) ?></h3>
                            <p class="uc-username"><i class="fas fa-at"></i> <?= clean($u['username']) ?></p>
                            
                            <div class="uc-meta">
                                <div class="uc-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?= $u['last_login'] ? date('d M Y', strtotime($u['last_login'])) : 'Belum pernah' ?></span>
                                </div>
                            </div>

                            <div class="uc-actions">
                                <button class="uc-btn edit" onclick='editUser(<?= htmlspecialchars(json_encode($u, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if (!$isYou): ?>
                                <a href="?action=delete&id=<?= $u['id'] ?>" class="uc-btn delete" data-confirm="Yakin hapus user ini? Tindakan ini tidak dapat dibatalkan.">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-ult">
                        <div class="empty-illustration"><i class="fas fa-users-slash"></i></div>
                        <h3>Belum ada user</h3>
                        <p>Mulai tambahkan administrator pertama Anda untuk mengelola sistem</p>
                        <div class="empty-actions">
                            <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-user-plus"></i> Tambah User Pertama</button>
                            <button class="btn btn-secondary" onclick="resetFilters()"><i class="fas fa-redo"></i> Reset Filter</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: SIDEBAR WIDGETS -->
        <aside class="users-sidebar">
            <!-- Role Distribution -->
            <div class="card widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribusi Akses</h3>
                </div>
                <div class="widget-body">
                    <?php if (!empty($roleStats)): 
                        $maxRole = max(array_column($roleStats, 'total'));
                        $roleColors = ['superadmin' => '#8B5CF6', 'admin' => '#10B981'];
                    ?>
                        <?php foreach ($roleStats as $rs): 
                            $pct = round(($rs['total'] / max($maxRole, 1)) * 100);
                            $color = $roleColors[$rs['role']] ?? '#6B7280';
                        ?>
                        <div class="role-stat-item" onclick="filterByRole('<?= clean($rs['role']) ?>')" style="cursor:pointer">
                            <div class="rsi-head">
                                <span class="rsi-name" style="color: <?= $color ?>">
                                    <i class="fas fa-<?= $rs['role'] === 'superadmin' ? 'user-shield' : 'user-cog' ?>"></i> 
                                    <?= ucfirst(clean($rs['role'])) ?>
                                </span>
                                <span class="rsi-count"><?= $rs['total'] ?></span>
                            </div>
                            <div class="rsi-bar">
                                <div class="rsi-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="widget-empty"><i class="fas fa-chart-pie"></i><p>Belum ada data role</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Security Tips -->
            <div class="card widget-card tips-card">
                <div class="widget-header">
                    <h3><i class="fas fa-shield-alt"></i> Tips Keamanan</h3>
                </div>
                <div class="widget-body">
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-key"></i></div>
                        <div class="tip-text">Gunakan password minimal 8 karakter dengan kombinasi huruf, angka, dan simbol.</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-user-lock"></i></div>
                        <div class="tip-text">Berikan hak akses <strong>Superadmin</strong> hanya kepada pihak yang benar-benar terpercaya.</div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon"><i class="fas fa-trash-restore"></i></div>
                        <div class="tip-text">Hapus akun admin yang sudah tidak aktif atau resigned dari organisasi.</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===== MODAL FORM ===== -->
<div class="modal-ultimate" id="userModal">
    <div class="modal-content-ultimate" style="max-width:550px;">
        <div class="modal-header-ultimate" style="background: linear-gradient(135deg, #3B82F6, #1E40AF); color: white;">
            <div class="modal-header-content">
                <div class="modal-icon" style="background: rgba(255,255,255,0.2);"><i class="fas fa-user-cog"></i></div>
                <div>
                    <h3 style="color: white;" id="modalTitle">Tambah User</h3>
                    <p style="color: rgba(255,255,255,0.8); margin:0;">Kelola hak akses administrator</p>
                </div>
            </div>
            <button class="modal-close-ultimate" onclick="closeModal()" style="background: rgba(255,255,255,0.2); color: white;"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" id="userForm" class="modal-body-ultimate">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" id="formId">
            
            <div class="form-grid-2" style="grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group-ultimate" style="grid-column: 1 / -1;">
                    <label>Nama Lengkap <span class="required">*</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-user"></i>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" required placeholder="Nama lengkap administrator">
                    </div>
                </div>
                
                <div class="form-group-ultimate" style="grid-column: 1 / -1;">
                    <label>Username <span class="required">*</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-at"></i>
                        <input type="text" name="username" id="username" required placeholder="Username untuk login">
                    </div>
                </div>

                <div class="form-group-ultimate" style="grid-column: 1 / -1;">
                    <label>Password <span id="passHint" style="font-weight:normal;color:var(--gray-600);font-size:.75rem;">(Kosongkan jika tidak ingin mengubah)</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Minimal 6 karakter">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')"><i class="fas fa-eye"></i></button>
                    </div>
                    <!-- Password Strength Meter -->
                    <div class="password-strength" id="passwordStrength" style="display:none; margin-top:8px;">
                        <div class="strength-bar-container">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <span class="strength-text" id="strengthText"></span>
                    </div>
                </div>

                <div class="form-group-ultimate" style="grid-column: 1 / -1;">
                    <label>Role <span class="required">*</span></label>
                    <div class="input-group-ultimate">
                        <i class="fas fa-user-tag"></i>
                        <select name="role" id="role" required>
                            <option value="admin">Admin (Akses Terbatas)</option>
                            <option value="superadmin">Superadmin (Akses Penuh)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer-ultimate">
                <button type="button" class="btn-secondary-ultimate" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn-primary-ultimate" style="background: linear-gradient(135deg, #3B82F6, #1E40AF); box-shadow: 0 4px 16px rgba(59,130,246,0.4);">
                    <i class="fas fa-save"></i> Simpan User
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* ===== USERS PAGE ULTIMATE v4.0 ===== */
.users-page { padding: var(--spacing-lg); animation: fadeIn 0.4s ease; }

/* Hero */
.users-hero {
    position: relative; padding: 36px 40px; margin-bottom: 28px;
    background: linear-gradient(135deg, #1E293B 0%, #0F172A 50%, #020617 100%);
    border-radius: var(--radius-2xl); overflow: hidden; box-shadow: var(--shadow-xl);
    border: 1px solid rgba(59,130,246,0.2);
}
.hero-bg-effects { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.hero-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.5; animation: orbFloat 20s infinite ease-in-out; }
.orb-1 { width: 320px; height: 320px; background: radial-gradient(circle, rgba(59,130,246,0.3), transparent); top: -120px; right: 8%; }
.orb-2 { width: 220px; height: 220px; background: radial-gradient(circle, rgba(139,92,246,0.2), transparent); bottom: -100px; left: 15%; animation-delay: -7s; }
.hero-grid-pattern { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px); background-size: 40px 40px; }
.hero-academic-pattern { position: absolute; top: 50%; right: 5%; transform: translateY(-50%); font-size: 18rem; color: rgba(59,130,246,0.05); pointer-events: none; display: flex; align-items: center; justify-content: center; }
.hero-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; color: var(--white); }
.hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(59,130,246,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(59,130,246,0.3); border-radius: 50px; font-size: 0.78rem; font-weight: 600; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #93C5FD; }
.hero-title { font-size: 2.2rem; font-weight: 700; margin-bottom: 10px; line-height: 1.2; font-family: 'Space Grotesk', sans-serif; display: flex; align-items: center; gap: 16px; }
.title-icon-wrap { width: 56px; height: 56px; background: rgba(59,130,246,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(59,130,246,0.3); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; color: #93C5FD; }
.hero-subtitle { font-size: 0.95rem; opacity: 0.9; max-width: 600px; color: #CBD5E1; }
.hero-mini-stats { display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
.hms-item { display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; font-size: 0.85rem; color: #E2E8F0; }
.hms-item i { color: #94A3B8; }
.hms-item strong { font-family: 'Space Grotesk', sans-serif; font-weight: 700; color: var(--white); }
.hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-hero-primary { padding: 14px 26px; background: linear-gradient(135deg, #3B82F6, #1E40AF); color: var(--white); border: none; border-radius: var(--radius); font-size: 0.92rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; box-shadow: 0 8px 20px rgba(59,130,246,0.4); }
.btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(59,130,246,0.5); }

/* Layout */
.users-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.users-main-col { min-width: 0; }

/* Toolbar */
.users-toolbar { padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; overflow: visible !important; }
.toolbar-left { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; flex: 1; min-width: 0; overflow: visible !important; }
.toolbar-search { position: relative; flex: 1; min-width: 240px; max-width: 380px; }
.toolbar-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 0.9rem; pointer-events: none; }
.toolbar-search input { width: 100%; padding: 10px 44px 10px 42px; border: 1.5px solid var(--gray-200); border-radius: var(--radius); font-size: 0.88rem; font-family: inherit; background: var(--gray-50); transition: all var(--transition); }
[data-theme="dark"] .toolbar-search input { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.toolbar-search input:focus { outline: none; border-color: #3B82F6; background: var(--white); box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }
.search-kbd { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); padding: 2px 8px; background: var(--gray-200); color: var(--gray-600); border-radius: 4px; font-size: 0.7rem; font-weight: 600; font-family: 'Courier New', monospace; pointer-events: none; }

/* Custom Dropdown */
.custom-dropdown { position: relative; min-width: 200px; z-index: 1000; overflow: visible !important; }
.dropdown-trigger {
    display: flex; align-items: center; gap: 10px; padding: 10px 16px;
    background: var(--white); border: 1.5px solid var(--gray-200); border-radius: var(--radius);
    font-size: 0.88rem; font-weight: 600; color: var(--dark); cursor: pointer;
    transition: all var(--transition); width: 100%; text-align: left; font-family: inherit;
}
[data-theme="dark"] .dropdown-trigger { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.dropdown-trigger:hover { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.dropdown-arrow { margin-left: auto; transition: transform 0.3s ease; color: var(--gray-500); font-size: 0.75rem; }
.custom-dropdown.open .dropdown-arrow { transform: rotate(180deg); }

.dropdown-menu {
    position: absolute !important;
    top: calc(100% + 8px) !important;
    left: 0 !important;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    max-height: 300px;
    overflow-y: auto !important;
    z-index: 999999 !important;
    padding: 8px;
    min-width: 100%;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    display: block !important;
}
[data-theme="dark"] .dropdown-menu { background: var(--dark-2); border-color: rgba(255,255,255,0.1); }
.dropdown-menu::-webkit-scrollbar { width: 6px; }
.dropdown-menu::-webkit-scrollbar-track { background: rgba(59,130,246,0.05); border-radius: 3px; }
.dropdown-menu::-webkit-scrollbar-thumb { background: #3B82F6; border-radius: 3px; }
.custom-dropdown.open .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }

.dropdown-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: var(--radius); cursor: pointer; transition: all var(--transition-fast); font-size: 0.88rem; color: var(--dark); white-space: nowrap; }
[data-theme="dark"] .dropdown-item { color: var(--gray-200); }
.dropdown-item:hover { background: rgba(59,130,246,0.08); transform: translateX(4px); }
.dropdown-item.active { background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(30,64,175,0.1)); color: #3B82F6; font-weight: 600; }
.dropdown-item i:first-child { width: 18px; font-size: 0.9rem; }
.dropdown-count { margin-left: auto; font-size: 0.72rem; color: var(--gray-500); background: var(--gray-100); padding: 2px 8px; border-radius: 10px; font-weight: 600; }
[data-theme="dark"] .dropdown-count { background: rgba(255,255,255,0.08); color: var(--gray-400); }
.dropdown-item.active .dropdown-count { background: rgba(59,130,246,0.2); color: #3B82F6; }

.toolbar-right { display: flex; gap: 10px; align-items: center; }
.toolbar-icon-btn { width: 40px; height: 40px; border-radius: var(--radius); border: 1.5px solid var(--gray-200); background: var(--white); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--gray-600); transition: all var(--transition); font-size: 0.9rem; }
[data-theme="dark"] .toolbar-icon-btn { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--gray-300); }
.toolbar-icon-btn:hover { background: #3B82F6; color: var(--white); border-color: #3B82F6; transform: translateY(-2px); }

/* User Cards (Access Badge Style) */
.users-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px; }
.user-card { position: relative; background: var(--white); border-radius: var(--radius-xl); overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; }
[data-theme="dark"] .user-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.user-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: rgba(59,130,246,0.3); }

.uc-header { position: relative; height: 100px; display: flex; align-items: flex-start; justify-content: space-between; padding: 12px 16px; }
.uc-role-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border-radius: 20px; font-size: 0.72rem; font-weight: 600; color: white; }
.uc-you-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: rgba(255,255,255,0.9); color: #F59E0B; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }

.uc-body { padding: 0 20px 20px; display: flex; flex-direction: column; flex: 1; position: relative; }
.uc-avatar-wrapper { position: relative; width: 80px; height: 80px; margin: -40px auto 12px; }
.uc-avatar { width: 100%; height: 100%; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700; color: white; border: 4px solid var(--white); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
[data-theme="dark"] .uc-avatar { border-color: var(--dark-2); }
.uc-status { position: absolute; bottom: 4px; right: 4px; width: 16px; height: 16px; border-radius: 50%; border: 3px solid var(--white); }
[data-theme="dark"] .uc-status { border-color: var(--dark-2); }
.uc-status.online { background: #10B981; box-shadow: 0 0 0 2px rgba(16,185,129,0.3); }
.uc-status.offline { background: #94A3B8; }

.uc-name { font-size: 1.15rem; font-weight: 700; color: var(--dark); text-align: center; margin-bottom: 4px; line-height: 1.3; }
[data-theme="dark"] .uc-name { color: var(--white); }
.uc-username { font-size: 0.85rem; color: var(--gray-600); text-align: center; margin-bottom: 16px; }
[data-theme="dark"] .uc-username { color: var(--gray-400); }
.uc-username i { margin-right: 4px; color: #3B82F6; }

.uc-meta { display: flex; justify-content: center; margin-bottom: 16px; }
.uc-meta-item { display: flex; align-items: center; gap: 6px; font-size: 0.78rem; color: var(--gray-600); background: var(--gray-50); padding: 6px 12px; border-radius: 20px; }
[data-theme="dark"] .uc-meta-item { background: rgba(255,255,255,0.05); color: var(--gray-400); }
.uc-meta-item i { color: #3B82F6; }

.uc-actions { display: flex; gap: 10px; margin-top: auto; }
.uc-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px; border-radius: var(--radius); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all var(--transition-fast); text-decoration: none; border: none; font-family: inherit; }
.uc-btn.edit { background: rgba(59,130,246,0.1); color: #3B82F6; }
.uc-btn.edit:hover { background: #3B82F6; color: white; }
.uc-btn.delete { background: rgba(239,68,68,0.1); color: #EF4444; }
.uc-btn.delete:hover { background: #EF4444; color: white; }

/* Sidebar */
.users-sidebar { display: flex; flex-direction: column; gap: 20px; position: sticky; top: 90px; }
.widget-card { overflow: hidden; background: var(--white); border-radius: var(--radius-xl); border: 1px solid var(--gray-100); box-shadow: var(--shadow-sm); }
[data-theme="dark"] .widget-card { background: var(--dark-2); border-color: rgba(255,255,255,0.08); }
.widget-header { padding: 16px 18px; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50); }
[data-theme="dark"] .widget-header { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.08); }
.widget-header h3 { font-size: 0.95rem; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 8px; margin: 0; }
[data-theme="dark"] .widget-header h3 { color: var(--white); }
.widget-header h3 i { color: #3B82F6; }
.widget-body { padding: 16px 18px; }
.widget-empty { text-align: center; padding: 24px 12px; color: var(--gray-600); }
.widget-empty i { font-size: 2rem; color: var(--gray-300); margin-bottom: 8px; display: block; }

.role-stat-item { padding: 10px 0; border-bottom: 1px solid var(--gray-100); transition: all var(--transition-fast); }
[data-theme="dark"] .role-stat-item { border-color: rgba(255,255,255,0.05); }
.role-stat-item:last-child { border-bottom: none; }
.role-stat-item:hover { background: rgba(59,130,246,0.04); padding-left: 8px; border-radius: var(--radius); }
.rsi-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.rsi-name { font-size: 0.82rem; font-weight: 600; color: var(--dark); display: flex; align-items: center; gap: 8px; }
[data-theme="dark"] .rsi-name { color: var(--gray-200); }
.rsi-name i { font-size: 0.78rem; }
.rsi-count { font-size: 0.85rem; font-weight: 700; color: var(--dark); font-family: 'Space Grotesk', sans-serif; }
[data-theme="dark"] .rsi-count { color: var(--white); }
.rsi-bar { height: 6px; background: var(--gray-100); border-radius: 50px; overflow: hidden; }
[data-theme="dark"] .rsi-bar { background: rgba(255,255,255,0.08); }
.rsi-bar-fill { height: 100%; border-radius: 50px; animation: barFill 1s ease-out; }

.tips-card { background: linear-gradient(135deg, rgba(59,130,246,0.04), rgba(30,64,175,0.04)); border: 1px solid rgba(59,130,246,0.1); }
.tip-item { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid rgba(59,130,246,0.1); align-items: flex-start; }
.tip-item:last-child { border-bottom: none; }
.tip-icon { width: 28px; height: 28px; border-radius: var(--radius-sm); background: rgba(59,130,246,0.12); color: #3B82F6; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; margin-top: 1px; }
.tip-text { font-size: 0.82rem; color: var(--gray-700); line-height: 1.5; flex: 1; }
[data-theme="dark"] .tip-text { color: var(--gray-300); }

/* Modal & Password Strength */
.modal-ultimate { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 20px; animation: fadeIn 0.3s; }
.modal-ultimate.open { display: flex; }
.modal-content-ultimate { background: var(--white); border-radius: var(--radius-2xl); width: 100%; max-width: 550px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; animation: modalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 30px 60px rgba(0,0,0,0.3); }
[data-theme="dark"] .modal-content-ultimate { background: var(--dark-2); }
@keyframes modalIn { from { opacity: 0; transform: scale(0.9) translateY(20px); } to { opacity: 1; transform: none; } }
.modal-header-ultimate { display: flex; justify-content: space-between; align-items: center; padding: 24px 28px; }
.modal-header-content { display: flex; align-items: center; gap: 16px; }
.modal-icon { width: 50px; height: 50px; border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 1.3rem; flex-shrink: 0; }
.modal-header-ultimate h3 { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; }
.modal-close-ultimate { width: 40px; height: 40px; border-radius: var(--radius); border: none; cursor: pointer; transition: all var(--transition-fast); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
.modal-close-ultimate:hover { background: rgba(255,255,255,0.3); transform: rotate(90deg); }
.modal-body-ultimate { flex: 1; overflow-y: auto; padding: 28px; }
.modal-footer-ultimate { display: flex; justify-content: flex-end; gap: 12px; padding: 20px 28px; border-top: 1px solid var(--gray-100); background: var(--gray-50); }
[data-theme="dark"] .modal-footer-ultimate { border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.2); }

.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group-ultimate { display: flex; flex-direction: column; gap: 6px; }
.form-group-ultimate label { font-size: 0.88rem; font-weight: 600; color: var(--dark); }
[data-theme="dark"] .form-group-ultimate label { color: var(--gray-200); }
.input-group-ultimate { position: relative; }
.input-group-ultimate i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 1rem; z-index: 1; pointer-events: none; }
.input-group-ultimate input, .input-group-ultimate select { width: 100%; padding: 12px 16px 12px 46px; border: 1.5px solid var(--gray-200); border-radius: var(--radius); font-size: 0.95rem; font-family: inherit; background: var(--gray-50); transition: all var(--transition); color: var(--dark); }
[data-theme="dark"] .input-group-ultimate input, [data-theme="dark"] .input-group-ultimate select { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.input-group-ultimate input:focus, .input-group-ultimate select:focus { outline: none; border-color: #3B82F6; background: var(--white); box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }
.toggle-password { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--gray-500); cursor: pointer; z-index: 2; padding: 4px; }
.toggle-password:hover { color: #3B82F6; }

/* Password Strength Meter */
.password-strength { margin-top: 8px; }
.strength-bar-container { height: 6px; background: var(--gray-200); border-radius: 3px; overflow: hidden; margin-bottom: 4px; }
.strength-bar { height: 100%; width: 0%; border-radius: 3px; transition: all 0.3s ease; }
.strength-weak { background: #EF4444; width: 25%; }
.strength-medium { background: #F59E0B; width: 50%; }
.strength-strong { background: #3B82F6; width: 75%; }
.strength-very-strong { background: #10B981; width: 100%; }
.strength-text { font-size: 0.75rem; font-weight: 600; }
.strength-weak + .strength-text { color: #EF4444; }
.strength-medium + .strength-text { color: #F59E0B; }
.strength-strong + .strength-text { color: #3B82F6; }
.strength-very-strong + .strength-text { color: #10B981; }

.btn-secondary-ultimate, .btn-primary-ultimate { padding: 12px 24px; border-radius: var(--radius); font-size: 0.9rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--transition); font-family: inherit; border: none; text-decoration: none; }
.btn-secondary-ultimate { background: var(--gray-100); color: var(--gray-700); }
[data-theme="dark"] .btn-secondary-ultimate { background: rgba(255,255,255,0.08); color: var(--gray-300); }
.btn-secondary-ultimate:hover { background: var(--gray-200); }
.btn-primary-ultimate { background: linear-gradient(135deg, #3B82F6, #1E40AF); color: var(--white); box-shadow: 0 4px 16px rgba(59,130,246,0.4); }
.btn-primary-ultimate:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(59,130,246,0.5); color: white; }

.empty-state-ult { grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--white); border-radius: var(--radius-xl); border: 2px dashed var(--gray-200); }
[data-theme="dark"] .empty-state-ult { background: var(--dark-2); border-color: rgba(255,255,255,0.1); }
.empty-illustration i { font-size: 3.5rem; color: var(--gray-300); margin-bottom: 16px; display: block; }
[data-theme="dark"] .empty-illustration i { color: var(--gray-600); }
.empty-state-ult h3 { font-size: 1.3rem; color: var(--dark); margin-bottom: 8px; }
[data-theme="dark"] .empty-state-ult h3 { color: var(--white); }
.empty-state-ult p { color: var(--gray-600); margin-bottom: 24px; }
[data-theme="dark"] .empty-state-ult p { color: var(--gray-400); }
.empty-actions { display: flex; gap: 12px; justify-content: center; }

@media (max-width: 1200px) {
    .users-layout { grid-template-columns: 1fr; }
    .users-sidebar { position: static; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
}
@media (max-width: 768px) {
    .users-page { padding: 16px; }
    .users-hero { padding: 24px 20px; }
    .hero-title { font-size: 1.6rem; }
    .title-icon-wrap { width: 44px; height: 44px; font-size: 1.2rem; }
    .hero-content { flex-direction: column; align-items: flex-start; }
    .hero-actions { width: 100%; }
    .users-toolbar { flex-direction: column; align-items: stretch; }
    .toolbar-left, .toolbar-right { width: 100%; flex-direction: column; align-items: stretch; }
    .toolbar-search { max-width: 100%; }
    .toolbar-right { flex-direction: row; justify-content: space-between; }
    .users-grid { grid-template-columns: 1fr; }
    .custom-dropdown { width: 100%; }
    .form-grid-2 { grid-template-columns: 1fr; }
}
</style>

<script>
(function() {
    'use strict';

    // Custom Dropdown Logic
    const dropdown = document.getElementById('roleDropdown');
    const trigger = document.getElementById('roleTrigger');
    const menu = document.getElementById('roleMenu');
    const label = document.getElementById('roleLabel');
    const items = menu.querySelectorAll('.dropdown-item');

    if (dropdown && trigger && menu) {
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });

        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });

        items.forEach(item => {
            item.addEventListener('click', function() {
                const value = this.dataset.value;
                const text = this.querySelector('span').textContent;
                const icon = this.querySelector('i');
                
                label.textContent = text;
                trigger.querySelector('i:first-child').style.color = icon.style.color;
                
                items.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                dropdown.classList.remove('open');
                applyFilters(value);
            });
        });
    }

    window.filterByRole = function(role) {
        applyFilters(role);
    };

    window.applyFilters = function(role) {
        const url = new URL(window.location);
        const q = document.getElementById('searchInput').value.trim();
        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        if (role) url.searchParams.set('role', role); else url.searchParams.delete('role');
        window.location = url;
    };

    window.resetFilters = function() {
        const url = new URL(window.location);
        url.searchParams.delete('q');
        url.searchParams.delete('role');
        window.location = url;
    };

    // Modal Functions
    window.openModal = function() {
        document.getElementById('userForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('modalTitle').textContent = 'Tambah User Baru';
        document.getElementById('password').required = true;
        document.getElementById('passHint').style.display = 'none';
        document.getElementById('passwordStrength').style.display = 'none';
        document.getElementById('userModal').classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('nama_lengkap')?.focus(), 300);
    };

    window.closeModal = function() {
        document.getElementById('userModal').classList.remove('open');
        document.body.style.overflow = '';
    };

    window.editUser = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit User';
        document.getElementById('formId').value = data.id;
        document.getElementById('nama_lengkap').value = data.nama_lengkap;
        document.getElementById('username').value = data.username;
        document.getElementById('role').value = data.role;
        document.getElementById('password').required = false;
        document.getElementById('passHint').style.display = 'inline';
        document.getElementById('passwordStrength').style.display = 'none';
        document.getElementById('userModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    // Password Visibility Toggle
    window.togglePasswordVisibility = function(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    };

    // Password Strength Meter
    const passwordInput = document.getElementById('password');
    const strengthContainer = document.getElementById('passwordStrength');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            if (val.length === 0) {
                strengthContainer.style.display = 'none';
                return;
            }
            strengthContainer.style.display = 'block';
            
            let strength = 0;
            if (val.length >= 6) strength += 1;
            if (val.match(/[a-z]/) && val.match(/[A-Z]/)) strength += 1;
            if (val.match(/[0-9]/)) strength += 1;
            if (val.match(/[^a-zA-Z0-9]/)) strength += 1;

            strengthBar.className = 'strength-bar';
            if (strength <= 1) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Lemah';
            } else if (strength === 2) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Sedang';
            } else if (strength === 3) {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Kuat';
            } else {
                strengthBar.classList.add('strength-very-strong');
                strengthText.textContent = 'Sangat Kuat';
            }
        });
    }

    // Search Debounce
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const url = new URL(window.location);
                const q = this.value.trim();
                if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
                window.location = url;
            }, 600);
        });
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                const url = new URL(window.location);
                const q = this.value.trim();
                if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
                window.location = url;
            }
        });
    }

    // Modal Backdrop Click
    document.getElementById('userModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'all 0.4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 400);
        });
    }, 5000);

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            searchInput?.focus();
        }
        if (e.key === 'Escape') {
            const modal = document.getElementById('userModal');
            if (modal?.classList.contains('open')) closeModal();
            if (dropdown) dropdown.classList.remove('open');
        }
    });

    // Auto Open Edit Modal
    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && $editData): ?>
    window.addEventListener('load', () => {
        setTimeout(() => editUser(<?= json_encode($editData, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>), 300);
    });
    <?php endif; ?>

    console.log('%c👥 User Management Ultimate Loaded', 'color:#3B82F6;font-weight:bold;font-size:12px');
})();
</script>

<?php include 'includes/footer.php'; ?>