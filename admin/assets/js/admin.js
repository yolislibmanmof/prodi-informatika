/* ============================================================
   ADMIN PANEL JS - MULTIMATE EDITION v2.0
   Informatika Pro © 2026
   Advanced Admin Dashboard with Premium Features
============================================================ */

(function() {
    'use strict';

    /* ===== GLOBAL VARIABLES ===== */
    const IFPro = window.IFPro = window.IFPro || {};
    IFPro.version = '2.0.0';
    IFPro.features = {};

    /* ===== UTILITY FUNCTIONS ===== */
    const $ = (selector, context = document) => context.querySelector(selector);
    const $$ = (selector, context = document) => Array.from(context.querySelectorAll(selector));
    
    const debounce = (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    const throttle = (func, limit) => {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    };

    /* ===== 1. THEME MANAGER ===== */
    IFPro.theme = {
        init() {
            const saved = localStorage.getItem('admin_theme') || 'light';
            this.set(saved);
            
            const toggleBtn = $('.theme-toggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    const current = document.documentElement.getAttribute('data-theme');
                    const next = current === 'dark' ? 'light' : 'dark';
                    this.set(next);
                });
            }
        },

        set(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('admin_theme', theme);
            
            const icon = $('.theme-toggle i');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
    };

    /* ===== 2. SIDEBAR MANAGER ===== */
    IFPro.sidebar = {
        init() {
            const sidebar = $('#sidebar');
            const toggle = $('#sidebarToggle');
            const collapseBtn = $('#collapseSidebar');

            // Restore state
            const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
            if (isCollapsed && window.innerWidth > 1024) {
                sidebar?.classList.add('collapsed');
            }

            // Toggle handlers
            if (toggle) {
                toggle.addEventListener('click', () => {
                    if (window.innerWidth <= 1024) {
                        sidebar.classList.toggle('mobile-open');
                    } else {
                        sidebar.classList.toggle('collapsed');
                        localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
                    }
                });
            }

            if (collapseBtn) {
                collapseBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                    localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
                });
            }

            // Close mobile sidebar on outside click
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 1024 && sidebar?.classList.contains('mobile-open')) {
                    if (!sidebar.contains(e.target) && !toggle?.contains(e.target)) {
                        sidebar.classList.remove('mobile-open');
                    }
                }
            });
        }
    };

    /* ===== 3. DROPDOWN MANAGER ===== */
    IFPro.dropdown = {
        init() {
            $$('.dropdown').forEach(dropdown => {
                const trigger = dropdown.querySelector('button, .admin-profile');
                if (!trigger) return;

                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    
                    // Close other dropdowns
                    $$('.dropdown.open').forEach(d => {
                        if (d !== dropdown) d.classList.remove('open');
                    });
                    
                    dropdown.classList.toggle('open');
                });
            });

            // Close on outside click
            document.addEventListener('click', () => {
                $$('.dropdown.open').forEach(d => d.classList.remove('open'));
            });

            // Close on ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    $$('.dropdown.open').forEach(d => d.classList.remove('open'));
                }
            });
        }
    };

    /* ===== 4. COMMAND PALETTE (Ctrl+K) ===== */
    IFPro.commandPalette = {
        init() {
            const searchInput = $('#globalSearch');
            
            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                // Ctrl+K or Cmd+K
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    searchInput?.focus();
                }
                
                // / key when not in input
                if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
                    e.preventDefault();
                    searchInput?.focus();
                }
            });

            if (searchInput) {
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        searchInput.value = '';
                        searchInput.blur();
                    }
                });

                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        const query = searchInput.value.trim();
                        if (query) {
                            this.search(query);
                        }
                    }
                });
            }
        },

        search(query) {
            // In production: fetch search results via AJAX
            console.log('Searching:', query);
            IFPro.toast.show(`Mencari: ${query}`, 'info');
        }
    };

    /* ===== 5. TOAST NOTIFICATION SYSTEM ===== */
    IFPro.toast = {
        container: null,

        init() {
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.id = 'toastContainer';
                this.container.className = 'toast-container';
                document.body.appendChild(this.container);
            }
        },

        show(message, type = 'success', duration = 4000) {
            this.init();
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icons = {
                success: 'check-circle',
                error: 'exclamation-circle',
                warning: 'exclamation-triangle',
                info: 'info-circle'
            };

            toast.innerHTML = `
                <i class="fas fa-${icons[type] || 'info-circle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            this.container.appendChild(toast);
            
            // Auto remove
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
    };

    // Global toast function for backward compatibility
    window.showToast = (msg, type) => IFPro.toast.show(msg, type);

    /* ===== 6. CONFIRM DIALOG ===== */
    IFPro.confirm = {
        init() {
            $$('[data-confirm]').forEach(el => {
                el.addEventListener('click', (e) => {
                    const message = el.dataset.confirm || 'Apakah Anda yakin?';
                    if (!confirm(message)) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
        }
    };

    /* ===== 7. FORM UTILITIES ===== */
    IFPro.form = {
        init() {
            // Auto-save indicator
            $$('form[data-autosave]').forEach(form => {
                let saveTimer;
                form.addEventListener('input', debounce(() => {
                    const indicator = form.querySelector('.autosave-indicator') || this.createIndicator(form);
                    indicator.textContent = '✓ Tersimpan otomatis';
                    indicator.classList.add('saved');
                    
                    setTimeout(() => {
                        indicator.classList.remove('saved');
                    }, 2000);
                }, 1000));
            });

            // Password toggle
            $$('.toggle-password').forEach(btn => {
                btn.addEventListener('click', () => {
                    const input = btn.previousElementSibling;
                    if (input) {
                        const type = input.type === 'password' ? 'text' : 'password';
                        input.type = type;
                        btn.querySelector('i').className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
                    }
                });
            });
        },

        createIndicator(form) {
            const indicator = document.createElement('div');
            indicator.className = 'autosave-indicator';
            form.appendChild(indicator);
            return indicator;
        },

        validate(form) {
            const inputs = $$('input[required], textarea[required], select[required]', form);
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('error');
                    isValid = false;
                } else {
                    input.classList.remove('error');
                }
            });

            return isValid;
        }
    };

    /* ===== 8. IMAGE UTILITIES ===== */
    IFPro.image = {
        init() {
            // Lazy loading
            const lazyImages = $$('img[data-src]');
            if (lazyImages.length > 0) {
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    });
                });
                lazyImages.forEach(img => imageObserver.observe(img));
            }

            // Image preview on upload
            $$('input[type="file"][accept*="image"]').forEach(input => {
                input.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        const preview = input.closest('.form-group')?.querySelector('.image-preview');
                        
                        if (preview) {
                            reader.onload = (e) => {
                                preview.src = e.target.result;
                                preview.style.display = 'block';
                            };
                            reader.readAsDataURL(file);
                        }
                    }
                });
            });
        }
    };

    /* ===== 9. TIME UTILITIES ===== */
    IFPro.time = {
        init() {
            $$('[data-time]').forEach(el => {
                el.textContent = this.relative(el.dataset.time);
            });

            // Update every minute
            setInterval(() => {
                $$('[data-time]').forEach(el => {
                    el.textContent = this.relative(el.dataset.time);
                });
            }, 60000);
        },

        relative(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'baru saja';
            if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;
            if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
            if (diff < 604800) return `${Math.floor(diff / 86400)} hari lalu`;
            
            return date.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        }
    };

    /* ===== 10. KEYBOARD SHORTCUTS ===== */
    IFPro.shortcuts = {
        init() {
            document.addEventListener('keydown', (e) => {
                // Shift+? for help
                if (e.shiftKey && e.key === '?') {
                    e.preventDefault();
                    this.showHelp();
                }

                // Ctrl+N for new item (context-specific)
                if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                    const newBtn = $('[onclick*="openModal"], .btn-primary:contains("Tambah")');
                    if (newBtn) {
                        e.preventDefault();
                        newBtn.click();
                    }
                }
            });
        },

        showHelp() {
            const shortcuts = [
                'Ctrl+K atau / — Cari',
                'Ctrl+N — Item baru',
                'Esc — Tutup panel',
                'Shift+? — Bantuan ini'
            ];

            alert('⌨️ Keyboard Shortcuts:\n\n' + shortcuts.join('\n'));
        }
    };

    /* ===== 11. RIPPLE EFFECT ===== */
    IFPro.ripple = {
        init() {
            $$('.btn, .sidebar-item, .quick-btn, [data-ripple]').forEach(el => {
                el.addEventListener('click', function(e) {
                    if (this.dataset.noRipple) return;
                    
                    const rect = this.getBoundingClientRect();
                    const ripple = document.createElement('span');
                    ripple.className = 'ripple-effect';
                    ripple.style.left = (e.clientX - rect.left) + 'px';
                    ripple.style.top = (e.clientY - rect.top) + 'px';
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => ripple.remove(), 600);
                });
            });
        }
    };

    /* ===== 12. TABLE UTILITIES ===== */
    IFPro.table = {
        init() {
            // Sortable tables
            $$('table[data-sortable]').forEach(table => {
                const headers = $$('th[data-sort]', table);
                headers.forEach(header => {
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', () => {
                        this.sort(table, header);
                    });
                });
            });
        },

        sort(table, header) {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const columnIndex = Array.from(header.parentNode.children).indexOf(header);
            const sortType = header.dataset.sort;
            const isAsc = header.classList.contains('sort-asc');

            rows.sort((a, b) => {
                const aVal = a.children[columnIndex].textContent.trim();
                const bVal = b.children[columnIndex].textContent.trim();

                if (sortType === 'number') {
                    return isAsc ? bVal - aVal : aVal - bVal;
                } else {
                    return isAsc ? bVal.localeCompare(aVal) : aVal.localeCompare(bVal);
                }
            });

            // Update header classes
            $$('th', table).forEach(th => {
                th.classList.remove('sort-asc', 'sort-desc');
            });
            header.classList.add(isAsc ? 'sort-desc' : 'sort-asc');

            // Re-append rows
            rows.forEach(row => tbody.appendChild(row));
        }
    };

    /* ===== 13. NOTIFICATION POLLING ===== */
    IFPro.notifications = {
        init() {
            // Poll for new notifications every 30 seconds
            setInterval(() => {
                this.check();
            }, 30000);
        },

        async check() {
            try {
                // In production: fetch from API
                // const response = await fetch('/api/notifications/count');
                // const data = await response.json();
                // Update badge count
            } catch (error) {
                console.error('Notification check failed:', error);
            }
        }
    };

    /* ===== 14. EXPORT UTILITIES ===== */
    IFPro.export = {
        toCSV(table, filename = 'export.csv') {
            const rows = $$('tr', table);
            const csv = rows.map(row => {
                return Array.from(row.children).map(cell => {
                    return `"${cell.textContent.trim().replace(/"/g, '""')}"`;
                }).join(',');
            }).join('\n');

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
        },

        print() {
            window.print();
        }
    };

    /* ===== 15. MODAL MANAGER ===== */
    IFPro.modal = {
        open(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        },

        close(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        },

        init() {
            // Close modal on backdrop click
            $$('.modal').forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.close(modal.id);
                    }
                });
            });

            // Close modal on ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    $$('.modal.active').forEach(modal => {
                        this.close(modal.id);
                    });
                }
            });
        }
    };

    /* ===== 16. CHART UTILITIES (Chart.js Ready) ===== */
    IFPro.charts = {
        instances: {},

        create(canvasId, type, data, options = {}) {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !window.Chart) return null;

            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            };

            this.instances[canvasId] = new Chart(canvas, {
                type,
                data,
                options: { ...defaultOptions, ...options }
            });

            return this.instances[canvasId];
        },

        destroy(canvasId) {
            if (this.instances[canvasId]) {
                this.instances[canvasId].destroy();
                delete this.instances[canvasId];
            }
        }
    };

    /* ===== 17. LOCAL STORAGE MANAGER ===== */
    IFPro.storage = {
        set(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch (e) {
                console.error('Storage set failed:', e);
            }
        },

        get(key, defaultValue = null) {
            try {
                const value = localStorage.getItem(key);
                return value ? JSON.parse(value) : defaultValue;
            } catch (e) {
                console.error('Storage get failed:', e);
                return defaultValue;
            }
        },

        remove(key) {
            localStorage.removeItem(key);
        },

        clear() {
            localStorage.clear();
        }
    };

    /* ===== 18. PAGE TRANSITIONS ===== */
    IFPro.transitions = {
        init() {
            // Smooth page transitions
            $$('a[href]:not([target]):not([data-no-transition])').forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    
                    // Skip external, anchors, javascript
                    if (!href || href.startsWith('#') || href.startsWith('javascript:') || 
                        href.startsWith('http') || href.startsWith('mailto:') || href.startsWith('tel:')) {
                        return;
                    }

                    // Skip form submissions
                    if (this.tagName === 'BUTTON' || this.closest('form')) return;

                    const currentPath = window.location.pathname.split('/').pop();
                    const targetPath = href.split('/').pop().split('?')[0];

                    if (currentPath === targetPath) return;

                    e.preventDefault();
                    document.body.classList.add('page-leaving');

                    setTimeout(() => {
                        window.location.href = href;
                    }, 300);
                });
            });
        }
    };

    /* ===== 19. PERFORMANCE MONITOR ===== */
    IFPro.performance = {
        init() {
            if (window.performance && performance.getEntriesByType) {
                const [navigation] = performance.getEntriesByType('navigation');
                if (navigation) {
                    console.log('%c⚡ Performance', 'color:#10B981;font-weight:bold');
                    console.log(`  DOM Ready: ${Math.round(navigation.domContentLoadedEventEnd)}ms`);
                    console.log(`  Full Load: ${Math.round(navigation.loadEventEnd)}ms`);
                }
            }
        }
    };

    /* ===== 20. INITIALIZATION ===== */
    function init() {
        // Initialize all modules
        IFPro.theme.init();
        IFPro.sidebar.init();
        IFPro.dropdown.init();
        IFPro.commandPalette.init();
        IFPro.confirm.init();
        IFPro.form.init();
        IFPro.image.init();
        IFPro.time.init();
        IFPro.shortcuts.init();
        IFPro.ripple.init();
        IFPro.table.init();
        IFPro.notifications.init();
        IFPro.modal.init();
        IFPro.transitions.init();
        IFPro.performance.init();

        // Console branding
        console.log('%c✨ Informatika Pro Admin Panel', 'color:#10B981;font-size:14px;font-weight:bold');
        console.log('%c   Multimate Edition v' + IFPro.version, 'color:#64748b;font-size:11px');
        console.log('%c   Developed with 💚 for Komandan', 'color:#64748b;font-size:11px');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

/* ===== GLOBAL CSS INJECTION ===== */
const globalStyle = document.createElement('style');
globalStyle.textContent = `
.toast-container {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 10000;
    display: flex;
    flex-direction: column;
    gap: 12px;
    pointer-events: none;
}

.toast {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 12px;
    color: #fff;
    box-shadow: 0 10px 30px rgba(0,0,0,.2);
    min-width: 320px;
    max-width: 420px;
    animation: toastIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: auto;
    backdrop-filter: blur(10px);
}

.toast-success { background: linear-gradient(135deg, #10B981, #047857); }
.toast-error { background: linear-gradient(135deg, #EF4444, #DC2626); }
.toast-warning { background: linear-gradient(135deg, #F59E0B, #D97706); }
.toast-info { background: linear-gradient(135deg, #3B82F6, #1E40AF); }

.toast i {
    font-size: 1.3rem;
    flex-shrink: 0;
}

.toast span {
    flex: 1;
    font-size: 0.9rem;
    font-weight: 500;
}

.toast button {
    background: none;
    border: none;
    color: #fff;
    cursor: pointer;
    padding: 4px;
    margin-left: auto;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.toast button:hover {
    opacity: 1;
}

@keyframes toastIn {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.ripple-effect {
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.4);
    transform: scale(0);
    animation: rippleEffect 0.6s linear;
    pointer-events: none;
}

@keyframes rippleEffect {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

.page-leaving {
    opacity: 0.5;
    transition: opacity 0.3s;
}

.autosave-indicator {
    font-size: 0.75rem;
    color: #10B981;
    margin-top: 8px;
    padding: 6px 12px;
    background: rgba(16,185,129,0.1);
    border-radius: 6px;
    display: inline-block;
    transition: all 0.3s;
}

.autosave-indicator.saved {
    animation: pulse 0.6s ease;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

input.error,
textarea.error,
select.error {
    border-color: #EF4444 !important;
    animation: shake 0.4s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-8px); }
    75% { transform: translateX(8px); }
}

.image-preview {
    max-width: 200px;
    max-height: 200px;
    margin-top: 12px;
    border-radius: 8px;
    display: none;
}

th[data-sort] {
    cursor: pointer;
    user-select: none;
    position: relative;
    padding-right: 30px !important;
}

th[data-sort]::after {
    content: '↕';
    position: absolute;
    right: 12px;
    opacity: 0.3;
    font-size: 0.9rem;
}

th.sort-asc::after {
    content: '↑';
    opacity: 1;
    color: #10B981;
}

th.sort-desc::after {
    content: '↓';
    opacity: 1;
    color: #10B981;
}

@media print {
    .toast-container,
    .modal,
    .sidebar,
    .admin-header {
        display: none !important;
    }
}
`;
document.head.appendChild(globalStyle);