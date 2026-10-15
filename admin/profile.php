<?php
require_once 'includes/auth.php';
$pageTitle = 'Profil Saya';

try { $db = getDB(); } catch (Throwable $e) { $db = null; }

$adminId   = (int)($_SESSION['admin_id'] ?? 0);
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$adminEmail = $_SESSION['admin_email'] ?? '';
$adminSince = '';

/* ===== DETEKSI TABEL & MUAT DATA (Ultimate Anti-Error) ===== */
$table = null; $row = [];
if ($db && $adminId > 0) {
    foreach (['users', 'admins', 'admin'] as $t) {
        try {
            $st = $db->prepare("SELECT * FROM `$t` WHERE id = ? LIMIT 1");
            $st->execute([$adminId]);
            $r = $st->fetch();
            if ($r) { $table = $t; $row = $r; break; }
        } catch (Throwable $e) {}
    }
}
$nameCol = $emailCol = $passCol = null;
if ($row) {
    foreach (['name', 'nama', 'nama_lengkap', 'username', 'full_name'] as $c) if (isset($row[$c])) { $nameCol = $c; break; }
    if (isset($row['email'])) $emailCol = 'email';
    foreach (['password', 'pass_hash', 'password_hash'] as $c) if (isset($row[$c])) { $passCol = $c; break; }
    if ($nameCol) $adminName = $row[$nameCol] ?: $adminName;
    if ($emailCol) $adminEmail = $row[$emailCol] ?? '';
    $adminSince = $row['created_at'] ?? '';
}

/* ===== HELPERS PASSWORD (multi-format) ===== */
function verifyAnyPassword($plain, $hash) {
    if (!$hash || !is_string($hash)) return false;
    if (password_verify($plain, $hash)) return true;
    if (hash_equals($hash, md5($plain))) return true;
    if (hash_equals($hash, sha1($plain))) return true;
    return false;
}
function hashLikeStored($plain, $hash) {
    if (is_string($hash) && (strpos($hash, '$2y$') === 0 || strpos($hash, '$argon') === 0)) return password_hash($plain, PASSWORD_DEFAULT);
    if (is_string($hash) && strlen($hash) === 32 && ctype_xdigit($hash)) return md5($plain);
    if (is_string($hash) && strlen($hash) === 40 && ctype_xdigit($hash)) return sha1($plain);
    return password_hash($plain, PASSWORD_DEFAULT);
}

/* ===== HANDLE POST ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update_profile') {
        $newName  = clean($_POST['nama'] ?? '');
        $newEmail = clean($_POST['email'] ?? '');
        if ($newName === '') {
            setFlash('error', 'Nama tidak boleh kosong');
        } elseif (!$db || !$table) {
            setFlash('error', 'Tabel user tidak terdeteksi — profil tidak dapat disimpan');
        } else {
            try {
                $sets = []; $params = [];
                if ($nameCol)  { $sets[] = "`$nameCol` = ?";  $params[] = $newName; }
                if ($emailCol) { $sets[] = "`$emailCol` = ?"; $params[] = $newEmail; }
                if (isset($row['updated_at'])) $sets[] = "`updated_at` = NOW()";
                if (empty($sets)) throw new Exception('Tidak ada kolom yang dapat diperbarui');
                $params[] = $adminId;
                $db->prepare("UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
                $_SESSION['admin_name'] = $newName;
                if ($emailCol) $_SESSION['admin_email'] = $newEmail;
                $adminName = $newName; $adminEmail = $newEmail;
                setFlash('success', 'Profil berhasil diperbarui');
            } catch (Throwable $e) {
                setFlash('error', 'Gagal memperbarui profil: ' . $e->getMessage());
            }
        }
        header('Location: profile.php'); exit;
    }

    if ($action === 'update_password') {
        $cur  = $_POST['password_lama'] ?? '';
        $new  = $_POST['password_baru'] ?? '';
        $conf = $_POST['password_konfirmasi'] ?? '';
        if (!$db || !$table || !$passCol) {
            setFlash('error', 'Ubah sandi tidak tersedia: kolom password tidak terdeteksi');
        } elseif ($new !== $conf) {
            setFlash('error', 'Konfirmasi sandi tidak cocok');
        } elseif (strlen($new) < 8) {
            setFlash('error', 'Sandi baru minimal 8 karakter');
        } elseif (!verifyAnyPassword($cur, $row[$passCol] ?? '')) {
            setFlash('error', 'Sandi lama tidak sesuai');
        } else {
            try {
                $hash = hashLikeStored($new, $row[$passCol] ?? '');
                $sets = ["`$passCol` = ?"];
                if (isset($row['updated_at'])) $sets[] = "`updated_at` = NOW()";
                $db->prepare("UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = ?")->execute([$hash, $adminId]);
                setFlash('success', 'Sandi berhasil diubah. Gunakan sandi baru saat login berikutnya.');
            } catch (Throwable $e) {
                setFlash('error', 'Gagal mengubah sandi: ' . $e->getMessage());
            }
        }
        header('Location: profile.php'); exit;
    }
}

/* ===== STATISTIK KONTRIBUSI ===== */
$statArtikel = 0;
if ($db && $adminId > 0) {
    try { $statArtikel = (int)$db->query("SELECT COUNT(*) FROM berita WHERE author_id = {$adminId}")->fetchColumn(); } catch (Throwable $e) {}
}

include 'includes/header.php';
?>

<div class="page-content profile-page">
    <!-- ===== HERO ===== -->
    <div class="profile-hero">
        <div class="hero-bg-effects">
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-grid-pattern"></div>
        </div>
        <div class="profile-hero-content">
            <div class="pf-avatar-wrap">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($adminName) ?>&background=10B981&color=fff&bold=true&size=160" alt="<?= clean($adminName) ?>">
                <span class="pf-avatar-status"></span>
            </div>
            <div class="pf-hero-info">
                <div class="pf-hero-badges">
                    <span class="pf-badge role"><i class="fas fa-user-shield"></i> <?= clean(ucfirst($adminRole)) ?></span>
                    <span class="pf-badge active"><i class="fas fa-check-circle"></i> Akun Aktif</span>
                    <?php if ($adminSince): ?><span class="pf-badge since"><i class="fas fa-calendar"></i> Sejak <?= date('M Y', strtotime($adminSince)) ?></span><?php endif; ?>
                </div>
                <h1><?= clean($adminName) ?></h1>
                <p><?= clean($adminEmail ?: 'Email belum diatur') ?></p>
                <div class="pf-mini-stats">
                    <div class="pf-ms-item"><i class="fas fa-newspaper"></i> <span><strong><?= $statArtikel ?></strong> Artikel</span></div>
                    <div class="pf-ms-item"><i class="fas fa-id-badge"></i> <span>ID: <strong>#<?= str_pad($adminId, 4, '0', STR_PAD_LEFT) ?></strong></span></div>
                    <div class="pf-ms-item"><i class="fas fa-database"></i> <span>Tabel: <strong><?= clean($table ?: 'sesi') ?></strong></span></div>
                </div>
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

    <!-- ===== FORMS GRID ===== -->
    <div class="profile-layout">
        <div class="profile-main-col">
            <!-- Edit Profil -->
            <div class="card pf-card">
                <div class="pf-card-header">
                    <h3><i class="fas fa-user-edit"></i> Informasi Profil</h3>
                </div>
                <form method="POST" class="pf-card-body">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="field-label">Nama Tampilan <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" name="nama" required value="<?= clean($adminName) ?>" maxlength="60">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="field-label">Email</label>
                            <div class="input-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" value="<?= clean($adminEmail) ?>" placeholder="email@anda.com" <?= $emailCol ? '' : 'disabled' ?>>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="field-label">Role (tidak dapat diubah)</label>
                            <div class="input-icon">
                                <i class="fas fa-user-shield"></i>
                                <input type="text" value="<?= clean(ucfirst($adminRole)) ?>" disabled>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="field-label">ID Admin</label>
                            <div class="input-icon">
                                <i class="fas fa-hashtag"></i>
                                <input type="text" value="#<?= str_pad($adminId, 4, '0', STR_PAD_LEFT) ?>" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="pf-form-actions">
                        <button type="submit" class="btn btn-primary" <?= $table ? '' : 'disabled title="Tabel user tidak terdeteksi"' ?>>
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Ganti Password -->
            <div class="card pf-card">
                <div class="pf-card-header">
                    <h3><i class="fas fa-key"></i> Keamanan Akun</h3>
                </div>
                <form method="POST" class="pf-card-body" id="pfPassForm">
                    <input type="hidden" name="action" value="update_password">
                    <div class="form-grid-2">
                        <div class="form-group" style="grid-column: 1 / -1">
                            <label class="field-label">Sandi Lama <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password_lama" id="pfOldPass" required autocomplete="current-password">
                                <button type="button" class="pf-eye" data-target="pfOldPass" title="Lihat"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="field-label">Sandi Baru <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-key"></i>
                                <input type="password" name="password_baru" id="pfNewPass" required minlength="8" autocomplete="new-password">
                                <button type="button" class="pf-eye" data-target="pfNewPass" title="Lihat"><i class="fas fa-eye"></i></button>
                            </div>
                            <div class="pf-strength"><div class="pf-strength-fill" id="pfStrengthFill"></div></div>
                            <small class="form-hint" id="pfStrengthText">Minimal 8 karakter</small>
                        </div>
                        <div class="form-group">
                            <label class="field-label">Konfirmasi Sandi <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-shield-alt"></i>
                                <input type="password" name="password_konfirmasi" id="pfConfPass" required minlength="8" autocomplete="new-password">
                            </div>
                            <small class="form-hint" id="pfMatchText">&nbsp;</small>
                        </div>
                    </div>
                    <div class="pf-form-actions">
                        <button type="submit" class="btn btn-primary" <?= ($table && $passCol) ? '' : 'disabled title="Kolom password tidak terdeteksi"' ?>>
                            <i class="fas fa-sync-alt"></i> Perbarui Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SIDEBAR -->
        <aside class="profile-sidebar">
            <div class="card widget-card">
                <div class="widget-header"><h3><i class="fas fa-info-circle"></i> Informasi Sesi</h3></div>
                <div class="widget-body">
                    <div class="pf-sess-row"><span>Login sebagai</span><strong><?= clean($adminName) ?></strong></div>
                    <div class="pf-sess-row"><span>Role</span><strong><?= clean(ucfirst($adminRole)) ?></strong></div>
                    <div class="pf-sess-row"><span>Waktu server</span><strong><?= date('d M Y, H:i') ?> WIB</strong></div>
                    <div class="pf-sess-row"><span>Format sandi</span><strong><?= $passCol ? (strpos($row[$passCol] ?? '', '$2y$') === 0 ? 'bcrypt' : 'legacy') : '-' ?></strong></div>
                </div>
            </div>

            <div class="card widget-card tips-card">
                <div class="widget-header"><h3><i class="fas fa-lightbulb"></i> Tips Keamanan</h3></div>
                <div class="widget-body">
                    <div class="tip-item"><div class="tip-icon"><i class="fas fa-check"></i></div><div class="tip-text">Gunakan kombinasi huruf, angka, dan simbol</div></div>
                    <div class="tip-item"><div class="tip-icon"><i class="fas fa-check"></i></div><div class="tip-text">Jangan bagikan sandi kepada siapa pun</div></div>
                    <div class="tip-item"><div class="tip-icon"><i class="fas fa-check"></i></div><div class="tip-text">Ganti sandi secara berkala (3 bulan sekali)</div></div>
                    <div class="tip-item"><div class="tip-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="tip-text">Selalu logout saat memakai komputer bersama</div></div>
                </div>
            </div>

            <div class="card widget-card">
                <div class="widget-header"><h3><i class="fas fa-bolt"></i> Aksi Cepat</h3></div>
                <div class="widget-body">
                    <a href="dashboard.php" class="qa-btn"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span><i class="fas fa-chevron-right qa-arrow"></i></a>
                    <a href="users.php" class="qa-btn"><i class="fas fa-users-cog"></i><span>Manajemen User</span><i class="fas fa-chevron-right qa-arrow"></i></a>
                    <a href="logout.php" class="qa-btn danger"><i class="fas fa-sign-out-alt"></i><span>Keluar</span><i class="fas fa-chevron-right qa-arrow"></i></a>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
/* ============================================================
   PROFILE PAGE - ACCOUNT CENTER EDITION v4.0
============================================================ */
.profile-page { padding: var(--spacing-lg); animation: fadeIn 0.4s ease; }

.profile-hero {
    position: relative; padding: 36px 40px; margin-bottom: 28px;
    background: linear-gradient(135deg, #064E3B 0%, #047857 50%, #10B981 100%);
    border-radius: var(--radius-2xl); overflow: hidden; box-shadow: var(--shadow-xl);
}
.hero-bg-effects { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.hero-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.4; animation: orbFloat 20s infinite ease-in-out; }
.orb-1 { width: 320px; height: 320px; background: radial-gradient(circle, #6EE7B7, transparent); top: -120px; right: 8%; }
.orb-2 { width: 220px; height: 220px; background: radial-gradient(circle, #A7F3D0, transparent); bottom: -100px; left: 15%; animation-delay: -7s; }
.hero-grid-pattern { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 40px 40px; }

.profile-hero-content { position: relative; z-index: 1; display: flex; align-items: center; gap: 28px; flex-wrap: wrap; color: var(--white); }
.pf-avatar-wrap { position: relative; flex-shrink: 0; }
.pf-avatar-wrap img { width: 110px; height: 110px; border-radius: 50%; border: 4px solid rgba(255,255,255,0.35); box-shadow: 0 10px 30px rgba(0,0,0,0.25); }
.pf-avatar-status { position: absolute; bottom: 6px; right: 6px; width: 18px; height: 18px; background: #34D399; border: 3px solid #064E3B; border-radius: 50%; box-shadow: 0 0 10px rgba(52,211,153,0.8); }
.pf-hero-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
.pf-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.5px; background: rgba(255,255,255,0.16); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.22); }
.profile-hero h1 { font-size: 2rem; margin: 0 0 4px 0; font-family: 'Space Grotesk', sans-serif; font-weight: 700; }
.profile-hero p { margin: 0 0 14px 0; opacity: 0.9; font-size: 0.95rem; }
.pf-mini-stats { display: flex; gap: 10px; flex-wrap: wrap; }
.pf-ms-item { display: flex; align-items: center; gap: 8px; padding: 7px 13px; background: rgba(255,255,255,0.12); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.16); border-radius: 10px; font-size: 0.82rem; }
.pf-ms-item i { color: #A7F3D0; }
.pf-ms-item strong { font-family: 'Space Grotesk', sans-serif; }

.profile-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.profile-main-col { min-width: 0; display: flex; flex-direction: column; gap: 20px; }

.pf-card { overflow: hidden; }
.pf-card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-100); background: var(--gray-50); }
[data-theme="dark"] .pf-card-header { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.08); }
.pf-card-header h3 { margin: 0; font-size: 0.98rem; font-weight: 700; display: flex; align-items: center; gap: 10px; color: var(--dark); }
[data-theme="dark"] .pf-card-header h3 { color: var(--white); }
.pf-card-header h3 i { color: #10B981; }
.pf-card-body { padding: 20px; }
.pf-form-actions { display: flex; justify-content: flex-end; padding-top: 6px; }

.input-icon { position: relative; display: flex; align-items: center; }
.input-icon > i:first-child { position: absolute; left: 13px; color: var(--gray-400); font-size: 0.88rem; pointer-events: none; }
.input-icon input { width: 100%; padding: 11px 40px 11px 38px; border: 1.5px solid var(--gray-200); border-radius: var(--radius); font-size: 0.9rem; font-family: inherit; background: var(--white); color: var(--dark); transition: all var(--transition); }
[data-theme="dark"] .input-icon input { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: var(--white); }
.input-icon input:focus { outline: none; border-color: #10B981; box-shadow: 0 0 0 3px rgba(16,185,129,0.12); }
.input-icon input:disabled { opacity: 0.6; cursor: not-allowed; }
.pf-eye { position: absolute; right: 8px; background: none; border: none; cursor: pointer; color: var(--gray-400); padding: 6px; }
.pf-eye:hover { color: #10B981; }

.pf-strength { height: 5px; background: var(--gray-100); border-radius: 50px; overflow: hidden; margin-top: 8px; }
[data-theme="dark"] .pf-strength { background: rgba(255,255,255,0.08); }
.pf-strength-fill { height: 100%; width: 0; border-radius: 50px; background: #EF4444; transition: all 0.3s; }

.pf-sess-row { display: flex; justify-content: space-between; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--gray-100); font-size: 0.85rem; }
[data-theme="dark"] .pf-sess-row { border-color: rgba(255,255,255,0.06); }
.pf-sess-row:last-child { border-bottom: none; }
.pf-sess-row span { color: var(--gray-500); }
.pf-sess-row strong { color: var(--dark); font-family: 'Space Grotesk', sans-serif; }
[data-theme="dark"] .pf-sess-row strong { color: var(--white); }

.qa-btn.danger { color: #EF4444; }
.qa-btn.danger i:first-child { color: #EF4444; }
.qa-btn.danger:hover { background: rgba(239,68,68,0.08); }

.profile-sidebar { display: flex; flex-direction: column; gap: 20px; position: sticky; top: 90px; }

@media (max-width: 1200px) {
    .profile-layout { grid-template-columns: 1fr; }
    .profile-sidebar { position: static; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
}
@media (max-width: 768px) {
    .profile-page { padding: 16px; }
    .profile-hero { padding: 24px 20px; }
    .profile-hero-content { flex-direction: column; align-items: flex-start; gap: 18px; }
    .profile-hero h1 { font-size: 1.5rem; }
    .form-grid-2 { grid-template-columns: 1fr; }
}
</style>

<script>
(function() {
    'use strict';

    /* Toggle lihat sandi */
    document.querySelectorAll('.pf-eye').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = document.getElementById(this.dataset.target);
            if (!input) return;
            const isPass = input.type === 'password';
            input.type = isPass ? 'text' : 'password';
            this.querySelector('i').className = isPass ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    });

    /* Strength meter */
    const newPass = document.getElementById('pfNewPass');
    const fill = document.getElementById('pfStrengthFill');
    const txt = document.getElementById('pfStrengthText');
    if (newPass && fill && txt) {
        newPass.addEventListener('input', function() {
            const v = this.value;
            let score = 0;
            if (v.length >= 8) score++;
            if (v.length >= 12) score++;
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            const levels = [
                { w: '10%', c: '#EF4444', t: 'Sangat lemah' },
                { w: '25%', c: '#EF4444', t: 'Lemah' },
                { w: '50%', c: '#F59E0B', t: 'Cukup' },
                { w: '75%', c: '#10B981', t: 'Kuat' },
                { w: '90%', c: '#059669', t: 'Sangat kuat' },
                { w: '100%', c: '#047857', t: 'Ultimate!' }
            ];
            const lv = levels[v ? score : 0];
            fill.style.width = lv.w; fill.style.background = lv.c;
            txt.textContent = v ? 'Kekuatan: ' + lv.t : 'Minimal 8 karakter';
            txt.style.color = v ? lv.c : '';
        });
    }

    /* Cocokkan konfirmasi */
    const confPass = document.getElementById('pfConfPass');
    const matchTxt = document.getElementById('pfMatchText');
    if (confPass && matchTxt && newPass) {
        const check = () => {
            if (!confPass.value) { matchTxt.innerHTML = '&nbsp;'; return; }
            if (confPass.value === newPass.value) { matchTxt.textContent = '✓ Sandi cocok'; matchTxt.style.color = '#10B981'; }
            else { matchTxt.textContent = '✗ Sandi tidak cocok'; matchTxt.style.color = '#EF4444'; }
        };
        confPass.addEventListener('input', check);
        newPass.addEventListener('input', check);
    }

    /* Auto dismiss alert */
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'all 0.4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 400);
        });
    }, 5000);

    console.log('%c👤 Admin Profile v4.0 Loaded', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c   Features: Multi-table detect, Multi-hash verify, Strength meter', 'color:#64748b;font-size:11px');
})();
</script>

<?php include 'includes/footer.php'; ?>