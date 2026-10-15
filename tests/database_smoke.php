<?php
declare(strict_types=1);

/**
 * MySQL smoke tests for fields used by admin CRUD.
 * All test writes are rolled back, so this never leaves sample rows behind.
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

function expectDatabase(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * Create, read, update, verify, and delete one row inside the surrounding transaction.
 * Table/SQL identifiers are literals from this test file, never external input.
 */
function roundTrip(
    PDO $pdo,
    string $table,
    string $insertSql,
    array $insertParams,
    array $before,
    string $updateSql,
    array $updateParams,
    array $after
): int {
    $pdo->prepare($insertSql)->execute($insertParams);
    $id = (int)$pdo->lastInsertId();
    expectDatabase($id > 0, "{$table}: INSERT did not return an auto-increment ID.");

    $query = $pdo->prepare("SELECT * FROM `{$table}` WHERE id = ?");
    $query->execute([$id]);
    $row = $query->fetch();
    expectDatabase(is_array($row), "{$table}: inserted row could not be read.");

    foreach ($before as $column => $expected) {
        expectDatabase(array_key_exists($column, $row), "{$table}: missing column {$column}.");
        if ($expected === '__NOT_NULL__') {
            expectDatabase($row[$column] !== null && $row[$column] !== '', "{$table}.{$column} should not be empty.");
        } else {
            expectDatabase((string)$row[$column] === (string)$expected, "{$table}.{$column} insert mismatch.");
        }
    }

    $updateParams[] = $id;
    $pdo->prepare($updateSql)->execute($updateParams);

    $query = $pdo->prepare("SELECT * FROM `{$table}` WHERE id = ?");
    $query->execute([$id]);
    $row = $query->fetch();
    expectDatabase(is_array($row), "{$table}: updated row could not be read.");

    foreach ($after as $column => $expected) {
        expectDatabase(array_key_exists($column, $row), "{$table}: missing column {$column} after update.");
        if ($expected === '__NOT_NULL__') {
            expectDatabase($row[$column] !== null && $row[$column] !== '', "{$table}.{$column} should not be empty after update.");
        } else {
            expectDatabase((string)$row[$column] === (string)$expected, "{$table}.{$column} update mismatch.");
        }
    }

    $pdo->prepare("DELETE FROM `{$table}` WHERE id = ?")->execute([$id]);
    return $id;
}

$suffix = bin2hex(random_bytes(5));
$pdo->beginTransaction();

try {
    roundTrip(
        $pdo, 'agenda',
        "INSERT INTO agenda (judul, tanggal_mulai, status, is_featured) VALUES (?, NOW(), 'akan_datang', ?)",
        ["CI agenda {$suffix}", 1],
        ['judul' => "CI agenda {$suffix}", 'is_featured' => 1],
        "UPDATE agenda SET is_featured = ?, updated_at = NOW() WHERE id = ?",
        [0],
        ['is_featured' => 0, 'updated_at' => '__NOT_NULL__']
    );

    roundTrip(
        $pdo, 'pengumuman',
        "INSERT INTO pengumuman (judul, konten, status) VALUES (?, ?, 'aktif')",
        ["CI announcement {$suffix}", 'Smoke test'],
        ['judul' => "CI announcement {$suffix}"],
        "UPDATE pengumuman SET status = ?, updated_at = NOW() WHERE id = ?",
        ['nonaktif'],
        ['status' => 'nonaktif', 'updated_at' => '__NOT_NULL__']
    );

    roundTrip(
        $pdo, 'dosen',
        "INSERT INTO dosen (nama, telepon, pendidikan, pendidikan_terakhir, status) VALUES (?, ?, ?, ?, 'aktif')",
        ["CI Lecturer {$suffix}", '081234567890', 'S2', 'S2'],
        ['telepon' => '081234567890', 'pendidikan' => 'S2', 'pendidikan_terakhir' => 'S2'],
        "UPDATE dosen SET telepon = ?, pendidikan = ?, pendidikan_terakhir = ?, updated_at = NOW() WHERE id = ?",
        ['081200000000', 'S3', 'S3'],
        ['telepon' => '081200000000', 'pendidikan' => 'S3', 'pendidikan_terakhir' => 'S3', 'updated_at' => '__NOT_NULL__']
    );

    roundTrip(
        $pdo, 'fasilitas',
        "INSERT INTO fasilitas (nama, kapasitas, status) VALUES (?, ?, 'aktif')",
        ["CI Facility {$suffix}", 30],
        ['kapasitas' => 30],
        "UPDATE fasilitas SET kapasitas = ?, updated_at = NOW() WHERE id = ?",
        [45],
        ['kapasitas' => 45, 'updated_at' => '__NOT_NULL__']
    );

    roundTrip(
        $pdo, 'konsentrasi',
        "INSERT INTO konsentrasi (kode, nama, status) VALUES (?, ?, 'aktif')",
        ["C{$suffix}", "CI Concentration {$suffix}"],
        ['nama' => "CI Concentration {$suffix}"],
        "UPDATE konsentrasi SET nama = ?, updated_at = NOW() WHERE id = ?",
        ["CI Updated Concentration {$suffix}"],
        ['nama' => "CI Updated Concentration {$suffix}", 'updated_at' => '__NOT_NULL__']
    );

    roundTrip(
        $pdo, 'prestasi',
        "INSERT INTO prestasi (judul, status) VALUES (?, 'aktif')",
        ["CI Achievement {$suffix}"],
        ['judul' => "CI Achievement {$suffix}"],
        "UPDATE prestasi SET judul = ?, updated_at = NOW() WHERE id = ?",
        ["CI Updated Achievement {$suffix}"],
        ['judul' => "CI Updated Achievement {$suffix}", 'updated_at' => '__NOT_NULL__']
    );

    roundTrip(
        $pdo, 'mitra',
        "INSERT INTO mitra (nama, status) VALUES (?, 'aktif')",
        ["CI Partner {$suffix}"],
        ['nama' => "CI Partner {$suffix}"],
        "UPDATE mitra SET nama = ?, updated_at = NOW() WHERE id = ?",
        ["CI Updated Partner {$suffix}"],
        ['nama' => "CI Updated Partner {$suffix}", 'updated_at' => '__NOT_NULL__']
    );

    roundTrip(
        $pdo, 'dokumen',
        "INSERT INTO dokumen (judul, kategori, filename, status) VALUES (?, 'penelitian', ?, 'aktif')",
        ["CI Document {$suffix}", "ci-{$suffix}.pdf"],
        ['kategori' => 'penelitian', 'filename' => "ci-{$suffix}.pdf"],
        "UPDATE dokumen SET judul = ?, updated_at = NOW() WHERE id = ?",
        ["CI Updated Document {$suffix}"],
        ['judul' => "CI Updated Document {$suffix}", 'updated_at' => '__NOT_NULL__']
    );

    roundTrip(
        $pdo, 'alumni',
        "INSERT INTO alumni (nama, email, telepon, ipk, kota, instagram, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif')",
        ["CI Alumni {$suffix}", "ci-{$suffix}@example.invalid", '081234567890', '3.50', 'Maumere', '@ci_test', 1],
        ['email' => "ci-{$suffix}@example.invalid", 'telepon' => '081234567890', 'ipk' => '3.50', 'kota' => 'Maumere', 'instagram' => '@ci_test', 'is_featured' => 1],
        "UPDATE alumni SET kota = ?, is_featured = ?, updated_at = NOW() WHERE id = ?",
        ['Ende', 0],
        ['kota' => 'Ende', 'is_featured' => 0, 'updated_at' => '__NOT_NULL__']
    );

    // API key needs an administrator row on schemas that enforce the owner relationship.
    $adminUser = "ci_smoke_{$suffix}";
    $pdo->prepare("INSERT INTO admins (username, password, nama_lengkap, role, status) VALUES (?, ?, ?, 'admin', 'aktif')")
        ->execute([$adminUser, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), 'CI Smoke Administrator']);
    $adminId = (int)$pdo->lastInsertId();
    $keyPrefix = 'ci' . $suffix;
    roundTrip(
        $pdo, 'api_keys',
        "INSERT INTO api_keys (admin_id, name, key_prefix, key_hash, expires_at, rate_limit, ip_whitelist, status) VALUES (?, ?, ?, ?, NULL, ?, ?, 'aktif')",
        [$adminId, "CI API Key {$suffix}", $keyPrefix, hash('sha256', $suffix), 1000, '127.0.0.1'],
        ['rate_limit' => 1000, 'ip_whitelist' => '127.0.0.1'],
        "UPDATE api_keys SET rate_limit = ?, ip_whitelist = ?, updated_at = NOW() WHERE id = ?",
        [2000, '127.0.0.1,10.0.0.1'],
        ['rate_limit' => 2000, 'ip_whitelist' => '127.0.0.1,10.0.0.1', 'updated_at' => '__NOT_NULL__']
    );
    $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$adminId]);

    roundTrip(
        $pdo, 'email_templates',
        "INSERT INTO email_templates (kode, nama, subjek, body, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'aktif', NOW(), NOW())",
        ["ci_{$suffix}", 'CI Smoke Template', 'Smoke test', 'Template body'],
        ['kode' => "ci_{$suffix}", 'created_at' => '__NOT_NULL__'],
        "UPDATE email_templates SET subjek = ?, updated_at = NOW() WHERE id = ?",
        ['Updated smoke test'],
        ['subjek' => 'Updated smoke test', 'updated_at' => '__NOT_NULL__']
    );

    $pdo->rollBack();
    echo "MySQL CRUD smoke tests passed (10 tables; all test writes rolled back).\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "MySQL CRUD smoke test failed: " . $e->getMessage() . "\n");
    exit(1);
}
