<?php
// admin/upload_custom.php
// Pure JSON upload handler - No CKEditor dependencies
header('Content-Type: application/json');
error_reporting(0); // Suppress warnings to keep JSON valid
ini_set('display_errors', 0);

// Basic CORS if needed (though samesite usually)
header("Access-Control-Allow-Origin: *");

$response = ['success' => false, 'message' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file received']);
    exit;
}

$file = $_FILES['file'];
$uploadDir = __DIR__ . '/../assets/uploads/blogs/';
if (!file_exists($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Validation
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
if (!in_array(strtolower($file['type']), $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only images allowed.']);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) { // 5MB
    echo json_encode(['success' => false, 'message' => 'File too large (Max 5MB)']);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'img_' . time() . '_' . uniqid() . '.' . $ext;
$target = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $target)) {
    $url = '/assets/uploads/blogs/' . $filename;
    echo json_encode([
        'success' => true, 
        'url' => $url,
        'message' => 'Upload successful'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Server failed to save file']);
}
?>
