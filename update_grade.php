<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user'], $_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo 'Access denied. <a href="index.php">return</a>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$postedToken = (string)($_POST['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['csrf_token'] ?? '');
if ($postedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
    http_response_code(400);
    echo 'Invalid request.';
    exit;
}

$subjectRaw = trim((string)($_POST['subject'] ?? ''));
$subject = preg_replace('/[^a-zA-Z0-9 ]/', '', $subjectRaw) ?? '';
$subject = trim($subject);

if ($subject === '' || mb_strlen($subject) > 30 || !preg_match('/^[a-zA-Z0-9 ]+$/', $subject)) {
    $_SESSION['dashboard_message'] = 'Subject must be 1-30 characters.';
    header('Location: dashboard.php');
    exit;
}

$gradeValueRaw = (string)($_POST['grade_value'] ?? '');
if (!preg_match('/^\d{1,3}$/', $gradeValueRaw)) {
    $_SESSION['dashboard_message'] = 'Grade must be a whole number from 0 to 100.';
    header('Location: dashboard.php');
    exit;
}

$gradeValue = (int)$gradeValueRaw;
if ($gradeValue < 0 || $gradeValue > 100) {
    $_SESSION['dashboard_message'] = 'Grade must be a whole number from 0 to 100.';
    header('Location: dashboard.php');
    exit;
}

$pdo = getDb();

// Validate selected student
// Require an existing grade id to update
$gradeIdRaw = (string)($_POST['grade_id'] ?? '');
if (!preg_match('/^\d+$/', $gradeIdRaw)) {
    $_SESSION['dashboard_message'] = 'Invalid grade record selected.';
    header('Location: dashboard.php');
    exit;
}

$gradeId = (int)$gradeIdRaw;
$check = $pdo->prepare('SELECT COUNT(*) FROM grades WHERE id = :id');
$check->execute([':id' => $gradeId]);
if ((int)$check->fetchColumn() === 0) {
    $_SESSION['dashboard_message'] = 'Selected grade record not found.';
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare('UPDATE grades SET subject = :subject, grade_value = :grade_value WHERE id = :grade_id');
$stmt->execute([
    ':subject' => $subject,
    ':grade_value' => $gradeValue,
    ':grade_id' => $gradeId,
]);

$_SESSION['dashboard_message'] = 'Grade updated successfully.';
header('Location: dashboard.php');
exit;
