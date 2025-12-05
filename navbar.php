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
$categories = [];
$categoriesWithSubs = [];
try {
    // Check which field exists in categories table
    $cols = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $fields = array_column($cols, 'Field');
    
    $labelField = in_array('title', $fields) ? 'title' : (in_array('name', $fields) ? 'name' : null);
    
    if ($labelField !== null) {
        // Fetch top-level categories
        $catSql = "
            SELECT id, {$labelField} AS title
            FROM categories
            WHERE parent_id = 0 OR parent_id IS NULL
            ORDER BY title ASC
        ";
        $catStmt = $pdo->query($catSql);
        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch all categories with their subcategories for mega menu
        foreach ($categories as $cat) {
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
    $categories = [];
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
?>
<link rel="icon" type="image/png" href="favicon.png">
<link rel="stylesheet" href="assets/css/navbar.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

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
      
      <li class="has-submenu">
        <div class="menu-item-wrapper">
          <a href="product.php">Shop</a>
          <span class="plus">+</span>
        </div>
        <ul class="mobile-submenu">
          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
              <li><a href="product.php?cat=<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['title'], ENT_QUOTES, 'UTF-8'); ?></a></li>
            <?php endforeach; ?>
          <?php else: ?>
            <li><a href="product.php">All Products</a></li>
          <?php endif; ?>
        </ul>
      </li>
      
      <li class="<?php echo $isProduct ? 'active' : ''; ?>"><a href="product.php">Product</a></li>
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
          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
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
      <li class="<?php echo $isProduct ? 'active' : ''; ?>"><a href="product.php">Product</a></li>

      <li class="has-mega">
        <span>Shop</span>
        <div class="mega-menu">
          <div class="mega-menu-inner">
            <?php if (!empty($categoriesWithSubs)): ?>
              <?php foreach ($categoriesWithSubs as $parentCat): ?>
                <div>
                  <div class="mega-column-title"><?php echo htmlspecialchars($parentCat['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                  <ul class="mega-list">
                    <?php if (!empty($parentCat['subcategories'])): ?>
                      <?php foreach ($parentCat['subcategories'] as $subCat): ?>
                        <li>
                          <a href="product.php?cat=<?php echo (int)$subCat['id']; ?>" class="mega-item-link">
                            <span class="mega-name"><?php echo htmlspecialchars($subCat['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                          </a>
                        </li>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <!-- No subcategories, do nothing or show empty state if desired -->
                    <?php endif; ?>
                  </ul>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div>
                <div class="mega-column-title">No Categories Available</div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </li>

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
