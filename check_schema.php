<?php
require_once 'includes/db.php';

echo "<h2>Database Tables</h2>";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($tables);
echo "</pre>";

echo "<h2>Users Table Structure</h2>";
$stmt = $pdo->query("DESCRIBE users");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";

echo "<h2>Orders Table Structure</h2>";
$stmt = $pdo->query("DESCRIBE orders");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
