<?php
/**
 * users/_user.php
 * ------------------------------------------------------------
 * Shared validation + form for staff account create/edit.
 * ------------------------------------------------------------
 */

/** Validate account data. Password is optional on edit ($isNew = false). */
function validate_user(mysqli $conn, array $d, bool $isNew, int $excludeId = 0): array
{
    $errors = [];
    if ($d['full_name'] === '') $errors['full_name'] = 'Full name is required.';
    if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $d['username'])) {
        $errors['username'] = 'Use 3–50 letters, numbers, dot or underscore.';
    }
    if (!in_array($d['role'], ['admin', 'nurse'], true)) $errors['role'] = 'Choose a role.';

    if ($isNew || $d['password'] !== '') {
        if (strlen($d['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        } elseif ($d['password'] !== $d['confirm']) {
            $errors['confirm'] = 'Passwords do not match.';
        }
    }

    if (!isset($errors['username'])) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id <> ?');
        $stmt->bind_param('si', $d['username'], $excludeId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows) $errors['username'] = 'Username is already taken.';
    }
    return $errors;
}

/** Read posted account fields. */
function user_input(): array
{
    return [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'username'  => trim($_POST['username'] ?? ''),
        'role'      => $_POST['role'] ?? 'nurse',
        'password'  => $_POST['password'] ?? '',
        'confirm'   => $_POST['confirm'] ?? '',
    ];
}

/** Render the account form. */
function render_user_form(array $data, array $errors, bool $isNew): void
{
    $invalid = fn($f) => isset($errors[$f]) ? ' is-invalid' : '';
    $errMsg  = fn($f) => isset($errors[$f]) ? '<div class="invalid-feedback">' . e($errors[$f]) . '</div>' : '';
    ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="card" style="max-width: 640px;">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control<?= $invalid('full_name') ?>"
                               value="<?= e(old($data, 'full_name')) ?>" placeholder="e.g. Maria Santos, RN" maxlength="100">
                        <?= $errMsg('full_name') ?>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control<?= $invalid('username') ?>"
                               value="<?= e(old($data, 'username')) ?>" maxlength="50" autocomplete="off">
                        <?= $errMsg('username') ?>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select<?= $invalid('role') ?>">
                            <option value="nurse"<?= selected(old($data, 'role', 'nurse'), 'nurse') ?>>Nurse / Clinic Staff</option>
                            <option value="admin"<?= selected(old($data, 'role'), 'admin') ?>>Administrator</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password <?= $isNew ? '<span class="text-danger">*</span>' : '' ?></label>
                        <input type="password" name="password" class="form-control<?= $invalid('password') ?>" autocomplete="new-password">
                        <?= $errMsg('password') ?>
                        <?php if (!$isNew): ?><div class="form-text">Leave blank to keep the current password.</div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm" class="form-control<?= $invalid('confirm') ?>" autocomplete="new-password">
                        <?= $errMsg('confirm') ?>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2 py-3">
                <a href="<?= url('users/index.php') ?>" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i><?= $isNew ? 'Create Account' : 'Update Account' ?></button>
            </div>
        </div>
    </form>
    <?php
}
