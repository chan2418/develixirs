<?php
// blog.php - Display published blogs from database
session_start();
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
<?php
// Include SEO helper
require_once __DIR__ . '/includes/seo_meta.php';

// Generate SEO meta tags
echo generate_seo_meta([
    'title' => 'Beauty & Wellness Blog - DevElixir Natural Cosmetics',
    'description' => 'Discover ayurvedic beauty tips, natural skincare routines, herbal wellness guides, and more. Expert advice on natural cosmetics and holistic beauty from DevElixir.',
    'keywords' => 'ayurvedic beauty blog, natural skincare tips, herbal wellness, beauty tips india, organic cosmetics guide, DevElixir blog',
    'url' => 'https://develixirs.com/blog.php',
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
                  <i class="fa-regular fa-calendar"></i>
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
  </script>

</body>
</html>
