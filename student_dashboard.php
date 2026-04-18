<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'], $_SESSION['user'], $_SESSION['role'])) {
    header('Location: index.php');
    exit;
}

if ($_SESSION['role'] === 'Admin') {
    header('Location: dashboard.php');
    exit;
}

if ($_SESSION['role'] !== 'Student') {
    http_response_code(403);
    echo 'Access denied. <a href="index.php">Return to login</a>';
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT subject, grade_value FROM grades WHERE student_id = :student_id ORDER BY subject ASC');
$stmt->execute([':student_id' => (int)$_SESSION['user_id']]);
$grades = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
</head>
<body>
    <h1>Student Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars((string)$_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?></p>

    <h2>Your Grades</h2>
    <?php if (empty($grades)): ?>
        <p>No grades found.</p>
    <?php else: ?>
        <table border="1" cellpadding="6" cellspacing="0">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$grade['subject'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)$grade['grade_value']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="logout.php">Log out</a></p>
</body>
</html>
