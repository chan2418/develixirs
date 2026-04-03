<?php
// blog_tag.php - Blog Tag Archive Page with SEO
require_once __DIR__ . '/includes/db.php';

$tagSlug = $_GET['tag'] ?? '';
$tag = null;
$posts = [];
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

if (!empty($tagSlug)) {
    try {
        // Fetch tag with SEO data
        $stmtTag = $pdo->prepare("SELECT * FROM blog_tags WHERE slug = ?");
        $stmtTag->execute([$tagSlug]);
        $tag = $stmtTag->fetch(PDO::FETCH_ASSOC);
        
        if ($tag) {
            // Fetch posts with this tag
            $stmtPosts = $pdo->prepare("
                SELECT b.*, ba.name as author_name, bc.title as cat_title
                FROM blogs b
                INNER JOIN blog_post_tags bpt ON b.id = bpt.blog_id
                LEFT JOIN blog_authors ba ON b.author_id = ba.id
                LEFT JOIN blog_categories bc ON b.blog_category_id = bc.id
                WHERE bpt.tag_id = ? 
                  AND b.is_published = 1
                  AND (b.published_at IS NULL OR b.published_at <= NOW())
                ORDER BY b.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmtPosts->execute([$tag['id'], $perPage, $offset]);
            $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count for pagination
            $stmtCount = $pdo->prepare("
                SELECT COUNT(*) FROM blogs b
                INNER JOIN blog_post_tags bpt ON b.id = bpt.blog_id
                WHERE bpt.tag_id = ? AND b.is_published = 1 AND (b.published_at IS NULL OR b.published_at <= NOW())
            ");
            $stmtCount->execute([$tag['id']]);
            $totalPosts = $stmtCount->fetchColumn();
            $totalPages = ceil($totalPosts / $perPage);
        }
    } catch (PDOException $e) {
        error_log('Tag archive error: ' . $e->getMessage());
    }
}

// If tag not found, redirect to blog
if (!$tag) {
    header('Location: /blog.php');
    exit;
}

// SEO Meta Tags
$pageTitle = !empty($tag['seo_title']) ? $tag['seo_title'] : $tag['name'] . ' - Blog Tag';
$metaDescription = !empty($tag['seo_description']) ? $tag['seo_description'] : 'Browse all blog posts tagged with ' . $tag['name'];
$ogImage = !empty($tag['seo_image']) ? $tag['seo_image'] : '/assets/images/default-og.jpg';

include __DIR__ . '/includes/header.php';
?>

<!-- SEO Meta Tags -->
<meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
<meta property="og:image" content="<?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $ogImage) ?>">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $ogImage) ?>">

<style>
.tag-archive-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 0;
    color: white;
    margin-bottom: 40px;
}
.blog-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border-radius: 12px;
    overflow: hidden;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.blog-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
.blog-card img {
    width: 100%;
    height: 220px;
    object-fit: cover;
}
.pagination {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-top: 40px;
}
.pagination a, .pagination span {
    padding: 8px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
}
.pagination a:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
.pagination .active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
</style>

<!-- Tag Archive Header -->
<div class="tag-archive-header">
  <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    <div style="display: inline-block; background: rgba(255,255,255,0.2); padding: 6px 16px; border-radius: 20px; margin-bottom: 16px;">
      <span style="font-size: 14px;">📌 Tag</span>
    </div>
    <h1 style="font-size: 42px; margin: 0 0 16px 0; font-weight: 700;"><?= htmlspecialchars($tag['name']) ?></h1>
    <?php if (!empty($tag['description'])): ?>
      <p style="font-size: 18px; opacity: 0.95; max-width: 700px; line-height: 1.6;"><?= htmlspecialchars($tag['description']) ?></p>
    <?php endif; ?>
    <p style="margin-top: 20px; opacity: 0.9;"><strong><?= count($posts) ?></strong> article<?= count($posts) != 1 ? 's' : '' ?> found</p>
  </div>
</div>

<!-- Blog Posts Grid -->
<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px 60px;">
  <?php if (!empty($posts)): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px;">
      <?php foreach ($posts as $post): ?>
        <div class="blog-card">
          <a href="/blog_single.php?slug=<?= htmlspecialchars($post['slug']) ?>" style="text-decoration: none; color: inherit;">
            <img src="<?= htmlspecialchars($post['featured_image'] ?: '/assets/images/blog-default.jpg') ?>" alt="<?= htmlspecialchars($post['title']) ?>">
            <div style="padding: 20px;">
              <?php if (!empty($post['cat_title'])): ?>
                <span style="display: inline-block; background: #e0e7ff; color: #667eea; font-size: 12px; padding: 4px 12px; border-radius: 12px; margin-bottom: 12px;">
                  <?= htmlspecialchars($post['cat_title']) ?>
                </span>
              <?php endif; ?>
              <h3 style="font-size: 20px; font-weight: 600; margin: 0 0 12px 0; line-height: 1.4;">
                <?= htmlspecialchars($post['title']) ?>
              </h3>
              <div style="display: flex; align-items: center; gap: 12px; font-size: 13px; color: #666;">
                <?php if (!empty($post['author_name'])): ?>
                  <span>👤 <?= htmlspecialchars($post['author_name']) ?></span>
                <?php endif; ?>
                <span>📅 <?= date('M j, Y', strtotime($post['created_at'])) ?></span>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?tag=<?= urlencode($tagSlug) ?>&page=<?= $page - 1 ?>">← Previous</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <?php if ($i == $page): ?>
            <span class="active"><?= $i ?></span>
          <?php else: ?>
            <a href="?tag=<?= urlencode($tagSlug) ?>&page=<?= $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
          <a href="?tag=<?= urlencode($tagSlug) ?>&page=<?= $page + 1 ?>">Next →</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div style="text-align: center; padding: 60px 20px;">
      <h2 style="font-size: 24px; color: #666; margin-bottom: 16px;">No articles found with this tag</h2>
      <p style="color: #999;">Check back later for new content!</p>
      <a href="/blog.php" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #667eea; color: white; border-radius: 8px; text-decoration: none;">
        Browse All Articles
      </a>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
