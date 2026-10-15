<?php
/**
 * Create the first superadmin using a one-time generated password.
 * Run only from a terminal after importing schema.sql and configuring .env:
 * php database/create-admin.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

function promptLine(string $label): string
{
    fwrite(STDOUT, $label);
    $value = fgets(STDIN);
    return trim($value === false ? '' : $value);
}

try {
    $db = getDB();
    $existing = (int) $db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($existing > 0) {
        fwrite(STDERR, "Pembuatan admin awal dibatalkan: tabel admins sudah berisi akun.\n");
        exit(1);
    }

    $username = promptLine('Username admin (3-50 karakter): ');
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
        throw new RuntimeException('Username hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung (3-50 karakter).');
    }

    $name = promptLine('Nama lengkap: ');
    if ($name === '' || strlen($name) > 100) {
        throw new RuntimeException('Nama lengkap wajib diisi dan maksimal 100 karakter.');
    }

    $email = promptLine('Email (boleh dikosongkan): ');
    if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100)) {
        throw new RuntimeException('Alamat email tidak valid.');
    }

    // No shared/default password: generate a unique secret and display it once in this terminal.
    $temporaryPassword = rtrim(strtr(base64_encode(random_bytes(30)), '+/', '-_'), '=');
    $hash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
    if ($hash === false) {
        throw new RuntimeException('Gagal membuat hash password.');
    }

    $stmt = $db->prepare(
        "INSERT INTO admins (username, password, nama_lengkap, email, role, status, failed_attempts, must_change_password)
         VALUES (?, ?, ?, ?, 'superadmin', 'aktif', 0, 0)"
    );
    $stmt->execute([$username, $hash, $name, $email === '' ? null : $email]);

    fwrite(STDOUT, "\nAkun superadmin berhasil dibuat. Simpan password ini sekarang; password tidak disimpan dalam bentuk teks.\n");
    fwrite(STDOUT, "Username : {$username}\n");
    fwrite(STDOUT, "Password : {$temporaryPassword}\n");
    fwrite(STDOUT, "Jangan membagikan password dan ganti setelah login pertama.\n");
} catch (Throwable $e) {
    fwrite(STDERR, "Pembuatan admin gagal: " . $e->getMessage() . "\n");
    exit(1);
}
