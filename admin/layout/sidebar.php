<?php
// admin/sidebar.php
// Complete sidebar with Dashboard, Appearance (Banner), Products (submenu), Orders (submenu -> Invoices), Categories, Users, Settings
// Keeps active highlight logic based on current request URI.

if (session_status() === PHP_SESSION_NONE) session_start();

// Determine current path & file
$req_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$req_file = basename($req_path);

// helper: returns 'sidebar-link' plus 'active' when candidate matches
if (!function_exists('is_active_link')) {
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
}

// Icons map used for repeated items
$icons = [
  'Dashboard' => '🏠',
  'Products'  => '📦',
  'Orders'    => '🛒',
  'Appearance'=> '🎨',
  'Blog'      => '📝',
  'Ayurvedh Blog' => '🌿',
  'Media'     => '🖼️',
  'Categories'=> '📁',
  'Users'     => '👥',
  'Offers & Coupons' => '🎟️',
  'Subscription Reports' => '📈',
  'Subscribers' => '👑',
  'Subscription Settings' => '💰',
  'Settings'  => '⚙️',
];

// Primary nav entries (other than Dashboard / parents rendered explicitly)
$nav = [
  ['label'=>'Categories','href'=>'/admin/categories.php','match'=>['categories','categories.php','category']],
  ['label'=>'Users','href'=>'/admin/users.php','match'=>['users','users.php','user']],
  ['label'=>'Offers & Coupons','href'=>'/admin/coupons.php','match'=>['coupons','coupons.php','coupon','coupons_add','coupons_edit']],
  ['label'=>'Subscription Reports','href'=>'/admin/subscription_reports.php','match'=>['subscription_reports','subscription_reports.php']],
  ['label'=>'Subscribers','href'=>'/admin/subscription_subscribers.php','match'=>['subscription_subscribers','subscription_subscribers.php']],
  ['label'=>'Subscription Settings','href'=>'/admin/subscription_settings.php','match'=>['subscription_settings','subscription_settings.php']],
  ['label'=>'Settings','href'=>'/admin/settings.php','match'=>['settings','settings.php']],
];

// Determine if current page is inside special parents (Products / Orders / Appearance)
$isProductArea = in_array($req_file, [
                    'products.php',
                    'add_product.php',
                    'edit_product.php',
                    'product_reviews.php',
                    'product_inventory.php',
                    'tags.php',
                    'labels.php',               // 👈 add
                    'filter_groups.php',        // 👈 add
                    'filter_group_add.php',     // 👈 add
                    'filter_group_edit.php',    // 👈 add
                    'filter_options.php',       // 👈 add
                    'filter_option_add.php',    // 👈 add
                    'filter_option_edit.php'    // 👈 add
                ]) ||
                stripos($req_path, '/admin/products') === 0 ||
                stripos($req_path, '/admin/product_reviews') === 0 ||
                stripos($req_path, '/admin/product_inventory') === 0 ||
                stripos($req_path, '/admin/tags') === 0 ||
                stripos($req_path, '/admin/filter_groups') === 0 ||   // 👈 add
                stripos($req_path, '/admin/filter_options') === 0;    // 👈 add

$isOrdersArea = in_array($req_file, ['orders.php','order_view.php','invoices.php','invoice_view.php','shipments.php']) ||
                stripos($req_path, '/admin/orders') === 0 ||
                stripos($req_path, '/admin/invoices') === 0 ||
                stripos($req_path, '/admin/shipments') === 0;

$isAppearanceArea = in_array($req_file, ['banner.php','appearance.php','appearance_homepage.php','appearance_footer.php','product_highlights.php']) ||
                    stripos($req_path, '/admin/banner.php') === 0 ||
                    stripos($req_path, '/admin/appearance') === 0 ||
                    stripos($req_path, '/admin/product_highlights') === 0;

$isMediaArea = in_array($req_file, ['media.php']) ||
               stripos($req_path, '/admin/media') === 0;

$blogScopeParam = strtolower(trim((string)($_GET['scope'] ?? '')));
$isAyurvedhBlogScope = $blogScopeParam === 'ayurvedh';
$isBlogScopePage = in_array($req_file, [
    'blogs.php',
    'add_blog.php',
    'edit_blog.php',
    'delete_blog.php',
    'blog_categories.php',
    'add_blog_category.php',
    'edit_blog_category.php',
    'blog_tags.php',
    'add_blog_tag.php',
    'edit_blog_tag.php',
    'delete_blog_tag.php',
], true);
$isDefaultBlogArea = ($isBlogScopePage && !$isAyurvedhBlogScope) || in_array($req_file, ['authors.php', 'add_author.php', 'edit_author.php'], true);
$isAyurvedhBlogArea = $isBlogScopePage && $isAyurvedhBlogScope;
$isBlogCrudPage = in_array($req_file, ['blogs.php', 'add_blog.php', 'edit_blog.php', 'delete_blog.php'], true);
$isBlogCategoryPage = in_array($req_file, ['blog_categories.php', 'add_blog_category.php', 'edit_blog_category.php'], true);
$isBlogTagPage = in_array($req_file, ['blog_tags.php', 'add_blog_tag.php', 'edit_blog_tag.php', 'delete_blog_tag.php'], true);

$defaultBlogPostsActive = in_array($req_file, ['blogs.php', 'delete_blog.php'], true) && !$isAyurvedhBlogScope;
$defaultBlogAddActive = in_array($req_file, ['add_blog.php', 'edit_blog.php'], true) && !$isAyurvedhBlogScope;
$defaultBlogCategoriesActive = $isBlogCategoryPage && !$isAyurvedhBlogScope;
$defaultBlogTagsActive = $isBlogTagPage && !$isAyurvedhBlogScope;
$ayurvedhBlogPostsActive = $isBlogCrudPage && $isAyurvedhBlogScope;
$ayurvedhBlogAddActive = in_array($req_file, ['add_blog.php', 'edit_blog.php'], true) && $isAyurvedhBlogScope;
$ayurvedhBlogCategoriesActive = $isBlogCategoryPage && $isAyurvedhBlogScope;
$ayurvedhBlogTagsActive = $isBlogTagPage && $isAyurvedhBlogScope;

?>
<!-- Sidebar -->
<aside class="w-64 bg-white border-r border-gray-200 min-h-screen shrink-0" aria-label="Sidebar">
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

    <!-- Pages (Page Builder) -->
    <a href="/admin/pages/index.php"
       class="<?php echo is_active_link(['pages','editor.php']); ?> flex items-center gap-3 px-4 py-2 rounded-md"
       title="Website Pages">
      <span class="text-slate-500">📄</span>
      <span class="text-sm">Pages</span>
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

        <a href="/admin/appearance_homepage.php" class="<?php echo is_active_link(['appearance_homepage','appearance_homepage.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Homepage</span>
        </a>

        <a href="/admin/appearance_footer.php" class="<?php echo is_active_link(['appearance_footer','appearance_footer.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Footer</span>
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

        <!-- Product Groups -->
        <a href="/admin/product_groups.php"
           class="<?php echo is_active_link(['product_groups','product_groups.php']); ?> block px-3 py-2 rounded-md">
           <span class="text-slate-500">•</span>
           <span class="text-sm">Product Groups</span>
        </a>

        <a href="/admin/tags.php"
          class="<?php echo is_active_link(['tags','tags.php','product_tags']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Product Tags</span>
        </a>

        <!-- ✅ Product Labels -->
        <a href="/admin/labels.php"
          class="<?php echo is_active_link(['labels','labels.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Product Labels</span>
        </a>

        <!-- ✅ New menu item -->
        <a href="/admin/filter_groups.php"
          class="<?php echo is_active_link([
              'filter_groups',
              'filter_groups.php',
              'filter_options',
              'filter_options.php',
              'filter_group_add',
              'filter_group_edit',
              'filter_option_add',
              'filter_option_edit'
          ]); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Product Filters</span>
        </a>

        <a href="/admin/product_reviews.php" class="<?php echo is_active_link(['product_reviews','product_reviews.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Product Reviews</span>
        </a>

        <a href="/admin/product_inventory.php"
          class="<?php echo is_active_link(['product_inventory','product_inventory.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Inventory Management</span>
        </a>

        <a href="/admin/external_reviews.php"
          class="<?php echo is_active_link(['external_reviews','external_reviews.php','external_review_form']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">External Reviews</span>
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
          <span class="text-sm">All Orders</span>
        </a>

        <a href="/admin/order_returns.php" class="<?php echo is_active_link(['order_returns','order_returns.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Order Returns</span>
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

    <!-- Blog parent (collapsible) -->
    <?php
      $blogParentActive = $isDefaultBlogArea ? 'sidebar-link active' : 'sidebar-link';
      $blogSubMax = $isDefaultBlogArea ? 'max-h-40' : 'max-h-0';
      $blogArrowRotate = $isDefaultBlogArea ? 'rotate-180' : '';
    ?>
    <div class="px-2">
      <button
        id="blogToggle"
        onclick="toggleSidebarSubmenu('blogSubmenu','blogToggleArrow')"
        class="<?php echo $blogParentActive; ?> w-full flex items-center justify-between gap-3 px-4 py-2 rounded-md"
        aria-expanded="<?php echo $isDefaultBlogArea ? 'true' : 'false'; ?>"
        aria-controls="blogSubmenu"
        aria-haspopup="true"
      >
        <span class="flex items-center gap-3">
          <span class="text-slate-500"><?php echo $icons['Blog']; ?></span>
          <span class="text-sm">Blog</span>
        </span>

        <svg id="blogToggleArrow" class="w-4 h-4 transition-transform <?php echo $blogArrowRotate; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div id="blogSubmenu" class="ml-4 mt-1 space-y-1 overflow-hidden transition-all <?php echo $blogSubMax; ?>" aria-hidden="<?php echo $isDefaultBlogArea ? 'false' : 'true'; ?>">
        <a href="/admin/blogs.php" class="<?php echo $defaultBlogPostsActive ? 'sidebar-link active' : 'sidebar-link'; ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">All Posts</span>
        </a>

        <a href="/admin/add_blog.php" class="<?php echo $defaultBlogAddActive ? 'sidebar-link active' : 'sidebar-link'; ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Add New Post</span>
        </a>

        <a href="/admin/blog_categories.php" class="<?php echo $defaultBlogCategoriesActive ? 'sidebar-link active' : 'sidebar-link'; ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Categories</span>
        </a>

        <a href="/admin/authors.php" class="<?php echo is_active_link(['authors','authors.php','add_author','edit_author']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Authors</span>
        </a>

        <a href="/admin/blog_tags.php" class="<?php echo $defaultBlogTagsActive ? 'sidebar-link active' : 'sidebar-link'; ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Blog Tags</span>
        </a>
      </div>
    </div>

    <!-- Ayurvedh Blog parent (collapsible) -->
    <?php
      $ayurvedhBlogParentActive = $isAyurvedhBlogArea ? 'sidebar-link active' : 'sidebar-link';
      $ayurvedhBlogSubMax = $isAyurvedhBlogArea ? 'max-h-40' : 'max-h-0';
      $ayurvedhBlogArrowRotate = $isAyurvedhBlogArea ? 'rotate-180' : '';
    ?>
    <div class="px-2">
      <button
        id="ayurvedhBlogToggle"
        onclick="toggleSidebarSubmenu('ayurvedhBlogSubmenu','ayurvedhBlogToggleArrow')"
        class="<?php echo $ayurvedhBlogParentActive; ?> w-full flex items-center justify-between gap-3 px-4 py-2 rounded-md"
        aria-expanded="<?php echo $isAyurvedhBlogArea ? 'true' : 'false'; ?>"
        aria-controls="ayurvedhBlogSubmenu"
        aria-haspopup="true"
      >
        <span class="flex items-center gap-3">
          <span class="text-slate-500"><?php echo $icons['Ayurvedh Blog']; ?></span>
          <span class="text-sm">Ayurvedh Blog</span>
        </span>

        <svg id="ayurvedhBlogToggleArrow" class="w-4 h-4 transition-transform <?php echo $ayurvedhBlogArrowRotate; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div id="ayurvedhBlogSubmenu" class="ml-4 mt-1 space-y-1 overflow-hidden transition-all <?php echo $ayurvedhBlogSubMax; ?>" aria-hidden="<?php echo $isAyurvedhBlogArea ? 'false' : 'true'; ?>">
        <a href="/admin/blogs.php?scope=ayurvedh" class="<?php echo $ayurvedhBlogPostsActive ? 'sidebar-link active' : 'sidebar-link'; ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">All Posts</span>
        </a>

        <a href="/admin/add_blog.php?scope=ayurvedh" class="<?php echo $ayurvedhBlogAddActive ? 'sidebar-link active' : 'sidebar-link'; ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Add New Post</span>
        </a>

        <a href="/admin/blog_categories.php?scope=ayurvedh" class="<?php echo $ayurvedhBlogCategoriesActive ? 'sidebar-link active' : 'sidebar-link'; ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Categories</span>
        </a>

        <a href="/admin/blog_tags.php?scope=ayurvedh" class="<?php echo $ayurvedhBlogTagsActive ? 'sidebar-link active' : 'sidebar-link'; ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Tags</span>
        </a>
      </div>
    </div>

    <!-- Categories parent (collapsible) -->
    <?php
      $isCatArea = in_array($req_file, ['categories.php','add_category.php']) || stripos($req_path, '/admin/categories') === 0;
      $catParentActive = $isCatArea ? 'sidebar-link active' : 'sidebar-link';
      $catSubMax = $isCatArea ? 'max-h-40' : 'max-h-0';
      $catArrowRotate = $isCatArea ? 'rotate-180' : '';
    ?>
    <div class="px-2">
      <button
        id="catToggle"
        onclick="toggleSidebarSubmenu('catSubmenu','catToggleArrow')"
        class="<?php echo $catParentActive; ?> w-full flex items-center justify-between gap-3 px-4 py-2 rounded-md"
        aria-expanded="<?php echo $isCatArea ? 'true' : 'false'; ?>"
        aria-controls="catSubmenu"
        aria-haspopup="true"
      >
        <span class="flex items-center gap-3">
          <span class="text-slate-500"><?php echo $icons['Categories']; ?></span>
          <span class="text-sm">Categories</span>
        </span>

        <svg id="catToggleArrow" class="w-4 h-4 transition-transform <?php echo $catArrowRotate; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div id="catSubmenu" class="ml-4 mt-1 space-y-1 overflow-hidden transition-all <?php echo $catSubMax; ?>" aria-hidden="<?php echo $isCatArea ? 'false' : 'true'; ?>">
        <a href="/admin/categories.php" class="<?php echo is_active_link(['categories','categories.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">All Categories</span>
        </a>

        <a href="/admin/add_category.php" class="<?php echo is_active_link(['add_category','add_category.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Add Category</span>
        </a>
      </div>
    </div>

    <!-- Concerns parent (collapsible) -->
    <?php
      $isConcernArea = in_array($req_file, ['concerns.php','add_concern.php']) || stripos($req_path, '/admin/concerns') === 0;
      $concernParentActive = $isConcernArea ? 'sidebar-link active' : 'sidebar-link';
      $concernSubMax = $isConcernArea ? 'max-h-40' : 'max-h-0';
      $concernArrowRotate = $isConcernArea ? 'rotate-180' : '';
    ?>
    <div class="px-2">
      <button
        id="concernToggle"
        onclick="toggleSidebarSubmenu('concernSubmenu','concernToggleArrow')"
        class="<?php echo $concernParentActive; ?> w-full flex items-center justify-between gap-3 px-4 py-2 rounded-md"
        aria-expanded="<?php echo $isConcernArea ? 'true' : 'false'; ?>"
        aria-controls="concernSubmenu"
        aria-haspopup="true"
      >
        <span class="flex items-center gap-3">
          <span class="text-slate-500">❤️</span>
          <span class="text-sm">Shop by Concern</span>
        </span>

        <svg id="concernToggleArrow" class="w-4 h-4 transition-transform <?php echo $concernArrowRotate; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div id="concernSubmenu" class="ml-4 mt-1 space-y-1 overflow-hidden transition-all <?php echo $concernSubMax; ?>" aria-hidden="<?php echo $isConcernArea ? 'false' : 'true'; ?>">
        <a href="/admin/concerns.php" class="<?php echo is_active_link(['concerns','concerns.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">All Concerns</span>
        </a>

        <a href="/admin/add_concern.php" class="<?php echo is_active_link(['add_concern','add_concern.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Add Concern</span>
        </a>
      </div>
    </div>

    <!-- Shop by Seasonal (single link) -->
    <a href="/admin/seasonals.php"
       class="<?php echo is_active_link(['seasonals','seasonals.php','add_seasonal','add_seasonal.php']); ?> flex items-center gap-3 px-4 py-2 rounded-md"
       title="Shop by Seasonal">
      <span class="text-slate-500">⛅</span>
      <span class="text-sm">Shop by Seasonal</span>
    </a>

    <!-- Herbals parent (collapsible) -->
    <?php
      $isHerbalArea = in_array($req_file, ['herbals.php','add_herbal.php']) || stripos($req_path, '/admin/herbals') === 0;
      $herbalParentActive = $isHerbalArea ? 'sidebar-link active' : 'sidebar-link';
      $herbalSubMax = $isHerbalArea ? 'max-h-40' : 'max-h-0';
      $herbalArrowRotate = $isHerbalArea ? 'rotate-180' : '';
    ?>
    <div class="px-2">
      <button
        id="herbalToggle"
        onclick="toggleSidebarSubmenu('herbalSubmenu','herbalToggleArrow')"
        class="<?php echo $herbalParentActive; ?> w-full flex items-center justify-between gap-3 px-4 py-2 rounded-md"
        aria-expanded="<?php echo $isHerbalArea ? 'true' : 'false'; ?>"
        aria-controls="herbalSubmenu"
        aria-haspopup="true"
      >
        <span class="flex items-center gap-3">
          <span class="text-slate-500">🌿</span>
          <span class="text-sm">Shop by Herbal</span>
        </span>

        <svg id="herbalToggleArrow" class="w-4 h-4 transition-transform <?php echo $herbalArrowRotate; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div id="herbalSubmenu" class="ml-4 mt-1 space-y-1 overflow-hidden transition-all <?php echo $herbalSubMax; ?>" aria-hidden="<?php echo $isHerbalArea ? 'false' : 'true'; ?>">
        <a href="/admin/herbals.php" class="<?php echo is_active_link(['herbals','herbals.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">All Herbals</span>
        </a>

        <a href="/admin/add_herbal.php" class="<?php echo is_active_link(['add_herbal','add_herbal.php']); ?> block px-3 py-2 rounded-md">
          <span class="text-slate-500">•</span>
          <span class="text-sm">Add Herbal</span>
        </a>
      </div>
    </div>

    <!-- Media (single link) -->
    <a href="/admin/media.php"
       class="<?php echo is_active_link(['media','media.php']); ?> flex items-center gap-3 px-4 py-2 rounded-md"
       title="Media Library">
      <span class="text-slate-500">🖼️</span>
      <span class="text-sm">Media</span>
    </a>

    <!-- loop the rest -->
    <?php foreach ($nav as $item): 
        // Categories is handled manually now, skip it in loop
        if($item['label'] === 'Categories') continue;
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
#productsSubmenu.max-h-0, #ordersSubmenu.max-h-0, #appearanceSubmenu.max-h-0, #blogSubmenu.max-h-0, #ayurvedhBlogSubmenu.max-h-0, #catSubmenu.max-h-0, #concernSubmenu.max-h-0, #herbalSubmenu.max-h-0 { max-height: 0; }
#productsSubmenu.max-h-40, #ordersSubmenu.max-h-40, #appearanceSubmenu.max-h-40, #blogSubmenu.max-h-40, #ayurvedhBlogSubmenu.max-h-40, #catSubmenu.max-h-40, #concernSubmenu.max-h-40, #herbalSubmenu.max-h-40 { max-height: 500px; }
#productsSubmenu, #ordersSubmenu, #appearanceSubmenu, #blogSubmenu, #ayurvedhBlogSubmenu, #catSubmenu, #concernSubmenu, #herbalSubmenu { transition: max-height .18s ease-in-out; overflow: hidden; }

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
