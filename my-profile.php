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
require_once __DIR__ . '/includes/subscription_lifecycle_helper.php';
require_once __DIR__ . '/includes/subscription_reporting_helper.php';

subscription_sync_statuses($pdo, (int)$_SESSION['user_id']);

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

$currentSubscription = subscription_fetch_current_active($pdo, (int)$_SESSION['user_id']);
$upcomingSubscription = subscription_fetch_upcoming_active($pdo, (int)$_SESSION['user_id']);
$subscriptionHistory = subscription_fetch_history($pdo, (int)$_SESSION['user_id'], 8);
$subscriptionTransactions = subscription_fetch_transactions($pdo, (int)$_SESSION['user_id'], 8);
$subscriptionDashboardStats = subscription_user_dashboard_stats($pdo, (int)$_SESSION['user_id'], $currentSubscription, $upcomingSubscription);
$subscriptionMessageCode = trim((string)($_GET['subscription'] ?? ''));
$subscriptionMessage = '';
$subscriptionMessageClass = 'alert-success';

if ($subscriptionMessageCode === 'activated') {
  $subscriptionMessage = 'Subscription activated successfully.';
} elseif ($subscriptionMessageCode === 'renewed') {
  $subscriptionMessage = 'Subscription renewal scheduled successfully.';
} elseif ($subscriptionMessageCode === 'already_subscribed') {
  $subscriptionMessage = 'You already have an active subscription.';
}

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

function get_product_image_url($images) {
  $default = '/assets/images/avatar-default.png';
  $images = (string)$images;
  if ($images === '') return $default;

  $val = '';
  $decoded = @json_decode($images, true);
  if (is_array($decoded) && !empty($decoded[0])) {
    $first = $decoded[0];
    if (is_array($first)) {
      $val = (string)($first['path'] ?? $first['url'] ?? $first['src'] ?? '');
    } else {
      $val = (string)$first;
    }
  } else {
    if (strpos($images, ',') !== false) {
      $parts = array_map('trim', explode(',', $images));
      $val = (string)($parts[0] ?? '');
    } else {
      $val = trim($images);
    }
  }

  if ($val === '') return $default;
  if (preg_match('#^https?://#i', $val) || strpos($val, '/') === 0) return $val;
  if (preg_match('#^(assets/|uploads/)#i', $val)) return '/' . ltrim($val, '/');
  return '/assets/uploads/products/' . ltrim($val, '/');
}

// ================= USER ORDERS =================
$stmtOrders = $pdo->prepare("
    SELECT 
        o.id,
        o.order_number,
        o.order_status AS status,
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

    /* ===== Visual Refresh ===== */
    :root{
      --brand:#0f766e;
      --brand-dark:#0b5f59;
      --brand-soft:#ecfeff;
      --surface:#ffffff;
      --surface-soft:#f8fafc;
      --text-strong:#0f172a;
      --text-muted:#64748b;
      --line:#e2e8f0;
      --shadow:0 12px 30px rgba(15, 23, 42, 0.08);
      --radius-lg:18px;
      --radius-md:12px;
      --radius-sm:10px;
    }

    body{
      background:
        radial-gradient(1100px 500px at -15% -25%, rgba(15,118,110,0.10), transparent 55%),
        radial-gradient(900px 500px at 115% -10%, rgba(217,119,6,0.10), transparent 52%),
        #f3f6fa;
      color:var(--text-strong);
    }

    .account-wrapper{
      max-width:1280px;
      margin:34px auto 64px;
      gap:24px;
      align-items:flex-start;
    }

    .account-sidebar{
      border:1px solid var(--line);
      border-radius:var(--radius-lg);
      overflow:hidden;
      box-shadow:var(--shadow);
      background:var(--surface);
      position:sticky;
      top:92px;
    }

    .sidebar-header{
      padding:20px 18px;
      border-bottom:1px solid var(--line);
      background:
        linear-gradient(130deg, rgba(15,118,110,0.10) 0%, rgba(255,255,255,1) 55%);
    }

    .avatar{
      width:50px;
      height:50px;
      border-radius:14px;
      font-size:20px;
      font-weight:700;
      color:#0b3f3c;
      background:linear-gradient(135deg, #ccfbf1, #fde68a);
      box-shadow:inset 0 0 0 1px rgba(15,118,110,0.16);
    }

    .sidebar-header-text span{
      color:var(--text-muted);
      font-size:12px;
      letter-spacing:.2px;
    }

    .sidebar-header-text strong{
      color:var(--text-strong);
      font-size:14px;
      font-weight:700;
    }

    .sidebar-section{
      border-top:1px solid #f1f5f9;
      padding:10px 0 12px;
    }

    .sidebar-title{
      padding:0 16px 8px;
      font-size:11px;
      letter-spacing:.6px;
      color:#94a3b8;
    }

    .sidebar-link{
      margin:4px 10px;
      border-radius:10px;
      padding:10px 12px;
      border:1px solid transparent;
      transition:all .2s ease;
      color:#334155;
    }

    .sidebar-link:hover{
      background:#f8fafc;
      border-color:#e2e8f0;
    }

    .sidebar-link.active{
      color:var(--brand);
      border-color:#99f6e4;
      background:var(--brand-soft);
      box-shadow:inset 0 0 0 1px rgba(13,148,136,0.08);
    }

    .sidebar-link span:first-child{
      font-weight:600;
      font-size:13px;
    }

    .sidebar-link span:last-child{
      color:#94a3b8;
      font-size:12px;
      font-weight:700;
    }

    .account-main{
      border:1px solid var(--line);
      border-radius:var(--radius-lg);
      box-shadow:var(--shadow);
      padding:18px;
      background:var(--surface-soft);
      min-height:680px;
    }

    .account-section{
      background:var(--surface);
      border:1px solid var(--line);
      border-radius:16px;
      padding:22px;
    }

    .mobile-back-row{
      margin-bottom:14px;
      background:#ffffff;
      border:1px solid var(--line);
      border-radius:12px;
      padding:8px 10px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
    }

    .mobile-back-btn{
      display:inline-flex;
      align-items:center;
      gap:8px;
      border:1px solid #b7ead3;
      background:#f0fdf7;
      color:#0f766e;
      border-radius:999px;
      padding:8px 12px;
      font-weight:700;
      font-size:13px;
      line-height:1;
      cursor:pointer;
    }

    .mobile-back-btn:hover{
      background:#dcfce7;
    }

    .mobile-back-btn i{
      font-size:12px;
    }

    .mobile-section-label{
      font-size:12px;
      color:#64748b;
      font-weight:600;
    }

    .section-heading-row{
      margin-bottom:8px;
    }

    .section-heading-row h2{
      font-size:20px;
      color:var(--text-strong);
      letter-spacing:.1px;
    }

    .section-heading-row a{
      color:var(--brand);
      font-size:12px;
      font-weight:700;
      padding:6px 10px;
      border:1px solid #99f6e4;
      border-radius:999px;
      transition:all .2s ease;
    }

    .section-heading-row a:hover{
      background:var(--brand-soft);
      border-color:#5eead4;
    }

    .section-subtext{
      color:var(--text-muted);
      margin-bottom:16px;
    }

    .form-row{
      gap:14px;
      margin-bottom:14px;
    }

    .form-label{
      color:#64748b;
      font-size:12px;
      font-weight:500;
      letter-spacing:.2px;
    }

    .form-input{
      background:#fff;
      border:1px solid #d5dde8;
      border-radius:12px;
      font-size:14px;
      color:var(--text-strong);
      padding:11px 12px;
      transition:border-color .2s ease, box-shadow .2s ease;
    }

    .form-input:focus{
      border-color:var(--brand);
      box-shadow:0 0 0 4px rgba(15,118,110,0.10);
    }

    .profile-input[readonly]{
      background:#f8fafc;
      color:#475569;
    }

    .gender-row{
      gap:10px;
      margin-bottom:18px;
    }

    .gender-row label{
      display:inline-flex;
      align-items:center;
      gap:5px;
      font-size:13px;
      border:1px solid #dbe3ee;
      border-radius:999px;
      padding:6px 12px;
      background:#fff;
    }

    .divider{
      margin:20px 0;
      background:#edf2f8;
    }

    .address-save-btn,
    .address-cancel-btn{
      border-radius:10px;
      padding:10px 16px;
      font-weight:600;
      transition:all .2s ease;
    }

    .address-save-btn{
      background:var(--brand);
    }

    .address-save-btn:hover{
      background:var(--brand-dark);
      transform:translateY(-1px);
    }

    .address-cancel-btn{
      background:#eef2f7;
      color:#334155;
    }

    .alert{
      border-radius:12px;
      border-width:1px;
      box-shadow:0 4px 12px rgba(2,6,23,0.04);
    }

    .orders-header-row{
      gap:12px;
      margin-bottom:16px;
      padding-bottom:14px;
      border-bottom:1px solid #edf2f8;
    }

    .orders-header-row h2{
      font-size:21px;
      letter-spacing:.1px;
    }

    .orders-search input{
      min-width:320px;
      border-radius:10px;
      border:1px solid #d5dde8;
      padding:10px 12px;
      background:#fff;
    }

    .orders-search button{
      border-radius:10px;
      background:var(--brand);
      font-weight:600;
      padding:10px 14px;
    }

    .orders-search button:hover{
      background:var(--brand-dark);
    }

    .order-card{
      border:1px solid #e2e8f0;
      border-radius:14px;
      padding:14px;
      background:#fff;
      box-shadow:0 4px 12px rgba(2,6,23,0.04);
      transition:transform .2s ease, box-shadow .2s ease;
    }

    .order-card:hover{
      transform:translateY(-1px);
      box-shadow:0 10px 20px rgba(2,6,23,0.08);
    }

    .order-product-img img{
      width:86px;
      height:86px;
      border-radius:12px;
      border:1px solid #e2e8f0;
      background:#fff;
    }

    .order-product-name{
      font-size:15px;
      font-weight:600;
      color:#111827;
    }

    .order-meta{
      color:#64748b;
      font-size:12px;
    }

    .order-price{
      font-size:15px;
      color:#0f172a;
      margin-top:2px;
    }

    .order-right{
      min-width:230px;
      display:flex;
      flex-direction:column;
      justify-content:center;
      gap:4px;
      text-align:left;
    }

    .order-status{
      font-size:13px;
    }

    .order-link-btn{
      font-size:12px;
      font-weight:700;
      color:var(--brand);
    }

    .order-link-btn:hover{
      color:var(--brand-dark);
      text-decoration:underline;
    }

    .address-header-row h2,
    .upi-header-row h2,
    .wishlist-header h2{
      font-size:20px;
    }

    .address-add-btn{
      border:1px solid #99f6e4;
      background:var(--brand-soft);
      color:var(--brand);
      border-radius:10px;
      font-weight:700;
      padding:9px 12px;
    }

    .address-card{
      border:1px solid #e2e8f0;
      border-radius:14px;
      padding:14px;
      box-shadow:0 4px 12px rgba(2,6,23,0.04);
    }

    .address-name-line{
      margin-bottom:6px;
      font-size:14px;
    }

    .address-actions{
      display:flex;
      align-items:center;
      gap:8px;
      flex-wrap:wrap;
    }

    .address-actions button,
    .address-actions a{
      border-radius:10px !important;
      font-weight:700 !important;
      font-size:12px !important;
      padding:7px 12px !important;
      box-shadow:0 2px 8px rgba(2,6,23,0.08);
    }

    .addr-btn{
      text-decoration:none;
      border:none;
      color:#fff;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-width:88px;
    }

    .default-btn{
      background:linear-gradient(135deg, #f59e0b, #d97706) !important;
    }

    .edit-btn{
      background:linear-gradient(135deg, #22c55e, #16a34a) !important;
    }

    .delete-btn{
      background:linear-gradient(135deg, #ef4444, #dc2626) !important;
    }

    .default-badge{
      margin-left:10px;
      font-size:11px;
      color:#0f766e;
      border:1px solid #5eead4;
      background:#ecfeff;
      padding:2px 8px;
      border-radius:999px;
      font-weight:700;
      letter-spacing:.2px;
    }

    .upi-box{
      border-radius:12px;
      overflow:hidden;
      border:1px solid #e2e8f0;
      background:#fff;
    }

    .upi-row{
      padding:12px 14px;
    }

    .upi-logo{
      width:36px;
      height:36px;
      border-radius:10px;
      margin-right:10px;
    }

    .reviews-empty-wrap{
      border:1px dashed #cbd5e1;
      border-radius:14px;
      background:#f8fafc;
    }

    .wishlist-item{
      border:1px solid #e2e8f0;
      border-radius:14px;
      margin-bottom:10px;
      box-shadow:0 4px 12px rgba(2,6,23,0.04);
      border-bottom:1px solid #e2e8f0;
    }

    .wishlist-item:last-child{
      border-bottom:1px solid #e2e8f0;
    }

    .wishlist-thumb img{
      border-radius:12px;
      border:1px solid #e2e8f0;
      background:#fff;
    }

    .wishlist-title a{
      color:#111827;
    }

    .wishlist-title a:hover{
      color:var(--brand);
    }

    .wishlist-delete{
      color:#94a3b8;
      transition:color .2s ease;
    }

    .wishlist-delete:hover{
      color:#ef4444;
    }

    .address-modal-content{
      border-radius:16px;
      border:1px solid #e2e8f0;
      box-shadow:0 20px 40px rgba(2,6,23,0.2);
      max-height:88vh;
      overflow:auto;
    }

    .address-modal-header{
      padding-bottom:10px;
      border-bottom:1px solid #edf2f8;
    }

    .address-modal-close{
      width:32px;
      height:32px;
      border-radius:8px;
      background:#f1f5f9;
      color:#334155;
    }

    #section-reviews > h2,
    #section-notifications > h2{
      font-size:20px !important;
      font-weight:700 !important;
      margin-bottom:12px !important;
      color:#111827 !important;
    }

    .section-main-title{
      font-size:20px;
      font-weight:700;
      margin-bottom:10px;
      color:#111827;
    }

    .reviews-list,
    .notif-list{
      display:flex;
      flex-direction:column;
      gap:10px;
      margin-top:10px;
    }

    .review-card{
      border:1px solid #e2e8f0;
      border-radius:12px;
      padding:12px;
      display:flex;
      gap:10px;
      background:#fff;
      box-shadow:0 4px 12px rgba(2,6,23,0.04);
    }

    .review-thumb-img{
      width:60px;
      height:60px;
      object-fit:cover;
      border:1px solid #e2e8f0;
      border-radius:10px;
      background:#fff;
    }

    .review-order-body{
      flex:1;
    }

    .review-order-name{
      font-size:14px;
      font-weight:600;
      margin-bottom:2px;
      color:#111827;
    }

    .review-stars-row{
      display:flex;
      align-items:center;
      gap:8px;
      margin-bottom:4px;
    }

    .review-stars{
      font-size:14px;
      color:#f59e0b;
    }

    .review-comment{
      font-size:12px;
      color:#475569;
      margin-bottom:4px;
      line-height:1.55;
    }

    .review-date{
      font-size:11px;
      color:#94a3b8;
    }

    .review-link-row{
      margin-top:7px;
      font-size:12px;
    }

    .review-link{
      color:var(--brand);
      text-decoration:none;
      font-weight:700;
    }

    .review-link:hover{
      text-decoration:underline;
    }

    .notif-card{
      border:1px solid #e2e8f0;
      border-radius:12px;
      padding:12px;
      box-shadow:0 4px 12px rgba(2,6,23,0.04);
    }

    .notif-card.unread{
      background:#f8fafc;
      border-left:4px solid var(--brand);
    }

    .notif-card.read{
      background:#fff;
    }

    .notif-title{
      font-size:14px;
      font-weight:700;
      margin-bottom:4px;
      color:#0f172a;
    }

    .notif-message{
      font-size:13px;
      color:#475569;
      margin-bottom:6px;
      line-height:1.55;
    }

    .notif-date{
      font-size:11px;
      color:#94a3b8;
    }

    .empty-state{
      font-size:13px;
      color:#64748b;
      border:1px dashed #cbd5e1;
      border-radius:12px;
      background:#f8fafc;
      padding:14px;
    }

    .empty-state.centered{
      text-align:center;
    }

    .subscription-shell{
      display:grid;
      gap:20px;
    }

    .subscription-hero{
      display:grid;
      grid-template-columns:minmax(0, 1.55fr) minmax(320px, .95fr);
      gap:18px;
      align-items:stretch;
    }

    .subscription-card,
    .subscription-mini-card,
    .subscription-list-card{
      position:relative;
      overflow:hidden;
      border:1px solid #dbe7df;
      border-radius:24px;
      background:#fff;
      box-shadow:0 22px 46px rgba(15,23,42,0.08);
    }

    .subscription-card{
      padding:28px;
      background:
        radial-gradient(circle at top left, rgba(222,247,236,0.96), transparent 34%),
        radial-gradient(circle at bottom right, rgba(255,238,214,0.85), transparent 28%),
        linear-gradient(135deg, #ffffff 0%, #f7fbf8 56%, #fffaf2 100%);
    }

    .subscription-card::after{
      content:"";
      position:absolute;
      inset:0 auto auto 0;
      width:100%;
      height:8px;
      background:linear-gradient(90deg, #0f766e 0%, #2ca58d 42%, #f4b860 100%);
    }

    .subscription-hero-top{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:18px;
      margin-bottom:18px;
    }

    .subscription-heading{
      max-width:640px;
    }

    .subscription-kicker{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:8px 12px;
      border-radius:999px;
      background:rgba(15,118,110,0.08);
      color:var(--brand-dark);
      font-size:11px;
      font-weight:700;
      text-transform:uppercase;
      letter-spacing:.16em;
    }

    .subscription-kicker::before{
      content:"";
      width:8px;
      height:8px;
      border-radius:50%;
      background:#f4b860;
      box-shadow:0 0 0 4px rgba(244,184,96,0.18);
    }

    .subscription-title{
      margin-top:16px;
      font-size:34px;
      line-height:1.02;
      letter-spacing:-.03em;
      color:#10221f;
      font-weight:800;
    }

    .subscription-summary{
      margin-top:12px;
      max-width:680px;
      font-size:15px;
      line-height:1.75;
      color:#46615a;
    }

    .subscription-badge{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:10px 14px;
      border-radius:999px;
      font-size:12px;
      font-weight:800;
      letter-spacing:.08em;
      text-transform:uppercase;
      border:1px solid transparent;
      white-space:nowrap;
      flex-shrink:0;
    }

    .subscription-badge.active{
      background:#ecfdf5;
      color:#047857;
      border-color:#a7f3d0;
    }

    .subscription-badge.scheduled{
      background:#fff7ed;
      color:#c2410c;
      border-color:#fdba74;
    }

    .subscription-badge.inactive{
      background:#f8fafc;
      color:#475569;
      border-color:#cbd5e1;
    }

    .subscription-pulse-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0,1fr));
      gap:14px;
      margin-bottom:18px;
    }

    .subscription-pulse{
      padding:18px;
      border-radius:20px;
      background:linear-gradient(135deg, #123e38 0%, #0f766e 100%);
      color:#eefcf8;
      box-shadow:0 18px 34px rgba(15,118,110,0.18);
    }

    .subscription-pulse.warm{
      background:linear-gradient(135deg, #fff4e2 0%, #ffe7bf 100%);
      color:#7c3f00;
      box-shadow:none;
      border:1px solid #f7cf8a;
    }

    .subscription-pulse-label{
      display:block;
      font-size:11px;
      text-transform:uppercase;
      letter-spacing:.15em;
      opacity:.78;
      margin-bottom:8px;
    }

    .subscription-pulse strong{
      display:block;
      font-size:20px;
      line-height:1.2;
      font-weight:800;
    }

    .subscription-pulse small{
      display:block;
      margin-top:8px;
      font-size:12px;
      line-height:1.55;
      opacity:.88;
    }

    .subscription-stat-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0,1fr));
      gap:16px;
      margin-top:20px;
    }

    .subscription-stat-card{
      position:relative;
      display:flex;
      flex-direction:column;
      gap:8px;
      min-height:148px;
      border:1px solid #d4e8de;
      border-radius:18px;
      background:linear-gradient(180deg, #ffffff 0%, #f7fbf8 100%);
      padding:18px 18px 16px;
      box-shadow:0 10px 24px rgba(15, 23, 42, 0.06);
      overflow:hidden;
    }

    .subscription-stat-card::before{
      content:"";
      position:absolute;
      left:0;
      top:0;
      bottom:0;
      width:4px;
      background:#0f766e;
      opacity:.78;
    }

    .subscription-stat-card:nth-child(2)::before{
      background:#0ea5a4;
    }

    .subscription-stat-card:nth-child(3)::before{
      background:#2563eb;
    }

    .subscription-stat-card:nth-child(4)::before{
      background:#d97706;
    }

    .subscription-stat-label{
      display:block;
      font-size:11px;
      text-transform:uppercase;
      letter-spacing:.12em;
      color:#6d857d;
      margin-bottom:2px;
      font-weight:700;
    }

    .subscription-stat-value{
      display:block;
      font-size:30px;
      line-height:1.08;
      font-weight:800;
      color:#10221f;
      letter-spacing:-.02em;
      font-variant-numeric:tabular-nums;
      word-break:break-word;
    }

    .subscription-stat-note{
      display:block;
      margin-top:auto;
      padding-top:9px;
      border-top:1px solid #e7f0ec;
      font-size:12.5px;
      color:#547069;
      line-height:1.5;
    }

    .subscription-meta-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0,1fr));
      gap:14px;
      margin-top:18px;
    }

    .subscription-meta{
      background:#fcfffd;
      border:1px solid #dce9e2;
      border-radius:18px;
      padding:16px 18px;
    }

    .subscription-meta-label{
      display:block;
      font-size:11px;
      text-transform:uppercase;
      letter-spacing:.15em;
      color:#7b9088;
      margin-bottom:6px;
      font-weight:700;
    }

    .subscription-meta-value{
      font-size:15px;
      line-height:1.45;
      font-weight:700;
      color:#17302c;
    }

    .subscription-reminder-banner{
      margin-top:18px;
      display:flex;
      align-items:flex-start;
      gap:14px;
      border-radius:18px;
      border:1px solid #f4c57a;
      background:linear-gradient(135deg, #fff8ee 0%, #fff1d7 100%);
      color:#98570b;
      padding:16px 18px;
      box-shadow:0 12px 24px rgba(244,184,96,0.14);
    }

    .subscription-reminder-banner i{
      margin-top:2px;
    }

    .subscription-reminder-banner strong{
      color:#7c3f00;
    }

    .subscription-benefit-title{
      margin-top:22px;
      margin-bottom:12px;
      font-size:13px;
      font-weight:800;
      color:#17302c;
      text-transform:uppercase;
      letter-spacing:.12em;
    }

    .subscription-benefits{
      display:grid;
      grid-template-columns:repeat(2, minmax(0,1fr));
      gap:12px;
    }

    .subscription-benefit{
      display:flex;
      align-items:flex-start;
      gap:12px;
      padding:15px 16px;
      border-radius:18px;
      border:1px solid #dce9e2;
      background:#fff;
      font-size:13px;
      color:#47625b;
      line-height:1.6;
    }

    .subscription-benefit i{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:24px;
      height:24px;
      border-radius:50%;
      background:#ecfdf5;
      color:#0f766e;
      margin-top:1px;
      flex-shrink:0;
    }

    .subscription-action-row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
      flex-wrap:wrap;
      margin-top:22px;
    }

    .subscription-cta{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      min-width:220px;
      padding:15px 22px;
      border-radius:999px;
      background:linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
      color:#fff;
      font-weight:800;
      letter-spacing:.02em;
      text-decoration:none;
      box-shadow:0 18px 30px rgba(15,118,110,0.22);
      transition:transform .18s ease, box-shadow .18s ease, opacity .18s ease;
    }

    .subscription-cta:hover{
      transform:translateY(-1px);
      box-shadow:0 22px 36px rgba(15,118,110,0.28);
    }

    .subscription-cta-note{
      max-width:360px;
      font-size:13px;
      line-height:1.65;
      color:#5f756f;
    }

    .subscription-mini-card{
      padding:26px;
      background:
        radial-gradient(circle at top right, rgba(60,148,130,0.34), transparent 24%),
        linear-gradient(180deg, #0f3b36 0%, #102d2a 100%);
      border-color:#134842;
      color:#eaf6f2;
    }

    .subscription-side-kicker{
      display:inline-flex;
      align-items:center;
      gap:8px;
      color:#f4d49b;
      font-size:11px;
      text-transform:uppercase;
      letter-spacing:.16em;
      font-weight:700;
    }

    .subscription-side-kicker::before{
      content:"";
      width:7px;
      height:7px;
      border-radius:50%;
      background:#f4b860;
    }

    .subscription-mini-card h4{
      margin-top:14px;
      font-size:26px;
      line-height:1.08;
      font-weight:800;
      color:#fff;
    }

    .subscription-side-copy{
      margin-top:10px;
      font-size:14px;
      line-height:1.7;
      color:rgba(234,246,242,0.78);
    }

    .subscription-summary-stack{
      display:grid;
      gap:10px;
      margin-top:20px;
    }

    .subscription-summary-line{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:16px;
      padding:14px 16px;
      border-radius:18px;
      border:1px solid rgba(255,255,255,0.08);
      background:rgba(255,255,255,0.05);
      font-size:13px;
      line-height:1.5;
      color:rgba(234,246,242,0.74);
    }

    .subscription-summary-line strong{
      color:#fff;
      text-align:right;
      max-width:58%;
      font-weight:700;
    }

    .subscription-side-spotlight{
      margin-top:18px;
      padding:18px;
      border-radius:20px;
      background:linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03));
      border:1px solid rgba(255,255,255,0.09);
    }

    .subscription-side-spotlight-title{
      font-size:11px;
      text-transform:uppercase;
      letter-spacing:.16em;
      color:#f4d49b;
      font-weight:700;
    }

    .subscription-side-spotlight-value{
      display:block;
      margin-top:10px;
      font-size:28px;
      line-height:1;
      font-weight:800;
      color:#fff;
    }

    .subscription-side-spotlight-note{
      display:block;
      margin-top:10px;
      font-size:13px;
      line-height:1.65;
      color:rgba(234,246,242,0.74);
    }

    .subscription-data-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0,1fr));
      gap:18px;
    }

    .subscription-list-card{
      padding:0;
    }

    .subscription-list-head{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:16px;
      padding:22px 22px 18px;
      border-bottom:1px solid #e9f0ec;
      background:linear-gradient(180deg, #ffffff 0%, #f8fbf9 100%);
    }

    .subscription-list-kicker{
      display:block;
      margin-bottom:8px;
      font-size:11px;
      text-transform:uppercase;
      letter-spacing:.16em;
      color:#82958e;
      font-weight:700;
    }

    .subscription-list-card h4{
      font-size:20px;
      line-height:1.15;
      font-weight:800;
      color:#10221f;
      margin:0;
    }

    .subscription-list-subtitle{
      margin-top:8px;
      font-size:13px;
      line-height:1.6;
      color:#647771;
    }

    .subscription-list-count{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-width:42px;
      height:42px;
      padding:0 14px;
      border-radius:999px;
      background:#f0fdf7;
      color:#0f766e;
      font-size:13px;
      font-weight:800;
      border:1px solid #b7ead3;
      flex-shrink:0;
    }

    .subscription-list-body{
      padding:18px 22px 22px;
    }

    .subscription-list-empty{
      padding:18px;
      border-radius:18px;
      border:1px dashed #cfe0d8;
      background:#f8fbf9;
      color:#637670;
      font-size:13px;
      line-height:1.7;
    }

    .subscription-table{
      width:100%;
      border-collapse:separate;
      border-spacing:0 10px;
      font-size:13px;
    }

    .subscription-table th{
      text-align:left;
      padding:0 12px 4px;
      font-size:11px;
      text-transform:uppercase;
      letter-spacing:.14em;
      color:#8ca099;
      font-weight:700;
    }

    .subscription-table td{
      padding:14px 12px;
      vertical-align:top;
      background:#f8fbf9;
      border-top:1px solid #e5efea;
      border-bottom:1px solid #e5efea;
      color:#34504a;
      line-height:1.55;
    }

    .subscription-table td:first-child{
      border-left:1px solid #e5efea;
      border-radius:16px 0 0 16px;
    }

    .subscription-table td:last-child{
      border-right:1px solid #e5efea;
      border-radius:0 16px 16px 0;
    }

    .subscription-table strong{
      color:#10221f;
    }

    .subscription-status-pill{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:7px 10px;
      border-radius:999px;
      font-size:11px;
      font-weight:800;
      text-transform:uppercase;
      letter-spacing:.08em;
      border:1px solid transparent;
      white-space:nowrap;
    }

    .subscription-status-pill.active{
      background:#ecfdf5;
      color:#047857;
      border-color:#a7f3d0;
    }

    .subscription-status-pill.scheduled,
    .subscription-status-pill.pending{
      background:#fff7ed;
      color:#b45309;
      border-color:#fdba74;
    }

    .subscription-status-pill.expired,
    .subscription-status-pill.failed{
      background:#fff1f2;
      color:#be123c;
      border-color:#fda4af;
    }

    .subscription-status-pill.completed{
      background:#eff6ff;
      color:#1d4ed8;
      border-color:#bfdbfe;
    }

    @media (max-width:1100px){
      .subscription-hero,
      .subscription-data-grid{
        grid-template-columns:1fr;
      }

      .subscription-stat-grid{
        grid-template-columns:repeat(2, minmax(0,1fr));
      }
    }

    @media (max-width:720px){
      .subscription-card,
      .subscription-mini-card{
        padding:22px 18px;
      }

      .subscription-hero-top,
      .subscription-action-row,
      .subscription-list-head{
        flex-direction:column;
      }

      .subscription-title{
        font-size:28px;
      }

      .subscription-pulse-grid,
      .subscription-stat-grid,
      .subscription-meta-grid,
      .subscription-benefits{
        grid-template-columns:1fr;
      }

      .subscription-table{
        display:block;
        overflow-x:auto;
        white-space:nowrap;
      }
    }

    .address-default-check{
      margin-bottom:12px;
      font-size:13px;
      color:#334155;
    }

    .address-modal-actions{
      text-align:right;
      margin-top:10px;
    }

    @media (max-width:980px){
      .account-wrapper{
        margin:24px auto 42px;
      }

      .account-main{
        padding:12px;
      }

      .account-section{
        padding:16px;
      }

      .orders-search{
        width:100%;
      }

      .orders-search input{
        min-width:0;
        width:100%;
      }
    }

    @media (max-width:900px){
      .account-sidebar{
        position:relative;
        top:0;
      }

      .account-main{
        min-height:560px;
      }
    }

    @media (max-width:640px){
      .account-wrapper{
        padding:0 10px;
      }

      .orders-header-row h2,
      .section-heading-row h2,
      .address-header-row h2,
      .upi-header-row h2,
      .wishlist-header h2{
        font-size:18px;
      }

      .order-card{
        padding:12px;
      }

      .order-product-img img{
        width:74px;
        height:74px;
      }

      .address-actions{
        width:100%;
      }

      .address-actions .addr-btn{
        min-width:0;
      }
    }

    /* Final mobile override: right-to-left slide panel for mobile sections. */
    @media (max-width:900px){
      .account-wrapper{
        display:block;
        overflow:visible;
        padding:0 12px 24px;
        position:relative;
        --mobile-panel-top-offset:0px;
      }

      .account-sidebar{
        position:relative;
        width:100%;
        max-width:100%;
        z-index:1;
      }

      .account-sidebar{
        margin-bottom:16px;
      }

      .mobile-panel-backdrop{
        position:fixed;
        top:var(--mobile-panel-top-offset, 0px);
        left:0;
        right:0;
        height:calc(100dvh - var(--mobile-panel-top-offset, 0px));
        border:none;
        padding:0;
        margin:0;
        width:100%;
        background:rgba(2,6,23,0.48);
        opacity:0;
        pointer-events:none;
        transition:opacity .28s ease;
        z-index:2300;
      }

      .account-main{
        position:fixed;
        top:var(--mobile-panel-top-offset, 0px);
        right:0;
        width:min(100vw, 700px);
        max-width:100%;
        height:calc(100dvh - var(--mobile-panel-top-offset, 0px));
        margin:0;
        min-height:0;
        overflow-y:auto;
        overflow-x:hidden;
        padding:14px;
        z-index:2301;
        border-radius:0;
        transform:translateX(104%);
        transition:transform .32s cubic-bezier(.22,.8,.22,1);
        box-shadow:-26px 0 46px rgba(15,23,42,0.24);
        will-change:transform;
      }

      .account-wrapper.show-main .account-main{
        transform:translateX(0);
      }

      .account-wrapper.show-main .account-sidebar{
        transform:none !important;
      }

      .account-wrapper.show-main .mobile-panel-backdrop{
        opacity:1;
        pointer-events:auto;
      }

      .mobile-back-row{
        display:flex;
        align-items:center;
        position:sticky;
        top:0;
        z-index:3;
        margin-bottom:10px;
        background:rgba(255,255,255,0.96);
        backdrop-filter:blur(8px);
      }
    }

    @media (max-width:640px){
      .account-wrapper{
        padding:0 8px 20px;
      }

      .account-main{
        padding:10px;
      }

      .account-section{
        padding:14px;
        border-radius:14px;
      }

      .mobile-back-row{
        padding:10px 8px;
      }

      .sidebar-link{
        margin:4px 8px;
        padding:10px 11px;
      }
    }

    /* Brand alignment override: match site gold/olive palette used across navbar/pages */
    :root{
      --primary:#D4AF37;
      --primary-dark:#B89026;
      --brand:#D4AF37;
      --brand-dark:#B89026;
      --brand-soft:#fff8e5;
      --surface:#ffffff;
      --surface-soft:#fffdf7;
      --text-strong:#2f2718;
      --text-muted:#726449;
      --line:#eadfbf;
      --shadow:0 16px 36px rgba(93, 76, 38, 0.12);
    }

    body{
      background:
        radial-gradient(1200px 520px at -10% -18%, rgba(212,175,55,0.18), transparent 56%),
        radial-gradient(980px 460px at 112% -8%, rgba(59,80,44,0.14), transparent 52%),
        #f8f4ea;
      color:var(--text-strong);
    }

    .account-wrapper{
      margin-top:30px;
    }

    .account-sidebar{
      border:1px solid var(--line);
      box-shadow:var(--shadow);
    }

    .sidebar-header{
      background:
        linear-gradient(140deg, rgba(212,175,55,0.2) 0%, rgba(255,255,255,1) 62%);
      border-bottom:1px solid var(--line);
    }

    .avatar{
      background:linear-gradient(135deg, #f6e1a0 0%, #efd07a 100%);
      color:#5a4517;
      box-shadow:0 6px 14px rgba(184,144,38,0.22);
    }

    .sidebar-header-text span{
      color:#8c7a55;
    }

    .sidebar-header-text strong{
      color:#3d321b;
    }

    .sidebar-title{
      color:#9a8453;
    }

    .sidebar-link{
      color:#4f4228;
    }

    .sidebar-link:hover{
      background:#fff7e2;
      border-color:#ecd38a;
    }

    .sidebar-link.active{
      color:#8a6912;
      border-color:#e6c66f;
      background:linear-gradient(135deg, #fff8e7 0%, #fff2cf 100%);
      box-shadow:inset 0 0 0 1px rgba(212,175,55,0.25);
    }

    .sidebar-link span:last-child{
      color:#b29453;
    }

    .account-main{
      background:var(--surface-soft);
      border:1px solid var(--line);
      box-shadow:var(--shadow);
    }

    .account-section{
      border:1px solid #ecdfbb;
      background:linear-gradient(180deg, #ffffff 0%, #fffdf7 100%);
      box-shadow:0 10px 24px rgba(103,82,41,0.06);
    }

    .section-heading-row h2,
    .orders-header-row h2,
    .address-header-row h2,
    .upi-header-row h2,
    .wishlist-header h2{
      color:#3b2f18;
    }

    .section-subtext,
    .faq-a,
    .order-meta,
    .order-status-text,
    .address-text,
    .upi-info-id,
    .wishlist-meta{
      color:#76684c;
    }

    .form-input{
      border-color:#dfcfaa;
      background:#fff;
    }

    .form-input:focus{
      border-color:var(--primary);
      box-shadow:0 0 0 1px rgba(212,175,55,.35);
    }

    .profile-edit-link,
    .order-link-btn,
    .coupon-tnc,
    .danger-links a{
      color:#936f13;
    }

    .orders-search button,
    .address-save-btn{
      background:linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color:#2e230d;
      font-weight:700;
    }

    .orders-search button:hover,
    .address-save-btn:hover{
      background:linear-gradient(135deg, #c7a031 0%, #aa8320 100%);
    }

    .address-add-btn{
      border-color:#e4d3ab;
      background:#fff9ea;
      color:#8f6d14;
    }

    .order-card,
    .address-card,
    .upi-box,
    .coupon-card,
    .wishlist-item,
    .review-card,
    .notif-card{
      border-color:#e8dcba;
    }

    .order-status.delivered{
      color:#5f7b1e;
    }

    .order-status.cancelled{
      color:#b33c2f;
    }

    .wishlist-discount,
    .coupon-title{
      color:#6b7f2b;
    }

    .modal-backdrop{
      background:rgba(47,39,24,0.45);
    }

    .address-modal-content{
      border:1px solid #e4d7b6;
      box-shadow:0 16px 34px rgba(88,70,35,0.22);
    }

    .mobile-back-btn{
      border-color:#ead08c;
      background:#fff4d7;
      color:#7d5d0f;
    }

    .mobile-back-btn:hover{
      background:#ffedbf;
    }

    .mobile-section-label{
      color:#7a6640;
    }

    .subscription-card,
    .subscription-mini-card,
    .subscription-list-card{
      border-color:#e2d2a5;
      box-shadow:0 20px 40px rgba(90,73,36,0.12);
    }

    .subscription-card{
      background:
        radial-gradient(circle at top left, rgba(255,238,194,0.82), transparent 34%),
        radial-gradient(circle at bottom right, rgba(205,178,107,0.24), transparent 30%),
        linear-gradient(135deg, #ffffff 0%, #fffdf7 58%, #fff7e7 100%);
    }

    .subscription-card::after{
      background:linear-gradient(90deg, #3B502C 0%, #D4AF37 55%, #f2cf7a 100%);
    }

    .subscription-kicker{
      background:rgba(212,175,55,0.16);
      color:#6f5617;
    }

    .subscription-kicker::before{
      background:#D4AF37;
      box-shadow:0 0 0 4px rgba(212,175,55,0.2);
    }

    .subscription-title{
      color:#3d2f18;
    }

    .subscription-summary{
      color:#6e5f44;
    }

    .subscription-badge.active{
      background:#eef5e5;
      color:#4e6520;
      border-color:#cddfb0;
    }

    .subscription-badge.scheduled{
      background:#fff6e6;
      color:#915d0a;
      border-color:#f0c979;
    }

    .subscription-pulse{
      background:linear-gradient(135deg, #3b502c 0%, #516d3d 100%);
      color:#f4f9ee;
      box-shadow:0 16px 30px rgba(59,80,44,0.22);
    }

    .subscription-pulse.warm{
      background:linear-gradient(135deg, #fff7e6 0%, #fce8b9 100%);
      color:#7b4c05;
      border-color:#eac779;
      box-shadow:none;
    }

    .subscription-stat-card{
      border-color:#ead8ad;
      background:linear-gradient(180deg, #ffffff 0%, #fffaf0 100%);
      box-shadow:0 10px 24px rgba(93, 75, 37, 0.09);
    }

    .subscription-stat-card::before{
      background:#b89026;
      opacity:.85;
    }

    .subscription-stat-card:nth-child(2)::before{
      background:#3b502c;
    }

    .subscription-stat-card:nth-child(3)::before{
      background:#9f7f27;
    }

    .subscription-stat-card:nth-child(4)::before{
      background:#5a7134;
    }

    .subscription-stat-label{
      color:#8f7848;
    }

    .subscription-stat-value{
      color:#3a2d17;
    }

    .subscription-stat-note{
      border-top-color:#efe1c0;
      color:#6f6247;
    }

    .subscription-meta{
      background:#fffcf5;
      border-color:#eadbb6;
    }

    .subscription-meta-label{
      color:#8f7a4f;
    }

    .subscription-meta-value{
      color:#4b3a1d;
    }

    .subscription-reminder-banner{
      border-color:#e8c67e;
      background:linear-gradient(135deg, #fff8ea 0%, #ffefca 100%);
      color:#895708;
      box-shadow:0 10px 22px rgba(184,144,38,0.16);
    }

    .subscription-benefit{
      border-color:#eadab2;
      background:#fffdf8;
      color:#67583d;
    }

    .subscription-benefit i{
      background:#fff1ce;
      color:#8f6c12;
    }

    .subscription-cta{
      background:linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color:#2e230d;
      box-shadow:0 14px 26px rgba(184,144,38,0.28);
    }

    .subscription-cta:hover{
      box-shadow:0 18px 30px rgba(184,144,38,0.34);
    }

    .subscription-cta-note{
      color:#6c5d41;
    }

    .subscription-mini-card{
      background:
        radial-gradient(circle at top right, rgba(244,214,145,0.24), transparent 28%),
        linear-gradient(180deg, #3b502c 0%, #2d3d21 100%);
      border-color:#556d40;
    }

    .subscription-side-kicker,
    .subscription-side-spotlight-title{
      color:#f1d188;
    }

    .subscription-side-copy,
    .subscription-summary-line,
    .subscription-side-spotlight-note{
      color:rgba(245,238,220,0.84);
    }

    .subscription-summary-line{
      border-color:rgba(255,255,255,0.14);
      background:rgba(255,255,255,0.08);
    }

    .subscription-side-spotlight{
      border-color:rgba(255,255,255,0.16);
      background:linear-gradient(135deg, rgba(255,255,255,0.11), rgba(255,255,255,0.04));
    }

    .subscription-list-kicker{
      color:#9a7f44;
    }

    .subscription-list-subtitle{
      color:#75694e;
    }

    .subscription-list-count{
      background:#fff3d1;
      color:#896811;
      border-color:#e8ce89;
    }

    .subscription-list-body{
      border-top-color:#ecdfbb;
    }

    .subscription-table th{
      color:#6f5b30;
      background:#fff8e7;
      border-bottom-color:#e8d5a8;
    }

    .subscription-table td{
      border-bottom-color:#f0e6ca;
      color:#56452a;
    }

    .subscription-status-pill.active{
      background:#edf4e5;
      color:#4f6721;
      border-color:#cfe1b7;
    }

    .subscription-status-pill.scheduled,
    .subscription-status-pill.pending{
      background:#fff6e5;
      color:#915f0b;
      border-color:#efca84;
    }

    .subscription-status-pill.expired,
    .subscription-status-pill.failed{
      background:#fff1ee;
      color:#b03f2e;
      border-color:#efb0a5;
    }

    .subscription-status-pill.completed{
      background:#f2f7e8;
      color:#4d6520;
      border-color:#d2e1bb;
    }

    body.mobile-panel-open{
      overflow:hidden;
      touch-action:none;
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
      <a href="#" class="sidebar-link" data-section="subscription">
        <span>My Subscription</span>
        <span>&gt;</span>
      </a>
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

  <button type="button" class="mobile-panel-backdrop" id="mobilePanelBackdrop" aria-label="Close panel"></button>

  <main class="account-main">

    <!-- MOBILE BACK BUTTON -->
    <div class="mobile-back-row">
      <button type="button" class="mobile-back-btn"><i class="fa-solid fa-arrow-left"></i><span>Back</span></button>
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
          <p class="empty-state">You haven't placed any orders yet.</p>
        <?php else: ?>
          <?php foreach ($orders as $order): ?>
            <?php
              $stmtItem = $pdo->prepare("
                SELECT 
                  oi.qty,
                  oi.price,
                  COALESCE(p.name, oi.product_name) AS name,
                  p.images
                FROM order_items oi
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = ?
                ORDER BY (CASE WHEN p.images IS NULL OR p.images = '' THEN 1 ELSE 0 END), oi.id ASC
                LIMIT 1
              ");
              $stmtItem->execute([$order['id']]);
              $item = $stmtItem->fetch(PDO::FETCH_ASSOC);

              $productName  = $item['name'] ?? 'Product';
              $productImage = get_product_image_url($item['images'] ?? '');

              $status = strtolower($order['status'] ?? '');
              $statusClass = ($status === 'delivered') ? 'delivered' : (($status === 'cancelled') ? 'cancelled' : '');
            ?>
            <article class="order-card">
              <div class="order-left">
                <div class="order-product-img">
                  <img src="<?php echo htmlspecialchars($productImage); ?>" alt="">
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
          <p class="empty-state">
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
                    <span class="default-badge">
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
                <?php if (empty($addr['is_default'])): ?>
                  <form action="set-default-address.php" method="post" style="display:inline;">
                    <input type="hidden" name="address_id" value="<?php echo (int)$addr['id']; ?>">
                    <button type="submit" class="addr-btn default-btn" title="Set as Default">
                      Set Default
                    </button>
                  </form>
                <?php endif; ?>
                <a href="edit-address.php?id=<?php echo (int)$addr['id']; ?>" 
                   class="addr-btn edit-btn"
                   title="Edit">
                  Edit
                </a>
                <form action="delete-address.php" method="post" style="display:inline;" 
                      onsubmit="return confirm('Delete this address?');">
                  <input type="hidden" name="address_id" value="<?php echo (int)$addr['id']; ?>">
                  <button type="submit" class="addr-btn delete-btn" title="Delete">
                    Delete
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- SUBSCRIPTION SECTION -->
    <section class="account-section" id="section-subscription" style="display:none;">
      <?php
        $daysLeft = $subscriptionDashboardStats['days_left'];
        $daysLeftLabel = $daysLeft === null ? 'Not active' : ($daysLeft === 0 ? 'Ends today' : $daysLeft . ' day(s)');
        $memberSinceLabel = !empty($subscriptionDashboardStats['member_since']) ? date('d M Y', strtotime($subscriptionDashboardStats['member_since'])) : 'Not started';
        $subscriptionSpendLabel = '₹' . number_format((float)$subscriptionDashboardStats['subscription_total_paid'], 2);
        $subscriptionSavingsLabel = '₹' . number_format((float)$subscriptionDashboardStats['subscription_order_savings'], 2);
        $planHeadline = $currentSubscription['display_plan_name'] ?? ($upcomingSubscription['display_plan_name'] ?? 'No Active Subscription');
        $statusBadgeLabel = $currentSubscription ? 'Active' : ($upcomingSubscription ? 'Scheduled' : 'Inactive');
        $statusBadgeClass = $currentSubscription ? 'active' : ($upcomingSubscription ? 'scheduled' : 'inactive');
        $statusSummary = $currentSubscription
          ? 'Your membership pricing is live right now. Renewals stack automatically after the current cycle ends.'
          : ($upcomingSubscription
              ? 'Your next membership cycle is already scheduled and will activate automatically on the date below.'
              : 'Pick a plan to unlock member pricing, cleaner repeat checkout, and subscription-only perks.');
        $currentPeriodLabel = $currentSubscription
          ? date('d M Y', strtotime($currentSubscription['start_date'])) . ' - ' . date('d M Y', strtotime($currentSubscription['end_date']))
          : 'Not active yet';
        $renewalStartLabel = $upcomingSubscription ? date('d M Y', strtotime($upcomingSubscription['start_date'])) : 'Not scheduled';
        $renewalEndLabel = $upcomingSubscription
          ? date('d M Y', strtotime($upcomingSubscription['end_date']))
          : ($currentSubscription ? date('d M Y', strtotime($currentSubscription['end_date'])) : 'Not set');
        $billingCycleLabel = $currentSubscription['billing_cycle_label'] ?? ($upcomingSubscription['billing_cycle_label'] ?? 'Not set');
        $latestTransaction = $subscriptionTransactions[0] ?? null;
        $lastPaidLabel = $latestTransaction && !empty($latestTransaction['payment_date'])
          ? date('d M Y', strtotime($latestTransaction['payment_date']))
          : 'Not available';
        $renewPlanId = 0;
        if (!empty($currentSubscription['plan_id'])) {
          $renewPlanId = (int)$currentSubscription['plan_id'];
        } elseif (!empty($upcomingSubscription['plan_id'])) {
          $renewPlanId = (int)$upcomingSubscription['plan_id'];
        }
        $benefitSource = $currentSubscription ?: $upcomingSubscription;
        $benefitsList = !empty($benefitSource['benefits_list']) ? array_slice($benefitSource['benefits_list'], 0, 6) : [];
        $primaryActionNote = $currentSubscription
          ? 'Renew whenever you are ready. The next cycle starts only after your current membership ends.'
          : ($upcomingSubscription
              ? 'Need one more future cycle? You can buy another renewal and it will queue after the scheduled one.'
              : 'Choose a plan first. Member discount and plan perks will start after activation.');
        $spotlightTitle = $currentSubscription ? 'Live Benefit' : ($upcomingSubscription ? 'Queued Renewal' : 'Member Savings');
        $spotlightValue = $currentSubscription
          ? number_format((float)$currentSubscription['effective_discount_percentage'], 0) . '% OFF'
          : ($upcomingSubscription ? date('d M', strtotime($upcomingSubscription['start_date'])) : $subscriptionSavingsLabel);
        $spotlightNote = $currentSubscription
          ? (!empty($currentSubscription['effective_free_shipping']) ? 'Free shipping is included in this membership.' : 'Member pricing is applied automatically in cart and checkout.')
          : ($upcomingSubscription
              ? 'Your next cycle activates on ' . date('d M Y', strtotime($upcomingSubscription['start_date'])) . '.'
              : 'Total savings earned from orders placed with membership pricing.');
      ?>
      <div class="subscription-shell">
        <?php if ($subscriptionMessage !== ''): ?>
          <div class="alert <?php echo htmlspecialchars($subscriptionMessageClass); ?>">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($subscriptionMessage); ?></span>
          </div>
        <?php endif; ?>

        <div class="subscription-hero">
          <div class="subscription-card">
            <div class="subscription-hero-top">
              <div class="subscription-heading">
                <span class="subscription-kicker">DevElixir Membership</span>
                <h3 class="subscription-title"><?php echo htmlspecialchars($planHeadline); ?></h3>
                <p class="subscription-summary"><?php echo htmlspecialchars($statusSummary); ?></p>
              </div>
              <span class="subscription-badge <?php echo $statusBadgeClass; ?>">
                <i class="fa-solid <?php echo $currentSubscription ? 'fa-bolt' : ($upcomingSubscription ? 'fa-clock' : 'fa-circle-info'); ?>"></i>
                <?php echo htmlspecialchars($statusBadgeLabel); ?>
              </span>
            </div>

            <div class="subscription-pulse-grid">
              <div class="subscription-pulse">
                <span class="subscription-pulse-label">Current Period</span>
                <strong><?php echo htmlspecialchars($currentPeriodLabel); ?></strong>
                <small><?php echo $currentSubscription ? 'Live membership runs until ' . date('d M Y', strtotime($currentSubscription['end_date'])) . '.' : 'Activate a plan to start membership perks.'; ?></small>
              </div>
              <div class="subscription-pulse warm">
                <span class="subscription-pulse-label">Next Action</span>
                <strong><?php echo htmlspecialchars($currentSubscription ? 'Renew when ready' : ($upcomingSubscription ? 'Renewal already queued' : 'Choose your first plan')); ?></strong>
                <small><?php echo htmlspecialchars($primaryActionNote); ?></small>
              </div>
            </div>

            <div class="subscription-stat-grid">
              <div class="subscription-stat-card">
                <span class="subscription-stat-label">Days Left</span>
                <span class="subscription-stat-value"><?php echo htmlspecialchars($daysLeftLabel); ?></span>
                <span class="subscription-stat-note"><?php echo $currentSubscription ? 'Until ' . date('d M Y', strtotime($currentSubscription['end_date'])) : 'Activate a plan to start membership benefits'; ?></span>
              </div>
              <div class="subscription-stat-card">
                <span class="subscription-stat-label">Member Since</span>
                <span class="subscription-stat-value"><?php echo htmlspecialchars($memberSinceLabel); ?></span>
                <span class="subscription-stat-note"><?php echo (int)$subscriptionDashboardStats['subscription_payment_count']; ?> completed plan payment(s)</span>
              </div>
              <div class="subscription-stat-card">
                <span class="subscription-stat-label">Membership Spend</span>
                <span class="subscription-stat-value"><?php echo htmlspecialchars($subscriptionSpendLabel); ?></span>
                <span class="subscription-stat-note">Amount paid for subscription plans</span>
              </div>
              <div class="subscription-stat-card">
                <span class="subscription-stat-label">Saved On Orders</span>
                <span class="subscription-stat-value"><?php echo htmlspecialchars($subscriptionSavingsLabel); ?></span>
                <span class="subscription-stat-note"><?php echo (int)$subscriptionDashboardStats['subscription_order_count']; ?> order(s) used membership pricing</span>
              </div>
            </div>

            <div class="subscription-meta-grid">
              <div class="subscription-meta">
                <span class="subscription-meta-label">Discount</span>
                <span class="subscription-meta-value">
                  <?php echo $currentSubscription ? number_format((float)$currentSubscription['effective_discount_percentage'], 0) . '% off' : 'Not active'; ?>
                </span>
              </div>
              <div class="subscription-meta">
                <span class="subscription-meta-label">Free Shipping</span>
                <span class="subscription-meta-value">
                  <?php echo ($currentSubscription && !empty($currentSubscription['effective_free_shipping'])) ? 'Included' : 'No'; ?>
                </span>
              </div>
              <div class="subscription-meta">
                <span class="subscription-meta-label">Current Period</span>
                <span class="subscription-meta-value">
                  <?php echo $currentSubscription ? date('d M Y', strtotime($currentSubscription['start_date'])) . ' - ' . date('d M Y', strtotime($currentSubscription['end_date'])) : 'No active plan'; ?>
                </span>
              </div>
              <div class="subscription-meta">
                <span class="subscription-meta-label">Queued Renewal</span>
                <span class="subscription-meta-value">
                  <?php echo htmlspecialchars($renewalStartLabel); ?>
                </span>
              </div>
            </div>

            <?php if ($currentSubscription && $daysLeft !== null && $daysLeft <= 7): ?>
              <div class="subscription-reminder-banner">
                <i class="fa-solid fa-hourglass-half"></i>
                <div>
                  <strong>Renewal reminder:</strong>
                  your membership ends on <?php echo date('d M Y', strtotime($currentSubscription['end_date'])); ?>.
                  Buy the next cycle now and it will start automatically after this one ends.
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($benefitsList)): ?>
              <div class="subscription-benefit-title">Included With Your Membership</div>
              <div class="subscription-benefits">
                <?php foreach ($benefitsList as $benefit): ?>
                  <div class="subscription-benefit">
                    <i class="fa-solid fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($benefit); ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="subscription-action-row">
              <a class="subscription-cta" href="<?php echo $renewPlanId > 0 ? 'subscription_checkout.php?plan_id=' . $renewPlanId : 'subscription.php'; ?>">
                <i class="fa-solid fa-crown"></i>
                <?php echo $currentSubscription ? 'Renew Membership' : ($upcomingSubscription ? 'Buy Another Renewal' : 'Choose a Plan'); ?>
              </a>
              <div class="subscription-cta-note">
                <?php echo htmlspecialchars($primaryActionNote); ?>
              </div>
            </div>
          </div>

          <div class="subscription-mini-card">
            <span class="subscription-side-kicker">Membership Timeline</span>
            <h4>Lifecycle Snapshot</h4>
            <p class="subscription-side-copy">
              Track exactly where this membership stands right now, what comes next, and how much of your profile activity already uses subscriber pricing.
            </p>
            <div class="subscription-summary-stack">
              <div class="subscription-summary-line">
                <span>Status</span>
                <strong><?php echo htmlspecialchars($statusBadgeLabel); ?></strong>
              </div>
              <div class="subscription-summary-line">
                <span>Billing Cycle</span>
                <strong><?php echo htmlspecialchars($billingCycleLabel); ?></strong>
              </div>
              <div class="subscription-summary-line">
                <span>Renewal Starts</span>
                <strong><?php echo htmlspecialchars($renewalStartLabel); ?></strong>
              </div>
              <div class="subscription-summary-line">
                <span>Renewal Ends</span>
                <strong><?php echo htmlspecialchars($renewalEndLabel); ?></strong>
              </div>
              <div class="subscription-summary-line">
                <span>Last Paid</span>
                <strong><?php echo htmlspecialchars($lastPaidLabel); ?></strong>
              </div>
              <div class="subscription-summary-line">
                <span>Subscriber Orders</span>
                <strong><?php echo (int)$subscriptionDashboardStats['subscription_order_count']; ?></strong>
              </div>
            </div>
            <div class="subscription-side-spotlight">
              <span class="subscription-side-spotlight-title"><?php echo htmlspecialchars($spotlightTitle); ?></span>
              <span class="subscription-side-spotlight-value"><?php echo htmlspecialchars($spotlightValue); ?></span>
              <span class="subscription-side-spotlight-note"><?php echo htmlspecialchars($spotlightNote); ?></span>
            </div>
          </div>
        </div>

        <div class="subscription-data-grid">
          <div class="subscription-list-card">
            <div class="subscription-list-head">
              <div>
                <span class="subscription-list-kicker">History</span>
                <h4>Subscription Timeline</h4>
                <p class="subscription-list-subtitle">Review every membership cycle, whether it was active, scheduled, or already completed.</p>
              </div>
              <span class="subscription-list-count"><?php echo count($subscriptionHistory); ?></span>
            </div>
            <div class="subscription-list-body">
              <?php if (empty($subscriptionHistory)): ?>
                <div class="subscription-list-empty">No subscription history found yet. Once you activate or renew a membership, the timeline will appear here.</div>
              <?php else: ?>
                <table class="subscription-table">
                  <thead>
                    <tr>
                      <th>Plan</th>
                      <th>Status</th>
                      <th>Period</th>
                      <th>Discount</th>
                      <th>Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($subscriptionHistory as $subRow): ?>
                      <?php
                        $historyAmount = $subRow['transaction_amount'];
                        if ($historyAmount === null && isset($subRow['price_paid']) && $subRow['price_paid'] !== null) {
                          $historyAmount = (float)$subRow['price_paid'];
                        }
                        $subStatusClass = 'pending';
                        $subStatusLabel = ucfirst((string)$subRow['status']);
                        if (($subRow['status'] ?? '') === 'active' && !empty($subRow['start_date']) && strtotime($subRow['start_date']) > strtotime(date('Y-m-d'))) {
                          $subStatusClass = 'scheduled';
                          $subStatusLabel = 'Scheduled';
                        } elseif (($subRow['status'] ?? '') === 'active') {
                          $subStatusClass = 'active';
                          $subStatusLabel = 'Active';
                        } elseif (($subRow['status'] ?? '') === 'expired') {
                          $subStatusClass = 'expired';
                          $subStatusLabel = 'Expired';
                        }
                      ?>
                      <tr>
                        <td>
                          <strong><?php echo htmlspecialchars($subRow['display_plan_name']); ?></strong><br>
                          <span style="color:#70847d;"><?php echo htmlspecialchars($subRow['billing_cycle_label']); ?></span>
                        </td>
                        <td>
                          <span class="subscription-status-pill <?php echo $subStatusClass; ?>">
                            <?php echo htmlspecialchars($subStatusLabel); ?>
                          </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($subRow['start_date'])); ?> - <?php echo date('d M Y', strtotime($subRow['end_date'])); ?></td>
                        <td><?php echo number_format((float)$subRow['effective_discount_percentage'], 0); ?>%</td>
                        <td><?php echo $historyAmount !== null ? '₹' . number_format((float)$historyAmount, 2) : '-'; ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          </div>

          <div class="subscription-list-card">
            <div class="subscription-list-head">
              <div>
                <span class="subscription-list-kicker">Payments</span>
                <h4>Payment History</h4>
                <p class="subscription-list-subtitle">Track completed, pending, or manual subscription payments recorded for this account.</p>
              </div>
              <span class="subscription-list-count"><?php echo count($subscriptionTransactions); ?></span>
            </div>
            <div class="subscription-list-body">
              <?php if (empty($subscriptionTransactions)): ?>
                <div class="subscription-list-empty">No subscription payments recorded yet. Manual activation and checkout payments will show here.</div>
              <?php else: ?>
                <table class="subscription-table">
                  <thead>
                    <tr>
                      <th>Plan</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Method</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($subscriptionTransactions as $tx): ?>
                      <?php
                        $txStatus = strtolower((string)($tx['payment_status'] ?? 'pending'));
                        $txStatusClass = in_array($txStatus, ['completed', 'pending', 'failed'], true) ? $txStatus : 'pending';
                        $txDate = $tx['payment_date'] ?: $tx['created_at'];
                      ?>
                      <tr>
                        <td>
                          <strong><?php echo htmlspecialchars($tx['plan_name'] ?? 'Subscription'); ?></strong><br>
                          <span style="color:#70847d;">Txn #<?php echo (int)$tx['id']; ?></span>
                        </td>
                        <td>₹<?php echo number_format((float)$tx['amount'], 2); ?></td>
                        <td>
                          <span class="subscription-status-pill <?php echo $txStatusClass; ?>">
                            <?php echo htmlspecialchars(ucfirst($txStatus)); ?>
                          </span>
                        </td>
                        <td><?php echo htmlspecialchars($tx['payment_method'] ?: 'Pending'); ?></td>
                        <td><?php echo $txDate ? date('d M Y, h:i A', strtotime($txDate)) : '-'; ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- UPI SECTION -->
    <section class="account-section" id="section-upi" style="display:none;">
      <div class="upi-header-row">
        <h2>Manage Saved UPI</h2>
      </div>

      <div class="upi-box">
        <?php if (empty($userUpis)): ?>
          <div class="empty-state centered">
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
        <h2 class="section-main-title">
          My Reviews &amp; Ratings
        </h2>

        <div class="reviews-list">
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
            <div class="review-card">
              <div class="review-order-thumb">
                <img src="<?php echo htmlspecialchars($thumb); ?>" alt="" 
                     class="review-thumb-img">
              </div>

              <div class="review-order-body">
                <div class="review-order-name">
                  <?php echo htmlspecialchars($rev['product_name']); ?>
                </div>

                <div class="review-stars-row">
                  <div class="review-stars">
                    <?php echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars); ?>
                  </div>
                </div>


                <div class="review-comment">
                  <?php echo nl2br(htmlspecialchars($rev['comment'])); ?>
                </div>

                <div class="review-date">
                  Reviewed on <?php echo date('d M Y', strtotime($rev['created_at'])); ?>
                </div>

                <div class="review-link-row">
                  <a href="product_view.php?id=<?php echo (int)$rev['product_id']; ?>" 
                     class="review-link">
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
        <p class="empty-state">No new notifications.</p>
      <?php else: ?>
        <div class="notif-list">
          <?php foreach ($notifications as $notif): ?>
            <div class="notif-card <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>">
              <div class="notif-title">
                <?php echo htmlspecialchars($notif['title']); ?>
              </div>
              <div class="notif-message">
                <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
              </div>
              <div class="notif-date">
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
          <p class="empty-state">Your wishlist is empty.</p>
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
              <div class="wishlist-delete" title="Remove" data-wishlist-id="<?php echo $item['wishlist_id']; ?>" data-product-id="<?php echo $item['id']; ?>">&#128465;</div>
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

      <div class="address-default-check">
        <label>
          <input type="checkbox" name="is_default" value="1">
          Set as default address
        </label>
      </div>

      <div class="address-modal-actions">
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
    const mobilePanelBackdrop = document.getElementById('mobilePanelBackdrop');
    
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

    function syncMobilePanelOffset() {
      if (!wrapper) return;

      let offset = 0;
      if (isMobile()) {
        const mobileHeader = document.querySelector('.mobile-header');
        if (mobileHeader) {
          const headerStyle = window.getComputedStyle(mobileHeader);
          const isVisible = headerStyle.display !== 'none' && headerStyle.visibility !== 'hidden';
          if (isVisible) {
            const rect = mobileHeader.getBoundingClientRect();
            if (rect.height > 0) {
              // Use the visible lower edge so panel starts below sticky/fixed header + marquee area.
              offset = Math.max(0, Math.round(rect.bottom));
            }
          }
        }
      }

      wrapper.style.setProperty('--mobile-panel-top-offset', offset + 'px');
    }

    function openMobilePanel(groupTitle) {
      if (!isMobile()) return;
      syncMobilePanelOffset();
      wrapper.classList.add('show-main');
      document.body.classList.add('mobile-panel-open');
      if (mobileSectionLabel) {
        mobileSectionLabel.textContent = groupTitle || 'Account';
      }
    }

    function closeMobilePanel(resetToProfile = false) {
      wrapper.classList.remove('show-main');
      document.body.classList.remove('mobile-panel-open');

      if (!resetToProfile) return;

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
    }

    // Check URL param for tab
    syncMobilePanelOffset();

    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        const targetLink = document.querySelector(`.sidebar-link[data-section="${tabParam}"]`);
        if (targetLink) {
            // Deactivate all
            sidebarLinks.forEach(l => l.classList.remove('active'));
            sections.forEach(s => s.style.display = 'none');
            
            // Activate target
            targetLink.classList.add('active');
            const targetSection = document.getElementById(`section-${tabParam}`);
            if (targetSection) {
                targetSection.style.display = 'block';
            }
            
            // Mobile view adjustment
            if (isMobile()) {
                const sectionTitleEl = targetLink.closest('.sidebar-section')?.querySelector('.sidebar-title');
                openMobilePanel(sectionTitleEl ? sectionTitleEl.textContent.trim() : 'Account');
            }
        }
    }

    // Sidebar navigation - Event Delegation
    document.addEventListener('click', function(e) {
      const link = e.target.closest('.sidebar-link');
      if (!link) return;

      e.preventDefault();

      // Remove active class from all links
      document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
      link.classList.add('active');

      const sectionKey = link.getAttribute('data-section');
      if (!sectionKey) return;

      // Hide all sections
      document.querySelectorAll('.account-section').forEach(sec => {
        sec.style.display = 'none';
      });

      // Show target section
      const targetSection = document.getElementById('section-' + sectionKey);
      if (targetSection) {
        targetSection.style.display = 'block';
      }

      // Mobile handling
      const sectionTitleEl = link.closest('.sidebar-section')?.querySelector('.sidebar-title');
      const groupTitle = sectionTitleEl ? sectionTitleEl.textContent.trim() : 'Account';

      if (isMobile()) {
        openMobilePanel(groupTitle);
      }
    });

    // Mobile back button
    if (mobileBackBtn) {
      mobileBackBtn.addEventListener('click', function() {
        closeMobilePanel(true);
      });
    }

    if (mobilePanelBackdrop) {
      mobilePanelBackdrop.addEventListener('click', function() {
        closeMobilePanel(false);
      });
    }

    window.addEventListener('resize', function() {
      syncMobilePanelOffset();
      if (!isMobile()) {
        wrapper.classList.remove('show-main');
        document.body.classList.remove('mobile-panel-open');
      }
    });

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

    // Wishlist Delete Functionality
    document.addEventListener('click', function(e) {
      const deleteBtn = e.target.closest('.wishlist-delete');
      if (!deleteBtn) return;

      e.preventDefault();
      e.stopPropagation();

      const productId = deleteBtn.getAttribute('data-product-id');
      const wishlistId = deleteBtn.getAttribute('data-wishlist-id');
      const wishlistItem = deleteBtn.closest('.wishlist-item');

      if (!confirm('Remove this item from your wishlist?')) return;

      // Optimistic UI update - remove immediately
      wishlistItem.style.opacity = '0.5';
      wishlistItem.style.pointerEvents = 'none';

      fetch('ajax_wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=toggle&product_id=${productId}`
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          // Remove from DOM with animation
          wishlistItem.style.transition = 'all 0.3s ease';
          wishlistItem.style.maxHeight = wishlistItem.offsetHeight + 'px';
          setTimeout(() => {
            wishlistItem.style.maxHeight = '0';
            wishlistItem.style.padding = '0';
            wishlistItem.style.margin = '0';
            wishlistItem.style.border = 'none';
          }, 10);
          
          setTimeout(() => {
            wishlistItem.remove();
            
            // Update count in header
            const wishlistList = document.querySelector('.wishlist-list');
            const remainingItems = wishlistList.querySelectorAll('.wishlist-item').length;
            const headerCount = document.querySelector('.wishlist-header h2');
            if (headerCount) {
              headerCount.textContent = `My Wishlist (${remainingItems})`;
            }
            
            // Show empty message if no items left
            if (remainingItems === 0) {
              wishlistList.innerHTML = '<p style="font-size:13px;color:#777;">Your wishlist is empty.</p>';
            }
          }, 300);
        } else {
          // Revert on error
          wishlistItem.style.opacity = '1';
          wishlistItem.style.pointerEvents = 'auto';
          alert(data.message || 'Failed to remove item');
        }
      })
      .catch(err => {
        console.error('Wishlist delete error:', err);
        wishlistItem.style.opacity = '1';
        wishlistItem.style.pointerEvents = 'auto';
        alert('Network error. Please try again.');
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

<script>
  // Check for order success
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('order_success') === 'true') {
    const orderId = urlParams.get('id');
    alert('Payment Successful! Your Order #' + orderId + ' has been placed.');
    // Clean URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
</script>

<?php include 'footer.php'; ?>

</body>
</html>
