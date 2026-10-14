<?php
/**
 * Legacy seed entrypoint retained for compatibility.
 * The old script attempted to load a missing seed.sql and documented a shared admin password.
 * Setup is now explicit and avoids shared/default credentials.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

fwrite(STDOUT, "Seeder lama dinonaktifkan agar tidak membuat akun dengan password bawaan.\n");
fwrite(STDOUT, "1. Import database/schema.sql.\n");
fwrite(STDOUT, "2. Salin .env.example menjadi .env dan isi konfigurasi database.\n");
fwrite(STDOUT, "3. Jalankan: php database/create-admin.php\n");
