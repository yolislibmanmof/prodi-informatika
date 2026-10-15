<!-- Scroll to Top Button -->
<button class="scroll-top-btn" id="scrollTopBtn" title="Kembali ke atas" aria-label="Scroll to top">
    <i class="fas fa-arrow-up"></i>
    <svg class="progress-ring" viewBox="0 0 36 36" aria-hidden="true">
        <circle class="progress-ring-bg" cx="18" cy="18" r="15" fill="none" stroke-width="2"/>
        <circle class="progress-ring-circle" cx="18" cy="18" r="15" fill="none" stroke-width="2"/>
    </svg>
</button>

<!-- Footer Admin -->
<footer class="admin-footer" role="contentinfo">
    <div class="footer-left">
        <span class="footer-version" title="Version">
            <i class="fas fa-code-branch"></i>
            <span>v3.0.0</span>
        </span>
        <span class="footer-divider" aria-hidden="true"></span>
        <span class="footer-status">
            <span class="status-dot" aria-label="Status: Online"></span>
            <span>Sistem Online</span>
        </span>
        <span class="footer-divider" aria-hidden="true"></span>
        <span class="footer-db-status">
            <i class="fas fa-database"></i>
            <span>Database: <strong id="dbStatus">Connected</strong></span>
        </span>
    </div>
    <div class="footer-right">
        <span class="footer-copyright">
            <i class="fas fa-copyright"></i>
            <?= date('Y') ?> Informatika Pro
        </span>
        <span class="footer-divider" aria-hidden="true"></span>
        <span class="footer-credit">
            Dibuat dengan <i class="fas fa-heart heart-icon" aria-label="love"></i> untuk Komandan
        </span>
        <span class="footer-divider" aria-hidden="true"></span>
        <a href="#" class="footer-link" id="aboutLink" title="Tentang sistem">
            <i class="fas fa-info-circle"></i>
            <span>About</span>
        </a>
        <a href="#" class="footer-link" id="docsLink" title="Dokumentasi">
            <i class="fas fa-book"></i>
            <span>Docs</span>
        </a>
    </div>
</footer>

<!-- Page Transition Overlay -->
<div class="page-transition" id="pageTransition" aria-hidden="true">
    <div class="page-transition-content">
        <div class="page-transition-spinner"></div>
        <div class="page-transition-text">Memuat halaman...</div>
    </div>
</div>

<!-- System Info Modal -->
<div class="modal system-info-modal" id="systemInfoModal" role="dialog" aria-labelledby="systemInfoTitle" aria-hidden="true">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <div>
                <h3 id="systemInfoTitle">
                    <i class="fas fa-info-circle"></i> Informasi Sistem
                </h3>
                <p style="color: var(--gray-600); font-size: 0.85rem; margin-top: 4px;">Detail teknis admin panel</p>
            </div>
            <button class="modal-close" onclick="closeSystemInfo()" aria-label="Close modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="system-info-grid">
                <div class="info-section">
                    <h4><i class="fas fa-server"></i> Server</h4>
                    <div class="info-item">
                        <span class="info-label">PHP Version</span>
                        <span class="info-value"><?= PHP_VERSION ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Server Software</span>
                        <span class="info-value"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Memory Usage</span>
                        <span class="info-value"><?= round(memory_get_usage(true) / 1024 / 1024, 2) ?> MB</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Peak Memory</span>
                        <span class="info-value"><?= round(memory_get_peak_usage(true) / 1024 / 1024, 2) ?> MB</span>
                    </div>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-database"></i> Database</h4>
                    <div class="info-item">
                        <span class="info-label">Driver</span>
                        <span class="info-value">MySQL (PDO)</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Connection</span>
                        <span class="info-value">
                            <span class="badge badge-success">
                                <i class="fas fa-check-circle"></i> Active
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Charset</span>
                        <span class="info-value">utf8mb4</span>
                    </div>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-shield-alt"></i> Security</h4>
                    <div class="info-item">
                        <span class="info-label">CSRF Protection</span>
                        <span class="info-value">
                            <span class="badge badge-success">Enabled</span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Rate Limiting</span>
                        <span class="info-value">
                            <span class="badge badge-success">Active</span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">SQL Injection</span>
                        <span class="info-value">
                            <span class="badge badge-success">Protected</span>
                        </span>
                    </div>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-cog"></i> Application</h4>
                    <div class="info-item">
                        <span class="info-label">Version</span>
                        <span class="info-value">3.0.0 Ultimate</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Update</span>
                        <span class="info-value"><?= date('d M Y') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Environment</span>
                        <span class="info-value">
                            <span class="badge badge-primary">Production</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeSystemInfo()">
                <i class="fas fa-times"></i> Tutup
            </button>
            <button class="btn btn-primary" onclick="exportSystemInfo()">
                <i class="fas fa-download"></i> Export Info
            </button>
        </div>
    </div>
</div>

<script src="assets/js/admin.js"></script>

<style>
/* ============================================================
   FOOTER ULTIMATE - INTEGRATED WITH ADMIN.CSS v3.0
   Menggunakan CSS Variables & Existing Components
============================================================ */

/* ===== Scroll to Top Button ===== */
.scroll-top-btn {
    position: fixed;
    bottom: 32px;
    right: 32px;
    z-index: 999;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: var(--gradient-primary);
    border: none;
    cursor: pointer;
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px) scale(0.85);
    transition: all var(--transition);
    overflow: hidden;
}

.scroll-top-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
    opacity: 0;
    transition: opacity var(--transition);
}

.scroll-top-btn:hover::before {
    opacity: 1;
}

.scroll-top-btn.show {
    opacity: 1;
    visibility: visible;
    transform: none;
}

.scroll-top-btn:hover {
    transform: translateY(-4px) scale(1.08);
    box-shadow: 0 12px 32px rgba(16, 185, 129, 0.5);
}

.scroll-top-btn:active {
    transform: translateY(-2px) scale(1.02);
}

.scroll-top-btn i {
    position: relative;
    z-index: 2;
    transition: transform var(--transition);
}

.scroll-top-btn:hover i {
    transform: translateY(-2px);
}

.progress-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.progress-ring-bg {
    stroke: rgba(255, 255, 255, 0.2);
    stroke-linecap: round;
}

.progress-ring-circle {
    stroke: var(--white);
    stroke-linecap: round;
    stroke-dasharray: 94.25;
    stroke-dashoffset: 94.25;
    transition: stroke-dashoffset 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ===== Admin Footer ===== */
.admin-footer {
    margin-top: auto;
    padding: 18px 32px;
    background: var(--white);
    border-top: 1px solid var(--gray-100);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 0.85rem;
    color: var(--gray-600);
}

[data-theme="dark"] .admin-footer {
    background: var(--dark-2);
    border-top-color: rgba(255, 255, 255, 0.08);
}

.footer-left,
.footer-right {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

.footer-divider {
    width: 1px;
    height: 16px;
    background: var(--gray-200);
    opacity: 0.5;
}

[data-theme="dark"] .footer-divider {
    background: rgba(255, 255, 255, 0.1);
}

.footer-version {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    background: var(--gray-100);
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--gray-700);
    transition: all var(--transition-fast);
}

[data-theme="dark"] .footer-version {
    background: rgba(255, 255, 255, 0.08);
    color: var(--gray-300);
}

.footer-version:hover {
    background: var(--primary);
    color: var(--white);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.footer-version i {
    font-size: 0.85rem;
}

.footer-status,
.footer-db-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    font-weight: 500;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--primary);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    animation: pulseStatus 2s infinite;
}

@keyframes pulseStatus {
    0%, 100% { box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2); }
    50% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0.05); }
}

.footer-db-status i {
    color: var(--primary);
    font-size: 0.9rem;
}

.footer-db-status strong {
    color: var(--primary);
    font-weight: 700;
}

.footer-copyright {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}

.footer-copyright i {
    font-size: 0.85rem;
    color: var(--gray-500);
}

.footer-credit {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.heart-icon {
    color: #EF4444;
    animation: heartbeat 1.5s ease-in-out infinite;
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    10%, 30% { transform: scale(1.15); }
    20%, 40% { transform: scale(1); }
}

.footer-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    color: var(--gray-600);
    text-decoration: none;
    border-radius: var(--radius-sm);
    font-size: 0.82rem;
    font-weight: 500;
    transition: all var(--transition-fast);
}

[data-theme="dark"] .footer-link {
    color: var(--gray-400);
}

.footer-link:hover {
    background: var(--gray-100);
    color: var(--primary);
    transform: translateY(-2px);
}

[data-theme="dark"] .footer-link:hover {
    background: rgba(255, 255, 255, 0.08);
    color: var(--primary-light);
}

.footer-link i {
    font-size: 0.85rem;
}

/* ===== Page Transition Overlay ===== */
.page-transition {
    position: fixed;
    inset: 0;
    z-index: 10000;
    background: linear-gradient(135deg, var(--dark) 0%, var(--dark-2) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition);
}

.page-transition.active {
    opacity: 1;
    visibility: visible;
}

.page-transition-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

.page-transition-spinner {
    width: 50px;
    height: 50px;
    border: 3px solid rgba(16, 185, 129, 0.2);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.page-transition-text {
    color: var(--white);
    font-size: 0.95rem;
    font-weight: 500;
    opacity: 0.9;
}

/* ===== System Info Modal ===== */
.system-info-modal {
    display: none;
}

.system-info-modal.active {
    display: flex;
}

.system-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.info-section {
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--radius);
    border: 1px solid var(--gray-100);
}

[data-theme="dark"] .info-section {
    background: var(--dark);
    border-color: rgba(255, 255, 255, 0.08);
}

.info-section h4 {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

[data-theme="dark"] .info-section h4 {
    color: var(--white);
}

.info-section h4 i {
    color: var(--primary);
    font-size: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

[data-theme="dark"] .info-item {
    border-bottom-color: rgba(255, 255, 255, 0.05);
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 0.82rem;
    color: var(--gray-600);
    font-weight: 500;
}

[data-theme="dark"] .info-label {
    color: var(--gray-400);
}

.info-value {
    font-size: 0.82rem;
    color: var(--dark);
    font-weight: 600;
    font-family: 'Courier New', monospace;
}

[data-theme="dark"] .info-value {
    color: var(--white);
}

/* ===== Selection ===== */
::selection {
    background: rgba(16, 185, 129, 0.25);
    color: var(--dark);
}

[data-theme="dark"] ::selection {
    background: rgba(52, 211, 153, 0.3);
    color: var(--white);
}

/* ===== Focus Visible ===== */
*:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
    border-radius: 4px;
}

/* ===== Loading Skeleton ===== */
.skeleton {
    background: linear-gradient(90deg, var(--gray-100) 25%, var(--gray-200) 50%, var(--gray-100) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: var(--radius-sm);
}

[data-theme="dark"] .skeleton {
    background: linear-gradient(90deg, rgba(255,255,255,0.05) 25%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0.05) 75%);
    background-size: 200% 100%;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ===== Print Styles ===== */
@media print {
    .sidebar,
    .admin-header,
    .scroll-top-btn,
    .admin-footer,
    .page-transition {
        display: none !important;
    }
    
    .admin-main {
        margin-left: 0 !important;
    }
    
    .page-content,
    .dashboard-content {
        padding: 0 !important;
    }
    
    * {
        box-shadow: none !important;
        text-shadow: none !important;
    }
    
    body {
        background: white !important;
        color: black !important;
    }
}

/* ===== Reduced Motion ===== */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}

/* ===== Responsive ===== */
@media (max-width: 1024px) {
    .admin-footer {
        padding: 16px 24px;
    }
    
    .footer-db-status {
        display: none;
    }
}

@media (max-width: 768px) {
    .admin-footer {
        padding: 14px 20px;
        font-size: 0.78rem;
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }
    
    .footer-left,
    .footer-right {
        justify-content: center;
        gap: 10px;
    }
    
    .footer-divider {
        display: none;
    }
    
    .scroll-top-btn {
        bottom: 24px;
        right: 24px;
        width: 46px;
        height: 46px;
    }
    
    .footer-link span {
        display: none;
    }
    
    .system-info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .footer-version,
    .footer-credit {
        display: none;
    }
    
    .scroll-top-btn {
        bottom: 20px;
        right: 20px;
        width: 42px;
        height: 42px;
    }
}
</style>

<script>
(function() {
    'use strict';

    /* ===== 1. Scroll to Top Button with Progress Ring ===== */
    const scrollBtn = document.getElementById('scrollTopBtn');
    const progressCircle = scrollBtn?.querySelector('.progress-ring-circle');
    const circumference = 94.25; // 2 * PI * 15

    if (progressCircle) {
        progressCircle.style.strokeDasharray = `${circumference} ${circumference}`;
        progressCircle.style.strokeDashoffset = circumference;
    }

    function updateScrollProgress() {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrollPercent = docHeight > 0 ? Math.min(scrollTop / docHeight, 1) : 0;
        
        // Show/hide button
        if (scrollTop > 400) {
            scrollBtn?.classList.add('show');
        } else {
            scrollBtn?.classList.remove('show');
        }
        
        // Update progress ring
        if (progressCircle) {
            const offset = circumference - (scrollPercent * circumference);
            progressCircle.style.strokeDashoffset = offset;
        }
    }

    // Throttled scroll listener for performance
    let scrollTicking = false;
    window.addEventListener('scroll', () => {
        if (!scrollTicking) {
            window.requestAnimationFrame(() => {
                updateScrollProgress();
                scrollTicking = false;
            });
            scrollTicking = true;
        }
    }, { passive: true });

    // Initial update
    updateScrollProgress();

    // Scroll to top click handler
    scrollBtn?.addEventListener('click', () => {
        window.scrollTo({ 
            top: 0, 
            behavior: 'smooth' 
        });
        
        if (window.IFPro?.toast) {
            window.IFPro.toast.show('Kembali ke atas', 'info');
        }
    });

    /* ===== 2. Smooth Page Transitions ===== */
    const transition = document.getElementById('pageTransition');
    
    document.querySelectorAll('a[href]:not([target]):not([data-no-transition])').forEach(link => {
        const href = link.getAttribute('href');
        
        // Skip external links, anchors, and javascript links
        if (!href || 
            href.startsWith('#') || 
            href.startsWith('javascript:') || 
            href.startsWith('http') || 
            href.startsWith('mailto:') || 
            href.startsWith('tel:') ||
            href.startsWith('data:')) {
            return;
        }
        
        // Skip form submissions and buttons
        if (link.tagName === 'BUTTON' || link.closest('form')) return;
        
        // Skip download links
        if (link.hasAttribute('download')) return;
        
        link.addEventListener('click', function(e) {
            const currentPath = window.location.pathname.split('/').pop();
            const targetPath = href.split('/').pop().split('?')[0];
            
            // Skip if same page
            if (currentPath === targetPath) return;
            
            // Skip if modifier keys pressed (open in new tab)
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;
            
            e.preventDefault();
            transition?.classList.add('active');
            
            setTimeout(() => {
                window.location.href = href;
            }, 350);
        });
    });

    // Hide transition on page load
    window.addEventListener('load', () => {
        setTimeout(() => {
            transition?.classList.remove('active');
        }, 150);
    });

    /* ===== 3. Auto-close Dropdowns on Scroll ===== */
    let scrollTimeout;
    window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            if (window.scrollY > 150) {
                document.querySelectorAll('.dropdown.open').forEach(d => {
                    d.classList.remove('open');
                    const trigger = d.querySelector('[aria-expanded]');
                    if (trigger) trigger.setAttribute('aria-expanded', 'false');
                });
            }
        }, 200);
    }, { passive: true });

    /* ===== 4. Form Submission Loading ===== */
    document.querySelectorAll('form:not([data-no-loading])').forEach(form => {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                const originalHTML = btn.innerHTML;
                const originalText = btn.textContent;
                
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                btn.style.opacity = '0.7';
                btn.style.cursor = 'wait';
                
                // Restore after 4 seconds (fallback for slow connections)
                setTimeout(() => {
                    if (btn.disabled) {
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                        btn.style.opacity = '';
                        btn.style.cursor = '';
                        
                        if (window.IFPro?.toast) {
                            window.IFPro.toast.show('Koneksi lambat, silakan coba lagi', 'warning');
                        }
                    }
                }, 4000);
            }
        });
    });

    /* ===== 5. Enhanced Confirmation Dialogs ===== */
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function(e) {
            const msg = this.dataset.confirm || 'Apakah Anda yakin?';
            
            // Use custom modal if available, fallback to native confirm
            if (window.IFPro?.modal?.confirm) {
                e.preventDefault();
                window.IFPro.modal.confirm(msg).then(confirmed => {
                    if (confirmed) {
                        // Proceed with action
                        if (this.tagName === 'A') {
                            window.location.href = this.href;
                        } else if (this.tagName === 'BUTTON' && this.form) {
                            this.form.submit();
                        }
                    }
                });
            } else {
                if (!confirm(msg)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    });

    /* ===== 6. Dynamic Tooltips ===== */
    const tooltipStyle = document.createElement('style');
    tooltipStyle.textContent = `
        .dynamic-tooltip {
            position: absolute;
            z-index: 10001;
            padding: 8px 14px;
            background: var(--dark);
            color: var(--white);
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            transform: translateY(6px);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
            pointer-events: none;
            max-width: 250px;
        }
        
        [data-theme="dark"] .dynamic-tooltip {
            background: var(--dark-2);
        }
        
        .dynamic-tooltip.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .dynamic-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: var(--dark);
        }
        
        [data-theme="dark"] .dynamic-tooltip::after {
            border-top-color: var(--dark-2);
        }
    `;
    document.head.appendChild(tooltipStyle);

    let activeTooltip = null;
    
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', function(e) {
            if (activeTooltip) {
                activeTooltip.remove();
                activeTooltip = null;
            }
            
            const tooltip = document.createElement('div');
            tooltip.className = 'dynamic-tooltip';
            tooltip.textContent = this.dataset.tooltip;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            
            let left = rect.left + rect.width / 2 - tooltipRect.width / 2;
            let top = rect.top - tooltipRect.height - 10 + window.scrollY;
            
            // Prevent overflow left
            if (left < 10) left = 10;
            
            // Prevent overflow right
            if (left + tooltipRect.width > window.innerWidth - 10) {
                left = window.innerWidth - tooltipRect.width - 10;
            }
            
            // If tooltip would go above viewport, show below
            if (top < window.scrollY + 10) {
                top = rect.bottom + 10 + window.scrollY;
                tooltip.classList.add('tooltip-bottom');
            }
            
            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';
            
            requestAnimationFrame(() => {
                tooltip.classList.add('show');
            });
            
            activeTooltip = tooltip;
            this._tooltip = tooltip;
        });
        
        el.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.classList.remove('show');
                setTimeout(() => {
                    if (this._tooltip && this._tooltip.parentNode) {
                        this._tooltip.remove();
                    }
                }, 200);
                this._tooltip = null;
            }
            
            if (activeTooltip === this._tooltip) {
                activeTooltip = null;
            }
        });
        
        // Remove tooltip on click
        el.addEventListener('click', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });

    /* ===== 7. Keyboard Navigation ===== */
    document.addEventListener('keydown', (e) => {
        // Ctrl/Cmd + / = Focus search (handled in header)
        
        // Ctrl/Cmd + Shift + P = Print
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'P') {
            e.preventDefault();
            window.print();
            
            if (window.IFPro?.toast) {
                window.IFPro.toast.show('Membuka dialog print...', 'info');
            }
        }
        
        // Escape = Close modals and dropdowns
        if (e.key === 'Escape') {
            document.querySelectorAll('.dropdown.open').forEach(d => {
                d.classList.remove('open');
                const trigger = d.querySelector('[aria-expanded]');
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
            });
        }
    });

    /* ===== 8. System Info Modal ===== */
    window.openSystemInfo = function() {
        const modal = document.getElementById('systemInfoModal');
        if (modal) {
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeSystemInfo = function() {
        const modal = document.getElementById('systemInfoModal');
        if (modal) {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    };

    window.exportSystemInfo = function() {
        const info = {
            version: '3.0.0 Ultimate',
            php_version: '<?= PHP_VERSION ?>',
            server: '<?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?>',
            memory_usage: '<?= round(memory_get_usage(true) / 1024 / 1024, 2) ?> MB',
            peak_memory: '<?= round(memory_get_peak_usage(true) / 1024 / 1024, 2) ?> MB',
            timestamp: new Date().toISOString()
        };
        
        const blob = new Blob([JSON.stringify(info, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `system-info-${Date.now()}.json`;
        a.click();
        URL.revokeObjectURL(url);
        
        if (window.IFPro?.toast) {
            window.IFPro.toast.show('System info exported', 'success');
        }
    };

    // Footer links handlers
    document.getElementById('aboutLink')?.addEventListener('click', function(e) {
        e.preventDefault();
        openSystemInfo();
    });

    document.getElementById('docsLink')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (window.IFPro?.toast) {
            window.IFPro.toast.show('📚 Dokumentasi akan segera tersedia!', 'info');
        }
    });

    // Close modal on backdrop click
    document.getElementById('systemInfoModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeSystemInfo();
        }
    });

    /* ===== 9. Database Status Monitor ===== */
    async function checkDatabaseStatus() {
        const dbStatusEl = document.getElementById('dbStatus');
        if (!dbStatusEl) return;
        
        try {
            // In production: implement actual health check endpoint
            // const response = await fetch('/api/health/db');
            // const data = await response.json();
            
            // Simulated check
            dbStatusEl.textContent = 'Connected';
            dbStatusEl.style.color = 'var(--primary)';
        } catch (error) {
            dbStatusEl.textContent = 'Error';
            dbStatusEl.style.color = 'var(--danger)';
            console.error('Database check failed:', error);
        }
    }

    // Check database status every 60 seconds
    checkDatabaseStatus();
    setInterval(checkDatabaseStatus, 60000);

    /* ===== 10. Performance Monitoring ===== */
    if (window.performance && performance.getEntriesByType) {
        window.addEventListener('load', () => {
            setTimeout(() => {
                const [navigation] = performance.getEntriesByType('navigation');
                if (navigation) {
                    const loadTime = Math.round(navigation.loadEventEnd - navigation.startTime);
                    const domReady = Math.round(navigation.domContentLoadedEventEnd - navigation.startTime);
                    
                    console.log('%c⚡ Performance Metrics', 'color:#10B981;font-weight:bold;font-size:12px');
                    console.log(`  DOM Ready: ${domReady}ms`);
                    console.log(`  Full Load: ${loadTime}ms`);
                    
                    if (loadTime > 3000) {
                        console.warn('⚠️ Page load time exceeds 3 seconds');
                    }
                }
            }, 100);
        });
    }

    /* ===== 11. Console Branding ===== */
    console.log('%c╔══════════════════════════════════════════════════╗', 'color:#10B981;font-weight:bold');
    console.log('%c║   INFORMATIKA PRO ADMIN PANEL v3.0 ULTIMATE      ║', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c╚══════════════════════════════════════════════════╝', 'color:#10B981;font-weight:bold');
    console.log('%c', 'font-size:8px');
    console.log('%c⚡ Powered by PHP 8.2 + MySQL + Modern JavaScript', 'color:#64748b;font-size:11px');
    console.log('%c💚 Developed with love for Komandan', 'color:#64748b;font-size:11px');
    console.log('%c🔒 Security: CSRF + PDO + Rate Limiting + XSS Protection', 'color:#64748b;font-size:11px');
    console.log('%c🎨 Design: Glassmorphism + Dark Mode + Responsive', 'color:#64748b;font-size:11px');
    console.log('%c', 'font-size:8px');
    console.log('%cKeyboard Shortcuts:', 'color:#10B981;font-weight:bold;font-size:11px');
    console.log('%c  Ctrl+K  → Command Palette', 'color:#64748b;font-size:10px');
    console.log('%c  Ctrl+B  → Toggle Sidebar', 'color:#64748b;font-size:10px');
    console.log('%c  Ctrl+/  → Focus Search', 'color:#64748b;font-size:10px');
    console.log('%c  Esc     → Close Modals/Dropdowns', 'color:#64748b;font-size:10px');

    /* ===== 12. Global Error Handlers ===== */
    window.addEventListener('error', (e) => {
        console.error('❌ Uncaught Error:', e.error);
        
        // In production: send to error monitoring service
        // fetch('/api/errors', {
        //     method: 'POST',
        //     body: JSON.stringify({
        //         message: e.message,
        //         filename: e.filename,
        //         lineno: e.lineno,
        //         colno: e.colno,
        //         stack: e.error?.stack
        //     })
        // });
    });

    window.addEventListener('unhandledrejection', (e) => {
        console.error('❌ Unhandled Promise Rejection:', e.reason);
        
        if (window.IFPro?.toast) {
            window.IFPro.toast.show('Terjadi kesalahan. Silakan refresh halaman.', 'error');
        }
    });

    /* ===== 13. Service Worker Registration (PWA Ready) ===== */
    if ('serviceWorker' in navigator && location.protocol === 'https:') {
        window.addEventListener('load', () => {
            // Uncomment untuk production PWA:
            // navigator.serviceWorker.register('/sw.js')
            //     .then(reg => console.log('✅ Service Worker registered'))
            //     .catch(err => console.log('❌ SW registration failed:', err));
        });
    }

    /* ===== 14. Auto-refresh Notifications (every 60s) ===== */
    let lastNotifCount = null;
    
    async function refreshNotifications() {
        try {
            // In production: implement actual endpoint
            // const response = await fetch('/api/notifications/count');
            // const data = await response.json();
            
            // const badge = document.querySelector('.header-icon-btn .hib-badge');
            // if (badge && data.count !== lastNotifCount) {
            //     badge.textContent = data.count;
            //     lastNotifCount = data.count;
            // }
        } catch (error) {
            console.error('Notification refresh failed:', error);
        }
    }

    // Refresh every 60 seconds
    setInterval(refreshNotifications, 60000);

    /* ===== 15. View Site Button ===== */
    document.getElementById('viewSiteBtn')?.addEventListener('click', function() {
        window.open('../index.php', '_blank');
    });


    /* ===== Secure mutating GET-style admin links =====
       Preserve existing routes while submitting state changes with a CSRF token.
       The matching server-side guard rejects direct, forged GET mutations. */
    const adminCsrfToken = <?= json_encode(generateCSRF(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window.adminSecureAction = function (targetUrl) {
        let target;
        try { target = new URL(targetUrl, window.location.href); } catch (_) { return; }
        if (target.origin !== window.location.origin) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = target.pathname + target.search;
        form.hidden = true;
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'csrf_token';
        tokenInput.value = adminCsrfToken;
        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    };

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const target = event.target instanceof Element ? event.target : event.target.parentElement;
        const link = target && target.closest ? target.closest('a[href]') : null;
        if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

        let destination;
        try { destination = new URL(link.href, window.location.href); } catch (_) { return; }
        if (destination.origin !== window.location.origin) return;

        // Logout also changes authentication state and must use the same POST token.
        if (destination.pathname.toLowerCase().endsWith('/logout.php')) {
            event.preventDefault();
            window.adminSecureAction(destination.href);
            return;
        }

        const action = (destination.searchParams.get('action') || '').toLowerCase();
        const mutatingAction = /(?:^|[-_])(delete|remove|destroy|toggle|status|duplicate|publish|draft|activate|deactivate|approve|reject|spam|pending|verified|accepted|rejected|revoke|unsubscribe|dibaca|dibalas|clear|reset|disable|enable|feature|hide|show|purge|flush|cleanup|mark|archive|restore|regenerate|generate|send|resend)(?:$|[-_])/.test(action);
        if (!mutatingAction) return;

        event.preventDefault();
        window.adminSecureAction(destination.href);
    });

    console.log('%c✅ Footer Ultimate Loaded Successfully', 'color:#10B981;font-weight:bold;font-size:12px');
})();
</script>

</body>
</html>