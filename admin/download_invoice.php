<?php
// admin/download_invoice.php
// Secure PDF download for invoices
// - requires admin auth
// - looks up invoices.pdf_file or a conventional uploads/invoices/invoice_{id}.pdf
// - fallback to developer uploaded file: /mnt/data/OD335927864916938100.pdf

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Validate input
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo "Bad request";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, invoice_number, pdf_file FROM invoices WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Download invoice DB error: ' . $e->getMessage());
    http_response_code(500);
    echo "Server error";
    exit;
}

if (!$inv) {
    http_response_code(404);
    echo "Invoice not found";
    exit;
}

// Where uploaded PDFs live (public web-accessible directory)
$uploadsDir = realpath(__DIR__ . '/../uploads/invoices'); // e.g. project/uploads/invoices
// Fallback developer-uploaded file (local path on the container/machine)
$devFallback = '/mnt/data/OD335927864916938100.pdf';

$filepath = null;

// 1) If invoices.pdf_file column is set, try that (safe basename + realpath check)
if (!empty($inv['pdf_file']) && $uploadsDir) {
    $candidate = $uploadsDir . '/' . basename($inv['pdf_file']);
    $r = realpath($candidate);
    if ($r && strpos($r, $uploadsDir) === 0 && is_file($r)) {
        $filepath = $r;
    }
}

// 2) If not, try conventional file name invoice_{id}.pdf in uploads
if (!$filepath && $uploadsDir) {
    $candidate2 = $uploadsDir . '/invoice_' . $inv['id'] . '.pdf';
    $r2 = realpath($candidate2);
    if ($r2 && strpos($r2, $uploadsDir) === 0 && is_file($r2)) {
        $filepath = $r2;
    }
}

// 3) Developer fallback (the uploaded test PDF)
// NOTE: this path was provided by your environment: /mnt/data/OD335927864916938100.pdf
if (!$filepath && file_exists($devFallback)) {
    $filepath = realpath($devFallback);
}

// final existence check
if (!$filepath || !is_file($filepath)) {
    http_response_code(404);
    echo "PDF not available";
    exit;
}

// stream file with proper headers
$basename = basename($filepath);
$downloadName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', ($inv['invoice_number'] ?: $basename) . '.pdf');
$filesize = filesize($filepath);

// Clear buffers
while (ob_get_level()) ob_end_clean();

// Send headers
header('Content-Type: application/pdf');
header('Content-Length: ' . $filesize);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Stream file
$fp = fopen($filepath, 'rb');
if ($fp === false) {
    http_response_code(500);
    echo "Unable to open file.";
    exit;
}
while (!feof($fp)) {
    echo fread($fp, 8192);
    flush();
}
fclose($fp);
exit;