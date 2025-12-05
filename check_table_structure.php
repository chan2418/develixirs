<?php
require_once 'includes/db.php';

echo "<h2>Users Table Structure</h2>";
$stmt = $pdo->query("DESCRIBE users");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";

echo "<h2>Products Table Structure</h2>";
$stmt = $pdo->query("DESCRIBE products");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";

echo "<h2>Orders Table Structure</h2>";
$stmt = $pdo->query("DESCRIBE orders");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
