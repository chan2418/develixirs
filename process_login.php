<?php
// process_login.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($email) || empty($password)) {
        header("Location: login.php?error=" . urlencode("Email and Password are required."));
        exit;
    }

    try {
        // Fetch user
        $stmt = $pdo->prepare("SELECT id, name, password, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verify Password
            if (password_verify($password, $user['password'])) {
                
                // Check if verified
                if ($user['is_verified'] == 0) {
                    // Not verified -> redirect to OTP
                    header("Location: verify-otp.php?email=" . urlencode($email) . "&msg=" . urlencode("Please verify your email first."));
                    exit;
                }

                // LOGIN SUCCESS
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $email;

                // Set Flash Message for Welcome Toast
                $_SESSION['login_success_msg'] = "Hey, Welcome back " . htmlspecialchars($user['name']) . "! 👋";

                // Redirect to Home (index.php) instead of profile
                header("Location: index.php");
                exit;

            } else {
                // Invalid Password
                header("Location: login.php?error=" . urlencode("Invalid password."));
                exit;
            }
        } else {
            // User not found
            header("Location: login.php?error=" . urlencode("No account found with this email."));
            exit;
        }

    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        header("Location: login.php?error=" . urlencode("Something went wrong. Please try again."));
        exit;
    }

} else {
    // If accessed directly
    header("Location: login.php");
    exit;
}
