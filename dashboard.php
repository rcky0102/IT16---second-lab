<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user'], $_SESSION['role'])) {
    header('Location: index.php');
    exit;
}

if ($_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo 'Access denied. <a href="index.php">return</a>';
    exit;
}

$pdo = getDb();

// Fetch students for admin to select
$studentsStmt = $pdo->query("SELECT id, username, full_name FROM users WHERE role = 'Student' ORDER BY username ASC");
$students = $studentsStmt->fetchAll();

// Fetch all grades with student info
$gradesStmt = $pdo->query('SELECT g.id, g.student_id, g.subject, g.grade_value, u.username, u.full_name FROM grades g JOIN users u ON g.student_id = u.id ORDER BY u.username, g.subject');
$grades = $gradesStmt->fetchAll();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$message = $_SESSION['dashboard_message'] ?? '';
unset($_SESSION['dashboard_message']);
$messageClass = 'success';
if ($message !== '' && (stripos($message, 'failed') !== false || stripos($message, 'invalid') !== false || stripos($message, 'must') !== false || stripos($message, 'denied') !== false || stripos($message, 'unknown') !== false)) {
    $messageClass = 'error';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="app-shell">
        <header class="topbar">
            <div>
                <div class="topbar-title">Student Information and Grading System</div>
                <div class="topbar-sub">Admin portal for records and grading</div>
            </div>
            <nav class="topbar-links">
                <a href="admin_settings.php">Settings</a>
                <a href="logout.php">Log out</a>
            </nav>
        </header>

        <section class="content-stack">
                <section class="hero">
                    <h1>Admin Dashboard</h1>
                    <p>Welcome, <?php echo htmlspecialchars((string)$_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?></p>
                </section>

                <?php if ($message !== ''): ?>
                    <section class="card">
                        <p class="notice <?php echo $messageClass; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                    </section>
                <?php endif; ?>

                <section class="card">
                    <h2>Add or Update Grades</h2>
                    <div class="grid">
                        <div>
                            <h3>Add New Subject and Grade</h3>
                            <form action="add_grade.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                                <label for="add_student_id">Select Student</label>
                                <select id="add_student_id" name="student_id" required>
                                    <option value="">-- Select student --</option>
                                    <?php foreach ($students as $s): ?>
                                        <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['username'] . ' (' . $s['full_name'] . ')', ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <label for="add_subject">Subject Name (Max 30 chars)</label>
                                <input id="add_subject" type="text" name="subject" maxlength="30" pattern="[A-Za-z0-9 ]+" required>

                                <label for="add_grade_value">Grade (0-100)</label>
                                <input id="add_grade_value" type="number" name="grade_value" min="0" max="100" required>

                                <input type="submit" value="Add Grade">
                            </form>
                        </div>

                        <div>
                            <h3>Update Existing Subject and Grade</h3>
                            <form action="update_grade.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                                <label for="update_grade_id">Select Grade Record to Update</label>
                                <select id="update_grade_id" name="grade_id" required>
                                    <option value="">-- Select grade record --</option>
                                    <?php foreach ($grades as $g): ?>
                                        <option value="<?php echo (int)$g['id']; ?>"><?php echo htmlspecialchars($g['username'] . ' (' . $g['full_name'] . ') - ' . $g['subject'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <label for="update_subject">New Subject Name (Max 30 chars)</label>
                                <input id="update_subject" type="text" name="subject" maxlength="30" pattern="[A-Za-z0-9 ]+" required>

                                <label for="update_grade_value">New Grade (0-100)</label>
                                <input id="update_grade_value" type="number" name="grade_value" min="0" max="100" required>

                                <input type="submit" value="Update Grade">
                            </form>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <h2>All Student Grades</h2>
                    <?php if (empty($grades)): ?>
                        <p>No grades recorded.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Full Name</th>
                                    <th>Subject</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $g): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string)$g['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string)$g['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string)$g['subject'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo (int)$g['grade_value']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>
        </section>
    </main>
</body>
</html>
