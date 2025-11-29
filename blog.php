<?php
// blog.php - Display published blogs from database
require_once __DIR__ . '/includes/db.php';

// Fetch published blog posts
$blogs = [];
$recentBlogs = [];
$categoryId = isset($_GET['cat']) ? (int)$_GET['cat'] : null;

try {
    // Build query
    $sql = "SELECT b.id, b.title, b.slug, b.content, b.featured_image, b.created_at, c.title as category_name 
            FROM blogs b
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE b.is_published = 1";
    $params = [];

    if ($categoryId) {
        $sql .= " AND b.category_id = :cat_id";
        $params[':cat_id'] = $categoryId;
    }

    $sql .= " ORDER BY b.created_at DESC LIMIT 9";

    // Fetch blogs
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch 3 recent blogs for sidebar
    $stmtRecent = $pdo->query("
        SELECT id, title, slug, featured_image, created_at 
        FROM blogs 
        WHERE is_published = 1 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $recentBlogs = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Blog fetch error: ' . $e->getMessage());
    $blogs = [];
    $recentBlogs = [];
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Journal – Devilixirs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
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
      --text: #333;
      --text-light: #666;
      --bg-light: #f9f9f9;
      --border: #eaeaea;
      --font-heading: 'Playfair Display', serif;
      --font-body: 'Poppins', sans-serif;
    }
    
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:var(--font-body); color:var(--text); background:#fff; line-height:1.7;}
    img{max-width:100%; display:block;}
    a{text-decoration:none; color:inherit; transition:0.3s;}
    ul{list-style:none;}
    
    /* HERO SECTION */
    .hero{
      background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('/assets/images/blog-hero.jpg'); /* You might need a hero image */
      background-color: #222; /* Fallback */
      background-size: cover;
      background-position: center;
      color: #fff;
      padding: 100px 0;
      text-align: center;
      margin-bottom: 60px;
    }
    .hero-inner{
      max-width: 800px;
      margin: 0 auto;
      padding: 0 20px;
    }
    .hero-title{
      font-family: var(--font-heading);
      font-size: 48px;
      font-weight: 700;
      margin-bottom: 15px;
      letter-spacing: 1px;
    }
    .breadcrumb{
      font-size: 14px;
      opacity: 0.8;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    .breadcrumb a:hover{ color: var(--primary); }
    
    /* LAYOUT */
    .layout{
      max-width: 1200px;
      margin: 0 auto 80px;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 50px;
    }
    
    /* BLOG GRID */
    .blog-grid{
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 40px;
    }
    
    /* POST CARD */
    .post-card{
      background: #fff;
      border-radius: 4px;
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    .post-card:hover{
      transform: translateY(-5px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    }
    
    .post-thumb{
      position: relative;
      overflow: hidden;
      padding-top: 65%; /* Aspect Ratio */
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
      transform: scale(1.05);
    }
    
    .post-category-badge{
      position: absolute;
      top: 20px;
      left: 20px;
      background: #fff;
      color: var(--text);
      padding: 6px 14px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      z-index: 2;
    }
    
    .post-body{
      padding: 30px 0 10px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    
    .post-meta{
      font-size: 12px;
      color: #999;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 500;
    }
    
    .post-title{
      font-family: var(--font-heading);
      font-size: 24px;
      font-weight: 700;
      line-height: 1.3;
      margin-bottom: 15px;
      color: var(--text);
    }
    .post-title:hover{
      color: var(--primary);
    }
    
    .post-excerpt{
      font-size: 15px;
      color: var(--text-light);
      margin-bottom: 20px;
      flex: 1;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .post-readmore{
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: var(--primary);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: auto;
      padding-bottom: 5px;
      border-bottom: 1px solid transparent;
      width: fit-content;
    }
    .post-readmore:hover{
      color: var(--primary-dark);
      border-bottom-color: var(--primary-dark);
    }
    
    /* SIDEBAR */
    aside{
      position: sticky;
      top: 100px;
      height: fit-content;
    }
    
    .sidebar-widget{
      margin-bottom: 40px;
    }
    
    .widget-title{
      font-family: var(--font-heading);
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--primary);
      display: inline-block;
    }
    
    .recent-post-item{
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      align-items: center;
    }
    .recent-post-item img{
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 4px;
    }
    .recent-post-info h4{
      font-family: var(--font-heading);
      font-size: 16px;
      line-height: 1.4;
      margin-bottom: 5px;
    }
    .recent-post-info h4:hover{ color: var(--primary); }
    .recent-post-date{
      font-size: 11px;
      color: #999;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    .category-list li{
      margin-bottom: 12px;
    }
    .category-list a{
      display: flex;
      justify-content: space-between;
      font-size: 14px;
      color: var(--text-light);
      padding: 8px 0;
      border-bottom: 1px solid var(--border);
    }
    .category-list a:hover, .category-list a.active{
      color: var(--primary);
      padding-left: 5px;
    }
    
    /* EMPTY STATE */
    .empty-state{
      grid-column: 1/-1;
      text-align: center;
      padding: 80px 0;
      color: #999;
    }
    .empty-state i{
      font-size: 48px;
      margin-bottom: 20px;
      color: var(--border);
    }
    
    /* RESPONSIVE */
    @media(max-width: 1024px){
      .layout{ grid-template-columns: 1fr; gap: 60px; }
      .sidebar-widget{ max-width: 400px; }
    }
    @media(max-width: 768px){
      .blog-grid{ grid-template-columns: 1fr; }
      .hero-title{ font-size: 36px; }
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-inner">
      <h1 class="hero-title">The Journal</h1>
      <div class="breadcrumb">
        <a href="index.php">Home</a>
        <span> / </span>
        <span>Blog</span>
      </div>
    </div>
  </section>

  <!-- MAIN LAYOUT -->
  <main class="layout">

    <!-- BLOG CONTENT -->
    <section>
      <div class="blog-grid">
        <?php if (empty($blogs)): ?>
          <div class="empty-state">
            <i class="fa-regular fa-newspaper"></i>
            <h3>No stories found</h3>
            <p>We haven't published any articles in this category yet.</p>
          </div>
        <?php else: ?>
          <?php foreach ($blogs as $p): ?>
            <article class="post-card">
              <a href="blog_single.php?slug=<?= e($p['slug']) ?>" class="post-thumb">
                <img src="<?= e($p['featured_image'] ?: '/assets/images/blog-default.jpg') ?>" alt="<?= e($p['title']) ?>">
                <?php if (!empty($p['category_name'])): ?>
                  <span class="post-category-badge"><?= e($p['category_name']) ?></span>
                <?php endif; ?>
              </a>
              
              <div class="post-body">
                <div class="post-meta">
                  <?= e(date('F j, Y', strtotime($p['created_at']))) ?>
                </div>
                
                <h2 class="post-title">
                  <a href="blog_single.php?slug=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a>
                </h2>
                
                <p class="post-excerpt"><?= e(truncate($p['content'], 140)) ?></p>
                
                <a href="blog_single.php?slug=<?= e($p['slug']) ?>" class="post-readmore">
                  Read Story <i class="fa-solid fa-arrow-right-long"></i>
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- SIDEBAR -->
    <aside>
      
      <!-- Categories -->
      <div class="sidebar-widget">
        <h3 class="widget-title">Categories</h3>
        <ul class="category-list">
          <li>
            <a href="blog.php" class="<?= !$categoryId ? 'active' : '' ?>">
              All Stories <span><i class="fa-solid fa-angle-right"></i></span>
            </a>
          </li>
          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
              <li>
                <a href="blog.php?cat=<?= (int)$cat['id'] ?>" class="<?= ($categoryId == $cat['id']) ? 'active' : '' ?>">
                  <?= htmlspecialchars($cat['title'] ?? $cat['name'] ?? '') ?>
                  <span><i class="fa-solid fa-angle-right"></i></span>
                </a>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Recent Posts -->
      <div class="sidebar-widget">
        <h3 class="widget-title">Recent Stories</h3>
        <?php if (!empty($recentBlogs)): ?>
          <?php foreach ($recentBlogs as $r): ?>
            <div class="recent-post-item">
              <a href="blog_single.php?slug=<?= e($r['slug']) ?>">
                <img src="<?= e($r['featured_image'] ?: '/assets/images/blog-default.jpg') ?>" alt="">
              </a>
              <div class="recent-post-info">
                <h4><a href="blog_single.php?slug=<?= e($r['slug']) ?>"><?= e($r['title']) ?></a></h4>
                <div class="recent-post-date"><?= e(date('M j, Y', strtotime($r['created_at']))) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </aside>

  </main>

  <!-- FOOTER -->
  <footer style="background:#1a1a1a; color:#999; padding:60px 0; text-align:center; font-size:13px; border-top: 1px solid #333;">
    <p style="letter-spacing: 1px;">&copy; <?= date('Y') ?> <strong>Devilixirs</strong>. All Rights Reserved.</p>
  </footer>

</body>
</html>
