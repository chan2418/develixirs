<?php
// admin/banner.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/layout/header.php';

// get the current banner (latest active)
$stmt = $pdo->prepare("SELECT * FROM banners ORDER BY id DESC LIMIT 1");
$stmt->execute();
$banner = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

// ensure csrf
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = htmlspecialchars($_SESSION['csrf_token']);
?>
<link rel="stylesheet" href="/assets/css/admin.css">
<div class="page-wrap" style="max-width:1000px;margin:28px auto;">
  <h1 style="font-size:20px;font-weight:800;margin-bottom:6px;">Homepage Banner</h1>
  <p style="color:#64748b;margin-bottom:12px;">Upload a new banner image — recommended size: <strong>1600×600</strong> or similar wide ratio.</p>

  <?php if (!empty($_GET['ok'])): ?>
    <div style="padding:10px;border-radius:8px;background:#ecfdf5;color:#065f46;border:1px solid #bbf7d0;margin-bottom:12px;">Banner updated.</div>
  <?php endif; ?>

  <div class="card" style="padding:18px;">
    <form action="save_banner.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
      <div style="display:flex;gap:18px;flex-wrap:wrap;">
        <div style="flex:1;min-width:280px;">
          <label style="font-weight:700;display:block;margin-bottom:6px;">Banner image</label>
          <label style="display:block;border:2px dashed #d8e1f0;padding:14px;border-radius:10px;cursor:pointer;background:#fbfdff;text-align:center;">
            <div style="font-weight:700;">Click to choose file or drag & drop</div>
            <div style="font-size:13px;color:#555;margin-top:6px;">JPG, PNG, WEBP — max 5MB</div>
            <input type="file" name="banner" accept="image/*" style="display:none;">
          </label>
        </div>

        <div style="width:360px;">
          <label style="font-weight:700;display:block;margin-bottom:6px;">Alt text</label>
          <input name="alt_text" class="input-field" value="<?php echo htmlspecialchars($banner['alt_text'] ?? ''); ?>" placeholder="Alt text for accessibility" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">
          <label style="font-weight:700;display:block;margin-top:12px;margin-bottom:6px;">Optional Link (where banner points)</label>
          <input name="link" value="<?php echo htmlspecialchars($banner['link'] ?? ''); ?>" placeholder="https://example.com" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7;">
          <div style="display:flex;gap:8px;margin-top:12px;">
            <label style="display:flex;gap:8px;align-items:center;">
              <input type="checkbox" name="is_active" value="1" <?php if (empty($banner) || (int)($banner['is_active'] ?? 1)===1) echo 'checked'; ?>> Active
            </label>
            <button type="submit" class="btn primary" style="margin-left:auto;">Save banner</button>
          </div>
        </div>
      </div>

      <div style="margin-top:18px;">
        <div style="font-weight:700;margin-bottom:8px;">Preview</div>
        <?php if (!empty($banner) && !empty($banner['filename'])): 
            $src = htmlspecialchars('/assets/uploads/banners/' . ltrim($banner['filename'],'/'));
        ?>
          <img id="bannerPreview" src="<?php echo $src; ?>" alt="<?php echo htmlspecialchars($banner['alt_text'] ?? ''); ?>" style="width:100%;max-height:360px;object-fit:cover;border-radius:10px;border:1px solid #eef2f7;">
        <?php else: ?>
          <div id="bannerPreview" style="width:100%;height:220px;border-radius:10px;background:#f3f6fb;display:flex;align-items:center;justify-content:center;color:#64748b;">No banner yet</div>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<script>
// show client-side preview when user picks a new file
document.querySelector('input[type=file][name=banner]')?.addEventListener('change', function(e){
  const file = this.files && this.files[0];
  if (!file) return;
  const url = URL.createObjectURL(file);
  const img = document.getElementById('bannerPreview');
  if (img && img.tagName === 'IMG') img.src = url;
  else if (img) {
    img.innerHTML = '';
    const n = document.createElement('img');
    n.src = url; n.style.width='100%'; n.style.maxHeight='360px'; n.style.objectFit='cover'; n.style.borderRadius='10px';
    img.appendChild(n);
  }
});
</script>

<?php include __DIR__ . '/footer.php'; ?>