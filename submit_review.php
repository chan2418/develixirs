<?php
// ============= SUPER STRONG DEBUGGING =============
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start(); // Start buffering

// Log everything to file
$debugLog = __DIR__ . '/debug_review_log.txt';
function logDebug($message) {
    global $debugLog;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($debugLog, $logMessage, FILE_APPEND);
    error_log($logMessage); // Also log to PHP error log
}

logDebug("=== NEW REVIEW SUBMISSION ATTEMPT ===");
logDebug("Script started at: " . __FILE__);

// Start session
try {
    session_start();
    logDebug("Session started successfully");
} catch (Exception $e) {
    logDebug("ERROR starting session: " . $e->getMessage());
}

logDebug("Session Data: " . print_r($_SESSION, true));
logDebug("Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
logDebug("Request Method: " . $_SERVER['REQUEST_METHOD']);
logDebug("POST Data: " . print_r($_POST, true));
logDebug("FILES Data: " . print_r($_FILES, true));

// Include database
try {
    require_once __DIR__ . '/includes/db.php';
    logDebug("Database connection included successfully");
    logDebug("PDO object exists: " . (isset($pdo) ? 'YES' : 'NO'));
} catch (Exception $e) {
    logDebug("FATAL ERROR loading database: " . $e->getMessage());
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    logDebug("ERROR: User not logged in - redirecting to login");
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to submit a review. Please login first.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logDebug("ERROR: Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $productId = (int)($_POST['product_id'] ?? 0);
    $userId = (int)$_SESSION['user_id'];
    $rating = (int)($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['review'] ?? '');
    
    logDebug("Parsed Data - Product ID: $productId, User ID: $userId, Rating: $rating, Review: " . substr($reviewText, 0, 50) . "...");
    
    // Validation
    if ($productId <= 0) {
        logDebug("VALIDATION ERROR: Invalid product ID: $productId");
        throw new Exception('Invalid product ID');
    }
    
    if ($rating < 1 || $rating > 5) {
        logDebug("VALIDATION ERROR: Invalid rating: $rating");
        throw new Exception('Please select a rating between 1 and 5 stars');
    }
    
    if (empty($reviewText)) {
        logDebug("VALIDATION ERROR: Empty review text");
        throw new Exception('Please write a review');
    }
    
    logDebug("Validation passed - fetching user info");
    
    // Get user info
    $stmtUser = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        logDebug("ERROR: User not found in database for ID: $userId");
        throw new Exception("User account not found. Please login again.");
    }
    
    $reviewerName = $user['name'] ?? 'Anonymous';
    $reviewerEmail = $user['email'] ?? '';
    
    logDebug("User Data Retrieved - Name: $reviewerName, Email: $reviewerEmail");

    // Check for duplicate review
    if (!empty($reviewerEmail)) {
        logDebug("Checking for duplicate review");
        $stmtCheck = $pdo->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND reviewer_email = ?");
        $stmtCheck->execute([$productId, $reviewerEmail]);
        
        if ($stmtCheck->fetch()) {
            logDebug("ERROR: Duplicate review attempt detected");
            throw new Exception('You have already reviewed this product');
        }
        logDebug("No duplicate found - proceeding");
    }
    
    // ============= IMAGE UPLOAD HANDLING =============
    $uploadedImages = [];
    $uploadDir = __DIR__ . '/assets/uploads/reviews/';
    
    logDebug("Upload directory path: $uploadDir");
    logDebug("Upload directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO'));
    logDebug("Upload directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO'));
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        logDebug("Creating upload directory");
        if (mkdir($uploadDir, 0755, true)) {
            logDebug("SUCCESS: Created directory");
        } else {
            logDebug("ERROR: Failed to create directory");
        }
    }
    
    if (isset($_FILES['review_images']) && !empty($_FILES['review_images']['name'][0])) {
        logDebug("Processing uploaded images - Count: " . count($_FILES['review_images']['name']));
        
        $maxImages = 3;
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        
        $fileCount = count($_FILES['review_images']['name']);
        
        for ($i = 0; $i < min($fileCount, $maxImages); $i++) {
            if ($_FILES['review_images']['error'][$i] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['review_images']['tmp_name'][$i];
                $fileName = $_FILES['review_images']['name'][$i];
                $fileSize = $_FILES['review_images']['size'][$i];
                $fileType = $_FILES['review_images']['type'][$i];
                
                logDebug("Image $i: $fileName (Size: $fileSize, Type: $fileType)");
                
                if ($fileSize > $maxFileSize) {
                    logDebug("WARNING: Image $i exceeds max size");
                    continue;
                }
                
                if (!in_array($fileType, $allowedTypes)) {
                    logDebug("WARNING: Image $i invalid type");
                    continue;
                }
                
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = 'review_' . $productId . '_' . $userId . '_' . time() . '_' . $i . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;
                
                logDebug("Attempting to move file to: $destPath");
                
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $uploadedImages[] = $newFileName;
                    logDebug("SUCCESS: Uploaded $newFileName");
                } else {
                    logDebug("ERROR: Failed to move file");
                }
            } else {
                logDebug("ERROR: Upload error code for image $i: " . $_FILES['review_images']['error'][$i]);
            }
        }
    } else {
        logDebug("No images uploaded");
    }
    
    $imagesJson = !empty($uploadedImages) ? json_encode($uploadedImages) : null;
    logDebug("Images JSON: " . ($imagesJson ?? 'NULL'));
    
    // ============= INSERT REVIEW =============
    logDebug("Preparing to insert review into database");
    
    // Check if images column exists
    $hasImagesColumn = false;
    try {
        $pdo->query("SELECT images FROM product_reviews LIMIT 1");
        $hasImagesColumn = true;
        logDebug("Images column EXISTS in table");
    } catch (Exception $e) {
        logDebug("Images column DOES NOT EXIST - using fallback query");
    }
    
    if ($hasImagesColumn) {
        logDebug("Using INSERT with images column");
        $stmt = $pdo->prepare("
            INSERT INTO product_reviews (product_id, reviewer_name, reviewer_email, rating, comment, images, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'approved', NOW())
        ");
        $result = $stmt->execute([$productId, $reviewerName, $reviewerEmail, $rating, $reviewText, $imagesJson]);
    } else {
        logDebug("Using INSERT without images column");
        $stmt = $pdo->prepare("
            INSERT INTO product_reviews (product_id, reviewer_name, reviewer_email, rating, comment, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'approved', NOW())
        ");
        $result = $stmt->execute([$productId, $reviewerName, $reviewerEmail, $rating, $reviewText]);
    }

    
    $insertedId = $pdo->lastInsertId();
    logDebug("INSERT Result: " . ($result ? 'SUCCESS' : 'FAILED'));
    logDebug("Inserted Review ID: $insertedId");
    
    // Return success
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your review has been submitted successfully.',
        'review' => [
            'reviewer_name' => $reviewerName,
            'rating' => $rating,
            'created_at' => date('M j, Y'),
            'comment' => nl2br(htmlspecialchars($reviewText)),
            'images' => $uploadedImages
        ]
    ]);
    logDebug("SUCCESS: Response sent to client");
    exit;
    
} catch (PDOException $e) {
    $errorMsg = "DATABASE ERROR: " . $e->getMessage();
    logDebug($errorMsg);
    logDebug("Error Code: " . $e->getCode());
    logDebug("Stack Trace: " . $e->getTraceAsString());
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'debug' => 'Check debug_review_log.txt for details'
    ]);
    exit;
    
} catch (Exception $e) {
    $errorMsg = "GENERAL ERROR: " . $e->getMessage();
    logDebug($errorMsg);
    logDebug("Stack Trace: " . $e->getTraceAsString());
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'debug' => 'Check debug_review_log.txt for details'
    ]);
    exit;
}
