<?php
require_once __DIR__ . '/includes/db.php';

echo "Checking 'orders' table columns...\n";
$stmt = $pdo->query("SHOW COLUMNS FROM orders");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hasShipping = false;
foreach ($columns as $col) {
    if ($col['Field'] === 'shipping_charge') {
        $hasShipping = true;
        echo "Found column: {$col['Field']} ({$col['Type']})\n";
    }
}

if ($hasShipping) {
    echo "SUCCESS: 'shipping_charge' column exists.\n";
} else {
    echo "FAILURE: 'shipping_charge' column missing.\n";
}
?>
