<?php
// admin/save_category.php

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/image_compressor.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- FLASH HELPERS ---------- */
function flash_set($k, $v) {
    $_SESSION[$k] = $v;
}
function redirect_back() {
    header('Location: categories.php');
    exit;
}

/* ---------- CSRF ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_back();
}

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    flash_set('form_errors', ['Invalid form token, please try again.']);
    redirect_back();
}

/* ---------- INPUTS ---------- */
$id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;    // 0 => insert
$title       = trim($_POST['title'] ?? '');
$parent_raw  = trim($_POST['parent_id'] ?? '');
$description = trim($_POST['description'] ?? '');

$old = [
    'title'       => $title,
    'parent_id'   => $parent_raw,
    'description' => $description,
];

$errors = [];

/* ---------- BASIC VALIDATION ---------- */
if ($title === '') {
    $errors[] = 'Title is required.';
}

$parent_id = null;
if ($parent_raw !== '') {
    if (!ctype_digit($parent_raw)) {
        $errors[] = 'Invalid parent category.';
    } else {
        $parent_id = (int)$parent_raw;
    }
}

/* ---------- STOP IF BASIC ERRORS ---------- */
if ($errors) {
    flash_set('form_errors', $errors);
    flash_set('old', $old);
    redirect_back();
}

/* ---------- SLUG ---------- */
function slugify($str) {
    $s = strtolower($str);
    $s = preg_replace('/[^a-z0-9]+/i', '-', $s);
    $s = trim($s, '-');
    if ($s === '') {
        $s = 'cat-' . time();
    }
    return $s;
}

// If this is a subcategory (has a parent), include parent name in slug
$slug = slugify($title);
if ($parent_id !== null && $parent_id > 0) {
    try {
        $stmtParent = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmtParent->execute([$parent_id]);
        $parentName = $stmtParent->fetchColumn();
        if ($parentName) {
            $slug = slugify($parentName) . '-' . $slug;
        }
    } catch (PDOException $e) {
        // If can't fetch parent, just use the title slug
    }
}

/* ---------- HANDLE IMAGE UPLOAD OR LIBRARY SELECTION ---------- */
$imageFilename = null;
$uploadDir = __DIR__ . '/../assets/uploads/categories/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// 1. Check for New File Upload (ONLY if a file was actually successfully selected)
if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $f = $_FILES['image'];

    if ($f['size'] > 5 * 1024 * 1024) {
        $errors[] = 'Image is too large (max 5MB).';
    } else {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $f['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed, true)) {
            $errors[] = 'Invalid image type. Use JPG, PNG, WEBP or GIF.';
        } else {
            $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
            $ext = $ext ? strtolower($ext) : 'jpg';
            $imageFilename = time() . '-' . bin2hex(random_bytes(5)) . '.' . $ext;
            $dest          = $uploadDir . $imageFilename;

            // Compress and save image
            $result = compressImage($f['tmp_name'], $dest);
            if (!$result['success']) {
                $errors[] = 'Could not compress/save uploaded image: ' . $result['message'];
            }
        }
    }
}
// 2. Check for Library Selection (if no new upload)
elseif (!empty($_POST['image_selected'])) {
    $imageFilename = trim($_POST['image_selected']);
    // If it comes from library, it might be full path or relative.
    // Ensure we store consistent format. 
    // The DB seems to store just filename usually? Or "assets/..."?
    // Looking at categories.php code I wrote: if(strpos($edit['image'], 'assets/')...
    // So if it's a library path like "/assets/uploads/products/xyz.jpg", we might want to store that full path 
    // OR just the filename if it's in the same folder.
    // But library can be anywhere. Safest is to store what we get, but strip leading slash if needed?
    // Let's store full relative path "assets/..." if outside standard folder.
    // If it's "/assets/...", strip leading slash.
    if (strpos($imageFilename, '/') === 0) {
        $imageFilename = ltrim($imageFilename, '/');
    }
}

/* ---------- STOP IF IMAGE ERRORS ---------- */
if ($errors) {
    flash_set('form_errors', $errors);
    flash_set('old', $old);
    redirect_back();
}

/* ---------- ALWAYS FIXED PARAM COUNTS ---------- */
$meta_title       = trim($_POST['meta_title'] ?? '');
$meta_description = trim($_POST['meta_description'] ?? '');

// Process FAQs
$faqs_json = null;
if (!empty($_POST['faq_questions']) && is_array($_POST['faq_questions'])) {
    $faq_acc = [];
    foreach ($_POST['faq_questions'] as $i => $q) {
        $q = trim($q);
        $a = trim($_POST['faq_answers'][$i] ?? '');
        if ($q !== '' || $a !== '') {
            $faq_acc[] = ['q' => $q, 'a' => $a];
        }
    }
    if (!empty($faq_acc)) {
        $faqs_json = json_encode($faq_acc);
    }
}

// Process Media Gallery
$gallery_items = [];

// 1. Keep Existing Images (from UI hidden inputs)
// This allows deletions to work because we only keep what is sent back.
if (!empty($_POST['existing_images']) && is_array($_POST['existing_images'])) {
    foreach ($_POST['existing_images'] as $img) {
        if (is_string($img) && !empty($img)) {
            $gallery_items[] = $img;
        }
    }
}

// 2. Add Library Selections (JSON from hidden input)
if (!empty($_POST['media_gallery_selected'])) {
    $selected = json_decode($_POST['media_gallery_selected'], true);
    if (is_array($selected)) {
        foreach ($selected as $path) {
            if (is_string($path) && !empty($path)) {
                // Prevent duplicates if already in existing (though UI should handle this)
                if (!in_array($path, $gallery_items)) {
                    $gallery_items[] = $path;
                }
            }
        }
    }
}

// 3. Handle new uploads
if (!empty($_FILES['media_gallery'])) {
    $files = $_FILES['media_gallery'];
    // Handle multiple files
    $count = is_array($files['name']) ? count($files['name']) : 0;
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tmp = $files['tmp_name'][$i];
            $name = $files['name'][$i];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp);
            finfo_close($finfo);
            
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'video/webm', 'video/ogg'];
            if (in_array($mime, $allowed)) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $ext = $ext ? strtolower($ext) : 'jpg';
                
                // Use a generic prefix for gallery items
                $fn = 'gallery-' . time() . '-' . bin2hex(random_bytes(4)) . '-' . $i . '.' . $ext;
                $dest = $uploadDir . $fn;
                
                // Check if it's a video to bypass compression
                if (strpos($mime, 'video/') === 0) {
                    if (move_uploaded_file($tmp, $dest)) {
                        $gallery_items[] = $fn;
                    }
                } else {
                    // It's an image, compress it
                    $res = compressImage($tmp, $dest);
                    if ($res['success']) {
                        $gallery_items[] = $fn;
                    }
                }
            }
        }
    }
}
$media_gallery_json = !empty($gallery_items) ? json_encode(array_values(array_unique($gallery_items))) : null;


/* ---------- ALWAYS FIXED PARAM COUNTS ---------- */
try {
    if ($id > 0) {
        // ===== UPDATE EXISTING CATEGORY =====
        // 1) If no new image uploaded, fetch existing image so we still bind something
        if ($imageFilename === null) {
            $stmtOld = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
            $stmtOld->execute([$id]);
            $rowOld = $stmtOld->fetch(PDO::FETCH_ASSOC);
            $imageFilename = $rowOld['image'] ?? null;
        }

        // 2) Fixed UPDATE with 11 placeholders
        $sql = "
            UPDATE categories
            SET
                name        = ?,   -- required
                title       = ?,   -- optional label
                slug        = ?,   -- required
                parent_id   = ?,   -- can be null
                description = ?,   -- text
                image       = ?,   -- can be null
                meta_title      = ?,
                meta_description= ?,
                faqs            = ?,
                media_gallery   = ?
            WHERE id = ?
        ";
        $params = [
            $title,         // name
            $title,         // title
            $slug,          // slug
            $parent_id,     // parent_id (null or int)
            $description,   // description
            $imageFilename, // image (null or string)
            $meta_title,
            $meta_description,
            $faqs_json,
            $media_gallery_json,
            $id             // id
        ];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        flash_set('success_msg', 'Category updated successfully.');

    } else {
        // ===== INSERT NEW CATEGORY =====
        // Fixed INSERT
        $sql = "
            INSERT INTO categories
                (name, title, slug, parent_id, description, image, meta_title, meta_description, faqs, media_gallery)
            VALUES
                (?,    ?,     ?,    ?,         ?,           ?,     ?,          ?,                ?,    ?)
        ";
        $params = [
            $title,         // name (NOT NULL)
            $title,         // title
            $slug,          // slug (NOT NULL)
            $parent_id,     // parent_id (null or int)
            $description,   // description
            $imageFilename, // image (null or string)
            $meta_title,
            $meta_description,
            $faqs_json,
            $media_gallery_json
        ];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        flash_set('success_msg', 'Category created successfully.');
    }

} catch (PDOException $e) {
    // If anything DB-related explodes, push message to UI
    flash_set('form_errors', ['Database error: ' . $e->getMessage()]);
    flash_set('old', $old);
}

redirect_back();