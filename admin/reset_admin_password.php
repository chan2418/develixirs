<?php
/**
 * Simple Admin Password Reset - FOR HOSTINGER
 * Deletes and recreates admin user with correct password
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/db.php';

$email = 'admin@develixirs.com';
$password = 'admin@123';

echo "<h1>Admin Password Reset</h1>";

try {
    // First, delete existing admin user
    $delete = $pdo->prepare("DELETE FROM users WHERE email = ?");
    $delete->execute([$email]);
    echo "<p>✅ Deleted old admin user (if existed)</p>";
    
    // Create fresh admin user with correct password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $pdo->prepare("
        INSERT INTO users (name, email, password, role, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $insert->execute(['Admin', $email, $password_hash, 'admin']);
    echo "<p>✅ Created new admin user</p>";
    
    // Verify password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        echo "<p style='color:green; font-weight:bold;'>✅ PASSWORD VERIFIED! Login will work!</p>";
        echo "<p><strong>Credentials:</strong><br>";
        echo "Email: <code>$email</code><br>";
        echo "Password: <code>$password</code></p>";
        echo "<p><a href='login.php' style='background:#0b76ff; color:white; padding:12px 24px; text-decoration:none; border-radius:8px;'>Go to Login</a></p>";
    } else {
        echo "<p style='color:red;'>❌ Password verification failed</p>";
    }
    
    echo "<hr><p style='color:red;'><strong>⚠️ DELETE THIS FILE NOW!</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
