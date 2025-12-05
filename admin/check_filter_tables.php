<?php
require_once __DIR__ . '/../includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Checking Filter Tables...</h2>";

function checkTable($pdo, $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        echo "<p style='color:green'>Table '$table' exists.</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>Table '$table' MISSING or Error: " . $e->getMessage() . "</p>";
        return false;
    }
    return true;
}

$g = checkTable($pdo, 'filter_groups');
$o = checkTable($pdo, 'filter_options');

// Helper to add column if missing
function addColumnIfNeeded($pdo, $table, $col, $def) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $col $def");
            echo "<p style='color:green'>Added column '$col' to '$table'.</p>";
        } else {
            echo "<p style='color:gray'>Column '$col' already exists in '$table'.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error checking/adding '$col' to '$table': " . $e->getMessage() . "</p>";
    }
}

if ($g) {
    echo "<h3>Checking columns for 'filter_groups'...</h3>";
    addColumnIfNeeded($pdo, 'filter_groups', 'is_active', 'TINYINT(1) DEFAULT 1');
    addColumnIfNeeded($pdo, 'filter_groups', 'sort_order', 'INT(11) DEFAULT 0');
    addColumnIfNeeded($pdo, 'filter_groups', 'column_name', 'VARCHAR(100) DEFAULT NULL');
    addColumnIfNeeded($pdo, 'filter_groups', 'param_key', 'VARCHAR(100) NOT NULL');
}

if (!$g || !$o) {
    echo "<h3>Attempting to create missing tables...</h3>";
    try {
        $sql = "
        CREATE TABLE IF NOT EXISTS `filter_groups` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(100) NOT NULL,
          `param_key` varchar(100) NOT NULL,
          `column_name` varchar(100) DEFAULT NULL,
          `sort_order` int(11) DEFAULT 0,
          `is_active` tinyint(1) DEFAULT 1,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `filter_options` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `group_id` int(11) NOT NULL,
          `name` varchar(100) NOT NULL,
          `value` varchar(100) NOT NULL,
          `sort_order` int(11) DEFAULT 0,
          PRIMARY KEY (`id`),
          KEY `group_id` (`group_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $pdo->exec($sql);
        echo "<p style='color:green'>Tables created successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>Failed to create tables: " . $e->getMessage() . "</p>";
    }
}

echo "<p><a href='filter_groups.php'>Go to Product Filters</a></p>";
