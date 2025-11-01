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

        // Normalize $_FILES array structure
        $fileCount = count($files['name']);

        if ($fileCount > $maxImages) {
            return [
                'success' => false,
                'error' => "Maximum {$maxImages} images allowed"
            ];
        }

        for ($i = 0; $i < $fileCount; $i++) {
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

        return [
            'success' => !empty($uploaded),
            'files' => $uploaded,
            'errors' => $errors
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




}
