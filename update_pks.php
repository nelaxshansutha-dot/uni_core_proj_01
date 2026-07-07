<?php
$modelsDir = 'backend/models/';
$files = glob($modelsDir . '*.php');

foreach ($files as $file) {
    if (basename($file) === 'BaseModel.php') continue;

    $content = file_get_contents($file);
    
    // Find the findByIdBase($id, 'columnName') pattern to extract the primary key
    if (preg_match("/findByIdBase\(\\$id,\s*'([^']+)'\)/", $content, $matches)) {
        $pk = $matches[1];
    } else {
        $pk = 'id'; // default
    }

    // Replace the override of findById that has 2 args
    $content = preg_replace(
        "/public function findById\(\\$id\).*?findByIdBase\(\\$id,\s*'[^']+'\);\s*}/s",
        "public function findById(\$id) {\n        return parent::findByIdBase(\$id);\n    }",
        $content
    );
    
    // Replace the parent::findByIdBase($id, '...') pattern
    $content = preg_replace(
        "/parent::findByIdBase\(\\$id,\s*'[^']+'\)/",
        "parent::findByIdBase(\$id)",
        $content
    );

    // Replace the $this->findByIdBase($id, '...') pattern
    $content = preg_replace(
        "/\\\$this->findByIdBase\(\\$id,\s*'[^']+'\)/",
        "\$this->findByIdBase(\$id)",
        $content
    );

    // Add getPrimaryKey method below getTableName
    if (strpos($content, 'getPrimaryKey') === false) {
        $replacement = <<<EOT
getTableName() {
        return "$1";
    }

    // Encapsulation: Define primary key internally
    protected function getPrimaryKey() {
        return "$pk";
    }
EOT;
        $content = preg_replace('/getTableName\(\)\s*\{\s*return\s*"([^"]+)";\s*\}/', $replacement, $content);
    }
    
    file_put_contents($file, $content);
    echo "Updated " . basename($file) . " with PK $pk\n";
}
