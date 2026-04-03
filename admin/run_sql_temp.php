<?php
require_once __DIR__ . '/../includes/db.php';
$sqlFile = __DIR__ . '/../create_labels_table.sql';
if (file_exists($sqlFile)) {
    $sql = file_get_contents($sqlFile);
    try {
        $pdo->exec($sql);
        echo "SQL executed successfully.";
    } catch (PDOException $e) {
        echo "Error executing SQL: " . $e->getMessage();
    }
} else {
    echo "SQL file not found.";
}
unlink(__FILE__); // Self-destruct
?>
