<?php
require_once __DIR__ . '/includes/db.php';

echo "Checking 'products' table columns...\n";
$stmt = $pdo->query("SHOW COLUMNS FROM products");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hasHsn = false;
foreach ($columns as $col) {
    if ($col['Field'] === 'hsn') {
        $hasHsn = true;
        echo "Found column: {$col['Field']} ({$col['Type']})\n";
    }
}

if ($hasHsn) {
    echo "SUCCESS: 'hsn' column exists.\n";
} else {
    echo "FAILURE: 'hsn' column missing.\n";
}
?>
