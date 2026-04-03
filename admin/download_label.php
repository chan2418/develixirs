<?php
ob_start();
// admin/download_label.php
// Streams a shipment label PDF for download (no layout/header/footer).

require_once __DIR__ . '/_auth.php';     // auth only (should not output HTML)
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo 'Invalid request';
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT label_file FROM shipments WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Download label DB error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Server error';
    exit;
}

// Preferred path: uploaded file name in your uploads folder (publically served location).
$uploadsDirWeb = '/uploads/labels/';          // web-accessible folder (adjust if different)
$uploadsDirFs  = __DIR__ . '/../uploads/labels/'; // server filesystem path (adjust)

// Developer fallback (the file you uploaded in the environment)
$developerFallback = '/mnt/data/OD335927864916938100.pdf';

// Determine real file path to serve
$labelFile = $row['label_file'] ?? null;
$fsPath = null;

if ($labelFile) {
    // try common uploads path
    $candidate = rtrim($uploadsDirFs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $labelFile;
    if (file_exists($candidate) && is_readable($candidate)) {
        $fsPath = $candidate;
    }
}

// if no uploaded label, try a developer/test fallback (local path)
if (!$fsPath && file_exists($developerFallback) && is_readable($developerFallback)) {
    $fsPath = $developerFallback;
}

if (!$fsPath) {
    // Fallback: If no uploaded file found, generate one on the fly
    header("Location: generate_label_pdf.php?id={$id}");
    exit;
}

// Force download: send proper headers and stream file (no other output)
if (ob_get_level()) ob_end_clean();

$basename = basename($fsPath);
$filesize = filesize($fsPath);

// Security checks: ensure file is a PDF (basic)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $fsPath);
finfo_close($finfo);

if ($mime !== 'application/pdf') {
    // still allow but set application/octet-stream if not PDF
    $mime = 'application/octet-stream';
}

// Headers
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $basename) . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . $filesize);
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Stream the file
$fp = fopen($fsPath, 'rb');
if ($fp === false) {
    http_response_code(500);
    echo 'Unable to read file';
    exit;
}

while (!feof($fp)) {
    echo fread($fp, 8192);
    flush();
}
fclose($fp);
exit;