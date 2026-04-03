<?php
require_once __DIR__ . '/includes/db.php';

try {
    $stmt = $pdo->query("DESCRIBE homepage_products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
