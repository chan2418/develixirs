-- ============================================
-- Subscribe & Save System - Multi-Plan Schema
-- ============================================

CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(80) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `badge_text` varchar(80) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `billing_cycle` varchar(20) NOT NULL DEFAULT 'monthly',
  `benefits` text DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `free_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `auto_renew_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `validity_days` int(11) NOT NULL DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_subscription_plan_visibility` (`is_active`,`is_featured`,`display_order`),
  KEY `idx_subscription_plan_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Subscription plan configurations';

INSERT INTO `subscription_plans`
(`slug`, `name`, `short_description`, `badge_text`, `price`, `compare_price`, `discount_percentage`, `billing_cycle`, `benefits`, `display_order`, `is_featured`, `free_shipping`, `is_active`, `auto_renew_enabled`, `validity_days`)
SELECT * FROM (
    SELECT 'glow-monthly', 'Glow Monthly', 'Entry membership for repeat skincare shoppers.', 'Starter', 249.00, 299.00, 5.00, 'monthly', '["5% subscriber discount on products","Priority customer support","Early access to new arrivals","Exclusive subscriber-only offers"]', 1, 0, 0, 1, 1, 30
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `subscription_plans` WHERE slug = 'glow-monthly');

INSERT INTO `subscription_plans`
(`slug`, `name`, `short_description`, `badge_text`, `price`, `compare_price`, `discount_percentage`, `billing_cycle`, `benefits`, `display_order`, `is_featured`, `free_shipping`, `is_active`, `auto_renew_enabled`, `validity_days`)
SELECT * FROM (
    SELECT 'care-quarterly', 'Care Quarterly', 'Best value for regular customers buying every few weeks.', 'Most Popular', 599.00, 749.00, 10.00, 'quarterly', '["10% subscriber discount on products","Priority customer support","Early access to new arrivals","Exclusive subscriber-only offers"]', 2, 1, 0, 1, 1, 90
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `subscription_plans` WHERE slug = 'care-quarterly');

INSERT INTO `subscription_plans`
(`slug`, `name`, `short_description`, `badge_text`, `price`, `compare_price`, `discount_percentage`, `billing_cycle`, `benefits`, `display_order`, `is_featured`, `free_shipping`, `is_active`, `auto_renew_enabled`, `validity_days`)
SELECT * FROM (
    SELECT 'elite-yearly', 'Elite Yearly', 'Highest savings plan for loyal repeat buyers.', 'Best Value', 1799.00, 2199.00, 15.00, 'yearly', '["15% subscriber discount on products","Free shipping on member orders","Priority customer support","Early access to new arrivals","Exclusive subscriber-only offers"]', 3, 0, 1, 1, 1, 365
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `subscription_plans` WHERE slug = 'elite-yearly');

CREATE TABLE IF NOT EXISTS `user_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `status` enum('active','expired','cancelled','pending') NOT NULL DEFAULT 'pending',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
  `plan_name` varchar(100) DEFAULT NULL,
  `price_paid` decimal(10,2) DEFAULT NULL,
  `compare_price_snapshot` decimal(10,2) DEFAULT NULL,
  `discount_percentage_snapshot` decimal(5,2) DEFAULT NULL,
  `billing_cycle_snapshot` varchar(20) DEFAULT NULL,
  `validity_days_snapshot` int(11) DEFAULT NULL,
  `free_shipping_snapshot` tinyint(1) NOT NULL DEFAULT 0,
  `badge_text_snapshot` varchar(80) DEFAULT NULL,
  `benefits_snapshot` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User subscription records';

CREATE TABLE IF NOT EXISTS `subscription_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_subscription_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL COMMENT 'Gateway payment ID',
  `payment_status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_subscription_id` (`user_subscription_id`),
  KEY `user_id` (`user_id`),
  KEY `payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Subscription payment history';

SET @dbname = DATABASE();
SET @tablename = 'users';

SET @columnname = 'is_subscriber';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = @tablename AND table_schema = @dbname AND column_name = @columnname) > 0,
  'SELECT 1',
  'ALTER TABLE users ADD COLUMN is_subscriber TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''Is user currently subscribed'';'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'current_subscription_id';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = @tablename AND table_schema = @dbname AND column_name = @columnname) > 0,
  'SELECT 1',
  'ALTER TABLE users ADD COLUMN current_subscription_id INT(11) NULL COMMENT ''FK to user_subscriptions'';'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'subscription_expires_at';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = @tablename AND table_schema = @dbname AND column_name = @columnname) > 0,
  'SELECT 1',
  'ALTER TABLE users ADD COLUMN subscription_expires_at DATE NULL COMMENT ''Subscription expiry date'';'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
