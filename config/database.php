<?php
/**
 * Database Configuration for Pet Clinic Application
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'pet_clinic';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
    
    public function closeConnection() {
        $this->conn = null;
    }
}

// Global database connection function
function getDB() {
    $database = new Database();
    return $database->getConnection();
}

// Initialize database if not exists
function initializeDatabase() {
    try {
        // Connect without database name first
        $conn = new PDO("mysql:host=localhost", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Read and execute schema
        $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $conn->exec($statement);
            }
        }
        
        $conn = null;
        return true;
    } catch(PDOException $e) {
        error_log("Database initialization error: " . $e->getMessage());
        return false;
    }
}
?>

