<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN customer_address TEXT DEFAULT NULL AFTER customer_name");
    echo "Column customer_address added successfully.";
} catch (PDOException $e) {
    echo "Error or column already exists: " . $e->getMessage();
}
?>
