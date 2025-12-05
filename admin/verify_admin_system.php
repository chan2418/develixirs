<?php
// COMPREHENSIVE ADMIN SYSTEM VERIFICATION
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head>";
echo "<title>Admin System Verification</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f5f5f5}";
echo ".section{background:white;padding:20px;margin:15px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}";
echo "h1{color:#333}h2{color:#0066cc;border-bottom:2px solid #0066cc;padding-bottom:10px}";
echo ".success{color:#00aa00;font-weight:bold}.error{color:#dd0000;font-weight:bold}.warn{color:#ff8800;font-weight:bold}";
echo ".file-list{margin:10px 0;padding:10px;background:#f9f9f9;border-left:4px solid #0066cc}";
echo ".btn{display:inline-block;padding:12px 24px;background:#0066cc;color:white;text-decoration:none;border-radius:6px;margin:10px 5px 0 0;font-weight:bold}";
echo ".btn:hover{background:#0052a3}</style></head><body>";

echo "<h1>🔍 Admin System Verification</h1>";

$allGood = true;

// ========== 1. SESSION CHECK ==========
echo "<div class='section'>";
echo "<h2>1. Session Status</h2>";
session_start();

if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    echo "<p class='success'>✅ LOGGED IN as Admin</p>";
    echo "<p>Admin ID: " . ($_SESSION['admin_id'] ?? 'not set') . "</p>";
    echo "<p>Admin Name: " . ($_SESSION['admin_name'] ?? 'not set') . "</p>";
    echo "<p><a href='dashboard.php' class='btn'>Go to Dashboard</a></p>";
} else {
    echo "<p class='error'>❌ NOT LOGGED IN</p>";
    echo "<p>You need to login first to access the admin dashboard.</p>";
    echo "<p><a href='login.php' class='btn'>Login Now</a></p>";
    $allGood = false;
}
echo "</div>";

// ========== 2. REQUIRED FILES CHECK ==========
echo "<div class='section'>";
echo "<h2>2. Required Admin Files</h2>";

$requiredFiles = [
    '_auth.php' => 'Authentication guard',
    'login.php' => 'Login page',
    'authenticate.php' => 'Login processor',
    'dashboard.php' => 'Dashboard',
    'layout/header.php' => 'Header layout',
    'layout/sidebar.php' => 'Sidebar layout',
    'layout/footer.php' => 'Footer layout',
];

$missingFiles = [];
foreach ($requiredFiles as $file => $desc) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ $file - $desc</p>";
    } else {
        echo "<p class='error'>❌ $file - $desc (MISSING!)</p>";
        $missingFiles[] = $file;
        $allGood = false;
    }
}

if (!empty($missingFiles)) {
    echo "<div class='file-list'>";
    echo "<p><strong>Missing files - Please upload:</strong></p>";
    foreach ($missingFiles as $f) {
        echo "• admin/$f<br>";
    }
    echo "</div>";
}
echo "</div>";

// ========== 3. DATABASE CHECK ==========
echo "<div class='section'>";
echo "<h2>3. Database Connection</h2>";

try {
    require_once '../includes/db.php';
    echo "<p class='success'>✅ Database connected</p>";
    
    // Check admin user
    $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p class='success'>✅ Admin user exists: " . htmlspecialchars($admin['email']) . "</p>";
    } else {
        echo "<p class='error'>❌ No admin user found!</p>";
        echo "<p>Run this SQL in phpMyAdmin to create admin:</p>";
        echo "<div class='file-list'><pre>INSERT INTO users (name, email, password, role, is_verified, created_at) 
VALUES ('Admin', 'admin@develixirs.com', '\$2y\$10\$G1CpoOSRYNF..xF6CrxdV.ziPcEztmgzh0GxgxG4viQ2aoeP1JQA2', 'admin', 1, NOW());</pre></div>";
        $allGood = false;
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database Error: " . $e->getMessage() . "</p>";
    $allGood = false;
}
echo "</div>";

// ========== 4. LOGIN TEST ==========
echo "<div class='section'>";
echo "<h2>4. Login Credentials Test</h2>";

if (isset($pdo) && isset($admin)) {
    $testPassword = 'admin@123';
    
    if (password_verify($testPassword, $admin['password'] ?? '')) {
        echo "<p class='success'>✅ Password 'admin@123' is CORRECT</p>";
        echo "<p>You can login with: <strong>admin@develixirs.com / admin@123</strong></p>";
    } else {
        echo "<p class='error'>❌ Password verification failed</p>";
        echo "<p>The password hash in database doesn't match 'admin@123'</p>";
        $allGood = false;
    }
}
echo "</div>";

// ========== 5. FINAL STATUS ==========
echo "<div class='section'>";
echo "<h2>5. Overall Status</h2>";

if ($allGood && isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    echo "<p class='success' style='font-size:20px'>✅ ALL SYSTEMS OPERATIONAL!</p>";
    echo "<p>Your admin system is fully configured and you're logged in.</p>";
    echo "<p><a href='dashboard.php' class='btn'>Access Dashboard</a></p>";
} elseif ($allGood) {
    echo "<p class='warn' style='font-size:20px'>⚠️ SYSTEM READY - LOGIN REQUIRED</p>";
    echo "<p>All files and configuration are correct. You just need to login.</p>";
    echo "<p><a href='login.php' class='btn'>Login to Admin</a></p>";
} else {
    echo "<p class='error' style='font-size:20px'>❌ ISSUES DETECTED</p>";
    echo "<p>Please fix the issues marked above before accessing the dashboard.</p>";
}

echo "</div>";

echo "<div class='section' style='background:#fff3cd;border-left:4px solid #ff8800'>";
echo "<p><strong>⚠️ Security Reminder:</strong> Delete this file (verify_admin_system.php) after verification!</p>";
echo "</div>";

echo "</body></html>";
?>
