<?php
/**
 * QueryOptimizer.php - Query Performance Helper
 * Provides optimized queries dan query analysis
 */

class QueryOptimizer {
    
    /**
     * Get berita untuk homepage (optimized)
     * Menggunakan index: idx_berita_status_published
     */
    public static function getHomepageNews($limit = 6) {
        return cache('homepage_news_' . $limit, 300, function() use ($limit) {
            $db = getDB();
            
            // Optimized: hanya ambil kolom yang dibutuhkan
            $stmt = $db->prepare("
                SELECT 
                    id, judul, slug, kategori, ringkasan, gambar, 
                    published_at, views
                FROM berita 
                WHERE status = 'published' 
                ORDER BY published_at DESC 
                LIMIT ?
            ");
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        });
    }
    
    /**
     * Get berita dengan pagination (optimized)
     */
    public static function getPaginatedNews($page = 1, $perPage = 12, $kategori = null) {
        $db = getDB();
        $offset = ($page - 1) * $perPage;
        
        $where = "WHERE status = 'published'";
        $params = [];
        
        if ($kategori) {
            $where .= " AND kategori = ?";
            $params[] = $kategori;
        }
        
        // Query dengan index
        $sql = "
            SELECT 
                id, judul, slug, kategori, ringkasan, gambar, 
                published_at, views, author_id
            FROM berita 
            $where
            ORDER BY published_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param);
        }
        $stmt->bindValue(count($params) + 1, (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get total count dengan caching
     */
    public static function getTotalNews($kategori = null) {
        $cacheKey = 'total_news_' . ($kategori ?? 'all');
        
        return cache($cacheKey, 300, function() use ($kategori) {
            $db = getDB();
            
            if ($kategori) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM berita WHERE status = 'published' AND kategori = ?");
                $stmt->execute([$kategori]);
            } else {
                $stmt = $db->query("SELECT COUNT(*) FROM berita WHERE status = 'published'");
            }
            
            return (int)$stmt->fetchColumn();
        });
    }
    
    /**
     * Get popular articles (most viewed)
     */
    public static function getPopularArticles($limit = 5) {
        return cache('popular_articles_' . $limit, 600, function() use ($limit) {
            $db = getDB();
            
            $stmt = $db->prepare("
                SELECT id, judul, slug, gambar, views, kategori
                FROM berita 
                WHERE status = 'published' 
                ORDER BY views DESC 
                LIMIT ?
            ");
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        });
    }
    
    /**
     * Get recent articles (sidebar)
     */
    public static function getRecentArticles($limit = 5) {
        return cache('recent_articles_' . $limit, 300, function() use ($limit) {
            $db = getDB();
            
            $stmt = $db->prepare("
                SELECT id, judul, slug, gambar, published_at
                FROM berita 
                WHERE status = 'published' 
                ORDER BY published_at DESC 
                LIMIT ?
            ");
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        });
    }
    
    /**
     * Get categories with count
     */
    public static function getCategoriesWithCount() {
        return cache('categories_count', 600, function() {
            $db = getDB();
            
            $stmt = $db->query("
                SELECT kategori, COUNT(*) as total
                FROM berita 
                WHERE status = 'published' 
                    AND kategori IS NOT NULL 
                    AND kategori != ''
                GROUP BY kategori
                ORDER BY total DESC
            ");
            
            return $stmt->fetchAll();
        });
    }
    
    /**
     * Get testimonials (optimized)
     */
    public static function getApprovedTestimonials($limit = 6) {
        return cache('approved_testimonials_' . $limit, 600, function() use ($limit) {
            $db = getDB();
            
            $sql = "
                SELECT id, nama, tahun_masuk, quote, avatar
                FROM testimoni 
                WHERE status = 'approved' 
                ORDER BY urutan ASC, created_at DESC
            ";
            
            if ($limit) {
                $sql .= " LIMIT " . (int)$limit;
            }
            
            return $db->query($sql)->fetchAll();
        });
    }
    
    /**
     * Get FAQs (optimized)
     */
    public static function getActiveFAQs() {
        return cache('active_faqs', 600, function() {
            $db = getDB();
            
            $stmt = $db->query("
                SELECT id, pertanyaan, jawaban
                FROM faq 
                WHERE status = 'aktif'
                ORDER BY urutan ASC
            ");
            
            return $stmt->fetchAll();
        });
    }
    
    /**
     * Get active announcements
     */
    public static function getActiveAnnouncements() {
        return cache('active_announcements', 300, function() {
            $db = getDB();
            
            $stmt = $db->query("
                SELECT id, judul, konten, prioritas, tanggal_mulai, tanggal_berakhir
                FROM pengumuman 
                WHERE status = 'aktif'
                    AND (tanggal_mulai IS NULL OR tanggal_mulai <= CURDATE())
                    AND (tanggal_berakhir IS NULL OR tanggal_berakhir >= CURDATE())
                ORDER BY 
                    FIELD(prioritas, 'penting', 'tinggi', 'normal', 'rendah'),
                    created_at DESC
            ");
            
            return $stmt->fetchAll();
        });
    }
    
    /**
     * Clear all news-related cache
     */
    public static function clearNewsCache() {
        $patterns = [
            'homepage_news_',
            'total_news_',
            'popular_articles_',
            'recent_articles_',
            'categories_count',
            'berita_slug_',
            'related_news_',
            'dashboard_stats'
        ];
        
        foreach ($patterns as $pattern) {
            cacheForgetPattern($pattern);
        }
        
        // Log untuk debugging
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("News cache cleared");
        }
    }
    
    /**
     * Clear testimonials cache
     */
    public static function clearTestimonialCache() {
        cacheForgetPattern('approved_testimonials');
    }
    
    /**
     * Clear FAQ cache
     */
    public static function clearFAQCache() {
        cacheForgetPattern('active_faqs');
    }
}