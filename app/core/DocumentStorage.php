<?php
/**
 * Almacenamiento de documentos en disco (fuera de public/).
 * Imágenes: conversión automática a WebP + redimensionado prudente para ahorrar espacio.
 * PDF: se guarda sin alterar (ya comprimido internamente).
 */
class DocumentStorage {

    public const MAX_BYTES = 10485760; // 10 MB

    /** Calidad WebP: 85 ≈ buen equilibrio tamaño/calidad para documentos escaneados */
    private const WEBP_QUALITY = 85;

    /** Lado máximo en píxeles (fotos de celular → tamaño razonable para pantalla/impresión A4) */
    private const MAX_IMAGE_DIMENSION = 2400;

    private const MIME_EXT = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    private const OPTIMIZABLE_IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public static function storageRoot(): string {
        return dirname(APPROOT) . DIRECTORY_SEPARATOR . 'storage';
    }

    public static function documentosRoot(): string {
        return self::storageRoot() . DIRECTORY_SEPARATOR . 'documentos';
    }

    public static function absolutePath(string $relativePath): string {
        $relativePath = str_replace(['\\', '..'], ['/', ''], $relativePath);
        return self::storageRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /** Valida que la ruta relativa pertenezca a la junta indicada. */
    public static function pathBelongsToJunta(string $relativePath, int $juntaId): bool {
        $normalized = str_replace('\\', '/', $relativePath);
        return (bool)preg_match('#^documentos/' . $juntaId . '/#', $normalized);
    }

    /** @return array{path:string,mime_type:string,tamano_bytes:int,archivo_nombre_original:string}|null */
    public static function storeUploadedFile(int $juntaId, array $file): ?array {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES || ($file['size'] ?? 0) <= 0) {
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset(self::MIME_EXT[$mime])) {
            return null;
        }

        $ext = self::MIME_EXT[$mime];
        $year = date('Y');
        $dir = self::documentosRoot() . DIRECTORY_SEPARATOR . $juntaId . DIRECTORY_SEPARATOR . $year;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return null;
        }

        $uuid = bin2hex(random_bytes(16));
        $filename = $uuid . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        $finalMime = $mime;
        $finalDest = $dest;
        $finalFilename = $filename;

        if (in_array($mime, self::OPTIMIZABLE_IMAGE_MIMES, true)) {
            $optimized = self::optimizeImageToWebp($dest, $mime);
            if ($optimized !== null) {
                $finalDest = $optimized['path'];
                $finalMime = 'image/webp';
                $finalFilename = basename($finalDest);
            }
        }

        $relative = 'documentos/' . $juntaId . '/' . $year . '/' . $finalFilename;
        $original = basename((string)($file['name'] ?? 'documento.' . $ext));
        $original = preg_replace('/[^\w\s\.\-áéíóúñÁÉÍÓÚÑ()]/u', '_', $original) ?: 'documento.' . $ext;

        return [
            'path' => $relative,
            'mime_type' => $finalMime,
            'tamano_bytes' => (int)filesize($finalDest),
            'archivo_nombre_original' => mb_substr($original, 0, 255),
        ];
    }

    /**
     * Convierte JPEG/PNG/WebP a WebP con calidad fija y redimensiona si excede MAX_IMAGE_DIMENSION.
     * @return array{path:string,size:int}|null
     */
    private static function optimizeImageToWebp(string $absPath, string $sourceMime): ?array {
        if (!extension_loaded('gd') || !function_exists('imagewebp')) {
            return null;
        }

        $image = self::loadImage($absPath, $sourceMime);
        if ($image === false) {
            return null;
        }

        if ($sourceMime === 'image/png') {
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $image = self::downscaleIfNeeded($image);
        if ($image === false) {
            return null;
        }

        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $absPath);
        if ($webpPath === $absPath) {
            $webpPath = $absPath . '.webp';
        }

        $saved = imagewebp($image, $webpPath, self::WEBP_QUALITY);
        imagedestroy($image);
        if (!$saved || !is_file($webpPath)) {
            return null;
        }

        $originalSize = (int)filesize($absPath);
        $newSize = (int)filesize($webpPath);

        // WebP ya subido: conservar el más liviano
        if ($sourceMime === 'image/webp' && $newSize >= $originalSize) {
            @unlink($webpPath);
            return null;
        }

        @unlink($absPath);
        return ['path' => $webpPath, 'size' => $newSize];
    }

    /** @return \GdImage|false */
    private static function loadImage(string $path, string $mime) {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    /** @param \GdImage $image */
    private static function downscaleIfNeeded($image) {
        $width = imagesx($image);
        $height = imagesy($image);
        if ($width <= 0 || $height <= 0) {
            imagedestroy($image);
            return false;
        }

        $maxSide = max($width, $height);
        if ($maxSide <= self::MAX_IMAGE_DIMENSION) {
            return $image;
        }

        $ratio = self::MAX_IMAGE_DIMENSION / $maxSide;
        $newW = max(1, (int)round($width * $ratio));
        $newH = max(1, (int)round($height * $ratio));

        $resized = imagecreatetruecolor($newW, $newH);
        if ($resized === false) {
            return $image;
        }

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $newW, $newH, $transparent);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($image);
        return $resized;
    }

    public static function deleteRelativePath(?string $relativePath): void {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        $abs = self::absolutePath($relativePath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    public static function formatBytes(int $bytes): string {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 1) . ' MB';
    }

    public static function isPreviewable(string $mime): bool {
        return $mime === 'application/pdf' || str_starts_with($mime, 'image/');
    }

    public static function supportsImageOptimization(): bool {
        return extension_loaded('gd') && function_exists('imagewebp');
    }
}
