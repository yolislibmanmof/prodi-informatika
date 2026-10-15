<?php
declare(strict_types=1);

/**
 * Verify that records produced by admin-like INSERT/UPDATE operations satisfy the
 * same visibility predicates used by public pages. Everything runs in a transaction
 * that is rolled back; no persistent content is created.
 */
$host = getenv('MYSQL_HOST') ?: '127.0.0.1';
$port = (int)(getenv('MYSQL_PORT') ?: 3306);
$dbName = getenv('MYSQL_DATABASE') ?: 'prodi_informatika';
$user = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: 'root';

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
    $user,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

function assertPublicSync(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function publicRecord(PDO $pdo, string $label, string $sql, array $params, bool $expected = true): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    $visible = is_array($row);
    assertPublicSync(
        $visible === $expected,
        $label . ': public visibility expected ' . ($expected ? 'visible' : 'hidden') . ', got ' . ($visible ? 'visible' : 'hidden') . '.'
    );
    return $visible ? $row : null;
}

function insertAndCheck(PDO $pdo, string $label, string $insertSql, array $insertParams, string $publicSql, array $publicParams, string $expectedColumn, string $expectedValue): int
{
    $pdo->prepare($insertSql)->execute($insertParams);
    $id = (int)$pdo->lastInsertId();
    assertPublicSync($id > 0, $label . ': INSERT did not return an ID.');
    $row = publicRecord($pdo, $label, $publicSql, array_merge($publicParams, [$id]));
    assertPublicSync($row !== null && isset($row[$expectedColumn]) && (string)$row[$expectedColumn] === $expectedValue,
        $label . ': public query did not return the new field values.');
    return $id;
}

$suffix = bin2hex(random_bytes(5));
$pdo->beginTransaction();

try {
    // Homepage and agenda.php query the next upcoming agenda records.
    $agendaTitle = "CI public agenda {$suffix}";
    insertAndCheck(
        $pdo, 'agenda',
        "INSERT INTO agenda (judul, tanggal_mulai, status, is_featured) VALUES (?, DATE_ADD(NOW(), INTERVAL 1 DAY), 'akan_datang', 1)",
        [$agendaTitle],
        "SELECT id, judul FROM agenda WHERE status = 'akan_datang' AND tanggal_mulai >= CURDATE() AND id = ?",
        [], 'judul', $agendaTitle
    );

    // berita.php and index.php require published status.
    $newsTitle = "CI public news {$suffix}";
    $newsId = insertAndCheck(
        $pdo, 'berita',
        "INSERT INTO berita (judul, slug, kategori, ringkasan, konten, status, published_at) VALUES (?, ?, 'Akademik', ?, ?, 'published', NOW())",
        [$newsTitle, "ci-public-{$suffix}", 'CI summary', 'CI content'],
        "SELECT id, judul FROM berita WHERE status = 'published' AND id = ?",
        [], 'judul', $newsTitle
    );
    $updatedNewsTitle = "CI updated public news {$suffix}";
    $pdo->prepare("UPDATE berita SET judul = ?, updated_at = NOW() WHERE id = ?")->execute([$updatedNewsTitle, $newsId]);
    $newsRow = publicRecord($pdo, 'berita update', "SELECT id, judul FROM berita WHERE status = 'published' AND id = ?", [$newsId]);
    assertPublicSync($newsRow !== null && $newsRow['judul'] === $updatedNewsTitle, 'berita: public SELECT did not return the updated title.');
    $pdo->prepare("UPDATE berita SET status = 'draft', published_at = NULL WHERE id = ?")->execute([$newsId]);
    publicRecord($pdo, 'berita draft transition', "SELECT id FROM berita WHERE status = 'published' AND id = ?", [$newsId], false);

    // Public announcement listing checks status and optional active date window.
    $announcementTitle = "CI public announcement {$suffix}";
    insertAndCheck(
        $pdo, 'pengumuman',
        "INSERT INTO pengumuman (judul, konten, status) VALUES (?, ?, 'aktif')",
        [$announcementTitle, 'CI announcement content'],
        "SELECT id, judul FROM pengumuman WHERE status = 'aktif' AND (tanggal_mulai IS NULL OR tanggal_mulai <= CURDATE()) AND (tanggal_berakhir IS NULL OR tanggal_berakhir >= CURDATE()) AND id = ?",
        [], 'judul', $announcementTitle
    );

    // Public lecturer listing uses the active status and both education fields are kept in sync.
    $lecturerName = "CI public lecturer {$suffix}";
    $lecturerId = insertAndCheck(
        $pdo, 'dosen',
        "INSERT INTO dosen (nama, pendidikan, pendidikan_terakhir, status) VALUES (?, 'S2', 'S2', 'aktif')",
        [$lecturerName],
        "SELECT id, nama, pendidikan, pendidikan_terakhir FROM dosen WHERE status = 'aktif' AND id = ?",
        [], 'nama', $lecturerName
    );
    $lecturer = publicRecord($pdo, 'dosen education sync', "SELECT id, pendidikan, pendidikan_terakhir FROM dosen WHERE status = 'aktif' AND id = ?", [$lecturerId]);
    assertPublicSync($lecturer !== null && $lecturer['pendidikan'] === $lecturer['pendidikan_terakhir'], 'dosen: education fields diverged between admin and public data.');

    $faqText = "CI public FAQ {$suffix}";
    insertAndCheck(
        $pdo, 'faq',
        "INSERT INTO faq (pertanyaan, jawaban, status, kategori) VALUES (?, ?, 'aktif', 'Umum')",
        [$faqText, 'CI answer'],
        "SELECT id, pertanyaan FROM faq WHERE status = 'aktif' AND id = ?",
        [], 'pertanyaan', $faqText
    );

    $facilityName = "CI public facility {$suffix}";
    insertAndCheck(
        $pdo, 'fasilitas',
        "INSERT INTO fasilitas (nama, kapasitas, status) VALUES (?, 25, 'aktif')",
        [$facilityName],
        "SELECT id, nama FROM fasilitas WHERE status IN ('aktif', 'maintenance') AND id = ?",
        [], 'nama', $facilityName
    );

    $galleryTitle = "CI public gallery {$suffix}";
    insertAndCheck(
        $pdo, 'galeri',
        "INSERT INTO galeri (judul, gambar, kategori, tanggal) VALUES (?, 'ci-test.jpg', 'Akademik', CURDATE())",
        [$galleryTitle],
        "SELECT id, judul FROM galeri WHERE id = ?",
        [], 'judul', $galleryTitle
    );

    $concentrationName = "CI public concentration {$suffix}";
    insertAndCheck(
        $pdo, 'konsentrasi',
        "INSERT INTO konsentrasi (kode, nama, status) VALUES (?, ?, 'aktif')",
        ["C{$suffix}", $concentrationName],
        "SELECT id, nama FROM konsentrasi WHERE status = 'aktif' AND id = ?",
        [], 'nama', $concentrationName
    );

    $achievementTitle = "CI public achievement {$suffix}";
    insertAndCheck(
        $pdo, 'prestasi',
        "INSERT INTO prestasi (judul, status) VALUES (?, 'aktif')",
        [$achievementTitle],
        "SELECT id, judul FROM prestasi WHERE status = 'aktif' AND id = ?",
        [], 'judul', $achievementTitle
    );

    $partnerName = "CI public partner {$suffix}";
    insertAndCheck(
        $pdo, 'mitra',
        "INSERT INTO mitra (nama, status) VALUES (?, 'aktif')",
        [$partnerName],
        "SELECT id, nama FROM mitra WHERE status = 'aktif' AND id = ?",
        [], 'nama', $partnerName
    );

    $documentTitle = "CI public document {$suffix}";
    insertAndCheck(
        $pdo, 'dokumen',
        "INSERT INTO dokumen (judul, kategori, filename, status) VALUES (?, 'penelitian', ?, 'aktif')",
        [$documentTitle, "ci-{$suffix}.pdf"],
        "SELECT id, judul, kategori FROM dokumen WHERE status = 'aktif' AND kategori = 'penelitian' AND id = ?",
        [], 'judul', $documentTitle
    );

    $testimonialName = "CI public testimonial {$suffix}";
    insertAndCheck(
        $pdo, 'testimoni',
        "INSERT INTO testimoni (nama, tahun_masuk, quote, status) VALUES (?, '2026', 'CI quote', 'approved')",
        [$testimonialName],
        "SELECT id, nama FROM testimoni WHERE status = 'approved' AND id = ?",
        [], 'nama', $testimonialName
    );

    // Website identity/settings are read through pengaturan and can be cached by the app.
    $settingKey = "ci_setting_{$suffix}";
    insertAndCheck(
        $pdo, 'pengaturan',
        "INSERT INTO pengaturan (key_name, value) VALUES (?, ?)",
        [$settingKey, 'before'],
        "SELECT id, key_name, value FROM pengaturan WHERE key_name = ? AND id = ?",
        [$settingKey], 'value', 'before'
    );
    $settingIdStmt = $pdo->prepare("SELECT id FROM pengaturan WHERE key_name = ? LIMIT 1");
    $settingIdStmt->execute([$settingKey]);
    $settingId = (int)$settingIdStmt->fetchColumn();
    $pdo->prepare("UPDATE pengaturan SET value = ? WHERE id = ?")->execute(['after', $settingId]);
    $setting = publicRecord($pdo, 'pengaturan update', "SELECT id, value FROM pengaturan WHERE key_name = ? AND id = ?", [$settingKey, $settingId]);
    assertPublicSync($setting !== null && $setting['value'] === 'after', 'pengaturan: public setting value was not updated.');

    $pdo->rollBack();
    echo "Public visibility smoke tests passed for 13 content/settings modules; news publication transition verified. All writes rolled back.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Public visibility smoke test failed: " . $e->getMessage() . "\n");
    exit(1);
}
