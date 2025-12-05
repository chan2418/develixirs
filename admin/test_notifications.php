<?php
require_once __DIR__ . '/../includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing notifications table...\n";

try {
    $stmt = $pdo->query("SELECT * FROM notifications LIMIT 1");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Success! Table exists.\n";
    print_r($rows);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
