<?php
// admin/test_label_debug.php
// Check what's actually happening

session_start();

echo "<h2>Debug Information</h2>";
echo "<strong>Session Status:</strong> " . (isset($_SESSION['admin_logged']) ? "Logged in" : "Not logged in") . "<br>";

try {
    require_once __DIR__ . '/../includes/db.php';
    echo "<strong>Database:</strong> Connected<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM shipments");
    $count = $stmt->fetchColumn();
    echo "<strong>Shipments count:</strong> $count<br>";
    
} catch (Exception $e) {
    echo "<strong>Database Error:</strong> " . $e->getMessage() . "<br>";
}

echo "<br><strong>Testing Dompdf:</strong><br>";
require_once __DIR__ . '/../vendor/autoload.php';

if (class_exists('Dompdf\\Dompdf')) {
    echo "Dompdf class exists<br>";
    try {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml('<h1>Test</h1>');
        $dompdf->render();
        $output = $dompdf->output();
        echo "PDF generated successfully, size: " . strlen($output) . " bytes<br>";
    } catch (Exception $e) {
        echo "Dompdf error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Dompdf class NOT found<br>";
}
?>
