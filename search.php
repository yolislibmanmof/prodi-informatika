<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Hasil Pencarian';
$query = isset($_GET['q']) ? clean($_GET['q']) : '';

$results = ['berita' => [], 'pengumuman' => [], 'faq' => []];

if ($query) {
    try {
        $db = getDB();
        $searchTerm = "%$query%";
        
        // Search Berita
        $stmt = $db->prepare("SELECT id, judul, ringkasan, slug, kategori, created_at FROM berita WHERE status='published' AND (judul LIKE ? OR ringkasan LIKE ?) ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$searchTerm, $searchTerm]);
        $results['berita'] = $stmt->fetchAll();
        
        // Search Pengumuman
        $stmt = $db->prepare("SELECT id, judul, konten, created_at FROM pengumuman WHERE status='aktif' AND (judul LIKE ? OR konten LIKE ?) ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$searchTerm, $searchTerm]);
        $results['pengumuman'] = $stmt->fetchAll();
        
        // Search FAQ
        $stmt = $db->prepare("SELECT id, pertanyaan, jawaban FROM faq WHERE status='aktif' AND (pertanyaan LIKE ? OR jawaban LIKE ?) ORDER BY urutan ASC LIMIT 5");
        $stmt->execute([$searchTerm, $searchTerm]);
        $results['faq'] = $stmt->fetchAll();
        
    } catch (Throwable $e) {
        // Silent fail
    }
}

$totalResults = count($results['berita']) + count($results['pengumuman']) + count($results['faq']);
?>

<div class="sr-hero">
    <div class="container">
        <span class="sr-hero-badge"><i class="fas fa-search"></i> Pencarian</span>
        <h1 class="sr-hero-title">Hasil Pencarian</h1>
        
        <!-- Search Form -->
        <form method="GET" action="search.php" class="sr-search-form">
            <div class="sr-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Ketik kata kunci pencarian..." required>
            </div>
            <button type="submit" class="sr-search-btn">
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        
        <?php if ($query): ?>
        <p class="sr-result-count">Ditemukan <strong><?= $totalResults ?></strong> hasil untuk "<strong><?= htmlspecialchars($query) ?></strong>"</p>
        <?php endif; ?>
    </div>
</div>

<section class="sr-section">
    <div class="container">
        <?php if (!$query): ?>
            <div class="sr-empty-state">
                <i class="fas fa-search"></i>
                <h3>Mulai Pencarian</h3>
                <p>Ketik kata kunci di atas untuk mencari berita, pengumuman, atau FAQ.</p>
            </div>
        <?php elseif ($totalResults === 0): ?>
            <div class="sr-empty-state">
                <i class="fas fa-search"></i>
                <h3>Tidak Ada Hasil</h3>
                <p>Tidak ditemukan konten yang cocok dengan "<strong><?= htmlspecialchars($query) ?></strong>". Coba kata kunci lain.</p>
                <div class="sr-suggestions">
                    <p>Saran pencarian:</p>
                    <div class="sr-suggestion-tags">
                        <a href="?q=beasiswa">Beasiswa</a>
                        <a href="?q=kurikulum">Kurikulum</a>
                        <a href="?q=AI">AI</a>
                        <a href="?q=pendaftaran">Pendaftaran</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="sr-results">
                <!-- Berita Results -->
                <?php if (!empty($results['berita'])): ?>
                <div class="sr-group">
                    <h2 class="sr-group-title"><i class="fas fa-newspaper"></i> Berita (<?= count($results['berita']) ?>)</h2>
                    <div class="sr-list">
                        <?php foreach ($results['berita'] as $b): ?>
                        <a href="berita-detail.php?slug=<?= clean($b['slug']) ?>" class="sr-item">
                            <div class="sr-item-icon"><i class="fas fa-newspaper"></i></div>
                            <div class="sr-item-content">
                                <h3><?= clean($b['judul']) ?></h3>
                                <p><?= clean(mb_substr($b['ringkasan'], 0, 120)) ?>...</p>
                                <span class="sr-item-meta"><i class="far fa-calendar"></i> <?= formatTanggal($b['created_at']) ?></span>
                            </div>
                            <i class="fas fa-chevron-right sr-item-arrow"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Pengumuman Results -->
                <?php if (!empty($results['pengumuman'])): ?>
                <div class="sr-group">
                    <h2 class="sr-group-title"><i class="fas fa-bullhorn"></i> Pengumuman (<?= count($results['pengumuman']) ?>)</h2>
                    <div class="sr-list">
                        <?php foreach ($results['pengumuman'] as $p): ?>
                        <a href="pengumuman.php" class="sr-item">
                            <div class="sr-item-icon"><i class="fas fa-bullhorn"></i></div>
                            <div class="sr-item-content">
                                <h3><?= clean($p['judul']) ?></h3>
                                <p><?= clean(mb_substr($p['konten'], 0, 120)) ?>...</p>
                                <span class="sr-item-meta"><i class="far fa-calendar"></i> <?= formatTanggal($p['created_at']) ?></span>
                            </div>
                            <i class="fas fa-chevron-right sr-item-arrow"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- FAQ Results -->
                <?php if (!empty($results['faq'])): ?>
                <div class="sr-group">
                    <h2 class="sr-group-title"><i class="fas fa-question-circle"></i> FAQ (<?= count($results['faq']) ?>)</h2>
                    <div class="sr-list">
                        <?php foreach ($results['faq'] as $f): ?>
                        <a href="faq.php" class="sr-item">
                            <div class="sr-item-icon"><i class="fas fa-question-circle"></i></div>
                            <div class="sr-item-content">
                                <h3><?= clean($f['pertanyaan']) ?></h3>
                                <p><?= clean(mb_substr($f['jawaban'], 0, 120)) ?>...</p>
                            </div>
                            <i class="fas fa-chevron-right sr-item-arrow"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.sr-hero {
    background: linear-gradient(135deg, var(--black-primary) 0%, var(--green-dark) 100%);
    padding: 140px 20px 60px; text-align: center; color: #fff;
}
.sr-hero-badge {
    display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px;
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
    border-radius: 50px; font-size: .85rem; margin-bottom: 20px; backdrop-filter: blur(10px);
}
.sr-hero-title { font-size: clamp(2rem, 4vw, 3rem); font-weight: 700; margin-bottom: 32px; }

.sr-search-form {
    display: flex; max-width: 700px; margin: 0 auto 20px;
    background: #fff; border-radius: 16px; overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,.2);
}
.sr-search-wrap {
    flex: 1; position: relative; display: flex; align-items: center;
}
.sr-search-wrap i {
    position: absolute; left: 20px; color: var(--gray-400); font-size: 1.1rem;
}
.sr-search-wrap input {
    width: 100%; padding: 18px 20px 18px 50px; border: none; outline: none;
    font-size: 1.05rem; font-family: inherit; background: transparent;
}
.sr-search-btn {
    padding: 0 28px; background: var(--green-primary); border: none;
    color: #fff; cursor: pointer; font-size: 1.1rem; transition: all .3s;
}
.sr-search-btn:hover { background: var(--green-dark); }
.sr-result-count { font-size: 1rem; opacity: .9; }
.sr-result-count strong { color: #34D399; }

.sr-section { padding: 60px 0 100px; background: var(--gray-50); }
body.gl-dark .sr-section { background: #0F172A; }

.sr-empty-state {
    text-align: center; padding: 80px 20px; background: #fff;
    border-radius: 20px; border: 2px dashed var(--gray-200); max-width: 600px; margin: 0 auto;
}
body.gl-dark .sr-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }
.sr-empty-state i { font-size: 3rem; color: var(--gray-300); margin-bottom: 16px; }
.sr-empty-state h3 { font-size: 1.3rem; margin-bottom: 8px; }
.sr-empty-state p { color: var(--gray-600); margin-bottom: 20px; }

.sr-suggestions { margin-top: 24px; }
.sr-suggestions p { font-size: .9rem; margin-bottom: 12px; }
.sr-suggestion-tags { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
.sr-suggestion-tags a {
    padding: 8px 16px; background: var(--green-light); color: var(--green-dark);
    border-radius: 50px; text-decoration: none; font-size: .85rem; font-weight: 500;
    transition: all .3s;
}
body.gl-dark .sr-suggestion-tags a { background: rgba(16,185,129,.15); color: #34D399; }
.sr-suggestion-tags a:hover { background: var(--green-primary); color: #fff; }

.sr-results { display: flex; flex-direction: column; gap: 32px; }
.sr-group-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 1.2rem; margin-bottom: 16px; padding-bottom: 12px;
    border-bottom: 2px solid var(--gray-200);
}
body.gl-dark .sr-group-title { border-color: rgba(255,255,255,.1); }
.sr-group-title i { color: var(--green-primary); }

.sr-list { display: flex; flex-direction: column; gap: 12px; }
.sr-item {
    display: flex; align-items: flex-start; gap: 16px;
    padding: 20px; background: #fff; border-radius: 14px;
    border: 1px solid var(--gray-100); text-decoration: none;
    transition: all .3s;
}
body.gl-dark .sr-item { background: #1E293B; border-color: rgba(255,255,255,.08); }
.sr-item:hover { transform: translateX(6px); border-color: var(--green-primary); box-shadow: var(--shadow-sm); }
.sr-item-icon {
    width: 48px; height: 48px; background: var(--green-light); color: var(--green-dark);
    border-radius: 12px; display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
body.gl-dark .sr-item-icon { background: rgba(16,185,129,.15); color: #34D399; }
.sr-item-content { flex: 1; min-width: 0; }
.sr-item-content h3 { font-size: 1.05rem; margin-bottom: 6px; color: var(--black-primary); }
body.gl-dark .sr-item-content h3 { color: #E2E8F0; }
.sr-item:hover .sr-item-content h3 { color: var(--green-primary); }
.sr-item-content p { font-size: .9rem; color: var(--gray-600); margin-bottom: 8px; line-height: 1.5; }
body.gl-dark .sr-item-content p { color: #94A3B8; }
.sr-item-meta { font-size: .8rem; color: var(--gray-500); display: flex; align-items: center; gap: 6px; }
.sr-item-meta i { color: var(--green-primary); }
.sr-item-arrow { color: var(--gray-400); transition: all .3s; margin-top: 16px; }
.sr-item:hover .sr-item-arrow { color: var(--green-primary); transform: translateX(4px); }

@media (max-width: 768px) {
    .sr-search-form { flex-direction: column; border-radius: 12px; }
    .sr-search-btn { padding: 14px; border-radius: 0 0 12px 12px; }
    .sr-item { flex-direction: column; }
    .sr-item-arrow { display: none; }
}
</style>

<?php require_once 'includes/footer.php'; ?>