<?php
// admin/delete_seasonal.php

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        // Optional: unlink image if we want strict cleanup, but usually we leave files
        
        // Delete record
        $stmt = $pdo->prepare("DELETE FROM seasonals WHERE id = ?");
        $stmt->execute([$id]);

        // Unlink products
        // Set seasonal_id to NULL for any products that had this seasonal
        $pdo->prepare("UPDATE products SET seasonal_id = NULL WHERE seasonal_id = ?")->execute([$id]);

        $_SESSION['success_msg'] = "Seasonal theme deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Error deleting seasonal theme: " . $e->getMessage();
    }
}

header("Location: seasonals.php");
exit;
