<?php
/**
 * sidebar.php
 * ------------------------------------------------------------
 * Left navigation. On large screens it is always visible;
 * on phones/tablets it becomes a Bootstrap offcanvas menu.
 * Uses $activeNav (set by the page) to highlight the current link.
 * ------------------------------------------------------------
 */

// [key, label, icon, path, adminOnly]
$navItems = [
    ['dashboard', 'Dashboard',     'grid-1x2',          'dashboard.php',        false],
    ['patients',  'Patients',      'people',            'patients/index.php',   false],
    ['visits',    'Clinic Visits', 'clipboard2-pulse',  'visits/index.php',     false],
    ['medicines', 'Medicines',     'capsule',           'medicines/index.php',  false],
    ['users',     'Staff Accounts','person-gear',       'users/index.php',      true],
];
?>
<aside class="offcanvas-lg offcanvas-start app-sidebar d-print-none" tabindex="-1" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></span>
        <div>
            <div class="brand-name">School Clinic</div>
            <div class="brand-sub">Records System</div>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto d-lg-none"
                data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Menu</div>
        <?php foreach ($navItems as [$key, $label, $icon, $path, $adminOnly]): ?>
            <?php if ($adminOnly && !is_admin()) continue; ?>
            <a href="<?= url($path) ?>" class="sidebar-link <?= $activeNav === $key ? 'active' : '' ?>">
                <i class="bi bi-<?= $icon ?>"></i><span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>

        <div class="nav-label mt-3">Quick actions</div>
        <a href="<?= url('visits/create.php') ?>" class="sidebar-link">
            <i class="bi bi-plus-circle"></i><span>Record a Visit</span>
        </a>
        <a href="<?= url('patients/create.php') ?>" class="sidebar-link">
            <i class="bi bi-person-plus"></i><span>Add Patient</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <i class="bi bi-calendar3 me-1"></i><?= date('l, M d, Y') ?>
    </div>
</aside>
