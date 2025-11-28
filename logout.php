<?php
session_start();

// Remove all session data
session_unset();

// Destroy the session completely
session_destroy();

// Redirect back to homepage or login page
header("Location: index.php");  // you can change to login.php if you prefer
exit;
