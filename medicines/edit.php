<?php
/**
 * medicines/edit.php - UPDATE a medicine (e.g. restock or fix details).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_medicine.php';
require_login();

$id   = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM medicines WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$medicine = $stmt->get_result()->fetch_assoc();

if (!$medicine) {
    flash('danger', 'Medicine not found.');
    redirect('medicines/index.php');
}

$data   = $medicine;
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
            'UPDATE medicines SET name = ?, dosage_form = ?, unit = ?, stock_qty = ?, reorder_level = ?, expiry_date = ?
              WHERE id = ?'
        );
        $stmt->bind_param('sssiisi', $data['name'], $data['dosage_form'], $data['unit'], $stock, $level, $expiry, $id);
        $stmt->execute();

        flash('success', $data['name'] . ' was updated.');
        redirect('medicines/index.php');
    }
}

$pageTitle = 'Edit Medicine';
$activeNav = 'medicines';
require_once __DIR__ . '/../includes/header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= url('medicines/index.php') ?>">Medicines</a></li>
        <li class="breadcrumb-item active"><?= e($medicine['name']) ?></li>
    </ol>
</nav>
<?php render_medicine_form($data, $errors, 'Update Medicine'); ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
