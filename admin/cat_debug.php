<?php
// admin/cat_debug.php
require_once __DIR__ . '/_auth.php'; // Protect this file
require_once __DIR__ . '/../includes/db.php';

echo '<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">';
echo '<div class="p-10">';
echo '<h1 class="text-2xl font-bold mb-5">Category Image Debugger</h1>';
echo '<p class="mb-5 text-gray-600">This table shows exactly what is in the database for your categories.</p>';

echo '<table class="w-full border-collapse border border-gray-300 text-sm">';
echo '<tr class="bg-gray-100">';
echo '<th class="border p-2">ID</th>';
echo '<th class="border p-2">Name</th>';
echo '<th class="border p-2">Parent ID</th>';
echo '<th class="border p-2">Raw Image Value (DB)</th>';
echo '<th class="border p-2">Status</th>';
echo '</tr>';

try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY parent_id, id");
    $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cats as $c) {
        $imgRaw = $c['image'];
        $status = '';
        
        $rowClass = ($c['parent_id'] == 0 || $c['parent_id'] === null) ? "bg-blue-50" : "";
        $isRoot = ($c['parent_id'] == 0 || $c['parent_id'] === null);

        if (empty($imgRaw)) {
            $status = '<span class="text-red-500 font-bold">EMPTY (Will show Placeholder)</span>';
        } else {
            $val = trim($imgRaw);
            if ($val === '') {
                 $status = '<span class="text-red-500 font-bold">WHITESPACE ONLY (Will show Placeholder)</span>';
            } else {
                 $status = '<span class="text-green-600 font-bold">HAS IMAGE</span>';
            }
        }
        
        // Only verify file if it's not a URL
        if (!empty($imgRaw) && strpos($imgRaw, 'http') === false) {
             // Basic path check helper
             $path = $imgRaw;
             if (strpos($path, 'assets/') !== 0 && strpos($path, '/') !== 0) {
                 $path = 'assets/uploads/categories/' . $path;
             }
             $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');
             
             if (!file_exists($fullPath)) {
                 $status .= '<br><span class="text-xs text-red-600">File Not Found on Server!</span>';
                 $status .= '<br><span class="text-xs text-gray-400">Path: ' . htmlspecialchars($fullPath) . '</span>';
             } else {
                 $status .= '<br><span class="text-xs text-green-600">File Exists on Server</span>';
             }
        }

        echo "<tr class='$rowClass hover:bg-gray-50'>";
        echo "<td class='border p-2'>{$c['id']}</td>";
        echo "<td class='border p-2'>" . htmlspecialchars($c['name'] ?? $c['title'] ?? '???') . ($isRoot ? ' <b>(ROOT)</b>' : '') . "</td>";
        echo "<td class='border p-2'>" . ($c['parent_id'] ?? 'NULL') . "</td>";
        echo "<td class='border p-2 font-mono text-blue-600'>" . htmlspecialchars($imgRaw ?? 'NULL') . "</td>";
        echo "<td class='border p-2'>$status</td>";
        echo "</tr>";
    }

} catch (Exception $e) {
    echo "<tr><td colspan='5' class='p-4 text-red-600'>Error: " . $e->getMessage() . "</td></tr>";
}

echo "</table>";
echo '</div>';
