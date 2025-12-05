<?php
require_once 'includes/db.php';

try {
    // Fetch all orders without address
    $stmt = $pdo->query("SELECT id, user_id FROM orders WHERE customer_address IS NULL");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;
    foreach ($orders as $order) {
        // Fetch default address for user
        $stmtAddr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1");
        $stmtAddr->execute([$order['user_id']]);
        $addr = $stmtAddr->fetch(PDO::FETCH_ASSOC);

        if ($addr) {
            $addressString = $addr['full_name'] . "\n" . 
                             $addr['address_line1'] . "\n" . 
                             ($addr['address_line2'] ? $addr['address_line2'] . "\n" : "") . 
                             $addr['city'] . ", " . $addr['state'] . " - " . $addr['pincode'] . "\n" . 
                             "Phone: " . $addr['phone'];
            
            $stmtUpd = $pdo->prepare("UPDATE orders SET customer_address = ? WHERE id = ?");
            $stmtUpd->execute([$addressString, $order['id']]);
            $updated++;
        }
    }
    echo "Updated address for $updated orders.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
