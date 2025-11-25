<?php
// admin/save_banner.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- FLASH HELPERS ---------- */
function flash_add_error($msg) {
    if (!isset($_SESSION['form_errors']) || !is_array($_SESSION['form_errors'])) {
        $_SESSION['form_errors'] = [];
    }
    $_SESSION['form_errors'][] = $msg;
}

function flash_success($msg) {
    $_SESSION['success_msg'] = $msg;
}

function redirect_to_slot($slot) {
    header('Location: banner.php?slot=' . urlencode($slot));
    exit;
}

/* ---------- ONLY POST ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to_slot('home');
}

/* ---------- CSRF ---------- */
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    flash_add_error('Invalid form token, please try again.');
    redirect_to_slot($_POST['page_slot'] ?? 'home');
}

/* ---------- INPUTS ---------- */
$page_slot = trim($_POST['page_slot'] ?? 'home');
$alt_text  = trim($_POST['alt_text'] ?? '');
$link      = trim($_POST['link'] ?? '');
$is_active = !empty($_POST['is_active']) ? 1 : 0;
$category_id_raw = trim($_POST['category_id'] ?? '');

/* ---------- ALLOWED SLOTS ---------- */
$allowedSlots = [
    'home',
    'home_sidebar',
    'home_center',
    'home_offer',   // 👈 new child slot for sidebar offer
    'product',
    'product_detail',
    'blog',
    'category',
    'top_category',
];

if (!in_array($page_slot, $allowedSlots, true)) {
    $page_slot = 'home';
}

/* ---------- CATEGORY VALIDATION (FOR CATEGORY SLOTS) ---------- */
$category_id = null;
if (in_array($page_slot, ['category','top_category'], true)) {
    if ($category_id_raw === '') {
        flash_add_error('Please select a category for this banner.');
    } elseif (!ctype_digit($category_id_raw)) {
        flash_add_error('Invalid category selected.');
    } else {
        $category_id = (int)$category_id_raw;
    }
}

/* ---------- FILES VALIDATION ---------- */
$files = $_FILES['banners'] ?? null;
$validFiles = [];

if (!$files || !is_array($files['name'])) {
    flash_add_error('Please choose at least one banner image.');
} else {
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            flash_add_error('One of the uploads failed (error code: ' . $files['error'][$i] . ').');
            continue;
        }

        if ($files['size'][$i] > 5 * 1024 * 1024) {
            flash_add_error('One of the images is too large (max 5MB).');
            continue;
        }

        $tmpName = $files['tmp_name'][$i];

        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($mime, $allowed, true)) {
            flash_add_error('One of the images has an invalid type.');
            continue;
        }

        $origName = $files['name'][$i];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'jpg';
        }

        $validFiles[] = [
            'tmp_name' => $tmpName,
            'ext'      => $ext,
            'orig'     => $origName,
        ];
    }
}

$homeChildSlots = ['home_sidebar', 'home_center', 'home_offer'];

if (!empty($_SESSION['form_errors'])) {
    redirect_to_slot(in_array($page_slot, $homeChildSlots, true) ? 'home' : $page_slot);
}

/* ---------- UPLOAD DIR ---------- */
$uploadDir = __DIR__ . '/../assets/uploads/banners/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

if (empty($validFiles)) {
    flash_add_error('No valid images to upload.');
    redirect_to_slot(in_array($page_slot, $homeChildSlots, true) ? 'home' : $page_slot);
}

/* ---------- INSERT BANNERS ---------- */
try {
    $pdo->beginTransaction();

    $sql = "
        INSERT INTO banners
            (name, filename, alt_text, link, is_active, page_slot, category_id)
        VALUES
            (?,    ?,        ?,        ?,    ?,         ?,         ?)
    ";
    $stmt = $pdo->prepare($sql);

    foreach ($validFiles as $f) {
        $newName = time() . '-' . bin2hex(random_bytes(5)) . '.' . $f['ext'];
        $dest    = $uploadDir . $newName;

        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        $bannerName = $alt_text !== '' ? $alt_text : ('Banner ' . date('Y-m-d H:i:s'));

        $stmt->execute([
            $bannerName,
            $newName,
            $alt_text,
            $link,
            $is_active,
            $page_slot,
            $category_id,
        ]);
    }

    $pdo->commit();
    flash_success('Banners uploaded successfully.');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_add_error('Database / upload error: ' . $e->getMessage());
}

redirect_to_slot(in_array($page_slot, $homeChildSlots, true) ? 'home' : $page_slot);