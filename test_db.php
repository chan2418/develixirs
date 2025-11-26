<?php
require 'includes/db.php';
echo "Connected!";

 mysql -h 127.0.0.1 -P 3307 -u develixirs_user -p

develixirs_pass

USE develixirs_db;


SELECT *
FROM products
WHERE (is_active = 1 OR is_active IS NULL)
  AND category_id = 9;
UPDATE users
SET password = '$2y$10$wB0xNxGr6nqPivhmTdQWxeGfK9iUlW129N4.hO.V1HQ0i5R9vIjXi'
WHERE email = 'admin@admin.com';

-- 1) Add parent_id column if missing
ALTER TABLE categories
  ADD COLUMN IF NOT EXISTS parent_id INT UNSIGNED DEFAULT NULL;

-- 2) (Optional) If you don't have title but have name, copy name -> title
-- If title exists skip this
-- UPDATE categories SET title = name WHERE (title IS NULL OR title = '') AND (name IS NOT NULL AND name <> '');

-- 3) Make sure slug is unique (if not already UNIQUE)
ALTER TABLE categories
  ADD UNIQUE KEY if_not_exists_slug_unique (slug(191));



mysql> INSERT INTO product_reviews (product_id, reviewer_name, reviewer_email, rating, comment, status)
    -> VALUES
    -> (1, 'Arun Kumar', 'arun@gmail.com', 4.5, 'Very good quality product!', 'approved'),
    -> (1, 'Meera', 'meera@example.com', 3.0, 'Average experience.', 'pending'),
ERROR 1452 (23000): Cannot add or update a child row: a foreign key constraint fails (`develixirs_db`.`product_reviews`, CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE)
mysql> 



-- 1) Show the user row (check email and id and the stored password)
SELECT id, email, name, password, role ,LENGTH(password) AS pass_len
FROM users
WHERE email = 'admin@admin.com'
LIMIT 1;

-- 2) If you think the admin account is different, list first 10 users
SELECT id, email, name, password, LENGTH(password) AS pass_len FROM users ORDER BY id LIMIT 10;


docker exec -it <mysql-container> mysql -u root -p -e "SHOW DATABASES;"



$2y$10$abcdefgABCDEFG1234567890abcdefgABCDEFG1234567890



    SELECT *
    FROM your_table_name;



ALTER TABLE banners
  ADD COLUMN page_slot VARCHAR(32) NOT NULL DEFAULT 'home';





ALTER TABLE `banners`
  ADD COLUMN `category_id` INT NULL AFTER `page_slot`;





  -- All possible tags (New, Trending, Sale, etc.)
CREATE TABLE tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Link table: which product has which tags
CREATE TABLE product_tags (
  product_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY (product_id, tag_id),
  CONSTRAINT fk_product_tags_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_product_tags_tag
    FOREIGN KEY (tag_id) REFERENCES tags(id)
    ON DELETE CASCADE
);


CREATE TABLE filter_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filter_key VARCHAR(50) NOT NULL,   -- 'color', 'size', 'range'
    value VARCHAR(100) NOT NULL,       -- actual value used in products table
    label VARCHAR(100) NOT NULL,       -- text displayed in UI
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE filter_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,        -- FK to filter_groups.id
    value VARCHAR(100) NOT NULL,  -- stored in products.column_name
    label VARCHAR(100) NOT NULL,  -- UI text
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    CONSTRAINT fk_group FOREIGN KEY (group_id)
        REFERENCES filter_groups(id)
        ON DELETE CASCADE
);




DELIMITER //

CREATE TRIGGER trg_products_bi_category_name
BEFORE INSERT ON products
FOR EACH ROW
BEGIN
  DECLARE v_cat_title VARCHAR(255);

  IF NEW.category_id IS NOT NULL THEN
    SELECT title
    INTO v_cat_title
    FROM categories
    WHERE id = NEW.category_id
    LIMIT 1;

    SET NEW.category_name = v_cat_title;
  ELSE
    SET NEW.category_name = NULL;
  END IF;
END//

DELIMITER ;




DELIMITER $$

CREATE TRIGGER trg_products_bi_category_name
BEFORE INSERT ON products
FOR EACH ROW
BEGIN
  DECLARE v_cat_title VARCHAR(255);

  IF NEW.category_id IS NOT NULL THEN
    SELECT title
    INTO v_cat_title
    FROM categories
    WHERE id = NEW.category_id
    LIMIT 1;

    SET NEW.category_name = v_cat_title;
  ELSE
    SET NEW.category_name = NULL;
  END IF;
END$$

DELIMITER ;


