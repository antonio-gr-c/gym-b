<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar variables de entorno desde .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

class Database {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $this->host = $_ENV['DB_HOST'];
        $this->dbname = $_ENV['DB_NAME'];
        $this->username = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASS'];
    }

    public function connect() {
        try {
            // Aquí usamos \PDO y \PDOException directamente sin necesidad de `use`
            $this->conn = new \PDO("mysql:host=$this->host;dbname=$this->dbname;charset=utf8", $this->username, $this->password);
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch (\PDOException $e) {
            die(json_encode(["error" => "Error de conexión: " . $e->getMessage()]));
        }
    }
}
