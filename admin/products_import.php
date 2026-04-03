<?php
// admin/products_import.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// Check if file uploaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    
    $file = $_FILES['csv_file'];
    
    // Basic validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("File upload error: " . $file['error']);
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        die("Invalid file type. Please upload a CSV file.");
    }
    
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        die("Could not open file.");
    }
    
    // Read header row
    $header = fgetcsv($handle);
    // Expected: id, name, sku, price, compare_price, stock, description, category_id, image_url, is_active
    
    // Simple column mapping based on index if header matches standard
    // Or we assume standard order if headers are slightly different names
    
    $imported = 0;
    $updated = 0;
    $errors = 0;
    
    try {
        $pdo->beginTransaction();
        
        $stmtInsert = $pdo->prepare("
            INSERT INTO products (name, sku, price, compare_price, stock, description, category_id, images, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmtUpdate = $pdo->prepare("
            UPDATE products 
            SET name=?, sku=?, price=?, compare_price=?, stock=?, description=?, category_id=?, images=?, is_active=?, updated_at=NOW()
            WHERE id=?
        ");

        $stmtCheckCat = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
        
        while (($row = fgetcsv($handle)) !== false) {
            // Check if blank row
            if (empty(implode('', $row))) continue;

            // Map columns (assuming standard template order)
            // 0: id, 1: name, 2: sku, 3: price, 4: compare_price, 5: stock, 6: description, 7: category_id, 8: image_url, 9: is_active
            
            $id = trim($row[0] ?? '');
            $name = trim($row[1] ?? '');
            $sku = trim($row[2] ?? '');
            $price = (float)($row[3] ?? 0);
            $compare_price = !empty($row[4]) ? (float)$row[4] : null;
            $stock = (int)($row[5] ?? 0);
            $description = trim($row[6] ?? '');
            $category_id = (int)($row[7] ?? 0);
            $image_url = trim($row[8] ?? '');
            $is_active = isset($row[9]) ? (int)$row[9] : 1;
            
            if (empty($name) || $price <= 0) {
                // Skip invalid rows
                continue;
            }
            
            // Format Image as JSON array if it's a single URL
            // If already JSON format leave it, otherwise wrap
            if (!empty($image_url)) {
                 $imagesJson = json_encode([$image_url]);
            } else {
                 $imagesJson = '[]';
            }
            
            // Validate Category exists
            if ($category_id > 0) {
                 $stmtCheckCat->execute([$category_id]);
                 if (!$stmtCheckCat->fetch()) {
                     $category_id = null; // Set to Uncategorized/Null if invalid
                 }
            } else {
                 $category_id = null;
            }
            
            if (!empty($id)) {
                // Update existing
                $stmtUpdate->execute([
                    $name, $sku, $price, $compare_price, $stock, $description, $category_id, $imagesJson, $is_active, 
                    $id
                ]);
                $updated++;
            } else {
                // Insert new
                $stmtInsert->execute([
                    $name, $sku, $price, $compare_price, $stock, $description, $category_id, $imagesJson, $is_active
                ]);
                $imported++;
            }
        }
        
        $pdo->commit();
        fclose($handle);
        
        // Redirect back with message
        $msg = "Import successful! Added: $imported, Updated: $updated";
        echo "<script>alert('$msg'); window.location.href='products.php';</script>";
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        fclose($handle);
        die("Import Error: " . $e->getMessage());
    }
} else {
    // Direct access not allowed without POST
    header('Location: products.php');
    exit;
}
