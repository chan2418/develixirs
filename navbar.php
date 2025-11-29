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
try {
    // Check which field exists in categories table
    $cols = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $fields = array_column($cols, 'Field');
    
    $labelField = in_array('title', $fields) ? 'title' : (in_array('name', $fields) ? 'name' : null);
    
    if ($labelField !== null) {
        $catSql = "
            SELECT id, {$labelField} AS title
            FROM categories
            WHERE parent_id = 0 OR parent_id IS NULL
            ORDER BY title ASC
        ";
        $catStmt = $pdo->query($catSql);
        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $categories = [];
}

// --- USER INFO ---
$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $isLoggedIn ? ($_SESSION['user_name'] ?? 'User') : null;
$userLetter = $isLoggedIn ? strtoupper(substr($userName, 0, 1)) : null;

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
?>
<link rel="stylesheet" href="assets/css/navbar.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

<!-- MOBILE HEADER -->
<header class="mobile-header">
  <button class="mobile-menu-toggle">
    <i class="fa-solid fa-bars"></i>
  </button>

  <div class="mobile-header-title">
    MENU
  </div>

  <button class="mobile-cart-btn">
    <i class="fa-solid fa-cart-shopping"></i>
    <span class="mobile-cart-count">0</span>
  </button>
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
      <li class="active"><a href="index.php">Home</a><span class="plus">+</span></li>
      <li><a href="#">Shop</a><span class="plus">+</span></li>
      <li><a href="product.php">Product</a><span class="plus">+</span></li>
      <li><a href="#">Blog</a><span class="plus">+</span></li>
      <li><a href="#">Contact</a><span class="plus">+</span></li>
      <li><a href="my-profile.php?tab=wishlist">Wishlist (<?php echo $wishlistCount; ?>)</a></li>
      
      <?php if ($isLoggedIn): ?>
        <li><a href="users.php">Users</a></li>
        <li><a href="my-profile.php">My Profile</a></li>
        <li><a href="logout.php" class="logout-link">Logout</a></li>
      <?php else: ?>
        <li><a href="login.php">Login / Register</a></li>
      <?php endif; ?>
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
      <span>USD <i class="fa-solid fa-caret-down"></i></span>
      <span>ENGLISH <i class="fa-solid fa-caret-down"></i></span>
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
        <img src="logo.png" alt="Devilixirs Logo" style="height:180px; width: 130px;">
      </a>
    </div>

    <form class="search-box" action="products.php" method="get">
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
      <input type="hidden" name="category" id="searchCategoryInput" value="">

      <button class="search-button" type="submit">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </form>

    <div class="header-icons">
      <a href="my-profile.php?tab=wishlist" class="icon-btn" style="position:relative;">
        <i class="fa-regular fa-heart" style="font-size:22px;"></i>
        <?php if ($wishlistCount > 0): ?>
          <span style="position:absolute;top:-6px;right:-6px;background:#ff4d4d;color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;"><?php echo $wishlistCount; ?></span>
        <?php endif; ?>
      </a>
      <a href="cart.php" class="icon-btn" style="position:relative;">
        <i class="fa-solid fa-bag-shopping" style="font-size:22px;"></i>
        <?php if ($cartCount > 0): ?>
          <span class="cart-count" style="position:absolute;top:-6px;right:-6px;background:#ff4d4d;color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;"><?php echo $cartCount; ?></span>
        <?php else: ?>
          <span class="cart-count" style="display:none;">0</span>
        <?php endif; ?>
        <span style="margin-left:8px;">₹<?php echo number_format($cartTotal, 2); ?></span>
      </a>
    </div>
  </div>
</header>

<!-- NAV -->
<nav class="nav">
  <div class="nav-inner">
    <ul>
      <li class="active"><a href="index.php">Home</a></li>
      <li><a href="product.php">Product</a></li>

      <li class="has-mega">
        <span>Shop</span>
        <div class="mega-menu">
          <div class="mega-menu-inner">
            <div>
              <div class="mega-column-title">Hair Care</div>
              <ul class="mega-list">
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 1 -</span>
                    <span class="mega-name">Herbal Oils <span class="mega-label-e">E</span></span>
                  </a>
                </li>
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 2 -</span>
                    <span class="mega-name">Anti Hairfall</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 3 -</span>
                    <span class="mega-name">Growth Boost</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 4 -</span>
                    <span class="mega-name">Scalp Detox</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 5 -</span>
                    <span class="mega-name">Onion Range</span>
                  </a>
                </li>
              </ul>
            </div>

            <div>
              <div class="mega-column-title">Skin Care</div>
              <ul class="mega-list">
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 6 -</span>
                    <span class="mega-name">Face Wash</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 7 -</span>
                    <span class="mega-name">Serums</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 8 -</span>
                    <span class="mega-name">Moisturizers</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 9 -</span>
                    <span class="mega-name">Body Care</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 10 -</span>
                    <span class="mega-name">Sun Protection</span>
                  </a>
                </li>
              </ul>
            </div>

            <div>
              <div class="mega-column-title">Combos &amp; Specials</div>
              <ul class="mega-list">
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 11 -</span>
                    <span class="mega-name">Hair + Skin Kit</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 12 -</span>
                    <span class="mega-name">Bridal Combo</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 13 -</span>
                    <span class="mega-name">Travel Minis</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 14 -</span>
                    <span class="mega-name">Festival Offers</span>
                  </a>
                </li>
                <li>
                  <a href="#" class="mega-item-link">
                    <span class="mega-demo">Demo 15 -</span>
                    <span class="mega-name">Shopping 1 <span class="mega-label-e">E</span></span>
                  </a>
                </li>
              </ul>
            </div>

          </div>
        </div>
      </li>

      <li><a href="#">Blog</a></li>
      <li><a href="#">Contact</a></li>

      <?php if ($isLoggedIn): ?>
        <li><a href="my-profile.php">My Account</a></li>
        <li><a href="logout.php">Logout</a></li>
      <?php else: ?>
        <li><a href="login.php">Login</a></li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<!-- MOBILE BOTTOM NAV -->
<nav class="mobile-bottom-nav">
  <a href="index.php">
    <i class="fa-solid fa-house"></i>
    <span>Home</span>
  </a>
  <a href="#">
    <i class="fa-solid fa-cart-shopping"></i>
    <span>Cart (0)</span>
  </a>
  <a href="my-profile.php?tab=wishlist">
    <i class="fa-regular fa-heart"></i>
    <span>Wishlist (<?php echo $wishlistCount; ?>)</span>
  </a>

  <?php if ($isLoggedIn): ?>
    <a href="my-profile.php">
      <i class="fa-regular fa-user"></i>
      <span>Account</span>
    </a>
  <?php else: ?>
    <a href="login.php">
      <i class="fa-regular fa-user"></i>
      <span>Login</span>
    </a>
  <?php endif; ?>
</nav>

<script>
// Mobile menu open/close
document.addEventListener('DOMContentLoaded', function () {
  const overlay = document.querySelector('.mobile-menu-overlay');
  const openBtn  = document.querySelector('.mobile-menu-toggle');
  const closeBtn = document.querySelector('.mobile-menu-close');

  if (openBtn && overlay) {
    openBtn.addEventListener('click', function () {
      overlay.classList.add('open');
    });
  }

  if (closeBtn && overlay) {
    closeBtn.addEventListener('click', function () {
      overlay.classList.remove('open');
    });
  }

  if (overlay) {
    overlay.addEventListener('click', function (e) {
      if (!e.target.closest('.mobile-menu-panel')) {
        overlay.classList.remove('open');
      }
    });
  }
});

// Mobile mega menu for "Shop"
document.addEventListener('DOMContentLoaded', function () {
  const shopMenu = document.querySelector('.nav li.has-mega');

  if (window.innerWidth <= 768 && shopMenu) {
    shopMenu.addEventListener('click', function (e) {
      e.stopPropagation();
      this.classList.toggle('open');
    });
  }
});

// Search category dropdown
document.addEventListener('DOMContentLoaded', function () {
  const toggle   = document.getElementById('searchCategoryToggle');
  const dropdown = document.getElementById('searchCategoryDropdown');
  const label    = document.getElementById('searchCategoryLabel');
  const input    = document.getElementById('searchCategoryInput');

  if (!toggle || !dropdown || !label || !input) return;

  toggle.addEventListener('click', function (e) {
    e.stopPropagation();
    toggle.classList.toggle('open');
  });

  dropdown.querySelectorAll('li').forEach(function (li) {
    li.addEventListener('click', function (e) {
      e.stopPropagation();
      const catId   = this.getAttribute('data-cat-id') || '';
      const catName = this.getAttribute('data-cat-name') || 'All categories';

      input.value = catId;
      label.textContent = catId ? catName : 'All categories';

      toggle.classList.remove('open');
    });
  });

  document.addEventListener('click', function () {
    toggle.classList.remove('open');
  });
});

// User dropdown (top-right)
document.addEventListener('DOMContentLoaded', function () {
  const userDropdown = document.querySelector('.user-dropdown');

  if (userDropdown) {
    userDropdown.addEventListener('click', function (e) {
      e.stopPropagation();
      this.classList.toggle('open');
    });

    document.addEventListener('click', function () {
      userDropdown.classList.remove('open');
    });
  }
});
</script>