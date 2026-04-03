<?php
// admin/save_review.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Helper for redirect
function redirect($msg, $type = 'success') {
    $_SESSION[$type] = $msg;
    header('Location: product_reviews.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('Invalid request method.', 'error');
}

// CSRF Check
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    redirect('Invalid CSRF token.', 'error');
}

// Handle Delete
if (!empty($_POST['delete']) && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM product_reviews WHERE id = ?");
        $stmt->execute([$id]);
        redirect('Review deleted successfully.');
    } catch (Exception $e) {
        redirect('Error deleting review: ' . $e->getMessage(), 'error');
    }
}

// Extract Inputs
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$product_id = (int)($_POST['product_id'] ?? 0);
$reviewer_name = trim($_POST['reviewer_name'] ?? '');
$rating = (int)($_POST['rating'] ?? 5);
$title = trim($_POST['title'] ?? '');
// UPDATED: Using `comment`
$comment = trim($_POST['comment'] ?? '');
$status = $_POST['status'] ?? 'pending';
$is_featured = !empty($_POST['is_featured']) ? 1 : 0;
$admin_note = trim($_POST['admin_note'] ?? '');

// Validation
if ($product_id <= 0) {
    redirect('Please select a product.', 'error');
}
if (empty($reviewer_name)) {
    redirect('Reviewer name is required.', 'error');
}
if (empty($comment)) {
    redirect('Review content is required.', 'error');
}
if ($rating < 1 || $rating > 5) {
    redirect('Rating must be between 1 and 5.', 'error');
}

try {
    if ($id) {
        // UPDATE
        $sql = "UPDATE product_reviews SET 
                product_id = ?, 
                reviewer_name = ?, 
                rating = ?, 
                title = ?, 
                comment = ?, 
                status = ?, 
                is_featured = ?, 
                admin_note = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $product_id, 
            $reviewer_name, 
            $rating, 
            $title, 
            $comment, 
            $status, 
            $is_featured, 
            $admin_note, 
            $id
        ]);
        redirect('Review updated successfully.');
    } else {
        // INSERT
        // Note: user_id is null for manual admin entry
        $sql = "INSERT INTO product_reviews 
                (product_id, reviewer_name, rating, title, comment, status, is_featured, admin_note, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $product_id, 
            $reviewer_name, 
            $rating, 
            $title, 
            $comment, 
            $status, 
            $is_featured, 
            $admin_note
        ]);
        redirect('Review created successfully.');
    }
} catch (Exception $e) {
    redirect('Database Error: ' . $e->getMessage(), 'error');
}
?>
