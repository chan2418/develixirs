<?php
require_once __DIR__ . '/includes/db.php';

try {
    echo "=== PRODUCTS TABLE STRUCTURE ===\n";
    $stm = $pdo->query("DESCRIBE products");
    $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
    }

    echo "\n=== PRODUCT_GROUPS TABLE STRUCTURE ===\n";
    $stm2 = $pdo->query("DESCRIBE product_groups");
    $rows2 = $stm2->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows2 as $row) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
