<?php
// admin/save_product.php
// Handles saving a new product (or update if you extend it).
// Requires: session, includes/db.php which must provide $pdo (PDO instance)

session_start();
require_once __DIR__ . '/../includes/db.php';

// Simple flash helpers (used by add_product.php)
function flash_set($key, $val) { $_SESSION[$key] = $val; }
function flash_get($key) { $v = $_SESSION[$key] ?? null; if (isset($_SESSION[$key])) unset($_SESSION[$key]); return $v; }

/** Basic helpers **/

function slugify($text) {
    // Transliterate
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    // Replace non letter or digits by -
    $text = preg_replace('~[^\\pL\\d]+~u', '-', $text);
    // Lowercase
    $text = strtolower($text);
    // Remove unwanted characters
    $text = preg_replace('~-+~', '-', $text);
    $text = trim($text, '-');
    if ($text === '') {
        $text = 'product-' . substr(bin2hex(random_bytes(6)), 0, 8);
    }
    return $text;
}

function ensure_upload_dir($dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create directory: $dir");
        }
    }
}

/** Validate CSRF token **/
$err = [];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $err[] = "Invalid request method.";
}

$csrf = $_POST['csrf_token'] ?? '';
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    $err[] = "Invalid CSRF token.";
}

/** Collect and validate fields **/
$title = trim($_POST['title'] ?? '');
$short_desc = trim($_POST['short_desc'] ?? '');
$description = trim($_POST['description'] ?? '');
$meta_title = trim($_POST['meta_title'] ?? '');
$meta_description = trim($_POST['meta_description'] ?? '');
$sku = trim($_POST['sku'] ?? '');
$price = $_POST['price'] ?? '';
$compare_price = $_POST['compare_price'] ?? null;
$currency = 'INR';
$category_id = $_POST['category_id'] ?? null;
$stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
$is_published = isset($_POST['is_published']) && ($_POST['is_published'] === '1' || $_POST['is_published'] === 1) ? 1 : 0;

// Basic validations
if ($title === '') $err[] = "Product name is required.";
if ($price === '' || !is_numeric($price) || (float)$price < 0) $err[] = "Valid price is required.";
if (!empty($category_id) && !ctype_digit((string)$category_id)) $category_id = null;

// If errors, save old inputs and redirect back
if (!empty($err)) {
    flash_set('form_errors', $err);
    flash_set('old', $_POST);
    header('Location: add_product.php');
    exit;
}

/** Handle images upload **/
$upload_debug = []; // paths relative to project root to show in add_product.php
$savedFiles = [];   // filenames to store in DB (we'll json_encode this)

$uploadDir = __DIR__ . '/../assets/uploads/products/'; // absolute path
$uploadWebPrefix = '/assets/uploads/products/'; // URL prefix for images
try {
    ensure_upload_dir($uploadDir);
} catch (Exception $e) {
    $err[] = "Unable to prepare upload directory: " . $e->getMessage();
}

// Process uploaded files if any
if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    // Limit to 5 files
    $count = count($_FILES['images']['name']);
    $limit = min(5, $count);
    for ($i = 0; $i < $limit; $i++) {
        $name = $_FILES['images']['name'][$i];
        $tmp  = $_FILES['images']['tmp_name'][$i];
        $errCode = $_FILES['images']['error'][$i];
        $size = $_FILES['images']['size'][$i];

        if ($errCode !== UPLOAD_ERR_OK) continue;
        if (!is_uploaded_file($tmp)) continue;
        if ($size <= 0) continue;

        // Validate mime type (basic)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        if (strpos($mime, 'image/') !== 0) continue;

        // Generate safe filename
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $base = bin2hex(random_bytes(6));
        $filename = $base . ($ext ? '.' . $ext : '');

        $dest = $uploadDir . $filename;

        if (!move_uploaded_file($tmp, $dest)) {
            // try copy as fallback
            if (!@copy($tmp, $dest)) {
                // skip this file
                continue;
            }
        }

        // Optionally you can create thumbnails here - skipped for simplicity

        // store relative path (web)
        $savedFiles[] = $filename;
        $upload_debug[] = 'assets/uploads/products/' . $filename;
    }
}

/** Generate slug and ensure unique **/
$slug = slugify($title);
$baseSlug = $slug;
$counter = 1;
try {
    $stmtChk = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
    while (true) {
        $stmtChk->execute([$slug]);
        $c = (int)$stmtChk->fetchColumn();
        if ($c === 0) break;
        $slug = $baseSlug . '-' . $counter++;
    }
} catch (Exception $e) {
    // if products table missing or error, we still proceed with slug
    // but record the exception for debug
    $upload_debug[] = 'slug-check-error: ' . $e->getMessage();
}

/** Insert into DB **/
try {
    $images_json = !empty($savedFiles) ? json_encode(array_values($savedFiles)) : null;

    $sql = "INSERT INTO products
        (name, sku, slug, short_description, description, price, compare_price, currency, category_id, images, variants, stock, is_active, is_featured, meta_title, meta_description, created_at)
        VALUES
        (:name, :sku, :slug, :short_desc, :description, :price, :compare_price, :currency, :category_id, :images, NULL, :stock, :is_active, 0, :meta_title, :meta_description, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $title,
        ':sku' => $sku ?: null,
        ':slug' => $slug,
        ':short_desc' => $short_desc ?: null,
        ':description' => $description ?: null,
        ':price' => number_format((float)$price, 2, '.', ''),
        ':compare_price' => $compare_price !== null && $compare_price !== '' ? number_format((float)$compare_price, 2, '.', '') : null,
        ':currency' => $currency,
        ':category_id' => $category_id ?: null,
        ':images' => $images_json,
        ':stock' => (int)$stock,
        ':is_active' => (int)$is_published,
        ':meta_title' => $meta_title ?: null,
        ':meta_description' => $meta_description ?: null,
    ]);

    $newId = $pdo->lastInsertId();

    // success flash
    flash_set('success_msg', 'Product created successfully.');
    flash_set('upload_debug', $upload_debug);

    // redirect to edit page so admin can continue editing
    header('Location: products.php');
    exit;

} catch (PDOException $e) {
    // capture DB error and return to form
    $err[] = "Database error: " . $e->getMessage();
    // if slug conflict or missing fields cause errors, admin will see them
    flash_set('form_errors', $err);
    // persist old input (so admin doesn't retype)
    flash_set('old', $_POST);
    flash_set('upload_debug', $upload_debug);
    header('Location: add_product.php');
    exit;
}