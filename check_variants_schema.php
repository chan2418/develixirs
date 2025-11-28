<?php
require_once __DIR__ . '/includes/db.php';

try {
    $stmt = $pdo->query("DESCRIBE product_variants");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "Column: " . $col['Field'] . " | Type: " . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
