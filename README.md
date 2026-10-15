# 🎓 Informatika Pro

Website resmi Program Studi Informatika dengan panel administrasi modern dan sistem keamanan berlapis.

## ✨ Fitur Utama

### 🌐 Halaman Publik
- Beranda dengan Matrix Rain Loader
- Profil, Kurikulum, Dosen, Fasilitas
- Berita, Pengumuman, Agenda, Galeri
- Prestasi, Prospek Karir, Alumni, Mitra
- Dokumen & Unduhan, FAQ, Testimoni
- Form Kontak dengan anti-spam
- Newsletter subscription

### 🔐 Panel Admin (28+ Modul)
- Dashboard dengan analitik real-time
- CRUD lengkap untuk semua konten
- Media Library terpusat
- Email Template Manager
- API Key Management
- Activity & Security Logs
- Multi-role authentication

### 🛡️ Keamanan
- 5 lapis pertahanan (`.htaccess` → `init_security.php` → `Auth.php` → `Security.php` → `security_logs`)
- CSRF protection, rate limiting, brute-force lockout
- Password hashing (bcrypt), session fingerprinting
- Remember-me dengan selector:validator pattern
- XSS & SQL injection prevention

###  UI/UX
- Dark mode otomatis
- Responsive design
- Smooth page transitions
- Smart loader (Matrix Rain + Quantum Holographic)
- Glassmorphism & modern animations

## ️ Teknologi

| Layer | Stack |
|-------|-------|
| Backend | PHP 8.2 + PDO |
| Database | MySQL / MariaDB |
| Frontend | HTML5, CSS3, Vanilla JS |
| Server | Apache (Laragon/XAMPP) |
| Icons | Font Awesome 6.5 |
| Fonts | Space Grotesk, Inter, Poppins |

##  Instalasi Cepat

### 1. Persyaratan
- PHP ≥ 8.0
- MySQL ≥ 5.7
- Apache dengan `mod_rewrite` aktif

### 2. Setup
```bash
# Clone repositori
git clone https://github.com/yolislibmanmof/prodi-informatika.git
cd prodi-informatika

# Import database
phpmyadmin → import database/schema.sql

# Konfigurasi
cp .env.example .env
# Edit .env: DB credentials, APP_ENV=production