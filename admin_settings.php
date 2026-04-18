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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
</head>
<body>
    <h1>Admin Settings</h1>
    <p>Welcome, <?php echo htmlspecialchars((string)$_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if ($message !== ''): ?>
        <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <h2>Database Export / Restore</h2>
    <form action="export_backup.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit">Export SQL Backup</button>
    </form>

    <h2>Internal Backup / Restore</h2>
    <form action="backup_restore.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" name="action" value="backup">Run Backup</button>
        <button type="submit" name="action" value="restore">Run Restore</button>
    </form>

    <h2>Upload SQL Backup (Restore)</h2>
    <form action="upload_backup.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <input type="file" name="backup_file" accept=".sql" required>
        <button type="submit">Upload</button>
    </form>

    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>
