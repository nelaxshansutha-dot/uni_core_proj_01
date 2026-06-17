<?php
class Database {
    private $host = "localhost"; // db server
    private $db_name = "unicore_db"; // database
    private $username = "root"; //user name
    private $password = ""; //password
    public $conn;   //connection object 

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");// hand writting good
            // Set PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // exception throw
        } catch(PDOException $exception) {
            // Handle error, optionally log it
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>
