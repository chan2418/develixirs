<?php
// admin/handlers/media_upload.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Configuration
$uploadDir = __DIR__ . '/../../assets/uploads/media/';
$thumbDir = __DIR__ . '/../../assets/uploads/media/thumbs/';
$maxFileSize = 100 * 1024 * 1024; // 100MB
$allowedMimes = [
    'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/svg+xml',
    'video/mp4', 'video/webm',
    'application/pdf'
];

// Create directories if not exist
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($thumbDir)) mkdir($thumbDir, 0777, true);

$uploaded = [];
$errors = [];

if (!isset($_FILES['files'])) {
    echo json_encode(['success' => false, 'error' => 'No files uploaded']);
    exit;
}

$files = $_FILES['files'];
$fileCount = is_array($files['name']) ? count($files['name']) : 1;

for ($i = 0; $i < $fileCount; $i++) {
    $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
    $fileTmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
    $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];

    // Validate
    if ($fileError !== UPLOAD_ERR_OK) {
        $errors[] = "$fileName: Upload error";
        continue;
    }

    if ($fileSize > $maxFileSize) {
        $errors[] = "$fileName: File too large (max 100MB)";
        continue;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmpName);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        $errors[] = "$fileName: File type not allowed";
        continue;
    }

    // Generate unique ID and filename
    $mediaId = substr(str_replace('-', '', uuid_v4()), 0, 36);
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $safeFileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
    $storedFileName = $mediaId . '_' . $safeFileName . '.' . $ext;
    $storagePath = 'assets/uploads/media/' . $storedFileName;
    $fullPath = $uploadDir . $storedFileName;

    // Move uploaded file
    if (!move_uploaded_file($fileTmpName, $fullPath)) {
        $errors[] = "$fileName: Failed to save file";
        continue;
    }

    // Get dimensions (for images)
    $width = $height = null;
    if (strpos($mimeType, 'image') === 0 && $mimeType !== 'image/svg+xml') {
        $imageInfo = getimagesize($fullPath);
        if ($imageInfo) {
            $width = $imageInfo[0];
            $height = $imageInfo[1];
        }

        // Generate thumbnail
        $thumbPath = $thumbDir . $mediaId . '.jpg';
        generateThumbnail($fullPath, $thumbPath, 300, 300);
    }

    // Get folder_id from POST
    $folderId = $_POST['folder_id'] ?? null;

    // Insert into database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO media_files (
                id, filename, original_filename, mime_type, size, 
                width, height, storage_path, cdn_url, thumb_url, uploaded_by, folder_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $cdnUrl = '/' . $storagePath;
        $thumbUrl = strpos($mimeType, 'image') === 0 ? '/assets/uploads/media/thumbs/' . $mediaId . '.jpg' : null;
        $uploadedBy = $_SESSION['admin_id'] ?? 1;

        $stmt->execute([
            $mediaId,
            $storedFileName,
            $fileName,
            $mimeType,
            $fileSize,
            $width,
            $height,
            $storagePath,
            $cdnUrl,
            $thumbUrl,
            $uploadedBy,
            $folderId
        ]);

        $uploaded[] = [
            'id' => $mediaId,
            'filename' => $storedFileName,
            'mime' => $mimeType,
            'size' => $fileSize,
            'width' => $width,
            'height' => $height,
            'url' => $cdnUrl,
            'thumb_url' => $thumbUrl,
            'status' => 'ready'
        ];

    } catch (PDOException $e) {
        $errors[] = "$fileName: Database error - " . $e->getMessage();
        unlink($fullPath); // Clean up
    }
}

// Generate UUID v4
function uuid_v4() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Generate thumbnail
function generateThumbnail($source, $dest, $maxWidth, $maxHeight) {
    $imageInfo = getimagesize($source);
    if (!$imageInfo) return false;

    list($origWidth, $origHeight, $type) = $imageInfo;

    // Calculate dimensions
    $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
    $newWidth = (int)($origWidth * $ratio);
    $newHeight = (int)($origHeight * $ratio);

    // Create image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($source);
            break;
        case IMAGETYPE_WEBP:
            $srcImage = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }

    if (!$srcImage) return false;

    // Create thumbnail
    $thumbImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($thumbImage, false);
        imagesavealpha($thumbImage, true);
    }

    imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
    imagejpeg($thumbImage, $dest, 85);

    imagedestroy($srcImage);
    imagedestroy($thumbImage);

    return true;
}

echo json_encode([
    'success' => count($uploaded) > 0,
    'uploaded' => $uploaded,
    'errors' => $errors
]);
