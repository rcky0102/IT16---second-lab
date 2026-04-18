<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$now = time();
$lockoutUntil = (int)($_SESSION['lockout_until'] ?? 0);
if ($lockoutUntil > $now) {
    $_SESSION['login_error'] = 'Too many failed attempts. Try again later.';
    header('Location: index.php');
    exit;
}

$usernameRaw = trim((string)($_POST['username'] ?? ''));
$username = preg_replace('/[^a-zA-Z0-9_]/', '', $usernameRaw) ?? '';
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Invalid username or password.';
    header('Location: index.php');
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1');
$stmt->execute([':username' => $username]);
$user = $stmt->fetch();

$validPassword = false;
if ($user) {
    $stored = (string)$user['password'];
    $sha256 = hash('sha256', $password);

    if (hash_equals($stored, $sha256)) {
        $validPassword = true;
    } elseif (password_get_info($stored)['algo'] !== null && password_verify($password, $stored)) {
        $validPassword = true;
    } elseif (hash_equals($stored, $password)) {
        $validPassword = true;
    }
}

if ($user && $validPassword) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user'] = (string)$user['username'];
    $_SESSION['role'] = (string)$user['role'];
    $_SESSION['failed_attempts'] = 0;
    $_SESSION['lockout_until'] = 0;

    if ($_SESSION['role'] === 'Admin') {
        header('Location: dashboard.php');
        exit;
    }

    header('Location: student_dashboard.php');
    exit;
}

$_SESSION['failed_attempts'] = (int)($_SESSION['failed_attempts'] ?? 0) + 1;
if ((int)$_SESSION['failed_attempts'] >= 3) {
    $_SESSION['lockout_until'] = $now + 30;
    $_SESSION['failed_attempts'] = 0;
}

$_SESSION['login_error'] = 'Invalid username or password.';
header('Location: index.php');
exit;
