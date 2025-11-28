<?php
// product_view.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db.php';

// ---- 1. Get product ID from URL ----
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    // invalid id → redirect to shop
    header('Location: product.php');
    exit;
}

// ---- 2. Fetch product + category from DB ----
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            c.id   AS cat_id,
            c.name AS cat_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.id = :id
          AND (p.is_active = 1 OR p.is_active IS NULL)
        LIMIT 1
    ");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database error: ' . htmlspecialchars($e->getMessage()));
}

if (!$product) {
    // product not found
    http_response_code(404);
    echo "<h1 style='font-family:sans-serif;text-align:center;margin-top:60px;'>Product not found.</h1>";
    exit;
}

// ---- Fetch Variants with Override Data ----
$variants = [];
$variantFaqs = []; // Indexed by variant_id
try {
    $stmtVar = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1 ORDER BY price ASC");
    $stmtVar->execute([$productId]);
    $variants = $stmtVar->fetchAll(PDO::FETCH_ASSOC);

    // Inject Main Product as First Variant
    $mainVariantName = $product['main_variant_name'] ?? '';
    // Only show if there are other variants OR if a specific main name is set
    if (!empty($variants) || !empty($mainVariantName)) {
        $mainVariant = [
            'id' => 0, // 0 indicates main product (empty check passes)
            'variant_name' => $mainVariantName ?: 'Default',
            'price' => $product['price'],
            'stock' => $product['stock'],
            'sku' => $product['sku'],
            'image' => null, 
            'images' => null,
            'custom_title' => null,
            'custom_description' => null
        ];
        array_unshift($variants, $mainVariant);
    }
    
    // Fetch FAQs for all variants
    if (!empty($variants)) {
        $variantIds = array_column($variants, 'id');
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $stmtVarFaq = $pdo->prepare("SELECT * FROM variant_faqs WHERE variant_id IN ($placeholders) ORDER BY variant_id, display_order");
        $stmtVarFaq->execute($variantIds);
        $allVarFaqs = $stmtVarFaq->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by variant_id
        foreach ($allVarFaqs as $vfaq) {
            $vid = $vfaq['variant_id'];
            if (!isset($variantFaqs[$vid])) {
                $variantFaqs[$vid] = [];
            }
            $variantFaqs[$vid][] = $vfaq;
        }
    }
} catch (Exception $e) {
    $variants = [];
    $variantFaqs = [];
}

// ---- 3. Helper: parse images field into array ----
function parse_product_images($imagesField) {
    $out = [];
    if (!$imagesField) {
        return $out;
    }

    // Try JSON first: ["a.jpg","b.jpg"]
    $maybe = @json_decode($imagesField, true);
    if (is_array($maybe) && !empty($maybe)) {
        foreach ($maybe as $img) {
            $img = trim((string)$img);
            if ($img === '') continue;

            // Already full URL or absolute path
            if (preg_match('#^https?://#i', $img) || strpos($img, '/') === 0) {
                $out[] = $img;
            } else {
                // Relative file in uploads
                $out[] = 'assets/uploads/products/' . ltrim($img, '/');
            }
        }
        return $out;
    }

    // Otherwise: single or comma-separated "a.jpg,b.jpg"
    $parts = array_map('trim', explode(',', $imagesField));
    foreach ($parts as $img) {
        if ($img === '') continue;

        if (preg_match('#^https?://#i', $img) || strpos($img, '/') === 0) {
            $out[] = $img;
        } else {
            $out[] = 'assets/uploads/products/' . ltrim($img, '/');
        }
    }

    return $out;
}

// ---- images ----
$imageList = parse_product_images($product['images'] ?? '');
if (empty($imageList)) {
    $imageList = ['assets/images/avatar-default.png']; // fallback
}
$mainImage   = $imageList[0];
$thumbImages = array_slice($imageList, 1, 3);

// ---- 4. Prepare values for template ----
$productName   = $product['name'] ?? 'Product';
$categoryName  = $product['cat_name'] ?? 'Shop';

$price         = isset($product['price']) ? (float)$product['price'] : 0;
$oldPrice      = (isset($product['old_price']) && $product['old_price'] > $price)
                    ? (float)$product['old_price']
                    : null;

$rating        = isset($product['rating']) ? (float)$product['rating'] : 0;
$ratingCount   = isset($product['rating_count']) ? (int)$product['rating_count'] : 0;
$ratingStars   = $rating > 0 ? str_repeat('★', round($rating)) : '★★★★★';

$inStock       = isset($product['stock']) ? ((int)$product['stock'] > 0) : true;

// Short description: use short_desc if exists, else first part of description
$shortDesc = '';
if (!empty($product['short_desc'])) {
    $shortDesc = $product['short_desc'];
} elseif (!empty($product['description'])) {
    $shortDesc = mb_substr(strip_tags($product['description']), 0, 260) . '…';
}

// Long description & other blocks (optional DB columns)
$longDescription = $product['description']      ?? '';
$ingredientsText = $product['ingredients']      ?? '';
$howToUseText    = $product['how_to_use']       ?? '';

// Fetch "Why Devilixirs" from site_settings
$whyDevilixirsText = '';
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute(['why_devilixirs']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $whyDevilixirsText = $row['setting_value'] ?? '';
} catch (Exception $e) {
    $whyDevilixirsText = '';
}

// Fetch FAQs
$faqs = [];
try {
    $stmtFaq = $pdo->prepare("SELECT * FROM product_faqs WHERE product_id = ? ORDER BY id ASC");
    $stmtFaq->execute([$productId]);
    $faqs = $stmtFaq->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $faqs = [];
}

// Fetch Product Detail Sidebar Banner
$sidebarBanner = null;
try {
    $stmtBanner = $pdo->prepare("SELECT * FROM banners WHERE page_slot = 'product_detail_sidebar' AND is_active = 1 ORDER BY id DESC LIMIT 1");
    $stmtBanner->execute();
    $sidebarBanner = $stmtBanner->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $sidebarBanner = null;
}

// Images
$imageList = parse_product_images($product['images'] ?? '');
if (empty($imageList)) {
    $imageList = ['/assets/images/avatar-default.png']; // fallback
}
$mainImage   = $imageList[0];
$thumbImages = array_slice($imageList, 1, 3); // max 3 thumbs

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Devilixirs – <?php echo htmlspecialchars($productName, ENT_QUOTES); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
 :root{
  --primary:#D4AF37;
  --primary-dark:#B89026;
  --primary-light:#E5C158;
  --body-bg:#f8f9fa;
  --berry:#A41B42;
  --berry-dark:#832238;
  --highlight:#3B502C;
  --highlight-light:#4a6637;

  --text:#1a1a1a;
  --text-light:#2d2d2d;
  --muted:#777777;
  --border:#e1e1e1;
  --card-bg:#ffffff;
  --shadow-sm:0 2px 4px rgba(0,0,0,.08);
  --shadow-md:0 4px 12px rgba(0,0,0,.12);
  --shadow-lg:0 8px 24px rgba(0,0,0,.15);
  --shadow-xl:0 12px 32px rgba(0,0,0,.18);
}

*{
  margin:0;
  padding:0;
  box-sizing:border-box;
}

html{
  scroll-behavior:smooth;
}

html, body{
  font-family:"Poppins",sans-serif;
  color:var(--text);
  background:var(--body-bg);
  overflow-x:hidden;
}

a{ 
  color:inherit; 
  text-decoration:none; 
  transition:all .3s ease;
}
img{ 
  max-width:100%; 
  display:block; 
}
ul{ list-style:none; }

.container{
  max-width:1200px;
  margin:0 auto;
  padding:0 15px;
}

/* ============== HERO / BREADCRUMB ============== */
.product-hero{
  background:url("https://images.pexels.com/photos/3738344/pexels-photo-3738344.jpeg?auto=compress&cs=tinysrgb&w=1600")
             center/cover no-repeat;
  position:relative;
  margin-bottom:40px;
}
.product-hero::before{
  content:"";
  position:absolute;
  inset:0;
  background:linear-gradient(135deg, rgba(59,80,44,.7) 0%, rgba(164,27,66,.6) 100%);
}
.product-hero-inner{
  position:relative;
  z-index:1;
  padding:60px 15px 70px;
  max-width:900px;
  margin:0 auto;
  text-align:center;
  color:#fff;
}
.product-hero h1{
  font-size:32px;
  margin-bottom:10px;
  letter-spacing:.08em;
  text-transform:uppercase;
  text-shadow:2px 2px 8px rgba(0,0,0,.3);
  animation:slideDown .6s ease;
}
.breadcrumb{
  font-size:13px;
  animation:fadeIn .8s ease;
}
.breadcrumb a{
  color:#ffffff;
  opacity:.85;
  transition:all .3s ease;
}
.breadcrumb span{
  margin:0 4px;
}
.breadcrumb a:hover{
  opacity:1;
  transform:translateY(-1px);
}

@keyframes slideDown {
  from { opacity:0; transform:translateY(-20px); }
  to { opacity:1; transform:translateY(0); }
}

.hero-cats{
  margin-top:16px;
  display:flex;
  justify-content:center;
  flex-wrap:wrap;
  gap:20px;
  font-size:12px;
}
.hero-cat{
  display:flex;
  align-items:center;
  gap:6px;
  opacity:.9;
  transition:all .3s ease;
}
.hero-cat:hover{
  opacity:1;
  transform:scale(1.05);
}
.hero-cat i{
  font-size:18px;
}
.hero-cat small{
  display:block;
  font-size:10px;
  opacity:.8;
}

/* ============== MAIN PRODUCT SECTION ============== */
.page-wrap{
  max-width:1200px;
  margin:0 auto 60px;
  padding:0 15px;
  display:grid;
  grid-template-columns:3fr 2fr;
  gap:40px;
  animation:fadeInUp .6s ease;
}

@keyframes fadeInUp {
  from { opacity:0; transform:translateY(30px); }
  to { opacity:1; transform:translateY(0); }
}

.product-media{
  background:#fff;
  border:1px solid var(--border);
  border-radius:12px;
  padding:18px;
  box-shadow:var(--shadow-md);
  transition:all .3s ease;
}
.product-media:hover{
  box-shadow:var(--shadow-lg);
  transform:translateY(-4px);
}

/* Main Image Wrapper with Navigation */
.product-main-image-wrapper {
  position: relative;
  border-radius:8px;
  overflow:hidden;
}
.product-main-image{
  border:1px solid var(--border);
  background:#fafafa;
  border-radius:8px;
  overflow:hidden;
  position:relative;
}
.product-main-image::after{
  content:"";
  position:absolute;
  inset:0;
  background:linear-gradient(180deg, transparent 60%, rgba(0,0,0,.05) 100%);
  pointer-events:none;
}
.product-main-image img{
  width:100%;
  height:auto; 
  object-fit:cover;
  transition:all .5s ease;
}
.product-main-image:hover img{
  transform:scale(1.05);
}

/* Navigation Arrows */
.image-nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.9);
  border: none;
  width: 45px;
  height: 45px;
  border-radius: 50%;
  cursor: pointer;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.image-nav-btn:hover {
  background: rgba(255, 255, 255, 1);
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  transform: translateY(-50%) scale(1.1);
}
.image-nav-btn i {
  font-size: 18px;
  color: #333;
}
.prev-btn {
  left: 15px;
}
.next-btn {
  right: 15px;
}

/* Fullscreen Button */
.fullscreen-btn {
  position: absolute;
  bottom: 15px;
  right: 15px;
  background: rgba(255, 255, 255, 0.9);
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 6px;
  cursor: pointer;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.fullscreen-btn:hover {
  background: rgba(255, 255, 255, 1);
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  transform: scale(1.1);
}
.fullscreen-btn i {
  font-size: 16px;
  color: #333;
}

/* Thumbnail Gallery */
.thumb-gallery{
  margin-top:16px;
  display:flex;
  gap:10px;
  flex-wrap: wrap;
}
.thumb-item{
  flex: 0 0 calc(16.666% - 10px);
  min-width: 80px;
  border:3px solid transparent;
  background:#fafafa;
  border-radius:8px;
  cursor:pointer;
  overflow:hidden;
  transition:all .3s ease;
  position:relative;
}
.thumb-item.active {
  border-color:#4f46e5;
  box-shadow:0 0 0 1px #4f46e5;
}
.thumb-item::after{
  content:"";
  position:absolute;
  inset:0;
  background:rgba(79,70,229,.1);
  opacity:0;
  transition:all .3s ease;
}
.thumb-item:hover{
  border-color:var(--primary);
  transform:translateY(-3px);
  box-shadow:var(--shadow-md);
}
.thumb-item:hover::after{
  opacity:1;
}
.thumb-item img{
  height:90px;
  object-fit:cover;
  width:100%;
  transition:all .3s ease;
}
.thumb-item:hover img{
  transform:scale(1.1);
}

.product-summary{
  background:#fff;
  border:1px solid var(--border);
  border-radius:12px;
  padding:22px 24px 24px;
  box-shadow:var(--shadow-md);
  transition:all .3s ease;
}
.product-summary:hover{
  box-shadow:var(--shadow-lg);
}
.product-title{
  font-size:20px;
  letter-spacing:.1em;
  text-transform:uppercase;
  margin-bottom:10px;
  background:linear-gradient(135deg, var(--highlight) 0%, var(--berry) 100%);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
}
.product-meta{
  font-size:12px;
  color:var(--muted);
  margin-bottom:10px;
}

.rating-row{
  display:flex;
  align-items:center;
  gap:8px;
  font-size:12px;
  margin-bottom:8px;
}
.rating-stars{
  color:#ffb400;
  font-size:11px;
  text-shadow:0 1px 2px rgba(255,180,0,.3);
}

.stock{
  font-size:12px;
  margin-bottom:12px;
  color:var(--highlight);
  font-weight:600;
}

.price{
  font-size:22px;
  font-weight:700;
  color:var(--berry);
  margin-bottom:16px;
  animation:priceGlow 2s ease infinite;
}
.price .old-price{
  font-size:15px;
  text-decoration:line-through;
  color:#777;
  margin-right:8px;
}

@keyframes priceGlow {
  0%, 100% { text-shadow:0 0 0 rgba(164,27,66,0); }
  50% { text-shadow:0 0 8px rgba(164,27,66,.4); }
}

.short-desc{
  font-size:13px;
  color:var(--muted);
  line-height:1.7;
  margin-bottom:16px;
  border-top:1px solid var(--border);
  padding-top:16px;
}

.option-label{
  font-size:13px;
  margin-bottom:6px;
  font-weight:500;
  color:var(--text-light);
}

.color-options{
  display:flex;
  gap:8px;
  margin-bottom:14px;
}
.color-dot{
  width:28px;
  height:28px;
  border-radius:50%;
  border:2px solid transparent;
  cursor:pointer;
  transition:all .3s ease;
  box-shadow:var(--shadow-sm);
}
.color-dot:hover{
  transform:scale(1.15);
  box-shadow:var(--shadow-md);
}
.color-dot.gold{ background:linear-gradient(135deg, #d8a74b 0%, #f4d03f 100%); }
.color-dot.tan{ background:linear-gradient(135deg, #d3b694 0%, #e8d8c3 100%); }
.color-dot.blue{ background:linear-gradient(135deg, #2f4f90 0%, #4169b8 100%); }
.color-dot.active{
  border-color:var(--highlight);
  transform:scale(1.2);
  box-shadow:0 0 0 3px rgba(59,80,44,.2);
}

.size-options{
  display:flex;
  gap:8px;
  margin-bottom:16px;
}
.size-btn{
  min-width:36px;
  padding:6px 10px;
  border:1px solid var(--border);
  background:#fff;
  font-size:12px;
  cursor:pointer;
  border-radius:4px;
  transition:all .3s ease;
  font-weight:500;
}
.size-btn:hover{
  border-color:var(--highlight);
  background:rgba(59,80,44,.05);
  transform:translateY(-2px);
  box-shadow:var(--shadow-sm);
}
.size-btn.active{
  border-color:var(--highlight);
  color:#fff;
  background:linear-gradient(135deg, var(--highlight) 0%, var(--highlight-light) 100%);
  box-shadow:var(--shadow-md);
}

.icon-options{
  display:flex;
  gap:8px;
  margin-bottom:18px;
  font-size:12px;
}
.icon-options span{
  width:32px;
  height:32px;
  border-radius:50%;
  border:1px solid var(--border);
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  background:#fff;
  transition:all .3s ease;
}
.icon-options span:hover{
  border-color:var(--primary);
  background:linear-gradient(135deg, rgba(212,175,55,.1) 0%, rgba(212,175,55,.2) 100%);
  transform:translateY(-3px) rotate(10deg);
  box-shadow:var(--shadow-md);
}

.quantity-row{
  display:flex;
  align-items:center;
  gap:12px;
  margin-top:10px;
}
.qty-label{
  font-weight:600;
  font-size:13px;
  color:var(--text-light);
}
.qty-box{
  display:flex;
  align-items:center;
  border:1px solid var(--border);
  font-size:13px;
  border-radius:6px;
  overflow:hidden;
  box-shadow:var(--shadow-sm);
}
.qty-btn{
  width:32px;
  height:32px;
  border:none;
  background:linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  cursor:pointer;
  transition:all .3s ease;
  font-weight:600;
  color:var(--text);
}
.qty-btn:hover{
  background:linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
  color:#fff;
}
.qty-input{
  width:45px;
  border:none;
  text-align:center;
  outline:none;
  font-weight:600;
}

.btn-add-cart{
  margin-left:10px;
  padding:10px 32px;
  border:none;
  background:linear-gradient(135deg, var(--berry) 0%, var(--berry-dark) 100%);
  color:#fff;
  font-size:13px;
  text-transform:uppercase;
  letter-spacing:.12em;
  cursor:pointer;
  border-radius:6px;
  box-shadow:var(--shadow-md);
  transition:all .3s ease;
  font-weight:600;
}
.btn-add-cart:hover{
  transform:translateY(-2px);
  box-shadow:var(--shadow-lg);
  background:linear-gradient(135deg, var(--berry-dark) 0%, var(--berry) 100%);
}
.btn-add-cart:active{
  transform:translateY(0);
  box-shadow:var(--shadow-sm);
}

.share-row{
  margin-top:22px;
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  font-size:12px;
  align-items:center;
}
.share-row span.label{
  font-weight:500;
  color:var(--text-light);
}
.share-btn{
  padding:6px 12px;
  border:1px solid var(--border);
  border-radius:999px;
  background:#fff;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  gap:6px;
  transition:all .3s ease;
  box-shadow:var(--shadow-sm);
}
.share-btn:hover{
  border-color:var(--primary);
  background:linear-gradient(135deg, rgba(212,175,55,.1) 0%, rgba(212,175,55,.2) 100%);
  transform:translateY(-2px);
  box-shadow:var(--shadow-md);
}

/* ============== DESCRIPTION TABS & SIDEBAR ============== */
.tabs-area{
    max-width:1200px;
    margin:0 auto 50px;
    padding:0 15px;
    display:grid;
    grid-template-columns:3fr 1.4fr;
    gap:30px;
}
.tabs-card{
    background:#fff;
    border:1px solid var(--border);
    border-radius:12px;
    overflow:hidden;
    box-shadow:var(--shadow-md);
    transition:all .3s ease;
}
.tabs-card:hover{
    box-shadow:var(--shadow-lg);
}
.tabs-header{
    display:flex;
    border-bottom:1px solid var(--border);
    background:linear-gradient(135deg, #fafafa 0%, #f0f0f0 100%);
    overflow-x:auto;
    overflow-y:hidden;
    -webkit-overflow-scrolling:touch;
    scrollbar-width:none;
}
.tabs-header::-webkit-scrollbar{
    display:none;
}
.tab-item{
    padding:12px 20px;
    font-size:13px;
    font-weight:500;
    letter-spacing:.08em;
    transition:all .25s ease;
    cursor:pointer;
    white-space:nowrap;
    flex-shrink:0;
    position:relative;
}
.tab-item::after{
    content:"";
    position:absolute;
    bottom:0;
    left:50%;
    width:0;
    height:2px;
    background:var(--highlight);
    transition:all .3s ease;
    transform:translateX(-50%);
}
.tab-item:hover{
    background:linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    color:var(--primary-dark);
}
.tab-item:hover::after{
    width:70%;
}
.tab-item.active{
    background:#fff;
    border-bottom:2px solid var(--highlight);
    color:var(--highlight);
    font-weight:600;
}
.tab-item.active::after{
    width:100%;
}

.tab-contents {
  padding: 24px 26px;
  background: #fff;
}

.tab-pane{
  display:none;
  animation: fadeInSlide .4s ease;
}
.tab-pane.active{
  display:block;
}

.tab-pane h3 {
  font-size: 18px;
  font-weight: 600;
  color: var(--highlight);
  margin-bottom: 12px;
  letter-spacing: .05em;
  text-transform: uppercase;
  position:relative;
  padding-left:16px;
}
.tab-pane h3::before{
  content:"";
  position:absolute;
  left:0;
  top:50%;
  transform:translateY(-50%);
  width:4px;
  height:20px;
  background:linear-gradient(180deg, var(--primary) 0%, var(--berry) 100%);
  border-radius:2px;
}

.tab-pane p {
  font-size: 14px;
  line-height: 1.8;
  color: #555;
  margin-bottom: 12px;
}

.tabs-list{
  margin-top:10px;
  font-size:13px;
  padding-left:22px;
}
.tabs-list li{
  margin-bottom:6px;
  font-size:14px;
  line-height:1.8;
  transition:all .3s ease;
}
.tabs-list li:hover{
  color:var(--highlight);
  transform:translateX(5px);
}

@keyframes fadeInSlide {
  from { opacity:0; transform:translateY(10px); }
  to { opacity:1; transform:translateY(0); }
}

/* Right column (tags + banner) */
.right-sidebar{
  display:flex;
  flex-direction:column;
  gap:20px;
}
.tags-card{
  background:#fff;
  border:1px solid var(--border);
  border-radius:12px;
  padding:16px 18px 18px;
  font-size:13px;
  box-shadow:var(--shadow-md);
  transition:all .3s ease;
}
.tags-card:hover{
  box-shadow:var(--shadow-lg);
  transform:translateY(-2px);
}
.tags-title{
  text-transform:uppercase;
  font-size:12px;
  margin-bottom:10px;
  font-weight:600;
  color:var(--text-light);
}
.tag-chip{
  display:inline-block;
  padding:6px 12px;
  background:linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius:999px;
  margin:0 6px 8px 0;
  font-size:11px;
  cursor:pointer;
  transition:all .3s ease;
  border:1px solid transparent;
  font-weight:500;
}
.tag-chip:hover{
  background:linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color:#fff;
  transform:translateY(-2px);
  box-shadow:var(--shadow-md);
  border-color:var(--primary);
}
.side-banner{
  background:#fff;
  border:1px solid var(--border);
  border-radius:12px;
  overflow:hidden;
  box-shadow:var(--shadow-md);
  transition:all .3s ease;
}
.side-banner:hover{
  box-shadow:var(--shadow-lg);
  transform:translateY(-4px);
}
.side-banner img{
  width:100%;
  height:220px;
  object-fit:cover;
  transition:all .5s ease;
}
.side-banner:hover img{
  transform:scale(1.1);
}
.side-banner-txt{
  padding:14px 16px 16px;
  font-size:13px;
  background:linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
}
.side-banner-txt h4{
  margin-bottom:4px;
  font-size:15px;
  color:var(--highlight);
  font-weight:600;
}
.side-banner-txt span{
  color:var(--muted);
  font-size:12px;
}

/* REVIEWS FORM */
.review-form h3{
  margin:0 0 10px;
  font-size:18px;
  font-weight:600;
  color:var(--highlight);
  position:relative;
  padding-left:16px;
}
.review-form h3::before{
  content:"";
  position:absolute;
  left:0;
  top:50%;
  transform:translateY(-50%);
  width:4px;
  height:20px;
  background:linear-gradient(180deg, var(--primary) 0%, var(--berry) 100%);
  border-radius:2px;
}
.review-form p{
  margin:0 0 12px;
  font-size:13px;
  color:#777;
}
.form-row{
  margin-bottom:14px;
}
.form-row.half{
  display:flex;
  gap:16px;
}
.form-row label{
  display:block;
  margin-bottom:4px;
  font-size:13px;
  font-weight:500;
  color:var(--text-light);
}
textarea,
input[type="text"],
input[type="email"]{
  width:100%;
  padding:10px 12px;
  border:1px solid #ddd;
  font-size:13px;
  outline:none;
  border-radius:6px;
  transition:all .3s ease;
  font-family:"Poppins",sans-serif;
}
textarea:focus,
input:focus{
  border-color:var(--berry);
  box-shadow:0 0 0 3px rgba(164,27,66,.1);
}
.rating-input{
  direction:rtl;
  display:inline-flex;
  gap:4px;
}
.rating-input input{ display:none; }
.rating-input label{
  font-size:20px;
  color:#ccc;
  cursor:pointer;
  transition:all .3s ease;
}
.rating-input label:hover{
  transform:scale(1.2);
}
.rating-input input:checked ~ label,
.rating-input label:hover,
.rating-input label:hover ~ label{
  color:#f1c40f;
  text-shadow:0 0 8px rgba(241,196,15,.5);
}
.btn-submit-review{
  border:none;
  background:linear-gradient(135deg, var(--berry) 0%, var(--berry-dark) 100%);
  color:#fff;
  padding:10px 22px;
  text-transform:uppercase;
  font-size:12px;
  letter-spacing:.08em;
  cursor:pointer;
  border-radius:6px;
  transition:all .3s ease;
  box-shadow:var(--shadow-md);
  font-weight:600;
}
.btn-submit-review:hover{
  transform:translateY(-2px);
  box-shadow:var(--shadow-lg);
  background:linear-gradient(135deg, var(--berry-dark) 0%, var(--berry) 100%);
}

/* ============== BRAND STRIP & FOOTER ============== */
.brand-strip{
  background:linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
  padding:24px 0;
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
.brand-strip-inner span{
  transition:all .3s ease;
  cursor:pointer;
}
.brand-strip-inner span:hover{
  color:var(--primary);
  transform:translateY(-2px);
}

.footer{
  background:linear-gradient(135deg, #0d0d0d 0%, #1a1a1a 100%);
  color:#ddd;
  padding:40px 0 0;
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
  color:var(--primary);
  font-weight:600;
}
.footer-links li{
  margin-bottom:6px;
  color:#bfbfbf;
  cursor:pointer;
  transition:all .3s ease;
}
.footer-links li:hover{
  color:#fff;
  transform:translateX(5px);
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
  padding:6px 10px;
  border-radius:4px;
  transition:all .3s ease;
  cursor:pointer;
  border:1px solid transparent;
}
.footer-payments span:hover{
  background:var(--primary);
  color:#000;
  border-color:var(--primary);
  transform:translateY(-2px);
}

/* Back to top */
.back-top{
  position:fixed;
  right:20px;
  bottom:20px;
  width:45px;
  height:45px;
  border-radius:50%;
  background:linear-gradient(135deg, var(--berry) 0%, var(--berry-dark) 100%);
  color:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  font-size:18px;
  box-shadow:var(--shadow-lg);
  transition:all .3s ease;
  z-index:999;
}
.back-top:hover{
  transform:translateY(-5px) scale(1.1);
  box-shadow:var(--shadow-xl);
}

/* ============== RESPONSIVE ============== */
@media(max-width:992px){
  .page-wrap{
    grid-template-columns:1.6fr 1.6fr;
  }
  .tabs-area{
    grid-template-columns:1.6fr 1.4fr;
  }
  .footer-inner{
    grid-template-columns:repeat(2,minmax(0,1fr));
  }
}

@media(max-width:768px){
  .page-wrap{
    grid-template-columns:1fr;
  }
  .tabs-area{
    grid-template-columns:1fr;
  }

  .tabs-header{
    display: flex;
    flex-wrap: nowrap !important;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    touch-action: pan-x;
    scrollbar-width: none;
  }

  .tabs-header::-webkit-scrollbar {
    display: none;
  }

  .tab-item{
    flex: 0 0 auto;
    white-space: nowrap;
    padding: 12px 18px;
    min-width: auto;
  }

  .form-row.half{
    flex-direction:column;
  }
  
  .product-hero h1{
    font-size:24px;
  }
}

@media(max-width:576px){
  .hero-cats{
    gap:14px;
  }
  .footer-inner{
    grid-template-columns:1fr;
  }
}
  </style>
</head>
<body>

  <!-- 🔹 Shared navbar from your project -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- HERO / BREADCRUMB -->
  <section class="product-hero">
    <div class="product-hero-inner">
      <h1><?php echo htmlspecialchars($productName, ENT_QUOTES); ?></h1>
      <div class="breadcrumb">
        <a href="index.php">Home</a><span>&rsaquo;</span>
        <a href="product.php">Shop</a>
        <?php if (!empty($categoryName)): ?>
          <span>&rsaquo;</span>
          <a href="product.php?category[]=<?php echo urlencode($categoryName); ?>">
            <?php echo htmlspecialchars($categoryName, ENT_QUOTES); ?>
          </a>
        <?php endif; ?>
        <span>&rsaquo;</span>
        <span><?php echo htmlspecialchars($productName, ENT_QUOTES); ?></span>
      </div>

      <div class="hero-cats">
        <?php if (!empty($categoryName)): ?>
          <div class="hero-cat">
            <i class="fa-solid fa-leaf"></i>
            <span>
              <?php echo htmlspecialchars($categoryName, ENT_QUOTES); ?>
              <small>(Herbal)</small>
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- MAIN PRODUCT SECTION -->
  <section class="page-wrap">
    <!-- LEFT: IMAGES -->
    <div class="product-media">
      <div class="product-main-image-wrapper">
        <!-- Navigation Arrows -->
        <button class="image-nav-btn prev-btn" id="prevImageBtn">
          <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button class="image-nav-btn next-btn" id="nextImageBtn">
          <i class="fa-solid fa-chevron-right"></i>
        </button>
        
        <!-- Main Image -->
        <div class="product-main-image">
          <img id="mainProductImage"
               src="<?php echo htmlspecialchars($mainImage, ENT_QUOTES); ?>"
               alt="<?php echo htmlspecialchars($productName, ENT_QUOTES); ?>">
        </div>
        
        <!-- Fullscreen Button -->
        <button class="fullscreen-btn" id="fullscreenBtn">
          <i class="fa-solid fa-expand"></i>
        </button>
      </div>
      
      <!-- Thumbnail Gallery -->
      <div class="thumb-gallery">
        <?php foreach ($imageList as $index => $img): ?>
          <div class="thumb-item <?php echo $index === 0 ? 'active' : ''; ?>" 
               data-index="<?php echo $index; ?>"
               data-image="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>">
            <img src="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>" alt="Product image <?php echo $index + 1; ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- RIGHT: SUMMARY -->
    <div class="product-summary">
      <h2 class="product-title"><?php echo htmlspecialchars($productName, ENT_QUOTES); ?></h2>

      <div class="product-meta">
        <?php if ($ratingCount > 0): ?>
          <span><i class="fa-regular fa-pen-to-square"></i>
            <?php echo $ratingCount; ?> Review<?php echo $ratingCount > 1 ? 's' : ''; ?>
          </span>
        <?php else: ?>
          <span>No reviews yet</span>
        <?php endif; ?>
      </div>

      <div class="rating-row">
        <div class="rating-stars">
          <?php echo $ratingStars; ?>
        </div>
        <?php if ($ratingCount > 0): ?>
          <span>(Rated <?php echo number_format($rating, 1); ?>)</span>
        <?php endif; ?>
      </div>

      <div class="stock">
        <?php echo $inStock ? 'In Stock' : 'Out of Stock'; ?>
      </div>

      <div class="price">
        <?php if ($oldPrice): ?>
          <span class="old-price">₹<?php echo number_format($oldPrice, 2); ?></span>
        <?php endif; ?>
        ₹<?php echo number_format($price, 2); ?>
      </div>

      <?php if (!empty($shortDesc)): ?>
        <div class="short-desc">
          <?php echo nl2br(htmlspecialchars($shortDesc, ENT_QUOTES)); ?>
        </div>
      <?php endif; ?>


      <!-- Variants / Size -->
      <?php if (!empty($variants)): ?>
        <div class="mb-4">
          <div class="option-label">Select <?php echo htmlspecialchars($product['variant_label'] ?? 'Size'); ?></div>
          <div class="size-options flex flex-wrap gap-2">
            <?php foreach ($variants as $idx => $v): 
              // Prepare variant data
              $vImages = !empty($v['images']) ? json_decode($v['images'], true) : [];
              if (!is_array($vImages)) $vImages = [];
              if (empty($vImages) && !empty($v['image'])) {
                $vImages = [$v['image']]; // Fallback to legacy single image
              }
              $vImagesUrls = array_map(function($img) {
                return '/assets/uploads/products/' . ltrim($img, '/');
              }, $vImages);
              
              $vFaqs = $variantFaqs[$v['id']] ?? [];
            ?>
              <button type="button" 
                      class="size-btn <?php echo $idx === 0 ? 'active' : ''; ?>"
                      data-id="<?php echo $v['id']; ?>"
                      data-price="<?php echo $v['price']; ?>"
                      data-stock="<?php echo $v['stock']; ?>"
                      data-sku="<?php echo htmlspecialchars($v['sku']); ?>"
                      data-image="<?php echo !empty($v['image']) ? '/assets/uploads/products/' . htmlspecialchars($v['image']) : ''; ?>"
                      data-custom-title="<?php echo htmlspecialchars($v['custom_title'] ?? '', ENT_QUOTES); ?>"
                      data-custom-description="<?php echo htmlspecialchars($v['custom_description'] ?? '', ENT_QUOTES); ?>"
                      data-short-description="<?php echo htmlspecialchars($v['short_description'] ?? '', ENT_QUOTES); ?>"
                      data-images="<?php echo htmlspecialchars(json_encode($vImagesUrls), ENT_QUOTES); ?>"
                      data-faqs="<?php echo htmlspecialchars(json_encode($vFaqs), ENT_QUOTES); ?>">
                <?php echo htmlspecialchars($v['variant_name']); ?>
              </button>
            <?php endforeach; ?>
          </div>
          <input type="hidden" id="selectedVariantId" name="variant_id" value="<?php echo $variants[0]['id']; ?>">
        </div>
      <?php endif; ?>

      <!-- JS for Variants with Dynamic Switching -->
      <script>
        document.addEventListener("DOMContentLoaded", function() {
          const variantBtns = document.querySelectorAll('.size-btn');
          const priceDisplay = document.querySelector('.price');
          const stockDisplay = document.querySelector('.stock');
          const variantInput = document.getElementById('selectedVariantId');
          
          // Store default product data for fallback
          const defaultData = {
            title: '<?php echo addslashes($productName); ?>',
            description: `<?php echo addslashes($longDescription); ?>`,
            images: <?php echo json_encode($imageList); ?>.map(img => img.startsWith('/') ? img : '/' + img),
            faqs: <?php echo json_encode($faqs); ?>
          };

          if (variantBtns.length > 0) {
            // Safe JSON parse helper (MUST be defined first)
            function safeParse(jsonStr, fallback) {
              try {
                return jsonStr ? JSON.parse(jsonStr) : fallback;
              } catch (e) {
                console.error('JSON Parse Error:', e);
                return fallback;
              }
            }

            // Function to update all content dynamically
            function updateVariant(btn) {
              console.log('=== Updating Variant ===');
              console.log('Button dataset:', btn.dataset);
              
              // Update active class
              variantBtns.forEach(b => b.classList.remove('active'));
              btn.classList.add('active');

              // Get variant data (with fallback to product data)
              const customTitle = btn.dataset.customTitle || defaultData.title;
              const customDesc = btn.dataset.customDescription || defaultData.description;
              const shortDesc = btn.dataset.shortDescription || '';
              const variantImages = safeParse(btn.dataset.images, defaultData.images);
              const variantFaqs = safeParse(btn.dataset.faqs, defaultData.faqs);

              console.log('Custom Title:', customTitle);
              console.log('Short Description:', shortDesc);
              console.log('Variant Images:', variantImages);
              console.log('Default Images:', defaultData.images);

              // Update Title (both locations!)
              const titleElement = document.querySelector('.product-hero h1');
              const productTitleElement = document.querySelector('.product-title');
              console.log('Title Element:', titleElement);
              console.log('Product Title Element:', productTitleElement);
              
              if (titleElement && customTitle) {
                titleElement.textContent = customTitle;
                console.log('Hero title updated to:', customTitle);
              }
              if (productTitleElement && customTitle) {
                productTitleElement.textContent = customTitle;
                console.log('Product title updated to:', customTitle);
              }

              // Update Short Description
              const shortDescElement = document.querySelector('.short-desc');
              if (shortDescElement) {
                if (shortDesc) {
                  shortDescElement.innerHTML = shortDesc.replace(/\n/g, '<br>');
                  shortDescElement.style.display = 'block';
                } else {
                  shortDescElement.style.display = 'none';
                }
              }

              // Update Description (in tab content)
              const descTab = document.querySelector('#tab-desc');
              if (descTab && customDesc) {
                descTab.innerHTML = '<h3>Description</h3><p>' + customDesc.replace(/\n/g, '<br>') + '</p>';
              }

              // Update Images with fallback
              let finalImages = variantImages;
              if (!finalImages || finalImages.length === 0) {
                finalImages = defaultData.images;
              }
              console.log('Final Images to update:', finalImages);
              updateImageGallery(finalImages);

              // Update FAQs
              updateFAQs(Array.isArray(variantFaqs) && variantFaqs.length > 0 ? variantFaqs : defaultData.faqs);

              // Update Price & Stock
              const price = parseFloat(btn.dataset.price);
              if (priceDisplay) {
                priceDisplay.innerHTML = '<?php if ($oldPrice): ?><span class="old-price">₹<?php echo number_format($oldPrice, 2); ?></span><?php endif; ?>' + '₹' + price.toFixed(2);
              }
              
              const stock = parseInt(btn.dataset.stock);
              if (stockDisplay) {
                if (stock > 0) {
                  stockDisplay.textContent = 'In Stock';
                  stockDisplay.style.color = 'var(--highlight)';
                } else {
                  stockDisplay.textContent = 'Out of Stock';
                  stockDisplay.style.color = 'red';
                }
              }
              
              // Update hidden input
              if (variantInput) {
                variantInput.value = btn.dataset.id;
              }
            }

            // Update image gallery
            function updateImageGallery(images) {
              console.log('=== updateImageGallery called ===');
              console.log('Images to display:', images);
              
              const mainImage = document.getElementById('mainProductImage');
              const thumbGallery = document.querySelector('.thumb-gallery');
              
              console.log('Main Image Element:', mainImage);
              console.log('Thumb Gallery Element:', thumbGallery);
              
              if (mainImage && images.length > 0) {
                console.log('Updating main image to:', images[0]);
                mainImage.src = images[0];
              }
              
              if (thumbGallery) {
                thumbGallery.innerHTML = '';
                images.forEach((img, index) => {
                  const thumbItem = document.createElement('div');
                  thumbItem.className = 'thumb-item' + (index === 0 ? ' active' : '');
                  thumbItem.setAttribute('data-index', index);
                  thumbItem.setAttribute('data-image', img);
                  thumbItem.innerHTML = `<img src="${img}" alt="Thumbnail ${index + 1}">`;
                  thumbItem.addEventListener('click', function() {
                    mainImage.src = img;
                    document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
                    thumbItem.classList.add('active');
                  });
                  thumbGallery.appendChild(thumbItem);
                });
              }
            }

            // Update FAQs
            function updateFAQs(faqs) {
              const faqContainer = document.querySelector('.faqs-container'); // Changed from #faqAccordion to .faqs-container
              if (!faqContainer) return;
              
              faqContainer.innerHTML = '';
              
              if (!faqs || faqs.length === 0) {
                faqContainer.innerHTML = '<p class="text-gray-500">No FAQs available for this variant.</p>';
                return;
              }
              
              faqs.forEach((faq, index) => {
                const faqItem = document.createElement('div');
                faqItem.className = 'faq-item';
                faqItem.innerHTML = `
                  <div class="faq-question">
                    ${faq.question}
                    <span class="faq-toggle">+</span>
                  </div>
                  <div class="faq-answer">
                    <p>${faq.answer}</p>
                  </div>
                `;
                faqContainer.appendChild(faqItem);
              });

              // Re-attach event listeners for new FAQ items
              document.querySelectorAll('.faq-question').forEach(question => {
                question.addEventListener('click', function() {
                  this.parentElement.classList.toggle('active');
                });
              });
            }

            // Initialize with first variant
            try {
               updateVariant(variantBtns[0]);
            } catch (e) {
               console.error('Error updating initial variant:', e);
            }

            // Attach click event to variant buttons
            variantBtns.forEach(btn => {
              btn.addEventListener('click', function() {
                try {
                  updateVariant(this);
                } catch (e) {
                  console.error('Error updating variant:', e);
                }
              });
            });
          }
        });
      </script>

      <!-- Quantity + Add to cart -->
      <div class="quantity-row">
        <span class="qty-label">Quantity</span>
        <div class="qty-box">
          <button type="button" class="qty-btn" data-action="dec">−</button>
          <input class="qty-input" type="text" value="1" name="quantity">
          <button type="button" class="qty-btn" data-action="inc">+</button>
        </div>
        <!-- For now this is a dummy button. Later you can POST to cart.php -->
        <button class="btn-add-cart" type="button">
          <i class="fa-solid fa-bag-shopping"></i> &nbsp;Add To Cart
        </button>
      </div>

      <!-- Share -->
      <div class="share-row">
        <span class="label">Share:</span>
        <button class="share-btn"><i class="fa-brands fa-x-twitter"></i> Tweet</button>
        <button class="share-btn"><i class="fa-brands fa-facebook-f"></i> Share</button>
        <button class="share-btn"><i class="fa-brands fa-google-plus-g"></i> Google+</button>
        <button class="share-btn"><i class="fa-brands fa-pinterest-p"></i> Pinterest</button>
      </div>
    </div>
  </section>

  <!-- DESCRIPTION / TABS & SIDEBAR -->
  <section class="tabs-area">
    <!-- Tabs -->
    <div class="tabs-card">

      <div class="tabs-header">
        <div class="tab-item active" data-tab="desc">Description</div>
        <div class="tab-item" data-tab="ing">Ingredients</div>
        <div class="tab-item" data-tab="use">How to use</div>
        <div class="tab-item" data-tab="why">Why Devilixirs</div>
        <div class="tab-item" data-tab="why">Why Devilixirs</div>
      </div>

      <div class="tab-contents">
        <!-- DESCRIPTION -->
        <div class="tab-pane active" id="tab-desc">
          <h3>Description</h3>
          <?php if (!empty($longDescription)): ?>
            <p>
              <?php echo nl2br(htmlspecialchars($longDescription, ENT_QUOTES)); ?>
            </p>
          <?php else: ?>
            <p>
              Description coming soon for this product.
            </p>
          <?php endif; ?>
        </div>

        <!-- INGREDIENTS -->
        <div class="tab-pane" id="tab-ing">
          <h3>Ingredients</h3>
          <?php if (!empty($ingredientsText)): ?>
            <p><?php echo nl2br(htmlspecialchars($ingredientsText, ENT_QUOTES)); ?></p>
          <?php else: ?>
            <p>Ingredients information will be updated soon.</p>
          <?php endif; ?>
        </div>

        <!-- HOW TO USE -->
        <div class="tab-pane" id="tab-use">
          <h3>How to use</h3>
          <?php if (!empty($howToUseText)): ?>
            <p><?php echo nl2br(htmlspecialchars($howToUseText, ENT_QUOTES)); ?></p>
          <?php else: ?>
            <p>Usage instructions will be updated soon.</p>
          <?php endif; ?>
        </div>

        <!-- WHY DEVILIXIRS -->
        <div class="tab-pane" id="tab-why">
          <h3>Why Devilixirs</h3>
          <?php if (!empty($whyDevilixirsText)): ?>
            <p><?php echo nl2br(htmlspecialchars($whyDevilixirsText, ENT_QUOTES)); ?></p>
          <?php else: ?>
            <ul class="tabs-list">
              <li>✔ 100% Herbal formula</li>
              <li>✔ No chemicals or parabens</li>
              <li>✔ Crafted in small batches</li>
              <li>✔ Made in Chennai with care</li>
            </ul>
          <?php endif; ?>
        </div>



      </div>
    </div>

    <!-- Right Sidebar -->
    <div class="right-sidebar">
      <div class="tags-card">
        <div class="tags-title">Popular Tags</div>
        <div>
          <span class="tag-chip">Herbal</span>
          <span class="tag-chip">Handmade</span>
          <span class="tag-chip">Hair Care</span>
          <span class="tag-chip">Skin Care</span>
          <span class="tag-chip">Devilixirs</span>
        </div>
      </div>

      <?php if (!empty($sidebarBanner)): 
        $bannerSrc = '/assets/uploads/banners/' . ltrim($sidebarBanner['filename'] ?? '', '/');
        $bannerAlt = htmlspecialchars($sidebarBanner['alt_text'] ?? 'Promo Banner', ENT_QUOTES);
        $bannerLink = $sidebarBanner['link'] ?? '';
      ?>
        <div class="side-banner">
          <?php if (!empty($bannerLink)): ?>
            <a href="<?php echo htmlspecialchars($bannerLink, ENT_QUOTES); ?>">
              <img src="<?php echo htmlspecialchars($bannerSrc, ENT_QUOTES); ?>" alt="<?php echo $bannerAlt; ?>">
            </a>
          <?php else: ?>
            <img src="<?php echo htmlspecialchars($bannerSrc, ENT_QUOTES); ?>" alt="<?php echo $bannerAlt; ?>">
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- FAQs SECTION -->
  <?php if (!empty($faqs)): ?>
  <section class="faqs-section">
    <div class="section-title">
      <h2>Frequently Asked Questions</h2>
    </div>
    <div class="faqs-container">
      <?php foreach ($faqs as $f): ?>
        <div class="faq-item">
          <div class="faq-question">
            <?php echo htmlspecialchars($f['question']); ?>
            <span class="faq-toggle">+</span>
          </div>
          <div class="faq-answer">
            <p><?php echo nl2br(htmlspecialchars($f['answer'])); ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- REVIEWS SECTION -->
  <section class="reviews-section">
    <div class="section-title">
      <h2>Customer Reviews</h2>
    </div>
    <div class="review-form-container">
      <h3>Write a review</h3>


      <form action="#" method="post">
        <div class="form-row">

          <div class="rating-input">
            <input type="radio" name="rating" id="star5" value="5">
            <label for="star5">★</label>
            <input type="radio" name="rating" id="star4" value="4">
            <label for="star4">★</label>
            <input type="radio" name="rating" id="star3" value="3">
            <label for="star3">★</label>
            <input type="radio" name="rating" id="star2" value="2">
            <label for="star2">★</label>
            <input type="radio" name="rating" id="star1" value="1">
            <label for="star1">★</label>
          </div>
        </div>

        <div class="form-row">
          <label for="review">Your review *</label>
          <textarea id="review" name="review" rows="4" required></textarea>
        </div>

        <div class="form-row">
          <label for="review_images">Upload Images (optional)</label>
          <input type="file" id="review_images" name="review_images[]" accept="image/*" multiple class="file-input">
        </div>

        <button type="submit" class="btn-submit-review">Submit Review</button>
      </form>
    </div>
  </section>

  <script>
    // ============ Image Gallery Logic ============
    (function() {
      const mainImage = document.getElementById('mainProductImage');
      const thumbItems = document.querySelectorAll('.thumb-item');
      const prevBtn = document.getElementById('prevImageBtn');
      const nextBtn = document.getElementById('nextImageBtn');
      const fullscreenBtn = document.getElementById('fullscreenBtn');
      
      let currentIndex = 0;
      const images = Array.from(thumbItems).map(item => item.dataset.image);
      
      // Update main image and active thumbnail
      function updateImage(index) {
        if (index < 0) index = images.length - 1;
        if (index >= images.length) index = 0;
        
        currentIndex = index;
        mainImage.src = images[index];
        
        // Update active thumbnail
        thumbItems.forEach((item, i) => {
          if (i === index) {
            item.classList.add('active');
          } else {
            item.classList.remove('active');
          }
        });
      }
      
      // Thumbnail click
      thumbItems.forEach((item, index) => {
        item.addEventListener('click', () => {
          updateImage(index);
        });
      });
      
      // Previous button
      if (prevBtn) {
        prevBtn.addEventListener('click', () => {
          updateImage(currentIndex - 1);
        });
      }
      
      // Next button
      if (nextBtn) {
        nextBtn.addEventListener('click', () => {
          updateImage(currentIndex + 1);
        });
      }
      
      // Fullscreen button
      if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', () => {
          if (mainImage.requestFullscreen) {
            mainImage.requestFullscreen();
          } else if (mainImage.webkitRequestFullscreen) {
            mainImage.webkitRequestFullscreen();
          } else if (mainImage.mozRequestFullScreen) {
            mainImage.mozRequestFullScreen();
          } else if (mainImage.msRequestFullscreen) {
            mainImage.msRequestFullscreen();
          }
        });
      }
      
      // Keyboard navigation
      document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
          updateImage(currentIndex - 1);
        } else if (e.key === 'ArrowRight') {
          updateImage(currentIndex + 1);
        }
      });
    })();

    // FAQ Accordion Logic
    document.querySelectorAll('.faq-question').forEach(item => {
      item.addEventListener('click', () => {
        const parent = item.parentElement;
        parent.classList.toggle('active');
      });
    });
  </script>

  <style>
    /* FAQs Styles */
    .faqs-section {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .faqs-container {
      border: 1px solid #eee;
      border-radius: 8px;
    }
    .faq-item {
      border-bottom: 1px solid #eee;
    }
    .faq-item:last-child {
      border-bottom: none;
    }
    .faq-question {
      padding: 15px 20px;
      cursor: pointer;
      font-weight: 600;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #f9f9f9;
    }
    .faq-answer {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease-out;
      padding: 0 20px;
      background: #fff;
    }
    .faq-item.active .faq-answer {
      max-height: 500px; /* Arbitrary large height */
      padding: 15px 20px;
      border-top: 1px solid #eee;
    }
    .faq-toggle {
      font-size: 1.2rem;
      transition: transform 0.3s;
    }
    .faq-item.active .faq-toggle {
      transform: rotate(45deg);
    }

    /* Reviews Styles */
    .reviews-section {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .review-form-container {
      background: #fff;
      padding: 30px;
      border: 1px solid #eee;
      border-radius: 8px;
    }
    .review-form-container h3 {
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 20px;
      color: #333;
    }
    .form-row {
      margin-bottom: 20px;
    }
    .form-row label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: #333;
      font-size: 0.95rem;
    }
    .form-row textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-family: inherit;
      font-size: 0.95rem;
      resize: vertical;
      transition: border-color 0.3s;
    }
    .form-row textarea:focus {
      outline: none;
      border-color: #4f46e5;
    }
    .rating-input {
      display: flex;
      flex-direction: row-reverse;
      justify-content: flex-end;
      gap: 5px;
    }
    .rating-input input[type="radio"] {
      display: none;
    }
    .rating-input label {
      cursor: pointer;
      font-size: 2rem;
      color: #ddd;
      margin: 0;
      transition: color 0.2s;
    }
    .rating-input input[type="radio"]:checked ~ label,
    .rating-input label:hover,
    .rating-input label:hover ~ label {
      color: #fbbf24;
    }
    .file-input {
      width: 100%;
      padding: 12px;
      border: 2px dashed #ddd;
      border-radius: 6px;
      cursor: pointer;
      transition: border-color 0.3s;
      background: #f9fafb;
    }
    .file-input:hover {
      border-color: #4f46e5;
      background: #f3f4f6;
    }
    .btn-submit-review {
      background: linear-gradient(135deg, var(--berry) 0%, var(--berry-dark) 100%);
      color: white;
      padding: 12px 30px;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: var(--shadow-md);
      text-transform: uppercase;
      letter-spacing: 0.12em;
    }
    .btn-submit-review:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
      background: linear-gradient(135deg, var(--berry-dark) 0%, var(--berry) 100%);
    }
    .btn-submit-review:active {
      transform: translateY(0);
      box-shadow: var(--shadow-sm);
    }
    .section-title {
      margin-bottom: 20px;
      border-bottom: 2px solid #eee;
      padding-bottom: 10px;
    }
    .section-title h2 {
      font-size: 1.5rem;
      font-weight: 600;
      color: #333;
      margin: 0;
    }
  </style>

  <!-- FAQs SECTION -->
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
        <div style="font-size:21px;margin-bottom:8px;font-weight:700;letter-spacing:.18em;">
          DEVILIXIRS
          <span style="display:block;font-size:10px;letter-spacing:.25em;font-weight:400;">HERBAL&nbsp;CARE</span>
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

  <!-- Back to top -->
  <div class="back-top" onclick="window.scrollTo({top:0,behavior:'smooth'});">
    <i class="fa-solid fa-angle-up"></i>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // Tabs
      const tabs  = document.querySelectorAll('.tab-item');
      const panes = document.querySelectorAll('.tab-pane');

      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          tabs.forEach(t => t.classList.remove('active'));
          panes.forEach(p => p.classList.remove('active'));

          tab.classList.add('active');

          const target = tab.getAttribute('data-tab');
          const pane   = document.getElementById('tab-' + target);
          if (pane) pane.classList.add('active');
        });
      });

      // Image thumbs → change main image
      const mainImg = document.getElementById('mainProductImage');
      document.querySelectorAll('.thumb-item img').forEach(img => {
        img.addEventListener('click', () => {
          if (!mainImg) return;
          mainImg.src = img.src;
        });
      });

      // Quantity buttons
      const qtyInput = document.querySelector('.qty-input');
      document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          if (!qtyInput) return;
          let val = parseInt(qtyInput.value || '1', 10);
          if (isNaN(val) || val < 1) val = 1;
          if (btn.dataset.action === 'inc') {
            val++;
          } else if (btn.dataset.action === 'dec' && val > 1) {
            val--;
          }
          qtyInput.value = val;
        });
      });
    });
  </script>

</body>
</html>