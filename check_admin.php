<?php
// check_admin.php - Verify admin account exists and reset if needed
require_once __DIR__ . '/includes/db.php';

$email = 'admin@develixirs.com';
$password = 'admin@123';

echo "<h2>Admin Account Diagnostic</h2>";

try {
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ <b>Admin account EXISTS</b><br>";
        echo "ID: {$admin['id']}<br>";
        echo "Name: {$admin['name']}<br>";
        echo "Email: {$admin['email']}<br>";
        echo "Role: {$admin['role']}<br>";
        echo "Created: {$admin['created_at']}<br><br>";
        
        // Now test the password
        $passStmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
        $passStmt->execute([$email]);
        $passData = $passStmt->fetch();
        
        if (password_verify($password, $passData['password'])) {
            echo "✅ <b style='color:green;'>PASSWORD IS CORRECT! Login should work!</b><br><br>";
            echo "<a href='admin/login.php' style='background:#0b76ff;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;'>Go to Admin Login</a>";
        } else {
            echo "❌ <b style='color:red;'>PASSWORD VERIFICATION FAILED!</b><br>";
            echo "The password hash is incorrect. Click the button below to fix it:<br><br>";
            echo "<form method='post'>";
            echo "<button type='submit' name='reset_password' style='background:#ff4d4d;color:white;padding:12px 24px;border:none;border-radius:8px;cursor:pointer;'>Reset Admin Password</button>";
            echo "</form>";
        }
    } else {
        echo "❌ <b>Admin account NOT FOUND!</b><br>";
        echo "Click the button below to create it:<br><br>";
        echo "<form method='post'>";
        echo "<button type='submit' name='create_admin' style='background:#4CAF50;color:white;padding:12px 24px;border:none;border-radius:8px;cursor:pointer;'>Create Admin Account</button>";
        echo "</form>";
    }
    
    // Handle form submissions
    if (isset($_POST['reset_password'])) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $updateStmt->execute([$password_hash, $email]);
        echo "<br><br>✅ <b style='color:green;'>Password has been reset! Refresh this page to verify.</b>";
    }
    
    if (isset($_POST['create_admin'])) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $insertStmt->execute(['Admin', $email, $password_hash, 'admin']);
        echo "<br><br>✅ <b style='color:green;'>Admin account created! Refresh this page to verify.</b>";
    }
    
    echo "<hr><p style='color:red;'><b>⚠️ DELETE THIS FILE AFTER USE!</b></p>";
    
} catch (PDOException $e) {
    echo "❌ <b>Database Error:</b> " . htmlspecialchars($e->getMessage());
}
?>
