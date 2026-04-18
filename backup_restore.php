<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

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

$action = (string)($_POST['action'] ?? '');
$pdo = getDb();

try {
    if ($action === 'backup') {
        // DDL statements auto-commit in MySQL; use FK checks guard and correct drop order.
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('DROP TABLE IF EXISTS grades_backup');
        $pdo->exec('DROP TABLE IF EXISTS student_info_backup');
        $pdo->exec('DROP TABLE IF EXISTS users_backup');

        $pdo->exec('CREATE TABLE users_backup LIKE users');
        $pdo->exec('INSERT INTO users_backup SELECT * FROM users');

        $pdo->exec('CREATE TABLE student_info_backup LIKE student_info');
        $pdo->exec('INSERT INTO student_info_backup SELECT * FROM student_info');

        $pdo->exec('CREATE TABLE grades_backup LIKE grades');
        $pdo->exec('INSERT INTO grades_backup SELECT * FROM grades');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $_SESSION['admin_message'] = 'Backup completed.';
    } elseif ($action === 'restore') {
        // Ensure backup tables exist before attempting restore.
        $requiredTables = ['users_backup', 'student_info_backup', 'grades_backup'];
        foreach ($requiredTables as $tableName) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name');
            $check->execute([':table_name' => $tableName]);
            if ((int)$check->fetchColumn() === 0) {
                $_SESSION['admin_message'] = 'Restore failed: run Backup first.';
                header('Location: admin_settings.php');
                exit;
            }
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('TRUNCATE TABLE grades');
        $pdo->exec('TRUNCATE TABLE student_info');
        $pdo->exec('TRUNCATE TABLE users');

        $pdo->exec('INSERT INTO users SELECT * FROM users_backup');
        $pdo->exec('INSERT INTO student_info SELECT * FROM student_info_backup');
        $pdo->exec('INSERT INTO grades SELECT * FROM grades_backup');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $_SESSION['admin_message'] = 'Restore completed.';
    } else {
        $_SESSION['admin_message'] = 'Unknown action.';
    }
} catch (Throwable $e) {
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    } catch (Throwable $ignored) {
    }

    $_SESSION['admin_message'] = 'Database operation failed: ' . $e->getMessage();
}

header('Location: admin_settings.php');
exit;
