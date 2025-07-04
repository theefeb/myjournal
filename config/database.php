<?php
require_once __DIR__ . '/constants.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->pdo->query("SELECT 1"); // Test connection immediately

        } catch (PDOException $e) {
            $error = "Database Connection Failed:\n";
            $error .= "Message: " . $e->getMessage() . "\n";
            $error .= "Trying to connect to: mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . "\n";
            $error .= "Using username: " . DB_USER;

            error_log($error);
            throw new Exception("Could not connect to database. Please check your database configuration.");
        }
    }
    

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }

    public static function testConnection() {
        try {
            $pdo = self::getInstance();
            $pdo->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            error_log("Connection test failed: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize database connection
try {
    $pdo = Database::getInstance();

    // Set timezone for database connection
    $pdo->exec("SET time_zone = '+00:00'");

    // Test connection
    if (!Database::testConnection()) {
        throw new Exception("Failed to verify database connection");
    }
} catch (Exception $e) {
    die("Database initialization error: " . $e->getMessage());
}