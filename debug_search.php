<?php
require_once __DIR__ . '/includes/db.php';

$searchQuery = $_GET['q'] ?? 'soap'; // Default to 'soap' if not provided
echo "Testing search for: " . htmlspecialchars($searchQuery) . "<br>";

try {
    $sql = "SELECT id, name, description FROM products WHERE (is_active = 1 OR is_active IS NULL) AND (name LIKE :search_q1 OR description LIKE :search_q2)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':search_q1' => '%' . $searchQuery . '%',
        ':search_q2' => '%' . $searchQuery . '%'
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($results) . " results:<br>";
    echo "<pre>";
    print_r($results);
    echo "</pre>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
