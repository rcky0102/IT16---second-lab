<?php
session_start();

if (isset($_SESSION['user'])) {
    if (($_SESSION['role'] ?? '') === 'Admin') {
        header('Location: dashboard.php');
        exit;
    }

    header('Location: student_dashboard.php');
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

$lockoutUntil = (int)($_SESSION['lockout_until'] ?? 0);
$remainingSeconds = max(0, $lockoutUntil - time());
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login</title>
</head>
<body>
    <form action="login.php" method="POST" autocomplete="off">
        <h1>System Login</h1>

        <?php if ($error !== ''): ?>
            <p style="color:#b00020;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <label for="username">Username:</label>
        <input id="username" type="text" name="username" maxlength="50" pattern="[A-Za-z0-9_]+" required>
        <br>

        <label for="password">Password:</label>
        <input id="password" type="password" name="password" maxlength="64" required>
        <br>

        <input id="loginBtn" type="submit" value="LOG IN" <?php echo $remainingSeconds > 0 ? 'disabled' : ''; ?>>
        <p id="lockoutMsg"></p>
    </form>

    <script>
        (function () {
            var remaining = <?php echo (int)$remainingSeconds; ?>;
            var button = document.getElementById('loginBtn');
            var message = document.getElementById('lockoutMsg');

            function render() {
                if (remaining > 0) {
                    button.disabled = true;
                    message.textContent = 'Login locked. Try again in ' + remaining + 's.';
                    remaining -= 1;
                    return;
                }

                button.disabled = false;
                message.textContent = '';
            }

            render();
            if (remaining > 0) {
                setInterval(render, 1000);
            }
        })();
    </script>
</body>
</html>
