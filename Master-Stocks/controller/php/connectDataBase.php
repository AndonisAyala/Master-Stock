<?php
// connectDataBase.php - Conexión a la base de datos

class Database {
    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $database = "Master-Stocks";
    public $connection;
    
    public function __construct() {
        $this->connection = new mysqli($this->host, $this->user, $this->password, $this->database);
        
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        
        // Set the character set to UTF-8
        if (!$this->connection->set_charset("utf8")) {
            die("Error loading character set utf8: " . $this->connection->error);
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

// Crear instancia de la base de datos
$database = new Database();
$connection = $database->getConnection();
?>