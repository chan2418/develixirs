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

// ==================== CATEGORY RESOLUTION (Moved to Top) ====================

// 1. Basic ID from URL
$categoryId = isset($_GET['cat']) && $_GET['cat'] !== '' ? (int)$_GET['cat'] : null;

// 2. Parse Category Names from sidebar/filter params
$selectedCategoryName = null; // parent-only name -> "Men Care"
$selectedCategoryFull = null; // full path        -> "Men Care / Face Wash"

if (isset($_GET['category'])) {
    $rawVals = is_array($_GET['category']) ? $_GET['category'] : [$_GET['category']];
    foreach ($rawVals as $rawVal) {
        $val = trim($rawVal);
        if ($val === '') continue;
        if (strpos($val, '/') !== false) {
            if ($selectedCategoryFull === null) {
                $selectedCategoryFull = $val;
                $parts = explode('/', $val, 2);
                $parentName = trim($parts[0] ?? '');
                if ($parentName !== '' && $selectedCategoryName === null) {
                    $selectedCategoryName = $parentName;
                }
            }
        } else {
            if ($selectedCategoryName === null) {
                $selectedCategoryName = $val;
            }
        }
    }
}

// 3. Resolve these Names to IDs
$resolvedParentId = null;
$resolvedSubId    = null;
$bannerCategoryId = null;

// Column detection (name vs title)
$categoryLabelField = 'name'; // Default
try {
    $cols = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $fields = array_column($cols, 'Field');
    if (in_array('title', $fields)) $categoryLabelField = 'title';
} catch (Exception $e) {}

if ($categoryLabelField) {
    try {
        // A) Full path "Parent / Sub"
        if (!empty($selectedCategoryFull)) {
            $parts = explode('/', $selectedCategoryFull, 2);
            $pName = trim($parts[0]);
            $cName = trim($parts[1]);
            
            $stmt = $pdo->prepare("SELECT parent.id as pid, child.id as cid FROM categories parent JOIN categories child ON child.parent_id = parent.id WHERE parent.$categoryLabelField = ? AND child.$categoryLabelField = ? LIMIT 1");
            $stmt->execute([$pName, $cName]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $resolvedParentId = $row['pid'];
                $resolvedSubId = $row['cid'];
            }
        }
        // B) Just Parent "Parent"
        if ($resolvedParentId === null && !empty($selectedCategoryName)) {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE $categoryLabelField = ? AND (parent_id IS NULL OR parent_id = 0) LIMIT 1");
            $stmt->execute([$selectedCategoryName]);
            if ($id = $stmt->fetchColumn()) {
                $resolvedParentId = $id;
            }
        }
    } catch(Exception $e) {}
}

// 4. Final Decision for "Current Page Category"
if ($resolvedSubId !== null) {
    $bannerCategoryId = $resolvedSubId;
} elseif ($resolvedParentId !== null) {
    $bannerCategoryId = $resolvedParentId;
} elseif ($categoryId !== null && $categoryId > 0) {
    $bannerCategoryId = $categoryId;
}

// ===== LOAD ALL FILTER GROUPS + OPTIONS =====
$filterGroups = [];   // each group has its options inside

try {
    // load groups (FALLBACK LOGIC)
    $sqlGroups = "SELECT * FROM filter_groups WHERE is_active = 1 AND category_id IS NULL ORDER BY sort_order ASC, name ASC"; // Default Common
    
    // If we are on a category page, check for specific filters
    if ($bannerCategoryId) {
        // Check if this category has specific filters
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM filter_groups WHERE is_active = 1 AND category_id = ?");
        $stmtCheck->execute([$bannerCategoryId]);
        if ($stmtCheck->fetchColumn() > 0) {
            // Yes, load specific filters INSTEAD of common ones
            $sqlGroups = "SELECT * FROM filter_groups WHERE is_active = 1 AND category_id = " . (int)$bannerCategoryId . " ORDER BY sort_order ASC, name ASC";
        }
    }

    $stmt = $pdo->prepare($sqlGroups);
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

function get_product_page_first_image($images) {
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

// (Moved logic to top)
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
$whereParts = ["products.is_active = 1"];
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

    // For dynamic filters (NOT category), use product_filter_values table
    $optionIds = array_filter($vals, function($v) {
        return is_numeric($v) && (int)$v > 0;
    });
    
    if (empty($optionIds)) {
        continue;
    }
    
    $phList = [];
    foreach ($optionIds as $optId) {
        $ph = ':opt_' . $paramIndex;
        $paramIndex++;
        $phList[] = $ph;
        $params[$ph] = (int)$optId;
    }
    
    $subquery = "products.id IN (
        SELECT product_id 
        FROM product_filter_values 
        WHERE filter_group_id = " . (int)$g['id'] . "
        AND filter_option_id IN (" . implode(',', $phList) . ")
    )";
    
    $whereParts[] = $subquery;
}

// 2) Category filter (from cat= in URL – optional)
// If a parent category is clicked, include all its subcategories too.
// Only if there is NO "category[]" filter present (i.e. not coming from Men Care filter / sidebar)
$hasCategoryFilter = !empty($_GET['category']);

if ($categoryId !== null && $categoryId > 0 && !$hasCategoryFilter) {
    // 👉 here you can keep either the simple version:
    // $whereParts[]           = "cat_id = :cat_id";
    // $params[':cat_id'] = $categoryId;

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

// 4) Search filter (q=)
// 4) Search filter (q=) - STRONG SEARCH
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($searchQuery !== '') {
    // Split by space to allow "face wash" to match "face herbal wash"
    $keywords = explode(' ', $searchQuery);
    $keywordGroups = [];
    
    foreach ($keywords as $index => $word) {
        $word = trim($word);
        if (empty($word)) continue;
        
        $pName = ":sq_name_{$index}";
        $pDesc = ":sq_desc_{$index}";
        $pCat  = ":sq_cat_{$index}";
        
        $keywordGroups[] = "(products.name LIKE {$pName} OR products.description LIKE {$pDesc} OR categories.name LIKE {$pCat})";
        $params[$pName] = '%' . $word . '%';
        $params[$pDesc] = '%' . $word . '%';
        $params[$pCat]  = '%' . $word . '%';
    }
    
    if (!empty($keywordGroups)) {
        // AND logic: Product must match ALL keywords (in either name or description)
        $whereParts[] = '(' . implode(' AND ', $keywordGroups) . ')';
    }
}

// 5) Product Group Filter (group_id=)
$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
if ($groupId > 0) {
    try {
        // Ensure the table exists to avoid errors if not created yet
        // A simple subquery: id IN (...)
        $whereParts[] = "products.id IN (SELECT product_id FROM product_group_map WHERE group_id = :group_id)";
        $params[':group_id'] = $groupId;
    } catch (Exception $e) {
        // ignore if table doesn't exist
    }
}

// 6) Homepage Section Filter (section=)
$sectionFilter = isset($_GET['section']) ? trim($_GET['section']) : '';
if ($sectionFilter !== '') {
    try {
        // Filter by homepage_products table
        $whereParts[] = "products.id IN (SELECT product_id FROM homepage_products WHERE section = :section)";
        $params[':section'] = $sectionFilter;
    } catch (Exception $e) {
        // ignore
    }
}

// 7) Concern Filter (concern=slug)
$concernSlug = isset($_GET['concern']) ? trim($_GET['concern']) : '';
if ($concernSlug !== '') {
    try {
        // Find concern ID from slug
        $stmtC = $pdo->prepare("SELECT id FROM concerns WHERE slug = ? LIMIT 1");
        $stmtC->execute([$concernSlug]);
        $concernId = $stmtC->fetchColumn();
        
        if ($concernId) {
             $whereParts[] = "products.concern_id = :concern_id";
             $params[':concern_id'] = $concernId;
        }
    } catch (Exception $e) { }
}

// 8) Seasonal Filter (seasonal=slug)
$seasonalSlug = isset($_GET['seasonal']) ? trim($_GET['seasonal']) : '';
if ($seasonalSlug !== '') {
    try {
        // Find seasonal ID from slug
        $stmtSea = $pdo->prepare("SELECT id FROM seasonals WHERE slug = ? LIMIT 1");
        $stmtSea->execute([$seasonalSlug]);
        $seasonalId = $stmtSea->fetchColumn();
        
        if ($seasonalId) {
             $whereParts[] = "products.seasonal_id = :seasonal_id";
             $params[':seasonal_id'] = $seasonalId;
        }
    } catch (Exception $e) { }
}

// 6) Final WHERE SQL
$whereSql = '';
if (!empty($whereParts)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereParts);
}

/* ----- sorting ----- */
/* ----- sorting ----- */
// Default sort logic
$orderBy = 'id DESC';

// If searching, prioritize relevance (name match)
if ($searchQuery !== '' && $sort === 'default') {
    $relevanceParts = [];
    foreach ($keywords as $word) {
        $word = trim($word);
        if (empty($word)) continue;
        // Score 2 for name match, 1 for description match
        // We need to bind these params or use the existing ones if we can ensure index matching.
        // Since we are inside the loop where we generated params, we can reuse the :sq_name_X params if we are careful.
        // But $orderBy is constructed AFTER the loop.
        // We need to reconstruct the relevance SQL safely.
        // Actually, we can just inject the values since we are using prepared statements for the main query, 
        // but ORDER BY usually can't take params in some drivers? 
        // PDO allows params in ORDER BY? No, usually not for expressions.
        // Wait, we can put expressions in SELECT list and order by alias?
        // Or just put the expression in ORDER BY.
        // To be safe against SQL injection, we should use the params we already created.
        
        // We'll use the same params :sq_name_0, etc.
        // But we need to make sure they are available to the query. They are in $params.
        
        $pName = ":sq_name_{$index}"; // Note: $index is from the loop above, but we are outside it now.
        // We need to loop again or store the parts.
    }
    
    // Let's rebuild the parts using the same loop structure
    // BUT: We need SEPARATE parameters for ORDER BY because PDO doesn't allow reusing
    // the same named parameter when emulation is off!
    $relevanceScore = [];
    foreach ($keywords as $index => $word) {
        $word = trim($word);
        if (empty($word)) continue;
        $pRel = ":rel_name_{$index}";  // NEW parameter name for ORDER BY
        // Score 2 points if name matches
        $relevanceScore[] = "(CASE WHEN products.name LIKE {$pRel} THEN 2 ELSE 0 END)";
        $params[$pRel] = '%' . $word . '%';  // Bind the same value but with different param name
    }
    
    if (!empty($relevanceScore)) {
        $orderBy = "(" . implode(' + ', $relevanceScore) . ") DESC, id DESC";
    }
}

switch ($sort) {
    case 'popularity':
        // Fallback to created_at if sold_count doesn't exist
        $orderBy = 'created_at DESC';
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
    // default case is handled above
}

/* ----- total count for pagination ----- */
$totalProducts = 0;
try {
    $sqlCount = "SELECT COUNT(*) FROM products LEFT JOIN categories ON products.category_id = categories.id {$whereSql}";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalProducts = (int)$stmtCount->fetchColumn();

    // 🔹 FALLBACK SEARCH: If 0 results and we have multiple keywords, try "OR" logic instead of "AND"
    if ($totalProducts === 0 && $searchQuery !== '' && count($keywords) > 1) {
        // Remove the last WHERE part (which is the strict AND search condition)
        // NOTE: This assumes search logic was the last thing added to $whereParts.
        // If code changes, this might need to be more robust (e.g. by key).
        array_pop($whereParts);

        // Re-add with OR
        $whereParts[] = '(' . implode(' OR ', $keywordGroups) . ')';
        
        // Rebuild SQL
        $whereSql = 'WHERE ' . implode(' AND ', $whereParts);
        
        // Re-run Count
        $sqlCount = "SELECT COUNT(*) FROM products LEFT JOIN categories ON products.category_id = categories.id {$whereSql}";
        $stmtCount = $pdo->prepare($sqlCount);
        $stmtCount->execute($params);
        $totalProducts = (int)$stmtCount->fetchColumn();
    }
} catch (PDOException $e) {
    $totalProducts = 0;
}

$totalPages = $totalProducts > 0 ? (int)ceil($totalProducts / $perPage) : 1;

/* ----- fetch products for current page ----- */
$products = [];
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
        $sqlProducts = "
            SELECT products.*, categories.name as category_name,
                   product_labels.name AS label_name,
                   product_labels.color AS label_color,
                   product_labels.text_color AS label_text_color,
                   COALESCE(AVG(product_reviews.rating), 0) as avg_rating,
                   COUNT(product_reviews.id) as review_count
            FROM products
            LEFT JOIN categories ON products.category_id = categories.id
            LEFT JOIN product_labels ON products.label_id = product_labels.id AND product_labels.is_active = 1
            LEFT JOIN product_reviews ON products.id = product_reviews.product_id AND product_reviews.status = 'approved'
            {$whereSql}
            GROUP BY products.id
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset
        ";
    } else {
        $sqlProducts = "
            SELECT products.*, categories.name as category_name,
                   COALESCE(AVG(product_reviews.rating), 0) as avg_rating,
                   COUNT(product_reviews.id) as review_count
            FROM products
            LEFT JOIN categories ON products.category_id = categories.id
            LEFT JOIN product_reviews ON products.id = product_reviews.product_id AND product_reviews.status = 'approved'
            {$whereSql}
            GROUP BY products.id
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset
        ";
    }
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
    // Log error silently
    error_log("Product fetch error: " . $e->getMessage());
}

/* ==================== BANNERS FOR PRODUCT PAGE (CATEGORY AWARE) ==================== */

/* ==================== BANNERS FOR PRODUCT PAGE (CATEGORY AWARE) ==================== */

/* ==================== BANNERS FOR PRODUCT PAGE (CATEGORY AWARE) ==================== */

/* ==================== BANNERS FOR PRODUCT PAGE (CATEGORY AWARE) ==================== */

// (Moved Logic to top)

// 6️⃣ Fetch Category SEO & FAQs if a category is selected
$catSeo = ['title' => 'Devilixirs – Shop', 'desc' => '', 'faqs' => [], 'gallery' => []];

if ($bannerCategoryId) { // Use resolved category ID
    try {
        $stmtSeo = $pdo->prepare("SELECT meta_title, meta_description, description, image, faqs, media_gallery FROM categories WHERE id = ?");
        $stmtSeo->execute([$bannerCategoryId]);
        $rowSeo = $stmtSeo->fetch(PDO::FETCH_ASSOC);
        if ($rowSeo) {
            if (!empty($rowSeo['meta_title'])) $catSeo['title'] = $rowSeo['meta_title'];
            if (!empty($rowSeo['meta_description'])) $catSeo['desc'] = $rowSeo['meta_description'];
            if (!empty($rowSeo['description'])) $catSeo['content'] = $rowSeo['description'];
            if (!empty($rowSeo['image'])) $catSeo['main_image'] = $rowSeo['image'];
            if (!empty($rowSeo['faqs'])) $catSeo['faqs'] = json_decode($rowSeo['faqs'], true) ?: [];
            if (!empty($rowSeo['media_gallery'])) $catSeo['gallery'] = json_decode($rowSeo['media_gallery'], true) ?: [];
        }
    } catch (PDOException $e) { /* Silently fail */ }
}

// 6.5 PREVIEW OVERRIDE (Category Mode)
if (!empty($_GET['preview_token']) && session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['previews'][$_GET['preview_token']])) {
    $pData = $_SESSION['previews'][$_GET['preview_token']];
    // Check if this preview data is actually for a category (optional safety)
    if (($pData['preview_type'] ?? '') === 'category') {
        if (!empty($pData['title'])) $catSeo['title'] = $pData['title'];
        if (!empty($pData['meta_title'])) $catSeo['title'] = $pData['meta_title']; // Meta title takes precedence
        if (!empty($pData['meta_description'])) $catSeo['desc'] = $pData['meta_description'];
        if (isset($pData['description'])) $catSeo['content'] = $pData['description']; // Allow empty string to clear

        // Main Image (Upload > Selected > DB/Existing fallback)
        if (!empty($pData['image_paths'][0])) {
            $catSeo['main_image'] = $pData['image_paths'][0];
        } elseif (!empty($pData['image_selected'])) {
            $catSeo['main_image'] = $pData['image_selected'];
        }

        // Gallery Construction
        // start with existing (hidden inputs)
        $pGallery = [];
        if (!empty($pData['existing_images'])) {
             $pGallery = is_array($pData['existing_images']) ? $pData['existing_images'] : explode(',', $pData['existing_images']);
        }
        // merge library selections
        if (!empty($pData['media_gallery_selected'])) {
            $libSel = json_decode($pData['media_gallery_selected'], true) ?: [];
            $pGallery = array_merge($pGallery, $libSel);
        }
        // merge new uploads
        if (!empty($pData['media_gallery_paths'])) {
            $pGallery = array_merge($pGallery, $pData['media_gallery_paths']);
        }
        // Unique and assign if not empty (or empty to clear if intention was clear)
        $catSeo['gallery'] = array_values(array_unique($pGallery));

        // FAQs
        if (!empty($pData['faq_questions'])) {
            $catSeo['faqs'] = [];
            foreach ($pData['faq_questions'] as $i => $q) {
                if (!empty($q)) {
                    $catSeo['faqs'][] = ['q' => $q, 'a' => $pData['faq_answers'][$i] ?? ''];
                }
            }
        }
    }
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
            LIMIT 1
        ");
        $stmt->execute([':cid' => $bannerCategoryId]);
        $heroBanners = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        $heroBanners = [];
        error_log('Category banner query error: ' . $e->getMessage());
    }
}

// 5️⃣ Fallback – generic product banners
// Only show fallback IF: no specific banner AND no category text/gallery to show
// We check catSeo main_image and gallery.
if (empty($heroBanners) && empty($catSeo['main_image']) && empty($catSeo['gallery'])) {
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

            $img = get_product_page_first_image($p['images'] ?? '');

            // Get ratings from the query result (calculated from reviews)
            $rating      = isset($p['avg_rating']) ? (float)$p['avg_rating'] : 0;
            $ratingCount = isset($p['review_count']) ? (int)$p['review_count'] : 0;
            
            // Generate stars based on actual rating
            $fullStars = floor($rating);
            $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
            $emptyStars = 5 - $fullStars - $halfStar;
            
            $stars = str_repeat('★', $fullStars);
            if ($halfStar) $stars .= '✰';
            $stars .= str_repeat('☆', $emptyStars);
            
            // Prepare WhatsApp Link
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $fullProductUrl = $protocol . "://" . $host . "/" . $detailUrl;
            $waMessage = "Hi, I'm interested in this product: " . $name . " - " . $fullProductUrl;
            $waLink = "https://wa.me/919500650454?text=" . urlencode($waMessage);
          ?>
          <article class="product-card">
            <!-- 🔹 Wrap clickable area with <a> -->
            <a href="<?php echo htmlspecialchars($detailUrl, ENT_QUOTES); ?>" class="product-link" style="display:block;">
              <div class="product-image-wrap">
                <?php if (!empty($p['label_name'])): ?>
                  <span class="product-badge" style="background-color: <?php echo htmlspecialchars($p['label_color'] ?? '#000000', ENT_QUOTES); ?>; color: <?php echo htmlspecialchars($p['label_text_color'] ?? '#FFFFFF', ENT_QUOTES); ?>;">
                    <?php echo htmlspecialchars($p['label_name'], ENT_QUOTES); ?>
                  </span>
                <?php endif; ?>

                <img
                  src="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>"
                  alt="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>">

                <!-- Separate Large WhatsApp Button -->
                <span class="whatsapp-inquiry-btn large-wa-btn" 
                      data-url="<?php echo htmlspecialchars($waLink, ENT_QUOTES); ?>" 
                      title="Inquire on WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i>
                </span>

                <div class="product-actions">
                  <span class="wishlist-btn" data-product-id="<?php echo $productId; ?>"><i class="fa-regular fa-heart"></i></span>
                  <span class="buy-now-btn" data-product-id="<?php echo $productId; ?>" title="Buy Now"><i class="fa-solid fa-bolt"></i></span>
                  <span class="cart-btn" data-product-id="<?php echo $productId; ?>"><i class="fa-solid fa-bag-shopping"></i></span>
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


// 7️⃣ OFF: User requested NOT to merge Category Main Image + Gallery into Hero Banners
// (They only want banners from the 'banners' table)
$catBanners = [];

/* 
// A) Add Main Image (if exists)
if (!empty($catSeo['main_image'])) {
    $img = $catSeo['main_image'];
    $fullUrl = (strpos($img, '/') === 0) ? $img : '/assets/uploads/categories/' . $img;
    $catBanners[] = [
        'filename' => $img,
        'full_url' => $fullUrl,
        'alt_text' => $catSeo['title'],
        'link'     => ''
    ];
}

// B) Add Gallery Images
if (!empty($catSeo['gallery'])) {
    foreach ($catSeo['gallery'] as $img) {
        $fullUrl = (strpos($img, '/') === 0) ? $img : '/assets/uploads/categories/' . $img;
        $catBanners[] = [
            'filename' => $img, 
            'full_url' => $fullUrl,
            'alt_text' => $catSeo['title'],
            'link'     => ''
        ];
    }
}

if (!empty($catBanners)) {
    $heroBanners = array_merge($catBanners, $heroBanners);
}
*/

?>
<?php 
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
  <title><?= htmlspecialchars($catSeo['title']) ?></title>
  <?php if (!empty($catSeo['desc'])): ?>
  <meta name="description" content="<?= htmlspecialchars($catSeo['desc']) ?>">
  <?php endif; ?>
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


/* Hide mobile filter overlay by default on ALL screens */
.filter-overlay {
  display: none;
}

@media(max-width:576px){
  .products-grid{
    /* Hide desktop header & nav on mobile */
    .abc, .header, .nav {
      display:none !important;
    }

    /* Adjust shop content margin for fixed mobile header */
    .shop-hero {
      margin-top:20px; /* Reduced from 60px since main has padding */
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
            if (!empty($b['full_url'])) {
                $src = $b['full_url'];
            } else {
                $src = '/assets/uploads/banners/' . ltrim($b['filename'] ?? '', '/');
            }
            $alt  = $b['alt_text'] ?? '';
            $link = trim($b['link'] ?? '');
          ?>
          <?php
            // Check if it's a video
            $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
            $isVid = in_array($ext, ['mp4', 'webm', 'ogg']);
          ?>
          
          <div class="shop-hero-slide" <?php if(!$isVid) echo 'style="background-image:url(\'' . htmlspecialchars($src, ENT_QUOTES) . '\');"'; ?>>
            <?php if ($isVid): ?>
                <video src="<?= htmlspecialchars($src, ENT_QUOTES) ?>" autoplay loop muted playsinline style="position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; z-index:0;"></video>
                <!-- Add a dark overlay for text readability if needed -->
                <div style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.3); z-index:1;"></div>
            <?php endif; ?>

            <?php if ($link): ?>
              <a href="<?php echo htmlspecialchars($link, ENT_QUOTES); ?>" class="shop-hero-slide-link" style="z-index:2;">
            <?php endif; ?>

              <div class="shop-hero-overlay" style="z-index:2;">
                <div class="shop-hero-inner">
                  <!-- Alt text hidden by request
                  <?php if (!empty($b['alt_text'])): ?>
                    <h1><?php echo htmlspecialchars($b['alt_text'], ENT_QUOTES, 'UTF-8'); ?></h1>
                  <?php endif; ?>
                  -->
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
                      $optId = $opt['id'];  // Use ID for filter matching
                      $label = $opt['label'];
                      $isChecked = in_array($optId, $selectedValues, false);
                    ?>
                    <li>
                      <label>
                        <input
                          type="checkbox"
                          name="<?php echo htmlspecialchars($paramKey, ENT_QUOTES); ?>[]"
                          value="<?php echo (int)$optId; ?>"
                          <?php echo $isChecked ? 'checked' : ''; ?>
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

  <!-- CATEGORY CONTENT SECTION (Description + FAQs + Gallery) -->
  <?php if (!empty($catSeo['content']) || !empty($catSeo['faqs']) || !empty($catSeo['gallery'])): ?>
  <section class="category-content-section" style="max-width:1200px; margin:40px auto 60px; padding:0 15px; border-top:1px solid #eee; pt-10">
      
      <!-- 1. Rich Text Description -->
      <?php if (!empty($catSeo['content'])): ?>
          <div class="cat-description rich-text" style="color:#333 !important; font-size:15px; line-height:1.8; margin-top:40px; margin-bottom:40px;">
              <?= $catSeo['content'] ?>
          </div>
      <?php endif; ?>

      <!-- 2. Media Gallery Grid (Bottom) -->
      <?php if (!empty($catSeo['gallery'])): ?>
          <div class="cat-gallery" style="margin-bottom:40px;">
              <h3 style="font-size:20px; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:20px; color:#111;">Gallery</h3>
              <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:15px;">
                  <?php foreach ($catSeo['gallery'] as $img): 
                      $gSrc = (strpos($img, '/') === 0) ? $img : '/assets/uploads/categories/' . $img;
                      $ext = strtolower(pathinfo($gSrc, PATHINFO_EXTENSION));
                      $isVid = in_array($ext, ['mp4', 'webm', 'ogg']);
                  ?>
                      <div style="height:200px; border-radius:8px; overflow:hidden; border:1px solid #eee; background:#f9f9f9;">
                          <?php if ($isVid): ?>
                              <video src="<?= htmlspecialchars($gSrc) ?>" controls style="width:100%; height:100%; object-fit:cover;"></video>
                          <?php else: ?>
                              <img src="<?= htmlspecialchars($gSrc) ?>" style="width:100%; height:100%; object-fit:cover;">
                          <?php endif; ?>
                      </div>
                  <?php endforeach; ?>
              </div>
          </div>
      <?php endif; ?>

      <!-- 3. FAQs Accordion -->
      <?php if (!empty($catSeo['faqs'])): ?>
          <div class="cat-faqs">
              <h3 style="font-size:20px; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:20px; border-bottom:1px solid #ddd; padding-bottom:10px; color:#111;">Frequently Asked Questions</h3>
              
              <div class="faq-accordion">
                  <?php foreach ($catSeo['faqs'] as $idx => $faq): ?>
                      <div class="faq-item" style="border-bottom:1px solid #eee;">
                          <button class="faq-btn" onclick="toggleFaq(this)" style="width:100%; text-align:left; padding:15px 0; background:none; border:none; cursor:pointer; font-size:15px; font-weight:600; color:#333; display:flex; justify-content:space-between; align-items:center;">
                              <span><?= htmlspecialchars($faq['q']) ?></span>
                              <i class="fa-solid fa-chevron-down transition-transform duration-300" style="color:#777;"></i>
                          </button>
                          <div class="faq-answer" style="display:none; padding-bottom:15px; color:#555; font-size:14px; line-height:1.6;">
                              <?= nl2br(htmlspecialchars($faq['a'])) ?>
                          </div>
                      </div>
                  <?php endforeach; ?>
              </div>
          </div>

          <script>
            function toggleFaq(btn) {
                const answer = btn.nextElementSibling;
                const icon = btn.querySelector('i');
                if (answer.style.display === 'none') {
                    answer.style.display = 'block';
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    answer.style.display = 'none';
                    icon.style.transform = 'rotate(0deg)';
                }
            }
          </script>
      <?php endif; ?>
      
  </section>
  <style>
    .cat-description.rich-text h2 { font-size:18px; margin-top:20px; margin-bottom:10px; color:#111; }
    .cat-description.rich-text h3 { font-size:16px; margin-top:15px; margin-bottom:8px; color:#111; }
    .cat-description.rich-text p { margin-bottom:15px; color:#333; }
    .cat-description.rich-text ul { list-style:disc; margin-left:20px; margin-bottom:15px; color:#333; }
    .cat-description.rich-text img { max-width:100%; height:auto; border-radius:4px; margin:10px 0; }
  </style>
  <?php endif; ?>

  <!-- FOOTER -->
  <?php include 'footer.php'; ?>



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

      // 1. Preserve 'cat' from current URL
      const currentUrlParams = new URLSearchParams(window.location.search);
      if (currentUrlParams.has('cat')) {
        params.set('cat', currentUrlParams.get('cat'));
      }
      // Also preserve 'category' if it's in the URL but not in form (for sidebar links)
      if (currentUrlParams.has('category') && !params.has('category')) {
          params.set('category', currentUrlParams.get('category'));
      }

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


<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Load initial wishlist state
    fetch('ajax_wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_all'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.ids) {
            data.ids.forEach(id => {
                const buttons = document.querySelectorAll(`.wishlist-btn[data-product-id="${id}"]`);
                buttons.forEach(btn => {
                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-regular');
                        icon.classList.add('fa-solid');
                        icon.style.color = 'red';
                        btn.setAttribute('data-in-wishlist', 'true');
                    }
                });
            });
        }
    })
    .catch(err => console.error('Wishlist load error:', err));

    // 2. Handle click with immediate visual feedback
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.wishlist-btn');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const productId = btn.getAttribute('data-product-id');
        const icon = btn.querySelector('i');
        const isInWishlist = btn.getAttribute('data-in-wishlist') === 'true';

        // Immediate visual feedback (optimistic update)
        if (isInWishlist) {
            icon.classList.remove('fa-solid');
            icon.classList.add('fa-regular');
            icon.style.color = '';
            btn.setAttribute('data-in-wishlist', 'false');
        } else {
            icon.classList.remove('fa-regular');
            icon.classList.add('fa-solid');
            icon.style.color = 'red';
            btn.setAttribute('data-in-wishlist', 'true');
        }

        // Send request to server
        fetch('ajax_wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle&product_id=${productId}`
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                // Revert on error
                if (isInWishlist) {
                    icon.classList.remove('fa-regular');
                    icon.classList.add('fa-solid');
                    icon.style.color = 'red';
                    btn.setAttribute('data-in-wishlist', 'true');
                } else {
                    icon.classList.remove('fa-solid');
                    icon.classList.add('fa-regular');
                    icon.style.color = '';
                    btn.setAttribute('data-in-wishlist', 'false');
                }
                alert(data.message || 'Error updating wishlist');
            } else {
                // Update all buttons with same product ID on the page
                const allButtons = document.querySelectorAll(`.wishlist-btn[data-product-id="${productId}"]`);
                allButtons.forEach(b => {
                    const i = b.querySelector('i');
                    if (data.status === 'added') {
                        i.classList.remove('fa-regular');
                        i.classList.add('fa-solid');
                        i.style.color = 'red';
                        b.setAttribute('data-in-wishlist', 'true');
                    } else {
                        i.classList.remove('fa-solid');
                        i.classList.add('fa-regular');
                        i.style.color = '';
                        b.setAttribute('data-in-wishlist', 'false');
                    }
                });
                
                // Show toast notification
                showToast(data.status === 'added' ? 'Added to wishlist!' : 'Removed from wishlist!', 'success');
                
                // 🔹 Update navbar wishlist counter
                if (data.wishlist_count !== undefined) {
                    const wishlistCountEl = document.querySelector('.wishlist-count');
                    if (wishlistCountEl) {
                        wishlistCountEl.textContent = data.wishlist_count;
                        if (data.wishlist_count > 0) {
                            wishlistCountEl.style.display = 'flex';
                            wishlistCountEl.style.position = 'absolute';
                            wishlistCountEl.style.top = '-6px';
                            wishlistCountEl.style.right = '-6px';
                        } else {
                            wishlistCountEl.style.display = 'none';
                        }
                    }
                    
                    // Update mobile wishlist count
                    const mobileWishlistSpans = document.querySelectorAll('.mobile-bottom-nav a[href*="wishlist"] span, .mobile-menu-list a[href*="wishlist"]');
                    mobileWishlistSpans.forEach(span => {
                        if (span.textContent.includes('Wishlist')) {
                            span.textContent = `Wishlist (${data.wishlist_count})`;
                        }
                    });
                }
            }
        })
        .catch(err => {
            console.error('Wishlist toggle error:', err);
            // Revert on error
            if (isInWishlist) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
                icon.style.color = 'red';
                btn.setAttribute('data-in-wishlist', 'true');
            } else {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
                icon.style.color = '';
                btn.setAttribute('data-in-wishlist', 'false');
            }
            alert('Network error. Please try again.');
        });
    });

    // Toast function for wishlist
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: ${type === 'success' ? '#4CAF50' : '#f44336'};
            color: white;
            padding: 16px 24px;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }
});
</script>

<script>
// Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    // Create toast container if it doesn't exist
    if (!document.getElementById('toast-container')) {
        const toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = 'position:fixed;top:80px;right:20px;z-index:9999;';
        document.body.appendChild(toastContainer);
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.style.cssText = `
            background: ${type === 'success' ? '#4CAF50' : '#f44336'};
            color: white;
            padding: 16px 24px;
            border-radius: 4px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
            font-size: 14px;
        `;
        toast.innerHTML = `
            <i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.getElementById('toast-container').appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Add animation styles
    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.cart-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();

            const productId = btn.getAttribute('data-product-id');
            const icon = btn.querySelector('i');

            // Show adding animation
            const originalColor = icon.style.color;
            icon.style.color = '#4CAF50';

            fetch('ajax_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add&product_id=${productId}&quantity=1`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Product added to cart successfully!', 'success');
                    
                    // Update navbar count
                    const cartCountEl = document.querySelector('.cart-count');
                    if (cartCountEl) {
                        cartCountEl.textContent = data.count;
                        if (data.count > 0) {
                            cartCountEl.style.display = 'flex';
                            cartCountEl.style.position = 'absolute';
                            cartCountEl.style.top = '-6px';
                            cartCountEl.style.right = '-6px';
                        } else {
                            cartCountEl.style.display = 'none';
                        }
                    }
                    
                    // Update navbar total
                    const cartTotalEl = document.querySelector('.cart-total');
                    if (cartTotalEl && data.total !== undefined) {
                        cartTotalEl.textContent = '₹' + data.total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                    
                    setTimeout(() => {
                        icon.style.color = originalColor;
                    }, 500);
                } else {
                    showToast(data.message || 'Error adding to cart', 'error');
                    icon.style.color = originalColor;
                }
            })
            .catch(err => {
                console.error('Cart add error:', err);
                showToast('Error adding to cart', 'error');
                icon.style.color = originalColor;
            });
            return;
        }
        
        // Buy Now Button Logic (Direct Redirect for Reliability)
        const buyBtn = e.target.closest('.buy-now-btn');
        if (buyBtn) {
            e.preventDefault();
            e.stopPropagation();

            const productId = buyBtn.getAttribute('data-product-id');
            const icon = buyBtn.querySelector('i');
            
            // Show processing state
            icon.className = 'fa-solid fa-spinner fa-spin';

            // Direct redirect to checkout
            window.location.href = `checkout.php?source=direct_buy&product_id=${productId}&quantity=1`;
        }

        // WhatsApp Inquiry Logic
        const waBtn = e.target.closest('.whatsapp-inquiry-btn');
        if (waBtn) {
            e.preventDefault();
            e.stopPropagation();
            const url = waBtn.getAttribute('data-url');
            if(url) window.open(url, '_blank');
        }
    });
});
</script>
<style>
/* Large WhatsApp Button Style for Product Grid */
.large-wa-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #fff;
    color: #25D366;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    z-index: 5;
    transition: all 0.3s ease;
    cursor: pointer;
}
.large-wa-btn:hover {
    transform: scale(1.1);
    background: #25D366;
    color: #fff;
    box-shadow: 0 6px 15px rgba(37, 211, 102, 0.4);
}
</style>
</body>
</html>