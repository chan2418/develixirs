<?php
// admin/save_product.php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/image_compressor.php';



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
$hsn              = trim($_POST['hsn'] ?? ''); // NEW
$price            = $_POST['price'] ?? '';

// Smart Price Fallback: If main price is empty, try to use the first custom variant's price
if (($price === '' || $price === null) && !empty($_POST['variants']) && is_array($_POST['variants'])) {
    foreach ($_POST['variants'] as $v) {
        // Only use price from Custom variants (where price is set)
        if (isset($v['price']) && is_numeric($v['price']) && (!isset($v['type']) || $v['type'] !== 'linked')) {
            $price = $v['price'];
            break; 
        }
    }
}
$compare_price    = $_POST['compare_price'] ?? null; // may not exist in table, we handle later
$currency         = 'INR';

// NEW: main + sub category
$parent_category_id = $_POST['parent_category_id'] ?? null;
$category_id        = $_POST['category_id'] ?? null;

$stock        = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
$is_published = (isset($_POST['is_published']) && ($_POST['is_published'] === '1' || $_POST['is_published'] === 1)) ? 1 : 0;

// SEO Keywords (hidden tags)
$seo_keywords = trim($_POST['seo_keywords'] ?? '');

/** Collect tags (comma-separated text input) **/
$tagIds = [];
$tagsInput = trim($_POST['tags_input'] ?? '');

// DEBUG: Log what we received


if ($tagsInput !== '') {
    // Process comma-separated tags
    $tagNames = array_filter(array_map('trim', explode(',', $tagsInput)));

    
    if (!empty($tagNames)) {
        // Pre-statements to check/create tags
        $stmtCheckTag = $pdo->prepare("SELECT id FROM tags WHERE name = ? LIMIT 1");
        $stmtCreateTag = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
        
        foreach ($tagNames as $tagName) {
            $stmtCheckTag->execute([$tagName]);
            $tid = $stmtCheckTag->fetchColumn();
            
            if (!$tid) {
                // Create new tag
                $tslug = slugify($tagName);
                try {
                    $stmtCreateTag->execute([$tagName, $tslug]);
                    $tid = $pdo->lastInsertId();

                } catch (Exception $e) {
                    // Fallback for unique constraint on slug
                    $tslug .= '-' . time();
                    try {
                        $stmtCreateTag->execute([$tagName, $tslug]);
                        $tid = $pdo->lastInsertId(); 

                    } catch (Exception $ex) {

                        continue; // skip if still fails
                    }
                }
            } else {

            }
            
            if ($tid > 0) {
                $tagIds[] = (int)$tid;
            }
        }
    }
}

// Verify legacy array selection if present
$tagIdsRaw = $_POST['tags'] ?? [];
if (!is_array($tagIdsRaw)) {
    $tagIdsRaw = [$tagIdsRaw];
}
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

// validate main & sub category as numeric (if provided)
if (!empty($parent_category_id) && !ctype_digit((string)$parent_category_id)) {
    $parent_category_id = null;
}
if (!empty($category_id) && !ctype_digit((string)$category_id)) {
    $category_id = null;
}

// Decide final category_id to save: prefer sub category, else main
$category_id_final = null;
if (!empty($category_id)) {
    $category_id_final = (int)$category_id;
} elseif (!empty($parent_category_id)) {
    $category_id_final = (int)$parent_category_id;
}

// normalize parent_category_id as int or null
$parent_category_id_final = !empty($parent_category_id) ? (int)$parent_category_id : null;

if (!empty($err)) {
    // Return error as text for AJAX
    echo "Error:Result: " . implode("\n", $err);
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
    echo "Error: " . implode("\n", $err);
    exit;
}

$hasSlug            = in_array('slug', $productCols, true);
$hasShortDesc       = in_array('short_description', $productCols, true);
$hasComparePrice    = in_array('compare_price', $productCols, true);
$hasCurrency        = in_array('currency', $productCols, true);
$hasCategoryId      = in_array('category_id', $productCols, true);
$hasParentCategory  = in_array('parent_category_id', $productCols, true); // NEW
$hasImages          = in_array('images', $productCols, true);
$hasCategoryName    = in_array('category_name', $productCols, true);   // 👈 category_name support
$hasVariants        = in_array('variants', $productCols, true);
$hasStock           = in_array('stock', $productCols, true);
$hasIsActive        = in_array('is_active', $productCols, true);
$hasIsFeatured      = in_array('is_featured', $productCols, true);
$hasMetaTitle       = in_array('meta_title', $productCols, true);
$hasMetaDesc        = in_array('meta_description', $productCols, true);
$hasSeoKeywords     = in_array('seo_keywords', $productCols, true); // Hidden SEO tags
$hasCreatedAt       = in_array('created_at', $productCols, true);
$hasSku             = in_array('sku', $productCols, true);
$hasHsn             = in_array('hsn', $productCols, true); // NEW
$hasName            = in_array('name', $productCols, true);
$hasDescription     = in_array('description', $productCols, true);
$hasConcernId       = in_array('concern_id', $productCols, true); // NEW
$hasSeasonalId      = in_array('seasonal_id', $productCols, true); // NEW: Seasonal

/** 🔹 Resolve category_name from categories table (parent-level name like "Men Care") **/
$categoryNameVal = null;

if ($hasCategoryName && $category_id_final !== null) {
    try {
        $stmtCat = $pdo->prepare("
            SELECT c.name AS cat_name,
                   c.parent_id,
                   p.name AS parent_name
            FROM categories c
            LEFT JOIN categories p ON c.parent_id = p.id
            WHERE c.id = :cid
            LIMIT 1
        ");
        $stmtCat->execute([':cid' => $category_id_final]);
        $row = $stmtCat->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // If category has a parent → use parent name as category_name (Men Care, Baby Care etc.)
            if (!empty($row['parent_id'])) {
                $categoryNameVal = $row['parent_name'];
                // If parent_category_id not already set, sync it from DB
                if ($parent_category_id_final === null) {
                    $parent_category_id_final = (int)$row['parent_id'];
                }
            } else {
                // Top-level category: use its own name
                $categoryNameVal = $row['cat_name'];
            }
        }
    } catch (Exception $e) {
        // If it fails, just leave categoryNameVal as null
    }
}

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
    $limit = min(10, $count);
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
        
        $isImage = strpos($mime, 'image/') === 0;
        $isVideo = strpos($mime, 'video/') === 0;
        
        if (!$isImage && !$isVideo) continue;

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $base = bin2hex(random_bytes(6));
        $filename = $base . ($ext ? '.' . $ext : '');

        $dest = $uploadDir . $filename;

        if ($isImage) {
            // Compress and save image
            $result = compressImage($tmp, $dest);
            if (!$result['success']) {
                if (!@copy($tmp, $dest)) {
                    continue;
                }
            }
        } else {
            // Move video directly (no compression)
            if (!@copy($tmp, $dest)) {
                continue;
            }
        }

        $savedFiles[]   = $filename;
        $upload_debug[] = 'assets/uploads/products/' . $filename;
    }
}

// Handle Images from Media Library (Legacy)
if (!empty($_POST['images_from_media']) && is_array($_POST['images_from_media'])) {
    foreach ($_POST['images_from_media'] as $mediaPath) {
        if (!empty($mediaPath)) {
            $savedFiles[] = $mediaPath;
            $upload_debug[] = 'media-library-linked: ' . $mediaPath;
        }
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
    if ($hasHsn && $hsn !== '') {
        $addField('hsn', ':hsn', $hsn);
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
    // NEW: ingredients and how_to_use
    if (in_array('ingredients', $productCols, true)) {
        $addField('ingredients', ':ingredients', trim($_POST['ingredients'] ?? '') ?: null);
    }
    if (in_array('how_to_use', $productCols, true)) {
        $addField('how_to_use', ':how_to_use', trim($_POST['how_to_use'] ?? '') ?: null);
    }
    if (in_array('price', $productCols, true)) {
        $addField('price', ':price', number_format((float)$price, 2, '.', ''));
    }
    
    // NEW: Save Compare Price & Discount
    $compare_price = $_POST['compare_price'] ?? null;
    $discount_percent = $_POST['discount_percent'] ?? null;
    $gst_rate = $_POST['gst_rate'] ?? null;
    
    if (in_array('compare_price', $productCols, true)) {
        $addField(
            'compare_price',
            ':compare_price',
            ($compare_price !== null && $compare_price !== '') ? number_format((float)$compare_price, 2, '.', '') : null
        );
    }
    if (in_array('discount_percent', $productCols, true)) {
        $addField(
            'discount_percent',
            ':discount_percent',
            ($discount_percent !== null && $discount_percent !== '') ? number_format((float)$discount_percent, 2, '.', '') : null
        );
    }
    // NEW: Save GST Rate
    if (in_array('gst_rate', $productCols, true)) {
        $addField(
            'gst_rate',
            ':gst_rate',
            ($gst_rate !== null && $gst_rate !== '') ? number_format((float)$gst_rate, 2, '.', '') : '0.00'
        );
    }

    if ($hasCurrency) {
        $addField('currency', ':currency', 'INR');
    }
    // Variant Label & Main Variant Name
    if (in_array('variant_label', $productCols, true)) {
        $addField('variant_label', ':variant_label', trim($_POST['variant_label'] ?? 'Size'));
    }
    if (in_array('main_variant_name', $productCols, true)) {
        $addField('main_variant_name', ':main_variant_name', trim($_POST['main_variant_name'] ?? '') ?: null);
    }

    // NEW: save main + sub category (if columns exist)
    // Note: DB uses 'cat_id', not 'category_id'
    $hasCatId = in_array('cat_id', $productCols, true);
    
    if ($hasParentCategory) {
        $addField('parent_category_id', ':parent_category_id', $parent_category_id_final ?: null);
    }
    
    if ($hasCatId) {
        $addField('cat_id', ':cat_id', $category_id_final ?: null);
    } elseif ($hasCategoryId) {
        // Fallback if schema changes back
        $addField('category_id', ':category_id', $category_id_final ?: null);
    }

    // 🔹 NEW: save category_name (top-level name like "Men Care") if column exists
    if ($hasCategoryName) {
        $addField('category_name', ':category_name', $categoryNameVal);
    }

    if ($hasConcernId) {
        $concern_id_val = !empty($_POST['concern_id']) ? (int)$_POST['concern_id'] : null;
        $addField('concern_id', ':concern_id', $concern_id_val);
    }

    if ($hasSeasonalId) {
        $seasonal_id_val = !empty($_POST['seasonal_id']) ? (int)$_POST['seasonal_id'] : null;
        $addField('seasonal_id', ':seasonal_id', $seasonal_id_val);
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
    if ($hasSeoKeywords) {
        $addField('seo_keywords', ':seo_keywords', $seo_keywords ?: null);
    }
    // NEW: Save Product Label
    if (in_array('label_id', $productCols, true)) {
        $label_id = !empty($_POST['label_id']) ? (int)$_POST['label_id'] : null;
        $addField('label_id', ':label_id', $label_id);
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
        error_log('DEBUG save_product.php - Attempting to insert ' . count($tagIds) . ' tags for product ID: ' . $newId);
        try {
            $stmtTag = $pdo->prepare("
                INSERT IGNORE INTO product_tags (product_id, tag_id)
                VALUES (:pid, :tid)
            ");

            foreach ($tagIds as $tid) {
                $stmtTag->execute([
                    ':pid' => $newId,
                    ':tid' => $tid,
                ]);
                error_log('DEBUG save_product.php - Inserted tag ID ' . $tid . ' for product ' . $newId);
            }
            error_log('DEBUG save_product.php - Successfully inserted all tags');
        } catch (Exception $e) {
            // Log error but don't block product creation
            error_log('ERROR save_product.php - Tag insert error: ' . $e->getMessage());
            $upload_debug[] = 'tag-insert-error: ' . $e->getMessage();
        }
    } else {
        error_log('DEBUG save_product.php - No tags to insert. tagIds: ' . print_r($tagIds, true) . ', newId: ' . $newId);
    }

    /** ================== INSERT PRODUCT VARIANTS ================== */
    $variantsRaw = $_POST['variants'] ?? [];
    if (!empty($variantsRaw) && is_array($variantsRaw) && $newId > 0) {
        try {
            // Check for 'hsn' column existence to prevent "Invalid parameter number" errors
            $hsnExists = false;
            try {
                $chkHsn = $pdo->query("SHOW COLUMNS FROM product_variants LIKE 'hsn'");
                if ($chkHsn && $chkHsn->rowCount() > 0) {
                    $hsnExists = true;
                }
            } catch (Exception $e) { $hsnExists = false; }

            // Prepare Dynamic SQL
            $sqlFields = [
                'product_id', 'variant_name', 'type', 'linked_product_id', 
                'price', 'compare_price', 'discount_percent', 'stock', 'sku', 
                'custom_title', 'custom_description', 'short_description', 
                'ingredients', 'how_to_use', 'meta_title', 'meta_description', 
                'images', 'image', 'is_active'
            ];
            $sqlValues = [
                ':pid', ':name', ':type', ':linked_product_id', 
                ':price', ':compare_price', ':discount_percent', ':stock', ':sku', 
                ':custom_title', ':custom_desc', ':short_desc', 
                ':ingredients', ':how_to_use', ':meta_title', ':meta_desc', 
                ':images', ':image', '1'
            ];

            if ($hsnExists) {
                $sqlFields[] = 'hsn';
                $sqlValues[] = ':hsn';
            }

            $sqlInsertVar = "INSERT INTO product_variants (" . implode(', ', $sqlFields) . ") VALUES (" . implode(', ', $sqlValues) . ")";
            $stmtVar = $pdo->prepare($sqlInsertVar);
            
            // Prepare variant FAQ statement
            $stmtVarFaq = $pdo->prepare("
                INSERT INTO variant_faqs (variant_id, question, answer)
                VALUES (:vid, :question, :answer)
            ");
            
            error_log("=== SAVE_PRODUCT.PHP: Processing " . count($variantsRaw) . " variants for product ID: {$newId} ===");
            error_log("HSN Column Exists: " . ($hsnExists ? 'YES' : 'NO'));
            
            foreach ($variantsRaw as $idx => $v) {
                error_log("--- Processing variant index {$idx} ---");
                
                $vName  = trim($v['name'] ?? '');
                $vType  = trim($v['type'] ?? 'custom');
                $vLinkedProductId = !empty($v['linked_product_id']) ? (int)$v['linked_product_id'] : null;
                
                if ($vName === '') continue;
                
                // Set defaults based on type
                if ($vType === 'linked') {
                    $vPrice = 0.00;
                    $vComparePrice = null;
                    $vDiscountPercent = null;
                    $vStock = 0; 
                    $vSku = '';
                    $vHsn = trim($v['hsn'] ?? '') ?: null; // Capture HSN even for Linked
                    $vCustomTitle = null;
                    $vCustomDesc = null;
                    $vShortDesc = null;
                    $vIngredients = null;
                    $vHowToUse = null;
                    $vMetaTitle = null;
                    $vMetaDesc = null;
                    $variantImages = [];
                } else {
                    $vPrice = (float)($v['price'] ?? 0);
                    $vComparePrice = isset($v['compare_price']) && $v['compare_price'] !== '' ? (float)$v['compare_price'] : null;
                    $vDiscountPercent = isset($v['discount_percent']) && $v['discount_percent'] !== '' ? (float)$v['discount_percent'] : null;
                    $vStock = (int)($v['stock'] ?? 0);
                    $vSku   = trim($v['sku'] ?? '');
                    $vHsn   = trim($v['hsn'] ?? '') ?: null; 
                    $vCustomTitle = trim($v['custom_title'] ?? '') ?: null;
                    $vCustomDesc = trim($v['custom_description'] ?? '') ?: null;
                    $vShortDesc = trim($v['short_description'] ?? '') ?: null;
                    $vIngredients = trim($v['ingredients'] ?? '') ?: null;
                    $vHowToUse = trim($v['how_to_use'] ?? '') ?: null;
                    $vMetaTitle = trim($v['meta_title'] ?? '') ?: null;
                    $vMetaDesc = trim($v['meta_description'] ?? '') ?: null;
                    
                    // Handle Images
                    $variantImages = [];
                    if (!empty($_FILES['variants']['name'][$idx]['images'])) {
                        $fileCount = count($_FILES['variants']['name'][$idx]['images']);
                        for ($i = 0; $i < min($fileCount, 10); $i++) {
                            $fName = $_FILES['variants']['name'][$idx]['images'][$i] ?? '';
                            $fTmp  = $_FILES['variants']['tmp_name'][$idx]['images'][$i] ?? '';
                            $fErr  = $_FILES['variants']['error'][$idx]['images'][$i] ?? UPLOAD_ERR_NO_FILE;
    
                            if ($fErr === UPLOAD_ERR_OK && is_uploaded_file($fTmp)) {
                                $ext = pathinfo($fName, PATHINFO_EXTENSION) ?: 'jpg';
                                $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
                                $newName = 'var_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                                $dest = $uploadDir . $newName;
                                if (compressImage($fTmp, $dest)['success']) {
                                    $variantImages[] = $newName;
                                }
                            }
                        }
                    }
                }

                $imagesJson = !empty($variantImages) ? json_encode($variantImages) : null;
                $legacyImage = !empty($variantImages) ? $variantImages[0] : null;

                // Bind Parameters
                $params = [
                    ':pid'          => $newId,
                    ':name'         => $vName,
                    ':type'         => $vType,
                    ':linked_product_id' => $vLinkedProductId,
                    ':price'        => $vPrice,
                    ':compare_price' => $vComparePrice,
                    ':discount_percent' => $vDiscountPercent,
                    ':stock'        => $vStock,
                    ':sku'          => $vSku,
                    ':custom_title' => $vCustomTitle,
                    ':custom_desc'  => $vCustomDesc,
                    ':short_desc'   => $vShortDesc,
                    ':ingredients'  => $vIngredients,
                    ':how_to_use'   => $vHowToUse,
                    ':meta_title'   => $vMetaTitle,
                    ':meta_desc'    => $vMetaDesc,
                    ':images'       => $imagesJson,
                    ':image'        => $legacyImage
                ];

                if ($hsnExists) {
                    $params[':hsn'] = $vHsn;
                }

                try {
                    $stmtVar->execute($params);
                    $variantId = (int)$pdo->lastInsertId();

                } catch (PDOException $e) {

                    throw $e;
                }
                
                // Insert variant FAQs
                if (!empty($v['faqs']) && is_array($v['faqs'])) {
                    foreach ($v['faqs'] as $faq) {
                        $q = trim($faq['question'] ?? '');
                        $a = trim($faq['answer'] ?? '');
                        if ($q && $a) {
                            $stmtVarFaq->execute([
                                ':vid' => $variantId,
                                ':question' => $q,
                                ':answer' => $a
                            ]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $upload_debug[] = 'variant-insert-error: ' . $e->getMessage();
        }
    }

    /** ================== INSERT PRODUCT FAQS ================== */
    $faqsRaw = $_POST['faqs'] ?? [];
    if (!empty($faqsRaw) && is_array($faqsRaw) && $newId > 0) {
        try {
            $stmtFaq = $pdo->prepare("
                INSERT INTO product_faqs (product_id, question, answer)
                VALUES (:pid, :question, :answer)
            ");

            foreach ($faqsRaw as $f) {
                $q = trim($f['question'] ?? '');
                $a = trim($f['answer'] ?? '');

                if ($q === '' || $a === '') continue;

                $stmtFaq->execute([
                    ':pid'      => $newId,
                    ':question' => $q,
                    ':answer'   => $a,
                ]);
            }
        } catch (Exception $e) {
            $upload_debug[] = 'faq-insert-error: ' . $e->getMessage();
        }
    }
    /** ========================================================= */

    // ==== Related Products ====
    if (!empty($_POST['related_products']) && is_array($_POST['related_products'])) {
        try {
            $insertRelated = $pdo->prepare("
                INSERT INTO product_relations (product_id, related_product_id)
                VALUES (?, ?)
            ");
            
            foreach ($_POST['related_products'] as $relatedId) {
                $relatedId = (int)$relatedId;
                if ($relatedId > 0 && $relatedId != $productId) {
                    $insertRelated->execute([$productId, $relatedId]);
                }
            }
        } catch (Exception $e) {
            $upload_debug[] = 'related-products-error: ' . $e->getMessage();
        }
    }

    // ==== Product Groups (Collections) ====
    if (isset($_POST['group_ids'])) {
        try {
            $group_ids = $_POST['group_ids'];
            if (!is_array($group_ids)) $group_ids = [];
            
            $stmtGroupMap = $pdo->prepare("INSERT IGNORE INTO product_group_map (product_id, group_id) VALUES (?, ?)");
            
            foreach ($group_ids as $gid) {
                $gid = (int)$gid;
                if ($gid > 0) {
                    $stmtGroupMap->execute([$newId, $gid]);
                }
            }
        } catch (Exception $e) {
            $upload_debug[] = 'groups-insert-error: ' . $e->getMessage();
        }
    }

    // ==== Handle Product Filters (Dynamic Attributes) ====
    // ==== Handle Product Filters (Dynamic Attributes) ====
    $filterData = [];
    if (!empty($_POST['filter_options_json'])) {
        $decoded = json_decode($_POST['filter_options_json'], true);
        if (is_array($decoded)) $filterData = $decoded;
    } elseif (isset($_POST['filter_options']) && is_array($_POST['filter_options'])) {
        $filterData = $_POST['filter_options'];
    }

    if (!empty($filterData)) {
        try {
            $stmtFilterVal = $pdo->prepare("INSERT IGNORE INTO product_filter_values (product_id, filter_group_id, filter_option_id) VALUES (?, ?, ?)");
            
            foreach ($filterData as $groupId => $optionIds) {
                $groupId = (int)$groupId;
                // If checkbox array is empty or not array, skip
                if (!is_array($optionIds)) continue;

                foreach ($optionIds as $optId) {
                    $optId = (int)$optId;
                    if ($groupId > 0 && $optId > 0) {
                        $stmtFilterVal->execute([$newId, $groupId, $optId]);
                    }
                }
            }
        } catch (Exception $e) {
             $upload_debug[] = 'filters-insert-error: ' . $e->getMessage();
        }
    }

    // ==== Product Media Gallery Processing ====
    $productMediaArray = [];
    if (!empty($_FILES['product_media'])) {
        $mediaUploadDir = __DIR__ . '/../assets/uploads/product_media/';
        if (!is_dir($mediaUploadDir)) {
            mkdir($mediaUploadDir, 0755, true);
        }
        
        $allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $allowedVideoTypes = ['video/mp4', 'video/webm'];
        $allowedTypes = array_merge($allowedImageTypes, $allowedVideoTypes);
        
        foreach ($_FILES['product_media']['tmp_name'] as $key => $tmpName) {
            if (empty($tmpName) || $_FILES['product_media']['error'][$key] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $fileType = $_FILES['product_media']['type'][$key];
            $fileSize = $_FILES['product_media']['size'][$key];
            
            // Validate type
            if (!in_array($fileType, $allowedTypes)) {
                $upload_debug[] = "media-skip-invalid-type: {$_FILES['product_media']['name'][$key]}";
                continue;
            }
            
            // Validate size (50MB max)
            if ($fileSize > 50 * 1024 * 1024) {
                $upload_debug[] = "media-skip-too-large: {$_FILES['product_media']['name'][$key]}";
                continue;
            }
            
            $ext = pathinfo($_FILES['product_media']['name'][$key], PATHINFO_EXTENSION);
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $destination = $mediaUploadDir . $filename;
            
            // Compress and save product media (image or video)
            // Note: Only compress images, not videos
            $isImage = in_array($fileType, $allowedImageTypes);
            
            if ($isImage) {
                $mResult = compressImage($tmpName, $destination);
                if ($mResult['success']) {
                    $productMediaArray[] = [
                        'type' => 'image',
                        'path' => $filename
                    ];
                    $upload_debug[] = "media-uploaded-compressed: $filename";
                } else {
                    $upload_debug[] = "media-compression-failed: {$_FILES['product_media']['name'][$key]}";
                }
            } else {
                // Videos: just move without compression
                if (move_uploaded_file($tmpName, $destination)) {
                    $productMediaArray[] = [
                        'type' => 'video',
                        'path' => $filename
                    ];
                    $upload_debug[] = "media-uploaded: $filename";
                } else {
                    $upload_debug[] = "media-failed: {$_FILES['product_media']['name'][$key]}";
                }
            }
        }
    }
    
    // Handle Product Media from Media Library (Gallery)
    if (!empty($_POST['product_media_from_media']) && is_array($_POST['product_media_from_media'])) {
        $mediaUploadDir = __DIR__ . '/../assets/uploads/product_media/';
        if (!is_dir($mediaUploadDir)) {
            mkdir($mediaUploadDir, 0755, true);
        }
        
        foreach ($_POST['product_media_from_media'] as $mediaPath) {
            $sourcePath = $_SERVER['DOCUMENT_ROOT'] . $mediaPath;
            if (file_exists($sourcePath)) {
                $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);
                $newFilename = time() . '_' . uniqid() . '_lib.' . $ext;
                $destPath = $mediaUploadDir . $newFilename;
                
                if (copy($sourcePath, $destPath)) {
                    // Check if video
                    $isVideo = in_array(strtolower($ext), ['mp4', 'webm', 'mov']);
                    
                    $productMediaArray[] = [
                        'type' => $isVideo ? 'video' : 'image',
                        'path' => $newFilename
                    ];
                    $upload_debug[] = "media-library-copied: $newFilename";
                }
            }
        }
    }
    
    // Update product with media if any uploaded
    if (!empty($productMediaArray)) {
        try {
            $updateMedia = $pdo->prepare("UPDATE products SET product_media = ? WHERE id = ?");
            $updateMedia->execute([json_encode($productMediaArray), $productId]);
        } catch (Exception $e) {
            $upload_debug[] = 'media-db-error: ' . $e->getMessage();
        }
    }

    flash_set('success_msg', 'Product created successfully.');
    flash_set('upload_debug', $upload_debug);

    flash_set('success_msg', 'Product created successfully.');
    // Return Success for AJAX
    echo "Success";
    exit;

} catch (PDOException $e) {
    $err[] = "Database error: " . $e->getMessage();
    flash_set('form_errors', $err);
    flash_set('old', $_POST);
    flash_set('upload_debug', $upload_debug);
    $err[] = "Database error: " . $e->getMessage();
    echo "Error:Result: " . implode("\n", $err);
    exit;
} catch (Exception $e) {
    $err[] = "Error: " . $e->getMessage();
    echo "Error:Result: " . implode("\n", $err);
    exit;
}