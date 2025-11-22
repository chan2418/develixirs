<?php
// admin/delete_category.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

session_start();
function flash($k,$v=null){ if($v===null){ if(!empty($_SESSION[$k])){ $t=$_SESSION[$k]; unset($_SESSION[$k]); return $t;} return null;} $_SESSION[$k]=$v; }

$id = !empty($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { flash('form_errors',['Missing category id']); header('Location: categories.php'); exit; }

try {
    // Re-parent children -> set parent_id = NULL (top level) to avoid orphan constraint issues
    $pdo->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = ?")->execute([$id]);

    // Optionally if you want to keep products: do NOT delete products.
    // Delete category record
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);

    flash('success_msg', 'Category deleted (children re-parented to top level).');
} catch (Exception $e) {
    flash('form_errors', ['DB error: ' . $e->getMessage()]);
}
header('Location: categories.php');
exit;