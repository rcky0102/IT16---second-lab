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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="page-shell centered">
        <section class="hero">
            <h1>Student Information and Grading System</h1>
            <p>Secure access portal</p>
        </section>

        <section class="card">
            <form action="login.php" method="POST" autocomplete="off">
                <h2>System Login</h2>

                <?php if ($error !== ''): ?>
                    <p class="notice error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <label for="username">Username</label>
                <input id="username" type="text" name="username" maxlength="50" pattern="[A-Za-z0-9_]+" required>

                <label for="password">Password</label>
                <input id="password" type="password" name="password" maxlength="64" required>

                <input id="loginBtn" type="submit" value="Log In" <?php echo $remainingSeconds > 0 ? 'disabled' : ''; ?>>
                <p id="lockoutMsg" class="notice error" style="display:none;"></p>
            </form>
        </section>
    </main>

    <script>
        (function () {
            var remaining = <?php echo (int)$remainingSeconds; ?>;
            var button = document.getElementById('loginBtn');
            var message = document.getElementById('lockoutMsg');

            function render() {
                if (remaining > 0) {
                    button.disabled = true;
                    message.style.display = 'block';
                    message.textContent = 'Login locked. Try again in ' + remaining + 's.';
                    remaining -= 1;
                    return;
                }

                button.disabled = false;
                message.style.display = 'none';
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
