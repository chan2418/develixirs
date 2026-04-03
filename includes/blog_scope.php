<?php

if (!function_exists('site_blog_scope_normalize')) {
    function site_blog_scope_normalize(?string $scope): string
    {
        $value = strtolower(trim((string)$scope));
        $ayurvedhAliases = ['ayurvedh', 'ayurvedha', 'ayurvedic', 'ayurvedhic'];
        return in_array($value, $ayurvedhAliases, true) ? 'ayurvedh' : 'blog';
    }
}

if (!function_exists('site_blog_scope_is_ayurvedh')) {
    function site_blog_scope_is_ayurvedh(string $scope): bool
    {
        return site_blog_scope_normalize($scope) === 'ayurvedh';
    }
}

if (!function_exists('site_blog_scope_supports_subcategories')) {
    function site_blog_scope_supports_subcategories(string $scope): bool
    {
        return site_blog_scope_is_ayurvedh($scope);
    }
}

if (!function_exists('site_blog_scope_from_request')) {
    function site_blog_scope_from_request(): string
    {
        $raw = $_GET['scope'] ?? '';
        return site_blog_scope_normalize((string)$raw);
    }
}

if (!function_exists('site_blog_scope_list_path')) {
    function site_blog_scope_list_path(string $scope): string
    {
        return site_blog_scope_is_ayurvedh($scope) ? 'ayurvedha_blog.php' : 'blog.php';
    }
}

if (!function_exists('site_blog_scope_append_query')) {
    function site_blog_scope_append_query(string $path, array $params = []): string
    {
        $clean = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $clean[(string)$key] = (string)$value;
        }
        if (empty($clean)) {
            return $path;
        }
        return $path . '?' . http_build_query($clean);
    }
}

if (!function_exists('site_blog_scope_type_column_exists')) {
    function site_blog_scope_type_column_exists(PDO $pdo): bool
    {
        static $checked = false;
        static $exists = false;

        if ($checked) {
            return $exists;
        }
        $checked = true;

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM blogs LIKE 'blog_type'");
            $exists = (bool)($stmt && $stmt->fetch(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            $exists = false;
        }

        return $exists;
    }
}

if (!function_exists('site_blog_scope_category_scope_column_exists')) {
    function site_blog_scope_category_scope_column_exists(PDO $pdo): bool
    {
        static $checked = false;
        static $exists = false;

        if ($checked) {
            return $exists;
        }
        $checked = true;

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM blog_categories LIKE 'blog_scope'");
            $exists = (bool)($stmt && $stmt->fetch(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            $exists = false;
        }

        return $exists;
    }
}

if (!function_exists('site_blog_scope_category_parent_column_exists')) {
    function site_blog_scope_category_parent_column_exists(PDO $pdo): bool
    {
        static $checked = false;
        static $exists = false;

        if ($checked) {
            return $exists;
        }
        $checked = true;

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM blog_categories LIKE 'parent_id'");
            $exists = (bool)($stmt && $stmt->fetch(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            $exists = false;
        }

        return $exists;
    }
}

if (!function_exists('site_blog_scope_taxonomy_filter_clause')) {
    function site_blog_scope_taxonomy_filter_clause(string $scope, string $column = 'blog_scope', string $paramName = ':blog_scope_type'): array
    {
        if (site_blog_scope_is_ayurvedh($scope)) {
            return [
                "LOWER(COALESCE({$column}, '')) = {$paramName}",
                [$paramName => 'ayurvedh'],
            ];
        }

        return [
            "({$column} IS NULL OR {$column} = '' OR LOWER({$column}) = 'blog')",
            [],
        ];
    }
}

if (!function_exists('site_blog_scope_filter_clause')) {
    function site_blog_scope_filter_clause(string $scope, string $column = 'b.blog_type'): array
    {
        if (site_blog_scope_is_ayurvedh($scope)) {
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
