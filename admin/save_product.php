<?php
// admin/save_product.php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Simple flash helpers (used by add_product.php)
function flash_set($key, $val) { $_SESSION[$key] = $val; }
function flash_get($key) { $v = $_SESSION[$key] ?? null; if (isset($_SESSION[$key])) unset($_SESSION[$key]); return $v; }

/** Basic helpers **/

function slugify($text) {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^\\pL\\d]+~u', '-', $text);
    $text = strtolower($text);
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

$err = [];

/** Validate request + CSRF **/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $err[] = "Invalid request method.";
}

$csrf = $_POST['csrf_token'] ?? '';
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    $err[] = "Invalid CSRF token.";
}

/** Collect and validate fields **/
$title            = trim($_POST['title'] ?? '');
$short_desc       = trim($_POST['short_desc'] ?? '');
$description      = trim($_POST['description'] ?? '');
$meta_title       = trim($_POST['meta_title'] ?? '');
$meta_description = trim($_POST['meta_description'] ?? '');
$sku              = trim($_POST['sku'] ?? '');
$price            = $_POST['price'] ?? '';
$compare_price    = $_POST['compare_price'] ?? null; // may not exist in table, we handle later
$currency         = 'INR';
$category_id      = $_POST['category_id'] ?? null;
$stock            = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
$is_published     = (isset($_POST['is_published']) && ($_POST['is_published'] === '1' || $_POST['is_published'] === 1)) ? 1 : 0;

/** New: collect tags (multi-select) **/
$tagIdsRaw = $_POST['tags'] ?? [];
if (!is_array($tagIdsRaw)) {
    $tagIdsRaw = [$tagIdsRaw];
}
$tagIds = [];
foreach ($tagIdsRaw as $tid) {
    $tid = trim((string)$tid);
    if ($tid !== '' && ctype_digit($tid)) {
        $tagIds[] = (int)$tid;
    }
}
$tagIds = array_values(array_unique($tagIds)); // remove duplicates

// Basic validations
if ($title === '') {
    $err[] = "Product name is required.";
}
if ($price === '' || !is_numeric($price) || (float)$price < 0) {
    $err[] = "Valid price is required.";
}
if (!empty($category_id) && !ctype_digit((string)$category_id)) {
    $category_id = null;
}

if (!empty($err)) {
    flash_set('form_errors', $err);
    flash_set('old', $_POST); // includes tags[]
    header('Location: add_product.php');
    exit;
}

/** Figure out which columns exist in products table **/
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM products");
    $productCols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // If this fails, we're in serious trouble anyway
    $productCols = [];
    $err[] = "Unable to read products table structure: " . $e->getMessage();
    flash_set('form_errors', $err);
    flash_set('old', $_POST);
    header('Location: add_product.php');
    exit;
}

$hasSlug          = in_array('slug', $productCols, true);
$hasShortDesc     = in_array('short_description', $productCols, true);
$hasComparePrice  = in_array('compare_price', $productCols, true);
$hasCurrency      = in_array('currency', $productCols, true);
$hasCategoryId    = in_array('category_id', $productCols, true);
$hasImages        = in_array('images', $productCols, true);
$hasVariants      = in_array('variants', $productCols, true);
$hasStock         = in_array('stock', $productCols, true);
$hasIsActive      = in_array('is_active', $productCols, true);
$hasIsFeatured    = in_array('is_featured', $productCols, true);
$hasMetaTitle     = in_array('meta_title', $productCols, true);
$hasMetaDesc      = in_array('meta_description', $productCols, true);
$hasCreatedAt     = in_array('created_at', $productCols, true);
$hasSku           = in_array('sku', $productCols, true);
$hasName          = in_array('name', $productCols, true);
$hasDescription   = in_array('description', $productCols, true);

/** Handle images upload **/
$upload_debug = [];
$savedFiles   = [];

$uploadDir       = __DIR__ . '/../assets/uploads/products/';
$uploadWebPrefix = '/assets/uploads/products/';

try {
    ensure_upload_dir($uploadDir);
} catch (Exception $e) {
    $err[] = "Unable to prepare upload directory: " . $e->getMessage();
}

if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $count = count($_FILES['images']['name']);
    $limit = min(5, $count);
    for ($i = 0; $i < $limit; $i++) {
        $name    = $_FILES['images']['name'][$i];
        $tmp     = $_FILES['images']['tmp_name'][$i];
        $errCode = $_FILES['images']['error'][$i];
        $size    = $_FILES['images']['size'][$i];

        if ($errCode !== UPLOAD_ERR_OK) continue;
        if (!is_uploaded_file($tmp)) continue;
        if ($size <= 0) continue;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        if (strpos($mime, 'image/') !== 0) continue;

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $base = bin2hex(random_bytes(6));
        $filename = $base . ($ext ? '.' . $ext : '');

        $dest = $uploadDir . $filename;

        if (!move_uploaded_file($tmp, $dest)) {
            if (!@copy($tmp, $dest)) {
                continue;
            }
        }

        $savedFiles[]   = $filename;
        $upload_debug[] = 'assets/uploads/products/' . $filename;
    }
}

/** Generate slug only if products table has slug column **/
$slug = null;
if ($hasSlug) {
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
        $upload_debug[] = 'slug-check-error: ' . $e->getMessage();
    }
}

/** Build INSERT dynamically based on existing columns **/
try {
    $images_json = (!empty($savedFiles) && $hasImages)
        ? json_encode(array_values($savedFiles))
        : null;

    $fields       = [];
    $placeholders = [];
    $bind         = [];

    // Helper to conditionally add a field
    $addField = function($col, $ph, $val) use (&$fields, &$placeholders, &$bind, $productCols) {
        if (in_array($col, $productCols, true)) {
            $fields[]       = $col;
            $placeholders[] = $ph;
            $bind[$ph]      = $val;
        }
    };

    // REQUIRED-ish fields
    if ($hasName) {
        $addField('name', ':name', $title);
    }
    if ($hasSku && $sku !== '') {
        $addField('sku', ':sku', $sku);
    }
    if ($hasSlug && $slug !== null) {
        $addField('slug', ':slug', $slug);
    }
    if ($hasShortDesc) {
        $addField('short_description', ':short_desc', $short_desc ?: null);
    }
    if ($hasDescription) {
        $addField('description', ':description', $description ?: null);
    }
    if (in_array('price', $productCols, true)) {
        $addField('price', ':price', number_format((float)$price, 2, '.', ''));
    }
    if ($hasComparePrice) {
        $addField(
            'compare_price',
            ':compare_price',
            $compare_price !== null && $compare_price !== ''
                ? number_format((float)$compare_price, 2, '.', '')
                : null
        );
    }
    if ($hasCurrency) {
        $addField('currency', ':currency', $currency);
    }
    if ($hasCategoryId) {
        $addField('category_id', ':category_id', $category_id ?: null);
    }
    if ($hasImages) {
        $addField('images', ':images', $images_json);
    }
    if ($hasVariants) {
        $addField('variants', ':variants', null); // not used yet
    }
    if ($hasStock) {
        $addField('stock', ':stock', (int)$stock);
    }
    if ($hasIsActive) {
        $addField('is_active', ':is_active', (int)$is_published);
    }
    if ($hasIsFeatured) {
        $addField('is_featured', ':is_featured', 0);
    }
    if ($hasMetaTitle) {
        $addField('meta_title', ':meta_title', $meta_title ?: null);
    }
    if ($hasMetaDesc) {
        $addField('meta_description', ':meta_description', $meta_description ?: null);
    }
    if ($hasCreatedAt) {
        $addField('created_at', ':created_at', date('Y-m-d H:i:s'));
    }

    // Safety: ensure we at least have name and price
    if (empty($fields)) {
        throw new RuntimeException("No matching columns found in products table for insert.");
    }

    $sql = "INSERT INTO products (" . implode(',', $fields) . ")
            VALUES (" . implode(',', $placeholders) . ")";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($bind);

    $newId = (int)$pdo->lastInsertId();

    /** ================== INSERT PRODUCT TAGS ================== */
    if (!empty($tagIds) && $newId > 0) {
        try {
            // Check if product_tags table exists
            $hasProductTags = false;
            $checkStmt = $pdo->query("SHOW TABLES LIKE 'product_tags'");
            if ($checkStmt && $checkStmt->rowCount() > 0) {
                $hasProductTags = true;
            }

            if ($hasProductTags) {
                $stmtTag = $pdo->prepare("
                    INSERT INTO product_tags (product_id, tag_id)
                    VALUES (:pid, :tid)
                ");

                foreach ($tagIds as $tid) {
                    $stmtTag->execute([
                        ':pid' => $newId,
                        ':tid' => $tid,
                    ]);
                }
            } else {
                $upload_debug[] = 'product_tags table not found, tags not saved.';
            }
        } catch (Exception $e) {
            // Don't block product creation if tags fail, just log debug
            $upload_debug[] = 'tag-insert-error: ' . $e->getMessage();
        }
    }

    /** ========================================================= */

    flash_set('success_msg', 'Product created successfully.');
    flash_set('upload_debug', $upload_debug);

    header('Location: products.php');
    exit;

} catch (PDOException $e) {
    $err[] = "Database error: " . $e->getMessage();
    flash_set('form_errors', $err);
    flash_set('old', $_POST);
    flash_set('upload_debug', $upload_debug);
    header('Location: add_product.php');
    exit;
} catch (Exception $e) {
    $err[] = "Error: " . $e->getMessage();
    flash_set('form_errors', $err);
    flash_set('old', $_POST);
    flash_set('upload_debug', $upload_debug);
    header('Location: add_product.php');
    exit;
}