<?php
/**
 * patients/_form.php
 * ------------------------------------------------------------
 * Patient form fields shared by create.php and edit.php.
 * Expects: $data (field values), $errors (field => message),
 *          $submitLabel (button text)
 * ------------------------------------------------------------
 */

/** Print the Bootstrap "is-invalid" class + message helpers. */
$invalid = fn($f) => isset($errors[$f]) ? ' is-invalid' : '';
$errMsg  = fn($f) => isset($errors[$f]) ? '<div class="invalid-feedback">' . e($errors[$f]) . '</div>' : '';
?>
<form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-body">

            <div class="section-title">Personal Information</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">ID Number <span class="text-danger">*</span></label>
                    <input type="text" name="id_number" class="form-control<?= $invalid('id_number') ?>"
                           value="<?= e(old($data, 'id_number')) ?>" placeholder="e.g. 2024-00123" maxlength="30" required>
                    <?= $errMsg('id_number') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control<?= $invalid('first_name') ?>"
                           value="<?= e(old($data, 'first_name')) ?>" maxlength="60" required>
                    <?= $errMsg('first_name') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control<?= $invalid('last_name') ?>"
                           value="<?= e(old($data, 'last_name')) ?>" maxlength="60" required>
                    <?= $errMsg('last_name') ?>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label">Sex <span class="text-danger">*</span></label>
                    <select name="sex" class="form-select<?= $invalid('sex') ?>" required>
                        <option value="">Select...</option>
                        <?php foreach (SEX_OPTIONS as $opt): ?>
                            <option<?= selected(old($data, 'sex'), $opt) ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $errMsg('sex') ?>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label">Birthdate</label>
                    <input type="date" name="birthdate" class="form-control<?= $invalid('birthdate') ?>"
                           value="<?= e(old($data, 'birthdate')) ?>" max="<?= date('Y-m-d') ?>">
                    <?= $errMsg('birthdate') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact No.</label>
                    <input type="tel" name="contact_no" class="form-control<?= $invalid('contact_no') ?>"
                           value="<?= e(old($data, 'contact_no')) ?>" placeholder="09XXXXXXXXX" maxlength="20">
                    <?= $errMsg('contact_no') ?>
                </div>
            </div>

            <div class="section-title">School Information</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Patient Type <span class="text-danger">*</span></label>
                    <select name="patient_type" class="form-select<?= $invalid('patient_type') ?>" required>
                        <?php foreach (PATIENT_TYPES as $opt): ?>
                            <option<?= selected(old($data, 'patient_type', 'Student'), $opt) ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $errMsg('patient_type') ?>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Course &amp; Year / Department</label>
                    <input type="text" name="course_grade" class="form-control"
                           value="<?= e(old($data, 'course_grade')) ?>" placeholder="e.g. BSIT 3 or Registrar's Office" maxlength="60">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Section</label>
                    <input type="text" name="section" class="form-control"
                           value="<?= e(old($data, 'section')) ?>" maxlength="30">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Guardian / Emergency Contact Person</label>
                    <input type="text" name="guardian_name" class="form-control"
                           value="<?= e(old($data, 'guardian_name')) ?>" maxlength="100">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Guardian Contact No.</label>
                    <input type="tel" name="guardian_contact" class="form-control<?= $invalid('guardian_contact') ?>"
                           value="<?= e(old($data, 'guardian_contact')) ?>" maxlength="20">
                    <?= $errMsg('guardian_contact') ?>
                </div>
            </div>

            <div class="section-title">Medical Information</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Blood Type</label>
                    <select name="blood_type" class="form-select<?= $invalid('blood_type') ?>">
                        <?php foreach (BLOOD_TYPES as $opt): ?>
                            <option<?= selected(old($data, 'blood_type', 'Unknown'), $opt) ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Allergies</label>
                    <input type="text" name="allergies" class="form-control"
                           value="<?= e(old($data, 'allergies')) ?>" placeholder="e.g. Penicillin, Seafood (leave blank if none)">
                </div>
                <div class="col-12">
                    <label class="form-label">Existing Medical Conditions</label>
                    <textarea name="medical_conditions" class="form-control" rows="2"
                              placeholder="e.g. Asthma, Hypertension"><?= e(old($data, 'medical_conditions')) ?></textarea>
                </div>
            </div>
        </div>

        <div class="card-footer bg-white d-flex justify-content-end gap-2 py-3">
            <a href="<?= url('patients/index.php') ?>" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i><?= e($submitLabel) ?></button>
        </div>
    </div>
</form>
