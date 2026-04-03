<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/subscription_reporting_helper.php';
require_once __DIR__ . '/../includes/subscription_reminder_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['subscription_admin_csrf'])) {
    $_SESSION['subscription_admin_csrf'] = bin2hex(random_bytes(32));
}

$flash = null;
$flashClass = 'emerald';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['subscription_admin_csrf'], $csrf)) {
        $flash = 'Invalid request token.';
        $flashClass = 'rose';
    } else {
        try {
            $action = trim((string)($_POST['action'] ?? ''));
            if ($action === 'sync_now') {
                $result = subscription_sync_statuses($pdo);
                $flash = 'Status sync completed. Expired: ' . (int)$result['expired_rows'] . ' | Users synced: ' . (int)$result['users_synced'];
            } elseif ($action === 'run_reminders') {
                $result = subscription_process_expiry_reminders($pdo, true);
                $flash = 'Reminder sweep completed. Processed: ' . (int)$result['processed'] . ' | Emails sent: ' . (int)$result['emails_sent'] . ' | Existing skipped: ' . (int)$result['skipped_existing'];
                $flashClass = 'amber';
            } elseif ($action === 'manual_activate') {
                $lookup = trim((string)($_POST['customer_lookup'] ?? ''));
                $selectedUserId = (int)($_POST['customer_user_id'] ?? 0);
                $planId = (int)($_POST['plan_id'] ?? 0);
                $activationMode = trim((string)($_POST['activation_mode'] ?? 'queue'));
                $paymentMethod = trim((string)($_POST['payment_method'] ?? 'admin_manual'));
                $amount = trim((string)($_POST['amount'] ?? '0'));

                if ($lookup === '') {
                    throw new RuntimeException('Enter customer email, phone, or user ID.');
                }
                if ($planId <= 0) {
                    throw new RuntimeException('Select a subscription plan.');
                }

                $user = null;
                if ($selectedUserId > 0) {
                    $stmtUser = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE id = ? LIMIT 1");
                    $stmtUser->execute([$selectedUserId]);
                    $user = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: null;
                } elseif (ctype_digit($lookup)) {
                    $stmtUser = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE id = ? OR phone = ? LIMIT 1");
                    $stmtUser->execute([(int)$lookup, $lookup]);
                    $user = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: null;
                } else {
                    $stmtUser = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE email = ? LIMIT 1");
                    $stmtUser->execute([$lookup]);
                    $user = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: null;
                }

                if (!$user) {
                    throw new RuntimeException('Customer not found. Use exact user ID, phone, or email.');
                }

                $result = subscription_activate_manual_subscription($pdo, (int)$user['id'], $planId, [
                    'activation_mode' => $activationMode,
                    'payment_method' => $paymentMethod,
                    'amount' => (float)$amount,
                ]);

                $planName = $result['current_subscription']['display_plan_name']
                    ?? $result['upcoming_subscription']['display_plan_name']
                    ?? 'Subscription';
                $flash = 'Manual activation created for ' . ($user['name'] ?: ('User #' . (int)$user['id']))
                    . ' | Plan: ' . $planName
                    . ' | Start: ' . $result['effective_start_date']
                    . ' | End: ' . $result['effective_end_date'];
            }
        } catch (Throwable $e) {
            $flash = $e->getMessage();
            $flashClass = 'rose';
        }
    }
}

subscription_sync_statuses($pdo);

$filter = subscription_reporting_normalize_filter($_GET['filter'] ?? 'current');
$search = trim((string)($_GET['q'] ?? ''));
$adminPlans = subscription_fetch_admin_plans($pdo);

if (($_GET['export'] ?? '') === 'csv') {
    $exportRows = subscription_fetch_records($pdo, $filter, 5000, $search);
    $filename = 'subscription-subscribers-' . $filter . '-' . date('Ymd-His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Customer Name', 'Email', 'Phone', 'Plan', 'Cycle', 'Status', 'Start Date', 'End Date', 'Discount %', 'Amount', 'Payment Status', 'Payment Method', 'Payment Date']);

    foreach ($exportRows as $row) {
        $statusLabel = ucfirst((string)$row['status']);
        if ($row['status'] === 'active' && $row['start_date'] > date('Y-m-d')) {
            $statusLabel = 'Scheduled';
        } elseif ($row['status'] === 'active' && $row['end_date'] >= date('Y-m-d')) {
            $statusLabel = 'Active';
        }

        $amount = $row['transaction_amount'];
        if ($amount === null && isset($row['price_paid']) && $row['price_paid'] !== null) {
            $amount = (float)$row['price_paid'];
        }

        fputcsv($output, [
            $row['user_name'] ?: 'User #' . $row['user_id'],
            $row['user_email'] ?? '',
            $row['user_phone'] ?? '',
            $row['display_plan_name'],
            $row['billing_cycle_label'],
            $statusLabel,
            $row['start_date'],
            $row['end_date'],
            $row['effective_discount_percentage'],
            $amount !== null ? number_format((float)$amount, 2, '.', '') : '',
            ucfirst((string)($row['transaction_payment_status'] ?? 'pending')),
            (string)($row['transaction_payment_method'] ?? ''),
            $row['transaction_payment_date'] ?? '',
        ]);
    }

    fclose($output);
    exit;
}

$counts = subscription_fetch_record_counts($pdo);
$rows = subscription_fetch_records($pdo, $filter, 250, $search);

$page_title = 'Subscribers';
$page_subtitle = 'Current, upcoming, and expired subscriptions';
include __DIR__ . '/layout/header.php';
?>

<div class="max-w-[1450px] mx-auto py-6 px-4 space-y-6">
    <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold uppercase tracking-wide">
                    Phase 4
                    <span class="text-slate-400">Subscribers + export</span>
                </div>
                <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-900">Subscribers</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Search current members, queued renewals, and expired subscriptions. Export the current view to CSV or run the reminder sweep manually before the daily cron runs.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="/admin/subscription_reports.php" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:border-slate-400 hover:text-slate-900 transition">
                    Open Reports
                </a>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['subscription_admin_csrf']); ?>">
                    <input type="hidden" name="action" value="run_reminders">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-3 text-sm font-semibold text-amber-700 hover:bg-amber-100 transition">
                        Run Reminders
                    </button>
                </form>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['subscription_admin_csrf']); ?>">
                    <input type="hidden" name="action" value="sync_now">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">
                        Run Status Sync
                    </button>
                </form>
            </div>
        </div>
        <?php if ($flash): ?>
            <div class="mt-4 rounded-2xl border px-4 py-3 text-sm <?php
                if ($flashClass === 'amber') {
                    echo 'border-amber-200 bg-amber-50 text-amber-700';
                } elseif ($flashClass === 'rose') {
                    echo 'border-rose-200 bg-rose-50 text-rose-700';
                } else {
                    echo 'border-emerald-200 bg-emerald-50 text-emerald-700';
                }
            ?>">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-black text-slate-900">Manual Activation</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">
                    Use this for testing, complimentary access, offline/manual approvals, or internal subscription activation. Recommended mode is <strong>Queue After Current</strong> so you do not overwrite an active customer plan by mistake.
                </p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500">
                <div><strong>Queue After Current</strong>: starts after the latest active period.</div>
                <div><strong>Replace Current</strong>: cancels active/queued rows and starts today.</div>
            </div>
        </div>

        <?php if (empty($adminPlans)): ?>
            <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                No subscription plans found. Create or activate a plan first in Subscription Settings.
            </div>
        <?php else: ?>
            <form method="post" class="mt-6 grid gap-4 xl:grid-cols-12">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['subscription_admin_csrf']); ?>">
                <input type="hidden" name="action" value="manual_activate">

                <div class="xl:col-span-3">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Customer</label>
                    <div class="relative">
                        <input
                            type="hidden"
                            name="customer_user_id"
                            id="customerUserId"
                            value=""
                        >
                        <input
                            type="text"
                            name="customer_lookup"
                            id="customerLookup"
                            autocomplete="off"
                            data-search-endpoint="/admin/subscription_user_lookup.php"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-slate-500 focus:outline-none"
                            placeholder="Type name, email, phone, or user ID"
                            required
                        >
                        <div
                            id="customerLookupSuggestions"
                            class="absolute left-0 right-0 top-full z-20 mt-2 hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                        ></div>
                    </div>
                    <div id="customerLookupMeta" class="mt-2 text-xs leading-5 text-slate-500">
                        Start typing to search matching users. You can still paste the exact email, phone, or user ID manually.
                    </div>
                </div>

                <div class="xl:col-span-3">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Plan</label>
                    <select name="plan_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-slate-500 focus:outline-none" required>
                        <option value="">Select plan</option>
                        <?php foreach ($adminPlans as $plan): ?>
                            <option value="<?php echo (int)$plan['id']; ?>">
                                <?php echo htmlspecialchars($plan['name']); ?>
                                <?php echo !empty($plan['is_active']) ? '' : ' (Inactive)'; ?>
                                - ₹<?php echo number_format((float)$plan['price'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Activation Mode</label>
                    <select name="activation_mode" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-slate-500 focus:outline-none">
                        <option value="queue">Queue After Current</option>
                        <option value="replace">Replace Current</option>
                    </select>
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Internal Method</label>
                    <select name="payment_method" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-slate-500 focus:outline-none">
                        <option value="admin_manual">Admin Manual</option>
                        <option value="internal_test">Internal Test</option>
                        <option value="complimentary">Complimentary</option>
                        <option value="offline_payment">Offline Payment</option>
                    </select>
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Amount</label>
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        name="amount"
                        value="0.00"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-slate-500 focus:outline-none"
                    >
                </div>

                <div class="xl:col-span-12 flex flex-wrap items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">
                        Activate Subscription
                    </button>
                    <div class="text-xs leading-5 text-slate-500">
                        For testing, keep amount as `0.00`. For offline/manual paid activation, enter the collected amount.
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <?php foreach (subscription_reporting_filters() as $key => $label): ?>
            <a href="?filter=<?php echo urlencode($key); ?>&q=<?php echo urlencode($search); ?>" class="rounded-3xl border p-5 <?php echo $filter === $key ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-900'; ?>">
                <div class="text-xs uppercase tracking-wide opacity-70"><?php echo htmlspecialchars($label); ?></div>
                <div class="mt-3 text-3xl font-black"><?php echo (int)($counts[$key] ?? 0); ?></div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Subscription Records</h2>
                <p class="text-sm text-slate-500">Filter: <?php echo htmlspecialchars(subscription_reporting_filters()[$filter]); ?></p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form method="get" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email, phone, plan" class="w-full sm:w-72 rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-slate-500 focus:outline-none">
                    <button type="submit" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:border-slate-400 hover:text-slate-900 transition">Search</button>
                </form>
                <a href="?filter=<?php echo urlencode($filter); ?>&q=<?php echo urlencode($search); ?>&export=csv" class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition text-center">
                    Export CSV
                </a>
            </div>
        </div>
        <div class="px-6 py-3 text-sm text-slate-500 border-b border-slate-100">
            Showing <?php echo count($rows); ?> record(s)
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wide">
                    <tr>
                        <th class="px-6 py-4 text-left">Customer</th>
                        <th class="px-6 py-4 text-left">Plan</th>
                        <th class="px-6 py-4 text-left">Status</th>
                        <th class="px-6 py-4 text-left">Period</th>
                        <th class="px-6 py-4 text-left">Discount</th>
                        <th class="px-6 py-4 text-left">Amount</th>
                        <th class="px-6 py-4 text-left">Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">No subscription records found for this filter.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $badgeClass = 'bg-slate-100 text-slate-700';
                                $statusLabel = ucfirst((string)$row['status']);
                                if ($row['status'] === 'active' && $row['start_date'] <= date('Y-m-d') && $row['end_date'] >= date('Y-m-d')) {
                                    $badgeClass = 'bg-emerald-50 text-emerald-700';
                                    $statusLabel = 'Active';
                                } elseif ($row['status'] === 'active' && $row['start_date'] > date('Y-m-d')) {
                                    $badgeClass = 'bg-amber-50 text-amber-700';
                                    $statusLabel = 'Scheduled';
                                } elseif ($row['status'] === 'expired') {
                                    $badgeClass = 'bg-rose-50 text-rose-700';
                                    $statusLabel = 'Expired';
                                }

                                $amount = $row['transaction_amount'];
                                if ($amount === null && isset($row['price_paid']) && $row['price_paid'] !== null) {
                                    $amount = (float)$row['price_paid'];
                                }
                            ?>
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-4 align-top">
                                    <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($row['user_name'] ?: 'User #' . $row['user_id']); ?></div>
                                    <div class="text-slate-500"><?php echo htmlspecialchars($row['user_email'] ?? ''); ?></div>
                                    <div class="text-slate-400 text-xs mt-1"><?php echo htmlspecialchars($row['user_phone'] ?? ''); ?></div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($row['display_plan_name']); ?></div>
                                    <div class="text-slate-500"><?php echo htmlspecialchars($row['billing_cycle_label']); ?></div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($statusLabel); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top text-slate-600">
                                    <div><?php echo date('d M Y', strtotime($row['start_date'])); ?></div>
                                    <div class="text-slate-400">to <?php echo date('d M Y', strtotime($row['end_date'])); ?></div>
                                </td>
                                <td class="px-6 py-4 align-top text-slate-600"><?php echo number_format((float)$row['effective_discount_percentage'], 0); ?>%</td>
                                <td class="px-6 py-4 align-top text-slate-900 font-semibold">
                                    <?php echo $amount !== null ? '₹' . number_format((float)$amount, 2) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 align-top text-slate-600">
                                    <div><?php echo htmlspecialchars(ucfirst((string)($row['transaction_payment_status'] ?? 'pending'))); ?></div>
                                    <div class="text-slate-400 text-xs mt-1"><?php echo htmlspecialchars((string)($row['transaction_payment_method'] ?? 'pending')); ?></div>
                                    <div class="text-slate-400 text-xs mt-1"><?php echo !empty($row['transaction_payment_date']) ? date('d M Y, h:i A', strtotime($row['transaction_payment_date'])) : '-'; ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const lookupInput = document.getElementById('customerLookup');
    const lookupIdInput = document.getElementById('customerUserId');
    const suggestionsBox = document.getElementById('customerLookupSuggestions');
    const metaBox = document.getElementById('customerLookupMeta');

    if (!lookupInput || !lookupIdInput || !suggestionsBox || !metaBox) {
        return;
    }

    const endpoint = lookupInput.getAttribute('data-search-endpoint');
    let debounceTimer = null;

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[char];
        });
    }

    function closeSuggestions() {
        suggestionsBox.innerHTML = '';
        suggestionsBox.classList.add('hidden');
    }

    function setMeta(text, tone) {
        metaBox.className = 'mt-2 text-xs leading-5';
        if (tone === 'ok') {
            metaBox.classList.add('text-emerald-600');
        } else if (tone === 'warn') {
            metaBox.classList.add('text-amber-600');
        } else {
            metaBox.classList.add('text-slate-500');
        }
        metaBox.textContent = text;
    }

    function renderItems(items) {
        if (!items.length) {
            suggestionsBox.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No matching users found.</div>';
            suggestionsBox.classList.remove('hidden');
            return;
        }

        suggestionsBox.innerHTML = items.map(function (item) {
            const subscriptionText = item.is_subscriber
                ? 'Subscriber' + (item.subscription_expires_at ? ' until ' + item.subscription_expires_at : '')
                : 'No active subscription';
            const name = escapeHtml(item.name || ('User #' + item.id));
            const email = escapeHtml(item.email || 'No email');
            const phone = escapeHtml(item.phone || 'No phone');
            const badgeClass = item.is_subscriber
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-slate-100 text-slate-600';

            return '' +
                '<button type="button" class="flex w-full items-start justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-slate-50" ' +
                    'data-user-id="' + item.id + '" ' +
                    'data-user-name="' + name + '" ' +
                    'data-user-email="' + email + '" ' +
                    'data-user-phone="' + phone + '" ' +
                    'data-user-subscriber="' + (item.is_subscriber ? '1' : '0') + '" ' +
                    'data-user-expiry="' + escapeHtml(item.subscription_expires_at || '') + '">' +
                    '<div>' +
                        '<div class="font-semibold text-slate-900">' + name + ' <span class="text-slate-400">#' + item.id + '</span></div>' +
                        '<div class="mt-1 text-xs text-slate-500">' + email + '</div>' +
                        '<div class="text-xs text-slate-400">' + phone + '</div>' +
                    '</div>' +
                    '<span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold ' + badgeClass + '">' + escapeHtml(subscriptionText) + '</span>' +
                '</button>';
        }).join('');
        suggestionsBox.classList.remove('hidden');
    }

    function selectUser(button) {
        const userId = button.getAttribute('data-user-id') || '';
        const userName = button.getAttribute('data-user-name') || '';
        const userEmail = button.getAttribute('data-user-email') || '';
        const userPhone = button.getAttribute('data-user-phone') || '';
        const isSubscriber = button.getAttribute('data-user-subscriber') === '1';
        const expiry = button.getAttribute('data-user-expiry') || '';

        lookupIdInput.value = userId;
        lookupInput.value = userName + ' | ' + userEmail + ' | ' + userPhone + ' | #' + userId;

        let meta = 'Selected user #' + userId + '. ';
        if (isSubscriber) {
            meta += expiry ? 'Current subscription expires on ' + expiry + '.' : 'Customer currently has an active subscription.';
            setMeta(meta, 'warn');
        } else {
            meta += 'No active subscription found.';
            setMeta(meta, 'ok');
        }
        closeSuggestions();
    }

    function performLookup() {
        const query = lookupInput.value.trim();
        lookupIdInput.value = '';

        if (query === '') {
            setMeta('Start typing to search matching users. You can still paste the exact email, phone, or user ID manually.', 'default');
            closeSuggestions();
            return;
        }

        if (!/^\\d+$/.test(query) && query.length < 2) {
            closeSuggestions();
            return;
        }

        fetch(endpoint + '?q=' + encodeURIComponent(query), {
            credentials: 'same-origin'
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (!payload || payload.success !== true) {
                    setMeta('User lookup failed. You can still enter the exact email, phone, or user ID manually.', 'warn');
                    closeSuggestions();
                    return;
                }
                renderItems(payload.items || []);
            })
            .catch(function () {
                setMeta('User lookup failed. You can still enter the exact email, phone, or user ID manually.', 'warn');
                closeSuggestions();
            });
    }

    lookupInput.addEventListener('input', function () {
        lookupIdInput.value = '';
        setMeta('Searching matching users...', 'default');
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(performLookup, 220);
    });

    lookupInput.addEventListener('focus', function () {
        const query = lookupInput.value.trim();
        if (query !== '' && suggestionsBox.innerHTML.trim() !== '') {
            suggestionsBox.classList.remove('hidden');
        }
    });

    suggestionsBox.addEventListener('click', function (event) {
        const button = event.target.closest('button[data-user-id]');
        if (!button) {
            return;
        }
        selectUser(button);
    });

    document.addEventListener('click', function (event) {
        if (!suggestionsBox.contains(event.target) && event.target !== lookupInput) {
            closeSuggestions();
        }
    });
});
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
