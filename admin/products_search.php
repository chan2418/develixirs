<?php
// admin/products_search.php
header('Content-Type: application/json; charset=utf-8');
// hide warnings from output
error_reporting(E_ALL);
ini_set('display_errors','0');

require_once __DIR__ . '/_auth.php';      // keep this if you use admin session
require_once __DIR__ . '/../includes/db.php';

// read inputs
$q = trim($_GET['q'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, (int)($_GET['perPage'] ?? 10));
$offset = ($page - 1) * $perPage;

// build where
$whereParts = ['1=1'];
$params = [];

if ($q !== '') {
    $whereParts[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.slug LIKE ?)";
    $like = "%$q%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($status_filter !== '') {
    if ($status_filter === 'published') { $whereParts[] = "p.is_active = 1"; }
    elseif ($status_filter === 'draft') { $whereParts[] = "p.is_active = 0"; }
}
if ($category_filter) {
    $whereParts[] = "p.category_id = ?";
    $params[] = $category_filter;
}
$whereSql = implode(' AND ', $whereParts);

// total count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));

// fetch rows
$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE $whereSql
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";
$params_for_list = array_merge($params, [$perPage, $offset]);
$stmt = $pdo->prepare($sql);
$stmt->execute($params_for_list);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// helper same as products.php
function get_first_image_small($images) {
    $default = '/assets/images/avatar-default.png';
    if (!$images) return $default;
    $maybe = @json_decode($images, true);
    if (is_array($maybe) && !empty($maybe[0])) {
        $val = $maybe[0];
    } else {
        if (strpos($images, ',') !== false) {
            $parts = array_map('trim', explode(',', $images));
            $val = $parts[0] ?? '';
        } else {
            $val = trim($images);
        }
    }
    if (!$val) return $default;
    if (preg_match('#^https?://#i', $val) || strpos($val, '/') === 0) {
        return $val;
    }
    return '/assets/uploads/products/' . ltrim($val, '/');
}

// build rows_html
ob_start();
if (empty($rows)) {
    echo '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No products found.</td></tr>';
} else {
    foreach ($rows as $r) {
        $img = htmlspecialchars(get_first_image_small($r['images']));
        $stock = (int)$r['stock'];
        $statusText = $r['is_active'] ? 'Published' : 'Draft';
        $statusClass = $r['is_active'] ? 'bg-green-50 text-green-700' : 'bg-gray-50 text-slate-600';
        $id = (int)$r['id'];
        $name = htmlspecialchars($r['name']);
        $sku = htmlspecialchars($r['sku'] ?: substr($r['name'],0,60));
        $category_name = htmlspecialchars($r['category_name'] ?? '-');
        $price = number_format($r['price'],2);
        ?>
        <tr class="hover:bg-gray-50">
          <td class="px-4 py-4"><input type="checkbox" name="ids[]" value="<?php echo $id; ?>" class="h-4 w-4 text-indigo-600 border-gray-200 rounded" /></td>

          <td class="px-4 py-4 flex items-center gap-3">
            <div class="w-14 h-14 rounded-lg overflow-hidden bg-gray-100 border"><img src="<?php echo $img; ?>" alt="" class="object-cover w-full h-full"></div>
            <div>
              <div class="font-semibold text-slate-800"><?php echo $name; ?></div>
              <div class="text-xs text-slate-400 mt-0.5"><?php echo $sku; ?></div>
            </div>
          </td>

          <td class="px-4 py-4 text-sm text-slate-600"><?php echo $category_name; ?></td>

          <td class="px-4 py-4 text-sm">
            <?php if ($stock <= 0): ?>
              <span class="text-sm font-semibold text-red-600">Out of stock</span>
            <?php elseif ($stock <= 5): ?>
              <span class="text-sm font-semibold text-amber-600"><?php echo $stock; ?> Low Stock</span>
            <?php else: ?>
              <span class="text-sm text-slate-600"><?php echo $stock; ?></span>
            <?php endif; ?>
          </td>

          <td class="px-4 py-4 text-sm font-semibold">₹ <?php echo $price; ?></td>

          <td class="px-4 py-4"><span class="inline-flex items-center px-3 py-1 rounded-full <?php echo $statusClass; ?> text-sm"><?php echo $statusText; ?></span></td>

          <td class="px-4 py-4 text-right">
            <div class="inline-flex items-center gap-2">
              <a href="edit_product.php?id=<?php echo $id; ?>" class="btn-mini">Edit</a>

              <a href="products.php?toggle=<?php echo $id; ?>" onclick="return confirm('Toggle product active state?');" class="btn-mini">Toggle</a>

              <button type="button" class="delete-btn btn-danger" data-delete-id="<?php echo $id; ?>">Delete</button>
            </div>
          </td>
        </tr>
        <?php
    }
}
$rows_html = ob_get_clean();

// build pagination_html
$pagination_html = '';
if ($pages > 1) {
    $start = max(1, $page - 2);
    $end = min($pages, $page + 2);
    $pagination_html .= '<a href="#" data-page="'.max(1,$page-1).'" class="px-3 py-1 rounded-lg border bg-white hover:shadow">Prev</a>';
    for ($p=$start;$p<=$end;$p++) {
        $activeClass = $p === $page ? 'bg-indigo-600 text-white' : 'bg-white';
        $pagination_html .= '<a href="#" data-page="'.$p.'" class="px-3 py-1 rounded-lg '.$activeClass.'">'.$p.'</a>';
    }
    $pagination_html .= '<a href="#" data-page="'.min($pages,$page+1).'" class="px-3 py-1 rounded-lg border bg-white hover:shadow">Next</a>';
}

// totals text
$from = $total ? ($offset + 1) : 0;
$to = min($total, $offset + count($rows));
$totals_text = "Showing {$from} - {$to} of {$total} products";

// output JSON
echo json_encode([
    'ok' => true,
    'rows_html' => $rows_html,
    'pagination_html' => $pagination_html,
    'totals_text' => $totals_text,
]);
exit;