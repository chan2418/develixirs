<?php
// admin/delete_concern.php

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Optional: Check if products linked? For now, we just nullify them
        $pdo->exec("UPDATE products SET concern_id = NULL WHERE concern_id = $id");
        
        // Delete concern
        $stmt = $pdo->prepare("DELETE FROM concerns WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success_msg'] = "Concern deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Failed to delete: " . $e->getMessage();
    }
}

header("Location: concerns.php");
exit;
