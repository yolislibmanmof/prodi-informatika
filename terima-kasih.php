<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/functions.php';

$receipt = $_SESSION['contact_receipt'] ?? null;
$isBotTrap = isset($_GET['bot']);
unset($_SESSION['contact_receipt']);

$pageTitle = 'Pesan Terkirim';
$pageDesc = 'Terima kasih telah menghubungi kami';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Informatika Pro</title>
    <meta name="description" content="<?= $pageDesc ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --tk-primary: #10B981;
            --tk-dark: #0F172A;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--tk-dark) 0%, #047857 100%);
            min-height: 100vh; color: #fff;
            display: flex; align-items: center; justify-content: center;
            padding: 20px; position: relative; overflow-x: hidden;
        }
        body::before, body::after {
            content: ''; position: fixed; border-radius: 50%;
            filter: blur(80px); opacity: .4; pointer-events: none;
        }
        body::before {
            width: 500px; height: 500px; background: var(--tk-primary);
            top: -150px; left: -150px; animation: tkFloat 20s ease-in-out infinite;
        }
        body::after {
            width: 400px; height: 400px; background: #34D399;
            bottom: -100px; right: -100px; animation: tkFloat 25s ease-in-out infinite reverse;
        }
        @keyframes tkFloat {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(50px, -50px); }
        }
        .tk-card {
            position: relative; z-index: 10;
            background: rgba(255,255,255,.08);
            backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 28px; padding: 50px 40px;
            max-width: 560px; width: 100%; text-align: center;
            box-shadow: 0 30px 70px rgba(0,0,0,.3);
            animation: tkCardIn .8s cubic-bezier(.34,1.56,.64,1);
        }
        @keyframes tkCardIn {
            from { opacity: 0; transform: translateY(40px) scale(.9); }
            to { opacity: 1; transform: none; }
        }
        .tk-success-anim { margin-bottom: 24px; }
        .tk-checkmark { width: 100px; height: 100px; }
        .tk-circle {
            stroke-dasharray: 166; stroke-dashoffset: 166;
            stroke-width: 2; stroke: #34D399; fill: none;
            animation: tkStroke .7s cubic-bezier(.65,0,.45,1) forwards;
        }
        .tk-check {
            stroke-dasharray: 48; stroke-dashoffset: 48;
            stroke-width: 3; stroke: #34D399; stroke-linecap: round;
            fill: none; animation: tkStroke .4s cubic-bezier(.65,0,.45,1) .7s forwards;
        }
        @keyframes tkStroke { to { stroke-dashoffset: 0; } }
        h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem; margin-bottom: 12px;
            background: linear-gradient(135deg, #34D399, var(--tk-primary));
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: tkFadeUp .8s .3s both;
        }
        .tk-subtitle {
            color: rgba(255,255,255,.85); font-size: 1rem;
            margin-bottom: 28px; line-height: 1.6;
            animation: tkFadeUp .8s .5s both;
        }
        @keyframes tkFadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: none; }
        }
        .tk-ticket {
            background: linear-gradient(135deg, rgba(16,185,129,.15), rgba(52,211,153,.08));
            border: 1.5px dashed rgba(52,211,153,.5);
            border-radius: 16px; padding: 24px; margin-bottom: 28px;
            text-align: left; position: relative;
            animation: tkFadeUp .8s .7s both;
        }
        .tk-ticket::before, .tk-ticket::after {
            content: ''; position: absolute; width: 30px; height: 30px;
            background: var(--tk-dark); border-radius: 50%;
            top: 50%; transform: translateY(-50%);
        }
        .tk-ticket::before { left: -15px; }
        .tk-ticket::after { right: -15px; }
        .tk-ticket-label {
            font-size: .72rem; color: rgba(255,255,255,.6);
            text-transform: uppercase; letter-spacing: 1.5px;
            margin-bottom: 8px; text-align: center;
        }
        .tk-ticket-number {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 1.4rem; font-weight: 700;
            color: #34D399; text-align: center;
            letter-spacing: 1.5px; margin-bottom: 16px;
            padding-bottom: 16px; border-bottom: 1px dashed rgba(255,255,255,.1);
        }
        .tk-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .tk-info-item {
            padding: 10px 12px; background: rgba(255,255,255,.04);
            border-radius: 8px;
        }
        .tk-info-label {
            font-size: .7rem; color: rgba(255,255,255,.6);
            text-transform: uppercase; letter-spacing: .5px;
            margin-bottom: 2px;
        }
        .tk-info-value { font-size: .88rem; font-weight: 500; word-break: break-word; }
        .tk-steps {
            background: rgba(255,255,255,.04); border-radius: 14px;
            padding: 20px; margin-bottom: 28px; text-align: left;
            animation: tkFadeUp .8s .9s both;
        }
        .tk-steps h3 {
            font-size: .95rem; margin-bottom: 14px;
            display: flex; align-items: center; gap: 8px;
        }
        .tk-steps h3 i { color: #F59E0B; }
        .tk-step {
            display: flex; gap: 12px; padding: 8px 0;
            font-size: .85rem; color: rgba(255,255,255,.85);
        }
        .tk-step-num {
            width: 22px; height: 22px; border-radius: 50%;
            background: rgba(52,211,153,.2); color: #34D399;
            display: flex; align-items: center; justify-content: center;
            font-size: .72rem; font-weight: 700; flex-shrink: 0;
        }
        .tk-actions {
            display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;
            animation: tkFadeUp .8s 1.1s both;
        }
        .tk-btn {
            padding: 12px 24px; border-radius: 50px; border: none;
            font-family: inherit; font-weight: 600; font-size: .9rem;
            cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all .3s cubic-bezier(.4,0,.2,1);
        }
        .tk-btn-primary {
            background: linear-gradient(135deg, var(--tk-primary), #047857);
            color: #fff; box-shadow: 0 8px 20px rgba(16,185,129,.35);
        }
        .tk-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(16,185,129,.5);
        }
        .tk-btn-ghost {
            background: rgba(255,255,255,.08); color: #fff;
            border: 1.5px solid rgba(255,255,255,.2);
        }
        .tk-btn-ghost:hover {
            background: rgba(255,255,255,.15); transform: translateY(-2px);
        }
        .tk-countdown {
            margin-top: 24px; font-size: .8rem;
            color: rgba(255,255,255,.6);
            animation: tkFadeUp .8s 1.3s both;
        }
        .tk-countdown strong { color: #34D399; }
        #tkConfetti {
            position: fixed; inset: 0; z-index: 1;
            pointer-events: none;
        }
        @media (max-width: 600px) {
            .tk-card { padding: 36px 24px; }
            h1 { font-size: 1.6rem; }
            .tk-info-grid { grid-template-columns: 1fr; }
            .tk-actions { flex-direction: column; }
            .tk-btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <canvas id="tkConfetti"></canvas>
    <div class="tk-card">
        <?php if ($isBotTrap): ?>
            <div class="tk-success-anim">
                <svg class="tk-checkmark" viewBox="0 0 52 52">
                    <circle class="tk-circle" cx="26" cy="26" r="25"/>
                    <path class="tk-check" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h1>Terima Kasih!</h1>
            <p class="tk-subtitle">Pesan Anda telah kami terima.</p>
        <?php elseif ($receipt): ?>
            <div class="tk-success-anim">
                <svg class="tk-checkmark" viewBox="0 0 52 52">
                    <circle class="tk-circle" cx="26" cy="26" r="25"/>
                    <path class="tk-check" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h1>Pesan Terkirim! 🎉</h1>
            <p class="tk-subtitle">
                Terima kasih <strong><?= htmlspecialchars($receipt['name']) ?></strong>! Tim kami akan merespons
                melalui email <strong><?= htmlspecialchars($receipt['email']) ?></strong> dalam 1x24 jam.
            </p>
            <div class="tk-ticket">
                <div class="tk-ticket-label">Nomor Tiket</div>
                <div class="tk-ticket-number"><?= htmlspecialchars($receipt['ticket']) ?></div>
                <div class="tk-info-grid">
                    <div class="tk-info-item">
                        <div class="tk-info-label">Nama</div>
                        <div class="tk-info-value"><?= htmlspecialchars($receipt['name']) ?></div>
                    </div>
                    <div class="tk-info-item">
                        <div class="tk-info-label">Email</div>
                        <div class="tk-info-value"><?= htmlspecialchars($receipt['email']) ?></div>
                    </div>
                    <div class="tk-info-item">
                        <div class="tk-info-label">Tanggal</div>
                        <div class="tk-info-value"><?= htmlspecialchars($receipt['time']) ?></div>
                    </div>
                    <div class="tk-info-item">
                        <div class="tk-info-label">Status</div>
                        <div class="tk-info-value" style="color:#34D399">
                            <i class="fas fa-check-circle"></i> Diterima
                        </div>
                    </div>
                </div>
            </div>
            <div class="tk-steps">
                <h3><i class="fas fa-route"></i> Apa yang terjadi selanjutnya?</h3>
                <div class="tk-step">
                    <div class="tk-step-num">1</div>
                    <span>Tim admisi memverifikasi pesan Anda</span>
                </div>
                <div class="tk-step">
                    <div class="tk-step-num">2</div>
                    <span>Balasan akan dikirim ke email dalam 1x24 jam</span>
                </div>
                <div class="tk-step">
                    <div class="tk-step-num">3</div>
                    <span>Simpan nomor tiket sebagai referensi</span>
                </div>
            </div>
            <div class="tk-actions">
                <a href="index.php" class="tk-btn tk-btn-primary">
                    <i class="fas fa-home"></i> Kembali ke Beranda
                </a>
                <button class="tk-btn tk-btn-ghost" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak Tiket
                </button>
            </div>
            <div class="tk-countdown">
                Auto redirect ke beranda dalam <strong id="tkTimer">10</strong> detik
            </div>
        <?php else: ?>
            <div class="tk-success-anim">
                <i class="fas fa-exclamation-triangle" style="font-size:4rem;color:#F59E0B"></i>
            </div>
            <h1 style="color:#F59E0B">Akses Tidak Valid</h1>
            <p class="tk-subtitle">Halaman ini tidak dapat diakses langsung.</p>
            <div class="tk-actions" style="margin-top:20px">
                <a href="index.php#contact" class="tk-btn tk-btn-primary">
                    <i class="fas fa-envelope"></i> Kirim Pesan Baru
                </a>
            </div>
        <?php endif; ?>
    </div>
    <script>
        <?php if ($receipt && !$isBotTrap): ?>
        (function() {
            let seconds = 10;
            const timer = document.getElementById('tkTimer');
            const interval = setInterval(() => {
                seconds--;
                if (timer) timer.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(interval);
                    window.location.href = 'index.php';
                }
            }, 1000);
        })();
        <?php endif; ?>
        <?php if ($receipt && !$isBotTrap): ?>
        (function() {
            const canvas = document.getElementById('tkConfetti');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            const colors = ['#10B981', '#34D399', '#F59E0B', '#3B82F6', '#8B5CF6', '#FFFFFF'];
            const particles = [];
            for (let i = 0; i < 120; i++) {
                particles.push({
                    x: canvas.width / 2 + (Math.random() - .5) * 200,
                    y: canvas.height / 2,
                    w: Math.random() * 10 + 4,
                    h: Math.random() * 5 + 2,
                    color: colors[Math.floor(Math.random() * colors.length)],
                    vx: (Math.random() - .5) * 12,
                    vy: -Math.random() * 14 - 4,
                    rot: Math.random() * Math.PI * 2,
                    vr: (Math.random() - .5) * .3,
                    gravity: .35,
                    life: 0
                });
            }
            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                let alive = false;
                particles.forEach(p => {
                    p.vy += p.gravity;
                    p.x += p.vx;
                    p.y += p.vy;
                    p.rot += p.vr;
                    p.life++;
                    if (p.y < canvas.height + 50 && p.life < 300) {
                        alive = true;
                        ctx.save();
                        ctx.translate(p.x, p.y);
                        ctx.rotate(p.rot);
                        ctx.fillStyle = p.color;
                        ctx.globalAlpha = Math.max(0, 1 - p.life / 300);
                        ctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);
                        ctx.restore();
                    }
                });
                if (alive) requestAnimationFrame(animate);
                else canvas.style.display = 'none';
            }
            animate();
            window.addEventListener('resize', () => {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }, { once: true });
        })();
        <?php endif; ?>
    </script>
</body>
</html>