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
    echo 'Access denied. <a href="index.php">Return</a>';
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = $_SESSION['admin_message'] ?? '';
unset($_SESSION['admin_message']);
$messageClass = 'success';
if ($message !== '' && (stripos($message, 'failed') !== false || stripos($message, 'invalid') !== false || stripos($message, 'unknown') !== false)) {
    $messageClass = 'error';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
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
                <div class="topbar-sub">Admin settings and database tools</div>
            </div>
            <nav class="topbar-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Log out</a>
            </nav>
        </header>

        <section class="content-stack">
                <section class="hero">
                    <h1>Admin Settings</h1>
                    <p>Welcome, <?php echo htmlspecialchars((string)$_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?></p>
                </section>

                <?php if ($message !== ''): ?>
                    <section class="card">
                        <p class="notice <?php echo $messageClass; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                    </section>
                <?php endif; ?>

                <section class="card">
                    <h2>Database Export</h2>
                    <div>
                        <h3>Export SQL Backup</h3>
                        <form action="export_backup.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit">Export SQL Backup</button>
                        </form>
                    </div>
                </section>

                <section class="card">
                    <h2>Upload SQL Backup (Restore)</h2>
                    <form action="upload_backup.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <label for="backup_file">Select SQL backup file</label>
                        <input id="backup_file" type="file" name="backup_file" accept=".sql" required>
                        <button type="submit">Upload</button>
                    </form>
                </section>
        </section>
    </main>
</body>
</html>
