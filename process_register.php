<?php
// process_register.php

require_once __DIR__ . '/includes/db.php'; // DB connection (creates $pdo)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form values
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if ($password !== $confirm_password) {
        // Passwords don't match → back to signup with error
        header("Location: signup.php?error=" . urlencode("Passwords do not match."));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Invalid email → back to signup with error
        header("Location: signup.php?error=" . urlencode("Invalid email address."));
        exit;
    }

    if ($name === '') {
        header("Location: signup.php?error=" . urlencode("Name is required."));
        exit;
    }

    // Encrypt password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate OTP
    $otp = rand(100000, 999999);

    try {
        // Prepare insert query (is_verified = 0)
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, otp, is_verified)
            VALUES (?, ?, ?, ?, 0)
        ");

        // Execute with values
        $success = $stmt->execute([$name, $email, $hashedPassword, $otp]);

        // If insert successful → Send OTP
        if ($success) {
            require_once __DIR__ . '/includes/SMTPMailer.php';
            $mailer = new SMTPMailer();
            $subject = "Verify your email - Develixirs";
            $message = "Hi $name,<br><br>Your OTP for verification is: <b>$otp</b><br><br>Please enter this OTP to complete your registration.";
            
            $mailResult = $mailer->send($email, $subject, $message);
            
            if ($mailResult === true) {
                header("Location: verify-otp.php?email=" . urlencode($email));
                exit;
            } else {
                // Email failed, but user created. Delete user or handle error?
                // For now, let's show error.
                header("Location: signup.php?error=" . urlencode("Registration successful but failed to send OTP. Please contact support."));
                exit;
            }
        } else {
            header("Location: signup.php?error=" . urlencode("Registration failed. Please try again."));
            exit;
        }

    } catch (PDOException $e) {

        // Duplicate email (email is UNIQUE)
        if ($e->getCode() == 23000) {
            header("Location: signup.php?error=" . urlencode("This email is already registered."));
            exit;
        }

        // Other DB error
        header("Location: signup.php?error=" . urlencode("Registration failed: " . $e->getMessage()));
        exit;
    }
}

// If someone accesses this file directly (not POST), send back to signup
header("Location: signup.php");
exit;
