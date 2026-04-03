<?php
// admin/handlers/save_preview_data.php
// Updated: Force Cache Refresh
session_start();
require_once __DIR__ . '/../_auth.php'; // Ensure admin is logged in

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// 1. Initialize preview data from POST
$previewData = $_POST;

// 2. Generic File Upload Handler
// iterates over ALL $_FILES and saves them, storing paths in $previewData[key_paths]
$tempUploadDir = __DIR__ . '/../../assets/uploads/temp/';
if (!is_dir($tempUploadDir)) {
    mkdir($tempUploadDir, 0777, true);
}

foreach ($_FILES as $key => $fileInfo) {
    $paths = [];
    
    // Normalize logic for single vs multiple
    if (is_array($fileInfo['name'])) {
        foreach ($fileInfo['name'] as $i => $fname) {
            if ($fileInfo['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($fname, PATHINFO_EXTENSION);
                $newName = 'preview_' . $key . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($fileInfo['tmp_name'][$i], $tempUploadDir . $newName)) {
                    $paths[] = 'assets/uploads/temp/' . $newName;
                }
            }
        }
    } else {
        if ($fileInfo['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
            $newName = 'preview_' . $key . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($fileInfo['tmp_name'], $tempUploadDir . $newName)) {
                $paths[] = 'assets/uploads/temp/' . $newName;
            }
        }
    }
    
    if (!empty($paths)) {
        // If single file input (not array name), store as string or array?
        // Let's store as array for consistency, consumer checks [0]
        $previewData[$key . '_paths'] = $paths;
    }
}

// Backward compatibility for Product Preview (expects images_array)
if (isset($previewData['preview_images_paths'])) {
    $existing = [];
    if (!empty($_POST['existing_images'])) {
        $existing = is_array($_POST['existing_images']) ? $_POST['existing_images'] : explode(',', $_POST['existing_images']);
    }
    $previewData['images_array'] = array_merge($previewData['preview_images_paths'], $existing);
    $previewData['preview_images'] = $previewData['images_array'];
}

// 3. Process Variants
// $_POST['variants'] is an array from FormData. Encode it for preview page logic.
if (!empty($_POST['variants']) && is_array($_POST['variants'])) {
    $previewData['variants_json'] = json_encode($_POST['variants']);
}

// 3. Save to Session (Token Based)
$previewToken = bin2hex(random_bytes(16)); // Secure 32-char token
if (!isset($_SESSION['previews'])) {
    $_SESSION['previews'] = [];
}

// Store data with timestamp for potential cleanup
$_SESSION['previews'][$previewToken] = array_merge($previewData, [
    'created_at' => time()
]);

// Limit session size: keep only last 5 previews
if (count($_SESSION['previews']) > 5) {
    array_shift($_SESSION['previews']);
}

// 4. Determine Preview URL based on Type
$previewType = $_POST['preview_type'] ?? 'product'; // default to product for backward compat
$url = '/product_view.php';

switch ($previewType) {
    case 'blog':
        $url = '/blog_single.php';
        break;
    case 'category':
        $url = '/product.php'; // Category page on frontend
        break;
    case 'product':
    default:
        $url = '/product_view.php';
        break;
}

echo json_encode([
    'success' => true, 
    'preview_url' => $url . '?preview_token=' . $previewToken,
    'token' => $previewToken
]);
