<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

require_once __DIR__ . '/includes/db.php';

// ================= HANDLE PROFILE UPDATE =================
$updateMessage = '';
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
  try {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($firstName)) {
      throw new Exception('First name is required.');
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new Exception('Invalid email address.');
    }
    
    // Check if email is already used by another user
    if (!empty($email)) {
      $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
      $stmtCheck->execute([$email, $_SESSION['user_id']]);
      if ($stmtCheck->fetch()) {
        throw new Exception('This email is already in use by another account.');
      }
    }
    
    // Combine first and last name
    $fullName = $firstName . ($lastName ? ' ' . $lastName : '');
    
    // Convert gender to database format (male/female)
    $genderDB = null;  // Default to NULL
    if (strtolower($gender) === 'male') {
      $genderDB = 'male';
    } elseif (strtolower($gender) === 'female') {
      $genderDB = 'female';
    }
    
    // Update user in database
    $stmtUpdate = $pdo->prepare("
      UPDATE users 
      SET name = ?, email = ?, phone = ?, gender = ?
      WHERE id = ?
    ");
    
    $stmtUpdate->execute([
      $fullName,
      $email,
      $phone,
      $genderDB,  // Will be NULL if no gender selected, or 'M'/'F'
      $_SESSION['user_id']
    ]);
    
    $updateMessage = 'Profile updated successfully!';
    
  } catch (Exception $e) {
    $updateError = $e->getMessage();
  }
}

// ================= USER INFO =================
$stmt = $pdo->prepare("SELECT id, name, email, phone, gender FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fallbacks
$userName   = $user['name']  ?? 'Customer Name';
$userEmail  = $user['email'] ?? 'customer@example.com';
$userPhone  = $user['phone'] ?? '+91 00000 00000';

// Convert database gender to display format
$userGenderDB = strtolower($user['gender'] ?? '');
$userGender = '';
if ($userGenderDB === 'male' || $userGenderDB === 'm') {
  $userGender = 'male';
} elseif ($userGenderDB === 'female' || $userGenderDB === 'f') {
  $userGender = 'female';
}

// Split first/last name
$nameParts  = preg_split('/\s+/', trim($userName));
$firstName  = $nameParts[0] ?? 'Customer';
$lastName   = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';

// Avatar letter
$avatarLetter = strtoupper(substr($userName, 0, 1));

// Gender checked flags
$isMale   = ($userGender === 'male');
$isFemale = ($userGender === 'female');

// ================= USER ORDERS =================
$stmtOrders = $pdo->prepare("
    SELECT 
        o.id,
        o.order_number,
        o.status,
        o.created_at,
        o.total_amount AS grand_total
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmtOrders->execute([$_SESSION['user_id']]);
$orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

// ================= USER ADDRESSES =================
$stmtAddr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
$stmtAddr->execute([$_SESSION['user_id']]);
$addresses = $stmtAddr->fetchAll(PDO::FETCH_ASSOC);

// ================= USER UPI =================
$stmtUpi = $pdo->prepare("SELECT * FROM user_upi WHERE user_id = ? ORDER BY created_at DESC");
$stmtUpi->execute([$_SESSION['user_id']]);
$userUpis = $stmtUpi->fetchAll(PDO::FETCH_ASSOC);

// ================= NOTIFICATIONS =================
$stmtNotif = $pdo->prepare("SELECT * FROM user_notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmtNotif->execute([$_SESSION['user_id']]);
$notifications = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

// ================= WISHLIST =================
$stmtWish = $pdo->prepare("
    SELECT w.id AS wishlist_id, p.* 
    FROM wishlist w
    JOIN products p ON p.id = w.product_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmtWish->execute([$_SESSION['user_id']]);
$wishlistItems = $stmtWish->fetchAll(PDO::FETCH_ASSOC);

// ================= USER REVIEWS =================
$stmtReviews = $pdo->prepare("
    SELECT 
        r.id,
        r.product_id,
        r.reviewer_name,
        r.reviewer_email,
        r.rating,
        r.comment,
        r.status,
        r.created_at,
        p.name   AS product_name,
        p.images AS product_images
    FROM product_reviews r
    JOIN products p ON p.id = r.product_id
    WHERE r.reviewer_email = ?
    ORDER BY r.created_at DESC
");
$stmtReviews->execute([$userEmail]);
$userReviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Account – Devilixirs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <!-- Navbar CSS -->
  <link rel="stylesheet" href="assets/css/navbar.css">

  <style>
    :root{
      --primary:#2874f0;
      --primary-dark:#225fc0;
      --bg:#f1f3f6;
      --text:#111;
      --muted:#555;
      --border:#e0e0e0;
    }

    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
    }

    body{
      font-family:'Poppins',sans-serif;
      background:var(--bg);
      color:var(--text);
    }

    a{
      color:inherit;
      text-decoration:none;
    }

    /* PAGE WRAP */
    .account-wrapper{
      max-width:1180px;
      margin:40px auto 40px;
      padding:0 15px;
      display:flex;
      gap:18px;
    }

    /* LEFT SIDEBAR */
    .account-sidebar{
      width:280px;
      flex:0 0 280px;
      flex-shrink:0;
      background:#fff;
      border-radius:2px;
      box-shadow:0 1px 3px rgba(0,0,0,0.08);
    }

    .sidebar-header{
      display:flex;
      align-items:center;
      gap:10px;
      padding:16px 18px;
      background:#fff;
      border-bottom:1px solid var(--border);
    }

    .avatar{
      width:42px;
      height:42px;
      border-radius:50%;
      background:#ffe2b3;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:600;
      font-size:18px;
      color:#9b4b00;
    }

    .sidebar-header-text{
      display:flex;
      flex-direction:column;
      justify-content:center;
    }
    .sidebar-header-text span{
      font-size:13px;
      color:#757575;
    }
    .sidebar-header-text strong{
      font-size:15px;
      color:#212121;
    }

    .sidebar-section{
      border-top:8px solid #f5f5f5;
      padding:10px 0;
    }

    .sidebar-title{
      font-size:12px;
      font-weight:600;
      text-transform:uppercase;
      padding:0 18px 6px;
      color:#878787;
    }

    .sidebar-link{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:10px 18px;
      font-size:13px;
      cursor:pointer;
    }
    .sidebar-link:hover{
      background:#f5f5f5;
    }
    .sidebar-link.active{
      color:var(--primary);
      font-weight:500;
    }
    .sidebar-link span:last-child{
      font-size:11px;
      color:#878787;
    }

    /* RIGHT CONTENT */
    .account-main{
      flex:1 1 auto;
      min-width:0;
      background:#fff;
      border-radius:2px;
      box-shadow:0 1px 3px rgba(0,0,0,0.08);
      padding:20px 24px 30px;
      min-height:500px;
    }

    .section-heading-row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      margin-bottom:12px;
    }

    .section-heading-row h2{
      font-size:18px;
      font-weight:600;
    }

    .section-heading-row a{
      font-size:12px;
      color:var(--primary);
      font-weight:500;
      cursor:pointer;
    }

    .section-subtext{
      font-size:12px;
      color:#878787;
      margin-bottom:18px;
    }

    /* FORM GRID */
    .form-row{
      display:flex;
      gap:18px;
      margin-bottom:18px;
    }
    .form-field{
      flex:1;
      display:flex;
      flex-direction:column;
      gap:4px;
    }

    .form-label{
      font-size:12px;
      color:#878787;
    }

    .form-input{
      width: 100%;
      padding:10px 12px;
      border:1px solid #d0d0d0;
      border-radius:2px;
      font-size:13px;
      outline:none;
      background:#f9f9f9;
    }
    .form-input:focus{
      border-color:var(--primary);
      box-shadow:0 0 0 1px rgba(40,116,240,.2);
    }

    .gender-row{
      display:flex;
      align-items:center;
      gap:16px;
      font-size:13px;
      margin-bottom:24px;
    }
    .gender-row input{
      margin-right:4px;
    }

    .divider{
      height:1px;
      background:#f0f0f0;
      margin:24px 0;
    }

    /* FAQ SECTION */
    .faq-title{
      font-size:14px;
      font-weight:600;
      margin-bottom:10px;
    }

    .faq-item{
      margin-bottom:16px;
    }
    .faq-q{
      font-size:13px;
      font-weight:600;
      margin-bottom:4px;
    }
    .faq-a{
      font-size:12px;
      color:#555;
      line-height:1.6;
    }

    .danger-links{
      margin-top:24px;
      font-size:12px;
    }
    .danger-links a{
      display:inline-block;
      margin-right:20px;
      color:var(--primary);
    }
    .danger-links a.delete{
      color:#d32f2f;
    }

    /* Alert Messages */
    .alert{
      padding:12px 16px;
      border-radius:4px;
      margin-bottom:16px;
      font-size:13px;
      display:flex;
      align-items:center;
      gap:10px;
    }
    .alert-success{
      background:#e8f5e9;
      color:#2e7d32;
      border:1px solid #a5d6a7;
    }
    .alert-error{
      background:#ffebee;
      color:#c62828;
      border:1px solid #ef9a9a;
    }
    .alert i{
      font-size:16px;
    }

    /* RESPONSIVE (base) */
    @media (max-width:900px){
      .account-wrapper{
        flex-direction:column;
      }
      .account-sidebar{
        width:100%;
      }
    }

    @media (max-width:600px){
      .account-wrapper{
        padding:0 8px 20px;
      }
      .account-main{
        padding:16px 12px 24px;
      }
      .form-row{
        flex-direction:column;
      }
    }

    /* Mobile slide layout */
    @media (max-width:900px){
      .account-wrapper{
        position:relative;
        overflow:hidden;
      }

      .account-sidebar,
      .account-main{
        width:100%;
        max-width:100%;
        transition:transform .3s ease;
      }

      .account-main{
        position:absolute;
        top:0;
        left:0;
        transform:translateX(100%);
      }

      .account-wrapper.show-main .account-sidebar{
        transform:translateX(-100%);
      }
      .account-wrapper.show-main .account-main{
        transform:translateX(0%);
      }

      .mobile-back-row{
        display:flex;
        align-items:center;
        gap:8px;
        margin-bottom:10px;
      }
    }

    @media (min-width:901px){
      .mobile-back-row{
        display:none;
      }
    }

    .mobile-back-btn{
      border:none;
      background:none;
      padding:4px 0 8px;
      font-size:14px;
      font-weight:500;
      color:var(--primary);
      cursor:pointer;
      display:flex;
      align-items:center;
      gap:4px;
    }

    /* ORDERS STYLES */
    .orders-header-row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:18px;
    }

    .orders-header-row h2{
      font-size:18px;
      font-weight:600;
    }

    .orders-search{
      display:flex;
      align-items:center;
      gap:8px;
    }

    .orders-search input{
      padding:8px 10px;
      border:1px solid var(--border);
      border-radius:2px;
      font-size:13px;
      min-width:260px;
    }

    .orders-search button{
      padding:8px 14px;
      border:none;
      border-radius:2px;
      background:var(--primary);
      color:#fff;
      font-size:13px;
      cursor:pointer;
    }
    .orders-search button:hover{
      background:var(--primary-dark);
    }

    .orders-main{
      flex:1;
      display:flex;
      flex-direction:column;
      gap:12px;
    }

    .order-card{
      border:1px solid var(--border);
      border-radius:2px;
      padding:14px 16px;
      display:flex;
      justify-content:space-between;
      gap:16px;
      background:#fff;
    }

    .order-left{
      display:flex;
      gap:12px;
      flex:1;
    }

    .order-product-img img{
      width:80px;
      height:80px;
      object-fit:cover;
    }

    .order-product-details{
      display:flex;
      flex-direction:column;
      justify-content:center;
      gap:4px;
    }

    .order-product-name{
      font-size:14px;
      font-weight:500;
    }

    .order-meta{
      font-size:12px;
      color:#777;
    }

    .order-price{
      font-size:14px;
      font-weight:600;
      margin-top:4px;
    }

    .order-right{
      min-width:210px;
      text-align:left;
      font-size:12px;
    }

    .order-status{
      font-size:12px;
      font-weight:600;
      margin-bottom:4px;
    }

    .order-status.delivered{
      color:#388e3c;
    }

    .order-status.cancelled{
      color:#d32f2f;
    }

    .order-status-text{
      font-size:12px;
      color:#555;
      margin-bottom:8px;
    }

    .order-link-btn{
      border:none;
      background:none;
      padding:0;
      font-size:12px;
      color:var(--primary);
      cursor:pointer;
    }

    @media (max-width:600px){
      .orders-header-row{
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
      }
      .orders-search input{
        min-width:0;
        width:100%;
      }
      .orders-search{
        width:100%;
      }
      .order-card{
        flex-direction:column;
      }
      .order-right{
        min-width:0;
      }
    }

    /* ADDRESS STYLES */
    .address-header-row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      margin-bottom:14px;
    }
    .address-header-row h2{
      font-size:16px;
      font-weight:600;
    }
    .address-add-btn{
      display:inline-flex;
      align-items:center;
      gap:6px;
      font-size:13px;
      font-weight:500;
      color:var(--primary);
      border:1px solid var(--border);
      background:#fafafa;
      padding:8px 12px;
      cursor:pointer;
      border-radius:2px;
    }
    .address-add-btn span.plus{
      font-size:18px;
      line-height:1;
    }
    .address-list{
      margin-top:10px;
      display:flex;
      flex-direction:column;
      gap:10px;
    }
    .address-card{
      border:1px solid var(--border);
      background:#fff;
      padding:12px 14px;
      font-size:13px;
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
    }
    .address-main{
      max-width:90%;
    }
    .address-name-line{
      font-weight:600;
      margin-bottom:4px;
    }
    .address-name-line span.phone{
      font-weight:500;
      margin-left:16px;
    }
    .address-text{
      color:#555;
      line-height:1.6;
    }
    .address-text span.pincode{
      font-weight:600;
    }
    .address-actions{
      font-size:18px;
      cursor:pointer;
      line-height:1;
      color:#555;
    }

    /* UPI / Coupons / Reviews / Wishlist */
    .upi-header-row{ margin-bottom:12px; }
    .upi-header-row h2{ font-size:16px;font-weight:600; }
    .upi-box{ border:1px solid var(--border); background:#fff; }
    .upi-row{
      display:flex;
      align-items:center;
      padding:10px 14px;
      border-bottom:1px solid var(--border);
      font-size:13px;
    }
    .upi-row:last-child{ border-bottom:none; }
    .upi-logo{
      width:32px;
      height:32px;
      border-radius:50%;
      background:#fff;
      border:1px solid #eee;
      display:flex;
      align-items:center;
      justify-content:center;
      margin-right:12px;
    }
    .upi-logo img{
      width:24px;
      height:24px;
      object-fit:contain;
    }
    .upi-info{
      flex:1;
      display:flex;
      flex-direction:column;
      gap:2px;
    }
    .upi-info-title{ font-weight:500; }
    .upi-info-id{ color:#555;font-size:13px; }
    .upi-delete{ font-size:16px;cursor:pointer;color:#c0c0c0; }

    .coupons-header{ margin-bottom:12px; }
    .coupons-header h2{ font-size:16px;font-weight:600; }
    .coupon-list{ display:flex;flex-direction:column;gap:0; }
    .coupon-card{
      border:1px solid var(--border);
      background:#fff;
      padding:12px 16px;
      font-size:13px;
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      border-bottom:none;
    }
    .coupon-card:last-child{ border-bottom:1px solid var(--border); }
    .coupon-left{ max-width:70%; }
    .coupon-title{ font-weight:600;color:#388e3c;margin-bottom:4px; }
    .coupon-desc{ font-size:12px;color:#555; }
    .coupon-right{ text-align:right;font-size:12px; }
    .coupon-valid{ color:#555;margin-bottom:6px; }
    .coupon-tnc{ color:var(--primary);font-weight:500;font-size:12px; }

    @media (max-width:600px){
      .coupon-card{ flex-direction:column;gap:6px; }
      .coupon-left{ max-width:100%; }
      .coupon-right{ text-align:left; }
    }

    .reviews-empty-wrap{
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      padding:60px 0 40px;
      text-align:center;
    }
    .reviews-empty-illustration{ margin-bottom:16px; }
    .reviews-empty-illustration img{
      width:140px;
      max-width:100%;
    }
    .reviews-empty-title{ font-size:16px;font-weight:600;margin-bottom:4px; }
    .reviews-empty-text{ font-size:12px;color:#777; }

    .wishlist-header{ margin-bottom:12px; }
    .wishlist-header h2{ font-size:16px;font-weight:600; }
    .wishlist-list{ display:flex;flex-direction:column;gap:0; }
    .wishlist-item{
      border:1px solid var(--border);
      background:#fff;
      padding:14px 16px;
      display:flex;
      align-items:flex-start;
      gap:18px;
      border-bottom:none;
    }
    .wishlist-item:last-child{ border-bottom:1px solid var(--border); }
    .wishlist-thumb img{
      width:90px;
      height:90px;
      object-fit:cover;
    }
    .wishlist-info{ flex:1; }
    .wishlist-title{
      font-size:14px;
      font-weight:500;
      margin-bottom:4px;
    }
    .wishlist-meta{
      font-size:12px;
      color:#777;
      margin-bottom:6px;
    }
    .wishlist-price-row{
      display:flex;
      align-items:baseline;
      gap:8px;
      font-size:14px;
    }
    .wishlist-price{ font-weight:600; }
    .wishlist-old-price{
      font-size:13px;
      text-decoration:line-through;
      color:#777;
    }
    .wishlist-discount{
      font-size:13px;
      color:#388e3c;
      font-weight:500;
    }
    .wishlist-delete{
      margin-left:auto;
      font-size:18px;
      cursor:pointer;
      color:#c0c0c0;
    }

    @media (max-width:600px){
      .wishlist-item{
        padding:12px;
        gap:10px;
      }
      .wishlist-thumb img{
        width:72px;
        height:72px;
      }
      .wishlist-title{
        font-size:13px;
      }
      .wishlist-price-row{
        font-size:13px;
      }
    }

    /* MODAL */
    .modal-backdrop{
      position:fixed;
      inset:0;
      background:rgba(0,0,0,0.35);
      z-index:900;
    }

    .address-modal{
      position:fixed;
      inset:0;
      display:flex;
      align-items:center;
      justify-content:center;
      z-index:901;
    }

    .address-modal-content{
      width:100%;
      max-width:560px;
      background:#fff;
      border-radius:4px;
      box-shadow:0 4px 20px rgba(0,0,0,0.18);
      padding:18px 20px 16px;
    }

    .address-modal-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      margin-bottom:14px;
    }

    .address-modal-header h3{
      font-size:16px;
      font-weight:600;
    }

    .address-modal-close{
      border:none;
      background:none;
      font-size:20px;
      cursor:pointer;
    }

    .address-form .form-input{
      background:#fff;
    }

    .address-cancel-btn,
    .address-save-btn{
      padding:8px 16px;
      font-size:13px;
      border-radius:2px;
      border:none;
      cursor:pointer;
    }

    .address-cancel-btn{
      background:#f1f1f1;
      margin-right:8px;
    }

    .address-save-btn{
      background:var(--primary);
      color:#fff;
    }
    .address-save-btn:hover{
      background:var(--primary-dark);
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<div class="account-wrapper">
  <aside class="account-sidebar">

    <div class="sidebar-header">
      <div class="avatar"><?php echo htmlspecialchars($avatarLetter); ?></div>
      <div class="sidebar-header-text">
        <span>Hello,</span>
        <strong><?php echo htmlspecialchars($userName); ?></strong>
      </div>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-title">My Orders</div>
      <a href="#" class="sidebar-link" data-section="orders">
        <span>All Orders</span>
        <span>&gt;</span>
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-title">Account Settings</div>
      <a href="#" class="sidebar-link active" data-section="profile">
        <span>Profile Information</span>
        <span>&gt;</span>
      </a>
      <a href="#" class="sidebar-link" data-section="addresses">
        <span>Manage Addresses</span>
        <span>&gt;</span>
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-title">Payments</div>
      <a href="#" class="sidebar-link" data-section="upi">
        <span>Saved UPI</span>
        <span>&gt;</span>
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-title">My Stuff</div>
      <a href="#" class="sidebar-link" data-section="reviews">
        <span>My Reviews & Ratings</span>
        <span>&gt;</span>
      </a>
      <a href="#" class="sidebar-link" data-section="notifications">
        <span>All Notifications</span>
        <span>&gt;</span>
      </a>
      <a href="#" class="sidebar-link" data-section="wishlist">
        <span>My Wishlist</span>
        <span>&gt;</span>
      </a>
    </div>

  </aside>

  <main class="account-main">

    <!-- MOBILE BACK BUTTON -->
    <div class="mobile-back-row">
      <button type="button" class="mobile-back-btn">←</button>
      <span class="mobile-section-label">Account Settings</span>
    </div>

    <!-- PROFILE SECTION -->
    <section class="account-section active" id="section-profile">
      
      <?php if ($updateMessage): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i>
          <span><?php echo htmlspecialchars($updateMessage); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($updateError): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <span><?php echo htmlspecialchars($updateError); ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="">
        <input type="hidden" name="action" value="update_profile">

        <div class="section-heading-row">
          <h2>Personal Information</h2>
          <a href="#" class="profile-edit-link" data-section="personal">Edit</a>
        </div>
        <p class="section-subtext">
          Update your name and gender details here.
        </p>

        <div class="form-row">
          <div class="form-field">
            <label class="form-label">First Name</label>
            <input 
              type="text" 
              class="form-input profile-input" 
              name="first_name"
              value="<?php echo htmlspecialchars($firstName); ?>" 
              data-section="personal"
              readonly
              required
            >
          </div>
          <div class="form-field">
            <label class="form-label">Last Name</label>
            <input 
              type="text" 
              class="form-input profile-input" 
              name="last_name"
              value="<?php echo htmlspecialchars($lastName); ?>" 
              data-section="personal"
              readonly
            >
          </div>
        </div>

        <div class="form-label" style="margin-bottom:4px;">Your Gender</div>
        <div class="gender-row">
          <label>
            <input 
              type="radio" 
              name="gender" 
              value="male"
              class="profile-input"
              data-section="personal"
              <?php echo $isMale ? 'checked' : ''; ?> 
              disabled
            > 
            Male
          </label>
          <label>
            <input 
              type="radio" 
              name="gender" 
              value="female"
              class="profile-input"
              data-section="personal"
              <?php echo $isFemale ? 'checked' : ''; ?> 
              disabled
            > 
            Female
          </label>
        </div>

        <div class="divider"></div>

        <div class="section-heading-row">
          <h2>Email Address</h2>
          <a href="#" class="profile-edit-link" data-section="email">Edit</a>
        </div>
        <p class="section-subtext">
          Your email address is used for login and important communication.
        </p>

        <div class="form-row">
          <div class="form-field"> 
            <label class="form-label">Email Address</label>
            <input 
              type="email" 
              class="form-input profile-input"
              name="email"
              value="<?php echo htmlspecialchars($userEmail); ?>" 
              data-section="email"
              readonly
              required
            >
          </div>
        </div>

        <div class="divider"></div>

        <div class="section-heading-row">
          <h2>Mobile Number</h2>
          <a href="#" class="profile-edit-link" data-section="phone">Edit</a>
        </div>
        <p class="section-subtext">
          Your mobile number is used for OTP and order updates.
        </p>

        <div class="form-row">
          <div class="form-field">
            <label class="form-label">Mobile Number</label>
            <input 
              type="tel" 
              class="form-input profile-input"
              name="phone"
              value="<?php echo htmlspecialchars($userPhone); ?>" 
              data-section="phone"
              readonly
            >
          </div>
        </div>

        <!-- SAVE BUTTON (only visible after Edit is clicked) -->
        <div id="profile-actions" style="display:none; margin-top:12px;">
          <button type="submit" class="address-save-btn">
            Save Changes
          </button>
          <button type="button" class="address-cancel-btn" id="cancel-edit-btn">
            Cancel
          </button>
        </div>

        <div class="divider"></div>

        <h3 class="faq-title">FAQs</h3>

        <div class="faq-item">
          <div class="faq-q">What happens when I update my email address (or mobile number)?</div>
          <div class="faq-a">
            Your login email id (or mobile number) changes, likewise. You'll receive all your
            account related communication on your updated email address (or mobile number).
          </div>
        </div>

        <div class="faq-item">
          <div class="faq-q">When will my account be updated with the new email address (or mobile number)?</div>
          <div class="faq-a">
            It happens as soon as you confirm the verification code sent to your email (or mobile)
            and save the changes.
          </div>
        </div>

        <div class="danger-links">
          <a href="#" class="delete">Deactivate Account</a>
          <a href="logout.php">Logout</a>
        </div>
      </form>
    </section>

    <!-- ORDERS SECTION -->
    <section class="account-section" id="section-orders" style="display:none;">
      <div class="orders-header-row">
        <h2>My Orders</h2>
        <div class="orders-search">
          <input type="text" placeholder="Search your orders here" />
          <button>Search Orders</button>
        </div>
      </div>

      <div class="orders-main">
        <?php if (empty($orders)): ?>
          <p style="font-size:14px;color:#777;">You haven't placed any orders yet.</p>
        <?php else: ?>
          <?php foreach ($orders as $order): ?>
            <?php
              $stmtItem = $pdo->prepare("
                SELECT oi.quantity, oi.price, p.name, p.images
                FROM order_items oi
                JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = ?
                LIMIT 1
              ");
              $stmtItem->execute([$order['id']]);
              $item = $stmtItem->fetch(PDO::FETCH_ASSOC);

              $productName  = $item['name'] ?? 'Product';
              $productImage = '';

              if (!empty($item['images'])) {
                $imgs = json_decode($item['images'], true);
                if (is_array($imgs) && !empty($imgs[0])) {
                  $productImage = '/assets/uploads/products/' . $imgs[0];
                }
              }

              $status = strtolower($order['status'] ?? '');
              $statusClass = ($status === 'delivered') ? 'delivered' : (($status === 'cancelled') ? 'cancelled' : '');
            ?>
            <article class="order-card">
              <div class="order-left">
                <div class="order-product-img">
                  <img src="<?php echo $productImage ?: 'https://via.placeholder.com/80'; ?>" alt="">
                </div>
                <div class="order-product-details">
                  <div class="order-product-name">
                    <?php echo htmlspecialchars($productName); ?>
                  </div>
                  <div class="order-meta">
                    Order No: #<?php echo htmlspecialchars($order['order_number']); ?>
                  </div>
                  <div class="order-price">
                    ₹<?php echo number_format((float)$order['grand_total'], 2); ?>
                  </div>
                </div>
              </div>
              <div class="order-right">
                <div class="order-status <?php echo $statusClass; ?>">
                  ● <?php echo ucfirst($order['status']); ?>
                </div>
                <div class="order-status-text">
                  Ordered on <?php echo date('d M Y', strtotime($order['created_at'])); ?>
                </div>
                <a class="order-link-btn" href="order-details.php?id=<?php echo (int)$order['id']; ?>">
                  View Order
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- ADDRESSES SECTION -->
    <section class="account-section" id="section-addresses" style="display:none;">
      <div class="address-header-row">
        <h2>Manage Addresses</h2>
        <button type="button" class="address-add-btn" id="openAddressModal">
          <span class="plus">+</span>
          <span>ADD A NEW ADDRESS</span>
        </button>
      </div>

      <div class="address-list">
        <?php if (empty($addresses)): ?>
          <p style="font-size:13px;color:#777;">
            You don't have any saved addresses yet. Add one to make checkout faster.
          </p>
        <?php else: ?>
          <?php foreach ($addresses as $addr): ?>
            <div class="address-card">
              <div class="address-main">
                <div class="address-name-line">
                  <?php echo htmlspecialchars($addr['full_name']); ?>
                  <span class="phone"><?php echo htmlspecialchars($addr['phone']); ?></span>
                  <?php if (!empty($addr['is_default'])): ?>
                    <span style="margin-left:10px;font-size:11px;color:#388e3c;border:1px solid #388e3c;padding:2px 6px;border-radius:2px;">
                      Default
                    </span>
                  <?php endif; ?>
                </div>
                <div class="address-text">
                  <?php echo nl2br(htmlspecialchars($addr['address_line1'])); ?>
                  <?php if (!empty($addr['address_line2'])): ?>
                    , <?php echo nl2br(htmlspecialchars($addr['address_line2'])); ?>
                  <?php endif; ?>,
                  <?php echo htmlspecialchars($addr['city']); ?>,
                  <?php echo htmlspecialchars($addr['state']); ?> –
                  <span class="pincode"><?php echo htmlspecialchars($addr['pincode']); ?></span>
                </div>
              </div>
              <div class="address-actions">
                <form action="delete-address.php" method="post" style="display:inline;" 
                      onsubmit="return confirm('Delete this address?');">
                  <input type="hidden" name="address_id" value="<?php echo (int)$addr['id']; ?>">
                  <button type="submit" 
                          style="border:none;background:none;cursor:pointer;font-size:16px;color:#c00;" 
                          title="Delete">
                    &#128465;
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- UPI SECTION -->
    <section class="account-section" id="section-upi" style="display:none;">
      <div class="upi-header-row">
        <h2>Manage Saved UPI</h2>
      </div>

      <div class="upi-box">
        <?php if (empty($userUpis)): ?>
          <div style="padding:16px;text-align:center;color:#777;font-size:13px;">
            No saved UPI IDs found.
          </div>
        <?php else: ?>
          <?php foreach ($userUpis as $upi): ?>
            <?php
              $provider = htmlspecialchars($upi['provider'] ?? 'UPI');
              $upiId = htmlspecialchars($upi['upi_id']);
              // Simple logic to pick a logo based on provider name (optional)
              $logoUrl = 'https://cdn-icons-png.flaticon.com/512/10103/10103218.png'; // Default UPI logo
              if (stripos($provider, 'Google') !== false) {
                $logoUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5a/Google_Pay_Logo.svg/512px-Google_Pay_Logo.svg.png';
              } elseif (stripos($provider, 'PhonePe') !== false) {
                $logoUrl = 'https://download.logo.wine/logo/PhonePe/PhonePe-Logo.wine.png';
              } elseif (stripos($provider, 'Paytm') !== false) {
                $logoUrl = 'https://download.logo.wine/logo/Paytm/Paytm-Logo.wine.png';
              }
            ?>
            <div class="upi-row">
              <div class="upi-logo">
                <img src="<?php echo $logoUrl; ?>" alt="<?php echo $provider; ?>">
              </div>
              <div class="upi-info">
                <div class="upi-info-title"><?php echo $provider; ?></div>
                <div class="upi-info-id"><?php echo $upiId; ?></div>
              </div>
              <div class="upi-delete" title="Delete">&#128465;</div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- REVIEWS SECTION -->
    <section class="account-section" id="section-reviews" style="display:none;">
      <?php if (empty($userReviews)): ?>
        <div class="reviews-empty-wrap">
          <div class="reviews-empty-illustration">
            <img src="https://cdn-icons-png.flaticon.com/512/748/748113.png" alt="No reviews">
          </div>
          <div class="reviews-empty-title">No Reviews &amp; Ratings</div>
          <div class="reviews-empty-text">
            You have not rated or reviewed any product yet!
          </div>
        </div>
      <?php else: ?>
        <h2 style="font-size:16px;font-weight:600;margin-bottom:10px;">
          My Reviews &amp; Ratings
        </h2>

        <div style="display:flex;flex-direction:column;gap:10px;margin-top:10px;">
          <?php foreach ($userReviews as $rev): ?>
            <?php
              $thumb = 'https://via.placeholder.com/60';
              if (!empty($rev['product_images'])) {
                  $imgs = json_decode($rev['product_images'], true);
                  if (is_array($imgs) && !empty($imgs[0])) {
                      $thumb = '/assets/uploads/products/' . $imgs[0];
                  }
              }

              $stars = (int)round($rev['rating']);
              $status = $rev['status'];
              $statusLabel = ucfirst($status);
              $statusColor = '#999';

              if ($status === 'approved') $statusColor = '#388e3c';
              elseif ($status === 'pending') $statusColor = '#f57c00';
              elseif ($status === 'rejected') $statusColor = '#d32f2f';
            ?>
            <div style="border:1px solid #e0e0e0;padding:10px 12px;display:flex;gap:10px;background:#fff;">
              <div class="review-order-thumb">
                <img src="<?php echo htmlspecialchars($thumb); ?>" alt="" 
                     style="width:60px;height:60px;object-fit:cover;border:1px solid #eee;">
              </div>

              <div class="review-order-body" style="flex:1;">
                <div class="review-order-name" style="font-size:13px;font-weight:600;margin-bottom:2px;">
                  <?php echo htmlspecialchars($rev['product_name']); ?>
                </div>

                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                  <div class="review-stars" style="font-size:14px;color:#ffb400;">
                    <?php echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars); ?>
                  </div>
                  <div style="font-size:11px;color:<?php echo $statusColor; ?>;">
                    ● <?php echo $statusLabel; ?>
                  </div>
                </div>

                <div style="font-size:12px;color:#555;margin-bottom:4px;">
                  <?php echo nl2br(htmlspecialchars($rev['comment'])); ?>
                </div>

                <div style="font-size:11px;color:#999;">
                  Reviewed on <?php echo date('d M Y', strtotime($rev['created_at'])); ?>
                </div>

                <div style="margin-top:6px;font-size:12px;">
                  <a href="product_view.php?id=<?php echo (int)$rev['product_id']; ?>" 
                     style="color:#2874f0;text-decoration:none;">
                    View Product
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- NOTIFICATIONS SECTION -->
    <section class="account-section" id="section-notifications" style="display:none;">
      <h2>All Notifications</h2>
      <?php if (empty($notifications)): ?>
        <p style="font-size:13px;color:#777;">No new notifications.</p>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px;margin-top:10px;">
          <?php foreach ($notifications as $notif): ?>
            <div style="border:1px solid #e0e0e0;padding:12px;background:<?php echo $notif['is_read'] ? '#fff' : '#f9f9f9'; ?>;">
              <div style="font-size:14px;font-weight:600;margin-bottom:4px;">
                <?php echo htmlspecialchars($notif['title']); ?>
              </div>
              <div style="font-size:13px;color:#555;margin-bottom:6px;">
                <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
              </div>
              <div style="font-size:11px;color:#999;">
                <?php echo date('d M Y, h:i A', strtotime($notif['created_at'])); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- WISHLIST SECTION -->
    <section class="account-section" id="section-wishlist" style="display:none;">
      <div class="wishlist-header">
        <h2>My Wishlist (<?php echo count($wishlistItems); ?>)</h2>
      </div>

      <div class="wishlist-list">
        <?php if (empty($wishlistItems)): ?>
          <p style="font-size:13px;color:#777;">Your wishlist is empty.</p>
        <?php else: ?>
          <?php foreach ($wishlistItems as $item): ?>
            <?php
              $img = 'https://via.placeholder.com/90';
              if (!empty($item['images'])) {
                $decoded = json_decode($item['images'], true);
                if (is_array($decoded) && !empty($decoded[0])) {
                  $img = '/assets/uploads/products/' . $decoded[0];
                }
              }
              $price = $item['price'];
              $compare = $item['compare_price'];
              $discount = 0;
              if ($compare > $price) {
                $discount = round((($compare - $price) / $compare) * 100);
              }
            ?>
            <div class="wishlist-item">
              <div class="wishlist-thumb">
                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
              </div>
              <div class="wishlist-info">
                <div class="wishlist-title">
                  <a href="product_view.php?id=<?php echo $item['id']; ?>">
                    <?php echo htmlspecialchars($item['name']); ?>
                  </a>
                </div>
                <div class="wishlist-meta">
                  <!-- Optional: Display variant or other meta info -->
                </div>
                <div class="wishlist-price-row">
                  <span class="wishlist-price">₹<?php echo number_format($price, 2); ?></span>
                  <?php if ($compare > $price): ?>
                    <span class="wishlist-old-price">₹<?php echo number_format($compare, 2); ?></span>
                    <span class="wishlist-discount"><?php echo $discount; ?>% off</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="wishlist-delete" title="Remove">&#128465;</div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

  </main>
</div>

<!-- Address Modal -->
<div id="addressModalBackdrop" class="modal-backdrop" style="display:none;"></div>

<div id="addressModal" class="address-modal" style="display:none;">
  <div class="address-modal-content">
    <div class="address-modal-header">
      <h3>Add New Address</h3>
      <button type="button" class="address-modal-close" id="closeAddressModal">&times;</button>
    </div>
    <form action="add-address.php" method="post" class="address-form">
      <div class="form-row">
        <div class="form-field">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-input" required>
        </div>
        <div class="form-field">
          <label class="form-label">Phone</label>
          <input type="tel" name="phone" class="form-input" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label class="form-label">Address Line 1</label>
          <textarea name="address_line1" class="form-input" rows="2" required></textarea>
        </div>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label class="form-label">Address Line 2 (Optional)</label>
          <textarea name="address_line2" class="form-input" rows="2"></textarea>
        </div>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label class="form-label">City</label>
          <input type="text" name="city" class="form-input" required>
        </div>
        <div class="form-field">
          <label class="form-label">State</label>
          <input type="text" name="state" class="form-input" required>
        </div>
        <div class="form-field">
          <label class="form-label">Pincode</label>
          <input type="text" name="pincode" class="form-input" required>
        </div>
      </div>

      <div style="margin-bottom:12px;font-size:13px;">
        <label>
          <input type="checkbox" name="is_default" value="1">
          Set as default address
        </label>
      </div>

      <div style="text-align:right;margin-top:10px;">
        <button type="button" class="address-cancel-btn" id="cancelAddressModal">Cancel</button>
        <button type="submit" class="address-save-btn">Save Address</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    const sections = document.querySelectorAll('.account-section');
    const wrapper = document.querySelector('.account-wrapper');
    const mobileBackBtn = document.querySelector('.mobile-back-btn');
    const mobileSectionLabel = document.querySelector('.mobile-section-label');
    
    // Address modal elements
    const openAddressModalBtn = document.getElementById('openAddressModal');
    const addressModal = document.getElementById('addressModal');
    const addressModalBackdrop = document.getElementById('addressModalBackdrop');
    const closeAddressModalBtn = document.getElementById('closeAddressModal');
    const cancelAddressModalBtn = document.getElementById('cancelAddressModal');

    // Profile edit elements
    const profileSection = document.getElementById('section-profile');
    const profileEditLinks = profileSection.querySelectorAll('.profile-edit-link');
    const profileInputs = profileSection.querySelectorAll('.profile-input');
    const profileActions = document.getElementById('profile-actions');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');

    // Store original values for cancel functionality
    let originalValues = {};

    function isMobile() {
      return window.innerWidth <= 900;
    }

    // Sidebar navigation
    sidebarLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();

        sidebarLinks.forEach(l => l.classList.remove('active'));
        this.classList.add('active');

        const sectionKey = this.getAttribute('data-section');
        if (!sectionKey) return;

        sections.forEach(sec => {
          sec.style.display = (sec.id === 'section-' + sectionKey) ? 'block' : 'none';
        });

        const sectionTitleEl = this.closest('.sidebar-section')?.querySelector('.sidebar-title');
        const groupTitle = sectionTitleEl ? sectionTitleEl.textContent.trim() : 'Account';

        if (isMobile()) {
          wrapper.classList.add('show-main');
          if (mobileSectionLabel) {
            mobileSectionLabel.textContent = groupTitle;
          }
        }
      });
    });

    // Mobile back button
    if (mobileBackBtn) {
      mobileBackBtn.addEventListener('click', function() {
        wrapper.classList.remove('show-main');

        sections.forEach(sec => {
          sec.style.display = (sec.id === 'section-profile') ? 'block' : 'none';
        });

        sidebarLinks.forEach(link => {
          if (link.getAttribute('data-section') === 'profile') {
            link.classList.add('active');
          } else {
            link.classList.remove('active');
          }
        });

        if (mobileSectionLabel) {
          mobileSectionLabel.textContent = 'Account Settings';
        }
      });
    }

    // Address modal functions
    function openAddressModal() {
      if (addressModal && addressModalBackdrop) {
        addressModal.style.display = 'flex';
        addressModalBackdrop.style.display = 'block';
      }
    }

    function closeAddressModal() {
      if (addressModal && addressModalBackdrop) {
        addressModal.style.display = 'none';
        addressModalBackdrop.style.display = 'none';
      }
    }

    if (openAddressModalBtn) {
      openAddressModalBtn.addEventListener('click', openAddressModal);
    }
    if (closeAddressModalBtn) {
      closeAddressModalBtn.addEventListener('click', closeAddressModal);
    }
    if (cancelAddressModalBtn) {
      cancelAddressModalBtn.addEventListener('click', closeAddressModal);
    }
    if (addressModalBackdrop) {
      addressModalBackdrop.addEventListener('click', closeAddressModal);
    }

    // Profile edit functionality
    profileEditLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        
        const section = this.getAttribute('data-section');
        
        // Store original values
        profileInputs.forEach(input => {
          const inputSection = input.getAttribute('data-section');
          if (!section || inputSection === section) {
            if (input.type === 'radio') {
              originalValues[input.name] = input.checked;
            } else {
              originalValues[input.name] = input.value;
            }
          }
        });

        // Enable inputs for this section
        profileInputs.forEach(input => {
          const inputSection = input.getAttribute('data-section');
          if (!section || inputSection === section) {
            if (input.type === 'radio') {
              input.disabled = false;
            } else {
              input.removeAttribute('readonly');
              input.style.backgroundColor = '#fff';
            }
          }
        });

        // Show save/cancel buttons
        profileActions.style.display = 'block';
      });
    });

    // Cancel edit functionality
    if (cancelEditBtn) {
      cancelEditBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Restore original values
        profileInputs.forEach(input => {
          if (originalValues.hasOwnProperty(input.name)) {
            if (input.type === 'radio') {
              input.checked = originalValues[input.name];
              input.disabled = true;
            } else {
              input.value = originalValues[input.name];
              input.setAttribute('readonly', 'readonly');
              input.style.backgroundColor = '#f9f9f9';
            }
          }
        });

        // Hide save/cancel buttons
        profileActions.style.display = 'none';
        
        // Clear stored values
        originalValues = {};
      });
    }
  });
</script>

</body>
</html>
