<?php
require_once __DIR__ . '/includes/db.php';

// ---------- 1. PARSE FILTERS FROM AJAX ----------
if (empty($_POST['filters'])) {
    // no filters sent, treat as empty array
    $filters = [];
} else {
    parse_str($_POST['filters'], $filters);
}

// OPTIONAL DEBUG (write to a log file)
/*
file_put_contents(
    __DIR__.'/debug_ajax_products.log',
    date('Y-m-d H:i:s') . "\n" . print_r(['POST' => $_POST, 'filters' => $filters], true) . "\n\n",
    FILE_APPEND
);
*/

// ---------- 2. LOAD FILTER GROUPS (for dynamic filters) ----------
$filterGroups = [];
try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM filter_groups
        WHERE is_active = 1
        ORDER BY sort_order ASC, name ASC
    ");
    $stmt->execute();
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtOpt = $pdo->prepare("
        SELECT *
        FROM filter_options
        WHERE is_active = 1
        ORDER BY sort_order ASC, label ASC
    ");
    $stmtOpt->execute();
    $options = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);

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

// ---------- 3. HELPERS ----------
function get_first_image($images) {
    $default = '/assets/images/avatar-default.png';
    if (!$images) return $default;

    $maybe = @json_decode($images, true);
    if (is_array($maybe) && !empty($maybe[0])) {
        $val = $maybe[0];
    } else {
        if (strpos($images, ',') !== false) {
            $parts = array_map('trim', explode(',', $images));
            $val   = $parts[0] ?? '';
        } else {
            $val = trim($images);
        }
    }

    if (!$val) return $default;

    if (preg_match('#^https?://#i', $val) || strpos($val, '/') === 0) {
        return $val;
    }
    return '/assets/uploads/products/' . ltrim($val, '/');
}

// ---------- 4. READ FILTER VALUES FROM $filters ----------
$priceMin = isset($filters['price_min']) && $filters['price_min'] !== ''
    ? (float)$filters['price_min']
    : null;

$priceMax = isset($filters['price_max']) && $filters['price_max'] !== ''
    ? (float)$filters['price_max']
    : null;

// category from sidebar (?cat=ID)
$categoryId = isset($filters['cat']) && $filters['cat'] !== ''
    ? (int)$filters['cat']
    : null;

$sort = $filters['sort'] ?? 'default';
$page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
$perPage = 9;
$offset  = ($page - 1) * $perPage;

// ---------- 5. BUILD WHERE CLAUSE ----------
$whereParts = ["(is_active = 1 OR is_active IS NULL)"];
$params     = [];
$paramIndex = 1;

// dynamic groups
foreach ($filterGroups as $g) {
    $paramKey   = $g['param_key'];   // e.g. "category", "range"
    $columnName = $g['column_name']; // e.g. "category_name", "range"

    if (isset($filters[$paramKey]) && is_array($filters[$paramKey])) {
        $vals = array_filter($filters[$paramKey], 'strlen');

        if (!empty($vals)) {
            $phList = [];

            foreach ($vals as $val) {
                $ph = ':' . $paramKey . $paramIndex;
                $phList[]     = $ph;
                $params[$ph]  = $val;
                $paramIndex++;
            }

            $whereParts[] = "{$columnName} IN (" . implode(',', $phList) . ")";
        }
    }
}

// category_id via cat=
if ($categoryId !== null && $categoryId > 0) {
    $whereParts[]           = "category_id = :category_id";
    $params[':category_id'] = $categoryId;
}

// price
if ($priceMin !== null) {
    $whereParts[]          = "price >= :price_min";
    $params[':price_min']  = $priceMin;
}
if ($priceMax !== null) {
    $whereParts[]          = "price <= :price_max";
    $params[':price_max']  = $priceMax;
}

$whereSql = '';
if (!empty($whereParts)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereParts);
}

// ---------- 6. SORT ----------
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
        $orderBy = 'id DESC';
}

// ---------- 7. QUERY DB ----------
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

    foreach ($params as $name => $val) {
        $stmtProducts->bindValue($name, $val);
    }

    $stmtProducts->bindValue(':limit',  $perPage, \PDO::PARAM_INT);
    $stmtProducts->bindValue(':offset', $offset,  \PDO::PARAM_INT);

    $stmtProducts->execute();
    $products = $stmtProducts->fetchAll(\PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}

// helper to build page URL (if user clicks pagination = full reload)
function buildPageUrlAjax($pageNum, $filters) {
    $filters['page'] = $pageNum;
    return 'product.php?' . http_build_query($filters);
}
?>
<!-- 🔹 THIS IS THE CONTENT THAT WILL REPLACE #productResults -->

<div class="shop-toolbar">
  <div class="shop-view-toggle">
    <button class="filter-toggle" type="button">
      <i class="fa-solid fa-sliders"></i>
      <span>Filter</span>
    </button>
  </div>

  <div class="shop-sort">
    <span>Sort by:</span>
    <select name="sort">
      <option value="default"    <?= $sort === 'default'    ? 'selected' : ''; ?>>Default sorting</option>
      <option value="popularity" <?= $sort === 'popularity' ? 'selected' : ''; ?>>Sort by popularity</option>
      <option value="latest"     <?= $sort === 'latest'     ? 'selected' : ''; ?>>Sort by latest</option>
      <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : ''; ?>>Sort by price: low to high</option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : ''; ?>>Sort by price: high to low</option>
    </select>
  </div>
</div>

<?php if (empty($products)): ?>
  <p style="font-size:14px;color:#777;">No products found with these filters.</p>
<?php else: ?>
  <div class="products-grid">
    <?php foreach ($products as $p): ?>
      <?php
        $name        = $p['name'] ?? 'Product';
        $price       = isset($p['price']) ? (float)$p['price'] : 0;
        $oldPrice    = (isset($p['old_price']) && $p['old_price'] > $price)
                       ? (float)$p['old_price']
                       : null;
        $img         = get_first_image($p['images'] ?? '');
        $rating      = isset($p['rating']) ? (float)$p['rating'] : 0;
        $ratingCount = isset($p['rating_count']) ? (int)$p['rating_count'] : 0;
        $stars       = $rating > 0 ? str_repeat('★', round($rating)) : '★★★★★';
      ?>
      <article class="product-card">
        <div class="product-image-wrap">
          <?php if ($oldPrice): ?>
            <span class="product-badge sale">Sale</span>
          <?php else: ?>
            <span class="product-badge">New</span>
          <?php endif; ?>

          <img src="<?= htmlspecialchars($img, ENT_QUOTES); ?>"
               alt="<?= htmlspecialchars($name, ENT_QUOTES); ?>">

          <div class="product-actions">
            <span><i class="fa-regular fa-heart"></i></span>
            <span><i class="fa-regular fa-eye"></i></span>
            <span><i class="fa-solid fa-bag-shopping"></i></span>
          </div>
        </div>
        <div class="product-info">
          <div class="product-name">
            <?= htmlspecialchars($name, ENT_QUOTES); ?>
          </div>
          <div class="product-price">
            <?php if ($oldPrice): ?>
              <span class="old">₹<?= number_format($oldPrice, 2); ?></span>
            <?php endif; ?>
            ₹<?= number_format($price, 2); ?>
          </div>
          <div class="product-stars">
            <?= $stars; ?>
            <span>(<?= $ratingCount; ?>)</span>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a
        href="<?= htmlspecialchars(buildPageUrlAjax($p, $filters), ENT_QUOTES); ?>"
        class="page-item<?= $p === $page ? ' active' : ''; ?>"
      >
        <?= $p; ?>
      </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
      <a
        href="<?= htmlspecialchars(buildPageUrlAjax($page + 1, $filters), ENT_QUOTES); ?>"
        class="page-item"
      >
        <i class="fa-solid fa-angle-right"></i>
      </a>
    <?php endif; ?>
  </div>
<?php endif; ?>