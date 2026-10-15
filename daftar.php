<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Pendaftaran Mahasiswa Baru';
$pageDesc  = 'Daftar menjadi mahasiswa baru Program Studi Informatika. Proses cepat, mudah, dan aman.';

$success = $error = '';
$receipt = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi server-side tambahan
    $errors = [];
    if (empty($_POST['nama_lengkap']))   $errors[] = 'Nama lengkap wajib diisi';
    if (empty($_POST['email']))          $errors[] = 'Email wajib diisi';
    if (empty($_POST['telepon']))        $errors[] = 'Nomor telepon wajib diisi';
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    }

    if (!empty($errors)) {
        $error = implode('. ', $errors);
    } else {
        $data = [
            'nama_lengkap'     => clean($_POST['nama_lengkap']),
            'email'            => clean($_POST['email']),
            'telepon'          => preg_replace('/[^0-9+]/', '', $_POST['telepon']),
            'alamat'           => clean($_POST['alamat']),
            'asal_sekolah'     => clean($_POST['asal_sekolah']),
            'jurusan_sekolah'  => clean($_POST['jurusan_sekolah']),
            'nilai_rata_rata'  => floatval($_POST['nilai_rata_rata'] ?? 0),
            'jalur_pendaftaran'=> clean($_POST['jalur_pendaftaran'] ?? 'reguler')
        ];

        try {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO pendaftaran (nama_lengkap, email, telepon, alamat, asal_sekolah, jurusan_sekolah, nilai_rata_rata, jalur_pendaftaran) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $data['nama_lengkap'], $data['email'], $data['telepon'], $data['alamat'],
                $data['asal_sekolah'], $data['jurusan_sekolah'], $data['nilai_rata_rata'], $data['jalur_pendaftaran']
            ]);
            $insertId = $db->lastInsertId();
            $regNumber = 'REG-' . date('Ymd') . '-' . str_pad($insertId, 5, '0', STR_PAD_LEFT);

            $success = 'Pendaftaran berhasil!';
            $receipt = [
                'number'     => $regNumber,
                'name'       => $data['nama_lengkap'],
                'email'      => $data['email'],
                'phone'      => $data['telepon'],
                'school'     => $data['asal_sekolah'],
                'major'      => $data['jurusan_sekolah'],
                'grade'      => $data['nilai_rata_rata'],
                'track'      => ucfirst($data['jalur_pendaftaran']),
                'date'       => date('d F Y, H:i'),
                'id'         => $insertId
            ];
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.';
        }
    }
}
?>

<!-- ===== HERO PENDAFTARAN ===== -->
<div class="rg-hero">
    <div class="rg-hero-inner">
        <div class="rg-hero-content">
            <span class="rg-hero-badge">
                <i class="fas fa-graduation-cap"></i>
                Pendaftaran Mahasiswa Baru 2026/2027
            </span>
            <h1 class="rg-hero-title">Mulai Perjalanan<br><span>Digitalmu</span> Hari Ini</h1>
            <p class="rg-hero-subtitle">
                Bergabunglah dengan 500+ mahasiswa yang telah memilih Program Studi Informatika sebagai langkah awal karier impian mereka.
            </p>
            <div class="rg-hero-features">
                <div class="rg-hero-feat"><i class="fas fa-check-circle"></i> Proses 5 menit</div>
                <div class="rg-hero-feat"><i class="fas fa-check-circle"></i> Gratis & tanpa biaya</div>
                <div class="rg-hero-feat"><i class="fas fa-check-circle"></i> Konfirmasi via email</div>
            </div>
        </div>
        <div class="rg-hero-visual">
            <div class="rg-hero-blob">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="rg-hero-float rg-hero-float-1">
                <i class="fas fa-laptop-code"></i>
                <span>AI & Data Science</span>
            </div>
            <div class="rg-hero-float rg-hero-float-2">
                <i class="fas fa-shield-alt"></i>
                <span>Cybersecurity</span>
            </div>
            <div class="rg-hero-float rg-hero-float-3">
                <i class="fas fa-rocket"></i>
                <span>Software Engineer</span>
            </div>
        </div>
    </div>
</div>

<!-- ===== FORM SECTION ===== -->
<section class="rg-section">
    <div class="container">
        <div class="rg-layout">

            <!-- Sidebar Info -->
            <aside class="rg-sidebar">
                <div class="rg-sidebar-card">
                    <div class="rg-sidebar-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <h3>Panduan Pendaftaran</h3>
                    <p>Isi formulir dengan data yang valid. Tim kami akan menghubungi Anda dalam 1x24 jam untuk verifikasi.</p>
                    <ul class="rg-steps-info">
                        <li>
                            <div class="rg-step-num">1</div>
                            <div>
                                <strong>Data Pribadi</strong>
                                <span>Identitas & kontak</span>
                            </div>
                        </li>
                        <li>
                            <div class="rg-step-num">2</div>
                            <div>
                                <strong>Data Akademik</strong>
                                <span>Riwayat sekolah</span>
                            </div>
                        </li>
                        <li>
                            <div class="rg-step-num">3</div>
                            <div>
                                <strong>Konfirmasi</strong>
                                <span>Review & kirim</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="rg-sidebar-card rg-sidebar-card-accent">
                    <h4><i class="fas fa-headset"></i> Butuh Bantuan?</h4>
                    <p>Tim admisi siap membantu Anda 24/7</p>
                    <a href="https://wa.me/6281234567890" target="_blank" class="rg-help-btn">
                        <i class="fab fa-whatsapp"></i> Chat Admisi
                    </a>
                </div>
            </aside>

            <!-- Form Container -->
            <div class="rg-form-wrap">

                <!-- Progress Bar -->
                <div class="rg-progress">
                    <div class="rg-progress-track">
                        <div class="rg-progress-fill" id="rgProgressFill"></div>
                    </div>
                    <div class="rg-progress-steps">
                        <div class="rg-pstep active" data-step="1">
                            <div class="rg-pstep-circle"><span>1</span><i class="fas fa-check"></i></div>
                            <div class="rg-pstep-label">Data Pribadi</div>
                        </div>
                        <div class="rg-pstep" data-step="2">
                            <div class="rg-pstep-circle"><span>2</span><i class="fas fa-check"></i></div>
                            <div class="rg-pstep-label">Data Akademik</div>
                        </div>
                        <div class="rg-pstep" data-step="3">
                            <div class="rg-pstep-circle"><span>3</span><i class="fas fa-check"></i></div>
                            <div class="rg-pstep-label">Konfirmasi</div>
                        </div>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="rg-form-card">

                    <!-- Alerts -->
                    <?php if ($success && !$receipt): ?>
                    <div class="rg-alert rg-alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Berhasil!</strong> <?= $success ?>
                        </div>
                        <button class="rg-alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                    </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                    <div class="rg-alert rg-alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <strong>Gagal!</strong> <?= $error ?>
                        </div>
                        <button class="rg-alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                    </div>
                    <?php endif; ?>

                    <!-- Draft Indicator -->
                    <div class="rg-draft-info" id="rgDraftInfo" style="display:none">
                        <i class="fas fa-save"></i>
                        <span>Draft tersimpan otomatis</span>
                        <button type="button" id="rgClearDraft">Hapus draft</button>
                    </div>

                    <!-- Form -->
                    <form method="POST" id="rgForm" novalidate autocomplete="on">

                        <!-- ===== STEP 1: DATA PRIBADI ===== -->
                        <div class="rg-step active" data-step="1">
                            <div class="rg-step-header">
                                <div class="rg-step-icon"><i class="fas fa-user"></i></div>
                                <div>
                                    <h2>Data Pribadi</h2>
                                    <p>Informasi identitas & kontak Anda</p>
                                </div>
                            </div>

                            <div class="rg-field">
                                <label for="rg_nama">Nama Lengkap <span class="rg-req">*</span></label>
                                <div class="rg-input-wrap">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="rg_nama" name="nama_lengkap"
                                           required minlength="3" maxlength="100"
                                           autocomplete="name" placeholder="Sesuai KTP/Ijazah">
                                </div>
                                <div class="rg-field-msg"></div>
                            </div>

                            <div class="rg-row">
                                <div class="rg-field">
                                    <label for="rg_email">Email Aktif <span class="rg-req">*</span></label>
                                    <div class="rg-input-wrap">
                                        <i class="fas fa-envelope"></i>
                                        <input type="email" id="rg_email" name="email"
                                               required autocomplete="email" placeholder="nama@email.com">
                                    </div>
                                    <div class="rg-field-msg"></div>
                                </div>

                                <div class="rg-field">
                                    <label for="rg_phone">Nomor WhatsApp <span class="rg-req">*</span></label>
                                    <div class="rg-input-wrap">
                                        <i class="fab fa-whatsapp"></i>
                                        <input type="tel" id="rg_phone" name="telepon"
                                               required autocomplete="tel" placeholder="0812-3456-7890">
                                    </div>
                                    <div class="rg-field-msg"></div>
                                </div>
                            </div>

                            <div class="rg-field">
                                <label for="rg_address">Alamat Lengkap</label>
                                <div class="rg-textarea-wrap">
                                    <textarea id="rg_address" name="alamat" rows="3"
                                              maxlength="300" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota, Provinsi, Kode Pos"></textarea>
                                    <div class="rg-char-count"><span id="rgAddrCount">0</span>/300</div>
                                </div>
                                <div class="rg-field-msg"></div>
                            </div>

                            <div class="rg-step-actions">
                                <div></div>
                                <button type="button" class="rg-btn rg-btn-primary rg-next-btn">
                                    Lanjut <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ===== STEP 2: DATA AKADEMIK ===== -->
                        <div class="rg-step" data-step="2">
                            <div class="rg-step-header">
                                <div class="rg-step-icon"><i class="fas fa-school"></i></div>
                                <div>
                                    <h2>Data Akademik</h2>
                                    <p>Riwayat pendidikan Anda</p>
                                </div>
                            </div>

                            <div class="rg-field">
                                <label for="rg_school">Asal Sekolah <span class="rg-req">*</span></label>
                                <div class="rg-input-wrap">
                                    <i class="fas fa-school"></i>
                                    <input type="text" id="rg_school" name="asal_sekolah"
                                           required minlength="3" placeholder="Contoh: SMAN 1 Jakarta"
                                           list="rgSchoolList">
                                    <datalist id="rgSchoolList">
                                        <option value="SMAN 1 Jakarta">
                                        <option value="SMAN 2 Jakarta">
                                        <option value="SMAN 3 Jakarta">
                                        <option value="SMKN 1 Jakarta">
                                        <option value="MAN 1 Jakarta">
                                    </datalist>
                                </div>
                                <div class="rg-field-msg"></div>
                            </div>

                            <div class="rg-row">
                                <div class="rg-field">
                                    <label for="rg_major">Jurusan Sekolah</label>
                                    <div class="rg-input-wrap">
                                        <i class="fas fa-book"></i>
                                        <select id="rg_major" name="jurusan_sekolah">
                                            <option value="">Pilih Jurusan</option>
                                            <option value="IPA">IPA (MIPA)</option>
                                            <option value="IPS">IPS</option>
                                            <option value="Bahasa">Bahasa</option>
                                            <option value="SMK/Kejuruan">SMK/Kejuruan</option>
                                            <option value="MA">MA (Madrasah Aliyah)</option>
                                        </select>
                                    </div>
                                    <div class="rg-field-msg"></div>
                                </div>

                                <div class="rg-field">
                                    <label for="rg_grade">Nilai Rata-rata</label>
                                    <div class="rg-input-wrap">
                                        <i class="fas fa-chart-line"></i>
                                        <input type="number" id="rg_grade" name="nilai_rata_rata"
                                               step="0.01" min="0" max="100" placeholder="Contoh: 85.50">
                                    </div>
                                    <div class="rg-field-msg"></div>
                                </div>
                            </div>

                            <div class="rg-field">
                                <label>Jalur Pendaftaran <span class="rg-req">*</span></label>
                                <div class="rg-track-grid">
                                    <label class="rg-track-card">
                                        <input type="radio" name="jalur_pendaftaran" value="reguler" checked>
                                        <div class="rg-track-inner">
                                            <div class="rg-track-icon">📚</div>
                                            <div class="rg-track-info">
                                                <strong>Reguler</strong>
                                                <span>Jalur seleksi umum</span>
                                            </div>
                                            <i class="fas fa-check-circle rg-track-check"></i>
                                        </div>
                                    </label>

                                    <label class="rg-track-card">
                                        <input type="radio" name="jalur_pendaftaran" value="prestasi">
                                        <div class="rg-track-inner">
                                            <div class="rg-track-icon">🏆</div>
                                            <div class="rg-track-info">
                                                <strong>Prestasi</strong>
                                                <span>Potongan biaya 20%</span>
                                            </div>
                                            <i class="fas fa-check-circle rg-track-check"></i>
                                        </div>
                                    </label>

                                    <label class="rg-track-card">
                                        <input type="radio" name="jalur_pendaftaran" value="beasiswa">
                                        <div class="rg-track-inner">
                                            <div class="rg-track-icon">🎓</div>
                                            <div class="rg-track-info">
                                                <strong>Beasiswa</strong>
                                                <span>Gratis 100% + tunjangan</span>
                                            </div>
                                            <i class="fas fa-check-circle rg-track-check"></i>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Conditional: syarat beasiswa -->
                            <div class="rg-field rg-scholarship-extra" id="rgScholarExtra" style="display:none">
                                <label for="rg_scholarEssay">Motivasi Singkat <span class="rg-req">*</span></label>
                                <div class="rg-textarea-wrap">
                                    <textarea id="rg_scholarEssay" rows="3" maxlength="500"
                                              placeholder="Ceritakan alasan Anda layak mendapatkan beasiswa (maks. 500 karakter)"></textarea>
                                    <div class="rg-char-count"><span id="rgEssayCount">0</span>/500</div>
                                </div>
                                <div class="rg-field-msg"></div>
                            </div>

                            <div class="rg-step-actions">
                                <button type="button" class="rg-btn rg-btn-ghost rg-prev-btn">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </button>
                                <button type="button" class="rg-btn rg-btn-primary rg-next-btn">
                                    Lanjut <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ===== STEP 3: KONFIRMASI ===== -->
                        <div class="rg-step" data-step="3">
                            <div class="rg-step-header">
                                <div class="rg-step-icon"><i class="fas fa-clipboard-check"></i></div>
                                <div>
                                    <h2>Konfirmasi Data</h2>
                                    <p>Periksa kembali sebelum mengirim</p>
                                </div>
                            </div>

                            <div class="rg-review">
                                <div class="rg-review-group">
                                    <h4><i class="fas fa-user"></i> Data Pribadi</h4>
                                    <div class="rg-review-grid">
                                        <div class="rg-review-item">
                                            <span>Nama Lengkap</span>
                                            <strong id="rv_nama">-</strong>
                                        </div>
                                        <div class="rg-review-item">
                                            <span>Email</span>
                                            <strong id="rv_email">-</strong>
                                        </div>
                                        <div class="rg-review-item">
                                            <span>No. WhatsApp</span>
                                            <strong id="rv_phone">-</strong>
                                        </div>
                                        <div class="rg-review-item full">
                                            <span>Alamat</span>
                                            <strong id="rv_address">-</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="rg-review-group">
                                    <h4><i class="fas fa-school"></i> Data Akademik</h4>
                                    <div class="rg-review-grid">
                                        <div class="rg-review-item">
                                            <span>Asal Sekolah</span>
                                            <strong id="rv_school">-</strong>
                                        </div>
                                        <div class="rg-review-item">
                                            <span>Jurusan</span>
                                            <strong id="rv_major">-</strong>
                                        </div>
                                        <div class="rg-review-item">
                                            <span>Nilai Rata-rata</span>
                                            <strong id="rv_grade">-</strong>
                                        </div>
                                        <div class="rg-review-item">
                                            <span>Jalur</span>
                                            <strong id="rv_track" class="rg-track-badge">-</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <label class="rg-agreement">
                                <input type="checkbox" id="rgAgree" required>
                                <span class="rg-agree-box"></span>
                                <span>
                                    Saya menyatakan data yang diisi adalah <strong>benar dan dapat dipertanggungjawabkan</strong>.
                                    Saya menyetujui <a href="#" onclick="return false">Syarat & Ketentuan</a>
                                    serta <a href="#" onclick="return false">Kebijakan Privasi</a> Program Studi Informatika.
                                </span>
                            </label>

                            <div class="rg-step-actions">
                                <button type="button" class="rg-btn rg-btn-ghost rg-prev-btn">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </button>
                                <button type="submit" class="rg-btn rg-btn-success rg-submit-btn" id="rgSubmitBtn">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Kirim Pendaftaran</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== SUCCESS MODAL ===== -->
<?php if ($receipt): ?>
<div class="rg-modal" id="rgSuccessModal">
    <div class="rg-modal-card">
        <button class="rg-modal-close" id="rgModalClose"><i class="fas fa-times"></i></button>

        <div class="rg-success-anim">
            <svg class="rg-checkmark" viewBox="0 0 52 52">
                <circle class="rg-check-circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="rg-check-path" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        </div>

        <div class="rg-modal-body">
            <h2>Pendaftaran Berhasil! 🎉</h2>
            <p>Selamat datang di keluarga besar Informatika Pro. Silakan simpan nomor registrasi Anda.</p>

            <div class="rg-receipt" id="rgReceipt">
                <div class="rg-receipt-header">
                    <div class="rg-receipt-logo">
                        <i class="fas fa-code"></i>
                        <span>Informatika<span>Pro</span></span>
                    </div>
                    <div class="rg-receipt-stamp">OFFICIAL</div>
                </div>
                <div class="rg-receipt-title">BUKTI PENDAFTARAN</div>
                <div class="rg-receipt-number">
                    <span>No. Registrasi</span>
                    <strong><?= $receipt['number'] ?></strong>
                </div>
                <div class="rg-receipt-grid">
                    <div><span>Nama</span><strong><?= clean($receipt['name']) ?></strong></div>
                    <div><span>Email</span><strong><?= clean($receipt['email']) ?></strong></div>
                    <div><span>Telepon</span><strong><?= clean($receipt['phone']) ?></strong></div>
                    <div><span>Sekolah</span><strong><?= clean($receipt['school'] ?: '-') ?></strong></div>
                    <div><span>Jurusan</span><strong><?= clean($receipt['major'] ?: '-') ?></strong></div>
                    <div><span>Nilai</span><strong><?= $receipt['grade'] ?: '-' ?></strong></div>
                    <div><span>Jalur</span><strong><?= clean($receipt['track']) ?></strong></div>
                    <div><span>Tanggal</span><strong><?= $receipt['date'] ?></strong></div>
                </div>
                <div class="rg-receipt-footer">
                    <i class="fas fa-info-circle"></i>
                    <span>Tim admisi akan menghubungi Anda via email dalam 1x24 jam untuk tahap verifikasi.</span>
                </div>
            </div>

            <div class="rg-modal-actions">
                <button type="button" class="rg-btn rg-btn-ghost" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak Bukti
                </button>
                <button type="button" class="rg-btn rg-btn-primary" id="rgDownloadReceipt">
                    <i class="fas fa-download"></i> Unduh PDF
                </button>
                <a href="<?= SITE_URL ?>/" class="rg-btn rg-btn-success">
                    <i class="fas fa-home"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Confetti Canvas -->
<canvas id="rgConfetti" class="rg-confetti"></canvas>
<?php endif; ?>

<!-- ============================================================
     SELF-CONTAINED STYLES (prefix: rg-)
============================================================ -->
<style>
:root {
    --rg-primary: #10B981;
    --rg-primary-dark: #047857;
    --rg-primary-light: #D1FAE5;
    --rg-dark: #0F172A;
    --rg-gray-50: #F8FAFC;
    --rg-gray-100: #F1F5F9;
    --rg-gray-200: #E2E8F0;
    --rg-gray-400: #94A3B8;
    --rg-gray-600: #475569;
    --rg-gray-700: #334155;
    --rg-danger: #EF4444;
    --rg-warning: #F59E0B;
    --rg-success: #10B981;
    --rg-radius: 14px;
    --rg-radius-lg: 22px;
    --rg-shadow-sm: 0 2px 8px rgba(15,23,42,.06);
    --rg-shadow-md: 0 10px 30px rgba(15,23,42,.08);
    --rg-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
}

/* ===== Hero ===== */
.rg-hero {
    background: linear-gradient(135deg, #0F172A 0%, #047857 100%);
    padding: 120px 20px 80px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.rg-hero::before {
    content: ''; position: absolute; inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(16,185,129,.3) 0, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(52,211,153,.2) 0, transparent 50%);
    pointer-events: none;
}
.rg-hero-inner {
    position: relative; z-index: 1;
    max-width: 1200px; margin: 0 auto;
    display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;
}
.rg-hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 16px; background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.2); border-radius: 50px;
    font-size: .82rem; font-weight: 500; backdrop-filter: blur(10px);
    margin-bottom: 20px;
    animation: rgSlideDown .6s cubic-bezier(.4,0,.2,1);
}
.rg-hero-badge i { color: #34D399; }
@keyframes rgSlideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: none; } }

.rg-hero-title {
    font-size: clamp(2rem, 4.5vw, 3.5rem); font-weight: 700;
    line-height: 1.15; margin-bottom: 20px; letter-spacing: -.02em;
    animation: rgSlideUp .8s cubic-bezier(.4,0,.2,1);
}
.rg-hero-title span {
    background: linear-gradient(135deg, #34D399, #10B981);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
}
@keyframes rgSlideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: none; } }

.rg-hero-subtitle {
    font-size: 1.05rem; line-height: 1.7; opacity: .85;
    margin-bottom: 28px; max-width: 540px;
    animation: rgSlideUp 1s cubic-bezier(.4,0,.2,1);
}

.rg-hero-features {
    display: flex; gap: 20px; flex-wrap: wrap;
    animation: rgSlideUp 1.2s cubic-bezier(.4,0,.2,1);
}
.rg-hero-feat {
    display: flex; align-items: center; gap: 8px;
    font-size: .88rem; font-weight: 500;
}
.rg-hero-feat i { color: #34D399; }

.rg-hero-visual {
    position: relative; height: 420px;
    display: flex; align-items: center; justify-content: center;
}
.rg-hero-blob {
    width: 280px; height: 280px; border-radius: 50%;
    background: linear-gradient(135deg, rgba(52,211,153,.3), rgba(16,185,129,.15));
    border: 2px solid rgba(52,211,153,.4);
    display: flex; align-items: center; justify-content: center;
    font-size: 6rem; color: #34D399;
    animation: rgBlobPulse 4s ease-in-out infinite;
    box-shadow: 0 0 80px rgba(52,211,153,.3);
}
@keyframes rgBlobPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
.rg-hero-float {
    position: absolute; display: flex; align-items: center; gap: 10px;
    padding: 12px 18px; background: rgba(255,255,255,.12);
    backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,.2);
    border-radius: 14px; color: #fff; font-size: .85rem; font-weight: 500;
    animation: rgFloat 5s ease-in-out infinite;
}
.rg-hero-float i { font-size: 1.1rem; color: #34D399; }
.rg-hero-float-1 { top: 30px; left: 20px; animation-delay: 0s; }
.rg-hero-float-2 { bottom: 50px; left: 0; animation-delay: -2s; }
.rg-hero-float-3 { top: 40%; right: 10px; animation-delay: -4s; }
@keyframes rgFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}

/* ===== Form Section ===== */
.rg-section { padding: 80px 0; background: var(--rg-gray-50); }
body.gl-dark .rg-section { background: #0F172A; }

.rg-layout {
    display: grid; grid-template-columns: 320px 1fr; gap: 40px;
    align-items: flex-start;
}

/* Sidebar */
.rg-sidebar { display: flex; flex-direction: column; gap: 20px; position: sticky; top: 100px; }
.rg-sidebar-card {
    background: #fff; border-radius: var(--rg-radius-lg); padding: 28px;
    box-shadow: var(--rg-shadow-md); border: 1px solid var(--rg-gray-100);
}
body.gl-dark .rg-sidebar-card { background: #1E293B; border-color: rgba(255,255,255,.08); color: #E2E8F0; }
.rg-sidebar-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: linear-gradient(135deg, #10B981, #047857);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; margin-bottom: 16px;
    box-shadow: 0 8px 20px rgba(16,185,129,.3);
}
.rg-sidebar-card h3 { font-size: 1.15rem; margin-bottom: 8px; }
.rg-sidebar-card > p { font-size: .88rem; color: var(--rg-gray-600); line-height: 1.6; margin-bottom: 20px; }
body.gl-dark .rg-sidebar-card > p { color: #94A3B8; }

.rg-steps-info { display: flex; flex-direction: column; gap: 14px; }
.rg-steps-info li {
    display: flex; align-items: center; gap: 14px;
    padding: 12px; border-radius: 12px; background: var(--rg-gray-50);
    transition: all .3s;
}
body.gl-dark .rg-steps-info li { background: rgba(255,255,255,.04); }
.rg-steps-info li:hover { background: var(--rg-primary-light); transform: translateX(4px); }
body.gl-dark .rg-steps-info li:hover { background: rgba(16,185,129,.12); }
.rg-step-num {
    width: 32px; height: 32px; border-radius: 50%;
    background: linear-gradient(135deg, #10B981, #047857);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: .85rem; font-weight: 700; flex-shrink: 0;
}
.rg-steps-info strong { display: block; font-size: .92rem; margin-bottom: 2px; }
.rg-steps-info span { font-size: .78rem; color: var(--rg-gray-600); }

.rg-sidebar-card-accent {
    background: linear-gradient(135deg, #10B981, #047857);
    color: #fff; border: none;
}
.rg-sidebar-card-accent h4 { font-size: 1rem; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
.rg-sidebar-card-accent p { color: rgba(255,255,255,.85); font-size: .85rem; margin-bottom: 14px; }
.rg-help-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 16px; background: #fff; color: var(--rg-primary-dark);
    border-radius: 10px; font-size: .85rem; font-weight: 600;
    text-decoration: none; transition: all .3s;
}
.rg-help-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.2); }

/* Form Wrap */
.rg-form-wrap { min-width: 0; }

/* Progress */
.rg-progress {
    background: #fff; border-radius: var(--rg-radius-lg); padding: 28px 32px;
    box-shadow: var(--rg-shadow-md); margin-bottom: 24px;
    border: 1px solid var(--rg-gray-100);
}
body.gl-dark .rg-progress { background: #1E293B; border-color: rgba(255,255,255,.08); }
.rg-progress-track {
    height: 6px; background: var(--rg-gray-100); border-radius: 10px;
    overflow: hidden; margin-bottom: 20px;
}
body.gl-dark .rg-progress-track { background: rgba(255,255,255,.08); }
.rg-progress-fill {
    height: 100%; width: 33%;
    background: linear-gradient(90deg, #10B981, #34D399);
    border-radius: 10px; transition: width .5s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 0 10px rgba(16,185,129,.5);
}
.rg-progress-steps { display: flex; justify-content: space-between; }
.rg-pstep {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    flex: 1; position: relative;
}
.rg-pstep-circle {
    width: 40px; height: 40px; border-radius: 50%;
    background: var(--rg-gray-100); color: var(--rg-gray-600);
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem; font-weight: 700; transition: all .4s;
    position: relative; z-index: 2;
}
body.gl-dark .rg-pstep-circle { background: rgba(255,255,255,.08); color: #94A3B8; }
.rg-pstep-circle i { display: none; }
.rg-pstep.active .rg-pstep-circle {
    background: linear-gradient(135deg, #10B981, #047857);
    color: #fff; box-shadow: 0 8px 20px rgba(16,185,129,.4);
    transform: scale(1.1);
}
.rg-pstep.done .rg-pstep-circle {
    background: var(--rg-primary); color: #fff;
}
.rg-pstep.done .rg-pstep-circle span { display: none; }
.rg-pstep.done .rg-pstep-circle i { display: block; }
.rg-pstep-label { font-size: .78rem; color: var(--rg-gray-600); font-weight: 500; }
.rg-pstep.active .rg-pstep-label { color: var(--rg-primary-dark); font-weight: 700; }
body.gl-dark .rg-pstep.active .rg-pstep-label { color: #34D399; }

/* Form Card */
.rg-form-card {
    background: #fff; border-radius: var(--rg-radius-lg); padding: 40px;
    box-shadow: var(--rg-shadow-md); border: 1px solid var(--rg-gray-100);
}
body.gl-dark .rg-form-card { background: #1E293B; border-color: rgba(255,255,255,.08); color: #E2E8F0; }

/* Alerts */
.rg-alert {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
    animation: rgSlideDown .4s cubic-bezier(.4,0,.2,1);
}
.rg-alert > i { font-size: 1.2rem; flex-shrink: 0; }
.rg-alert > div { flex: 1; font-size: .9rem; }
.rg-alert strong { display: block; margin-bottom: 2px; }
.rg-alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
.rg-alert-error { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
.rg-alert-close {
    background: none; border: none; color: inherit; cursor: pointer;
    opacity: .6; padding: 4px; transition: opacity .2s;
}
.rg-alert-close:hover { opacity: 1; }

/* Draft Info */
.rg-draft-info {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; background: rgba(59,130,246,.1);
    color: #1E40AF; border-radius: 10px; margin-bottom: 16px;
    font-size: .82rem;
}
body.gl-dark .rg-draft-info { background: rgba(59,130,246,.15); color: #93C5FD; }
.rg-draft-info i { color: #3B82F6; }
.rg-draft-info button {
    margin-left: auto; background: none; border: none;
    color: inherit; cursor: pointer; font-size: .8rem;
    text-decoration: underline; font-family: inherit;
}

/* Steps */
.rg-step { display: none; animation: rgStepIn .5s cubic-bezier(.4,0,.2,1); }
.rg-step.active { display: block; }
@keyframes rgStepIn {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: none; }
}

.rg-step-header {
    display: flex; align-items: center; gap: 16px;
    padding-bottom: 24px; margin-bottom: 28px;
    border-bottom: 1px solid var(--rg-gray-100);
}
body.gl-dark .rg-step-header { border-color: rgba(255,255,255,.08); }
.rg-step-icon {
    width: 56px; height: 56px; border-radius: 16px;
    background: linear-gradient(135deg, #10B981, #047857);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
    box-shadow: 0 10px 24px rgba(16,185,129,.3);
}
.rg-step-header h2 { font-size: 1.35rem; margin-bottom: 4px; }
.rg-step-header p { font-size: .88rem; color: var(--rg-gray-600); margin: 0; }
body.gl-dark .rg-step-header p { color: #94A3B8; }

/* Fields */
.rg-field { margin-bottom: 20px; }
.rg-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.rg-field label {
    display: block; font-size: .88rem; font-weight: 600;
    color: var(--rg-gray-700); margin-bottom: 8px;
}
body.gl-dark .rg-field label { color: #E2E8F0; }
.rg-req { color: var(--rg-danger); }

.rg-input-wrap, .rg-textarea-wrap { position: relative; }
.rg-input-wrap i {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%); color: var(--rg-gray-400);
    font-size: .9rem; transition: color .3s;
    pointer-events: none;
}
.rg-input-wrap input, .rg-input-wrap select {
    width: 100%; padding: 13px 14px 13px 42px;
    background: var(--rg-gray-50); border: 1.5px solid var(--rg-gray-200);
    border-radius: 12px; font-size: .95rem; font-family: inherit;
    color: var(--rg-dark); transition: all .3s;
}
body.gl-dark .rg-input-wrap input, body.gl-dark .rg-input-wrap select {
    background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.1); color: #fff;
}
.rg-textarea-wrap textarea {
    width: 100%; padding: 12px 14px;
    background: var(--rg-gray-50); border: 1.5px solid var(--rg-gray-200);
    border-radius: 12px; font-size: .95rem; font-family: inherit;
    color: var(--rg-dark); resize: vertical; min-height: 90px;
    transition: all .3s;
}
body.gl-dark .rg-textarea-wrap textarea {
    background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.1); color: #fff;
}

.rg-input-wrap input:focus, .rg-input-wrap select:focus, .rg-textarea-wrap textarea:focus {
    outline: none; border-color: var(--rg-primary);
    background: #fff; box-shadow: 0 0 0 4px rgba(16,185,129,.12);
}
body.gl-dark .rg-input-wrap input:focus, body.gl-dark .rg-input-wrap select:focus,
body.gl-dark .rg-textarea-wrap textarea:focus {
    background: rgba(255,255,255,.06);
}
.rg-input-wrap input:focus + i, .rg-input-wrap:focus-within i { color: var(--rg-primary); }

.rg-input-wrap input.valid, .rg-textarea-wrap textarea.valid, .rg-input-wrap select.valid {
    border-color: var(--rg-success);
}
.rg-input-wrap input.invalid, .rg-textarea-wrap textarea.invalid, .rg-input-wrap select.invalid {
    border-color: var(--rg-danger); background: #FEF2F2;
}
body.gl-dark .rg-input-wrap input.invalid, body.gl-dark .rg-textarea-wrap textarea.invalid {
    background: rgba(239,68,68,.1);
}

.rg-field-msg {
    font-size: .78rem; margin-top: 6px; min-height: 16px;
    color: var(--rg-danger); display: flex; align-items: center; gap: 4px;
}
.rg-field-msg.success { color: var(--rg-success); }
.rg-char-count {
    position: absolute; bottom: 10px; right: 12px;
    font-size: .72rem; color: var(--rg-gray-400);
    pointer-events: none;
}

/* Track cards */
.rg-track-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.rg-track-card { cursor: pointer; position: relative; }
.rg-track-card input { position: absolute; opacity: 0; pointer-events: none; }
.rg-track-inner {
    display: flex; flex-direction: column; align-items: center; text-align: center;
    padding: 20px 14px; background: var(--rg-gray-50);
    border: 2px solid var(--rg-gray-200); border-radius: 14px;
    transition: all .3s; position: relative;
}
body.gl-dark .rg-track-inner { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.1); }
.rg-track-icon { font-size: 2rem; margin-bottom: 10px; }
.rg-track-info strong { display: block; font-size: .95rem; margin-bottom: 2px; }
.rg-track-info span { font-size: .78rem; color: var(--rg-gray-600); }
.rg-track-check {
    position: absolute; top: 10px; right: 10px;
    color: var(--rg-gray-300); font-size: 1.1rem;
    transition: all .3s;
}
.rg-track-card:hover .rg-track-inner { border-color: var(--rg-primary); transform: translateY(-2px); }
.rg-track-card input:checked + .rg-track-inner {
    background: var(--rg-primary-light); border-color: var(--rg-primary);
    box-shadow: 0 8px 20px rgba(16,185,129,.2);
}
body.gl-dark .rg-track-card input:checked + .rg-track-inner {
    background: rgba(16,185,129,.15); border-color: var(--rg-primary);
}
.rg-track-card input:checked + .rg-track-inner .rg-track-check { color: var(--rg-primary); transform: scale(1.2); }

/* Scholarship Extra */
.rg-scholarship-extra {
    background: #FFFBEB; border: 1px dashed #F59E0B;
    padding: 20px; border-radius: 14px; margin-top: -8px; margin-bottom: 20px;
    animation: rgSlideDown .4s cubic-bezier(.4,0,.2,1);
}
body.gl-dark .rg-scholarship-extra { background: rgba(245,158,11,.1); }

/* Review */
.rg-review { display: flex; flex-direction: column; gap: 20px; margin-bottom: 24px; }
.rg-review-group {
    background: var(--rg-gray-50); border-radius: 14px; padding: 20px;
}
body.gl-dark .rg-review-group { background: rgba(255,255,255,.04); }
.rg-review-group h4 {
    display: flex; align-items: center; gap: 8px;
    font-size: .95rem; margin-bottom: 14px; color: var(--rg-primary-dark);
}
body.gl-dark .rg-review-group h4 { color: #34D399; }
.rg-review-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.rg-review-item.full { grid-column: 1 / -1; }
.rg-review-item {
    padding: 10px 14px; background: #fff; border-radius: 10px;
    border: 1px solid var(--rg-gray-100);
}
body.gl-dark .rg-review-item { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.08); }
.rg-review-item span { display: block; font-size: .72rem; color: var(--rg-gray-600); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.rg-review-item strong { font-size: .95rem; word-break: break-word; }
.rg-track-badge {
    display: inline-block; padding: 4px 10px;
    background: var(--rg-primary-light); color: var(--rg-primary-dark);
    border-radius: 6px; font-size: .82rem !important;
}

/* Agreement */
.rg-agreement {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 16px; background: var(--rg-gray-50); border-radius: 12px;
    cursor: pointer; font-size: .88rem; line-height: 1.5;
    margin-bottom: 24px; user-select: none;
}
body.gl-dark .rg-agreement { background: rgba(255,255,255,.04); }
.rg-agreement input { position: absolute; opacity: 0; }
.rg-agree-box {
    width: 22px; height: 22px; flex-shrink: 0; border-radius: 6px;
    border: 2px solid var(--rg-gray-300); background: #fff;
    display: flex; align-items: center; justify-content: center;
    transition: all .3s; margin-top: 1px;
}
body.gl-dark .rg-agree-box { background: transparent; border-color: rgba(255,255,255,.2); }
.rg-agree-box::after {
    content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
    font-size: .7rem; color: #fff; opacity: 0; transform: scale(0);
    transition: all .3s cubic-bezier(.34,1.56,.64,1);
}
.rg-agreement input:checked + .rg-agree-box {
    background: var(--rg-primary); border-color: var(--rg-primary);
}
.rg-agreement input:checked + .rg-agree-box::after { opacity: 1; transform: scale(1); }
.rg-agreement a { color: var(--rg-primary); font-weight: 600; text-decoration: underline; }

/* Buttons */
.rg-step-actions {
    display: flex; justify-content: space-between; gap: 12px;
    padding-top: 24px; border-top: 1px solid var(--rg-gray-100);
    margin-top: 8px;
}
body.gl-dark .rg-step-actions { border-color: rgba(255,255,255,.08); }

.rg-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 13px 24px; border: none; border-radius: 12px;
    font-size: .92rem; font-weight: 600; font-family: inherit;
    cursor: pointer; text-decoration: none;
    transition: all .3s cubic-bezier(.4,0,.2,1);
    position: relative; overflow: hidden;
}
.rg-btn-primary {
    background: linear-gradient(135deg, #10B981, #047857); color: #fff;
    box-shadow: 0 8px 20px rgba(16,185,129,.3);
}
.rg-btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(16,185,129,.45); }
.rg-btn-ghost {
    background: transparent; color: var(--rg-gray-700);
    border: 1.5px solid var(--rg-gray-200);
}
body.gl-dark .rg-btn-ghost { color: #E2E8F0; border-color: rgba(255,255,255,.1); }
.rg-btn-ghost:hover { background: var(--rg-gray-100); border-color: var(--rg-gray-300); }
.rg-btn-success {
    background: linear-gradient(135deg, #10B981, #047857); color: #fff;
    box-shadow: 0 8px 20px rgba(16,185,129,.3);
}
.rg-btn-success:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(16,185,129,.45); }
.rg-btn:disabled { opacity: .7; cursor: not-allowed; }
.rg-submit-btn { flex: 1; padding: 15px 28px; font-size: 1rem; }

/* ===== Success Modal ===== */
.rg-modal {
    position: fixed; inset: 0; z-index: 10000;
    background: rgba(15,23,42,.75); backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center;
    padding: 20px; overflow-y: auto;
    animation: rgFadeIn .3s;
}
@keyframes rgFadeIn { from { opacity: 0; } to { opacity: 1; } }
.rg-modal-card {
    background: #fff; border-radius: 24px; padding: 40px;
    max-width: 560px; width: 100%; position: relative;
    box-shadow: 0 30px 70px rgba(0,0,0,.3);
    animation: rgModalIn .5s cubic-bezier(.34,1.56,.64,1);
    max-height: 90vh; overflow-y: auto;
}
body.gl-dark .rg-modal-card { background: #1E293B; color: #E2E8F0; }
@keyframes rgModalIn { from { opacity: 0; transform: scale(.85) translateY(30px); } to { opacity: 1; transform: none; } }

.rg-modal-close {
    position: absolute; top: 16px; right: 16px;
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--rg-gray-100); border: none; cursor: pointer;
    color: var(--rg-gray-700); display: flex; align-items: center; justify-content: center;
    transition: all .3s;
}
.rg-modal-close:hover { background: #FEE2E2; color: var(--rg-danger); transform: rotate(90deg); }

.rg-success-anim { text-align: center; margin-bottom: 20px; }
.rg-checkmark { width: 90px; height: 90px; }
.rg-check-circle {
    stroke-dasharray: 166; stroke-dashoffset: 166;
    stroke-width: 2; stroke-miterlimit: 10; stroke: #10B981;
    fill: none; animation: rgStroke .6s cubic-bezier(.65,0,.45,1) forwards;
}
.rg-check-path {
    transform-origin: 50% 50%; stroke-dasharray: 48; stroke-dashoffset: 48;
    stroke-width: 3; stroke: #10B981; stroke-linecap: round;
    fill: none; animation: rgStroke .3s cubic-bezier(.65,0,.45,1) .8s forwards;
}
@keyframes rgStroke { to { stroke-dashoffset: 0; } }

.rg-modal-body { text-align: center; }
.rg-modal-body h2 { font-size: 1.6rem; margin-bottom: 8px; }
.rg-modal-body > p { color: var(--rg-gray-600); margin-bottom: 24px; }
body.gl-dark .rg-modal-body > p { color: #94A3B8; }

/* Receipt */
.rg-receipt {
    background: linear-gradient(135deg, #fff, #F0FDF4);
    border: 2px dashed #10B981; border-radius: 16px;
    padding: 24px; text-align: left; margin-bottom: 20px;
    position: relative;
}
body.gl-dark .rg-receipt { background: linear-gradient(135deg, #0F172A, #042F2E); border-color: #34D399; }
.rg-receipt-header {
    display: flex; justify-content: space-between; align-items: center;
    padding-bottom: 16px; margin-bottom: 16px;
    border-bottom: 1px dashed rgba(16,185,129,.3);
}
.rg-receipt-logo {
    display: flex; align-items: center; gap: 8px;
    font-family: 'Space Grotesk', sans-serif; font-weight: 700;
}
.rg-receipt-logo i {
    width: 32px; height: 32px; border-radius: 8px;
    background: linear-gradient(135deg, #10B981, #047857);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: .9rem;
}
.rg-receipt-logo span span { color: #10B981; }
.rg-receipt-stamp {
    padding: 4px 10px; background: #10B981; color: #fff;
    font-size: .65rem; font-weight: 700; letter-spacing: 2px;
    border-radius: 4px; transform: rotate(-5deg);
}
.rg-receipt-title {
    text-align: center; font-size: .75rem; letter-spacing: 3px;
    color: var(--rg-gray-600); margin-bottom: 12px;
}
body.gl-dark .rg-receipt-title { color: #94A3B8; }
.rg-receipt-number {
    text-align: center; padding: 14px; margin-bottom: 14px;
    background: #fff; border-radius: 10px;
    border: 1px solid var(--rg-gray-200);
}
body.gl-dark .rg-receipt-number { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); }
.rg-receipt-number span { display: block; font-size: .7rem; color: var(--rg-gray-600); margin-bottom: 4px; letter-spacing: 1px; }
.rg-receipt-number strong {
    font-family: 'JetBrains Mono', 'Courier New', monospace;
    font-size: 1.2rem; color: var(--rg-primary-dark); letter-spacing: 1px;
}
body.gl-dark .rg-receipt-number strong { color: #34D399; }

.rg-receipt-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.rg-receipt-grid > div { padding: 8px 10px; background: rgba(16,185,129,.06); border-radius: 8px; }
.rg-receipt-grid span { display: block; font-size: .68rem; color: var(--rg-gray-600); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 2px; }
.rg-receipt-grid strong { font-size: .85rem; word-break: break-word; }

.rg-receipt-footer {
    margin-top: 14px; padding: 10px 12px;
    background: rgba(59,130,246,.08); border-radius: 8px;
    display: flex; align-items: flex-start; gap: 8px;
    font-size: .78rem; color: #1E40AF;
}
body.gl-dark .rg-receipt-footer { background: rgba(59,130,246,.15); color: #93C5FD; }
.rg-receipt-footer i { color: #3B82F6; margin-top: 2px; flex-shrink: 0; }

.rg-modal-actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
.rg-modal-actions .rg-btn { flex: 1; min-width: 140px; padding: 12px 16px; font-size: .85rem; }

/* Confetti */
.rg-confetti {
    position: fixed; inset: 0; z-index: 10001;
    pointer-events: none; display: none;
}

/* ===== Responsive ===== */
@media (max-width: 1024px) {
    .rg-layout { grid-template-columns: 1fr; }
    .rg-sidebar { position: static; }
}
@media (max-width: 768px) {
    .rg-hero { padding: 100px 16px 60px; }
    .rg-hero-inner { grid-template-columns: 1fr; gap: 40px; }
    .rg-hero-visual { height: 300px; }
    .rg-hero-blob { width: 200px; height: 200px; font-size: 4rem; }
    .rg-row, .rg-track-grid, .rg-review-grid { grid-template-columns: 1fr; }
    .rg-form-card { padding: 24px; }
    .rg-progress { padding: 20px; }
    .rg-pstep-label { font-size: .7rem; }
    .rg-modal-card { padding: 24px; }
    .rg-modal-actions { flex-direction: column; }
    .rg-modal-actions .rg-btn { width: 100%; }
    .rg-step-actions { flex-direction: column-reverse; }
    .rg-step-actions .rg-btn { width: 100%; }
}

/* ===== Print ===== */
@media print {
    body * { visibility: hidden; }
    .rg-receipt, .rg-receipt * { visibility: visible; }
    .rg-receipt {
        position: absolute; left: 0; top: 0; width: 100%;
        border: 2px solid #10B981; background: #fff !important;
    }
    .rg-modal-card { box-shadow: none; max-height: none; }
    .rg-modal-close, .rg-modal-actions { display: none; }
}
</style>

<!-- ============================================================
     SELF-CONTAINED SCRIPTS
============================================================ -->
<script>
(function() {
    'use strict';

    const form = document.getElementById('rgForm');
    if (!form) return;

    /* ===== 1. Multi-Step Navigation ===== */
    const steps = form.querySelectorAll('.rg-step');
    const psteps = document.querySelectorAll('.rg-pstep');
    const progressFill = document.getElementById('rgProgressFill');
    let currentStep = 1;

    const goToStep = (n) => {
        if (n < 1 || n > steps.length) return;
        steps.forEach(s => s.classList.remove('active'));
        form.querySelector(`.rg-step[data-step="${n}"]`).classList.add('active');
        currentStep = n;

        // Update progress bar
        const percent = ((n - 1) / (steps.length - 1)) * 100;
        progressFill.style.width = percent + '%';

        // Update step indicators
        psteps.forEach(p => {
            const s = +p.dataset.step;
            p.classList.toggle('active', s === n);
            p.classList.toggle('done', s < n);
        });

        // Update review saat masuk step 3
        if (n === 3) buildReview();

        // Scroll ke atas form
        window.scrollTo({
            top: document.querySelector('.rg-form-wrap').offsetTop - 100,
            behavior: 'smooth'
        });
    };

    form.querySelectorAll('.rg-next-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (validateStep(currentStep)) goToStep(currentStep + 1);
        });
    });
    form.querySelectorAll('.rg-prev-btn').forEach(btn => {
        btn.addEventListener('click', () => goToStep(currentStep - 1));
    });

    /* ===== 2. Real-time Validation ===== */
    const validators = {
        rg_nama: v => v.trim().length >= 3 ? '' : 'Nama minimal 3 karakter',
        rg_email: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? '' : 'Format email tidak valid',
        rg_phone: v => /^[\d\s\-+()]{8,}$/.test(v) ? '' : 'Nomor telepon tidak valid',
        rg_school: v => v.trim().length >= 3 ? '' : 'Asal sekolah minimal 3 karakter',
        rg_address: () => '', // optional
        rg_major: () => '',
        rg_grade: v => v === '' || (parseFloat(v) >= 0 && parseFloat(v) <= 100) ? '' : 'Nilai harus 0-100'
    };

    const showFieldState = (input, msg) => {
        const msgEl = input.closest('.rg-field')?.querySelector('.rg-field-msg');
        if (!msgEl) return;
        if (msg === '') {
            input.classList.remove('invalid');
            input.classList.add('valid');
            msgEl.textContent = '';
            msgEl.classList.remove('success');
        } else if (msg === null) {
            input.classList.remove('invalid', 'valid');
            msgEl.textContent = '';
        } else {
            input.classList.add('invalid');
            input.classList.remove('valid');
            msgEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + msg;
        }
    };

    // Attach validators
    Object.keys(validators).forEach(id => {
        const input = document.getElementById(id);
        if (!input) return;
        input.addEventListener('blur', () => {
            if (input.value === '') showFieldState(input, null);
            else showFieldState(input, validators[id](input.value));
        });
        input.addEventListener('input', () => {
            if (input.classList.contains('invalid')) {
                const msg = validators[id](input.value);
                showFieldState(input, msg === '' ? '' : msg);
            }
        });
    });

    /* ===== 3. Step Validation ===== */
    function validateStep(n) {
        let valid = true;
        const stepEl = form.querySelector(`.rg-step[data-step="${n}"]`);
        const required = stepEl.querySelectorAll('[required]');

        required.forEach(input => {
            // Skip checkbox (agreement di step 3)
            if (input.type === 'checkbox') return;

            const id = input.id;
            let msg = '';

            if (input.value.trim() === '') {
                msg = 'Field ini wajib diisi';
            } else if (validators[id]) {
                msg = validators[id](input.value);
            }

            if (msg) {
                showFieldState(input, msg);
                valid = false;
            } else {
                showFieldState(input, '');
            }
        });

        if (!valid) {
            // Focus field error pertama
            const firstError = stepEl.querySelector('.invalid');
            if (firstError) firstError.focus();

            // Shake animation
            stepEl.style.animation = 'none';
            void stepEl.offsetWidth;
            stepEl.style.animation = 'rgShake .5s';
        }

        return valid;
    }

    // Inject shake keyframes
    const shakeStyle = document.createElement('style');
    shakeStyle.textContent = `
        @keyframes rgShake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-8px); }
            40%, 80% { transform: translateX(8px); }
        }
    `;
    document.head.appendChild(shakeStyle);

    /* ===== 4. Phone Formatter ===== */
    const phoneInput = document.getElementById('rg_phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', e => {
            let v = e.target.value.replace(/[^\d+]/g, '');
            if (v.startsWith('+62')) v = v.substring(3);
            else if (v.startsWith('62')) v = v.substring(2);
            else if (v.startsWith('0')) v = v.substring(1);

            // Format: 0812-3456-7890
            let formatted = '0' + v;
            if (v.length > 4) formatted = '0' + v.substring(0, 4) + '-' + v.substring(4);
            if (v.length > 8) formatted = '0' + v.substring(0, 4) + '-' + v.substring(4, 8) + '-' + v.substring(8, 12);

            e.target.value = formatted;
        });
    }

    /* ===== 5. Character Counters ===== */
    const setupCounter = (textareaId, countId) => {
        const ta = document.getElementById(textareaId);
        const counter = document.getElementById(countId);
        if (!ta || !counter) return;
        const max = parseInt(ta.getAttribute('maxlength')) || 500;
        ta.addEventListener('input', () => {
            counter.textContent = ta.value.length;
            counter.parentElement.style.color = ta.value.length > max * 0.9 ? '#EF4444' : '';
        });
    };
    setupCounter('rg_address', 'rgAddrCount');
    setupCounter('rg_scholarEssay', 'rgEssayCount');

    /* ===== 6. Conditional Scholarship Fields ===== */
    const trackRadios = form.querySelectorAll('input[name="jalur_pendaftaran"]');
    const scholarExtra = document.getElementById('rgScholarExtra');
    trackRadios.forEach(r => {
        r.addEventListener('change', () => {
            if (scholarExtra) {
                scholarExtra.style.display = r.value === 'beasiswa' ? 'block' : 'none';
            }
        });
    });

    /* ===== 7. Auto-Save Draft (localStorage) ===== */
    const DRAFT_KEY = 'rg_registration_draft';
    const draftInfo = document.getElementById('rgDraftInfo');
    const clearDraftBtn = document.getElementById('rgClearDraft');

    const saveDraft = () => {
        const data = {};
        form.querySelectorAll('input, textarea, select').forEach(el => {
            if (!el.name) return;
            if (el.type === 'radio') {
                if (el.checked) data[el.name] = el.value;
            } else if (el.type === 'checkbox') {
                data[el.name] = el.checked;
            } else {
                data[el.name] = el.value;
            }
        });
        localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
        if (draftInfo) draftInfo.style.display = 'flex';
    };

    const loadDraft = () => {
        const saved = localStorage.getItem(DRAFT_KEY);
        if (!saved) return;
        try {
            const data = JSON.parse(saved);
            Object.keys(data).forEach(name => {
                const el = form.querySelector(`[name="${name}"]`);
                if (!el) return;
                if (el.type === 'radio') {
                    const target = form.querySelector(`[name="${name}"][value="${data[name]}"]`);
                    if (target) target.checked = true;
                } else if (el.type === 'checkbox') {
                    el.checked = data[name];
                } else {
                    el.value = data[name];
                }
            });
            if (draftInfo) draftInfo.style.display = 'flex';
            // Trigger change events for conditional fields
            trackRadios.forEach(r => { if (r.checked) r.dispatchEvent(new Event('change')); });
            // Update counters
            document.getElementById('rg_address')?.dispatchEvent(new Event('input'));
            document.getElementById('rg_scholarEssay')?.dispatchEvent(new Event('input'));
        } catch (e) {
            console.warn('[IFPro] Draft corrupt, cleared');
            localStorage.removeItem(DRAFT_KEY);
        }
    };

    // Auto-save setiap 1.5 dtk setelah perubahan
    let saveTimer;
    form.addEventListener('input', () => {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveDraft, 1500);
    });
    form.addEventListener('change', saveDraft);

    if (clearDraftBtn) {
        clearDraftBtn.addEventListener('click', () => {
            if (confirm('Hapus semua data draft?')) {
                localStorage.removeItem(DRAFT_KEY);
                form.reset();
                if (draftInfo) draftInfo.style.display = 'none';
                form.querySelectorAll('.valid, .invalid').forEach(el => el.classList.remove('valid', 'invalid'));
                form.querySelectorAll('.rg-field-msg').forEach(el => el.textContent = '');
            }
        });
    }

    // Load draft saat halaman dibuka (kecuali ada success)
    <?php if (!$success): ?>
    loadDraft();
    <?php endif; ?>

    /* ===== 8. Build Review (Step 3) ===== */
    function buildReview() {
        const getVal = id => document.getElementById(id)?.value || '-';
        const setText = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val || '-';
        };

        setText('rv_nama', getVal('rg_nama'));
        setText('rv_email', getVal('rg_email'));
        setText('rv_phone', getVal('rg_phone'));
        setText('rv_address', getVal('rg_address') || '-');
        setText('rv_school', getVal('rg_school'));
        setText('rv_major', getVal('rg_major') || '-');
        setText('rv_grade', getVal('rg_grade') || '-');

        const track = form.querySelector('input[name="jalur_pendaftaran"]:checked');
        setText('rv_track', track ? track.value.charAt(0).toUpperCase() + track.value.slice(1) : '-');
    }

    /* ===== 9. Agreement Validation ===== */
    const agreeBox = document.getElementById('rgAgree');
    form.addEventListener('submit', e => {
        if (!validateStep(3)) {
            e.preventDefault();
            return;
        }
        if (agreeBox && !agreeBox.checked) {
            e.preventDefault();
            agreeBox.parentElement.style.animation = 'rgShake .5s';
            setTimeout(() => agreeBox.parentElement.style.animation = '', 500);
            return;
        }

        // Clear draft saat submit
        localStorage.removeItem(DRAFT_KEY);

        // Loading state
        const submitBtn = document.getElementById('rgSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Mengirim...</span>';
        }
    });

    /* ===== 10. Success Modal + Confetti ===== */
    const successModal = document.getElementById('rgSuccessModal');
    const confettiCanvas = document.getElementById('rgConfetti');

    if (successModal) {
        // Launch confetti
        if (confettiCanvas) launchConfetti();

        // Close handler
        document.getElementById('rgModalClose')?.addEventListener('click', () => {
            successModal.style.animation = 'rgFadeOut .3s forwards';
            setTimeout(() => successModal.remove(), 300);
        });

        // Download receipt (simple HTML to text)
        document.getElementById('rgDownloadReceipt')?.addEventListener('click', () => {
            const receipt = document.getElementById('rgReceipt');
            if (!receipt) return;
            const text = receipt.innerText;
            const blob = new Blob([text], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'bukti-pendaftaran-<?= $receipt['number'] ?? 'draft' ?>.txt';
            a.click();
            URL.revokeObjectURL(url);
        });
    }

    function launchConfetti() {
        const canvas = confettiCanvas;
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        canvas.style.display = 'block';

        const colors = ['#10B981', '#34D399', '#F59E0B', '#3B82F6', '#EF4444', '#8B5CF6', '#FFFFFF'];
        const particles = [];

        for (let i = 0; i < 150; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: -20 - Math.random() * canvas.height * 0.5,
                w: Math.random() * 10 + 5,
                h: Math.random() * 6 + 3,
                color: colors[Math.floor(Math.random() * colors.length)],
                vx: (Math.random() - 0.5) * 4,
                vy: Math.random() * 3 + 2,
                rot: Math.random() * Math.PI * 2,
                vr: (Math.random() - 0.5) * 0.2,
                gravity: 0.1
            });
        }

        let frame = 0;
        const maxFrames = 240;

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.vy += p.gravity;
                p.x += p.vx;
                p.y += p.vy;
                p.rot += p.vr;

                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rot);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);
                ctx.restore();
            });

            frame++;
            if (frame < maxFrames) {
                requestAnimationFrame(animate);
            } else {
                canvas.style.display = 'none';
            }
        }
        animate();

        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }, { once: true });
    }

    // Fade-out keyframes
    const fadeOutStyle = document.createElement('style');
    fadeOutStyle.textContent = `@keyframes rgFadeOut { to { opacity: 0; } }`;
    document.head.appendChild(fadeOutStyle);

    /* ===== 11. Keyboard Navigation ===== */
    form.addEventListener('keydown', e => {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'BUTTON') {
            e.preventDefault();
            const next = form.querySelector('.rg-step.active .rg-next-btn');
            if (next) next.click();
            else form.querySelector('.rg-submit-btn')?.click();
        }
    });

    /* ===== 12. Focus Trap di Modal ===== */
    if (successModal) {
        const focusable = successModal.querySelectorAll('button, a, input, [tabindex]:not([tabindex="-1"])');
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        successModal.addEventListener('keydown', e => {
            if (e.key !== 'Tab') return;
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault(); last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault(); first.focus();
            }
        });
        first?.focus();
    }

})();
</script>

<?php require_once 'includes/footer.php'; ?>