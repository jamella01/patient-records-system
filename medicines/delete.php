<?php
/**
 * medicines/delete.php - DELETE a medicine (POST only).
 * Medicines already dispensed in a visit are protected by the
 * ON DELETE RESTRICT foreign key, so the visit history stays intact.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('medicines/index.php');
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

try {
    $stmt = $conn->prepare('DELETE FROM medicines WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    flash($stmt->affected_rows ? 'success' : 'danger', $stmt->affected_rows ? 'Medicine deleted.' : 'Medicine not found.');
} catch (mysqli_sql_exception $ex) {
    // 1451 = row is referenced by a foreign key (visit_medicines)
    if ($ex->getCode() === 1451) {
        flash('warning', 'This medicine cannot be deleted because it appears in visit records. Set its stock to 0 instead.');
    } else {
        throw $ex;
    }
}
redirect('medicines/index.php');
