<?php
// admin/header.php - centered menu, brand left, profile right
if (session_status() === PHP_SESSION_NONE) session_start();
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Develixirs Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* NAV WRAPPER */
    .admin-navbar {
      position: sticky;
      top: 0;
      z-index: 9999;
      background: #fff;
      border-bottom: 1px solid #eef2f7;
      box-shadow: 0 3px 14px rgba(2,6,23,0.03);
    }

    /* Use CSS grid to center the menu */
    .admin-nav-inner {
      max-width: 1250px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: auto 1fr auto; /* left | center | right */
      align-items: center;
      gap: 12px;
      padding: 12px 18px;
    }

    /* Left: brand */
    .brand-left {
      display:flex;
      align-items:center;
      gap:12px;
    }
    .brand-left a { display:flex; align-items:center; gap:10px; text-decoration:none; color:#111; }
    .brand-left img { height:40px; display:block; }
    .brand-left .brand-text { font-weight:700; font-size:18px; }

    /* Center: menu (centered) */
    .nav-center {
      display:flex;
      justify-content:center; /* center horizontally */
    }
    .nav-menu {
      display:flex;
      gap:20px;
      align-items:center;
      background:transparent;
      padding:6px 12px;
      border-radius:8px;
    }
    .nav-menu a {
      text-decoration:none;
      color:#222;
      font-weight:600;
      font-size:15px;
      padding:8px 10px;
      border-radius:6px;
    }
    .nav-menu a:hover, .nav-menu a.active {
      background:#f3f7ff;
      color:#0066cc;
    }

    /* Right: profile */
    .nav-right {
      display:flex;
      align-items:center;
      gap:12px;
      justify-self:end;
    }
    .profile-btn {
      display:flex;
      align-items:center;
      gap:8px;
      border:0;
      background:transparent;
      padding:6px 8px;
      cursor:pointer;
      border-radius:8px;
    }
    .avatar { width:36px; height:36px; border-radius:50%; object-fit:cover; background:#ddd; }

    /* profile dropdown */
    .profile-dropdown {
      position:absolute;
      right:18px; /* aligned with nav inner right */
      top:64px;
      width:200px;
      background:#fff;
      border:1px solid #eef2f7;
      box-shadow:0 10px 30px rgba(2,6,23,0.08);
      border-radius:10px;
      display:none;
      z-index:2000;
      overflow:hidden;
    }
    .profile-dropdown a { display:block; padding:10px 12px; text-decoration:none; color:#111; }
    .profile-dropdown a:hover { background:#f6f8fb; }

    /* responsive: collapse center menu to burger */
    .nav-burger {
      display:none;
      border:0;
      background:transparent;
      padding:8px;
      cursor:pointer;
    }
    @media (max-width: 920px) {
      .nav-center { justify-content:flex-end; } /* keep some layout */
      .nav-menu { display:none; } /* hide center menu */
      .nav-burger { display:inline-flex; }
      .brand-left .brand-text { display:none; } /* conserve space */
      .admin-nav-inner { grid-template-columns: auto 1fr auto; }
    }

    /* mobile slide menu */
    .mobile-menu {
      display:none;
      position:relative;
      background:#fff;
      border-bottom:1px solid #eef2f7;
    }
    .mobile-menu a { display:block; padding:12px 18px; text-decoration:none; color:#111; border-top:1px solid #f3f5f8; }
    .mobile-menu.show { display:block; }
  </style>
</head>
<body>

<header class="admin-navbar">
  <div class="admin-nav-inner">

    <!-- LEFT: Brand -->
    <div class="brand-left">
      <a href="/admin/dashboard.php">
        <img src="/assets/images/deevelixir-logo-1-copy-e1679708810866.png" alt="logo">
        <span class="brand-text">Develixirs</span>
      </a>
    </div>

    <!-- CENTER: Menu (perfectly centered) -->
    <div class="nav-center">
      <nav class="nav-menu" id="navMenu">
        <a href="/admin/dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='dashboard.php')?'active':''; ?>">Dashboard</a>
        <a href="/admin/products.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='products.php')?'active':''; ?>">Products</a>
        <a href="/admin/orders.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='orders.php')?'active':''; ?>">Orders</a>
        <a href="/admin/categories.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='categories.php')?'active':''; ?>">Categories</a>
        <a href="/admin/users.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='users.php')?'active':''; ?>">Users</a>
        <a href="/admin/coupons.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='coupons.php')?'active':''; ?>">Offers & Coupons</a>
      </nav>
    </div>

    <!-- RIGHT: Profile + burger -->
    <div class="nav-right">
      <!-- burger for mobile -->
      <button class="nav-burger" id="navBurger" aria-label="Toggle menu">
        <svg width="20" height="14" viewBox="0 0 20 14"><rect width="20" height="2" rx="1" fill="#374151"/><rect y="6" width="20" height="2" rx="1" fill="#374151"/><rect y="12" width="20" height="2" rx="1" fill="#374151"/></svg>
      </button>

      <!-- profile button -->
      <button class="profile-btn" id="profileToggle">
        <img src="/assets/images/avatar-default.png" alt="avatar" class="avatar">
        <span style="font-weight:600;"><?php echo htmlspecialchars($admin_name); ?></span>
        <svg width="12" height="12" viewBox="0 0 24 24" style="color:#666;"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
      </button>

      <!-- dropdown (hidden by default) -->
      <div class="profile-dropdown" id="profileDropdown">
        <a href="/admin/profile.php">Profile</a>
        <a href="/admin/settings.php">Settings</a>
        <a href="/admin/logout.php" style="color:#d00; font-weight:700;">Logout</a>
      </div>
    </div>
  </div>

  <!-- Mobile slide menu -->
  <nav class="mobile-menu" id="mobileMenu">
    <a href="/admin/dashboard.php">Dashboard</a>
    <a href="/admin/products.php">Products</a>
    <a href="/admin/orders.php">Orders</a>
    <a href="/admin/categories.php">Categories</a>
    <a href="/admin/users.php">Users</a>
    <a href="/admin/coupons.php">Offers & Coupons</a>
    <a href="/admin/profile.php">Profile</a>
    <a href="/admin/logout.php">Logout</a>
  </nav>
</header>

<script>
  // Toggle profile dropdown + mobile menu
  (function(){
    const profileToggle = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');
    const navBurger = document.getElementById('navBurger');
    const mobileMenu = document.getElementById('mobileMenu');

    if (profileToggle) {
      profileToggle.addEventListener('click', function(e){
        e.stopPropagation();
        profileDropdown.style.display = profileDropdown.style.display === 'block' ? 'none' : 'block';
      });
    }

    if (navBurger) {
      navBurger.addEventListener('click', function(e){
        e.stopPropagation();
        mobileMenu.classList.toggle('show');
      });
    }

    document.addEventListener('click', function(){
      profileDropdown.style.display = 'none';
      mobileMenu.classList.remove('show');
    });

    window.addEventListener('resize', function(){
      // hide mobile menu when resizing to desktop
      if (window.innerWidth > 920) {
        mobileMenu.classList.remove('show');
      }
    });
  })();
</script>