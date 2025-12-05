<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN tax_amount DECIMAL(10,2) DEFAULT 0.00 AFTER shipping_charge");
    echo "Column tax_amount added successfully.";
} catch (PDOException $e) {
    echo "Error or column already exists: " . $e->getMessage();
}
?>
