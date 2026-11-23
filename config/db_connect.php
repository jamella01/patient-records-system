<?php
/**
 * db_connect.php
 * ------------------------------------------------------------
 * Database connection + global app settings.
 *
 * LOCAL (XAMPP)  : keep the values below as they are.
 * INFINITYFREE   : Control Panel -> MySQL Databases, then replace
 *                  DB_HOST  -> e.g. sql123.infinityfree.com
 *                  DB_USER  -> e.g. if0_12345678
 *                  DB_PASS  -> your vPanel / hosting password
 *                  DB_NAME  -> e.g. if0_12345678_clinic_db
 *                  BASE_URL -> ''   (empty, because the app sits in htdocs/ root)
 * ------------------------------------------------------------
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clinic_db');

// URL path of the project folder (no trailing slash).
// XAMPP: '/patient-records-system'   |   InfinityFree root: ''
define('BASE_URL', '/patient-records-system');

// Name shown in the navbar, login page and print slips.
define('APP_NAME', 'School Clinic Records');

// Philippine time for visit timestamps.
date_default_timezone_set('Asia/Manila');

// Make mysqli throw exceptions so errors are not silently ignored.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    // Friendly message instead of exposing credentials / stack trace.
    http_response_code(500);
    exit('<div style="font-family:sans-serif;padding:2rem">
            <h3>Database connection failed</h3>
            <p>Please check the settings in <code>config/db_connect.php</code>
            and make sure the database has been imported.</p>
          </div>');
}
