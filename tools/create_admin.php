<?php
require_once __DIR__ . '/../includes/db.php';

$name = 'Admin';
$email = 'admin@develixirs.com';
$password_plain = 'Admin@123'; // change immediately after first login
$role = 'admin';

// hash it
$hash = password_hash($password_plain, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
try {
    $stmt->execute([$name, $email, $hash, $role]);
    echo "Admin created: $email / $password_plain";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}