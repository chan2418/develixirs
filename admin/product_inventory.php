<?php
// admin/product_inventory.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
$page_title = "Product Inventory";
include __DIR__ . '/layout/header.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

// helper: get first image from JSON / comma list / path
function get_first_image_url($images) {
    $default = '/assets/images/avatar-default.png';
    if (empty($images)) return $default;
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
    if (preg_match('#^https?://#i', $val) || strpos($val, '/') === 0) return $val;
    return '/assets/uploads/products/' . ltrim($val, '/');
}

// fetch products (showing common fields)
try {
    $stmt = $pdo->query("SELECT id, name, sku, stock, price, is_active, images FROM products ORDER BY stock ASC, name ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $products = [];
    $fetchError = $e->getMessage();
}

// ensure CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];
?>
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
.page-wrap { max-width:1200px; margin:28px auto; padding:0 18px 60px; font-family:Inter,system-ui,Arial; color:#0f172a; }
.card { background:#fff; padding:16px; border-radius:12px; border:1px solid #e6eef7; box-shadow:0 8px 30px rgba(2,6,23,0.04); }
.table { width:100%; border-collapse:collapse; font-size:14px; }
.table th, .table td { padding:12px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; text-align:left; }
.table th { background:#fbfcfe; color:#475569; font-weight:700; font-size:13px; }
.prod-thumb { width:72px; height:72px; object-fit:cover; border-radius:8px; border:1px solid #eef2f7; }
.qty-input { width:86px; padding:8px; border-radius:8px; border:1px solid #e6eef7; }
.btn { padding:8px 12px; border-radius:8px; font-weight:700; cursor:pointer; }
.btn.primary { background:#0b76ff; color:#fff; border:0; }
.badge-low { background:#fff1f2; color:#dc2626; padding:6px 10px; border-radius:999px; font-weight:700; }
.badge-ok { background:#ecfdf5; color:#065f46; padding:6px 10px; border-radius:999px; font-weight:700; }
.small { font-size:13px; color:#64748b; }
.row { display:flex; gap:12px; align-items:center; }
.msg { margin-top:8px; padding:10px;border-radius:8px; display:none; }
.msg.ok { background:#ecfdf5;color:#065f46;border:1px solid #bbf7d0; }
.msg.err { background:#fff1f2;color:#991b1b;border:1px solid #fee2e2; }
@media(max-width:900px){ .table th, .table td { padding:10px; } }
</style>

<div class="page-wrap">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div>
      <h1 style="margin:0;font-size:22px;font-weight:800;">Product Inventory</h1>
      <div class="small" style="margin-top:6px;">View product thumbnails, title and stock. Increase stock directly from this page.</div>
    </div>
    <div>
      <a href="/admin/products.php" class="btn" style="background:#fff;border:1px solid #e6eef7;color:#0b1220;margin-right:8px;">Products</a>
      <a href="/admin/add_product.php" class="btn primary">+ Add Product</a>
    </div>
  </div>

  <div class="card">
    <?php if (!empty($fetchError)): ?>
      <div class="msg err" style="display:block;"><?php echo htmlspecialchars($fetchError); ?></div>
    <?php endif; ?>

    <div style="overflow:auto;">
      <table class="table" aria-describedby="inventory-list">
        <thead>
          <tr>
            <th style="width:70px;"></th>
            <th>Product</th>
            <th style="width:120px">SKU</th>
            <th style="width:140px">Stock</th>
            <th style="width:140px">Add Stock</th>
            <th style="width:120px">Status</th>
            <th style="width:120px">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr><td colspan="7" class="small" style="padding:40px;text-align:center;color:#64748b;">No products found.</td></tr>
          <?php else: foreach ($products as $p): 
              $img = htmlspecialchars(get_first_image_url($p['images']));
              $stock = (int)$p['stock'];
          ?>
            <tr id="prod-row-<?php echo (int)$p['id']; ?>">
              <td><img src="<?php echo $img; ?>" alt="" class="prod-thumb"></td>

              <td>
                <div style="font-weight:800;"><?php echo htmlspecialchars($p['name']); ?></div>
                <div class="small" style="margin-top:6px;">₹ <?php echo number_format((float)$p['price'],2); ?></div>
              </td>

              <td><?php echo htmlspecialchars($p['sku'] ?: '-'); ?></td>

              <td>
                <div id="stock-value-<?php echo (int)$p['id']; ?>">
                  <?php if ($stock <= 5): ?>
                    <span class="badge-low"><?php echo $stock; ?></span>
                  <?php else: ?>
                    <span class="badge-ok"><?php echo $stock; ?></span>
                  <?php endif; ?>
                </div>
              </td>

              <td>
                <div class="row">
                  <input type="number" min="1" value="1" class="qty-input" id="qty-<?php echo (int)$p['id']; ?>">
                  <button class="btn primary" onclick="addStock(<?php echo (int)$p['id']; ?>)">Add</button>
                </div>
                <div id="row-msg-<?php echo (int)$p['id']; ?>" class="msg" role="status" aria-live="polite"></div>
              </td>

              <td><?php echo (int)$p['is_active'] ? '<span class="small">Published</span>' : '<span class="small">Draft</span>'; ?></td>

              <td>
                <a class="btn" style="background:#fff;border:1px solid #e6eef7;color:#0b1220;text-decoration:none;padding:8px 10px;border-radius:8px;" href="/admin/edit_product.php?id=<?php echo (int)$p['id']; ?>">Edit</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const csrfToken = '<?php echo addslashes($csrf); ?>';

function showRowMsg(id, text, ok = true) {
  const el = document.getElementById('row-msg-' + id);
  if (!el) return;
  el.className = 'msg ' + (ok ? 'ok' : 'err');
  el.textContent = text;
  el.style.display = 'block';
  // hide after 3s
  setTimeout(() => { el.style.display = 'none'; }, 3500);
}

function addStock(productId) {
  const qtyInput = document.getElementById('qty-' + productId);
  if (!qtyInput) return;
  const amt = parseInt(qtyInput.value, 10);
  if (isNaN(amt) || amt <= 0) {
    showRowMsg(productId, 'Enter a positive quantity', false);
    return;
  }

  const btn = qtyInput.nextElementSibling; // not strictly necessary
  // disable while working
  if (btn) btn.disabled = true;

  fetch('/admin/product_inventory_update.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: productId, qty: amt, csrf_token: csrfToken })
  })
  .then(r => r.json())
  .then(json => {
    if (!json || !json.ok) {
      showRowMsg(productId, json && json.error ? json.error : 'Update failed', false);
      if (btn) btn.disabled = false;
      return;
    }
    // update stock UI
    const stockWrap = document.getElementById('stock-value-' + productId);
    if (stockWrap) {
      const newStock = parseInt(json.stock, 10);
      if (newStock <= 5) {
        stockWrap.innerHTML = '<span class="badge-low">'+ newStock +'</span>';
      } else {
        stockWrap.innerHTML = '<span class="badge-ok">'+ newStock +'</span>';
      }
    }
    showRowMsg(productId, 'Stock updated ✓', true);
    if (btn) btn.disabled = false;
    // reset qty input to 1
    qtyInput.value = 1;
  })
  .catch(err => {
    console.error(err);
    showRowMsg(productId, 'Network error', false);
    if (btn) btn.disabled = false;
  });
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>