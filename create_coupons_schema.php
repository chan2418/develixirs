<?php
/**
 * Database Migration: Create Coupons System Tables
 * Run this file once to create all required tables for the offers & coupons system
 */

require_once __DIR__ . '/includes/db.php';

try {
    echo "<h2>Creating Coupons System Tables...</h2>";
    
    // 0. Drop existing tables to ensure clean state and consistent types
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS coupon_usage");
    $pdo->exec("DROP TABLE IF EXISTS coupon_products");
    $pdo->exec("DROP TABLE IF EXISTS coupon_categories");
    $pdo->exec("DROP TABLE IF EXISTS coupons");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<p>✓ Dropped existing coupon tables to ensure fresh start</p>";
    
    // 1. Create coupons table
    // Using INT UNSIGNED for id to be consistent with modern practices
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS coupons (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            code VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            discount_type ENUM('percentage', 'flat') NOT NULL DEFAULT 'percentage',
            discount_value DECIMAL(10,2) NOT NULL,
            max_discount_limit DECIMAL(10,2) NULL,
            min_purchase DECIMAL(10,2) NULL,
            offer_type ENUM('first_user', 'cart_value', 'festival', 'product_specific', 'category_specific', 'universal') NOT NULL DEFAULT 'universal',
            usage_limit_per_user ENUM('once', 'unlimited') NOT NULL DEFAULT 'once',
            can_be_clubbed TINYINT(1) NOT NULL DEFAULT 0,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_code (code),
            INDEX idx_status (status),
            INDEX idx_offer_type (offer_type),
            INDEX idx_dates (start_date, end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✓ Created 'coupons' table</p>";
    
    // 2. Create coupon_usage table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS coupon_usage (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            coupon_id INT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            discount_amount DECIMAL(10,2) NOT NULL,
            used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_coupon_user (coupon_id, user_id),
            INDEX idx_user (user_id),
            INDEX idx_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✓ Created 'coupon_usage' table</p>";
    
    // 3. Create coupon_products table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS coupon_products (
            coupon_id INT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (coupon_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✓ Created 'coupon_products' table</p>";
    
    // 4. Create coupon_categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS coupon_categories (
            coupon_id INT UNSIGNED NOT NULL,
            category_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (coupon_id, category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✓ Created 'coupon_categories' table</p>";
    
    // ---------------------------------------------------------
    // ADD FOREIGN KEYS SAFELY
    // ---------------------------------------------------------
    
    // Helper function to add FK safely
    function addForeignKey($pdo, $table, $column, $refTable, $refColumn, $constraintName) {
        try {
            // Check if constraint exists
            $check = $pdo->prepare("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_NAME = ?
            ");
            $check->execute([$table, $constraintName]);
            
            if ($check->rowCount() == 0) {
                $pdo->exec("
                    ALTER TABLE $table
                    ADD CONSTRAINT $constraintName
                    FOREIGN KEY ($column) REFERENCES $refTable($refColumn) ON DELETE CASCADE
                ");
                echo "<p>✓ Added FK: $table.$column -> $refTable.$refColumn</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠ Could not add FK $constraintName: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Add Coupon FKs (Internal integrity)
    addForeignKey($pdo, 'coupon_usage', 'coupon_id', 'coupons', 'id', 'fk_usage_coupon');
    addForeignKey($pdo, 'coupon_products', 'coupon_id', 'coupons', 'id', 'fk_products_coupon');
    addForeignKey($pdo, 'coupon_categories', 'coupon_id', 'coupons', 'id', 'fk_categories_coupon');
    
    // Add External FKs (Integrity with existing tables)
    // We try these but don't fail if types don't match
    addForeignKey($pdo, 'coupon_usage', 'user_id', 'users', 'id', 'fk_usage_user');
    addForeignKey($pdo, 'coupon_products', 'product_id', 'products', 'id', 'fk_products_product');
    addForeignKey($pdo, 'coupon_categories', 'category_id', 'categories', 'id', 'fk_categories_category');
    
    // 5. Add coupon columns to orders table
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'coupon_code'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("
                ALTER TABLE orders
                ADD COLUMN coupon_code VARCHAR(50) NULL AFTER total,
                ADD COLUMN coupon_discount DECIMAL(10,2) NULL DEFAULT 0 AFTER coupon_code
            ");
            echo "<p>✓ Added coupon columns to 'orders' table</p>";
        } else {
            echo "<p>✓ Coupon columns already exist in 'orders' table</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠ Could not update orders table: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h3 style='color: green;'>✓ Database migration completed!</h3>";
    echo "<p><a href='admin/coupons.php'>Go to Manage Coupons</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    die();
}
