<?php
/**
 * users/delete.php - DELETE a staff account (admin only, POST only).
 * Visits they attended are kept; attended_by becomes NULL (FK SET NULL).
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users/index.php');
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id === (int) current_user()['id']) {
    flash('danger', 'You cannot delete your own account while logged in.');
    redirect('users/index.php');
}

$stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

flash($stmt->affected_rows ? 'success' : 'danger', $stmt->affected_rows ? 'Account deleted.' : 'Account not found.');
redirect('users/index.php');
