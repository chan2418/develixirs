<?php
// admin/setup_page_builder.php
require_once __DIR__ . '/../includes/db.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        type VARCHAR(50) DEFAULT 'custom',
        status VARCHAR(20) DEFAULT 'draft', -- draft, published, scheduled
        content JSON,
        meta_title VARCHAR(255),
        meta_description TEXT,
        meta_keywords TEXT,
        is_public BOOLEAN DEFAULT 1,
        published_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "Page Builder table 'pages' created successfully!";

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
