<?php
// admin/filter_option_delete.php
require_once '../includes/db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: filter_groups.php');
    exit;
}

$id      = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

if ($id <= 0 || $groupId <= 0) {
    // Invalid data – no HTML sent yet, safe to die or redirect
    die('Invalid delete request.');
}

// Optional: you can check if this option belongs to the group,
// but DELETE with both conditions is usually enough:
$stmt = $pdo->prepare("
    DELETE FROM filter_options
    WHERE id = :id
      AND group_id = :group_id
");
$stmt->execute([
    'id'       => $id,
    'group_id' => $groupId,
]);

// Redirect back to options list for that group
header('Location: filter_options.php?group_id=' . $groupId);
exit;