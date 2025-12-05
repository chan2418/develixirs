<?php
// Check what's happening with the dashboard
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Dashboard Access Debug</h1>";

session_start();

echo "<h2>1. Session Check</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    echo "✅ Admin session is active<br>";
    echo "Admin ID: " . ($_SESSION['admin_id'] ?? 'not set') . "<br>";
    echo "Admin Name: " . ($_SESSION['admin_name'] ?? 'not set') . "<br>";
} else {
    echo "❌ NOT logged in!<br>";
    echo "<p><strong>You need to login first:</strong></p>";
    echo "<a href='login.php'>Go to Admin Login</a><br><br>";
}

echo "<h2>2. Dashboard File Check</h2>";
if (file_exists('dashboard.php')) {
    echo "✅ dashboard.php exists<br>";
    
    // Check first few lines
    $content = file_get_contents('dashboard.php');
    if (strpos($content, 'session_start') !== false) {
        echo "✅ Session start found in dashboard<br>";
    }
    if (strpos($content, 'admin_logged') !== false) {
        echo "✅ Auth check found in dashboard<br>";
    }
} else {
    echo "❌ dashboard.php not found!<br>";
}

echo "<h2>3. What to do:</h2>";
echo "<ol>";
echo "<li>Make sure you logged in at <a href='login.php'>login.php</a></li>";
echo "<li>If you see 'NOT logged in' above, login first</li>";
echo "<li>If you see 'Admin session is active', the dashboard should work</li>";
echo "</ol>";
?>
