<?php
require_once __DIR__ . '/includes/db.php';

try {
    // 1. Create product_groups table
    $sql1 = "CREATE TABLE IF NOT EXISTS product_groups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql1);
    echo "Table 'product_groups' created or already exists.\n";

    // 2. Create product_group_map table
    $sql2 = "CREATE TABLE IF NOT EXISTS product_group_map (
        product_id INT NOT NULL,
        group_id INT NOT NULL,
        PRIMARY KEY (product_id, group_id),
        FOREIGN KEY (group_id) REFERENCES product_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql2);
    echo "Table 'product_group_map' created or already exists.\n";

} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage() . "\n";
}
?>
