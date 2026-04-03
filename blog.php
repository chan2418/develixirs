<?php
// blog.php - Display published blogs from database
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/blog_scope.php';

$scope = defined('BLOG_SCOPE_FORCE')
    ? site_blog_scope_normalize((string)BLOG_SCOPE_FORCE)
    : site_blog_scope_from_request();
$isAyurvedhScope = site_blog_scope_is_ayurvedh($scope);
$blogListPath = site_blog_scope_list_path($scope);
$blogTypeColumnAvailable = site_blog_scope_type_column_exists($pdo);
$heroTitle = $isAyurvedhScope ? 'Ayurvedh Blog' : 'Beauty & Wellness Blog';
$heroSubtitle = $isAyurvedhScope
    ? 'Authentic Ayurvedh insights, herbal routines, and traditional wellness guidance.'
    : 'Discover skincare guides, wellness stories, and natural beauty routines from DevElixir.';
$seoTitle = $isAyurvedhScope
    ? 'Ayurvedh Blog - DevElixir Natural Cosmetics'
    : 'Beauty & Wellness Blog - DevElixir Natural Cosmetics';
$seoDescription = $isAyurvedhScope
    ? 'Explore Ayurvedh-focused articles on herbal remedies, dosha-based beauty rituals, and holistic wellness from DevElixir.'
    : 'Discover ayurvedic beauty tips, natural skincare routines, herbal wellness guides, and more. Expert advice on natural cosmetics and holistic beauty from DevElixir.';
$seoKeywords = $isAyurvedhScope
    ? 'ayurvedha blog, ayurvedic lifestyle tips, herbal remedies blog, dosha skincare guide, develixir ayurvedha'
    : 'ayurvedic beauty blog, natural skincare tips, herbal wellness, beauty tips india, organic cosmetics guide, DevElixir blog';
$seoUrl = 'https://develixirs.com/' . $blogListPath;

// Fetch published blog posts
$blogs = [];
$recentBlogs = [];
$categories = []; // Define categories array
$mainCategories = [];
$subcategoriesByParent = [];
$selectedMainCategoryId = null;
$dbError = null;
$categoryId = isset($_GET['cat']) ? (int)$_GET['cat'] : null;
$tagSlug = isset($_GET['tag']) ? $_GET['tag'] : null; // Get tag slug from URL
$categoryScopeColumnAvailable = site_blog_scope_category_scope_column_exists($pdo);
$categoryParentColumnAvailable = site_blog_scope_category_parent_column_exists($pdo);
$supportsSubcategories = site_blog_scope_supports_subcategories($scope) && $categoryParentColumnAvailable;

// Helper to truncate text
function truncate($text, $length = 150) {
    $text = strip_tags($text);
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

// Helper for escaping
function e($v) {
    return htmlspecialchars($v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
}

function render_blog_grid_items_html(array $blogs, ?string $dbError, bool $isAyurvedhScope): string
{
    ob_start();
    if (empty($blogs)): ?>
      <div class="empty-state">
        <i class="fa-regular fa-newspaper"></i>
        <h3>No stories found</h3>
        <p>We haven't published any articles in this category yet.</p>
        <?php if (!empty($dbError)): ?>
          <div style="margin-top:20px; padding:10px; border:1px solid red; color:red; font-family:monospace; font-size:12px; text-align:left; display:inline-block;">
            <strong>Debug Error:</strong> <?= e($dbError) ?>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <?php foreach ($blogs as $p): ?>
        <?php
          $postUrl = site_blog_scope_append_query('blog_single.php', [
            'slug' => $p['slug'] ?? '',
            'scope' => $isAyurvedhScope ? 'ayurvedh' : ''
          ]);
        ?>
        <article class="post-card">
          <a href="<?= e($postUrl) ?>" class="post-thumb">
            <img src="<?= e($p['featured_image'] ?: '/assets/images/blog-default.jpg') ?>" alt="<?= e($p['title']) ?>">
            <?php if (!empty($p['category_name'])): ?>
              <span class="post-category-badge"><?= e($p['category_name']) ?></span>
            <?php endif; ?>
          </a>

          <div class="post-body">
            <div class="post-meta">
              <?php if (!empty($p['author_name'])): ?>
                <div class="author-info" style="display:flex; align-items:center; gap:8px; margin-right:15px;">
                  <?php if(!empty($p['author_pic'])): ?>
                    <img src="<?= e($p['author_pic']) ?>" alt="<?= e($p['author_name']) ?>" style="width:24px; height:24px; border-radius:50%; object-fit:cover;">
                  <?php else: ?>
                    <i class="fa-solid fa-circle-user" style="font-size:24px; color:#ccc;"></i>
                  <?php endif; ?>
                  <span><?= e($p['author_name']) ?></span>
                </div>
              <?php endif; ?>

              <span style="display:flex; align-items:center; gap:6px;">
                <i class="fa-regular fa-calendar"></i>
                <?= e(date('F j, Y', strtotime($p['created_at']))) ?>
              </span>
            </div>

            <h2 class="post-title">
              <a href="<?= e($postUrl) ?>"><?= e($p['title']) ?></a>
            </h2>

            <p class="post-excerpt"><?= e(truncate($p['content'], 140)) ?></p>

            <a href="<?= e($postUrl) ?>" class="post-readmore">
              Read Story <i class="fa-solid fa-arrow-right-long"></i>
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif;

    return (string)ob_get_clean();
}

try {
    $selectedCategory = null;
    if ($categoryId && $supportsSubcategories) {
        $selectedCategorySql = "SELECT id, parent_id FROM blog_categories WHERE id = :cat_id";
        $selectedCategoryParams = [':cat_id' => $categoryId];
        if ($categoryScopeColumnAvailable) {
            [$selectedCatScopeClause, $selectedCatScopeParams] = site_blog_scope_taxonomy_filter_clause($scope, 'blog_scope', ':selected_cat_scope');
            $selectedCategorySql .= " AND {$selectedCatScopeClause}";
            $selectedCategoryParams = array_merge($selectedCategoryParams, $selectedCatScopeParams);
        }
        $selectedCategorySql .= " LIMIT 1";
        $stmtSelectedCategory = $pdo->prepare($selectedCategorySql);
        $stmtSelectedCategory->execute($selectedCategoryParams);
        $selectedCategory = $stmtSelectedCategory->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Build query
    $sql = "SELECT DISTINCT b.id, b.title, b.slug, b.content, b.featured_image, b.created_at, 
                   c.title as category_name,
                   a.name as author_name, a.profile_pic as author_pic
            FROM blogs b
            LEFT JOIN blog_categories c ON b.blog_category_id = c.id
            LEFT JOIN authors a ON b.author_id = a.id";

    // Add joins for tag filtering if tag is present
    if ($tagSlug) {
        $sql .= " INNER JOIN blog_post_tags bpt ON b.id = bpt.blog_id
                  INNER JOIN blog_tags bt ON bpt.tag_id = bt.id";
    }

    $sql .= " WHERE b.is_published = 1 AND (b.published_at IS NULL OR b.published_at <= :current_time)";
    
    $params = [':current_time' => date('Y-m-d H:i:s')];

    if ($blogTypeColumnAvailable) {
        [$scopeClause, $scopeParams] = site_blog_scope_filter_clause($scope, 'b.blog_type');
        $sql .= " AND {$scopeClause}";
        $params = array_merge($params, $scopeParams);
    } elseif ($isAyurvedhScope) {
        // If DB doesn't have scope column yet, keep Ayurvedha page isolated.
        $sql .= " AND 1=0";
    }

    if ($categoryId) {
        $isMainCategorySelection = $supportsSubcategories
            && $selectedCategory
            && (int)($selectedCategory['parent_id'] ?? 0) === 0;

        if ($isMainCategorySelection) {
            // Selecting a main Ayurvedh category shows posts from the main + its children.
            $sql .= " AND (b.blog_category_id = :cat_id OR b.blog_category_id IN (SELECT id FROM blog_categories WHERE parent_id = :cat_parent_id))";
            $selectedMainCategoryId = (int)$selectedCategory['id'];
            $params[':cat_parent_id'] = $categoryId;
        } else {
            $sql .= " AND b.blog_category_id = :cat_id";
            if ($supportsSubcategories && $selectedCategory) {
                $parentId = (int)($selectedCategory['parent_id'] ?? 0);
                $selectedMainCategoryId = $parentId > 0 ? $parentId : (int)$selectedCategory['id'];
            }
        }

        $params[':cat_id'] = $categoryId;
    }
    
    if ($tagSlug) {
        $sql .= " AND bt.slug = :tag_slug";
        $params[':tag_slug'] = $tagSlug;
    }

    $sql .= " ORDER BY b.created_at DESC LIMIT 9";

    // Fetch blogs
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch 3 recent blogs for sidebar
    $recentSql = "
        SELECT id, title, slug, featured_image, created_at 
        FROM blogs 
        WHERE is_published = 1 AND (published_at IS NULL OR published_at <= :current_time)
    ";
    $recentParams = [':current_time' => date('Y-m-d H:i:s')];
    if ($blogTypeColumnAvailable) {
        [$recentScopeClause, $recentScopeParams] = site_blog_scope_filter_clause($scope, 'blog_type');
        $recentSql .= " AND {$recentScopeClause}";
        $recentParams = array_merge($recentParams, $recentScopeParams);
    } elseif ($isAyurvedhScope) {
        $recentSql .= " AND 1=0";
    }
    $recentSql .= " ORDER BY created_at DESC LIMIT 3";
    $stmtRecent = $pdo->prepare($recentSql);
    $stmtRecent->execute($recentParams);
    $recentBlogs = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Categories for Sidebar (scope-aware, with main/subcategory support for Ayurvedh).
    $allCategories = [];
    $catSql = "SELECT id, title";
    if ($categoryParentColumnAvailable) {
        $catSql .= ", parent_id";
    }
    $catSql .= " FROM blog_categories";

    $catWhere = [];
    $catParams = [];
    if ($categoryScopeColumnAvailable) {
        [$catScopeClause, $catScopeParams] = site_blog_scope_taxonomy_filter_clause($scope, 'blog_scope', ':cat_scope_type');
        $catWhere[] = $catScopeClause;
        $catParams = array_merge($catParams, $catScopeParams);
    }
    if (!$supportsSubcategories && $categoryParentColumnAvailable) {
        $catWhere[] = "(parent_id IS NULL OR parent_id = 0)";
    }
    if (!empty($catWhere)) {
        $catSql .= " WHERE " . implode(' AND ', $catWhere);
    }
    if ($categoryParentColumnAvailable) {
        $catSql .= " ORDER BY COALESCE(parent_id, id), (parent_id IS NOT NULL), title ASC";
    } else {
        $catSql .= " ORDER BY title ASC";
    }

    $stmtCats = $pdo->prepare($catSql);
    $stmtCats->execute($catParams);
    $allCategories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

    if (!$blogTypeColumnAvailable && $isAyurvedhScope) {
        // Keep Ayurvedha page isolated when blog_type column is unavailable.
        $categories = [];
    } elseif (!$blogTypeColumnAvailable && !$isAyurvedhScope) {
        // Legacy fallback behavior for regular blog.
        $categories = $allCategories;
    } else {
        // Determine which categories actually have published posts in this scope.
        $usedSql = "
            SELECT DISTINCT b.blog_category_id AS category_id
            FROM blogs b
            WHERE b.blog_category_id IS NOT NULL
              AND b.is_published = 1
              AND (b.published_at IS NULL OR b.published_at <= :current_time)
        ";
        $usedParams = [':current_time' => date('Y-m-d H:i:s')];
        if ($blogTypeColumnAvailable) {
            [$usedScopeClause, $usedScopeParams] = site_blog_scope_filter_clause($scope, 'b.blog_type');
            $usedSql .= " AND {$usedScopeClause}";
            $usedParams = array_merge($usedParams, $usedScopeParams);
        }

        $stmtUsedCats = $pdo->prepare($usedSql);
        $stmtUsedCats->execute($usedParams);
        $usedRows = $stmtUsedCats->fetchAll(PDO::FETCH_ASSOC);
        $usedCategoryIdMap = [];
        foreach ($usedRows as $usedRow) {
            $usedId = isset($usedRow['category_id']) ? (int)$usedRow['category_id'] : 0;
            if ($usedId > 0) {
                $usedCategoryIdMap[$usedId] = true;
            }
        }

        if ($supportsSubcategories) {
            $allCategoriesById = [];
            foreach ($allCategories as $catRow) {
                $allCategoriesById[(int)$catRow['id']] = $catRow;
            }

            $visibleCategoryMap = [];
            foreach (array_keys($usedCategoryIdMap) as $usedCategoryId) {
                if (!isset($allCategoriesById[$usedCategoryId])) {
                    continue;
                }
                $visibleCategoryMap[$usedCategoryId] = true;
                $parentId = isset($allCategoriesById[$usedCategoryId]['parent_id']) ? (int)$allCategoriesById[$usedCategoryId]['parent_id'] : 0;
                if ($parentId > 0) {
                    $visibleCategoryMap[$parentId] = true;
                }
            }

            if ($selectedMainCategoryId) {
                $visibleCategoryMap[$selectedMainCategoryId] = true;
                foreach ($allCategories as $catRow) {
                    $parentId = isset($catRow['parent_id']) ? (int)$catRow['parent_id'] : 0;
                    if ($parentId === (int)$selectedMainCategoryId) {
                        $visibleCategoryMap[(int)$catRow['id']] = true;
                    }
                }
            }

            foreach ($allCategories as $catRow) {
                if (isset($visibleCategoryMap[(int)$catRow['id']])) {
                    $categories[] = $catRow;
                }
            }
        } else {
            foreach ($allCategories as $catRow) {
                if (isset($usedCategoryIdMap[(int)$catRow['id']])) {
                    $categories[] = $catRow;
                }
            }
        }
    }

    if ($supportsSubcategories) {
        foreach ($categories as $catRow) {
            $parentId = isset($catRow['parent_id']) ? (int)$catRow['parent_id'] : 0;
            if ($parentId > 0) {
                if (!isset($subcategoriesByParent[$parentId])) {
                    $subcategoriesByParent[$parentId] = [];
                }
                $subcategoriesByParent[$parentId][] = $catRow;
            } else {
                $mainCategories[] = $catRow;
            }
        }
    } else {
        $mainCategories = $categories;
    }
    
} catch (PDOException $e) {
    $dbError = $e->getMessage(); // Capture error
    error_log('Blog fetch error: ' . $dbError);
    $blogs = [];
    $recentBlogs = [];
    $categories = [];
    $mainCategories = [];
    $subcategoriesByParent = [];
}

if (isset($_GET['ajax']) && (string)$_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'html' => render_blog_grid_items_html($blogs, $dbError, $isAyurvedhScope),
        'count' => count($blogs),
        'categoryId' => $categoryId,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Fetch blog banners
$blogBanners = [];
try {
    $stmtBanner = $pdo->prepare("SELECT filename FROM banners WHERE page_slot = 'blog' AND is_active = 1 ORDER BY id DESC");
    $stmtBanner->execute();
    $bannerRows = $stmtBanner->fetchAll(PDO::FETCH_ASSOC);
    foreach ($bannerRows as $row) {
        if (!empty($row['filename'])) {
            $blogBanners[] = '/assets/uploads/banners/' . ltrim($row['filename'], '/');
        }
    }
} catch (PDOException $e) {
    error_log('Banner fetch error: ' . $e->getMessage());
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
    'title' => $seoTitle,
    'description' => $seoDescription,
    'keywords' => $seoKeywords,
    'url' => $seoUrl,
    'type' => 'website'
]);

// Add Website Schema
echo generate_website_schema();
?>
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  
  <style>
    :root{
      --primary: #D4AF37;
      --primary-dark: #B89026;
      --text: #1a1a1a;
      --text-light: #666;
      --bg-light: #f5f5f5;
      --border: #e0e0e0;
      --font-heading: 'Playfair Display', serif;
      --font-body: 'Poppins', sans-serif;
    }
    
    *{margin:0;padding:0;box-sizing:border-box;}
    body{
      font-family:var(--font-body); 
      color:var(--text); 
      background:#fff; 
      line-height:1.7;
    }
    img{max-width:100%; display:block;}
    a{text-decoration:none; color:inherit; transition:0.3s;}
    ul{list-style:none;}
    
    /* HERO SECTION */
    .hero{
      background: #1a1a1a;
      color: #fff;
      padding: 80px 20px;
      text-align: center;
      margin-bottom: 60px;
      margin-top: 120px;
      position: relative;
      overflow: hidden;
      min-height: 400px;
    }
    
    /* Banner Carousel */
    .banner-carousel {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
    }
    .banner-slide {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      opacity: 0;
      transition: opacity 1s ease-in-out;
      background-size: cover;
      background-position: center;
    }
    .banner-slide.active {
      opacity: 1;
    }
    
    .hero.has-banner {
      background: #000;
    }
    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,186.7C384,213,480,235,576,213.3C672,192,768,128,864,128C960,128,1056,192,1152,197.3C1248,203,1344,149,1392,122.7L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
      opacity: 0.3;
    }
    .hero-inner{
      max-width: 800px;
      margin: 0 auto;
      padding: 0 20px;
      position: relative;
      z-index: 1;
    }
    .hero-title{
      font-family: var(--font-heading);
      font-size: 54px;
      font-weight: 700;
      margin-bottom: 15px;
      letter-spacing: 1px;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }
    .hero-subtitle {
      font-size: 18px;
      opacity: 0.95;
      margin-bottom: 20px;
      font-weight: 300;
    }
    .breadcrumb{
      font-size: 13px;
      opacity: 0.9;
      text-transform: uppercase;
      letter-spacing: 2px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255,255,255,0.2);
      padding: 8px 20px;
      border-radius: 30px;
    }
    .breadcrumb a:hover{ opacity: 0.8; }
    
    /* LAYOUT */
    .layout{
      max-width: 1300px;
      margin: 0 auto 80px;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 60px;
    }
    
    /* BLOG GRID */
    .blog-grid{
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 35px;
    }
    .blog-grid.is-loading{
      opacity: 0.55;
      pointer-events: none;
      transition: opacity 0.2s ease;
    }
    
    /* POST CARD */
    .post-card{
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
      height: 100%;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      border: 1px solid var(--border);
    }
    .post-card:hover{
      transform: translateY(-8px);
      box-shadow: 0 12px 24px rgba(212, 175, 55, 0.15);
      border-color: var(--primary);
    }
    
    .post-thumb{
      position: relative;
      overflow: hidden;
      padding-top: 60%; /* Aspect Ratio */
      background: #f0f0f0;
    }
    .post-thumb img{
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    .post-card:hover .post-thumb img{
      transform: scale(1.08);
    }
    
    .post-category-badge{
      position: absolute;
      top: 15px;
      left: 15px;
      background: var(--primary);
      color: #fff;
      padding: 6px 14px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      border-radius: 4px;
      z-index: 2;
    }
    
    .post-body{
      padding: 24px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    
    .post-meta{
      font-size: 12px;
      color: var(--primary);
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .post-meta i {
      font-size: 11px;
    }
    
    .post-title{
      font-family: var(--font-heading);
      font-size: 22px;
      font-weight: 700;
      line-height: 1.3;
      margin-bottom: 12px;
      color: var(--text);
    }
    .post-title:hover{
      color: var(--primary);
    }
    
    .post-excerpt{
      font-size: 14px;
      color: var(--text-light);
      margin-bottom: 18px;
      flex: 1;
      line-height: 1.6;
    }
    
    .post-readmore{
      font-size: 13px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: var(--primary);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: auto;
      width: fit-content;
      padding: 10px 0;
    }
    .post-readmore i {
      transition: transform 0.3s;
    }
    .post-readmore:hover{
      color: var(--primary-dark);
    }
    .post-readmore:hover i {
      transform: translateX(5px);
    }
    
    /* SIDEBAR */
    aside{
      position: sticky;
      top: 160px;
      height: fit-content;
    }
    
    .sidebar-widget{
      background: #fff;
      padding: 25px;
      margin-bottom: 30px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      border: 1px solid var(--border);
    }
    
    .widget-title{
      font-family: var(--font-heading);
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 20px;
      color: var(--text);
      position: relative;
      padding-bottom: 12px;
    }
    .widget-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 50px;
      height: 3px;
      background: var(--primary);
    }
    
    .recent-post-item{
      display: flex;
      gap: 12px;
      margin-bottom: 18px;
      padding-bottom: 18px;
      border-bottom: 1px solid var(--border);
    }
    .recent-post-item:last-child {
      margin-bottom: 0;
      padding-bottom: 0;
      border-bottom: none;
    }
    .recent-post-item img{
      width: 70px;
      height: 70px;
      object-fit: cover;
      border-radius: 8px;
      flex-shrink: 0;
    }
    .recent-post-info h4{
      font-family: var(--font-heading);
      font-size: 14px;
      line-height: 1.4;
      margin-bottom: 6px;
      font-weight: 600;
    }
    .recent-post-info h4:hover{ color: var(--primary); }
    .recent-post-date{
      font-size: 11px;
      color: #999;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    
    .category-list li{
      margin-bottom: 0;
    }
    .category-list a{
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 14px;
      color: var(--text-light);
      padding: 12px 0;
      border-bottom: 1px solid var(--border);
      transition: all 0.2s;
    }
    .category-list a:hover, .category-list a.active{
      color: var(--primary);
      padding-left: 8px;
    }
    .category-list a i {
      font-size: 12px;
      transition: transform 0.2s;
    }
    .category-list a:hover i {
      transform: translateX(3px);
    }
    .category-item{
      border-bottom: 1px solid var(--border);
    }
    .category-item:last-child{
      border-bottom: 0;
    }
    .category-main-row{
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .category-main-row > a{
      flex: 1;
      border-bottom: 0;
      padding: 12px 0;
    }
    .category-toggle-btn{
      border: 0;
      background: transparent;
      color: var(--text-light);
      cursor: pointer;
      padding: 8px 4px;
      transition: color 0.2s;
    }
    .category-toggle-btn:hover{
      color: var(--primary);
    }
    .category-toggle-btn i{
      transition: transform 0.2s;
    }
    .category-item.expanded .category-toggle-btn i{
      transform: rotate(90deg);
    }
    .subcategory-list{
      list-style: none;
      margin: 0;
      padding: 0 0 8px 12px;
      display: none;
    }
    .category-item.expanded .subcategory-list{
      display: block;
    }
    .subcategory-list a{
      border-bottom: 0;
      padding: 8px 0;
      font-size: 13px;
    }
    
    /* EMPTY STATE */
    .empty-state{
      grid-column: 1/-1;
      text-align: center;
      padding: 100px 20px;
      background: var(--bg-light);
      border-radius: 12px;
    }
    .empty-state i{
      font-size: 64px;
      margin-bottom: 20px;
      color: #ddd;
    }
    .empty-state h3 {
      font-size: 24px;
      margin-bottom: 10px;
      color: var(--text);
    }
    .empty-state p {
      color: var(--text-light);
    }
    
    /* RESPONSIVE */
    @media(max-width: 1024px){
      .layout{ grid-template-columns: 1fr; gap: 50px; }
      aside { position: static; }
      .sidebar-widget{ max-width: 500px; margin-left: auto; margin-right: auto; }
    }
    @media(max-width: 768px){
      .blog-grid{ grid-template-columns: 1fr; }
      .hero{ padding: 50px 20px; margin-top: 70px; }
      .hero-title{ font-size: 36px; }
      .hero-subtitle { font-size: 16px; }
      .post-title { font-size: 20px; }
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- HERO -->
  <section class="hero <?php echo !empty($blogBanners) ? 'has-banner' : ''; ?>">
    <?php if (!empty($blogBanners)): ?>
      <div class="banner-carousel">
        <?php foreach ($blogBanners as $index => $banner): ?>
          <div class="banner-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
               style="background-image: url('<?php echo htmlspecialchars($banner); ?>');">
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="hero-inner">
      <h1 class="hero-title"><?php echo htmlspecialchars($heroTitle); ?></h1>
      <p class="hero-subtitle"><?php echo htmlspecialchars($heroSubtitle); ?></p>
      <div class="breadcrumb">
        <a href="index.php">Home</a>
        <i class="fa-solid fa-angle-right"></i>
        <span><?php echo htmlspecialchars($heroTitle); ?></span>
      </div>
    </div>
  </section>

  <!-- MAIN LAYOUT -->
  <main class="layout">

    <!-- BLOG CONTENT -->
    <section>
      <div class="blog-grid" id="blogGrid">
        <?= render_blog_grid_items_html($blogs, $dbError, $isAyurvedhScope) ?>
      </div>
    </section>

    <!-- SIDEBAR -->
    <aside>
      
      <!-- Categories -->
      <div class="sidebar-widget" id="blogCategoriesWidget">
        <h3 class="widget-title">Categories</h3>
        <ul class="category-list">
          <li>
            <a href="<?= e($blogListPath) ?>" class="<?= !$categoryId ? 'active' : '' ?>" data-category-filter-link="1">
              All Stories <span><i class="fa-solid fa-angle-right"></i></span>
            </a>
          </li>
          <?php if (!empty($mainCategories)): ?>
            <?php if ($supportsSubcategories): ?>
              <?php foreach ($mainCategories as $mainCategory): ?>
                <?php
                  $mainCategoryId = (int)$mainCategory['id'];
                  $subcategories = $subcategoriesByParent[$mainCategoryId] ?? [];
                  $hasSubcategories = !empty($subcategories);
                  $isMainSelected = ((int)$categoryId === $mainCategoryId);
                  $isSubSelected = false;
                  foreach ($subcategories as $subCategory) {
                      if ((int)$categoryId === (int)$subCategory['id']) {
                          $isSubSelected = true;
                          break;
                      }
                  }
                  $isExpanded = $isMainSelected || $isSubSelected;
                ?>
                <li class="category-item <?= $isExpanded ? 'expanded' : '' ?>">
                  <div class="category-main-row">
                    <a href="<?= e(site_blog_scope_append_query($blogListPath, ['cat' => $mainCategoryId])) ?>" class="<?= $isMainSelected ? 'active' : '' ?>" data-category-filter-link="1">
                      <?= htmlspecialchars($mainCategory['title'] ?? '') ?>
                      <?php if (!$hasSubcategories): ?>
                        <span><i class="fa-solid fa-angle-right"></i></span>
                      <?php endif; ?>
                    </a>
                    <?php if ($hasSubcategories): ?>
                      <button
                        type="button"
                        class="category-toggle-btn"
                        data-main-category="<?= $mainCategoryId ?>"
                        aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>"
                        aria-controls="subcategory-list-<?= $mainCategoryId ?>"
                      >
                        <i class="fa-solid fa-angle-right"></i>
                      </button>
                    <?php endif; ?>
                  </div>

                  <?php if (!empty($subcategories)): ?>
                    <ul class="subcategory-list" id="subcategory-list-<?= $mainCategoryId ?>">
                      <?php foreach ($subcategories as $subCategory): ?>
                        <?php $subCategoryId = (int)$subCategory['id']; ?>
                        <li>
                          <a href="<?= e(site_blog_scope_append_query($blogListPath, ['cat' => $subCategoryId])) ?>" class="<?= ((int)$categoryId === $subCategoryId) ? 'active' : '' ?>" data-category-filter-link="1">
                            ↳ <?= htmlspecialchars($subCategory['title'] ?? '') ?>
                            <span><i class="fa-solid fa-angle-right"></i></span>
                          </a>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <?php foreach ($mainCategories as $cat): ?>
                <li>
                  <a href="<?= e(site_blog_scope_append_query($blogListPath, ['cat' => (int)$cat['id']])) ?>" class="<?= ($categoryId == $cat['id']) ? 'active' : '' ?>" data-category-filter-link="1">
                    <?= htmlspecialchars($cat['title'] ?? $cat['name'] ?? '') ?>
                    <span><i class="fa-solid fa-angle-right"></i></span>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Recent Posts -->
      <div class="sidebar-widget">
        <h3 class="widget-title">Recent Stories</h3>
        <?php if (!empty($recentBlogs)): ?>
          <?php foreach ($recentBlogs as $r): ?>
            <?php
              $recentUrl = site_blog_scope_append_query('blog_single.php', [
                'slug' => $r['slug'] ?? '',
                'scope' => $isAyurvedhScope ? 'ayurvedh' : ''
              ]);
            ?>
            <div class="recent-post-item">
              <a href="<?= e($recentUrl) ?>">
                <img src="<?= e($r['featured_image'] ?: '/assets/images/blog-default.jpg') ?>" alt="">
              </a>
              <div class="recent-post-info">
                <h4><a href="<?= e($recentUrl) ?>"><?= e($r['title']) ?></a></h4>
                <div class="recent-post-date">
                  <i class="fa-regular fa-calendar"></i>
                  <?= e(date('M j, Y', strtotime($r['created_at']))) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </aside>

  </main>

  <!-- FOOTER -->
  <!-- FOOTER -->
  <?php include 'footer.php'; ?>

  <script>
  // Banner carousel auto-scroll
  <?php if (!empty($blogBanners) && count($blogBanners) > 1): ?>
  (function() {
    const slides = document.querySelectorAll('.banner-slide');
    let currentSlide = 0;
    
    function nextSlide() {
      slides[currentSlide].classList.remove('active');
      currentSlide = (currentSlide + 1) % slides.length;
      slides[currentSlide].classList.add('active');
    }
    
    // Change slide every 5 seconds
    setInterval(nextSlide, 5000);
  })();
  <?php endif; ?>

  (function() {
    const toggleButtons = document.querySelectorAll('.category-toggle-btn');
    if (!toggleButtons.length) {
      return;
    }

    toggleButtons.forEach((button) => {
      button.addEventListener('click', function(event) {
        event.preventDefault();
        const categoryItem = this.closest('.category-item');
        if (!categoryItem) {
          return;
        }
        const expanded = categoryItem.classList.toggle('expanded');
        this.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      });
    });
  })();

  (function() {
    const gridEl = document.getElementById('blogGrid');
    const categoryWidgetEl = document.getElementById('blogCategoriesWidget');
    if (!gridEl || !categoryWidgetEl) {
      return;
    }

    const getCatParam = (urlValue) => {
      const url = new URL(urlValue, window.location.origin);
      return url.searchParams.get('cat') || '';
    };

    const syncActiveCategoryLink = (urlValue) => {
      const activeCat = getCatParam(urlValue);
      const filterLinks = categoryWidgetEl.querySelectorAll('a[data-category-filter-link="1"]');

      filterLinks.forEach((linkEl) => linkEl.classList.remove('active'));

      let matchedLink = null;
      for (const linkEl of filterLinks) {
        if (getCatParam(linkEl.href) === activeCat) {
          matchedLink = linkEl;
          break;
        }
      }
      if (!matchedLink && activeCat === '' && filterLinks.length) {
        matchedLink = filterLinks[0];
      }
      if (matchedLink) {
        matchedLink.classList.add('active');
        const categoryItem = matchedLink.closest('.category-item');
        if (categoryItem) {
          categoryItem.classList.add('expanded');
          const toggleBtn = categoryItem.querySelector('.category-toggle-btn');
          if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', 'true');
          }
        }
      }
    };

    let activeController = null;
    const loadPostsWithAjax = async (targetUrl, pushHistory = true) => {
      const ajaxUrl = new URL(targetUrl, window.location.origin);
      ajaxUrl.searchParams.set('ajax', '1');

      if (activeController) {
        activeController.abort();
      }
      activeController = new AbortController();

      gridEl.classList.add('is-loading');

      try {
        const response = await fetch(ajaxUrl.toString(), {
          method: 'GET',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          signal: activeController.signal
        });

        if (!response.ok) {
          throw new Error('Failed to load filtered posts');
        }

        const payload = await response.json();
        if (!payload || typeof payload.html !== 'string') {
          throw new Error('Invalid AJAX payload');
        }

        gridEl.innerHTML = payload.html;
        if (pushHistory) {
          window.history.pushState({ blogAjax: true }, '', targetUrl);
        }
      } catch (error) {
        if (error.name === 'AbortError') {
          return;
        }
        window.location.href = targetUrl;
      } finally {
        gridEl.classList.remove('is-loading');
      }
    };

    categoryWidgetEl.addEventListener('click', (event) => {
      const linkEl = event.target.closest('a[data-category-filter-link="1"]');
      if (!linkEl || !categoryWidgetEl.contains(linkEl)) {
        return;
      }

      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }

      event.preventDefault();
      const targetUrl = linkEl.href;
      syncActiveCategoryLink(targetUrl);
      loadPostsWithAjax(targetUrl, true);
    });

    window.addEventListener('popstate', () => {
      const targetUrl = window.location.href;
      syncActiveCategoryLink(targetUrl);
      loadPostsWithAjax(targetUrl, false);
    });
  })();
  </script>

</body>
</html>
