<?php
require 'includes/db.php';

$stmt = $pdo->query('SELECT id, name, parent_id FROM categories ORDER BY parent_id, name');
$categories = $stmt->fetchAll();

foreach ($categories as $cat) {
    $indent = $cat['parent_id'] > 0 ? '  ' : '';
    echo $indent . 'ID: ' . $cat['id'] . ' | Name: ' . $cat['name'] . ' | Parent: ' . ($cat['parent_id'] ?: 'TOP') . PHP_EOL;
}
