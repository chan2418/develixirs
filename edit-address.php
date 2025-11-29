<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$addressId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch the address to edit
$address = null;
if ($addressId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $address = null;
    }
}

if (!$address) {
    header("Location: my-profile.php?tab=addresses");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $addressLine1 = trim($_POST['address_line1'] ?? '');
    $addressLine2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    
    if (empty($fullName) || empty($phone) || empty($addressLine1) || empty($city) || empty($state) || empty($pincode)) {
        $_SESSION['error_message'] = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE user_addresses 
                SET full_name = ?, phone = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, pincode = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$fullName, $phone, $addressLine1, $addressLine2, $city, $state, $pincode, $addressId, $userId]);
            
            $_SESSION['success_message'] = 'Address updated successfully!';
            header("Location: my-profile.php?tab=addresses");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Error updating address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Address - Devilixirs</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Poppins", sans-serif; background: #f5f5f5; }
        .container { max-width: 600px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { font-size: 24px; margin-bottom: 20px; color: #1a1a1a; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 14px; color: #333; }
        input, textarea { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; font-family: "Poppins", sans-serif; }
        input:focus, textarea:focus { outline: none; border-color: #D4AF37; }
        .btn { padding: 14px 24px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; font-family: "Poppins", sans-serif; transition: all 0.2s; }
        .btn-primary { background: #D4AF37; color: #fff; }
        .btn-primary:hover { background: #B89026; }
        .btn-secondary { background: #f5f5f5; color: #666; }
        .btn-secondary:hover { background: #e0e0e0; }
        .button-group { display: flex; gap: 12px; margin-top: 30px; }
        .error { background: #fff0f0; color: #d63333; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fa-solid fa-location-dot"></i> Edit Address</h1>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($address['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Phone Number *</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($address['phone']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Address Line 1 *</label>
                <input type="text" name="address_line1" value="<?php echo htmlspecialchars($address['address_line1']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Address Line 2</label>
                <input type="text" name="address_line2" value="<?php echo htmlspecialchars($address['address_line2'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>City *</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($address['city']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>State *</label>
                <input type="text" name="state" value="<?php echo htmlspecialchars($address['state']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Pincode *</label>
                <input type="text" name="pincode" value="<?php echo htmlspecialchars($address['pincode']); ?>" required>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='my-profile.php?tab=addresses'">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>
