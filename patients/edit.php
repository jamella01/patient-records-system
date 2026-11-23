<?php
/**
 * patients/edit.php - UPDATE an existing patient record.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_patient.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);

// Load the current record
$stmt = $conn->prepare('SELECT * FROM patients WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    flash('danger', 'Patient not found.');
    redirect('patients/index.php');
}

$data   = $patient;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data   = patient_input();
    $errors = validate_patient($conn, $data, $id);

    if (!$errors) {
        $d = nullify_empty($data);
        $stmt = $conn->prepare(
            'UPDATE patients SET id_number = ?, first_name = ?, last_name = ?, patient_type = ?, course_grade = ?,
                    section = ?, sex = ?, birthdate = ?, contact_no = ?, guardian_name = ?, guardian_contact = ?,
                    blood_type = ?, allergies = ?, medical_conditions = ?
              WHERE id = ?'
        );
        $stmt->bind_param(
            'ssssssssssssssi',
            $d['id_number'], $d['first_name'], $d['last_name'], $d['patient_type'], $d['course_grade'],
            $d['section'], $d['sex'], $d['birthdate'], $d['contact_no'], $d['guardian_name'], $d['guardian_contact'],
            $d['blood_type'], $d['allergies'], $d['medical_conditions'], $id
        );
        $stmt->execute();

        flash('success', 'Patient record updated.');
        redirect('patients/view.php?id=' . $id);
    }
}

$pageTitle   = 'Edit Patient';
$activeNav   = 'patients';
$submitLabel = 'Update Patient';
require_once __DIR__ . '/../includes/header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= url('patients/index.php') ?>">Patients</a></li>
        <li class="breadcrumb-item"><a href="<?= url('patients/view.php?id=' . $id) ?>"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
</nav>

<?php if ($errors): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i>Please fix the highlighted fields.</div>
<?php endif; ?>

<?php require __DIR__ . '/_form.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
