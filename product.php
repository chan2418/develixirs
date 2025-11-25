<?php
require_once __DIR__ . '/includes/db.php';   // make sure this sets $pdo

$productBanners = [];

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM banners
        WHERE is_active = 1
          AND page_slot = :slot
        ORDER BY id DESC
    ");
    $stmt->execute(['slot' => 'product']);   // 🔑 this matches admin's page_slot
    $productBanners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $productBanners = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Devilixirs – Shop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <!-- Shared navbar styles -->
  <link rel="stylesheet" href="assets/css/navbar.css">
  
  <style>
:root{
  --primary:#D4AF37;
  --primary-dark:#B89026;
  --accent:#D4AF37;

  --text:#111111;
  --muted:#777777;

  --bg:#f7f7f7;
  --border:#e3e3e3;

  --danger:#c0392b;
}

*{
  margin:0;
  padding:0;
  box-sizing:border-box;
}

body{
  font-family:"Poppins",sans-serif;
  color:var(--text);
  background: #dadada00;
}

a{
  color:inherit;
  text-decoration:none;
}

img{
  max-width:100%;
  display:block;
}

ul{
  list-style:none;
}

.container{
  max-width:1200px;
  margin:0 auto;
  padding:0 15px;
}

/* ================= HERO / PRODUCT BANNER ================ */

/* push hero below fixed navbar – tuned, not too much space */
.shop-hero{
  margin-top:15px;         /* desktop/tablet gap */
  margin-bottom:40px;
}

/* slider wrapper */
.shop-hero-slider{
  position:relative;
  overflow:hidden;
  border:1px solid var(--border);
  border-radius:8px;
  box-shadow:0 4px 18px rgba(0,0,0,.06);
}

/* track for slides */
.shop-hero-track{
  display:flex;
  transition:transform .6s ease;
  will-change:transform;
}

/* each slide uses DB banner as background */
.shop-hero-slide{
  min-width:100%;
  height:360px;            /* shows banner nicely */
  background-size:cover;
  background-position:center;
  position:relative;
}

/* clickable whole slide if link exists */
.shop-hero-slide-link{
  display:block;
  width:100%;
  height:100%;
}

/* gradient overlay so white text visible */
.shop-hero-overlay{
  position:absolute;
  inset:0;
  display:flex;
  align-items:center;
}

/* text inside hero */
.shop-hero-inner{
  position:relative;
  z-index:2;
  padding:32px 40px;
  color:#fff;
  text-align:left;
  max-width:900px;
}

.shop-hero-inner h1{
  font-size:28px;
  letter-spacing:.12em;
  text-transform:uppercase;
  margin-bottom:8px;
}

.shop-hero-inner p{
  font-size:13px;
  max-width:420px;
}

/* dots navigation */
.shop-hero-dots{
  position:absolute;
  left:50%;
  bottom:14px;
  transform:translateX(-50%);
  display:flex;
  gap:6px;
}

.shop-hero-dot{
  width:8px;
  height:8px;
  border-radius:999px;
  border:none;
  background:rgba(255,255,255,.4);
  cursor:pointer;
  padding:0;
  transition:all .2s ease;
}

.shop-hero-dot.active{
  width:18px;
  background:#fff;
}

/* fallback breadcrumb hero (when no DB banners) */
.shop-breadcrumb{
  font-size:13px;
}
.shop-breadcrumb span{
  margin:0 4px;
}
.shop-breadcrumb a{
  color:#ffffff;
  opacity:.85;
}
.shop-breadcrumb a:hover{
  opacity:1;
}

/* mobile tweaks for hero */
@media (max-width:768px){
  .shop-hero{
    margin-top:60px;        /* slightly smaller gap on mobile */
    margin-bottom:30px;
  }

  .shop-hero-slide{
    height:260px;           /* taller than default mobile but not huge */
  }

  .shop-hero-inner{
    padding:20px 18px;
  }

  .shop-hero-inner h1{
    font-size:20px;
  }
}

/* ================= SHOP CONTENT ================ */

.shop-wrapper{
  max-width:1200px;
  margin:35px auto 60px;
  padding:0 15px;
  display:grid;
  grid-template-columns:280px 1fr;
  gap:30px;
}

/* Sidebar */
.filter-card{
  border:1px solid var(--border);
  margin-bottom:20px;
  background:#ffffff;
}
.filter-card-title{
  background:#D4AF37;
  color:#fff;
  padding:12px 18px;
  font-size:14px;
  text-transform:uppercase;
  letter-spacing:.12em;
  display:flex;
  align-items:center;
  gap:10px;
}
.filter-body{
  padding:16px 18px 18px;
  font-size:13px;
}
.filter-group{
  margin-bottom:18px;
}
.filter-group:last-child{
  margin-bottom:0;
}
.filter-group-title{
  font-weight:600;
  margin-bottom:10px;
}
.filter-options li{
  margin-bottom:6px;
  display:flex;
  align-items:center;
  gap:8px;
  color:var(--muted);
  font-size:12px;
}
.filter-options input{
  accent-color:var(--primary-dark);
}

.price-row{
  display:flex;
  align-items:center;
  gap:10px;
  margin:10px 0 12px;
}
.price-row input{
  width:70px;
  padding:4px 6px;
  border:1px solid var(--border);
  font-size:12px;
}
.btn-filter{
  display:inline-block;
  border:1px solid var(--text);
  padding:7px 18px;
  font-size:12px;
  text-transform:uppercase;
  letter-spacing:.12em;
  background:#fff;
  cursor:pointer;
}
.btn-filter:hover{
  background:#111;
  color:#fff;
}

.side-banner{
  margin-top:20px;
  position:relative;
  overflow:hidden;
  border-radius:0;
  border:1px solid var(--border);
}
.side-banner img{
  width:100%;
  height:220px;
  object-fit:cover;
}
.side-banner-text{
  position:absolute;
  inset:auto 0 18px 18px;
  color:#fff;
  text-shadow:0 1px 3px rgba(0,0,0,.5);
}
.side-banner-text h4{
  font-size:16px;
  margin-bottom:4px;
}
.side-banner-text p{
  font-size:12px;
}

/* Content header (grid/list + sort) */
.shop-toolbar{
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-bottom:18px;
  font-size:12px;
}
.shop-view-toggle{
  display:flex;
  align-items:center;
  gap:6px;
}
.view-btn{
  width:28px;
  height:28px;
  border-radius:2px;
  border:1px solid var(--border);
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  font-size:13px;
}
.view-btn.active{
  background:#111111;
  color:#fff;
  border-color:#111111;
}
.shop-sort{
  display:flex;
  align-items:center;
  gap:6px;
}
.shop-sort select{
  font-size:12px;
  padding:5px 8px;
  border:1px solid var(--border);
  outline:none;
  background:#fff;
}

/* Products grid */
.products-grid{
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:24px;
}
.product-card{
  background:#ffffff;
  border:1px solid var(--border);
  position:relative;
  overflow:hidden;
}
.product-image-wrap{
  position:relative;
  background:#fafafa;
}
.product-image-wrap img{
  width:100%;
  height:250px;
  object-fit:cover;
}
.product-badge{
  position:absolute;
  top:10px;
  left:10px;
  background:#a41b42;
  color:#fff;
  font-size:10px;
  text-transform:uppercase;
  padding:3px 7px;
  letter-spacing:.08em;
}
.product-badge.sale{
  right:10px;
  left:auto;
  background:var(--danger);
}

.product-actions{
  position:absolute;
  bottom:10px;
  left:50%;
  transform:translateX(-50%);
  display:flex;
  gap:6px;
  opacity:0;
  transition:.2s ease;
}
.product-actions span{
  width:28px;
  height:28px;
  border-radius:2px;
  background:#D4AF37;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:13px;
  cursor:pointer;
  box-shadow:0 2px 6px rgba(0,0,0,.15);
}
.product-card:hover .product-actions{
  opacity:1;
  bottom:16px;
}

.product-info{
  padding:14px 16px 16px;
  text-align:left;
}
.product-name{
  font-size:13px;
  margin-bottom:6px;
}
.product-price{
  font-size:13px;
  font-weight:600;
  margin-bottom:4px;
}
.product-price span.old{
  text-decoration:line-through;
  color:var(--muted);
  font-weight:400;
  margin-right:4px;
}
.product-stars{
  font-size:11px;
  color:#f1c40f;
}
.product-stars span{
  color:var(--muted);
  margin-left:4px;
}

/* Pagination */
.pagination{
  margin-top:26px;
  display:flex;
  justify-content:center;
  gap:4px;
  font-size:12px;
}
.page-item{
  min-width:28px;
  height:28px;
  border:1px solid var(--border);
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
}
.page-item.active{
  background:#111;
  color:#fff;
  border-color:#111;
}

/* Logos strip above footer */
.brand-strip{
  background:#333;
  padding:26px 0;
  margin-top:40px;
}
.brand-strip-inner{
  max-width:1200px;
  margin:0 auto;
  padding:0 15px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  flex-wrap:wrap;
  gap:30px;
  color:#bbbbbb;
  font-size:12px;
  text-transform:uppercase;
}

/* FOOTER */
.footer{
  background:#111;
  color:#ddd;
  padding:40px 0 0;
  margin-top:0;
}
.footer-inner{
  max-width:1200px;
  margin:0 auto;
  padding:0 15px 30px;
  display:grid;
  grid-template-columns:1.6fr 1.2fr 1.2fr 1.4fr;
  gap:30px;
  font-size:12px;
}
.footer-title{
  font-size:13px;
  text-transform:uppercase;
  margin-bottom:14px;
}
.footer-links li{
  margin-bottom:6px;
  color:#bfbfbf;
  cursor:pointer;
}
.footer-links li:hover{
  color:#fff;
}
.footer-bottom{
  border-top:1px solid #222;
  padding:14px 15px 18px;
  max-width:1200px;
  margin:0 auto;
  display:flex;
  justify-content:space-between;
  align-items:center;
  font-size:11px;
  color:#bfbfbf;
}
.footer-payments{
  display:flex;
  gap:6px;
}
.footer-payments span{
  background:#222;
  padding:4px 7px;
  border-radius:2px;
}

/* Back to top */
.back-top{
  position:fixed;
  right:20px;
  bottom:20px;
  width:40px;
  height:40px;
  border-radius:50%;
  background:#a41b42;
  color:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  font-size:18px;
  box-shadow:0 3px 10px rgba(0,0,0,.25);
  z-index:90;
}

/* ============== RESPONSIVE LAYOUT ============== */
@media(max-width:992px){
  .shop-wrapper{
    grid-template-columns:240px 1fr;
  }
  .products-grid{
    grid-template-columns:repeat(2,minmax(0,1fr));
  }
  .footer-inner{
    grid-template-columns:repeat(2,minmax(0,1fr));
  }
}

@media(max-width:768px){
  .shop-wrapper{
    grid-template-columns:1fr;
  }
  .products-grid{
    grid-template-columns:repeat(2,minmax(0,1fr));
  }

  /* hide sidebar on mobile, use filter sheet instead */
  aside{
    display:none;
  }

  .filter-toggle{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 10px;
    border:1px solid var(--border);
    background:#fff;
    font-size:12px;
    border-radius:4px;
    cursor:pointer;
  }

  .filter-toggle i{
    font-size:12px;
  }
}

@media(max-width:576px){
  .products-grid{
    grid-template-columns:repeat(2, 1fr);
    gap:12px;
  }

  .product-image-wrap img{
    height:180px;
  }

  .product-name{
    font-size:12px;
  }

  .product-price{
    font-size:12px;
  }

  .footer-inner{
    grid-template-columns:1fr;
  }
}

/* Hide wishlist & cart icons ONLY on mobile */
@media (max-width:768px){
  .header-icons{
    display:none !important;
  }
}

/* ========== MOBILE FILTER SHEET ========== */
.filter-overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.35);
  display:none;
  z-index:999;
}

.filter-overlay.open{
  display:block;
}

.filter-sheet{
  position:absolute;
  top:0;
  right:0;
  width:100%;
  max-width:420px;
  height:100%;
  background:#fff;
  display:flex;
  flex-direction:column;
  transform:translateX(100%);
  transition:transform .25s ease;
}

.filter-overlay.open .filter-sheet{
  transform:translateX(0);
}

.filter-sheet-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:12px 16px;
  border-bottom:1px solid var(--border);
  font-size:14px;
  font-weight:500;
}

.filter-sheet-header button{
  border:none;
  background:none;
  font-size:13px;
  cursor:pointer;
}

.filter-sheet-header .filter-back i{
  font-size:16px;
}

.filter-sheet-body{
  flex:1;
  display:flex;
  min-height:0;
}

/* left tabs */
.filter-left{
  width:36%;
  max-width:150px;
  background:#f3f3f5;
  border-right:1px solid var(--border);
  display:flex;
  flex-direction:column;
}

.filter-tab{
  padding:12px 10px;
  border:none;
  text-align:left;
  font-size:13px;
  background:transparent;
  cursor:pointer;
  border-left:3px solid transparent;
}

.filter-tab.active{
  background:#fff;
  border-left-color:var(--primary);
  font-weight:500;
}

/* right pane */
.filter-right{
  flex:1;
  padding:14px 16px;
  overflow-y:auto;
}

.filter-pane{
  display:none;
  font-size:13px;
}

.filter-pane.active{
  display:block;
}

.filter-pane h4{
  margin-bottom:8px;
  font-size:14px;
}

.filter-pane label{
  display:block;
  margin-bottom:8px;
}

.filter-pane input[type="checkbox"]{
  margin-right:6px;
}

/* bottom bar */
.filter-sheet-footer{
  padding:10px 14px;
  border-top:1px solid var(--border);
  display:flex;
  align-items:center;
  justify-content:space-between;
  font-size:13px;
}

.filter-apply{
  border:none;
  padding:8px 22px;
  background:var(--primary);
  color:#fff;
  font-size:13px;
  cursor:pointer;
  border-radius:4px;
}
  </style>
  
</head>
<body>

  <!-- 🔹 SHARED NAVBAR (top bar + header + nav + mobile bottom nav) -->
  <?php include 'navbar.php'; ?>

  <!-- HERO -->
  <section class="shop-hero">
  <?php if (!empty($productBanners)): ?>
    <div class="shop-hero-slider">
      <div class="shop-hero-track">
        <?php foreach ($productBanners as $idx => $b): ?>
          <?php
            $src  = '/assets/uploads/banners/' . ltrim($b['filename'] ?? '', '/');
            $alt  = $b['alt_text'] ?? '';
            $link = trim($b['link'] ?? '');
          ?>
          <div class="shop-hero-slide"
               style="background-image:url('<?php echo htmlspecialchars($src, ENT_QUOTES); ?>');">
            <?php if ($link): ?>
              <a href="<?php echo htmlspecialchars($link, ENT_QUOTES); ?>" class="shop-hero-slide-link">
            <?php endif; ?>

              <div class="shop-hero-overlay">
                <div class="shop-hero-inner">
                  <?php if (!empty($b['alt_text'])): ?>
                    <h1><?php echo htmlspecialchars($b['alt_text'], ENT_QUOTES, 'UTF-8'); ?></h1>
                  <?php endif; ?>
                  <!-- you can later add subtitle/title columns in DB if you want -->
                </div>
              </div>

            <?php if ($link): ?>
              </a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (count($productBanners) > 1): ?>
        <div class="shop-hero-dots">
          <?php foreach ($productBanners as $idx => $b): ?>
            <button
              class="shop-hero-dot<?php echo $idx === 0 ? ' active' : ''; ?>"
              data-slide="<?php echo $idx; ?>">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <!-- Fallback if no product banners in DB -->
    <div class="shop-hero-inner">
      <div class="shop-breadcrumb"></div>
    </div>
  <?php endif; ?>
</section>
  <!-- CATEGORY ICON BAR -->
  <!-- <section class="shop-category-row">
    <div class="shop-category-inner">
      <div class="shop-cat-item">
        <i class="fa-solid fa-heart-pulse"></i>
        <span>Hair Care <small>(13)</small></span>
      </div>
      <div class="shop-cat-item">
        <i class="fa-solid fa-spa"></i>
        <span>Skin &amp; Body <small>(14)</small></span>
      </div>
      <div class="shop-cat-item">
        <i class="fa-solid fa-seedling"></i>
        <span>Wellness <small>(11)</small></span>
      </div>
      <div class="shop-cat-item">
        <i class="fa-solid fa-gift"></i>
        <span>Combos <small>(22)</small></span>
      </div>
    </div>
  </section> -->

  <!-- MAIN SHOP CONTENT -->
  <section class="shop-wrapper">

    <!-- SIDEBAR -->
    <aside>
      <div class="filter-card">
        <div class="filter-card-title">
          <i class="fa-solid fa-sliders"></i>
          <span>Filter By</span>
        </div>
        <div class="filter-body">
          <!-- Color -->
          <div class="filter-group">
            <div class="filter-group-title">Color</div>
            <ul class="filter-options">
              <li><input type="checkbox"> <span>Beige</span></li>
              <li><input type="checkbox"> <span>Black</span></li>
              <li><input type="checkbox"> <span>Green</span></li>
              <li><input type="checkbox"> <span>Rose</span></li>
            </ul>
          </div>

          <!-- Size -->
          <div class="filter-group">
            <div class="filter-group-title">Size</div>
            <ul class="filter-options">
              <li><input type="checkbox"> <span>Mini</span></li>
              <li><input type="checkbox"> <span>Standard</span></li>
              <li><input type="checkbox"> <span>Large</span></li>
            </ul>
          </div>

          <!-- Brand -->
          <div class="filter-group">
            <div class="filter-group-title">Range</div>
            <ul class="filter-options">
              <li><input type="checkbox"> <span>Onion Series</span></li>
              <li><input type="checkbox"> <span>Glow Care</span></li>
              <li><input type="checkbox"> <span>Aloe Rituals</span></li>
            </ul>
          </div>

          <!-- Price -->
          <div class="filter-group">
            <div class="filter-group-title">Price</div>
            <div class="price-row">
              <input type="number" placeholder="₹10">
              <span>–</span>
              <input type="number" placeholder="₹2000">
            </div>
            <button class="btn-filter" type="button">Filter</button>
          </div>

        </div>
      </div>

      <!-- Small banner -->
      <div class="side-banner">
        <img src="https://images.pexels.com/photos/3738335/pexels-photo-3738335.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
        <div class="side-banner-text">
          <h4>Living Room Vibes</h4>
          <p>Soft fragrances &amp; calm decor picks.</p>
        </div>
      </div>
    </aside>

    <!-- PRODUCTS AREA -->
    <div>
      <div class="shop-toolbar">
        <div class="shop-view-toggle">
          <!-- mobile filter button (opens sheet) -->
          <button class="filter-toggle">
            <i class="fa-solid fa-sliders"></i>
            <span>Filter</span>
          </button>
        </div>

        <div class="shop-sort">
          <span>Sort by:</span>
          <select>
            <option>Default sorting</option>
            <option>Sort by popularity</option>
            <option>Sort by latest</option>
            <option>Sort by price: low to high</option>
            <option>Sort by price: high to low</option>
          </select>
        </div>
      </div>

      <!-- PRODUCTS GRID -->
      <div class="products-grid">
        <!-- product 1 -->
        <article class="product-card">
          <div class="product-image-wrap">
            <span class="product-badge">New</span>
            <img src="https://images.pexels.com/photos/3738336/pexels-photo-3738336.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
            <div class="product-actions">
              <span><i class="fa-regular fa-heart"></i></span>
              <span><i class="fa-regular fa-eye"></i></span>
              <span><i class="fa-solid fa-bag-shopping"></i></span>
            </div>
          </div>
          <div class="product-info">
            <div class="product-name">Herbal Heart Cushion</div>
            <div class="product-price">₹1,499.00</div>
            <div class="product-stars">★★★★★ <span>(12)</span></div>
          </div>
        </article>

        <!-- product 2 -->
        <article class="product-card">
          <div class="product-image-wrap">
            <span class="product-badge">New</span>
            <span class="product-badge sale">Sale</span>
            <img src="https://images.pexels.com/photos/3738340/pexels-photo-3738340.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
            <div class="product-actions">
              <span><i class="fa-regular fa-heart"></i></span>
              <span><i class="fa-regular fa-eye"></i></span>
              <span><i class="fa-solid fa-bag-shopping"></i></span>
            </div>
          </div>
          <div class="product-info">
            <div class="product-name">Aloe Desk Planter</div>
            <div class="product-price">
              <span class="old">₹1,999.00</span> ₹1,599.00
            </div>
            <div class="product-stars">★★★★☆ <span>(8)</span></div>
          </div>
        </article>

        <!-- product 3 -->
        <article class="product-card">
          <div class="product-image-wrap">
            <span class="product-badge">New</span>
            <img src="https://images.pexels.com/photos/3738338/pexels-photo-3738338.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
            <div class="product-actions">
              <span><i class="fa-regular fa-heart"></i></span>
              <span><i class="fa-regular fa-eye"></i></span>
              <span><i class="fa-solid fa-bag-shopping"></i></span>
            </div>
          </div>
          <div class="product-info">
            <div class="product-name">Soft Knit Pillow</div>
            <div class="product-price">₹1,299.00</div>
            <div class="product-stars">★★★★★ <span>(5)</span></div>
          </div>
        </article>

        <!-- product 4 -->
        <article class="product-card">
          <div class="product-image-wrap">
            <span class="product-badge">New</span>
            <img src="https://images.pexels.com/photos/3738339/pexels-photo-3738339.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
            <div class="product-actions">
              <span><i class="fa-regular fa-heart"></i></span>
              <span><i class="fa-regular fa-eye"></i></span>
              <span><i class="fa-solid fa-bag-shopping"></i></span>
            </div>
          </div>
          <div class="product-info">
            <div class="product-name">Owl Cushion Toy</div>
            <div class="product-price">
              <span class="old">₹1,799.00</span> ₹1,450.00
            </div>
            <div class="product-stars">★★★★☆ <span>(21)</span></div>
          </div>
        </article>

        <!-- product 5 -->
        <article class="product-card">
          <div class="product-image-wrap">
            <span class="product-badge">New</span>
            <img src="https://images.pexels.com/photos/3738335/pexels-photo-3738335.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
            <div class="product-actions">
              <span><i class="fa-regular fa-heart"></i></span>
              <span><i class="fa-regular fa-eye"></i></span>
              <span><i class="fa-solid fa-bag-shopping"></i></span>
            </div>
          </div>
          <div class="product-info">
            <div class="product-name">Striped Gift Box</div>
            <div class="product-price">₹799.00</div>
            <div class="product-stars">★★★★☆ <span>(4)</span></div>
          </div>
        </article>

        <!-- product 6 -->
        <article class="product-card">
          <div class="product-image-wrap">
            <span class="product-badge">New</span>
            <img src="https://images.pexels.com/photos/3738342/pexels-photo-3738342.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
            <div class="product-actions">
              <span><i class="fa-regular fa-heart"></i></span>
              <span><i class="fa-regular fa-eye"></i></span>
              <span><i class="fa-solid fa-bag-shopping"></i></span>
            </div>
          </div>
          <div class="product-info">
            <div class="product-name">Canvas Wall Art</div>
            <div class="product-price">
              <span class="old">₹2,499.00</span> ₹1,999.00
            </div>
            <div class="product-stars">★★★★★ <span>(9)</span></div>
          </div>
        </article>
      </div>

      <!-- PAGINATION -->
      <div class="pagination">
        <div class="page-item active">1</div>
        <div class="page-item">2</div>
        <div class="page-item">3</div>
        <div class="page-item">4</div>
        <div class="page-item">…</div>
        <div class="page-item"><i class="fa-solid fa-angle-right"></i></div>
      </div>

    </div>
  </section>

  <!-- BRAND STRIP -->
  <section class="brand-strip">
    <div class="brand-strip-inner">
      <span>Wild Mountain</span>
      <span>Vintage Studio</span>
      <span>Organic Blend</span>
      <span>Inspire Graphic</span>
      <span>Pure Aroma</span>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="footer-inner">
      <div>
        <div class="logo" style="font-size:21px;margin-bottom:8px;">
          DEVILIXIRS
          <span>HERBAL&nbsp;CARE</span>
        </div>
        <p style="color:#bfbfbf;line-height:1.7;margin-bottom:14px;">
          Clean, simple herbal blends for hair, skin and home. Crafted in small batches with love from Chennai.
        </p>
      </div>

      <div>
        <h4 class="footer-title">Customer Service</h4>
        <ul class="footer-links">
          <li>Help &amp; Contact</li>
          <li>Returns &amp; Refunds</li>
          <li>Shipping</li>
          <li>Order Tracking</li>
        </ul>
      </div>

      <div>
        <h4 class="footer-title">Company</h4>
        <ul class="footer-links">
          <li>About Us</li>
          <li>Blog</li>
          <li>Our Ingredients</li>
          <li>Wholesale</li>
        </ul>
      </div>

      <div>
        <h4 class="footer-title">Archive</h4>
        <ul class="footer-links">
          <li>Designer Picks</li>
          <li>Gift Boxes</li>
          <li>Seasonal Offers</li>
          <li>Lookbook</li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <span>Copyright © 2025 <strong>Devilixirs</strong>. All Rights Reserved.</span>
      <div class="footer-payments">
        <span>Visa</span>
        <span>MasterCard</span>
        <span>Rupay</span>
        <span>UPI</span>
      </div>
    </div>
  </footer>

  <!-- MOBILE FILTER OVERLAY -->
  <div class="filter-overlay">
    <div class="filter-sheet">
      <!-- top bar -->
      <div class="filter-sheet-header">
        <button class="filter-back">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h3>Filters</h3>
        <button class="filter-clear">Clear</button>
      </div>

      <!-- content area -->
      <div class="filter-sheet-body">
        <div class="filter-left">
          <button class="filter-tab active" data-filter-target="color">Color</button>
          <button class="filter-tab" data-filter-target="size">Size</button>
          <button class="filter-tab" data-filter-target="range">Range</button>
          <button class="filter-tab" data-filter-target="price">Price</button>
        </div>

        <div class="filter-right">
          <!-- Color -->
          <div class="filter-pane active" id="filter-color">
            <h4>Color</h4>
            <label><input type="checkbox"> Beige</label>
            <label><input type="checkbox"> Black</label>
            <label><input type="checkbox"> Green</label>
            <label><input type="checkbox"> Rose</label>
          </div>

          <!-- Size -->
          <div class="filter-pane" id="filter-size">
            <h4>Size</h4>
            <label><input type="checkbox"> Mini</label>
            <label><input type="checkbox"> Standard</label>
            <label><input type="checkbox"> Large</label>
          </div>

          <!-- Range -->
          <div class="filter-pane" id="filter-range">
            <h4>Range</h4>
            <label><input type="checkbox"> Onion Series</label>
            <label><input type="checkbox"> Glow Care</label>
            <label><input type="checkbox"> Aloe Rituals</label>
          </div>

          <!-- Price -->
          <div class="filter-pane" id="filter-price">
            <h4>Price</h4>
            <div class="price-row">
              <input type="number" placeholder="₹10">
              <span>–</span>
              <input type="number" placeholder="₹2000">
            </div>
          </div>
        </div>
      </div>

      <!-- bottom bar -->
      <div class="filter-sheet-footer">
        <div class="filter-count">
          1,004 products found
        </div>
        <button class="filter-apply">Apply</button>
      </div>
    </div>
  </div>

  <!-- Back to top -->
  <div class="back-top" onclick="window.scrollTo({top:0,behavior:'smooth'});">
    <i class="fa-solid fa-angle-up"></i>
  </div>

  <!-- 🔹 Shared navbar JS -->
  <script src="assets/js/navbar.js"></script>

  <!-- Filter sheet JS -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const overlay = document.querySelector('.filter-overlay');
      const openBtn = document.querySelector('.filter-toggle');
      const backBtn = document.querySelector('.filter-back');
      const applyBtn = document.querySelector('.filter-apply');
      const clearBtn = document.querySelector('.filter-clear');
      const tabs = document.querySelectorAll('.filter-tab');
      const panes = document.querySelectorAll('.filter-pane');

      function openFilter() {
        if (overlay) overlay.classList.add('open');
      }
      function closeFilter() {
        if (overlay) overlay.classList.remove('open');
      }

      if (openBtn) openBtn.addEventListener('click', openFilter);
      if (backBtn) backBtn.addEventListener('click', closeFilter);
      if (applyBtn) applyBtn.addEventListener('click', closeFilter);
      if (clearBtn) clearBtn.addEventListener('click', function () {
        // simple clear: uncheck everything
        overlay.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);
        overlay.querySelectorAll('input[type="number"]').forEach(i => i.value = '');
      });

      // tab switching
      tabs.forEach(tab => {
        tab.addEventListener('click', function () {
          const target = this.getAttribute('data-filter-target');

          tabs.forEach(t => t.classList.remove('active'));
          this.classList.add('active');

          panes.forEach(p => {
            p.classList.toggle('active', p.id === 'filter-' + target);
          });
        });
      });
    });
  </script>
  <script>
document.addEventListener('DOMContentLoaded', function () {
  const track = document.querySelector('.shop-hero-track');
  if (!track) return;

  const slides = Array.from(document.querySelectorAll('.shop-hero-slide'));
  const dots   = Array.from(document.querySelectorAll('.shop-hero-dot'));

  if (slides.length <= 1) return; // only one banner, no slider needed

  let index = 0;
  const total = slides.length;
  const delay = 3000;

  function goToSlide(i){
    index = i;
    track.style.transform = 'translateX(' + (-index * 100) + '%)';
    dots.forEach(d => d.classList.remove('active'));
    if (dots[index]) dots[index].classList.add('active');
  }

  dots.forEach(function(dot){
    dot.addEventListener('click', function(){
      const s = parseInt(dot.getAttribute('data-slide') || '0', 10);
      goToSlide(s);
      resetTimer();
    });
  });

  let timer = null;
  function startTimer(){
    timer = setInterval(function(){
      const next = (index + 1) % total;
      goToSlide(next);
    }, delay);
  }
  function resetTimer(){
    if (timer) clearInterval(timer);
    startTimer();
  }

  goToSlide(0);
  startTimer();
});
</script>
</body>
</html>