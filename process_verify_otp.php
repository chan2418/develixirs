<?php
require_once __DIR__ . '/includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $otp = $_POST['otp'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ($user['otp'] === $otp) {
            // Success
            $update = $pdo->prepare("UPDATE users SET is_verified = 1, otp = NULL WHERE id = ?");
            $update->execute([$user['id']]);

            // Log in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Welcome message
            $_SESSION['login_success_msg'] = "Hey, Welcome back " . htmlspecialchars($user['name']) . "! 👋";

            header("Location: index.php");
            exit;
        } else {
            header("Location: verify-otp.php?email=" . urlencode($email) . "&error=Invalid OTP");
            exit;
        }
    } else {
        header("Location: signup.php?error=User not found");
        exit;
    }
}
?>
