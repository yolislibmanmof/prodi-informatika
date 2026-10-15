<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function expectUploadPath(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$uploadsRoot = $root . DIRECTORY_SEPARATOR . 'uploads';
$suffix = bin2hex(random_bytes(6));
$folderName = 'test-contract-' . $suffix;
$relativeFolder = 'uploads/' . $folderName;
$folder = $uploadsRoot . DIRECTORY_SEPARATOR . $folderName;
$outside = __DIR__ . DIRECTORY_SEPARATOR . 'outside-' . $suffix . '.txt';

if (!is_dir($uploadsRoot) && !mkdir($uploadsRoot, 0755, true) && !is_dir($uploadsRoot)) {
    throw new RuntimeException('Unable to create upload root for path tests.');
}
if (!mkdir($folder, 0700) && !is_dir($folder)) {
    throw new RuntimeException('Unable to create upload path test directory.');
}

$filesToClean = [];
try {
    file_put_contents($outside, 'must survive path traversal attempts');
    $filesToClean[] = $outside;

    $safePath = $folder . DIRECTORY_SEPARATOR . 'safe.txt';
    file_put_contents($safePath, 'safe');
    $filesToClean[] = $safePath;
    expectUploadPath(
        deleteUploadedFile($relativeFolder . '/safe.txt') === true,
        'A file beneath uploads/ should be deletable.'
    );
    expectUploadPath(!is_file($safePath), 'A permitted upload file was not removed.');

    $legacyPath = $folder . DIRECTORY_SEPARATOR . 'legacy.jpg';
    file_put_contents($legacyPath, 'legacy');
    $filesToClean[] = $legacyPath;
    expectUploadPath(
        deleteUploadedFile('legacy.jpg', $relativeFolder) === true,
        'A legacy basename should be deletable only within the supplied upload folder.'
    );
    expectUploadPath(!is_file($legacyPath), 'A permitted legacy upload file was not removed.');

    expectUploadPath(
        deleteUploadedFile('../tests/outside-' . $suffix . '.txt', $relativeFolder) === false,
        'Path traversal must be rejected.'
    );
    expectUploadPath(is_file($outside), 'Path traversal removed a file outside uploads/.');

    $linkPath = $folder . DIRECTORY_SEPARATOR . 'outside-link.txt';
    if (@symlink($outside, $linkPath)) {
        $filesToClean[] = $linkPath;
        expectUploadPath(
            deleteUploadedFile($relativeFolder . '/outside-link.txt') === false,
            'A symlink resolving outside uploads/ must be rejected.'
        );
        expectUploadPath(is_file($outside), 'Deleting a symlink removed its outside target.');
    }

    expectUploadPath(deleteUploadedFile('https://example.invalid/avatar.png', $relativeFolder) === false,
        'External URLs must never be treated as local upload paths.');

    echo "Upload path security tests passed: safe delete, legacy basename, traversal, external URL, and symlink containment.\n";
} finally {
    foreach ($filesToClean as $file) {
        if (is_link($file) || is_file($file)) {
            @unlink($file);
        }
    }
    @rmdir($folder);
}
