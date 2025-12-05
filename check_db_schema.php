<?php
require_once __DIR__ . '/includes/db.php';

try {
    $stmt = $pdo->query("SELECT id FROM products LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Product ID: " . ($row['id'] ?? 'None') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
