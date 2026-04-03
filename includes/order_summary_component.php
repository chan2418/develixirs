<?php

if (!function_exists('order_summary_format_money')) {
    function order_summary_format_money(float $value): string
    {
        return number_format($value, 2);
    }
}

if (!function_exists('order_summary_format_rate')) {
    function order_summary_format_rate(float $value): string
    {
        $formatted = number_format($value, 2);
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        return $formatted === '' ? '0' : $formatted;
    }
}

if (!function_exists('order_summary_max_gst_rate')) {
    function order_summary_max_gst_rate(PDO $pdo, array $items): float
    {
        $ids = [];
        foreach ($items as $item) {
            $productId = isset($item['product_id']) ? (int)$item['product_id'] : (isset($item['id']) ? (int)$item['id'] : 0);
            if ($productId > 0) {
                $ids[] = $productId;
            }
        }

        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return 0.0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT MAX(COALESCE(gst_rate, 0)) FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $rate = (float)$stmt->fetchColumn();

        return max(0.0, round($rate, 2));
    }
}

if (!function_exists('order_summary_resolve_values')) {
    function order_summary_resolve_values(array $props): array
    {
        $productsTotal = round(max(0, (float)($props['productsTotal'] ?? 0)), 2);
        $shipping = round(max(0, (float)($props['shipping'] ?? 0)), 2);
        $discount = round(max(0, (float)($props['discount'] ?? 0)), 2);

        $grandTotal = isset($props['grandTotal'])
            ? round(max(0, (float)$props['grandTotal']), 2)
            : round(max(0, $productsTotal + $shipping - $discount), 2);

        $gstRate = round(max(0, (float)($props['gstRate'] ?? 0)), 2);
        $gstTypeRaw = strtoupper(trim((string)($props['gstType'] ?? 'IGST')));
        $gstType = ($gstTypeRaw === 'CGST+SGST') ? 'CGST+SGST' : 'IGST';

        $taxableValue = isset($props['taxableValue'])
            ? round(max(0, (float)$props['taxableValue']), 2)
            : ($gstRate > 0 ? round($grandTotal / (1 + ($gstRate / 100)), 2) : $grandTotal);

        if ($gstType === 'CGST+SGST') {
            $cgst = array_key_exists('cgst', $props)
                ? round(max(0, (float)$props['cgst']), 2)
                : round(($grandTotal - $taxableValue) / 2, 2);
            $sgst = array_key_exists('sgst', $props)
                ? round(max(0, (float)$props['sgst']), 2)
                : round(($grandTotal - $taxableValue) - $cgst, 2);
            $igst = 0.0;
        } else {
            $igst = array_key_exists('igst', $props)
                ? round(max(0, (float)$props['igst']), 2)
                : round(max(0, $grandTotal - $taxableValue), 2);
            $cgst = 0.0;
            $sgst = 0.0;
        }

        $showDiscountRow = !empty($props['showDiscountWhenZero']) || $discount > 0;
        $shippingDisplay = $shipping > 0 ? '₹' . order_summary_format_money($shipping) : 'FREE';
        $halfRate = round($gstRate / 2, 2);

        return [
            'productsTotal' => $productsTotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'grandTotal' => $grandTotal,
            'taxableValue' => $taxableValue,
            'gstRate' => $gstRate,
            'gstType' => $gstType,
            'igst' => $igst,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'showDiscountRow' => $showDiscountRow,
            'shippingDisplay' => $shippingDisplay,
            'halfRate' => $halfRate,
        ];
    }
}

if (!function_exists('render_order_summary_component')) {
    function render_order_summary_component(array $props): void
    {
        static $stylePrinted = false;
        $values = order_summary_resolve_values($props);
        $title = (string)($props['title'] ?? 'Order Summary');

        if (!$stylePrinted) {
            $stylePrinted = true;
            echo '<style>
                .os-card {
                    background: #fff;
                    border: 1px solid #ececec;
                    border-radius: 10px;
                    padding: 18px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                }
                .price-details .os-card {
                    border: none;
                    border-radius: 0;
                    box-shadow: none;
                    padding: 0;
                }
                .os-title {
                    font-size: 17px;
                    font-weight: 700;
                    color: #1f2937;
                    margin-bottom: 10px;
                }
                .os-divider {
                    border-top: 1px solid #e7e7e7;
                    margin: 8px 0;
                }
                .os-row {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                    padding: 6px 0;
                    font-size: 15px;
                    color: #222;
                }
                .os-row .os-amount {
                    text-align: right;
                    min-width: 110px;
                    font-variant-numeric: tabular-nums;
                }
                .os-grand {
                    background: #f7f8fb;
                    border-radius: 8px;
                    padding: 9px 10px;
                    font-size: 18px;
                    font-weight: 800;
                    color: #111827;
                }
                .os-tax-title {
                    font-size: 12px;
                    color: #6b7280;
                    font-weight: 600;
                    margin-top: 2px;
                }
                .os-subrow {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                    padding: 4px 0;
                    font-size: 13px;
                    color: #6b7280;
                }
                .os-subrow .os-amount {
                    text-align: right;
                    min-width: 110px;
                    font-variant-numeric: tabular-nums;
                }
                .os-discount {
                    color: #0f766e;
                }
            </style>';
        }

        echo '<div class="os-card">';
        echo '<div class="os-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '<div class="os-divider"></div>';

        echo '<div class="os-row"><span>Products Total</span><span class="os-amount">₹' . order_summary_format_money($values['productsTotal']) . '</span></div>';
        echo '<div class="os-row"><span>Shipping</span><span class="os-amount">' . $values['shippingDisplay'] . '</span></div>';
        if ($values['showDiscountRow']) {
            echo '<div class="os-row os-discount"><span>Discount</span><span class="os-amount">- ₹' . order_summary_format_money($values['discount']) . '</span></div>';
        }

        echo '<div class="os-divider"></div>';
        echo '<div class="os-row os-grand"><span>Grand Total</span><span class="os-amount">₹' . order_summary_format_money($values['grandTotal']) . '</span></div>';
        echo '<div class="os-divider"></div>';

        echo '<div class="os-tax-title">Tax Breakup (Included in above)</div>';
        echo '<div class="os-subrow"><span>Taxable Value</span><span class="os-amount">₹' . order_summary_format_money($values['taxableValue']) . '</span></div>';
        if ($values['gstType'] === 'CGST+SGST') {
            echo '<div class="os-subrow"><span>CGST @ ' . order_summary_format_rate($values['halfRate']) . '%</span><span class="os-amount">₹' . order_summary_format_money($values['cgst']) . '</span></div>';
            echo '<div class="os-subrow"><span>SGST @ ' . order_summary_format_rate($values['halfRate']) . '%</span><span class="os-amount">₹' . order_summary_format_money($values['sgst']) . '</span></div>';
        } else {
            echo '<div class="os-subrow"><span>IGST @ ' . order_summary_format_rate($values['gstRate']) . '%</span><span class="os-amount">₹' . order_summary_format_money($values['igst']) . '</span></div>';
        }
        echo '<div class="os-divider"></div>';
        echo '</div>';
    }
}
