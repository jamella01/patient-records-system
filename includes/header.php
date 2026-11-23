<?php
/**
 * header.php
 * ------------------------------------------------------------
 * Opening HTML + page shell (sidebar and top bar).
 * Before including, a page may set:
 *   $pageTitle  - text shown in <title> and the top bar
 *   $activeNav  - key of the sidebar link to highlight
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/functions.php';
require_login();

$pageTitle = $pageTitle ?? 'Dashboard';
$activeNav = $activeNav ?? '';
$user      = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>

    <!-- Bootstrap 5 + Bootstrap Icons + Inter font -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<div class="app-wrapper">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="app-main">
        <!-- Top bar -->
        <header class="app-topbar d-print-none">
            <button class="btn btn-light border d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Open menu">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="app-topbar-title"><?= e($pageTitle) ?></h1>

            <div class="dropdown ms-auto">
                <button class="btn btn-user dropdown-toggle" data-bs-toggle="dropdown">
                    <span class="avatar"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></span>
                    <span class="d-none d-sm-inline"><?= e($user['full_name']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><span class="dropdown-item-text small text-muted">
                        Signed in as <strong><?= e($user['username']) ?></strong> (<?= e($user['role']) ?>)
                    </span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= url('logout.php') ?>">
                        <i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
                </ul>
            </div>
        </header>

        <main class="app-content">
            <?php render_flash(); ?>
