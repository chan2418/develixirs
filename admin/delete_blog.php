<?php
// Admin: Delete Blog Post
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($blogId > 0) {
    try {
        // Get image path first to delete file
        $stmt = $pdo->prepare("SELECT featured_image FROM blogs WHERE id = :id");
        $stmt->execute([':id' => $blogId]);
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the blog post
        $deleteStmt = $pdo->prepare("DELETE FROM blogs WHERE id = :id");
        $deleteStmt->execute([':id' => $blogId]);
        
        // Delete associated image if exists
        if ($blog && !empty($blog['featured_image'])) {
            $imagePath = __DIR__ . '/..' . $blog['featured_image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    } catch (PDOException $e) {
        error_log('Blog delete error: ' . $e->getMessage());
    }
}

header('Location: /admin/blogs.php');
exit;
