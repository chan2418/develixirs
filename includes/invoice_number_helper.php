<?php
// Shared invoice number generator.
// Format: DEV/WEB/<FINANCIAL_YEAR>/<ORDER_SEQUENCE_IN_FY>

if (!function_exists('invoice_brand_code')) {
    function invoice_brand_code(): string
    {
        return 'DEV';
    }
}

if (!function_exists('invoice_channel_code')) {
    function invoice_channel_code(): string
    {
        return 'WEB';
    }
}

if (!function_exists('invoice_financial_year_start')) {
    function invoice_financial_year_start(?string $dateValue): int
    {
        $timestamp = strtotime((string)$dateValue);
        if ($timestamp === false) {
            $timestamp = time();
        }

        $year = (int)date('Y', $timestamp);
        $month = (int)date('n', $timestamp);

        return ($month >= 4) ? $year : ($year - 1);
    }
}

if (!function_exists('invoice_financial_year_code')) {
    function invoice_financial_year_code(?string $dateValue): string
    {
        $startYear = invoice_financial_year_start($dateValue);
        $endYear = $startYear + 1;

        return substr((string)$startYear, -2) . substr((string)$endYear, -2);
    }
}

if (!function_exists('invoice_financial_year_window')) {
    function invoice_financial_year_window(?string $dateValue): array
    {
        $startYear = invoice_financial_year_start($dateValue);

        return [
            sprintf('%04d-04-01 00:00:00', $startYear),
            sprintf('%04d-04-01 00:00:00', $startYear + 1),
        ];
    }
}

if (!function_exists('build_invoice_number_from_parts')) {
    function build_invoice_number_from_parts(string $financialYearCode, int $sequence): string
    {
        $sequence = max(1, $sequence);

        return invoice_brand_code() . '/' . invoice_channel_code() . '/' . $financialYearCode . '/' . $sequence;
    }
}

if (!function_exists('invoice_order_context')) {
    function invoice_order_context(PDO $pdo, int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("SELECT id, created_at FROM orders WHERE id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        return $order ?: null;
    }
}

if (!function_exists('invoice_order_sequence')) {
    function invoice_order_sequence(PDO $pdo, int $orderId, ?string $orderCreatedAt = null): int
    {
        if ($orderId <= 0) {
            return 1;
        }

        if (!$orderCreatedAt) {
            $order = invoice_order_context($pdo, $orderId);
            if (!$order) {
                return max(1, $orderId);
            }
            $orderCreatedAt = $order['created_at'] ?? null;
        }

        [$fyStart, $fyEnd] = invoice_financial_year_window($orderCreatedAt);

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM orders
            WHERE created_at >= ?
              AND created_at < ?
              AND (
                    created_at < ?
                    OR (created_at = ? AND id <= ?)
              )
        ");
        $stmt->execute([$fyStart, $fyEnd, $orderCreatedAt, $orderCreatedAt, $orderId]);

        return max(1, (int)$stmt->fetchColumn());
    }
}

if (!function_exists('build_invoice_number_for_order')) {
    function build_invoice_number_for_order(PDO $pdo, int $orderId, ?string $orderCreatedAt = null): string
    {
        if (!$orderCreatedAt) {
            $order = invoice_order_context($pdo, $orderId);
            $orderCreatedAt = $order['created_at'] ?? null;
        }

        $financialYearCode = invoice_financial_year_code($orderCreatedAt);
        $sequence = invoice_order_sequence($pdo, $orderId, $orderCreatedAt);

        return build_invoice_number_from_parts($financialYearCode, $sequence);
    }
}

if (!function_exists('build_invoice_number')) {
    function build_invoice_number($pdoOrOrderNumber = null, int $orderId = 0, ?string $orderCreatedAt = null): string
    {
        if ($pdoOrOrderNumber instanceof PDO) {
            return build_invoice_number_for_order($pdoOrOrderNumber, $orderId, $orderCreatedAt);
        }

        $financialYearCode = invoice_financial_year_code($orderCreatedAt);
        $sequence = max(1, $orderId);

        return build_invoice_number_from_parts($financialYearCode, $sequence);
    }
}

if (!function_exists('sync_invoice_number')) {
    function sync_invoice_number(PDO $pdo, int $invoiceId): ?string
    {
        if ($invoiceId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT i.id, i.invoice_number, i.order_id, o.created_at AS order_created_at
            FROM invoices i
            JOIN orders o ON o.id = i.order_id
            WHERE i.id = ?
            LIMIT 1
        ");
        $stmt->execute([$invoiceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $expected = build_invoice_number_for_order($pdo, (int)$row['order_id'], $row['order_created_at'] ?? null);
        if (($row['invoice_number'] ?? '') !== $expected) {
            $update = $pdo->prepare("UPDATE invoices SET invoice_number = ? WHERE id = ?");
            $update->execute([$expected, $invoiceId]);
        }

        return $expected;
    }
}

if (!function_exists('sync_all_invoice_numbers')) {
    function sync_all_invoice_numbers(PDO $pdo): int
    {
        $stmt = $pdo->query("
            SELECT i.id, i.invoice_number, i.order_id, o.created_at AS order_created_at
            FROM invoices i
            JOIN orders o ON o.id = i.order_id
            ORDER BY o.created_at ASC, o.id ASC, i.id ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return 0;
        }

        $updated = 0;
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
                $update = $pdo->prepare("UPDATE invoices SET invoice_number = ? WHERE id = ?");
                $update->execute([$expected, $row['id']]);
                $updated++;
            }
        }

        return $updated;
    }
}
