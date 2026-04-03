<?php

require_once __DIR__ . '/subscription_lifecycle_helper.php';
require_once __DIR__ . '/subscription_reminder_helper.php';

if (!function_exists('subscription_reporting_filters')) {
    function subscription_reporting_filters(): array
    {
        return [
            'current' => 'Current',
            'upcoming' => 'Upcoming',
            'expired' => 'Expired',
            'all' => 'All Records',
        ];
    }
}

if (!function_exists('subscription_reporting_normalize_filter')) {
    function subscription_reporting_normalize_filter(?string $filter): string
    {
        $filter = strtolower(trim((string)$filter));
        $filters = subscription_reporting_filters();

        return isset($filters[$filter]) ? $filter : 'current';
    }
}

if (!function_exists('subscription_reporting_range_options')) {
    function subscription_reporting_range_options(): array
    {
        return [
            '30d' => 'Last 30 Days',
            '90d' => 'Last 90 Days',
            '365d' => 'Last 365 Days',
            'fy' => 'Current Financial Year',
        ];
    }
}

if (!function_exists('subscription_reporting_normalize_range')) {
    function subscription_reporting_normalize_range(?string $range): string
    {
        $range = strtolower(trim((string)$range));
        $options = subscription_reporting_range_options();

        return isset($options[$range]) ? $range : '90d';
    }
}

if (!function_exists('subscription_reporting_resolve_range')) {
    function subscription_reporting_resolve_range(?string $range): array
    {
        $range = subscription_reporting_normalize_range($range);
        $today = new DateTimeImmutable('today');

        if ($range === '30d') {
            $start = $today->modify('-29 days');
        } elseif ($range === '365d') {
            $start = $today->modify('-364 days');
        } elseif ($range === 'fy') {
            $year = (int)$today->format('Y');
            $month = (int)$today->format('n');
            if ($month >= 4) {
                $start = new DateTimeImmutable($year . '-04-01');
            } else {
                $start = new DateTimeImmutable(($year - 1) . '-04-01');
            }
        } else {
            $start = $today->modify('-89 days');
        }

        return [
            'key' => $range,
            'label' => subscription_reporting_range_options()[$range],
            'start' => $start->format('Y-m-d'),
            'end' => $today->format('Y-m-d'),
        ];
    }
}

if (!function_exists('subscription_records_where_sql')) {
    function subscription_records_where_sql(string $filter): string
    {
        $filter = subscription_reporting_normalize_filter($filter);

        if ($filter === 'upcoming') {
            return "WHERE us.status = 'active' AND us.start_date > CURDATE()";
        }
        if ($filter === 'expired') {
            return "WHERE us.status = 'expired'";
        }
        if ($filter === 'all') {
            return '';
        }

        return "WHERE us.status = 'active' AND us.start_date <= CURDATE() AND us.end_date >= CURDATE()";
    }
}

if (!function_exists('subscription_fetch_record_counts')) {
    function subscription_fetch_record_counts(PDO $pdo): array
    {
        ensure_subscription_schema($pdo);

        return [
            'current' => (int)$pdo->query("SELECT COUNT(*) FROM user_subscriptions WHERE status = 'active' AND start_date <= CURDATE() AND end_date >= CURDATE()")->fetchColumn(),
            'upcoming' => (int)$pdo->query("SELECT COUNT(*) FROM user_subscriptions WHERE status = 'active' AND start_date > CURDATE()")->fetchColumn(),
            'expired' => (int)$pdo->query("SELECT COUNT(*) FROM user_subscriptions WHERE status = 'expired'")->fetchColumn(),
            'all' => (int)$pdo->query("SELECT COUNT(*) FROM user_subscriptions")->fetchColumn(),
        ];
    }
}

if (!function_exists('subscription_fetch_records')) {
    function subscription_fetch_records(PDO $pdo, string $filter = 'current', int $limit = 150, string $search = ''): array
    {
        ensure_subscription_schema($pdo);

        $filter = subscription_reporting_normalize_filter($filter);
        $limit = max(1, min(5000, $limit));
        $search = trim($search);
        $where = subscription_records_where_sql($filter);
        $params = [];

        if ($search !== '') {
            $searchClause = "(" . implode(' OR ', [
                'u.name LIKE ?',
                'u.email LIKE ?',
                'u.phone LIKE ?',
                "COALESCE(NULLIF(us.plan_name, ''), sp.name, '') LIKE ?",
            ]) . ")";
            $searchParams = array_fill(0, 4, '%' . $search . '%');
            if ($where === '') {
                $where = 'WHERE ' . $searchClause;
            } else {
                $where .= ' AND ' . $searchClause;
            }
            $params = $searchParams;
        }

        $sql = subscription_base_record_sql() . "\n            {$where}\n            ORDER BY us.created_at DESC, us.id DESC\n            LIMIT {$limit}\n        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $row = subscription_hydrate_record($row);
        }
        unset($row);

        return $rows;
    }
}

if (!function_exists('subscription_fetch_report_data')) {
    function subscription_fetch_report_data(PDO $pdo, ?string $range = null): array
    {
        ensure_subscription_schema($pdo);
        ensure_subscription_reminder_schema($pdo);

        $resolvedRange = subscription_reporting_resolve_range($range);
        $start = $resolvedRange['start'];
        $end = $resolvedRange['end'];

        $overview = [
            'revenue' => 0.0,
            'completed_purchases' => 0,
            'avg_ticket' => 0.0,
            'renewals_sold' => 0,
            'active_subscribers' => 0,
            'queued_renewals' => 0,
            'expiring_soon' => 0,
            'subscription_discount_given' => 0.0,
            'subscription_orders' => 0,
            'subscription_order_revenue' => 0.0,
        ];

        $stmtOverview = $pdo->prepare(" 
            SELECT
                COUNT(*) AS completed_purchases,
                COALESCE(SUM(st.amount), 0) AS revenue,
                COALESCE(AVG(st.amount), 0) AS avg_ticket
            FROM subscription_transactions st
            WHERE st.payment_status = 'completed'
              AND DATE(COALESCE(st.payment_date, st.created_at)) BETWEEN ? AND ?
        ");
        $stmtOverview->execute([$start, $end]);
        $overview = array_merge($overview, array_map(static function ($value) {
            return is_numeric($value) ? (float)$value : $value;
        }, $stmtOverview->fetch(PDO::FETCH_ASSOC) ?: []));
        $overview['completed_purchases'] = (int)($overview['completed_purchases'] ?? 0);
        $overview['revenue'] = (float)($overview['revenue'] ?? 0);
        $overview['avg_ticket'] = (float)($overview['avg_ticket'] ?? 0);

        $overview['active_subscribers'] = (int)$pdo->query("SELECT COUNT(*) FROM user_subscriptions WHERE status = 'active' AND start_date <= CURDATE() AND end_date >= CURDATE()")->fetchColumn();
        $overview['queued_renewals'] = (int)$pdo->query("SELECT COUNT(*) FROM user_subscriptions WHERE status = 'active' AND start_date > CURDATE()")->fetchColumn();
        $overview['expiring_soon'] = (int)$pdo->query("SELECT COUNT(*) FROM user_subscriptions WHERE status = 'active' AND start_date <= CURDATE() AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

        $stmtRenewals = $pdo->prepare(" 
            SELECT COUNT(*)
            FROM user_subscriptions us
            WHERE DATE(us.created_at) BETWEEN ? AND ?
              AND EXISTS (
                  SELECT 1
                  FROM user_subscriptions prev
                  WHERE prev.user_id = us.user_id
                    AND prev.id < us.id
              )
        ");
        $stmtRenewals->execute([$start, $end]);
        $overview['renewals_sold'] = (int)$stmtRenewals->fetchColumn();

        if (subscription_table_exists($pdo, 'orders') && subscription_column_exists($pdo, 'orders', 'subscription_discount') && subscription_column_exists($pdo, 'orders', 'applied_discount_type')) {
            $stmtOrders = $pdo->prepare(" 
                SELECT
                    COUNT(*) AS subscription_orders,
                    COALESCE(SUM(subscription_discount), 0) AS subscription_discount_given,
                    COALESCE(SUM(total_amount), 0) AS subscription_order_revenue
                FROM orders
                WHERE applied_discount_type = 'subscription'
                  AND DATE(created_at) BETWEEN ? AND ?
            ");
            $stmtOrders->execute([$start, $end]);
            $orderMetrics = $stmtOrders->fetch(PDO::FETCH_ASSOC) ?: [];
            $overview['subscription_orders'] = (int)($orderMetrics['subscription_orders'] ?? 0);
            $overview['subscription_discount_given'] = (float)($orderMetrics['subscription_discount_given'] ?? 0);
            $overview['subscription_order_revenue'] = (float)($orderMetrics['subscription_order_revenue'] ?? 0);
        }

        $stmtTopPlans = $pdo->prepare(" 
            SELECT
                COALESCE(NULLIF(us.plan_name, ''), sp.name, 'Subscription') AS plan_name,
                COUNT(*) AS purchases,
                COALESCE(SUM(st.amount), 0) AS revenue,
                COALESCE(AVG(st.amount), 0) AS avg_amount
            FROM subscription_transactions st
            LEFT JOIN user_subscriptions us ON us.id = st.user_subscription_id
            LEFT JOIN subscription_plans sp ON sp.id = st.plan_id
            WHERE st.payment_status = 'completed'
              AND DATE(COALESCE(st.payment_date, st.created_at)) BETWEEN ? AND ?
            GROUP BY plan_name
            ORDER BY revenue DESC, purchases DESC, plan_name ASC
            LIMIT 5
        ");
        $stmtTopPlans->execute([$start, $end]);
        $topPlans = $stmtTopPlans->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmtTrend = $pdo->prepare(" 
            SELECT
                DATE_FORMAT(DATE(COALESCE(st.payment_date, st.created_at)), '%Y-%m-01') AS month_key,
                COUNT(*) AS purchases,
                COALESCE(SUM(st.amount), 0) AS revenue
            FROM subscription_transactions st
            WHERE st.payment_status = 'completed'
              AND DATE(COALESCE(st.payment_date, st.created_at)) BETWEEN ? AND ?
            GROUP BY month_key
            ORDER BY month_key ASC
        ");
        $stmtTrend->execute([$start, $end]);
        $trendRows = $stmtTrend->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($trendRows as &$trendRow) {
            $trendRow['label'] = date('M Y', strtotime((string)$trendRow['month_key']));
            $trendRow['purchases'] = (int)$trendRow['purchases'];
            $trendRow['revenue'] = (float)$trendRow['revenue'];
        }
        unset($trendRow);

        $stmtExpiring = $pdo->query(" 
            SELECT
                us.*,
                u.name AS user_name,
                u.email AS user_email,
                u.phone AS user_phone,
                sp.name AS current_plan_name,
                sp.discount_percentage AS current_discount_percentage,
                DATEDIFF(us.end_date, CURDATE()) AS days_to_expiry
            FROM user_subscriptions us
            LEFT JOIN users u ON u.id = us.user_id
            LEFT JOIN subscription_plans sp ON sp.id = us.plan_id
            WHERE us.status = 'active'
              AND us.start_date <= CURDATE()
              AND us.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY us.end_date ASC, us.id ASC
            LIMIT 12
        ");
        $expiringSoon = $stmtExpiring->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($expiringSoon as &$row) {
            $row = subscription_hydrate_record($row);
            $row['days_to_expiry'] = (int)($row['days_to_expiry'] ?? 0);
        }
        unset($row);

        $reminderBreakdown = [];
        $recentReminders = [];
        if (subscription_table_exists($pdo, 'subscription_reminder_logs')) {
            $stmtReminderBreakdown = $pdo->prepare(" 
                SELECT reminder_code, COUNT(*) AS total_sent
                FROM subscription_reminder_logs
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY reminder_code
                ORDER BY total_sent DESC, reminder_code ASC
            ");
            $stmtReminderBreakdown->execute([$start, $end]);
            $reminderBreakdown = $stmtReminderBreakdown->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $stmtRecentReminders = $pdo->prepare(" 
                SELECT
                    rl.*,
                    u.name AS user_name,
                    u.email AS user_email,
                    COALESCE(NULLIF(us.plan_name, ''), sp.name, 'Subscription') AS plan_name
                FROM subscription_reminder_logs rl
                LEFT JOIN users u ON u.id = rl.user_id
                LEFT JOIN user_subscriptions us ON us.id = rl.user_subscription_id
                LEFT JOIN subscription_plans sp ON sp.id = us.plan_id
                WHERE DATE(rl.created_at) BETWEEN ? AND ?
                ORDER BY rl.created_at DESC, rl.id DESC
                LIMIT 10
            ");
            $stmtRecentReminders->execute([$start, $end]);
            $recentReminders = $stmtRecentReminders->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        return [
            'range' => $resolvedRange,
            'overview' => $overview,
            'top_plans' => $topPlans,
            'trend' => $trendRows,
            'expiring_soon' => $expiringSoon,
            'reminder_breakdown' => $reminderBreakdown,
            'recent_reminders' => $recentReminders,
        ];
    }
}

if (!function_exists('subscription_user_dashboard_stats')) {
    function subscription_user_dashboard_stats(PDO $pdo, int $userId, ?array $currentSubscription = null, ?array $upcomingSubscription = null): array
    {
        ensure_subscription_schema($pdo);

        $stats = [
            'days_left' => null,
            'member_since' => null,
            'subscription_total_paid' => 0.0,
            'subscription_payment_count' => 0,
            'subscription_order_count' => 0,
            'subscription_order_savings' => 0.0,
            'subscription_order_revenue' => 0.0,
        ];

        if ($currentSubscription && !empty($currentSubscription['end_date'])) {
            $today = new DateTimeImmutable('today');
            $end = new DateTimeImmutable((string)$currentSubscription['end_date']);
            $stats['days_left'] = max(0, (int)$today->diff($end)->format('%r%a'));
        }

        $stmtPaid = $pdo->prepare(" 
            SELECT
                COUNT(*) AS payment_count,
                COALESCE(SUM(amount), 0) AS total_paid,
                MIN(DATE(COALESCE(payment_date, created_at))) AS member_since
            FROM subscription_transactions
            WHERE user_id = ?
              AND payment_status = 'completed'
        ");
        $stmtPaid->execute([$userId]);
        $paidRow = $stmtPaid->fetch(PDO::FETCH_ASSOC) ?: [];
        $stats['subscription_payment_count'] = (int)($paidRow['payment_count'] ?? 0);
        $stats['subscription_total_paid'] = (float)($paidRow['total_paid'] ?? 0);
        $stats['member_since'] = $paidRow['member_since'] ?? null;

        if (subscription_table_exists($pdo, 'orders') && subscription_column_exists($pdo, 'orders', 'subscription_discount') && subscription_column_exists($pdo, 'orders', 'applied_discount_type')) {
            $stmtOrders = $pdo->prepare(" 
                SELECT
                    COUNT(*) AS subscription_order_count,
                    COALESCE(SUM(subscription_discount), 0) AS subscription_order_savings,
                    COALESCE(SUM(total_amount), 0) AS subscription_order_revenue
                FROM orders
                WHERE user_id = ?
                  AND applied_discount_type = 'subscription'
            ");
            $stmtOrders->execute([$userId]);
            $orderRow = $stmtOrders->fetch(PDO::FETCH_ASSOC) ?: [];
            $stats['subscription_order_count'] = (int)($orderRow['subscription_order_count'] ?? 0);
            $stats['subscription_order_savings'] = (float)($orderRow['subscription_order_savings'] ?? 0);
            $stats['subscription_order_revenue'] = (float)($orderRow['subscription_order_revenue'] ?? 0);
        }

        $stats['next_cycle_label'] = $upcomingSubscription['billing_cycle_label'] ?? ($currentSubscription['billing_cycle_label'] ?? 'Not set');

        return $stats;
    }
}
