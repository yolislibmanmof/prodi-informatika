/**
 * Lazy Loading dengan Intersection Observer
 * Load images hanya saat muncul di viewport
 */

(function() {
    'use strict';
    
    // Check browser support
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    
                    // Load actual image
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                    }
                    
                    // Load srcset jika ada
                    if (img.dataset.srcset) {
                        img.srcset = img.dataset.srcset;
                    }
                    
                    // Remove lazy class
                    img.classList.remove('lazy');
                    
                    // Stop observing
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px', // Start loading 50px before visible
            threshold: 0.01
        });
        
        // Observe all lazy images
        document.querySelectorAll('img.lazy').forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback untuk browser lama
        document.querySelectorAll('img.lazy').forEach(img => {
            if (img.dataset.src) {
                img.src = img.dataset.src;
            }
        });
    }
})();