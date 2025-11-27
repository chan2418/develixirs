<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify OTP – Develixirs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body{margin:0;font-family:'Poppins',sans-serif;background:linear-gradient(120deg,#f8ebeb,#ffffff);display:flex;align-items:center;justify-content:center;min-height:100vh;}
    .wrapper{background:#fff;width:400px;padding:40px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.08);text-align:center;}
    h1{color:#d4af37;margin:0 0 8px;font-size:26px;font-weight:600;}
    p{margin:0 0 25px;font-size:14px;color:#777;}
    input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;outline:none;box-sizing:border-box;text-align:center;letter-spacing:4px;}
    button{width:100%;background:#A41B42;color:#fff;border:none;padding:12px;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;transition:0.2s;margin-top:20px;}
    button:hover{background:#3b502c;}
    .error{color:red;font-size:13px;margin-bottom:10px;}
  </style>
</head>
<body>
<div class="wrapper">
  <h1>Verify OTP</h1>
  <p>Enter the 6-digit code sent to <?php echo htmlspecialchars($_GET['email'] ?? ''); ?></p>
  
  <?php if (isset($_GET['error'])): ?>
    <div class="error"><?php echo htmlspecialchars($_GET['error']); ?></div>
  <?php endif; ?>

  <form action="process_verify_otp.php" method="post">
    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
    <input type="text" name="otp" placeholder="000000" maxlength="6" required>
    <button type="submit">Verify</button>
  </form>
</div>
</body>
</html>
