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
$homeBeforeBlogsBanners = [];
$homeBeforeBlogsLatest  = null;
$productSideBanners = [];
$productSideLatest  = null;
$productDetailSideBanners = [];
$productDetailSideLatest  = null;

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

    // BEFORE BLOGS BANNER (Wide Carousel)
    $stmt5 = $pdo->prepare("SELECT * FROM banners WHERE page_slot = 'home_before_blogs' ORDER BY id DESC");
    $stmt5->execute();
    $homeBeforeBlogsBanners = $stmt5->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $homeBeforeBlogsLatest  = $homeBeforeBlogsBanners[0] ?? null;
}
if ($currentSlot === 'product') {
    $stmtP = $pdo->prepare("SELECT * FROM banners WHERE page_slot = 'product_sidebar' ORDER BY id DESC");
    $stmtP->execute();
    $productSideBanners = $stmtP->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $productSideLatest  = $productSideBanners[0] ?? null;
}
if ($currentSlot === 'product_detail') {
    $stmtPD = $pdo->prepare("SELECT * FROM banners WHERE page_slot = 'product_detail_sidebar' ORDER BY id DESC");
    $stmtPD->execute();
    $productDetailSideBanners = $stmtPD->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $productDetailSideLatest  = $productDetailSideBanners[0] ?? null;
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

  <!-- HOME SUB-TABS (Visible only on Home slot) -->
  <?php if ($currentSlot === 'home'): ?>
    <div class="sub-tabs-container" style="margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; display: flex; gap: 24px;">
        <button class="sub-tab-btn active" onclick="switchHomeTab('main')" style="padding: 12px 4px; font-weight: 600; background: none; cursor: pointer; transition: all 0.2s;">
            Main Slider
        </button>
        <button class="sub-tab-btn" onclick="switchHomeTab('center')" style="padding: 12px 4px; font-weight: 600; background: none; cursor: pointer; transition: all 0.2s;">
            Center Carousel
        </button>
        <button class="sub-tab-btn" onclick="switchHomeTab('blogs')" style="padding: 12px 4px; font-weight: 600; background: none; cursor: pointer; transition: all 0.2s;">
            Wide Banner (Before Blogs)
        </button>
        <button class="sub-tab-btn" onclick="switchHomeTab('sidebar')" style="padding: 12px 4px; font-weight: 600; background: none; cursor: pointer; transition: all 0.2s;">
            Sidebar Ad
        </button>
        <button class="sub-tab-btn" onclick="switchHomeTab('offer')" style="padding: 12px 4px; font-weight: 600; background: none; cursor: pointer; transition: all 0.2s;">
            Special Offer
        </button>
    </div>

    <style>
        .sub-tab-btn.active { color: #3b82f6 !important; border-bottom-color: #3b82f6 !important; }
        .sub-tab-btn:hover { color: #1d4ed8 !important; }
        .home-section-content { display: none; animation: fadeIn 0.3s ease; }
        .home-section-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
  <?php endif; ?>

  <!-- MAIN BANNER UPLOADER (Generic for all slots, but wrapped for Home tab) -->
  <?php
    $mainWrapperId = ($currentSlot === 'home') ? 'home-main' : '';
    $mainWrapperClass = ($currentSlot === 'home') ? 'home-section-content active' : '';
  ?>
  <div id="<?= $mainWrapperId ?>" class="<?= $mainWrapperClass ?>">
      <div class="card" style="padding:18px;">
        <h2 style="font-size:16px;font-weight:700;margin-bottom:6px;">
            <?php echo ($currentSlot === 'home') ? 'Main Hero Slider' : 'Manage Banners'; ?>
        </h2>
        <form action="save_banner.php" method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
          <input type="hidden" name="page_slot" value="<?php echo htmlspecialchars($currentSlot, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="is_active" value="1">

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
              
              <!-- Media Library Integration -->
              <div style="margin-top:10px;">
                 <button type="button" id="addBannerMediaBtn" style="padding:8px 12px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                     <span>📁</span> Select from Library
                 </button>
                 <div id="banner_media_container" style="margin-top:10px;"></div>
              </div>
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
              <?php endif; ?>

              <label style="font-weight:700;display:block;margin-bottom:6px;">Alt text (for latest/all)</label>
              <input
                name="alt_text"
                class="input-field"
                value="<?php echo htmlspecialchars($latest['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Alt text"
                style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;"
              >

              <label style="font-weight:700;display:block;margin-top:12px;margin-bottom:6px;">
                Link (optional)
              </label>
              <input
                name="link"
                value="<?php echo htmlspecialchars($latest['link'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="https://example.com"
                style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;"
              >

              <div style="display:flex;gap:8px;margin-top:12px;align-items:center;">
                <button
                  type="submit"
                  class="btn primary"
                  style="margin-left:auto;padding:8px 16px;border-radius:8px;background:#2563eb;color:#fff;border:none;font-size:13px;cursor:pointer;">
                  Upload / Save
                </button>
              </div>
            </div>
          </div>

          <div class="banner-preview-wrapper">
            <div style="font-weight:700;margin-bottom:8px;">Preview (Last uploaded)</div>
            <?php if (!empty($latest) && !empty($latest['filename'])):
              $srcMain = '/assets/uploads/banners/' . ltrim($latest['filename'], '/');
            ?>
              <img
                id="bannerPreviewMain"
                src="<?php echo htmlspecialchars($srcMain, ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars($latest['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                style="width:100%;max-height:260px;object-fit:cover;border-radius:10px;border:1px solid #eef2f7;">
            <?php else: ?>
              <div
                id="bannerPreviewMain"
                style="width:100%;height:160px;border-radius:10px;background:#f3f6fb;display:flex;align-items:center;justify-content:center;color:#64748b;">
                No banner yet.
              </div>
            <?php endif; ?>
          </div>
        </form>

        <!-- Existing Banners -->
        <?php if (!empty($banners)): ?>
          <div class="card" style="padding:18px;margin-top:20px;">
            <div style="font-weight:700;margin-bottom:10px;">Existing Banners</div>
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

                    <?php if (in_array($currentSlot, ['category', 'top_category'], true) && !empty($b['category_id'])): ?>
                      <div style="font-size:11px;color:#d97706;margin-top:2px;font-weight:600;">
                        <span style="color:#4b5563;">Category:</span> 
                        <?php echo htmlspecialchars($categoryMap[$b['category_id']] ?? 'Unknown (ID:'.$b['category_id'].')', ENT_QUOTES); ?>
                      </div>
                    <?php endif; ?>

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
          </div>
        <?php endif; ?>
      </div>
  </div>

  <?php if ($currentSlot === 'home'): ?>

    <!-- 2) CENTER CAROUSEL -->
    <div id="home-center" class="home-section-content">
        <div class="card" style="padding:18px;margin-top:24px;">
          <h2 style="font-size:16px;font-weight:700;margin-bottom:6px;">Center Carousel</h2>
          <p style="font-size:12px;color:#64748b;margin-bottom:12px;">Managing the rotating banners in the middle section.</p>
          <form action="save_banner.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="page_slot" value="home_center">
            
            <div class="banner-upload-row">
              <div class="banner-upload-left">
                <label style="font-weight:700;display:block;margin-bottom:6px;">Center banner image</label>
                <label style="display:block;border:2px dashed #d8e1f0;padding:14px;border-radius:10px;cursor:pointer;background:#fbfdff;text-align:center;">
                  <div style="font-weight:700;">Click to choose file</div>
                  <input type="file" name="banners[]" accept="image/*" multiple style="display:none;" data-preview-target="bannerPreviewCenter">
                </label>
                <div style="margin-top:10px;">
                   <button type="button" id="addCenterMediaBtn" style="padding:8px 12px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                       <span>📁</span> Select from Library
                   </button>
                   <div id="center_media_container" style="margin-top:10px;"></div>
                </div>
              </div>
              <div class="banner-upload-right">
                <label style="font-weight:700;display:block;margin-bottom:6px;">Alt text</label>
                <input name="alt_text" class="input-field" value="<?php echo htmlspecialchars($homeCenterLatest['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Alt text" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">
                <label style="font-weight:700;display:block;margin-top:12px;margin-bottom:6px;">Action Link</label>
                <input name="link" value="<?php echo htmlspecialchars($homeCenterLatest['link'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">
                
                <div style="display:flex;gap:8px;margin-top:12px;align-items:center;">
                    <label style="display:flex;gap:8px;align-items:center;font-size:13px;"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                    <button type="submit" class="btn primary" style="margin-left:auto;padding:8px 16px;border-radius:8px;background:#2563eb;color:#fff;border:none;font-size:13px;cursor:pointer;">Save Center Banner</button>
                </div>
              </div>
            </div>
          </form>

          <!-- Existing Center Banners -->
          <?php if (!empty($homeCenterBanners)): ?>
            <div class="banner-existing-grid" style="margin-top:20px;">
              <?php foreach ($homeCenterBanners as $b): $src = '/assets/uploads/banners/' . ltrim($b['filename'], '/'); ?>
                <div class="banner-existing-item">
                    <div class="banner-existing-thumb banner-list-thumb"><img src="<?= htmlspecialchars($src) ?>" alt=""></div>
                    <div class="banner-existing-body">
                        <span>ID: <?= $b['id'] ?></span>
                        <form action="delete_banner.php" method="post" onsubmit="return confirm('Delete?');" style="display:inline-block;float:right;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" style="color:red;border:none;background:none;cursor:pointer;">Delete</button>
                        </form>
                    </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
    </div>

    <!-- 3) WIDE BANNER (BEFORE BLOGS) -->
    <div id="home-blogs" class="home-section-content">
        <div class="card" style="padding:18px;margin-top:24px;">
          <h2 style="font-size:16px;font-weight:700;margin-bottom:6px;">Wide Banner (Before Blogs)</h2>
          <p style="font-size:12px;color:#64748b;margin-bottom:12px;">Carousel slider before Latest Blogs.</p>
          <form action="save_banner.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="page_slot" value="home_before_blogs">
            <input type="hidden" name="is_active" value="1">
            
            <div class="banner-upload-row">
              <div class="banner-upload-left">
                <label style="font-weight:700;display:block;margin-bottom:6px;">Banner image</label>
                <label style="display:block;border:2px dashed #d8e1f0;padding:14px;border-radius:10px;cursor:pointer;background:#fbfdff;text-align:center;">
                  <div style="font-weight:700;">Click to choose file</div>
                  <input type="file" name="banners[]" accept="image/*" multiple style="display:none;" data-preview-target="bannerPreviewBlogs">
                </label>
                <div style="margin-top:10px;">
                   <button type="button" id="addBlogsMediaBtn" style="padding:8px 12px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                       <span>📁</span> Select from Library
                   </button>
                   <div id="blogs_media_container" style="margin-top:10px;"></div>
                </div>
              </div>
              <div class="banner-upload-right">
                <label style="font-weight:700;display:block;margin-bottom:6px;">Alt text</label>
                <input name="alt_text" class="input-field" value="<?php echo htmlspecialchars($homeBeforeBlogsLatest['alt_text'] ?? '', ENT_QUOTES); ?>" placeholder="Alt text" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">
                <label style="font-weight:700;display:block;margin-top:12px;margin-bottom:6px;">Link</label>
                <input name="link" value="<?php echo htmlspecialchars($homeBeforeBlogsLatest['link'] ?? '', ENT_QUOTES); ?>" placeholder="Link" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">
                
                <div style="display:flex;gap:8px;margin-top:12px;align-items:center;">
                    <button type="submit" class="btn primary" style="margin-left:auto;padding:8px 16px;border-radius:8px;background:#2563eb;color:#fff;border:none;cursor:pointer;">Save Blogs Banner</button>
                </div>
              </div>
            </div>
          </form>

          <!-- Existing Before Blogs Banners -->
          <?php if (!empty($homeBeforeBlogsBanners)): ?>
            <div class="banner-existing-grid" style="margin-top:20px;">
              <?php foreach ($homeBeforeBlogsBanners as $b): $src = '/assets/uploads/banners/' . ltrim($b['filename'], '/'); ?>
                <div class="banner-existing-item">
                    <div class="banner-existing-thumb banner-list-thumb"><img src="<?= htmlspecialchars($src) ?>" alt=""></div>
                    <div class="banner-existing-body">
                        <form action="delete_banner.php" method="post" onsubmit="return confirm('Delete?');" style="float:right;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" style="color:red;border:none;background:none;cursor:pointer;">Delete</button>
                        </form>
                    </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
    </div>

    <!-- 4) SIDEBAR AD -->
    <div id="home-sidebar" class="home-section-content">
        <div class="card" style="padding:18px;margin-top:24px;">
            <h2 style="font-size:16px;font-weight:700;margin-bottom:6px;">Sidebar Ad</h2>
            <p style="font-size:12px;color:#64748b;margin-bottom:12px;">Small vertical banner in sidebar.</p>
            <form action="save_banner.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="page_slot" value="home_sidebar">
                <input type="hidden" name="is_active" value="1">
                
                <div class="banner-upload-row">
                  <div class="banner-upload-left">
                    <label style="font-weight:700;display:block;margin-bottom:6px;">Banner image</label>
                    <label style="display:block;border:2px dashed #d8e1f0;padding:14px;border-radius:10px;cursor:pointer;background:#fbfdff;text-align:center;">
                      <div style="font-weight:700;">Click to choose file</div>
                      <input type="file" name="banners[]" accept="image/*" style="display:none;" data-preview-target="bannerPreviewSidebar">
                    </label>
                    <!-- Media Library Integration -->
                    <div style="margin-top:10px;">
                       <button type="button" id="addSidebarMediaBtn" style="padding:8px 12px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                           <span>📁</span> Select from Library
                       </button>
                       <div id="sidebar_media_container" style="margin-top:10px;"></div>
                    </div>
                  </div>
                  <div class="banner-upload-right">
                    <label style="font-weight:700;display:block;margin-bottom:6px;">Alt text</label>
                    <input name="alt_text" class="input-field" value="<?php echo htmlspecialchars($homeSideLatest['alt_text'] ?? '', ENT_QUOTES); ?>" placeholder="Alt text" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">
                    <label style="font-weight:700;display:block;margin-top:12px;margin-bottom:6px;">Link</label>
                    <input name="link" value="<?php echo htmlspecialchars($homeSideLatest['link'] ?? '', ENT_QUOTES); ?>" placeholder="Link" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">
                    <div style="margin-top:12px;">
                        <button type="submit" class="btn primary" style="margin-left:auto;padding:8px 16px;border-radius:8px;background:#2563eb;color:#fff;border:none;font-size:13px;cursor:pointer;">Save Sidebar Ad</button>
                    </div>
                  </div>
                </div>
            </form>

            <?php if (!empty($homeSideBanners)): ?>
                <div class="banner-existing-grid" style="margin-top:20px;">
                  <?php foreach ($homeSideBanners as $b): $src = '/assets/uploads/banners/' . ltrim($b['filename'], '/'); ?>
                    <div class="banner-existing-item">
                        <div class="banner-existing-thumb banner-list-thumb"><img src="<?= htmlspecialchars($src) ?>" alt=""></div>
                        <div class="banner-existing-body">
                            <form action="delete_banner.php" method="post" onsubmit="return confirm('Delete?');" style="float:right;">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button type="submit" style="color:red;border:none;background:none;cursor:pointer;">Delete</button>
                            </form>
                        </div>
                    </div>
                  <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 5) OFFER BANNER -->
    <div id="home-offer" class="home-section-content">
        <div class="card" style="padding:18px;margin-top:24px;">
            <h2 style="font-size:16px;font-weight:700;margin-bottom:6px;">Special Offer Banner</h2>
            <p style="font-size:12px;color:#64748b;margin-bottom:12px;">Promo banner for limited offers.</p>
            <form action="save_banner.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="page_slot" value="home_offer">
                <input type="hidden" name="is_active" value="1">
                
                <div class="banner-upload-row">
                  <div class="banner-upload-left">
                    <label style="font-weight:700;display:block;margin-bottom:6px;">Banner image</label>
                    <label style="display:block;border:2px dashed #d8e1f0;padding:14px;border-radius:10px;cursor:pointer;background:#fbfdff;text-align:center;">
                      <div style="font-weight:700;">Click to choose file</div>
                      <input type="file" name="banners[]" accept="image/*" style="display:none;" data-preview-target="bannerPreviewOffer">
                    </label>
                    <div style="margin-top:10px;">
                       <button type="button" id="addOfferMediaBtn" style="padding:8px 12px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                           <span>📁</span> Select from Library
                       </button>
                       <div id="offer_media_container" style="margin-top:10px;"></div>
                    </div>
                  </div>
                  <div class="banner-upload-right">
                    <label style="font-weight:700;display:block;margin-bottom:6px;">Alt text</label>
                    <input name="alt_text" class="input-field" value="<?php echo htmlspecialchars($homeOfferLatest['alt_text'] ?? '', ENT_QUOTES); ?>" placeholder="Alt text" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">
                    <label style="font-weight:700;display:block;margin-top:12px;margin-bottom:6px;">Link</label>
                    <input name="link" value="<?php echo htmlspecialchars($homeOfferLatest['link'] ?? '', ENT_QUOTES); ?>" placeholder="Link" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">
                    <div style="margin-top:12px;">
                        <button type="submit" class="btn primary" style="margin-left:auto;padding:8px 16px;border-radius:8px;background:#2563eb;color:#fff;border:none;font-size:13px;cursor:pointer;">Save Offer</button>
                    </div>
                  </div>
                </div>
            </form>

            <?php if (!empty($homeOfferBanners)): ?>
                <div class="banner-existing-grid" style="margin-top:20px;">
                  <?php foreach ($homeOfferBanners as $b): $src = '/assets/uploads/banners/' . ltrim($b['filename'], '/'); ?>
                    <div class="banner-existing-item">
                        <div class="banner-existing-thumb banner-list-thumb"><img src="<?= htmlspecialchars($src) ?>" alt=""></div>
                        <div class="banner-existing-body">
                            <form action="delete_banner.php" method="post" onsubmit="return confirm('Delete?');" style="float:right;">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button type="submit" style="color:red;border:none;background:none;cursor:pointer;">Delete</button>
                            </form>
                        </div>
                    </div>
                  <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
  <?php endif; ?>

  <?php if ($currentSlot === 'product'): ?>

  <!-- PRODUCT LISTING SIDEBAR BANNER: UPLOAD + PREVIEW -->
  <div class="card" style="padding:18px;margin-top:24px;">
    <h2 style="font-size:16px;font-weight:700;margin-bottom:6px;">Product Listing Sidebar Banner</h2>
    <p style="font-size:12px;color:#64748b;margin-bottom:12px;">
      This banner appears in the sidebar of the product listing page.
    </p>

    <form action="save_banner.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
      <input type="hidden" name="page_slot" value="product_sidebar">

      <div class="banner-upload-row">
        <div class="banner-upload-left">
          <label style="font-weight:700;display:block;margin-bottom:6px;">Sidebar banner image</label>
          <label style="display:block;border:2px dashed #d8e1f0;padding:14px;border-radius:10px;cursor:pointer;background:#fbfdff;text-align:center;">
            <div style="font-weight:700;">Click to choose file or drag &amp; drop</div>
            <input
              type="file"
              name="banners[]"
              accept="image/*"
              style="display:none;"
              data-preview-target="bannerPreviewProductSidebar">
          </label>
        </div>

        <div class="banner-upload-right">
          <label style="font-weight:700;display:block;margin-bottom:6px;">Alt text</label>
          <input
            name="alt_text"
            value="<?php echo htmlspecialchars($productSideLatest['alt_text'] ?? '', ENT_QUOTES); ?>"
            style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">

          <label style="font-weight:700;display:block;margin-top:10px;margin-bottom:6px;">Optional Link</label>
          <input
            name="link"
            value="<?php echo htmlspecialchars($productSideLatest['link'] ?? '', ENT_QUOTES); ?>"
            style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">

          <div style="margin-top:10px;display:flex;align-items:center;gap:8px;">
            <label style="font-size:13px;">
              <input
                type="checkbox"
                name="is_active"
                value="1"
                <?php if (empty($productSideLatest) || (int)($productSideLatest['is_active'] ?? 1) === 1) echo 'checked'; ?>>
              Active
            </label>

            <button
              type="submit"
              style="margin-left:auto;background:#2563eb;color:#fff;padding:8px 14px;border-radius:6px;border:none;font-size:13px;cursor:pointer;">
              Save Sidebar Banner
            </button>
          </div>
        </div>
      </div>

      <!-- Preview -->
      <div class="banner-preview-wrapper" style="margin-top:16px;">
        <div style="font-weight:700;margin-bottom:8px;">Latest sidebar banner preview</div>
        <?php if (!empty($productSideLatest['filename'])):
          $src = '/assets/uploads/banners/' . ltrim($productSideLatest['filename'], '/');
        ?>
          <img
            id="bannerPreviewProductSidebar"
            src="<?php echo htmlspecialchars($src, ENT_QUOTES); ?>"
            style="width:100%;max-height:260px;object-fit:cover;border-radius:10px;border:1px solid #eef2f7;">
        <?php else: ?>
          <div
            id="bannerPreviewProductSidebar"
            style="height:160px;background:#f3f6fb;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#64748b;">
            No sidebar banner yet
          </div>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- EXISTING PRODUCT SIDEBAR BANNERS LIST -->
  <div class="card" style="padding:18px;margin-top:20px;">
  <div style="font-weight:700;margin-bottom:10px;">
    Existing sidebar banners – Product Listing Page
  </div>

  <?php if (empty($productSideBanners)): ?>
    <div style="color:#64748b;font-size:14px;">No sidebar banners found for Product Listing page.</div>
  <?php else: ?>
    <div class="banner-existing-grid">
      <?php foreach ($productSideBanners as $b):
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
                onsubmit="return confirm('Delete this product sidebar banner permanently?');"
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

<?php endif; // end if ($currentSlot === 'product') ?>

<?php if ($currentSlot === 'product_detail'): ?>
  <!-- PRODUCT DETAIL SIDEBAR BANNER: UPLOAD + PREVIEW -->
  <div class="card" style="padding:18px;margin-top:24px;">
    <h2 style="font-size:16px;font-weight:700;margin-bottom:6px;">Product Detail Sidebar Banner</h2>
    <p style="font-size:12px;color:#64748b;margin-bottom:12px;">
      This banner appears in the right sidebar of individual product detail pages (product_view.php).
    </p>

    <form action="save_banner.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
      <input type="hidden" name="page_slot" value="product_detail_sidebar">

      <div class="banner-upload-row">
        <div class="banner-upload-left">
          <label style="font-weight:700;display:block;margin-bottom:6px;">Sidebar banner image</label>
          <label style="display:block;border:2px dashed #d8e1f0;padding:14px;border-radius:10px;cursor:pointer;background:#fbfdff;text-align:center;">
            <div style="font-weight:700;">Click to choose file or drag &amp; drop</div>
            <input
              type="file"
              name="banners[]"
              accept="image/*"
              style="display:none;"
              data-preview-target="bannerPreviewProductDetailSidebar">
          </label>
        </div>

        <div class="banner-upload-right">
          <label style="font-weight:700;display:block;margin-bottom:6px;">Alt text</label>
          <input
            name="alt_text"
            value="<?php echo htmlspecialchars($productDetailSideLatest['alt_text'] ?? '', ENT_QUOTES); ?>"
            style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">

          <label style="font-weight:700;display:block;margin-top:10px;margin-bottom:6px;">Optional Link</label>
          <input
            name="link"
            value="<?php echo htmlspecialchars($productDetailSideLatest['link'] ?? '', ENT_QUOTES); ?>"
            style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">

          <div style="margin-top:10px;display:flex;align-items:center;gap:8px;">
            <label style="font-size:13px;">
              <input
                type="checkbox"
                name="is_active"
                value="1"
                <?php if (empty($productDetailSideLatest) || (int)($productDetailSideLatest['is_active'] ?? 1) === 1) echo 'checked'; ?>>
              Active
            </label>

            <button
              type="submit"
              style="margin-left:auto;padding:8px 16px;border-radius:8px;background:#2563eb;color:#fff;border:none;font-size:13px;cursor:pointer;">
              Save sidebar banner
            </button>
          </div>
        </div>
      </div>

      <div class="banner-preview-wrapper">
        <div style="font-weight:700;margin-bottom:8px;">Sidebar banner preview</div>
        <?php if (!empty($productDetailSideLatest) && !empty($productDetailSideLatest['filename'])):
          $srcPD = '/assets/uploads/banners/' . ltrim($productDetailSideLatest['filename'], '/');
        ?>
          <img
            id="bannerPreviewProductDetailSidebar"
            src="<?php echo htmlspecialchars($srcPD, ENT_QUOTES, 'UTF-8'); ?>"
            alt="<?php echo htmlspecialchars($productDetailSideLatest['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            style="width:100%;max-height:260px;object-fit:cover;border-radius:10px;border:1px solid #eef2f7;">
        <?php else: ?>
          <div
            id="bannerPreviewProductDetailSidebar"
            style="width:100%;height:180px;border-radius:10px;background:#f3f6fb;display:flex;align-items:center;justify-content:center;color:#64748b;">
            No sidebar banner yet.
          </div>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Existing product detail sidebar banners -->
  <div class="card" style="padding:18px;margin-top:20px;">
    <div style="font-weight:700;margin-bottom:10px;">Existing sidebar banners – Product Detail Page</div>

    <?php if (empty($productDetailSideBanners)): ?>
      <div style="color:#64748b;font-size:14px;">No sidebar banners found for Product Detail Page.</div>
    <?php else: ?>
      <div class="banner-existing-grid">
        <?php foreach ($productDetailSideBanners as $b):
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
                  onsubmit="return confirm('Delete this product detail sidebar banner permanently?');"
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

<?php endif; // end if ($currentSlot === 'product_detail') ?>
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

<script src="/admin/banner-media-init-v2.js"></script>

<?php include __DIR__ . '/layout/footer.php'; ?>
<script>
// Tab Switching Logic for Home Page
function switchHomeTab(tabName) {
    // Hide all contents
    document.querySelectorAll('.home-section-content').forEach(el => el.classList.remove('active'));
    // Deactivate all buttons
    document.querySelectorAll('.sub-tab-btn').forEach(btn => btn.classList.remove('active'));
    
    // Show target content
    const target = document.getElementById('home-' + tabName);
    if(target) target.classList.add('active');
    
    // Activate button
    const buttons = document.querySelectorAll('.sub-tab-btn');
    buttons.forEach(btn => {
        if(btn.getAttribute('onclick').includes("'" + tabName + "'")) {
            btn.classList.add('active');
        }
    });

    // Save preference to sessionStorage so it persists on reload
    sessionStorage.setItem('activeHomeBannerTab', tabName);
}

// Restore active tab on load
document.addEventListener('DOMContentLoaded', () => {
    const savedTab = sessionStorage.getItem('activeHomeBannerTab');
    if(savedTab) {
        switchHomeTab(savedTab);
    }
    
    // Init media buttons for all sections
    if(typeof setupBannerMediaButton === 'function') {
        setupBannerMediaButton('addBannerMediaBtn', 'banner_media_container');
        setupBannerMediaButton('addCenterMediaBtn', 'center_media_container');
        setupBannerMediaButton('addBlogsMediaBtn', 'blogs_media_container');
        setupBannerMediaButton('addSidebarMediaBtn', 'sidebar_media_container');
        setupBannerMediaButton('addOfferMediaBtn', 'offer_media_container');
    }
});
</script>