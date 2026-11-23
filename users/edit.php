<?php
/**
 * users/edit.php - UPDATE a staff account (admin only).
 * Password is changed only when a new one is entered.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_user.php';
require_admin();

$id   = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT id, username, full_name, role FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();

if (!$account) {
    flash('danger', 'Account not found.');
    redirect('users/index.php');
}

$isMe   = $id === (int) current_user()['id'];
$data   = $account;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = user_input();
    if ($isMe) {
        $data['role'] = 'admin'; // an admin cannot demote themselves (avoids locking everyone out)
    }
    $errors = validate_user($conn, $data, false, $id);

    if (!$errors) {
        if ($data['password'] !== '') {
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET username = ?, full_name = ?, role = ?, password_hash = ? WHERE id = ?');
            $stmt->bind_param('ssssi', $data['username'], $data['full_name'], $data['role'], $hash, $id);
        } else {
            $stmt = $conn->prepare('UPDATE users SET username = ?, full_name = ?, role = ? WHERE id = ?');
            $stmt->bind_param('sssi', $data['username'], $data['full_name'], $data['role'], $id);
        }
        $stmt->execute();

        // Keep the session in sync when editing your own account
        if ($isMe) {
            $_SESSION['user']['username']  = $data['username'];
            $_SESSION['user']['full_name'] = $data['full_name'];
        }
        flash('success', 'Account updated.');
        redirect('users/index.php');
    }
}

$pageTitle = 'Edit Account';
$activeNav = 'users';
require_once __DIR__ . '/../includes/header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= url('users/index.php') ?>">Staff Accounts</a></li>
        <li class="breadcrumb-item active"><?= e($account['full_name']) ?></li>
    </ol>
</nav>
<?php if ($isMe): ?>
    <div class="alert alert-info small py-2" style="max-width: 640px;"><i class="bi bi-info-circle me-1"></i>You are editing your own account. Your role stays Administrator.</div>
<?php endif; ?>
<?php render_user_form($data, $errors, false); ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
