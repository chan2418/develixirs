<?php
// add_admin.php - Run this once to add a new admin account
require_once __DIR__ . '/includes/db.php';

// New admin details
$email = 'admin@develixirs.com';
$password = 'admin@123';
$name = 'Admin';
$role = 'admin';

try {
    // Check if admin already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    
    if ($checkStmt->fetch()) {
        echo "❌ Admin with email '$email' already exists!<br>";
        exit;
    }
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new admin
    $insertStmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $insertStmt->execute([$name, $email, $password_hash, $role]);
    
    echo "✅ <b>Admin account created successfully!</b><br><br>";
    echo "<b>Login Details:</b><br>";
    echo "Email: <code>$email</code><br>";
    echo "Password: <code>$password</code><br><br>";
    echo "You can now delete this file (add_admin.php) for security.";
    
} catch (PDOException $e) {
    echo "❌ <b>Error creating admin:</b> " . $e->getMessage();
}
?>
