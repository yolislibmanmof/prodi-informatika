<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Halaman Tidak Ditemukan';
?>

<div class="er-hero">
    <div class="container">
        <div class="er-content">
            <div class="er-visual">
                <div class="er-code">404</div>
                <div class="er-float-icon er-icon-1"><i class="fas fa-code"></i></div>
                <div class="er-float-icon er-icon-2"><i class="fas fa-bug"></i></div>
                <div class="er-float-icon er-icon-3"><i class="fas fa-search"></i></div>
            </div>
            <h1 class="er-title">Oops! Halaman Tidak Ditemukan</h1>
            <p class="er-subtitle">Sepertinya Anda tersesat di dunia digital. Halaman yang Anda cari mungkin telah dipindahkan, dihapus, atau tidak pernah ada.</p>
            
            <div class="er-actions">
                <a href="<?= SITE_URL ?>/" class="er-btn er-btn-primary">
                    <i class="fas fa-home"></i> Kembali ke Beranda
                </a>
                <a href="<?= SITE_URL ?>/search.php" class="er-btn er-btn-ghost">
                    <i class="fas fa-search"></i> Cari Halaman
                </a>
            </div>

            <div class="er-suggestions">
                <p>Mungkin Anda mencari:</p>
                <div class="er-suggestion-links">
                    <a href="<?= SITE_URL ?>/berita.php"><i class="fas fa-newspaper"></i> Berita Terbaru</a>
                    <a href="<?= SITE_URL ?>/kurikulum.php"><i class="fas fa-book-open"></i> Kurikulum</a>
                    <a href="<?= SITE_URL ?>/daftar.php"><i class="fas fa-user-plus"></i> Pendaftaran</a>
                    <a href="<?= SITE_URL ?>/faq.php"><i class="fas fa-question-circle"></i> FAQ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.er-hero {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    padding: 120px 20px 80px; background: linear-gradient(135deg, var(--black-primary) 0%, var(--green-dark) 100%);
    color: #fff; position: relative; overflow: hidden;
}
.er-hero::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(circle at 30% 50%, rgba(16,185,129,.2) 0%, transparent 50%);
}
.er-content {
    position: relative; z-index: 1; text-align: center; max-width: 700px;
    animation: erFadeIn .8s cubic-bezier(.4,0,.2,1);
}
@keyframes erFadeIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: none; } }

.er-visual { position: relative; margin-bottom: 40px; }
.er-code {
    font-size: clamp(6rem, 15vw, 12rem); font-weight: 900;
    font-family: 'Space Grotesk', sans-serif;
    background: linear-gradient(135deg, #34D399, #10B981);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    line-height: 1; letter-spacing: -5px;
    animation: erPulse 3s ease-in-out infinite;
}
@keyframes erPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }

.er-float-icon {
    position: absolute; width: 60px; height: 60px;
    background: rgba(255,255,255,.1); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.2); border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: #34D399;
    animation: erFloat 4s ease-in-out infinite;
}
.er-icon-1 { top: 10%; left: 10%; animation-delay: 0s; }
.er-icon-2 { top: 20%; right: 15%; animation-delay: -1.5s; }
.er-icon-3 { bottom: 10%; left: 20%; animation-delay: -3s; }
@keyframes erFloat { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-20px) rotate(10deg); } }

.er-title { font-size: clamp(1.8rem, 4vw, 2.5rem); font-weight: 700; margin-bottom: 16px; }
.er-subtitle { font-size: 1.1rem; opacity: .9; max-width: 600px; margin: 0 auto 32px; line-height: 1.6; }

.er-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-bottom: 40px; }
.er-btn {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 14px 28px; border-radius: 50px; font-weight: 600;
    text-decoration: none; transition: all .3s; font-size: .95rem;
}
.er-btn-primary {
    background: #fff; color: var(--green-dark);
    box-shadow: 0 10px 30px rgba(0,0,0,.2);
}
.er-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 15px 40px rgba(0,0,0,.3); }
.er-btn-ghost {
    background: transparent; color: #fff;
    border: 2px solid rgba(255,255,255,.3);
}
.er-btn-ghost:hover { background: rgba(255,255,255,.1); border-color: #fff; }

.er-suggestions p { font-size: .95rem; opacity: .8; margin-bottom: 16px; }
.er-suggestion-links { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.er-suggestion-links a {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 18px; background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.2); border-radius: 50px;
    color: #fff; text-decoration: none; font-size: .88rem;
    transition: all .3s;
}
.er-suggestion-links a:hover { background: rgba(255,255,255,.2); transform: translateY(-2px); }
.er-suggestion-links a i { color: #34D399; }

@media (max-width: 768px) {
    .er-actions { flex-direction: column; }
    .er-btn { width: 100%; justify-content: center; }
    .er-suggestion-links { flex-direction: column; align-items: center; }
    .er-suggestion-links a { width: 100%; max-width: 280px; justify-content: center; }
}
</style>

<?php require_once 'includes/footer.php'; ?>