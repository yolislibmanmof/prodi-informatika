<?php
/**
 * database.php - Database Connection Handler
 * Secure PDO connection dengan environment-based configuration
 * Features: Connection pooling, auto-reconnect, secure error handling
 */

// Pastikan config sudah di-load
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

class Database {
    private static $instance = null;
    private $conn = null;
    
    // Connection parameters dari config
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    
    /**
     * Private constructor untuk enforce singleton pattern
     */
    private function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Get database connection (Singleton)
     * @return PDO
     */
    public function getConnection() {
        // Return existing connection jika masih valid
        if ($this->conn !== null && $this->isConnectionAlive()) {
            return $this->conn;
        }
        
        // Create new connection
        return $this->connect();
    }
    
    /**
     * Establish database connection
     * @return PDO
     */
    private function connect() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host={$this->host};port=" . (defined("DB_PORT") ? (int) DB_PORT : 3306) . ";dbname={$this->db_name};charset={$this->charset}";
            
            // PDO options untuk security dan performance
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Prevent SQL injection
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_PERSISTENT => false, // Disable persistent connections untuk better security
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            // Set connection timeout (5 seconds)
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            $this->conn->setAttribute(PDO::ATTR_TIMEOUT, 5);
            
// ✅ Ambil timezone dari config, convert ke offset
$appTimezone = defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Asia/Jakarta';
$dateTime = new DateTime('now', new DateTimeZone($appTimezone));
$dbTimezone = $dateTime->format('P'); // +08:00
$this->conn->exec("SET time_zone = '" . $dbTimezone . "'");
            
            return $this->conn;
            
        } catch (PDOException $e) {
            // Log error tapi jangan expose ke user (security)
            $this->logError($e);
            
            // Show user-friendly message
            if (APP_DEBUG) {
                // Development: show detailed error
                die("Database Connection Failed: " . $e->getMessage());
            } else {
                // Production: generic message
                die("Maaf, terjadi kesalahan sistem. Silakan coba beberapa saat lagi.");
            }
        }
    }
    
    /**
     * Check apakah connection masih alive
     * @return bool
     */
    private function isConnectionAlive() {
        try {
            if ($this->conn === null) {
                return false;
            }
            
            // Simple query untuk test connection
            $this->conn->query('SELECT 1');
            return true;
            
        } catch (PDOException $e) {
            // Connection lost, log dan return false
            $this->logError($e, 'Connection check failed');
            return false;
        }
    }
    
    /**
     * Log database errors
     * @param PDOException $e
     * @param string $context
     */
    private function logError($e, $context = '') {
        $logMessage = sprintf(
            "[%s] Database Error: %s | Context: %s | Host: %s | DB: %s\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $context,
            $this->host,
            $this->db_name
        );
        
        $logFile = __DIR__ . '/../logs/database-error.log';
        
        // Pastikan folder logs ada
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        error_log($logMessage, 3, $logFile);
    }
    
    /**
     * Close connection (optional, PHP akan auto-close saat script selesai)
     */
    public function closeConnection() {
        $this->conn = null;
    }
    
    /**
     * Get singleton instance
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

/**
 * Global function untuk backward compatibility
 * Usage: $db = getDB();
 * 
 * @return PDO
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * Helper function untuk transaction
 * Usage:
 * dbTransaction(function($db) {
 *     $db->exec("INSERT INTO ...");
 *     $db->exec("UPDATE ...");
 * });
 * 
 * @param callable $callback
 * @return mixed Result dari callback
 */
function dbTransaction($callback) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        $result = $callback($db);
        
        $db->commit();
        return $result;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}