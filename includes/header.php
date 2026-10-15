<?php
require_once __DIR__ . '/init_security.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Deteksi halaman saat ini untuk kontrol Loader
$currentPage = basename($_SERVER['PHP_SELF']);
$isHomePage = ($currentPage === 'index.php' || $currentPage === '');

/* ===== SEO & Page Metadata ===== */
$pageTitle    = $pageTitle ?? 'Program Studi Informatika - Terakreditasi Baik Sekali';
$pageDesc     = $pageDesc ?? 'Program Studi Informatika terdepan yang mencetak AI Engineer, IT Consultant, dan Software Engineer berkualitas tinggi untuk era transformasi digital.';
$pageKeywords = $pageKeywords ?? 'informatika, teknik informatika, program studi, kampus IT, AI engineer, software engineer, data science, cybersecurity';
$pageImage    = $pageImage ?? (defined('SITE_URL') ? SITE_URL : '') . '/assets/og-image.jpg';
$pageUrl      = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$pageType     = $pageType ?? 'website';
$canonical    = $canonical ?? $pageUrl;

// Breadcrumb generation untuk SEO
$breadcrumbItems = [
    ['name' => 'Beranda', 'url' => defined('SITE_URL') ? SITE_URL : '/'],
];
if (isset($pageTitle) && !empty($pageTitle) && $pageTitle !== 'Program Studi Informatika - Terakreditasi Baik Sekali') {
    $breadcrumbItems[] = ['name' => $pageTitle, 'url' => $pageUrl];
}

// Anti-FOUC: Cek preferensi tema sebelum render untuk mencegah layar berkedip
$initialTheme = isset($_COOKIE['hp_theme']) ? $_COOKIE['hp_theme'] : (isset($_COOKIE['gl_theme']) ? $_COOKIE['gl_theme'] : 'light');

// Detect device untuk adaptive loading
$isMobile = isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone|iPad/i', $_SERVER['HTTP_USER_AGENT']);

// Generate unique cache-buster version
$assetVersion = defined('SITE_VERSION') ? SITE_VERSION : date('YmdH');
?>
<!DOCTYPE html>
<html lang="id" class="<?= $initialTheme === 'dark' ? 'gl-dark' : '' ?>" data-theme="<?= $initialTheme ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=5">
    <meta name="theme-color" content="#10B981" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0F172A" media="(prefers-color-scheme: dark)">
    <meta name="color-scheme" content="light dark">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="format-detection" content="telephone=no">
    
    <!-- PWA Support -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= defined('SITE_NAME') ? SITE_NAME : 'Informatika Pro' ?>">
    <meta name="application-name" content="<?= defined('SITE_NAME') ? SITE_NAME : 'Informatika Pro' ?>">
    <meta name="msapplication-TileColor" content="#10B981">

    <title><?= clean($pageTitle) ?> | <?= defined('SITE_NAME') ? SITE_NAME : 'Informatika Pro' ?></title>
    <meta name="description" content="<?= clean($pageDesc) ?>">
    <meta name="keywords" content="<?= clean($pageKeywords) ?>">
    <meta name="author" content="<?= defined('SITE_NAME') ? SITE_NAME : 'Informatika Pro' ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="bingbot" content="index, follow">
    <link rel="canonical" href="<?= clean($canonical) ?>">
    
    <!-- Alternate Languages (i18n ready) -->
    <link rel="alternate" hreflang="id" href="<?= clean($pageUrl) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= clean($pageUrl) ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?= clean($pageType) ?>">
    <meta property="og:site_name" content="<?= defined('SITE_NAME') ? SITE_NAME : 'Informatika Pro' ?>">
    <meta property="og:title" content="<?= clean($pageTitle) ?>">
    <meta property="og:description" content="<?= clean($pageDesc) ?>">
    <meta property="og:image" content="<?= clean($pageImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= clean($pageTitle) ?>">
    <meta property="og:url" content="<?= clean($pageUrl) ?>">
    <meta property="og:locale" content="id_ID">
    <meta property="og:locale:alternate" content="en_US">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= clean($pageTitle) ?>">
    <meta name="twitter:description" content="<?= clean($pageDesc) ?>">
    <meta name="twitter:image" content="<?= clean($pageImage) ?>">
    <meta name="twitter:image:alt" content="<?= clean($pageTitle) ?>">
    <meta name="twitter:site" content="@informatikapro">
    <meta name="twitter:creator" content="@informatikapro">

    <!-- Performance: Preconnect, DNS Prefetch & Prerender -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    
    <!-- Preload Critical Resources -->
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="preload" as="style" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php if (defined('SITE_URL')): ?>
    <link rel="preload" as="style" href="<?= SITE_URL ?>/css/style.css?v=<?= $assetVersion ?>">
    <?php endif; ?>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <?php if (defined('SITE_URL')): ?>
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css?v=<?= $assetVersion ?>">
    <?php endif; ?>

    <!-- Enhanced SVG Favicon with Animation -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><defs><linearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'><stop offset='0%25' style='stop-color:%2310B981'/><stop offset='100%25' style='stop-color:%23047857'/></linearGradient></defs><rect width='100' height='100' rx='22' fill='url(%23grad)'/><text x='50' y='68' font-family='Arial, sans-serif' font-size='48' font-weight='bold' text-anchor='middle' fill='white'>IP</text></svg>">
    <link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%2310B981'/><text x='50' y='68' font-family='Arial, sans-serif' font-size='48' font-weight='bold' text-anchor='middle' fill='white'>IP</text></svg>">

    <!-- JSON-LD: EducationalOrganization -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "EducationalOrganization",
      "name": "<?= defined('SITE_NAME') ? SITE_NAME : 'Program Studi Informatika' ?>",
      "alternateName": "Informatika Pro",
      "url": "<?= defined('SITE_URL') ? SITE_URL : 'https://informatikapro.ac.id' ?>",
      "logo": "<?= defined('SITE_URL') ? SITE_URL : '' ?>/assets/logo.png",
      "description": "<?= clean($pageDesc) ?>",
      "address": {
        "@type": "PostalAddress",
        "addressCountry": "ID",
        "addressLocality": "Jakarta"
      },
      "sameAs": [
        "https://instagram.com/informatikapro",
        "https://youtube.com/@informatikapro",
        "https://linkedin.com/school/informatikapro"
      ],
      "foundingDate": "2020",
      "numberOfEmployees": {
        "@type": "QuantitativeValue",
        "value": 25,
        "unitText": "Dosen Ahli"
      }
    }
    </script>

    <!-- JSON-LD: BreadcrumbList -->
    <?php if (count($breadcrumbItems) > 1): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        <?php foreach ($breadcrumbItems as $index => $item): ?>
        {
          "@type": "ListItem",
          "position": <?= $index + 1 ?>,
          "name": "<?= clean($item['name']) ?>",
          "item": "<?= clean($item['url']) ?>"
        }<?= $index < count($breadcrumbItems) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
      ]
    }
    </script>
    <?php endif; ?>

    <!-- JSON-LD: WebSite with SearchAction -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "<?= defined('SITE_NAME') ? SITE_NAME : 'Informatika Pro' ?>",
      "url": "<?= defined('SITE_URL') ? SITE_URL : 'https://informatikapro.ac.id' ?>",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?= defined('SITE_URL') ? SITE_URL : '' ?>/search.php?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>

    <style>
    /* ===== CORE UTILITIES ===== */
    .gl-skip-link { 
        position: absolute; left: -9999px; top: auto; 
        width: 1px; height: 1px; overflow: hidden; z-index: 10002; 
    }
    .gl-skip-link:focus { 
        position: fixed; top: 10px; left: 10px; 
        width: auto; height: auto; padding: 12px 20px; 
        background: #10B981; color: #fff; border-radius: 8px; 
        text-decoration: none; font-weight: 600; font-size: .9rem; 
        box-shadow: 0 8px 20px rgba(16,185,129,.4); 
        transition: all 0.3s; 
    }
    
    <?php if ($isHomePage): ?>
    /* ===== MATRIX RAIN LOADER (HANYA DI HOMEPAGE) ===== */
    .gl-loader { 
        position: fixed; inset: 0; z-index: 99999; 
        background: linear-gradient(135deg, #020617 0%, #0F172A 50%, #047857 100%);
        display: flex; align-items: center; justify-content: center; 
        transition: opacity 0.8s cubic-bezier(.4,0,.2,1), visibility 0.8s;
        overflow: hidden;
    }
    .gl-loader.gone { opacity: 0; visibility: hidden; pointer-events: none; }
    body.gl-dark .gl-loader { background: linear-gradient(135deg, #020617 0%, #0F172A 100%); }
    
    .gl-matrix-canvas {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        opacity: 0.25; z-index: 1;
    }
    
    .gl-loader-content { 
        position: relative; z-index: 2;
        text-align: center; 
        padding: 20px;
    }
    
    .gl-loader-logo { 
        display: flex; align-items: center; justify-content: center; 
        gap: 14px; font-family: 'Space Grotesk', sans-serif; 
        font-size: 2rem; font-weight: 800; color: #ffffff; 
        margin-bottom: 32px; position: relative;
        letter-spacing: -0.02em;
    }
    .gl-loader-logo .logo-icon {
        width: 60px; height: 60px; border-radius: 16px;
        background: linear-gradient(135deg, #10B981, #047857);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; color: #fff;
        box-shadow: 0 8px 24px rgba(16,185,129,0.5);
        animation: glLogoSpin 3s ease-in-out infinite;
    }
    @keyframes glLogoSpin {
        0%, 100% { transform: rotate(0deg) scale(1); }
        50% { transform: rotate(5deg) scale(1.05); }
    }
    
    .gl-loader-logo .logo-text {
        position: relative;
        display: inline-block;
    }
    .gl-loader-logo .logo-text span.accent { color: #10B981; }
    
    .gl-loader-logo .logo-text::before,
    .gl-loader-logo .logo-text::after {
        content: attr(data-text);
        position: absolute; top: 0; left: 0;
        width: 100%; height: 100%;
    }
    .gl-loader-logo .logo-text::before {
        color: #EC4899;
        animation: glGlitch1 3s infinite;
        clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%);
    }
    .gl-loader-logo .logo-text::after {
        color: #3B82F6;
        animation: glGlitch2 2.5s infinite;
        clip-path: polygon(0 60%, 100% 60%, 100% 100%, 0 100%);
    }
    @keyframes glGlitch1 {
        0%, 90%, 100% { transform: translate(0); }
        92% { transform: translate(-2px, 1px); }
        94% { transform: translate(2px, -1px); }
    }
    @keyframes glGlitch2 {
        0%, 90%, 100% { transform: translate(0); }
        91% { transform: translate(2px, 1px); }
        93% { transform: translate(-2px, -1px); }
    }
    
    .gl-loader-status {
        display: flex; align-items: center; justify-content: center;
        gap: 8px; font-family: 'JetBrains Mono', 'Courier New', monospace;
        font-size: 0.8rem; color: rgba(255,255,255,0.6);
        letter-spacing: 2px; text-transform: uppercase;
        margin-bottom: 20px;
    }
    .gl-loader-status .status-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: #10B981;
        box-shadow: 0 0 10px #10B981;
        animation: glStatusPulse 1.5s infinite;
    }
    @keyframes glStatusPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.8); }
    }
    
    .gl-loader-terminal {
        max-width: 380px; margin: 0 auto;
        background: rgba(0,0,0,0.4); backdrop-filter: blur(10px);
        border: 1px solid rgba(16,185,129,0.3);
        border-radius: 12px; padding: 16px 20px;
        text-align: left;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    .gl-terminal-header {
        display: flex; align-items: center; gap: 8px;
        padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 12px;
    }
    .gl-terminal-dots { display: flex; gap: 5px; }
    .gl-terminal-dots span { width: 10px; height: 10px; border-radius: 50%; }
    .gl-terminal-dots span:nth-child(1) { background: #FF5F56; }
    .gl-terminal-dots span:nth-child(2) { background: #FFBD2E; }
    .gl-terminal-dots span:nth-child(3) { background: #27C93F; }
    .gl-terminal-title {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.72rem; color: rgba(255,255,255,0.5);
        margin-left: auto;
    }
    
    .gl-terminal-body {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.75rem; color: rgba(255,255,255,0.7);
    }
    .gl-terminal-line {
        display: flex; gap: 8px; margin-bottom: 6px;
        opacity: 0; transform: translateX(-10px);
        animation: glTerminalLine 0.3s forwards;
    }
    .gl-terminal-line:nth-child(1) { animation-delay: 0.2s; }
    .gl-terminal-line:nth-child(2) { animation-delay: 0.6s; }
    .gl-terminal-line:nth-child(3) { animation-delay: 1.0s; }
    .gl-terminal-line:nth-child(4) { animation-delay: 1.4s; }
    
    @keyframes glTerminalLine {
        to { opacity: 1; transform: translateX(0); }
    }
    
    .gl-terminal-prompt { color: #10B981; }
    .gl-terminal-cmd { color: #fff; }
    .gl-terminal-success { color: #10B981; }
    .gl-terminal-info { color: #3B82F6; }
    .gl-terminal-warn { color: #F59E0B; }
    
    .gl-terminal-cursor {
        display: inline-block;
        width: 7px; height: 14px;
        background: #10B981;
        animation: glCursorBlink 1s infinite;
        vertical-align: middle;
        margin-left: 2px;
    }
    @keyframes glCursorBlink {
        0%, 50% { opacity: 1; }
        51%, 100% { opacity: 0; }
    }
    
    .gl-loader-progress {
        margin-top: 16px; height: 3px;
        background: rgba(255,255,255,0.1);
        border-radius: 10px; overflow: hidden;
    }
    .gl-loader-progress-fill {
        height: 100%; width: 0;
        background: linear-gradient(90deg, #10B981, #06B6D4, #8B5CF6);
        background-size: 200% 100%;
        border-radius: 10px;
        animation: glProgressFill 2.5s cubic-bezier(0.4, 0, 0.2, 1) forwards,
                   glProgressGlow 2s linear infinite;
        box-shadow: 0 0 15px rgba(16,185,129,0.6);
    }
    @keyframes glProgressFill { 
        0% { width: 0%; }
        20% { width: 25%; }
        50% { width: 60%; }
        80% { width: 85%; }
        100% { width: 100%; }
    }
    @keyframes glProgressGlow {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    .gl-loader-percent {
        margin-top: 10px;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 0.85rem; color: rgba(255,255,255,0.7);
        font-weight: 600;
    }
    .gl-loader-percent strong {
        color: #10B981; font-size: 1.1rem;
    }
    <?php endif; ?>

    /* ===== GLOBAL THEME OVERRIDES ===== */
    html { scroll-behavior: smooth; }
    ::selection { background: rgba(16,185,129,0.25); color: #0F172A; }
    body.gl-dark ::selection { background: rgba(16,185,129,0.4); color: #fff; }
    
    body { transition: background-color 0.3s ease, color 0.3s ease; }
    body.gl-dark { background-color: #0F172A !important; color: #E2E8F0 !important; }
    
    /* Prevent FOUC for common elements */
    body.gl-dark .navbar { background: rgba(15,23,42,0.9) !important; border-color: rgba(255,255,255,0.05) !important; backdrop-filter: blur(12px); }
    body.gl-dark .logo, body.gl-dark .nav-link { color: #E2E8F0 !important; }
    body.gl-dark .nav-toggle span { background: #E2E8F0 !important; }
    body.gl-dark .footer { background: #020617 !important; }
    body.gl-dark .footer-col h4, body.gl-dark .footer-col .logo { color: #fff !important; }
    body.gl-dark .footer-col p, body.gl-dark .footer-col ul a, body.gl-dark .contact-info li { color: rgba(226,232,240,0.7) !important; }
    body.gl-dark .newsletter-form input { background: rgba(255,255,255,0.08); color: #fff; border-color: rgba(255,255,255,0.1); }
    body.gl-dark .newsletter-form input::placeholder { color: rgba(255,255,255,0.4); }
    body.gl-dark .footer-bottom { border-color: rgba(255,255,255,0.08); color: rgba(226,232,240,0.6); }
    body.gl-dark .wa-float { box-shadow: 0 8px 24px rgba(37,211,102,0.4); }

/* Smooth reveal for main content - HOMEPAGE ONLY */
<?php if ($isHomePage): ?>
main, .main-content, #main-content {
    opacity: 0;
    animation: glContentReveal 0.6s ease-out 1.5s forwards;
}
@keyframes glContentReveal {
    to { opacity: 1; }
}
<?php else: ?>
main, .main-content, #main-content {
    opacity: 1; /* Instant show on non-homepage */
}
<?php endif; ?>

<?php if (!$isHomePage): ?>
/* ANTI-FLASH: Preloader state untuk non-homepage */
html.gl-preload body {
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}
html.gl-preload body #quantumLoader {
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
}
/* Saat preloader selesai, smooth reveal body */
html:not(.gl-preload) body {
    opacity: 1;
    visibility: visible;
}
<?php endif; ?>

    /* ===== SCROLLBAR STYLING ===== */
    ::-webkit-scrollbar { width: 10px; height: 10px; }
    ::-webkit-scrollbar-track { background: #F1F5F9; }
    body.gl-dark ::-webkit-scrollbar-track { background: #0B1120; }
    ::-webkit-scrollbar-thumb { 
        background: linear-gradient(180deg, #10B981, #047857); 
        border-radius: 5px; 
    }
    ::-webkit-scrollbar-thumb:hover { 
        background: linear-gradient(180deg, #047857, #065F46); 
    }

    @media (prefers-reduced-motion: reduce) { 
        *, *::before, *::after { 
            animation-duration: 0.01ms !important; 
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important; 
            scroll-behavior: auto !important; 
        } 
        .gl-matrix-canvas { display: none; }
    }
    @media print { 
        .navbar, .footer, .wa-float, .gl-loader, .gl-skip-link,
        .gl-fab-hub, .gl-back-top { display: none !important; } 
        body { background: #fff !important; color: #000 !important; } 
        a { color: #000 !important; text-decoration: underline; }
        a[href]:after { content: " (" attr(href) ")"; font-size: 0.8em; color: #666; }
    }
    </style>

<!-- Anti-FOUC Theme Script + Preloader Detection (Executes before render) -->
<script>
    (function() {
        // 1. Theme detection (existing)
        const savedTheme = localStorage.getItem('hp_theme') || localStorage.getItem('gl_theme');
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('gl-dark');
            document.documentElement.setAttribute('data-theme', 'dark');
            if (document.body) document.body.classList.add('gl-dark');
        }
        
        // 2. ANTI-FLASH: Preloader detection
        // Jika user navigasi internal, set class gl-preload SEBELUM render
        // Ini akan hide body sampai Quantum Loader aktif
        try {
            if (sessionStorage.getItem('glInternalNav') === 'true') {
                document.documentElement.classList.add('gl-preload');
            }
        } catch(e) {}
    })();
</script>
</head>
<body class="<?= $initialTheme === 'dark' ? 'gl-dark' : '' ?>" data-page="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'])) ?>">

<a href="#main-content" class="gl-skip-link">Langsung ke konten utama</a>

<?php if ($isHomePage): ?>
<!-- ===== CODE MATRIX LOADER (HANYA DI HOMEPAGE) ===== -->
<div class="gl-loader" id="glLoader" role="status" aria-live="polite" aria-label="Memuat halaman">
    <canvas class="gl-matrix-canvas" id="glMatrixCanvas"></canvas>
    
    <div class="gl-loader-content">
        <div class="gl-loader-logo">
            <div class="logo-icon"><i class="fas fa-code"></i></div>
            <span class="logo-text" data-text="InformatikaPro">Informatika<span class="accent">Pro</span></span>
        </div>
        
        <div class="gl-loader-status">
            <span class="status-dot"></span>
            <span id="glLoaderStatus">Initializing System...</span>
        </div>
        
        <div class="gl-loader-terminal">
            <div class="gl-terminal-header">
                <div class="gl-terminal-dots"><span></span><span></span><span></span></div>
                <span class="gl-terminal-title">system_boot.sh</span>
            </div>
            <div class="gl-terminal-body">
                <div class="gl-terminal-line"><span class="gl-terminal-prompt">$</span><span class="gl-terminal-cmd">loading modules...</span><span class="gl-terminal-success">✓</span></div>
                <div class="gl-terminal-line"><span class="gl-terminal-prompt">$</span><span class="gl-terminal-cmd">connecting database...</span><span class="gl-terminal-success">✓</span></div>
                <div class="gl-terminal-line"><span class="gl-terminal-prompt">$</span><span class="gl-terminal-cmd">rendering interface...</span><span class="gl-terminal-info">⟳</span></div>
                <div class="gl-terminal-line"><span class="gl-terminal-prompt">$</span><span class="gl-terminal-cmd">ready to launch</span><span class="gl-terminal-cursor"></span></div>
            </div>
            
            <div class="gl-loader-progress">
                <div class="gl-loader-progress-fill" id="glProgressFill"></div>
            </div>
            <div class="gl-loader-percent">Loading: <strong id="glProgressPercent">0%</strong></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isHomePage): ?>
<!-- BULLETPROOF LOADER HIDER + MATRIX ANIMATION (HANYA DI HOMEPAGE) -->
<script>
(function() {
    'use strict';
    
    // ===== 1. Matrix Rain Animation =====
    const canvas = document.getElementById('glMatrixCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let width, height, columns, drops;
        const chars = 'アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン0123456789ABCDEF</>{}[]()=+-*&^%$#@!';
        const charArray = chars.split('');
        const fontSize = 14;
        
        function initMatrix() {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
            columns = Math.floor(width / fontSize);
            drops = Array(columns).fill(1);
        }
        
        function drawMatrix() {
            ctx.fillStyle = 'rgba(2, 6, 23, 0.08)';
            ctx.fillRect(0, 0, width, height);
            ctx.fillStyle = '#10B981';
            ctx.font = fontSize + 'px monospace';
            
            for (let i = 0; i < drops.length; i++) {
                const text = charArray[Math.floor(Math.random() * charArray.length)];
                const x = i * fontSize;
                const y = drops[i] * fontSize;
                
                if (Math.random() > 0.95) ctx.fillStyle = '#34D399';
                else if (Math.random() > 0.98) ctx.fillStyle = '#06B6D4';
                else ctx.fillStyle = '#10B981';
                
                ctx.fillText(text, x, y);
                if (y > height && Math.random() > 0.975) drops[i] = 0;
                drops[i]++;
            }
            
            if (!window.__glLoaderHidden) requestAnimationFrame(drawMatrix);
        }
        
        initMatrix();
        drawMatrix();
        window.addEventListener('resize', initMatrix);
    }
    
    // ===== 2. Progress Simulation =====
    const progressFill = document.getElementById('glProgressFill');
    const progressPercent = document.getElementById('glProgressPercent');
    const loaderStatus = document.getElementById('glLoaderStatus');
    
    const statusMessages = [
        'Initializing System...', 'Loading Modules...', 'Connecting Database...',
        'Fetching Resources...', 'Compiling Assets...', 'Rendering Interface...',
        'Optimizing Performance...', 'Almost Ready...', 'Launching...'
    ];
    
    let currentProgress = 0;
    let statusIndex = 0;
    
    function updateProgress() {
        if (window.__glLoaderHidden) return;
        
        const increment = Math.random() * 15 + 5;
        currentProgress = Math.min(currentProgress + increment, 100);
        
        if (progressFill) progressFill.style.width = currentProgress + '%';
        if (progressPercent) progressPercent.textContent = Math.floor(currentProgress) + '%';
        
        const newStatusIndex = Math.floor((currentProgress / 100) * statusMessages.length);
        if (newStatusIndex !== statusIndex && newStatusIndex < statusMessages.length) {
            statusIndex = newStatusIndex;
            if (loaderStatus) loaderStatus.textContent = statusMessages[statusIndex];
        }
        
        if (currentProgress < 100) setTimeout(updateProgress, Math.random() * 200 + 100);
    }
    
    setTimeout(updateProgress, 300);
    
    // ===== 3. Loader Hider =====
    const glLoader = document.getElementById('glLoader');
    const pageLoader = document.querySelector('.hp-loader, .bd-loader, .rg-loader, .tk-loader, .co-loader');
    
    if (glLoader) {
        const hide = () => {
            if (window.__glLoaderHidden) return;
            window.__glLoaderHidden = true;
            
            if (loaderStatus) loaderStatus.textContent = 'Welcome!';
            if (progressPercent) progressPercent.textContent = '100%';
            
            setTimeout(() => {
                glLoader.classList.add('gone');
                setTimeout(() => { 
                    if (glLoader && glLoader.parentNode) glLoader.remove(); 
                }, 800);
            }, 300);
        };
        
        if (pageLoader) {
            setTimeout(hide, 150);
        } else {
            window.addEventListener('load', () => setTimeout(hide, 1500));
            setTimeout(hide, 4500); // Absolute fallback
        }
    }
    
    // ===== 4. Performance Observer =====
    if ('PerformanceObserver' in window) {
        try {
            const lcpObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                console.log('[GL] LCP:', lastEntry.startTime.toFixed(2) + 'ms');
            });
            lcpObserver.observe({ type: 'largest-contentful-paint', buffered: true });
        } catch(e) {}
    }
    
    // ===== 5. Console Branding =====
    console.log('%c╔════════════════════════════════════════════╗', 'color:#10B981;font-weight:bold;font-size:12px');
    console.log('%c║   INFORMATIKA PRO - Code Matrix Loaded     ║', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c╚════════════════════════════════════════════╝', 'color:#10B981;font-weight:bold;font-size:12px');
    console.log('%c🚀 Welcome, Developer!', 'color:#3B82F6;font-weight:bold;font-size:12px');
    console.log('%c📧 Contact: dev@informatikapro.ac.id', 'color:#94A3B8;font-size:11px');
    console.log('%c⚠️ Jangan paste kode apapun di sini!', 'color:#EF4444;font-weight:bold;font-size:12px');
    
    // ===== 6. Keyboard Shortcuts (Global) =====
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            const searchInput = document.querySelector('input[type="search"], input[name="q"], #searchInput, #glSearchInput');
            if (searchInput) { e.preventDefault(); searchInput.focus(); }
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            const themeToggle = document.querySelector('#themeToggle, .theme-toggle, [data-theme-toggle]');
            if (themeToggle) { e.preventDefault(); themeToggle.click(); }
        }
    });
    
    // ===== 7. Global Theme Toggle Function =====
    window.glToggleTheme = function() {
        const html = document.documentElement;
        const body = document.body;
        const isDark = html.classList.contains('gl-dark');
        
        if (isDark) {
            html.classList.remove('gl-dark');
            html.setAttribute('data-theme', 'light');
            body.classList.remove('gl-dark');
            localStorage.setItem('gl_theme', 'light');
            localStorage.setItem('hp_theme', 'light');
            document.cookie = 'gl_theme=light;path=/;max-age=31536000';
        } else {
            html.classList.add('gl-dark');
            html.setAttribute('data-theme', 'dark');
            body.classList.add('gl-dark');
            localStorage.setItem('gl_theme', 'dark');
            localStorage.setItem('hp_theme', 'dark');
            document.cookie = 'gl_theme=dark;path=/;max-age=31536000';
        }
        window.dispatchEvent(new CustomEvent('glThemeChange', { detail: { theme: isDark ? 'light' : 'dark' } }));
    };
    
    // ===== 8. Network Status Indicator =====
    window.addEventListener('online', () => { document.documentElement.removeAttribute('data-offline'); });
    window.addEventListener('offline', () => { document.documentElement.setAttribute('data-offline', 'true'); });
    if (!navigator.onLine) { document.documentElement.setAttribute('data-offline', 'true'); }
    
})();
</script>
<?php endif; ?>

<!-- Global State -->
<script>
    window.GL_CONFIG = {
        siteUrl: '<?= defined('SITE_URL') ? SITE_URL : '' ?>',
        siteName: '<?= defined('SITE_NAME') ? SITE_NAME : 'Informatika Pro' ?>',
        assetVersion: '<?= $assetVersion ?>',
        isMobile: <?= $isMobile ? 'true' : 'false' ?>,
        initialTheme: '<?= $initialTheme ?>'
    };
</script>