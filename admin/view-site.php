<?php
/**
 * Redirect ke website publik
 * File ini memungkinkan admin untuk melihat website dengan mudah
 */
require_once __DIR__ . '/../includes/config.php';

// Redirect ke halaman utama website publik
header('Location: ' . SITE_URL);
exit;