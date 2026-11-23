<?php
/**
 * dashboard.php
 * ------------------------------------------------------------
 * Overview: summary counts, recent visits, medicine stock /
 * expiry alerts and most common complaints (last 30 days).
 * ------------------------------------------------------------
 */
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require_once __DIR__ . '/includes/header.php';

/** Run a query that returns a single number. */
function scalar(mysqli $conn, string $sql): int
{
    return (int) $conn->query($sql)->fetch_row()[0];
}

// ----- Summary cards -----
$totalPatients = scalar($conn, 'SELECT COUNT(*) FROM patients');
$visitsToday   = scalar($conn, 'SELECT COUNT(*) FROM visits WHERE DATE(visit_date) = CURDATE()');
$visitsMonth   = scalar($conn, "SELECT COUNT(*) FROM visits WHERE visit_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$lowStockCount = scalar($conn, 'SELECT COUNT(*) FROM medicines WHERE stock_qty <= reorder_level');

// ----- Recent visits -----
$recentVisits = $conn->query(
    "SELECT v.id, v.visit_date, v.complaint, v.temperature, v.disposition,
            p.id AS patient_id, p.first_name, p.last_name, p.id_number
       FROM visits v
       JOIN patients p ON p.id = v.patient_id
   ORDER BY v.visit_date DESC
      LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

// ----- Medicine alerts: low stock OR expiring within 30 days (or already expired) -----
$medAlerts = $conn->query(
    "SELECT id, name, stock_qty, reorder_level, unit, expiry_date,
            (stock_qty <= reorder_level) AS is_low,
            (expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS is_expiring
       FROM medicines
      WHERE stock_qty <= reorder_level
         OR (expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
   ORDER BY expiry_date ASC"
)->fetch_all(MYSQLI_ASSOC);

// ----- Top complaints, grouped by diagnosis (fallback to complaint), last 30 days -----
$topComplaints = $conn->query(
    "SELECT COALESCE(NULLIF(diagnosis, ''), complaint) AS label, COUNT(*) AS total
       FROM visits
      WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
   GROUP BY label
   ORDER BY total DESC
      LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);
$maxComplaint = $topComplaints ? max(array_column($topComplaints, 'total')) : 1;

$stats = [
    ['Registered Patients', $totalPatients, 'people',            'bg-soft-teal',   'patients/index.php'],
    ['Visits Today',        $visitsToday,   'clipboard2-pulse',  'bg-soft-blue',   'visits/index.php?from=' . date('Y-m-d') . '&to=' . date('Y-m-d')],
    ['Visits This Month',   $visitsMonth,   'calendar-check',    'bg-soft-purple', 'visits/index.php?from=' . date('Y-m-01')],
    ['Low Stock Medicines', $lowStockCount, 'exclamation-triangle', $lowStockCount ? 'bg-soft-red' : 'bg-soft-amber', 'medicines/index.php?filter=low'],
];
?>

<!-- Summary cards -->
<div class="row g-3 mb-4">
    <?php foreach ($stats as [$label, $value, $icon, $color, $link]): ?>
        <div class="col-6 col-xl-3">
            <a href="<?= url($link) ?>" class="text-decoration-none text-reset">
                <div class="card h-100">
                    <div class="card-body stat-card">
                        <div class="stat-icon <?= $color ?>"><i class="bi bi-<?= $icon ?>"></i></div>
                        <div>
                            <div class="stat-value"><?= $value ?></div>
                            <div class="stat-label"><?= e($label) ?></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <!-- Recent visits -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <span><i class="bi bi-clock-history me-2 text-primary"></i>Recent Visits</span>
                <a href="<?= url('visits/create.php') ?>" class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-plus-lg"></i> Record Visit
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr><th>Patient</th><th>Complaint</th><th>Temp</th><th>Disposition</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!$recentVisits): ?>
                        <tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i>No visits recorded yet.</div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentVisits as $v): ?>
                        <tr>
                            <td>
                                <a href="<?= url('visits/view.php?id=' . $v['id']) ?>" class="fw-semibold text-decoration-none">
                                    <?= e($v['last_name'] . ', ' . $v['first_name']) ?>
                                </a>
                                <div class="cell-sub"><?= e($v['id_number']) ?></div>
                            </td>
                            <td><?= e($v['complaint']) ?></td>
                            <td class="text-nowrap"><?= temp_display($v['temperature']) ?></td>
                            <td><?= disposition_badge($v['disposition']) ?></td>
                            <td class="text-nowrap">
                                <?= fmt_date($v['visit_date']) ?>
                                <div class="cell-sub"><?= fmt_date($v['visit_date'], 'h:i A') ?></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div class="col-lg-4 d-flex flex-column gap-3">
        <!-- Medicine alerts -->
        <div class="card">
            <div class="card-header"><i class="bi bi-capsule me-2 text-danger"></i>Medicine Alerts</div>
            <ul class="list-group list-group-flush">
                <?php if (!$medAlerts): ?>
                    <li class="list-group-item text-muted small py-3">
                        <i class="bi bi-check-circle text-success me-1"></i> All medicines are in stock and not expiring soon.
                    </li>
                <?php endif; ?>
                <?php foreach ($medAlerts as $m): ?>
                    <li class="list-group-item d-flex align-items-center gap-2">
                        <div class="flex-grow-1">
                            <a href="<?= url('medicines/edit.php?id=' . $m['id']) ?>" class="fw-medium text-decoration-none text-reset">
                                <?= e($m['name']) ?>
                            </a>
                            <div class="cell-sub">
                                <?= (int) $m['stock_qty'] ?> <?= e($m['unit']) ?> left
                                &middot; exp. <?= fmt_date($m['expiry_date']) ?>
                            </div>
                        </div>
                        <?php if ($m['is_low']): ?><span class="badge text-bg-danger">Low</span><?php endif; ?>
                        <?php if ($m['is_expiring']): ?>
                            <span class="badge text-bg-warning">
                                <?= strtotime($m['expiry_date']) < strtotime('today') ? 'Expired' : 'Expiring' ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Top complaints -->
        <div class="card flex-grow-1">
            <div class="card-header"><i class="bi bi-bar-chart me-2 text-primary"></i>Common Cases <span class="text-muted fw-normal small">(last 30 days)</span></div>
            <div class="card-body">
                <?php if (!$topComplaints): ?>
                    <p class="text-muted small mb-0">No data yet.</p>
                <?php endif; ?>
                <?php foreach ($topComplaints as $c): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?= e($c['label']) ?></span>
                            <span class="fw-semibold"><?= (int) $c['total'] ?></span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= round($c['total'] / $maxComplaint * 100) ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
