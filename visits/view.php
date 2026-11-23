<?php
/**
 * visits/view.php
 * ------------------------------------------------------------
 * SELECT one visit with patient info and dispensed medicines.
 * Doubles as a printable clinic slip (Print button / Ctrl+P).
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id   = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare(
    'SELECT v.*, p.id_number, p.first_name, p.last_name, p.patient_type, p.course_grade, p.section,
            p.sex, p.birthdate, p.allergies, p.guardian_name, p.guardian_contact,
            u.full_name AS attended_name
       FROM visits v
       JOIN patients p ON p.id = v.patient_id
  LEFT JOIN users u ON u.id = v.attended_by
      WHERE v.id = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$v = $stmt->get_result()->fetch_assoc();

if (!$v) {
    flash('danger', 'Visit not found.');
    redirect('visits/index.php');
}

$stmt = $conn->prepare(
    'SELECT m.name, m.dosage_form, m.unit, vm.quantity
       FROM visit_medicines vm
       JOIN medicines m ON m.id = vm.medicine_id
      WHERE vm.visit_id = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$meds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Visit Details';
$activeNav = 'visits';
require_once __DIR__ . '/../includes/header.php';

$fullName = $v['first_name'] . ' ' . $v['last_name'];
?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3 d-print-none">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="<?= url('visits/index.php') ?>">Clinic Visits</a></li>
            <li class="breadcrumb-item active">Visit #<?= $id ?></li>
        </ol>
    </nav>
    <div class="ms-auto d-flex gap-2">
        <button onclick="window.print()" class="btn btn-light border"><i class="bi bi-printer me-1"></i>Print Slip</button>
        <a href="<?= url('visits/edit.php?id=' . $id) ?>" class="btn btn-light border"><i class="bi bi-pencil me-1"></i>Edit</a>
        <button type="button" class="btn btn-light border text-danger"
                data-delete-url="<?= url('visits/delete.php') ?>"
                data-delete-id="<?= $id ?>"
                data-delete-name="this visit"
                data-delete-extra="Dispensed medicines will be returned to stock.">
            <i class="bi bi-trash3 me-1"></i>Delete
        </button>
    </div>
</div>

<div class="card mx-auto" style="max-width: 860px;">
    <div class="card-body p-4">
        <!-- Slip header -->
        <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
            <div class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></div>
            <div>
                <h5 class="mb-0"><?= e(APP_NAME) ?></h5>
                <div class="cell-sub">Clinic Visit Record</div>
            </div>
            <div class="ms-auto text-end">
                <div class="fw-semibold">Visit #<?= str_pad((string) $id, 5, '0', STR_PAD_LEFT) ?></div>
                <div class="cell-sub"><?= fmt_date($v['visit_date'], 'F d, Y · h:i A') ?></div>
            </div>
        </div>

        <?php if ($v['allergies']): ?>
            <div class="alert alert-danger allergy-alert py-2 small mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i><strong>Allergies:</strong> <?= e($v['allergies']) ?>
            </div>
        <?php endif; ?>

        <div class="section-title">Patient</div>
        <div class="row">
            <div class="col-sm-6"><div class="info-label">Name</div>
                <div class="info-value"><a href="<?= url('patients/view.php?id=' . $v['patient_id']) ?>" class="text-decoration-none"><?= e($fullName) ?></a></div></div>
            <div class="col-sm-3 col-6"><div class="info-label">ID Number</div><div class="info-value"><?= e($v['id_number']) ?></div></div>
            <div class="col-sm-3 col-6"><div class="info-label">Type</div><div class="info-value"><?= e($v['patient_type']) ?></div></div>
            <div class="col-sm-6"><div class="info-label">Course / Department</div>
                <div class="info-value"><?= e($v['course_grade'] ?: '—') ?><?= $v['section'] ? ' · ' . e($v['section']) : '' ?></div></div>
            <div class="col-sm-3 col-6"><div class="info-label">Sex / Age</div><div class="info-value"><?= e($v['sex']) ?> · <?= age($v['birthdate']) ?></div></div>
            <div class="col-sm-3 col-6"><div class="info-label">Guardian</div>
                <div class="info-value"><?= e($v['guardian_name'] ?: '—') ?>
                    <?php if ($v['guardian_contact']): ?><div class="cell-sub"><?= e($v['guardian_contact']) ?></div><?php endif; ?></div></div>
        </div>

        <div class="section-title">Complaint &amp; Vital Signs</div>
        <div class="row">
            <div class="col-12"><div class="info-label">Chief Complaint</div><div class="info-value"><?= e($v['complaint']) ?></div></div>
            <div class="col-4"><div class="info-label">Temperature</div><div class="info-value"><?= temp_display($v['temperature']) ?></div></div>
            <div class="col-4"><div class="info-label">Blood Pressure</div><div class="info-value"><?= $v['blood_pressure'] ? e($v['blood_pressure']) . ' mmHg' : '—' ?></div></div>
            <div class="col-4"><div class="info-label">Pulse Rate</div><div class="info-value"><?= $v['pulse_rate'] ? (int) $v['pulse_rate'] . ' bpm' : '—' ?></div></div>
        </div>

        <div class="section-title">Assessment &amp; Treatment</div>
        <div class="row">
            <div class="col-sm-8"><div class="info-label">Diagnosis</div><div class="info-value"><?= e($v['diagnosis'] ?: '—') ?></div></div>
            <div class="col-sm-4"><div class="info-label">Disposition</div><div class="info-value"><?= disposition_badge($v['disposition']) ?></div></div>
            <div class="col-12"><div class="info-label">Treatment / Intervention</div><div class="info-value"><?= nl2br(e($v['treatment'] ?: '—')) ?></div></div>
        </div>

        <div class="info-label mb-2">Medicines Dispensed</div>
        <?php if ($meds): ?>
            <table class="table table-sm table-bordered mb-3">
                <thead><tr><th>Medicine</th><th>Form</th><th class="text-end">Qty</th></tr></thead>
                <tbody>
                <?php foreach ($meds as $m): ?>
                    <tr><td><?= e($m['name']) ?></td><td><?= e($m['dosage_form']) ?></td>
                        <td class="text-end"><?= (int) $m['quantity'] ?> <?= e($m['unit']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="info-value text-muted">None</p>
        <?php endif; ?>

        <?php if ($v['notes']): ?>
            <div class="info-label">Notes</div><div class="info-value"><?= nl2br(e($v['notes'])) ?></div>
        <?php endif; ?>

        <!-- Signature line -->
        <div class="row mt-5">
            <div class="col-sm-6 ms-auto text-center">
                <div class="border-top pt-2 fw-semibold"><?= e($v['attended_name'] ?? '________________') ?></div>
                <div class="cell-sub">Attending Clinic Staff</div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
