<?php
// admin/products_export.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=products_export_' . date('Y-m-d_H-i') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Output Header Row
fputcsv($output, ['id', 'name', 'sku', 'price', 'compare_price', 'stock', 'description', 'category_id', 'image_url', 'is_active']);

// Fetch all products
try {
    // 1. Get all products
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id ASC");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Handle Images: take the first one if multiple
        $img = '';
        if (!empty($row['images'])) {
            $decoded = json_decode($row['images'], true);
            if (is_array($decoded) && !empty($decoded[0])) {
                $img = $decoded[0]; // First image from JSON
            } else {
                // Determine if it's comma separated or single string
                if (strpos($row['images'], ',') !== false) {
                    $parts = explode(',', $row['images']);
                    $img = trim($parts[0]);
                } else {
                    $img = trim($row['images']);
                }
            }
        }

        // Handle Active Status (if column missing in DB, default to 1)
        $isActive = isset($row['is_active']) ? $row['is_active'] : 1;

        $csvRow = [
            $row['id'],
            $row['name'],
            $row['sku'] ?? '',
            $row['price'],
            $row['compare_price'] ?? '',
            $row['stock'],
            $row['description'],
            $row['category_id'],
            $img,
            $isActive
        ];

        fputcsv($output, $csvRow);
    }

} catch (PDOException $e) {
    // In case of error, maybe write to a log or simple error in CSV
    // Ensure we don't break the download flow too badly
    error_log("Export Error: " . $e->getMessage());
}

fclose($output);
exit;
