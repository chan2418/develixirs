<?php

if (!function_exists('subscription_db_name')) {
    function subscription_db_name(PDO $pdo): string
    {
        static $dbName = null;
        if ($dbName !== null) {
            return $dbName;
        }

        if (defined('DB_NAME') && DB_NAME !== '') {
            $dbName = DB_NAME;
            return $dbName;
        }

        $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
        return $dbName;
    }
}

if (!function_exists('subscription_table_exists')) {
    function subscription_table_exists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
        ");
        $stmt->execute([subscription_db_name($pdo), $table]);

        return ((int)$stmt->fetchColumn()) > 0;
    }
}

if (!function_exists('subscription_column_exists')) {
    function subscription_column_exists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([subscription_db_name($pdo), $table, $column]);

        return ((int)$stmt->fetchColumn()) > 0;
    }
}

if (!function_exists('subscription_index_exists')) {
    function subscription_index_exists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
        ");
        $stmt->execute([subscription_db_name($pdo), $table, $index]);

        return ((int)$stmt->fetchColumn()) > 0;
    }
}

if (!function_exists('subscription_slugify')) {
    function subscription_slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim((string)$slug, '-');

        return $slug !== '' ? $slug : 'subscription-plan';
    }
}

if (!function_exists('subscription_cycle_options')) {
    function subscription_cycle_options(): array
    {
        return [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ];
    }
}

if (!function_exists('subscription_cycle_label')) {
    function subscription_cycle_label(?string $cycle): string
    {
        $cycle = strtolower(trim((string)$cycle));
        $options = subscription_cycle_options();

        return $options[$cycle] ?? ucfirst($cycle ?: 'Monthly');
    }
}

if (!function_exists('subscription_cycle_suffix')) {
    function subscription_cycle_suffix(?string $cycle): string
    {
        $cycle = strtolower(trim((string)$cycle));

        if ($cycle === 'quarterly') {
            return '/3 months';
        }
        if ($cycle === 'yearly') {
            return '/year';
        }

        return '/month';
    }
}

if (!function_exists('subscription_default_benefits')) {
    function subscription_default_benefits(float $discountPercentage = 0.0, bool $freeShipping = false): array
    {
        $items = [];

        if ($discountPercentage > 0) {
            $items[] = number_format($discountPercentage, 0) . '% subscriber discount on products';
        }
        if ($freeShipping) {
            $items[] = 'Free shipping on member orders';
        }

        $items[] = 'Priority customer support';
        $items[] = 'Early access to new arrivals';
        $items[] = 'Exclusive subscriber-only offers';

        return $items;
    }
}

if (!function_exists('subscription_decode_benefits')) {
    function subscription_decode_benefits($value, float $discountPercentage = 0.0, bool $freeShipping = false): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = json_decode((string)$value, true);
        }

        if (!is_array($items)) {
            $items = [];
        }

        $items = array_values(array_filter(array_map(static function ($item) {
            return trim((string)$item);
        }, $items)));

        if (!$items) {
            $items = subscription_default_benefits($discountPercentage, $freeShipping);
        }

        return $items;
    }
}

if (!function_exists('subscription_encode_benefits_from_text')) {
    function subscription_encode_benefits_from_text(string $text, float $discountPercentage = 0.0, bool $freeShipping = false): string
    {
        $items = preg_split('/\R+/', $text) ?: [];
        $items = array_values(array_filter(array_map(static function ($item) {
            return trim((string)$item);
        }, $items)));

        if (!$items) {
            $items = subscription_default_benefits($discountPercentage, $freeShipping);
        }

        return json_encode($items, JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('subscription_benefits_text')) {
    function subscription_benefits_text($value, float $discountPercentage = 0.0, bool $freeShipping = false): string
    {
        return implode("\n", subscription_decode_benefits($value, $discountPercentage, $freeShipping));
    }
}

if (!function_exists('subscription_recommended_plans')) {
    function subscription_recommended_plans(): array
    {
        return [
            [
                'slug' => 'glow-monthly',
                'name' => 'Glow Monthly',
                'short_description' => 'Entry membership for repeat skincare shoppers.',
                'badge_text' => 'Starter',
                'price' => 249.00,
                'compare_price' => 299.00,
                'discount_percentage' => 5.00,
                'billing_cycle' => 'monthly',
                'validity_days' => 30,
                'display_order' => 1,
                'is_featured' => 0,
                'free_shipping' => 0,
                'auto_renew_enabled' => 1,
                'is_active' => 1,
                'benefits' => json_encode([
                    '5% subscriber discount on products',
                    'Priority customer support',
                    'Early access to new arrivals',
                    'Exclusive subscriber-only offers',
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                'slug' => 'care-quarterly',
                'name' => 'Care Quarterly',
                'short_description' => 'Best value for regular customers buying every few weeks.',
                'badge_text' => 'Most Popular',
                'price' => 599.00,
                'compare_price' => 749.00,
                'discount_percentage' => 10.00,
                'billing_cycle' => 'quarterly',
                'validity_days' => 90,
                'display_order' => 2,
                'is_featured' => 1,
                'free_shipping' => 0,
                'auto_renew_enabled' => 1,
                'is_active' => 1,
                'benefits' => json_encode([
                    '10% subscriber discount on products',
                    'Priority customer support',
                    'Early access to new arrivals',
                    'Exclusive subscriber-only offers',
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                'slug' => 'elite-yearly',
                'name' => 'Elite Yearly',
                'short_description' => 'Highest savings plan for loyal repeat buyers.',
                'badge_text' => 'Best Value',
                'price' => 1799.00,
                'compare_price' => 2199.00,
                'discount_percentage' => 15.00,
                'billing_cycle' => 'yearly',
                'validity_days' => 365,
                'display_order' => 3,
                'is_featured' => 0,
                'free_shipping' => 1,
                'auto_renew_enabled' => 1,
                'is_active' => 1,
                'benefits' => json_encode([
                    '15% subscriber discount on products',
                    'Free shipping on member orders',
                    'Priority customer support',
                    'Early access to new arrivals',
                    'Exclusive subscriber-only offers',
                ], JSON_UNESCAPED_UNICODE),
            ],
        ];
    }
}

if (!function_exists('ensure_subscription_schema')) {
    function ensure_subscription_schema(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        if (!subscription_table_exists($pdo, 'subscription_plans')) {
            $pdo->exec("
                CREATE TABLE `subscription_plans` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `slug` varchar(80) DEFAULT NULL,
                  `name` varchar(100) NOT NULL,
                  `short_description` varchar(255) DEFAULT NULL,
                  `badge_text` varchar(80) DEFAULT NULL,
                  `price` decimal(10,2) NOT NULL,
                  `compare_price` decimal(10,2) DEFAULT NULL,
                  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
                  `billing_cycle` varchar(20) NOT NULL DEFAULT 'monthly',
                  `benefits` text DEFAULT NULL,
                  `display_order` int(11) NOT NULL DEFAULT 0,
                  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
                  `free_shipping` tinyint(1) NOT NULL DEFAULT 0,
                  `is_active` tinyint(1) NOT NULL DEFAULT 1,
                  `auto_renew_enabled` tinyint(1) NOT NULL DEFAULT 1,
                  `validity_days` int(11) NOT NULL DEFAULT 30,
                  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            $pdo->exec("ALTER TABLE subscription_plans MODIFY COLUMN billing_cycle VARCHAR(20) NOT NULL DEFAULT 'monthly'");

            $columns = [
                'slug' => "ALTER TABLE subscription_plans ADD COLUMN slug VARCHAR(80) DEFAULT NULL AFTER id",
                'short_description' => "ALTER TABLE subscription_plans ADD COLUMN short_description VARCHAR(255) DEFAULT NULL AFTER name",
                'badge_text' => "ALTER TABLE subscription_plans ADD COLUMN badge_text VARCHAR(80) DEFAULT NULL AFTER short_description",
                'compare_price' => "ALTER TABLE subscription_plans ADD COLUMN compare_price DECIMAL(10,2) DEFAULT NULL AFTER price",
                'display_order' => "ALTER TABLE subscription_plans ADD COLUMN display_order INT(11) NOT NULL DEFAULT 0 AFTER benefits",
                'is_featured' => "ALTER TABLE subscription_plans ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER display_order",
                'free_shipping' => "ALTER TABLE subscription_plans ADD COLUMN free_shipping TINYINT(1) NOT NULL DEFAULT 0 AFTER is_featured",
            ];

            foreach ($columns as $column => $sql) {
                if (!subscription_column_exists($pdo, 'subscription_plans', $column)) {
                    $pdo->exec($sql);
                }
            }
        }

        if (!subscription_index_exists($pdo, 'subscription_plans', 'idx_subscription_plan_visibility')) {
            $pdo->exec("ALTER TABLE subscription_plans ADD INDEX idx_subscription_plan_visibility (is_active, is_featured, display_order)");
        }

        if (!subscription_index_exists($pdo, 'subscription_plans', 'idx_subscription_plan_slug')) {
            $pdo->exec("ALTER TABLE subscription_plans ADD INDEX idx_subscription_plan_slug (slug)");
        }

        if (!subscription_table_exists($pdo, 'user_subscriptions')) {
            $pdo->exec("
                CREATE TABLE `user_subscriptions` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `user_id` int(11) NOT NULL,
                  `plan_id` int(11) NOT NULL,
                  `status` enum('active','expired','cancelled','pending') NOT NULL DEFAULT 'pending',
                  `start_date` date NOT NULL,
                  `end_date` date NOT NULL,
                  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
                  `plan_name` varchar(100) DEFAULT NULL,
                  `price_paid` decimal(10,2) DEFAULT NULL,
                  `compare_price_snapshot` decimal(10,2) DEFAULT NULL,
                  `discount_percentage_snapshot` decimal(5,2) DEFAULT NULL,
                  `billing_cycle_snapshot` varchar(20) DEFAULT NULL,
                  `validity_days_snapshot` int(11) DEFAULT NULL,
                  `free_shipping_snapshot` tinyint(1) NOT NULL DEFAULT 0,
                  `badge_text_snapshot` varchar(80) DEFAULT NULL,
                  `benefits_snapshot` text DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  PRIMARY KEY (`id`),
                  KEY `user_id` (`user_id`),
                  KEY `plan_id` (`plan_id`),
                  KEY `status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            $userSubscriptionColumns = [
                'plan_name' => "ALTER TABLE user_subscriptions ADD COLUMN plan_name VARCHAR(100) DEFAULT NULL AFTER auto_renew",
                'price_paid' => "ALTER TABLE user_subscriptions ADD COLUMN price_paid DECIMAL(10,2) DEFAULT NULL AFTER plan_name",
                'compare_price_snapshot' => "ALTER TABLE user_subscriptions ADD COLUMN compare_price_snapshot DECIMAL(10,2) DEFAULT NULL AFTER price_paid",
                'discount_percentage_snapshot' => "ALTER TABLE user_subscriptions ADD COLUMN discount_percentage_snapshot DECIMAL(5,2) DEFAULT NULL AFTER compare_price_snapshot",
                'billing_cycle_snapshot' => "ALTER TABLE user_subscriptions ADD COLUMN billing_cycle_snapshot VARCHAR(20) DEFAULT NULL AFTER discount_percentage_snapshot",
                'validity_days_snapshot' => "ALTER TABLE user_subscriptions ADD COLUMN validity_days_snapshot INT(11) DEFAULT NULL AFTER billing_cycle_snapshot",
                'free_shipping_snapshot' => "ALTER TABLE user_subscriptions ADD COLUMN free_shipping_snapshot TINYINT(1) NOT NULL DEFAULT 0 AFTER validity_days_snapshot",
                'badge_text_snapshot' => "ALTER TABLE user_subscriptions ADD COLUMN badge_text_snapshot VARCHAR(80) DEFAULT NULL AFTER free_shipping_snapshot",
                'benefits_snapshot' => "ALTER TABLE user_subscriptions ADD COLUMN benefits_snapshot TEXT DEFAULT NULL AFTER badge_text_snapshot",
            ];

            foreach ($userSubscriptionColumns as $column => $sql) {
                if (!subscription_column_exists($pdo, 'user_subscriptions', $column)) {
                    $pdo->exec($sql);
                }
            }
        }

        if (!subscription_table_exists($pdo, 'subscription_transactions')) {
            $pdo->exec("
                CREATE TABLE `subscription_transactions` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `user_subscription_id` int(11) NOT NULL,
                  `user_id` int(11) NOT NULL,
                  `plan_id` int(11) NOT NULL,
                  `amount` decimal(10,2) NOT NULL,
                  `payment_method` varchar(50) DEFAULT NULL,
                  `payment_id` varchar(255) DEFAULT NULL,
                  `payment_status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
                  `payment_date` timestamp NULL DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`),
                  KEY `user_subscription_id` (`user_subscription_id`),
                  KEY `user_id` (`user_id`),
                  KEY `payment_status` (`payment_status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            $subscriptionTransactionColumns = [
                'payment_method' => "ALTER TABLE subscription_transactions ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER amount",
                'payment_id' => "ALTER TABLE subscription_transactions ADD COLUMN payment_id VARCHAR(255) DEFAULT NULL AFTER payment_method",
                'payment_status' => "ALTER TABLE subscription_transactions ADD COLUMN payment_status ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending' AFTER payment_id",
                'payment_date' => "ALTER TABLE subscription_transactions ADD COLUMN payment_date TIMESTAMP NULL DEFAULT NULL AFTER payment_status",
                'created_at' => "ALTER TABLE subscription_transactions ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT current_timestamp() AFTER payment_date",
            ];

            foreach ($subscriptionTransactionColumns as $column => $sql) {
                if (!subscription_column_exists($pdo, 'subscription_transactions', $column)) {
                    $pdo->exec($sql);
                }
            }
        }

        if (!subscription_index_exists($pdo, 'subscription_transactions', 'user_subscription_id')) {
            $pdo->exec("ALTER TABLE subscription_transactions ADD INDEX user_subscription_id (user_subscription_id)");
        }

        if (!subscription_index_exists($pdo, 'subscription_transactions', 'user_id')) {
            $pdo->exec("ALTER TABLE subscription_transactions ADD INDEX user_id (user_id)");
        }

        if (!subscription_index_exists($pdo, 'subscription_transactions', 'payment_status')) {
            $pdo->exec("ALTER TABLE subscription_transactions ADD INDEX payment_status (payment_status)");
        }

        if (subscription_table_exists($pdo, 'users')) {
            $userColumns = [
                'is_subscriber' => "ALTER TABLE users ADD COLUMN is_subscriber TINYINT(1) NOT NULL DEFAULT 0 AFTER created_at",
                'current_subscription_id' => "ALTER TABLE users ADD COLUMN current_subscription_id INT(11) DEFAULT NULL AFTER is_subscriber",
                'subscription_expires_at' => "ALTER TABLE users ADD COLUMN subscription_expires_at DATE DEFAULT NULL AFTER current_subscription_id",
            ];

            foreach ($userColumns as $column => $sql) {
                if (!subscription_column_exists($pdo, 'users', $column)) {
                    $pdo->exec($sql);
                }
            }
        }

        $done = true;

        $plans = $pdo->query("SELECT id, name, slug, display_order, is_active, is_featured, discount_percentage, free_shipping, benefits FROM subscription_plans ORDER BY created_at ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $displayOrder = 1;
        $featuredPlanId = 0;

        foreach ($plans as $plan) {
            $updates = [];
            $params = [];

            $slug = trim((string)($plan['slug'] ?? ''));
            if ($slug === '') {
                $slug = subscription_slugify((string)$plan['name']);
                $slug .= '-' . (int)$plan['id'];
                $updates[] = 'slug = ?';
                $params[] = $slug;
            }

            if ((int)($plan['display_order'] ?? 0) <= 0) {
                $updates[] = 'display_order = ?';
                $params[] = $displayOrder;
            }

            $benefits = subscription_decode_benefits($plan['benefits'] ?? '', (float)($plan['discount_percentage'] ?? 0), !empty($plan['free_shipping']));
            $encodedBenefits = json_encode($benefits, JSON_UNESCAPED_UNICODE);
            if (($plan['benefits'] ?? '') !== $encodedBenefits) {
                $updates[] = 'benefits = ?';
                $params[] = $encodedBenefits;
            }

            if ($updates) {
                $params[] = (int)$plan['id'];
                $stmt = $pdo->prepare("UPDATE subscription_plans SET " . implode(', ', $updates) . " WHERE id = ?");
                $stmt->execute($params);
            }

            if (!$featuredPlanId && (int)$plan['is_featured'] === 1 && (int)$plan['is_active'] === 1) {
                $featuredPlanId = (int)$plan['id'];
            }

            $displayOrder++;
        }

        if (!$plans) {
            subscription_seed_recommended_plans($pdo, false);
        } elseif ($featuredPlanId === 0) {
            $stmt = $pdo->query("SELECT id FROM subscription_plans WHERE is_active = 1 ORDER BY display_order ASC, price ASC, id ASC LIMIT 1");
            $featuredPlanId = (int)$stmt->fetchColumn();
            if ($featuredPlanId > 0) {
                $pdo->prepare("UPDATE subscription_plans SET is_featured = 0")->execute();
                $pdo->prepare("UPDATE subscription_plans SET is_featured = 1 WHERE id = ?")->execute([$featuredPlanId]);
            }
        } else {
            $pdo->prepare("UPDATE subscription_plans SET is_featured = 0 WHERE id <> ?")->execute([$featuredPlanId]);
        }
    }
}

if (!function_exists('subscription_seed_recommended_plans')) {
    function subscription_seed_recommended_plans(PDO $pdo, bool $overwriteExisting = false): int
    {
        ensure_subscription_schema($pdo);

        $rowsChanged = 0;
        foreach (subscription_recommended_plans() as $plan) {
            $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE slug = ? LIMIT 1");
            $stmt->execute([$plan['slug']]);
            $existingId = (int)$stmt->fetchColumn();

            if ($existingId > 0) {
                if ($overwriteExisting) {
                    $update = $pdo->prepare("
                        UPDATE subscription_plans
                        SET name = ?, short_description = ?, badge_text = ?, price = ?, compare_price = ?, discount_percentage = ?,
                            billing_cycle = ?, benefits = ?, display_order = ?, is_featured = ?, free_shipping = ?, is_active = ?,
                            auto_renew_enabled = ?, validity_days = ?
                        WHERE id = ?
                    ");
                    $update->execute([
                        $plan['name'],
                        $plan['short_description'],
                        $plan['badge_text'],
                        $plan['price'],
                        $plan['compare_price'],
                        $plan['discount_percentage'],
                        $plan['billing_cycle'],
                        $plan['benefits'],
                        $plan['display_order'],
                        $plan['is_featured'],
                        $plan['free_shipping'],
                        $plan['is_active'],
                        $plan['auto_renew_enabled'],
                        $plan['validity_days'],
                        $existingId,
                    ]);
                    $rowsChanged++;
                }
                continue;
            }

            $insert = $pdo->prepare("
                INSERT INTO subscription_plans (
                    slug, name, short_description, badge_text, price, compare_price, discount_percentage, billing_cycle,
                    benefits, display_order, is_featured, free_shipping, is_active, auto_renew_enabled, validity_days
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?
                )
            ");
            $insert->execute([
                $plan['slug'],
                $plan['name'],
                $plan['short_description'],
                $plan['badge_text'],
                $plan['price'],
                $plan['compare_price'],
                $plan['discount_percentage'],
                $plan['billing_cycle'],
                $plan['benefits'],
                $plan['display_order'],
                $plan['is_featured'],
                $plan['free_shipping'],
                $plan['is_active'],
                $plan['auto_renew_enabled'],
                $plan['validity_days'],
            ]);
            $rowsChanged++;
        }

        $featuredSlug = subscription_recommended_plans()[1]['slug'];
        $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE slug = ? LIMIT 1");
        $stmt->execute([$featuredSlug]);
        $featuredId = (int)$stmt->fetchColumn();
        if ($featuredId > 0) {
            $pdo->prepare("UPDATE subscription_plans SET is_featured = 0")->execute();
            $pdo->prepare("UPDATE subscription_plans SET is_featured = 1 WHERE id = ?")->execute([$featuredId]);
        }

        return $rowsChanged;
    }
}

if (!function_exists('subscription_prepare_plan_payload')) {
    function subscription_prepare_plan_payload(array $source): array
    {
        $name = trim((string)($source['name'] ?? ''));
        $slug = trim((string)($source['slug'] ?? ''));
        $shortDescription = trim((string)($source['short_description'] ?? ''));
        $badgeText = trim((string)($source['badge_text'] ?? ''));
        $price = (float)($source['price'] ?? 0);
        $comparePrice = trim((string)($source['compare_price'] ?? ''));
        $discountPercentage = (float)($source['discount_percentage'] ?? 0);
        $billingCycle = strtolower(trim((string)($source['billing_cycle'] ?? 'monthly')));
        $validityDays = max(1, (int)($source['validity_days'] ?? 30));
        $displayOrder = max(1, (int)($source['display_order'] ?? 1));
        $isActive = !empty($source['is_active']) ? 1 : 0;
        $isFeatured = !empty($source['is_featured']) ? 1 : 0;
        $freeShipping = !empty($source['free_shipping']) ? 1 : 0;
        $autoRenew = !empty($source['auto_renew_enabled']) ? 1 : 0;

        if (!array_key_exists($billingCycle, subscription_cycle_options())) {
            $billingCycle = 'monthly';
        }

        if ($slug === '') {
            $slug = subscription_slugify($name);
        } else {
            $slug = subscription_slugify($slug);
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'short_description' => $shortDescription,
            'badge_text' => $badgeText !== '' ? $badgeText : null,
            'price' => $price,
            'compare_price' => $comparePrice !== '' ? (float)$comparePrice : null,
            'discount_percentage' => $discountPercentage,
            'billing_cycle' => $billingCycle,
            'validity_days' => $validityDays,
            'display_order' => $displayOrder,
            'is_active' => $isActive,
            'is_featured' => $isFeatured,
            'free_shipping' => $freeShipping,
            'auto_renew_enabled' => $autoRenew,
            'benefits' => subscription_encode_benefits_from_text((string)($source['benefits_text'] ?? ''), $discountPercentage, (bool)$freeShipping),
        ];
    }
}

if (!function_exists('subscription_slug_in_use')) {
    function subscription_slug_in_use(PDO $pdo, string $slug, int $ignoreId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM subscription_plans WHERE slug = ?";
        $params = [$slug];

        if ($ignoreId > 0) {
            $sql .= " AND id <> ?";
            $params[] = $ignoreId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return ((int)$stmt->fetchColumn()) > 0;
    }
}

if (!function_exists('subscription_fetch_admin_plans')) {
    function subscription_fetch_admin_plans(PDO $pdo): array
    {
        ensure_subscription_schema($pdo);

        $activeCounts = [];
        if (subscription_table_exists($pdo, 'user_subscriptions')) {
            $stats = $pdo->query("
                SELECT plan_id,
                       SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_subscribers,
                       COUNT(*) AS total_subscriptions
                FROM user_subscriptions
                GROUP BY plan_id
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($stats as $row) {
                $activeCounts[(int)$row['plan_id']] = [
                    'active_subscribers' => (int)$row['active_subscribers'],
                    'total_subscriptions' => (int)$row['total_subscriptions'],
                ];
            }
        }

        $stmt = $pdo->query("
            SELECT *
            FROM subscription_plans
            ORDER BY is_featured DESC, display_order ASC, price ASC, id ASC
        ");
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($plans as &$plan) {
            $plan['benefits_list'] = subscription_decode_benefits($plan['benefits'] ?? '', (float)$plan['discount_percentage'], !empty($plan['free_shipping']));
            $plan['benefits_text'] = implode("\n", $plan['benefits_list']);
            $plan['active_subscribers'] = $activeCounts[(int)$plan['id']]['active_subscribers'] ?? 0;
            $plan['total_subscriptions'] = $activeCounts[(int)$plan['id']]['total_subscriptions'] ?? 0;
        }
        unset($plan);

        return $plans;
    }
}

if (!function_exists('subscription_fetch_active_plans')) {
    function subscription_fetch_active_plans(PDO $pdo): array
    {
        ensure_subscription_schema($pdo);

        $stmt = $pdo->query("
            SELECT *
            FROM subscription_plans
            WHERE is_active = 1
            ORDER BY is_featured DESC, display_order ASC, price ASC, id ASC
        ");
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($plans as &$plan) {
            $plan['benefits_list'] = subscription_decode_benefits($plan['benefits'] ?? '', (float)$plan['discount_percentage'], !empty($plan['free_shipping']));
        }
        unset($plan);

        return $plans;
    }
}

if (!function_exists('subscription_fetch_plan_by_id')) {
    function subscription_fetch_plan_by_id(PDO $pdo, int $planId, bool $activeOnly = true): ?array
    {
        ensure_subscription_schema($pdo);

        if ($planId <= 0) {
            return null;
        }

        $sql = "SELECT * FROM subscription_plans WHERE id = ?";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            return null;
        }

        $plan['benefits_list'] = subscription_decode_benefits($plan['benefits'] ?? '', (float)$plan['discount_percentage'], !empty($plan['free_shipping']));

        return $plan;
    }
}

if (!function_exists('subscription_fetch_primary_plan')) {
    function subscription_fetch_primary_plan(PDO $pdo, ?int $requestedPlanId = null, bool $activeOnly = true): ?array
    {
        if ($requestedPlanId && $requestedPlanId > 0) {
            $plan = subscription_fetch_plan_by_id($pdo, $requestedPlanId, $activeOnly);
            if ($plan) {
                return $plan;
            }
        }

        $plans = $activeOnly ? subscription_fetch_active_plans($pdo) : subscription_fetch_admin_plans($pdo);
        return $plans[0] ?? null;
    }
}
