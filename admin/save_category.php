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

/* ---------- HANDLE IMAGE UPLOAD (OPTIONAL) ---------- */
$imageFilename = null;
$uploadDir = __DIR__ . '/../assets/uploads/categories/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['image'];

    if ($f['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Image upload failed.';
    } else {
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
}

/* ---------- STOP IF IMAGE ERRORS ---------- */
if ($errors) {
    flash_set('form_errors', $errors);
    flash_set('old', $old);
    redirect_back();
}

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

        // 2) Fixed UPDATE with 7 placeholders, always
        $sql = "
            UPDATE categories
            SET
                name        = ?,   -- required
                title       = ?,   -- optional label
                slug        = ?,   -- required
                parent_id   = ?,   -- can be null
                description = ?,   -- text
                image       = ?    -- can be null
            WHERE id = ?
        ";
        $params = [
            $title,         // name
            $title,         // title
            $slug,          // slug
            $parent_id,     // parent_id (null or int)
            $description,   // description
            $imageFilename, // image (null or string)
            $id             // id
        ];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        flash_set('success_msg', 'Category updated successfully.');

    } else {
        // ===== INSERT NEW CATEGORY =====
        // Fixed INSERT with 6 placeholders, always
        $sql = "
            INSERT INTO categories
                (name, title, slug, parent_id, description, image)
            VALUES
                (?,    ?,     ?,    ?,         ?,           ?)
        ";
        $params = [
            $title,         // name (NOT NULL)
            $title,         // title
            $slug,          // slug (NOT NULL)
            $parent_id,     // parent_id (null or int)
            $description,   // description
            $imageFilename  // image (null or string)
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