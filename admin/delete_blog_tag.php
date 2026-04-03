<?php
// admin/delete_blog_tag.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/blog_scope_helper.php';

$scope = admin_blog_scope_from_request();
$redirectUrl = admin_blog_scope_url('/admin/blog_tags.php', $scope);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$scopeColumnAvailable = admin_blog_ensure_tag_scope_column($pdo);

if ($id > 0) {
    try {
        $canDelete = true;
        if ($scopeColumnAvailable) {
            [$scopeClause, $scopeParams] = admin_blog_scope_taxonomy_filter_clause($scope, 'blog_scope', 'tag_del_scope');
            $checkStmt = $pdo->prepare("SELECT id FROM blog_tags WHERE id = :id AND {$scopeClause} LIMIT 1");
            $checkStmt->execute(array_merge([':id' => $id], $scopeParams));
            $canDelete = (bool)$checkStmt->fetchColumn();
        }

        if ($canDelete) {
            // Delete associations first to avoid foreign key constraints (or logical orphans).
            $stmt = $pdo->prepare("DELETE FROM blog_post_tags WHERE tag_id = ?");
            $stmt->execute([$id]);

            if ($scopeColumnAvailable) {
                [$scopeClause, $scopeParams] = admin_blog_scope_taxonomy_filter_clause($scope, 'blog_scope', 'tag_del_scope_2');
                $stmt = $pdo->prepare("DELETE FROM blog_tags WHERE id = :id AND {$scopeClause}");
                $stmt->execute(array_merge([':id' => $id], $scopeParams));
            } else {
                $stmt = $pdo->prepare("DELETE FROM blog_tags WHERE id = ?");
                $stmt->execute([$id]);
            }
        }

    } catch (PDOException $e) {
        // In a real app, you might pass this error back via session or query param
        error_log("Error deleting blog tag: " . $e->getMessage());
    }
}

// Redirect back
header('Location: ' . $redirectUrl);
exit;
