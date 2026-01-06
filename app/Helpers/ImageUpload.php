<?php
//handles file uploads, validation, and storage
namespace Helpers;

class ImageUpload
{
    // Upload directory (relative to public folder)
    private static $uploadDir = 'uploads/products/';

    // Allowed file types
    private static $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    // Max file size (5MB)
    private static $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes

    //upload single image
    public static function upload($file)
    {
        // Check if file was uploaded
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return [
                'success' => false,
                'error' => 'No file uploaded'
            ];
        }    

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => 'File upload error: ' . $file['error']
            ];
        }

        // Validate file size
        if ($file['size'] > self::$maxFileSize) {
            return [
                'success' => false,
                'error' => 'File too large. Max size: 5MB'
            ];
        }

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::$allowedTypes)) {
            return [
                'success' => false,
                'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP'
            ];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('product_', true) . '.' . $extension;

        // Create upload directory if not exists
        $uploadPath = PUBLIC_PATH . '/' . self::$uploadDir;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Move uploaded file
        $destination = $uploadPath . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => true,
                'filename' =>  $filename,
                'full_path' => $destination
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to save file'
        ];
    }

    //upload multiple images
    public static function uploadMultiple($files, $maxImages=5)
    {
        $uploaded = [];
        $errors = [];

         
        // Check if files array is empty
        if (empty($files)) {
            return [
                'success' => false,
                'files' => [],
                'errors' => ['No files provided']
            ];
        }

        // Detect file array structure
        $isStandardStructure = isset($files['name']) && is_array($files['name']);

        if ($isStandardStructure) {
            // Standard PHP $_FILES structure
            // $_FILES['images'] = ['name' => [...], 'type' => [...], ...]

            $fileCount = count($files['name']);

            if ($fileCount > $maxImages) {
                return [
                    'success' => false,
                    'files' => [],
                    'errors' => ["Maximum {$maxImages} images allowed, got {$fileCount}"]
                ];
            }

            for ($i = 0; $i < $fileCount; $i++) {
                // Skip if no file uploaded at this index
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];

                $result = self::upload($file);

                if ($result['success']) {
                    $uploaded[] = $result['filename'];
                } else {
                    $errors[] = $result['error'];
                }
            }
        }else {
            // Already normalized structure (array of file arrays)
            // [['name' => 'img1.jpg', ...], ['name' => 'img2.jpg', ...]]
            
            $fileCount = count($files);
            
            if ($fileCount > $maxImages) {
                return [
                    'success' => false,
                    'files' => [],
                    'errors' => ["Maximum {$maxImages} images allowed, got {$fileCount}"]
                ];
            }

            // Process each file
            foreach ($files as $index => $file) {
                // Skip if not a valid file array
                if (!is_array($file) || !isset($file['name'])) {
                    $errors[] = "File {$index}: Invalid file data";
                    continue;
                }

                // Skip if no file uploaded
                if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                // Upload individual file
                $result = self::upload($file);

                if ($result['success']) {
                    $uploaded[] = $result['filename'];
                } else {
                    $errors[] = "File {$index}: " . $result['error'];
                }
            }
        }
    
        // Return results
        return [
            'success' => !empty($uploaded),
            'files' => $uploaded,
            'errors' => $errors,
            'uploaded_count' => count($uploaded),
            'failed_count' => count($errors)
        ];
    }

    //delete image file
    public static function delete($filename)
    {
        if (empty($filename)) {
            return false;
        }

        $filePath = PUBLIC_PATH . '/' . self::$uploadDir . $filename;

        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    //get full url for image
    public static function getUrl($filename)
    {
        if (empty($filename)) {
            return APP_URL . '/public/uploads/products/no-image.png';
        }

        return APP_URL . '/public/uploads/products/' . $filename;
    }

    //validate image from base64 (for future JSON uploads)
    public static function uploadBase64($base64Image)
    {
        // Remove data URI prefix if present
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid image type'
                ];
            }

            $base64Image = base64_decode($base64Image);

            if ($base64Image === false) {
                return [
                    'success' => false,
                    'error' => 'Base64 decode failed'
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => 'Invalid base64 image format'
            ];
        }

        // Generate unique filename
        $filename = uniqid('product_', true) . '.' . $type;
        $uploadPath = PUBLIC_PATH . '/' . self::$uploadDir;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $destination = $uploadPath . $filename;

        if (file_put_contents($destination, $base64Image)) {
            return [
                'success' => true,
                'filename' => self::$uploadDir . $filename
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to save image'
        ];
    }



    //----------------------------------
    // user image upload
    //----------------------------------

    //upload user profille photo
    public static function uploadUserPhoto($file, $userId)
    {
        // Use same validation as product images
        $instance = new self();
        
        // Validate file exists
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'No file uploaded'
            ];
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'filename' => null,
                'error' => $instance->getUploadErrorMessage($file['error'])
            ];
        }
        
        // Validate file size (2MB max for profile photos)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'File size exceeds 2MB limit'
            ];
        }
        
        // Validate MIME type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Invalid file type. Only JPEG, PNG, and WebP allowed'
            ];
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'user_' . $userId . '_' . time() . '_' . uniqid() . '.' . $extension;
        
        // Upload directory for user photos
        $uploadDir = __DIR__ . '/../../public/uploads/users/';
        $uploadPath = $uploadDir . $filename;
        
        // Create directory if doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Failed to move uploaded file'
            ];
        }
        
        // Log success
        if (APP_ENV === 'development') {
            error_log("User photo uploaded: {$filename}");
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'error' => null
        ];
    }

    //upoad user profile photo from base64
    public static function uploadBase64UserPhoto($base64Data, $userId)
    {
        // Remove data URI prefix if present
        if (strpos($base64Data, 'data:image') === 0) {
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        }
        
        // Decode base64
        $imageData = base64_decode($base64Data);
        
        if ($imageData === false) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Invalid base64 data'
            ];
        }
        
        // Validate size (2MB max)
        $maxSize = 2 * 1024 * 1024;
        if (strlen($imageData) > $maxSize) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Image size exceeds 2MB limit'
            ];
        }
        
        // Detect image type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        
        // Validate MIME type
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];
        
        if (!isset($allowedTypes[$mimeType])) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Invalid image type'
            ];
        }
        
        $extension = $allowedTypes[$mimeType];
        
        // Generate filename
        $filename = 'user_' . $userId . '_' . time() . '_' . uniqid() . '.' . $extension;
        
        // Upload directory
        $uploadDir = __DIR__ . '/../../public/uploads/users/';
        $uploadPath = $uploadDir . $filename;
        
        // Create directory if doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Save file
        if (file_put_contents($uploadPath, $imageData) === false) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Failed to save image'
            ];
        }
        
        // Log success
        if (APP_ENV === 'development') {
            error_log("User photo uploaded (base64): {$filename}");
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'error' => null
        ];
    }

    //delete user profile photo
    public static function deleteUserPhoto($filename)
    {
        if (empty($filename)) {
            return false;
        }
        
        $uploadDir = __DIR__ . '/../../public/uploads/users/';
        $filePath = $uploadDir . $filename;
        
        // Check if file exists
        if (!file_exists($filePath)) {
            error_log("User photo not found for deletion: {$filePath}");
            return false;
        }
        
        // Delete file
        if (unlink($filePath)) {
            if (APP_ENV === 'development') {
                error_log("User photo deleted: {$filename}");
            }
            return true;
        }
        
        error_log("Failed to delete user photo: {$filePath}");
        return false;
    }

    //get full url for user profile photo
    public static function getUserPhotoUrl($filename)
    {
        if (empty($filename)) {
            return APP_URL . '/uploads/users/no-image.png';
        }
        
        return APP_URL . '/uploads/users/' . $filename;
    }

    //get upload error message
    private function getUploadErrorMessage($errorCode)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        return $errors[$errorCode] ?? 'Unknown upload error';
    }


}
