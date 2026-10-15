<?php
/**
 * Cache.php - Redis Caching Layer
 * Performance boost 10-50x untuk query berulang
 */

class Cache {
    private static $redis = null;
    private static $enabled = true;
    
    public static function init() {
        if (!extension_loaded('redis')) {
            self::$enabled = false;
            return;
        }
        
        try {
            self::$redis = new Redis();
            self::$redis->connect('127.0.0.1', 6379);
            self::$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        } catch (Exception $e) {
            self::$enabled = false;
            error_log("Redis connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get cached data atau execute callback jika belum ada
     * Usage: Cache::remember('key', 3600, function() { return expensive_query(); });
     */
    public static function remember($key, $ttl, $callback) {
        if (!self::$enabled || !self::$redis) {
            return $callback();
        }
        
        try {
            $data = self::$redis->get($key);
            
            if ($data !== false) {
                return $data;
            }
            
            $data = $callback();
            self::$redis->setex($key, $ttl, $data);
            
            return $data;
        } catch (Exception $e) {
            error_log("Cache error: " . $e->getMessage());
            return $callback();
        }
    }
    
    /**
     * Hapus cache by key
     */
    public static function forget($key) {
        if (!self::$enabled || !self::$redis) return;
        
        try {
            self::$redis->del($key);
        } catch (Exception $e) {
            error_log("Cache forget error: " . $e->getMessage());
        }
    }
    
    /**
     * Hapus semua cache dengan pattern
     * Usage: Cache::forgetPattern('berita:*')
     */
    public static function forgetPattern($pattern) {
        if (!self::$enabled || !self::$redis) return;
        
        try {
            $keys = self::$redis->keys($pattern);
            if (!empty($keys)) {
                self::$redis->del($keys);
            }
        } catch (Exception $e) {
            error_log("Cache pattern forget error: " . $e->getMessage());
        }
    }
    
    /**
     * Clear semua cache
     */
    public static function flush() {
        if (!self::$enabled || !self::$redis) return;
        
        try {
            self::$redis->flushDB();
        } catch (Exception $e) {
            error_log("Cache flush error: " . $e->getMessage());
        }
    }
}

// Initialize cache
Cache::init();