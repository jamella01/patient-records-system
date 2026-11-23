<?php
/**
 * medicines/create.php - INSERT a new medicine into inventory.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_medicine.php';
require_login();

$data   = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data   = medicine_form_input();
    $errors = validate_medicine($data);

    if (!$errors) {
        $expiry = $data['expiry_date'] ?: null;
        $stock  = (int) $data['stock_qty'];
        $level  = (int) $data['reorder_level'];
        $stmt = $conn->prepare(
            'INSERT INTO medicines (name, dosage_form, unit, stock_qty, reorder_level, expiry_date) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssiis', $data['name'], $data['dosage_form'], $data['unit'], $stock, $level, $expiry);
        $stmt->execute();

        flash('success', $data['name'] . ' was added to the inventory.');
        redirect('medicines/index.php');
    }
}

$pageTitle = 'Add Medicine';
$activeNav = 'medicines';
require_once __DIR__ . '/../includes/header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= url('medicines/index.php') ?>">Medicines</a></li>
        <li class="breadcrumb-item active">Add Medicine</li>
    </ol>
</nav>
<?php render_medicine_form($data, $errors, 'Save Medicine'); ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
