<?php
// admin/delete_herbal.php

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Optional: Check if products linked? For now, we just nullify them
        $pdo->exec("UPDATE products SET herbal_id = NULL WHERE herbal_id = $id");
        
        // Delete herbal
        $stmt = $pdo->prepare("DELETE FROM herbals WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success_msg'] = "Herbal category deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Failed to delete: " . $e->getMessage();
    }
}

header("Location: herbals.php");
exit;
