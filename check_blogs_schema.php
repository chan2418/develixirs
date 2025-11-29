<?php
require_once __DIR__ . '/includes/db.php';

try {
    $stmt = $pdo->query("DESCRIBE blogs");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Columns in blogs table:\n";
    print_r($columns);
    
    if (in_array('category_id', $columns)) {
        echo "\ncategory_id exists!";
    } else {
        echo "\ncategory_id MISSING!";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
