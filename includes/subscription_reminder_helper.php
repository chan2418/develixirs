<?php

require_once __DIR__ . '/subscription_lifecycle_helper.php';

if (!function_exists('ensure_subscription_reminder_schema')) {
    function ensure_subscription_reminder_schema(PDO $pdo): void
    {
        ensure_subscription_schema($pdo);

        if (!subscription_table_exists($pdo, 'subscription_reminder_logs')) {
            $pdo->exec(" 
                CREATE TABLE `subscription_reminder_logs` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `user_subscription_id` int(11) NOT NULL,
                  `user_id` int(11) NOT NULL,
                  `reminder_code` varchar(40) NOT NULL,
                  `days_to_expiry` int(11) NOT NULL DEFAULT 0,
                  `notification_title` varchar(255) DEFAULT NULL,
                  `notification_message` text DEFAULT NULL,
                  `user_notification_sent` tinyint(1) NOT NULL DEFAULT 0,
                  `email_status` varchar(20) NOT NULL DEFAULT 'skipped',
                  `email_error` text DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uniq_subscription_reminder` (`user_subscription_id`,`reminder_code`),
                  KEY `idx_subscription_reminder_user` (`user_id`),
                  KEY `idx_subscription_reminder_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
    }
}

if (!function_exists('subscription_reminder_day_map')) {
    function subscription_reminder_day_map(): array
    {
        return [
            7 => 'expiry_7_days',
            3 => 'expiry_3_days',
            0 => 'expiry_today',
        ];
    }
}

if (!function_exists('subscription_fetch_site_settings')) {
    function subscription_fetch_site_settings(PDO $pdo, array $keys = []): array
    {
        if (!subscription_table_exists($pdo, 'site_settings')) {
            return [];
        }

        $params = [];
        $sql = "SELECT setting_key, setting_value FROM site_settings";
        if ($keys) {
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $sql .= " WHERE setting_key IN ($placeholders)";
            $params = array_values($keys);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $settings = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }
}

if (!function_exists('subscription_reminder_content')) {
    function subscription_reminder_content(array $subscription, int $daysToExpiry, array $settings = []): array
    {
        $brandName = trim((string)($settings['company_name'] ?? 'DevElixir'));
        $supportEmail = trim((string)($settings['company_email'] ?? 'support@develixir.com'));
        $planName = trim((string)($subscription['display_plan_name'] ?? $subscription['plan_name'] ?? $subscription['current_plan_name'] ?? 'Subscription'));
        $endDate = !empty($subscription['end_date']) ? date('d M Y', strtotime($subscription['end_date'])) : 'soon';
        $discount = number_format((float)($subscription['effective_discount_percentage'] ?? $subscription['discount_percentage_snapshot'] ?? $subscription['current_discount_percentage'] ?? 0), 0);

        if ($daysToExpiry <= 0) {
            $title = 'Subscription expires today';
            $summary = $planName . ' expires today.';
            $body = 'Your ' . $planName . ' subscription expires today (' . $endDate . '). Renew now to keep your ' . $discount . '% member pricing active without a break.';
            $subject = $brandName . ' subscription expires today';
        } elseif ($daysToExpiry === 3) {
            $title = 'Subscription expires in 3 days';
            $summary = $planName . ' expires in 3 days.';
            $body = 'Your ' . $planName . ' subscription ends on ' . $endDate . '. Renew now to keep your ' . $discount . '% member pricing and avoid losing plan benefits.';
            $subject = $brandName . ' subscription expires in 3 days';
        } else {
            $title = 'Subscription expires in 7 days';
            $summary = $planName . ' expires in 7 days.';
            $body = 'Your ' . $planName . ' subscription ends on ' . $endDate . '. Renew now so your member pricing continues without interruption.';
            $subject = $brandName . ' subscription expires in 7 days';
        }

        $emailMessage = '<p>Hi ' . htmlspecialchars((string)($subscription['user_name'] ?? 'Customer')) . ',</p>'
            . '<p>' . htmlspecialchars($body) . '</p>'
            . '<p>Plan: <strong>' . htmlspecialchars($planName) . '</strong><br>'
            . 'Expiry Date: <strong>' . htmlspecialchars($endDate) . '</strong></p>'
            . '<p>If you need help, contact us at ' . htmlspecialchars($supportEmail) . '.</p>'
            . '<p>Team ' . htmlspecialchars($brandName) . '</p>';

        return [
            'title' => $title,
            'summary' => $summary,
            'body' => $body,
            'subject' => $subject,
            'email_message' => $emailMessage,
        ];
    }
}

if (!function_exists('subscription_send_reminder_email')) {
    function subscription_send_reminder_email(string $toEmail, string $subject, string $message): array
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'skipped',
                'error' => 'Invalid email address',
            ];
        }

        $mailerPath = __DIR__ . '/SMTPMailer.php';
        if (!is_file($mailerPath)) {
            return [
                'status' => 'skipped',
                'error' => 'Mailer not configured',
            ];
        }

        require_once $mailerPath;

        try {
            $mailer = new SMTPMailer();
            $result = $mailer->send($toEmail, $subject, $message);
            if ($result === true) {
                return [
                    'status' => 'sent',
                    'error' => null,
                ];
            }

            return [
                'status' => 'failed',
                'error' => is_string($result) ? $result : 'SMTP send failed',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}

if (!function_exists('subscription_process_expiry_reminders')) {
    function subscription_process_expiry_reminders(PDO $pdo, bool $sendEmails = true): array
    {
        ensure_subscription_reminder_schema($pdo);

        $dayMap = subscription_reminder_day_map();
        $settings = subscription_fetch_site_settings($pdo, ['company_name', 'company_email']);
        $daysSql = implode(',', array_map('intval', array_keys($dayMap)));

        $sql = "
            SELECT
                us.*,
                u.name AS user_name,
                u.email AS user_email,
                sp.name AS current_plan_name,
                sp.discount_percentage AS current_discount_percentage,
                sp.free_shipping AS current_free_shipping,
                DATEDIFF(us.end_date, CURDATE()) AS days_to_expiry
            FROM user_subscriptions us
            LEFT JOIN users u ON u.id = us.user_id
            LEFT JOIN subscription_plans sp ON sp.id = us.plan_id
            WHERE us.status = 'active'
              AND us.start_date <= CURDATE()
              AND us.end_date >= CURDATE()
              AND DATEDIFF(us.end_date, CURDATE()) IN ($daysSql)
            ORDER BY us.end_date ASC, us.id ASC
        ";

        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $result = [
            'eligible' => count($rows),
            'processed' => 0,
            'skipped_existing' => 0,
            'user_notifications_sent' => 0,
            'emails_sent' => 0,
            'email_failures' => 0,
        ];

        $hasUserNotifications = subscription_table_exists($pdo, 'user_notifications');
        $checkLogStmt = $pdo->prepare("SELECT id FROM subscription_reminder_logs WHERE user_subscription_id = ? AND reminder_code = ? LIMIT 1");
        $insertUserNotificationStmt = $hasUserNotifications
            ? $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())")
            : null;
        $insertLogStmt = $pdo->prepare(" 
            INSERT INTO subscription_reminder_logs (
                user_subscription_id, user_id, reminder_code, days_to_expiry,
                notification_title, notification_message, user_notification_sent,
                email_status, email_error, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($rows as $row) {
            $daysToExpiry = (int)$row['days_to_expiry'];
            $reminderCode = $dayMap[$daysToExpiry] ?? null;
            if ($reminderCode === null) {
                continue;
            }

            $checkLogStmt->execute([(int)$row['id'], $reminderCode]);
            if ($checkLogStmt->fetchColumn()) {
                $result['skipped_existing']++;
                continue;
            }

            $content = subscription_reminder_content($row, $daysToExpiry, $settings);
            $userNotificationSent = 0;
            if ($insertUserNotificationStmt) {
                $insertUserNotificationStmt->execute([
                    (int)$row['user_id'],
                    $content['title'],
                    $content['body'],
                ]);
                $userNotificationSent = 1;
                $result['user_notifications_sent']++;
            }

            $emailResult = [
                'status' => 'skipped',
                'error' => null,
            ];
            if ($sendEmails) {
                $emailResult = subscription_send_reminder_email(
                    (string)($row['user_email'] ?? ''),
                    $content['subject'],
                    $content['email_message']
                );
                if ($emailResult['status'] === 'sent') {
                    $result['emails_sent']++;
                } elseif ($emailResult['status'] === 'failed') {
                    $result['email_failures']++;
                }
            }

            $insertLogStmt->execute([
                (int)$row['id'],
                (int)$row['user_id'],
                $reminderCode,
                $daysToExpiry,
                $content['title'],
                $content['body'],
                $userNotificationSent,
                $emailResult['status'],
                $emailResult['error'],
            ]);

            $result['processed']++;
        }

        return $result;
    }
}
