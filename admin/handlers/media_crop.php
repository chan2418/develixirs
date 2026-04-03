<?php
// admin/handlers/media_crop.php
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$mediaId = $_POST['media_id'] ?? '';
$x = (int)($_POST['x'] ?? 0);
$y = (int)($_POST['y'] ?? 0);
$width = (int)($_POST['width'] ?? 0);
$height = (int)($_POST['height'] ?? 0);

if (!$mediaId || $width <= 0 || $height <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    // Get media info
    $stmt = $pdo->prepare("SELECT * FROM media_files WHERE id = ?");
    $stmt->execute([$mediaId]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$media) {
        throw new Exception('Media not found');
    }

    $uploadDir = __DIR__ . '/../../assets/uploads/media/';
    $sourcePath = $uploadDir . $media['filename'];

    if (!file_exists($sourcePath)) {
        throw new Exception('Source file not found');
    }

    // Create image resource based on type
    $mimeType = $media['mime_type'];
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($sourcePath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            throw new Exception('Unsupported image type for cropping');
    }

    if (!$source) {
        throw new Exception('Failed to load image');
    }

    // Create cropped image
    $cropped = imagecreatetruecolor($width, $height);
    
    // Preserve transparency for PNG
    if ($mimeType === 'image/png') {
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        $transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
        imagefill($cropped, 0, 0, $transparent);
    }

    imagecopyresampled($cropped, $source, 0, 0, $x, $y, $width, $height, $width, $height);

    // Save cropped image (overwrite original)
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            imagejpeg($cropped, $sourcePath, 90);
            break;
        case 'image/png':
            imagepng($cropped, $sourcePath, 9);
            break;
        case 'image/webp':
            imagewebp($cropped, $sourcePath, 90);
            break;
    }

    imagedestroy($source);
    imagedestroy($cropped);

    // Update database dimensions
    $stmt = $pdo->prepare("UPDATE media_files SET width = ?, height = ? WHERE id = ?");
    $stmt->execute([$width, $height, $mediaId]);

    echo json_encode([
        'success' => true,
        'message' => 'Image cropped successfully',
        'dimensions' => ['width' => $width, 'height' => $height]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
