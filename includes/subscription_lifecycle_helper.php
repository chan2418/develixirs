<?php

require_once __DIR__ . '/subscription_plan_helper.php';

if (!function_exists('subscription_base_record_sql')) {
    function subscription_base_record_sql(): string
    {
        return "
            SELECT
                us.*,
                u.name AS user_name,
                u.email AS user_email,
                u.phone AS user_phone,
                sp.name AS current_plan_name,
                sp.badge_text AS current_badge_text,
                sp.billing_cycle AS current_billing_cycle,
                sp.discount_percentage AS current_discount_percentage,
                sp.free_shipping AS current_free_shipping,
                sp.benefits AS current_benefits,
                tx.amount AS transaction_amount,
                tx.payment_status AS transaction_payment_status,
                tx.payment_date AS transaction_payment_date,
                tx.payment_method AS transaction_payment_method
            FROM user_subscriptions us
            LEFT JOIN users u ON u.id = us.user_id
            LEFT JOIN subscription_plans sp ON sp.id = us.plan_id
            LEFT JOIN (
                SELECT st1.*
                FROM subscription_transactions st1
                INNER JOIN (
                    SELECT user_subscription_id, MAX(id) AS latest_id
                    FROM subscription_transactions
                    GROUP BY user_subscription_id
                ) latest_tx ON latest_tx.latest_id = st1.id
            ) tx ON tx.user_subscription_id = us.id
        ";
    }
}

if (!function_exists('subscription_hydrate_record')) {
    function subscription_hydrate_record(array $row): array
    {
        $planName = trim((string)($row['plan_name'] ?? ''));
        if ($planName === '') {
            $planName = trim((string)($row['current_plan_name'] ?? 'Subscription'));
        }

        $discountPercentage = isset($row['discount_percentage_snapshot']) && $row['discount_percentage_snapshot'] !== null
            ? (float)$row['discount_percentage_snapshot']
            : (float)($row['current_discount_percentage'] ?? 0);
        $freeShipping = isset($row['free_shipping_snapshot'])
            ? !empty($row['free_shipping_snapshot'])
            : !empty($row['current_free_shipping']);
        $billingCycle = trim((string)($row['billing_cycle_snapshot'] ?? $row['current_billing_cycle'] ?? 'monthly'));
        $badgeText = trim((string)($row['badge_text_snapshot'] ?? $row['current_badge_text'] ?? ''));
        $benefits = $row['benefits_snapshot'] ?? ($row['current_benefits'] ?? '');

        $row['display_plan_name'] = $planName !== '' ? $planName : 'Subscription';
        $row['effective_discount_percentage'] = round($discountPercentage, 2);
        $row['effective_free_shipping'] = $freeShipping ? 1 : 0;
        $row['effective_billing_cycle'] = $billingCycle;
        $row['billing_cycle_label'] = subscription_cycle_label($billingCycle);
        $row['effective_badge_text'] = $badgeText;
        $row['benefits_list'] = subscription_decode_benefits($benefits, $discountPercentage, $freeShipping);
        $row['transaction_amount'] = isset($row['transaction_amount']) ? (float)$row['transaction_amount'] : null;

        return $row;
    }
}

if (!function_exists('subscription_fetch_current_active')) {
    function subscription_fetch_current_active(PDO $pdo, int $userId): ?array
    {
        ensure_subscription_schema($pdo);

        $sql = subscription_base_record_sql() . "
            WHERE us.user_id = ?
              AND us.status = 'active'
              AND us.start_date <= CURDATE()
              AND us.end_date >= CURDATE()
            ORDER BY us.end_date DESC, us.id DESC
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? subscription_hydrate_record($row) : null;
    }
}

if (!function_exists('subscription_fetch_upcoming_active')) {
    function subscription_fetch_upcoming_active(PDO $pdo, int $userId): ?array
    {
        ensure_subscription_schema($pdo);

        $sql = subscription_base_record_sql() . "
            WHERE us.user_id = ?
              AND us.status = 'active'
              AND us.start_date > CURDATE()
            ORDER BY us.start_date ASC, us.id ASC
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? subscription_hydrate_record($row) : null;
    }
}

if (!function_exists('subscription_fetch_latest_scheduled')) {
    function subscription_fetch_latest_scheduled(PDO $pdo, int $userId): ?array
    {
        ensure_subscription_schema($pdo);

        $sql = subscription_base_record_sql() . "
            WHERE us.user_id = ?
              AND us.status = 'active'
            ORDER BY us.end_date DESC, us.id DESC
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? subscription_hydrate_record($row) : null;
    }
}

if (!function_exists('subscription_fetch_history')) {
    function subscription_fetch_history(PDO $pdo, int $userId, int $limit = 10): array
    {
        ensure_subscription_schema($pdo);

        $limit = max(1, min(50, $limit));
        $sql = subscription_base_record_sql() . "
            WHERE us.user_id = ?
            ORDER BY us.created_at DESC, us.id DESC
            LIMIT {$limit}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $row = subscription_hydrate_record($row);
        }
        unset($row);

        return $rows;
    }
}

if (!function_exists('subscription_fetch_transactions')) {
    function subscription_fetch_transactions(PDO $pdo, int $userId, int $limit = 10): array
    {
        ensure_subscription_schema($pdo);

        $limit = max(1, min(50, $limit));
        $sql = "
            SELECT
                st.*,
                COALESCE(NULLIF(us.plan_name, ''), sp.name) AS plan_name
            FROM subscription_transactions st
            LEFT JOIN user_subscriptions us ON us.id = st.user_subscription_id
            LEFT JOIN subscription_plans sp ON sp.id = st.plan_id
            WHERE st.user_id = ?
            ORDER BY st.created_at DESC, st.id DESC
            LIMIT {$limit}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('subscription_sync_statuses')) {
    function subscription_sync_statuses(PDO $pdo, ?int $userId = null): array
    {
        ensure_subscription_schema($pdo);

        $expiredSql = "UPDATE user_subscriptions SET status = 'expired' WHERE status = 'active' AND end_date < CURDATE()";
        $expiredParams = [];
        if ($userId !== null) {
            $expiredSql .= " AND user_id = ?";
            $expiredParams[] = $userId;
        }
        $stmtExpire = $pdo->prepare($expiredSql);
        $stmtExpire->execute($expiredParams);
        $expiredRows = $stmtExpire->rowCount();

        if ($userId !== null) {
            $userIds = [$userId];
        } else {
            $userIds = $pdo->query("SELECT DISTINCT user_id FROM user_subscriptions ORDER BY user_id ASC")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }

        $usersSynced = 0;
        foreach ($userIds as $targetUserId) {
            $targetUserId = (int)$targetUserId;
            if ($targetUserId <= 0) {
                continue;
            }

            $current = subscription_fetch_current_active($pdo, $targetUserId);
            $stmtLatestEnd = $pdo->prepare("
                SELECT MAX(end_date)
                FROM user_subscriptions
                WHERE user_id = ?
                  AND status = 'active'
            ");
            $stmtLatestEnd->execute([$targetUserId]);
            $latestActiveEndDate = $stmtLatestEnd->fetchColumn() ?: null;

            if ($current) {
                $stmtUser = $pdo->prepare("
                    UPDATE users
                    SET is_subscriber = 1,
                        current_subscription_id = ?,
                        subscription_expires_at = ?
                    WHERE id = ?
                ");
                $stmtUser->execute([
                    (int)$current['id'],
                    $latestActiveEndDate ?: $current['end_date'],
                    $targetUserId,
                ]);
            } else {
                $stmtUser = $pdo->prepare("
                    UPDATE users
                    SET is_subscriber = 0,
                        current_subscription_id = NULL,
                        subscription_expires_at = NULL
                    WHERE id = ?
                ");
                $stmtUser->execute([$targetUserId]);
            }

            $usersSynced++;
        }

        return [
            'expired_rows' => $expiredRows,
            'users_synced' => $usersSynced,
        ];
    }
}

if (!function_exists('subscription_activate_paid_subscription')) {
    function subscription_activate_paid_subscription(PDO $pdo, int $userId, string $gatewayOrderId): array
    {
        ensure_subscription_schema($pdo);

        $stmt = $pdo->prepare("
            SELECT
                st.*,
                us.id AS subscription_id,
                us.plan_id,
                us.plan_name,
                us.validity_days_snapshot,
                us.end_date,
                us.start_date
            FROM subscription_transactions st
            INNER JOIN user_subscriptions us ON us.id = st.user_subscription_id
            WHERE st.payment_id = ?
              AND st.user_id = ?
            ORDER BY st.id DESC
            LIMIT 1
        ");
        $stmt->execute([$gatewayOrderId, $userId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            throw new RuntimeException('Transaction not found');
        }

        if (($transaction['payment_status'] ?? '') === 'completed') {
            subscription_sync_statuses($pdo, $userId);
            $current = subscription_fetch_current_active($pdo, $userId);
            $upcoming = subscription_fetch_upcoming_active($pdo, $userId);

            return [
                'transaction_id' => (int)$transaction['id'],
                'subscription_id' => (int)$transaction['subscription_id'],
                'activation_type' => $upcoming ? 'renewal' : 'instant',
                'current_subscription' => $current,
                'upcoming_subscription' => $upcoming,
                'effective_start_date' => $transaction['start_date'],
                'effective_end_date' => $transaction['end_date'],
            ];
        }

        $latestScheduled = subscription_fetch_latest_scheduled($pdo, $userId);
        $validityDays = max(1, (int)($transaction['validity_days_snapshot'] ?? 30));
        $startDate = date('Y-m-d');
        $activationType = 'instant';

        if ($latestScheduled && (int)$latestScheduled['id'] !== (int)$transaction['subscription_id']) {
            $startDate = date('Y-m-d', strtotime($latestScheduled['end_date'] . ' +1 day'));
            $activationType = 'renewal';
        }

        $endDate = date('Y-m-d', strtotime($startDate . " +{$validityDays} days"));

        $stmtTx = $pdo->prepare("
            UPDATE subscription_transactions
            SET payment_status = 'completed',
                payment_date = NOW(),
                payment_method = 'razorpay'
            WHERE id = ?
        ");
        $stmtTx->execute([(int)$transaction['id']]);

        $stmtSub = $pdo->prepare("
            UPDATE user_subscriptions
            SET status = 'active',
                start_date = ?,
                end_date = ?
            WHERE id = ?
        ");
        $stmtSub->execute([
            $startDate,
            $endDate,
            (int)$transaction['subscription_id'],
        ]);

        subscription_sync_statuses($pdo, $userId);
        $current = subscription_fetch_current_active($pdo, $userId);
        $upcoming = subscription_fetch_upcoming_active($pdo, $userId);

        return [
            'transaction_id' => (int)$transaction['id'],
            'subscription_id' => (int)$transaction['subscription_id'],
            'activation_type' => $activationType,
            'current_subscription' => $current,
            'upcoming_subscription' => $upcoming,
            'effective_start_date' => $startDate,
            'effective_end_date' => $endDate,
        ];
    }
}

if (!function_exists('subscription_activate_manual_subscription')) {
    function subscription_activate_manual_subscription(PDO $pdo, int $userId, int $planId, array $options = []): array
    {
        ensure_subscription_schema($pdo);

        if ($userId <= 0) {
            throw new RuntimeException('Invalid user selected.');
        }

        $plan = subscription_fetch_plan_by_id($pdo, $planId, false);
        if (!$plan) {
            throw new RuntimeException('Subscription plan not found.');
        }

        $activationMode = trim((string)($options['activation_mode'] ?? 'queue'));
        if (!in_array($activationMode, ['queue', 'replace'], true)) {
            $activationMode = 'queue';
        }

        $amount = isset($options['amount']) ? max(0, round((float)$options['amount'], 2)) : 0.0;
        $paymentMethod = strtolower(trim((string)($options['payment_method'] ?? 'admin_manual')));
        $paymentMethod = preg_replace('/[^a-z0-9_-]+/i', '_', $paymentMethod);
        $paymentMethod = trim((string)$paymentMethod, '_');
        if ($paymentMethod === '') {
            $paymentMethod = 'admin_manual';
        }
        $paymentMethod = substr($paymentMethod, 0, 50);

        $startedTransaction = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            if ($activationMode === 'replace') {
                $stmtCancel = $pdo->prepare("
                    UPDATE user_subscriptions
                    SET status = 'cancelled'
                    WHERE user_id = ?
                      AND status = 'active'
                      AND end_date >= CURDATE()
                ");
                $stmtCancel->execute([$userId]);
            }

            $latestScheduled = subscription_fetch_latest_scheduled($pdo, $userId);
            $startDate = date('Y-m-d');
            $activationType = 'instant';

            if ($activationMode === 'queue' && $latestScheduled) {
                $startDate = date('Y-m-d', strtotime($latestScheduled['end_date'] . ' +1 day'));
                $activationType = 'renewal';
            }

            $validityDays = max(1, (int)($plan['validity_days'] ?? 30));
            $endDate = date('Y-m-d', strtotime($startDate . " +{$validityDays} days"));

            $stmtSub = $pdo->prepare("
                INSERT INTO user_subscriptions (
                    user_id, plan_id, status, start_date, end_date, auto_renew,
                    plan_name, price_paid, compare_price_snapshot, discount_percentage_snapshot,
                    billing_cycle_snapshot, validity_days_snapshot, free_shipping_snapshot,
                    badge_text_snapshot, benefits_snapshot
                ) VALUES (
                    ?, ?, 'active', ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?
                )
            ");
            $stmtSub->execute([
                $userId,
                (int)$plan['id'],
                $startDate,
                $endDate,
                !empty($plan['auto_renew_enabled']) ? 1 : 0,
                $plan['name'],
                $amount,
                $plan['compare_price'] !== null ? (float)$plan['compare_price'] : null,
                (float)$plan['discount_percentage'],
                $plan['billing_cycle'],
                $validityDays,
                !empty($plan['free_shipping']) ? 1 : 0,
                $plan['badge_text'] ?? null,
                json_encode($plan['benefits_list'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);
            $subscriptionId = (int)$pdo->lastInsertId();

            $manualPaymentId = 'manual-' . $subscriptionId . '-' . date('YmdHis');
            $stmtTx = $pdo->prepare("
                INSERT INTO subscription_transactions (
                    user_subscription_id, user_id, plan_id, amount, payment_method, payment_id, payment_status, payment_date
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, 'completed', NOW()
                )
            ");
            $stmtTx->execute([
                $subscriptionId,
                $userId,
                (int)$plan['id'],
                $amount,
                $paymentMethod,
                $manualPaymentId,
            ]);
            $transactionId = (int)$pdo->lastInsertId();

            subscription_sync_statuses($pdo, $userId);
            $current = subscription_fetch_current_active($pdo, $userId);
            $upcoming = subscription_fetch_upcoming_active($pdo, $userId);

            if ($startedTransaction) {
                $pdo->commit();
            }

            return [
                'transaction_id' => $transactionId,
                'subscription_id' => $subscriptionId,
                'activation_type' => $activationType,
                'current_subscription' => $current,
                'upcoming_subscription' => $upcoming,
                'effective_start_date' => $startDate,
                'effective_end_date' => $endDate,
                'payment_method' => $paymentMethod,
                'amount' => $amount,
            ];
        } catch (Throwable $e) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
