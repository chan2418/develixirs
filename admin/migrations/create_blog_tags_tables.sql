-- Migration: Create blog tags system with SEO capabilities
-- Date: 2025-12-10
-- Purpose: Dedicated blog tags (separate from product tags) with full SEO support

-- Blog Tags Table
CREATE TABLE IF NOT EXISTS `blog_tags` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT COMMENT 'Full description displayed on tag archive page',
  `seo_title` VARCHAR(60) COMMENT 'SEO optimized title tag (max 60 chars)',
  `seo_description` VARCHAR(160) COMMENT 'Meta description for search engines (max 160 chars)',
  `seo_image` VARCHAR(255) COMMENT 'Open Graph image for social sharing',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Blog tags with SEO metadata for tag archive pages';

-- Blog Post Tags Junction Table
CREATE TABLE IF NOT EXISTS `blog_post_tags` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `blog_id` INT UNSIGNED NOT NULL,
  `tag_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `blog_tags`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_blog_tag` (`blog_id`, `tag_id`),
  INDEX `idx_blog_id` (`blog_id`),
  INDEX `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Many-to-many relationship between blogs and tags';
