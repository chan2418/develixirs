<?php
// admin/handlers/media_upload_url.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$imageUrl = $_POST['image_url'] ?? '';
$folderId = $_POST['folder_id'] ?? null;

if (!$imageUrl) {
    echo json_encode(['success' => false, 'error' => 'URL required']);
    exit;
}

// Validate URL
if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid URL']);
    exit;
}

try {
    // Download file
    $fileContent = @file_get_contents($imageUrl);
    if ($fileContent === false) {
        throw new Exception('Failed to download file from URL');
    }

    // Get file info
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($fileContent);
    
    $allowedMimes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif',
        'video/mp4', 'video/webm'
    ];
    
    if (!in_array($mimeType, $allowedMimes)) {
        throw new Exception('File type not allowed');
    }

    // Generate UUID
    function uuid_v4() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    $mediaId = uuid_v4();
    $ext = match($mimeType) {
        'image/jpeg', 'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        default => 'bin'
    };

    $fileName = $mediaId . '.' . $ext;
    $uploadDir = __DIR__ . '/../../assets/uploads/media/';
    $thumbDir = __DIR__ . '/../../assets/uploads/media/thumbs/';
    
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!is_dir($thumbDir)) mkdir($thumbDir, 0777, true);

    $fullPath = $uploadDir . $fileName;
    file_put_contents($fullPath, $fileContent);

    // Get dimensions
    $width = $height = null;
    if (strpos($mimeType, 'image') === 0) {
        $imageInfo = getimagesize($fullPath);
        if ($imageInfo) {
            $width = $imageInfo[0];
            $height = $imageInfo[1];
        }
    }

    $storagePath = 'assets/uploads/media/' . $fileName;
    $cdnUrl = '/' . $storagePath;
    $thumbUrl = null;

    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO media_files (
            id, filename, original_filename, mime_type, size, 
            width, height, storage_path, cdn_url, thumb_url, uploaded_by, folder_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $uploadedBy = $_SESSION['admin_id'] ?? 1;
    $fileSize = strlen($fileContent);
    $originalFilename = basename(parse_url($imageUrl, PHP_URL_PATH));

    $stmt->execute([
        $mediaId,
        $fileName,
        $originalFilename,
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

    echo json_encode([
        'success' => true,
        'media' => [
            'id' => $mediaId,
            'filename' => $fileName,
            'url' => $cdnUrl
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
