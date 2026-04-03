<?php
// admin/upload_blog_image.php - Silent version
error_reporting(0);
ini_set('display_errors', 0);
session_start();

$funcNum = $_GET['CKEditorFuncNum'] ?? 0;

if (!isset($_FILES['upload'])) {
    echo "<script>window.parent.CKEDITOR.tools.callFunction($funcNum, '', 'No file');</script>";
    exit;
}

$file = $_FILES['upload'];
if ($file['error'] != 0) {
    echo "<script>window.parent.CKEDITOR.tools.callFunction($funcNum, '', 'Upload Error');</script>";
    exit;
}

$folder = __DIR__ . '/../assets/uploads/blogs/';
if (!file_exists($folder)) {
    @mkdir($folder, 0755, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$name = time() . '_' . rand(100,999) . '.' . $ext;
$dest = $folder . $name;

if (@move_uploaded_file($file['tmp_name'], $dest)) {
    $url = '/assets/uploads/blogs/' . $name;
    echo "<script>window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', '');</script>";
} else {
    // Return a clean error message in the alert
    echo "<script>window.parent.CKEDITOR.tools.callFunction($funcNum, '', 'Server Permission Error');</script>";
}
?>
