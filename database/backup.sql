-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 12, 2026 at 08:12 AM
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `nama_lengkap`, `email`, `role`, `foto`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$wqhYkVoVgzc/UHji.VwpZ.nzQqaEobK9tfirwas.QYuIfrxj1J.cG', 'Super Admin', 'admin@informatika.ac.id', 'superadmin', 'default.png', '2026-10-12 06:50:26', '2026-10-11 11:41:49', '2026-10-11 23:50:26');

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `berita`
--

INSERT INTO `berita` (`id`, `judul`, `slug`, `kategori`, `ringkasan`, `konten`, `gambar`, `views`, `status`, `author_id`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 'Kuliah Tamu \"End-to-End AI: Python & Web Application\"', 'kuliah-tamu-end-to-end-ai', 'Workshop', 'Program Studi Informatika gelar workshop intensif tentang pengembangan AI end-to-end menggunakan Python.', '<p>Program Studi Informatika Telkom University Surabaya menggelar kuliah tamu berbasis workshop dengan tema \"End-to-End AI: Using Python and Web Application\". Acara ini menghadirkan praktisi industri yang berbagi pengalaman nyata dalam pengembangan sistem AI.</p><p>Peserta diajarkan proses lengkap dari pengolahan data, training model, hingga deployment ke aplikasi web.</p>', NULL, 0, 'published', NULL, '2024-11-19 10:00:00', '2026-10-11 11:41:49', '2026-10-11 13:04:53'),
(2, 'Pelatihan Keamanan Siber dan Literasi Digital', 'pelatihan-keamanan-siber', 'Pengabdian', 'Mahasiswa dan dosen mengadakan pelatihan keamanan siber untuk masyarakat umum.', '<p>Dalam rangka pengabdian masyarakat, Prodi Informatika mengadakan pelatihan keamanan siber untuk meningkatkan literasi digital masyarakat.</p>', NULL, 7, 'published', NULL, '2026-07-01 10:00:00', '2026-10-11 11:41:49', '2026-10-12 00:53:46'),
(3, 'Pengembangan Website Katalog untuk Percetakan Sangga Buana', 'website-katalog-sangga-buana', 'Proyek', 'Kolaborasi mahasiswa dalam proyek pengembangan sistem katalog digital untuk UMKM lokal.', '<p>Tim mahasiswa mengembangkan sistem katalog digital modern untuk UMKM percetakan lokal sebagai bentuk kontribusi nyata.</p>', NULL, 0, 'published', NULL, '2026-07-01 10:00:00', '2026-10-11 11:41:49', '2026-10-11 13:04:53');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faq`
--

INSERT INTO `faq` (`id`, `pertanyaan`, `jawaban`, `urutan`, `status`, `created_at`) VALUES
(1, 'Apa saja konsentrasi yang tersedia di Prodi Informatika?', 'Kami memiliki tiga konsentrasi: Data Science & Intelligent System (DSIS), Communication & Information Technology (CITI), dan Software Engineering & Algorithm (SEAL).', 1, 'aktif', '2026-10-11 11:41:50'),
(2, 'Bagaimana prospek kerja lulusan Informatika?', 'Lulusan kami berkarir sebagai AI Engineer, IT Consultant, System Analyst, Programmer, Network Engineer, dan banyak posisi teknologi lainnya.', 2, 'aktif', '2026-10-11 11:41:50'),
(3, 'Apakah ada program beasiswa?', 'Ya, kami menyediakan berbagai program beasiswa termasuk beasiswa prestasi akademik, beasiswa kurang mampu, dan beasiswa mitra industri.', 3, 'aktif', '2026-10-11 11:41:50');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mata_kuliah`
--

CREATE TABLE `mata_kuliah` (
  `id` int(11) NOT NULL,
  `semester` tinyint(4) NOT NULL COMMENT '1-8',
  `kode` varchar(20) NOT NULL COMMENT 'Kode mata kuliah (IF101, IF201, dst)',
  `nama` varchar(255) NOT NULL COMMENT 'Nama mata kuliah',
  `sks` tinyint(4) NOT NULL DEFAULT '3' COMMENT 'Jumlah SKS',
  `urutan` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Urutan tampilan',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `mata_kuliah`
--

INSERT INTO `mata_kuliah` (`id`, `semester`, `kode`, `nama`, `sks`, `urutan`, `created_at`, `updated_at`) VALUES
(1, 1, 'IF101', 'Dasar Pemrograman', 3, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(2, 1, 'IF102', 'Matematika Diskrit', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(3, 1, 'IF103', 'Pengantar Informatika', 3, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(4, 1, 'IF104', 'Bahasa Inggris', 2, 4, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(5, 1, 'IF105', 'Pendidikan Agama', 2, 5, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(6, 1, 'IF106', 'Pancasila', 2, 6, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(7, 2, 'IF201', 'Algoritma & Struktur Data', 3, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(8, 2, 'IF202', 'Basis Data', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(9, 2, 'IF203', 'Sistem Operasi', 3, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(10, 2, 'IF204', 'Statistika', 3, 4, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(11, 2, 'IF205', 'Kewarganegaraan', 2, 5, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(12, 2, 'IF206', 'Bahasa Indonesia', 2, 6, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(13, 3, 'IF301', 'Pemrograman Web', 3, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(14, 3, 'IF302', 'Jaringan Komputer', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(15, 3, 'IF303', 'Rekayasa Perangkat Lunak', 3, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(16, 3, 'IF304', 'Interaksi Manusia & Komputer', 3, 4, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(17, 3, 'IF305', 'Kalkulus', 3, 5, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(18, 4, 'IF401', 'Kecerdasan Buatan', 3, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(19, 4, 'IF402', 'Machine Learning', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(20, 4, 'IF403', 'Keamanan Siber', 3, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(21, 4, 'IF404', 'Manajemen Proyek TI', 3, 4, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(22, 4, 'IF405', 'Metodologi Penelitian', 2, 5, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(23, 5, 'IF501', 'Deep Learning', 3, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(24, 5, 'IF502', 'Cloud Computing', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(25, 5, 'IF503', 'Mobile Programming', 3, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(26, 5, 'IF504', 'Data Mining', 3, 4, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(27, 5, 'IF505', 'Etika Profesi TI', 2, 5, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(28, 6, 'IF601', 'Magang / Studi Independen (MSIB)', 20, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(29, 6, 'IF602', 'Pemrograman Mobile Lanjut', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(30, 6, 'IF603', 'Big Data Analytics', 3, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(31, 7, 'IF701', 'Skripsi / Tugas Akhir', 6, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(32, 7, 'IF702', 'Topik Khusus Informatika', 3, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(33, 7, 'IF703', 'Kewirausahaan TI', 2, 3, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(34, 8, 'IF801', 'Skripsi / Tugas Akhir (Lanjutan)', 6, 1, '2026-10-12 01:33:18', '2026-10-12 01:33:18'),
(35, 8, 'IF802', 'Seminar Hasil', 2, 2, '2026-10-12 01:33:18', '2026-10-12 01:33:18');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `key_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `key_name`, `value`, `updated_at`) VALUES
(1, 'site_name', 'Program Studi Informatika', '2026-10-11 11:41:50'),
(2, 'tagline', 'Mencetak Inovator Digital Masa Depan', '2026-10-11 11:41:50'),
(3, 'email', 'info@informatikapro.ac.id', '2026-10-11 11:41:50'),
(4, 'telepon', '(021) 1234-5678', '2026-10-11 11:41:50'),
(5, 'alamat', 'Jl. Informatika No. 1, Jakarta', '2026-10-11 11:41:50'),
(6, 'whatsapp', '6281234567890', '2026-10-11 11:41:50');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengumuman`
--

INSERT INTO `pengumuman` (`id`, `judul`, `konten`, `prioritas`, `status`, `tanggal_mulai`, `tanggal_berakhir`, `created_at`) VALUES
(1, 'Pendaftaran Mahasiswa Baru 2026/2027', 'Pendaftaran mahasiswa baru telah dibuka! Segera daftarkan diri Anda melalui jalur prestasi maupun reguler.', 'penting', 'aktif', NULL, NULL, '2026-10-11 11:41:50'),
(2, 'Jadwal UAS Semester Ganjil 2026', 'Ujian Akhir Semester akan dilaksanakan mulai tanggal 15 Desember 2026. Persiapkan diri Anda dengan baik.', 'normal', 'aktif', NULL, NULL, '2026-10-11 11:41:50');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `testimoni`
--

INSERT INTO `testimoni` (`id`, `nama`, `tahun_masuk`, `avatar`, `quote`, `status`, `urutan`, `created_at`) VALUES
(1, 'Ivan Sholana', 'Mahasiswa 2021', 'https://i.pravatar.cc/150?img=12', 'Saya merasa sangat diberkahi dengan kesempatan mengasah kemampuan dan kemandirian dalam berkembang di segala kondisi.', 'approved', 0, '2026-10-11 11:41:50'),
(2, 'Whisnumurty Galih', 'Mahasiswa 2021', 'https://i.pravatar.cc/150?img=33', 'Kampus sangat mendukung pengembangan mahasiswa melalui pembimbingan dosen, dukungan fasilitas, dan suasana kolaboratif.', 'approved', 0, '2026-10-11 11:41:50'),
(3, 'Gede Satyamahinsa', 'Mahasiswa 2022', 'https://i.pravatar.cc/150?img=52', 'Dosen aktif dalam penelitian dan sering melibatkan mahasiswa. Kesempatan berharga untuk belajar langsung dari pengalaman.', 'approved', 0, '2026-10-11 11:41:50'),
(4, 'Shinta Putri', 'Mahasiswa 2023', 'https://i.pravatar.cc/150?img=45', 'Dukungan luar biasa dari para dosen. Mereka tidak hanya memberikan ilmu di kelas, tapi selalu siap membantu di luar jam kuliah.', 'approved', 0, '2026-10-11 11:41:50'),
(5, 'Ida Ayu Putri', 'Mahasiswa 2024', 'https://i.pravatar.cc/150?img=20', 'Informatika mengajarkan bahwa matematika bisa dibuat lebih sederhana dengan pemrograman. Logika terasa lebih jelas dan mudah.', 'approved', 0, '2026-10-11 11:41:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `berita`
--
ALTER TABLE `berita`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nidn` (`nidn`);

--
-- Indexes for table `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `galeri`
--
ALTER TABLE `galeri`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kontak`
--
ALTER TABLE `kontak`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_semester` (`semester`),
  ADD KEY `idx_urutan` (`semester`,`urutan`);

--
-- Indexes for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Indexes for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `testimoni`
--
ALTER TABLE `testimoni`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `berita`
--
ALTER TABLE `berita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faq`
--
ALTER TABLE `faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `galeri`
--
ALTER TABLE `galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `testimoni`
--
ALTER TABLE `testimoni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `berita`
--
ALTER TABLE `berita`
  ADD CONSTRAINT `berita_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
