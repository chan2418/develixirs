-- Add compare_price column to subscription_plans table
SET @dbname = DATABASE();
SET @tablename = "subscription_plans";
SET @columnname = "compare_price";

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = @tablename AND table_schema = @dbname AND column_name = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE subscription_plans ADD COLUMN compare_price DECIMAL(10,2) NULL COMMENT 'Original price before discount' AFTER price;"
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Set a default compare price for the existing plan (optional)
UPDATE subscription_plans SET compare_price = 1500.00 WHERE id = 1 AND compare_price IS NULL;
