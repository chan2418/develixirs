<?php
// navbar.php

// --- SESSION ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DATABASE CONNECTION ---
if (!isset($pdo)) {
    require_once __DIR__ . '/includes/db.php';
}

// --- FETCH CATEGORIES ---
$navCategories = [];
$categoriesWithSubs = [];
try {
    // Check which field exists in categories table
    $cols = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $fields = array_column($cols, 'Field');
    
    $labelField = in_array('title', $fields) ? 'title' : (in_array('name', $fields) ? 'name' : null);
    
    if ($labelField !== null) {
        // Fetch top-level categories (for navbar only)
        $catSql = "
            SELECT id, {$labelField} AS title
            FROM categories
            WHERE parent_id = 0 OR parent_id IS NULL
            ORDER BY title ASC
        ";
        $catStmt = $pdo->query($catSql);
        $navCategories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch all categories with their subcategories for mega menu
        foreach ($navCategories as $cat) {
            $subSql = "
                SELECT id, {$labelField} AS title
                FROM categories
                WHERE parent_id = ?
                ORDER BY title ASC
            ";
            $subStmt = $pdo->prepare($subSql);
            $subStmt->execute([$cat['id']]);
            $subcategories = $subStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $categoriesWithSubs[] = [
                'id' => $cat['id'],
                'title' => $cat['title'],
                'subcategories' => $subcategories
            ];
        }
    }
} catch (PDOException $e) {
    $navCategories = [];
    $categoriesWithSubs = [];
}

// --- USER INFO ---
$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $isLoggedIn ? ($_SESSION['user_name'] ?? 'User') : null;

// If username is empty or just whitespace, derive from email
if ($isLoggedIn && (empty(trim($userName)) || $userName === 'User')) {
    $userEmail = $_SESSION['user_email'] ?? '';
    if (!empty($userEmail) && strpos($userEmail, '@') !== false) {
        $parts = explode('@', $userEmail);
        $userName = $parts[0];
    }
}

$userLetter = $isLoggedIn && !empty($userName) ? strtoupper(substr($userName, 0, 1)) : 'U';

// --- WISHLIST COUNT ---
$wishlistCount = 0;
if ($isLoggedIn) {
    try {
        $stmtWishlist = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmtWishlist->execute([$_SESSION['user_id']]);
        $wishlistCount = (int)$stmtWishlist->fetchColumn();
    } catch (PDOException $e) {
        $wishlistCount = 0;
    }
}

// --- CART COUNT AND TOTAL ---
$cartCount = 0;
$cartTotal = 0;
if ($isLoggedIn) {
    try {
        $stmtCart = $pdo->prepare("
            SELECT COUNT(*) as count, COALESCE(SUM(p.price * c.quantity), 0) as total
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmtCart->execute([$_SESSION['user_id']]);
        $cartData = $stmtCart->fetch(PDO::FETCH_ASSOC);
        $cartCount = (int)$cartData['count'];
        $cartTotal = (float)$cartData['total'];
    } catch (PDOException $e) {
        $cartCount = 0;
        $cartTotal = 0;
    }
}

// --- DETECT CURRENT PAGE FOR ACTIVE MENU ---
$currentPage = basename($_SERVER['PHP_SELF']);
$isHome = ($currentPage === 'index.php');
$isProduct = ($currentPage === 'product.php');
$isBlog = ($currentPage === 'blog.php' || $currentPage === 'blog_single.php');
$isContact = ($currentPage === 'contact.php');

// --- FETCH ACTIVE COUPONS FOR MARQUEE ---
// Show on all pages or restrict as needed. Currently enabled globally as per "then we go other pages" goal.
$activeCoupons = [];
try {
    // Check if table exists to avoid errors on some installs
    $stmtC = $pdo->prepare("SELECT title, code, discount_type, discount_value FROM coupons WHERE status = 'active' AND show_on_marquee = 1 AND DATE(start_date) <= CURDATE() AND (end_date IS NULL OR DATE(end_date) >= CURDATE()) ORDER BY id DESC");
    $stmtC->execute();
    $activeCoupons = $stmtC->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // safe fail
}

// Removed fallback debug dummy coupon.
// Coupon marquee will only show if there are actual active coupons in the database.

// --- FETCH DYNAMIC PAGES ---
$navPages = [];
try {
    // Check if pages table exists first
    $checkPageTable = $pdo->query("SHOW TABLES LIKE 'pages'");
    if ($checkPageTable->rowCount() > 0) {
        $pageStmt = $pdo->query("SELECT title, slug FROM pages WHERE status='published' AND is_public=1 ORDER BY title ASC");
        $navPages = $pageStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) { /* ignore */ }

// --- CHECK FOR LOGIN SUCCESS MESSAGE ---
$loginSuccessMsg = '';
if (isset($_SESSION['login_success_msg'])) {
    $loginSuccessMsg = $_SESSION['login_success_msg'];
    unset($_SESSION['login_success_msg']);
}
?>
<link rel="icon" type="image/png" href="favicon.png">
<link rel="stylesheet" href="assets/css/navbar.css?v=3">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
/* FORCE DROPDOWN Z-INDEX TO FIX OVERLAP */
.abc { position: relative !important; z-index: 2100 !important; }
.user-menu { z-index: 2200 !important; }
</style>

<!-- TOAST NOTIFICATION STYLES & CONTAINER -->
<style>
/* PREMIUM TOAST STYLE */
.custom-toast {
    visibility: hidden;
    min-width: 280px;
    background: rgba(255, 255, 255, 0.98);
    color: #333;
    text-align: center;
    border-radius: 50px; /* Pill shape */
    padding: 12px 28px;
    position: fixed;
    z-index: 10000;
    left: 50%;
    top: -80px; /* Start off screen */
    transform: translateX(-50%);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    font-size: 14px;
    font-weight: 500;
    border: 1px solid #D4AF37; /* Gold Border */
    backdrop-filter: blur(12px);
    opacity: 0;
    transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); /* Bouncy effect */
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.custom-toast.show {
    visibility: visible;
    opacity: 1;
    top: 40px; /* Slide down to visible position */
}
</style>

<div id="toastNotification" class="custom-toast">
    <i class="fa-solid fa-circle-check" style="color:#D4AF37; font-size:18px;"></i>
    <span class="toast-msg" id="toastMessage"></span>
</div>

<script>
function showToast(message) {
    const toast = document.getElementById('toastNotification');
    const msg = document.getElementById('toastMessage');
    if(toast && msg) {
        msg.textContent = message;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }
}

// Check for PHP flash message
<?php if (!empty($loginSuccessMsg)): ?>
document.addEventListener('DOMContentLoaded', function() {
    showToast("<?php echo addslashes($loginSuccessMsg); ?>");
});
<?php endif; ?>
</script>

<!-- PROMO MARQUEE STYLES -->
<style>
.coupon-marquee-bar {
    background: #3B502C; /* Highlight Color */
    color: #fff;
    overflow: hidden;
    padding: 8px 0;
    position: relative;
    z-index: 100; /* Lower than mobile header (110) and menu (120) */
    font-family: 'Poppins', sans-serif;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.coupon-track {
    display: flex;
    gap: 50px;
    white-space: nowrap;
    animation: marquee 25s linear infinite;
    width: max-content;
}
.coupon-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.5px;
}
.code-badge {
    background: #fff;
    color: #3B502C;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 12px;
    border: 1px dashed #3B502C;
}
.coupon-track:hover {
    animation-play-state: paused;
}
@keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/* FIX MOBILE HEADER OVERLAP */
@media (max-width: 768px) {
    .mobile-header {
        display: flex !important; /* Force visibility */
        position: sticky !important; /* Stack instead of float */
        top: 0;
        z-index: 2000 !important; /* Above everything including nav (999) */
        width: 100%;
    }
}
</style>

<?php if (!empty($activeCoupons)): ?>
<div class="coupon-marquee-bar">
    <div class="coupon-track">
        <?php 
        // Triplicate for smooth loop
        $displayCoupons = array_merge($activeCoupons, $activeCoupons, $activeCoupons); 
        foreach ($displayCoupons as $ac): 
            $discountText = ($ac['discount_type'] ?? 'percentage') == 'percentage' ? number_format($ac['discount_value']) . '% OFF' : '₹' . number_format($ac['discount_value']) . ' OFF';
        ?>
        <div class="coupon-item">
            <i class="fa-solid fa-gift"></i>
            <span><?php echo htmlspecialchars($ac['title'] ?? 'Offer'); ?>: Use Code <strong class="code-badge"><?php echo htmlspecialchars($ac['code']); ?></strong> for <?php echo $discountText; ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- MOBILE HEADER -->
<header class="mobile-header">
  <button class="mobile-menu-toggle">
    <i class="fa-solid fa-bars"></i>
  </button>

  <a href="index.php" class="mobile-header-logo">
    <img src="develixir-logo.png" alt="DevElixir Logo">
  </a>

  <?php if ($isLoggedIn): ?>
    <div class="mobile-header-user-dropdown">
      <button class="mobile-header-user-btn">
        <div class="mobile-header-user-circle">
          <?php echo htmlspecialchars($userLetter); ?>
        </div>
      </button>
      
      <!-- Dropdown menu -->
      <div class="mobile-header-user-menu">
        <a href="my-profile.php">My Profile</a>
        <a href="logout.php">Logout</a>
      </div>
    </div>
  <?php else: ?>
    <a href="login.php" class="mobile-header-login-btn">
      <i class="fa-regular fa-user"></i>
    </a>
  <?php endif; ?>
</header>

<!-- OFF-CANVAS MOBILE MENU -->
<div class="mobile-menu-overlay">
  <div class="mobile-menu-panel">
    <div class="mobile-menu-top">
      <span class="mobile-menu-title">Menu</span>
      <button class="mobile-menu-close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="mobile-menu-search">
      <input type="text" placeholder="Search ..." />
      <button><i class="fa-solid fa-magnifying-glass"></i></button>
    </div>

    <ul class="mobile-menu-list">
      <li class="<?php echo $isHome ? 'active' : ''; ?>"><a href="index.php">Home</a></li>
      
      <!-- Dynamic Top-Level Categories (Mobile) -->
      <?php if (!empty($categoriesWithSubs)): ?>
        <?php foreach ($categoriesWithSubs as $cat): ?>
          <?php if (!empty($cat['subcategories'])): ?>
            <!-- Category with subcategories -->
            <li class="has-submenu">
              <div class="menu-item-wrapper">
                <a href="product.php?cat=<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['title'], ENT_QUOTES, 'UTF-8'); ?></a>
                <span class="plus">+</span>
              </div>
              <ul class="mobile-submenu">
                <?php foreach ($cat['subcategories'] as $subCat): ?>
                  <li><a href="product.php?cat=<?php echo (int)$subCat['id']; ?>"><?php echo htmlspecialchars($subCat['title'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                <?php endforeach; ?>
                <!-- See All Link -->
                <li><a href="product.php?cat=<?php echo (int)$cat['id']; ?>" style="color: #D4AF37; font-weight: 500;">See All</a></li>
              </ul>
            </li>
          <?php else: ?>
            <!-- Category without subcategories -->
            <li class="<?php echo (isset($_GET['cat']) && $_GET['cat'] == $cat['id']) ? 'active' : ''; ?>">
              <a href="product.php?cat=<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['title'], ENT_QUOTES, 'UTF-8'); ?></a>
            </li>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
      
      <!-- All Products Link -->
      <li class="<?php echo ($isProduct && !isset($_GET['cat'])) ? 'active' : ''; ?>">
        <a href="product.php">All Products</a>
      </li>

      <!-- Dynamic Pages Mobile Menu -->
      <?php if (!empty($navPages)): ?>
      <li class="has-submenu">
        <div class="menu-item-wrapper">
          <a href="#">Pages</a>
          <span class="plus">+</span>
        </div>
        <ul class="mobile-submenu">
          <?php foreach ($navPages as $np): ?>
            <li><a href="page.php?slug=<?php echo htmlspecialchars($np['slug']); ?>"><?php echo htmlspecialchars($np['title']); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </li>
      <?php endif; ?>
      
      <li class="<?php echo $isBlog ? 'active' : ''; ?>"><a href="blog.php">Blog</a></li>
      <li class="<?php echo $isContact ? 'active' : ''; ?>"><a href="contact.php">Contact</a></li>
      <li><a href="my-profile.php?tab=wishlist">Wishlist (<?php echo $wishlistCount; ?>)</a></li>
    </ul>

    <div class="mobile-menu-colors">
      <span class="color-box c1"></span>
      <span class="color-box c2"></span>
      <span class="color-box c3"></span>
      <span class="color-box c4"></span>
    </div>
  </div>
</div>

<!-- TOP BAR -->
<div class="abc">
  <div class="top-bar-inner">
    <div class="top-left">
      <!-- Currency and language selectors removed -->
    </div>

    <!-- CENTER: social icons -->
    <div class="top-center">
      <div class="social">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-dribbble"></i></a>
        <a href="#"><i class="fab fa-pinterest-p"></i></a>
        <a href="#"><i class="fab fa-google-plus-g"></i></a>
        <a href="#"><i class="fab fa-behance"></i></a>
      </div>
    </div>

    <div class="top-right">
      <?php if ($isLoggedIn): ?>
        <div class="user-profile user-dropdown">
          <div class="user-circle">
            <?php echo htmlspecialchars($userLetter); ?>
          </div>
          <span class="user-name">
            <?php echo htmlspecialchars($userName); ?>
          </span>
          <i class="fa-solid fa-caret-down user-caret"></i>

          <!-- dropdown menu -->
          <div class="user-menu">
            <a href="my-profile.php">My Profile</a>
            <a href="logout.php">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="login.php">Login / Register</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- HEADER -->
<header class="header">
  <div class="header-inner">
    <div class="logo">
      <a href="index.php">
        <img src="develixir-logo.png" alt="DevElixir Logo" style="height:85px; width: auto;">
      </a>
    </div>

    <form class="search-box" action="product.php" method="get">
      <div class="search-category" id="searchCategoryToggle">
        <span id="searchCategoryLabel">All categories</span>
        <i class="fa-solid fa-caret-down"></i>

        <!-- DROPDOWN -->
        <ul class="search-category-dropdown" id="searchCategoryDropdown">
          <li data-cat-id="">All categories</li>
          <?php if (!empty($navCategories)): ?>
            <?php foreach ($navCategories as $cat): ?>
              <li
                data-cat-id="<?php echo (int)$cat['id']; ?>"
                data-cat-name="<?php echo htmlspecialchars($cat['title'], ENT_QUOTES, 'UTF-8'); ?>"
              >
                <?php echo htmlspecialchars($cat['title'], ENT_QUOTES, 'UTF-8'); ?>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>

      <input
        class="search-input"
        type="text"
        name="q"
        placeholder="Search herbal products..."
      />

      <!-- hidden category field -->
      <input type="hidden" name="cat" id="searchCategoryInput" value="">

      <button class="search-button" type="submit">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </form>

    <div class="header-icons">
      <a href="my-profile.php?tab=wishlist" class="icon-btn" style="position:relative;">
        <i class="fa-regular fa-heart" style="font-size:22px;"></i>
        <?php if ($wishlistCount > 0): ?>
          <span class="wishlist-count" style="position:absolute;top:-6px;right:-6px;background:#ff4d4d;color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;"><?php echo $wishlistCount; ?></span>
        <?php else: ?>
          <span class="wishlist-count" style="display:none;">0</span>
        <?php endif; ?>
      </a>
      <a href="cart.php" class="icon-btn" style="position:relative;">
        <i class="fa-solid fa-bag-shopping" style="font-size:22px;"></i>
        <?php if ($cartCount > 0): ?>
          <span class="cart-count" style="position:absolute;top:-6px;right:-6px;background:#ff4d4d;color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;"><?php echo $cartCount; ?></span>
        <?php else: ?>
          <span class="cart-count" style="display:none;">0</span>
        <?php endif; ?>
        <span class="cart-total" style="margin-left:8px;">₹<?php echo number_format($cartTotal, 2); ?></span>
      </a>
    </div>
  </div>
</header>

<!-- NAV -->
<nav class="nav">
  <div class="nav-inner">
    <ul>
      <li class="<?php echo $isHome ? 'active' : ''; ?>"><a href="index.php">Home</a></li>
      <!-- Dynamic Top Level Categories -->
      <!-- Dynamic Top Level Categories -->
      <?php if (!empty($categoriesWithSubs)): ?>
        <?php foreach ($categoriesWithSubs as $cat): ?>
          <li class="<?php echo (isset($_GET['cat']) && $_GET['cat'] == $cat['id']) ? 'active' : ''; ?> <?php echo !empty($cat['subcategories']) ? 'has-dropdown' : ''; ?>">
            <a href="product.php?cat=<?php echo (int)$cat['id']; ?>" style="text-transform: uppercase;">
              <?php echo htmlspecialchars($cat['title']); ?> 
              <?php if(!empty($cat['subcategories'])): ?> <i class="fa-solid fa-angle-down"></i> <?php endif; ?>
            </a>
            <?php if (!empty($cat['subcategories'])): ?>
            <?php 
                $subCount = count($cat['subcategories']) + 1; // +1 for See All link
                $multiClass = ($subCount > 10) ? 'multi-column' : '';
            ?>
            <ul class="dropdown-menu <?php echo $multiClass; ?>">
                <?php foreach ($cat['subcategories'] as $subCat): ?>
                    <li>
                        <a href="product.php?cat=<?php echo (int)$subCat['id']; ?>">
                            <?php echo htmlspecialchars($subCat['title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <!-- See All Link -->
                <li>
                    <a href="product.php?cat=<?php echo (int)$cat['id']; ?>" style="color: #D4AF37 !important; font-weight: 500;">
                        See All <i class="fa-solid fa-arrow-right" style="font-size: 10px; margin-left: 4px;"></i>
                    </a>
                </li>
            </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- All Products Link -->
      <li class="<?php echo ($isProduct && !isset($_GET['cat'])) ? 'active' : ''; ?>">
        <a href="product.php" style="text-transform: uppercase;">All Products</a>
      </li>

      <!-- Dynamic Pages Menu (Desktop) -->
      <?php if (!empty($navPages)): ?>
      <li class="has-dropdown">
        <a href="#">Pages <i class="fa-solid fa-angle-down"></i></a>
        <ul class="dropdown-menu">
            <?php foreach ($navPages as $np): ?>
                <li><a href="page.php?slug=<?php echo htmlspecialchars($np['slug']); ?>"><?php echo htmlspecialchars($np['title']); ?></a></li>
            <?php endforeach; ?>
        </ul>
      </li>
      <?php endif; ?>

      <li class="<?php echo $isBlog ? 'active' : ''; ?>"><a href="blog.php">Blog</a></li>
      <li class="<?php echo $isContact ? 'active' : ''; ?>"><a href="contact.php">Contact</a></li>


    </ul>
  </div>
</nav>

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
    <span>Cart (<?php echo $cartCount; ?>)</span>
  </a>
  <a href="my-profile.php?tab=wishlist">
    <i class="fa-regular fa-heart"></i>
    <span>Wishlist (<?php echo $wishlistCount; ?>)</span>
  </a>
</nav>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.querySelector('.mobile-menu-overlay');
    const openBtn  = document.querySelector('.mobile-menu-toggle');
    const closeBtn = document.querySelector('.mobile-menu-close');

    if(openBtn && overlay){
      openBtn.addEventListener('click', function(e){
        e.preventDefault();
        overlay.classList.add('open');
      });
    }

    if(closeBtn && overlay){
      closeBtn.addEventListener('click', function(e){
        e.preventDefault();
        overlay.classList.remove('open');
      });
    }

    if(overlay){
      overlay.addEventListener('click', function(e){
        if(!e.target.closest('.mobile-menu-panel')){
          overlay.classList.remove('open');
        }
      });
    }

    // Mobile submenu toggle
    const hasSubmenuItems = document.querySelectorAll('.mobile-menu-list li.has-submenu');
    
    hasSubmenuItems.forEach(function(item) {
      const wrapper = item.querySelector('.menu-item-wrapper');
      const plusIcon = item.querySelector('.plus');
      
      if(plusIcon) {
        plusIcon.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          item.classList.toggle('open');
        });
      }
    });

    // Mobile bottom nav user dropdown toggle
    const mobileUserTrigger = document.querySelector('.mobile-user-trigger');
    const mobileUserDropdown = document.querySelector('.mobile-user-dropdown');
    
    if(mobileUserTrigger && mobileUserDropdown) {
      mobileUserTrigger.addEventListener('click', function(e) {
        e.preventDefault();
        mobileUserDropdown.classList.toggle('open');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function(e) {
        if(!mobileUserDropdown.contains(e.target)) {
          mobileUserDropdown.classList.remove('open');
        }
      });
    }

    // Mobile header user dropdown toggle
    const mobileHeaderUserBtn = document.querySelector('.mobile-header-user-btn');
    const mobileHeaderUserDropdown = document.querySelector('.mobile-header-user-dropdown');
    
    if(mobileHeaderUserBtn && mobileHeaderUserDropdown) {
      mobileHeaderUserBtn.addEventListener('click', function(e) {
        e.preventDefault();
        mobileHeaderUserDropdown.classList.toggle('open');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function(e) {
        if(!mobileHeaderUserDropdown.contains(e.target)) {
          mobileHeaderUserDropdown.classList.remove('open');
        }
      });
    }
  });
</script>
