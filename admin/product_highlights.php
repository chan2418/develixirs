<?php
// admin/home_products.php
// This page manages ONLY homepage highlight sections:
// Trendy, Best Sellers, Sale, Top Rated.
// "New Herbal Products" (latest) is auto-fetched in index.php and NOT editable here.

session_start();
require_once __DIR__ . '/../includes/db.php';

// (optional) simple auth check – adjust to your logic
if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

// sections we support (NO 'latest' here)
$sections = [
    'trendy'      => 'Trendy Products',
    'best_seller' => 'Best Sellers',
    'sale'        => 'Sale Products',
    'top_rated'   => 'Top Rated Products',
];

// current section from GET or default
$currentSection = $_GET['section'] ?? 'trendy';
if (!isset($sections[$currentSection])) {
    $currentSection = 'trendy';
}

$message = '';
$error   = '';

// handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? 'trendy';
    if (!isset($sections[$section])) {
        $error = 'Invalid section selected.';
    } else {
        $ids = $_POST['product_ids'] ?? [];

        if (!is_array($ids)) {
            $ids = [];
        }

        try {
            $pdo->beginTransaction();

            // remove old entries for this section
            $del = $pdo->prepare("DELETE FROM homepage_products WHERE section = :section");
            $del->execute(['section' => $section]);

            // insert new ones in chosen order
            if (!empty($ids)) {
                $ins = $pdo->prepare("
                    INSERT INTO homepage_products (product_id, section, sort_order)
                    VALUES (:pid, :section, :sort_order)
                ");

                $sort = 1;
                foreach ($ids as $pid) {
                    $pid = (int)$pid;
                    if ($pid <= 0) continue;
                    $ins->execute([
                        'pid'        => $pid,
                        'section'    => $section,
                        'sort_order' => $sort++,
                    ]);
                }
            }

            $pdo->commit();
            $message = 'Homepage products updated for section: ' . $sections[$section];
            // redirect to avoid resubmit
            header('Location: product_highlights.php?section=' . urlencode($section) . '&saved=1');
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// If returned after save
if (isset($_GET['saved']) && $_GET['saved'] == '1' && !$error) {
    $message = 'Changes saved successfully.';
}

// fetch all products (you can add pagination later)
try {
    $stmt = $pdo->query("
        SELECT id, name, price, images
        FROM products
        ORDER BY id DESC
    ");
    $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allProducts = [];
    $error = 'Could not load products: ' . $e->getMessage();
}

// fetch selected products for current section
$selectedIds = [];
try {
    $stmt = $pdo->prepare("
        SELECT product_id
        FROM homepage_products
        WHERE section = :section
        ORDER BY sort_order ASC, id DESC
    ");
    $stmt->execute(['section' => $currentSection]);
    $selected = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $selectedIds = array_flip($selected); // for quick lookup
} catch (PDOException $e) {
    // ignore
}

// simple helper for image
function get_first_image($images) {
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Homepage Products – Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="bg-gray-100">
<div class="min-h-screen flex">

  <?php include __DIR__ . '/layout/header.php'; ?>

  <main class="flex-1 p-6">
    <div class="max-w-5xl mx-auto bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold text-gray-800">Homepage Product Sections</h1>
      </div>

      <!-- Section tabs (no Latest tab) -->
      <div class="flex flex-wrap gap-2 mb-6">
        <?php foreach ($sections as $key => $label): ?>
          <a
            href="?section=<?php echo urlencode($key); ?>"
            class="px-3 py-1 rounded-full text-sm <?php echo $key === $currentSection
              ? 'bg-indigo-600 text-white'
              : 'bg-gray-100 text-gray-700'; ?>"
          >
            <?php echo htmlspecialchars($label); ?>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($message): ?>
        <div class="mb-4 p-3 rounded bg-green-50 text-green-700 text-sm">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="mb-4 p-3 rounded bg-red-50 text-red-700 text-sm">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="section" value="<?php echo htmlspecialchars($currentSection); ?>">

        <p class="text-sm text-gray-600 mb-3">
          Select which products should appear in
          <strong><?php echo htmlspecialchars($sections[$currentSection]); ?></strong>
          on the homepage.
        </p>

        <div class="border rounded overflow-hidden bg-gray-50">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-100 border-b">
            <tr>
              <th class="px-3 py-2 text-left">
                <input type="checkbox" id="checkAll">
              </th>
              <th class="px-3 py-2 text-left">Product</th>
              <th class="px-3 py-2 text-left">Price</th>
              <th class="px-3 py-2 text-left">Preview</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($allProducts)): ?>
              <?php foreach ($allProducts as $p): ?>
                <?php
                  $checked = isset($selectedIds[$p['id']]);
                  $img = get_first_image($p['images'] ?? '');
                ?>
                <tr class="border-b bg-white hover:bg-gray-50">
                  <td class="px-3 py-2 align-top">
                    <input
                      type="checkbox"
                      name="product_ids[]"
                      value="<?php echo (int)$p['id']; ?>"
                      class="product-checkbox"
                      <?php echo $checked ? 'checked' : ''; ?>
                    >
                  </td>
                  <td class="px-3 py-2 align-top">
                    <div class="font-medium text-gray-800">
                      <?php echo htmlspecialchars($p['name']); ?>
                    </div>
                    <div class="text-xs text-gray-400">ID: <?php echo (int)$p['id']; ?></div>
                  </td>
                  <td class="px-3 py-2 align-top">
                    ₹<?php echo number_format((float)$p['price'], 2); ?>
                  </td>
                  <td class="px-3 py-2 align-top">
                    <img src="<?php echo htmlspecialchars($img); ?>" alt="" class="w-12 h-12 object-cover rounded border">
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="px-3 py-4 text-center text-gray-500">
                  No products found.
                </td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex justify-end">
          <button
            type="submit"
            class="px-4 py-2 bg-indigo-600 text-white text-sm rounded shadow hover:bg-indigo-700"
          >
            Save Selection
          </button>
        </div>
      </form>
    </div>
  </main>
</div>

<script>
// check / uncheck all
document.addEventListener('DOMContentLoaded', function () {
  const checkAll = document.getElementById('checkAll');
  const boxes = document.querySelectorAll('.product-checkbox');
  if (checkAll) {
    checkAll.addEventListener('change', function () {
      boxes.forEach(cb => cb.checked = checkAll.checked);
    });
  }
});
</script>
</body>
</html>