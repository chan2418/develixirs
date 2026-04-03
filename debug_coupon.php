<?php
require_once 'includes/db.php';

try {
    // Check if show_on_marquee column exists
    $cols = $pdo->query("SHOW COLUMNS FROM coupons")->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($cols, 'Field');
    echo "<b>DB Columns:</b> " . implode(', ', $colNames) . "<br><br>";

    if (in_array('show_on_marquee', $colNames)) {
        echo "<b style='color:green'>✓ show_on_marquee column EXISTS</b><br><br>";
    } else {
        echo "<b style='color:red'>✗ show_on_marquee column MISSING!</b><br><br>";
    }

    // List all coupons
    $stmt = $pdo->query("SELECT id, title, code, status, show_on_marquee, start_date, end_date FROM coupons");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<b>All Coupons:</b><br>";
    echo "<table border=1 cellpadding=5><tr><th>ID</th><th>Title</th><th>Code</th><th>Status</th><th>show_on_marquee</th><th>start_date</th><th>end_date</th></tr>";
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $k => $v) echo "<td>" . htmlspecialchars($v) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";

    // Check the marquee query directly
    $stmtC = $pdo->prepare("SELECT title, code, discount_type, discount_value FROM coupons WHERE status = 'active' AND show_on_marquee = 1 AND DATE(start_date) <= CURDATE() AND (end_date IS NULL OR DATE(end_date) >= CURDATE()) ORDER BY id DESC");
    $stmtC->execute();
    $marquee = $stmtC->fetchAll(PDO::FETCH_ASSOC);

    echo "<b>Marquee result count: " . count($marquee) . "</b><br>";
    if (!empty($marquee)) {
        echo "<pre>" . print_r($marquee, true) . "</pre>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
