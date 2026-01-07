<?php
//handles file uploads, validation, and storage
/**
 * V1 ImageUpload Helper - Intentionally Vulnerable
 * V1 VULNERABILITIES (By Design):
 * - No file type validation (any file can be uploaded!)
 * V2 FIXES: Strict file type validation, content checking
*/
namespace Helpers\V1;

class ImageUpload
{
    // Upload directories
    private static $productUploadDir = 'uploads/products/';
    private static $userUploadDir = 'uploads/users/';

    // V1: NO file type restrictions (VULNERABLE!)

    // V1: NO size limit enforcement (VULNERABLE!)
    private static $maxFileSize = 100 * 1024 * 1024; // 100MB (too large!)

    //=================================================================
    // PRODUCT IMAGE UPLOADS
    //=================================================================
    /**
     * Upload single product image
     * V1: VULNERABLE - Accepts any file type!
    */
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

        // V1: Weak size check (accepts very large files)
        if ($file['size'] > self::$maxFileSize) {
            return [
                'success' => false,
                'error' => 'File too large. Max size: 100MB'
            ];
        }

        // V1: NO FILE TYPE VALIDATION! (CRITICAL VULNERABILITY!)
        // Any file extension is accepted: .php, .exe, .sh, .bat, etc.
        
        
        // Generate filename (V1: Predictable pattern - VULNERABLE!)
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('product_', true) . '.' . $extension;

        // V2 TODO: Sanitize extension, use random names
        // V3 TODO: Remove extension completely, use UUID

        // Create upload directory if not exists
        $uploadPath = PUBLIC_PATH . '/' . self::$productUploadDir;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true); // V1: 0777 not secure!
            // V2: Use 0755 permissions
        }

        // Move uploaded file
        $destination = $uploadPath . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            
            
            return [
                'success' => true,
                'filename' => $filename,
                'full_path' => $destination
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to save file'
        ];
    }

    //Upload multiple product images
    public static function uploadMultiple($files, $maxImages = 10)
    {
        // V1: Allows 10 images (too many - DoS risk)
        // V2: Limit to 5 images
        
        $uploaded = [];
        $errors = [];

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
            $fileCount = count($files['name']);

            if ($fileCount > $maxImages) {
                return [
                    'success' => false,
                    'files' => [],
                    'errors' => ["Maximum {$maxImages} images allowed"]
                ];
            }

            for ($i = 0; $i < $fileCount; $i++) {
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
        } else {
            $fileCount = count($files);

            if ($fileCount > $maxImages) {
                return [
                    'success' => false,
                    'files' => [],
                    'errors' => ["Maximum {$maxImages} images allowed"]
                ];
            }

            foreach ($files as $index => $file) {
                if (!is_array($file) || !isset($file['name'])) {
                    $errors[] = "File {$index}: Invalid file data";
                    continue;
                }

                if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $result = self::upload($file);

                if ($result['success']) {
                    $uploaded[] = $result['filename'];
                } else {
                    $errors[] = "File {$index}: " . $result['error'];
                }
            }
        }

        return [
            'success' => !empty($uploaded),
            'files' => $uploaded,
            'errors' => $errors,
            'uploaded_count' => count($uploaded),
            'failed_count' => count($errors)
        ];
    }

    //Upload base64 encoded image
    public static function uploadBase64($base64Image)
    {
        // V1: Weak validation (VULNERABLE!)
        
        // Remove data URI prefix if present
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]);

            // V1: Accepts any image type claim (not validated!)
            // Attacker can send: data:image/jpg;base64,<php_code_base64>
            
        } else {
            // V1: Still accepts even without proper format!
            $type = 'unknown';
        }

        $imageData = base64_decode($base64Image);

        if ($imageData === false) {
            return [
                'success' => false,
                'error' => 'Base64 decode failed'
            ];
        }

        // V1: NO content validation (VULNERABLE!)
        // Can contain PHP code, malware, etc.

        // Generate filename
        $filename = uniqid('product_', true) . '.' . $type;
        $uploadPath = PUBLIC_PATH . '/' . self::$productUploadDir;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $destination = $uploadPath . $filename;

        if (file_put_contents($destination, $imageData)) {
            return [
                'success' => true,
                'filename' => $filename
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to save image'
        ];
    }

    //Delete product image
    public static function delete($filename)
    {
        
        if (empty($filename)) {
            return false;
        }

        $filePath = PUBLIC_PATH . '/' . self::$productUploadDir . $filename;

        
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    
    public static function getUrl($filename)
    {
        if (empty($filename)) {
            return APP_URL . '/uploads/products/no-image.png';
        }

        return APP_URL . '/uploads/products/' . $filename;
    }



    //=================================================================
    // USER PROFILE PHOTO UPLOADS
    //=================================================================

    
    public static function uploadUserPhoto($file, $userId)
    {
        // V1: Same vulnerabilities as product upload
        
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'No file uploaded'
            ];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Upload error: ' . $file['error']
            ];
        }

        // V1: Large file size allowed (10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'File size exceeds 10MB limit'
            ];
        }

        // V1: NO FILE TYPE VALIDATION!
        // Can upload .php, .exe, etc. as "profile photo"
        
        // Generate filename (predictable)
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'user_' . $userId . '_' . time() . '.' . $extension;

        // Upload directory
        $uploadDir = PUBLIC_PATH . '/' . self::$userUploadDir;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // V1: Insecure permissions
        }

        $uploadPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Failed to move uploaded file'
            ];
        }

        return [
            'success' => true,
            'filename' => $filename,
            'error' => null
        ];
    }

    
    public static function uploadBase64UserPhoto($base64Data, $userId)
    {
        // V1: Weak validation
        
        if (strpos($base64Data, 'data:image') === 0) {
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        }

        $imageData = base64_decode($base64Data);

        if ($imageData === false) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Invalid base64 data'
            ];
        }

        // V1: Large size allowed
        $maxSize = 10 * 1024 * 1024;
        if (strlen($imageData) > $maxSize) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Image size exceeds 10MB limit'
            ];
        }

        // V1: Weak type detection (can be fooled)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);

        // V1: Only checks claimed MIME type (VULNERABLE!)
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];

        $extension = $allowedTypes[$mimeType] ?? 'bin';

        // Generate filename
        $filename = 'user_' . $userId . '_' . time() . '.' . $extension;

        // Upload directory
        $uploadDir = PUBLIC_PATH . '/' . self::$userUploadDir;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $uploadPath = $uploadDir . $filename;

        if (file_put_contents($uploadPath, $imageData) === false) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Failed to save image'
            ];
        }

        return [
            'success' => true,
            'filename' => $filename,
            'error' => null
        ];
    }

    
    public static function deleteUserPhoto($filename)
    {
        if (empty($filename)) {
            return false;
        }

        $uploadDir = PUBLIC_PATH . '/' . self::$userUploadDir;
        $filePath = $uploadDir . $filename;

        // V1: Anyone can delete anyone's photo!
        
        if (!file_exists($filePath)) {
            return false;
        }

        return unlink($filePath);
    }

    
    public static function getUserPhotoUrl($filename)
    {
        if (empty($filename)) {
            return APP_URL . '/uploads/users/default-avatar.png';
        }

        return APP_URL . '/uploads/users/' . $filename;
    }
}
