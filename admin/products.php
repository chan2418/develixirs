<?php
// admin/products.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// page meta for header/sidebar
$page_title = "Products";
$activeMenu = "products";

// include layout header (this prints opening HTML, header & sidebar)
include __DIR__ . '/layout/header.php';

// ----------------------
// Bulk actions (POST)
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $ids = array_map('intval', $_POST['ids'] ?? []);
    if (!empty($ids)) {
        $in = implode(',', $ids);
        if ($action === 'delete') {
            $pdo->exec("DELETE FROM products WHERE id IN ($in)");
        } 
        // 'is_active' column missing, enable/disable skipped
    }
    header('Location: products.php'); exit;
}

// ----------------------
// Toggle Single (GET)
// ----------------------
// ----------------------
// Toggle Single (GET)
// ----------------------
if (isset($_GET['toggle'])) {
    $toggleId = (int)$_GET['toggle'];
    if ($toggleId > 0) {
        // Toggle is_active
        $stmtTags = $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
        $stmtTags->execute([$toggleId]);
    }
    header('Location: products.php'); // Redirect to clear query param
    exit;
}

// ----------------------
// Inputs / Filters
// ----------------------
$q = trim($_GET['q'] ?? '');
$status_filter   = trim($_GET['status'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// ----------------------
// Build WHERE
// ----------------------
$whereParts = ["1=1"];
$params = [];

// Dynamic Filters Query Logic
// (We rely on $filterGroups being fetched below, but we need query logic here. 
//  For cleanliness, I'll fetch just IDs or move fetching up. I'll move fetching up.)
$filterGroups = [];
try {
    $stmtFG = $pdo->query("SELECT * FROM filter_groups WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    $filterGroups = $stmtFG->fetchAll(PDO::FETCH_ASSOC);
    foreach ($filterGroups as &$fg) {
        $stmtOpt = $pdo->prepare("SELECT * FROM filter_options WHERE group_id = ? ORDER BY sort_order ASC, name ASC");
        $stmtOpt->execute([$fg['id']]);
        $fg['options'] = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($fg);
} catch (Exception $e) { }

foreach ($filterGroups as $fg) {
    $param = 'filter_' . $fg['id'];
    if (!empty($_GET[$param])) {
        $optId = (int)$_GET[$param];
        if ($optId > 0) {
            $whereParts[] = "EXISTS (SELECT 1 FROM product_filter_values pfv_{$fg['id']} WHERE pfv_{$fg['id']}.product_id = p.id AND pfv_{$fg['id']}.filter_option_id = ?)";
            $params[] = $optId;
        }
    }
}

if ($q !== '') {
    $whereParts[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.slug LIKE ?)";
    $like = "%$q%";
    $params[] = $like; 
    $params[] = $like; 
    $params[] = $like;
}

if ($status_filter !== '') {
    // 'is_active' column missing, so we skip filtering or assume all active
    // if ($status_filter === 'published') { ... }
}

/**
 * Category filter:
 * - match category_id
 */
if ($category_filter) {
    $whereParts[] = "p.category_id = ?";
    $params[] = $category_filter;
}

$whereSql = implode(' AND ', $whereParts);

// ----------------------
// Totals & Rows
// ----------------------
$stmt = $pdo->prepare("SELECT COUNT(*) 
                       FROM products p 
                       WHERE $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));

/**
 * Join categories:
 *  - c_sub = category (p.category_id)
 *  - Parent category not in table, so we skip it
 */
$sql = "SELECT 
            p.*,
            c_parent.title AS parent_category_name,
            c_sub.title AS sub_category_name
        FROM products p
        LEFT JOIN categories c_sub 
               ON p.category_id = c_sub.id
        LEFT JOIN categories c_parent 
               ON p.parent_category_id = c_parent.id
        WHERE $whereSql
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

$params_for_list = array_merge($params, [$perPage, $offset]);
$stmt = $pdo->prepare($sql);
$stmt->execute($params_for_list);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------
// Categories for filter dropdown
// ----------------------
$cats = [];
try {
    $cats = $pdo->query("
        SELECT id, title AS name 
        FROM categories 
        ORDER BY title ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cats = [];
}

// ----------------------
// Helpers
// ----------------------
function get_product_first_image($images) {
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

function products_preserve_qs($overrides = []) {
    $qs = $_GET;
    foreach ($overrides as $k => $v) { $qs[$k] = $v; }
    return '?'.http_build_query($qs);
}
?>

<!-- MAIN CONTENT -->
<div class="max-w-[1200px] mx-auto">

  <!-- Toolbar -->
  <div class="flex items-start justify-between gap-4 mb-6">
    <div>
      <h2 class="text-2xl font-extrabold text-slate-800">Products</h2>
      <p class="text-sm text-slate-500 mt-1">Manage your product catalog with filters and bulk actions.</p>
    </div>

    <div class="flex items-center gap-3">
      <form id="importForm" action="products_import.php" method="POST" enctype="multipart/form-data" class="hidden">
           <input type="file" name="csv_file" id="csvFile" accept=".csv" onchange="document.getElementById('importForm').submit()">
      </form>

      <button onclick="document.getElementById('csvFile').click()" class="bg-white border border-gray-200 px-3 py-2 rounded-lg text-sm hover:shadow flex items-center gap-2">
           <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
           Import CSV
      </button>

      <a href="products_export.php" class="bg-white border border-gray-200 px-3 py-2 rounded-lg text-sm hover:shadow flex items-center gap-2">
           <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
           Export CSV
      </a>
      
      <a href="download_template.php" class="text-xs text-indigo-600 hover:underline mr-2">Download Template</a>
      <a href="add_product.php" class="bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white inline-flex items-center gap-2 px-4 py-2 rounded-lg font-semibold shadow">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Product
      </a>
    </div>
  </div>

  <!-- Filters -->
  <div class="bg-white rounded-xl shadow p-4 mb-6">
    <form id="filterForm" onsubmit="return false" class="flex flex-col md:flex-row md:items-center gap-3">
      <div class="flex-1 min-w-0 flex items-center gap-3">
        <input id="q" name="q" type="search" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search product name, SKU or slug" class="flex-1 border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
        <button id="searchBtn" type="button" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 font-semibold shadow">Search</button>
      </div>

      <div class="flex items-center gap-3">
        <select id="status" name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
          <option value="">All Status</option>
          <option value="published" <?php if($status_filter==='published') echo 'selected'; ?>>Published</option>
          <option value="draft" <?php if($status_filter==='draft') echo 'selected'; ?>>Draft</option>
        </select>

        <select id="category" name="category" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
          <option value="">All Categories</option>
          <?php foreach($cats as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>" <?php if((int)$category_filter === (int)$c['id']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($c['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <!-- Dynamic Filters -->
        <?php foreach($filterGroups as $fg): ?>
          <select name="filter_<?= $fg['id'] ?>" class="border border-gray-200 rounded-lg px-3 py-2 text-sm dynamic-filter">
            <option value="">All <?= htmlspecialchars($fg['name']) ?></option>
            <?php foreach($fg['options'] as $opt): ?>
              <option value="<?= $opt['id'] ?>" <?php if((int)($_GET['filter_'.$fg['id']]??0) === $opt['id']) echo 'selected'; ?>>
                <?= htmlspecialchars($opt['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php endforeach; ?>

        <a id="resetBtn" href="products.php" class="px-3 py-2 rounded-lg bg-gray-50 border border-gray-100 text-sm">Reset</a>
      </div>
    </form>
  </div>

  <!-- Products table card -->
  <div class="bg-white rounded-2xl shadow p-4">
    <form method="post" id="bulkForm">
      <div class="overflow-auto">
        <table class="w-full min-w-[1000px] divide-y divide-gray-100">
          <thead class="bg-gray-50">
            <tr class="text-left text-sm text-gray-500">
              <th class="px-4 py-3 w-12">
                <input id="checkAll" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-200 rounded" />
              </th>
              <th class="px-4 py-3">Product</th>
              <th class="px-4 py-3">Category (Parent / Sub)</th>
              <th class="px-4 py-3">Stock</th>
              <th class="px-4 py-3">Price</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3 text-right">Action</th>
            </tr>
          </thead>

          <tbody id="productsTbody" class="bg-white divide-y divide-gray-100">
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                  No products found.
                </td>
              </tr>
            <?php else: foreach($rows as $r):
              $img = htmlspecialchars(get_product_first_image($r['images']));
              $stock = (int)$r['stock'];
              
              // Status Logic
              $isActive = (int)($r['is_active'] ?? 1);
              if ($isActive === 1) {
                  $statusText  = 'Published'; 
                  $statusClass = 'bg-green-100 text-green-800 border border-green-200';
              } else {
                  $statusText  = 'Draft'; 
                  $statusClass = 'bg-gray-100 text-gray-600 border border-gray-200';
              }

              $parentCat = $r['parent_category_name'] ?? '';
              $subCat    = $r['sub_category_name'] ?? '';

              if ($parentCat && $subCat) {
                  $catLabel = $parentCat . ' / ' . $subCat;
              } elseif ($parentCat) {
                  $catLabel = $parentCat;
              } elseif ($subCat) {
                  $catLabel = $subCat;
              } else {
                  $catLabel = '-';
              }
            ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-4">
                  <input type="checkbox" name="ids[]" value="<?php echo (int)$r['id']; ?>" class="h-4 w-4 text-indigo-600 border-gray-200 rounded focus:ring-indigo-500" />
                </td>

                <td class="px-4 py-4 flex items-center gap-3">
                  <div class="w-14 h-14 rounded-lg overflow-hidden bg-gray-100 border relative group">
                    <img src="<?php echo $img; ?>" alt="" class="object-cover w-full h-full transition-transform group-hover:scale-105">
                  </div>
                  <div>
                    <div class="font-semibold text-slate-800">
                      <?php echo htmlspecialchars($r['name']); ?>
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5 font-mono">
                      <?php echo htmlspecialchars($r['sku'] ?: substr($r['name'],0,60)); ?>
                    </div>
                  </div>
                </td>

                <td class="px-4 py-4 text-sm text-slate-600">
                  <?php echo htmlspecialchars($catLabel); ?>
                </td>

                <td class="px-4 py-4 text-sm">
                  <?php if ($stock <= 0): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                      Out of stock
                    </span>
                  <?php elseif ($stock <= 5): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                      <?php echo $stock; ?> Low Stock
                    </span>
                  <?php else: ?>
                    <span class="text-slate-600 font-medium"><?php echo $stock; ?></span>
                  <?php endif; ?>
                </td>

                <td class="px-4 py-4 text-sm font-semibold text-slate-700">
                  ₹ <?php echo number_format($r['price'],2); ?>
                </td>

                <td class="px-4 py-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                    <?php echo $statusText; ?>
                  </span>
                </td>

                <td class="px-4 py-4 text-right">
                  <div class="inline-flex items-center gap-2">
                    <a href="edit_product.php?id=<?php echo (int)$r['id']; ?>" class="px-3 py-1 rounded border bg-white text-sm font-semibold">Edit</a>
                    <a href="products.php?toggle=<?php echo (int)$r['id']; ?>" onclick="return confirm('Toggle product active state?');" class="px-3 py-1 rounded border bg-white text-sm font-semibold">Toggle</a>
                    <button type="button" class="delete-btn px-3 py-1 rounded bg-red-600 text-white text-sm" data-delete-id="<?php echo (int)$r['id']; ?>">Delete</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- footer area -->
      <div class="mt-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <select id="bulkAction" name="bulk_action" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <option value="">Bulk action</option>
            <option value="delete">Delete</option>
            <option value="enable">Enable</option>
            <option value="disable">Disable</option>
          </select>
          <button type="submit" form="bulkForm" onclick="return confirmBulk()" class="bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white px-4 py-2 rounded-lg font-semibold shadow">Apply</button>
        </div>

        <div class="flex items-center gap-3">
          <div id="totalsText" class="text-sm text-slate-500">
            Showing <?php echo $total ? min($total, $offset+1) : 0; ?> - <?php echo min($total, $offset + count($rows)); ?> of <?php echo $total; ?> products
          </div>

          <div id="paginationWrap" class="inline-flex items-center gap-1">
            <?php
              $start = max(1, $page - 2);
              $end   = min($pages, $page + 2);
            ?>
            <a href="#" data-page="<?php echo max(1,$page-1); ?>" class="px-3 py-1 rounded-lg border bg-white hover:shadow">Prev</a>
            <?php for($p=$start;$p<=$end;$p++): ?>
              <a href="#" data-page="<?php echo $p; ?>" data-page-num="<?php echo $p; ?>" class="px-3 py-1 rounded-lg <?php echo $p==$page ? 'bg-indigo-600 text-white' : 'bg-white'; ?>">
                <?php echo $p; ?>
              </a>
            <?php endfor; ?>
            <a href="#" data-page="<?php echo min($pages,$page+1); ?>" class="px-3 py-1 rounded-lg border bg-white hover:shadow">Next</a>
          </div>
        </div>
      </div>
    </form>
  </div>

  <p class="text-xs text-slate-400 mt-4">Tip: Use Import to bulk-add products and Export to download CSV.</p>
</div>

<!-- JS: AJAX search + delete logic -->
<script>
/* Utilities */
function qs(params) { return Object.keys(params).map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k])).join('&'); }
let ajaxTimer = null;
const debounceMs = 450;

/* Read filters */
function readFilters() {
  const params = { 
    q: document.getElementById('q').value || '', 
    status: document.getElementById('status').value || '', 
    category: document.getElementById('category').value || '', 
    perPage: 10 
  };
  document.querySelectorAll('.dynamic-filter').forEach(el => {
      if(el.value) params[el.name] = el.value;
  });
  return params;
}

/* AJAX search */
function doSearch(page = 1) {
  const filters = readFilters(); 
  filters.page = page;
  const url = 'products_search.php?' + qs(filters);
  const input = document.getElementById('q');
  const cursorPos = input && typeof input.selectionStart !== 'undefined' ? input.selectionStart : null;

  fetch(url, { cache: 'no-store' })
    .then(r => r.json())
    .then(data => {
      if (data.error) { console.error('search error', data.error); return; }
      document.getElementById('productsTbody').innerHTML = data.rows_html;
      document.getElementById('totalsText').textContent = data.totals_text;
      document.getElementById('paginationWrap').innerHTML = data.pagination_html;
      if (input) { 
        input.focus(); 
        if (cursorPos !== null) { 
          try { input.setSelectionRange(cursorPos, cursorPos); } catch(e) {} 
        } 
      }
    }).catch(err => console.error('ajax fetch error', err));
}

function scheduleSearch(){ 
  clearTimeout(ajaxTimer); 
  ajaxTimer = setTimeout(() => doSearch(1), debounceMs); 
}

/* Bind events */
document.addEventListener('DOMContentLoaded', function(){
  const qInput = document.getElementById('q'); 
  if (qInput) qInput.addEventListener('input', scheduleSearch);
  const sb = document.getElementById('searchBtn'); 
  if (sb) sb.addEventListener('click', function(){ doSearch(1); });
  const pw = document.getElementById('paginationWrap'); 
  if (pw) pw.addEventListener('click', function(e){ 
    const a = e.target.closest('a[data-page]'); 
    if (!a) return; 
    e.preventDefault(); 
    doSearch(parseInt(a.getAttribute('data-page')||'1',10)); 
  });
  const st = document.getElementById('status'); 
  if (st) st.addEventListener('change', function(){ doSearch(1); });
  const ct = document.getElementById('category'); 
  if (ct) ct.addEventListener('change', function(){ doSearch(1); });
  
  // Dynamic filters
  document.querySelectorAll('.dynamic-filter').forEach(el => {
      el.addEventListener('change', function(){ doSearch(1); });
  });
  const checkAll = document.getElementById('checkAll'); 
  if (checkAll) checkAll.addEventListener('change', function(e){ 
    const ch = e.target.checked; 
    document.querySelectorAll('input[name="ids[]"]').forEach(i => i.checked = ch); 
  });
});

/* Confirm bulk */
function confirmBulk(){ 
  var act = document.getElementById('bulkAction').value; 
  if (!act) { 
    alert('Select bulk action'); 
    return false; 
  } 
  if (act === 'delete') { 
    return confirm('Are you sure you want to DELETE selected products? This action cannot be undone.'); 
  } 
  return true; 
}

/* Delegated delete handler */
document.addEventListener('click', function(e){
  const btn = e.target.closest('.delete-btn');
  if (!btn) return;
  e.preventDefault();
  const id = btn.getAttribute('data-delete-id');
  if (!id) { alert('Delete failed: missing id'); return; }
  if (!confirm('Are you sure you want to DELETE this product? This action cannot be undone.')) return;

  btn.disabled = true;
  const oldText = btn.innerHTML;
  btn.innerHTML = 'Deleting...';

  fetch('products_delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: 'id=' + encodeURIComponent(id)
  })
  .then(async res => {
    const text = await res.text();
    let json;
    try { json = JSON.parse(text); } catch (err) { 
      console.error('invalid json', text); 
      alert('Delete failed (invalid server response). See console.'); 
      btn.disabled = false; 
      btn.innerHTML = oldText; 
      return; 
    }
    if (!json.ok) { 
      alert('Delete failed: ' + (json.error || 'Unknown error')); 
      btn.disabled = false; 
      btn.innerHTML = oldText; 
      return; 
    }
    const active = document.querySelector('#paginationWrap a.bg-indigo-600[data-page], #paginationWrap a[aria-current="true"][data-page]');
    const current = active ? parseInt(active.getAttribute('data-page')||'1',10) : 1;
    doSearch(current || 1);
  })
  .catch(err => { 
    console.error('fetch error', err); 
    alert('Delete failed (network). See console.'); 
    btn.disabled = false; 
    btn.innerHTML = oldText; 
  });
});
</script>

<?php
// include layout footer (closes main, footer & body/html)
include __DIR__ . '/layout/footer.php';
?>