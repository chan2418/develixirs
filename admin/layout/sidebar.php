<?php
// admin/sidebar.php
// Complete sidebar with Dashboard, Appearance (Banner), Products (submenu), Orders (submenu -> Invoices), Categories, Users, Settings
// Keeps active highlight logic based on current request URI.

if (session_status() === PHP_SESSION_NONE) session_start();

// Determine current path & file
$req_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$req_file = basename($req_path);

// helper: returns 'sidebar-link' plus 'active' when candidate matches
function is_active_link($candidates = []) {
    global $req_file, $req_path;
    if (!is_array($candidates)) $candidates = [$candidates];

    foreach ($candidates as $candidate) {
        // if looks like a filename with extension
        if (strpos($candidate, '.php') !== false) {
            if ($req_file === $candidate) return 'sidebar-link active';
        } else {
            // allow passing either a name or a path prefix
            $cand = '/admin/' . ltrim($candidate, '/');
            if ($req_file === $candidate . '.php') return 'sidebar-link active';
            if (stripos($req_path, $cand) === 0) return 'sidebar-link active';
            // also match simple keywords
            if (stripos($req_path, '/' . ltrim($candidate, '/')) !== false) return 'sidebar-link active';
        }
    }
    return 'sidebar-link';
}

// Icons map used for repeated items
$icons = [
  'Dashboard' => '🏠',
  'Products'  => '📦',
  'Orders'    => '🧾',
  'Categories'=> '📂',
  'Users'     => '👥',
  'Settings'  => '⚙️',
  'Appearance'=> '🖼️'
];

// Primary nav entries (other than Dashboard / parents rendered explicitly)
$nav = [
  ['label'=>'Categories','href'=>'/admin/categories.php','match'=>['categories','categories.php','category']],
  ['label'=>'Users','href'=>'/admin/users.php','match'=>['users','users.php','user']],
  ['label'=>'Settings','href'=>'/admin/settings.php','match'=>['settings','settings.php']],
];

// Determine if current page is inside special parents (Products / Orders / Appearance)
$isProductArea = in_array($req_file, [
                    'products.php',
                    'add_product.php',
                    'edit_product.php',
                    'product_reviews.php',
                    'product_inventory.php',
                    'tags.php'                // 👈 NEW
                ]) ||
                stripos($req_path, '/admin/products') === 0 ||
                stripos($req_path, '/admin/product_reviews') === 0 ||
                stripos($req_path, '/admin/product_inventory') === 0 ||
                stripos($req_path, '/admin/tags') === 0;   // 👈 NEW

$isOrdersArea = in_array($req_file, ['orders.php','order_view.php','invoices.php','invoice_view.php','shipments.php']) ||
                stripos($req_path, '/admin/orders') === 0 ||
                stripos($req_path, '/admin/invoices') === 0 ||
                stripos($req_path, '/admin/shipments') === 0;

$isAppearanceArea = in_array($req_file, ['banner.php','appearance.php','product_highlights.php']) ||
                    stripos($req_path, '/admin/banner.php') === 0 ||
                    stripos($req_path, '/admin/appearance') === 0 ||
                    stripos($req_path, '/admin/product_highlights') === 0;

?>
<!-- Sidebar -->
<aside class="w-64 bg-white border-r border-gray-200 min-h-screen" aria-label="Sidebar">
  <div class="px-6 py-6">
    <a href="/admin/dashboard.php" class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-md bg-indigo-600 text-white flex items-center justify-center font-extrabold">D</div>
      <div>
        <div class="font-extrabold text-lg text-slate-800">DEVELIXIR</div>
        <div class="text-xs text-slate-400">Admin Panel</div>
      </div>
    </a>
  </div>

  <nav class="px-2 py-4 space-y-1" aria-label="Main navigation">
    <!-- Dashboard (explicit single link) -->
    <a href="/admin/dashboard.php"
       class="<?php echo is_active_link(['dashboard','dashboard.php','/admin/']); ?> flex items-center gap-3 px-4 py-2 rounded-md"
       title="Dashboard">
      <span class="text-slate-500"><?php echo $icons['Dashboard']; ?></span>
      <span class="text-sm">Dashboard</span>
    </a>

    <!-- Appearance parent (collapsible) -->
    <?php
      $appearanceParentActive = $isAppearanceArea ? 'sidebar-link active' : 'sidebar-link';
      $appearanceSubMax = $isAppearanceArea ? 'max-h-40' : 'max-h-0';
      $appearanceArrowRotate = $isAppearanceArea ? 'rotate-180' : '';
    ?>
    <div class="px-2">
      <button
        id="appearanceToggle"
        onclick="toggleSidebarSubmenu('appearanceSubmenu','appearanceToggleArrow')"
        class="<?php echo $appearanceParentActive; ?> w-full flex items-center justify-between gap-3 px-4 py-2 rounded-md"
        aria-expanded="<?php echo $isAppearanceArea ? 'true' : 'false'; ?>"
        aria-controls="appearanceSubmenu"
        aria-haspopup="true"
      >
        <span class="flex items-center gap-3">
          <span class="text-slate-500"><?php echo $icons['Appearance']; ?></span>
          <span class="text-sm">Appearance</span>
        </span>

        <svg id="appearanceToggleArrow" class="w-4 h-4 transition-transform <?php echo $appearanceArrowRotate; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div id="appearanceSubmenu" class="ml-4 mt-1 space-y-1 overflow-hidden transition-all <?php echo $appearanceSubMax; ?>" aria-hidden="<?php echo $isAppearanceArea ? 'false' : 'true'; ?>">
        <a href="/admin/banner.php" class="<?php echo is_active_link(['banner','banner.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Banner</span>
        </a>

        <a href="/admin/appearance.php" class="<?php echo is_active_link(['appearance','appearance.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Theme / Sections</span>
        </a>

        <a href="/admin/product_highlights.php"
          class="<?php echo is_active_link(['product_highlights','product_highlights.php','highlights']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Product Highlights</span>
        </a>
      </div>
    </div>

    <!-- Products parent (collapsible) -->
    <?php
      $productParentActive = $isProductArea ? 'sidebar-link active' : 'sidebar-link';
      $productSubMax = $isProductArea ? 'max-h-40' : 'max-h-0';
      $productArrowRotate = $isProductArea ? 'rotate-180' : '';
    ?>
    <div class="px-2">
      <button
        id="productsToggle"
        onclick="toggleSidebarSubmenu('productsSubmenu','productsToggleArrow')"
        class="<?php echo $productParentActive; ?> w-full flex items-center justify-between gap-3 px-4 py-2 rounded-md"
        aria-expanded="<?php echo $isProductArea ? 'true' : 'false'; ?>"
        aria-controls="productsSubmenu"
        aria-haspopup="true"
      >
        <span class="flex items-center gap-3">
          <span class="text-slate-500"><?php echo $icons['Products']; ?></span>
          <span class="text-sm">Products</span>
        </span>

        <svg id="productsToggleArrow" class="w-4 h-4 transition-transform <?php echo $productArrowRotate; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div id="productsSubmenu" class="ml-4 mt-1 space-y-1 overflow-hidden transition-all <?php echo $productSubMax; ?>" aria-hidden="<?php echo $isProductArea ? 'false' : 'true'; ?>">
        <a href="/admin/products.php" class="<?php echo is_active_link(['products','products.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Products</span>
        </a>

        <!-- 👇 NEW: Product Tags -->
        <a href="/admin/tags.php"
          class="<?php echo is_active_link(['tags','tags.php','product_tags']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Product Tags</span>
        </a>

        <a href="/admin/product_reviews.php" class="<?php echo is_active_link(['product_reviews','product_reviews.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Product Reviews</span>
        </a>

        <a href="/admin/product_inventory.php" class="<?php echo is_active_link(['product_inventory','inventory']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Product Inventory</span>
        </a>
    </div>

    <!-- Orders parent (collapsible) with Invoices + Shipments -->
    <?php
      $ordersParentActive = $isOrdersArea ? 'sidebar-link active' : 'sidebar-link';
      $ordersSubMax = $isOrdersArea ? 'max-h-40' : 'max-h-0';
      $ordersArrowRotate = $isOrdersArea ? 'rotate-180' : '';
    ?>
    <div class="px-2">
      <button
        id="ordersToggle"
        onclick="toggleSidebarSubmenu('ordersSubmenu','ordersToggleArrow')"
        class="<?php echo $ordersParentActive; ?> w-full flex items-center justify-between gap-3 px-4 py-2 rounded-md"
        aria-expanded="<?php echo $isOrdersArea ? 'true' : 'false'; ?>"
        aria-controls="ordersSubmenu"
        aria-haspopup="true"
      >
        <span class="flex items-center gap-3">
          <span class="text-slate-500"><?php echo $icons['Orders']; ?></span>
          <span class="text-sm">Orders</span>
        </span>

        <svg id="ordersToggleArrow" class="w-4 h-4 transition-transform <?php echo $ordersArrowRotate; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div id="ordersSubmenu" class="ml-4 mt-1 space-y-1 overflow-hidden transition-all <?php echo $ordersSubMax; ?>" aria-hidden="<?php echo $isOrdersArea ? 'false' : 'true'; ?>">
        <a href="/admin/orders.php" class="<?php echo is_active_link(['orders','orders.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Orders</span>
        </a>

        <a href="/admin/invoices.php" class="<?php echo is_active_link(['invoices','invoices.php','invoice']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Invoices</span>
        </a>

        <a href="/admin/shipments.php" class="<?php echo is_active_link(['shipments','shipments.php','shipment']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Shipments</span>
        </a>
      </div>
    </div>

    <!-- loop the rest -->
    <?php foreach ($nav as $item): 
        $classes = is_active_link($item['match']);
    ?>
      <a href="<?php echo $item['href']; ?>" class="<?php echo $classes; ?> flex items-center gap-3 px-4 py-2 rounded-md" title="<?php echo htmlspecialchars($item['label']); ?>">
        <span class="text-slate-500"><?php echo $icons[$item['label']] ?? '•'; ?></span>
        <span class="text-sm"><?php echo htmlspecialchars($item['label']); ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="px-4 mt-6 text-xs text-slate-500">
    Signed in as <strong class="text-slate-700"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? ($_SESSION['admin_user'] ?? 'Admin')); ?></strong>
  </div>
</aside>

<style>
/* style hooks used by header.php and other admin pages */
.sidebar-link { display:flex; align-items:center; gap:0.75rem; padding:0.5rem 0.75rem; color:#4b5563; text-decoration:none; border-radius:0.5rem; font-weight:600; transition:background .12s, color .12s; }
.sidebar-link:hover { background:#f3f4f6; color:#374151; }
.sidebar-link.active { background:#eef2ff; color:#6366f1; font-weight:700; }

/* submenu helper sizes */
#productsSubmenu.max-h-0, #ordersSubmenu.max-h-0, #appearanceSubmenu.max-h-0 { max-height: 0; }
#productsSubmenu.max-h-40, #ordersSubmenu.max-h-40, #appearanceSubmenu.max-h-40 { max-height: 240px; }
#productsSubmenu, #ordersSubmenu, #appearanceSubmenu { transition: max-height .18s ease-in-out; overflow: hidden; }

/* arrow rotation helper */
.rotate-180 { transform: rotate(180deg); }

/* small responsive */
@media (max-width: 980px) {
  aside.w-64 { width: 56px; } /* optional collapsed layout on small screens if desired */
}
</style>

<script>
/**
 * Toggle a submenu by id (adds/removes max-h classes and rotates arrow)
 * Works with the `max-h-0` / `max-h-40` classes above.
 */
function toggleSidebarSubmenu(submenuId, arrowId) {
  const submenu = document.getElementById(submenuId);
  const arrow = document.getElementById(arrowId);
  if (!submenu) return;

  const closed = submenu.classList.contains('max-h-0');
  if (closed) {
    submenu.classList.remove('max-h-0');
    submenu.classList.add('max-h-40');
    submenu.setAttribute('aria-hidden', 'false');
    if (arrow) arrow.classList.add('rotate-180');
  } else {
    submenu.classList.remove('max-h-40');
    submenu.classList.add('max-h-0');
    submenu.setAttribute('aria-hidden', 'true');
    if (arrow) arrow.classList.remove('rotate-180');
  }
}
</script>