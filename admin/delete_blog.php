<?php
// Admin: Delete Blog Post
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/blog_scope_helper.php';

$scope = admin_blog_scope_from_request();
$listUrl = admin_blog_scope_url('/admin/blogs.php', $scope);
$scopeColumnAvailable = admin_blog_ensure_scope_column($pdo);
$blogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($blogId > 0) {
    try {
        $scopeWhere = '';
        $scopeParams = [];
        if ($scopeColumnAvailable) {
            [$scopeClause, $scopeParams] = admin_blog_scope_filter_clause($scope, 'blog_type');
            $scopeWhere = " AND {$scopeClause}";
        }

        // Get image path first to delete file
        $stmt = $pdo->prepare("SELECT featured_image FROM blogs WHERE id = :id{$scopeWhere}");
        $stmt->execute(array_merge([':id' => $blogId], $scopeParams));
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the blog post
        $deleteStmt = $pdo->prepare("DELETE FROM blogs WHERE id = :id{$scopeWhere}");
        $deleteStmt->execute(array_merge([':id' => $blogId], $scopeParams));
        
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

header('Location: ' . $listUrl);
exit;
