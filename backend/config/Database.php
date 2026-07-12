<?php

namespace Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $conn;

    private $host = '127.0.0.1';
    private $db_name = 'uni_core_proj_01'; 
    private $username = 'root';
    private $password = '';

    private function __construct() {
       
        if (isset($_ENV['DB_HOST'])) $this->host = $_ENV['DB_HOST'];
        if (isset($_ENV['DB_NAME'])) $this->db_name = $_ENV['DB_NAME'];
        if (isset($_ENV['DB_USER'])) $this->username = $_ENV['DB_USER'];
        if (isset($_ENV['DB_PASS'])) $this->password = $_ENV['DB_PASS'];

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
         
            error_log("Connection Error: " . $e->getMessage());
            die(json_encode(["success" => false, "message" => "Database connection failed."]));
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
