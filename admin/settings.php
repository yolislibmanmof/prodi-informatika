<?php
require_once 'includes/auth.php';
$pageTitle = 'Pengaturan Website';

try {
    $db = getDB();
} catch (Throwable $e) {
    $db = null;
    setFlash('error', 'Koneksi database gagal');
}

// Fetch current settings (Anti-Error)
$settingsData = [];
if ($db) {
    try {
        $settingsData = $db->query("SELECT key_name, value FROM pengaturan")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Throwable $e) {
        $settingsData = [];
    }
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    try {
        $textSettings = [
            'site_name', 'tagline', 'kaprodi_nama', 'kaprodi_gelar', 'kaprodi_sambutan',
            'seo_description', 'seo_keywords', 'social_instagram', 'social_facebook', 
            'social_twitter', 'social_linkedin', 'contact_email', 'contact_phone', 
            'contact_whatsapp', 'contact_address', 'contact_maps', 'maintenance_message'
        ];
        
        foreach ($textSettings as $key) {
            $value = isset($_POST[$key]) ? clean($_POST[$key]) : '';
            $stmt = $db->prepare("INSERT INTO pengaturan (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        // Maintenance mode checkbox
        $maintValue = isset($_POST['maintenance_mode']) ? '1' : '0';
        $stmt = $db->prepare("INSERT INTO pengaturan (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute(['maintenance_mode', $maintValue, $maintValue]);

        // Handle image settings with validated uploads and safe replace ordering.
        // Keep the old asset in place until the database points to the new file.
        $fileSettings = ['logo', 'favicon', 'kaprodi_foto'];
        foreach ($fileSettings as $key) {
            if (empty($_FILES[$key]['name'])) {
                continue;
            }

            $folder = $key === 'favicon' ? 'uploads' : 'uploads/settings';
            $newFile = uploadImage($_FILES[$key], $folder);
            if (!$newFile) {
                throw new RuntimeException('Gagal memvalidasi file pengaturan: ' . $key);
            }

            $oldValue = (string)($settingsData[$key] ?? '');
            try {
                $stmt = $db->prepare("INSERT INTO pengaturan (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
                $stmt->execute([$key, $newFile, $newFile]);
            } catch (Throwable $saveError) {
                deleteUploadedFile((string)$newFile, $folder);
                throw $saveError;
            }

            $settingsData[$key] = $newFile;
            if ($oldValue !== '' && $oldValue !== $newFile) {
                deleteUploadedFile($oldValue, $folder);
            }
        }
        
        setFlash('success', 'Semua pengaturan website berhasil disimpan!');
        header('Location: settings.php'); exit;
    } catch (Throwable $e) {
        setFlash('error', 'Gagal menyimpan pengaturan: ' . $e->getMessage());
    }
}

include 'includes/header.php';
?>

<div class="page-content settings-page-ultimate">
    <!-- Page Header -->
    <div class="page-header-ultimate">
        <div class="header-content">
            <div class="breadcrumb-ultimate">
                <a href="dashboard.php"><i class="fas fa-home"></i></a>
                <i class="fas fa-chevron-right"></i>
                <span>Pengaturan</span>
            </div>
            <h1 class="page-title">
                <i class="fas fa-cogs"></i>
                Pengaturan Website
            </h1>
            <p class="page-subtitle">Konfigurasi lengkap identitas, branding, dan informasi publik website</p>
        </div>
        <div class="header-actions">
            <button type="button" class="btn-icon-ultimate" onclick="location.reload()" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="btn-icon-ultimate" onclick="exportSettings()" title="Export Config">
                <i class="fas fa-download"></i>
            </button>
        </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert-ultimate alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?> fade-in">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <span><?= clean($flash['message']) ?></span>
        <button onclick="this.parentElement.remove()" class="alert-close"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="settingsForm" class="settings-form-ultimate">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
        
        <!-- Tabs Navigation -->
        <div class="settings-tabs-ultimate">
            <button type="button" class="tab-btn-ultimate active" data-tab="tab-umum">
                <i class="fas fa-globe"></i>
                <span>Umum & Sistem</span>
            </button>
            <button type="button" class="tab-btn-ultimate" data-tab="tab-branding">
                <i class="fas fa-palette"></i>
                <span>Branding</span>
            </button>
            <button type="button" class="tab-btn-ultimate" data-tab="tab-kaprodi">
                <i class="fas fa-user-tie"></i>
                <span>Profil Kaprodi</span>
            </button>
            <button type="button" class="tab-btn-ultimate" data-tab="tab-seo">
                <i class="fas fa-search"></i>
                <span>SEO & Sosmed</span>
            </button>
            <button type="button" class="tab-btn-ultimate" data-tab="tab-kontak">
                <i class="fas fa-address-book"></i>
                <span>Kontak</span>
            </button>
        </div>

        <!-- Tab Contents -->
        <div class="settings-content-ultimate">
            
            <!-- TAB 1: Umum & Sistem -->
            <div class="tab-content-ultimate active" id="tab-umum">
                <div class="card-ultimate">
                    <div class="card-header-ultimate">
                        <div class="card-icon" style="background: linear-gradient(135deg, #3B82F6, #1E40AF);">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <h3>Identitas Dasar</h3>
                            <p>Informasi utama yang ditampilkan di header dan title website</p>
                        </div>
                    </div>
                    <div class="card-body-ultimate">
                        <div class="form-grid-2">
                            <div class="form-group-ultimate">
                                <label>Nama Website <span class="required">*</span></label>
                                <div class="input-group-ultimate">
                                    <i class="fas fa-font"></i>
                                    <input type="text" name="site_name" value="<?= htmlspecialchars($settingsData['site_name'] ?? 'Informatika Pro') ?>" required placeholder="Contoh: Informatika Pro">
                                </div>
                            </div>
                            <div class="form-group-ultimate">
                                <label>Tagline / Slogan</label>
                                <div class="input-group-ultimate">
                                    <i class="fas fa-quote-right"></i>
                                    <input type="text" name="tagline" value="<?= htmlspecialchars($settingsData['tagline'] ?? '') ?>" placeholder="Contoh: Mencetak Inovator Digital">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-ultimate">
                    <div class="card-header-ultimate">
                        <div class="card-icon" style="background: linear-gradient(135deg, #F59E0B, #B45309);">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div>
                            <h3>Mode Pemeliharaan</h3>
                            <p>Aktifkan untuk menampilkan halaman maintenance kepada pengunjung</p>
                        </div>
                    </div>
                    <div class="card-body-ultimate">
                        <div class="toggle-section-ultimate">
                            <label class="toggle-switch-ultimate">
                                <input type="checkbox" name="maintenance_mode" value="1" <?= ($settingsData['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?> id="maintToggle">
                                <span class="toggle-slider-ultimate"></span>
                            </label>
                            <div class="toggle-info">
                                <span class="toggle-label" id="maintLabel"><?= ($settingsData['maintenance_mode'] ?? '0') === '1' ? 'Mode Maintenance Aktif' : 'Mode Maintenance Nonaktif' ?></span>
                                <small id="maintSublabel"><?= ($settingsData['maintenance_mode'] ?? '0') === '1' ? 'Website tidak dapat diakses publik' : 'Website dapat diakses normal' ?></small>
                            </div>
                        </div>
                        <div class="form-group-ultimate" id="maintMessageField" style="<?= ($settingsData['maintenance_mode'] ?? '0') === '1' ? '' : 'display:none;' ?>">
                            <label>Pesan Maintenance</label>
                            <div class="textarea-group-ultimate">
                                <textarea name="maintenance_message" rows="3" placeholder="Website sedang dalam perbaikan. Silakan kembali nanti."><?= htmlspecialchars($settingsData['maintenance_message'] ?? '') ?></textarea>
                                <span class="char-counter"><span id="maintCount">0</span>/200</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Branding -->
            <div class="tab-content-ultimate" id="tab-branding">
                <div class="card-ultimate">
                    <div class="card-header-ultimate">
                        <div class="card-icon" style="background: linear-gradient(135deg, #8B5CF6, #5B21B6);">
                            <i class="fas fa-image"></i>
                        </div>
                        <div>
                            <h3>Logo & Favicon</h3>
                            <p>Upload aset visual website. Format: PNG, JPG, WEBP (Max 2MB)</p>
                        </div>
                    </div>
                    <div class="card-body-ultimate">
                        <div class="upload-grid-ultimate">
                            <!-- Logo Upload -->
                            <div class="upload-section-ultimate">
                                <label class="upload-label">Logo Website</label>
                                <div class="upload-box-ultimate" onclick="document.getElementById('logoInput').click()">
                                    <img src="<?= !empty($settingsData['logo']) ? '../' . $settingsData['logo'] : 'https://via.placeholder.com/200x60/3B82F6/FFFFFF?text=Preview+Logo' ?>" id="logoPreview" class="upload-preview">
                                    <div class="upload-overlay-ultimate">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span>Klik untuk ganti logo</span>
                                        <small>PNG, JPG, WEBP (Max 2MB)</small>
                                    </div>
                                    <input type="file" id="logoInput" name="logo" accept="image/*" hidden onchange="previewImage(this, 'logoPreview')">
                                </div>
                                <small class="upload-hint">Ukuran ideal: 200x60px</small>
                            </div>
                            <!-- Favicon Upload -->
                            <div class="upload-section-ultimate">
                                <label class="upload-label">Favicon</label>
                                <div class="upload-box-ultimate" onclick="document.getElementById('faviconInput').click()">
                                    <img src="<?= !empty($settingsData['favicon']) ? '../' . $settingsData['favicon'] : 'https://via.placeholder.com/64x64/10B981/FFFFFF?text=ICO' ?>" id="faviconPreview" class="upload-preview favicon-preview">
                                    <div class="upload-overlay-ultimate">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span>Ganti favicon</span>
                                        <small>PNG, ICO (Max 512KB)</small>
                                    </div>
                                    <input type="file" id="faviconInput" name="favicon" accept="image/x-icon,image/png,image/jpeg" hidden onchange="previewImage(this, 'faviconPreview')">
                                </div>
                                <small class="upload-hint">Ukuran ideal: 32x32px atau 64x64px</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Profil Kaprodi -->
            <div class="tab-content-ultimate" id="tab-kaprodi">
                <div class="card-ultimate">
                    <div class="card-header-ultimate">
                        <div class="card-icon" style="background: linear-gradient(135deg, #10B981, #047857);">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div>
                            <h3>Data Ketua Program Studi</h3>
                            <p>Informasi yang ditampilkan di halaman Sambutan Kaprodi</p>
                        </div>
                    </div>
                    <div class="card-body-ultimate">
                        <div class="form-grid-2">
                            <div class="form-group-ultimate">
                                <label>Nama Lengkap</label>
                                <div class="input-group-ultimate">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="kaprodi_nama" value="<?= htmlspecialchars($settingsData['kaprodi_nama'] ?? '') ?>" placeholder="Contoh: Dr. Budi Santoso">
                                </div>
                            </div>
                            <div class="form-group-ultimate">
                                <label>Gelar Akademik</label>
                                <div class="input-group-ultimate">
                                    <i class="fas fa-graduation-cap"></i>
                                    <input type="text" name="kaprodi_gelar" value="<?= htmlspecialchars($settingsData['kaprodi_gelar'] ?? '') ?>" placeholder="Contoh: S.Kom., M.Kom.">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group-ultimate" style="margin-top:24px;">
                            <label>Foto Kaprodi</label>
                            <div class="upload-box-ultimate circular" onclick="document.getElementById('kaprodiFotoInput').click()">
                                <img src="<?= !empty($settingsData['kaprodi_foto']) ? '../' . $settingsData['kaprodi_foto'] : 'https://via.placeholder.com/150x150/8B5CF6/FFFFFF?text=Foto' ?>" id="kaprodiFotoPreview" class="upload-preview circular-preview">
                                <div class="upload-overlay-ultimate">
                                    <i class="fas fa-camera"></i>
                                    <span>Ganti Foto</span>
                                    <small>JPG, PNG (Max 2MB)</small>
                                </div>
                                <input type="file" id="kaprodiFotoInput" name="kaprodi_foto" accept="image/*" hidden onchange="previewImage(this, 'kaprodiFotoPreview')">
                            </div>
                            <small class="upload-hint">Ukuran ideal: 300x300px (persegi)</small>
                        </div>

                        <div class="form-group-ultimate" style="margin-top:24px;">
                            <label>Kata Sambutan</label>
                            <div class="textarea-group-ultimate">
                                <textarea name="kaprodi_sambutan" rows="6" placeholder="Tulis kata sambutan untuk calon mahasiswa..."><?= htmlspecialchars($settingsData['kaprodi_sambutan'] ?? '') ?></textarea>
                                <span class="char-counter"><span id="sambutanCount">0</span>/500</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: SEO & Sosial Media -->
            <div class="tab-content-ultimate" id="tab-seo">
                <div class="card-ultimate">
                    <div class="card-header-ultimate">
                        <div class="card-icon" style="background: linear-gradient(135deg, #EC4899, #BE185D);">
                            <i class="fas fa-search"></i>
                        </div>
                        <div>
                            <h3>SEO Meta Tags</h3>
                            <p>Optimasi website agar mudah ditemukan di mesin pencari</p>
                        </div>
                    </div>
                    <div class="card-body-ultimate">
                        <div class="form-group-ultimate">
                            <label>Meta Description</label>
                            <div class="textarea-group-ultimate">
                                <textarea name="seo_description" rows="3" maxlength="160" placeholder="Deskripsi singkat website (maks 160 karakter)"><?= htmlspecialchars($settingsData['seo_description'] ?? '') ?></textarea>
                                <span class="char-counter"><span id="descCount">0</span>/160</span>
                            </div>
                            <small class="form-hint">Optimal untuk SEO: 150-160 karakter</small>
                        </div>
                        <div class="form-group-ultimate">
                            <label>Meta Keywords</label>
                            <div class="input-group-ultimate">
                                <i class="fas fa-tags"></i>
                                <input type="text" name="seo_keywords" value="<?= htmlspecialchars($settingsData['seo_keywords'] ?? '') ?>" placeholder="informatika, kampus IT, AI, software engineer (pisahkan dengan koma)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-ultimate">
                    <div class="card-header-ultimate">
                        <div class="card-icon" style="background: linear-gradient(135deg, #06B6D4, #0891B2);">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <div>
                            <h3>Tautan Media Sosial</h3>
                            <p>Isi dengan URL lengkap (https://...). Kosongkan jika tidak ada</p>
                        </div>
                    </div>
                    <div class="card-body-ultimate">
                        <div class="form-grid-2">
                            <div class="form-group-ultimate">
                                <label><i class="fab fa-instagram" style="color:#E1306C;"></i> Instagram</label>
                                <div class="input-group-ultimate">
                                    <i class="fab fa-instagram"></i>
                                    <input type="url" name="social_instagram" value="<?= htmlspecialchars($settingsData['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/...">
                                </div>
                            </div>
                            <div class="form-group-ultimate">
                                <label><i class="fab fa-facebook" style="color:#1877F2;"></i> Facebook</label>
                                <div class="input-group-ultimate">
                                    <i class="fab fa-facebook"></i>
                                    <input type="url" name="social_facebook" value="<?= htmlspecialchars($settingsData['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/...">
                                </div>
                            </div>
                            <div class="form-group-ultimate">
                                <label><i class="fab fa-twitter" style="color:#1DA1F2;"></i> Twitter / X</label>
                                <div class="input-group-ultimate">
                                    <i class="fab fa-twitter"></i>
                                    <input type="url" name="social_twitter" value="<?= htmlspecialchars($settingsData['social_twitter'] ?? '') ?>" placeholder="https://twitter.com/...">
                                </div>
                            </div>
                            <div class="form-group-ultimate">
                                <label><i class="fab fa-linkedin" style="color:#0A66C2;"></i> LinkedIn</label>
                                <div class="input-group-ultimate">
                                    <i class="fab fa-linkedin"></i>
                                    <input type="url" name="social_linkedin" value="<?= htmlspecialchars($settingsData['social_linkedin'] ?? '') ?>" placeholder="https://linkedin.com/...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 5: Kontak -->
            <div class="tab-content-ultimate" id="tab-kontak">
                <div class="card-ultimate">
                    <div class="card-header-ultimate">
                        <div class="card-icon" style="background: linear-gradient(135deg, #F97316, #C2410C);">
                            <i class="fas fa-address-card"></i>
                        </div>
                        <div>
                            <h3>Informasi Kontak Publik</h3>
                            <p>Data yang ditampilkan di footer dan halaman kontak website</p>
                        </div>
                    </div>
                    <div class="card-body-ultimate">
                        <div class="form-grid-2">
                            <div class="form-group-ultimate">
                                <label>Email Resmi <span class="required">*</span></label>
                                <div class="input-group-ultimate">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="contact_email" value="<?= htmlspecialchars($settingsData['contact_email'] ?? '') ?>" required placeholder="info@informatikapro.ac.id">
                                </div>
                            </div>
                            <div class="form-group-ultimate">
                                <label>Nomor Telepon Kantor</label>
                                <div class="input-group-ultimate">
                                    <i class="fas fa-phone"></i>
                                    <input type="text" name="contact_phone" value="<?= htmlspecialchars($settingsData['contact_phone'] ?? '') ?>" placeholder="(021) 1234-5678">
                                </div>
                            </div>
                            <div class="form-group-ultimate">
                                <label>Nomor WhatsApp</label>
                                <div class="input-group-ultimate">
                                    <i class="fab fa-whatsapp"></i>
                                    <input type="text" name="contact_whatsapp" value="<?= htmlspecialchars($settingsData['contact_whatsapp'] ?? '') ?>" placeholder="6281234567890">
                                </div>
                            </div>
                        </div>
                        <div class="form-group-ultimate" style="margin-top:20px;">
                            <label>Alamat Lengkap</label>
                            <div class="textarea-group-ultimate">
                                <textarea name="contact_address" rows="3" placeholder="Jl. Informatika No. 1, Jakarta, Indonesia"><?= htmlspecialchars($settingsData['contact_address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="form-group-ultimate">
                            <label>Google Maps Embed URL</label>
                            <div class="input-group-ultimate">
                                <i class="fas fa-map-marked-alt"></i>
                                <input type="text" name="contact_maps" value="<?= htmlspecialchars($settingsData['contact_maps'] ?? '') ?>" placeholder="https://www.google.com/maps/embed?pb=...">
                            </div>
                            <small class="form-hint">
                                <i class="fas fa-info-circle"></i> 
                                Ambil dari Google Maps > Share > Embed a map > Copy HTML src
                            </small>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Sticky Footer Action -->
        <div class="settings-actions-ultimate">
            <button type="button" class="btn-secondary-ultimate" onclick="if(confirm('Reset form ke data terakhir yang disimpan?')) location.reload();">
                <i class="fas fa-undo"></i> Reset Form
            </button>
            <button type="submit" class="btn-primary-ultimate">
                <i class="fas fa-save"></i> Simpan Semua Perubahan
            </button>
        </div>
    </form>
</div>

<style>
/* ============================================================
   SETTINGS PAGE ULTIMATE MULTIMATE PROFESSIONAL v4.0
   Glassmorphism, Smooth Animations, Professional Design
============================================================ */

.settings-page-ultimate {
    padding: var(--spacing-lg);
    animation: fadeIn 0.4s ease;
}

/* Page Header Ultimate */
.page-header-ultimate {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 32px;
    gap: 20px;
    flex-wrap: wrap;
}

.header-content {
    flex: 1;
    min-width: 0;
}

.breadcrumb-ultimate {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 8px;
}

.breadcrumb-ultimate a {
    color: var(--primary);
    text-decoration: none;
    transition: color var(--transition-fast);
}

.breadcrumb-ultimate a:hover {
    color: var(--primary-dark);
}

.breadcrumb-ultimate i {
    font-size: 0.7rem;
    color: var(--gray-400);
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark);
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

[data-theme="dark"] .page-title {
    color: var(--white);
}

.page-title i {
    color: var(--primary);
}

.page-subtitle {
    font-size: 0.95rem;
    color: var(--gray-600);
    margin: 0;
}

[data-theme="dark"] .page-subtitle {
    color: var(--gray-400);
}

.header-actions {
    display: flex;
    gap: 8px;
}

.btn-icon-ultimate {
    width: 42px;
    height: 42px;
    border-radius: var(--radius);
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-600);
    transition: all var(--transition);
    font-size: 1rem;
}

[data-theme="dark"] .btn-icon-ultimate {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.1);
    color: var(--gray-300);
}

.btn-icon-ultimate:hover {
    background: var(--primary);
    color: var(--white);
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}

/* Alert Ultimate */
.alert-ultimate {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: var(--radius-lg);
    margin-bottom: 24px;
    box-shadow: var(--shadow-md);
    animation: slideInDown 0.4s ease;
}

.alert-success {
    background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(5,150,105,0.05));
    border: 1px solid rgba(16,185,129,0.2);
    color: #047857;
}

.alert-error {
    background: linear-gradient(135deg, rgba(239,68,68,0.1), rgba(220,38,38,0.05));
    border: 1px solid rgba(239,68,68,0.2);
    color: #DC2626;
}

.alert-close {
    margin-left: auto;
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    padding: 4px;
    opacity: 0.7;
    transition: opacity var(--transition-fast);
}

.alert-close:hover {
    opacity: 1;
}

@keyframes slideInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: none; }
}

/* Tabs Ultimate */
.settings-tabs-ultimate {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid var(--gray-200);
    margin-bottom: 32px;
    overflow-x: auto;
    padding-bottom: 2px;
    scrollbar-width: thin;
}

[data-theme="dark"] .settings-tabs-ultimate {
    border-bottom-color: rgba(255,255,255,0.1);
}

.tab-btn-ultimate {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--gray-600);
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all var(--transition);
    white-space: nowrap;
    font-family: inherit;
    border-radius: 8px 8px 0 0;
    position: relative;
}

[data-theme="dark"] .tab-btn-ultimate {
    color: var(--gray-400);
}

.tab-btn-ultimate:hover {
    color: var(--primary);
    background: rgba(16,185,129,0.05);
}

.tab-btn-ultimate.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: rgba(16,185,129,0.08);
}

.tab-btn-ultimate i {
    font-size: 1.1rem;
}

/* Tab Content */
.tab-content-ultimate {
    display: none;
    animation: fadeIn 0.4s ease;
}

.tab-content-ultimate.active {
    display: block;
}

/* Card Ultimate */
.card-ultimate {
    background: var(--white);
    border-radius: var(--radius-2xl);
    border: 1px solid var(--gray-100);
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
    overflow: hidden;
    transition: all var(--transition);
}

[data-theme="dark"] .card-ultimate {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.08);
}

.card-ultimate:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.card-header-ultimate {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 24px 28px;
    border-bottom: 1px solid var(--gray-100);
    background: linear-gradient(135deg, rgba(16,185,129,0.03), rgba(59,130,246,0.03));
}

[data-theme="dark"] .card-header-ultimate {
    border-color: rgba(255,255,255,0.08);
    background: rgba(0,0,0,0.2);
}

.card-icon {
    width: 56px;
    height: 56px;
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-size: 1.5rem;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    flex-shrink: 0;
}

.card-header-ultimate h3 {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--dark);
    margin: 0 0 4px 0;
}

[data-theme="dark"] .card-header-ultimate h3 {
    color: var(--white);
}

.card-header-ultimate p {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin: 0;
}

[data-theme="dark"] .card-header-ultimate p {
    color: var(--gray-400);
}

.card-body-ultimate {
    padding: 28px;
}

/* Form Grid */
.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* Form Group Ultimate */
.form-group-ultimate {
    margin-bottom: 20px;
}

.form-group-ultimate label {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 8px;
}

[data-theme="dark"] .form-group-ultimate label {
    color: var(--gray-200);
}

.required {
    color: var(--danger);
}

.input-group-ultimate,
.textarea-group-ultimate {
    position: relative;
}

.input-group-ultimate i,
.textarea-group-ultimate i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-400);
    font-size: 1rem;
    z-index: 1;
    pointer-events: none;
}

.input-group-ultimate input,
.textarea-group-ultimate textarea {
    width: 100%;
    padding: 12px 16px 12px 46px;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: 0.95rem;
    font-family: inherit;
    background: var(--gray-50);
    transition: all var(--transition);
    color: var(--dark);
}

[data-theme="dark"] .input-group-ultimate input,
[data-theme="dark"] .textarea-group-ultimate textarea {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    color: var(--white);
}

.input-group-ultimate input:focus,
.textarea-group-ultimate textarea:focus {
    outline: none;
    border-color: var(--primary);
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(16,185,129,0.1);
}

[data-theme="dark"] .input-group-ultimate input:focus,
[data-theme="dark"] .textarea-group-ultimate textarea:focus {
    background: rgba(255,255,255,0.08);
}

.textarea-group-ultimate textarea {
    min-height: 100px;
    resize: vertical;
    padding-left: 16px;
}

.char-counter {
    position: absolute;
    bottom: 10px;
    right: 16px;
    font-size: 0.75rem;
    color: var(--gray-500);
    background: var(--white);
    padding: 2px 8px;
    border-radius: 4px;
}

[data-theme="dark"] .char-counter {
    background: var(--dark);
    color: var(--gray-400);
}

.form-hint {
    display: block;
    color: var(--gray-600);
    font-size: 0.8rem;
    margin-top: 6px;
}

[data-theme="dark"] .form-hint {
    color: var(--gray-400);
}

.form-hint i {
    margin-right: 4px;
    color: var(--primary);
}

/* Upload Box Ultimate */
.upload-grid-ultimate {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.upload-section-ultimate {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.upload-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--dark);
}

[data-theme="dark"] .upload-label {
    color: var(--gray-200);
}

.upload-box-ultimate {
    position: relative;
    border: 2px dashed var(--gray-300);
    border-radius: var(--radius-xl);
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: all var(--transition);
    background: var(--gray-50);
    min-height: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

[data-theme="dark"] .upload-box-ultimate {
    background: rgba(255,255,255,0.03);
    border-color: rgba(255,255,255,0.1);
}

.upload-box-ultimate:hover {
    border-color: var(--primary);
    background: rgba(16,185,129,0.05);
    transform: translateY(-2px);
}

.upload-box-ultimate.circular {
    border-radius: 50%;
    width: 180px;
    height: 180px;
    min-height: auto;
    margin: 0 auto;
}

.upload-preview {
    max-width: 100%;
    max-height: 120px;
    object-fit: contain;
    margin-bottom: 12px;
    transition: opacity var(--transition);
}

.upload-preview.favicon-preview {
    max-width: 64px;
    max-height: 64px;
}

.upload-preview.circular-preview {
    max-width: 120px;
    max-height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--white);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.upload-overlay-ultimate {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(16,185,129,0.95), rgba(5,150,105,0.95));
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--white);
    opacity: 0;
    transition: opacity var(--transition);
    gap: 8px;
}

.upload-box-ultimate:hover .upload-overlay-ultimate {
    opacity: 1;
}

.upload-overlay-ultimate i {
    font-size: 2rem;
}

.upload-overlay-ultimate span {
    font-size: 0.95rem;
    font-weight: 600;
}

.upload-overlay-ultimate small {
    font-size: 0.75rem;
    opacity: 0.9;
}

.upload-hint {
    display: block;
    color: var(--gray-600);
    font-size: 0.8rem;
    text-align: center;
}

[data-theme="dark"] .upload-hint {
    color: var(--gray-400);
}

/* Toggle Switch Ultimate */
.toggle-section-ultimate {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    margin-bottom: 20px;
}

[data-theme="dark"] .toggle-section-ultimate {
    background: rgba(255,255,255,0.04);
}

.toggle-switch-ultimate {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 30px;
    flex-shrink: 0;
}

.toggle-switch-ultimate input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider-ultimate {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background-color: var(--gray-300);
    border-radius: 34px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

[data-theme="dark"] .toggle-slider-ultimate {
    background-color: rgba(255,255,255,0.2);
}

.toggle-slider-ultimate:before {
    position: absolute;
    content: "";
    height: 24px;
    width: 24px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

input:checked + .toggle-slider-ultimate {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
}

input:checked + .toggle-slider-ultimate:before {
    transform: translateX(30px);
}

.toggle-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.toggle-label {
    font-weight: 700;
    font-size: 1rem;
    color: var(--dark);
    transition: color var(--transition-fast);
}

[data-theme="dark"] .toggle-label {
    color: var(--white);
}

#maintLabel {
    color: var(--primary);
}

#maintSublabel {
    font-size: 0.85rem;
    color: var(--gray-600);
}

[data-theme="dark"] #maintSublabel {
    color: var(--gray-400);
}

/* Actions Footer Ultimate */
.settings-actions-ultimate {
    position: sticky;
    bottom: 20px;
    background: var(--white);
    padding: 20px 28px;
    border-radius: var(--radius-xl);
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    border: 1px solid var(--gray-100);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    z-index: 50;
    backdrop-filter: blur(10px);
}

[data-theme="dark"] .settings-actions-ultimate {
    background: var(--dark-2);
    border-color: rgba(255,255,255,0.1);
}

.btn-secondary-ultimate,
.btn-primary-ultimate {
    padding: 14px 28px;
    border-radius: var(--radius);
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all var(--transition);
    font-family: inherit;
    border: none;
}

.btn-secondary-ultimate {
    background: var(--gray-100);
    color: var(--gray-700);
}

[data-theme="dark"] .btn-secondary-ultimate {
    background: rgba(255,255,255,0.08);
    color: var(--gray-300);
}

.btn-secondary-ultimate:hover {
    background: var(--gray-200);
    transform: translateY(-2px);
}

.btn-primary-ultimate {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: var(--white);
    box-shadow: 0 4px 16px rgba(16,185,129,0.4);
}

.btn-primary-ultimate:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16,185,129,0.5);
}

/* Responsive */
@media (max-width: 1024px) {
    .form-grid-2,
    .upload-grid-ultimate {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .settings-page-ultimate {
        padding: 16px;
    }
    
    .page-header-ultimate {
        flex-direction: column;
        align-items: stretch;
    }
    
    .header-actions {
        justify-content: flex-end;
    }
    
    .page-title {
        font-size: 1.6rem;
    }
    
    .settings-tabs-ultimate {
        gap: 4px;
    }
    
    .tab-btn-ultimate {
        padding: 12px 16px;
        font-size: 0.85rem;
    }
    
    .card-body-ultimate {
        padding: 20px;
    }
    
    .settings-actions-ultimate {
        position: static;
        flex-direction: column;
        box-shadow: none;
        border: none;
        padding: 0;
        background: transparent;
    }
    
    .settings-actions-ultimate button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    // Tab Switching dengan animasi smooth
    const tabs = document.querySelectorAll('.tab-btn-ultimate');
    const contents = document.querySelectorAll('.tab-content-ultimate');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            
            tab.classList.add('active');
            document.getElementById(tab.dataset.tab).classList.add('active');
            
            // Smooth scroll ke atas jika di mobile
            if (window.innerWidth <= 768) {
                document.querySelector('.settings-form-ultimate').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Image Preview dengan validasi
    window.previewImage = function(input, previewId) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const maxSize = previewId === 'faviconPreview' ? 512 * 1024 : 2 * 1024 * 1024;
            
            if (file.size > maxSize) {
                if (window.IFPro?.toast) {
                    window.IFPro.toast.show(`Ukuran file melebihi batas ${(maxSize / 1024 / 1024).toFixed(0)}MB`, 'error');
                }
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(previewId).src = e.target.result;
                document.getElementById(previewId).style.opacity = '1';
            };
            reader.readAsDataURL(file);
        }
    };

    // Maintenance Toggle dengan animasi
    const maintToggle = document.getElementById('maintToggle');
    const maintLabel = document.getElementById('maintLabel');
    const maintSublabel = document.getElementById('maintSublabel');
    const maintField = document.getElementById('maintMessageField');
    const maintCount = document.getElementById('maintCount');
    
    if (maintToggle) {
        maintToggle.addEventListener('change', function() {
            if (this.checked) {
                maintLabel.textContent = 'Mode Maintenance Aktif';
                maintLabel.style.color = 'var(--danger)';
                maintSublabel.textContent = 'Website tidak dapat diakses publik';
                maintField.style.display = 'block';
                maintField.style.animation = 'fadeIn 0.4s ease';
            } else {
                maintLabel.textContent = 'Mode Maintenance Nonaktif';
                maintLabel.style.color = 'var(--primary)';
                maintSublabel.textContent = 'Website dapat diakses normal';
                maintField.style.display = 'none';
            }
        });
    }

    // Character Counter
    const updateCounter = (textarea, counterId, max) => {
        const count = textarea.value.length;
        const counter = document.getElementById(counterId);
        if (counter) {
            counter.textContent = count;
            if (count >= max * 0.9) {
                counter.style.color = 'var(--warning)';
            } else {
                counter.style.color = '';
            }
        }
    };

    const sambutanTextarea = document.querySelector('textarea[name="kaprodi_sambutan"]');
    if (sambutanTextarea) {
        sambutanTextarea.addEventListener('input', function() {
            updateCounter(this, 'sambutanCount', 500);
        });
        updateCounter(sambutanTextarea, 'sambutanCount', 500);
    }

    const maintTextarea = document.querySelector('textarea[name="maintenance_message"]');
    if (maintTextarea) {
        maintTextarea.addEventListener('input', function() {
            updateCounter(this, 'maintCount', 200);
        });
        updateCounter(maintTextarea, 'maintCount', 200);
    }

    const descTextarea = document.querySelector('textarea[name="seo_description"]');
    if (descTextarea) {
        descTextarea.addEventListener('input', function() {
            updateCounter(this, 'descCount', 160);
        });
        updateCounter(descTextarea, 'descCount', 160);
    }

    // Form Validation & Submit
    const form = document.getElementById('settingsForm');
    form?.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        
        // Auto re-enable after 5 seconds (fallback)
        setTimeout(() => {
            if (submitBtn.disabled) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                if (window.IFPro?.toast) {
                    window.IFPro.toast.show('Proses penyimpanan terlalu lama, silakan coba lagi', 'warning');
                }
            }
        }, 5000);
    });

    // Export Settings (JSON)
    window.exportSettings = function() {
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `settings-backup-${new Date().toISOString().slice(0,10)}.json`;
        a.click();
        URL.revokeObjectURL(url);
        
        if (window.IFPro?.toast) {
            window.IFPro.toast.show('Konfigurasi berhasil di-export', 'success');
        }
    };

    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert-ultimate').forEach(alert => {
            alert.style.transition = 'all 0.4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 400);
        });
    }, 5000);

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S = Save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            form?.requestSubmit();
        }
    });

    console.log('%c⚙️ Settings Page Ultimate Loaded', 'color:#10B981;font-weight:bold;font-size:12px');
    console.log('%c   Shortcuts: Ctrl+S (Save)', 'color:#64748b;font-size:10px');
})();
</script>

<?php include 'includes/footer.php'; ?>