<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Add is_featured column
    $pdo->exec("ALTER TABLE `external_reviews` ADD COLUMN `is_featured` TINYINT(1) DEFAULT '0' AFTER `is_active`");
    echo "Column 'is_featured' added successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'is_featured' already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
