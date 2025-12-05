<?php
session_start();
require_once __DIR__ . '/includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Privacy Policy - DevElixir Natural Cosmetics</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  <style>
    body { font-family: 'Poppins', sans-serif; background: #f9f9f9; color: #333; margin: 0; }
    .page-container { max-width: 1000px; margin: 40px auto; padding: 40px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    h1 { color: #1a1a1a; margin-bottom: 20px; }
    h2 { font-size: 18px; margin-top: 30px; margin-bottom: 15px; color: #444; }
    p { line-height: 1.8; margin-bottom: 15px; color: #555; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/navbar.php'; ?>
  
  <div class="page-container">
    <h1>Privacy Policy</h1>
    <p>Your privacy is important to us. It is DevElixir's policy to respect your privacy regarding any information we may collect from you across our website.</p>
    
    <h2>1. Information We Collect</h2>
    <p>We only ask for personal information when we truly need it to provide a service to you. We collect it by fair and lawful means, with your knowledge and consent.</p>
    
    <h2>2. How We Use Information</h2>
    <p>We use the information we collect to operate and maintain our website, send you newsletters, and respond to your comments or inquiries.</p>
    
    <h2>3. Security</h2>
    <p>We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it.</p>
  </div>
  
  <?php include 'footer.php'; ?>
</body>
</html>
