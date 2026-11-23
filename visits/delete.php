<?php
/**
 * visits/delete.php
 * ------------------------------------------------------------
 * DELETE a visit (POST only). Medicines recorded for the visit
 * are returned to stock, since deleting usually means the
 * record was entered by mistake.
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_visit.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('visits/index.php');
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

$conn->begin_transaction();
restore_medicines($conn, $id);

$stmt = $conn->prepare('DELETE FROM visits WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$deleted = $stmt->affected_rows > 0;
$conn->commit();

flash($deleted ? 'success' : 'danger', $deleted ? 'Visit deleted and medicines returned to stock.' : 'Visit not found.');
redirect('visits/index.php');
