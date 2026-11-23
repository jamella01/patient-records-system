<?php
/**
 * patients/delete.php - DELETE a patient (POST only).
 * Their visits are removed automatically by the ON DELETE CASCADE
 * foreign key; dispensed medicine is NOT returned to stock because
 * it was already consumed.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('patients/index.php');
}
verify_csrf();

$id   = (int) ($_POST['id'] ?? 0);
$stmt = $conn->prepare('DELETE FROM patients WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    flash('success', 'Patient record deleted.');
} else {
    flash('danger', 'Patient not found or already deleted.');
}
redirect('patients/index.php');
