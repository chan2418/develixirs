<?php
// blog_single.php - Display single blog post
session_start();
require_once __DIR__ . '/includes/db.php';

$slug = $_GET['slug'] ?? '';
$post = null;
$recentBlogs = [];
$categoryBanners = [];

if ($slug) {
    try {
        // Fetch single post
        $stmt = $pdo->prepare("
            SELECT b.*, c.id as cat_id, c.title as category_name 
            FROM blogs b
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE b.slug = :slug AND b.is_published = 1 
            LIMIT 1
        ");
        $stmt->execute([':slug' => $slug]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post) {
            // Fetch banners for this category
            if (!empty($post['cat_id'])) {
                $stmtBanners = $pdo->prepare("SELECT filename FROM banners WHERE category_id = :cat_id AND is_active = 1 ORDER BY id DESC");
                $stmtBanners->execute([':cat_id' => $post['cat_id']]);
                $bannerRows = $stmtBanners->fetchAll(PDO::FETCH_ASSOC);
                foreach ($bannerRows as $row) {
                    if (!empty($row['filename'])) {
                        $categoryBanners[] = '/assets/uploads/banners/' . ltrim($row['filename'], '/');
                    }
                }
            }
        }

        // Fetch recent posts for sidebar
        $stmtRecent = $pdo->query("
            SELECT id, title, slug, featured_image, created_at 
            FROM blogs 
            WHERE is_published = 1 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $recentBlogs = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('Blog fetch error: ' . $e->getMessage());
    }
}

// Redirect if not found
if (!$post) {
    header('Location: blog.php');
    exit;
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
  <title><?= e($post['meta_title'] ?: $post['title']) ?> – Devilixirs</title>
  <meta name="description" content="<?= e($post['meta_description'] ?: '') ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  
  <style>
    :root{
      --primary:#D4AF37;
      --primary-dark:#B89026;
      --text:#1a1a1a;
      --text-light:#666;
      --border:#e0e0e0;
      --bg-light:#f5f5f5;
      --font-heading: 'Playfair Display', serif;
      --font-body: 'Poppins', sans-serif;
    }
    *{margin:0;padding:0;box-sizing:border-box;}
    body{
      font-family:var(--font-body);
      color:var(--text);
      background:#fff;
      line-height:1.7;
      /* padding-top removed */
    }
    img{max-width:100%;display:block;}
    a{text-decoration:none;color:inherit;transition:0.3s;}
    ul{list-style:none;}
    
    /* HERO BANNER */
    .hero{
      background:#1a1a1a;
      position:relative;
      overflow:hidden;
      min-height:350px;
      margin-bottom:50px;
    }
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
    
    /* LAYOUT */
    .layout{
      max-width:1300px;
      margin:0 auto 80px;
      padding:0 20px;
      display:grid;
      grid-template-columns:320px 1fr;
      gap:40px;
    }
    
    /* SIDEBAR */
    aside {
      position: sticky;
      top: 160px;
      height: fit-content;
    }
    .sidebar-box{
      background:#fff;
      border:1px solid var(--border);
      margin-bottom:25px;
      border-radius:12px;
      overflow:hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .sidebar-title{
      background:var(--primary);
      color:#fff;
      padding:15px 20px;
      font-size:16px;
      font-weight:700;
      letter-spacing:0.5px;
    }
    .sidebar-content{
      padding:20px;
    }
    
    .recent-post{
      display:flex;
      gap:12px;
      margin-bottom:18px;
      padding-bottom:18px;
      border-bottom:1px solid var(--border);
      transition: transform 0.2s;
    }
    .recent-post:hover {
      transform: translateX(3px);
    }
    .recent-post:last-child{
      border-bottom:none;
      margin-bottom:0;
      padding-bottom:0;
    }
    .recent-post img{
      width:80px;
      height:80px;
      object-fit:cover;
      border-radius:8px;
      flex-shrink:0;
    }
    .recent-post-title{
      font-weight:600;
      font-size:14px;
      margin-bottom:6px;
      line-height:1.4;
      color:var(--text);
    }
    .recent-post-title:hover {
      color: var(--primary);
    }
    .recent-post-meta{
      font-size:11px;
      color:#999;
      display:flex;
      align-items:center;
      gap:5px;
    }
    
    /* SINGLE POST CONTENT */
    .post-content-wrap {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .post-header {
      padding: 30px 30px 20px;
      border-bottom: 1px solid var(--border);
    }
    .post-title-main {
      font-family: var(--font-heading);
      font-size: 36px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 15px;
      line-height: 1.3;
    }
    .post-meta-single {
      font-size: 14px;
      color: var(--text-light);
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
    }
    .post-meta-single i {
      color: var(--primary);
      margin-right: 5px;
    }
    .post-category-badge {
      display: inline-block;
      background: var(--primary);
      color: #fff;
      padding: 6px 14px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .post-featured-image {
      width: 100%;
      height: auto;
      max-height: 550px;
      object-fit: cover;
    }
    .post-body-content {
      padding: 40px;
      font-size: 17px;
      color: var(--text);
      line-height: 1.8;
    }
    .post-body-content h2 { 
      font-family: var(--font-heading);
      margin-top: 35px; 
      margin-bottom: 18px; 
      font-size: 28px;
      color: var(--text);
    }
    .post-body-content h3 { 
      font-family: var(--font-heading);
      margin-top: 28px; 
      margin-bottom: 15px; 
      font-size: 22px;
      color: var(--text);
    }
    .post-body-content p { 
      margin-bottom: 20px;
      text-align: justify;
    }
    .post-body-content ul { 
      list-style: disc; 
      margin-left: 30px; 
      margin-bottom: 20px;
    }
    .post-body-content li {
      margin-bottom: 10px;
    }
    .post-body-content img { 
      max-width: 100%; 
      height: auto; 
      border-radius: 8px; 
      margin: 30px 0;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .post-body-content a {
      color: var(--primary);
      text-decoration: underline;
    }
    .post-body-content a:hover {
      color: var(--primary-dark);
    }

    @media(max-width:1024px){
      .layout{grid-template-columns:280px 1fr; gap:30px;}
    }
    @media(max-width:768px){
      body { padding-top: 80px; }
      .layout{grid-template-columns:1fr;}
      .post-title-main{font-size:28px;}
      .post-body-content {padding: 25px;}
      .hero { min-height: 250px; }
      aside { position: static; }
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- HERO BANNER -->
  <section class="hero">
    <?php if (!empty($categoryBanners)): ?>
      <div class="banner-carousel">
        <?php foreach ($categoryBanners as $index => $banner): ?>
          <div class="banner-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
               style="background-image: url('<?php echo htmlspecialchars($banner); ?>');">
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- MAIN LAYOUT -->
  <main class="layout">

    <!-- SIDEBAR LEFT -->
    <aside>
      <!-- Recent Posts -->
      <div class="sidebar-box">
        <div class="sidebar-title">Recent Posts</div>
        <div class="sidebar-content">
          <?php if (!empty($recentBlogs)): ?>
            <?php foreach ($recentBlogs as $r): ?>
              <div class="recent-post">
                <a href="blog_single.php?slug=<?= e($r['slug']) ?>" style="display:flex;gap:12px;width:100%;">
                  <img src="<?= e($r['featured_image'] ?: '/assets/images/blog-default.jpg') ?>" alt="">
                  <div>
                    <div class="recent-post-title"><?= e($r['title']) ?></div>
                    <div class="recent-post-meta">
                      <i class="fa-regular fa-calendar"></i>
                      <?= e(date('M j, Y', strtotime($r['created_at']))) ?>
                    </div>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="color:#999;font-size:13px;">No recent posts</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Categories -->
      <?php if (!empty($categories)): ?>
      <div class="sidebar-box">
        <div class="sidebar-title">Categories</div>
        <div class="sidebar-content">
          <ul style="font-size:14px;line-height:2.2;">
            <?php foreach ($categories as $cat): ?>
              <li>
                <a href="blog.php?cat=<?php echo (int)$cat['id']; ?>" style="display:flex;justify-content:space-between;color:var(--text-light);">
                  <span><?php echo htmlspecialchars($cat['title'] ?? $cat['name'] ?? ''); ?></span>
                  <i class="fa-solid fa-angle-right" style="font-size:12px;"></i>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <?php endif; ?>
    </aside>

    <!-- SINGLE POST CONTENT -->
    <article class="post-content-wrap">
      <?php if ($post['featured_image']): ?>
        <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" class="post-featured-image">
      <?php endif; ?>

      <div class="post-header">
        <h1 class="post-title-main"><?= e($post['title']) ?></h1>
        <div class="post-meta-single">
          <span><i class="fa-regular fa-calendar"></i><?= e(date('F j, Y', strtotime($post['created_at']))) ?></span>
          <?php if (!empty($post['category_name'])): ?>
            <span class="post-category-badge"><?= e($post['category_name']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="post-body-content">
        <?= $post['content'] // Content is HTML, output directly (admin trusted) ?>
      </div>
    </article>

  </main>

  <!-- FOOTER -->
  <!-- FOOTER -->
  <?php include 'footer.php'; ?>

  <script>
  // Banner carousel auto-scroll
  <?php if (!empty($categoryBanners) && count($categoryBanners) > 1): ?>
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
  </script>

</body>
</html>
