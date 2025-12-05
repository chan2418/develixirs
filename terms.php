<?php
session_start();
require_once __DIR__ . '/includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Terms & Conditions - DevElixir Natural Cosmetics</title>
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
    <h1>Terms & Conditions</h1>
    <p>Welcome to DevElixir Natural Cosmetics. By using our website, you agree to the following terms and conditions.</p>
    
    <h2>1. General</h2>
    <p>These terms apply to all visitors, users, and others who access or use the Service.</p>
    
    <h2>2. Products</h2>
    <p>All products are subject to availability. We reserve the right to discontinue any product at any time.</p>
    
    <h2>3. Pricing</h2>
    <p>Prices for our products are subject to change without notice.</p>
    
    <h2>4. Contact Information</h2>
    <p>Questions about the Terms of Service should be sent to us at sales@develixirs.com.</p>
  </div>
  
  <?php include 'footer.php'; ?>
</body>
</html>
