<?php
require_once __DIR__ . '/includes/db.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM order_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in 'order_items' table:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
