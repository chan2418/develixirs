-- Adds optional scope support for separating normal Blog and Ayurvedh Blog posts.
-- Safe to run multiple times.

SET @db = DATABASE();
SET @table = 'blogs';
SET @column = 'blog_type';

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = @db
        AND TABLE_NAME = @table
        AND COLUMN_NAME = @column
    ),
    'SELECT 1',
    'ALTER TABLE blogs ADD COLUMN blog_type VARCHAR(40) NULL DEFAULT NULL AFTER blog_category_id'
  )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM INFORMATION_SCHEMA.STATISTICS
      WHERE TABLE_SCHEMA = @db
        AND TABLE_NAME = @table
        AND INDEX_NAME = 'idx_blogs_blog_type'
    ),
    'SELECT 1',
    'CREATE INDEX idx_blogs_blog_type ON blogs (blog_type)'
  )
);

PREPARE stmt2 FROM @index_sql;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
