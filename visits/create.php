<?php
/**
 * visits/create.php
 * ------------------------------------------------------------
 * INSERT a clinic visit and dispense medicines in one
 * transaction: if any medicine lacks stock, nothing is saved.
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_visit.php';
require_login();

// Pre-select a patient when coming from the patient list/profile
$data     = ['patient_id' => (int) ($_GET['patient_id'] ?? 0) ?: ''];
$errors   = [];
$medItems = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data     = visit_input();
    $medItems = medicine_input();
    $errors   = validate_visit($conn, $data);

    if (!$errors) {
        $conn->begin_transaction();
        try {
            $visitId = save_visit($conn, $data);
            dispense_medicines($conn, $visitId, $medItems);
            $conn->commit();

            flash('success', 'Clinic visit recorded.');
            redirect('visits/view.php?id=' . $visitId);
        } catch (RuntimeException $ex) {
            $conn->rollback();
            $errors['meds'] = $ex->getMessage();
        }
    }
}

$lists       = visit_form_lists($conn);
$pageTitle   = 'Record Visit';
$activeNav   = 'visits';
$submitLabel = 'Save Visit';
require_once __DIR__ . '/../includes/header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= url('visits/index.php') ?>">Clinic Visits</a></li>
        <li class="breadcrumb-item active">Record Visit</li>
    </ol>
</nav>

<?php if ($errors && !isset($errors['meds'])): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i>Please fix the highlighted fields.</div>
<?php endif; ?>

<?php require __DIR__ . '/_form.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
