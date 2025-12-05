<?php
// admin/settings.php
// Manage site settings (Company Name, Address, etc.)

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Site Settings';
include __DIR__ . '/layout/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'company_name' => $_POST['company_name'] ?? '',
        'company_address' => $_POST['company_address'] ?? '',
        'company_email' => $_POST['company_email'] ?? '',
        'company_phone' => $_POST['company_phone'] ?? '',
        'tax_rate' => $_POST['tax_rate'] ?? '18',
    ];

    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
        
        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        
        $_SESSION['flash_success'] = 'Settings updated successfully.';
        // Refresh to show new values
        echo "<script>window.location.href='settings.php';</script>";
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = 'Error updating settings: ' . $e->getMessage();
    }
}

// Fetch existing settings
$currentSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // ignore
}

// Defaults
$companyName = $currentSettings['company_name'] ?? 'DEVELIXIR';
$companyAddress = $currentSettings['company_address'] ?? "123 Herbal Street, Green City\nKerala, India - 670001";
$companyEmail = $currentSettings['company_email'] ?? 'support@develixir.com';
$companyPhone = $currentSettings['company_phone'] ?? '';
$taxRate = $currentSettings['tax_rate'] ?? '18';

?>

<div class="max-w-[800px] mx-auto mt-8 px-4 pb-20">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Site Settings</h1>
            <p class="text-slate-500 text-sm">Manage general site configuration and company details.</p>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="mb-4 p-4 bg-green-50 text-green-700 border border-green-200 rounded-lg">
            <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="mb-4 p-4 bg-red-50 text-red-700 border border-red-200 rounded-lg">
            <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 space-y-6">
            
            <!-- Company Details -->
            <div>
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Company Details (for Invoices)</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Company Name</label>
                        <input type="text" name="company_name" value="<?= htmlspecialchars($companyName) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Company Address</label>
                        <textarea name="company_address" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"><?= htmlspecialchars($companyAddress) ?></textarea>
                        <p class="text-xs text-slate-500 mt-1">This address will appear on invoices.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Contact Email</label>
                            <input type="email" name="company_email" value="<?= htmlspecialchars($companyEmail) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Contact Phone</label>
                            <input type="text" name="company_phone" value="<?= htmlspecialchars($companyPhone) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-gray-100">

            <!-- Financial Settings -->
            <div>
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Financial Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Default Tax Rate (%)</label>
                        <input type="number" step="0.01" name="tax_rate" value="<?= htmlspecialchars($taxRate) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
            </div>

        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                Save Settings
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
