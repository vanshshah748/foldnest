<?php
// backend/config/database.php
// Database connection class using PDO with environment variable support

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    /**
     * Constructor — loads database credentials from .env file
     */
    public function __construct() {
        // Load environment variables from .env file
        $env_path = realpath(__DIR__ . '/../../.env');
        if (file_exists($env_path)) {
            $env = parse_ini_file($env_path);
            $this->host    = isset($env['DB_HOST']) ? trim($env['DB_HOST'], '"\'') : 'localhost';
            $this->db_name = isset($env['DB_NAME']) ? trim($env['DB_NAME'], '"\'') : 'ecommerce_db';
            $this->username = isset($env['DB_USER']) ? trim($env['DB_USER'], '"\'') : 'root';
            $this->password = isset($env['DB_PASS']) ? trim($env['DB_PASS'], '"\'') : '';
        } else {
            // Fallback defaults for XAMPP/WAMP
            $this->host    = 'localhost';
            $this->db_name = 'ecommerce_db';
            $this->username = 'root';
            $this->password = '';
        }
    }

    /**
     * Get the database connection
     *
     * @return PDO|null Returns PDO connection object or null on failure
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            // Set error mode to exception for proper error handling
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Set default fetch mode to associative array
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Set character encoding to UTF-8
            $this->conn->exec("SET NAMES utf8mb4");
        } catch(PDOException $exception) {
            // Log error instead of displaying it (security best practice)
            error_log("Database Connection Error: " . $exception->getMessage());
            // Return null so the calling code can handle the error
        }

        return $this->conn;
    }
}
?>
