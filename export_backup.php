<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/backup_utils.php';

if (!isset($_SESSION['user'], $_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo 'Access denied. <a href="index.php">Return</a>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_settings.php');
    exit;
}

$postedToken = (string)($_POST['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['csrf_token'] ?? '');
if ($postedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
    http_response_code(400);
    echo 'Invalid request.';
    exit;
}

try {
    $pdo = getDb();
    $tables = ['users', 'student_info', 'grades'];
    $dump = buildSqlDump($pdo, $tables);

    $backupDir = __DIR__ . DIRECTORY_SEPARATOR . 'backups';
    if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
        throw new RuntimeException('Unable to create backup directory.');
    }

    $filename = 'secondlab_backup_' . date('Ymd_His') . '.sql';
    $path = $backupDir . DIRECTORY_SEPARATOR . $filename;
    if (file_put_contents($path, $dump) === false) {
        throw new RuntimeException('Failed to write backup file.');
    }

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string)filesize($path));
    readfile($path);
    exit;
} catch (Throwable $e) {
    $_SESSION['admin_message'] = 'Export failed: ' . $e->getMessage();
    header('Location: admin_settings.php');
    exit;
}
