<?php
require_once __DIR__ . '/includes/google_config.php';

if (GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID') {
    die('Please configure your Google Client ID and Secret in includes/google_config.php');
}

header('Location: ' . $google_oauth_url);
exit;
?>
