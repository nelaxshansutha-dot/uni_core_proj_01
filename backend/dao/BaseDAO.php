<?php
namespace DAO;

use Config\Database;
use PDO;

abstract class BaseDAO {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Helper to execute a query and return the statement
     */
    protected function executeQuery($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
