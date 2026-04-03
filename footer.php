  <style>
    /* FOOTER STYLES */
    .footer{
      background:#111;
      color:#e0e0e0;
      padding:60px 0 20px;
      margin-top:60px;
      font-family: 'Poppins', sans-serif;
    }
    .footer-inner{
      max-width:1200px;
      margin:0 auto;
      padding:0 15px 30px;
      display:grid;
      /* Adjusted for variable columns */
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap:20px;
    }
    .footer-logo{
      display:inline-block;
      margin-bottom:8px;
    }
    .footer-about{
      font-size:14px;
      line-height:1.6;
      margin-bottom:20px;
      color:#ccc;
    }
    .footer-contact-item{
      display:flex;
      align-items:flex-start;
      gap:10px;
      font-size:12px;
      margin-bottom:8px;
    }
    .footer-contact-item i{
      width:22px;
      height:22px;
      border-radius:2px;
      background:#1a241d;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:11px;
      margin-top:2px;
    }
    .footer-title{
      font-size:16px;
      font-weight: 600;
      text-transform:uppercase;
      margin-bottom:20px;
      color: #fff;
      letter-spacing: 0.05em;
      position: relative;
      padding-bottom: 10px;
    }
    .footer-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 40px;
        height: 2px;
        background: #D4AF37; /* Gold color */
    }
    .footer-links{
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .footer-links li{
      font-size:14px;
      margin-bottom:10px;
      color:#ccc;
      cursor:pointer;
      transition: all 0.3s ease;
    }
    .footer-links li:hover{
      color:#fff;
      padding-left: 5px;
    }
    .footer-links li a:hover{
      color:#D4AF37 !important;
    }
    .footer-links li::before{
      content:'\f105';
      font-family:'Font Awesome 6 Free';
      font-weight:900;
      margin-right:6px;
      font-size:10px;
    }
    .footer-gallery{
      display:grid;
      grid-template-columns:repeat(3,1fr);
      gap:6px;
    }
    .footer-gallery img{
      width:100%;
      height:60px;
      object-fit:cover;
      border-radius:4px;
    }
    .footer-social{
      display:flex;
      gap:10px;
      margin-top:15px;
    }
    .footer-social a{
      width:32px;
      height:32px;
      border-radius:50%;
      background:#222;
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:14px;
      transition:0.3s;
    }
    .footer-social a:hover{
      background:#D4AF37;
      color:#fff;
    }
    .footer-bottom{
      border-top:1px solid #222c24;
      margin-top:10px;
      padding:12px 15px 16px;
      max-width:1200px;
      margin-left:auto;
      margin-right:auto;
      display:flex;
      align-items:center;
      justify-content:space-between;
      font-size:11px;
      color:#bfbfbf;
    }
    .footer-payments{
      display:flex;
      gap:8px;
      font-size:11px;
    }
    .footer-payments span{
      padding:4px 7px;
      border-radius:2px;
      background:#1b241d;
    }

    /* BACK TO TOP */
    .back-top{
      position:fixed;
      right:25px;
      bottom:80px; /* Adjusted to be above mobile nav if present */
      width:42px;
      height:42px;
      border-radius:50%;
      background:#A41B42;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-size:18px;
      cursor:pointer;
      box-shadow:0 3px 12px rgba(0,0,0,.25);
      z-index:999;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    .back-top.visible{
      opacity: 1;
      visibility: visible;
    }

    /* MOBILE BOTTOM NAV */
    .mobile-bottom-nav{
      display:none;
      position:fixed;
      bottom:0;
      left:0;
      width:100%;
      background:#fff;
      border-top:1px solid #eee;
      padding:10px 0;
      z-index:1000;
      justify-content:space-around;
      box-shadow:0 -2px 10px rgba(0,0,0,0.05);
    }
    .mobile-bottom-nav a{
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:4px;
      color:#666;
      font-size:10px;
      text-decoration:none;
    }
    .mobile-bottom-nav a i{
      font-size:18px;
    }
    .mobile-bottom-nav a:hover, .mobile-bottom-nav a.active{
      color:#A41B42;
    }

    /* RESPONSIVE */
    @media (max-width:992px){
      .footer-inner{
        grid-template-columns:repeat(3,minmax(0,1fr));
      }
    }
    @media (max-width:768px){
      .footer-inner{
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
      .mobile-bottom-nav{
        display:flex;
      }
      .footer{
        padding-bottom:80px; /* Space for bottom nav */
      }
    }
    @media (max-width:600px){
      .footer-inner{
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* Amazon-style 2 cols */
        text-align: left;
        gap: 30px 15px; /* Vertical gap, Horizontal gap */
      }
      
      /* Make Logo/About section full width */
      .footer-inner > div:first-child {
        grid-column: 1 / -1;
        text-align: left;
      }
      
      /* Make Gallery full width if present */
      .footer-inner > div:last-child {
        grid-column: 1 / -1;
      }

      .footer-social {
          justify-content: flex-start; /* Left align social */
      }
      
      .footer-title {
          font-size: 15px; /* Readable size */
      }
      
      .footer-title::after {
          left: 0;
          transform: none; /* Left align underline */
      }
      
      /* Ensure links don't have excessive padding */
      .footer-links li:hover {
          padding-left: 0; /* Optional: Remove hover shift for cleaner mobile feel */
      }

      .footer-bottom{
        flex-direction:column;
        gap:15px;
        text-align:center;
      }
    }
    /* SUBSCRIBE SECTION STYLES */
    .subscribe-section {
      background: #C5E1C5; /* Light Green */
      padding: 50px 0;
      margin-top: 60px;
      position: relative;
      overflow: hidden;
    }
    .subscribe-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 40px;
      position: relative;
      z-index: 2;
    }
    .subscribe-content {
      flex: 1;
      max-width: 600px;
    }
    .subscribe-title {
      font-size: 28px;
      font-weight: 700;
      color: #1a1a1a;
      margin-bottom: 15px;
      line-height: 1.3;
      font-family: 'Poppins', sans-serif;
    }
    .subscribe-subtitle {
      font-size: 14px;
      color: #4a6b4a;
      margin-bottom: 25px;
      font-weight: 500;
    }
    .subscribe-form {
      display: flex;
      background: #fff;
      padding: 5px;
      border-radius: 999px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
      max-width: 450px;
    }
    .subscribe-input {
      border: none;
      outline: none;
      padding: 12px 20px;
      border-radius: 999px;
      flex: 1;
      font-size: 14px;
      color: #333;
    }
    .subscribe-btn {
      background: #014D40;
      color: #fff;
      border: none;
      padding: 10px 28px;
      border-radius: 999px;
      font-weight: 600;
      cursor: pointer;
      font-size: 13px;
      transition: background 0.2s;
    }
    .subscribe-btn:hover {
      background: #0b6b5a;
    }
    .subscribe-image-wrapper {
      flex: 1;
      display: flex;
      justify-content: flex-end;
      align-items: flex-end;
      position: relative;
    }
    .subscribe-image-wrapper img {
      max-height: 350px;
      width: auto;
      object-fit: contain;
      margin-bottom: -50px;
    }
    @media (max-width: 768px) {
      .subscribe-section {
        padding: 40px 0 0;
      }
      .subscribe-container {
        flex-direction: column;
        text-align: center;
        gap: 30px;
      }
      .subscribe-content {
        max-width: 100%;
      }
      .subscribe-form {
        margin: 0 auto;
      }
      .subscribe-image-wrapper {
        justify-content: center;
        width: 100%;
      }
      .subscribe-image-wrapper img {
        max-height: 250px;
        width: 100%;
        max-width: 300px;
        margin: 0 auto;
        display: block;
      }
    }
</style>

<?php
// Fetch Footer Settings
$fSettings = [];
try {
    $stmtFS = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'footer_settings'");
    $stmtFS->execute();
    $fsRow = $stmtFS->fetch(PDO::FETCH_ASSOC);
    if ($fsRow) {
        $fSettings = json_decode($fsRow['setting_value'], true) ?: [];
    }
    
    // Fetch General Settings (Subscribe Image & Chatbot)
    $chatEnabled = '1'; // Default to Enabled so user sees it
    $chatTitle = 'Customer Support';
    $chatWelcome = 'Hi! How can we help you today?';
    $chatWhatsapp = '';
    $subscribeImage = 'assets/images/category-placeholder.jpg';

    $stmtAll = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while($row = $stmtAll->fetch(PDO::FETCH_ASSOC)){
        if($row['setting_key'] == 'subscribe_image') $subscribeImage = $row['setting_value'];
        if($row['setting_key'] == 'subscribe_title') $subscribeTitle = $row['setting_value'];
        if($row['setting_key'] == 'subscribe_subtitle') $subscribeSubtitle = $row['setting_value'];
        if($row['setting_key'] == 'chatbot_enabled') $chatEnabled = $row['setting_value'];
        if($row['setting_key'] == 'chatbot_title') $chatTitle = $row['setting_value'];
        if($row['setting_key'] == 'chatbot_welcome_msg') $chatWelcome = $row['setting_value'];
        if($row['setting_key'] == 'chatbot_whatsapp_number') $chatWhatsapp = $row['setting_value'];
    }
    
    // Defaults if empty
    if(empty($subscribeTitle)) $subscribeTitle = 'Stay home & get your daily <br>needs from our shop';
    if(empty($subscribeSubtitle)) $subscribeSubtitle = 'Start Your Daily Shopping with Herbal Ecom';

} catch (Exception $e) {}

// ... existing footer logic ...
// (We just added the styles and fetch above)

// Link Columns (Dynamic) - Migration Logic
$linkColumns = $fSettings['link_columns'] ?? [];
if (empty($linkColumns)) {
    // Attempt to migrate old keys if they exist
    if (!empty($fSettings['col2'])) $linkColumns[] = $fSettings['col2'];
    if (!empty($fSettings['col3'])) $linkColumns[] = $fSettings['col3'];
    if (!empty($fSettings['col4'])) $linkColumns[] = $fSettings['col4'];
    else if (!empty($fSettings['col_extra'])) $linkColumns[] = $fSettings['col_extra'];
    if (!empty($fSettings['col5'])) $linkColumns[] = $fSettings['col5'];
    if (!empty($fSettings['col6'])) $linkColumns[] = $fSettings['col6'];

    // Fallback default
    if (empty($linkColumns)) {
        $linkColumns = [
            ['title' => 'Informations', 'links' => [
                 ['label' => 'About Devilixirs', 'url' => 'about.php'],
                 ['label' => 'Best Sellers', 'url' => 'product.php?sort=best'],
                 ['label' => 'Ayurvedh Blog', 'url' => 'ayurvedha_blog.php']
            ]],
            ['title' => 'Links', 'links' => [
                 ['label' => 'Shop All', 'url' => 'product.php'],
                 ['label' => 'Terms', 'url' => 'terms.php']
            ]]
        ];
    }
}

// Always expose a dedicated Ayurvedh blog link in footer columns.
$hasAyurvedhaFooterLink = false;
foreach ($linkColumns as $column) {
    foreach (($column['links'] ?? []) as $link) {
        $urlValue = strtolower(trim((string)($link['url'] ?? '')));
        if ($urlValue === 'ayurvedha_blog.php' || strpos($urlValue, 'ayurvedha_blog.php') !== false) {
            $hasAyurvedhaFooterLink = true;
            break 2;
        }
    }
}
if (!$hasAyurvedhaFooterLink) {
    if (empty($linkColumns)) {
        $linkColumns[] = [
            'title' => 'Resources',
            'links' => [
                ['label' => 'Ayurvedh Blog', 'url' => 'ayurvedha_blog.php']
            ]
        ];
    } else {
        if (!isset($linkColumns[0]['links']) || !is_array($linkColumns[0]['links'])) {
            $linkColumns[0]['links'] = [];
        }
        $linkColumns[0]['links'][] = ['label' => 'Ayurvedh Blog', 'url' => 'ayurvedha_blog.php'];
    }
}


$contact = $fSettings['contact'] ?? [
    'address_line1' => 'DevElixir Natural Cosmetics ™',
    'address_line2' => 'No:6, 3rd Cross Street,',
    'address_line3' => 'Kamatchiamman Garden, Sethukkarai,',
    'address_city' => 'Gudiyatham-632602, Vellore, Tamilnadu',
    'address_country' => 'INDIA',
    'email' => 'sales@develixirs.com',
    'phone' => '+91 95006 50454'
];

$about = $fSettings['about'] ?? [
    'description' => 'DevElixir Natural Cosmetics - Pure, natural, and effective skincare solutions for you and your family.',
    'social_fb' => '#',
    'social_tw' => '#',
    'social_insta' => '#',
    'social_pin' => '#'
];

$gallery = $fSettings['gallery'] ?? [
    'title' => 'Gallery',
    'images' => [
        'assets/uploads/products/1167485b8dbb.jpg',
        'assets/uploads/products/c28997524100.jpg',
        'assets/uploads/products/84b062f7d8d2.jpg',
        'assets/uploads/products/fb15b8e998ea.jpg',
        'assets/uploads/products/8e2202201f76.jpg',
        'assets/uploads/products/459a32ced2ab.jpg'
    ]
];
?>

<!-- SUBSCRIBE SECTION -->
<section class="subscribe-section">
<div class="subscribe-container">
  <div class="subscribe-content">
    <h2 class="subscribe-title"><?= html_entity_decode($subscribeTitle) // Allow <br> tag ?></h2>
    <p class="subscribe-subtitle"><?= htmlspecialchars($subscribeSubtitle) ?></p>
    
    <form class="subscribe-form" onsubmit="event.preventDefault();">
        <div style="display:flex; align-items:center; padding-left:15px;">
          <i class="fa-regular fa-envelope" style="color:#aaa;"></i>
        </div>
        <input type="email" class="subscribe-input" placeholder="Enter Your Email" required>
        <button type="submit" class="subscribe-btn">Subscribe</button>
    </form>
  </div>
  
  <div class="subscribe-image-wrapper">
      <img src="<?= htmlspecialchars($subscribeImage); ?>" alt="Subscribe Banner" onerror="this.onerror=null; this.src='assets/images/category-placeholder.jpg'; this.style.opacity='0.7';">
  </div>
</div>
</section>

<!-- FOOTER -->
  <footer class="footer">
    <div class="footer-inner">
      <div>
        <a href="index.php" class="footer-logo">
          <img src="develixir-logo.png" alt="DevElixir Logo" style="height: 50px; margin-bottom: 15px;">
        </a>
        <p><?= nl2br(htmlspecialchars($about['description'])) ?></p>
        <div class="footer-social">
          <?php if(!empty($about['social_fb'])): ?><a href="<?= htmlspecialchars($about['social_fb']) ?>" target="_blank"><i class="fa-brands fa-facebook-f"></i></a><?php endif; ?>
          <?php if(!empty($about['social_tw'])): ?><a href="<?= htmlspecialchars($about['social_tw']) ?>" target="_blank"><i class="fa-brands fa-twitter"></i></a><?php endif; ?>
          <?php if(!empty($about['social_insta'])): ?><a href="<?= htmlspecialchars($about['social_insta']) ?>" target="_blank"><i class="fa-brands fa-instagram"></i></a><?php endif; ?>
          <?php if(!empty($about['social_pin'])): ?><a href="<?= htmlspecialchars($about['social_pin']) ?>" target="_blank"><i class="fa-brands fa-pinterest"></i></a><?php endif; ?>
        </div>
      </div>

      <!-- Dynamic Link Columns -->
      <?php foreach($linkColumns as $col): 
          if(!empty($col['links'])):
      ?>
      <div>
        <h4 class="footer-title"><?= htmlspecialchars($col['title']) ?></h4>
        <ul class="footer-links">
          <?php foreach($col['links'] as $link): ?>
            <li><a href="<?= htmlspecialchars($link['url']) ?>" style="color:inherit; text-decoration:none;"><?= htmlspecialchars($link['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php 
          endif; 
      endforeach; 
      ?>

      <div>
        <h4 class="footer-title">Contact Us</h4>
        <ul class="footer-links">
          <?php if(!empty($contact['address_line1'])): ?><li><strong><?= htmlspecialchars($contact['address_line1']) ?></strong></li><?php endif; ?>
          <?php if(!empty($contact['address_line2'])): ?><li><?= htmlspecialchars($contact['address_line2']) ?></li><?php endif; ?>
          <?php if(!empty($contact['address_line3'])): ?><li><?= htmlspecialchars($contact['address_line3']) ?></li><?php endif; ?>
          <?php if(!empty($contact['address_city'])): ?><li><?= htmlspecialchars($contact['address_city']) ?></li><?php endif; ?>
          <?php if(!empty($contact['address_country'])): ?><li><?= htmlspecialchars($contact['address_country']) ?></li><?php endif; ?>
          <?php if(!empty($contact['email'])): ?><li>Email: <?= htmlspecialchars($contact['email']) ?></li><?php endif; ?>
          <?php if(!empty($contact['phone'])): ?><li>Phone: <?= htmlspecialchars($contact['phone']) ?></li><?php endif; ?>
        </ul>
      </div>

      <div>
        <h4 class="footer-title"><?= htmlspecialchars($gallery['title']) ?></h4>
        <div class="footer-gallery">
          <?php foreach(($gallery['images'] ?? []) as $imgUrl): 
              if(!empty($imgUrl)):
          ?>
            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Gallery">
          <?php 
              endif;
          endforeach; ?>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <span>Copyright © 2025 <strong>DevElixir Natural Cosmetics</strong>. All Rights Reserved.</span>
      <div class="footer-payments">
        <span>UPI</span>
        <span>Rupay</span>
        <span>MasterCard</span>
        <span>Visa</span>
      </div>
    </div>
  </footer>



  <!-- MOBILE BOTTOM NAV -->
  <nav class="mobile-bottom-nav">
    <a href="index.php">
      <i class="fa-solid fa-house"></i>
      <span>Home</span>
    </a>
    <a href="product.php">
      <i class="fa-solid fa-store"></i>
      <span>Products</span>
    </a>
    <a href="cart.php">
      <i class="fa-solid fa-cart-shopping"></i>
      <span>Cart</span>
    </a>
    <a href="my-profile.php?tab=wishlist">
      <i class="fa-regular fa-heart"></i>
      <span>Wishlist</span>
    </a>
  </nav>

  <script>
    // Back to top removed
  </script>
  <script src="assets/js/navbar.js"></script>

<?php if($chatEnabled == '1'): ?>
<!-- GLOBAL CHATBOT WIDGET -->
<style>
    .chatbot-widget {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 10000;
        font-family: 'Outfit', 'Poppins', sans-serif;
    }
    .chatbot-toggle {
        width: 65px;
        height: 65px;
        background: linear-gradient(135deg, #D4AF37, #C5A028);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 30px;
        cursor: pointer;
        box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .chatbot-toggle:hover {
        transform: scale(1.1) rotate(-10deg);
        box-shadow: 0 12px 30px rgba(212, 175, 55, 0.5);
    }
    
    .chatbot-window {
        position: absolute;
        bottom: 90px;
        right: 0;
        width: 380px;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px) scale(0.95);
        transform-origin: bottom right;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
    }
    .chatbot-window.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }
    
    .chatbot-header {
        background: rgba(212, 175, 55, 0.95); /* Glass-like fallbacks */
        background: linear-gradient(135deg, #D4AF37, #F2D06B);
        color: #fff;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .chatbot-title {
        font-size: 16px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: 0.5px;
    }
    .bot-status {
        width: 8px;
        height: 8px;
        background: #25D366;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 5px #25D366;
    }

    .chatbot-body {
        padding: 20px;
        background: #f4f6f8;
        height: 350px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
        scroll-behavior: smooth;
    }
    .chatbot-body::-webkit-scrollbar { width: 4px; }
    .chatbot-body::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
    
    /* Bubbles */
    .bot-msg, .user-msg {
        max-width: 85%;
        padding: 12px 16px;
        font-size: 14px;
        line-height: 1.5;
        position: relative;
        word-wrap: break-word;
    }
    .bot-msg {
        background: #fff;
        border-radius: 18px 18px 18px 0;
        color: #333;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        align-self: flex-start;
        margin-right: auto; /* Force Left */
        border-bottom-left-radius: 4px;
    }
    .user-msg {
        background: #D4AF37;
        color: #fff;
        border-radius: 18px 18px 0 18px;
        align-self: flex-end;
        margin-left: auto; /* Force Right */
        box-shadow: 0 2px 8px rgba(212, 175, 55, 0.3);
        border-bottom-right-radius: 4px;
        text-align: right; /* Text align right inside bubble? optional, mostly left is better for reading. Let's keep default or left. */
        /* Actually keep text-align left for multi-line readability usually, or right if short. Let's start with default (left) styling but bubble on right. */
    }
    
    /* Product Carousel in Chat */
    .chat-products-carousel {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 5px;
        max-width: 100%;
        align-self: flex-start;
    }
    .chat-products-carousel::-webkit-scrollbar { height: 4px; }
    .chat-products-carousel::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }

    .chat-product-card {
        flex: 0 0 140px;
        background: #fff;
        border-radius: 10px;
        padding: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        text-align: center;
        transition: transform 0.2s;
        border: 1px solid #f0f0f0;
    }
    .chat-product-card:hover { transform: translateY(-3px); }
    .chat-product-card img {
        width: 100%;
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
        margin-bottom: 6px;
    }
    .chat-product-card h5 {
        font-size: 11px;
        margin: 0 0 4px;
        color: #333;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        line-height: 1.3;
        height: 28px;
    }
    .chat-product-card .price {
        font-size: 12px;
        font-weight: 700;
        color: #D4AF37;
        margin-bottom: 6px;
        display: block;
    }
    .chat-product-card .view-btn {
        display: block;
        background: #1a1a1a;
        color: #fff;
        font-size: 10px;
        padding: 4px;
        border-radius: 4px;
        text-decoration: none;
    }
    
    /* Footer & Input */
    .chatbot-footer {
        padding: 12px 15px;
        background: #fff;
        border-top: 1px solid #f0f0f0;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .chat-input {
        flex: 1;
        border: 1px solid #eee;
        background: #f9f9f9;
        border-radius: 25px;
        padding: 10px 18px;
        font-size: 13px;
        outline: none;
        transition: all 0.2s;
    }
    .chat-input:focus {
        background: #fff;
        border-color: #D4AF37;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
    }
    .chat-send {
        background: #D4AF37;
        color: #fff;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: transform 0.2s;
        box-shadow: 0 2px 8px rgba(212, 175, 55, 0.3);
    }
    .chat-send:hover { transform: scale(1.1); }
    
    /* Typing Indicator */
    .typing-indicator {
        background: transparent;
        padding: 10px 16px;
        display: none;
        align-self: flex-start;
    }
    .typing-dots {
        display: flex;
        gap: 4px;
    }
    .typing-dots span {
        width: 6px;
        height: 6px;
        background: #ccc;
        border-radius: 50%;
        animation: typing 1.4s infinite ease-in-out both;
    }
    .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
    .typing-dots span:nth-child(2) { animation-delay: -0.16s; }
    
    @keyframes typing {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }
    
    @media (max-width: 480px) {
        .chatbot-window {
            width: 320px;
            right: 0px; 
            bottom: 85px;
        }
        .chatbot-widget {
            right: 20px;
            bottom: 90px; 
            z-index: 20000;
        }
    }
</style>

<div class="chatbot-widget">
    <div class="chatbot-window" id="chatWindow">
        <div class="chatbot-header">
            <div class="chatbot-title">
                <i class="fa-solid fa-wand-magic-sparkles"></i> <?= htmlspecialchars($chatTitle) ?> <span class="bot-status"></span>
            </div>
            <div class="chatbot-close" onclick="toggleChat()">
                <i class="fa-solid fa-chevron-down"></i>
            </div>
        </div>
        <div class="chatbot-body" id="chatBody">
            <div class="bot-msg">
                <?= nl2br(htmlspecialchars($chatWelcome)) ?>
            </div>
            
            <!-- Typing Indicator -->
            <div class="typing-indicator" id="typingIndicator">
                <div class="typing-dots">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>

    <div class="chatbot-footer">
        <input type="text" id="chatInput" class="chat-input" placeholder="Type a message..." onkeypress="handleEnter(event)">
        <button class="chat-send" onclick="sendMessage()">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
    </div>
    
    <div class="chatbot-toggle" onclick="toggleChat()">
        <i class="fa-regular fa-comments" id="chatIcon"></i>
    </div>
</div>

<script>
    function toggleChat() {
        const window = document.getElementById('chatWindow');
        const icon = document.getElementById('chatIcon');
        
        window.classList.toggle('open');
        
        if(window.classList.contains('open')) {
            icon.classList.remove('fa-comments');
            icon.classList.add('fa-chevron-down');
            setTimeout(() => document.getElementById('chatInput').focus(), 300);
        } else {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-comments');
        }
    }
    
    function handleEnter(e) {
        if(e.key === 'Enter') sendMessage();
    }
    
    function sendMessage() {
        const input = document.getElementById('chatInput');
        const msg = input.value.trim();
        const body = document.getElementById('chatBody');
        const typing = document.getElementById('typingIndicator');
        const whatsappNum = "<?= $chatWhatsapp ?>";
        
        if(!msg) return;
        
        // 1. Add User Msg
        const userBubble = document.createElement('div');
        userBubble.className = 'user-msg';
        userBubble.innerText = msg;
        body.insertBefore(userBubble, typing); // Insert before typing
        
        input.value = '';
        body.scrollTop = body.scrollHeight;
        
        // 2. Show Typing
        typing.style.display = 'block';
        body.scrollTop = body.scrollHeight;
        
        // 3. AJAX Request
        fetch('ajax_chatbot.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ message: msg })
        })
        .then(res => res.json())
        .then(data => {
            setTimeout(() => {
                typing.style.display = 'none';
                
                // --- INSERT BOT REPLY BEFORE TYPING INDICATOR TO KEEP ORDER ---
                
                if (data.type === 'products' && data.data && data.data.length > 0) {
                    // Render Products
                    console.log('Rendering Products:', data.data);
                    
                    const msgBubble = document.createElement('div');
                    msgBubble.className = 'bot-msg';
                    msgBubble.innerHTML = parseMessage(data.message);
                    body.insertBefore(msgBubble, typing);
                    
                    const carousel = document.createElement('div');
                    carousel.className = 'chat-products-carousel';
                    // Ensure full width usage within flex container
                    carousel.style.width = '100%'; 
                    
                    data.data.forEach(prod => {
                        // Sanitize attributes
                        const safeName = prod.name.replace(/"/g, '&quot;');
                        const safeImage = prod.image.replace(/"/g, '&quot;');
                        
                        const card = document.createElement('div');
                        card.className = 'chat-product-card';
                        card.innerHTML = `
                            <div style="height:100px; overflow:hidden; border-radius:6px; margin-bottom:6px;">
                                <img src="${safeImage}" alt="${safeName}" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                            <h5 title="${safeName}">${prod.name}</h5>
                            <span class="price">₹${prod.price}</span>
                            <a href="${prod.url}" class="view-btn">View</a>
                        `;
                        carousel.appendChild(card);
                    });
                    body.insertBefore(carousel, typing);
                    
                } else if (data.type === 'contact_agent') {
                     // Agent Redirect Logic
                     const botBubble = document.createElement('div');
                     botBubble.className = 'bot-msg';
                     // Parse message first, then append link
                     botBubble.innerHTML = parseMessage(data.message) + ' <br><a href="#" onclick="openWhatsapp(\''+msg+'\')" style="color:#D4AF37;font-weight:700;text-decoration:none;margin-top:5px;display:inline-block;">Click to Chat on WhatsApp <i class="fa-solid fa-arrow-right"></i></a>';
                     body.insertBefore(botBubble, typing); // <-- CHANGE HERE
                     
                } else {
                    // Standard Text
                    const botBubble = document.createElement('div');
                    botBubble.className = 'bot-msg';
                    botBubble.innerHTML = parseMessage(data.message); 
                    body.insertBefore(botBubble, typing); // <-- CHANGE HERE
                }
                
                body.scrollTop = body.scrollHeight;
            }, 500); // Small delay for realism
        })
        .catch(err => {
            typing.style.display = 'none';
            console.error(err);
        });
    }
    
    // Helper to parse Markdown-like syntax from backend
    function parseMessage(text) {
        if (!text) return '';
        
        // 1. Escape HTML (Basic XSS protection)
        let safe = text.replace(/&/g, "&amp;")
                       .replace(/</g, "&lt;")
                       .replace(/>/g, "&gt;")
                       .replace(/"/g, "&quot;")
                       .replace(/'/g, "&#039;");

        // 2. Bold: **text** -> <strong>text</strong>
        safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // 3. Links: [text](url) -> <a href="url" target="_blank">text</a>
        safe = safe.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank" style="text-decoration:underline; color:#0e7490;">$1</a>');
        
        // 4. Newlines: \n -> <br>
        safe = safe.replace(/\n/g, '<br>');
        
        return safe;
    }
    
    function openWhatsapp(msg) {
        const whatsappNum = "<?= preg_replace('/[^0-9]/', '', $chatWhatsapp) ?>";
        if(whatsappNum) {
            const url = `https://wa.me/${whatsappNum}?text=${encodeURIComponent(msg)}`;
            window.open(url, '_blank');
        }
    }
</script>
<?php endif; ?>

<!-- Floating WhatsApp Button -->
<a href="https://wa.me/919500650454?text=Hello!%20I%20have%20a%20question" 
   target="_blank" 
   class="whatsapp-float">
  <i class="fab fa-whatsapp"></i>
</a>

<style>
.whatsapp-float {
    position: fixed;
    left: 20px;
    bottom: 20px;
    background: #25D366;
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
    z-index: 9999;
    transition: all 0.3s ease;
    text-decoration: none;
}

.whatsapp-float:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(37, 211, 102, 0.6);
}

@media (max-width: 768px) {
  .whatsapp-float {
    left: 15px;
    bottom: 90px;
    width: 50px;
    height: 50px;
    font-size: 26px;
    z-index: 20000;
  }
}
</style>
