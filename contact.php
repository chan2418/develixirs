<?php
session_start();
require_once __DIR__ . '/includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us – Devilixirs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    body{
      font-family:"Poppins",sans-serif;
      background:#f7f7f7;
      margin:0;
    }

    .contact-wrapper{
      max-width:900px;
      margin:50px auto;
      padding:20px;
      background:#fff;
      border-radius:8px;
      border:1px solid #e3e3e3;
    }

    h1{
      font-size:28px;
      margin-bottom:12px;
    }

    p{
      color:#555;
      margin-bottom:25px;
    }

    .contact-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:30px;
    }

    input, textarea{
      width:100%;
      padding:12px 14px;
      border:1px solid #ddd;
      border-radius:6px;
      font-size:14px;
      margin-bottom:18px;
    }

    textarea{
      height:140px;
      resize:none;
    }

    button{
      width:100%;
      padding:12px;
      background:#111;
      border:none;
      color:#fff;
      font-size:15px;
      border-radius:6px;
      cursor:pointer;
    }

    .info-box{
      background:#fafafa;
      padding:20px;
      border-radius:8px;
      border:1px solid #eee;
    }

    .info-box div{
      margin-bottom:15px;
    }

    .info-box i{
      margin-right:8px;
      color:#D4AF37;
    }

    @media (max-width:768px){
      .contact-grid{
        grid-template-columns:1fr;
      }
    }
  </style>
</head>

<body>

<?php include __DIR__ . '/navbar.php'; ?>

<div class="contact-wrapper">
  <h1>Contact Us</h1>
  <p>Have any questions? Our team will get back to you shortly.</p>

  <div class="contact-grid">

    <!-- CONTACT FORM -->
    <form method="POST" action="send_contact.php">
      <input type="text" name="name" placeholder="Your Name" required>
      <input type="email" name="email" placeholder="Email Address" required>
      <input type="text" name="subject" placeholder="Subject" required>
      <textarea name="message" placeholder="Your message..." required></textarea>

      <button type="submit">Send Message</button>
    </form>

    <!-- CONTACT DETAILS -->
    <div class="info-box">
      <div><i class="fa-solid fa-phone"></i> +91 9363076177</div>
      <div><i class="fa-solid fa-envelope"></i> chandrusri247@gmail.com</div>
      <div><i class="fa-solid fa-location-dot"></i> Arasamaram, Pichanoorpet, Gudiyattham</div>
      
      <!-- Google Map -->
      <div style="margin-top: 20px; border-radius: 8px; overflow: hidden;">
        <iframe src="https://maps.google.com/maps?q=Arasamaram%2C+Pichanoorpet%2C+Gudiyattham&t=&z=15&ie=UTF8&iwloc=&output=embed" width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </div>

  </div>
</div>

</body>
</html>
