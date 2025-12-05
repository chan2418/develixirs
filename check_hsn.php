<?php
require_once __DIR__ . '/includes/db.php';

echo "Checking 'products' table columns...\n";
$stmt = $pdo->query("SHOW COLUMNS FROM products");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hasHsn = false;
foreach ($columns as $col) {
    echo "Column: {$col['name']} ({$col['type']})\n";
    if ($col['name'] === 'hsn') {
        $hasHsn = true;
    }
}

echo "\nHSN Column Exists: " . ($hasHsn ? 'YES' : 'NO') . "\n";
?>
