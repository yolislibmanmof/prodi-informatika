-- Additive, idempotent compatibility migration for admin CRUD fields.
-- Targeted for MySQL 5.7+ / MariaDB installations using this project schema.
-- Review and back up the database before applying in the target environment.
SET @current_schema := DATABASE();

SET @ddl_1 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'agenda'
     AND COLUMN_NAME = 'is_featured') = 0,
  'ALTER TABLE `agenda` ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE stmt_1 FROM @ddl_1;
EXECUTE stmt_1;
DEALLOCATE PREPARE stmt_1;

SET @ddl_2 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'agenda'
     AND COLUMN_NAME = 'updated_at') = 0,
  'ALTER TABLE `agenda` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt_2 FROM @ddl_2;
EXECUTE stmt_2;
DEALLOCATE PREPARE stmt_2;

SET @ddl_3 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'pengumuman'
     AND COLUMN_NAME = 'updated_at') = 0,
  'ALTER TABLE `pengumuman` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt_3 FROM @ddl_3;
EXECUTE stmt_3;
DEALLOCATE PREPARE stmt_3;

SET @ddl_4 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'dosen'
     AND COLUMN_NAME = 'telepon') = 0,
  'ALTER TABLE `dosen` ADD COLUMN `telepon` VARCHAR(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt_4 FROM @ddl_4;
EXECUTE stmt_4;
DEALLOCATE PREPARE stmt_4;

SET @ddl_5 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'dosen'
     AND COLUMN_NAME = 'pendidikan') = 0,
  'ALTER TABLE `dosen` ADD COLUMN `pendidikan` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt_5 FROM @ddl_5;
EXECUTE stmt_5;
DEALLOCATE PREPARE stmt_5;

SET @ddl_6 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'dosen'
     AND COLUMN_NAME = 'updated_at') = 0,
  'ALTER TABLE `dosen` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt_6 FROM @ddl_6;
EXECUTE stmt_6;
DEALLOCATE PREPARE stmt_6;

SET @ddl_7 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'fasilitas'
     AND COLUMN_NAME = 'kapasitas') = 0,
  'ALTER TABLE `fasilitas` ADD COLUMN `kapasitas` INT(10) UNSIGNED DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt_7 FROM @ddl_7;
EXECUTE stmt_7;
DEALLOCATE PREPARE stmt_7;

SET @ddl_8 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'fasilitas'
     AND COLUMN_NAME = 'updated_at') = 0,
  'ALTER TABLE `fasilitas` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt_8 FROM @ddl_8;
EXECUTE stmt_8;
DEALLOCATE PREPARE stmt_8;

SET @ddl_9 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'konsentrasi'
     AND COLUMN_NAME = 'updated_at') = 0,
  'ALTER TABLE `konsentrasi` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt_9 FROM @ddl_9;
EXECUTE stmt_9;
DEALLOCATE PREPARE stmt_9;

SET @ddl_10 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'prestasi'
     AND COLUMN_NAME = 'updated_at') = 0,
  'ALTER TABLE `prestasi` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt_10 FROM @ddl_10;
EXECUTE stmt_10;
DEALLOCATE PREPARE stmt_10;

SET @ddl_11 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'mitra'
     AND COLUMN_NAME = 'updated_at') = 0,
  'ALTER TABLE `mitra` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt_11 FROM @ddl_11;
EXECUTE stmt_11;
DEALLOCATE PREPARE stmt_11;

SET @ddl_12 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'dokumen'
     AND COLUMN_NAME = 'updated_at') = 0,
  'ALTER TABLE `dokumen` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt_12 FROM @ddl_12;
EXECUTE stmt_12;
DEALLOCATE PREPARE stmt_12;

SET @ddl_13 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'alumni'
     AND COLUMN_NAME = 'email') = 0,
  'ALTER TABLE `alumni` ADD COLUMN `email` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt_13 FROM @ddl_13;
EXECUTE stmt_13;
DEALLOCATE PREPARE stmt_13;

SET @ddl_14 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'alumni'
     AND COLUMN_NAME = 'telepon') = 0,
  'ALTER TABLE `alumni` ADD COLUMN `telepon` VARCHAR(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt_14 FROM @ddl_14;
EXECUTE stmt_14;
DEALLOCATE PREPARE stmt_14;

SET @ddl_15 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'alumni'
     AND COLUMN_NAME = 'ipk') = 0,
  'ALTER TABLE `alumni` ADD COLUMN `ipk` DECIMAL(3,2) DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt_15 FROM @ddl_15;
EXECUTE stmt_15;
DEALLOCATE PREPARE stmt_15;

SET @ddl_16 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'alumni'
     AND COLUMN_NAME = 'kota') = 0,
  'ALTER TABLE `alumni` ADD COLUMN `kota` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt_16 FROM @ddl_16;
EXECUTE stmt_16;
DEALLOCATE PREPARE stmt_16;

SET @ddl_17 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'alumni'
     AND COLUMN_NAME = 'instagram') = 0,
  'ALTER TABLE `alumni` ADD COLUMN `instagram` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt_17 FROM @ddl_17;
EXECUTE stmt_17;
DEALLOCATE PREPARE stmt_17;

SET @ddl_18 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'alumni'
     AND COLUMN_NAME = 'is_featured') = 0,
  'ALTER TABLE `alumni` ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE stmt_18 FROM @ddl_18;
EXECUTE stmt_18;
DEALLOCATE PREPARE stmt_18;

SET @ddl_19 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'alumni'
     AND COLUMN_NAME = 'updated_at') = 0,
  'ALTER TABLE `alumni` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt_19 FROM @ddl_19;
EXECUTE stmt_19;
DEALLOCATE PREPARE stmt_19;

SET @ddl_20 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'api_keys'
     AND COLUMN_NAME = 'rate_limit') = 0,
  'ALTER TABLE `api_keys` ADD COLUMN `rate_limit` INT(10) UNSIGNED NOT NULL DEFAULT 1000',
  'SELECT 1'
);
PREPARE stmt_20 FROM @ddl_20;
EXECUTE stmt_20;
DEALLOCATE PREPARE stmt_20;

SET @ddl_21 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'api_keys'
     AND COLUMN_NAME = 'ip_whitelist') = 0,
  'ALTER TABLE `api_keys` ADD COLUMN `ip_whitelist` VARCHAR(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt_21 FROM @ddl_21;
EXECUTE stmt_21;
DEALLOCATE PREPARE stmt_21;

SET @ddl_22 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'api_keys'
     AND COLUMN_NAME = 'updated_at') = 0,
  'ALTER TABLE `api_keys` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt_22 FROM @ddl_22;
EXECUTE stmt_22;
DEALLOCATE PREPARE stmt_22;

SET @ddl_23 := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @current_schema
     AND TABLE_NAME = 'email_templates'
     AND COLUMN_NAME = 'created_at') = 0,
  'ALTER TABLE `email_templates` ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt_23 FROM @ddl_23;
EXECUTE stmt_23;
DEALLOCATE PREPARE stmt_23;

-- Keep the admin-facing education field compatible with the legacy public-facing column.
UPDATE dosen
SET pendidikan = pendidikan_terakhir
WHERE (pendidikan IS NULL OR pendidikan = '')
  AND pendidikan_terakhir IS NOT NULL
  AND pendidikan_terakhir <> '';

-- Allow the "Penelitian" category offered by the admin document form.
-- Widen the existing ENUM only when that value is not already present.
SET @dokumen_category_type := (
  SELECT COLUMN_TYPE
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @current_schema
    AND TABLE_NAME = 'dokumen'
    AND COLUMN_NAME = 'kategori'
  LIMIT 1
);
SET @dokumen_category_ddl := IF(
  @dokumen_category_type IS NOT NULL
    AND LOCATE('penelitian', @dokumen_category_type) = 0,
  'ALTER TABLE `dokumen` MODIFY COLUMN `kategori` ENUM(''kurikulum'',''akademik'',''kemahasiswaan'',''sop'',''penelitian'',''lainnya'') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''akademik''',
  'SELECT 1'
);
PREPARE stmt_24 FROM @dokumen_category_ddl;
EXECUTE stmt_24;
DEALLOCATE PREPARE stmt_24;

-- Verification query: every expected column should return a row.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND (
    (TABLE_NAME = 'agenda' AND COLUMN_NAME IN ('is_featured','updated_at'))
    OR (TABLE_NAME = 'pengumuman' AND COLUMN_NAME = 'updated_at')
    OR (TABLE_NAME = 'dosen' AND COLUMN_NAME IN ('telepon','pendidikan','updated_at'))
    OR (TABLE_NAME = 'fasilitas' AND COLUMN_NAME IN ('kapasitas','updated_at'))
    OR (TABLE_NAME IN ('konsentrasi','prestasi','mitra','dokumen') AND COLUMN_NAME = 'updated_at')
    OR (TABLE_NAME = 'alumni' AND COLUMN_NAME IN ('email','telepon','ipk','kota','instagram','is_featured','updated_at'))
    OR (TABLE_NAME = 'api_keys' AND COLUMN_NAME IN ('rate_limit','ip_whitelist','updated_at'))
    OR (TABLE_NAME = 'email_templates' AND COLUMN_NAME = 'created_at')
  )
ORDER BY TABLE_NAME, COLUMN_NAME;
