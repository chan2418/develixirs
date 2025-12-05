<?php
require_once 'includes/db.php';

try {
    // Backfill tax for orders where it is 0
    // Assuming total_amount is tax-inclusive (18% GST)
    // Tax = Total - (Total / 1.18)
    $sql = "UPDATE orders SET tax_amount = total_amount - (total_amount / 1.18) WHERE tax_amount = 0";
    $count = $pdo->exec($sql);
    echo "Updated tax for $count orders.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
