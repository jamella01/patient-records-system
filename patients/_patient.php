<?php
/**
 * patients/_patient.php
 * ------------------------------------------------------------
 * Shared logic for create.php and edit.php:
 *   - list of allowed option values
 *   - collecting + validating form input
 * ------------------------------------------------------------
 */

const PATIENT_TYPES = ['Student', 'Faculty', 'Staff'];
const SEX_OPTIONS   = ['Male', 'Female'];
const BLOOD_TYPES   = ['Unknown', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

/** Field names stored in the `patients` table (besides id/timestamps). */
const PATIENT_FIELDS = [
    'id_number', 'first_name', 'last_name', 'patient_type', 'course_grade', 'section',
    'sex', 'birthdate', 'contact_no', 'guardian_name', 'guardian_contact',
    'blood_type', 'allergies', 'medical_conditions',
];

/** Read and trim the posted patient fields. */
function patient_input(): array
{
    $data = [];
    foreach (PATIENT_FIELDS as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }
    return $data;
}

/**
 * Validate patient data. Returns [field => message] errors.
 * $excludeId lets edit.php skip the record itself in the unique check.
 */
function validate_patient(mysqli $conn, array $d, int $excludeId = 0): array
{
    $errors = [];

    if ($d['id_number'] === '')  $errors['id_number']  = 'ID number is required.';
    if ($d['first_name'] === '') $errors['first_name'] = 'First name is required.';
    if ($d['last_name'] === '')  $errors['last_name']  = 'Last name is required.';
    if (!in_array($d['patient_type'], PATIENT_TYPES, true)) $errors['patient_type'] = 'Choose a patient type.';
    if (!in_array($d['sex'], SEX_OPTIONS, true))            $errors['sex']          = 'Choose a sex.';
    if (!in_array($d['blood_type'], BLOOD_TYPES, true))     $errors['blood_type']   = 'Invalid blood type.';

    if ($d['birthdate'] !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $d['birthdate']);
        if (!$dt || $dt->format('Y-m-d') !== $d['birthdate'] || $dt > new DateTime()) {
            $errors['birthdate'] = 'Enter a valid birthdate (not in the future).';
        }
    }
    foreach (['contact_no', 'guardian_contact'] as $f) {
        if ($d[$f] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $d[$f])) {
            $errors[$f] = 'Enter a valid phone number.';
        }
    }

    // ID number must be unique
    if (!isset($errors['id_number'])) {
        $stmt = $conn->prepare('SELECT id FROM patients WHERE id_number = ? AND id <> ?');
        $stmt->bind_param('si', $d['id_number'], $excludeId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['id_number'] = 'This ID number is already registered.';
        }
    }
    return $errors;
}

/** Convert empty strings to NULL for optional columns before saving. */
function nullify_empty(array $d): array
{
    foreach ($d as $k => $v) {
        if ($v === '') $d[$k] = null;
    }
    return $d;
}
