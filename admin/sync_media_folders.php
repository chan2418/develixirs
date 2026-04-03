<?php
// admin/sync_media_folders.php
// One-time sync to organize all existing images into folders
// SELF-CONTAINED VERSION WITH IMAGE IMPORT

ob_start();
session_start();

// 1. Manual Auth Check
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// 2. Database Connection
require_once __DIR__ . '/../includes/db.php';

// 3. Helper Functions
function localGetOrCreateFolder($pdo, $folderPath) {
    if (!$folderPath) return null;
    
    $parts = array_filter(explode('/', $folderPath));
    $parentId = null;
    
    foreach ($parts as $folderName) {
        $folderName = trim($folderName);
        if (!$folderName) continue;
        
        if ($parentId) {
            $stmt = $pdo->prepare("SELECT id FROM media_folders WHERE name = ? AND parent_id = ?");
            $stmt->execute([$folderName, $parentId]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM media_folders WHERE name = ? AND (parent_id IS NULL OR parent_id = '')");
            $stmt->execute([$folderName]);
        }
        
        $folderId = $stmt->fetchColumn();
        
        if (!$folderId) {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            $newId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
            
            $stmt = $pdo->prepare("INSERT INTO media_folders (id, name, parent_id) VALUES (?, ?, ?)");
            $stmt->execute([$newId, $folderName, $parentId]);
            $folderId = $newId;
        }
        
        $parentId = $folderId;
    }
    
    return $parentId;
}

function localImportImage($pdo, $imagePath, $folderId) {
    if (!$imagePath || !$folderId) return false;
    
    // Clean path
    $imagePath = ltrim($imagePath, '/');
    $fullPath = __DIR__ . '/../' . $imagePath;
    
    if (!file_exists($fullPath)) return false;
    
    // Check if already in DB
    $stmt = $pdo->prepare("SELECT id FROM media_files WHERE storage_path = ?");
    $stmt->execute([$imagePath]);
    $existingId = $stmt->fetchColumn();
    
    if ($existingId) {
        // Update folder
        $pdo->prepare("UPDATE media_files SET folder_id = ? WHERE id = ?")->execute([$folderId, $existingId]);
        return true;
    }
    
    // Register new file
    $fileSize = filesize($fullPath);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fullPath);
    finfo_close($finfo);
    
    $width = null; $height = null;
    if (strpos($mimeType, 'image') === 0) {
        $info = @getimagesize($fullPath);
        if ($info) { $width = $info[0]; $height = $info[1]; }
    }
    
    // UUID
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    $mediaId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    
    $filename = basename($imagePath);
    
    $stmt = $pdo->prepare("INSERT INTO media_files (id, filename, original_filename, mime_type, size, width, height, storage_path, cdn_url, folder_id, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$mediaId, $filename, $filename, $mimeType, $fileSize, $width, $height, $imagePath, '/'.$imagePath, $folderId, 1]);
    
    return true;
}

// 4. Main Execution
set_time_limit(600); // 10 minutes
ob_clean();
header('Content-Type: application/json');

$results = [
    'products' => 0,
    'categories' => 0,
    'banners' => 0,
    'images_processed' => 0,
    'errors' => []
];

try {
    // A. PRODUCTS
    $start = 0;
    while(true) {
        $stmt = $pdo->prepare("SELECT id, name, images, product_media FROM products LIMIT ?, 50");
        $stmt->bindValue(1, $start, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!$products) break;
        
        foreach ($products as $p) {
            $fid = localGetOrCreateFolder($pdo, "Products/{$p['name']}");
            if ($fid) {
                // Main images JSON
                if (!empty($p['images'])) {
                    $imgs = json_decode($p['images'], true);
                    if (is_array($imgs)) {
                        foreach ($imgs as $img) {
                            if ($img) {
                                // Path: assets/uploads/products/{img}
                                $path = 'assets/uploads/products/' . $img;
                                if(localImportImage($pdo, $path, $fid)) $results['images_processed']++;
                            }
                        }
                    }
                }
                // Product Media JSON
                if (!empty($p['product_media'])) {
                    $media = json_decode($p['product_media'], true);
                    if (is_array($media)) {
                        foreach ($media as $m) {
                            if (isset($m['path'])) {
                                // Path: assets/uploads/product_media/{path}
                                $path = 'assets/uploads/product_media/' . $m['path'];
                                if(localImportImage($pdo, $path, $fid)) $results['images_processed']++;
                            } elseif (isset($m['url'])) {
                                // Fallback for older format if any
                                $path = ltrim($m['url'], '/'); 
                                if(localImportImage($pdo, $path, $fid)) $results['images_processed']++;
                            }
                        }
                    }
                }
                $results['products']++;
            }
        }
        $start += 50;
    }

    // B. CATEGORIES
    $results['log'][] = 'Start: Categories Sync...';
    $stmt = $pdo->query("SELECT id, name, image FROM categories");
    while ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($c['image'])) {
            $fid = localGetOrCreateFolder($pdo, "Categories/{$c['name']}");
            if ($fid) {
                // Path: assets/uploads/categories/{image}
                $path = 'assets/uploads/categories/' . $c['image'];
                if(localImportImage($pdo, $path, $fid)) {
                    $results['images_processed']++;
                } else {
                    $results['errors'][] = "Cat Image Failed: {$c['name']} - File not found at $path";
                }
                $results['categories']++;
            }
        }
    }

    // C. BANNERS
    $results['log'][] = 'Start: Custom Banner Organization...';
    try {
        // Fetch banners with their slot info
        $stmt = $pdo->query("SELECT filename, page_slot FROM banners");
        $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results['log'][] = 'Found ' . count($banners) . ' banners';

        foreach ($banners as $b) {
            $filename = $b['filename'];
            $slot = $b['page_slot'] ?? 'home';
            
            if (!$filename) continue;

            // Determine Folder Path based on Slot
            $folderPath = 'Homepage/Banners'; // Default

            if (strpos($slot, 'home') !== false) {
                $folderPath = 'Homepage/Banners';
            } elseif (strpos($slot, 'product') !== false) {
                $folderPath = 'Products Page/Banners';
            } elseif (strpos($slot, 'category') !== false || strpos($slot, 'top_category') !== false) {
                $folderPath = 'Categories Page/Banners';
            } elseif (strpos($slot, 'blog') !== false) {
                $folderPath = 'Blog Page/Banners';
            } else {
                $folderPath = 'Other/Banners';
            }

            // Get/Create Folder
            $fid = localGetOrCreateFolder($pdo, $folderPath);
            
            if ($fid) {
                // Path: assets/uploads/banners/{filename}
                $path = 'assets/uploads/banners/' . $filename;
                
                // Import
                if(localImportImage($pdo, $path, $fid)) {
                    $results['images_processed']++;
                }
                $results['banners_processed']++;
            }
        }
        $results['log'][] = 'Banner sync complete';
    } catch (Exception $e) {
        $results['errors'][] = "Banner: " . $e->getMessage();
    }
    
    echo json_encode(['success' => true, 'results' => $results]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'results' => $results]);
}

ob_end_flush();
