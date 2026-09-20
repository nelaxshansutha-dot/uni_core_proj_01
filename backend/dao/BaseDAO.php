<?php
namespace DAO;

use Config\Database;
use PDO;

abstract class BaseDAO {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

   
    protected function executeQuery($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;

        //IN HERE THE EXECUTING THE QUERY TO PREVENT THE SQL INJECTION
    }
}
