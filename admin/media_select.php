<?php
// admin/media_select.php - Standalone media selector for blog editor
require_once __DIR__ . '/_auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Scan filesystem for media files
$mediaFiles = [];
$uploadDirs = [
    '../assets/uploads/',
    '../assets/images/',
    '../assets/uploads/blog_images/',
];

$debugInfo = [];
foreach ($uploadDirs as $dir) {
    $absolutePath = realpath($dir);
    $exists = is_dir($dir);
    $debugInfo[] = "Dir: $dir | Exists: " . ($exists ? 'Yes' : 'No') . " | Absolute: " . ($absolutePath ?: 'N/A');
    
    if (is_dir($dir)) {
        $files = glob($dir . '*.{jpg,jpeg,png,gif,webp,mp4,mov,avi}', GLOB_BRACE);
        $debugInfo[] = "  - Found " . count($files) . " files";
        
        foreach ($files as $file) {
            $relativePath = str_replace('../', '/', $file);
            $fileName = basename($file);
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            $mediaFiles[] = [
                'id' => md5($file),
                'file_name' => $fileName,
                'file_path' => $relativePath,
                'file_type' => in_array($extension, ['mp4', 'mov', 'avi']) ? 'video' : 'image'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Media</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        .media-card.selected {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto p-6">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-4 mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-images text-indigo-600 mr-2"></i>
                Select Media
            </h1>
            <div class="flex gap-3">
                <span id="selectedCount" class="text-sm text-gray-600 py-2 px-4 bg-gray-100 rounded">
                    0 selected
                </span>
                <button onclick="importSelected()" 
                        class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 transition font-medium">
                    <i class="fas fa-check mr-2"></i>Import Selected
                </button>
                <button onclick="window.parent.closeMediaModal()" 
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </div>

        <!-- Media Grid -->
        <div class="bg-white rounded-lg shadow p-6">
            <?php if (empty($mediaFiles)): ?>
                <div class="text-center py-16">
                    <i class="fas fa-images text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg">No media files found</p>
                    <p class="text-gray-400 text-sm mt-2">Upload images from the Media Library page</p>
                    
                    <!-- Debug Info -->
                    <div class="mt-6 text-left bg-gray-100 p-4 rounded text-xs">
                        <p class="font-bold mb-2">Debug Info:</p>
                        <?php foreach ($debugInfo as $info): ?>
                            <p><?php echo htmlspecialchars($info); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" id="mediaGrid">
                    <?php foreach ($mediaFiles as $file): ?>
                        <div class="media-card border-2 border-gray-200 rounded-lg overflow-hidden cursor-pointer hover:border-indigo-400 transition relative"
                             data-file-id="<?php echo $file['id']; ?>"
                             data-file-path="<?php echo htmlspecialchars($file['file_path']); ?>"
                             onclick="toggleSelect(this)">
                            
                            <!-- Selection Checkbox -->
                            <div class="absolute top-2 left-2 z-10">
                                <input type="checkbox" 
                                       class="media-checkbox w-5 h-5 accent-indigo-600 cursor-pointer"
                                       onclick="event.stopPropagation(); toggleSelect(this.parentElement.parentElement)">
                            </div>

                            <!-- Image Preview -->
                            <div class="aspect-square bg-gray-100 flex items-center justify-center">
                                <?php if (strpos($file['file_type'], 'image') !== false): ?>
                                    <img src="<?php echo htmlspecialchars($file['file_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($file['file_name']); ?>"
                                         class="w-full h-full object-cover">
                                <?php elseif (strpos($file['file_type'], 'video') !== false): ?>
                                    <video src="<?php echo htmlspecialchars($file['file_path']); ?>" 
                                           class="w-full h-full object-cover"></video>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <i class="fas fa-play-circle text-white text-3xl opacity-75"></i>
                                    </div>
                                <?php else: ?>
                                    <i class="fas fa-file text-gray-400 text-3xl"></i>
                                <?php endif; ?>
                            </div>

                            <!-- File Name -->
                            <div class="p-2 bg-white">
                                <p class="text-xs text-gray-600 truncate" title="<?php echo htmlspecialchars($file['file_name']); ?>">
                                    <?php echo htmlspecialchars($file['file_name']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const selectedFiles = new Set();

        function toggleSelect(card) {
            const checkbox = card.querySelector('.media-checkbox');
            const fileId = card.dataset.fileId;

            if (selectedFiles.has(fileId)) {
                selectedFiles.delete(fileId);
                card.classList.remove('selected');
                checkbox.checked = false;
            } else {
                selectedFiles.add(fileId);
                card.classList.add('selected');
                checkbox.checked = true;
            }

            updateSelectedCount();
        }

        function updateSelectedCount() {
            const count = selectedFiles.size;
            document.getElementById('selectedCount').textContent = `${count} selected`;
        }

        function importSelected() {
            if (selectedFiles.size === 0) {
                alert('Please select at least one image');
                return;
            }

            const selectedCards = Array.from(document.querySelectorAll('.media-card.selected'));
            const imagePaths = selectedCards.map(card => card.dataset.filePath);

            // Call parent window function to insert images
            if (window.parent && window.parent.insertImagesToEditor) {
                window.parent.insertImagesToEditor(imagePaths);
            }
        }

        // Allow selecting with keyboard (Space key)
        document.addEventListener('keydown', function(e) {
            if (e.code === 'Space' && document.activeElement.classList.contains('media-card')) {
                e.preventDefault();
                toggleSelect(document.activeElement);
            }
        });
    </script>
</body>
</html>
