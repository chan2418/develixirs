<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/google_config.php';

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // 1. Exchange code for access token using socket connection
    $post_data = http_build_query([
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URL,
        'grant_type' => 'authorization_code'
    ]);

    // Make HTTPS request using fsockopen
    $fp = fsockopen('ssl://oauth2.googleapis.com', 443, $errno, $errstr, 30);
    if (!$fp) {
        die("Socket Error: $errstr ($errno)");
    }

    $request = "POST /token HTTP/1.1\r\n";
    $request .= "Host: oauth2.googleapis.com\r\n";
    $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $request .= "Content-Length: " . strlen($post_data) . "\r\n";
    $request .= "Connection: Close\r\n\r\n";
    $request .= $post_data;

    fwrite($fp, $request);
    
    $response = '';
    while (!feof($fp)) {
        $response .= fgets($fp, 128);
    }
    fclose($fp);

    // Extract body from response (handle chunked encoding)
    $parts = explode("\r\n\r\n", $response, 2);
    $body = isset($parts[1]) ? $parts[1] : '';
    
    // Remove chunk size indicators if present (chunked transfer encoding)
    $body = preg_replace('/^[0-9a-fA-F]+\r\n/', '', $body);
    $body = preg_replace('/\r\n[0-9a-fA-F]+\r\n/', '', $body);
    $body = trim($body);
    
    $token_data = json_decode($body, true);
    
    // Check for errors from Google
    if (isset($token_data['error'])) {
        $error = $token_data['error'];
        $error_desc = $token_data['error_description'] ?? 'Unknown error';
        
        if ($error === 'invalid_grant') {
            die('The authorization code has expired or was already used. <a href="google-login.php">Please try again</a>.');
        }
        
        die('Google OAuth Error: ' . htmlspecialchars($error) . ' - ' . htmlspecialchars($error_desc));
    }
    
    if (!isset($token_data['access_token'])) {
        die('Access token not found. Response: ' . htmlspecialchars($body));
    }

    $access_token = $token_data['access_token'];

    // 2. Get User Profile using socket connection
    $fp = fsockopen('ssl://www.googleapis.com', 443, $errno, $errstr, 30);
    if (!$fp) {
        die("Socket Error: $errstr ($errno)");
    }

    $request = "GET /oauth2/v2/userinfo HTTP/1.1\r\n";
    $request .= "Host: www.googleapis.com\r\n";
    $request .= "Authorization: Bearer " . $access_token . "\r\n";
    $request .= "Connection: Close\r\n\r\n";

    fwrite($fp, $request);
    
    $response = '';
    while (!feof($fp)) {
        $response .= fgets($fp, 128);
    }
    fclose($fp);

    // Extract body from response (handle chunked encoding)
    $parts = explode("\r\n\r\n", $response, 2);
    $body = isset($parts[1]) ? $parts[1] : '';
    
    // Remove chunk size indicators if present
    $body = preg_replace('/^[0-9a-fA-F]+\r\n/', '', $body);
    $body = preg_replace('/\r\n[0-9a-fA-F]+\r\n/', '', $body);
    $body = trim($body);
    
    if (empty($body)) {
        die('Error fetching user info: empty response');
    }

    $google_user = json_decode($body, true);

    if (isset($google_user['email'])) {
        $email = $google_user['email'];
        $name = $google_user['name'];
        $google_id = $google_user['id'];
        $picture = $google_user['picture'] ?? '';

        // 3. Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // User exists, log them in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Update google_id if not set
            if (empty($user['google_id'])) {
                $update = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $update->execute([$google_id, $user['id']]);
            }
        } else {
            // User doesn't exist, create new account
            $password = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT); // Random password
            
            $insert = $pdo->prepare("INSERT INTO users (name, email, password, google_id, role) VALUES (?, ?, ?, ?, 'customer')");
            $insert->execute([$name, $email, $password, $google_id]);
            
            $new_user_id = $pdo->lastInsertId();
            
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'customer';
        }

        // Redirect to profile or home
        header("Location: my-profile.php");
        exit;

    } else {
        die('Could not retrieve user information from Google.');
    }

} else {
    // No code, redirect back to login
    header("Location: login.php");
    exit;
}
?>
