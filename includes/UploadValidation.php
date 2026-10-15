<?php
declare(strict_types=1);

/**
 * Content-based upload validation shared by the public/admin image upload helpers.
 * This class deliberately does not check is_uploaded_file(); request handlers must do that
 * first, while unit tests can safely exercise these pure file-content checks with fixtures.
 */
final class UploadValidation
{
    /**
     * Return the detected MIME type for a valid raster image, or null on any mismatch.
     */
    public static function validatedRasterImageMime(
        string $path,
        string $extension,
        int $reportedSize,
        int $maxBytes = 5242880,
        int $maxPixels = 40000000
    ): ?string {
        $extension = strtolower($extension);
        $allowed = [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'webp' => ['image/webp'],
            'ico'  => ['image/vnd.microsoft.icon', 'image/x-icon'],
        ];

        if (!isset($allowed[$extension]) || !is_file($path) || !is_readable($path)
            || $maxBytes <= 0 || $maxPixels <= 0 || $reportedSize <= 0 || $reportedSize > $maxBytes) {
            return null;
        }
        $actualSize = filesize($path);
        if ($actualSize === false || $actualSize <= 0 || $actualSize > $maxBytes || $actualSize !== $reportedSize) {
            return null;
        }

        if (!function_exists('finfo_open')) {
            return null;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);
        if (!is_string($mime) || !in_array($mime, $allowed[$extension], true)) {
            return null;
        }

        $imageInfo = @getimagesize($path);
        if ($imageInfo === false
            || !isset($imageInfo[0], $imageInfo[1], $imageInfo['mime'])
            || !in_array($imageInfo['mime'], $allowed[$extension], true)
            || $imageInfo[0] < 1
            || $imageInfo[1] < 1
            || ($imageInfo[0] * $imageInfo[1]) > $maxPixels) {
            return null;
        }

        return $mime;
    }

    /**
     * Validate common PDF/Office/archive formats by extension, MIME and file signature.
     */
    public static function isValidDocumentFile(
        string $path,
        string $extension,
        int $reportedSize,
        int $maxBytes = 20971520
    ): bool {
        $extension = strtolower($extension);
        $allowedMimes = [
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword', 'application/x-ole-storage', 'application/CDFV2', 'application/vnd.ms-office'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip', 'application/x-zip-compressed'],
            'xls'  => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/CDFV2', 'application/vnd.ms-office'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/x-zip', 'application/x-zip-compressed'],
            'ppt'  => ['application/vnd.ms-powerpoint', 'application/x-ole-storage', 'application/CDFV2', 'application/vnd.ms-office'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/x-zip', 'application/x-zip-compressed'],
            'zip'  => ['application/zip', 'application/x-zip', 'application/x-zip-compressed'],
            'rar'  => ['application/vnd.rar', 'application/x-rar', 'application/x-rar-compressed'],
        ];

        if (!isset($allowedMimes[$extension]) || !is_file($path) || !is_readable($path)
            || $maxBytes <= 0 || $reportedSize <= 0 || $reportedSize > $maxBytes) {
            return false;
        }
        $actualSize = filesize($path);
        if ($actualSize === false || $actualSize <= 0 || $actualSize > $maxBytes || $actualSize !== $reportedSize) {
            return false;
        }

        if (!function_exists('finfo_open')) {
            return false;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return false;
        }
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);
        if (!is_string($mime) || !in_array($mime, $allowedMimes[$extension], true)) {
            return false;
        }

        $handle = @fopen($path, 'rb');
        $header = $handle ? fread($handle, 8) : false;
        if (is_resource($handle)) {
            fclose($handle);
        }
        if (!is_string($header) || $header === '') {
            return false;
        }

        if ($extension === 'pdf') {
            return strncmp($header, '%PDF-', 5) === 0;
        }
        if (in_array($extension, ['docx', 'xlsx', 'pptx', 'zip'], true)) {
            return in_array(substr($header, 0, 4), ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"], true);
        }
        if (in_array($extension, ['doc', 'xls', 'ppt'], true)) {
            return substr($header, 0, 8) === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
        }
        if ($extension === 'rar') {
            return strncmp($header, 'Rar!', 4) === 0;
        }
        return false;
    }
}
