<?php
// admin/migrations/add_shipment_date.php
// require_once __DIR__ . '/../_auth.php'; // Commented out for CLI run
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

echo "Checking shipments table for shipment_date column...\n";

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM shipments LIKE 'shipment_date'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "Column 'shipment_date' already exists.\n";
    } else {
        // Add column
        $sql = "ALTER TABLE shipments ADD COLUMN shipment_date DATETIME NULL AFTER tracking_number";
        $pdo->exec($sql);
        echo "Successfully added 'shipment_date' column to shipments table.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
