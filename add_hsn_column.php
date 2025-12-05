<?php
require_once __DIR__ . '/includes/db.php';

try {
    echo "Adding 'hsn' column to 'products' table...\n";
    $pdo->exec("ALTER TABLE products ADD COLUMN hsn VARCHAR(20) DEFAULT NULL AFTER sku");
    echo "Success: Column 'hsn' added.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Notice: Column 'hsn' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>
