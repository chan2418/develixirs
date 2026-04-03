<?php
// admin/upload_image_ajax.php - Simple AJAX upload handler
session_start();
header('Content-Type: application/json');

// Check auth
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check file
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];

// Prepare directory  
$uploadDir = __DIR__ . '/../assets/uploads/blogs/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate filename
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = 'upload_' . time() . '_' . uniqid() . '.' . $ext;
$targetPath = $uploadDir . $filename;

// Move file
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $url = '/assets/uploads/blogs/' . $filename;
    echo json_encode(['success' => true, 'url' => $url]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
}
