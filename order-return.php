<?php
require_once __DIR__ . '/includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId  = $_SESSION['user_id'];
$error   = '';
$success = '';

// 1. Fetch Order & Validate
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found or access denied.");
}

// 2. Validate Time (7 Days)
$created  = strtotime($order['created_at']);
$diffDays = (time() - $created) / (60 * 60 * 24);

if ($diffDays > 7) {
    die("Return window closed (more than 7 days).");
}

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    
    // Image Upload Handling
    $imagePaths = [];
    $uploadDir = __DIR__ . '/assets/uploads/returns/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] === 0) {
                $tmpName = $_FILES['images']['tmp_name'][$key];
                $ext     = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    $newName = 'return_' . $orderId . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                        $imagePaths[] = $newName;
                    }
                }
            }
        }
    }
    
    if (empty($reason)) {
        $error = "Please provide a reason for the return.";
    } else {
        try {
            // Check existing return
            $stmtCheck = $pdo->prepare("SELECT id FROM order_returns WHERE order_id = ?");
            $stmtCheck->execute([$orderId]);
            if ($stmtCheck->fetch()) {
                 $error = "A return request has already been submitted for this order.";
            } else {
                // Insert Return Request
                $stmtInsert = $pdo->prepare("
                    INSERT INTO order_returns (order_id, user_id, reason, images, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $imageJson = json_encode($imagePaths);
                $stmtInsert->execute([$orderId, $userId, $reason, $imageJson]);
                
                // ADD NOTIFICATION
                try {
                    $notifTitle = "New Return Request: Order #" . $order['order_number'];
                    $notifMsg = "User ID $userId requested a return for Order #" . $order['order_number'] . ".\nReason: " . substr($reason, 0, 50) . "...";
                    $notifUrl = "order_returns.php?status=pending"; // Link to new admin page
                    
                    $stmtNotif = $pdo->prepare("INSERT INTO notifications (title, message, url, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
                    $stmtNotif->execute([$notifTitle, $notifMsg, $notifUrl]);
                } catch (Exception $e) {
                    // Ignore notification error to not block user flow
                }
                
                header("Location: order-details.php?id=$orderId");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Return Request | Order #<?php echo htmlspecialchars($order['order_number']); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/navbar.css">
  <script src="https://cdn.tailwindcss.com"></script>
  
  <style>
    body { font-family: 'Outfit', sans-serif; background: #fdfbf7; color: #1f2937; }
    .gold-text { color: #D4AF37; }
    .gold-bg { background-color: #D4AF37; }
    .gold-border { border-color: #D4AF37; }
    
    /* Custom Scrollbar for textarea */
    textarea::-webkit-scrollbar { width: 8px; }
    textarea::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 4px; }
    textarea::-webkit-scrollbar-thumb:hover { background-color: #d1d5db; }
  </style>
</head>
<body class="flex flex-col min-h-screen">

<?php include __DIR__ . '/navbar.php'; ?>

<main class="flex-grow container mx-auto px-4 py-8 max-w-3xl">
    
    <!-- Breadcrumb / Back -->
    <div class="mb-6">
        <a href="order-details.php?id=<?php echo $orderId; ?>" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-800 transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back to Order Details
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden relative">
        <!-- Top accent -->
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-yellow-600 via-yellow-400 to-yellow-600"></div>

        <div class="p-8 md:p-10">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-50 text-yellow-600 mb-4 ring-8 ring-yellow-50/50">
                    <i class="fa-solid fa-box-open text-2xl"></i>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Request a Return</h1>
                <p class="text-gray-500">
                    Please provide the details below to initiate your return request.
                </p>
                <div class="mt-3 inline-block bg-gray-50 px-3 py-1 rounded text-sm text-gray-600 font-medium">
                    Order #<?php echo htmlspecialchars($order['order_number']); ?>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg" role="alert">
                    <p class="font-bold">Error</p>
                    <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <!-- Reason -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Reason for Return <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <textarea 
                            name="reason" 
                            rows="5" 
                            class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-transparent outline-none transition-all resize-none text-gray-700 placeholder-gray-400"
                            placeholder="Please describe strictly what happened. E.g. 'Received damaged product', 'Wrong item delivered', etc."
                            required></textarea>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Please provide as much detail as possible to speed up the approval process.</p>
                </div>

                <!-- Proof Upload -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Upload Proof (Optional but Recommended)
                    </label>
                    
                    <div id="drop-area" class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-yellow-400 transition-colors cursor-pointer bg-gray-50 hover:bg-yellow-50/30 group">
                        <input type="file" name="images[]" id="fileElem" multiple accept="image/*" class="hidden" onchange="handleFiles(this.files)">
                        
                        <div class="flex flex-col items-center justify-center space-y-2 pointer-events-none">
                            <div class="p-3 bg-white rounded-full shadow-sm group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-cloud-arrow-up text-xl text-yellow-500"></i>
                            </div>
                            <span class="font-medium text-gray-600 group-hover:text-yellow-700">Click to upload photos</span>
                            <span class="text-xs text-gray-400">JPG, PNG, WEBP up to 5MB</span>
                        </div>
                    </div>

                    <!-- Preview Container -->
                    <div id="gallery" class="grid grid-cols-4 gap-3 mt-4"></div>
                </div>

                <!-- Actions -->
                <div class="pt-6 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
                    <a href="order-details.php?id=<?php echo $orderId; ?>" class="w-full sm:w-auto px-6 py-3 rounded-xl border border-gray-200 text-gray-600 font-medium hover:bg-gray-50 hover:text-gray-900 transition text-center">
                        Cancel
                    </a>
                    <button type="submit" class="w-full sm:w-auto px-8 py-3 rounded-xl bg-gradient-to-r from-yellow-600 to-yellow-500 text-white font-bold shadow-lg shadow-yellow-500/30 hover:shadow-yellow-500/50 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                        <span>Submit Request</span>
                        <i class="fa-solid fa-paper-plane text-sm"></i>
                    </button>
                </div>

            </form>
        </div>
    </div>
    
    <!-- Help Text -->
    <div class="mt-8 text-center text-sm text-gray-500 max-w-md mx-auto">
        <p>Need immediate assistance?</p>
        <a href="https://wa.me/919500650454" target="_blank" class="font-semibold text-yellow-600 hover:text-yellow-700 flex items-center justify-center gap-1.5 mt-1">
            <i class="fa-brands fa-whatsapp"></i> Chat with Support
        </a>
    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
    // Trigger file input when clicking the drop area
    document.getElementById('drop-area').addEventListener('click', () => {
        document.getElementById('fileElem').click();
    });

    function handleFiles(files) {
        const gallery = document.getElementById('gallery');
        gallery.innerHTML = ''; // Clear preview
        
        if (!files.length) return;

        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onloadend = function() {
                const div = document.createElement('div');
                div.className = 'relative group aspect-square rounded-lg overflow-hidden border border-gray-200 shadow-sm';
                
                const img = document.createElement('img');
                img.src = reader.result;
                img.className = 'w-full h-full object-cover';
                
                // Add overlay/check
                const overlay = document.createElement('div');
                overlay.className = 'absolute inset-0 bg-black/20 hidden group-hover:flex items-center justify-center';
                overlay.innerHTML = '<i class="fa-solid fa-check text-white drop-shadow-md"></i>';

                div.appendChild(img);
                div.appendChild(overlay);
                gallery.appendChild(div);
            }
        });
    }

    // Drag and drop prevent default
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        document.getElementById('drop-area').addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Highlight drop area
    ['dragenter', 'dragover'].forEach(eventName => {
        document.getElementById('drop-area').addEventListener(eventName, () => {
            document.getElementById('drop-area').classList.add('border-yellow-400', 'bg-yellow-50');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        document.getElementById('drop-area').addEventListener(eventName, () => {
            document.getElementById('drop-area').classList.remove('border-yellow-400', 'bg-yellow-50');
        }, false);
    });
    
    // Handle drop
    document.getElementById('drop-area').addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        document.getElementById('fileElem').files = files; // Assign dropped files to input
        handleFiles(files);
    }, false);

</script>

</body>
</html>
