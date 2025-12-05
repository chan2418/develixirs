<?php
require_once __DIR__ . '/../includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing Missing Profile Tables...</h2>";

// 1. user_upi
try {
    $sql = "CREATE TABLE IF NOT EXISTS `user_upi` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `provider` varchar(50) DEFAULT 'UPI',
        `upi_id` varchar(100) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    echo "<p style='color:green'>Table 'user_upi' checked/created.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error 'user_upi': " . $e->getMessage() . "</p>";
}

// 2. user_notifications
try {
    $sql = "CREATE TABLE IF NOT EXISTS `user_notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `message` text,
        `is_read` tinyint(1) DEFAULT 0,
        `url` varchar(255) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    echo "<p style='color:green'>Table 'user_notifications' checked/created.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error 'user_notifications': " . $e->getMessage() . "</p>";
}

// 3. wishlist
try {
    $sql = "CREATE TABLE IF NOT EXISTS `wishlist` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `product_id` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    echo "<p style='color:green'>Table 'wishlist' checked/created.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error 'wishlist': " . $e->getMessage() . "</p>";
}

// 4. user_addresses
try {
    $sql = "CREATE TABLE IF NOT EXISTS `user_addresses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `full_name` varchar(100) NOT NULL,
        `phone` varchar(20) NOT NULL,
        `address_line1` text NOT NULL,
        `address_line2` text,
        `city` varchar(100) NOT NULL,
        `state` varchar(100) NOT NULL,
        `pincode` varchar(20) NOT NULL,
        `is_default` tinyint(1) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    echo "<p style='color:green'>Table 'user_addresses' checked/created.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error 'user_addresses': " . $e->getMessage() . "</p>";
}

echo "<p><a href='/my-profile.php'>Go to My Profile</a></p>";
