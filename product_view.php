<?php
// product_view.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/subscription_plan_helper.php';

// ---- 1. PREVIEW MODE CHECK ----
$isPreview = false;
$productId = 0; // Initialize for both modes
$previewToken = isset($_GET['preview_token']) ? htmlspecialchars(trim($_GET['preview_token']), ENT_QUOTES, 'UTF-8') : null;

if ($previewToken && !empty($_SESSION['previews'][$previewToken])) {
    // PREVIEW MODE ACTIVE
    $isPreview = true;
    $previewData = $_SESSION['previews'][$previewToken];
    
    // Mock $product array matching DB structure
    $product = [
        'id' => 0, // Placeholder ID
        'name' => $previewData['title'] ?? 'Preview Product',
        'price' => $previewData['price'] ?? 0,
        'compare_price' => $previewData['compare_price'] ?? 0,
        'discount_percent' => $previewData['discount_percent'] ?? 0,
        'stock' => $previewData['stock'] ?? 10,
        'sku' => $previewData['sku'] ?? '',
        'short_description' => $previewData['short_desc'] ?? '',
        'description' => $previewData['description'] ?? '',
        'ingredients' => $previewData['ingredients'] ?? '',
        'how_to_use' => $previewData['how_to_use'] ?? '',
        'seo_keywords' => $previewData['seo_keywords'] ?? '',
        'cat_name' => 'Preview Category', // Could be fetched if needed
        'category_id' => $previewData['category_id'] ?? 0,
        'label_id' => $previewData['label_id'] ?? 0,
        // Images handled specially
        'images' => json_encode($previewData['preview_images'] ?? [])
    ];

    // Handle Label mocking if label_id is present
    if (!empty($previewData['label_id'])) {
         try {
            $stmtL = $pdo->prepare("SELECT name, color, text_color FROM product_labels WHERE id = ?");
            $stmtL->execute([$previewData['label_id']]);
            $lbl = $stmtL->fetch(PDO::FETCH_ASSOC);
            if ($lbl) {
                $product['label_name'] = $lbl['name'];
                $product['label_color'] = $lbl['color'];
                $product['label_text_color'] = $lbl['text_color'];
            }
         } catch(Exception $e){}
    }

} else {
    // ---- STANDARD PRODUCTION MODE ----
    
    // Get product ID from URL
    $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($productId <= 0) {
        // invalid id → redirect to shop
        header('Location: product.php');
        exit;
    }

    // ---- 2. Fetch product + category from DB ----
    try {
        // Check if product_labels table exists
        $hasLabelsTable = false;
        try {
            $pdo->query("SELECT 1 FROM product_labels LIMIT 0");
            $hasLabelsTable = true;
        } catch (PDOException $e) {
            $hasLabelsTable = false;
        }
        
        // Build SQL with or without labels
        if ($hasLabelsTable) {
            $stmt = $pdo->prepare("
                SELECT 
                    p.*,
                    c.id   AS category_id,
                    c.name AS cat_name,
                    pl.name AS label_name,
                    pl.color AS label_color,
                    pl.text_color AS label_text_color
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN product_labels pl ON pl.id = p.label_id AND pl.is_active = 1
                WHERE p.id = :id
                  AND p.is_active = 1
                LIMIT 1
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT 
                    p.*,
                    c.id   AS category_id,
                    c.name AS cat_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.id = :id
                  AND p.is_active = 1
                LIMIT 1
            ");
        }
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
}
// ---- 3. Fetch Product Reviews (Dynamic Count) ----
    $reviews = [];
    try {
        $stmtReviews = $pdo->prepare("
            SELECT *
            FROM product_reviews
            WHERE product_id = ? AND status = 'approved'
            ORDER BY created_at DESC
        ");
        $stmtReviews->execute([$productId]);
        $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $reviews = [];
    }
// ---- Fetch Variants with Override Data ----
$variants = [];
$variantFaqs = []; // Indexed by variant_id

if ($isPreview) {
    // ---- MOCK VARIANTS FROM SESSION ----
    $variantsRaw = json_decode($previewData['variants_json'] ?? '[]', true);
    
    foreach ($variantsRaw as $idx => $v) {
        // Build mock variant matching DB structure
        $mockV = [
            'id' => $idx + 999, // Fake ID
            'product_id' => 0,
            'is_active' => 1,
            'variant_name' => $v['name'] ?? 'Variant ' . ($idx+1),
            'price' => $v['price'] ?? 0,
            'compare_price' => $v['compare_price'] ?? 0,
            'discount_percent' => $v['discount_percent'] ?? 0,
            'stock' => $v['stock'] ?? 10,
            'sku' => $v['sku'] ?? '',
            'type' => $v['type'] ?? 'custom',
            'linked_product_id' => $v['linked_product_id'] ?? null,
            // Custom Overrides (mapped to standard columns mostly, but we use 'custom_' keys for JS)
            'custom_title' => $v['custom_title'] ?? ($v['customTitle'] ?? null),
            'custom_description' => $v['custom_description'] ?? ($v['customDesc'] ?? null),
            'short_description' => $v['short_description'] ?? ($v['shortDesc'] ?? null),
            'ingredients' => $v['ingredients'] ?? null,
            'how_to_use' => $v['how_to_use'] ?? ($v['howToUse'] ?? null),
            'meta_title' => $v['meta_title'] ?? ($v['metaTitle'] ?? null),
            'meta_description' => $v['meta_description'] ?? ($v['metaDesc'] ?? null),
            'images' => '[]' // TODO: handle variant images
        ];

        // Handle Linked Type Logic (Mock fetch if possible, or just skip specialized linking for speed)
        // Ideally we should fetch minimal data for linked products even in preview
        if ($mockV['type'] === 'linked' && !empty($mockV['linked_product_id'])) {
             try {
                 $stmtL = $pdo->prepare("SELECT name, price, compare_price, discount_percent, stock, sku, images, description, short_description, ingredients, how_to_use FROM products WHERE id = ?");
                 $stmtL->execute([$mockV['linked_product_id']]);
                 $lp = $stmtL->fetch(PDO::FETCH_ASSOC);
                 if ($lp) {
                    $mockV['price'] = $lp['price'];
                    $mockV['compare_price'] = $lp['compare_price'];
                    $mockV['discount_percent'] = $lp['discount_percent'];
                    $mockV['stock'] = $lp['stock'];
                    $mockV['sku'] = $lp['sku'];
                    $mockV['images'] = $lp['images'];

                    // Map overrides
                    $mockV['variant_name'] = $lp['name'];
                    $mockV['custom_title'] = $lp['name'];
                    $mockV['custom_description'] = $lp['description'];
                    $mockV['short_description'] = $lp['short_description'];
                    $mockV['ingredients'] = $lp['ingredients'];
                    $mockV['how_to_use'] = $lp['how_to_use'];
                 }
             } catch(Exception $e){}
        }

        $variants[] = $mockV;

        // Mock Variant FAQs
        if (!empty($v['faqs']) && is_array($v['faqs'])) {
             // Structure for $variantFaqs[$vid] = [ ['question'=>..., 'answer'=>...] ]
             $variantFaqs[$mockV['id']] = $v['faqs'];
        }
    }

    // Inject Main Product as First Variant (Same logic as DB mode)
    // ... logic duplicated below, so we'll fall through to shared logic

} else {
    // ---- STANDARD DB FETCH ----
    try {
        $stmtVar = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1 ORDER BY price ASC");
        
        // ... (existing DB fetch logic) ...
        $stmtVar->execute([$productId]);
        $variants = $stmtVar->fetchAll(PDO::FETCH_ASSOC);
    
        // Filter out variants that are linked but missing linked_product_id
        $variants = array_filter($variants, function($v) {
            if (isset($v['type']) && $v['type'] === 'linked' && empty($v['linked_product_id'])) {
                return false;
            }
            return true;
        });
    
        // Hydrate Linked Variants
        $linkedProductIds = [];
        foreach ($variants as $v) {
            if (isset($v['type']) && $v['type'] === 'linked' && !empty($v['linked_product_id'])) {
                $linkedProductIds[] = $v['linked_product_id'];
            }
        }
    
        $linkedProductsData = [];
        if (!empty($linkedProductIds)) {
            $linkedProductIds = array_unique($linkedProductIds);
            $placeholders = implode(',', array_fill(0, count($linkedProductIds), '?'));
            // FETCH ALL CONTENT FIELDS
            $stmtLinked = $pdo->prepare("SELECT id, name, price, compare_price, discount_percent, stock, sku, images, description, short_description, ingredients, how_to_use FROM products WHERE id IN ($placeholders)");
            $stmtLinked->execute($linkedProductIds);
            $rows = $stmtLinked->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $linkedProductsData[$r['id']] = $r;
            }
        }
    
        // Merge linked data into variants
        foreach ($variants as &$v) {
            if (isset($v['type']) && $v['type'] === 'linked' && !empty($v['linked_product_id'])) {
                $lid = $v['linked_product_id'];
                if (isset($linkedProductsData[$lid])) {
                    $lp = $linkedProductsData[$lid];
                    $v['price'] = $lp['price'];
                    $v['compare_price'] = $lp['compare_price'];
                    $v['discount_percent'] = $lp['discount_percent'];
                    $v['stock'] = $lp['stock'];
                    $v['sku'] = $lp['sku'];
                    $v['images'] = $lp['images']; 
                    
                    // Content Overrides
                    // FIXED: Do not overwrite variant_name if it was retrieved from DB (which is the custom name)
                    // If DB variant_name is somehow empty, then fallback (but save_product ensures it's not empty).
                    if (empty($v['variant_name'])) {
                        $v['variant_name'] = $lp['name'];
                    }
                    // For custom_title, we might want the Linked Product title IF the user didn't specify a custom title override.
                    // But typically custom_title maps to 'title' in products. 
                    // Let's assume if the user provided specific custom_title in variants table, use it.
                    if (empty($v['custom_title'])) {
                        $v['custom_title'] = $lp['name'];
                    }
                    $v['custom_description'] = $lp['description'];
                    $v['short_description'] = $lp['short_description'];
                    $v['ingredients'] = $lp['ingredients'];
                    $v['how_to_use'] = $lp['how_to_use'];
                }
            }
        }
        unset($v);
        
        // Fetch FAQs for all variants (DB Mode)
        // Done below in shared block or separate? 
        // Existing code had it after Main Variant injection. 
        // We will preserve flow.
        
    } catch (Exception $e) {
        $variants = [];
        $variantFaqs = [];
    }
} // End if($isPreview)

// ---- Shared Logic: Inject Main Product as First Variant ----
// ... (The rest of the file stays same)
$mainVariantName = $product['main_variant_name'] ?? '';
// Only show if there are other variants OR if a specific main name is set
if (!empty($variants) || !empty($mainVariantName)) {
    $mainVariant = [
        'id' => 0, // 0 indicates main product
        'variant_name' => $mainVariantName ?: 'Default',
        'price' => $product['price'],
        'compare_price' => $product['compare_price'],
        'discount_percent' => $product['discount_percent'],
        'stock' => $product['stock'],
        'sku' => $product['sku'],
        'image' => null, 
        'images' => null,
        // Ensure these keys exist for JS compat
        'custom_title' => null,
        'custom_description' => null,
        'short_description' => $product['short_description'] ?? '',
        'ingredients' => null,
        'how_to_use' => null
    ];
    array_unshift($variants, $mainVariant);
}

// ---- Variant FAQs Query (Only for DB mode, Session mode already built it) ----
if (!$isPreview && !empty($variants)) {
    // ... DB FAQ Logic ...
    try {
        $variantIds = array_column($variants, 'id');
        $dbVariantIds = array_filter($variantIds, function($id) { return $id > 0; });
        
        if (!empty($dbVariantIds)) {
            $placeholders = implode(',', array_fill(0, count($dbVariantIds), '?'));
            $stmtVarFaq = $pdo->prepare("SELECT * FROM variant_faqs WHERE variant_id IN ($placeholders) ORDER BY variant_id, display_order");
            $stmtVarFaq->execute(array_values($dbVariantIds));
            $allVarFaqs = $stmtVarFaq->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($allVarFaqs as $vfaq) {
                $vid = $vfaq['variant_id'];
                if (!isset($variantFaqs[$vid])) {
                    $variantFaqs[$vid] = [];
                }
                $variantFaqs[$vid][] = $vfaq;
            }
        }
    } catch(Exception $e) {}
}

// Auto-calculate prices (Shared)
if (!empty($variants)) {
    foreach ($variants as &$v) {
        $cp = isset($v['compare_price']) ? (float)$v['compare_price'] : 0;
        $dp = isset($v['discount_percent']) ? (float)$v['discount_percent'] : 0;
        if ($cp > 0 && $dp > 0 && (!isset($v['price']) || $v['price'] == 0)) {
             // Only auto-calc if price isn't explicitly set (or logic dictated by business rule)
             // However, normally price IS set. The view logic seemed to prefer calculated?
             // Existing code:
             // if ($cp > 0 && $dp > 0) { $v['price'] = ... }
             // We keep it to ensure parity.
             $v['price'] = $cp - ($cp * ($dp / 100));
        }
    }
    unset($v);
}

// ---- Fetch Active Subscription Plan ----
$subscriptionPlan = null;
try {
    ensure_subscription_schema($pdo);
    $subscriptionPlan = subscription_fetch_primary_plan($pdo, null, true);
} catch (Exception $e) {
    $subscriptionPlan = null;
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

// Use first variant's pricing data if variants exist, otherwise use main product
if (!empty($variants) && isset($variants[0])) {
    $firstVariant = $variants[0];
    $price         = isset($firstVariant['price']) ? (float)$firstVariant['price'] : 0;
    $comparePrice  = (isset($firstVariant['compare_price']) && $firstVariant['compare_price'] > 0)
                        ? (float)$firstVariant['compare_price']
                        : null;
    $discountPercent = isset($firstVariant['discount_percent']) ? (float)$firstVariant['discount_percent'] : null;
} else {
    // No variants - use main product pricing
    $price         = isset($product['price']) ? (float)$product['price'] : 0;
    $comparePrice  = (isset($product['compare_price']) && $product['compare_price'] > 0)
                        ? (float)$product['compare_price']
                        : null;
    $discountPercent = isset($product['discount_percent']) ? (float)$product['discount_percent'] : null;
}

// Auto-calculate price if compare price and discount exist
if ($comparePrice && $discountPercent && $discountPercent > 0) {
    $price = $comparePrice - ($comparePrice * ($discountPercent / 100));
}

// Ensure compare price is greater than price for display logic
if ($comparePrice && $comparePrice <= $price) {
    $comparePrice = null;
}

// Calculate savings if compare price exists
$savings = $comparePrice ? ($comparePrice - $price) : 0;

// Calculate dynamic ratings from reviews array
$ratingCount = count($reviews);
$ratingSum   = 0;
foreach ($reviews as $rev) {
    $ratingSum += (float)$rev['rating'];
}
$rating      = $ratingCount > 0 ? ($ratingSum / $ratingCount) : 0;
$ratingStars = $rating > 0 ? str_repeat('★', round($rating)) : '★★★★★';


$inStock       = isset($product['stock']) ? ((int)$product['stock'] > 0) : true;

// Short description: use short_desc if exists, else first part of description
$shortDesc = '';
if (!empty($product['short_description'])) {
    $shortDesc = $product['short_description'];
} elseif (!empty($product['description'])) {
    $shortDesc = mb_substr(strip_tags($product['description']), 0, 260) . '…';
}
// DEBUG removed

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

// Fetch Product Tags
$productTags = [];
try {
    $stmtTags = $pdo->prepare("
        SELECT t.id, t.name 
        FROM tags t
        JOIN product_tags pt ON t.id = pt.tag_id
        WHERE pt.product_id = ?
        ORDER BY t.name ASC
    ");
    $stmtTags->execute([$productId]);
    $productTags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $productTags = [];
}

// Images
$imageList = parse_product_images($product['images'] ?? '');
if (empty($imageList)) {
    $imageList = ['/assets/images/avatar-default.png']; // fallback
}
$mainImage   = $imageList[0];
$thumbImages = array_slice($imageList, 1, 3); // max 3 thumbs

// Fetch Product Reviews
// [Moved logic to top of file]

?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
// Include SEO helper
require_once __DIR__ . '/includes/seo_meta.php';

// Prepare product data for schema
$productUrl = 'https://develixirs.com/product_view.php?id=' . $productId;
$firstImage = !empty($imageList) ? 'https://develixirs.com/' . ltrim($imageList[0], '/') : 'https://develixirs.com/assets/images/product-default.jpg';

// Generate SEO meta tags
$seoKeywords = $productName . ', ayurvedic ' . strtolower($categoryName) . ', natural beauty products, herbal cosmetics';

// Add visible tags (from product_tags table)
if (!empty($productTags)) {
    $tagNames = array_column($productTags, 'name');
    $seoKeywords .= ', ' . implode(', ', $tagNames);
}

// Add hidden SEO keywords (from products.seo_keywords field)
if (!empty($product['seo_keywords'])) {
    $hiddenKeywords = array_filter(array_map('trim', explode(',', $product['seo_keywords'])));
    if (!empty($hiddenKeywords)) {
        $seoKeywords .= ', ' . implode(', ', $hiddenKeywords);
    }
}

echo generate_seo_meta([
    'title' => $productName . ' - DevElixir Natural Cosmetics',
    'description' => strip_tags($shortDesc) ?: ('Buy ' . $productName . ' online at DevElixir. Authentic ayurvedic beauty products with natural ingredients. Free shipping on orders ₹1000+'),
    'keywords' => $seoKeywords,
    'url' => $productUrl,
    'image' => $firstImage,
    'type' => 'product'
]);

// PREVIEW MODE SEO PROTECTION
if ($isPreview) {
    echo '<meta name="robots" content="noindex, nofollow">';
}


// Generate Product Schema
$productSchemaData = [
    'name' => $productName,
    'description' => strip_tags($shortDesc ?: $longDescription),
    'image' => $firstImage,
    'url' => $productUrl,
    'price' => $price,
    'sku' => $product['sku'] ?? ('PROD-' . $productId)
];

// Add ratings if available
if ($rating > 0 && $ratingCount > 0) {
    $productSchemaData['rating'] = $rating;
    $productSchemaData['review_count'] = $ratingCount;
}

echo generate_product_schema($productSchemaData);

// Generate Breadcrumb Schema
$breadcrumbs = [
    ['name' => 'Home', 'url' => 'https://develixirs.com/'],
    ['name' => $categoryName, 'url' => 'https://develixirs.com/shop.php?category=' . ($product['category_id'] ?? '')],
    ['name' => $productName, 'url' => $productUrl]
];
echo generate_breadcrumb_schema($breadcrumbs);
?>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <?php if ($isPreview): ?>
  <style>
    .preview-banner {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: #ff9800;
        color: #fff;
        text-align: center;
        padding: 8px;
        font-weight: bold;
        z-index: 9999;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    body { margin-top: 40px; } /* Push body down */
  </style>
  <?php endif; ?>

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
  max-width:1280px;
  margin:50px auto 70px;
  padding:0 20px;
  display:grid;
  grid-template-columns:1.2fr 1fr;
  gap:50px;
  animation:fadeInUp .6s ease;
  align-items: start; /* Prevent equal height stretching */
}

@keyframes fadeInUp {
  from { opacity:0; transform:translateY(30px); }
  to { opacity:1; transform:translateY(0); }
}

.product-media{
  background:#fff;
  border:1px solid var(--border);
  border-radius:16px;
  padding:24px;
  box-shadow:var(--shadow-md);
  transition:all .4s cubic-bezier(0.4, 0, 0.2, 1);
}
.product-media:hover{
  box-shadow:var(--shadow-xl);
  transform:translateY(-2px);
  border-color:#d0d0d0;
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

/* Product Label Badge on Main Image */
.product-label-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 10;
  padding: 6px 14px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  pointer-events: none;
}

/* Subscribe & Save Card */
.subscribe-save-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 24px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
  position: relative;
  overflow: hidden;
}
.subscribe-save-card::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
  pointer-events: none;
}
.subscribe-save-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}
.subscribe-save-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.subscribe-save-title {
  color: white;
  font-size: 20px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 10px;
}
.subscribe-save-icon {
  width: 32px;
  height: 32px;
  background: rgba(255,255,255,0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}
.subscribe-save-price {
  color: white;
  font-size: 24px;
  font-weight: 800;
  background: rgba(255,255,255,0.2);
  padding: 6px 16px;
  border-radius: 8px;
}
.subscribe-save-tagline {
  color: rgba(255,255,255,0.95);
  font-size: 14px;
  margin-bottom: 12px;
  font-weight: 500;
}
.subscribe-save-benefits {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.subscribe-save-benefit {
  color: rgba(255,255,255,0.9);
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.subscribe-save-benefit i {
  color: #ffd700;
  font-size: 12px;
}

/* Variant Cards CSS */
.variant-options-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-top: 16px;
}
.variant-card {
  border: 2px solid #e8e8e8;
  border-radius: 16px;
  padding: 22px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: #fff;
  text-align: left;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 130px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.variant-card:hover {
  border-color: #c0c0c0;
  box-shadow: 0 6px 16px rgba(0,0,0,0.1);
  transform: translateY(-4px);
}
.variant-card.active {
  border: 2.5px solid #3B502C;
  background: linear-gradient(135deg, #fafef9 0%, #ffffff 100%);
  box-shadow: 0 8px 20px rgba(59,80,44,0.15);
}
.variant-name {
  font-weight: 700;
  font-size: 17px;
  margin-bottom: 10px;
  color: #222;
  letter-spacing: 0.01em;
}
.variant-price-row {
  margin-bottom: 10px;
}
.variant-current-price {
  font-weight: 800;
  font-size: 24px;
  color: #A41B42;
}
.variant-stock {
  font-size: 13px;
  font-weight: 600;
  color: #2e7d32;
  margin-top: auto;
  font-style: italic;
}
.variant-stock.out-stock {
  color: #d32f2f;
}

/* Mobile: Horizontal scroll for variants */
@media (max-width: 768px) {
  .variant-options-grid {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    gap: 12px;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: 10px;
  }
  
  .variant-options-grid::-webkit-scrollbar {
    display: none;
  }
  
  .variant-card {
    flex: 0 0 75%;
    scroll-snap-align: start;
    min-height: 120px;
    padding: 18px;
  }
  
  .variant-name {
    font-size: 15px;
  }
  
  .variant-current-price {
    font-size: 20px;
  }
}
.product-main-image::after{
  content:"";
  position:absolute;
  inset:0;
  background:linear-gradient(180deg, transparent 60%, rgba(0,0,0,.05) 100%);
  pointer-events:none;
}
/* Main Product Video Constraint */
.product-main-image video{
  width: auto;
  max-width: 100%;
  height: auto; 
  max-height: 600px;
  object-fit: contain;
  display: none;
  background: #000;
  margin: 0 auto;
}
.product-main-image img{
  width: auto !important;
  max-width: 100% !important;
  height: auto;
  max-height: 600px;
  object-fit: contain;
  display: block;
  margin: 0 auto;
  cursor: zoom-in;
  transition: transform .5s ease;
}
.product-main-image:hover img{
  transition: none;
  cursor: crosshair;
}

/* Force override for main image ID to fix persistent sizing issues */
#mainProductImage {
  width: auto !important;
  max-width: 100% !important;
  height: auto !important;
  max-height: 600px !important;
  display: block !important;
  margin: 0 auto !important;
  object-fit: contain !important;
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

/* Fullscreen Mode Styles */
.product-main-image-wrapper:fullscreen {
  background: #000;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}
.product-main-image-wrapper:fullscreen .product-main-image {
  width: 100%;
  height: 100%;
  border: none;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
}
.product-main-image-wrapper:fullscreen img {
  max-width: 100%;
  max-height: 100%;
  width: auto;
  height: auto;
  object-fit: contain;
  transform: none !important;
  cursor: default;
}
.product-main-image-wrapper:fullscreen .fullscreen-btn {
  display: none;
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
  border-radius:16px;
  padding:30px;
  box-shadow:var(--shadow-md);
  transition:all .4s cubic-bezier(0.4, 0, 0.2, 1);
  position:sticky;
  top:20px;
}
.product-summary:hover{
  box-shadow:var(--shadow-xl);
  border-color:#d0d0d0;
}
.product-title{
  font-size:24px;
  letter-spacing:.05em;
  font-weight:700;
  line-height:1.3;
  margin-bottom:16px;
  background:linear-gradient(135deg, var(--highlight) 0%, var(--berry) 100%);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
  animation:titleGlow 3s ease-in-out infinite;
}

@keyframes titleGlow {
  0%, 100% { filter: brightness(1); }
  50% { filter: brightness(1.1); }
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

.price-section{
  margin:20px 0;
  padding:20px 0;
  border-top:2px solid #f0f0f0;
  border-bottom:2px solid #f0f0f0;
}

.discount-badge{
  display:inline-block;
  background:linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
  color:#fff;
  padding:6px 16px;
  border-radius:20px;
  font-size:14px;
  font-weight:700;
  margin-bottom:12px;
  letter-spacing:0.5px;
  box-shadow:0 2px 8px rgba(255,107,107,0.3);
  animation:pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform:scale(1); }
  50% { transform:scale(1.05); }
}

.price-row{
  display:flex;
  align-items:center;
  gap:12px;
  margin-bottom:10px;
}

.price-row .old-price{
  font-size:20px;
  text-decoration:line-through;
  color:#999;
  font-weight:500;
}

.price-row .current-price{
  font-size:36px;
  font-weight:800;
  color:var(--berry);
  animation:priceGlow 3s ease infinite;
}

@keyframes priceGlow {
  0%, 100% { text-shadow:0 0 0 rgba(164,27,66,0); }
  50% { text-shadow:0 0 12px rgba(164,27,66,.5); }
}

.savings-text{
  font-size:15px;
  color:#2e7d32;
  font-weight:500;
  background:#e8f5e9;
  padding:8px 16px;
  border-radius:8px;
  display:inline-block;
  border-left:4px solid #2e7d32;
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
  padding:14px 36px;
  border:none;
  background:linear-gradient(135deg, var(--berry) 0%, var(--berry-dark) 100%);
  color:#fff;
  font-size:14px;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:.12em;
  border-radius:8px;
  cursor:pointer;
  box-shadow:var(--shadow-md);
  transition:all .4s cubic-bezier(0.4, 0, 0.2, 1);
  position:relative;
  overflow:hidden;
}
.btn-add-cart::before{
  content:"";
  position:absolute;
  top:50%;
  left:50%;
  width:0;
  height:0;
  border-radius:50%;
  background:rgba(255,255,255,.2);
  transform:translate(-50%, -50%);
  transition:width .6s, height .6s;
}
.btn-add-cart:hover::before{
  width:300px;
  height:300px;
}
.btn-add-cart:hover{
  transform:translateY(-3px);
  box-shadow:var(--shadow-xl);
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
.share-btn.whatsapp:hover { color: #25D366; border-color: #25D366; background: rgba(37, 211, 102, 0.1); }
.share-btn.instagram:hover { color: #E1306C; border-color: #E1306C; background: rgba(225, 48, 108, 0.1); }
.share-btn.facebook:hover { color: #1877F2; border-color: #1877F2; background: rgba(24, 119, 242, 0.1); }
.share-btn.twitter:hover { color: #000000; border-color: #000000; background: rgba(0, 0, 0, 0.1); }

/* ============== DESCRIPTION TABS & SIDEBAR ============== */
.tabs-area{
  max-width:1280px;
  margin:60px auto;
  padding:0 20px;
  display:grid;
  grid-template-columns:2.5fr 1fr;
  gap:40px;
  animation:fadeInUp .8s ease;
}
.tabs-card{
  background:#fff;
  border:1px solid var(--border);
  border-radius:16px;
  box-shadow:var(--shadow-md);
  overflow:hidden;
  transition:all .4s cubic-bezier(0.4, 0, 0.2, 1);
}
.tabs-card:hover{
  box-shadow:var(--shadow-xl);
  border-color:#d0d0d0;
}
.tabs-header{
  display:flex;
  border-bottom:2px solid #f0f0f0;
  background:linear-gradient(to bottom, #fafafa, #ffffff);
  padding:0;
  overflow-x:auto;
  overflow-y:hidden;
  scrollbar-width:none;
}
.tabs-header::-webkit-scrollbar{
    display:none;
}
.tab-item{
    flex:1;
    padding:18px 24px;
    text-align:center;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
    border:none;
    transition:all .4s cubic-bezier(0.4, 0, 0.2, 1);
    color:var(--muted);
    background:transparent;
    position:relative;
    overflow:hidden;
}
.tab-item::after{
    content:"";
    position:absolute;
    bottom:0;
    left:50%;
    width:0;
    height:3px;
    background:linear-gradient(90deg, var(--highlight) 0%, var(--berry) 100%);
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
  display:none !important;
  animation: fadeInSlide .4s ease;
}
.tab-pane.active{
  display:block !important;
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
/* Rich Text / CKEditor Helpers */
.tab-pane ul {
  list-style-type: disc;
  margin-left: 20px;
  margin-bottom: 12px;
}
.tab-pane ol {
  list-style-type: decimal;
  margin-left: 20px;
  margin-bottom: 12px;
}
.tab-pane li {
  margin-bottom: 6px;
  line-height: 1.6;
}
.tab-pane strong, .tab-pane b {
  font-weight: 700;
  color: #333;
}
.tab-pane p:last-child {
  margin-bottom: 0;
}
/* Fix for CKEditor nested paragraphs in lists */
.tab-pane li p {
  margin: 0 !important;
  padding: 0 !important;
  display: inline-block; /* Keeps text with bullet */
}
/* Fix for empty bullets */
.tab-pane li {
  min-height: 1.2em;
  margin-bottom: 4px; /* Slight spacing between list items */
}
/* Ensure list indentation */
.tab-pane ul, .tab-pane ol {
  padding-left: 24px;
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
  background:#111;
  color:#e0e0e0;
  padding:50px 0 20px;
  font-family: 'Poppins', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
.footer-inner{
  max-width:1200px;
  margin:0 auto;
  padding:0 15px 20px;
  display:grid;
  grid-template-columns:repeat(4,minmax(0,1fr));
  gap:30px;
}
.footer-title{
  font-size:15px;
  font-weight:600;
  margin-bottom:12px;
  text-transform:uppercase;
  letter-spacing:0.5px;
  color:#fff;
  font-family: 'Poppins', sans-serif;
}
.footer-links{
  list-style:none;
  padding:0;
  margin:0;
}
.footer-links li{
  font-size:13px;
  margin-bottom:8px;
  color:#ccc;
  font-family: 'Poppins', sans-serif;
  line-height: 1.6;
}
.footer-links a{
  color:#ccc;
  text-decoration:none;
  transition:all .3s ease;
  font-family: 'Poppins', sans-serif;
}
.footer-links a:hover{
  color:#D4AF37;
}
.footer-bottom{
  border-top:1px solid #333;
  margin-top:10px;
  padding:16px 15px;
  max-width:1200px;
  margin-left:auto;
  margin-right:auto;
  display:flex;
  justify-content:space-between;
  align-items:center;
  font-size:12px;
  color:#bbb;
  font-family: 'Poppins', sans-serif;
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
    gap: 25px;
    margin: 30px auto 50px;
    padding: 0 15px;
  }
  
  /* Fix product-media for mobile */
  .product-media {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 0;
  }
  
  .product-main-image-wrapper {
    border-radius: 8px;
  }
  
  .product-main-image {
    border-radius: 8px;
  }
  
  .product-main-image img {
    max-height: 400px;
    width: auto;
    max-width: 100%;
    margin: 0 auto;
    object-fit: contain;
    background: #fff;
  }
  
  .thumb-gallery {
    gap: 6px;
    margin-top: 12px;
  }
  
  .thumb-item {
    flex: 0 0 calc(25% - 5px);
    min-width: 70px;
    border-width: 2px;
  }
  
  .thumb-item img {
    height: 75px;
  }
  
  /* Image navigation buttons - hide on small mobile */
  .image-nav-btn {
    width: 35px;
    height: 35px;
  }
  
  .image-nav-btn i {
    font-size: 14px;
  }
  
  .prev-btn {
    left: 8px;
  }
  
  .next-btn {
    right: 8px;
  }
  
  /* Fix product-summary for mobile */
  .product-summary {
    padding: 18px;
    border-radius: 8px;
    position: static;
    margin-top: 20px;
  }
  
  .product-title {
    font-size: 18px;
    line-height: 1.4;
    margin-bottom: 12px;
  }
  
  .product-meta {
    font-size: 11px;
    margin-bottom: 8px;
  }
  
  .rating-row {
    font-size: 11px;
    margin-bottom: 6px;
  }
  
  .rating-stars {
    font-size: 11px;
  }
  
  .stock {
    font-size: 11px;
    margin-bottom: 10px;
  }
  
  .price-section {
    margin: 15px 0;
    padding: 15px 0;
  }
  
  .price-row .current-price {
    font-size: 26px;
  }
  
  .price-row .old-price {
    font-size: 16px;
  }
  
  .discount-badge {
    font-size: 11px;
    padding: 4px 10px;
    margin-bottom: 10px;
  }
  
  .savings-text {
    font-size: 12px;
    padding: 6px 12px;
  }
  
  .short-desc {
    font-size: 12px;
    line-height: 1.6;
    margin-bottom: 14px;
    padding-top: 12px;
  }
  
  .option-label {
    font-size: 12px;
    margin-bottom: 5px;
  }
  
  /* Action buttons */
  .action-row {
    gap: 8px;
  }
  
  .btn-add-cart {
    font-size: 13px;
    padding: 12px 20px;
  }
  
  .btn-buy-now {
    font-size: 13px;
    padding: 12px 20px;
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

  .footer-bottom{
    flex-direction:column;
    text-align:center;
    gap:10px;
  }
}

/* Extra small mobile (iPhone 12 Pro and similar - 390px) */
@media (max-width: 480px) {
  .page-wrap {
    padding: 0 10px;
    margin: 20px auto 40px;
    gap: 15px;
  }
  
  .product-media {
    padding: 10px;
  }
  
  .product-main-image img {
    max-height: 300px;
  }
  
  .thumb-item {
    flex: 0 0 calc(25% - 4px);
    min-width: 65px;
  }
  
  .thumb-item img {
    height: 65px;
  }
  
  .product-summary {
    padding: 15px;
    margin-top: 15px;
  }
  
  .product-title {
    font-size: 16px;
    margin-bottom: 10px;
  }
  
  .product-meta {
    font-size: 10px;
  }
  
  .rating-row {
    font-size: 10px;
  }
  
  .rating-stars {
    font-size: 10px;
  }
  
  .stock {
    font-size: 10px;
  }
  
  .price-section {
    margin: 12px 0;
    padding: 12px 0;
  }
  
  .price-row .current-price {
    font-size: 24px;
  }
  
  .price-row .old-price {
    font-size: 14px;
  }
  
  .discount-badge {
    font-size: 10px;
    padding: 3px 8px;
  }
  
  .savings-text {
    font-size: 11px;
    padding: 5px 10px;
  }
  
  .short-desc {
    font-size: 11px;
    line-height: 1.5;
  }
  
  .option-label {
    font-size: 11px;
  }
  
  .btn-add-cart,
  .btn-buy-now {
    font-size: 12px;
    padding: 10px 16px;
  }
  
  .variant-card {
    flex: 0 0 85%;
    padding: 15px;
  }
  
  .variant-name {
    font-size: 14px;
  }
  
  .variant-current-price {
    font-size: 18px;
  }
}

/* Very small mobile (360px - compact Android phones) */
@media (max-width: 390px) {
  .page-wrap {
    padding: 0 8px;
    margin: 15px auto 30px;
    gap: 12px;
  }
  
  .product-media {
    padding: 8px;
  }
  
  .product-main-image img {
    max-height: 280px;
  }
  
  .thumb-item {
    flex: 0 0 calc(25% - 3px);
    min-width: 60px;
  }
  
  .thumb-item img {
    height: 60px;
  }
  
  .image-nav-btn {
    width: 30px;
    height: 30px;
  }
  
  .image-nav-btn i {
    font-size: 12px;
  }
  
  .product-summary {
    padding: 12px;
    margin-top: 12px;
  }
  
  .product-title {
    font-size: 15px;
    margin-bottom: 8px;
  }
  
  .price-section {
    margin: 10px 0;
    padding: 10px 0;
  }
  
  .price-row .current-price {
    font-size: 22px;
  }
  
  .price-row .old-price {
    font-size: 13px;
  }
  
  .discount-badge {
    font-size: 9px;
    padding: 3px 6px;
  }
  
  .savings-text {
    font-size: 10px;
    padding: 4px 8px;
  }
  
  .short-desc {
    font-size: 10px;
    line-height: 1.4;
    margin-bottom: 10px;
  }
  
  .btn-add-cart,
  .btn-buy-now {
    font-size: 11px;
    padding: 9px 14px;
  }
  
  .variant-card {
    flex: 0 0 90%;
    padding: 12px;
    min-height: 110px;
  }
  
  .variant-name {
    font-size: 13px;
  }
  
  .variant-current-price {
    font-size: 16px;
  }
  
  .variant-stock {
    font-size: 11px;
  }
}

/* Rich Content Styles (Tables, Lists, etc.) */
.rich-content table {
  width: 100%;
  border-collapse: collapse;
  margin: 1em 0;
  font-size: 14px;
}
.rich-content table th, 
.rich-content table td {
  border: 1px solid var(--border);
  padding: 8px 12px;
  text-align: left;
}
.rich-content table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: var(--text);
}
.rich-content ul, .rich-content ol {
  margin-left: 20px;
  margin-bottom: 1em;
}
.rich-content ul { list-style: disc; }
.rich-content ol { list-style: decimal; }
.rich-content li { margin-bottom: 0.5em; }

.rich-content img {
  max-width: 100%;
  height: auto;
  border-radius: 4px;
  display: block;
  margin: 10px 0;
}
.rich-content blockquote {
  border-left: 4px solid var(--primary);
  padding-left: 16px;
  margin: 1em 0;
  color: var(--muted);
  font-style: italic;
}
.rich-content p {
  margin-bottom: 1em;
  line-height: 1.6;
}
.rich-content br {
  display: block; /* Ensure line breaks are respected */
  content: " "; /* Fix for some browsers ignoring empty br */
  margin-bottom: 0.5em;
}
</style>
</head>
<body>
<?php if ($isPreview): ?>
    <div class="preview-banner">
        ⚠️ PREVIEW MODE - Changes are not saved
    </div>
<?php endif; ?>


  <!-- 🔹 Shared navbar from your project -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- HERO / BREADCRUMB -->


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
        
        <!-- Main Image        
        <!-- Main Image/Video Area -->
        <div class="product-main-image">
          <?php if (!empty($product['label_name'])): ?>
            <span class="product-label-badge" style="background-color: <?php echo htmlspecialchars($product['label_color'] ?? '#000000', ENT_QUOTES); ?>; color: <?php echo htmlspecialchars($product['label_text_color'] ?? '#FFFFFF', ENT_QUOTES); ?>;">
              <?php echo htmlspecialchars($product['label_name'], ENT_QUOTES); ?>
            </span>
          <?php endif; ?>
          <?php 
            $ext = pathinfo($mainImage, PATHINFO_EXTENSION);
            $isMainVideo = in_array(strtolower($ext), ['mp4', 'webm', 'ogg', 'mov']);
          ?>
          <img id="mainProductImage"
               src="<?php echo htmlspecialchars($mainImage, ENT_QUOTES); ?>"
               alt="<?php echo htmlspecialchars($productName, ENT_QUOTES); ?>"
               style="display: <?php echo $isMainVideo ? 'none' : 'block'; ?>;">
          
          <video id="mainProductVideo"
                 src="<?php echo $isMainVideo ? htmlspecialchars($mainImage, ENT_QUOTES) : ''; ?>"
                 controls autoplay muted loop playsinline webkit-playsinline disablepictureinpicture
                 controlsList="nodownload"
                 style="/* CSS handles size: .product-main-image video */ display: <?php echo $isMainVideo ? 'block' : 'none'; ?>;">
          </video>
        </div>
        
        <!-- Fullscreen Button -->
        <button class="fullscreen-btn" id="fullscreenBtn">
          <i class="fa-solid fa-expand"></i>
        </button>
      </div>
      
      <!-- Thumbnail Gallery -->
      <div class="thumb-gallery">
        <?php foreach ($imageList as $index => $img): 
             $tExt = pathinfo($img, PATHINFO_EXTENSION);
             $isTVideo = in_array(strtolower($tExt), ['mp4', 'webm', 'ogg', 'mov']);
        ?>
          <div class="thumb-item <?php echo $index === 0 ? 'active' : ''; ?>" 
               data-index="<?php echo $index; ?>"
               data-image="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>">
             <?php if ($isTVideo): ?>
                <video src="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>" class="pointer-events-none" muted playsinline style="width:100%; height:100%; object-fit:cover;"></video>
                <div class="absolute inset-0 flex items-center justify-center bg-black/20 pointer-events-none" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.2);">
                    <i class="fa-solid fa-play" style="color:white; font-size:20px; text-shadow:0 2px 4px rgba(0,0,0,0.5);"></i>
                </div>
             <?php else: ?>
                <img src="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>" alt="Product media <?php echo $index + 1; ?>">
             <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- ONLY Buttons on Left (Moved to Left Column) -->
      <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #f0f0f0; display:flex; flex-wrap:wrap; gap:12px;">
        <!-- Add to Cart Button -->
        <button class="btn-add-cart" type="button" data-product-id="<?php echo $productId; ?>" style="background:linear-gradient(135deg, var(--berry) 0%, var(--berry-dark) 100%); color:#fff; border:none; padding:10px 24px; border-radius:30px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; box-shadow:0 4px 10px rgba(164,27,66,0.2);">
          <i class="fa-solid fa-bag-shopping"></i> Add To Cart
        </button>
        
        <!-- Buy Now Button -->
        <button class="btn-buy-now" type="button" data-product-id="<?php echo $productId; ?>" style="background:var(--primary); color:#fff; border:none; padding:10px 24px; border-radius:30px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; box-shadow:0 4px 10px rgba(212,175,55,0.3);">
          <i class="fa-solid fa-bolt"></i> Buy Now
        </button>
      </div>

        <script>
        // Quantity Logic
        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.qty-input');
                let val = parseInt(input.value);
                if(this.dataset.action === 'inc') val++;
                else if(this.dataset.action === 'dec' && val > 1) val--;
                input.value = val;
            });
        });

        // Buy Now Logic (Direct Redirect)
        document.querySelector('.btn-buy-now').addEventListener('click', function() {
            const btn = this;
            const pid = btn.dataset.productId;
            const qty = document.querySelector('.qty-input').value;

            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;

            // Direct redirect to checkout with params
            window.location.href = `checkout.php?source=direct_buy&product_id=${pid}&quantity=${qty}`;
        });
        </script>
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

      <div class="price-section"
           data-unit-price="<?php echo number_format($price, 2, '.', ''); ?>"
           data-unit-compare-price="<?php echo number_format((float)$comparePrice, 2, '.', ''); ?>"
           data-discount-percent="<?php echo number_format((float)$discountPercent, 2, '.', ''); ?>">
        <?php if ($discountPercent && $discountPercent > 0): ?>
          <div class="discount-badge">
            <?php echo number_format($discountPercent, 0); ?>% OFF
          </div>
        <?php endif; ?>
        
        <div class="price-row">
          <?php if ($comparePrice): ?>
            <span class="old-price">₹<?php echo number_format($comparePrice, 2); ?></span>
          <?php endif; ?>
          <span class="current-price">₹<?php echo number_format($price, 2); ?></span>
        </div>
        
        <?php if ($savings > 0): ?>
          <div class="savings-text">
            You Save: <strong>₹<?php echo number_format($savings, 2); ?></strong>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($shortDesc)): ?>
        <div class="short-desc">
          <?php echo $shortDesc; ?>
        </div>
      <?php endif; ?>

      <!-- Subscribe & Save Card -->
      <?php if ($subscriptionPlan): ?>
        <a href="subscription.php" class="subscribe-save-card" style="text-decoration:none; display:block;">
          <div class="subscribe-save-header">
            <div class="subscribe-save-title">
              <div class="subscribe-save-icon">
                <i class="fa-solid fa-crown"></i>
              </div>
              Subscribe & Save
            </div>
            <div class="subscribe-save-price">
              ₹<?php echo number_format($subscriptionPlan['price'], 0); ?>
            </div>
          </div>
          <div class="subscribe-save-tagline">
            Get <?php echo number_format($subscriptionPlan['discount_percentage'], 0); ?>% OFF on all future purchases!
          </div>
          <div class="subscribe-save-benefits">
            <div class="subscribe-save-benefit">
              <i class="fa-solid fa-check-circle"></i>
              Exclusive subscriber-only discounts
            </div>
            <div class="subscribe-save-benefit">
              <i class="fa-solid fa-check-circle"></i>
              Early access to new products
            </div>
            <div class="subscribe-save-benefit">
              <i class="fa-solid fa-check-circle"></i>
              Save more on every order
            </div>
          </div>
        </a>
      <?php endif; ?>


      <!-- Variants / Size -->

      <!-- Quantity Selector (Right Side) -->
      <div class="quantity-row" style="margin: 20px 0; display:flex; gap:12px; align-items:center;">
        <span class="qty-label" style="font-weight:600; color:var(--text-light);">Quantity</span>
        <div class="qty-box" style="display:flex; align-items:center; border:1px solid #ddd; border-radius:6px; overflow:hidden;">
          <button type="button" class="qty-btn" data-action="dec" style="width:32px; height:32px; border:none; background:#f9f9f9; cursor:pointer;">−</button>
          <input class="qty-input" type="text" value="1" name="quantity" style="width:40px; text-align:center; border:none; outline:none; font-weight:600;">
          <button type="button" class="qty-btn" data-action="inc" style="width:32px; height:32px; border:none; background:#f9f9f9; cursor:pointer;">+</button>
        </div>
      </div>

      <?php if (!empty($variants)): ?>
        <div class="mb-4">
          <div class="option-label">Select <?php echo htmlspecialchars($product['variant_label'] ?? 'Size'); ?></div>
          <div class="variant-options-grid">
            <?php foreach ($variants as $idx => $v): 
              // Prepare variant data using helper (handles JSON/legacy/linked paths correctly)
              $vImages = parse_product_images($v['images'] ?? '');
              
              if (empty($vImages) && !empty($v['image'])) {
                 // Fallback to legacy single image column if valid
                 $vImages = parse_product_images($v['image']);
              }
              
              // Ensure we have root-relative paths for the frontend
              $vImagesUrls = array_map(function($img) {
                if (preg_match('#^https?://#i', $img)) return $img;
                return '/' . ltrim($img, '/');
              }, $vImages);
              
              $vFaqs = $variantFaqs[$v['id']] ?? [];
              
              // Hide variants beyond index 2 (show first 3: 0, 1, 2)
              $isHidden = $idx >= 3 ? 'style="display:none;"' : '';
              $hiddenClass = $idx >= 3 ? 'hidden-variant' : '';
            ?>
              <div class="variant-card <?php echo $idx === 0 ? 'active' : ''; ?> <?php echo $hiddenClass; ?>" <?php echo $isHidden; ?>
                      data-id="<?php echo $v['id']; ?>"
                      data-price="<?php echo $v['price']; ?>"
                      data-compare-price="<?php echo $v['compare_price'] ?? ''; ?>"
                      data-discount-percent="<?php echo $v['discount_percent'] ?? ''; ?>"
                      data-stock="<?php echo $v['stock']; ?>"
                      data-sku="<?php echo htmlspecialchars($v['sku']); ?>"
                      data-image="<?php echo !empty($v['image']) ? '/assets/uploads/products/' . htmlspecialchars($v['image']) : ''; ?>"
                      data-custom-title="<?php echo htmlspecialchars($v['custom_title'] ?? '', ENT_QUOTES); ?>"
                      data-custom-description="<?php echo htmlspecialchars($v['custom_description'] ?? '', ENT_QUOTES); ?>"
                      data-short-description="<?php echo htmlspecialchars($v['short_description'] ?? '', ENT_QUOTES); ?>"
                      data-ingredients="<?php echo htmlspecialchars($v['ingredients'] ?? '', ENT_QUOTES); ?>"
                      data-how-to-use="<?php echo htmlspecialchars($v['how_to_use'] ?? '', ENT_QUOTES); ?>"
                      data-meta-title="<?php echo htmlspecialchars($v['meta_title'] ?? '', ENT_QUOTES); ?>"
                      data-meta-description="<?php echo htmlspecialchars($v['meta_description'] ?? '', ENT_QUOTES); ?>"
                      data-images="<?php echo htmlspecialchars(json_encode($vImagesUrls), ENT_QUOTES); ?>"
                      data-short-description="<?php echo htmlspecialchars($v['short_description'] ?? '', ENT_QUOTES); ?>"
                      data-ingredients="<?php echo htmlspecialchars($v['ingredients'] ?? '', ENT_QUOTES); ?>"
                      data-how-to-use="<?php echo htmlspecialchars($v['how_to_use'] ?? '', ENT_QUOTES); ?>"
                      data-meta-title="<?php echo htmlspecialchars($v['meta_title'] ?? '', ENT_QUOTES); ?>"
                      data-meta-description="<?php echo htmlspecialchars($v['meta_description'] ?? '', ENT_QUOTES); ?>"
                      data-faqs="<?php echo htmlspecialchars(json_encode($vFaqs), ENT_QUOTES); ?>">

                
                <div class="variant-name"><?php echo htmlspecialchars($v['variant_name']); ?></div>
                
                <div class="variant-price-row">
                  <span class="variant-current-price">₹<?php echo number_format($v['price']); ?></span>
                </div>
                
                <div class="variant-stock <?php echo ($v['stock'] <= 0) ? 'out-stock' : ''; ?>">
                  <?php echo ($v['stock'] > 0) ? 'In stock' : 'Out of stock'; ?>
                </div>

              </div>
            <?php endforeach; ?>
            
            <?php if (count($variants) > 3): ?>
              <button type="button" id="toggleVariantsBtn" class="variant-toggle-btn" style="
                  display: inline-flex; 
                  align-items: center; 
                  justify-content: center; 
                  padding: 4px 12px; 
                  border: 1px dashed #ccc; 
                  border-radius: 6px; 
                  background: #f9f9f9; 
                  cursor: pointer; 
                  font-size: 12px; 
                  color: #555; 
                  font-weight: 500;
                  height: 36px;
                  min-height: 0;
                  align-self: center; /* Prevent flex stretching */
                  transition: all 0.2s ease;
              ">
                See More +
              </button>
            <?php endif; ?>
          </div>
          
          <?php if (count($variants) > 3): ?>
            <script>
            document.getElementById('toggleVariantsBtn').addEventListener('click', function() {
              const hiddenVariants = document.querySelectorAll('.hidden-variant');
              const isExpanded = this.getAttribute('data-expanded') === 'true';
              
              if (!isExpanded) {
                // Expand
                hiddenVariants.forEach(el => el.style.display = 'block'); // or flex/grid item behavior
                this.innerHTML = 'See Less -';
                this.setAttribute('data-expanded', 'true');
                // Move button to end (flex/grid flow handles this if it's the last child)
              } else {
                // Collapse
                hiddenVariants.forEach(el => el.style.display = 'none');
                this.innerHTML = 'See More +';
                this.setAttribute('data-expanded', 'false');
              }
            });
            </script>
          <?php endif; ?>
          <input type="hidden" id="selectedVariantId" name="variant_id" value="<?php echo $variants[0]['id']; ?>">
        </div>
      <?php endif; ?>

      <!-- JS for Variants with Dynamic Switching -->
      <script>
        document.addEventListener("DOMContentLoaded", function() {
          const variantBtns = document.querySelectorAll('.variant-card');
          const priceDisplay = document.querySelector('.price-section');
          const stockDisplay = document.querySelector('.stock');
          const variantInput = document.getElementById('selectedVariantId');
          
          // Store default product data for fallback
          const defaultData = {
            title: '<?php echo addslashes($productName); ?>',
            description: `<?php echo addslashes($longDescription); ?>`,
            ingredients: `<?php echo addslashes($ingredientsText); ?>`,
            howToUse: `<?php echo addslashes($howToUseText); ?>`,
            metaTitle: `<?php echo addslashes($productName); ?>`, // Default to product name
            metaDescription: `<?php echo addslashes(strip_tags($shortDesc)); ?>`, // Default to short desc
            images: <?php echo json_encode($imageList); ?>.map(img => img.startsWith('/') ? img : '/' + img),
            faqs: <?php echo json_encode($faqs); ?>
          };

          function normalizeQuantity(rawValue) {
            const parsedQty = parseInt(rawValue, 10);
            return Number.isFinite(parsedQty) && parsedQty > 0 ? parsedQty : 1;
          }

          function setUnitPriceData(unitPrice, unitComparePrice, discountPercent) {
            if (!priceDisplay) return;

            const normalizedUnitPrice = Number.isFinite(unitPrice) ? unitPrice : 0;
            const normalizedUnitComparePrice = Number.isFinite(unitComparePrice) && unitComparePrice > 0 ? unitComparePrice : 0;
            const normalizedDiscountPercent = Number.isFinite(discountPercent) && discountPercent > 0 ? discountPercent : 0;

            priceDisplay.dataset.unitPrice = normalizedUnitPrice.toFixed(2);
            priceDisplay.dataset.unitComparePrice = normalizedUnitComparePrice.toFixed(2);
            priceDisplay.dataset.discountPercent = normalizedDiscountPercent.toString();
          }

          function refreshPriceDisplayByQuantity() {
            if (!priceDisplay) return;

            const qtyInput = document.querySelector('.qty-input');
            const quantity = normalizeQuantity(qtyInput ? qtyInput.value : 1);

            if (qtyInput) {
              qtyInput.value = quantity;
            }

            const unitPrice = parseFloat(priceDisplay.dataset.unitPrice) || 0;
            const unitComparePrice = parseFloat(priceDisplay.dataset.unitComparePrice) || 0;
            const discountPercent = parseFloat(priceDisplay.dataset.discountPercent) || 0;

            const totalPrice = unitPrice * quantity;
            const totalComparePrice = unitComparePrice > 0 ? unitComparePrice * quantity : 0;
            const savings = totalComparePrice > 0 ? Math.max(totalComparePrice - totalPrice, 0) : 0;

            let html = '';
            if (discountPercent > 0) {
              html += `<div class="discount-badge">${Math.round(discountPercent)}% OFF</div>`;
            }

            html += `<div class="price-row">`;
            if (totalComparePrice > 0) {
              html += `<span class="old-price">₹${totalComparePrice.toFixed(2)}</span>`;
            }
            html += `<span class="current-price">₹${totalPrice.toFixed(2)}</span>`;
            html += `</div>`;

            if (savings > 0) {
              html += `<div class="savings-text">You Save: <strong>₹${savings.toFixed(2)}</strong></div>`;
            }

            priceDisplay.innerHTML = html;
          }

          window.refreshProductPriceSection = refreshPriceDisplayByQuantity;

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
              
              // Define tab elements
              const descTab = document.getElementById('tab-desc');
              const ingTab = document.getElementById('tab-ing');
              const useTab = document.getElementById('tab-use');
              
              // Update active class
              variantBtns.forEach(b => b.classList.remove('active'));
              btn.classList.add('active');

              // Get variant data (with fallback to product data)
              const customTitle = btn.dataset.customTitle || defaultData.title;
              const customDesc = btn.dataset.customDescription || defaultData.description;
              const shortDesc = btn.dataset.shortDescription || '';
              const ingredients = btn.dataset.ingredients || defaultData.ingredients;
              const howToUse = btn.dataset.howToUse || defaultData.howToUse;
              const metaTitle = btn.dataset.metaTitle || defaultData.metaTitle;
              const metaDescription = btn.dataset.metaDescription || defaultData.metaDescription;
              const variantImages = safeParse(btn.dataset.images, defaultData.images);
              const variantFaqs = safeParse(btn.dataset.faqs, defaultData.faqs);

              // Update Title (both locations!)
              const titleElement = document.querySelector('.product-hero h1');
              const productTitleElement = document.querySelector('.product-title');
              
              if (titleElement && customTitle) {
                titleElement.textContent = customTitle;
              }
              if (productTitleElement && customTitle) {
                productTitleElement.textContent = customTitle;
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
              if (descTab && customDesc) {
                descTab.innerHTML = customDesc;
              }

              // Update Ingredients (already declared above)
              if (ingTab) {
                if (ingredients) {
                  ingTab.innerHTML = ingredients;
                } else {
                  ingTab.innerHTML = '<p>Ingredients information will be updated soon.</p>';
                }
              }

              // Update How to Use (already declared above)
              if (useTab) {
                if (howToUse) {
                  useTab.innerHTML = howToUse;
                } else {
                  useTab.innerHTML = '<p>Usage instructions will be updated soon.</p>';
                }
              }

              // Update SEO Title & Description
              if (metaTitle) {
                document.title = 'Devilixirs – ' + metaTitle;
              }
              if (metaDescription) {
                const metaDescTag = document.querySelector('meta[name="description"]');
                if (metaDescTag) {
                  metaDescTag.setAttribute('content', metaDescription);
                }
              }

              // Update Images with fallback
              let finalImages = variantImages;
              if (!finalImages || finalImages.length === 0) {
                finalImages = defaultData.images;
              }
              updateImageGallery(finalImages);

              // Update FAQs
              updateFAQs(Array.isArray(variantFaqs) && variantFaqs.length > 0 ? variantFaqs : defaultData.faqs);

              // Update Price, Compare Price, Discount & Stock
              let price = parseFloat(btn.dataset.price);
              const comparePrice = parseFloat(btn.dataset.comparePrice) || 0;
              const discountPercent = parseFloat(btn.dataset.discountPercent) || 0;
              
              // Auto-calculate price if compare price and discount exist
              if (comparePrice > 0 && discountPercent > 0) {
                  price = comparePrice - (comparePrice * (discountPercent / 100));
              }

              setUnitPriceData(price, comparePrice, discountPercent);
              refreshPriceDisplayByQuantity();

              
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
              const mainImage = document.getElementById('mainProductImage');
              const mainVideo = document.getElementById('mainProductVideo');
              const thumbGallery = document.querySelector('.thumb-gallery');
              
              // Helper to check video extension
              const isVideoUrl = (url) => {
                  const ext = url.split('.').pop().toLowerCase();
                  return ['mp4', 'webm', 'ogg', 'mov'].includes(ext);
              };

              // Update initial main view
              if (images.length > 0) {
                  const firstUrl = images[0];
                  if (isVideoUrl(firstUrl)) {
                      if (mainImage) mainImage.style.display = 'none';
                      if (mainVideo) {
                          mainVideo.src = firstUrl;
                          mainVideo.style.display = 'block';
                          mainVideo.play().catch(e => console.log('Autoplay prevented'));
                      }
                  } else {
                      if (mainVideo) {
                          mainVideo.style.display = 'none';
                          mainVideo.pause();
                      }
                      if (mainImage) {
                          mainImage.src = firstUrl;
                          mainImage.style.display = 'block';
                      }
                  }
              }
              
              if (thumbGallery) {
                thumbGallery.innerHTML = '';
                images.forEach((img, index) => {
                  const isVideo = isVideoUrl(img);
                  const thumbItem = document.createElement('div');
                  thumbItem.className = 'thumb-item' + (index === 0 ? ' active' : '');
                  thumbItem.setAttribute('data-index', index);
                  thumbItem.setAttribute('data-image', img);
                  
                  if (isVideo) {
                      thumbItem.innerHTML = `
                        <video src="${img}" muted playsinline style="width:100%; height:100%; object-fit:cover; pointer-events:none;"></video>
                        <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.2);">
                            <i class="fa-solid fa-play" style="color:white; font-size:20px; text-shadow:0 2px 4px rgba(0,0,0,0.5);"></i>
                        </div>
                      `;
                  } else {
                      thumbItem.innerHTML = `<img src="${img}" alt="Thumbnail ${index + 1}">`;
                  }

                  thumbItem.addEventListener('click', function() {
                    // Switch main media
                    if (isVideoUrl(img)) {
                        if (mainImage) mainImage.style.display = 'none';
                        if (mainVideo) {
                            mainVideo.src = img;
                            mainVideo.style.display = 'block';
                            mainVideo.play().catch(e=>{});
                        }
                    } else {
                        if (mainVideo) {
                            mainVideo.style.display = 'none';
                            mainVideo.pause();
                        }
                        if (mainImage) {
                            mainImage.src = img;
                            mainImage.style.display = 'block';
                        }
                    }

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

          refreshPriceDisplayByQuantity();
        });


        // Image Zoom Logic
        const mainImageContainer = document.querySelector('.product-main-image');
        const mainImage = document.getElementById('mainProductImage');
        const mainVideo = document.getElementById('mainProductVideo');

        // Add click handler for main video fullscreen
        if (mainVideo) {
          mainVideo.addEventListener('click', function(e) {
            const videoSrc = this.getAttribute('src');
            if (videoSrc) {
              openLightbox('video', videoSrc);
            }
          });
          mainVideo.style.cursor = 'pointer';
        }

        // Make ALL videos on the page clickable for fullscreen (including rich text content)
        // Use capturing phase to intercept clicks before video controls handle them
        document.addEventListener('click', function(e) {
          // Check if clicked element is a video or inside a video
          let targetVideo = null;
          if (e.target.tagName === 'VIDEO') {
            targetVideo = e.target;
          } else if (e.target.closest('video')) {
            targetVideo = e.target.closest('video');
          }
          
          if (targetVideo) {
            const videoSrc = targetVideo.getAttribute('src');
            if (videoSrc) {
              // Open fullscreen lightbox
              openLightbox('video', videoSrc);
              e.stopPropagation();
            }
          }
        }, true); // Use capture phase

        // Function to wrap video with overlay
        function setupVideoOverlay(video) {
          // Skip if already wrapped or if it's the main product video (handled separately)
          if (video.parentElement.classList.contains('video-fullscreen-wrapper') || video.id === 'mainProductVideo') {
             // For main video, just ensure pointer cursor
             if (video.id === 'mainProductVideo') video.style.cursor = 'pointer';
             return;
          }

          video.style.cursor = 'pointer';
          video.title = 'Click to view fullscreen';
          
          const wrapper = document.createElement('div');
          wrapper.className = 'video-fullscreen-wrapper';
          wrapper.style.position = 'relative';
          wrapper.style.display = 'inline-block';
          wrapper.style.width = '100%';
          // Ensure wrapper respects video dimensions if set, or defaults to 100%
          
          video.parentNode.insertBefore(wrapper, video);
          wrapper.appendChild(video);
          
          // Add click overlay
          const overlay = document.createElement('div');
          overlay.className = 'video-fullscreen-overlay';
          overlay.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; cursor: pointer; z-index: 10; background: transparent;';
          overlay.title = 'Click to view fullscreen';
          
          overlay.addEventListener('click', function(e) {
            e.stopPropagation(); // Stop propagation
            e.preventDefault();  // Prevent default
            const videoSrc = video.getAttribute('src');
            if (videoSrc) {
              openLightbox('video', videoSrc);
            }
          });
          
          wrapper.appendChild(overlay);
        }

        // 1. Setup existing videos
        document.querySelectorAll('video').forEach(setupVideoOverlay);

        // 2. Observer for new videos (tabs, dynamic content)
        const videoObserver = new MutationObserver(function(mutations) {
          mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
              if (node.nodeType === 1) { // Element node
                if (node.tagName === 'VIDEO') {
                  setupVideoOverlay(node);
                } else if (node.querySelectorAll) {
                  node.querySelectorAll('video').forEach(setupVideoOverlay);
                }
              }
            });
          });
        });

        videoObserver.observe(document.body, {
          childList: true,
          subtree: true
        });

        // Add capturing listener as fallback for any unwrapped videos
        document.addEventListener('click', function(e) {
           // ... existing fallback ...
        }, true);

        if (mainImageContainer && mainImage) {
          mainImageContainer.addEventListener('mousemove', function(e) {
            const { left, top, width, height } = mainImageContainer.getBoundingClientRect();
            const x = e.clientX - left;
            const y = e.clientY - top;
            
            // Calculate percentage position
            const xPercent = (x / width) * 100;
            const yPercent = (y / height) * 100;

            // Set transform origin to mouse position
            mainImage.style.transformOrigin = `${xPercent}% ${yPercent}%`;
            mainImage.style.transform = 'scale(2)'; // Zoom level 2x
          });

          mainImageContainer.addEventListener('mouseleave', function() {
            mainImage.style.transformOrigin = 'center center';
            mainImage.style.transform = 'scale(1)';
          });
        }
      </script>



      <!-- Share -->
      <div class="share-row">
        <span class="label">Share:</span>
        <button class="share-btn whatsapp"><i class="fa-brands fa-whatsapp"></i> WhatsApp</button>
        <button class="share-btn instagram"><i class="fa-brands fa-instagram"></i> Instagram</button>
        <button class="share-btn facebook"><i class="fa-brands fa-facebook-f"></i> Facebook</button>
        <button class="share-btn twitter"><i class="fa-brands fa-x-twitter"></i> Tweet</button>
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
      </div>

      <div class="tab-contents">
        <!-- DESCRIPTION -->
        <div class="tab-pane active rich-content" id="tab-desc">
          <!-- <h3>Description</h3> REMOVED -->
          <?php if (!empty($longDescription)): ?>
            <?php echo $longDescription; ?>
          <?php else: ?>
            <p>Description coming soon for this product.</p>
          <?php endif; ?>
        </div>

        <!-- INGREDIENTS -->
        <div class="tab-pane rich-content" id="tab-ing">
          <!-- <h3>Ingredients</h3> REMOVED -->
          <?php if (!empty($ingredientsText)): ?>
            <?php echo $ingredientsText; ?>
          <?php else: ?>
            <p>Ingredients information will be updated soon.</p>
          <?php endif; ?>
        </div>

        <!-- HOW TO USE -->
        <div class="tab-pane rich-content" id="tab-use">
          <!-- <h3>How to use</h3> REMOVED -->
          <?php if (!empty($howToUseText)): ?>
            <?php echo $howToUseText; ?>
          <?php else: ?>
            <p>Usage instructions will be updated soon.</p>
          <?php endif; ?>
        </div>

        <!-- WHY DEVILIXIRS -->
        <div class="tab-pane" id="tab-why">
          <!-- <h3>Why Devilixirs</h3> REMOVED -->
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
      <?php if (!empty($productTags)): ?>
      <div class="tags-card">
        <div class="tags-title">Tags</div>
        <div>
          <?php foreach ($productTags as $tag): ?>
            <span class="tag-chip"><?php echo htmlspecialchars($tag['name'], ENT_QUOTES); ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

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

  <!-- PRODUCT MEDIA GALLERY -->
  <?php
  $productMedia = [];
  if (!empty($product['product_media'])) {
      $decoded = json_decode($product['product_media'], true);
      if (is_array($decoded)) {
          $productMedia = $decoded;
      }
  }
  ?>
  <?php if (!empty($productMedia)): ?>
  <section class="product-media-section">
    <div class="section-title">
      <h2>Product Gallery</h2>
    </div>
    <div class="media-gallery-grid">
      <?php foreach ($productMedia as $media): ?>
        <div class="media-item" onclick="window.openLightbox('<?php echo $media['type']; ?>', '/assets/uploads/product_media/<?php echo htmlspecialchars($media['path'], ENT_QUOTES); ?>')">
          <?php if ($media['type'] === 'video'): ?>
            <video src="/assets/uploads/product_media/<?php echo htmlspecialchars($media['path'], ENT_QUOTES); ?>"></video>
            <div class="play-icon">▶</div>
          <?php else: ?>
            <img src="/assets/uploads/product_media/<?php echo htmlspecialchars($media['path'], ENT_QUOTES); ?>" alt="Product Media">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

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
    <div class="container">
      <?php if (isset($_SESSION['success'])): ?>
        <div style="background:#d4edda;color:#155724;padding:15px;margin-bottom:20px;border-radius:4px;border:1px solid #c3e6cb;">
          <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_SESSION['error'])): ?>
        <div style="background:#f8d7da;color:#721c24;padding:15px;margin-bottom:20px;border-radius:4px;border:1px solid #f5c6cb;">
          <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="section-title">
      <h2>Customer Reviews (<?= count($reviews) ?>)</h2>
    </div>
    
    <!-- Display Existing Reviews -->
    <?php if (!empty($reviews)): ?>
      <div class="reviews-list" style="margin-bottom:40px;">
        <?php foreach ($reviews as $review): ?>
          <div class="review-item" style="background:#fff;border:1px solid #e0e0e0;border-radius:12px;padding:24px;margin-bottom:20px;">
            <div class="review-header" style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px;">
              <div>
                <div class="review-author" style="font-weight:600;font-size:15px;margin-bottom:6px;">
                  <?= htmlspecialchars($review['reviewer_name'] ?: 'Anonymous') ?>
                </div>
                <div class="review-rating" style="color:#ffb400;font-size:16px;margin-bottom:8px;">
                  <?= str_repeat('★', (int)$review['rating']) ?><?= str_repeat('☆', 5 - (int)$review['rating']) ?>
                </div>
              </div>
              <div class="review-date" style="font-size:12px;color:#999;">
                <?= date('M j, Y', strtotime($review['created_at'])) ?>
              </div>
            </div>
            <div class="review-comment" style="color:#333;line-height:1.7;font-size:14px;margin-bottom:12px;">
              <?= nl2br(htmlspecialchars($review['comment'])) ?>
            </div>
            <?php 
            // Display review images if they exist
            $reviewImages = !empty($review['images']) ? json_decode($review['images'], true) : [];
            if (is_array($reviewImages) && !empty($reviewImages)): 
            ?>
            <div class="review-images" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
              <?php foreach ($reviewImages as $img): ?>
                <img src="/assets/uploads/reviews/<?= htmlspecialchars($img) ?>" alt="Review image" 
                     style="width:100px;height:100px;object-fit:cover;border-radius:8px;cursor:pointer;border:1px solid #e0e0e0;"
                     onclick="window.open(this.src, '_blank')">
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    
    <!-- Review Form -->
    <div class="review-form-container">
      <h3>Write a review</h3>


      <form id="reviewForm" action="submit_review.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
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
          <label for="review_images">Add Photos (Optional - Max 3)</label>
          <input type="file" id="review_images" name="review_images[]" accept="image/*" multiple style="margin-top:8px;">
          <div id="imagePreview" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;"></div>
          <small style="color:#666;font-size:12px;margin-top:4px;display:block;">Max 3 images, 5MB each. JPG, PNG, WEBP accepted.</small>
        </div>

        <button type="submit" class="btn-submit-review">Submit Review</button>
      </form>
    </div>
  </section>

<div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

<script>
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    
    // Toast Styles
    toast.style.minWidth = '300px';
    toast.style.padding = '16px 24px';
    toast.style.marginBottom = '10px';
    toast.style.borderRadius = '8px';
    toast.style.background = '#fff';
    toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    toast.style.gap = '12px';
    toast.style.transform = 'translateX(120%)';
    toast.style.transition = 'transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
    toast.style.borderLeft = type === 'success' ? '5px solid #2e7d32' : '5px solid #d32f2f';
    
    // Icon
    const icon = type === 'success' ? '<i class="fas fa-check-circle" style="color:#2e7d32;font-size:20px;"></i>' : '<i class="fas fa-exclamation-circle" style="color:#d32f2f;font-size:20px;"></i>';
    
    toast.innerHTML = `
        ${icon}
        <div style="font-size:14px;font-weight:500;color:#333;">${message}</div>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    requestAnimationFrame(() => {
        toast.style.transform = 'translateX(0)';
    });
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.style.transform = 'translateX(120%)';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}

// Image preview for review form
document.getElementById('review_images').addEventListener('change', function(e) {
    const previewContainer = document.getElementById('imagePreview');
    previewContainer.innerHTML = '';
    
    const files = Array.from(e.target.files).slice(0, 3); // Max 3 images
    
    files.forEach((file, index) => {
        if (file.size > 5 * 1024 * 1024) { // 5MB limit
            showToast(`Image ${index + 1} is too large (max 5MB)`, 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #ddd;';
            previewContainer.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
});


document.getElementById('reviewForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate Rating
    const rating = document.querySelector('input[name="rating"]:checked');
    if (!rating) {
        showToast('Please select a star rating before submitting.', 'error');
        return;
    }
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('.btn-submit-review');
    const originalText = submitBtn.textContent;
    
    submitBtn.textContent = 'Submitting...';
    submitBtn.disabled = true;
    
    fetch('submit_review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        if (data.success) {
            // Create new review element
            const reviewHtml = `
                <div class="review-item" style="background:#fff;border:1px solid #e0e0e0;border-radius:12px;padding:24px;margin-bottom:20px;animation:fadeIn 0.5s ease;">
                    <div class="review-header" style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px;">
                        <div>
                            <div class="review-author" style="font-weight:600;font-size:15px;margin-bottom:6px;">
                                ${data.review.reviewer_name}
                            </div>
                            <div class="review-rating" style="color:#ffb400;font-size:16px;margin-bottom:8px;">
                                ${'★'.repeat(data.review.rating)}${'☆'.repeat(5 - data.review.rating)}
                            </div>
                        </div>
                        <div class="review-date" style="font-size:12px;color:#999;">
                            ${data.review.created_at}
                        </div>
                    </div>
                    <div class="review-comment" style="color:#333;line-height:1.7;font-size:14px;margin-bottom:12px;">
                        ${data.review.comment}
                    </div>
                    ${data.review.images && data.review.images.length > 0 ? `
                        <div class="review-images" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
                            ${data.review.images.map(img => `
                                <img src="/assets/uploads/reviews/${img}" alt="Review image" 
                                     style="width:100px;height:100px;object-fit:cover;border-radius:8px;cursor:pointer;border:1px solid #e0e0e0;"
                                     onclick="window.open(this.src, '_blank')">
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            `;
            
            // Prepend to reviews list or create it if it doesn't exist
            let reviewsList = document.querySelector('.reviews-list');
            
            if (!reviewsList) {
                const sectionTitle = document.querySelector('.reviews-section .section-title');
                reviewsList = document.createElement('div');
                reviewsList.className = 'reviews-list';
                reviewsList.style.marginBottom = '40px';
                sectionTitle.insertAdjacentElement('afterend', reviewsList);
            }
            
            reviewsList.insertAdjacentHTML('afterbegin', reviewHtml);
            
            // Update count in reviews section
            const countHeader = document.querySelector('.section-title h2');
            let currentCount = 0;
            
            if (countHeader) {
                const match = countHeader.textContent.match(/\d+/);
                currentCount = match ? parseInt(match[0]) : 0;
                countHeader.textContent = `Customer Reviews (${currentCount + 1})`;
            }
            
            // Update product summary section at the top
            // Try multiple selectors to find the element
            let productMeta = document.querySelector('.product-summary .product-meta span');
            if (!productMeta) {
                productMeta = document.querySelector('.product-meta span');
            }
            
            if (productMeta) {
                const newCount = currentCount + 1;
                const newHTML = `<i class="fa-regular fa-pen-to-square"></i> ${newCount} Review${newCount > 1 ? 's' : ''}`;
                productMeta.innerHTML = newHTML;
            }
            
            // Update rating stars in product summary
            const ratingRow = document.querySelector('.product-summary .rating-row');
            
            if (ratingRow && data.review.rating) {
                const starsDiv = ratingRow.querySelector('.rating-stars');
                
                if (starsDiv) {
                    const newRating = data.review.rating;
                    const starHTML = '★'.repeat(newRating) + '☆'.repeat(5 - newRating);
                    starsDiv.innerHTML = starHTML;
                }
                
                let ratingText = ratingRow.querySelector('span');
                if (!ratingText) {
                    ratingText = document.createElement('span');
                    ratingRow.appendChild(ratingText);
                }
                ratingText.textContent = `(Rated ${data.review.rating}.0)`;
            }

            // Reset form
            document.getElementById('reviewForm').reset();
            document.getElementById('imagePreview').innerHTML = '';
            
            showToast(data.message || 'Review submitted successfully!', 'success');
            
        } else {
            showToast(data.message || 'Failed to submit review', 'error');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showToast(error.message || 'An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});
</script>

  <!-- RELATED PRODUCTS SECTION -->
  <?php
  // Fetch Related Products
  $relatedProducts = [];
  try {
      $stmtRelated = $pdo->prepare("
          SELECT p.id, p.name, p.price, p.compare_price, p.images
          FROM product_relations pr
          JOIN products p ON pr.related_product_id = p.id
          WHERE pr.product_id = ? AND p.is_active = 1
          LIMIT 10
      ");
      $stmtRelated->execute([$productId]);
      $relatedProducts = $stmtRelated->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
      $relatedProducts = [];
  }
  ?>

  <?php if (!empty($relatedProducts)): ?>
  <section class="related-products-section">
    <div class="container">
      <h2 class="section-title">Related Products</h2>
      <div class="related-products-carousel">
        <?php foreach ($relatedProducts as $rp): 
          // Get first image from images JSON
          $rpImage = null;
          if (!empty($rp['images'])) {
              $imgs = json_decode($rp['images'], true);
              if (is_array($imgs) && !empty($imgs)) {
                  $rpImage = $imgs[0];
              }
          }
          $rpImagePath = !empty($rpImage) ? '/assets/uploads/products/' . htmlspecialchars($rpImage) : '/assets/images/avatar-default.png';
          
          $rpPrice = (float)$rp['price'];
          $rpOldPrice = !empty($rp['compare_price']) && $rp['compare_price'] > $rpPrice ? (float)$rp['compare_price'] : null;
        ?>
        <div class="related-product-card">
          <a href="product_view.php?id=<?php echo $rp['id']; ?>" class="product-link">
            <div class="product-image">
              <img src="<?php echo $rpImagePath; ?>" alt="<?php echo htmlspecialchars($rp['name']); ?>">
            </div>
            <div class="product-info">
              <h3 class="product-name"><?php echo htmlspecialchars($rp['name']); ?></h3>
              <div class="product-price">
                <?php if ($rpOldPrice): ?>
                  <span class="old-price">₹<?php echo number_format($rpOldPrice, 2); ?></span>
                <?php endif; ?>
                <span class="current-price">₹<?php echo number_format($rpPrice, 2); ?></span>
              </div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

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
      // Fullscreen button
      const mainImageWrapper = document.querySelector('.product-main-image-wrapper');
      if (fullscreenBtn && mainImageWrapper) {
        fullscreenBtn.addEventListener('click', () => {
          if (mainImageWrapper.requestFullscreen) {
            mainImageWrapper.requestFullscreen();
          } else if (mainImageWrapper.webkitRequestFullscreen) {
            mainImageWrapper.webkitRequestFullscreen();
          } else if (mainImageWrapper.mozRequestFullScreen) {
            mainImageWrapper.mozRequestFullScreen();
          } else if (mainImageWrapper.msRequestFullscreen) {
            mainImageWrapper.msRequestFullscreen();
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

    /* Product Media Gallery */
    .product-media-section {
      max-width: 1000px; /* Slightly narrower for better reading flow on vertical */
      margin: 40px auto;
      padding: 0 20px;
    }
    .media-gallery-grid {
      display: flex;
      flex-direction: column;
      gap: 40px;
      margin-top: 30px;
    }
    .media-item {
      position: relative;
      border-radius: 16px;
      overflow: hidden;
      width: 100%;
      cursor: pointer;
      box-shadow: var(--shadow-md);
      transition: transform 0.3s ease;
    }
    .media-item:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-lg);
    }
    .media-item img, .media-item video {
      width: 100%;
      height: auto;
      display: block;
    }
    .play-icon {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 50px;
      height: 50px;
      background: rgba(0,0,0,0.6);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 24px;
      pointer-events: none;
    }
    
    /* Lightbox */
    .lightbox-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.9);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    .lightbox-overlay.active {
      opacity: 1;
      visibility: visible;
    }
    .lightbox-content {
      max-width: 90%;
      max-height: 90%;
      position: relative;
    }
    .lightbox-content img, .lightbox-content video {
      max-width: 100%;
      max-height: 90vh;
      border-radius: 4px;
    }
    .lightbox-close {
      position: absolute;
      top: -40px;
      right: 0;
      color: white;
      font-size: 30px;
      cursor: pointer;
    }

    /* Related Products Section */
    .related-products-section {
      max-width: 1200px;
      margin: 40px auto;
      padding: 20px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .related-products-section .section-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 20px;
      color: #333;
    }
    .related-products-carousel {
      display: flex;
      gap: 20px;
      overflow-x: auto;
      scroll-behavior: smooth;
      padding: 10px 0;
      scrollbar-width: thin;
      scrollbar-color: #ccc #f1f1f1;
    }
    .related-products-carousel::-webkit-scrollbar {
      height: 8px;
    }
    .related-products-carousel::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 4px;
    }
    .related-products-carousel::-webkit-scrollbar-thumb {
      background: #ccc;
      border-radius: 4px;
    }
    .related-products-carousel::-webkit-scrollbar-thumb:hover {
      background: #999;
    }
    .related-product-card {
      flex: 0 0 250px;
      background: #fff;
      border: 1px solid #e1e1e1;
      border-radius: 12px;
      overflow: hidden;
      transition: all 0.3s ease;
    }
    .related-product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }
    
    /* Mobile: Show 2 related products at a time */
    @media (max-width: 768px) {
      .related-products-section {
        margin: 30px auto;
        padding: 15px;
      }
      
      .related-products-section .section-title {
        font-size: 1.2rem;
        margin-bottom: 15px;
      }
      
      .related-products-carousel {
        gap: 12px;
        padding: 5px 0;
      }
      
      .related-product-card {
        flex: 0 0 48%;
        min-width: 48%;
      }
      
      .related-products-carousel::-webkit-scrollbar {
        height: 6px;
      }
    }
    .related-product-card:hover {
      border-color: #3B502C;
    }
    .related-product-card .product-link {
      text-decoration: none;
      color: inherit;
      display: block;
    }
    .related-product-card .product-image {
      width: 100%;
      height: 200px;
      overflow: hidden;
      background: #f5f5f5;
    }
    .related-product-card .product-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }
    .related-product-card:hover .product-image img {
      transform: scale(1.05);
    }
    .related-product-card .product-info {
      padding: 15px;
    }
    .related-product-card .product-name {
      font-size: 0.95rem;
      font-weight: 600;
      margin-bottom: 10px;
      color: #333;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .related-product-card .product-price {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .related-product-card .old-price {
      text-decoration: line-through;
      color: #999;
      font-size: 0.85rem;
    }
    .related-product-card .current-price {
      font-size: 1.1rem;
      font-weight: 700;
      color: #A41B42;
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
  <!-- FOOTER -->
  <?php include 'footer.php'; ?>

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

      // Image/Video thumbs → change media
      const mainImgContainer = document.querySelector('.product-main-image');
      const mainImg = document.getElementById('mainProductImage');
      const mainVideo = document.getElementById('mainProductVideo');

      document.querySelectorAll('.thumb-item').forEach(thumb => {
        thumb.addEventListener('click', () => {
             // 1. Remove active from all thumbs
             document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
             // 2. Add active to clicked
             thumb.classList.add('active');

             // 3. Get media source
             const mediaSrc = thumb.getAttribute('data-image');
             if(!mediaSrc) return;

             // 4. Check extension
             const ext = mediaSrc.split('.').pop().toLowerCase();
             const isVideo = ['mp4', 'webm', 'ogg', 'mov'].includes(ext);

             if (isVideo) {
                 if(mainImg) mainImg.style.display = 'none';
                 if(mainVideo) {
                     mainVideo.style.display = 'block';
                     mainVideo.src = mediaSrc;
                     mainVideo.play();
                 }
             } else {
                 if(mainVideo) {
                     mainVideo.style.display = 'none';
                     mainVideo.pause();
                 }
                 if(mainImg) {
                     mainImg.style.display = 'block';
                     mainImg.src = mediaSrc;
                 }
             }
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
          if (typeof window.refreshProductPriceSection === 'function') {
            window.refreshProductPriceSection();
          }
        });
      });

      if (qtyInput) {
        qtyInput.addEventListener('input', () => {
          if (typeof window.refreshProductPriceSection === 'function') {
            window.refreshProductPriceSection();
          }
        });
      }

      // Add to Cart Logic
      const addToCartBtn = document.querySelector('.btn-add-cart');
      if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
          const productId = this.getAttribute('data-product-id');
          const quantity = parseInt(document.querySelector('.qty-input').value) || 1;
          
          // Visual feedback
          const originalText = this.innerHTML;
          this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';
          this.disabled = true;
          
          fetch('ajax_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&product_id=${productId}&quantity=${quantity}`
          })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              this.innerHTML = '<i class="fa-solid fa-check"></i> Added!';
              this.style.background = '#2e7d32'; // Green success color
              
              // Check for coupon status (if coupon was removed due to cart change)
              if (data.coupon_status && data.coupon_status.status === 'removed') {
                  alert(data.coupon_status.message);
              }
              
              setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
                this.style.background = ''; // Reset color
                // Optional: Update cart count in header if you have one
                // updateCartCount(data.count); 
              }, 2000);
            } else {
              if (data.message && data.message.toLowerCase().includes('login')) {
                  window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
              } else {
                  showToast(data.message || 'Failed to add to cart', 'error');
              }
              this.innerHTML = originalText;
              this.disabled = false;
            }
          })
          .catch(err => {
            console.error(err);
            alert('An error occurred. Please try again.');
            this.innerHTML = originalText;
            this.disabled = false;
          });
        });
      }

      // Social Share Logic
      const shareUrl = encodeURIComponent(window.location.href);
      const shareTitle = encodeURIComponent(document.title);

      const waBtn = document.querySelector('.share-btn.whatsapp');
      if(waBtn) {
          waBtn.addEventListener('click', () => {
              window.open(`https://wa.me/?text=${shareTitle}%20${shareUrl}`, '_blank');
          });
      }

      const fbBtn = document.querySelector('.share-btn.facebook');
      if(fbBtn) {
          fbBtn.addEventListener('click', () => {
              window.open(`https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`, '_blank');
          });
      }

      const twBtn = document.querySelector('.share-btn.twitter');
      if(twBtn) {
          twBtn.addEventListener('click', () => {
              window.open(`https://twitter.com/intent/tweet?text=${shareTitle}&url=${shareUrl}`, '_blank');
          });
      }

      const instaBtn = document.querySelector('.share-btn.instagram');
      if(instaBtn) {
          instaBtn.addEventListener('click', () => {
              // Instagram doesn't support direct web sharing links.
              // We'll copy the link to clipboard and notify the user.
              navigator.clipboard.writeText(window.location.href).then(() => {
                  alert('Link copied to clipboard! You can paste it on Instagram.');
              }).catch(err => {
                  console.error('Failed to copy: ', err);
              });
          });
      }

    });
  </script>

  <!-- Lightbox Overlay -->
  <div id="mediaLightbox" class="lightbox-overlay" onclick="closeLightbox(event)">
    <div class="lightbox-content">
      <span class="lightbox-close" onclick="closeLightbox(event)">&times;</span>
      <div id="lightboxMediaContainer"></div>
    </div>
  </div>

  <script>
    window.openLightbox = function(type, src) {
      const container = document.getElementById('lightboxMediaContainer');
      const lightbox = document.getElementById('mediaLightbox');
      
      if (type === 'video') {
        container.innerHTML = `<video src="${src}" controls autoplay class="w-full h-full"></video>`;
      } else {
        container.innerHTML = `<img src="${src}" alt="Full View">`;
      }
      
      lightbox.classList.add('active');
      document.body.style.overflow = 'hidden'; // Prevent scrolling
    };

    window.closeLightbox = function(e) {
      if (e.target.classList.contains('lightbox-overlay') || e.target.classList.contains('lightbox-close')) {
        const lightbox = document.getElementById('mediaLightbox');
        const container = document.getElementById('lightboxMediaContainer');
        
        lightbox.classList.remove('active');
        container.innerHTML = ''; // Stop video playback
        document.body.style.overflow = ''; // Restore scrolling
      }
    };
  </script>

</body>
</html>
