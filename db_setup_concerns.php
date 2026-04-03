<?php
require_once 'includes/db.php';

try {
    // 1. Create concerns table
    $sqlCon = "CREATE TABLE IF NOT EXISTS concerns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        image VARCHAR(255) NULL,
        description TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlCon);
    echo "Table 'concerns' created or already exists.\n";

    // 2. Add concern_id to products if not exists
    // Check if column exists first
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'concern_id'");
    if ($stmt->rowCount() == 0) {
        $sqlAlter = "ALTER TABLE products ADD COLUMN concern_id INT NULL DEFAULT NULL";
        $pdo->exec($sqlAlter);
        echo "Column 'concern_id' added to products table.\n";
    } else {
        echo "Column 'concern_id' already exists in products table.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
