<?php
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid Request']);
    exit;
}

// Get input (JSON or POST)
$input = json_decode(file_get_contents('php://input'), true);
$userMsg = strtolower(trim($input['message'] ?? $_POST['message'] ?? ''));

$response = [
    'type' => 'text',
    'message' => 'I am here to help! specific queries like "latest products" or "best sellers" work best.',
    'data' => []
];

try {
    // 1. Order Tracking (Specific ID)
    if (preg_match('/(ORD-\d+)/i', $userMsg, $matches)) {
        $orderId = strtoupper($matches[1]);
        $stmt = $pdo->prepare("SELECT order_number, status, total_amount, created_at FROM orders WHERE order_number = :ord LIMIT 1");
        $stmt->execute([':ord' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $status = ucfirst($order['status']);
            $date = date('d M Y', strtotime($order['created_at']));
            $response['message'] = "📦 **Order #{$order['order_number']}**\nStatus: **{$status}**\nPlaced on: {$date}\nAmount: ₹" . number_format($order['total_amount'], 2);
        } else {
            $response['message'] = "❌ I couldn't find Order #{$orderId}. Please check the ID and try again.";
        }
    }
    // 1b. Order Tracking (General Question)
    elseif (strpos($userMsg, 'track') !== false || strpos($userMsg, 'order status') !== false || strpos($userMsg, 'where is my order') !== false) {
        $response['message'] = "To track your order, please enter your **Order ID** (e.g., ORD-12345) here, or visit our [Tracking Page](track-order.php).";
    }

    // 2. Coupons & Offers
    elseif (strpos($userMsg, 'coupon') !== false || strpos($userMsg, 'offer') !== false || strpos($userMsg, 'discount') !== false || strpos($userMsg, 'promo') !== false) {
        $stmt = $pdo->prepare("SELECT code, description, discount_type, discount_value, min_purchase FROM coupons WHERE status = 'active' AND (valid_until IS NULL OR valid_until >= CURDATE()) ORDER BY discount_value DESC LIMIT 3");
        $stmt->execute();
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($coupons) {
            $msg = "🎉 **Active Offers:**\n";
            foreach($coupons as $c) {
                $val = ($c['discount_type'] == 'percentage') ? floatval($c['discount_value'])."% OFF" : "₹".floatval($c['discount_value'])." OFF";
                $msg .= "• **{$c['code']}**: $val ({$c['description']})\n";
            }
            $response['message'] = $msg;
        } else {
            $response['message'] = "Sorry, there are no active coupons at the moment. Please check back later!";
        }
    }

    // ================== CONSULTATIVE / EXPERT ANSWERS ==================

    // 2a. Certifications & Trust
    elseif (strpos($userMsg, 'certifi') !== false || strpos($userMsg, 'safe') !== false || strpos($userMsg, 'natural') !== false || strpos($userMsg, 'pure') !== false) {
        $response['message'] = "🏆 **Certified Purity**\nYes! We take quality seriously.\n\n• **AYUSH Premium Certified**\n• **GMP Certified** (Good Manufacturing Practice)\n• **ISO 9001:2015 Certified**\n\nOur products are 100% Natural, Safe, and Chemical-Free. 🌿";
    }

    // 2b. Freshness Promise
    elseif (strpos($userMsg, 'fresh') !== false || strpos($userMsg, 'expiry') !== false || strpos($userMsg, 'date') !== false || strpos($userMsg, 'batch') !== false) {
        $response['message'] = "🌿 **Freshness Promise**\nUnlike mass market brands, we don't store products for months.\n\nWe Create **Fresh Batches** for every order to ensure maximum potency and effectiveness! ✨";
    }

    // 2c. Subscriptions
    elseif (strpos($userMsg, 'subscri') !== false || strpos($userMsg, 'plan') !== false || strpos($userMsg, 'save') !== false || strpos($userMsg, 'month') !== false) {
        $response['message'] = "🔄 **Subscribe & Save**\nYes! You can subscribe to your favorite products and save money.\n\n• Get automated deliveries.\n• Cancel anytime.\n\n[View Subscription Plans](subscription.php)";
    }

    // 2d. SKIN EXPERT: Acne / Oily
    elseif (strpos($userMsg, 'acne') !== false || strpos($userMsg, 'pimple') !== false || strpos($userMsg, 'oil') !== false || strpos($userMsg, 'face wash') !== false) {
        $response['message'] = "💧 **For Acne & Oily Skin**\nI recommend our Neem & Tulsi range. It purifies skin and controls oil naturally.\n\nCheck out these matching products:";
        // Fetch acne products dynamically
        $stmt = $pdo->prepare("SELECT id, name, price, images FROM products WHERE is_active = 1 AND (name LIKE '%neem%' OR name LIKE '%tulsi%' OR name LIKE '%acne%') LIMIT 5");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($products) {
            $response['type'] = 'products';
            $response['data'] = formatProducts($products);
        } else {
            // Fallback link if no specific product found
             $response['message'] .= "\n\n[View Acne Collection](search.php?q=acne)";
        }
    }

    // 2e. SKIN EXPERT: Glow / Brightening
    elseif (strpos($userMsg, 'glow') !== false || strpos($userMsg, 'bright') !== false || strpos($userMsg, 'dull') !== false || strpos($userMsg, 'white') !== false || strpos($userMsg, 'tan') !== false) {
        $response['message'] = "✨ **For Glowing Skin**\nOur Kumkumadi and Saffron range is perfect for brightening and removing tan.\n\nTop picks for you:";
        $stmt = $pdo->prepare("SELECT id, name, price, images FROM products WHERE is_active = 1 AND (name LIKE '%kumkumadi%' OR name LIKE '%saffron%' OR name LIKE '%glow%') LIMIT 5");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($products) {
            $response['type'] = 'products';
            $response['data'] = formatProducts($products);
        }
    }

    // 2f. HAIR EXPERT: Hair Fall / Growth
    elseif (strpos($userMsg, 'hair') !== false || strpos($userMsg, 'fall') !== false || strpos($userMsg, 'dandruff') !== false || strpos($userMsg, 'growth') !== false) {
        $response['message'] = "💆‍♀️ **Hair Care Solutions**\nOur secret herbal oil blend stops hair fall and promotes regrowth.\n\nBest sellers for Hair:";
        $stmt = $pdo->prepare("SELECT id, name, price, images FROM products WHERE is_active = 1 AND (name LIKE '%hair%' OR name LIKE '%oil%' OR name LIKE '%shampoo%') LIMIT 5");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($products) {
            $response['type'] = 'products';
            $response['data'] = formatProducts($products);
        }
    }

    // 2g. Payments
    elseif (strpos($userMsg, 'pay') !== false || strpos($userMsg, 'cod') !== false || strpos($userMsg, 'card') !== false || strpos($userMsg, 'upi') !== false) {
        $response['message'] = "💳 **Payment Options**\nWe accept:\n• **UPI** (GPay, PhonePe)\n• **Credit/Debit Cards**\n• **Netbanking**\n\n✅ **Cash on Delivery (COD)** is available for select pin codes.";
    }

    // ===================================================================

    // 3. Late/New Products (Existing)
    elseif (strpos($userMsg, 'latest') !== false || strpos($userMsg, 'new') !== false || strpos($userMsg, 'recent') !== false) {
        $stmt = $pdo->prepare("SELECT id, name, price, images FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($products) {
            $response['type'] = 'products';
            $response['message'] = 'Here are our latest arrivals:';
            $response['data'] = formatProducts($products);
        } else {
            $response['message'] = "I couldn't find any new products at the moment.";
        }
    }

    // 4. Best Sellers (Existing)
    elseif (strpos($userMsg, 'best') !== false || strpos($userMsg, 'sell') !== false || strpos($userMsg, 'popular') !== false) {
        // Assuming 'sold_count' column exists based on product.php
        $stmt = $pdo->prepare("SELECT id, name, price, images FROM products WHERE is_active = 1 ORDER BY sold_count DESC LIMIT 5");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($products) {
            $response['type'] = 'products';
            $response['message'] = 'Here are our most popular items:';
            $response['data'] = formatProducts($products);
        } else {
            $response['message'] = "I couldn't retrieve the best sellers right now.";
        }
    }

    // 5. Store Policies (Refunds, Returns, Shipping)
    elseif (strpos($userMsg, 'refund') !== false || strpos($userMsg, 'return') !== false || strpos($userMsg, 'cancel') !== false) {
        $response['message'] = "🔄 **Return & Refund Policy**\nWe have a 7-day return policy for damaged or incorrect items. \n\nTo initiate a return or cancellation, please contact our support at [sales@develixirs.com](mailto:sales@develixirs.com) or use the WhatsApp button below.";
        $response['type'] = 'contact_agent'; // Offers WhatsApp link
    }
    elseif (strpos($userMsg, 'shipping') !== false || strpos($userMsg, 'delivery') !== false || strpos($userMsg, 'deliver') !== false || strpos($userMsg, 'time') !== false) {
        $response['message'] = "🚚 **Shipping Info**\n• **Domestic (India):** 3-5 business days.\n• **International:** 7-14 business days.\n• **Free Shipping** on orders above ₹999!";
    }

    // 6. About / General Info
    elseif (strpos($userMsg, 'about') !== false || strpos($userMsg, 'who are you') !== false || strpos($userMsg, 'location') !== false) {
        $response['message'] = "🌿 **About DevElixir**\nWe provide pure, natural, and effective skincare solutions. \n\n📍 **Location:** Gudiyatham, Vellore, Tamilnadu, INDIA.\n📧 **Email:** sales@develixirs.com";
    }

    // 7. Contact
    elseif (strpos($userMsg, 'contact') !== false || strpos($userMsg, 'support') !== false || strpos($userMsg, 'help') !== false || strpos($userMsg, 'call') !== false) {
        $response['message'] = "📞 **Contact Us**\n• **Phone:** +91 95006 50454\n• **Email:** sales@develixirs.com\n\nOr click below to chat on WhatsApp!";
        $response['type'] = 'contact_agent';
    }

    // 8. Dynamic Knowledge Base (Pages & Blogs) - The "Know Everything" Layer
    else {
        $foundAnswer = false;
        $searchTerm = '%' . $userMsg . '%';
        
        // A. Search Pages
        try {
            $stmtPage = $pdo->prepare("SELECT title, slug, meta_description FROM pages WHERE status='published' AND (title LIKE :q OR content LIKE :q) LIMIT 1");
            $stmtPage->execute([':q' => $searchTerm]);
            $page = $stmtPage->fetch(PDO::FETCH_ASSOC);
            
            if ($page) {
                $desc = !empty($page['meta_description']) ? $page['meta_description'] : "Read more about " . $page['title'];
                $response['message'] = "📄 **I found this on our website:**\n\n**{$page['title']}**\n{$desc}\n\n[Read More](page.php?slug={$page['slug']})";
                $foundAnswer = true;
            }
        } catch (Exception $e) { /* Ignore table errors */ }
        
        // B. Search Blogs (if no page found)
        if (!$foundAnswer) {
             try {
                $stmtBlog = $pdo->prepare("SELECT title, slug, short_description FROM blogs WHERE status='published' AND (title LIKE :q OR content LIKE :q) LIMIT 1");
                $stmtBlog->execute([':q' => $searchTerm]);
                $blog = $stmtBlog->fetch(PDO::FETCH_ASSOC);
                
                if ($blog) {
                    $desc = !empty($blog['short_description']) ? $blog['short_description'] : "Read our article on " . $blog['title'];
                    $response['message'] = "📝 **I found an article about that:**\n\n**{$blog['title']}**\n{$desc}\n\n[Read Article](blog_view.php?slug={$blog['slug']})";
                    $foundAnswer = true;
                }
            } catch (Exception $e) { /* Ignore table errors */ }
        }

        // C. Fallback (If nothing found in DB)
        if (!$foundAnswer) {
            // Check if it's a greeting
            $greetings = ['hi', 'hello', 'hey', 'start', 'good morning', 'good evening'];
            $foundGreeting = false;
            foreach($greetings as $g) {
                if (strpos($userMsg, $g) !== false && strlen($userMsg) < 15) {
                    $foundGreeting = true; 
                    break;
                }
            }
            
            if ($foundGreeting) {
                 // Fetch welcome msg from DB or default
                 $msg = "Hi there! 👋 Welcome to DevElixir.\n\nI can help you with:\n• **Tracking Orders** (e.g. 'Track ORD-123')\n• **Finding Products** ('Latest', 'Best Sellers')\n• **Coupons**\n• **Shipping & Returns**\n• **Any other questions!**";
                 $response['message'] = $msg;
            } else {
                $response['type'] = 'contact_agent'; // Keep this for fallbacks so user can switch to human
                $response['message'] = "I'm not sure about that. Connect with our expert on WhatsApp for detailed assistance.";
            }
        }
    }

} catch (Exception $e) {
    $response['message'] = "Sorry, I encountered an error. Please try again.";
    error_log("Chatbot Error: " . $e->getMessage());
}

echo json_encode($response);

// Helper to format images
function formatProducts($products) {
    $formatted = [];
    foreach ($products as $p) {
        $img = $p['images'];
        // logic from product.php simplified
        if (strpos($img, ',') !== false) {
            $parts = explode(',', $img);
            $img = trim($parts[0]);
        }
        // Basic cleanup
        $img = trim($img);
        if (empty($img)) $img = 'assets/images/category-placeholder.jpg';
        // Ensure path (if not http)
        if (!preg_match('#^https?://#i', $img) && strpos($img, '/') !== 0) {
             $img = 'assets/uploads/products/' . $img;
        }
        
        $formatted[] = [
            'id' => $p['id'],
            'name' => $p['name'],
            'price' => number_format($p['price'], 2),
            'image' => $img,
            'url' => 'product_view.php?id=' . $p['id']
        ];
    }
    return $formatted;
}
