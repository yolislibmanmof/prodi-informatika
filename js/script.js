/* ============================================================
   INFORMATIKA PRO — GLOBAL SMART ENGINE v2.1 (ANTI-FLASH)
   ------------------------------------------------------------
   • Auto-skip fitur yang sudah dimiliki script inline halaman
     (handshake: .hp-loader/.hp-cursor/.bd-progress-bar/.gl-loader
      /#quantumLoader = mesin inline aktif)
   • Semua fitur dibungkus try/catch → 1 gagal tidak menular
   • Hormati prefers-reduced-motion & tab tersembunyi
   • v2.1: Disabled img-fade & load-reveal untuk prevent flash
============================================================ */
(function () {
    'use strict';

    window.IFPro = window.IFPro || { features: {} };
    const FEATURES = window.IFPro.features;

    /* ===== Helper Dasar ===== */
    const $  = (s, c) => (c || document).querySelector(s);
    const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));
    const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Handshake deteksi mesin inline halaman (UPDATED untuk detect loader baru)
    const INLINE_ENGINE = !!$('.hp-loader, .hp-cursor, .bd-progress-bar, .gl-loader, #quantumLoader');
    const navToggleEl   = document.getElementById('navToggle');
    const GLOBAL_NAV    = !!(navToggleEl && navToggleEl.hasAttribute('aria-expanded'));

    // Klaim fitur agar tidak pernah dobel
    const claim = (name) => {
        if (FEATURES[name]) return false;
        FEATURES[name] = true;
        return true;
    };

    // Jalankan aman
    const safe = (name, fn) => {
        try { fn(); }
        catch (err) { console.warn('[IFPro] Fitur "' + name + '" dilewati:', err); }
    };

    // Throttle scroll dengan rAF
    const onScroll = (fn) => {
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(() => { fn(); ticking = false; });
            }
        }, { passive: true });
    };

    /* ============================================================
       1. NAVBAR SCROLL EFFECT (skip jika navbar ultimate aktif)
    ============================================================ */
    safe('navbar-scroll', () => {
        if (GLOBAL_NAV || !claim('navbar-scroll')) return;
        const navbar = document.getElementById('navbar');
        if (!navbar) return;
        onScroll(() => navbar.classList.toggle('scrolled', window.scrollY > 50));
    });

    /* ============================================================
       2. MOBILE NAV TOGGLE (skip jika navbar ultimate aktif)
    ============================================================ */
    safe('nav-toggle', () => {
        if (GLOBAL_NAV || !claim('nav-toggle')) return;
        const toggle = document.getElementById('navToggle');
        const menu   = document.getElementById('navMenu');
        if (!toggle || !menu) return;

        toggle.addEventListener('click', () => {
            toggle.classList.toggle('active');
            menu.classList.toggle('active');
        });
        $$('.nav-link', menu).forEach(link => {
            link.addEventListener('click', () => {
                toggle.classList.remove('active');
                menu.classList.remove('active');
            });
        });
    });

    /* ============================================================
       3. ACTIVE LINK ON SCROLL (scrollspy presisi)
    ============================================================ */
    safe('scrollspy', () => {
        if (GLOBAL_NAV || !claim('scrollspy')) return;
        const sections = $$('section[id]');
        if (!sections.length) return;

        onScroll(() => {
            const y = window.scrollY + 120;
            sections.forEach(sec => {
                const top = sec.offsetTop;
                const id  = sec.getAttribute('id');
                const link = document.querySelector('.nav-link[href="#' + id + '"], .nav-link[href$="#' + id + '"]');
                if (!link) return;
                if (y >= top && y < top + sec.offsetHeight) link.classList.add('active');
                else link.classList.remove('active');
            });
        });
    });

    /* ============================================================
       4. TYPEWRITER (skip jika mesin inline halaman aktif)
    ============================================================ */
    safe('typewriter', () => {
        if (INLINE_ENGINE || !claim('typewriter')) return;
        const el = document.getElementById('typewriter');
        if (!el) return;

        const words = ['Digital Anda', 'Teknologi', 'Masa Depan', 'Karier Impian'];
        if (REDUCED) { el.textContent = words[0]; return; }

        let w = 0, c = 0, del = false;
        (function type() {
            if (document.hidden) { setTimeout(type, 500); return; }
            const word = words[w];
            el.textContent = word.substring(0, c + (del ? -1 : 1));
            c += del ? -1 : 1;

            let speed = del ? 45 : 95;
            if (!del && c === word.length)      { speed = 2000; del = true; }
            else if (del && c === 0)            { del = false; w = (w + 1) % words.length; speed = 450; }
            setTimeout(type, speed);
        })();
    });

    /* ============================================================
       5. SCROLL REVEAL (.fade-up) — IntersectionObserver
    ============================================================ */
    safe('reveal', () => {
        if (!claim('reveal')) return;
        const targets = $$('.fade-up');
        if (!targets.length) return;

        if (REDUCED || !('IntersectionObserver' in window)) {
            targets.forEach(t => t.classList.add('visible'));
            return;
        }

        const obs = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    setTimeout(() => entry.target.classList.add('visible'), i * 60);
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        targets.forEach(t => obs.observe(t));
    });

    /* ============================================================
       6. COUNTER ANIMATION (easing + format id-ID)
    ============================================================ */
    safe('counter', () => {
        if (INLINE_ENGINE || !claim('counter')) return;
        const counters = $$('.stat-number[data-target]');
        if (!counters.length) return;

        if (REDUCED || !('IntersectionObserver' in window)) {
            counters.forEach(el => el.textContent = (+el.dataset.target).toLocaleString('id-ID'));
            return;
        }

        const easeOutQuart = t => 1 - Math.pow(1 - t, 4);
        const obs = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                const target = +el.dataset.target;
                const start = performance.now();
                const dur = 2000;

                (function tick(now) {
                    const p = Math.min((now - start) / dur, 1);
                    el.textContent = Math.floor(target * easeOutQuart(p)).toLocaleString('id-ID');
                    if (p < 1) requestAnimationFrame(tick);
                    else el.textContent = target.toLocaleString('id-ID');
                })(start);

                obs.unobserve(el);
            });
        }, { threshold: 0.5 });

        counters.forEach(c => obs.observe(c));
    });

    /* ============================================================
       7. TESTIMONIAL SLIDER — swipe, autoplay cerdas, pause hover
    ============================================================ */
    safe('slider', () => {
        if (!claim('slider')) return;
        const track = document.getElementById('testimonialTrack');
        const dotsWrap = document.getElementById('sliderDots');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        if (!track || !dotsWrap || !track.children.length) return;

        const total = track.children.length;
        let current = 0, timer = null, paused = false;

        // Build dots sekali
        if (!dotsWrap.children.length) {
            for (let i = 0; i < total; i++) {
                const dot = document.createElement('div');
                dot.className = 'dot' + (i === 0 ? ' active' : '');
                dot.setAttribute('role', 'button');
                dot.setAttribute('aria-label', 'Testimoni ' + (i + 1));
                dot.addEventListener('click', () => go(i));
                dotsWrap.appendChild(dot);
            }
        }

        function update() {
            track.style.transform = 'translateX(-' + (current * 100) + '%)';
            $$('.dot', dotsWrap).forEach((d, i) => d.classList.toggle('active', i === current));
        }
        function go(i) {
            current = (i + total) % total;
            update();
            restart();
        }
        function restart() {
            clearInterval(timer);
            timer = setInterval(() => {
                if (!paused && !document.hidden && !REDUCED) {
                    current = (current + 1) % total;
                    update();
                }
            }, 5000);
        }

        nextBtn && nextBtn.addEventListener('click', () => go(current + 1));
        prevBtn && prevBtn.addEventListener('click', () => go(current - 1));

        // Pause saat hover / fokus di dalam slider
        const sliderBox = track.closest('.testimonial-slider') || track.parentElement;
        sliderBox.addEventListener('mouseenter', () => paused = true);
        sliderBox.addEventListener('mouseleave', () => paused = false);

        // Swipe support (mobile)
        let startX = 0, deltaX = 0;
        track.addEventListener('touchstart', e => { startX = e.touches[0].clientX; paused = true; }, { passive: true });
        track.addEventListener('touchmove',  e => { deltaX = e.touches[0].clientX - startX; }, { passive: true });
        track.addEventListener('touchend', () => {
            if (Math.abs(deltaX) > 50) go(current + (deltaX < 0 ? 1 : -1));
            deltaX = 0; paused = false;
        });

        update();
        restart();
    });

    /* ============================================================
       8. SMOOTH ANCHOR
    ============================================================ */
    safe('anchor', () => {
        if (INLINE_ENGINE || !claim('anchor')) return;
        $$('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                const id = a.getAttribute('href');
                if (id.length < 2) return;
                const target = document.querySelector(id);
                if (!target) return;
                e.preventDefault();
                window.scrollTo({
                    top: target.getBoundingClientRect().top + window.scrollY - 80,
                    behavior: REDUCED ? 'auto' : 'smooth'
                });
            });
        });
    });

    /* ============================================================
       9. UTILITAS GLOBAL
    ============================================================ */

    // --- Toast publik: IFPro.toast('pesan', 'success'|'error') ---
    window.IFPro.toast = function (msg, type) {
        type = type || 'success';
        let wrap = $('#ifproToastWrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'ifproToastWrap';
            document.body.appendChild(wrap);
        }
        const t = document.createElement('div');
        t.className = 'ifpro-toast ifpro-toast-' + type;
        t.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i><span></span>';
        t.querySelector('span').textContent = msg;
        wrap.appendChild(t);
        requestAnimationFrame(() => t.classList.add('show'));
        setTimeout(() => {
            t.classList.remove('show');
            setTimeout(() => t.remove(), 400);
        }, 3500);
    };

    // --- Ripple effect pada tombol ---
    safe('ripple', () => {
        if (!claim('ripple')) return;
        document.addEventListener('pointerdown', e => {
            const btn = e.target.closest('.btn, .btn-contact, .slider-btn, .btn-white, .btn-outline');
            if (!btn || REDUCED) return;
            const r = document.createElement('span');
            const rect = btn.getBoundingClientRect();
            r.className = 'ifpro-ripple';
            r.style.left = (e.clientX - rect.left) + 'px';
            r.style.top  = (e.clientY - rect.top) + 'px';
            btn.style.position = btn.style.position || 'relative';
            btn.style.overflow = 'hidden';
            btn.appendChild(r);
            setTimeout(() => r.remove(), 650);
        });
    });

    // --- Loading state tombol submit form publik ---
    safe('form-loading', () => {
        if (!claim('form-loading')) return;
        $$('form[method="POST"]:not([data-no-loading])').forEach(form => {
            form.addEventListener('submit', () => {
                const btn = form.querySelector('button[type="submit"]');
                if (!btn || btn.dataset.loading) return;
                btn.dataset.loading = '1';
                btn.dataset.original = btn.innerHTML;
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
                }, 10);
                // Proteksi jika submit batal/gagal, kembalikan setelah 4 dtk
                setTimeout(() => {
                    if (btn.dataset.loading) {
                        btn.innerHTML = btn.dataset.original;
                        delete btn.dataset.loading;
                    }
                }, 4000);
            });
        });
    });

    // --- External link: buka tab baru dengan aman ---
    safe('external', () => {
        if (!claim('external')) return;
        $$('a[href^="http"]').forEach(a => {
            if (a.target === '_blank') return;
            if (a.hostname && a.hostname !== location.hostname) {
                a.target = '_blank';
                a.rel = 'noopener noreferrer';
            }
        });
    });

    /* ============================================================
       DISABLED FEATURES (ANTI-FLASH PATCHES)
    ============================================================ */

    // --- DISABLED: Lazy image fade-in ---
    // Alasan: Menyebabkan "flash" saat navigasi karena set SEMUA gambar ke opacity:0
    // Lazy loading sudah di-handle oleh lazy-load.js yang terpisah dan lebih robust
    // safe('img-fade', () => { ... });

    // --- DISABLED: Load reveal ---
    // Alasan: Class 'loaded' di body bisa conflict dengan loader system (Quantum/Matrix)
    // Reveal animations sudah di-handle oleh scroll reveal (Section 5) via IntersectionObserver
    // safe('load-reveal', () => { ... });

    /* ============================================================
       10. INJEKSI CSS UTILITAS (sekali saja)
    ============================================================ */
    safe('inject-css', () => {
        if (!claim('inject-css')) return;
        const style = document.createElement('style');
        style.textContent = [
            '#ifproToastWrap{position:fixed;top:90px;right:20px;z-index:10002;display:flex;flex-direction:column;gap:10px;pointer-events:none}',
            '.ifpro-toast{display:flex;align-items:center;gap:10px;padding:13px 18px;border-radius:12px;color:#fff;font-size:.9rem;box-shadow:0 10px 30px rgba(0,0,0,.25);opacity:0;transform:translateX(40px);transition:all .4s cubic-bezier(.4,0,.2,1);max-width:340px}',
            '.ifpro-toast.show{opacity:1;transform:none}',
            '.ifpro-toast-success{background:linear-gradient(135deg,#10B981,#047857)}',
            '.ifpro-toast-error{background:linear-gradient(135deg,#EF4444,#B91C1C)}',
            '.ifpro-ripple{position:absolute;width:0;height:0;border-radius:50%;background:rgba(255,255,255,.35);transform:translate(-50%,-50%) scale(0);animation:ifproRip .6s ease-out forwards;pointer-events:none}',
            '@keyframes ifproRip{to{transform:translate(-50%,-50%) scale(1);width:420px;height:420px;opacity:0}}'
        ].join('');
        document.head.appendChild(style);
    });

    /* ============================================================
       11. CONSOLE BRANDING
    ============================================================ */
    console.log('%c⚡ IFPro Smart Engine v2.1 aktif (Anti-Flash)', 'color:#10B981;font-weight:bold');
    console.log('%cFitur aktif: ' + Object.keys(FEATURES).join(', '), 'color:#64748b;font-size:10px');
    console.log('%c✓ img-fade & load-reveal disabled untuk prevent flash', 'color:#F59E0B;font-size:10px');

})();