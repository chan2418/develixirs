<?php
// admin/save_category.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// simple flash helper
function flash_set($k, $v) { $_SESSION[$k] = $v; }

// check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: categories.php');
    exit;
}

// CSRF check (if you use it)
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    flash_set('form_errors', ['Invalid CSRF token.']);
    header('Location: categories.php');
    exit;
}

// sanitize inputs
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$title = trim((string)($_POST['title'] ?? ''));
$parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
$description = isset($_POST['description']) ? trim((string)$_POST['description']) : null;
$slug = trim((string)($_POST['slug'] ?? ''));

// basic validation
$errors = [];
if ($title === '') {
    $errors[] = 'Title is required.';
}

if (!empty($parent_id) && $id && $parent_id === $id) {
    $errors[] = 'Category cannot be its own parent.';
}

if (!empty($errors)) {
    flash_set('form_errors', $errors);
    // preserve old input for repopulation (categories.php doesn't currently use this, but safe)
    $_SESSION['old'] = $_POST;
    header('Location: ' . ($id ? "categories.php?edit={$id}" : 'categories.php'));
    exit;
}

// create slug if missing
function slugify($s) {
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[^\p{L}\p{N}\-]+/u','-', $s);
    $s = preg_replace('/\-{2,}/','-', $s);
    $s = trim($s, '-');
    return $s ?: 'cat-' . substr(md5(uniqid('', true)),0,6);
}
if ($slug === '') {
    $slug = slugify($title);
}

// determine which columns exist in categories table to avoid SQL errors
$stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories'");
$stmt->execute();
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
$has_name = in_array('name', $cols);
$has_title = in_array('title', $cols);
$has_parent = in_array('parent_id', $cols);
$has_description = in_array('description', $cols);
$has_slug = in_array('slug', $cols);

// we will always ensure 'name' gets a value (DB expects it). We'll map $title -> name if needed
$now = date('Y-m-d H:i:s');

try {
    if ($id) {
        // UPDATE
        $parts = [];
        $params = [];

        // set name (if exists)
        if ($has_name) { $parts[] = "name = ?"; $params[] = $title; }
        // set title (if exists)
        if ($has_title) { $parts[] = "title = ?"; $params[] = $title; }
        if ($has_slug) { $parts[] = "slug = ?"; $params[] = $slug; }
        if ($has_parent) { $parts[] = "parent_id = ?"; $params[] = $parent_id; }
        if ($has_description) { $parts[] = "description = ?"; $params[] = $description; }

        if (empty($parts)) {
            throw new Exception('No writable columns found in categories table.');
        }

        $params[] = $id;
        $sql = "UPDATE categories SET " . implode(', ', $parts) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        flash_set('success_msg', 'Category updated successfully.');
        header('Location: categories.php');
        exit;
    } else {
        // INSERT
        $cols_to_insert = [];
        $placeholders = [];
        $params = [];

        // ensure name exists and is set
        if ($has_name) { $cols_to_insert[] = 'name'; $placeholders[] = '?'; $params[] = $title; }
        if ($has_title) { $cols_to_insert[] = 'title'; $placeholders[] = '?'; $params[] = $title; }
        if ($has_slug) { $cols_to_insert[] = 'slug'; $placeholders[] = '?'; $params[] = $slug; }
        if ($has_parent) { $cols_to_insert[] = 'parent_id'; $placeholders[] = '?'; $params[] = $parent_id; }
        if ($has_description) { $cols_to_insert[] = 'description'; $placeholders[] = '?'; $params[] = $description; }

        // If DB doesn't have 'name' but has 'title' then ensure at least one column exists
        if (empty($cols_to_insert)) {
            throw new Exception('No writable columns found in categories table.');
        }

        $sql = "INSERT INTO categories (" . implode(', ', $cols_to_insert) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        flash_set('success_msg', 'Category added successfully.');
        header('Location: categories.php');
        exit;
    }
} catch (PDOException $ex) {
    // handle duplicate slug or other db errors
    $errMsg = $ex->getMessage();
    // if MySQL duplicate slug (UNIQUE), give user-friendly message
    if (strpos($errMsg, 'Duplicate entry') !== false && strpos($errMsg, 'slug') !== false) {
        $errors[] = 'Slug already exists — please choose another slug.';
    } else {
        $errors[] = 'DB error: ' . $errMsg;
    }
    flash_set('form_errors', $errors);
    $_SESSION['old'] = $_POST;
    header('Location: ' . ($id ? "categories.php?edit={$id}" : 'categories.php'));
    exit;
}