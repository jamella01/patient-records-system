<?php
/**
 * index.php - entry point.
 * Sends logged-in users to the dashboard, everyone else to login.
 */
require_once __DIR__ . '/includes/functions.php';

redirect(current_user() ? 'dashboard.php' : 'login.php');
