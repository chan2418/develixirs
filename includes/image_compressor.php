<?php
/**
 * Image Compression Utility
 * Automatically compresses and optimizes images for web
 * Supports JPEG, PNG, and generates WebP versions
 */

class ImageCompressor {
    
    // Configuration
    private $config = [
        'jpeg_quality' => 85,      // 0-100
        'png_compression' => 6,    // 0-9
        'max_width' => 1920,
        'max_height' => 1920,
        'create_webp' => true,
        'webp_quality' => 85
    ];
    
    /**
     * Compress an uploaded image
     * 
     * @param string $sourcePath - Path to source image
     * @param string $destPath - Path to save compressed image
     * @param array $customConfig - Optional custom settings
     * @return array - ['success' => bool, 'message' => string, 'webp_path' => string|null]
     */
    public function compress($sourcePath, $destPath, $customConfig = []) {
        // Merge custom config
        $config = array_merge($this->config, $customConfig);
        
        // Validate source file exists
        if (!file_exists($sourcePath)) {
            return ['success' => false, 'message' => 'Source file not found'];
        }
        
        // Get image info
        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            return ['success' => false, 'message' => 'Invalid image file'];
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Create image resource from source
        $sourceImage = $this->createImageFromFile($sourcePath, $type);
        if ($sourceImage === false) {
            return ['success' => false, 'message' => 'Failed to load image'];
        }
        
        // Calculate new dimensions if resizing needed
        $newDimensions = $this->calculateDimensions(
            $width, 
            $height, 
            $config['max_width'], 
            $config['max_height']
        );
        
        // Resize if needed
        if ($newDimensions['width'] !== $width || $newDimensions['height'] !== $height) {
            $resizedImage = $this->resizeImage(
                $sourceImage, 
                $width, 
                $height, 
                $newDimensions['width'], 
                $newDimensions['height']
            );
            imagedestroy($sourceImage);
            $sourceImage = $resizedImage;
        }
        
        // Save compressed image
        $saved = $this->saveImage(
            $sourceImage, 
            $destPath, 
            $type, 
            $config['jpeg_quality'], 
            $config['png_compression']
        );
        
        if (!$saved) {
            imagedestroy($sourceImage);
            return ['success' => false, 'message' => 'Failed to save compressed image'];
        }
        
        // Generate WebP version if enabled
        $webpPath = null;
        if ($config['create_webp'] && function_exists('imagewebp')) {
            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $destPath);
            imagewebp($sourceImage, $webpPath, $config['webp_quality']);
        }
        
        imagedestroy($sourceImage);
        
        return [
            'success' => true, 
            'message' => 'Image compressed successfully',
            'webp_path' => $webpPath,
            'original_size' => filesize($sourcePath),
            'compressed_size' => filesize($destPath),
            'savings' => round((1 - filesize($destPath) / filesize($sourcePath)) * 100, 2) . '%'
        ];
    }
    
    /**
     * Compress an uploaded file ($_FILES array)
     * 
     * @param array $uploadedFile - $_FILES['field_name']
     * @param string $destPath - Destination path
     * @param array $customConfig - Optional custom settings
     * @return array - Result array
     */
    public function compressUpload($uploadedFile, $destPath, $customConfig = []) {
        if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file($uploadedFile['tmp_name'])) {
            return ['success' => false, 'message' => 'Invalid upload'];
        }
        
        return $this->compress($uploadedFile['tmp_name'], $destPath, $customConfig);
    }
    
    /**
     * Create image resource from file
     */
    private function createImageFromFile($path, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return @imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return @imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return @imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
            default:
                return false;
        }
    }
    
    /**
     * Calculate new dimensions maintaining aspect ratio
     */
    private function calculateDimensions($width, $height, $maxWidth, $maxHeight) {
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return ['width' => $width, 'height' => $height];
        }
        
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        
        return [
            'width' => (int)round($width * $ratio),
            'height' => (int)round($height * $ratio)
        ];
    }
    
    /**
     * Resize image
     */
    private function resizeImage($sourceImage, $sourceWidth, $sourceHeight, $destWidth, $destHeight) {
        $destImage = imagecreatetruecolor($destWidth, $destHeight);
        
        // Preserve transparency for PNG
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
        $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
        imagefilledrectangle($destImage, 0, 0, $destWidth, $destHeight, $transparent);
        
        imagecopyresampled(
            $destImage, $sourceImage,
            0, 0, 0, 0,
            $destWidth, $destHeight,
            $sourceWidth, $sourceHeight
        );
        
        return $destImage;
    }
    
    /**
     * Save image to file
     */
    private function saveImage($image, $path, $type, $jpegQuality, $pngCompression) {
        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $path, $jpegQuality);
            case IMAGETYPE_PNG:
                return imagepng($image, $path, $pngCompression);
            case IMAGETYPE_GIF:
                return imagegif($image, $path);
            case IMAGETYPE_WEBP:
                return function_exists('imagewebp') ? imagewebp($image, $path, $jpegQuality) : false;
            default:
                return false;
        }
    }
}

/**
 * Helper function for quick compression
 * 
 * @param string $source - Source file path or $_FILES array
 * @param string $dest - Destination path
 * @param array $config - Optional config
 * @return array - Result
 */
function compressImage($source, $dest, $config = []) {
    $compressor = new ImageCompressor();
    
    // Check if $source is uploaded file array
    if (is_array($source) && isset($source['tmp_name'])) {
        return $compressor->compressUpload($source, $dest, $config);
    }
    
    return $compressor->compress($source, $dest, $config);
}
