<?php
/**
 * patients/index.php
 * ------------------------------------------------------------
 * SELECT: list of patients with live search (name / ID number),
 * patient-type filter and pagination.
 * ------------------------------------------------------------
 */
$pageTitle = 'Patients';
$activeNav = 'patients';
require_once __DIR__ . '/../includes/header.php';

$search  = trim($_GET['q'] ?? '');
$type    = $_GET['type'] ?? '';
$perPage = 10;
$page    = max(1, (int) ($_GET['page'] ?? 1));

// ----- Build the WHERE clause dynamically with bound parameters -----
$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR p.id_number LIKE ?
                 OR CONCAT(p.first_name, ' ', p.last_name) LIKE ?)";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}
if (in_array($type, ['Student', 'Faculty', 'Staff'], true)) {
    $where[]  = 'p.patient_type = ?';
    $params[] = $type;
    $types   .= 's';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ----- Count for pagination -----
$stmt = $conn->prepare("SELECT COUNT(*) FROM patients p $whereSql");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total      = (int) $stmt->get_result()->fetch_row()[0];
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// ----- Fetch the page of patients with their visit count -----
$stmt = $conn->prepare(
    "SELECT p.*, COUNT(v.id) AS visit_count, MAX(v.visit_date) AS last_visit
       FROM patients p
  LEFT JOIN visits v ON v.patient_id = p.id
       $whereSql
   GROUP BY p.id
   ORDER BY p.last_name, p.first_name
      LIMIT ? OFFSET ?"
);
$pageParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($types . 'ii', ...$pageParams);
$stmt->execute();
$patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="page-actions">
    <form class="d-flex flex-wrap gap-2" method="get">
        <div class="input-group" style="max-width: 320px;">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="search" name="q" class="form-control" placeholder="Search name or ID number..."
                   value="<?= e($search) ?>" data-live-search>
        </div>
        <select name="type" class="form-select" style="width: auto;" onchange="this.form.submit()">
            <option value="">All types</option>
            <?php foreach (['Student', 'Faculty', 'Staff'] as $t): ?>
                <option<?= selected($type, $t) ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($search !== '' || $type !== ''): ?>
            <a href="<?= url('patients/index.php') ?>" class="btn btn-light border">Clear</a>
        <?php endif; ?>
    </form>
    <a href="<?= url('patients/create.php') ?>" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> Add Patient
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>ID Number</th>
                <th>Name</th>
                <th>Type</th>
                <th class="d-none d-md-table-cell">Course / Dept.</th>
                <th class="d-none d-lg-table-cell">Contact</th>
                <th class="text-center">Visits</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$patients): ?>
                <tr><td colspan="7">
                    <div class="empty-state"><i class="bi bi-person-x"></i>
                        <?= $search !== '' ? 'No patients match "' . e($search) . '".' : 'No patients registered yet.' ?>
                    </div>
                </td></tr>
            <?php endif; ?>

            <?php foreach ($patients as $p): ?>
                <tr>
                    <td class="fw-medium text-nowrap"><?= e($p['id_number']) ?></td>
                    <td>
                        <a href="<?= url('patients/view.php?id=' . $p['id']) ?>" class="fw-semibold text-decoration-none">
                            <?= e($p['last_name'] . ', ' . $p['first_name']) ?>
                        </a>
                        <?php if ($p['allergies']): ?>
                            <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Allergies: <?= e($p['allergies']) ?>"></i>
                        <?php endif; ?>
                        <div class="cell-sub"><?= e($p['sex']) ?> &middot; <?= age($p['birthdate']) ?> yrs</div>
                    </td>
                    <td><?= type_badge($p['patient_type']) ?></td>
                    <td class="d-none d-md-table-cell">
                        <?= e($p['course_grade'] ?: '—') ?>
                        <?php if ($p['section']): ?><span class="cell-sub">· Sec <?= e($p['section']) ?></span><?php endif; ?>
                    </td>
                    <td class="d-none d-lg-table-cell"><?= e($p['contact_no'] ?: '—') ?></td>
                    <td class="text-center">
                        <span class="badge rounded-pill text-bg-light border"><?= (int) $p['visit_count'] ?></span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url('visits/create.php?patient_id=' . $p['id']) ?>" class="btn btn-sm btn-icon btn-outline-primary" title="Record visit">
                            <i class="bi bi-clipboard2-plus"></i></a>
                        <a href="<?= url('patients/view.php?id=' . $p['id']) ?>" class="btn btn-sm btn-icon btn-light border" title="View">
                            <i class="bi bi-eye"></i></a>
                        <a href="<?= url('patients/edit.php?id=' . $p['id']) ?>" class="btn btn-sm btn-icon btn-light border" title="Edit">
                            <i class="bi bi-pencil"></i></a>
                        <button type="button" class="btn btn-sm btn-icon btn-light border text-danger" title="Delete"
                                data-delete-url="<?= url('patients/delete.php') ?>"
                                data-delete-id="<?= $p['id'] ?>"
                                data-delete-name="<?= e($p['first_name'] . ' ' . $p['last_name']) ?>"
                                data-delete-extra="All of this patient's visit records will also be deleted.">
                            <i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
        <span class="small text-muted">
            <?= $total ? 'Showing ' . ($offset + 1) . '–' . ($offset + count($patients)) . ' of ' . $total . ' patients' : '0 patients' ?>
        </span>
        <?php render_pagination($page, $totalPages); ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
