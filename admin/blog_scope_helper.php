<?php

if (!function_exists('admin_blog_scope_normalize')) {
    function admin_blog_scope_normalize(?string $scope): string
    {
        $value = strtolower(trim((string)$scope));
        return $value === 'ayurvedh' ? 'ayurvedh' : 'blog';
    }
}

if (!function_exists('admin_blog_scope_from_request')) {
    function admin_blog_scope_from_request(): string
    {
        $raw = $_POST['blog_scope'] ?? ($_GET['scope'] ?? '');
        return admin_blog_scope_normalize((string)$raw);
    }
}

if (!function_exists('admin_blog_scope_is_ayurvedh')) {
    function admin_blog_scope_is_ayurvedh(string $scope): bool
    {
        return admin_blog_scope_normalize($scope) === 'ayurvedh';
    }
}

if (!function_exists('admin_blog_scope_page_title')) {
    function admin_blog_scope_page_title(string $scope): string
    {
        return admin_blog_scope_is_ayurvedh($scope) ? 'Ayurvedh Blog' : 'Blog';
    }
}

if (!function_exists('admin_blog_scope_posts_label')) {
    function admin_blog_scope_posts_label(string $scope): string
    {
        return admin_blog_scope_is_ayurvedh($scope) ? 'Ayurvedh Posts' : 'Blog Posts';
    }
}

if (!function_exists('admin_blog_scope_post_label')) {
    function admin_blog_scope_post_label(string $scope): string
    {
        return admin_blog_scope_is_ayurvedh($scope) ? 'Ayurvedh Post' : 'Blog Post';
    }
}

if (!function_exists('admin_blog_scope_query_string')) {
    function admin_blog_scope_query_string(string $scope): string
    {
        return admin_blog_scope_is_ayurvedh($scope) ? 'scope=ayurvedh' : '';
    }
}

if (!function_exists('admin_blog_scope_url')) {
    function admin_blog_scope_url(string $path, string $scope, array $extra = []): string
    {
        $params = [];
        if (admin_blog_scope_is_ayurvedh($scope)) {
            $params['scope'] = 'ayurvedh';
        }
        foreach ($extra as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $params[(string)$key] = (string)$value;
        }
        if (empty($params)) {
            return $path;
        }
        return $path . '?' . http_build_query($params);
    }
}

if (!function_exists('admin_blog_scope_categories_label')) {
    function admin_blog_scope_categories_label(string $scope): string
    {
        return admin_blog_scope_is_ayurvedh($scope) ? 'Ayurvedh Categories' : 'Blog Categories';
    }
}

if (!function_exists('admin_blog_scope_category_label')) {
    function admin_blog_scope_category_label(string $scope): string
    {
        return admin_blog_scope_is_ayurvedh($scope) ? 'Ayurvedh Category' : 'Blog Category';
    }
}

if (!function_exists('admin_blog_scope_tags_label')) {
    function admin_blog_scope_tags_label(string $scope): string
    {
        return admin_blog_scope_is_ayurvedh($scope) ? 'Ayurvedh Tags' : 'Blog Tags';
    }
}

if (!function_exists('admin_blog_scope_tag_label')) {
    function admin_blog_scope_tag_label(string $scope): string
    {
        return admin_blog_scope_is_ayurvedh($scope) ? 'Ayurvedh Tag' : 'Blog Tag';
    }
}

if (!function_exists('admin_blog_scope_db_value')) {
    function admin_blog_scope_db_value(string $scope): ?string
    {
        return admin_blog_scope_is_ayurvedh($scope) ? 'ayurvedh' : null;
    }
}

if (!function_exists('admin_blog_ensure_scope_column')) {
    function admin_blog_ensure_scope_column(PDO $pdo): bool
    {
        static $checked = false;
        static $available = false;

        if ($checked) {
            return $available;
        }
        $checked = true;

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM blogs LIKE 'blog_type'");
            if ($stmt && $stmt->fetch(PDO::FETCH_ASSOC)) {
                $available = true;
                return true;
            }

            $pdo->exec("ALTER TABLE blogs ADD COLUMN blog_type VARCHAR(40) NULL DEFAULT NULL AFTER blog_category_id");
            try {
                $pdo->exec("CREATE INDEX idx_blogs_blog_type ON blogs (blog_type)");
            } catch (PDOException $e) {
                // index may already exist; safe to ignore
            }
            $available = true;
            return true;
        } catch (PDOException $e) {
            error_log('Blog scope column setup failed: ' . $e->getMessage());
            $available = false;
            return false;
        }
    }
}

if (!function_exists('admin_blog_scope_filter_clause')) {
    function admin_blog_scope_filter_clause(string $scope, string $column = 'blog_type'): array
    {
        $normalized = admin_blog_scope_normalize($scope);
        if ($normalized === 'ayurvedh') {
            return [
                "LOWER(COALESCE({$column}, '')) = :blog_scope_type",
                [':blog_scope_type' => 'ayurvedh'],
            ];
        }

        return [
            "({$column} IS NULL OR {$column} = '' OR LOWER({$column}) = 'blog')",
            [],
        ];
    }
}

if (!function_exists('admin_blog_scope_taxonomy_value')) {
    function admin_blog_scope_taxonomy_value(string $scope): ?string
    {
        return admin_blog_scope_is_ayurvedh($scope) ? 'ayurvedh' : null;
    }
}

if (!function_exists('admin_blog_ensure_category_scope_column')) {
    function admin_blog_ensure_category_scope_column(PDO $pdo): bool
    {
        static $checked = false;
        static $available = false;

        if ($checked) {
            return $available;
        }
        $checked = true;

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM blog_categories LIKE 'blog_scope'");
            if ($stmt && $stmt->fetch(PDO::FETCH_ASSOC)) {
                $available = true;
                return true;
            }

            $pdo->exec("ALTER TABLE blog_categories ADD COLUMN blog_scope VARCHAR(40) NULL DEFAULT NULL AFTER description");
            try {
                $pdo->exec("CREATE INDEX idx_blog_categories_scope ON blog_categories (blog_scope)");
            } catch (PDOException $e) {
                // safe ignore
            }
            $available = true;
            return true;
        } catch (PDOException $e) {
            error_log('Blog categories scope column setup failed: ' . $e->getMessage());
            $available = false;
            return false;
        }
    }
}

if (!function_exists('admin_blog_ensure_tag_scope_column')) {
    function admin_blog_ensure_tag_scope_column(PDO $pdo): bool
    {
        static $checked = false;
        static $available = false;

        if ($checked) {
            return $available;
        }
        $checked = true;

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM blog_tags LIKE 'blog_scope'");
            if ($stmt && $stmt->fetch(PDO::FETCH_ASSOC)) {
                $available = true;
                return true;
            }

            $pdo->exec("ALTER TABLE blog_tags ADD COLUMN blog_scope VARCHAR(40) NULL DEFAULT NULL AFTER seo_image");
            try {
                $pdo->exec("CREATE INDEX idx_blog_tags_scope ON blog_tags (blog_scope)");
            } catch (PDOException $e) {
                // safe ignore
            }
            $available = true;
            return true;
        } catch (PDOException $e) {
            error_log('Blog tags scope column setup failed: ' . $e->getMessage());
            $available = false;
            return false;
        }
    }
}

if (!function_exists('admin_blog_scope_taxonomy_filter_clause')) {
    function admin_blog_scope_taxonomy_filter_clause(string $scope, string $column = 'blog_scope', string $paramPrefix = 'tax_scope'): array
    {
        $normalized = admin_blog_scope_normalize($scope);
        if ($normalized === 'ayurvedh') {
            $key = ':' . $paramPrefix;
            return [
                "LOWER(COALESCE({$column}, '')) = {$key}",
                [$key => 'ayurvedh'],
            ];
        }

        return [
            "({$column} IS NULL OR {$column} = '' OR LOWER({$column}) = 'blog')",
            [],
        ];
    }
}
