<?php
namespace App\Core;

/**
 * Safe image upload handling for product photos / logos.
 */
class Upload
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /**
     * Handle a single uploaded image. Returns the public relative path
     * (e.g. "uploads/12/abc.jpg") or null on no-file / failure.
     */
    public static function image(string $field, int $tenantId): ?string
    {
        if (empty($_FILES[$field]['tmp_name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $file = $_FILES[$field];
        $max  = (int) config('app.max_upload');
        if ($file['size'] > $max) {
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!isset(self::ALLOWED[$mime])) {
            return null;
        }
        $ext = self::ALLOWED[$mime];

        $base = rtrim((string) config('app.upload_dir'), '/');
        $dir  = $base . '/' . $tenantId;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }
        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }
        return 'uploads/' . $tenantId . '/' . $name;
    }

    public static function delete(?string $relPath): void
    {
        if (!$relPath) {
            return;
        }
        $base = dirname((string) config('app.upload_dir'));
        $full = $base . '/' . ltrim($relPath, '/');
        // only delete inside the uploads dir
        $uploads = realpath((string) config('app.upload_dir'));
        $target  = realpath($full);
        if ($uploads && $target && str_starts_with($target, $uploads) && is_file($target)) {
            @unlink($target);
        }
    }
}
