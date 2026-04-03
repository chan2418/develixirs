<?php
// admin/migrations/create_media_tables.php
// Run this ONCE to create media library database tables

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

try {
    // 1. media_files table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_files (
            id CHAR(36) PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_filename VARCHAR(255),
            mime_type VARCHAR(100),
            size BIGINT,
            width INT,
            height INT,
            storage_path TEXT,
            cdn_url TEXT,
            thumb_url TEXT,
            alt_text TEXT,
            title VARCHAR(255),
            description TEXT,
            colors JSON,
            exif JSON,
            uploaded_by INT,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL,
            folder_id CHAR(36),
            INDEX idx_uploaded_at (uploaded_at),
            INDEX idx_deleted_at (deleted_at),
            INDEX idx_mime_type (mime_type),
            INDEX idx_uploaded_by (uploaded_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 2. media_tags table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_tags (
            id CHAR(36) PRIMARY KEY,
            name VARCHAR(100) UNIQUE,
            slug VARCHAR(100) UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 3. media_file_tags junction table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_file_tags (
            media_id CHAR(36),
            tag_id CHAR(36),
            PRIMARY KEY (media_id, tag_id),
            FOREIGN KEY (media_id) REFERENCES media_files(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES media_tags(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 4. media_variants table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_variants (
            id CHAR(36) PRIMARY KEY,
            media_id CHAR(36),
            variant_name VARCHAR(50),
            format VARCHAR(10),
            url TEXT,
            width INT,
            height INT,
            size BIGINT,
            INDEX idx_media_id (media_id),
            FOREIGN KEY (media_id) REFERENCES media_files(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 5. media_usage table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_usage (
            id CHAR(36) PRIMARY KEY,
            media_id CHAR(36),
            entity_type VARCHAR(50),
            entity_id INT,
            field_name VARCHAR(100),
            last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_media_id (media_id),
            INDEX idx_entity (entity_type, entity_id),
            FOREIGN KEY (media_id) REFERENCES media_files(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 6. media_versions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_versions (
            id CHAR(36) PRIMARY KEY,
            media_id CHAR(36),
            version_number INT,
            storage_path TEXT,
            url TEXT,
            uploaded_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_media_version (media_id, version_number),
            FOREIGN KEY (media_id) REFERENCES media_files(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 7. media_folders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_folders (
            id CHAR(36) PRIMARY KEY,
            name VARCHAR(255),
            parent_id CHAR(36),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_parent_id (parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo json_encode([
        'success' => true,
        'message' => 'Media tables created successfully!',
        'tables' => [
            'media_files',
            'media_tags',
            'media_file_tags',
            'media_variants',
            'media_usage',
            'media_versions',
            'media_folders'
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
