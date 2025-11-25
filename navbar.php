<?php

// Make sure $categories exists so navbar won't break on pages without categories query
if (!isset($categories)) {
    $categories = [];
}
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

<!-- NAVBAR STYLES -->
<link rel="stylesheet" href="assets/css/navbar.css">
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
      <li><a href="product.php">Product</a><span class="plus">+</span></li>
      <li><a href="#">Shop</a><span class="plus">+</span></li>
      <li><a href="#">Blog</a><span class="plus">+</span></li>
      <li><a href="#">Contact</a><span class="plus">+</span></li>
      <li><a href="#">Wishlist (0)</a></li>
      <li><a href="#">Login</a></li>
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
<div class="top-bar">
  <div class="top-bar-inner">
    <div class="top-left">
      <span>USD <i class="fa-solid fa-caret-down"></i></span>
      <span>ENGLISH <i class="fa-solid fa-caret-down"></i></span>
    </div>
    <div class="top-right">
      <div class="social">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-dribbble"></i></a>
        <a href="#"><i class="fab fa-pinterest-p"></i></a>
        <a href="#"><i class="fab fa-google-plus-g"></i></a>
        <a href="#"><i class="fab fa-behance"></i></a>
      </div>
      <a href="#">Login / Register</a>
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
          <li data-cat-id="">
            All categories
          </li>

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
      <div class="icon-btn">
        <i class="fa-regular fa-heart"></i>
      </div>
      <div class="icon-btn">
        <i class="fa-solid fa-bag-shopping"></i>
        <span class="cart-count">0</span>
        <span>$0.00</span>
      </div>
    </div>
  </div>
</header>

<!-- DESKTOP NAV -->
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
  <a href="#">
    <i class="fa-regular fa-heart"></i>
    <span>Wishlist (0)</span>
  </a>
  <a href="#">
    <i class="fa-regular fa-user"></i>
    <span>Login</span>
  </a>
</nav>
<!-- NAVBAR JS -->
<script src="assets/js/navbar.js"></script>