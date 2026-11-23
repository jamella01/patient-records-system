<?php
/**
 * users/create.php - INSERT a new staff account (admin only).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_user.php';
require_admin();

$data   = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data   = user_input();
    $errors = validate_user($conn, $data, true);

    if (!$errors) {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $data['username'], $hash, $data['full_name'], $data['role']);
        $stmt->execute();

        flash('success', 'Account for ' . $data['full_name'] . ' was created.');
        redirect('users/index.php');
    }
}

$pageTitle = 'Add Account';
$activeNav = 'users';
require_once __DIR__ . '/../includes/header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= url('users/index.php') ?>">Staff Accounts</a></li>
        <li class="breadcrumb-item active">Add Account</li>
    </ol>
</nav>
<?php render_user_form($data, $errors, true); ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
