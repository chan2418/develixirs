<?php
require 'includes/db.php';
echo "Connected!";

 mysql -h 127.0.0.1 -P 3307 -u develixirs_user -p

develixirs_pass

USE develixirs_db;

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