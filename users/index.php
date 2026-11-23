<?php
/**
 * users/index.php - SELECT: list of staff accounts (admin only).
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pageTitle = 'Staff Accounts';
$activeNav = 'users';
require_once __DIR__ . '/../includes/header.php';

$users = $conn->query(
    'SELECT u.*, COUNT(v.id) AS visits_handled
       FROM users u
  LEFT JOIN visits v ON v.attended_by = u.id
   GROUP BY u.id
   ORDER BY u.full_name'
)->fetch_all(MYSQLI_ASSOC);
?>
<div class="page-actions">
    <p class="text-muted small mb-0">Accounts that can sign in to the clinic system.</p>
    <a href="<?= url('users/create.php') ?>" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Add Account</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Name</th><th>Username</th><th>Role</th><th class="text-center">Visits Handled</th><th class="d-none d-md-table-cell">Created</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): $isMe = $u['id'] == current_user()['id']; ?>
                <tr>
                    <td class="fw-semibold">
                        <span class="avatar me-2"><?= e(strtoupper($u['full_name'][0])) ?></span><?= e($u['full_name']) ?>
                        <?php if ($isMe): ?><span class="badge text-bg-light border ms-1">You</span><?php endif; ?>
                    </td>
                    <td><?= e($u['username']) ?></td>
                    <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-soft-purple' : 'badge-soft-primary' ?>"><?= ucfirst(e($u['role'])) ?></span></td>
                    <td class="text-center"><?= (int) $u['visits_handled'] ?></td>
                    <td class="d-none d-md-table-cell"><?= fmt_date($u['created_at']) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url('users/edit.php?id=' . $u['id']) ?>" class="btn btn-sm btn-icon btn-light border" title="Edit"><i class="bi bi-pencil"></i></a>
                        <?php if (!$isMe): ?>
                            <button type="button" class="btn btn-sm btn-icon btn-light border text-danger" title="Delete"
                                    data-delete-url="<?= url('users/delete.php') ?>"
                                    data-delete-id="<?= $u['id'] ?>"
                                    data-delete-name="the account of <?= e($u['full_name']) ?>"
                                    data-delete-extra="Their past visit records will be kept.">
                                <i class="bi bi-trash3"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
