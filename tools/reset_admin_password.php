<?php
// tools/reset_admin_password.php
require_once __DIR__ . '/../includes/db.php';

$email = 'admin@example.com'; // Change this if needed
$password = 'admin123';       // Change this to your desired password
$name = 'System Admin';

try {
    // 1. Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetchColumn();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($existing) {
        // Update existing
        $update = $pdo->prepare("UPDATE users SET password = ?, role = 'admin', is_active = 1 WHERE email = ?");
        $update->execute([$hash, $email]);
        echo "<h1>Success!</h1><p>Updated user <strong>$email</strong> with password: <strong>$password</strong></p>";
    } else {
        // Create new
        $insert = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, 'admin', 1)");
        $insert->execute([$name, $email, $hash]);
        echo "<h1>Success!</h1><p>Created new admin <strong>$email</strong> with password: <strong>$password</strong></p>";
    }
} catch (PDOException $e) {
    echo "<h1>Error</h1><p>" . $e->getMessage() . "</p>";
}
?>
