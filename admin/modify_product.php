<?php
// admin/modify_product.php
// Update handler for Edit Product (supports parent + sub category + category_name)

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

// flash + redirect helpers
function flash_set($k, $v) { $_SESSION[$k] = $v; }
function redirect($url) { header('Location: ' . $url); exit; }

// only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('products.php');
}

// CSRF check
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    error_log('[modify_product] CSRF mismatch');
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
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// ============ MAP INPUTS ============

$id   = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    flash_set('form_errors', ['Missing product ID.']);
    redirect('products.php');
}

$name              = trim((string)($_POST['name'] ?? ''));
$slug              = trim((string)($_POST['slug'] ?? ''));
$short_description = trim((string)($_POST['short_description'] ?? ''));
$description       = trim((string)($_POST['description'] ?? ''));
$meta_title        = trim((string)($_POST['meta_title'] ?? ''));
$meta_description  = trim((string)($_POST['meta_description'] ?? ''));

$parent_category_id = isset($_POST['parent_category_id']) && $_POST['parent_category_id'] !== ''
    ? (int)$_POST['parent_category_id']
    : null;

$category_id = isset($_POST['category_id']) && $_POST['category_id'] !== ''
    ? (int)$_POST['category_id']
    : null;

$price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
$stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
$sku   = trim((string)($_POST['sku'] ?? ''));
$is_active = (isset($_POST['is_active']) && (string)$_POST['is_active'] === '0') ? 0 : 1;

// ============ VALIDATION ============

$errors = [];
if ($name === '')   $errors[] = 'Product name is required.';
if ($price < 0)     $errors[] = 'Price must be >= 0.';
if ($stock < 0)     $errors[] = 'Stock must be >= 0.';

if ($errors) {
    flash_set('form_errors', $errors);
    flash_set('old', $_POST);
    redirect("edit_product.php?id={$id}");
}

// ============ FETCH EXISTING PRODUCT ============

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        error_log("[modify_product] requested id {$id} not found");
        flash_set('form_errors', ['Product to update not found.']);
        redirect('products.php');
    }
} catch (Exception $e) {
    error_log("[modify_product] DB fetch error: " . $e->getMessage());
    flash_set('form_errors', ['DB error: ' . $e->getMessage()]);
    redirect('products.php');
}

// ============ CHECK COLUMNS (for category_name) ============

try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM products");
    $productCols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $productCols = [];
}

$hasCategoryName = in_array('category_name', $productCols, true);

// ============ RESOLVE category_name LIKE save_product ============
//
// Logic:
// - Take effective category = subcategory if chosen, else parent
// - If that category has a parent → category_name = parent.name  (Men Care, Baby Care, etc.)
// - Else → category_name = this category's name
// - Also auto-fill parent_category_id from DB if missing

$categoryNameVal = null;
$effectiveCatId  = null;

if (!empty($category_id)) {
    $effectiveCatId = $category_id;
} elseif (!empty($parent_category_id)) {
    $effectiveCatId = $parent_category_id;
}

if ($hasCategoryName && $effectiveCatId !== null) {
    try {
        $stmtCat = $pdo->prepare("
            SELECT c.id,
                   c.name AS cat_name,
                   c.parent_id,
                   p.name AS parent_name
            FROM categories c
            LEFT JOIN categories p ON c.parent_id = p.id
            WHERE c.id = :cid
            LIMIT 1
        ");
        $stmtCat->execute([':cid' => $effectiveCatId]);
        $row = $stmtCat->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if (!empty($row['parent_id'])) {
                // subcategory → use parent as top-level label
                $categoryNameVal = $row['parent_name'];

                // if parent_category_id not set, sync from DB
                if ($parent_category_id === null) {
                    $parent_category_id = (int)$row['parent_id'];
                }
                // category_id should be the subcategory id
                if ($category_id === null) {
                    $category_id = (int)$row['id'];
                }
            } else {
                // top-level category
                $categoryNameVal = $row['cat_name'];
                // if user only selected top-level, keep parent_category_id null and category_id = top-level
                if ($category_id === null) {
                    $category_id = (int)$row['id'];
                }
            }
        }
    } catch (Exception $e) {
        // leave categoryNameVal as null on error
        error_log('[modify_product] category lookup error: ' . $e->getMessage());
    }
}

// ============ SLUG HANDLING ============

if ($slug === '') {
    $slug = slugify($name);
} else {
    $slug = slugify($slug);
}

// ensure slug unique (exclude current id)
$base   = $slug;
$suffix = 1;
while (true) {
    $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ? AND id != ? LIMIT 1");
    $stmt->execute([$slug, $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) break;
    $slug = $base . '-' . $suffix++;
}

// ============ IMAGE UPLOAD (MERGE WITH EXISTING) ============

$savedImages = [];
if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
        $orig = $_FILES['images']['name'][$i] ?? '';
        $err  = $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        $tmp  = $_FILES['images']['tmp_name'][$i] ?? '';

        if (!$orig || $err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) continue;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        if (strpos($mime, 'image/') !== 0) continue;

        $ext = pathinfo($orig, PATHINFO_EXTENSION) ?: 'jpg';
        $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $uploadDir . $filename;

        if (@move_uploaded_file($tmp, $dest)) {
            $savedImages[] = $filename;
        }
    }
}

// merge existing images + new ones
$finalImages = [];
if (!empty($existing['images'])) {
    $oldImgs = [];
    $maybe = @json_decode($existing['images'], true);
    if (is_array($maybe)) {
        $oldImgs = array_values($maybe);
    } elseif (strpos($existing['images'], ',') !== false) {
        $oldImgs = array_map('trim', explode(',', $existing['images']));
    } else {
        $oldImgs = [$existing['images']];
    }
    $finalImages = $oldImgs;
}
if (!empty($savedImages)) {
    $finalImages = array_values(array_unique(array_merge($finalImages, $savedImages)));
}
$imagesForDb = empty($finalImages) ? '' : json_encode($finalImages);

// ============ UPDATE DB ============

try {
    $sql = "UPDATE products SET
                name = :name,
                slug = :slug,
                short_description = :short_description,
                description = :description,
                meta_title = :meta_title,
                meta_description = :meta_description,
                parent_category_id = :parent_category_id,
                category_id = :category_id,
                category_name = :category_name,
                price = :price,
                stock = :stock,
                sku = :sku,
                images = :images,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'               => $name,
        ':slug'               => $slug,
        ':short_description'  => $short_description,
        ':description'        => $description,
        ':meta_title'         => $meta_title,
        ':meta_description'   => $meta_description,
        ':parent_category_id' => $parent_category_id ?: null,
        ':category_id'        => $category_id ?: null,
        ':category_name'      => $categoryNameVal, // 👈 this is what product.php uses
        ':price'              => $price,
        ':stock'              => $stock,
        ':sku'                => $sku,
        ':images'             => $imagesForDb,
        ':is_active'          => $is_active,
        ':id'                 => $id,
    ]);

    flash_set('success_msg', 'Product updated successfully.');
    redirect('products.php');

} catch (Exception $e) {
    // optional: delete newly uploaded files if error
    foreach ($savedImages as $f) {
        @unlink($uploadDir . $f);
    }
    error_log('[modify_product] DB error: ' . $e->getMessage());
    flash_set('form_errors', ['DB error: ' . $e->getMessage()]);
    flash_set('old', $_POST);
    redirect("edit_product.php?id={$id}");
}