<?php
// admin/modify_product.php
// Update handler for Edit Product (supports parent + sub category + category_name)

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

require_once __DIR__ . '/../includes/image_compressor.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

// flash + redirect helpers
function flash_set($k, $v) { $_SESSION[$k] = $v; }
function redirect($url) { header('Location: ' . $url); exit; }

// only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('products.php');
}

// DEBUG: Log POST data
$_SESSION['debug_last_post'] = $_POST;

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
$seo_keywords      = trim((string)($_POST['seo_keywords'] ?? ''));  // Hidden SEO tags

$parent_category_id = isset($_POST['parent_category_id']) && $_POST['parent_category_id'] !== ''
    ? (int)$_POST['parent_category_id']
    : null;

$category_id = isset($_POST['category_id']) && $_POST['category_id'] !== ''
    ? (int)$_POST['category_id']
    : null;

$price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
$stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
$sku   = trim((string)($_POST['sku'] ?? ''));
$hsn   = trim((string)($_POST['hsn'] ?? '')); // NEW
$is_active = (isset($_POST['is_active']) && (string)$_POST['is_active'] === '0') ? 0 : 1;
$label_id = isset($_POST['label_id']) && $_POST['label_id'] !== '' ? (int)$_POST['label_id'] : null;

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
$hasConcernId    = in_array('concern_id', $productCols, true); // NEW
$hasSeasonalId   = in_array('seasonal_id', $productCols, true); // NEW: Seasonal

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
        
        $isImage = strpos($mime, 'image/') === 0;
        $isVideo = strpos($mime, 'video/') === 0;
        
        if (!$isImage && !$isVideo) continue;

        $ext = pathinfo($orig, PATHINFO_EXTENSION) ?: 'jpg';
        $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $uploadDir . $filename;

        if ($isImage) {
            // Compress and save image
            $result = compressImage($tmp, $dest);
            if ($result['success']) {
                $savedImages[] = $filename;
            } else {
                // Fallback copy
                 if (@copy($tmp, $dest)) {
                    $savedImages[] = $filename;
                 }
            }
        } else {
            // Video - copy directly
            if (@copy($tmp, $dest)) {
                $savedImages[] = $filename;
            }
        }
    }
}

// Handle Images from Media Library (Legacy)
if (!empty($_POST['images_from_media']) && is_array($_POST['images_from_media'])) {
    foreach ($_POST['images_from_media'] as $mediaPath) {
        if (!empty($mediaPath)) {
            $savedImages[] = $mediaPath;
        }
    }
}

// merge existing images + new ones
// 1. Parse current DB images
$currentDbImages = [];
$rawExisting = $existing['images'] ?? '';

if (strlen(trim($rawExisting)) > 0) {
    $decoded = json_decode($rawExisting, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $currentDbImages = $decoded;
    } elseif (strpos($rawExisting, ',') !== false) {
        $currentDbImages = array_map('trim', explode(',', $rawExisting));
    } else {
        $currentDbImages = [$rawExisting];
    }
}

// 2. Determine kept existing images (based on submitted order)
$keptImages = [];
if (isset($_POST['existing_legacy_images_order']) && is_array($_POST['existing_legacy_images_order'])) {
    $submittedOrder = $_POST['existing_legacy_images_order'];
    // Security: Only keep images that were actually in the DB (prevent injection)
    // Also preserves the submitted order
    foreach ($submittedOrder as $img) {
         // Loose check: if the filename exists in currentDbImages
         // Note: $img matches the exact string stored in DB
         if (in_array($img, $currentDbImages)) {
             $keptImages[] = $img;
         }
    }
} else {
    // If no order input, start with all existing
    $keptImages = $currentDbImages;
    
    // Process explicit removals (fallback or if JS order failing)
    if (isset($_POST['removed_legacy_images']) && is_array($_POST['removed_legacy_images'])) {
        $keptImages = array_diff($keptImages, $_POST['removed_legacy_images']);
    }
}

// 3. Physical File Deletion for removed items
if (isset($_POST['removed_legacy_images']) && is_array($_POST['removed_legacy_images'])) {
   foreach ($_POST['removed_legacy_images'] as $remVal) {
       // Only delete if it was in our product's list
       if (in_array($remVal, $currentDbImages)) {
           $fsPath = __DIR__ . '/../assets/uploads/products/' . basename($remVal);
           if (file_exists($fsPath)) {
               @unlink($fsPath);
           }
       }
   }
}

// 4. Merge Kept Existing + New Uploads
$finalImages = array_merge($keptImages, $savedImages);

// Remove duplicates (just in case) and re-index
$finalImages = array_values(array_unique($finalImages));

$imagesForDb = empty($finalImages) ? '' : json_encode($finalImages);

// ============ UPDATE DB ============

try {
    $variant_label = trim($_POST['variant_label'] ?? 'Size');
    if ($variant_label === '') $variant_label = 'Size';
    $main_variant_name = trim($_POST['main_variant_name'] ?? '') ?: null;

    $ingredients = trim($_POST['ingredients'] ?? '');
    $how_to_use = trim($_POST['how_to_use'] ?? '');

    $compare_price = isset($_POST['compare_price']) && $_POST['compare_price'] !== '' ? (float)$_POST['compare_price'] : null;
    $discount_percent = isset($_POST['discount_percent']) && $_POST['discount_percent'] !== '' ? (float)$_POST['discount_percent'] : null;
    $gst_rate = isset($_POST['gst_rate']) && $_POST['gst_rate'] !== '' ? (float)$_POST['gst_rate'] : 0.00;

    // Check if cat_id exists
    $hasCatId = in_array('cat_id', $productCols, true);
    $catIdCol = $hasCatId ? 'cat_id' : 'category_id';

    // Build additional SET clauses
    $additionalSets = [];
    
    if (array_key_exists('label_id', $existing)) {
        $additionalSets[] = 'label_id = :label_id';
    }
    
    if ($hasConcernId) {
        $additionalSets[] = 'concern_id = :concern_id';
    }
    
    if ($hasSeasonalId) {
        $additionalSets[] = 'seasonal_id = :seasonal_id';
    }
    
    // Join additional columns
    $additionalSetStr = !empty($additionalSets) ? ', ' . implode(', ', $additionalSets) : '';

    $sql = "UPDATE products SET
                name = :name,
                slug = :slug,
                short_description = :short_description,
                description = :description,
                ingredients = :ingredients,
                how_to_use = :how_to_use,
                meta_title = :meta_title,
                meta_description = :meta_description,
                seo_keywords = :seo_keywords,
                parent_category_id = :parent_category_id,
                $catIdCol = :category_id,
                price = :price,
                compare_price = :compare_price,
                discount_percent = :discount_percent,
                gst_rate = :gst_rate,
                stock = :stock,
                sku = :sku,
                hsn = :hsn,
                images = :images,
                is_active = :is_active,
                variant_label = :variant_label,
                main_variant_name = :main_variant_name{$additionalSetStr},
                updated_at = NOW()
            WHERE id = :id";


    $stmt = $pdo->prepare($sql);
    $bindParams = [
        ':name'               => $name,
        ':slug'               => $slug,
        ':short_description'  => $short_description,
        ':description'        => $description,
        ':ingredients'        => $ingredients ?: null,
        ':how_to_use'         => $how_to_use ?: null,
        ':meta_title'         => $meta_title,
        ':meta_description'   => $meta_description,
        ':seo_keywords'       => $seo_keywords,
        ':parent_category_id' => $parent_category_id ?: null,
        ':category_id'        => $category_id ?: null,
        ':price'              => $price,
        ':compare_price'      => $compare_price,
        ':discount_percent'   => $discount_percent,
        ':gst_rate'           => $gst_rate,
        ':stock'              => $stock,
        ':sku'                => $sku,
        ':hsn'                => $hsn,
        ':images'             => $imagesForDb,
        ':is_active'          => $is_active,
        ':variant_label'      => $variant_label,
        ':main_variant_name'  => $main_variant_name,
        ':id'                 => $id,
    ];

    if (array_key_exists('label_id', $existing)) {
        $bindParams[':label_id'] = $label_id;
    }

    if ($hasConcernId) {
        $bindParams[':concern_id'] = !empty($_POST['concern_id']) ? (int)$_POST['concern_id'] : null;
    }

    if ($hasSeasonalId) {
        $bindParams[':seasonal_id'] = !empty($_POST['seasonal_id']) ? (int)$_POST['seasonal_id'] : null;
    }

    $stmt->execute($bindParams);

    // ============ HANDLE TAGS (product_tags table) ============
    // Parse tags_input (comma-separated text)
    // Parse tags_input (comma-separated text)
    $tagIds = [];
    $textTags = trim($_POST['tags_input'] ?? '');
    
    if ($textTags !== '') {
        $tagNames = array_filter(array_map('trim', explode(',', $textTags)));
        
        if (!empty($tagNames)) {
             $stmtCheckTag = $pdo->prepare("SELECT id FROM tags WHERE name = ? LIMIT 1");
             $stmtCreateTag = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
             foreach ($tagNames as $nm) {
                 $stmtCheckTag->execute([$nm]);
                 $tid = $stmtCheckTag->fetchColumn();
                 if (!$tid) {
                     $tslug = slugify($nm);
                     // try insert
                     try {
                         $stmtCreateTag->execute([$nm, $tslug]);
                         $tid = $pdo->lastInsertId();
                     } catch (Exception $e) {
                         $tslug .= '-'.time();
                         try { 
                             $stmtCreateTag->execute([$nm, $tslug]); 
                             $tid = $pdo->lastInsertId();
                         } catch (Exception $ex) { 
                             continue; 
                         }
                     }
                 }
                 if ($tid) $tagIds[] = (int)$tid;
             }
        }
    }

    // Merge legacy select input if present
    $legacyTags = $_POST['tags'] ?? [];
    if (is_array($legacyTags)) {
        foreach($legacyTags as $tid) {
            if(ctype_digit((string)$tid)) $tagIds[] = (int)$tid;
        }
    }
    $tagIds = array_unique($tagIds);

    // 1. Delete existing tags for this product
    $pdo->prepare("DELETE FROM product_tags WHERE product_id = ?")->execute([$id]);

    // 2. Insert new tags
    if (!empty($tagIds)) {
        try {
            $stmtTag = $pdo->prepare("INSERT IGNORE INTO product_tags (product_id, tag_id) VALUES (?, ?)");
            foreach ($tagIds as $tid) {
                $stmtTag->execute([$id, $tid]);
            }
        } catch (Exception $e) {
            // silent fail
        }
    }

    // ==== Handle Product Groups (Collections) ====
    // 1. Delete existing group mappings for this product
    $pdo->prepare("DELETE FROM product_group_map WHERE product_id = ?")->execute([$id]);

    // 2. Insert new selected groups
    if (isset($_POST['group_ids']) && is_array($_POST['group_ids'])) {
        try {
            $stmtGroupMap = $pdo->prepare("INSERT IGNORE INTO product_group_map (product_id, group_id) VALUES (?, ?)");
            foreach ($_POST['group_ids'] as $gid) {
                $gid = (int)$gid;
                if ($gid > 0) {
                    $stmtGroupMap->execute([$id, $gid]);
                }
            }
        } catch (Exception $e) {
            error_log('groups-update-error: ' . $e->getMessage());
        }
    }

    // ==== Handle Product Filters (Dynamic Attributes) ====
    // 1. Delete existing filters for this product
    try {
        $pdo->prepare("DELETE FROM product_filter_values WHERE product_id = ?")->execute([$id]);
    } catch (Exception $e) { error_log('filter-delete-error: ' . $e->getMessage()); }

    // 2. Insert new selected filters
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
                if (!is_array($optionIds)) continue;

                foreach ($optionIds as $optId) {
                    $optId = (int)$optId;
                    if ($groupId > 0 && $optId > 0) {
                        $stmtFilterVal->execute([$id, $groupId, $optId]);
                    }
                }
            }
        } catch (Exception $e) {
            error_log('filters-update-error: ' . $e->getMessage());
        }
    }

    // ============ HANDLE VARIANTS ============
    // 1. Delete removed variants
    if (!empty($_POST['delete_variant_ids']) && is_array($_POST['delete_variant_ids'])) {
        $delIds = array_map('intval', $_POST['delete_variant_ids']);
        if (!empty($delIds)) {
            $inQuery = implode(',', $delIds);
            $pdo->exec("DELETE FROM product_variants WHERE id IN ($inQuery) AND product_id = $id");
        }
    }

    // 2. Update/Insert variants
    $variantsRaw = $_POST['variants'] ?? [];
    

    if (!empty($variantsRaw) && is_array($variantsRaw)) {
        // Prepare statements for variants (with type and linked_product_id)
        try {
            // Check if HSN column exists
            $hsnExists = false;
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM product_variants LIKE 'hsn'")->fetchAll();
                if (!empty($cols)) {
                    $hsnExists = true;
                }
            } catch (Exception $e) { $hsnExists = false; }

            if ($hsnExists) {
                $stmtInsert = $pdo->prepare("INSERT INTO product_variants (product_id, variant_name, type, linked_product_id, price, compare_price, discount_percent, stock, sku, hsn, image, images, custom_title, custom_description, short_description, ingredients, how_to_use, meta_title, meta_description, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                $stmtUpdate = $pdo->prepare("UPDATE product_variants SET variant_name=?, type=?, linked_product_id=?, price=?, compare_price=?, discount_percent=?, stock=?, sku=?, hsn=?, image=?, images=?, custom_title=?, custom_description=?, short_description=?, ingredients=?, how_to_use=?, meta_title=?, meta_description=? WHERE id=? AND product_id=?");
            } else {
                $stmtInsert = $pdo->prepare("INSERT INTO product_variants (product_id, variant_name, type, linked_product_id, price, compare_price, discount_percent, stock, sku, image, images, custom_title, custom_description, short_description, ingredients, how_to_use, meta_title, meta_description, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                $stmtUpdate = $pdo->prepare("UPDATE product_variants SET variant_name=?, type=?, linked_product_id=?, price=?, compare_price=?, discount_percent=?, stock=?, sku=?, image=?, images=?, custom_title=?, custom_description=?, short_description=?, ingredients=?, how_to_use=?, meta_title=?, meta_description=? WHERE id=? AND product_id=?");
            }
            
            // Prepare statements for variant FAQs
            $stmtInsertFaq = $pdo->prepare("INSERT INTO variant_faqs (variant_id, question, answer, display_order) VALUES (?, ?, ?, ?)");
            $stmtDeleteFaqs = $pdo->prepare("DELETE FROM variant_faqs WHERE variant_id = ?");
        } catch (PDOException $e) {
             echo "<div style='background:red;color:white;padding:20px;font-family:monospace;'>";
             echo "<h2>⚠️ DATABASE SCHEMA ERROR</h2>";
             echo "<p>Failed to prepare SQL statements. This usually means your database is missing new columns (type, linked_product_id, hsn).</p>";
             echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
             echo "</div>";
             die("CRITICAL DB ERROR");
        }

        foreach ($variantsRaw as $idx => $v) {
            $vId    = !empty($v['id']) ? (int)$v['id'] : 0;
            $vName  = trim($v['name'] ?? '');
            $vType  = trim($v['type'] ?? 'custom'); // NEW: variant type
            $vLinkedProductId = !empty($v['linked_product_id']) ? (int)$v['linked_product_id'] : null; // NEW



            if ($vName === '') continue;

            // Different handling for linked vs custom variants
            if ($vType === 'linked') {
                // LINKED VARIANT - only need linked_product_id
                // Use 0 for NOT NULL columns to prevent SQL errors
                $vPrice = 0.00;
                $vComparePrice = null;
                $vDiscountPercent = null;
                $vStock = 0; 
                $vSku = '';
                // Fix: Allow HSN saving for linked variants
                $vHsn = trim($v['hsn'] ?? '') ?: null;
                $vCustomTitle = null;
                $vCustomDesc = null;
                $vShortDesc = null;
                $vIngredients = null;
                $vHowToUse = null;
                $vMetaTitle = null;
                $vMetaDesc = null;
                $vImagesJson = '[]';
                $vFirstImage = null;
                // $variantId will be set after INSERT/UPDATE
                
            } else {
                // CUSTOM VARIANT - process all fields
                $vPrice = (float)($v['price'] ?? 0);
                $vComparePrice = isset($v['compare_price']) && $v['compare_price'] !== '' ? (float)$v['compare_price'] : null;
                $vDiscountPercent = isset($v['discount_percent']) && $v['discount_percent'] !== '' ? (float)$v['discount_percent'] : null;
                $vStock = isset($v['stock']) ? (int)$v['stock'] : 0;
                $vSku   = trim($v['sku'] ?? '');
                $vHsn   = trim($v['hsn'] ?? '');
                $vCustomTitle = trim($v['custom_title'] ?? '') ?: null;
                $vCustomDesc = trim($v['custom_description'] ?? '') ?: null;
                $vShortDesc = trim($v['short_description'] ?? '') ?: null;
                $vIngredients = trim($v['ingredients'] ?? '') ?: null;
                $vHowToUse = trim($v['how_to_use'] ?? '') ?: null;
                $vMetaTitle = trim($v['meta_title'] ?? '') ?: null;
                $vMetaDesc = trim($v['meta_description'] ?? '') ?: null;

                // Handle Variant Images Upload (Multiple)
                $variantImages = [];
                
                // 1. Keep existing images (that weren't deleted)
                if (!empty($v['existing_images']) && is_array($v['existing_images'])) {
                    $variantImages = $v['existing_images'];
                }

                // 2. Add new uploaded images
                if (!empty($_FILES['variants']['name'][$idx]['images'])) {
                    foreach ($_FILES['variants']['name'][$idx]['images'] as $i => $fname) {
                        if (empty($fname)) continue;
                        
                        $fTmp = $_FILES['variants']['tmp_name'][$idx]['images'][$i];
                        $fErr = $_FILES['variants']['error'][$idx]['images'][$i];
                        
                        if ($fErr === UPLOAD_ERR_OK && is_uploaded_file($fTmp)) {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime = finfo_file($finfo, $fTmp);
                            finfo_close($finfo);
                            
                            if (strpos($mime, 'image/') === 0) {
                                $ext = pathinfo($fname, PATHINFO_EXTENSION) ?: 'jpg';
                                $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
                                $newName = 'var_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                                $dest = $uploadDir . $newName;
                                
                                // Compress and save variant image
                                $vResult = compressImage($fTmp, $dest);
                                if ($vResult['success']) {
                                    $variantImages[] = $newName;
                                }
                            }
                        }
                    }
                }
                
                $vImagesJson = !empty($variantImages) ? json_encode($variantImages) : '[]';
                $vFirstImage = !empty($variantImages) ? $variantImages[0] : null;
            }

            if ($vId > 0) {
                // Update existing variant
                try {
                    // UPDATE
                    $params = [
                        $vName, $vType, $vLinkedProductId, $vPrice, $vComparePrice, $vDiscountPercent, $vStock, $vSku
                    ];
                    if ($hsnExists) { $params[] = $vHsn; }
                    
                    $params = array_merge($params, [
                        $vFirstImage, $vImagesJson, $vCustomTitle, $vCustomDesc, $vShortDesc, $vIngredients, $vHowToUse, $vMetaTitle, $vMetaDesc,
                        $vId, $id
                    ]);

                    $stmtUpdate->execute($params);
                    $variantId = $vId; // for FAQs
                } catch (PDOException $e) {
                    error_log('ERROR modify_product.php - Variant Update Fail: ' . $e->getMessage());
                    throw $e;
                }
            } else {
                // Insert new variant
                try {
                    // INSERT
                    $params = [
                        $id, $vName, $vType, $vLinkedProductId, $vPrice, $vComparePrice, $vDiscountPercent, $vStock, $vSku
                    ];
                    if ($hsnExists) { $params[] = $vHsn; }
                    
                    $params = array_merge($params, [
                        $vFirstImage, $vImagesJson, $vCustomTitle, $vCustomDesc, $vShortDesc, $vIngredients, $vHowToUse, $vMetaTitle, $vMetaDesc
                    ]);
                    
                    $stmtInsert->execute($params);
                    $variantId = (int)$pdo->lastInsertId();
                    error_log("  ✓ Variant #{$idx} inserted with ID: {$variantId}");
                    
                    echo "<p style='color:#0f0;'>✓ Variant #{$idx} '<strong>{$vName}</strong>' INSERT successful! ID: <strong>{$variantId}</strong></p>";
                    
                } catch (PDOException $e) {
                    error_log('ERROR modify_product.php - Variant Insert Fail: ' . $e->getMessage());
                    echo "<p style='color:#f00;'>✗ Variant #{$idx} INSERT FAILED: " . htmlspecialchars($e->getMessage()) . "</p>";
                    throw $e;
                }
            }
            
            // Handle Variant FAQs
            if ($variantId > 0) {
                // Delete existing FAQs for this variant
                $stmtDeleteFaqs->execute([$variantId]);
                
                // Insert new/updated FAQs
                if (!empty($v['faqs']) && is_array($v['faqs'])) {
                    foreach ($v['faqs'] as $faqIdx => $faq) {
                        $question = trim($faq['question'] ?? '');
                        $answer = trim($faq['answer'] ?? '');
                        
                        if ($question && $answer) {
                            $stmtInsertFaq->execute([$variantId, $question, $answer, $faqIdx]);
                        }
                    }
                }
            }
        }
    }

    // ============ HANDLE FAQS ============
    // 1. Delete removed FAQs
    if (!empty($_POST['delete_faq_ids'])) {
        // It might be a comma-separated string from the hidden input
        $delFaqIds = explode(',', $_POST['delete_faq_ids']);
        $delFaqIds = array_map('intval', $delFaqIds);
        $delFaqIds = array_filter($delFaqIds); // remove 0 or empty

        if (!empty($delFaqIds)) {
            $inQuery = implode(',', $delFaqIds);
            $pdo->exec("DELETE FROM product_faqs WHERE id IN ($inQuery) AND product_id = $id");
        }
    }

    // 2. Update/Insert FAQs
    $faqsRaw = $_POST['faqs'] ?? [];
    if (!empty($faqsRaw) && is_array($faqsRaw)) {
        $stmtInsertFaq = $pdo->prepare("INSERT INTO product_faqs (product_id, question, answer) VALUES (?, ?, ?)");
        $stmtUpdateFaq = $pdo->prepare("UPDATE product_faqs SET question=?, answer=? WHERE id=? AND product_id=?");

        foreach ($faqsRaw as $f) {
            $fId = !empty($f['id']) ? (int)$f['id'] : 0;
            $q   = trim($f['question'] ?? '');
            $a   = trim($f['answer'] ?? '');

            if ($q === '' || $a === '') continue;

            if ($fId > 0) {
                // Update
                $stmtUpdateFaq->execute([$q, $a, $fId, $id]);
            } else {
                // Insert
                $stmtInsertFaq->execute([$id, $q, $a]);
            }
        }
    }

    // ==== Update Related Products ====
    // Delete all existing relations for this product
    try {
        $deleteRelated = $pdo->prepare("DELETE FROM product_relations WHERE product_id = ?");
        $deleteRelated->execute([$id]);
        
        // Insert new relations
        if (!empty($_POST['related_products']) && is_array($_POST['related_products'])) {
            $insertRelated = $pdo->prepare("
                INSERT INTO product_relations (product_id, related_product_id)
                VALUES (?, ?)
            ");
            
            foreach ($_POST['related_products'] as $relatedId) {
                $relatedId = (int)$relatedId;
                if ($relatedId > 0 && $relatedId != $id) {
                    $insertRelated->execute([$id, $relatedId]);
                }
            }
        }
    } catch (Exception $e) {
        // Log error but don't fail the whole operation
        error_log('Related products update error: ' . $e->getMessage());
    }

    // ==== Product Media Gallery Processing ====
    // Fetch existing media
    $existingMedia = [];
    $stmt = $pdo->prepare("SELECT product_media FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($result['product_media'])) {
        $decoded = json_decode($result['product_media'], true);
        if (is_array($decoded)) {
            $existingMedia = $decoded;
        }
    }
    
    // Handle removed media
    if (!empty($_POST['removed_media']) && is_array($_POST['removed_media'])) {
        $mediaUploadDir = __DIR__ . '/../assets/uploads/product_media/';
        foreach ($_POST['removed_media'] as $index) {
            $index = (int)$index;
            if (isset($existingMedia[$index])) {
                // Delete file from server
                $filePath = $mediaUploadDir . $existingMedia[$index]['path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                // Mark for removal (unset later or handle re-index)
                unset($existingMedia[$index]);
            }
        }
    }

// Handle removed legacy images
if (!empty($_POST['removed_legacy_images']) && is_array($_POST['removed_legacy_images'])) {
    foreach ($_POST['removed_legacy_images'] as $delImg) {
        $delImg = trim($delImg);
        $key = array_search($delImg, $finalImages);
        if ($key !== false) {
            // Remove file from server if needed (optional, maybe keep as backup?)
            // Usually we delete to save space
            $delPath = $uploadDir . $delImg;
            if (file_exists($delPath)) {
                @unlink($delPath);
            }
            unset($finalImages[$key]);
        }
    }
    // Re-index
    $finalImages = array_values($finalImages);
}

// Handle reordering of legacy images
if (!empty($_POST['existing_legacy_images_order']) && is_array($_POST['existing_legacy_images_order'])) {
    $reorderedLegacy = [];
    foreach ($_POST['existing_legacy_images_order'] as $fname) {
        $fname = trim($fname);
        // Verify this image actually belongs to the product (is in finalImages)
        if (in_array($fname, $finalImages)) {
            $reorderedLegacy[] = $fname;
            // Remove from original so we can append any leftovers
            $key = array_search($fname, $finalImages);
            if ($key !== false) {
                unset($finalImages[$key]);
            }
        }
    }
    // Append any remaining images (edge case or safeguard)
    if (!empty($finalImages)) {
        foreach ($finalImages as $img) {
            $reorderedLegacy[] = $img;
        }
    }
    $finalImages = $reorderedLegacy;
}
    
    // Handle reordering of existing media
    if (!empty($_POST['existing_media_order']) && is_array($_POST['existing_media_order'])) {
        $reorderedMedia = [];
        // The frontend sends existing indices in the desired order
        foreach ($_POST['existing_media_order'] as $oldIndex) {
            $oldIndex = (int)$oldIndex;
            // Only add if it still exists (wasn't removed)
            if (isset($existingMedia[$oldIndex])) {
                $reorderedMedia[] = $existingMedia[$oldIndex];
                // Unset from original to track any leftovers (though shouldn't be any if logic is correct)
                unset($existingMedia[$oldIndex]);
            }
        }
        
        // If there are any remaining items that weren't in the order list (edge case), append them
        if (!empty($existingMedia)) {
            foreach ($existingMedia as $media) {
                $reorderedMedia[] = $media;
            }
        }
        
        $existingMedia = $reorderedMedia;
    } else {
        // Just re-index if no specific order provided
        $existingMedia = array_values($existingMedia);
    }
    
    // Handle new media uploads
    $newMediaArray = [];
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
            
            if (!in_array($fileType, $allowedTypes) || $fileSize > 50 * 1024 * 1024) {
                continue;
            }
            
            $ext = pathinfo($_FILES['product_media']['name'][$key], PATHINFO_EXTENSION);
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $destination = $mediaUploadDir . $filename;
            
            // Compress and save product media (image or video)
            $isImage = in_array($fileType, $allowedImageTypes);
            
            if ($isImage) {
                $mResult = compressImage($tmpName, $destination);
                if ($mResult['success']) {
                    $newMediaArray[] = [
                        'type' => 'image',
                        'path' => $filename
                    ];
                }
            } else {
                // Videos: just move without compression
                if (move_uploaded_file($tmpName, $destination)) {
                    $newMediaArray[] = [
                        'type' => 'video',
                        'path' => $filename
                    ];
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
                    $isVideo = in_array(strtolower($ext), ['mp4', 'webm', 'mov']);
                    $newMediaArray[] = [
                        'type' => $isVideo ? 'video' : 'image',
                        'path' => $newFilename
                    ];
                }
            }
        }
    }
    
    // Merge existing (not removed) and new media
    $finalMediaArray = array_merge($existingMedia, $newMediaArray);
    
    // Update database with final media array
    try {
        $updateMedia = $pdo->prepare("UPDATE products SET product_media = ? WHERE id = ?");
        $updateMedia->execute([json_encode($finalMediaArray), $id]);
    } catch (Exception $e) {
        error_log('Media update error: ' . $e->getMessage());
    }

    flash_set('success_msg', 'Product updated successfully.');
    redirect('products.php');

} catch (PDOException $e) {
    // optional: delete newly uploaded files if error
    foreach ($savedImages as $f) {
        @unlink($uploadDir . $f);
    }
    error_log('[modify_product] DB error: ' . $e->getMessage());
    flash_set('form_errors', ['DB error: ' . $e->getMessage()]);
    flash_set('old', $_POST);
    redirect("edit_product.php?id={$id}");
}