<?php
// blog_single.php - Display single blog post
require_once __DIR__ . '/includes/db.php';

$slug = $_GET['slug'] ?? '';
$post = null;
$recentBlogs = [];

if ($slug) {
    try {
        // Fetch single post
        $stmt = $pdo->prepare("
            SELECT * FROM blogs 
            WHERE slug = :slug AND is_published = 1 
            LIMIT 1
        ");
        $stmt->execute([':slug' => $slug]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch recent posts for sidebar
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
  
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  
  <style>
    :root{
      --primary:#D4AF37;
      --accent:#D4AF37;
      --text:#222;
      --border:#e3e3e3;
      --footer-bg:#252525;
    }
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:"Poppins",sans-serif;color:var(--text);background:#f7f7f7;line-height:1.6;}
    img{max-width:100%;display:block;}
    a{text-decoration:none;color:inherit;}
    ul{list-style:none;}
    
    .hero{
      background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      position:relative;
      color:#fff;
      margin-bottom:40px;
      padding:60px 0;
    }
    .hero-inner{
      max-width:1200px;
      margin:0 auto;
      padding:0 15px;
      text-align:center;
    }
    .hero-title{
      font-size:32px;
      font-weight:700;
      margin-bottom:10px;
    }
    .breadcrumb{
      font-size:14px;
      opacity:0.9;
    }
    .breadcrumb a{
      color:#fff;
      text-decoration:underline;
    }
    
    .layout{
      max-width:1200px;
      margin:0 auto 60px;
      padding:0 15px;
      display:grid;
      grid-template-columns:280px 1fr;
      gap:30px;
    }
    
    .sidebar-box{
      border:1px solid var(--border);
      margin-bottom:25px;
      background:#fff;
      border-radius:8px;
      overflow:hidden;
    }
    .sidebar-title{
      background:var(--primary);
      color:#fff;
      padding:12px 16px;
      font-size:14px;
      font-weight:600;
    }
    .sidebar-content{
      padding:16px;
      font-size:13px;
      color:#444;
    }
    
    .recent-post{
      display:flex;
      gap:12px;
      margin-bottom:16px;
      padding-bottom:16px;
      border-bottom:1px solid #f0f0f0;
    }
    .recent-post:last-child{
      border-bottom:none;
      margin-bottom:0;
      padding-bottom:0;
    }
    .recent-post img{
      width:70px;
      height:70px;
      object-fit:cover;
      border-radius:4px;
    }
    .recent-post-title{
      font-weight:500;
      font-size:13px;
      margin-bottom:4px;
      line-height:1.4;
    }
    .recent-post-meta{
      font-size:11px;
      color:#999;
    }
    
    /* Single Post Styles */
    .post-content-wrap {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 8px;
      overflow: hidden;
      padding: 30px;
    }
    .post-header {
      margin-bottom: 20px;
    }
    .post-meta-single {
      font-size: 13px;
      color: #777;
      margin-bottom: 15px;
      display: flex;
      gap: 15px;
    }
    .post-featured-image {
      width: 100%;
      height: auto;
      max-height: 500px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 30px;
    }
    .post-body-content {
      font-size: 16px;
      color: #333;
      line-height: 1.8;
    }
    .post-body-content h2 { margin-top: 30px; margin-bottom: 15px; font-size: 24px; }
    .post-body-content h3 { margin-top: 25px; margin-bottom: 12px; font-size: 20px; }
    .post-body-content p { margin-bottom: 20px; }
    .post-body-content ul { list-style: disc; margin-left: 20px; margin-bottom: 20px; }
    .post-body-content img { max-width: 100%; height: auto; border-radius: 4px; margin: 20px 0; }

    @media(max-width:1024px){
      .layout{grid-template-columns:250px 1fr;}
    }
    @media(max-width:768px){
      .layout{grid-template-columns:1fr;}
      .hero-title{font-size:24px;}
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- HERO / BREADCRUMB -->
  <section class="hero">
    <div class="hero-inner">
      <h1 class="hero-title"><?= e($post['title']) ?></h1>
      <div class="breadcrumb">
        <a href="index.php">Home</a>
        <span> › </span>
        <a href="blog.php">Blog</a>
        <span> › </span>
        <span><?= e($post['title']) ?></span>
      </div>
    </div>
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
            <p style="color:#999;font-size:12px;">No recent posts</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Categories (Dynamic) -->
      <div class="sidebar-box">
        <div class="sidebar-title">Categories</div>
        <div class="sidebar-content">
          <ul style="font-size:13px;line-height:2;">
            <?php if (!empty($categories)): ?>
              <?php foreach ($categories as $cat): ?>
                <li>
                  <a href="blog.php?cat=<?php echo (int)$cat['id']; ?>">
                    <?php echo htmlspecialchars($cat['title'] ?? $cat['name'] ?? ''); ?>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li><span style="color:#999;">No categories found</span></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </aside>

    <!-- SINGLE POST CONTENT -->
    <article class="post-content-wrap">
      <?php if ($post['featured_image']): ?>
        <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" class="post-featured-image">
      <?php endif; ?>

      <div class="post-header">
        <div class="post-meta-single">
          <span><i class="fa-regular fa-calendar"></i> <?= e(date('F j, Y', strtotime($post['created_at']))) ?></span>

        </div>
        <!-- Title is already in Hero, but we can repeat or keep minimal -->
      </div>

      <div class="post-body-content">
        <?= $post['content'] // Content is HTML, so we output directly (admin trusted) ?>
      </div>
    </article>

  </main>

  <!-- FOOTER -->
  <footer style="background:#252525;color:#d3d3d3;padding:40px 0;text-align:center;font-size:13px;">
    <p>Copyright © <?= date('Y') ?> <strong>Devilixirs</strong>. All Rights Reserved.</p>
  </footer>

</body>
</html>
