<?php
// admin/filter_group_delete.php
require_once '../includes/db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: filter_groups.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    die('Invalid delete request.');
}

try {
    // If you want it safe, wrap in transaction
    $pdo->beginTransaction();

    // First delete all options of this group
    $stmtOpt = $pdo->prepare("DELETE FROM filter_options WHERE group_id = :gid");
    $stmtOpt->execute(['gid' => $id]);

    // Then delete the group itself
    $stmtGrp = $pdo->prepare("DELETE FROM filter_groups WHERE id = :id");
    $stmtGrp->execute(['id' => $id]);

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // for debugging you could echo error, but in production better redirect
    die('Error deleting group: ' . $e->getMessage());
}

// Redirect back to list
header('Location: filter_groups.php');
exit;