<?php
if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/subscription_lifecycle_helper.php';
require_once __DIR__ . '/../includes/subscription_reminder_helper.php';

try {
    $syncResult = subscription_sync_statuses($pdo);
    $reminderResult = subscription_process_expiry_reminders($pdo, true);
    $payload = [
        'success' => true,
        'expired_rows' => (int)$syncResult['expired_rows'],
        'users_synced' => (int)$syncResult['users_synced'],
        'reminders_processed' => (int)$reminderResult['processed'],
        'reminders_skipped_existing' => (int)$reminderResult['skipped_existing'],
        'user_notifications_sent' => (int)$reminderResult['user_notifications_sent'],
        'emails_sent' => (int)$reminderResult['emails_sent'],
        'email_failures' => (int)$reminderResult['email_failures'],
        'ran_at' => date('Y-m-d H:i:s'),
    ];

    if (PHP_SAPI === 'cli') {
        echo 'Subscription sync completed | Expired: ' . $payload['expired_rows']
            . ' | Users synced: ' . $payload['users_synced']
            . ' | Reminders: ' . $payload['reminders_processed']
            . ' | Emails sent: ' . $payload['emails_sent']
            . ' | Ran at: ' . $payload['ran_at'] . PHP_EOL;
    } else {
        echo json_encode($payload);
    }
} catch (Throwable $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Subscription sync failed: ' . $e->getMessage() . PHP_EOL);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }
}
