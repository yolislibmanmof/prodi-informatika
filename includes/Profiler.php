<?php
/**
 * Simple Profiler untuk development
 */

class Profiler {
    private static $startTime;
    private static $queries = [];
    private static $enabled = false;
    
    public static function start() {
        // Enable hanya di development
        self::$enabled = (Config::get('APP_ENV') === 'development');
        
        if (!self::$enabled) return;
        
        self::$startTime = microtime(true);
    }
    
    public static function logQuery($query, $duration) {
        if (!self::$enabled) return;
        
        self::$queries[] = [
            'query' => $query,
            'duration' => $duration
        ];
    }
    
    public static function getStats() {
        if (!self::$enabled) return null;
        
        $endTime = microtime(true);
        $totalTime = $endTime - self::$startTime;
        
        $totalQueryTime = array_sum(array_column(self::$queries, 'duration'));
        
        return [
            'total_time' => round($totalTime * 1000, 2) . 'ms',
            'query_count' => count(self::$queries),
            'query_time' => round($totalQueryTime * 1000, 2) . 'ms',
            'memory_usage' => round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB',
            'queries' => self::$queries
        ];
    }
    
    public static function render() {
        if (!self::$enabled) return;
        
        $stats = self::getStats();
        
        echo '<div style="position:fixed;bottom:0;right:0;background:#000;color:#0f0;padding:10px;font-family:monospace;font-size:12px;z-index:9999;max-height:300px;overflow:auto;">';
        echo '<strong>⚡ Performance Stats</strong><br>';
        echo "Time: {$stats['total_time']}<br>";
        echo "Queries: {$stats['query_count']} ({$stats['query_time']})<br>";
        echo "Memory: {$stats['memory_usage']}<br>";
        echo '<details><summary>Query Log</summary><pre>' . print_r($stats['queries'], true) . '</pre></details>';
        echo '</div>';
    }
}

// Start profiler
Profiler::start();