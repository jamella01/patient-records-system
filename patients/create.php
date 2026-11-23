<?php
/**
 * patients/create.php - INSERT a new patient record.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_patient.php';
require_login();

$data   = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data   = patient_input();
    $errors = validate_patient($conn, $data);

    if (!$errors) {
        $d = nullify_empty($data);
        $stmt = $conn->prepare(
            'INSERT INTO patients (id_number, first_name, last_name, patient_type, course_grade, section,
                                   sex, birthdate, contact_no, guardian_name, guardian_contact,
                                   blood_type, allergies, medical_conditions)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'ssssssssssssss',
            $d['id_number'], $d['first_name'], $d['last_name'], $d['patient_type'], $d['course_grade'], $d['section'],
            $d['sex'], $d['birthdate'], $d['contact_no'], $d['guardian_name'], $d['guardian_contact'],
            $d['blood_type'], $d['allergies'], $d['medical_conditions']
        );
        $stmt->execute();

        flash('success', 'Patient ' . $data['first_name'] . ' ' . $data['last_name'] . ' was added.');
        redirect('patients/view.php?id=' . $conn->insert_id);
    }
}

$pageTitle   = 'Add Patient';
$activeNav   = 'patients';
$submitLabel = 'Save Patient';
require_once __DIR__ . '/../includes/header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= url('patients/index.php') ?>">Patients</a></li>
        <li class="breadcrumb-item active">Add Patient</li>
    </ol>
</nav>

<?php if ($errors): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i>Please fix the highlighted fields.</div>
<?php endif; ?>

<?php require __DIR__ . '/_form.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
