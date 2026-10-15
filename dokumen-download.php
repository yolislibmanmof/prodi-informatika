<?php
require_once __DIR__ . '/includes/init_security.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

try {
    $db = getDB();
    $db->prepare("UPDATE dokumen SET downloads = downloads + 1 WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal mencatat unduhan']);
}