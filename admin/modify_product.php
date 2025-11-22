<?php
// admin/save_product.php
// Save handler for create & update product (robust, update when id provided)

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

// small flash helpers
function flash_set($k, $v) { $_SESSION[$k] = $v; }
function redirect($url) { header('Location: ' . $url); exit; }

// only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('products.php');
}

// CSRF check
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log('[save_product] CSRF mismatch');
    flash_set('form_errors', ['Invalid CSRF token.']);
    redirect('products.php');
}

// basic slugify
function slugify($text) {
    $text = trim((string)$text);
    if ($text === '') return bin2hex(random_bytes(4));
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text) ?: bin2hex(random_bytes(4));
}

// upload dir
$uploadDir = __DIR__ . '/../assets/uploads/products/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

// map inputs (these names must match edit_product.php)
$id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
$name = trim((string)($_POST['name'] ?? ''));
$slug = trim((string)($_POST['slug'] ?? ''));
$short_description = trim((string)($_POST['short_description'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$meta_title = trim((string)($_POST['meta_title'] ?? ''));
$meta_description = trim((string)($_POST['meta_description'] ?? ''));
$category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
$stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
$sku = trim((string)($_POST['sku'] ?? ''));
$is_active = (isset($_POST['is_active']) && (string)$_POST['is_active'] === '0') ? 0 : 1;

// validate minimal
$errors = [];
if ($name === '') $errors[] = 'Product name is required.';
if ($price < 0) $errors[] = 'Price must be >= 0.';
if ($stock < 0) $errors[] = 'Stock must be >= 0.';

if ($errors) {
    flash_set('form_errors', $errors);
    flash_set('old', $_POST);
    redirect($id ? "edit_product.php?id={$id}" : 'add_product.php');
}

// fetch existing row (for update)
$existing = null;
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            error_log("[save_product] requested update id {$id} not found");
            flash_set('form_errors', ['Product to update not found.']);
            redirect('products.php');
        }
    } catch (Exception $e) {
        error_log("[save_product] DB fetch error: " . $e->getMessage());
        flash_set('form_errors', ['DB error: ' . $e->getMessage()]);
        redirect('products.php');
    }
}

// generate slug if blank
if ($slug === '') $slug = slugify($name);

// ensure slug unique (exclude current id when updating)
$base = $slug;
$suffix = 1;
while (true) {
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ? AND id != ? LIMIT 1");
        $stmt->execute([$slug, $id]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) break;
    $slug = $base . '-' . $suffix++;
}

// handle images upload
$savedImages = [];
if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
    for ($i=0;$i<count($_FILES['images']['name']);$i++) {
        $orig = $_FILES['images']['name'][$i] ?? '';
        $err = $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        $tmp = $_FILES['images']['tmp_name'][$i] ?? '';
        if (!$orig || $err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) continue;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        if (strpos($mime, 'image/') !== 0) continue;
        $ext = pathinfo($orig, PATHINFO_EXTENSION) ?: 'jpg';
        $ext = preg_replace('/[^a-zA-Z0-9]/','', $ext);
        $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $uploadDir . $filename;
        if (@move_uploaded_file($tmp, $dest)) $savedImages[] = $filename;
    }
}

// build images for DB: merge with existing (update) or set new (create)
$finalImages = [];
if ($existing && !empty($existing['images'])) {
    $oldImgs = [];
    $maybe = @json_decode($existing['images'], true);
    if (is_array($maybe)) $oldImgs = array_values($maybe);
    elseif (strpos($existing['images'], ',') !== false) $oldImgs = array_map('trim', explode(',', $existing['images']));
    else $oldImgs = [$existing['images']];
    $finalImages = array_values(array_unique(array_merge($oldImgs, $savedImages)));
} else {
    $finalImages = $savedImages;
}
$imagesForDb = empty($finalImages) ? '' : json_encode(array_values($finalImages));

// Now run UPDATE or INSERT
try {
    if ($id > 0) {
        $sql = "UPDATE products SET
                    name = :name,
                    slug = :slug,
                    short_description = :short_description,
                    description = :description,
                    meta_title = :meta_title,
                    meta_description = :meta_description,
                    category_id = :category_id,
                    price = :price,
                    stock = :stock,
                    sku = :sku,
                    images = :images,
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'=>$name, ':slug'=>$slug, ':short_description'=>$short_description,
            ':description'=>$description, ':meta_title'=>$meta_title, ':meta_description'=>$meta_description,
            ':category_id'=>$category_id ?: null, ':price'=>$price, ':stock'=>$stock, ':sku'=>$sku,
            ':images'=>$imagesForDb, ':is_active'=>$is_active, ':id'=>$id
        ]);
        flash_set('success_msg', 'Product updated successfully.');
        redirect('products.php');
    } else {
        $sql = "INSERT INTO products
                    (name, slug, short_description, description, meta_title, meta_description, category_id, price, stock, sku, images, is_active, created_at)
                VALUES
                    (:name, :slug, :short_description, :description, :meta_title, :meta_description, :category_id, :price, :stock, :sku, :images, :is_active, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'=>$name, ':slug'=>$slug, ':short_description'=>$short_description,
            ':description'=>$description, ':meta_title'=>$meta_title, ':meta_description'=>$meta_description,
            ':category_id'=>$category_id ?: null, ':price'=>$price, ':stock'=>$stock, ':sku'=>$sku,
            ':images'=>$imagesForDb, ':is_active'=>$is_active
        ]);
        flash_set('success_msg', 'Product created successfully.');
        redirect('products.php');
    }
} catch (Exception $e) {
    // cleanup uploaded files for this request to avoid orphan files
    foreach ($savedImages as $f) @unlink($uploadDir . $f);
    error_log('[save_product] DB error: ' . $e->getMessage());
    flash_set('form_errors', ['DB error: ' . $e->getMessage()]);
    flash_set('old', $_POST);
    redirect($id ? "edit_product.php?id={$id}" : 'add_product.php');
}