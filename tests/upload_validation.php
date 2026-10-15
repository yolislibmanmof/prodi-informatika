<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/UploadValidation.php';

function expectUpload(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prodi-upload-tests-' . bin2hex(random_bytes(6));
if (!mkdir($tmpDir, 0700) && !is_dir($tmpDir)) {
    throw new RuntimeException('Unable to create upload test directory.');
}

$files = [];
try {
    $validGif = base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=', true);
    expectUpload(is_string($validGif) && $validGif !== '', 'Test GIF fixture could not be decoded.');

    $gifPath = $tmpDir . DIRECTORY_SEPARATOR . 'valid.gif';
    file_put_contents($gifPath, $validGif);
    $files[] = $gifPath;
    $gifSize = filesize($gifPath);
    expectUpload(is_int($gifSize) && $gifSize > 0, 'Valid GIF fixture is empty.');
    expectUpload(
        UploadValidation::validatedRasterImageMime($gifPath, 'gif', $gifSize) === 'image/gif',
        'A valid GIF image should pass raster validation.'
    );

    expectUpload(
        UploadValidation::validatedRasterImageMime($gifPath, 'png', $gifSize) === null,
        'A GIF renamed to PNG must be rejected.'
    );
    expectUpload(
        UploadValidation::validatedRasterImageMime($gifPath, 'gif', $gifSize, $gifSize - 1) === null,
        'An image over the configured maximum must be rejected.'
    );
    expectUpload(
        UploadValidation::validatedRasterImageMime($gifPath, 'svg', $gifSize) === null,
        'SVG must remain excluded from raster image uploads.'
    );

    $fakeImagePath = $tmpDir . DIRECTORY_SEPARATOR . 'fake.gif';
    file_put_contents($fakeImagePath, '<html><script>alert(1)</script></html>');
    $files[] = $fakeImagePath;
    $fakeImageSize = filesize($fakeImagePath);
    expectUpload(is_int($fakeImageSize) && $fakeImageSize > 0, 'Fake image fixture is empty.');
    expectUpload(
        UploadValidation::validatedRasterImageMime($fakeImagePath, 'gif', $fakeImageSize) === null,
        'HTML/script payload disguised as a GIF must be rejected.'
    );

    $validPdfPath = $tmpDir . DIRECTORY_SEPARATOR . 'valid.pdf';
    $validPdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
    file_put_contents($validPdfPath, $validPdf);
    $files[] = $validPdfPath;
    $pdfSize = filesize($validPdfPath);
    expectUpload(is_int($pdfSize) && $pdfSize > 0, 'Test PDF fixture is empty.');
    expectUpload(
        UploadValidation::isValidDocumentFile($validPdfPath, 'pdf', $pdfSize),
        'A PDF with an appropriate MIME and signature should pass document validation.'
    );
    expectUpload(
        !UploadValidation::isValidDocumentFile($validPdfPath, 'txt', $pdfSize),
        'Unsupported document extensions must be rejected.'
    );
    expectUpload(
        !UploadValidation::isValidDocumentFile($validPdfPath, 'pdf', $pdfSize, $pdfSize - 1),
        'An oversized document must be rejected.'
    );
    expectUpload(
        !UploadValidation::isValidDocumentFile($fakeImagePath, 'pdf', (int)$fakeImageSize),
        'HTML/script payload disguised as a PDF must be rejected.'
    );

    echo "Upload validation tests passed: valid image/PDF, mismatched extensions, unsupported SVG, fake content, and file-size limits.\n";
} finally {
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    @rmdir($tmpDir);
}
