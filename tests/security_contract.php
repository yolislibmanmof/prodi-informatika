<?php
declare(strict_types=1);

/**
 * Source-level security contract checks. These checks do not replace runtime penetration
 * testing, but catch regressions in shared guards, uploads, cache handling, and schema sync.
 */
$root = dirname(__DIR__);
$failures = [];

function expectContract(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }
}

$authPath = $root . '/admin/includes/auth.php';
$auth = is_file($authPath) ? (string)file_get_contents($authPath) : '';
$footerPath = $root . '/admin/includes/footer.php';
$footer = is_file($footerPath) ? (string)file_get_contents($footerPath) : '';
$logoutPath = $root . '/admin/logout.php';
$logout = is_file($logoutPath) ? (string)file_get_contents($logoutPath) : '';
$functionsPath = $root . '/includes/functions.php';
$functions = is_file($functionsPath) ? (string)file_get_contents($functionsPath) : '';
$mediaPath = $root . '/admin/media.php';
$media = is_file($mediaPath) ? (string)file_get_contents($mediaPath) : '';
$documentsPath = $root . '/admin/dokumen.php';
$documents = is_file($documentsPath) ? (string)file_get_contents($documentsPath) : '';
$uploadValidationPath = $root . '/includes/UploadValidation.php';
$uploadValidation = is_file($uploadValidationPath) ? (string)file_get_contents($uploadValidationPath) : '';
$testimoniPath = $root . '/admin/testimoni.php';
$testimoni = is_file($testimoniPath) ? (string)file_get_contents($testimoniPath) : '';
$settingsPath = $root . '/admin/settings.php';
$settings = is_file($settingsPath) ? (string)file_get_contents($settingsPath) : '';

expectContract(str_contains($auth, "!verifyCSRF(\$_POST['csrf_token'] ?? '')"), 'Central admin POST CSRF guard is missing.');
expectContract(str_contains($auth, 'http_response_code(405)') && str_contains($auth, '$mutatingAdminAction'), 'Mutating GET actions are not blocked centrally.');
expectContract(str_contains($footer, 'window.adminSecureAction') && str_contains($footer, "tokenInput.name = 'csrf_token'"), 'Admin action link POST/CSRF bridge is missing.');
expectContract(str_contains($logout, '($_SERVER[\'REQUEST_METHOD\'] ?? \'GET\') !== \'POST\'') && str_contains($logout, 'Security::csrfVerify'), 'Logout does not require a CSRF-protected POST.');
expectContract(str_contains($functions, 'is_uploaded_file($file[\'tmp_name\'])'), 'Upload handler does not require an HTTP-uploaded temporary file.');
expectContract(str_contains($functions, 'UploadValidation::validatedRasterImageMime(') && str_contains($functions, "require_once __DIR__ . '/UploadValidation.php';"), 'Shared image validator is not wired into the image upload helper.');
expectContract(!preg_match('/[\'"]svg[\'"]\s*=>/i', $functions), 'SVG appears in the upload MIME allowlist; active SVG uploads must remain disabled.');
expectContract(str_contains($functions, 'unserialize($cacheData, [\'allowed_classes\' => false])'), 'File cache unserialize is not restricted to scalar/array values.');
expectContract(str_contains($media, 'is_uploaded_file(') && str_contains($media, 'UploadValidation::validatedRasterImageMime('), 'Media library upload path is missing strict file/content validation.');
expectContract(strpos($media, "'svg' =>") === false, 'Media library must not allow active SVG uploads.');
expectContract(str_contains($documents, 'safeDocumentFilePath(') && str_contains($documents, 'is_uploaded_file(') && str_contains($documents, 'UploadValidation::isValidDocumentFile('), 'Document upload/path handling is missing validation.');
expectContract(str_contains($uploadValidation, 'finfo_file(') && str_contains($uploadValidation, 'getimagesize(') && str_contains($uploadValidation, 'isValidDocumentFile('), 'Shared upload validator is missing content/signature checks.');
expectContract(!str_contains($auth, "|| isset(\$_GET['action'])"), 'Read-only admin GET requests should not flush public cache.');
expectContract(str_contains($testimoni, 'getExistingUploadPath(' . '$db' . ", 'testimoni', 'avatar'") && str_contains($testimoni, "deleteUploadedFile((string)" . '$row' . "['avatar'], 'uploads/testimoni')"), 'Testimonial avatar updates/deletes must use trusted database references and safe path deletion.');
expectContract(str_contains($settings, 'deleteUploadedFile(' . '$oldValue' . ', ' . '$folder' . ')') && !str_contains($settings, "unlink(__DIR__ . '/../' . " . '$oldValue' . ')'), 'Branding settings must use safe path deletion after the database save.');

// Every static POST form in top-level admin pages must carry a token. The login form
// uses Security::csrfField(), which outputs the corresponding hidden input at runtime.
foreach (glob($root . '/admin/*.php') ?: [] as $file) {
    $source = (string)file_get_contents($file);
    if (!preg_match_all('/<form\b[^>]*>/i', $source, $matches, PREG_OFFSET_CAPTURE)) {
        continue;
    }
    foreach ($matches[0] as [$tag, $offset]) {
        if (stripos($tag, 'method="post"') === false && stripos($tag, "method='post'") === false && !preg_match('/method\s*=\s*post/i', $tag)) {
            continue;
        }
        $close = stripos($source, '</form>', $offset);
        $end = $close === false ? min(strlen($source), $offset + 4000) : $close;
        $formSource = substr($source, $offset, $end - $offset);
        $hasHiddenToken = stripos($formSource, 'name="csrf_token"') !== false || stripos($formSource, "name='csrf_token'") !== false;
        $usesSecurityHelper = preg_match('/Security::csrfField\s*\(/i', $formSource) === 1;
        expectContract($hasHiddenToken || $usesSecurityHelper, basename($file) . ' has a POST form without CSRF field/helper.');
    }
}

// Validate expected CRUD columns against the canonical schema dump.
$schema = (string)file_get_contents($root . '/database/schema.sql');
$requiredColumns = [
    'agenda' => ['is_featured', 'updated_at'],
    'pengumuman' => ['updated_at'],
    'dosen' => ['telepon', 'pendidikan', 'updated_at'],
    'fasilitas' => ['kapasitas', 'updated_at'],
    'konsentrasi' => ['updated_at'],
    'prestasi' => ['updated_at'],
    'mitra' => ['updated_at'],
    'dokumen' => ['updated_at'],
    'alumni' => ['email', 'telepon', 'ipk', 'kota', 'instagram', 'is_featured', 'updated_at'],
    'api_keys' => ['rate_limit', 'ip_whitelist', 'updated_at'],
    'email_templates' => ['created_at'],
];
foreach ($requiredColumns as $table => $columns) {
    $start = strpos($schema, 'CREATE TABLE `' . $table . '` (');
    $end = $start === false ? false : strpos($schema, ') ENGINE=', $start);
    $tableSql = ($start !== false && $end !== false) ? substr($schema, $start, $end - $start) : '';
    foreach ($columns as $column) {
        expectContract((bool)preg_match('/`' . preg_quote($column, '/') . '`\s+/', $tableSql), 'Schema is missing ' . $table . '.' . $column . '.');
    }
}

$documentTableStart = strpos($schema, 'CREATE TABLE `dokumen` (');
$documentTableEnd = $documentTableStart === false ? false : strpos($schema, ') ENGINE=', $documentTableStart);
$documentTableSql = ($documentTableStart !== false && $documentTableEnd !== false) ? substr($schema, $documentTableStart, $documentTableEnd - $documentTableStart) : '';
expectContract(str_contains($documentTableSql, "'penelitian'"), 'Document schema enum is missing the admin Penelitian category.');

$migration = (string)file_get_contents($root . '/database/migrations/20261015_admin_crud_schema_compat.sql');
expectContract(str_contains($migration, 'information_schema.COLUMNS') && str_contains($migration, 'PREPARE stmt_') && str_contains($migration, 'DEALLOCATE PREPARE'), 'Idempotent admin CRUD schema migration is incomplete.');
expectContract(str_contains($migration, 'dokumen_category_ddl') && str_contains($migration, 'stmt_24'), 'Legacy document category enum upgrade is missing.');

if ($failures) {
    fwrite(STDERR, "Security/schema contract failures:\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

echo "Security/schema contract checks passed.\n";
