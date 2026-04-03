-- Add published_at column to blogs table
SET @dbname = DATABASE();
SET @tablename = "blogs";
SET @columnname = "published_at";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE blogs ADD COLUMN published_at DATETIME NULL;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Backfill: For existing published posts, set published_at = created_at if it's NULL
UPDATE blogs SET published_at = created_at WHERE is_published = 1 AND published_at IS NULL;
