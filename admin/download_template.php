<?php
// admin/download_template.php
require_once __DIR__ . '/_auth.php';

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=product_import_template.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Header Row
fputcsv($output, ['id', 'name', 'sku', 'price', 'compare_price', 'stock', 'description', 'category_id', 'image_url', 'is_active']);

// Sample Rows
fputcsv($output, [
    '', 
    'Sample Product', 
    'PROD-001', 
    '199.00', 
    '249.00', 
    '100', 
    'This is a sample product description.', 
    '1', 
    'https://example.com/image.jpg', 
    '1'
]);

fputcsv($output, [
    '', 
    'Second Product', 
    'PROD-002', 
    '599.00', 
    '', 
    '50', 
    'Another product description.', 
    '2', 
    '', 
    '1'
]);

fclose($output);
exit;
