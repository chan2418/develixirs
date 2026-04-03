<?php
// admin/upload_blog_video.php - Handle video uploads for Quill blog editor
require_once __DIR__ . '/_auth.php';

header('Content-Type: application/json');

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];

// Validate file type
$allowedTypes = ['video/mp4', 'video/webm', 'video/quicktime'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes) && !in_array($file['type'], $allowedTypes)) {
    echo json_encode(['error' => 'Invalid file type. Only MP4, WEBM, and MOV videos allowed.']);
    exit;
}

// Max 50MB
if ($file['size'] > 50 * 1024 * 1024) {
    echo json_encode(['error' => 'File too large. Maximum 50MB allowed.']);
    exit;
}

// Create upload directory
$uploadDir = __DIR__ . '/../assets/uploads/blogs/videos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
if (empty($extension)) {
    // Determine extension from MIME type
    $extensionMap = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov'
    ];
    $extension = $extensionMap[$mimeType] ?? 'mp4';
}

$filename = 'blog_video_' . uniqid() . '_' . time() . '.' . $extension;
$targetPath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $location = '/assets/uploads/blogs/videos/' . $filename;
    echo json_encode(['location' => $location]);
} else {
    echo json_encode(['error' => 'Failed to upload video']);
}
