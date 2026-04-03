<?php
// page.php - Frontend Page Renderer
require_once __DIR__ . '/includes/db.php';
session_start();

$slug = $_GET['slug'] ?? '';
$preview = isset($_GET['preview']) && isset($_SESSION['user_id']); // Allow preview for logged in admin

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    echo "Page not found";
    exit;
}

// Fetch Page
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ?");
$stmt->execute([$slug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    header("HTTP/1.0 404 Not Found");
    include '404.php'; // Assuming you have one, or just echo error
    exit;
}

// Check visibility
if ($page['status'] !== 'published' && !$preview) {
    header("HTTP/1.0 404 Not Found");
    echo "Page not found (Draft)";
    exit;
}

// Decode Content
$blocks = json_decode($page['content'], true) ?: [];

// Helper to render blocks
function render_block($block) {
    $type = $block['type'];
    $data = $block['data'];

    switch ($type) {
        case 'hero':
            $bg = !empty($data['bg_image']) ? 'background-image: url('.htmlspecialchars($data['bg_image']).');' : 'background-color: #f7fafc;';
            echo '<section class="relative py-20 px-6 bg-cover bg-center" style="'.$bg.'">';
            echo '  <div class="absolute inset-0 bg-black opacity-40"></div>';
            echo '  <div class="relative max-w-4xl mx-auto text-center text-white">';
            if(!empty($data['heading'])) echo '<h1 class="text-4xl md:text-5xl font-bold mb-4">'.htmlspecialchars($data['heading']).'</h1>';
            if(!empty($data['subheading'])) echo '<p class="text-lg md:text-xl opacity-90 mb-8">'.htmlspecialchars($data['subheading']).'</p>';
            if(!empty($data['cta_text'])) {
                 echo '<a href="'.htmlspecialchars($data['cta_link'] ?? '#').'" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-8 py-3 rounded-full transition-colors">'.htmlspecialchars($data['cta_text']).'</a>';
            }
            echo '  </div>';
            echo '</section>';
            break;

        case 'text':
            echo '<section class="py-12 px-6">';
            echo '  <div class="max-w-4xl mx-auto prose prose-lg text-gray-700">';
            echo      $data['content']; // Allow HTML (Authorized admin content)
            echo '  </div>';
            echo '</section>';
            break;

        case 'image_text':
            $order = ($data['position'] ?? 'left') === 'right' ? 'md:order-2' : '';
            echo '<section class="py-12 px-6 bg-white">';
            echo '  <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-12 items-center">';
            echo '    <div class="'.$order.'">';
            if(!empty($data['image'])) echo '<img src="'.htmlspecialchars($data['image']).'" class="rounded-lg shadow-lg w-full object-cover" alt="">';
            echo '    </div>';
            echo '    <div>';
            echo '      <div class="prose text-gray-700">'.$data['content'].'</div>';
            echo '    </div>';
            echo '  </div>';
            echo '</section>';
            break;

        case 'faq':
            echo '<section class="py-12 px-6 bg-gray-50">';
            echo '  <div class="max-w-3xl mx-auto">';
            echo '    <h2 class="text-3xl font-bold text-center mb-8 text-gray-800">Frequently Asked Questions</h2>';
            echo '    <div class="space-y-4">';
            if (!empty($data['faqs'])) {
                foreach ($data['faqs'] as $idx => $faq) {
                    echo '<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">';
                    echo '  <details class="group">';
                    echo '    <summary class="flex justify-between items-center font-medium cursor-pointer list-none p-4 text-gray-800 bg-gray-50 hover:bg-gray-100 transition">';
                    echo        '<span>'.htmlspecialchars($faq['question']).'</span>';
                    echo        '<span class="transition group-open:rotate-180">';
                    echo          '<svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>';
                    echo        '</span>';
                    echo '    </summary>';
                    echo '    <div class="text-gray-600 p-4 border-t border-gray-100 group-open:animate-fadeIn">';
                    echo         nl2br(htmlspecialchars($faq['answer']));
                    echo '    </div>';
                    echo '  </details>';
                    echo '</div>';
                }
            }
            echo '    </div>';
            echo '  </div>';
            echo '</section>';
            break;

        case 'form':
            $formType = $data['form_type'] ?? 'contact';
            $btnLabel = $data['btn_label'] ?? 'Send Message';
            $recipient = $data['recipient_email'] ?? '';
            $successMsg = $data['success_msg'] ?? 'Thank you for your message.';
            
            // Check if success param is present
            $isSuccess = isset($_GET['success']) && $_GET['success'] == 1;

            echo '<section class="py-12 px-6 bg-white">';
            echo '  <div class="max-w-2xl mx-auto">';
            
            if ($isSuccess) {
                echo '    <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg text-center mb-8">';
                echo '      <i class="fa-solid fa-check-circle text-2xl mb-2"></i>';
                echo '      <p class="font-medium text-lg">'.htmlspecialchars($successMsg).'</p>';
                echo '    </div>';
            }

            echo '    <form action="/handlers/submit_form.php" method="POST" class="bg-white rounded-xl shadow-lg border border-gray-100 p-8">';
            echo '      <input type="hidden" name="return_url" value="'.htmlspecialchars($_SERVER['REQUEST_URI']).'">';
            echo '      <input type="hidden" name="form_type" value="'.htmlspecialchars($formType).'">';
             if (!empty($recipient)) {
                 echo '      <input type="hidden" name="recipient" value="'.htmlspecialchars($recipient).'">';
             }
            
            echo '      <h3 class="text-2xl font-bold text-gray-800 mb-6 font-poppins">Contact Us</h3>';
            
            echo '      <div class="mb-5">';
            echo '          <label class="block text-sm font-medium text-gray-700 mb-2">Your Name</label>';
            echo '          <input type="text" name="name" required class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition outline-none" placeholder="John Doe">';
            echo '      </div>';

            echo '      <div class="mb-5">';
            echo '          <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>';
            echo '          <input type="email" name="email" required class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition outline-none" placeholder="john@example.com">';
            echo '      </div>';

            echo '      <div class="mb-6">';
            echo '          <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>';
            echo '          <textarea name="message" required rows="4" class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition outline-none" placeholder="How can we help?"></textarea>';
            echo '      </div>';

            echo '      <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-lg shadow-md hover:shadow-lg transition transform active:scale-95">';
            echo '          '.htmlspecialchars($btnLabel);
            echo '      </button>';
            echo '    </form>';
            echo '  </div>';
            echo '</section>';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    require_once __DIR__ . '/includes/seo_meta.php';
    echo generate_seo_meta([
        'title' => $page['meta_title'] ?: $page['title'],
        'description' => $page['meta_description'] ?: '',
        'keywords' => $page['meta_keywords'] ?: '',
        'url' => 'https://develixirs.com/page.php?slug=' . $slug
    ]);
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Prose defaults for text blocks */
        .prose h1 { font-size: 2.25em; font-weight: 800; margin-bottom: 0.5em; color: #1a202c; }
        .prose h2 { font-size: 1.5em; font-weight: 700; margin-top: 1.5em; margin-bottom: 0.5em; color: #1a202c; }
        .prose p { margin-top: 1em; margin-bottom: 1em; line-height: 1.75; }
        .prose ul { list-style-type: disc; padding-left: 1.5em; margin-top: 1em; margin-bottom: 1em; }
        .prose a { color: #4f46e5; text-decoration: underline; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="assets/css/navbar.css">
</head>
<body class="font-poppins">

<?php include __DIR__ . '/navbar.php'; ?>

<main>
    <?php foreach ($blocks as $block): ?>
        <?php render_block($block); ?>
    <?php endforeach; ?>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
