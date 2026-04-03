<?php
// admin/appearance_homepage.php
session_start();
include __DIR__ . '/../includes/db.php';

$page_title = 'Homepage Appearance';
$page_subtitle = 'Manage homepage specific sections';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'home_video_url' => $_POST['home_video_url'] ?? '',
        'home_video_title' => $_POST['home_video_title'] ?? '',
        'home_video_desc' => $_POST['home_video_desc'] ?? '', // Now rich text
        'home_video_btn_text' => $_POST['home_video_btn_text'] ?? '',
        'home_video_btn_link' => $_POST['home_video_btn_link'] ?? '',
        'home_video_btn_color' => $_POST['home_video_btn_color'] ?? '#4F46E5', // Default Indigo
        'home_side_video_enabled' => isset($_POST['home_side_video_enabled']) ? '1' : '0',
        'home_side_video_url' => $_POST['home_side_video_url'] ?? '',
        'home_side_video_closeable' => isset($_POST['home_side_video_closeable']) ? '1' : '0',
        'home_seo_title' => $_POST['home_seo_title'] ?? '',
        'home_seo_description' => $_POST['home_seo_description'] ?? '',
        'our_story_title' => $_POST['our_story_title'] ?? '',
        'our_story_description' => $_POST['our_story_description'] ?? '',
        'cert_section_title' => $_POST['cert_section_title'] ?? '',
        'cert_section_title' => $_POST['cert_section_title'] ?? '',
        'cert_section_icon' => $_POST['cert_section_icon'] ?? 'fa-solid fa-award',
        // Chatbot Settings
        'chatbot_enabled' => isset($_POST['chatbot_enabled']) ? '1' : '0',
        'chatbot_title' => $_POST['chatbot_title'] ?? 'Customer Support',
        'chatbot_welcome_msg' => $_POST['chatbot_welcome_msg'] ?? 'Hi! How can we help you today?',
        'chatbot_whatsapp_number' => $_POST['chatbot_whatsapp_number'] ?? '',
        'chatbot_whatsapp_number' => $_POST['chatbot_whatsapp_number'] ?? '',
        // Subscribe Text
        'subscribe_title' => $_POST['subscribe_title'] ?? '',
        'subscribe_subtitle' => $_POST['subscribe_subtitle'] ?? '',
    ];

    // Handle File Upload for Subscribe Image OR Media Library Selection
    // Priority: Media Library URL > File Upload
    if (!empty($_POST['subscribe_image_url'])) {
        // User selected from media library
        $settings['subscribe_image'] = $_POST['subscribe_image_url'];
    } elseif (isset($_FILES['subscribe_image']) && $_FILES['subscribe_image']['error'] === UPLOAD_ERR_OK) {
        // User uploaded a new file
        $uploadDir = __DIR__ . '/../assets/uploads/banners/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $filename = 'subscribe_' . time() . '_' . basename($_FILES['subscribe_image']['name']);
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['subscribe_image']['tmp_name'], $targetPath)) {
            $settings['subscribe_image'] = 'assets/uploads/banners/' . $filename;
        }
    }

    // Handle Right Side Video Upload
    if (isset($_FILES['home_side_video_file']) && $_FILES['home_side_video_file']['error'] === UPLOAD_ERR_OK) {
        $allowedVideoExt = ['mp4', 'webm'];
        $originalName = $_FILES['home_side_video_file']['name'] ?? '';
        $videoExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (in_array($videoExt, $allowedVideoExt, true)) {
            $uploadDir = __DIR__ . '/../assets/uploads/home_videos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $filename = 'home_side_video_' . time() . '_' . mt_rand(1000, 9999) . '.' . $videoExt;
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['home_side_video_file']['tmp_name'], $targetPath)) {
                $settings['home_side_video_url'] = 'assets/uploads/home_videos/' . $filename;
            }
        } else {
            $error = 'Right-side video upload failed. Allowed formats: MP4 and WEBM only.';
        }
    } elseif (isset($_FILES['home_side_video_file']) && ($_FILES['home_side_video_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $uploadErrorCode = (int)($_FILES['home_side_video_file']['error'] ?? UPLOAD_ERR_NO_FILE);
        $uploadErrorMap = [
            UPLOAD_ERR_INI_SIZE => 'Video is too large for server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'Video is too large for form upload limit.',
            UPLOAD_ERR_PARTIAL => 'Video upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server missing temporary folder for upload.',
            UPLOAD_ERR_CANT_WRITE => 'Server failed to save uploaded video.',
            UPLOAD_ERR_EXTENSION => 'A server extension blocked video upload.'
        ];
        $error = $uploadErrorMap[$uploadErrorCode] ?? 'Right-side video upload failed. Please try again.';
    }

    // Handle Our Story Image Upload OR Media Library Selection
    if (!empty($_POST['our_story_image_url'])) {
        $settings['our_story_image'] = $_POST['our_story_image_url'];
    } elseif (isset($_FILES['our_story_image']) && $_FILES['our_story_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/story/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $filename = 'story_' . time() . '_' . basename($_FILES['our_story_image']['name']);
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['our_story_image']['tmp_name'], $targetPath)) {
            $settings['our_story_image'] = 'assets/uploads/story/' . $filename;
        }
    }

    // Handle Features JSON
    if (isset($_POST['features'])) {
        $features = [];
        foreach ($_POST['features'] as $index => $feature) {
            if (!empty($feature['title'])) { // Only save if title exists
                $features[] = [
                    'icon' => $feature['icon'] ?? '',
                    'title' => $feature['title'] ?? '',
                    'desc' => $feature['desc'] ?? ''
                ];
            }
        }
        $settings['features_json'] = json_encode($features); // Add to settings array
    }

    // Handle Our Stories JSON (Multiple Stories)
    if (isset($_POST['stories'])) {
        $uploadDir = __DIR__ . '/../assets/uploads/story/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $stories = [];
        foreach ($_POST['stories'] as $index => $story) {
            if (!empty($story['title'])) {
                $imageUrl = $story['image'] ?? '';
                
                // Check if there's a file upload for this story
                if (isset($_FILES['story_file_' . $index]) && $_FILES['story_file_' . $index]['error'] === UPLOAD_ERR_OK) {
                    $filename = 'story_' . time() . '_' . $index . '_' . basename($_FILES['story_file_' . $index]['name']);
                    $targetPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['story_file_' . $index]['tmp_name'], $targetPath)) {
                        $imageUrl = 'assets/uploads/story/' . $filename;
                    }
                }
                
                $stories[] = [
                    'title' => $story['title'] ?? '',
                    'description' => $story['description'] ?? '',
                    'image' => $imageUrl
                ];
            }
        }
        $settings['our_stories_json'] = json_encode($stories);
    }

    // Handle Quick Links (Page IDs)
    if (isset($_POST['quick_links'])) {
        $quickLinks = array_filter($_POST['quick_links'], function($id) {
            return !empty($id) && is_numeric($id);
        });
        $settings['quick_links_json'] = json_encode(array_values($quickLinks));
    }

    // Handle Certification Badges JSON (Max 5 Image+Title)
    if (isset($_POST['badges'])) {
        $uploadDir = __DIR__ . '/../assets/uploads/badges/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $badges = [];
        foreach ($_POST['badges'] as $index => $badge) {
            // We want to save even if just image is set, or just title
            // But usually check if at least one is present or if it's an update
            
            $imageUrl = $badge['image'] ?? '';
            
            // Check file upload
            if (isset($_FILES['badge_file_' . $index]) && $_FILES['badge_file_' . $index]['error'] === UPLOAD_ERR_OK) {
                $filename = 'badge_' . time() . '_' . $index . '_' . basename($_FILES['badge_file_' . $index]['name']);
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['badge_file_' . $index]['tmp_name'], $targetPath)) {
                    $imageUrl = 'assets/uploads/badges/' . $filename;
                }
            }
            
            // Only add if we have an image or a title
            if (!empty($imageUrl) || !empty($badge['title'])) {
                $badges[] = [
                    'title' => $badge['title'] ?? '',
                    'image' => $imageUrl
                ];
            }
        }
        $settings['cert_badges_json'] = json_encode($badges);
    }

    try {
        $pdo->beginTransaction();
        // Use REPLACE INTO to avoid parameter binding issues with reused placeholders
        $stmt = $pdo->prepare("REPLACE INTO site_settings (setting_key, setting_value) VALUES (:key, :val)");
        foreach ($settings as $key => $val) {
            $stmt->execute([':key' => $key, ':val' => $val]);
        }
        $pdo->commit();
        if (!isset($error)) {
            $success = "Homepage settings updated successfully.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Fetch Current Settings
$currentSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) { }

// Defaults
$vidUrl = $currentSettings['home_video_url'] ?? '';
$vidTitle = $currentSettings['home_video_title'] ?? '';
$vidDesc = $currentSettings['home_video_desc'] ?? '';
$vidBtnText = $currentSettings['home_video_btn_text'] ?? '';
$vidBtnLink = $currentSettings['home_video_btn_link'] ?? '';
$vidBtnColor = $currentSettings['home_video_btn_color'] ?? '#4F46E5';
$sideVideoEnabled = $currentSettings['home_side_video_enabled'] ?? '0';
$sideVideoUrl = $currentSettings['home_side_video_url'] ?? '';
$sideVideoCloseable = $currentSettings['home_side_video_closeable'] ?? '1';
$sideVideoPreviewUrl = $sideVideoUrl;
if (!empty($sideVideoPreviewUrl) && !preg_match('#^https?://#i', $sideVideoPreviewUrl) && strpos($sideVideoPreviewUrl, '/') !== 0) {
    $sideVideoPreviewUrl = '../' . ltrim($sideVideoPreviewUrl, '/');
}
$seoTitle = $currentSettings['home_seo_title'] ?? '';
$seoDesc = $currentSettings['home_seo_description'] ?? '';

// Certification Section Defaults
$certTitle = $currentSettings['cert_section_title'] ?? 'Certified Excellence';
$certIcon = $currentSettings['cert_section_icon'] ?? 'fa-solid fa-award';
$certBadgesJson = $currentSettings['cert_badges_json'] ?? '[]';
$certBadges = json_decode($certBadgesJson, true);
if (empty($certBadges)) {
    // Default 3 badges
    $certBadges = [
        ['title' => 'GMP Certified', 'image' => 'assets/images/badge-gmp.png'],
        ['title' => 'AYUSH Premium', 'image' => 'assets/images/badge-ayush.png'],
        ['title' => 'ISO 9001:2015', 'image' => 'assets/images/badge-iso.png'],
    ];
}

// Subscribe & Features Defaults
$subscribeImage = $currentSettings['subscribe_image'] ?? 'assets/images/bottle-group.png';
$subscribeTitle = $currentSettings['subscribe_title'] ?? 'Stay home & get your daily <br>needs from our shop';
$subscribeSubtitle = $currentSettings['subscribe_subtitle'] ?? 'Start Your Daily Shopping with Herbal Ecom';
$featuresJson = $currentSettings['features_json'] ?? '[]';
$features = json_decode($featuresJson, true);
if (empty($features)) {
    // defaults if empty
    $features = [
        ['icon' => 'fa-solid fa-earth-americas', 'title' => 'Worldwide Shipping', 'desc' => 'Free worldwide shipping across the globe'],
        ['icon' => 'fa-brands fa-whatsapp', 'title' => 'Whatsapp Customer', 'desc' => '24-day hassle-free return policy'],
        ['icon' => 'fa-regular fa-credit-card', 'title' => 'Secured Payments', 'desc' => 'We accept all major credit cards'],
        ['icon' => 'fa-solid fa-truck-fast', 'title' => 'Quick Delivery', 'desc' => 'Free shipping across India above ₹499'],
        ['icon' => 'fa-solid fa-leaf', 'title' => 'Freshly Made', 'desc' => 'We make your produce fresh batches'],
    ];
}

// Quick Links Defaults
$quickLinksJson = $currentSettings['quick_links_json'] ?? '[]';
$selectedQuickLinks = json_decode($quickLinksJson, true) ?: [];

// Fetch All Pages for Dropdown
$allPages = [];
try {
    $stmt = $pdo->query("SELECT id, title, slug FROM pages WHERE status = 'published' ORDER BY title ASC");
    $allPages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allPages = [];
}

// Our Stories Defaults (Multiple Stories)
$ourStoriesJson = $currentSettings['our_stories_json'] ?? '[]';
$ourStories = json_decode($ourStoriesJson, true);
if (empty($ourStories)) {
    $ourStories = [
        ['title' => 'A New Era in Natural Beauty', 'description' => 'Pioneering sustainable practices with traditional Ayurvedic wisdom.', 'image' => ''],
        ['title' => 'A Multi-Sensorial Journey', 'description' => 'Authentic roots, sophisticated presentation, immersive experience.', 'image' => ''],
        ['title' => 'Fresh, Pure, Potent', 'description' => 'Handcrafted using 100% natural ingredients from the Indian landscape.', 'image' => ''],
    ];
}

// Chatbot Defaults
$chatEnabled = $currentSettings['chatbot_enabled'] ?? '0';
$chatTitle = $currentSettings['chatbot_title'] ?? 'Customer Support';
$chatWelcome = $currentSettings['chatbot_welcome_msg'] ?? 'Hi! How can we help you today?';
$chatWhatsapp = $currentSettings['chatbot_whatsapp_number'] ?? '';

?>
<?php include 'layout/header.php'; ?>

<div class="max-w-[1000px] mx-auto mt-8 px-4 pb-20">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Homepage Appearance</h1>
            <p class="text-slate-500 text-sm">Configure sections and SEO for the homepage.</p>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="bg-green-50 text-green-700 p-4 rounded-lg mb-6 border border-green-200 flex items-center gap-2">
            <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
        
        <!-- SEO Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <span class="bg-blue-100 text-blue-600 p-1.5 rounded text-sm"><i class="fa-brands fa-google"></i></span>
                Homepage SEO
            </h3>
            <div class="grid grid-cols-1 gap-6">
                 <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">SEO Title</label>
                    <input type="text" name="home_seo_title" value="<?= htmlspecialchars($seoTitle) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="My Awesome Herbal Store">
                    <p class="text-xs text-slate-500 mt-1">Overrides the default page title.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">SEO Meta Description</label>
                    <textarea name="home_seo_description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= htmlspecialchars($seoDesc) ?></textarea>
                    <p class="text-xs text-slate-500 mt-1">Recommended length: 150-160 characters.</p>
                </div>
            </div>
        </div>


        <!-- Certification Section Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <span class="bg-purple-100 text-purple-600 p-1.5 rounded text-sm"><i class="fa-solid fa-certificate"></i></span>
                Certification Badges Section
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                 <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Section Title</label>
                    <input type="text" name="cert_section_title" value="<?= htmlspecialchars($certTitle) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Header Icon Class 
                        <a href="https://fontawesome.com/icons" target="_blank" class="text-xs text-indigo-500 font-normal hover:underline ml-1">(FontAwesome 6)</a>
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="cert_section_icon" value="<?= htmlspecialchars($certIcon) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. fa-solid fa-award">
                        <div class="w-10 h-10 bg-gray-100 rounded border border-gray-200 flex items-center justify-center text-gray-600">
                            <i class="<?= htmlspecialchars($certIcon) ?>"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Badges Repeater -->
            <div class="mt-6 border-t border-gray-100 pt-6">
                <h4 class="text-sm font-semibold text-slate-700 mb-3">Badges (Max 5)</h4>
                <div class="space-y-3">
                    <?php 
                    // merge existing badges with empty slots up to 5
                    $displayBadges = array_pad($certBadges, 5, []);
                    foreach ($displayBadges as $i => $badge): 
                        if ($i >= 5) break; // Limit to 5
                    ?>
                        <div class="flex gap-4 items-start p-3 bg-gray-50 border border-gray-200 rounded-lg">
                            <!-- Image -->
                            <div class="w-16 h-16 bg-white rounded-lg border border-gray-200 flex-shrink-0 overflow-hidden flex items-center justify-center relative group">
                                <?php if (!empty($badge['image'])): ?>
                                    <img id="badge_preview_<?= $i ?>" src="../<?= htmlspecialchars($badge['image']) ?>" class="w-full h-full object-contain p-1">
                                <?php else: ?>
                                    <div id="badge_preview_<?= $i ?>" class="text-gray-300 text-xs text-center p-1">No Icon</div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Inputs -->
                            <div class="flex-1 space-y-2">
                                <div class="flex gap-2">
                                    <div class="flex-1">
                                        <input type="text" name="badges[<?= $i ?>][title]" value="<?= htmlspecialchars($badge['title'] ?? '') ?>" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:border-indigo-500" placeholder="Badge Title (e.g. GMP Certified)">
                                    </div>
                                    <div class="flex gap-1">
                                        <button type="button" onclick="openMediaForBadge(<?= $i ?>)" class="bg-white text-gray-600 px-2 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50" title="Select from Library">
                                            <i class="fa-regular fa-images"></i>
                                        </button>
                                        <input type="file" id="badge_file_<?= $i ?>" name="badge_file_<?= $i ?>" accept="image/*" class="hidden" onchange="previewBadgeImage(<?= $i ?>, this)">
                                        <button type="button" onclick="document.getElementById('badge_file_<?= $i ?>').click()" class="bg-white text-gray-600 px-2 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50" title="Upload New">
                                            <i class="fa-solid fa-upload"></i>
                                        </button>
                                        <button type="button" onclick="removeBadge(<?= $i ?>)" class="bg-white text-red-600 px-2 py-1 text-xs rounded border border-gray-300 hover:bg-red-50" title="Remove Badge">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <input type="hidden" name="badges[<?= $i ?>][image]" id="badge_image_<?= $i ?>" value="<?= htmlspecialchars($badge['image'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Our Stories Section (Multiple Cards) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <span class="bg-amber-100 text-amber-600 p-1.5 rounded text-sm"><i class="fa-solid fa-book-open"></i></span>
                Our Stories Section (Max 4 Cards)
            </h3>
            <div class="space-y-4">
                <?php foreach ($ourStories as $idx => $story): ?>
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Title & Description -->
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-500 block mb-1">Story Title</label>
                                    <input type="text" name="stories[<?= $idx ?>][title]" value="<?= htmlspecialchars($story['title'] ?? '') ?>" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-indigo-500" placeholder="A New Era in Natural Beauty">
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-500 block mb-1">Description</label>
                                    <textarea name="stories[<?= $idx ?>][description]" rows="3" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-indigo-500" placeholder="Brief description..."><?= htmlspecialchars($story['description'] ?? '') ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Image Upload -->
                            <div>
                                <label class="text-xs font-semibold text-slate-500 block mb-1">Image</label>
                                <div class="w-full h-32 bg-gray-100 rounded-lg overflow-hidden border border-gray-200 mb-2">
                                    <?php if (!empty($story['image'])): ?>
                                        <img id="story_preview_<?= $idx ?>" src="../<?= htmlspecialchars($story['image']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div id="story_preview_<?= $idx ?>" class="w-full h-full flex items-center justify-center text-gray-400 text-xs">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" onclick="openMediaForStory(<?= $idx ?>)" class="flex-1 bg-gray-100 text-gray-700 px-3 py-1.5 text-xs rounded border border-gray-300 hover:bg-gray-200">
                                        📁 Library
                                    </button>
                                    <input type="file" id="story_file_<?= $idx ?>" accept="image/*" class="hidden" onchange="previewStoryImage(<?= $idx ?>, this)">
                                    <button type="button" onclick="document.getElementById('story_file_<?= $idx ?>').click()" class="flex-1 bg-gray-100 text-gray-700 px-3 py-1.5 text-xs rounded border border-gray-300 hover:bg-gray-200">
                                        📤 Upload
                                    </button>
                                </div>
                                <input type="hidden" name="stories[<?= $idx ?>][image]" id="story_image_<?= $idx ?>" value="<?= htmlspecialchars($story['image'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php 
                // Add empty slots if less than 4
                for ($i = count($ourStories); $i < 4; $i++): ?>
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-500 block mb-1">Story Title</label>
                                    <input type="text" name="stories[<?= $i ?>][title]" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-indigo-500" placeholder="Story Title">
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-500 block mb-1">Description</label>
                                    <textarea name="stories[<?= $i ?>][description]" rows="3" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-indigo-500"></textarea>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500 block mb-1">Image</label>
                                <div class="w-full h-32 bg-gray-100 rounded-lg overflow-hidden border border-gray-200 mb-2">
                                    <div id="story_preview_<?= $i ?>" class="w-full h-full flex items-center justify-center text-gray-400 text-xs">No Image</div>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" onclick="openMediaForStory(<?= $i ?>)" class="flex-1 bg-gray-100 text-gray-700 px-3 py-1.5 text-xs rounded border border-gray-300 hover:bg-gray-200">
                                        📁 Library
                                    </button>
                                    <input type="file" id="story_file_<?= $i ?>" accept="image/*" class="hidden" onchange="previewStoryImage(<?= $i ?>, this)">
                                    <button type="button" onclick="document.getElementById('story_file_<?= $i ?>').click()" class="flex-1 bg-gray-100 text-gray-700 px-3 py-1.5 text-xs rounded border border-gray-300 hover:bg-gray-200">
                                        📤 Upload
                                    </button>
                                </div>
                                <input type="hidden" name="stories[<?= $i ?>][image]" id="story_image_<?= $i ?>" value="">
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Homepage Video/Media Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded text-sm"><i class="fa-solid fa-video"></i></span>
                Media & Text Section
            </h3>
            
            <div class="grid grid-cols-1 gap-6">
                <!-- Media Selection -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Media URL (Video or Image)</label>
                    <div class="flex gap-2">
                        <input type="text" name="home_video_url" id="home_video_url" value="<?= htmlspecialchars($vidUrl) ?>" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="https://...">
                        <button type="button" id="selectMediaBtn" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-200 transition">
                            📁 Select Media
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">YouTube URL, MP4 link, or Image URL. Use the button to pick from library.</p>
                    
                    <!-- Preview -->
                    <div id="media_preview" class="mt-3 hidden border rounded p-2 bg-gray-50 max-w-xs">
                         <!-- JS will populate -->
                    </div>
                </div>
            
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Section Title</label>
                        <input type="text" name="home_video_title" value="<?= htmlspecialchars($vidTitle) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <!-- Button Settings -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Button Text</label>
                            <input type="text" name="home_video_btn_text" value="<?= htmlspecialchars($vidBtnText) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="col-span-1">
                             <label class="block text-sm font-medium text-slate-700 mb-1">Button Color</label>
                             <div class="flex items-center gap-2">
                                <input type="color" name="home_video_btn_color" value="<?= htmlspecialchars($vidBtnColor) ?>" class="h-10 w-12 p-0 border-0 rounded overflow-hidden cursor-pointer">
                                <input type="text" value="<?= htmlspecialchars($vidBtnColor) ?>" class="flex-1 px-2 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50" readonly>
                             </div>
                        </div>
                    </div>
                </div>
                
                <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1">Button Link</label>
                     <input type="text" name="home_video_btn_link" value="<?= htmlspecialchars($vidBtnLink) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Rich Text Description -->
                <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                     <div id="editorWrapper">
                         <textarea name="home_video_desc" id="editor-content"><?= htmlspecialchars($vidDesc) ?></textarea>
                     </div>
                </div>
            </div>

            <!-- Quick Links Section -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Sidebar Quick Links (Max 4)</label>
                <p class="text-xs text-slate-500 mb-3">Select up to 4 pages to display in the sidebar Quick Links widget</p>
                <div class="space-y-2">
                    <?php for ($i = 0; $i < 4; $i++): 
                        $selectedId = $selectedQuickLinks[$i] ?? '';
                    ?>
                        <select name="quick_links[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                            <option value="">-- None --</option>
                            <?php foreach ($allPages as $page): ?>
                                <option value="<?= $page['id'] ?>" <?= ($selectedId == $page['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($page['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Right Side Home Video Widget -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <span class="bg-fuchsia-100 text-fuchsia-600 p-1.5 rounded text-sm"><i class="fa-solid fa-clapperboard"></i></span>
                Right Side Video Widget (Homepage)
            </h3>

            <div class="space-y-5">
                <div class="flex flex-wrap gap-6 items-center">
                    <label class="switch relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="home_side_video_enabled" value="1" class="sr-only peer" <?= ($sideVideoEnabled === '1') ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-fuchsia-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-fuchsia-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900">Show floating video on homepage</span>
                    </label>

                    <label class="switch relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="home_side_video_closeable" value="1" class="sr-only peer" <?= ($sideVideoCloseable === '1') ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-fuchsia-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-fuchsia-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900">Allow users to close widget</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Video URL (Instagram/Reel size recommended 9:16)</label>
                    <div class="flex gap-2">
                        <input type="text" name="home_side_video_url" id="home_side_video_url" value="<?= htmlspecialchars($sideVideoUrl) ?>" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-fuchsia-500" placeholder="https://... or assets/uploads/home_videos/...">
                        <button type="button" id="selectSideVideoBtn" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-200 transition">
                            📁 Select Media
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Best output: vertical video (1080x1920). Use MP4 (H.264) or WEBM for reliable playback.</p>
                </div>

                <div class="flex flex-wrap gap-3 items-center">
                    <input type="file" name="home_side_video_file" id="home_side_video_file" accept="video/mp4,video/webm" class="hidden">
                    <button type="button" id="uploadSideVideoBtn" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-200 transition">
                        📤 Upload New Video
                    </button>
                    <span id="home_side_video_file_name" class="text-xs text-slate-500"></span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-2">Preview</label>
                    <div id="side_video_preview_box" class="w-[180px] h-[320px] rounded-2xl border border-gray-200 overflow-hidden bg-gray-100 flex items-center justify-center">
                        <?php if (!empty($sideVideoPreviewUrl)): ?>
                            <video id="side_video_preview" src="<?= htmlspecialchars($sideVideoPreviewUrl) ?>" class="w-full h-full object-cover" controls muted playsinline></video>
                        <?php else: ?>
                            <div id="side_video_preview" class="text-xs text-gray-400 px-3 text-center">No video selected</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscribe & Features Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <span class="bg-green-100 text-green-600 p-1.5 rounded text-sm"><i class="fa-solid fa-leaf"></i></span>
                Homepage Subscribe & Features
            </h3>
            
            <!-- Subscribe Image -->
            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Subscribe Title (HTML allowed for &lt;br&gt;)</label>
                        <input type="text" name="subscribe_title" value="<?= htmlspecialchars($subscribeTitle) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                         <label class="block text-sm font-medium text-slate-700 mb-1">Subscribe Subtitle</label>
                         <input type="text" name="subscribe_subtitle" value="<?= htmlspecialchars($subscribeSubtitle) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>

                <label class="block text-sm font-medium text-slate-700 mb-1">Subscribe Section Image</label>
                <div class="space-y-3">
                    <!-- Preview -->
                    <div class="w-32 h-32 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                        <?php if (!empty($subscribeImage)): ?>
                            <img id="subscribe_preview" src="../<?= htmlspecialchars($subscribeImage) ?>" class="w-full h-full object-contain">
                        <?php else: ?>
                            <div id="subscribe_preview" class="w-full h-full flex items-center justify-center text-gray-400 text-xs">No Image</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Controls -->
                    <div class="flex gap-2">
                        <button type="button" id="selectSubscribeImgBtn" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-200 transition">
                            📁 Select from Library
                        </button>
                        <input type="file" name="subscribe_image" id="subscribe_image_file" accept="image/*" class="hidden">
                        <button type="button" onclick="document.getElementById('subscribe_image_file').click()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-200 transition">
                            📤 Upload New
                        </button>
                    </div>
                    
                    <!-- Hidden field for selected library image -->
                    <input type="hidden" name="subscribe_image_url" id="subscribe_image_url" value="">
                    
                    <p class="text-xs text-slate-500">Recommended: Transparent PNG, approx 400x400px</p>
                </div>
            </div>

            <!-- Features Repeater -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Service Features (Max 5)</label>
                <div class="space-y-3">
                    <?php foreach ($features as $idx => $ft): ?>
                        <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-500 block mb-1">Icon Class (FontAwesome)</label>
                            <input type="text" name="features[<?= $idx ?>][icon]" value="<?= htmlspecialchars($ft['icon'] ?? '') ?>" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-indigo-500" placeholder="fa-solid fa-truck">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500 block mb-1">Title</label>
                            <input type="text" name="features[<?= $idx ?>][title]" value="<?= htmlspecialchars($ft['title'] ?? '') ?>" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-indigo-500" placeholder="Free Shipping">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500 block mb-1">Description</label>
                            <input type="text" name="features[<?= $idx ?>][desc]" value="<?= htmlspecialchars($ft['desc'] ?? '') ?>" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-indigo-500" placeholder="Over $50">
                        </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php 
                    // Add empty slots if less than 5
                    for ($i = count($features); $i < 5; $i++): ?>
                        <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-500 block mb-1">Icon Class</label>
                            <input type="text" name="features[<?= $i ?>][icon]" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-indigo-500" placeholder="fa-solid fa-star">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500 block mb-1">Title</label>
                            <input type="text" name="features[<?= $i ?>][title]" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500 block mb-1">Description</label>
                            <input type="text" name="features[<?= $i ?>][desc]" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-indigo-500">
                        </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        </div>

        <!-- Chatbot Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <span class="bg-blue-100 text-blue-600 p-1.5 rounded text-sm"><i class="fa-solid fa-comments"></i></span>
                Chatbot Configuration
            </h3>
            
            <div class="space-y-4">
                <div class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                     <label class="switch relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="chatbot_enabled" value="1" class="sr-only peer" <?= ($chatEnabled == '1') ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900">Enable Global Chatbot</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Widget Title</label>
                        <input type="text" name="chatbot_title" value="<?= htmlspecialchars($chatTitle) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">WhatsApp Number (Optional)</label>
                        <input type="text" name="chatbot_whatsapp_number" value="<?= htmlspecialchars($chatWhatsapp) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. 919500650454">
                        <p class="text-xs text-slate-500 mt-1">If set, a direct 'Chat on WhatsApp' button will be shown.</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Welcome Message</label>
                    <textarea name="chatbot_welcome_msg" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($chatWelcome) ?></textarea>
                </div>
            </div>
        </div>

        <div class="pt-4 flex justify-end">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-8 rounded-lg transition shadow-md hover:shadow-lg text-lg">
                Save All Changes
            </button>
        </div>

    </form>
</div>

<!-- Scripts -->
<script src="//cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<style>
  .cke_notification_warning { display: none !important; }
  .cke_notifications_area { display: none !important; }
</style>
<script>
  // Initialize CKEditor
  const ckEditorFontCssUrl = 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Cormorant+Garamond:wght@300;400;500;600;700&family=Lato:wght@300;400;700;900&family=Open+Sans:wght@300;400;600;700;800&family=Source+Sans+3:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&family=EB+Garamond:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Cormorant:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700;800&family=Raleway:wght@300;400;500;600;700;800&display=swap';
  const ckEditorExtraFontNames =
    'Playfair Display/Playfair Display, serif;' +
    'Cormorant Garamond/Cormorant Garamond, serif;' +
    'Lato/Lato, sans-serif;' +
    'Open Sans/Open Sans, sans-serif;' +
    'Source Sans/Source Sans 3, Source Sans Pro, sans-serif;' +
    'Libre Baskerville/Libre Baskerville, serif;' +
    'EB Garamond/EB Garamond, serif;' +
    'Montserrat/Montserrat, sans-serif;' +
    'Poppins/Poppins, sans-serif;' +
    'Cormorant/Cormorant, serif;' +
    'Nunito/Nunito, sans-serif;' +
    'Raleway/Raleway, sans-serif';
  CKEDITOR.replace('editor-content', {
    height: 300,
    removePlugins: 'easyimage,cloudservices',
    extraPlugins: 'uploadimage', // Ensure this plugin is allowed/available
    filebrowserUploadUrl: '/admin/upload_blog_image.php',
    uploadUrl: '/admin/upload_blog_image.php',
    font_names: (CKEDITOR.config.font_names ? CKEDITOR.config.font_names + ';' : '') + ckEditorExtraFontNames,
    on: {
        instanceReady: function() {
             this.document.appendStyleSheet(ckEditorFontCssUrl);
             const notificationArea = document.querySelector('.cke_notifications_area');
             if (notificationArea) notificationArea.style.display = 'none';
        }
    }
  });

  // Media Modal Logic
  window.mediaTargetInput = null;

  function openMediaModal(targetInputId) {
    window.mediaTargetInput = targetInputId;
    const modal = document.createElement('div');
    modal.id = 'mediaLibraryModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
    modal.innerHTML = `
      <div class="bg-white rounded-lg shadow-xl w-11/12 h-5/6 max-w-6xl overflow-hidden relative">
        <button onclick="closeMediaModal()" class="absolute top-4 right-4 z-50 bg-white rounded-full w-10 h-10 flex items-center justify-center shadow-lg hover:bg-gray-100 text-gray-600 hover:text-gray-800" style="font-size: 24px;">
          &times;
        </button>
        <iframe src="/admin/media.php?select=1" class="w-full h-full border-0"></iframe>
      </div>
    `;
    document.body.appendChild(modal);
  }

  window.closeMediaModal = function() {
    const modal = document.getElementById('mediaLibraryModal');
    if (modal) modal.remove();
  };

  function resolveMediaPreviewUrl(url) {
      if (!url) return '';
      if (/^https?:\/\//i.test(url) || url.startsWith('/') || url.startsWith('../')) {
          return url;
      }
      return '../' + url.replace(/^\/+/, '');
  }

  function renderSideVideoPreview(url) {
      const previewBox = document.getElementById('side_video_preview_box');
      if (!previewBox) return;
      const safeUrl = resolveMediaPreviewUrl(url);
      if (!safeUrl) {
          previewBox.innerHTML = '<div id="side_video_preview" class="text-xs text-gray-400 px-3 text-center">No video selected</div>';
          return;
      }
      previewBox.innerHTML = '<video id="side_video_preview" src="' + safeUrl + '" class="w-full h-full object-cover" controls muted playsinline></video>';
  }

  document.getElementById('selectMediaBtn').addEventListener('click', function() {
      openMediaModal('home_video_url');
  });

  const selectSideVideoBtn = document.getElementById('selectSideVideoBtn');
  if (selectSideVideoBtn) {
      selectSideVideoBtn.addEventListener('click', function() {
          openMediaModal('home_side_video_url');
      });
  }

  // Subscribe Image Media Button
  document.getElementById('selectSubscribeImgBtn').addEventListener('click', function() {
      openMediaModal('subscribe_image_url');
  });

  const sideVideoUploadBtn = document.getElementById('uploadSideVideoBtn');
  const sideVideoFileInput = document.getElementById('home_side_video_file');
  const sideVideoUrlInput = document.getElementById('home_side_video_url');
  const sideVideoFileName = document.getElementById('home_side_video_file_name');

  if (sideVideoUploadBtn && sideVideoFileInput) {
      sideVideoUploadBtn.addEventListener('click', function() {
          sideVideoFileInput.click();
      });
  }

  if (sideVideoFileInput) {
      sideVideoFileInput.addEventListener('change', function(e) {
          const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
          if (!file) return;
          if (sideVideoFileName) sideVideoFileName.textContent = file.name;
          renderSideVideoPreview(URL.createObjectURL(file));
      });
  }

  if (sideVideoUrlInput) {
      sideVideoUrlInput.addEventListener('input', function() {
          if (!sideVideoFileInput || !sideVideoFileInput.files || sideVideoFileInput.files.length === 0) {
              renderSideVideoPreview(sideVideoUrlInput.value.trim());
          }
      });
  }

  // File input preview for subscribe image
  document.getElementById('subscribe_image_file').addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
          const reader = new FileReader();
          reader.onload = function(event) {
              const preview = document.getElementById('subscribe_preview');
              if (preview.tagName === 'IMG') {
                  preview.src = event.target.result;
              } else {
                  preview.outerHTML = '<img id="subscribe_preview" src="' + event.target.result + '" class="w-full h-full object-contain">';
              }
          };
          reader.readAsDataURL(e.target.files[0]);
      }
  });

  // Our Stories Image Handlers (for multiple stories)
  window.currentStoryIndex = null;
  
  window.openMediaForStory = function(index) {
      window.currentStoryIndex = index;
      openMediaModal('story_image_' + index);
  };
  
  window.previewStoryImage = function(index, input) {
      if (input.files && input.files[0]) {
          const reader = new FileReader();
          reader.onload = function(e) {
              const preview = document.getElementById('story_preview_' + index);
              if (preview.tagName === 'IMG') {
                  preview.src = e.target.result;
              } else {
                  preview.outerHTML = '<img id="story_preview_' + index + '" src="' + e.target.result + '" class="w-full h-full object-cover">';
              }
              // Note: File upload will be handled via form submission
          };
          reader.readAsDataURL(input.files[0]);
      }
  };

  // Certification Badges Image Handlers
  window.currentBadgeIndex = null;
  
  window.openMediaForBadge = function(index) {
      window.currentBadgeIndex = index;
      openMediaModal('badge_image_' + index);
  };
  
  window.previewBadgeImage = function(index, input) {
      if (input.files && input.files[0]) {
          const reader = new FileReader();
          reader.onload = function(e) {
              const preview = document.getElementById('badge_preview_' + index);
              if (preview.tagName === 'IMG') {
                  preview.src = e.target.result;
              } else {
                  preview.outerHTML = '<img id="badge_preview_' + index + '" src="' + e.target.result + '" class="w-full h-full object-contain">';
              }
          };
          reader.readAsDataURL(input.files[0]);
      }
  };

  window.removeBadge = function(index) {
      if(!confirm('Are you sure you want to remove this badge?')) return;
      
      // Clear Title
      const titleInput = document.querySelector(`input[name="badges[${index}][title]"]`);
      if(titleInput) titleInput.value = '';
      
      // Clear Hidden Image Input
      const hiddenInput = document.getElementById(`badge_image_${index}`);
      if(hiddenInput) hiddenInput.value = '';
      
      // Reset Preview
      const previewEl = document.getElementById(`badge_preview_${index}`);
      if(previewEl) {
          const container = previewEl.parentNode;
          container.innerHTML = `<div id="badge_preview_${index}" class="text-gray-300 text-xs text-center p-1">No Icon</div>`;
      }
  };

  // Callback from media.php
  window.insertImagesToEditor = function(imagePaths) {
      if (!imagePaths || imagePaths.length === 0) return;

      if (window.mediaTargetInput === 'home_video_url') {
          // Set URL
          const url = imagePaths[0];
          document.getElementById('home_video_url').value = url;
          closeMediaModal();
      } else if (window.mediaTargetInput === 'home_side_video_url') {
          const url = imagePaths[0];
          const sideVideoInput = document.getElementById('home_side_video_url');
          if (sideVideoInput) {
              sideVideoInput.value = url;
              renderSideVideoPreview(url);
          }
          closeMediaModal();
      } else if (window.mediaTargetInput === 'subscribe_image_url') {
          // Set Subscribe Image URL
          const url = imagePaths[0];
          document.getElementById('subscribe_image_url').value = url;
          
          // Update preview
          const preview = document.getElementById('subscribe_preview');
          if (preview.tagName === 'IMG') {
              preview.src = '../' + url;
          } else {
              preview.outerHTML = '<img id="subscribe_preview" src="../' + url + '" class="w-full h-full object-contain">';
          }
          
          closeMediaModal();
      } else if (window.mediaTargetInput === 'our_story_image_url') {
           // Case for single story image if used
           const url = imagePaths[0];
           // ... implementation if needed
           document.getElementById('our_story_image_url').value = url;
           closeMediaModal();
      } else if (window.mediaTargetInput && window.mediaTargetInput.startsWith('story_image_')) {
          // Set Story Image URL
          const url = imagePaths[0];
          const hiddenInput = document.getElementById(window.mediaTargetInput);
          if (hiddenInput) {
              hiddenInput.value = url;
              
              // Update preview
              const index = window.currentStoryIndex;
              const preview = document.getElementById('story_preview_' + index);
              if (preview) {
                  if (preview.tagName === 'IMG') {
                      preview.src = '../' + url;
                  } else {
                      preview.outerHTML = '<img id="story_preview_' + index + '" src="../' + url + '" class="w-full h-full object-cover">';
                  }
              }
          }
          closeMediaModal();
      } else if (window.mediaTargetInput && window.mediaTargetInput.startsWith('badge_image_')) {
        // Set Badge Image URL
        const url = imagePaths[0];
        const hiddenInput = document.getElementById(window.mediaTargetInput);
        if (hiddenInput) {
            hiddenInput.value = url;
            
            // Update preview
            const index = window.currentBadgeIndex;
            const preview = document.getElementById('badge_preview_' + index);
            if (preview) {
                if (preview.tagName === 'IMG') {
                    preview.src = '../' + url;
                } else {
                    preview.outerHTML = '<img id="badge_preview_' + index + '" src="../' + url + '" class="w-full h-full object-contain">';
                }
            }
        }
        closeMediaModal();
    }
  };
</script>

<?php include 'layout/footer.php'; ?>
