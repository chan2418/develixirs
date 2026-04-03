<?php
// blog_single.php - Modern blog design inspired by Mamaearth
// Updated: 2025-12-10 10:00 AM - TOC in Right Sidebar
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

$slug = $_GET['slug'] ?? '';
$post = null;
$recentBlogs = [];
$relatedPosts = [];
$popularTags = [];  // Initialize to ensure it always exists
$postTags = [];     // Initialize post-specific tags too
$categories = [];   // Add this too

// PREVIEW LOGIC
if (!empty($_GET['preview_token']) && !empty($_SESSION['previews'][$_GET['preview_token']])) {
    $pData = $_SESSION['previews'][$_GET['preview_token']];
    if (($pData['preview_type'] ?? '') === 'blog') {
        $post = [
            'id' => 0,
            'title' => $pData['title'] ?? 'Preview Post',
            'slug' => 'preview',
            'content' => $pData['content'] ?? '',
            'featured_image' => !empty($pData['featured_image_url']) ? $pData['featured_image_url'] : ($pData['image_paths'][0] ?? ''),
            'meta_title' => $pData['meta_title'] ?? '',
            'meta_description' => $pData['meta_description'] ?? '',
            'author_name' => $_SESSION['admin_name'] ?? 'Admin',
            'author_pic' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'cat_id' => $pData['category_id'] ?? 0,
            'category_name' => 'Preview Category'
        ];
        // Handle Featured Image Upload (if any) vs URL
        if (!empty($pData['image_paths'][0])) {
            $post['featured_image'] = $pData['image_paths'][0];
        }
    }
}

if ($slug && !$post) { // Only fetch if not already set by preview
    try {
        $scopeWhere = '';
        $scopeParams = [];
        if ($blogTypeColumnAvailable) {
            [$scopeClause, $scopeParams] = site_blog_scope_filter_clause($scope, 'b.blog_type');
            $scopeWhere = " AND {$scopeClause}";
        } elseif ($isAyurvedhScope) {
            $scopeWhere = " AND 1=0";
        }

        // Fetch single post
        $stmt = $pdo->prepare("
            SELECT b.*, c.id as cat_id, c.title as category_name,
                   a.name as author_name, a.profile_pic as author_pic
            FROM blogs b
            LEFT JOIN blog_categories c ON b.blog_category_id = c.id
            LEFT JOIN authors a ON b.author_id = a.id
            WHERE b.slug = :slug AND b.is_published = 1 AND (b.published_at IS NULL OR b.published_at <= :current_time)
            {$scopeWhere}
            LIMIT 1
        ");
        $singleParams = array_merge([
            ':slug' => $slug,
            ':current_time' => date('Y-m-d H:i:s')
        ], $scopeParams);
        $stmt->execute($singleParams);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post) {
            // Fetch related posts - First try manually selected, then category-based
            $relatedPosts = [];
            
            // Try manually selected related articles first
            try {
                $stmtManual = $pdo->prepare("
                    SELECT b.id, b.title, b.slug, b.featured_image, b.created_at 
                    FROM blogs b
                    INNER JOIN blog_related br ON b.id = br.related_blog_id
                    WHERE br.blog_id = :blog_id 
                      AND b.is_published = 1 
                      AND (b.published_at IS NULL OR b.published_at <= :current_time)
                      {$scopeWhere}
                    ORDER BY br.created_at DESC
                    LIMIT 3
                ");
                $manualParams = array_merge([
                    ':blog_id' => $post['id'],
                    ':current_time' => date('Y-m-d H:i:s')
                ], $scopeParams);
                $stmtManual->execute($manualParams);
                $relatedPosts = $stmtManual->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // blog_related table might not exist yet, ignore
            }
            
            // If less than 3 manual selections, fill with category-based articles
            if (count($relatedPosts) < 3 && !empty($post['cat_id'])) {
                $excludeIds = array_merge([$post['id']], array_column($relatedPosts, 'id'));
                $placeholders = str_repeat('?,', count($excludeIds) - 1) . '?';
                $categoryScopeSql = '';
                $categoryScopeParams = [];
                if ($blogTypeColumnAvailable) {
                    if ($isAyurvedhScope) {
                        $categoryScopeSql = " AND LOWER(COALESCE(blog_type, '')) = ?";
                        $categoryScopeParams[] = 'ayurvedh';
                    } else {
                        $categoryScopeSql = " AND (blog_type IS NULL OR blog_type = '' OR LOWER(blog_type) = 'blog')";
                    }
                } elseif ($isAyurvedhScope) {
                    $categoryScopeSql = " AND 1=0";
                }
                
                $stmtCategory = $pdo->prepare("
                    SELECT id, title, slug, featured_image, created_at 
                    FROM blogs 
                    WHERE blog_category_id = ? 
                      AND id NOT IN ($placeholders)
                      AND is_published = 1 
                      AND (published_at IS NULL OR published_at <= ?)
                      {$categoryScopeSql}
                    ORDER BY created_at DESC 
                    LIMIT ?
                ");
                $params = array_merge(
                    [$post['cat_id']],
                    $excludeIds,
                    [date('Y-m-d H:i:s')],
                    $categoryScopeParams,
                    [3 - count($relatedPosts)]
                );
                $stmtCategory->execute($params);
                $categoryRelated = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);
                
                $relatedPosts = array_merge($relatedPosts, $categoryRelated);
            }
        }

        // Fetch recent posts for sidebar
        // Fetch recent posts for sidebar
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
        $recentSql .= " ORDER BY created_at DESC LIMIT 5";
        $stmtRecent = $pdo->prepare($recentSql);
        $stmtRecent->execute($recentParams);
        $recentBlogs = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Categories for Sidebar
        $stmtCats = $pdo->query("SELECT id, title FROM blog_categories ORDER BY title ASC");
        $categories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Popular Blog Tags for Sidebar (only show tags that have been used)
        try {
            $popularScopeSql = '';
            $popularParams = [':current_time' => date('Y-m-d H:i:s')];
            if ($blogTypeColumnAvailable) {
                [$popularScopeClause, $popularScopeParams] = site_blog_scope_filter_clause($scope, 'bs.blog_type');
                $popularScopeSql = " AND {$popularScopeClause}";
                $popularParams = array_merge($popularParams, $popularScopeParams);
            } elseif ($isAyurvedhScope) {
                $popularScopeSql = " AND 1=0";
            }

            $stmtTags = $pdo->prepare("
                SELECT bt.id, bt.name, bt.slug,
                       COUNT(
                         CASE
                           WHEN bs.is_published = 1
                            AND (bs.published_at IS NULL OR bs.published_at <= :current_time)
                            {$popularScopeSql}
                           THEN 1
                           ELSE NULL
                         END
                       ) as post_count
                FROM blog_tags bt
                LEFT JOIN blog_post_tags bpt ON bt.id = bpt.tag_id
                LEFT JOIN blogs bs ON bs.id = bpt.blog_id
                GROUP BY bt.id, bt.name, bt.slug
                ORDER BY post_count DESC, bt.name ASC
                LIMIT 15
            ");
            $stmtTags->execute($popularParams);
            $popularTags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $popularTags = [];
        }



        // Fetch Blog Tags for current post
        $postTags = [];
        try {
            $stmtPostTags = $pdo->prepare("
                SELECT bt.* FROM blog_tags bt
                INNER JOIN blog_post_tags bpt ON bt.id = bpt.tag_id
                WHERE bpt.blog_id = ?
                ORDER BY bt.name ASC
            ");
            $stmtPostTags->execute([$post['id']]);
            $postTags = $stmtPostTags->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { }

    } catch (PDOException $e) {
        error_log('Blog fetch error: ' . $e->getMessage());
    }
}

// Redirect if not found
if (!$post) {
    header('Location: ' . $blogListPath);
    exit;
}

// Helper for escaping
function e($v) { 
    return htmlspecialchars($v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); 
}

// Generate Table of Contents from content
function generateTOC($content) {
    if (empty($content)) return [];
    $toc = [];
    preg_match_all('/<(h[2-3])[^>]*>(.*?)<\/\1>/si', $content, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $index => $match) {
        $tagName = strtolower($match[1]);
        $title = strip_tags($match[2]);
        $title = html_entity_decode($title); 
        $title = trim($title);
        
        if (!$title) continue;
        
        $toc[] = [
            'tag' => $tagName,
            'title' => $title,
            'slug' => 'section-' . ($index + 1)
        ];
    }
    return $toc;
}

$tableOfContents = generateTOC($post['content']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= e($post['meta_title'] ?: $post['title']) ?> – Develixirs</title>
  <meta name="description" content="<?= e($post['meta_description'] ?: '') ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  
  <style>
    :root{
      --primary:#667eea;
      --primary-dark:#5568d3;
      --text:#1a1a1a;
      --text-light:#666;
      --border:#e5e7eb;
      --bg-light:#f9fafb;
      --shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    *{margin:0;padding:0;box-sizing:border-box;}
    body{
      font-family:'Poppins', sans-serif;
      color:var(--text);
      background:var(--bg-light);
      line-height:1.7;
      padding-top:80px;
    }
    
    /* BREADCRUMB */
    .breadcrumb-section {
      background: #fff;
      border-bottom: 1px solid var(--border);
      padding: 20px 0;
    }
    .breadcrumb {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      color: var(--text-light);
    }
    .breadcrumb a {
      color: var(--primary);
      text-decoration: none;
    }
    .breadcrumb a:hover {
      text-decoration: underline;
    }
    .breadcrumb i {
      font-size: 12px;
    }
    
    /* HERO/HEADER */
    .blog-hero {
      background: #fff;
      padding: 60px 0 40px;
    }
    .blog-hero-container {
      max-width: 800px;
      margin: 0 auto;
      padding: 0 20px;
      text-align: center;
    }
    .blog-category-badge {
      display: inline-block;
      background: var(--primary);
      color: #fff;
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 20px;
    }
    .blog-title {
      font-size: 42px;
      font-weight: 800;
      color: var(--text);
      margin-bottom: 20px;
      line-height: 1.2;
    }
    .blog-meta {
      font-size: 14px;
      color: var(--text-light);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 20px;
    }
    .blog-meta i {
      color: var(--primary);
      margin-right: 6px;
    }
    .blog-featured-image-container {
      max-width: 800px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .blog-featured-image {
      width: 100%;
      border-radius: 16px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    
    /* LAYOUT GRID */
    .content-layout {
      max-width: 1200px;
      margin: 40px auto 80px;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 50px;
      align-items: start;
    }
    
    /* MAIN CONTENT */
    .blog-content {
      background: #fff;
      border-radius: 12px;
      padding: 50px;
      box-shadow: var(--shadow);
    }
    .blog-content p {
      margin-bottom: 24px;
      font-size: 17px;
      line-height: 1.8;
      color: #374151;
    }
    .blog-content h2 {
      font-size: 32px;
      font-weight: 700;
      color: var(--text);
      margin-top: 48px;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--border);
    }
    .blog-content h3 {
      font-size: 24px;
      font-weight: 600;
      color: var(--text);
      margin-top: 36px;
      margin-bottom: 16px;
    }
    .blog-content ul, .blog-content ol {
      margin: 24px 0 24px 24px;
    }
    .blog-content li {
      margin-bottom: 12px;
      font-size: 17px;
      line-height: 1.8;
      color: #374151;
    }
    .blog-content img {
      max-width: 100%;
      border-radius: 12px;
      margin: 32px 0;
    }
    
    /* SIDEBAR */
    .blog-sidebar {
      position: sticky;
      top: 100px;
    }
    .sidebar-widget {
      background: #fff;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: var(--shadow);
    }
    .widget-title {
      font-size: 18px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--border);
    }
    
    /* SIDEBAR POPULAR TERMS / TAGS STYLE */
    .tags-container {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .tag-pill {
      font-size: 13px;
      color: #555;
      background: #fff;
      border: 1px solid #e0e0e0;
      padding: 6px 14px;
      border-radius: 4px;
      text-decoration: none;
      transition: all 0.2s;
    }
    .tag-pill:hover {
      border-color: var(--primary);
      color: var(--primary);
    }

    /* TOC LIST (Sidebar Style) */
    .toc-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .toc-item {
      margin-bottom: 0;
      border-bottom: 1px solid #f0f0f0;
    }
    .toc-item:last-child {
      border-bottom: none;
    }
    .toc-link {
        display: block;
        padding: 10px 0;
        color: #555;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.2s;
        line-height: 1.5;
    }
    .toc-link:hover {
      color: var(--primary);
      padding-left: 5px;
    }

    /* AUTHOR BOX */
    .author-box {
      margin-top: 50px;
      padding-top: 30px;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .author-img-wrapper {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      overflow: hidden;
      background: #eee;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .author-img-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      margin: 0; /* Override blog content img margin */
    }
    .author-img-wrapper i {
      font-size: 30px;
      color: #999;
    }
    .author-details {
      display: flex;
      flex-direction: column;
    }
    .author-label {
      font-size: 12px;
      text-transform: uppercase;
      color: var(--text-light);
      letter-spacing: 1px;
      margin-bottom: 4px;
    }
    .author-name {
      font-size: 18px;
      font-weight: 700;
      color: var(--text);
    }

    /* SIDEBAR CATEGORIES */
    .category-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .category-link {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
        color: #4b5563;
        font-size: 15px;
        text-decoration: none;
        transition: all 0.2s;
    }
    .category-link:last-child {
        border-bottom: none;
    }
    .category-link:hover {
        color: var(--primary);
        padding-left: 8px;
    }
    .category-link i {
        font-size: 12px;
        color: #d1d5db;
        transition: color 0.2s;
    }
    .category-link:hover i {
        color: var(--primary);
    }

    /* RELATED POSTS SECTION */
    .related-posts-section {
      max-width: 1200px;
      margin: 0 auto 80px;
      padding: 0 20px;
    }
    .section-title {
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 40px;
      text-align: center;
      position: relative;
    }
    .section-title::after {
        content: '';
        display: block;
        width: 60px;
        height: 3px;
        background: var(--primary);
        margin: 10px auto 0;
        border-radius: 2px;
    }
    .related-posts-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
    }
    .related-post-card {
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      border: 1px solid #eee;
      transition: all 0.3s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .related-post-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      border-color: transparent;
    }
    .related-post-image {
      width: 100%;
      height: 220px;
      object-fit: cover;
    }
    .related-post-content {
      padding: 24px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }
    .related-post-title {
      font-size: 18px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 12px;
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .related-post-meta {
      margin-top: auto;
      font-size: 13px;
      color: var(--text-light);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* RESPONSIVE */
    @media(max-width:1024px){
      .content-layout{grid-template-columns:1fr 300px; gap:30px;}
      .blog-content{padding:40px;}
      .related-posts-grid{grid-template-columns: repeat(2, 1fr);}
    }
    @media(max-width:768px){
      .content-layout{grid-template-columns:1fr;}
      .blog-sidebar{position:static;}
      .blog-title{font-size:28px;}
      .blog-content{padding:25px 20px;}
      .related-posts-grid{grid-template-columns:1fr;}
      .related-post-image{height: 200px;}
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- BREADCRUMB -->
  <section class="breadcrumb-section">
    <nav class="breadcrumb">
      <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
      <i class="fa-solid fa-chevron-right"></i>
      <a href="<?= e($blogListPath) ?>"><?= $isAyurvedhScope ? 'Ayurvedh Blog' : 'Blog' ?></a>
      <?php if (!empty($post['category_name'])): ?>
        <i class="fa-solid fa-chevron-right"></i>
        <span><?= e($post['category_name']) ?></span>
      <?php endif; ?>
    </nav>
  </section>

  <!-- BLOG HERO/HEADER -->
  <section class="blog-hero">
    <div class="blog-hero-container">
      <?php if (!empty($post['category_name'])): ?>
        <div class="blog-category-badge"><?= e($post['category_name']) ?></div>
      <?php endif; ?>
      
      <h1 class="blog-title"><?= e($post['title']) ?></h1>
      
      <div class="blog-meta">
        <span><i class="fa-regular fa-calendar"></i><?= e(date('F j, Y', strtotime($post['created_at']))) ?></span>
        <span><i class="fa-regular fa-clock"></i>5 min read</span>
      </div>
    </div>
    
    <?php if ($post['featured_image']): ?>
      <div class="blog-featured-image-container">
        <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" class="blog-featured-image">
      </div>
    <?php endif; ?>
  </section>

  <!-- CONTENT + SIDEBAR -->
  <div class="content-layout">
    
    <!-- MAIN CONTENT -->
    <article class="blog-content">
      <div class="blog-inner-content"><?= $post['content'] ?></div>
      
      <!-- Blog Tags -->
      <?php if (!empty($postTags)): ?>
        <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #e5e7eb;">
          <h4 style="font-size: 14px; font-weight: 600; color: #6b7280; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Tagged In:</h4>
          <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <?php foreach ($postTags as $tag): ?>
              <a href="<?= e(site_blog_scope_append_query($blogListPath, ['tag' => $tag['slug'] ?? '', 'scope' => $isAyurvedhScope ? 'ayurvedh' : ''])) ?>" 
                 style="display: inline-block; padding: 6px 14px; background: #f3f4f6; color: #374151; border-radius: 20px; font-size: 13px; text-decoration: none; transition: all 0.2s;"
                 onmouseover="this.style.background='#667eea'; this.style.color='white';"
                 onmouseout="this.style.background='#f3f4f6'; this.style.color='#374151';">
                # <?= htmlspecialchars($tag['name']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Author Box -->
      <?php if (!empty($post['author_name'])): ?>
      <div class="author-box">
        <div class="author-img-wrapper">
            <?php if(!empty($post['author_pic'])): ?>
              <img src="<?= e($post['author_pic']) ?>" alt="<?= e($post['author_name']) ?>">
            <?php else: ?>
              <i class="fa-solid fa-circle-user"></i>
            <?php endif; ?>
        </div>
        <div class="author-details">
            <span class="author-label">Written by</span>
            <h4 class="author-name"><?= e($post['author_name']) ?></h4>
            <!-- Optional bio if we had it in DB -->
        </div>
      </div>
      <?php endif; ?>

      <!-- Powered by -->
      <div class="powered-by">
        <i class="fa-solid fa-bolt"></i> Powered by <strong>Develixirs</strong>
      </div>
    </article>

    <!-- SIDEBAR (RIGHT) -->
    <aside class="blog-sidebar">
      
      <!-- Table of Contents Widget (TOP) -->
      <?php if (!empty($tableOfContents)): ?>
      <div class="sidebar-widget">
        <h3 class="widget-title">In this Article</h3>
        <ul class="toc-list">
          <?php foreach ($tableOfContents as $item): ?>
            <li class="toc-item" style="<?= $item['tag'] === 'h3' ? 'padding-left: 15px;' : '' ?>">
              <a href="#<?= e($item['slug']) ?>" class="toc-link">
                <?= e($item['title']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <!-- Categories Widget (Product Categories) -->
      <div class="sidebar-widget">
        <h3 class="widget-title">Product Categories</h3>
        <ul class="category-list">
            <?php foreach ($categories as $cat): ?>
            <li>
                <a href="product.php?cat=<?= $cat['id'] ?>" class="category-link">
                    <?= htmlspecialchars($cat['title']) ?>
                    <i class="fa-solid fa-angle-right"></i>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
      </div>

      <!-- Blog Categories Widget -->
      <div class="sidebar-widget">
        <h3 class="widget-title">Blog Categories</h3>
        <ul class="category-list">
            <?php 
            // Fetch blog categories
            try {
                if ($blogTypeColumnAvailable) {
                    [$blogCatScopeClause, $blogCatScopeParams] = site_blog_scope_filter_clause($scope, 'b.blog_type');
                    $blogCatSql = "
                        SELECT DISTINCT c.id, c.title, c.slug
                        FROM blog_categories c
                        INNER JOIN blogs b ON b.blog_category_id = c.id
                        WHERE b.is_published = 1
                          AND (b.published_at IS NULL OR b.published_at <= :current_time)
                          AND {$blogCatScopeClause}
                        ORDER BY c.title ASC
                    ";
                    $stmtBlogCats = $pdo->prepare($blogCatSql);
                    $stmtBlogCats->execute(array_merge([':current_time' => date('Y-m-d H:i:s')], $blogCatScopeParams));
                } elseif ($isAyurvedhScope) {
                    $stmtBlogCats = null;
                } else {
                    $stmtBlogCats = $pdo->query("SELECT id, title, slug FROM blog_categories ORDER BY title ASC");
                }
                $blogCategories = $stmtBlogCats ? $stmtBlogCats->fetchAll(PDO::FETCH_ASSOC) : [];
            } catch (PDOException $e) {
                $blogCategories = [];
            }
            
            if (!empty($blogCategories)):
                foreach ($blogCategories as $bCat): ?>
            <li>
                <a href="<?= e(site_blog_scope_append_query($blogListPath, ['cat' => (int)$bCat['id'], 'scope' => $isAyurvedhScope ? 'ayurvedh' : ''])) ?>" class="category-link">
                    <?= htmlspecialchars($bCat['title']) ?>
                    <i class="fa-solid fa-angle-right"></i>
                </a>
            </li>
            <?php endforeach;
            else: ?>
            <li class="text-sm text-gray-500">No blog categories available</li>
            <?php endif; ?>
        </ul>
      </div>
      
      <!-- Recent Posts -->
      <?php if (!empty($recentBlogs)): ?>
      <div class="sidebar-widget">
        <h3 class="widget-title">Latest Posts</h3>
        <?php foreach ($recentBlogs as $r): ?>
          <a href="<?= e(site_blog_scope_append_query('blog_single.php', ['slug' => $r['slug'] ?? '', 'scope' => $isAyurvedhScope ? 'ayurvedh' : ''])) ?>" style="text-decoration:none;">
            <div style="display:flex; gap:15px; margin-bottom:20px; align-items:flex-start;">
              <img src="<?= e($r['featured_image'] ?: '/assets/images/blog-default.jpg') ?>" alt="" style="width:80px; height:60px; object-fit:cover; border-radius:6px;">
              <div>
                <div style="font-weight:600; font-size:14px; line-height:1.4; margin-bottom:5px; color:#333; transition:color 0.2s;"><?= e($r['title']) ?></div>
                <div style="font-size:12px; color:#888;"><?= e(date('M j, Y', strtotime($r['created_at']))) ?></div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Popular Tags (Blog Tags) -->
      <div class="sidebar-widget">
        <h3 class="widget-title">Popular Tags</h3>
        
        <div class="tags-container">
            <?php if (!empty($popularTags)): ?>
              <?php foreach ($popularTags as $tag): ?>
                <a href="<?= e(site_blog_scope_append_query($blogListPath, ['tag' => $tag['slug'] ?? '', 'scope' => $isAyurvedhScope ? 'ayurvedh' : ''])) ?>" class="tag-pill"><?= htmlspecialchars($tag['name']) ?></a>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="text-sm text-gray-500">No tags available</p>
            <?php endif; ?>
        </div>
      </div>
      
    </aside>
  </div>

  <!-- RELATED POSTS -->
  <?php if (!empty($relatedPosts)): ?>
  <section class="related-posts-section">
    <h2 class="section-title">Related Articles</h2>
    <div class="related-posts-grid">
      <?php foreach ($relatedPosts as $related): ?>
        <a href="<?= e(site_blog_scope_append_query('blog_single.php', ['slug' => $related['slug'] ?? '', 'scope' => $isAyurvedhScope ? 'ayurvedh' : ''])) ?>" style="text-decoration:none;">
          <div class="related-post-card">
            <img src="<?= e($related['featured_image'] ?: '/assets/images/blog-default.jpg') ?>" alt="" class="related-post-image">
            <div class="related-post-content">
              <h3 class="related-post-title"><?= e($related['title']) ?></h3>
              <div class="related-post-meta">
                <i class="fa-regular fa-calendar"></i>
                <?= e(date('M j, Y', strtotime($related['created_at']))) ?>
              </div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php include 'includes/footer.php'; ?>

  <script>
    // Add IDs to Headings for Table of Contents navigation
    document.addEventListener('DOMContentLoaded', function() {
      const content = document.querySelector('.blog-content');
      if (content) {
        const headings = content.querySelectorAll('h2, h3');
        headings.forEach((heading, index) => {
          heading.id = 'section-' + (index + 1);
        });
      }
      
      // Smooth scroll for TOC links
      document.querySelectorAll('.toc-link').forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const targetId = this.getAttribute('href').substring(1);
          const targetElement = document.getElementById(targetId);
          
          if (targetElement) {
            const offset = 100;
            const elementPosition = targetElement.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - offset;
            
            window.scrollTo({
              top: offsetPosition,
              behavior: 'smooth'
            });
          }
        });
      });
    });
  </script>


</body>
</html>
