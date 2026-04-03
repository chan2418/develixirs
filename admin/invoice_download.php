<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/invoice_number_helper.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { die("Invalid invoice"); }

try {
    sync_invoice_number($pdo, $id);
} catch (Exception $e) {
    error_log('Invoice number sync error: ' . $e->getMessage());
}

// fetch invoice + order + customer
$stmt = $pdo->prepare("SELECT 
      i.*, 
      o.order_number, o.customer_name, o.customer_email, 
      o.customer_phone, o.customer_address,
      o.total_amount, o.payment_status, o.payment_method,
      o.created_at AS order_date
    FROM invoices i
    JOIN orders o ON i.order_id = o.id
    WHERE i.id = ? LIMIT 1");
$stmt->execute([$id]);
$inv = $stmt->fetch();

if (!$inv) { die("Invoice not found"); }

?>
<!doctype html>
<html>
<head>
<title>Invoice <?= htmlspecialchars($inv['invoice_number']) ?></title>
<style>
body { font-family: Arial; background:#f8f8f8; padding:20px; }
.page { max-width:900px; margin:auto; background:white; padding:30px; border-radius:12px; }
h1,h2,h3 { margin:0; }

.table { width:100%; border-collapse: collapse; margin-top:20px; }
.table th, .table td { padding:10px; border-bottom:1px solid #eee; }

.header-wrap { display:flex; justify-content:space-between; }
.invoice-title { font-size:26px; font-weight:bold; }
.text-muted { color:#888; font-size:14px; }

.btn-download {
  background:#0b76ff; color:white; padding:10px 14px;
  border-radius:8px; text-decoration:none; font-weight:bold;
}
</style>
</head>
<body>

<div class="page">
  <div class="header-wrap">
    <div>
      <h2 class="invoice-title">Invoice <?= htmlspecialchars($inv['invoice_number']) ?></h2>
      <div class="text-muted">Date: <?= date("d M Y", strtotime($inv['created_at'])) ?></div>
      <div class="text-muted">Order ID: #<?= htmlspecialchars($inv['order_id']) ?></div>
    </div>

    <div>
      <a onclick="window.print()" class="btn-download">Download PDF</a>
    </div>
  </div>

  <hr style="margin:20px 0">

  <h3>DevElixir Natural Cosmetics ™</h3>
  <div class="text-muted" style="margin-top:5px;">
    No:6, 3rd Cross Street, Kamatchiamman Garden,<br>
    Sethukkarai, Gudiyatham-632602, Vellore, Tamil Nadu, INDIA<br>
    Phone: 9500650454<br>
    Email: sales@develixirs.com<br>
    GSTIN : 33FCOPR7048E1Z8
  </div>

  <hr style="margin:20px 0">

  <h3>Billing To:</h3>
  <strong><?= htmlspecialchars($inv['customer_name']) ?></strong><br>
  <?= nl2br(htmlspecialchars($inv['customer_address'] ?? '')) ?><br>
  Phone: <?= htmlspecialchars($inv['customer_phone']) ?><br>
  Email: <?= htmlspecialchars($inv['customer_email']) ?>

  <hr style="margin:20px 0">

  <table class="table">
    <thead>
      <tr>
        <th>ITEM</th>
        <th>QTY</th>
        <th>PRICE</th>
        <th>TOTAL</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?= htmlspecialchars($inv['order_number']) ?> — Product Payment</td>
        <td>1</td>
        <td>₹<?= number_format($inv['amount'],2) ?></td>
        <td>₹<?= number_format($inv['amount'],2) ?></td>
      </tr>
    </tbody>
  </table>

  <hr style="margin:20px 0">

  <table style="width:100%;">
    <tr>
      <td style="text-align:right; font-size:18px; font-weight:bold;">
        Total Amount: ₹<?= number_format($inv['amount'],2) ?>
      </td>
    </tr>
  </table>

  <hr style="margin:20px 0">

  <h3>Payment Info</h3>
  <div class="text-muted">
    Method: <?= htmlspecialchars(strtoupper($inv['payment_method'])) ?><br>
    Status: <?= htmlspecialchars(ucfirst($inv['payment_status'])) ?>
  </div>

  <h2 style="text-align:center; margin-top:40px; color:green;">
    <?= strtoupper($inv['status']) ?>
  </h2>

</div>

</body>
</html>
