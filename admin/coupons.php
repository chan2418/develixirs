<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// Fetch filters
$statusFilter = $_GET['status'] ?? 'all';
$offerTypeFilter = $_GET['offer_type'] ?? 'all';
$discountTypeFilter = $_GET['discount_type'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM coupons WHERE 1=1";
$params = [];

if ($searchQuery) {
    $sql .= " AND (code LIKE :search OR title LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

if ($statusFilter !== 'all') {
    if ($statusFilter === 'expired') {
        $sql .= " AND end_date < NOW()";
    } else {
        $sql .= " AND status = :status AND end_date >= NOW()";
        $params[':status'] = $statusFilter;
    }
}

if ($offerTypeFilter !== 'all') {
    $sql .= " AND offer_type = :offer_type";
    $params[':offer_type'] = $offerTypeFilter;
}

if ($discountTypeFilter !== 'all') {
    $sql .= " AND discount_type = :discount_type";
    $params[':discount_type'] = $discountTypeFilter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/layout/header.php';
?>

<style>
  .admin-content {
    max-width: 1400px;
    margin: 30px auto;
    padding: 0 20px;
  }
  
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
  }
  
  .page-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
  }
  
  .btn-primary {
    background: #0066cc;
    color: #fff;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
  }
  
  .btn-primary:hover {
    background: #0052a3;
    transform: translateY(-1px);
  }
  
  .filters-bar {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  }
  
  .filters-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 15px;
    align-items: end;
  }
  
  .filter-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #666;
    margin-bottom: 6px;
  }
  
  .filter-group input,
  .filter-group select {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
  }
  
  .filter-group input:focus,
  .filter-group select:focus {
    outline: none;
    border-color: #0066cc;
  }
  
  .coupons-table {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  }
  
  table {
    width: 100%;
    border-collapse: collapse;
  }
  
  thead {
    background: #f8f9fa;
  }
  
  th {
    padding: 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 700;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  td {
    padding: 16px;
    border-top: 1px solid #f0f0f0;
    font-size: 14px;
    color: #333;
  }
  
  tbody tr:hover {
    background: #fafafa;
  }
  
  .coupon-code {
    font-family: 'Courier New', monospace;
    background: #f0f7ff;
    color: #0066cc;
    padding: 4px 10px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 13px;
  }
  
  .badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .badge-active {
    background: #d4edda;
    color: #155724;
  }
  
  .badge-inactive {
    background: #f8d7da;
    color: #721c24;
  }
  
  .badge-expired {
    background: #e0e0e0;
    color: #666;
  }
  
  .offer-type {
    font-size: 12px;
    color: #666;
    text-transform: capitalize;
  }
  
  .discount-value {
    font-weight: 700;
    color: #28a745;
    font-size: 15px;
  }
  
  .validity {
    font-size: 12px;
    color: #666;
  }
  
  .actions {
    display: flex;
    gap: 8px;
  }
  
  .btn-edit,
  .btn-delete {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
  }
  
  .btn-edit {
    background: #fff3cd;
    color: #856404;
  }
  
  .btn-edit:hover {
    background: #ffc107;
    color: #fff;
  }
  
  .btn-delete {
    background: #f8d7da;
    color: #721c24;
  }
  
  .btn-delete:hover {
    background: #dc3545;
    color: #fff;
  }
  
  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
  }
  
  .empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    color: #ddd;
  }
  
  .empty-state h3 {
    font-size: 20px;
    margin-bottom: 10px;
    color: #666;
  }
  
  @media (max-width: 1024px) {
    .filters-grid {
      grid-template-columns: 1fr 1fr;
    }
  }
  
  @media (max-width: 768px) {
    .filters-grid {
      grid-template-columns: 1fr;
    }
    
    .page-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 15px;
    }
    
    table {
      font-size: 12px;
    }
    
    th, td {
      padding: 10px;
    }
  }
</style>

<div class="admin-content">
  <div class="page-header">
    <h1>Offers & Coupons</h1>
    <a href="coupons_add.php" class="btn-primary">
      <i class="fa fa-plus"></i>
      Create New Coupon
    </a>
  </div>
  
  <div class="filters-bar">
    <form method="GET" action="">
      <div class="filters-grid">
        <div class="filter-group">
          <label>Search</label>
          <input type="text" name="search" placeholder="Search by code or title..." value="<?php echo htmlspecialchars($searchQuery); ?>">
        </div>
        
        <div class="filter-group">
          <label>Status</label>
          <select name="status" onchange="this.form.submit()">
            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label>Offer Type</label>
          <select name="offer_type" onchange="this.form.submit()">
            <option value="all" <?php echo $offerTypeFilter === 'all' ? 'selected' : ''; ?>>All</option>
            <option value="first_user" <?php echo $offerTypeFilter === 'first_user' ? 'selected' : ''; ?>>First User</option>
            <option value="cart_value" <?php echo $offerTypeFilter === 'cart_value' ? 'selected' : ''; ?>>Cart Value</option>
            <option value="festival" <?php echo $offerTypeFilter === 'festival' ? 'selected' : ''; ?>>Festival</option>
            <option value="product_specific" <?php echo $offerTypeFilter === 'product_specific' ? 'selected' : ''; ?>>Product-Specific</option>
            <option value="category_specific" <?php echo $offerTypeFilter === 'category_specific' ? 'selected' : ''; ?>>Category-Specific</option>
            <option value="universal" <?php echo $offerTypeFilter === 'universal' ? 'selected' : ''; ?>>Universal</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label>Discount Type</label>
          <select name="discount_type" onchange="this.form.submit()">
            <option value="all" <?php echo $discountTypeFilter === 'all' ? 'selected' : ''; ?>>All</option>
            <option value="percentage" <?php echo $discountTypeFilter === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
            <option value="flat" <?php echo $discountTypeFilter === 'flat' ? 'selected' : ''; ?>>Flat Amount</option>
          </select>
        </div>
      </div>
    </form>
  </div>
  
  <div class="coupons-table">
    <?php if (empty($coupons)): ?>
      <div class="empty-state">
        <i class="fa fa-ticket"></i>
        <h3>No coupons found</h3>
        <p>Create your first coupon to get started</p>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Coupon Code</th>
            <th>Title</th>
            <th>Offer Type</th>
            <th>Discount</th>
            <th>Validity</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($coupons as $coupon): 
            $now = date('Y-m-d H:i:s');
            $isExpired = $now > $coupon['end_date'];
            $statusBadge = $isExpired ? 'expired' : $coupon['status'];
            
            // Format offer type
            $offerTypeLabel = str_replace('_', ' ', $coupon['offer_type']);
            
            // Format discount
            if ($coupon['discount_type'] === 'percentage') {
              $discountText = $coupon['discount_value'] . '%';
              if ($coupon['max_discount_limit']) {
                $discountText .= ' (Max ₹' . number_format($coupon['max_discount_limit'], 0) . ')';
              }
            } else {
              $discountText = '₹' . number_format($coupon['discount_value'], 0);
            }
            
            // Format dates
            $startDate = date('d M Y', strtotime($coupon['start_date']));
            $endDate = date('d M Y', strtotime($coupon['end_date']));
          ?>
            <tr>
              <td><span class="coupon-code"><?php echo htmlspecialchars($coupon['code']); ?></span></td>
              <td><?php echo htmlspecialchars($coupon['title']); ?></td>
              <td><span class="offer-type"><?php echo ucwords($offerTypeLabel); ?></span></td>
              <td><span class="discount-value"><?php echo $discountText; ?></span></td>
              <td class="validity"><?php echo $startDate; ?> - <?php echo $endDate; ?></td>
              <td>
                <span class="badge badge-<?php echo $statusBadge; ?>">
                  <?php echo $isExpired ? 'Expired' : ucfirst($coupon['status']); ?>
                </span>
              </td>
              <td>
                <div class="actions">
                  <a href="coupons_edit.php?id=<?php echo $coupon['id']; ?>" class="btn-edit">Edit</a>
                  <button class="btn-delete" onclick="deleteCoupon(<?php echo $coupon['id']; ?>, '<?php echo htmlspecialchars($coupon['code']); ?>')">Delete</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script>
function deleteCoupon(id, code) {
  if (!confirm(`Are you sure you want to delete coupon "${code}"? This action cannot be undone.`)) {
    return;
  }
  
  fetch('delete_coupon.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${id}`
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('Coupon deleted successfully');
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert('An error occurred');
  });
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
