<?php
require_once __DIR__ . '/includes/db.php';

echo "Checking 'notifications' table columns...\n";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "Found column: {$col['Field']} ({$col['Type']})\n";
    }
} catch (PDOException $e) {
    echo "Table 'notifications' does not exist or error: " . $e->getMessage() . "\n";
    
    // Create table if not exists
    echo "Attempting to create table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        url VARCHAR(255),
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table created or already exists.\n";
}
?>
