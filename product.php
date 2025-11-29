<?php
require_once __DIR__ . '/includes/db.php';   // must set $pdo

// 🔹 Detect AJAX filter request and map POST "filters" → $_GET
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // "filters" = serialized query string from JS: color[]=Red&price_min=100...
    $raw = $_POST['filters'] ?? '';
    $parsed = [];
    parse_str($raw, $parsed);

    // Overwrite $_GET so all existing code below (price, groups, sort...) works
    $_GET = $parsed;
}

// ===== LOAD ALL FILTER GROUPS + OPTIONS =====
$filterGroups = [];   // each group has its options inside

try {
    // load groups
    $stmt = $pdo->prepare("
        SELECT *
        FROM filter_groups
        WHERE is_active = 1
        ORDER BY sort_order ASC, name ASC
    ");
    $stmt->execute();
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // load all options once
    $stmtOpt = $pdo->prepare("
        SELECT *
        FROM filter_options
        WHERE is_active = 1
        ORDER BY sort_order ASC, label ASC
    ");
    $stmtOpt->execute();
    $options = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);

    // attach options to groups
    foreach ($groups as $g) {
        $g['options'] = [];
        foreach ($options as $opt) {
            if ((int)$opt['group_id'] === (int)$g['id']) {
                $g['options'][] = $opt;
            }
        }
        $filterGroups[] = $g;
    }
} catch (PDOException $e) {
    $filterGroups = [];
}

// active values to keep checkboxes checked
$activeFilterValues = [];   // [param_key => [values]]

function get_first_image($images) {
    // same default as index.php
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

/* ==================== PRODUCT FILTERS / SORT / PAGINATION ==================== */

// Read filters from query string (for price + sort)
$priceMin = isset($_GET['price_min']) && $_GET['price_min'] !== ''
    ? (float)$_GET['price_min']
    : null;

$priceMax = isset($_GET['price_max']) && $_GET['price_max'] !== ''
    ? (float)$_GET['price_max']
    : null;

// category filter from URL (?cat=4)
$categoryId = isset($_GET['cat']) && $_GET['cat'] !== ''
    ? (int)$_GET['cat']
    : null;
// 🔹 category NAME from ?category[]=Mens+Care (index.php link)
// 🔹 category from filters (can be "Men Care" OR "Men Care / Face Wash")
$selectedCategoryName = null; // parent-only name -> "Men Care"
$selectedCategoryFull = null; // full path        -> "Men Care / Face Wash"

if (isset($_GET['category'])) {
    $rawVals = is_array($_GET['category'])
        ? $_GET['category']
        : [$_GET['category']];

    foreach ($rawVals as $rawVal) {
        $val = trim($rawVal);
        if ($val === '') {
            continue;
        }

        // If it is a subcategory: "Parent / Sub"
        if (strpos($val, '/') !== false) {
            if ($selectedCategoryFull === null) {
                $selectedCategoryFull = $val;

                // also extract the parent name
                $parts = explode('/', $val, 2);
                $parentName = trim($parts[0] ?? '');
                if ($parentName !== '' && $selectedCategoryName === null) {
                    $selectedCategoryName = $parentName;
                }
            }
        } else {
            // just parent category like "Men Care"
            if ($selectedCategoryName === null) {
                $selectedCategoryName = $val;
            }
        }
    }
}
$sort = $_GET['sort'] ?? 'default';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9; // products per page
$offset  = ($page - 1) * $perPage;

/**
 * Build WHERE parts:
 *   - always: (is_active = 1 OR is_active IS NULL)
 *   - dynamic filter groups (color/size/etc.) from filter_groups
 *   - category_id (from ?cat=)
 *   - price range
 */
$whereParts = ["(is_active = 1 OR is_active IS NULL)"];
$params     = [];      // named params like [':color1' => 'Black']
$paramIndex = 1;       // to create unique param names

// 1) Dynamic filters from filter_groups
// 1) Dynamic filters from filter_groups
// 1) Dynamic filters from filter_groups
foreach ($filterGroups as $g) {
    $paramKey   = $g['param_key'];   // e.g. 'color', 'category', 'range'
    $columnName = $g['column_name']; // e.g. 'color', 'category', 'category_name'

    if (!isset($_GET[$paramKey]) || !is_array($_GET[$paramKey])) {
        continue;
    }

    // Raw values from GET (e.g. "Men Care", "Men Care / Face wash")
    $vals = array_filter($_GET[$paramKey], 'strlen');
    if (empty($vals)) {
        continue;
    }

    // Keep original selection for checked state
    $activeFilterValues[$paramKey] = $vals;

    // ---------- SPECIAL CASE: CATEGORY ----------
    if ($paramKey === 'category') {
        $orParts = [];

        foreach ($vals as $val) {
            $val = trim($val);
            if ($val === '') {
                continue;
            }

            // If it's a PARENT like "Men Care"
            if (strpos($val, '/') === false) {
                // exact match
                $phEq   = ':' . $paramKey . '_eq_'   . $paramIndex;
                $phLike = ':' . $paramKey . '_like_' . $paramIndex;
                $paramIndex++;

                $params[$phEq]   = $val;             // "Men Care"
                $params[$phLike] = $val . ' /%';     // "Men Care /%"

                // (category = 'Men Care' OR category LIKE 'Men Care /%')
                $orParts[] = "({$columnName} = {$phEq} OR {$columnName} LIKE {$phLike})";
            }
            // If it's a FULL (parent / sub) like "Men Care / Face wash"
            else {
                $ph = ':' . $paramKey . '_full_' . $paramIndex;
                $paramIndex++;

                $params[$ph] = $val;
                $orParts[]   = "{$columnName} = {$ph}";
            }
        }

        if (!empty($orParts)) {
            // Wrap all category conditions into one big OR block
            $whereParts[] = '(' . implode(' OR ', $orParts) . ')';
        }

        continue; // important: skip the default IN() logic below
    }
    // ---------- END CATEGORY SPECIAL CASE ----------

    // Default: simple IN() for non-category filters
    $phList = [];
    foreach ($vals as $val) {
        $ph = ':' . $paramKey . $paramIndex;
        $paramIndex++;

        $phList[]    = $ph;
        $params[$ph] = $val;
    }

    $whereParts[] = "{$columnName} IN (" . implode(',', $phList) . ")";
}

// 2) Category filter (from cat= in URL – optional)
// 2) Category filter (from cat= in URL – optional)
// If a parent category is clicked, include all its subcategories too.
// 2) Category filter (from cat= in URL – optional)
// Only if there is NO "category[]" filter present (i.e. not coming from Men Care filter / sidebar)
$hasCategoryFilter = !empty($_GET['category']);

if ($categoryId !== null && $categoryId > 0 && !$hasCategoryFilter) {
    // 👉 here you can keep either the simple version:
    // $whereParts[]           = "category_id = :category_id";
    // $params[':category_id'] = $categoryId;

    // or the extended parent+children logic if you want for ?cat= links
    $categoryIdsForFilter = [$categoryId];

    try {
        $stmtCat = $pdo->prepare("
            SELECT id 
            FROM categories 
            WHERE parent_id = :pid
        ");
        $stmtCat->execute([':pid' => $categoryId]);
        $childIds = $stmtCat->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($childIds)) {
            foreach ($childIds as $cid) {
                $categoryIdsForFilter[] = (int)$cid;
            }
        }
    } catch (PDOException $e) {
        // fallback: only clicked category
    }

    $placeholders = [];
    foreach ($categoryIdsForFilter as $idx => $cid) {
        $ph = ":cat{$idx}";
        $placeholders[] = $ph;
        $params[$ph] = $cid;
    }

    if (!empty($placeholders)) {
        $whereParts[] = "category_id IN (" . implode(',', $placeholders) . ")";
    }
}

// 3) Price filter
if ($priceMin !== null) {
    $whereParts[]         = "price >= :price_min";
    $params[':price_min'] = $priceMin;
}
if ($priceMax !== null) {
    $whereParts[]         = "price <= :price_max";
    $params[':price_max'] = $priceMax;
}

// 4) Final WHERE SQL
$whereSql = '';
if (!empty($whereParts)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereParts);
}

/* ----- sorting ----- */
switch ($sort) {
    case 'popularity':
        $orderBy = 'sold_count DESC';
        break;
    case 'latest':
        $orderBy = 'created_at DESC';
        break;
    case 'price_asc':
        $orderBy = 'price ASC';
        break;
    case 'price_desc':
        $orderBy = 'price DESC';
        break;
    default:
        $orderBy = 'id DESC'; // default sorting
}

/* ----- total count for pagination ----- */
$totalProducts = 0;
try {
    $sqlCount = "SELECT COUNT(*) FROM products {$whereSql}";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalProducts = (int)$stmtCount->fetchColumn();
} catch (PDOException $e) {
    $totalProducts = 0;
}

$totalPages = $totalProducts > 0 ? (int)ceil($totalProducts / $perPage) : 1;

/* ----- fetch products for current page ----- */
$products = [];
try {
    $sqlProducts = "
        SELECT *
        FROM products
        {$whereSql}
        ORDER BY {$orderBy}
        LIMIT :limit OFFSET :offset
    ";
    $stmtProducts = $pdo->prepare($sqlProducts);

    // bind named params for filters (color/size/category/price)
    foreach ($params as $name => $val) {
        $stmtProducts->bindValue($name, $val);
    }

    // bind limit & offset
    $stmtProducts->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmtProducts->bindValue(':offset', $offset,  PDO::PARAM_INT);

    $stmtProducts->execute();
    $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}

/* ==================== BANNERS FOR PRODUCT PAGE (CATEGORY AWARE) ==================== */

/* ==================== BANNERS FOR PRODUCT PAGE (CATEGORY AWARE) ==================== */

/* ==================== BANNERS FOR PRODUCT PAGE (CATEGORY AWARE) ==================== */

/* ==================== BANNERS FOR PRODUCT PAGE (CATEGORY AWARE) ==================== */

$heroBanners = [];

// Try to detect which column is used for category label: 'title' or 'name'
$categoryLabelField = null;
try {
    $cols   = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $fields = array_column($cols, 'Field');

    if (in_array('title', $fields, true)) {
        $categoryLabelField = 'title';
    } elseif (in_array('name', $fields, true)) {
        $categoryLabelField = 'name';
    }
} catch (Exception $e) {
    $categoryLabelField = null;
}

// Resolve parent + sub category IDs from selected filters
$resolvedParentId = null;
$resolvedSubId    = null;

if ($categoryLabelField !== null) {
    try {
        // 1️⃣ If we have a FULL subcategory like "Men Care / Face Wash"
        if (!empty($selectedCategoryFull)) {
            $parts      = explode('/', $selectedCategoryFull, 2);
            $parentName = trim($parts[0] ?? '');
            $childName  = trim($parts[1] ?? '');

            if ($parentName !== '' && $childName !== '') {
                $sql = "
                    SELECT parent.id AS parent_id, child.id AS child_id
                    FROM categories parent
                    JOIN categories child ON child.parent_id = parent.id
                    WHERE parent.{$categoryLabelField} = :pname
                      AND child.{$categoryLabelField}  = :cname
                    LIMIT 1
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':pname' => $parentName,
                    ':cname' => $childName,
                ]);
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $resolvedParentId = (int)$row['parent_id'];
                    $resolvedSubId    = (int)$row['child_id'];
                }
            }
        }

        // 2️⃣ If no sub found but we have a parent name like "Men Care"
        if ($resolvedParentId === null && $selectedCategoryName !== null && $selectedCategoryName !== '') {
            $sql = "
                SELECT id
                FROM categories
                WHERE {$categoryLabelField} = :pname
                  AND (parent_id IS NULL OR parent_id = 0)
                LIMIT 1
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':pname' => $selectedCategoryName]);
            if ($id = $stmt->fetchColumn()) {
                $resolvedParentId = (int)$id;
            }
        }

    } catch (PDOException $e) {
        error_log('Category name→ID resolve error: ' . $e->getMessage());
        $resolvedParentId = null;
        $resolvedSubId    = null;
    }
}

// 3️⃣ Choose which category_id to use for banners, in priority:
//
//    a) Subcategory ID (most specific)
//    b) Parent ID from name
//    c) Numeric ?cat= from URL
//
$bannerCategoryId = null;

if ($resolvedSubId !== null) {
    $bannerCategoryId = $resolvedSubId;
} elseif ($resolvedParentId !== null) {
    $bannerCategoryId = $resolvedParentId;
} elseif ($categoryId !== null && $categoryId > 0) {
    $bannerCategoryId = $categoryId;
}

// 4️⃣ Load banners based on resolved category_id (if any)
if ($bannerCategoryId !== null) {
    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM banners
            WHERE is_active = 1
              AND page_slot IN ('category','top_category')
              AND category_id = :cid
            ORDER BY id DESC
        ");
        $stmt->execute([':cid' => $bannerCategoryId]);
        $heroBanners = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        $heroBanners = [];
        error_log('Category banner query error: ' . $e->getMessage());
    }
}

// 5️⃣ Fallback – generic product banners
if (empty($heroBanners)) {
    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM banners
            WHERE is_active = 1
              AND page_slot = 'product'
            ORDER BY id DESC
        ");
        $stmt->execute();
        $heroBanners = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        $heroBanners = [];
        error_log('Product fallback banner query error: ' . $e->getMessage());
    }
}
// Sidebar banner for product listing page
$productSidebarBanner = null;

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM banners
        WHERE is_active = 1
          AND page_slot = 'product_sidebar'
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute();
    $productSidebarBanner = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
    $productSidebarBanner = null;
}

/* ========== RENDER FUNCTION FOR PRODUCTS AREA (used by normal + AJAX) ========== */
function renderProductResults($products, $totalPages, $page, $sort) { ?>
  <div id="productResults">
    <div class="shop-toolbar">
      <div class="shop-view-toggle">
        <!-- filter button (desktop + mobile) -->
        <button class="filter-toggle" type="button">
          <i class="fa-solid fa-sliders"></i>
          <span>Filter</span>
        </button>
      </div>

      <div class="shop-sort">
        <span>Sort by:</span>
        <select name="sort" id="sortSelect">
          <option value="default"    <?php echo $sort === 'default'    ? 'selected' : ''; ?>>Default sorting</option>
          <option value="popularity" <?php echo $sort === 'popularity' ? 'selected' : ''; ?>>Sort by popularity</option>
          <option value="latest"     <?php echo $sort === 'latest'     ? 'selected' : ''; ?>>Sort by latest</option>
          <option value="price_asc"  <?php echo $sort === 'price_asc'  ? 'selected' : ''; ?>>Sort by price: low to high</option>
          <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Sort by price: high to low</option>
        </select>
      </div>
    </div>

    <!-- PRODUCTS GRID -->
    <?php if (empty($products)): ?>
      <p style="font-size:14px;color:#777;">No products found with these filters.</p>
    <?php else: ?>
      <div class="products-grid">
        <?php foreach ($products as $p): ?>
          <?php
            $productId = (int)($p['id'] ?? 0);                           // 🔹 get product id
            $detailUrl = 'product_view.php?id=' . $productId;            // 🔹 build URL

            $name  = $p['name'] ?? 'Product';
            $price = isset($p['price']) ? (float)$p['price'] : 0;
            $oldPrice = (isset($p['old_price']) && $p['old_price'] > $price)
                ? (float)$p['old_price']
                : null;

            $img = get_first_image($p['images'] ?? '');

            $rating      = isset($p['rating']) ? (float)$p['rating'] : 0;
            $ratingCount = isset($p['rating_count']) ? (int)$p['rating_count'] : 0;
            $stars       = $rating > 0 ? str_repeat('★', round($rating)) : '★★★★★';
          ?>
          <article class="product-card">
            <!-- 🔹 Wrap clickable area with <a> -->
            <a href="<?php echo htmlspecialchars($detailUrl, ENT_QUOTES); ?>" class="product-link" style="display:block;">
              <div class="product-image-wrap">
                <?php if ($oldPrice): ?>
                  <span class="product-badge sale">Sale</span>
                <?php else: ?>
                  <span class="product-badge">New</span>
                <?php endif; ?>

                <img
                  src="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>"
                  alt="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>">

                <div class="product-actions">
                  <span><i class="fa-regular fa-heart"></i></span>
                  <span><i class="fa-regular fa-eye"></i></span>
                  <span><i class="fa-solid fa-bag-shopping"></i></span>
                </div>
              </div>
              <div class="product-info">
                <div class="product-name">
                  <?php echo htmlspecialchars($name, ENT_QUOTES); ?>
                </div>
                <div class="product-price">
                  <?php if ($oldPrice): ?>
                    <span class="old">₹<?php echo number_format($oldPrice, 2); ?></span>
                  <?php endif; ?>
                  ₹<?php echo number_format($price, 2); ?>
                </div>
                <div class="product-stars">
                  <?php echo $stars; ?>
                  <span>(<?php echo $ratingCount; ?>)</span>
                </div>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a
            href="#"
            class="page-item<?php echo $p === $page ? ' active' : ''; ?>"
            data-page="<?php echo $p; ?>"
          >
            <?php echo $p; ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a
            href="#"
            class="page-item"
            data-page="<?php echo $page + 1; ?>"
          >
            <i class="fa-solid fa-angle-right"></i>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
<?php
}

// 🔹 If this is an AJAX request, just return this block and stop
if ($isAjax) {
    renderProductResults($products, $totalPages, $page, $sort);
    exit;
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
  <?php include __DIR__ . '/navbar.php'; ?>
  
  <style>
/* === CSS (same as yours) === */
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
.shop-hero{
  margin-top:15px;
  margin-bottom:40px;
}

.shop-hero-slider{
  position:relative;
  overflow:hidden;
  border:1px solid var(--border);
  border-radius:8px;
  box-shadow:0 4px 18px rgba(0,0,0,.06);
}

.shop-hero-track{
  display:flex;
  transition:transform .6s ease;
  will-change:transform;
}

.shop-hero-slide{
  min-width:100%;
  height:360px;
  background-size:cover;
  background-position:center;
  position:relative;
}

.shop-hero-slide-link{
  display:block;
  width:100%;
  height:100%;
}

.shop-hero-overlay{
  position:absolute;
  inset:0;
  display:flex;
  align-items:center;
}

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

/* Filter toggle button (desktop + mobile) */
.filter-toggle{
  display:None;
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

/* mobile tweaks for hero */
@media (max-width:768px){
  .shop-hero{
    margin-top:60px;
    margin-bottom:30px;
  }
  .shop-hero-slide{
    height:260px;
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
  height:auto;
  display:block;
  object-fit:contain;
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

  aside{
    display:none;
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

  <!-- 🔹 SHARED NAVBAR -->

  <!-- HERO -->
  <section class="shop-hero">
  <?php if (!empty($heroBanners)): ?>
    <div class="shop-hero-slider">
      <div class="shop-hero-track">
        <?php foreach ($heroBanners as $idx => $b): ?>
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
                </div>
              </div>

            <?php if ($link): ?>
              </a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (count($heroBanners) > 1): ?>
        <div class="shop-hero-dots">
          <?php foreach ($heroBanners as $idx => $b): ?>
            <button
              class="shop-hero-dot<?php echo $idx === 0 ? ' active' : ''; ?>"
              data-slide="<?php echo $idx; ?>">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="shop-hero-inner">
      <div class="shop-breadcrumb"></div>
    </div>
  <?php endif; ?>
</section>

  <!-- MAIN SHOP CONTENT -->
  <form id="productFilterForm" onsubmit="return false;">

    <section class="shop-wrapper">

      <!-- SIDEBAR (desktop filters) -->
      <aside>
        <div class="filter-card">
          <div class="filter-card-title">
            <i class="fa-solid fa-sliders"></i>
            <span>Filter By</span>
          </div>
          <div class="filter-body">
            <?php foreach ($filterGroups as $g): ?>
              <div class="filter-group">
                <div class="filter-group-title">
                  <?php echo htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <ul class="filter-options">
                  <?php
                    $paramKey = $g['param_key'];
                    $selectedValues = $activeFilterValues[$paramKey] ?? [];
                  ?>
                  <?php foreach ($g['options'] as $opt): ?>
                    <?php
                      $val   = $opt['value'];
                      $label = $opt['label'];
                    ?>
                    <li>
                      <label>
                        <input
                          type="checkbox"
                          name="<?php echo htmlspecialchars($paramKey, ENT_QUOTES); ?>[]"
                          value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>"
                          <?php echo in_array($val, $selectedValues, true) ? 'checked' : ''; ?>
                        >
                        <span><?php echo htmlspecialchars($label, ENT_QUOTES); ?></span>
                      </label>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>

            <!-- Price filter (desktop) -->
            <div class="filter-group">
              <div class="filter-group-title">Price</div>
              <div class="price-row">
                <input
                  type="number"
                  name="price_min"
                  value="<?php echo $priceMin !== null ? htmlspecialchars($priceMin, ENT_QUOTES) : ''; ?>"
                  placeholder="Min">
                <span>–</span>
                <input
                  type="number"
                  name="price_max"
                  value="<?php echo $priceMax !== null ? htmlspecialchars($priceMax, ENT_QUOTES) : ''; ?>"
                  placeholder="Max">
              </div>

              <div style="display:flex; gap:8px; margin-top:8px;">
                <button class="btn-filter btn-apply-desktop" type="button">Apply</button>
                <button class="btn-filter btn-clear-desktop" type="button">Clear</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar banner -->
        <?php if (!empty($productSidebarBanner) && !empty($productSidebarBanner['filename'])): ?>
          <?php
            $sbSrc  = '/assets/uploads/banners/' . ltrim($productSidebarBanner['filename'], '/');
            $sbAlt  = $productSidebarBanner['alt_text'] ?? '';
            $sbLink = trim($productSidebarBanner['link'] ?? '');
          ?>
          <div class="side-banner">
            <?php if ($sbLink): ?>
              <a href="<?php echo htmlspecialchars($sbLink, ENT_QUOTES); ?>">
            <?php endif; ?>

              <img
                src="<?php echo htmlspecialchars($sbSrc, ENT_QUOTES); ?>"
                alt="<?php echo htmlspecialchars($sbAlt, ENT_QUOTES); ?>"
              >

              <?php if (!empty($sbAlt)): ?>
                <div class="side-banner-text">
                  <h4><?php echo htmlspecialchars($sbAlt, ENT_QUOTES); ?></h4>
                </div>
              <?php endif; ?>

            <?php if ($sbLink): ?>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </aside>

      <!-- PRODUCTS AREA -->
      <?php renderProductResults($products, $totalPages, $page, $sort); ?>
    </section>

    <!-- MOBILE FILTER OVERLAY (DB-powered) -->
    <div class="filter-overlay">
      <div class="filter-sheet">
        <div class="filter-sheet-header">
          <button class="filter-back" type="button">
            <i class="fa-solid fa-arrow-left"></i>
          </button>
          <h3>Filters</h3>
          <button class="filter-clear" type="button">Clear</button>
        </div>

        <div class="filter-sheet-body">
          <div class="filter-left">
            <?php foreach ($filterGroups as $idx => $g): ?>
              <?php $paramKey = $g['param_key']; ?>
              <button
                type="button"
                class="filter-tab<?php echo $idx === 0 ? ' active' : ''; ?>"
                data-filter-target="<?php echo htmlspecialchars($paramKey, ENT_QUOTES); ?>">
                <?php echo htmlspecialchars($g['name'], ENT_QUOTES); ?>
              </button>
            <?php endforeach; ?>

            <!-- Price tab -->
            <button
              type="button"
              class="filter-tab"
              data-filter-target="price">
              Price
            </button>
          </div>

          <div class="filter-right">
            <?php foreach ($filterGroups as $idx => $g): ?>
              <?php
                $paramKey = $g['param_key'];
                $selectedValues = $activeFilterValues[$paramKey] ?? [];
              ?>
              <div
                class="filter-pane<?php echo $idx === 0 ? ' active' : ''; ?>"
                id="filter-<?php echo htmlspecialchars($paramKey, ENT_QUOTES); ?>">
                <h4><?php echo htmlspecialchars($g['name'], ENT_QUOTES); ?></h4>

                <?php foreach ($g['options'] as $opt): ?>
                  <?php $val = $opt['value']; $label = $opt['label']; ?>
                  <label>
                    <input
                      type="checkbox"
                      name="<?php echo htmlspecialchars($paramKey, ENT_QUOTES); ?>[]"
                      value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>"
                      <?php echo in_array($val, $selectedValues, true) ? 'checked' : ''; ?>>
                    <?php echo htmlspecialchars($label, ENT_QUOTES); ?>
                  </label>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>

            <!-- Price pane (MOBILE) -->
            <div class="filter-pane" id="filter-price">
              <h4>Price</h4>
              <div class="price-row">
                <input
                  type="number"
                  name="m_price_min"
                  value="<?php echo $priceMin !== null ? htmlspecialchars($priceMin, ENT_QUOTES) : ''; ?>"
                  placeholder="Min"
                  class="mobile-price-min">
                <span>–</span>
                <input
                  type="number"
                  name="m_price_max"
                  value="<?php echo $priceMax !== null ? htmlspecialchars($priceMax, ENT_QUOTES) : ''; ?>"
                  placeholder="Max"
                  class="mobile-price-max">
              </div>
            </div>
          </div>
        </div>

        <div class="filter-sheet-footer">
          <div class="filter-count">
            <?php echo number_format($totalProducts); ?> products found
          </div>
          <button class="filter-apply" type="button">Apply</button>
        </div>
      </div>
    </div>
  </form>

  <!-- BRAND STRIP -->
  <!-- <section class="brand-strip">
    <div class="brand-strip-inner">
      <span>Wild Mountain</span>
      <span>Vintage Studio</span>
      <span>Organic Blend</span>
      <span>Inspire Graphic</span>
      <span>Pure Aroma</span>
    </div>
  </section> -->

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

  <!-- Back to top -->
  <div class="back-top" onclick="window.scrollTo({top:0,behavior:'smooth'});">
    <i class="fa-solid fa-angle-up"></i>
  </div>

  <!-- 🔹 Shared navbar JS -->
  <script src="assets/js/navbar.js"></script>

  <!-- Slider + Filters + AJAX -->
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    // ================= HERO SLIDER =================
    const track = document.querySelector('.shop-hero-track');
    if (track) {
      const slides = Array.from(document.querySelectorAll('.shop-hero-slide'));
      const dots   = Array.from(document.querySelectorAll('.shop-hero-dot'));

      if (slides.length > 1) {
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
          });
        });

        setInterval(function(){
          const next = (index + 1) % total;
          goToSlide(next);
        }, delay);

        goToSlide(0);
      }
    }

    // ================= FILTERS + AJAX =================
    const form           = document.getElementById('productFilterForm');
    const overlay        = document.querySelector('.filter-overlay');
    const openBtn        = document.querySelector('.filter-toggle');
    const backBtn        = document.querySelector('.filter-back');
    const applyMobileBtn = document.querySelector('.filter-apply');
    const clearMobileBtn = document.querySelector('.filter-clear');
    const tabs           = document.querySelectorAll('.filter-tab');
    const panes          = document.querySelectorAll('.filter-pane');
    const applyDesktopBtns = document.querySelectorAll('.btn-apply-desktop');
    const clearDesktopBtn  = document.querySelector('.btn-clear-desktop');

    function loadProducts(extraParams = {}) {
      if (!form) return;

      const formData = new FormData(form);
      const params   = new URLSearchParams(formData);

      // pagination support
      if (extraParams.page) {
        params.set('page', extraParams.page);
      }

      const queryString = params.toString();
      console.log('Sending filters:', queryString);

      fetch('product.php?ajax=1', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'filters=' + encodeURIComponent(queryString)
      })
      .then(res => res.text())
      .then(html => {
        const container = document.getElementById('productResults');
        if (container) {
          container.innerHTML = html;
          window.scrollTo({ top: container.offsetTop - 60, behavior: 'smooth' });
        }
      })
      .catch(err => {
        console.error('AJAX error:', err);
      });
    }

    // open/close mobile sheet
    function openFilter() {
      if (overlay) overlay.classList.add('open');
    }
    function closeFilter() {
      if (overlay) overlay.classList.remove('open');
    }

    if (openBtn) openBtn.addEventListener('click', openFilter);
    if (backBtn) backBtn.addEventListener('click', closeFilter);

    // mobile APPLY
    if (applyMobileBtn) {
      applyMobileBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (!form) return;

        // sync mobile price -> main price
        const mobileMin  = document.querySelector('.mobile-price-min');
        const mobileMax  = document.querySelector('.mobile-price-max');
        const mainMin    = form.querySelector('input[name="price_min"]');
        const mainMax    = form.querySelector('input[name="price_max"]');

        if (mobileMin && mainMin) mainMin.value = mobileMin.value;
        if (mobileMax && mainMax) mainMax.value = mobileMax.value;

        closeFilter();
        loadProducts();
      });
    }

    // desktop APPLY
    applyDesktopBtns.forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        loadProducts();
      });
    });

    // clear filter logic (shared for mobile & desktop)
    function clearAllFilters() {
      if (form) {
        form.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);
        form.querySelectorAll('input[type="number"]').forEach(i => i.value = '');
      }
      if (overlay) {
        overlay.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);
        overlay.querySelectorAll('input[type="number"]').forEach(i => i.value = '');
      }
      loadProducts();
    }

    if (clearMobileBtn) {
      clearMobileBtn.addEventListener('click', function (e) {
        e.preventDefault();
        clearAllFilters();
      });
    }
    if (clearDesktopBtn) {
      clearDesktopBtn.addEventListener('click', function (e) {
        e.preventDefault();
        clearAllFilters();
      });
    }

    // sort change – event delegation
    document.addEventListener('change', function (e) {
      if (e.target && e.target.matches('select[name="sort"]')) {
        e.preventDefault();
        loadProducts();
      }
    });

    // pagination – event delegation (because innerHTML replaces content)
    document.addEventListener('click', function (e) {
      const pageBtn = e.target.closest('.page-item');
      if (pageBtn && pageBtn.dataset.page) {
        e.preventDefault();
        loadProducts({ page: pageBtn.dataset.page });
      }
    });

    // mobile tabs
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

</body>
</html>