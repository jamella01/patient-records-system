<?php
/**
 * patients/view.php
 * ------------------------------------------------------------
 * SELECT one patient: profile, medical info and full visit history.
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id   = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM patients WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
    flash('danger', 'Patient not found.');
    redirect('patients/index.php');
}

// Visit history with the name of the staff who attended
$stmt = $conn->prepare(
    'SELECT v.*, u.full_name AS attended_name
       FROM visits v
  LEFT JOIN users u ON u.id = v.attended_by
      WHERE v.patient_id = ?
   ORDER BY v.visit_date DESC'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$visits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Patient Profile';
$activeNav = 'patients';
require_once __DIR__ . '/../includes/header.php';

$fullName = $p['first_name'] . ' ' . $p['last_name'];
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= url('patients/index.php') ?>">Patients</a></li>
        <li class="breadcrumb-item active"><?= e($fullName) ?></li>
    </ol>
</nav>

<?php if ($p['allergies']): ?>
    <div class="alert alert-danger allergy-alert d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><strong>Allergy alert:</strong> <?= e($p['allergies']) ?></div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <!-- Profile card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center pb-2">
                <div class="avatar mx-auto mb-2" style="width:72px;height:72px;font-size:1.6rem;">
                    <?= e(strtoupper($p['first_name'][0] . $p['last_name'][0])) ?>
                </div>
                <h5 class="mb-1"><?= e($fullName) ?></h5>
                <div class="text-muted small mb-2"><?= e($p['id_number']) ?></div>
                <?= type_badge($p['patient_type']) ?>
            </div>
            <div class="card-body pt-3 border-top">
                <div class="row">
                    <div class="col-6"><div class="info-label">Sex</div><div class="info-value"><?= e($p['sex']) ?></div></div>
                    <div class="col-6"><div class="info-label">Age</div><div class="info-value"><?= age($p['birthdate']) ?></div></div>
                    <div class="col-6"><div class="info-label">Birthdate</div><div class="info-value"><?= fmt_date($p['birthdate']) ?></div></div>
                    <div class="col-6"><div class="info-label">Blood Type</div><div class="info-value"><?= e($p['blood_type']) ?></div></div>
                    <div class="col-12"><div class="info-label">Course / Department</div>
                        <div class="info-value"><?= e($p['course_grade'] ?: '—') ?><?= $p['section'] ? ' · Section ' . e($p['section']) : '' ?></div></div>
                    <div class="col-12"><div class="info-label">Contact No.</div><div class="info-value"><?= e($p['contact_no'] ?: '—') ?></div></div>
                    <div class="col-12"><div class="info-label">Guardian / Emergency Contact</div>
                        <div class="info-value"><?= e($p['guardian_name'] ?: '—') ?>
                            <?php if ($p['guardian_contact']): ?><div class="cell-sub"><?= e($p['guardian_contact']) ?></div><?php endif; ?>
                        </div></div>
                    <div class="col-12"><div class="info-label">Medical Conditions</div>
                        <div class="info-value"><?= nl2br(e($p['medical_conditions'] ?: 'None recorded')) ?></div></div>
                </div>
                <div class="cell-sub">Registered <?= fmt_date($p['created_at']) ?> · Updated <?= fmt_date($p['updated_at']) ?></div>
            </div>
            <div class="card-footer bg-white d-flex gap-2 py-3">
                <a href="<?= url('patients/edit.php?id=' . $id) ?>" class="btn btn-light border flex-fill"><i class="bi bi-pencil me-1"></i>Edit</a>
                <button type="button" class="btn btn-light border text-danger flex-fill"
                        data-delete-url="<?= url('patients/delete.php') ?>"
                        data-delete-id="<?= $id ?>"
                        data-delete-name="<?= e($fullName) ?>"
                        data-delete-extra="All of this patient's visit records will also be deleted.">
                    <i class="bi bi-trash3 me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Visit history -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <span><i class="bi bi-clipboard2-pulse me-2 text-primary"></i>Visit History
                    <span class="badge rounded-pill text-bg-light border ms-1"><?= count($visits) ?></span></span>
                <a href="<?= url('visits/create.php?patient_id=' . $id) ?>" class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-plus-lg"></i> Record Visit
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Date</th><th>Complaint / Diagnosis</th><th>Temp</th><th>Disposition</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$visits): ?>
                        <tr><td colspan="5"><div class="empty-state"><i class="bi bi-clipboard"></i>No clinic visits yet.</div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($visits as $v): ?>
                        <tr>
                            <td class="text-nowrap"><?= fmt_date($v['visit_date']) ?>
                                <div class="cell-sub"><?= fmt_date($v['visit_date'], 'h:i A') ?></div></td>
                            <td><?= e($v['complaint']) ?>
                                <div class="cell-sub"><?= e($v['diagnosis'] ?: 'No diagnosis') ?> · <?= e($v['attended_name'] ?? 'Unknown staff') ?></div></td>
                            <td class="text-nowrap"><?= temp_display($v['temperature']) ?></td>
                            <td><?= disposition_badge($v['disposition']) ?></td>
                            <td class="text-end">
                                <a href="<?= url('visits/view.php?id=' . $v['id']) ?>" class="btn btn-sm btn-icon btn-light border" title="View"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
