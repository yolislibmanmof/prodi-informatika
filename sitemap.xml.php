<?php
// Sitemap XML Generator untuk SEO
header('Content-Type: application/xml; charset=utf-8');

require_once 'includes/config.php';

try {
    $db = getDB();
    
    // Base URL
    $baseUrl = rtrim(SITE_URL, '/');
    
    // Get all dynamic content
    $beritaList = $db->query("SELECT slug, updated_at FROM berita WHERE status='published' ORDER BY updated_at DESC")->fetchAll();
    $pengumumanList = $db->query("SELECT id, updated_at FROM pengumuman WHERE status='aktif' ORDER BY updated_at DESC")->fetchAll();
    $galeriList = $db->query("SELECT id, updated_at FROM galeri ORDER BY updated_at DESC")->fetchAll();
    $faqList = $db->query("SELECT id, updated_at FROM faq WHERE status='aktif' ORDER BY updated_at DESC")->fetchAll();
    
} catch (Throwable $e) {
    $beritaList = [];
    $pengumumanList = [];
    $galeriList = [];
    $faqList = [];
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Homepage -->
    <url>
        <loc><?= $baseUrl ?>/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    
    <!-- Static Pages -->
    <url>
        <loc><?= $baseUrl ?>/kurikulum.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= $baseUrl ?>/prospek-karir.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= $baseUrl ?>/sambutan-kaprodi.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= $baseUrl ?>/dosen.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= $baseUrl ?>/faq.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= $baseUrl ?>/testimoni.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= $baseUrl ?>/galeri.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= $baseUrl ?>/berita.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= $baseUrl ?>/pengumuman.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= $baseUrl ?>/daftar.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.9</priority>
    </url>
    
    <!-- Berita Detail Pages -->
    <?php foreach ($beritaList as $b): ?>
    <url>
        <loc><?= $baseUrl ?>/berita-detail.php?slug=<?= htmlspecialchars($b['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($b['updated_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>
    
    <!-- Pengumuman Pages -->
    <?php foreach ($pengumumanList as $p): ?>
    <url>
        <loc><?= $baseUrl ?>/pengumuman.php</loc>
        <lastmod><?= date('Y-m-d', strtotime($p['updated_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
    </url>
    <?php endforeach; ?>
</urlset>