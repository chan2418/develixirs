<?php
// admin/authenticate.php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Basic POST checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// CSRF check
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header('Location: login.php?err=1');
    exit;
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = trim($_POST['password'] ?? '');

if (!$email || !$password) {
    header('Location: login.php?err=1');
    exit;
}

try {
    // Prepared statement to get user
    $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && ($user['role'] === 'admin')) {
        // password stored hashed using password_hash()
        if (password_verify($password, $user['password'])) {
            // Successful login
            session_regenerate_id(true);
            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['name'];
            // optional: last_login update
            $update = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
            $update->execute([$user['id']]);
            header('Location: dashboard.php');
            exit;
        }
    }

    // fallback - invalid
    header('Location: login.php?err=1');
    exit;

} catch (PDOException $e) {
    // log $e->getMessage() into a file in production
    header('Location: login.php?err=1');
    exit;
}