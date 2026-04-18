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

$studentIdRaw = (string)($_POST['student_id'] ?? '');
if (!preg_match('/^\d+$/', $studentIdRaw)) {
    $_SESSION['dashboard_message'] = 'Invalid student selected.';
    header('Location: dashboard.php');
    exit;
}

$studentId = (int)$studentIdRaw;
$pdo = getDb();

$studentCheck = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = :id AND role = "Student"');
$studentCheck->execute([':id' => $studentId]);
if ((int)$studentCheck->fetchColumn() === 0) {
    $_SESSION['dashboard_message'] = 'Selected student not found.';
    header('Location: dashboard.php');
    exit;
}

$dupCheck = $pdo->prepare('SELECT COUNT(*) FROM grades WHERE student_id = :student_id AND subject = :subject');
$dupCheck->execute([
    ':student_id' => $studentId,
    ':subject' => $subject,
]);
if ((int)$dupCheck->fetchColumn() > 0) {
    $_SESSION['dashboard_message'] = 'Subject already exists for this student. Use Update instead.';
    header('Location: dashboard.php');
    exit;
}

$nextId = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM grades')->fetchColumn();
$insert = $pdo->prepare('INSERT INTO grades (id, student_id, subject, grade_value) VALUES (:id, :student_id, :subject, :grade_value)');
$insert->execute([
    ':id' => $nextId,
    ':student_id' => $studentId,
    ':subject' => $subject,
    ':grade_value' => $gradeValue,
]);

$_SESSION['dashboard_message'] = 'New subject and grade added successfully.';
header('Location: dashboard.php');
exit;
