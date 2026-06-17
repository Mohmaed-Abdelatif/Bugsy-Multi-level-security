<?php
// difference from V1:
//-strict MIME type validation using finfo (reads actual file bytes, not claimed type)
//- file extension whitelist — only jpg, jpeg, png, webp allowed
//- extension stripped from stored filename — random UUID only (no .php possible)
//- size limit: 5MB products, 2MB avatars (V1 was 100)
//- directory permissions: 0755 not 0777
//- path traversal protection on delete
//- double extension attack prevention (.php.jpg → rejected)
//- base64: content validated before saving, not after

namespace Helpers\V2;

class ImageUpload
{
    //---------------------
    // Configuration
    //---------------------

    private static string $productUploadDir = 'uploads/products/';
    private static string $userUploadDir    = 'uploads/users/';

    // V2: only real image MIME types accepted
    // finfo reads the actual file bytes — cannot be spoofed by renaming
    private static array $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    // V2: strict size limits
    private static int $maxProductSize = 5 * 1024 * 1024;  // 5MB
    private static int $maxUserSize    = 2 * 1024 * 1024;  // 2MB


    //----------------------------
    // PRODUCT IMAGE UPLOADS
    //----------------------------

    //upload single product image
    public static function upload(array $file): array
    {
        // Basic upload error check
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return self::fail('No file uploaded');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return self::fail('Upload error code: ' . $file['error']);
        }

        // Size check — 5MB max
        if ($file['size'] > self::$maxProductSize) {
            return self::fail('File too large. Maximum allowed size is 5MB');
        }

        // MIME validation — reads actual file bytes
        $mime = self::getRealMime($file['tmp_name']);

        if (!$mime || !isset(self::$allowedMimes[$mime])) {
            return self::fail('Invalid file type. Only JPEG, PNG, and WebP images are allowed');
        }

        // Double extension attack check (.php.jpg, .php.png, etc.)
        // Original filename must not contain a dangerous extension anywhere
        if (self::hasDangerousExtension($file['name'])) {
            return self::fail('File name contains a disallowed extension');
        }

        // Generate safe filename — UUID + validated extension only
        // The original filename is never used
        $extension = self::$allowedMimes[$mime];
        $filename  = self::generateSafeFilename('product', $extension);

        // Ensure upload directory exists with secure permissions
        $uploadPath = PUBLIC_PATH . '/' . self::$productUploadDir;
        self::ensureDirectory($uploadPath);

        $destination = $uploadPath . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return self::fail('Failed to save file');
        }

        return [
            'success'  => true,
            'filename' => $filename,
            'url'      => self::getUrl($filename)
        ];
    }


    // Upload multiple product images
    public static function uploadMultiple(array $files, int $maxImages = 5): array
    {
        // V2: max 5 images (V1 allowed 10)
        $uploaded = [];
        $errors   = [];

        if (empty($files)) {
            return ['success' => false, 'files' => [], 'errors' => ['No files provided']];
        }

        $isStandard = isset($files['name']) && is_array($files['name']);

        if ($isStandard) {
            $fileCount = count($files['name']);

            if ($fileCount > $maxImages) {
                return ['success' => false, 'files' => [], 'errors' => ["Maximum {$maxImages} images allowed"]];
            }

            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

                $result = self::upload([
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                ]);

                if ($result['success']) {
                    $uploaded[] = $result['filename'];
                } else {
                    $errors[] = $result['error'];
                }
            }
        } else {
            if (count($files) > $maxImages) {
                return ['success' => false, 'files' => [], 'errors' => ["Maximum {$maxImages} images allowed"]];
            }

            foreach ($files as $i => $file) {
                if (!is_array($file) || $file['error'] === UPLOAD_ERR_NO_FILE) continue;

                $result = self::upload($file);

                if ($result['success']) {
                    $uploaded[] = $result['filename'];
                } else {
                    $errors[] = "File {$i}: " . $result['error'];
                }
            }
        }

        return [
            'success'        => !empty($uploaded),
            'files'          => $uploaded,
            'errors'         => $errors,
            'uploaded_count' => count($uploaded),
            'failed_count'   => count($errors),
        ];
    }


    // Upload base64-encoded product image
    public static function uploadBase64(string $base64Image): array
    {
        //striping data URI prefix: "data:image/jpeg;base64,..."
        if (preg_match('/^data:image\/\w+;base64,/', $base64Image)) {
            $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
        }

        $imageData = base64_decode($base64Image, true);

        if ($imageData === false || empty($imageData)) {
            return self::fail('Invalid base64 data');
        }

        // size check before writing anything to disk
        if (strlen($imageData) > self::$maxProductSize) {
            return self::fail('Image too large. Maximum allowed size is 5MB');
        }

        // Validate actual content — write to temp file so finfo can read it
        $tmpFile = tempnam(sys_get_temp_dir(), 'bugsy_');
        file_put_contents($tmpFile, $imageData);

        $mime = self::getRealMime($tmpFile);
        unlink($tmpFile); // clean up temp file immediately

        if (!$mime || !isset(self::$allowedMimes[$mime])) {
            return self::fail('Invalid image type. Only JPEG, PNG, and WebP are allowed');
        }

        $extension = self::$allowedMimes[$mime];
        $filename  = self::generateSafeFilename('product', $extension);

        $uploadPath = PUBLIC_PATH . '/' . self::$productUploadDir;
        self::ensureDirectory($uploadPath);

        if (file_put_contents($uploadPath . $filename, $imageData) === false) {
            return self::fail('Failed to save image');
        }

        return [
            'success'  => true,
            'filename' => $filename,
            'url'      => self::getUrl($filename)
        ];
    }


    //Delete product image — path traversal protected
    public static function delete(string $filename): bool
    {
        if (empty($filename)) return false;

        //striping any directory components — filename only, never a path
        $filename = basename($filename);

        $filePath = PUBLIC_PATH . '/' . self::$productUploadDir . $filename;

        // Confirm the resolved path is inside the upload directory
        $realUploadDir = realpath(PUBLIC_PATH . '/' . self::$productUploadDir);
        $realFilePath  = realpath($filePath);

        if (!$realFilePath || !str_starts_with($realFilePath, $realUploadDir)) {
            error_log("V2 ImageUpload: path traversal attempt blocked — {$filename}");
            return false;
        }

        return is_file($realFilePath) && unlink($realFilePath);
    }


    // Get product image URL
    public static function getUrl(string $filename = ''): string
    {
        if (empty($filename)) {
            return APP_URL . '/uploads/products/no-image.png';
        }

        return APP_URL . '/uploads/products/' . basename($filename);
    }


    //-----------------------------
    // USER PROFILE PHOTO UPLOADS
    //-----------------------------

    // Upload user profile photo
    public static function uploadUserPhoto(array $file, int $userId): array
    {
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return self::fail('No file uploaded');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return self::fail('Upload error code: ' . $file['error']);
        }

        // 2MB max for avatars
        if ($file['size'] > self::$maxUserSize) {
            return self::fail('File too large. Maximum allowed size for profile photos is 2MB');
        }

        $mime = self::getRealMime($file['tmp_name']);

        if (!$mime || !isset(self::$allowedMimes[$mime])) {
            return self::fail('Invalid file type. Only JPEG, PNG, and WebP images are allowed');
        }

        if (self::hasDangerousExtension($file['name'])) {
            return self::fail('File name contains a disallowed extension');
        }

        $extension = self::$allowedMimes[$mime];
        $filename  = self::generateSafeFilename('user_' . $userId, $extension);

        $uploadDir = PUBLIC_PATH . '/' . self::$userUploadDir;
        self::ensureDirectory($uploadDir);

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            return self::fail('Failed to save photo');
        }

        return [
            'success'  => true,
            'filename' => $filename,
            'error'    => null
        ];
    }


    // Upload base64-encoded user photo
    public static function uploadBase64UserPhoto(string $base64Data, int $userId): array
    {
        if (preg_match('/^data:image\/\w+;base64,/', $base64Data)) {
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        }

        $imageData = base64_decode($base64Data, true);

        if ($imageData === false || empty($imageData)) {
            return self::fail('Invalid base64 data');
        }

        if (strlen($imageData) > self::$maxUserSize) {
            return self::fail('Image too large. Maximum allowed size is 2MB');
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'bugsy_user_');
        file_put_contents($tmpFile, $imageData);

        $mime = self::getRealMime($tmpFile);
        unlink($tmpFile);

        if (!$mime || !isset(self::$allowedMimes[$mime])) {
            return self::fail('Invalid image type. Only JPEG, PNG, and WebP are allowed');
        }

        $extension = self::$allowedMimes[$mime];
        $filename  = self::generateSafeFilename('user_' . $userId, $extension);

        $uploadDir = PUBLIC_PATH . '/' . self::$userUploadDir;
        self::ensureDirectory($uploadDir);

        if (file_put_contents($uploadDir . $filename, $imageData) === false) {
            return self::fail('Failed to save image');
        }

        return [
            'success'  => true,
            'filename' => $filename,
            'error'    => null
        ];
    }


    // Delete user photo — path traversal protected
    public static function deleteUserPhoto(string $filename): bool
    {
        if (empty($filename)) return false;

        $filename = basename($filename);

        $filePath      = PUBLIC_PATH . '/' . self::$userUploadDir . $filename;
        $realUploadDir = realpath(PUBLIC_PATH . '/' . self::$userUploadDir);
        $realFilePath  = realpath($filePath);

        if (!$realFilePath || !str_starts_with($realFilePath, $realUploadDir)) {
            error_log("V2 ImageUpload: path traversal attempt blocked — {$filename}");
            return false;
        }

        return is_file($realFilePath) && unlink($realFilePath);
    }


    // Get user photo URL
    public static function getUserPhotoUrl(string $filename = ''): string
    {
        if (empty($filename)) {
            return APP_URL . '/uploads/users/default-avatar.png';
        }

        return APP_URL . '/uploads/users/' . basename($filename);
    }


    //---------------------
    // Private helpers
    //---------------------

    //read actual MIME type from file bytes using finfo
    //this cannot be spoofed by renaming a .php file to .jpg
    //finfo reads the file signature (magic bytes), not the name or claimed type
    private static function getRealMime(string $filePath): ?string
    {
        if (!file_exists($filePath)) return null;

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($filePath);

        return $mime ?: null;
    }


    //check if a filename contains any dangerous extension anywhere
    //blocks double-extension attacks: shell.php.jpg, backdoor.php5.png
    private static function hasDangerousExtension(string $filename): bool
    {
        $dangerous = [
            'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar',
            'exe', 'sh', 'bat', 'cmd', 'ps1',
            'js', 'py', 'rb', 'pl', 'asp', 'aspx', 'jsp',
            'htaccess', 'htpasswd', 'svg', 'xml', 'html', 'htm',
        ];

        //check every dot-separated segment, not just the last one
        $parts = explode('.', strtolower($filename));

        foreach ($parts as $part) {
            if (in_array($part, $dangerous, true)) {
                return true;
            }
        }

        return false;
    }


    // Generate a cryptographically random filename
    // Format: prefix_<16 random hex chars>.<extension>
    // The original filename is NEVER used
    private static function generateSafeFilename(string $prefix, string $extension): string
    {
        return $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }


    //create directory with secure permissions
    // 0755: owner can read/write/execute, others can only read/execute
    // V1 used 0777 — anyone on the server could modify upload files
    private static function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }


    // Consistent failure response shape
    private static function fail(string $message): array
    {
        return [
            'success'  => false,
            'filename' => null,
            'error'    => $message
        ];
    }
}