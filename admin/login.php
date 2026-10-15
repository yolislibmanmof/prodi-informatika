<?php
require_once __DIR__ . '/../includes/init_security.php';
require_once __DIR__ . '/../includes/Auth.php';

$error = '';
$success = '';
$submittedUsername = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
$rememberChecked = !empty($_COOKIE['ifpro_remember']) || (($_POST['remember'] ?? '') === '1');

// Hapus cookie lama yang hanya menyimpan username, bukan token autentikasi.
if (isset($_COOKIE['remember_admin'])) {
    setcookie('remember_admin', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE['remember_admin']);
}

// Gunakan mekanisme remember-me yang sama dengan area admin.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        Auth::bootRemember();
    } catch (Throwable $e) {
        error_log('Admin remember-me bootstrap failed: ' . $e->getMessage());
    }

    if (Auth::check()) {
        header('Location: dashboard.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!is_string($csrfToken) || !Security::csrfVerify($csrfToken)) {
        if (function_exists('sec_log')) {
            sec_log('csrf_fail', 'warning', 'admin login');
        }
        $error = 'Sesi formulir tidak valid atau sudah kedaluwarsa. Muat ulang halaman dan coba lagi.';
    } elseif (!Security::rateLimit('admin_login', 5, 900)) {
        $error = 'Terlalu banyak percobaan login. Silakan tunggu 15 menit sebelum mencoba lagi.';
    } else {
        $username = $submittedUsername;
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $remember = (($_POST['remember'] ?? '') === '1');

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } elseif (strlen($username) > 50 || strlen($password) > 4096) {
            $error = 'Data login tidak valid.';
        } else {
            try {
                [$authenticated, $authMessage] = Auth::attempt($username, $password, $remember);

                if ($authenticated) {
                    unset($_SESSION['rl']['admin_login']);
                    $_SESSION['last_activity'] = time();
                    $success = 'ok';
                } else {
                    // Hindari membocorkan apakah username ada, akun terkunci, atau dinonaktifkan.
                    $error = 'Username atau password salah atau akun tidak dapat digunakan.';
                }
            } catch (Throwable $e) {
                error_log('Admin login processing failed: ' . $e->getMessage());
                $error = 'Login belum dapat diproses. Silakan coba lagi atau hubungi administrator.';
            }
        }
    }
}

$attemptTimes = $_SESSION['rl']['admin_login'] ?? [];
if (!is_array($attemptTimes)) {
    $attemptTimes = [];
}
$attemptTimes = array_values(array_filter(
    $attemptTimes,
    static fn($timestamp) => is_numeric($timestamp) && (time() - (int)$timestamp) < 900
));
if (isset($_SESSION['rl']) && is_array($_SESSION['rl'])) {
    $_SESSION['rl']['admin_login'] = $attemptTimes;
}
$attempts_left = max(0, 5 - count($attemptTimes));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Login Admin — Informatika Pro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* ============================================================
   ULTIMATE LOGIN v4.0 - Self-Contained & Premium
============================================================ */
:root {
    --primary: #10B981;
    --primary-dark: #047857;
    --accent: #8B5CF6;
    --bg-dark: #0f172a;
    --glass-bg: rgba(255, 255, 255, 0.03);
    --glass-border: rgba(255, 255, 255, 0.08);
    --text-primary: #f8fafc;
    --text-secondary: #94a3b8;
}

* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { height: 100%; }
body {
    font-family: 'Inter', sans-serif;
    background: var(--bg-dark);
    color: var(--text-primary);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ---------- Background Effects ---------- */
#particleCanvas {
    position: fixed; inset: 0; z-index: 1; pointer-events: none;
}
.bg-mesh {
    position: fixed; inset: 0; z-index: 0;
    background: 
        radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.15) 0, transparent 50%),
        radial-gradient(at 100% 0%, rgba(139, 92, 246, 0.15) 0, transparent 50%),
        radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0, transparent 50%),
        radial-gradient(at 0% 100%, rgba(139, 92, 246, 0.1) 0, transparent 50%),
        linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
}
.orb {
    position: fixed; border-radius: 50%; filter: blur(80px); opacity: 0.4;
    animation: floatOrb 20s ease-in-out infinite; z-index: 0; pointer-events: none;
}
.orb-1 { width: 400px; height: 400px; background: #10B981; top: -100px; left: -100px; }
.orb-2 { width: 350px; height: 350px; background: #8B5CF6; bottom: -100px; right: -100px; animation-delay: -7s; }
.orb-3 { width: 250px; height: 250px; background: #34D399; top: 50%; left: 60%; animation-delay: -14s; }

@keyframes floatOrb {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -40px) scale(1.1); }
    66% { transform: translate(-20px, 30px) scale(0.9); }
}

/* ---------- Layout ---------- */
.wrap {
    position: relative; z-index: 10;
    width: 100%; max-width: 480px;
    padding: 20px;
    animation: fadeInUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(40px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.card {
    background: var(--glass-bg);
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    border: 1px solid var(--glass-border);
    border-radius: 24px;
    padding: 40px 36px;
    position: relative; overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    transition: transform 0.1s ease-out, box-shadow 0.3s;
}
.card::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
    animation: shine 6s ease-in-out infinite; pointer-events: none;
}
@keyframes shine { 0%, 80% { left: -100%; } 100% { left: 200%; } }

.card.shake { animation: shakeX 0.5s cubic-bezier(.36,.07,.19,.97) both; }
@keyframes shakeX {
    10%, 90% { transform: translateX(-2px); }
    20%, 80% { transform: translateX(4px); }
    30%, 50%, 70% { transform: translateX(-6px); }
    40%, 60% { transform: translateX(6px); }
}

/* ---------- Logo ---------- */
.logo {
    display: flex; align-items: center; gap: 16px; justify-content: center; margin-bottom: 28px;
}
.logo-icon {
    position: relative; width: 52px; height: 52px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border-radius: 16px; display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: #fff; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
}
.logo-icon::after {
    content: ''; position: absolute; inset: -4px; border-radius: 18px;
    border: 2px solid rgba(16, 185, 129, 0.6); animation: ringPulse 2.5s ease-out infinite;
}
@keyframes ringPulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.4); opacity: 0; }
}
.logo-text h1 {
    font-family: 'Space Grotesk', sans-serif; font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em;
}
.logo-text h1 span {
    background: linear-gradient(135deg, var(--primary), #34D399);
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
.logo-text small {
    display: block; font-size: 0.7rem; letter-spacing: 2px; text-transform: uppercase;
    color: var(--text-secondary); margin-top: 2px;
}

.welcome { text-align: center; margin-bottom: 32px; }
.welcome h2 { font-size: 1.4rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); }
.welcome p { font-size: 0.9rem; color: var(--text-secondary); line-height: 1.5; }

/* ---------- Alert ---------- */
.alert {
    display: flex; align-items: flex-start; gap: 12px;
    background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);
    color: #FCA5A5; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px;
    animation: slideDown 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.alert-icon {
    background: rgba(239, 68, 68, 0.2); border-radius: 8px; padding: 8px;
    color: #F87171; flex-shrink: 0;
}
.alert-content { flex: 1; font-size: 0.85rem; line-height: 1.4; }
.alert-content strong { display: block; margin-bottom: 2px; color: #FCA5A5; }
.alert-close {
    background: none; border: none; color: inherit; cursor: pointer;
    opacity: 0.6; padding: 4px; transition: opacity 0.2s, transform 0.2s;
}
.alert-close:hover { opacity: 1; transform: scale(1.1); }

.attempts {
    display: flex; gap: 8px; align-items: center; font-size: 0.8rem;
    color: #FCD34D; background: rgba(251, 191, 36, 0.1);
    border: 1px solid rgba(251, 191, 36, 0.2); padding: 10px 14px;
    border-radius: 10px; margin-bottom: 20px;
}

/* ---------- Floating Inputs ---------- */
.fgroup { position: relative; margin-bottom: 24px; }
.ibox {
    display: flex; align-items: center;
    background: rgba(255, 255, 255, 0.03);
    border: 1.5px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px; padding: 0 16px;
    transition: border-color 0.3s, background 0.3s, box-shadow 0.3s;
}
.ibox:focus-within {
    border-color: var(--primary);
    background: rgba(16, 185, 129, 0.05);
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}
.lead { color: var(--text-secondary); margin-right: 12px; transition: color 0.3s; font-size: 1rem; }
.ibox:focus-within .lead { color: var(--primary); }

.fgroup input {
    flex: 1; background: none; border: none; outline: none;
    color: var(--text-primary); font-size: 0.95rem; padding: 26px 0 10px;
    font-family: inherit; width: 100%;
}
.fgroup label {
    position: absolute; left: 44px; top: 18px;
    color: var(--text-secondary); font-size: 0.9rem; pointer-events: none;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.fgroup label.up {
    top: 8px; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px;
    color: var(--primary); text-transform: uppercase;
}
.underline {
    position: absolute; left: 50%; bottom: 0; height: 2px; width: 0;
    transform: translateX(-50%); background: linear-gradient(90deg, var(--primary), #34D399);
    border-radius: 2px; transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.ibox:focus-within ~ .underline { width: 92%; }

.eye {
    background: none; border: none; color: var(--text-secondary); cursor: pointer;
    padding: 8px; margin-left: 4px; transition: transform 0.3s, color 0.3s;
}
.eye:hover { color: var(--primary); }
.eye.spin { animation: spinIcon 0.4s ease-in-out; }
@keyframes spinIcon { 0% { transform: rotate(0deg) scale(1); } 50% { transform: rotate(180deg) scale(0.8); } 100% { transform: rotate(360deg) scale(1); } }

.hint { font-size: 0.75rem; color: var(--text-secondary); margin-top: 8px; padding-left: 4px; display: flex; align-items: center; gap: 6px; }

/* ---------- Options ---------- */
.opts { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
.cb {
    display: flex; align-items: center; gap: 10px; cursor: pointer;
    font-size: 0.85rem; color: var(--text-secondary); user-select: none;
}
.cb input { position: absolute; opacity: 0; width: 0; height: 0; }
.cb .box {
    width: 18px; height: 18px; flex-shrink: 0; border-radius: 5px;
    border: 1.5px solid rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.03);
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.cb .box::after {
    content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
    font-size: 0.6rem; color: #fff; transform: scale(0); transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.cb input:checked + .box {
    background: var(--primary); border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}
.cb input:checked + .box::after { transform: scale(1); }

/* ---------- Button ---------- */
.btn {
    width: 100%; padding: 16px; border: none; border-radius: 14px; cursor: pointer;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff; font-size: 1rem; font-weight: 600; position: relative; overflow: hidden;
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
    transition: transform 0.25s, box-shadow 0.25s, opacity 0.25s;
    font-family: inherit;
}
.btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(16, 185, 129, 0.45);
}
.btn:active:not(:disabled) { transform: translateY(0); }
.btn:disabled { cursor: wait; opacity: 0.85; }

.btn .txt, .btn .ldg {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn .ldg {
    position: absolute; inset: 0; opacity: 0; transform: translateY(12px);
}
.btn.loading .txt { opacity: 0; transform: translateY(-12px); }
.btn.loading .ldg { opacity: 1; transform: translateY(0); }

.spin-svg { width: 20px; height: 20px; animation: rot 1.2s linear infinite; }
.spin-svg circle {
    fill: none; stroke: #fff; stroke-width: 4; stroke-linecap: round;
    stroke-dasharray: 90; animation: dash 1.3s ease-in-out infinite;
}
@keyframes rot { to { transform: rotate(360deg); } }
@keyframes dash {
    0% { stroke-dasharray: 1, 150; stroke-dashoffset: 0; }
    50% { stroke-dasharray: 90, 150; stroke-dashoffset: -35; }
    100% { stroke-dasharray: 90, 150; stroke-dashoffset: -124; }
}

.ripple {
    position: absolute; border-radius: 50%; background: rgba(255,255,255,0.3);
    transform: translate(-50%, -50%) scale(0); pointer-events: none;
}
.ripple.go { animation: rip 0.6s ease-out forwards; }
@keyframes rip {
    to { transform: translate(-50%, -50%) scale(1); opacity: 0; width: 500px; height: 500px; }
}

/* ---------- Success Overlay ---------- */
.overlay {
    position: absolute; inset: 0; z-index: 50; border-radius: 24px;
    background: linear-gradient(135deg, rgba(16,185,129,0.98), rgba(4,120,87,0.98));
    backdrop-filter: blur(10px);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    opacity: 0; visibility: hidden; transition: opacity 0.5s, visibility 0.5s;
}
.overlay.show { opacity: 1; visibility: visible; }
.overlay svg { width: 80px; height: 80px; margin-bottom: 20px; }
.overlay .cir {
    fill: none; stroke: #fff; stroke-width: 2; stroke-dasharray: 166;
    stroke-dashoffset: 166; animation: draw 0.6s cubic-bezier(0.65, 0, 0.45, 1) 0.1s forwards;
}
.overlay .chk {
    fill: none; stroke: #fff; stroke-width: 3; stroke-linecap: round;
    stroke-dasharray: 48; stroke-dashoffset: 48; animation: draw 0.35s cubic-bezier(0.65, 0, 0.45, 1) 0.7s forwards;
}
@keyframes draw { to { stroke-dashoffset: 0; } }
.overlay h2 {
    font-size: 1.4rem; font-weight: 700; opacity: 0; transform: translateY(16px);
    animation: upFade 0.5s 0.9s forwards;
}
.overlay p {
    font-size: 0.9rem; color: rgba(255,255,255,0.85); margin-top: 8px;
    opacity: 0; animation: upFade 0.5s 1.1s forwards;
}
@keyframes upFade { to { opacity: 1; transform: translateY(0); } }

/* ---------- Footer ---------- */
.sep {
    position: relative; text-align: center; margin: 28px 0 16px;
    font-size: 0.7rem; letter-spacing: 2px; text-transform: uppercase;
    color: var(--text-secondary);
}
.sep::before, .sep::after {
    content: ''; position: absolute; top: 50%; width: 35%; height: 1px;
    background: rgba(255, 255, 255, 0.1);
}
.sep::before { left: 0; }
.sep::after { right: 0; }

.demo {
    display: flex; align-items: center; gap: 14px; padding: 14px 16px; cursor: pointer;
    background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.15);
    border-radius: 12px; transition: all 0.3s;
}
.demo:hover {
    background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);
    transform: translateX(4px);
}
.demo i { color: var(--primary); font-size: 1.2rem; }
.demo-info b { display: block; font-size: 0.85rem; color: var(--text-primary); margin-bottom: 2px; }
.demo-info code { font-size: 0.75rem; color: var(--text-secondary); font-family: 'Space Grotesk', monospace; }

.secure {
    display: flex; gap: 8px; align-items: center; justify-content: center;
    font-size: 0.75rem; color: var(--text-secondary); margin-top: 20px;
}
.secure i { color: var(--primary); }

.copy {
    margin-top: 24px; font-size: 0.75rem; color: var(--text-secondary);
    text-align: center; opacity: 0.6;
}

/* ---------- Responsive ---------- */
@media (max-width: 520px) {
    .wrap { padding: 16px; }
    .card { padding: 32px 24px; border-radius: 20px; }
    .logo { flex-direction: column; text-align: center; gap: 12px; }
    .orb { display: none; } 
    .opts { flex-direction: column; align-items: flex-start; }
}
@media (prefers-reduced-motion: reduce) {
    * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}
input:-webkit-autofill,
input:-webkit-autofill:hover, 
input:-webkit-autofill:focus {
    -webkit-text-fill-color: var(--text-primary);
    -webkit-box-shadow: 0 0 0 1000px rgba(16, 185, 129, 0.1) inset;
    transition: background-color 9999s ease-in-out 0s;
}
</style>
</head>
<body>

<canvas id="particleCanvas"></canvas>
<div class="bg-mesh"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="wrap">
    <div class="card" id="card">

        <!-- Success overlay -->
        <div class="overlay" id="overlay">
            <svg viewBox="0 0 52 52">
                <circle class="cir" cx="26" cy="26" r="25"/>
                <path class="chk" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
            <h2>Login Berhasil!</h2>
            <p>Mengalihkan ke dashboard...</p>
        </div>

        <div class="logo">
            <div class="logo-icon"><i class="fas fa-code"></i></div>
            <div class="logo-text">
                <h1>Informatika<span>Pro</span></h1>
                <small>Admin Control Panel</small>
            </div>
        </div>

        <div class="welcome">
            <h2>Selamat Datang Kembali</h2>
            <p>Silakan masuk untuk mengelola sistem informasi program studi</p>
        </div>

        <?php if ($error): ?>
        <div class="alert" id="alertBox">
            <div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="alert-content">
                <strong>Gagal Masuk</strong>
                <?= clean($error) ?>
            </div>
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Tutup"><i class="fas fa-times"></i></button>
        </div>
        <?php if ($attempts_left > 0 && $attempts_left < 5): ?>
        <div class="attempts">
            <i class="fas fa-shield-alt"></i> 
            Sisa percobaan: <b><?= $attempts_left ?></b>
        </div>
        <?php endif; endif; ?>

        <form method="POST" id="form" novalidate autocomplete="off">
            <?php Security::csrfField(); ?>
            <div class="fgroup">
                <div class="ibox">
                    <i class="fas fa-user lead"></i>
                    <input type="text" id="username" name="username" autocomplete="username"
                           value="<?= clean($submittedUsername) ?>" required>
                    <label for="username">Username</label>
                </div>
                <div class="underline"></div>
            </div>

            <div class="fgroup">
                <div class="ibox">
                    <i class="fas fa-lock lead"></i>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                    <label for="password">Password</label>
                    <button type="button" class="eye" id="eye" aria-label="Lihat password"><i class="fas fa-eye" id="eyeIcon"></i></button>
                </div>
                <div class="underline"></div>
                <div class="hint"><i class="fas fa-info-circle"></i> Minimal 6 karakter</div>
            </div>

            <div class="opts">
                <label class="cb">
                    <input type="checkbox" name="remember" value="1" <?= $rememberChecked ? 'checked' : '' ?>>
                    <span class="box"></span>
                    <span>Ingat saya selama 30 hari</span>
                </label>
            </div>

            <button type="submit" class="btn" id="btn">
                <span class="txt"><i class="fas fa-sign-in-alt"></i> Masuk Sekarang</span>
                <span class="ldg">
                    <svg class="spin-svg" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20"/></svg>
                    Memverifikasi...
                </span>
            </button>
        </form>

        
        <div class="secure">
            <i class="fas fa-shield-alt"></i> 
            <span>Koneksi terenkripsi &amp; dilindungi rate-limiting</span>
        </div>
    </div>

    <p class="copy">&copy; <?= date('Y') ?> Informatika Pro. All rights reserved.</p>
</div>

<script>
/* ============ 1. PARTICLE NETWORK ============ */
const cv = document.getElementById('particleCanvas'), cx = cv.getContext('2d');
let pts = [];
const sizeCanvas = () => { cv.width = innerWidth; cv.height = innerHeight; };
class P {
    constructor(init) { this.reset(init); }
    reset(init) {
        this.x = Math.random() * cv.width;
        this.y = init ? Math.random() * cv.height : cv.height + 10;
        this.r = Math.random() * 2 + 0.5;
        this.vy = Math.random() * 0.4 + 0.1;
        this.vx = (Math.random() - 0.5) * 0.3;
        this.o = Math.random() * 0.5 + 0.1;
    }
    step() {
        this.y -= this.vy; this.x += this.vx;
        if (this.y < -12) this.reset(false);
    }
    draw() {
        cx.beginPath();
        cx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
        cx.fillStyle = `rgba(52, 211, 153, ${this.o})`;
        cx.shadowBlur = 10; cx.shadowColor = 'rgba(16, 185, 129, 0.8)';
        cx.fill(); cx.shadowBlur = 0;
    }
}
const build = () => { pts = Array.from({length: Math.min(60, innerWidth / 20)}, () => new P(true)); };
const loop = () => {
    cx.clearRect(0, 0, cv.width, cv.height);
    pts.forEach(p => { p.step(); p.draw(); });
    for (let i = 0; i < pts.length; i++) {
        for (let j = i + 1; j < pts.length; j++) {
            const d = Math.hypot(pts[i].x - pts[j].x, pts[i].y - pts[j].y);
            if (d < 120) {
                cx.strokeStyle = `rgba(16, 185, 129, ${(1 - d / 120) * 0.15})`;
                cx.lineWidth = 0.5;
                cx.beginPath(); cx.moveTo(pts[i].x, pts[i].y); cx.lineTo(pts[j].x, pts[j].y); cx.stroke();
            }
        }
    }
    requestAnimationFrame(loop);
};
sizeCanvas(); build(); loop();
addEventListener('resize', () => { sizeCanvas(); build(); });

/* ============ 2. FLOATING LABELS ============ */
document.querySelectorAll('.fgroup input').forEach(inp => {
    const lb = inp.closest('.fgroup').querySelector('label');
    const sync = () => lb.classList.toggle('up', inp.value !== '' || document.activeElement === inp);
    inp.addEventListener('input', sync);
    inp.addEventListener('focus', sync);
    inp.addEventListener('blur', sync);
    sync();
});

/* ============ 3. TOGGLE PASSWORD ============ */
const eye = document.getElementById('eye'),
      pwd = document.getElementById('password'),
      eyeIcon = document.getElementById('eyeIcon');
eye.addEventListener('click', () => {
    const show = pwd.type === 'password';
    pwd.type = show ? 'text' : 'password';
    eyeIcon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
    eye.classList.add('spin');
    setTimeout(() => eye.classList.remove('spin'), 400);
    pwd.focus();
});

/* ============ 4. SUBMIT: VALIDASI + LOADING ============ */
const form = document.getElementById('form'),
      btn  = document.getElementById('btn'),
      card = document.getElementById('card');
form.addEventListener('submit', e => {
    e.preventDefault();
    const u = document.getElementById('username').value.trim(),
          p = pwd.value;
    if (!u || !p) {
        card.classList.remove('shake'); void card.offsetWidth;
        card.classList.add('shake');
        (!u ? document.getElementById('username') : pwd).focus();
        return;
    }
    btn.classList.add('loading');
    btn.disabled = true;
    setTimeout(() => form.submit(), 800);
});

/* ============ 5. RIPPLE EFFECT ============ */
btn.addEventListener('pointerdown', e => {
    const r = document.createElement('span');
    r.className = 'ripple';
    const b = btn.getBoundingClientRect();
    r.style.left = (e.clientX - b.left) + 'px';
    r.style.top  = (e.clientY - b.top) + 'px';
    btn.appendChild(r);
    requestAnimationFrame(() => r.classList.add('go'));
    setTimeout(() => r.remove(), 650);
});

/* ============ 6. 3D TILT CARD (Desktop Only) ============ */
if (matchMedia('(hover: hover) and (pointer: fine)').matches) {
    card.addEventListener('mousemove', e => {
        const r = card.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width - 0.5;
        const y = (e.clientY - r.top) / r.height - 0.5;
        card.style.transform = `perspective(1000px) rotateY(${x * 6}deg) rotateX(${-y * 6}deg) scale(1.01)`;
    });
    card.addEventListener('mouseleave', () => {
        card.style.transform = 'perspective(1000px) rotateY(0) rotateX(0) scale(1)';
    });
}

/* ============ 8. SUCCESS → REDIRECT ============ */
<?php if ($success === 'ok'): ?>
setTimeout(() => {
    document.getElementById('overlay').classList.add('show');
    setTimeout(() => window.location.href = 'dashboard.php', 1500);
}, 300);
<?php endif; ?>
</script>
</body>
</html>