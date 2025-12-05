<?php
session_start();
// die('DEBUG: Reached submit_review.php'); // Uncomment to test reachability
require_once __DIR__ . '/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    file_put_contents('debug_review.log', date('Y-m-d H:i:s') . " - Error: User not logged in\n", FILE_APPEND);
    header('Location: login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? 'product.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $userId = (int)$_SESSION['user_id'];
    $rating = (int)($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['review'] ?? '');
    
    file_put_contents('debug_review.log', date('Y-m-d H:i:s') . " - Attempt: User $userId submitting for Product $productId. Rating: $rating\n", FILE_APPEND);
    
    // Validation
    if ($productId <= 0) {
        $_SESSION['error'] = 'Invalid product';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'product.php'));
        exit;
    }
    
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = 'Please select a rating between 1 and 5 stars';
        header('Location: product_view.php?id=' . $productId);
        exit;
    }
    
    if (empty($reviewText)) {
        $_SESSION['error'] = 'Please write a review';
        header('Location: product_view.php?id=' . $productId);
        exit;
    }
    
    try {
        // Get user info first
        $stmtUser = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        $reviewerName = $user['name'] ?? 'Anonymous';
        $reviewerEmail = $user['email'] ?? '';

        // Check if user already reviewed this product (using email)
        if (!empty($reviewerEmail)) {
            $stmtCheck = $pdo->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND reviewer_email = ?");
            $stmtCheck->execute([$productId, $reviewerEmail]);
            
            if ($stmtCheck->fetch()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
                exit;
            }
        }
        
        // Insert review (Auto-approved for now)
        $stmt = $pdo->prepare("
            INSERT INTO product_reviews (product_id, reviewer_name, reviewer_email, rating, comment, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'approved', NOW())
        ");
        
        $stmt->execute([$productId, $reviewerName, $reviewerEmail, $rating, $reviewText]);
        
        // Return JSON success
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Thank you! Your review has been submitted successfully.',
            'review' => [
                'reviewer_name' => $reviewerName,
                'rating' => $rating,
                'created_at' => date('M j, Y'),
                'comment' => nl2br(htmlspecialchars($reviewText))
            ]
        ]);
        exit;
        
    } catch (PDOException $e) {
        error_log('Review submission error: ' . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to submit review. Please try again.']);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
