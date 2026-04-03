<?php
// admin/users_export.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users_export_' . date('Y-m-d_H-i') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Output Header Row
fputcsv($output, [
    'User ID', 
    'Name', 
    'Email', 
    'Username', 
    'Phone', 
    'Status', 
    'Created At',
    'Last Login'
]);

// Read inputs (Filters) matches users.php
$q = trim($_GET['q'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

// Helper to check columns
function getTableColumns(PDO $pdo, string $table) : array {
    try {
        $cols = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) $cols[] = $r['Field'];
        return $cols;
    } catch (Exception $e) {
        return ['id','name','email','username'];
    }
}
$cols = getTableColumns($pdo, 'users');

// Helpers for mappings
function pick_col($candidates, $available) {
    foreach ($candidates as $c) {
        if (in_array($c, $available, true)) return $c;
    }
    return null;
}
$name_col       = pick_col(['name','full_name','display_name','username'], $cols) ?: 'name';
$email_col      = pick_col(['email','user_email','mail'], $cols) ?: 'email';
$username_col   = pick_col(['username','user_name','login'], $cols);
$status_col     = pick_col(['status','is_active','active','user_status'], $cols);
$phone_col      = pick_col(['phone','mobile','contact','phone_number'], $cols); 
$created_col    = pick_col(['created_at','created','joined_at','registered_at'], $cols) ?: 'created_at';
$last_login_col = pick_col(['last_login','last_seen','last_active','last_login_at'], $cols);

// Build WHERE
$whereParts = [];
$params = [];

if ($q !== '') {
    $searchCols = array_unique(array_filter([$name_col, $email_col, $username_col]));
    $subs = [];
    foreach ($searchCols as $c) {
        $subs[] = "$c LIKE :q";
    }
    if ($subs) {
        $whereParts[] = '(' . implode(' OR ', $subs) . ')';
        $params[':q'] = "%$q%";
    }
}

if ($filter_status !== '' && $status_col !== null) {
    $whereParts[] = "{$status_col} = :status";
    $params[':status'] = $filter_status;
}

$whereSql = $whereParts ? implode(' AND ', $whereParts) : '1=1';

// Fetch Users
try {
    $selectCols = [];
    $selectCols[] = 'id';
    $selectCols[] = "{$name_col} AS name";
    $selectCols[] = "{$email_col} AS email";
    $selectCols[] = $username_col ? "{$username_col} AS username" : "NULL AS username";
    $selectCols[] = $phone_col ? "{$phone_col} AS phone" : "NULL AS phone";
    $selectCols[] = $status_col ? "{$status_col} AS status" : "NULL AS status";
    $selectCols[] = "{$created_col} AS created_at";
    $selectCols[] = $last_login_col ? "{$last_login_col} AS last_login" : "NULL AS last_login";
    
    $sql = "SELECT " . implode(', ', $selectCols) . " FROM users WHERE {$whereSql} ORDER BY {$created_col} DESC";
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Map status to readable
        $sStr = strtolower((string)$row['status']);
        $statusText = $row['status'];
        if ($sStr === '1' || $sStr === 'active') $statusText = 'Active';
        if ($sStr === '0' || $sStr === 'inactive') $statusText = 'Inactive';
        
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['email'],
            $row['username'],
            $row['phone'],
            $statusText,
            $row['created_at'],
            $row['last_login']
        ]);
    }

} catch (Exception $e) {
    error_log("Export Error: " . $e->getMessage());
    fputcsv($output, ['Error exporting data']);
}

fclose($output);
exit;
