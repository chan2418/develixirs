<?php
// admin/banner.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/layout/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- ALLOWED SLOTS (TABS) ---------- */
$allowedSlots = [
    'home'          => 'Home Page',
    'product'       => 'Product Listing',
    'product_detail'=> 'Product Detail',
    'blog'          => 'Blog Page',
    'category'      => 'Sub Categories',
    'top_category'  => 'Top Level Categories',
];

/* ---------- CURRENT SLOT (TAB) ---------- */
$currentSlot = $_GET['slot'] ?? 'home';
if (!array_key_exists($currentSlot, $allowedSlots)) {
    $currentSlot = 'home';
}

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');

/* ---------- FETCH BANNERS FOR CURRENT SLOT (MAIN) ---------- */
$stmt = $pdo->prepare("SELECT * FROM banners WHERE page_slot = :slot ORDER BY id DESC");
$stmt->execute(['slot' => $currentSlot]);
$banners = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
$latest  = $banners[0] ?? null;

/* ---------- HOME LEFT & CENTER SIDEBAR BANNERS (SEPARATE SLOTS) ---------- */
$homeSideBanners   = [];
$homeSideLatest    = null;
$homeCenterBanners = [];
$homeCenterLatest  = null;
$homeOfferBanners  = [];
$homeOfferLatest   = null;

if ($currentSlot === 'home') {
    // LEFT SIDEBAR
    $stmt2 = $pdo->prepare("SELECT * FROM banners WHERE page_slot = 'home_sidebar' ORDER BY id DESC");
    $stmt2->execute();
    $homeSideBanners = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $homeSideLatest  = $homeSideBanners[0] ?? null;

    // CENTER SIDEBAR / CENTER PROMO
    $stmt3 = $pdo->prepare("SELECT * FROM banners WHERE page_slot = 'home_center' ORDER BY id DESC");
    $stmt3->execute();
    $homeCenterBanners = $stmt3->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $homeCenterLatest  = $homeCenterBanners[0] ?? null;

    // SIDEBAR OFFER CARD (for “Limited Offer” section)
    $stmt4 = $pdo->prepare("SELECT * FROM banners WHERE page_slot = 'home_offer' ORDER BY id DESC");
    $stmt4->execute();
    $homeOfferBanners = $stmt4->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $homeOfferLatest  = $homeOfferBanners[0] ?? null;
}

/* ---------- FETCH CATEGORIES FOR DROPDOWN (SUB / TOP) ---------- */
$categoriesForDropdown = [];
$categoryMap = [];

if (in_array($currentSlot, ['category', 'top_category'], true)) {
    try {
        $cols   = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_ASSOC);
        $fields = array_column($cols, 'Field');

        $labelField = in_array('title', $fields, true)
            ? 'title'
            : (in_array('name', $fields, true) ? 'name' : null);

        if ($labelField !== null) {
            if ($currentSlot === 'category') {
                // SUB CATEGORIES ONLY
                $sql = "
                    SELECT id, {$labelField} AS label
                    FROM categories
                    WHERE parent_id IS NOT NULL AND parent_id <> 0
                    ORDER BY label ASC
                ";
            } else {
                // TOP LEVEL CATEGORIES ONLY
                $sql = "
                    SELECT id, {$labelField} AS label
                    FROM categories
                    WHERE parent_id IS NULL OR parent_id = 0
                    ORDER BY label ASC
                ";
            }

            $stmtCats = $pdo->query($sql);
            $categoriesForDropdown = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

            foreach ($categoriesForDropdown as $c) {
                $categoryMap[(int)$c['id']] = $c['label'];
            }
        }
    } catch (Exception $e) {
        $categoriesForDropdown = [];
        $categoryMap = [];
    }
}

/* ---------- FLASH MESSAGES ---------- */
$formErrors  = $_SESSION['form_errors'] ?? [];
$successMsg  = $_SESSION['success_msg'] ?? '';
unset($_SESSION['form_errors'], $_SESSION['success_msg']);
?>
<link rel="stylesheet" href="/assets/css/admin.css">

<style>
  /* LOCAL OVERRIDES JUST FOR THIS PAGE */
  .banner-admin-page .card {
    position: relative;
    overflow: visible;
  }

  .banner-admin-page .banner-preview-wrapper img,
  .banner-admin-page .banner-list-thumb img {
    position: static !important;
    display: block;
    max-width: 100%;
    height: auto;
  }

  .banner-admin-page .page-wrap {
    max-width: 1000px;
    margin: 28px auto;
  }

  .banner-tabs {
    display: inline-flex;
    gap: 6px;
    padding: 4px;
    background: #f1f5f9;
    border-radius: 999px;
    margin: 10px 0 16px;
  }
  .banner-tabs a {
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 13px;
    text-decoration: none;
    color: #475569;
    border: 1px solid transparent;
    background: transparent;
  }
  .banner-tabs a.active {
    background: #2563eb;
    color: #fff;
    border-color: #1d4ed8;
  }

  .banner-upload-row {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
    position: relative;
    z-index: 2;
  }

  .banner-upload-left {
    flex: 1;
    min-width: 280px;
  }

  .banner-upload-right {
    width: 360px;
  }

  .banner-preview-wrapper {
    margin-top: 18px;
    position: relative;
    z-index: 1;
  }

  .banner-existing-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
  }

  .banner-existing-item {
    width: 230px;
    border: 1px solid #eef2f7;
    border-radius: 10px;
    overflow: hidden;
    background: #f9fbff;
  }

  .banner-existing-thumb {
    width: 100%;
    height: 120px;
    overflow: hidden;
    background: #f3f6fb;
  }

  .banner-existing-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .banner-existing-body {
    padding: 8px 10px;
    font-size: 12px;
  }

  .alert-success {
    padding: 10px;
    border-radius: 8px;
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #bbf7d0;
    margin-bottom: 12px;
    font-size: 13px;
  }
  .alert-error {
    padding: 10px;
    border-radius: 8px;
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
    margin-bottom: 8px;
    font-size: 13px;
  }

  @media (max-width: 700px) {
    .banner-upload-right {
      width: 100%;
    }
  }
</style>

<div class="page-wrap banner-admin-page">
  <h1 style="font-size:20px;font-weight:800;margin-bottom:4px;">Banners</h1>
  <p style="color:#64748b;margin-bottom:6px;">
    Upload one or more banner images — recommended size:
    <strong>1600×600</strong> or similar wide ratio.
  </p>

  <!-- Tabs for slots -->
  <div class="banner-tabs">
    <?php foreach ($allowedSlots as $slotKey => $slotLabel): ?>
      <a
        href="banner.php?slot=<?php echo urlencode($slotKey); ?>"
        class="<?php echo $slotKey === $currentSlot ? 'active' : ''; ?>"
      >
        <?php echo htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8'); ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Slot label info -->
  <p style="font-size:12px;color:#64748b;margin-bottom:12px;">
    You are editing banners for:
    <strong><?php echo htmlspecialchars($allowedSlots[$currentSlot], ENT_QUOTES, 'UTF-8'); ?></strong>
  </p>

  <!-- Flash messages -->
  <?php if ($successMsg): ?>
    <div class="alert-success"><?php echo htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <?php if (!empty($formErrors)): ?>
    <?php foreach ($formErrors as $err): ?>
      <div class="alert-error"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- MAIN BANNERS FOR CURRENT SLOT -->
  <div class="card" style="padding:18px;">
    <form action="save_banner.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
      <input type="hidden" name="page_slot" value="<?php echo htmlspecialchars($currentSlot, ENT_QUOTES, 'UTF-8'); ?>">

      <div class="banner-upload-row">
        <div class="banner-upload-left">
          <label style="font-weight:700;display:block;margin-bottom:6px;">Banner images</label>
          <label style="display:block;border:2px dashed #d8e1f0;padding:14px;border-radius:10px;cursor:pointer;background:#fbfdff;text-align:center;">
            <div style="font-weight:700;">Click to choose files or drag &amp; drop</div>
            <div style="font-size:13px;color:#555;margin-top:6px;">JPG, PNG, WEBP — max 5MB each</div>
            <input
              type="file"
              name="banners[]"
              accept="image/*"
              multiple
              style="display:none;"
              data-preview-target="bannerPreviewMain">
          </label>
        </div>

        <div class="banner-upload-right">

          <?php if (in_array($currentSlot, ['category', 'top_category'], true)): ?>
            <label style="font-weight:700;display:block;margin-bottom:6px;margin-top:4px;">
              Select <?php echo $currentSlot === 'top_category' ? 'Top Level Category' : 'Subcategory'; ?>
            </label>
            <select
              name="category_id"
              style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;margin-bottom:8px;font-size:13px;">
              <option value="">-- Choose one --</option>
              <?php
                $selectedCatId = isset($latest['category_id']) ? (int)$latest['category_id'] : 0;
                foreach ($categoriesForDropdown as $c):
                  $cid = (int)$c['id'];
              ?>
                <option
                  value="<?php echo $cid; ?>"
                  <?php echo $cid === $selectedCatId ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($c['label'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p style="font-size:11px;color:#64748b;margin-bottom:10px;">
              The selected <?php echo $currentSlot === 'top_category' ? 'top level category' : 'subcategory'; ?> will be used
              on the front-end to show this banner for that category page.
            </p>
          <?php endif; ?>

          <label style="font-weight:700;display:block;margin-bottom:6px;">Alt text (for new banners)</label>
          <input
            name="alt_text"
            class="input-field"
            value="<?php echo htmlspecialchars($latest['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Alt text for accessibility"
            style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;"
          >

          <label style="font-weight:700;display:block;margin-top:12px;margin-bottom:6px;">
            Optional Link (where new banners point)
          </label>
          <input
            name="link"
            value="<?php echo htmlspecialchars($latest['link'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="https://example.com"
            style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;"
          >

          <div style="display:flex;gap:8px;margin-top:12px;align-items:center;">
            <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
              <input type="checkbox" name="is_active" value="1"
                <?php if (empty($latest) || (int)($latest['is_active'] ?? 1) === 1) echo 'checked'; ?>>
              Active (for new banners)
            </label>
            <button
              type="submit"
              class="btn primary"
              style="margin-left:auto;padding:8px 16px;border-radius:8px;background:#2563eb;color:#fff;border:none;font-size:13px;cursor:pointer;">
              Save banners
            </button>
          </div>
        </div>
      </div>

      <!-- main preview (latest banner for this slot) -->
      <div class="banner-preview-wrapper">
        <div style="font-weight:700;margin-bottom:8px;">Latest banner preview</div>
        <?php if (!empty($latest) && !empty($latest['filename'])):
          $src = '/assets/uploads/banners/' . ltrim($latest['filename'], '/');
        ?>
          <img
            id="bannerPreviewMain"
            src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>"
            alt="<?php echo htmlspecialchars($latest['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            style="width:100%;max-height:360px;object-fit:cover;border-radius:10px;border:1px solid #eef2f7;"
          >
        <?php else: ?>
          <div
            id="bannerPreviewMain"
            style="width:100%;height:220px;border-radius:10px;background:#f3f6fb;display:flex;align-items:center;justify-content:center;color:#64748b;">
            No banner yet for this page.
          </div>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- existing banners list for this slot -->
  <div class="card" style="padding:18px;margin-top:20px;">
    <div style="font-weight:700;margin-bottom:10px;">
      Existing banners – <?php echo htmlspecialchars($allowedSlots[$currentSlot], ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <?php if (empty($banners)): ?>
      <div style="color:#64748b;font-size:14px;">No banners found for this page slot.</div>
    <?php else: ?>
      <div class="banner-existing-grid">
        <?php foreach ($banners as $b):
          $src = '/assets/uploads/banners/' . ltrim($b['filename'] ?? '', '/');
        ?>
          <div class="banner-existing-item">
            <div class="banner-existing-thumb banner-list-thumb">
              <img
                src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars($b['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="banner-existing-body">
              <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?php echo htmlspecialchars($b['alt_text'] ?: 'No alt text', ENT_QUOTES, 'UTF-8'); ?>
              </div>

              <?php if (!empty($b['link'])): ?>
                <div style="font-size:11px;color:#2563eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">
                  <?php echo htmlspecialchars($b['link'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($b['category_id']) && isset($categoryMap[(int)$b['category_id']])): ?>
                <div style="font-size:11px;color:#6b7280;margin-top:2px;">
                  Category: <?php echo htmlspecialchars($categoryMap[(int)$b['category_id']], ENT_QUOTES, 'UTF-8'); ?>
                </div>
              <?php endif; ?>

              <div style="margin-top:6px;font-size:11px;color:#64748b;display:flex;justify-content:space-between;align-items:center;">
                <span>ID: <?php echo (int)$b['id']; ?></span>
                <?php if (!empty($b['is_active'])): ?>
                  <span style="padding:2px 6px;border-radius:999px;background:#dcfce7;color:#166534;">Active</span>
                <?php else: ?>
                  <span style="padding:2px 6px;border-radius:999px;background:#f3f4f6;color:#4b5563;">Inactive</span>
                <?php endif; ?>
              </div>

              <div style="margin-top:6px; display:flex; justify-content:space-between; align-items:center;">
                <form
                  action="delete_banner.php"
                  method="post"
                  onsubmit="return confirm('Delete this banner permanently?');"
                  style="margin-left:auto;">
                  <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                  <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                  <button
                    type="submit"
                    style="border:none;background:#dc2626;color:#fff;padding:4px 8px;border-radius:6px;font-size:11px;cursor:pointer;">
                    Delete
                  </button>
                </form>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($currentSlot === 'home'): ?>
    <!-- EXTRA: LEFT SIDEBAR BANNER FOR HOME PAGE ONLY -->
    <div class="card" style="padding:18px;margin-top:24px;">
      <h2 style="font-size:16px;font-weight:700;margin-bottom:6px;">Left Sidebar Banner (Home Page)</h2>
      <p style="font-size:12px;color:#64748b;margin-bottom:12px;">
        This banner can be used for the left-hand side promo image on the homepage.
        It does <strong>not</strong> affect the main hero slider.
      </p>

      <form action="save_banner.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <!-- Use a different page_slot so it never mixes with main hero -->
        <input type="hidden" name="page_slot" value="home_sidebar">

        <div class="banner-upload-row">
          <div class="banner-upload-left">
            <label style="font-weight:700;display:block;margin-bottom:6px;">Sidebar banner image</label>
            <label style="display:block;border:2px dashed #d8e1f0;padding:14px;border-radius:10px;cursor:pointer;background:#fbfdff;text-align:center;">
              <div style="font-weight:700;">Click to choose file or drag &amp; drop</div>
              <div style="font-size:13px;color:#555;margin-top:6px;">JPG, PNG, WEBP — max 5MB</div>
              <input
                type="file"
                name="banners[]"
                accept="image/*"
                style="display:none;"
                data-preview-target="bannerPreviewSidebar">
            </label>
          </div>

          <div class="banner-upload-right">
            <label style="font-weight:700;display:block;margin-bottom:6px;">Alt text (sidebar)</label>
            <input
              name="alt_text"
              class="input-field"
              value="<?php echo htmlspecialchars($homeSideLatest['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              placeholder="Alt text for accessibility"
              style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;"
            >

            <label style="font-weight:700;display:block;margin-top:12px;margin-bottom:6px;">
              Optional Link
            </label>
            <input
              name="link"
              value="<?php echo htmlspecialchars($homeSideLatest['link'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              placeholder="https://example.com"
              style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;"
            >

            <div style="display:flex;gap:8px;margin-top:12px;align-items:center;">
              <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                <input type="checkbox" name="is_active" value="1"
                  <?php if (empty($homeSideLatest) || (int)($homeSideLatest['is_active'] ?? 1) === 1) echo 'checked'; ?>>
                Active
              </label>
              <button
                type="submit"
                class="btn primary"
                style="margin-left:auto;padding:8px 16px;border-radius:8px;background:#2563eb;color:#fff;border:none;font-size:13px;cursor:pointer;">
                Save sidebar banner
              </button>
            </div>
          </div>
        </div>

        <div class="banner-preview-wrapper">
          <div style="font-weight:700;margin-bottom:8px;">Sidebar banner preview</div>
          <?php if (!empty($homeSideLatest) && !empty($homeSideLatest['filename'])):
            $srcSide = '/assets/uploads/banners/' . ltrim($homeSideLatest['filename'], '/');
          ?>
            <img
              id="bannerPreviewSidebar"
              src="<?php echo htmlspecialchars($srcSide, ENT_QUOTES, 'UTF-8'); ?>"
              alt="<?php echo htmlspecialchars($homeSideLatest['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              style="width:100%;max-height:260px;object-fit:cover;border-radius:10px;border:1px solid #eef2f7;">
          <?php else: ?>
            <div
              id="bannerPreviewSidebar"
              style="width:100%;height:180px;border-radius:10px;background:#f3f6fb;display:flex;align-items:center;justify-content:center;color:#64748b;">
              No sidebar banner yet.
            </div>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Existing sidebar banners -->
    <div class="card" style="padding:18px;margin-top:20px;">
      <div style="font-weight:700;margin-bottom:10px;">Existing sidebar banners – Home Page</div>

      <?php if (empty($homeSideBanners)): ?>
        <div style="color:#64748b;font-size:14px;">No sidebar banners found for Home Page.</div>
      <?php else: ?>
        <div class="banner-existing-grid">
          <?php foreach ($homeSideBanners as $b):
            $src = '/assets/uploads/banners/' . ltrim($b['filename'] ?? '', '/');
          ?>
            <div class="banner-existing-item">
              <div class="banner-existing-thumb banner-list-thumb">
                <img
                  src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>"
                  alt="<?php echo htmlspecialchars($b['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="banner-existing-body">
                <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?php echo htmlspecialchars($b['alt_text'] ?: 'No alt text', ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <?php if (!empty($b['link'])): ?>
                  <div style="font-size:11px;color:#2563eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">
                    <?php echo htmlspecialchars($b['link'], ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                <?php endif; ?>

                <div style="margin-top:6px;font-size:11px;color:#64748b;display:flex;justify-content:space-between;align-items:center;">
                  <span>ID: <?php echo (int)$b['id']; ?></span>
                  <?php if (!empty($b['is_active'])): ?>
                    <span style="padding:2px 6px;border-radius:999px;background:#dcfce7;color:#166534;">Active</span>
                  <?php else: ?>
                    <span style="padding:2px 6px;border-radius:999px;background:#f3f4f6;color:#4b5563;">Inactive</span>
                  <?php endif; ?>
                </div>

                <div style="margin-top:6px; display:flex; justify-content:space-between; align-items:center;">
                  <form
                    action="delete_banner.php"
                    method="post"
                    onsubmit="return confirm('Delete this sidebar banner permanently?');"
                    style="margin-left:auto;">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                    <button
                      type="submit"
                      style="border:none;background:#dc2626;color:#fff;padding:4px 8px;border-radius:6px;font-size:11px;cursor:pointer;">
                      Delete
                    </button>
                  </form>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- CENTER SIDEBAR BANNER (HOME PAGE) -->
    <div class="card" style="padding:18px;margin-top:24px;">
      <h2 style="font-size:16px;font-weight:700;margin-bottom:6px;">Center Sidebar Banner (Home Page)</h2>
      <p style="font-size:12px;color:#64748b;margin-bottom:12px;">
        This banner can be used for a center promo image on the homepage.
        It is separate from the main hero slider and left sidebar banner.
      </p>

      <form action="save_banner.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <!-- Dedicated slot for center banner -->
        <input type="hidden" name="page_slot" value="home_center">

        <div class="banner-upload-row">
          <div class="banner-upload-left">
            <label style="font-weight:700;display:block;margin-bottom:6px;">Center banner image</label>
            <label style="display:block;border:2px dashed #d8e1f0;padding:14px;border-radius:10px;cursor:pointer;background:#fbfdff;text-align:center;">
              <div style="font-weight:700;">Click to choose file or drag &amp; drop</div>
              <div style="font-size:13px;color:#555;margin-top:6px;">JPG, PNG, WEBP — max 5MB</div>
              <input
                type="file"
                name="banners[]"
                accept="image/*"
                style="display:none;"
                data-preview-target="bannerPreviewCenter">
            </label>
          </div>

          <div class="banner-upload-right">
            <label style="font-weight:700;display:block;margin-bottom:6px;">Alt text (center)</label>
            <input
              name="alt_text"
              class="input-field"
              value="<?php echo htmlspecialchars($homeCenterLatest['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              placeholder="Alt text for accessibility"
              style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;"
            >

            <label style="font-weight:700;display:block;margin-top:12px;margin-bottom:6px;">
              Optional Link
            </label>
            <input
              name="link"
              value="<?php echo htmlspecialchars($homeCenterLatest['link'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              placeholder="https://example.com"
              style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;"
            >

            <div style="display:flex;gap:8px;margin-top:12px;align-items:center;">
              <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                <input type="checkbox" name="is_active" value="1"
                  <?php if (empty($homeCenterLatest) || (int)($homeCenterLatest['is_active'] ?? 1) === 1) echo 'checked'; ?>>
                Active
              </label>
              <button
                type="submit"
                class="btn primary"
                style="margin-left:auto;padding:8px 16px;border-radius:8px;background:#2563eb;color:#fff;border:none;font-size:13px;cursor:pointer;">
                Save center banner
              </button>
            </div>
          </div>
        </div>

        <div class="banner-preview-wrapper">
          <div style="font-weight:700;margin-bottom:8px;">Center banner preview</div>
          <?php if (!empty($homeCenterLatest) && !empty($homeCenterLatest['filename'])):
            $srcCenter = '/assets/uploads/banners/' . ltrim($homeCenterLatest['filename'], '/');
          ?>
            <img
              id="bannerPreviewCenter"
              src="<?php echo htmlspecialchars($srcCenter, ENT_QUOTES, 'UTF-8'); ?>"
              alt="<?php echo htmlspecialchars($homeCenterLatest['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              style="width:100%;max-height:260px;object-fit:cover;border-radius:10px;border:1px solid #eef2f7;">
          <?php else: ?>
            <div
              id="bannerPreviewCenter"
              style="width:100%;height:180px;border-radius:10px;background:#f3f6fb;display:flex;align-items:center;justify-content:center;color:#64748b;">
              No center banner yet.
            </div>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Existing center banners -->
    <div class="card" style="padding:18px;margin-top:20px;">
      <div style="font-weight:700;margin-bottom:10px;">Existing center banners – Home Page</div>

      <?php if (empty($homeCenterBanners)): ?>
        <div style="color:#64748b;font-size:14px;">No center banners found for Home Page.</div>
      <?php else: ?>
        <div class="banner-existing-grid">
          <?php foreach ($homeCenterBanners as $b):
            $src = '/assets/uploads/banners/' . ltrim($b['filename'] ?? '', '/');
          ?>
            <div class="banner-existing-item">
              <div class="banner-existing-thumb banner-list-thumb">
                <img
                  src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>"
                  alt="<?php echo htmlspecialchars($b['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="banner-existing-body">
                <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?php echo htmlspecialchars($b['alt_text'] ?: 'No alt text', ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <?php if (!empty($b['link'])): ?>
                  <div style="font-size:11px;color:#2563eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">
                    <?php echo htmlspecialchars($b['link'], ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                <?php endif; ?>

                <div style="margin-top:6px;font-size:11px;color:#64748b;display:flex;justify-content:space-between;align-items:center;">
                  <span>ID: <?php echo (int)$b['id']; ?></span>
                  <?php if (!empty($b['is_active'])): ?>
                    <span style="padding:2px 6px;border-radius:999px;background:#dcfce7;color:#166534;">Active</span>
                  <?php else: ?>
                    <span style="padding:2px 6px;border-radius:999px;background:#f3f4f6;color:#4b5563;">Inactive</span>
                  <?php endif; ?>
                </div>

                <div style="margin-top:6px; display:flex; justify-content:space-between; align-items:center;">
                  <form
                    action="delete_banner.php"
                    method="post"
                    onsubmit="return confirm('Delete this center banner permanently?');"
                    style="margin-left:auto;">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                    <button
                      type="submit"
                      style="border:none;background:#dc2626;color:#fff;padding:4px 8px;border-radius:6px;font-size:11px;cursor:pointer;">
                      Delete
                    </button>
                  </form>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

        <!-- SIDEBAR OFFER BANNER (HOME PAGE) -->
    <div class="card" style="padding:18px;margin-top:24px;">
      <h2 style="font-size:16px;font-weight:700;margin-bottom:6px;">Sidebar Offer Banner (Home Page)</h2>
      <p style="font-size:12px;color:#64748b;margin-bottom:12px;">
        This banner is used in the small “Limited Offer” section in the left sidebar on the homepage.
      </p>

      <form action="save_banner.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <!-- dedicated slot for the offer card -->
        <input type="hidden" name="page_slot" value="home_offer">

        <div class="banner-upload-row">
          <div class="banner-upload-left">
            <label style="font-weight:700;display:block;margin-bottom:6px;">Offer banner image</label>
            <label style="display:block;border:2px dashed #d8e1f0;padding:14px;border-radius:10px;cursor:pointer;background:#fbfdff;text-align:center;">
              <div style="font-weight:700;">Click to choose file or drag &amp; drop</div>
              <div style="font-size:13px;color:#555;margin-top:6px;">JPG, PNG, WEBP — max 5MB</div>
              <input
                type="file"
                name="banners[]"
                accept="image/*"
                style="display:none;"
                data-preview-target="bannerPreviewOffer">
            </label>
          </div>

          <div class="banner-upload-right">
            <label style="font-weight:700;display:block;margin-bottom:6px;">Alt text (offer)</label>
            <input
              name="alt_text"
              class="input-field"
              value="<?php echo htmlspecialchars($homeOfferLatest['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              placeholder="Alt text for accessibility"
              style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;"
            >

            <label style="font-weight:700;display:block;margin-top:12px;margin-bottom:6px;">
              Optional Link
            </label>
            <input
              name="link"
              value="<?php echo htmlspecialchars($homeOfferLatest['link'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              placeholder="https://example.com"
              style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;"
            >

            <div style="display:flex;gap:8px;margin-top:12px;align-items:center;">
              <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                <input type="checkbox" name="is_active" value="1"
                  <?php if (empty($homeOfferLatest) || (int)($homeOfferLatest['is_active'] ?? 1) === 1) echo 'checked'; ?>>
                Active
              </label>
              <button
                type="submit"
                class="btn primary"
                style="margin-left:auto;padding:8px 16px;border-radius:8px;background:#2563eb;color:#fff;border:none;font-size:13px;cursor:pointer;">
                Save offer banner
              </button>
            </div>
          </div>
        </div>

        <div class="banner-preview-wrapper">
          <div style="font-weight:700;margin-bottom:8px;">Offer banner preview</div>
          <?php if (!empty($homeOfferLatest) && !empty($homeOfferLatest['filename'])):
            $srcOffer = '/assets/uploads/banners/' . ltrim($homeOfferLatest['filename'], '/');
          ?>
            <img
              id="bannerPreviewOffer"
              src="<?php echo htmlspecialchars($srcOffer, ENT_QUOTES, 'UTF-8'); ?>"
              alt="<?php echo htmlspecialchars($homeOfferLatest['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              style="width:100%;max-height:260px;object-fit:cover;border-radius:10px;border:1px solid #eef2f7;">
          <?php else: ?>
            <div
              id="bannerPreviewOffer"
              style="width:100%;height:160px;border-radius:10px;background:#f3f6fb;display:flex;align-items:center;justify-content:center;color:#64748b;">
              No sidebar offer banner yet.
            </div>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Existing sidebar offer banners -->
    <div class="card" style="padding:18px;margin-top:20px;">
      <div style="font-weight:700;margin-bottom:10px;">Existing sidebar offer banners – Home Page</div>

      <?php if (empty($homeOfferBanners)): ?>
        <div style="color:#64748b;font-size:14px;">No sidebar offer banners found for Home Page.</div>
      <?php else: ?>
        <div class="banner-existing-grid">
          <?php foreach ($homeOfferBanners as $b):
            $src = '/assets/uploads/banners/' . ltrim($b['filename'] ?? '', '/');
          ?>
            <div class="banner-existing-item">
              <div class="banner-existing-thumb banner-list-thumb" style="height:100px;">
                <img
                  src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>"
                  alt="<?php echo htmlspecialchars($b['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="banner-existing-body">
                <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?php echo htmlspecialchars($b['alt_text'] ?: 'No alt text', ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <?php if (!empty($b['link'])): ?>
                  <div style="font-size:11px;color:#2563eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">
                    <?php echo htmlspecialchars($b['link'], ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                <?php endif; ?>

                <div style="margin-top:6px;font-size:11px;color:#64748b;display:flex;justify-content:space-between;align-items:center;">
                  <span>ID: <?php echo (int)$b['id']; ?></span>
                  <?php if (!empty($b['is_active'])): ?>
                    <span style="padding:2px 6px;border-radius:999px;background:#dcfce7;color:#166534;">Active</span>
                  <?php else: ?>
                    <span style="padding:2px 6px;border-radius:999px;background:#f3f4f6;color:#4b5563;">Inactive</span>
                  <?php endif; ?>
                </div>

                <div style="margin-top:6px; display:flex; justify-content:space-between; align-items:center;">
                  <form
                    action="delete_banner.php"
                    method="post"
                    onsubmit="return confirm('Delete this offer banner permanently?');"
                    style="margin-left:auto;">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                    <button
                      type="submit"
                      style="border:none;background:#dc2626;color:#fff;padding:4px 8px;border-radius:6px;font-size:11px;cursor:pointer;">
                      Delete
                    </button>
                  </form>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>

<script>
// handle previews for both main + sidebar + center file inputs
document.querySelectorAll('input[type=file][name="banners[]"]').forEach(function(input){
  input.addEventListener('change', function(){
    const file = this.files && this.files[0];
    if (!file) return;
    const url = URL.createObjectURL(file);
    const targetId = this.dataset.previewTarget || 'bannerPreviewMain';
    const el = document.getElementById(targetId);
    if (!el) return;

    if (el.tagName === 'IMG') {
      el.src = url;
    } else {
      el.innerHTML = '';
      const img = document.createElement('img');
      img.src = url;
      img.style.width = '100%';
      img.style.maxHeight =
        (targetId === 'bannerPreviewSidebar' || targetId === 'bannerPreviewCenter')
          ? '260px'
          : '360px';
      img.style.objectFit = 'cover';
      img.style.borderRadius = '10px';
      img.style.border = '1px solid #eef2f7';
      el.appendChild(img);
    }
  });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>