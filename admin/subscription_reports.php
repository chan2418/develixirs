<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/subscription_reporting_helper.php';
require_once __DIR__ . '/../includes/subscription_reminder_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$flash = null;
$flashClass = 'emerald';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    if ($action === 'sync_now') {
        $result = subscription_sync_statuses($pdo);
        $flash = 'Status sync completed. Expired: ' . (int)$result['expired_rows'] . ' | Users synced: ' . (int)$result['users_synced'];
    } elseif ($action === 'run_reminders') {
        $result = subscription_process_expiry_reminders($pdo, true);
        $flash = 'Reminder sweep completed. Processed: ' . (int)$result['processed'] . ' | Notifications: ' . (int)$result['user_notifications_sent'] . ' | Emails sent: ' . (int)$result['emails_sent'];
        $flashClass = 'amber';
    }
}

subscription_sync_statuses($pdo);

$range = subscription_reporting_normalize_range($_GET['range'] ?? '90d');
$report = subscription_fetch_report_data($pdo, $range);
$overview = $report['overview'];
$page_title = 'Subscription Reports';
$page_subtitle = 'Revenue, reminders, and subscription performance';
include __DIR__ . '/layout/header.php';
?>

<div class="max-w-[1500px] mx-auto py-6 px-4 space-y-6">
    <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-50 text-sky-700 text-xs font-semibold uppercase tracking-wide">
                    Phase 4
                    <span class="text-slate-400">Reports + reminders</span>
                </div>
                <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-900">Subscription Reports</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Track revenue, renewals, subscriber savings, expiring plans, and reminder delivery from one place. Use this page before launch and for daily monitoring after launch.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <form method="post">
                    <input type="hidden" name="action" value="run_reminders">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-3 text-sm font-semibold text-amber-700 hover:bg-amber-100 transition">
                        Run Reminders Now
                    </button>
                </form>
                <form method="post">
                    <input type="hidden" name="action" value="sync_now">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">
                        Run Status Sync
                    </button>
                </form>
                <a href="/admin/subscription_subscribers.php?filter=current" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:border-slate-400 hover:text-slate-900 transition">
                    Open Subscribers
                </a>
            </div>
        </div>
        <?php if ($flash): ?>
            <div class="mt-4 rounded-2xl border px-4 py-3 text-sm <?php echo $flashClass === 'amber' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'; ?>">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Reporting Range</h2>
                <p class="text-sm text-slate-500"><?php echo htmlspecialchars($report['range']['label']); ?>: <?php echo date('d M Y', strtotime($report['range']['start'])); ?> to <?php echo date('d M Y', strtotime($report['range']['end'])); ?></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php foreach (subscription_reporting_range_options() as $key => $label): ?>
                    <a href="?range=<?php echo urlencode($key); ?>" class="rounded-2xl px-4 py-2 text-sm font-semibold <?php echo $range === $key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'; ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-500">Subscription Revenue</div>
            <div class="mt-3 text-3xl font-black text-slate-900">₹<?php echo number_format((float)$overview['revenue'], 2); ?></div>
            <div class="mt-2 text-sm text-slate-500"><?php echo (int)$overview['completed_purchases']; ?> completed purchases</div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-500">Average Ticket</div>
            <div class="mt-3 text-3xl font-black text-slate-900">₹<?php echo number_format((float)$overview['avg_ticket'], 2); ?></div>
            <div class="mt-2 text-sm text-slate-500"><?php echo (int)$overview['renewals_sold']; ?> renewals sold</div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-500">Active Subscribers</div>
            <div class="mt-3 text-3xl font-black text-slate-900"><?php echo (int)$overview['active_subscribers']; ?></div>
            <div class="mt-2 text-sm text-slate-500"><?php echo (int)$overview['queued_renewals']; ?> queued renewals</div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-500">Expiring In 7 Days</div>
            <div class="mt-3 text-3xl font-black text-slate-900"><?php echo (int)$overview['expiring_soon']; ?></div>
            <div class="mt-2 text-sm text-slate-500">Current active plans ending soon</div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-500">Savings Given</div>
            <div class="mt-3 text-3xl font-black text-slate-900">₹<?php echo number_format((float)$overview['subscription_discount_given'], 2); ?></div>
            <div class="mt-2 text-sm text-slate-500"><?php echo (int)$overview['subscription_orders']; ?> orders used membership pricing</div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1.4fr,.9fr] gap-6">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Revenue Trend</h2>
                <p class="text-sm text-slate-500">Completed subscription payments in the selected range</p>
            </div>
            <div class="p-6 space-y-4">
                <?php if (empty($report['trend'])): ?>
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">No completed subscription payments in this range yet.</div>
                <?php else: ?>
                    <?php
                        $maxTrendRevenue = 0.0;
                        foreach ($report['trend'] as $trendRevenueRow) {
                            $trendRevenueValue = (float)($trendRevenueRow['revenue'] ?? 0);
                            if ($trendRevenueValue > $maxTrendRevenue) {
                                $maxTrendRevenue = $trendRevenueValue;
                            }
                        }
                    ?>
                    <?php foreach ($report['trend'] as $trendRow): ?>
                        <?php $width = $maxTrendRevenue > 0 ? max(8, (int)round(((float)$trendRow['revenue'] / $maxTrendRevenue) * 100)) : 8; ?>
                        <div>
                            <div class="flex items-center justify-between gap-4 text-sm mb-2">
                                <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($trendRow['label']); ?></div>
                                <div class="text-slate-500"><?php echo (int)$trendRow['purchases']; ?> purchase(s) • ₹<?php echo number_format((float)$trendRow['revenue'], 2); ?></div>
                            </div>
                            <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500" style="width: <?php echo $width; ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Top Plans</h2>
                <p class="text-sm text-slate-500">Best-performing plans by subscription revenue</p>
            </div>
            <div class="p-6">
                <?php if (empty($report['top_plans'])): ?>
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">No plan revenue data found in this range.</div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($report['top_plans'] as $planRow): ?>
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="font-bold text-slate-900"><?php echo htmlspecialchars($planRow['plan_name']); ?></div>
                                        <div class="mt-1 text-sm text-slate-500"><?php echo (int)$planRow['purchases']; ?> purchase(s)</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-slate-900">₹<?php echo number_format((float)$planRow['revenue'], 2); ?></div>
                                        <div class="text-sm text-slate-500">Avg ₹<?php echo number_format((float)$planRow['avg_amount'], 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1.2fr,1fr] gap-6">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Expiring Soon</h2>
                    <p class="text-sm text-slate-500">Subscribers whose current membership ends within the next 7 days</p>
                </div>
                <a href="/admin/subscription_subscribers.php?filter=current" class="text-sm font-semibold text-slate-700 hover:text-slate-900">View all current</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="px-6 py-4 text-left">Customer</th>
                            <th class="px-6 py-4 text-left">Plan</th>
                            <th class="px-6 py-4 text-left">Days Left</th>
                            <th class="px-6 py-4 text-left">Expiry</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($report['expiring_soon'])): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500">No active subscriptions are expiring in the next 7 days.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($report['expiring_soon'] as $row): ?>
                                <tr>
                                    <td class="px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($row['user_name'] ?: 'User #' . $row['user_id']); ?></div>
                                        <div class="text-slate-500"><?php echo htmlspecialchars($row['user_email'] ?? ''); ?></div>
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($row['display_plan_name']); ?></div>
                                        <div class="text-slate-500"><?php echo htmlspecialchars($row['billing_cycle_label']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?php echo (int)$row['days_to_expiry'] <= 3 ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700'; ?>">
                                            <?php echo (int)$row['days_to_expiry']; ?> day(s)
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 align-top text-slate-600"><?php echo date('d M Y', strtotime($row['end_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Reminder Activity</h2>
                <p class="text-sm text-slate-500">Reminder logs recorded in the selected range</p>
            </div>
            <div class="p-6 space-y-4">
                <?php if (empty($report['reminder_breakdown'])): ?>
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">No reminder logs yet for this range.</div>
                <?php else: ?>
                    <?php foreach ($report['reminder_breakdown'] as $breakdownRow): ?>
                        <div class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3">
                            <div class="font-semibold text-slate-900"><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst((string)$breakdownRow['reminder_code']))); ?></div>
                            <div class="text-sm text-slate-500"><?php echo (int)$breakdownRow['total_sent']; ?> sent</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="pt-2 border-t border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900 mb-3">Recent Reminder Logs</h3>
                    <?php if (empty($report['recent_reminders'])): ?>
                        <div class="text-sm text-slate-500">No reminder records available yet.</div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($report['recent_reminders'] as $log): ?>
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($log['user_name'] ?: 'User #' . $log['user_id']); ?></div>
                                            <div class="text-sm text-slate-500"><?php echo htmlspecialchars($log['plan_name']); ?></div>
                                        </div>
                                        <div class="text-right text-xs text-slate-500">
                                            <div><?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?></div>
                                            <div class="mt-1 <?php echo ($log['email_status'] ?? 'skipped') === 'failed' ? 'text-rose-600' : 'text-slate-500'; ?>">
                                                Email: <?php echo htmlspecialchars((string)$log['email_status']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-sm text-slate-600"><?php echo htmlspecialchars($log['notification_title'] ?? ''); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
