<?php
// create_groups_table_v2.php
// Adaptive script to create product_group_map with correct Foreign Key types

require_once __DIR__ . '/includes/db.php';

function getColumnDetails($pdo, $table, $column) {
    $stmt = $pdo->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if ($col['Field'] === $column) {
            return $col;
        }
    }
    return null;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Database Setup: Product Groups</h1>";

    // 1. Ensure product_groups exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS product_groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p>✅ Table <code>product_groups</code> checks out.</p>";

    // 1.5 Ensure 'image' column exists in product_groups
    $imgCol = getColumnDetails($pdo, 'product_groups', 'image');
    if (!$imgCol) {
        $pdo->exec("ALTER TABLE product_groups ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER name");
        echo "<p>✅ Added <code>image</code> column to <code>product_groups</code>.</p>";
    } else {
        echo "<p>ℹ️ Column <code>image</code> already exists.</p>";
    }

    // 2. Introspect 'products.id'
    $prodIdMeta = getColumnDetails($pdo, 'products', 'id');
    if (!$prodIdMeta) throw new Exception("Could not find 'products.id' column.");

    $prodIdType = $prodIdMeta['Type']; // e.g., 'int(11)' or 'bigint(20) unsigned'
    $isProdUnsigned = stripos($prodIdType, 'unsigned') !== false;
    
    // 3. Introspect 'product_groups.id'
    $groupIdMeta = getColumnDetails($pdo, 'product_groups', 'id');
    $groupIdType = $groupIdMeta['Type'];
    $isGroupUnsigned = stripos($groupIdType, 'unsigned') !== false;

    echo "<p>ℹ️ Detected <code>products.id</code> type: <strong>$prodIdType</strong></p>";
    echo "<p>ℹ️ Detected <code>product_groups.id</code> type: <strong>$groupIdType</strong></p>";

    // 4. Construct SQL for product_group_map
    // We must match the types EXACTLY for Foreign Keys.
    
    // Extract base types (int, bigint, etc)
    $prodBaseType = preg_replace('/\(.*\)/', '', $prodIdType); // remove length for cleaner matching if needed, but MySQL usually prefers exact copy
    // Actually, for CREATE TABLE, we usually just want 'INT' or 'BIGINT' plus 'UNSIGNED' if applicable.
    
    // Helper to build definition
    $buildDef = function($fullType) {
        $upper = strtoupper($fullType);
        $base = 'INT';
        if (strpos($upper, 'BIGINT') !== false) $base = 'BIGINT';
        elseif (strpos($upper, 'MEDIUMINT') !== false) $base = 'MEDIUMINT';
        elseif (strpos($upper, 'SMALLINT') !== false) $base = 'SMALLINT';
        elseif (strpos($upper, 'TINYINT') !== false) $base = 'TINYINT';
        
        $unsigned = (strpos($upper, 'UNSIGNED') !== false) ? 'UNSIGNED' : '';
        return "$base $unsigned";
    };

    $prodColDef = $buildDef($prodIdType);
    $groupColDef = $buildDef($groupIdType);

    echo "<p>🛠 Constructing map table with: <br>product_id: $prodColDef<br>group_id: $groupColDef</p>";

    $sql = "
        CREATE TABLE IF NOT EXISTS product_group_map (
            product_id $prodColDef NOT NULL,
            group_id $groupColDef NOT NULL,
            PRIMARY KEY (product_id, group_id),
            CONSTRAINT fk_pg_map_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            CONSTRAINT fk_pg_map_group FOREIGN KEY (group_id) REFERENCES product_groups(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $pdo->exec($sql);
    echo "<p>✅ Table <code>product_group_map</code> created successfully!</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    // Fallback proposal: Create without FKs?
    echo "<p>Attempting fallback (Tables without Foreign Key constraints)...</p>";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS product_group_map (
                product_id INT NOT NULL,
                group_id INT NOT NULL,
                PRIMARY KEY (product_id, group_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        echo "<p>✅ Fallback: Table created WITHOUT Foreign Key constraints. (Data integrity not enforced at DB level).</p>";
    } catch (Exception $e2) {
        echo "<p style='color:red'>❌ Fallback failed: " . $e2->getMessage() . "</p>";
    }
}
