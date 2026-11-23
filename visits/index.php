<?php
/**
 * visits/index.php
 * ------------------------------------------------------------
 * SELECT: clinic visit log with search, date range and
 * disposition filters, plus pagination.
 * ------------------------------------------------------------
 */
$pageTitle = 'Clinic Visits';
$activeNav = 'visits';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_visit.php';

$search      = trim($_GET['q'] ?? '');
$from        = $_GET['from'] ?? '';
$to          = $_GET['to'] ?? '';
$disposition = $_GET['disposition'] ?? '';
$perPage     = 10;
$page        = max(1, (int) ($_GET['page'] ?? 1));

/** Accept only real Y-m-d dates from the URL. */
$validDate = fn($d) => $d !== '' && DateTime::createFromFormat('Y-m-d', $d) !== false;

$where = []; $params = []; $types = '';

if ($search !== '') {
    $where[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR p.id_number LIKE ?
                 OR v.complaint LIKE ? OR v.diagnosis LIKE ?)";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
    $types .= 'sssss';
}
if ($validDate($from)) { $where[] = 'DATE(v.visit_date) >= ?'; $params[] = $from; $types .= 's'; }
if ($validDate($to))   { $where[] = 'DATE(v.visit_date) <= ?'; $params[] = $to;   $types .= 's'; }
if (in_array($disposition, DISPOSITIONS, true)) {
    $where[] = 'v.disposition = ?'; $params[] = $disposition; $types .= 's';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$stmt = $conn->prepare("SELECT COUNT(*) FROM visits v JOIN patients p ON p.id = v.patient_id $whereSql");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total      = (int) $stmt->get_result()->fetch_row()[0];
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// Page of visits
$stmt = $conn->prepare(
    "SELECT v.*, p.first_name, p.last_name, p.id_number, u.full_name AS attended_name
       FROM visits v
       JOIN patients p ON p.id = v.patient_id
  LEFT JOIN users u ON u.id = v.attended_by
       $whereSql
   ORDER BY v.visit_date DESC
      LIMIT ? OFFSET ?"
);
$pageParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($types . 'ii', ...$pageParams);
$stmt->execute();
$visits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$hasFilter = $search !== '' || $from !== '' || $to !== '' || $disposition !== '';
?>

<div class="card mb-3">
    <div class="card-body py-3">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" placeholder="Patient, ID or complaint..."
                           value="<?= e($search) ?>" data-live-search>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control" value="<?= e($from) ?>" onchange="this.form.submit()">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control" value="<?= e($to) ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
                <label class="form-label">Disposition</label>
                <select name="disposition" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach (DISPOSITIONS as $d): ?>
                        <option<?= selected($disposition, $d) ?>><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <?php if ($hasFilter): ?>
                    <a href="<?= url('visits/index.php') ?>" class="btn btn-light border flex-fill">Clear</a>
                <?php endif; ?>
                <a href="<?= url('visits/create.php') ?>" class="btn btn-primary flex-fill text-nowrap"><i class="bi bi-plus-lg"></i> New</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>Date &amp; Time</th>
                <th>Patient</th>
                <th>Complaint / Diagnosis</th>
                <th>Temp</th>
                <th>Disposition</th>
                <th class="d-none d-xl-table-cell">Attended By</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$visits): ?>
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-clipboard-x"></i>No visits found.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($visits as $v): ?>
                <tr>
                    <td class="text-nowrap"><?= fmt_date($v['visit_date']) ?>
                        <div class="cell-sub"><?= fmt_date($v['visit_date'], 'h:i A') ?></div></td>
                    <td>
                        <a href="<?= url('patients/view.php?id=' . $v['patient_id']) ?>" class="fw-semibold text-decoration-none">
                            <?= e($v['last_name'] . ', ' . $v['first_name']) ?></a>
                        <div class="cell-sub"><?= e($v['id_number']) ?></div>
                    </td>
                    <td><?= e($v['complaint']) ?>
                        <?php if ($v['diagnosis']): ?><div class="cell-sub"><?= e($v['diagnosis']) ?></div><?php endif; ?></td>
                    <td class="text-nowrap"><?= temp_display($v['temperature']) ?></td>
                    <td><?= disposition_badge($v['disposition']) ?></td>
                    <td class="d-none d-xl-table-cell small"><?= e($v['attended_name'] ?? '—') ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url('visits/view.php?id=' . $v['id']) ?>" class="btn btn-sm btn-icon btn-light border" title="View"><i class="bi bi-eye"></i></a>
                        <a href="<?= url('visits/edit.php?id=' . $v['id']) ?>" class="btn btn-sm btn-icon btn-light border" title="Edit"><i class="bi bi-pencil"></i></a>
                        <button type="button" class="btn btn-sm btn-icon btn-light border text-danger" title="Delete"
                                data-delete-url="<?= url('visits/delete.php') ?>"
                                data-delete-id="<?= $v['id'] ?>"
                                data-delete-name="the visit of <?= e($v['first_name'] . ' ' . $v['last_name']) ?> on <?= fmt_date($v['visit_date']) ?>"
                                data-delete-extra="Dispensed medicines will be returned to stock.">
                            <i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
        <span class="small text-muted">
            <?= $total ? 'Showing ' . ($offset + 1) . '–' . ($offset + count($visits)) . ' of ' . $total . ' visits' : '0 visits' ?>
        </span>
        <?php render_pagination($page, $totalPages); ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
