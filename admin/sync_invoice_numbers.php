<?php
$isCli = (PHP_SAPI === 'cli');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/invoice_number_helper.php';

if ($isCli) {
    try {
        $updated = sync_all_invoice_numbers($pdo);
        echo "Invoice number sync completed.\n";
        echo "Database Host: " . (defined('DB_HOST') ? DB_HOST : 'unknown') . "\n";
        echo "Database Name: " . (defined('DB_NAME') ? DB_NAME : 'unknown') . "\n";
        echo "Updated Records: " . $updated . "\n";
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, "Invoice sync failed: " . $e->getMessage() . "\n");
        exit(1);
    }
}

require_once __DIR__ . '/_auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function collect_invoice_sync_preview(PDO $pdo, int $limit = 25): array
{
    $stmt = $pdo->query("
        SELECT i.id, i.invoice_number, i.order_id, o.order_number, o.created_at AS order_created_at
        FROM invoices i
        JOIN orders o ON o.id = i.order_id
        ORDER BY o.created_at ASC, o.id ASC, i.id ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $preview = [];
    $mismatchCount = 0;
    $currentFinancialYear = null;
    $sequence = 0;

    foreach ($rows as $row) {
        $financialYearCode = invoice_financial_year_code($row['order_created_at'] ?? null);
        if ($financialYearCode !== $currentFinancialYear) {
            $currentFinancialYear = $financialYearCode;
            $sequence = 0;
        }

        $sequence++;
        $expected = build_invoice_number_from_parts($financialYearCode, $sequence);

        if (($row['invoice_number'] ?? '') !== $expected) {
            $mismatchCount++;
            if (count($preview) < $limit) {
                $preview[] = [
                    'invoice_id' => (int)$row['id'],
                    'order_id' => (int)$row['order_id'],
                    'order_number' => $row['order_number'] ?? '',
                    'order_created_at' => $row['order_created_at'] ?? '',
                    'current_invoice_number' => $row['invoice_number'] ?? '',
                    'expected_invoice_number' => $expected,
                ];
            }
        }
    }

    return [
        'total' => count($rows),
        'mismatches' => $mismatchCount,
        'preview' => $preview,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['flash_error'] = 'Invalid request token.';
        header('Location: sync_invoice_numbers.php');
        exit;
    }

    try {
        $updated = sync_all_invoice_numbers($pdo);
        $_SESSION['flash_success'] = "Invoice numbers synced successfully. Updated {$updated} record(s).";
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = 'Invoice sync failed: ' . $e->getMessage();
    }

    header('Location: sync_invoice_numbers.php');
    exit;
}

$summary = collect_invoice_sync_preview($pdo);
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$page_title = 'Sync Invoice Numbers';
$page_subtitle = 'Rebuild invoice numbers as DEV/WEB/FY/sequence';
include __DIR__ . '/layout/header.php';
?>

<div class="max-w-[1200px] mx-auto py-6 px-4 space-y-6">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Invoice Number Sync</h2>
                <p class="text-sm text-slate-500 mt-2">
                    This page uses the existing database connection from <code>includes/db.php</code> and updates invoice numbers to
                    <strong>DEV/WEB/2526/1</strong> style based on order financial year and order sequence.
                </p>
            </div>
            <form method="post" class="shrink-0">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                <button type="submit" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">
                    Run Sync Now
                </button>
            </form>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4">
            <?= h($flashSuccess) ?>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-xl p-4">
            <?= h($flashError) ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Database Host</div>
            <div class="mt-2 text-lg font-bold text-slate-900"><?= h(defined('DB_HOST') ? DB_HOST : 'unknown') ?></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Database Name</div>
            <div class="mt-2 text-lg font-bold text-slate-900"><?= h(defined('DB_NAME') ? DB_NAME : 'unknown') ?></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Invoices</div>
            <div class="mt-2 text-lg font-bold text-slate-900"><?= (int)$summary['total'] ?></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Needs Update</div>
            <div class="mt-2 text-lg font-bold <?= ($summary['mismatches'] > 0) ? 'text-amber-600' : 'text-emerald-600' ?>">
                <?= (int)$summary['mismatches'] ?>
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-semibold text-slate-900">Preview</h3>
            <p class="text-sm text-slate-500 mt-1">Showing the first <?= count($summary['preview']) ?> invoice number changes that will be applied.</p>
        </div>

        <?php if (empty($summary['preview'])): ?>
            <div class="px-6 py-10 text-center text-slate-500">
                All invoice numbers are already in the correct format.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold">Invoice ID</th>
                            <th class="px-6 py-3 text-left font-semibold">Order</th>
                            <th class="px-6 py-3 text-left font-semibold">Order Date</th>
                            <th class="px-6 py-3 text-left font-semibold">Current Number</th>
                            <th class="px-6 py-3 text-left font-semibold">New Number</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($summary['preview'] as $row): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-medium text-slate-900"><?= (int)$row['invoice_id'] ?></td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900"><?= h($row['order_number']) ?></div>
                                    <div class="text-xs text-slate-500">Order ID: <?= (int)$row['order_id'] ?></div>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    <?= !empty($row['order_created_at']) ? h(date('d M Y H:i', strtotime($row['order_created_at']))) : '-' ?>
                                </td>
                                <td class="px-6 py-4 text-slate-600"><?= h($row['current_invoice_number']) ?></td>
                                <td class="px-6 py-4 font-semibold text-indigo-700"><?= h($row['expected_invoice_number']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
