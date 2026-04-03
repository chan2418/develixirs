<?php
// admin/user_add.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Add User';
$error = '';
$success = '';

// Handle Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    // Optional fields depending on your DB
    $phone = trim($_POST['phone'] ?? ''); 
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, Email and Password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered.';
            } else {
                // Insert
                $hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Attempt to insert. We use common columns. 
                // Adjust if your schema uses different names (e.g. user_email instead of email)
                // Based on users.php detection, 'email' and 'name' seem standard.
                $sql = "INSERT INTO users (name, email, password, phone, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $email, $hash, $phone, $status]);
                
                $success = 'User created successfully!';
                
                // Redirect or clear
                header('Location: users.php');
                exit;
            }
        } catch (PDOException $e) {
            $err = $e->getMessage();
            if (strpos($err, 'Unknown column') !== false) {
                 // Fallback: maybe 'phone' or 'status' column doesn't exist?
                 // Retry with minimal columns
                 try {
                     $sql = "INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())";
                     $stmt = $pdo->prepare($sql);
                     $stmt->execute([$name, $email, $hash]);
                     $success = 'User created successfully (minimal fields)!';
                     header('Location: users.php');
                     exit;
                 } catch (Exception $ex) {
                     $error = 'Database error: ' . $ex->getMessage();
                 }
            } else {
                $error = 'Database error: ' . $err;
            }
        }
    }
}

include __DIR__ . '/layout/header.php';
?>

<div class="max-w-[800px] mx-auto mt-8 px-4">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Add New User</h1>
            <p class="text-sm text-slate-500">Create a new customer account manually.</p>
        </div>
        <a href="users.php" class="text-sm text-indigo-600 hover:underline">← Back to Users</a>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
        <?php if ($error): ?>
            <div class="mb-4 p-3 rounded bg-red-50 text-red-700 text-sm border border-red-200">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="John Doe">
                </div>
                
                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email Address *</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="john@example.com">
                </div>
                
                <!-- Phone -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Phone (Optional)</label>
                    <input type="text" name="phone" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="+91 98765 43210">
                </div>
                
                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Password *</label>
                    <input type="password" name="password" required minlength="6" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="••••••••">
                </div>
            </div>
            
            <div class="mb-6">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="status" value="1" checked class="w-5 h-5 text-indigo-600 border-gray-200 rounded focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-slate-700 font-medium">Active Account</span>
                </label>
            </div>
            
            <div class="flex items-center gap-4">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 shadow-sm transition-colors">
                    Create User
                </button>
                <a href="users.php" class="px-4 py-2.5 text-slate-600 font-medium hover:text-slate-900">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
