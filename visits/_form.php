<?php
/**
 * visits/_form.php
 * ------------------------------------------------------------
 * Visit form shared by create.php and edit.php.
 * Expects: $data, $errors, $lists (patients/medicines/staff),
 *          $medItems [medicine_id => qty], $reserved [medicine_id => qty
 *          already dispensed by this visit, used on edit], $submitLabel
 * ------------------------------------------------------------
 */
$invalid = fn($f) => isset($errors[$f]) ? ' is-invalid' : '';
$errMsg  = fn($f) => isset($errors[$f]) ? '<div class="invalid-feedback">' . e($errors[$f]) . '</div>' : '';
$reserved = $reserved ?? [];

/** <option> list of medicines, showing how many are available. */
$medOptions = function ($selectedId) use ($lists, $reserved) {
    $html = '<option value="">Select medicine...</option>';
    foreach ($lists['medicines'] as $m) {
        $available = $m['stock_qty'] + ($reserved[$m['id']] ?? 0);
        $html .= '<option value="' . $m['id'] . '"' . selected($selectedId, $m['id'])
               . ($available <= 0 ? ' disabled' : '') . '>'
               . e($m['name']) . ' (' . $available . ' ' . e($m['unit']) . ' left)</option>';
    }
    return $html;
};

// Always show at least one (empty) medicine row
$rows = $medItems ?: ['' => ''];
?>
<form method="post" novalidate>
    <?= csrf_field() ?>

    <!-- Allergy warning, filled by JS when the selected patient has allergies -->
    <div class="alert alert-danger allergy-alert d-none align-items-center gap-2" id="allergyAlert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><strong>Allergy alert:</strong> <span id="allergyText"></span></div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="section-title">Patient &amp; Visit</div>
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                    <select name="patient_id" id="patientSelect" class="form-select<?= $invalid('patient_id') ?>" required>
                        <option value="">Select patient...</option>
                        <?php foreach ($lists['patients'] as $p): ?>
                            <option value="<?= $p['id'] ?>" data-allergies="<?= e($p['allergies']) ?>"<?= selected(old($data, 'patient_id'), $p['id']) ?>>
                                <?= e($p['last_name'] . ', ' . $p['first_name'] . ' — ' . $p['id_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $errMsg('patient_id') ?>
                    <div class="form-text">Not in the list? <a href="<?= url('patients/create.php') ?>">Register a new patient</a>.</div>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Date &amp; Time of Visit <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="visit_date" class="form-control<?= $invalid('visit_date') ?>"
                           value="<?= e(old($data, 'visit_date', date('Y-m-d\TH:i'))) ?>" required>
                    <?= $errMsg('visit_date') ?>
                </div>
                <div class="col-12">
                    <label class="form-label">Chief Complaint <span class="text-danger">*</span></label>
                    <input type="text" name="complaint" class="form-control<?= $invalid('complaint') ?>"
                           value="<?= e(old($data, 'complaint')) ?>" placeholder="e.g. Headache, stomach ache, fever" maxlength="255" required>
                    <?= $errMsg('complaint') ?>
                </div>
            </div>

            <div class="section-title">Vital Signs</div>
            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label">Temperature</label>
                    <div class="input-group has-validation">
                        <input type="number" step="0.1" min="34" max="43" name="temperature"
                               class="form-control<?= $invalid('temperature') ?>" value="<?= e(old($data, 'temperature')) ?>" placeholder="36.5">
                        <span class="input-group-text">&deg;C</span>
                        <?= $errMsg('temperature') ?>
                    </div>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Blood Pressure</label>
                    <div class="input-group has-validation">
                        <input type="text" name="blood_pressure" class="form-control<?= $invalid('blood_pressure') ?>"
                               value="<?= e(old($data, 'blood_pressure')) ?>" placeholder="120/80" maxlength="7">
                        <span class="input-group-text">mmHg</span>
                        <?= $errMsg('blood_pressure') ?>
                    </div>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Pulse Rate</label>
                    <div class="input-group has-validation">
                        <input type="number" min="30" max="220" name="pulse_rate" class="form-control<?= $invalid('pulse_rate') ?>"
                               value="<?= e(old($data, 'pulse_rate')) ?>" placeholder="80">
                        <span class="input-group-text">bpm</span>
                        <?= $errMsg('pulse_rate') ?>
                    </div>
                </div>
            </div>

            <div class="section-title">Assessment &amp; Treatment</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Diagnosis / Assessment</label>
                    <input type="text" name="diagnosis" class="form-control" value="<?= e(old($data, 'diagnosis')) ?>" maxlength="255">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Disposition <span class="text-danger">*</span></label>
                    <select name="disposition" class="form-select<?= $invalid('disposition') ?>">
                        <?php foreach (DISPOSITIONS as $opt): ?>
                            <option<?= selected(old($data, 'disposition', 'Returned to class'), $opt) ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $errMsg('disposition') ?>
                </div>
                <div class="col-12">
                    <label class="form-label">Treatment / Intervention</label>
                    <textarea name="treatment" class="form-control" rows="2"><?= e(old($data, 'treatment')) ?></textarea>
                </div>
            </div>

            <div class="section-title">Medicines Dispensed</div>
            <?php if (isset($errors['meds'])): ?>
                <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= e($errors['meds']) ?></div>
            <?php endif; ?>
            <div id="medRows">
                <?php foreach ($rows as $medId => $qty): ?>
                    <div class="row g-2 mb-2 med-row">
                        <div class="col-7 col-md-8">
                            <select name="med_id[]" class="form-select"><?= $medOptions($medId) ?></select>
                        </div>
                        <div class="col-3 col-md-2">
                            <input type="number" name="med_qty[]" class="form-control" min="1" value="<?= e($qty ?: 1) ?>" aria-label="Quantity">
                        </div>
                        <div class="col-2 col-md-2">
                            <button type="button" class="btn btn-light border w-100 remove-med" title="Remove"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addMed"><i class="bi bi-plus-lg me-1"></i>Add another medicine</button>
            <div class="form-text">Leave the medicine blank if none was given. Stock is deducted automatically.</div>

            <div class="section-title">Other Details</div>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Attended By</label>
                    <select name="attended_by" class="form-select">
                        <option value="">—</option>
                        <?php foreach ($lists['staff'] as $s): ?>
                            <option value="<?= $s['id'] ?>"<?= selected(old($data, 'attended_by', current_user()['id']), $s['id']) ?>><?= e($s['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= e(old($data, 'notes')) ?>" placeholder="e.g. Parent informed, follow-up tomorrow">
                </div>
            </div>
        </div>

        <div class="card-footer bg-white d-flex justify-content-end gap-2 py-3">
            <a href="<?= url('visits/index.php') ?>" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i><?= e($submitLabel) ?></button>
        </div>
    </div>
</form>

<?php
// Page-specific JS (printed by footer.php)
$pageScripts = <<<'JS'
<script>
    // Show the allergy warning for the selected patient
    const patientSelect = document.getElementById('patientSelect');
    function updateAllergy() {
        const opt = patientSelect.selectedOptions[0];
        const allergies = opt ? opt.dataset.allergies : '';
        const box = document.getElementById('allergyAlert');
        document.getElementById('allergyText').textContent = allergies || '';
        box.classList.toggle('d-none', !allergies);
        box.classList.toggle('d-flex', !!allergies);
    }
    patientSelect.addEventListener('change', updateAllergy);
    updateAllergy();

    // Add / remove medicine rows
    const medRows = document.getElementById('medRows');
    document.getElementById('addMed').addEventListener('click', function () {
        const row = medRows.querySelector('.med-row').cloneNode(true);
        row.querySelector('select').value = '';
        row.querySelector('input').value = 1;
        medRows.appendChild(row);
    });
    medRows.addEventListener('click', function (ev) {
        const btn = ev.target.closest('.remove-med');
        if (!btn) return;
        const rows = medRows.querySelectorAll('.med-row');
        if (rows.length > 1) {
            btn.closest('.med-row').remove();
        } else {                       // keep one row, just clear it
            rows[0].querySelector('select').value = '';
            rows[0].querySelector('input').value = 1;
        }
    });
</script>
JS;
