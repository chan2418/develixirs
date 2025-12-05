<?php
session_start();
require_once __DIR__ . '/includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About Us - DevElixir Natural Cosmetics</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  <style>
    body { font-family: 'Poppins', sans-serif; background: #f9f9f9; color: #333; margin: 0; }
    .page-container { max-width: 1000px; margin: 40px auto; padding: 40px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    h1 { color: #1a1a1a; margin-bottom: 20px; }
    p { line-height: 1.8; margin-bottom: 20px; color: #555; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/navbar.php'; ?>
  
  <div class="page-container">
    <h1>About DevElixir</h1>
    <p>Welcome to DevElixir Natural Cosmetics, where nature meets science to bring you the purest skincare solutions.</p>
    <p>Our mission is to provide high-quality, natural products that are safe for you and your family. We believe in the power of herbal ingredients to nurture and rejuvenate your skin.</p>
    <p>Founded in Coimbatore, we are dedicated to sustainability and ethical sourcing. Every product is crafted with care, ensuring that you receive only the best that nature has to offer.</p>
  </div>
  
  <?php include 'footer.php'; ?>
</body>
</html>
