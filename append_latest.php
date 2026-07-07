<?php
$file = 'backend/models/Marketplace.php';
$content = file_get_contents($file);

$method = <<<EOT

    public function getLatestItems(\$limit = 5) {
        \$query = "SELECT productID as id, productName as title, 'marketplace' as type, created_at FROM " . \$this->table . " ORDER BY created_at DESC LIMIT " . intval(\$limit);
        \$stmt = \$this->conn->prepare(\$query);
        \$stmt->execute();
        return \$stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
EOT;

$content = preg_replace('/}\s*\?>\s*$/ism', $method, $content);
file_put_contents($file, $content);

$file2 = 'backend/models/LostItem.php';
$content2 = file_get_contents($file2);

$method2 = <<<EOT

    public function getLatestItems(\$limit = 5) {
        \$query = "SELECT lostID as id, lostItemName as title, 'lost_item' as type, created_at FROM " . \$this->table . " ORDER BY created_at DESC LIMIT " . intval(\$limit);
        \$stmt = \$this->conn->prepare(\$query);
        \$stmt->execute();
        return \$stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
EOT;

$content2 = preg_replace('/}\s*\?>\s*$/ism', $method2, $content2);
file_put_contents($file2, $content2);

echo "Added getLatestItems to Marketplace and LostItem\n";
