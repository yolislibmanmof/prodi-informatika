<?php
/**
 * cache-cleanup.php - Auto cleanup expired cache
 * Include file ini di footer.php atau jalankan via cron
 */

require_once __DIR__ . '/CacheManager.php';

// Cleanup expired cache setiap 100 request (probabilistic)
if (mt_rand(1, 100) === 1) {
    $removed = CacheManager::cleanup();
    
    if ($removed > 0 && defined('APP_DEBUG') && APP_DEBUG) {
        error_log("Cache cleanup: removed $removed expired files");
    }
}