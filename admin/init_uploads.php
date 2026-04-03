<?php
// admin/init_uploads.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$base = __DIR__ . '/../assets/uploads/';
$blogs = __DIR__ . '/../assets/uploads/blogs/';

function fix($path) {
    echo "Checking $path ... ";
    if (!file_exists($path)) {
        if (mkdir($path, 0755, true)) echo "Created. ";
        else echo "Failed to create. ";
    }
    if (chmod($path, 0755)) echo "Permissions set. ";
    else echo "Failed to set permissions. ";
    echo "Writable: " . (is_writable($path) ? "YES" : "NO") . "<br>";
}

fix($base);
fix($blogs);
echo "Done.";
?>
