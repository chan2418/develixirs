<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: my-profile.php#section-addresses");
  exit;
}

$userId   = $_SESSION['user_id'];
$fullName = trim($_POST['full_name'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$addr1    = trim($_POST['address_line1'] ?? '');
$addr2    = trim($_POST['address_line2'] ?? '');
$city     = trim($_POST['city'] ?? '');
$state    = trim($_POST['state'] ?? '');
$pincode  = trim($_POST['pincode'] ?? '');
$isDefault = isset($_POST['is_default']) ? 1 : 0;

if ($fullName === '' || $phone === '' || $addr1 === '' || $city === '' || $state === '' || $pincode === '') {
  header("Location: my-profile.php#section-addresses");
  exit;
}

try {
  $pdo->beginTransaction();

  if ($isDefault) {
    $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
    $stmt->execute([$userId]);
  }

  $stmt = $pdo->prepare("
    INSERT INTO user_addresses
    (user_id, full_name, phone, address_line1, address_line2, city, state, pincode, is_default)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");

  $stmt->execute([
    $userId,
    $fullName,
    $phone,
    $addr1,
    $addr2,
    $city,
    $state,
    $pincode,
    $isDefault
  ]);

  $pdo->commit();

} catch (Exception $e) {
  $pdo->rollBack();
  error_log('Add address error: ' . $e->getMessage());
}

header("Location: my-profile.php#section-addresses");
exit;
