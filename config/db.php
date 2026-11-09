<?php
// config/db.php
// Opens the shared mysqli connection in $conn using credentials from the
// PRIVATE config file (see config/bootstrap.php). No credentials live here.
//
// Pages: on failure a safe 503 page is shown (details only in the error log).
// define('DB_OPTIONAL', true) before including to get $conn = null instead.

require_once __DIR__ . '/bootstrap.php';

$conn = null;

(function () use (&$conn) {
    $db = app_config('db');
    $missing = [];
    foreach (['host', 'name', 'user'] as $k) {
        $v = (string)($db[$k] ?? '');
        if ($v === '' || stripos($v, 'TO_BE_CONFIGURED') !== false) {
            $missing[] = $k;
        }
    }
    if ($missing) {
        error_log('Welzee: database not configured (missing: ' . implode(',', $missing) . '; config source: ' . (app_config('_source') ?: 'none found') . ')');
        if (!defined('DB_OPTIONAL')) {
            app_fail(503, 'Service temporarily unavailable. Please try again shortly.');
        }
        return;
    }

    mysqli_report(MYSQLI_REPORT_STRICT); // throw mysqli_sql_exception, never print
    try {
        $conn = new mysqli((string)$db['host'], (string)$db['user'], (string)$db['pass'], (string)$db['name'], (int)($db['port'] ?? 3306));
        $conn->set_charset('utf8mb4');
        $tz = (string)($db['time_zone'] ?? '');
        if (preg_match('/^[+-]\d{2}:\d{2}$/', $tz)) {
            $conn->query("SET time_zone = '" . $tz . "'");
        }
    } catch (Throwable $e) {
        $conn = null;
        error_log('Welzee DB connection failed: ' . $e->getMessage());
        if (!defined('DB_OPTIONAL')) {
            app_fail(503, 'Service temporarily unavailable. Please try again shortly.');
        }
    }
})();
