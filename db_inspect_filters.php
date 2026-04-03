<?php
require_once __DIR__ . '/includes/db.php';

function describeTable($pdo, $table) {
    echo "Table: $table\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

describeTable($pdo, 'filter_groups');
describeTable($pdo, 'categories');
?>
