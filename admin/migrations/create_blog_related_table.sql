-- Migration: Create blog_related table for manual related articles selection
-- Date: 2025-12-10

CREATE TABLE IF NOT EXISTS `blog_related` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `blog_id` INT UNSIGNED NOT NULL COMMENT 'The main blog post ID',
  `related_blog_id` INT UNSIGNED NOT NULL COMMENT 'The related blog post ID',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`related_blog_id`) REFERENCES `blogs`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_relation` (`blog_id`, `related_blog_id`),
  INDEX `idx_blog_id` (`blog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores manually selected related articles for blog posts';
