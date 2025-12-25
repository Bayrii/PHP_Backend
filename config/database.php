<?php
/**
 * Database Configuration
 * Contains database connection settings using PDO
 */

// Database configuration constants
define('DB_HOST', 'mysql-bayram-aliyev.alwaysdata.net');
define('DB_USER', '443284_student');
define('DB_PASS', 'student123');
define('DB_NAME', 'bayram-aliyev_driving_experience');
define('DB_CHARSET', 'utf8mb4');


// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', 'bayram123!');
// define('DB_NAME', 'driving_experience');
// define('DB_CHARSET', 'utf8mb4');


/**
 * Database Connection Class using PDO
 * Singleton pattern for single database connection
 */
class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get the singleton instance
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection using PDO
     */
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database Error: Connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get the PDO connection
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a query and return PDOStatement
     * @param string $query
     * @return PDOStatement|false
     */
    public function query($query) {
        try {
            return $this->connection->query($query);
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Prepare a statement
     * @param string $query
     * @return PDOStatement|false
     */
    public function prepare($query) {
        try {
            return $this->connection->prepare($query);
        } catch (PDOException $e) {
            error_log("Prepare Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get last inserted ID
     * @return string
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Prevent cloning of instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>
