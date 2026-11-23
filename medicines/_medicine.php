<?php
/**
 * medicines/_medicine.php
 * ------------------------------------------------------------
 * Shared input/validation + form for medicine create/edit.
 * ------------------------------------------------------------
 */

const DOSAGE_FORMS = ['Tablet', 'Capsule', 'Syrup', 'Suspension', 'Sachet', 'Solution', 'Ointment', 'Cream', 'Drops', 'Inhaler', 'Other'];

/** Read posted medicine fields. */
function medicine_form_input(): array
{
    $data = [];
    foreach (['name', 'dosage_form', 'unit', 'stock_qty', 'reorder_level', 'expiry_date'] as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }
    return $data;
}

/** Validate medicine fields. Returns [field => message]. */
function validate_medicine(array $d): array
{
    $errors = [];
    if ($d['name'] === '') $errors['name'] = 'Medicine name is required.';
    if (!in_array($d['dosage_form'], DOSAGE_FORMS, true)) $errors['dosage_form'] = 'Choose a dosage form.';
    if ($d['unit'] === '') $errors['unit'] = 'Unit is required (e.g. pcs, bottle).';
    if (!ctype_digit($d['stock_qty']))     $errors['stock_qty']     = 'Enter a whole number (0 or more).';
    if (!ctype_digit($d['reorder_level'])) $errors['reorder_level'] = 'Enter a whole number (0 or more).';
    if ($d['expiry_date'] !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $d['expiry_date']);
        if (!$dt || $dt->format('Y-m-d') !== $d['expiry_date']) $errors['expiry_date'] = 'Enter a valid date.';
    }
    return $errors;
}

/** Render the medicine form. */
function render_medicine_form(array $data, array $errors, string $submitLabel): void
{
    $invalid = fn($f) => isset($errors[$f]) ? ' is-invalid' : '';
    $errMsg  = fn($f) => isset($errors[$f]) ? '<div class="invalid-feedback">' . e($errors[$f]) . '</div>' : '';
    ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="card" style="max-width: 760px;">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Medicine Name &amp; Strength <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control<?= $invalid('name') ?>"
                               value="<?= e(old($data, 'name')) ?>" placeholder="e.g. Paracetamol 500mg" maxlength="100" required>
                        <?= $errMsg('name') ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Dosage Form <span class="text-danger">*</span></label>
                        <select name="dosage_form" class="form-select<?= $invalid('dosage_form') ?>">
                            <?php foreach (DOSAGE_FORMS as $f): ?>
                                <option<?= selected(old($data, 'dosage_form', 'Tablet'), $f) ?>><?= $f ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $errMsg('dosage_form') ?>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" min="0" name="stock_qty" class="form-control<?= $invalid('stock_qty') ?>"
                               value="<?= e(old($data, 'stock_qty', '0')) ?>">
                        <?= $errMsg('stock_qty') ?>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Unit <span class="text-danger">*</span></label>
                        <input type="text" name="unit" class="form-control<?= $invalid('unit') ?>" list="unitList"
                               value="<?= e(old($data, 'unit', 'pcs')) ?>" maxlength="20">
                        <datalist id="unitList"><option>pcs</option><option>bottle</option><option>sachet</option><option>tube</option><option>box</option></datalist>
                        <?= $errMsg('unit') ?>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" min="0" name="reorder_level" class="form-control<?= $invalid('reorder_level') ?>"
                               value="<?= e(old($data, 'reorder_level', '10')) ?>">
                        <?= $errMsg('reorder_level') ?>
                        <div class="form-text">Alert when stock falls to this.</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control<?= $invalid('expiry_date') ?>"
                               value="<?= e(old($data, 'expiry_date')) ?>">
                        <?= $errMsg('expiry_date') ?>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2 py-3">
                <a href="<?= url('medicines/index.php') ?>" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i><?= e($submitLabel) ?></button>
            </div>
        </div>
    </form>
    <?php
}
