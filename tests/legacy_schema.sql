-- Minimal representative legacy tables before admin CRUD compatibility changes.
-- Used only by CI to prove the migration can upgrade an older database schema.
CREATE TABLE agenda (
  id INT UNSIGNED NOT NULL PRIMARY KEY,
  judul VARCHAR(255) NOT NULL,
  tanggal_mulai DATETIME NOT NULL,
  status ENUM('akan_datang','berlangsung','selesai','batal') NOT NULL DEFAULT 'akan_datang',
  gambar VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pengumuman (
  id INT NOT NULL PRIMARY KEY,
  judul VARCHAR(255) NOT NULL,
  konten TEXT NOT NULL,
  prioritas ENUM('rendah','normal','tinggi','penting') DEFAULT 'normal',
  status ENUM('aktif','nonaktif') DEFAULT 'aktif',
  tanggal_mulai DATE DEFAULT NULL,
  tanggal_berakhir DATE DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dosen (
  id INT NOT NULL PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  pendidikan_terakhir VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fasilitas (id INT UNSIGNED NOT NULL PRIMARY KEY, nama VARCHAR(150) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE konsentrasi (id INT UNSIGNED NOT NULL PRIMARY KEY, kode VARCHAR(20) NOT NULL, nama VARCHAR(150) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE prestasi (id INT UNSIGNED NOT NULL PRIMARY KEY, judul VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE mitra (id INT UNSIGNED NOT NULL PRIMARY KEY, nama VARCHAR(150) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE dokumen (id INT UNSIGNED NOT NULL PRIMARY KEY, judul VARCHAR(255) NOT NULL, kategori ENUM('kurikulum','akademik','kemahasiswaan','sop','lainnya') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'akademik', filename VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE alumni (id INT UNSIGNED NOT NULL PRIMARY KEY, nama VARCHAR(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE api_keys (id INT UNSIGNED NOT NULL PRIMARY KEY, admin_id INT NOT NULL, name VARCHAR(100) NOT NULL, key_prefix VARCHAR(12) NOT NULL, key_hash VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE email_templates (id INT UNSIGNED NOT NULL PRIMARY KEY, kode VARCHAR(50) NOT NULL, nama VARCHAR(100) NOT NULL, subjek VARCHAR(255) NOT NULL, body MEDIUMTEXT NOT NULL, status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif', updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
