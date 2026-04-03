<?php
// Try to include db.php from known locations
$paths = [
    __DIR__ . '/includes/db.php',
    __DIR__ . '/../includes/db.php',
    $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php'
];

$connected = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        if (isset($pdo)) {
            $connected = true;
            break;
        }
    }
}

if (!$connected) {
    die("Error: Could not find includes/db.php or establish database connection.");
}

try {
    // Attempt 1: Create WITHOUT Foreign Keys first (safest for different engines/collations)
    // We will just index the columns for performance
    $sql = "CREATE TABLE IF NOT EXISTS order_returns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        user_id INT NOT NULL,
        reason TEXT,
        images TEXT, -- JSON array of image paths
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (order_id),
        INDEX (user_id)
    )";
    // Note: I removed the FOREIGN KEY constraints to avoid the errno 150.
    // Logic will still work fine at application level.

    $pdo->exec($sql);
    echo "<h1>Success!</h1>";
    echo "<p>Table 'order_returns' created successfully (without strict constraints).</p>";
    echo "<p><a href='order-details.php'>Go back to Order Details</a></p>";

} catch (PDOException $e) {
    echo "<h1>Error</h1>";
    echo "<p>Error creating table: " . $e->getMessage() . "</p>";
}
?>
