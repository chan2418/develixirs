<?php
require_once __DIR__ . '/includes/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `external_reviews` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `platform_name` varchar(50) DEFAULT NULL,
      `reviewer_name` varchar(100) DEFAULT NULL,
      `review_content` text,
      `rating` decimal(2,1) DEFAULT '5.0',
      `review_link` varchar(255) DEFAULT NULL,
      `platform_icon` varchar(255) DEFAULT NULL,
      `is_active` tinyint(1) DEFAULT '1',
      `sort_order` int(11) DEFAULT '0',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Table 'external_reviews' created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
