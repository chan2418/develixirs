<?php
// handlers/submit_form.php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);
$formType = $_POST['form_type'] ?? 'contact';
$returnUrl = $_POST['return_url'] ?? '/';
// Recipient: if set use it, else default
$recipient = filter_input(INPUT_POST, 'recipient', FILTER_SANITIZE_EMAIL);
$to = !empty($recipient) ? $recipient : 'chandrusri247@gmail.com'; // Default from send_contact.php

// Simple Validation
if (!$name || !$email || !$message) {
   die('Please fill all fields');
}

$subject = "New Form Submission: " . ucfirst($formType);
$body = "
You have received a new message from the website form ($formType).

Name: $name
Email: $email
Message:
$message

---------------------------
Sent from: " . $_SERVER['HTTP_HOST'];

$headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
$headers .= "Reply-To: $email";

// Send Email
mail($to, $subject, $body, $headers);

// Redirect with success
if (strpos($returnUrl, '?') !== false) {
    header("Location: $returnUrl&success=1");
} else {
    header("Location: $returnUrl?success=1");
}
exit;
