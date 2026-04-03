<?php
// admin/orders_export.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=orders_export_' . date('Y-m-d_H-i') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Output Header Row
fputcsv($output, [
    'Order ID', 
    'Order Number', 
    'Customer Name', 
    'Email', 
    'Phone', 
    'Total Amount', 
    'Payment Status', 
    'Order Status', 
    'Created At'
]);

// Read inputs (Filters) matches orders.php
$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');

// Helper to check columns
function getTableColumns(PDO $pdo, string $table) : array {
    try {
        $cols = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) $cols[] = $r['Field'];
        return $cols;
    } catch (Exception $e) {
        return ['id','order_number','customer_name','email','phone','total_amount','payment_status','order_status','created_at'];
    }
}

$ordersColumns = getTableColumns($pdo, 'orders');

// Build WHERE
$whereParts = [];
$params = [];

if ($q !== '') {
    $searchCols = array_intersect($ordersColumns, ['order_number','customer_name','email']);
    if (!empty($searchCols)) {
        $orParts = [];
        $i = 0;
        foreach ($searchCols as $col) {
            $i++;
            $param = ':q' . $i;
            $orParts[] = "$col LIKE $param";
            $params[$param] = "%{$q}%";
        }
        if ($orParts) {
            $whereParts[] = '(' . implode(' OR ', $orParts) . ')';
        }
    }
}

if ($status !== '') {
    if (in_array('order_status', $ordersColumns)) {
        $whereParts[] = "order_status = :status";
        $params[':status'] = $status;
    }
}

$whereSql = $whereParts ? implode(' AND ', $whereParts) : '1=1';

// Fetch Orders
try {
    // Select specific columns + phone if exists
    $colsToSelect = ['id','order_number','customer_name','email','total_amount','payment_status','order_status','created_at'];
    if (in_array('phone', $ordersColumns)) $colsToSelect[] = 'phone';
    
    $selectCols = implode(',', $colsToSelect);
    
    $sql = "SELECT {$selectCols} FROM orders WHERE {$whereSql} ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    
    // Bind Params
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['order_number'],
            $row['customer_name'],
            $row['email'],
            $row['phone'] ?? '',
            $row['total_amount'],
            $row['payment_status'],
            $row['order_status'],
            $row['created_at']
        ]);
    }

} catch (Exception $e) {
    error_log("Export Error: " . $e->getMessage());
    fputcsv($output, ['Error exporting data']);
}

fclose($output);
exit;
