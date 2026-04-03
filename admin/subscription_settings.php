<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/subscription_plan_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function subscription_flash_set(string $key, string $value): void
{
    $_SESSION[$key] = $value;
}

function subscription_flash_get(string $key): ?string
{
    $value = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);
    return $value;
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

ensure_subscription_schema($pdo);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        subscription_flash_set('flash_error', 'Invalid request token.');
        header('Location: subscription_settings.php');
        exit;
    }

    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'seed_recommended') {
            $changed = subscription_seed_recommended_plans($pdo, !empty($_POST['overwrite_existing']));
            subscription_flash_set('flash_success', "Recommended plans synced. {$changed} plan(s) inserted or refreshed.");
        } elseif ($action === 'save_plan') {
            $planId = (int)($_POST['plan_id'] ?? 0);
            $payload = subscription_prepare_plan_payload($_POST);

            if ($payload['name'] === '') {
                throw new RuntimeException('Plan name is required.');
            }
            if ($payload['price'] <= 0) {
                throw new RuntimeException('Plan price must be greater than zero.');
            }
            if ($payload['compare_price'] !== null && $payload['compare_price'] <= $payload['price']) {
                $payload['compare_price'] = null;
            }
            if ($payload['is_featured']) {
                $payload['is_active'] = 1;
            }
            if (subscription_slug_in_use($pdo, $payload['slug'], $planId)) {
                throw new RuntimeException('Slug already exists. Use a different slug.');
            }

            if ($planId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE subscription_plans
                    SET slug = ?, name = ?, short_description = ?, badge_text = ?, price = ?, compare_price = ?,
                        discount_percentage = ?, billing_cycle = ?, benefits = ?, display_order = ?, is_featured = ?,
                        free_shipping = ?, is_active = ?, auto_renew_enabled = ?, validity_days = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $payload['slug'],
                    $payload['name'],
                    $payload['short_description'],
                    $payload['badge_text'],
                    $payload['price'],
                    $payload['compare_price'],
                    $payload['discount_percentage'],
                    $payload['billing_cycle'],
                    $payload['benefits'],
                    $payload['display_order'],
                    $payload['is_featured'],
                    $payload['free_shipping'],
                    $payload['is_active'],
                    $payload['auto_renew_enabled'],
                    $payload['validity_days'],
                    $planId,
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO subscription_plans (
                        slug, name, short_description, badge_text, price, compare_price, discount_percentage, billing_cycle,
                        benefits, display_order, is_featured, free_shipping, is_active, auto_renew_enabled, validity_days
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?
                    )
                ");
                $stmt->execute([
                    $payload['slug'],
                    $payload['name'],
                    $payload['short_description'],
                    $payload['badge_text'],
                    $payload['price'],
                    $payload['compare_price'],
                    $payload['discount_percentage'],
                    $payload['billing_cycle'],
                    $payload['benefits'],
                    $payload['display_order'],
                    $payload['is_featured'],
                    $payload['free_shipping'],
                    $payload['is_active'],
                    $payload['auto_renew_enabled'],
                    $payload['validity_days'],
                ]);
                $planId = (int)$pdo->lastInsertId();
            }

            if ($payload['is_featured']) {
                $stmt = $pdo->prepare("UPDATE subscription_plans SET is_featured = 0 WHERE id <> ?");
                $stmt->execute([$planId]);
            }

            ensure_subscription_schema($pdo);
            subscription_flash_set('flash_success', 'Subscription plan saved successfully.');
        } elseif ($action === 'set_featured') {
            $planId = (int)($_POST['plan_id'] ?? 0);
            if ($planId <= 0) {
                throw new RuntimeException('Invalid plan selected.');
            }

            $stmt = $pdo->prepare("UPDATE subscription_plans SET is_featured = 0");
            $stmt->execute();
            $stmt = $pdo->prepare("UPDATE subscription_plans SET is_featured = 1, is_active = 1 WHERE id = ?");
            $stmt->execute([$planId]);

            subscription_flash_set('flash_success', 'Featured plan updated.');
        } elseif ($action === 'toggle_active') {
            $planId = (int)($_POST['plan_id'] ?? 0);
            $nextState = !empty($_POST['next_state']) ? 1 : 0;
            if ($planId <= 0) {
                throw new RuntimeException('Invalid plan selected.');
            }

            $stmt = $pdo->prepare("UPDATE subscription_plans SET is_active = ? WHERE id = ?");
            $stmt->execute([$nextState, $planId]);

            if ($nextState === 0) {
                ensure_subscription_schema($pdo);
            }

            subscription_flash_set('flash_success', $nextState ? 'Plan activated.' : 'Plan deactivated.');
        } else {
            throw new RuntimeException('Unknown action.');
        }
    } catch (Throwable $e) {
        subscription_flash_set('flash_error', $e->getMessage());
    }

    header('Location: subscription_settings.php');
    exit;
}

$plans = subscription_fetch_admin_plans($pdo);
$planCount = count($plans);
$activePlanCount = count(array_filter($plans, static function ($plan) {
    return !empty($plan['is_active']);
}));
$featuredPlan = null;
$activeSubscribers = 0;

foreach ($plans as $plan) {
    if (!empty($plan['is_featured'])) {
        $featuredPlan = $plan;
    }
    $activeSubscribers += (int)($plan['active_subscribers'] ?? 0);
}

$flashSuccess = subscription_flash_get('flash_success');
$flashError = subscription_flash_get('flash_error');

$page_title = 'Subscription Settings';
$page_subtitle = 'Manage multiple plans, featured plan, pricing and billing cycles';
include __DIR__ . '/layout/header.php';
?>

<div class="max-w-[1400px] mx-auto py-6 px-4 space-y-6">
    <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold uppercase tracking-wide">
                    Phase 1
                    <span class="text-slate-400">Multi-plan foundation</span>
                </div>
                <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-900">Subscription Plan Manager</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    This page now supports multiple plans. Use one featured plan as the default highlight, keep multiple active plans live, and manage monthly, quarterly, and yearly pricing from one place.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <form method="post" class="inline-flex items-center gap-2">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="seed_recommended">
                    <input type="hidden" name="overwrite_existing" value="1">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">
                        Load Recommended Plans
                    </button>
                </form>
                <a href="#new-plan" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:border-slate-400 hover:bg-slate-50 transition">
                    Add Custom Plan
                </a>
            </div>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
            <?= h($flashSuccess) ?>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-800">
            <?= h($flashError) ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Plans</div>
            <div class="mt-2 text-3xl font-black text-slate-900"><?= $planCount ?></div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active Plans</div>
            <div class="mt-2 text-3xl font-black text-slate-900"><?= $activePlanCount ?></div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Featured Plan</div>
            <div class="mt-2 text-lg font-bold text-slate-900"><?= h($featuredPlan['name'] ?? 'Not set') ?></div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active Subscribers</div>
            <div class="mt-2 text-3xl font-black text-slate-900"><?= $activeSubscribers ?></div>
        </div>
    </div>

    <div id="new-plan" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-black text-slate-900">Create New Plan</h2>
                <p class="mt-1 text-sm text-slate-500">Use this for custom plans beyond the recommended monthly, quarterly, and yearly setup.</p>
            </div>
        </div>

        <form method="post" class="mt-6 grid gap-4 lg:grid-cols-12">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="save_plan">

            <div class="lg:col-span-4">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Plan Name</label>
                <input type="text" name="name" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500" placeholder="Glow Monthly">
            </div>
            <div class="lg:col-span-3">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Slug</label>
                <input type="text" name="slug" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500" placeholder="glow-monthly">
            </div>
            <div class="lg:col-span-2">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Badge</label>
                <input type="text" name="badge_text" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500" placeholder="Most Popular">
            </div>
            <div class="lg:col-span-3">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Short Description</label>
                <input type="text" name="short_description" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500" placeholder="Best for regular shoppers">
            </div>

            <div class="lg:col-span-2">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Price</label>
                <input type="number" step="0.01" min="0.01" name="price" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500" placeholder="599">
            </div>
            <div class="lg:col-span-2">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Compare Price</label>
                <input type="number" step="0.01" min="0" name="compare_price" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500" placeholder="749">
            </div>
            <div class="lg:col-span-2">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Discount %</label>
                <input type="number" step="0.01" min="0" max="100" name="discount_percentage" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500" placeholder="10">
            </div>
            <div class="lg:col-span-2">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cycle</label>
                <select name="billing_cycle" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
                    <?php foreach (subscription_cycle_options() as $value => $label): ?>
                        <option value="<?= h($value) ?>"><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Validity Days</label>
                <input type="number" min="1" name="validity_days" value="30" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
            </div>
            <div class="lg:col-span-2">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Display Order</label>
                <input type="number" min="1" name="display_order" value="<?= max(1, $planCount + 1) ?>" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
            </div>

            <div class="lg:col-span-12">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Benefits</label>
                <textarea name="benefits_text" rows="5" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500" placeholder="One benefit per line"></textarea>
            </div>

            <div class="lg:col-span-12 flex flex-wrap gap-5 text-sm text-slate-600">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                    <span>Active</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_featured" value="1" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                    <span>Featured Plan</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="free_shipping" value="1" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                    <span>Free Shipping</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="auto_renew_enabled" value="1" checked class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                    <span>Auto Renew Flag</span>
                </label>
            </div>

            <div class="lg:col-span-12">
                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                    Create Plan
                </button>
            </div>
        </form>
    </div>

    <div class="space-y-6">
        <?php foreach ($plans as $plan): ?>
            <?php
                $cycleLabel = subscription_cycle_label($plan['billing_cycle'] ?? 'monthly');
                $cycleSuffix = subscription_cycle_suffix($plan['billing_cycle'] ?? 'monthly');
                $badgeText = trim((string)($plan['badge_text'] ?? ''));
            ?>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="max-w-3xl">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-2xl font-black text-slate-900"><?= h($plan['name']) ?></h2>
                            <?php if ($badgeText !== ''): ?>
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-700"><?= h($badgeText) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($plan['is_featured'])): ?>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700">Featured</span>
                            <?php endif; ?>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide <?= !empty($plan['is_active']) ? 'bg-sky-50 text-sky-700' : 'bg-slate-100 text-slate-500' ?>">
                                <?= !empty($plan['is_active']) ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>

                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            <?= h($plan['short_description'] ?: 'No short description set.') ?>
                        </p>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Price</div>
                                <div class="mt-2 text-2xl font-black text-slate-900">₹<?= number_format((float)$plan['price'], 0) ?><span class="text-sm font-semibold text-slate-500"><?= h($cycleSuffix) ?></span></div>
                                <?php if (!empty($plan['compare_price']) && (float)$plan['compare_price'] > (float)$plan['price']): ?>
                                    <div class="mt-1 text-sm text-slate-400 line-through">₹<?= number_format((float)$plan['compare_price'], 0) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Discount</div>
                                <div class="mt-2 text-2xl font-black text-slate-900"><?= number_format((float)$plan['discount_percentage'], 0) ?>%</div>
                                <div class="mt-1 text-sm text-slate-500"><?= h($cycleLabel) ?> plan</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Subscribers</div>
                                <div class="mt-2 text-2xl font-black text-slate-900"><?= (int)$plan['active_subscribers'] ?></div>
                                <div class="mt-1 text-sm text-slate-500">Active subscribers</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Plan Flags</div>
                                <div class="mt-2 text-sm font-semibold text-slate-900">
                                    <?= !empty($plan['free_shipping']) ? 'Free Shipping' : 'No Free Shipping' ?><br>
                                    <?= !empty($plan['auto_renew_enabled']) ? 'Auto Renew Flag On' : 'Auto Renew Flag Off' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="set_featured">
                            <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                            <button type="submit" class="rounded-2xl border border-emerald-300 px-4 py-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 transition">
                                Make Featured
                            </button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                            <input type="hidden" name="next_state" value="<?= !empty($plan['is_active']) ? 0 : 1 ?>">
                            <button type="submit" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                <?= !empty($plan['is_active']) ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <form method="post" class="mt-8 grid gap-4 lg:grid-cols-12">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="save_plan">
                    <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">

                    <div class="lg:col-span-4">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Plan Name</label>
                        <input type="text" name="name" value="<?= h($plan['name']) ?>" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
                    </div>
                    <div class="lg:col-span-3">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Slug</label>
                        <input type="text" name="slug" value="<?= h($plan['slug']) ?>" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Badge</label>
                        <input type="text" name="badge_text" value="<?= h($plan['badge_text']) ?>" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
                    </div>
                    <div class="lg:col-span-3">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Short Description</label>
                        <input type="text" name="short_description" value="<?= h($plan['short_description']) ?>" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
                    </div>

                    <div class="lg:col-span-2">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Price</label>
                        <input type="number" step="0.01" min="0.01" name="price" value="<?= h($plan['price']) ?>" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Compare Price</label>
                        <input type="number" step="0.01" min="0" name="compare_price" value="<?= h($plan['compare_price']) ?>" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Discount %</label>
                        <input type="number" step="0.01" min="0" max="100" name="discount_percentage" value="<?= h($plan['discount_percentage']) ?>" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cycle</label>
                        <select name="billing_cycle" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
                            <?php foreach (subscription_cycle_options() as $value => $label): ?>
                                <option value="<?= h($value) ?>" <?= ($value === ($plan['billing_cycle'] ?? '')) ? 'selected' : '' ?>><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Validity Days</label>
                        <input type="number" min="1" name="validity_days" value="<?= h($plan['validity_days']) ?>" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Display Order</label>
                        <input type="number" min="1" name="display_order" value="<?= h($plan['display_order']) ?>" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500">
                    </div>

                    <div class="lg:col-span-12">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Benefits</label>
                        <textarea name="benefits_text" rows="5" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-slate-500"><?= h($plan['benefits_text']) ?></textarea>
                    </div>

                    <div class="lg:col-span-12 flex flex-wrap gap-5 text-sm text-slate-600">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" <?= !empty($plan['is_active']) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                            <span>Active</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_featured" value="1" <?= !empty($plan['is_featured']) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                            <span>Featured</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="free_shipping" value="1" <?= !empty($plan['free_shipping']) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                            <span>Free Shipping</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="auto_renew_enabled" value="1" <?= !empty($plan['auto_renew_enabled']) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                            <span>Auto Renew Flag</span>
                        </label>
                    </div>

                    <div class="lg:col-span-12 flex items-center justify-between gap-4">
                        <div class="text-xs text-slate-500">
                            Slug: <span class="font-semibold text-slate-700"><?= h($plan['slug']) ?></span> |
                            Total subscriptions: <span class="font-semibold text-slate-700"><?= (int)$plan['total_subscriptions'] ?></span>
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
