<?php
// admin/dashboard.php
// Polished admin dashboard using new layout header/footer (DEVELIXIR chrome)

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// small helper
function safeFetch($stmt) {
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: ['cnt' => 0, 'revenue' => 0];
}
function esc($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// --- SUMMARY ---
$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS revenue FROM orders WHERE DATE(created_at) = CURDATE()");
$stmt->execute(); $today = safeFetch($stmt);

$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS revenue FROM orders WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())");
$stmt->execute(); $month = safeFetch($stmt);

$totals = [];
$totals['products'] = (int) ($pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0);
$totals['orders']   = (int) ($pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0);
$totals['users']    = (int) ($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0);
$totals['categories'] = (int) ($pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn() ?: 0);

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='pending' OR payment_status='pending'");
$totals['pending'] = (int)($stmt->fetchColumn() ?: 0);

// --- SALES LAST 30 DAYS ---
$stmt = $pdo->prepare("
    SELECT DATE(created_at) AS day, COALESCE(SUM(total),0) AS revenue
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
");
$stmt->execute();
$sales_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// prepare labels and data for last 30 days (guaranteed 30 values)
$labels = []; $dataSales = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('d M', strtotime($d));
    $found = false;
    foreach ($sales_rows as $r) {
        if ($r['day'] === $d) { $dataSales[] = (float)$r['revenue']; $found = true; break; }
    }
    if (!$found) $dataSales[] = 0.0;
}

// --- LOW STOCK ---
$lowThreshold = 5;
$stmt = $pdo->prepare("SELECT id, name, sku, stock FROM products WHERE stock <= ? ORDER BY stock ASC LIMIT 8");
$stmt->execute([$lowThreshold]);
$lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- BEST SELLERS ---
$stmt = $pdo->prepare("
  SELECT p.id, p.name, p.sku, COALESCE(SUM(oi.qty),0) AS sold
  FROM order_items oi
  LEFT JOIN products p ON oi.product_id = p.id
  GROUP BY p.id
  ORDER BY sold DESC
  LIMIT 8
");
$stmt->execute();
$bestSellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- RECENT CUSTOMERS ---
$stmt = $pdo->prepare("SELECT id, name, email, created_at, (SELECT COUNT(*) FROM orders o WHERE o.user_id = users.id) AS orders_count FROM users ORDER BY created_at DESC LIMIT 8");
$stmt->execute();
$recentCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- RECENT ORDERS ---
$stmt = $pdo->query("SELECT id, order_number, customer_name, total, payment_status, order_status, created_at FROM orders ORDER BY created_at DESC LIMIT 8");
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// include new layout header/footer (DEVELIXIR)
include __DIR__ . '/layout/header.php';
?>

<link rel="stylesheet" href="/assets/css/admin.css">

<style>
/* Dashboard specific tweaks */
.dashboard-wrap { max-width:1250px; margin:28px auto; padding:0 18px 60px; font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
.kpis { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:16px; margin-bottom:22px; }
.kpi { padding:18px; border-radius:12px; color:#fff; position:relative; overflow:hidden; min-height:100px; display:flex; flex-direction:column; justify-content:space-between; }
.kpi .label { font-size:12px; font-weight:700; opacity:0.95; letter-spacing:0.6px; }
.kpi .value { font-size:26px; font-weight:800; margin-top:6px; display:flex; align-items:baseline; gap:8px; }
.kpi .sub { font-size:13px; opacity:0.9; color:rgba(255,255,255,0.95); margin-top:8px; }

/* gradient themes */
.kpi.revenue { background: linear-gradient(135deg, #0b76ff 0%, #6ea8ff 100%); box-shadow: 0 8px 30px rgba(11,118,255,0.12); }
.kpi.month { background: linear-gradient(135deg, #06b6d4 0%, #7ee3e8 100%); box-shadow: 0 8px 30px rgba(6,182,212,0.12); }
.kpi.products { background: linear-gradient(135deg, #10b981 0%, #7fe4bb 100%); box-shadow: 0 8px 30px rgba(16,185,129,0.10); }
.kpi.pending { background: linear-gradient(135deg, #f59e0b 0%, #ffd28a 100%); box-shadow: 0 8px 30px rgba(245,158,11,0.08); }

/* grid layout for main area */
.grid-2 { display:grid; grid-template-columns: 1fr 360px; gap:18px; align-items:start; margin-bottom:18px; }
.card { background:#fff; padding:16px; border-radius:12px; box-shadow:0 8px 30px rgba(2,6,23,0.04); border:1px solid #f0f3f7; }
.card h3 { margin:0 0 10px 0; font-size:18px; }

/* chart container */
.chart-wrap { height:320px; position:relative; padding:6px; }

/* quick actions */
.quick-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; }
.btn { padding:10px 14px; border-radius:10px; background:#0b76ff; color:#fff; text-decoration:none; font-weight:700; display:inline-block; box-shadow: 0 6px 18px rgba(11,118,255,0.12); }
.btn.alt { background:#06b6d4; box-shadow: 0 6px 18px rgba(6,182,212,0.08); }
.btn.ghost { background:transparent; color:#374151; border:1px solid #eef2f7; box-shadow:none; font-weight:700; }

/* small table styles */
.table { width:100%; border-collapse:collapse; font-size:14px; }
.table th { text-align:left; padding:10px; font-size:13px; color:#6b7280; border-bottom:1px solid #eef2f7; text-transform:uppercase; }
.table td { padding:10px; border-bottom:1px solid #f6f8fb; vertical-align:middle; }
.badge { display:inline-block; padding:6px 8px; border-radius:999px; font-weight:700; font-size:12px; }
.badge.success { background:#ecfdf5; color:#059669; }
.badge.warn { background:#fffbeb; color:#b45309; }
.badge.danger { background:#fff1f2; color:#dc2626; }

/* right column stack */
.right-stack { display:flex; flex-direction:column; gap:12px; }

/* responsive */
@media(max-width:980px){ .grid-2 { grid-template-columns: 1fr; } .chart-wrap{height:260px;} }
</style>

<div class="dashboard-wrap">

  <!-- KPI Row -->
  <div class="kpis">
    <div class="kpi revenue" role="region" aria-label="Today's revenue">
      <div>
        <div class="label">Today's Revenue</div>
        <div class="value">₹<?php echo number_format((float)$today['revenue'],2); ?></div>
        <div class="sub">Orders today: <?php echo (int)$today['cnt']; ?></div>
      </div>
      <div style="opacity:0.08; font-size:100px; position:absolute; right:12px; bottom:-6px;">₹</div>
    </div>

    <div class="kpi month" role="region" aria-label="This month revenue">
      <div>
        <div class="label">This Month</div>
        <div class="value">₹<?php echo number_format((float)$month['revenue'],2); ?></div>
        <div class="sub">Orders: <?php echo (int)$month['cnt']; ?></div>
      </div>
      <div style="opacity:0.08; font-size:100px; position:absolute; right:12px; bottom:-6px;">M</div>
    </div>

    <div class="kpi products" role="region" aria-label="Products and users">
      <div>
        <div class="label">Products / Users</div>
        <div class="value"><?php echo esc($totals['products']); ?> <span style="font-size:14px; font-weight:600; opacity:0.85;">/ <?php echo esc($totals['users']); ?></span></div>
        <div class="sub">Categories: <?php echo esc($totals['categories']); ?></div>
      </div>
      <div style="opacity:0.08; font-size:100px; position:absolute; right:12px; bottom:-6px;">P</div>
    </div>

    <div class="kpi pending" role="region" aria-label="Pending orders">
      <div>
        <div class="label">Pending Orders</div>
        <div class="value"><?php echo esc($totals['pending']); ?></div>
        <div class="sub">Total Orders: <?php echo esc($totals['orders']); ?></div>
      </div>
      <div style="opacity:0.08; font-size:100px; position:absolute; right:12px; bottom:-6px;">!</div>
    </div>
  </div>

  <!-- Chart + Right column -->
  <div class="grid-2">
    <div class="card" aria-labelledby="sales-title">
      <h3 id="sales-title">Sales — Last 30 Days</h3>
      <div class="chart-wrap">
        <canvas id="salesChart" role="img" aria-label="Sales chart for last 30 days"></canvas>
      </div>

      <div style="display:flex; gap:18px; margin-top:12px;">
        <div style="flex:1">
          <div style="font-size:13px; color:#6b7280;">This month</div>
          <div style="font-weight:800; font-size:20px;">₹<?php echo number_format((float)$month['revenue'],2); ?></div>
        </div>
        <div style="flex:1">
          <div style="font-size:13px; color:#6b7280;">Orders this month</div>
          <div style="font-weight:800; font-size:20px;"><?php echo (int)$month['cnt']; ?></div>
        </div>
      </div>
    </div>

    <div class="right-stack">
      <div class="card" aria-labelledby="quick-actions">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <h3 id="quick-actions" style="margin:0;">Quick Actions</h3>
          <a href="/admin/add_product.php" class="btn small">+ Add Product</a>
        </div>
        <div class="quick-actions" role="navigation" aria-label="Quick actions">
          <a href="/admin/products.php" class="btn">Products</a>
          <a href="/admin/orders.php" class="btn alt">Orders</a>
          <a href="/admin/categories.php" class="btn ghost">Categories</a>
          <a href="/admin/users.php" class="btn ghost">Users</a>
        </div>
      </div>

      <div class="card">
        <h3>Low Stock</h3>
        <?php if(empty($lowStock)): ?>
          <div style="color:#6b7280;">All good — no low-stock products (threshold <?php echo $lowThreshold; ?>)</div>
        <?php else: ?>
          <table class="table" aria-describedby="low-stock">
            <thead><tr><th>Product</th><th>Stock</th></tr></thead>
            <tbody>
            <?php foreach($lowStock as $p): ?>
              <tr>
                <td><?php echo esc($p['name']); ?></td>
                <td><span class="badge warn"><?php echo (int)$p['stock']; ?></span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Best sellers + recent customers / orders -->
  <div style="display:grid; grid-template-columns: 1fr 420px; gap:18px; margin-top:18px;">
    <div class="card">
      <h3>Best Selling Products</h3>
      <?php if(empty($bestSellers)): ?>
        <div style="color:#6b7280;">No sales data yet.</div>
      <?php else: ?>
        <table class="table" aria-describedby="best-sellers">
          <thead><tr><th>Product</th><th>Sold</th></tr></thead>
          <tbody>
            <?php foreach($bestSellers as $b): ?>
              <tr>
                <td><?php echo esc($b['name']); ?></td>
                <td><?php echo (int)$b['sold']; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div style="display:flex; flex-direction:column; gap:12px;">
      <div class="card">
        <h3>Recent Customers</h3>
        <?php if(empty($recentCustomers)): ?>
          <div style="color:#6b7280;">No recent customers.</div>
        <?php else: ?>
          <table class="table" aria-describedby="recent-customers">
            <thead><tr><th>Name</th><th>Orders</th><th>Joined</th></tr></thead>
            <tbody>
              <?php foreach($recentCustomers as $c): ?>
                <tr>
                  <td><?php echo esc($c['name'] ?: $c['email']); ?></td>
                  <td><?php echo (int)$c['orders_count']; ?></td>
                  <td><?php echo esc(date('d M Y', strtotime($c['created_at']))); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <div class="card">
        <h3>Recent Orders</h3>
        <?php if(empty($recentOrders)): ?>
          <div style="color:#6b7280;">No recent orders.</div>
        <?php else: ?>
          <table class="table" aria-describedby="recent-orders">
            <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach($recentOrders as $o): ?>
                <tr>
                  <td><?php echo esc($o['order_number']); ?></td>
                  <td><?php echo esc($o['customer_name']); ?></td>
                  <td>₹<?php echo number_format($o['total'],2); ?></td>
                  <td><?php echo esc($o['order_status']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
  const labels = <?php echo json_encode($labels); ?> || [];
  const dataSales = <?php echo json_encode($dataSales); ?> || [];

  const ctx = document.getElementById('salesChart').getContext('2d');
  const gradient = ctx.createLinearGradient(0,0,0,300);
  gradient.addColorStop(0, 'rgba(11,118,255,0.14)');
  gradient.addColorStop(1, 'rgba(11,118,255,0.02)');

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Revenue (₹)',
        data: dataSales,
        fill: true,
        tension: 0.35,
        borderWidth: 2,
        pointRadius: 3,
        pointBackgroundColor: 'rgba(11,118,255,1)',
        backgroundColor: gradient,
        borderColor: 'rgba(11,118,255,1)'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
        y: { grid: { color: '#f6f8fb' }, ticks: { callback: function(value){ return '₹' + value; } } }
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function(ctx){
              return '₹' + parseFloat(ctx.formattedValue || 0).toFixed(2);
            }
          }
        }
      }
    }
  });
})();
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>