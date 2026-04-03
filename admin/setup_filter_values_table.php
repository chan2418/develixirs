<?php
require_once __DIR__ . '/../includes/db.php';

echo "<h2>Setting up Product Filter Values Table...</h2>";

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS `product_filter_values` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `product_id` int(11) NOT NULL,
      `filter_group_id` int(11) NOT NULL, 
      `filter_option_id` int(11) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `product_id` (`product_id`),
      KEY `filter_group_id` (`filter_group_id`),
      KEY `filter_option_id` (`filter_option_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($sql);
    echo "<p style='color:green'>Table 'product_filter_values' created or already exists.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
