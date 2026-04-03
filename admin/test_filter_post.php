<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

echo "<h2>POST Data Capture Test</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } pre { background: #f5f5f5; padding: 10px; }</style>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>✓ Form Submitted!</h3>";
    
    echo "<h4>All POST Data:</h4>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    echo "<h4>Looking for filter_options:</h4>";
    if (isset($_POST['filter_options'])) {
        echo "<pre>Standard filter_options: " . print_r($_POST['filter_options'], true) . "</pre>";
    } else {
        echo "<p style='color:red;'>✗ filter_options NOT found in POST</p>";
    }
    
    echo "<h4>Looking for filter_options_json:</h4>";
    if (isset($_POST['filter_options_json'])) {
        echo "<pre>JSON filter_options: " . htmlspecialchars($_POST['filter_options_json']) . "</pre>";
        $decoded = json_decode($_POST['filter_options_json'], true);
        echo "<pre>Decoded: " . print_r($decoded, true) . "</pre>";
    } else {
        echo "<p style='color:red;'>✗ filter_options_json NOT found in POST</p>";
    }
    
    echo "<hr><a href='test_filter_post.php'>← Try Again</a>";
    exit;
}

// Fetch a filter group for testing
$stmt = $pdo->query("SELECT * FROM filter_groups WHERE is_active = 1 LIMIT 1");
$testGroup = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$testGroup) {
    die("No active filter groups found. Please create one first.");
}

$stmtOpt = $pdo->prepare("SELECT * FROM filter_options WHERE group_id = ? LIMIT 3");
$stmtOpt->execute([$testGroup['id']]);
$testOptions = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);

?>
<h3>Test Filter Submission</h3>
<p>This form will show you exactly what data is being sent when you check filters.</p>

<form method="POST" action="test_filter_post.php">
    <h4>Filter Group: <?php echo htmlspecialchars($testGroup['name']); ?></h4>
    
    <?php foreach ($testOptions as $opt): ?>
        <label style="display: block; margin: 10px 0;">
            <input type="checkbox" 
                   name="filter_options[<?= $testGroup['id'] ?>][]" 
                   value="<?= $opt['id'] ?>">
            <?= htmlspecialchars($opt['label']) ?>
        </label>
    <?php endforeach; ?>
    
    <br><br>
    <button type="submit" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer;">
        Submit Test
    </button>
</form>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    console.log('Form submitting...');
    
    const checked = document.querySelectorAll('input[name^="filter_options"]:checked');
    console.log('Checked filters:', checked.length);
    
    checked.forEach(cb => {
        console.log('- Name:', cb.name, 'Value:', cb.value);
    });
});
</script>
