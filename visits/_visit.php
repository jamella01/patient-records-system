<?php
/**
 * visits/_visit.php
 * ------------------------------------------------------------
 * Shared logic for visit create/edit/delete:
 *   - reading + validating the visit form
 *   - dispensing medicines (deducting stock) and
 *     restoring stock when a visit is edited or deleted.
 * All stock changes are meant to run inside a DB transaction.
 * ------------------------------------------------------------
 */

const DISPOSITIONS = ['Returned to class', 'Observation', 'Sent home', 'Referred to hospital'];

/** Read and trim posted visit fields. */
function visit_input(): array
{
    $fields = ['patient_id', 'visit_date', 'complaint', 'temperature', 'blood_pressure', 'pulse_rate',
               'diagnosis', 'treatment', 'disposition', 'attended_by', 'notes'];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }
    return $data;
}

/**
 * Read the dynamic "medicines dispensed" rows.
 * Returns [medicine_id => total_quantity] (duplicates are merged).
 */
function medicine_input(): array
{
    $ids  = $_POST['med_id']  ?? [];
    $qtys = $_POST['med_qty'] ?? [];
    $items = [];
    foreach ($ids as $i => $medId) {
        $medId = (int) $medId;
        $qty   = (int) ($qtys[$i] ?? 0);
        if ($medId > 0 && $qty > 0) {
            $items[$medId] = ($items[$medId] ?? 0) + $qty;
        }
    }
    return $items;
}

/** Validate visit fields. Returns [field => message]. */
function validate_visit(mysqli $conn, array $d): array
{
    $errors = [];

    // Patient must exist
    $pid  = (int) $d['patient_id'];
    $stmt = $conn->prepare('SELECT id FROM patients WHERE id = ?');
    $stmt->bind_param('i', $pid);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $errors['patient_id'] = 'Please select a patient.';
    }

    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $d['visit_date']);
    if (!$dt) {
        $errors['visit_date'] = 'Enter a valid date and time.';
    } elseif ($dt > new DateTime('+5 minutes')) {
        $errors['visit_date'] = 'Visit date cannot be in the future.';
    }

    if ($d['complaint'] === '') {
        $errors['complaint'] = 'Chief complaint is required.';
    }
    if ($d['temperature'] !== '' && (!is_numeric($d['temperature']) || $d['temperature'] < 34 || $d['temperature'] > 43)) {
        $errors['temperature'] = 'Temperature must be between 34 and 43 °C.';
    }
    if ($d['blood_pressure'] !== '' && !preg_match('#^\d{2,3}/\d{2,3}$#', $d['blood_pressure'])) {
        $errors['blood_pressure'] = 'Use the format 120/80.';
    }
    if ($d['pulse_rate'] !== '' && (!ctype_digit($d['pulse_rate']) || $d['pulse_rate'] < 30 || $d['pulse_rate'] > 220)) {
        $errors['pulse_rate'] = 'Pulse rate must be 30–220 bpm.';
    }
    if (!in_array($d['disposition'], DISPOSITIONS, true)) {
        $errors['disposition'] = 'Choose a disposition.';
    }
    return $errors;
}

/** Bind and run INSERT or UPDATE for a visit. Returns the visit id. */
function save_visit(mysqli $conn, array $d, int $id = 0): int
{
    // Optional numeric fields become NULL when empty
    $temp   = $d['temperature'] === '' ? null : (float) $d['temperature'];
    $pulse  = $d['pulse_rate']  === '' ? null : (int) $d['pulse_rate'];
    $staff  = $d['attended_by'] === '' ? null : (int) $d['attended_by'];
    $bp     = $d['blood_pressure'] ?: null;
    $diag   = $d['diagnosis'] ?: null;
    $treat  = $d['treatment'] ?: null;
    $notes  = $d['notes'] ?: null;
    $date   = str_replace('T', ' ', $d['visit_date']) . ':00';
    $pid    = (int) $d['patient_id'];

    if ($id === 0) {
        $stmt = $conn->prepare(
            'INSERT INTO visits (patient_id, visit_date, complaint, temperature, blood_pressure, pulse_rate,
                                 diagnosis, treatment, disposition, attended_by, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issdsisssis', $pid, $date, $d['complaint'], $temp, $bp, $pulse,
                          $diag, $treat, $d['disposition'], $staff, $notes);
        $stmt->execute();
        return $conn->insert_id;
    }

    $stmt = $conn->prepare(
        'UPDATE visits SET patient_id = ?, visit_date = ?, complaint = ?, temperature = ?, blood_pressure = ?,
                pulse_rate = ?, diagnosis = ?, treatment = ?, disposition = ?, attended_by = ?, notes = ?
          WHERE id = ?'
    );
    $stmt->bind_param('issdsisssisi', $pid, $date, $d['complaint'], $temp, $bp, $pulse,
                      $diag, $treat, $d['disposition'], $staff, $notes, $id);
    $stmt->execute();
    return $id;
}

/**
 * Deduct stock and record the medicines given in a visit.
 * Throws RuntimeException if a medicine does not have enough stock
 * (caller should roll back the transaction).
 */
function dispense_medicines(mysqli $conn, int $visitId, array $items): void
{
    $lock   = $conn->prepare('SELECT name, stock_qty, unit FROM medicines WHERE id = ? FOR UPDATE');
    $deduct = $conn->prepare('UPDATE medicines SET stock_qty = stock_qty - ? WHERE id = ?');
    $insert = $conn->prepare('INSERT INTO visit_medicines (visit_id, medicine_id, quantity) VALUES (?, ?, ?)');

    foreach ($items as $medId => $qty) {
        $lock->bind_param('i', $medId);
        $lock->execute();
        $med = $lock->get_result()->fetch_assoc();

        if (!$med) {
            throw new RuntimeException('A selected medicine no longer exists.');
        }
        if ($med['stock_qty'] < $qty) {
            throw new RuntimeException(sprintf(
                'Not enough stock for %s: requested %d, only %d %s available.',
                $med['name'], $qty, $med['stock_qty'], $med['unit']
            ));
        }
        $deduct->bind_param('ii', $qty, $medId);
        $deduct->execute();
        $insert->bind_param('iii', $visitId, $medId, $qty);
        $insert->execute();
    }
}

/** Put a visit's dispensed medicines back into stock and clear the list. */
function restore_medicines(mysqli $conn, int $visitId): void
{
    $stmt = $conn->prepare(
        'UPDATE medicines m
           JOIN visit_medicines vm ON vm.medicine_id = m.id
            SET m.stock_qty = m.stock_qty + vm.quantity
          WHERE vm.visit_id = ?'
    );
    $stmt->bind_param('i', $visitId);
    $stmt->execute();

    $stmt = $conn->prepare('DELETE FROM visit_medicines WHERE visit_id = ?');
    $stmt->bind_param('i', $visitId);
    $stmt->execute();
}

/** Data needed by the form dropdowns. */
function visit_form_lists(mysqli $conn): array
{
    return [
        'patients'  => $conn->query('SELECT id, id_number, first_name, last_name, allergies FROM patients ORDER BY last_name, first_name')->fetch_all(MYSQLI_ASSOC),
        'medicines' => $conn->query('SELECT id, name, stock_qty, unit FROM medicines ORDER BY name')->fetch_all(MYSQLI_ASSOC),
        'staff'     => $conn->query('SELECT id, full_name FROM users ORDER BY full_name')->fetch_all(MYSQLI_ASSOC),
    ];
}
