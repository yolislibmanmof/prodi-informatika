<?php
/**
 * Seeder untuk populate database dengan dummy data
 * Jalankan sekali setelah setup database
 * 
 * Usage: php database/seed.php
 */

require_once __DIR__ . '/../includes/Config.php';
require_once __DIR__ . '/../includes/database.php';

echo "🌱 Starting database seeding...\n";

try {
    $db = getDB();
    
    // Check apakah sudah ada data
    $count = $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    
    if ($count > 0) {
        echo "⚠️  Database sudah berisi data. Skip seeding.\n";
        echo "   Gunakan --force untuk override: php database/seed.php --force\n";
        
        if (!in_array('--force', $argv)) {
            exit(0);
        }
        
        echo "🗑️  Menghapus data existing...\n";
        $db->exec("TRUNCATE TABLE admins");
        $db->exec("TRUNCATE TABLE berita");
        $db->exec("TRUNCATE TABLE faq");
    }
    
    // Load seed SQL
    $seedSQL = file_get_contents(__DIR__ . '/seed.sql');
    
    // Execute
    $db->exec($seedSQL);
    
    echo "✅ Seeding completed successfully!\n";
    echo "\n📝 Default credentials:\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n";
    echo "   ⚠️  PENTING: Segera ganti password setelah login pertama!\n\n";
    
} catch (Exception $e) {
    echo "❌ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}