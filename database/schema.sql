-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 12, 2026 at 09:02 AM
-- Server version: 5.7.39
-- PHP Version: 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `prodi-informatika`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int(11) UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('superadmin','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'admin',
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default.png',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('aktif','nonaktif','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_secret` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failed_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No default administrator is inserted here.
-- After importing this schema and configuring .env, run:
-- php database/create-admin.php

-- --------------------------------------------------------

--
-- Table structure for table `agenda`
--

CREATE TABLE `agenda` (
  `id` int(11) UNSIGNED NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `lokasi` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kategori` enum('akademik','kemahasiswaan','riset','lainnya') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'akademik',
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime DEFAULT NULL,
  `status` enum('akan_datang','berlangsung','selesai','batal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'akan_datang',
  `gambar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `agenda`
--

INSERT INTO `agenda` (`id`, `judul`, `deskripsi`, `lokasi`, `kategori`, `tanggal_mulai`, `tanggal_selesai`, `status`, `gambar`, `created_at`) VALUES
(1, 'Seminar Nasional AI 2026', 'Seminar tahunan menghadirkan pakar AI industri dan akademik.', 'Auditorium Kampus', 'akademik', '2026-11-05 08:00:00', '2026-11-05 16:00:00', 'akan_datang', NULL, '2026-10-12 08:59:10'),
(2, 'Yudisium Periode I', 'Pengumuman kelulusan dan pelepasan lulusan periode I.', 'Auditorium Kampus', 'akademik', '2026-11-20 09:00:00', '2026-11-20 12:00:00', 'akan_datang', NULL, '2026-10-12 08:59:10'),
(3, 'Penutupan Pendaftaran MABA Gel. 2', 'Batas akhir unggah berkas pendaftaran gelombang kedua.', 'Online', 'akademik', '2026-12-15 23:59:00', NULL, 'akan_datang', NULL, '2026-10-12 08:59:10'),
(4, 'Ujian Akhir Semester Ganjil', 'Pelaksanaan UAS serentak seluruh mata kuliah.', 'Seluruh ruang kelas', 'akademik', '2026-12-15 07:00:00', '2026-12-22 18:00:00', 'akan_datang', NULL, '2026-10-12 08:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `alumni`
--

CREATE TABLE `alumni` (
  `id` int(11) UNSIGNED NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun_lulus` smallint(5) UNSIGNED DEFAULT NULL,
  `pekerjaan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `perusahaan` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quote` text COLLATE utf8mb4_unicode_ci,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `alumni`
--

INSERT INTO `alumni` (`id`, `nama`, `tahun_lulus`, `pekerjaan`, `perusahaan`, `foto`, `linkedin`, `quote`, `status`, `created_at`) VALUES
(1, 'Raka Pratama', 2020, 'Machine Learning Engineer', 'GoTo Group', NULL, NULL, 'Kurikulum berbasis proyek membuat saya siap kerja sejak hari pertama.', 'aktif', '2026-10-12 08:59:10'),
(2, 'Dian Puspita', 2021, 'Data Analyst', 'Telkom Indonesia', NULL, NULL, 'Dosen-dosen sangat suportif membimbing riset hingga publikasi.', 'aktif', '2026-10-12 08:59:10'),
(3, 'Fajar Nugroho', 2019, 'Co-Founder & CTO', 'Startup EdTech Lokal', NULL, NULL, 'Kewirausahaan TI yang saya pelajari jadi bekal membangun perusahaan.', 'aktif', '2026-10-12 08:59:10'),
(4, 'Luh Putu Ari', 2022, 'Security Consultant', 'Firma Konsultan Siber', NULL, NULL, 'Lab cyber security kami setara standar industri.', 'aktif', '2026-10-12 08:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `analytics`
--

CREATE TABLE `analytics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `session_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `page_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `page_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visitor_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referrer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visit_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) UNSIGNED NOT NULL,
  `admin_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_prefix` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permissions` json DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `status` enum('aktif','dicabut') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `berita`
--

CREATE TABLE `berita` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ringkasan` text COLLATE utf8mb4_unicode_ci,
  `konten` longtext COLLATE utf8mb4_unicode_ci,
  `gambar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `views` int(11) DEFAULT '0',
  `status` enum('draft','published') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `author_id` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `meta_description` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `allow_comments` tinyint(1) NOT NULL DEFAULT '1',
  `sumber_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `berita`
--

INSERT INTO `berita` (`id`, `judul`, `slug`, `kategori`, `ringkasan`, `konten`, `gambar`, `views`, `status`, `author_id`, `published_at`, `created_at`, `updated_at`, `meta_description`, `tags`, `is_featured`, `allow_comments`, `sumber_url`) VALUES
(1, 'Kuliah Tamu \"End-to-End AI: Python & Web Application\"', 'kuliah-tamu-end-to-end-ai', 'Workshop', 'Program Studi Informatika gelar workshop intensif tentang pengembangan AI end-to-end menggunakan Python.', '<p>Program Studi Informatika Telkom University Surabaya menggelar kuliah tamu berbasis workshop dengan tema \"End-to-End AI: Using Python and Web Application\". Acara ini menghadirkan praktisi industri yang berbagi pengalaman nyata dalam pengembangan sistem AI.</p><p>Peserta diajarkan proses lengkap dari pengolahan data, training model, hingga deployment ke aplikasi web.</p>', NULL, 0, 'published', NULL, '2024-11-19 10:00:00', '2026-10-11 11:41:49', '2026-10-11 13:04:53', NULL, NULL, 0, 1, NULL),
(2, 'Pelatihan Keamanan Siber dan Literasi Digital', 'pelatihan-keamanan-siber', 'Pengabdian', 'Mahasiswa dan dosen mengadakan pelatihan keamanan siber untuk masyarakat umum.', '<p>Dalam rangka pengabdian masyarakat, Prodi Informatika mengadakan pelatihan keamanan siber untuk meningkatkan literasi digital masyarakat.</p>', NULL, 7, 'published', NULL, '2026-07-01 10:00:00', '2026-10-11 11:41:49', '2026-10-12 00:53:46', NULL, NULL, 0, 1, NULL),
(3, 'Pengembangan Website Katalog untuk Percetakan Sangga Buana', 'website-katalog-sangga-buana', 'Proyek', 'Kolaborasi mahasiswa dalam proyek pengembangan sistem katalog digital untuk UMKM lokal.', '<p>Tim mahasiswa mengembangkan sistem katalog digital modern untuk UMKM percetakan lokal sebagai bentuk kontribusi nyata.</p>', NULL, 0, 'published', NULL, '2026-07-01 10:00:00', '2026-10-11 11:41:49', '2026-10-11 13:04:53', NULL, NULL, 0, 1, NULL);

--
-- Triggers `berita`
--
DELIMITER $$
CREATE TRIGGER `trg_berita_before_insert` BEFORE INSERT ON `berita` FOR EACH ROW BEGIN
  IF NEW.`status` = 'published' AND NEW.`published_at` IS NULL THEN
    SET NEW.`published_at` = NOW();
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_berita_before_update` BEFORE UPDATE ON `berita` FOR EACH ROW BEGIN
  IF NEW.`status` = 'published' AND OLD.`status` <> 'published' AND NEW.`published_at` IS NULL THEN
    SET NEW.`published_at` = NOW();
  END IF;
  IF NEW.`status` = 'draft' AND OLD.`status` = 'published' THEN
    SET NEW.`published_at` = NULL;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) UNSIGNED NOT NULL,
  `berita_id` int(11) NOT NULL,
  `parent_id` int(11) UNSIGNED DEFAULT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `komentar` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','spam') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dokumen`
--

CREATE TABLE `dokumen` (
  `id` int(11) UNSIGNED NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` enum('kurikulum','akademik','kemahasiswaan','sop','lainnya') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'akademik',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe_file` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'pdf',
  `ukuran` int(11) UNSIGNED DEFAULT NULL,
  `downloads` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `deskripsi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dokumen`
--

INSERT INTO `dokumen` (`id`, `judul`, `kategori`, `filename`, `tipe_file`, `ukuran`, `downloads`, `deskripsi`, `status`, `created_at`) VALUES
(1, 'Kurikulum 2026 Program Studi Informatika', 'kurikulum', 'dokumen/kurikulum-2026.pdf', 'pdf', 1048576, 0, 'Dokumen resmi kurikulum OBE 8 semester.', 'aktif', '2026-10-12 08:59:10'),
(2, 'Panduan Akademik 2026/2027', 'akademik', 'dokumen/panduan-akademik.pdf', 'pdf', 2097152, 0, 'Peraturan akademik, KRS, dan evaluasi studi.', 'aktif', '2026-10-12 08:59:10'),
(3, 'Template Proposal Skripsi', 'akademik', 'dokumen/template-proposal.docx', 'docx', 512000, 0, 'Format resmi penulisan proposal tugas akhir.', 'aktif', '2026-10-12 08:59:10'),
(4, 'SOP Pengajuan Surat Keterangan', 'sop', 'dokumen/sop-surat.pdf', 'pdf', 204800, 0, 'Alur pengajuan surat untuk mahasiswa dan alumni.', 'aktif', '2026-10-12 08:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id` int(11) NOT NULL,
  `nidn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gelar` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keahlian` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profil` text COLLATE utf8mb4_unicode_ci,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `jabatan` enum('dosen','kaprodi','sekprodi','kordinator_lab','kepala_lab') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dosen',
  `jabatan_fungsional` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pendidikan_terakhir` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_scholar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scopus_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sinta_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sambutan` text COLLATE utf8mb4_unicode_ci,
  `urutan` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id`, `nidn`, `nama`, `gelar`, `keahlian`, `email`, `foto`, `profil`, `status`, `created_at`, `jabatan`, `jabatan_fungsional`, `pendidikan_terakhir`, `google_scholar`, `scopus_id`, `sinta_id`, `linkedin`, `sambutan`, `urutan`) VALUES
(1, '0712058001', 'Rina Kartika Sari', 'Dr., S.Kom., M.T.', 'Artificial Intelligence, Data Science', 'rina.sari@informatika.ac.id', NULL, 'Pakar AI dengan lebih dari 40 publikasi internasional dan aktif sebagai reviewer jurnal bereputasi.', 'aktif', '2026-10-12 08:59:10', 'kaprodi', 'Lektor Kepala', 'S3 Ilmu Komputer', NULL, NULL, NULL, NULL, 'Assalamualaikum warahmatullahi wabarakatuh, salam sejahtera bagi kita semua. Selamat datang di website resmi Program Studi Informatika. Kami berkomitmen mencetak lulusan yang tidak hanya unggul secara teknis, tetapi juga berkarakter, adaptif, dan siap memimpin transformasi digital bangsa. Salam inovatif!', 1),
(2, '0715038502', 'Budi Hartono', 'S.Kom., M.Kom., Ph.D.', 'Cyber Security, Kriptografi', 'budi.hartono@informatika.ac.id', NULL, 'Peneliti keamanan siber, konsultan keamanan informasi untuk beberapa BUMN.', 'aktif', '2026-10-12 08:59:10', 'sekprodi', 'Lektor', 'S3 Cyber Security', NULL, NULL, NULL, NULL, '', 2),
(3, '0720098803', 'Andi Prasetyo', 'S.Kom., M.Kom.', 'Software Engineering, Cloud Computing', 'andi.prasetyo@informatika.ac.id', NULL, 'Praktisi rekayasa perangkat lunak, mantan senior engineer di perusahaan teknologi nasional.', 'aktif', '2026-10-12 08:59:10', 'dosen', 'Asisten Ahli', 'S2 Ilmu Komputer', NULL, NULL, NULL, NULL, '', 3),
(4, '0705079004', 'Siti Rahmawati', 'S.Si., M.Stat.', 'Data Mining, Statistika', 'siti.rahmawati@informatika.ac.id', NULL, 'Ahli statistika dan data mining, pembimbing tim kompetisi data science mahasiswa.', 'aktif', '2026-10-12 08:59:10', 'dosen', 'Lektor', 'S2 Statistika', NULL, NULL, NULL, NULL, '', 4),
(5, '0718029205', 'Dewa Made Yoga', 'S.Kom., M.T.', 'Jaringan Komputer, IoT', 'dewa.yoga@informatika.ac.id', NULL, 'Fokus riset Internet of Things dan jaringan nirkabel generasi terbaru.', 'aktif', '2026-10-12 08:59:10', 'dosen', 'Asisten Ahli', 'S2 Teknik Elektro', NULL, NULL, NULL, NULL, '', 5),
(6, '0722069406', 'Maya Lestari', 'S.Kom., M.Kom.', 'Interaksi Manusia Komputer, UI/UX', 'maya.lestari@informatika.ac.id', NULL, 'Desainer UX dan pengajar interaksi manusia komputer, mentor program MSIB.', 'aktif', '2026-10-12 08:59:10', 'dosen', 'Asisten Ahli', 'S2 Informatika', NULL, NULL, NULL, NULL, '', 6);

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `to_email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_kode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','terkirim','gagal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) UNSIGNED NOT NULL,
  `kode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subjek` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `kode`, `nama`, `subjek`, `body`, `status`, `updated_at`) VALUES
(1, 'kontak_auto_reply', 'Balasan Otomatis Kontak', 'Terima kasih atas pesan Anda - {{ticket}}', '<p>Halo {{nama}},</p><p>Pesan Anda dengan nomor tiket <strong>{{ticket}}</strong> telah kami terima dan akan dibalas dalam 1x24 jam kerja.</p>', 'aktif', '2026-10-12 08:59:10'),
(2, 'pendaftaran_diterima', 'Pendaftaran Diterima', 'Selamat! Pendaftaran {{nomor}} Diterima', '<p>Halo {{nama}},</p><p>Selamat, pendaftaran <strong>{{nomor}}</strong> dinyatakan DITERIMA. Silakan cek langkah selanjutnya di halaman pendaftaran.</p>', 'aktif', '2026-10-12 08:59:10'),
(3, 'pendaftaran_ditolak', 'Pendaftaran Ditolak', 'Informasi Hasil Seleksi {{nomor}}', '<p>Halo {{nama}},</p><p>Pendaftaran <strong>{{nomor}}</strong> belum dapat kami terima periode ini. Tetap semangat!</p>', 'aktif', '2026-10-12 08:59:10'),
(4, 'newsletter_welcome', 'Selamat Datang Newsletter', 'Selamat bergabung di Newsletter Informatika Pro', '<p>Halo {{nama}},</p><p>Anda kini berlangganan newsletter kami.</p>', 'aktif', '2026-10-12 08:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `faq`
--

CREATE TABLE `faq` (
  `id` int(11) NOT NULL,
  `pertanyaan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jawaban` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `urutan` int(11) DEFAULT '0',
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `kategori` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Umum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faq`
--

INSERT INTO `faq` (`id`, `pertanyaan`, `jawaban`, `urutan`, `status`, `created_at`, `kategori`) VALUES
(1, 'Apa saja konsentrasi yang tersedia di Prodi Informatika?', 'Kami memiliki tiga konsentrasi: Data Science & Intelligent System (DSIS), Communication & Information Technology (CITI), dan Software Engineering & Algorithm (SEAL).', 1, 'aktif', '2026-10-11 11:41:50', 'Akademik'),
(2, 'Bagaimana prospek kerja lulusan Informatika?', 'Lulusan kami berkarir sebagai AI Engineer, IT Consultant, System Analyst, Programmer, Network Engineer, dan banyak posisi teknologi lainnya.', 2, 'aktif', '2026-10-11 11:41:50', 'Prospek Karir'),
(3, 'Apakah ada program beasiswa?', 'Ya, kami menyediakan berbagai program beasiswa termasuk beasiswa prestasi akademik, beasiswa kurang mampu, dan beasiswa mitra industri.', 3, 'aktif', '2026-10-11 11:41:50', 'Biaya & Beasiswa'),
(4, 'Bagaimana cara mendaftar sebagai mahasiswa baru?', 'Klik menu Daftar, isi formulir multi-langkah lengkap dengan berkas, lalu simpan nomor pendaftaran untuk memantau status seleksi.', 4, 'aktif', '2026-10-12 08:59:10', 'Pendaftaran'),
(5, 'Apakah tersedia kelas karyawan atau kelas malam?', 'Saat ini perkuliahan reguler dilaksanakan siang hari; kelas karyawan sedang dalam kajian dan akan diumumkan melalui halaman pengumuman.', 5, 'aktif', '2026-10-12 08:59:10', 'Akademik'),
(6, 'Berapa estimasi biaya kuliah per semester?', 'Biaya bervariasi sesuai SKS yang diambil. Rincian resmi dapat diminta melalui form kontak atau WhatsApp resmi prodi.', 6, 'aktif', '2026-10-12 08:59:10', 'Biaya & Beasiswa');

-- --------------------------------------------------------

--
-- Table structure for table `fasilitas`
--

CREATE TABLE `fasilitas` (
  `id` int(11) UNSIGNED NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` enum('laboratorium','ruangan','peralatan','lainnya') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'laboratorium',
  `lokasi` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ikon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fa-flask',
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `gambar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `urutan` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fasilitas`
--

INSERT INTO `fasilitas` (`id`, `nama`, `jenis`, `lokasi`, `ikon`, `deskripsi`, `gambar`, `status`, `urutan`, `created_at`) VALUES
(1, 'Lab AI & Data Science', 'laboratorium', 'Gedung A Lt. 2', 'fa-brain', '40 workstation GPU untuk pelatihan model dan riset data.', NULL, 'aktif', 1, '2026-10-12 08:59:10'),
(2, 'Lab Cyber Security', 'laboratorium', 'Gedung A Lt. 3', 'fa-shield-alt', 'Security operation center mini dengan sandbox serangan.', NULL, 'aktif', 2, '2026-10-12 08:59:10'),
(3, 'Lab Software Engineering', 'laboratorium', 'Gedung B Lt. 1', 'fa-code', 'Ruang pengembangan kolaboratif dengan perangkat CI/CD.', NULL, 'aktif', 3, '2026-10-12 08:59:10'),
(4, 'Lab Jaringan & Cloud', 'laboratorium', 'Gedung B Lt. 2', 'fa-network-wired', 'Perangkat router, switch, dan rack server cloud kampus.', NULL, 'aktif', 4, '2026-10-12 08:59:10'),
(5, 'Studio Multimedia', 'ruangan', 'Gedung C Lt. 1', 'fa-video', 'Studio produksi konten, podcast, dan kuliah daring.', NULL, 'aktif', 5, '2026-10-12 08:59:10'),
(6, 'Ruang Kolaborasi Mahasiswa', 'ruangan', 'Gedung C Lt. 2', 'fa-users', 'Co-working space terbuka untuk tim proyek dan UKM.', NULL, 'aktif', 6, '2026-10-12 08:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `galeri`
--

CREATE TABLE `galeri` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `gambar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `karir_paths`
--

CREATE TABLE `karir_paths` (
  `id` int(11) UNSIGNED NOT NULL,
  `jabatan` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ikon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fa-briefcase',
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `gaji_min` int(11) UNSIGNED DEFAULT NULL,
  `gaji_max` int(11) UNSIGNED DEFAULT NULL,
  `permintaan` enum('rendah','sedang','tinggi','sangat_tinggi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tinggi',
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `urutan` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `karir_paths`
--

INSERT INTO `karir_paths` (`id`, `jabatan`, `ikon`, `deskripsi`, `gaji_min`, `gaji_max`, `permintaan`, `status`, `urutan`, `created_at`) VALUES
(1, 'AI Engineer', 'fa-robot', 'Membangun dan melatih model kecerdasan buatan untuk produk digital.', 8000000, 20000000, 'sangat_tinggi', 'aktif', 1, '2026-10-12 08:59:10'),
(2, 'Data Scientist', 'fa-chart-line', 'Mengolah data besar menjadi insight bisnis yang dapat ditindaklanjuti.', 9000000, 22000000, 'sangat_tinggi', 'aktif', 2, '2026-10-12 08:59:10'),
(3, 'Software Engineer', 'fa-code', 'Merancang, mengembangkan, dan memelihara aplikasi perangkat lunak.', 7000000, 18000000, 'sangat_tinggi', 'aktif', 3, '2026-10-12 08:59:10'),
(4, 'Cyber Security Analyst', 'fa-shield-alt', 'Melindungi sistem dan data organisasi dari serangan siber.', 10000000, 25000000, 'tinggi', 'aktif', 4, '2026-10-12 08:59:10'),
(5, 'Cloud Architect', 'fa-cloud', 'Merancang infrastruktur cloud yang skalabel dan hemat biaya.', 12000000, 30000000, 'tinggi', 'aktif', 5, '2026-10-12 08:59:10'),
(6, 'System Analyst', 'fa-sitemap', 'Menerjemahkan kebutuhan bisnis menjadi spesifikasi sistem.', 7000000, 16000000, 'tinggi', 'aktif', 6, '2026-10-12 08:59:10'),
(7, 'Network Engineer', 'fa-network-wired', 'Membangun dan memelihara infrastruktur jaringan perusahaan.', 6500000, 15000000, 'sedang', 'aktif', 7, '2026-10-12 08:59:10'),
(8, 'IT Consultant', 'fa-briefcase', 'Memberikan solusi strategis teknologi untuk klien lintas industri.', 8000000, 20000000, 'tinggi', 'aktif', 8, '2026-10-12 08:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `konsentrasi`
--

CREATE TABLE `konsentrasi` (
  `id` int(11) UNSIGNED NOT NULL,
  `kode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `ikon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fa-graduation-cap',
  `warna` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#10B981',
  `urutan` int(11) NOT NULL DEFAULT '0',
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `konsentrasi`
--

INSERT INTO `konsentrasi` (`id`, `kode`, `nama`, `deskripsi`, `ikon`, `warna`, `urutan`, `status`, `created_at`) VALUES
(1, 'DSIS', 'Data Science & Intelligent System', 'Fokus pada kecerdasan buatan, machine learning, dan pengolahan data skala besar.', 'fa-brain', '#8B5CF6', 1, 'aktif', '2026-10-12 08:59:10'),
(2, 'CITI', 'Communication & Information Technology', 'Fokus pada jaringan, keamanan siber, dan teknologi komunikasi modern.', 'fa-network-wired', '#3B82F6', 2, 'aktif', '2026-10-12 08:59:10'),
(3, 'SEAL', 'Software Engineering & Algorithm', 'Fokus pada rekayasa perangkat lunak, arsitektur sistem, dan algoritma.', 'fa-code', '#10B981', 3, 'aktif', '2026-10-12 08:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `kontak`
--

CREATE TABLE `kontak` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telepon` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subjek` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pesan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('baru','dibaca','dibalas') COLLATE utf8mb4_unicode_ci DEFAULT 'baru',
  `ip_address` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ticket_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balasan` text COLLATE utf8mb4_unicode_ci,
  `dibaca_at` datetime DEFAULT NULL,
  `dibalas_at` datetime DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mata_kuliah`
--

CREATE TABLE `mata_kuliah` (
  `id` int(11) NOT NULL,
  `semester` tinyint(4) NOT NULL COMMENT '1-8',
  `kode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kode mata kuliah (IF101, IF201, dst)',
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nama mata kuliah',
  `sks` tinyint(4) NOT NULL DEFAULT '3' COMMENT 'Jumlah SKS',
  `urutan` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Urutan tampilan',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `jenis` enum('wajib','pilihan','wajib_pilihan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'wajib',
  `konsentrasi_id` int(11) UNSIGNED DEFAULT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mata_kuliah`
--

INSERT INTO `mata_kuliah` (`id`, `semester`, `kode`, `nama`, `sks`, `urutan`, `created_at`, `updated_at`, `jenis`, `konsentrasi_id`, `deskripsi`) VALUES
(1, 1, 'IF101', 'Dasar Pemrograman', 3, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(2, 1, 'IF102', 'Matematika Diskrit', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(3, 1, 'IF103', 'Pengantar Informatika', 3, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(4, 1, 'IF104', 'Bahasa Inggris', 2, 4, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(5, 1, 'IF105', 'Pendidikan Agama', 2, 5, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(6, 1, 'IF106', 'Pancasila', 2, 6, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(7, 2, 'IF201', 'Algoritma & Struktur Data', 3, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(8, 2, 'IF202', 'Basis Data', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(9, 2, 'IF203', 'Sistem Operasi', 3, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(10, 2, 'IF204', 'Statistika', 3, 4, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(11, 2, 'IF205', 'Kewarganegaraan', 2, 5, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(12, 2, 'IF206', 'Bahasa Indonesia', 2, 6, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(13, 3, 'IF301', 'Pemrograman Web', 3, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(14, 3, 'IF302', 'Jaringan Komputer', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(15, 3, 'IF303', 'Rekayasa Perangkat Lunak', 3, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(16, 3, 'IF304', 'Interaksi Manusia & Komputer', 3, 4, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(17, 3, 'IF305', 'Kalkulus', 3, 5, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(18, 4, 'IF401', 'Kecerdasan Buatan', 3, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(19, 4, 'IF402', 'Machine Learning', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(20, 4, 'IF403', 'Keamanan Siber', 3, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(21, 4, 'IF404', 'Manajemen Proyek TI', 3, 4, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(22, 4, 'IF405', 'Metodologi Penelitian', 2, 5, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(23, 5, 'IF501', 'Deep Learning', 3, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(24, 5, 'IF502', 'Cloud Computing', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(25, 5, 'IF503', 'Mobile Programming', 3, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(26, 5, 'IF504', 'Data Mining', 3, 4, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(27, 5, 'IF505', 'Etika Profesi TI', 2, 5, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(28, 6, 'IF601', 'Magang / Studi Independen (MSIB)', 20, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(29, 6, 'IF602', 'Pemrograman Mobile Lanjut', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(30, 6, 'IF603', 'Big Data Analytics', 3, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(31, 7, 'IF701', 'Skripsi / Tugas Akhir', 6, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(32, 7, 'IF702', 'Topik Khusus Informatika', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(33, 7, 'IF703', 'Kewirausahaan TI', 2, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(34, 8, 'IF801', 'Skripsi / Tugas Akhir (Lanjutan)', 6, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL),
(35, 8, 'IF802', 'Seminar Hasil', 2, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18', 'wajib', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(11) UNSIGNED NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ukuran` int(11) UNSIGNED DEFAULT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mitra`
--

CREATE TABLE `mitra` (
  `id` int(11) UNSIGNED NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` enum('industri','universitas','pemerintah','komunitas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'industri',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deskripsi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `urutan` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mitra`
--

INSERT INTO `mitra` (`id`, `nama`, `jenis`, `logo`, `website`, `deskripsi`, `status`, `urutan`, `created_at`) VALUES
(1, 'Telkom Indonesia', 'industri', NULL, 'https://telkom.co.id', 'Magang MBKM dan riset terapan jaringan', 'aktif', 1, '2026-10-12 08:59:10'),
(2, 'GoTo Group', 'industri', NULL, 'https://gotojek.com', 'Kuliah tamu praktisi dan rekrutmen lulusan', 'aktif', 2, '2026-10-12 08:59:10'),
(3, 'Dicoding Indonesia', 'industri', NULL, 'https://dicoding.com', 'Sertifikasi kompetensi pengembangan perangkat lunak', 'aktif', 3, '2026-10-12 08:59:10'),
(4, 'Cisco Networking Academy', 'industri', NULL, 'https://netacad.com', 'Akademi jaringan dan sertifikasi CCNA', 'aktif', 4, '2026-10-12 08:59:10'),
(5, 'AWS Academy', 'industri', NULL, 'https://aws.amazon.com', 'Kurikulum cloud computing bersertifikat', 'aktif', 5, '2026-10-12 08:59:10'),
(6, 'Dinas Kominfo Kota', 'pemerintah', NULL, NULL, 'Pengabdian masyarakat literasi digital', 'aktif', 6, '2026-10-12 08:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','aktif','unsubscribed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `tipe` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pesan` text COLLATE utf8mb4_unicode_ci,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pendaftaran`
--

CREATE TABLE `pendaftaran` (
  `id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telepon` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `asal_sekolah` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jurusan_sekolah` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nilai_rata_rata` decimal(4,2) DEFAULT NULL,
  `jalur_pendaftaran` enum('reguler','prestasi','beasiswa') COLLATE utf8mb4_unicode_ci DEFAULT 'reguler',
  `status` enum('pending','verified','accepted','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nomor_pendaftaran` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tahun_akademik` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esai_beasiswa` text COLLATE utf8mb4_unicode_ci,
  `berkas_ktp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `berkas_rapor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sumber_info` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `key_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `group_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'umum',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `key_name`, `value`, `updated_at`, `group_name`, `type`, `label`) VALUES
(1, 'site_name', 'Program Studi Informatika', '2026-10-12 08:59:10', 'identitas', 'text', 'Nama Situs'),
(2, 'tagline', 'Mencetak Inovator Digital Masa Depan', '2026-10-12 08:59:10', 'identitas', 'text', 'Tagline'),
(3, 'email', 'info@informatikapro.ac.id', '2026-10-12 08:59:10', 'kontak', 'text', 'Email Resmi'),
(4, 'telepon', '(021) 1234-5678', '2026-10-12 08:59:10', 'kontak', 'text', 'Telepon'),
(5, 'alamat', 'Jl. Informatika No. 1, Jakarta', '2026-10-12 08:59:10', 'kontak', 'textarea', 'Alamat'),
(6, 'whatsapp', '6281234567890', '2026-10-12 08:59:10', 'kontak', 'text', 'Nomor WhatsApp'),
(7, 'instagram', 'https://instagram.com/informatikapro', '2026-10-12 08:59:10', 'sosial', 'url', 'Instagram'),
(8, 'youtube', 'https://youtube.com/@informatikapro', '2026-10-12 08:59:10', 'sosial', 'url', 'YouTube'),
(9, 'linkedin', 'https://linkedin.com/company/informatikapro', '2026-10-12 08:59:10', 'sosial', 'url', 'LinkedIn'),
(10, 'akreditasi', 'Baik Sekali', '2026-10-12 08:59:10', 'identitas', 'text', 'Peringkat Akreditasi'),
(11, 'visi', 'Menjadi program studi Informatika unggulan yang menghasilkan inovator digital berdaya saing global pada tahun 2030.', '2026-10-12 08:59:10', 'profil', 'textarea', 'Visi'),
(12, 'misi', '1) Pendidikan berbasis proyek dan industri. 2) Riset AI dan keamanan siber. 3) Kemitraan global untuk mahasiswa dan dosen.', '2026-10-12 08:59:10', 'profil', 'textarea', 'Misi'),
(13, 'statistik_mahasiswa', '518', '2026-10-12 08:59:10', 'statistik', 'number', 'Jumlah Mahasiswa'),
(14, 'statistik_publikasi', '158', '2026-10-12 08:59:10', 'statistik', 'number', 'Jumlah Publikasi'),
(15, 'statistik_dosen', '25', '2026-10-12 08:59:10', 'statistik', 'number', 'Jumlah Dosen'),
(16, 'statistik_mitra', '40', '2026-10-12 08:59:10', 'statistik', 'number', 'Jumlah Mitra'),
(17, 'jam_layanan', 'Senin - Jumat, 08.00 - 16.00 WIB', '2026-10-12 08:59:10', 'kontak', 'text', 'Jam Layanan'),
(18, 'footer_text', 'Hak Cipta 2026 Program Studi Informatika.', '2026-10-12 08:59:10', 'identitas', 'textarea', 'Teks Footer'),
(19, 'seo_keywords', 'informatika, teknik informatika, kampus IT, AI engineer, software engineer', '2026-10-12 08:59:10', 'seo', 'textarea', 'Meta Keywords'),
(20, 'seo_description', 'Program Studi Informatika terdepan yang mencetak AI Engineer, IT Consultant, dan Software Engineer berkualitas.', '2026-10-12 08:59:10', 'seo', 'textarea', 'Meta Description'),
(21, 'pendaftaran_dibuka', '1', '2026-10-12 08:59:10', 'pendaftaran', 'boolean', 'Status Pendaftaran'),
(22, 'tahun_akademik', '2026/2027', '2026-10-12 08:59:10', 'pendaftaran', 'text', 'Tahun Akademik Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `konten` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `prioritas` enum('rendah','normal','tinggi','penting') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci DEFAULT 'aktif',
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_berakhir` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target` enum('semua','mahasiswa','dosen','staff') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'semua',
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengumuman`
--

INSERT INTO `pengumuman` (`id`, `judul`, `konten`, `prioritas`, `status`, `tanggal_mulai`, `tanggal_berakhir`, `created_at`, `link`, `target`, `is_pinned`) VALUES
(1, 'Pendaftaran Mahasiswa Baru 2026/2027', 'Pendaftaran mahasiswa baru telah dibuka! Segera daftarkan diri Anda melalui jalur prestasi maupun reguler.', 'penting', 'aktif', NULL, NULL, '2026-10-11 11:41:50', NULL, 'semua', 0),
(2, 'Jadwal UAS Semester Ganjil 2026', 'Ujian Akhir Semester akan dilaksanakan mulai tanggal 15 Desember 2026. Persiapkan diri Anda dengan baik.', 'normal', 'aktif', NULL, NULL, '2026-10-11 11:41:50', NULL, 'semua', 0);

-- --------------------------------------------------------

--
-- Table structure for table `prestasi`
--

CREATE TABLE `prestasi` (
  `id` int(11) UNSIGNED NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pencapaian` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kategori` enum('akademik','nonakademik','kompetisi','penelitian') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kompetisi',
  `tingkat` enum('internal','regional','nasional','internasional') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nasional',
  `nama_tim` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `gambar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `urutan` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prestasi`
--

INSERT INTO `prestasi` (`id`, `judul`, `pencapaian`, `kategori`, `tingkat`, `nama_tim`, `tahun`, `deskripsi`, `gambar`, `status`, `urutan`, `created_at`) VALUES
(1, 'Gemastik XV Divisi Pemrograman', 'Juara 1 Nasional', 'kompetisi', 'nasional', 'Tim Binary Storm', '2025', NULL, NULL, 'aktif', 1, '2026-10-12 08:59:10'),
(2, 'ON MIPA PT Bidang Komputer', 'Medali Emas', 'akademik', 'nasional', '1 mahasiswa', '2025', NULL, NULL, 'aktif', 2, '2026-10-12 08:59:10'),
(3, 'Hackathon Indonesia AI Track', 'Winner', 'kompetisi', 'nasional', 'Tim Neural Ninja', '2026', NULL, NULL, 'aktif', 3, '2026-10-12 08:59:10'),
(4, 'International Conference on Computer Science', 'Best Paper Award', 'penelitian', 'internasional', 'Tim dosen & mahasiswa', '2026', NULL, NULL, 'aktif', 4, '2026-10-12 08:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) UNSIGNED NOT NULL,
  `admin_id` int(11) NOT NULL,
  `selector` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hashed_validator` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `severity` enum('info','warning','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `testimoni`
--

CREATE TABLE `testimoni` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun_masuk` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default.png',
  `quote` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `urutan` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role` enum('mahasiswa','alumni','mitra') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mahasiswa',
  `rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `sosial_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `testimoni`
--

INSERT INTO `testimoni` (`id`, `nama`, `tahun_masuk`, `avatar`, `quote`, `status`, `urutan`, `created_at`, `role`, `rating`, `sosial_url`) VALUES
(1, 'Ivan Sholana', 'Mahasiswa 2021', 'https://i.pravatar.cc/150?img=12', 'Saya merasa sangat diberkahi dengan kesempatan mengasah kemampuan dan kemandirian dalam berkembang di segala kondisi.', 'approved', 0, '2026-10-11 11:41:50', 'mahasiswa', NULL, NULL),
(2, 'Whisnumurty Galih', 'Mahasiswa 2021', 'https://i.pravatar.cc/150?img=33', 'Kampus sangat mendukung pengembangan mahasiswa melalui pembimbingan dosen, dukungan fasilitas, dan suasana kolaboratif.', 'approved', 0, '2026-10-11 11:41:50', 'mahasiswa', NULL, NULL),
(3, 'Gede Satyamahinsa', 'Mahasiswa 2022', 'https://i.pravatar.cc/150?img=52', 'Dosen aktif dalam penelitian dan sering melibatkan mahasiswa. Kesempatan berharga untuk belajar langsung dari pengalaman.', 'approved', 0, '2026-10-11 11:41:50', 'mahasiswa', NULL, NULL),
(4, 'Shinta Putri', 'Mahasiswa 2023', 'https://i.pravatar.cc/150?img=45', 'Dukungan luar biasa dari para dosen. Mereka tidak hanya memberikan ilmu di kelas, tapi selalu siap membantu di luar jam kuliah.', 'approved', 0, '2026-10-11 11:41:50', 'mahasiswa', NULL, NULL),
(5, 'Ida Ayu Putri', 'Mahasiswa 2024', 'https://i.pravatar.cc/150?img=20', 'Informatika mengajarkan bahwa matematika bisa dibuat lebih sederhana dengan pemrograman. Logika terasa lebih jelas dan mudah.', 'approved', 0, '2026-10-11 11:41:50', 'mahasiswa', NULL, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_berita_published`
-- (See below for the actual view)
--
CREATE TABLE `v_berita_published` (
`id` int(11)
,`judul` varchar(255)
,`slug` varchar(255)
,`kategori` varchar(50)
,`ringkasan` text
,`gambar` varchar(255)
,`views` int(11)
,`is_featured` tinyint(1)
,`published_at` datetime
,`author_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_dashboard_stats`
-- (See below for the actual view)
--
CREATE TABLE `v_dashboard_stats` (
`total_berita` bigint(21)
,`berita_published` bigint(21)
,`total_views` decimal(32,0)
,`komentar_pending` bigint(21)
,`total_testimoni` bigint(21)
,`total_pendaftaran` bigint(21)
,`pendaftaran_pending` bigint(21)
,`kontak_baru` bigint(21)
,`dosen_aktif` bigint(21)
,`subscriber_aktif` bigint(21)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_admin` (`admin_id`),
  ADD KEY `idx_activity_created` (`created_at`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uq_admins_email` (`email`);

--
-- Indexes for table `agenda`
--
ALTER TABLE `agenda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_agenda_tanggal` (`tanggal_mulai`);

--
-- Indexes for table `alumni`
--
ALTER TABLE `alumni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alumni_tahun` (`tahun_lulus`);

--
-- Indexes for table `analytics`
--
ALTER TABLE `analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_analytics_page` (`page_url`),
  ADD KEY `idx_analytics_time` (`visit_time`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_apikeys_admin` (`admin_id`);

--
-- Indexes for table `berita`
--
ALTER TABLE `berita`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `idx_berita_status` (`status`),
  ADD KEY `idx_berita_kategori` (`kategori`),
  ADD KEY `idx_berita_published` (`published_at`),
  ADD KEY `idx_berita_featured` (`is_featured`);
ALTER TABLE `berita` ADD FULLTEXT KEY `ft_berita_search` (`judul`,`ringkasan`,`konten`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comments_berita_status` (`berita_id`,`status`),
  ADD KEY `idx_comments_parent` (`parent_id`);

--
-- Indexes for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dokumen_kategori` (`kategori`);

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nidn` (`nidn`),
  ADD KEY `idx_dosen_status` (`status`),
  ADD KEY `idx_dosen_jabatan` (`jabatan`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emaillogs_status` (`status`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_template_kode` (`kode`);

--
-- Indexes for table `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_faq_kategori_urutan` (`kategori`,`urutan`),
  ADD KEY `idx_faq_status` (`status`);

--
-- Indexes for table `fasilitas`
--
ALTER TABLE `fasilitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fasilitas_jenis` (`jenis`);

--
-- Indexes for table `galeri`
--
ALTER TABLE `galeri`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_galeri_kategori` (`kategori`),
  ADD KEY `idx_galeri_tanggal` (`tanggal`);

--
-- Indexes for table `karir_paths`
--
ALTER TABLE `karir_paths`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `konsentrasi`
--
ALTER TABLE `konsentrasi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_konsentrasi_kode` (`kode`);

--
-- Indexes for table `kontak`
--
ALTER TABLE `kontak`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kontak_ticket` (`ticket_number`),
  ADD KEY `idx_kontak_status` (`status`),
  ADD KEY `idx_kontak_created` (`created_at`),
  ADD KEY `fk_kontak_replied` (`replied_by`);

--
-- Indexes for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mk_kode` (`kode`),
  ADD KEY `idx_semester` (`semester`),
  ADD KEY `idx_urutan` (`semester`,`urutan`),
  ADD KEY `fk_mk_konsentrasi` (`konsentrasi_id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_media_path` (`path`),
  ADD KEY `fk_media_uploader` (`uploaded_by`);

--
-- Indexes for table `mitra`
--
ALTER TABLE `mitra`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mitra_jenis` (`jenis`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_newsletter_email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_read` (`is_read`,`created_at`),
  ADD KEY `fk_notif_admin` (`admin_id`);

--
-- Indexes for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pendaftaran_nomor` (`nomor_pendaftaran`),
  ADD KEY `idx_pendaftaran_status` (`status`),
  ADD KEY `idx_pendaftaran_jalur` (`jalur_pendaftaran`),
  ADD KEY `fk_pendaftaran_reviewer` (`reviewed_by`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`),
  ADD KEY `idx_pengaturan_group` (`group_name`);

--
-- Indexes for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pengumuman_tayang` (`status`,`tanggal_berakhir`),
  ADD KEY `idx_pengumuman_prioritas` (`prioritas`);

--
-- Indexes for table `prestasi`
--
ALTER TABLE `prestasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prestasi_tahun` (`tahun`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_remember_selector` (`selector`),
  ADD KEY `idx_remember_admin` (`admin_id`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_security_event` (`event_type`),
  ADD KEY `idx_security_ip` (`ip_address`);

--
-- Indexes for table `testimoni`
--
ALTER TABLE `testimoni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_testimoni_status_urutan` (`status`,`urutan`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agenda`
--
ALTER TABLE `agenda`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `alumni`
--
ALTER TABLE `alumni`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `analytics`
--
ALTER TABLE `analytics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `berita`
--
ALTER TABLE `berita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `faq`
--
ALTER TABLE `faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fasilitas`
--
ALTER TABLE `fasilitas`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `galeri`
--
ALTER TABLE `galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `karir_paths`
--
ALTER TABLE `karir_paths`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `konsentrasi`
--
ALTER TABLE `konsentrasi`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kontak`
--
ALTER TABLE `kontak`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mitra`
--
ALTER TABLE `mitra`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `prestasi`
--
ALTER TABLE `prestasi`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `testimoni`
--
ALTER TABLE `testimoni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

-- --------------------------------------------------------

--
-- Structure for view `v_berita_published`
--
DROP TABLE IF EXISTS `v_berita_published`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_berita_published`  AS SELECT `b`.`id` AS `id`, `b`.`judul` AS `judul`, `b`.`slug` AS `slug`, `b`.`kategori` AS `kategori`, `b`.`ringkasan` AS `ringkasan`, `b`.`gambar` AS `gambar`, `b`.`views` AS `views`, `b`.`is_featured` AS `is_featured`, `b`.`published_at` AS `published_at`, `a`.`nama_lengkap` AS `author_name` FROM (`berita` `b` left join `admins` `a` on((`b`.`author_id` = `a`.`id`))) WHERE (`b`.`status` = 'published') ;

-- --------------------------------------------------------

--
-- Structure for view `v_dashboard_stats`
--
DROP TABLE IF EXISTS `v_dashboard_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_dashboard_stats`  AS SELECT (select count(0) from `berita`) AS `total_berita`, (select count(0) from `berita` where (`berita`.`status` = 'published')) AS `berita_published`, (select coalesce(sum(`berita`.`views`),0) from `berita`) AS `total_views`, (select count(0) from `comments` where (`comments`.`status` = 'pending')) AS `komentar_pending`, (select count(0) from `testimoni`) AS `total_testimoni`, (select count(0) from `pendaftaran`) AS `total_pendaftaran`, (select count(0) from `pendaftaran` where (`pendaftaran`.`status` = 'pending')) AS `pendaftaran_pending`, (select count(0) from `kontak` where (`kontak`.`status` = 'baru')) AS `kontak_baru`, (select count(0) from `dosen` where (`dosen`.`status` = 'aktif')) AS `dosen_aktif`, (select count(0) from `newsletter_subscribers` where (`newsletter_subscribers`.`status` = 'aktif')) AS `subscriber_aktif` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD CONSTRAINT `fk_apikeys_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `berita`
--
ALTER TABLE `berita`
  ADD CONSTRAINT `berita_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comments_berita` FOREIGN KEY (`berita_id`) REFERENCES `berita` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comments_parent` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kontak`
--
ALTER TABLE `kontak`
  ADD CONSTRAINT `fk_kontak_replied` FOREIGN KEY (`replied_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  ADD CONSTRAINT `fk_mk_konsentrasi` FOREIGN KEY (`konsentrasi_id`) REFERENCES `konsentrasi` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `fk_media_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD CONSTRAINT `fk_pendaftaran_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `fk_remember_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
