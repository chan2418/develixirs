<?php
require_once __DIR__ . '/includes/db.php';   // make sure this creates $pdo


// ================== HOME OFFER BANNER (LEFT BOTTOM AD CARD) ==================
$homeOfferBanner = null;
try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM banners
        WHERE is_active = 1
          AND page_slot = 'home_offer'
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute();
    $homeOfferBanner = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
    $homeOfferBanner = null;
}

$popularTags = [];

try {
    $stmt = $pdo->query("
        SELECT t.id, t.name, t.slug, COUNT(pt.product_id) as usage_count
        FROM tags t
        LEFT JOIN product_tags pt ON pt.tag_id = t.id
        WHERE t.is_active = 1
        GROUP BY t.id
        ORDER BY usage_count DESC, t.name ASC
        LIMIT 10
    ");
    $popularTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $popularTags = [];
}

// ================== HERO BANNERS (HOME ONLY) ==================
$homeBanners = [];
try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM banners
        WHERE is_active = 1
          AND page_slot = :slot
        ORDER BY id DESC
    ");
    $stmt->execute(['slot' => 'home']);
    $homeBanners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $homeBanners = [];
}

// ================== HOME LEFT SIDEBAR BANNERS ==================
$homeSidebarBanners = [];
try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM banners
        WHERE is_active = 1
          AND page_slot = 'home_sidebar'
        ORDER BY id DESC
    ");
    $stmt->execute();
    $homeSidebarBanners = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $homeSidebarBanners = [];
}

// ================== HOME CENTER BANNER (FOR WIDE BANNER) ==================
$homeCenterBanner = null;
try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM banners
        WHERE is_active = 1
          AND page_slot = 'home_center'
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute();
    $homeCenterBanner = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
    $homeCenterBanner = null;
}

// ================== HOMEPAGE BANNER (fallback if you ever need it) ==================
$homeBanner = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $homeBanner = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
    $homeBanner = null;
}

function get_first_image($images) {
    // change this default if you have a different placeholder
    $default = '/assets/images/avatar-default.png';

    if (!$images) return $default;

    // try JSON first
    $maybe = @json_decode($images, true);
    if (is_array($maybe) && !empty($maybe[0])) {
        $val = $maybe[0];
    } else {
        // then comma-separated
        if (strpos($images, ',') !== false) {
            $parts = array_map('trim', explode(',', $images));
            $val = $parts[0] ?? '';
        } else {
            $val = trim($images);
        }
    }

    if (!$val) return $default;

    // if full URL or absolute path
    if (preg_match('#^https?://#i', $val) || strpos($val, '/') === 0) {
        return $val;
    }

    // else assume file in uploads
    return '/assets/uploads/products/' . ltrim($val, '/');
}

// helper: fetch specific homepage sections (best seller, trendy, sale, top_rated)
function fetch_home_section(PDO $pdo, string $section, int $limit): array {
    $sql = "
        SELECT p.id, p.name, p.price, p.images
        FROM homepage_products hp
        JOIN products p ON p.id = hp.product_id
        WHERE hp.section = :section
        ORDER BY hp.sort_order ASC, hp.id DESC
        LIMIT :lim
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':section', $section, PDO::PARAM_STR);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$categories    = [];
$allProducts   = [];
$newProducts   = [];
$bestProducts  = [];
$picksProducts = [];
$tabLatest     = [];
$tabTrendy     = [];
$tabSale       = [];
$tabTop        = [];
$subCategories = [];

try {
    // ================== CATEGORY LABEL FIELD (same as admin) ==================
    $cols   = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $fields = array_column($cols, 'Field');

    $labelField = in_array('title', $fields)
        ? 'title'
        : (in_array('name', $fields) ? 'name' : null);

    if ($labelField !== null) {
        $catSql = "
            SELECT id, {$labelField} AS title
            FROM categories
            WHERE parent_id = 0 OR parent_id IS NULL
            ORDER BY title ASC
        ";
        $catStmt    = $pdo->query($catSql);
        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===== SUBCATEGORIES FOR BANNER ROW =====
    if ($labelField !== null) {
        // Try to detect an image field in categories table
        $imgField = null;
        foreach ($fields as $f) {
            if (in_array($f, ['image', 'thumbnail', 'thumb', 'banner', 'icon', 'img'])) {
                $imgField = $f;
                break;
            }
        }

        // If there is an image column, select it; otherwise set NULL as img
        $imgSelect = $imgField ? ", {$imgField} AS img" : ", NULL AS img";

        $subSql = "
            SELECT id, {$labelField} AS title, parent_id {$imgSelect}
            FROM categories
            WHERE parent_id IS NOT NULL
              AND parent_id <> 0
            ORDER BY title ASC
        ";

        $subStmt       = $pdo->query($subSql);
        $subCategories = $subStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 1) NEW HERBAL PRODUCTS + DEVILIXIRS PICKS + LATEST TAB
     *    - all come from latest products in `products` table (auto, no manual selection)
     */
    $stmt = $pdo->query("
        SELECT id, name, price, images
        FROM products
        ORDER BY id DESC
        LIMIT 8
    ");
    $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // New Herbal Products – first 4 latest
    $newProducts   = array_slice($allProducts, 0, 4);

    // Devilixirs Picks – first 2 latest
    $picksProducts = array_slice($allProducts, 0, 2);

    // Latest Products (tab column) – same logic as New Herbal: from latest products
    $tabLatest = array_slice($allProducts, 0, 3);

    /**
     * 2) OTHER SECTIONS FROM homepage_products TABLE
     *    - Best Sellers (homepage_products.section = 'best_seller')
     *    - Trendy Products (section = 'trendy')
     *    - Sale Products (section = 'sale')
     *    - Top Rated (section = 'top_rated')
     */
    $bestProducts = fetch_home_section($pdo, 'best_seller', 4);
    $tabTrendy    = fetch_home_section($pdo, 'trendy', 3);
    $tabSale      = fetch_home_section($pdo, 'sale', 3);
    $tabTop       = fetch_home_section($pdo, 'top_rated', 3);

    // ===== LATEST BLOGS FOR HOMEPAGE =====
    $latestBlogs = [];
    try {
        $stmtBlogs = $pdo->query("
            SELECT id, title, slug, content, featured_image, created_at
            FROM blogs
            WHERE is_published = 1
            ORDER BY created_at DESC
            LIMIT 2
        ");
        $latestBlogs = $stmtBlogs->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $latestBlogs = [];
    }

    // ===== LATEST REVIEWS FOR HOMEPAGE =====
    $homeReviews = [];
    try {
        $stmtReviews = $pdo->query("
            SELECT pr.*, p.name as product_name, p.images as product_images
            FROM product_reviews pr
            JOIN products p ON pr.product_id = p.id
            WHERE pr.status = 'approved' AND pr.rating >= 4
            ORDER BY pr.created_at DESC
            LIMIT 4
        ");
        $homeReviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $homeReviews = [];
    }

} catch (PDOException $e) {
    // uncomment for debugging:
    // echo "DB error: " . $e->getMessage();
    $categories = $allProducts = $newProducts = $bestProducts = $picksProducts = [];
    $tabLatest = $tabTrendy = $tabSale = $tabTop = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
// Include SEO helper
require_once __DIR__ . '/includes/seo_meta.php';

// Generate SEO meta tags
echo generate_seo_meta([
    'title' => 'DevElixir Natural Cosmetics | Authentic Ayurvedic Beauty Products Online',
    'description' => 'Shop authentic ayurvedic beauty products, natural cosmetics, and herbal skincare at DevElixir. Ancient wellness secrets since 2005. Free shipping on orders ₹1000+',
    'keywords' => 'ayurvedic beauty products, natural cosmetics india, herbal skincare online, organic baby care, hair care products, skin care coimbatore, DevElixir',
    'url' => 'https://develixirs.com/',
    'type' => 'website'
]);

// Add Organization Schema
echo generate_organization_schema();

// Add Website Schema with search
echo generate_website_schema();
?>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  <?php include __DIR__ . '/navbar.php'; ?>
  <style>
    :root{
      /* Devilixirs Gold + Silver Theme */
      --primary:#D4AF37;        /* Header gold */
      --primary-dark:#B89026;   /* Slightly darker gold for hover */
      --accent:#D4AF37;         /* Use same gold as accent */

      --text:#1a1a1a;
      --muted:#6b6b6b;

      --bg:#B5B5B5;             /* Silver body tone */
      --card-bg:#ffffff;
      --border:#d0d0d0;
    }

    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
    }

    body{
      font-family:'Poppins',sans-serif;
      color:var(--text);
      background:#dadada00; /* was #ffffff */
    }

    a{
      text-decoration:none;
      color:inherit;
    }

    ul{
      list-style:none;
    }

    img{
      max-width:100%;
      display:block;
    }

    .wrapper{
      max-width:1200px;
      margin:0 auto;
      padding:0 15px 60px;
    }

    /* TOP BAR */
    .top-bar{
      background:var(--primary-dark);
      color:#fff;
      font-size:12px;
    }
    .top-bar-inner{
      max-width:1200px;
      margin:0 auto;
      padding:8px 15px;
      display:flex;
      align-items:center;
      justify-content:space-between;
    }
    .top-left span{
      margin-right:20px;
      cursor:pointer;
    }
    .top-left i{
      margin-left:4px;
      font-size:10px;
    }
    .top-right{
      display:flex;
      align-items:center;
      gap:12px;
      font-size:13px;
    }
    .top-right a{
      opacity:.9;
    }
    .top-right a:hover{
      opacity:1;
    }

    /* MAIN HEADER */
    .header{
      background:var(--primary);
      padding:18px 0;
      color:#fff;
      position:relative;
      z-index:50;
    }
    .header-inner{
      max-width:1200px;
      margin:0 auto;
      padding:0 15px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:30px;
    }
    .logo{
      font-size:24px;
      font-weight:700;
      letter-spacing:.16em;
    }
    .logo span{
      display:block;
      font-size:11px;
      letter-spacing:.26em;
      font-weight:400;
      margin-top:2px;
    }
    .logo img{
      max-height:100px;
      width:auto;
      display:block;
    }

    .search-box{
      flex:1;
      max-width:600px;
      display:flex;
      background:#fff;
      border-radius:999px;
      overflow:visible;   /* ✅ allow dropdown to show */
      box-shadow:0 4px 14px rgba(0,0,0,.12);
    }
    .search-category{
  position:relative;
  padding:12px 18px;
  border-right:1px solid var(--border);
  display:flex;
  align-items:center;
  gap:6px;
  font-size:13px;
  color:var(--muted);
  white-space:nowrap;
  cursor:pointer;
}
    .search-input{
      flex:1;
      border:none;
      padding:0 14px;
      font-size:13px;
      outline:none;
    }
    .search-button{
      width:52px;
      border:none;
      background:var(--primary);
      cursor:pointer;
      font-size:16px;
      color:#fff;
      transition:.2s ease;
    }
    .search-button:hover{
      background:var(--primary-dark);
    }

    .header-icons{
      display:flex;
      align-items:center;
      gap:18px;
      font-size:14px;
    }
    .header-icons .icon-btn{
      cursor:pointer;
      display:flex;
      align-items:center;
      gap:6px;
      font-size:13px;
      position:relative;
    }
    .header-icons .icon-btn i{
      font-size:18px;
    }
    .cart-count{
      position:absolute;
      top:-5px;
      right:-10px;
      background:#000;
      color:#fff;
      border-radius:50%;
      font-size:10px;
      width:16px;
      height:16px;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    /* NAVBAR */
    .nav{
      background:var(--primary);
      border-top:1px solid rgba(255,255,255,.15);
      position:relative;
      z-index:40;
    }
    .nav-inner{
      max-width:1200px;
      margin:0 auto;
      padding:0 15px;
      position:relative;
    }

    .nav ul{
      display:flex;
      gap:32px;
      align-items:center;
      font-size:13px;
      color:#fff;
    }
    .nav li{
      padding:14px 0;
      position:relative;
      cursor:pointer;
    }
    .nav li.active{
      font-weight:600;
    }
    .nav li.active::after{
      content:'';
      position:absolute;
      left:0;
      bottom:0;
      width:100%;
      height:3px;
      background:#fff;
    }

    /* ===== MEGA MENU (SHOP DROPDOWN) ===== */
    .nav-inner{
      position:relative;
    }

    .nav li.has-mega{
      position:static; /* allow dropdown full width */
    }

    /* Mega menu styles moved to assets/css/navbar.css */
    .mega-item-link:hover{
      transform:translateX(3px);
    }

    .mega-demo{
      display:block;
      font-size:11px;
      text-transform:uppercase;
      letter-spacing:.18em;
      color:var(--muted);
    }

    .mega-name{
      display:block;
      font-size:13px;
      font-weight:500;
      color:var(--text);
      margin-top:2px;
    }

    .mega-label-e{
      display:inline-block;
      font-size:9px;
      padding:1px 5px;
      border-radius:3px;
      background:#f0f0f0;
      color:#444;
      margin-left:6px;
    }

    .mega-menu .mega-list{
      display:block;
      margin:0;
      padding:0;
    }

    .mega-menu .mega-list li{
      display:block;
      padding:4px 0;
    }
    .mega-item-link{
      display:block;
      padding:3px 0;
      font-size:13px;
      color:#333;
    }

    .mega-item-link:hover{
      color:var(--primary-dark);
    }

    /* MAIN CONTENT */
    .main{
      background:linear-gradient(180deg, #dadada00 0%, #ffffff 55%);
    }

    /* ---- NEW LAYOUT: SIDEBAR + MAIN ---- */
    .page-grid{
      display:grid;
      grid-template-columns:260px 1fr;
      gap:25px;
      margin-top:40px;
    }

    .side-column{
      display:flex;
      flex-direction:column;
      gap:24px;
    }

    .hero-section{
      padding:0 0 30px;
    }

    /* CATEGORY SIDEBAR CARD */
    .card{
      background:var(--card-bg);
      border:1px solid var(--border);
      box-shadow:0 2px 6px rgba(0,0,0,.04);
    }
    .card-header{
      background:var(--primary);
      color:#fff;
      padding:12px 16px;
      font-size:13px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      text-transform:uppercase;
      font-weight:600;
      cursor:default;
    }
    .card-header .label{
      display:flex;
      align-items:center;
      gap:8px;
    }
    .card-body{
      padding:12px 0;
    }

    .categories-card .card-header{
      cursor:pointer;
    }
    .categories-card .card-header .toggle-icon{
      font-size:14px;
      transition:transform .2s ease;
    }
    .categories-card.collapsed .card-body{
      display:none;
    }
    .categories-card.collapsed .toggle-icon{
      transform:rotate(90deg);
    }

    .category-list li{
      padding:10px 18px;
      display:flex;
      align-items:center;
      gap:10px;
      font-size:13px;
      border-top:1px solid var(--border);
      cursor:pointer;
    }
    .category-list li:first-child{
      border-top:none;
    }
    .category-list li i{
      color:var(--primary);
      width:16px;
      text-align:center;
    }
    .category-list li:hover{
      background:var(--bg);
    }

    /* HERO RIGHT */
    .hero-banner{
      background:linear-gradient(90deg,#f7faf8,#ffffff);
      border:1px solid var(--border);
      min-height:220px;
      display:flex;
      align-items:center;
      padding:40px 60px;
      box-shadow:0 4px 18px rgba(0,0,0,.06);
      position:relative;
      overflow:hidden;
    }
    .hero-banner::before{
      content:'';
      position:absolute;
      right:-80px;
      top:-80px;
      width:230px;
      height:230px;
      border-radius:50%;
      opacity:.8;
    }
    .hero-text{
      position:relative;
      z-index:1;
    }
    .hero-text small{
      font-size:11px;
      letter-spacing:.2em;
      text-transform:uppercase;
      color:var(--muted);
      display:block;
      margin-bottom:10px;
    }
    .hero-text h1{
      font-size:30px;
      letter-spacing:.04em;
      margin-bottom:10px;
    }
    .hero-text p{
      font-size:13px;
      color:var(--muted);
      max-width:380px;
      margin-bottom:22px;
    }

    .btn-primary{
      background:linear-gradient(145deg, #014d40, #0b6b5a);
      border:1px solid var(--accent);
      color:#fff;
      padding:11px 26px;
      font-size:13px;
      border-radius:999px;
      border:none;
      text-transform:uppercase;
      font-weight:500;
      cursor:pointer;
      transition:.2s ease;
      box-shadow:0 4px 14px rgba(12,140,85,.35);
    }
    .btn-primary:hover{
      background:var(--primary-dark);
      transform:translateY(-1px);
      box-shadow:0 6px 18px rgba(0,0,0,.25);
    }

    /* CONTENT BELOW HERO (right column) */
    .content-grid{
      /* now just a normal flow container */
    }

    /* BANNERS ROW */
    .banner-row{
      display:flex;
      gap:18px;
      margin-bottom:26px;
      overflow:hidden;
      scroll-behavior:smooth;
    }
    .banner-item{
      position:relative;
      overflow:hidden;
      background:#eee;
      border:1px solid var(--border);
      flex:0 0 calc(33.333% - 12px);
    }
    .banner-item img{
      width:100%;
      height:180px;
      object-fit:cover;
      transition:transform .3s ease;
    }
    .banner-item:hover img{
      transform:scale(1.04);
    }
    .banner-caption{
      position:absolute;
      left:20px;
      bottom:22px;
      color:#fff;
      text-shadow:0 1px 2px rgba(0,0,0,.3);
      max-width:70%;
    }
    .banner-caption h4{
      font-size:18px;
      font-weight:600;
      margin-bottom:3px;
    }
    .banner-caption span{
      font-size:12px;
    }

    /* SECTION TITLE ROW */
    .section-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      margin-bottom:14px;
    }
    .section-header h3{
      font-size:15px;
      text-transform:uppercase;
      position:relative;
      padding-left:14px;
    }
    .section-header h3::before{
      content:'';
      position:absolute;
      left:0;
      top:50%;
      width:6px;
      height:60%;
      background:var(--primary);
      transform:translateY(-50%);
      border-radius:3px;
    }
    .section-arrows{
      display:flex;
      gap:6px;
      font-size:13px;
      color:var(--muted);
    }
    .section-arrows span{
      width:24px;
      height:24px;
      border-radius:50%;
      border:1px solid var(--border);
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
    }
    .section-arrows span:hover{
      border-color:var(--primary);
      color:var(--primary);
    }

    /* PRODUCT GRID */
    .product-grid{
      display:grid;
      grid-template-columns:repeat(4,minmax(0,1fr));
      gap:18px;
    }
    .product-card{
      border:1px solid var(--border);
      background:#fff;
      padding:14px;
      text-align:left;
      transition:box-shadow .2s ease, transform .2s ease;
    }
    .product-card:hover{
      box-shadow:0 2px 10px rgba(0,0,0,.08);
      transform:translateY(-2px);
    }
    .product-image{
      margin-bottom:14px;
    }
    .product-name{
      font-size:13px;
      margin-bottom:4px;
    }
    .product-price{
      font-weight:600;
      font-size:14px;
      margin-bottom:4px;
      color:var(--primary-dark);
    }
    .product-stars{
      font-size:10px;
      color:#ccc;
    }

    /* SIDE CARDS */
    .mini-product{
      display:flex;
      align-items:center;
      gap:10px;
      padding:10px 16px;
      border-top:1px solid var(--border);
    }
    .mini-product:first-child{
      border-top:none;
    }
    .mini-product img{
      width:56px;
      height:56px;
      object-fit:cover;
      border-radius:8px;
      border:1px solid var(--border);
    }
    .mini-info{
      flex:1;
    }
    .mini-name{
      font-size:12px;
      margin-bottom:4px;
    }
    .mini-price{
      color:var(--primary);
      font-weight:600;
      font-size:13px;
    }

    .tags-wrap{
      padding:14px 16px;
      display:flex;
      flex-wrap:wrap;
      gap:8px;
    }
    .tag-item{
      padding:5px 9px;
      font-size:11px;
      background:#f0f5f2;
      border-radius:999px;
      cursor:pointer;
    }
    .tag-item:hover{
      background:var(--primary);
      color:#fff;
    }

    /* BANNER STRIP */
    .wide-banner{
      margin-top:30px;
      border:1px solid var(--border);
      position:relative;
      overflow:hidden;
      background:#eee;
      border-radius:8px;
      box-shadow:0 4px 16px rgba(0,0,0,.08);
    }
    .wide-banner img{
      width:100%;
      height:220px;
      object-fit:cover;
      filter:brightness(.88);
    }
    .wide-banner-text{
      position:absolute;
      inset:0;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      color:#fff;
      text-align:center;
      text-shadow:0 1px 3px rgba(0,0,0,.4);
    }
    .wide-banner-text h2{
      font-size:20px;
      letter-spacing:.2em;
      text-transform:uppercase;
      margin-bottom:8px;
    }
    .wide-banner-text p{
      font-size:13px;
    }

    /* LATEST BLOG */
    .latest-blog-section{
      margin-top:40px;
    }
    .blog-grid{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:24px;
    }
    .blog-card{
      border:1px solid var(--border);
      background:#fff;
      box-shadow:0 2px 6px rgba(0,0,0,.04);
    }
    .blog-card-image{
      background:#f3f3f3;
    }
    .blog-card-body{
      padding:18px 22px 20px;
    }
    .blog-title{
      font-size:13px;
      font-weight:600;
      text-transform:uppercase;
      margin-bottom:10px;
    }
    .blog-meta{
      font-size:11px;
      margin-bottom:10px;
    }
    .blog-meta span{
      margin-right:10px;
    }
    .blog-meta a{
      color:var(--primary);
    }
    .blog-excerpt{
      font-size:12px;
      color:var(--muted);
      line-height:1.6;
    }

    /* TABBED PRODUCTS SECTION */
    .tabbed-products-section{
      margin-top:40px;
      border:1px solid var(--border);
      background:#fff;
      box-shadow:0 2px 6px rgba(0,0,0,.04);
      padding:0 18px 20px;
    }
    .tabs-bar{
      display:grid;
      grid-template-columns:repeat(4, minmax(0, 1fr));
      column-gap:18px;
      margin:0 0 14px;
    }
    .tab-ribbon{
      position:relative;
      padding:10px 6px;
      font-size:12px;
      text-transform:uppercase;
      background:var(--primary);
      color:#fff;
      font-weight:500;
      cursor:pointer;
      text-align:center;
      border-radius:3px;
    }
    .tab-ribbon::after{
      display:none;
    }
    .tab-ribbon.inactive{
      background:#f3f3f3;
      color:#333;
    }

    .tabbed-columns{
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
      align-items: stretch;
    }
    .tab-column{
      display: grid;
      grid-auto-rows: 1fr;
      gap: 10px;
      background: #fff;
    }
    .tab-column-card{
      margin-bottom: 0 !important;
      height: 100%;
      display: flex;
      padding: 10px 12px;
      border: 1px solid var(--border);
      box-sizing: border-box;
      background:#fff;
      border-radius:4px;
    }
    .tab-col-product{
      display: flex;
      align-items: flex-start;
      gap: 8px;
      width: 100%;
    }
    .tab-col-product img{
      width: 60px;
      height: 60px;
      object-fit: contain;
    }
    .tab-col-info{
      display: flex;
      flex-direction: column;
      flex: 1;
    }
    .tab-col-name{
      font-family: 'Poppins', sans-serif;
      font-size: 12.5px;
      font-weight: 500;
      line-height: 1.4;
      color: #2b2b2b;
      letter-spacing: 0.2px;
      margin-bottom: 4px;
      white-space: normal;
      word-break: break-word;
      flex: 1;
    }
    .tab-col-price{
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 600;
      color: #b89026;
      margin-top:2px;
    }
    .tab-col-stars{
      color: #cfcfcf;
      font-size: 11px;
      margin-top:2px;
    }

    /* BRANDS ROW */
    .brands-row{
      margin-top:40px;
      padding:22px 0 10px;
      display:flex;
      justify-content:center;
      flex-wrap:wrap;
      gap:50px;
      border-top:1px solid var(--border);
    }
    .brand-item{
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:13px;
      text-transform:lowercase;
      opacity:.9;
    }

    /* FOOTER & BACK TO TOP styles are now in footer.php */

    /* RESPONSIVE */
    @media (max-width:992px){
      .page-grid{
        grid-template-columns:1fr;
      }
      .banner-row{
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
      .product-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
      .blog-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
      .footer-inner{
        grid-template-columns:repeat(3,minmax(0,1fr));
      }
    }

    @media (max-width:768px){
      .header-inner{
        flex-direction:column;
        align-items:flex-start;
        gap:15px;
      }
      .banner-row{
        overflow:hidden;
      }
      .banner-item{
        flex:0 0 100%;
      }
      .product-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
      .top-bar-inner{
        flex-direction:column;
        align-items:flex-start;
        gap:6px;
      }
      .nav ul{
        overflow-x:auto;
      }
      .tabbed-columns{
        grid-template-columns:1fr;
      }
      .footer-inner{
        grid-template-columns:repeat(2,minmax(0,1fr));
      }

      .side-column{
        order:2;
        margin-top:20px;
      }
      .main-column{
        order:1;
      }

      .hero-section{
        padding:20px 0 24px;
      }
    }

    @media (max-width:600px){
      .footer-inner{
        grid-template-columns:1fr;
      }
    }

    @media (max-width:480px){
      .product-grid{
        grid-template-columns:repeat(2, 1fr);
        gap: 12px;
      }
      
      /* Hide categories sidebar on mobile */
      .side-column{
        display: none;
      }
      
      /* Make main content full width when sidebar is hidden */
      .page-grid{
        grid-template-columns: 1fr;
      }
      
      /* Reduce tabbed products section height on mobile */
      .tabbed-products-section {
        margin-top: 20px !important;
        padding: 0 12px 15px !important;
      }
      
      .tabs-bar {
        margin-bottom: 10px !important;
      }
      
      .tab-ribbon {
        padding: 8px 4px !important;
        font-size: 11px !important;
      }
      
      .tab-column-card {
        padding: 8px !important;
        margin-bottom: 8px !important;
      }
      
      .tab-col-product img {
        width: 50px !important;
        height: 50px !important;
      }
      
      /* Reduce tab-column background height */
      .tab-column {
        padding: 10px !important;
        min-height: auto !important;
      }
    }

    /* ===== MOBILE MEGA MENU FIX ===== */
    @media (max-width: 768px){

      .nav ul{
        flex-direction:column;
        align-items:flex-start;
        gap:0;
      }

      .nav li{
        width:100%;
      }

      .mega-menu{
        position:static;
        box-shadow:none;
        display:none;
        padding:15px 0;
      }

      .nav li.has-mega.open .mega-menu{
        display:block;
      }

      .mega-menu-inner{
        grid-template-columns:1fr;
        gap:20px;
      }

      .mega-item-link{
        padding:6px 0;
      }
    }

    /* Ad Poster Card */
    .ad-card{
      border:none;
      background:linear-gradient(135deg, #A41B42, #3B502C);
      color:#fff;
      position:relative;
      overflow:hidden;
      padding:16px 18px 18px;
      box-shadow:0 6px 18px rgba(0,0,0,.25);
    }

    .ad-card::before{
      content:'';
      position:absolute;
      right:-40px;
      bottom:-40px;
      width:140px;
      height:140px;
      border-radius:50%;
      background:rgba(212,175,55,0.25);
    }

    .ad-badge{
      font-size:10px;
      text-transform:uppercase;
      letter-spacing:.18em;
      opacity:.85;
      margin-bottom:6px;
    }

    .ad-title{
      font-size:15px;
      font-weight:600;
      line-height:1.4;
      max-width:180px;
      margin-bottom:8px;
    }

    .ad-text{
      font-size:11px;
      opacity:.9;
      max-width:200px;
      margin-bottom:14px;
    }

    .footer-social {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }
    .footer-social a {
        width: 36px;
        height: 36px;
        background: #222;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        border-radius: 50%;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    .footer-social a:hover {
        background: var(--primary);
        color: #fff;
        transform: translateY(-3px);
    }
    .ad-cta{
      display:inline-flex;
      align-items:center;
      gap:6px;
      font-size:11px;
      text-transform:uppercase;
      padding:6px 12px;
      border-radius:999px;
      background:rgba(0,0,0,0.25);
      border:1px solid rgba(255,255,255,0.4);
      cursor:pointer;
    }

    .ad-cta i{
      font-size:10px;
    }

    /* Left sidebar promo poster */
    .promo-card{
      padding:0;
      border:1px solid var(--border);
      overflow:hidden;
      background:#eee;
    }

    .promo-image{
      position:relative;
    }

    .promo-image img{
      width:100%;
      height:260px;
      object-fit:cover;
      display:block;
    }

    .promo-overlay{
      position:absolute;
      inset:0;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      text-align:center;
      color:#fff;
      text-transform:uppercase;
      letter-spacing:.14em;
      background:linear-gradient(to bottom,
        rgba(0,0,0,0.05),
        rgba(0,0,0,0.45)
      );
    }

    .promo-text-top{
      font-size:13px;
      font-weight:600;
      margin-bottom:6px;
    }

    .promo-text-bottom{
      font-size:18px;
      font-weight:600;
    }

    .promo-text-bottom span{
      font-size:22px;
    }

    /* MOBILE BOTTOM NAV */
    .mobile-bottom-nav{
      position:fixed;
      left:0;
      right:0;
      bottom:0;
      height:60px;
      background:#ffffff;
      border-top:1px solid #e3e3e3;
      display:none;
      align-items:center;
      justify-content:space-around;
      font-size:11px;
      z-index:99;
    }

    .mobile-bottom-nav a{
      flex:1;
      text-align:center;
      text-decoration:none;
      color:#3B502C;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:4px;
    }

    .mobile-bottom-nav a i{
      font-size:18px;
    }

    .mobile-bottom-nav .count{
      font-size:11px;
      opacity:0.7;
    }

    /* ===== MOBILE HEADER ===== */
    .mobile-header{
      display:none;
      height:50px;
      background:#ffffff;
      border-bottom:1px solid #e3e3e3;
      padding:0 12px;
      align-items:center;
      justify-content:space-between;
      position:fixed;
      top:0;
      left:0;
      right:0;
      z-index:110;
    }

    .mobile-header-title{
      font-size:14px;
      font-weight:600;
      color:#3B502C;
      text-transform:uppercase;
    }

    .mobile-menu-toggle,
    .mobile-cart-btn{
      background:none;
      border:none;
      padding:0;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:20px;
      color:#3B502C;
      cursor:pointer;
    }

    .mobile-cart-btn{
      position:relative;
    }

    .mobile-cart-count{
      position:absolute;
      top:-4px;
      right:-8px;
      background:#000;
      color:#fff;
      font-size:10px;
      width:16px;
      height:16px;
      border-radius:50%;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    /* ===== OFF CANVAS MENU ===== */
    .mobile-menu-overlay{
      position:fixed;
      inset:0;
      background:rgba(0,0,0,0.55);
      display:none;
      z-index:120;
    }

    .mobile-menu-overlay.open{
      display:block;
    }

    .mobile-menu-panel{
      position:absolute;
      top:0;
      left:0;
      width:80%;
      max-width:320px;
      height:100%;
      background:#ffffff;
      box-shadow:2px 0 18px rgba(0,0,0,0.3);
      display:flex;
      flex-direction:column;
    }

    .mobile-menu-top{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:12px 14px;
      background:#4a4a4a;
      color:#fff;
      text-transform:uppercase;
      font-size:14px;
    }

    .mobile-menu-title{
      font-weight:600;
    }

    .mobile-menu-close{
      background:none;
      border:none;
      color:#fff;
      font-size:18px;
      cursor:pointer;
    }

    .mobile-menu-search{
      display:flex;
      padding:8px 10px;
      border-bottom:1px solid #e3e3e3;
    }

    .mobile-menu-search input{
      flex:1;
      border:1px solid #ddd;
      padding:6px 8px;
      font-size:12px;
    }

    .mobile-menu-search button{
      width:36px;
      border:none;
      background:#4a4a4a;
      color:#fff;
      font-size:14px;
    }

    .mobile-menu-list{
      list-style:none;
      margin:0;
      padding:0;
      flex:1;
      overflow-y:auto;
    }

    .mobile-menu-list li{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:12px 14px;
      font-size:13px;
      border-bottom:1px solid #f0f0f0;
    }

    .mobile-menu-list li.active{
      background:#3B502C;
      color:#fff;
    }

    .mobile-menu-list li a{
      flex:1;
    }

    .mobile-menu-list .plus{
      font-weight:700;
      font-size:16px;
      color:#777;
    }

    .mobile-menu-colors{
      padding:12px 14px 14px;
      display:flex;
      gap:8px;
    }

    .color-box{
      width:26px;
      height:26px;
      display:inline-block;
    }

    .color-box.c1{ background:#29458b; }
    .color-box.c2{ background:#2d8ec5; }
    .color-box.c3{ background:#d54235; }
    .color-box.c4{ background:#b6328f; }

    @media (max-width:768px){
      .mobile-header{
        display:flex;
      }

      .top-bar,
      .header,
      .nav{
        margin-top:50px;
      }

      .header-icons{
        display:none;
      }

      .nav ul{
        display:none;
      }

      body{
        padding-bottom:70px;
      }

      .mobile-bottom-nav{
        display:flex;
      }
    }

    /* MOBILE: TABS AS SECTIONS */
    @media (max-width:768px){
      .tabs-bar{
        display:none;
      }

      .tabbed-columns{
        grid-template-columns:1fr;
      }

      .tab-column{
        margin-bottom:24px;
      }

      .tab-column:nth-child(1)::before{
        content:"Latest Products";
      }
      .tab-column:nth-child(2)::before{
        content:"Trendy Products";
      }
      .tab-column:nth-child(3)::before{
        content:"Sale Products";
      }
      .tab-column:nth-child(4)::before{
        content:"Top Rated";
      }

      .tab-column::before{
        display:block;
        background:#D4AF37;
        color:#fff;
        padding:10px 14px;
        font-size:13px;
        font-weight:600;
        text-transform:uppercase;
        margin-bottom:12px;
      }
    }

    .hero-banner{
      /* fallback if no DB banner */
      background:linear-gradient(90deg,#f7faf8,#ffffff);
      border:1px solid var(--border);
      min-height:220px;
      display:flex;
      align-items:center;
      padding:40px 60px;
      box-shadow:0 4px 18px rgba(0,0,0,.06);
      position:relative;
      overflow:hidden;
    }

    /* when we have a DB banner image */
    .hero-banner.has-banner{
      background-size:cover;
      background-position:center;
      background-repeat:no-repeat;
    }

    .hero-text{
      position:relative;
      z-index:1;
    }

    /* HERO SLIDER */
    .hero-slider{
      position:relative;
      border:1px solid var(--border);
      box-shadow:0 4px 18px rgba(0,0,0,.06);
      overflow:hidden;
      border-radius:8px;
    }

    .hero-slider-track{
      display:flex;
      transition:transform .6s ease;
      will-change:transform;
    }

    .hero-slide{
      min-width:100%;
      height:260px;
      background-size:cover;
      background-position:center;
      position:relative;
    }

    .hero-slide-link{
      display:block;
      width:100%;
      height:100%;
      text-decoration:none;
      color:inherit;
    }

    .hero-slide-overlay{
      position:absolute;
      inset:0;
      display:flex;
      align-items:center;
      padding:40px 60px;
      box-sizing:border-box;
    }

    .hero-slide .hero-text{
      color:#fff;
    }

    .hero-slide .hero-text small{
      font-size:11px;
      letter-spacing:.2em;
      text-transform:uppercase;
      color:#f5f5f5;
      display:block;
      margin-bottom:10px;
    }

    .hero-slide .hero-text h1{
      font-size:30px;
      letter-spacing:.04em;
      margin-bottom:10px;
    }

    .hero-slide .hero-text p{
      font-size:13px;
      max-width:380px;
      margin-bottom:22px;
      color:#f0f0f0;
    }

    .hero-slider-dots{
      position:absolute;
      left:50%;
      bottom:14px;
      transform:translateX(-50%);
      display:flex;
      gap:6px;
    }

    .hero-dot{
      width:8px;
      height:8px;
      border-radius:999px;
      border:none;
      background:rgba(255,255,255,0.4);
      cursor:pointer;
      padding:0;
      transition:all .2s ease;
    }

    .hero-dot.active{
      width:18px;
      background:#ffffff;
    }

    @media (max-width:768px){
      .hero-slide{
        height:220px;
      }
      .hero-slide-overlay{
        padding:24px 18px;
      }
      .hero-slide .hero-text h1{
        font-size:20px;
      }
    }

    /* ===== SIDEBAR SLIDER ===== */
    .sidebar-slider{
      position:relative;
      overflow:hidden;
      border:none;
      background:transparent;
      box-shadow:none;
      padding:0;
      height:260px;
    }

    .sidebar-slider-track{
      display:flex;
      flex-direction:row;
      transition:transform .6s ease;
      will-change:transform;
    }

    .sidebar-slide{
      min-width:100%;
      height:100%;
      position:relative;
    }

    .sidebar-slide-link{
      display:block;
      width:100%;
      height:100%;
      text-decoration:none;
      color:inherit;
    }

    .sidebar-slider-dots{
      position:absolute;
      left:50%;
      bottom:10px;
      transform:translateX(-50%);
      display:flex;
      gap:4px;
      z-index:2;
    }

    .sidebar-dot{
      width:7px;
      height:7px;
      border-radius:999px;
      border:none;
      background:rgba(255,255,255,0.4);
      cursor:pointer;
      padding:0;
      transition:all .2s ease;
    }

    .sidebar-dot.active{
      width:16px;
      background:#ffffff;
    }

    @media (max-width:768px){
      .sidebar-slider{
        height:220px;
      }
    }
    .wide-banner{
  margin-top:30px;
  border:1px solid var(--border);
  position:relative;
  overflow:hidden;
  background:#eee;
  border-radius:8px;
  box-shadow:0 4px 16px rgba(0,0,0,.08);
  height:260px;              /* 🔑 full banner height */
}

.wide-banner img{
  width:100%;
  height:100%;               /* 🔑 fill the .wide-banner */
  object-fit:cover;
  display:block;
  filter:brightness(.88);
}

/* optional: a bit shorter on mobile */
@media (max-width:768px){
  .wide-banner{
    height:220px;
  }
}
.search-category{
  position:relative;
  padding:12px 18px;
  border-right:1px solid var(--border);
  display:flex;
  align-items:center;
  gap:6px;
  font-size:13px;
  color:var(--muted);
  white-space:nowrap;
  cursor:pointer;
}

.search-category-dropdown{
  position:absolute;
  top:100%;
  left:0;
  right:0;
  background:#fff;
  border:1px solid var(--border);
  box-shadow:0 6px 18px rgba(0,0,0,.15);
  list-style:none;
  margin:6px 0 0;
  padding:4px 0;
  max-height:260px;
  overflow-y:auto;
  z-index:99;
  display:none;
}

.search-category-dropdown li{
  padding:8px 12px;
  font-size:13px;
  cursor:pointer;
}

.search-category-dropdown li:hover{
  background:#f5f5f5;
}

/* when open */
.search-category.open .search-category-dropdown{
  display:block;
}

  </style>
</head>
<body>


  <!-- MAIN -->
  <main class="main">
    <div class="wrapper">

      <!-- TWO COLUMN LAYOUT -->
      <div class="page-grid">
        <!-- LEFT SIDEBAR -->
        <aside class="side-column">
          <div class="card categories-card">
      <div class="card-header categories-toggle">
        <div class="label">
          <i class="fa-solid fa-bars"></i>
          <span>Categories</span>
        </div>
        <i class="fa-solid fa-chevron-right toggle-icon"></i>
      </div>
      <div class="card-body">
        <ul class="category-list">
  <?php if (!empty($categories)): ?>
    <?php foreach ($categories as $cat): ?>
      <li>
        <i class="fa-solid fa-leaf"></i>
        <a href="product.php?category[]=<?php echo urlencode($cat['title']); ?>">
          <?php echo htmlspecialchars($cat['title'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
      </li>
    <?php endforeach; ?>
  <?php else: ?>
    <li style="font-size:12px; color:#777; padding:8px 18px;">
      No categories available.
    </li>
  <?php endif; ?>
</ul>
      </div>
    </div>

          <!-- DEVILIXIRS PICKS – latest products from DB -->
          <div class="card">
            <div class="card-header">
              <div class="label">
                <i class="fa-solid fa-trophy"></i>
                <span>Devilixirs Picks</span>
              </div>
            </div>
            <div class="card-body">
              <?php if (!empty($picksProducts)): ?>
                <?php foreach ($picksProducts as $p): ?>
                  <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                  <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" class="mini-product" style="text-decoration:none; color:inherit; display:flex;">
                    <img src="<?php echo $img; ?>" alt="Product">
                    <div class="mini-info">
                      <div class="mini-name">
                        <?php echo htmlspecialchars($p['name']); ?>
                      </div>
                      <div class="product-stars">★★★★★</div>
                      <div class="mini-price">
                        ₹<?php echo number_format((float)$p['price'], 2); ?>
                      </div>
                    </div>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <div style="padding:10px 16px; font-size:12px; color:#777;">
                  No picks available yet.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="card promo-card">
            <?php if (!empty($homeSidebarBanners)): ?>
              <div class="card sidebar-slider">
                <div class="sidebar-slider-track">
                  <?php foreach ($homeSidebarBanners as $idx => $sb): ?>
                    <?php
                      $sidebarSrc  = '/assets/uploads/banners/' . ltrim($sb['filename'] ?? '', '/');
                      $sidebarAlt  = $sb['alt_text'] ?? '';
                      $sidebarLink = trim($sb['link'] ?? '');
                    ?>
                    <div class="sidebar-slide">
                      <?php if ($sidebarLink): ?>
                        <a href="<?php echo htmlspecialchars($sidebarLink, ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-slide-link">
                      <?php endif; ?>

                        <div class="promo-image">
                          <img
                            src="<?php echo htmlspecialchars($sidebarSrc, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($sidebarAlt, ENT_QUOTES, 'UTF-8'); ?>">
                          <div class="promo-overlay"></div>
                        </div>

                      <?php if ($sidebarLink): ?>
                        </a>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>

                <div class="sidebar-slider-dots">
                  <?php foreach ($homeSidebarBanners as $idx => $sb): ?>
                    <button
                      class="sidebar-dot<?php echo $idx === 0 ? ' active' : ''; ?>"
                      data-slide="<?php echo $idx; ?>">
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php else: ?>
              <!-- Fallback static image if no DB sidebar banners -->
              <div class="card promo-card">
                <div class="promo-image">
                  <img
                    src="https://dove.themeftc.com/wp-content/uploads/2021/05/bn-left20.jpg"
                    alt="Lucky day 50 outfits">
                  <div class="promo-overlay"></div>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <div class="card">
            <div class="card-header">
              <div class="label">
                <i class="fa-solid fa-tag"></i>
                <span>Popular Tags</span>
              </div>
            </div>
              <div class="tags-wrap">
                <?php if (!empty($popularTags)): ?>
                  <?php foreach ($popularTags as $tag): ?>
                    <a
                      class="tag-item"
                      href="products.php?tag=<?php echo urlencode($tag['slug']); ?>"
                    >
                      <?php echo htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span style="font-size:12px;color:#777;">
                    No popular tags yet.
                  </span>
                <?php endif; ?>
              </div>
          </div>

          <div class="card ad-card">
            <?php if (!empty($homeOfferBanner) && !empty($homeOfferBanner['filename'])): ?>
              <?php
                $offerSrc  = '/assets/uploads/banners/' . ltrim($homeOfferBanner['filename'], '/');
                $offerAlt  = $homeOfferBanner['alt_text'] ?? '';
                $offerLink = trim($homeOfferBanner['link'] ?? '');
              ?>

              <?php if ($offerLink): ?>
                <a href="<?php echo htmlspecialchars($offerLink, ENT_QUOTES, 'UTF-8'); ?>" style="display:block;">
              <?php endif; ?>

                <img
                  src="<?php echo htmlspecialchars($offerSrc, ENT_QUOTES, 'UTF-8'); ?>"
                  alt="<?php echo htmlspecialchars($offerAlt, ENT_QUOTES, 'UTF-8'); ?>"
                  style="width:100%;display:block;border-radius:8px;">
              <?php if ($offerLink): ?>
                </a>
              <?php endif; ?>

            <?php else: ?>
              <!-- fallback: your old design -->
              <div class="ad-badge">Limited Offer</div>
              <div class="ad-title">Flat 25% Off on Hair Ritual Kits</div>
              <div class="ad-text">
                Build your complete Devilixirs hair routine – oils, cleansers &amp; serums in one combo.
              </div>
              <button class="ad-cta">
                Shop Offer
                <i class="fa-solid fa-arrow-right"></i>
              </button>
            <?php endif; ?>
          </div>
        </aside>

        <!-- RIGHT COLUMN -->
        <div class="main-column">
          <!-- HERO -->
          <section class="hero-section">
            <?php if (!empty($homeBanners)): ?>
              <div class="hero-slider">
                <div class="hero-slider-track">
                  <?php foreach ($homeBanners as $idx => $b): ?>
                    <?php
                      $src  = '/assets/uploads/banners/' . ltrim($b['filename'] ?? '', '/');
                      $alt  = $b['alt_text'] ?? '';
                      $link = trim($b['link'] ?? '');
                    ?>
                    <div
                      class="hero-slide"
                      style="background-image:url('<?php echo htmlspecialchars($src, ENT_QUOTES); ?>');"
                    >
                      <?php if ($link): ?>
                        <a href="<?php echo htmlspecialchars($link); ?>" class="hero-slide-link">
                      <?php endif; ?>

                        <div class="hero-slide-overlay">
                          <div class="hero-text">
                            <!-- optional overlay text -->
                          </div>
                        </div>

                      <?php if ($link): ?>
                        </a>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>

                <!-- Dots (indicators) -->
                <div class="hero-slider-dots">
                  <?php foreach ($homeBanners as $idx => $b): ?>
                    <button
                      class="hero-dot<?php echo $idx === 0 ? ' active' : ''; ?>"
                      data-slide="<?php echo $idx; ?>">
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php else: ?>
              <!-- Fallback if no banners in DB -->
              <div class="hero-banner hero-fallback">
                <div class="hero-text">
                  <small>New Ayurvedic Range 2025</small>
                  <h1>PURE HERBAL HAIR &amp; SKIN CARE</h1>
                  <p>
                    Nourish your routine with cold-pressed oils, aloe-based cleansers and gentle blends
                    crafted to support healthy hair and glowing skin.
                  </p>
                  <button class="btn-primary">Shop Devilixirs</button>
                </div>
              </div>
            <?php endif; ?>
          </section>

          <!-- ALL OTHER CONTENT -->
          <section class="content-grid">
            <!-- Top banners -->
            <div class="banner-row">
  <?php if (!empty($subCategories)): ?>
    <?php foreach ($subCategories as $sub): ?>
      <?php
        // Default category image
        $catImg = '/assets/images/category-placeholder.jpg';

        if (!empty($sub['img'])) {
          $imgVal = trim($sub['img']);

          if (preg_match('#^https?://#i', $imgVal) || strpos($imgVal, '/') === 0) {
            // full URL or absolute path
            $catImg = $imgVal;
          } else {
            // assume stored as filename under uploads
            $catImg = '/assets/uploads/categories/' . ltrim($imgVal, '/');
          }
        }

        // 🔹 Build URL with subcategory ID only
        // This will filter products where category_id = subcategory ID
        $url = 'product.php?cat=' . (int)$sub['id'];
      ?>
      <a class="banner-item"
         href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
        <img
          src="<?php echo htmlspecialchars($catImg, ENT_QUOTES, 'UTF-8'); ?>"
          alt="<?php echo htmlspecialchars($sub['title'], ENT_QUOTES, 'UTF-8'); ?>"
        >
        <div class="banner-caption">
          <h4><?php echo htmlspecialchars($sub['title']); ?></h4>
        </div>
      </a>
    <?php endforeach; ?>
  <?php else: ?>
    <!-- Fallback if no subcategories found -->
    <div class="banner-item">
      <img src="https://images.pexels.com/photos/3738335/pexels-photo-3738335.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
      <div class="banner-caption">
        <h4>No subcategories</h4>
        <span>Add some in admin panel</span>
      </div>
    </div>
  <?php endif; ?>
</div>
            <!-- New Herbal Products (latest products from DB) -->
            <div class="section-header">
              <h3>New Herbal Products</h3>
              <div class="section-arrows">
                <span><i class="fa-solid fa-chevron-left"></i></span>
                <span><i class="fa-solid fa-chevron-right"></i></span>
              </div>
            </div>

            <div class="product-grid">
              <?php if (!empty($newProducts)): ?>
                <?php foreach ($newProducts as $p): ?>
                  <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                  <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" class="product-card" style="text-decoration:none; color:inherit; display:block;">
                    <div class="product-image">
                      <img src="<?php echo $img; ?>" alt="Product">
                    </div>
                    <div class="product-name">
                      <?php echo htmlspecialchars($p['name']); ?>
                    </div>
                    <div class="product-price">
                      ₹<?php echo number_format((float)$p['price'], 2); ?>
                    </div>
                    <div class="product-stars">★★★★★</div>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="font-size:12px; color:#777;">No products available yet.</p>
              <?php endif; ?>
            </div>

            <!-- Wide banner (NOW USING home_center banner) -->
            <div class="wide-banner">
              <?php if (!empty($homeCenterBanner) && !empty($homeCenterBanner['filename'])): ?>
                <?php
                  $centerSrc  = '/assets/uploads/banners/' . ltrim($homeCenterBanner['filename'], '/');
                  $centerAlt  = $homeCenterBanner['alt_text'] ?? '';
                  $centerLink = trim($homeCenterBanner['link'] ?? '');
                ?>
                <?php if ($centerLink): ?>
                  <a href="<?php echo htmlspecialchars($centerLink, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; position:relative;">
                <?php endif; ?>

                  <img
                    src="<?php echo htmlspecialchars($centerSrc, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?php echo htmlspecialchars($centerAlt, ENT_QUOTES, 'UTF-8'); ?>"
                  >

                <?php if ($centerLink): ?>
                  </a>
                <?php endif; ?>
              <?php else: ?>
                <!-- Fallback static image if no center banner in DB -->
                <img src="https://images.pexels.com/photos/3738344/pexels-photo-3738344.jpeg?auto=compress&cs=tinysrgb&w=1200" alt="">
                <div class="wide-banner-text">
                  <h2>We Make It Easy To Choose Clean Beauty</h2>
                  <p>Plant-based formulas, no harsh chemicals – just Devilixirs.</p>
                </div>
              <?php endif; ?>
            </div>

            <!-- Best Sellers (from homepage_products -> best_seller) -->
            <div style="margin-top:30px;">
              <div class="section-header">
                <h3>Best Sellers</h3>
                <div class="section-arrows">
                  <span><i class="fa-solid fa-chevron-left"></i></span>
                  <span><i class="fa-solid fa-chevron-right"></i></span>
                </div>
              </div>

              <div class="product-grid">
                <?php if (!empty($bestProducts)): ?>
                  <?php foreach ($bestProducts as $p): ?>
                    <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                    <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" class="product-card" style="text-decoration:none; color:inherit; display:block;">
                      <div class="product-image">
                        <img src="<?php echo $img; ?>" alt="Product">
                      </div>
                      <div class="product-name">
                        <?php echo htmlspecialchars($p['name']); ?>
                      </div>
                      <div class="product-price">
                        ₹<?php echo number_format((float)$p['price'], 2); ?>
                      </div>
                      <div class="product-stars">★★★★★</div>
                    </a>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:12px; color:#777;">No best sellers available yet.</p>
                <?php endif; ?>
              </div>
            </div>

            <!-- LATEST BLOG SECTION (static) -->
            <div class="latest-blog-section">
              <div class="section-header">
                <h3>Latest Blog</h3>
                <div class="section-arrows">
                  <span><i class="fa-solid fa-chevron-left"></i></span>
                  <span><i class="fa-solid fa-chevron-right"></i></span>
                </div>
              </div>
              <div class="blog-grid">
                <?php if (!empty($latestBlogs)): ?>
                  <?php foreach ($latestBlogs as $blog): ?>
                    <article class="blog-card">
                      <a href="blog_single.php?slug=<?= htmlspecialchars($blog['slug']) ?>" style="text-decoration:none;color:inherit;">
                        <div class="blog-card-image">
                          <img src="<?= htmlspecialchars($blog['featured_image'] ?: '/assets/images/blog-default.jpg') ?>" alt="<?= htmlspecialchars($blog['title']) ?>">
                        </div>
                        <div class="blog-card-body">
                          <div class="blog-title"><?= htmlspecialchars($blog['title']) ?></div>
                          <div class="blog-meta">
                            <span><i class="fa-regular fa-calendar"></i> <?= date('F j, Y', strtotime($blog['created_at'])) ?></span>
                          </div>
                          <p class="blog-excerpt">
                            <?php 
                              $excerpt = strip_tags($blog['content']);
                              echo htmlspecialchars(strlen($excerpt) > 120 ? substr($excerpt, 0, 120) . '...' : $excerpt);
                            ?>
                          </p>
                        </div>
                      </a>
                    </article>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="grid-column:1/-1;text-align:center;color:#999;padding:40px 0;">No blog posts available yet.</p>
                <?php endif; ?>
              </div>
            </div>

            <!-- MULTI COLUMN TABBED PRODUCTS -->
            <div class="tabbed-products-section">
              <div class="tabs-bar">
                <div class="tab-ribbon">Latest Products</div>
                <div class="tab-ribbon">Trendy Products</div>
                <div class="tab-ribbon">Sale Products</div>
                <div class="tab-ribbon">Top Rated</div>
              </div>

              <div class="tabbed-columns">
                <!-- Latest -->
                <div class="tab-column">
                  <?php if (!empty($tabLatest)): ?>
                    <?php foreach ($tabLatest as $p): ?>
                      <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" class="tab-column-card" style="text-decoration:none; color:inherit; display:block;">
                        <div class="tab-col-product">
                          <img src="<?php echo $img; ?>" alt="Product">
                          <div class="tab-col-info">
                            <div class="tab-col-name"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div class="tab-col-price">
                              ₹<?php echo number_format((float)$p['price'], 2); ?>
                            </div>
                            <div class="tab-col-stars">★★★★★</div>
                          </div>
                        </div>
                      </a>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p style="font-size:11px; color:#777; padding:8px 0;">No latest products.</p>
                  <?php endif; ?>
                </div>

                <!-- Trendy -->
                <div class="tab-column">
                  <?php if (!empty($tabTrendy)): ?>
                    <?php foreach ($tabTrendy as $p): ?>
                      <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" class="tab-column-card" style="text-decoration:none; color:inherit; display:block;">
                        <div class="tab-col-product">
                          <img src="<?php echo $img; ?>" alt="Product">
                          <div class="tab-col-info">
                            <div class="tab-col-name"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div class="tab-col-price">
                              ₹<?php echo number_format((float)$p['price'], 2); ?>
                            </div>
                            <div class="tab-col-stars">★★★★★</div>
                          </div>
                        </div>
                      </a>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p style="font-size:11px; color:#777; padding:8px 0;">No trendy products.</p>
                  <?php endif; ?>
                </div>

                <!-- Sale -->
                <div class="tab-column">
                  <?php if (!empty($tabSale)): ?>
                    <?php foreach ($tabSale as $p): ?>
                      <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" class="tab-column-card" style="text-decoration:none; color:inherit; display:block;">
                        <div class="tab-col-product">
                          <img src="<?php echo $img; ?>" alt="Product">
                          <div class="tab-col-info">
                            <div class="tab-col-name"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div class="tab-col-price">
                              ₹<?php echo number_format((float)$p['price'], 2); ?>
                            </div>
                            <div class="tab-col-stars">★★★★★</div>
                          </div>
                        </div>
                      </a>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p style="font-size:11px; color:#777; padding:8px 0;">No sale products.</p>
                  <?php endif; ?>
                </div>

                <!-- Top rated -->
                <div class="tab-column">
                  <?php if (!empty($tabTop)): ?>
                    <?php foreach ($tabTop as $p): ?>
                      <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" class="tab-column-card" style="text-decoration:none; color:inherit; display:block;">
                        <div class="tab-col-product">
                          <img src="<?php echo $img; ?>" alt="Product">
                          <div class="tab-col-info">
                            <div class="tab-col-name"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div class="tab-col-price">
                              ₹<?php echo number_format((float)$p['price'], 2); ?>
                            </div>
                            <div class="tab-col-stars">★★★★★</div>
                          </div>
                        </div>
                      </a>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p style="font-size:11px; color:#777; padding:8px 0;">No top rated products.</p>
                  <?php endif; ?>
                </div>

              </div>
            </div>

          </section>
        </div>
      </div>

    </div>
    </div>
    
    <!-- CUSTOMER REVIEWS SECTION -->
    <?php if (!empty($homeReviews)): ?>
    <section class="section-reviews" style="padding: 60px 0; background: #fff;">
      <div class="container">
        <div class="section-header" style="text-align:center; margin-bottom:40px;">
          <h2 style="font-family:'Playfair Display', serif; font-size:32px; color:#333; margin-bottom:10px;">Customer Love</h2>
          <div style="width:60px; height:3px; background:#A41B42; margin:0 auto 15px;"></div>
          <p style="color:#777; font-size:14px;">See what our happy customers have to say</p>
        </div>
        
        <div class="reviews-scroll-container" style="display:flex; overflow-x:auto; overflow-y:hidden; gap:20px; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; scrollbar-width: none; padding-bottom: 10px;">
          <?php foreach ($homeReviews as $review): 
              $prodImg = get_first_image($review['product_images']);
          ?>
            <div class="home-review-card" style="background:#f9f9f9; padding:30px; border-radius:16px; border:1px solid #eee; transition:all 0.3s ease; flex: 0 0 350px; scroll-snap-align: start;">
              <div class="review-stars" style="color:#ffb400; font-size:16px; margin-bottom:15px;">
                <?= str_repeat('★', (int)$review['rating']) ?><?= str_repeat('☆', 5 - (int)$review['rating']) ?>
              </div>
              <p class="review-text" style="font-size:15px; line-height:1.7; color:#444; margin-bottom:25px; min-height:80px;">
                "<?= htmlspecialchars(mb_strimwidth($review['comment'], 0, 150, '...')) ?>"
              </p>
              <div class="review-meta" style="display:flex; align-items:center; gap:15px; padding-top:20px; border-top:1px solid #e5e5e5;">
                <a href="product_view.php?id=<?= $review['product_id'] ?>" class="review-prod-img" style="width:60px; height:60px; border-radius:10px; overflow:hidden; flex-shrink:0; border:1px solid #ddd;">
                  <img src="<?= htmlspecialchars($prodImg) ?>" alt="Product" style="width:100%; height:100%; object-fit:cover;">
                </a>
                <div class="review-info">
                  <div class="reviewer-name" style="font-weight:700; font-size:15px; color:#222; margin-bottom:4px;">
                    <?= htmlspecialchars($review['reviewer_name'] ?: 'Verified Customer') ?>
                  </div>
                  <div class="reviewed-product" style="font-size:12px; color:#777;">
                    Review for <a href="product_view.php?id=<?= $review['product_id'] ?>" style="color:#A41B42; font-weight:600; text-decoration:none;"><?= htmlspecialchars($review['product_name']) ?></a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <style>
        .home-review-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 10px 30px rgba(0,0,0,0.08);
          background: #fff;
          border-color: #A41B42;
        }
        
        /* Hide scrollbar for cleaner look */
        .reviews-scroll-container::-webkit-scrollbar {
          display: none;
        }
        
        /* Prevent horizontal page overflow */
        .section-reviews {
          overflow: hidden;
        }
        
        .home-review-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 10px 30px rgba(0,0,0,0.08);
          background: #fff;
          border-color: #A41B42;
        }
        
        /* Mobile: Optimize for horizontal scrolling */
        @media (max-width: 768px) {
          .reviews-scroll-container {
            gap: 15px !important;
            padding: 0 15px 10px 15px;
            margin: 0 -15px;
          }
          
          .home-review-card {
            flex: 0 0 85% !important;
            padding: 20px !important;
          }
          
          .review-stars {
            font-size: 14px !important;
            margin-bottom: 10px !important;
          }
          
          .review-text {
            font-size: 13px !important;
            line-height: 1.6 !important;
            margin-bottom: 15px !important;
            min-height: 60px !important;
          }
          
          .review-meta {
            padding-top: 12px !important;
            gap: 10px !important;
          }
          
          .review-prod-img {
            width: 50px !important;
            height: 50px !important;
          }
          
          .reviewer-name {
            font-size: 14px !important;
          }
          
          .reviewed-product {
            font-size: 11px !important;
          }
        }
        
        /* Very small screens: Full width cards */
        @media (max-width: 480px) {
          .home-review-card {
            flex: 0 0 90% !important;
          }
        }
      </style>
      
      <script>
        // Auto-scroll reviews carousel
        document.addEventListener('DOMContentLoaded', function() {
          const reviewsContainer = document.querySelector('.reviews-scroll-container');
          
          if (reviewsContainer) {
            let scrollInterval;
            let isPaused = false;
            
            // Auto-scroll function
            function autoScroll() {
              if (!isPaused && reviewsContainer) {
                const cardWidth = reviewsContainer.querySelector('.home-review-card').offsetWidth;
                const gap = 20; // gap between cards
                const scrollAmount = cardWidth + gap;
                
                // Check if we've reached the end
                if (reviewsContainer.scrollLeft + reviewsContainer.offsetWidth >= reviewsContainer.scrollWidth - 10) {
                  // Reset to beginning
                  reviewsContainer.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                  // Scroll to next card
                  reviewsContainer.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                }
              }
            }
            
            // Start auto-scrolling every 4 seconds
            scrollInterval = setInterval(autoScroll, 4000);
            
            // Pause on hover (desktop)
            reviewsContainer.addEventListener('mouseenter', function() {
              isPaused = true;
            });
            
            reviewsContainer.addEventListener('mouseleave', function() {
              isPaused = false;
            });
            
            // Pause on touch/scroll (mobile)
            reviewsContainer.addEventListener('touchstart', function() {
              isPaused = true;
              // Resume after 5 seconds of no interaction
              clearTimeout(reviewsContainer.resumeTimeout);
              reviewsContainer.resumeTimeout = setTimeout(function() {
                isPaused = false;
              }, 5000);
            });
            
            reviewsContainer.addEventListener('scroll', function() {
              isPaused = true;
              clearTimeout(reviewsContainer.resumeTimeout);
              reviewsContainer.resumeTimeout = setTimeout(function() {
                isPaused = false;
              }, 5000);
            });
          }
        });
      </script>
    </section>
    <?php endif; ?>

  </main>

  <!-- FOOTER -->
  <!-- FOOTER -->
  <?php include 'footer.php'; ?>

  <!-- JS -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const catCard = document.querySelector('.categories-card');
      const toggleHeader = document.querySelector('.categories-toggle');

      if (catCard && toggleHeader) {
        toggleHeader.addEventListener('click', function () {
          catCard.classList.toggle('collapsed');
        });
      }
    });
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function(){
      const shopMenu = document.querySelector('.nav li.has-mega');

      if(window.innerWidth <= 768 && shopMenu){
        shopMenu.addEventListener('click', function(e){
          e.stopPropagation();
          this.classList.toggle('open');
        });
      }
    });
  </script>

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
    });
  </script>

  <!-- Auto scroll banner -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const row = document.querySelector('.banner-row');
      if (!row) return;

      const items = Array.from(row.querySelectorAll('.banner-item'));
      const itemCount = items.length;
      if (itemCount === 0) return;

      // if only one subcategory, no need to clone or scroll
      if (itemCount === 1) return;

      // duplicate items to make loop seamless
      items.forEach(item => {
        const clone = item.cloneNode(true);
        row.appendChild(clone);
      });

      let index = 0;
      const speed = 2000;

      function getItemWidth() {
        return items[0].offsetWidth;
      }

      setInterval(() => {
        const itemWidth = getItemWidth();
        index++;

        if (index > itemCount) {
          row.scrollLeft -= itemWidth * itemCount;
          index -= itemCount;
        }

        row.scrollTo({
          left: itemWidth * index,
          behavior: 'smooth'
        });
      }, speed);
    });
  </script>

  <!-- Hero slider auto -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const track = document.querySelector('.hero-slider-track');
      if (!track) return;

      const slides = Array.from(document.querySelectorAll('.hero-slide'));
      const dots   = Array.from(document.querySelectorAll('.hero-dot'));

      if (slides.length <= 1) return; // nothing to slide

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

      startTimer();
    });
  </script>

  <!-- Sidebar slider auto -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sTrack = document.querySelector('.sidebar-slider-track');
      if (!sTrack) return;

      const sSlides = Array.from(sTrack.querySelectorAll('.sidebar-slide'));
      const sDots   = Array.from(document.querySelectorAll('.sidebar-dot'));

      if (sSlides.length <= 1) return; // nothing to slide

      let sIndex = 0;
      const sTotal = sSlides.length;
      const sDelay = 3000;

      function sidebarGoTo(i){
        sIndex = i;
        const slideWidth = sSlides[0].offsetWidth;
        sTrack.style.transform = 'translateX(' + (-sIndex * slideWidth) + 'px)';

        sDots.forEach(d => d.classList.remove('active'));
        if (sDots[sIndex]) sDots[sIndex].classList.add('active');
      }

      sDots.forEach(function(dot){
        dot.addEventListener('click', function(){
          const s = parseInt(dot.getAttribute('data-slide') || '0', 10);
          sidebarGoTo(s);
          sidebarResetTimer();
        });
      });

      let sTimer = null;
      function sidebarStartTimer(){
        sTimer = setInterval(function(){
          const next = (sIndex + 1) % sTotal;
          sidebarGoTo(next);
        }, sDelay);
      }

      function sidebarResetTimer(){
        if (sTimer) clearInterval(sTimer);
        sidebarStartTimer();
      }

      sidebarGoTo(0);
      sidebarStartTimer();
    });
  </script>

  <script>
document.addEventListener('DOMContentLoaded', function () {
  const toggle   = document.getElementById('searchCategoryToggle');
  const dropdown = document.getElementById('searchCategoryDropdown');
  const label    = document.getElementById('searchCategoryLabel');
  const input    = document.getElementById('searchCategoryInput');

  if (!toggle || !dropdown || !label || !input) return;

  // open / close dropdown
  toggle.addEventListener('click', function (e) {
    e.stopPropagation();
    toggle.classList.toggle('open');
  });

  // clicking on an item
  dropdown.querySelectorAll('li').forEach(function (li) {
    li.addEventListener('click', function (e) {
      e.stopPropagation();
      const catId   = this.getAttribute('data-cat-id') || '';
      const catName = this.getAttribute('data-cat-name') || 'All categories';

      // update label + hidden input
      input.value = catId;
      label.textContent = catId ? catName : 'All categories';

      toggle.classList.remove('open');
    });
  });

  // click outside closes
  document.addEventListener('click', function () {
    toggle.classList.remove('open');
  });
});
</script>

</body>
</html>