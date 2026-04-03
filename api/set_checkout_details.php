<?php
if (ob_get_level() === 0) {
    ob_start();
}

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

function send_json_response(array $payload): void
{
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    send_json_response([
        'success' => false,
        'message' => 'Please login to continue.'
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}

$userId = (int)$_SESSION['user_id'];
$addressId = (int)($_POST['address_id'] ?? 0);
$gstNumber = strtoupper(trim((string)($_POST['gst_number'] ?? '')));
$gstNumber = preg_replace('/\s+/', '', $gstNumber);

if ($addressId <= 0) {
    send_json_response([
        'success' => false,
        'message' => 'Please select a valid delivery address.'
    ]);
}

if ($gstNumber !== '' && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstNumber)) {
    send_json_response([
        'success' => false,
        'message' => 'GST number format is invalid.'
    ]);
}

try {
    $stmt = $pdo->prepare("
        SELECT id, full_name, phone, address_line1, address_line2, city, state, pincode
        FROM user_addresses
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$addressId, $userId]);
    $addr = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$addr) {
        send_json_response([
            'success' => false,
            'message' => 'Selected address was not found.'
        ]);
    }

    $lines = [];
    if (!empty($addr['full_name'])) {
        $lines[] = trim((string)$addr['full_name']);
    }
    if (!empty($addr['address_line1'])) {
        $lines[] = trim((string)$addr['address_line1']);
    }
    if (!empty($addr['address_line2'])) {
        $lines[] = trim((string)$addr['address_line2']);
    }

    $cityState = implode(', ', array_filter([
        trim((string)($addr['city'] ?? '')),
        trim((string)($addr['state'] ?? ''))
    ]));
    $pincode = trim((string)($addr['pincode'] ?? ''));
    if ($cityState !== '' || $pincode !== '') {
        $cityStateLine = trim($cityState . ($pincode !== '' ? ' - ' . $pincode : ''));
        $lines[] = $cityStateLine;
    }

    if (!empty($addr['phone'])) {
        $lines[] = 'Phone: ' . trim((string)$addr['phone']);
    }

    if ($gstNumber !== '') {
        $lines[] = 'GSTIN: ' . $gstNumber;
    }

    $shippingAddress = trim(implode("\n", $lines));

    $_SESSION['selected_address_id'] = $addressId;
    $_SESSION['shipping_address'] = $shippingAddress;

    if ($gstNumber !== '') {
        $_SESSION['gst_number'] = $gstNumber;
    } else {
        unset($_SESSION['gst_number']);
    }

    send_json_response([
        'success' => true,
        'message' => 'Delivery details saved.'
    ]);
} catch (Throwable $e) {
    send_json_response([
        'success' => false,
        'message' => 'Unable to save delivery details.'
    ]);
}
