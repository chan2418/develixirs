<?php
// admin/edit_category.php
// Redirect to add_category.php with edit mode

if (empty($_GET['id'])) {
    header('Location: categories.php');
    exit;
}

$editId = (int)$_GET['id'];
header("Location: add_category.php?edit={$editId}");
exit;
