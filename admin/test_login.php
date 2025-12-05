<?php
// Test admin login process
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Admin Login Test</h1>";

require_once '../includes/db.php';

$email = 'admin@develixirs.com';
$password = 'admin@123';

echo "<h2>Testing Login for: $email</h2>";

try {
    $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User found<br>";
        echo "Name: " . htmlspecialchars($user['name']) . "<br>";
        echo "Email: " . htmlspecialchars($user['email']) . "<br>";
        echo "Role: " . htmlspecialchars($user['role']) . "<br>";
        
        if ($user['role'] === 'admin') {
            echo "✅ User is admin<br>";
            
            if (password_verify($password, $user['password'])) {
                echo "✅ <strong>PASSWORD CORRECT!</strong><br>";
                echo "<br><strong style='color:green'>Login should work!</strong><br>";
                echo "<br><a href='login.php'>Go to Admin Login</a>";
            } else {
                echo "❌ PASSWORD INCORRECT!<br>";
                echo "Password hash in database: " . substr($user['password'], 0, 30) . "...<br>";
                echo "<br><strong style='color:red'>Password does not match!</strong><br>";
                echo "<p>You need to create admin with correct password hash.</p>";
            }
        } else {
            echo "❌ User is not admin (role: " . $user['role'] . ")<br>";
        }
    } else {
        echo "❌ No user found with email: $email<br>";
        echo "<p>You need to create the admin user first!</p>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p>After fixing, <strong>delete this file</strong> for security!</p>";
?>
