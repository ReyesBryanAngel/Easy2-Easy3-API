<?php
namespace v1\Database;

use PDO;
use PDOException;
use Dotenv\Dotenv as Dotenv;


class Database {
    private $conn;
    private $host;
    private $port;
    private $username;
    private $password;
    private $database;

    public function __construct() {
        $dotenv = Dotenv::createImmutable(__DIR__ .'/../../../');
        $dotenv->load();
        $this->host = $_ENV['DB_HOST'];
        $this->port = $_ENV['DB_PORT'];
        $this->username = $_ENV['DB_USERNAME'];
        $this->password = $_ENV['DB_PASSWORD'];
        $this->database = $_ENV['DB_DATABASE'];
        
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->database}",
                $this->username,
                $this->password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $response = array(
                'status' => 'failed',
                'message' => "Connection failed: " . $e->getMessage()
            );
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
            exit;
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}
