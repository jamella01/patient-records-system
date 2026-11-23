<?php
/**
 * login.php
 * ------------------------------------------------------------
 * Staff login. Verifies the username + password against the
 * `users` table (bcrypt hashes) and stores the user in session.
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/includes/functions.php';

// Already logged in? Go straight to the dashboard.
if (current_user()) {
    redirect('dashboard.php');
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        // SELECT the account by username (prepared statement)
        $stmt = $conn->prepare('SELECT id, username, password_hash, full_name, role FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();

        if ($account && password_verify($password, $account['password_hash'])) {
            session_regenerate_id(true); // prevent session fixation
            unset($account['password_hash']);
            $_SESSION['user'] = $account;
            flash('success', 'Welcome back, ' . $account['full_name'] . '!');
            redirect('dashboard.php');
        }
        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="card login-card">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <div class="login-logo mb-3"><i class="bi bi-heart-pulse-fill"></i></div>
                <h4 class="fw-bold mb-1"><?= e(APP_NAME) ?></h4>
                <p class="text-muted small mb-0">Sign in to manage clinic records</p>
            </div>

            <?php render_flash(); ?>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?= e($username) ?>" required autofocus autocomplete="username">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               required autocomplete="current-password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePw" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
                </button>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">
                Demo account: <code>admin</code> / <code>admin123</code>
            </p>
        </div>
    </div>
</div>
<script>
    // Show / hide password
    document.getElementById('togglePw').addEventListener('click', function () {
        const pw = document.getElementById('password');
        pw.type = pw.type === 'password' ? 'text' : 'password';
        this.innerHTML = pw.type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });
</script>
</body>
</html>
