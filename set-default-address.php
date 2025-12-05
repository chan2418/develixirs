<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['address_id'])) {
    $userId = $_SESSION['user_id'];
    $addressId = (int)$_POST['address_id'];
    
    try {
        // First, unset all default addresses for this user
        $stmtUnset = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
        $stmtUnset->execute([$userId]);
        
        // Then set the selected address as default
        $stmtSet = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmtSet->execute([$addressId, $userId]);
        
        // Fetch the phone number from this address and update user profile
        $stmtPhone = $pdo->prepare("SELECT phone FROM user_addresses WHERE id = ?");
        $stmtPhone->execute([$addressId]);
        $addrPhone = $stmtPhone->fetchColumn();
        
        if ($addrPhone) {
            $stmtUser = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
            $stmtUser->execute([$addrPhone, $userId]);
        }
        
        $_SESSION['success_message'] = 'Default address updated successfully!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error updating default address.';
    }
}

header("Location: my-profile.php?tab=addresses");
exit;
