<?php
/**
 * ImageOptimizer.php - Auto optimize uploaded images
 * Convert to WebP + Resize + Compress
 */

class ImageOptimizer {
    
    public static function optimize($sourcePath, $destPath = null, $maxWidth = 1920, $quality = 85) {
        if (!file_exists($sourcePath)) {
            return ['success' => false, 'error' => 'File not found'];
        }
        
        $destPath = $destPath ?? $sourcePath;
        $info = getimagesize($sourcePath);
        
        if (!$info) {
            return ['success' => false, 'error' => 'Invalid image'];
        }
        
        $mime = $info['mime'];
        $width = $info[0];
        $height = $info[1];
        $originalSize = filesize($sourcePath);
        
        // Skip optimization jika file sudah sangat kecil (< 100KB)
        if ($originalSize < 100 * 1024) {
            return [
                'success' => true,
                'original_size' => $originalSize,
                'optimized_size' => $originalSize,
                'savings_percent' => 0,
                'skipped' => true
            ];
        }
        
        // Calculate new dimensions
        if ($width > $maxWidth) {
            $ratio = $maxWidth / $width;
            $newWidth = $maxWidth;
            $newHeight = (int)($height * $ratio);
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
        
        // Load image based on type
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($sourcePath);
                break;
            case 'image/gif':
                return ['success' => true, 'original_size' => $originalSize, 'optimized_size' => $originalSize, 'savings_percent' => 0, 'skipped' => true];
            default:
                return ['success' => false, 'error' => 'Unsupported format'];
        }
        
        if (!$image) {
            return ['success' => false, 'error' => 'Failed to load image'];
        }
        
        // Create new image with new dimensions
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($mime === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save optimized image
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($newImage, $destPath, $quality);
                break;
            case 'image/png':
                imagepng($newImage, $destPath, 6); // 6 is good balance for PNG
                break;
            case 'image/webp':
                imagewebp($newImage, $destPath, $quality);
                break;
        }
        
        imagedestroy($image);
        imagedestroy($newImage);
        
        $optimizedSize = filesize($destPath);
        $savings = $originalSize > 0 ? round((1 - $optimizedSize / $originalSize) * 100, 2) : 0;
        
        return [
            'success' => true,
            'path' => $destPath,
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'savings_percent' => $savings
        ];
    }
}