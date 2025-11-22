<?php
// admin/login.php
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Admin Login - Develixirs</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="/assets/css/style.css" />
  <style>
    /* quick centered login styles, replace with your CSS */
    body { font-family: Arial, sans-serif; background:#f4f6f8; }
    .login-wrap { max-width:420px; margin:60px auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.08) }
    .logo { text-align:center; margin-bottom:18px; }
    .logo img{ max-width:220px; height:auto; }
    .form-group { margin-bottom:12px; }
    input[type="email"], input[type="password"] { width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; }
    button { width:100%; padding:10px; border:none; background:#0066cc; color:#fff; border-radius:4px; font-weight:600 }
    .note { margin-top:12px; font-size:13px; color:#666; text-align:center; }
    .error { color:#b00020; margin-bottom:12px; text-align:center; }
  </style>
</head>
<body>
  <div class="login-wrap">
    <div class="logo">
      <!-- adjust path if you named the file differently -->
      <img src="/assets/images/deevelixir-logo-1-copy-e1679708810866.png" alt="Develixirs Logo">
    </div>

    <?php if(isset($_GET['err'])): ?>
      <div class="error">Invalid email or password</div>
    <?php endif; ?>

    <form action="authenticate.php" method="post" autocomplete="off" novalidate>
      <div class="form-group">
        <label for="email">Admin Email</label>
        <input id="email" name="email" type="email" required placeholder="you@company.com">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required placeholder="Your password">
      </div>

      <!-- Simple CSRF token -->
      <?php
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
      ?>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

      <button type="submit">Log in</button>
    </form>

    <div class="note">Developed by Develixirs | Secure admin access</div>
  </div>
</body>
</html>
