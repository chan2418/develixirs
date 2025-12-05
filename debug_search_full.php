<?php
require_once __DIR__ . '/includes/db.php';

$_GET['q'] = 'face wash';
$_GET['sort'] = 'default';

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = $_GET['sort'] ?? 'default';

$whereParts = ["(is_active = 1 OR is_active IS NULL)"];
$params = [];
$keywords = [];
$keywordGroups = [];

if ($searchQuery !== '') {
    $keywords = explode(' ', $searchQuery);
    
    foreach ($keywords as $index => $word) {
        $word = trim($word);
        if (empty($word)) continue;
        
        $pName = ":sq_name_{$index}";
        $pDesc = ":sq_desc_{$index}";
        $pCat  = ":sq_cat_{$index}";
        
        $keywordGroups[] = "(products.name LIKE {$pName} OR products.description LIKE {$pDesc} OR categories.name LIKE {$pCat})";
        $params[$pName] = '%' . $word . '%';
        $params[$pDesc] = '%' . $word . '%';
        $params[$pCat]  = '%' . $word . '%';
    }
    
    if (!empty($keywordGroups)) {
        $whereParts[] = '(' . implode(' AND ', $keywordGroups) . ')';
    }
}

$whereSql = 'WHERE ' . implode(' AND ', $whereParts);

// Build ORDER BY
$orderBy = 'id DESC';
if ($searchQuery !== '' && $sort === 'default') {
    $relevanceScore = [];
    foreach ($keywords as $index => $word) {
        $word = trim($word);
        if (empty($word)) continue;
        $pRel = ":rel_name_{$index}";
        $relevanceScore[] = "(CASE WHEN products.name LIKE {$pRel} THEN 2 ELSE 0 END)";
        $params[$pRel] = '%' . $word . '%';
    }
    
    if (!empty($relevanceScore)) {
        $orderBy = "(" . implode(' + ', $relevanceScore) . ") DESC, id DESC";
    }
}

$sqlProducts = "
    SELECT products.*, categories.name as category_name
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    {$whereSql}
    ORDER BY {$orderBy}
    LIMIT 10
";

echo "SQL:\n" . $sqlProducts . "\n\n";
echo "Params:\n";
print_r($params);

try {
    $stmt = $pdo->prepare($sqlProducts);
    foreach ($params as $name => $val) {
        $stmt->bindValue($name, $val);
    }
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nResults: " . count($products) . "\n";
    foreach ($products as $p) {
        echo "- {$p['id']}: {$p['name']} (Category: {$p['category_name']})\n";
    }
} catch (PDOException $e) {
    echo "\nError: " . $e->getMessage() . "\n";
}
?>
