<?php
require __DIR__ . '/../includes/db.php';
echo "<pre>";
echo "PDO DSN check\n";
try {
    $stmt = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "Current DB: " . $stmt . PHP_EOL;
    $stmt = $pdo->query("SELECT USER()")->fetchColumn();
    echo "Current MySQL user: " . $stmt . PHP_EOL;
} catch (Exception $e) {
    echo "DB error: " . $e->getMessage();
}
echo "</pre>";


// develixirs_pass

// # 1) Add columns one by one (no IF NOT EXISTS)
// docker exec -i develixirs_db mysql -u root -prootpass -e "USE develixirs_db; ALTER TABLE orders ADD COLUMN order_number VARCHAR(100) DEFAULT NULL;"
// docker exec -i develixirs_db mysql -u root -prootpass -e "USE develixirs_db; ALTER TABLE orders ADD COLUMN customer_name VARCHAR(255) DEFAULT NULL;"
// docker exec -i develixirs_db mysql -u root -prootpass -e "USE develixirs_db; ALTER TABLE orders ADD COLUMN order_status VARCHAR(50) DEFAULT 'pending';"

// # 2) Verify the columns were added
// docker exec -i develixirs_db mysql -u root -prootpass -e "USE develixirs_db; DESCRIBE orders\G"