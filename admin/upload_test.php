<?php
// Simple test - log everything and return success
session_start();

$log = __DIR__ . '/UPLOAD_TEST_LOG.txt';
$data = date('Y-m-d H:i:s') . " - UPLOAD TEST HIT\n";
$data .= "GET: " . json_encode($_GET) . "\n";
$data .= "POST: " . json_encode($_POST) . "\n"; 
$data .= "FILES: " . json_encode($_FILES) . "\n\n";
file_put_contents($log, $data, FILE_APPEND);

// Get funcNum if present
$funcNum = $_GET['CKEditorFuncNum'] ?? $_GET['funcNum'] ?? null;

if ($funcNum) {
    // Return script callback
    echo "<script>";
    echo "window.parent.CKEDITOR.tools.callFunction($funcNum, '/test-image.jpg', 'Test upload successful');";
    echo "</script>";
} else {
    // Return JSON
    header('Content-Type: application/json');
    echo json_encode([
        'uploaded' => 1,
        'fileName' => 'test.jpg',
        'url' => '/test-image.jpg'
    ]);
}
