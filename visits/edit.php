<?php
/**
 * visits/edit.php
 * ------------------------------------------------------------
 * UPDATE a clinic visit. Previously dispensed medicines are
 * returned to stock first, then the new list is deducted -
 * all inside one transaction.
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_visit.php';
require_login();

$id   = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM visits WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$visit = $stmt->get_result()->fetch_assoc();

if (!$visit) {
    flash('danger', 'Visit not found.');
    redirect('visits/index.php');
}

// Medicines currently recorded for this visit [medicine_id => qty]
$stmt = $conn->prepare('SELECT medicine_id, SUM(quantity) AS qty FROM visit_medicines WHERE visit_id = ? GROUP BY medicine_id');
$stmt->bind_param('i', $id);
$stmt->execute();
$reserved = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'qty', 'medicine_id');

// Prepare stored values for the form (datetime-local needs "Y-m-d\TH:i")
$data = $visit;
$data['visit_date'] = date('Y-m-d\TH:i', strtotime($visit['visit_date']));
$medItems = $reserved;
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data     = visit_input();
    $medItems = medicine_input();
    $errors   = validate_visit($conn, $data);

    if (!$errors) {
        $conn->begin_transaction();
        try {
            save_visit($conn, $data, $id);
            restore_medicines($conn, $id);          // give back the old quantities
            dispense_medicines($conn, $id, $medItems); // deduct the new ones
            $conn->commit();

            flash('success', 'Visit record updated.');
            redirect('visits/view.php?id=' . $id);
        } catch (RuntimeException $ex) {
            $conn->rollback();
            $errors['meds'] = $ex->getMessage();
        }
    }
}

$lists       = visit_form_lists($conn);
$pageTitle   = 'Edit Visit';
$activeNav   = 'visits';
$submitLabel = 'Update Visit';
require_once __DIR__ . '/../includes/header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= url('visits/index.php') ?>">Clinic Visits</a></li>
        <li class="breadcrumb-item"><a href="<?= url('visits/view.php?id=' . $id) ?>">Visit #<?= $id ?></a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
</nav>

<?php if ($errors && !isset($errors['meds'])): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i>Please fix the highlighted fields.</div>
<?php endif; ?>

<?php require __DIR__ . '/_form.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
