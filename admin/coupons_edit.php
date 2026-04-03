<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// Get coupon ID
if (!isset($_GET['id'])) {
    header('Location: coupons.php');
    exit;
}

$couponId = intval($_GET['id']);

// Fetch coupon data
$stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = :id");
$stmt->execute([':id' => $couponId]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    header('Location: coupons.php?error=Coupon not found');
    exit;
}

// Fetch associated products
$stmt = $pdo->prepare("SELECT product_id FROM coupon_products WHERE coupon_id = :id");
$stmt->execute([':id' => $couponId]);
$selectedProducts = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch associated categories
$stmt = $pdo->prepare("SELECT category_id FROM coupon_categories WHERE coupon_id = :id");
$stmt->execute([':id' => $couponId]);
$selectedCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch all products
$productsStmt = $pdo->query("SELECT id, name FROM products ORDER BY name");
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories
$categoriesStmt = $pdo->query("SELECT id, title FROM categories ORDER BY title");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/layout/header.php';
?>

<style>
  /* Reuse styles from coupons_add.php */
  .admin-content {
    max-width: 900px;
    margin: 30px auto;
    padding: 0 20px;
  }
  
  .page-header {
    margin-bottom: 30px;
  }
  
  .page-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
  }
  
  .page-header p {
    color: #666;
    font-size: 14px;
  }
  
  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #0066cc;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 20px;
  }
  
  .back-link:hover {
    text-decoration: underline;
  }
  
  .form-card {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  }
  
  .form-section {
    margin-bottom: 35px;
    padding-bottom: 35px;
    border-bottom: 1px solid #e0e0e0;
  }
  
  .form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
  }
  
  .section-title {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .section-title i {
    color: #0066cc;
  }
  
  .form-group {
    margin-bottom: 20px;
  }
  
  .form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
  }
  
  .form-group label .required {
    color: #dc3545;
  }
  
  .form-group label .optional {
    color: #999;
    font-weight: 400;
    font-size: 12px;
  }
  
  .form-group input[type="text"],
  .form-group input[type="number"],
  .form-group input[type="datetime-local"],
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: border-color 0.2s;
  }
  
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: #0066cc;
  }
  
  .form-group textarea {
    resize: vertical;
    min-height: 80px;
  }
  
  .radio-group {
    display: flex;
    gap: 20px;
  }
  
  .radio-option {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .radio-option input[type="radio"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
  }
  
  .radio-option label {
    margin: 0;
    cursor: pointer;
    font-weight: 500;
  }
  
  .toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
  }
  
  .toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }
  
  .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 26px;
  }
  
  .slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
  }
  
  input:checked + .slider {
    background-color: #28a745;
  }
  
  input:checked + .slider:before {
    transform: translateX(24px);
  }
  
  .toggle-group {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  
  .multi-select {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 10px;
    max-height: 200px;
    overflow-y: auto;
  }
  
  .multi-select-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px;
    margin-bottom: 4px;
  }
  
  .multi-select-item:hover {
    background: #f8f9fa;
    border-radius: 4px;
  }
  
  .multi-select-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
  }
  
  .multi-select-item label {
    margin: 0;
    cursor: pointer;
    flex: 1;
    font-weight: 400;
  }
  
  .conditional-field {
    display: none;
  }
  
  .conditional-field.show {
    display: block;
  }
  
  .form-actions {
    display: flex;
    gap: 15px;
    justify-content: space-between;
    margin-top: 30px;
  }
  
  .btn-submit {
    background: #28a745;
    color: #fff;
    padding: 14px 32px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-submit:hover {
    background: #218838;
    transform: translateY(-1px);
  }
  
  .btn-cancel {
    background: #6c757d;
    color: #fff;
    padding: 14px 32px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
  }
  
  .btn-cancel:hover {
    background: #5a6268;
  }
  
  .btn-delete-coupon {
    background: #dc3545;
    color: #fff;
    padding: 14px 32px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-delete-coupon:hover {
    background: #c82333;
  }
  
  .help-text {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
  }
  
  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }
  
  @media (max-width: 768px) {
    .grid-2 {
      grid-template-columns: 1fr;
    }
    
    .radio-group {
      flex-direction: column;
      gap: 10px;
    }
    
    .form-actions {
      flex-direction: column;
    }
    
    .btn-submit,
    .btn-cancel,
    .btn-delete-coupon {
      width: 100%;
    }
  }
</style>

<div class="admin-content">
  <a href="coupons.php" class="back-link">
    <i class="fa fa-arrow-left"></i>
    Back to Coupons
  </a>
  
  <div class="page-header">
    <h1>Edit Coupon</h1>
    <p>Update coupon details below</p>
  </div>
  
  <form id="couponForm" method="POST" action="update_coupon.php">
    <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
    
    <div class="form-card">
      
      <!-- Basic Information -->
      <div class="form-section">
        <h3 class="section-title">
          <i class="fa fa-info-circle"></i>
          Basic Information
        </h3>
        
        <div class="form-group">
          <label>Coupon Title <span class="required">*</span></label>
          <input type="text" name="title" required value="<?php echo htmlspecialchars($coupon['title']); ?>">
        </div>
        
        <div class="form-group">
          <label>Coupon Code <span class="required">*</span></label>
          <input type="text" name="code" required value="<?php echo htmlspecialchars($coupon['code']); ?>" style="text-transform: uppercase;" readonly>
          <div class="help-text">Coupon code cannot be changed after creation</div>
        </div>
        
        <div class="form-group">
          <label>Description <span class="optional">(optional)</span></label>
          <textarea name="description"><?php echo htmlspecialchars($coupon['description']); ?></textarea>
        </div>
      </div>
      
      <!-- Discount Configuration -->
      <div class="form-section">
        <h3 class="section-title">
          <i class="fa fa-percent"></i>
          Discount Configuration
        </h3>
        
        <div class="form-group">
          <label>Discount Type <span class="required">*</span></label>
          <div class="radio-group">
            <div class="radio-option">
              <input type="radio" name="discount_type" value="percentage" id="discountPercentage" <?php echo $coupon['discount_type'] === 'percentage' ? 'checked' : ''; ?> onchange="updateDiscountLabel()">
              <label for="discountPercentage">Percentage (%)</label>
            </div>
            <div class="radio-option">
              <input type="radio" name="discount_type" value="flat" id="discountFlat" <?php echo $coupon['discount_type'] === 'flat' ? 'checked' : ''; ?> onchange="updateDiscountLabel()">
              <label for="discountFlat">Flat Amount (₹)</label>
            </div>
          </div>
        </div>
        
        <div class="grid-2">
          <div class="form-group">
            <label id="discountValueLabel">Discount Value <span class="required">*</span></label>
            <input type="number" name="discount_value" step="0.01" min="0" required value="<?php echo $coupon['discount_value']; ?>">
          </div>
          
          <div class="form-group" id="maxDiscountGroup">
            <label>Maximum Discount Limit (₹) <span class="optional">(optional)</span></label>
            <input type="number" name="max_discount_limit" step="0.01" min="0" value="<?php echo $coupon['max_discount_limit'] ?? ''; ?>">
          </div>
        </div>
      </div>
      
      <!-- Conditions -->
      <div class="form-section">
        <h3 class="section-title">
          <i class="fa fa-sliders"></i>
          Conditions & Rules
        </h3>
        
        <div class="form-group">
          <label>Minimum Purchase Required (₹) <span class="optional">(optional)</span></label>
          <input type="number" name="min_purchase" step="0.01" min="0" value="<?php echo $coupon['min_purchase'] ?? ''; ?>">
        </div>
        
        <div class="form-group">
          <label>Offer Type <span class="required">*</span></label>
          <select name="offer_type" id="offerType" required onchange="updateConditionalFields()">
            <option value="universal" <?php echo $coupon['offer_type'] === 'universal' ? 'selected' : ''; ?>>Universal Offer</option>
            <option value="first_user" <?php echo $coupon['offer_type'] === 'first_user' ? 'selected' : ''; ?>>First User Offer</option>
            <option value="cart_value" <?php echo $coupon['offer_type'] === 'cart_value' ? 'selected' : ''; ?>>Cart Value Offer</option>
            <option value="festival" <?php echo $coupon['offer_type'] === 'festival' ? 'selected' : ''; ?>>Festival / Seasonal Offer</option>
            <option value="product_specific" <?php echo $coupon['offer_type'] === 'product_specific' ? 'selected' : ''; ?>>Product-Specific Offer</option>
            <option value="category_specific" <?php echo $coupon['offer_type'] === 'category_specific' ? 'selected' : ''; ?>>Category-Specific Offer</option>
          </select>
        </div>
        
        <!-- Product Selector -->
        <div class="form-group conditional-field" id="productSelector">
          <label>Select Products <span class="required">*</span></label>
          <div class="multi-select">
            <?php foreach ($products as $product): ?>
              <div class="multi-select-item">
                <input type="checkbox" name="products[]" value="<?php echo $product['id']; ?>" id="product_<?php echo $product['id']; ?>" <?php echo in_array($product['id'], $selectedProducts) ? 'checked' : ''; ?>>
                <label for="product_<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        
        <!-- Category Selector -->
        <div class="form-group conditional-field" id="categorySelector">
          <label>Select Categories <span class="required">*</span></label>
          <div class="multi-select">
            <?php foreach ($categories as $category): ?>
              <div class="multi-select-item">
                <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" id="category_<?php echo $category['id']; ?>" <?php echo in_array($category['id'], $selectedCategories) ? 'checked' : ''; ?>>
                <label for="category_<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['title']); ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
      <!-- Usage Rules -->
      <div class="form-section">
        <h3 class="section-title">
          <i class="fa fa-users"></i>
          Usage Rules
        </h3>
        
        <div class="form-group">
          <label>Usage Limit Per User <span class="required">*</span></label>
          <select name="usage_limit_per_user" required>
            <option value="once" <?php echo $coupon['usage_limit_per_user'] === 'once' ? 'selected' : ''; ?>>Once per user</option>
            <option value="unlimited" <?php echo $coupon['usage_limit_per_user'] === 'unlimited' ? 'selected' : ''; ?>>Unlimited</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Can Be Clubbed With Other Offers?</label>
          <div class="toggle-group">
            <label class="toggle-switch">
              <input type="checkbox" name="can_be_clubbed" value="1" <?php echo $coupon['can_be_clubbed'] ? 'checked' : ''; ?>>
              <span class="slider"></span>
            </label>
            <span>Allow combining with other coupons</span>
          </div>
        </div>
        
        <div class="form-group">
          <label>Show on Top Scrolling Marquee?</label>
          <div class="toggle-group">
            <label class="toggle-switch">
              <input type="checkbox" name="show_on_marquee" value="1" <?php echo !empty($coupon['show_on_marquee']) ? 'checked' : ''; ?>>
              <span class="slider"></span>
            </label>
            <span>Display this coupon in the website header marquee</span>
          </div>
        </div>
      </div>
      
      <!-- Validity -->
      <div class="form-section">
        <h3 class="section-title">
          <i class="fa fa-calendar"></i>
          Validity Period
        </h3>
        
        <div class="grid-2">
          <div class="form-group">
            <label>Start Date & Time <span class="required">*</span></label>
            <input type="datetime-local" name="start_date" required value="<?php echo date('Y-m-d\TH:i', strtotime($coupon['start_date'])); ?>">
          </div>
          
          <div class="form-group">
            <label>End Date & Time <span class="required">*</span></label>
            <input type="datetime-local" name="end_date" required value="<?php echo date('Y-m-d\TH:i', strtotime($coupon['end_date'])); ?>">
          </div>
        </div>
      </div>
      
      <!-- Status -->
      <div class="form-section">
        <h3 class="section-title">
          <i class="fa fa-toggle-on"></i>
          Status
        </h3>
        
        <div class="form-group">
          <div class="toggle-group">
            <label class="toggle-switch">
              <input type="checkbox" name="status" value="active" <?php echo $coupon['status'] === 'active' ? 'checked' : ''; ?>>
              <span class="slider"></span>
            </label>
            <span>Active (Coupon is live and can be used)</span>
          </div>
        </div>
      </div>
      
      <div class="form-actions">
        <button type="button" class="btn-delete-coupon" onclick="deleteCoupon()">Delete Coupon</button>
        <div style="display: flex; gap: 15px;">
          <a href="coupons.php" class="btn-cancel">Cancel</a>
          <button type="submit" class="btn-submit">Update Coupon</button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
// Update discount label based on type
function updateDiscountLabel() {
  const isPercentage = document.getElementById('discountPercentage').checked;
  const label = document.getElementById('discountValueLabel');
  const maxDiscountGroup = document.getElementById('maxDiscountGroup');
  
  if (isPercentage) {
    label.innerHTML = 'Discount Value (%) <span class="required">*</span>';
    maxDiscountGroup.style.display = 'block';
  } else {
    label.innerHTML = 'Discount Value (₹) <span class="required">*</span>';
    maxDiscountGroup.style.display = 'none';
  }
}

// Show/hide conditional fields based on offer type
function updateConditionalFields() {
  const offerType = document.getElementById('offerType').value;
  const productSelector = document.getElementById('productSelector');
  const categorySelector = document.getElementById('categorySelector');
  
  // Hide all conditional fields
  productSelector.classList.remove('show');
  categorySelector.classList.remove('show');
  
  // Show relevant field
  if (offerType === 'product_specific') {
    productSelector.classList.add('show');
  } else if (offerType === 'category_specific') {
    categorySelector.classList.add('show');
  }
}

// Delete coupon
function deleteCoupon() {
  if (!confirm('Are you sure you want to delete this coupon? This action cannot be undone.')) {
    return;
  }
  
  fetch('delete_coupon.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=<?php echo $coupon['id']; ?>'
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('Coupon deleted successfully');
      window.location.href = 'coupons.php';
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert('An error occurred');
  });
}

// Initialize
updateDiscountLabel();
updateConditionalFields();
</script>

<?php include __DIR__ . '/footer.php'; ?>
