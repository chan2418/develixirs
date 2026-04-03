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
        JOIN products p ON p.id = pt.product_id AND p.is_active = 1
        WHERE t.is_active = 1
        GROUP BY t.id
        ORDER BY usage_count DESC, t.name ASC
        LIMIT 10
    ");
    $popularTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $popularTags = [];
}

// ================== CONCERNS ==================
$concerns = [];
try {
    $stmt = $pdo->query("SELECT * FROM concerns ORDER BY title ASC");
    $concerns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $concerns = [];
}

// ================== SEASONALS ==================
$seasonals = [];
try {
    $stmt = $pdo->query("SELECT * FROM seasonals ORDER BY title ASC");
    $seasonals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $seasonals = [];
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

// ================== HOME CENTER BANNERS (FOR WIDE BANNER CAROUSEL) ==================
$homeCenterBanners = [];
try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM banners
        WHERE is_active = 1
        AND page_slot = 'home_center'
        ORDER BY id DESC
    ");
    $stmt->execute();
    $rawBanners = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Filter out invalid banners (Check extension AND file existence)
    $validExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    foreach ($rawBanners as $b) {
        $fname = trim($b['filename'] ?? '');
        $ext   = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
        
        // Construct physical path to check existence
        $bannerPath = __DIR__ . '/assets/uploads/banners/' . ltrim($fname, '/');

        if (!empty($fname) && $fname !== 'null' && in_array($ext, $validExts)) {
            if (file_exists($bannerPath)) {
                $homeCenterBanners[] = $b;
            }
        }
    }
} catch (PDOException $e) {
    $homeCenterBanners = [];
}
    
// ================== HOME BEFORE BLOGS BANNERS (CAROUSEL) ==================
$homeBeforeBlogsBanners = [];
try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM banners
        WHERE is_active = 1
        AND page_slot = 'home_before_blogs'
        ORDER BY id DESC
    ");
    $stmt->execute();
    $homeBeforeBlogsBanners = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $homeBeforeBlogsBanners = [];
}

// ================== SITE SETTINGS (Dynamic Features) ==================
$siteSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $siteSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // ignore
}
// Parse specific settings
$subscribeImage = !empty($siteSettings['subscribe_image']) ? $siteSettings['subscribe_image'] : 'assets/images/category-placeholder.jpg';
$featuresJson = !empty($siteSettings['features_json']) ? $siteSettings['features_json'] : '[]';
$features = json_decode($featuresJson, true);

// Fallback defaults if empty
if (empty($features)) {
    $features = [
        ['icon' => 'fa-solid fa-earth-americas', 'title' => 'Worldwide Shipping', 'desc' => 'Free worldwide shipping across the globe'],
        ['icon' => 'fa-brands fa-whatsapp', 'title' => 'Whatsapp Customer', 'desc' => '24-day hassle-free return policy'],
        ['icon' => 'fa-regular fa-credit-card', 'title' => 'Secured Payments', 'desc' => 'We accept all major credit cards'],
        ['icon' => 'fa-solid fa-truck-fast', 'title' => 'Quick Delivery', 'desc' => 'Free shipping across India above ₹499'],
        ['icon' => 'fa-solid fa-leaf', 'title' => 'Freshly Made', 'desc' => 'We make your produce fresh batches'],
    ];
}

// Fetch Quick Links Pages
$quickLinksPages = [];
$quickLinksJson = !empty($siteSettings['quick_links_json']) ? $siteSettings['quick_links_json'] : '[]';
$quickLinkIds = json_decode($quickLinksJson, true) ?: [];
if (!empty($quickLinkIds)) {
    try {
        $placeholders = implode(',', array_fill(0, count($quickLinkIds), '?'));
        $stmt = $pdo->prepare("SELECT id, title, slug FROM pages WHERE id IN ($placeholders) AND status = 'published' ORDER BY FIELD(id, $placeholders)");
        $stmt->execute(array_merge($quickLinkIds, $quickLinkIds));
        $quickLinksPages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $quickLinksPages = [];
    }
}


// Our Story (Single)
$osTitle = !empty($siteSettings['our_story_title']) ? $siteSettings['our_story_title'] : '';
$osDesc  = !empty($siteSettings['our_story_description']) ? $siteSettings['our_story_description'] : '';
$osImage = !empty($siteSettings['our_story_image']) ? $siteSettings['our_story_image'] : '';

// Our Stories Section (Multiple Stories)
$ourStoriesJson = !empty($siteSettings['our_stories_json']) ? $siteSettings['our_stories_json'] : '[]';
$ourStories = json_decode($ourStoriesJson, true) ?: [];

// Certification Section
$certTitle = !empty($siteSettings['cert_section_title']) ? $siteSettings['cert_section_title'] : 'Certified Excellence';
$certIcon = !empty($siteSettings['cert_section_icon']) ? $siteSettings['cert_section_icon'] : 'fa-solid fa-award';
$certBadgesJson = !empty($siteSettings['cert_badges_json']) ? $siteSettings['cert_badges_json'] : '[]';
$certBadges = json_decode($certBadgesJson, true);
if (empty($certBadges)) {
    // Default 3 badges
    $certBadges = [
        ['title' => 'GMP Certified', 'image' => 'assets/images/badge-gmp.png'],
        ['title' => 'AYUSH Premium', 'image' => 'assets/images/badge-ayush.png'],
        ['title' => 'ISO 9001:2015', 'image' => 'assets/images/badge-iso.png'],
    ];
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
        WHERE hp.section = :section AND p.is_active = 1
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

    // 1) FORCE IMAGE FIELD SELECTION (Since we verified column is 'image')
    $imgField = 'image';
    $imgSelect = ", image AS img";

    // DIRECT HARDCODED QUERY TO DEBUG
    // We ignore $labelField logic for a moment to ensure we get data
    $catSql = "SELECT * FROM categories WHERE parent_id = 0 OR parent_id IS NULL ORDER BY id ASC";
    $catStmt    = $pdo->query($catSql);
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

    // ===== BANNER ROW (PRODUCT GROUPS) =====
    $bannerGroups = [];
    try {
        $stmt = $pdo->query("SELECT * FROM product_groups ORDER BY id DESC LIMIT 12");
        $bannerGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $bannerGroups = [];
    }

    /**
     * 1) NEW HERBAL PRODUCTS + DEVILIXIRS PICKS + LATEST TAB
     *    - all come from latest products in `products` table (auto, no manual selection)
     */
    $stmt = $pdo->query("
        SELECT id, name, price, images
        FROM products
        WHERE is_active = 1
        ORDER BY id DESC
        LIMIT 8
    ");
    $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // New Herbal Products – MANUAL SELECTION ONLY
    // If empty, show nothing (per user request to stop auto-showing)
    $newProducts = fetch_home_section($pdo, 'new_herbal', 8);

    // Devilixirs Picks – first 2 latest (still auto from allProducts)
    $picksProducts = array_slice($allProducts, 0, 2);

    // Latest Products (tab column) – NOW MANUAL SELECTION
    // We fetch more (e.g. 8) just in case, though display limits are handled elsewhere or by CSS
    $tabLatest = fetch_home_section($pdo, 'latest', 8);

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

    // ===== WEEKLY SPECIALS (Discounted Products) =====
    $weeklySpecials = [];
    try {
        $stmtSpecials = $pdo->query("
            SELECT id, name, price, compare_price, images
            FROM products
            WHERE compare_price IS NOT NULL 
              AND compare_price > price
            ORDER BY ((compare_price - price) / compare_price) DESC
            LIMIT 3
        ");
        $weeklySpecials = $stmtSpecials->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $weeklySpecials = [];
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

// Fetch Homepage Settings
$homeSettings = [];
try {
    $stmtVS = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'home_%' OR setting_key LIKE 'show_%'");
    while ($row = $stmtVS->fetch(PDO::FETCH_ASSOC)) {
        $homeSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

$seoTitle = !empty($homeSettings['home_seo_title']) ? $homeSettings['home_seo_title'] : 'DevElixir Natural Cosmetics | Authentic Ayurvedic Beauty Products Online';
$seoDesc = !empty($homeSettings['home_seo_description']) ? $homeSettings['home_seo_description'] : 'Shop authentic ayurvedic beauty products, natural cosmetics, and herbal skincare at DevElixir. Ancient wellness secrets since 2005. Free shipping on orders ₹1000+';

// Video Section Vars (Global)
$hvUrl = $homeSettings['home_video_url'] ?? '';
$hvTitle = $homeSettings['home_video_title'] ?? '';
$hvDesc = $homeSettings['home_video_desc'] ?? '';
$hvBtnText = $homeSettings['home_video_btn_text'] ?? '';
$hvBtnLink = $homeSettings['home_video_btn_link'] ?? '';
$hvBtnColor = $homeSettings['home_video_btn_color'] ?? '#4F46E5';

// Product Section Visibility (0 or 1)
$showLatest = $homeSettings['show_latest_products'] ?? '1';
$showTrendy = $homeSettings['show_trendy_products'] ?? '1';
$showSale   = $homeSettings['show_sale_products'] ?? '1';
$showTop    = $homeSettings['show_top_rated_products'] ?? '1';

// Calculate grid columns for visible tabs
$visibleTabs = 0;
if ($showLatest == '1') $visibleTabs++;
if ($showTrendy == '1') $visibleTabs++;
if ($showSale == '1')   $visibleTabs++;
if ($showTop == '1')    $visibleTabs++;

// Default to 4 if somehow 0 to prevent CSS errors, though layout handles it
$gridCols = $visibleTabs > 0 ? $visibleTabs : 4;

// Remove old static vars
// $bbImg = ... (Removed in favor of banners table)

// Generate SEO meta tags
echo generate_seo_meta([
    'title' => $seoTitle,
    'description' => $seoDesc,
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

    html, body{
      font-family:'Poppins',sans-serif;
      color:var(--text);
      background:#dadada00; /* was #ffffff */
      overflow-x: hidden;
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

    /* BANNERS ROW (Auto Scroll Marquee) */
    .banner-row-container {
      margin-bottom: 26px;
      overflow: hidden;
      width: 100%;
      max-width: 100%; /* Fix: Strict container width */
      position: relative;
      /* Optional: Fade edges */
      mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
      -webkit-mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
    }
    
    /* FIX GRID OVERFLOW */
    .main-column, .content-grid {
      min-width: 0; /* CRITICAL: Prevents Grid blowout */
    }
    
    .banner-row{
      display:flex;
      gap:12px;
      padding-bottom: 5px;
      width: max-content; /* Allow track to be as long as needed */
      animation: bannerMarquee 30s linear infinite; /* Auto Scroll Animation */
    }
    
    .banner-row:hover {
      animation-play-state: paused; /* Pause on hover */
    }

    @keyframes bannerMarquee {
      0% { transform: translateX(0); }
      100% { transform: translateX(-33.33%); } /* Move 1/3 since we triplicated data */
    }

    .banner-item{
      position:relative;
      overflow:hidden;
      background:#fff; /* changed from #eee */
      border:1px solid var(--border);
      flex: 0 0 auto;
      width: 110px;   /* Even smaller width */
      height: 140px;  /* Even smaller height */
      border-radius: 20px; /* More curved edges */
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      text-decoration: none;
    }
    .banner-item img{
      width:100%;
      height:100%;
      object-fit:cover; /* Keeps it filling the card */
      transition:transform .3s ease;
    }
    .banner-item:hover img{
      transform:scale(1.04);
    }
    .banner-caption{
      position:absolute;
      left:0;
      bottom:0;
      width: 100%;
      padding: 10px;
      color:#fff;
      background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
      text-shadow:0 2px 4px rgba(0,0,0,0.8);
      text-align: center; /* Center text */
    }
    .banner-caption h4{
      font-size:14px;
      font-weight:700;
      margin-bottom:0;
      line-height: 1.2;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }
    .banner-caption span{
      font-size:9px;
      display: none;
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
      display:flex;
      flex-direction:column;
      height:100%;
      justify-content:space-between;
    }
    .product-card > a {
      flex-grow:1;
      display:flex;
      flex-direction:column;
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

    /* Weekly Specials Widget Styles */
    .weekly-specials-card .card-header {
      background: linear-gradient(135deg, #2ECC71, #27ae60);
    }
    .special-product {
      transition: all 0.2s ease;
    }
    .special-product:hover {
      background: #f5f7fa;
      margin: 0 -12px;
      padding-left: 22px !important;
      padding-right: 10px !important;
    }
    .special-product:last-of-type {
      border-bottom: none !important;
    }

    /* Quick Links Hover Effects */
    .card-body ul li a:hover {
      background: #f5f7fa;
      padding-left: 20px !important;
    }

    /* Newsletter Card Styles */
    .newsletter-card .card-header {
      background: linear-gradient(135deg, #667eea, #764ba2);
    }
    .newsletter-card input:focus {
      border-color: #667eea !important;
    }
    .newsletter-card button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(102,126,234,0.4) !important;
    }

    /* Blog Mini Hover Effect */
    .blog-mini:hover {
      background: #f5f7fa;
      margin: 0 -12px;
      padding-left: 22px !important;
      padding-right: 10px !important;
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

    /* SHOP BY CATEGORY - ALL FIT IN ONE ROW (NO SCROLL) */
    .top-categories-section {
      margin-top: 40px;
      margin-bottom: 20px;
    }
    
    .categories-visual-grid {
      display: flex;
      flex-wrap: nowrap; /* FORCE SINGLE LINE */
      gap: 16px;
      justify-content: space-evenly; /* Distribute evenly */
      text-align: center;
      width: 100%;
    }
    
    .categories-visual-grid a {
      flex: 1; /* Each item takes equal space */
      min-width: 0; /* Allow shrinking below content size */
      max-width: 120px; /* Max size on large screens */
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: inherit;
      transition: transform 0.2s;
    }
    
    .categories-visual-grid a:hover {
      transform: translateY(-5px);
    }

    /* Mobile: smaller items to fit */
    @media (max-width: 480px) {
      .categories-visual-grid {
        gap: 8px;
      }
      .categories-visual-grid a {
        gap: 6px;
        max-width: 70px;
      }
      .categories-visual-grid a > div {
        width: 55px !important;
        height: 55px !important;
      }
      .categories-visual-grid h4 {
        font-size: 9px !important;
        line-height: 1.2 !important;
      }
    }

    /* Tablet: medium items */
    @media (min-width: 481px) and (max-width: 768px) {
      .categories-visual-grid {
        gap: 12px;
      }
      .categories-visual-grid a {
        max-width: 90px;
      }
      .categories-visual-grid a > div {
        width: 75px !important;
        height: 75px !important;
      }
      .categories-visual-grid h4 {
        font-size: 11px !important;
      }
    }

    /* Desktop: full size items */
    @media (min-width: 769px) {
      .categories-visual-grid a {
        max-width: 120px;
      }
      .categories-visual-grid a > div {
        width: 100px !important;
        height: 100px !important;
      }
    }

    /* HOME VIDEO SECTION - RESPONSIVE */
    .home-video-section {
      margin-top: 40px;
      margin-bottom: 30px;
      background: #fff;
      border: 1px solid #eee;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }
    
    .home-video-section > div {
      display: flex;
      flex-wrap: wrap;
      align-items: stretch;
    }
    
    .home-video-section > div > div:first-child {
      flex: 1;
      min-width: 300px;
      position: relative;
      background: #000;
      min-height: 280px;
      max-height: 350px; /* REDUCED from 450px */
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .home-video-section > div > div:last-child {
      flex: 1;
      min-width: 300px;
      padding: 30px; /* REDUCED from 40px */
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .home-video-section iframe,
    .home-video-section video,
    .home-video-section img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      max-height: 350px; /* REDUCED from 450px */
    }
    
    .home-video-section iframe {
      border: 0;
      min-height: 280px; /* REDUCED from 350px */
    }
    
    .home-video-section h3 {
      font-size: 22px; /* REDUCED from 24px */
      font-weight: 800;
      margin-bottom: 15px;
      color: #1a1a1a;
      font-family: 'Poppins', sans-serif;
    }
    
    .home-video-section .prose {
      font-size: 13px; /* REDUCED from 15px */
      line-height: 1.6;
      color: #555;
      margin-bottom: 25px;
    }

    /* Mobile: stack vertically, reduce padding */
    @media (max-width: 768px) {
      .home-video-section {
        margin-top: 30px;
        margin-bottom: 20px;
      }
      .home-video-section > div {
        flex-direction: column !important;
      }
      .home-video-section > div > div:first-child {
        min-width: 100% !important;
        min-height: 250px !important;
        max-height: 250px !important;
      }
      .home-video-section > div > div:last-child {
        min-width: 100% !important;
        padding: 20px !important;
      }
      .home-video-section h3 {
        font-size: 20px !important;
        margin-bottom: 12px !important;
      }
      .home-video-section .prose {
        font-size: 14px !important;
        line-height: 1.6 !important;
        margin-bottom: 20px !important;
      }
      .home-video-section iframe,
      .home-video-section video,
      .home-video-section img {
        min-height: 250px !important;
        max-height: 250px !important;
      }
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

    /* DYNAMIC GRID COLUMNS BASED ON VISIBLE SECTIONS */
    .tabs-bar, .tabbed-columns {
      display: grid;
      grid-template-columns: repeat(<?php echo $gridCols; ?>, minmax(0, 1fr));
      column-gap: 18px; /* Match existing gap */
    }
    
    .tabbed-columns {
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



    /* CERTIFICATION BADGES - FIT IN ONE ROW (LIKE CATEGORIES) */
    .cert-badges-grid {
      display: flex;
      flex-wrap: nowrap !important;
      gap: 15px; /* Reduced gap */
      justify-content: space-evenly; /* Distribute evenly */
      align-items: flex-start;
      width: 100%;
      padding: 10px 0;
    }
    
    .cert-badge {
      flex: 1; /* Equal width */
      min-width: 0; /* Allow shrinking */
      max-width: 130px;
      display: flex;
      flex-direction: column;
      align-items: center;
      cursor: pointer;
      transition: transform 0.3s;
    }

    .cert-badge:hover {
      transform: translateY(-5px);
    }
    
    .cert-badge > div {
       /* Desktop default size */
       width: 100px !important;
       height: 100px !important;
       border-radius: 50%;
       background: #fff;
       display: flex;
       align-items: center;
       justify-content: center;
       box-shadow: 0 6px 20px rgba(0,0,0,0.1);
       margin-bottom: 12px;
       border: 3px solid #f8f9fa;
       transition: all 0.3s;
    }

    .cert-badge:hover > div {
       box-shadow: 0 10px 25px rgba(0,0,0,0.15);
       border-color: #667eea;
    }

    .cert-badge img {
        width: 65px !important;
        height: 65px !important;
        object-fit: contain;
    }
    
    .cert-badge i {
       font-size: 40px !important;
       color: #ccc;
    }

    @media (max-width: 768px) {
      .cert-badges-grid {
        gap: 8px; /* Tighter gap on mobile */
        padding: 0 5px;
      }
      .cert-badge {
        max-width: 80px; /* Limit width */
      }
      .cert-badge > div {
        width: 60px !important;
        height: 60px !important;
        margin-bottom: 8px !important;
        border-width: 2px !important;
      }
      .cert-badge img {
        width: 35px !important;
        height: 35px !important;
      }
      .cert-badge i {
        font-size: 24px !important;
      }
      .cert-badge p {
        font-size: 10px !important;
        line-height: 1.2;
        font-weight: 600;
        white-space: normal; /* Allow text wrapping */
        word-wrap: break-word;
      }
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
      .banner-row-container{
        /* Ensure it sits above other content if overlap occurs */
        position: relative;
        z-index: 5;
      }
      .banner-row{
        /* Keep marquee logic */
      }
      .banner-item{
        /* Mobile: Still mini cards, just slightly smaller */
        flex: 0 0 110px; 
        height: 75px; 
      }
      /* Mobile: Horizontal Scroll for Products */
      .product-grid{
        display: flex !important;
        flex-wrap: nowrap !important;
        overflow-x: auto;
        gap: 16px;
        padding-bottom: 20px;
        /* -webkit-overflow-scrolling: touch; */
        scrollbar-width: none;
      }
      .product-grid::-webkit-scrollbar {
        display: none;
      }
      .product-card {
        flex: 0 0 160px; /* Fixed width for scroll */
        min-width: 160px;
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
        grid-template-columns:repeat(2, 1fr);
        gap: 12px;
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
        /* FULL WIDTH BANNER FIX */
        margin-left: -15px;
        margin-right: -15px;
        width: calc(100% + 30px);
      }
      
      .hero-slider {
        border-radius: 0;
        border-left: none;
        border-right: none;
      }
    }

    @media (max-width:600px){
      .footer-inner{
        grid-template-columns:1fr;
      }
    }

    @media (max-width:480px){
      /* .product-grid handled in 768px query (scroll) */
      
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
        padding: 0 !important;
        min-height: auto !important;
        grid-auto-rows: min-content !important; /* Fix "too height" on header */
        gap: 8px; /* space between header and items */
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
    }



    /* ===== FEATURES ROW ===== */
    .features-section {
      background: #fff;
      padding: 40px 0;
      border-bottom: 1px solid #eee;
    }
    .features-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 20px;
    }
    .feature-item {
      display: flex;
      flex-direction: row;
      align-items: center;
      gap: 15px;
      padding: 15px;
      background: #f9fdfa;
      border-radius: 8px;
      border: 1px solid #f0f0f0;
      transition: transform 0.2s;
    }
    .feature-item:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .feature-icon-box {
      width: 40px;
      height: 40px;
      background: #eaffee;
      color: #2e8b57;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }
    .feature-text h5 {
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 3px;
      text-transform: uppercase;
      color: #1a1a1a;
      letter-spacing: 0.5px;
    }
    .feature-text p {
      font-size: 11px;
      color: #777;
      line-height: 1.3;
      margin: 0;
    }
    
    /* Responsive Subscribe & Features */
    @media (max-width: 992px) {
      .features-container {
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
      }
    }
    /* Our Story Grid (Desktop) */
    .our-story-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    @media (max-width: 768px) {
       /* Our Story Mobile Scroll */
        .our-story-grid {
          display: flex !important; /* Switch to flex scroll */
          flex-wrap: nowrap !important;
          overflow-x: auto;
          gap: 20px;
          padding-bottom: 20px; /* Space for scrollbar if any */
          scrollbar-width: none;
        }
        .our-story-grid::-webkit-scrollbar {
          display: none;
        }
        .our-story-grid > div {
           flex: 0 0 280px; /* Fixed width card */
           min-width: 280px;
        }
        
       /* Features Mobile Single Row */
       .features-container {
         display: flex !important;
         flex-wrap: nowrap !important;
         grid-template-columns: none !important;
         justify-content: space-evenly;
         gap: 6px;
         padding: 0 4px;
       }
       .feature-item {
         flex: 1;
         min-width: 0;
         flex-direction: column !important; /* Stack Icon & Title */
         justify-content: flex-start !important;
         text-align: center;
         padding: 10px 4px !important;
         gap: 6px !important;
       }
       .feature-icon-box {
           width: 35px !important;
           height: 35px !important;
           font-size: 14px !important;
           margin: 0 auto;
       }
       .feature-text h5 {
           font-size: 9px !important;
           line-height: 1.2;
           margin: 0;
           word-wrap: break-word; /* Ensure wrapping */
           white-space: normal;
       }
       .feature-text p {
           display: none !important; /* Hide desc on mobile to fit */
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

    .btn-add-cart-mini {
      width: 100%;
      margin-top: 10px; /* Space above button */
      background: #A41B42; /* fallback */
      background: linear-gradient(135deg, var(--berry, #A41B42) 0%, var(--berry-dark, #801230) 100%);
      color: #fff !important;
      border: none;
      padding: 10px 0;
      border-radius: 30px; /* Pill shape */
      font-size: 13px;
      font-weight: 700; 
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: all 0.3s ease;
      position: relative;
      z-index: 2; /* Ensure clickable above card link if any issue */
      box-shadow: 0 4px 10px rgba(164,27,66,0.2); /* Match product page shadow */
      text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    .btn-add-cart-mini:hover {
      background: linear-gradient(135deg, var(--berry-dark, #801230) 0%, var(--berry, #A41B42) 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(164,27,66,0.3);
      color: #fff !important; /* Force white on hover */
    }
    .btn-add-cart-mini:active {
      transform: translateY(0);
      color: #fff !important; /* Force white on click/drag */
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

    /* See More Button Style */
    .section-see-more {
      text-align: center;
      margin-top: 25px;
      margin-bottom: 10px;
    }
    .btn-see-more {
      display: inline-flex;
      align-items: center;
      padding: 10px 28px;
      background-color: #fff;
      color: var(--primary-dark);
      border: 1px solid var(--primary);
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      transition: all 0.3s ease;
    }
    .btn-see-more:hover {
      background-color: var(--primary);
      color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(212, 175, 55, 0.25);
    }

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
        display:inline-block; /* Fix "too width" visual by fitting content */
        width: auto;
        background:#D4AF37;
        color:#fff;
        padding:4px 8px;
        font-size:10px;
        font-weight:600;
        text-transform:uppercase;
        border-radius: 4px; /* Rounded badge look */
        margin-bottom:8px;
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

          <!-- WEEKLY SPECIALS CARD -->
          <?php if (!empty($weeklySpecials)): ?>
          <div class="card weekly-specials-card">
            <div class="card-header">
              <div class="label">
                <i class="fa-solid fa-bolt"></i>
                <span>⚡ Weekly Specials</span>
              </div>
            </div>
            <div class="card-body">
              <?php foreach ($weeklySpecials as $ws): 
                $wsImg = htmlspecialchars(get_first_image($ws['images'] ?? ''));
                $wsDiscount = round((($ws['compare_price'] - $ws['price']) / $ws['compare_price']) * 100);
              ?>
                <a href="product_view.php?id=<?= (int)$ws['id'] ?>" class="special-product" style="text-decoration:none; color:inherit; display:flex; align-items:center; padding:10px 0; border-bottom:1px solid #f0f0f0;">
                  <div class="special-img-wrap" style="position:relative; flex-shrink:0; margin-right:12px;">
                    <img src="<?= $wsImg ?>" alt="Product" style="width:50px; height:50px; object-fit:cover; border-radius:50%; border:2px solid #f0f0f0;">
                    <span class="discount-badge" style="position:absolute; top:-5px; right:-5px; background:#ff4757; color:#fff; font-size:9px; font-weight:700; padding:2px 5px; border-radius:10px; box-shadow:0 2px 4px rgba(0,0,0,0.2);">-<?= $wsDiscount ?>%</span>
                  </div>
                  <div class="special-info" style="flex:1; min-width:0;">
                    <div class="special-name" style="font-size:13px; font-weight:600; color:#333; margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                      <?= htmlspecialchars($ws['name']) ?>
                    </div>
                    <div class="special-prices" style="display:flex; align-items:center; gap:8px;">
                      <span class="special-price" style="font-size:14px; font-weight:700; color:#2ECC71;">
                        ₹<?= number_format((float)$ws['price'], 2) ?>
                      </span>
                      <span class="special-old-price" style="font-size:11px; color:#999; text-decoration:line-through;">
                        ₹<?= number_format((float)$ws['compare_price'], 2) ?>
                      </span>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
              
              <a href="product.php" style="display:block; text-align:center; padding:12px; margin-top:8px; font-size:12px; font-weight:600; color:#2ECC71; text-decoration:none; border-top:1px solid #f0f0f0;">
                View All Deals →
              </a>
            </div>
          </div>
          <?php endif; ?>

          <!-- TOP RATED PRODUCTS CARD -->
          <div class="card">
            <div class="card-header">
              <div class="label">
                <i class="fa-solid fa-star"></i>
                <span>⭐ Top Rated</span>
              </div>
            </div>
            <div class="card-body">
              <?php if (!empty($tabTop)): ?>
                <?php foreach (array_slice($tabTop, 0, 2) as $tr): 
                  $trImg = htmlspecialchars(get_first_image($tr['images'] ?? ''));
                ?>
                  <a href="product_view.php?id=<?= (int)$tr['id'] ?>" class="mini-product" style="text-decoration:none; color:inherit; display:flex;">
                    <img src="<?= $trImg ?>" alt="Product" style="width:60px; height:60px; object-fit:cover; border-radius:8px; margin-right:12px;">
                    <div class="mini-info">
                      <div class="mini-name" style="font-size:13px; font-weight:600; margin-bottom:3px;">
                        <?= htmlspecialchars($tr['name']) ?>
                      </div>
                      <div class="product-stars" style="color:#FFB400; font-size:12px; margin-bottom:3px;">★★★★★</div>
                      <div class="mini-price" style="font-size:14px; font-weight:700; color:#2ECC71;">
                        ₹<?= number_format((float)$tr['price'], 2) ?>
                      </div>
                    </div>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <div style="padding:10px 16px; font-size:12px; color:#777;">
                  No top rated products yet.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- QUICK LINKS CARD -->
          <div class="card">
            <div class="card-header">
              <div class="label">
                <i class="fa-solid fa-circle-info"></i>
                <span>Quick Links</span>
              </div>
            </div>
            <div class="card-body" style="padding:12px 0;">
              <?php if (!empty($quickLinksPages)): ?>
                <ul style="list-style:none; margin:0; padding:0;">
                  <?php foreach ($quickLinksPages as $idx => $qlPage): 
                    $isLast = ($idx === count($quickLinksPages) - 1);
                    // Icon mapping - you can expand this
                    $iconMap = [
                      'contact' => 'fa-headset',
                      'support' => 'fa-headset',
                      'shipping' => 'fa-truck',
                      'returns' => 'fa-rotate-left',
                      'refund' => 'fa-rotate-left',
                      'faq' => 'fa-circle-question',
                      'about' => 'fa-circle-info',
                      'privacy' => 'fa-shield-halved',
                      'terms' => 'fa-file-contract',
                    ];
                    
                    // Try to match icon based on slug keywords
                    $icon = 'fa-circle-right'; // default
                    foreach ($iconMap as $keyword => $iconClass) {
                      if (stripos($qlPage['slug'], $keyword) !== false || stripos($qlPage['title'], $keyword) !== false) {
                        $icon = $iconClass;
                        break;
                      }
                    }
                  ?>
                    <li style="<?= !$isLast ? 'border-bottom:1px solid #f0f0f0;' : '' ?>">
                      <a href="page.php?slug=<?= urlencode($qlPage['slug']) ?>" style="display:flex; align-items:center; padding:10px 16px; font-size:13px; color:#555; text-decoration:none; transition:all 0.2s;">
                        <i class="fa-solid <?= $icon ?>" style="margin-right:10px; font-size:14px; color:#2ECC71;"></i>
                        <?= htmlspecialchars($qlPage['title']) ?>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <div style="padding:10px 16px; font-size:12px; color:#777; text-align:center;">
                  No quick links configured. Set them in <a href="/admin/appearance_homepage.php" style="color:#2ECC71;">Admin Panel</a>.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- NEWSLETTER CARD -->
          <div class="card newsletter-card">
            <div class="card-header">
              <div class="label">
                <i class="fa-solid fa-envelope"></i>
                <span>Newsletter</span>
              </div>
            </div>
            <div class="card-body" style="padding:20px;">
              <div style="text-align:center; margin-bottom:16px;">
                <div style="width:50px; height:50px; background:linear-gradient(135deg, #667eea, #764ba2); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 12px;">
                  <i class="fa-solid fa-envelope-open" style="color:#fff; font-size:22px;"></i>
                </div>
                <h4 style="font-size:14px; font-weight:700; color:#333; margin:0 0 6px;">Stay Updated!</h4>
                <p style="font-size:11px; color:#777; line-height:1.4; margin:0;">
                  Get exclusive deals, tips & updates delivered to your inbox
                </p>
              </div>
              
              <form onsubmit="event.preventDefault(); alert('Thanks for subscribing!');" style="display:flex; flex-direction:column; gap:10px;">
                <div style="position:relative;">
                  <i class="fa-regular fa-envelope" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#999; font-size:14px;"></i>
                  <input 
                    type="email" 
                    placeholder="Enter your email" 
                    required 
                    style="width:100%; padding:11px 12px 11px 38px; border:1.5px solid #e0e0e0; border-radius:8px; font-size:12px; outline:none; transition:all 0.2s; box-sizing:border-box;"
                    onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)'"
                    onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'"
                  >
                </div>
                <button type="submit" style="background:linear-gradient(135deg, #667eea, #764ba2); color:#fff; border:none; padding:11px 20px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; transition:all 0.3s; box-shadow:0 4px 12px rgba(102,126,234,0.3);">
                  <i class="fa-solid fa-paper-plane" style="margin-right:6px;"></i>
                  Subscribe Now
                </button>
              </form>
              
              <div style="margin-top:12px; padding-top:12px; border-top:1px solid #f0f0f0; display:flex; align-items:center; justify-content:center; gap:4px;">
                <i class="fa-solid fa-shield-halved" style="color:#2ECC71; font-size:10px;"></i>
                <p style="font-size:9px; color:#999; margin:0; line-height:1.3;">
                  We protect your privacy. Unsubscribe anytime.
                </p>
              </div>
            </div>
          </div>

          <!-- NEW ARRIVALS CARD -->
          <div class="card">
            <div class="card-header">
              <div class="label">
                <i class="fa-solid fa-sparkles"></i>
                <span>🆕 New Arrivals</span>
              </div>
            </div>
            <div class="card-body">
              <?php if (!empty($newProducts)): ?>
                <?php foreach (array_slice($newProducts, 0, 2) as $np): 
                  $npImg = htmlspecialchars(get_first_image($np['images'] ?? ''));
                ?>
                  <a href="product_view.php?id=<?= (int)$np['id'] ?>" class="mini-product" style="text-decoration:none; color:inherit; display:flex;">
                    <div style="position:relative; flex-shrink:0; margin-right:12px;">
                      <img src="<?= $npImg ?>" alt="Product" style="width:60px; height:60px; object-fit:cover; border-radius:8px;">
                      <span style="position:absolute; top:-5px; left:-5px; background:#FF6B6B; color:#fff; font-size:8px; font-weight:700; padding:3px 6px; border-radius:10px; box-shadow:0 2px 4px rgba(0,0,0,0.2);">NEW</span>
                    </div>
                    <div class="mini-info">
                      <div class="mini-name" style="font-size:13px; font-weight:600; margin-bottom:3px;">
                        <?= htmlspecialchars($np['name']) ?>
                      </div>
                      <div class="mini-price" style="font-size:14px; font-weight:700; color:#2ECC71;">
                        ₹<?= number_format((float)$np['price'], 2) ?>
                      </div>
                    </div>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <div style="padding:10px 16px; font-size:12px; color:#777;">
                  No new products yet.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- TRUST BADGES CARD -->
          <div class="card trust-badges-card">
            <div class="card-header">
              <div class="label">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Why Shop With Us</span>
              </div>
            </div>
            <div class="card-body" style="padding:16px;">
              <div style="display:flex; flex-direction:column; gap:12px;">
                <div style="display:flex; align-items:center; gap:12px;">
                  <div style="width:40px; height:40px; background:#E8F5E9; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fa-solid fa-lock" style="color:#2ECC71; font-size:18px;"></i>
                  </div>
                  <div>
                    <div style="font-size:12px; font-weight:600; color:#333; margin-bottom:2px;">Secure Payment</div>
                    <div style="font-size:10px; color:#777;">SSL encrypted checkout</div>
                  </div>
                </div>
                
                <div style="display:flex; align-items:center; gap:12px;">
                  <div style="width:40px; height:40px; background:#E3F2FD; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fa-solid fa-truck-fast" style="color:#2196F3; font-size:18px;"></i>
                  </div>
                  <div>
                    <div style="font-size:12px; font-weight:600; color:#333; margin-bottom:2px;">Free Shipping</div>
                    <div style="font-size:10px; color:#777;">On orders over ₹500</div>
                  </div>
                </div>
                
                <div style="display:flex; align-items:center; gap:12px;">
                  <div style="width:40px; height:40px; background:#FFF3E0; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fa-solid fa-circle-check" style="color:#FF9800; font-size:18px;"></i>
                  </div>
                  <div>
                    <div style="font-size:12px; font-weight:600; color:#333; margin-bottom:2px;">Money Back</div>
                    <div style="font-size:10px; color:#777;">30 day guarantee</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- LATEST BLOG POSTS CARD -->
          <?php if (!empty($latestBlogs)): ?>
          <div class="card">
            <div class="card-header">
              <div class="label">
                <i class="fa-solid fa-newspaper"></i>
                <span>📰 Latest Articles</span>
              </div>
            </div>
            <div class="card-body">
              <?php foreach (array_slice($latestBlogs, 0, 2) as $blog): 
                $blogImg = !empty($blog['featured_image']) ? htmlspecialchars($blog['featured_image']) : 'assets/images/category-placeholder.jpg';
              ?>
                <a href="blog-detail.php?slug=<?= urlencode($blog['slug']) ?>" class="blog-mini" style="text-decoration:none; color:inherit; display:flex; padding:10px 0; border-bottom:1px solid #f0f0f0; transition:all 0.2s;">
                  <img src="<?= $blogImg ?>" alt="Blog" style="width:60px; height:60px; object-fit:cover; border-radius:8px; margin-right:12px; flex-shrink:0;">
                  <div style="flex:1; min-width:0;">
                    <div style="font-size:12px; font-weight:600; color:#333; margin-bottom:4px; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                      <?= htmlspecialchars($blog['title']) ?>
                    </div>
                    <div style="font-size:10px; color:#999;">
                      <i class="fa-regular fa-calendar" style="margin-right:4px;"></i>
                      <?= date('M d, Y', strtotime($blog['created_at'])) ?>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
              <a href="blogs.php" style="display:block; text-align:center; padding:12px; margin-top:8px; font-size:12px; font-weight:600; color:#2ECC71; text-decoration:none;">
                Read All Articles →
              </a>
            </div>
          </div>
          <?php endif; ?>

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
            <!-- Top banners -->
            <style>
              .banner-row {
                 gap: 20px !important; /* Increase image-to-image space */
              }
              .banner-row-container {
                 margin-bottom: 40px !important; /* Increase space to next section */
                 padding: 10px 0;
              }
              .banner-item {
                 /* Optional: Add shadow/border to pop */
                 /* border: 1px solid #eee; */
              }
            </style>
            <div class="banner-row-container">
              <div class="banner-row">
                <?php 
                if (!empty($bannerGroups)): 
                  // Duplicate for smooth marquee effect
                  $loopGroups = array_merge($bannerGroups, $bannerGroups, $bannerGroups);
                  foreach ($loopGroups as $bg): 
                    $bgImg = !empty($bg['image']) ? $bg['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path
                    if (strpos($bgImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($bgImg, '/') === 0) {
                        // is absolute
                    } else {
                        $bgImg = '/assets/uploads/groups/' . $bgImg;
                    }
                    
                    $url = 'product.php?group_id=' . (int)$bg['id'];
                ?>
                  <a class="banner-item" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="<?php echo htmlspecialchars($bgImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($bg['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="banner-caption">
                      <h4><?php echo htmlspecialchars($bg['name']); ?></h4>
                    </div>
                  </a>
                <?php endforeach; ?>
                <?php else: ?>
                   <p class="text-center p-4 text-gray-400">No collections found</p>
                <?php endif; ?>
              </div>
            </div>

            <!-- TOP LEVEL CATEGORIES GRID (VISUAL) -->
            <?php if (!empty($categories)): ?>
            <div class="top-categories-section">
              <div class="section-header">
                <h3>Shop by Category</h3>
              </div>
              
              <!-- AUTO SCROLL CONTAINER (3 Items Visible) -->
              <style>
                #categoryAutoScroll::-webkit-scrollbar {
                  display: none;
                }
                #categoryAutoScroll {
                  -ms-overflow-style: none;
                  scrollbar-width: none;
                }
                .cat-slide-item {
                  /* Default Desktop: Gap 25px, 3 items */
                  flex: 0 0 calc(33.333% - 17px);
                }
                @media (max-width: 768px) {
                  #categoryAutoScroll {
                    gap: 20px !important; /* Increased mobile gap */
                  }
                  .cat-slide-item {
                    /* Mobile: Show 2 Items. 
                       Formula: (100% - 20px) / 2 = 50% - 10px */
                    flex: 0 0 calc(50% - 10px) !important;
                  }
                  .cat-slide-item h4 {
                    font-size: 13px !important; /* Slightly larger text for bigger images */
                  }
                }
              </style>
              
              <div id="categoryAutoScroll" style="display: flex; gap: 25px; overflow-x: auto; white-space: nowrap; padding: 10px 0; -webkit-overflow-scrolling: touch; cursor: pointer;">
                <?php 
                $catDebug = [];
                // Duplicate categories to ensure scrolling if few items
                $displayCats = $categories;
                if (count($displayCats) < 6) {
                    $displayCats = array_merge($displayCats, $categories); // duplicate once
                }

                foreach ($displayCats as $cat): 
                    // Use 'image' column directly
                    $rawImg = !empty($cat['image']) ? $cat['image'] : ($cat['img'] ?? '');
                    $catImg = trim($rawImg);
                    
                    if ($catImg === '') {
                        $catImg = '/assets/images/category-placeholder.jpg';
                    } else {
                        // Parse Image Path
                        if (strpos($catImg, 'http') === 0) {
                        } elseif (strpos($catImg, '/') === 0) {
                        } elseif (strpos($catImg, 'assets/') === 0) {
                            $catImg = '/' . $catImg;
                        } else {
                            $catImg = '/assets/uploads/categories/' . $catImg;
                        }
                    }
                ?>
                  <a href="product.php?category[]=<?php echo urlencode($cat['title']); ?>" class="cat-slide-item" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <!-- Full Width Image Container (Square) -->
                    <div style="width: 100%; aspect-ratio: 1/1; border-radius: 16px; overflow: hidden; border: 2px solid #eee; box-shadow: 0 6px 15px rgba(0,0,0,0.08); background: #fff; position: relative;">
                      <img src="<?php echo htmlspecialchars($catImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($cat['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #333; line-height: 1.3; max-width: 100%; text-align:center; white-space: normal;">
                      <?php echo htmlspecialchars($cat['title']); ?>
                    </h4>
                  </a>
                <?php endforeach; ?>
              </div>

              <!-- AUTO SCROLL SCRIPT -->
              <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const scrollContainer = document.getElementById("categoryAutoScroll");
                    let scrollSpeed = 1.0; // Slightly faster to handle sub-pixel issues
                    let isHovered = false;

                    function autoScroll() {
                        if (!isHovered && scrollContainer) {
                            // Increment scroll
                            scrollContainer.scrollLeft += scrollSpeed;
                            
                            // Check if reached end
                            // Tolerance of 1px
                            if (scrollContainer.scrollLeft >= (scrollContainer.scrollWidth - scrollContainer.clientWidth - 1)) {
                                scrollContainer.scrollLeft = 0; // Reset to start
                            }
                        }
                        requestAnimationFrame(autoScroll);
                    }
                    
                    if(scrollContainer) {
                        // Remove potential CSS conflict
                        scrollContainer.style.scrollBehavior = 'auto'; 
                        
                        // Pause on hover
                        scrollContainer.addEventListener("mouseenter", () => isHovered = true);
                        scrollContainer.addEventListener("mouseleave", () => isHovered = false);
                        
                        // Start loop
                        requestAnimationFrame(autoScroll);
                    }
                });
              </script>

            </div>
            <?php endif; ?>

            <!-- New Herbal Products (latest products from DB) -->
            <div class="section-header">
              <h3>New Herbal Products</h3>
              <a href="product.php?section=new_herbal&sort=latest" class="btn-see-more">
                See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

            <div class="product-grid">
              <?php if (!empty($newProducts)): ?>
                <?php foreach ($newProducts as $p): ?>
                  <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                  <div class="product-card" style="position:relative;">
                    <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                    <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                      <i class="fa-solid fa-cart-plus"></i> Add
                    </button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="font-size:12px; color:#777;">No products available yet.</p>
              <?php endif; ?>
            </div>
            


            <!-- New Herbal Products (latest products from DB) -->
            <div class="section-header">
              <h3>New Herbal Products</h3>
              <a href="product.php?section=new_herbal&sort=latest" class="btn-see-more">
                See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

            <div class="product-grid">
              <?php if (!empty($newProducts)): ?>
                <?php foreach ($newProducts as $p): ?>
                  <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                  <div class="product-card" style="position:relative;">
                    <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                    <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                      <i class="fa-solid fa-cart-plus"></i> Add
                    </button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="font-size:12px; color:#777;">No products available yet.</p>
              <?php endif; ?>
            </div>
            


            <!-- SHOP BY CONCERN -->
            <?php if (!empty($concerns)): ?>
            <div class="top-concerns-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Concern</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #concernAutoScroll::-webkit-scrollbar { display: none; }
                #concernAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="concernAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($concerns as $c): 
                    $cImg = !empty($c['image']) ? $c['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($cImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($cImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($cImg, 'assets/') === 0) {
                        $cImg = '/' . $cImg;
                    } else {
                        $cImg = '/assets/uploads/concerns/' . $cImg;
                    }
                ?>
                  <a href="product.php?concern=<?php echo urlencode($c['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($cImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($c['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>


            <!-- SHOP BY CONCERN -->
            <?php if (!empty($concerns)): ?>
            <div class="top-concerns-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Concern</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #concernAutoScroll::-webkit-scrollbar { display: none; }
                #concernAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="concernAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($concerns as $c): 
                    $cImg = !empty($c['image']) ? $c['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($cImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($cImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($cImg, 'assets/') === 0) {
                        $cImg = '/' . $cImg;
                    } else {
                        $cImg = '/assets/uploads/concerns/' . $cImg;
                    }
                ?>
                  <a href="product.php?concern=<?php echo urlencode($c['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($cImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($c['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>


            <!-- Wide banner (NOW CAROUSEL) -->
            <?php if (!empty($homeCenterBanners)): ?>
            <div class="wide-banner">
                <div class="wide-slider" style="position:relative; width:100%; height:100%; overflow:hidden;">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeCenterBanners as $centerBanner): ?>
                      <?php
                        $rawFn = trim($centerBanner['filename'] ?? '');
                        if (empty($rawFn)) continue; // skip empty
                        
                        $centerSrc  = '/assets/uploads/banners/' . ltrim($rawFn, '/');
                        $centerAlt  = $centerBanner['alt_text'] ?? '';
                        $centerLink = trim($centerBanner['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($centerLink): ?>
                          <a href="<?php echo htmlspecialchars($centerLink, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($centerSrc, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($centerAlt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($centerLink): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeCenterBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeCenterBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <!-- Arrows (optional but good for UX) -->
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const track = document.querySelector('.wide-slider-track');
                    const slides = document.querySelectorAll('.wide-slide');
                    const dots = document.querySelectorAll('.wide-dot');
                    const prevBtn = document.querySelector('.wide-prev');
                    const nextBtn = document.querySelector('.wide-next');
                    
                    if (!track) return;
                    if (slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      
                      // Update dots
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() {
                      currentIndex = (currentIndex + 1) % slides.length;
                      updateSlider();
                    }
                    
                    function prevSlide() {
                      currentIndex = (currentIndex - 1 + slides.length) % slides.length;
                      updateSlider();
                    }
                    
                    function startAutoSlide() {
                      stopAutoSlide();
                      autoSlideInterval = setInterval(nextSlide, 3000);
                    }
                    
                    function stopAutoSlide() {
                      if (autoSlideInterval) clearInterval(autoSlideInterval);
                    }
                    
                    // Event Listeners
                    if (nextBtn) nextBtn.addEventListener('click', () => {
                      nextSlide();
                      startAutoSlide();
                    });
                    
                    if (prevBtn) prevBtn.addEventListener('click', () => {
                      prevSlide();
                      startAutoSlide();
                    });
                    
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => {
                        currentIndex = idx;
                        updateSlider();
                        startAutoSlide();
                      });
                    });
                    
                    // Start
                    startAutoSlide();
                    
                    // Initial dot style
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

            <!-- Best Sellers (from homepage_products -> best_seller) -->
            <div style="margin-top:30px;">
            <div class="section-header">
              <h3>Best Sellers</h3>
              <a href="product.php?section=best_seller&sort=popularity" class="btn-see-more">
                 See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

              <div class="product-grid">
                <?php if (!empty($bestProducts)): ?>
                  <?php foreach ($bestProducts as $p): ?>
                    <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                    <div class="product-card" style="position:relative;">
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                      <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                        <i class="fa-solid fa-cart-plus"></i> Add
                      </button>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:12px; color:#777;">No best sellers available yet.</p>
                <?php endif; ?>
              </div>
            </div>
              



            <!-- SHOP BY SEASONAL -->
            <?php if (!empty($seasonals)): ?>
            <div class="top-seasonals-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Seasonal</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #seasonalAutoScroll::-webkit-scrollbar { display: none; }
                #seasonalAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="seasonalAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($seasonals as $s): 
                    $sImg = !empty($s['image']) ? $s['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($sImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($sImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($sImg, 'assets/') === 0) {
                        $sImg = '/' . $sImg;
                    } else {
                        $sImg = '/assets/uploads/seasonals/' . $sImg;
                    }
                ?>
                  <a href="product.php?seasonal=<?php echo urlencode($s['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($sImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($s['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- HOMEPAGE VIDEO/MEDIA SECTION -->
            <?php
            // Settings fetched at top of file
            
            // Only show if URL is present
            if (!empty($hvUrl)): 
            ?>
            <div class="home-video-section">
                <div>
                    <!-- Left: Media -->
                    <div>
                         <?php 
                            $ext = strtolower(pathinfo($hvUrl, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
                            
                            if (strpos($hvUrl, 'youtube') !== false || strpos($hvUrl, 'youtu.be') !== false): 
                             // YouTube
                             $embedUrl = $hvUrl;
                             if (strpos($hvUrl, 'watch?v=') !== false) {
                                 $embedUrl = str_replace('watch?v=', 'embed/', $hvUrl);
                                 $embedUrl = explode('&', $embedUrl)[0];
                             } elseif (strpos($hvUrl, 'youtu.be/') !== false) {
                                 $embedUrl = str_replace('youtu.be/', 'www.youtube.com/embed/', $hvUrl);
                             }
                         ?>
                            <iframe src="<?= htmlspecialchars($embedUrl) ?>" allowfullscreen></iframe>
                         
                         <?php elseif ($isImage): ?>
                            <!-- Image -->
                            <img src="<?= htmlspecialchars($hvUrl) ?>" alt="Section Media">
                         
                         <?php else: ?>
                            <!-- HTML5 Video / Default -->
                            <video src="<?= htmlspecialchars($hvUrl) ?>" controls></video>
                         <?php endif; ?>
                    </div>
                    
                    <!-- Right: Text -->
                    <div>
                        <?php if($hvTitle): ?>
                            <h3 style="font-size: 28px; font-weight: 800; margin-bottom: 20px; color: #1a1a1a; font-family: 'Poppins', sans-serif;"><?= htmlspecialchars($hvTitle) ?></h3>
                        <?php endif; ?>
                        
                        <?php if($hvDesc): ?>
                            <div style="font-size: 16px; line-height: 1.8; color: #555; margin-bottom: 30px;" class="prose max-w-none">
                                <?= html_entity_decode($hvDesc) // Allow HTML from CKEditor ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($hvBtnText && $hvBtnLink): ?>
                            <div>
                                <a href="<?= htmlspecialchars($hvBtnLink) ?>" style="display: inline-block; background: <?= htmlspecialchars($hvBtnColor) ?>; color: #fff; padding: 14px 35px; border-radius: 999px; font-weight: 600; text-transform: uppercase; font-size: 14px; letter-spacing: 0.5px; transition: opacity 0.2s; text-decoration: none;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                    <?= htmlspecialchars($hvBtnText) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- WIDE BANNER (BEFORE BLOGS) - CAROUSEL -->
            <?php if (!empty($homeBeforeBlogsBanners)): ?>
            <div class="wide-banner" style="margin-top: 60px; margin-bottom: 40px;">
                <div class="wide-slider" id="blogs-slider" style="position:relative; width:100%; height:100%; overflow:hidden; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeBeforeBlogsBanners as $b): ?>
                      <?php
                        $src  = '/assets/uploads/banners/' . ltrim($b['filename'], '/');
                        $alt  = $b['alt_text'] ?? '';
                        $link = trim($b['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($link): ?>
                          <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($link): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeBeforeBlogsBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeBeforeBlogsBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <!-- Re-use the slider script logic or attach new one -->
                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const sliderId = '#blogs-slider';
                    const container = document.querySelector(sliderId);
                    if(!container) return;

                    const track = container.querySelector('.wide-slider-track');
                    const slides = container.querySelectorAll('.wide-slide');
                    const dots = container.querySelectorAll('.wide-dot');
                    const prevBtn = container.querySelector('.wide-prev');
                    const nextBtn = container.querySelector('.wide-next');
                    
                    if (!track || slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() { currentIndex = (currentIndex + 1) % slides.length; updateSlider(); }
                    function prevSlide() { currentIndex = (currentIndex - 1 + slides.length) % slides.length; updateSlider(); }
                    function startAutoSlide() { stopAutoSlide(); autoSlideInterval = setInterval(nextSlide, 3500); }
                    function stopAutoSlide() { if (autoSlideInterval) clearInterval(autoSlideInterval); }
                    
                    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); startAutoSlide(); });
                    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); startAutoSlide(); });
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => { currentIndex = idx; updateSlider(); startAutoSlide(); });
                    });
                    
                    startAutoSlide();
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

            <!-- MULTI COLUMN TABBED PRODUCTS -->
            <?php if ($visibleTabs > 0): ?>
            <div class="tabbed-products-section">
              <div class="tabs-bar">
                <?php if ($showLatest == '1'): ?><div class="tab-ribbon">Latest Products</div><?php endif; ?>
                <?php if ($showTrendy == '1'): ?><div class="tab-ribbon">Trendy Products</div><?php endif; ?>
                <?php if ($showSale == '1'): ?><div class="tab-ribbon">Sale Products</div><?php endif; ?>
                <?php if ($showTop == '1'): ?><div class="tab-ribbon">Top Rated</div><?php endif; ?>
              </div>

              <div class="tabbed-columns">
                <!-- Latest -->
                <?php if ($showLatest == '1'): ?>
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
                <?php endif; ?>

                <!-- Trendy -->
                <?php if ($showTrendy == '1'): ?>
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
                <?php endif; ?>

                <!-- Sale -->
                <?php if ($showSale == '1'): ?>
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
                <?php endif; ?>

                <!-- Top rated -->
                <?php if ($showTop == '1'): ?>
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
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

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

            </section>

          <!-- ALL OTHER CONTENT -->
          <section class="content-grid">
            <!-- Top banners -->
            <!-- Top banners -->
            <style>
              .banner-row {
                 gap: 20px !important; /* Increase image-to-image space */
              }
              .banner-row-container {
                 margin-bottom: 40px !important; /* Increase space to next section */
                 padding: 10px 0;
              }
              .banner-item {
                 /* Optional: Add shadow/border to pop */
                 /* border: 1px solid #eee; */
              }
            </style>
            <div class="banner-row-container">
              <div class="banner-row">
                <?php 
                if (!empty($bannerGroups)): 
                  // Duplicate for smooth marquee effect
                  $loopGroups = array_merge($bannerGroups, $bannerGroups, $bannerGroups);
                  foreach ($loopGroups as $bg): 
                    $bgImg = !empty($bg['image']) ? $bg['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path
                    if (strpos($bgImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($bgImg, '/') === 0) {
                        // is absolute
                    } else {
                        $bgImg = '/assets/uploads/groups/' . $bgImg;
                    }
                    
                    $url = 'product.php?group_id=' . (int)$bg['id'];
                ?>
                  <a class="banner-item" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="<?php echo htmlspecialchars($bgImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($bg['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="banner-caption">
                      <h4><?php echo htmlspecialchars($bg['name']); ?></h4>
                    </div>
                  </a>
                <?php endforeach; ?>
                <?php else: ?>
                   <p class="text-center p-4 text-gray-400">No collections found</p>
                <?php endif; ?>
              </div>
            </div>

            <!-- TOP LEVEL CATEGORIES GRID (VISUAL) -->
            <?php if (!empty($categories)): ?>
            <div class="top-categories-section">
              <div class="section-header">
                <h3>Shop by Category</h3>
              </div>
              
              <!-- AUTO SCROLL CONTAINER (3 Items Visible) -->
              <style>
                #categoryAutoScroll::-webkit-scrollbar {
                  display: none;
                }
                #categoryAutoScroll {
                  -ms-overflow-style: none;
                  scrollbar-width: none;
                }
                .cat-slide-item {
                  /* Default Desktop: Gap 25px, 3 items */
                  flex: 0 0 calc(33.333% - 17px);
                }
                @media (max-width: 768px) {
                  #categoryAutoScroll {
                    gap: 20px !important; /* Increased mobile gap */
                  }
                  .cat-slide-item {
                    /* Mobile: Show 2 Items. 
                       Formula: (100% - 20px) / 2 = 50% - 10px */
                    flex: 0 0 calc(50% - 10px) !important;
                  }
                  .cat-slide-item h4 {
                    font-size: 13px !important; /* Slightly larger text for bigger images */
                  }
                }
              </style>
              
              <div id="categoryAutoScroll" style="display: flex; gap: 25px; overflow-x: auto; white-space: nowrap; padding: 10px 0; -webkit-overflow-scrolling: touch; cursor: pointer;">
                <?php 
                $catDebug = [];
                // Duplicate categories to ensure scrolling if few items
                $displayCats = $categories;
                if (count($displayCats) < 6) {
                    $displayCats = array_merge($displayCats, $categories); // duplicate once
                }

                foreach ($displayCats as $cat): 
                    // Use 'image' column directly
                    $rawImg = !empty($cat['image']) ? $cat['image'] : ($cat['img'] ?? '');
                    $catImg = trim($rawImg);
                    
                    if ($catImg === '') {
                        $catImg = '/assets/images/category-placeholder.jpg';
                    } else {
                        // Parse Image Path
                        if (strpos($catImg, 'http') === 0) {
                        } elseif (strpos($catImg, '/') === 0) {
                        } elseif (strpos($catImg, 'assets/') === 0) {
                            $catImg = '/' . $catImg;
                        } else {
                            $catImg = '/assets/uploads/categories/' . $catImg;
                        }
                    }
                ?>
                  <a href="product.php?category[]=<?php echo urlencode($cat['title']); ?>" class="cat-slide-item" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <!-- Full Width Image Container (Square) -->
                    <div style="width: 100%; aspect-ratio: 1/1; border-radius: 16px; overflow: hidden; border: 2px solid #eee; box-shadow: 0 6px 15px rgba(0,0,0,0.08); background: #fff; position: relative;">
                      <img src="<?php echo htmlspecialchars($catImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($cat['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #333; line-height: 1.3; max-width: 100%; text-align:center; white-space: normal;">
                      <?php echo htmlspecialchars($cat['title']); ?>
                    </h4>
                  </a>
                <?php endforeach; ?>
              </div>

              <!-- AUTO SCROLL SCRIPT -->
              <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const scrollContainer = document.getElementById("categoryAutoScroll");
                    let scrollSpeed = 1.0; // Slightly faster to handle sub-pixel issues
                    let isHovered = false;

                    function autoScroll() {
                        if (!isHovered && scrollContainer) {
                            // Increment scroll
                            scrollContainer.scrollLeft += scrollSpeed;
                            
                            // Check if reached end
                            // Tolerance of 1px
                            if (scrollContainer.scrollLeft >= (scrollContainer.scrollWidth - scrollContainer.clientWidth - 1)) {
                                scrollContainer.scrollLeft = 0; // Reset to start
                            }
                        }
                        requestAnimationFrame(autoScroll);
                    }
                    
                    if(scrollContainer) {
                        // Remove potential CSS conflict
                        scrollContainer.style.scrollBehavior = 'auto'; 
                        
                        // Pause on hover
                        scrollContainer.addEventListener("mouseenter", () => isHovered = true);
                        scrollContainer.addEventListener("mouseleave", () => isHovered = false);
                        
                        // Start loop
                        requestAnimationFrame(autoScroll);
                    }
                });
              </script>

            </div>
            <?php endif; ?>

            <!-- SHOP BY CONCERN -->
            <?php if (!empty($concerns)): ?>
            <div class="top-concerns-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Concern</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #concernAutoScroll::-webkit-scrollbar { display: none; }
                #concernAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="concernAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($concerns as $c): 
                    $cImg = !empty($c['image']) ? $c['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($cImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($cImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($cImg, 'assets/') === 0) {
                        $cImg = '/' . $cImg;
                    } else {
                        $cImg = '/assets/uploads/concerns/' . $cImg;
                    }
                ?>
                  <a href="product.php?concern=<?php echo urlencode($c['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($cImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($c['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>


            <!-- New Herbal Products (latest products from DB) -->
            <div class="section-header">
              <h3>New Herbal Products</h3>
              <a href="product.php?section=new_herbal&sort=latest" class="btn-see-more">
                See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

            <div class="product-grid">
              <?php if (!empty($newProducts)): ?>
                <?php foreach ($newProducts as $p): ?>
                  <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                  <div class="product-card" style="position:relative;">
                    <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                    <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                      <i class="fa-solid fa-cart-plus"></i> Add
                    </button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="font-size:12px; color:#777;">No products available yet.</p>
              <?php endif; ?>
            </div>
            


            <!-- Wide banner (NOW CAROUSEL) -->
            <?php if (!empty($homeCenterBanners)): ?>
            <div class="wide-banner">
                <div class="wide-slider" style="position:relative; width:100%; height:100%; overflow:hidden;">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeCenterBanners as $centerBanner): ?>
                      <?php
                        $rawFn = trim($centerBanner['filename'] ?? '');
                        if (empty($rawFn)) continue; // skip empty
                        
                        $centerSrc  = '/assets/uploads/banners/' . ltrim($rawFn, '/');
                        $centerAlt  = $centerBanner['alt_text'] ?? '';
                        $centerLink = trim($centerBanner['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($centerLink): ?>
                          <a href="<?php echo htmlspecialchars($centerLink, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($centerSrc, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($centerAlt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($centerLink): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeCenterBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeCenterBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <!-- Arrows (optional but good for UX) -->
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const track = document.querySelector('.wide-slider-track');
                    const slides = document.querySelectorAll('.wide-slide');
                    const dots = document.querySelectorAll('.wide-dot');
                    const prevBtn = document.querySelector('.wide-prev');
                    const nextBtn = document.querySelector('.wide-next');
                    
                    if (!track) return;
                    if (slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      
                      // Update dots
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() {
                      currentIndex = (currentIndex + 1) % slides.length;
                      updateSlider();
                    }
                    
                    function prevSlide() {
                      currentIndex = (currentIndex - 1 + slides.length) % slides.length;
                      updateSlider();
                    }
                    
                    function startAutoSlide() {
                      stopAutoSlide();
                      autoSlideInterval = setInterval(nextSlide, 3000);
                    }
                    
                    function stopAutoSlide() {
                      if (autoSlideInterval) clearInterval(autoSlideInterval);
                    }
                    
                    // Event Listeners
                    if (nextBtn) nextBtn.addEventListener('click', () => {
                      nextSlide();
                      startAutoSlide();
                    });
                    
                    if (prevBtn) prevBtn.addEventListener('click', () => {
                      prevSlide();
                      startAutoSlide();
                    });
                    
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => {
                        currentIndex = idx;
                        updateSlider();
                        startAutoSlide();
                      });
                    });
                    
                    // Start
                    startAutoSlide();
                    
                    // Initial dot style
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

            <!-- SHOP BY SEASONAL -->
            <?php if (!empty($seasonals)): ?>
            <div class="top-seasonals-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Seasonal</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #seasonalAutoScroll::-webkit-scrollbar { display: none; }
                #seasonalAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="seasonalAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($seasonals as $s): 
                    $sImg = !empty($s['image']) ? $s['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($sImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($sImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($sImg, 'assets/') === 0) {
                        $sImg = '/' . $sImg;
                    } else {
                        $sImg = '/assets/uploads/seasonals/' . $sImg;
                    }
                ?>
                  <a href="product.php?seasonal=<?php echo urlencode($s['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($sImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($s['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Best Sellers (from homepage_products -> best_seller) -->
            <div style="margin-top:30px;">
            <div class="section-header">
              <h3>Best Sellers</h3>
              <a href="product.php?section=best_seller&sort=popularity" class="btn-see-more">
                 See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

              <div class="product-grid">
                <?php if (!empty($bestProducts)): ?>
                  <?php foreach ($bestProducts as $p): ?>
                    <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                    <div class="product-card" style="position:relative;">
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                      <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                        <i class="fa-solid fa-cart-plus"></i> Add
                      </button>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:12px; color:#777;">No best sellers available yet.</p>
                <?php endif; ?>
              </div>
            </div>
              



            <!-- HOMEPAGE VIDEO/MEDIA SECTION -->
            <?php
            // Settings fetched at top of file
            
            // Only show if URL is present
            if (!empty($hvUrl)): 
            ?>
            <div class="home-video-section">
                <div>
                    <!-- Left: Media -->
                    <div>
                         <?php 
                            $ext = strtolower(pathinfo($hvUrl, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
                            
                            if (strpos($hvUrl, 'youtube') !== false || strpos($hvUrl, 'youtu.be') !== false): 
                             // YouTube
                             $embedUrl = $hvUrl;
                             if (strpos($hvUrl, 'watch?v=') !== false) {
                                 $embedUrl = str_replace('watch?v=', 'embed/', $hvUrl);
                                 $embedUrl = explode('&', $embedUrl)[0];
                             } elseif (strpos($hvUrl, 'youtu.be/') !== false) {
                                 $embedUrl = str_replace('youtu.be/', 'www.youtube.com/embed/', $hvUrl);
                             }
                         ?>
                            <iframe src="<?= htmlspecialchars($embedUrl) ?>" allowfullscreen></iframe>
                         
                         <?php elseif ($isImage): ?>
                            <!-- Image -->
                            <img src="<?= htmlspecialchars($hvUrl) ?>" alt="Section Media">
                         
                         <?php else: ?>
                            <!-- HTML5 Video / Default -->
                            <video src="<?= htmlspecialchars($hvUrl) ?>" controls></video>
                         <?php endif; ?>
                    </div>
                    
                    <!-- Right: Text -->
                    <div>
                        <?php if($hvTitle): ?>
                            <h3 style="font-size: 28px; font-weight: 800; margin-bottom: 20px; color: #1a1a1a; font-family: 'Poppins', sans-serif;"><?= htmlspecialchars($hvTitle) ?></h3>
                        <?php endif; ?>
                        
                        <?php if($hvDesc): ?>
                            <div style="font-size: 16px; line-height: 1.8; color: #555; margin-bottom: 30px;" class="prose max-w-none">
                                <?= html_entity_decode($hvDesc) // Allow HTML from CKEditor ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($hvBtnText && $hvBtnLink): ?>
                            <div>
                                <a href="<?= htmlspecialchars($hvBtnLink) ?>" style="display: inline-block; background: <?= htmlspecialchars($hvBtnColor) ?>; color: #fff; padding: 14px 35px; border-radius: 999px; font-weight: 600; text-transform: uppercase; font-size: 14px; letter-spacing: 0.5px; transition: opacity 0.2s; text-decoration: none;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                    <?= htmlspecialchars($hvBtnText) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- WIDE BANNER (BEFORE BLOGS) - CAROUSEL -->
            <?php if (!empty($homeBeforeBlogsBanners)): ?>
            <div class="wide-banner" style="margin-top: 60px; margin-bottom: 40px;">
                <div class="wide-slider" id="blogs-slider" style="position:relative; width:100%; height:100%; overflow:hidden; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeBeforeBlogsBanners as $b): ?>
                      <?php
                        $src  = '/assets/uploads/banners/' . ltrim($b['filename'], '/');
                        $alt  = $b['alt_text'] ?? '';
                        $link = trim($b['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($link): ?>
                          <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($link): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeBeforeBlogsBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeBeforeBlogsBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <!-- Re-use the slider script logic or attach new one -->
                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const sliderId = '#blogs-slider';
                    const container = document.querySelector(sliderId);
                    if(!container) return;

                    const track = container.querySelector('.wide-slider-track');
                    const slides = container.querySelectorAll('.wide-slide');
                    const dots = container.querySelectorAll('.wide-dot');
                    const prevBtn = container.querySelector('.wide-prev');
                    const nextBtn = container.querySelector('.wide-next');
                    
                    if (!track || slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() { currentIndex = (currentIndex + 1) % slides.length; updateSlider(); }
                    function prevSlide() { currentIndex = (currentIndex - 1 + slides.length) % slides.length; updateSlider(); }
                    function startAutoSlide() { stopAutoSlide(); autoSlideInterval = setInterval(nextSlide, 3500); }
                    function stopAutoSlide() { if (autoSlideInterval) clearInterval(autoSlideInterval); }
                    
                    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); startAutoSlide(); });
                    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); startAutoSlide(); });
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => { currentIndex = idx; updateSlider(); startAutoSlide(); });
                    });
                    
                    startAutoSlide();
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

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
            <?php if ($visibleTabs > 0): ?>
            <div class="tabbed-products-section">
              <div class="tabs-bar">
                <?php if ($showLatest == '1'): ?><div class="tab-ribbon">Latest Products</div><?php endif; ?>
                <?php if ($showTrendy == '1'): ?><div class="tab-ribbon">Trendy Products</div><?php endif; ?>
                <?php if ($showSale == '1'): ?><div class="tab-ribbon">Sale Products</div><?php endif; ?>
                <?php if ($showTop == '1'): ?><div class="tab-ribbon">Top Rated</div><?php endif; ?>
              </div>

              <div class="tabbed-columns">
                <!-- Latest -->
                <?php if ($showLatest == '1'): ?>
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
                <?php endif; ?>

                <!-- Trendy -->
                <?php if ($showTrendy == '1'): ?>
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
                <?php endif; ?>

                <!-- Sale -->
                <?php if ($showSale == '1'): ?>
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
                <?php endif; ?>

                <!-- Top rated -->
                <?php if ($showTop == '1'): ?>
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
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

          </section>
        </div>
      </div>

    </div>
    </div>
    
  </main>


  <!-- CERTIFICATION BADGES SECTION -->
  <section style="background:linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding:50px 0; position:relative; overflow:hidden;">
    <!-- Decorative background elements -->
    <div style="position:absolute; top:-50px; right:-50px; width:200px; height:200px; background:rgba(255,255,255,0.1); border-radius:50%;"></div>
    <div style="position:absolute; bottom:-30px; left:-30px; width:150px; height:150px; background:rgba(255,255,255,0.1); border-radius:50%;"></div>
    
    <div style="max-width:1200px; margin:0 auto; padding:0 20px; position:relative; z-index:1;">
      <h2 style="text-align:center; font-size:24px; font-weight:700; color:#2c3e50; margin-bottom:40px; font-family:'Poppins', sans-serif; display:flex; align-items:center; justify-content:center; gap:12px;">
        <i class="<?= htmlspecialchars($certIcon) ?>" style="color:#667eea; font-size:32px;"></i>
        <?= htmlspecialchars($certTitle) ?>
      </h2>
      
      <div class="cert-badges-grid">
        <?php foreach ($certBadges as $badge): ?>
          <div class="cert-badge">
            <div>
              <?php if (!empty($badge['image'])): ?>
                <img src="<?= htmlspecialchars($badge['image']) ?>" alt="<?= htmlspecialchars($badge['title']) ?>">
              <?php else: ?>
                <i class="fa-solid fa-award"></i>
              <?php endif; ?>
            </div>
            <p style="font-size:13px; font-weight:600; color:#34495e; margin:0;"><?= htmlspecialchars($badge['title']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <style>
      .cert-badge:hover {
        transform: translateY(-10px);
      }
      .cert-badge:hover > div {
        box-shadow: 0 12px 35px rgba(0,0,0,0.2) !important;
        border-color: #667eea !important;
      }
    </style>
  </section>





  <!-- FOOTER -->
  <!-- FOOTER -->
  <!-- SUBSCRIBE SECTION -->
  <!-- Features Row -->

  <!-- FEATURES ROW -->
  <div class="features-section">
    <div class="features-container">
      <?php if (!empty($features)): ?>
        <?php foreach ($features as $ft): ?>
          <div class="feature-item">
            <div class="feature-icon-box">
              <i class="<?php echo htmlspecialchars($ft['icon']); ?>"></i>
            </div>
            <div class="feature-text">
              <h5><?php echo htmlspecialchars($ft['title']); ?></h5>
              <p><?php echo htmlspecialchars($ft['desc']); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- OUR STORIES SECTION (Multi-Card Grid) -->
  <?php if (!empty($ourStories)): ?>
  <section style="background:#fff; padding:80px 0;">
    <div style="max-width:1200px; margin:0 auto; padding:0 20px;">
      <h2 style="font-size:28px; font-weight:700; color:#1a1a1a; margin-bottom:50px; font-family:'Poppins', sans-serif; text-transform:uppercase; letter-spacing:2px; text-align:center;">
        OUR STORY
      </h2>
      
      <!-- Single Story Content (New) -->
      <?php if (!empty($osTitle) || !empty($osDesc) || !empty($osImage)): ?>
      <div style="margin-bottom: 50px; text-align: center;">
          <?php if (!empty($osImage)): 
             $sImg = $osImage;
             if (strpos($sImg, 'http') === 0) {}
             elseif (strpos($sImg, '/') === 0) {}
             elseif (strpos($sImg, 'assets/') === 0) { $sImg = '/' . $sImg; }
             else { $sImg = '/assets/uploads/story/' . $sImg; }
          ?>
          <div style="margin-bottom:20px; border-radius:12px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto 30px auto;">
            <img src="<?= htmlspecialchars($sImg) ?>" alt="Our Story" style="width:100%; height:auto; display:block;">
          </div>
          <?php endif; ?>
          
          <?php if (!empty($osTitle)): ?>
          <h3 style="font-size:24px; font-weight:700; color:#1a1a1a; margin-bottom:15px;"><?= htmlspecialchars($osTitle) ?></h3>
          <?php endif; ?>
          
          <?php if (!empty($osDesc)): ?>
          <p style="font-size:16px; color:#555; line-height:1.8; max-width:800px; margin:0 auto;"><?= nl2br(htmlspecialchars($osDesc)) ?></p>
          <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="our-story-grid">
        <?php foreach ($ourStories as $story): ?>
          <div style="text-align:center;">
            <?php if (!empty($story['image'])): 
                $sImg = $story['image'];
                if (strpos($sImg, 'http') === 0) {
                    // URL
                } elseif (strpos($sImg, '/') === 0) {
                    // Absolute path
                } elseif (strpos($sImg, 'assets/') === 0) {
                    $sImg = '/' . $sImg;
                } else {
                    $sImg = '/assets/uploads/story/' . $sImg;
                }
            ?>
              <div style="margin-bottom:20px; border-radius:12px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,0.1); height:250px;">
                <img src="<?= htmlspecialchars($sImg) ?>" alt="<?= htmlspecialchars($story['title']) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">

              </div>
            <?php endif; ?>
            
            <h3 style="font-size:16px; font-weight:700; color:#1a1a1a; margin-bottom:12px; text-transform:uppercase; letter-spacing:1px;">
              <?= htmlspecialchars($story['title']) ?>
            </h3>
            
            <p style="font-size:14px; color:#666; line-height:1.6; margin:0;">
              <?= htmlspecialchars($story['description']) ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

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

  // HOMEPAGE ADD TO CART LOGIC
  document.addEventListener('DOMContentLoaded', function() {
    const miniBtns = document.querySelectorAll('.btn-add-cart-mini');
    
    miniBtns.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent any parent link action if structure changes
        e.stopPropagation(); // Stop bubbling
        
        const productId = this.getAttribute('data-product-id');
        const originalHtml = this.innerHTML;
        const originalBg = this.style.background;
        
        // Show loading state
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';
        this.style.pointerEvents = 'none';
        
        fetch('ajax_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&product_id=${productId}&quantity=1`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Success state
                this.innerHTML = '<i class="fa-solid fa-check"></i> Added';
                this.style.background = '#27ae60'; // Green
                
                // Update Cart Count in Header (desktop + mobile)
                // Desktop: .cart-count, Mobile: .mobile-cart-count
                const desktopCount = document.querySelector('.cart-count');
                const mobileCount = document.querySelector('.mobile-cart-count');
                const mobileNavCount = document.querySelector('.mobile-bottom-nav .count'); // adjust selector if needed

                if (desktopCount) desktopCount.textContent = data.count;
                if (mobileCount) mobileCount.textContent = data.count;
                if (mobileNavCount) mobileNavCount.textContent = `(${data.count})`; // logic might vary

                // Revert button after 2s
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.style.background = originalBg; // Restore original gradient/color
                    this.style.pointerEvents = 'auto';
                }, 2000);
            } else {
                // Error (e.g. login required)
                if (data.message && data.message.toLowerCase().includes('login')) {
                     window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                } else {
                     alert(data.message || 'Failed to add to cart');
                     this.innerHTML = originalHtml;
                     this.style.pointerEvents = 'auto';
                }
            }
        })
        .catch(err => {
            console.error(err);
            this.innerHTML = originalHtml;
            this.style.pointerEvents = 'auto';
        });
      });
    });
  });
</script>

</body>
</html<!-- CUSTOMER REVIEWS SECTION -->
  <?php if (!empty($homeReviews)): ?>
  <section class="section-reviews" style="background:linear-gradient(to bottom, #ffffff, #f9fafb); padding:70px 0;">
    <div class="container">
      <!-- Enhanced Section Header -->
      <div style="text-align:center; margin-bottom:50px;">
        <h2 style="font-size:32px; font-weight:700; color:#1a1a1a; margin-bottom:12px; font-family:'Poppins', sans-serif;">
          What Our Customers Say
        </h2>
        <p style="font-size:15px; color:#666; margin:0;">Real reviews from verified purchases</p>
      </div>
      
      <div class="reviews-scroll-container">
        <?php foreach ($homeReviews as $review): 
            $prodImg = get_first_image($review['product_images']);
        ?>
          <div class="home-review-card">
            <div class="quote-icon"><i class="fa-solid fa-quote-left"></i></div>
            
            <div class="review-stars">
              <?= str_repeat('<i class="fa-solid fa-star"></i>', (int)$review['rating']) ?>
              <?= str_repeat('<i class="fa-regular fa-star"></i>', 5 - (int)$review['rating']) ?>
            </div>
            
            <p class="review-text">
              "<?= htmlspecialchars(mb_strimwidth($review['comment'], 0, 140, '...')) ?>"
            </p>
            
            <div class="review-meta">
              <a href="product_view.php?id=<?= $review['product_id'] ?>" class="review-prod-img">
                <img src="<?= htmlspecialchars($prodImg) ?>" alt="Product">
              </a>
              <div class="review-info">
                <div class="reviewer-name">
                  <?= htmlspecialchars($review['reviewer_name'] ?: 'Verified Customer') ?>
                  <i class="fa-solid fa-circle-check" style="color:#2ECC71; margin-left:4px; font-size:12px;"></i>
                </div>
                <div class="reviewed-product">
                  Verified Purchase • <a href="product_view.php?id=<?= $review['product_id'] ?>"><?= htmlspecialchars($review['product_name']) ?></a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <style>
      .section-reviews {
          padding: 60px 0; /* Reduced padding to match others */
          background: linear-gradient(to bottom, #ffffff, #fcfcfc);
      }
      
      /* Removed custom .center-header styles to use global .section-header */

      .reviews-scroll-container {
          display: flex;
          overflow-x: auto;
          gap: 25px;
          padding: 20px 10px 40px;
          scroll-snap-type: x mandatory;
          -webkit-overflow-scrolling: touch;
          scrollbar-width: none; 
      }
      .reviews-scroll-container::-webkit-scrollbar {
          display: none;
      }

      .home-review-card {
          background: #fff;
          padding: 30px; /* Slightly reduced */
          border-radius: 16px;
          box-shadow: 0 4px 20px rgba(0,0,0,0.05); /* Softer shadow */
          flex: 0 0 350px; /* Back to 350px */
          scroll-snap-align: start; /* Standard snap */
          position: relative;
          transition: transform 0.3s ease, box-shadow 0.3s ease;
          border: 1px solid rgba(0,0,0,0.03);
          display: flex;
          flex-direction: column;
      }
      .home-review-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 10px 30px rgba(0,0,0,0.08); /* Consistent hover */
          border-color: #A41B42;
      }

      .quote-icon {
          position: absolute;
          top: 20px;
          right: 25px;
          font-size: 32px; /* Smaller icon */
          color: #f2f2f2;
          z-index: 0;
      }

      .review-stars {
          color: #ffb400; /* Standard gold */
          font-size: 14px;
          margin-bottom: 15px;
          position: relative;
          z-index: 1;
      }
      .review-stars i { margin-right: 2px; }

      .review-text {
          font-size: 15px; /* Standard text size */
          line-height: 1.7;
          color: #555;
          margin-bottom: 25px;
          flex-grow: 1;
          font-style: italic;
          position: relative;
          z-index: 1;
      }

      .review-meta {
          display: flex;
          align-items: center;
          gap: 15px;
          padding-top: 20px;
          border-top: 1px solid #f5f5f5;
      }

      .review-prod-img {
          width: 50px;
          height: 50px;
          border-radius: 50%;
          overflow: hidden;
          border: 1px solid #eee;
          box-shadow: none; /* Cleaner */
      }
      .review-prod-img img {
          width: 100%;
          height: 100%;
          object-fit: cover;
      }

      .review-info {
          display: flex;
          flex-direction: column;
      }
      .reviewer-name {
          font-weight: 700;
          color: #333;
          font-size: 15px;
          display: flex;
          align-items: center;
      }
      .reviewed-product {
          font-size: 12px;
          color: #888;
          margin-top: 2px;
      }
      .reviewed-product a {
          color: #A41B42;
          text-decoration: none;
          font-weight: 600;
          transition: color 0.2s;
      }
      .reviewed-product a:hover {
          text-decoration: underline;
      }

      /* Responsive */
      @media (max-width: 768px) {
          .section-reviews { padding: 40px 0; }
          .reviews-scroll-container { gap: 15px; padding: 10px 15px 20px; }
          .home-review-card { flex: 0 0 300px; padding: 20px; }
          .quote-icon { font-size: 24px; top: 15px; right: 15px; }
          .review-text { font-size: 14px; margin-bottom: 20px; }
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
              const cardWidth = reviewsContainer.queryS<!-- Wide banner (NOW CAROUSEL) -->
            <?php if (!empty($homeCenterBanners)): ?>
            <div class="wide-banner">
                <div class="wide-slider" style="position:relative; width:100%; height:100%; overflow:hidden;">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeCenterBanners as $centerBanner): ?>
                      <?php
                        $rawFn = trim($centerBanner['filename'] ?? '');
                        if (empty($rawFn)) continue; // skip empty
                        
                        $centerSrc  = '/assets/uploads/banners/' . ltrim($rawFn, '/');
                        $centerAlt  = $centerBanner['alt_text'] ?? '';
                        $centerLink = trim($centerBanner['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($centerLink): ?>
                          <a href="<?php echo htmlspecialchars($centerLink, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($centerSrc, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($centerAlt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($centerLink): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeCenterBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeCenterBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <!-- Arrows (optional but good for UX) -->
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const track = document.querySelector('.wide-slider-track');
                    const slides = document.querySelectorAll('.wide-slide');
                    const dots = document.querySelectorAll('.wide-dot');
                    const prevBtn = document.querySelector('.wide-prev');
                    const nextBtn = document.querySelector('.wide-next');
                    
                    if (!track) return;
                    if (slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      
                      // Update dots
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() {
                      currentIndex = (currentIndex + 1) % slides.length;
                      updateSlider();
                    }
                    
                    function prevSlide() {
                      currentIndex = (currentIndex - 1 + slides.length) % slides.length;
                      updateSlider();
                    }
                    
                    function startAutoSlide() {
                      stopAutoSlide();
                      autoSlideInterval = setInterval(nextSlide, 3000);
                    }
                    
                    function stopAutoSlide() {
                      if (autoSlideInterval) clearInterval(autoSlideInterval);
                    }
                    
                    // Event Listeners
                    if (nextBtn) nextBtn.addEventListener('click', () => {
                      nextSlide();
                      startAutoSlide();
                    });
                    
                    if (prevBtn) prevBtn.addEventListener('click', () => {
                      prevSlide();
                      startAutoSlide();
                    });
                    
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => {
                        currentIndex = idx;
                        updateSlider();
                        startAutoSlide();
                      });
                    });
                    
                    // Start
                    startAutoSlide();
                    
                    // Initial dot style
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

            <!-- Best Sellers (from homepage_products -> best_seller) -->
            <div style="margin-top:30px;">
            <div class="section-header">
              <h3>Best Sellers</h3>
              <a href="product.php?section=best_seller&sort=popularity" class="btn-see-more">
                 See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

              <div class="product-grid">
                <?php if (!empty($bestProducts)): ?>
                  <?php foreach ($bestProducts as $p): ?>
                    <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                    <div class="product-card" style="position:relative;">
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                      <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                        <i class="fa-solid fa-cart-plus"></i> Add
                      </button>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:12px; color:#777;">No best sellers available yet.</p>
                <?php endif; ?>
              </div>
            </div>
              



            <!-- Best Sellers (from homepage_products -> best_seller) -->
            <div style="margin-top:30px;">
            <div class="section-header">
              <h3>Best Sellers</h3>
              <a href="product.php?section=best_seller&sort=popularity" class="btn-see-more">
                 See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

              <div class="product-grid">
                <?php if (!empty($bestProducts)): ?>
                  <?php foreach ($bestProducts as $p): ?>
                    <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                    <div class="product-card" style="position:relative;">
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                      <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                        <i class="fa-solid fa-cart-plus"></i> Add
                      </button>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:12px; color:#777;">No best sellers available yet.</p>
                <?php endif; ?>
              </div>
            </div>
              



            <!-- SHOP BY SEASONAL -->
            <?php if (!empty($seasonals)): ?>
            <div class="top-seasonals-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Seasonal</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #seasonalAutoScroll::-webkit-scrollbar { display: none; }
                #seasonalAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="seasonalAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($seasonals as $s): 
                    $sImg = !empty($s['image']) ? $s['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($sImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($sImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($sImg, 'assets/') === 0) {
                        $sImg = '/' . $sImg;
                    } else {
                        $sImg = '/assets/uploads/seasonals/' . $sImg;
                    }
                ?>
                  <a href="product.php?seasonal=<?php echo urlencode($s['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($sImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($s['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- SHOP BY SEASONAL -->
            <?php if (!empty($seasonals)): ?>
            <div class="top-seasonals-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Seasonal</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #seasonalAutoScroll::-webkit-scrollbar { display: none; }
                #seasonalAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="seasonalAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($seasonals as $s): 
                    $sImg = !empty($s['image']) ? $s['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($sImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($sImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($sImg, 'assets/') === 0) {
                        $sImg = '/' . $sImg;
                    } else {
                        $sImg = '/assets/uploads/seasonals/' . $sImg;
                    }
                ?>
                  <a href="product.php?seasonal=<?php echo urlencode($s['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($sImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($s['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- HOMEPAGE VIDEO/MEDIA SECTION -->
            <?php
            // Settings fetched at top of file
            
            // Only show if URL is present
            if (!empty($hvUrl)): 
            ?>
            <div class="home-video-section">
                <div>
                    <!-- Left: Media -->
                    <div>
                         <?php 
                            $ext = strtolower(pathinfo($hvUrl, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
                            
                            if (strpos($hvUrl, 'youtube') !== false || strpos($hvUrl, 'youtu.be') !== false): 
                             // YouTube
                             $embedUrl = $hvUrl;
                             if (strpos($hvUrl, 'watch?v=') !== false) {
                                 $embedUrl = str_replace('watch?v=', 'embed/', $hvUrl);
                                 $embedUrl = explode('&', $embedUrl)[0];
                             } elseif (strpos($hvUrl, 'youtu.be/') !== false) {
                                 $embedUrl = str_replace('youtu.be/', 'www.youtube.com/embed/', $hvUrl);
                             }
                         ?>
                            <iframe src="<?= htmlspecialchars($embedUrl) ?>" allowfullscreen></iframe>
                         
                         <?php elseif ($isImage): ?>
                            <!-- Image -->
                            <img src="<?= htmlspecialchars($hvUrl) ?>" alt="Section Media">
                         
                         <?php else: ?>
                            <!-- HTML5 Video / Default -->
                            <video src="<?= htmlspecialchars($hvUrl) ?>" controls></video>
                         <?php endif; ?>
                    </div>
                    
                    <!-- Right: Text -->
                    <div>
                        <?php if($hvTitle): ?>
                            <h3 style="font-size: 28px; font-weight: 800; margin-bottom: 20px; color: #1a1a1a; font-family: 'Poppins', sans-serif;"><?= htmlspecialchars($hvTitle) ?></h3>
                        <?php endif; ?>
                        
                        <?php if($hvDesc): ?>
                            <div style="font-size: 16px; line-height: 1.8; color: #555; margin-bottom: 30px;" class="prose max-w-none">
                                <?= html_entity_decode($hvDesc) // Allow HTML from CKEditor ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($hvBtnText && $hvBtnLink): ?>
                            <div>
                                <a href="<?= htmlspecialchars($hvBtnLink) ?>" style="display: inline-block; background: <?= htmlspecialchars($hvBtnColor) ?>; color: #fff; padding: 14px 35px; border-radius: 999px; font-weight: 600; text-transform: uppercase; font-size: 14px; letter-spacing: 0.5px; transition: opacity 0.2s; text-decoration: none;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                    <?= htmlspecialchars($hvBtnText) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- WIDE BANNER (BEFORE BLOGS) - CAROUSEL -->
            <?php if (!empty($homeBeforeBlogsBanners)): ?>
            <div class="wide-banner" style="margin-top: 60px; margin-bottom: 40px;">
                <div class="wide-slider" id="blogs-slider" style="position:relative; width:100%; height:100%; overflow:hidden; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeBeforeBlogsBanners as $b): ?>
                      <?php
                        $src  = '/assets/uploads/banners/' . ltrim($b['filename'], '/');
                        $alt  = $b['alt_text'] ?? '';
                        $link = trim($b['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($link): ?>
                          <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($link): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeBeforeBlogsBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeBeforeBlogsBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <!-- Re-use the slider script logic or attach new one -->
                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const sliderId = '#blogs-slider';
                    const container = document.querySelector(sliderId);
                    if(!container) return;

                    const track = container.querySelector('.wide-slider-track');
                    const slides = container.querySelectorAll('.wide-slide');
                    const dots = container.querySelectorAll('.wide-dot');
                    const prevBtn = container.querySelector('.wide-prev');
                    const nextBtn = container.querySelector('.wide-next');
                    
                    if (!track || slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() { currentIndex = (currentIndex + 1) % slides.length; updateSlider(); }
                    function prevSlide() { currentIndex = (currentIndex - 1 + slides.length) % slides.length; updateSlider(); }
                    function startAutoSlide() { stopAutoSlide(); autoSlideInterval = setInterval(nextSlide, 3500); }
                    function stopAutoSlide() { if (autoSlideInterval) clearInterval(autoSlideInterval); }
                    
                    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); startAutoSlide(); });
                    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); startAutoSlide(); });
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => { currentIndex = idx; updateSlider(); startAutoSlide(); });
                    });
                    
                    startAutoSlide();
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

            <!-- MULTI COLUMN TABBED PRODUCTS -->
            <?php if ($visibleTabs > 0): ?>
            <div class="tabbed-products-section">
              <div class="tabs-bar">
                <?php if ($showLatest == '1'): ?><div class="tab-ribbon">Latest Products</div><?php endif; ?>
                <?php if ($showTrendy == '1'): ?><div class="tab-ribbon">Trendy Products</div><?php endif; ?>
                <?php if ($showSale == '1'): ?><div class="tab-ribbon">Sale Products</div><?php endif; ?>
                <?php if ($showTop == '1'): ?><div class="tab-ribbon">Top Rated</div><?php endif; ?>
              </div>

              <div class="tabbed-columns">
                <!-- Latest -->
                <?php if ($showLatest == '1'): ?>
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
                <?php endif; ?>

                <!-- Trendy -->
                <?php if ($showTrendy == '1'): ?>
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
                <?php endif; ?>

                <!-- Sale -->
                <?php if ($showSale == '1'): ?>
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
                <?php endif; ?>

                <!-- Top rated -->
                <?php if ($showTop == '1'): ?>
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
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

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

            </section>

          <!-- ALL OTHER CONTENT -->
          <section class="content-grid">
            <!-- Top banners -->
            <!-- Top banners -->
            <style>
              .banner-row {
                 gap: 20px !important; /* Increase image-to-image space */
              }
              .banner-row-container {
                 margin-bottom: 40px !important; /* Increase space to next section */
                 padding: 10px 0;
              }
              .banner-item {
                 /* Optional: Add shadow/border to pop */
                 /* border: 1px solid #eee; */
              }
            </style>
            <div class="banner-row-container">
              <div class="banner-row">
                <?php 
                if (!empty($bannerGroups)): 
                  // Duplicate for smooth marquee effect
                  $loopGroups = array_merge($bannerGroups, $bannerGroups, $bannerGroups);
                  foreach ($loopGroups as $bg): 
                    $bgImg = !empty($bg['image']) ? $bg['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path
                    if (strpos($bgImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($bgImg, '/') === 0) {
                        // is absolute
                    } else {
                        $bgImg = '/assets/uploads/groups/' . $bgImg;
                    }
                    
                    $url = 'product.php?group_id=' . (int)$bg['id'];
                ?>
                  <a class="banner-item" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="<?php echo htmlspecialchars($bgImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($bg['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="banner-caption">
                      <h4><?php echo htmlspecialchars($bg['name']); ?></h4>
                    </div>
                  </a>
                <?php endforeach; ?>
                <?php else: ?>
                   <p class="text-center p-4 text-gray-400">No collections found</p>
                <?php endif; ?>
              </div>
            </div>

            <!-- TOP LEVEL CATEGORIES GRID (VISUAL) -->
            <?php if (!empty($categories)): ?>
            <div class="top-categories-section">
              <div class="section-header">
                <h3>Shop by Category</h3>
              </div>
              
              <!-- AUTO SCROLL CONTAINER (3 Items Visible) -->
              <style>
                #categoryAutoScroll::-webkit-scrollbar {
                  display: none;
                }
                #categoryAutoScroll {
                  -ms-overflow-style: none;
                  scrollbar-width: none;
                }
                .cat-slide-item {
                  /* Default Desktop: Gap 25px, 3 items */
                  flex: 0 0 calc(33.333% - 17px);
                }
                @media (max-width: 768px) {
                  #categoryAutoScroll {
                    gap: 20px !important; /* Increased mobile gap */
                  }
                  .cat-slide-item {
                    /* Mobile: Show 2 Items. 
                       Formula: (100% - 20px) / 2 = 50% - 10px */
                    flex: 0 0 calc(50% - 10px) !important;
                  }
                  .cat-slide-item h4 {
                    font-size: 13px !important; /* Slightly larger text for bigger images */
                  }
                }
              </style>
              
              <div id="categoryAutoScroll" style="display: flex; gap: 25px; overflow-x: auto; white-space: nowrap; padding: 10px 0; -webkit-overflow-scrolling: touch; cursor: pointer;">
                <?php 
                $catDebug = [];
                // Duplicate categories to ensure scrolling if few items
                $displayCats = $categories;
                if (count($displayCats) < 6) {
                    $displayCats = array_merge($displayCats, $categories); // duplicate once
                }

                foreach ($displayCats as $cat): 
                    // Use 'image' column directly
                    $rawImg = !empty($cat['image']) ? $cat['image'] : ($cat['img'] ?? '');
                    $catImg = trim($rawImg);
                    
                    if ($catImg === '') {
                        $catImg = '/assets/images/category-placeholder.jpg';
                    } else {
                        // Parse Image Path
                        if (strpos($catImg, 'http') === 0) {
                        } elseif (strpos($catImg, '/') === 0) {
                        } elseif (strpos($catImg, 'assets/') === 0) {
                            $catImg = '/' . $catImg;
                        } else {
                            $catImg = '/assets/uploads/categories/' . $catImg;
                        }
                    }
                ?>
                  <a href="product.php?category[]=<?php echo urlencode($cat['title']); ?>" class="cat-slide-item" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <!-- Full Width Image Container (Square) -->
                    <div style="width: 100%; aspect-ratio: 1/1; border-radius: 16px; overflow: hidden; border: 2px solid #eee; box-shadow: 0 6px 15px rgba(0,0,0,0.08); background: #fff; position: relative;">
                      <img src="<?php echo htmlspecialchars($catImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($cat['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #333; line-height: 1.3; max-width: 100%; text-align:center; white-space: normal;">
                      <?php echo htmlspecialchars($cat['title']); ?>
                    </h4>
                  </a>
                <?php endforeach; ?>
              </div>

              <!-- AUTO SCROLL SCRIPT -->
              <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const scrollContainer = document.getElementById("categoryAutoScroll");
                    let scrollSpeed = 1.0; // Slightly faster to handle sub-pixel issues
                    let isHovered = false;

                    function autoScroll() {
                        if (!isHovered && scrollContainer) {
                            // Increment scroll
                            scrollContainer.scrollLeft += scrollSpeed;
                            
                            // Check if reached end
                            // Tolerance of 1px
                            if (scrollContainer.scrollLeft >= (scrollContainer.scrollWidth - scrollContainer.clientWidth - 1)) {
                                scrollContainer.scrollLeft = 0; // Reset to start
                            }
                        }
                        requestAnimationFrame(autoScroll);
                    }
                    
                    if(scrollContainer) {
                        // Remove potential CSS conflict
                        scrollContainer.style.scrollBehavior = 'auto'; 
                        
                        // Pause on hover
                        scrollContainer.addEventListener("mouseenter", () => isHovered = true);
                        scrollContainer.addEventListener("mouseleave", () => isHovered = false);
                        
                        // Start loop
                        requestAnimationFrame(autoScroll);
                    }
                });
              </script>

            </div>
            <?php endif; ?>

            <!-- SHOP BY CONCERN -->
            <?php if (!empty($concerns)): ?>
            <div class="top-concerns-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Concern</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #concernAutoScroll::-webkit-scrollbar { display: none; }
                #concernAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="concernAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($concerns as $c): 
                    $cImg = !empty($c['image']) ? $c['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($cImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($cImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($cImg, 'assets/') === 0) {
                        $cImg = '/' . $cImg;
                    } else {
                        $cImg = '/assets/uploads/concerns/' . $cImg;
                    }
                ?>
                  <a href="product.php?concern=<?php echo urlencode($c['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($cImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($c['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>


            <!-- New Herbal Products (latest products from DB) -->
            <div class="section-header">
              <h3>New Herbal Products</h3>
              <a href="product.php?section=new_herbal&sort=latest" class="btn-see-more">
                See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

            <div class="product-grid">
              <?php if (!empty($newProducts)): ?>
                <?php foreach ($newProducts as $p): ?>
                  <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                  <div class="product-card" style="position:relative;">
                    <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                    <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                      <i class="fa-solid fa-cart-plus"></i> Add
                    </button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="font-size:12px; color:#777;">No products available yet.</p>
              <?php endif; ?>
            </div>
            


            <!-- Wide banner (NOW CAROUSEL) -->
            <?php if (!empty($homeCenterBanners)): ?>
            <div class="wide-banner">
                <div class="wide-slider" style="position:relative; width:100%; height:100%; overflow:hidden;">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeCenterBanners as $centerBanner): ?>
                      <?php
                        $rawFn = trim($centerBanner['filename'] ?? '');
                        if (empty($rawFn)) continue; // skip empty
                        
                        $centerSrc  = '/assets/uploads/banners/' . ltrim($rawFn, '/');
                        $centerAlt  = $centerBanner['alt_text'] ?? '';
                        $centerLink = trim($centerBanner['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($centerLink): ?>
                          <a href="<?php echo htmlspecialchars($centerLink, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($centerSrc, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($centerAlt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($centerLink): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeCenterBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeCenterBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <!-- Arrows (optional but good for UX) -->
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const track = document.querySelector('.wide-slider-track');
                    const slides = document.querySelectorAll('.wide-slide');
                    const dots = document.querySelectorAll('.wide-dot');
                    const prevBtn = document.querySelector('.wide-prev');
                    const nextBtn = document.querySelector('.wide-next');
                    
                    if (!track) return;
                    if (slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      
                      // Update dots
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() {
                      currentIndex = (currentIndex + 1) % slides.length;
                      updateSlider();
                    }
                    
                    function prevSlide() {
                      currentIndex = (currentIndex - 1 + slides.length) % slides.length;
                      updateSlider();
                    }
                    
                    function startAutoSlide() {
                      stopAutoSlide();
                      autoSlideInterval = setInterval(nextSlide, 3000);
                    }
                    
                    function stopAutoSlide() {
                      if (autoSlideInterval) clearInterval(autoSlideInterval);
                    }
                    
                    // Event Listeners
                    if (nextBtn) nextBtn.addEventListener('click', () => {
                      nextSlide();
                      startAutoSlide();
                    });
                    
                    if (prevBtn) prevBtn.addEventListener('click', () => {
                      prevSlide();
                      startAutoSlide();
                    });
                    
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => {
                        currentIndex = idx;
                        updateSlider();
                        startAutoSlide();
                      });
                    });
                    
                    // Start
                    startAutoSlide();
                    
                    // Initial dot style
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

            <!-- SHOP BY SEASONAL -->
            <?php if (!empty($seasonals)): ?>
            <div class="top-seasonals-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Seasonal</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #seasonalAutoScroll::-webkit-scrollbar { display: none; }
                #seasonalAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="seasonalAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($seasonals as $s): 
                    $sImg = !empty($s['image']) ? $s['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($sImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($sImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($sImg, 'assets/') === 0) {
                        $sImg = '/' . $sImg;
                    } else {
                        $sImg = '/assets/uploads/seasonals/' . $sImg;
                    }
                ?>
                  <a href="product.php?seasonal=<?php echo urlencode($s['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($sImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($s['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Best Sellers (from homepage_products -> best_seller) -->
            <div style="margin-top:30px;">
            <div class="section-header">
              <h3>Best Sellers</h3>
              <a href="product.php?section=best_seller&sort=popularity" class="btn-see-more">
                 See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

              <div class="product-grid">
                <?php if (!empty($bestProducts)): ?>
                  <?php foreach ($bestProducts as $p): ?>
                    <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                    <div class="product-card" style="position:relative;">
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                      <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                        <i class="fa-solid fa-cart-plus"></i> Add
                      </button>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:12px; color:#777;">No best sellers available yet.</p>
                <?php endif; ?>
              </div>
            </div>
              



            <!-- HOMEPAGE VIDEO/MEDIA SECTION -->
            <?php
            // Settings fetched at top of file
            
            // Only show if URL is present
            if (!empty($hvUrl)): 
            ?>
            <div class="home-video-section">
                <div>
                    <!-- Left: Media -->
                    <div>
                         <?php 
                            $ext = strtolower(pathinfo($hvUrl, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
                            
                            if (strpos($hvUrl, 'youtube') !== false || strpos($hvUrl, 'youtu.be') !== false): 
                             // YouTube
                             $embedUrl = $hvUrl;
                             if (strpos($hvUrl, 'watch?v=') !== false) {
                                 $embedUrl = str_replace('watch?v=', 'embed/', $hvUrl);
                                 $embedUrl = explode('&', $embedUrl)[0];
                             } elseif (strpos($hvUrl, 'youtu.be/') !== false) {
                                 $embedUrl = str_replace('youtu.be/', 'www.youtube.com/embed/', $hvUrl);
                             }
                         ?>
                            <iframe src="<?= htmlspecialchars($embedUrl) ?>" allowfullscreen></iframe>
                         
                         <?php elseif ($isImage): ?>
                            <!-- Image -->
                            <img src="<?= htmlspecialchars($hvUrl) ?>" alt="Section Media">
                         
                         <?php else: ?>
                            <!-- HTML5 Video / Default -->
                            <video src="<?= htmlspecialchars($hvUrl) ?>" controls></video>
                         <?php endif; ?>
                    </div>
                    
                    <!-- Right: Text -->
                    <div>
                        <?php if($hvTitle): ?>
                            <h3 style="font-size: 28px; font-weight: 800; margin-bottom: 20px; color: #1a1a1a; font-family: 'Poppins', sans-serif;"><?= htmlspecialchars($hvTitle) ?></h3>
                        <?php endif; ?>
                        
                        <?php if($hvDesc): ?>
                            <div style="font-size: 16px; line-height: 1.8; color: #555; margin-bottom: 30px;" class="prose max-w-none">
                                <?= html_entity_decode($hvDesc) // Allow HTML from CKEditor ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($hvBtnText && $hvBtnLink): ?>
                            <div>
                                <a href="<?= htmlspecialchars($hvBtnLink) ?>" style="display: inline-block; background: <?= htmlspecialchars($hvBtnColor) ?>; color: #fff; padding: 14px 35px; border-radius: 999px; font-weight: 600; text-transform: uppercase; font-size: 14px; letter-spacing: 0.5px; transition: opacity 0.2s; text-decoration: none;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                    <?= htmlspecialchars($hvBtnText) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- WIDE BANNER (BEFORE BLOGS) - CAROUSEL -->
            <?php if (!empty($homeBeforeBlogsBanners)): ?>
            <div class="wide-banner" style="margin-top: 60px; margin-bottom: 40px;">
                <div class="wide-slider" id="blogs-slider" style="position:relative; width:100%; height:100%; overflow:hidden; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeBeforeBlogsBanners as $b): ?>
                      <?php
                        $src  = '/assets/uploads/banners/' . ltrim($b['filename'], '/');
                        $alt  = $b['alt_text'] ?? '';
                        $link = trim($b['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($link): ?>
                          <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($link): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeBeforeBlogsBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeBeforeBlogsBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <!-- Re-use the slider script logic or attach new one -->
                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const sliderId = '#blogs-slider';
                    const container = document.querySelector(sliderId);
                    if(!container) return;

                    const track = container.querySelector('.wide-slider-track');
                    const slides = container.querySelectorAll('.wide-slide');
                    const dots = container.querySelectorAll('.wide-dot');
                    const prevBtn = container.querySelector('.wide-prev');
                    const nextBtn = container.querySelector('.wide-next');
                    
                    if (!track || slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() { currentIndex = (currentIndex + 1) % slides.length; updateSlider(); }
                    function prevSlide() { currentIndex = (currentIndex - 1 + slides.length) % slides.length; updateSlider(); }
                    function startAutoSlide() { stopAutoSlide(); autoSlideInterval = setInterval(nextSlide, 3500); }
                    function stopAutoSlide() { if (autoSlideInterval) clearInterval(autoSlideInterval); }
                    
                    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); startAutoSlide(); });
                    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); startAutoSlide(); });
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => { currentIndex = idx; updateSlider(); startAutoSlide(); });
                    });
                    
                    startAutoSlide();
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

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
            <?php if ($visibleTabs > 0): ?>
            <div class="tabbed-products-section">
              <div class="tabs-bar">
                <?php if ($showLatest == '1'): ?><div class="tab-ribbon">Latest Products</div><?php endif; ?>
                <?php if ($showTrendy == '1'): ?><div class="tab-ribbon">Trendy Products</div><?php endif; ?>
                <?php if ($showSale == '1'): ?><div class="tab-ribbon">Sale Products</div><?php endif; ?>
                <?php if ($showTop == '1'): ?><div class="tab-ribbon">Top Rated</div><?php endif; ?>
              </div>

              <div class="tabbed-columns">
                <!-- Latest -->
                <?php if ($showLatest == '1'): ?>
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
                <?php endif; ?>

                <!-- Trendy -->
                <?php if ($showTrendy == '1'): ?>
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
                <?php endif; ?>

                <!-- Sale -->
                <?php if ($showSale == '1'): ?>
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
                <?php endif; ?>

                <!-- Top rated -->
                <?php if ($showTop == '1'): ?>
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
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

          </section>
        </div>
      </div>

    </div>
    </div>
    
  </main>


  <!-- CERTIFICATION BADGES SECTION -->
  <section style="background:linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding:50px 0; position:relative; overflow:hidden;">
    <!-- Decorative background elements -->
    <div style="position:absolute; top:-50px; right:-50px; width:200px; height:200px; background:rgba(255,255,255,0.1); border-radius:50%;"></div>
    <div style="position:absolute; bottom:-30px; left:-30px; width:150px; height:150px; background:rgba(255,255,255,0.1); border-radius:50%;"></div>
    
    <div style="max-width:1200px; margin:0 auto; padding:0 20px; position:relative; z-index:1;">
      <h2 style="text-align:center; font-size:24px; font-weight:700; color:#2c3e50; margin-bottom:40px; font-family:'Poppins', sans-serif; display:flex; align-items:center; justify-content:center; gap:12px;">
        <i class="<?= htmlspecialchars($certIcon) ?>" style="color:#667eea; font-size:32px;"></i>
        <?= htmlspecialchars($certTitle) ?>
      </h2>
      
      <div class="cert-badges-grid">
        <?php foreach ($certBadges as $badge): ?>
          <div class="cert-badge">
            <div>
              <?php if (!empty($badge['image'])): ?>
                <img src="<?= htmlspecialchars($badge['image']) ?>" alt="<?= htmlspecialchars($badge['title']) ?>">
              <?php else: ?>
                <i class="fa-solid fa-award"></i>
              <?php endif; ?>
            </div>
            <p style="font-size:13px; font-weight:600; color:#34495e; margin:0;"><?= htmlspecialchars($badge['title']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <style>
      .cert-badge:hover {
        transform: translateY(-10px);
      }
      .cert-badge:hover > div {
        box-shadow: 0 12px 35px rgba(0,0,0,0.2) !important;
        border-color: #667eea !important;
      }
    </style>
  </section>





  <!-- FOOTER -->
  <!-- FOOTER -->
  <!-- SUBSCRIBE SECTION -->
  <!-- Features Row -->

  <!-- FEATURES ROW -->
  <div class="features-section">
    <div class="features-container">
      <?php if (!empty($features)): ?>
        <?php foreach ($features as $ft): ?>
          <div class="feature-item">
            <div class="feature-icon-box">
              <i class="<?php echo htmlspecialchars($ft['icon']); ?>"></i>
            </div>
            <div class="feature-text">
              <h5><?php echo htmlspecialchars($ft['title']); ?></h5>
              <p><?php echo htmlspecialchars($ft['desc']); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- OUR STORIES SECTION (Multi-Card Grid) -->
  <?php if (!empty($ourStories)): ?>
  <section style="background:#fff; padding:80px 0;">
    <div style="max-width:1200px; margin:0 auto; padding:0 20px;">
      <h2 style="font-size:28px; font-weight:700; color:#1a1a1a; margin-bottom:50px; font-family:'Poppins', sans-serif; text-transform:uppercase; letter-spacing:2px; text-align:center;">
        OUR STORY
      </h2>
      
      <!-- Single Story Content (New) -->
      <?php if (!empty($osTitle) || !empty($osDesc) || !empty($osImage)): ?>
      <div style="margin-bottom: 50px; text-align: center;">
          <?php if (!empty($osImage)): 
             $sImg = $osImage;
             if (strpos($sImg, 'http') === 0) {}
             elseif (strpos($sImg, '/') === 0) {}
             elseif (strpos($sImg, 'assets/') === 0) { $sImg = '/' . $sImg; }
             else { $sImg = '/assets/uploads/story/' . $sImg; }
          ?>
          <div style="margin-bottom:20px; border-radius:12px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto 30px auto;">
            <img src="<?= htmlspecialchars($sImg) ?>" alt="Our Story" style="width:100%; height:auto; display:block;">
          </div>
          <?php endif; ?>
          
          <?php if (!empty($osTitle)): ?>
          <h3 style="font-size:24px; font-weight:700; color:#1a1a1a; margin-bottom:15px;"><?= htmlspecialchars($osTitle) ?></h3>
          <?php endif; ?>
          
          <?php if (!empty($osDesc)): ?>
          <p style="font-size:16px; color:#555; line-height:1.8; max-width:800px; margin:0 auto;"><?= nl2br(htmlspecialchars($osDesc)) ?></p>
          <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="our-story-grid">
        <?php foreach ($ourStories as $story): ?>
          <div style="text-align:center;">
            <?php if (!empty($story['image'])): 
                $sImg = $story['image'];
                if (strpos($sImg, 'http') === 0) {
                    // URL
                } elseif (strpos($sImg, '/') === 0) {
                    // Absolute path
                } elseif (strpos($sImg, 'assets/') === 0) {
                    $sImg = '/' . $sImg;
                } else {
                    $sImg = '/assets/uploads/story/' . $sImg;
                }
            ?>
              <div style="margin-bottom:20px; border-radius:12px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,0.1); height:250px;">
                <img src="<?= htmlspecialchars($sImg) ?>" alt="<?= htmlspecialchars($story['title']) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">

              </div>
            <?php endif; ?>
            
            <h3 style="font-size:16px; font-weight:700; color:#1a1a1a; margin-bottom:12px; text-transform:uppercase; letter-spacing:1px;">
              <?= htmlspecialchars($story['title']) ?>
            </h3>
            
            <p style="font-size:14px; color:#666; line-height:1.6; margin:0;">
              <?= htmlspecialchars($story['description']) ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

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

  // HOMEPAGE ADD TO CART LOGIC
  document.addEventListener('DOMContentLoaded', function() {
    const miniBtns = document.querySelectorAll('.btn-add-cart-mini');
    
    miniBtns.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent any parent link action if structure changes
        e.stopPropagation(); // Stop bubbling
        
        const productId = this.getAttribute('data-product-id');
        const originalHtml = this.innerHTML;
        const originalBg = this.style.background;
        
        // Show loading state
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';
        this.style.pointerEvents = 'none';
        
        fetch('ajax_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&product_id=${productId}&quantity=1`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Success state
                this.innerHTML = '<i class="fa-solid fa-check"></i> Added';
                this.style.background = '#27ae60'; // Green
                
                // Update Cart Count in Header (desktop + mobile)
                // Desktop: .cart-count, Mobile: .mobile-cart-count
                const desktopCount = document.querySelector('.cart-count');
                const mobileCount = document.querySelector('.mobile-cart-count');
                const mobileNavCount = document.querySelector('.mobile-bottom-nav .count'); // adjust selector if needed

                if (desktopCount) desktopCount.textContent = data.count;
                if (mobileCount) mobileCount.textContent = data.count;
                if (mobileNavCount) mobileNavCount.textContent = `(${data.count})`; // logic might vary

                // Revert button after 2s
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.style.background = originalBg; // Restore original gradient/color
                    this.style.pointerEvents = 'auto';
                }, 2000);
            } else {
                // Error (e.g. login required)
                if (data.message && data.message.toLowerCase().includes('login')) {
                     window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                } else {
                     alert(data.message || 'Failed to add to cart');
                     this.innerHTML = originalHtml;
                     this.style.pointerEvents = 'auto';
                }
            }
        })
        .catch(err => {
            console.error(err);
            this.innerHTML = originalHtml;
            this.style.pointerEvents = 'auto';
        });
      });
    });
  });
</script>

</body>
</html<!-- CUSTOMER REVIEWS SECTION -->
  <?php if (!empty($homeReviews)): ?>
  <section class="section-reviews" style="background:linear-gradient(to bottom, #ffffff, #f9fafb); padding:70px 0;">
    <div class="container">
      <!-- Enhanced Section Header -->
      <div style="text-align:center; margin-bottom:50px;">
        <h2 style="font-size:32px; font-weight:700; color:#1a1a1a; margin-bottom:12px; font-family:'Poppins', sans-serif;">
          What Our Customers Say
        </h2>
        <p style="font-size:15px; color:#666; margin:0;">Real reviews from verified purchases</p>
      </div>
      
      <div class="reviews-scroll-container">
        <?php foreach ($homeReviews as $review): 
            $prodImg = get_first_image($review['product_images']);
        ?>
          <div class="home-review-card">
            <div class="quote-icon"><i class="fa-solid fa-quote-left"></i></div>
            
            <div class="review-stars">
              <?= str_repeat('<i class="fa-solid fa-star"></i>', (int)$review['rating']) ?>
              <?= str_repeat('<i class="fa-regular fa-star"></i>', 5 - (int)$review['rating']) ?>
            </div>
            
            <p class="review-text">
              "<?= htmlspecialchars(mb_strimwidth($review['comment'], 0, 140, '...')) ?>"
            </p>
            
            <div class="review-meta">
              <a href="product_view.php?id=<?= $review['product_id'] ?>" class="review-prod-img">
                <img src="<?= htmlspecialchars($prodImg) ?>" alt="Product">
              </a>
              <div class="review-info">
                <div class="reviewer-name">
                  <?= htmlspecialchars($review['reviewer_name'] ?: 'Verified Customer') ?>
                  <i class="fa-solid fa-circle-check" style="color:#2ECC71; margin-left:4px; font-size:12px;"></i>
                </div>
                <div class="reviewed-product">
                  Verified Purchase • <a href="product_view.php?id=<?= $review['product_id'] ?>"><?= htmlspecialchars($review['product_name']) ?></a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <style>
      .section-reviews {
          padding: 60px 0; /* Reduced padding to match others */
          background: linear-gradient(to bottom, #ffffff, #fcfcfc);
      }
      
      /* Removed custom .center-header styles to use global .section-header */

      .reviews-scroll-container {
          display: flex;
          overflow-x: auto;
          gap: 25px;
          padding: 20px 10px 40px;
          scroll-snap-type: x mandatory;
          -webkit-overflow-scrolling: touch;
          scrollbar-width: none; 
      }
      .reviews-scroll-container::-webkit-scrollbar {
          display: none;
      }

      .home-review-card {
          background: #fff;
          padding: 30px; /* Slightly reduced */
          border-radius: 16px;
          box-shadow: 0 4px 20px rgba(0,0,0,0.05); /* Softer shadow */
          flex: 0 0 350px; /* Back to 350px */
          scroll-snap-align: start; /* Standard snap */
          position: relative;
          transition: transform 0.3s ease, box-shadow 0.3s ease;
          border: 1px solid rgba(0,0,0,0.03);
          display: flex;
          flex-direction: column;
      }
      .home-review-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 10px 30px rgba(0,0,0,0.08); /* Consistent hover */
          border-color: #A41B42;
      }

      .quote-icon {
          position: absolute;
          top: 20px;
          right: 25px;
          font-size: 32px; /* Smaller icon */
          color: #f2f2f2;
          z-index: 0;
      }

      .review-stars {
          color: #ffb400; /* Standard gold */
          font-size: 14px;
          margin-bottom: 15px;
          position: relative;
          z-index: 1;
      }
      .review-stars i { margin-right: 2px; }

      .review-text {
          font-size: 15px; /* Standard text size */
          line-height: 1.7;
          color: #555;
          margin-bottom: 25px;
          flex-grow: 1;
          font-style: italic;
          position: relative;
          z-index: 1;
      }

      .review-meta {
          display: flex;
          align-items: center;
          gap: 15px;
          padding-top: 20px;
          border-top: 1px solid #f5f5f5;
      }

      .review-prod-img {
          width: 50px;
          height: 50px;
          border-radius: 50%;
          overflow: hidden;
          border: 1px solid #eee;
          box-shadow: none; /* Cleaner */
      }
      .review-prod-img img {
          width: 100%;
          height: 100%;
          object-fit: cover;
      }

      .review-info {
          display: flex;
          flex-direction: column;
      }
      .reviewer-name {
          font-weight: 700;
          color: #333;
          font-size: 15px;
          display: flex;
          align-items: center;
      }
      .reviewed-product {
          font-size: 12px;
          color: #888;
          margin-top: 2px;
      }
      .reviewed-product a {
          color: #A41B42;
          text-decoration: none;
          font-weight: 600;
          transition: color 0.2s;
      }
      .reviewed-product a:hover {
          text-decoration: underline;
      }

      /* Responsive */
      @media (max-width: 768px) {
          .section-reviews { padding: 40px 0; }
          .reviews-scroll-container { gap: 15px; padding: 10px 15px 20px; }
          .home-review-card { flex: 0 0 300px; padding: 20px; }
          .quote-icon { font-size: 24px; top: 15px; right: 15px; }
          .review-text { font-size: 14px; margin-bottom: 20px; }
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
         <!-- HOMEPAGE VIDEO/MEDIA SECTION -->
            <?php
            // Settings fetched at top of file
            
            // Only show if URL is present
            if (!empty($hvUrl)): 
            ?>
            <div class="home-video-section">
                <div>
                    <!-- Left: Media -->
                    <div>
                         <?php 
                            $ext = strtolower(pathinfo($hvUrl, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
                            
                            if (strpos($hvUrl, 'youtube') !== false || strpos($hvUrl, 'youtu.be') !== false): 
                             // YouTube
                             $embedUrl = $hvUrl;
                             if (strpos($hvUrl, 'watch?v=') !== false) {
                                 $embedUrl = str_replace('watch?v=', 'embed/', $hvUrl);
                                 $embedUrl = explode('&', $embedUrl)[0];
                             } elseif (strpos($hvUrl, 'youtu.be/') !== false) {
                                 $embedUrl = str_replace('youtu.be/', 'www.youtube.com/embed/', $hvUrl);
                             }
                         ?>
                            <iframe src="<?= htmlspecialchars($embedUrl) ?>" allowfullscreen></iframe>
                         
                         <?php elseif ($isImage): ?>
                            <!-- Image -->
                            <img src="<?= htmlspecialchars($hvUrl) ?>" alt="Section Media">
                         
                         <?php else: ?>
                            <!-- HTML5 Video / Default -->
                            <video src="<?= htmlspecialchars($hvUrl) ?>" controls></video>
                         <?php endif; ?>
                    </div>
                    
                    <!-- Right: Text -->
                    <div>
                        <?php if($hvTitle): ?>
                            <h3 style="font-size: 28px; font-weight: 800; margin-bottom: 20px; color: #1a1a1a; font-family: 'Poppins', sans-serif;"><?= htmlspecialchars($hvTitle) ?></h3>
                        <?php endif; ?>
                        
                        <?php if($hvDesc): ?>
                            <div style="font-size: 16px; line-height: 1.8; color: #555; margin-bottom: 30px;" class="prose max-w-none">
                                <?= html_entity_decode($hvDesc) // Allow HTML from CKEditor ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($hvBtnText && $hvBtnLink): ?>
                            <div>
                                <a href="<?= htmlspecialchars($hvBtnLink) ?>" style="display: inline-block; background: <?= htmlspecialchars($hvBtnColor) ?>; color: #fff; padding: 14px 35px; border-radius: 999px; font-weight: 600; text-transform: uppercase; font-size: 14px; letter-spacing: 0.5px; transition: opacity 0.2s; text-decoration: none;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                    <?= htmlspecialchars($hvBtnText) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- WIDE BANNER (BEFORE BLOGS) - CAROUSEL -->
            <?php if (!empty($homeBeforeBlogsBanners)): ?>
            <div class="wide-banner" style="margin-top: 60px; margin-bottom: 40px;">
                <div class="wide-slider" id="blogs-slider" style="position:relative; width:100%; height:100%; overflow:hidden; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeBeforeBlogsBanners as $b): ?>
                      <?php
                        $src  = '/assets/uploads/banners/' . ltrim($b['filename'], '/');
                        $alt  = $b['alt_text'] ?? '';
                        $link = trim($b['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($link): ?>
                          <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($link): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeBeforeBlogsBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeBeforeBlogsBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <!-- Re-use the slider script logic or attach new one -->
                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const sliderId = '#blogs-slider';
                    const container = document.querySelector(sliderId);
                    if(!container) return;

                    const track = container.querySelector('.wide-slider-track');
                    const slides = container.querySelectorAll('.wide-slide');
                    const dots = container.querySelectorAll('.wide-dot');
                    const prevBtn = container.querySelector('.wide-prev');
                    const nextBtn = container.querySelector('.wide-next');
                    
                    if (!track || slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() { currentIndex = (currentIndex + 1) % slides.length; updateSlider(); }
                    function prevSlide() { currentIndex = (currentIndex - 1 + slides.length) % slides.length; updateSlider(); }
                    function startAutoSlide() { stopAutoSlide(); autoSlideInterval = setInterval(nextSlide, 3500); }
                    function stopAutoSlide() { if (autoSlideInterval) clearInterval(autoSlideInterval); }
                    
                    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); startAutoSlide(); });
                    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); startAutoSlide(); });
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => { currentIndex = idx; updateSlider(); startAutoSlide(); });
                    });
                    
                    startAutoSlide();
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

            <!-- MULTI COLUMN TABBED PRODUCTS -->
            <?php if ($visibleTabs > 0): ?>
            <div class="tabbed-products-section">
              <div class="tabs-bar">
                <?php if ($showLatest == '1'): ?><div class="tab-ribbon">Latest Products</div><?php endif; ?>
                <?php if ($showTrendy == '1'): ?><div class="tab-ribbon">Trendy Products</div><?php endif; ?>
                <?php if ($showSale == '1'): ?><div class="tab-ribbon">Sale Products</div><?php endif; ?>
                <?php if ($showTop == '1'): ?><div class="tab-ribbon">Top Rated</div><?php endif; ?>
              </div>

              <div class="tabbed-columns">
                <!-- Latest -->
                <?php if ($showLatest == '1'): ?>
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
                <?php endif; ?>

                <!-- Trendy -->
                <?php if ($showTrendy == '1'): ?>
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
                <?php endif; ?>

                <!-- Sale -->
                <?php if ($showSale == '1'): ?>
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
                <?php endif; ?>

                <!-- Top rated -->
                <?php if ($showTop == '1'): ?>
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
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

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

            </section>

          <!-- ALL OTHER CONTENT -->
          <section class="content-grid">
            <!-- Top banners -->
            <!-- Top banners -->
            <style>
              .banner-row {
                 gap: 20px !important; /* Increase image-to-image space */
              }
              .banner-row-container {
                 margin-bottom: 40px !important; /* Increase space to next section */
                 padding: 10px 0;
              }
              .banner-item {
                 /* Optional: Add shadow/border to pop */
                 /* border: 1px solid #eee; */
              }
            </style>
            <div class="banner-row-container">
              <div class="banner-row">
                <?php 
                if (!empty($bannerGroups)): 
                  // Duplicate for smooth marquee effect
                  $loopGroups = array_merge($bannerGroups, $bannerGroups, $bannerGroups);
                  foreach ($loopGroups as $bg): 
                    $bgImg = !empty($bg['image']) ? $bg['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path
                    if (strpos($bgImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($bgImg, '/') === 0) {
                        // is absolute
                    } else {
                        $bgImg = '/assets/uploads/groups/' . $bgImg;
                    }
                    
                    $url = 'product.php?group_id=' . (int)$bg['id'];
                ?>
                  <a class="banner-item" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="<?php echo htmlspecialchars($bgImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($bg['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="banner-caption">
                      <h4><?php echo htmlspecialchars($bg['name']); ?></h4>
                    </div>
                  </a>
                <?php endforeach; ?>
                <?php else: ?>
                   <p class="text-center p-4 text-gray-400">No collections found</p>
                <?php endif; ?>
              </div>
            </div>

            <!-- TOP LEVEL CATEGORIES GRID (VISUAL) -->
            <?php if (!empty($categories)): ?>
            <div class="top-categories-section">
              <div class="section-header">
                <h3>Shop by Category</h3>
              </div>
              
              <!-- AUTO SCROLL CONTAINER (3 Items Visible) -->
              <style>
                #categoryAutoScroll::-webkit-scrollbar {
                  display: none;
                }
                #categoryAutoScroll {
                  -ms-overflow-style: none;
                  scrollbar-width: none;
                }
                .cat-slide-item {
                  /* Default Desktop: Gap 25px, 3 items */
                  flex: 0 0 calc(33.333% - 17px);
                }
                @media (max-width: 768px) {
                  #categoryAutoScroll {
                    gap: 20px !important; /* Increased mobile gap */
                  }
                  .cat-slide-item {
                    /* Mobile: Show 2 Items. 
                       Formula: (100% - 20px) / 2 = 50% - 10px */
                    flex: 0 0 calc(50% - 10px) !important;
                  }
                  .cat-slide-item h4 {
                    font-size: 13px !important; /* Slightly larger text for bigger images */
                  }
                }
              </style>
              
              <div id="categoryAutoScroll" style="display: flex; gap: 25px; overflow-x: auto; white-space: nowrap; padding: 10px 0; -webkit-overflow-scrolling: touch; cursor: pointer;">
                <?php 
                $catDebug = [];
                // Duplicate categories to ensure scrolling if few items
                $displayCats = $categories;
                if (count($displayCats) < 6) {
                    $displayCats = array_merge($displayCats, $categories); // duplicate once
                }

                foreach ($displayCats as $cat): 
                    // Use 'image' column directly
                    $rawImg = !empty($cat['image']) ? $cat['image'] : ($cat['img'] ?? '');
                    $catImg = trim($rawImg);
                    
                    if ($catImg === '') {
                        $catImg = '/assets/images/category-placeholder.jpg';
                    } else {
                        // Parse Image Path
                        if (strpos($catImg, 'http') === 0) {
                        } elseif (strpos($catImg, '/') === 0) {
                        } elseif (strpos($catImg, 'assets/') === 0) {
                            $catImg = '/' . $catImg;
                        } else {
                            $catImg = '/assets/uploads/categories/' . $catImg;
                        }
                    }
                ?>
                  <a href="product.php?category[]=<?php echo urlencode($cat['title']); ?>" class="cat-slide-item" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <!-- Full Width Image Container (Square) -->
                    <div style="width: 100%; aspect-ratio: 1/1; border-radius: 16px; overflow: hidden; border: 2px solid #eee; box-shadow: 0 6px 15px rgba(0,0,0,0.08); background: #fff; position: relative;">
                      <img src="<?php echo htmlspecialchars($catImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($cat['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #333; line-height: 1.3; max-width: 100%; text-align:center; white-space: normal;">
                      <?php echo htmlspecialchars($cat['title']); ?>
                    </h4>
                  </a>
                <?php endforeach; ?>
              </div>

              <!-- AUTO SCROLL SCRIPT -->
              <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const scrollContainer = document.getElementById("categoryAutoScroll");
                    let scrollSpeed = 1.0; // Slightly faster to handle sub-pixel issues
                    let isHovered = false;

                    function autoScroll() {
                        if (!isHovered && scrollContainer) {
                            // Increment scroll
                            scrollContainer.scrollLeft += scrollSpeed;
                            
                            // Check if reached end
                            // Tolerance of 1px
                            if (scrollContainer.scrollLeft >= (scrollContainer.scrollWidth - scrollContainer.clientWidth - 1)) {
                                scrollContainer.scrollLeft = 0; // Reset to start
                            }
                        }
                        requestAnimationFrame(autoScroll);
                    }
                    
                    if(scrollContainer) {
                        // Remove potential CSS conflict
                        scrollContainer.style.scrollBehavior = 'auto'; 
                        
                        // Pause on hover
                        scrollContainer.addEventListener("mouseenter", () => isHovered = true);
                        scrollContainer.addEventListener("mouseleave", () => isHovered = false);
                        
                        // Start loop
                        requestAnimationFrame(autoScroll);
                    }
                });
              </script>

            </div>
            <?php endif; ?>

            <!-- SHOP BY CONCERN -->
            <?php if (!empty($concerns)): ?>
            <div class="top-concerns-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Concern</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #concernAutoScroll::-webkit-scrollbar { display: none; }
                #concernAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="concernAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($concerns as $c): 
                    $cImg = !empty($c['image']) ? $c['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($cImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($cImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($cImg, 'assets/') === 0) {
                        $cImg = '/' . $cImg;
                    } else {
                        $cImg = '/assets/uploads/concerns/' . $cImg;
                    }
                ?>
                  <a href="product.php?concern=<?php echo urlencode($c['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($cImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($c['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>


            <!-- New Herbal Products (latest products from DB) -->
            <div class="section-header">
              <h3>New Herbal Products</h3>
              <a href="product.php?section=new_herbal&sort=latest" class="btn-see-more">
                See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

            <div class="product-grid">
              <?php if (!empty($newProducts)): ?>
                <?php foreach ($newProducts as $p): ?>
                  <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                  <div class="product-card" style="position:relative;">
                    <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                    <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                      <i class="fa-solid fa-cart-plus"></i> Add
                    </button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="font-size:12px; color:#777;">No products available yet.</p>
              <?php endif; ?>
            </div>
            


            <!-- Wide banner (NOW CAROUSEL) -->
            <?php if (!empty($homeCenterBanners)): ?>
            <div class="wide-banner">
                <div class="wide-slider" style="position:relative; width:100%; height:100%; overflow:hidden;">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeCenterBanners as $centerBanner): ?>
                      <?php
                        $rawFn = trim($centerBanner['filename'] ?? '');
                        if (empty($rawFn)) continue; // skip empty
                        
                        $centerSrc  = '/assets/uploads/banners/' . ltrim($rawFn, '/');
                        $centerAlt  = $centerBanner['alt_text'] ?? '';
                        $centerLink = trim($centerBanner['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($centerLink): ?>
                          <a href="<?php echo htmlspecialchars($centerLink, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($centerSrc, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($centerAlt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($centerLink): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeCenterBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeCenterBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <!-- Arrows (optional but good for UX) -->
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const track = document.querySelector('.wide-slider-track');
                    const slides = document.querySelectorAll('.wide-slide');
                    const dots = document.querySelectorAll('.wide-dot');
                    const prevBtn = document.querySelector('.wide-prev');
                    const nextBtn = document.querySelector('.wide-next');
                    
                    if (!track) return;
                    if (slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      
                      // Update dots
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() {
                      currentIndex = (currentIndex + 1) % slides.length;
                      updateSlider();
                    }
                    
                    function prevSlide() {
                      currentIndex = (currentIndex - 1 + slides.length) % slides.length;
                      updateSlider();
                    }
                    
                    function startAutoSlide() {
                      stopAutoSlide();
                      autoSlideInterval = setInterval(nextSlide, 3000);
                    }
                    
                    function stopAutoSlide() {
                      if (autoSlideInterval) clearInterval(autoSlideInterval);
                    }
                    
                    // Event Listeners
                    if (nextBtn) nextBtn.addEventListener('click', () => {
                      nextSlide();
                      startAutoSlide();
                    });
                    
                    if (prevBtn) prevBtn.addEventListener('click', () => {
                      prevSlide();
                      startAutoSlide();
                    });
                    
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => {
                        currentIndex = idx;
                        updateSlider();
                        startAutoSlide();
                      });
                    });
                    
                    // Start
                    startAutoSlide();
                    
                    // Initial dot style
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

            <!-- SHOP BY SEASONAL -->
            <?php if (!empty($seasonals)): ?>
            <div class="top-seasonals-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Seasonal</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #seasonalAutoScroll::-webkit-scrollbar { display: none; }
                #seasonalAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="seasonalAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($seasonals as $s): 
                    $sImg = !empty($s['image']) ? $s['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($sImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($sImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($sImg, 'assets/') === 0) {
                        $sImg = '/' . $sImg;
                    } else {
                        $sImg = '/assets/uploads/seasonals/' . $sImg;
                    }
                ?>
                  <a href="product.php?seasonal=<?php echo urlencode($s['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($sImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($s['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Best Sellers (from homepage_products -> best_seller) -->
            <div style="margin-top:30px;">
            <div class="section-header">
              <h3>Best Sellers</h3>
              <a href="product.php?section=best_seller&sort=popularity" class="btn-see-more">
                 See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

              <div class="product-grid">
                <?php if (!empty($bestProducts)): ?>
                  <?php foreach ($bestProducts as $p): ?>
                    <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                    <div class="product-card" style="position:relative;">
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                      <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                        <i class="fa-solid fa-cart-plus"></i> Add
                      </button>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:12px; color:#777;">No best sellers available yet.</p>
                <?php endif; ?>
              </div>
            </div>
              



            <!-- HOMEPAGE VIDEO/MEDIA SECTION -->
            <?php
            // Settings fetched at top of file
            
            // Only show if URL is present
            if (!empty($hvUrl)): 
            ?>
            <div class="home-video-section">
                <div>
                    <!-- Left: Media -->
                    <div>
                         <?php 
                            $ext = strtolower(pathinfo($hvUrl, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
                            
                            if (strpos($hvUrl, 'youtube') !== false || strpos($hvUrl, 'youtu.be') !== false): 
                             // YouTube
                             $embedUrl = $hvUrl;
                             if (strpos($hvUrl, 'watch?v=') !== false) {
                                 $embedUrl = str_replace('watch?v=', 'embed/', $hvUrl);
                                 $embedUrl = explode('&', $embedUrl)[0];
                             } elseif (strpos($hvUrl, 'youtu.be/') !== false) {
                                 $embedUrl = str_replace('youtu.be/', 'www.youtube.com/embed/', $hvUrl);
                             }
                         ?>
                            <iframe src="<?= htmlspecialchars($embedUrl) ?>" allowfullscreen></iframe>
                         
                         <?php elseif ($isImage): ?>
                            <!-- Image -->
                            <img src="<?= htmlspecialchars($hvUrl) ?>" alt="Section Media">
                         
                         <?php else: ?>
                            <!-- HTML5 Video / Default -->
                            <video src="<?= htmlspecialchars($hvUrl) ?>" controls></video>
                         <?php endif; ?>
                    </div>
                    
                    <!-- Right: Text -->
                    <div>
                        <?php if($hvTitle): ?>
                            <h3 style="font-size: 28px; font-weight: 800; margin-bottom: 20px; color: #1a1a1a; font-family: 'Poppins', sans-serif;"><?= htmlspecialchars($hvTitle) ?></h3>
                        <?php endif; ?>
                        
                        <?php if($hvDesc): ?>
                            <div style="font-size: 16px; line-height: 1.8; color: #555; margin-bottom: 30px;" class="prose max-w-none">
                                <?= html_entity_decode($hvDesc) // Allow HTML from CKEditor ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($hvBtnText && $hvBtnLink): ?>
                            <div>
                                <a href="<?= htmlspecialchars($hvBtnLink) ?>" style="display: inline-block; background: <?= htmlspecialchars($hvBtnColor) ?>; color: #fff; padding: 14px 35px; border-radius: 999px; font-weight: 600; text-transform: uppercase; font-size: 14px; letter-spacing: 0.5px; transition: opacity 0.2s; text-decoration: none;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                    <?= htmlspecialchars($hvBtnText) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- WIDE BANNER (BEFORE BLOGS) - CAROUSEL -->
            <?php if (!empty($homeBeforeBlogsBanners)): ?>
            <div class="wide-banner" style="margin-top: 60px; margin-bottom: 40px;">
                <div class="wide-slider" id="blogs-slider" style="position:relative; width:100%; height:100%; overflow:hidden; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeBeforeBlogsBanners as $b): ?>
                      <?php
                        $src  = '/assets/uploads/banners/' . ltrim($b['filename'], '/');
                        $alt  = $b['alt_text'] ?? '';
                        $link = trim($b['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($link): ?>
                          <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($link): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeBeforeBlogsBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeBeforeBlogsBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <!-- Re-use the slider script logic or attach new one -->
                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const sliderId = '#blogs-slider';
                    const container = document.querySelector(sliderId);
                    if(!container) return;

                    const track = container.querySelector('.wide-slider-track');
                    const slides = container.querySelectorAll('.wide-slide');
                    const dots = container.querySelectorAll('.wide-dot');
                    const prevBtn = container.querySelector('.wide-prev');
                    const nextBtn = container.querySelector('.wide-next');
                    
                    if (!track || slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() { currentIndex = (currentIndex + 1) % slides.length; updateSlider(); }
                    <!-- MULTI COLUMN TABBED PRODUCTS -->
            <?php if ($visibleTabs > 0): ?>
            <div class="tabbed-products-section">
              <div class="tabs-bar">
                <?php if ($showLatest == '1'): ?><div class="tab-ribbon">Latest Products</div><?php endif; ?>
                <?php if ($showTrendy == '1'): ?><div class="tab-ribbon">Trendy Products</div><?php endif; ?>
                <?php if ($showSale == '1'): ?><div class="tab-ribbon">Sale Products</div><?php endif; ?>
                <?php if ($showTop == '1'): ?><div class="tab-ribbon">Top Rated</div><?php endif; ?>
              </div>

              <div class="tabbed-columns">
                <!-- Latest -->
                <?php if ($showLatest == '1'): ?>
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
                <?php endif; ?>

                <!-- Trendy -->
                <?php if ($showTrendy == '1'): ?>
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
                <?php endif; ?>

                <!-- Sale -->
                <?php if ($showSale == '1'): ?>
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
                <?php endif; ?>

                <!-- Top rated -->
                <?php if ($showTop == '1'): ?>
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
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

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

            <!-- LATEST BLOG POSTS CARD -->
          <?php if (!empty($latestBlogs)): ?>
          <div class="card">
            <div class="card-header">
              <div class="label">
                <i class="fa-solid fa-newspaper"></i>
                <span>📰 Latest Articles</span>
              </div>
            </div>
            <div class="card-body">
              <?php foreach (array_slice($latestBlogs, 0, 2) as $blog): 
                $blogImg = !empty($blog['featured_image']) ? htmlspecialchars($blog['featured_image']) : 'assets/images/category-placeholder.jpg';
              ?>
                <a href="blog-detail.php?slug=<?= urlencode($blog['slug']) ?>" class="blog-mini" style="text-decoration:none; color:inherit; display:flex; padding:10px 0; border-bottom:1px solid #f0f0f0; transition:all 0.2s;">
                  <img src="<?= $blogImg ?>" alt="Blog" style="width:60px; height:60px; object-fit:cover; border-radius:8px; margin-right:12px; flex-shrink:0;">
                  <div style="flex:1; min-width:0;">
                    <div style="font-size:12px; font-weight:600; color:#333; margin-bottom:4px; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                      <?= htmlspecialchars($blog['title']) ?>
                    </div>
                    <div style="font-size:10px; color:#999;">
                      <i class="fa-regular fa-calendar" style="margin-right:4px;"></i>
                      <?= date('M d, Y', strtotime($blog['created_at'])) ?>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
              <a href="blogs.php" style="display:block; text-align:center; padding:12px; margin-top:8px; font-size:12px; font-weight:600; color:#2ECC71; text-decoration:none;">
                Read All Articles →
              </a>
            </div>
          </div>
          <?php endif; ?>

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
            <!-- Top banners -->
            <style>
              .banner-row {
                 gap: 20px !important; /* Increase image-to-image space */
              }
              .banner-row-container {
                 margin-bottom: 40px !important; /* Increase space to next section */
                 padding: 10px 0;
              }
              .banner-item {
                 /* Optional: Add shadow/border to pop */
                 /* border: 1px solid #eee; */
              }
            </style>
            <div class="banner-row-container">
              <div class="banner-row">
                <?php 
                if (!empty($bannerGroups)): 
                  // Duplicate for smooth marquee effect
                  $loopGroups = array_merge($bannerGroups, $bannerGroups, $bannerGroups);
                  foreach ($loopGroups as $bg): 
                    $bgImg = !empty($bg['image']) ? $bg['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path
                    if (strpos($bgImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($bgImg, '/') === 0) {
                        // is absolute
                    } else {
                        $bgImg = '/assets/uploads/groups/' . $bgImg;
                    }
                    
                    $url = 'product.php?group_id=' . (int)$bg['id'];
                ?>
                  <a class="banner-item" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="<?php echo htmlspecialchars($bgImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($bg['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="banner-caption">
                      <h4><?php echo htmlspecialchars($bg['name']); ?></h4>
                    </div>
                  </a>
                <?php endforeach; ?>
                <?php else: ?>
                   <p class="text-center p-4 text-gray-400">No collections found</p>
                <?php endif; ?>
              </div>
            </div>

            <!-- TOP LEVEL CATEGORIES GRID (VISUAL) -->
            <?php if (!empty($categories)): ?>
            <div class="top-categories-section">
              <div class="section-header">
                <h3>Shop by Category</h3>
              </div>
              
              <!-- AUTO SCROLL CONTAINER (3 Items Visible) -->
              <style>
                #categoryAutoScroll::-webkit-scrollbar {
                  display: none;
                }
                #categoryAutoScroll {
                  -ms-overflow-style: none;
                  scrollbar-width: none;
                }
                .cat-slide-item {
                  /* Default Desktop: Gap 25px, 3 items */
                  flex: 0 0 calc(33.333% - 17px);
                }
                @media (max-width: 768px) {
                  #categoryAutoScroll {
                    gap: 20px !important; /* Increased mobile gap */
                  }
                  .cat-slide-item {
                    /* Mobile: Show 2 Items. 
                       Formula: (100% - 20px) / 2 = 50% - 10px */
                    flex: 0 0 calc(50% - 10px) !important;
                  }
                  .cat-slide-item h4 {
                    font-size: 13px !important; /* Slightly larger text for bigger images */
                  }
                }
              </style>
              
              <div id="categoryAutoScroll" style="display: flex; gap: 25px; overflow-x: auto; white-space: nowrap; padding: 10px 0; -webkit-overflow-scrolling: touch; cursor: pointer;">
                <?php 
                $catDebug = [];
                // Duplicate categories to ensure scrolling if few items
                $displayCats = $categories;
                if (count($displayCats) < 6) {
                    $displayCats = array_merge($displayCats, $categories); // duplicate once
                }

                foreach ($displayCats as $cat): 
                    // Use 'image' column directly
                    $rawImg = !empty($cat['image']) ? $cat['image'] : ($cat['img'] ?? '');
                    $catImg = trim($rawImg);
                    
                    if ($catImg === '') {
                        $catImg = '/assets/images/category-placeholder.jpg';
                    } else {
                        // Parse Image Path
                        if (strpos($catImg, 'http') === 0) {
                        } elseif (strpos($catImg, '/') === 0) {
                        } elseif (strpos($catImg, 'assets/') === 0) {
                            $catImg = '/' . $catImg;
                        } else {
                            $catImg = '/assets/uploads/categories/' . $catImg;
                        }
                    }
                ?>
                  <a href="product.php?category[]=<?php echo urlencode($cat['title']); ?>" class="cat-slide-item" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <!-- Full Width Image Container (Square) -->
                    <div style="width: 100%; aspect-ratio: 1/1; border-radius: 16px; overflow: hidden; border: 2px solid #eee; box-shadow: 0 6px 15px rgba(0,0,0,0.08); background: #fff; position: relative;">
                      <img src="<?php echo htmlspecialchars($catImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($cat['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #333; line-height: 1.3; max-width: 100%; text-align:center; white-space: normal;">
                      <?php echo htmlspecialchars($cat['title']); ?>
                    </h4>
                  </a>
                <?php endforeach; ?>
              </div>

              <!-- AUTO SCROLL SCRIPT -->
              <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const scrollContainer = document.getElementById("categoryAutoScroll");
                    let scrollSpeed = 1.0; // Slightly faster to handle sub-pixel issues
                    let isHovered = false;

                    function autoScroll() {
                        if (!isHovered && scrollContainer) {
                            // Increment scroll
                            scrollContainer.scrollLeft += scrollSpeed;
                            
                            // Check if reached end
                            // Tolerance of 1px
                            if (scrollContainer.scrollLeft >= (scrollContainer.scrollWidth - scrollContainer.clientWidth - 1)) {
                                scrollContainer.scrollLeft = 0; // Reset to start
                            }
                        }
                        requestAnimationFrame(autoScroll);
                    }
                    
                    if(scrollContainer) {
                        // Remove potential CSS conflict
                        scrollContainer.style.scrollBehavior = 'auto'; 
                        
                        // Pause on hover
                        scrollContainer.addEventListener("mouseenter", () => isHovered = true);
                        scrollContainer.addEventListener("mouseleave", () => isHovered = false);
                        
                        // Start loop
                        requestAnimationFrame(autoScroll);
                    }
                });
              </script>

            </div>
            <?php endif; ?>

            <!-- New Herbal Products (latest products from DB) -->
            <div class="section-header">
              <h3>New Herbal Products</h3>
              <a href="product.php?section=new_herbal&sort=latest" class="btn-see-more">
                See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

            <div class="product-grid">
              <?php if (!empty($newProducts)): ?>
                <?php foreach ($newProducts as $p): ?>
                  <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                  <div class="product-card" style="position:relative;">
                    <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                    <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                      <i class="fa-solid fa-cart-plus"></i> Add
                    </button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="font-size:12px; color:#777;">No products available yet.</p>
              <?php endif; ?>
            </div>
            


            <!-- SHOP BY CONCERN -->
            <?php if (!empty($concerns)): ?>
            <div class="top-concerns-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Concern</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #concernAutoScroll::-webkit-scrollbar { display: none; }
                #concernAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="concernAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($concerns as $c): 
                    $cImg = !empty($c['image']) ? $c['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($cImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($cImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($cImg, 'assets/') === 0) {
                        $cImg = '/' . $cImg;
                    } else {
                        $cImg = '/assets/uploads/concerns/' . $cImg;
                    }
                ?>
                  <a href="product.php?concern=<?php echo urlencode($c['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($cImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($c['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>


            <!-- Wide banner (NOW CAROUSEL) -->
            <?php if (!empty($homeCenterBanners)): ?>
            <div class="wide-banner">
                <div class="wide-slider" style="position:relative; width:100%; height:100%; overflow:hidden;">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeCenterBanners as $centerBanner): ?>
                      <?php
                        $rawFn = trim($centerBanner['filename'] ?? '');
                        if (empty($rawFn)) continue; // skip empty
                        
                        $centerSrc  = '/assets/uploads/banners/' . ltrim($rawFn, '/');
                        $centerAlt  = $centerBanner['alt_text'] ?? '';
                        $centerLink = trim($centerBanner['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($centerLink): ?>
                          <a href="<?php echo htmlspecialchars($centerLink, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($centerSrc, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($centerAlt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($centerLink): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeCenterBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeCenterBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <!-- Arrows (optional but good for UX) -->
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const track = document.querySelector('.wide-slider-track');
                    const slides = document.querySelectorAll('.wide-slide');
                    const dots = document.querySelectorAll('.wide-dot');
                    const prevBtn = document.querySelector('.wide-prev');
                    const nextBtn = document.querySelector('.wide-next');
                    
                    if (!track) return;
                    if (slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      
                      // Update dots
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() {
                      currentIndex = (currentIndex + 1) % slides.length;
                      updateSlider();
                    }
                    
                    function prevSlide() {
                      currentIndex = (currentIndex - 1 + slides.length) % slides.length;
                      updateSlider();
                    }
                    
                    function startAutoSlide() {
                      stopAutoSlide();
                      autoSlideInterval = setInterval(nextSlide, 3000);
                    }
                    
                    function stopAutoSlide() {
                      if (autoSlideInterval) clearInterval(autoSlideInterval);
                    }
                    
                    // Event Listeners
                    if (nextBtn) nextBtn.addEventListener('click', () => {
                      nextSlide();
                      startAutoSlide();
                    });
                    
                    if (prevBtn) prevBtn.addEventListener('click', () => {
                      prevSlide();
                      startAutoSlide();
                    });
                    
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => {
                        currentIndex = idx;
                        updateSlider();
                        startAutoSlide();
                      });
                    });
                    
                    // Start
                    startAutoSlide();
                    
                    // Initial dot style
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

            <!-- Best Sellers (from homepage_products -> best_seller) -->
            <div style="margin-top:30px;">
            <div class="section-header">
              <h3>Best Sellers</h3>
              <a href="product.php?section=best_seller&sort=popularity" class="btn-see-more">
                 See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

              <div class="product-grid">
                <?php if (!empty($bestProducts)): ?>
                  <?php foreach ($bestProducts as $p): ?>
                    <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                    <div class="product-card" style="position:relative;">
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                      <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                        <i class="fa-solid fa-cart-plus"></i> Add
                      </button>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:12px; color:#777;">No best sellers available yet.</p>
                <?php endif; ?>
              </div>
            </div>
              



            <!-- SHOP BY SEASONAL -->
            <?php if (!empty($seasonals)): ?>
            <div class="top-seasonals-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Seasonal</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #seasonalAutoScroll::-webkit-scrollbar { display: none; }
                #seasonalAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="seasonalAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($seasonals as $s): 
                    $sImg = !empty($s['image']) ? $s['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($sImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($sImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($sImg, 'assets/') === 0) {
                        $sImg = '/' . $sImg;
                    } else {
                        $sImg = '/assets/uploads/seasonals/' . $sImg;
                    }
                ?>
                  <a href="product.php?seasonal=<?php echo urlencode($s['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($sImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($s['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- HOMEPAGE VIDEO/MEDIA SECTION -->
            <?php
            // Settings fetched at top of file
            
            // Only show if URL is present
            if (!empty($hvUrl)): 
            ?>
            <div class="home-video-section">
                <div>
                    <!-- Left: Media -->
                    <div>
                         <?php 
                            $ext = strtolower(pathinfo($hvUrl, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
                            
                            if (strpos($hvUrl, 'youtube') !== false || strpos($hvUrl, 'youtu.be') !== false): 
                             // YouTube
                             $embedUrl = $hvUrl;
                             if (strpos($hvUrl, 'watch?v=') !== false) {
                                 $embedUrl = str_replace('watch?v=', 'embed/', $hvUrl);
                                 $embedUrl = explode('&', $embedUrl)[0];
                             } elseif (strpos($hvUrl, 'youtu.be/') !== false) {
                                 $embedUrl = str_replace('youtu.be/', 'www.youtube.com/embed/', $hvUrl);
                             }
                         ?>
                            <iframe src="<?= htmlspecialchars($embedUrl) ?>" allowfullscreen></iframe>
                         
                         <?php elseif ($isImage): ?>
                            <!-- Image -->
                            <img src="<?= htmlspecialchars($hvUrl) ?>" alt="Section Media">
                         
                         <?php else: ?>
                            <!-- HTML5 Video / Default -->
                            <video src="<?= htmlspecialchars($hvUrl) ?>" controls></video>
                         <?php endif; ?>
                    </div>
                    
                    <!-- Right: Text -->
                    <div>
                        <?php if($hvTitle): ?>
                            <h3 style="font-size: 28px; font-weight: 800; margin-bottom: 20px; color: #1a1a1a; font-family: 'Poppins', sans-serif;"><?= htmlspecialchars($hvTitle) ?></h3>
                        <?php endif; ?>
                        
                        <?php if($hvDesc): ?>
                            <div style="font-size: 16px; line-height: 1.8; color: #555; margin-bottom: 30px;" class="prose max-w-none">
                                <?= html_entity_decode($hvDesc) // Allow HTML from CKEditor ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($hvBtnText && $hvBtnLink): ?>
                            <div>
                                <a href="<?= htmlspecialchars($hvBtnLink) ?>" style="display: inline-block; background: <?= htmlspecialchars($hvBtnColor) ?>; color: #fff; padding: 14px 35px; border-radius: 999px; font-weight: 600; text-transform: uppercase; font-size: 14px; letter-spacing: 0.5px; transition: opacity 0.2s; text-decoration: none;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                    <?= htmlspecialchars($hvBtnText) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- WIDE BANNER (BEFORE BLOGS) - CAROUSEL -->
            <?php if (!empty($homeBeforeBlogsBanners)): ?>
            <div class="wide-banner" style="margin-top: 60px; margin-bottom: 40px;">
                <div class="wide-slider" id="blogs-slider" style="position:relative; width:100%; height:100%; overflow:hidden; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeBeforeBlogsBanners as $b): ?>
                      <?php
                        $src  = '/assets/uploads/banners/' . ltrim($b['filename'], '/');
                        $alt  = $b['alt_text'] ?? '';
                        $link = trim($b['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($link): ?>
                          <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($link): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeBeforeBlogsBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeBeforeBlogsBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <!-- Re-use the slider script logic or attach new one -->
                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const sliderId = '#blogs-slider';
                    const container = document.querySelector(sliderId);
                    if(!container) return;

                    const track = container.querySelector('.wide-slider-track');
                    const slides = container.querySelectorAll('.wide-slide');
                    const dots = container.querySelectorAll('.wide-dot');
                    const prevBtn = container.querySelector('.wide-prev');
                    const nextBtn = container.querySelector('.wide-next');
                    
                    if (!track || slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() { currentIndex = (currentIndex + 1) % slides.length; updateSlider(); }
                    function prevSlide() { currentIndex = (currentIndex - 1 + slides.length) % slides.length; updateSlider(); }
                    function startAutoSlide() { stopAutoSlide(); autoSlideInterval = setInterval(nextSlide, 3500); }
                    function stopAutoSlide() { if (autoSlideInterval) clearInterval(autoSlideInterval); }
                    
                    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); startAutoSlide(); });
                    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); startAutoSlide(); });
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => { currentIndex = idx; updateSlider(); startAutoSlide(); });
                    });
                    
                    startAutoSlide();
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

            </section>

          <!-- ALL OTHER CONTENT -->
          <section class="content-grid">
            <!-- Top banners -->
            <!-- Top banners -->
            <style>
              .banner-row {
                 gap: 20px !important; /* Increase image-to-image space */
              }
              .banner-row-container {
                 margin-bottom: 40px !important; /* Increase space to next section */
                 padding: 10px 0;
              }
              .banner-item {
                 /* Optional: Add shadow/border to pop */
                 /* border: 1px solid #eee; */
              }
            </style>
            <div class="banner-row-container">
              <div class="banner-row">
                <?php 
                if (!empty($bannerGroups)): 
                  // Duplicate for smooth marquee effect
                  $loopGroups = array_merge($bannerGroups, $bannerGroups, $bannerGroups);
                  foreach ($loopGroups as $bg): 
                    $bgImg = !empty($bg['image']) ? $bg['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path
                    if (strpos($bgImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($bgImg, '/') === 0) {
                        // is absolute
                    } else {
                        $bgImg = '/assets/uploads/groups/' . $bgImg;
                    }
                    
                    $url = 'product.php?group_id=' . (int)$bg['id'];
                ?>
                  <a class="banner-item" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="<?php echo htmlspecialchars($bgImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($bg['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="banner-caption">
                      <h4><?php echo htmlspecialchars($bg['name']); ?></h4>
                    </div>
                  </a>
                <?php endforeach; ?>
                <?php else: ?>
                   <p class="text-center p-4 text-gray-400">No collections found</p>
                <?php endif; ?>
              </div>
            </div>

            <!-- TOP LEVEL CATEGORIES GRID (VISUAL) -->
            <?php if (!empty($categories)): ?>
            <div class="top-categories-section">
              <div class="section-header">
                <h3>Shop by Category</h3>
              </div>
              
              <!-- AUTO SCROLL CONTAINER (3 Items Visible) -->
              <style>
                #categoryAutoScroll::-webkit-scrollbar {
                  display: none;
                }
                #categoryAutoScroll {
                  -ms-overflow-style: none;
                  scrollbar-width: none;
                }
                .cat-slide-item {
                  /* Default Desktop: Gap 25px, 3 items */
                  flex: 0 0 calc(33.333% - 17px);
                }
                @media (max-width: 768px) {
                  #categoryAutoScroll {
                    gap: 20px !important; /* Increased mobile gap */
                  }
                  .cat-slide-item {
                    /* Mobile: Show 2 Items. 
                       Formula: (100% - 20px) / 2 = 50% - 10px */
                    flex: 0 0 calc(50% - 10px) !important;
                  }
                  .cat-slide-item h4 {
                    font-size: 13px !important; /* Slightly larger text for bigger images */
                  }
                }
              </style>
              
              <div id="categoryAutoScroll" style="display: flex; gap: 25px; overflow-x: auto; white-space: nowrap; padding: 10px 0; -webkit-overflow-scrolling: touch; cursor: pointer;">
                <?php 
                $catDebug = [];
                // Duplicate categories to ensure scrolling if few items
                $displayCats = $categories;
                if (count($displayCats) < 6) {
                    $displayCats = array_merge($displayCats, $categories); // duplicate once
                }

                foreach ($displayCats as $cat): 
                    // Use 'image' column directly
                    $rawImg = !empty($cat['image']) ? $cat['image'] : ($cat['img'] ?? '');
                    $catImg = trim($rawImg);
                    
                    if ($catImg === '') {
                        $catImg = '/assets/images/category-placeholder.jpg';
                    } else {
                        // Parse Image Path
                        if (strpos($catImg, 'http') === 0) {
                        } elseif (strpos($catImg, '/') === 0) {
                        } elseif (strpos($catImg, 'assets/') === 0) {
                            $catImg = '/' . $catImg;
                        } else {
                            $catImg = '/assets/uploads/categories/' . $catImg;
                        }
                    }
                ?>
                  <a href="product.php?category[]=<?php echo urlencode($cat['title']); ?>" class="cat-slide-item" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <!-- Full Width Image Container (Square) -->
                    <div style="width: 100%; aspect-ratio: 1/1; border-radius: 16px; overflow: hidden; border: 2px solid #eee; box-shadow: 0 6px 15px rgba(0,0,0,0.08); background: #fff; position: relative;">
                      <img src="<?php echo htmlspecialchars($catImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($cat['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #333; line-height: 1.3; max-width: 100%; text-align:center; white-space: normal;">
                      <?php echo htmlspecialchars($cat['title']); ?>
                    </h4>
                  </a>
                <?php endforeach; ?>
              </div>

              <!-- AUTO SCROLL SCRIPT -->
              <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const scrollContainer = document.getElementById("categoryAutoScroll");
                    let scrollSpeed = 1.0; // Slightly faster to handle sub-pixel issues
                    let isHovered = false;

                    function autoScroll() {
                        if (!isHovered && scrollContainer) {
                            // Increment scroll
                            scrollContainer.scrollLeft += scrollSpeed;
                            
                            // Check if reached end
                            // Tolerance of 1px
                            if (scrollContainer.scrollLeft >= (scrollContainer.scrollWidth - scrollContainer.clientWidth - 1)) {
                                scrollContainer.scrollLeft = 0; // Reset to start
                            }
                        }
                        requestAnimationFrame(autoScroll);
                    }
                    
                    if(scrollContainer) {
                        // Remove potential CSS conflict
                        scrollContainer.style.scrollBehavior = 'auto'; 
                        
                        // Pause on hover
                        scrollContainer.addEventListener("mouseenter", () => isHovered = true);
                        scrollContainer.addEventListener("mouseleave", () => isHovered = false);
                        
                        // Start loop
                        requestAnimationFrame(autoScroll);
                    }
                });
              </script>

            </div>
            <?php endif; ?>

            <!-- SHOP BY CONCERN -->
            <?php if (!empty($concerns)): ?>
            <div class="top-concerns-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Concern</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #concernAutoScroll::-webkit-scrollbar { display: none; }
                #concernAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="concernAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($concerns as $c): 
                    $cImg = !empty($c['image']) ? $c['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($cImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($cImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($cImg, 'assets/') === 0) {
                        $cImg = '/' . $cImg;
                    } else {
                        $cImg = '/assets/uploads/concerns/' . $cImg;
                    }
                ?>
                  <a href="product.php?concern=<?php echo urlencode($c['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($cImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($c['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>


            <!-- New Herbal Products (latest products from DB) -->
            <div class="section-header">
              <h3>New Herbal Products</h3>
              <a href="product.php?section=new_herbal&sort=latest" class="btn-see-more">
                See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

            <div class="product-grid">
              <?php if (!empty($newProducts)): ?>
                <?php foreach ($newProducts as $p): ?>
                  <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                  <div class="product-card" style="position:relative;">
                    <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                    <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                      <i class="fa-solid fa-cart-plus"></i> Add
                    </button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="font-size:12px; color:#777;">No products available yet.</p>
              <?php endif; ?>
            </div>
            


            <!-- Wide banner (NOW CAROUSEL) -->
            <?php if (!empty($homeCenterBanners)): ?>
            <div class="wide-banner">
                <div class="wide-slider" style="position:relative; width:100%; height:100%; overflow:hidden;">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeCenterBanners as $centerBanner): ?>
                      <?php
                        $rawFn = trim($centerBanner['filename'] ?? '');
                        if (empty($rawFn)) continue; // skip empty
                        
                        $centerSrc  = '/assets/uploads/banners/' . ltrim($rawFn, '/');
                        $centerAlt  = $centerBanner['alt_text'] ?? '';
                        $centerLink = trim($centerBanner['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($centerLink): ?>
                          <a href="<?php echo htmlspecialchars($centerLink, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($centerSrc, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($centerAlt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($centerLink): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeCenterBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeCenterBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <!-- Arrows (optional but good for UX) -->
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const track = document.querySelector('.wide-slider-track');
                    const slides = document.querySelectorAll('.wide-slide');
                    const dots = document.querySelectorAll('.wide-dot');
                    const prevBtn = document.querySelector('.wide-prev');
                    const nextBtn = document.querySelector('.wide-next');
                    
                    if (!track) return;
                    if (slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      
                      // Update dots
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() {
                      currentIndex = (currentIndex + 1) % slides.length;
                      updateSlider();
                    }
                    
                    function prevSlide() {
                      currentIndex = (currentIndex - 1 + slides.length) % slides.length;
                      updateSlider();
                    }
                    
                    function startAutoSlide() {
                      stopAutoSlide();
                      autoSlideInterval = setInterval(nextSlide, 3000);
                    }
                    
                    function stopAutoSlide() {
                      if (autoSlideInterval) clearInterval(autoSlideInterval);
                    }
                    
                    // Event Listeners
                    if (nextBtn) nextBtn.addEventListener('click', () => {
                      nextSlide();
                      startAutoSlide();
                    });
                    
                    if (prevBtn) prevBtn.addEventListener('click', () => {
                      prevSlide();
                      startAutoSlide();
                    });
                    
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => {
                        currentIndex = idx;
                        updateSlider();
                        startAutoSlide();
                      });
                    });
                    
                    // Start
                    startAutoSlide();
                    
                    // Initial dot style
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

            <!-- SHOP BY SEASONAL -->
            <?php if (!empty($seasonals)): ?>
            <div class="top-seasonals-section" style="margin-top: 40px; margin-bottom: 20px;">
              <div class="section-header">
                <h3>Shop by Seasonal</h3>
              </div>
              
              <!-- GRID CONTAINER (Using same visual style as categories) -->
              <style>
                #seasonalAutoScroll::-webkit-scrollbar { display: none; }
                #seasonalAutoScroll { -ms-overflow-style: none; scrollbar-width: none; }
              </style>
              <div id="seasonalAutoScroll" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 4px; -webkit-overflow-scrolling: touch;">
                <?php foreach ($seasonals as $s): 
                    $sImg = !empty($s['image']) ? $s['image'] : '/assets/images/category-placeholder.jpg';
                    // Parse Image Path like categories
                     if (strpos($sImg, 'http') === 0) {
                        // is URL
                    } elseif (strpos($sImg, '/') === 0) {
                        // is absolute
                    } elseif (strpos($sImg, 'assets/') === 0) {
                        $sImg = '/' . $sImg;
                    } else {
                        $sImg = '/assets/uploads/seasonals/' . $sImg;
                    }
                ?>
                  <a href="product.php?seasonal=<?php echo urlencode($s['slug']); ?>" style="flex: 0 0 140px; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; position: relative;">
                         <img src="<?php echo htmlspecialchars($sImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #333; text-align:center; max-width: 100%; white-space: normal; line-height: 1.3;"><?php echo htmlspecialchars($s['title']); ?></h4>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Best Sellers (from homepage_products -> best_seller) -->
            <div style="margin-top:30px;">
            <div class="section-header">
              <h3>Best Sellers</h3>
              <a href="product.php?section=best_seller&sort=popularity" class="btn-see-more">
                 See More <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:11px;"></i>
              </a>
            </div>

              <div class="product-grid">
                <?php if (!empty($bestProducts)): ?>
                  <?php foreach ($bestProducts as $p): ?>
                    <?php $img = htmlspecialchars(get_first_image($p['images'] ?? '')); ?>
                    <div class="product-card" style="position:relative;">
                      <a href="product_view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
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
                      <button class="btn-add-cart-mini" data-product-id="<?php echo (int)$p['id']; ?>">
                        <i class="fa-solid fa-cart-plus"></i> Add
                      </button>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:12px; color:#777;">No best sellers available yet.</p>
                <?php endif; ?>
              </div>
            </div>
              



            <!-- HOMEPAGE VIDEO/MEDIA SECTION -->
            <?php
            // Settings fetched at top of file
            
            // Only show if URL is present
            if (!empty($hvUrl)): 
            ?>
            <div class="home-video-section">
                <div>
                    <!-- Left: Media -->
                    <div>
                         <?php 
                            $ext = strtolower(pathinfo($hvUrl, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
                            
                            if (strpos($hvUrl, 'youtube') !== false || strpos($hvUrl, 'youtu.be') !== false): 
                             // YouTube
                             $embedUrl = $hvUrl;
                             if (strpos($hvUrl, 'watch?v=') !== false) {
                                 $embedUrl = str_replace('watch?v=', 'embed/', $hvUrl);
                                 $embedUrl = explode('&', $embedUrl)[0];
                             } elseif (strpos($hvUrl, 'youtu.be/') !== false) {
                                 $embedUrl = str_replace('youtu.be/', 'www.youtube.com/embed/', $hvUrl);
                             }
                         ?>
                            <iframe src="<?= htmlspecialchars($embedUrl) ?>" allowfullscreen></iframe>
                         
                         <?php elseif ($isImage): ?>
                            <!-- Image -->
                            <img src="<?= htmlspecialchars($hvUrl) ?>" alt="Section Media">
                         
                         <?php else: ?>
                            <!-- HTML5 Video / Default -->
                            <video src="<?= htmlspecialchars($hvUrl) ?>" controls></video>
                         <?php endif; ?>
                    </div>
                    
                    <!-- Right: Text -->
                    <div>
                        <?php if($hvTitle): ?>
                            <h3 style="font-size: 28px; font-weight: 800; margin-bottom: 20px; color: #1a1a1a; font-family: 'Poppins', sans-serif;"><?= htmlspecialchars($hvTitle) ?></h3>
                        <?php endif; ?>
                        
                        <?php if($hvDesc): ?>
                            <div style="font-size: 16px; line-height: 1.8; color: #555; margin-bottom: 30px;" class="prose max-w-none">
                                <?= html_entity_decode($hvDesc) // Allow HTML from CKEditor ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($hvBtnText && $hvBtnLink): ?>
                            <div>
                                <a href="<?= htmlspecialchars($hvBtnLink) ?>" style="display: inline-block; background: <?= htmlspecialchars($hvBtnColor) ?>; color: #fff; padding: 14px 35px; border-radius: 999px; font-weight: 600; text-transform: uppercase; font-size: 14px; letter-spacing: 0.5px; transition: opacity 0.2s; text-decoration: none;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                    <?= htmlspecialchars($hvBtnText) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- WIDE BANNER (BEFORE BLOGS) - CAROUSEL -->
            <?php if (!empty($homeBeforeBlogsBanners)): ?>
            <div class="wide-banner" style="margin-top: 60px; margin-bottom: 40px;">
                <div class="wide-slider" id="blogs-slider" style="position:relative; width:100%; height:100%; overflow:hidden; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                  <div class="wide-slider-track" style="display:flex; transition:transform 0.5s ease-in-out; height:100%;">
                    <?php foreach ($homeBeforeBlogsBanners as $b): ?>
                      <?php
                        $src  = '/assets/uploads/banners/' . ltrim($b['filename'], '/');
                        $alt  = $b['alt_text'] ?? '';
                        $link = trim($b['link'] ?? '');
                      ?>
                      <div class="wide-slide" style="min-width:100%; height:100%; flex-shrink:0;">
                        <?php if ($link): ?>
                          <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>
                        
                        <img 
                          src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" 
                          alt="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>"
                          style="width:100%; height:100%; object-fit:cover;"
                        >
                        
                        <?php if ($link): ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if (count($homeBeforeBlogsBanners) > 1): ?>
                    <!-- Dots -->
                    <div class="wide-slider-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:8px;">
                      <?php foreach ($homeBeforeBlogsBanners as $idx => $cb): ?>
                        <button class="wide-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo $idx; ?>" style="width:10px; height:10px; border-radius:50%; border:none; background:rgba(255,255,255,0.5); cursor:pointer; padding:0;"></button>
                      <?php endforeach; ?>
                    </div>
                    
                    <button class="wide-prev" style="position:absolute; top:50%; left:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="wide-next" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); background:rgba(0,0,0,0.3); color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-chevron-right"></i></button>
                  <?php endif; ?>
                </div>

                <!-- Re-use the slider script logic or attach new one -->
                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const sliderId = '#blogs-slider';
                    const container = document.querySelector(sliderId);
                    if(!container) return;

                    const track = container.querySelector('.wide-slider-track');
                    const slides = container.querySelectorAll('.wide-slide');
                    const dots = container.querySelectorAll('.wide-dot');
                    const prevBtn = container.querySelector('.wide-prev');
                    const nextBtn = container.querySelector('.wide-next');
                    
                    if (!track || slides.length < 2) return;
                    
                    let currentIndex = 0;
                    let autoSlideInterval;
                    
                    function updateSlider() {
                      track.style.transform = `translateX(-${currentIndex * 100}%)`;
                      dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                          dot.style.background = 'white';
                          dot.classList.add('active');
                        } else {
                          dot.style.background = 'rgba(255,255,255,0.5)';
                          dot.classList.remove('active');
                        }
                      });
                    }
                    
                    function nextSlide() { currentIndex = (currentIndex + 1) % slides.length; updateSlider(); }
                    function prevSlide() { currentIndex = (currentIndex - 1 + slides.length) % slides.length; updateSlider(); }
                    function startAutoSlide() { stopAutoSlide(); autoSlideInterval = setInterval(nextSlide, 3500); }
                    function stopAutoSlide() { if (autoSlideInterval) clearInterval(autoSlideInterval); }
                    
                    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); startAutoSlide(); });
                    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); startAutoSlide(); });
                    dots.forEach((dot, idx) => {
                      dot.addEventListener('click', () => { currentIndex = idx; updateSlider(); startAutoSlide(); });
                    });
                    
                    startAutoSlide();
                    if(dots[0]) dots[0].style.background = 'white';
                  });
                </script>
            </div>
            <?php endif; ?>

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
            <?php if ($visibleTabs > 0): ?>
            <div class="tabbed-products-section">
              <div class="tabs-bar">
                <?php if ($showLatest == '1'): ?><div class="tab-ribbon">Latest Products</div><?php endif; ?>
                <?php if ($showTrendy == '1'): ?><div class="tab-ribbon">Trendy Products</div><?php endif; ?>
                <?php if ($showSale == '1'): ?><div class="tab-ribbon">Sale Products</div><?php endif; ?>
                <?php if ($showTop == '1'): ?><div class="tab-ribbon">Top Rated</div><?php endif; ?>
              </div>

              <div class="tabbed-columns">
                <!-- Latest -->
                <?php if ($showLatest == '1'): ?>
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
                <?php endif; ?>

                <!-- Trendy -->
                <?php if ($showTrendy == '1'): ?>
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
                <?php endif; ?>

                <!-- Sale -->
                <?php if ($showSale == '1'): ?>
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
                <?php endif; ?>

                <!-- Top rated -->
                <?php if ($showTop == '1'): ?>
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
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

          </section>
        </div>
      </div>

    </div>
    </div>
    
  </main>


  <!-- CERTIFICATION BADGES SECTION -->
  <section style="background:linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding:50px 0; position:relative; overflow:hidden;">
    <!-- Decorative background elements -->
    <div style="position:absolute; top:-50px; right:-50px; width:200px; height:200px; background:rgba(255,255,255,0.1); border-radius:50%;"></div>
    <div style="position:absolute; bottom:-30px; left:-30px; width:150px; height:150px; background:rgba(255,255,255,0.1); border-radius:50%;"></div>
    
    <div style="max-width:1200px; margin:0 auto; padding:0 20px; position:relative; z-index:1;">
      <h2 style="text-align:center; font-size:24px; font-weight:700; color:#2c3e50; margin-bottom:40px; font-family:'Poppins', sans-serif; display:flex; align-items:center; justify-content:center; gap:12px;">
        <i class="<?= htmlspecialchars($certIcon) ?>" style="color:#667eea; font-size:32px;"></i>
        <?= htmlspecialchars($certTitle) ?>
      </h2>
      
      <div class="cert-badges-grid">
        <?php foreach ($certBadges as $badge): ?>
          <div class="cert-badge">
            <div>
              <?php if (!empty($badge['image'])): ?>
                <img src="<?= htmlspecialchars($badge['image']) ?>" alt="<?= htmlspecialchars($badge['title']) ?>">
              <?php else: ?>
                <i class="fa-solid fa-award"></i>
              <?php endif; ?>
            </div>
            <p style="font-size:13px; font-weight:600; color:#34495e; margin:0;"><?= htmlspecialchars($badge['title']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <style>
      .cert-badge:hover {
        transform: translateY(-10px);
      }
      .cert-badge:hover > div {
        box-shadow: 0 12px 35px rgba(0,0,0,0.2) !important;
        border-color: #667eea !important;
      }
    </style>
  </section>





  <!-- FOOTER -->
  <!-- FOOTER -->
  <!-- SUBSCRIBE SECTION -->
  <!-- Features Row -->

  <!-- FEATURES ROW -->
  <div class="features-section">
    <div class="features-container">
      <?php if (!empty($features)): ?>
        <?php foreach ($features as $ft): ?>
          <div class="feature-item">
            <div class="feature-icon-box">
              <i class="<?php echo htmlspecialchars($ft['icon']); ?>"></i>
            </div>
            <div class="feature-text">
              <h5><?php echo htmlspecialchars($ft['title']); ?></h5>
              <p><?php echo htmlspecialchars($ft['desc']); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- OUR STORIES SECTION (Multi-Card Grid) -->
  <?php if (!empty($ourStories)): ?>
  <section style="background:#fff; padding:80px 0;">
    <div style="max-width:1200px; margin:0 auto; padding:0 20px;">
      <h2 style="font-size:28px; font-weight:700; color:#1a1a1a; margin-bottom:50px; font-family:'Poppins', sans-serif; text-transform:uppercase; letter-spacing:2px; text-align:center;">
        OUR STORY
      </h2>
      
      <!-- Single Story Content (New) -->
      <?php if (!empty($osTitle) || !empty($osDesc) || !empty($osImage)): ?>
      <div style="margin-bottom: 50px; text-align: center;">
          <?php if (!empty($osImage)): 
             $sImg = $osImage;
             if (strpos($sImg, 'http') === 0) {}
             elseif (strpos($sImg, '/') === 0) {}
             elseif (strpos($sImg, 'assets/') === 0) { $sImg = '/' . $sImg; }
             else { $sImg = '/assets/uploads/story/' . $sImg; }
          ?>
          <div style="margin-bottom:20px; border-radius:12px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto 30px auto;">
            <img src="<?= htmlspecialchars($sImg) ?>" alt="Our Story" style="width:100%; height:auto; display:block;">
          </div>
          <?php endif; ?>
          
          <?php if (!empty($osTitle)): ?>
          <h3 style="font-size:24px; font-weight:700; color:#1a1a1a; margin-bottom:15px;"><?= htmlspecialchars($osTitle) ?></h3>
          <?php endif; ?>
          
          <?php if (!empty($osDesc)): ?>
          <p style="font-size:16px; color:#555; line-height:1.8; max-width:800px; margin:0 auto;"><?= nl2br(htmlspecialchars($osDesc)) ?></p>
          <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="our-story-grid">
        <?php foreach ($ourStories as $story): ?>
          <div style="text-align:center;">
            <?php if (!empty($story['image'])): 
                $sImg = $story['image'];
                if (strpos($sImg, 'http') === 0) {
                    // URL
                } elseif (strpos($sImg, '/') === 0) {
                    // Absolute path
                } elseif (strpos($sImg, 'assets/') === 0) {
                    $sImg = '/' . $sImg;
                } else {
                    $sImg = '/assets/uploads/story/' . $sImg;
                }
            ?>
              <div style="margin-bottom:20px; border-radius:12px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,0.1); height:250px;">
                <img src="<?= htmlspecialchars($sImg) ?>" alt="<?= htmlspecialchars($story['title']) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">

              </div>
            <?php endif; ?>
            
            <h3 style="font-size:16px; font-weight:700; color:#1a1a1a; margin-bottom:12px; text-transform:uppercase; letter-spacing:1px;">
              <?= htmlspecialchars($story['title']) ?>
            </h3>
            
            <p style="font-size:14px; color:#666; line-height:1.6; margin:0;">
              <?= htmlspecialchars($story['description']) ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

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

  // HOMEPAGE ADD TO CART LOGIC
  document.addEventListener('DOMContentLoaded', function() {
    const miniBtns = document.querySelectorAll('.btn-add-cart-mini');
    
    miniBtns.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent any parent link action if structure changes
        e.stopPropagation(); // Stop bubbling
        
        const productId = this.getAttribute('data-product-id');
        const originalHtml = this.innerHTML;
        const originalBg = this.style.background;
        
        // Show loading state
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';
        this.style.pointerEvents = 'none';
        
        fetch('ajax_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&product_id=${productId}&quantity=1`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Success state
                this.innerHTML = '<i class="fa-solid fa-check"></i> Added';
                this.style.background = '#27ae60'; // Green
                
                // Update Cart Count in Header (desktop + mobile)
                // Desktop: .cart-count, Mobile: .mobile-cart-count
                const desktopCount = document.querySelector('.cart-count');
                const mobileCount = document.querySelector('.mobile-cart-count');
                const mobileNavCount = document.querySelector('.mobile-bottom-nav .count'); // adjust selector if needed

                if (desktopCount) desktopCount.textContent = data.count;
                if (mobileCount) mobileCount.textContent = data.count;
                if (mobileNavCount) mobileNavCount.textContent = `(${data.count})`; // logic might vary

                // Revert button after 2s
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.style.background = originalBg; // Restore original gradient/color
                    this.style.pointerEvents = 'auto';
                }, 2000);
            } else {
                // Error (e.g. login required)
                if (data.message && data.message.toLowerCase().includes('login')) {
                     window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                } else {
                     alert(data.message || 'Failed to add to cart');
                     this.innerHTML = originalHtml;
                     this.style.pointerEvents = 'auto';
                }
            }
        })
        .catch(err => {
            console.error(err);
            this.innerHTML = originalHtml;
            this.style.pointerEvents = 'auto';
        });
      });
    });
  });
</script>

</body>
</html<!-- FEATURES ROW -->
  <div class="features-section">
    <div class="features-container">
      <?php if (!empty($features)): ?>
        <?php foreach ($features as $ft): ?>
          <div class="feature-item">
            <div class="feature-icon-box">
              <i class="<?php echo htmlspecialchars($ft['icon']); ?>"></i>
            </div>
            <div class="feature-text">
              <h5><?php echo htmlspecialchars($ft['title']); ?></h5>
              <p><?php echo htmlspecialchars($ft['desc']); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- OUR STORIES SECTION (Multi-Card Grid) -->
  <?php if (!empty($ourStories)): ?>
  <section style="background:#fff; padding:80px 0;">
    <div style="max-width:1200px; margin:0 auto; padding:0 20px;">
      <h2 style="font-size:28px; font-weight:700; color:#1a1a1a; margin-bottom:50px; font-family:'Poppins', sans-serif; text-transform:uppercase; letter-spacing:2px; text-align:center;">
        OUR STORY
      </h2>
      
      <!-- Single Story Content (New) -->
      <?php if (!empty($osTitle) || !empty($osDesc) || !empty($osImage)): ?>
      <div style="margin-bottom: 50px; text-align: center;">
          <?php if (!empty($osImage)): 
             $sImg = $osImage;
             if (strpos($sImg, 'http') === 0) {}
             elseif (strpos($sImg, '/') === 0) {}
             elseif (strpos($sImg, 'assets/') === 0) { $sImg = '/' . $sImg; }
             else { $sImg = '/assets/uploads/story/' . $sImg; }
          ?>
          <div style="margin-bottom:20px; border-radius:12px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto 30px auto;">
            <img src="<?= htmlspecialchars($sImg) ?>" alt="Our Story" style="width:100%; height:auto; display:block;">
          </div>
          <?php endif; ?>
          
          <?php if (!empty($osTitle)): ?>
          <h3 style="font-size:24px; font-weight:700; color:#1a1a1a; margin-bottom:15px;"><?= htmlspecialchars($osTitle) ?></h3>
          <?php endif; ?>
          
          <?php if (!empty($osDesc)): ?>
          <p style="font-size:16px; color:#555; line-height:1.8; max-width:800px; margin:0 auto;"><?= nl2br(htmlspecialchars($osDesc)) ?></p>
          <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="our-story-grid">
        <?php foreach ($ourStories as $story): ?>
          <div style="text-align:center;">
            <?php if (!empty($story['image'])): 
                $sImg = $story['image'];
                if (strpos($sImg, 'http') === 0) {
                    // URL
                } elseif (strpos($sImg, '/') === 0) {
                    // Absolute path
                } elseif (strpos($sImg, 'assets/') === 0) {
                    $sImg = '/' . $sImg;
                } else {
                    $sImg = '/assets/uploads/story/' . $sImg;
                }
            ?>
              <div style="margin-bottom:20px; border-radius:12px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,0.1); height:250px;">
                <img src="<?= htmlspecialchars($sImg) ?>" alt="<?= htmlspecialchars($story['title']) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">

              </div>
            <?php endif; ?>
            
            <h3 style="font-size:16px; font-weight:700; color:#1a1a1a; margin-bottom:12px; text-transform:uppercase; letter-spacing:1px;">
              <?= htmlspecialchars($story['title']) ?>
            </h3>
            
            <p style="font-size:14px; color:#666; line-height:1.6; margin:0;">
              <?= htmlspecialchars($story['description']) ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

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

  // HOMEPAGE ADD TO CART LOGIC
  document.addEventListener('DOMContentLoaded', function() {
    const miniBtns = document.querySelectorAll('.btn-add-cart-mini');
    
    miniBtns.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent any parent link action if structure changes
        e.stopPropagation(); // Stop bubbling
        
        const productId = this.getAttribute('data-product-id');
        const originalHtml = this.innerHTML;
        const originalBg = this.style.background;
        
        // Show loading state
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';
        this.style.pointerEvents = 'none';
        
        fetch('ajax_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&product_id=${productId}&quantity=1`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Success state
                this.innerHTML = '<i class="fa-solid fa-check"></i> Added';
                this.style.background = '#27ae60'; // Green
                
                // Update Cart Count in Header (desktop + mobile)
                // Desktop: .cart-count, Mobile: .mobile-cart-count
                const desktopCount = document.querySelector('.cart-count');
                const mobileCount = document.querySelector('.mobile-cart-count');
                const mobileNavCount = document.querySelector('.mobile-bottom-nav .count'); // adjust selector if needed

                if (desktopCount) desktopCount.textContent = data.count;
                if (mobileCount) mobileCount.textContent = data.count;
                if (mobileNavCount) mobileNavCount.textContent = `(${data.count})`; // logic might vary

                // Revert button after 2s
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.style.background = originalBg; // Restore original gradient/color
                    this.style.pointerEvents = 'auto';
                }, 2000);
            } else {
                // Error (e.g. login required)
                if (data.message && data.message.toLowerCase().includes('login')) {
                     window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                } else {
                     alert(data.message || 'Failed to add to cart');
                     this.innerHTML = originalHtml;
                     this.style.pointerEvents = 'auto';
                }
            }
        })
        .catch(err => {
            console.error(err);
            this.innerHTML = originalHtml;
            this.style.pointerEvents = 'auto';
        });
      });
    });
  });
</script>

</body>
</html<!-- CUSTOMER REVIEWS SECTION -->
  <?php if (!empty($homeReviews)): ?>
  <section class="section-reviews" style="background:linear-gradient(to bottom, #ffffff, #f9fafb); padding:70px 0;">
    <div class="container">
      <!-- Enhanced Section Header -->
      <div style="text-align:center; margin-bottom:50px;">
        <h2 style="font-size:32px; font-weight:700; color:#1a1a1a; margin-bottom:12px; font-family:'Poppins', sans-serif;">
          What Our Customers Say
        </h2>
        <p style="font-size:15px; color:#666; margin:0;">Real reviews from verified purchases</p>
      </div>
      
      <div class="reviews-scroll-container">
        <?php foreach ($homeReviews as $review): 
            $prodImg = get_first_image($review['product_images']);
        ?>
          <div class="home-review-card">
            <div class="quote-icon"><i class="fa-solid fa-quote-left"></i></div>
            
            <div class="review-stars">
              <?= str_repeat('<i class="fa-solid fa-star"></i>', (int)$review['rating']) ?>
              <?= str_repeat('<i class="fa-regular fa-star"></i>', 5 - (int)$review['rating']) ?>
            </div>
            
            <p class="review-text">
              "<?= htmlspecialchars(mb_strimwidth($review['comment'], 0, 140, '...')) ?>"
            </p>
            
            <div class="review-meta">
              <a href="product_view.php?id=<?= $review['product_id'] ?>" class="review-prod-img">
                <img src="<?= htmlspecialchars($prodImg) ?>" alt="Product">
              </a>
              <div class="review-info">
                <div class="reviewer-name">
                  <?= htmlspecialchars($review['reviewer_name'] ?: 'Verified Customer') ?>
                  <i class="fa-solid fa-circle-check" style="color:#2ECC71; margin-left:4px; font-size:12px;"></i>
                </div>
                <div class="reviewed-product">
                  Verified Purchase • <a href="product_view.php?id=<?= $review['product_id'] ?>"><?= htmlspecialchars($review['product_name']) ?></a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <style>
      .section-reviews {
          padding: 60px 0; /* Reduced padding to match others */
          background: linear-gradient(to bottom, #ffffff, #fcfcfc);
      }
      
      /* Removed custom .center-header styles to use global .section-header */

      .reviews-scroll-container {
          display: flex;
          overflow-x: auto;
          gap: 25px;
          padding: 20px 10px 40px;
          scroll-snap-type: x mandatory;
          -webkit-overflow-scrolling: touch;
          scrollbar-width: none; 
      }
      .reviews-scroll-container::-webkit-scrollbar {
          display: none;
      }

      .home-review-card {
          background: #fff;
          padding: 30px; /* Slightly reduced */
          border-radius: 16px;
          box-shadow: 0 4px 20px rgba(0,0,0,0.05); /* Softer shadow */
          flex: 0 0 350px; /* Back to 350px */
          scroll-snap-align: start; /* Standard snap */
          position: relative;
          transition: transform 0.3s ease, box-shadow 0.3s ease;
          border: 1px solid rgba(0,0,0,0.03);
          display: flex;
          flex-direction: column;
      }
      .home-review-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 10px 30px rgba(0,0,0,0.08); /* Consistent hover */
          border-color: #A41B42;
      }

      .quote-icon {
          position: absolute;
          top: 20px;
          right: 25px;
          font-size: 32px; /* Smaller icon */
          color: #f2f2f2;
          z-index: 0;
      }

      .review-stars {
          color: #ffb400; /* Standard gold */
          font-size: 14px;
          margin-bottom: 15px;
          position: relative;
          z-index: 1;
      }
      .review-stars i { margin-right: 2px; }

      .review-text {
          font-size: 15px; /* Standard text size */
          line-height: 1.7;
          color: #555;
          margin-bottom: 25px;
          flex-grow: 1;
          font-style: italic;
          position: relative;
          z-index: 1;
      }

      .review-meta {
          display: flex;
          align-items: center;
          gap: 15px;
          padding-top: 20px;
          border-top: 1px solid #f5f5f5;
      }

      .review-prod-img {
          width: 50px;
          height: 50px;
          border-radius: 50%;
          overflow: hidden;
          border: 1px solid #eee;
          box-shadow: none; /* Cleaner */
      }
      .review-prod-img img {
          width: 100%;
          height: 100%;
          object-fit: cover;
      }

      .review-info {
          display: flex;
          flex-direction: column;
      }
      .reviewer-name {
          font-weight: 700;
          color: #333;
          font-size: 15px;
          display: flex;
          align-items: center;
      }
      .reviewed-product {
          font-size: 12px;
          color: #888;
          margin-top: 2px;
      }
      .reviewed-product a {
          color: #A41B42;
          text-decoration: none;
          font-weight: 600;
          transition: color 0.2s;
      }
      .reviewed-product a:hover {
          text-decoration: underline;
      }

      /* Responsive */
      @media (max-width: 768px) {
          .section-reviews { padding: 40px 0; }
          .reviews-scroll-container { gap: 15px; padding: 10px 15px 20px; }
          .home-review-card { flex: 0 0 300px; padding: 20px; }
          .quote-icon { font-size: 24px; top: 15px; right: 15px; }
          .review-text { font-size: 14px; margin-bottom: 20px; }
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


  >