<?php
/**
 * Dynamic Sitemap Generator
 * Generates sitemap.xml with all products, categories, and pages from database
 */

require_once __DIR__ . '/../includes/db.php';

// Start output buffering FIRST
ob_start();

// Get current date
$today = date('Y-m-d');

// Start XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n\n";

// Homepage
echo "    <!-- Homepage -->\n";
echo "    <url>\n";
echo "        <loc>https://develixirs.com/</loc>\n";
echo "        <lastmod>{$today}</lastmod>\n";
echo "        <changefreq>daily</changefreq>\n";
echo "        <priority>1.0</priority>\n";
echo "    </url>\n\n";

// Static pages
$staticPages = [
    ['url' => 'product.php', 'changefreq' => 'daily', 'priority' => '0.9'], // Was shop.php
    // 'product.php' is already here? Line 32 was product.php. 
    // If shop.php is gone, I should remove it or rename it.
    // The original list had shop.php AND product.php.
    // Let's just remove shop.php if it's dead, or map it to product.php?
    // User file list only shows product.php.
    
    ['url' => 'blog.php', 'changefreq' => 'weekly', 'priority' => '0.7'],
    ['url' => 'contact.php', 'changefreq' => 'monthly', 'priority' => '0.6']
];

echo "    <!-- Main Pages -->\n";
foreach ($staticPages as $page) {
    echo "    <url>\n";
    echo "        <loc>https://develixirs.com/{$page['url']}</loc>\n";
    echo "        <lastmod>{$today}</lastmod>\n";
    echo "        <changefreq>{$page['changefreq']}</changefreq>\n";
    echo "        <priority>{$page['priority']}</priority>\n";
    echo "    </url>\n";
}
echo "\n";

// Categories
try {
    $stmt = $pdo->query("SELECT id, name, updated_at FROM categories WHERE parent_id = 0 OR parent_id IS NULL ORDER BY id");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($categories) {
        echo "    <!-- Category Pages -->\n";
        foreach ($categories as $cat) {
            $lastmod = $cat['updated_at'] ?? $today;
            $catId = (int)$cat['id'];
            echo "    <url>\n";
            // Corrected to product.php?cat=ID (matching product.php logic)
            echo "        <loc>https://develixirs.com/product.php?cat={$catId}</loc>\n"; 
            echo "        <lastmod>{$lastmod}</lastmod>\n";
            echo "        <changefreq>weekly</changefreq>\n";
            echo "        <priority>0.8</priority>\n";
            echo "    </url>\n";
        }
        echo "\n";
    }
} catch (PDOException $e) {
    // Handle error silently in sitemap
}

// Products
try {
    $stmt = $pdo->query("
        SELECT id, name, slug, updated_at, images 
        FROM products 
        WHERE is_active = 1 
        ORDER BY id DESC 
        LIMIT 1000
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($products) {
        echo "    <!-- Product Pages -->\n";
        foreach ($products as $product) {
            $lastmod = $product['updated_at'] ?? $today;
            $productId = (int)$product['id'];
            $slug = $product['slug'] ?? '';
            
            echo "    <url>\n";
            echo "        <loc>https://develixirs.com/product_view.php?id={$productId}</loc>\n";
            echo "        <lastmod>{$lastmod}</lastmod>\n";
            echo "        <changefreq>weekly</changefreq>\n";
            echo "        <priority>0.8</priority>\n";
            
            // Add product image if available
            if (!empty($product['images'])) {
                $images = json_decode($product['images'], true);
                if (is_array($images) && !empty($images[0])) {
                    // Clean the image filename
                    $imageFile = htmlspecialchars($images[0], ENT_XML1, 'UTF-8');
                    $imageUrl = "https://develixirs.com/assets/uploads/products/{$imageFile}";
                    $imageName = htmlspecialchars($product['name'], ENT_XML1, 'UTF-8');
                    
                    echo "        <image:image>\n";
                    echo "            <image:loc>{$imageUrl}</image:loc>\n";
                    echo "            <image:title>{$imageName}</image:title>\n";
                    echo "        </image:image>\n";
                }
            }
            
            echo "    </url>\n";
        }
        echo "\n";
    }
} catch (PDOException $e) {
    // Handle error silently in sitemap
}

// Blog posts
try {
    $stmt = $pdo->query("
        SELECT id, title, slug, updated_at 
        FROM blogs 
        WHERE is_published = 1 
        ORDER BY created_at DESC 
        LIMIT 500
    ");
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($blogs) {
        echo "    <!-- Blog Posts -->\n";
        foreach ($blogs as $blog) {
            $lastmod = $blog['updated_at'] ?? $today;
            $blogId = (int)$blog['id'];
            $slug = htmlspecialchars($blog['slug'] ?? '', ENT_XML1, 'UTF-8');
            
            echo "    <url>\n";
            if ($slug) {
                echo "        <loc>https://develixirs.com/blog_single.php?slug={$slug}</loc>\n";
            } else {
                echo "        <loc>https://develixirs.com/blog_single.php?id={$blogId}</loc>\n";
            }
            echo "        <lastmod>{$lastmod}</lastmod>\n";
            echo "        <changefreq>monthly</changefreq>\n";
            echo "        <priority>0.6</priority>\n";
            echo "    </url>\n";
        }
    }
} catch (PDOException $e) {
    // Handle error silently in sitemap
}

// Close XML
echo "\n</urlset>";

// Get buffered content
$xml = ob_get_clean();

// Optionally save to file
if (isset($_GET['save']) && $_GET['save'] === '1') {
    file_put_contents(__DIR__ . '/../sitemap.xml', $xml);
    header('Content-Type: text/html; charset=utf-8');
    echo "<h2>Sitemap saved to sitemap.xml</h2>";
    echo "<p><a href='/sitemap.xml' target='_blank'>View Sitemap</a></p>";
    echo "<p><a href='sitemap_generator.php'>Generate Again</a></p>";
} else {
    // Output XML
    header('Content-Type: text/xml; charset=utf-8');
    echo $xml;
}
?>
