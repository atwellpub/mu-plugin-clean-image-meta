<?php
/**
 * Plugin Name: Clean Image Metadata
 * Description: Automatically removes all metadata (EXIF, IPTC, XMP, etc.) from uploaded images.
 * Version: 1.0.0
 * Author: atwellpub
 * Contributors: Hudson Atwell, Claude 3.7 Thinking
 * Text Domain: clean-image-meta
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to handle image metadata cleaning
 */
class Clean_Image_Meta {

    /**
     * Initialize the plugin
     */
    public function __construct() {
        // Hook into WordPress upload process
        add_filter('wp_handle_upload', array($this, 'clean_image_metadata'), 10, 2);
        
        // Add filter for existing images when they're processed
        add_filter('wp_generate_attachment_metadata', array($this, 'clean_generated_metadata'), 10, 2);
    }

    /**
     * Clean metadata from uploaded images
     *
     * @param array $file Array of uploaded file data
     * @param string $context The type of upload action
     * @return array Modified file data
     */
    public function clean_image_metadata($file, $context) {
        // Only process if it's an image upload
        if ($context !== 'upload' || !isset($file['type']) || strpos($file['type'], 'image/') !== 0) {
            return $file;
        }

        // Clean the image file
        $this->process_image($file['file']);

        return $file;
    }

    /**
     * Clean metadata from generated image sizes
     *
     * @param array $metadata Attachment metadata
     * @param int $attachment_id Attachment ID
     * @return array Modified metadata
     */
    public function clean_generated_metadata($metadata, $attachment_id) {
        // Skip if not an image
        if (!isset($metadata['file'])) {
            return $metadata;
        }

        $upload_dir = wp_upload_dir();
        $base_file = trailingslashit($upload_dir['basedir']) . $metadata['file'];

        // Clean the main file
        $this->process_image($base_file);

        // Clean all generated sizes
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $base_dir = dirname($base_file) . '/';
            
            foreach ($metadata['sizes'] as $size) {
                if (isset($size['file'])) {
                    $this->process_image($base_dir . $size['file']);
                }
            }
        }

        return $metadata;
    }

    /**
     * Process an image to remove metadata
     *
     * @param string $file_path Full path to the image file
     * @return bool Success or failure
     */
    private function process_image($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        $file_type = wp_check_filetype($file_path);
        $mime_type = $file_type['type'];

        // Process based on image type
        switch ($mime_type) {
            case 'image/jpeg':
                return $this->clean_jpeg($file_path);
            
            case 'image/png':
                return $this->clean_png($file_path);
                
            case 'image/webp':
                return $this->clean_webp($file_path);
                
            case 'image/gif':
                return $this->clean_gif($file_path);
                
            default:
                // For unsupported types, we'll use a fallback method
                return $this->fallback_clean($file_path, $mime_type);
        }
    }

    /**
     * Clean metadata from JPEG images
     *
     * @param string $file_path Full path to the image file
     * @return bool Success or failure
     */
    private function clean_jpeg($file_path) {
        if (!function_exists('imagecreatefromjpeg')) {
            return false;
        }

        // Load the image
        $image = @imagecreatefromjpeg($file_path);
        if (!$image) {
            return false;
        }

        // Save without metadata
        $result = imagejpeg($image, $file_path, 90);
        imagedestroy($image);

        return $result;
    }

    /**
     * Clean metadata from PNG images
     *
     * @param string $file_path Full path to the image file
     * @return bool Success or failure
     */
    private function clean_png($file_path) {
        if (!function_exists('imagecreatefrompng')) {
            return false;
        }

        // Load the image
        $image = @imagecreatefrompng($file_path);
        if (!$image) {
            return false;
        }

        // Preserve transparency
        imagealphablending($image, false);
        imagesavealpha($image, true);

        // Save without metadata
        $result = imagepng($image, $file_path, 9);
        imagedestroy($image);

        return $result;
    }

    /**
     * Clean metadata from WebP images
     *
     * @param string $file_path Full path to the image file
     * @return bool Success or failure
     */
    private function clean_webp($file_path) {
        // Check for WebP support in GD
        if (!function_exists('imagecreatefromwebp')) {
            return $this->fallback_clean($file_path, 'image/webp');
        }

        // Load the image
        $image = @imagecreatefromwebp($file_path);
        if (!$image) {
            return false;
        }

        // Preserve transparency
        imagealphablending($image, false);
        imagesavealpha($image, true);

        // Save without metadata
        $result = imagewebp($image, $file_path, 80);
        imagedestroy($image);

        return $result;
    }

    /**
     * Clean metadata from GIF images
     *
     * @param string $file_path Full path to the image file
     * @return bool Success or failure
     */
    private function clean_gif($file_path) {
        if (!function_exists('imagecreatefromgif')) {
            return false;
        }

        // Load the image
        $image = @imagecreatefromgif($file_path);
        if (!$image) {
            return false;
        }

        // Save without metadata
        $result = imagegif($image, $file_path);
        imagedestroy($image);

        return $result;
    }

    /**
     * Fallback method for cleaning metadata
     * Uses ImageMagick if available
     *
     * @param string $file_path Full path to the image file
     * @param string $mime_type The mime type of the image
     * @return bool Success or failure
     */
    private function fallback_clean($file_path, $mime_type) {
        // Try using exec if available and allowed
        if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
            // Try ImageMagick's convert command
            $escaped_path = escapeshellarg($file_path);
            $output = null;
            $return_var = null;
            
            exec("convert $escaped_path -strip $escaped_path 2>&1", $output, $return_var);
            
            if ($return_var === 0) {
                return true;
            }
        }
        
        // If ImageMagick failed or isn't available, try a generic GD approach
        if (in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']) && 
            function_exists('imagecreatefromstring')) {
            
            $img_content = file_get_contents($file_path);
            if (!$img_content) {
                return false;
            }
            
            $image = @imagecreatefromstring($img_content);
            if (!$image) {
                return false;
            }
            
            // Determine output function based on mime type
            switch ($mime_type) {
                case 'image/jpeg':
                    $result = imagejpeg($image, $file_path, 90);
                    break;
                case 'image/png':
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    $result = imagepng($image, $file_path, 9);
                    break;
                case 'image/gif':
                    $result = imagegif($image, $file_path);
                    break;
                case 'image/webp':
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    $result = imagewebp($image, $file_path, 80);
                    break;
                default:
                    $result = false;
            }
            
            imagedestroy($image);
            return $result;
        }
        
        return false;
    }
}

// Initialize the plugin
new Clean_Image_Meta();
