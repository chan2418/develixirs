<?php
require_once __DIR__ . '/includes/db.php';

try {
    echo "Adding 'shipping_charge' column to 'orders' table...\n";
    $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_charge DECIMAL(10,2) DEFAULT 0.00 AFTER total_amount");
    echo "Success: Column 'shipping_charge' added.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Notice: Column 'shipping_charge' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>
