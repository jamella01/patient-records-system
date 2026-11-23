<?php
/**
 * medicines/index.php
 * ------------------------------------------------------------
 * SELECT: medicine inventory with search and quick filters
 * (low stock / expiring within 30 days).
 * ------------------------------------------------------------
 */
$pageTitle = 'Medicines';
$activeNav = 'medicines';
require_once __DIR__ . '/../includes/header.php';

$search = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? '';

$where = []; $params = []; $types = '';
if ($search !== '') {
    $where[] = '(m.name LIKE ? OR m.dosage_form LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like);
    $types .= 'ss';
}
if ($filter === 'low') {
    $where[] = 'm.stock_qty <= m.reorder_level';
} elseif ($filter === 'expiring') {
    $where[] = 'm.expiry_date IS NOT NULL AND m.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Also count how many times each medicine was dispensed
$stmt = $conn->prepare(
    "SELECT m.*, COALESCE(SUM(vm.quantity), 0) AS total_dispensed
       FROM medicines m
  LEFT JOIN visit_medicines vm ON vm.medicine_id = m.id
       $whereSql
   GROUP BY m.id
   ORDER BY m.name"
);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$today    = strtotime('today');
$soon     = strtotime('+30 days', $today);
$filters  = ['' => 'All', 'low' => 'Low stock', 'expiring' => 'Expiring soon'];
?>

<div class="page-actions">
    <form class="d-flex flex-wrap gap-2" method="get">
        <div class="input-group" style="max-width: 280px;">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="search" name="q" class="form-control" placeholder="Search medicine..." value="<?= e($search) ?>" data-live-search>
        </div>
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <div class="btn-group" role="group">
            <?php foreach ($filters as $key => $label): ?>
                <a href="?<?= e(http_build_query(['q' => $search, 'filter' => $key])) ?>"
                   class="btn btn-<?= $filter === $key ? 'primary' : 'light border' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </form>
    <a href="<?= url('medicines/create.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Medicine</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>Medicine</th>
                <th>Form</th>
                <th>Stock</th>
                <th class="d-none d-md-table-cell">Reorder at</th>
                <th>Expiry</th>
                <th class="d-none d-lg-table-cell text-center">Dispensed</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$medicines): ?>
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-capsule"></i>No medicines found.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($medicines as $m):
                $isLow     = $m['stock_qty'] <= $m['reorder_level'];
                $expiryTs  = $m['expiry_date'] ? strtotime($m['expiry_date']) : null;
                $isExpired = $expiryTs !== null && $expiryTs < $today;
                $isSoon    = $expiryTs !== null && !$isExpired && $expiryTs <= $soon;
                // Stock bar: 100% = 3x the reorder level
                $pct = min(100, round($m['stock_qty'] / max(1, $m['reorder_level'] * 3) * 100));
            ?>
                <tr>
                    <td class="fw-semibold"><?= e($m['name']) ?></td>
                    <td><?= e($m['dosage_form']) ?></td>
                    <td style="min-width: 130px;">
                        <div class="d-flex justify-content-between small">
                            <span class="<?= $isLow ? 'text-danger fw-semibold' : '' ?>"><?= (int) $m['stock_qty'] ?> <?= e($m['unit']) ?></span>
                            <?php if ($isLow): ?><span class="badge text-bg-danger">Low</span><?php endif; ?>
                        </div>
                        <div class="bar-track mt-1"><div class="bar-fill" style="width: <?= $pct ?>%; <?= $isLow ? 'background:#dc2626' : '' ?>"></div></div>
                    </td>
                    <td class="d-none d-md-table-cell"><?= (int) $m['reorder_level'] ?></td>
                    <td class="text-nowrap">
                        <?= fmt_date($m['expiry_date']) ?>
                        <?php if ($isExpired): ?><span class="badge text-bg-danger">Expired</span>
                        <?php elseif ($isSoon): ?><span class="badge text-bg-warning">Soon</span><?php endif; ?>
                    </td>
                    <td class="d-none d-lg-table-cell text-center"><?= (int) $m['total_dispensed'] ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url('medicines/edit.php?id=' . $m['id']) ?>" class="btn btn-sm btn-icon btn-light border" title="Edit / Restock"><i class="bi bi-pencil"></i></a>
                        <button type="button" class="btn btn-sm btn-icon btn-light border text-danger" title="Delete"
                                data-delete-url="<?= url('medicines/delete.php') ?>"
                                data-delete-id="<?= $m['id'] ?>"
                                data-delete-name="<?= e($m['name']) ?>">
                            <i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-muted py-3"><?= count($medicines) ?> medicine(s)</div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
