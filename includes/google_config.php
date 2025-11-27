<?php
// Google API Configuration
define('GOOGLE_CLIENT_ID', '352017365404-j268u8pe0ff0m4h39aum9kfocao31l8c.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URL', 'http://localhost:8003/google-callback.php');

// Google OAuth URL
$google_oauth_url = 'https://accounts.google.com/o/oauth2/v2/auth?client_id=' . GOOGLE_CLIENT_ID . '&redirect_uri=' . urlencode(GOOGLE_REDIRECT_URL) . '&response_type=code&scope=email%20profile';
?>
