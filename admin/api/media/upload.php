<?php
/**
 * Media Upload Endpoint
 * Handles upload of images, audio (mp3), and video files for blog posts
 * 
 * Returns JSON: {"success": true, "url": "/path/to/file", "mime": "image/jpeg"}
 * Or: {"success": false, "error": "Error message"}
 */

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

// Configuration
$maxFileSize = 10 * 1024 * 1024; // 10MB
$allowedMimeTypes = [
    // Images
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/svg+xml',
    // Audio
    'audio/mpeg',      // MP3
    'audio/mp3',
    'audio/wav',
    'audio/ogg',
    // Video
    'video/mp4',
    'video/webm',
    'video/ogg',
    'video/quicktime'  // MOV
];

$response = ['success' => false, 'error' => 'Unknown error'];

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $response['error'] = 'No file uploaded or upload error occurred';
    echo json_encode($response);
    exit;
}

$file = $_FILES['file'];
$fileSize = $file['size'];
$fileTmp = $file['tmp_name'];
$fileName = $file['name'];

// Validate file size
if ($fileSize > $maxFileSize) {
    $maxSizeMB = $maxFileSize / (1024 * 1024);
    $response['error'] = "File too large. Maximum size is {$maxSizeMB}MB";
    echo json_encode($response);
    exit;
}

// Get MIME type (server-side detection, don't trust client)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $fileTmp);
finfo_close($finfo);

// Validate MIME type
if (!in_array($mimeType, $allowedMimeTypes)) {
    $response['error'] = 'Invalid file type. Allowed: images, audio (mp3, wav), video (mp4, webm)';
    echo json_encode($response);
    exit;
}

// Determine media category folder
$category = 'other';
if (strpos($mimeType, 'image/') === 0) {
    $category = 'images';
} elseif (strpos($mimeType, 'audio/') === 0) {
    $category = 'audio';
} elseif (strpos($mimeType, 'video/') === 0) {
    $category = 'video';
}

// Create upload directory structure
// From /admin/api/media/ go up 2 levels to /admin/
$uploadBaseDir = __DIR__ . '/../../assets/blog_media';
$uploadDir = $uploadBaseDir . '/' . $category . '/';

if (!file_exists($uploadDir)) {
    if (!@mkdir($uploadDir, 0755, true)) {
        $response['error'] = 'Failed to create upload directory';
        echo json_encode($response);
        exit;
    }
}

// Generate unique filename
$extension = pathinfo($fileName, PATHINFO_EXTENSION);
if (empty($extension)) {
    // Fallback: determine extension from MIME type
    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'audio/mpeg' => 'mp3',
        'audio/wav' => 'wav',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm'
    ];
    $extension = $mimeToExt[$mimeType] ?? 'bin';
}

$uniqueFileName = time() . '_' . uniqid() . '.' . $extension;
$targetPath = $uploadDir . $uniqueFileName;

// Move uploaded file
if (!move_uploaded_file($fileTmp, $targetPath)) {
    $response['error'] = 'Failed to save file';
    echo json_encode($response);
    exit;
}

// Success - URL should point to /admin/assets/blog_media/
$publicUrl = '/admin/assets/blog_media/' . $category . '/' . $uniqueFileName;
$response = [
    'success' => true,
    'url' => $publicUrl,
    'mime' => $mimeType,
    'category' => $category,
    'size' => filesize($targetPath)
];

echo json_encode($response);
