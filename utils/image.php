<?php
class ImageUtil {
    // Upload directory
    private static $upload_dir = '../uploads/profile_pictures/';
    private static $thumbnail_dir = '../uploads/profile_pictures/thumbnails/';
    
    // Allowed file types
    private static $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    // Maximum file size (5MB)
    private static $max_size = 5 * 1024 * 1024;
    
    // Thumbnail dimensions
    private static $thumbnail_width = 150;
    private static $thumbnail_height = 150;
    
    // Initialize directories
    public static function init() {
        // Create upload directories if they don't exist
        if (!file_exists(self::$upload_dir)) {
            mkdir(self::$upload_dir, 0755, true);
        }
        
        if (!file_exists(self::$thumbnail_dir)) {
            mkdir(self::$thumbnail_dir, 0755, true);
        }
    }
    
    // Upload profile picture
    public static function uploadProfilePicture($file, $user_id) {
        self::init();
        
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['error' => 'No file uploaded'];
        }
        
        // Check file size
        if ($file['size'] > self::$max_size) {
            return ['error' => 'File is too large. Maximum size is 5MB'];
        }
        
        // Check file type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $file_type = $finfo->file($file['tmp_name']);
        
        if (!in_array($file_type, self::$allowed_types)) {
            return ['error' => 'Invalid file type. Allowed types: JPEG, PNG, GIF'];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $user_id . '_' . time() . '.' . $extension;
        $filepath = self::$upload_dir . $filename;
        $thumbnail_path = self::$thumbnail_dir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['error' => 'Failed to upload file'];
        }
        
        // Create thumbnail
        self::createThumbnail($filepath, $thumbnail_path);
        
        // Return file paths
        return [
            'success' => true,
            'profile_picture' => 'uploads/profile_pictures/' . $filename,
            'thumbnail' => 'uploads/profile_pictures/thumbnails/' . $filename
        ];
    }
    
    // Create thumbnail
    private static function createThumbnail($source_path, $thumbnail_path) {
        // Get image dimensions
        list($width, $height) = getimagesize($source_path);
        
        // Calculate thumbnail dimensions (maintain aspect ratio)
        $ratio = min(self::$thumbnail_width / $width, self::$thumbnail_height / $height);
        $new_width = $width * $ratio;
        $new_height = $height * $ratio;
        
        // Create thumbnail image
        $thumbnail = imagecreatetruecolor($new_width, $new_height);
        
        // Load source image based on file type
        $source_image = null;
        $extension = pathinfo($source_path, PATHINFO_EXTENSION);
        
        switch (strtolower($extension)) {
            case 'jpeg':
            case 'jpg':
                $source_image = imagecreatefromjpeg($source_path);
                break;
            case 'png':
                $source_image = imagecreatefrompng($source_path);
                // Preserve transparency
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                break;
            case 'gif':
                $source_image = imagecreatefromgif($source_path);
                break;
        }
        
        // Resize image
        imagecopyresampled($thumbnail, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Save thumbnail
        switch (strtolower($extension)) {
            case 'jpeg':
            case 'jpg':
                imagejpeg($thumbnail, $thumbnail_path, 90);
                break;
            case 'png':
                imagepng($thumbnail, $thumbnail_path, 9);
                break;
            case 'gif':
                imagegif($thumbnail, $thumbnail_path);
                break;
        }
        
        // Free memory
        imagedestroy($source_image);
        imagedestroy($thumbnail);
    }
    
    // Delete profile picture
    public static function deleteProfilePicture($filepath) {
        if (empty($filepath)) {
            return;
        }
        
        // Get full paths
        $full_path = '../' . $filepath;
        $thumbnail_path = str_replace('profile_pictures/', 'profile_pictures/thumbnails/', $full_path);
        
        // Delete files if they exist
        if (file_exists($full_path)) {
            unlink($full_path);
        }
        
        if (file_exists($thumbnail_path)) {
            unlink($thumbnail_path);
        }
    }
}
?>
