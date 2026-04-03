<?php
// admin/add_category_seo_columns.php
require_once __DIR__ . '/../includes/db.php';

try {
    echo "Adding columns to categories table...\n";

    // 1. meta_title
    try {
        $pdo->exec("ALTER TABLE categories ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL");
        echo "- Added meta_title\n";
    } catch (Exception $e) { echo "- meta_title might already exist\n"; }

    // 2. meta_description
    try {
        $pdo->exec("ALTER TABLE categories ADD COLUMN meta_description TEXT DEFAULT NULL");
        echo "- Added meta_description\n";
    } catch (Exception $e) { echo "- meta_description might already exist\n"; }

    // 3. faqs (JSON)
    try {
        $pdo->exec("ALTER TABLE categories ADD COLUMN faqs TEXT DEFAULT NULL");
        echo "- Added faqs\n";
    } catch (Exception $e) { echo "- faqs might already exist\n"; }

    // 4. media_gallery (JSON)
    try {
        $pdo->exec("ALTER TABLE categories ADD COLUMN media_gallery TEXT DEFAULT NULL");
        echo "- Added media_gallery\n";
    } catch (Exception $e) { echo "- media_gallery might already exist\n"; }

    echo "Done.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
