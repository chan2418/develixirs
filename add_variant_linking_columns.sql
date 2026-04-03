-- Add type and linked_product_id columns to product_variants table
-- Run this SQL on your Hostinger database before uploading the PHP files

ALTER TABLE product_variants 
ADD COLUMN type VARCHAR(20) DEFAULT 'custom' AFTER variant_name,
ADD COLUMN linked_product_id INT NULL AFTER type;
