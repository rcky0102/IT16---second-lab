<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/backup_utils.php';

if (!isset($_SESSION['user'], $_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo 'Access denied. <a href="index.php">return</a>';
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

if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['admin_message'] = 'Upload failed.';
    header('Location: admin_settings.php');
    exit;
}

$file = $_FILES['backup_file'];
$originalName = (string)($file['name'] ?? '');
$tempPath = (string)($file['tmp_name'] ?? '');
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if ($extension !== 'sql') {
    $_SESSION['admin_message'] = 'Only .sql files are allowed.';
    header('Location: admin_settings.php');
    exit;
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    $_SESSION['admin_message'] = 'Failed to prepare upload directory.';
    header('Location: admin_settings.php');
    exit;
}

$safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName) ?? 'backup.sql';
$destination = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

// TOCTOU defense: verify extension again immediately before persisting the file.
$extAtSave = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if ($extAtSave !== 'sql') {
    $_SESSION['admin_message'] = 'File type changed during processing.';
    header('Location: admin_settings.php');
    exit;
}

if (!is_uploaded_file($tempPath) || !move_uploaded_file($tempPath, $destination)) {
    $_SESSION['admin_message'] = 'Could not save uploaded file.';
    header('Location: admin_settings.php');
    exit;
}

try {
    $sql = file_get_contents($destination);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('Uploaded file is empty or unreadable.');
    }

    $pdo = getDb();
    executeSqlDump($pdo, $sql);
    $_SESSION['admin_message'] = 'Upload and restore completed successfully.';
} catch (Throwable $e) {
    $_SESSION['admin_message'] = 'Restore failed: ' . $e->getMessage();
}

header('Location: admin_settings.php');
exit;
