<?php
/**
 * CacheManager.php - Advanced Cache Management
 * Features: Tag-based caching, auto-invalidation, stats
 */

class CacheManager {
    
    private static $cacheDir;
    private static $enabled = true;
    
    /**
     * Initialize cache manager
     */
    public static function init() {
        self::$cacheDir = __DIR__ . '/../cache';
        
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
        
        // Check apakah caching enabled dari config
        if (defined('CACHE_ENABLED')) {
            self::$enabled = CACHE_ENABLED;
        }
    }
    
    /**
     * Get cache dengan tag support
     * @param string $key Cache key
     * @param int $ttl Time to live (seconds)
     * @param callable $callback Function untuk generate data
     * @param array $tags Tags untuk invalidation group
     * @return mixed
     */
    public static function remember($key, $ttl, $callback, $tags = []) {
        if (!self::$enabled) {
            return $callback();
        }
        
        $cacheFile = self::getCachePath($key);
        
        // Try to get from cache
        if (file_exists($cacheFile)) {
            $data = @file_get_contents($cacheFile);
            if ($data !== false) {
                $cached = @unserialize($data, ['allowed_classes' => false]);
                
                if ($cached !== false && 
                    isset($cached['expires']) && 
                    $cached['expires'] > time()) {
                    return $cached['value'];
                }
            }
        }
        
        // Cache miss, generate new data
        $value = $callback();
        
        // Save to cache with metadata
        $cacheData = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time(),
            'tags' => $tags,
            'key' => $key
        ];
        
        @file_put_contents($cacheFile, serialize($cacheData), LOCK_EX);
        
        // Update tag registry
        if (!empty($tags)) {
            self::registerTags($key, $tags);
        }
        
        return $value;
    }
    
    /**
     * Clear cache by key
     */
    public static function forget($key) {
        $cacheFile = self::getCachePath($key);
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }
    
    /**
     * Clear all caches with specific tag
     * @param string $tag Tag name
     */
    public static function forgetByTag($tag) {
        $tagFile = self::$cacheDir . '/_tag_' . md5($tag) . '.json';
        
        if (!file_exists($tagFile)) {
            return;
        }
        
        $keys = json_decode(@file_get_contents($tagFile), true) ?: [];
        
        foreach ($keys as $key) {
            self::forget($key);
        }
        
        @unlink($tagFile);
    }
    
    /**
     * Clear multiple tags
     * @param array $tags Array of tag names
     */
    public static function forgetByTags($tags) {
        foreach ($tags as $tag) {
            self::forgetByTag($tag);
        }
    }
    
    /**
     * Clear ALL cache
     */
    public static function flush() {
        $files = glob(self::$cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
    
    /**
     * Get cache statistics
     */
    public static function getStats() {
        $files = glob(self::$cacheDir . '/*.cache');
        $totalSize = 0;
        $totalFiles = 0;
        $expired = 0;
        
        foreach ($files as $file) {
            $totalFiles++;
            $totalSize += filesize($file);
            
            $data = @unserialize(@file_get_contents($file), ['allowed_classes' => false]);
            if ($data && isset($data['expires']) && $data['expires'] < time()) {
                $expired++;
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size' => self::formatBytes($totalSize),
            'expired' => $expired,
            'active' => $totalFiles - $expired
        ];
    }
    
    /**
     * Cleanup expired caches
     */
    public static function cleanup() {
        $files = glob(self::$cacheDir . '/*.cache');
        $removed = 0;
        
        foreach ($files as $file) {
            $data = @unserialize(@file_get_contents($file), ['allowed_classes' => false]);
            
            if ($data && isset($data['expires']) && $data['expires'] < time()) {
                @unlink($file);
                $removed++;
            }
        }
        
        return $removed;
    }
    
    /**
     * Private helpers
     */
    private static function getCachePath($key) {
        return self::$cacheDir . '/' . md5($key) . '.cache';
    }
    
    private static function registerTags($key, $tags) {
        foreach ($tags as $tag) {
            $tagFile = self::$cacheDir . '/_tag_' . md5($tag) . '.json';
            
            $keys = [];
            if (file_exists($tagFile)) {
                $keys = json_decode(@file_get_contents($tagFile), true) ?: [];
            }
            
            if (!in_array($key, $keys)) {
                $keys[] = $key;
                @file_put_contents($tagFile, json_encode($keys), LOCK_EX);
            }
        }
    }
    
    private static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// Initialize
CacheManager::init();