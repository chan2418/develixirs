<?php
// admin/upload_editor_video.php - Handle video uploads from Quill editor
session_start();
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_FILES['video'])) {
    echo json_encode(['success' => false, 'error' => 'No video file uploaded']);
    exit;
}

$file = $_FILES['video'];
$uploadDir = __DIR__ . '/../uploads/editor_videos/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Validate file
$allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid video format. Allowed: MP4, WEBM, OGG, MOV']);
    exit;
}

// Check file size (max 50MB)
$maxSize = 50 * 1024 * 1024; // 50MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'Video too large. Max size: 50MB']);
    exit;
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('video_') . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    $webPath = '/uploads/editor_videos/' . $filename;
    echo json_encode([
        'success' => true,
        'url' => $webPath,
        'filename' => $filename
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save video']);
}
